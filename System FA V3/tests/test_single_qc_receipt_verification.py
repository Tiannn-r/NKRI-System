import json
import urllib.request
import urllib.error
import sys, os
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))
from app import get_conn

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

def test_single_qc_receipt_verification():
    print("================================================================")
    print("TESTING ONE-BY-ONE (SATU-SATU) QC WAREHOUSE RECEIPT VERIFICATION")
    print("================================================================")

    # 1. Login
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("[1] Authentication OK.")

    # 2. Verify static/index.html UI changes
    with open("static/index.html", "r", encoding="utf-8") as f:
        html = f.read()

    # Check that individual per-item buttons exist
    assert "verifySingleQCItem(it, 'ACCEPT')" in html, "Missing per-item ACC button in modal"
    assert "verifySingleQCItem(it, 'DECLINED')" in html, "Missing per-item Decline button in modal"
    assert "isSubmittingQCItem" in html, "Missing isSubmittingQCItem tracking"
    # Check that batch buttons at the bottom were removed
    assert "@click=\"submitQCWarehouseActualVerify('ACCEPT')\"" not in html, "Batch ACC button still present in modal footer"
    assert "@click=\"submitQCWarehouseActualVerify('DECLINED')\"" not in html, "Batch DECLINED button still present in modal footer"
    # Check that WH/QC table status checks item-level status
    assert "item.warehouse_receipt_status === 'ACCEPTED' || item.warehouse_receipt_status === 'ACC'" in html, "WH/QC table does not check item-level status"
    print("[2 OK] Frontend HTML verified: individual per-set buttons present, batch footer buttons removed, and WH/QC table checks item-level status.")

    # 3. Create a test tray with 3 good assemblies in DB
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, product_type, session_status, verified_by_gudang)
                VALUES ('TEST/ONEBYONE/01', 'admin', 'Sopi', 'Nanggala P9', 'closed', FALSE)
                RETURNING id
            """)
            tray_id = cur.fetchone()['id']

            assy_ids = []
            for i in range(1, 4):
                cur.execute("""
                    INSERT INTO assemblies (tray_id, set_number, pic_assembly, part15_sn, part1_sn, part12_sn, status, qc_status, warehouse_receipt_status)
                    VALUES (%s, %s, 'Sopi', %s, %s, %s, 'Good', 'GOOD', 'PENDING')
                    RETURNING id
                """, (tray_id, i, f"SN_TEST_{i}", f"PN1_{i}", f"PN12_{i}"))
                assy_ids.append(cur.fetchone()['id'])
            conn.commit()

    print(f"[3 OK] Created test tray {tray_id} with 3 assemblies: {assy_ids}")

    # 4. Verify Set #1 ACC
    st, res1 = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "assembly_id": assy_ids[0],
        "action": "ACCEPT"
    }, token=admin_token)
    assert st == 200 and res1.get("ok"), f"Failed to ACC set 1: {res1}"
    assert res1.get("tray_fully_verified") is False, "Tray should not be fully verified after 1 of 3"
    print("[4 OK] Set #1 verified ACC. Tray is not fully verified.")

    # Verify DB state after Set #1
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, warehouse_receipt_status FROM assemblies WHERE id = ANY(%s) ORDER BY id", (assy_ids,))
            statuses = {r['id']: r['warehouse_receipt_status'] for r in cur.fetchall()}
            assert statuses[assy_ids[0]] == "ACCEPTED", f"Expected ACCEPTED, got {statuses[assy_ids[0]]}"
            assert statuses[assy_ids[1]] == "PENDING", f"Expected PENDING, got {statuses[assy_ids[1]]}"
            assert statuses[assy_ids[2]] == "PENDING", f"Expected PENDING, got {statuses[assy_ids[2]]}"

            cur.execute("SELECT verified_by_gudang FROM trays WHERE id = %s", (tray_id,))
            assert cur.fetchone()['verified_by_gudang'] is False, "Tray should still be verified_by_gudang = False"
    print("[4b OK] DB confirms Set #1 is ACCEPTED while Set #2 and #3 remain PENDING.")

    # 5. Verify Set #2 DECLINED
    st, res2 = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "assembly_id": assy_ids[1],
        "action": "DECLINED",
        "notes": "Bodi baret parah"
    }, token=admin_token)
    assert st == 200 and res2.get("ok"), f"Failed to decline set 2: {res2}"
    assert res2.get("tray_fully_verified") is False, "Tray should not be fully verified after 2 of 3"
    print("[5 OK] Set #2 verified DECLINED. Tray is not fully verified.")

    # 6. Verify Set #3 ACC
    st, res3 = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "assembly_id": assy_ids[2],
        "action": "ACCEPT"
    }, token=admin_token)
    assert st == 200 and res3.get("ok"), f"Failed to ACC set 3: {res3}"
    assert res3.get("tray_fully_verified") is True, "Tray should now be fully verified (all 3 verified)"
    print("[6 OK] Set #3 verified ACC. Tray is now marked fully verified!")

    # Verify final DB state
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, warehouse_receipt_status, declined_reason FROM assemblies WHERE id = ANY(%s) ORDER BY id", (assy_ids,))
            rows = {r['id']: r for r in cur.fetchall()}
            assert rows[assy_ids[0]]['warehouse_receipt_status'] == "ACCEPTED"
            assert rows[assy_ids[1]]['warehouse_receipt_status'] == "DECLINED" and rows[assy_ids[1]]['declined_reason'] == "Bodi baret parah"
            assert rows[assy_ids[2]]['warehouse_receipt_status'] == "ACCEPTED"

            cur.execute("SELECT verified_by_gudang FROM trays WHERE id = %s", (tray_id,))
            assert cur.fetchone()['verified_by_gudang'] is True, "Tray should be verified_by_gudang = True"

            # Clean up test tray and assemblies
            cur.execute("DELETE FROM assemblies WHERE id = ANY(%s)", (assy_ids,))
            cur.execute("DELETE FROM trays WHERE id = %s", (tray_id,))
            conn.commit()

    print("[7 OK] Final DB state verified: Set 1 ACC, Set 2 DECLINED, Set 3 ACC. Cleaned up test data.")
    print("================================================================")
    print("ALL ONE-BY-ONE VERIFICATION TESTS PASSED SUCCESSFULLY (100%)!")
    print("================================================================")

if __name__ == "__main__":
    test_single_qc_receipt_verification()
