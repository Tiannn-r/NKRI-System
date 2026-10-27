#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script untuk Menambahkan Data Perakitan Part 12 Manual (Double Marking) ke Database
Sistem Traceability & Inventory Final Assembly P9

Data yang ditambahkan:
1. P1: 26371  | P12: 62607 | P15: K1103581 (CS: K)
2. P1: 38821  | P12: 70115 | P15: N1103937 (CS: N)
3. P1: 01271  | P12: 70121 | P15: V1103958 (CS: V)
4. P1: 55851  | P12: 62597 | P15: N1103925 (CS: N)
5. P1: 25841  | P12: 62610 | P15: X1103906 (CS: X)
6. P1: 40481  | P12: 62601 | P15: Z1103555 (CS: Z)
7. P1: 38281  | P12: 70127 | P15: C1102970 (CS: C)

Kondisi:
- Sudah lolos Perakitan (status = 'Good')
- Sudah lolos Uji Fungsional 1 / TF 1 (assy_test_status = 'Good')
- Posisi saat ini: Di TF 2 (siap/antre uji QC 'PENDING_QC', atau sudah lolos TF 2 jika dipilih)
"""

import os
import sys
import shutil
import sqlite3
import argparse
import json
from datetime import datetime

# Path default database
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.environ.get("DB_PATH", os.path.join(BASE_DIR, "database.db"))

# Data Part yang akan dimasukkan
MANUAL_ITEMS = [
    {"part1": "26371", "part12": "62607", "part15": "K1103581", "cs": "K"},
    {"part1": "38821", "part12": "70115", "part15": "N1103937", "cs": "N"},
    {"part1": "01271", "part12": "70121", "part15": "V1103958", "cs": "V"},
    {"part1": "55851", "part12": "62597", "part15": "N1103925", "cs": "N"},
    {"part1": "25841", "part12": "62610", "part15": "X1103906", "cs": "X"},
    {"part1": "40481", "part12": "62601", "part15": "Z1103555", "cs": "Z"},
    {"part1": "38281", "part12": "70127", "part15": "C1102970", "cs": "C"},
]

def backup_database(db_path):
    """Membuat salinan cadangan database sebelum perubahan data."""
    if not os.path.exists(db_path):
        return None
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_path = f"{db_path}.backup_double_marking_{timestamp}"
    shutil.copy2(db_path, backup_path)
    return backup_path

def get_next_tray_info(cur):
    """Mencari nomor urut tray berikutnya."""
    cur.execute("SELECT tray_code FROM trays ORDER BY id DESC LIMIT 50")
    rows = cur.fetchall()
    max_num = 0
    import re
    for r in rows:
        code = r[0] or ''
        nums = re.findall(r'\d+', code)
        if nums:
            val = int(nums[-1])
            if val > max_num:
                max_num = val
    next_num = max_num + 1
    tray_code = f"WH/ASSY/{next_num:06d}"
    mo_code = f"WH/WO/{next_num:06d}"
    return next_num, tray_code, mo_code

def format_doc_number(tray_code, stage):
    """Format nomor dokumen TF1 / TF2 sesuai kaidah penomoran sistem FA."""
    import re
    digits = re.findall(r'\d+', tray_code or '')
    seq = digits[-1].zfill(6) if digits else '000001'
    if stage.upper() in ['ASSY', 'TF1']:
        return f"Assy/QC/{seq}"
    elif stage.upper() in ['QC', 'TF2']:
        return f"WH/QC/{seq}"
    return f"DOC/{seq}"

def run_insertion(db_path, tray_id=None, tray_code=None, operator="Administrator NKRI", 
                  tester_assy="Administrator NKRI", tester_qc="QC Tester",
                  tf2_status="PENDING_QC", dry_run=False):
    """
    Eksekusi penambahan data ke database SQLite.
    """
    if not os.path.exists(db_path):
        print(f"[ERROR] File database tidak ditemukan di: {db_path}")
        return False

    print("=" * 68)
    print("  PENAMBAHAN DATA MANUAL PART 12 (DOUBLE MARKING) KE DATABASE")
    print("=" * 68)
    print(f"[*] Target Database : {db_path}")
    print(f"[*] Total Data      : {len(MANUAL_ITEMS)} unit set")
    print(f"[*] Operator Assy   : {operator}")
    print(f"[*] Tester TF 1     : {tester_assy}")
    print(f"[*] Status TF 2     : {tf2_status} ({'Antrean Uji QC' if tf2_status == 'PENDING_QC' else 'Sudah Lolos QC'})")
    print(f"[*] Mode Eksekusi   : {'SIMULASI (DRY-RUN)' if dry_run else 'APPLY KE DATABASE'}")
    print("-" * 68)

    if not dry_run:
        b_path = backup_database(db_path)
        if b_path:
            print(f"[+] Backup database otomatis dibuat di:\n    {b_path}")
            print("-" * 68)

    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    try:
        now_str = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        # -------------------------------------------------------------
        # 1. Tentukan atau Buat Tray
        # -------------------------------------------------------------
        start_set_num = 1
        if tray_id:
            cur.execute("SELECT id, tray_code FROM trays WHERE id = ?", (tray_id,))
            t_row = cur.fetchone()
            if not t_row:
                raise Exception(f"Tray ID {tray_id} tidak ditemukan di tabel trays!")
            active_tray_id = t_row['id']
            active_tray_code = t_row['tray_code']
            cur.execute("SELECT COALESCE(MAX(set_number), 0) FROM assemblies WHERE tray_id = ?", (active_tray_id,))
            start_set_num = cur.fetchone()[0] + 1
            print(f"[1/5] Menggunakan Tray yang sudah ada: ID {active_tray_id} ({active_tray_code}), mulai Set #{start_set_num}")
        elif tray_code:
            cur.execute("SELECT id, tray_code FROM trays WHERE tray_code = ?", (tray_code,))
            t_row = cur.fetchone()
            if t_row:
                active_tray_id = t_row['id']
                active_tray_code = t_row['tray_code']
                cur.execute("SELECT COALESCE(MAX(set_number), 0) FROM assemblies WHERE tray_id = ?", (active_tray_id,))
                start_set_num = cur.fetchone()[0] + 1
                print(f"[1/5] Menggunakan Tray yang sudah ada: ID {active_tray_id} ({active_tray_code}), mulai Set #{start_set_num}")
            else:
                next_num, _, mo_code = get_next_tray_info(cur)
                active_tray_code = tray_code
                cur.execute("""
                    INSERT INTO trays (
                        tray_code, pic_warehouse, assigned_operator, source_document_mo,
                        product_type, status, verified_by_gudang, verified_at, verified_by_user,
                        session_status, created_at
                    ) VALUES (
                        ?, 'Gudang', ?, ?,
                        'Nanggala P9', 'active', 1, ?, 'Administrator NKRI',
                        'active', ?
                    )
                """, (active_tray_code, operator, mo_code, now_str, now_str))
                active_tray_id = cur.lastrowid
                print(f"[1/5] Membuat Tray baru khusus: ID {active_tray_id} ({active_tray_code})")
        else:
            next_num, auto_tray_code, auto_mo_code = get_next_tray_info(cur)
            active_tray_code = auto_tray_code
            cur.execute("""
                INSERT INTO trays (
                    tray_code, pic_warehouse, assigned_operator, source_document_mo,
                    product_type, status, verified_by_gudang, verified_at, verified_by_user,
                    session_status, created_at
                ) VALUES (
                    ?, 'Gudang', ?, ?,
                    'Nanggala P9', 'active', 1, ?, 'Administrator NKRI',
                    'active', ?
                )
            """, (active_tray_code, operator, auto_mo_code, now_str, now_str))
            active_tray_id = cur.lastrowid
            print(f"[1/5] Membuat Tray otomatis: ID {active_tray_id} ({active_tray_code})")

        assy_doc_number = format_doc_number(active_tray_code, 'ASSY')
        qc_doc_number = format_doc_number(active_tray_code, 'QC') if tf2_status == 'GOOD' else None

        print(f"      No Dokumen TF 1 (Assy): {assy_doc_number}")
        if qc_doc_number:
            print(f"      No Dokumen TF 2 (QC)  : {qc_doc_number}")

        # -------------------------------------------------------------
        # 2. Sinkronisasi Master Stocks & Alokasi Tray
        # -------------------------------------------------------------
        print("\n[2/5] Memperbarui Master Stocks untuk seluruh part...")
        for item in MANUAL_ITEMS:
            p1 = item['part1']
            p12 = item['part12']
            p15 = item['part15']
            cs = item['cs']

            # Part 1: Pastikan ada di master_stocks dan berstatus Done
            cur.execute("""
                INSERT INTO master_stocks (sn, cs, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
                VALUES (?, NULL, '1', 'Done', ?, ?, ?, ?)
                ON CONFLICT(sn, part_type) DO UPDATE SET
                    status = 'Done',
                    allocated_tray_id = excluded.allocated_tray_id,
                    used_at = excluded.used_at
            """, (p1, active_tray_id, now_str, now_str, now_str))

            # Part 12: Pastikan berstatus Done di master_stocks
            cur.execute("""
                INSERT INTO master_stocks (sn, cs, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
                VALUES (?, NULL, '12', 'Done', ?, ?, ?, ?)
                ON CONFLICT(sn, part_type) DO UPDATE SET
                    status = 'Done',
                    allocated_tray_id = excluded.allocated_tray_id,
                    used_at = excluded.used_at
            """, (p12, active_tray_id, now_str, now_str, now_str))

            # Part 15: Pastikan berstatus Done di master_stocks
            cur.execute("""
                INSERT INTO master_stocks (sn, cs, part_type, status, allocated_tray_id, allocated_at, used_at, created_at)
                VALUES (?, ?, '15', 'Done', ?, ?, ?, ?)
                ON CONFLICT(sn, part_type) DO UPDATE SET
                    cs = excluded.cs,
                    status = 'Done',
                    allocated_tray_id = excluded.allocated_tray_id,
                    used_at = excluded.used_at
            """, (p15, cs, active_tray_id, now_str, now_str, now_str))

        print("      -> 21 part (7 set x 3 komponen) berhasil disinkronkan ke master_stocks.")

        # -------------------------------------------------------------
        # 3. Masukkan ke Tabel parts (Komponen dalam Tray)
        # -------------------------------------------------------------
        print("\n[3/5] Menambahkan komponen ke tabel parts (Tray Supply)...")
        for item in MANUAL_ITEMS:
            for p_type, sn_val in [('15', item['part15']), ('12', item['part12']), ('1', item['part1'])]:
                cur.execute("""
                    INSERT OR REPLACE INTO parts (tray_id, part_type, sn, status, created_at)
                    VALUES (?, ?, ?, 'Done', ?)
                """, (active_tray_id, p_type, sn_val, now_str))
        print("      -> 21 item berhasil dicatat di tabel parts.")

        # -------------------------------------------------------------
        # 4. Masukkan ke Tabel assemblies (Perakitan Good & Lolos TF1)
        # -------------------------------------------------------------
        print("\n[4/5] Memasukkan data rakitan ke tabel assemblies & assembly_wo_history...")
        inserted_assy_ids = []
        for idx, item in enumerate(MANUAL_ITEMS):
            set_num = start_set_num + idx
            p1 = item['part1']
            p12 = item['part12']
            p15 = item['part15']

            cur.execute("""
                INSERT INTO assemblies (
                    tray_id, set_number, pic_assembly,
                    part15_sn, part12_sn, part1_sn,
                    status, original_assembly_status,
                    assy_test_status, assy_doc_number,
                    qc_status, qc_doc_number, qc_tested_at, qc_tested_by,
                    verified_status, lifecycle_path,
                    warehouse_receipt_status,
                    started_at, finished_at
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?,
                    'Good', 'Good',
                    'Good', ?,
                    ?, ?, ?, ?,
                    'ACC', 'FRESH_GOOD',
                    'PENDING',
                    ?, ?
                )
                ON CONFLICT(tray_id, set_number) DO UPDATE SET
                    part15_sn = excluded.part15_sn,
                    part12_sn = excluded.part12_sn,
                    part1_sn = excluded.part1_sn,
                    status = excluded.status,
                    original_assembly_status = excluded.original_assembly_status,
                    assy_test_status = excluded.assy_test_status,
                    assy_doc_number = excluded.assy_doc_number,
                    qc_status = excluded.qc_status,
                    qc_doc_number = excluded.qc_doc_number,
                    qc_tested_at = excluded.qc_tested_at,
                    qc_tested_by = excluded.qc_tested_by,
                    verified_status = excluded.verified_status,
                    finished_at = excluded.finished_at
                RETURNING id
            """, (
                active_tray_id, set_num, operator,
                p15, p12, p1,
                assy_doc_number,
                tf2_status, qc_doc_number, (now_str if tf2_status == 'GOOD' else None), (tester_qc if tf2_status == 'GOOD' else None),
                now_str, now_str
            ))
            assy_id = cur.fetchone()[0]
            inserted_assy_ids.append((assy_id, set_num, item))

            # Riwayat pengerjaan (Assembly WO History)
            cur.execute("""
                INSERT INTO assembly_wo_history (
                    tray_id, tray_code, set_number, pic_assembly,
                    part15_sn, part12_sn, part1_sn,
                    status, started_at, finished_at, source, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Good', ?, ?, 'ASSEMBLY_MANUAL_DOUBLE_MARKING', ?)
            """, (active_tray_id, active_tray_code, set_num, operator, p15, p12, p1, now_str, now_str, now_str))

        print(f"      -> {len(inserted_assy_ids)} set berhasil dimasukkan ke tabel assemblies.")

        # -------------------------------------------------------------
        # 5. Masukkan ke Tabel assembly_tests (Audit Trail Uji TF 1 & TF 2)
        # -------------------------------------------------------------
        print("\n[5/5] Mencatat histori uji di tabel assembly_tests (TF 1 & TF 2)...")
        for assy_id, set_num, item in inserted_assy_ids:
            p1 = item['part1']
            p12 = item['part12']
            p15 = item['part15']

            # Catat TF 1 (ASSY Test)
            cur.execute("""
                INSERT INTO assembly_tests (
                    stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                    p9_sn, part15_sn, part12_sn, part1_sn,
                    tester_username, tester_role, status, lifecycle_path, created_at
                ) VALUES (
                    'ASSY', ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 'assembly', 'Good', 'FRESH_GOOD', ?
                )
            """, (
                assy_doc_number, active_tray_id, active_tray_code, set_num, assy_id,
                p15, p15, p12, p1,
                tester_assy, now_str
            ))

            # Jika status TF 2 diset GOOD, catat juga uji QC
            if tf2_status == 'GOOD':
                cur.execute("""
                    INSERT INTO assembly_tests (
                        stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                        p9_sn, part15_sn, part12_sn, part1_sn,
                        tester_username, tester_role, status, lifecycle_path, created_at
                    ) VALUES (
                        'QC', ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, 'qc', 'Good', 'FRESH_GOOD', ?
                    )
                """, (
                    qc_doc_number, active_tray_id, active_tray_code, set_num, assy_id,
                    p15, p15, p12, p1,
                    tester_qc, now_str
                ))

        if dry_run:
            conn.rollback()
            print("\n[i] Mode SIMULASI selesai. Perubahan TIDAK disimpan ke file database (Rollback OK).")
        else:
            conn.commit()
            print("\n[+] SEMUA PERUBAHAN BERHASIL DISIMPAN KE DATABASE (COMMIT OK)!")

        # -------------------------------------------------------------
        # Verifikasi Hasil
        # -------------------------------------------------------------
        print("\n" + "=" * 68)
        print("  RINGKASAN DATA YANG BERHASIL DIINPUT")
        print("=" * 68)
        print(f"{'Set':<5} | {'P9 / Part 15':<12} | {'Part 12':<10} | {'Part 1':<10} | {'Status TF1':<10} | {'Status TF2':<12}")
        print("-" * 68)
        for assy_id, set_num, item in inserted_assy_ids:
            st_tf2 = "PENDING QC" if tf2_status == "PENDING_QC" else "GOOD (LULUS)"
            print(f"#{set_num:<4} | {item['part15']:<12} | {item['part12']:<10} | {item['part1']:<10} | {'Good':<10} | {st_tf2:<12}")
        print("-" * 68)
        print(f"Tray Code  : {active_tray_code} (ID: {active_tray_id})")
        print(f"Dokumen TF1: {assy_doc_number}")
        if qc_doc_number:
            print(f"Dokumen TF2: {qc_doc_number}")
        print("=" * 68 + "\n")

        return True

    except Exception as ex:
        conn.rollback()
        print(f"\n[X] Terjadi error: {ex}")
        print("[X] Seluruh perubahan dibatalkan (Rollback).")
        import traceback
        traceback.print_exc()
        return False
    finally:
        conn.close()

def main():
    parser = argparse.ArgumentParser(
        description="Menambahkan data manual Part 12 double marking ke database Final Assembly."
    )
    parser.add_argument(
        "--db", default=DB_PATH, help=f"Path file database SQLite (default: {DB_PATH})"
    )
    parser.add_argument(
        "--tray-code", default=None, help="Kode tray yang digunakan (misal: WH/ASSY/000137). Jika kosong, dibuat otomatis."
    )
    parser.add_argument(
        "--tray-id", type=int, default=None, help="ID tray yang sudah ada di database."
    )
    parser.add_argument(
        "--operator", default="Administrator NKRI", help="Nama operator perakitan (default: Administrator NKRI)"
    )
    parser.add_argument(
        "--tester-assy", default="Administrator NKRI", help="Nama petugas TF 1 (default: Administrator NKRI)"
    )
    parser.add_argument(
        "--tester-qc", default="QC Tester", help="Nama petugas TF 2 (default: QC Tester)"
    )
    parser.add_argument(
        "--qc-status", choices=["PENDING_QC", "GOOD"], default="PENDING_QC",
        help="Status di TF 2: 'PENDING_QC' (default: berada di antrean uji TF 2) atau 'GOOD' (sudah lulus uji TF 2)"
    )
    parser.add_argument(
        "--dry-run", action="store_true", help="Simulasi saja tanpa mengubah file database."
    )
    parser.add_argument(
        "-y", "--yes", action="store_true", help="Langsung eksekusi tanpa konfirmasi interaktif."
    )

    args = parser.parse_args()

    # Jika dijalankan interaktif tanpa flag -y dan bukan dry-run, konfirmasi dulu
    if not args.yes and not args.dry_run and sys.stdin.isatty():
        print("Anda akan menambahkan 7 data unit double marking ke database:")
        for idx, it in enumerate(MANUAL_ITEMS, 1):
            print(f"  {idx}. P1: {it['part1']} | P12: {it['part12']} | P15: {it['part15']}")
        print(f"Status: Lolos Perakitan (Good) -> Lolos TF 1 (Good) -> Posisi di TF 2 ({args.qc_status})")
        confirm = input("\nLanjutkan eksekusi ke database? (Y/n): ").strip().lower()
        if confirm not in ['y', 'yes', '']:
            print("[i] Eksekusi dibatalkan oleh pengguna.")
            sys.exit(0)

    success = run_insertion(
        db_path=args.db,
        tray_id=args.tray_id,
        tray_code=args.tray_code,
        operator=args.operator,
        tester_assy=args.tester_assy,
        tester_qc=args.tester_qc,
        tf2_status=args.qc_status,
        dry_run=args.dry_run
    )

    if not success:
        sys.exit(1)

if __name__ == "__main__":
    main()
