import json
import urllib.request
import urllib.parse
import sys

BASE_URL = "http://127.0.0.1:8000"

def request(path, method="GET", data=None, token=None):
    url = f"{BASE_URL}{path}"
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data).encode("utf-8") if data else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req) as resp:
            return resp.status, json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode("utf-8"))

def test_qc_role():
    print("=== TESTING ROLE QC INTEGRATION ===")

    # 1. Login as Admin
    status, res = request("/api/login", method="POST", data={"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        status, res = request("/api/login", method="POST", data={"username": "admin", "password": "admin123", "force_takeover": True})
    assert status == 200 and res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("[OK] Admin login successful.")

    # Clean up test user if previously exists
    status, users_res = request("/api/admin/users", "GET", token=admin_token)
    if users_res.get("ok"):
        for u in users_res.get("data", []):
            if u.get("username") in ["test_qc_user", "test_qc_updated"]:
                request("/api/admin/users/delete", "POST", {"id": u["id"]}, token=admin_token)

    # 2. Create user with role 'qc'
    qc_payload = {
        "nik": "NIK-QC-TEST-001",
        "full_name": "QC Inspector Test",
        "username": "test_qc_user",
        "password": "qc_password123",
        "role": "qc"
    }
    status, create_res = request("/api/admin/users", "POST", qc_payload, token=admin_token)
    assert status == 200 and create_res.get("ok"), f"Failed to create QC user: {create_res}"
    qc_user = create_res["data"]
    assert qc_user["role"] == "qc", f"Expected role 'qc', got {qc_user.get('role')}"
    print(f"[OK] Successfully created user with role 'qc': id={qc_user['id']}, role={qc_user['role']}")

    # 3. Update user role
    update_payload = {
        "id": qc_user["id"],
        "nik": "NIK-QC-TEST-001",
        "full_name": "QC Inspector Senior",
        "username": "test_qc_user",
        "role": "qc"
    }
    status, update_res = request("/api/admin/users/update", "POST", update_payload, token=admin_token)
    assert status == 200 and update_res.get("ok"), f"Failed to update QC user: {update_res}"
    assert update_res["data"]["full_name"] == "QC Inspector Senior"
    assert update_res["data"]["role"] == "qc"
    print("[OK] Successfully updated user keeping role 'qc'.")

    # 4. Login as newly created QC user
    status, login_qc = request("/api/login", "POST", {"username": "test_qc_user", "password": "qc_password123", "force_takeover": True})
    assert status == 200 and login_qc.get("ok"), f"QC login failed: {login_qc}"
    assert login_qc["role"] == "qc", f"Expected login role 'qc', got {login_qc.get('role')}"
    qc_token = login_qc["token"]
    print(f"[OK] QC user login successful. Acquired token with role='{login_qc['role']}'.")

    # 5. Access QC endpoints with QC token
    status, qc_items = request("/api/qc/pending-items", "GET", token=qc_token)
    assert status == 200 and qc_items.get("ok"), f"QC token cannot access /api/qc/pending-items: {qc_items}"
    print("[OK] QC user can access /api/qc/pending-items.")

    status, qc_reports = request("/api/reports/qc-tests", "GET", token=qc_token)
    assert status == 200 and qc_reports.get("ok"), f"QC token cannot access /api/reports/qc-tests: {qc_reports}"
    print("[OK] QC user can access /api/reports/qc-tests.")

    # 6. Clean up created test user
    status, del_res = request("/api/admin/users/delete", "POST", {"id": qc_user["id"]}, token=admin_token)
    assert status == 200 and del_res.get("ok"), f"Failed to delete test QC user: {del_res}"
    print("[OK] Test QC user deleted and cleaned up successfully.")

    print("\nALL ROLE QC FUNCTIONALITIES AND PERMISSIONS VERIFIED 100%!")

if __name__ == "__main__":
    test_qc_role()
