/**
 * ============================================================================
 * FINAL ASSEMBLY TRACEABILITY SYSTEM - ENTERPRISE LOGGER (logger.js)
 * ============================================================================
 * Modul logging terpusat untuk mencatat event dan error sistem secara terstruktur,
 * informatif, dan ramah pengguna (menggunakan bahasa Indonesia resmi pabrik).
 *
 * Fitur:
 * - Adaptive Theme (Dark Mode & Light Mode Chrome DevTools)
 * - Pemetaan otomatis nama menu teknis ke Nama Menu di Tampilan Web
 * - Pemetaan otomatis endpoint API ke Nama Aksi Bahasa Indonesia yang jelas
 * ============================================================================
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    var loggerInstance = factory();
    root.logger = loggerInstance;
    root.log = loggerInstance; // Shortcut alias
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // Daftar kamus nama menu di tampilan website
  var MENU_LABELS = {
    // Menu Gudang
    'warehouse': 'Work Order (Input Part)',
    'production_hold': 'Hasil Perakitan',
    'functional_hold': 'Hasil Test Functional 1 dan 2',
    'returns': 'Pengembalian Barang',
    'warehouse_receipt': 'Good Receipts (WH/GR)',
    'reassembly': 'Perakitan Ulang',
    'traveler': 'Cetak Dokumen',
    'master': 'Master Stok',
    'report': 'History',
    'delivery_order': 'Delivery Order & PL',

    // Menu Perakitan & QC
    'assembly': 'Area Perakitan',
    'assy_testing': 'Test Functional 1 (Assy)',
    'qc': 'Test Functional 2 (QC)',

    // Menu Administrator
    'users/verify_set': 'Verifikasi Hasil Rakitan dan Test',
    'users/verify_part': 'Verifikasi Status Part',
    'users/verify_replacement': 'Verifikasi Pergantian Part',
    'users/audit': 'Riwayat Audit Gudang',
    'users/downtime': 'Audit Downtime Perakitan',
    'users/testing_points': 'Master Testing Point',
    'users/add_part': 'Tambah Part Number',
    'users/manage': 'Kelola Pengguna',
    'users': 'Administrator'
  };

  // Badge pill styling
  var badgeStyle = function (bgColor, textColor) {
    return (
      'background: ' +
      bgColor +
      '; color: ' +
      (textColor || '#ffffff') +
      '; font-weight: 700; font-size: 11px; padding: 2px 8px; border-radius: 4px; letter-spacing: 0.5px;'
    );
  };

  // Text message styling (Inherit browser theme: putih di Dark Mode, hitam di Light Mode)
  var textStyle = function (customColor, isBold) {
    var color = customColor || 'inherit';
    return (
      'color: ' +
      color +
      '; font-size: 12px; font-weight: ' +
      (isBold ? '700' : '500') +
      '; padding-left: 6px;'
    );
  };

  var parseError = function (err) {
    if (!err) return { status: '', url: '', message: 'Terjadi kendala sistem' };

    // Format Axios Error
    if (err.response) {
      var status = err.response.status || 'ERR';
      var statusText = err.response.statusText || '';
      var url = (err.config && err.config.url) || '';
      var method = (err.config && err.config.method ? err.config.method.toUpperCase() : 'REQ');
      var serverMsg =
        (err.response.data && (err.response.data.message || err.response.data.error)) ||
        statusText ||
        'Server Error';

      return {
        status: status,
        url: method + ' ' + url,
        message: serverMsg,
        details: err.response.data
      };
    }

    // Format Standard Error JS
    return {
      status: 'CLIENT_ERR',
      url: '',
      message: err.message || String(err),
      details: err.stack || err
    };
  };

  var Logger = {
    /**
     * Dapatkan nama resmi menu yang tampil di web
     */
    getMenuLabel: function (tab, subTab) {
      if (!tab) return 'Menu';
      var key = String(tab).toLowerCase();
      if (key === 'users' && subTab) {
        var subKey = 'users/' + String(subTab).toLowerCase();
        if (MENU_LABELS[subKey]) return MENU_LABELS[subKey];
      }
      return MENU_LABELS[key] || tab;
    },

    /**
     * Dapatkan nama aksi proses dalam Bahasa Indonesia yang lazim di pabrik
     */
    getActionLabel: function (method, path) {
      var p = String(path || '').toLowerCase();
      var m = String(method || 'GET').toUpperCase();

      // Perakitan
      if (p.includes('/assembly/save') || p.includes('/assembly/submit')) return 'Simpan Rakitan Unit';
      if (p.includes('/trays/create') || (p.includes('/trays') && m === 'POST')) return 'Buat Dokumen Tray Baru';
      if (p.includes('/assembly/request-proceed')) return 'Lanjut ke Proses Berikutnya';
      if (p.includes('/assembly/pause')) return 'Jeda Sesi Perakitan';
      if (p.includes('/assembly/resume')) return 'Lanjutkan Sesi Perakitan';
      if (p.includes('/assembly/end-early')) return 'Selesaikan Tray Lebih Awal';

      // Test Functional 1 & 2
      if (p.includes('/tf1') || p.includes('/assy-test') || p.includes('/testing/submit')) return 'Submit Hasil Uji Test Functional 1';
      if (p.includes('/qc/test') || p.includes('/qc/submit')) return 'Submit Hasil Uji Test Functional 2 (QC)';
      if (p.includes('/qc/verify') || p.includes('/verify-tray')) return 'Verifikasi Dokumen QC';

      // Gudang & Material
      if (p.includes('/warehouse/supply') || p.includes('/supply/submit')) return 'Supply Part ke Perakitan';
      if (p.includes('/returns') && m === 'POST') return 'Pengembalian Barang ke Gudang';
      if (p.includes('/warehouse-receipt') || p.includes('/warehouse_receipt') || p.includes('/receipts')) return 'Penerimaan Unit (WH/GR)';
      if (p.includes('/reassembly') && m === 'POST') return 'Simpan Rakit Ulang';

      // Delivery Order & Customer
      if (p.includes('/delivery-order') && m === 'POST') return 'Buat Delivery Order (Surat Jalan)';
      if (p.includes('/customers') && m === 'DELETE') return 'Hapus Data Customer';
      if (p.includes('/customers') && m === 'POST') return 'Simpan Data Customer';

      // Verifikasi & Admin
      if (p.includes('/verify-set') || p.includes('/verify_set')) return 'Persetujuan / Verifikasi Set';
      if (p.includes('/verify-part') || p.includes('/verify_part')) return 'Persetujuan / Verifikasi Part';
      if (p.includes('/verify-replacement')) return 'Persetujuan / Verifikasi Pergantian Part';
      if (p.includes('/downtime')) return 'Simpan Log Downtime';
      if (p.includes('/testing-points') || p.includes('/testing_points')) return 'Update Master Testing Point';

      // Default
      if (m === 'POST') return 'Simpan Data (' + path + ')';
      if (m === 'PUT' || m === 'PATCH') return 'Update Data (' + path + ')';
      if (m === 'DELETE') return 'Hapus Data (' + path + ')';
      return 'Proses Data (' + path + ')';
    },

    /**
     * Inisialisasi awal saat sistem siap
     */
    init: function (systemName, version) {
      console.log(
        '%c SYSTEM %c' + (systemName || 'Final Assembly System') + ' v' + (version || '3.0') + ' - Logger Active ✅',
        badgeStyle('#0284c7', '#ffffff'),
        textStyle(null, true)
      );
    },

    /**
     * Log Navigasi Menu (Menampilkan nama resmi menu website)
     */
    navigate: function (tab, subTab) {
      var label = this.getMenuLabel(tab, subTab);
      console.log(
        '%c MENU %c' + label,
        badgeStyle('#2563eb', '#ffffff'),
        textStyle(null, true)
      );
    },

    /**
     * Log Informasi Event
     */
    info: function (moduleName, message, extraData) {
      var mod = (moduleName || 'INFO').toUpperCase();
      if (extraData !== undefined) {
        console.log(
          '%c ' + mod + ' %c' + message,
          badgeStyle('#2563eb', '#ffffff'),
          textStyle(null, false),
          extraData
        );
      } else {
        console.log(
          '%c ' + mod + ' %c' + message,
          badgeStyle('#2563eb', '#ffffff'),
          textStyle(null, false)
        );
      }
    },

    /**
     * Log Sukses Aksi
     */
    success: function (moduleName, message, extraData) {
      var mod = (moduleName || 'SUKSES').toUpperCase();
      if (extraData !== undefined) {
        console.log(
          '%c ' + mod + ' %c✅ ' + message,
          badgeStyle('#059669', '#ffffff'),
          textStyle('#10b981', true),
          extraData
        );
      } else {
        console.log(
          '%c ' + mod + ' %c✅ ' + message,
          badgeStyle('#059669', '#ffffff'),
          textStyle('#10b981', true)
        );
      }
    },

    /**
     * Log Peringatan
     */
    warn: function (moduleName, message, extraData) {
      var mod = (moduleName || 'PERINGATAN').toUpperCase();
      if (extraData !== undefined) {
        console.warn(
          '%c ' + mod + ' %c⚠️ ' + message,
          badgeStyle('#d97706', '#ffffff'),
          textStyle('#f59e0b', true),
          extraData
        );
      } else {
        console.warn(
          '%c ' + mod + ' %c⚠️ ' + message,
          badgeStyle('#d97706', '#ffffff'),
          textStyle('#f59e0b', true)
        );
      }
    },

    /**
     * Log Error Terstruktur (Dipicu oleh try-catch atau API reject)
     */
    error: function (moduleName, actionDescription, errorObj) {
      var mod = (moduleName || 'ERROR').toUpperCase();
      var parsed = parseError(errorObj);

      var logTitle = '❌ Gagal: ' + actionDescription;
      if (parsed.status) {
        logTitle += ' (' + parsed.status + ')';
      }
      if (parsed.url) {
        logTitle += ' → ' + parsed.url;
      }

      console.error(
        '%c ' + mod + ' %c' + logTitle + '\n   Detail: ' + parsed.message,
        badgeStyle('#e11d48', '#ffffff'),
        textStyle('#fb7185', true),
        parsed.details || errorObj
      );
    },

    /**
     * Log Pemanggilan API Request
     */
    api: function (method, endpoint, payload) {
      console.log(
        '%c PROSES ' + method.toUpperCase() + ' %c' + endpoint,
        badgeStyle('#7c3aed', '#ffffff'),
        textStyle(null, false),
        payload || ''
      );
    }
  };

  return Logger;
});
