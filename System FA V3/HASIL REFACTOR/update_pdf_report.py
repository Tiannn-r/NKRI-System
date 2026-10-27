import re

with open('generate_pdf_report.py', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update Phase 3
content = content.replace(
    'Operator memindai barcode Part 15, Part 1, dan Part 12.',
    'Operator menginput nomor seri Part 15, Part 1, dan Part 12 secara manual atau memilih rekomendasi stok.'
)

# 2. Update Penerimaan Fisik
content = content.replace(
    '• Verifikasi fisik pencocokan barcode.<br/>',
    '• Verifikasi fisik pencocokan Serial Number (SN) manual.<br/>'
)

# 3. Update Anomaly Scanner to Diagnostik Anomali
content = content.replace(
    '• Scanner diagnostik slow query & anomali data.',
    '• Diagnostik server, slow query & anomali data.'
)

# 4. Update Chapter 9 to show the user confirmed decisions
old_chapter_9 = """    # =========================================================================
    # 9. LEMBAR CATATAN REVISI PENGGUNA & TANDA TANGAN
    # =========================================================================
    story.append(KeepTogether([
        Paragraph("9. Lembar Catatan Revisi Pengguna (*Review & Feedback*)", h1_style),
        Paragraph("Silakan gunakan kolom di bawah ini untuk mencatat instruksi atau keputusan atas temuan anomali database legacy sebelum dilakukan penyesuaian akhir:", body_style),
        Spacer(1, 3),
        # Revision Blank Box
        Table([
            [Paragraph("<b>CATATAN / KEPUTUSAN PENGGUNA TERKAIT ANOMALI & ALUR:</b><br/><br/><br/><br/><br/><br/><br/>", ParagraphStyle('RevBox', parent=styles['Normal'], fontName='Helvetica', fontSize=8, leading=13, textColor=colors.HexColor("#94A3B8")))]
        ], colWidths=[515], style=[
            ('BACKGROUND', (0,0), (-1,-1), colors.HexColor("#F8FAFC")),
            ('BOX', (0,0), (-1,-1), 1, colors.HexColor("#CBD5E1")),
            ('TOPPADDING', (0,0), (-1,-1), 6),
            ('BOTTOMPADDING', (0,0), (-1,-1), 6),
            ('LEFTPADDING', (0,0), (-1,-1), 8),
        ]),
        Spacer(1, 10),
        # Signatures
        Table([
            [
                Paragraph("<b>Disusun Oleh:</b><br/><br/><br/><br/>( Sistem Final Assembly )<br/>Tanggal: 26/10/2026", ParagraphStyle('Sign1', parent=styles['Normal'], fontName='Helvetica', fontSize=7.5, alignment=1)),
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
    ]))"""

new_chapter_9 = """    # =========================================================================
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
        "TF 2 (<code>WH/QC</code>), Penerimaan Gudang (<code>WH/GR</code>). Jika perakitan ulang, akhiran otomatis <code>.../PU/angka</code>."
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
    ]))"""

if old_chapter_9 in content:
    content = content.replace(old_chapter_9, new_chapter_9)
    print("Chapter 9 updated.")
else:
    print("WARNING: old_chapter_9 not found directly, checking regex or line endings.")
    content_norm = content.replace('\r\n', '\n')
    old_norm = old_chapter_9.replace('\r\n', '\n')
    if old_norm in content_norm:
        content = content_norm.replace(old_norm, new_chapter_9.replace('\r\n', '\n'))
        print("Chapter 9 updated with normalized line endings.")

with open('generate_pdf_report.py', 'w', encoding='utf-8') as f:
    f.write(content)

print("generate_pdf_report.py successfully updated.")
