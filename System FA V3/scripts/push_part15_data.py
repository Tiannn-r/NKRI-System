import os
import sys
import json
import sqlite3
import argparse

# Path resolving
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_DB_LOCATIONS = [
    os.environ.get("DB_PATH", ""),
    os.path.join(SCRIPT_DIR, "database.db"),
    os.path.join(SCRIPT_DIR, "..", "database.db"),
    os.path.abspath("database.db")
]

def find_database(custom_path=None):
    if custom_path:
        if os.path.exists(custom_path):
            return os.path.abspath(custom_path)
        else:
            print(f"[!] File database yang ditentukan tidak ditemukan: {custom_path}")
            sys.exit(1)
    for loc in DEFAULT_DB_LOCATIONS:
        if loc and os.path.exists(loc):
            return os.path.abspath(loc)
    # Default fallback to parent database.db
    return os.path.abspath(os.path.join(SCRIPT_DIR, "..", "database.db"))

def load_part15_data():
    json_path = os.path.join(SCRIPT_DIR, "part15_data.json")
    txt_path = os.path.join(SCRIPT_DIR, "part15_sn_list.txt")

    if os.path.exists(json_path):
        with open(json_path, "r", encoding="utf-8") as f:
            data = json.load(f)
            return data
    elif os.path.exists(txt_path):
        data = []
        with open(txt_path, "r", encoding="utf-8") as f:
            for line in f:
                parts = line.strip().split()
                if parts:
                    sn = parts[0]
                    cs = parts[1] if len(parts) > 1 else sn[0]
                    data.append({"sn": sn, "cs": cs})
        return data
    else:
        print("[!] File part15_data.json atau part15_sn_list.txt tidak ditemukan di folder scripts/")
        sys.exit(1)

def push_part15(db_path, reset_mode=False):
    print("=" * 60)
    print(" SCRIPT PUSH / IMPORT DATA SERIAL NUMBER PART 15 (P15)")
    print("=" * 60)
    print(f"[*] Lokasi Database: {db_path}")

    # Load 5900 data
    items = load_part15_data()
    print(f"[*] Total Serial Number P15 dalam daftar: {len(items)} items")
    print(f"    - Pertama : {items[0]['sn']} (CS: {items[0]['cs']})")
    print(f"    - Terakhir: {items[-1]['sn']} (CS: {items[-1]['cs']})")

    conn = sqlite3.connect(db_path)
    cur = conn.cursor()

    # Pastikan tabel master_stocks ada
    cur.execute("""
        CREATE TABLE IF NOT EXISTS master_stocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sn TEXT NOT NULL,
            cs TEXT,
            part_type TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'Tersedia',
            allocated_tray_id INTEGER,
            allocated_at TEXT,
            used_at TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            UNIQUE(sn, part_type)
        )
    """)
    cur.execute("CREATE INDEX IF NOT EXISTS idx_master_stocks_type_status ON master_stocks(part_type, status);")

    if reset_mode:
        print("\n[!] MODE RESET DIAKTIFKAN: Menghapus seluruh data Part 15 lama di master_stocks...")
        cur.execute("DELETE FROM master_stocks WHERE part_type = '15'")
        conn.commit()
        print("[+] Data lama Part 15 berhasil dihapus.")

    # Cek data existing
    cur.execute("SELECT sn FROM master_stocks WHERE part_type = '15'")
    existing_sns = set(r[0] for r in cur.fetchall())
    print(f"\n[*] Jumlah Part 15 yang sudah ada di database saat ini: {len(existing_sns)} items")

    to_insert = []
    skipped_count = 0

    for item in items:
        sn = item["sn"]
        cs = item["cs"]
        if sn in existing_sns:
            skipped_count += 1
        else:
            to_insert.append((sn, cs, '15', 'Tersedia'))

    if to_insert:
        print(f"[*] Memasukkan {len(to_insert)} items Part 15 baru ke master_stocks...")
        cur.executemany("""
            INSERT OR IGNORE INTO master_stocks (sn, cs, part_type, status)
            VALUES (?, ?, ?, ?)
        """, to_insert)
        conn.commit()
        print(f"[+] Berhasil menambahkan {len(to_insert)} items baru!")
    else:
        print("[+] Semua 5,900 serial number P15 sudah lengkap ada di database.")

    # Rekapitulasi akhir
    cur.execute("""
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'Tersedia' THEN 1 END) as tersedia,
            COUNT(CASE WHEN status != 'Tersedia' THEN 1 END) as terpakai
        FROM master_stocks
        WHERE part_type = '15'
    """)
    res = cur.fetchone()

    cur.execute("""
        SELECT status, COUNT(*) 
        FROM master_stocks 
        WHERE part_type = '15' 
        GROUP BY status 
        ORDER BY COUNT(*) DESC
    """)
    breakdown = cur.fetchall()

    print("\n" + "=" * 60)
    print(" HASIL REKAPITULASI MASTER STOCKS PART 15")
    print("=" * 60)
    print(f"Total Part 15 di DB  : {res[0]} items")
    print(f"Item yang diabaikan (sudah ada & status terjaga): {skipped_count} items")
    print(f"Item baru ditambahkan: {len(to_insert)} items")
    print("\nRincian Status Part 15 di Database:")
    for b in breakdown:
        print(f"  - Status '{b[0]}': {b[1]} items")
    print("=" * 60)
    print("[SUCCESS] Data P15 berhasil dipush ke database!")
    conn.close()

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Push data serial number Part 15 (P15) ke database.")
    parser.add_argument("--db", type=str, default=None, help="Path ke file database.db (opsional, default auto-detect)")
    parser.add_argument("--reset", action="store_true", help="Hapus data P15 lama sebelum import (HATI-HATI: hanya gunakan jika ingin reset total)")
    args = parser.parse_args()

    target_db = find_database(args.db)
    push_part15(target_db, reset_mode=args.reset)
