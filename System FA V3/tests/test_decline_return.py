import unittest
import urllib.request
import json
import uuid
import sys
import os

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
BASE_URL = "http://localhost:8000"

class TestDeclineReturn(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        login_data = json.dumps({"username": "admin", "password": "admin123", "force_takeover": True}).encode()
        req = urllib.request.Request(f"{BASE_URL}/api/login", data=login_data, headers={"Content-Type": "application/json"})
        resp = urllib.request.urlopen(req)
        cls.token = json.loads(resp.read().decode())["token"]

    def api_post(self, path, payload):
        req = urllib.request.Request(
            f"{BASE_URL}/api{path}",
            data=json.dumps(payload).encode(),
            headers={"Content-Type": "application/json", "Authorization": f"Bearer {self.token}"}
        )
        resp = urllib.request.urlopen(req)
        return json.loads(resp.read().decode())

    def api_get(self, path):
        req = urllib.request.Request(
            f"{BASE_URL}/api{path}",
            headers={"Authorization": f"Bearer {self.token}"}
        )
        resp = urllib.request.urlopen(req)
        return json.loads(resp.read().decode())

    def test_decline_return_flow(self):
        sn_suffix = str(uuid.uuid4())[:4]
        p15_sn = f"TEST_DEC_{sn_suffix}_15"
        p1_sn = f"T1_{sn_suffix}"
        p12_sn = f"T12_{sn_suffix}"

        from app import get_conn
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("INSERT INTO master_stocks (part_type, sn, status) VALUES ('15', %s, 'Tersedia'), ('1', %s, 'Tersedia'), ('12', %s, 'Tersedia') ON CONFLICT DO NOTHING", (p15_sn, p1_sn, p12_sn))
                cur.execute("""
                    INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, session_status, return_reason, created_at)
                    VALUES (%s, 'Gudang', 'Arini', 'WH/MO/999888', 'Nanggala P9', 'RETURNED_TO_WAREHOUSE', 'Barang dikembalikan perakitan', NOW())
                    RETURNING id
                """, (f"WH/ASSY/DEC_{sn_suffix}",))
                tray_id = cur.fetchone()['id']
                cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', %s, 'RETURNED'), (%s, '1', %s, 'RETURNED'), (%s, '12', %s, 'RETURNED')", (tray_id, p15_sn, tray_id, p1_sn, tray_id, p12_sn))
                conn.commit()

        # 1. Decline only Part 12
        decline_res = self.api_post("/gudang/decline-return-set", {
            "tray_id": tray_id,
            "set_number": 1,
            "declined_parts": ["12"],
            "p15_sn": p15_sn,
            "p1_sn": p1_sn,
            "p12_sn": p12_sn,
            "reason": "Part 12 hilang saat serah terima pengembalian"
        })
        self.assertTrue(decline_res.get("ok"))

        # 2. Check returned tray detail
        detail_res = self.api_get(f"/gudang/returned-tray-detail?tray_id={tray_id}")
        self.assertTrue(detail_res.get("ok"))
        sets = detail_res.get("sets", [])
        self.assertEqual(len(sets), 1)
        s1 = sets[0]
        self.assertTrue(s1.get("is_declined"))
        self.assertEqual(s1.get("status"), "Declined")
        self.assertIn("12", s1.get("declined_parts", []))

        # 3. Check admin pending requests
        admin_reqs = self.api_get("/admin/pending-verifications")
        self.assertTrue(admin_reqs.get("ok"))
        found_req = next((r for r in admin_reqs.get("data", []) if r["sn"] == p12_sn and r["status"] == "PENDING"), None)
        self.assertIsNotNone(found_req)

        # 4. Approve re-register as Admin
        appr_res = self.api_post("/admin/approve-re-register", {
            "request_id": found_req["id"],
            "action": "APPROVE",
            "target_status": "Tersedia",
            "notes": "Part 12 sudah diganti baru"
        })
        self.assertTrue(appr_res.get("ok"))

if __name__ == "__main__":
    unittest.main()
