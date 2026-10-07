import re

with open('app.py', 'r', encoding='utf-8') as f:
    content = f.read()

target = '''                                    ORDER BY t.id DESC LIMIT 1
                                """)
                            tray = cur.fetchone()
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)'''

replacement = '''                                    ORDER BY t.id DESC LIMIT 1
                                """)
                            tray = cur.fetchone()
                            if not tray and tray_code:
                                clean_code = re.sub(r'-PU\\d*$', '', tray_code, flags=re.IGNORECASE).strip()
                                digits = re.findall(r'\\d+', clean_code)
                                candidate_codes = [clean_code]
                                if digits:
                                    last_digits = digits[-1]
                                    candidate_codes.append(f"WH/ASSY/{last_digits.zfill(6)}")
                                    candidate_codes.append(f"WH/ASSY/{last_digits}")
                                    candidate_codes.append(last_digits.zfill(6))
                                    candidate_codes.append(last_digits)
                                for cand in candidate_codes:
                                    cur.execute("""
                                        SELECT t.id, t.tray_code, 
                                               COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
                                               COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
                                               t.source_document_mo, 
                                               COALESCE(t.product_type, 'Nanggala P9') as product_type, 
                                               COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                               t.verified_at, t.verified_by_user,
                                               COALESCE(t.session_status, 'active') as session_status,
                                               t.return_reason, t.return_notified,
                                               t.created_at
                                        FROM trays t
                                        LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                        LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                        WHERE t.tray_code = ?
                                    """, (cand,))
                                    tray = cur.fetchone()
                                    if tray:
                                        break
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)'''

count = content.count(target)
print('Count of target:', count)
if count == 1:
    content = content.replace(target, replacement)
    with open('app.py', 'w', encoding='utf-8') as f:
        f.write(content)
    print('app.py updated fallback successfully!')
else:
    print('Target not matched exactly')
