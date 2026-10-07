# Walkthrough: Konsistensi Template Tabel & Urutan Komponen (SN P9 Part 15 &rarr; PN P9 Part 1 &rarr; PN P9 Part 12)

Seluruh template tabel cetak dokumen resmi, tabel laporan, kartu pengujian, kartu QC, modal verifikasi, dan ekspor Excel telah dibuat **100% konsisten** di seluruh aplikasi sesuai dengan format template yang diminta:

---

## 1. Standar Template & Urutan yang Diterapkan

### A. Format Header Dua Tingkat Dokumen Cetak (Printable Traveler Documents)
Sesuai format acuan:
```
+----+-------------------+---------------------------------------+
|    |   Serial Number   |           Production Number           |
| No +-------------------+-------------------+-------------------+
|    |    P9 Part 15     |     P9 Part 1     |    P9 Part 12     |
+----+-------------------+-------------------+-------------------+
```
Struktur header dua tingkat ini kini konsisten pada seluruh 4 Dokumen Cetak Resmi:
1. **Dokumen 1 (Supply Material WO Gudang - `supply`)**
2. **Dokumen 2 (Traveler Perakitan - `assy`)**
3. **Dokumen 3 (Uji Fungsional Perakitan - `assy_qc`)**
4. **Dokumen 4 (Dokumen Mutu Quality Control - `wh_qc`)**

---

### B. Urutan Kolom Konsisten di Seluruh Tabel & Kartu
Urutan komponen standar yang seragam di seluruh modul:
1. **SN P9 Part 15** (Serial Number Produk Utama)
2. **PN P9 Part 1** (Production Number Part 1)
3. **PN P9 Part 12** (Production Number Part 12)

Bagian-bagian yang telah diselaraskan:
- **Tabel Verifikasi Admin** (`admin_verify`)
- **Tabel Laporan Sub-tab 1** (WO Traveler)
- **Tabel Laporan Sub-tab 2** (Uji Fungsional Perakitan)
- **Tabel Laporan Sub-tab 3** (Uji Mutu Quality Control)
- **Tabel Laporan Sub-tab 4** (Penerimaan Fisik Gudang)
- **Kartu Slot Pengujian Perakitan** (Tab 1.5 Testing Card)
- **Kartu Slot QC & Hold/Rework** (Tab 4 & Tab 4.5 Card)
- **Tabel Modal Verifikasi Gudang** (`gudangVerifySets`)
- **Fungsi Ekspor Excel**:
  - `exportTrayToExcel`
  - `exportReportToExcel`
  - `exportCurrentReportToExcel` (Subtab WO, Assy Test, QC Test, Warehouse Receipt)

---

## 2. Verifikasi & Hasil Pengujian

| Komponen / Fitur | File / Skrip Pengujian | Hasil | Status |
| :--- | :--- | :--- | :---: |
| **Konsistensi Header Dokumen & Urutan Part** | `tests/test_part_consistency.py` | 4 Dokumen & Seluruh Tabel Laporan/Ekspor sesuai template | **PASS** |
| **Validasi HTML & Vue Reactive Properties** | `tests/validate_html.py` | 100% tag HTML seimbang, semua binding Vue valid | **PASS** |
| **Dual Badge (Hold/Rework &rarr; Good) & Cetak Label** | `tests/test_hold_rework_to_good_badges_and_print.py` | Dual badge muncul dan tombol cetak label aktif | **PASS** |
| **Realtime Notifikasi & Dokumen** | `tests/test_realtime_and_doc_improvements.py` | Live polling dan switch dokumen instan | **PASS** |
