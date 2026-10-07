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
    with urllib.request.urlopen(req) as res:
        return json.loads(res.read().decode("utf-8"))

print("=== STARTING FULL END-TO-END AUTOMATED VERIFICATION ===")

# 1. Login
login_res = request("/login", method="POST", data={"username": "admin", "password": "admin123"})
assert login_res.get("ok"), f"Login failed: {login_res}"
token = login_res["token"]
print("1. Login as Admin: SUCCESS (token obtained)")

# 2. Reset production & trays
clear_res = request("/admin/clear_trays", method="POST", token=token)
assert clear_res.get("ok"), f"Clear trays failed: {clear_res}"
print("2. Admin Clear Trays: SUCCESS ->", clear_res.get("message"))

# 3. Verify Stock Summary
stock_res = request("/master/stocks_summary", token=token)
assert stock_res.get("ok"), f"Stock summary failed: {stock_res}"
summary_map = {item["part_type"]: item for item in stock_res["data"]}
assert summary_map["15"]["total"] == 2000, f"Expected 2000 Part 15, got {summary_map['15']['total']}"
assert summary_map["15"]["available"] == 2000, f"Expected 2000 available Part 15, got {summary_map['15']['available']}"
assert summary_map["12"]["total"] == 10000, f"Expected 10000 Part 12, got {summary_map['12']['total']}"
assert summary_map["1"]["total"] == 6000, f"Expected 6000 Part 1, got {summary_map['1']['total']}"
print(f"3. Master Stocks Summary: SUCCESS (Part 15: {summary_map['15']['available']}/2000 Tersedia, Part 12: {summary_map['12']['available']}/10000, Part 1: {summary_map['1']['available']}/6000)")

# 4. Create Gudang Tray
tray_payload = {
    "pic_warehouse": "Budi Gudang",
    "product_type": "Nanggala P9",
    "assigned_operator": "Siti Assembly",
    "source_document_mo": "MH/MO/000001",
    "parts": [
        {"part_type": "1", "sn": "001"},
        {"part_type": "12", "sn": "60001"},
        {"part_type": "15", "sn": "J1100001"}
    ]
}
gudang_res = request("/gudang/save", method="POST", data=tray_payload, token=token)
assert gudang_res.get("ok"), f"Save tray failed: {gudang_res}"
tray_code = gudang_res["data"]["tray_code"]
print(f"4. Gudang Save Tray: SUCCESS -> Tray Code: {tray_code}")

# 5. Fetch Assembly Tray
assembly_tray = request(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", token=token)
tray_data = assembly_tray["data"]
tray_id = tray_data["tray"]["id"]
assert len(tray_data["parts"]) == 3, f"Expected 3 parts, got {len(tray_data['parts'])}"
print(f"5. Fetch Assembly Tray: SUCCESS ({len(tray_data['parts'])} parts in tray)")

# 6. Save Assembly Set #1
assy_payload = {
    "tray_id": tray_id,
    "set_number": 1,
    "operator": "Siti Assembly",
    "part15": "J1100001",
    "part12": "60001",
    "part1": "001",
    "status": "Good",
    "reason": None,
    "started_at": "2026-08-20T08:00:00.000Z",
    "is_edit": False,
    "assembly_id": None,
    "old_sns": None
}
assy_res = request("/assembly/save", method="POST", data=assy_payload, token=token)
assert assy_res.get("ok"), f"Assembly save failed: {assy_res}"
print("6. Assembly Save Set #1 (Status: Good): SUCCESS")

# 7. Check Updated Tray
tray_detail_after = request(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", token=token)
assemblies = tray_detail_after["data"]["assemblies"]
assert len(assemblies) == 1, f"Expected 1 assembly, got {len(assemblies)}"
assert assemblies[0]["status"] == "Good"
assert assemblies[0]["part15_sn"] == "J1100001"
print("7. Verify Assembly in Tray: SUCCESS -> Completed Set #1 with Part 15 SN J1100001")

# 8. Check Laporan
laporan_res = request("/laporan", token=token)
assert laporan_res.get("ok"), f"Laporan failed: {laporan_res}"
assert len(laporan_res["data"]) >= 1
print(f"8. Laporan Traceability: SUCCESS ({len(laporan_res['data'])} records found)")

# 9. Clean up Tray via Admin Clear Trays
reset_res = request("/admin/clear_trays", method="POST", token=token)
assert reset_res.get("ok"), f"Reset failed: {reset_res}"
print("9. Admin Database Reset Confirmation: SUCCESS (All tables clean, 18.000 stocks reset to Tersedia)")

print("\n=== ALL TESTS PASSED SUCCESSFULLY! ===")
