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
    print("=== TESTING TRAY VERIFICATION BLOCKING WHEN UNVERIFIED SETS EXIST ===")

    # 1. Login as Admin & Clear Trays
    status, res = request("/api/login", method="POST", data={"username": "admin", "password": "admin123"})
    assert status == 200, "Admin login failed"
    admin_token = res["token"]

    status, res = request("/api/admin/clear_trays", method="POST", token=admin_token)
    assert status == 200, "Clear trays failed"

    # 2. Login as Gudang (Aziz)
    status, res = request("/api/login", method="POST", data={"username": "aziz", "password": "4z1z"})
    assert status == 200, "Gudang login failed"
    gudang_token = res["token"]

    # 3. Create Tray in Gudang
    tray_payload = {
        "pic_warehouse": "Aziz",
        "assigned_operator": "Kholifaturrohmah",
        "source_document_mo": "WH/MO/260831/0001",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": "J1100001"},
            {"part_type": "1", "sn": "001"},
            {"part_type": "12", "sn": "60001"}
        ]
    }
    status, res = request("/api/gudang/save", method="POST", data=tray_payload, token=gudang_token)
    assert status == 200 and res.get("ok"), "Create tray failed"
    tray_id = res["data"]["id"]
    tray_code = res["data"]["tray_code"]

    # 4. Assembly Operator finishes Set #1
    status, res = request("/api/login", method="POST", data={"username": "olif", "password": "0l1f"})
    assert status == 200, "Assembly login failed"
    assy_token = res["token"]

    assy_payload = {
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "Kholifaturrohmah",
        "part15": "J1100001",
        "part1": "001",
        "part12": "60001",
        "status": "Good"
    }
    status, res = request("/api/assembly/save", method="POST", data=assy_payload, token=assy_token)
    assert status == 200 and res.get("ok"), "Assembly save failed"
    print("1. Assembled Set #1: SUCCESS (Status: Good, verified_status: NULL)")

    # 5. BUG VERIFICATION: Try to complete tray verification BEFORE any set is ACC/DECLINED -> MUST RETURN 400 ERROR
    status, res = request("/api/gudang/verify-tray", method="POST", data={"tray_id": tray_id}, token=gudang_token)
    assert status == 400, f"Expected HTTP 400 for unverified sets, got {status}: {res}"
    assert not res.get("ok"), "Verify tray succeeded when sets were not verified! Bug still present."
    print(f"2. Complete Tray Verification WITHOUT ACC/DECLINE: PROPERLY BLOCKED ({res.get('error')})")

    # 6. Gudang performs ACC verification on Set #1
    acc_payload = {
        "tray_id": tray_id,
        "set_number": 1,
        "action": "ACC",
        "status": "Good"
    }
    status, res = request("/api/gudang/verify-set", method="POST", data=acc_payload, token=gudang_token)
    assert status == 200 and res.get("ok"), f"ACC Set #1 failed: {res}"
    print("3. Gudang ACC Set #1: SUCCESS")

    # 7. Now complete tray verification -> MUST SUCCEED WITH 200 OK
    status, res = request("/api/gudang/verify-tray", method="POST", data={"tray_id": tray_id}, token=gudang_token)
    assert status == 200 and res.get("ok"), f"Verify tray failed after all sets ACC: {res}"
    print("4. Complete Tray Verification AFTER ALL SETS ACC: SUCCESS (HTTP 200 OK)")

    # 8. Check Traveler report data
    status, res = request(f"/api/tray/detail?id={tray_id}", method="GET", token=gudang_token)
    assert status == 200 and res.get("ok"), "Fetch tray detail failed"
    tray_data = res["data"]
    assert tray_data["tray"]["verified_by_gudang"] == True, "Tray not marked verified"
    assert tray_data["assemblies"][0]["verified_status"] == "ACC", "Assembly set #1 not marked ACC"
    print("5. Verify Document State: Tray is verified and Set #1 has verified_status = 'ACC'")

    print("\n=== ALL VERIFICATION BLOCKING TESTS PASSED 100%! ===")

if __name__ == "__main__":
    test()
