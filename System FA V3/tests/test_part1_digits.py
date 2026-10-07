import urllib.request
import urllib.parse
import json

BASE_URL = "http://127.0.0.1:8000/api"

def request(endpoint, method="GET", data=None, token=None):
    url = f"{BASE_URL}{endpoint}"
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data if data is not None else {}).encode("utf-8") if (data is not None or method == "POST") else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req) as res:
            return res.getcode(), json.loads(res.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode("utf-8"))

def test_part1_formats():
    print("=== TESTING PART 1 3-DIGIT & 4-DIGIT FORMATS ===")
    
    # 1. Login
    status, login_res = request("/login", method="POST", data={"username": "admin", "password": "admin123"})
    assert status == 200 and login_res.get("ok")
    token = login_res["token"]
    print("1. Login Admin: OK")

    # 2. Reset database trays
    status, reset_res = request("/admin/clear_trays", method="POST", token=token)
    assert status == 200 and reset_res.get("ok")
    print("2. Trays Reset: OK")

    # 3. Check Available Part 1 for 3 digits ('001', '099', '999')
    status, avail_3 = request("/master/available?type=1&q=001", token=token)
    assert status == 200 and avail_3.get("ok")
    assert any(x['sn'] == '001' for x in avail_3['data']), f"SN 001 not found in available: {avail_3['data'][:5]}"
    print("3a. Query 3-digit SN '001': FOUND -> '001'")

    # 4. Check Available Part 1 for 4 digits ('1000', '6000')
    status, avail_4 = request("/master/available?type=1&q=1000", token=token)
    assert status == 200 and avail_4.get("ok")
    assert any(x['sn'] == '1000' for x in avail_4['data']), f"SN 1000 not found in available: {avail_4['data'][:5]}"
    print("3b. Query 4-digit SN '1000': FOUND -> '1000'")

    # 5. Create Tray with a combination of 3-digit and 4-digit Part 1 SNs
    payload = {
        "pic_warehouse": "Budi Gudang",
        "product_type": "Nanggala P9",
        "assigned_operator": "Kholifaturrohmah",
        "source_document_mo": "MH/MO/000001",
        "parts": [
            {"part_type": "1", "sn": "001"},       # 3 digits
            {"part_type": "12", "sn": "60001"},
            {"part_type": "15", "sn": "J1100001"},
            {"part_type": "1", "sn": "1000"},      # 4 digits
            {"part_type": "12", "sn": "60002"},
            {"part_type": "15", "sn": "G1100002"}
        ]
    }
    status, save_res = request("/gudang/save", method="POST", data=payload, token=token)
    assert status == 200 and save_res.get("ok"), f"Gudang save failed: {save_res}"
    tray_id = save_res["data"]["id"]
    tray_code = save_res["data"]["tray_code"]
    print(f"4. Tray Created Successfully with 3-digit ('001') & 4-digit ('1000') Part 1 -> {tray_code}")

    # 6. Assembly Save Set #1 with 3-digit SN '001'
    status, a1 = request("/assembly/save", method="POST", data={
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "operator1",
        "part15_sn": "J1100001",
        "part12_sn": "60001",
        "part1_sn": "001",
        "status": "Good"
    }, token=token)
    assert status == 200 and a1.get("ok"), f"Assembly set #1 failed: {a1}"
    print("5a. Assembly Set #1 (Part 1 SN '001'): SUCCESS")

    # 7. Assembly Save Set #2 with 4-digit SN '1000'
    status, a2 = request("/assembly/save", method="POST", data={
        "tray_id": tray_id,
        "set_number": 2,
        "operator": "operator1",
        "part15_sn": "G1100002",
        "part12_sn": "60002",
        "part1_sn": "1000",
        "status": "Good"
    }, token=token)
    assert status == 200 and a2.get("ok"), f"Assembly set #2 failed: {a2}"
    print("5b. Assembly Set #2 (Part 1 SN '1000'): SUCCESS")

    # 8. Check Laporan returns exact SNs
    status, lap = request("/laporan", token=token)
    assert status == 200 and len(lap["data"]) == 2
    part1_sns = [x["part1_sn"] for x in lap["data"]]
    assert "001" in part1_sns and "1000" in part1_sns, f"Expected ['001', '1000'], got {part1_sns}"
    print(f"6. Laporan Traceability Part 1 SNs Verified: {part1_sns}")

    print("\n=== ALL PART 1 (3 DIGIT 001-999 & 4 DIGIT 1000-6000) TESTS PASSED 100%! ===")

if __name__ == "__main__":
    test_part1_formats()
