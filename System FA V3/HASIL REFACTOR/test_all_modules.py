import urllib.request
import urllib.parse
import json
import time

BASE_URL = "http://127.0.0.1:8000"

def request_api(endpoint, method="GET", data=None, headers=None):
    if headers is None:
        headers = {}
    url = f"{BASE_URL}{endpoint}"
    req_headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        **headers
    }
    encoded_data = None
    if data is not None:
        encoded_data = json.dumps(data).encode("utf-8")
    
    req = urllib.request.Request(url, data=encoded_data, headers=req_headers, method=method)
    start = time.time()
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            elapsed = int((time.time() - start) * 1000)
            status = resp.status
            body = resp.read().decode("utf-8")
            try:
                parsed = json.loads(body)
            except:
                parsed = body[:100]
            return status, elapsed, parsed
    except urllib.error.HTTPError as e:
        elapsed = int((time.time() - start) * 1000)
        body = e.read().decode("utf-8")
        try:
            parsed = json.loads(body)
        except:
            parsed = body[:100]
        return e.code, elapsed, parsed
    except Exception as e:
        elapsed = int((time.time() - start) * 1000)
        return 0, elapsed, str(e)

results = []

def run_test(module, name, endpoint, method="GET", data=None, expected_status=200):
    status, elapsed, parsed = request_api(endpoint, method, data)
    ok = (status == expected_status)
    if not ok and isinstance(parsed, dict) and parsed.get('ok') == True:
        ok = True
    result_str = "PASS" if ok else f"FAIL ({status})"
    results.append({
        "module": module,
        "name": name,
        "endpoint": f"{method} {endpoint}",
        "status": status,
        "elapsed_ms": elapsed,
        "result": result_str,
        "sample": str(parsed)[:80]
    })
    print(f"[{result_str}] {module} - {name} ({elapsed}ms)")

# 1. AUTENTIKASI & KELOLA PENGGUNA
run_test("Kelola Pengguna", "Get Operators", "/api/operators")
run_test("Kelola Pengguna", "Lookup Users", "/api/users/lookup")
run_test("Kelola Pengguna", "Get Users (Admin)", "/api/admin/users")

# 2. GUDANG & SUPPLY TRAY
run_test("Gudang", "Get Trays", "/api/assembly/trays")
run_test("Gudang", "Get Tray Detail", "/api/assembly/tray")
run_test("Gudang", "Get Returned Trays", "/api/gudang/returned-trays")

# 3. ASSEMBLY & MEJA RAKIT
run_test("Assembly", "Get Pending Reassembly", "/api/assembly/pending-reassembly")
run_test("Assembly", "Get Production Products", "/api/production/products")
run_test("Assembly", "Get Assembly Results", "/api/production/assembly-results")
run_test("Assembly", "Audit Downtime Logs", "/api/admin/downtime-logs")

# 4. TF 1 (TESTING POINT CHECKLIST)
run_test("TF 1", "Get Testing Points (Public)", "/api/testing-points")
run_test("TF 1", "Get Testing Points (Admin)", "/api/admin/testing-points")

# 5. TF 2 (QUALITY CONTROL)
run_test("TF 2", "Get QC Items", "/api/qc/items")
run_test("TF 2", "Get QC Pending Items", "/api/qc/pending-items")
run_test("TF 2", "Get Production Hold Items", "/api/production/hold-items")

# 6. PENERIMAAN FISIK GUDANG & LAPORAN
run_test("Penerimaan", "Laporan Penerimaan Fisik", "/api/reports/warehouse-receipts")
run_test("Laporan", "Laporan WO Traveler", "/api/laporan")
run_test("Laporan", "Laporan Uji Fungsional 1", "/api/reports/assy-tests")
run_test("Laporan", "Laporan Uji Mutu QC", "/api/reports/qc-tests")

# 7. DAFTAR ULANG & GANTI PART & VERIFIKASI ADMIN
run_test("Verifikasi", "Pending Verifications", "/api/admin/pending-verifications")
run_test("Verifikasi", "Pending Assemblies", "/api/admin/pending-assemblies")
run_test("Verifikasi", "Pending Replacements", "/api/admin/pending-replacements")
run_test("Riwayat Audit", "Part Audit Logs", "/api/admin/audit-logs")

# 8. MASTER STOK & TAMBAH PART NUMBER
run_test("Master Stok", "Stocks Summary", "/api/master/stocks_summary")
run_test("Master Stok", "Stocks List P15", "/api/master/stocks_list?part_type=15&page=1&limit=5")
run_test("Master Stok", "Available Parts", "/api/master/available?part_type=15")
run_test("Master Stok", "Recent Added Parts", "/api/master/recent-added?limit=5")

# 9. DELIVERY ORDER, PACKING LIST & SURAT JALAN
run_test("Delivery Order", "List Delivery Orders", "/api/delivery-orders")
run_test("Delivery Order", "Next Meta DO/PL", "/api/delivery-orders/next-meta")
run_test("Delivery Order", "DO Settings", "/api/delivery-orders/settings")
run_test("Delivery Order", "Available Serial Numbers", "/api/available-serial-numbers")
run_test("Delivery Order", "Customers List", "/api/customers")
run_test("Delivery Order", "Packing Labels", "/api/packing-labels")
run_test("Delivery Order", "Cloudinary Settings", "/api/cloudinary/settings")
run_test("Delivery Order", "Cloudinary Test", "/api/cloudinary/test")

# 10. CMS & DIAGNOSTIK (/admin)
run_test("CMS Portal", "Slow Queries Log", "/api/admin/slow-queries")
run_test("CMS Portal", "Backend Error Logs", "/api/admin/error-logs")
run_test("CMS Portal", "Anomaly Scanner", "/api/admin/anomalies/scan")

print("\n--- SUMMARY OF RESULTS ---")
total = len(results)
passed = sum(1 for r in results if "PASS" in r["result"])
print(f"Total Tests: {total} | Passed: {passed} | Failed: {total - passed}")

with open("test_results.json", "w", encoding="utf-8") as f:
    json.dump(results, f, indent=2)
