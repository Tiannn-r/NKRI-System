import urllib.request
import urllib.parse
import json

BASE_URL = "http://localhost:8000"

def make_request(path, method="GET", data=None, token=None):
    url = BASE_URL + path
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    
    body = json.dumps(data).encode("utf-8") if data else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    
    try:
        with urllib.request.urlopen(req) as resp:
            status = resp.getcode()
            res_body = resp.read().decode("utf-8")
            return status, json.loads(res_body) if res_body else {}
    except urllib.error.HTTPError as e:
        res_body = e.read().decode("utf-8")
        return e.code, json.loads(res_body) if res_body else {}

def test_live_flow():
    print("Testing live functional testing workflow via HTTP...", flush=True)

    # 1. Login as Admin
    status, res = make_request("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        status, res = make_request("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    token = res["token"]
    print("[OK] Admin token acquired.", flush=True)

    # 2. Get QC Pending items
    status, res = make_request("/api/qc/pending-items", "GET", token=token)
    assert status == 200 and res.get("ok")
    items = res.get("data", [])
    print(f"[OK] Fetched {len(items)} QC pending items.", flush=True)

    if len(items) > 0:
        target = items[0]
        assy_id = target.get("assembly_id") or target.get("id")
        print(f"Testing QC test submit on Assembly ID: {assy_id}, SN: {target.get('part15_sn')}", flush=True)

        # 3. Submit QC Test (All Good)
        qc_checklist = [
            {"test_num": 1, "name": "Visual & Label", "status": "GOOD"},
            {"test_num": 2, "name": "Fungsi Elektronik", "status": "GOOD"},
            {"test_num": 3, "name": "Sensor & Respon", "status": "GOOD"},
            {"test_num": 4, "name": "Ketahanan Beban", "status": "GOOD"},
            {"test_num": 5, "name": "Kalibrasi Standar", "status": "GOOD"}
        ]
        status, qc_res = make_request("/api/qc/submit-test", "POST", {
            "assembly_id": assy_id,
            "test_checklist": qc_checklist,
            "notes": "Live test QC Mutlak Good"
        }, token=token)
        assert status == 200 and qc_res.get("ok"), f"QC submit failed: {qc_res}"
        print(f"[OK] QC Test submitted! Generated Doc: {qc_res.get('doc_number')}", flush=True)

        # 4. Warehouse physical receipt verify (Accept)
        status, wh_res = make_request("/api/warehouse/verify-qc-receipt", "POST", {
            "assembly_id": assy_id,
            "action": "ACCEPT",
            "notes": "Barang fisik lengkap diterima di gudang"
        }, token=token)
        assert status == 200 and wh_res.get("ok"), f"WH verify failed: {wh_res}"
        print(f"[OK] Warehouse receipt verified: {wh_res.get('message')}", flush=True)

    # 5. Check reports
    status, r_assy = make_request("/api/reports/assy-tests", "GET", token=token)
    assert status == 200 and r_assy.get("ok")
    print(f"[OK] Reports Assy Tests: {len(r_assy.get('data', []))} records.", flush=True)

    status, r_qc = make_request("/api/reports/qc-tests", "GET", token=token)
    assert status == 200 and r_qc.get("ok")
    print(f"[OK] Reports QC Tests: {len(r_qc.get('data', []))} records.", flush=True)

    status, r_wh = make_request("/api/reports/warehouse-receipts", "GET", token=token)
    assert status == 200 and r_wh.get("ok")
    print(f"[OK] Reports Warehouse Receipts: {len(r_wh.get('data', []))} records.", flush=True)

    print("\nALL LIVE FLOW TESTS PASSED COMPLETELY!", flush=True)

if __name__ == "__main__":
    test_live_flow()
