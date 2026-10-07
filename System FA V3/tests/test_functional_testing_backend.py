import unittest
import json
import os
import psycopg2
from psycopg2.extras import RealDictCursor
import urllib.request
import urllib.parse
from app import ensure_db_schema

class TestFunctionalTestingBackend(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        ensure_db_schema()
        cls.db_url = os.environ.get('DATABASE_URL', 'postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db')

    def test_01_schema_exists(self):
        conn = psycopg2.connect(self.db_url, cursor_factory=RealDictCursor)
        cur = conn.cursor()
        cur.execute("SELECT table_name FROM information_schema.tables WHERE table_name = 'assembly_tests'")
        self.assertIsNotNone(cur.fetchone(), "Table assembly_tests should exist")

        cur.execute("SELECT column_name FROM information_schema.columns WHERE table_name = 'assemblies' AND column_name = 'qc_status'")
        self.assertIsNotNone(cur.fetchone(), "assemblies.qc_status should exist")
        conn.close()

if __name__ == '__main__':
    unittest.main()
