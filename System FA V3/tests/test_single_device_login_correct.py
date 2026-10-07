import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import json
import urllib.request
import urllib.error
import threading
import socket
from app import Handler, ensure_db_schema, get_conn
from http.server import ThreadingHTTPServer

def get_free_port():
    s = socket.socket()
    s.bind(('', 0))
    port = s.getsockname()[1]
    s.close()
    return port

def run_test():
    ensure_db_schema()
    port = get_free_port()
    server = ThreadingHTTPServer(('127.0.0.1', port), Handler)
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()
    base_url = f"http://127.0.0.1:{port}"
    print(f"Test server started at {base_url}")

    def request(path, method="GET", body=None, token=None):
        url = f"{base_url}{path}"
        headers = {"Content-Type": "application/json"}
        if token:
            headers["Authorization"] = f"Bearer {token}"
        data = json.dumps(body).encode("utf-8") if body else None
        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req) as res:
                return res.getcode(), json.loads(res.read().decode("utf-8"))
        except urllib.error.HTTPError as e:
            try:
                return e.code, json.loads(e.read().decode("utf-8"))
            except Exception:
                return e.code, {"error": str(e)}

    try:
        # Clear existing sessions first
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("DELETE FROM user_sessions")
                conn.commit()

        print("\n=== STEP 1: Device 1 logs in as 'admin' ===")
        code, res1 = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 200 and res1.get("ok"), f"Login 1 failed: {res1}"
        token1 = res1["token"]
        print(f"[OK] Device 1 logged in successfully. Token: {token1[:8]}...")

        # Device 1 accesses API
        code, data1 = request("/api/admin/users", "GET", token=token1)
        assert code == 200 and data1.get("ok"), f"Device 1 access failed: {data1}"
        print("[OK] Device 1 authenticated & active.")

        print("\n=== STEP 2: Device 2 attempts to login with 'admin' while Device 1 is active ===")
        code, res2 = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 409, f"Expected 409 Conflict, got {code}: {res2}"
        assert res2.get("is_active_elsewhere") is True or "sedang digunakan" in res2.get("error", "")
        print(f"[OK] Device 2 properly BLOCKED with 409 Conflict: '{res2.get('error')}'")

        print("\n=== STEP 3: Verify Device 1 is NOT affected and still active ===")
        code, data1_check = request("/api/admin/users", "GET", token=token1)
        assert code == 200 and data1_check.get("ok")
        print("[OK] Device 1 session remains active and uninterrupted.")

        print("\n=== STEP 4: Device 1 logs out explicitly ===")
        code, logout_res = request("/api/logout", "POST", token=token1)
        assert code == 200 and logout_res.get("ok")
        print(f"[OK] Device 1 logged out: {logout_res.get('message')}")

        print("\n=== STEP 5: Device 2 attempts to login again ===")
        code, res2_retry = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 200 and res2_retry.get("ok"), f"Device 2 login after logout failed: {res2_retry}"
        token2 = res2_retry["token"]
        print(f"[OK] Device 2 logged in successfully! Token: {token2[:8]}...")

        print("\n=== STEP 6: Test Admin Reset Session for a locked user ===")
        # 6.1 Assy logs in on workstation
        code, assy_res = request("/api/login", "POST", {"username": "Assy", "password": "assy123"})
        assert code == 200 and assy_res.get("ok")
        print("[OK] User 'Assy' logged in on Workstation.")

        # 6.2 Another device attempts to login as 'Assy' -> blocked
        code, assy_blocked = request("/api/login", "POST", {"username": "Assy", "password": "assy123"})
        assert code == 409
        print(f"[OK] Second device for 'Assy' blocked with 409: '{assy_blocked.get('error')}'")

        # 6.3 Admin unlocks/resets Assy session
        code, reset_res = request("/api/admin/users/reset-session", "POST", {"username": "Assy"}, token=token2)
        assert code == 200 and reset_res.get("ok")
        print(f"[OK] Admin reset session for 'Assy': {reset_res.get('message')}")

        # 6.4 Now second device can log in as 'Assy' immediately
        code, assy_unblocked = request("/api/login", "POST", {"username": "Assy", "password": "assy123"})
        assert code == 200 and assy_unblocked.get("ok")
        print("[OK] User 'Assy' logged in on new device after Admin reset.")

        print("\n[PASS] ALL SINGLE DEVICE LOGIN & SESSION LOCKING TESTS PASSED 100%!")

    finally:
        server.shutdown()
        server.server_close()

if __name__ == "__main__":
    run_test()
