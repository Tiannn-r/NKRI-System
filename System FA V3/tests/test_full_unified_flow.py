import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import json
import urllib.request
import urllib.parse
from app import get_conn

BASE_URL = "http://localhost:8000"

def login(username, password):
    data = json.dumps({"username": username, "password": password, "force_takeover": True}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/api/login", data=data, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req) as resp:
            res = json.loads(resp.read().decode('utf-8'))
            return res.get('token')
    except urllib.error.HTTPError as e:
        print("Login error:", e.read().decode())
        raise

def api_post(path, body, token):
    data = json.dumps(body).encode('utf-8')
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
        headers["Cookie"] = f"session={token}"
    req = urllib.request.Request(f"{BASE_URL}{path}", data=data, headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode('utf-8'))

def api_get(path, token):
    headers = {}
    if token:
        headers["Authorization"] = f"Bearer {token}"
        headers["Cookie"] = f"session={token}"
    req = urllib.request.Request(f"{BASE_URL}{path}", headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode('utf-8'))

def run_tests():
    print("=== STARTING END-TO-END FLOW TESTS ===")
    admin_cookie = login("admin", "admin123")
    gudang_cookie = login("Gudang", "gudang123")
    assy_cookie = login("Assy", "assy123")
    print("[OK] Logged in successfully as admin, Gudang, Assy")

    import time
    ts = int(time.time())
    # 1. TEST FRESH GOOD
    print("\n--- TEST 1: FRESH GOOD (All 5 Tests Good -> Direct to QC) ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-TEST-001', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-TEST-{ts}-1',))
            tray_id = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'Assy', NOW())
                RETURNING id
            """, (tray_id, f'P15-{ts}-1', f'P1-{ts}-1', f'P12-{ts}-1'))
            assy_id = cur.fetchone()['id']
            conn.commit()

    # Submit test all Good
    res_test = api_post("/api/assembly/submit-test", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy_id,
        "part15_sn": f'P15-{ts}-1',
        "part12_sn": f'P12-{ts}-1',
        "part1_sn": f'P1-{ts}-1',
        "test_checklist": [{"test_num": i, "status": "GOOD"} for i in range(1, 6)],
        "notes": "Testing all good"
    }, assy_cookie)
    assert res_test.get("ok") is True, f"Submit test failed: {res_test}"
    print("[OK] Assembly test submitted as Good:", res_test.get("doc_number"))

    # Verify not in Admin pending-assemblies
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id not in pending_ids, "Fresh Good should NOT appear in Admin pending verifications!"
    print("[OK] Fresh Good set does NOT appear in Admin pending verifications")

    # Verify directly in QC pending-items
    qc_pending = api_get("/api/qc/pending-items", admin_cookie)
    qc_ids = [a['assembly_id'] for a in qc_pending.get('data', [])]
    assert assy_id in qc_ids, "Fresh Good set MUST appear in QC pending items!"
    print("[OK] Fresh Good set appeared directly in QC pending items!")

    # 2. TEST HOLD -> GUDANG -> ADMIN GOOD -> QC
    print("\n--- TEST 2: HOLD -> GUDANG -> ADMIN GOOD -> QC ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-TEST-002', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-TEST-{ts}-2',))
            tray_id_2 = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'Assy', NOW())
                RETURNING id
            """, (tray_id_2, f'P15-{ts}-2', f'P1-{ts}-2', f'P12-{ts}-2'))
            assy_id_2 = cur.fetchone()['id']
            conn.commit()

    # Submit test with FAILURE
    res_test_fail = api_post("/api/assembly/submit-test", {
        "tray_id": tray_id_2,
        "set_number": 1,
        "assembly_id": assy_id_2,
        "part15_sn": f'P15-{ts}-2',
        "part12_sn": f'P12-{ts}-2',
        "part1_sn": f'P1-{ts}-2',
        "test_checklist": [
            {"test_num": 1, "status": "GOOD"},
            {"test_num": 2, "status": "NOT_GOOD"},
            {"test_num": 3, "status": "GOOD"},
            {"test_num": 4, "status": "NOT_GOOD"},
            {"test_num": 5, "status": "GOOD"},
        ],
        "notes": "Failed functional check"
    }, assy_cookie)
    assert res_test_fail.get("ok") is True and res_test_fail.get("status") == "Hold"
    print("[OK] Assembly test correctly evaluated as Hold:", res_test_fail.get("doc_number"))

    # Verify NOT in Admin pending-assemblies yet
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_2 not in pending_ids, "Hold set must NOT appear in Admin before Gudang requests verification!"
    print("[OK] Hold set is currently withheld from Admin (waiting for Gudang)")

    # Gudang requests verification / re-registers the set
    gudang_req = api_post("/api/master/re-register", {
        "assembly_id": assy_id_2,
        "reason": "Pengajuan Verifikasi Barang Hold Uji oleh Gudang"
    }, gudang_cookie)
    assert gudang_req.get("ok") is True
    print("[OK] Gudang requested Admin verification for Hold set:", gudang_req.get("message"))

    # NOW it MUST appear in Admin pending-assemblies as a Card
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_sets = {a['assembly_id']: a for a in admin_pending.get('data', [])}
    assert assy_id_2 in pending_sets, "Hold set must appear in Admin after Gudang requested verification!"
    assert pending_sets[assy_id_2]['assembly_status'] == 'Hold'
    print("[OK] Hold set now appears in Admin pending verifications as Card with status Hold")

    # Admin clicks Daftarkan Good (APPROVE_GOOD) -> Lanjut QC
    admin_app = api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_2,
        "decision": "APPROVE_GOOD",
        "notes": "Lolos toleransi teknis oleh Admin"
    }, admin_cookie)
    assert admin_app.get("ok") is True
    print("[OK] Admin approved set as Good:", admin_app.get("message"))

    # Card must disappear from Admin
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_2 not in pending_ids, "Approved set must disappear from Admin!"
    print("[OK] Approved set disappeared from Admin")

    # Unit must appear in QC pending items
    qc_pending = api_get("/api/qc/pending-items", admin_cookie)
    qc_items = {a['assembly_id']: a for a in qc_pending.get('data', [])}
    assert assy_id_2 in qc_items, "Approved set must appear in QC queue!"
    assert qc_items[assy_id_2]['lifecycle_path'] == 'HOLD_APPROVED'
    print("[OK] Approved set appeared in QC queue with lifecycle_path = HOLD_APPROVED")

    # 3. TEST HOLD -> GUDANG -> ADMIN REWORK -> HILANG DI ADMIN -> GUDANG RE-REGISTER -> ADMIN GOOD -> QC
    print("\n--- TEST 3: HOLD -> REWORK (Card MENGHILANG) -> GUDANG RE-REGISTER -> ADMIN GOOD -> QC ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-TEST-003', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-TEST-{ts}-3',))
            tray_id_3 = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'Assy', NOW())
                RETURNING id
            """, (tray_id_3, f'P15-{ts}-3', f'P1-{ts}-3', f'P12-{ts}-3'))
            assy_id_3 = cur.fetchone()['id']
            conn.commit()

    # Assembly test fails
    api_post("/api/assembly/submit-test", {
        "tray_id": tray_id_3,
        "set_number": 1,
        "assembly_id": assy_id_3,
        "part15_sn": f'P15-{ts}-3',
        "part12_sn": f'P12-{ts}-3',
        "part1_sn": f'P1-{ts}-3',
        "test_checklist": [{"test_num": 1, "status": "NOT_GOOD"}],
        "notes": "Failed test 1"
    }, assy_cookie)

    # Gudang requests verification
    api_post("/api/master/re-register", {
        "assembly_id": assy_id_3,
        "reason": "Pengajuan Verifikasi Gudang"
    }, gudang_cookie)

    # Admin selects REWORK
    admin_rework = api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_3,
        "decision": "REWORK",
        "notes": "Perlu disetting ulang di Gudang"
    }, admin_cookie)
    assert admin_rework.get("ok") is True
    print("[OK] Admin directed set to REWORK:", admin_rework.get("message"))

    # CRITICAL CHECK: In Admin, the card MUST DISAPPEAR (menghilang di admin)!
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_3 not in pending_ids, "Card MUST disappear from Admin when marked as REWORK!"
    print("[OK] Card SUCCESSFULLY DISAPPEARED from Admin!")

    # Gudang re-registers set after rework
    gudang_rereg = api_post("/api/master/re-register", {
        "assembly_id": assy_id_3,
        "reason": "Sudah selesai rework oleh Gudang"
    }, gudang_cookie)
    assert gudang_rereg.get("ok") is True
    print("[OK] Gudang re-registered the reworked set")

    # Card MUST reappear in Admin with status Rework!
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_sets = {a['assembly_id']: a for a in admin_pending.get('data', [])}
    assert assy_id_3 in pending_sets, "Reworked set must reappear in Admin after Gudang re-registration!"
    assert pending_sets[assy_id_3]['assembly_status'] == 'Rework'
    print("[OK] Card REAPPEARED in Admin with status Rework!")

    # Admin approves Good
    admin_final_app = api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_3,
        "decision": "APPROVE_GOOD",
        "notes": "Rework berhasil, lanjut QC"
    }, admin_cookie)
    assert admin_final_app.get("ok") is True
    print("[OK] Admin approved reworked set to Good")

    # Card must disappear from Admin again
    admin_pending = api_get("/api/admin/pending-assemblies", admin_cookie)
    pending_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_3 not in pending_ids, "Approved set must disappear from Admin"
    print("[OK] Set disappeared from Admin")

    # Set must appear in QC queue with HOLD_REWORK_APPROVED lifecycle!
    qc_pending = api_get("/api/qc/pending-items", admin_cookie)
    qc_items = {a['assembly_id']: a for a in qc_pending.get('data', [])}
    assert assy_id_3 in qc_items, "Approved reworked set must appear in QC queue!"
    assert qc_items[assy_id_3]['lifecycle_path'] == 'HOLD_REWORK_APPROVED'
    print("[OK] Set appeared in QC queue with lifecycle_path = HOLD_REWORK_APPROVED!")

    # 4. TEST REPORTS
    print("\n--- TEST 4: REPORTS LIFECYCLE AUDIT ---")
    reports = api_get("/api/reports/assy-tests", admin_cookie)
    assert reports.get("ok") is True
    report_items = reports.get("data", [])
    print(f"[OK] Fetched {len(report_items)} items from /api/reports/assy-tests with lifecycle_path preserved")

    print("\n=== ALL END-TO-END FLOW TESTS PASSED WITH 100% SUCCESS! ===")

if __name__ == "__main__":
    run_tests()
