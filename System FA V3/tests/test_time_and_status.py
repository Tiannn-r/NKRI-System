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

print("=== VERIFYING WIB TIMESTAMP & STATUS FLOW ===")
# 1. Login
login = request("/login", method="POST", data={"username": "admin", "password": "admin123"})
token = login["token"]

# 2. Clear Trays
request("/admin/clear_trays", method="POST", token=token)

# 3. Create Tray with 1 set
save_tray = request("/gudang/save", method="POST", token=token, data={
    "assigned_operator": "Muthia",
    "pic_warehouse": "admin",
    "product_type": "Nanggala P9",
    "source_document_mo": "MO-TEST-001",
    "parts": [
        {"part_type": "1", "sn": "001"},
        {"part_type": "12", "sn": "60001"},
        {"part_type": "15", "sn": "J1100001"}
    ]
})
tray_code = save_tray["data"]["tray_code"]
tray_id = save_tray["data"]["id"]
print(f"Created tray: {tray_code}")

# 4. Save Assembly with ISO UTC string (e.g. 03:49:15Z) to verify it gets converted to WIB (10:49:15)
assy_save = request("/assembly/save", method="POST", token=token, data={
    "tray_id": tray_id,
    "set_number": 1,
    "operator": "Muthia",
    "part15": "J1100001",
    "part12": "60001",
    "part1": "001",
    "status": "Good",
    "started_at": "2026-08-21T03:49:15Z"
})
assert assy_save.get("ok"), f"Assembly save failed: {assy_save}"

# 5. Fetch Tray Assembly and check started_at
tray_res = request(f"/assembly/tray?code={urllib.parse.quote(tray_code)}", method="GET", token=token)
assemblies = tray_res["data"]["assemblies"]
assert len(assemblies) == 1
start_time_str = str(assemblies[0]["started_at"])
print(f"Started at in DB: {start_time_str}")
assert "10:49:15" in start_time_str, f"Expected 10:49:15 WIB in started_at, got {start_time_str}"
print("WIB timezone conversion verified successfully!")

print("=== ALL TIME & STATUS TESTS PASSED! ===")
