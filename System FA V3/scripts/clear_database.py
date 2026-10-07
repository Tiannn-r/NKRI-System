"""
Script utilitas untuk mereset/mengosongkan seluruh data Tray & Perakitan,
serta mengembalikan seluruh 18.000 Master Stock menjadi 'Tersedia'.
"""
import sys
import os

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), '..')))
from app import get_conn

def reset_db():
    print("=== MERESET DATA TRAY & PERAKITAN ===")
    with get_conn() as conn:
        with conn.cursor() as cur:
            cur.execute("TRUNCATE TABLE assemblies CASCADE;")
            cur.execute("TRUNCATE TABLE parts CASCADE;")
            cur.execute("TRUNCATE TABLE trays CASCADE;")
            cur.execute("""
                UPDATE master_stocks 
                SET status = 'Tersedia', 
                    allocated_tray_id = NULL, 
                    allocated_at = NULL, 
                    used_at = NULL;
            """)
            conn.commit()
            print("SUKSES: Seluruh data Tray & Perakitan telah dibersihkan.")
            print("SUKSES: Seluruh stok master telah dikembalikan ke status 'Tersedia'.")

if __name__ == "__main__":
    reset_db()
