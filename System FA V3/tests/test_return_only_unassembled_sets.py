import json
import urllib.request
import urllib.parse
import threading
import time
import psycopg2
from psycopg2.extras import RealDictCursor
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import app

DB_CONFIG = {
    "dbname": "barang_db",
    "user": "postgres",
    "password": "muhammad_ibnufs",
    "host": "localhost",
    "port": 5432
}

def get_conn():
    return psycopg2.connect(**DB_CONFIG, cursor_factory=RealDictCursor)

def run_tests():
    port = 52912
    server = app.ThreadingHTTPServer(('127.0.0.1', port), app.Handler)
    server_thread = threading.Thread(target=server.serve_forever, daemon=True)
    server_thread.start()
    time.sleep(1)
    base_url = f"http://127.0.0.1:{port}"
    print(f"Test server running at {base_url}")

    def request(path, method="GET", body=None, token=None):
        url = base_url + path
        headers = {}
        data = None
        if token:
            headers['Authorization'] = f"Bearer {token}"
        if body is not None:
            headers['Content-Type'] = 'application/json'
            data = json.dumps(body).encode('utf-8')
        req = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req) as response:
                res_data = response.read().decode('utf-8')
                return response.status, json.loads(res_data) if res_data else {}
        except urllib.request.HTTPError as e:
            err_data = e.read().decode('utf-8')
            return e.code, json.loads(err_data) if err_data else {}

    # 1. Login Admin, Gudang & Assembly (with force_takeover=True)
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM user_sessions")
            conn.commit()

    code, res_admin = request("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert code == 200 and res_admin.get("ok"), f"Admin login failed: {res_admin}"
    token_admin = res_admin["token"]

    code, res_wh = request("/api/login", "POST", {"username": "gudang", "password": "gudang123", "force_takeover": True})
    if code != 200 or not res_wh.get("ok"):
        token_wh = token_admin
    else:
        token_wh = res_wh["token"]

    # Ensure Operator Arini Salsabila exists
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO users (username, password, full_name, nik, role)
                VALUES ('arini', '123', 'Arini Salsabila', 'NIK-ARINI-01', 'assembly')
                ON CONFLICT (username) DO UPDATE SET password = '123', full_name = 'Arini Salsabila', role = 'assembly'
            """)
            conn.commit()

    code, res_assy = request("/api/login", "POST", {"username": "arini", "password": "123", "force_takeover": True})
    assert code == 200 and res_assy.get("ok"), f"Arini login failed: {res_assy}"
    token_assy = res_assy["token"]

    # 2. Prepare mock parts and tray with 3 sets:
    # Set 1: P15_A, P1_A, P12_A
    # Set 2: P15_B, P1_B, P12_B
    # Set 3: P15_C, P1_C, P12_C
    test_sns = [
        ('15', 'TEST_UNASSY_15_A'), ('1', 'TEST_UNASSY_01_A'), ('12', 'TEST_UNASSY_12_A'),
        ('15', 'TEST_UNASSY_15_B'), ('1', 'TEST_UNASSY_01_B'), ('12', 'TEST_UNASSY_12_B'),
        ('15', 'TEST_UNASSY_15_C'), ('1', 'TEST_UNASSY_01_C'), ('12', 'TEST_UNASSY_12_C')
    ]

    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in test_sns:
                cur.execute("DELETE FROM parts WHERE sn = %s", (sn,))
                cur.execute("DELETE FROM master_stocks WHERE sn = %s", (sn,))
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Tersedia')", (pt, sn))
            cur.execute("DELETE FROM trays WHERE tray_code = 'WH/ASSY/TEST_RETURN_UNASSY'")
            conn.commit()

    # Save Tray in Gudang
    code, res_save_tray = request("/api/gudang/save", "POST", {
        "pic_warehouse": "Granada",
        "assigned_operator": "Arini Salsabila",
        "source_document_mo": "MO-TEST-UNASSY",
        "product_type": "Nanggala P9",
        "parts": [{"part_type": pt, "sn": sn} for pt, sn in test_sns]
    }, token=token_wh)
    assert code == 200 and res_save_tray.get("ok"), f"Failed saving tray: {res_save_tray}"
    tray_id = res_save_tray["data"]["tray_id"]
    tray_code = res_save_tray["data"]["tray_code"]
    print(f"[1] Tray created: {tray_code} (ID: {tray_id}) with 3 sets.")

    # 3. Assemble Set 1 (P15_A, P1_A, P12_A)
    code, res_assy_save = request("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "Operator Test",
        "part15": "TEST_UNASSY_15_A",
        "part12": "TEST_UNASSY_12_A",
        "part1": "TEST_UNASSY_01_A",
        "status": "Good"
    }, token=token_assy)
    assert code == 200 and res_assy_save.get("ok"), f"Failed assembling Set 1: {res_assy_save}"
    print("[2] Set 1 successfully assembled as 'Good'. Set 2 and Set 3 remain unassembled.")

    # Verify Set 1 is Done in master_stocks and parts, and in assemblies table
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT COUNT(*) as c FROM assemblies WHERE tray_id = %s", (tray_id,))
            assert cur.fetchone()['c'] == 1, "Expected 1 assembly record for Set 1"

            cur.execute("SELECT status FROM master_stocks WHERE sn = 'TEST_UNASSY_15_A'")
            assert cur.fetchone()['status'] == 'Done', "Set 1 P15 should have status 'Done' in master_stocks"

    # 4. Operator triggers "Kembalikan ke Gudang" (RETURN_WAREHOUSE)
    code, res_return = request("/api/assembly/session-action", "POST", {
        "tray_id": tray_id,
        "tray_code": tray_code,
        "action": "RETURN_WAREHOUSE",
        "reason": "Uji coba pengembalian hanya set yang belum terakit"
    }, token=token_assy)
    assert code == 200 and res_return.get("ok"), f"Return warehouse failed: {res_return}"
    print(f"[3] RETURN_WAREHOUSE response message: {res_return.get('message')}")

    # 5. VERIFY DATABASE STATE AFTER RETURN_WAREHOUSE:
    with get_conn() as conn:
        with conn.cursor() as cur:
            # (A) Assemblies record for Set 1 MUST NOT BE DELETED!
            cur.execute("SELECT COUNT(*) as c, MAX(status) as st FROM assemblies WHERE tray_id = %s", (tray_id,))
            row = cur.fetchone()
            assert row['c'] == 1, f"Expected assemblies to remain 1, got {row['c']}"
            assert row['st'] == 'Good', f"Expected assembly status Good, got {row['st']}"
            print("[4 OK] Set 1 remains intact in assemblies table!")

            # (B) Master stocks for Set 1 MUST REMAIN 'Done'
            cur.execute("SELECT sn, status FROM master_stocks WHERE sn IN ('TEST_UNASSY_15_A', 'TEST_UNASSY_01_A', 'TEST_UNASSY_12_A')")
            rows = cur.fetchall()
            for r in rows:
                assert r['status'] == 'Done', f"Set 1 part {r['sn']} should remain 'Done', got {r['status']}"
            print("[5 OK] Constituent parts of Set 1 remain 'Done' in master_stocks!")

            # (C) Master stocks for Set 2 & Set 3 (unassembled) MUST BE RESET TO 'Tersedia'
            cur.execute("SELECT sn, status, allocated_tray_id FROM master_stocks WHERE sn IN ('TEST_UNASSY_15_B', 'TEST_UNASSY_01_B', 'TEST_UNASSY_12_B', 'TEST_UNASSY_15_C', 'TEST_UNASSY_01_C', 'TEST_UNASSY_12_C')")
            rows = cur.fetchall()
            assert len(rows) == 6, f"Expected 6 unassembled parts in master_stocks, got {len(rows)}"
            for r in rows:
                assert r['status'] == 'Tersedia', f"Unassembled part {r['sn']} should be 'Tersedia', got {r['status']}"
                assert r['allocated_tray_id'] is None, f"Unassembled part {r['sn']} should have allocated_tray_id = NULL"
            print("[6 OK] Unassembled parts (Sets 2 and 3) are reset to 'Tersedia' and unallocated in master_stocks!")

            # (D) Parts table status: Set 1 is 'Done', Set 2 & 3 are 'RETURNED'
            cur.execute("SELECT sn, status FROM parts WHERE tray_id = %s", (tray_id,))
            parts_dict = {r['sn']: r['status'] for r in cur.fetchall()}
            assert parts_dict['TEST_UNASSY_15_A'] == 'Done', f"Set 1 part 15 should be Done, got {parts_dict['TEST_UNASSY_15_A']}"
            assert parts_dict['TEST_UNASSY_15_B'] == 'RETURNED', f"Set 2 part 15 should be RETURNED, got {parts_dict['TEST_UNASSY_15_B']}"
            assert parts_dict['TEST_UNASSY_15_C'] == 'RETURNED', f"Set 3 part 15 should be RETURNED, got {parts_dict['TEST_UNASSY_15_C']}"
            print("[7 OK] Parts table correctly distinguishes 'Done' (assembled) vs 'RETURNED' (unassembled)!")

    # 6. Test GET /api/gudang/returned-trays
    code, res_returned_trays = request(f"/api/gudang/returned-trays?q={urllib.parse.quote(tray_code)}", "GET", token=token_wh)
    assert code == 200 and res_returned_trays.get("ok"), f"Failed get returned-trays: {res_returned_trays}"
    returned_list = res_returned_trays.get("data", [])
    matched_tray = next((t for t in returned_list if t['id'] == tray_id), None)
    assert matched_tray is not None, f"Tray {tray_id} not found in returned trays"
    assert matched_tray['finished_count'] == 1, f"Expected finished_count == 1, got {matched_tray['finished_count']}"
    assert matched_tray['total_sets'] == 2, f"Expected total_sets == 2 (only unassembled), got {matched_tray['total_sets']}"
    assert matched_tray['total_parts'] == 6, f"Expected total_parts == 6 (unassembled parts), got {matched_tray['total_parts']}"
    print(f"[8 OK] GET /api/gudang/returned-trays shows finished_count=1 and total_sets=2 (unassembled only)!")

    # 7. Test GET /api/gudang/returned-tray-detail
    code, res_detail = request(f"/api/gudang/returned-tray-detail?tray_id={tray_id}", "GET", token=token_wh)
    assert code == 200 and res_detail.get("ok"), f"Failed get returned-tray-detail: {res_detail}"
    sets = res_detail.get("sets", [])
    assert len(sets) == 2, f"Expected 2 sets in return detail modal, got {len(sets)}"
    assert res_detail.get("finished_count") == 1, f"Expected finished_count=1 in detail response, got {res_detail.get('finished_count')}"
    # Verify that Set 1 is NOT among the returned sets list
    returned_p15_sns = [s['p15_sn'] for s in sets]
    assert 'TEST_UNASSY_15_A' not in returned_p15_sns, "Finished Set 1 should NOT be in the returned sets to verify!"
    assert 'TEST_UNASSY_15_B' in returned_p15_sns and 'TEST_UNASSY_15_C' in returned_p15_sns
    print(f"[9 OK] GET /api/gudang/returned-tray-detail contains ONLY unassembled Sets B and C (total 2 sets)!")

    # 8. Gudang verifies the returned tray via /api/gudang/verify-return-tray
    code, res_verify = request("/api/gudang/verify-return-tray", "POST", {"tray_id": tray_id}, token=token_wh)
    assert code == 200 and res_verify.get("ok"), f"Failed verify return tray: {res_verify}"
    print(f"[10 OK] /api/gudang/verify-return-tray executed successfully.")

    # Check that Set 1 is STILL 'Done' after tray verification!
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT status FROM master_stocks WHERE sn = 'TEST_UNASSY_15_A'")
            assert cur.fetchone()['status'] == 'Done', "Set 1 must STILL be 'Done' even after return verification!"

            cur.execute("SELECT status FROM parts WHERE sn = 'TEST_UNASSY_15_A' AND tray_id = %s", (tray_id,))
            assert cur.fetchone()['status'] == 'Done', "Set 1 part must STILL have status 'Done' in parts table!"

            cur.execute("SELECT return_verified FROM trays WHERE id = %s", (tray_id,))
            assert cur.fetchone()['return_verified'] is True, "Tray return_verified should be True"
            print("[11 OK] Assembled Set 1 completely preserved as 'Done' after Gudang verification!")

    # 9. Test EDGE CASE: Try to return a tray where 100% of sets are already assembled
    # Let's create a 1-set tray, assemble it, and attempt RETURN_WAREHOUSE
    edge_sns = [('15', 'TEST_EDGE_15'), ('1', 'TEST_EDGE_01'), ('12', 'TEST_EDGE_12')]
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in edge_sns:
                cur.execute("DELETE FROM parts WHERE sn = %s", (sn,))
                cur.execute("DELETE FROM master_stocks WHERE sn = %s", (sn,))
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Tersedia')", (pt, sn))
            conn.commit()

    code, res_edge_tray = request("/api/gudang/save", "POST", {
        "pic_warehouse": "Granada",
        "assigned_operator": "Arini Salsabila",
        "source_document_mo": "MO-TEST-EDGE",
        "product_type": "Nanggala P9",
        "parts": [{"part_type": pt, "sn": sn} for pt, sn in edge_sns]
    }, token=token_wh)
    edge_tray_id = res_edge_tray["data"]["tray_id"]
    edge_tray_code = res_edge_tray["data"]["tray_code"]

    code, res_edge_assy = request("/api/assembly/save", "POST", {
        "tray_id": edge_tray_id,
        "set_number": 1,
        "operator": "Operator Test",
        "part15": "TEST_EDGE_15",
        "part12": "TEST_EDGE_12",
        "part1": "TEST_EDGE_01",
        "status": "Good"
    }, token=token_assy)
    assert code == 200 and res_edge_assy.get("ok")

    # Now attempt RETURN_WAREHOUSE on fully assembled tray
    code, res_edge_ret = request("/api/assembly/session-action", "POST", {
        "tray_id": edge_tray_id,
        "tray_code": edge_tray_code,
        "action": "RETURN_WAREHOUSE",
        "reason": "Test return on fully assembled tray"
    }, token=token_assy)
    assert code == 400, f"Expected 400 Bad Request, got {code}: {res_edge_ret}"
    assert "sudah selesai dirakit" in res_edge_ret.get("error", ""), f"Expected informative error, got {res_edge_ret}"
    print(f"[12 OK] Return of 100% assembled tray correctly rejected with 400 error: {res_edge_ret.get('error')}")

    # 13. Test SET-BY-SET VERIFICATION (/api/gudang/verify-return-set)
    # Create 3-set tray, assemble Set 1, return, then verify Set 2 and Set 3 one by one
    setby_sns = [
        ('15', 'TEST_SETBY_15_A'), ('1', 'TEST_SETBY_01_A'), ('12', 'TEST_SETBY_12_A'),
        ('15', 'TEST_SETBY_15_B'), ('1', 'TEST_SETBY_01_B'), ('12', 'TEST_SETBY_12_B'),
        ('15', 'TEST_SETBY_15_C'), ('1', 'TEST_SETBY_01_C'), ('12', 'TEST_SETBY_12_C')
    ]
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in setby_sns:
                cur.execute("DELETE FROM parts WHERE sn = %s", (sn,))
                cur.execute("DELETE FROM master_stocks WHERE sn = %s", (sn,))
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Tersedia')", (pt, sn))
            conn.commit()

    code, res_sb_tray = request("/api/gudang/save", "POST", {
        "pic_warehouse": "Granada",
        "assigned_operator": "Arini Salsabila",
        "source_document_mo": "MO-TEST-SETBY",
        "product_type": "Nanggala P9",
        "parts": [{"part_type": pt, "sn": sn} for pt, sn in setby_sns]
    }, token=token_wh)
    sb_tray_id = res_sb_tray["data"]["tray_id"]
    sb_tray_code = res_sb_tray["data"]["tray_code"]

    # Assemble Set 1
    code, res_sb_assy = request("/api/assembly/save", "POST", {
        "tray_id": sb_tray_id,
        "set_number": 1,
        "operator": "Operator Test",
        "part15": "TEST_SETBY_15_A",
        "part12": "TEST_SETBY_12_A",
        "part1": "TEST_SETBY_01_A",
        "status": "Good"
    }, token=token_assy)
    assert code == 200 and res_sb_assy.get("ok")

    # Return to warehouse
    code, res_sb_ret = request("/api/assembly/session-action", "POST", {
        "tray_id": sb_tray_id,
        "tray_code": sb_tray_code,
        "action": "RETURN_WAREHOUSE",
        "reason": "Test set-by-set return verification"
    }, token=token_assy)
    assert code == 200 and res_sb_ret.get("ok")

    # Verify Set 2 first
    code, res_v2 = request("/api/gudang/verify-return-set", "POST", {
        "tray_id": sb_tray_id,
        "set_number": 2,
        "p15_sn": "TEST_SETBY_15_B",
        "p1_sn": "TEST_SETBY_01_B",
        "p12_sn": "TEST_SETBY_12_B"
    }, token=token_wh)
    assert code == 200 and res_v2.get("ok")
    assert res_v2.get("is_fully_verified") is False, "Set 3 is not yet verified, so is_fully_verified must be False"

    # Verify Set 3 next
    code, res_v3 = request("/api/gudang/verify-return-set", "POST", {
        "tray_id": sb_tray_id,
        "set_number": 3,
        "p15_sn": "TEST_SETBY_15_C",
        "p1_sn": "TEST_SETBY_01_C",
        "p12_sn": "TEST_SETBY_12_C"
    }, token=token_wh)
    assert code == 200 and res_v3.get("ok")
    assert res_v3.get("is_fully_verified") is True, "All returned parts now verified, is_fully_verified must be True"

    with get_conn() as conn:
        with conn.cursor() as cur:
            # Check tray is marked return_verified = True
            cur.execute("SELECT return_verified FROM trays WHERE id = %s", (sb_tray_id,))
            assert cur.fetchone()['return_verified'] is True, "Tray return_verified must be True"

            # Check Set 1 is STILL 'Done'
            cur.execute("SELECT status FROM master_stocks WHERE sn = 'TEST_SETBY_15_A'")
            assert cur.fetchone()['status'] == 'Done', "Set 1 must remain 'Done'"
            cur.execute("SELECT COUNT(*) as c FROM assemblies WHERE tray_id = %s", (sb_tray_id,))
            assert cur.fetchone()['c'] == 1, "Assembly record for Set 1 must remain intact"
    print("[13 OK] Set-by-set verification correctly completed and verified tray without altering assembled sets!")

    # Cleanup test records
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in test_sns + edge_sns + setby_sns:
                cur.execute("DELETE FROM assembly_tests WHERE assembly_id IN (SELECT id FROM assemblies WHERE part15_sn = %s)", (sn,))
                cur.execute("DELETE FROM assemblies WHERE part15_sn = %s", (sn,))
                cur.execute("DELETE FROM parts WHERE sn = %s", (sn,))
                cur.execute("DELETE FROM master_stocks WHERE sn = %s", (sn,))
            cur.execute("DELETE FROM trays WHERE id IN (%s, %s, %s)", (tray_id, edge_tray_id, sb_tray_id))
            conn.commit()

    print("\n========================================================")
    print("ALL TESTS FOR RETURN-ONLY-UNASSEMBLED-SETS PASSED 100%!")
    print("========================================================")

if __name__ == '__main__':
    run_tests()
