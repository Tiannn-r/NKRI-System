import requests
import json
import psycopg2
from psycopg2.extras import RealDictCursor

BASE_URL = "http://localhost:8000/api"

DB_URL = "postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db"

def get_db():
    return psycopg2.connect(DB_URL, cursor_factory=RealDictCursor)

def test_hold_flow_and_admin_verification():
    print("Testing Hold Test Flow and Admin Verification...")
    
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT username, password, role FROM users WHERE role = 'admin' LIMIT 1")
            admin_u = cur.fetchone()
            cur.execute("SELECT username, password, role FROM users WHERE role = 'assembly' LIMIT 1")
            assy_u = cur.fetchone()

    # 1. Login as Admin
    res = requests.post(f"{BASE_URL}/login", json={"username": admin_u["username"], "password": admin_u["password"], "force_takeover": True})
    assert res.status_code == 200, f"Admin login failed: {res.text}"
    admin_token = res.json()["token"]
    headers = {"Authorization": f"Bearer {admin_token}", "Content-Type": "application/json"}

    # 2. Login as Operator Assembly
    res = requests.post(f"{BASE_URL}/login", json={"username": assy_u["username"], "password": assy_u["password"], "force_takeover": True})
    assert res.status_code == 200, f"Assembly login failed: {res.text}"
    assy_token = res.json()["token"]
    assy_headers = {"Authorization": f"Bearer {assy_token}", "Content-Type": "application/json"}

    import time
    t_code = f"TEST-HOLD-{int(time.time())}"
    # 3. Create a tray in DB with 1 completed set
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, session_status, created_at)
                VALUES (%s, 'warehouse', %s, 'active', NOW())
                RETURNING id
            """, (t_code, assy_u['username']))
            tray_id = cur.fetchone()['id']

            cur.execute("""
                INSERT INTO master_stocks (part_type, sn, cs, status, allocated_tray_id)
                VALUES ('15', %s, 'CS1', 'Supplied', %s),
                       ('1', %s, 'CS1', 'Supplied', %s),
                       ('12', %s, 'CS1', 'Supplied', %s)
            """, (f"HOLD-SN-{tray_id}", tray_id, f"HOLD-P1-{tray_id}", tray_id, f"HOLD-P12-{tray_id}", tray_id))

            cur.execute("""
                INSERT INTO parts (tray_id, part_type, sn, status)
                VALUES (%s, '15', %s, 'Good'),
                       (%s, '1', %s, 'Good'),
                       (%s, '12', %s, 'Good')
            """, (tray_id, f"HOLD-SN-{tray_id}", tray_id, f"HOLD-P1-{tray_id}", tray_id, f"HOLD-P12-{tray_id}"))

            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part12_sn, part1_sn, status, pic_assembly, started_at, finished_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'assembly', NOW(), NOW())
                RETURNING id
            """, (tray_id, f"HOLD-SN-{tray_id}", f"HOLD-P12-{tray_id}", f"HOLD-P1-{tray_id}"))
            assembly_id = cur.fetchone()['id']
            conn.commit()

    print(f"[OK] Test Tray {tray_id} and Assembly {assembly_id} created.")

    # 4. Assembly Operator submits test checklist with FAILURE (Not Good)
    test_payload = {
        "assembly_id": assembly_id,
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": f"HOLD-SN-{tray_id}",
        "part12_sn": f"HOLD-P12-{tray_id}",
        "part1_sn": f"HOLD-P1-{tray_id}",
        "test_checklist": [
            {"test_num": 1, "name": "Uji Kelistrikan & Daya", "status": "Good"},
            {"test_num": 2, "name": "Uji Sensor & Input", "status": "Not Good"},
            {"test_num": 3, "name": "Uji Mekanikal Komponen", "status": "Good"},
            {"test_num": 4, "name": "Uji Komunikasi Data", "status": "Not Good"},
            {"test_num": 5, "name": "Uji Kalibrasi & Keselarasan", "status": "Good"}
        ],
        "notes": "Sensor gagal respon"
    }

    res = requests.post(f"{BASE_URL}/assembly/submit-test", json=test_payload, headers=assy_headers)
    assert res.status_code == 200, f"Submit test failed: {res.text}"
    body = res.json()
    assert body["status"] == "Hold", f"Expected Hold status, got {body['status']}"
    assert "gagal dalam test 2" in body["failure_reasons"]
    print(f"[OK] Submit test returned Hold with failure reasons: {body['failure_reasons']}")

    # 5. Check tray slots to ensure item is NOT lost from tray query
    res = requests.get(f"{BASE_URL}/assembly/tray?id={tray_id}", headers=assy_headers)
    assert res.status_code == 200, f"Get tray failed: {res.text}"
    tray_data = res.json()["data"]
    assy_item = next((a for a in tray_data["assemblies"] if a["id"] == assembly_id), None)
    assert assy_item is not None, "Assembly disappeared from tray assemblies!"
    assert assy_item["status"] == "Hold"
    assert assy_item["qc_status"] == "HOLD_ASSY"
    print("[OK] Assembly correctly retained in tray with status=Hold & qc_status=HOLD_ASSY.")

    # 6. Check Admin Pending Verifications
    res = requests.get(f"{BASE_URL}/admin/pending-assemblies", headers=headers)
    assert res.status_code == 200
    pending_list = res.json()["data"]
    pending_item = next((p for p in pending_list if p["assembly_id"] == assembly_id), None)
    assert pending_item is not None, "Hold item not found in Admin pending assemblies queue!"
    print(f"[OK] Item found in Admin Pending Assemblies queue: Set #{pending_item['set_number']}, SN {pending_item['part15_sn']}")

    # 7. Admin Approves to Good (Lanjut ke QC)
    res = requests.post(f"{BASE_URL}/admin/approve-hold-item", json={
        "assembly_id": assembly_id,
        "action": "APPROVE_GOOD",
        "notes": "Disetujui Admin setelah cek sensor fisik"
    }, headers=headers)
    assert res.status_code == 200, f"Approve good failed: {res.text}"
    print(f"[OK] Admin approved item: {res.json()['message']}")

    # 8. Check that item is now in QC Pending Items
    res = requests.get(f"{BASE_URL}/qc/pending-items", headers=headers)
    assert res.status_code == 200
    qc_pending = res.json()["data"]
    qc_item = next((q for q in qc_pending if q["assembly_id"] == assembly_id), None)
    assert qc_item is not None, "Approved item not found in QC pending items list!"
    assert qc_item["assembly_status"] == "Good"
    assert qc_item["assy_doc_number"] is not None
    print(f"[OK] Item successfully forwarded to QC Pending queue with Doc No: {qc_item['assy_doc_number']}")

    # 9. Clean up test records
    with get_db() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM assembly_tests WHERE tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM assemblies WHERE tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM parts WHERE tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM master_stocks WHERE allocated_tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM trays WHERE id = %s", (tray_id,))
            conn.commit()

    print("\nALL HOLD FLOW & ADMIN VERIFICATION TESTS PASSED SUCCESSFULLY!")

if __name__ == "__main__":
    test_hold_flow_and_admin_verification()
