import os
import psycopg2
from psycopg2.extras import RealDictCursor

def inspect():
    db_url = os.environ.get('DATABASE_URL', 'postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db')
    conn = psycopg2.connect(db_url, cursor_factory=RealDictCursor)
    cur = conn.cursor()

    cur.execute("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name")
    tables = [t['table_name'] for t in cur.fetchall()]
    print("Tables in DB:", tables)

    for t in tables:
        cur.execute(f"SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{t}' ORDER BY ordinal_position")
        cols = cur.fetchall()
        print(f"\n=== Table: {t} ===")
        for c in cols:
            print(f"  - {c['column_name']}: {c['data_type']}")

    # Check users roles
    cur.execute("SELECT id, username, role, full_name FROM users")
    users = cur.fetchall()
    print("\n=== Users ===")
    for u in users:
        print(f"  {u['id']}: {u['username']} ({u['role']}) - {u['full_name']}")

    conn.close()

if __name__ == '__main__':
    inspect()
