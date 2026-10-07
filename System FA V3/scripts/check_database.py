"""
Script utilitas untuk memeriksa status database PostgreSQL dan ringkasan stok master.
"""
import sys
import os

# Menambahkan direktori root ke sys.path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import get_conn

def check_db():
    print("=== MEMERIKSA STATUS DATABASE BARANG ===")
    with get_conn() as conn:
        with conn.cursor() as cur:
            # 1. Users
            cur.execute("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role")
            users = cur.fetchall()
            print("\n[1] Pengguna Terdaftar:")
            for u in users:
                print(f"    - Role {u['role']}: {u['count']} pengguna")

            # 2. Master Stocks
            cur.execute("""
                SELECT part_type, 
                       COUNT(*) as total, 
                       COUNT(CASE WHEN status='Tersedia' THEN 1 END) as available,
                       COUNT(CASE WHEN status='Supplied' THEN 1 END) as supplied,
                       COUNT(CASE WHEN status='Done' THEN 1 END) as done
                FROM master_stocks 
                GROUP BY part_type 
                ORDER BY part_type
            """)
            stocks = cur.fetchall()
            print("\n[2] Ringkasan Stok Master (18.000 Items):")
            for s in stocks:
                print(f"    - Part {s['part_type']}: Total {s['total']} (Tersedia: {s['available']}, Supplied: {s['supplied']}, Done: {s['done']})")

            # 3. Trays & Assemblies
            cur.execute("SELECT COUNT(*) as count FROM trays")
            trays_count = cur.fetchone()['count']
            cur.execute("SELECT status, COUNT(*) as count FROM assemblies GROUP BY status ORDER BY status")
            assy_stats = cur.fetchall()
            print(f"\n[3] Data Operasional:")
            print(f"    - Total Tray Aktif: {trays_count}")
            if assy_stats:
                print(f"    - Status Perakitan:")
                for a in assy_stats:
                    print(f"      * {a['status']}: {a['count']} set")
            else:
                print(f"    - Belum ada perakitan yang tersimpan.")

if __name__ == "__main__":
    check_db()
