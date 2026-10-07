import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import time
import json
import urllib.request
import urllib.parse
from app import get_conn

BASE_URL = "http://localhost:8000"

def login(username, password):
    data = json.dumps({"username": username, "password": password, "force_takeover": True}).encode('utf-8')
    req = urllib.request.Request(f"{BASE_URL}/api/login", data=data, headers={"Content-Type": "application/json"})
    with urllib.request.urlopen(req) as resp:
        res = json.loads(resp.read().decode('utf-8'))
        return res.get('token')

def api_post(path, body, token):
    data = json.dumps(body).encode('utf-8')
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
        headers["Cookie"] = f"session={token}"
    req = urllib.request.Request(f"{BASE_URL}{path}", data=data, headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode('utf-8'))

def api_get(path, token):
    headers = {}
    if token:
        headers["Authorization"] = f"Bearer {token}"
        headers["Cookie"] = f"session={token}"
    req = urllib.request.Request(f"{BASE_URL}{path}", headers=headers)
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode('utf-8'))

def run_tests():
    print("=== STARTING QC PRINT & STRICT SEPARATION TESTS ===")
    admin_tok = login("admin", "admin123")
    gudang_tok = login("Gudang", "gudang123")
    assy_tok = login("Assy", "assy123")
    print("[OK] Logged in as admin, Gudang, Assy")

    ts = int(time.time())

    # ----------------------------------------------------
    # TEST 1: STRICT SEPARATION - QC REWORK / DECLINED NEVER APPEARS IN BARANG RAKITAN
    # ----------------------------------------------------
    print("\n--- TEST 1: QC Item Strict Separation (Never in Barang Rakitan, Always in Hasil QC) ---")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO trays (tray_code, source_document_mo, product_type, pic_warehouse, assigned_operator)
                VALUES (%s, 'MO-SEP', 'Nanggala P9', 'Gudang', 'Assy')
                RETURNING id
            """, (f'TRAY-SEP-{ts}',))
            tray_id = cur.fetchone()['id']
            cur.execute("""
                INSERT INTO assemblies (tray_id, set_number, part15_sn, part1_sn, part12_sn, status, qc_status, pic_assembly, started_at)
                VALUES (%s, 1, %s, %s, %s, 'Good', 'PENDING_QC', 'Assy', NOW())
                RETURNING id
            """, (tray_id, f'P15-SEP-{ts}', f'P1-SEP-{ts}', f'P12-SEP-{ts}'))
            assy_id = cur.fetchone()['id']
            for pt, sn in [('15', f'P15-SEP-{ts}'), ('1', f'P1-SEP-{ts}'), ('12', f'P12-SEP-{ts}')]:
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES (%s, %s, 'Good')", (pt, sn))
            conn.commit()

    # QC test fails
    res_qc = api_post("/api/qc/submit-test", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy_id,
        "part15_sn": f'P15-SEP-{ts}',
        "part12_sn": f'P12-SEP-{ts}',
        "part1_sn": f'P1-SEP-{ts}',
        "test_checklist": [{"test_num": 2, "status": "NOT_GOOD"}],
        "notes": "gagal qc set 1"
    }, admin_tok)
    assert res_qc.get("ok") is True

    # Gudang registers the set via reregister-declined (simulating decline/re-registration)
    res_reg = api_post("/api/master/reregister-declined", {
        "assembly_id": assy_id,
        "reason": "gagal qc (Set #1)",
        "target_status": "Hold"
    }, gudang_tok)
    assert res_reg.get("ok") is True
    print("[OK] Gudang re-registered QC item with reason 'gagal qc'")

    # Query pending-assemblies: It MUST be in qc_data and MUST NOT be in data (Barang Rakitan)
    res_pending = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_in_assy_section = [d['assembly_id'] for d in res_pending.get('data', [])]
    assy_in_qc_section = [d['assembly_id'] for d in res_pending.get('qc_data', [])]

    assert assy_id not in assy_in_assy_section, "QC item must NEVER appear in BARANG RAKITAN section!"
    assert assy_id in assy_in_qc_section, "QC item MUST appear in HASIL UJI MUTU QC section!"
    print("[OK] Verified: QC item is strictly in HASIL UJI MUTU QC and NOT in Barang Perakitan!")

    # Admin marks it REWORK_QC
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id,
        "decision": "REWORK_QC",
        "notes": "Perlu Rework QC"
    }, admin_tok)

    # Gudang finishes rework and re-registers it again
    api_post("/api/master/re-register", {
        "assembly_id": assy_id,
        "reason": "Selesai perbaikan QC"
    }, gudang_tok)

    # Check again: MUST still be in qc_data and NOT in data!
    res_pending2 = api_get("/api/admin/pending-assemblies", admin_tok)
    assy_in_assy_section2 = [d['assembly_id'] for d in res_pending2.get('data', [])]
    assy_in_qc_section2 = [d['assembly_id'] for d in res_pending2.get('qc_data', [])]
    assert assy_id not in assy_in_assy_section2, "Reworked QC item must NEVER leak into BARANG RAKITAN!"
    assert assy_id in assy_in_qc_section2, "Reworked QC item MUST reappear in HASIL UJI MUTU QC!"
    print("[OK] Verified: Reworked QC item stays strictly in HASIL UJI MUTU QC!")

    # Admin approves GOOD_QC -> moves to Warehouse Receipt
    api_post("/api/admin/approve-hold-item", {
        "assembly_id": assy_id,
        "decision": "APPROVE_GOOD_QC",
        "notes": "QC sudah lolos"
    }, admin_tok)
    print("[OK] Admin approved QC item as Good")

    # ----------------------------------------------------
    # TEST 2: VERIFIKASI AKTUAL FISIK PADA CETAK DOKUMEN QC KE GUDANG
    # ----------------------------------------------------
    print("\n--- TEST 2: Verifikasi Aktual Fisik pada Cetak Dokumen QC ke Gudang ---")
    # Call verify-qc-receipt by tray_id (simulating Gudang verifying the physical tray before printing)
    res_actual_verif = api_post("/api/warehouse/verify-qc-receipt", {
        "tray_id": tray_id,
        "action": "ACCEPT"
    }, gudang_tok)
    assert res_actual_verif.get("ok") is True
    assert res_actual_verif.get("count", 0) >= 1
    print(f"[OK] Physical actual verification completed for Tray: {res_actual_verif.get('message')}")

    # ----------------------------------------------------
    # TEST 3: LAPORAN VERIFIKASI AKTUAL GUDANG - HISTORY RECAP (ONLY GOOD ITEMS)
    # ----------------------------------------------------
    print("\n--- TEST 3: Laporan Verifikasi Aktual Gudang (History Recap, Only Good) ---")
    res_report = api_get(f"/api/reports/warehouse-receipts?q=P15-SEP-{ts}", admin_tok)
    assert res_report.get("ok") is True
    report_items = res_report.get("data", [])
    assert len(report_items) >= 1, "Report must contain the verified Good item!"
    item = report_items[0]
    assert item["part15_sn"] == f'P15-SEP-{ts}'
    assert item["qc_status"] == 'GOOD', "Report must ONLY return items with qc_status = 'GOOD'!"
    assert item["warehouse_receipt_status"] == 'ACCEPTED'
    print(f"[OK] Report returned verified item: SN {item['part15_sn']}, Status: {item['warehouse_receipt_status']}, QC Doc: {item['qc_doc_number']}")

    print("\n=== ALL QC PRINT & STRICT SEPARATION TESTS PASSED 100%! ===")

if __name__ == "__main__":
    run_tests()
