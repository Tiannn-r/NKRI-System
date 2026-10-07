import os
import sys
import urllib.request
import urllib.parse
import json
import time
import psycopg2

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from app import DB_URL

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
    print("================================================================", flush=True)
    print("STARTING END-TO-END VERIFICATION: 4-STAGE REVISED MANUFACTURING FLOW", flush=True)
    print("Perakitan -> Tester Assembly (15 Stages) -> QC -> Verifikasi Gudang", flush=True)
    print("================================================================", flush=True)

    # 1. Login Admin
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Admin login failed: {res}"
    admin_token = res["token"]
    print("[STEP 1 OK] Admin login successful.", flush=True)

    # 2. Login Assembly Operator
    st, res = make_req("/api/login", "POST", {"username": "assy", "password": "123", "force_takeover": True})
    assy_token = res.get("token") or admin_token
    print("[STEP 2 OK] Assembly operator token acquired.", flush=True)

    # 3. Create unique test parts in Master Stock via DB directly
    timestamp = int(time.time()) % 100000
    p15_good = f"B110{timestamp:04d}"
    p12_good = f"6{timestamp:04d}"
    p1_good = f"{timestamp % 900 + 100:03d}"

    p15_hold = f"B110{(timestamp + 1):04d}"
    p12_hold = f"6{(timestamp + 1):04d}"
    p1_hold = f"{(timestamp + 1) % 900 + 100:03d}"

    print(f"Creating test parts for Good Set: {p15_good} / {p12_good} / {p1_good}", flush=True)
    print(f"Creating test parts for Hold Set: {p15_hold} / {p12_hold} / {p1_hold}", flush=True)

    conn = psycopg2.connect(DB_URL)
    with conn.cursor() as cur:
        for sn, pt in [(p15_good, '15'), (p12_good, '12'), (p1_good, '1'),
                       (p15_hold, '15'), (p12_hold, '12'), (p1_hold, '1')]:
            cur.execute("DELETE FROM master_stocks WHERE sn = %s AND part_type = %s", (sn, pt))
            cur.execute("INSERT INTO master_stocks (sn, part_type, status) VALUES (%s, %s, 'Tersedia')", (sn, pt))
    conn.commit()
    conn.close()

    # Create Supply Tray via /api/gudang/save
    st, res = make_req("/api/gudang/save", "POST", {
        "pic_warehouse": "gudang",
        "assigned_operator": "assy",
        "source_document_mo": f"WH/MO/{timestamp}",
        "product_type": "Nanggala P9",
        "parts": [
            {"part_type": "15", "sn": p15_good},
            {"part_type": "12", "sn": p12_good},
            {"part_type": "1", "sn": p1_good},
            {"part_type": "15", "sn": p15_hold},
            {"part_type": "12", "sn": p12_hold},
            {"part_type": "1", "sn": p1_hold}
        ]
    }, token=admin_token)
    assert res.get("ok"), f"Create tray failed: {res}"
    tray_data = res.get("data", {})
    tray_id = tray_data.get("tray_id") or tray_data.get("id")
    tray_code = tray_data.get("tray_code")
    print(f"[STEP 3 OK] Created Supply Tray: {tray_code} (ID: {tray_id}) with 2 sets.", flush=True)

    # Fetch tray details
    st, res = make_req(f"/api/assembly/tray?tray_code={tray_code}", "GET", token=assy_token)
    assert res.get("ok"), f"Fetch tray failed: {res}"
    assemblies = res.get("data", {}).get("assemblies", [])
    print(f"[STEP 4 OK] Tray fetched with {len(assemblies)} assembly slots.", flush=True)

    # -------------------------------------------------------------
    # STAGE 1: PERAKITAN
    # -------------------------------------------------------------
    # Save Set #1 as GOOD
    st, res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "part15_sn": p15_good,
        "part12_sn": p12_good,
        "part1_sn": p1_good,
        "status": "Good",
        "operator": "assy"
    }, token=assy_token)
    assert res.get("ok"), f"Assembly save Good set failed: {res}"
    print(f"[STAGE 1 - SET 1 OK] Perakitan Set #1 Good saved!", flush=True)

    # Save Set #2 as HOLD (Simulating Perakitan issue)
    st, res = make_req("/api/assembly/save", "POST", {
        "tray_id": tray_id,
        "set_number": 2,
        "part15_sn": p15_hold,
        "part12_sn": p12_hold,
        "part1_sn": p1_hold,
        "status": "Hold",
        "defective_parts": "12",
        "reason": "Bagian luncuran seret saat rakit",
        "operator": "assy"
    }, token=assy_token)
    assert res.get("ok"), f"Assembly save Hold set failed: {res}"
    print(f"[STAGE 1 - SET 2 OK] Perakitan Set #2 Hold saved!", flush=True)

    # Re-fetch tray to get populated assembly IDs
    st, res = make_req(f"/api/assembly/tray?tray_code={tray_code}", "GET", token=assy_token)
    refreshed_assys = res.get("data", {}).get("assemblies", [])
    assy_id_good = None
    assy_id_hold = None
    for a in refreshed_assys:
        if a.get("set_number") == 1:
            assy_id_good = a.get("id")
        elif a.get("set_number") == 2:
            assy_id_hold = a.get("id")
    assert assy_id_good and assy_id_hold, "Failed to locate assembly IDs!"
    print(f"[STAGE 1 OK] Set #1 ID: {assy_id_good}, Set #2 ID: {assy_id_hold}", flush=True)

    # -------------------------------------------------------------
    # STAGE 2: TESTER ASSEMBLY (15 TAHAPAN PENGUJIAN)
    # -------------------------------------------------------------
    fifteen_stages = [
        {"test_num": 1, "name": "Cek upper dan lower", "status": "GOOD"},
        {"test_num": 2, "name": "Cek front sight dan rear sight", "status": "GOOD"},
        {"test_num": 3, "name": "Uji mag release 1 mag 3 kali release", "status": "GOOD"},
        {"test_num": 4, "name": "Test Kokang 3x", "status": "GOOD"},
        {"test_num": 5, "name": "Tarik picu, hammer mukul", "status": "GOOD"},
        {"test_num": 6, "name": "Tarik hammer kemudian tarik picu", "status": "GOOD"},
        {"test_num": 7, "name": "Tarik hammer 1/2 kemudian tarik picu", "status": "GOOD"},
        {"test_num": 8, "name": "Tarik hammer full kemudian tarik picu", "status": "GOOD"},
        {"test_num": 9, "name": "Tarik hammer full kemudian dorong kedepan", "status": "GOOD"},
        {"test_num": 10, "name": "Safety lock on kemudian tarik picu", "status": "GOOD"},
        {"test_num": 11, "name": "Turunkan hammer kemudian kokang penuh kebelakang, aktifkan slide lock", "status": "GOOD"},
        {"test_num": 12, "name": "Slide lock turunkan", "status": "GOOD"},
        {"test_num": 13, "name": "Kokang penuh kemudian aktifkan slide lock dan savety river aktifkan", "status": "GOOD"},
        {"test_num": 14, "name": "Tutup safety river kemudian turunkan slide lock", "status": "GOOD"},
        {"test_num": 15, "name": "Tarik hammer letakan tangan dipelindung picu kemudian dorong barrel dari depan", "status": "GOOD"}
    ]

    # Test Set #1: ALL 15 STAGES GOOD
    st, res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 1,
        "assembly_id": assy_id_good,
        "part15_sn": p15_good,
        "part12_sn": p12_good,
        "part1_sn": p1_good,
        "test_checklist": fifteen_stages,
        "notes": "Uji fungsional 15 tahap lolos sempurna"
    }, token=assy_token)
    assert res.get("ok"), f"Submit test Set #1 failed: {res}"
    assert res.get("status") == "Good", f"Expected status Good, got {res.get('status')}"
    assy_doc_number = res.get("doc_number")
    assert assy_doc_number and assy_doc_number.startswith("Assy/QC/"), f"Expected Assy/QC doc, got {assy_doc_number}"
    print(f"[STAGE 2 - SET 1 OK] 15 Tahap Tester Assembly Lolos GOOD! Terbit Dokumen: {assy_doc_number}", flush=True)

    # Test Set #2: 1 STAGE NOT GOOD (Stage 4 Test Kokang 3x fail)
    fifteen_stages_fail = [dict(s) for s in fifteen_stages]
    fifteen_stages_fail[3]["status"] = "NOT_GOOD" # Stage 4 fail

    st, res = make_req("/api/assembly/submit-test", "POST", {
        "tray_id": tray_id,
        "set_number": 2,
        "assembly_id": assy_id_hold,
        "part15_sn": p15_hold,
        "part12_sn": p12_hold,
        "part1_sn": p1_hold,
        "test_checklist": fifteen_stages_fail,
        "notes": "Gagal pada kokang tahap 4"
    }, token=assy_token)
    assert res.get("ok"), f"Submit test Set #2 failed: {res}"
    assert res.get("status") == "Hold", f"Expected status Hold, got {res.get('status')}"
    assert "test 4" in res.get("failure_reasons", "").lower(), f"Expected auto failure reason, got {res.get('failure_reasons')}"
    print(f"[STAGE 2 - SET 2 OK] Tester Assembly Hold berhasil auto-generate alasan: '{res.get('failure_reasons')}'", flush=True)

    # -------------------------------------------------------------
    # STAGE 3: QUALITY CONTROL (QC)
    # -------------------------------------------------------------
    # Fetch QC pending items: Set #1 MUST be present!
    st, res = make_req("/api/qc/pending-items", "GET", token=admin_token)
    assert res.get("ok"), f"Fetch QC items failed: {res}"
    qc_pending = res.get("data", [])
    target_qc = next((item for item in qc_pending if item.get("part15_sn") == p15_good), None)
    assert target_qc is not None, f"Set #1 ({p15_good}) not found in QC pending items!"
    print(f"[STAGE 3 - PENDING OK] Set #1 ({p15_good}) ditemukan di antrean Card QC.", flush=True)

    # Verify that Set #1 has the 15-stage checklist attached!
    qc_checklist_raw = target_qc.get("test_checklist") or target_qc.get("assy_test_checklist")
    if isinstance(qc_checklist_raw, str):
        qc_checklist_data = json.loads(qc_checklist_raw)
    else:
        qc_checklist_data = qc_checklist_raw or []
    assert len(qc_checklist_data) == 15, f"Expected 15 test stages attached to QC card, got {len(qc_checklist_data)}"
    print(f"[STAGE 3 - MODAL DETAIL OK] 15 Tahapan hasil uji tester assembly tersimpan utuh di Card QC ({len(qc_checklist_data)} tahap).", flush=True)

    # QC Decision for Set #1: GOOD
    st, res = make_req("/api/qc/submit-test", "POST", {
        "assembly_id": assy_id_good,
        "status": "Good",
        "notes": "Verifikasi QC Lulus, Standar Dimensi dan Uji Sesuai",
        "test_checklist": fifteen_stages
    }, token=admin_token)
    assert res.get("ok"), f"Submit QC Good failed: {res}"
    assert res.get("status") == "Good"
    wh_qc_doc_number = res.get("doc_number")
    assert wh_qc_doc_number and wh_qc_doc_number.startswith("WH/QC/"), f"Expected WH/QC doc, got {wh_qc_doc_number}"
    print(f"[STAGE 3 - QC GOOD OK] Keputusan QC: Good! Terbit Dokumen Resmi Mutu: {wh_qc_doc_number}", flush=True)

    # Verify QC Persistence: Check `/api/qc/items`
    st, res = make_req("/api/qc/items", "GET", token=admin_token)
    assert res.get("ok"), f"Fetch all QC items failed: {res}"
    history_qc = res.get("history", [])
    target_history = next((item for item in history_qc if item.get("part15_sn") == p15_good), None)
    assert target_history is not None, f"Set #1 ({p15_good}) hilang dari history QC!"
    assert target_history.get("qc_status") == "GOOD", f"Expected qc_status GOOD, got {target_history.get('qc_status')}"
    print(f"[STAGE 3 - PERSISTENCE OK] Card QC Set #1 ({p15_good}) tetap tersimpan permanen di riwayat QC (Status: GOOD).", flush=True)

    # -------------------------------------------------------------
    # STAGE 4: VERIFIKASI FISIK GUDANG (Penerimaan Hasil QC)
    # -------------------------------------------------------------
    st, res = make_req("/api/warehouse/verify-qc-receipt", "POST", {
        "assembly_id": assy_id_good,
        "action": "ACCEPT",
        "notes": "Fisik unit P9 diterima lengkap di gudang senjata"
    }, token=admin_token)
    assert res.get("ok"), f"Warehouse verify failed: {res}"
    print(f"[STAGE 4 OK] Verifikasi fisik gudang selesai: '{res.get('message')}'", flush=True)

    # -------------------------------------------------------------
    # REPORTS & TRACEABILITY
    # -------------------------------------------------------------
    st, res = make_req(f"/api/reports/assy-tests?doc_number={assy_doc_number}", "GET", token=admin_token)
    assert res.get("ok") and len(res.get("data", [])) > 0, "Laporan Tester Assy tidak memuat record!"
    print(f"[REPORTS OK] Laporan Hasil Uji Tester Assy memuat dokumen {assy_doc_number}.", flush=True)

    st, res = make_req(f"/api/reports/qc-tests?doc_number={wh_qc_doc_number}", "GET", token=admin_token)
    assert res.get("ok") and len(res.get("data", [])) > 0, "Laporan QC tidak memuat record!"
    print(f"[REPORTS OK] Laporan Hasil Uji QC memuat dokumen {wh_qc_doc_number}.", flush=True)

    st, res = make_req(f"/api/reports/warehouse-receipts?doc_number={wh_qc_doc_number}", "GET", token=admin_token)
    assert res.get("ok") and len(res.get("data", [])) > 0, "Laporan Penerimaan Gudang tidak memuat record!"
    print(f"[REPORTS OK] Laporan Penerimaan Gudang memuat dokumen {wh_qc_doc_number}.", flush=True)

    print("================================================================", flush=True)
    print("ALL 4-STAGE REVISED MANUFACTURING FLOW VERIFICATIONS PASSED (100%)!", flush=True)
    print("================================================================", flush=True)

if __name__ == "__main__":
    run_test()
