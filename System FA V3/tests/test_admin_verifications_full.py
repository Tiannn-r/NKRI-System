import unittest
import urllib.request
import json

BASE_URL = "http://127.0.0.1:8000"

class TestAdminVerificationFeatures(unittest.TestCase):
    def setUp(self):
        # Login as admin with force takeover
        data = json.dumps({"username": "admin", "password": "admin123", "force_takeover": True}).encode("utf-8")
        req = urllib.request.Request(f"{BASE_URL}/api/login", data=data, headers={"Content-Type": "application/json"})
        res = json.loads(urllib.request.urlopen(req).read().decode("utf-8"))
        self.token = res["token"]
        self.headers = {
            "Content-Type": "application/json",
            "Authorization": f"Bearer {self.token}"
        }

    def test_01_get_pending_verifications(self):
        req = urllib.request.Request(f"{BASE_URL}/api/admin/pending-verifications", headers=self.headers)
        res = json.loads(urllib.request.urlopen(req).read().decode("utf-8"))
        self.assertTrue(res.get("ok"))
        self.assertIsInstance(res.get("data"), list)
        print(f"-> test_01: {len(res['data'])} pending verification requests found.")

    def test_02_get_pending_assemblies(self):
        req = urllib.request.Request(f"{BASE_URL}/api/admin/pending-assemblies", headers=self.headers)
        res = json.loads(urllib.request.urlopen(req).read().decode("utf-8"))
        self.assertTrue(res.get("ok"))
        self.assertIsInstance(res.get("data"), list)
        print(f"-> test_02: {len(res['data'])} pending declined assemblies found.")

if __name__ == "__main__":
    unittest.main()
