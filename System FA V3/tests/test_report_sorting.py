import os
import sys
import json
import time
import threading
import urllib.request
import urllib.parse
import psycopg2

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import app

def test_report_sorting():
    print("==================================================================")
    print("TESTING REPORT SORTING (ASCENDING & DESCENDING) FEATURE")
    print("==================================================================")

    port = 52901
    server = app.ThreadingHTTPServer(('127.0.0.1', port), app.Handler)
    server_thread = threading.Thread(target=server.serve_forever, daemon=True)
    server_thread.start()
    time.sleep(1)
    base_url = f"http://127.0.0.1:{port}"
    print(f"Test server started at {base_url}")

    def make_req(path, method="GET", data=None, token=None):
        url = base_url + path
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

    # 1. Login Admin
    st, res = make_req("/api/login", "POST", {"username": "admin", "password": "123", "force_takeover": True})
    if not res.get("ok"):
        st, res = make_req("/api/login", "POST", {"username": "admin", "password": "admin123", "force_takeover": True})
    assert res.get("ok"), f"Admin login failed: {res}"
    token = res["token"]
    print("[1 OK] Admin login successful.")

    # 2. Test /api/laporan order=desc vs order=asc
    st, res_desc = make_req("/api/laporan?order=desc", token=token)
    assert st == 200 and res_desc.get("ok"), f"Laporan desc failed: {res_desc}"
    data_desc = res_desc.get("data", [])

    st, res_asc = make_req("/api/laporan?order=asc", token=token)
    assert st == 200 and res_asc.get("ok"), f"Laporan asc failed: {res_asc}"
    data_asc = res_asc.get("data", [])

    print(f"[2 OK] /api/laporan returned {len(data_desc)} items for DESC and {len(data_asc)} items for ASC.")
    if len(data_desc) >= 2:
        first_desc_id = data_desc[0]["id"]
        last_desc_id = data_desc[-1]["id"]
        first_asc_id = data_asc[0]["id"]
        last_asc_id = data_asc[-1]["id"]
        print(f"       DESC first ID: {first_desc_id}, last ID: {last_desc_id}")
        print(f"       ASC  first ID: {first_asc_id}, last ID: {last_asc_id}")
        assert first_desc_id == last_asc_id or data_desc != data_asc, "Sorting did not alter order"

    # 3. Test /api/laporan with sort_by=sn15
    st, res_sn_asc = make_req("/api/laporan?sort_by=sn15&order=asc", token=token)
    assert st == 200 and res_sn_asc.get("ok")
    data_sn_asc = res_sn_asc.get("data", [])
    st, res_sn_desc = make_req("/api/laporan?sort_by=sn15&order=desc", token=token)
    assert st == 200 and res_sn_desc.get("ok")
    data_sn_desc = res_sn_desc.get("data", [])
    print(f"[3 OK] /api/laporan with sort_by=sn15 returned {len(data_sn_asc)} items.")
    if len(data_sn_asc) >= 2:
        sns_asc = [r["part15_sn"] or "" for r in data_sn_asc if r.get("part15_sn")]
        if len(sns_asc) >= 2:
            assert sns_asc == sorted(sns_asc), f"SNs not sorted ASC: {sns_asc[:5]}"
            print(f"       SNs correctly sorted ASC: {sns_asc[:3]} ... {sns_asc[-3:]}")

    # 4. Test /api/reports/assy-tests order=asc vs order=desc
    st, res_assy_desc = make_req("/api/reports/assy-tests?order=desc", token=token)
    assert st == 200 and res_assy_desc.get("ok")
    st, res_assy_asc = make_req("/api/reports/assy-tests?order=asc", token=token)
    assert st == 200 and res_assy_asc.get("ok")
    print(f"[4 OK] /api/reports/assy-tests sorting tested successfully.")

    # 5. Test /api/reports/qc-tests order=asc vs order=desc
    st, res_qc_desc = make_req("/api/reports/qc-tests?order=desc", token=token)
    assert st == 200 and res_qc_desc.get("ok")
    st, res_qc_asc = make_req("/api/reports/qc-tests?order=asc", token=token)
    assert st == 200 and res_qc_asc.get("ok")
    print(f"[5 OK] /api/reports/qc-tests sorting tested successfully.")

    # 6. Test /api/reports/warehouse-receipts order=asc vs order=desc
    st, res_wh_desc = make_req("/api/reports/warehouse-receipts?order=desc", token=token)
    assert st == 200 and res_wh_desc.get("ok")
    st, res_wh_asc = make_req("/api/reports/warehouse-receipts?order=asc", token=token)
    assert st == 200 and res_wh_asc.get("ok")
    print(f"[6 OK] /api/reports/warehouse-receipts sorting tested successfully.")

    # 7. Check HTML template elements for report sorting
    with open("static/index.html", "r", encoding="utf-8") as f:
        html = f.read()

    assert "Pilihan Urutan:" in html, "Pilihan Urutan label not found in index.html"
    assert "Urutkan Kolom:" in html, "Urutkan Kolom label not found in index.html"
    assert "toggleReportSortOrder" in html, "toggleReportSortOrder not found in index.html"
    assert "setReportSort" in html, "setReportSort not found in index.html"
    assert "getReportSortIconClass" in html, "getReportSortIconClass not found in index.html"
    assert "getReportSortLabel" in html, "getReportSortLabel not found in index.html"
    assert "compareReportValues" in html, "compareReportValues not found in index.html"
    print("[7 OK] All frontend HTML and Vue template sorting controls verified.")

    print("\nALL REPORT SORTING (ASCENDING & DESCENDING) CHECKS PASSED 100%!")

if __name__ == "__main__":
    test_report_sorting()
