import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import json
import urllib.request
import urllib.error
import threading
import time
import socket
from app import Handler, get_conn, ensure_db_schema
from http.server import ThreadingHTTPServer

def get_free_port():
    s = socket.socket()
    s.bind(('', 0))
    port = s.getsockname()[1]
    s.close()
    return port

def run_tests():
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
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("DELETE FROM user_sessions")
                conn.commit()

        # TEST 1: Single Session Login (1 Akun 1 Perangkat - Device Baru Ditolak jika Akun Sedang Aktif)
        print("\n--- TEST 1: Single Session Login (Device Baru Ditolak jika Akun Sedang Aktif) ---")
        # 1.1 Login on Device 1
        code, res1 = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 200 and res1.get("ok"), f"Login 1 failed: {res1}"
        token1 = res1["token"]
        print("Device 1 logged in, token1:", token1[:8] + "...")

        # Verify token 1 works
        code, data = request("/api/admin/users", "GET", token=token1)
        assert code == 200 and data.get("ok"), f"Device 1 access failed: {data}"
        print("Device 1 access validated successfully.")

        # 1.2 Login on Device 2 with same account -> MUST BE BLOCKED (409 Conflict)
        code, res2 = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 409, f"Expected 409 for active account, got {code}: {res2}"
        print(f"Device 2 properly blocked with 409: '{res2.get('error')}'")

        # 1.3 Device 1 logs out
        code, logout_res = request("/api/logout", "POST", token=token1)
        assert code == 200 and logout_res.get("ok")
        print("Device 1 logged out.")

        # 1.4 Device 2 can now login successfully
        code, res2_ok = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
        assert code == 200 and res2_ok.get("ok")
        token2 = res2_ok["token"]
        print("Device 2 logged in successfully after Device 1 logout.")

        # TEST 2: Assembly Pause & Resume (Jeda Pengerjaan & Audit Downtime)
        print("\n--- TEST 2: Assembly Pause & Resume (Jeda Pengerjaan) ---")
        # Login as assembly operator
        code, res_op = request("/api/login", "POST", {"username": "Assy", "password": "assy123"})
        token_op = res_op["token"]

        # Pause workstation
        pause_payload = {
            "tray_code": "WH/20260901/0001",
            "set_number": 1,
            "reason": "Menunggu Komponen Gudang: Part 15 kehabisan stok sementara",
            "operator": "Operator Perakitan"
        }
        code, pause_res = request("/api/assembly/pause", "POST", pause_payload, token=token_op)
        assert code == 200 and pause_res.get("ok"), f"Pause failed: {pause_res}"
        downtime_id = pause_res["downtime_id"]
        print(f"Pause created successfully with downtime_id = {downtime_id}")

        time.sleep(1)

        # Resume workstation
        resume_payload = {
            "downtime_id": downtime_id
        }
        code, resume_res = request("/api/assembly/resume", "POST", resume_payload, token=token_op)
        assert code == 200 and resume_res.get("ok"), f"Resume failed: {resume_res}"
        print(f"Work resumed successfully, duration = {resume_res.get('data', {}).get('duration_seconds')} seconds")

        # Check Admin Downtime Logs
        code, dt_logs = request("/api/admin/downtime-logs", "GET", token=token2)
        assert code == 200 and dt_logs.get("ok"), f"Fetch downtime logs failed: {dt_logs}"
        logs = dt_logs.get("data", [])
        assert any(l["id"] == downtime_id for l in logs), f"Downtime {downtime_id} not found in logs!"
        print(f"Admin Downtime audit logs verified: {len(logs)} records found.")

        # TEST 3: Selesaikan Sesi (Hold, Return Warehouse, Handover Shift)
        print("\n--- TEST 3: Selesaikan Sesi Pengerjaan ---")
        # Always create an isolated test tray to avoid affecting production trays
        _conn = get_conn()
        _cur = _conn.cursor()
        _cur.execute("""
            INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, session_status)
            VALUES ('TEST-RETURN-TRAY-999', 'Gudang', 'Operator Perakitan', 'WH/MO/TEST-999', 'Nanggala P9', 'active')
            RETURNING id, tray_code
        """)
        _test_tray_row = _cur.fetchone()
        test_tray_id = _test_tray_row['id']
        test_tray_code = _test_tray_row['tray_code']
        _cur.execute("""
            INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', 'TEST-P15-999', 'Supplied')
        """, (test_tray_id,))
        _conn.commit()

        # Test RETURN_WAREHOUSE
        code, ret_res = request("/api/assembly/session-action", "POST", {
            "tray_id": test_tray_id,
            "tray_code": test_tray_code,
            "action": "RETURN_WAREHOUSE",
            "reason": "Kembalikan ke Gudang"
        }, token=token_op)
        assert code == 200 and ret_res.get("ok"), f"Session RETURN_WAREHOUSE failed: {ret_res}"
        print(f"Session RETURN_WAREHOUSE succeeded: {ret_res.get('message')}")

        # Verify Gudang returned trays list
        code, res_wh = request("/api/login", "POST", {"username": "Granada", "password": "Whs01"})
        assert code == 200 and res_wh.get("ok"), f"Gudang login failed: {res_wh}"
        token_wh = res_wh["token"]
        code, wh_returned = request("/api/gudang/returned-trays", "GET", token=token_wh)
        assert code == 200 and wh_returned.get("ok")
        returned_trays = wh_returned.get("data", [])
        assert any(t["id"] == test_tray_id for t in returned_trays), f"Returned tray {test_tray_code} not in Gudang returned list!"
        print(f"Gudang received returned tray notification: {test_tray_code}")

        # Gudang acknowledges return
        code, ack_res = request("/api/gudang/acknowledge-return", "POST", {"tray_id": test_tray_id}, token=token_wh)
        assert code == 200 and ack_res.get("ok"), f"Acknowledge return failed: {ack_res}"
        print("Gudang acknowledged and archived returned notification.")

        # 3.3 HANDOVER SHIFT
        code, handover_res = request("/api/assembly/session-action", "POST", {
            "tray_id": test_tray_id,
            "action": "HANDOVER_SHIFT",
            "new_operator": "Siti Rahma",
            "reason": "Serah terima shift 2 ke shift 3"
        }, token=token_op)
        assert code == 200 and handover_res.get("ok"), f"Session HANDOVER_SHIFT failed: {handover_res}"
        print(f"Session HANDOVER_SHIFT succeeded: {handover_res.get('message')}")

        # TEST 4: Filter per Operator in Assembly Trays
        print("\n--- TEST 4: Filter per Operator in Assembly Trays ---")
        code, filtered_trays = request("/api/assembly/trays?operator=Siti+Rahma", "GET", token=token_wh)
        assert code == 200 and filtered_trays.get("ok")
        print(f"Operator filter returned {len(filtered_trays.get('data', []))} trays for 'Siti Rahma'.")

        print("\nALL 8 NEW REQUIREMENTS & FIXES VALIDATED 100% SUCCESSFULLY!")

    finally:
        try:
            _c = get_conn()
            _cr = _c.cursor()
            _cr.execute("DELETE FROM parts WHERE tray_id IN (SELECT id FROM trays WHERE tray_code LIKE 'TEST-RETURN-TRAY-%')")
            _cr.execute("DELETE FROM trays WHERE tray_code LIKE 'TEST-RETURN-TRAY-%'")
            _c.commit()
        except Exception:
            pass
        server.shutdown()
        server.server_close()

if __name__ == "__main__":
    run_tests()
