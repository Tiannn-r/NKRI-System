import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import get_conn

def migrate_part1():
    print("=== MENGUPDATE PART NUMBER PART 1 (001-999: 3 DIGIT, 1000-6000: 4 DIGIT) ===")
    with get_conn() as conn:
        with conn.cursor() as cur:
            # Check existing Part 1 count
            cur.execute("SELECT COUNT(*) as cnt FROM master_stocks WHERE part_type = '1'")
            cnt = cur.fetchone()['cnt']
            print(f"Total Part 1 saat ini di master_stocks: {cnt}")

            # Re-seed or update Part 1 in master_stocks
            # First, update numbers 1 to 999 from '0001'..'0999' to '001'..'999'
            for i in range(1, 1000):
                old_4 = f"{i:04d}"
                new_3 = f"{i:03d}"
                cur.execute("""
                    UPDATE master_stocks 
                    SET sn = %s 
                    WHERE part_type = '1' AND (sn = %s OR sn = %s)
                """, (new_3, old_4, str(i)))

                # Update parts table if exists
                cur.execute("""
                    UPDATE parts 
                    SET sn = %s 
                    WHERE part_type = '1' AND (sn = %s OR sn = %s)
                """, (new_3, old_4, str(i)))

                # Update assemblies table if exists
                cur.execute("""
                    UPDATE assemblies 
                    SET part1_sn = %s 
                    WHERE part1_sn = %s OR part1_sn = %s
                """, (new_3, old_4, str(i)))

            # For 1000 to 6000, ensure they are 4 digits '1000'..'6000'
            for i in range(1000, 6001):
                sn_4 = f"{i:04d}"
                cur.execute("""
                    UPDATE master_stocks 
                    SET sn = %s 
                    WHERE part_type = '1' AND sn = %s
                """, (sn_4, str(i)))

            conn.commit()

            # Verify sample from database
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '1' ORDER BY id LIMIT 5")
            sample_start = [r['sn'] for r in cur.fetchall()]
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '1' AND id >= 995 ORDER BY id LIMIT 10")
            sample_mid = [r['sn'] for r in cur.fetchall()]
            cur.execute("SELECT sn FROM master_stocks WHERE part_type = '1' ORDER BY id DESC LIMIT 5")
            sample_end = [r['sn'] for r in cur.fetchall()]

            print(f"Sample Awal Part 1: {sample_start}")
            print(f"Sample Transisi 999 -> 1000: {sample_mid}")
            print(f"Sample Akhir Part 1: {sample_end}")
            print("=== UPDATE PART 1 SELESAI DENGAN SUKSES! ===")

if __name__ == "__main__":
    migrate_part1()
