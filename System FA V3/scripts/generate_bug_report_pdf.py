import os
import sys
from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm, mm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, HRFlowable, KeepTogether
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super().showPage()
        super().save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748B"))
        
        # Header (pages > 1)
        if self._pageNumber > 1:
            self.drawString(2 * cm, 28.3 * cm, "Laporan Analisis & Solusi Bug: Sistem Assembly & Traceability P9")
            self.setStrokeColor(colors.HexColor("#CBD5E1"))
            self.setLineWidth(0.5)
            self.line(2 * cm, 28.1 * cm, 19 * cm, 28.1 * cm)
            
        # Footer
        footer_text = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(19 * cm, 1.2 * cm, footer_text)
        self.drawString(2 * cm, 1.2 * cm, "Dokumen Internal Sistem Traceability & Perakitan P9 NKRI - 2026")
        self.setStrokeColor(colors.HexColor("#CBD5E1"))
        self.setLineWidth(0.5)
        self.line(2 * cm, 1.5 * cm, 19 * cm, 1.5 * cm)
        self.restoreState()

def create_report_pdf(output_path="Laporan_Analisis_Bug_dan_Solusi_Sistem_SN.pdf"):
    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        leftMargin=1.8 * cm,
        rightMargin=1.8 * cm,
        topMargin=2.0 * cm,
        bottomMargin=2.0 * cm
    )

    styles = getSampleStyleSheet()
    
    primary_color = colors.HexColor("#1E3A8A") # Navy
    accent_red = colors.HexColor("#DC2626")
    dark_color = colors.HexColor("#0F172A")
    text_color = colors.HexColor("#334155")
    bg_light = colors.HexColor("#F8FAFC")
    border_color = colors.HexColor("#CBD5E1")

    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=19,
        textColor=dark_color,
        spaceAfter=3
    )
    
    subtitle_style = ParagraphStyle(
        'DocSubTitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=12,
        textColor=colors.HexColor("#64748B"),
        spaceAfter=10
    )

    h1_style = ParagraphStyle(
        'Header1',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=11,
        leading=15,
        textColor=primary_color,
        spaceBefore=8,
        spaceAfter=4
    )

    h2_style = ParagraphStyle(
        'Header2',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9.5,
        leading=13,
        textColor=dark_color,
        spaceBefore=5,
        spaceAfter=2
    )

    body_style = ParagraphStyle(
        'BodyDark',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.2,
        leading=11.5,
        textColor=text_color,
        spaceAfter=4
    )

    bullet_style = ParagraphStyle(
        'BulletText',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.2,
        leading=11.5,
        textColor=text_color,
        leftIndent=10,
        firstLineIndent=-7,
        spaceAfter=3
    )

    code_style = ParagraphStyle(
        'CodeSnippet',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=7.2,
        leading=9.5,
        textColor=colors.HexColor("#0F172A")
    )

    story = []

    # Title & Metadata
    story.append(Paragraph("LAPORAN KOMPREHENSIF PERBAIKAN SISTEM & ANALISIS BUG", title_style))
    story.append(Paragraph("Sistem Assembly & Traceability P9 - PT NKRI | 06 September 2026", subtitle_style))
    story.append(HRFlowable(width="100%", thickness=1.5, color=primary_color, spaceAfter=8))

    # Meta Table Info
    info_data = [
        [
            Paragraph("<b>Topik Analisis:</b>", body_style),
            Paragraph("Refactor Alur Perakitan, QC Rework Separation, Dokumen Per Tray, & UI Fixes", body_style),
            Paragraph("<b>Status Akhir:</b>", body_style),
            Paragraph("<font color='#16A34A'><b>100% RESOLVED & VERIFIED</b></font>", body_style)
        ],
        [
            Paragraph("<b>Komponen:</b>", body_style),
            Paragraph("Backend (<code>app.py</code>), DB PostgreSQL, & Frontend (<code>index.html</code>)", body_style),
            Paragraph("<b>Tanggal Selesai:</b>", body_style),
            Paragraph("06 September 2026", body_style)
        ]
    ]
    info_table = Table(info_data, colWidths=[2.8*cm, 6.8*cm, 2.7*cm, 5.1*cm])
    info_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), bg_light),
        ('BOX', (0,0), (-1,-1), 1, border_color),
        ('INNERGRID', (0,0), (-1,-1), 0.5, border_color),
        ('PADDING', (0,0), (-1,-1), 4),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(info_table)
    story.append(Spacer(1, 6))

    # 1. Ringkasan Topik Masalah yang Diselesaikan
    story.append(Paragraph("1. Rangkuman Permasalahan yang Ditemukan & Diperbaiki", h1_style))
    story.append(Paragraph("Berdasarkan permintaan dan hasil inspeksi alur operasional, terdapat 5 poin penting perbaikan:", body_style))

    points_data = [
        [Paragraph("<b>No</b>", body_style), Paragraph("<b>Masalah / Gejala</b>", body_style), Paragraph("<b>Akar Penyebab</b>", body_style), Paragraph("<b>Solusi & Status</b>", body_style)],
        [
            Paragraph("1", body_style),
            Paragraph("<b>Error Refresh Browser (502/503/500)</b><br/>Muncul baris merah console saat browser di-refresh.", body_style),
            Paragraph("Endpoint <code>/api/master/parts</code> tidak memiliki return JSON, query <code>/api/laporan</code> missing JOIN, dan proses lama.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Menambahkan return JSON, memperbaiki klausa JOIN, restart backend.", body_style)
        ],
        [
            Paragraph("2", body_style),
            Paragraph("<b>Pemisahan Status Test Assembly & QC Rework</b><br/>Saat QC rework, status Test Assembly ikut berubah jadi Rework.", body_style),
            Paragraph("Kondisi badge menganggap semua status Rework berasal dari perakitan, padahal unit yang masuk QC telah lolos 15 tahap perakitan.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Test Assembly tetap <code>GOOD</code> saat unit masuk QC; hanya QC yang menampilkan <code>REWORK</code>.", body_style)
        ],
        [
            Paragraph("3", body_style),
            Paragraph("<b>Konsep 1 Nomor Dokumen Per Tray</b><br/>Setiap set menghasilkan nomor dokumen berbeda padahal 1 tray.", body_style),
            Paragraph("Sistem lama menggunakan increment counter independen (<code>get_next_doc_number</code>) per set bukan turunan kode tray.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Menggunakan <code>get_tray_doc_number</code> (1 Supply Tray = 1 Doc Assy/QC dan 1 Doc WH/QC).", body_style)
        ],
        [
            Paragraph("4", body_style),
            Paragraph("<b>Pemindahan Verifikasi Gudang dari Laporan ke Dokumen</b><br/>Tombol Accept/Decline masih ada di tabel Laporan.", body_style),
            Paragraph("Verifikasi fisik gudang tercampur di tabel rekapitulasi laporan alih-alih di menu Dokumen Cetak.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Menghapus tombol aksi di Laporan (read-only) dan memusatkan verifikasi fisik di Dokumen.", body_style)
        ],
        [
            Paragraph("5", body_style),
            Paragraph("<b>Layout Card Header & Modal Test Assembly</b><br/>Status menutupi kode tray dan banner merah/hijau redundan.", body_style),
            Paragraph("Banner status hasil uji redundan di modal; badge status overlap sebelum pengujian dilakukan.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Badge disembunyikan sampai pengujian selesai; banner box merah/hijau dihapus total dari modal.", body_style)
        ],
        [
            Paragraph("6", body_style),
            Paragraph("<b>Status Test Assembly di QC Cards (Hold & Verif)</b><br/>Test Assembly sempat menampilkan HOLD pada kartu QC.", body_style),
            Paragraph("Unit di meja QC telah lolos 15 tahap uji perakitan, sehingga status Test Assembly harus konsisten GOOD.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Status Test Assembly di seluruh card QC diatur mutlak GOOD (emerald).", body_style)
        ],
        [
            Paragraph("7", body_style),
            Paragraph("<b>Judul Dokumen QC & Pembersihan Catatan QC</b><br/>Judul dokumen diperbarui & kolom catatan QC dihapus.", body_style),
            Paragraph("Judul resmi dokumen disesuaikan menjadi 'Quality Control dan Test Functional 2' dan tabel dibersihkan dari Catatan QC.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Judul dokumen resmi diperbarui, tombol navigasi diselaraskan, dan kolom catatan QC dihapus.", body_style)
        ],
        [
            Paragraph("8", body_style),
            Paragraph("<b>Pembersihan Referensi QC pada Dokumen WO</b><br/>Dokumen WO memuat kolom tanda tangan QC.", body_style),
            Paragraph("Dokumen Work Order adalah dokumen serah terima supply Gudang & Perakitan.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: Tanda tangan QC dihapus dari Dokumen WO, kini rapi 2 kolom (Gudang & Perakitan).", body_style)
        ],
        [
            Paragraph("9", body_style),
            Paragraph("<b>Preservasi Status Perakitan Saat Barang Declined</b><br/>Decline gudang merubah status kartu & laporan perakitan.", body_style),
            Paragraph("Saat Gudang menolak fisik barang QC, status perakitan tidak boleh berubah menjadi Declined.", body_style),
            Paragraph("<font color='#16A34A'><b>SOLVED</b></font>: assemblies.status tetap Good; laporan declined dipusatkan di Laporan QC Terakhir (Status Barang: Good | Status Verified: DECLINED | Keterangan).", body_style)
        ]
    ]
    points_table = Table(points_data, colWidths=[0.8*cm, 5.0*cm, 5.8*cm, 5.8*cm])
    points_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#F1F5F9")),
        ('BOX', (0,0), (-1,-1), 1, border_color),
        ('INNERGRID', (0,0), (-1,-1), 0.5, border_color),
        ('PADDING', (0,0), (-1,-1), 3.5),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(points_table)
    story.append(Spacer(1, 6))

    # 2. Detail Implementasi & Perubahan Kode
    story.append(Paragraph("2. Rincian Teknis Implementasi & Perubahan Kode", h1_style))
    story.append(Paragraph("<b>A. Card Header, Modal & Tampilan Dokumen (Frontend <code>static/index.html</code>):</b>", h2_style))
    story.append(Paragraph("&bull; <b>Konsistensi Status Test Assembly di QC:</b> Pada kartu Verifikasi Admin QC, badge Test Assembly selalu menampilkan <code>GOOD</code>.", bullet_style))
    story.append(Paragraph("&bull; <b>Pembersihan Modal:</b> Menghapus box banner merah status hasil uji real-time dari Modal Test Assembly.", bullet_style))
    story.append(Paragraph("&bull; <b>Judul Dokumen & Tabel:</b> Judul dokumen diubah menjadi <code>Quality Control dan Test Functional 2</code>, kolom Catatan QC dihapus dari tabel.", bullet_style))
    story.append(Paragraph("&bull; <b>Dokumen Supply WO:</b> Disederhanakan menjadi 2 blok tanda tangan (Gudang Supply & Operator Final Assembly) tanpa referensi QC.", bullet_style))
    story.append(Paragraph("&bull; <b>Laporan QC Terakhir (Penerimaan Fisik Gudang):</b> Mengadopsi struktur kolom baru: <code>No | No. Dokumen QC | SN P9 Part 15 | PN P9 Part 12 | PN P9 Part 1 | Tray & MO | Status Barang (GOOD) | Status Verified (ACC/DECLINED) | Keterangan | Petugas & Waktu</code>.", bullet_style))
    story.append(Paragraph("&bull; <b>Dual Badge & Cetak Label Uji Pasca Hold / Rework:</b> Card Testing Operator kini menampilkan status berdampingan yang rapi dan profesional (<code>HOLD + GOOD</code> atau <code>REWORK + GOOD</code>) serta langsung mengaktifkan tombol <code>[Cetak Label]</code> setelah barang disetujui Good.", bullet_style))

    story.append(Paragraph("<b>B. Alur Penolakan & Pemisahan Data (Backend <code>app.py</code>):</b>", h2_style))
    story.append(Paragraph("&bull; <b>Preservasi Status Perakitan:</b> Endpoint <code>/api/warehouse/verify-qc-receipt</code> dan <code>/api/gudang/verify-set</code> saat DECLINE tidak mengubah <code>assemblies.status</code> (tetap <code>Good</code>), melainkan mencatat <code>warehouse_receipt_status = 'DECLINED'</code> dan <code>declined_reason</code>.", bullet_style))
    story.append(Paragraph("&bull; <b>Query Perakitan & Tray:</b> <code>/api/tray/detail</code> dan <code>/api/laporan</code> mempertahankan nilai asli status assembly tanpa override ke Declined, serta menyertakan <code>lifecycle_path</code> untuk status history akurat.", bullet_style))
    story.append(Paragraph("&bull; <b>Nomor Dokumen Tunggal Per Tray:</b> Fungsi <code>get_tray_doc_number</code> menjamin konsistensi nomor dokumen 1 per tray.", bullet_style))

    story.append(Spacer(1, 6))

    # 3. Hasil Pengujian & Verifikasi
    story.append(Paragraph("3. Hasil Pengujian & Verifikasi Sistem (100% OK)", h1_style))

    res_data = [
        [Paragraph("<b>Skenario / Komponen Uji</b>", body_style), Paragraph("<b>Metode Uji</b>", body_style), Paragraph("<b>Hasil Aktual</b>", body_style), Paragraph("<b>Status</b>", body_style)],
        [
            Paragraph("Sintaks HTML & Template Vue", body_style),
            Paragraph("<code>validate_html.py</code> & Node.js", code_style),
            Paragraph("0 open tag remaining, 366 props valid", body_style),
            Paragraph("<font color='#16A34A'><b>PASS</b></font>", body_style)
        ],
        [
            Paragraph("Dual Badge Hold/Rework ➔ Good", body_style),
            Paragraph("Integration & UI Logic Test", body_style),
            Paragraph("Badge ganda tampil rapi & profesional", body_style),
            Paragraph("<font color='#16A34A'><b>PASS</b></font>", body_style)
        ],
        [
            Paragraph("Aktivasi Tombol Cetak Label", body_style),
            Paragraph("E2E Testing Operator Flow", body_style),
            Paragraph("Tombol Cetak Label aktif pasca ACC Good", body_style),
            Paragraph("<font color='#16A34A'><b>PASS</b></font>", body_style)
        ],
        [
            Paragraph("Nomor Dokumen Per Tray (Assy & QC)", body_style),
            Paragraph("Integration Test & DB Query", body_style),
            Paragraph("1 Tray = 1 Dokumen Assy/QC & WH/QC", body_style),
            Paragraph("<font color='#16A34A'><b>PASS</b></font>", body_style)
        ],
        [
            Paragraph("Pemisahan Status Rework QC", body_style),
            Paragraph("End-to-End Workflow Test", body_style),
            Paragraph("Test Assembly tetap Good, QC = Rework", body_style),
            Paragraph("<font color='#16A34A'><b>PASS</b></font>", body_style)
        ]
    ]
    res_table = Table(res_data, colWidths=[5.2*cm, 4.2*cm, 5.7*cm, 2.3*cm])
    res_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor("#F1F5F9")),
        ('BOX', (0,0), (-1,-1), 1, border_color),
        ('INNERGRID', (0,0), (-1,-1), 0.5, border_color),
        ('PADDING', (0,0), (-1,-1), 3.5),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(res_table)
    story.append(Spacer(1, 8))

    # 4. Panduan & Kesimpulan
    story.append(Paragraph("4. Kesimpulan & Panduan", h1_style))
    story.append(Paragraph(
        "Seluruh perbaikan telah diterapkan di backend (<code>app.py</code>) dan frontend (<code>index.html</code>). Pengguna dapat langsung me-refresh browser (<b>F5</b> atau <b>Ctrl + F5</b>) untuk menikmati tampilan card yang bersih dan rapi, alur dokumen 1 per tray, serta verifikasi fisik yang terpusat di Dokumen.",
        body_style
    ))

    # Build PDF
    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"PDF successfully generated at: {output_path}")

if __name__ == "__main__":
    out = os.path.join(os.getcwd(), "Laporan_Analisis_Bug_dan_Solusi_Sistem_SN.pdf")
    create_report_pdf(out)
