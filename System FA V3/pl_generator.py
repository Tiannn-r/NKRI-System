import os
import io
import re
from datetime import datetime
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.platypus import Table, TableStyle
from reportlab.pdfgen import canvas

def generate_packing_list_pdf(do_data, settings=None):
    if not settings:
        settings = {}
    buf = io.BytesIO()
    width, height = A4  # 595.27 x 841.89 points
    c = canvas.Canvas(buf, pagesize=A4)
    
    crates = do_data.get('crates') or []
    sheets = []
    if do_data.get('package_type') == 'unit' or not crates:
        sheets.append({
            'sheet_idx': 1,
            'total_sheets': 1,
            'c1': {'crate_id': 'Kemasan Satuan', 'boxes': []},
            'c2': None
        })
    else:
        for i in range(0, len(crates), 2):
            sheets.append({
                'sheet_idx': (i // 2) + 1,
                'total_sheets': max(1, (len(crates) + 1) // 2),
                'c1': crates[i],
                'c2': crates[i + 1] if i + 1 < len(crates) else None
            })
            
    company_name = settings.get('company_name') or 'PT Nanggala Kencana Rekatama Indonesia'
    head_office = settings.get('head_office') or 'Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130'
    factory = settings.get('factory_address') or 'Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292'
    contact_email = settings.get('contact_email') or 'info@nanggala.co.id'
    
    base_pl = (crates[0].get('pl_number') if crates else None) or do_data.get('pl_number') or (do_data.get('do_number', '').replace('/SJ/', '/PL/')) or '001/PL/PPIC/10/2026'
    m_pl = re.match(r'^(\d+)(/PL/.*)$', str(base_pl))

    for sheet in sheets:
        c1 = sheet['c1']
        c2 = sheet['c2']
        if m_pl:
            sheet_seq = int(m_pl.group(1)) + (sheet['sheet_idx'] - 1)
            pl_num = f"{sheet_seq:03d}{m_pl.group(2)}"
        else:
            pl_num = c1.get('pl_number') or base_pl
        
        # 1. Top Pink Banner Polygon
        c.setFillColor(colors.HexColor('#F7EAEB'))
        p = c.beginPath()
        p.moveTo(0, height)
        p.lineTo(width, height)
        p.lineTo(width, height - 50)
        p.lineTo(width * 0.52, height - 50)
        p.lineTo(width * 0.46, height - 90)
        p.lineTo(0, height - 90)
        p.close()
        c.drawPath(p, fill=1, stroke=0)
        
        # Company Name
        c.setFillColor(colors.black)
        c.setFont('Helvetica-Bold', 12)
        c.drawRightString(width - 36, height - 32, company_name)
        
        # PACKING LIST title removed as requested
        pass
        
        # Logo
        base_dir = os.path.dirname(os.path.abspath(__file__))
        logo_path = os.path.join(base_dir, 'static', 'nkri-logo.png')
        if not os.path.exists(logo_path):
            logo_path = os.path.join(base_dir, 'static', 'nkri-logo-red.png')
        if os.path.exists(logo_path):
            try:
                c.drawImage(logo_path, 36, height - 70, width=120, height=45, preserveAspectRatio=True, mask='auto')
            except Exception:
                pass
                
        # 2. Metadata Section
        c.setFillColor(colors.black)
        c.setFont('Helvetica', 8.5)
        top_y = height - 105
        
        c2_str = f" & {c2.get('crate_id') or c2.get('crate_number')}" if c2 else ""
        c1_str = str(c1.get('crate_id') or c1.get('crate_number') or '-')
        peti_str = f"{c1_str}{c2_str}"
        
        cust_addr = (do_data.get('customer_address') or '-').replace('\n', ', ')
        if len(cust_addr) > 75:
            cust_addr = cust_addr[:72] + '...'
            
        prog_name = do_data.get('program_name') or '-'
        if len(prog_name) > 75:
            prog_name = prog_name[:72] + '...'
            
        meta_items = [
            ('No. Packing List', f": {pl_num}"),
            ('Tanggal', f": {do_data.get('do_date') or '-'}"),
            ('ID Peti', f": {peti_str}"),
            ('Nama Produk', f": {do_data.get('product_name') or 'Nanggala P9'}"),
            ('Nomor Kontrak', f": {do_data.get('contract_number') or '-'}"),
            ('Program', f": {prog_name}"),
            ('Penerima', f": {do_data.get('customer_name') or '-'} - {cust_addr}")
        ]
        
        y = top_y
        for label, val in meta_items:
            c.setFont('Helvetica-Bold', 8.5)
            c.drawString(36, y, label)
            c.setFont('Helvetica', 8.5)
            c.drawString(130, y, val)
            y -= 13
            
        # 3. Tables Section (Up to 4 boxes)
        def build_box_table(box_id, crate_label, items):
            data = [['No', 'Serial Number']]
            for s in range(1, 11):
                sn_val = ''
                if s - 1 < len(items):
                    it = items[s - 1]
                    sn_val = it.get('serial_number') if isinstance(it, dict) else str(it or '')
                data.append([str(s), sn_val or '-'])
                
            t = Table(data, colWidths=[28, 220])
            t.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#F1F5F9')),
                ('TEXTCOLOR', (0,0), (-1,0), colors.black),
                ('ALIGN', (0,0), (0,-1), 'CENTER'),
                ('ALIGN', (1,0), (1,-1), 'LEFT'),
                ('FONTNAME', (0,0), (-1,0), 'Helvetica-Bold'),
                ('FONTSIZE', (0,0), (-1,0), 8),
                ('BOTTOMPADDING', (0,0), (-1,-1), 2),
                ('TOPPADDING', (0,0), (-1,-1), 2),
                ('GRID', (0,0), (-1,-1), 0.5, colors.black),
                ('FONTNAME', (0,1), (-1,-1), 'Helvetica'),
                ('FONTSIZE', (0,1), (-1,-1), 8),
            ]))
            return t

        boxes_to_render = []
        # Crate 1
        c1_boxes = c1.get('boxes') or []
        for b_idx in range(2):
            if b_idx < len(c1_boxes):
                box = c1_boxes[b_idx]
                bid = box.get('box_id') or (f"{c1.get('crate_id')}-{b_idx+1:02d}" if c1.get('crate_id') else f"Dus {b_idx+1}")
                clabel = c1.get('crate_id') or str(c1.get('crate_number') or '1')
                boxes_to_render.append({
                    'box_id': bid,
                    'crate_label': clabel,
                    'items': box.get('items') or []
                })
            else:
                boxes_to_render.append(None)
            
        # Crate 2 (if any)
        if c2:
            c2_boxes = c2.get('boxes') or []
            for b_idx in range(2):
                if b_idx < len(c2_boxes):
                    box = c2_boxes[b_idx]
                    bid = box.get('box_id') or (f"{c2.get('crate_id')}-{b_idx+1:02d}" if c2.get('crate_id') else f"Dus {b_idx+1}")
                    clabel = c2.get('crate_id') or str(c2.get('crate_number') or '2')
                    boxes_to_render.append({
                        'box_id': bid,
                        'crate_label': clabel,
                        'items': box.get('items') or []
                    })
                else:
                    boxes_to_render.append(None)
        else:
            boxes_to_render.extend([None, None])
            
        table_top_y = y - 10
        row1_y = table_top_y
        
        col_coords = [(36, row1_y), (308, row1_y), (36, row1_y - 235), (308, row1_y - 235)]
        
        for idx, coord in enumerate(col_coords):
            bx = boxes_to_render[idx]
            if not bx:
                continue
            x_pos, y_pos = coord
            c.setFont('Helvetica-Bold', 8.5)
            c.drawString(x_pos, y_pos, f"No. Pak: {bx['box_id']}")
            c.setFont('Helvetica', 7.5)
            c.setFillColor(colors.HexColor('#64748B'))
            c.drawString(x_pos + 120, y_pos, f"(Peti {bx['crate_label']})")
            c.setFillColor(colors.black)
            
            tbl = build_box_table(bx['box_id'], bx['crate_label'], bx['items'])
            tbl.wrapOn(c, 248, 200)
            tbl.drawOn(c, x_pos, y_pos - 195)
            
        # 4. Footer Section
        c.setFont('Helvetica', 8)
        c.setFillColor(colors.HexColor('#64748B'))
        now_str = datetime.now().strftime("%d %B %Y, %H:%M WIB")
        c.drawString(36, 68, f"Tanggal Cetak Dokumen: {now_str}")
        c.drawRightString(width - 36, 68, f"Lembar {sheet['sheet_idx']} dari {sheet['total_sheets']}")
        
        # Pink Footer Banner
        c.setFillColor(colors.HexColor('#F7EAEB'))
        c.rect(0, 0, width, 60, fill=1, stroke=0)
        
        c.setFillColor(colors.HexColor('#CC0000'))
        c.setFont('Helvetica-Bold', 8)
        c.drawString(36, 44, "Head Office :")
        c.drawString(220, 44, "Factory :")
        c.drawString(420, 44, "Kontak :")
        
        c.setFillColor(colors.black)
        c.setFont('Helvetica', 7.5)
        off_lines = head_office.split('\n')
        for i, l in enumerate(off_lines[:2]):
            c.drawString(36, 32 - (i * 10), l)
            
        fac_lines = factory.split('\n')
        for i, l in enumerate(fac_lines[:2]):
            c.drawString(220, 32 - (i * 10), l)
            
        c.drawString(420, 32, contact_email)
        
        c.showPage()
        
    c.save()
    return buf.getvalue()
