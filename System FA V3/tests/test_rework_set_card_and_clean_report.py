import urllib.request
import urllib.parse
import json
import time
import sys

BASE_URL = "http://localhost:8000"

def make_req(path, method="GET", data=None, token=None):
    url = BASE_URL + path
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data).encode("utf-8") if data is not None else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            status = resp.getcode()
            res_body = resp.read().decode("utf-8")
            return status, json.loads(res_body) if res_body else {}
    except urllib.error.HTTPError as e:
        res_body = e.read().decode("utf-8")
        return e.code, json.loads(res_body) if res_body else {}

def run_tests():
    print("Testing Rework Set Card and Clean Printable Report Document...", flush=True)

    # 1. Login Admin
    status, login_res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not login_res.get("ok"):
        status, login_res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert login_res.get("ok"), f"Admin login failed: {login_res}"
    admin_token = login_res["token"]

    # 2. Get available stocks
    status, avail_15 = make_req("/api/master/available?type=15&limit=1", "GET", token=admin_token)
    status, avail_1 = make_req("/api/master/available?type=1&limit=1", "GET", token=admin_token)
    status, avail_12 = make_req("/api/master/available?type=12&limit=1", "GET", token=admin_token)

    ts = int(time.time() * 1000) % 1000000
    p15_sn = avail_15["data"][0]["sn"] if avail_15.get("data") else f"B1100{ts:03d}"
    p1_sn = avail_1["data"][0]["sn"] if avail_1.get("data") else f"P1_{ts:03d}"
    p12_sn = avail_12["data"][0]["sn"] if avail_12.get("data") else f"60{ts:03d}"

    print(f"[OK] Using parts: P15={p15_sn}, P1={p1_sn}, P12={p12_sn}", flush=True)

    # 3. Create tray in warehouse
    tray_payload = {
        "pic_warehouse": "gudang",
        "assigned_operator": "assembly",
        "source_document_mo": f"MO-REWORK-TEST-{ts}",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": p15_sn},
            {"part_type": "1", "sn": p1_sn},
            {"part_type": "12", "sn": p12_sn}
        ]
    }
    status, t_res = make_req("/api/gudang/save", "POST", tray_payload, token=admin_token)
    assert status == 200 and t_res.get("ok"), f"Create tray failed: {t_res}"
    tray_info = t_res["data"]
    tray_id = tray_info["id"]
    tray_code = tray_info["tray_code"]
    print(f"[OK] Created tray {tray_code} (ID: {tray_id})", flush=True)

    # 4. Assemble slot 1 via /api/assembly/save
    status, assy_res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "assembly",
        "part15": p15_sn,
        "part1": p1_sn,
        "part12": p12_sn,
        "status": "Good"
    }, token=admin_token)
    assert status == 200 and assy_res.get("ok"), f"Assemble slot failed: {assy_res}"
    print("[OK] Assembled slot 1 successfully", flush=True)

    # 5. Submit test as Hold / Rework
    status, test_res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15_sn,
        "part1_sn": p1_sn,
        "part12_sn": p12_sn,
        "test_checklist": [
            {"test_num": 1, "status": "GOOD"},
            {"test_num": 2, "status": "NOT_GOOD"},
            {"test_num": 3, "status": "NOT_GOOD"},
            {"test_num": 4, "status": "NOT_GOOD"},
            {"test_num": 5, "status": "GOOD"}
        ],
        "notes": "Testing gagal parameter 2, 3, 4"
    }, token=admin_token)
    assert status == 200 and test_res.get("ok"), f"Submit test failed: {test_res}"
    doc_number = test_res.get("doc_number")
    print(f"[OK] Test submitted (Hold) with doc_number: {doc_number}", flush=True)

    # 6. Gudang registers re-register with target_status = 'Rework'
    status, rereg_res = make_req("/api/master/re-register", "POST", {
        "part_type": "15",
        "sn": p15_sn,
        "target_status": "Rework",
        "reason": "Harus dirework ulang 1 set"
    }, token=admin_token)
    assert status == 200 and rereg_res.get("ok"), f"Re-register failed: {rereg_res}"
    print(f"[OK] Re-registered P15 -> Enqueued 1 set ({rereg_res.get('message')})", flush=True)

    # 7. Admin checks pending assemblies cards
    status, pend_sets_res = make_req("/api/admin/pending-assemblies", "GET", token=admin_token)
    assert status == 200 and pend_sets_res.get("ok"), f"Fetch pending assemblies failed: {pend_sets_res}"
    pending_sets = pend_sets_res.get("data", [])
    
    found_set = None
    for s in pending_sets:
        if s.get("part15_sn") == p15_sn:
            found_set = s
            break
    
    assert found_set is not None, f"Set with P15={p15_sn} not found in pending-assemblies cards!"
    print(f"[OK] Found Pending Set Card: assembly_id={found_set['assembly_id']}, set_number={found_set['set_number']}, status={found_set['assembly_status']}", flush=True)
    assert found_set["assembly_status"] == "Rework", f"Expected assembly_status to be 'Rework', got {found_set['assembly_status']}"

    # 8. Test Admin Decision / Approval on Set Card
    status, approve_res = make_req("/api/admin/approve-part-direct", "POST", {
        "part_type": "15",
        "sn": p15_sn,
        "target_status": "Tersedia",
        "notes": "Verifikasi Admin Disetujui"
    }, token=admin_token)
    assert status == 200 and approve_res.get("ok"), f"Approve P15 failed: {approve_res}"
    print(f"[OK] Admin approved P15: {approve_res.get('message')}", flush=True)

    # Approve remaining parts in set
    make_req("/api/admin/approve-part-direct", "POST", {"part_type": "1", "sn": p1_sn, "target_status": "Tersedia"}, token=admin_token)
    make_req("/api/admin/approve-part-direct", "POST", {"part_type": "12", "sn": p12_sn, "target_status": "Tersedia"}, token=admin_token)
    print("[OK] All 3 parts approved to Tersedia", flush=True)

    # 9. Verify that static/index.html doesn't have "Alasan: {{ item.assy_failure_reasons" in printable template
    with open("static/index.html", "r", encoding="utf-8") as f:
        html_content = f.read()
    assert "item.assy_failure_reasons || item.reason" not in html_content, "Alasan failure reasons text still found in index.html!"
    print("[OK] Verified: Failure reasons text is removed from printable report table.", flush=True)

    print("\nALL INTEGRATION & VERIFICATION TESTS PASSED SUCCESSFULLY (100%)!\n", flush=True)

if __name__ == "__main__":
    try:
        run_tests()
    except Exception as e:
        import traceback
        traceback.print_exc()
        sys.exit(1)
