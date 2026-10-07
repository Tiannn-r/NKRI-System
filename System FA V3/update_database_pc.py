import sqlite3
import os

DB_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'database.db')

def main():
    if not os.path.exists(DB_PATH):
        print(f"[!] Database tidak ditemukan di: {DB_PATH}")
        print("[!] Pastikan file script ini berada di folder yang sama dengan database.db")
        return

    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()
    print(f"[*] Terhubung ke database: {DB_PATH}")

    try:
        print("\n======================================================")
        print("  MIGRASI DATABASE CLOUDINARY & DELIVERY ORDER")
        print("======================================================")

        # 1. Tabel Pengaturan Cloudinary
        print("\n[1/2] Memastikan Tabel cloud_settings...")
        cur.execute("""
            CREATE TABLE IF NOT EXISTS cloud_settings (
                id INTEGER PRIMARY KEY DEFAULT 1,
                cloud_name TEXT DEFAULT '',
                api_key TEXT DEFAULT '',
                api_secret TEXT DEFAULT '',
                folder TEXT DEFAULT 'nkri-pl',
                updated_at TEXT DEFAULT (datetime('now', 'localtime'))
            )
        """)
        cur.execute("INSERT OR IGNORE INTO cloud_settings (id) VALUES (1)")
        print(" -> Tabel cloud_settings OK")

        # 2. Kolom Cloudinary pada delivery_orders
        print("\n[2/2] Memeriksa Kolom Cloudinary pada Tabel delivery_orders...")
        cur.execute("PRAGMA table_info(delivery_orders)")
        existing_cols = [c['name'] for c in cur.fetchall()]
        cloud_cols = [
            ("cloud_public_id", "TEXT"),
            ("cloud_pdf_url", "TEXT"),
            ("cloud_version", "TEXT"),
            ("cloud_uploaded_at", "TEXT")
        ]
        for col_name, col_type in cloud_cols:
            if col_name not in existing_cols:
                cur.execute(f"ALTER TABLE delivery_orders ADD COLUMN {col_name} {col_type}")
                print(f" -> Kolom delivery_orders.{col_name} berhasil ditambahkan")
            else:
                print(f" -> Kolom delivery_orders.{col_name} sudah ada")

        # 3. Index Performa Delivery Orders (Aman)
        cur.execute("CREATE INDEX IF NOT EXISTS idx_do_number ON delivery_orders(do_number)")
        cur.execute("CREATE INDEX IF NOT EXISTS idx_pl_do_id ON packing_lists(delivery_order_id)")
        cur.execute("CREATE INDEX IF NOT EXISTS idx_plb_do_id ON packing_list_boxes(delivery_order_id)")

        conn.commit()
        print("\n[+] SEMUA PERUBAHAN CLOUDINARY BERHASIL DISIMPAN KE DATABASE!")

        # Verifikasi
        print("\n------------------ HASIL VERIFIKASI ------------------")
        cur.execute("PRAGMA table_info(delivery_orders)")
        cols_now = [c['name'] for c in cur.fetchall()]
        verified = [col for col, _ in cloud_cols if col in cols_now]
        print(f"Kolom Cloudinary aktif : {verified}")
        cur.execute("SELECT id, cloud_name, folder, updated_at FROM cloud_settings WHERE id = 1")
        cs = cur.fetchone()
        print(f"Pengaturan Cloud       : {dict(cs) if cs else 'Belum ada data'}")
        print("------------------------------------------------------\n")

    except Exception as e:
        conn.rollback()
        print(f"\n[X] Terjadi error: {e}")
        print("[X] Perubahan dibatalkan.")
    finally:
        conn.close()

if __name__ == '__main__':
    main()
