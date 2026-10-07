import time
import json
import sys
import psycopg2
from psycopg2.extras import RealDictCursor
import requests

BASE_URL = "http://127.0.0.1:8000/api"

import os

DB_URL = os.environ.get("DATABASE_URL", "postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db")

def get_conn():
    return psycopg2.connect(DB_URL, cursor_factory=RealDictCursor)

def make_req(path, method="GET", payload=None, token=None):
    url = BASE_URL + path
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
        headers["Cookie"] = f"session={token}"
    if method == "POST":
        r = requests.post(url, json=payload, headers=headers)
    elif method == "GET":
        r = requests.get(url, headers=headers)
    else:
        r = requests.delete(url, headers=headers)
    try:
        return r.status_code, r.json()
    except:
        return r.status_code, r.text

def run_tests():
    print("=== TESTING DUAL BADGES (HOLD/REWORK -> GOOD) & CETAK LABEL ===", flush=True)

    # 1. Login Admin
    st, res = make_req("/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if st != 200 or not res.get("ok"):
        st, res = make_req("/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert st == 200 and res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("[OK] Logged in as Admin", flush=True)

    # 2. Insert test tray & parts into DB
    ts = int(time.time())
    tray_code = f"WH/ASSY/TEST_{ts}"
    p15 = f"P15-TEST-{ts}"
    p12 = f"P12-TEST-{ts}"
    p1 = f"P1-TEST-{ts}"

    with get_conn() as conn:
        with conn.cursor() as cur:
            # Insert master stocks
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('15', %s, 'Good')", (p15,))
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('12', %s, 'Good')", (p12,))
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('1', %s, 'Good')", (p1,))
            
            # Insert tray
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-TEST-001', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (tray_code,))
            tray_id = cur.fetchone()['id']

            # Insert parts
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', %s, 'Good')", (tray_id, p15))
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '12', %s, 'Good')", (tray_id, p12))
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '1', %s, 'Good')", (tray_id, p1))
            conn.commit()

    print(f"[OK] Created Tray {tray_code} (ID: {tray_id}) with parts: P15={p15}, P12={p12}, P1={p1}", flush=True)

    # 3. Assemble Slot 1
    st, res = make_req("/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15,
        "part12_sn": p12,
        "part1_sn": p1,
        "operator": "Assy",
        "pic_assembly": "Assy",
        "status": "Good"
    }, token=admin_token)
    assert st == 200 and res.get("ok"), f"Assembly save failed: {res}"
    assy_id = res.get("data", {}).get("assembly_id") or res.get("assembly_id")
    print(f"[OK] Assembled Slot 1 successfully (Assy ID: {assy_id})", flush=True)

    # 4. Submit Assembly Test with Failures (Hold Test)
    fail_checklist = [{"test_num": i, "name": f"Test Step {i}", "status": "FAIL" if i in [1, 2, 3] else "GOOD"} for i in range(1, 16)]
    st, res = make_req("/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy_id,
        "part15_sn": p15,
        "part12_sn": p12,
        "part1_sn": p1,
        "test_checklist": fail_checklist,
        "notes": "Gagal step 1, 2, 3"
    }, token=admin_token)
    assert st == 200 and res.get("ok"), f"Submit fail test failed: {res}"
    print(f"[OK] Slot 1 submitted as Hold Test (assy_doc_number generated)", flush=True)

    # 5. Fetch tray details and verify Hold Test state
    st, res = make_req(f"/assembly/tray?code={tray_code}", "GET", token=admin_token)
    assert st == 200 and res.get("ok"), f"Fetch tray failed: {res}"
    assemblies = res["data"]["assemblies"]
    slot1 = next((a for a in assemblies if a["set_number"] == 1), None)
    assert slot1 is not None, "Slot 1 not found in assemblies"
    assert slot1["status"] == "Hold", f"Expected Hold status, got {slot1['status']}"
    assert "gagal dalam test" in (slot1.get("assy_failure_reasons") or ""), f"Missing failure reasons in slot: {slot1}"
    print("[OK] Verified slot is in Hold status with assy_failure_reasons", flush=True)

    # 6. Admin Approves the Hold item to Good (APPROVE_GOOD)
    st, res = make_req("/admin/approve-hold-item", "POST", {
        "assembly_id": slot1["id"],
        "decision": "APPROVE_GOOD",
        "notes": "Disetujui Admin Lolos Good"
    }, token=admin_token)
    assert st == 200 and res.get("ok"), f"Admin approve failed: {res}"
    print(f"[OK] Admin approved Hold item to Good: {res.get('message')}", flush=True)

    # 7. Fetch tray details again and verify slot status is now Good with lifecycle_path = HOLD_APPROVED
    st, res = make_req(f"/assembly/tray?code={tray_code}", "GET", token=admin_token)
    assert st == 200 and res.get("ok"), f"Fetch tray failed: {res}"
    assemblies = res["data"]["assemblies"]
    slot1_after = next((a for a in assemblies if a["set_number"] == 1), None)
    assert slot1_after is not None
    assert slot1_after["status"] == "Good", f"Expected status 'Good', got {slot1_after['status']}"
    assert slot1_after["verified_status"] == "APPROVED", f"Expected verified_status 'APPROVED', got {slot1_after['verified_status']}"
    assert slot1_after["lifecycle_path"] == "HOLD_APPROVED", f"Expected lifecycle_path 'HOLD_APPROVED', got {slot1_after['lifecycle_path']}"
    print(f"[OK] Slot 1 updated: status={slot1_after['status']}, verified_status={slot1_after['verified_status']}, lifecycle_path={slot1_after['lifecycle_path']}", flush=True)

    # 8. Evaluate Vue template logic for Badges and Cetak Label
    def evaluate_card_badges(slot):
        badges = []
        if slot.get('status') == 'Good' and (slot.get('lifecycle_path') in ['HOLD_REWORK_APPROVED', 'HOLD_QC_REWORK_APPROVED'] or slot.get('verified_status') in ['REWORK_GUDANG', 'REWORK_QC_GUDANG']):
            badges = ['REWORK', 'GOOD']
        elif slot.get('status') == 'Good' and (slot.get('lifecycle_path') in ['HOLD_APPROVED', 'HOLD_QC_APPROVED'] or slot.get('verified_status') in ['APPROVED', 'APPROVED_QC'] or slot.get('assy_failure_reasons') or slot.get('reason')):
            badges = ['HOLD', 'GOOD']
        elif slot.get('status') == 'Rework' or slot.get('verified_status') in ['REWORK_GUDANG', 'REWORK_QC_GUDANG']:
            badges = ['REWORK_TEST']
        elif slot.get('status') == 'Hold' or slot.get('qc_status') == 'HOLD_ASSY' or ((slot.get('reason') or slot.get('assy_failure_reasons')) and slot.get('status') != 'Good'):
            badges = ['HOLD_TEST']
        elif (slot.get('assy_doc_number') or slot.get('assy_tested_at') or slot.get('tested_at')) and slot.get('status') == 'Good':
            badges = ['GOOD']
        return badges

    def evaluate_can_print_label(slot):
        return bool(slot.get('status') == 'Good' or slot.get('verified_status') in ['APPROVED', 'APPROVED_QC'] or (slot.get('assy_doc_number') and slot.get('status') not in ['Hold', 'Rework'] and slot.get('qc_status') != 'HOLD_ASSY'))

    badges_slot1 = evaluate_card_badges(slot1_after)
    assert badges_slot1 == ['HOLD', 'GOOD'], f"Expected ['HOLD', 'GOOD'] badges, got {badges_slot1}"
    assert evaluate_can_print_label(slot1_after) is True, "Cetak Label button should be enabled for approved item!"
    print(f"[OK] Hold -> Good evaluates correctly to dual badges: {badges_slot1} and can_print_label=True", flush=True)

    # 9. Test Rework -> Good scenario
    rework_slot = {
        "status": "Good",
        "verified_status": "APPROVED",
        "lifecycle_path": "HOLD_REWORK_APPROVED",
        "assy_doc_number": "Assy/QC/000001",
        "assy_failure_reasons": "gagal dalam test 1"
    }
    badges_rework = evaluate_card_badges(rework_slot)
    assert badges_rework == ['REWORK', 'GOOD'], f"Expected ['REWORK', 'GOOD'] badges, got {badges_rework}"
    assert evaluate_can_print_label(rework_slot) is True, "Cetak Label button should be enabled for rework approved item!"
    print(f"[OK] Rework -> Good evaluates correctly to dual badges: {badges_rework} and can_print_label=True", flush=True)

    # 10. Test Unresolved Hold scenario
    unresolved_hold = {
        "status": "Hold",
        "qc_status": "HOLD_ASSY",
        "lifecycle_path": "HOLD",
        "assy_doc_number": "Assy/QC/000001",
        "assy_failure_reasons": "gagal dalam test 1"
    }
    badges_unresolved = evaluate_card_badges(unresolved_hold)
    assert badges_unresolved == ['HOLD_TEST'], f"Expected ['HOLD_TEST'], got {badges_unresolved}"
    assert evaluate_can_print_label(unresolved_hold) is False, "Cetak Label button should be disabled for unresolved hold!"
    print(f"[OK] Unresolved Hold evaluates correctly to: {badges_unresolved} and can_print_label=False", flush=True)

    print("\nALL DUAL BADGE AND CETAK LABEL TESTS PASSED 100% CLEANLY!\n", flush=True)

if __name__ == "__main__":
    try:
        run_tests()
    except Exception as e:
        import traceback
        traceback.print_exc()
        sys.exit(1)
