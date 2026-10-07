import requests
import json
import psycopg2
from psycopg2.extras import RealDictCursor

BASE_URL = "http://127.0.0.1:8000"
DB_URL = "postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db"

def get_db():
    return psycopg2.connect(DB_URL, cursor_factory=RealDictCursor)

def test_html_ui_elements():
    print("--- 1. Testing HTML / Vue Template Elements ---")
    with open("static/index.html", "r", encoding="utf-8") as f:
        html = f.read()

    # 1. Test Assembly badge in filteredAdminQCSets is GOOD
    assert '<span class="font-bold text-slate-700">Test Assembly:</span>' in html
    qc_card_part = html[html.find('filteredAdminQCSets'):html.find('<!-- Petugas & Waktu QC -->')]
    assert 'Test Assembly:' in qc_card_part
    assert 'GOOD' in qc_card_part
    print("[PASS] Admin QC cards show Test Assembly as GOOD.")

    # 2. Real-time failure box in Test Assembly modal is removed
    assert 'Status Hasil: <strong class="uppercase text-red-700 font-black">HOLD (Gagal Uji)</strong>' not in html
    print("[PASS] Failure reasons banner box removed from Test Assembly modal.")

    # 3. Document Title is 'Quality Control dan Test Functional 2' and 'Catatan QC' is removed
    assert 'Quality Control dan Test Functional 2' in html
    wh_qc_doc_chunk = html[html.find('template v-if="activePrintDocType === \'wh_qc\'"'):html.find('template v-if="activePrintDocType === \'wh_qc\'"') + 5000]
    assert 'Quality Control dan Test Functional 2' in wh_qc_doc_chunk
    assert '<th class="p-1.5 text-center bg-slate-100">Catatan QC</th>' not in wh_qc_doc_chunk
    print("[PASS] Dokumen QC title updated and Catatan QC column removed.")

    # 4. Dokumen WO signature has only Gudang and Perakitan (no QC)
    supply_doc_chunk = html[html.find("activePrintDocType === 'supply'"):html.find("activePrintDocType === 'assy'")]
    assert 'Diperiksa\n                      Oleh (Quality Control):' not in supply_doc_chunk
    assert 'Diperiksa Oleh (Quality Control):' not in supply_doc_chunk
    print("[PASS] Dokumen WO (Supply) is clean of QC.")

    # 5. Laporan QC Terakhir has Status Barang, Status Verified, Keterangan
    subtab4_chunk = html[html.find("SUB-TAB 4: PENERIMAAN FISIK GUDANG"):html.find("<!-- ================= TAB 5: MASTER DB INVENTORY MONITOR ================= -->")]
    assert 'Status Barang' in subtab4_chunk
    assert 'Status Verified' in subtab4_chunk
    assert 'Keterangan' in subtab4_chunk
    print("[PASS] Laporan QC Terakhir includes Status Barang | Status Verified | Keterangan columns.")

def test_decline_preserves_perakitan_status():
    print("\n--- 2. Testing Warehouse Decline Behavior on Assemblies ---")
    conn = get_db()
    cur = conn.cursor()

    # Login as admin to get token
    res = requests.post(f"{BASE_URL}/api/login", json={"username": "admin", "password": "123", "force_takeover": True})
    if not res.json().get("ok"):
        res = requests.post(f"{BASE_URL}/api/login", json={"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.status_code == 200 and res.json().get("ok")
    token = res.json()["token"]
    headers = {"Authorization": f"Bearer {token}"}

    # Create dummy tray & assembly with Good status
    cur.execute("""
        INSERT INTO trays (tray_code, source_document_mo, product_type, assigned_operator, pic_warehouse)
        VALUES ('TRAY-TEST-DEC-01', 'WH/MO/999901', 'Nanggala P9', 'operator1', 'admin')
        RETURNING id
    """)
    tray_id = cur.fetchone()["id"]

    cur.execute("""
        INSERT INTO assemblies (tray_id, set_number, part15_sn, part12_sn, part1_sn, status, qc_status, pic_assembly, finished_at)
        VALUES (%s, 1, 'P15-TESTDEC-01', 'P12-TESTDEC-01', 'P1-TESTDEC-01', 'Good', 'GOOD', 'operator1', NOW())
        RETURNING id
    """, (tray_id,))
    assy_id = cur.fetchone()["id"]
    cur.execute("""
        INSERT INTO assembly_wo_history (tray_id, set_number, part15_sn, part12_sn, part1_sn, status, pic_assembly, finished_at)
        VALUES (%s, 1, 'P15-TESTDEC-01', 'P12-TESTDEC-01', 'P1-TESTDEC-01', 'Good', 'operator1', NOW())
    """, (tray_id,))
    conn.commit()

    # Gudang declines receipt via /api/warehouse/verify-qc-receipt
    dec_res = requests.post(
        f"{BASE_URL}/api/warehouse/verify-qc-receipt",
        json={"assembly_id": assy_id, "action": "DECLINED", "notes": "Fisik baret dan kotak rusak"},
        headers=headers
    )
    assert dec_res.status_code == 200, f"Decline failed: {dec_res.text}"

    # Verify DB state: assemblies.status MUST REMAIN 'Good'
    cur.execute("SELECT status, warehouse_receipt_status, verified_status, is_declined, declined_reason FROM assemblies WHERE id = %s", (assy_id,))
    assy_row = cur.fetchone()
    assert assy_row["status"] == "Good", f"Expected status 'Good', got {assy_row['status']}"
    assert assy_row["warehouse_receipt_status"] == "DECLINED"
    assert assy_row["verified_status"] == "DECLINED"
    assert assy_row["is_declined"] is True
    assert "Fisik baret" in assy_row["declined_reason"]
    print("[PASS] DB level: assemblies.status remains 'Good', warehouse_receipt_status is 'DECLINED'.")

    # Verify /api/tray/detail returns status 'Good'
    tray_detail_res = requests.get(f"{BASE_URL}/api/tray/detail?id={tray_id}", headers=headers)
    assert tray_detail_res.status_code == 200
    detail_data = tray_detail_res.json()["data"]["assemblies"][0]
    assert detail_data["status"] == "Good", f"Perakitan tray slot status should be 'Good', got {detail_data['status']}"
    print("[PASS] /api/tray/detail returns status 'Good' for Perakitan station card.")

    # Verify /api/laporan (Laporan Perakitan / WO) returns status 'Good'
    laporan_res = requests.get(f"{BASE_URL}/api/laporan?q=P15-TESTDEC-01", headers=headers)
    assert laporan_res.status_code == 200
    laporan_item = laporan_res.json()["data"][0]
    assert laporan_item["status"] == "Good", f"Laporan Perakitan status should be 'Good', got {laporan_item['status']}"
    print("[PASS] /api/laporan returns status 'Good' for Perakitan history report.")

    # Verify /api/reports/warehouse-receipts (Laporan QC Terakhir)
    wh_rep_res = requests.get(f"{BASE_URL}/api/reports/warehouse-receipts?q=P15-TESTDEC-01", headers=headers)
    assert wh_rep_res.status_code == 200
    wh_rep_item = wh_rep_res.json()["data"][0]
    assert wh_rep_item["barang_status"] == "Good" or wh_rep_item["assembly_status"] == "Good"
    assert wh_rep_item["warehouse_receipt_status"] == "DECLINED"
    assert "Fisik baret" in wh_rep_item["declined_reason"]
    print("[PASS] /api/reports/warehouse-receipts clearly reports: Status Barang = Good, Status Verified = DECLINED, Keterangan = reason.")

    # Clean up test data
    cur.execute("DELETE FROM assemblies WHERE id = %s", (assy_id,))
    cur.execute("DELETE FROM trays WHERE id = %s", (tray_id,))
    conn.commit()
    conn.close()
    print("[PASS] Test cleanup completed successfully.")

if __name__ == "__main__":
    test_html_ui_elements()
    test_decline_preserves_perakitan_status()
    print("\n>>> ALL 5 REQUIREMENTS VERIFIED AND PASSED 100% CLEANLY! <<<")
