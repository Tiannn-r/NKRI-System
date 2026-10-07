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

def test_api():
    print("Testing HTTP endpoints on live server...")
    
    # 1. Login as Admin
    status, res = make_request("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        # try admin123
        status, res = make_request("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    
    assert res.get("ok"), f"Login failed: {res}"
    token = res["token"]
    print("[OK] Login successful, token acquired.")

    # 2. Get QC Pending Items
    status, res = make_request("/api/qc/pending-items", "GET", token=token)
    assert status == 200 and res.get("ok"), f"GET /api/qc/pending-items failed: {res}"
    print(f"[OK] GET /api/qc/pending-items returned {len(res.get('data', []))} items.")

    # 3. Get Assy Reports
    status, res = make_request("/api/reports/assy-tests", "GET", token=token)
    assert status == 200 and res.get("ok"), f"GET /api/reports/assy-tests failed: {res}"
    print(f"[OK] GET /api/reports/assy-tests returned {len(res.get('data', []))} records.")

    # 4. Get QC Reports
    status, res = make_request("/api/reports/qc-tests", "GET", token=token)
    assert status == 200 and res.get("ok"), f"GET /api/reports/qc-tests failed: {res}"
    print(f"[OK] GET /api/reports/qc-tests returned {len(res.get('data', []))} records.")

    # 5. Get Warehouse Receipt Reports
    status, res = make_request("/api/reports/warehouse-receipts", "GET", token=token)
    assert status == 200 and res.get("ok"), f"GET /api/reports/warehouse-receipts failed: {res}"
    print(f"[OK] GET /api/reports/warehouse-receipts returned {len(res.get('data', []))} records.")

    print("\nALL HTTP API ENDPOINTS TESTED AND WORKING PERFECTLY!")

if __name__ == "__main__":
    test_api()
