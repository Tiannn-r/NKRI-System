import unittest
import json
import os
import sys
import urllib.request

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import get_conn

BASE_URL = 'http://localhost:8000/api'

def req(endpoint, method='GET', data=None, token=None):
    url = BASE_URL + endpoint
    body = json.dumps(data).encode('utf-8') if data else None
    headers = {'Content-Type': 'application/json'}
    if token:
        headers['Authorization'] = f'Bearer {token}'
    r = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(r) as res:
            return res.status, json.loads(res.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        return e.code, json.loads(e.read().decode('utf-8'))

class TestPengembalianPerSet(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        # 1. Login as Gudang
        status, data = req('/login', 'POST', {'username': 'Gudang', 'password': 'wh123', 'force_takeover': True})
        assert status == 200, f"Login failed: {data}"
        cls.token = data['token']
        
        # 2. Create test parts and returned tray
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("DELETE FROM master_stocks WHERE sn IN ('TEST_P15_A', 'TEST_P1_A', 'TEST_P12_A', 'TEST_P15_B', 'TEST_P1_B', 'TEST_P12_B')")
                cur.execute("""
                    INSERT INTO master_stocks (sn, cs, part_type, status)
                    VALUES 
                    ('TEST_P15_A', 'CS15A', '15', 'Supplied'),
                    ('TEST_P1_A', 'CS1A', '1', 'Supplied'),
                    ('TEST_P12_A', 'CS12A', '12', 'Supplied'),
                    ('TEST_P15_B', 'CS15B', '15', 'Supplied'),
                    ('TEST_P1_B', 'CS1B', '1', 'Supplied'),
                    ('TEST_P12_B', 'CS12B', '12', 'Supplied')
                """)
                cur.execute("""
                    INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, session_status, return_reason, return_notified, return_verified, created_at)
                    VALUES ('WH/ASSY/999901', 'Gudang', 'Arini', 'RETURNED_TO_WAREHOUSE', 'Test Return Multi Set', TRUE, FALSE, NOW())
                    RETURNING id
                """)
                cls.tray_id = cur.fetchone()['id']
                
                # Insert 2 sets of parts
                cur.execute("""
                    INSERT INTO parts (tray_id, part_type, sn, status)
                    VALUES 
                    (%s, '15', 'TEST_P15_A', 'RETURNED'),
                    (%s, '1', 'TEST_P1_A', 'RETURNED'),
                    (%s, '12', 'TEST_P12_A', 'RETURNED'),
                    (%s, '15', 'TEST_P15_B', 'RETURNED'),
                    (%s, '1', 'TEST_P1_B', 'RETURNED'),
                    (%s, '12', 'TEST_P12_B', 'RETURNED')
                """, (cls.tray_id, cls.tray_id, cls.tray_id, cls.tray_id, cls.tray_id, cls.tray_id))
                conn.commit()

    def test_01_get_returned_tray_detail(self):
        status, res = req(f'/gudang/returned-tray-detail?tray_id={self.tray_id}', 'GET', token=self.token)
        self.assertEqual(status, 200)
        self.assertTrue(res['ok'])
        self.assertEqual(len(res['sets']), 2)
        self.assertEqual(res['sets'][0]['set_number'], 1)
        self.assertEqual(res['sets'][0]['status'], 'Dikembalikan')
        self.assertFalse(res['sets'][0]['is_verified'])

    def test_02_verify_set_1(self):
        status, res = req('/gudang/verify-return-set', 'POST', {
            'tray_id': self.tray_id,
            'set_number': 1,
            'p15_sn': 'TEST_P15_A',
            'p1_sn': 'TEST_P1_A',
            'p12_sn': 'TEST_P12_A'
        }, token=self.token)
        self.assertEqual(status, 200)
        self.assertTrue(res['ok'])
        self.assertFalse(res['is_fully_verified']) # Set 2 is still pending

        # Check detail again
        status, d_res = req(f'/gudang/returned-tray-detail?tray_id={self.tray_id}', 'GET', token=self.token)
        self.assertTrue(d_res['sets'][0]['is_verified'])
        self.assertEqual(d_res['sets'][0]['status'], 'Tersedia')
        self.assertFalse(d_res['sets'][1]['is_verified'])
        self.assertEqual(d_res['sets'][1]['status'], 'Dikembalikan')

        # Check DB master_stocks for Set 1
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("SELECT sn, status FROM master_stocks WHERE sn IN ('TEST_P15_A', 'TEST_P1_A', 'TEST_P12_A')")
                rows = cur.fetchall()
                for r in rows:
                    self.assertEqual(r['status'], 'Tersedia')

    def test_03_verify_set_2(self):
        status, res = req('/gudang/verify-return-set', 'POST', {
            'tray_id': self.tray_id,
            'set_number': 2,
            'p15_sn': 'TEST_P15_B',
            'p1_sn': 'TEST_P1_B',
            'p12_sn': 'TEST_P12_B'
        }, token=self.token)
        self.assertEqual(status, 200)
        self.assertTrue(res['ok'])
        self.assertTrue(res['is_fully_verified']) # Both sets now verified!

        # Check detail again
        status, d_res = req(f'/gudang/returned-tray-detail?tray_id={self.tray_id}', 'GET', token=self.token)
        self.assertTrue(d_res['tray']['return_verified'])
        self.assertTrue(d_res['sets'][0]['is_verified'])
        self.assertTrue(d_res['sets'][1]['is_verified'])

    @classmethod
    def tearDownClass(cls):
        with get_conn() as conn:
            with conn.cursor() as cur:
                cur.execute("DELETE FROM parts WHERE tray_id = %s", (cls.tray_id,))
                cur.execute("DELETE FROM trays WHERE id = %s", (cls.tray_id,))
                cur.execute("DELETE FROM master_stocks WHERE sn IN ('TEST_P15_A', 'TEST_P1_A', 'TEST_P12_A', 'TEST_P15_B', 'TEST_P1_B', 'TEST_P12_B')")
                conn.commit()

if __name__ == '__main__':
    unittest.main()
