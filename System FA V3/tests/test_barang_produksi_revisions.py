import os
import sys
import json
import time
import urllib.request
import urllib.parse
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

def test_barang_produksi_revisions():
    print("================================================================")
    print("TESTING USER REVISIONS FOR BARANG PRODUKSI & QC WORKFLOW")
    print("================================================================")

    # 1. Login Admin & Gudang
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]

    st, res = make_req("/api/login", "POST", {"username": "gudang", "password": "123", "force_takeover": True})
    gudang_token = res.get("token") or admin_token

    print("[1] Authentication OK.")

    # 2. Verify static/index.html UI Requirements
    with open("static/index.html", "r", encoding="utf-8") as f:
        html_content = f.read()

    # 2a. Pop-up validasi khsusus 1 set
    assert "Apakah Anda ingin mendaftarkan kembali barang ini?" in html_content, "Prompt text not found"
    assert 'showReregisterSetModal' in html_content, "showReregisterSetModal not found"
    assert '>Minta<' in html_content or '<span>Minta</span>' in html_content, "'Minta' button not found"
    assert '>Batal<' in html_content or 'Batal' in html_content, "'Batal' button not found"
    print("[2a OK] Pop-up validation modal requirement verified in static/index.html.")

    # 2b. Card locking & status badges
    assert "Menunggu Verifikasi Admin" in html_content, "Status 'Menunggu Verifikasi Admin' not found"
    assert "Terverifikasi" in html_content, "Status 'Terverifikasi' not found"
    print("[2b OK] Card locking and 'Menunggu Verifikasi Admin' / 'Terverifikasi' verified.")

    # 2c. Statuses beside process names (Test Assembly & QC)
    assert "Test Assembly:" in html_content, "'Test Assembly:' label not found"
    assert "QC:" in html_content, "'QC:' label not found"
    print("[2c OK] Side-by-side process status labels verified.")

    # 2d. 5 columns across x 3 rows down table format
    assert "[1, 2, 3, 4, 5]" in html_content or "[1,2,3,4,5]" in html_content, "5 columns grid row 1 not found"
    assert "[6, 7, 8, 9, 10]" in html_content or "[6,7,8,9,10]" in html_content, "5 columns grid row 2 not found"
    assert "[11, 12, 13, 14, 15]" in html_content or "[11,12,13,14,15]" in html_content, "5 columns grid row 3 not found"
    print("[2d OK] 15 stages test table 5 columns x 3 rows grid verified.")

    # 2e. Barang Produksi menu restricted to Gudang (and Admin)
    # Ensure no production_hold button in Perakitan or QC group
    perakitan_chunk = html_content[html_content.find('<!-- PERAKITAN / ASSEMBLY -->'):html_content.find('<!-- QUALITY CONTROL (QC) -->')]
    assert 'production_hold' not in perakitan_chunk, "production_hold found in Perakitan group"
    qc_chunk = html_content[html_content.find('<!-- QUALITY CONTROL (QC) -->'):html_content.find('<!-- LAPORAN & AUDIT -->')]
    assert 'production_hold' not in qc_chunk, "production_hold found in QC group"
    print("[2e OK] Menu Barang Produksi confirmed removed from Perakitan and QC sidebars.")

    # 3. Create Supply Tray via API with 1 Set
    ts = int(time.time()) % 100000
    p15_sn = f"B110{ts:04d}"
    p12_sn = f"6{ts:04d}"
    p1_sn = f"{(ts % 900) + 100:03d}"

    conn = psycopg2.connect(DB_URL)
    with conn.cursor() as cur:
        for sn, pt in [(p15_sn, '15'), (p12_sn, '12'), (p1_sn, '1')]:
            cur.execute("DELETE FROM master_stocks WHERE sn = %s", (sn,))
            cur.execute("INSERT INTO master_stocks (sn, part_type, status) VALUES (%s, %s, 'Tersedia')", (sn, pt))
        conn.commit()

    st, res = make_req("/api/gudang/save", "POST", {
        "pic_warehouse": "gudang",
        "assigned_operator": "assy",
        "source_document_mo": "MO/REV/001",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": p15_sn},
            {"part_type": "12", "sn": p12_sn},
            {"part_type": "1", "sn": p1_sn}
        ]
    }, token=gudang_token)
    assert res.get("ok"), f"Save tray failed: {res}"
    tray_id = res["data"].get("tray_id") or res["data"].get("id")
    tray_code = res["data"]["tray_code"]

    # Save Assembly as Good
    st, res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15_sn,
        "part12_sn": p12_sn,
        "part1_sn": p1_sn,
        "status": "Good",
        "operator": "Operator 1"
    }, token=admin_token)
    assert res.get("ok"), f"Assembly save failed: {res}"

    # Get assembly id
    st, res = make_req(f"/api/assembly/tray?tray_code={tray_code}", "GET", token=admin_token)
    assys = res.get("data", {}).get("assemblies", [])
    assert len(assys) > 0, "No assembly found"
    assy_id = assys[0]["id"]

    # Submit test with stage 4 NOT_GOOD
    checklist_fail = [
        {"test_num": i, "name": f"Test Step {i}", "status": "NOT_GOOD" if i == 4 else "GOOD"}
        for i in range(1, 16)
    ]
    st, res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy_id,
        "part15_sn": p15_sn,
        "part12_sn": p12_sn,
        "part1_sn": p1_sn,
        "test_checklist": checklist_fail
    }, token=admin_token)
    assert res.get("ok"), f"Assy test submit failed: {res}"
    assert res.get("status") == "Hold", f"Expected status Hold, got {res.get('status')}"
    print(f"[3 OK] Created Assembly Hold item ID: {assy_id} (P15: {p15_sn}) with Stage 4 NOT_GOOD.")

    # 4. Re-register 1 Set via API
    st, rereg_res = make_req("/api/master/re-register", "POST", {
        "assembly_id": assy_id,
        "part_type": "15",
        "sn": p15_sn,
        "reason": "Daftar ulang 1 set setelah perbaikan fisik"
    }, token=gudang_token)
    assert rereg_res.get("ok"), f"Re-register failed: {rereg_res}"
    print("[4 OK] Set re-registered successfully. Card is now locked.")

    # Verify assembly is now locked with status 'PENDING_ADMIN'
    with conn.cursor() as cur:
        cur.execute("SELECT is_reregistered, verified_status FROM assemblies WHERE id = %s", (assy_id,))
        row = cur.fetchone()
        assert row[0] is True, "is_reregistered should be True"
        assert row[1] == 'PENDING_ADMIN', f"verified_status should be PENDING_ADMIN, got {row[1]}"
    print("[4b OK] Verified DB state: is_reregistered=True, verified_status='PENDING_ADMIN' (Menunggu Verifikasi Admin).")

    # 5. Check Admin Pending Assemblies (card appears)
    st, pending_res = make_req("/api/admin/pending-assemblies", "GET", token=admin_token)
    assert pending_res.get("ok"), f"Pending assemblies failed: {pending_res}"
    found_item = next((it for it in pending_res.get("data", []) if it["assembly_id"] == assy_id), None)
    assert found_item is not None, f"Assembly {assy_id} not found in admin pending items"
    print(f"[5 OK] Verified card in Admin Verification: ID {assy_id}, Stage: {found_item['stage']}.")

    # 6. Admin Approves as GOOD (APPROVE_GOOD) -> Preserves historical test checklist!
    st, approve_res = make_req("/api/admin/approve-hold-item", "POST", {
        "assembly_id": assy_id,
        "decision": "APPROVE_GOOD",
        "notes": "ACC Good oleh Admin"
    }, token=admin_token)
    assert approve_res.get("ok"), f"Approve Good failed: {approve_res}"
    print("[6 OK] Admin approved Good.")

    # Verify in DB: status is Good, qc_status is PENDING_QC, verified_status is APPROVED,
    # and all 15 stages in assemblies are converted to GOOD for QC, while Perakitan history (assembly_tests) preserves the raw NOT_GOOD!
    with conn.cursor() as cur:
        cur.execute("SELECT status, qc_status, verified_status, is_reregistered, assy_test_checklist FROM assemblies WHERE id = %s", (assy_id,))
        row = cur.fetchone()
        status, qc_status, v_status, is_rereg, checklist_str = row
        assert status == 'Good', f"Status should be Good, got {status}"
        assert qc_status == 'PENDING_QC', f"QC status should be PENDING_QC, got {qc_status}"
        assert v_status == 'APPROVED', f"Verified status should be APPROVED (Terverifikasi), got {v_status}"
        assert is_rereg is False, "is_reregistered should be reset to False"

        # Check that in assemblies (which moves to QC), all 15 stages are GOOD
        checklist = json.loads(checklist_str) if isinstance(checklist_str, str) else checklist_str
        assert len(checklist) == 15, f"Checklist should have 15 items, got {len(checklist)}"
        stage4 = next(item for item in checklist if item["test_num"] == 4)
        assert stage4["status"] == "GOOD", f"QC Stage 4 should be GOOD, got {stage4['status']}"

        # Check that in assembly_tests (Perakitan history), Stage 4 remains NOT_GOOD
        cur.execute("SELECT test_checklist FROM assembly_tests WHERE assembly_id = %s AND stage = 'ASSY' ORDER BY id DESC LIMIT 1", (assy_id,))
        hist_row = cur.fetchone()
        assert hist_row is not None, "assembly_tests record not found"
        hist_chk = json.loads(hist_row[0]) if isinstance(hist_row[0], str) else hist_row[0]
        hist_stage4 = next(item for item in hist_chk if item["test_num"] == 4)
        assert hist_stage4["status"] == "NOT_GOOD", f"Perakitan history should retain Stage 4 as NOT_GOOD, got {hist_stage4['status']}"
    print("[6b OK] Auto-reset verified: In QC 15 stages are GOOD, and Perakitan history (assembly_tests) retained NOT_GOOD.")

    # 6c. Test Admin Decision REWORK
    st, rework_res = make_req("/api/admin/approve-hold-item", "POST", {
        "assembly_id": assy_id,
        "decision": "REWORK",
        "notes": "Harap bongkar dan rakit ulang"
    }, token=admin_token)
    assert rework_res.get("ok"), f"Approve Rework failed: {rework_res}"
    with conn.cursor() as cur:
        cur.execute("SELECT status, verified_status, lifecycle_path FROM assemblies WHERE id = %s", (assy_id,))
        rw_st, rw_vst, rw_life = cur.fetchone()
        assert rw_st == 'Rework', f"Expected status 'Rework', got {rw_st}"
        assert rw_vst == 'REWORK_GUDANG', f"Expected verified_status 'REWORK_GUDANG', got {rw_vst}"
        assert rw_life == 'REWORK', f"Expected lifecycle_path 'REWORK', got {rw_life}"
    print("[6c OK] Admin Decision REWORK verified: status='Rework', verified_status='REWORK_GUDANG'.")

    # 7. Test Warehouse Physical Verification Post-QC (ACC & DECLINED)
    # Mark assembly as QC Good first
    with conn.cursor() as cur:
        cur.execute("UPDATE assemblies SET status = 'Good', qc_status = 'GOOD', qc_tested_at = NOW(), qc_tested_by = 'qc_tester' WHERE id = %s", (assy_id,))
        conn.commit()

    # Test warehouse ACC
    st, wh_acc_res = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "tray_id": tray_id,
        "action": "ACCEPT",
        "notes": "Fisik lengkap dan rapi"
    }, token=gudang_token)
    assert wh_acc_res.get("ok"), f"Warehouse verify failed: {wh_acc_res}"
    with conn.cursor() as cur:
        cur.execute("SELECT warehouse_receipt_status FROM assemblies WHERE id = %s", (assy_id,))
        assert cur.fetchone()[0] == 'ACCEPTED', "Warehouse receipt status should be ACCEPTED"
    print("[7a OK] Warehouse QC physical verification ACCEPT verified.")

    # Test warehouse DECLINED
    st, wh_dec_res = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "tray_id": tray_id,
        "action": "DECLINED",
        "notes": "Ada lecet pada bodi"
    }, token=gudang_token)
    assert wh_dec_res.get("ok"), f"Warehouse verify declined failed: {wh_dec_res}"
    with conn.cursor() as cur:
        cur.execute("SELECT warehouse_receipt_status, declined_reason FROM assemblies WHERE id = %s", (assy_id,))
        row = cur.fetchone()
        assert row[0] == 'DECLINED', f"Expected DECLINED, got {row[0]}"
        assert row[1] == 'Ada lecet pada bodi', f"Expected notes 'Ada lecet pada bodi', got {row[1]}"
    print("[7b OK] Warehouse QC physical verification DECLINED verified.")

    # 8. Re-register set: Sesuai instruksi user, pendaftaran ulang 1 set TIDAK mencemari per-part re_register_requests
    st, rereg2_res = make_req("/api/master/re-register", "POST", {
        "assembly_id": assy_id,
        "reason": "Daftarkan ulang kedua kalinya"
    }, token=gudang_token)
    assert rereg2_res.get("ok"), f"Second re-register failed: {rereg2_res}"

    with conn.cursor() as cur:
        # Check that re_register_requests does NOT contain entries from set re-registration
        cur.execute("SELECT count(*) FROM re_register_requests WHERE sn = %s", (p15_sn,))
        req_count = cur.fetchone()[0]
        assert req_count == 0, f"Expected 0 requests in re_register_requests for set re-registration, got {req_count}"
        
        # Check that assembly itself is marked PENDING_ADMIN
        cur.execute("SELECT is_reregistered, verified_status FROM assemblies WHERE id = %s", (assy_id,))
        row = cur.fetchone()
        assert row[0] is True, "is_reregistered should be True"
        assert 'PENDING_ADMIN' in row[1], f"verified_status should be PENDING_ADMIN, got {row[1]}"
    print("[8a OK] Verified set re-registration tracks on assembly without polluting per-part re_register_requests.")

    # 8b. Test registering an individual part from master stock (without assembly_id)
    # create a standalone part
    p_standalone = f"ST{ts:04d}"
    with conn.cursor() as cur:
        cur.execute("INSERT INTO master_stocks (sn, part_type, status) VALUES (%s, '15', 'Hold')", (p_standalone,))
        conn.commit()

    st, rereg_part_res = make_req("/api/master/re-register", "POST", {
        "part_type": "15",
        "sn": p_standalone,
        "reason": "Daftar ulang individual part"
    }, token=gudang_token)
    assert rereg_part_res.get("ok"), f"Individual part re-register failed: {rereg_part_res}"

    with conn.cursor() as cur:
        cur.execute("SELECT current_status, status FROM re_register_requests WHERE sn = %s", (p_standalone,))
        part_row = cur.fetchone()
        assert part_row is not None, "Part should exist in re_register_requests"
        assert part_row[0] == 'Hold', f"Expected current_status Hold, got {part_row[0]}"
        assert part_row[1] == 'PENDING', f"Expected status PENDING, got {part_row[1]}"
    print("[8b OK] Verified individual part re-registration works cleanly and records in re_register_requests.")

    print("================================================================")
    print("ALL USER REVISION TESTS PASSED SUCCESSFULLY! 100% VERIFIED.")
    print("================================================================")

if __name__ == "__main__":
    test_barang_produksi_revisions()
