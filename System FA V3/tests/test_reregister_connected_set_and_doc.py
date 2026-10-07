import urllib.request
import urllib.parse
import json
import time

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

def test_full_connected_set_and_doc_flow():
    print("Testing Connected Set Re-register, Hold Test Doc Generation, and Rework Flow...", flush=True)

    # 1. Login Admin
    status, login_res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not login_res.get("ok"):
        status, login_res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert login_res.get("ok"), f"Admin login failed: {login_res}"
    admin_token = login_res["token"]

    # 2. Login Operator
    op_token = admin_token

    # 3. Fetch available parts
    status, avail_15_res = make_req("/api/master/available?type=15", "GET", token=admin_token)
    avail_15 = avail_15_res.get("data", [])
    status, avail_1_res = make_req("/api/master/available?type=1", "GET", token=admin_token)
    avail_1 = avail_1_res.get("data", [])
    status, avail_12_res = make_req("/api/master/available?type=12", "GET", token=admin_token)
    avail_12 = avail_12_res.get("data", [])

    ts = int(time.time() * 1000) % 1000000
    p15_sn = avail_15[0]["sn"] if avail_15 else f"J1100{ts:03d}"
    p1_sn = avail_1[0]["sn"] if avail_1 else f"P1_{ts}"
    p12_sn = avail_12[0]["sn"] if avail_12 else f"60{ts:03d}"

    # 4. Save Tray in Warehouse
    tray_payload = {
        "pic_warehouse": "gudang",
        "assigned_operator": "assembly",
        "product_type": "Nanggala P9",
        "source_document_mo": f"WH/MO/{ts:06d}",
        "parts": [
            {"part_type": "15", "sn": p15_sn},
            {"part_type": "1", "sn": p1_sn},
            {"part_type": "12", "sn": p12_sn}
        ]
    }

    status, t_res = make_req("/api/gudang/save", "POST", tray_payload, token=admin_token)
    assert status == 200 and t_res.get("ok"), f"Warehouse save tray failed: {t_res}"
    tray_info = t_res["data"]
    tray_id = tray_info["id"]
    tray_code = tray_info["tray_code"]
    print(f"[OK] Created Tray: {tray_code} (ID: {tray_id})", flush=True)

    # 5. Operator saves assembly as Good
    status, save_res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "assembly",
        "part15": p15_sn,
        "part1": p1_sn,
        "part12": p12_sn,
        "status": "Good"
    }, token=op_token)
    assert status == 200 and save_res.get("ok"), f"Assembly save failed: {save_res}"
    print(f"[OK] Assembled Set #1 ({p15_sn}) as Good.", flush=True)

    # 6. Testing Operator Assembly: Submit test with FAILURE (Not Good / Hold)
    test_payload = {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15_sn,
        "part1_sn": p1_sn,
        "part12_sn": p12_sn,
        "test_checklist": [
            {"test_num": 1, "status": "Good"},
            {"test_num": 2, "status": "Good"},
            {"test_num": 3, "status": "Not Good"}, # FAILED PARAMETER
            {"test_num": 4, "status": "Good"},
            {"test_num": 5, "status": "Good"}
        ],
        "notes": "Testing failure on parameter 3"
    }

    status, submit_res = make_req("/api/assembly/submit-test", "POST", test_payload, token=op_token)
    assert status == 200 and submit_res.get("ok"), f"Submit test failed: {submit_res}"
    assert submit_res["status"] == "Hold", f"Expected Hold, got {submit_res['status']}"
    doc_number = submit_res.get("doc_number")
    assert doc_number and doc_number.startswith("Assy/QC/"), f"Expected Assy/QC/... doc number for Hold item, got: {doc_number}"
    print(f"[OK] Submit Failed Test correctly generated doc number: {doc_number}", flush=True)

    # 7. Verify master stock status for all 3 parts updated to 'Hold'
    status, stocks_res = make_req(f"/api/master/stocks_list?q={urllib.parse.quote(p15_sn)}", "GET", token=admin_token)
    st_data = stocks_res.get("data", [])
    match_15 = next((x for x in st_data if x["sn"] == p15_sn), None)
    assert match_15 and match_15["stock_status"] == "Hold", f"Expected master stock Hold, got: {match_15}"
    print(f"[OK] Master Stock status for {p15_sn} is correctly 'Hold'.", flush=True)

    # 8. Check Connected Set Endpoint
    status, conn_res = make_req(f"/api/master/get-connected-set?sn={urllib.parse.quote(p15_sn)}&part_type=15", "GET", token=admin_token)
    assert status == 200 and conn_res.get("ok"), f"get-connected-set failed: {conn_res}"
    assert conn_res.get("is_connected") == True, f"Expected is_connected=True, got {conn_res}"
    set_info = conn_res["set_info"]
    assert set_info["part15_sn"] == p15_sn
    assert set_info["part1_sn"] == p1_sn
    assert set_info["part12_sn"] == p12_sn
    print(f"[OK] get-connected-set accurately identified set: P15={p15_sn}, P1={p1_sn}, P12={p12_sn}", flush=True)

    # 9. Re-register via Master Stock: User selects ONLY 1 part (e.g. part 15)
    status, rereg_res = make_req("/api/master/re-register", "POST", {
        "part_type": "15",
        "sn": p15_sn,
        "target_status": "Tersedia",
        "reason": "Uji ulang part terhubung"
    }, token=admin_token)
    assert status == 200 and rereg_res.get("ok"), f"Re-register failed: {rereg_res}"
    assert rereg_res.get("is_set") == True, f"Expected is_set=True, got {rereg_res}"
    print(f"[OK] Re-register on 1 part automatically included full set: {rereg_res['message']}", flush=True)

    # 10. Verify ALL 3 connected parts in master stock are now 'Pending Verif Admin'
    for sn_check in [p15_sn, p1_sn, p12_sn]:
        status, st_res = make_req(f"/api/master/stocks_list?q={urllib.parse.quote(sn_check)}", "GET", token=admin_token)
        rows = st_res.get("data", [])
        row = next((r for r in rows if r["sn"] == sn_check), None)
        assert row and row["stock_status"] == "Pending Verif Admin", f"Expected Pending Verif Admin for {sn_check}, got: {row}"
    print(f"[OK] All 3 parts ({p15_sn}, {p1_sn}, {p12_sn}) are now 'Pending Verif Admin' in Master Stocks.", flush=True)

    # 11. Verify Admin Pending Verifications list has the requests
    status, pending_res = make_req("/api/admin/pending-verifications", "GET", token=admin_token)
    assert status == 200 and pending_res.get("ok")
    pending_reqs = pending_res.get("data", [])
    sn_pending_set = {r["sn"] for r in pending_reqs if r.get("status") == "PENDING"}
    assert p15_sn in sn_pending_set, f"{p15_sn} missing from pending requests"
    assert p1_sn in sn_pending_set, f"{p1_sn} missing from pending requests"
    assert p12_sn in sn_pending_set, f"{p12_sn} missing from pending requests"
    print(f"[OK] Admin pending verifications received all 3 parts.", flush=True)

    # 12. Admin Approves the requests
    for r in pending_reqs:
        if r["sn"] in [p15_sn, p1_sn, p12_sn] and r.get("status") == "PENDING":
            status, app_res = make_req("/api/admin/approve-re-register", "POST", {
                "request_id": r["id"],
                "action": "APPROVE",
                "target_status": "Tersedia",
                "notes": "Verified and restored to Tersedia"
            }, token=admin_token)
            assert status == 200 and app_res.get("ok")

    print(f"[OK] Admin approved all re-registered parts back to Tersedia.", flush=True)

    # 13. Check Reports / Printable doc contains the test record with doc_number and status Hold
    status, rep_res = make_req(f"/api/reports/assy-tests?doc_number={urllib.parse.quote(doc_number)}", "GET", token=admin_token)
    assert status == 200 and rep_res.get("ok")
    rep_items = rep_res.get("data", [])
    assert len(rep_items) >= 1, f"Failed test item missing from reports: {rep_items}"
    assert rep_items[0]["status"] == "Hold"
    assert rep_items[0]["doc_number"] == doc_number
    print(f"[OK] Report & Document correctly contains failed test record with doc number {doc_number}.", flush=True)

    print("\nALL CONNECTED SET, RE-REGISTER, AND DOCUMENT DATA FLOW TESTS PASSED COMPLETELY!\n", flush=True)

if __name__ == "__main__":
    test_full_connected_set_and_doc_flow()
