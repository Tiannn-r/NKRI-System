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

print("=== STARTING TEST FOR ALL REVISED FEATURES ===")

# 1. Login as Admin
status, login_res = request("/login", method="POST", data={"username": "admin", "password": "admin123"})
assert status == 200 and login_res.get("ok"), f"Login failed: {login_res}"
token = login_res["token"]
print("1. Login as Admin: SUCCESS")

# 2. Reset trays & production
status, clear_res = request("/admin/clear_trays", method="POST", token=token)
assert status == 200 and clear_res.get("ok"), f"Clear trays failed: {clear_res}"
print("2. Admin Clear Trays: SUCCESS")

# 3a. Test Gudang Save Tray WITHOUT assigned_operator -> MUST return HTTP 400
bad_gudang_payload = {
    "pic_warehouse": "Budi Gudang",
    "product_type": "Nanggala P9",
    "assigned_operator": "",
    "source_document_mo": "MH/MO/000001",
    "parts": [
        {"part_type": "1", "sn": "001"},
        {"part_type": "12", "sn": "60001"},
        {"part_type": "15", "sn": "J1100001"}
    ]
}
status, bad_gudang_res = request("/gudang/save", method="POST", data=bad_gudang_payload, token=token)
assert status == 400, f"Expected 400 for empty assigned_operator, got {status}"
assert "Operator Assembly wajib dipilih" in bad_gudang_res.get("error", "")
print("3a. Gudang Save WITHOUT operator: BLOCKED (Validation OK)")

# 3b. Create Tray in Gudang with valid Operator Assembly 'Kholifaturrohmah'
gudang_payload = {
    "pic_warehouse": "Budi Gudang",
    "product_type": "Nanggala P9",
    "assigned_operator": "Kholifaturrohmah",
    "source_document_mo": "MH/MO/000001",
    "parts": [
        {"part_type": "1", "sn": "001"},
        {"part_type": "12", "sn": "60001"},
        {"part_type": "15", "sn": "J1100001"},
        {"part_type": "1", "sn": "002"},
        {"part_type": "12", "sn": "60002"},
        {"part_type": "15", "sn": "G1100002"}
    ]
}
status, gudang_res = request("/gudang/save", method="POST", data=gudang_payload, token=token)
assert status == 200 and gudang_res.get("ok"), f"Gudang save failed: {gudang_res}"
tray = gudang_res["data"]
tray_code = tray["tray_code"]
tray_id = tray.get("tray_id") or tray.get("id")
print(f"3b. Gudang Save Tray: SUCCESS -> {tray_code} (Tray ID: {tray_id}, Operator: {tray['assigned_operator']})")

# 4. Mandatory Operator Validation in Assembly Save
# Attempt 4a: Save without operator -> MUST return HTTP 400
status, fail_res = request("/assembly/save", method="POST", data={
    "tray_id": tray_id,
    "set_number": 1,
    "operator": "",
    "part15_sn": "J1100001",
    "part12_sn": "60001",
    "part1_sn": "001",
    "status": "Good",
    "reason": ""
}, token=token)
assert status == 400, f"Expected 400 status, got {status}"
assert "Nama Operator Assembly wajib diisi" in fail_res.get("error", "")
print("4a. Operator Assembly Mandatory Validation: SUCCESS (Blocked save without operator)")

# Attempt 4b: Save with operator 'Siti Assembly' -> SUCCESS
status, pass_res = request("/assembly/save", method="POST", data={
    "tray_id": tray_id,
    "set_number": 1,
    "operator": "operator1",
    "part15_sn": "J1100001",
    "part12_sn": "60001",
    "part1_sn": "001",
    "status": "Good",
    "reason": ""
}, token=token)
assert status == 200 and pass_res.get("ok"), f"Assembly save failed: {pass_res}"
print("4b. Assembly Save Set #1: SUCCESS")

# Save Set #2 as Scrap with reason
status, pass_res2 = request("/assembly/save", method="POST", data={
    "tray_id": tray_id,
    "set_number": 2,
    "operator": "operator1",
    "part15_sn": "G1100002",
    "part12_sn": "60002",
    "part1_sn": "002",
    "status": "Scrap",
    "reason": "Pin connector Part 12 patah saat perakitan"
}, token=token)
assert status == 200 and pass_res2.get("ok"), f"Assembly save set #2 failed: {pass_res2}"
print("4c. Assembly Save Set #2 (Scrap with reason): SUCCESS")

# 5. Check Tray Assigned Operator
status, tray_detail = request(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", token=token)
assert status == 200 and tray_detail.get("ok")
assert tray_detail["data"]["tray"]["assigned_operator"] in ["Kholifaturrohmah", "operator1"], f"Unexpected assigned operator: {tray_detail['data']['tray']['assigned_operator']}"
print(f"5. Tray Assigned Operator Verified: SUCCESS -> {tray_detail['data']['tray']['assigned_operator']}")

# 6. Test Laporan Traceability Filters
# 6a. Filter by status 'Good'
status, lap_good = request("/laporan?status=Good", token=token)
assert status == 200 and len(lap_good["data"]) == 1
assert lap_good["data"][0]["part15_sn"] == "J1100001"
print(f"6a. Filter by Status 'Good': SUCCESS (1 record returned)")

# 6b. Filter by status 'Scrap'
status, lap_scrap = request("/laporan?status=Scrap", token=token)
assert status == 200 and len(lap_scrap["data"]) == 1
assert lap_scrap["data"][0]["part15_sn"] == "G1100002"
assert "Pin connector Part 12 patah" in lap_scrap["data"][0]["reason"]
print(f"6b. Filter by Status 'Scrap': SUCCESS (1 record returned with reason)")

# 6c. Filter by operator
status, lap_op = request("/laporan?operator=operator1", token=token)
assert status == 200 and len(lap_op["data"]) == 2
print(f"6c. Filter by Operator 'operator1': SUCCESS (2 records returned)")

# 6d. Filter by tray_code
status, lap_tray = request(f"/laporan?tray_code={urllib.parse.quote(tray_code)}", token=token)
assert status == 200 and len(lap_tray["data"]) == 2
print(f"6d. Filter by No Doc '{tray_code}': SUCCESS (2 records returned)")

# 7. Update MO on tray
status, update_mo_res = request("/tray/update_mo", method="POST", data={
    "tray_id": tray_id,
    "source_document_mo": "MH/MO/000099"
}, token=token)
assert status == 200 and update_mo_res.get("ok")
status, tray_check = request(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", token=token)
assert tray_check["data"]["tray"]["source_document_mo"] == "MH/MO/000099"
print("7. Quick Update Source Doc MO: SUCCESS -> MH/MO/000099")

# Cleanup
request("/admin/clear_trays", method="POST", token=token)
print("\n=== ALL REVISED FEATURES VERIFIED 100% SUCCESSFULLY! ===")
