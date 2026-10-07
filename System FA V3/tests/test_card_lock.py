import urllib.request
import json
import psycopg2
import os
from psycopg2.extras import RealDictCursor

BASE_URL = "http://127.0.0.1:8000/api"
DB_URL = "postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db"

def request(endpoint, method="GET", data=None, token=None):
    url = f"{BASE_URL}{endpoint}"
    headers = {"Content-Type": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    body = json.dumps(data if data is not None else {}).encode("utf-8") if (data is not None or method == "POST") else None
    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    with urllib.request.urlopen(req, timeout=10) as res:
        return res.getcode(), json.loads(res.read().decode("utf-8"))

def test():
    # 1. Login as Admin, Gudang, & Assembly
    _, admin_login = request("/login", method="POST", data={"username": "admin", "password": "admin123"})
    admin_token = admin_login["token"]
    _, wh_login = request("/login", method="POST", data={"username": "granada", "password": "Gr4n4d4"})
    wh_token = wh_login["token"]
    _, assy_login = request("/login", method="POST", data={"username": "olif", "password": "0l1f"})
    assy_token = assy_login["token"]

    print("1. Logins: OK (Admin, Granada, Olif)")

    # 2. Reset trays
    request("/admin/clear_trays", method="POST", token=admin_token)

    # 3. Gudang save tray
    _, t_res = request("/gudang/save", method="POST", data={
        "pic_warehouse": "Granada",
        "product_type": "Nanggala P9",
        "assigned_operator": "Kholifaturrohmah",
        "source_document_mo": "MO/LOCK/TEST01",
        "parts": [
            {"part_type": "1", "sn": "001"},
            {"part_type": "12", "sn": "60001"},
            {"part_type": "15", "sn": "J1100001"}
        ]
    }, token=wh_token)
    tray = t_res["data"]
    tray_id = tray.get("tray_id") or tray.get("id")
    tray_code = tray["tray_code"]
    print("2. Created Tray:", tray_code, f"(Tray ID: {tray_id})")

    # 4. Assembly operator saves set #1
    _, assy_res = request("/assembly/save", method="POST", data={
        "tray_id": tray_id,
        "set_number": 1,
        "operator": "Kholifaturrohmah",
        "part15_sn": "J1100001",
        "part1_sn": "001",
        "part12_sn": "60001",
        "status": "Good",
        "reason": ""
    }, token=assy_token)
    print("3. Assembly Save Set #1:", assy_res["ok"])

    # 5. Gudang declines Set #1 during warehouse verification
    _, dec_res = request("/gudang/verify-set", method="POST", data={
        "tray_id": tray_id,
        "set_number": 1,
        "action": "DECLINE",
        "reason": "Barang tidak ditemukan fisik"
    }, token=wh_token)
    print("4. Gudang Declines Set #1:", dec_res["ok"])

    # 6. Admin fetch pending assemblies
    _, pending = request("/admin/pending-assemblies", method="GET", token=admin_token)
    sets = pending.get("data", [])
    print("5. Pending declined sets count:", len(sets))
    declined_set = [s for s in sets if s["tray_id"] == tray_id and s["set_number"] == 1][0]
    print("   Declined set ID:", declined_set["assembly_id"])

    # 7. Admin verifies all 3 parts
    request("/admin/approve-part-direct", method="POST", data={"part_type": "15", "sn": "J1100001", "target_status": "Tersedia"}, token=admin_token)
    request("/admin/approve-part-direct", method="POST", data={"part_type": "1", "sn": "001", "target_status": "Tersedia"}, token=admin_token)
    request("/admin/approve-part-direct", method="POST", data={"part_type": "12", "sn": "60001", "target_status": "Tersedia"}, token=admin_token)
    print("6. All 3 parts verified by Admin to Tersedia")

    # 8. Check database state
    conn = psycopg2.connect(DB_URL)
    with conn.cursor(cursor_factory=RealDictCursor) as cur:
        cur.execute('SELECT id, is_declined, verified_status, is_reregistered FROM assemblies WHERE id = %s', (declined_set['assembly_id'],))
        assy = cur.fetchone()
        print('7. Assembly DB State after full verification:', assy)
        assert assy['is_declined'] == False
        assert assy['verified_status'] == 'ACC'
        assert assy['is_reregistered'] == True

        cur.execute("SELECT part_type, sn, status FROM master_stocks WHERE (part_type='15' AND sn='J1100001') OR (part_type='1' AND sn='001') OR (part_type='12' AND sn='60001')")
        stocks = cur.fetchall()
        print('8. Master stocks status:', stocks)
        for s in stocks:
            assert s['status'] == 'Tersedia'

    print("=== CARD LOCKING & FULL VERIFICATION TEST PASSED 100% ===")

if __name__ == '__main__':
    test()
