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
    print("=== TESTING GUDANG ASSEMBLY ACCESS & SECURITY PERMISSIONS ===")

    # 1. Login as Admin
    status, res = request("/api/login", method="POST", data={"username": "admin", "password": "admin123"})
    assert status == 200 and res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("1. Login as Admin: SUCCESS")

    # 2. Clear Database to fresh state
    status, res = request("/api/admin/clear_trays", method="POST", token=admin_token)
    assert status == 200 and res.get("ok"), "Clear trays failed"

    # 3. Login as Gudang (Aziz)
    status, res = request("/api/login", method="POST", data={"username": "aziz", "password": "4z1z"})
    assert status == 200 and res.get("ok"), f"Gudang login failed: {res}"
    gudang_token = res["token"]
    assert res.get("role") == "gudang", f"Expected role gudang, got {res.get('role')}"
    print("2. Login as Gudang (Aziz): SUCCESS (Role = gudang)")

    # 4. Gudang creates a new Tray
    gudang_payload = {
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
    status, res = request("/api/gudang/save", method="POST", data=gudang_payload, token=gudang_token)
    assert status == 200 and res.get("ok"), f"Create tray failed: {res}"
    tray = res["data"]
    tray_id = tray["id"]
    tray_code = tray["tray_code"]
    print(f"3. Gudang Create Tray: SUCCESS -> {tray_code} (ID: {tray_id})")

    # 5. Gudang fetches assembly trays list
    status, res = request("/api/assembly/trays", method="GET", token=gudang_token)
    assert status == 200 and res.get("ok"), f"Fetch assembly trays failed: {res}"
    trays = res["data"]
    assert any(t["id"] == tray_id for t in trays), "Created tray not visible to Gudang in assembly trays list"
    print(f"4. Gudang Fetch Assembly Trays: SUCCESS (Found {len(trays)} trays)")

    # 6. Gudang fetches tray detail / assembly items
    status, res = request(f"/api/tray/detail?id={tray_id}", method="GET", token=gudang_token)
    assert status == 200 and res.get("ok"), f"Fetch tray detail failed: {res}"
    detail = res["data"]
    assert detail["tray"]["id"] == tray_id
    assert len(detail["parts"]) == 3
    print(f"5. Gudang Fetch Tray Detail: SUCCESS (Tray details and 3 parts loaded)")

    # 7. SECURITY TEST: Gudang attempts to SAVE/EXECUTE assembly -> MUST BE REJECTED WITH 403
    assy_attempt = {
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "Aziz",
        "part15": "J1100001",
        "part1": "001",
        "part12": "60001",
        "status": "Good"
    }
    status, res = request("/api/assembly/save", method="POST", data=assy_attempt, token=gudang_token)
    assert status == 403, f"Expected 403 Forbidden for Gudang assembly save, got {status}"
    assert not res.get("ok"), "Gudang was able to save assembly! Security failure."
    print("6. Gudang Assembly Save Attempt: BLOCKED WITH 403 (Security OK)")

    # 8. Assembly Operator logs in and performs assembly
    status, res = request("/api/login", method="POST", data={"username": "olif", "password": "0l1f"})
    assert status == 200, f"Assembly operator login failed: {res}"
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
    assert status == 200 and res.get("ok"), f"Operator assembly save failed: {res}"
    print("7. Assembly Operator Save Set #1: SUCCESS")

    # 9. Gudang monitors and loads updated tray details (with completed assembly set for label printing)
    status, res = request(f"/api/tray/detail?id={tray_id}", method="GET", token=gudang_token)
    assert status == 200 and res.get("ok"), "Gudang failed to fetch updated tray detail"
    assemblies = res["data"]["assemblies"]
    assert len(assemblies) == 1
    assert assemblies[0]["part15_sn"] == "J1100001"
    assert assemblies[0]["status"] == "Good"
    print(f"8. Gudang Monitor Updated Assembly: SUCCESS -> Set #1 with Part 15 SN {assemblies[0]['part15_sn']} is Good (Ready for Label Printing)")

    print("\n=== ALL GUDANG PERMISSIONS & MONITORING TESTS PASSED 100%! ===")

if __name__ == "__main__":
    test()
