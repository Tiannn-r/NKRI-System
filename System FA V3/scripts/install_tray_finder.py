import re

with open('app.py', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add find_tray_by_code helper function before DEFAULT_TESTING_POINTS
helper_fn = '''
def find_tray_by_code(cur, code_str):
    if not code_str:
        return None
    code_str = str(code_str).strip()
    query_sql = """
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
    """
    cur.execute(query_sql, (code_str,))
    tray = cur.fetchone()
    if tray:
        return tray
    # Try fuzzy variations: strip -PU suffix, try numeric padding, etc.
    clean_code = re.sub(r'-PU\\d*$', '', code_str, flags=re.IGNORECASE).strip()
    digits = re.findall(r'\\d+', clean_code)
    candidates = [clean_code]
    if digits:
        last_digits = digits[-1]
        candidates.append(f"WH/ASSY/{last_digits.zfill(6)}")
        candidates.append(f"WH/ASSY/{last_digits}")
        candidates.append(last_digits.zfill(6))
        candidates.append(last_digits)
    for cand in candidates:
        cur.execute(query_sql, (cand,))
        tray = cur.fetchone()
        if tray:
            return tray
    return None

'''

if "def find_tray_by_code" not in content:
    content = content.replace("DEFAULT_TESTING_POINTS = [", helper_fn + "DEFAULT_TESTING_POINTS = [", 1)
    print("Added find_tray_by_code helper")

# 2. Update /api/assembly/tray & /api/tray/detail (around line 1820)
old_tray_lookup = '''                            elif tray_code:
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
                                """, (tray_code,))'''

new_tray_lookup = '''                            elif tray_code:
                                tray = find_tray_by_code(cur, tray_code)'''

if old_tray_lookup in content:
    content = content.replace(old_tray_lookup, new_tray_lookup, 1)
    # Also adjust the fetchone below it
    content = content.replace(
'''                                ORDER BY t.id DESC LIMIT 1
                                """)
                            tray = cur.fetchone()''',
'''                                ORDER BY t.id DESC LIMIT 1
                                """)
                                tray = cur.fetchone()''', 1)
    print("Updated /api/assembly/tray lookup")

# 3. Update /api/traveler/tray (around line 2800)
old_traveler_lookup = '''                            elif code:
                                cur.execute("""
                                    SELECT t.id, t.tray_code,
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                           t.source_document_mo,
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           t.created_at
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.tray_code = ?
                                """, (code,))'''

new_traveler_lookup = '''                            elif code:
                                tray = find_tray_by_code(cur, code)'''

if old_traveler_lookup in content:
    content = content.replace(old_traveler_lookup, new_traveler_lookup, 1)
    # Also adjust the fetchone below it
    content = content.replace(
'''                                    ORDER BY t.id DESC LIMIT 1
                                """)
                            tray = cur.fetchone()''',
'''                                    ORDER BY t.id DESC LIMIT 1
                                """)
                                tray = cur.fetchone()''', 1)
    print("Updated /api/traveler/tray lookup")

with open('app.py', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done updating app.py!")
