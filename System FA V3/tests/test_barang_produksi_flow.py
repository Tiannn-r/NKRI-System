import os
import sys
import urllib.request
import urllib.parse
import json
import time
import psycopg2

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import DB_URL

BASE_URL = "http://localhost:8000"

def make_req(path, method="GET", data=None, token=None):
    url = BASE_URL + path
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data).encode("utf-8") if data is not None else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req) as resp:
            return resp.getcode(), json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode("utf-8"))

def run_test():
    print("================================================================", flush=True)
    print("RUNNING BARANG PRODUKSI & QC MANDATORY HOLD TEST", flush=True)
    print("================================================================", flush=True)

    # 1. Login Admin
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("[1] Admin login successful.", flush=True)

    # 2. Login QC
    st, res = make_req("/api/login", "POST", {"username": "qc", "password": "123", "force_takeover": True})
    qc_token = res.get("token") or admin_token
    print("[2] QC login token acquired.", flush=True)

    # 3. Create test parts in DB
    timestamp = int(time.time()) % 100000
    p15_assy_hold = f"B110{timestamp:04d}"
    p12_assy_hold = f"6{timestamp:04d}"
    p1_assy_hold = f"{timestamp % 900 + 100:03d}"

    p15_qc_hold = f"B110{(timestamp + 1):04d}"
    p12_qc_hold = f"6{(timestamp + 1):04d}"
    p1_qc_hold = f"{(timestamp + 1) % 900 + 100:03d}"

    conn = psycopg2.connect(DB_URL)
    with conn.cursor() as cur:
        for sn, pt in [(p15_assy_hold, '15'), (p12_assy_hold, '12'), (p1_assy_hold, '1'),
                       (p15_qc_hold, '15'), (p12_qc_hold, '12'), (p1_qc_hold, '1')]:
            cur.execute("DELETE FROM master_stocks WHERE sn = %s AND part_type = %s", (sn, pt))
            cur.execute("INSERT INTO master_stocks (sn, part_type, status) VALUES (%s, %s, 'Tersedia')", (sn, pt))
    conn.commit()
    conn.close()

    # Create Supply Tray via /api/gudang/save
    st, res = make_req("/api/gudang/save", "POST", {
        "pic_warehouse": "gudang",
        "assigned_operator": "assy",
        "source_document_mo": f"WH/MO/{timestamp}",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": p15_assy_hold},
            {"part_type": "12", "sn": p12_assy_hold},
            {"part_type": "1", "sn": p1_assy_hold},
            {"part_type": "15", "sn": p15_qc_hold},
            {"part_type": "12", "sn": p12_qc_hold},
            {"part_type": "1", "sn": p1_qc_hold}
        ]
    }, token=admin_token)
    assert res.get("ok"), f"Create tray failed: {res}"
    tray_data = res.get("data", {})
    tray_id = tray_data.get("tray_id") or tray_data.get("id")
    tray_code = tray_data.get("tray_code")
    print(f"[3] Created Supply Tray: {tray_code} (ID: {tray_id}) with 2 sets.", flush=True)

    # Save Set #1 Perakitan as Good
    st, res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15_assy_hold,
        "part12_sn": p12_assy_hold,
        "part1_sn": p1_assy_hold,
        "status": "Good",
        "operator": "assy"
    }, token=admin_token)
    assert res.get("ok"), f"Assembly save Set 1 failed: {res}"

    # Save Set #2 Perakitan as Good
    st, res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 2,
        "part15_sn": p15_qc_hold,
        "part12_sn": p12_qc_hold,
        "part1_sn": p1_qc_hold,
        "status": "Good",
        "operator": "assy"
    }, token=admin_token)
    assert res.get("ok"), f"Assembly save Set 2 failed: {res}"

    # Fetch tray to get assembly IDs
    st, res = make_req(f"/api/assembly/tray?tray_code={tray_code}", "GET", token=admin_token)
    assys = res.get("data", {}).get("assemblies", [])
    assy1_id = next(a["id"] for a in assys if a["set_number"] == 1)
    assy2_id = next(a["id"] for a in assys if a["set_number"] == 2)
    print(f"[3b] Assemblies: Set 1 (ID: {assy1_id}), Set 2 (ID: {assy2_id})", flush=True)

    # 4. Set 1: Tester Assembly failure (fails stage 4)
    checklist_fail = [
        {"test_num": i, "name": f"Test Step {i}", "status": "NOT_GOOD" if i == 4 else "GOOD"}
        for i in range(1, 16)
    ]
    st, res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy1_id,
        "part15_sn": p15_assy_hold,
        "part12_sn": p12_assy_hold,
        "part1_sn": p1_assy_hold,
        "test_checklist": checklist_fail
    }, token=admin_token)
    assert res.get("ok"), f"Assy test submit failed: {res}"
    assert res.get("status") == "Hold", f"Expected status Hold, got {res.get('status')}"
    print(f"[4] Set 1 failed assembly testing -> status: {res.get('status')} ({res.get('failure_reasons')}).", flush=True)

    # 5. Set 2: Tester Assembly success (all 15 stages Good)
    checklist_good = [
        {"test_num": i, "name": f"Test Step {i}", "status": "GOOD"}
        for i in range(1, 16)
    ]
    st, res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 2,
        "assembly_id": assy2_id,
        "part15_sn": p15_qc_hold,
        "part12_sn": p12_qc_hold,
        "part1_sn": p1_qc_hold,
        "test_checklist": checklist_good
    }, token=admin_token)
    assert res.get("ok"), f"Assy test submit failed: {res}"
    assert res.get("status") == "Good", f"Expected status Good, got {res.get('status')}"
    print(f"[5] Set 2 passed assembly testing -> status Good, Doc: {res.get('doc_number')}.", flush=True)

    # 6. Set 2 at QC: Try to submit Hold WITHOUT reason -> MUST return 400
    st, res = make_req("/api/qc/submit-test", "POST", {
        "assembly_id": assy2_id,
        "status": "Hold",
        "notes": "",
        "failure_reasons": ""
    }, token=qc_token)
    print(f"[6] QC Hold without reason response (st={st}): {res}", flush=True)
    assert st == 400 or not res.get("ok"), "Submitting QC Hold without reason should be rejected!"
    print("[6 OK] Mandatory Hold reason enforcement verified (rejected empty reason).", flush=True)

    # 7. Set 2 at QC: Submit Hold WITH mandatory reason (simulating confirmation from new pop-up modal)
    qc_reason_text = "Terdapat goresan fisik pada permukaan part 15 dan kelonggaran pin"
    st, res = make_req("/api/qc/submit-test", "POST", {
        "assembly_id": assy2_id,
        "status": "Hold",
        "notes": qc_reason_text,
        "failure_reasons": qc_reason_text,
        "test_checklist": checklist_good
    }, token=qc_token)
    assert res.get("ok"), f"QC Hold submission failed: {res}"
    assert res.get("status") == "Hold", f"Expected status Hold, got {res.get('status')}"
    print(f"[7 OK] QC Hold with mandatory reason submitted successfully: {res.get('message')}", flush=True)

    # 8. Verify GET /api/production/hold-items
    st, res = make_req("/api/production/hold-items", "GET", token=admin_token)
    assert res.get("ok"), f"Failed to fetch production hold items: {res}"
    items = res.get("data", [])
    print(f"[8] Total production hold items found: {len(items)}", flush=True)

    # Find Set 1 (Tester Assembly Hold)
    item_assy = next((i for i in items if i["part15_sn"] == p15_assy_hold), None)
    assert item_assy is not None, f"Set 1 {p15_assy_hold} not found in production hold items!"
    assert item_assy["hold_source"] == "TESTER_ASSEMBLY", f"Expected hold_source TESTER_ASSEMBLY, got {item_assy['hold_source']}"
    assert "test 4" in item_assy["display_reason"].lower(), f"Expected failure in test 4, got {item_assy['display_reason']}"
    print(f"[8a OK] Set 1 verified in Barang Produksi: hold_source={item_assy['hold_source']}, reason='{item_assy['display_reason']}'", flush=True)

    # Find Set 2 (QC Hold)
    item_qc = next((i for i in items if i["part15_sn"] == p15_qc_hold), None)
    assert item_qc is not None, f"Set 2 {p15_qc_hold} not found in production hold items!"
    assert item_qc["hold_source"] == "QC", f"Expected hold_source QC, got {item_qc['hold_source']}"
    assert qc_reason_text in item_qc["display_reason"], f"Expected '{qc_reason_text}' in reason, got {item_qc['display_reason']}"
    print(f"[8b OK] Set 2 verified in Barang Produksi: hold_source={item_qc['hold_source']}, reason='{item_qc['display_reason']}'", flush=True)

    # 9. Verify Filtering by Product
    st, res_prod = make_req("/api/production/products", "GET", token=admin_token)
    assert res_prod.get("ok"), f"Failed to fetch products: {res_prod}"
    products = res_prod.get("data", [])
    assert "Nanggala P9" in products, f"Expected 'Nanggala P9' in products list: {products}"
    print(f"[9 OK] Products list verified: {products}", flush=True)

    st, res_filter_p9 = make_req("/api/production/hold-items?product_type=Nanggala%20P9", "GET", token=admin_token)
    assert res_filter_p9.get("ok"), f"Failed product filter: {res_filter_p9}"
    p9_items = res_filter_p9.get("data", [])
    assert any(i["part15_sn"] == p15_assy_hold for i in p9_items), "Set 1 should be returned under Nanggala P9 filter"
    assert any(i["part15_sn"] == p15_qc_hold for i in p9_items), "Set 2 should be returned under Nanggala P9 filter"
    print(f"[9b OK] Product filter 'Nanggala P9' returned {len(p9_items)} items.", flush=True)

    # 10. Verify Filtering by Source
    st, res_assy_src = make_req("/api/production/hold-items?source=ASSY", "GET", token=admin_token)
    assy_items = res_assy_src.get("data", [])
    assert any(i["part15_sn"] == p15_assy_hold for i in assy_items), "Set 1 should be in source=ASSY"
    assert not any(i["part15_sn"] == p15_qc_hold for i in assy_items), "Set 2 (QC) should NOT be in source=ASSY"
    print(f"[10a OK] Source filter ASSY verified: Set 1 present, Set 2 absent.", flush=True)

    st, res_qc_src = make_req("/api/production/hold-items?source=QC", "GET", token=admin_token)
    qc_items = res_qc_src.get("data", [])
    assert any(i["part15_sn"] == p15_qc_hold for i in qc_items), "Set 2 should be in source=QC"
    assert not any(i["part15_sn"] == p15_assy_hold for i in qc_items), "Set 1 (ASSY) should NOT be in source=QC"
    print(f"[10b OK] Source filter QC verified: Set 2 present, Set 1 absent.", flush=True)

    # 11. Verify Search Query 'q'
    st, res_search = make_req(f"/api/production/hold-items?q={p15_qc_hold}", "GET", token=admin_token)
    search_items = res_search.get("data", [])
    assert len(search_items) == 1 and search_items[0]["part15_sn"] == p15_qc_hold, f"Search failed: {search_items}"
    print(f"[11 OK] Search by SN {p15_qc_hold} returned exact match.", flush=True)

    print("================================================================", flush=True)
    print("ALL TESTS PASSED SUCCESSFULLY! 100% VERIFIED.", flush=True)
    print("================================================================", flush=True)

if __name__ == "__main__":
    run_test()
