import urllib.request
import urllib.parse
import json

BASE_URL = "http://127.0.0.1:8000/api"

def req(path, method="GET", data=None, token=None):
    url = BASE_URL + path
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data).encode("utf-8") if data else None
    r = urllib.request.Request(url, data=body, headers=headers, method=method)
    with urllib.request.urlopen(r) as res:
        return json.loads(res.read().decode("utf-8"))

# 1. Login Admin
auth = req("/login", method="POST", data={"username": "admin", "password": "admin123"})
token = auth["token"]
print("1. Login OK")

# 2. Reset Trays
req("/admin/clear_trays", method="POST", token=token)

# 3. Create Tray
tray = req("/gudang/input", method="POST", token=token, data={
    "pic_warehouse": "Warehouse 1",
    "assigned_operator": "Kholifaturrohmah",
    "source_document_mo": "MH/MO/999888",
    "product_type": "Nanggala P9",
    "parts": [
        {"part_type": "1", "sn": "001"},
        {"part_type": "12", "sn": "60001"},
        {"part_type": "15", "sn": "J1100001"}
    ]
})
tray_code = tray["data"]["tray_code"]
print(f"2. Created Tray {tray_code}")

# 4. Fetch Tray
tray_info = req(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", token=token)
tray_id = tray_info["data"]["tray"]["id"]

# 5. Assemble Set #1 as HOLD with reason
assy = req("/assembly/save", method="POST", token=token, data={
    "tray_id": tray_id,
    "set_number": 1,
    "operator": "Operator Assembly 1",
    "part15": "J1100001",
    "part1": "001",
    "part12": "60001",
    "status": "Hold",
    "reason": "Kabel solder renggang pada modul P9",
    "started_at": "2026-08-20T09:00:00Z"
})
assert assy.get("ok"), f"Assembly save failed: {assy}"
print("3. Assembled Set #1 as HOLD with reason")

# 6. Check Master Stock Summary
summary = req("/master/stocks_summary", token=token)
sum_map = {item["part_type"]: item for item in summary["data"]}
print(f"4. Summary: Part 15 Hold Count = {sum_map['15']['hold_count']}, Part 1 Hold Count = {sum_map['1']['hold_count']}")
assert sum_map['15']['hold_count'] == 1
assert sum_map['1']['hold_count'] == 1
assert sum_map['12']['hold_count'] == 1

# 7. Check Stocks List filtered by status=Hold
hold_list = req("/master/stocks_list?status=Hold", token=token)
assert hold_list.get("ok")
assert len(hold_list["data"]) == 3, f"Expected 3 parts in hold list, got {len(hold_list['data'])}"
for row in hold_list["data"]:
    print(f"   -> Part {row['part_type']} SN: {row['sn']} | Status: {row['effective_status']} | Reason: {row['assembly_reason']} | Tray: {row['tray_code']}")
    assert row["effective_status"] == "Hold"
    assert row["assembly_reason"] == "Kabel solder renggang pada modul P9"
    assert row["tray_code"] == tray_code

# 8. Check Stocks List filtered by status=Supplied
# (since all 3 parts in tray were assembled into Hold, supplied count for this tray is 0)
supp_list = req("/master/stocks_list?status=Supplied", token=token)
print(f"5. Supplied items count: {supp_list['total']}")

# 9. Clean database back to fresh state
req("/admin/clear_trays", method="POST", token=token)
print("6. Database cleaned & reset back to fresh state.")
print("\n=== DRILLDOWN STATUS & REASON FEATURE VERIFIED 100% SUCCESSFULLY! ===")
