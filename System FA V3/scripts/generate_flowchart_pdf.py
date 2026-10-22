"""
Script untuk generate PDF Flowchart Sistem Final Assembly & Traceability
Menggunakan reportlab + mermaid.ink API untuk render diagram
"""
import os
import sys
import json
import base64
import zlib
import urllib.request
import urllib.error
import io
import time
import textwrap

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm, cm
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle,
    PageBreak, KeepTogether, HRFlowable
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.graphics.shapes import Drawing, Rect, String

# ======================== COLORS ========================
PRIMARY = HexColor('#1a237e')
PRIMARY_LIGHT = HexColor('#3949ab')
ACCENT = HexColor('#ff6f00')
TEXT_COLOR = HexColor('#1a1a2e')
TEXT_LIGHT = HexColor('#546e7a')
BG_LIGHT = HexColor('#f5f7fa')
BG_GREEN = HexColor('#e8f5e9')
BG_BLUE = HexColor('#e3f2fd')
BG_ORANGE = HexColor('#fff3e0')
BG_PURPLE = HexColor('#f3e5f5')
BG_PINK = HexColor('#fce4ec')
CHECK_GREEN = HexColor('#2e7d32')
CROSS_RED = HexColor('#c62828')
BORDER = HexColor('#e0e0e0')

# ======================== MERMAID DIAGRAMS ========================
DIAGRAMS = [
    {
        "id": "s1",
        "title": "Arsitektur Sistem (Overview)",
        "icon": "🏗️",
        "desc": "Gambaran umum arsitektur tiga lapis: Frontend (Browser), Backend (Python HTTP Server), dan Database (SQLite WAL).",
        "mermaid": """graph TB
    subgraph CLIENT["Frontend - Browser"]
        LOGIN["Halaman Login"]
        DASHBOARD["Dashboard Utama (Vue 3 + Tailwind CSS)"]
    end
    subgraph SERVER["Backend - Python HTTP Server"]
        API["REST API Handler (Port 8000)"]
        AUTH["Autentikasi & Session Manager"]
        RBAC["Role-Based Access Control"]
    end
    subgraph DB["Database - SQLite WAL Mode"]
        USERS[("users")]
        MASTER[("master_stocks")]
        TRAYS[("trays")]
        PARTS[("parts")]
        ASSY[("assemblies")]
        AUDIT[("part_audit_logs")]
        REREG[("re_register_requests")]
        DOWNTIME[("assembly_downtimes")]
        WO_HIST[("assembly_wo_history")]
    end
    CLIENT -->|HTTP Request| SERVER
    SERVER -->|Query / Insert| DB
    DB -->|Result Set| SERVER
    SERVER -->|JSON Response| CLIENT"""
    },
    {
        "id": "s2",
        "title": "Alur Autentikasi & Hak Akses",
        "icon": "🔐",
        "desc": "Proses login, validasi kredensial, pembuatan session token, dan routing berdasarkan role pengguna (RBAC).",
        "mermaid": """flowchart TD
    A(["User Membuka Aplikasi"]) --> B{"Sudah Login?"}
    B -->|Tidak| C["Tampilkan Halaman Login"]
    B -->|Ya - Token Valid| D["Redirect ke Dashboard"]
    C --> E["Input Username & Password"]
    E --> F{"/api/login: Validasi Kredensial"}
    F -->|Gagal| G["Tampilkan Pesan Error"]
    G --> E
    F -->|Berhasil| H["Generate Session Token"]
    H --> I["Simpan Token ke user_sessions & browser"]
    I --> J{"Identifikasi Role User"}
    J -->|Admin / Miranti| K["ADMIN: Akses Penuh Seluruh Tab"]
    J -->|Gudang| L["GUDANG: Supply, Cetak WO, Stok"]
    J -->|Assembly| M["ASSEMBLY: Meja Kerja, Rakit, QC"]
    J -->|Custom| N["CUSTOM: Sesuai permissions JSON"]
    K --> D
    L --> D
    M --> D
    N --> D
    style K fill:#ff6b6b,color:#fff
    style L fill:#ffd93d,color:#333
    style M fill:#6bcb77,color:#fff
    style N fill:#4d96ff,color:#fff"""
    },
    {
        "id": "s3",
        "title": "Alur Utama End-to-End (Proses Inti)",
        "icon": "🔄",
        "desc": "Alur lengkap 5 fase utama: Gudang - Perakitan - Quality Control - Verifikasi - Dokumen & Laporan.",
        "mermaid": """flowchart TD
    START(["MULAI"]) --> WH1
    subgraph WH["FASE 1 - GUDANG"]
        WH1["PIC Gudang Login"] --> WH2["Pilih Part dari Master Stocks"]
        WH2 --> WH3["Alokasi 22 Set ke Tray Baru"]
        WH3 --> WH4["Generate Kode Tray: WH/WO/xxxxxx"]
        WH4 --> WH5["Update Status Part: Supplied"]
        WH5 --> WH6["Cetak Dokumen Supply WH/WO"]
        WH6 --> WH7["Kirim Fisik Tray ke Assembly"]
    end
    WH7 --> ASSY1
    subgraph ASSY["FASE 2 - PERAKITAN"]
        ASSY1["Operator Login"] --> ASSY2["Pilih Tray & Set Nomor"]
        ASSY2 --> ASSY3["Klik Mulai - Timer Aktif"]
        ASSY3 --> ASSY4["Rakit 3 Komponen: P15 + P1 + P12"]
        ASSY4 --> ASSY5["Klik Selesai - Timer Stop"]
        ASSY5 --> ASSY6{"Tentukan Status QC"}
    end
    ASSY6 --> QC1
    subgraph QC["FASE 3 - QUALITY CONTROL"]
        QC1{"Kondisi Hasil Rakit?"}
        QC1 -->|Baik| QC_G["GOOD"]
        QC1 -->|Tertahan| QC_H["HOLD + Alasan"]
        QC1 -->|Rusak| QC_S["SCRAP + Alasan"]
        QC1 -->|Ulang| QC_R["REWORK + Alasan"]
    end
    QC_G --> V1
    QC_H --> V1
    QC_S --> V1
    QC_R --> V1
    subgraph VER["FASE 4 - VERIFIKASI"]
        V1["Admin/Gudang Review"] --> V2{"Setuju?"}
        V2 -->|ACC| V3["Verified - Part Done"]
        V2 -->|DECLINED| V4["Ditolak + Alasan"]
    end
    V3 --> DOC1
    subgraph DOC["FASE 5 - DOKUMEN"]
        DOC1["Cetak Form Perakitan"] --> DOC2["Cetak Uji Fungsional"]
        DOC2 --> DOC3["Cetak Dokumen Mutu"]
        DOC3 --> DOC4["Laporan Traceability"]
        DOC4 --> DOC5["Ekspor ke Excel"]
    end
    DOC5 --> FINISH(["SELESAI"])
    style START fill:#4d96ff,color:#fff
    style FINISH fill:#ff6b6b,color:#fff
    style WH fill:#fff3e0,stroke:#ff9800
    style ASSY fill:#e3f2fd,stroke:#2196f3
    style QC fill:#e8f5e9,stroke:#4caf50
    style VER fill:#f3e5f5,stroke:#9c27b0
    style DOC fill:#fce4ec,stroke:#e91e63"""
    },
    {
        "id": "s4",
        "title": "Detail Alur Gudang (Warehouse Flow)",
        "icon": "📦",
        "desc": "Proses lengkap dari cek stok master, validasi SN, pembuatan tray, hingga pengiriman ke area assembly.",
        "mermaid": """flowchart TD
    G_START(["Gudang Login"]) --> G1
    G1["Buka Tab Gudang"] --> G2["Cek Stok Master: Part 1 / Part 12 / Part 15"]
    G2 --> G3{"Stok Mencukupi?"}
    G3 -->|Tidak| G3a["Notifikasi Stok Habis"]
    G3 -->|Ya| G4["Input PIC Gudang & Operator Assembly"]
    G4 --> G5["Pilih 22 Set Part: masing-masing 1 SN"]
    G5 --> G6["Sistem Validasi SN Tidak Duplikat"]
    G6 --> G7{"Validasi Berhasil?"}
    G7 -->|Tidak| G7a["Error: SN Sudah Digunakan"]
    G7a --> G5
    G7 -->|Ya| G8["Buat Tray Baru di tabel trays"]
    G8 --> G9["Insert 66 Part ke tabel parts"]
    G9 --> G10["Update 66 Record master_stocks: Supplied"]
    G10 --> G11["Generate Nomor Dokumen WH/WO/xxxxxx"]
    G11 --> G12["Cetak Form Supply"]
    G12 --> G13["Kirim Fisik ke Area Assembly"]
    G13 --> G_END(["Tray Terkirim"])
    style G_START fill:#ffd93d,color:#333
    style G_END fill:#6bcb77,color:#fff"""
    },
    {
        "id": "s5",
        "title": "Detail Alur Perakitan (Assembly Flow)",
        "icon": "🔧",
        "desc": "Alur kerja operator perakitan: pilih tray, mulai timer, rakit komponen, pause/resume, submit hasil QC.",
        "mermaid": """flowchart TD
    A_START(["Operator Login"]) --> A1
    A1["Buka Tab Perakitan"] --> A2["Lihat Daftar Tray Tersedia"]
    A2 --> A3["Pilih Tray & Set Nomor"]
    A3 --> A4["Klik Mulai Rakit"]
    A4 --> A5["Timer Mulai: started_at"]
    A5 --> A6{"Perlu Jeda?"}
    A6 -->|Ya| A7["PAUSE + Input Alasan"]
    A7 --> A8["Catat ke assembly_downtimes"]
    A8 --> A9["RESUME: Lanjut Kerja"]
    A9 --> A6
    A6 -->|Tidak| A10["Rakit: P15 + P1 + P12"]
    A10 --> A11["Klik Selesai"]
    A11 --> A12["Timer Stop: finished_at"]
    A12 --> A13{"Pilih Status Hasil Rakit"}
    A13 -->|Good| A14["GOOD"]
    A13 -->|Hold| A15["HOLD + Alasan"]
    A13 -->|Scrap| A16["SCRAP + Alasan"]
    A13 -->|Rework| A17["REWORK + Alasan"]
    A14 --> A18["Insert ke assemblies"]
    A15 --> A18
    A16 --> A18
    A17 --> A18
    A18 --> A19["Insert ke assembly_wo_history"]
    A19 --> A20{"22 Set Selesai?"}
    A20 -->|Belum| A3
    A20 -->|Ya| A21["Batch Selesai"]
    A21 --> A_END(["Assembly Complete"])
    style A_START fill:#6bcb77,color:#fff
    style A_END fill:#4d96ff,color:#fff"""
    },
    {
        "id": "s6",
        "title": "Detail Alur Verifikasi & Approval",
        "icon": "🔍",
        "desc": "Lima jenis verifikasi: Set Assembly, Replacement Part, Re-Register Part, Hold Item, dan Verifikasi Tray Gudang.",
        "mermaid": """flowchart TD
    V_START(["Mulai Verifikasi"]) --> V1
    V1["Admin/Gudang Buka Tab Verifikasi"] --> V2["Lihat Daftar Pending"]
    V2 --> V3{"Jenis Verifikasi?"}
    V3 -->|Set Assembly| V4["Review Hasil Rakit Per Set"]
    V4 --> V5{"Keputusan?"}
    V5 -->|ACC| V6["verified_status = ACC"]
    V5 -->|DECLINED| V7["DECLINED + Alasan"]
    V7 --> V7a["Assembly Re-register Set"]
    V3 -->|Replacement| V8["Review Penggantian Part"]
    V8 --> V9{"Approve?"}
    V9 -->|Ya| V10["Part Lama: Tersedia, Baru: Supplied"]
    V9 -->|Tidak| V11["Reject + Alasan"]
    V3 -->|Re-Register| V12["Review Permohonan Re-register"]
    V12 --> V13{"Approve?"}
    V13 -->|Ya| V14["Status: Tersedia Kembali"]
    V14 --> V15["Catat di part_audit_logs"]
    V13 -->|Tidak| V16["Reject + Admin Notes"]
    V3 -->|Hold Item| V17["Review Item Hold/Rework"]
    V17 --> V18{"Keputusan?"}
    V18 -->|Release| V19["Status: Good"]
    V18 -->|Tetap| V20["Status Tetap"]
    V3 -->|Verifikasi Tray| V21["Gudang Verifikasi Fisik"]
    V21 --> V22["verified_by_gudang = TRUE"]
    V6 --> V_END(["Verified"])
    V10 --> V_END
    V14 --> V_END
    V19 --> V_END
    V22 --> V_END
    style V_START fill:#9c27b0,color:#fff
    style V_END fill:#6bcb77,color:#fff"""
    },
    {
        "id": "s7",
        "title": "State Machine Status Part",
        "icon": "📋",
        "desc": "Diagram transisi status part di master_stocks: dari Tersedia hingga Done, termasuk jalur Hold, Scrap, dan Rework.",
        "mermaid": """stateDiagram-v2
    [*] --> Tersedia: Part dibuat / diimport
    Tersedia --> Supplied: Dialokasi ke Tray
    Supplied --> Used: Dirakit oleh Operator
    Used --> Good: QC OK
    Used --> Hold: QC Tertahan
    Used --> Scrap: QC Rusak
    Used --> Rework: Perlu Ulang
    Good --> Done: Admin ACC
    Hold --> Good: Admin Approve
    Hold --> Rework: Admin Ubah
    Hold --> Tersedia: Re-register
    Scrap --> Tersedia: Re-register
    Rework --> Good: Ulang OK
    Rework --> Tersedia: Re-register
    Done --> [*]"""
    },
    {
        "id": "s8",
        "title": "Alur Cetak Dokumen",
        "icon": "🖨️",
        "desc": "Empat dokumen resmi: Supply Material (WH/WO), Traveler Perakitan (WH/ASSY), Uji Fungsional (assy_qc), Dokumen Mutu (wh_qc).",
        "mermaid": """flowchart LR
    subgraph D1["Dok 1 - Supply Material"]
        DS1["Gudang Klik Cetak WO"] --> DS2["Generate WH/WO/xxxxxx"]
        DS2 --> DS3["Tabel 22 Baris"]
        DS3 --> DS4["TTD: Gudang + Operator"]
    end
    subgraph D2["Dok 2 - Traveler Perakitan"]
        DA1["Assembly Selesai"] --> DA2["Generate WH/ASSY/xxxxxx"]
        DA2 --> DA3["Tabel + Waktu + Status"]
        DA3 --> DA4["TTD: Gudang + Operator + QC"]
    end
    subgraph D3["Dok 3 & 4 - Quality Control"]
        DQ1["QC Tab"] --> DQ2["Uji Fungsional"]
        DQ2 --> DQ3["Dokumen Mutu"]
        DQ3 --> DQ4["Hasil Test & Criteria"]
    end
    DS4 --> PRINT["Print / PDF"]
    DA4 --> PRINT
    DQ4 --> PRINT
    PRINT --> EXCEL["Export Excel"]"""
    },
    {
        "id": "s9",
        "title": "Alur Laporan & Traceability",
        "icon": "📊",
        "desc": "Empat sub-tab laporan dengan filter tanggal/status/keyword, detail modal, dan ekspor ke Excel.",
        "mermaid": """flowchart TD
    R_START(["Buka Tab Laporan"]) --> R1
    R1{"Pilih Sub-Tab Laporan"}
    R1 -->|Sub-tab 1| R2["WO Traveler Report"]
    R1 -->|Sub-tab 2| R3["Assy Test Report"]
    R1 -->|Sub-tab 3| R4["QC Test Report"]
    R1 -->|Sub-tab 4| R5["Warehouse Receipt"]
    R2 --> R6["Filter: Tanggal, Status, Keyword"]
    R3 --> R6
    R4 --> R6
    R5 --> R6
    R6 --> R7["Tampilkan Tabel Data"]
    R7 --> R8{"Aksi?"}
    R8 -->|Detail| R9["Modal Detail per Tray/Set"]
    R8 -->|Export| R10["Export Excel - SheetJS"]
    R8 -->|Cetak| R11["Print Dokumen"]
    style R_START fill:#e91e63,color:#fff"""
    },
    {
        "id": "s10",
        "title": "Alur Delivery Order & Packing List",
        "icon": "📦",
        "desc": "Pembuatan Delivery Order, Packing List, cetak PDF, upload Cloudinary, dan tracking publik.",
        "mermaid": """flowchart TD
    DO_START(["Buat Delivery Order"]) --> DO1
    DO1["Input Data Customer & Tanggal"] --> DO2["Generate Nomor DO Otomatis"]
    DO2 --> DO3["Pilih SN dari Part Done"]
    DO3 --> DO4["Buat Packing List & Crate"]
    DO4 --> DO5["Buat Box & Assign SN ke Slot"]
    DO5 --> DO6{"Simpan?"}
    DO6 -->|Ya| DO7["Insert: delivery_orders, packing_lists, boxes, items"]
    DO7 --> DO8["Generate PDF Packing List"]
    DO8 --> DO9{"Upload ke Cloud?"}
    DO9 -->|Ya| DO10["Upload Cloudinary + QR Code"]
    DO9 -->|Tidak| DO11["Simpan Lokal"]
    DO10 --> DO12["Tracking Publik"]
    DO11 --> DO12
    style DO_START fill:#ff9800,color:#fff"""
    },
    {
        "id": "s11",
        "title": "Alur Penanganan Hold / Scrap / Rework",
        "icon": "🔁",
        "desc": "Proses penanganan part non-good: review admin, re-register, replacement, dan pengerjaan ulang.",
        "mermaid": """flowchart TD
    HS_START(["Part Non-Good"]) --> HS1
    HS1{"Status Part?"}
    HS1 -->|HOLD| HS2["Masuk Production Hold Items"]
    HS2 --> HS3{"Admin Review"}
    HS3 -->|Release| HS4["Status: Good"]
    HS3 -->|Tetap Hold| HS5["Menunggu Keputusan"]
    HS3 -->|Re-register| HS6["Ajukan Re-register"]
    HS1 -->|SCRAP| HS7["Part Rusak Permanen"]
    HS7 --> HS8{"Bisa Diperbaiki?"}
    HS8 -->|Ya| HS6
    HS8 -->|Tidak| HS9["Perlu Replacement"]
    HS9 --> HS10["Operator Ajukan Replace"]
    HS10 --> HS11["Pilih Part Baru"]
    HS11 --> HS12["Admin Approve Replacement"]
    HS12 --> HS13["Part Lama: Tersedia, Baru: Supplied"]
    HS1 -->|REWORK| HS14["Operator Kerjakan Ulang"]
    HS14 --> HS15["Submit Ulang Hasil"]
    HS15 --> HS16{"Status Baru?"}
    HS16 -->|Good| HS4
    HS16 -->|Bermasalah| HS1
    HS6 --> HS17["Buat re_register_requests"]
    HS17 --> HS18{"Admin Approve?"}
    HS18 -->|Ya| HS19["Status: Tersedia + Audit Log"]
    HS18 -->|Tidak| HS20["Ditolak + Notes"]
    style HS_START fill:#ff6b6b,color:#fff"""
    },
    {
        "id": "s12",
        "title": "Entity Relationship Diagram (ERD)",
        "icon": "🗄️",
        "desc": "Relasi antar 9 tabel utama dalam database SQLite sistem Final Assembly.",
        "mermaid": """erDiagram
    users {
        int id PK
        varchar nik UK
        varchar full_name
        varchar username UK
        varchar role
        varchar department
        json permissions
    }
    master_stocks {
        int id PK
        varchar sn
        varchar cs
        varchar part_type
        varchar status
        int allocated_tray_id FK
    }
    trays {
        int id PK
        varchar tray_code UK
        varchar pic_warehouse
        varchar assigned_operator
        varchar status
    }
    parts {
        int id PK
        int tray_id FK
        varchar part_type
        varchar sn
        varchar status
    }
    assemblies {
        int id PK
        int tray_id FK
        int set_number
        varchar pic_assembly
        varchar part15_sn
        varchar part12_sn
        varchar part1_sn
        varchar status
        varchar verified_status
    }
    part_audit_logs {
        int id PK
        varchar sn
        varchar old_status
        varchar new_status
        varchar action_type
    }
    re_register_requests {
        int id PK
        varchar sn
        varchar status
        varchar requestor_username
    }
    assembly_downtimes {
        int id PK
        int tray_id FK
        int set_number
        varchar operator
    }
    assembly_wo_history {
        int id PK
        int tray_id FK
        varchar status
        varchar source
    }
    users ||--o{ trays : "manages"
    trays ||--|{ parts : "contains"
    trays ||--|{ assemblies : "produces"
    trays ||--o{ assembly_downtimes : "logs"
    trays ||--o{ assembly_wo_history : "records"
    master_stocks }o--|| trays : "allocated_to"
    assemblies ||--o{ re_register_requests : "triggers"
    assemblies ||--o{ part_audit_logs : "audits" """
    },
]


def mermaid_to_png(mermaid_code, width=800):
    """Render mermaid diagram to PNG using mermaid.ink API"""
    # Encode mermaid code for mermaid.ink
    mermaid_json = json.dumps({"code": mermaid_code, "mermaid": {"theme": "default"}})
    encoded = base64.urlsafe_b64encode(mermaid_json.encode('utf-8')).decode('ascii')
    
    url = f"https://mermaid.ink/img/{encoded}?width={width}&bgColor=white"
    
    print(f"  Downloading diagram from mermaid.ink ...", end=" ", flush=True)
    
    req = urllib.request.Request(url, headers={
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept': 'image/png,image/*'
    })
    
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = resp.read()
            print(f"OK ({len(data)} bytes)", flush=True)
            return data
    except urllib.error.HTTPError as e:
        print(f"HTTP Error {e.code}", flush=True)
        # Try pako/deflate encoding as fallback
        try:
            compressed = zlib.compress(mermaid_code.encode('utf-8'), 9)
            pako_encoded = base64.urlsafe_b64encode(compressed).decode('ascii')
            url2 = f"https://mermaid.ink/img/pako:{pako_encoded}?bgColor=white"
            req2 = urllib.request.Request(url2, headers={
                'User-Agent': 'Mozilla/5.0',
                'Accept': 'image/png,image/*'
            })
            with urllib.request.urlopen(req2, timeout=30) as resp2:
                data = resp2.read()
                print(f"  Fallback OK ({len(data)} bytes)", flush=True)
                return data
        except Exception as e2:
            print(f"  Fallback also failed: {e2}", flush=True)
            return None
    except Exception as e:
        print(f"Error: {e}", flush=True)
        return None


def build_pdf(output_path):
    """Build the complete PDF document"""
    
    from datetime import datetime
    now = datetime.now()
    date_str = now.strftime("%d %B %Y")
    
    # Month names in Indonesian
    months_id = {
        'January': 'Januari', 'February': 'Februari', 'March': 'Maret',
        'April': 'April', 'May': 'Mei', 'June': 'Juni',
        'July': 'Juli', 'August': 'Agustus', 'September': 'September',
        'October': 'Oktober', 'November': 'November', 'December': 'Desember'
    }
    for en, idn in months_id.items():
        date_str = date_str.replace(en, idn)
    
    W, H = A4  # 595.28 x 841.89 pts
    
    # ==================== STYLES ====================
    styles = getSampleStyleSheet()
    
    style_cover_title = ParagraphStyle(
        'CoverTitle', parent=styles['Normal'],
        fontSize=28, leading=36, alignment=TA_CENTER,
        textColor=white, fontName='Helvetica-Bold',
        spaceAfter=12
    )
    
    style_cover_subtitle = ParagraphStyle(
        'CoverSubtitle', parent=styles['Normal'],
        fontSize=15, leading=20, alignment=TA_CENTER,
        textColor=HexColor('#b0bec5'), fontName='Helvetica',
        spaceAfter=20
    )
    
    style_cover_meta = ParagraphStyle(
        'CoverMeta', parent=styles['Normal'],
        fontSize=10, leading=15, alignment=TA_CENTER,
        textColor=HexColor('#90a4ae'), fontName='Helvetica',
    )
    
    style_section_title = ParagraphStyle(
        'SectionTitle', parent=styles['Normal'],
        fontSize=16, leading=21, alignment=TA_LEFT,
        textColor=PRIMARY, fontName='Helvetica-Bold',
        spaceBefore=0, spaceAfter=4
    )
    
    style_section_desc = ParagraphStyle(
        'SectionDesc', parent=styles['Normal'],
        fontSize=9.5, leading=14, alignment=TA_LEFT,
        textColor=TEXT_LIGHT, fontName='Helvetica',
        spaceBefore=0, spaceAfter=10, leftIndent=0
    )
    
    style_toc_title = ParagraphStyle(
        'TOCTitle', parent=styles['Normal'],
        fontSize=20, leading=26, alignment=TA_LEFT,
        textColor=PRIMARY, fontName='Helvetica-Bold',
        spaceAfter=6
    )
    
    style_normal = ParagraphStyle(
        'NormalCustom', parent=styles['Normal'],
        fontSize=9.5, leading=13, alignment=TA_LEFT,
        textColor=TEXT_COLOR, fontName='Helvetica',
    )
    
    # ==================== BUILD ELEMENTS ====================
    elements = []
    
    # ---- COVER PAGE ----
    elements.append(Spacer(1, 100))
    elements.append(Paragraph(
        "<font color='#ffd54f' size='11'><b>STANDAR OPERASIONAL PROSEDUR &amp; ARSITEKTUR SISTEM</b></font>",
        ParagraphStyle('CoverTag', parent=styles['Normal'], alignment=TA_CENTER, spaceAfter=18)
    ))
    elements.append(Paragraph(
        "FLOWCHART SISTEM<br/>FINAL ASSEMBLY &amp; TRACEABILITY",
        style_cover_title
    ))
    elements.append(Paragraph(
        "Sistem Digitalisasi Perakitan &amp; Pelacakan Komponen Nanggala P9",
        style_cover_subtitle
    ))
    
    elements.append(HRFlowable(width="25%", thickness=3, color=ACCENT, 
                               spaceAfter=26, spaceBefore=0, hAlign='CENTER'))
    
    # Meta info table
    meta_data = [
        ['PRODUK', 'VERSI SISTEM', 'TANGGAL RILIS', 'TOTAL MODUL'],
        ['Nanggala P9', 'System FA V3.0', date_str, '13 Bagian Lengkap']
    ]
    meta_table = Table(meta_data, colWidths=[125, 125, 140, 125])
    meta_table.setStyle(TableStyle([
        ('TEXTCOLOR', (0, 0), (-1, 0), HexColor('#90a4ae')),
        ('TEXTCOLOR', (0, 1), (-1, 1), white),
        ('FONTSIZE', (0, 0), (-1, 0), 8.5),
        ('FONTSIZE', (0, 1), (-1, 1), 11.5),
        ('FONTNAME', (0, 0), (-1, 0), 'Helvetica-Bold'),
        ('FONTNAME', (0, 1), (-1, 1), 'Helvetica-Bold'),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 6),
    ]))
    elements.append(meta_table)
    
    elements.append(Spacer(1, 130))
    elements.append(Paragraph(
        "<b>PT NKRI -- Divisi Produksi &amp; Quality Control</b><br/>"
        "Dokumen Resmi Alur Kerja Sistem Final Assembly",
        style_cover_meta
    ))
    elements.append(PageBreak())
    
    # ---- TABLE OF CONTENTS ----
    elements.append(Paragraph("Daftar Isi Dokumentasi Sistem", style_toc_title))
    elements.append(Paragraph(
        "Dokumen ini memuat arsitektur menyeluruh, alur kerja antar-divisi, dan state diagram dari Sistem Final Assembly V3.",
        style_section_desc
    ))
    elements.append(Spacer(1, 6))
    
    toc_items = [
        ("01", "Arsitektur Sistem (Overview)", "Struktur Frontend Browser, Backend Server REST API, dan Database SQLite WAL"),
        ("02", "Alur Autentikasi & Hak Akses", "Login pengguna, validasi session token, dan Role-Based Access Control (RBAC)"),
        ("03", "Alur Utama End-to-End (Proses Inti)", "Siklus 5 fase: Gudang -> Perakitan -> QC -> Verifikasi -> Dokumen"),
        ("04", "Detail Alur Gudang (Warehouse Flow)", "Pengecekan stok master, alokasi 22 set per tray, dan cetak WO Supply"),
        ("05", "Detail Alur Perakitan (Assembly Flow)", "Pemilihan tray, timer per set, perakitan 3 komponen, pause downtime"),
        ("06", "Detail Alur Verifikasi & Approval", "Verifikasi perakitan, replacement part rusak, re-register, dan hold"),
        ("07", "State Machine Status Komponen", "Transisi siklus hidup status part dari Tersedia hingga Done di Master Stocks"),
        ("08", "Alur Cetak Dokumen Resmi", "Penerbitan WO Supply, Traveler Perakitan, Form QC, dan Dokumen Mutu"),
        ("09", "Alur Laporan & Traceability", "Pencarian jejak audit SN, laporan downtime, dan histori perakitan"),
        ("10", "Alur Delivery Order & Packing List", "Pemilihan batch finished goods, generate Packing List, dan cetak DO"),
        ("11", "Alur Penanganan Hold, Scrap & Rework", "Mekanisme approval penanganan kendala part dan audit re-register"),
        ("12", "Entity Relationship Diagram (ERD)", "Relasi antar 9 tabel database dalam sistem traceability"),
        ("13", "Matriks Modul & Hak Akses per Role", "Tabel pemetaan modul dan wewenang pengguna (Admin, Gudang, Assembly)"),
    ]
    
    toc_data = []
    for num, title, desc in toc_items:
        toc_data.append([
            Paragraph(f'<font color="#1a237e"><b>{num}</b></font>', ParagraphStyle('tn', fontSize=9.5, fontName='Helvetica-Bold', alignment=TA_CENTER)),
            Paragraph(f'<b>{title}</b><br/><font color="#546e7a" size="8">{desc}</font>', ParagraphStyle('tt', fontSize=9, leading=12, fontName='Helvetica')),
        ])
    
    toc_table = Table(toc_data, colWidths=[32, 483])
    toc_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 4.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4.5),
        ('LINEBELOW', (0, 0), (-1, -1), 0.5, HexColor('#e2e8f0')),
    ]))
    elements.append(toc_table)
    elements.append(PageBreak())
    
    # ---- DIAGRAM SECTIONS (1 TO 12) ----
    print("\n=== Generating PDF: Flowchart Sistem Final Assembly ===\n", flush=True)
    
    img_cache_dir = os.path.join(os.path.dirname(output_path), "flowchart_images")
    os.makedirs(img_cache_dir, exist_ok=True)
    
    for idx, diagram in enumerate(DIAGRAMS):
        section_num = idx + 1
        print(f"[{section_num:02d}/13] {diagram['title']}", flush=True)
        
        elements.append(Paragraph(
            f'<font color="#1a237e"><b>{section_num:02d}.</b></font> {diagram["title"]}',
            style_section_title
        ))
        elements.append(Paragraph(diagram["desc"], style_section_desc))
        elements.append(Spacer(1, 4))
        
        # Check cached image
        cache_file = os.path.join(img_cache_dir, f"diagram_{section_num:02d}.png")
        if os.path.exists(cache_file) and os.path.getsize(cache_file) > 1000:
            print("  Using cached image", flush=True)
            img_data = open(cache_file, 'rb').read()
        else:
            img_data = mermaid_to_png(diagram["mermaid"], width=900)
            if img_data:
                with open(cache_file, 'wb') as f:
                    f.write(img_data)
                time.sleep(0.5)
        
        if img_data:
            img_stream = io.BytesIO(img_data)
            try:
                img = Image(img_stream)
                max_w = W - 80   # 515.28 pts printable width
                max_h = H - 210  # 631.89 pts max height
                
                iw, ih = img.drawWidth, img.drawHeight
                if iw > 0 and ih > 0:
                    scale = min(max_w / iw, max_h / ih, 1.0)
                    img.drawWidth = iw * scale
                    img.drawHeight = ih * scale
                    img.hAlign = 'CENTER'
                
                elements.append(img)
            except Exception as e:
                print(f"  Error loading image: {e}", flush=True)
                elements.append(Paragraph(
                    '<i>[Diagram tidak dapat dimuat ke PDF. Buka static/flowchart_system.html untuk melihat.]</i>',
                    style_normal
                ))
        else:
            elements.append(Paragraph(
                '<i>[Diagram tidak dapat di-render. Buka static/flowchart_system.html untuk melihat.]</i>',
                style_normal
            ))
        
        elements.append(PageBreak())
    
    # ---- SECTION 13: ROLE ACCESS MATRIX TABLE ----
    print("[13/13] Ringkasan Modul & Hak Akses Tab per Role", flush=True)
    elements.append(Paragraph(
        '<font color="#1a237e"><b>13.</b></font> Ringkasan Modul & Hak Akses Tab per Role',
        style_section_title
    ))
    elements.append(Paragraph(
        "Matriks wewenang dan hak akses operasional tab sistem untuk Admin, Gudang, dan Assembly.",
        style_section_desc
    ))
    elements.append(Spacer(1, 6))
    
    col_style_hdr = ParagraphStyle('hdr', fontName='Helvetica-Bold', fontSize=8.5, leading=11, alignment=TA_CENTER, textColor=white)
    col_style_txt = ParagraphStyle('txt', fontName='Helvetica', fontSize=8, leading=11, textColor=TEXT_COLOR)
    col_style_bld = ParagraphStyle('bld', fontName='Helvetica-Bold', fontSize=8.5, leading=11, textColor=HexColor('#0f172a'))
    
    def cell_status(val):
        if val == 'YA':
            return Paragraph('<font color="#15803d"><b>YA</b></font>', ParagraphStyle('cs', alignment=TA_CENTER, fontName='Helvetica-Bold', fontSize=8.5))
        elif val == 'TIDAK':
            return Paragraph('<font color="#94a3b8"><b>-</b></font>', ParagraphStyle('cs', alignment=TA_CENTER, fontName='Helvetica-Bold', fontSize=8.5))
        elif val == 'LIHAT':
            return Paragraph('<font color="#0284c7"><b>LIHAT</b></font>', ParagraphStyle('cs', alignment=TA_CENTER, fontName='Helvetica-Bold', fontSize=8.5))
        return Paragraph(val, col_style_txt)

    role_rows_raw = [
        ('1', 'Perakitan (Meja Kerja)', 'YA', 'TIDAK', 'YA', 'Card workflow, timer per set, input hasil rakit (Good/Hold/Scrap/Rework)'),
        ('2', 'Gudang (Supply & Verif)', 'YA', 'YA', 'TIDAK', 'Pembuatan tray baru, alokasi 22 set SN, verifikasi fisik tray dari assembly'),
        ('3', 'Print Document', 'YA', 'YA', 'TIDAK', 'Cetak Work Order Supply (WH/WO), Traveler (WH/ASSY), QC & Mutu'),
        ('4', 'Laporan & Traceability', 'YA', 'YA', 'TIDAK', '4 modul report: Ringkasan Tray, Detail Komponen, Audit Trail, & Downtime'),
        ('5', 'Master DB', 'YA', 'LIHAT', 'TIDAK', 'Import & alokasi stok part master (P15, P1, P12). Gudang mode Read-only'),
        ('6', 'Kelola User & Hak Akses', 'YA', 'TIDAK', 'TIDAK', 'Manajemen akun, reset password, dan konfigurasi permission granular'),
        ('7', 'Verifikasi Admin', 'YA', 'TIDAK', 'TIDAK', 'Approval hasil rakit (ACC/Declined), replacement part rusak, & re-register'),
        ('8', 'Production Hold & Rework', 'YA', 'TIDAK', 'TIDAK', 'Review item bermasalah, release ke Good, instruksi rework, atau scrap'),
        ('9', 'Delivery Order & PL', 'YA', 'YA', 'TIDAK', 'Pembuatan DO produk jadi, generate Packing List, dan cetak dokumen PDF'),
        ('10', 'Testing Points Setting', 'YA', 'TIDAK', 'TIDAK', 'Konfigurasi parameter uji fungsional checklist perakitan'),
    ]
    
    table_data = [[
        Paragraph('No', col_style_hdr),
        Paragraph('Modul / Tab Sistem', col_style_hdr),
        Paragraph('Admin', col_style_hdr),
        Paragraph('Gudang', col_style_hdr),
        Paragraph('Assembly', col_style_hdr),
        Paragraph('Fungsi & Cakupan Wewenang', col_style_hdr)
    ]]
    
    for no, tab, adm, gdg, asy, ket in role_rows_raw:
        table_data.append([
            Paragraph(no, ParagraphStyle('no', alignment=TA_CENTER, fontName='Helvetica', fontSize=8.5)),
            Paragraph(f'<b>{tab}</b>', col_style_bld),
            cell_status(adm),
            cell_status(gdg),
            cell_status(asy),
            Paragraph(ket, col_style_txt),
        ])
    
    role_table = Table(table_data, colWidths=[24, 135, 45, 45, 52, 214])
    role_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), PRIMARY),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
        ('GRID', (0, 0), (-1, -1), 0.5, HexColor('#cbd5e1')),
        ('ROWBACKGROUNDS', (0, 1), (-1, -1), [white, HexColor('#f8fafc')]),
    ]))
    elements.append(role_table)
    elements.append(Spacer(1, 14))
    
    # Note box
    note_box_data = [[
        Paragraph(
            '<b>Catatan Hak Akses:</b><br/>'
            '- <b>LIHAT (Read-Only):</b> Pengguna dapat meninjau data tanpa izin menambah, mengedit, atau menghapus.<br/>'
            '- <b>Custom Permissions:</b> Hak akses setiap akun dapat dikonfigurasi secara granular oleh Admin melalui tab Kelola User.<br/>'
            '- <b>Traceability Penuh:</b> Seluruh transaksi dan mutasi status (Good, Hold, Scrap, Rework, Verifikasi) tercatat otomatis di <i>part_audit_logs</i> dengan timestamp, PIC, dan nomor referensi tray.',
            ParagraphStyle('nb', fontName='Helvetica', fontSize=8.5, leading=13, textColor=HexColor('#1e3a8a'))
        )
    ]]
    note_table = Table(note_box_data, colWidths=[515])
    note_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), HexColor('#eff6ff')),
        ('BOX', (0, 0), (-1, -1), 1, HexColor('#bfdbfe')),
        ('TOPPADDING', (0, 0), (-1, -1), 8),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
        ('LEFTPADDING', (0, 0), (-1, -1), 10),
        ('RIGHTPADDING', (0, 0), (-1, -1), 10),
    ]))
    elements.append(note_table)
    
    # Footer on last page
    elements.append(Spacer(1, 24))
    elements.append(HRFlowable(width="100%", thickness=0.5, color=BORDER, spaceAfter=8))
    elements.append(Paragraph(
        f'Dokumen ini dihasilkan otomatis dari Sistem Final Assembly V3 -- PT NKRI<br/>'
        f'Dicetak pada: {date_str}',
        ParagraphStyle('FooterDoc', parent=styles['Normal'],
            fontSize=8.5, leading=12, alignment=TA_CENTER, textColor=TEXT_LIGHT)
    ))
    
    # ==================== BUILD PDF ====================
    print(f"\n[INFO] Building PDF: {output_path}", flush=True)
    
    def on_first_page(canvas, doc):
        """Draw cover page background"""
        canvas.saveState()
        canvas.setFillColor(PRIMARY)
        canvas.rect(0, 0, W, H, fill=1, stroke=0)
        canvas.setFillColor(HexColor('#283593'))
        canvas.rect(0, H * 0.28, W, H * 0.44, fill=1, stroke=0)
        canvas.restoreState()
    
    def on_later_pages(canvas, doc):
        """Draw running header and footer on subsequent pages"""
        canvas.saveState()
        # Header line
        canvas.setStrokeColor(PRIMARY)
        canvas.setLineWidth(1.2)
        canvas.line(40, H - 38, W - 40, H - 38)
        
        # Header text
        canvas.setFont('Helvetica-Bold', 8)
        canvas.setFillColor(PRIMARY)
        canvas.drawString(40, H - 32, "SISTEM FINAL ASSEMBLY & TRACEABILITY -- NANGGALA P9")
        canvas.setFont('Helvetica', 8)
        canvas.setFillColor(TEXT_LIGHT)
        canvas.drawRightString(W - 40, H - 32, f"Halaman {doc.page}")
        
        # Footer line
        canvas.setStrokeColor(BORDER)
        canvas.setLineWidth(0.5)
        canvas.line(40, 30, W - 40, 30)
        canvas.setFont('Helvetica', 7.5)
        canvas.drawCentredString(W / 2, 18, f"System FA V3 -- PT NKRI -- {date_str}")
        canvas.restoreState()
    
    doc = SimpleDocTemplate(
        output_path,
        pagesize=A4,
        rightMargin=40,
        leftMargin=40,
        topMargin=48,
        bottomMargin=42,
        title="Flowchart Sistem Final Assembly & Traceability - Nanggala P9",
        author="System FA V3",
        subject="Flowchart & Diagram Alur Sistem",
    )
    
    doc.build(elements, onFirstPage=on_first_page, onLaterPages=on_later_pages)
    
    print(f"\n[SUCCESS] PDF berhasil dibuat: {output_path}", flush=True)
    print(f"  Ukuran: {os.path.getsize(output_path):,} bytes", flush=True)
    return output_path


if __name__ == "__main__":
    # Ensure stdout handles UTF-8 safely
    if sys.stdout.encoding and sys.stdout.encoding.lower() != 'utf-8':
        try:
            sys.stdout.reconfigure(encoding='utf-8')
        except Exception:
            pass
            
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_dir = os.path.dirname(script_dir) if os.path.basename(script_dir) == 'scripts' else script_dir
    
    output = os.path.join(project_dir, "Flowchart_Sistem_Final_Assembly.pdf")
    build_pdf(output)
