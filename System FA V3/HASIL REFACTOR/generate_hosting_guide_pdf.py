# generate_hosting_guide_pdf.py
"""
Script Generator PDF: Panduan Komprehensif Tahap Deployment & Hosting cPanel
Sistem Final Assembly & Traceability Nanggala P9
Standar Industri & Enterprise Software Engineering
"""

import os
import sys
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.pdfgen import canvas

# Numbered Canvas untuk "Halaman X dari Y" dengan Header & Footer Elegan
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
        
        # Header pada halaman 2 ke atas
        if self._pageNumber > 1:
            self.setFont("Helvetica-Bold", 7.5)
            self.setFillColor(colors.HexColor("#0F172A"))
            self.drawString(36, 812, "SISTEM FINAL ASSEMBLY & TRACEABILITY NANGGALA P9")
            self.setFont("Helvetica", 7.5)
            self.setFillColor(colors.HexColor("#0284C7"))
            self.drawRightString(559, 812, "Buku Panduan Deployment & Hosting cPanel (Standar Industri)")
            self.setStrokeColor(colors.HexColor("#E2E8F0"))
            self.setLineWidth(0.6)
            self.line(36, 806, 559, 806)

        # Footer pada semua halaman
        self.setStrokeColor(colors.HexColor("#E2E8F0"))
        self.setLineWidth(0.6)
        self.line(36, 38, 559, 38)

        self.setFont("Helvetica-Bold", 7.5)
        self.setFillColor(colors.HexColor("#059669"))
        self.drawString(36, 26, "STANDAR OPERASIONAL RILIS")
        self.setFont("Helvetica", 7.5)
        self.setFillColor(colors.HexColor("#64748B"))
        self.drawString(160, 26, "• Rekayasa Deployment, Isolasi Keamanan, dan Konfigurasi Server Produksi")
        page_text = f"Halaman {self._pageNumber} dari {page_count}"
        self.drawRightString(559, 26, page_text)

        self.restoreState()


def build_pdf(filename="PANDUAN_LENGKAP_DEPLOYMENT_DAN_HOSTING_CPANEL.pdf"):
    printable_width = 523 # 595 - 72

    doc = SimpleDocTemplate(
        filename,
        pagesize=A4,
        leftMargin=36,
        rightMargin=36,
        topMargin=44,
        bottomMargin=46
    )

    styles = getSampleStyleSheet()

    # Warna Tema Korporat & Developer
    c_primary = colors.HexColor("#0F172A")    # Slate 900
    c_secondary = colors.HexColor("#0284C7")  # Sky Blue 600
    c_accent = colors.HexColor("#059669")     # Emerald 600
    c_dark = colors.HexColor("#1E293B")       # Slate 800
    c_text = colors.HexColor("#334155")       # Slate 700
    c_muted = colors.HexColor("#64748B")      # Slate 500
    c_light = colors.HexColor("#F8FAFC")      # Slate 50
    c_border = colors.HexColor("#CBD5E1")     # Slate 300
    c_amber = colors.HexColor("#D97706")      # Amber 600
    c_rose = colors.HexColor("#DC2626")       # Red 600
    c_code_bg = colors.HexColor("#0F172A")    # Deep Dark Code Background

    # Typography Styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=17,
        leading=21,
        textColor=c_primary,
        spaceAfter=4
    )

    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9.5,
        leading=13.5,
        textColor=c_secondary,
        spaceAfter=8
    )

    h1_style = ParagraphStyle(
        'Header1',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        textColor=c_primary,
        spaceBefore=10,
        spaceAfter=5,
        keepWithNext=True
    )

    h2_style = ParagraphStyle(
        'Header2',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=10,
        leading=13,
        textColor=c_secondary,
        spaceBefore=7,
        spaceAfter=3,
        keepWithNext=True
    )

    body_style = ParagraphStyle(
        'DocBody',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8.3,
        leading=11.8,
        textColor=c_text,
        spaceAfter=4
    )

    bullet_style = ParagraphStyle(
        'DocBullet',
        parent=body_style,
        leftIndent=12,
        firstLineIndent=-8,
        spaceAfter=3
    )

    code_style = ParagraphStyle(
        'CodeSnippet',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=7.2,
        leading=10,
        textColor=colors.HexColor("#38BDF8"),
        backColor=c_code_bg,
        borderPadding=5,
        spaceBefore=3,
        spaceAfter=5
    )

    callout_text = ParagraphStyle(
        'CalloutText',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.8,
        leading=11,
        textColor=c_dark
    )

    table_header = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=7.8,
        leading=9.5,
        textColor=colors.white,
        alignment=1
    )

    table_cell = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.5,
        leading=10,
        textColor=c_dark
    )

    table_cell_bold = ParagraphStyle(
        'TableCellBold',
        parent=table_cell,
        fontName='Helvetica-Bold'
    )

    story = []

    # =========================================================================
    # HEADER BANNER
    # =========================================================================
    header_data = [
        [
            Paragraph("<b>PANDUAN OPERASIONAL & ARSITEKTUR INFRASTRUKTUR</b>", ParagraphStyle('TopBadge', fontName='Helvetica-Bold', fontSize=7.5, textColor=c_secondary, leading=9)),
            Paragraph("<b>STANDAR RILIS PRODUKSI 2026</b>", ParagraphStyle('TopDate', fontName='Helvetica-Bold', fontSize=7.5, textColor=c_muted, leading=9, alignment=2))
        ],
        [
            Paragraph("PANDUAN LENGKAP TAHAP DEPLOYMENT & HOSTING cPANEL", title_style),
            ""
        ],
        [
            Paragraph("Pemahaman Esensial Siklus Rilis Aplikasi, Komparasi Fundamental 'Deployment vs Sekadar Hosting', dan Petunjuk Teknis Implementasi Server Produksi cPanel untuk Sistem Final Assembly Nanggala P9", subtitle_style),
            ""
        ]
    ]

    header_table = Table(header_data, colWidths=[370, 153])
    header_table.setStyle(TableStyle([
        ('SPAN', (0, 1), (1, 1)),
        ('SPAN', (0, 2), (1, 2)),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
        ('TOPPADDING', (0, 0), (-1, -1), 1),
        ('LEFTPADDING', (0, 0), (-1, -1), 0),
        ('RIGHTPADDING', (0, 0), (-1, -1), 0),
    ]))
    story.append(header_table)
    story.append(HRFlowable(width="100%", thickness=1.5, color=c_secondary, spaceBefore=3, spaceAfter=6))

    # Meta Info Card
    meta_data = [
        [
            Paragraph("<b>Target Target Sistem:</b> Sistem Final Assembly & Traceability Nanggala P9", table_cell),
            Paragraph("<b>Tumpukan Teknologi:</b> Laravel 10 / PHP 8.2+ / MySQL / Vue.js 3", table_cell)
        ],
        [
            Paragraph("<b>Sasaran Pelaksana:</b> Tim IT, System Administrator, & DevOps Engineer", table_cell),
            Paragraph("<b>Lingkungan Rilis:</b> Shared Hosting / Cloud Hosting cPanel Enterprise", table_cell)
        ]
    ]
    meta_table = Table(meta_data, colWidths=[261, 262])
    meta_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#F1F5F9")),
        ('BOX', (0, 0), (-1, -1), 0.5, c_border),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(meta_table)
    story.append(Spacer(1, 8))

    # =========================================================================
    # BAB 1: DEFINISI & FUNGSI FUNDAMENTAL DEPLOYMENT WEBSITE
    # =========================================================================
    story.append(Paragraph("1. Definisi & Fungsi Fundamental Deployment Website", h1_style))
    story.append(Paragraph(
        "Dalam rekayasa perangkat lunak modern (<i>software engineering</i>), <b>Deployment</b> adalah proses terstruktur dan berulang "
        "untuk mentransformasikan kode aplikasi dari lingkungan lokal pengembang (<i>Local Development</i>) menjadi sistem aktif yang "
        "aman, teruji, teroptimasi, dan siap melayani pengguna di server produksi nyata (<i>Production Server</i>).",
        body_style
    ))
    story.append(Paragraph(
        "Banyak praktisi pemula menganggap rilis website hanyalah 'meng-upload seluruh folder proyek ke hosting via FTP/ZIP'. "
        "Anggapan ini keliru dan berisiko tinggi. Website berbasis framework modern (seperti Laravel) memiliki struktur arsitektur khusus "
        "yang memerlukan serangkaian tahapan sebelum dapat dipublikasikan secara aman.",
        body_style
    ))

    # Box Fungsi Utama Deployment
    func_box_data = [
        [
            Paragraph("<b>4 FUNGSI UTAMA DARI TAHAP DEPLOYMENT SISTEM:</b>", ParagraphStyle('FBoxTitle', fontName='Helvetica-Bold', fontSize=8, textColor=c_primary)),
        ],
        [
            Paragraph("<b>1. Isolasi Lingkungan & Kredensial Rahasia (Secret Segregation):</b> Memisahkan konfigurasi pengujian lokal dengan lingkungan server nyata. Kredensial database, kunci enkripsi aplikasi (<code>APP_KEY</code>), dan pengaturan sensitif disimpan dalam berkas <code>.env</code> yang terisolasi ketat di luar akses publik internet.", callout_text)
        ],
        [
            Paragraph("<b>2. Optimasi Aset & Pengoptimalan Memori (Production Optimization):</b> Meminifikasi berkas skrip, mengompilasi peta kelas PHP (<code>composer --optimize-autoloader</code>), dan mencache rute serta template Blade agar respons server meningkat hingga <b>300% lebih cepat</b>.", callout_text)
        ],
        [
            Paragraph("<b>3. Sinkronisasi Skema & Relasi Database (Schema Consistency):</b> Menjamin struktur tabel, indeks, tipe data, dan kunci asing (<i>Foreign Keys</i>) terbentuk sempurna melalui skrip migrasi terstruktur, bukan sekadar ekspor-impor manual yang rentan kesalahan.", callout_text)
        ],
        [
            Paragraph("<b>4. Mitigasi Risiko & Kemampuan Rollback Cepat (Zero-Downtime Resilience):</b> Mengintegrasikan version control (Git) sehingga jika ditemukan anomali pasca-rilis, sistem dapat dikembalikan ke versi stabil sebelumnya dalam hitungan detik tanpa memutus operasional pengguna.", callout_text)
        ]
    ]
    func_box = Table(func_box_data, colWidths=[523])
    func_box.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#EFF6FF")),
        ('BOX', (0, 0), (-1, -1), 1, c_secondary),
        ('LINEBELOW', (0, 0), (-1, 0), 0.5, c_secondary),
        ('TOPPADDING', (0, 0), (-1, -1), 3.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(func_box)
    story.append(Spacer(1, 8))

    # =========================================================================
    # BAB 2: KOMPARASI: TAHAP DEPLOYMENT VS SEKADAR HOSTING SAJA
    # =========================================================================
    story.append(Paragraph("2. Komparasi: Tahap Deployment Terstruktur vs Sekadar Menghosting Saja", h1_style))
    story.append(Paragraph(
        "Tabel berikut merangkum perbedaan krusial antara pendekatan rilis terstruktur dengan sekadar menaruh file secara mentah:",
        body_style
    ))

    comp_data = [
        [
            Paragraph("Parameter Evaluasi", table_header),
            Paragraph("Hanya Menghosting Saja<br/>(Pendekatan Mentah / Asal Upload)", table_header),
            Paragraph("Tahap Deployment Terstruktur<br/>(Standar Industri & Enterprise)", table_header)
        ],
        [
            Paragraph("<b>Keamanan Berkas Inti & .env</b>", table_cell_bold),
            Paragraph("<font color='#DC2626'><b>SANGAT RENTAN.</b> Berkas <code>.env</code> berisiko diunduh langsung lewat browser jika document root keliru mengarah ke root folder.</font>", table_cell),
            Paragraph("<font color='#059669'><b>TERISOLASI TOTAL.</b> Kode inti diletakkan di luar <code>public_html</code>. Hanya berkas aset statis publik yang dapat diakses dari web.</font>", table_cell)
        ],
        [
            Paragraph("<b>Penanganan Error (Debug Mode)</b>", table_cell_bold),
            Paragraph("<font color='#DC2626'><code>APP_DEBUG=true</code> sering tertinggal aktif. Saat error terjadi, informasi password database dan kredensial server terbuka gamblang ke publik.</font>", table_cell),
            Paragraph("<font color='#059669'><code>APP_DEBUG=false</code> wajib diterapkan. Error ditangani dengan halaman kustom bersahabat dan log disimpan aman di <code>storage/logs/</code>.</font>", table_cell)
        ],
        [
            Paragraph("<b>Performa Eksekusi Aplikasi</b>", table_cell_bold),
            Paragraph("Lambat. Setiap request memindai ratusan file route, config, dan blade view dari awal tanpa optimasi cache.", table_cell),
            Paragraph("<font color='#059669'>Sangat Cepat. Menggunakan <code>config:cache</code>, <code>route:cache</code>, dan <code>view:cache</code> untuk kompilasi memori super responsif.</font>", table_cell)
        ],
        [
            Paragraph("<b>Ketergantungan Paket (Dependencies)</b>", table_cell_bold),
            Paragraph("Folder <code>vendor</code> raksasa di-upload manual lewat ZIP/FTP. Memakan kuota hosting dan rawan berkas terpotong saat upload.", table_cell),
            Paragraph("<font color='#059669'>Paket diinstal bersih via Composer dengan opsi <code>--no-dev --optimize-autoloader</code> tanpa menyertakan alat pengujian yang tidak perlu.</font>", table_cell)
        ],
        [
            Paragraph("<b>Integritas Basis Data</b>", table_cell_bold),
            Paragraph("Ekspor-impor manual via phpMyAdmin rentan korupsi karakter encoding, foreign key tertinggal, atau kegagalan relasi.", table_cell),
            Paragraph("<font color='#059669'>Skema tervalidasi otomatis lewat file Migration terstruktur, menjamin relasi antar tabel presisi 100%.</font>", table_cell)
        ],
        [
            Paragraph("<b>Pembaruan Sistem (Maintenance)</b>", table_cell_bold),
            Paragraph("Sulit & Menakutkan. Harus memilih file mana yang berubah lalu menimpa file di server secara manual. Rawan salah timpa.", table_cell),
            Paragraph("<font color='#059669'>Mudah & Nyaman. Cukup melakukan <code>git pull</code> via cPanel Git Version Control atau pipeline CI/CD otomatis.</font>", table_cell)
        ],
        [
            Paragraph("<b>Rencana Pemulihan (Rollback Plan)</b>", table_cell_bold),
            Paragraph("<font color='#DC2626'>Tidak Ada. Jika aplikasi crash setelah update, sistem mati total (downtime panjang) sampai file lama dicari.</font>", table_cell),
            Paragraph("<font color='#059669'>Instan. Cukup mengarahkan kembali commit Git atau backup rilis sebelumnya dalam hitungan detik.</font>", table_cell)
        ]
    ]

    comp_table = Table(comp_data, colWidths=[95, 214, 214])
    comp_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), c_primary),
        ('ALIGN', (0, 0), (-1, 0), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('GRID', (0, 0), (-1, -1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor("#F8FAFC")]),
        ('TOPPADDING', (0, 0), (-1, -1), 3.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(comp_table)
    story.append(Spacer(1, 10))

    # =========================================================================
    # BAB 3: PANDUAN LANGKAH DEMI LANGKAH HOSTING DI CPANEL
    # =========================================================================
    story.append(PageBreak()) # Halaman Baru Khusus Panduan Teknis cPanel
    story.append(Paragraph("3. Panduan Praktis Langkah demi Langkah Hosting Laravel di cPanel", h1_style))
    story.append(Paragraph(
        "Berikut adalah 8 langkah operasional standar industri untuk memasang aplikasi <b>Sistem Final Assembly Nanggala P9</b> "
        "ke server hosting cPanel:",
        body_style
    ))

    # STEP 1
    story.append(Paragraph("Langkah 1: Konfigurasi Versi PHP & Ekstensi Wajib di cPanel", h2_style))
    story.append(Paragraph(
        "Sistem FA V3 dibangun di atas framework Laravel 10 dan memerlukan runtime PHP 8.2 atau 8.3:",
        body_style
    ))
    story.append(Paragraph("• Buka menu <b>Select PHP Version</b> (atau <i>MultiPHP Manager</i>) pada cPanel.", bullet_style))
    story.append(Paragraph("• Pilih versi PHP <b>8.2</b> (atau <b>8.3</b> direkomendasikan).", bullet_style))
    story.append(Paragraph("• Masuk ke tab <b>Extensions</b> dan pastikan aktif: <code>pdo_mysql</code>, <code>mbstring</code>, <code>openssl</code>, <code>tokenizer</code>, <code>xml</code>, <code>ctype</code>, <code>json</code>, <code>bcmath</code>, <code>fileinfo</code>, <code>zip</code>, dan <code>intl</code>.", bullet_style))
    story.append(Paragraph("• Masuk ke tab <b>Options</b> dan sesuaikan batas: <code>memory_limit = 512M</code>, <code>upload_max_filesize = 64M</code>, <code>post_max_size = 64M</code>, dan <code>max_execution_time = 120</code>.", bullet_style))
    story.append(Spacer(1, 4))

    # STEP 2
    story.append(Paragraph("Langkah 2: Pembuatan Database & Akun Pengguna MySQL di cPanel", h2_style))
    story.append(Paragraph("1. Masuk ke menu <b>MySQL Databases</b> di cPanel.", bullet_style))
    story.append(Paragraph("2. <b>Buat Database Baru:</b> Beri nama database (misal: <code>usercp_final_assembly</code>) lalu klik <i>Create Database</i>.", bullet_style))
    story.append(Paragraph("3. <b>Buat User MySQL:</b> Pada form <i>Add New User</i>, masukkan nama user (misal: <code>usercp_fa_user</code>) dan generate password acak yang kuat.", bullet_style))
    story.append(Paragraph("4. <b>Hubungkan User ke Database:</b> Pada form <i>Add User To Database</i>, pilih user dan database tersebut, centang <b>ALL PRIVILEGES</b>, lalu klik <i>Make Changes</i>.", bullet_style))
    story.append(Paragraph("5. <b>Impor Data:</b> Buka <b>phpMyAdmin</b>, pilih database yang baru dibuat, klik tab <b>Import</b>, dan unggah berkas database SQL terbaru.", bullet_style))
    story.append(Spacer(1, 4))

    # STEP 3 - ARSITEKTUR STRUKTUR FOLDER
    story.append(Paragraph("Langkah 3: Menyiapkan Struktur Direktori Aman (Mencegah Kebocoran .env)", h2_style))
    story.append(Paragraph(
        "<b>PERHATIAN KRUSIAL:</b> Jangan pernah menaruh seluruh folder Laravel langsung di dalam <code>public_html</code>. "
        "Gunakan salah satu dari dua metode pemisahan direktori aman berikut:",
        body_style
    ))

    # Box Perbandingan Metode Folder
    folder_box_data = [
        [
            Paragraph("<b>METODE A: Mengubah Document Root Domain (Sangat Direkomendasikan)</b>", ParagraphStyle('MetTitle', fontName='Helvetica-Bold', fontSize=8, textColor=c_primary)),
        ],
        [
            Paragraph("• Masuk ke menu <b>Domains</b> di cPanel, klik kelola domain Anda.<br/>"
                      "• Ubah nilai <b>Document Root</b> dari semula <code>/public_html</code> menjadi <code>/laravel_system/public</code>.<br/>"
                      "• Upload seluruh proyek sistem ke dalam direktori <code>/home/username/laravel_system/</code>.<br/>"
                      "• <b>Keuntungan:</b> Berkas inti berada di luar jangkauan internet, dan web server secara otomatis melayani pengunjung langsung dari folder <code>public/</code>.", callout_text)
        ],
        [
            Paragraph("<b>METODE B: Pemisahan Folder Publik & Inti (Jika Document Root Terkunci)</b>", ParagraphStyle('MetTitle2', fontName='Helvetica-Bold', fontSize=8, textColor=c_primary)),
        ],
        [
            Paragraph("• Buat folder baru di luar <code>public_html</code>, misal: <code>/home/username/core_fa/</code> dan letakkan seluruh berkas proyek di sana (kecuali isi folder public).<br/>"
                      "• Pindahkan seluruh isi folder <code>public/</code> (termasuk <code>index.php</code>, <code>favicon.png</code>, folder <code>js/</code>, <code>css/</code>) langsung ke dalam <code>public_html/</code>.<br/>"
                      "• Buka file <code>public_html/index.php</code>, sesuaikan path autoloader menjadi:<br/>"
                      "&nbsp;&nbsp;<code>require __DIR__.'/../core_fa/vendor/autoload.php';</code><br/>"
                      "&nbsp;&nbsp;<code>$app = require_once __DIR__.'/../core_fa/bootstrap/app.php';</code>", callout_text)
        ]
    ]
    folder_box = Table(folder_box_data, colWidths=[523])
    folder_box.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor("#FEF3C7")),
        ('BACKGROUND', (0, 2), (-1, 2), colors.HexColor("#E0F2FE")),
        ('BOX', (0, 0), (-1, -1), 0.5, c_border),
        ('LINEBELOW', (0, 0), (-1, 0), 0.5, c_amber),
        ('LINEBELOW', (0, 2), (-1, 2), 0.5, c_secondary),
        ('TOPPADDING', (0, 0), (-1, -1), 3.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(folder_box)
    story.append(Spacer(1, 6))

    # STEP 4 - ENVIRONMENT CONFIG
    story.append(Paragraph("Langkah 4: Konfigurasi Berkas Lingkungan Produksi (.env)", h2_style))
    story.append(Paragraph(
        "Buka File Manager di cPanel, buat berkas <code>.env</code> pada direktori inti aplikasi Laravel Anda. "
        "Pastikan nilai variabel disesuaikan ketat untuk mode produksi:",
        body_style
    ))

    env_snippet = (
        "APP_NAME=\"Sistem Final Assembly P9\"\n"
        "APP_ENV=production\n"
        "APP_KEY=base64:GUNAKAN_KEY_DARI_ARTISAN_KEY_GENERATE\n"
        "APP_DEBUG=false               # WAJIB FALSE! Mencegah kebocoran kredensial ke publik\n"
        "APP_URL=https://sistem-fa.domainanda.com\n\n"
        "DB_CONNECTION=mysql\n"
        "DB_HOST=127.0.0.1             # Atau localhost sesuai ketentuan hosting\n"
        "DB_PORT=3306\n"
        "DB_DATABASE=usercp_final_assembly\n"
        "DB_USERNAME=usercp_fa_user\n"
        "DB_PASSWORD=PasswordKuatMySQLYangTelahDibuat\n\n"
        "SESSION_DRIVER=file\n"
        "SESSION_LIFETIME=120\n"
        "CACHE_STORE=file"
    )
    story.append(Paragraph(env_snippet.replace('\n', '<br/>').replace(' ', '&nbsp;'), code_style))
    story.append(Spacer(1, 4))

    # STEP 5 - PERMISSION & CACHE
    story.append(Paragraph("Langkah 5: Hak Akses Folder & Perintah Optimasi Produksi", h2_style))
    story.append(Paragraph(
        "Agar Laravel dapat menulis sesi login, cache view, dan log error, atur izin akses direktori:",
        body_style
    ))
    story.append(Paragraph("• Atur izin folder <code>storage/</code> dan <code>bootstrap/cache/</code> beserta seluruh sub-foldernya menjadi <b>775</b> (atau 755).", bullet_style))
    story.append(Paragraph("• Buka menu <b>Terminal</b> pada cPanel (atau SSH) dan jalankan rangkaian perintah optimasi berikut:", bullet_style))

    artisan_snippet = (
        "# 1. Masuk ke folder proyek Laravel\n"
        "cd /home/username/laravel_system\n\n"
        "# 2. Instal paket vendor bersih tanpa modul developer\n"
        "composer install --no-dev --optimize-autoloader\n\n"
        "# 3. Bersihkan cache pengembangan lokal\n"
        "php artisan config:clear && php artisan route:clear && php artisan view:clear\n\n"
        "# 4. Kompilasi & kunci cache produksi (Performa Maksimal)\n"
        "php artisan config:cache\n"
        "php artisan route:cache\n"
        "php artisan view:cache\n\n"
        "# 5. Tautkan folder penyimpanan publik\n"
        "php artisan storage:link"
    )
    story.append(Paragraph(artisan_snippet.replace('\n', '<br/>').replace(' ', '&nbsp;'), code_style))
    story.append(Spacer(1, 4))

    # STEP 6 - SSL & HTTPS
    story.append(Paragraph("Langkah 6: Pemasangan Sertifikat Keamanan SSL & Pengalihan HTTPS", h2_style))
    story.append(Paragraph("1. Masuk ke menu <b>SSL/TLS Status</b> di cPanel.", bullet_style))
    story.append(Paragraph("2. Pilih domain sistem Anda lalu klik tombol <b>Run AutoSSL</b> (Sertifikat Let's Encrypt / cPanel AutoSSL terpasang gratis).", bullet_style))
    story.append(Paragraph("3. Pastikan pengalihan ke HTTPS aktif dengan menyertakan aturan berikut pada <code>public/.htaccess</code>:", bullet_style))

    htaccess_snippet = (
        "<IfModule mod_rewrite.c>\n"
        "    RewriteEngine On\n"
        "    RewriteCond %{HTTPS} !=on\n"
        "    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]\n"
        "</IfModule>"
    )
    story.append(Paragraph(htaccess_snippet.replace('\n', '<br/>').replace(' ', '&nbsp;'), code_style))
    story.append(Spacer(1, 4))

    # STEP 7 - GIT DEPLOYMENT
    story.append(Paragraph("Langkah 7: Deployment Otomatis via cPanel Git Version Control", h2_style))
    story.append(Paragraph(
        "Untuk memudahkan pembaruan sistem tanpa upload berulang kali:",
        body_style
    ))
    story.append(Paragraph("1. Masuk ke menu <b>Git Version Control</b> di cPanel, klik tombol <b>Create</b>.", bullet_style))
    story.append(Paragraph("2. Masukkan URL clone repositori: <code>https://github.com/Tiannn-r/NKRI-System.git</code>.", bullet_style))
    story.append(Paragraph("3. Pilih direktori target (<code>/home/username/laravel_system</code>) dan branch <code>main</code>.", bullet_style))
    story.append(Paragraph("4. Siapkan berkas <code>.cpanel.yml</code> di root repositori untuk otomatisasi deployment:", bullet_style))

    cpanel_yml_snippet = (
        "---\n"
        "deployment:\n"
        "  tasks:\n"
        "    - export DEPLOYPATH=/home/username/laravel_system/\n"
        "    - /bin/cp -R * $DEPLOYPATH"
    )
    story.append(Paragraph(cpanel_yml_snippet.replace('\n', '<br/>').replace(' ', '&nbsp;'), code_style))
    story.append(Spacer(1, 4))

    # STEP 8 - CHECKLIST VERIFIKASI
    story.append(Paragraph("Langkah 8: Checklist Verifikasi Sistem Pasca-Hosting (Go-Live)", h2_style))
    story.append(Paragraph("Sebelum sistem dinyatakan resmi beroperasi untuk lini produksi dan gudang, jalankan verifikasi berikut:", body_style))

    check_data = [
        [
            Paragraph("Komponen Pengujian", table_header),
            Paragraph("Kondisi Diharapkan (Expected Result)", table_header),
            Paragraph("Status", table_header)
        ],
        [
            Paragraph("<b>Akses URL Domain Utama</b>", table_cell_bold),
            Paragraph("Halaman diarahkan otomatis ke protokol HTTPS (gembok hijau aktif) dan form login tampil sempurna.", table_cell),
            Paragraph("<font color='#059669'><b>[ WAJIB ]</b></font>", table_cell_bold)
        ],
        [
            Paragraph("<b>Uji Keamanan Berkas .env</b>", table_cell_bold),
            Paragraph("Akses ke <code>https://domainanda.com/.env</code> menghasilkan respon <b>403 Forbidden</b> atau <b>404 Not Found</b>.", table_cell),
            Paragraph("<font color='#059669'><b>[ WAJIB ]</b></font>", table_cell_bold)
        ],
        [
            Paragraph("<b>Otentikasi Operator & Hak Akses</b>", table_cell_bold),
            Paragraph("Login menggunakan akun operator (Admin, Perakitan, QC, Gudang) berjalan lancar dan session tersimpan.", table_cell),
            Paragraph("<font color='#059669'><b>[ WAJIB ]</b></font>", table_cell_bold)
        ],
        [
            Paragraph("<b>Pencetakan Dokumen & Label SN</b>", table_cell_bold),
            Paragraph("Fitur cetak label barcode part dan traveler dokumen memunculkan jendela dialog cetak browser dengan rapi.", table_cell),
            Paragraph("<font color='#059669'><b>[ WAJIB ]</b></font>", table_cell_bold)
        ],
        [
            Paragraph("<b>Fitur Standalone Ekspor Excel</b>", table_cell_bold),
            Paragraph("Tombol Cetak Excel di TF 1 (pilihan modal), QC (dokumen ASSY/QC), dan Gudang (dokumen WH/GR) mengunduh file .xlsx valid.", table_cell),
            Paragraph("<font color='#059669'><b>[ WAJIB ]</b></font>", table_cell_bold)
        ]
    ]

    check_table = Table(check_data, colWidths=[130, 313, 80])
    check_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), c_dark),
        ('ALIGN', (0, 0), (-1, 0), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('GRID', (0, 0), (-1, -1), 0.5, c_border),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [colors.white, colors.HexColor("#F8FAFC")]),
        ('ALIGN', (2, 1), (2, -1), 'CENTER'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
        ('LEFTPADDING', (0, 0), (-1, -1), 5),
        ('RIGHTPADDING', (0, 0), (-1, -1), 5),
    ]))
    story.append(check_table)
    story.append(Spacer(1, 8))

    # =========================================================================
    # KESIMPULAN / SUMMARY BOX
    # =========================================================================
    summary_box_data = [
        [
            Paragraph("<b>RINGKASAN EKSEKUTIF UNTUK TIM IT & OPERASIONAL:</b>", ParagraphStyle('SumTitle', fontName='Helvetica-Bold', fontSize=8, textColor=c_primary))
        ],
        [
            Paragraph(
                "Melakukan deployment dengan metodologi yang benar menjamin bahwa sistem Anda beroperasi dalam kondisi paling prima, "
                "terlindungi dari kebocoran data rahasia, memiliki kecepatan respons tinggi, dan siap diperbarui kapan saja melalui Git "
                "tanpa risiko merusak database produksi. Panduan ini dapat menjadi SOP resmi tim IT dalam merilis sistem ke server live.",
                callout_text
            )
        ]
    ]
    summary_box = Table(summary_box_data, colWidths=[523])
    summary_box.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#F0FDF4")),
        ('BOX', (0, 0), (-1, -1), 1, c_accent),
        ('LINEBELOW', (0, 0), (-1, 0), 0.5, c_accent),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ]))
    story.append(summary_box)

    # Bangun Dokumen
    doc.build(story, canvasmaker=NumberedCanvas)
    print(f"[SUCCESS] PDF berhasil dibuat: {filename}")

if __name__ == '__main__':
    build_pdf()
