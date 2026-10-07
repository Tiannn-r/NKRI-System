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

def test():
    print("=== TESTING ADMIN DELETE VERIFICATION ITEM FEATURE ===")

    # 1. Login as Admin
    status, res = request("/api/login", method="POST", data={"username": "admin", "password": "admin123", "force_takeover": True})
    assert status == 200, f"Admin login failed: {res}"
    admin_token = res["token"]

    # 2. Login as Gudang (Aziz) & Request Re-register for Part 1 SN 004
    status, res = request("/api/login", method="POST", data={"username": "Aziz", "password": "Whs04", "force_takeover": True})
    assert status == 200, f"Gudang login failed: {res}"
    gudang_token = res["token"]

    # Submit a re-register request
    req_payload = {
        "part_type": "1",
        "sn": "004",
        "target_status": "Hold",
        "reason": "Test delete button feature"
    }
    status, res = request("/api/master/re-register", method="POST", data=req_payload, token=gudang_token)
    assert status == 200 and res.get("ok"), f"Submit re-register failed: {res}"
    print("1. Submitted Re-register Request for Part 1 SN 004: SUCCESS")

    # 3. Fetch Pending Verifications as Admin
    status, res = request("/api/admin/pending-verifications", method="GET", token=admin_token)
    assert status == 200 and res.get("ok"), "Fetch pending verifications failed"
    requests = res.get("data", [])
    target_req = next((r for r in requests if r["sn"] == "004" and str(r["part_type"]) == "1" and r["status"] == "PENDING"), None)
    assert target_req is not None, "Target request not found in pending list"
    req_id = target_req["id"]
    print(f"2. Found Pending Request ID #{req_id}: SUCCESS")

    # 4. Admin deletes the request via /api/admin/delete-re-register
    delete_payload = {
        "request_id": req_id,
        "sn": target_req["sn"],
        "part_type": target_req["part_type"]
    }
    status, res = request("/api/admin/delete-re-register", method="POST", data=delete_payload, token=admin_token)
    assert status == 200 and res.get("ok"), f"Delete re-register failed: {res}"
    print("3. Admin Delete Verification Request: SUCCESS")

    # 5. Verify target ID is no longer in pending list
    status, res = request("/api/admin/pending-verifications", method="GET", token=admin_token)
    assert status == 200 and res.get("ok"), "Fetch pending verifications failed"
    requests = res.get("data", [])
    assert not any(r["id"] == req_id for r in requests), f"Request ID #{req_id} still present in pending list!"
    print("4. Verification item confirmed deleted from queue: SUCCESS")

    print("\n=== ALL ADMIN DELETE VERIFICATION TESTS PASSED 100%! ===")

if __name__ == "__main__":
    test()
