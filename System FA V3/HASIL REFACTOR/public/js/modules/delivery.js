/**
 * Delivery Order (DO) & Packing List (PL) Module
 * Sistem Final Assembly & Traceability
 *
 * Refactored: Modular Separation (Fase 2)
 */
(function (window) {
  'use strict';

  window.createDeliveryModule = function (ctx) {
    const { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } = Vue;

    // External dependencies provided by app context
    const currentTab = ctx.currentTab;
    const fetchAPI = ctx.fetchAPI || window.api?.fetchAPI;
    const triggerToast = ctx.triggerToast || function (title, msg, type) {
      if (window.Swal) Swal.fire({ title: title, text: msg, icon: type || 'success' });
      else alert((title || '') + ': ' + (msg || ''));
    };
    const showConfirmCard = ctx.showConfirmCard || function (opts) {
      opts = opts || {};
      return confirm(opts.message || 'Apakah Anda yakin?');
    };
    const pgState = ctx.pgState || reactive({
      doHistory: { page: 1, size: 10 },
      labelsHistory: { page: 1, size: 10 }
    });
    const userRole = ctx.userRole || ref('');
    const operatorName = ctx.operatorName || ref('Operator');
    const userFullName = ctx.userFullName || ref('');
    const getNowWIBDate = ctx.getNowWIBDate || function () { return new Date(); };
    const getNowWIBDateString = ctx.getNowWIBDateString || function () {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, '0');
      const d = String(now.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    };
    const getYearMonthFromDate = ctx.getYearMonthFromDate || function (dateVal) {
      if (dateVal && typeof dateVal === 'string') {
        const s = dateVal.trim();
        const iso = s.match(/^(\d{4})-(\d{1,2})/);
        if (iso) {
          return {
            yy: iso[1].substring(2),
            mm: iso[2].padStart(2, '0'),
            yyyy: iso[1]
          };
        }
        const dmy = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
        if (dmy) {
          return {
            yy: dmy[3].substring(2),
            mm: dmy[2].padStart(2, '0'),
            yyyy: dmy[3]
          };
        }
      }
      const now = dateVal ? new Date(dateVal) : new Date();
      const yr = String(now.getFullYear());
      return {
        yy: yr.substring(2),
        mm: String(now.getMonth() + 1).padStart(2, '0'),
        yyyy: yr
      };
    };

    // Helper for formatting Indonesian date
    const formatIndoDate = (dt) => {
      if (window.utils && typeof window.utils.formatIndoDate === 'function') {
        return window.utils.formatIndoDate(dt);
      }
      if (!dt) return '-';
      const d = new Date(dt);
      if (isNaN(d.getTime())) return String(dt);
      const day = String(d.getDate()).padStart(2, '0');
      const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
    };

    // Helper for formatting Indonesian datetime in Excel export
    const formatIndoDateTime = (dt) => {
      if (window.utils && typeof window.utils.formatDateTime === 'function') {
        return window.utils.formatDateTime(dt);
      }
      if (!dt) return '-';
      const d = new Date(dt);
      if (isNaN(d.getTime())) return '-';
      const day = String(d.getDate()).padStart(2, '0');
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      const month = months[d.getMonth()];
      const year = d.getFullYear();
      const hours = String(d.getHours()).padStart(2, '0');
      const minutes = String(d.getMinutes()).padStart(2, '0');
      return day + ' ' + month + ' ' + year + ', ' + hours + ':' + minutes + ' WIB';
    };

    // ===================================================================
    // EXCEL EXPORT (DELIVERY ORDER)
    // ===================================================================
        const exportDeliveryOrderExcel = async (specificDOId = null) => {
          try {
            let url = '/api/delivery-orders/export-data';
            const params = [];
            if (specificDOId) {
              params.push(`do_id=${encodeURIComponent(specificDOId)}`);
            } else {
              if (doDocFilter && doDocFilter.value) {
                params.push(`doc_number=${encodeURIComponent(doDocFilter.value.trim())}`);
              } else if (doSearchText && doSearchText.value && doSearchText.value.trim()) {
                params.push(`doc_number=${encodeURIComponent(doSearchText.value.trim())}`);
              }
              if (doStartDate && doStartDate.value) {
                params.push(`start_date=${encodeURIComponent(doStartDate.value)}`);
              }
              if (doEndDate && doEndDate.value) {
                params.push(`end_date=${encodeURIComponent(doEndDate.value)}`);
              }
            }
            if (params.length > 0) {
              url += '?' + params.join('&');
            }

            const token = localStorage.getItem('token');
            const res = await fetch(url, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await res.json();
            if (!result.ok) {
              throw new Error(result.error || 'Gagal mengambil data DO untuk export');
            }

            const rows = result.data || [];
            if (!rows.length) {
              triggerToast('Export Excel DO', 'Tidak ada data DO yang dapat diexport!', 'warn');
              return;
            }

            const d = new Date();
            const dateStr = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
            const fileName = specificDOId ? `Laporan_DO_${(rows[0]?.do_number || String(specificDOId)).replace(/[\/:*?"<>|\\]/g, '_')}.xlsx` : `Laporan_Delivery_Orders_${dateStr}.xlsx`;

            if (typeof ExcelJS === 'undefined') {
              const simpleRows = rows.map((r, idx) => ({
                'No': idx + 1,
                'Tanggal': r.do_date || '-',
                'Doc DO': r.do_number || '-',
                'Serial Number': r.serial_number || '-',
                'Part 12': r.part12_sn || '-',
                'Part 1': r.part1_sn || '-',
                'Dikeluarkan Oleh': r.issued_by || '-',
                'Status Pengiriman': r.delivery_status || '-',
                'Penerima': r.customer_name || '-',
                'Alamat Penerima': r.customer_address || '-'
              }));
              const worksheet = XLSX.utils.json_to_sheet(simpleRows);
              const workbook = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan_DO');
              XLSX.writeFile(workbook, fileName);
              triggerToast('Berhasil', `File ${fileName} berhasil didownload (${rows.length} baris data)!`, 'success');
              return;
            }

            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'PT NKRI - Sistem Assembly & Traceability';
            workbook.created = new Date();

            const sheet = workbook.addWorksheet('Delivery_Orders', {
              pageSetup: {
                paperSize: 9, // A4
                orientation: 'landscape',
                fitToPage: true,
                fitToWidth: 1,
                fitToHeight: 0,
                showGridLines: true
              }
            });

            // Title Banner
            const titleRow = sheet.addRow(['LAPORAN DELIVERY ORDER / SURAT JALAN DISTRIBUSI P9']);
            titleRow.font = { name: 'Arial', size: 14, bold: true, color: { argb: 'FF991B1B' } };
            titleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 34;
            sheet.mergeCells(1, 1, 1, 10);

            // Subtitle
            const subTitleRow = sheet.addRow([`Dicetak pada: ${formatIndoDateTime(new Date())} | Total Data: ${rows.length} Unit SN`]);
            subTitleRow.font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF64748B' } };
            subTitleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            subTitleRow.height = 20;
            sheet.mergeCells(2, 1, 2, 10);

            sheet.addRow([]); // Spacer row

            // Columns Header: No | Tanggal | Doc DO | Serial Number | Part 12 | Part 1 | Dikeluarkan Oleh | Status Pengiriman | Penerima | Alamat Penerima
            const headers = [
              'No', 'Tanggal', 'Doc DO', 'Serial Number', 'Part 12', 'Part 1',
              'Dikeluarkan Oleh', 'Status Pengiriman', 'Penerima', 'Alamat Penerima'
            ];
            const headerRow = sheet.addRow(headers);
            headerRow.height = 26;
            headerRow.eachCell((cell) => {
              cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDC2626' } };
              cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
              cell.border = {
                top: { style: 'thin', color: { argb: 'FFB91C1C' } },
                bottom: { style: 'medium', color: { argb: 'FF991B1B' } },
                left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
              };
            });

            // Data Rows
            rows.forEach((r, idx) => {
              const rowValues = [
                idx + 1,
                r.do_date || '-',
                r.do_number || '-',
                r.serial_number || '-',
                r.part12_sn || '-',
                r.part1_sn || '-',
                r.issued_by || '-',
                r.delivery_status || '-',
                r.customer_name || '-',
                (r.customer_address || '-').replace(/\n/g, ', ')
              ];
              const dRow = sheet.addRow(rowValues);
              dRow.height = 22;
              dRow.eachCell((cell, colNumber) => {
                cell.font = { name: 'Arial', size: 9.5, color: { argb: 'FF1E293B' } };
                if (colNumber === 1 || colNumber === 2 || colNumber === 8) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (colNumber === 3 || colNumber === 4 || colNumber === 5 || colNumber === 6) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0F172A' } };
                } else {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                }
                if (idx % 2 === 1) {
                  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                }
                cell.border = {
                  top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                };
              });
            });

            // Column Widths
            const colWidths = [6, 14, 22, 16, 14, 14, 20, 18, 22, 34];
            colWidths.forEach((w, i) => {
              sheet.getColumn(i + 1).width = w;
            });

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const fileUrl = window.URL.createObjectURL(blob);
            const aLink = document.createElement('a');
            aLink.href = fileUrl;
            aLink.download = fileName;
            document.body.appendChild(aLink);
            aLink.click();
            setTimeout(() => { document.body.removeChild(aLink); window.URL.revokeObjectURL(fileUrl); }, 250);

            triggerToast('Berhasil', `File ${fileName} berhasil diexport (${rows.length} baris data)!`, 'success');
          } catch (err) {
            triggerToast('Gagal Export', err.message, 'error');
          }
        };

    // ===================================================================
    // CORE DELIVERY ORDER & PACKING LIST MODULE
    // ===================================================================
        // MODULE: DELIVERY ORDER (DO) & PACKING LIST (PL) & PELABELAN
        // ===================================================================
        const nkriEmblemBase64 = 'data:image/png;base64,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';
        const nkriLogoBase64 = 'data:image/png;base64,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';
        const nkriLabelLogoBase64 = 'data:image/png;base64,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';
        const doSubTab = ref(ctx.initialSubTab || 'dashboard'); // 'dashboard', 'customers', 'packing_list', 'surat_jalan', 'preview', 'detail', 'settings'
        const doTableDisplayMode = ref('pdf_exact'); // 'pdf_exact' (4 rows) or 'all_crates'
        const doSJCurrentPage = ref(1);

        // ===================================================================
        // 1. DASHBOARD DELIVERY ORDER (DO) STATS & CHART
        // ===================================================================
        const doDashboardStats = ref({
          total_assembled_sets: 0,
          total_assembled_parts: 0,
          ready_to_ship_sets: 0,
          ready_to_ship_parts: 0,
          shipped_sets: 0,
          shipped_parts: 0,
          quality: {
            good_sets: 0,
            good_parts: 0,
            hold_sets: 0,
            hold_parts: 0,
            scrap_sets: 0,
            scrap_parts: 0
          },
          summary: {
            total_do: 0,
            total_pl: 0,
            total_customers: 0
          }
        });
        const isLoadingDoStats = ref(false);
        let doQualityChartInstance = null;

        const renderDoQualityChart = () => {
          nextTick(() => {
            const canvas = document.getElementById('doQualityChartCanvas');
            if (!canvas) return;
            if (typeof Chart === 'undefined') {
              setTimeout(renderDoQualityChart, 250);
              return;
            }
            if (doQualityChartInstance) {
              try { doQualityChartInstance.destroy(); } catch (_) {}
              doQualityChartInstance = null;
            }
            const q = doDashboardStats.value.quality || {};
            const good = q.good_sets || 0;
            const hold = q.hold_sets || 0;
            const scrap = q.scrap_sets || 0;
            const total = good + hold + scrap;

            doQualityChartInstance = new Chart(canvas, {
              type: 'doughnut',
              data: {
                labels: ['Good (Lolos QC)', 'Hold (Tertahan)', 'Scrap (Afkir)'],
                datasets: [{
                  data: total > 0 ? [good, hold, scrap] : [1, 0, 0],
                  backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                  borderWidth: 2,
                  borderColor: '#ffffff',
                  hoverOffset: 6
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                  legend: {
                    position: 'bottom',
                    labels: {
                      boxWidth: 12,
                      padding: 14,
                      font: { family: 'Roboto', size: 11, weight: 'bold' }
                    }
                  },
                  tooltip: {
                    callbacks: {
                      label: function(context) {
                        const val = context.raw || 0;
                        const parts = val * 3;
                        return ` ${context.label}: ${val} Set (${parts} Part)`;
                      }
                    }
                  }
                }
              }
            });
          });
        };

        const loadDoDashboardStats = async () => {
          isLoadingDoStats.value = true;
          try {
            const res = await fetchAPI('/delivery-orders/dashboard-stats');
            if (res && res.ok && res.data) {
              doDashboardStats.value = res.data;
              renderDoQualityChart();
            }
          } catch (err) {
            console.error('Failed to load DO dashboard stats:', err);
          } finally {
            isLoadingDoStats.value = false;
          }
        };

        // ===================================================================
        // 2. PACKING LIST (PL) INNER STATE & UNIK ID CONTROLS
        // ===================================================================
        const packingListInnerTab = ref('form'); // 'form' or 'history'
        const plMode = ref(''); // '' (belum dipilih), 'realtime' or 'backdate'
        const plActiveStep = ref(0); // 0 = closed, 1 = parameters, 2 = allocation
        const plDeliverySeq = ref(1);
        const plGeneratedId = ref('');
        const plDuplicateWarning = ref(false);
        const plDuplicateMessage = ref('');
        const isCheckingPlUnique = ref(false);

        const selectPlMode = (mode) => {
          if (plMode.value) return;
          plMode.value = mode;
          plActiveStep.value = 1;
          if (mode === 'realtime') {
            labelForm.value.date = getNowWIBDateString();
            loadStandaloneLabels();
          }
          updateGeneratedPLNumber();
        };

        const isPlCardOpen = (step) => {
          return plActiveStep.value === step;
        };

        const goToPlStep = (targetStep) => {
          if (targetStep === 2) {
            if (plDuplicateWarning.value) {
              triggerToast('Peringatan ID Duplikat', 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.', 'error');
              return;
            }
            if (!labelForm.value.package_count || labelForm.value.package_count < 1) {
              triggerToast('Data Belum Lengkap', 'Jumlah kemasan harus minimal 1.', 'warn');
              return;
            }
            if (!labelForm.value.batch_number || labelForm.value.batch_number < 1) {
              triggerToast('Data Belum Lengkap', 'Nomor batch pengiriman harus minimal 1.', 'warn');
              return;
            }
            if (!plDeliverySeq.value || plDeliverySeq.value < 1) {
              triggerToast('Data Belum Lengkap', 'Nomor urut pengiriman harus minimal 1.', 'warn');
              return;
            }
            syncLabelPackagingStructure();
          }
          plActiveStep.value = targetStep;
        };

        const onPlHeaderClick = (step) => {
          // Strictly locked: Perpindahan form hanya melalui tombol Lanjut & Kembali
        };

        // ===================================================================
        // 3. SURAT JALAN (SJ) INNER STATE & UNIK ID CONTROLS
        // ===================================================================
        const suratJalanInnerTab = ref('form'); // 'form' or 'history'
        const sjMode = ref(''); // '' (belum dipilih), 'realtime' or 'backdate'
        const sjDeliverySeq = ref(1);
        const sjGeneratedId = ref('');
        const sjDuplicateWarning = ref(false);
        const sjDuplicateMessage = ref('');
        const isCheckingSjUnique = ref(false);

        const selectSjMode = (mode) => {
          if (sjMode.value) return;
          sjMode.value = mode;
          if (mode === 'realtime') {
            doForm.value.do_date = getTodayISODate();
            loadNextDOMeta();
          }
          updateGeneratedSJNumber();
          goToFormStep('customer');
        };

        // Navigation Dispatcher for Delivery Order Sub-tabs
        const setDoSubTab = (tab) => {
          if (tab === 'label_pl_only') {
            doSubTab.value = 'packing_list';
            packingListInnerTab.value = 'form';
          } else if (tab === 'labels_history') {
            doSubTab.value = 'packing_list';
            packingListInnerTab.value = 'history';
          } else if (tab === 'form') {
            doSubTab.value = 'surat_jalan';
            suratJalanInnerTab.value = 'form';
          } else if (tab === 'history') {
            doSubTab.value = 'surat_jalan';
            suratJalanInnerTab.value = 'history';
          } else {
            doSubTab.value = tab;
          }

          const subUrls = {
            'packing_list': '/packing-list',
            'label_pl_only': '/packing-list',
            'labels_history': '/packing-list',
            'surat_jalan': '/surat-jalan',
            'form': '/surat-jalan',
            'history': '/surat-jalan',
            'customers': '/database-customer',
            'database_do': '/database-customer',
            'dashboard': '/delivery'
          };
          const targetUrl = subUrls[doSubTab.value] || '/delivery';
          if (window.history && window.history.replaceState && window.location.pathname !== targetUrl) {
            try {
              window.history.replaceState(
                { type: 'tab', key: 'delivery_order_' + doSubTab.value, tab: 'delivery_order', subTab: doSubTab.value },
                '',
                targetUrl
              );
            } catch (_) {}
          }

          if (doSubTab.value === 'dashboard') {
            loadDoDashboardStats();
          } else if (doSubTab.value === 'customers') {
            loadCustomers();
          } else if (doSubTab.value === 'packing_list') {
            loadStandaloneLabels();
            if (typeof syncLabelPackagingStructure === 'function') syncLabelPackagingStructure();
            if (typeof loadAvailableFinishedSNs === 'function') loadAvailableFinishedSNs();
            if (typeof updateGeneratedPLNumber === 'function') updateGeneratedPLNumber();
          } else if (doSubTab.value === 'surat_jalan') {
            loadDOList();
            if (typeof loadAvailableFinishedSNs === 'function') loadAvailableFinishedSNs();
            if (typeof updateGeneratedSJNumber === 'function') updateGeneratedSJNumber();
          }
        };

        // Reactive watcher to keep browser URL synced with doSubTab changes
        watch(doSubTab, (newVal) => {
          if (currentTab && currentTab.value !== 'delivery_order') return;
          const subUrls = {
            'packing_list': '/packing-list',
            'label_pl_only': '/packing-list',
            'labels_history': '/packing-list',
            'surat_jalan': '/surat-jalan',
            'form': '/surat-jalan',
            'history': '/surat-jalan',
            'customers': '/database-customer',
            'database_do': '/database-customer',
            'dashboard': '/delivery'
          };
          const targetUrl = subUrls[newVal] || '/delivery';
          if (window.history && window.history.replaceState && window.location.pathname !== targetUrl) {
            try {
              window.history.replaceState(
                { type: 'tab', key: 'delivery_order_' + newVal, tab: 'delivery_order', subTab: newVal },
                '',
                targetUrl
              );
            } catch (_) {}
          }
        });

        const getSuratJalanVisibleCrates = () => {
          if (!previewDOData.value || !previewDOData.value.crates || previewDOData.value.crates.length === 0) {
            return [{}, {}, {}, {}];
          }
          if (doTableDisplayMode.value === 'all_crates') {
            return previewDOData.value.crates;
          }
          // 'pdf_exact' mode: 4 crates per page matching PDF 1 exactly
          const start = (doSJCurrentPage.value - 1) * 4;
          const sliced = previewDOData.value.crates.slice(start, start + 4);
          // Pad with empty objects if fewer than 4 to preserve exact table height
          while (sliced.length < 4) {
            sliced.push({});
          }
          return sliced;
        }; // 'preview', 'form', 'history', 'settings', 'customers', 'detail'
        const doSettings = ref({
          company_name: 'PT Nanggala Kencana Rekatama Indonesia',
          company_logo: '',
          head_office: 'Jalan Mendawai I No. 11 Kramat Pela, Jakarta Selatan, 12130',
          factory_address: 'Jalan Gempol No. 9, Mekar Mulya, Bandung, 40292',
          contact_email: 'info@nanggala.co.id',
          tracking_base_url: '',
          auto_base_url: '',
          default_customer_name: 'KEMHAN RI',
          default_customer_address: 'Jl. Tanah Abang Timur No. 6 - 7, Jakarta, 10110, Indonesia',
          default_contract_number: 'TRAK/2509/APBN/XI/2025/KEMHAN',
          default_program_name: 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
          default_issuer_name: 'Rezka Rachdana',
          default_issuer_title: 'Staf',
          do_prefix: '/SJ/PPIC/',
          pl_prefix: '/PL/PPIC/',
          crate_prefix: '2610-',
          crate_capacity: 20,
          box_capacity: 10,
          do_sequence_offset: 4,
          do_offset_month: '10/2026',
          cloudinary: {
            configured: false,
            cloud_name: '',
            folder: 'nkri-pl',
            api_key_masked: '',
            has_secret: false,
            base_delivery_url: ''
          }
        });
        const isSavingDOSettings = ref(false);
        const cloudForm = ref({
          cloud_name: '',
          api_key: '',
          api_secret: '',
          folder: 'nkri-pl'
        });
        const showCloudSecret = ref(false);
        const isTestingCloud = ref(false);
        const cloudTestResult = ref(null);
        const isSavingCloudSettings = ref(false);
        const isUploadingCloudPdf = ref({});
        const isBatchSyncingCloud = ref(false);
        const batchSyncProgress = ref({ total: 0, current: 0, success: 0, fail: 0 });

        // ===================================================================
        // STANDALONE PACKING LABELS (LABEL PACKING LIST MANDIRI)
        // ===================================================================
        const standaloneLabelsList = ref([]);
        const labelSearchText = ref('');
        const labelDocFilter = ref('');
        const labelPackageFilter = ref('');
        const labelStartDate = ref('');
        const labelEndDate = ref('');


        const getLabelPackagingIds = (lbl) => {
          if (!lbl) return [];
          if (Array.isArray(lbl.packaging_ids) && lbl.packaging_ids.length > 0) {
            return lbl.packaging_ids;
          }
          let crates = [];
          if (Array.isArray(lbl.crates) && lbl.crates.length > 0) {
            crates = lbl.crates;
          } else if (typeof lbl.crates_json === 'string') {
            try { crates = JSON.parse(lbl.crates_json); } catch (_) { crates = []; }
          }
          const ids = [];
          crates.forEach(c => {
            if (lbl.package_type === 'crate') {
              if (c.crate_id) ids.push(c.crate_id);
            } else {
              const bId = c.box_id || (c.boxes && c.boxes[0] && c.boxes[0].box_id);
              if (bId) ids.push(bId);
            }
          });
          if (ids.length === 0 && lbl.pl_number) {
            ids.push(lbl.pl_number);
          }
          return ids;
        };

        const formatLabelDateOnly = (dt) => {
          if (!dt) return '-';
          const s = String(dt);
          return s.length >= 10 ? s.substring(0, 10) : s;
        };

        const isLabelBackdate = (lbl) => {
          if (!lbl) return false;
          if (lbl.pl_mode === 'backdate' || lbl.is_backdate === 1 || lbl.is_backdate === true || lbl.is_backdate === '1') {
            return true;
          }
          if (lbl.pl_mode === 'realtime') {
            return false;
          }
          if (lbl.label_date && lbl.created_at) {
            const lDate = String(lbl.label_date).substring(0, 10);
            const cDate = String(lbl.created_at).substring(0, 10);
            return lDate < cDate;
          }
          return false;
        };

        const isDOBackdate = (item) => {
          if (!item) return false;
          if (item.sj_mode === 'backdate' || item.is_backdate === 1 || item.is_backdate === true || item.is_backdate === '1') {
            return true;
          }
          if (item.sj_mode === 'realtime') {
            return false;
          }
          if (item.do_date && item.created_at) {
            const dDate = String(item.do_date).substring(0, 10);
            const cDate = String(item.created_at).substring(0, 10);
            return dDate < cDate;
          }
          return false;
        };

        const distinctPLLabelNumbers = computed(() => {
          const set = new Set();
          (standaloneLabelsList.value || []).forEach(lbl => {
            const ids = getLabelPackagingIds(lbl);
            ids.forEach(id => set.add(id));
            if (lbl.pl_number) set.add(lbl.pl_number);
          });
          return Array.from(set).filter(Boolean).sort();
        });

        const resetLabelFilter = () => {
          labelSearchText.value = '';
          labelDocFilter.value = '';
          labelPackageFilter.value = '';
          labelStartDate.value = '';
          labelEndDate.value = '';
          if (pgState.labelsHistory) pgState.labelsHistory.page = 1;
        };

        const getLabelDateKey = (lbl) => {
          if (!lbl) return '';
          if (lbl.created_at) {
            const s = String(lbl.created_at).trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
              return s.substring(0, 10);
            }
          }
          return '';
        };

        const isLoadingStandaloneLabels = ref(false);
        const isSavingStandaloneLabel = ref(false);
        const activeLabelCrateIndex = ref(0);
        const labelCratePage = ref(1);
        const labelCratesPerPage = ref(10); // 10 Dus / Peti per halaman stepper

        const totalLabelCratePages = computed(() => {
          const count = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates.length : 0;
          return Math.max(1, Math.ceil(count / labelCratesPerPage.value));
        });

        const pagedLabelCrates = computed(() => {
          const list = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates : [];
          const start = (labelCratePage.value - 1) * labelCratesPerPage.value;
          return list.slice(start, start + labelCratesPerPage.value).map((c, idx) => ({
            crate: c,
            originalIndex: start + idx
          }));
        });

        const quickJumpTarget = ref('');

        const setLabelCratePage = (pg) => {
          const p = parseInt(pg, 10);
          if (!isNaN(p) && p >= 1 && p <= totalLabelCratePages.value) {
            labelCratePage.value = p;
          }
        };

        const onLabelCratePageInput = (e) => {
          let p = parseInt(e.target.value, 10);
          if (isNaN(p) || p < 1) p = 1;
          if (p > totalLabelCratePages.value) p = totalLabelCratePages.value;
          setLabelCratePage(p);
          e.target.value = p;
        };

        const prevLabelCratePage = () => {
          if (labelCratePage.value > 1) {
            labelCratePage.value--;
          }
        };

        const nextLabelCratePage = () => {
          if (labelCratePage.value < totalLabelCratePages.value) {
            labelCratePage.value++;
          }
        };

        const selectLabelCrate = (cIdx) => {
          activeLabelCrateIndex.value = cIdx;
          const targetPg = Math.floor(cIdx / labelCratesPerPage.value) + 1;
          if (labelCratePage.value !== targetPg) {
            labelCratePage.value = targetPg;
          }
        };

        const jumpToLabelCrate = (targetNum) => {
          const num = parseInt(targetNum, 10);
          const total = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates.length : 0;
          if (!isNaN(num) && num >= 1 && num <= total) {
            selectLabelCrate(num - 1);
            quickJumpTarget.value = '';
          }
        };

        const prevLabelCrate = () => {
          if (activeLabelCrateIndex.value > 0) {
            selectLabelCrate(activeLabelCrateIndex.value - 1);
          }
        };

        const nextLabelCrate = () => {
          const maxIdx = ((labelForm.value && labelForm.value.crates) ? labelForm.value.crates.length : 1) - 1;
          if (activeLabelCrateIndex.value < maxIdx) {
            selectLabelCrate(activeLabelCrateIndex.value + 1);
          }
        };
        const lastSavedPLInfo = ref(null);
        const labelForm = ref({
          date: getNowWIBDateString(),
          package_type: 'crate',
          package_count: 1,
          batch_number: 1,
          product_name: 'Nanggala P9',
          notes: '',
          crates: []
        });

        const previewDOId = ref(null);
        const previewDocType = ref('surat_jalan'); // 'surat_jalan', 'packing_list', 'label_peti', 'label_dus'
        const previewCrateIndex = ref(0);
        const previewDOData = ref(null);
        const isLoadingPreviewDO = ref(false);

        const previewCurrentCrate = computed(() => {
          if (!previewDOData.value || !previewDOData.value.crates || previewDOData.value.crates.length === 0) {
            return {};
          }
          const idx = Math.min(Math.max(0, previewCrateIndex.value), previewDOData.value.crates.length - 1);
          return previewDOData.value.crates[idx] || {};
        });

        const formatDocDate = (dt) => {
          if (!dt) return '-';
          if (typeof dt === 'string') {
            const clean = dt.split('T')[0];
            if (/^\d{4}-\d{2}-\d{2}$/.test(clean)) return clean;
          }
          const d = new Date(dt);
          if (isNaN(d.getTime())) return String(dt).split('T')[0] || String(dt);
          const y = d.getFullYear();
          const m = String(d.getMonth() + 1).padStart(2, '0');
          const day = String(d.getDate()).padStart(2, '0');
          return `${y}-${m}-${day}`;
        };

        const buildPackingListSheets = (doData) => {
          if (!doData || !doData.crates || doData.crates.length === 0) return [];
          const isBoxType = doData.package_type === 'box';

          if (isBoxType) {
            // KEMASAN PER DUS:
            // Setiap item di doData.crates merepresentasikan 1 Dus.
            // 1 Lembar A4 dapat memuat hingga 4 tabel Dus (grid 2x2).
            // Tabel disesuaikan HANYA sebanyak dus yang dikirim (tidak ada tabel kosong / No. Pak: -).
            const allBoxes = [];
            doData.crates.forEach((c, cIdx) => {
              const bList = (c.boxes && c.boxes.length > 0) ? c.boxes : [c];
              bList.forEach(b => {
                allBoxes.push({
                  box_id: b.box_id || c.box_id || '',
                  items: b.items || []
                });
              });
            });

            const pageSize = 4;
            const totalSheets = Math.max(1, Math.ceil(allBoxes.length / pageSize));
            const sheets = [];

            for (let s = 0; s < totalSheets; s++) {
              const slice = allBoxes.slice(s * pageSize, (s + 1) * pageSize);
              const tables = slice.map((box, idx) => {
                const globalIdx = s * pageSize + idx + 1;
                return {
                  title: `Dus ${globalIdx}`,
                  box_id: box.box_id,
                  items: box.items || []
                };
              });

              sheets.push({
                sheetIndex: s + 1,
                totalSheets: totalSheets,
                packageType: 'box',
                containerLabel: 'ID Dus',
                containerId: tables.map(t => t.box_id).filter(Boolean).join(' & ') || (doData.crates[0]?.box_id || '-'),
                tables: tables,
                crate1: doData.crates[0] || null,
                crate2: null
              });
            }
            return sheets;
          } else {
            // KEMASAN PER PETI (crate):
            // 1 Peti = 2 Dus (Peti X - Dus 1 & Peti X - Dus 2).
            // 1 Lembar A4 memuat hingga 2 Peti (= 4 Dus).
            const crates = doData.crates;
            const totalSheets = Math.max(1, Math.ceil(crates.length / 2));
            const sheets = [];

            for (let s = 0; s < totalSheets; s++) {
              const c1 = crates[s * 2];
              const c2 = crates[s * 2 + 1] || null;
              const c1Num = s * 2 + 1;
              const c2Num = s * 2 + 2;

              const tables = [];
              if (c1) {
                tables.push({
                  title: `Peti ${c1Num} - Dus 1`,
                  box_id: c1.boxes?.[0]?.box_id || (c1.crate_id ? `${c1.crate_id}-01` : ''),
                  items: c1.boxes?.[0]?.items || []
                });
                tables.push({
                  title: `Peti ${c1Num} - Dus 2`,
                  box_id: c1.boxes?.[1]?.box_id || (c1.crate_id ? `${c1.crate_id}-02` : ''),
                  items: c1.boxes?.[1]?.items || []
                });
              }
              if (c2) {
                tables.push({
                  title: `Peti ${c2Num} - Dus 1`,
                  box_id: c2.boxes?.[0]?.box_id || (c2.crate_id ? `${c2.crate_id}-01` : ''),
                  items: c2.boxes?.[0]?.items || []
                });
                tables.push({
                  title: `Peti ${c2Num} - Dus 2`,
                  box_id: c2.boxes?.[1]?.box_id || (c2.crate_id ? `${c2.crate_id}-02` : ''),
                  items: c2.boxes?.[1]?.items || []
                });
              }

              const crateIds = [c1?.crate_id || c1?.crate_number, c2?.crate_id || c2?.crate_number].filter(Boolean).join(' & ');

              sheets.push({
                sheetIndex: s + 1,
                totalSheets: totalSheets,
                packageType: 'crate',
                containerLabel: 'ID Peti',
                containerId: crateIds || (crates[0]?.crate_id || '-'),
                tables: tables,
                crate1: c1,
                crate2: c2
              });
            }
            return sheets;
          }
        };

        const previewPackingListSheets = computed(() => {
          return buildPackingListSheets(previewDOData.value);
        });

        const previewPackingListSheetIndex = ref(0);

        const previewCurrentPackingListSheet = computed(() => {
          const sheets = previewPackingListSheets.value;
          if (!sheets || sheets.length === 0) return { crate1: null, crate2: null, tables: [], sheetIndex: 1, totalSheets: 1 };
          const idx = Math.min(Math.max(0, previewPackingListSheetIndex.value), sheets.length - 1);
          return sheets[idx] || { crate1: null, crate2: null, tables: [], sheetIndex: 1, totalSheets: 1 };
        });

        const defaultCrateIdForNow = computed(() => {
          const targetDate = previewDOData?.value?.do_date || activePrintDO?.value?.do_date || doForm?.value?.do_date;
          const { yy, mm } = getYearMonthFromDate(targetDate);
          return `${yy}${mm}-1001`;
        });

        const printPackingListSheets = computed(() => {
          return buildPackingListSheets(activePrintDO.value);
        });

        // Helper untuk mendapatkan nomor Packing List yang berurutan per lembar kertas fisik
        const getSheetPLNumber = (sheet, doData) => {
          if (!sheet) return '-';
          const c1PL = sheet.crate1?.pl_number || sheet.crate1?.packing_list_number;
          const basePL = (doData?.crates && doData.crates[0]?.pl_number)
            || doData?.pl_number
            || (doData?.do_number ? doData.do_number.replace(/\/SJ\//g, '/PL/') : '')
            || c1PL
            || '001/PL/PPIC/10/2026';
          const m = String(basePL).match(/^(\d+)(\/PL\/.*)$/);
          if (m) {
            const sheetIdx = Math.max(0, (sheet.sheetIndex || 1) - 1);
            const seq = parseInt(m[1], 10) + sheetIdx;
            return `${String(seq).padStart(3, '0')}${m[2]}`;
          }
          return c1PL || basePL;
        };

        // Tanggal cetak real-time untuk footer dokumen resmi Surat Jalan & Packing List
        const currentRealtimePrintDate = ref('');
        const updateRealtimePrintDate = () => {
          const now = getNowWIBDate();
          const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ];
          const day = now.getDate();
          const month = months[now.getMonth()];
          const year = now.getFullYear();
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          currentRealtimePrintDate.value = `${day} ${month} ${year}, ${hours}:${minutes} WIB`;
        };
        updateRealtimePrintDate();

        // Helper untuk mengekstrak seluruh Serial Number secara mengurut kebawah untuk kemasan satuan
        const getDOSerialNumbers = (doObj) => {
          if (!doObj) return [];
          const snList = [];
          const crates = doObj.crates || doObj.packing_lists || doObj.packingLists || [];
          if (Array.isArray(crates)) {
            for (const crate of crates) {
              if (crate.boxes && Array.isArray(crate.boxes)) {
                for (const box of crate.boxes) {
                  if (box.items && Array.isArray(box.items)) {
                    for (const it of box.items) {
                      const sn = (typeof it === 'string' ? it : it?.serial_number) || '';
                      if (sn && !snList.includes(sn)) {
                        snList.push(sn);
                      }
                    }
                  }
                }
              }
              if (crate.serial_number && !snList.includes(crate.serial_number)) {
                snList.push(crate.serial_number);
              }
              if (doObj.package_type === 'unit' && crate.crate_id && !snList.includes(crate.crate_id)) {
                snList.push(crate.crate_id);
              }
            }
          }
          if (snList.length === 0 && doObj.items && Array.isArray(doObj.items)) {
            for (const it of doObj.items) {
              const sn = (typeof it === 'string' ? it : it?.serial_number) || '';
              if (sn && !snList.includes(sn)) {
                snList.push(sn);
              }
            }
          }
          return snList;
        };

        const getDOTotalQuantity = (doObj) => {
          if (!doObj) return 0;
          const snList = getDOSerialNumbers(doObj);
          if (snList.length > 0) return snList.length;
          if (doObj.total_quantity && parseInt(doObj.total_quantity) > 0) {
            return parseInt(doObj.total_quantity);
          }
          if (doObj.total_units && parseInt(doObj.total_units) > 0) {
            return parseInt(doObj.total_units);
          }
          if (doObj.package_type === 'box') {
            const bCount = getDOBoxCount(doObj);
            return bCount > 0 ? bCount * 10 : 0;
          }
          if (doObj.package_type === 'crate') {
            const cCount = getDOCrateCount(doObj);
            return cCount > 0 ? cCount * 20 : 0;
          }
          return 0;
        };

        const getDOCrateCount = (doObj) => {
          if (!doObj) return 0;
          const crates = doObj.crates || doObj.packing_lists || doObj.packingLists || [];
          if (Array.isArray(crates) && crates.length > 0) {
            return crates.length;
          }
          if (doObj.total_crates && parseInt(doObj.total_crates) > 0) {
            return parseInt(doObj.total_crates);
          }
          const qty = getDOTotalQuantity(doObj);
          return Math.ceil(qty / 20) || 0;
        };

        const getDOBoxCount = (doObj) => {
          if (!doObj) return 0;
          const crates = doObj.crates || doObj.packing_lists || doObj.packingLists || [];
          if (Array.isArray(crates) && crates.length > 0) {
            let bCount = 0;
            crates.forEach(c => {
              if (c.boxes && Array.isArray(c.boxes)) {
                bCount += c.boxes.length;
              }
            });
            if (bCount > 0) return bCount;
          }
          if (doObj.total_boxes && parseInt(doObj.total_boxes) > 0) {
            return parseInt(doObj.total_boxes);
          }
          const qty = getDOTotalQuantity(doObj);
          return Math.ceil(qty / 10) || 0;
        };

        const buildPLCloudSlug = (doNumber) => {
          const base = (doNumber || '').trim().replace(/\/SJ\//g, '/PL/');
          const slug = base.replace(/[^A-Za-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');
          return slug || 'packing-list';
        };

        const getDOTrackingFullUrl = (doItemOrNumber) => {
          let doNumber = '';
          let cloudUrl = '';
          if (typeof doItemOrNumber === 'object' && doItemOrNumber !== null) {
            doNumber = doItemOrNumber.do_number || '';
            cloudUrl = doItemOrNumber.cloud_pdf_url || doItemOrNumber.cloud_qr_url || '';
          } else {
            doNumber = (doItemOrNumber || '').trim();
          }

          // Coba cari cloudUrl dari objek aktif jika yang dikirim hanya nomor DO
          if (!cloudUrl && doNumber) {
            if (previewDOData.value?.do_number === doNumber && previewDOData.value?.cloud_pdf_url) {
              cloudUrl = previewDOData.value.cloud_pdf_url;
            } else if (activePrintDO.value?.do_number === doNumber && activePrintDO.value?.cloud_pdf_url) {
              cloudUrl = activePrintDO.value.cloud_pdf_url;
            } else if (Array.isArray(doList.value)) {
              const matched = doList.value.find(d => d.do_number === doNumber);
              if (matched?.cloud_pdf_url) {
                cloudUrl = matched.cloud_pdf_url;
              }
            }
          }

          if (!doNumber && !cloudUrl) return '/tracking_pl.html';

          // 1. Prioritaskan URL file PDF di Cloudinary jika sudah ada atau terkonfigurasi
          if (cloudUrl) return cloudUrl;

          const cld = doSettings.value?.cloudinary;
          if (cld && cld.configured && cld.cloud_name) {
            const folder = (cld.folder || 'nkri-pl').replace(/^\/+|\/+$/g, '');
            const slug = buildPLCloudSlug(doNumber);
            return `https://res.cloudinary.com/${cld.cloud_name}/image/upload/${folder}/${slug}.pdf`;
          }

          // 2. Fallback ke URL LAN / WAN tracking
          let baseUrl = (doSettings.value?.tracking_base_url || '').trim();
          if (!baseUrl) {
            const port = window.location.port || '8000';
            const detectedIp = doSettings.value?.detected_ip || '';
            // Jangan pernah gunakan localhost/127.0.0.1 dalam QR code jika IP LAN terdeteksi
            if (detectedIp && detectedIp !== '127.0.0.1') {
              baseUrl = `http://${detectedIp}:${port}`;
            } else if (doSettings.value?.auto_base_url && !doSettings.value.auto_base_url.includes('127.0.0.1') && !doSettings.value.auto_base_url.includes('localhost')) {
              baseUrl = doSettings.value.auto_base_url;
            } else if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
              baseUrl = window.location.origin;
            } else {
              baseUrl = `http://${window.location.hostname}:${port}`;
            }
          }
          baseUrl = (baseUrl || '').replace(/\/+$/, '');
          return `${baseUrl}/tracking_pl.html?do=${encodeURIComponent(doNumber)}`;
        };

        const renderDOTrackingQRCode = (elementId, doItemOrNumber) => {
          const container = document.getElementById(elementId);
          if (!container) return;
          container.innerHTML = '';

          const trackingUrl = getDOTrackingFullUrl(doItemOrNumber);
          if (!trackingUrl) return;

          // Render High-Resolution QR Code dengan Error Correction L agar modul balok lebih besar & cepat di-scan di ukuran kecil
          if (window.QRCode) {
            try {
              new window.QRCode(container, {
                text: trackingUrl,
                width: 140,
                height: 140,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: window.QRCode.CorrectLevel.L
              });
              const canvas = container.querySelector('canvas');
              if (canvas) {
                canvas.style.display = 'block';
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.objectFit = 'contain';
                canvas.style.imageRendering = 'pixelated';
                canvas.style.margin = '0 auto';
              }
              const img = container.querySelector('img');
              if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                img.style.imageRendering = 'pixelated';
                img.style.margin = '0 auto';
                if (canvas) img.style.display = 'none';
                else img.style.display = 'block';
              }
              return;
            } catch (e) {
              console.error('Local QRCode error:', e);
            }
          }
          const img = document.createElement('img');
          img.src = `https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=${encodeURIComponent(trackingUrl)}&ecc=L`;
          img.style.width = '100%';
          img.style.height = '100%';
          img.style.objectFit = 'contain';
          img.style.imageRendering = 'pixelated';
          img.style.display = 'block';
          img.style.margin = '0 auto';
          img.alt = 'QR Tracking';
          container.appendChild(img);
        };

        const renderSNLabelQRCode = (elementId, snText) => {
          if (!snText) return;
          const container = document.getElementById(elementId);
          if (!container) return;
          container.innerHTML = '';
          if (window.QRCode) {
            try {
              new window.QRCode(container, {
                text: snText,
                width: 120,
                height: 120,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: window.QRCode.CorrectLevel.M
              });
              const canvas = container.querySelector('canvas');
              if (canvas) {
                canvas.style.display = 'block';
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.objectFit = 'contain';
                canvas.style.imageRendering = 'pixelated';
                canvas.style.margin = '0 auto';
              }
              const img = container.querySelector('img');
              if (img) {
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'contain';
                img.style.imageRendering = 'pixelated';
                img.style.margin = '0 auto';
                if (canvas) img.style.display = 'none';
                else img.style.display = 'block';
              }
              return;
            } catch (e) {
              console.error('Local QRCode error:', e);
            }
          }
          const img = document.createElement('img');
          img.src = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(snText)}`;
          img.style.width = '100%';
          img.style.height = '100%';
          img.style.objectFit = 'contain';
          img.style.imageRendering = 'pixelated';
          img.style.display = 'block';
          img.style.margin = '0 auto';
          img.alt = 'QR Code';
          container.appendChild(img);
        };

        const buildPackingListHTMLString = (data) => {
          if (!data) return '';
          const crates = data.crates || [];
          const settings = doSettings.value || {};
          const companyName = settings.company_name || 'PT Nanggala Kencana Rekatama Indonesia';
          const headOffice = settings.head_office || 'Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130';
          const factory = settings.factory_address || 'Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292';
          const contactEmail = settings.contact_email || 'info@nanggala.co.id';
          const plNumber = data.pl_number || (data.do_number ? data.do_number.replace(/\/SJ\//g, '/PL/') : '001/PL/PPIC/09/2026');

          const now = new Date();
          const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
          const printDateStr = `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}, ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} WIB`;

          let sheets = [];
          if (data.package_type === 'unit' || crates.length === 0) {
            sheets.push({
              sheetIndex: 1,
              totalSheets: 1,
              crate1: { crate_id: 'Kemasan Satuan', crate_number: 1, boxes: [] },
              crate2: null,
              isUnit: true
            });
          } else {
            for (let i = 0; i < crates.length; i += 2) {
              sheets.push({
                sheetIndex: Math.floor(i / 2) + 1,
                totalSheets: Math.ceil(crates.length / 2),
                crate1: crates[i],
                crate2: crates[i + 1] || null,
                isUnit: false
              });
            }
          }

          const getSN = (crate, boxIdx, slotIdx) => {
            if (!crate || !crate.boxes || !crate.boxes[boxIdx]) return '';
            const items = crate.boxes[boxIdx].items || [];
            const it = items[slotIdx];
            if (!it) return '';
            return typeof it === 'string' ? it : (it.serial_number || '');
          };

          const renderRows = (crate, boxIdx) => {
            let rows = '';
            for (let s = 1; s <= 10; s++) {
              const sn = getSN(crate, boxIdx, s - 1);
              rows += `
                <tr style="border-bottom: 1px solid #000000; height: 18.5px;">
                  <td style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">${s}</td>
                  <td style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">${sn}</td>
                </tr>
              `;
            }
            return rows;
          };

          const renderTables = (sheet) => {
            const c1 = sheet.crate1;
            const c2 = sheet.crate2;
            const c1b1_id = c1?.boxes?.[0]?.box_id || (c1?.crate_id ? c1.crate_id + '-01' : 'Dus 1');
            const c1b2_id = c1?.boxes?.[1]?.box_id || (c1?.crate_id ? c1.crate_id + '-02' : 'Dus 2');
            const c1_label = c1?.crate_id || c1?.crate_number || '1';

            const c2b1_id = c2 ? (c2.boxes?.[0]?.box_id || (c2.crate_id ? c2.crate_id + '-01' : 'Dus 1')) : '-';
            const c2b2_id = c2 ? (c2.boxes?.[1]?.box_id || (c2.crate_id ? c2.crate_id + '-02' : 'Dus 2')) : '-';
            const c2_label = c2 ? (c2.crate_id || c2.crate_number || '2') : '';

            return `
              <table style="width: 100%; border-collapse: separate; border-spacing: 16px 12px; margin: 4px 0; border: none; font-family: 'Roboto', Arial, sans-serif;">
                <tbody>
                  <tr>
                    <!-- Tabel 1: Peti 1 - Pak 1 -->
                    <td style="width: 50%; vertical-align: top; padding: 0;">
                      <div style="margin-bottom: 4px; font-weight: bold; font-size: 12.5px;">
                        No. Pak : <strong>${c1b1_id}</strong>
                        <span style="font-size: 11px; color: #64748b; font-weight: normal; margin-left: 4px;">(Peti ${c1_label})</span>
                      </div>
                      <table style="border-collapse: collapse; border: 1.5px solid #000000; width: 100%; font-size: 11.5px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid #000000; font-weight: 700;">
                            <th style="border-right: 1.5px solid #000000; padding: 3px 6px; text-align: center; width: 42px; font-size: 12px;">No</th>
                            <th style="padding: 3px 8px; text-align: left; font-size: 12px;">Serial Number</th>
                          </tr>
                        </thead>
                        <tbody>${renderRows(c1, 0)}</tbody>
                      </table>
                    </td>

                    <!-- Tabel 2: Peti 1 - Pak 2 -->
                    <td style="width: 50%; vertical-align: top; padding: 0;">
                      <div style="margin-bottom: 4px; font-weight: bold; font-size: 12.5px;">
                        No. Pak : <strong>${c1b2_id}</strong>
                        <span style="font-size: 11px; color: #64748b; font-weight: normal; margin-left: 4px;">(Peti ${c1_label})</span>
                      </div>
                      <table style="border-collapse: collapse; border: 1.5px solid #000000; width: 100%; font-size: 11.5px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid #000000; font-weight: 700;">
                            <th style="border-right: 1.5px solid #000000; padding: 3px 6px; text-align: center; width: 42px; font-size: 12px;">No</th>
                            <th style="padding: 3px 8px; text-align: left; font-size: 12px;">Serial Number</th>
                          </tr>
                        </thead>
                        <tbody>${renderRows(c1, 1)}</tbody>
                      </table>
                    </td>
                  </tr>

                  <tr>
                    <!-- Tabel 3: Peti 2 - Pak 1 -->
                    <td style="width: 50%; vertical-align: top; padding: 0;">
                      <div style="margin-bottom: 4px; font-weight: bold; font-size: 12.5px;">
                        No. Pak : <strong>${c2b1_id}</strong>
                        ${c2 ? `<span style="font-size: 11px; color: #64748b; font-weight: normal; margin-left: 4px;">(Peti ${c2_label})</span>` : ''}
                      </div>
                      <table style="border-collapse: collapse; border: 1.5px solid #000000; width: 100%; font-size: 11.5px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid #000000; font-weight: 700;">
                            <th style="border-right: 1.5px solid #000000; padding: 3px 6px; text-align: center; width: 42px; font-size: 12px;">No</th>
                            <th style="padding: 3px 8px; text-align: left; font-size: 12px;">Serial Number</th>
                          </tr>
                        </thead>
                        <tbody>${renderRows(c2, 0)}</tbody>
                      </table>
                    </td>

                    <!-- Tabel 4: Peti 2 - Pak 2 -->
                    <td style="width: 50%; vertical-align: top; padding: 0;">
                      <div style="margin-bottom: 4px; font-weight: bold; font-size: 12.5px;">
                        No. Pak : <strong>${c2b2_id}</strong>
                        ${c2 ? `<span style="font-size: 11px; color: #64748b; font-weight: normal; margin-left: 4px;">(Peti ${c2_label})</span>` : ''}
                      </div>
                      <table style="border-collapse: collapse; border: 1.5px solid #000000; width: 100%; font-size: 11.5px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid #000000; font-weight: 700;">
                            <th style="border-right: 1.5px solid #000000; padding: 3px 6px; text-align: center; width: 42px; font-size: 12px;">No</th>
                            <th style="padding: 3px 8px; text-align: left; font-size: 12px;">Serial Number</th>
                          </tr>
                        </thead>
                        <tbody>${renderRows(c2, 1)}</tbody>
                      </table>
                    </td>
                  </tr>
                </tbody>
              </table>
            `;
          };

          let fullHtml = '<div style="background:#ffffff; color:#000000; width: 794px; margin: 0; padding: 0;">';
          sheets.forEach((sheet, sheetIdx) => {
            const c1 = sheet.crate1;
            const c2 = sheet.crate2;
            const idPetiStr = [
              c1 ? (c1.crate_id || c1.crate_number) : null,
              c2 ? (c2.crate_id || c2.crate_number) : null
            ].filter(Boolean).join(' & ') || '-';

            const sheetPLNumber = (() => {
              const basePL = (data.crates && data.crates[0]?.pl_number) || (data.crates && data.crates[0]?.packing_list_number) || data.pl_number || plNumber || '001/PL/PPIC/10/2026';
              const m = String(basePL).match(/^(\d+)(\/PL\/.*)$/);
              if (m) {
                const sIdx = Math.max(0, (sheet.sheetIndex || (sheetIdx + 1)) - 1);
                return `${String(parseInt(m[1], 10) + sIdx).padStart(3, '0')}${m[2]}`;
              }
              return c1?.pl_number || c1?.packing_list_number || basePL;
            })();

            fullHtml += `
              <div style="width: 794px; min-height: 1123px; max-height: 1123px; padding: 0 45px; box-sizing: border-box; font-family: 'Roboto', Arial, sans-serif; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; page-break-after: always; break-after: page; background: #ffffff;">
                <div>
                  <!-- Header Polygon Pink -->
                  <div style="position: relative; margin-left: -45px; margin-right: -45px; width: 794px; height: 124px; box-sizing: border-box; overflow: hidden; margin-bottom: 18px;">
                    <svg style="position: absolute; top: 0; left: 0; width: 794px; height: 124px; display: block; z-index: 0;" viewBox="0 0 794 124" preserveAspectRatio="none">
                      <polygon points="0,0 794,0 794,68 395,68 360,124 0,124" fill="#F7EAEB" />
                    </svg>
                    <div style="position: relative; z-index: 1; height: 100%; padding: 0 45px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
                      <div style="height: 68px; padding-top: 14px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; padding-top: 10px;">
                          <img src="${nkriLogoBase64 || nkriLabelLogoBase64 || '/nkri-logo.png'}" alt="NKRI" style="height: 38px; width: auto;" />
                        </div>
                        <div style="text-align: right;">
                          <span style="font-weight: 700; font-size: 15px; color: #000000; letter-spacing: -0.2px;">${companyName}</span>
                        </div>
                      </div>
                      <div style="height: 56px;"></div>
                    </div>
                  </div>

                  <!-- Metadata Grid -->
                  <div style="font-size: 13px; color: #000000; line-height: 1.45; margin-bottom: 12px;">
                    <table style="border-collapse: collapse; border: none; width: 100%;">
                      <tbody>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; width: 130px; vertical-align: top; font-size: 13px;">No. Packing List</td>
                          <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: ${sheetPLNumber}</td>
                        </tr>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Tanggal</td>
                          <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: ${data.do_date || '-'}</td>
                        </tr>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">ID Peti</td>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: ${idPetiStr}</td>
                        </tr>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nama Produk</td>
                          <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: ${data.product_name || 'Nanggala P9'}</td>
                        </tr>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nomor</td>
                          <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: ${data.contract_number || '-'}</td>
                        </tr>
                        <tr>
                          <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Program</td>
                          <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; max-width: 440px; line-height: 1.4; font-size: 13px;">: ${data.program_name || '-'}</td>
                        </tr>
                      </tbody>
                    </table>

                    <div style="margin-top: 10px;">
                      <div style="font-weight: 700; font-size: 13px;">Penerima :</div>
                      <div style="margin-top: 2px; line-height: 1.4; font-size: 13px;">
                        <div style="font-weight: 400;">${data.customer_name || '-'}</div>
                        <div style="white-space: pre-line;">${data.customer_address || '-'}</div>
                      </div>
                    </div>
                  </div>

                  <!-- 4 Tables -->
                  ${renderTables(sheet)}
                </div>

                <!-- Bottom Footer -->
                <div style="margin-top: auto;">
                  <div style="display: flex; justify-content: space-between; font-size: 11px; color: #64748b; font-weight: bold; margin-bottom: 6px;">
                    <span>Tanggal Akses Dokumen: ${printDateStr}</span>
                    <span>Lembar ${sheet.sheetIndex} dari ${sheet.totalSheets}</span>
                  </div>
                  <div style="background-color: #F7EAEB; margin-left: -45px; margin-right: -45px; padding: 14px 45px 36px 45px; width: 794px; box-sizing: border-box;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px;">
                      <div style="max-width: 240px;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Head Office :</div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35; white-space: pre-line;">${headOffice}</div>
                      </div>
                      <div style="max-width: 240px;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Factory :</div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35; white-space: pre-line;">${factory}</div>
                      </div>
                      <div style="text-align: left;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Kontak :</div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35;">${contactEmail}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `;
          });
          fullHtml += '</div>';
          return fullHtml;
        };

        const uploadDOPackingListToCloud = async (doItemOrId) => {
          let doData = null;
          let doId = null;
          if (typeof doItemOrId === 'object' && doItemOrId !== null) {
            doData = doItemOrId;
            doId = doItemOrId.id;
          } else {
            doId = doItemOrId;
          }
          if (!doId) return false;

          isUploadingCloudPdf.value[doId] = true;
          try {
            // Ambil detail lengkap jika crates belum ada
            if (!doData || !doData.crates || doData.crates.length === 0) {
              const res = await fetchAPI(`/delivery-orders/detail?id=${doId}`);
              if (res && res.data) {
                doData = res.data;
              }
            }
            if (!doData) throw new Error('Data DO tidak ditemukan');

            // Kirim permintaan upload langsung ke backend (backend akan menghasilkan PDF resmi via ReportLab langsung dari database)
            const uploadRes = await fetchAPI('/delivery-orders/cloud-pdf', {
              method: 'POST',
              body: JSON.stringify({
                do_id: doId
              })
            });

            if (uploadRes && uploadRes.ok) {
              triggerToast('Cloudinary Tersinkron', `PDF Packing List ${doData.do_number} berhasil diunggah (${Math.round((uploadRes.bytes || 0) / 1024)} KB)!`, 'success');
              // Update status lokal
              if (doData) {
                doData.cloud_pdf_url = uploadRes.cloud_pdf_url;
                doData.cloud_version = uploadRes.cloud_version;
                doData.cloud_public_id = uploadRes.cloud_public_id;
              }
              if (previewDOData.value && previewDOData.value.id === doId) {
                previewDOData.value.cloud_pdf_url = uploadRes.cloud_pdf_url;
                renderDOTrackingQRCode('do-sj-qr-preview', previewDOData.value);
              }
              await loadDeliveryOrders();
              return true;
            } else {
              triggerToast('Gagal Unggah Cloud', uploadRes?.error || 'Gagal mengunggah file ke Cloudinary', 'error');
              return false;
            }
          } catch (err) {
            console.error('Upload to Cloudinary error:', err);
            triggerToast('Error Cloudinary', err.message || 'Gagal memproses upload PDF', 'error');
            return false;
          } finally {
            isUploadingCloudPdf.value[doId] = false;
          }
        };

        const syncAllDOsToCloud = async () => {
          if (!doSettings.value?.cloudinary?.configured) {
            triggerToast('Perhatian', 'Kredensial Cloudinary belum dikonfigurasi. Silakan isi di tab Pengaturan terlebih dahulu.', 'warn');
            doSubTab.value = 'settings';
            return;
          }

          let pendingDOs = (doList.value || []).filter(d => !d.cloud_pdf_url);
          let isReSync = false;
          if (pendingDOs.length === 0) {
            const reOk = await showConfirmCard({
              title: 'Unggah Ulang Semua ke Cloudinary',
              message: 'Semua dokumen Surat Jalan sudah memiliki link cloud. Apakah Anda ingin mengunggah ulang (refresh/update) seluruh dokumen ke Cloudinary agar PDF terbarui?',
              icon: 'fa-solid fa-rotate',
              iconBg: 'bg-indigo-100 text-indigo-700',
              confirmText: 'Ya, Unggah Ulang Semua',
              confirmBtnClass: 'bg-indigo-600 hover:bg-indigo-700 text-white'
            });
            if (!reOk) return;
            pendingDOs = doList.value || [];
            isReSync = true;
          } else {
            const ok = await showConfirmCard({
              title: 'Sinkronisasi Massal ke Cloudinary',
              message: `Ditemukan ${pendingDOs.length} dokumen yang belum diunggah ke Cloudinary. Proses ini akan mengunggah seluruh dokumen secara berurutan agar dokumen Packing List dapat diakses publik dari luar jaringan. Lanjutkan?`,
              icon: 'fa-solid fa-cloud-arrow-up',
              iconBg: 'bg-indigo-100 text-indigo-700',
              confirmText: 'Ya, Sinkronkan Sekarang',
              confirmBtnClass: 'bg-indigo-600 hover:bg-indigo-700 text-white'
            });
            if (!ok) return;
          }

          isBatchSyncingCloud.value = true;
          batchSyncProgress.value = { total: pendingDOs.length, current: 0, success: 0, fail: 0 };

          for (const item of pendingDOs) {
            batchSyncProgress.value.current++;
            try {
              const s = await uploadDOPackingListToCloud(item);
              if (s) batchSyncProgress.value.success++;
              else batchSyncProgress.value.fail++;
            } catch (syncErr) {
              console.error('Error saat sinkronisasi cloud:', syncErr);
              batchSyncProgress.value.fail++;
            }
          }

          isBatchSyncingCloud.value = false;
          if (batchSyncProgress.value.success > 0) {
            triggerToast('Sinkronisasi Selesai', `Berhasil mengunggah ${batchSyncProgress.value.success} dari ${pendingDOs.length} dokumen ke Cloudinary!`, 'success');
          } else {
            triggerToast('Gagal Sinkronisasi', `Gagal mengunggah dokumen ke Cloudinary. Periksa koneksi internet atau kredensial Cloudinary.`, 'error');
          }
          await loadDeliveryOrders();
        };

        const testCloudinaryConnection = async () => {
          isTestingCloud.value = true;
          cloudTestResult.value = null;
          try {
            const res = await fetchAPI('/cloudinary/test', {
              method: 'POST',
              body: JSON.stringify(cloudForm.value)
            });
            cloudTestResult.value = res;
            if (res && res.ok) {
              if (res.delivery_ok) {
                triggerToast('Koneksi Berhasil!', res.note || 'Cloudinary aktif dan dapat diakses publik.', 'success');
              } else {
                triggerToast('Perhatian Pengaturan Akun', res.note || 'Upload berhasil, namun pengiriman PDF masih diblokir oleh Cloudinary.', 'warn');
              }
              await loadDOSettings();
            } else {
              triggerToast('Uji Koneksi Gagal', res?.error || 'Gagal menghubungi Cloudinary', 'error');
            }
          } catch (err) {
            cloudTestResult.value = { ok: false, error: err.message };
            triggerToast('Error Koneksi', err.message, 'error');
          } finally {
            isTestingCloud.value = false;
          }
        };

        const saveCloudinarySettings = async () => {
          isSavingCloudSettings.value = true;
          try {
            const res = await fetchAPI('/cloudinary/settings', {
              method: 'POST',
              body: JSON.stringify(cloudForm.value)
            });
            if (res && res.ok) {
              triggerToast('Sukses', 'Pengaturan Cloudinary berhasil disimpan!', 'success');
              if (res.data) {
                doSettings.value.cloudinary = res.data;
              }
              await loadDOSettings();
            } else {
              triggerToast('Gagal', res?.error || 'Gagal menyimpan pengaturan Cloudinary', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message, 'error');
          } finally {
            isSavingCloudSettings.value = false;
          }
        };

        const loadDOSettings = async () => {
          try {
            const res = await fetchAPI('/delivery-orders/settings');
            if (res && (res.data || res.settings)) {
              const data = res.data || res.settings;
              doSettings.value = { ...doSettings.value, ...data };
              if (data.current_batch_number) {
                doForm.value.batch_number = data.current_batch_number;
                labelForm.value.batch_number = data.current_batch_number;
              }
              if (data.cloudinary) {
                doSettings.value.cloudinary = data.cloudinary;
                cloudForm.value.cloud_name = data.cloudinary.cloud_name || '';
                cloudForm.value.folder = data.cloudinary.folder || 'nkri-pl';
              }
            }
          } catch (e) {
            console.error('Failed to load DO settings:', e);
          }
        };

        const saveDOSettings = async () => {
          isSavingDOSettings.value = true;
          try {
            const res = await fetchAPI('/delivery-orders/settings', {
              method: 'POST',
              body: JSON.stringify(doSettings.value)
            });
            if (res && (res.success || res.ok)) {
              triggerToast('Sukses', 'Pengaturan Surat Jalan & Packing List berhasil disimpan.', 'success');
              await loadDOSettings();
              await loadNextDOMeta();
            } else {
              triggerToast('Gagal', res?.message || 'Gagal menyimpan pengaturan.', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSavingDOSettings.value = false;
          }
        };

        // ===================================================================
        // STANDALONE PACKING LABELS METHODS
        // ===================================================================

        const generatedPackagingIds = computed(() => {
          const type = labelForm.value.package_type || 'crate';
          const crates = labelForm.value.crates || [];
          if (type === 'crate') {
            return crates.map(c => c.crate_id).filter(Boolean);
          } else {
            return crates.map(c => c.box_id || (c.boxes && c.boxes[0] && c.boxes[0].box_id)).filter(Boolean);
          }
        });

        const generatedPackagingIdPreview = computed(() => {
          const ids = generatedPackagingIds.value;
          if (!ids || ids.length === 0) return '-';
          const type = labelForm.value.package_type === 'crate' ? 'Peti' : 'Dus';
          if (ids.length === 1) return ids[0];
          if (ids.length === 2) return `${ids[0]}, ${ids[1]}`;
          return `${ids[0]} s/d ${ids[ids.length - 1]} (${ids.length} ${type})`;
        });

        const checkPackagingUniqueness = async () => {
          const ids = generatedPackagingIds.value;
          if (!ids || ids.length === 0) {
            plDuplicateWarning.value = false;
            plDuplicateMessage.value = '';
            return true;
          }
          const type = labelForm.value.package_type || 'crate';
          const labelType = type === 'crate' ? 'Peti' : 'Dus';

          // 1. Cek duplikasi di Standalone Packing Labels lokal
          let foundDup = null;
          (standaloneLabelsList.value || []).forEach(lbl => {
            const existIds = getLabelPackagingIds(lbl);
            for (const curId of ids) {
              if (existIds.some(ex => ex && ex.trim().toUpperCase() === curId.trim().toUpperCase())) {
                foundDup = curId;
                break;
              }
            }
          });

          // 2. Cek juga di Surat Jalan (DO) lokal
          if (!foundDup) {
            (doList.value || []).forEach(d => {
              (d.crates || []).forEach(c => {
                if (type === 'crate' && c.crate_id) {
                  for (const curId of ids) {
                    if (c.crate_id && c.crate_id.trim().toUpperCase() === curId.trim().toUpperCase()) {
                      foundDup = curId;
                      break;
                    }
                  }
                }
                (c.boxes || []).forEach(b => {
                  if (type === 'box' && b.box_id) {
                    for (const curId of ids) {
                      if (b.box_id && b.box_id.trim().toUpperCase() === curId.trim().toUpperCase()) {
                        foundDup = curId;
                        break;
                      }
                    }
                  }
                });
              });
            });
          }

          if (foundDup) {
            plDuplicateWarning.value = true;
            plDuplicateMessage.value = `ID ${labelType} "${foundDup}" sudah pernah dibuat dan tersimpan! Silakan ubah nomor batch atau nomor pengiriman agar unik.`;
            return false;
          }

          // 3. Cek ke backend
          isCheckingPlUnique.value = true;
          try {
            const checkId = ids[0];
            const res = await fetchAPI(`/delivery-orders/check-unique?type=${type}&number=${encodeURIComponent(checkId)}`);
            if (res && res.ok) {
              if (!res.is_unique) {
                plDuplicateWarning.value = true;
                plDuplicateMessage.value = res.message || `ID ${labelType} "${checkId}" sudah pernah dibuat! Silakan ubah nomor batch atau pengiriman.`;
                return false;
              }
            }
            plDuplicateWarning.value = false;
            plDuplicateMessage.value = '';
            return true;
          } catch (e) {
            console.error('Error checking packaging uniqueness:', e);
          } finally {
            isCheckingPlUnique.value = false;
          }
          return true;
        };

        const checkPlUniqueness = checkPackagingUniqueness;

        const syncLabelPackagingStructure = (forceBlank = false) => {
          let type = labelForm.value.package_type || 'crate';
          if (type !== 'box') type = 'crate';
          labelForm.value.package_type = type;
          const count = Math.max(1, parseInt(labelForm.value.package_count) || 1);
          const batchNum = Math.max(1, parseInt(labelForm.value.batch_number) || 1);
          const crates = [];

          const targetDate = labelForm.value.date || getNowWIBDateString();
          const parsed = getYearMonthFromDate(targetDate);
          const yy = parsed.yy;
          const mm = parsed.mm;
          const bNumStr = String(parseInt(batchNum, 10) || 1).padStart(2, '0');
          const ppStr = String(Math.max(1, parseInt(plDeliverySeq.value, 10) || 1)).padStart(2, '0');

          let startSeq = 1;
          if (type === 'crate') {
            for (let c = 0; c < count; c++) {
              const crateSeq = startSeq + c;
              const ddStr = String(crateSeq).padStart(2, '0');
              const crateId = `${yy}${mm}-${bNumStr}-${ppStr}${ddStr}`;
              const boxes = [];
              for (let b = 1; b <= 2; b++) {
                const ssStr = String(b).padStart(2, '0');
                const boxId = `${crateId}-${ssStr}`;
                const items = [];
                for (let s = 1; s <= 10; s++) {
                  items.push({ slot_number: s, serial_number: '' });
                }
                boxes.push({ box_number: b, box_id: boxId, items });
              }
              crates.push({ crate_number: c + 1, crate_id: crateId, total_quantity: 20, boxes });
            }
          } else {
            // Kemasan Dus (10 SN per Dus): Peti di-00-kan, No Dus count sebanyak jumlah dusnya (01, 02, ... 10)
            const parentCrateId = `${yy}${mm}-${bNumStr}-${ppStr}00`;
            for (let b = 0; b < count; b++) {
              const boxSeqStr = String(b + 1).padStart(2, '0');
              const boxId = `${parentCrateId}-${boxSeqStr}`;
              const items = [];
              for (let s = 1; s <= 10; s++) {
                items.push({ slot_number: s, serial_number: '' });
              }
              crates.push({
                crate_number: b + 1,
                crate_id: parentCrateId,
                box_id: boxId,
                total_quantity: 10,
                boxes: [{ box_number: b + 1, box_id: boxId, items }]
              });
            }
          }

          // Preserve existing serial numbers unless forceBlank is true
          if (!forceBlank) {
            const oldCrates = labelForm.value.crates || [];
            crates.forEach((c, cIdx) => {
              const oldC = oldCrates[cIdx];
              if (oldC && oldC.boxes) {
                c.boxes.forEach((b, bIdx) => {
                  const oldB = oldC.boxes[bIdx];
                  if (oldB && oldB.items) {
                    b.items.forEach((it, sIdx) => {
                      const oldIt = oldB.items[sIdx];
                      if (oldIt && oldIt.serial_number) {
                        it.serial_number = oldIt.serial_number;
                      }
                    });
                  }
                });
              }
            });
          }

          labelForm.value.crates = crates;
          const primaryId = (type === 'crate') ? (crates[0]?.crate_id || '') : (crates[0]?.box_id || '');
          plGeneratedId.value = primaryId;
          labelForm.value.pl_number = primaryId;

          if (activeLabelCrateIndex.value >= crates.length) {
            activeLabelCrateIndex.value = Math.max(0, crates.length - 1);
          }
          const maxPg = Math.max(1, Math.ceil(crates.length / labelCratesPerPage.value));
          if (labelCratePage.value > maxPg) {
            labelCratePage.value = maxPg;
          }

          checkPackagingUniqueness();
        };

        const updateGeneratedPLNumber = () => {
          syncLabelPackagingStructure();
        };

        watch(plMode, (mode) => {
          if (mode === 'realtime') {
            labelForm.value.date = getNowWIBDateString();
          }
          syncLabelPackagingStructure();
        });
        watch(() => labelForm.value.date, () => {
          syncLabelPackagingStructure();
        });
        watch(plDeliverySeq, () => {
          syncLabelPackagingStructure();
        });
        watch(() => labelForm.value.batch_number, () => {
          syncLabelPackagingStructure();
        });

        const getLabelFormFilledCount = () => {
          let count = 0;
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => {
                if (it.serial_number && String(it.serial_number).trim()) count++;
              });
            });
          });
          return count;
        };

        const getLabelFormRequiredCount = () => {
          let count = 0;
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              count += (b.items || []).length;
            });
          });
          if (count === 0) {
            const type = labelForm.value.package_type || 'crate';
            const pkgCount = Math.max(1, parseInt(labelForm.value.package_count) || 1);
            return type === 'crate' ? pkgCount * 20 : (type === 'box' ? pkgCount * 10 : pkgCount);
          }
          return count;
        };

        const getLabelCrateFilledCount = (crateOrIndex) => {
          let crate = crateOrIndex;
          if (typeof crateOrIndex === 'number') {
            crate = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates[crateOrIndex] : null;
          }
          if (!crate || !crate.boxes) return 0;
          let count = 0;
          crate.boxes.forEach(b => {
            (b.items || []).forEach(it => {
              if (it.serial_number && String(it.serial_number).trim()) count++;
            });
          });
          return count;
        };

        const getLabelCrateRequiredCount = (crateOrIndex) => {
          let crate = crateOrIndex;
          if (typeof crateOrIndex === 'number') {
            crate = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates[crateOrIndex] : null;
          }
          if (!crate) {
            crate = (labelForm.value && labelForm.value.crates) ? labelForm.value.crates[activeLabelCrateIndex.value] : null;
          }
          if (!crate || !crate.boxes) {
            const type = (labelForm.value && labelForm.value.package_type) || 'crate';
            return type === 'crate' ? 20 : (type === 'box' ? 10 : 1);
          }
          let count = 0;
          crate.boxes.forEach(b => {
            count += (b.items || []).length;
          });
          return count;
        };

        const isLabelFormComplete = computed(() => {
          const crates = labelForm.value.crates || [];
          if (!crates || crates.length === 0) return false;
          let totalSlots = 0;
          let filledSlots = 0;
          for (const c of crates) {
            const boxes = c.boxes || [];
            if (!boxes || boxes.length === 0) return false;
            for (const b of boxes) {
              const items = b.items || [];
              if (!items || items.length === 0) return false;
              for (const it of items) {
                totalSlots++;
                if (it.serial_number && String(it.serial_number).trim()) {
                  filledSlots++;
                }
              }
            }
          }
          return totalSlots > 0 && filledSlots === totalSlots;
        });

        const hasInvalidLabelSNs = computed(() => {
          const crates = labelForm.value.crates || [];
          if (!crates || crates.length === 0) return false;
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          for (const c of crates) {
            for (const b of (c.boxes || [])) {
              for (const it of (b.items || [])) {
                if (it.serial_number && String(it.serial_number).trim()) {
                  const sn = String(it.serial_number).trim().toUpperCase();
                  if (!availableSet.has(sn)) {
                    return true;
                  }
                }
              }
            }
          }
          return false;
        });

        const resetLabelForm = async (customBatch = null) => {
          plMode.value = '';
          plActiveStep.value = 0;
          plDeliverySeq.value = 1;
          plGeneratedId.value = '';
          plDuplicateWarning.value = false;
          plDuplicateMessage.value = '';
          activeLabelCrateIndex.value = 0;

          const currentBatch = customBatch || doSettings.value?.current_batch_number || 1;
          labelForm.value = {
            date: getNowWIBDateString(),
            package_type: 'crate',
            package_count: 1,
            batch_number: currentBatch,
            product_name: doSettings.value?.default_product_name || 'Nanggala P9',
            notes: '',
            crates: []
          };
          loadStandaloneLabels();
        };

        const confirmResetLabelForm = async () => {
          const hasFilled = (labelForm.value.crates || []).some(c => (c.boxes || []).some(b => (b.items || []).some(it => it.serial_number)));
          if (hasFilled) {
            const ok = await showConfirmCard({
              title: 'Reset Form Packing List',
              message: 'Apakah Anda yakin ingin mengosongkan form? Nomor Serial Number yang sudah diisi akan dibersihkan kembali dari awal.',
              icon: 'fa-solid fa-arrow-rotate-left',
              iconBg: 'bg-amber-100 text-amber-600',
              confirmText: 'Ya, Kosongkan Form',
              confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
            });
            if (!ok) return;
          }
          await resetLabelForm();
          triggerToast('Form Direset', 'Form Packing List telah dikosongkan kembali dari awal.', 'info');
        };

        // Bulking Data Input State & Methods (Standalone Packing List)
        const showLabelBulkModal = ref(false);
        const labelBulkRawText = ref('');
        const labelBulkScope = ref('all'); // 'all', 'from_active', 'active_only'
        const labelBulkMode = ref('overwrite'); // 'overwrite', 'empty_only'

        const openLabelBulkModal = () => {
          labelBulkRawText.value = '';
          labelBulkScope.value = 'all';
          labelBulkMode.value = 'overwrite';
          showLabelBulkModal.value = true;
        };

        const closeLabelBulkModal = () => {
          showLabelBulkModal.value = false;
        };

        const parsedLabelBulkSNs = computed(() => {
          if (!labelBulkRawText.value) return [];
          return labelBulkRawText.value
            .split(/[\r\n,;\t]+/)
            .map(s => s.trim().toUpperCase())
            .filter(s => s.length > 0);
        });

        const labelBulkStats = computed(() => {
          const list = parsedLabelBulkSNs.value;
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          let validCount = 0;
          let unverifiedCount = 0;
          const seen = new Set();
          let duplicatesCount = 0;

          list.forEach(sn => {
            if (seen.has(sn)) {
              duplicatesCount++;
            } else {
              seen.add(sn);
            }
            if (availableSet.has(sn)) {
              validCount++;
            } else {
              unverifiedCount++;
            }
          });

          return {
            total: list.length,
            unique: seen.size,
            validCount,
            unverifiedCount,
            duplicatesCount
          };
        });

        const applyLabelBulkData = () => {
          const list = parsedLabelBulkSNs.value;
          if (!list || list.length === 0) {
            triggerToast('Peringatan', 'Tidak ada data Serial Number yang dimasukkan.', 'warning');
            return;
          }

          // Validasi Ketersediaan SN: Wajib terdaftar di stok selesai dan lolos penerimaan fisik gudang
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          const invalidSNs = list.filter(sn => !availableSet.has(sn));

          if (invalidSNs.length > 0) {
            const sample = invalidSNs.slice(0, 3).join(', ') + (invalidSNs.length > 3 ? ` (+${invalidSNs.length - 3} lainnya)` : '');
            triggerToast('SN Tidak Tersedia', `Ditemukan ${invalidSNs.length} SN yang tidak tersedia atau belum lolos penerimaan gudang: ${sample}. Hanya SN yang telah lolos seluruh alur dan penerimaan fisik gudang yang dapat dimasukkan.`, 'error');
            return;
          }

          const crates = labelForm.value.crates || [];
          if (crates.length === 0) {
            triggerToast('Peringatan', 'Belum ada kemasan yang dikonfigurasi.', 'warning');
            return;
          }

          // Kumpulkan target slot SN sesuai jangkauan (scope)
          const targetSlots = [];
          let startCrateIdx = 0;
          let endCrateIdx = crates.length - 1;

          if (labelBulkScope.value === 'from_active') {
            startCrateIdx = Math.max(0, activeLabelCrateIndex.value);
          } else if (labelBulkScope.value === 'active_only') {
            startCrateIdx = Math.max(0, activeLabelCrateIndex.value);
            endCrateIdx = startCrateIdx;
          }

          for (let c = startCrateIdx; c <= endCrateIdx && c < crates.length; c++) {
            const boxes = crates[c].boxes || [];
            for (let b = 0; b < boxes.length; b++) {
              const items = boxes[b].items || [];
              for (let it = 0; it < items.length; it++) {
                targetSlots.push(items[it]);
              }
            }
          }

          if (targetSlots.length === 0) {
            triggerToast('Peringatan', 'Tidak ada slot kemasan yang tersedia pada target yang dipilih.', 'warning');
            return;
          }

          let appliedCount = 0;
          let snIdx = 0;

          for (let i = 0; i < targetSlots.length && snIdx < list.length; i++) {
            const slot = targetSlots[i];
            if (labelBulkMode.value === 'empty_only' && slot.serial_number && String(slot.serial_number).trim()) {
              continue; // Lewati jika sudah terisi pada mode empty_only
            }
            slot.serial_number = list[snIdx++];
            appliedCount++;
          }

          showLabelBulkModal.value = false;

          let msg = `${appliedCount} Serial Number berhasil dialokasikan secara bulking!`;
          if (snIdx < list.length) {
            const remaining = list.length - snIdx;
            msg += ` (${remaining} SN berlebih tidak masuk karena slot target penuh).`;
          }
          triggerToast('Bulking Selesai', msg, 'success');
        };

        // Alias backwards-compatibility
        const autoFillAvailableSNsForLabel = () => {
          openLabelBulkModal();
        };

        const loadStandaloneLabels = async () => {
          isLoadingStandaloneLabels.value = true;
          try {
            const res = await fetchAPI('/packing-labels');
            if (res && res.data) {
              standaloneLabelsList.value = res.data;
              const curBatch = res.current_batch || doSettings.value?.current_batch_number || 1;
              labelForm.value.batch_number = curBatch;
              if (res.next_pl_seq) {
                plDeliverySeq.value = res.next_pl_seq;
              }
              if (res.next_sj_seq) {
                sjDeliverySeq.value = res.next_sj_seq;
              }
              syncLabelPackagingStructure();
            }
          } catch (e) {
            console.error('Failed to load standalone labels:', e);
          } finally {
            isLoadingStandaloneLabels.value = false;
          }
        };

        const openStandaloneLabelsHistory = () => {
          doSubTab.value = 'labels_history';
          loadStandaloneLabels();
        };

        const saveStandaloneLabel = async (printType = null) => {
          if (plDuplicateWarning.value) {
            triggerToast('Dokumen Duplikat', 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.', 'error');
            return;
          }

          if (!isLabelFormComplete.value) {
            const filled = getLabelFormFilledCount();
            const req = getLabelFormRequiredCount();
            triggerToast('Kemasan Belum Lengkap', `Seluruh slot Serial Number harus terisi penuh (${filled} dari ${req} SN terisi) sebelum dapat disimpan dan dicetak label.`, 'warn');
            return;
          }

          // Cek Serial Number duplikat dalam pengisian
          const allSNs = [];
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => {
                const sn = String(it.serial_number || '').trim();
                if (sn) allSNs.push(sn);
              });
            });
          });
          const seen = new Set();
          const duplicates = [];
          for (const sn of allSNs) {
            if (seen.has(sn)) {
              if (!duplicates.includes(sn)) duplicates.push(sn);
            } else {
              seen.add(sn);
            }
          }
          if (duplicates.length > 0) {
            triggerToast('Serial Number Duplikat', `Terdapat SN ganda pada kemasan: ${duplicates.join(', ')}. Setiap slot harus memiliki Serial Number unik.`, 'error');
            return;
          }

          // Cek ketersediaan seluruh SN di stok gudang yang valid
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          const unavailable = allSNs.filter(sn => !availableSet.has(sn.trim().toUpperCase()));
          if (unavailable.length > 0) {
            const sample = unavailable.slice(0, 3).join(', ') + (unavailable.length > 3 ? ` (+${unavailable.length - 3} lainnya)` : '');
            triggerToast('SN Tidak Tersedia', `Terdapat ${unavailable.length} SN yang tidak tersedia atau belum lolos penerimaan fisik gudang: ${sample}. Hanya SN yang telah lolos seluruh alur dan penerimaan gudang yang dapat disimpan.`, 'error');
            return;
          }

          isSavingStandaloneLabel.value = true;
          try {
            const labelDate = labelForm.value.date || getNowWIBDateString();
            const payload = {
              package_type: labelForm.value.package_type,
              batch_number: labelForm.value.batch_number,
              product_name: labelForm.value.product_name || doSettings.value.default_product_name || 'Nanggala P9',
              notes: labelForm.value.notes,
              crates: labelForm.value.crates,
              date: labelDate,
              pl_number: plGeneratedId.value || labelForm.value.pl_number,
              pl_mode: plMode.value || (labelDate === getNowWIBDateString() ? 'realtime' : 'backdate'),
              is_backdate: plMode.value === 'backdate' || (labelDate < getNowWIBDateString())
            };
            const res = await fetchAPI('/packing-labels', {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (res && (res.ok || res.success)) {
              triggerToast('Sukses Disimpan', res.message || 'Label Packing List berhasil disimpan!', 'success');

              const savedBatch = payload.batch_number;
              const savedDoc = res.pl_number || res.label_batch_id;
              const printObj = {
                id: res.id,
                do_number: savedDoc,
                do_date: formatIndoDate(labelDate),
                program_name: doSettings.value.default_program_name || 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
                contract_number: doSettings.value.default_contract_number || 'TRAK/2509/APBN/XI/2025/KEMHAN',
                product_name: payload.product_name,
                customer_name: doSettings.value.default_customer_name || 'KEMHAN RI',
                customer_address: doSettings.value.default_customer_address || 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
                package_type: payload.package_type,
                crates: res.crates || payload.crates
              };

              lastSavedPLInfo.value = {
                pl_number: savedDoc,
                batch_number: savedBatch,
                total_containers: (payload.crates || []).length,
                total_qty: (payload.crates || []).reduce((acc, c) => acc + (c.total_quantity || 0), 0),
                saved_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
              };

              await loadStandaloneLabels();
              if (typeof loadAvailableFinishedSNs === 'function') {
                loadAvailableFinishedSNs();
              }

              // Otomatis reset form dari awal dengan batch berikutnya
              const nextBatch = res.next_batch || (parseInt(savedBatch) + 1) || 6;
              await resetLabelForm(nextBatch);

              if (printType) {
                openDODetailAndPrint(printObj, printType);
              }
            } else {
              triggerToast('Gagal', res?.error || 'Gagal menyimpan label.', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSavingStandaloneLabel.value = false;
          }
        };

        const printStandaloneLabel = (labelObj, printType = 'label_peti') => {
          let crates = [];
          if (Array.isArray(labelObj.crates) && labelObj.crates.length > 0) {
            crates = labelObj.crates;
          } else if (typeof labelObj.crates_json === 'string') {
            try { crates = JSON.parse(labelObj.crates_json); } catch (e) { crates = []; }
          }
          const printObj = {
            id: labelObj.id,
            do_number: labelObj.pl_number || labelObj.label_batch_id,
            do_date: labelObj.created_at ? labelObj.created_at.substring(0, 10) : formatIndoDate(),
            program_name: labelObj.program_name || doSettings.value.default_program_name || 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
            contract_number: labelObj.contract_number || doSettings.value.default_contract_number || 'TRAK/2509/APBN/XI/2025/KEMHAN',
            product_name: labelObj.product_name || doSettings.value.default_product_name || 'Nanggala P9',
            customer_name: labelObj.customer_name || doSettings.value.default_customer_name || 'KEMHAN RI',
            customer_address: labelObj.customer_address || doSettings.value.default_customer_address || 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
            package_type: labelObj.package_type || 'crate',
            crates: crates
          };
          openDODetailAndPrint(printObj, printType);
        };

        const deleteStandaloneLabel = async (id, labelBatchId) => {
          const ok = await showConfirmCard({
            title: 'Hapus Label Packing List',
            message: `Apakah Anda yakin ingin menghapus arsip label "${labelBatchId}"?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus Label',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const res = await fetchAPI('/packing-labels/delete', {
              method: 'POST',
              body: JSON.stringify({ id })
            });
            if (res && (res.ok || res.success)) {
              triggerToast('Sukses', 'Label Packing List berhasil dihapus!', 'success');
              loadStandaloneLabels();
            } else {
              triggerToast('Gagal', res?.error || 'Gagal menghapus label.', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const applySettingsToNewDOForm = () => {
          if (doSettings.value) {
            doForm.value.customer_name = doSettings.value.default_customer_name || '';
            doForm.value.customer_address = doSettings.value.default_customer_address || '';
            doForm.value.contract_number = doSettings.value.default_contract_number || '';
            doForm.value.program_name = doSettings.value.default_program_name || '';
            doSubTab.value = 'form';
            goToFormStep('customer');
            triggerToast('Pengaturan Diterapkan', 'Data default instansi dan kontrak berhasil diisikan ke form DO.', 'success');
          }
        };

        const loadDOPreviewData = async (doId) => {
          if (!doId) return;
          isLoadingPreviewDO.value = true;
          updateRealtimePrintDate();
          try {
            const res = await fetchAPI(`/delivery-orders/detail?id=${doId}`);
            if (res && (res.data || res.delivery_order)) {
              previewDOData.value = res.data || res.delivery_order;
              if (previewCrateIndex.value >= (previewDOData.value.crates?.length || 1)) {
                previewCrateIndex.value = 0;
              }
              if (previewPackingListSheetIndex.value >= (previewPackingListSheets.value.length || 1)) {
                previewPackingListSheetIndex.value = 0;
              }
              if (previewDOData.value?.package_type === 'unit' && (previewDocType.value === 'packing_list' || previewDocType.value === 'label_peti' || previewDocType.value === 'label_dus')) {
                previewDocType.value = 'label_sn';
              }
              nextTick(() => {
                if (previewDocType.value === 'surat_jalan') {
                  renderDOTrackingQRCode('do-sj-qr-preview', previewDOData.value);
                } else if (previewDocType.value === 'label_sn') {
                  const snList = getDOSerialNumbers(previewDOData.value);
                  snList.forEach((sn, idx) => {
                    renderSNLabelQRCode('do-sn-qr-preview-' + idx, sn);
                  });
                }
              });
            }
          } catch (e) {
            console.error('Failed to load preview DO data:', e);
          } finally {
            isLoadingPreviewDO.value = false;
          }
        };

        const openDODocumentPreview = (doId, docType = 'surat_jalan') => {
          updateRealtimePrintDate();
          previewDOId.value = doId;
          previewDocType.value = docType;
          previewCrateIndex.value = 0;
          previewPackingListSheetIndex.value = 0;
          loadDOPreviewData(doId).then(() => {
            if (previewDOData.value?.package_type === 'unit' && (docType === 'packing_list' || docType === 'label_peti' || docType === 'label_dus')) {
              previewDocType.value = 'label_sn';
            }
            nextTick(() => {
              if (previewDocType.value === 'surat_jalan') {
                renderDOTrackingQRCode('do-sj-qr-preview', previewDOData.value);
              } else if (previewDocType.value === 'label_sn') {
                const snList = getDOSerialNumbers(previewDOData.value);
                snList.forEach((sn, idx) => {
                  renderSNLabelQRCode('do-sn-qr-preview-' + idx, sn);
                });
              }
            });
          });
          doSubTab.value = 'preview';
        };

        watch(previewDocType, (newVal) => {
          if (newVal === 'surat_jalan') {
            nextTick(() => {
              renderDOTrackingQRCode('do-sj-qr-preview', previewDOData.value);
            });
          } else if (newVal === 'label_sn') {
            nextTick(() => {
              const snList = getDOSerialNumbers(previewDOData.value);
              snList.forEach((sn, idx) => {
                renderSNLabelQRCode('do-sn-qr-preview-' + idx, sn);
              });
            });
          }
        });
        const doList = ref([]);
        const doSearchText = ref('');
        const doSearchSN = ref('');
        const doStartDate = ref('');
        const doEndDate = ref('');
        const doDocFilter = ref('');
        const doSortOrder = ref('desc'); // 'desc' = terbaru paling atas, 'asc' = terlama
        const doSortField = ref('date'); // 'date' | 'do_number'
        const isTrackingSN = ref(false);
        const snTrackingResult = ref(null);

        const distinctDODocNumbers = computed(() => {
          const set = new Set();
          (doList.value || []).forEach(d => {
            if (d.do_number) set.add(d.do_number);
          });
          return Array.from(set).sort();
        });

        const resetDOFilter = () => {
          doSearchText.value = '';
          doSearchSN.value = '';
          doStartDate.value = '';
          doEndDate.value = '';
          doDocFilter.value = '';
          snTrackingResult.value = null;
          if (pgState.doHistory) pgState.doHistory.page = 1;
        };

        const toggleDOSortOrder = () => {
          doSortOrder.value = doSortOrder.value === 'desc' ? 'asc' : 'desc';
        };

        const toggleDOSortBy = (field) => {
          if (doSortField.value === field) {
            doSortOrder.value = doSortOrder.value === 'desc' ? 'asc' : 'desc';
          } else {
            doSortField.value = field;
            doSortOrder.value = 'desc';
          }
        };

        const trackSerialNumberDirect = async () => {
          const rawSn = (doSearchSN.value || '').trim();
          if (!rawSn) {
            triggerToast('Perhatian', 'Ketikkan Serial Number Part 15 yang ingin dilacak.', 'warn');
            return;
          }

          const sn = rawSn.toUpperCase();
          isTrackingSN.value = true;
          snTrackingResult.value = null;

          // 1. Cek dulu di in-memory doList
          let foundLocally = null;
          for (const d of (doList.value || [])) {
            const crates = Array.isArray(d.crates) ? d.crates : (Array.isArray(d.packing_lists) ? d.packing_lists : []);
            for (const c of crates) {
              const boxes = Array.isArray(c.boxes) ? c.boxes : [];
              for (const b of boxes) {
                const items = Array.isArray(b.items) ? b.items : [];
                for (const it of items) {
                  const itSn = (typeof it === 'object' && it !== null) ? (it.serial_number || '') : String(it);
                  if (itSn && itSn.toUpperCase().includes(sn)) {
                    let dSeq = 1;
                    if (d.do_number) {
                      const m = String(d.do_number).match(/^0*(\d+)/);
                      if (m) dSeq = parseInt(m[1], 10) || 1;
                    }
                    foundLocally = {
                      serial_number: itSn,
                      slot_number: it.slot_number || 1,
                      delivery_id: d.id,
                      do_number: d.do_number,
                      do_date: d.do_date,
                      delivery_seq: dSeq,
                      customer_name: d.customer_name,
                      contract_number: d.contract_number,
                      program_name: d.program_name,
                      crate_number: c.crate_number || 1,
                      crate_id: c.crate_id || '-',
                      box_number: b.box_number || 1,
                      box_id: b.box_id || '-',
                      pl_number: c.pl_number || d.pl_number,
                      status: d.status || 'Terkirim'
                    };
                    break;
                  }
                }
                if (foundLocally) break;
              }
              if (foundLocally) break;
            }
            if (foundLocally) break;
          }

          if (foundLocally) {
            snTrackingResult.value = foundLocally;
            isTrackingSN.value = false;
            triggerToast('SN Ditemukan!', `Serial Number ${sn} ditemukan di Pengiriman ke-${foundLocally.delivery_seq}, Peti ${foundLocally.crate_id}, Dus ${foundLocally.box_id}.`, 'success');
            return;
          }

          // 2. Jika tidak ada di in-memory, query ke endpoint backend
          try {
            const res = await fetchAPI(`/delivery-orders/track-sn?sn=${encodeURIComponent(sn)}`);
            if (res && res.found && res.data) {
              snTrackingResult.value = res.data;
              triggerToast('SN Ditemukan!', `Serial Number ${sn} ditemukan di Pengiriman ke-${res.data.delivery_seq}, Peti ${res.data.crate_id}, Dus ${res.data.box_id}.`, 'success');
            } else {
              snTrackingResult.value = null;
              triggerToast('Tidak Ditemukan', res?.message || `Serial Number ${sn} belum dialokasikan pada pengiriman manapun.`, 'info');
            }
          } catch (err) {
            triggerToast('Error Pelacakan', 'Terjadi kendala saat melacak Serial Number: ' + err.message, 'error');
          } finally {
            isTrackingSN.value = false;
          }
        };
        const customersList = ref([]);
        const isCustomerDropdownOpen = ref(false);
        const customerDropdownRef = ref(null);
        const showNewCustomerModal = ref(false);
        const newCustomerForm = ref({
          name: '',
          address: '',
          phone: '',
          fax: '',
          notes: ''
        });
        const isSubmittingCustomer = ref(false);
        const customerSearchQuery = ref('');
        const availableFinishedSNs = ref([]);
        const isSubmittingDO = ref(false);
        const showDOPostPublishModal = ref(false);
        const lastPublishedDO = ref(null);
        const selectedDOForDetail = ref(null);
        const isLoadingDODetail = ref(false);

        // Form Cards Sequential Stepper State
        const activeFormStep = ref('');
        const completedSteps = ref(new Set());
        const openFormCards = ref({
          customer: false,
          reference: false,
          batch: false,
          allocation: false
        });

        const isFormCardOpen = (cardKey) => {
          return !!openFormCards.value[cardKey];
        };

        const isStepValid = (stepKey) => {
          if (stepKey === 'customer') {
            return !!(doForm.value.customer_name && doForm.value.customer_name.trim() &&
              doForm.value.customer_address && doForm.value.customer_address.trim());
          }
          if (stepKey === 'reference') {
            return isStepValid('customer') &&
              !!(doForm.value.contract_number && doForm.value.contract_number.trim() &&
                doForm.value.program_name && doForm.value.program_name.trim());
          }
          if (stepKey === 'batch') {
            return isStepValid('reference') &&
              (parseInt(doForm.value.package_count) >= 1) &&
              (parseInt(doForm.value.batch_number) >= 1);
          }
          if (stepKey === 'allocation') {
            return isStepValid('batch');
          }
          return false;
        };

        const isStepCompleted = (stepKey) => {
          return completedSteps.value.has(stepKey) || isStepValid(stepKey);
        };

        const canAccessStep = (stepKey) => {
          if (stepKey === 'customer') return true;
          if (stepKey === 'reference') return isStepCompleted('customer') || isStepValid('customer');
          if (stepKey === 'batch') return (isStepCompleted('reference') || isStepValid('reference')) && isStepValid('customer');
          if (stepKey === 'allocation') return (isStepCompleted('batch') || isStepValid('batch')) && isStepValid('reference') && isStepValid('customer');
          return false;
        };

        const goToFormStep = (targetStep) => {
          if (targetStep === 'reference') {
            if (!doForm.value.customer_name || !doForm.value.customer_name.trim()) {
              triggerToast('Data Belum Lengkap', 'Nama Customer wajib diisi sebelum lanjut.', 'warn');
              return;
            }
            if (!doForm.value.customer_address || !doForm.value.customer_address.trim()) {
              triggerToast('Data Belum Lengkap', 'Alamat Pengiriman wajib diisi sebelum lanjut.', 'warn');
              return;
            }
            completedSteps.value.add('customer');
          } else if (targetStep === 'batch') {
            if (!doForm.value.contract_number || !doForm.value.contract_number.trim()) {
              triggerToast('Data Belum Lengkap', 'Nomor Referensi/Kontrak wajib diisi sebelum lanjut.', 'warn');
              return;
            }
            if (!doForm.value.program_name || !doForm.value.program_name.trim()) {
              triggerToast('Data Belum Lengkap', 'Nama Program Pengadaan wajib diisi sebelum lanjut.', 'warn');
              return;
            }
            completedSteps.value.add('customer');
            completedSteps.value.add('reference');
          } else if (targetStep === 'allocation') {
            if (sjDuplicateWarning.value) {
              triggerToast('Peringatan ID Duplikat', 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.', 'error');
              return;
            }
            if (!doForm.value.package_count || doForm.value.package_count < 1) {
              triggerToast('Data Belum Lengkap', 'Jumlah pengemasan harus minimal 1.', 'warn');
              return;
            }
            if (!doForm.value.batch_number || doForm.value.batch_number < 1) {
              triggerToast('Data Belum Lengkap', 'Nomor batch pengiriman harus minimal 1.', 'warn');
              return;
            }
            completedSteps.value.add('customer');
            completedSteps.value.add('reference');
            completedSteps.value.add('batch');
            syncPackagingStructure();
          }

          activeFormStep.value = targetStep;
          openFormCards.value = {
            customer: targetStep === 'customer',
            reference: targetStep === 'reference',
            batch: targetStep === 'batch',
            allocation: targetStep === 'allocation'
          };
        };

        const onCardHeaderClick = (cardKey) => {
          // Strictly locked: Perpindahan form hanya melalui tombol Lanjut & Kembali
        };

        const toggleFormCard = (cardKey) => {
          // Strictly locked: Perpindahan form hanya melalui tombol Lanjut & Kembali
        };

        const setAllFormCards = (isOpen) => {
          if (isOpen) {
            return;
          }
          resetDOFormSteps();
        };

        const resetDOFormSteps = () => {
          sjMode.value = '';
          activeFormStep.value = '';
          completedSteps.value.clear();
          openFormCards.value = {
            customer: false,
            reference: false,
            batch: false,
            allocation: false
          };
          activeContainerIndex.value = 0;
          selectedConsumedPLId.value = '';
          selectedConsumedPL.value = null;
          sjDeliverySeq.value = 1;
          sjGeneratedId.value = '';
          sjDuplicateWarning.value = false;
          sjDuplicateMessage.value = '';

          const s = doSettings.value || {};
          doForm.value = {
            do_number: '',
            do_date: getTodayISODate(),
            customer_name: s.default_customer_name || 'KEMHAN RI',
            customer_address: s.default_customer_address || 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
            customer_phone: s.default_customer_phone || '(021) 3840889',
            customer_fax: s.default_customer_fax || '(021) 3840889',
            contract_number: s.default_contract_number || 'TRAK/2509/APBN/XI/2025/KEMHAN',
            program_name: s.default_program_name || 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
            product_name: s.default_product_name || 'Nanggala P9',
            issued_by_name: s.default_issuer_name || 'Rezka Rachdana',
            issued_by_title: s.default_issuer_title || 'Kepala Departemen',
            issued_by_dept: s.default_issuer_department || 'Logistik & PPIC',
            package_type: 'crate',
            package_count: 1,
            total_quantity: 20,
            batch_number: s.current_batch_number || 1,
            notes: '',
            crates: []
          };
          loadNextDOMeta();
        };

        const productTypesList = ref([
          'Nanggala P9',
          'Nanggala P9 Gen-2',
          'Nanggala P9 Tactical'
        ]);

        const getTodayISODate = () => {
          const now = new Date();
          const y = now.getFullYear();
          const m = String(now.getMonth() + 1).padStart(2, '0');
          const d = String(now.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
        };

        const formatNumericDate = (dateStr) => {
          if (!dateStr) return '';
          const s = String(dateStr).trim();
          const isoMatch = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
          if (isoMatch) {
            const d = isoMatch[3].padStart(2, '0');
            const m = isoMatch[2].padStart(2, '0');
            const y = isoMatch[1];
            return `${d}/${m}/${y}`;
          }
          const monthMap = {
            'januari': '01', 'january': '01', 'jan': '01',
            'februari': '02', 'february': '02', 'feb': '02',
            'maret': '03', 'march': '03', 'mar': '03',
            'april': '04', 'apr': '04',
            'mei': '05', 'may': '05',
            'juni': '06', 'june': '06', 'jun': '06',
            'juli': '07', 'july': '07', 'jul': '07',
            'agustus': '08', 'august': '08', 'ags': '08', 'aug': '08',
            'september': '09', 'sep': '09', 'sept': '09',
            'oktober': '10', 'october': '10', 'okt': '10', 'oct': '10',
            'november': '11', 'nov': '11',
            'desember': '12', 'december': '12', 'des': '12', 'dec': '12'
          };
          const parts = s.split(/[\s,]+/);
          if (parts.length >= 3) {
            const day = parts[0].replace(/\D/g, '').padStart(2, '0');
            const mKey = parts[1].toLowerCase();
            const month = monthMap[mKey] || mKey.padStart(2, '0');
            const year = parts[2].replace(/\D/g, '');
            if (day && month && year) {
              return `${day}/${month}/${year}`;
            }
          }
          return s;
        };

        const generateDefaultDONumber = (dateStr = null) => {
          const now = dateStr ? new Date(dateStr) : new Date();
          const mm = String(isNaN(now.getMonth()) ? (new Date().getMonth() + 1) : (now.getMonth() + 1)).padStart(2, '0');
          const yyyy = isNaN(now.getFullYear()) ? new Date().getFullYear() : now.getFullYear();
          return `001/SJ/PPIC/${mm}/${yyyy}`;
        };

        const doForm = ref({
          do_number: generateDefaultDONumber(),
          do_date: getTodayISODate(),
          customer_name: 'KEMHAN RI',
          customer_address: 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
          customer_phone: '(021) 3840889',
          customer_fax: '(021) 3840889',
          contract_number: 'TRAK/2509/APBN/XI/2025/KEMHAN',
          program_name: 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
          product_name: 'Nanggala P9',
          issued_by_name: 'Rezka Rachdana',
          issued_by_title: 'Kepala Departemen',
          issued_by_dept: 'Logistik & PPIC',
          package_type: 'crate',
          package_count: 1,
          total_quantity: 20,
          batch_number: 1,
          crates: []
        });

        // Consumed Packing List State (Penggunaan Packing List yang Sudah Ada ke DO)
        const selectedConsumedPLId = ref('');
        const selectedConsumedPL = ref(null);
        const isLoadingConsumedPL = ref(false);

        const applyConsumedPackingList = async () => {
          if (!selectedConsumedPLId.value) {
            selectedConsumedPL.value = null;
            delete doForm.value.consumed_standalone_id;
            return;
          }
          isLoadingConsumedPL.value = true;
          try {
            const res = await fetchAPI(`/packing-labels/detail?id=${selectedConsumedPLId.value}`);
            if (res && res.data) {
              const plData = res.data;
              const currentSjMode = sjMode.value || (doForm.value?.is_backdate ? 'backdate' : 'realtime');
              const plModeVal = plData.pl_mode || (plData.is_backdate ? 'backdate' : 'realtime');

              // Aturan 2: Single Consume
              if (plData.is_consumed || plData.delivery_order_id) {
                triggerToast('PL Tidak Valid', `Packing List ${plData.pl_number || plData.label_batch_id} sudah pernah digunakan pada Surat Jalan lain dan tidak dapat dikonsumsi lagi.`, 'error');
                selectedConsumedPLId.value = '';
                selectedConsumedPL.value = null;
                delete doForm.value.consumed_standalone_id;
                return;
              }

              // Aturan 3: Syarat Jenis Mode Sama
              if (currentSjMode && plModeVal !== currentSjMode) {
                triggerToast('Mode Tidak Cocok', `Packing List bertipe ${plModeVal} tidak dapat dikonsumsi oleh Surat Jalan bertipe ${currentSjMode}. Syarat consume adalah jenis mode harus sama.`, 'error');
                selectedConsumedPLId.value = '';
                selectedConsumedPL.value = null;
                delete doForm.value.consumed_standalone_id;
                return;
              }

              selectedConsumedPL.value = plData;
              doForm.value.consumed_standalone_id = plData.id;
              doForm.value.package_type = plData.package_type || 'crate';
              doForm.value.batch_number = plData.batch_number || 1;
              doForm.value.package_count = plData.total_containers || (Array.isArray(plData.crates) ? plData.crates.length : 1);
              doForm.value.total_quantity = plData.total_quantity || 0;
              if (plData.product_name) {
                doForm.value.product_name = plData.product_name;
              }
              if (Array.isArray(plData.crates) && plData.crates.length > 0) {
                const pkgType = doForm.value.package_type || 'crate';
                const pkgCount = doForm.value.package_count;
                const batchNum = doForm.value.batch_number || 1;
                const newStructure = buildPackagingStructure(pkgType, pkgCount, batchNum, 1, doForm.value.do_date);

                newStructure.forEach((targetC, cIdx) => {
                  const sourceC = plData.crates[cIdx];
                  if (!sourceC) return;
                  targetC.consumed_source_crate_id = sourceC.crate_id;
                  targetC.consumed_from_pl = plData.pl_number || plData.label_batch_id;
                  targetC.consumed_pl_id = plData.id;
                  if (pkgType === 'box') {
                    targetC.consumed_source_box_id = sourceC.box_id || sourceC.boxes?.[0]?.box_id;
                  }
                  targetC.boxes.forEach((targetB, bIdx) => {
                    const sourceB = sourceC.boxes?.[bIdx];
                    if (!sourceB) return;
                    targetB.consumed_source_box_id = sourceB.box_id;
                    targetB.consumed_from_pl = plData.pl_number || plData.label_batch_id;
                    targetB.consumed_pl_id = plData.id;
                    targetB.items = (sourceB.items || []).map(it => ({
                      slot_number: it.slot_number,
                      serial_number: it.serial_number || '',
                      is_from_pl: true
                    }));
                  });
                });
                doForm.value.crates = newStructure;
              }
              activeContainerIndex.value = 0;
              triggerToast('Packing List Terpasang', `Berhasil memuat ${plData.total_quantity} unit dari Packing List ${plData.pl_number || plData.label_batch_id}`, 'success');
            } else {
              triggerToast('Gagal Memuat', 'Data Packing List tidak ditemukan', 'error');
            }
          } catch (e) {
            console.error('Error applying consumed PL:', e);
            triggerToast('Gagal', 'Terjadi kesalahan saat memuat data Packing List.', 'error');
          } finally {
            isLoadingConsumedPL.value = false;
          }
        };

        const resetConsumedPackingList = () => {
          selectedConsumedPLId.value = '';
          selectedConsumedPL.value = null;
          delete doForm.value.consumed_standalone_id;
          syncPackagingStructure();
          triggerToast('Direset', 'Alokasi Serial Number dikembalikan ke mode manual.', 'info');
        };

        // Container Selection from Standalone PL State & Functions
        const showSelectCrateModal = ref(false);
        const showSelectBoxModal = ref(false);
        const selectTarget = ref({ crateIdx: 0, boxNumber: 1, isBoxDO: false });
        const selectCrateSearchQuery = ref('');
        const selectBoxSearchQuery = ref('');

        const getAvailablePLCratesList = () => {
          const list = [];
          const currentSjMode = sjMode.value || (doForm.value?.is_backdate ? 'backdate' : 'realtime');

          (standaloneLabelsList.value || []).forEach(pl => {
            // Aturan 2: Dokumen PL yang sudah terpakai (is_consumed) hanya bisa di-consume 1 kali
            if (pl.is_consumed || pl.delivery_order_id) {
              return;
            }

            // Aturan 3: Hak consume PL untuk SJ: Jenis mode harus sama (backdate -> backdate, realtime -> realtime)
            const plModeVal = pl.pl_mode || (pl.is_backdate ? 'backdate' : 'realtime');
            if (currentSjMode && plModeVal !== currentSjMode) {
              return;
            }

            if (pl.package_type === 'crate') {
              let crates = [];
              if (Array.isArray(pl.crates) && pl.crates.length > 0) {
                crates = pl.crates;
              } else if (typeof pl.crates_json === 'string') {
                try { crates = JSON.parse(pl.crates_json); } catch (_) { }
              }
              crates.forEach((c, cIdx) => {
                list.push({
                  pl_id: pl.id,
                  pl_number: pl.pl_number || pl.label_batch_id,
                  pl_mode: plModeVal,
                  batch_number: pl.batch_number,
                  product_name: pl.product_name,
                  crate_id: c.crate_id,
                  crate_number: c.crate_number || (cIdx + 1),
                  boxes: c.boxes || [],
                  total_quantity: c.total_quantity || 20,
                  filled_items: (c.boxes || []).reduce((acc, b) => acc + (b.items || []).filter(it => it.serial_number && String(it.serial_number).trim()).length, 0)
                });
              });
            }
          });
          return list;
        };

        const filteredAvailablePLCratesList = computed(() => {
          const q = (selectCrateSearchQuery.value || '').trim().toLowerCase();
          const all = getAvailablePLCratesList();
          if (!q) return all;
          return all.filter(c =>
            (c.crate_id && c.crate_id.toLowerCase().includes(q)) ||
            (c.pl_number && c.pl_number.toLowerCase().includes(q)) ||
            (c.product_name && c.product_name.toLowerCase().includes(q))
          );
        });

        const isPLCrateUsed = (crateId, currentCrateIdx) => {
          if (!crateId) return false;
          return (doForm.value.crates || []).some((c, idx) => {
            if (idx === currentCrateIdx) return false;
            return Boolean(c.consumed_source_crate_id && c.consumed_source_crate_id === crateId);
          });
        };

        const openSelectCrateModal = (crateIdx) => {
          selectCrateSearchQuery.value = '';
          selectTarget.value = { crateIdx, boxNumber: 1, isBoxDO: false };
          loadStandaloneLabels();
          showSelectCrateModal.value = true;
        };

        const applySelectedCrateToDO = (plCrate) => {
          const cIdx = selectTarget.value.crateIdx;
          if (!doForm.value.crates || !doForm.value.crates[cIdx]) return;

          const targetCrate = doForm.value.crates[cIdx];
          // Tetap gunakan ID Peti milik Surat Jalan (mengikuti nomor Peti di SJ)
          const sjCrateId = targetCrate.crate_id;
          targetCrate.consumed_source_crate_id = plCrate.crate_id;
          targetCrate.consumed_from_pl = plCrate.pl_number;
          targetCrate.consumed_pl_id = plCrate.pl_id;

          // Salin boxes dan SN dari PL, namun box_id otomatis mengacu ke sjCrateId
          targetCrate.boxes = (plCrate.boxes || []).map((b, bIdx) => {
            const bNum = b.box_number || (bIdx + 1);
            return {
              box_number: bNum,
              box_id: `${sjCrateId}-${String(bNum).padStart(2, '0')}`,
              consumed_source_box_id: b.box_id || `${plCrate.crate_id}-${String(bNum).padStart(2, '0')}`,
              consumed_from_pl: plCrate.pl_number,
              consumed_pl_id: plCrate.pl_id,
              items: (b.items || []).map(it => ({
                slot_number: it.slot_number,
                serial_number: it.serial_number || '',
                is_from_pl: true
              }))
            };
          });

          showSelectCrateModal.value = false;
          triggerToast('Peti Terpilih', `Data Peti ${plCrate.crate_id} berhasil dialokasikan ke Peti ${cIdx + 1} (${sjCrateId})!`, 'success');
        };

        const getAvailablePLBoxesList = () => {
          const list = [];
          const currentSjMode = sjMode.value || (doForm.value?.is_backdate ? 'backdate' : 'realtime');

          (standaloneLabelsList.value || []).forEach(pl => {
            // Aturan 2: Dokumen PL yang sudah terpakai (is_consumed) hanya bisa di-consume 1 kali
            if (pl.is_consumed || pl.delivery_order_id) {
              return;
            }

            // Aturan 3: Hak consume PL untuk SJ: Jenis mode harus sama (backdate -> backdate, realtime -> realtime)
            const plModeVal = pl.pl_mode || (pl.is_backdate ? 'backdate' : 'realtime');
            if (currentSjMode && plModeVal !== currentSjMode) {
              return;
            }

            let crates = [];
            if (Array.isArray(pl.crates) && pl.crates.length > 0) {
              crates = pl.crates;
            } else if (typeof pl.crates_json === 'string') {
              try { crates = JSON.parse(pl.crates_json); } catch (_) { }
            }
            crates.forEach((c, cIdx) => {
              (c.boxes || []).forEach((b, bIdx) => {
                const boxId = b.box_id || c.box_id || `${c.crate_id || 'BOX'}-${String(bIdx + 1).padStart(2, '0')}`;
                list.push({
                  pl_id: pl.id,
                  pl_number: pl.pl_number || pl.label_batch_id,
                  pl_mode: plModeVal,
                  batch_number: pl.batch_number,
                  product_name: pl.product_name,
                  package_type: pl.package_type,
                  parent_crate_id: pl.package_type === 'crate' ? c.crate_id : null,
                  box_id: boxId,
                  box_number: b.box_number || (bIdx + 1),
                  items: b.items || [],
                  filled_items: (b.items || []).filter(it => it.serial_number && String(it.serial_number).trim()).length
                });
              });
            });
          });
          return list;
        };

        const filteredAvailablePLBoxesList = computed(() => {
          const q = (selectBoxSearchQuery.value || '').trim().toLowerCase();
          const all = getAvailablePLBoxesList();
          if (!q) return all;
          return all.filter(b =>
            (b.box_id && b.box_id.toLowerCase().includes(q)) ||
            (b.pl_number && b.pl_number.toLowerCase().includes(q)) ||
            (b.parent_crate_id && b.parent_crate_id.toLowerCase().includes(q)) ||
            (b.product_name && b.product_name.toLowerCase().includes(q)) ||
            (b.items || []).some(it => it.serial_number && String(it.serial_number).toLowerCase().includes(q))
          );
        });

        const isPLBoxUsed = (sourceBoxId, currentCrateIdx, currentBoxNumber, isBoxDO) => {
          if (!sourceBoxId) return false;
          for (let cIdx = 0; cIdx < (doForm.value.crates || []).length; cIdx++) {
            const c = doForm.value.crates[cIdx];
            for (let b of (c.boxes || [])) {
              if (isBoxDO) {
                if (cIdx !== currentCrateIdx && Boolean(b.consumed_source_box_id && b.consumed_source_box_id === sourceBoxId)) {
                  return true;
                }
              } else {
                if ((cIdx !== currentCrateIdx || b.box_number !== currentBoxNumber) && Boolean(b.consumed_source_box_id && b.consumed_source_box_id === sourceBoxId)) {
                  return true;
                }
              }
            }
          }
          return false;
        };

        const openSelectBoxModal = (crateIdx, boxNumber, isBoxDO = false) => {
          selectBoxSearchQuery.value = '';
          selectTarget.value = { crateIdx, boxNumber, isBoxDO };
          loadStandaloneLabels();
          showSelectBoxModal.value = true;
        };

        const applySelectedBoxToDO = (plBox) => {
          const { crateIdx, boxNumber, isBoxDO } = selectTarget.value;
          if (!doForm.value.crates || !doForm.value.crates[crateIdx]) return;

          const targetCrate = doForm.value.crates[crateIdx];

          if (isBoxDO) {
            // DO Kemasan Dus: Tetap gunakan ID Dus milik Surat Jalan (mengikuti urutan Dus di SJ)
            const sjBoxId = targetCrate.box_id || targetCrate.boxes?.[0]?.box_id;
            targetCrate.consumed_source_box_id = plBox.box_id;
            targetCrate.consumed_from_pl = plBox.pl_number;
            targetCrate.consumed_pl_id = plBox.pl_id;
            if (targetCrate.boxes && targetCrate.boxes[0]) {
              targetCrate.boxes[0].box_id = sjBoxId;
              targetCrate.boxes[0].consumed_source_box_id = plBox.box_id;
              targetCrate.boxes[0].consumed_from_pl = plBox.pl_number;
              targetCrate.boxes[0].consumed_pl_id = plBox.pl_id;
              targetCrate.boxes[0].items = (plBox.items || []).map(it => ({
                slot_number: it.slot_number,
                serial_number: it.serial_number || '',
                is_from_pl: true
              }));
            }
            showSelectBoxModal.value = false;
            triggerToast('Dus Terpilih', `Data Dus ${plBox.box_id} berhasil dialokasikan ke Dus ${crateIdx + 1} (${sjBoxId})!`, 'success');
          } else {
            // DO Kemasan Peti (Dus 1 atau Dus 2)
            const targetBoxIdx = Math.max(0, boxNumber - 1);
            if (!targetCrate.boxes || !targetCrate.boxes[targetBoxIdx]) return;
            const targetBox = targetCrate.boxes[targetBoxIdx];

            // ID Dus otomatis menyesuaikan dengan ID Peti: ${crate_id}-01 atau -02
            const newBoxId = `${targetCrate.crate_id}-${String(boxNumber).padStart(2, '0')}`;
            targetBox.box_id = newBoxId;
            targetBox.consumed_source_box_id = plBox.box_id;
            targetBox.consumed_from_pl = plBox.pl_number;
            targetBox.consumed_pl_id = plBox.pl_id;
            targetBox.items = (plBox.items || []).map(it => ({
              slot_number: it.slot_number,
              serial_number: it.serial_number || '',
              is_from_pl: true
            }));

            showSelectBoxModal.value = false;
            triggerToast('Dus Terpilih', `Dus ${plBox.box_id} dimasukkan ke Peti ${crateIdx + 1} sebagai ${newBoxId}!`, 'success');
          }
        };

        // Batch Paste Modal State
        const showBatchPasteModal = ref(false);
        const batchPasteRawText = ref('');
        const batchPasteTarget = ref({ crateIdx: 0, boxNum: 1, isUnit: false });

        // Print DO state
        const activePrintDO = ref(null);
        const activePrintType = ref('surat_jalan');

        // Card 4 Container Stepper State
        const activeContainerIndex = ref(0);

        const getBoxFilledCount = (box) => {
          if (!box || !box.items) return 0;
          return box.items.filter(it => it.serial_number && String(it.serial_number).trim()).length;
        };

        const isBoxComplete = (box) => {
          return getBoxFilledCount(box) === 10;
        };

        const getCrateFilledCount = (crate) => {
          if (!crate) return 0;
          let count = 0;
          (crate.boxes || []).forEach(b => {
            (b.items || []).forEach(it => {
              if (it && it.serial_number && String(it.serial_number).trim()) {
                count++;
              }
            });
          });
          return count;
        };

        const isBoxIndexLocked = (bIdx) => {
          if (bIdx === 0) return false;
          const boxes = getAllBoxesInForm();
          const prevBox = boxes[bIdx - 1];
          return !prevBox || !isBoxComplete(prevBox);
        };

        const isCrateComplete = (crate) => {
          return getCrateFilledCount(crate) === 20;
        };

        const isCrateIndexLocked = (cIdx) => {
          if (cIdx === 0) return false;
          const prevCrate = doForm.value.crates && doForm.value.crates[cIdx - 1];
          return !prevCrate || !isCrateComplete(prevCrate);
        };

        const getUnitBatches = () => {
          const allUnits = getAllUnitsInForm();
          const batches = [];
          for (let i = 0; i < allUnits.length; i += 10) {
            batches.push(allUnits.slice(i, i + 10));
          }
          return batches;
        };

        const getUnitBatchFilledCount = (batch) => {
          if (!batch) return 0;
          return batch.filter(it => it.serial_number && String(it.serial_number).trim()).length;
        };

        const isUnitBatchComplete = (batchIdx) => {
          const batches = getUnitBatches();
          const b = batches[batchIdx];
          if (!b || b.length === 0) return false;
          return b.every(it => it.serial_number && String(it.serial_number).trim());
        };

        const isUnitBatchLocked = (batchIdx) => {
          if (batchIdx === 0) return false;
          return !isUnitBatchComplete(batchIdx - 1);
        };

        const nextContainer = () => {
          if (doForm.value.package_type === 'box') {
            const boxes = getAllBoxesInForm();
            const cur = boxes[activeContainerIndex.value];
            if (!isBoxComplete(cur)) {
              triggerToast('Dus Belum Lengkap', 'Lengkapi 10 Serial Number pada Dus ini sebelum lanjut ke Dus berikutnya.', 'warn');
              return;
            }
            if (activeContainerIndex.value < boxes.length - 1) {
              activeContainerIndex.value++;
            }
          } else if (doForm.value.package_type === 'crate') {
            const crates = doForm.value.crates;
            const cur = crates[activeContainerIndex.value];
            if (!isCrateComplete(cur)) {
              triggerToast('Peti Belum Lengkap', 'Lengkapi 20 Serial Number pada Peti ini sebelum lanjut ke Peti berikutnya.', 'warn');
              return;
            }
            if (activeContainerIndex.value < crates.length - 1) {
              activeContainerIndex.value++;
            }
          } else {
            if (!isUnitBatchComplete(activeContainerIndex.value)) {
              triggerToast('Unit Belum Lengkap', 'Lengkapi Serial Number pada bagian ini sebelum lanjut ke bagian berikutnya.', 'warn');
              return;
            }
            const batches = getUnitBatches();
            if (activeContainerIndex.value < batches.length - 1) {
              activeContainerIndex.value++;
            }
          }
        };

        const prevContainer = () => {
          if (activeContainerIndex.value > 0) {
            activeContainerIndex.value--;
          }
        };

        const selectContainer = (idx) => {
          if (doForm.value.package_type === 'box') {
            if (isBoxIndexLocked(idx)) {
              triggerToast('Dus Terkunci', 'Selesaikan pengisian Dus sebelumnya terlebih dahulu.', 'warn');
              return;
            }
          } else if (doForm.value.package_type === 'crate') {
            if (isCrateIndexLocked(idx)) {
              triggerToast('Peti Terkunci', 'Selesaikan pengisian Peti sebelumnya terlebih dahulu.', 'warn');
              return;
            }
          } else {
            if (isUnitBatchLocked(idx)) {
              triggerToast('Bagian Terkunci', 'Selesaikan pengisian bagian sebelumnya terlebih dahulu.', 'warn');
              return;
            }
          }
          activeContainerIndex.value = idx;
        };

        const isDOSlotLocked = (items, sIdx) => {
          return false;
        };

        const autofillSingleBox = (boxIdx) => {
          const boxes = getAllBoxesInForm();
          const box = boxes[boxIdx];
          if (!box) return;

          const usedSNs = new Set();
          doForm.value.crates.forEach(c => {
            if (c.boxes) {
              c.boxes.forEach(b => {
                if (b.items) {
                  b.items.forEach(it => {
                    if (it.serial_number && it.serial_number.trim()) {
                      usedSNs.add(it.serial_number.trim().toUpperCase());
                    }
                  });
                }
              });
            }
          });

          const freeSNs = availableFinishedSNs.value.filter(s => !usedSNs.has(s.trim().toUpperCase()));
          let snIdx = 0;
          if (box.items) {
            box.items.forEach(it => {
              if (!it.serial_number || !it.serial_number.trim()) {
                if (snIdx < freeSNs.length) {
                  it.serial_number = freeSNs[snIdx++];
                }
              }
            });
          }

          triggerToast('Auto Fill Selesai', `Dus #${boxIdx + 1} telah diisi (${snIdx} SN dialokasikan).`, 'info');
        };

        const autofillSingleCrate = (crateIdx) => {
          const crate = doForm.value.crates && doForm.value.crates[crateIdx];
          if (!crate || !crate.boxes) return;

          const usedSNs = new Set();
          doForm.value.crates.forEach(c => {
            if (c.boxes) {
              c.boxes.forEach(b => {
                if (b.items) {
                  b.items.forEach(it => {
                    if (it.serial_number && it.serial_number.trim()) {
                      usedSNs.add(it.serial_number.trim().toUpperCase());
                    }
                  });
                }
              });
            }
          });

          const freeSNs = availableFinishedSNs.value.filter(s => !usedSNs.has(s.trim().toUpperCase()));
          let snIdx = 0;
          crate.boxes.forEach(b => {
            if (b.items) {
              b.items.forEach(it => {
                if (!it.serial_number || !it.serial_number.trim()) {
                  if (snIdx < freeSNs.length) {
                    it.serial_number = freeSNs[snIdx++];
                  }
                }
              });
            }
          });

          triggerToast('Auto Fill Selesai', `Peti #${crateIdx + 1} telah diisi (${snIdx} SN dialokasikan).`, 'info');
        };

        const autofillSingleUnitBatch = (batchIdx) => {
          const batches = getUnitBatches();
          const batch = batches[batchIdx];
          if (!batch) return;

          const usedSNs = new Set();
          doForm.value.crates.forEach(c => {
            if (c.boxes) {
              c.boxes.forEach(b => {
                if (b.items) {
                  b.items.forEach(it => {
                    if (it.serial_number && it.serial_number.trim()) {
                      usedSNs.add(it.serial_number.trim().toUpperCase());
                    }
                  });
                }
              });
            }
          });

          const freeSNs = availableFinishedSNs.value.filter(s => !usedSNs.has(s.trim().toUpperCase()));
          let snIdx = 0;
          batch.forEach(it => {
            if (!it.serial_number || !it.serial_number.trim()) {
              if (snIdx < freeSNs.length) {
                it.serial_number = freeSNs[snIdx++];
              }
            }
          });

          triggerToast('Auto Fill Selesai', `Bagian #${batchIdx + 1} telah diisi (${snIdx} SN dialokasikan).`, 'info');
        };

        const filteredCustomersList = computed(() => {
          const q = (customerSearchQuery.value || '').trim().toLowerCase();
          if (!q) return customersList.value;
          return customersList.value.filter(c =>
            (c.name && c.name.toLowerCase().includes(q)) ||
            (c.address && c.address.toLowerCase().includes(q))
          );
        });

        const getDODateKey = (item) => {
          if (!item) return '';
          if (item.do_date) {
            const str = String(item.do_date).trim();
            const matchIso = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (matchIso) {
              return `${matchIso[1]}-${matchIso[2]}-${matchIso[3]}`;
            }
            const months = {
              'januari': '01', 'februari': '02', 'maret': '03', 'april': '04',
              'mei': '05', 'juni': '06', 'juli': '07', 'agustus': '08',
              'september': '09', 'oktober': '10', 'november': '11', 'desember': '12'
            };
            const parts = str.toLowerCase().split(/\s+/);
            if (parts.length === 3 && months[parts[1]]) {
              const day = parts[0].padStart(2, '0');
              const month = months[parts[1]];
              const year = parts[2];
              return `${year}-${month}-${day}`;
            }
            const d = new Date(str);
            if (!isNaN(d.getTime())) {
              const y = d.getFullYear();
              const m = String(d.getMonth() + 1).padStart(2, '0');
              const day = String(d.getDate()).padStart(2, '0');
              return `${y}-${m}-${day}`;
            }
          }
          if (item.created_at) {
            const m = String(item.created_at).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) return `${m[1]}-${m[2]}-${m[3]}`;
          }
          return '';
        };

        const getDONumberSeq = (item) => {
          if (!item || !item.do_number) return 0;
          const match = String(item.do_number).trim().match(/^(\d+)/);
          return match ? parseInt(match[1], 10) : 0;
        };

        const filteredDOList = computed(() => {
          let list = (doList.value || []).slice();

          // 1. Doc DO Filter
          if (doDocFilter.value) {
            list = list.filter(d => d.do_number === doDocFilter.value);
          }

          // 2. Date Filter: Tanggal Dari
          if (doStartDate.value) {
            list = list.filter(d => {
              const dk = getDODateKey(d);
              return !dk || dk >= doStartDate.value;
            });
          }

          // 3. Date Filter: Tanggal Sampai
          if (doEndDate.value) {
            list = list.filter(d => {
              const dk = getDODateKey(d);
              return !dk || dk <= doEndDate.value;
            });
          }

          // 4. Search query (Nomor DO, Customer, Kontrak, Program, atau No PL)
          const q = (doSearchText.value || '').trim().toLowerCase();
          if (q) {
            list = list.filter(d =>
              (d.do_number && d.do_number.toLowerCase().includes(q)) ||
              (d.customer_name && d.customer_name.toLowerCase().includes(q)) ||
              (d.contract_number && d.contract_number.toLowerCase().includes(q)) ||
              (d.program_name && d.program_name.toLowerCase().includes(q)) ||
              (d.pl_number && d.pl_number.toLowerCase().includes(q))
            );
          }

          // 5. SN Filter jika operator memasukkan Serial Number Part 15
          const snQ = (doSearchSN.value || '').trim().toUpperCase();
          if (snQ) {
            list = list.filter(d => {
              const crates = Array.isArray(d.crates) ? d.crates : (Array.isArray(d.packing_lists) ? d.packing_lists : []);
              return crates.some(c => {
                const boxes = Array.isArray(c.boxes) ? c.boxes : [];
                return boxes.some(b => {
                  const items = Array.isArray(b.items) ? b.items : [];
                  return items.some(it => {
                    const s = (typeof it === 'object' && it !== null) ? (it.serial_number || '') : String(it);
                    return s && s.toUpperCase().includes(snQ);
                  });
                });
              });
            });
          }

          // 6. Urutan Berdasarkan Tanggal atau Nomor Surat Jalan
          list.sort((a, b) => {
            if (doSortField.value === 'do_number') {
              const seqA = getDONumberSeq(a);
              const seqB = getDONumberSeq(b);
              if (seqA !== seqB) {
                return doSortOrder.value === 'asc' ? seqA - seqB : seqB - seqA;
              }
              const numA = (a.do_number || '').trim();
              const numB = (b.do_number || '').trim();
              return doSortOrder.value === 'asc' ? numA.localeCompare(numB) : numB.localeCompare(numA);
            }

            // Default: Urut berdasarkan Tanggal DO
            const dateA = getDODateKey(a) || '';
            const dateB = getDODateKey(b) || '';
            if (dateA !== dateB) {
              return doSortOrder.value === 'asc' ? dateA.localeCompare(dateB) : dateB.localeCompare(dateA);
            }

            // Jika tanggal sama, urutkan berdasarkan urutan Nomor DO (seq)
            const seqA = getDONumberSeq(a);
            const seqB = getDONumberSeq(b);
            if (seqA !== seqB) {
              return doSortOrder.value === 'asc' ? seqA - seqB : seqB - seqA;
            }

            const idA = Number(a.id) || 0;
            const idB = Number(b.id) || 0;
            return doSortOrder.value === 'asc' ? idA - idB : idB - idA;
          });

          return list;
        });

        const selectCustomer = (cust) => {
          doForm.value.customer_name = cust.name;
          doForm.value.customer_address = cust.address || '';
          doForm.value.customer_phone = cust.phone || '';
          doForm.value.customer_fax = cust.fax || '';
          customerSearchQuery.value = cust.name;
          isCustomerDropdownOpen.value = false;
        };

        const filterNumberAndSymbolsOnly = (val) => {
          if (val === null || val === undefined) return '';
          return String(val).replace(/\p{L}/gu, '');
        };

        const blockLetterKeys = (event) => {
          if (event.key && event.key.length === 1 && /\p{L}/u.test(event.key)) {
            event.preventDefault();
          }
        };

        const onPhoneFaxInput = (targetObj, field, event) => {
          const filtered = filterNumberAndSymbolsOnly(event.target.value);
          if (targetObj && field) {
            targetObj[field] = filtered;
          }
          event.target.value = filtered;
        };

        const openNewCustomerModal = () => {
          newCustomerForm.value = { name: '', address: '', phone: '', fax: '', notes: '' };
          showNewCustomerModal.value = true;
        };

        const isSavingCustomer = isSubmittingCustomer;

        const saveNewCustomer = async () => {
          if (!newCustomerForm.value.name || !newCustomerForm.value.name.trim()) {
            triggerToast('Perhatian', 'Nama Customer wajib diisi', 'warn');
            return;
          }
          newCustomerForm.value.phone = filterNumberAndSymbolsOnly(newCustomerForm.value.phone);
          newCustomerForm.value.fax = filterNumberAndSymbolsOnly(newCustomerForm.value.fax);
          isSavingCustomer.value = true;
          try {
            const data = await fetchAPI('/customers', {
              method: 'POST',
              body: JSON.stringify(newCustomerForm.value)
            });
            const custObj = data?.customer || data?.data;
            if (data && (data.ok || custObj)) {
              const resCustomer = custObj || { ...newCustomerForm.value };
              const existingIdx = customersList.value.findIndex(c => c.id === resCustomer.id || (c.name && resCustomer.name && c.name.toLowerCase() === resCustomer.name.toLowerCase()));
              if (existingIdx >= 0) {
                customersList.value[existingIdx] = resCustomer;
              } else {
                customersList.value.unshift(resCustomer);
              }
              selectCustomer(resCustomer);
              showNewCustomerModal.value = false;
              newCustomerForm.value = { name: '', address: '', phone: '', fax: '' };
              triggerToast('Sukses', data?.message || 'Customer baru berhasil ditambahkan', 'success');
              await loadCustomers();
            } else {
              triggerToast('Gagal', data?.error || data?.message || 'Gagal menyimpan customer', 'error');
            }
          } catch (err) {
            console.error(err);
            triggerToast('Error', err.message || 'Gagal menyimpan customer', 'error');
          } finally {
            isSavingCustomer.value = false;
          }
        };

        const loadCustomers = async () => {
          try {
            const data = await fetchAPI('/customers');
            if (data && (data.customers || data.data)) {
              customersList.value = data.customers || data.data || [];
            }
          } catch (err) {
            console.error('Failed to load customers:', err);
          }
        };

        const deleteCustomer = async (id, name) => {
          const ok = await showConfirmCard({
            title: 'Hapus Customer',
            message: `Apakah Anda yakin ingin menghapus customer "${name}"?`,
            icon: 'fa-solid fa-trash-can',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const res = await fetchAPI(`/customers/${id}`, { method: 'DELETE' });
            if (res.ok) {
              triggerToast('Berhasil', 'Customer berhasil dihapus', 'success');
              await loadCustomers();
            } else {
              triggerToast('Gagal', res.error || 'Gagal menghapus customer', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const onLabelSNInput = (item) => {
          if (!item) return;
        };

        const getLabelSlotStatus = (item) => {
          if (!item || !item.serial_number) return 'empty';
          const sn = String(item.serial_number).trim().toUpperCase();
          if (!sn) return 'empty';

          // Cek duplikasi di dalam form Packing List
          let count = 0;
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => {
                if (it && String(it.serial_number || '').trim().toUpperCase() === sn) {
                  count++;
                }
              });
            });
          });
          if (count > 1) return 'duplicate';

          // Cek ketersediaan di stok selesai & lolos penerimaan fisik gudang
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          if (!availableSet.has(sn)) return 'invalid';

          return 'valid';
        };

        const getAvailableSNsForLabelSlot = (currentVal) => {
          const used = new Set();
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => {
                if (it && it.serial_number && it.serial_number.trim() && it.serial_number !== currentVal) {
                  used.add(it.serial_number.trim().toUpperCase());
                }
              });
            });
          });
          return (availableFinishedSNs.value || []).filter(s => s && !used.has(s.trim().toUpperCase()));
        };

        // ================= LABEL PL SLOT CUSTOM DROPDOWN =================
        const labelSlotDropdown = ref({
          isOpen: false,
          key: null
        });
        let labelSlotBlurTimer = null;

        const openLabelSlotDropdown = (crateIdx, bIdx, sIdx) => {
          if (labelSlotBlurTimer) clearTimeout(labelSlotBlurTimer);
          labelSlotDropdown.value = {
            isOpen: true,
            key: `${crateIdx}-${bIdx}-${sIdx}`
          };
        };

        const toggleLabelSlotDropdown = (crateIdx, bIdx, sIdx) => {
          if (labelSlotBlurTimer) clearTimeout(labelSlotBlurTimer);
          const targetKey = `${crateIdx}-${bIdx}-${sIdx}`;
          if (labelSlotDropdown.value.isOpen && labelSlotDropdown.value.key === targetKey) {
            labelSlotDropdown.value.isOpen = false;
            labelSlotDropdown.value.key = null;
          } else {
            labelSlotDropdown.value = {
              isOpen: true,
              key: targetKey
            };
          }
        };

        const closeLabelSlotDropdown = () => {
          labelSlotBlurTimer = setTimeout(() => {
            labelSlotDropdown.value.isOpen = false;
            labelSlotDropdown.value.key = null;
          }, 250);
        };

        const isLabelSlotDropdownOpen = (crateIdx, bIdx, sIdx) => {
          return labelSlotDropdown.value.isOpen && labelSlotDropdown.value.key === `${crateIdx}-${bIdx}-${sIdx}`;
        };

        const onLabelSlotInput = (item, crateIdx, bIdx, sIdx) => {
          openLabelSlotDropdown(crateIdx, bIdx, sIdx);
          onLabelSNInput(item);
        };

        const selectLabelSlotSN = (item, sn) => {
          if (item) {
            item.serial_number = sn;
            onLabelSNInput(item);
          }
          labelSlotDropdown.value.isOpen = false;
          labelSlotDropdown.value.key = null;
        };

        const clearLabelSlotSN = (item) => {
          if (item) {
            item.serial_number = '';
            onLabelSNInput(item);
          }
          labelSlotDropdown.value.isOpen = false;
          labelSlotDropdown.value.key = null;
        };

        const getFilteredSNsForLabelSlot = (currentVal) => {
          const list = getAvailableSNsForLabelSlot(currentVal);
          const q = (currentVal || '').trim().toUpperCase();
          if (!q) return list;
          return list.filter(sn => sn.toUpperCase().includes(q));
        };

        const handleLabelSlotKeyDown = (e, item, crateIdx, bIdx, sIdx) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            const suggestions = getFilteredSNsForLabelSlot(item.serial_number);
            if (suggestions.length > 0 && !suggestions.includes((item.serial_number || '').trim().toUpperCase())) {
              selectLabelSlotSN(item, suggestions[0]);
            } else {
              labelSlotDropdown.value.isOpen = false;
              labelSlotDropdown.value.key = null;
            }
          } else if (e.key === 'Escape') {
            labelSlotDropdown.value.isOpen = false;
            labelSlotDropdown.value.key = null;
          }
        };

        const loadDeliveryOrders = async () => {
          try {
            const data = await fetchAPI('/delivery-orders');
            if (data && (data.data || data.delivery_orders)) {
              doList.value = data.data || data.delivery_orders || [];
              if (doList.value.length > 0 && !previewDOId.value) {
                previewDOId.value = doList.value[0].id;
                loadDOPreviewData(previewDOId.value);
              }
            }
          } catch (err) {
            console.error('Failed to load delivery orders:', err);
          }
        };

        const loadAvailableFinishedSNs = async () => {
          try {
            const data = await fetchAPI('/available-serial-numbers');
            if (data && (data.data || data.serial_numbers)) {
              const raw = data.data || data.serial_numbers || [];
              availableFinishedSNs.value = raw.map(r => typeof r === 'string' ? r : (r.part15_sn || r.serial_number || '')).filter(Boolean);
            }
          } catch (err) {
            console.error('Failed to load available finished SNs:', err);
          }
        };

        const loadNextDOMeta = async (explicitDate = null) => {
          try {
            const targetDate = explicitDate || doForm.value.do_date || '';
            const queryParam = targetDate ? `?date=${encodeURIComponent(targetDate)}` : '';
            const res = await fetchAPI(`/delivery-orders/next-meta${queryParam}`);
            const data = (res && res.data) ? res.data : (res || {});

            const doNum = data.do_number || data.next_number || '';
            if (doNum) {
              doForm.value.do_number = doNum;
            } else if (!doForm.value.do_number) {
              doForm.value.do_number = generateDefaultDONumber(targetDate);
            }

            const bNum = data.current_batch || data.batch_number || doSettings.value?.current_batch_number || 1;
            doForm.value.batch_number = bNum;
            labelForm.value.batch_number = bNum;

            if (data.next_sj_seq) {
              sjDeliverySeq.value = data.next_sj_seq;
            }
            if (data.next_pl_seq) {
              plDeliverySeq.value = data.next_pl_seq;
            }

            if (!explicitDate && !doForm.value.do_date && data.do_date_iso) {
              doForm.value.do_date = data.do_date_iso;
            }
            const { yy, mm } = getYearMonthFromDate(targetDate || data.do_date_iso || doForm.value.do_date);
            if (data.crate_sequence) {
              doForm.value.start_crate_seq = data.crate_sequence;
              doForm.value.start_crate_month = `${yy}${mm}`;
            }
            if (data.box_sequence) {
              doForm.value.start_box_seq = data.box_sequence;
              doForm.value.start_box_month = `${yy}${mm}`;
            }

            const custName = data.customer_name || data.settings?.default_customer_name;
            if (custName && (!doForm.value.customer_name || doForm.value.customer_name === 'KEMHAN RI')) doForm.value.customer_name = custName;

            const custAddress = data.customer_address || data.settings?.default_customer_address;
            if (custAddress) doForm.value.customer_address = custAddress;

            const custPhone = data.customer_phone || data.settings?.default_customer_phone;
            if (custPhone) doForm.value.customer_phone = custPhone;

            const custFax = data.customer_fax || data.settings?.default_customer_fax;
            if (custFax) doForm.value.customer_fax = custFax;

            const contractNo = data.contract_number || data.settings?.default_contract_number;
            if (contractNo) doForm.value.contract_number = contractNo;

            const progName = data.program_name || data.settings?.default_program_name;
            if (progName) doForm.value.program_name = progName;

            const prodName = data.product_name || data.settings?.default_product_name;
            if (prodName) doForm.value.product_name = prodName;

            const issuerName = data.created_by_name || data.settings?.default_issuer_name;
            if (issuerName) doForm.value.issued_by_name = issuerName;

            const issuerTitle = data.created_by_title || data.settings?.default_issuer_title;
            if (issuerTitle) doForm.value.issued_by_title = issuerTitle;

            const issuerDept = data.created_by_department || data.settings?.default_issuer_department;
            if (issuerDept) doForm.value.issued_by_dept = issuerDept;

            syncPackagingStructure();
          } catch (err) {
            console.error('Failed to load next DO meta:', err);
            if (!doForm.value.do_number) {
              doForm.value.do_number = generateDefaultDONumber(explicitDate || doForm.value.do_date);
            }
          }
        };

        const loadNextDONumber = async () => {
          await loadNextDOMeta(doForm.value.do_date);
          triggerToast('Nomor DO Diperbarui', `Nomor Surat Jalan disesuaikan: ${doForm.value.do_number}`, 'info');
        };
        const loadNextBatchNumber = loadNextDOMeta;
        const loadDOList = loadDeliveryOrders;

        let doDateChangeTimeout = null;
        const onDODateChanged = () => {
          const dateVal = doForm.value.do_date;
          if (!dateVal || dateVal.length < 7) return;

          // 1. Langsung update ID Peti & ID Dus di tampilan seketika (reaktif tanpa jeda)
          syncPackagingStructure();

          // 2. Debounce panggil backend untuk update nomor DO & sequence bulan baru
          if (doDateChangeTimeout) clearTimeout(doDateChangeTimeout);
          doDateChangeTimeout = setTimeout(async () => {
            await loadNextDOMeta(dateVal);
          }, 250);
        };

        const setPackageType = (type) => {
          doForm.value.package_type = type;
          syncPackagingStructure();
        };

        const buildPackagingStructure = (type, count, batchNum, startSeq = 1, targetDate = null) => {
          const bNum = parseInt(batchNum) || 1;
          const num = parseInt(count) || 1;
          const start = Math.max(1, parseInt(startSeq) || 1);
          const crates = [];

          const { yy, mm } = getYearMonthFromDate(targetDate || doForm.value?.do_date);
          const bNumStr = String(parseInt(bNum, 10) || 1).padStart(2, '0');

          // Pengiriman ke-X (2 digit)
          let deliverySeq = 1;
          if (typeof sjDeliverySeq !== 'undefined' && sjDeliverySeq.value) {
            deliverySeq = parseInt(sjDeliverySeq.value, 10) || 1;
          } else if (doForm.value?.delivery_seq) {
            deliverySeq = parseInt(doForm.value.delivery_seq, 10) || 1;
          } else if (doForm.value?.do_number) {
            const m = String(doForm.value.do_number).match(/^0*(\d+)/);
            if (m) deliverySeq = parseInt(m[1], 10) || 1;
          }
          const ppStr = String(deliverySeq).padStart(2, '0');

          if (type === 'crate') {
            for (let i = 0; i < num; i++) {
              const c = start + i;
              const ddStr = String(c).padStart(2, '0');
              const crateId = `${yy}${mm}-${bNumStr}-${ppStr}${ddStr}`;
              const boxes = [];
              for (let b = 1; b <= 2; b++) {
                const ssStr = String(b).padStart(2, '0');
                const boxId = `${crateId}-${ssStr}`;
                const items = [];
                for (let s = 1; s <= 10; s++) {
                  items.push({ slot_number: s, serial_number: '' });
                }
                boxes.push({ box_number: b, box_id: boxId, items });
              }
              crates.push({ crate_number: i + 1, crate_id: crateId, total_quantity: 20, boxes });
            }
          } else if (type === 'box') {
            for (let i = 0; i < num; i++) {
              const b = start + i;
              const crateIdx = Math.ceil((i + 1) / 2);
              const ddStr = String(crateIdx).padStart(2, '0');
              const ssStr = String(((i % 2) + 1)).padStart(2, '0');
              const parentCrateId = `${yy}${mm}-${bNumStr}-${ppStr}${ddStr}`;
              const boxId = `${parentCrateId}-${ssStr}`;
              const items = [];
              for (let s = 1; s <= 10; s++) {
                items.push({ slot_number: s, serial_number: '' });
              }
              crates.push({
                crate_number: i + 1,
                crate_id: parentCrateId,
                box_id: boxId,
                total_quantity: 10,
                boxes: [{ box_number: 1, box_id: boxId, items }]
              });
            }
          } else {
            for (let u = 1; u <= num; u++) {
              crates.push({
                crate_number: u,
                crate_id: '',
                boxes: [{
                  box_number: 1,
                  box_id: '',
                  items: [{ slot_number: 1, serial_number: '' }]
                }]
              });
            }
          }
          return crates;
        };

        const buildCratesForQuantity = (qty, batchNum) => {
          const validQty = Math.max(1, parseInt(qty) || 1);
          const numCrates = Math.ceil(validQty / 20);
          return buildPackagingStructure('crate', numCrates, batchNum, 1, doForm.value.do_date);
        };

        const syncPackagingStructure = () => {
          const type = doForm.value.package_type || 'crate';
          const count = Math.max(1, parseInt(doForm.value.package_count) || 1);
          const batchNum = Math.max(1, parseInt(doForm.value.batch_number) || 1);

          if (type === 'crate') {
            doForm.value.total_quantity = count * 20;
          } else if (type === 'box') {
            doForm.value.total_quantity = count * 10;
          } else {
            doForm.value.total_quantity = count;
          }

          let startSeq = 1;
          const { yy, mm } = getYearMonthFromDate(doForm.value.do_date);
          const prefix = `${yy}${mm}-${batchNum}`;

          if (type === 'crate') {
            let maxFound = 0;
            const scanCrates = (list) => {
              (list || []).forEach(item => {
                let arr = [];
                if (Array.isArray(item.crates)) arr = item.crates;
                else if (typeof item.crates_json === 'string') {
                  try { arr = JSON.parse(item.crates_json); } catch (_) { }
                }
                arr.forEach(c => {
                  const cid = String(c.crate_id || '').trim();
                  if (cid.startsWith(prefix)) {
                    const s = parseInt(cid.substring(prefix.length), 10);
                    if (!isNaN(s)) maxFound = Math.max(maxFound, s);
                  }
                });
              });
            };
            scanCrates(doList.value);
            scanCrates(standaloneLabelsList.value);
            const baseSeq = (doForm.value.start_crate_month === `${yy}${mm}`) ? (doForm.value.start_crate_seq || 1) : 1;
            startSeq = Math.max(maxFound + 1, baseSeq);
          } else if (type === 'box') {
            const parentCrateId = `${yy}${mm}-${batchNum}000`;
            const boxPrefix = `${parentCrateId}-`;
            let maxFound = 0;
            const scanBoxes = (list) => {
              (list || []).forEach(item => {
                let arr = [];
                if (Array.isArray(item.crates)) arr = item.crates;
                else if (typeof item.crates_json === 'string') {
                  try { arr = JSON.parse(item.crates_json); } catch (_) { }
                }
                arr.forEach(c => {
                  (c.boxes || []).forEach(b => {
                    const bid = String(b.box_id || c.box_id || '').trim();
                    if (bid.startsWith(boxPrefix)) {
                      const s = parseInt(bid.substring(boxPrefix.length), 10);
                      if (!isNaN(s)) maxFound = Math.max(maxFound, s);
                    }
                  });
                });
              });
            };
            scanBoxes(doList.value);
            scanBoxes(standaloneLabelsList.value);
            const baseSeq = (doForm.value.start_box_month === `${yy}${mm}`) ? (doForm.value.start_box_seq || 1) : 1;
            startSeq = Math.max(maxFound + 1, baseSeq);
          }

          const oldCrates = doForm.value.crates || [];
          const newCrates = buildPackagingStructure(type, count, batchNum, startSeq, doForm.value.do_date);

          newCrates.forEach((c, cIdx) => {
            const oldC = oldCrates[cIdx];
            if (oldC) {
              if (oldC.consumed_source_crate_id) {
                c.consumed_source_crate_id = oldC.consumed_source_crate_id;
                c.consumed_from_pl = oldC.consumed_from_pl;
                c.consumed_pl_id = oldC.consumed_pl_id;
              }
              if (type === 'box' && oldC.consumed_source_box_id) {
                c.consumed_source_box_id = oldC.consumed_source_box_id;
                c.consumed_from_pl = oldC.consumed_from_pl;
                c.consumed_pl_id = oldC.consumed_pl_id;
              }
              if (oldC.boxes) {
                c.boxes.forEach((b, bIdx) => {
                  const oldB = oldC.boxes[bIdx];
                  if (oldB) {
                    if (oldB.consumed_source_box_id) {
                      b.consumed_source_box_id = oldB.consumed_source_box_id;
                      b.consumed_from_pl = oldB.consumed_from_pl;
                      b.consumed_pl_id = oldB.consumed_pl_id;
                    }
                    if (oldB.items) {
                      b.items.forEach((it, sIdx) => {
                        const oldIt = oldB.items[sIdx];
                        if (oldIt && oldIt.serial_number) {
                          it.serial_number = oldIt.serial_number;
                          it.is_from_pl = oldIt.is_from_pl;
                        }
                      });
                    }
                  }
                });
              }
            }
          });

          doForm.value.crates = newCrates;
          if (activeContainerIndex.value >= newCrates.length) {
            activeContainerIndex.value = Math.max(0, newCrates.length - 1);
          }
        };

        const checkSjUniqueness = async (customNum = null) => {
          const num = (customNum || sjGeneratedId.value || (doForm.value && doForm.value.do_number) || '').trim();
          if (!num) {
            sjDuplicateWarning.value = false;
            sjDuplicateMessage.value = '';
            return true;
          }
          const existsLocal = (doList.value || []).some(d => (d.do_number || '').trim().toUpperCase() === num.toUpperCase());
          if (existsLocal) {
            sjDuplicateWarning.value = true;
            sjDuplicateMessage.value = 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.';
            return false;
          }

          isCheckingSjUnique.value = true;
          try {
            const res = await fetchAPI(`/delivery-orders/check-unique?type=sj&number=${encodeURIComponent(num)}`);
            if (res && res.ok) {
              if (!res.is_unique) {
                sjDuplicateWarning.value = true;
                sjDuplicateMessage.value = 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.';
                return false;
              } else {
                sjDuplicateWarning.value = false;
                sjDuplicateMessage.value = '';
                return true;
              }
            }
          } catch (e) {
            console.error('Error checking SJ uniqueness:', e);
          } finally {
            isCheckingSjUnique.value = false;
          }
          return true;
        };

        const updateGeneratedSJNumber = () => {
          const targetDate = (sjMode.value === 'realtime') ? getNowWIBDateString() : (doForm.value?.do_date || getNowWIBDateString());
          if (sjMode.value === 'realtime' && doForm.value) {
            doForm.value.do_date = targetDate;
          }
          const { mm, yyyy } = getYearMonthFromDate(targetDate);
          const seq = Math.max(1, parseInt(sjDeliverySeq.value, 10) || 1);
          const doPrefix = doSettings.value.do_prefix || '/SJ/PPIC/';
          const cleanPrefix = doPrefix.startsWith('/') ? doPrefix : '/' + doPrefix;
          const endSlash = cleanPrefix.endsWith('/') ? cleanPrefix : cleanPrefix + '/';
          const gen = `${String(seq).padStart(3, '0')}${endSlash}${mm}/${yyyy}`;
          sjGeneratedId.value = gen;
          if (doForm.value) {
            doForm.value.do_number = gen;
            doForm.value.delivery_seq = seq;
          }
          checkSjUniqueness(gen);
        };

        watch(sjMode, (mode) => {
          if (mode === 'realtime' && doForm.value) {
            doForm.value.do_date = getTodayISODate();
          }
          // Bersihkan alokasi kemasan yang dikonsumsi jika mode berganti
          if (doForm.value) {
            delete doForm.value.consumed_standalone_id;
            delete doForm.value.consumed_pl_type;
            (doForm.value.crates || []).forEach(c => {
              delete c.consumed_source_crate_id;
              delete c.consumed_from_pl;
              delete c.consumed_pl_id;
              (c.boxes || []).forEach(b => {
                delete b.consumed_source_box_id;
                delete b.consumed_from_pl;
                delete b.consumed_pl_id;
              });
            });
          }
          selectedConsumedPLId.value = '';
          selectedConsumedPL.value = null;
          if (mode) {
            updateGeneratedSJNumber();
            syncPackagingStructure();
          }
        });
        watch(() => doForm.value?.do_date, () => {
          updateGeneratedSJNumber();
          syncPackagingStructure();
        });
        watch(sjDeliverySeq, () => {
          updateGeneratedSJNumber();
          syncPackagingStructure();
        });
        watch(() => doForm.value?.batch_number, () => {
          updateGeneratedSJNumber();
          syncPackagingStructure();
        });

        const getAllBoxesInForm = () => {
          const boxes = [];
          (doForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => boxes.push(b));
          });
          return boxes;
        };

        const getAllUnitsInForm = () => {
          const units = [];
          (doForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => units.push(it));
            });
          });
          return units;
        };

        const getTotalFilledSNs = () => {
          let count = 0;
          getAllUnitsInForm().forEach(it => {
            if (it.serial_number && String(it.serial_number).trim()) {
              count++;
            }
          });
          return count;
        };

        const isSNInList = (sn, slotItem = null) => {
          if (!sn) return false;
          const upper = String(sn).trim().toUpperCase();
          if (availableFinishedSNs.value && availableFinishedSNs.value.includes(upper)) {
            return true;
          }
          if (slotItem && (slotItem.is_from_pl || slotItem.isFromPl)) {
            return true;
          }
          // Cek apakah SN ini ada di dalam Standalone Packing Labels yang sah (belum dikonsumsi DO lain)
          for (const lbl of (standaloneLabelsList.value || [])) {
            const isConsumedByOtherDO = lbl.is_consumed && lbl.consumed_by_do && lbl.consumed_by_do !== doForm.value.do_number;
            if (!isConsumedByOtherDO) {
              let crates = lbl.crates || [];
              if (typeof crates === 'string') {
                try { crates = JSON.parse(crates); } catch (_) { crates = []; }
              } else if ((!crates || crates.length === 0) && typeof lbl.crates_json === 'string') {
                try { crates = JSON.parse(lbl.crates_json); } catch (_) { crates = []; }
              }
              for (const c of (crates || [])) {
                for (const b of (c.boxes || [])) {
                  for (const it of (b.items || [])) {
                    const itSn = typeof it === 'object' && it !== null ? (it.serial_number || '') : String(it || '');
                    if (itSn && itSn.trim().toUpperCase() === upper) {
                      return true;
                    }
                  }
                }
              }
            }
          }
          return false;
        };

        const hasInvalidDOSNs = computed(() => {
          const allUnits = getAllUnitsInForm();
          for (const it of allUnits) {
            if (it.serial_number && String(it.serial_number).trim()) {
              if (!isSNInList(it.serial_number, it)) {
                return true;
              }
            }
          }
          return false;
        });

        const getAvailableSNsForSlot = (currentVal) => {
          const used = new Set();
          getAllUnitsInForm().forEach(it => {
            if (it.serial_number && it.serial_number.trim() && it.serial_number !== currentVal) {
              used.add(it.serial_number.trim().toUpperCase());
            }
          });
          return availableFinishedSNs.value.filter(s => !used.has(s.trim().toUpperCase()));
        };

        // ================= DO SLOT CUSTOM DROPDOWN =================
        const doSlotDropdown = ref({
          isOpen: false,
          key: null
        });
        let doSlotBlurTimer = null;

        const openDOSlotDropdown = (key) => {
          if (doSlotBlurTimer) clearTimeout(doSlotBlurTimer);
          doSlotDropdown.value = { isOpen: true, key };
        };

        const toggleDOSlotDropdown = (key) => {
          if (doSlotBlurTimer) clearTimeout(doSlotBlurTimer);
          if (doSlotDropdown.value.isOpen && doSlotDropdown.value.key === key) {
            doSlotDropdown.value.isOpen = false;
            doSlotDropdown.value.key = null;
          } else {
            doSlotDropdown.value = { isOpen: true, key };
          }
        };

        const closeDOSlotDropdown = () => {
          doSlotBlurTimer = setTimeout(() => {
            doSlotDropdown.value.isOpen = false;
            doSlotDropdown.value.key = null;
          }, 250);
        };

        const isDOSlotDropdownOpen = (key) => {
          return doSlotDropdown.value.isOpen && doSlotDropdown.value.key === key;
        };

        const selectDOSlotSN = (item, sn) => {
          if (item) {
            item.serial_number = sn;
          }
          doSlotDropdown.value.isOpen = false;
          doSlotDropdown.value.key = null;
        };

        const clearDOSlotSN = (item) => {
          if (item) {
            item.serial_number = '';
          }
          doSlotDropdown.value.isOpen = false;
          doSlotDropdown.value.key = null;
        };

        const getFilteredSNsForDOSlot = (currentVal) => {
          const list = getAvailableSNsForSlot(currentVal);
          const q = (currentVal || '').trim().toUpperCase();
          if (!q) return list;
          return list.filter(sn => sn.toUpperCase().includes(q));
        };

        const handleDOSlotKeyDown = (e, item, key) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            const suggestions = getFilteredSNsForDOSlot(item.serial_number);
            if (suggestions.length > 0 && !suggestions.includes((item.serial_number || '').trim().toUpperCase())) {
              selectDOSlotSN(item, suggestions[0]);
            } else {
              doSlotDropdown.value.isOpen = false;
              doSlotDropdown.value.key = null;
            }
          } else if (e.key === 'Escape') {
            doSlotDropdown.value.isOpen = false;
            doSlotDropdown.value.key = null;
          }
        };

        const parsedBatchPasteSNs = computed(() => {
          if (!batchPasteRawText.value) return [];
          return batchPasteRawText.value
            .split(/[\r\n,;\t]+/)
            .map(s => s.trim().toUpperCase())
            .filter(s => s.length > 0);
        });

        const batchPasteStats = computed(() => {
          const list = parsedBatchPasteSNs.value;
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          let validCount = 0;
          let unverifiedCount = 0;
          const seen = new Set();
          let duplicatesCount = 0;

          list.forEach(sn => {
            if (seen.has(sn)) {
              duplicatesCount++;
            } else {
              seen.add(sn);
            }
            if (availableSet.has(sn)) {
              validCount++;
            } else {
              unverifiedCount++;
            }
          });

          return {
            total: list.length,
            unique: seen.size,
            validCount,
            unverifiedCount,
            duplicatesCount
          };
        });

        const openBatchPasteModal = (crateIdx, boxNum, isUnit = false, mode = 'box') => {
          batchPasteTarget.value = { crateIdx, boxNum, isUnit, mode };
          batchPasteRawText.value = '';
          showBatchPasteModal.value = true;
        };

        const applyBatchPaste = () => {
          const rawLines = parsedBatchPasteSNs.value;

          if (rawLines.length === 0) {
            triggerToast('Perhatian', 'Tidak ada data Serial Number yang valid untuk ditempel.', 'warning');
            return;
          }

          // Validasi Ketersediaan SN: Wajib terdaftar di stok selesai dan lolos penerimaan fisik gudang
          const availableSet = new Set((availableFinishedSNs.value || []).map(s => String(s).trim().toUpperCase()));
          const invalidSNs = rawLines.filter(sn => !availableSet.has(sn));

          if (invalidSNs.length > 0) {
            const sample = invalidSNs.slice(0, 3).join(', ') + (invalidSNs.length > 3 ? ` (+${invalidSNs.length - 3} lainnya)` : '');
            triggerToast('SN Tidak Tersedia', `Ditemukan ${invalidSNs.length} SN yang tidak tersedia atau belum lolos penerimaan fisik gudang: ${sample}. Hanya SN yang telah lolos semua proses dan penerimaan gudang yang dapat dimasukkan.`, 'error');
            return;
          }

          const { crateIdx, boxNum, isUnit, mode } = batchPasteTarget.value;
          let count = 0;

          if (isUnit) {
            if (rawLines.length > doForm.value.package_count) {
              doForm.value.package_count = rawLines.length;
              syncPackagingStructure();
            }
            const allUnits = getAllUnitsInForm();
            for (let i = 0; i < allUnits.length && i < rawLines.length; i++) {
              allUnits[i].serial_number = rawLines[i];
              count++;
            }
            showBatchPasteModal.value = false;
            triggerToast('Tempel Berhasil', `${count} Serial Number satuan berhasil dimasukkan.`, 'success');
            return;
          }

          if (mode === 'crate') {
            const crate = doForm.value.crates[crateIdx];
            if (!crate || !crate.boxes) return;
            let snIdx = 0;
            crate.boxes.forEach(box => {
              (box.items || []).forEach(it => {
                if (snIdx < rawLines.length) {
                  it.serial_number = rawLines[snIdx++];
                  count++;
                }
              });
            });
            showBatchPasteModal.value = false;
            triggerToast('Tempel Berhasil', `${count} Serial Number berhasil dialokasikan ke Peti #${crateIdx + 1}.`, 'success');
            return;
          }

          const crate = doForm.value.crates[crateIdx];
          if (!crate) return;
          const box = crate.boxes[boxNum - 1];
          if (!box) return;

          for (let i = 0; i < 10 && i < rawLines.length; i++) {
            box.items[i].serial_number = rawLines[i];
            count++;
          }

          showBatchPasteModal.value = false;
          triggerToast('Tempel Berhasil', `${count} Serial Number berhasil ditempel ke Dus ${boxNum}.`, 'success');
        };

        const addUnitToDOForm = (count = 1, showToast = true) => {
          const current = Math.max(1, parseInt(doForm.value.package_count) || 1);
          const target = Math.max(1, current + count);
          doForm.value.package_count = target;
          syncPackagingStructure();
          const batches = getUnitBatches();
          if (activeContainerIndex.value < batches.length - 1) {
            activeContainerIndex.value = batches.length - 1;
          }
          if (showToast) {
            triggerToast('Unit Diperbarui', `Jumlah satuan menjadi ${doForm.value.package_count} unit.`, 'info');
          }
        };

        const removeUnitFromDOForm = (unitIndex) => {
          const allUnits = getAllUnitsInForm();
          if (allUnits.length <= 1) {
            triggerToast('Minimal 1 Unit', 'Pengiriman satuan minimal harus memiliki 1 unit.', 'warn');
            return;
          }
          for (let i = unitIndex; i < allUnits.length - 1; i++) {
            allUnits[i].serial_number = allUnits[i + 1].serial_number;
          }
          doForm.value.package_count = allUnits.length - 1;
          syncPackagingStructure();
          const batches = getUnitBatches();
          if (activeContainerIndex.value >= batches.length) {
            activeContainerIndex.value = Math.max(0, batches.length - 1);
          }
          triggerToast('Unit Dihapus', `Jumlah satuan menjadi ${doForm.value.package_count} unit.`, 'info');
        };

        const updateUnitCountDirectly = (newCount) => {
          const count = Math.max(1, parseInt(newCount) || 1);
          doForm.value.package_count = count;
          syncPackagingStructure();
          const batches = getUnitBatches();
          if (activeContainerIndex.value >= batches.length) {
            activeContainerIndex.value = Math.max(0, batches.length - 1);
          }
        };

        // ================= UNIT PACKAGING DIRECT LIST & AUTOCOMPLETE =================
        const unitDropdown = ref({
          isOpen: false,
          idx: null
        });

        let unitBlurTimer = null;

        const onUnitInputFocus = (uIdx) => {
          if (unitBlurTimer) clearTimeout(unitBlurTimer);
          unitDropdown.value.idx = uIdx;
          unitDropdown.value.isOpen = true;
        };

        const onUnitInputBlur = (uIdx) => {
          unitBlurTimer = setTimeout(() => {
            if (unitDropdown.value.idx === uIdx) {
              unitDropdown.value.isOpen = false;
              unitDropdown.value.idx = null;
            }
          }, 250);
        };

        const onUnitInputChange = (uIdx) => {
          unitDropdown.value.idx = uIdx;
          unitDropdown.value.isOpen = true;
        };

        const getFilteredUnitSuggestions = (currentInput) => {
          const query = (currentInput || '').trim().toUpperCase();
          const allUnits = getAllUnitsInForm();
          const usedInOtherSlots = new Set();
          allUnits.forEach((u, i) => {
            if (i !== unitDropdown.value.idx && u.serial_number && u.serial_number.trim()) {
              usedInOtherSlots.add(u.serial_number.trim().toUpperCase());
            }
          });
          return (availableFinishedSNs.value || [])
            .filter(sn => {
              const s = sn.trim().toUpperCase();
              if (usedInOtherSlots.has(s)) return false;
              return !query || s.includes(query);
            })
            .slice(0, 15);
        };

        const selectUnitSN = (uIdx, sn) => {
          const allUnits = getAllUnitsInForm();
          if (allUnits[uIdx]) {
            allUnits[uIdx].serial_number = sn;
          }
          unitDropdown.value.isOpen = false;
          unitDropdown.value.idx = null;

          if (uIdx === allUnits.length - 1) {
            addUnitRowAutomatically();
          }
          nextTick(() => {
            const nextInput = document.getElementById(`unit-sn-input-${uIdx + 1}`);
            if (nextInput) nextInput.focus();
          });
        };

        const addUnitRowAutomatically = () => {
          addUnitToDOForm(1, false);
        };

        const handleUnitInputKeyDown = (e, uIdx) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            const allUnits = getAllUnitsInForm();
            unitDropdown.value.isOpen = false;
            if (uIdx === allUnits.length - 1) {
              addUnitRowAutomatically();
              nextTick(() => {
                const nextInput = document.getElementById(`unit-sn-input-${uIdx + 1}`);
                if (nextInput) nextInput.focus();
              });
            } else {
              const nextInput = document.getElementById(`unit-sn-input-${uIdx + 1}`);
              if (nextInput) nextInput.focus();
            }
          } else if (e.key === 'Escape') {
            unitDropdown.value.isOpen = false;
          }
        };

        const removeUnitRowDirectly = (uIdx) => {
          removeUnitFromDOForm(uIdx);
        };

        const autofillAllUnits = () => {
          const allUnits = getAllUnitsInForm();
          const usedSNs = new Set();
          allUnits.forEach(u => {
            if (u.serial_number && u.serial_number.trim()) {
              usedSNs.add(u.serial_number.trim().toUpperCase());
            }
          });
          const freeSNs = (availableFinishedSNs.value || []).filter(s => !usedSNs.has(s.trim().toUpperCase()));
          let filledCount = 0;
          allUnits.forEach(u => {
            if (!u.serial_number || !u.serial_number.trim()) {
              if (filledCount < freeSNs.length) {
                u.serial_number = freeSNs[filledCount++];
              }
            }
          });
          triggerToast('Auto Fill Satuan Selesai', `${filledCount} SN berhasil diisi otomatis.`, 'info');
        };

        const openDODetailView = async (doId) => {
          isLoadingDODetail.value = true;
          doSubTab.value = 'detail';
          try {
            const data = await fetchAPI(`/delivery-orders/detail?id=${doId}`);
            if (data && (data.data || data.delivery_order)) {
              selectedDOForDetail.value = data.data || data.delivery_order;
            } else {
              triggerToast('Gagal', data?.error || 'Data detail tidak ditemukan', 'error');
              doSubTab.value = 'history';
            }
          } catch (err) {
            console.error(err);
            triggerToast('Error', err.message || 'Gagal memuat detail DO', 'error');
            doSubTab.value = 'history';
          } finally {
            isLoadingDODetail.value = false;
          }
        };

        const openDODetailAndPrint = async (doId, printType = 'surat_jalan') => {
          try {
            isLoadingDODetail.value = true;
            updateRealtimePrintDate();

            let doData = null;
            // Prioritas 1: Gunakan data object langsung jika di-pass dari modul label PL
            if (typeof doId === 'object' && doId !== null) {
              doData = doId;
            } else if (previewDOData.value && (previewDOData.value.id === doId || !doId)) {
              doData = previewDOData.value;
            } else if (selectedDOForDetail.value && selectedDOForDetail.value.id === doId) {
              doData = selectedDOForDetail.value;
            } else if (lastPublishedDO.value && (lastPublishedDO.value.id === doId || lastPublishedDO.value.do_id === doId)) {
              doData = lastPublishedDO.value;
            }

            // Jika belum ada di memori, ambil dari backend (bisa standalone packing label atau DO)
            if (!doData || !doData.crates || doData.crates.length === 0) {
              const fetchId = (typeof doId === 'object' && doId !== null) ? (doId.id || doId.do_id) : doId;
              if (fetchId) {
                const resPl = await fetchAPI(`/packing-labels/detail?id=${fetchId}`);
                if (resPl && resPl.data) {
                  doData = resPl.data;
                } else {
                  const res = await fetchAPI(`/delivery-orders/detail?id=${fetchId}`);
                  if (res && (res.data || res.delivery_order)) {
                    doData = res.data || res.delivery_order;
                  }
                }
              }
            }

            if (doData) {
              if (typeof doData.crates === 'string') {
                try { doData.crates = JSON.parse(doData.crates); } catch (_) { doData.crates = []; }
              } else if (!doData.crates && typeof doData.crates_json === 'string') {
                try { doData.crates = JSON.parse(doData.crates_json); } catch (_) { doData.crates = []; }
              }
            }

            if (!doData) {
              triggerToast('Gagal', 'Data detail Delivery Order tidak ditemukan', 'error');
              return;
            }

            // Normalisasi printType berdasarkan package_type
            let normalizedPrintType = printType;
            if (doData.package_type === 'unit') {
              if (normalizedPrintType === 'label_parent' || normalizedPrintType === 'label_unit' || normalizedPrintType === 'label_peti' || normalizedPrintType === 'label_dus' || normalizedPrintType === 'label_sn') {
                normalizedPrintType = 'label_sn';
              }
            } else if (doData.package_type === 'box') {
              // Jika kemasan PER DUS: tidak ada Peti! Selalu gunakan label_dus!
              if (normalizedPrintType === 'label_parent' || normalizedPrintType === 'label_peti' || normalizedPrintType === 'label_child') {
                normalizedPrintType = 'label_dus';
              }
            } else {
              if (normalizedPrintType === 'label_parent') normalizedPrintType = 'label_peti';
              if (normalizedPrintType === 'label_child') normalizedPrintType = 'label_dus';
            }

            console.log('[DO Print] doData.id:', doData.id, '| package_type:', doData.package_type, '| type:', normalizedPrintType);

            const isUnitLabel = normalizedPrintType === 'label_sn';
            const isBoxOrPetiLabel = normalizedPrintType === 'label_peti' || normalizedPrintType === 'label_dus';
            const isLabel = isUnitLabel || isBoxOrPetiLabel;
            const docNum = (doData.do_number || 'DO').replace(/[\/\\]/g, '_');
            const typeLabel = isUnitLabel ? 'Label_SN'
              : normalizedPrintType === 'label_peti' ? 'Label_Peti'
                : normalizedPrintType === 'label_dus' ? 'Label_Dus'
                  : normalizedPrintType === 'surat_jalan' ? 'Surat_Jalan'
                    : normalizedPrintType === 'packing_list' ? 'Packing_List' : 'Dokumen_Lengkap';

            // Set data reaktif agar Vue merender dokumen yang dipilih ke #do-printable-area
            activePrintDO.value = doData;
            activePrintType.value = normalizedPrintType;

            // Tunggu Vue selesai merender DOM
            await nextTick();
            await nextTick();
            await nextTick();

            // Render QR code jika surat jalan
            if (normalizedPrintType === 'surat_jalan' || normalizedPrintType === 'all') {
              renderDOTrackingQRCode('do-sj-qr-print', activePrintDO.value);
            }

            // Render QR code jika label SN satuan
            if (isUnitLabel) {
              const snList = getDOSerialNumbers(activePrintDO.value);
              snList.forEach((sn, idx) => {
                renderSNLabelQRCode('do-sn-qr-print-' + idx, sn);
              });
            }

            // Tunggu frame berikutnya agar gambar & QR code stabil di DOM
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            await new Promise(resolve => setTimeout(resolve, 300));

            // Setup dynamic rule @page agar browser langsung mencetak dengan ukuran kertas yang tepat
            let styleEl = document.getElementById('do-dynamic-page-rule');
            if (!styleEl) {
              styleEl = document.createElement('style');
              styleEl.id = 'do-dynamic-page-rule';
              document.head.appendChild(styleEl);
            }

            const isSuratJalan = normalizedPrintType === 'surat_jalan';
            const pageRule = isUnitLabel
              ? '@page { size: 80mm 20mm landscape; margin: 0 !important; } @media print { @page { size: 80mm 20mm landscape; margin: 0 !important; } html, body { width: 80mm !important; margin: 0 !important; padding: 0 !important; } .do-page-break { page-break-after: always; break-after: page; } }'
              : isBoxOrPetiLabel
                ? '@page { size: 100mm 150mm; margin: 0 !important; } @media print { @page { size: 100mm 150mm; margin: 0 !important; } html, body { width: 100mm !important; margin: 0 !important; padding: 0 !important; } .do-page-break { page-break-after: always; break-after: page; } }'
                : isSuratJalan
                  ? '@page { size: 8.5in 5.5in landscape; margin: 0 !important; } @media print { @page { size: 8.5in 5.5in landscape; margin: 0 !important; } html, body { width: 8.5in !important; height: 5.5in !important; margin: 0 !important; padding: 0 !important; } .do-page-break { page-break-after: always; break-after: page; } .do-watermark-img { opacity: 0.35 !important; } }'
                  : '@page { size: A4 portrait; margin: 0 !important; } @media print { @page { size: A4 portrait; margin: 0 !important; } html, body { width: 210mm !important; margin: 0 !important; padding: 0 !important; } }';

            styleEl.innerHTML = pageRule;

            const origTitle = document.title;
            document.title = `${typeLabel}_${docNum}`;

            document.body.classList.add('printing-do-only');
            if (isUnitLabel) {
              document.body.classList.add('printing-sn-label-size');
            } else if (isBoxOrPetiLabel) {
              document.body.classList.add('printing-label-size');
            }

            let cleanedUp = false;
            const cleanup = () => {
              if (cleanedUp) return;
              cleanedUp = true;
              document.body.classList.remove('printing-do-only');
              document.body.classList.remove('printing-label-size');
              document.body.classList.remove('printing-sn-label-size');
              document.title = origTitle;
              if (styleEl) styleEl.innerHTML = '';
              activePrintDO.value = null;
              activePrintType.value = null;
              window.onafterprint = null;
            };

            window.onafterprint = cleanup;

            // Langsung buka dialog print di jendela browser saat ini tanpa membuka jendela/tab baru
            window.print();

            // Fallback timeout aman agar preview di dialog cetak browser tidak terhapus prematur
            setTimeout(cleanup, 60000);

          } catch (err) {
            console.error('Error openDODetailAndPrint:', err);
            triggerToast('Error', err.message || 'Gagal memuat dokumen untuk dicetak', 'error');
          } finally {
            isLoadingDODetail.value = false;
          }
        };

        const deleteDeliveryOrder = async (id, doNum) => {
          const ok = await showConfirmCard({
            title: 'Hapus Surat Jalan',
            message: `Apakah Anda yakin ingin menghapus dokumen Surat Jalan "${doNum}"? Seluruh nomor seri di dalamnya akan dikembalikan ke stok gudang.`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus Dokumen',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const data = await fetchAPI('/delivery-orders/delete', {
              method: 'POST',
              body: JSON.stringify({ id })
            });
            if (data && (data.success || data.ok)) {
              triggerToast('Dihapus', `Dokumen ${doNum} berhasil dihapus.`, 'info');
              loadDeliveryOrders();
              loadAvailableFinishedSNs();
            } else {
              triggerToast('Gagal', data?.error || 'Gagal menghapus dokumen', 'error');
            }
          } catch (err) {
            console.error(err);
            triggerToast('Error', err.message || 'Gagal menghapus dokumen', 'error');
          }
        };

        const submitDeliveryOrder = async () => {
          if (sjDuplicateWarning.value) {
            triggerToast('Dokumen Duplikat', 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.', 'error');
            return;
          }

          if (!doForm.value.customer_name.trim()) {
            triggerToast('Perhatian', 'Nama Customer wajib diisi', 'warn');
            goToFormStep('customer');
            return;
          }
          if (!doForm.value.customer_address.trim()) {
            triggerToast('Perhatian', 'Alamat Customer wajib diisi', 'warn');
            goToFormStep('customer');
            return;
          }
          if (!doForm.value.contract_number.trim()) {
            triggerToast('Perhatian', 'Nomor Kontrak wajib diisi', 'warn');
            goToFormStep('reference');
            return;
          }
          if (!doForm.value.program_name.trim()) {
            triggerToast('Perhatian', 'Program Pengadaan wajib diisi', 'warn');
            goToFormStep('reference');
            return;
          }

          // Khusus Kemasan Satuan: Pruning baris kosong otomatis jika ada minimal 1 unit yang terisi
          if (doForm.value.package_type === 'unit') {
            const allUnits = getAllUnitsInForm();
            const filled = allUnits.filter(it => it.serial_number && it.serial_number.trim());
            if (filled.length === 0) {
              triggerToast('Alokasi SN Kosong', 'Harap isi minimal 1 Serial Number unit pengiriman.', 'warn');
              goToFormStep('allocation');
              return;
            }
            doForm.value.package_count = filled.length;
            doForm.value.total_quantity = filled.length;
            const batchNum = Math.max(1, parseInt(doForm.value.batch_number) || 1);
            const newCrates = buildPackagingStructure('unit', filled.length, batchNum);
            filled.forEach((it, idx) => {
              const sn = it.serial_number.trim().toUpperCase();
              newCrates[idx].boxes[0].items[0].serial_number = sn;
              newCrates[idx].crate_id = sn;
            });
            doForm.value.crates = newCrates;
          }

          const totalFilled = getTotalFilledSNs();
          if (totalFilled !== doForm.value.total_quantity) {
            triggerToast('Alokasi SN Belum Lengkap', `Baru ${totalFilled} dari ${doForm.value.total_quantity} SN yang terisi. Lengkapi seluruh slot.`, 'warn');
            goToFormStep('allocation');
            return;
          }

          const seen = new Set();
          const unavailableSNs = [];

          for (const it of getAllUnitsInForm()) {
            const sn = it.serial_number ? it.serial_number.trim().toUpperCase() : '';
            if (seen.has(sn)) {
              triggerToast('Duplikasi SN', `Serial Number "${sn}" terisi lebih dari satu kali.`, 'error');
              return;
            }
            seen.add(sn);

            if (sn && !isSNInList(sn, it)) {
              unavailableSNs.push(sn);
            }
          }

          if (unavailableSNs.length > 0) {
            const sample = unavailableSNs.slice(0, 3).join(', ') + (unavailableSNs.length > 3 ? ` (+${unavailableSNs.length - 3} lainnya)` : '');
            triggerToast('SN Tidak Tersedia', `Terdapat ${unavailableSNs.length} SN yang tidak tersedia atau belum lolos penerimaan gudang: ${sample}. Pastikan seluruh SN telah lolos seluruh tahapan dan penerimaan fisik gudang.`, 'error');
            goToFormStep('allocation');
            return;
          }

          if (doForm.value.package_type === 'unit') {
            (doForm.value.crates || []).forEach(c => {
              const sn = c.boxes?.[0]?.items?.[0]?.serial_number;
              if (sn) c.crate_id = sn;
            });
          }

          if (!doForm.value.do_number || !doForm.value.do_number.trim()) {
            doForm.value.do_number = generateDefaultDONumber(doForm.value.do_date);
          }

          isSubmittingDO.value = true;
          doForm.value.customer_phone = filterNumberAndSymbolsOnly(doForm.value.customer_phone);
          doForm.value.customer_fax = filterNumberAndSymbolsOnly(doForm.value.customer_fax);
          try {
            const targetDate = doForm.value.do_date || getNowWIBDateString();
            const isBack = sjMode.value === 'backdate' || (targetDate < getNowWIBDateString());
            const mode = sjMode.value || (isBack ? 'backdate' : 'realtime');

            const consumedPlIds = [];
            if (doForm.value.consumed_standalone_id) {
              consumedPlIds.push(doForm.value.consumed_standalone_id);
            }
            (doForm.value.crates || []).forEach(c => {
              if (c.consumed_pl_id) consumedPlIds.push(c.consumed_pl_id);
              (c.boxes || []).forEach(b => {
                if (b.consumed_pl_id) consumedPlIds.push(b.consumed_pl_id);
              });
            });

            const payload = {
              ...doForm.value,
              delivery_seq: parseInt(sjDeliverySeq.value, 10) || 1,
              batch_number: parseInt(doForm.value.batch_number, 10) || 1,
              consumed_pl_ids: Array.from(new Set(consumedPlIds.filter(Boolean))),
              sj_mode: mode,
              is_backdate: isBack
            };
            const data = await fetchAPI('/delivery-orders', {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (data && (data.ok || data.success || data.delivery_order || data.data || data.id)) {
              const savedDO = data.delivery_order || data.data || data;
              const doNum = savedDO.do_number || data.do_number || doForm.value.do_number;
              triggerToast('Berhasil Diterbitkan', `Dokumen Surat Jalan ${doNum} berhasil dibuat!`, 'success');

              // Gabungkan data form dengan response API agar modal menampilkan info lengkap
              lastPublishedDO.value = {
                ...doForm.value,
                ...(typeof savedDO === 'object' ? savedDO : {}),
                id: savedDO.id || data.id,
                do_number: doNum,
              };
              showDOPostPublishModal.value = true;
              doSubTab.value = 'history';
              suratJalanInnerTab.value = 'history';

              // Reset Form Surat Jalan ke awal (kembali ke pemilihan jenis backdate / realtime)
              resetDOFormSteps();

              await loadDeliveryOrders();
              await loadAvailableFinishedSNs();
              await loadStandaloneLabels();
              await loadNextDONumber();
              await loadNextBatchNumber();

              // Otomatis unggah Packing List ke Cloudinary jika sudah dikonfigurasi
              if (doSettings.value?.cloudinary?.configured && lastPublishedDO.value?.id) {
                uploadDOPackingListToCloud(lastPublishedDO.value).catch(e => console.error('Auto cloud upload error:', e));
              }
            } else {
              triggerToast('Gagal Menerbitkan', data?.message || data?.error || 'Gagal menyimpan Delivery Order', 'error');
            }
          } catch (err) {
            console.error(err);
            triggerToast('Error', err.message || 'Gagal menyimpan dokumen pengiriman', 'error');
          } finally {
            isSubmittingDO.value = false;
          }
        };

        const getCratesForPrint = computed(() => {
          if (!activePrintDO.value) return [];
          return activePrintDO.value.crates || [];
        });

        const getBoxesForPrint = computed(() => {
          if (!activePrintDO.value) return [];
          const boxes = [];
          (activePrintDO.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => boxes.push(b));
          });
          return boxes;
        });

        const getUnitsForPrint = computed(() => {
          if (!activePrintDO.value) return [];
          const units = [];
          (activePrintDO.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => units.push(it));
            });
          });
          return units;
        });


        const handleCustomerDropdownClickOutside = (e) => {
          if (customerDropdownRef.value && !customerDropdownRef.value.contains(e.target)) {
            isCustomerDropdownOpen.value = false;
          }
        };

    // ===================================================================
    // PAGINATION COMPUTED PROPERTIES (DO & STANDALONE LABELS)
    // ===================================================================
    const pagedDOList = computed(() => {
      const s = pgState.doHistory || { page: 1, size: 10 };
      return (filteredDOList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const filteredStandaloneLabelsList = computed(() => {
      let list = standaloneLabelsList.value || [];

      // 1. Doc / Packaging ID Filter
      if (labelDocFilter.value) {
        const target = labelDocFilter.value.trim().toLowerCase();
        list = list.filter(l => {
          const ids = getLabelPackagingIds(l);
          return ids.some(id => id.toLowerCase().includes(target)) ||
                 (l.pl_number && l.pl_number.trim().toLowerCase() === target) ||
                 (l.label_batch_id && l.label_batch_id.trim().toLowerCase() === target);
        });
      }

      // 2. Package Type Filter
      if (labelPackageFilter.value) {
        list = list.filter(l => (l.package_type || '').toLowerCase() === labelPackageFilter.value.toLowerCase());
      }

      // 3. Date Filter: Tanggal Dari
      if (labelStartDate.value) {
        list = list.filter(l => {
          const dk = getLabelDateKey(l);
          return !dk || dk >= labelStartDate.value;
        });
      }

      // 4. Date Filter: Tanggal Sampai
      if (labelEndDate.value) {
        list = list.filter(l => {
          const dk = getLabelDateKey(l);
          return !dk || dk <= labelEndDate.value;
        });
      }

      // 5. Search Text query
      const q = (labelSearchText.value || '').trim().toLowerCase();
      if (q) {
        list = list.filter(l => {
          const ids = getLabelPackagingIds(l);
          const hasIdMatch = ids.some(id => id.toLowerCase().includes(q));
          const hasSNMatch = (l.crates || []).some(c =>
            (c.boxes || []).some(b =>
              (b.items || []).some(it => String(it.serial_number || '').toLowerCase().includes(q))
            )
          );
          return hasIdMatch ||
                 hasSNMatch ||
                 (l.label_batch_id || '').toLowerCase().includes(q) ||
                 (l.pl_number || '').toLowerCase().includes(q) ||
                 (l.created_by || '').toLowerCase().includes(q) ||
                 (l.product_name || '').toLowerCase().includes(q) ||
                 (l.package_type || '').toLowerCase().includes(q) ||
                 (l.notes || '').toLowerCase().includes(q);
        });
      }

      return list;
    });

    const pagedStandaloneLabelsList = computed(() => {
      const s = pgState.labelsHistory || { page: 1, size: 10 };
      return (filteredStandaloneLabelsList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgStandaloneLabels = computed(() => Math.max(1, Math.ceil((filteredStandaloneLabelsList.value || []).length / ((pgState.labelsHistory && pgState.labelsHistory.size) || 10))));
    const totalPgDOList = computed(() => Math.max(1, Math.ceil((filteredDOList.value || []).length / ((pgState.doHistory && pgState.doHistory.size) || 10))));

    // ===================================================================
    // WATCHERS
    // ===================================================================
    watch([doSearchText, doStartDate, doEndDate, doDocFilter], () => {
      if (pgState.doHistory) pgState.doHistory.page = 1;
    });

    watch([labelSearchText, labelDocFilter, labelPackageFilter, labelStartDate, labelEndDate], () => {
      if (pgState.labelsHistory) pgState.labelsHistory.page = 1;
    });

    watch(() => doForm.value.do_date, (newVal, oldVal) => {
      if (newVal && newVal !== oldVal) {
        onDODateChanged();
      }
    });

    // ===================================================================
    // LIFECYCLE LISTENERS
    // ===================================================================
    onMounted(() => {
      document.addEventListener('click', handleCustomerDropdownClickOutside);
    });

    onUnmounted(() => {
      document.removeEventListener('click', handleCustomerDropdownClickOutside);
    });

    // ===================================================================
    // TAB INITIALIZATION HELPER
    // ===================================================================
    const initDeliveryTab = (targetSub) => {
      if (window.logger) {
        logger.info('DELIVERY', 'Memuat data Delivery Order & Packing List...');
      }
      if (targetSub) {
        setDoSubTab(targetSub);
      }
      loadDoDashboardStats();
      loadCustomers();
      loadDOSettings();
      loadNextDOMeta();
      loadStandaloneLabels();
      syncLabelPackagingStructure();
      updateGeneratedPLNumber();
      updateGeneratedSJNumber();
      loadDOList().then(() => {
        if (doList.value && doList.value.length > 0) {
          if (!previewDOId.value) {
            previewDOId.value = doList.value[0].id;
          }
          loadDOPreviewData(previewDOId.value);
        }
      });
      loadAvailableFinishedSNs();
    };

    // Auto-run immediate prefetch so DO metadata & next number are ready instantly without waiting
    loadNextDOMeta();

    // ===================================================================
    // MODULE EXPORTS
    // ===================================================================
    return {
      initDeliveryTab,
      setDoSubTab,
      doDashboardStats, isLoadingDoStats, loadDoDashboardStats, renderDoQualityChart,
      packingListInnerTab, plMode, plDeliverySeq, plGeneratedId, plDuplicateWarning, plDuplicateMessage, isCheckingPlUnique, updateGeneratedPLNumber, checkPlUniqueness, checkPackagingUniqueness,
      selectPlMode, isPlCardOpen, goToPlStep, onPlHeaderClick, plActiveStep,
      suratJalanInnerTab, sjMode, sjDeliverySeq, sjGeneratedId, sjDuplicateWarning, sjDuplicateMessage, isCheckingSjUnique, updateGeneratedSJNumber, checkSjUniqueness, selectSjMode,
      generateDefaultDONumber,
      cloudForm, showCloudSecret, isTestingCloud, cloudTestResult, isSavingCloudSettings,
      isUploadingCloudPdf, isBatchSyncingCloud, batchSyncProgress,
      uploadDOPackingListToCloud, syncAllDOsToCloud, testCloudinaryConnection, saveCloudinarySettings,
      nkriLogoBase64, nkriLabelLogoBase64, nkriEmblemBase64, doSubTab, doTableDisplayMode, doSJCurrentPage, getSuratJalanVisibleCrates, doSettings, isSavingDOSettings, previewDOId, previewDocType, previewCrateIndex, previewDOData, isLoadingPreviewDO, previewCurrentCrate,
      previewPackingListSheets, previewPackingListSheetIndex, previewCurrentPackingListSheet, printPackingListSheets, getSheetPLNumber, renderDOTrackingQRCode, renderSNLabelQRCode, getDOTrackingFullUrl,
      standaloneLabelsList, isLoadingStandaloneLabels, isSavingStandaloneLabel, activeLabelCrateIndex, labelCratePage, labelCratesPerPage, totalLabelCratePages, pagedLabelCrates, setLabelCratePage, prevLabelCratePage, nextLabelCratePage, selectLabelCrate, prevLabelCrate, nextLabelCrate, quickJumpTarget, jumpToLabelCrate, onLabelCratePageInput, labelForm, lastSavedPLInfo, resetLabelForm, confirmResetLabelForm, syncLabelPackagingStructure, autoFillAvailableSNsForLabel, showLabelBulkModal, labelBulkRawText, labelBulkScope, labelBulkMode, openLabelBulkModal, closeLabelBulkModal, parsedLabelBulkSNs, labelBulkStats, applyLabelBulkData, loadStandaloneLabels, openStandaloneLabelsHistory, saveStandaloneLabel, printStandaloneLabel, deleteStandaloneLabel,
      getLabelFormFilledCount, getLabelFormRequiredCount, getLabelCrateFilledCount, getLabelCrateRequiredCount, isLabelFormComplete, hasInvalidLabelSNs, hasInvalidDOSNs,
      getLabelPackagingIds, generatedPackagingIds, generatedPackagingIdPreview, formatLabelDateOnly, isLabelBackdate, isDOBackdate,
      labelSearchText, labelDocFilter, labelPackageFilter, labelStartDate, labelEndDate, distinctPLLabelNumbers, resetLabelFilter, filteredStandaloneLabelsList, pagedStandaloneLabelsList, totalPgStandaloneLabels,
      loadDOSettings, saveDOSettings, applySettingsToNewDOForm, loadDOPreviewData, openDODocumentPreview, doList, doSearchText, doSearchSN, doSortOrder, doSortField, isTrackingSN, snTrackingResult, toggleDOSortOrder, toggleDOSortBy, getDONumberSeq, getDODateKey, trackSerialNumberDirect, filteredDOList, exportDeliveryOrderExcel, customersList, filteredCustomersList,
      doStartDate, doEndDate, doDocFilter, distinctDODocNumbers, resetDOFilter, pagedDOList, totalPgDOList,
      isCustomerDropdownOpen, customerDropdownRef, showNewCustomerModal, newCustomerForm,
      isSubmittingCustomer, isSavingCustomer: isSubmittingCustomer, customerSearchQuery, availableFinishedSNs, isSubmittingDO,
      defaultCrateIdForNow,
      showDOPostPublishModal, lastPublishedDO, selectedDOForDetail, isLoadingDODetail, openDODetailView,
      activeFormStep, completedSteps, openFormCards, isFormCardOpen, isStepValid, isStepCompleted,
      canAccessStep, goToFormStep, onCardHeaderClick, toggleFormCard, setAllFormCards, resetDOFormSteps,
      selectedConsumedPLId, selectedConsumedPL, isLoadingConsumedPL, applyConsumedPackingList, resetConsumedPackingList,
      productTypesList, doForm, showBatchPasteModal, batchPasteRawText, batchPasteTarget, parsedBatchPasteSNs, batchPasteStats,
      showSelectCrateModal, showSelectBoxModal, selectTarget, selectCrateSearchQuery, selectBoxSearchQuery, filteredAvailablePLCratesList, filteredAvailablePLBoxesList, getAvailablePLCratesList, isPLCrateUsed, openSelectCrateModal, applySelectedCrateToDO, getAvailablePLBoxesList, isPLBoxUsed, openSelectBoxModal, applySelectedBoxToDO,
      activePrintDO, activePrintType, activeContainerIndex, getBoxFilledCount, isBoxComplete,
      isBoxIndexLocked, isCrateComplete, isCrateIndexLocked, getUnitBatches, getUnitBatchFilledCount,
      isUnitBatchComplete, isUnitBatchLocked, nextContainer, prevContainer, selectContainer,
      isDOSlotLocked, autofillSingleBox, autofillSingleCrate, autofillSingleUnitBatch,
      selectCustomer, openNewCustomerModal, saveNewCustomer, loadCustomers, deleteCustomer, onLabelSNInput, getLabelSlotStatus, getAvailableSNsForLabelSlot, loadDeliveryOrders,
      loadAvailableFinishedSNs, loadNextDONumber, loadNextBatchNumber, loadNextDOMeta, loadDOList, setPackageType,
      buildPackagingStructure, syncPackagingStructure, buildCratesForQuantity, getAllBoxesInForm, getAllUnitsInForm,
      getTotalFilledSNs, getCrateFilledCount, isSNInList, getAvailableSNsForSlot,
      openBatchPasteModal, applyBatchPaste, addUnitToDOForm, removeUnitFromDOForm, updateUnitCountDirectly, openDODetailAndPrint, deleteDeliveryOrder,
      submitDeliveryOrder, getCratesForPrint, getBoxesForPrint, getUnitsForPrint,
      formatIndoDate,
      formatIndoDateTime,
      formatNumericDate, formatDocDate, currentRealtimePrintDate, updateRealtimePrintDate, getDOSerialNumbers, getDOTotalQuantity, getDOCrateCount, getDOBoxCount,
      unitDropdown, onUnitInputFocus, onUnitInputBlur, onUnitInputChange, getFilteredUnitSuggestions, selectUnitSN, addUnitRowAutomatically, handleUnitInputKeyDown, removeUnitRowDirectly, autofillAllUnits,
      labelSlotDropdown, openLabelSlotDropdown, toggleLabelSlotDropdown, closeLabelSlotDropdown, isLabelSlotDropdownOpen, onLabelSlotInput, selectLabelSlotSN, clearLabelSlotSN, getFilteredSNsForLabelSlot, handleLabelSlotKeyDown,
      doSlotDropdown, openDOSlotDropdown, toggleDOSlotDropdown, closeDOSlotDropdown, isDOSlotDropdownOpen, selectDOSlotSN, clearDOSlotSN, getFilteredSNsForDOSlot, handleDOSlotKeyDown,
      onDODateChanged,
      filterNumberAndSymbolsOnly, blockLetterKeys, onPhoneFaxInput
    };
  };
})(window);
