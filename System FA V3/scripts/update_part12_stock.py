import sqlite3

def update_part12_stock():
    conn = sqlite3.connect('database.db')
    cur = conn.cursor()

    print("=== MEMPERBARUI STOK MASTER PART 12 ===")
    
    # 1. Hapus stok Part 12 lama dari master_stocks sesuai instruksi user
    cur.execute("DELETE FROM master_stocks WHERE part_type = '12'")
    print("Berhasil menghapus stok Part 12 sebelumnya dari master_stocks.")

    # 2. Siapkan data serial number baru
    # Range 1: 60001 - 63500 (3.500 items)
    # Range 2: 70001 - 71500 (1.500 items)
    new_stocks = []
    
    for sn in range(60001, 63501):
        new_stocks.append((str(sn), '12', 'Tersedia'))

    for sn in range(70001, 71501):
        new_stocks.append((str(sn), '12', 'Tersedia'))

    print(f"Jumlah stok baru yang akan di-insert: {len(new_stocks)} items")

    # 3. Batch insert ke master_stocks
    cur.executemany("""
        INSERT INTO master_stocks (sn, part_type, status, cs, allocated_tray_id, allocated_at, used_at)
        VALUES (?, ?, ?, NULL, NULL, NULL, NULL)
    """, new_stocks)

    conn.commit()

    # 4. Verifikasi hasil
    cur.execute("""
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN CAST(sn AS INTEGER) BETWEEN 60001 AND 63500 THEN 1 END) as range1_cnt,
            COUNT(CASE WHEN CAST(sn AS INTEGER) BETWEEN 70001 AND 71500 THEN 1 END) as range2_cnt,
            COUNT(CASE WHEN status = 'Tersedia' THEN 1 END) as available_cnt
        FROM master_stocks
        WHERE part_type = '12'
    """)
    res = cur.fetchone()
    print("\n--- HASIL VERIFIKASI MASTER STOCKS PART 12 ---")
    print(f"Total Part 12: {res[0]} (Target: 5000)")
    print(f"Range 1 (60001 - 63500): {res[1]} (Target: 3500)")
    print(f"Range 2 (70001 - 71500): {res[2]} (Target: 1500)")
    print(f"Status Tersedia: {res[3]} (Target: 5000)")

    assert res[0] == 5000, f"Total salah: {res[0]}"
    assert res[1] == 3500, f"Range 1 salah: {res[1]}"
    assert res[2] == 1500, f"Range 2 salah: {res[2]}"
    assert res[3] == 5000, f"Tersedia salah: {res[3]}"

    # Pastikan Part 1 dan Part 15 tidak berubah
    cur.execute("SELECT part_type, COUNT(*) FROM master_stocks GROUP BY part_type ORDER BY part_type")
    for row in cur.fetchall():
        print(f"Part {row[0]}: {row[1]} items")

    conn.close()
    print("\n>>> PENGGANTIAN STOK MASTER PART 12 BERHASIL 100%! <<<")

if __name__ == '__main__':
    update_part12_stock()
