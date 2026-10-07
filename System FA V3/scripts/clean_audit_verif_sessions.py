import os
import psycopg2
from psycopg2.extras import RealDictCursor

def clean_database():
    db_url = os.environ.get('DATABASE_URL', 'postgresql://postgres:muhammad_ibnufs@localhost:5432/barang_db')
    conn = psycopg2.connect(db_url, cursor_factory=RealDictCursor)
    cur = conn.cursor()

    # 1. Hapus semua data log audit
    cur.execute("DELETE FROM part_audit_logs")
    print("[OK] Data part_audit_logs dikosongkan.")

    cur.execute("DELETE FROM assembly_downtimes")
    print("[OK] Data assembly_downtimes dikosongkan.")

    # 2. Hapus semua data permohonan verifikasi (scrap & decline)
    cur.execute("DELETE FROM re_register_requests")
    print("[OK] Data re_register_requests dikosongkan.")

    # 3. Reset status decline & verifikasi pada tabel rakitan
    cur.execute("""
        UPDATE assemblies 
        SET is_declined = FALSE, 
            verified_status = NULL, 
            declined_reason = NULL,
            is_reregistered = FALSE
    """)
    print("[OK] Status verifikasi rakitan direset.")

    # 4. Hapus semua sesi login pengguna
    cur.execute("DELETE FROM user_sessions")
    print("[OK] Semua sesi pengguna (user_sessions) dikosongkan.")

    # 5. Hapus status perakitan berlangsung
    cur.execute("DELETE FROM assembly_in_progress")
    print("[OK] assembly_in_progress dikosongkan.")

    conn.commit()

    print("\n=== RINGKASAN STATUS TABEL SETELAH DIKOSONGKAN ===")
    check_tables = ['part_audit_logs', 'assembly_downtimes', 're_register_requests', 'user_sessions', 'assembly_in_progress']
    for t in check_tables:
        cur.execute(f"SELECT COUNT(*) FROM {t}")
        cnt = cur.fetchone()['count']
        print(f"* {t}: {cnt} baris")

    cur.execute("SELECT COUNT(*) FROM assemblies WHERE is_declined = TRUE OR verified_status IS NOT NULL")
    declined_assy_cnt = cur.fetchone()['count']
    print(f"* assemblies pending verif: {declined_assy_cnt} baris")

    conn.close()

if __name__ == '__main__':
    clean_database()
