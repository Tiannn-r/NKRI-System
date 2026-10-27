-- ====================================================================
-- SKRIP SQL: PENAMBAHAN DATA MANUAL PART 12 (DOUBLE MARKING)
-- Sistem Traceability & Inventory Final Assembly P9
-- ====================================================================

BEGIN TRANSACTION;

-- 1. Buat Tray Baru untuk Unit Double Marking (atau gunakan tray yang sudah ada)
INSERT INTO trays (
    tray_code, pic_warehouse, assigned_operator, source_document_mo,
    product_type, status, verified_by_gudang, verified_at, verified_by_user,
    session_status, created_at
) VALUES (
    'WH/ASSY/000137', 'Gudang', 'Administrator NKRI', 'WH/WO/000137',
    'Nanggala P9', 'active', 1, datetime('now', 'localtime'), 'Administrator NKRI',
    'active', datetime('now', 'localtime')
);

-- Simpan tray_id yang baru dibuat
-- Pada SQLite: id = (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137')

-- 2. Master Stocks Sinkronisasi (21 Part: 7 Part 1, 7 Part 12, 7 Part 15)
-- Part 1:
INSERT INTO master_stocks (sn, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
VALUES 
    ('26371', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('38821', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('01271', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('55851', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('25841', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('40481', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('38281', '1', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime'))
ON CONFLICT(sn, part_type) DO UPDATE SET
    status = 'Done',
    allocated_tray_id = excluded.allocated_tray_id,
    used_at = excluded.used_at;

-- Part 12:
INSERT INTO master_stocks (sn, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
VALUES 
    ('62607', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('70115', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('70121', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('62597', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('62610', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('62601', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('70127', '12', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime'))
ON CONFLICT(sn, part_type) DO UPDATE SET
    status = 'Done',
    allocated_tray_id = excluded.allocated_tray_id,
    used_at = excluded.used_at;

-- Part 15:
INSERT INTO master_stocks (sn, cs, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
VALUES 
    ('K1103581', 'K', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('N1103937', 'N', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('V1103958', 'V', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('N1103925', 'N', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('X1103906', 'X', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('Z1103555', 'Z', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime')),
    ('C1102970', 'C', '15', 'Done', (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), datetime('now', 'localtime'), datetime('now', 'localtime'), datetime('now', 'localtime'))
ON CONFLICT(sn, part_type) DO UPDATE SET
    cs = excluded.cs,
    status = 'Done',
    allocated_tray_id = excluded.allocated_tray_id,
    used_at = excluded.used_at;

-- 3. Masukkan ke Tabel parts (Komponen Tray Supply)
INSERT OR REPLACE INTO parts (tray_id, part_type, sn, status, created_at)
VALUES 
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'K1103581', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '62607',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '26371',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'N1103937', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '70115',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '38821',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'V1103958', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '70121',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '01271',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'N1103925', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '62597',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '55851',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'X1103906', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '62610',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '25841',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'Z1103555', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '62601',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '40481',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '15', 'C1102970', 'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '12', '70127',    'Done', datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), '1',  '38281',    'Done', datetime('now', 'localtime'));

-- 4. Masukkan ke Tabel assemblies (Perakitan Good, Lolos TF1, Menunggu di TF2 / PENDING_QC)
INSERT INTO assemblies (
    tray_id, set_number, pic_assembly,
    part15_sn, part12_sn, part1_sn,
    status, original_assembly_status,
    assy_test_status, assy_doc_number,
    qc_status, verified_status, lifecycle_path,
    warehouse_receipt_status,
    started_at, finished_at
) VALUES 
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 1, 'Administrator NKRI', 'K1103581', '62607', '26371', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 2, 'Administrator NKRI', 'N1103937', '70115', '38821', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 3, 'Administrator NKRI', 'V1103958', '70121', '01271', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 4, 'Administrator NKRI', 'N1103925', '62597', '55851', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 5, 'Administrator NKRI', 'X1103906', '62610', '25841', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 6, 'Administrator NKRI', 'Z1103555', '62601', '40481', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime')),
    ((SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137'), 7, 'Administrator NKRI', 'C1102970', '70127', '38281', 'Good', 'Good', 'Good', 'Assy/QC/000137', 'PENDING_QC', 'ACC', 'FRESH_GOOD', 'PENDING', datetime('now', 'localtime'), datetime('now', 'localtime'))
ON CONFLICT(tray_id, set_number) DO UPDATE SET
    part15_sn = excluded.part15_sn,
    part12_sn = excluded.part12_sn,
    part1_sn = excluded.part1_sn,
    status = excluded.status,
    original_assembly_status = excluded.original_assembly_status,
    assy_test_status = excluded.assy_test_status,
    assy_doc_number = excluded.assy_doc_number,
    qc_status = excluded.qc_status,
    verified_status = excluded.verified_status,
    finished_at = excluded.finished_at;

-- 5. Masukkan ke Tabel assembly_tests (Audit Uji TF 1 / ASSY)
INSERT INTO assembly_tests (
    stage, doc_number, tray_id, tray_code, set_number, assembly_id,
    p9_sn, part15_sn, part12_sn, part1_sn,
    tester_username, tester_role, status, lifecycle_path, created_at
)
SELECT 
    'ASSY', 'Assy/QC/000137', a.tray_id, 'WH/ASSY/000137', a.set_number, a.id,
    a.part15_sn, a.part15_sn, a.part12_sn, a.part1_sn,
    'Administrator NKRI', 'assembly', 'Good', 'FRESH_GOOD', datetime('now', 'localtime')
FROM assemblies a
WHERE a.tray_id = (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137')
  AND a.set_number BETWEEN 1 AND 7;

-- 6. Masukkan ke Riwayat Event (assembly_wo_history)
INSERT INTO assembly_wo_history (
    tray_id, tray_code, set_number, pic_assembly,
    part15_sn, part12_sn, part1_sn,
    status, started_at, finished_at, source, created_at
)
SELECT 
    a.tray_id, 'WH/ASSY/000137', a.set_number, a.pic_assembly,
    a.part15_sn, a.part12_sn, a.part1_sn,
    a.status, a.started_at, a.finished_at, 'ASSEMBLY_MANUAL_DOUBLE_MARKING', datetime('now', 'localtime')
FROM assemblies a
WHERE a.tray_id = (SELECT id FROM trays WHERE tray_code = 'WH/ASSY/000137')
  AND a.set_number BETWEEN 1 AND 7;

COMMIT;
