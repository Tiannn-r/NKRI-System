/**
 * ============================================================================
 * FINAL ASSEMBLY TRACEABILITY SYSTEM - UTILITIES (utils.js)
 * ============================================================================
 * Kumpulan fungsi pembantu (helper functions) bersama untuk formatting tanggal,
 * styling badge status, perhitungan durasi, dan manipulasi teks.
 *
 * Dapat digunakan di semua modul JavaScript dan instance Vue.
 * ============================================================================
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    var utilsInstance = factory();
    root.utils = utilsInstance;
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var Utils = {
    /**
     * Parsing string tanggal ke objek Date dengan timezone WIB (UTC+7)
     */
    parseWIBDate: function (dtStr) {
      if (!dtStr) return null;
      if (dtStr instanceof Date) return isNaN(dtStr.getTime()) ? null : dtStr;
      var str = String(dtStr).trim();
      if (!str || ['none', 'null', '-', 'undefined', 'invalid date', 'n/a', ''].includes(str.toLowerCase())) return null;
      if (str.endsWith('Z') || str.includes('+') || (str.length > 10 && str.substring(10).includes('-'))) {
        var d = new Date(str);
        return isNaN(d.getTime()) ? null : d;
      }
      str = str.replace(' ', 'T');
      if (str.length === 10) str += 'T00:00:00';
      var dWib = new Date(str + '+07:00');
      return isNaN(dWib.getTime()) ? null : dWib;
    },

    /**
     * Menghitung waktu selesai efektif (memperbaiki perbedaan UTC/WIB jika ada)
     */
    getEffectiveFinishedAt: function (start, finish) {
      if (!finish || !start) return finish;
      try {
        var d1 = this.parseWIBDate(start);
        var d2 = this.parseWIBDate(finish);
        if (d1 && d2 && !isNaN(d1.getTime()) && !isNaN(d2.getTime()) && d2 < d1) {
          var diff = d1.getTime() - d2.getTime();
          if (diff >= 18000 * 1000 && diff <= 32400 * 1000) {
            d2 = new Date(d2.getTime() + 7 * 3600 * 1000);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            return (
              d2.getFullYear() + '-' +
              pad(d2.getMonth() + 1) + '-' +
              pad(d2.getDate()) + ' ' +
              pad(d2.getHours()) + ':' +
              pad(d2.getMinutes()) + ':' +
              pad(d2.getSeconds())
            );
          }
        }
      } catch (_) { }
      return finish;
    },

    /**
     * Format tanggal ringkas (DD/MM/YYYY)
     */
    formatDateOnly: function (dt) {
      if (!dt) return '-';
      try {
        var d = this.parseWIBDate(dt);
        if (!d || isNaN(d.getTime())) return String(dt);
        return d.toLocaleDateString('id-ID', {
          timeZone: 'Asia/Jakarta',
          day: '2-digit', month: '2-digit', year: 'numeric'
        });
      } catch (_) {
        return String(dt);
      }
    },

    /**
     * Format jam saja (HH.MM)
     */
    formatTimeOnly: function (dtStr, refStartStr) {
      if (!dtStr) return '-';
      try {
        var effective = dtStr;
        if (refStartStr) {
          effective = this.getEffectiveFinishedAt(refStartStr, dtStr);
        }
        var d = this.parseWIBDate(effective);
        if (!d || isNaN(d.getTime())) return String(dtStr);
        var parts = new Intl.DateTimeFormat('id-ID', {
          timeZone: 'Asia/Jakarta',
          hour: '2-digit',
          minute: '2-digit',
          hour12: false
        }).format(d);
        return parts.replace(':', '.');
      } catch (_) {
        return dtStr;
      }
    },

    /**
     * Format tanggal dan waktu standar (DD MMM YYYY, HH.MM)
     */
    formatDateTime: function (dtStr, refStartStr) {
      if (!dtStr) return '-';
      try {
        var effective = dtStr;
        if (refStartStr) {
          effective = this.getEffectiveFinishedAt(refStartStr, dtStr);
        }
        var d = this.parseWIBDate(effective);
        if (!d || isNaN(d.getTime())) return String(dtStr);
        var datePart = d.toLocaleDateString('id-ID', {
          timeZone: 'Asia/Jakarta',
          day: 'numeric', month: 'short', year: 'numeric'
        });
        var timePart = new Intl.DateTimeFormat('id-ID', {
          timeZone: 'Asia/Jakarta',
          hour: '2-digit', minute: '2-digit',
          hour12: false
        }).format(d).replace(':', '.');
        return datePart + ', ' + timePart;
      } catch (_) {
        return String(dtStr);
      }
    },

    /**
     * Format tanggal lengkap Bahasa Indonesia (DD MMMM YYYY)
     */
    formatIndoDate: function (dt) {
      if (!dt) dt = new Date();
      var d = (dt instanceof Date) ? dt : new Date(dt);
      if (isNaN(d.getTime())) return String(dt);
      var months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ];
      return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    },

    /**
     * Format tanggal + waktu Bahasa Indonesia (DD MMMM YYYY, HH:MM:SS WIB)
     */
    formatIndoDateTime: function (dt) {
      if (!dt) return '-';
      var d = (dt instanceof Date) ? dt : new Date(dt);
      if (isNaN(d.getTime())) return String(dt);
      var months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ];
      var pad = function (n) { return String(n).padStart(2, '0'); };
      return (
        d.getDate() + ' ' +
        months[d.getMonth()] + ' ' +
        d.getFullYear() + ', ' +
        pad(d.getHours()) + ':' +
        pad(d.getMinutes()) + ':' +
        pad(d.getSeconds()) + ' WIB'
      );
    },

    /**
     * Format durasi waktu pengerjaan (misal: "12 mnt 30 dtk")
     */
    formatDuration: function (start, finish) {
      if (start === null || start === undefined || start === '') return '-';
      try {
        var diffSec = 0;
        if (finish !== undefined && finish !== null && finish !== '') {
          var d1 = this.parseWIBDate(start);
          var d2 = this.parseWIBDate(finish);
          if (d1 && d2 && !isNaN(d1.getTime()) && !isNaN(d2.getTime())) {
            if (d2 < d1) {
              var diff = d1.getTime() - d2.getTime();
              if (diff >= 18000 * 1000 && diff <= 32400 * 1000) {
                d2 = new Date(d2.getTime() + 7 * 3600 * 1000);
              }
            }
            diffSec = Math.max(0, Math.floor((d2 - d1) / 1000));
          }
        } else if (typeof start === 'number') {
          diffSec = Math.max(0, Math.floor(start));
        } else if (!isNaN(Number(start))) {
          diffSec = Math.max(0, Math.floor(Number(start)));
        } else {
          return '-';
        }

        var h = Math.floor(diffSec / 3600);
        var m = Math.floor((diffSec % 3600) / 60);
        var s = diffSec % 60;

        if (h > 0) return h + ' jam ' + m + ' mnt ' + s + ' dtk';
        if (m > 0) return m + ' mnt ' + s + ' dtk';
        return s + ' dtk';
      } catch (_) {
        return '-';
      }
    },

    /**
     * CSS Class untuk badge status (Pill badge)
     */
    getStatusBadgeClass: function (status) {
      if (!status || status === '-') return 'bg-slate-100 text-slate-500 border-slate-200';
      var s = String(status).toLowerCase();
      if (s === 'ru' || s === 'dirakit ulang') return 'bg-indigo-100 text-indigo-900 border-indigo-300';
      if (s === 'good (ru)') return 'bg-emerald-100 text-emerald-900 border-emerald-400';
      if (s === 'hold (ru)') return 'bg-rose-100 text-rose-900 border-rose-300';
      if (s === 'pending' || s.includes('menunggu') || s.includes('belum')) return 'bg-amber-50 text-amber-800 border-amber-300';
      if (s === 'returned' || s === 'dikembalikan') return 'bg-amber-100 text-amber-800 border-amber-300';
      if (s === 'good' || s === 'tersedia') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      if (s === 'supplied') return 'bg-blue-50 text-blue-700 border-blue-200';
      if (s === 'hold') return 'bg-amber-50 text-amber-700 border-amber-200';
      if (s === 'scrap') return 'bg-red-50 text-red-700 border-red-200';
      if (s === 'rework') return 'bg-purple-50 text-purple-700 border-purple-200';
      if (s === 'decline' || s === 'declined' || s === 'pending verif admin') return 'bg-rose-50 text-rose-700 border-rose-200';
      return 'bg-slate-100 text-slate-700 border-slate-200';
    },

    /**
     * CSS Class untuk teks warna status
     */
    getStatusTextClass: function (status) {
      if (!status || status === '-') return 'text-slate-500 font-bold';
      var s = String(status).toLowerCase().trim();
      if (['good', 'tersedia', 'pass', 'terima', 'accepted', 'acc', 'completed'].includes(s) || (s.includes('good') && !s.includes('not'))) return 'text-emerald-600 font-bold';
      if (['hold', 'hold_assy', 'hold_qc', 'pending'].includes(s) || s.includes('menunggu') || s.includes('belum') || s.startsWith('hold')) return 'text-amber-600 font-bold';
      if (['rework', 'rework_assy', 'rework_qc', 'ru', 'dirakit ulang'].includes(s)) return 'text-purple-600 font-bold';
      if (['scrap', 'rusak', 'not_good', 'decline', 'declined', 'fail'].includes(s)) return 'text-red-600 font-bold';
      return 'text-slate-600 font-bold';
    },

    /**
     * Debounce helper untuk input pencarian / input cepat
     */
    debounce: function (func, wait) {
      var timeout;
      return function () {
        var context = this;
        var args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
          func.apply(context, args);
        }, wait || 300);
      };
    },

    /**
     * Truncate teks panjang dengan ellipsis
     */
    truncateText: function (text, maxLen) {
      if (!text) return '';
      var max = maxLen || 30;
      var s = String(text);
      if (s.length <= max) return s;
      return s.substring(0, max) + '...';
    },

    /**
     * Salin teks ke clipboard
     */
    copyToClipboard: function (text) {
      if (!navigator.clipboard) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
          document.execCommand('copy');
        } catch (_) { }
        document.body.removeChild(ta);
        return Promise.resolve(true);
      }
      return navigator.clipboard.writeText(text);
    },

    /**
     * Enterprise Excel Exporter menggunakan ExcelJS (dengan fallback XLSX)
     * Mendukung auto-column width, styled dark header, zebra rows, border grid,
     * dan highlighting badge warna pada kolom Status Aktual.
     */
    exportToExcel: async function (config) {
      config = config || {};
      var filename = config.filename || ('Laporan_' + Date.now() + '.xlsx');
      var sheetName = config.sheetName || 'Laporan';
      var title = config.title || 'LAPORAN SISTEM FINAL ASSEMBLY';
      var subtitle = config.subtitle || ('Dicetak otomatis oleh Sistem Final Assembly & Traceability NKRI • Tanggal: ' + new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) + ' WIB');
      var summary = config.summary || null;
      var columns = config.columns || [];
      var rawData = config.rows || [];
      var triggerToast = config.triggerToast || (window.utils && window.utils.triggerToast) || function () {};

      if (!rawData || rawData.length === 0) {
        if (typeof triggerToast === 'function') {
          triggerToast('Ekspor Dibatalkan', 'Tidak ada data untuk diekspor ke Excel.', 'warn');
        }
        return false;
      }

      // Fallback jika ExcelJS tidak tersedia
      if (typeof ExcelJS === 'undefined') {
        if (typeof XLSX !== 'undefined') {
          var headerRow = columns.map(function (c) { return c.header; });
          var wsData = [headerRow].concat(rawData);
          var ws = XLSX.utils.aoa_to_sheet(wsData);
          var wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, sheetName);
          XLSX.writeFile(wb, filename);
          if (typeof triggerToast === 'function') {
            triggerToast('Ekspor Selesai', 'File ' + filename + ' berhasil diunduh.', 'success');
          }
          return true;
        } else {
          if (typeof triggerToast === 'function') {
            triggerToast('Error Ekspor', 'Library ExcelJS / XLSX tidak ditemukan.', 'error');
          }
          return false;
        }
      }

      var workbook = new ExcelJS.Workbook();
      workbook.creator = 'Sistem Final Assembly P9';
      workbook.created = new Date();
      var sheet = workbook.addWorksheet(sheetName, {
        pageSetup: { orientation: 'landscape', paperSize: 9 }
      });

      var colCount = columns.length;

      // 1. Title Row
      sheet.mergeCells(1, 1, 1, colCount);
      var titleCell = sheet.getCell(1, 1);
      titleCell.value = title;
      titleCell.font = { name: 'Arial', size: 14, bold: true, color: { argb: 'FF1E293B' } };
      titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
      sheet.getRow(1).height = 28;

      // 2. Subtitle Row
      sheet.mergeCells(2, 1, 2, colCount);
      var subTitleCell = sheet.getCell(2, 1);
      subTitleCell.value = subtitle;
      subTitleCell.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF64748B' } };
      subTitleCell.alignment = { horizontal: 'center', vertical: 'middle' };
      sheet.getRow(2).height = 18;

      var curRowIdx = 3;

      // 3. Optional Summary Box
      if (summary) {
        var summaryText = typeof summary === 'string' ? summary : summary.join('   |   ');
        sheet.mergeCells(curRowIdx, 1, curRowIdx, colCount);
        var sumCell = sheet.getCell(curRowIdx, 1);
        sumCell.value = summaryText;
        sumCell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0F172A' } };
        sumCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF1F5F9' } };
        sumCell.alignment = { horizontal: 'center', vertical: 'middle' };
        sheet.getRow(curRowIdx).height = 20;
        curRowIdx++;
      }

      // Empty separator row
      sheet.addRow([]);
      curRowIdx++;

      // 4. Header Row
      var headerVals = columns.map(function (c) { return c.header; });
      var headerRow = sheet.addRow(headerVals);
      headerRow.height = 25;
      headerRow.eachCell(function (cell) {
        cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle' };
        cell.border = {
          top: { style: 'thin', color: { argb: 'FF0F172A' } },
          left: { style: 'thin', color: { argb: 'FF0F172A' } },
          bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
          right: { style: 'thin', color: { argb: 'FF0F172A' } }
        };
      });

      var colLengths = columns.map(function (c) { return String(c.header || '').length; });

      // 5. Data Rows
      rawData.forEach(function (rowVals, rIdx) {
        var row = sheet.addRow(rowVals);
        row.height = 20;
        var isZebra = rIdx % 2 === 1;

        row.eachCell(function (cell, colNum) {
          var valStr = String(cell.value || '');
          if (valStr.length > (colLengths[colNum - 1] || 0)) {
            colLengths[colNum - 1] = valStr.length;
          }

          cell.font = { name: 'Arial', size: 9.5, color: { argb: 'FF334155' } };
          cell.border = {
            top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
            right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
          };

          // Alignment logic
          var colDef = columns[colNum - 1] || {};
          if (colDef.align) {
            cell.alignment = { horizontal: colDef.align, vertical: 'middle' };
          } else {
            var h = String(colDef.header || '').toLowerCase();
            if (h === 'no' || h.includes('set') || h.includes('sn') || h.includes('waktu') || h.includes('status') || h.includes('tanggal')) {
              cell.alignment = { horizontal: 'center', vertical: 'middle' };
            } else {
              cell.alignment = { horizontal: 'left', vertical: 'middle' };
            }
          }

          // Zebra striping
          if (isZebra) {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
          }

          // Status Badge Coloring
          var upperVal = valStr.toUpperCase();
          var colHeader = String(colDef.header || '').toLowerCase();
          var isStatusCol = colHeader.includes('status');

          if (isStatusCol) {
            if ((upperVal.includes('GOOD') || upperVal.includes('LULUS') || upperVal.includes('ACC') || upperVal.includes('VERIFIED') || upperVal.includes('OK') || upperVal.includes('SELESAI')) && !upperVal.includes('NOT')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF047857' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
            } else if (upperVal.includes('HOLD') || upperVal.includes('TERTAHAN')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFB45309' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF3C7' } };
            } else if (upperVal.includes('DECLINE') || upperVal.includes('TOLAK') || upperVal.includes('NOT GOOD') || upperVal.includes('SCRAP') || upperVal.includes('FAIL')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFB91C1C' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
            } else if (upperVal.includes('MENUNGGU') || upperVal.includes('PENDING') || upperVal.includes('BELUM DIUJI') || upperVal.includes('BELUM')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF3730A3' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFEEF2FF' } };
            } else if (upperVal.includes('RU') || upperVal.includes('DIRAKIT ULANG') || upperVal.includes('REWORK')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF6D28D9' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFEDE9FE' } };
            }
          }
        });
      });

      // 6. Set calculated column widths
      columns.forEach(function (col, idx) {
        var baseW = col.width || 12;
        var len = (colLengths[idx] || 10) + 4;
        sheet.getColumn(idx + 1).width = Math.min(Math.max(baseW, len), 48);
      });

      // 7. Write to buffer and trigger download
      var buffer = await workbook.xlsx.writeBuffer();
      var blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      var link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (typeof triggerToast === 'function') {
        triggerToast('Ekspor Excel Berhasil', 'File ' + filename + ' berhasil diunduh.', 'success');
      }
      return true;
    }
  };

  return Utils;
});

