import urllib.request
import json
import time

BASE_URL = "http://127.0.0.1:8000"

def post_json(endpoint, payload):
    url = f"{BASE_URL}{endpoint}"
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST"
    )
    start = time.time()
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            elapsed = int((time.time() - start) * 1000)
            return resp.status, elapsed, json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        elapsed = int((time.time() - start) * 1000)
        return e.code, elapsed, json.loads(e.read().decode("utf-8"))

print("--- TESTING WRITE & POST ACTIONS ---")

# 1. Admin CMS Login (Admin Auth Gate)
st, el, res = post_json("/api/admin/cms-login", {"username": "admin", "password": "admin"})
print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Admin CMS Login: {res.get('message')} ({el}ms)")

# 2. Testing Point Create, Update, Delete
st, el, res = post_json("/api/admin/testing-points", {
    "category": "Test Uji Antigravity",
    "instruction": "Lakukan pengetesan sinyal dummy",
    "output": "Nilai tegangan 5.0V normal"
})
created_id = res.get("data", {}).get("id") if res.get("ok") else None
print(f"[{'PASS' if st in [200, 201] and res.get('ok') else 'FAIL'}] Create Testing Point ID {created_id} ({el}ms)")

if created_id:
    # Update
    st, el, res = post_json("/api/admin/testing-points/update", {
        "id": created_id,
        "category": "Test Uji Antigravity Updated",
        "instruction": "Lakukan pengetesan sinyal dummy v2",
        "output": "Nilai tegangan 5.2V normal"
    })
    print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Update Testing Point ID {created_id} ({el}ms)")

    # Delete
    st, el, res = post_json("/api/admin/testing-points/delete", {"id": created_id})
    print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Delete Testing Point ID {created_id} ({el}ms)")

# 3. Master Stock: Add Dummy Part and Delete
dummy_sn = f"TEST_SN_{int(time.time())}"
st, el, res = post_json("/api/master/add-part", {
    "part_type": "15",
    "sn": dummy_sn,
    "cs": "CS-TEST",
    "reason": "Automated verification test"
})
print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Add Part Number ({dummy_sn}) ({el}ms)")

# Delete Dummy Part
st, el, res = post_json("/api/master/delete-part", {
    "part_type": "15",
    "sn": dummy_sn
})
print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Delete Part Number ({dummy_sn}) ({el}ms)")

# 4. Direct Part Verify (Admin)
st, el, res = post_json("/api/admin/approve-part-direct", {
    "part_type": "15",
    "sn": "NON_EXISTENT_VERIFY_TEST",
    "target_status": "Tersedia",
    "notes": "Verify test endpoint execution"
})
print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Admin Direct Part Verify ({el}ms)")

# 5. Cloudinary Connectivity Test
st, el, res = post_json("/api/cloudinary/test", {})
print(f"[{'PASS' if st == 200 and res.get('ok') else 'FAIL'}] Cloudinary Test: {res.get('message')} ({el}ms)")

print("--- POST VERIFICATION COMPLETED ---")
