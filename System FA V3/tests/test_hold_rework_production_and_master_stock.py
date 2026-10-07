import json
import urllib.request
import urllib.parse
import sys
import os

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

BASE_URL = "http://localhost:8000"

def get_token(username="admin", password="admin123"):
    data = json.dumps({"username": username, "password": password, "force": True}).encode("utf-8")
    req = urllib.request.Request(f"{BASE_URL}/api/login", data=data, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req) as resp:
            body = json.loads(resp.read().decode("utf-8"))
            return body.get("token")
    except urllib.error.HTTPError as e:
        body = json.loads(e.read().decode("utf-8"))
        return body.get("token")

def api_get(path, token=None):
    headers = {}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    req = urllib.request.Request(f"{BASE_URL}{path}", headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode("utf-8"))

def api_post(path, data, token=None):
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data).encode("utf-8")
    req = urllib.request.Request(f"{BASE_URL}{path}", data=body, headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode("utf-8"))

def main():
    token = get_token("admin", "admin123")
    assert token, "Gagal login admin"
    print("[1] Login Admin sukses!")

    # Import get_conn directly to insert test records
    from app import get_conn

    # Cleanup any existing test data
    test_tray_code = "TRAY-TEST-REV"
    test_p15 = "SN-TEST-P15-REV"
    test_p1 = "SN-TEST-P1-REV"
    test_p12 = "SN-TEST-P12-REV"

    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM re_register_requests WHERE sn IN (%s, %s, %s)", (test_p15, test_p1, test_p12))
            cur.execute("DELETE FROM assemblies WHERE part15_sn = %s OR part1_sn = %s OR part12_sn = %s", (test_p15, test_p1, test_p12))
            cur.execute("DELETE FROM parts WHERE sn IN (%s, %s, %s)", (test_p15, test_p1, test_p12))
            cur.execute("DELETE FROM trays WHERE tray_code = %s", (test_tray_code,))
            cur.execute("DELETE FROM master_stocks WHERE sn IN (%s, %s, %s)", (test_p15, test_p1, test_p12))
            conn.commit()

            # Insert master stocks
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('15', %s, 'Hold')", (test_p15,))
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('1', %s, 'Good')", (test_p1,))
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('12', %s, 'Good')", (test_p12,))

            # Insert tray
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse)
                VALUES (%s, 'MO-REV-001', 'Nanggala P9', 'admin')
                RETURNING id
            """, (test_tray_code,))
            tray_id = cur.fetchone()['id']

            # Insert parts
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', %s, 'Hold')", (tray_id, test_p15))
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '1', %s, 'Good')", (tray_id, test_p1))
            cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '12', %s, 'Good')", (tray_id, test_p12))

            # Case A: Assembly with Hold during assembly (qc_status is NULL)
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, pic_assembly, part15_sn, part12_sn, part1_sn, status, reason, defective_parts, qc_status, verified_status)
                VALUES (%s, 1, 'admin', %s, %s, %s, 'Hold', 'Kendala saat perakitan baut seret', '15', NULL, NULL)
                RETURNING id
            """, (tray_id, test_p15, test_p12, test_p1))
            assy_id_perakitan = cur.fetchone()['id']
            conn.commit()

    print(f"[2] Test data perakitan dibuat: Assembly ID {assy_id_perakitan}")

    # TEST 1: Check /api/production/hold-items
    # Rakitan kendala saat perakitan TIDAK BOLEH masuk ke Barang Produksi
    prod_items = api_get("/api/production/hold-items", token)
    assert prod_items.get("ok"), "Gagal get production hold items"
    found_in_prod = any(item.get("assembly_id") == assy_id_perakitan for item in prod_items.get("data", []))
    assert not found_in_prod, "GAGAL: Rakitan saat perakitan (qc_status NULL) masih masuk ke Barang Produksi!"
    print("[PASS] Test 1: Rakitan dengan status Hold saat perakitan TIDAK masuk ke Barang Produksi.")

    # TEST 2: Check /api/master/get-connected-set untuk part dari perakitan
    conn_info = api_get(f"/api/master/get-connected-set?sn={test_p15}&part_type=15", token)
    assert conn_info.get("ok"), "Gagal get connected set"
    assert conn_info.get("is_connected") is False, "GAGAL: Part perakitan dianggap is_connected: True!"
    print("[PASS] Test 2: Part dari perakitan mengembalikan is_connected: False pada Master Stok.")

    # TEST 3: Daftar ulang part di Master Stok
    # Pendaftaran ulang part perakitan hanya mendaftarkan single part ke re_register_requests
    rereg_res = api_post("/api/master/re-register", {
        "part_type": "15",
        "sn": test_p15,
        "target_status": "Tersedia",
        "reason": "Part diperbaiki di meja stok"
    }, token)
    assert rereg_res.get("ok"), f"Gagal re-register part: {rereg_res}"
    assert not rereg_res.get("is_set"), "GAGAL: Re-register part perakitan malah didaftarkan sebagai set!"
    print("[PASS] Test 3: Pendaftaran ulang part di Master Stok berhasil per part (bukan set).")

    # TEST 4: Verifikasi di tabel Admin
    # Cek bahwa rakitan TIDAK masuk ke /api/admin/pending-assemblies ("Verifikasi Barang Rakitan")
    pending_assys = api_get("/api/admin/pending-assemblies", token)
    assert pending_assys.get("ok"), "Gagal get pending-assemblies"
    found_in_pending_assy = any(item.get("assembly_id") == assy_id_perakitan for item in pending_assys.get("data", []))
    assert not found_in_pending_assy, "GAGAL: Assembly masuk ke pending-assemblies Admin padahal hanya part yang didaftar ulang!"
    print("[PASS] Test 4: Rakitan TIDAK masuk ke Verifikasi Barang Rakitan Admin.")

    # Cek bahwa part masuk ke Verifikasi Part Admin
    verif_all = api_get("/api/admin/pending-verifications-all", token)
    assert verif_all.get("ok"), "Gagal get pending-verifications-all"
    req_row = next((r for r in verif_all.get("requests", []) if r.get("sn") == test_p15), None)
    assert req_row is not None, "GAGAL: Part tidak ditemukan di tabel Verifikasi Part Admin!"
    assert req_row.get("status") == "PENDING", "Status permohonan part bukan PENDING"
    print("[PASS] Test 5: Part perakitan masuk ke tabel Verifikasi Part Admin dengan status PENDING.")

    # TEST 5: Admin menyetujui permohonan part
    approve_res = api_post("/api/admin/approve-re-register", {
        "request_id": req_row["id"],
        "action": "APPROVE",
        "target_status": "Tersedia",
        "notes": "Disetujui Admin"
    }, token)
    assert approve_res.get("ok"), f"Gagal approve re-register part: {approve_res}"
    
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT status FROM master_stocks WHERE sn = %s", (test_p15,))
            st = cur.fetchone()["status"]
            assert st == "Tersedia", f"Status di master_stocks belum Tersedia: {st}"
    print("[PASS] Test 6: Approval permohonan part sukses mengembalikan status part menjadi 'Tersedia'.")

    # TEST 6: Sekarang buat unit dengan status HOLD_ASSY (dari Testing Operator Assembly)
    test_p15_test = "SN-TEST-P15-TESTING"
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('15', %s, 'Hold')", (test_p15_test,))
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, pic_assembly, part15_sn, part12_sn, part1_sn, status, reason, defective_parts, qc_status, verified_status)
                VALUES (%s, 2, 'admin', %s, %s, %s, 'Hold', 'Gagal uji fungsi tester', '15', 'HOLD_ASSY', 'HOLD_GUDANG')
                RETURNING id
            """, (tray_id, test_p15_test, test_p12, test_p1))
            assy_id_testing = cur.fetchone()['id']
            conn.commit()

    # Unit hasil testing HARUS MASUK ke Barang Produksi
    prod_items_2 = api_get("/api/production/hold-items", token)
    found_testing_in_prod = any(item.get("assembly_id") == assy_id_testing for item in prod_items_2.get("data", []))
    assert found_testing_in_prod, "GAGAL: Unit dari proses testing (HOLD_ASSY) TIDAK masuk ke Barang Produksi!"
    print("[PASS] Test 7: Unit dengan status Hold dari TESTING (HOLD_ASSY) BENAR masuk ke Barang Produksi.")

    # Cek get-connected-set untuk part dari unit testing
    conn_info_test = api_get(f"/api/master/get-connected-set?sn={test_p15_test}&part_type=15", token)
    assert conn_info_test.get("ok"), "Gagal get connected set testing"
    assert conn_info_test.get("is_connected") is True, "GAGAL: Unit testing harusnya is_connected: True"
    print("[PASS] Test 8: Unit hasil testing diidentifikasi sebagai 1 set rakitan terhubung.")

    # Cleanup test data
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM re_register_requests WHERE sn IN (%s, %s, %s, %s)", (test_p15, test_p1, test_p12, test_p15_test))
            cur.execute("DELETE FROM assemblies WHERE tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM parts WHERE tray_id = %s", (tray_id,))
            cur.execute("DELETE FROM trays WHERE id = %s", (tray_id,))
            cur.execute("DELETE FROM master_stocks WHERE sn IN (%s, %s, %s, %s)", (test_p15, test_p1, test_p12, test_p15_test))
            conn.commit()

    print("\n========================================================")
    print("SEMUA PENGUJIAN LOGIKA BARU BERHASIL 100% TANPA KENDALA!")
    print("========================================================")

if __name__ == "__main__":
    main()
