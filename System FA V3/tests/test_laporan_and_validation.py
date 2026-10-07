import unittest
import json
import os
import sys

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import app, init_db

class TestLaporanAndValidation(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        init_db()
        cls.client = app.test_client()

    def setUp(self):
        # Login admin
        res = self.client.post("/api/login", json={"username": "admin", "password": "password123"})
        self.assertEqual(res.status_code, 200)
        self.token = res.get_json().get("token")
        self.headers = {"Authorization": f"Bearer {self.token}"}

    def test_assembly_mandatory_operator_and_laporan_filters(self):
        # 1. Clear trays first
        self.client.post("/api/admin/clear_trays", headers=self.headers)

        # 2. Create tray in warehouse
        gudang_payload = {
            "pic_warehouse": "Warehouse Staff",
            "product_type": "Nanggala P9",
            "assigned_operator": "",
            "source_document_mo": "MH/MO/000001",
            "parts": [
                {"part_type": "1", "sn": "0001"},
                {"part_type": "12", "sn": "60001"},
                {"part_type": "15", "sn": "J1100001"}
            ]
        }
        res_gudang = self.client.post("/api/gudang/save", json=gudang_payload, headers=self.headers)
        self.assertTrue(res_gudang.get_json().get("ok"))
        tray = res_gudang.get_json()["data"]
        tray_id = tray["id"]

        # 3. Assembly save without operator -> MUST FAIL with 400
        assy_payload_no_op = {
            "tray_id": tray_id,
            "set_number": 1,
            "operator": "",
            "part15_sn": "J1100001",
            "part12_sn": "60001",
            "part1_sn": "0001",
            "status": "Good",
            "reason": ""
        }
        res_fail = self.client.post("/api/assembly/save", json=assy_payload_no_op, headers=self.headers)
        self.assertEqual(res_fail.status_code, 400)
        self.assertIn("Nama Operator Assembly wajib diisi", res_fail.get_json().get("error", ""))

        # 4. Assembly save WITH operator -> SUCCESS and tray assigned_operator is updated
        assy_payload_with_op = dict(assy_payload_no_op, operator="operator1")
        res_pass = self.client.post("/api/assembly/save", json=assy_payload_with_op, headers=self.headers)
        self.assertEqual(res_pass.status_code, 200)
        self.assertTrue(res_pass.get_json().get("ok"))

        # Verify tray assigned_operator is updated to operator1
        res_tray = self.client.get("/api/assembly/tray?code=WH/ASSY/000001", headers=self.headers)
        self.assertEqual(res_tray.get_json()["data"]["tray"]["assigned_operator"], "operator1")

        # 5. Test Laporan Filter API
        res_laporan = self.client.get("/api/laporan?operator=operator1&status=Good", headers=self.headers)
        self.assertEqual(res_laporan.status_code, 200)
        data = res_laporan.get_json()["data"]
        self.assertEqual(len(data), 1)
        self.assertEqual(data[0]["part15_sn"], "J1100001")
        self.assertEqual(data[0]["status"], "Good")

        # Filter with non-matching status
        res_laporan_empty = self.client.get("/api/laporan?status=Scrap", headers=self.headers)
        self.assertEqual(len(res_laporan_empty.get_json()["data"]), 0)

        # Cleanup
        self.client.post("/api/admin/clear_trays", headers=self.headers)
        print("\n=== TEST PASSED: MANDATORY OPERATOR & LAPORAN FILTERS ===")

if __name__ == "__main__":
    unittest.main()
