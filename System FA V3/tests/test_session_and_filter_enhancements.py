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
    port = 52899
    server = app.ThreadingHTTPServer(('127.0.0.1', port), app.Handler)
    server_thread = threading.Thread(target=server.serve_forever, daemon=True)
    server_thread.start()
    time.sleep(1)
    base_url = f"http://127.0.0.1:{port}"
    print(f"Test server started at {base_url}")

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

    # Setup users & clear old test sessions
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM user_sessions")
            conn.commit()

    # Login Admin, Gudang, and 2 Operators (Arini & Irsa)
    code, res_admin = request("/api/login", "POST", {"username": "admin", "password": "admin123"})
    assert code == 200 and res_admin.get("ok"), f"Admin login failed: {res_admin}"
    token_admin = res_admin["token"]

    # Ensure Operator Arini and Operator Irsa exist
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO users (username, password, full_name, nik, role)
                VALUES ('arini', '123', 'Arini Salsabila', 'NIK-ARINI-01', 'assembly')
                ON CONFLICT (username) DO UPDATE SET password = '123', full_name = 'Arini Salsabila', role = 'assembly'
            """)
            cur.execute("""
                INSERT INTO users (username, password, full_name, nik, role)
                VALUES ('irsa', '123', 'Irsa Nanda', 'NIK-IRSA-01', 'assembly')
                ON CONFLICT (username) DO UPDATE SET password = '123', full_name = 'Irsa Nanda', role = 'assembly'
            """)
            conn.commit()

    code, res_arini = request("/api/login", "POST", {"username": "arini", "password": "123"})
    assert code == 200 and res_arini.get("ok"), f"Arini login failed: {res_arini}"
    token_arini = res_arini["token"]

    code, res_irsa = request("/api/login", "POST", {"username": "irsa", "password": "123"})
    token_irsa = res_irsa["token"]

    code, res_wh = request("/api/login", "POST", {"username": "Granada", "password": "Whs01"})
    token_wh = res_wh["token"]

    print("\n--- TEST 1: Return to Warehouse (Kembalikan ke Gudang) ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '1' AND status = 'Tersedia' ORDER BY id LIMIT 1")
            sn_p1 = cur.fetchone()['sn']
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '12' AND status = 'Tersedia' ORDER BY id LIMIT 1")
            sn_p12 = cur.fetchone()['sn']
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '15' AND status = 'Tersedia' ORDER BY id LIMIT 1")
            sn_p15 = cur.fetchone()['sn']

    code, save_wh = request("/api/gudang/save", "POST", {
        "pic_warehouse": "Granada",
        "assigned_operator": "Arini Salsabila",
        "source_document_mo": "MO-RET-01",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "1", "sn": sn_p1},
            {"part_type": "12", "sn": sn_p12},
            {"part_type": "15", "sn": sn_p15}
        ]
    }, token=token_wh)
    assert code == 200 and save_wh.get("ok"), f"Gudang save tray failed: {save_wh}"
    ret_tray_id = save_wh["data"]["tray_id"]
    ret_tray_code = save_wh["data"]["tray_code"]

    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT status, allocated_tray_id FROM master_stocks WHERE sn = %s AND part_type = '15'", (sn_p15,))
            row = cur.fetchone()
            print(f"DEBUG row: {row}, ret_tray_id: {ret_tray_id}")
            assert row['status'] == 'Supplied' and row['allocated_tray_id'] == ret_tray_id, f"Expected Supplied, got {row}"

    code, ret_res = request("/api/assembly/session-action", "POST", {
        "tray_id": ret_tray_id,
        "tray_code": ret_tray_code,
        "action": "RETURN_WAREHOUSE",
        "reason": "Test Pengembalian Part ke Gudang"
    }, token=token_arini)
    assert code == 200 and ret_res.get("ok"), f"Return warehouse failed: {ret_res}"
    print(f"Session RETURN_WAREHOUSE executed: {ret_res.get('message')}")

    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT status, allocated_tray_id FROM master_stocks WHERE sn = %s AND part_type = '15'", (sn_p15,))
            row = cur.fetchone()
            assert row['status'] == 'Tersedia' and row['allocated_tray_id'] is None, f"Stock status not Tersedia: {row}"
            
            cur.execute("SELECT COUNT(*) as c FROM assemblies WHERE tray_id = %s", (ret_tray_id,))
            assert cur.fetchone()['c'] == 0, "Assemblies were not deleted on return"
            
            cur.execute("SELECT COUNT(*) as c FROM parts WHERE tray_id = %s AND status = 'RETURNED'", (ret_tray_id,))
            assert cur.fetchone()['c'] == 3, "Parts status should be RETURNED for inspection in Pengembalian Barang"
            
            cur.execute("SELECT session_status, return_notified FROM trays WHERE id = %s", (ret_tray_id,))
            t_row = cur.fetchone()
            assert t_row['session_status'] == 'RETURNED_TO_WAREHOUSE' and t_row['return_notified'] is True

    code, trays_res = request("/api/assembly/trays", "GET", token=token_arini)
    assert code == 200
    assert not any(t['tray_code'] == ret_tray_code for t in trays_res.get('data', [])), "Returned tray still appeared in assembly trays!"
    print("VERIFIED: Returned tray stock is 'Tersedia' and tray is excluded from Assembly trays list.")

    print("\n--- TEST 2: Handover Shift (Pindahkan Sisa Rakitan yang Belum Dirakit) ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '1' AND status = 'Tersedia' ORDER BY id LIMIT 2")
            p1_rows = cur.fetchall()
            p1_a, p1_b = p1_rows[0]['sn'], p1_rows[1]['sn']
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '12' AND status = 'Tersedia' ORDER BY id LIMIT 2")
            p12_rows = cur.fetchall()
            p12_a, p12_b = p12_rows[0]['sn'], p12_rows[1]['sn']
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '15' AND status = 'Tersedia' ORDER BY id LIMIT 2")
            p15_rows = cur.fetchall()
            p15_a, p15_b = p15_rows[0]['sn'], p15_rows[1]['sn']

    code, save_wh2 = request("/api/gudang/save", "POST", {
        "pic_warehouse": "Granada",
        "assigned_operator": "Arini Salsabila",
        "source_document_mo": "MO-SHIFT-01",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "1", "sn": p1_a},
            {"part_type": "12", "sn": p12_a},
            {"part_type": "15", "sn": p15_a},
            {"part_type": "1", "sn": p1_b},
            {"part_type": "12", "sn": p12_b},
            {"part_type": "15", "sn": p15_b}
        ]
    }, token=token_wh)
    assert code == 200 and save_wh2.get("ok")
    tray1_id = save_wh2["data"]["tray_id"]
    tray1_code = save_wh2["data"]["tray_code"]

    # Arini finishes Set #1
    code, save_assy = request("/api/assembly/save", "POST", {
        "tray_id": tray1_id,
        "set_number": 1,
        "operator": "Arini Salsabila",
        "part15": p15_a,
        "part12": p12_a,
        "part1": p1_a,
        "status": "Good"
    }, token=token_arini)
    assert code == 200 and save_assy.get("ok"), f"Assembly save failed: {save_assy}"

    # Arini performs HANDOVER_SHIFT to Irsa Nanda (for remaining Set #2)
    code, handover_res = request("/api/assembly/session-action", "POST", {
        "tray_id": tray1_id,
        "tray_code": tray1_code,
        "action": "HANDOVER_SHIFT",
        "new_operator": "Irsa Nanda",
        "reason": "Pergantian shift sore"
    }, token=token_arini)
    assert code == 200 and handover_res.get("ok"), f"Handover failed: {handover_res}"
    print(f"Handover result: {handover_res.get('message')}")

    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, tray_code, assigned_operator, session_status FROM trays WHERE tray_code LIKE %s", (f"{tray1_code}%",))
            trays = cur.fetchall()
            assert len(trays) == 2, f"Expected 2 trays (original + continuation), got {trays}"
            
            orig_t = next(t for t in trays if t['id'] == tray1_id)
            new_t = next(t for t in trays if t['id'] != tray1_id)
            
            assert orig_t['session_status'] == 'COMPLETED_SHIFT'
            assert new_t['assigned_operator'] == 'Irsa Nanda'
            assert new_t['session_status'] == 'active'
            
            cur.execute("SELECT set_number, pic_assembly, part15_sn FROM assemblies WHERE tray_id = %s", (tray1_id,))
            orig_assemblies = cur.fetchall()
            assert len(orig_assemblies) == 1 and orig_assemblies[0]['part15_sn'] == p15_a

            cur.execute("SELECT part_type, sn FROM parts WHERE tray_id = %s", (new_t['id'],))
            new_parts = cur.fetchall()
            assert len(new_parts) == 3, f"Expected 3 parts in new tray, got {len(new_parts)}"
            assert any(p['sn'] == p15_b for p in new_parts)

    print("VERIFIED: Finished set stayed with Arini; unfinished set #2 moved to new tray for Irsa.")

    print("\n--- TEST 3: Strict Operator Filter in Laporan & Assembly Trays ---")
    code, save_irsa = request("/api/assembly/save", "POST", {
        "tray_id": new_t['id'],
        "set_number": 1,
        "operator": "Irsa Nanda",
        "part15": p15_b,
        "part12": p12_b,
        "part1": p1_b,
        "status": "Good"
    }, token=token_irsa)
    assert code == 200 and save_irsa.get("ok")

    # Filter Laporan by Arini
    code, lap_arini = request("/api/laporan?operator=Arini", "GET", token=token_admin)
    assert code == 200
    rows_arini = lap_arini.get("data", [])
    assert all("Arini" in (r['pic_assembly'] or '') for r in rows_arini), "Laporan Arini contained other operators!"
    assert any(r['part15_sn'] == p15_a for r in rows_arini), "Arini's assembly not found in Arini's report"
    assert not any(r['part15_sn'] == p15_b for r in rows_arini), "Irsa's assembly appeared in Arini's report!"

    # Filter Laporan by Irsa
    code, lap_irsa = request("/api/laporan?operator=Irsa", "GET", token=token_admin)
    assert code == 200
    rows_irsa = lap_irsa.get("data", [])
    assert all("Irsa" in (r['pic_assembly'] or '') for r in rows_irsa), "Laporan Irsa contained other operators!"
    assert any(r['part15_sn'] == p15_b for r in rows_irsa), "Irsa's assembly not found in Irsa's report"
    assert not any(r['part15_sn'] == p15_a for r in rows_irsa), "Arini's assembly appeared in Irsa's report!"

    print("VERIFIED: Laporan filter strictly isolates data per operator.")

    code, tr_irsa = request("/api/assembly/trays?operator=Irsa+Nanda", "GET", token=token_admin)
    assert code == 200
    for t in tr_irsa.get("data", []):
        assert "Irsa" in (t.get("assigned_operator") or ''), f"Unexpected tray in Irsa filter: {t}"

    print("VERIFIED: Assembly trays filter strictly isolates active trays per operator.")

    # Cleanup test created trays & restore stock status
    with get_conn() as conn:
        with conn.cursor() as cur:
            test_tray_ids = [ret_tray_id, tray1_id, new_t['id']]
            cur.execute("DELETE FROM assemblies WHERE tray_id = ANY(%s)", (test_tray_ids,))
            cur.execute("DELETE FROM parts WHERE tray_id = ANY(%s)", (test_tray_ids,))
            cur.execute("DELETE FROM trays WHERE id = ANY(%s)", (test_tray_ids,))
            test_sns = [sn_p1, sn_p12, sn_p15, p1_a, p12_a, p15_a, p1_b, p12_b, p15_b]
            cur.execute("UPDATE master_stocks SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL, used_at = NULL WHERE sn = ANY(%s)", (test_sns,))
            conn.commit()

    print("\nALL ENHANCEMENTS & USER REQUIREMENTS VALIDATED 100% SUCCESSFULLY!")
    server.shutdown()

if __name__ == "__main__":
    run_tests()
