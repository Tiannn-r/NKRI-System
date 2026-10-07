"""
Script migrasi data dari PostgreSQL ke SQLite
Jalankan sekali untuk memindahkan semua data existing
"""
import os
import json
import sqlite3
import psycopg2
from psycopg2.extras import RealDictCursor

POSTGRES_URL = os.environ.get("DATABASE_URL", "postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db")
SQLITE_PATH = os.environ.get("DB_PATH", "database.db")

TABLES_IN_ORDER = [
    "users",
    "master_stocks",
    "trays",
    "parts",
    "assemblies",
    "assembly_tests",
    "testing_points",
    "user_sessions",
    "assembly_downtimes",
    "part_audit_logs",
    "re_register_requests",
    "assembly_wo_history",
    "assembly_in_progress",
]

def get_pg_conn():
    return psycopg2.connect(POSTGRES_URL, cursor_factory=RealDictCursor)

def get_sqlite_conn():
    conn = sqlite3.connect(SQLITE_PATH)
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=OFF")  # disable FK during migration
    return conn

def migrate_table(pg_cur, sqlite_conn, table_name):
    try:
        pg_cur.execute(f"SELECT * FROM {table_name}")
        rows = pg_cur.fetchall()
        if not rows:
            print(f"  {table_name}: 0 rows (empty)")
            return 0

        rows_as_dicts = [dict(r) for r in rows]
        columns = list(rows_as_dicts[0].keys())
        placeholders = ", ".join(["?" for _ in columns])
        col_names = ", ".join(columns)

        sqlite_cur = sqlite_conn.cursor()
        sqlite_cur.execute(f"DELETE FROM {table_name}")

        count = 0
        for row in rows_as_dicts:
            values = []
            for col in columns:
                val = row[col]
                if val is None:
                    values.append(None)
                elif isinstance(val, bool):
                    values.append(1 if val else 0)
                elif hasattr(val, 'isoformat'):
                    values.append(val.isoformat())
                elif isinstance(val, (dict, list)):
                    values.append(json.dumps(val))
                else:
                    values.append(val)
            try:
                sqlite_cur.execute(
                    f"INSERT OR REPLACE INTO {table_name} ({col_names}) VALUES ({placeholders})",
                    values
                )
                count += 1
            except Exception as e:
                print(f"    WARNING row {row.get('id')}: {e}")

        sqlite_conn.commit()
        print(f"  {table_name}: {count} rows migrated OK")
        return count
    except Exception as e:
        print(f"  {table_name}: ERROR - {e}")
        return 0

def main():
    print("=" * 60)
    print("MIGRASI DATA: PostgreSQL -> SQLite")
    print("=" * 60)
    print(f"Source: {POSTGRES_URL[:40]}...")
    print(f"Target: {SQLITE_PATH}")
    print()

    if not os.path.exists(SQLITE_PATH):
        print(f"ERROR: SQLite database '{SQLITE_PATH}' belum ada!")
        print("Jalankan 'python app.py' dulu sekali untuk inisialisasi schema, lalu Ctrl+C dan jalankan script ini.")
        return

    try:
        pg_conn = get_pg_conn()
        pg_cur = pg_conn.cursor()
        print("PostgreSQL: Connected OK")
    except Exception as e:
        print(f"ERROR koneksi PostgreSQL: {e}")
        return

    sqlite_conn = get_sqlite_conn()
    print("SQLite: Connected OK")
    print()

    total = 0
    for table in TABLES_IN_ORDER:
        count = migrate_table(pg_cur, sqlite_conn, table)
        total += count

    pg_conn.close()
    sqlite_conn.close()

    print()
    print("=" * 60)
    print(f"SELESAI! Total {total} baris berhasil dipindahkan.")
    print("=" * 60)

if __name__ == "__main__":
    main()
