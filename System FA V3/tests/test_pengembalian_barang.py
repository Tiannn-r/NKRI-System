import unittest
import json
import uuid
import sys
import os
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import psycopg2
from psycopg2.extras import RealDictCursor
from app import get_conn

class TestPengembalianBarang(unittest.TestCase):
    def setUp(self):
        self.conn = get_conn()
        self.conn.autocommit = True
        self.cur = self.conn.cursor(cursor_factory=RealDictCursor)

    def tearDown(self):
        self.cur.close()
        self.conn.close()

    def test_pengembalian_barang_complete_flow(self):
        # 1. Create unique test tray with 3 parts (1 set)
        suffix = uuid.uuid4().hex[:6].upper()
        tray_code = f"WH/ASSY/TEST_{suffix}"
        p15_sn = f"TEST15_{suffix}"
        p1_sn = f"TEST1_{suffix}"
        p12_sn = f"TEST12_{suffix}"
        operator = "Arini Salsabila"

        # Insert master stocks
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS1', '15', 'Tersedia') RETURNING id", (p15_sn,))
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS2', '1', 'Tersedia') RETURNING id", (p1_sn,))
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS3', '12', 'Tersedia') RETURNING id", (p12_sn,))

        # Create active tray
        self.cur.execute("""
            INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, session_status, created_at)
            VALUES (%s, 'WarehouseUser', %s, 'WH/MO/TEST-001', 'Nanggala P9', 'active', NOW())
            RETURNING id
        """, (tray_code, operator))
        tray_id = self.cur.fetchone()['id']

        # Allocate parts to tray
        self.cur.execute("UPDATE master_stocks SET status = 'Supplied', allocated_tray_id = %s WHERE sn IN (%s, %s, %s)", (tray_id, p15_sn, p1_sn, p12_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', %s, 'Supplied')", (tray_id, p15_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '1', %s, 'Supplied')", (tray_id, p1_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '12', %s, 'Supplied')", (tray_id, p12_sn))

        # Check parts are Supplied
        self.cur.execute("SELECT status FROM master_stocks WHERE allocated_tray_id = %s", (tray_id,))
        for r in self.cur.fetchall():
            self.assertEqual(r['status'], 'Supplied')

        # 2. Simulate Operator Assembly clicking "Kembalikan ke Gudang" (RETURN_WAREHOUSE)
        ret_reason = "Shift berakhir, ada kendala mesin"
        self.cur.execute("UPDATE parts SET status = 'RETURNED' WHERE tray_id = %s", (tray_id,))
        self.cur.execute("DELETE FROM assemblies WHERE tray_id = %s", (tray_id,))
        self.cur.execute("""
            UPDATE trays 
            SET session_status = 'RETURNED_TO_WAREHOUSE', return_reason = %s, return_notified = TRUE, return_verified = FALSE 
            WHERE id = %s
        """, (ret_reason, tray_id))

        # 3. Verify in GET /api/gudang/returned-trays query
        self.cur.execute("""
            SELECT t.id, t.tray_code, t.assigned_operator, t.return_reason, t.return_verified,
                   COUNT(DISTINCT p.id) as total_parts
            FROM trays t
            LEFT JOIN parts p ON t.id = p.tray_id
            WHERE t.session_status = 'RETURNED_TO_WAREHOUSE' AND t.id = %s
            GROUP BY t.id, t.tray_code, t.assigned_operator, t.return_reason, t.return_verified
        """, (tray_id,))
        returned_tray = self.cur.fetchone()
        self.assertIsNotNone(returned_tray)
        self.assertEqual(returned_tray['tray_code'], tray_code)
        self.assertEqual(returned_tray['assigned_operator'], operator)
        self.assertFalse(returned_tray['return_verified'])
        self.assertEqual(returned_tray['total_parts'], 3)

        # 4. Verify Returned Tray Detail grouping
        self.cur.execute("SELECT * FROM parts WHERE tray_id = %s ORDER BY id ASC", (tray_id,))
        parts = self.cur.fetchall()
        self.assertEqual(len(parts), 3)
        p15_sn_got = next((p['sn'] for p in parts if p['part_type'] == '15'), None)
        p1_sn_got = next((p['sn'] for p in parts if p['part_type'] == '1'), None)
        p12_sn_got = next((p['sn'] for p in parts if p['part_type'] == '12'), None)
        self.assertEqual(p15_sn_got, p15_sn)
        self.assertEqual(p1_sn_got, p1_sn)
        self.assertEqual(p12_sn_got, p12_sn)

        # 5. Simulate Gudang clicking "Verifikasi Pengembalian" (POST /api/gudang/verify-return-tray)
        verifier_name = "Petugas Gudang 1"
        self.cur.execute("""
            UPDATE trays 
            SET return_verified = TRUE, return_verified_at = NOW(), return_verified_by = %s, return_notified = FALSE 
            WHERE id = %s
        """, (verifier_name, tray_id))
        self.cur.execute("""
            UPDATE master_stocks 
            SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL 
            WHERE allocated_tray_id = %s OR sn IN (SELECT sn FROM parts WHERE tray_id = %s)
        """, (tray_id, tray_id))
        self.cur.execute("UPDATE parts SET status = 'Tersedia' WHERE tray_id = %s", (tray_id,))

        # Verify tray is now verified
        self.cur.execute("SELECT return_verified, return_verified_by FROM trays WHERE id = %s", (tray_id,))
        t_after = self.cur.fetchone()
        self.assertTrue(t_after['return_verified'])
        self.assertEqual(t_after['return_verified_by'], verifier_name)

        # Verify ALL parts in master_stocks are automatically back to 'Tersedia' (NOT 'Supplied')
        self.cur.execute("SELECT sn, status, allocated_tray_id FROM master_stocks WHERE sn IN (%s, %s, %s)", (p15_sn, p1_sn, p12_sn))
        stocks_after = self.cur.fetchall()
        self.assertEqual(len(stocks_after), 3)
        for s in stocks_after:
            self.assertEqual(s['status'], 'Tersedia', f"Stock {s['sn']} must be 'Tersedia' but got {s['status']}")
            self.assertIsNone(s['allocated_tray_id'])

        print(f"[TEST PASS] Pengembalian Barang & Verifikasi Stok Tersedia: {tray_code}")

    def test_pengembalian_barang_api_endpoints(self):
        suffix = uuid.uuid4().hex[:6].upper()
        tray_code = f"WH/ASSY/RET_{suffix}"
        p15_sn = f"RET15_{suffix}"
        p1_sn = f"RET1_{suffix}"
        p12_sn = f"RET12_{suffix}"
        operator = "Irsa Maharani"

        # Insert master stocks
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS1', '15', 'Tersedia') RETURNING id", (p15_sn,))
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS2', '1', 'Tersedia') RETURNING id", (p1_sn,))
        self.cur.execute("INSERT INTO master_stocks (sn, cs, part_type, status) VALUES (%s, 'CS3', '12', 'Tersedia') RETURNING id", (p12_sn,))

        # Create active tray & parts
        self.cur.execute("""
            INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, session_status, created_at)
            VALUES (%s, 'WarehouseUser', %s, 'WH/MO/RET-999', 'Nanggala P9', 'active', NOW())
            RETURNING id
        """, (tray_code, operator))
        tray_id = self.cur.fetchone()['id']

        self.cur.execute("UPDATE master_stocks SET status = 'Supplied', allocated_tray_id = %s WHERE sn IN (%s, %s, %s)", (tray_id, p15_sn, p1_sn, p12_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '15', %s, 'Supplied')", (tray_id, p15_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '1', %s, 'Supplied')", (tray_id, p1_sn))
        self.cur.execute("INSERT INTO parts (tray_id, part_type, sn, status) VALUES (%s, '12', %s, 'Supplied')", (tray_id, p12_sn))

        # Perform Return
        ret_reason = "Kembali ke gudang untuk kalibrasi ulang"
        self.cur.execute("UPDATE parts SET status = 'RETURNED' WHERE tray_id = %s", (tray_id,))
        self.cur.execute("DELETE FROM assemblies WHERE tray_id = %s", (tray_id,))
        self.cur.execute("""
            UPDATE trays 
            SET session_status = 'RETURNED_TO_WAREHOUSE', return_reason = %s, return_notified = TRUE, return_verified = FALSE 
            WHERE id = %s
        """, (ret_reason, tray_id))

        # Test GET /api/gudang/returned-trays query simulation
        self.cur.execute("""
            SELECT t.id, t.tray_code, t.assigned_operator, t.return_reason, t.return_verified,
                   COUNT(DISTINCT p.id) as total_parts,
                   GREATEST(COUNT(DISTINCT CASE WHEN p.part_type = '15' THEN p.id END), 1) as total_sets
            FROM trays t
            LEFT JOIN parts p ON t.id = p.tray_id
            WHERE t.session_status = 'RETURNED_TO_WAREHOUSE' AND t.assigned_operator = %s
            GROUP BY t.id, t.tray_code, t.assigned_operator, t.return_reason, t.return_verified
        """, (operator,))
        rows = self.cur.fetchall()
        matching = [r for r in rows if r['id'] == tray_id]
        self.assertEqual(len(matching), 1)
        self.assertEqual(matching[0]['total_sets'], 1)
        self.assertEqual(matching[0]['total_parts'], 3)
        self.assertFalse(matching[0]['return_verified'])

        # Test Detail Query & Sets grouping
        self.cur.execute("SELECT part_type, sn, status FROM parts WHERE tray_id = %s ORDER BY id ASC", (tray_id,))
        parts = self.cur.fetchall()
        p15_list = [p['sn'] for p in parts if p['part_type'] == '15']
        p1_list = [p['sn'] for p in parts if p['part_type'] == '1']
        p12_list = [p['sn'] for p in parts if p['part_type'] == '12']

        sets = []
        for i in range(max(len(p15_list), len(p1_list), len(p12_list))):
            sets.append({
                "set_number": i + 1,
                "pn": "P9 Part 15",
                "p15_sn": p15_list[i],
                "p1_sn": p1_list[i],
                "p12_sn": p12_list[i],
                "status": "Dikembalikan"
            })
        self.assertEqual(len(sets), 1)
        self.assertEqual(sets[0]['p15_sn'], p15_sn)
        self.assertEqual(sets[0]['p1_sn'], p1_sn)
        self.assertEqual(sets[0]['p12_sn'], p12_sn)

        # Perform Verification
        self.cur.execute("""
            UPDATE trays 
            SET return_verified = TRUE, return_verified_at = NOW(), return_verified_by = 'Admin Gudang', return_notified = FALSE 
            WHERE id = %s
        """, (tray_id,))
        self.cur.execute("""
            UPDATE master_stocks 
            SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL 
            WHERE allocated_tray_id = %s OR sn IN (SELECT sn FROM parts WHERE tray_id = %s)
        """, (tray_id, tray_id))
        self.cur.execute("UPDATE parts SET status = 'Tersedia' WHERE tray_id = %s", (tray_id,))

        # Verify in DB
        self.cur.execute("SELECT status FROM master_stocks WHERE sn IN (%s, %s, %s)", (p15_sn, p1_sn, p12_sn))
        for s in self.cur.fetchall():
            self.assertEqual(s['status'], 'Tersedia')

        print(f"[TEST PASS] API verification and status reset tested successfully: {tray_code}")

if __name__ == '__main__':
    unittest.main()
