import json
import urllib.request
import urllib.error
import sys, os
import time
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

def run_test():
    print("=========================================================================")
    print("TEST: DECLINED ITEM ENTERS ADMIN VERIFICATION (KHUSUS DECLINED) & RE-REG")
    print("=========================================================================")

    # 1. Login as Admin
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Login failed for admin: {res}"
    admin_token = res["token"]
    print("[1 OK] Admin login successful.")

    ts = int(time.time())
    sn_p15 = f"P15-DEC-{ts}"
    sn_p1 = f"P1-DEC-{ts}"
    sn_p12 = f"P12-DEC-{ts}"
    tray_code = f"TRAY-DEC-{ts}"

    # 2. Insert into DB: Master stocks, Tray, and Assembly with QC GOOD
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in [('15', sn_p15), ('1', sn_p1), ('12', sn_p12)]:
                cur.execute("""
                    INSERT INTO master_stocks (part_type, sn, status, created_at)
                    VALUES (%s, %s, 'Digunakan', NOW())
                """, (pt, sn))

            cur.execute("""
                INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, product_type, session_status, verified_by_gudang)
                VALUES (%s, 'admin', 'Sopi', 'Nanggala P9', 'closed', FALSE)
                RETURNING id
            """, (tray_code,))
            tray_id = cur.fetchone()['id']

            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, pic_assembly, part15_sn, part1_sn, part12_sn, status, qc_status, warehouse_receipt_status)
                VALUES (%s, 1, 'Sopi', %s, %s, %s, 'Good', 'GOOD', 'PENDING')
                RETURNING id
            """, (tray_id, sn_p15, sn_p1, sn_p12))
            assembly_id = cur.fetchone()['id']
            conn.commit()

    print(f"[2 OK] Seeded Tray {tray_id}, Assembly {assembly_id} with SNs: {sn_p15}, {sn_p1}, {sn_p12}")

    # 3. Warehouse verifies QC physical receipt and DECLINES this unit!
    print(f"[3] Gudang physically declines assembly #{assembly_id}...")
    st, res = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "assembly_id": assembly_id,
        "action": "DECLINED",
        "notes": "Cacat bodi fisik ditemukan saat verifikasi penerimaan gudang"
    }, token=admin_token)
    print("Gudang verify-qc-receipt response:", res)
    assert res.get("ok"), f"Decline failed: {res}"
    assert res.get("action") == "DECLINED"
    print("[3 OK] Assembly declined successfully.")

    # 4. Check DB status of assembly
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM assemblies WHERE id = %s", (assembly_id,))
            assy_row = cur.fetchone()
            assert assy_row['warehouse_receipt_status'] == 'DECLINED'
            assert assy_row['is_declined'] is True
            assert assy_row['verified_status'] == 'DECLINED'
            assert assy_row['status'] == 'Declined'
    print("[4 OK] Assembly DB record correctly updated to status 'Declined', is_declined=True, verified_status='DECLINED'.")

    # 5. Check master_stocks status for constituent parts
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in [('15', sn_p15), ('1', sn_p1), ('12', sn_p12)]:
                cur.execute("SELECT status FROM master_stocks WHERE part_type = %s AND sn = %s", (pt, sn))
                row = cur.fetchone()
                assert row is not None, f"Part {sn} not found in master stocks"
                assert row['status'] == 'Declined', f"Part {sn} status should be 'Declined', got '{row['status']}'"
    print("[5 OK] All 3 constituent parts in master_stocks updated to status 'Declined'.")

    # 6. Check Admin pending assemblies endpoint (/api/admin/pending-assemblies)
    print("[6] Checking Admin pending assemblies endpoint...")
    st, res = make_req("/api/admin/pending-assemblies", "GET", token=admin_token)
    assert res.get("ok"), f"Get pending assemblies failed: {res}"
    data = res.get("data", [])

    declined_item = next((it for it in data if it["assembly_id"] == assembly_id), None)
    assert declined_item is not None, f"Assembly #{assembly_id} NOT found in Admin pending assemblies! Data: {data}"
    assert declined_item["is_declined"] is True, f"Expected is_declined=True, got {declined_item['is_declined']}"
    assert declined_item["verified_status"] == "DECLINED", f"Expected verified_status=DECLINED, got {declined_item['verified_status']}"
    assert declined_item["warehouse_receipt_status"] == "DECLINED", f"Expected warehouse_receipt_status=DECLINED, got {declined_item['warehouse_receipt_status']}"
    print(f"[6 OK] Assembly #{assembly_id} correctly returned in Admin pending assemblies under 'Khusus Declined' queue!")

    # 7. Admin verifies parts back to 'Tersedia' (via /api/admin/approve-part-direct)
    print("[7] Admin verifies each part back to 'Tersedia'...")
    for pt, sn in [('15', sn_p15), ('1', sn_p1), ('12', sn_p12)]:
        st, res = make_req("/api/admin/approve-part-direct", "POST", {
            "part_type": pt,
            "sn": sn,
            "target_status": "Tersedia",
            "notes": f"Admin verifikasi fisik pulihkan {sn} untuk didaftarkan ulang"
        }, token=admin_token)
        assert res.get("ok"), f"Admin approval failed for {pt} {sn}: {res}"
    print("[7 OK] All 3 parts verified & approved by Admin to 'Tersedia'.")

    # 8. Check that parts in master_stocks are now 'Tersedia' and have allocated_tray_id = NULL
    with get_conn() as conn:
        with conn.cursor() as cur:
            for pt, sn in [('15', sn_p15), ('1', sn_p1), ('12', sn_p12)]:
                cur.execute("SELECT status, allocated_tray_id FROM master_stocks WHERE part_type = %s AND sn = %s", (pt, sn))
                row = cur.fetchone()
                assert row['status'] == 'Tersedia', f"Expected status 'Tersedia', got '{row['status']}'"
                assert row['allocated_tray_id'] is None, f"Expected allocated_tray_id None, got {row['allocated_tray_id']}"
    print("[8 OK] master_stocks records are 'Tersedia' and tray allocation released.")

    # 9. Check that the assembly in DB is marked as ACC / verified
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT is_declined, verified_status, is_reregistered FROM assemblies WHERE id = %s", (assembly_id,))
            assy_row = cur.fetchone()
            assert assy_row['is_declined'] is False, "is_declined should be False after full admin verify"
            assert assy_row['verified_status'] == 'ACC', "verified_status should be 'ACC'"
            assert assy_row['is_reregistered'] is True, "is_reregistered should be True"
    print("[9 OK] Assembly marked verified: is_declined=False, verified_status='ACC', is_reregistered=True.")

    # 10. Re-register these parts into a NEW tray via Gudang input/save
    print("[10] Re-registering these verified parts into a NEW tray...")
    st, res = make_req("/api/gudang/save", "POST", {
        "pic_warehouse": "admin",
        "assigned_operator": "Sopi",
        "source_document_mo": f"MO-REREG-{ts}",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": sn_p15},
            {"part_type": "1", "sn": sn_p1},
            {"part_type": "12", "sn": sn_p12}
        ]
    }, token=admin_token)
    assert res.get("ok"), f"Failed to re-register parts into new tray: {res}"
    new_tray = res["data"]
    print(f"[10 OK] Successfully re-registered parts into new tray {new_tray['tray_code']} (ID #{new_tray['id']})!")

    print("\n=========================================================================")
    print(">>> 100% PASS: DECLINED -> ADMIN VERIF -> DAFTAR ULANG KEMBALI WORKS! <<<")
    print("=========================================================================")

if __name__ == "__main__":
    run_test()
