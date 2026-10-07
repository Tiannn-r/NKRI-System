import os, sys, urllib.request, json, uuid
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import get_conn

def test_visibility():
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, username, role, full_name FROM users WHERE username = 'admin'")
            admin = cur.fetchone()
            token = str(uuid.uuid4())
            cur.execute(
                "INSERT INTO user_sessions (token, user_id, username, created_at, last_activity) VALUES (%s, %s, %s, NOW(), NOW())",
                (token, admin['id'], admin['username'])
            )
            conn.commit()

    # 1. Test /api/assembly/trays as Admin
    req = urllib.request.Request('http://localhost:8000/api/assembly/trays', headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req) as resp:
        data = json.loads(resp.read().decode())
        tray1 = next((t for t in data.get('data', []) if t['tray_code'] == 'WH/ASSY/000001'), None)
        assert tray1 is not None, "WH/ASSY/000001 must appear in /api/assembly/trays"
        assert tray1['session_status'] == 'RETURNED_TO_WAREHOUSE', f"Unexpected status: {tray1['session_status']}"
        assert tray1['finished_count'] == 9, f"Unexpected finished_count: {tray1['finished_count']}"
        print("[OK] /api/assembly/trays returns WH/ASSY/000001 with session_status='RETURNED_TO_WAREHOUSE' and finished_count=9")

    # 2. Test /api/assembly/tray?code=WH/ASSY/000001
    req2 = urllib.request.Request('http://localhost:8000/api/assembly/tray?code=WH/ASSY/000001', headers={'Authorization': f'Bearer {token}'})
    with urllib.request.urlopen(req2) as resp2:
        detail = json.loads(resp2.read().decode())
        tray_info = detail['data']['tray']
        assemblies = detail['data']['assemblies']
        parts = detail['data']['parts']

        assert tray_info['session_status'] == 'RETURNED_TO_WAREHOUSE', f"Tray session_status should be RETURNED_TO_WAREHOUSE, got: {tray_info.get('session_status')}"
        assert len(assemblies) == 9, f"Expected 9 finished assemblies, got: {len(assemblies)}"
        assert len(parts) == 30, f"Expected 30 parts, got: {len(parts)}"
        returned_parts = [p for p in parts if p.get('status') == 'RETURNED']
        assert len(returned_parts) == 3, f"Expected 3 RETURNED parts for set 10, got: {len(returned_parts)}"
        print("[OK] /api/assembly/tray returns full detail: session_status, 9 assemblies, 3 returned parts for slot 10")

    # 3. Test for Operator Arini
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, username, role, full_name FROM users WHERE username = 'Arini'")
            arini = cur.fetchone()
            if arini:
                token_arini = str(uuid.uuid4())
                cur.execute(
                    "INSERT INTO user_sessions (token, user_id, username, created_at, last_activity) VALUES (%s, %s, %s, NOW(), NOW())",
                    (token_arini, arini['id'], arini['username'])
                )
                conn.commit()
                req_arini = urllib.request.Request('http://localhost:8000/api/assembly/trays', headers={'Authorization': f'Bearer {token_arini}'})
                with urllib.request.urlopen(req_arini) as r_a:
                    d_a = json.loads(r_a.read().decode())
                    tray_arini = next((t for t in d_a.get('data', []) if t['tray_code'] == 'WH/ASSY/000001'), None)
                    assert tray_arini is not None, "Tray WH/ASSY/000001 must appear in Operator Arini trays list"
                    assert tray_arini['finished_count'] == 9
                    print("[OK] Operator Arini can also access WH/ASSY/000001 with 9 finished sets")

    print("\nALL VERIFICATIONS PASSED SUCCESSFULLY!")

if __name__ == '__main__':
    test_visibility()
