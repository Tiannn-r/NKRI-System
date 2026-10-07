import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import time
import json
import urllib.request
import urllib.parse
from app import get_conn

BASE_URL = "http://localhost:8000"

def login(username, password):
    data = json.dumps({"username": username, "password": password, "force_takeover": True}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/api/login", data=data, headers={"Content-Type": "application/json"})
    with urllib.request.urlopen(req) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        return res.get('token')

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
    print("=== STARTING QC SECTION & MULTI-CYCLE REWORK TESTS ===")
    admin_tok = login("admin", "admin123")
    gudang_tok = login("Gudang", "gudang123")
    assy_tok = login("Assy", "assy123")
    print("[OK] Logged in as admin, Gudang, Assy")

    ts = int(time.time())

    # ----------------------------------------------------
    # TEST 1: ASSEMBLY TEST NOT GOOD -> GUDANG -> ADMIN CARD -> REWORK 1 -> REWORK 2 -> GOOD -> QC
    # ----------------------------------------------------
    print("\n--- TEST 1: Assembly Hold -> Gudang -> Admin -> Multi Rework -> Good -> QC ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-T1', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-A-{ts}',))
            tray_id_1 = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'Assy', NOW())
                RETURNING id
            """, (tray_id_1, f'P15-A-{ts}', f'P1-A-{ts}', f'P12-A-{ts}'))
            assy_id_1 = cur.fetchone()['id']
            # Insert master stocks
            for pt, sn in [('15', f'P15-A-{ts}'), ('1', f'P1-A-{ts}'), ('12', f'P12-A-{ts}')]:
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Good')", (pt, sn))
            conn.commit()

    # Assembly test fails
    res_test1 = api_post("/api/assembly/submit-test", {
        "tray_id": tray_id_1,
        "set_number": 1,
        "assembly_id": assy_id_1,
        "part15_sn": f'P15-A-{ts}',
        "part12_sn": f'P12-A-{ts}',
        "part1_sn": f'P1-A-{ts}',
        "test_checklist": [{"test_num": 1, "status": "NOT_GOOD"}],
        "notes": "Failed test 1 assembly"
    }, assy_tok)
    assert res_test1.get("ok") is True

    # 1a. Verify it is with Gudang (HOLD_GUDANG) and NOT in Admin yet
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_1 not in assy_ids, "Assembly hold set must NOT appear in Admin before Gudang registers it!"
    print("[OK] Hold set is with Gudang in Master Stock (not in Admin)")

    # 1b. Gudang registers the set
    gudang_reg1 = api_post("/api/master/re-register", {
        "assembly_id": assy_id_1,
        "reason": "Daftar ulang hold assembly oleh Gudang"
    }, gudang_tok)
    assert gudang_reg1.get("ok") is True
    print("[OK] Gudang registered set to Admin")

    # 1c. Now it appears in Admin BARANG RAKITAN
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_1 in assy_ids, "Set must appear in Admin BARANG RAKITAN after Gudang registration!"
    print("[OK] Set appeared in Admin BARANG RAKITAN as Card")

    # 1d. Admin chooses REWORK 1 -> Card disappears from Admin
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_1,
        "decision": "REWORK",
        "notes": "Rework putaran 1"
    }, admin_tok)
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_1 not in assy_ids, "Set must disappear from Admin after REWORK decision!"
    print("[OK] Set disappeared from Admin, entered Gudang for Rework 1")

    # 1e. Gudang finishes rework 1 and re-registers
    api_post("/api/master/re-register", {
        "assembly_id": assy_id_1,
        "reason": "Selesai rework 1 oleh Gudang"
    }, gudang_tok)

    # 1f. Set reappears in Admin with status Rework -> Admin chooses REWORK LAGI (Rework 2)!
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_map = {a['assembly_id']: a for a in admin_pending.get('data', [])}
    assert assy_id_1 in assy_map
    assert assy_map[assy_id_1]['assembly_status'] == 'Rework'
    print("[OK] Set reappeared in Admin with status Rework")

    # Admin clicks REWORK again (bisa jadi rework lagi)
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_1,
        "decision": "REWORK",
        "notes": "Rework putaran 2 (masih belum lolos)"
    }, admin_tok)
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    assert assy_id_1 not in assy_ids, "Set must disappear again after REWORK 2 decision!"
    print("[OK] Set successfully re-routed to Gudang for REWORK 2!")

    # 1g. Gudang finishes rework 2 and re-registers
    api_post("/api/master/re-register", {
        "assembly_id": assy_id_1,
        "reason": "Selesai rework 2 oleh Gudang"
    }, gudang_tok)

    # 1h. Admin approves GOOD -> moves to QC
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_1,
        "decision": "APPROVE_GOOD",
        "notes": "Rework 2 oke, lanjut QC"
    }, admin_tok)
    qc_pending = api_get("/api/qc/pending-items", admin_tok)
    qc_ids = [a['assembly_id'] for a in qc_pending.get('data', [])]
    assert assy_id_1 in qc_ids, "Set must appear in QC queue after final Good approval!"
    print("[OK] Set successfully approved and entered QC queue!")

    # ----------------------------------------------------
    # TEST 2: QC TEST NOT GOOD -> GUDANG -> ADMIN SECTION HASIL QC -> REWORK QC -> GOOD QC -> WAREHOUSE RECEIPT
    # ----------------------------------------------------
    print("\n--- TEST 2: QC Hold -> Gudang -> Admin HASIL QC Section -> Rework QC -> Good QC -> Gudang Receipt ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-T2', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-QC-{ts}',))
            tray_id_2 = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, qc_status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'PENDING_QC', 'Assy', NOW())
                RETURNING id
            """, (tray_id_2, f'P15-QC-{ts}', f'P1-QC-{ts}', f'P12-QC-{ts}'))
            assy_id_2 = cur.fetchone()['id']
            for pt, sn in [('15', f'P15-QC-{ts}'), ('1', f'P1-QC-{ts}'), ('12', f'P12-QC-{ts}')]:
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Good')", (pt, sn))
            conn.commit()

    # QC test fails (Test 3 Not Good)
    res_qc_test = api_post("/api/qc/submit-test", {
        "tray_id": tray_id_2,
        "set_number": 1,
        "assembly_id": assy_id_2,
        "part15_sn": f'P15-QC-{ts}',
        "part12_sn": f'P12-QC-{ts}',
        "part1_sn": f'P1-QC-{ts}',
        "test_checklist": [
            {"test_num": 1, "status": "GOOD"},
            {"test_num": 2, "status": "GOOD"},
            {"test_num": 3, "status": "NOT_GOOD"},
            {"test_num": 4, "status": "GOOD"},
            {"test_num": 5, "status": "GOOD"},
        ],
        "notes": "Failed QC test 3"
    }, admin_tok)
    assert res_qc_test.get("ok") is True
    print("[OK] QC test submitted as Hold")

    # 2a. Verify it is with Gudang in Master Stock (HOLD_QC_GUDANG) and NOT in Admin yet
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    qc_ids = [a['assembly_id'] for a in admin_pending.get('qc_data', [])]
    assert assy_id_2 not in assy_ids and assy_id_2 not in qc_ids, "QC hold set must NOT appear in Admin before Gudang registers it!"
    print("[OK] QC Hold set is held with Gudang in Master Stock")

    # 2b. Gudang registers the set from Master Stock
    gudang_reg_qc = api_post("/api/master/re-register", {
        "assembly_id": assy_id_2,
        "reason": "Daftar ulang barang gagal QC oleh Gudang"
    }, gudang_tok)
    assert gudang_reg_qc.get("ok") is True
    print("[OK] Gudang registered QC hold set to Admin")

    # 2c. NOW it MUST appear in Admin HASIL QC (qc_data), NOT in Barang Perakitan (data)!
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_ids = [a['assembly_id'] for a in admin_pending.get('data', [])]
    qc_map = {a['assembly_id']: a for a in admin_pending.get('qc_data', [])}
    assert assy_id_2 not in assy_ids, "QC Hold set must NOT appear in BARANG PERAKITAN section!"
    assert assy_id_2 in qc_map, "QC Hold set MUST appear in HASIL QC section (qc_data)!"
    print("[OK] Set correctly separated: Appears in HASIL QC section, NOT in Barang Perakitan!")

    # 2d. Admin selects REWORK_QC -> Disappears from Admin
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_2,
        "decision": "REWORK_QC",
        "notes": "Rework fisik part gagal QC"
    }, admin_tok)
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    qc_ids = [a['assembly_id'] for a in admin_pending.get('qc_data', [])]
    assert assy_id_2 not in qc_ids, "QC set must disappear from Admin after REWORK_QC decision!"
    print("[OK] Set disappeared from HASIL QC, returned to Gudang for rework")

    # 2e. Gudang finishes rework and re-registers
    api_post("/api/master/re-register", {
        "assembly_id": assy_id_2,
        "reason": "Selesai rework QC oleh Gudang"
    }, gudang_tok)

    # 2f. Reappears in HASIL QC section with status Rework
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    qc_map = {a['assembly_id']: a for a in admin_pending.get('qc_data', [])}
    assert assy_id_2 in qc_map, "Reworked QC set must reappear in HASIL QC section!"
    assert qc_map[assy_id_2]['assembly_status'] == 'Rework'
    print("[OK] Reworked QC set reappeared in HASIL QC section with status Rework")

    # 2g. Admin approves GOOD_QC -> moves to Warehouse physical receipt
    admin_app_qc = api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id_2,
        "decision": "APPROVE_GOOD_QC",
        "notes": "QC rework disetujui Good oleh Admin"
    }, admin_tok)
    assert admin_app_qc.get("ok") is True
    print("[OK] Admin approved QC set as Good")

    # Disappears from Admin HASIL QC
    admin_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    qc_ids = [a['assembly_id'] for a in admin_pending.get('qc_data', [])]
    assert assy_id_2 not in qc_ids
    print("[OK] QC set disappeared from Admin and forwarded to final Warehouse receipt!")

    print("\n=== ALL QC SECTION & MULTI-CYCLE REWORK TESTS PASSED 100%! ===")

if __name__ == "__main__":
    run_tests()
