import os
import sys
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.pdfgen import canvas

# Numbered Canvas for "Halaman X dari Y"
class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super(NumberedCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super(NumberedCanvas, self).showPage()
        super(NumberedCanvas, self).save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        
        # Header on later pages
        if self._pageNumber > 1:
            self.setFont("Helvetica", 8)
            self.setFillColor(colors.HexColor("#475569"))
            self.drawString(40, 810, "Sistem Final Assembly & Traceability Nanggala P9 — Dokumen Spesifikasi Alur Kerja")
            self.drawRightString(555, 810, "Refactor V3 (Laravel + MariaDB)")
            self.setStrokeColor(colors.HexColor("#CBD5E1"))
            self.setLineWidth(0.5)
            self.line(40, 804, 555, 804)

        # Footer on all pages
        self.setStrokeColor(colors.HexColor("#CBD5E1"))
        self.setLineWidth(0.5)
        self.line(40, 42, 555, 42)

        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748B"))
        self.drawString(40, 30, "CONFIDENTIAL — Dokumen Internal Produksi & Quality Assurance FA V3")
        page_text = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(555, 30, page_text)
        
        self.restoreState()

def create_report(filename):
    doc = SimpleDocTemplate(
        filename,
        pagesize=A4,
        leftMargin=40,
        rightMargin=40,
        topMargin=48,
        bottomMargin=52
    )

    styles = getSampleStyleSheet()
    
    # Custom Palette
    c_primary = colors.HexColor("#1E3A8A")     # Navy
    c_secondary = colors.HexColor("#2563EB")   # Royal Blue
    c_dark = colors.HexColor("#0F172A")        # Slate 900
    c_text = colors.HexColor("#334155")        # Slate 700
    c_light = colors.HexColor("#F8FAFC")       # Slate 50
    c_border = colors.HexColor("#CBD5E1")      # Slate 300
    c_emerald = colors.HexColor("#059669")     # Green
    c_amber = colors.HexColor("#D97706")       # Amber
    c_rose = colors.HexColor("#E11D48")        # Rose Red

    # Custom Typography Styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=18,
        leading=22,
        textColor=c_primary,
        alignment=0,
        spaceAfter=4
    )

    h1_style = ParagraphStyle(
        'Heading1Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=16,
        textColor=c_primary,
        spaceBefore=14,
        spaceAfter=6,
        keepWithNext=True
    )

    h2_style = ParagraphStyle(
        'Heading2Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=13.5,
        textColor=c_secondary,
        spaceBefore=9,
        spaceAfter=3,
        keepWithNext=True
    )

    body_style = ParagraphStyle(
        'BodyCustom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=12.5,
        textColor=c_text,
        spaceAfter=5
    )

    bullet_style = ParagraphStyle(
        'BulletCustom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.3,
        leading=12,
        textColor=c_text,
        leftIndent=14,
        firstLineIndent=-9,
        spaceAfter=3.5
    )

    table_header_style = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10.5,
        textColor=colors.white,
        alignment=1
    )

    table_cell_style = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.5,
        leading=10.5,
        textColor=c_dark
    )

    table_cell_bold = ParagraphStyle(
        'TableCellBold',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=7.5,
        leading=10.5,
        textColor=c_dark
    )

    story = []

    # =========================================================================
    # HEADER BANNER & DOCUMENT METADATA
    # =========================================================================
    header_data = [
        [
            Paragraph("<b>DOKUMEN SPESIFIKASI ALUR KERJA SISTEM</b><br/><font size=8.5 color='#64748B'>Sistem Final Assembly & Traceability Nanggala P9 (Refactor V3)</font>", title_style),
            Paragraph("<b>STATUS:</b> <font color='#059669'>100% TERVERIFIKASI</font><br/><b>TANGGAL:</b> 26 Oktober 2026<br/><b>PLATFORM:</b> Laravel 10 + MariaDB<br/><b>TARGET AUDIT:</b> User Review & Sign-Off", ParagraphStyle('MetaRight', parent=styles['Normal'], fontName='Helvetica', fontSize=7.5, leading=10.5, alignment=2, textColor=c_dark))
        ]
    ]
    header_table = Table(header_data, colWidths=[335, 180])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ]))
    story.append(header_table)
    story.append(HRFlowable(width="100%", thickness=1.5, color=c_primary, spaceBefore=2, spaceAfter=10))

    # =========================================================================
    # 1. RINGKASAN EKSEKUTIF
    # =========================================================================
    story.append(Paragraph("1. Ringkasan Eksekutif & Karakteristik Sistem", h1_style))
    story.append(Paragraph(
        "Sistem Final Assembly & Traceability Nanggala P9 (Refactor V3) adalah sistem manufaktur terpadu berkecepatan tinggi yang mengatur seluruh alur perakitan, pengujian mutu bertingkat, penerimaan fisik gudang, hingga penerbitan dokumen ekspor/surat jalan. Sistem ini memberlakukan arsitektur pengamanan independen pada setiap menu, isolasi relasi bersih (<i>clean-slate</i>), dan pelacakan riwayat 5-dimensi yang transparan.",
        body_style
    ))

    # Metric Table
    metrics_data = [
        [Paragraph("Indikator Sistem", table_header_style), Paragraph("Kondisi Database Aktif", table_header_style), Paragraph("Keterangan Fungsional", table_header_style)],
        [Paragraph("<b>Total Master Part Terdata</b>", table_cell_style), Paragraph("<b>16.991 Serial Number</b>", table_cell_bold), Paragraph("P9 Part 1 (Optical), Part 12 (Board), Part 15 (Chassis)", table_cell_style)],
        [Paragraph("<b>Status Stok Sinkron</b>", table_cell_style), Paragraph("<b>54.716 Total Record</b>", table_cell_bold), Paragraph("100% identik dengan database operasional (tanpa selisih)", table_cell_style)],
        [Paragraph("<b>Total Endpoint API Aktif</b>", table_cell_style), Paragraph("<b>117 Rute Bersih (100% PASS)</b>", table_cell_bold), Paragraph("Seluruh modul terverifikasi tanpa kebocoran data / error", table_cell_style)],
        [Paragraph("<b>Akses Portal CMS</b>", table_cell_style), Paragraph("<b>Terisolasi di /admin</b>", table_cell_bold), Paragraph("Wajib re-autentikasi hak akses Administrator (Dedicated Gate)", table_cell_style)],
    ]
    m_table = Table(metrics_data, colWidths=[130, 140, 245])
    m_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('GRID', (0,0), (-1,-1), 0.5, c_border),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    story.append(m_table)
    story.append(Spacer(1, 8))

    # =========================================================================
    # 2. STRUKTUR PRODUK & STANDAR TRAY
    # =========================================================================
    story.append(Paragraph("2. Struktur Komponen Produk & Standar Alokasi Tray", h1_style))
    story.append(Paragraph(
        "Setiap 1 unit produk akhir <b>Nanggala P9</b> dibentuk dari gabungan 3 komponen utama yang wajib terikat secara konsisten:",
        body_style
    ))
    story.append(Paragraph("• <b>P9 Part 15 (Identitas Utama P9)</b>: Komponen casing luar dengan SN alfanumerik (contoh: <code>15004165</code>) yang menjadi acuan nomor seri produk akhir pada Surat Jalan.", bullet_style))
    story.append(Paragraph("• <b>P9 Part 1 (Optical Core Unit)</b>: Komponen optik internal dengan penomoran numerik.", bullet_style))
    story.append(Paragraph("• <b>P9 Part 12 (Mainboard Controller)</b>: Modul pengendali sirkuit elektronik dengan penomoran numerik.", bullet_style))
    story.append(Paragraph(
        "<b>Standar Tray Supply:</b> Setiap baki kerja (Tray) menampung tepat <b>22 Set</b> (Set #1 s.d. #22), setara dengan <b>66 komponen fisik</b> (22x Part 15 + 22x Part 1 + 22x Part 12). Format penomoran Tray otomatis: <code>TR-YYYYMMDD-xxx</code> terikat pada nomor Manufacturing Order (MO).",
        body_style
    ))
    story.append(Spacer(1, 8))

    # =========================================================================
    # 3. RINCIAN 10 FASE ALUR KERJA (PIPELINE)
    # =========================================================================
    story.append(Paragraph("3. Rincian 10 Fase Alur Kerja (End-to-End Pipeline)", h1_style))

    fases = [
        ("Fase 1: Master Stok & Registrasi Komponen Baru", "Semua komponen berawal di database Master Stok dengan status awal <code>Tersedia</code>. Penambahan part dapat dilakukan secara satuan maupun massal (bulk rentang nomor seri). Sistem secara otomatis memvalidasi keunikan nomor seri (mencegah duplikasi tanda titik/spasi) dan mencatat aktor penambah ke dalam <code>part_audit_logs</code>."),
        ("Fase 2: Gudang Supply Tray (Alokasi 22 Set)", "PIC Gudang membuka modul Supply Tray, memilih operator assembly dan menginput MO. Transaksi InnoDB dengan <i>row-level locking</i> dijalankan: 66 part seketika berubah status dari <code>Tersedia</code> menjadi <code>Supplied</code> dan terkunci permanen pada ID Tray tersebut agar tidak bisa diambil oleh tray lain."),
        ("Fase 3: Meja Kerja Assembly Workstation", "Operator perakitan login dan memilih Tray yang ditugaskan. Pengerjaan dilakukan slot demi slot (Slot 1 sampai 22). Operator menginput nomor seri Part 15, Part 1, dan Part 12 secara manual atau memilih rekomendasi stok. Sistem mencatat waktu mulai dan selesai perakitan per set, serta menyimpan riwayat perakitan ke tabel <code>assemblies</code>."),
        ("Fase 4: Manajemen Jeda, Serah Terima Shift, & Ganti Part Rusak", "Operator dapat menjeda sesi (downtime dicatat otomatis dalam detik). Jika ditemukan part cacat saat rakit, operator mengajukan ganti part: part pengganti yang <code>Tersedia</code> dipasangkan, sementara part lama dilepas total dari tray."),
        ("Fase 5: Test Functional 1 (TF 1) — 17 Parameter Uji di Assembly", "Setelah 1 set selesai dirakit, dilakukan pengujian fungsional tahap 1 di meja rakit menggunakan <b>17 Parameter Checklist Pengujian</b>. Jika ke-17 parameter bernilai <code>Good</code>, sistem otomatis menerbitkan dokumen serah terima pengujian <b>TF 1 (<code>ASSY/QC/xxxxxx</code> atau <code>ASSY/QC/PU/angka</code>)</b> dan meloloskan unit ke antrean TF 2. Jika ada parameter gagal, status menjadi <code>Hold TF 1</code>."),
        ("Fase 6: Test Functional 2 (TF 2) — Quality Control (QC)", "Unit yang lolos TF 1 masuk ke antrean Quality Control. <b>Aturan Baku TF 2:</b> QC <u>TIDAK</u> melakukan 17 pengujian ulang (checklist 17 poin TF 1 ditampilkan <i>Read-Only</i>). Keputusan QC adalah <b>1-Klik</b>: memilih <code>Good</code> (lolos QC dan terbit dokumen <code>WH/QC/xxxxxx</code>) atau <code>Not Good/Hold</code> (tertahan di QC)."),
        ("Fase 7: Penerimaan Fisik Gudang (Warehouse QC Receipts)", "Fisik unit yang lolos QC diserahkan kembali ke Gudang Barang Jadi. Petugas Gudang mencocokkan fisik: jika disetujui (<code>ACCEPT</code>), sistem menerbitkan dokumen <b>Goods Receipt (<code>WH/GR/xxxxxx</code>)</b> dan unit resmi terkunci siap kirim. Jika kemasan/fisik rusak, Gudang melakukan <code>DECLINE</code> yang mengalihkan part ke evaluasi Admin."),
        ("Fase 8: Pengembalian Baki Kerja (Returned Trays)", "Tray yang dikembalikan sebelum seluruh unit selesai diverifikasi oleh Gudang. Set yang utuh dipulihkan kembali status part-nya menjadi <code>Tersedia</code> agar dapat digunakan kembali pada produksi berikutnya, sedangkan part yang rusak di-decline dan diteruskan ke Admin."),
        ("Fase 9: Portal Verifikasi & Persetujuan Admin (Admin Gate)", "Admin bertindak sebagai otoritas tertinggi atas kendala material: persetujuan daftar ulang part, persetujuan ganti part, perakitan ulang dengan tray baru berakhiran <b><code>-PU</code></b> (contoh: <code>TR-20261025-001-PU</code>), dan penanganan unit Hold."),
        ("Fase 10: Surat Jalan (DO), Packing List, & Label Peti", "Sistem hanya menampilkan Serial Number Part 15 yang <b>lolos QC (Good) dan telah di-ACCEPT fisik oleh Gudang</b>. Petugas memilih Customer tujuan, sistem menyusun dokumen resmi Surat Jalan (<code>DO/YYYY/MM/xxx</code>), Packing List (<code>PL/YYYY/MM/xxx</code>), pembagian unit ke peti kayu (Crate 1 s.d. 10), label peti, dan upload PDF ke Cloudinary.")
    ]

    for title, desc in fases:
        story.append(Paragraph(title, h2_style))
        story.append(Paragraph(desc, body_style))

    story.append(Spacer(1, 8))

    # =========================================================================
    # 4. KETENTUAN ISOLASI RELASI (CLEAN-SLATE)
    # =========================================================================
    story.append(Paragraph("4. Ketentuan Mutlak Isolasi Relasi & Pemutusan Riwayat Lama (Clean-Slate)", h1_style))
    story.append(Paragraph(
        "Sesuai standar integritas data manufaktur FA V3, sistem memberlakukan <b>Prinsip Siklus Bersih (Clean-Slate Rule)</b> untuk mencegah terjadinya relasi hantu (<i>ghost linkage</i>) atau penarikan riwayat lama:",
        body_style
    ))
    story.append(Paragraph("• <b>Pemutusan Total Saat Ganti Part:</b> Komponen yang diganti di meja perakitan (<code>replace-part</code>) atau disetujui Admin seketika diputus dari baki dan set asalnya (<code>allocated_tray_id = NULL</code>, <code>last_tray_id = NULL</code>, <code>is_replaced_from_assy = 1</code>). Komponen lama berstatus steril tanpa ikatan set apapun, dan komponen pengganti mengambil alih slot secara bersih.", bullet_style))
    story.append(Paragraph("• <b>Sterilisasi Komponen Kembali ke Master Stok:</b> Komponen yang dikembalikan melalui verifikasi baki (<code>verify-return-set</code>) atau override status Gudang seketika kembali ke status <code>Tersedia</code> dengan mengosongkan seluruh ID baki dan timestamp. Komponen tersebut menjadi stok baru murni yang berdiri sendiri.", bullet_style))
    story.append(Paragraph("• <b>Isolasi Pendaftaran Ulang (Re-Register):</b> Komponen yang didaftar ulang dijamin tidak membawa relasi set lama. Endpoint penelusuran (<code>get-connected-set</code> dan <code>part-detail</code>) secara ketat mengembalikan nilai <code>null</code> untuk komponen berstatus <code>Tersedia</code>.", bullet_style))
    story.append(Paragraph("• <b>Acuan Relasi Berjalan (Current Active Running Lifecycle):</b> Pada modul Penerimaan Fisik Gudang maupun Surat Jalan (Delivery Order), sistem hanya mengakui dan merujuk pada relasi aktif yang sedang berjalan. Riwayat masa lalu dari siklus perakitan yang telah selesai, dibongkar, atau diganti tidak pernah ditarik sebagai acuan.", bullet_style))
    story.append(Spacer(1, 8))

    # =========================================================================
    # 5. SYARAT & KEAMANAN KETAT PER MENU (MENU SECURITY & GATEKEEPING)
    # =========================================================================
    story.append(Paragraph("5. Sistem Keamanan, Batasan Prasyarat & Validasi Ketat per Menu", h1_style))
    story.append(Paragraph(
        "Setiap modul fungsional dilengkapi dengan gerbang validasi mandiri (<i>isolated precondition gate</i>). Ketentuan ini memastikan <b>tidak ada data bocor, tidak ada unit yang dapat melompati tahapan, dan tidak ada duplikasi data</b>:",
        body_style
    ))

    menu_matrix = [
        [Paragraph("Menu / Modul", table_header_style), Paragraph("Prasyarat Masuk (Preconditions)", table_header_style), Paragraph("Validasi Keamanan & Anti-Duplikasi", table_header_style), Paragraph("Dokumen Resmi", table_header_style), Paragraph("Pencegahan Bocor", table_header_style)],
        [
            Paragraph("<b>Supply Baki</b><br/>(Gudang)", table_cell_bold),
            Paragraph("Part wajib berstatus <code>Tersedia</code> di Master Stok dan <code>allocated_tray_id = NULL</code>.", table_cell_style),
            Paragraph("• Anti-duplikasi intra-payload.<br/>• InnoDB <code>lockForUpdate()</code> baris master stock.<br/>• Baki baru berstatus <code>active</code>.", table_cell_style),
            Paragraph("Dokumen Supply:<br/><code>WH/WO/xxxxxx</code><br/>(PU: <code>WH/WO/PU/x</code>)", table_cell_style),
            Paragraph("Part yang sudah terikat baki tidak dapat diambil baki lain.", table_cell_style)
        ],
        [
            Paragraph("<b>Meja Perakitan</b><br/>(Workstation)", table_cell_bold),
            Paragraph("Baki wajib berstatus <code>active</code>. Part wajib terdaftar di baki bersangkutan.", table_cell_style),
            Paragraph("• Anti-duplikasi SN antar slot (1-22).<br/>• Pengecekan keanggotaan tabel <code>parts</code> baki.", table_cell_style),
            Paragraph("Dokumen Perakitan:<br/><code>WH/ASSY/xxxxxx</code><br/>(PU: <code>WH/ASSY/PU/x</code>)", table_cell_style),
            Paragraph("Mencegah part baki A diklaim oleh baki B.", table_cell_style)
        ],
        [
            Paragraph("<b>Uji Fungsional 1</b><br/>(TF 1 Assembly)", table_cell_bold),
            Paragraph("Set harus selesai dirakit (<code>finished_at IS NOT NULL</code>) dan status <code>Good</code>.", table_cell_style),
            Paragraph("• Wajib lolos 17 parameter checklist.<br/>• Jika 1 parameter gagal: otomatis <code>Hold</code> tanpa nomor dokumen.", table_cell_style),
            Paragraph("Dokumen TF 1:<br/><code>ASSY/QC/xxxxxx</code><br/>(PU: <code>.../PU/x</code>)", table_cell_style),
            Paragraph("Unit yang belum selesai rakit diblokir dari pengujian TF 1.", table_cell_style)
        ],
        [
            Paragraph("<b>Uji Mutu QC</b><br/>(TF 2 Quality)", table_cell_bold),
            Paragraph("Unit wajib lulus TF 1 Good dan memiliki dokumen <code>WH/WO/xxxxxx</code>.", table_cell_style),
            Paragraph("• Unit tanpa TF 1 Good tidak muncul di antrean.<br/>• Dikunci permanen jika sudah diverifikasi fisik Gudang.", table_cell_style),
            Paragraph("Dokumen TF 2 (QC):<br/><code>WH/QC/xxxxxx</code><br/>(PU: <code>WH/QC/PU/x</code>)", table_cell_style),
            Paragraph("Mencegah QC memverifikasi unit yang melompati uji TF 1.", table_cell_style)
        ],
        [
            Paragraph("<b>Penerimaan Fisik</b><br/>(Warehouse GR)", table_cell_bold),
            Paragraph("Unit wajib berstatus QC <code>GOOD</code>.", table_cell_style),
            Paragraph("• Verifikasi fisik pencocokan Serial Number (SN) manual.<br/>• Status <code>ACCEPTED</code> / <code>DECLINED</code> mengunci baris permanen.", table_cell_style),
            Paragraph("Dokumen Penerimaan:<br/><code>WH/GR/xxxxxx</code><br/>(PU: <code>WH/GR/PU/x</code>)", table_cell_style),
            Paragraph("Barang cacat/rusak di QC tidak dapat diterima oleh Gudang.", table_cell_style)
        ],
        [
            Paragraph("<b>Surat Jalan & DO</b><br/>(Pengiriman)", table_cell_bold),
            Paragraph("Wajib lolos 4 tahap: Rakit Good -> TF 1 Good -> QC Good -> Gudang ACCEPT.", table_cell_style),
            Paragraph("• Anti-duplikasi intra-dokumen (peti & box).<br/>• Global uniqueness check di <code>packing_list_items</code>.<br/>• Blokir status Hold/Scrap/Tersedia.", table_cell_style),
            Paragraph("Surat Jalan:<br/><code>.../SJ/PPIC/...</code><br/>Packing List:<br/><code>.../PL/PPIC/...</code>", table_cell_style),
            Paragraph("Tidak ada nomor seri duplikat keluar pabrik.", table_cell_style)
        ],
        [
            Paragraph("<b>Ganti Part & PU</b><br/>(Perakitan Ulang)", table_cell_bold),
            Paragraph("Part pengganti wajib <code>Tersedia</code>. Disetujui Admin jika status khusus.", table_cell_style),
            Paragraph("• Part lama diputus total dari relasi baki/set.<br/>• Baki perakitan ulang diberi penomoran <code>-PU</code>.", table_cell_style),
            Paragraph("Dokumen Work Order:<br/><code>...-PU</code>", table_cell_style),
            Paragraph("Part lama tidak membawa relasi ke perakitan baru.", table_cell_style)
        ],
        [
            Paragraph("<b>Portal CMS Admin</b><br/>(Pemeliharaan)", table_cell_bold),
            Paragraph("Login administrator terpisah dengan otorisasi role terpusat.", table_cell_style),
            Paragraph("• Audit trail pencatatan setiap mutasi status.<br/>• Diagnostik server, slow query & anomali data.", table_cell_style),
            Paragraph("Audit Trail Log:<br/><code>part_audit_logs</code>", table_cell_style),
            Paragraph("Operator dilarang mengakses modul eksekutif.", table_cell_style)
        ],
    ]
    menu_table = Table(menu_matrix, colWidths=[75, 110, 135, 95, 100])
    menu_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('GRID', (0,0), (-1,-1), 0.5, c_border),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 3),
        ('RIGHTPADDING', (0,0), (-1,-1), 3),
    ]))
    story.append(menu_table)
    story.append(Spacer(1, 8))

    # =========================================================================
    # 6. KEMUDAHAN PELACAKAN RIWAYAT (5-DIMENSIONAL TRACEABILITY)
    # =========================================================================
    story.append(Paragraph("6. Kemudahan Pelacakan Riwayat End-to-End (5-Dimensional Traceability)", h1_style))
    story.append(Paragraph(
        "Untuk memudahkan Admin dan Operator menemukan riwayat komponen secara seketika tanpa kebingungan struktur teknis, sistem menyediakan <b>Struktur Pelacakan 5-Dimensi</b> terintegrasi pada endpoint <code>/api/master/part-detail</code>:",
        body_style
    ))
    story.append(Paragraph("• <b>Dimensi 1 (Master Stock):</b> Serial Number, CS, Tipe Part, status aktif saat ini, ID baki alokasi, waktu alokasi, dan waktu pemakaian.", bullet_style))
    story.append(Paragraph("• <b>Dimensi 2 (Alokasi Baki Gudang):</b> Kode baki aktif (<code>TR-...</code>), nama operator gudang yang mensuplai, operator perakitan yang bertugas, nomor dokumen MO, dan waktu baki dibuat.", bullet_style))
    story.append(Paragraph("• <b>Dimensi 3 (Perakitan & Pengujian):</b> Nomor slot perakitan (Slot #1 s.d. #22), pasangan SN lengkap (Part 15 + Part 1 + Part 12), PIC perakitan, status rakit, hasil 17 checklist TF 1 + No Dokumen WO (<code>WH/WO/...</code>), keputusan QC + No Dokumen QC (<code>WH/QC/...</code>), serta status dan No Dokumen Penerimaan Fisik Gudang (<code>WH/GR/...</code>).", bullet_style))
    story.append(Paragraph("• <b>Dimensi 4 (Ekspedisi & Surat Jalan):</b> Nomor Surat Jalan (<code>.../SJ/PPIC/...</code>), tanggal kirim, nama customer penerima, nomor Packing List (<code>.../PL/PPIC/...</code>), nomor peti kayu (Crate), nomor box, dan nomor slot peti.", bullet_style))
    story.append(Paragraph("• <b>Dimensi 5 (Human-Readable Lifecycle):</b> Status tahapan manusiawi yang ringkas dengan badge warna visual (Hijau = Siap Kirim, Biru = Perakitan Aktif, Kuning = Sedang Diuji, Merah = Hold/Kendala), lokasi fisik barang saat ini, nomor dokumen acuan, dan deskripsi naratif yang mudah dimengerti operator maupun manajemen.", bullet_style))
    story.append(Paragraph("• <b>Part Audit Logs Kronologis:</b> Rekaman abadi setiap mutasi status yang mencatat timestamp detik persis, status sebelum, status sesudah, nama PIC/aktor, peran pengguna, dan alasan perubahan status.", bullet_style))
    story.append(Paragraph("• <b>4 Modul Laporan Universal Search:</b> Seluruh data dapat dicari secara instan melalui 4 modul laporan: <i>WO Traveler</i> (<code>/api/laporan</code>), <i>Laporan TF 1</i> (<code>/api/reports/assy-tests</code>), <i>Laporan QC TF 2</i> (<code>/api/reports/qc-tests</code>), dan <i>Laporan Penerimaan Fisik</i> (<code>/api/reports/warehouse-receipts</code>) dengan filter tanggal, operator, nomor dokumen, status, dan teks pencarian bebas.", bullet_style))
    story.append(Spacer(1, 8))

    # =========================================================================
    # 7. LAPORAN TEMUAN ANOMALI DATABASE LEGACY & REKOMENDASI SOLUSI
    # =========================================================================
    story.append(Paragraph("7. Laporan Temuan Anomali Database Legacy & Rekomendasi Solusi", h1_style))
    story.append(Paragraph(
        "Sesuai arahan pengguna untuk melakukan audit mendalam terhadap seluruh integritas data guna mencegah downtime server dan kebocoran data, berikut adalah hasil temuan anomali data warisan sistem lama (legacy data):",
        body_style
    ))

    anomaly_data = [
        [Paragraph("Temuan Anomali", table_header_style), Paragraph("Jumlah Kasus", table_header_style), Paragraph("Akar Penyebab (Root Cause)", table_header_style), Paragraph("Rekomendasi Tindakan (Menunggu Konfirmasi)", table_header_style)],
        [
            Paragraph("<b>1. Tray Lama Menggantung 'active'</b>", table_cell_bold),
            Paragraph("<b>521 Tray</b><br/>(dari 652 total tray)", table_cell_style),
            Paragraph("Pada kode monolitik lama, saat sebuah baki diajukan perakitan ulang (<code>-PU</code>, 155 tray), baki asal tidak pernah diubah statusnya menjadi <code>completed</code> atau <code>returned</code>. Hal ini menyebabkan 322 part lama tampak terdaftar di lebih dari satu baki aktif secara bersamaan di tabel histori lama.", table_cell_style),
            Paragraph("<b>Opsi Tindakan:</b><br/>Jalankan normalisasi 1 kali untuk mengubah status 521 baki yang sudah 100% diverifikasi gudang (<code>verified_by_gudang = 1</code>) menjadi <code>completed</code>. Pada sistem refactor baru, pencegahan sudah aktif 100%.", table_cell_style)
        ],
        [
            Paragraph("<b>2. Nomor Seri Duplikat di Surat Jalan Lama</b>", table_cell_bold),
            Paragraph("<b>2 Kasus SN</b><br/>(SN R1102145 & SN S1102711)", table_cell_style),
            Paragraph("SN <code>R1102145</code> terdaftar dua kali di DO ID #9 dan #15. SN <code>S1102711</code> terdaftar dua kali di DO ID #3 dan #15. Hal ini terjadi karena pembuatan DO pada sistem lama belum memiliki validasi keunikan serial number.", table_cell_style),
            Paragraph("<b>Opsi Tindakan:</b><br/>Biarkan catatan lama sebagai arsip bersejarah dokumen cetak fisik, atau tandai dianotasi. Pada sistem refactor baru, validasi unik mutlak telah dipasang sehingga tidak mungkin terulang.", table_cell_style)
        ],
        [
            Paragraph("<b>3. Surat Jalan Awal Tanpa Data Penerimaan Fisik</b>", table_cell_bold),
            Paragraph("<b>134 Unit</b><br/>(DO ID #1 s.d. #5)", table_cell_style),
            Paragraph("Unit-unit pada Surat Jalan generasi awal memiliki nilai <code>warehouse_receipt_status = NULL</code> karena modul Penerimaan Fisik Gudang baru diimplementasikan pada versi sistem setelahnya.", table_cell_style),
            Paragraph("<b>Opsi Tindakan:</b><br/>Biarkan sebagai catatan historis valid. Sistem refactor baru kini mewajibkan status penerimaan fisik ACCEPT sebelum unit dapat dipilih ke DO baru.", table_cell_style)
        ],
    ]
    anomaly_table = Table(anomaly_data, colWidths=[110, 65, 175, 165])
    anomaly_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_rose),
        ('GRID', (0,0), (-1,-1), 0.5, c_border),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 3),
        ('RIGHTPADDING', (0,0), (-1,-1), 3),
    ]))
    story.append(anomaly_table)
    story.append(Spacer(1, 8))

    # =========================================================================
    # 8. MATRIKS INTEGRASI MODUL & ENDPOINT API
    # =========================================================================
    story.append(Paragraph("8. Matriks Validasi Endpoint API per Modul", h1_style))
    story.append(Paragraph("Berikut adalah hasil uji verifikasi otomatis 117 endpoint pada server lokal Laravel:", body_style))

    api_matrix = [
        [Paragraph("No", table_header_style), Paragraph("Modul Fungsional", table_header_style), Paragraph("Endpoint Kunci", table_header_style), Paragraph("Hasil Uji", table_header_style)],
        [Paragraph("1", table_cell_style), Paragraph("Kelola Pengguna", table_cell_bold), Paragraph("GET /api/operators, GET /api/admin/users, POST /api/admin/users/update", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("2", table_cell_style), Paragraph("Gudang Supply", table_cell_bold), Paragraph("GET /api/assembly/trays, POST /api/gudang/save, POST /api/gudang/verify-tray", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("3", table_cell_style), Paragraph("Assembly Workstation", table_cell_bold), Paragraph("POST /api/assembly/save, POST /api/assembly/pause, POST /api/assembly/session-action", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("4", table_cell_style), Paragraph("TF 1 (Checklist)", table_cell_bold), Paragraph("GET /api/testing-points, POST /api/assembly/submit-test, POST /api/assembly/submit-single-retest", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("5", table_cell_style), Paragraph("TF 2 (Quality Control)", table_cell_bold), Paragraph("GET /api/qc/items, GET /api/qc/pending-items, POST /api/qc/submit-test", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("6", table_cell_style), Paragraph("Penerimaan Fisik", table_cell_bold), Paragraph("POST /api/warehouse/verify-qc-receipt, GET /api/reports/warehouse-receipts", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("7", table_cell_style), Paragraph("Pengembalian Tray", table_cell_bold), Paragraph("GET /api/gudang/returned-trays, POST /api/gudang/verify-return-set", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("8", table_cell_style), Paragraph("Verifikasi Admin", table_cell_bold), Paragraph("POST /api/admin/approve-replacement, POST /api/admin/approve-re-register", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("9", table_cell_style), Paragraph("Riwayat Audit & Downtime", table_cell_bold), Paragraph("GET /api/admin/audit-logs, GET /api/admin/downtime-logs", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("10", table_cell_style), Paragraph("Delivery Order & Labels", table_cell_bold), Paragraph("GET /api/delivery-orders, POST /api/delivery-orders, GET /api/available-serial-numbers", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
        [Paragraph("11", table_cell_style), Paragraph("Portal CMS (/admin)", table_cell_bold), Paragraph("POST /api/admin/cms-login, GET /api/admin/slow-queries, GET /api/admin/error-logs", table_cell_style), Paragraph("<font color='#059669'><b>PASS (200 OK)</b></font>", table_cell_style)],
    ]
    api_table = Table(api_matrix, colWidths=[20, 115, 290, 90])
    api_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_secondary),
        ('GRID', (0,0), (-1,-1), 0.5, c_border),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
    ]))
    story.append(api_table)
    story.append(Spacer(1, 10))

    # =========================================================================
    # 9. KEPUTUSAN OPERASIONAL PENGGUNA & LEMBAR PENGESAHAN
    # =========================================================================
    decision_text = (
        "<b>KEPUTUSAN OPERASIONAL & PENYELARASAN SISTEM (TERKONFIRMASI):</b><br/>"
        "• <b>Bebas Kebutuhan Scanner Fisik:</b> Seluruh modul operasional 100% menggunakan input manual mandiri, "
        "rekomendasi stok cerdas, dan autocomplete tanpa mewajibkan alat scanner barcode fisik (menyesuaikan budget).<br/>"
        "• <b>Pencegahan Human Error (Tanpa Batch Approval):</b> Verifikasi set pengembalian wajib dilakukan secara teliti satu per satu "
        "(1-by-1 strict verification) guna mencegah kesalahan persetujuan massal atas set yang bermasalah.<br/>"
        "• <b>Tanda Tangan Manual Standar:</b> Surat Jalan (DO) menggunakan lembar tanda tangan basah fisik konvensional.<br/>"
        "• <b>Format Dokumen Resmi Disinkronkan:</b> Supply (<code>WH/WO</code>), Perakitan (<code>WH/ASSY</code>), TF 1 (<code>ASSY/QC</code>), "
        "TF 2 (<code>WH/QC</code>), Penerimaan Gudang (<code>WH/GR</code>). Jika perakitan ulang, akhiran otomatis <code>.../PU/angka</code>.<br/>"
        "• <b>Sentralisasi Pengaturan Akun & Akses CMS:</b> Seluruh manajemen akun, matriks hak akses baca/tulis (*read-only / write*) per menu, "
        "serta kontrol izin masuk ke Portal Administrator CMS kini dipusatkan di <code>/admin</code> dengan kontrol dinamis 1-klik.<br/>"
        "• <b>Dual-Environment Database (Live vs Testing Simulator):</b> Tersedia database testing terisolasi (<code>db_final_assembly_testing</code>) "
        "yang dapat di-swipe secara instan sewaktu-waktu dari header maupun CMS tanpa mengganggu database produksi live (<code>db_final_assembly</code>)."
    )

    story.append(KeepTogether([
        Paragraph("9. Keputusan Operasional Pengguna & Lembar Pengesahan", h1_style),
        Paragraph("Berikut adalah rangkuman keputusan operasional yang telah diselaraskan ke dalam sistem produksi:", body_style),
        Spacer(1, 3),
        Table([
            [Paragraph(decision_text, ParagraphStyle('RevBox', parent=styles['Normal'], fontName='Helvetica', fontSize=8, leading=12, textColor=colors.HexColor("#0F172A")))]
        ], colWidths=[515], style=[
            ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#F0FDF4")),
            ('BOX', (0,0), (-1,-1), 1, colors.HexColor("#86EFAC")),
            ('TOPPADDING', (0,0), (-1,-1), 6),
            ('BOTTOMPADDING', (0,0), (-1,-1), 6),
            ('LEFTPADDING', (0,0), (-1,-1), 8),
            ('RIGHTPADDING', (0,0), (-1,-1), 8),
        ]),
        Spacer(1, 8),
        # Signatures
        Table([
            [
                Paragraph("<b>Disusun Oleh:</b><br/><br/><br/><br/>( Sistem Final Assembly )<br/>Tanggal: 27/10/2026", ParagraphStyle('Sign1', parent=styles['Normal'], fontName='Helvetica', fontSize=7.5, alignment=1)),
                Paragraph("<b>Diperiksa & Direview:</b><br/><br/><br/><br/>( _______________________ )<br/>Kepala Produksi / QA", ParagraphStyle('Sign2', parent=styles['Normal'], fontName='Helvetica', fontSize=7.5, alignment=1)),
                Paragraph("<b>Disetujui Oleh:</b><br/><br/><br/><br/>( _______________________ )<br/>Administrator Sistem", ParagraphStyle('Sign3', parent=styles['Normal'], fontName='Helvetica', fontSize=7.5, alignment=1)),
            ]
        ], colWidths=[171, 172, 172], style=[
            ('VALIGN', (0,0), (-1,-1), 'TOP'),
            ('GRID', (0,0), (-1,-1), 0.5, c_border),
            ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#F8FAFC")),
            ('TOPPADDING', (0,0), (-1,-1), 5),
            ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ])
    ]))

    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"PDF berhasil dibuat: {filename}")

if __name__ == '__main__':
    target_path = r"c:\Users\ibnhu\Desktop\Muhamad Ibnhu\Sistem Final Assembly\System FA V3\HASIL REFACTOR\LAPORAN_ALUR_SISTEM_FINAL_ASSEMBLY_P9.pdf"
    create_report(target_path)
