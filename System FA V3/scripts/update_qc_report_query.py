with open('app.py', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = None
end_idx = None

for i, l in enumerate(lines):
    if 'all_qc_tests AS (' in l and i > 2500 and i < 2700:
        start_idx = i
    if start_idx is not None and 'WHERE a.id NOT IN (SELECT assembly_id FROM assembly_tests WHERE stage = \'QC\' AND assembly_id IS NOT NULL)' in l and i > start_idx:
        end_idx = i
        break

print(f"start_idx: {start_idx}, end_idx: {end_idx}")

new_qc_block = """                                all_qc_tests AS (
                                    SELECT t.id, t.stage,
                                           COALESCE(NULLIF(t.doc_number, '-'), 'WH/QC/' || SUBSTR(t.tray_code, INSTR(t.tray_code, 'WH/ASSY/') + 8)) as doc_number,
                                           COALESCE(NULLIF(at_prev.doc_number, '-'), 'Assy/QC/' || SUBSTR(t.tray_code, INSTR(t.tray_code, 'WH/ASSY/') + 8)) as tf1_doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number) as assy_doc_number,
                                           t.tray_id, t.tray_code, t.set_number, t.assembly_id,
                                           COALESCE(t.part15_sn, t.p9_sn) as part15_sn,
                                           t.part1_sn, t.part12_sn,
                                           t.tester_username, t.test_checklist,
                                           t.test_checklist as qc_test_checklist,
                                           t.created_at as qc_tested_at,
                                           t.tester_username as qc_tested_by,
                                           COALESCE(u.full_name, t.tester_username) as qc_tester_name,
                                           t.status, t.failure_reasons, t.notes,
                                           COALESCE(t.lifecycle_path, a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(t.status_history, a.qc_status_history, 'Good QC') as status_history,
                                           t.created_at,
                                           COALESCE(u.full_name, t.tester_username) as tester_full_name,
                                           COALESCE(a.status, 'Good') as assembly_status,
                                           COALESCE(at_prev.status, a.assy_test_status, 'Good') as tf1_status,
                                           COALESCE(at_prev.failure_reasons, a.assy_failure_reasons) as tf1_failure_reasons,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as assy_test_checklist,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as tf1_test_checklist,
                                           a.pic_assembly,
                                           COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name,
                                           a.started_at, a.finished_at,
                                           t.status as qc_test_status,
                                           COALESCE(t.failure_reasons, t.notes, a.qc_failure_reasons) as qc_failure_reasons
                                    FROM latest_qc_tests t
                                    LEFT JOIN assemblies a ON t.assembly_id = a.id
                                    LEFT JOIN latest_assy_tests at_prev ON at_prev.assembly_id = t.assembly_id
                                    LEFT JOIN users u ON (t.tester_username = u.username OR t.tester_username = u.full_name)
                                    LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)
                                    WHERE (
                                        (COALESCE(at_prev.status, a.assy_test_status, '') = 'Good' AND a.qc_status != 'HOLD_ASSY')
                                    )

                                    UNION ALL

                                    SELECT (2000000 + a.id) as id, 'QC' as stage,
                                           COALESCE(a.qc_doc_number, 'WH/QC/' || SUBSTR(tr.tray_code, INSTR(tr.tray_code, 'WH/ASSY/') + 8)) as doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number, 'Assy/QC/' || SUBSTR(tr.tray_code, INSTR(tr.tray_code, 'WH/ASSY/') + 8)) as tf1_doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number) as assy_doc_number,
                                           a.tray_id, tr.tray_code, a.set_number, a.id as assembly_id,
                                           a.part15_sn, a.part1_sn, a.part12_sn,
                                           '-' as tester_username,
                                           '[]' as test_checklist,
                                           '[]' as qc_test_checklist,
                                           NULL as qc_tested_at,
                                           NULL as qc_tested_by,
                                           NULL as qc_tester_name,
                                           'Menunggu Test Functional 2' as status,
                                           NULL as failure_reasons,
                                           'Menunggu pengujian mutu oleh Inspector QC' as notes,
                                           COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(a.qc_status_history, 'Menunggu QC') as status_history,
                                           COALESCE(at_prev.created_at, a.finished_at, tr.created_at) as created_at,
                                           'Menunggu Inspector QC' as tester_full_name,
                                           COALESCE(a.status, 'Good') as assembly_status,
                                           COALESCE(at_prev.status, a.assy_test_status, 'Good') as tf1_status,
                                           COALESCE(at_prev.failure_reasons, a.assy_failure_reasons) as tf1_failure_reasons,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as assy_test_checklist,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as tf1_test_checklist,
                                           a.pic_assembly,
                                           COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name,
                                           a.started_at, a.finished_at,
                                           'Menunggu Test Functional 2' as qc_test_status,
                                           NULL as qc_failure_reasons
                                    FROM assemblies a
                                    JOIN trays tr ON a.tray_id = tr.id
                                    LEFT JOIN latest_assy_tests at_prev ON at_prev.assembly_id = a.id
                                    LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)
"""

if start_idx is not None and end_idx is not None:
    lines[start_idx:end_idx] = [new_qc_block + "\n"]
    with open('app.py', 'w', encoding='utf-8') as f:
        f.writelines(lines)
    print("Successfully updated app.py!")
else:
    print("Could not find start_idx or end_idx")
