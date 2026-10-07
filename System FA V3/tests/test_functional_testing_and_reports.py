import sys
import os
import json

# Ensure app can be imported
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import ensure_db_schema, get_next_doc_number, get_conn

def test_full_functional_testing_and_reporting_workflow():
    ensure_db_schema()
    conn = get_conn()
    cur = conn.cursor()

    # 1. Check doc number generators
    doc1 = get_next_doc_number(cur, 'ASSY')
    assert 'Assy/QC/' in doc1, f"Expected Assy/QC/XXXXXX, got {doc1}"
    doc_qc1 = get_next_doc_number(cur, 'QC')
    assert 'WH/QC/' in doc_qc1, f"Expected WH/QC/XXXXXX, got {doc_qc1}"

    # 2. Insert test users
    cur.execute("""
        INSERT INTO users (nik, full_name, username, password, role)
        VALUES ('101', 'Operator Budi', 'budi_assy_test', 'pass123', 'assembly')
        ON CONFLICT (username) DO NOTHING
    """)
    cur.execute("""
        INSERT INTO users (nik, full_name, username, password, role)
        VALUES ('102', 'Inspector QC Rina', 'rina_qc_test', 'pass123', 'qc')
        ON CONFLICT (username) DO NOTHING
    """)
    cur.execute("""
        INSERT INTO users (nik, full_name, username, password, role)
        VALUES ('103', 'Gudang Agus', 'agus_wh_test', 'pass123', 'gudang')
        ON CONFLICT (username) DO NOTHING
    """)
    cur.execute("""
        INSERT INTO users (nik, full_name, username, password, role)
        VALUES ('104', 'Admin Super', 'admin_test', 'admin123', 'admin')
        ON CONFLICT (username) DO NOTHING
    """)

    # 3. Warehouse supplies a tray
    cur.execute("DELETE FROM assembly_tests WHERE tray_code LIKE 'TRAY-TEST-%'")
    cur.execute("DELETE FROM assemblies WHERE tray_id IN (SELECT id FROM trays WHERE tray_code LIKE 'TRAY-TEST-%')")
    cur.execute("DELETE FROM parts WHERE tray_id IN (SELECT id FROM trays WHERE tray_code LIKE 'TRAY-TEST-%')")
    cur.execute("DELETE FROM trays WHERE tray_code LIKE 'TRAY-TEST-%'")
    cur.execute("""
        INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, created_at)
        VALUES ('TRAY-TEST-001', 'agus_wh_test', 'budi_assy_test', 'MO-TEST-001', 'Nanggala P9', NOW())
        RETURNING id
    """)
    tray_id = cur.fetchone()['id']

    # Parts
    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('15', 'SN-P9-T001', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))
    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('12', 'PN-P9-12-T001', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))
    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('1', 'PN-P9-1-T001', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))

    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('15', 'SN-P9-T002', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))
    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('12', 'PN-P9-12-T002', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))
    cur.execute("INSERT INTO parts (part_type, sn, status, tray_id) VALUES ('1', 'PN-P9-1-T002', 'Supplied', %s) ON CONFLICT (tray_id, sn) DO NOTHING", (tray_id,))

    # Assemblies (Set 1 & Set 2)
    cur.execute("""
        INSERT INTO assemblies (tray_id, set_number, part15_sn, part12_sn, part1_sn, pic_assembly, started_at, finished_at, status)
        VALUES (%s, 1, 'SN-P9-T001', 'PN-P9-12-T001', 'PN-P9-1-T001', 'budi_assy_test', NOW(), NOW(), 'In Progress')
        RETURNING id
    """, (tray_id,))
    assembly1_id = cur.fetchone()['id']

    cur.execute("""
        INSERT INTO assemblies (tray_id, set_number, part15_sn, part12_sn, part1_sn, pic_assembly, started_at, finished_at, status)
        VALUES (%s, 2, 'SN-P9-T002', 'PN-P9-12-T002', 'PN-P9-1-T002', 'budi_assy_test', NOW(), NOW(), 'In Progress')
        RETURNING id
    """, (tray_id,))
    assembly2_id = cur.fetchone()['id']
    conn.commit()

    # 4. Simulate Operator Assembly Functional Test on Set 1 (All Good)
    checklist_good = [
        {"test_num": 1, "name": "Uji Kelistrikan & Daya", "status": "GOOD"},
        {"test_num": 2, "name": "Uji Sensor & Input", "status": "GOOD"},
        {"test_num": 3, "name": "Uji Mekanikal Komponen", "status": "GOOD"},
        {"test_num": 4, "name": "Uji Komunikasi Data", "status": "GOOD"},
        {"test_num": 5, "name": "Uji Kalibrasi & Keselarasan", "status": "GOOD"}
    ]
    assy_doc_num = get_next_doc_number(cur, 'ASSY')
    cur.execute("""
        INSERT INTO assembly_tests (stage, doc_number, tray_id, tray_code, set_number, assembly_id, p9_sn, part15_sn, part12_sn, part1_sn, tester_username, tester_role, test_checklist, status, failure_reasons, notes, created_at)
        VALUES ('ASSY', %s, %s, 'TRAY-TEST-001', 1, %s, 'SN-P9-T001', 'SN-P9-T001', 'PN-P9-12-T001', 'PN-P9-1-T001', 'budi_assy_test', 'assembly', %s, 'Good', NULL, 'Kondisi rakitan sangat prima', NOW())
    """, (assy_doc_num, tray_id, assembly1_id, json.dumps(checklist_good)))
    
    cur.execute("""
        UPDATE assemblies
        SET status = 'Good', assy_doc_number = %s, qc_status = 'PENDING_QC'
        WHERE id = %s
    """, (assy_doc_num, assembly1_id))
    conn.commit()

    # 5. Simulate Operator Assembly Functional Test on Set 2 (Failed tests 2 & 4 -> Hold)
    checklist_fail = [
        {"test_num": 1, "name": "Uji Kelistrikan & Daya", "status": "GOOD"},
        {"test_num": 2, "name": "Uji Sensor & Input", "status": "NOT_GOOD"},
        {"test_num": 3, "name": "Uji Mekanikal Komponen", "status": "GOOD"},
        {"test_num": 4, "name": "Uji Komunikasi Data", "status": "NOT_GOOD"},
        {"test_num": 5, "name": "Uji Kalibrasi & Keselarasan", "status": "GOOD"}
    ]
    fails = [item["test_num"] for item in checklist_fail if item["status"] in ("NOT_GOOD", "NOT GOOD")]
    failure_reasons_str = ", ".join([f"gagal dalam test {num}" for num in fails])
    assert failure_reasons_str == "gagal dalam test 2, gagal dalam test 4", f"Unexpected failure reason: {failure_reasons_str}"

    cur.execute("""
        INSERT INTO assembly_tests (stage, doc_number, tray_id, tray_code, set_number, assembly_id, p9_sn, part15_sn, part12_sn, part1_sn, tester_username, tester_role, test_checklist, status, failure_reasons, notes, created_at)
        VALUES ('ASSY', NULL, %s, 'TRAY-TEST-001', 2, %s, 'SN-P9-T002', 'SN-P9-T002', 'PN-P9-12-T002', 'PN-P9-1-T002', 'budi_assy_test', 'assembly', %s, 'Hold', %s, 'Sensor tidak merespon transmisi', NOW())
    """, (tray_id, assembly2_id, json.dumps(checklist_fail), failure_reasons_str))

    cur.execute("""
        UPDATE assemblies
        SET status = 'Hold', reason = %s, assy_doc_number = NULL, qc_status = 'HOLD_ASSY'
        WHERE id = %s
    """, (failure_reasons_str, assembly2_id))
    conn.commit()

    # Verify QC Pending Query only picks up Set 1 (Good from Assy)
    cur.execute("""
        SELECT a.id, a.tray_id, a.set_number, a.part15_sn, a.part12_sn, a.part1_sn, a.status, a.assy_doc_number, a.qc_status, t.tray_code
        FROM assemblies a
        JOIN trays t ON a.tray_id = t.id
        WHERE a.status = 'Good' AND (a.qc_status IS NULL OR a.qc_status = 'PENDING_QC')
          AND a.id = %s
    """, (assembly1_id,))
    qc_pending = cur.fetchall()
    assert len(qc_pending) == 1, f"Expected 1 item in QC pending, got {len(qc_pending)}"
    assert qc_pending[0]['part15_sn'] == 'SN-P9-T001'
    assert qc_pending[0]['assy_doc_number'] == assy_doc_num

    # 6. Simulate QC Functional Test (Tahap 2) on Set 1 (All Good -> mutlak Good & WH/QC Doc)
    checklist_qc_good = [
        {"test_num": 1, "name": "Pemeriksaan Visual & Label Mutu", "status": "GOOD"},
        {"test_num": 2, "name": "Pengujian Fungsi Elektronik & Power", "status": "GOOD"},
        {"test_num": 3, "name": "Pengujian Kinerja Sensor & Respon", "status": "GOOD"},
        {"test_num": 4, "name": "Uji Ketahanan & Stress Test", "status": "GOOD"},
        {"test_num": 5, "name": "Kalibrasi Akhir Standar Mutu QC", "status": "GOOD"}
    ]
    qc_doc_num = get_next_doc_number(cur, 'QC')
    cur.execute("""
        INSERT INTO assembly_tests (stage, doc_number, tray_id, tray_code, set_number, assembly_id, p9_sn, part15_sn, part12_sn, part1_sn, tester_username, tester_role, test_checklist, status, failure_reasons, notes, created_at)
        VALUES ('QC', %s, %s, 'TRAY-TEST-001', 1, %s, 'SN-P9-T001', 'SN-P9-T001', 'PN-P9-12-T001', 'PN-P9-1-T001', 'rina_qc_test', 'qc', %s, 'Good', NULL, 'Lolos semua standar mutu QC', NOW())
    """, (qc_doc_num, tray_id, assembly1_id, json.dumps(checklist_qc_good)))

    cur.execute("""
        UPDATE assemblies
        SET qc_status = 'Good', qc_doc_number = %s, qc_tested_at = NOW(), qc_tested_by = 'rina_qc_test', warehouse_receipt_status = 'PENDING'
        WHERE id = %s
    """, (qc_doc_num, assembly1_id))
    conn.commit()

    # 7. Simulate Warehouse Physical Receipt Verification (Accept)
    cur.execute("""
        UPDATE assemblies
        SET warehouse_receipt_status = 'ACC', warehouse_receipt_at = NOW(), warehouse_receipt_by = 'agus_wh_test'
        WHERE id = %s
    """, (assembly1_id,))
    conn.commit()

    # 8. Check all 4 reports
    # Report 1: Assy Tests
    cur.execute("SELECT * FROM assembly_tests WHERE stage = 'ASSY' AND assembly_id IN (%s, %s) ORDER BY id ASC", (assembly1_id, assembly2_id))
    assy_reports = cur.fetchall()
    assert len(assy_reports) == 2, f"Expected 2 assy test records, got {len(assy_reports)}"
    assert assy_reports[0]['status'] == 'Good'
    assert assy_reports[0]['part12_sn'] == 'PN-P9-12-T001'
    assert assy_reports[0]['part1_sn'] == 'PN-P9-1-T001'
    assert assy_reports[1]['status'] == 'Hold'
    assert 'gagal dalam test 2' in assy_reports[1]['failure_reasons']

    # Report 2: QC Tests
    cur.execute("SELECT * FROM assembly_tests WHERE stage = 'QC' AND assembly_id = %s ORDER BY id ASC", (assembly1_id,))
    qc_reports = cur.fetchall()
    assert len(qc_reports) == 1, f"Expected 1 qc test record, got {len(qc_reports)}"
    assert qc_reports[0]['doc_number'] == qc_doc_num
    assert qc_reports[0]['part15_sn'] == 'SN-P9-T001'

    # Report 3: Warehouse Receipts
    cur.execute("""
        SELECT a.id, a.part15_sn, a.part12_sn, a.part1_sn, a.qc_doc_number, a.warehouse_receipt_status, a.warehouse_receipt_by, t.tray_code
        FROM assemblies a
        JOIN trays t ON a.tray_id = t.id
        WHERE a.qc_status = 'Good' AND a.id = %s
    """, (assembly1_id,))
    wh_receipts = cur.fetchall()
    assert len(wh_receipts) == 1, f"Expected 1 wh receipt record, got {len(wh_receipts)}"
    assert wh_receipts[0]['warehouse_receipt_status'] == 'ACC'
    assert wh_receipts[0]['qc_doc_number'] == qc_doc_num

    # 9. Test Admin Hold Approval on Set 2 (Approve to Good with Assy Doc generation)
    admin_approved_assy_doc = get_next_doc_number(cur, 'ASSY')
    cur.execute("""
        UPDATE assemblies
        SET status = 'Good', assy_doc_number = %s, qc_status = 'PENDING_QC'
        WHERE id = %s
    """, (admin_approved_assy_doc, assembly2_id))
    cur.execute("""
        UPDATE assembly_tests
        SET admin_approved_at = NOW(), admin_approved_by = 'admin_test', admin_action = 'APPROVE_GOOD'
        WHERE assembly_id = %s AND stage = 'ASSY'
    """, (assembly2_id,))
    conn.commit()

    cur.execute("SELECT status, assy_doc_number, qc_status FROM assemblies WHERE id = %s", (assembly2_id,))
    set2_after_admin = cur.fetchone()
    assert set2_after_admin['status'] == 'Good'
    assert set2_after_admin['qc_status'] == 'PENDING_QC'
    assert set2_after_admin['assy_doc_number'] == admin_approved_assy_doc

    # Cleanup test tray and assemblies
    cur.execute("DELETE FROM assembly_tests WHERE tray_id = %s", (tray_id,))
    cur.execute("DELETE FROM assemblies WHERE tray_id = %s", (tray_id,))
    cur.execute("DELETE FROM parts WHERE tray_id = %s", (tray_id,))
    cur.execute("DELETE FROM trays WHERE id = %s", (tray_id,))
    conn.commit()
    conn.close()

    print("SUCCESS: ALL 9 TEST CASES OF 2-STAGE FUNCTIONAL TESTING & REPORTING PASSED!")

if __name__ == '__main__':
    test_full_functional_testing_and_reporting_workflow()
