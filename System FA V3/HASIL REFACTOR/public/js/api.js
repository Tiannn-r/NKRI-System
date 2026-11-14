/**
 * ============================================================================
 * FINAL ASSEMBLY TRACEABILITY SYSTEM - API CLIENT ENGINE (api.js)
 * ============================================================================
 * Modul komunikasi HTTP terpusat untuk menghubungkan frontend ke REST API backend.
 *
 * Fitur:
 * - Otomatis menyertakan Bearer Token & CSRF Header
 * - Penanganan otomatis jika sesi login habis (401 Unauthorized → redirect login)
 * - Auto-retry cerdas jika reverse proxy (Ngrok/Cloudflare) mengalami glitch 502/503/504
 * - Integrasi otomatis ke logger.js untuk audit log & pelaporan error terstruktur
 * ============================================================================
 */

(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    var apiInstance = factory();
    root.api = apiInstance;
    root.fetchAPI = apiInstance.fetchAPI.bind(apiInstance); // Global alias kompatibilitas
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  var ApiClient = {
    /**
     * Dapatkan modul bisnis berdasarkan URL endpoint
     */
    getModuleFromPath: function (path) {
      if (!path) return 'SISTEM';
      var p = String(path).toLowerCase();
      if (p.includes('assembly')) return 'PERAKITAN';
      if (p.includes('tf1') || p.includes('functional') || p.includes('assy-test')) return 'TF1';
      if (p.includes('qc') || p.includes('tf2')) return 'TF2/QC';
      if (p.includes('warehouse') || p.includes('receipts') || p.includes('supply') || p.includes('returns')) return 'GUDANG';
      if (p.includes('report') || p.includes('laporan')) return 'LAPORAN';
      if (p.includes('delivery')) return 'DELIVERY';
      if (p.includes('hold')) return 'HOLD';
      if (p.includes('traveler')) return 'TRAVELER';
      if (p.includes('admin') || p.includes('user') || p.includes('audit') || p.includes('downtime')) return 'ADMIN';
      return 'API';
    },

    /**
     * Mesin pemanggil HTTP utama dengan auto-retry dan penanganan error
     */
    fetchAPI: async function (path, options, retryCount) {
      var self = this;
      options = options || {};
      retryCount = retryCount || 0;

      var token = localStorage.getItem('token');
      if (!options.headers) options.headers = {};
      options.headers['Content-Type'] = 'application/json';
      options.headers['Accept'] = 'application/json';
      options.headers['ngrok-skip-browser-warning'] = 'true';
      if (token) {
        options.headers['Authorization'] = 'Bearer ' + token;
      }

      var actUser = localStorage.getItem('username') || '';
      var actFull = localStorage.getItem('full_name') || '';
      var actRole = localStorage.getItem('role') || '';
      if (actUser) options.headers['X-Actor-Username'] = actUser;
      if (actFull) options.headers['X-Actor-Fullname'] = encodeURIComponent(actFull);
      if (actRole) options.headers['X-Actor-Role'] = actRole;

      var method = (options.method || 'GET').toUpperCase();
      var cleanPath = String(path).startsWith('/') ? path : '/' + path;
      var url = '/api' + cleanPath;
      var modName = self.getModuleFromPath(cleanPath);

      var actionLabel = '';
      if (typeof window !== 'undefined' && window.logger && typeof window.logger.getActionLabel === 'function') {
        actionLabel = window.logger.getActionLabel(method, cleanPath);
      } else {
        actionLabel = method + ' ' + url;
      }

      var res;
      try {
        res = await fetch(url, options);
      } catch (networkErr) {
        // Auto-retry untuk GET request jika terjadi transient network glitch
        if (retryCount < 1 && method === 'GET') {
          await new Promise(function (r) { setTimeout(r, 600); });
          return self.fetchAPI(path, options, retryCount + 1);
        }

        if (typeof window !== 'undefined' && window.logger) {
          window.logger.error(modName, actionLabel + ' (Koneksi Putus)', networkErr);
        }
        throw new Error('Gagal terhubung ke server backend. Pastikan server aktif dan koneksi stabil.');
      }

      // 401: Sesi login berakhir
      if (res.status === 401) {
        if (typeof window !== 'undefined' && window.logger) {
          window.logger.warn('AUTH', 'Sesi login telah berakhir, mengarahkan ke halaman login...');
        }
        localStorage.removeItem('token');
        setTimeout(function () {
          window.location.replace('/login?expired=1');
        }, 800);
        throw new Error('Sesi habis, silakan login kembali.');
      }

      // Auto-retry untuk 502/503/504 gateway glitches
      if ((res.status === 502 || res.status === 503 || res.status === 504) && retryCount < 1 && method === 'GET') {
        await new Promise(function (r) { setTimeout(r, 800); });
        return self.fetchAPI(path, options, retryCount + 1);
      }

      var contentType = res.headers.get('content-type') || '';

      // Penanganan jika server merespons non-JSON (HTML error page, 500 fatal crash)
      if (!contentType.includes('application/json')) {
        var text = '';
        try { text = await res.text(); } catch (_) { }

        if (!res.ok || res.redirected) {
          var errorMsg = 'Koneksi server bermasalah (' + res.status + ')';
          if (res.status === 404) errorMsg = 'Endpoint API tidak ditemukan (404): ' + cleanPath;
          if (res.status >= 500) errorMsg = 'Server backend sedang mengalami kendala (' + res.status + ')';

          if (typeof window !== 'undefined' && window.logger) {
            window.logger.error(modName, actionLabel, {
              status: res.status,
              url: method + ' ' + url,
              message: errorMsg,
              details: text
            });
          }
          throw new Error(errorMsg);
        }

        return { ok: true, data: text };
      }

      // Parse JSON response
      try {
        var json = await res.json();

        // Tangkap respon gagal dari backend (misal status 422/400/500 atau json.success === false)
        if (!res.ok || (json && json.success === false)) {
          var backendMsg = (json && (json.message || json.error)) || ('HTTP ' + res.status);
          if (typeof window !== 'undefined' && window.logger) {
            window.logger.error(modName, actionLabel, {
              status: res.status,
              url: method + ' ' + url,
              message: backendMsg,
              details: json
            });
          }
        } else if (method !== 'GET') {
          // Log aksi mutasi data yang berhasil
          if (typeof window !== 'undefined' && window.logger) {
            var successMsg = (json && json.message) || (actionLabel + ' berhasil');
            window.logger.success(modName, successMsg);
          }
        }

        return json;
      } catch (jsonErr) {
        if (!res.ok) {
          throw new Error('Kesalahan Server (' + res.status + '): Format data tidak dapat dibaca.');
        }
        throw new Error('Gagal memproses data server: Format JSON tidak valid.');
      }
    },

    /**
     * Shortcut HTTP GET
     */
    get: function (path, options) {
      options = options || {};
      options.method = 'GET';
      return this.fetchAPI(path, options);
    },

    /**
     * Shortcut HTTP POST
     */
    post: function (path, data, options) {
      options = options || {};
      options.method = 'POST';
      if (data !== undefined) {
        options.body = JSON.stringify(data);
      }
      return this.fetchAPI(path, options);
    },

    /**
     * Shortcut HTTP PUT
     */
    put: function (path, data, options) {
      options = options || {};
      options.method = 'PUT';
      if (data !== undefined) {
        options.body = JSON.stringify(data);
      }
      return this.fetchAPI(path, options);
    },

    /**
     * Shortcut HTTP PATCH
     */
    patch: function (path, data, options) {
      options = options || {};
      options.method = 'PATCH';
      if (data !== undefined) {
        options.body = JSON.stringify(data);
      }
      return this.fetchAPI(path, options);
    },

    /**
     * Shortcut HTTP DELETE
     */
    delete: function (path, options) {
      options = options || {};
      options.method = 'DELETE';
      return this.fetchAPI(path, options);
    }
  };

  return ApiClient;
});

