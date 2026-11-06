<!DOCTYPE html>
<html lang="id">

<head>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,700&display=swap"
    rel="stylesheet">

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Sistem Assembly & Traceability P9 - NKRI</title>
  <link rel="icon" type="image/png" href="/favicon.png">
  <script>
    const _tok = localStorage.getItem('token');
    if (!_tok || _tok === 'null' || _tok === 'undefined' || _tok.trim() === '') {
      const _cur = window.location.pathname;
      const _param = (_cur && _cur !== '/' && _cur !== '/login') ? '?redirect=' + encodeURIComponent(_cur + window.location.hash) : '';
      window.location.replace('/login' + _param);
    }
  </script>

  <!-- Suppress Tailwind CDN production warning in console -->
  <script>
    (function() {
      var _w = console.warn;
      console.warn = function() {
        if (arguments[0] && typeof arguments[0] === "string" && arguments[0].indexOf("cdn.tailwindcss.com should not be used in production") !== -1) return;
        _w.apply(console, arguments);
      };
    })();
  </script>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Roboto', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Arial', 'sans-serif'],
            mono: ['Roboto', 'Arial', 'sans-serif'],
          }
        }
      }
    }
  </script>

  <!-- FontAwesome 6.5.1 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Vue 3 CDN -->
  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

  <!-- ExcelJS for Rich Styled Excel Export (Colors, auto column width, A4 print ready) -->
  <script src="/exceljs.min.js"></script>
  <!-- SheetJS (xlsx) fallback -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

  <!-- Local QRCode.js (100% offline & tablet friendly) -->
  <script src="/qrcode.min.js"></script>

  <!-- Local html2pdf.js (100% offline client-side PDF generation) -->
  <script src="/html2pdf.bundle.min.js"></script>

  <!-- jsPDF + html2canvas: generate PDF 80x20mm langsung (no CSS @page hack) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,400;1,700&display=swap');

    [v-cloak] {
      display: none !important;
    }

    *:not(i):not([class*="fa-"]):not([class*="fa"]) {
      font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif !important;
    }

    * {
      font-variant-numeric: tabular-nums lining-nums;
      font-feature-settings: 'tnum' 1, 'lnum' 1;
    }

    body {
      background-color: #f8fafc;
      color: #0f172a;
    }

    /* Seluruh Dokumen Resmi & Label Fisik Menggunakan Font Roboto Sesuai Permintaan User */
    .doc-paper,
    .doc-paper *:not(i),
    .print-doc-wrapper,
    .print-doc-wrapper *:not(i),
    #printable-area,
    #printable-area *:not(i),
    #do-printable-area,
    #do-printable-area *:not(i),
    #label-printable-area,
    #label-printable-area *:not(i),
    .do-preview-scope,
    .do-preview-scope *:not(i) {
      font-family: 'Roboto', Arial, sans-serif !important;
    }

    /* Sembunyikan area cetak di layar secara visual saat tidak mencetak */
    #do-printable-area {
      position: absolute !important;
      left: -9999px !important;
      top: 0 !important;
      opacity: 0 !important;
      pointer-events: none !important;
      z-index: -9999 !important;
      width: 210mm !important;
      overflow: hidden !important;
    }

    /* High-Resolution Sharp QR Code Rendering (Zero Blur, Crisp Edges, Scanner Friendly) */
    .qr-crisp img,
    .qr-crisp canvas,
    #qrcode-container img,
    #qrcode-container canvas,
    #detail-qrcode-container img,
    #detail-qrcode-container canvas,
    [id^="batch-label-preview-qr-"] img,
    [id^="batch-label-preview-qr-"] canvas,
    [id^="batch-label-print-qr-"] img,
    [id^="batch-label-print-qr-"] canvas,
    [id^="do-sn-qr-preview-"] img,
    [id^="do-sn-qr-preview-"] canvas,
    [id^="do-sn-qr-print-"] img,
    [id^="do-sn-qr-print-"] canvas,
    #do-sj-qr-preview img,
    #do-sj-qr-preview canvas,
    #do-sj-qr-print img,
    #do-sj-qr-print canvas {
      width: 100% !important;
      height: 100% !important;
      max-width: 100% !important;
      max-height: 100% !important;
      aspect-ratio: 1 / 1 !important;
      object-fit: contain !important;
      image-rendering: -webkit-optimize-contrast !important;
      image-rendering: crisp-edges !important;
      image-rendering: pixelated !important;
      margin: auto !important;
      background: #ffffff !important;
    }

    #do-sj-qr-preview,
    #do-sj-qr-print {
      width: 50px !important;
      height: 50px !important;
      min-width: 50px !important;
      min-height: 50px !important;
      max-width: 50px !important;
      max-height: 50px !important;
      aspect-ratio: 1 / 1 !important;
      margin: 0 auto !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      box-sizing: border-box !important;
      overflow: hidden !important;
    }

    /* Pastikan elemen yang disembunyikan oleh QRCode.js (seperti canvas display:none) TIDAK dipaksa muncul berdampingan */
    #qrcode-container canvas[style*="display: none"],
    #qrcode-container img[style*="display: none"],
    #detail-qrcode-container canvas[style*="display: none"],
    #detail-qrcode-container img[style*="display: none"],
    [id^="batch-label-preview-qr-"] canvas[style*="display: none"],
    [id^="batch-label-preview-qr-"] img[style*="display: none"],
    [id^="batch-label-print-qr-"] canvas[style*="display: none"],
    [id^="batch-label-print-qr-"] img[style*="display: none"],
    [id^="do-sn-qr-preview-"] canvas[style*="display: none"],
    [id^="do-sn-qr-preview-"] img[style*="display: none"],
    [id^="do-sn-qr-print-"] canvas[style*="display: none"],
    [id^="do-sn-qr-print-"] img[style*="display: none"],
    #do-sj-qr-preview canvas[style*="display: none"],
    #do-sj-qr-preview img[style*="display: none"],
    #do-sj-qr-print canvas[style*="display: none"],
    #do-sj-qr-print img[style*="display: none"] {
      display: none !important;
    }

    body.printing-do-only #do-printable-area {
      position: absolute !important;
      left: 0 !important;
      top: 0 !important;
      opacity: 1 !important;
      visibility: visible !important;
      pointer-events: auto !important;
      z-index: 99999 !important;
      background: white !important;
      overflow: visible !important;
    }

    @page {
      size: A4 portrait;
      margin: 0mm !important;
    }

    /* Screen: A4-proportioned document viewer */
    .doc-viewer-shell {
      background: #cbd5e1;
      padding: 24px 16px;
      min-height: 200px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }

    .doc-paper {
      background: white;
      width: 100%;
      max-width: 794px;
      /* 210mm at 96dpi */
      height: 1123px;
      min-height: 1123px;
      /* 297mm at 96dpi */
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
      padding: 10mm 10mm 10mm 15mm;
      /* Margin: Top 1cm (10mm), Right 1cm (10mm), Bottom 1cm (10mm), Left 1.5cm (15mm) */
      box-sizing: border-box;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .print-doc-wrapper {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
      min-height: 100%;
      flex-grow: 1;
      width: 100%;
    }

    .print-top-section {
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 100%;
    }

    .print-meta-box {
      border: 1.5px solid #0f172a;
      background: white;
    }

    .print-meta-cell {
      border-right: 1px solid #94a3b8;
    }

    .print-items-wrapper>table {
      width: 100% !important;
      max-width: 100% !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
      border: 1.5px solid #0f172a !important;
    }

    .print-items-wrapper>table>thead>tr>th {
      border: 1px solid #64748b !important;
      background: #f1f5f9 !important;
      color: #0f172a !important;
      font-weight: 700 !important;
      text-align: center !important;
      padding: 4px 3px !important;
    }

    .print-items-wrapper>table>tbody>tr>td {
      border: 1px solid #64748b !important;
      color: #0f172a !important;
      padding: 4px 4px !important;
      vertical-align: middle !important;
    }

    /* Nested checklist table in Doc 3 */
    .print-items-wrapper table.nested-checklist-table {
      width: 100% !important;
      table-layout: fixed !important;
      border-collapse: collapse !important;
      border: 1px solid #64748b !important;
      margin: 0 !important;
    }

    .print-items-wrapper table.nested-checklist-table td {
      border: 1px solid #cbd5e1 !important;
      padding: 1.5px 1px !important;
      font-size: 7px !important;
      line-height: 1.1 !important;
      text-align: center !important;
      vertical-align: middle !important;
      color: #0f172a !important;
    }

    .print-footer-signatures {
      border-top: 1.5px solid #0f172a;
      margin-top: auto;
      padding-top: 12px;
      width: 100%;
    }

    @media print {
      @page {
        size: A4 portrait;
        margin: 0mm !important;
      }

      html,
      body {
        background: white !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: 100% !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .no-print,
      header,
      nav,
      button,
      select,
      input,
      .modal-backdrop {
        display: none !important;
      }

      body.printing-label-only * {
        visibility: hidden !important;
      }

      body.printing-label-only #label-printable-area,
      body.printing-label-only #label-printable-area * {
        visibility: visible !important;
      }

      body.printing-label-only #label-printable-area {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        height: 20mm !important;
        margin: 0 !important;
        padding: 1.5mm 2.5mm 1.5mm 3.5mm !important;
        box-sizing: border-box !important;
        border: none !important;
        box-shadow: none !important;
        background: white !important;
        z-index: 99999 !important;
        display: flex !important;
      }

      /* ================= CETAK BATCH LABEL SN ACC (THERMAL 80x20mm) ================= */
      #batch-label-printable-area {
        position: absolute !important;
        left: -9999px !important;
        top: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
        z-index: -9999 !important;
        width: 80mm !important;
        overflow: hidden !important;
      }

      body.printing-batch-labels {
        background: white !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        display: block !important;
      }

      body.printing-batch-labels #app-loading-screen,
      body.printing-batch-labels #app>*:not(#batch-label-printable-area) {
        display: none !important;
      }

      body.printing-batch-labels #batch-label-printable-area {
        visibility: visible !important;
        display: block !important;
        position: static !important;
        left: auto !important;
        top: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 80mm !important;
        min-width: 80mm !important;
        max-width: 80mm !important;
        background: white !important;
        opacity: 1 !important;
        z-index: 99999 !important;
        pointer-events: auto !important;
        overflow: visible !important;
      }

      body.printing-batch-labels #batch-label-printable-area * {
        visibility: visible !important;
      }

      body.printing-batch-labels .batch-label-page {
        page-break-after: always !important;
        break-after: page !important;
      }

      /* ================= CETAK DELIVERY ORDER, PACKING LIST & LABEL RESMI ================= */
      body.printing-do-only {
        background: white !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        display: block !important;
      }

      /* Sembunyikan elemen aplikasi lainnya di layar cetak saat mencetak DO/PL/Label */
      body.printing-do-only #app-loading-screen,
      body.printing-do-only #app>*:not(#do-printable-area) {
        display: none !important;
      }

      /* Tampilkan HANYA #do-printable-area secara statis agar seluruh halaman multi-page terpecah sempurna */
      body.printing-do-only #do-printable-area {
        visibility: visible !important;
        display: block !important;
        position: static !important;
        left: auto !important;
        top: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 210mm !important;
        min-width: 210mm !important;
        max-width: 210mm !important;
        background: white !important;
        opacity: 1 !important;
        z-index: 99999 !important;
        pointer-events: auto !important;
        overflow: visible !important;
      }

      /* Override ukuran untuk label (class ini ditambah via JS saat mencetak label) */
      body.printing-do-only.printing-label-size #do-printable-area {
        width: 100mm !important;
        min-width: 100mm !important;
        max-width: 100mm !important;
      }

      body.printing-do-only.printing-sn-label-size #do-printable-area {
        width: 80mm !important;
        min-width: 80mm !important;
        max-width: 80mm !important;
      }

      body.printing-do-only #do-printable-area *:not(.do-watermark-img) {
        visibility: visible !important;
        opacity: 1 !important;
      }

      .do-watermark-img,
      body.printing-do-only #do-printable-area .do-watermark-img,
      .do-preview-scope .do-watermark-img {
        opacity: 0.065 !important;
        filter: saturate(80%) !important;
      }

      body.printing-do-only .do-page-break {
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
      }

      body.printing-do-only .do-page-break.do-a4-doc {
        width: 210mm !important;
        max-width: 210mm !important;
        height: 297mm !important;
        min-height: 297mm !important;
        max-height: 297mm !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        padding-bottom: 0 !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
      }

      body.printing-do-only .do-page-break.do-label-100x150 {
        width: 100mm !important;
        min-width: 100mm !important;
        max-width: 100mm !important;
        height: 150mm !important;
        min-height: 150mm !important;
        max-height: 150mm !important;
        margin: 0 !important;
        padding: 0 !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
      }

      body.printing-do-only .do-page-break.do-label-80x20 {
        width: 80mm !important;
        min-width: 80mm !important;
        max-width: 80mm !important;
        height: 20mm !important;
        min-height: 20mm !important;
        max-height: 20mm !important;
        margin: 0 !important;
        padding: 1.5mm 2.5mm 1.5mm 3mm !important;
        page-break-after: always !important;
        break-after: page !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
      }

      body.printing-do-only .do-page-break:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
      }

      body.printing-do-only .flex {
        display: flex !important;
      }

      body.printing-do-only .flex-col {
        flex-direction: column !important;
      }

      body.printing-do-only .justify-between {
        justify-content: space-between !important;
      }

      body.printing-do-only .shrink-0 {
        flex-shrink: 0 !important;
      }

      body.printing-do-only .items-start {
        align-items: flex-start !important;
      }

      body.printing-do-only .items-center {
        align-items: center !important;
      }

      /* ===== PAKSA BACKGROUND WARNA TERCETAK (Chrome/Edge default memblokir background) ===== */
      body.printing-do-only #do-printable-area,
      body.printing-do-only #do-printable-area *,
      body.printing-do-only #do-printable-area svg,
      body.printing-do-only #do-printable-area polygon,
      body.printing-do-only #do-printable-area path,
      body.printing-do-only #do-printable-area rect {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }

      #printable-area,
      #printable-area * {
        visibility: visible !important;
      }

      #printable-area {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        height: 255mm !important;
        min-height: 255mm !important;
        max-height: 255mm !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        background: white !important;
        box-sizing: border-box !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      .doc-viewer-shell {
        background: white !important;
        padding: 0 !important;
        display: block !important;
        height: auto !important;
      }

      .doc-paper {
        box-shadow: none !important;
        height: 255mm !important;
        min-height: 255mm !important;
        max-height: 255mm !important;
        padding: 0 !important;
        max-width: 100% !important;
        border: none !important;
        background: white !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      .print-doc-wrapper {
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        width: 100% !important;
        height: 100% !important;
        min-height: 100% !important;
        flex-grow: 1 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      .print-top-section {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        width: 100% !important;
      }

      .print-meta-box {
        border: 1.5px solid #000 !important;
        margin-top: 8px !important;
        margin-bottom: 10px !important;
      }

      .print-meta-cell {
        border-right: 1px solid #000 !important;
      }

      .print-items-wrapper>table {
        border: 1.5px solid #000 !important;
        border-collapse: collapse !important;
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
      }

      .print-items-wrapper>table>thead>tr>th {
        border: 1px solid #000 !important;
        background: #f1f5f9 !important;
        color: #000 !important;
        padding: 3px 2px !important;
        font-size: 9px !important;
      }

      .print-items-wrapper>table>tbody>tr>td {
        border: 1px solid #000 !important;
        color: #000 !important;
        padding: 3px 2px !important;
        font-size: 9px !important;
        vertical-align: middle !important;
      }

      .print-items-wrapper table.nested-checklist-table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border: 1px solid #000 !important;
        margin: 0 !important;
      }

      .print-items-wrapper table.nested-checklist-table td {
        border: 1px solid #64748b !important;
        padding: 1px 1px !important;
        font-size: 7px !important;
        line-height: 1 !important;
        color: #000 !important;
        text-align: center !important;
        vertical-align: middle !important;
      }

      .print-footer-signatures {
        border-top: 1.5px solid #000 !important;
        margin-top: auto !important;
        padding-top: 8px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }
    }

    /* Sembunyikan scrollbar secara global namun tetap bisa scroll */
    html,
    body,
    * {
      -ms-overflow-style: none !important;
      /* IE and Edge */
      scrollbar-width: none !important;
      /* Firefox */
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar,
    *::-webkit-scrollbar {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
      background: transparent !important;
    }
  </style>
  <script src="/logo-data.js"></script>
</head>
