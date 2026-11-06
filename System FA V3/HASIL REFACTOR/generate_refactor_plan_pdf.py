# generate_refactor_plan_pdf.py
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
        
        # Header on later pages (page 2+)
        if self._pageNumber > 1:
            self.setFont("Helvetica-Bold", 8)
            self.setFillColor(colors.HexColor("#1E3A8A"))
            self.drawString(36, 810, "SISTEM FINAL ASSEMBLY & TRACEABILITY NANGGALA P9")
            self.setFont("Helvetica", 8)
            self.setFillColor(colors.HexColor("#64748B"))
            self.drawRightString(559, 810, "Dokumen Rencana Refactor Arsitektur V3.0")
            self.setStrokeColor(colors.HexColor("#CBD5E1"))
            self.setLineWidth(0.5)
            self.line(36, 804, 559, 804)

        # Footer on all pages
        self.setStrokeColor(colors.HexColor("#CBD5E1"))
        self.setLineWidth(0.5)
        self.line(36, 42, 559, 42)

        self.setFont("Helvetica-Bold", 8)
        self.setFillColor(colors.HexColor("#DC2626"))
        self.drawString(36, 30, "CONFIDENTIAL")
        self.setFont("Helvetica", 8)
        self.setFillColor(colors.HexColor("#64748B"))
        self.drawString(108, 30, "- Dokumen Perencanaan Arsitektur & Roadmap Rekayasa Ulang Sistem FA")
        page_text = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(559, 30, page_text)

        self.restoreState()

def create_pdf(filename="DOKUMEN_PERENCANAAN_REFACTOR_SISTEM_FA_V3.pdf"):
    printable_width = 523 # 595 - 72

    doc = SimpleDocTemplate(
        filename,
        pagesize=A4,
        leftMargin=36,
        rightMargin=36,
        topMargin=46,
        bottomMargin=50
    )

    styles = getSampleStyleSheet()

    # Color Palette
    c_primary = colors.HexColor("#1E3A8A")     # Navy
    c_secondary = colors.HexColor("#0D9488")   # Teal / Emerald
    c_dark = colors.HexColor("#0F172A")        # Slate 900
    c_text = colors.HexColor("#334155")        # Slate 700
    c_muted = colors.HexColor("#64748B")       # Slate 500
    c_light = colors.HexColor("#F8FAFC")       # Slate 50
    c_border = colors.HexColor("#CBD5E1")      # Slate 300
    c_emerald = colors.HexColor("#059669")     # Green
    c_amber = colors.HexColor("#D97706")       # Amber
    c_rose = colors.HexColor("#DC2626")        # Red

    # Typography Styles
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

    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=14,
        textColor=c_secondary,
        alignment=0,
        spaceAfter=12
    )

    h1_style = ParagraphStyle(
        'Heading1_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=16,
        textColor=colors.white,
        spaceBefore=0,
        spaceAfter=0
    )

    h2_style = ParagraphStyle(
        'Heading2_Custom',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10.5,
        leading=14,
        textColor=c_primary,
        spaceBefore=10,
        spaceAfter=4
    )

    body_style = ParagraphStyle(
        'Body_Custom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.5,
        leading=12,
        textColor=c_text,
        spaceAfter=5
    )

    body_bold = ParagraphStyle(
        'Body_Bold',
        parent=body_style,
        fontName='Helvetica-Bold'
    )

    bullet_style = ParagraphStyle(
        'Bullet_Custom',
        parent=body_style,
        leftIndent=12,
        firstLineIndent=-8,
        spaceAfter=3
    )

    table_header = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.white,
        alignment=1
    )

    table_cell = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.5,
        leading=10,
        textColor=c_text
    )

    table_cell_bold = ParagraphStyle(
        'TableCellBold',
        parent=table_cell,
        fontName='Helvetica-Bold',
        textColor=c_dark
    )

    table_cell_center = ParagraphStyle(
        'TableCellCenter',
        parent=table_cell,
        alignment=1
    )

    table_cell_code = ParagraphStyle(
        'TableCellCode',
        parent=table_cell,
        fontName='Courier',
        fontSize=7,
        leading=9,
        textColor=colors.HexColor("#0F766E")
    )

    callout_text = ParagraphStyle(
        'CalloutText',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=11,
        textColor=c_dark
    )

    code_block = ParagraphStyle(
        'CodeBlock',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=7.5,
        leading=10,
        textColor=colors.HexColor("#065F46")
    )

    def section_header(title):
        t = Table([[Paragraph(title, h1_style)]], colWidths=[printable_width])
        t.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,-1), c_primary),
            ('PADDING', (0,0), (-1,-1), 5),
            ('BOTTOMPADDING', (0,0), (-1,-1), 6),
            ('TOPPADDING', (0,0), (-1,-1), 6),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ]))
        return t

    def callout_box(text, bg_color="#EFF6FF", border_color="#3B82F6", prefix="INFO"):
        p = Paragraph(f"<b>[{prefix}]</b> {text}", callout_text)
        t = Table([[p]], colWidths=[printable_width])
        t.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,-1), colors.HexColor(bg_color)),
            ('BOX', (0,0), (-1,-1), 1, colors.HexColor(border_color)),
            ('PADDING', (0,0), (-1,-1), 6),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ]))
        return t

    story = []

    # =========================================================================
    # HEADER / BANNER DOKUMEN
    # =========================================================================
    header_data = [
        [
            Paragraph("DOKUMEN PERENCANAAN STRATEGIS REFACTOR", ParagraphStyle('HeaderBadge', fontName='Helvetica-Bold', fontSize=8, textColor=colors.HexColor("#2563EB"))),
            Paragraph("KODE DOK: <b>NKR-SOP-FA-REF-01</b>", ParagraphStyle('DocCode', fontName='Helvetica-Bold', fontSize=8, textColor=c_muted, alignment=2))
        ]
    ]
    t_top = Table(header_data, colWidths=[300, 223])
    t_top.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'BOTTOM'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 2),
    ]))
    story.append(t_top)
    story.append(HRFlowable(width="100%", thickness=1, color=c_border, spaceBefore=2, spaceAfter=8))

    story.append(Paragraph("PERENCANAAN ARSITEKTUR & ROADMAP REFACTOR SISTEM FINAL ASSEMBLY & TRACEABILITY NANGGALA P9 (VERSI 3.0)", title_style))
    story.append(Paragraph("Strategi Pemisahan Sistem Monolitik (17.362 Baris) Menjadi Arsitektur Modular, Clean URL Routing, dan Standarisasi Industri", subtitle_style))

    # Metadata Box
    meta_data = [
        [
            Paragraph("<b>Target Sistem:</b> Sistem Final Assembly & Traceability (V3)", table_cell),
            Paragraph("<b>Tanggal Rencana:</b> 04 November 2026", table_cell),
            Paragraph("<b>Klasifikasi:</b> CONFIDENTIAL / INTERNAL", table_cell)
        ],
        [
            Paragraph("<b>Stack Teknologi:</b> Laravel 11, Vue 3, MariaDB", table_cell),
            Paragraph("<b>Status Dokumen:</b> Baseline Resmi Refactor", table_cell),
            Paragraph("<b>Sifat Tindakan:</b> Zero Business Logic Change", table_cell)
        ]
    ]
    t_meta = Table(meta_data, colWidths=[180, 173, 170])
    t_meta.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), c_light),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('PADDING', (0,0), (-1,-1), 4),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t_meta)
    story.append(Spacer(1, 10))

    # =========================================================================
    # BAB 1: LATAR BELAKANG & ANALISIS MASALAH AWAL
    # =========================================================================
    story.append(section_header("BAB 1 — LATAR BELAKANG & ROOT CAUSE ANALYSIS"))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Sistem Final Assembly & Traceability Nanggala P9 (Sistem FA V3) merupakan perangkat lunak inti lini produksi alutsista "
        "yang mencakup pencatatan Work Order, verifikasi penerimaan part fisik gudang, 10 tahap perakitan unit, pengujian fungsional "
        "operator (TF1), verifikasi penjaminan mutu (QC/TF2), pencatatan Hold/Rakit Ulang, penerimaan fisik gudang (WH/GR), "
        "penerbitan Surat Jalan (Delivery Order), hingga cetak Packing List & Barcode Stiker. "
        "Sebelum dilakukan refactor, sistem mengalami degradasi performa serius (lag berat dan keterlambatan respon klik).",
        body_style
    ))

    story.append(Paragraph("<b>Hasil Investigasi & Analisis Penyebab Utama (Root Causes):</b>", h2_style))

    issue_table_data = [
        [
            Paragraph("Masalah / Bottleneck", table_header),
            Paragraph("Kondisi Sebelum Refactor", table_header),
            Paragraph("Dampak Teknis & Operasional", table_header)
        ],
        [
            Paragraph("<b>Monolitik app.js Ekstrem</b>", table_cell_bold),
            Paragraph("Satu file JavaScript tunggal membengkak hingga <b>17.362 baris (~856 KB)</b>.", table_cell),
            Paragraph("Browser harus mem-parse seluruh logika 14 modul sekaligus saat pertama kali dibuka, memicu lag dan konsumsi RAM browser tinggi.", table_cell)
        ],
        [
            Paragraph("<b>Vue Dev Build & Console Spam</b>", table_cell_bold),
            Paragraph("Menggunakan <font face='Courier'>vue.global.js</font> (development mode, ~623 KB).", table_cell),
            Paragraph("Konsol F12 dipenuhi puluhan warning spam render. DevTools menjadi lambat dan menyulitkan pelacakan error nyata di lini pabrik.", table_cell)
        ],
        [
            Paragraph("<b>Overhead DOM Rendering (v-show)</b>", table_cell_bold),
            Paragraph("Seluruh 14 modul dan ratusan modal memakai directive <font face='Courier'>v-show</font>.", table_cell),
            Paragraph("Browser terpaksa merender >50.000 elemen DOM secara bersamaan meski tab disembunyikan. Seluruh watcher reaktif aktif serentak.", table_cell)
        ],
        [
            Paragraph("<b>Ketiadaan Logging Terpusat</b>", table_cell_bold),
            Paragraph("Error HTTP dan backend hanya muncul sebagai error unhandled mentah.", table_cell),
            Paragraph("Operator dan pengembang tidak mengetahui di modul mana dan pada nomor part apa kegagalan proses terjadi.", table_cell)
        ],
        [
            Paragraph("<b>Navigasi Berbasis Hash (#)</b>", table_cell_bold),
            Paragraph("Navigasi antar menu bergantung pada string hash internal (misal <font face='Courier'>#delivery_order</font>).", table_cell),
            Paragraph("User tidak bisa mem-bookmark menu spesifik, tombol Back/Forward browser sering macet/double URL, dan direct link gagal dibuka.", table_cell)
        ]
    ]

    t_issues = Table(issue_table_data, colWidths=[120, 180, 223])
    t_issues.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('PADDING', (0,0), (-1,-1), 4),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t_issues)
    story.append(Spacer(1, 8))

    story.append(callout_box(
        "Prinsip Fundamental Refactor: <b>Zero Business Logic Change</b>. Seluruh alur proses pabrik, struktur data database MariaDB, "
        "kalkulasi Serial Number, dan hak akses otorisasi tidak diubah sama sekali; yang direkonstruksi adalah arsitektur kode "
        "agar modular, cepat, bersih, dan berstandar industri.",
        bg_color="#ECFDF5", border_color="#10B981", prefix="PRINSIP REFACTOR"
    ))

    story.append(Spacer(1, 10))

    # =========================================================================
    # BAB 2: STRUKTUR MODULAR BARU & PENJELASAN MODUL
    # =========================================================================
    story.append(section_header("BAB 2 — ARSITEKTUR BARU & STRUKTUR MODULAR FONDASI"))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Arsitektur monolitik dipecah menjadi susunan modul mandiri di dalam folder <b><font face='Courier'>public/js/modules/</font></b>. "
        "File <font face='Courier'>app.js</font> dirampingkan secara masif menjadi entry point orkestrator yang ringan (~50 KB) "
        "dan hanya memuat modul yang dibutuhkan saat user membuka menu terkait.",
        body_style
    ))

    modules_table_data = [
        [
            Paragraph("File Modul", table_header),
            Paragraph("Peran & Ruang Lingkup", table_header),
            Paragraph("Estimasi Baris", table_header),
            Paragraph("Tanggung Jawab Operasional", table_header)
        ],
        [
            Paragraph("<b>logger.js</b>", table_cell_code),
            Paragraph("Fondasi Logging Terpusat", table_cell_bold),
            Paragraph("~220 baris", table_cell_center),
            Paragraph("Mencatat event navigasi, mutasi data, dan membedah error HTTP terstruktur sesuai istilah operasional pabrik.", table_cell)
        ],
        [
            Paragraph("<b>utils.js</b>", table_cell_code),
            Paragraph("Kotak Peralatan Helper Bersama", table_cell_bold),
            Paragraph("~280 baris", table_cell_center),
            Paragraph("Formatting tanggal WIB (UTC+7), durasi pengerjaan, format tanggal Indonesia, class badge CSS, debounce, & clipboard.", table_cell)
        ],
        [
            Paragraph("<b>api.js</b>", table_cell_code),
            Paragraph("Engine Komunikasi HTTP Terpusat", table_cell_bold),
            Paragraph("~260 baris", table_cell_center),
            Paragraph("Otomatis menyertakan Bearer Token & CSRF, auto-redirect login saat 401, serta auto-retry cerdas untuk kendala jaringan.", table_cell)
        ],
        [
            Paragraph("<b>modules/delivery.js</b>", table_cell_code),
            Paragraph("Delivery Order & Packing List", table_cell_bold),
            Paragraph("~3.570 baris", table_cell_center),
            Paragraph("Surat jalan, kemasan peti/dus/satuan, stepper simetris 5x2, Input Bulking SN, sinkronisasi Cloudinary, dan ekspor Excel.", table_cell)
        ],
        [
            Paragraph("<b>modules/qc.js</b>", table_cell_code),
            Paragraph("Quality Control / Test Functional 2", table_cell_bold),
            Paragraph("~540 baris", table_cell_center),
            Paragraph("Pemeriksaan akhir tray QC, form checklist pengujian QC, pencatatan alasan Hold QC, dan cetak dokumen mutu WH/QC.", table_cell)
        ],
        [
            Paragraph("<b>modules/tf1.js</b>", table_cell_code),
            Paragraph("Test Functional 1 (Operator)", table_cell_bold),
            Paragraph("~1.200 baris", table_cell_center),
            Paragraph("Pengujian fungsional perakitan unit (parameter visual, mekanik, kelistrikan), single retest, dan cetak ASSY/QC.", table_cell)
        ],
        [
            Paragraph("<b>modules/assembly.js</b>", table_cell_code),
            Paragraph("Area Perakitan Utama (Assembly)", table_cell_bold),
            Paragraph("~2.100 baris", table_cell_center),
            Paragraph("Alokasi tray, perakitan 10 set part (Part 1 hingga Part 15), barcode scanner, input manual, dan validasi fisik.", table_cell)
        ],
        [
            Paragraph("<b>modules/holds.js</b>", table_cell_code),
            Paragraph("Hasil Perakitan, Hold, & Rakit Ulang", table_cell_bold),
            Paragraph("~1.800 baris", table_cell_center),
            Paragraph("Daftar unit tertahan (Production & Functional Hold), perakitan ulang (Reassembly), dan penggantian part reject.", table_cell)
        ],
        [
            Paragraph("<b>modules/warehouse.js</b>", table_cell_code),
            Paragraph("Manajemen Gudang & Penerimaan", table_cell_bold),
            Paragraph("~1.600 baris", table_cell_center),
            Paragraph("Penerimaan barang jadi (WH/GR), supply part tray, verifikasi aktual fisik pasca-QC, dan retur unit.", table_cell)
        ],
        [
            Paragraph("<b>modules/reports.js</b>", table_cell_code),
            Paragraph("Laporan & Rekapitulasi Dokumen", table_cell_bold),
            Paragraph("~1.400 baris", table_cell_center),
            Paragraph("Laporan produksi WO, rekap uji TF1/QC, analitik downtime, traceability, dan ekspor laporan resmi Excel.", table_cell)
        ],
        [
            Paragraph("<b>modules/traveler.js</b>", table_cell_code),
            Paragraph("Dokumen Pelacak & Barcode Stiker", table_cell_bold),
            Paragraph("~900 baris", table_cell_center),
            Paragraph("Visualisasi lembar traveler tray, cetak barcode QR stiker komponen, dan riwayat sirkulasi tray pabrik.", table_cell)
        ],
        [
            Paragraph("<b>modules/admin.js</b>", table_cell_code),
            Paragraph("Master Stock & Manajemen Hak Akses", table_cell_bold),
            Paragraph("~1.500 baris", table_cell_center),
            Paragraph("Manajemen master stock part, approval admin verifikasi set, manajemen user multi-departemen, dan audit log sistem.", table_cell)
        ]
    ]

    t_modules = Table(modules_table_data, colWidths=[95, 125, 65, 238])
    t_modules.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('PADDING', (0,0), (-1,-1), 3),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t_modules)

    story.append(PageBreak())

    # =========================================================================
    # BAB 3: CLEAN URL ROUTING & NAVIGASI
    # =========================================================================
    story.append(section_header("BAB 3 — SISTEM CLEAN URL ROUTING & NAVIGASI MANDIRI"))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Salah satu pilar utama dalam refactor Fase 2 adalah penerapan <b>Clean URL Routing</b> menggantikan hash routing. "
        "Setiap menu operasional kini memiliki endpoint URL mandiri yang dapat diakses secara langsung (*direct link*), "
        "dapat disimpan pada bookmark browser, serta mendukung tombol Back/Forward browser dengan sempurna tanpa reload halaman.",
        body_style
    ))

    url_table_data = [
        [
            Paragraph("Alamat URL Bersih", table_header),
            Paragraph("Nama Menu Operasional", table_header),
            Paragraph("Fungsi & Hak Akses", table_header)
        ],
        [
            Paragraph("<b>/perakitan</b>", table_cell_code),
            Paragraph("Area Perakitan", table_cell_bold),
            Paragraph("Lini perakitan set tray oleh operator assembly.", table_cell)
        ],
        [
            Paragraph("<b>/tf1</b>", table_cell_code),
            Paragraph("Test Functional 1 (Assy)", table_cell_bold),
            Paragraph("Uji fungsional parameter teknis oleh operator sebelum dikirim ke QC.", table_cell)
        ],
        [
            Paragraph("<b>/qc</b>", table_cell_code),
            Paragraph("Test Functional 2 (QC)", table_cell_bold),
            Paragraph("Pengujian mutu dan persetujuan Good oleh tim Quality Control.", table_cell)
        ],
        [
            Paragraph("<b>/delivery</b>", table_cell_code),
            Paragraph("Delivery Order & Packing List", table_cell_bold),
            Paragraph("Penerbitan Surat Jalan, alokasi SN kemasan, dan label packing list.", table_cell)
        ],
        [
            Paragraph("<b>/history</b>", table_cell_code),
            Paragraph("History / Laporan Produksi", table_cell_bold),
            Paragraph("Rekapitulasi riwayat pengerjaan dokumen tray dan ekspor Excel.", table_cell)
        ],
        [
            Paragraph("<b>/work-order</b>", table_cell_code),
            Paragraph("Work Order (Input Part)", table_cell_bold),
            Paragraph("Penerimaan dan registrasi part komponen fisik dari gudang.", table_cell)
        ],
        [
            Paragraph("<b>/hasil-perakitan</b>", table_cell_code),
            Paragraph("Hasil Perakitan (Production Hold)", table_cell_bold),
            Paragraph("Monitoring unit tertahan pada tahap perakitan dan reassembly.", table_cell)
        ],
        [
            Paragraph("<b>/hasil-test</b>", table_cell_code),
            Paragraph("Hasil Test Functional (TF Hold)", table_cell_bold),
            Paragraph("Daftar unit tertahan pada pengujian fungsional 1 dan 2.", table_cell)
        ],
        [
            Paragraph("<b>/penerimaan</b>", table_cell_code),
            Paragraph("Good Receipts (WH/GR)", table_cell_bold),
            Paragraph("Konfirmasi fisik serah terima barang jadi dari QC ke gudang penyimpanan.", table_cell)
        ],
        [
            Paragraph("<b>/rakit-ulang</b>", table_cell_code),
            Paragraph("Perakitan Ulang (Reassembly)", table_cell_bold),
            Paragraph("Proses re-assembly set unit yang mengalami kendala teknis / part reject.", table_cell)
        ],
        [
            Paragraph("<b>/traveler</b>", table_cell_code),
            Paragraph("Cetak Dokumen & Barcode", table_cell_bold),
            Paragraph("Pencetakan lembar traveler tray dan label barcode komponen.", table_cell)
        ],
        [
            Paragraph("<b>/master-stok</b>", table_cell_code),
            Paragraph("Master Stok & Komponen", table_cell_bold),
            Paragraph("Pengaturan stok material dan batas minimum kuantitas part.", table_cell)
        ],
        [
            Paragraph("<b>/verifikasi</b>", table_cell_code),
            Paragraph("Verifikasi Rakitan & Test", table_cell_bold),
            Paragraph("Panel persetujuan admin untuk part pengganti dan set perbaikan.", table_cell)
        ]
    ]

    t_url = Table(url_table_data, colWidths=[120, 160, 243])
    t_url.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('PADDING', (0,0), (-1,-1), 3),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t_url)
    story.append(Spacer(1, 10))

    # =========================================================================
    # BAB 4: ROADMAP EKSEKUSI TAHAP DEMI TAHAP
    # =========================================================================
    story.append(section_header("BAB 4 — ROADMAP PELAKSANAAN REFACTOR BERTAHAP (3 FASE)"))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Pelaksanaan refactor dilakukan secara ketat mengikuti metodologi <b>Step-by-Step Verification</b>. "
        "Setiap satu fase selesai dikerjakan, sistem diuji secara otomatis dan diverifikasi langsung di browser sebelum melangkah ke tahap selanjutnya.",
        body_style
    ))

    roadmap_data = [
        [
            Paragraph("Fase", table_header),
            Paragraph("Nama Tahap & Ruang Lingkup", table_header),
            Paragraph("Status Saat Ini", table_header),
            Paragraph("Keluaran / Deliverable", table_header)
        ],
        [
            Paragraph("<b>Fase 1A</b>", table_cell_center),
            Paragraph("Vue Production Build & Tailwind Filter", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Mengganti vue.global.js ke vue.global.prod.js (~150 KB). Seluruh warning render & dev spam hilang total.", table_cell)
        ],
        [
            Paragraph("<b>Fase 1B</b>", table_cell_center),
            Paragraph("Sistem Logging Terpusat (logger.js)", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Pembuatan logger.js, utils.js, api.js. Logging otomatis pada 118 endpoint API dengan istilah operasional pabrik.", table_cell)
        ],
        [
            Paragraph("<b>Fase 1C</b>", table_cell_center),
            Paragraph("Audit DOM Conditional Rendering (v-if)", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Audit seluruh 14 modul Blade dan 185 modal dialog memakai v-if. DOM load berkurang drastis ~70%.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2A</b>", table_cell_center),
            Paragraph("Clean URL Routing Tanpa Hash (#)", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Implementasi URL bersih (/perakitan, /delivery, /qc, dst.). Direct link & tombol Back/Forward browser berfungsi normal.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2B</b>", table_cell_center),
            Paragraph("Modularisasi Delivery Order & PL (delivery.js)", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Ekstraksi 3.571 baris ke public/js/modules/delivery.js. Stepper simetris 5x2, Input Bulking SN, perampingan app.js.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2C</b>", table_cell_center),
            Paragraph("Modularisasi Quality Control (qc.js)", table_cell_bold),
            Paragraph("<font color='#059669'><b>SELESAI ✅</b></font>", table_cell_center),
            Paragraph("Ekstraksi 421 baris ke public/js/modules/qc.js (544 baris). Form pengujian QC, checklist parameter, rekap tray.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2D</b>", table_cell_center),
            Paragraph("Modularisasi Test Functional 1 (tf1.js)", table_cell_bold),
            Paragraph("<font color='#D97706'><b>Berikutnya ⏳</b></font>", table_cell_center),
            Paragraph("Pemisahan logika pengujian fungsional operator assembly, single retest, dan cetak dokumen mutu ASSY/QC.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2E</b>", table_cell_center),
            Paragraph("Modularisasi Area Perakitan (assembly.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan logika pengerjaan tray 10 set, alokasi serial number komponen Part 1-15, dan scanner barcode.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2F</b>", table_cell_center),
            Paragraph("Modularisasi Hold & Rakit Ulang (holds.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan manajemen Production Hold, Functional Hold, Reassembly, dan penggantian komponen problematik.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2G</b>", table_cell_center),
            Paragraph("Modularisasi Gudang / Warehouse (warehouse.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan modul penerimaan barang jadi (WH/GR), supply part tray, dan retur unit.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2H</b>", table_cell_center),
            Paragraph("Modularisasi Laporan & Riwayat (reports.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan reporting pipeline, analitik downtime pengerjaan, dan generator ekspor Excel multi-sheet.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2I</b>", table_cell_center),
            Paragraph("Modularisasi Traveler & Cetak (traveler.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan lembar kerja pelacak tray dan rendering barcode stiker komponen.", table_cell)
        ],
        [
            Paragraph("<b>Fase 2J</b>", table_cell_center),
            Paragraph("Modularisasi Admin CMS & Master (admin.js)", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemisahan manajemen user, otorisasi RBAC departemen, master stok part, dan audit trails.", table_cell)
        ],
        [
            Paragraph("<b>Tahap Akhir</b>", table_cell_center),
            Paragraph("Pengarsipan _deprecated/ & Standarisasi Industri", table_cell_bold),
            Paragraph("<font color='#64748B'>Menunggu</font>", table_cell_center),
            Paragraph("Pemindahan file usang ke folder _deprecated/, audit Clean Code menyeluruh, dan verifikasi zero-dead-code.", table_cell)
        ]
    ]

    t_roadmap = Table(roadmap_data, colWidths=[55, 175, 75, 218])
    t_roadmap.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('PADDING', (0,0), (-1,-1), 3),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t_roadmap)

    story.append(PageBreak())

    # =========================================================================
    # BAB 5: STANDARISASI INDUSTRI & ATURAN CLEANUP FILE
    # =========================================================================
    story.append(section_header("BAB 5 — STANDARISASI INDUSTRI, CLEAN CODE & PENGARSIPAN"))
    story.append(Spacer(1, 6))

    story.append(Paragraph("<b>1. Pemisahan Tanggung Jawab (Separation of Concerns):</b>", h2_style))
    story.append(Paragraph("• <b>Layer Tampilan (View):</b> Template Blade hanya bertugas menyajikan struktur visual dan tata letak UI tanpa menyematkan logika bisnis yang berat.", bullet_style))
    story.append(Paragraph("• <b>Layer Logika Klien (Client State):</b> Setiap modul JavaScript di folder <font face='Courier'>public/js/modules/</font> bertanggung jawab penuh atas state, validasi input, dan reaktivitas menunya sendiri (Single Responsibility Principle).", bullet_style))
    story.append(Paragraph("• <b>Layer Helper Bersama:</b> Mengeliminasi duplikasi kode (DRY) dengan memusatkan fungsi umum ke <font face='Courier'>utils.js</font>, <font face='Courier'>api.js</font>, dan <font face='Courier'>logger.js</font>.", bullet_style))

    story.append(Spacer(1, 4))
    story.append(Paragraph("<b>2. Standar Penamaan & Kerapihan Kode (Naming Conventions):</b>", h2_style))
    story.append(Paragraph("• <b>JavaScript:</b> Menggunakan standar <i>camelCase</i> untuk nama fungsi dan variabel (<font face='Courier'>fetchTrayData</font>, <font face='Courier'>loadProductionHoldItems</font>), serta <i>UPPER_SNAKE_CASE</i> untuk konstanta.", bullet_style))
    story.append(Paragraph("• <b>Laravel / PHP:</b> Menggunakan <i>camelCase</i> untuk method Controller dan <i>snake_case</i> untuk atribut database MariaDB.", bullet_style))
    story.append(Paragraph("• <b>URL & File View:</b> Menggunakan <i>kebab-case</i> (<font face='Courier'>/perakitan</font>, <font face='Courier'>delivery-orders/index.blade.php</font>).", bullet_style))

    story.append(Spacer(1, 4))
    story.append(Paragraph("<b>3. Manajemen Error & Ketahanan Sistem (Error Resilience):</b>", h2_style))
    story.append(Paragraph("• Seluruh request asinkronus dibungkus blok <font face='Courier'>try-catch</font> terstruktur dan otomatis dicatat oleh logger.", bullet_style))
    story.append(Paragraph("• Pesan notifikasi error ke operator menggunakan Toast / SweetAlert2 dengan bahasa operasional yang jelas (misal: <i>'Serial number belum lolos QC gudang'</i>).", bullet_style))

    story.append(Spacer(1, 6))
    story.append(Paragraph("<b>4. Struktur Folder Arsip File Deprecated (<font face='Courier'>_deprecated/</font>):</b>", h2_style))
    story.append(Paragraph(
        "Di akhir seluruh rangkaian refactor, semua file lama yang tidak lagi aktif dipanggil oleh sistem akan dipindahkan ke folder khusus <b><font face='Courier'>_deprecated/</font></b>. "
        "File <b>tidak dihapus permanen</b> melainkan diarsipkan rapi sebagai cadangan historis (reference archive).",
        body_style
    ))

    arch_data = [
        [
            Paragraph("Direktori Arsip", table_header),
            Paragraph("Rencana Isi File yang Dipindahkan", table_header),
            Paragraph("Aturan Keamanan Sistem", table_header)
        ],
        [
            Paragraph("<b>_deprecated/js/</b>", table_cell_code),
            Paragraph("File monolitik lama <font face='Courier'>app.js</font> versi 17.362 baris asli setelah seluruh fungsinya berhasil diekstraksi ke modul-modul mandiri.", table_cell),
            Paragraph("Diletakkan di luar folder public/ agar browser tidak dapat mengakses script monolitik lama tersebut.", table_cell)
        ],
        [
            Paragraph("<b>_deprecated/scripts/</b>", table_cell_code),
            Paragraph("Script-script utilitas pengujian, migration scratch, dan file checker sementara.", table_cell),
            Paragraph("Mencegah penumpukan file pengujian di root folder project Laravel.", table_cell)
        ],
        [
            Paragraph("<b>_deprecated/views/</b>", table_cell_code),
            Paragraph("File Blade lama atau mockup komponen yang telah digantikan oleh struktur modul Blade baru.", table_cell),
            Paragraph("Diletakkan di luar folder resources/views/ agar mesin kompilasi Blade tidak memprosesnya.", table_cell)
        ],
        [
            Paragraph("<b>_deprecated/README.md</b>", table_cell_code),
            Paragraph("Buku catatan log: mendokumentasikan daftar file apa saja yang diarsipkan, tanggal pemindahan, dan alasan pengarsipan.", table_cell),
            Paragraph("Menjamin jejak audit transparan (*traceable changelog*).", table_cell)
        ]
    ]

    t_arch = Table(arch_data, colWidths=[120, 203, 200])
    t_arch.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), c_primary),
        ('BOX', (0,0), (-1,-1), 0.75, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, c_light]),
        ('PADDING', (0,0), (-1,-1), 4),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t_arch)
    story.append(Spacer(1, 10))

    # =========================================================================
    # BAB 6: PROTOKOL PENGUJIAN & LEMBAR PENGESAHAN
    # =========================================================================
    story.append(section_header("BAB 6 — PROTOKOL TESTING & LEMBAR PENGESAHAN DOKUMEN"))
    story.append(Spacer(1, 6))

    story.append(Paragraph(
        "Setiap rilis modul refactor wajib melewati checklist verifikasi standar sebelum dinyatakan selesai:",
        body_style
    ))
    story.append(Paragraph("1. <b>Sintaks Bebas Error:</b> File JavaScript diuji parsing kurung kurawal, koma, dan regular expression.", bullet_style))
    story.append(Paragraph("2. <b>Pembersihan Cache Blade:</b> Menjalankan perintah <font face='Courier'>php artisan view:clear</font> untuk memastikan rendering Blade baru.", bullet_style))
    story.append(Paragraph("3. <b>Verifikasi HTTP 200 OK:</b> Menguji respons endpoint rute web untuk menjamin ketiadaan error 500 internal server.", bullet_style))
    story.append(Paragraph("4. <b>Browser Hard Refresh (Ctrl + F5):</b> Menguji tampilan visual, DevTools Console (0 Error Merah), dan fungsionalitas tombol aksi.", bullet_style))

    story.append(Spacer(1, 12))

    # Lembar Pengesahan Sign-off
    sig_data = [
        [
            Paragraph("<b>Disusun & Direkayasa Oleh:</b>", table_cell_center),
            Paragraph("<b>Diverifikasi & Disetujui Oleh:</b>", table_cell_center)
        ],
        [
            Paragraph("<br/><br/><br/><b>Tim Arsitektur AI (Antigravity)</b><br/>Lead Technical Pair Programmer", table_cell_center),
            Paragraph("<br/><br/><br/><b>Lead Administrator Produksi & QA</b><br/>PT Nanggala Kencana Rekatama", table_cell_center)
        ],
        [
            Paragraph("Tanggal: <b>04 November 2026</b>", table_cell_center),
            Paragraph("Tanggal: <b>06 November 2026</b>", table_cell_center)
        ]
    ]

    t_sig = Table(sig_data, colWidths=[261, 262])
    t_sig.setStyle(TableStyle([
        ('BOX', (0,0), (-1,-1), 1, c_border),
        ('INNERGRID', (0,0), (-1,-1), 0.5, c_border),
        ('BACKGROUND', (0,0), (-1,0), c_light),
        ('PADDING', (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
    ]))
    story.append(t_sig)

    # Build document with NumberedCanvas
    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"[OK] Successfully built {filename} ({os.path.getsize(filename):,} bytes)")

if __name__ == '__main__':
    create_pdf()

