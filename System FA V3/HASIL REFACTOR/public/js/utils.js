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
      if (['good', 'tersedia', 'pass', 'terima', 'accepted', 'completed'].includes(s)) return 'text-emerald-600 font-bold';
      if (['hold', 'hold_assy', 'hold_qc', 'pending'].includes(s) || s.includes('menunggu')) return 'text-amber-600 font-bold';
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
    }
  };

  return Utils;
});

