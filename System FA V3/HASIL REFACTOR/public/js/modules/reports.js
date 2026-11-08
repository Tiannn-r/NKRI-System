/**
 * Multi-Category Reports & Traceability Module (Fase 2H)
 * Sistem Final Assembly & Traceability P9 - NKRI
 *
 * Encapsulates:
 * 1. Multi-Category Traceability Reports (WO, Assembly, TF1, TF2 QC, Warehouse Receipts)
 * 2. Multi-column interactive sorting & deep filtering (Dates, Operators, Status, Doc, Search Query)
 * 3. Document view vs Detailed unit view modes & Detail modals
 * 4. Enterprise Excel exporter with auto-calculated column widths and status highlights
 * 5. Report-specific pagination & pipeline summaries
 */
(function (window) {
  'use strict';

  window.createReportsModule = function (ctx) {
    const { ref, reactive, computed, watch, nextTick } = ctx.Vue || Vue;

    // External dependencies provided by app context
    const fetchAPI = ctx.fetchAPI || window.api?.fetchAPI;
    const triggerToast = ctx.triggerToast || function (title, msg, type) {
      if (window.Swal) Swal.fire({ title, text: msg, icon: type || 'info' });
      else alert((title || '') + ': ' + (msg || ''));
    };

    const currentTab = ctx.currentTab || ref('report');
    const pgState = ctx.pgState || reactive({
      woReport: { page: 1, size: 20 },
      assyReport: { page: 1, size: 20 },
      assyTestReport: { page: 1, size: 20 },
      qcReport: { page: 1, size: 20 },
      warehouseReport: { page: 1, size: 20 }
    });

    const operatorUsersList = ctx.operatorUsersList || ref([]);
    const traysList = ctx.traysList || ref([]);
    const assemblyResults = ctx.assemblyResults || ref([]);
    const isReassemblySet = ctx.isReassemblySet || (() => false);

    const getOperatorFullName = ctx.getOperatorFullName || function (name) { return name || '-'; };
    const getTodayDateStr = ctx.getTodayDateStr || function () {
      const d = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    };
    const getFormattedDocCode = ctx.getFormattedDocCode || function (code, type) { return code || '-'; };
    const getPUDocNumber = ctx.getPUDocNumber || function (code) { return code || '-'; };
    const getHistoryAssemblyStatus = (it) => {
      if (!it) return 'Good';
      if (it.original_assembly_status) {
        const oSt = String(it.original_assembly_status).trim().toLowerCase();
        if (oSt === 'good') return 'Good';
        if (oSt === 'hold') return 'Hold';
        if (oSt === 'scrap') return 'Scrap';
        if (oSt === 'declined') return 'Declined';
        if (oSt !== 'ru' && oSt !== 'dirakit ulang' && oSt !== 'rework') {
          return it.original_assembly_status.charAt(0).toUpperCase() + it.original_assembly_status.slice(1).toLowerCase();
        }
      }
      const rawSt = String(it.assembly_status || it.status || '').trim().toLowerCase();
      if (rawSt === 'hold') return 'Hold';
      if (rawSt === 'scrap') return 'Scrap';
      if (rawSt === 'declined') return 'Declined';
      if (rawSt && rawSt !== 'ru' && rawSt !== 'dirakit ulang' && rawSt !== 'rework') {
        return (it.assembly_status || it.status).charAt(0).toUpperCase() + (it.assembly_status || it.status).slice(1).toLowerCase();
      }
      return 'Good';
    };
    const getAssemblyStageStatus = (it) => {
      if (!it) return 'Good';
      if (ctx.getAssemblyStageStatus && typeof ctx.getAssemblyStageStatus === 'function') {
        const st = ctx.getAssemblyStageStatus(it);
        if (st) return st;
      }
      if (it.original_assembly_status) {
        const oSt = String(it.original_assembly_status).trim().toLowerCase();
        if (oSt === 'good') return 'Good';
        if (oSt === 'hold') return 'Hold';
        if (oSt === 'scrap') return 'Scrap';
        if (oSt === 'declined') return 'Declined';
        if (oSt === 'ru' || oSt === 'dirakit ulang') return 'RU';
      }
      const rawSt = String(it.assembly_status || it.status || '').trim().toLowerCase();
      if (rawSt === 'declined' || Boolean(it.is_declined)) return 'Declined';
      if (rawSt === 'hold') return 'Hold';
      if (rawSt === 'scrap') return 'Scrap';
      if (rawSt === 'ru' || rawSt === 'dirakit ulang') return 'RU';
      return 'Good';
    };
    const formatDocumentStatusText = ctx.formatDocumentStatusText || function (status) {
      if (!status || status === '-') return '-';
      const s = String(status).trim().toUpperCase();
      if (s === 'NOT_GOOD' || s === 'NOT GOOD' || s === 'NG') return 'NOT GOOD';
      if (s === 'ACC' || s === 'ACCEPTED' || s === 'DITERIMA' || s === 'TERIMA') return 'ACCEPTED';
      if (s === 'DECLINED' || s === 'DECLINE' || s === 'DITOLAK' || s === 'TOLAK') return 'DECLINED';
      if (s === 'PASS' || s === 'PASSED' || s === 'GOOD' || s === 'LOLOS' || s.includes('GOOD')) return 'GOOD';
      if (s === 'HOLD' || s === 'HOLD_ASSY' || s === 'HOLD_QC' || s.startsWith('HOLD')) return 'HOLD';
      if (s === 'RU' || s === 'DIRAKIT ULANG' || s === 'RAKIT ULANG') return 'DIRAKIT ULANG';
      if (s === 'REWORK' || s === 'REWORK_ASSY' || s === 'REWORK_QC') return 'HOLD';
      if (s === 'SCRAP' || s === 'FAIL') return 'SCRAP';
      if (s === 'PENDING' || s.includes('MENUNGGU') || s.includes('BELUM')) return 'PENDING';
      return String(status);
    };
    const loadTrays = ctx.loadTrays || (async () => {});
    const loadAssemblyResults = ctx.loadAssemblyResults || (async () => {});
    const openDetailModal = ctx.openDetailModal || function () {};
    const navigateTo = ctx.navigateTo || function () {};

    // ===================================================================
    // 1. STATE & FILTERS
    // ===================================================================
    const reportSubTab = ctx.reportSubTab || ref('wo');
    const reportFilters = ctx.reportFilters || ref({
      wo: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
      assembly: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
      assy_test: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
      qc_test: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
      warehouse_receipt: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' }
    });

    const reportViewMode = ref('document');
    const selectedReportDoc = ref(null);

    const laporanList = ref([]);
    const searchQuery = ref('');
    const laporanFilter = ref({
      startDate: '',
      endDate: '',
      operator: '',
      status: '',
      trayCode: ''
    });

    const assyTestReports = ctx.assyTestReports || ref([]);
    const qcTestReports = ctx.qcTestReports || ref([]);
    const warehouseReceiptReports = ctx.warehouseReceiptReports || ref([]);

    const resetLaporanFilter = () => {
      laporanFilter.value = {
        startDate: '',
        endDate: '',
        operator: '',
        status: '',
        trayCode: ''
      };
      searchQuery.value = '';
    };

    const distinctLaporanTrays = computed(() => {
      const set = new Set();
      (laporanList.value || []).forEach(l => {
        if (l.tray_code) set.add(l.tray_code);
      });
      return Array.from(set).sort();
    });

    const distinctLaporanOperators = computed(() => {
      const set = new Set();
      (laporanList.value || []).forEach(l => {
        if (l.pic_assembly) set.add(getOperatorFullName(l.pic_assembly));
      });
      (operatorUsersList.value || []).forEach(u => {
        if (u.full_name) set.add(u.full_name);
        else if (u.username) set.add(u.username);
      });
      return Array.from(set).filter(Boolean).sort();
    });

    // ===================================================================
    // 2. DATA FETCHING METHODS
    // ===================================================================
    const loadLaporan = async () => {
      try {
        const f = reportFilters.value ? reportFilters.value.wo : null;
        const params = new URLSearchParams();
        if (f) {
          if (f.startDate) params.append('start_date', f.startDate);
          if (f.endDate) params.append('end_date', f.endDate);
          if (f.operator) params.append('operator', f.operator);
          if (f.docNumber) params.append('tray_code', f.docNumber);
          if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') params.append('status', f.status);
          if (f.searchQuery) params.append('q', f.searchQuery);
          if (f.sortOrder) params.append('order', f.sortOrder);
          if (f.sortBy) params.append('sort_by', f.sortBy);
        }
        const queryStr = params.toString() ? '?' + params.toString() : '';
        const json = await fetchAPI('/laporan' + queryStr);
        if (json.ok) {
          laporanList.value = json.data || [];
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const loadAssyTestReports = async () => {
      try {
        const f = reportFilters.value.assy_test;
        const params = new URLSearchParams();
        if (f.startDate) params.append('start_date', f.startDate);
        if (f.endDate) params.append('end_date', f.endDate);
        if (f.operator) params.append('operator', f.operator);
        if (f.docNumber) params.append('doc_number', f.docNumber);
        if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') params.append('status', f.status);
        if (f.searchQuery) params.append('q', f.searchQuery);
        if (f.sortOrder) params.append('order', f.sortOrder);
        if (f.sortBy) params.append('sort_by', f.sortBy);
        const res = await fetchAPI('/reports/assy-tests?' + params.toString());
        if (res.ok) {
          assyTestReports.value = res.data || [];
        }
      } catch (_) { }
    };

    const loadQCTestReports = async () => {
      try {
        const f = reportFilters.value.qc_test;
        const params = new URLSearchParams();
        if (f.startDate) params.append('start_date', f.startDate);
        if (f.endDate) params.append('end_date', f.endDate);
        if (f.operator) params.append('operator', f.operator);
        if (f.docNumber) params.append('doc_number', f.docNumber);
        if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') params.append('status', f.status);
        if (f.searchQuery) params.append('q', f.searchQuery);
        if (f.sortOrder) params.append('order', f.sortOrder);
        if (f.sortBy) params.append('sort_by', f.sortBy);
        const res = await fetchAPI('/reports/qc-tests?' + params.toString());
        if (res.ok) {
          qcTestReports.value = res.data || [];
        }
      } catch (_) { }
    };

    const loadWarehouseReceiptReports = async () => {
      try {
        const f = reportFilters.value.warehouse_receipt;
        const params = new URLSearchParams();
        if (f.startDate) params.append('start_date', f.startDate);
        if (f.endDate) params.append('end_date', f.endDate);
        if (f.operator) params.append('operator', f.operator);
        if (f.docNumber) params.append('doc_number', f.docNumber);
        if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') params.append('status', f.status);
        if (f.searchQuery) params.append('q', f.searchQuery);
        if (f.sortOrder) params.append('order', f.sortOrder);
        if (f.sortBy) params.append('sort_by', f.sortBy);
        const res = await fetchAPI('/reports/warehouse-receipts?' + params.toString());
        if (res.ok) {
          warehouseReceiptReports.value = res.data || [];
        }
      } catch (_) { }
    };

    const setReportSubTab = (sub, isFromPopState = false) => {
      if (selectedReportDoc.value) {
        selectedReportDoc.value = null;
      }
      reportSubTab.value = sub;
      initReportsTab(sub);
      navigateTo('report', sub, isFromPopState);
    };

    const initReportsTab = (targetSub = null) => {
      const sub = targetSub || reportSubTab.value || 'wo';
      reportSubTab.value = sub;
      if (sub === 'wo') {
        loadLaporan();
        loadTrays();
        if (!assemblyResults.value || !assemblyResults.value.length) loadAssemblyResults();
      } else if (sub === 'assembly') {
        loadAssemblyResults();
        loadLaporan();
      } else if (sub === 'assy_test') {
        loadAssyTestReports();
      } else if (sub === 'qc_test') {
        loadQCTestReports();
      } else if (sub === 'warehouse_receipt') {
        loadWarehouseReceiptReports();
      }
    };

    // ===================================================================
    // 3. SORTING HELPERS
    // ===================================================================
    const compareReportValues = (a, b, isAsc) => {
      if (a === null || a === undefined) a = '';
      if (b === null || b === undefined) b = '';
      if (typeof a === 'number' && typeof b === 'number') {
        return isAsc ? a - b : b - a;
      }
      const strA = String(a).trim();
      const strB = String(b).trim();
      const cmp = strA.localeCompare(strB, undefined, { numeric: true, sensitivity: 'base' });
      return isAsc ? cmp : -cmp;
    };

    const setReportSort = (field) => {
      const sub = reportSubTab.value;
      const cur = reportFilters.value[sub];
      if (cur.sortBy === field) {
        cur.sortOrder = cur.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        cur.sortBy = field;
        cur.sortOrder = 'asc';
      }
    };

    const toggleReportSortOrder = () => {
      const sub = reportSubTab.value;
      const cur = reportFilters.value[sub];
      cur.sortOrder = cur.sortOrder === 'asc' ? 'desc' : 'asc';
    };

    const getReportSortIconClass = (field) => {
      const sub = reportSubTab.value;
      const cur = reportFilters.value[sub];
      if (cur.sortBy !== field) {
        return 'fa-solid fa-sort text-slate-300 opacity-60 group-hover:opacity-100 group-hover:text-slate-500 text-[10px] ml-1';
      }
      return cur.sortOrder === 'asc'
        ? 'fa-solid fa-arrow-up-long text-red-600 font-bold text-xs ml-1'
        : 'fa-solid fa-arrow-down-long text-red-600 font-bold text-xs ml-1';
    };

    const getReportSortLabel = () => {
      const sub = reportSubTab.value;
      const cur = reportFilters.value[sub];
      const fieldMap = {
        date: 'Tanggal & Waktu',
        sn15: 'SN Part 15',
        sn1: 'PN Part 1',
        sn12: 'PN Part 12',
        doc: 'No. Dokumen / Tray',
        warehouse_officer: 'Petugas Gudang',
        operator: 'Operator / Petugas',
        status: 'Status',
        reason: 'Keterangan'
      };
      const fName = fieldMap[cur.sortBy] || cur.sortBy;
      const dirName = cur.sortOrder === 'asc' ? 'Ascending (A-Z)' : 'Descending (Z-A)';
      return `${fName} • ${dirName}`;
    };

    const getReportCategoryTitle = (subTab) => {
      if (subTab === 'wo') return 'Supply (WH/WO)';
      if (subTab === 'assembly') return 'Perakitan (WH/ASSY)';
      if (subTab === 'assy_test') return 'TF 1 — Test Functional 1 (ASSY/QC)';
      if (subTab === 'qc_test') return 'TF 2 — Test Functional 2 (WH/QC)';
      if (subTab === 'warehouse_receipt') return 'Good Receipts (WH/GR)';
      return 'Laporan';
    };

    const resetCurrentReportFilter = () => {
      const targetKey = currentTab.value === 'warehouse_receipt' ? 'warehouse_receipt' : (reportSubTab.value || 'wo');
      reportFilters.value[targetKey] = {
        startDate: '',
        endDate: '',
        operator: '',
        docNumber: '',
        searchQuery: '',
        status: '',
        sortBy: 'date',
        sortOrder: 'desc'
      };
      if (targetKey === 'wo') loadLaporan();
      else if (targetKey === 'assy_test') loadAssyTestReports();
      else if (targetKey === 'qc_test') loadQCTestReports();
      else if (targetKey === 'warehouse_receipt') loadWarehouseReceiptReports();
    };

    // ===================================================================
    // 4. COMPUTED FILTERED REPORTS
    // ===================================================================
    const filteredLaporan = computed(() => {
      const list = (laporanList.value || []).filter(l => {
        const dateKey = l.finished_at ? l.finished_at.substring(0, 10) : (l.tray_created_at ? l.tray_created_at.substring(0, 10) : '');
        if (laporanFilter.value.startDate && dateKey && dateKey < laporanFilter.value.startDate) return false;
        if (laporanFilter.value.endDate && dateKey && dateKey > laporanFilter.value.endDate) return false;

        if (laporanFilter.value.operator) {
          const targetOp = laporanFilter.value.operator.toLowerCase().trim();
          const pAssy = getOperatorFullName(l.pic_assembly).toLowerCase().trim();
          const rawAssy = (l.pic_assembly || '').toLowerCase().trim();
          const match = pAssy === targetOp || pAssy.includes(targetOp) || rawAssy === targetOp || rawAssy.includes(targetOp);
          if (!match) return false;
        }

        if (laporanFilter.value.status) {
          const filterStat = laporanFilter.value.status.toLowerCase().trim();
          const itemStat = (l.status || 'Good').toLowerCase().trim();
          if (itemStat !== filterStat) return false;
        }

        if (laporanFilter.value.trayCode) {
          if ((l.tray_code || '').toLowerCase() !== laporanFilter.value.trayCode.toLowerCase()) return false;
        }

        if (searchQuery.value && searchQuery.value.trim()) {
          const q = searchQuery.value.toLowerCase().trim();
          const effStatus = (l.status || 'Good').toLowerCase();
          const match = (l.tray_code && l.tray_code.toLowerCase().includes(q)) ||
            (l.source_document_mo && l.source_document_mo.toLowerCase().includes(q)) ||
            (l.part15_sn && l.part15_sn.toLowerCase().includes(q)) ||
            (l.part1_sn && l.part1_sn.toLowerCase().includes(q)) ||
            (l.part12_sn && l.part12_sn.toLowerCase().includes(q)) ||
            (l.pic_assembly && l.pic_assembly.toLowerCase().includes(q)) ||
            (l.pic_warehouse && l.pic_warehouse.toLowerCase().includes(q)) ||
            (l.assigned_operator && l.assigned_operator.toLowerCase().includes(q)) ||
            getOperatorFullName(l.pic_assembly).toLowerCase().includes(q) ||
            getOperatorFullName(l.pic_warehouse).toLowerCase().includes(q) ||
            getOperatorFullName(l.assigned_operator).toLowerCase().includes(q) ||
            (l.reason && l.reason.toLowerCase().includes(q)) ||
            effStatus.includes(q);
          if (!match) return false;
        }

        return true;
      });

      return list.sort((a, b) => {
        const timeA = new Date(a.finished_at || a.started_at || a.tray_created_at || 0).getTime();
        const timeB = new Date(b.finished_at || b.started_at || b.tray_created_at || 0).getTime();
        if (timeB !== timeA) return timeB - timeA;
        return (b.id || 0) - (a.id || 0);
      });
    });

    const filteredAssemblyReport = computed(() => {
      const f = reportFilters.value ? reportFilters.value.assembly : null;
      const trays = assemblyResults.value || [];
      let list = [];
      trays.forEach(t => {
        (t.items || []).forEach(it => {
          list.push({
            ...it,
            tray_code: t.tray_code,
            source_document_mo: t.source_document_mo,
            pic_assembly: it.pic_assembly || t.pic_assembly,
            tray_created_at: t.tray_created_at,
            finished_at: it.finished_at || t.tray_created_at,
            original_assembly_status: it.original_assembly_status || (it.reason && it.reason !== '-' ? 'Hold' : 'Good'),
            assembly_status: it.original_assembly_status || it.assembly_status || it.status || 'Good',
            assembly_reason: (it.assembly_reason && it.assembly_reason !== '-') ? it.assembly_reason : (it.reason && it.reason !== '-' ? it.reason : '-')
          });
        });
      });

      if (!list.length && laporanList.value && laporanList.value.length) {
        list = laporanList.value.map(l => ({
          ...l,
          assembly_id: l.id,
          assembly_status: l.status || 'Good',
          assembly_reason: l.reason || '-'
        }));
      }
      if (!f) return list;
      if (f.startDate) {
        list = list.filter(it => (it.finished_at || it.tray_created_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(it => (it.finished_at || it.tray_created_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.operator) {
        const op = f.operator.toLowerCase();
        list = list.filter(it => (it.pic_assembly || '').toLowerCase().includes(op));
      }
      if (f.docNumber) {
        const d = f.docNumber.toLowerCase();
        list = list.filter(it => {
          const isPUAllocated = (typeof isSetAllocatedForReassembly === 'function' && isSetAllocatedForReassembly(it)) || Boolean(it.is_reregistered || it.rereg_wo_suffix || it.rereg_pending_operator);
          const assyDoc = (isPUAllocated ? (getSetPUDocNumber(it) || getFormattedDocCode(it.tray_code, 'assy_rework')) : ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy'))).toLowerCase();
          const puDoc = getPUDocNumber(it.tray_code).toLowerCase();
          const rawTray = (it.tray_code || '').toLowerCase();
          const suffix = (it.tray_code ? it.tray_code.split('/').pop() : '').toLowerCase();
          return assyDoc.includes(d) || puDoc.includes(d) || rawTray.includes(d) || (suffix && d.includes(suffix));
        });
      }
      if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') {
        list = list.filter(it => (it.assembly_status || it.status || 'Good').toLowerCase() === f.status.toLowerCase());
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(it => {
          return (it.part15_sn || '').toLowerCase().includes(q) ||
            (it.part1_sn || '').toLowerCase().includes(q) ||
            (it.part12_sn || '').toLowerCase().includes(q) ||
            (it.tray_code || '').toLowerCase().includes(q) ||
            (it.assembly_reason || '').toLowerCase().includes(q) ||
            (it.reason || '').toLowerCase().includes(q);
        });
      }
      const isAsc = f.sortOrder === 'asc';
      return [...list].sort((a, b) => {
        let valA, valB;
        switch (f.sortBy) {
          case 'sn15':
            valA = a.part15_sn || '';
            valB = b.part15_sn || '';
            break;
          case 'sn1':
            valA = a.part1_sn || '';
            valB = b.part1_sn || '';
            break;
          case 'sn12':
            valA = a.part12_sn || '';
            valB = b.part12_sn || '';
            break;
          case 'doc':
            valA = a.tray_code || '';
            valB = b.tray_code || '';
            break;
          case 'mo':
            valA = a.source_document_mo || '';
            valB = b.source_document_mo || '';
            break;
          case 'operator':
            valA = a.pic_assembly || '';
            valB = b.pic_assembly || '';
            break;
          case 'status':
            valA = a.assembly_status || a.status || 'Good';
            valB = b.assembly_status || b.status || 'Good';
            break;
          case 'reason':
            valA = a.assembly_reason || a.reason || '';
            valB = b.assembly_reason || b.reason || '';
            break;
          case 'date':
          default:
            valA = a.finished_at || a.started_at || a.tray_created_at || '';
            valB = b.finished_at || b.started_at || b.tray_created_at || '';
            break;
        }
        return compareReportValues(valA, valB, isAsc);
      });
    });

    const filteredWOReport = computed(() => {
      let list = [];
      if (laporanList.value && laporanList.value.length) {
        list = laporanList.value;
      } else if (assemblyResults.value && assemblyResults.value.length) {
        assemblyResults.value.forEach(t => {
          (t.items || []).forEach(it => {
            list.push({
              ...it,
              tray_code: t.tray_code,
              source_document_mo: t.source_document_mo,
              pic_warehouse: t.pic_warehouse,
              assigned_operator: t.assigned_operator || it.pic_assembly,
              created_at: t.tray_created_at || t.created_at,
              tray_created_at: t.tray_created_at || t.created_at,
              started_at: it.started_at || t.tray_created_at || t.created_at,
              finished_at: it.finished_at || t.tray_created_at || t.created_at,
              session_status: t.session_status,
              finished_count: t.finished_count,
              total_sets: t.total_sets
            });
          });
        });
      } else {
        list = traysList.value || [];
      }

      const f = reportFilters.value ? reportFilters.value.wo : null;
      if (!f) return list;
      if (f.startDate) {
        list = list.filter(t => (t.tray_created_at || t.created_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(t => (t.tray_created_at || t.created_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.operator) {
        const op = f.operator.toLowerCase();
        list = list.filter(t => (t.pic_warehouse || '').toLowerCase().includes(op) || (t.assigned_operator || '').toLowerCase().includes(op));
      }
      if (f.docNumber) {
        const d = f.docNumber.toLowerCase();
        list = list.filter(t => {
          const woDoc = getFormattedDocCode(t.tray_code, 'wo').toLowerCase();
          const rawTray = (t.tray_code || '').toLowerCase();
          const suffix = (t.tray_code ? t.tray_code.split('/').pop() : '').toLowerCase();
          return woDoc.includes(d) || rawTray.includes(d) || (suffix && d.includes(suffix));
        });
      }
      if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') {
        const targetStatus = f.status.trim().toUpperCase();
        list = list.filter(t => {
          const isFinished = (t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.status === 'Good' || t.status === 'SELESAI' || t.session_status === 'COMPLETED_SHIFT' || t.tray_status === 'returned' || t.status === 'returned' || t.status === 'Dikembalikan ke Gudang';
          if (targetStatus === 'SELESAI' || targetStatus === 'COMPLETED') return isFinished;
          if (targetStatus === 'AKTIF' || targetStatus === 'ACTIVE' || targetStatus === 'IN_PROGRESS' || targetStatus === 'TERSUPPLY') return !isFinished;
          return String(t.status || t.session_status || '').toUpperCase().includes(targetStatus);
        });
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(t =>
          ((t.part15_sn || t.p9_sn) && (t.part15_sn || t.p9_sn).toLowerCase().includes(q)) ||
          (t.part1_sn && t.part1_sn.toLowerCase().includes(q)) ||
          (t.part12_sn && t.part12_sn.toLowerCase().includes(q)) ||
          (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
          (t.source_document_mo && t.source_document_mo.toLowerCase().includes(q)) ||
          (t.pic_warehouse && t.pic_warehouse.toLowerCase().includes(q)) ||
          (t.assigned_operator && t.assigned_operator.toLowerCase().includes(q)) ||
          (t.session_status && t.session_status.toLowerCase().includes(q)) ||
          (t.reason && t.reason.toLowerCase().includes(q))
        );
      }
      const isAsc = f.sortOrder === 'asc';
      return [...list].sort((a, b) => {
        let valA, valB;
        switch (f.sortBy) {
          case 'doc':
            valA = a.tray_code || '';
            valB = b.tray_code || '';
            break;
          case 'mo':
            valA = a.source_document_mo || '';
            valB = b.source_document_mo || '';
            break;
          case 'sn15':
            valA = a.part15_sn || a.p9_sn || '';
            valB = b.part15_sn || b.p9_sn || '';
            break;
          case 'sn1':
            valA = a.part1_sn || '';
            valB = b.part1_sn || '';
            break;
          case 'sn12':
            valA = a.part12_sn || '';
            valB = b.part12_sn || '';
            break;
          case 'warehouse_officer':
            valA = getOperatorFullName(a.pic_warehouse) || a.pic_warehouse || '';
            valB = getOperatorFullName(b.pic_warehouse) || b.pic_warehouse || '';
            break;
          case 'operator':
            valA = getOperatorFullName(a.assigned_operator || a.pic_warehouse) || a.assigned_operator || a.pic_warehouse || '';
            valB = getOperatorFullName(b.assigned_operator || b.pic_warehouse) || b.assigned_operator || b.pic_warehouse || '';
            break;
          case 'status':
            valA = (a.finished_count >= a.total_sets && a.total_sets > 0) || a.finished_at || a.status === 'Good' || a.status === 'SELESAI' ? 'SELESAI' : (a.session_status || 'AKTIF');
            valB = (b.finished_count >= b.total_sets && b.total_sets > 0) || b.finished_at || b.status === 'Good' || b.status === 'SELESAI' ? 'SELESAI' : (b.session_status || 'AKTIF');
            break;
          case 'date':
          default:
            valA = a.tray_created_at || a.created_at || '';
            valB = b.tray_created_at || b.created_at || '';
            break;
        }
        return compareReportValues(valA, valB, isAsc);
      });
    });

    const filteredAssyTestReport = computed(() => {
      let list = assyTestReports.value || [];
      const f = reportFilters.value.assy_test;
      if (f.startDate) {
        list = list.filter(t => (t.created_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(t => (t.created_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.operator) {
        const op = f.operator.toLowerCase();
        list = list.filter(t => (t.tester_username || '').toLowerCase().includes(op) || (t.tester_full_name || '').toLowerCase().includes(op));
      }
      if (f.docNumber) {
        const d = f.docNumber.toLowerCase();
        list = list.filter(t => (t.doc_number || '').toLowerCase().includes(d) || (t.tray_code || '').toLowerCase().includes(d) || (t.assy_doc_number || '').toLowerCase().includes(d));
      }
      if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') {
        const targetStatus = f.status.trim().toUpperCase();
        if (targetStatus === 'MENUNGGU') {
          list = list.filter(t => (t.status || '').toUpperCase().includes('MENUNGGU') || (t.status || '').toUpperCase().includes('BELUM'));
        } else {
          list = list.filter(t => (t.status || '').trim().toUpperCase() === targetStatus);
        }
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(t =>
          (t.part15_sn && t.part15_sn.toLowerCase().includes(q)) ||
          (t.part12_sn && t.part12_sn.toLowerCase().includes(q)) ||
          (t.part1_sn && t.part1_sn.toLowerCase().includes(q)) ||
          (t.doc_number && t.doc_number.toLowerCase().includes(q)) ||
          (t.assy_doc_number && t.assy_doc_number.toLowerCase().includes(q)) ||
          (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
          (t.failure_reasons && t.failure_reasons.toLowerCase().includes(q))
        );
      }
      const isAsc = f.sortOrder === 'asc';
      return [...list].sort((a, b) => {
        let valA, valB;
        switch (f.sortBy) {
          case 'doc':
            valA = a.doc_number || a.tray_code || '';
            valB = b.doc_number || b.tray_code || '';
            break;
          case 'sn15':
            valA = a.part15_sn || a.p9_sn || '';
            valB = b.part15_sn || b.p9_sn || '';
            break;
          case 'sn1':
            valA = a.part1_sn || '';
            valB = b.part1_sn || '';
            break;
          case 'sn12':
            valA = a.part12_sn || '';
            valB = b.part12_sn || '';
            break;
          case 'operator':
            valA = a.tester_full_name || a.tester_username || '';
            valB = b.tester_full_name || b.tester_username || '';
            break;
          case 'status':
            valA = a.status || '';
            valB = b.status || '';
            break;
          case 'date':
          default:
            valA = a.created_at || '';
            valB = b.created_at || '';
            break;
        }
        return compareReportValues(valA, valB, isAsc);
      });
    });

    const filteredQCTestReport = computed(() => {
      let list = qcTestReports.value || [];
      const f = reportFilters.value.qc_test;
      if (f.startDate) {
        list = list.filter(t => (t.created_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(t => (t.created_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.operator) {
        const op = f.operator.toLowerCase();
        list = list.filter(t => (t.tester_username || '').toLowerCase().includes(op) || (t.tester_full_name || '').toLowerCase().includes(op));
      }
      if (f.docNumber) {
        const d = f.docNumber.toLowerCase();
        list = list.filter(t => (t.doc_number || '').toLowerCase().includes(d) || (t.tf1_doc_number || '').toLowerCase().includes(d) || (t.assy_doc_number || '').toLowerCase().includes(d) || (t.tray_code || '').toLowerCase().includes(d));
      }
      if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') {
        const targetStatus = f.status.trim().toUpperCase();
        if (targetStatus === 'MENUNGGU') {
          list = list.filter(t => (t.status || '').toUpperCase().includes('MENUNGGU') || (t.status || '').toUpperCase().includes('BELUM'));
        } else {
          list = list.filter(t => (t.status || '').trim().toUpperCase() === targetStatus);
        }
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(t =>
          (t.part15_sn && t.part15_sn.toLowerCase().includes(q)) ||
          (t.part12_sn && t.part12_sn.toLowerCase().includes(q)) ||
          (t.part1_sn && t.part1_sn.toLowerCase().includes(q)) ||
          (t.doc_number && t.doc_number.toLowerCase().includes(q)) ||
          (t.tf1_doc_number && t.tf1_doc_number.toLowerCase().includes(q)) ||
          (t.assy_doc_number && t.assy_doc_number.toLowerCase().includes(q)) ||
          (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
          (t.failure_reasons && t.failure_reasons.toLowerCase().includes(q))
        );
      }
      const isAsc = f.sortOrder === 'asc';
      return [...list].sort((a, b) => {
        let valA, valB;
        switch (f.sortBy) {
          case 'doc':
            valA = a.doc_number || a.tray_code || '';
            valB = b.doc_number || b.tray_code || '';
            break;
          case 'sn15':
            valA = a.part15_sn || a.p9_sn || '';
            valB = b.part15_sn || b.p9_sn || '';
            break;
          case 'sn1':
            valA = a.part1_sn || '';
            valB = b.part1_sn || '';
            break;
          case 'sn12':
            valA = a.part12_sn || '';
            valB = b.part12_sn || '';
            break;
          case 'operator':
            valA = a.tester_full_name || a.tester_username || '';
            valB = b.tester_full_name || b.tester_username || '';
            break;
          case 'status':
            valA = a.status || '';
            valB = b.status || '';
            break;
          case 'date':
          default:
            valA = a.created_at || '';
            valB = b.created_at || '';
            break;
        }
        return compareReportValues(valA, valB, isAsc);
      });
    });

    const filteredWarehouseReport = computed(() => {
      let list = warehouseReceiptReports.value || [];
      const f = reportFilters.value.warehouse_receipt;
      if (f.startDate) {
        list = list.filter(w => (w.warehouse_receipt_at || w.qc_tested_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(w => (w.warehouse_receipt_at || w.qc_tested_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.operator) {
        const op = f.operator.toLowerCase();
        list = list.filter(w => (w.warehouse_receipt_by || '').toLowerCase().includes(op) || (w.warehouse_receipt_by_name || '').toLowerCase().includes(op));
      }
      if (f.docNumber) {
        const d = f.docNumber.toLowerCase();
        list = list.filter(w => (w.qc_doc_number || '').toLowerCase().includes(d) || (w.tray_code || '').toLowerCase().includes(d));
      }
      if (f.status && f.status.trim() !== '' && f.status !== 'Semua Status') {
        const targetStatus = f.status.trim().toUpperCase();
        if (targetStatus === 'MENUNGGU' || targetStatus === 'PENDING') {
          list = list.filter(w => (w.warehouse_receipt_status || '').toUpperCase() === 'PENDING' || !w.warehouse_receipt_status);
        } else if (targetStatus === 'ACCEPTED') {
          list = list.filter(w => (w.warehouse_receipt_status || '').toUpperCase() === 'ACCEPTED' || (w.verified_status || '').toUpperCase() === 'VERIFIED');
        } else if (targetStatus === 'DECLINED') {
          list = list.filter(w => (w.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || (w.verified_status || '').toUpperCase() === 'DECLINED');
        } else {
          list = list.filter(w => (w.warehouse_receipt_status || '').trim().toUpperCase() === targetStatus);
        }
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(w =>
          (w.part15_sn && w.part15_sn.toLowerCase().includes(q)) ||
          (w.part12_sn && w.part12_sn.toLowerCase().includes(q)) ||
          (w.part1_sn && w.part1_sn.toLowerCase().includes(q)) ||
          (w.qc_doc_number && w.qc_doc_number.toLowerCase().includes(q)) ||
          (w.tray_code && w.tray_code.toLowerCase().includes(q))
        );
      }
      const isAsc = f.sortOrder === 'asc';
      return [...list].sort((a, b) => {
        let valA, valB;
        switch (f.sortBy) {
          case 'doc':
            valA = a.qc_doc_number || a.tray_code || '';
            valB = b.qc_doc_number || b.tray_code || '';
            break;
          case 'sn15':
            valA = a.part15_sn || '';
            valB = b.part15_sn || '';
            break;
          case 'sn1':
            valA = a.part1_sn || '';
            valB = b.part1_sn || '';
            break;
          case 'sn12':
            valA = a.part12_sn || '';
            valB = b.part12_sn || '';
            break;
          case 'status':
            valA = a.warehouse_receipt_status || '';
            valB = b.warehouse_receipt_status || '';
            break;
          case 'operator':
            valA = a.warehouse_receipt_by_name || a.warehouse_receipt_by || '';
            valB = b.warehouse_receipt_by_name || b.warehouse_receipt_by || '';
            break;
          case 'date':
          default:
            valA = a.warehouse_receipt_at || a.qc_tested_at || a.finished_at || '';
            valB = b.warehouse_receipt_at || b.qc_tested_at || b.finished_at || '';
            break;
        }
        return compareReportValues(valA, valB, isAsc);
      });
    });

    const currentReportList = computed(() => {
      const sub = reportSubTab.value;
      if (sub === 'wo') return filteredWOReport.value || [];
      if (sub === 'assembly') return filteredAssemblyReport.value || [];
      if (sub === 'assy_test') return filteredAssyTestReport.value || [];
      if (sub === 'qc_test') return filteredQCTestReport.value || [];
      if (sub === 'warehouse_receipt') return filteredWarehouseReport.value || [];
      return [];
    });

    const currentReportCounts = computed(() => {
      const sub = reportSubTab.value;
      if (sub === 'wo') {
        if (reportViewMode.value === 'doc') {
          const docList = currentReportDocSummaryList.value || [];
          const finished = docList.filter(d => d.status === 'SELESAI').length;
          const active = docList.length - finished;
          return { total: docList.length, good: finished, hold: active, other: 0 };
        } else {
          const trays = filteredWOReport.value || [];
          let finished = 0;
          let active = 0;
          trays.forEach(t => {
            const isFinished = (t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.status === 'Good' || t.status === 'SELESAI' || t.session_status === 'COMPLETED_SHIFT' || t.tray_status === 'returned' || t.status === 'returned' || t.status === 'Dikembalikan ke Gudang';
            if (isFinished) finished++;
            else active++;
          });
          return { total: trays.length, good: finished, hold: active, other: 0 };
        }
      } else if (sub === 'assembly') {
        const list = filteredAssemblyReport.value || [];
        let good = 0;
        let hold = 0;
        let other = 0;
        list.forEach(it => {
          const st = String(it.assembly_status || it.status || 'Good').toUpperCase();
          if (st === 'GOOD') good++;
          else if (st === 'HOLD') hold++;
          else other++;
        });
        return { total: list.length, good, hold, other };
      }

      let list = [];
      if (sub === 'assy_test') list = filteredAssyTestReport.value || [];
      else if (sub === 'qc_test') list = filteredQCTestReport.value || [];
      else if (sub === 'warehouse_receipt') list = filteredWarehouseReport.value || [];

      const total = list.length;
      let good = 0;
      let hold = 0;
      let other = 0;

      list.forEach(item => {
        const st = String(item.status || item.warehouse_receipt_status || item.assembly_status || '').toUpperCase();
        if (st === 'GOOD' || st === 'ACCEPTED' || st === 'ACC') good++;
        else if (st === 'HOLD' || st === 'DECLINED' || st === 'HOLD_ASSY' || st === 'HOLD_QC') hold++;
        else other++;
      });

      return { total, good, hold, other };
    });

    const currentReportOperatorOptions = computed(() => {
      const sub = reportSubTab.value;
      const set = new Set();
      if (sub === 'wo') {
        (traysList.value || []).forEach(t => {
          const opWh = (getOperatorFullName(t.pic_warehouse) || t.pic_warehouse || '').trim();
          const opAssign = (getOperatorFullName(t.assigned_operator) || t.assigned_operator || '').trim();
          if (opWh) set.add(opWh);
          if (opAssign) set.add(opAssign);
        });
      } else if (sub === 'assembly') {
        (filteredAssemblyReport.value || []).forEach(it => {
          const opAssy = (getOperatorFullName(it.pic_assembly) || it.pic_assembly || '').trim();
          if (opAssy) set.add(opAssy);
        });
      } else if (sub === 'assy_test') {
        (assyTestReports.value || []).forEach(t => {
          const name = (t.tester_full_name || t.tester_username || '').trim();
          if (name) set.add(name);
        });
      } else if (sub === 'qc_test') {
        (qcTestReports.value || []).forEach(t => {
          const name = (t.tester_full_name || t.tester_username || '').trim();
          if (name) set.add(name);
        });
      } else if (sub === 'warehouse_receipt') {
        (warehouseReceiptReports.value || []).forEach(w => {
          const name = (w.warehouse_receipt_by_name || w.warehouse_receipt_by || '').trim();
          if (name) set.add(name);
        });
      }
      return Array.from(set).filter(Boolean).sort((a, b) => a.localeCompare(b));
    });

    const currentReportDocOptions = computed(() => {
      const sub = reportSubTab.value;
      const set = new Set();
      if (sub === 'wo') {
        (traysList.value || []).forEach(t => {
          if (t.tray_code && t.tray_code !== '-') {
            set.add(t.tray_code.trim());
            set.add(getFormattedDocCode(t.tray_code, 'wo'));
          }
          if (t.source_document_mo && t.source_document_mo !== '-') set.add(t.source_document_mo.trim());
        });
      } else if (sub === 'assembly') {
        (filteredAssemblyReport.value || []).forEach(it => {
          if (it.assy_doc_number && it.assy_doc_number !== '-') {
            set.add(it.assy_doc_number.trim());
          } else if (it.tray_code) {
            set.add(getFormattedDocCode(it.tray_code, 'assy'));
          }
          if (it.tray_code && it.tray_code !== '-') {
            set.add(it.tray_code.trim());
            set.add(getFormattedDocCode(it.tray_code, 'wo'));
          }
        });
      } else if (sub === 'assy_test') {
        (assyTestReports.value || []).forEach(t => {
          if (t.doc_number && t.doc_number !== '-') {
            set.add(t.doc_number.trim());
          } else if (t.tray_code) {
            set.add(getFormattedDocCode(t.tray_code, 'tf1'));
          }
          if (t.assy_doc_number && t.assy_doc_number !== '-') {
            set.add(t.assy_doc_number.trim());
          } else if (t.tray_code) {
            set.add(getFormattedDocCode(t.tray_code, 'assy'));
          }
        });
      } else if (sub === 'qc_test') {
        (qcTestReports.value || []).forEach(t => {
          if (t.doc_number && t.doc_number !== '-') {
            set.add(t.doc_number.trim());
          } else if (t.tray_code) {
            set.add(getFormattedDocCode(t.tray_code, 'qc'));
          }
          if (t.tf1_doc_number && t.tf1_doc_number !== '-') {
            set.add(t.tf1_doc_number.trim());
          } else if (t.assy_doc_number && t.assy_doc_number !== '-') {
            set.add(t.assy_doc_number.trim());
          } else if (t.tray_code) {
            set.add(getFormattedDocCode(t.tray_code, 'tf1'));
          }
        });
      } else if (sub === 'warehouse_receipt') {
        (warehouseReceiptReports.value || []).forEach(w => {
          if (w.qc_doc_number && w.qc_doc_number !== '-') {
            set.add(w.qc_doc_number.trim());
          } else if (w.tray_code) {
            set.add(getFormattedDocCode(w.tray_code, 'qc'));
          }
        });
      }
      return Array.from(set).filter(Boolean).sort((a, b) => a.localeCompare(b));
    });

    const reportPipelineSummary = computed(() => {
      const woList = laporanList.value || [];
      const woTotal = woList.length;
      const woHold = woList.filter(i => String(i.status || '').toUpperCase() === 'HOLD').length;
      const woGood = woTotal - woHold;

      const assyList = assyTestReports.value || [];
      const assyTotal = assyList.length;
      const assyHold = assyList.filter(i => String(i.status || '').toUpperCase() === 'HOLD').length;
      const assyGood = assyList.filter(i => String(i.status || '').toUpperCase() === 'GOOD').length;

      const qcList = qcTestReports.value || [];
      const qcTotal = qcList.length;
      const qcGood = qcList.filter(i => String(i.status || '').toUpperCase() === 'GOOD').length;
      const qcHold = qcList.filter(i => String(i.status || '').toUpperCase() === 'HOLD').length;

      const whList = warehouseReceiptReports.value || [];
      const whTotal = whList.length;
      const whAcc = whList.filter(i => ['ACCEPTED', 'ACC'].includes(String(i.warehouse_receipt_status || '').toUpperCase())).length;

      return {
        woTotal, woHold, woGood,
        assyTotal, assyHold, assyGood,
        qcTotal, qcGood, qcHold,
        whTotal, whAcc
      };
    });

    // ===================================================================
    // 5. DOCUMENT VIEW & MODAL DETAIL INTERACTIONS
    // ===================================================================
    const currentReportDocSummaryList = computed(() => {
      const items = currentReportList.value || [];
      const sub = reportSubTab.value;
      const groupsMap = new Map();

      items.forEach(it => {
        let docCode = '';
        let originDoc = '';
        let operator = '';
        let timestamp = '';
        let status = '';

        if (sub === 'wo') {
          docCode = getFormattedDocCode(it.tray_code, 'wo');
          originDoc = it.source_document_mo || 'MO';
          operator = getOperatorFullName(it.pic_warehouse) || it.pic_warehouse || getOperatorFullName(it.assigned_operator) || it.assigned_operator || '-';
          timestamp = it.tray_created_at || it.created_at;
          const isFinished = (it.finished_count >= it.total_sets && it.total_sets > 0) || it.finished_at || it.session_status === 'COMPLETED_SHIFT';
          status = isFinished ? 'SELESAI' : 'TERSUPPLY';
        } else if (sub === 'assembly') {
          docCode = (it.tray_code && it.tray_code.toUpperCase().startsWith('WH/ASSY'))
            ? it.tray_code
            : ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY'))
              ? it.assy_doc_number
              : getFormattedDocCode(it.tray_code, 'assy'));
          originDoc = getFormattedDocCode(it.tray_code, 'wo');
          operator = getOperatorFullName(it.pic_assembly_name || it.pic_assembly || it.operator || it.assigned_operator);
          timestamp = it.finished_at || it.started_at || it.tray_created_at || it.created_at;
          status = it.assembly_status || it.status || 'Good';
        } else if (sub === 'assy_test') {
          docCode = (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code, 'tf1');
          originDoc = (it.assy_doc_number && it.assy_doc_number !== '-') ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy');
          operator = getOperatorFullName(it.tester_full_name || it.tester_username || it.pic_assembly);
          timestamp = it.created_at || it.tested_at;
          status = it.status || 'OK';
        } else if (sub === 'qc_test') {
          docCode = (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code, 'qc');
          originDoc = (it.tf1_doc_number && it.tf1_doc_number !== '-') ? it.tf1_doc_number : getFormattedDocCode(it.tray_code, 'tf1');
          operator = getOperatorFullName(it.qc_tester_name || it.tester_full_name || it.tester_username || it.pic_qc);
          timestamp = it.created_at || it.qc_tested_at || it.tested_at;
          status = it.status || 'Good';
        } else if (sub === 'warehouse_receipt') {
          docCode = (it.warehouse_doc_number && it.warehouse_doc_number !== '-') ? it.warehouse_doc_number : getFormattedDocCode(it.tray_code, 'wh_gr');
          originDoc = (it.tray_code && it.tray_code.toUpperCase().startsWith('WH/ASSY')) ? it.tray_code : getFormattedDocCode(it.tray_code, 'assy');
          const rawOp = it.warehouse_receipt_by_name || it.warehouse_receipt_by || it.verified_by_user || it.pic_warehouse;
          operator = rawOp ? getOperatorFullName(rawOp) : '-';
          timestamp = it.warehouse_receipt_at || it.effective_warehouse_receipt_at || it.verified_at || it.created_at || it.finished_at || null;
          status = it.warehouse_receipt_status || it.status || 'Verified';
        }

        const key = docCode || it.tray_code || 'UNKNOWN';
        if (!groupsMap.has(key)) {
          groupsMap.set(key, {
            doc_code: key,
            raw_tray_code: it.tray_code,
            origin_doc: originDoc,
            operator: operator,
            timestamp: timestamp,
            status: status,
            total_units: 0,
            good_count: 0,
            hold_count: 0,
            scrap_count: 0,
            rework_count: 0,
            items: []
          });
        }

        const g = groupsMap.get(key);

        const existingIdx = g.items.findIndex(existing =>
          (existing.assembly_id && existing.assembly_id === it.assembly_id) ||
          (existing.id && existing.id === it.id) ||
          (existing.set_number != null && existing.set_number === it.set_number)
        );

        if (existingIdx >= 0) {
          g.items[existingIdx] = { ...g.items[existingIdx], ...it };
        } else {
          g.total_units++;
          g.items.push(it);

          const itemSt = (sub === 'assembly') ? getHistoryAssemblyStatus(it).toUpperCase() : String(status || '').toUpperCase();
          if (['GOOD', 'OK', 'PASSED', 'SELESAI', 'VERIFIED'].includes(itemSt)) {
            g.good_count++;
          } else if (['HOLD', 'PENDING', 'HOLD_ASSY', 'HOLD_QC'].includes(itemSt)) {
            g.hold_count++;
          } else if (itemSt === 'SCRAP') {
            g.scrap_count++;
          } else if (itemSt === 'REWORK' || itemSt === 'RU') {
            g.rework_count++;
          }
        }

        if (operator && operator !== '-' && (!g.operator || g.operator === '-')) {
          g.operator = operator;
        }
        if (timestamp && (!g.timestamp || g.timestamp === '-')) {
          g.timestamp = timestamp;
        }
      });

      groupsMap.forEach(g => {
        g.items.sort((a, b) => (Number(a.set_number) || 0) - (Number(b.set_number) || 0));

        if (sub === 'wo') {
          const matchingTray = (traysList.value || []).find(t =>
            t.tray_code === g.raw_tray_code ||
            t.tray_code === g.doc_code ||
            (g.raw_tray_code && t.tray_code && (t.tray_code.includes(g.raw_tray_code) || g.raw_tray_code.includes(t.tray_code)))
          );

          const isTrayReturnedOrFinished =
            (matchingTray && (
              matchingTray.status === 'returned' ||
              matchingTray.session_status === 'COMPLETED_SHIFT' ||
              matchingTray.return_verified ||
              (matchingTray.finished_count >= matchingTray.total_sets && matchingTray.total_sets > 0)
            )) ||
            g.items.some(it =>
              it.session_status === 'COMPLETED_SHIFT' ||
              it.tray_status === 'returned' ||
              it.return_verified ||
              it.status === 'Dikembalikan ke Gudang' ||
              it.status === 'SELESAI'
            ) ||
            (g.items.length > 0 && g.items.every(it =>
              it.finished_at ||
              ['GOOD', 'SELESAI', 'DIKEMBALIKAN KE GUDANG', 'DECLINED', 'DECLINE', 'TERSEDIA'].includes(String(it.status || '').toUpperCase())
            ));

          g.status = isTrayReturnedOrFinished ? 'SELESAI' : 'TERSUPPLY';
        }

        if (!g.operator || g.operator === '-') {
          const itemWithOp = g.items.find(it => it.warehouse_receipt_by_name || it.warehouse_receipt_by || it.verified_by_user || it.pic_warehouse || it.pic_assembly_name || it.pic_assembly || it.tester_full_name || it.tester_username || it.operator);
          if (itemWithOp) {
            const op = itemWithOp.warehouse_receipt_by_name || itemWithOp.warehouse_receipt_by || itemWithOp.verified_by_user || itemWithOp.pic_warehouse || itemWithOp.pic_assembly_name || itemWithOp.pic_assembly || itemWithOp.tester_full_name || itemWithOp.tester_username || itemWithOp.operator;
            g.operator = getOperatorFullName(op);
          }
        }
        if (!g.timestamp || g.timestamp === '-') {
          const itemWithTime = g.items.find(it => it.warehouse_receipt_at || it.effective_warehouse_receipt_at || it.verified_at || it.qc_tested_at || it.finished_at || it.started_at || it.created_at);
          if (itemWithTime) {
            g.timestamp = itemWithTime.warehouse_receipt_at || itemWithTime.effective_warehouse_receipt_at || itemWithTime.verified_at || itemWithTime.qc_tested_at || itemWithTime.finished_at || itemWithTime.started_at || itemWithTime.created_at;
          }
        }
      });

      return Array.from(groupsMap.values());
    });

    const modalReportDocItems = computed(() => {
      if (!selectedReportDoc.value || !Array.isArray(selectedReportDoc.value.items)) return [];
      let items = selectedReportDoc.value.items;
      const sub = reportSubTab.value;
      if (sub === 'warehouse_receipt') {
        items = items.filter(it => {
          const assyGood = String(it.assembly_status || it.status || 'Good').toUpperCase() === 'GOOD';
          const tf1Good = String(it.assy_test_status || 'Good').toUpperCase() === 'GOOD';
          const qcSt = String(it.qc_status || it.qc_test_status || '').toUpperCase();
          const qcGood = qcSt === 'GOOD' || qcSt === 'GOOD_QC' || qcSt === 'ACC' || qcSt === 'ACCEPTED' || qcSt === 'PASS' || qcSt === 'PASSED';
          return assyGood && tf1Good && qcGood;
        });
      }

      const cleanMap = new Map();
      items.forEach(it => {
        const key = it.set_number != null ? `set_${it.set_number}` : (it.assembly_id ? `aid_${it.assembly_id}` : (it.id ? `id_${it.id}` : `sn_${it.part15_sn || it.sn || Math.random()}`));
        if (!cleanMap.has(key)) {
          cleanMap.set(key, it);
        } else {
          const prev = cleanMap.get(key);
          cleanMap.set(key, { ...it, ...prev });
        }
      });

      const deduplicated = Array.from(cleanMap.values());
      deduplicated.sort((a, b) => (Number(a.set_number) || 0) - (Number(b.set_number) || 0));
      return deduplicated;
    });

    const setReportViewMode = (mode) => {
      reportViewMode.value = mode;
      selectedReportDoc.value = null;
    };

    const openReportDocDetail = async (docGroup) => {
      if (!docGroup) return;
      selectedReportDoc.value = docGroup;

      if (docGroup.raw_tray_code) {
        try {
          const res = await fetchAPI(`/gudang/tray_detail?code=${encodeURIComponent(docGroup.raw_tray_code)}`);
          if (res && res.ok && res.data && res.data.sets && res.data.sets.length > 0) {
            const setsMap = new Map();
            res.data.sets.forEach(s => {
              setsMap.set(Number(s.set_number), s);
            });

            if (docGroup.items && docGroup.items.length > 0) {
              const seenSets = new Set();
              const clean = [];
              docGroup.items.forEach(item => {
                const sNum = Number(item.set_number);
                if (sNum && seenSets.has(sNum)) return;
                if (sNum) seenSets.add(sNum);

                const matched = setsMap.get(sNum);
                const sub = reportSubTab.value;
                if (sub === 'assy_test' || sub === 'qc_test' || sub === 'warehouse_receipt') {
                  clean.push(matched ? { ...matched, ...item, status: item.status || matched.status } : item);
                } else {
                  clean.push(matched ? { ...item, ...matched, status: matched.status || item.status } : item);
                }
              });
              clean.sort((a, b) => (Number(a.set_number) || 0) - (Number(b.set_number) || 0));
              docGroup.items = clean;
              docGroup.total_units = clean.length;
            } else {
              docGroup.items = res.data.sets;
              docGroup.total_units = res.data.sets.length;
            }
          }
        } catch (err) {
          console.warn('Tray detail sync error:', err);
        }
      }
    };

    const openWOUnitDetail = async (it) => {
      if (!it) return;
      const trayCode = it.tray_code || '-';
      const isFinished = (it.finished_count >= it.total_sets && it.total_sets > 0) || it.finished_at || it.session_status === 'COMPLETED_SHIFT' || it.tray_status === 'returned' || it.status === 'Good' || it.status === 'SELESAI' || it.status === 'Dikembalikan ke Gudang';
      const docGroup = {
        doc_code: getFormattedDocCode(trayCode, 'wo'),
        raw_tray_code: trayCode,
        origin_doc: it.source_document_mo || 'MO',
        operator: getOperatorFullName(it.pic_warehouse) || it.pic_warehouse || getOperatorFullName(it.assigned_operator) || it.assigned_operator || '-',
        timestamp: it.tray_created_at || it.created_at,
        status: isFinished ? 'SELESAI' : 'TERSUPPLY',
        session_status: it.session_status,
        tray_status: it.tray_status,
        total_units: 1,
        good_count: 1,
        hold_count: 0,
        scrap_count: 0,
        rework_count: 0,
        items: [it]
      };
      await openReportDocDetail(docGroup);
    };

    const closeReportDocDetail = () => {
      selectedReportDoc.value = null;
    };

    const openItemDetailFromDoc = (item, idx) => {
      if (!item) return;
      if (reportSubTab.value === 'wo') return;
      const context = reportSubTab.value;
      openDetailModal(item, idx, context);
    };

    // ===================================================================
    // 6. PAGINATION COMPUTED
    // ===================================================================
    const pagedWOReport = computed(() => {
      const s = pgState.woReport || { page: 1, size: 20 };
      return (filteredWOReport.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const pagedAssyReport = computed(() => {
      const s = pgState.assyReport || { page: 1, size: 20 };
      return (filteredAssemblyReport.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const pagedAssyTestReport = computed(() => {
      const s = pgState.assyTestReport || { page: 1, size: 20 };
      return (filteredAssyTestReport.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const pagedQCReport = computed(() => {
      const s = pgState.qcReport || { page: 1, size: 20 };
      return (filteredQCTestReport.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const pagedWarehouseReport = computed(() => {
      const s = pgState.warehouseReport || pgState.receiptReport || { page: 1, size: 20 };
      return (filteredWarehouseReport.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgWOReport = computed(() => Math.max(1, Math.ceil((filteredWOReport.value || []).length / (pgState.woReport?.size || 20))));
    const totalPgAssyReport = computed(() => Math.max(1, Math.ceil((filteredAssemblyReport.value || []).length / (pgState.assyReport?.size || 20))));
    const totalPgAssyTestReport = computed(() => Math.max(1, Math.ceil((filteredAssyTestReport.value || []).length / (pgState.assyTestReport?.size || 20))));
    const totalPgQCReport = computed(() => Math.max(1, Math.ceil((filteredQCTestReport.value || []).length / (pgState.qcReport?.size || 20))));
    const totalPgWarehouseReport = computed(() => Math.max(1, Math.ceil((filteredWarehouseReport.value || []).length / ((pgState.warehouseReport?.size) || (pgState.receiptReport?.size) || 20))));

    // ===================================================================
    // 7. ENTERPRISE EXCEL EXPORT ENGINE
    // ===================================================================
    const exportCurrentReportToExcel = async () => {
      const sub = reportSubTab.value;
      let filename = '';
      let title = '';
      let columns = [];
      let rawData = [];

      if (sub === 'wo') {
        const hasDetailedSN = (filteredWOReport.value || []).some(t => t.part15_sn || t.p9_sn || t.part1_sn || t.part12_sn);
        if (reportViewMode.value === 'detailed' || hasDetailedSN) {
          filename = `Laporan_Rinci_WO_Unit_${getTodayDateStr()}.xlsx`;
          title = 'LAPORAN RINCI WORK ORDER & TRACEABILITY UNIT';
          columns = [
            { header: 'No', width: 6 },
            { header: 'No Dokumen WO', width: 22 },
            { header: 'Dokumen Asal (MO)', width: 20 },
            { header: 'SN P9 Part 15', width: 20 },
            { header: 'PN P9 Part 1', width: 16 },
            { header: 'PN P9 Part 12', width: 16 },
            { header: 'Petugas / Operator', width: 22 },
            { header: 'Waktu Transaksi', width: 20 },
            { header: 'Status', width: 14 },
            { header: 'Keterangan', width: 28 }
          ];
          rawData = (filteredWOReport.value || []).map((t, idx) => {
            const isFinished = (t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.session_status === 'COMPLETED_SHIFT' || t.tray_status === 'returned' || t.status === 'Good' || t.status === 'SELESAI' || t.status === 'Dikembalikan ke Gudang';
            let keterangan = t.reason;
            if (!keterangan) {
              if (t.is_declined || t.status === 'Declined' || t.status === 'Decline') {
                keterangan = t.declined_reason ? `Dikembalikan ke Gudang (Declined: ${t.declined_reason})` : 'Dikembalikan ke Gudang (Declined)';
              } else if (t.status === 'Dikembalikan ke Gudang' || (!t.finished_at && (t.session_status === 'COMPLETED_SHIFT' || t.tray_status === 'returned'))) {
                keterangan = 'Dikembalikan ke Gudang';
              } else if (isFinished) {
                keterangan = 'Pengerjaan Selesai';
              } else {
                keterangan = 'Tray Tersupply ke Perakitan';
              }
            }
            return [
              idx + 1,
              getFormattedDocCode(t.tray_code, 'wo') + (t.set_number ? ' Set #' + t.set_number : ''),
              t.source_document_mo || '-',
              t.part15_sn || t.p9_sn || '-',
              t.part1_sn || '-',
              t.part12_sn || '-',
              getOperatorFullName(t.pic_warehouse) || t.pic_warehouse || getOperatorFullName(t.assigned_operator) || t.assigned_operator || '-',
              t.tray_created_at || t.created_at || '-',
              isFinished ? 'SELESAI' : 'TERSUPPLY',
              keterangan
            ];
          });
        } else {
          filename = `Laporan_Dokumen_WO_${getTodayDateStr()}.xlsx`;
          title = 'LAPORAN WORK ORDER (RINGKASAN PER DOKUMEN TRAY)';
          columns = [
            { header: 'No', width: 6 },
            { header: 'No Dokumen WO', width: 22 },
            { header: 'Dokumen Asal (MO)', width: 20 },
            { header: 'Petugas Gudang', width: 22 },
            { header: 'Operator Ditugaskan', width: 22 },
            { header: 'Total Set', width: 12 },
            { header: 'Set Selesai', width: 12 },
            { header: 'Waktu Dibuat', width: 20 },
            { header: 'Status Dokumen', width: 16 }
          ];
          rawData = (currentReportDocSummaryList.value || []).map((t, idx) => [
            idx + 1,
            t.doc_code,
            t.origin_doc || '-',
            t.operator || '-',
            t.operator || '-',
            t.total_units || 0,
            t.good_count || 0,
            t.timestamp || '-',
            t.status || 'TERSUPPLY'
          ]);
        }
      } else if (sub === 'assembly') {
        filename = `Laporan_Perakitan_Assembly_${getTodayDateStr()}.xlsx`;
        title = 'LAPORAN HASIL PERAKITAN (ASSEMBLY)';
        columns = [
          { header: 'No', width: 6 },
          { header: 'No Dokumen Perakitan', width: 24 },
          { header: 'No Dokumen WO', width: 22 },
          { header: 'Set #', width: 8 },
          { header: 'SN P9 Part 15', width: 20 },
          { header: 'PN P9 Part 1', width: 16 },
          { header: 'PN P9 Part 12', width: 16 },
          { header: 'Operator Assembly', width: 22 },
          { header: 'Waktu Selesai', width: 20 },
          { header: 'Status', width: 14 },
          { header: 'Keterangan Masalah', width: 30 }
        ];
        rawData = (filteredAssemblyReport.value || []).map((it, idx) => {
          const isPUAllocated = (typeof isSetAllocatedForReassembly === 'function' && isSetAllocatedForReassembly(it)) || Boolean(it.is_reregistered || it.rereg_wo_suffix || it.rereg_pending_operator);
          const docCode = isPUAllocated ? (getSetPUDocNumber(it) || getFormattedDocCode(it.tray_code, 'assy_rework')) : ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy'));
          return [
            idx + 1,
            docCode,
            getFormattedDocCode(it.tray_code, 'wo'),
            it.set_number || (idx + 1),
            it.part15_sn || it.sn || '-',
            it.part1_sn || '-',
            it.part12_sn || '-',
            getOperatorFullName(it.pic_assembly_name || it.pic_assembly) || it.pic_assembly || '-',
            it.finished_at || it.tray_created_at || '-',
            it.assembly_status || it.status || 'Good',
            it.assembly_reason || it.reason || '-'
          ];
        });
      } else if (sub === 'assy_test') {
        filename = `Laporan_Test_Functional_1_${getTodayDateStr()}.xlsx`;
        title = 'LAPORAN TEST FUNCTIONAL 1 (TF 1 - ASSY/QC)';
        columns = [
          { header: 'No', width: 6 },
          { header: 'No Dokumen TF 1', width: 24 },
          { header: 'No Dokumen Perakitan', width: 22 },
          { header: 'Set #', width: 8 },
          { header: 'SN P9 Part 15', width: 20 },
          { header: 'PN P9 Part 1', width: 16 },
          { header: 'PN P9 Part 12', width: 16 },
          { header: 'Operator Penguji (TF1)', width: 22 },
          { header: 'Waktu Pengujian', width: 20 },
          { header: 'Status Uji', width: 14 },
          { header: 'Detail Kendala / Failure', width: 32 }
        ];
        rawData = (filteredAssyTestReport.value || []).map((t, idx) => [
          idx + 1,
          t.doc_number || getFormattedDocCode(t.tray_code, 'tf1'),
          t.assy_doc_number || getFormattedDocCode(t.tray_code, 'assy'),
          t.set_number || (idx + 1),
          t.part15_sn || t.sn || '-',
          t.part1_sn || '-',
          t.part12_sn || '-',
          getOperatorFullName(t.tester_full_name || t.tester_username) || t.tester_username || '-',
          t.created_at || t.tested_at || '-',
          t.status || 'OK',
          t.failure_reasons || t.notes || '-'
        ]);
      } else if (sub === 'qc_test') {
        filename = `Laporan_Test_Functional_2_QC_${getTodayDateStr()}.xlsx`;
        title = 'LAPORAN TEST FUNCTIONAL 2 (TF 2 - WH/QC)';
        columns = [
          { header: 'No', width: 6 },
          { header: 'No Dokumen TF 2 (QC)', width: 24 },
          { header: 'No Dokumen TF 1', width: 22 },
          { header: 'Set #', width: 8 },
          { header: 'SN P9 Part 15', width: 20 },
          { header: 'PN P9 Part 1', width: 16 },
          { header: 'PN P9 Part 12', width: 16 },
          { header: 'Petugas QC', width: 22 },
          { header: 'Waktu Uji QC', width: 20 },
          { header: 'Status QC', width: 14 },
          { header: 'Detail Kendala / Failure', width: 32 }
        ];
        rawData = (filteredQCTestReport.value || []).map((t, idx) => [
          idx + 1,
          t.doc_number || getFormattedDocCode(t.tray_code, 'qc'),
          t.tf1_doc_number || getFormattedDocCode(t.tray_code, 'tf1'),
          t.set_number || (idx + 1),
          t.part15_sn || t.sn || '-',
          t.part1_sn || '-',
          t.part12_sn || '-',
          getOperatorFullName(t.qc_tester_name || t.tester_full_name || t.tester_username || t.pic_qc) || '-',
          t.created_at || t.qc_tested_at || '-',
          t.status || 'Good',
          t.failure_reasons || t.notes || '-'
        ]);
      } else if (sub === 'warehouse_receipt') {
        filename = `Laporan_Penerimaan_Gudang_GR_${getTodayDateStr()}.xlsx`;
        title = 'LAPORAN PENERIMAAN BARANG GUDANG (WH/GR)';
        columns = [
          { header: 'No', width: 6 },
          { header: 'No Dokumen Penerimaan (GR)', width: 24 },
          { header: 'No Dokumen QC / Tray', width: 22 },
          { header: 'Set #', width: 8 },
          { header: 'SN P9 Part 15', width: 20 },
          { header: 'PN P9 Part 1', width: 16 },
          { header: 'PN P9 Part 12', width: 16 },
          { header: 'Petugas Gudang', width: 22 },
          { header: 'Waktu Verifikasi', width: 20 },
          { header: 'Status Verifikasi Fisik', width: 18 },
          { header: 'Catatan Gudang', width: 28 }
        ];
        rawData = (filteredWarehouseReport.value || []).map((w, idx) => [
          idx + 1,
          w.warehouse_doc_number || getFormattedDocCode(w.tray_code, 'wh_gr'),
          w.qc_doc_number || getFormattedDocCode(w.tray_code, 'qc'),
          w.set_number || (idx + 1),
          w.part15_sn || '-',
          w.part1_sn || '-',
          w.part12_sn || '-',
          getOperatorFullName(w.warehouse_receipt_by_name || w.warehouse_receipt_by || w.verified_by_user) || '-',
          w.warehouse_receipt_at || w.qc_tested_at || w.finished_at || '-',
          w.warehouse_receipt_status || w.status || 'Verified',
          w.notes || '-'
        ]);
      }

      if (rawData.length === 0) {
        triggerToast('Ekspor Dibatalkan', 'Tidak ada data laporan yang sesuai untuk diekspor.', 'warn');
        return;
      }

      if (typeof ExcelJS === 'undefined') {
        const headerRow = columns.map(c => c.header);
        const wsData = [headerRow, ...rawData];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Laporan');
        XLSX.writeFile(wb, filename);
        triggerToast('Ekspor Selesai', `File ${filename} berhasil diunduh.`, 'success');
        return;
      }

      const workbook = new ExcelJS.Workbook();
      workbook.creator = 'Sistem Final Assembly P9';
      workbook.created = new Date();
      const sheet = workbook.addWorksheet('Laporan Traceability', {
        pageSetup: { orientation: 'landscape', paperSize: 9 }
      });

      const colCount = columns.length;

      sheet.mergeCells(1, 1, 1, colCount);
      const titleCell = sheet.getCell(1, 1);
      titleCell.value = title;
      titleCell.font = { name: 'Arial', size: 14, bold: true, color: { argb: 'FF1E293B' } };
      titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
      sheet.getRow(1).height = 28;

      sheet.mergeCells(2, 1, 2, colCount);
      const subTitleCell = sheet.getCell(2, 1);
      subTitleCell.value = `Dicetak otomatis oleh Sistem Final Assembly & Traceability NKRI • Tanggal: ${new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB`;
      subTitleCell.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF64748B' } };
      subTitleCell.alignment = { horizontal: 'center', vertical: 'middle' };
      sheet.getRow(2).height = 18;

      sheet.addRow([]);

      const headerRow = sheet.addRow(columns.map(c => c.header));
      headerRow.height = 24;
      headerRow.eachCell((cell) => {
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

      const colLengths = columns.map(c => c.header.length);

      rawData.forEach((rowVals, rIdx) => {
        const row = sheet.addRow(rowVals);
        row.height = 20;
        const isZebra = rIdx % 2 === 1;

        const rowStr = rowVals.join(' ').toUpperCase();
        const hasFailure = rowStr.includes('HOLD') || rowStr.includes('NOT_GOOD') || rowStr.includes('NOT GOOD') || rowStr.includes('DECLINE') || rowStr.includes('SCRAP') || rowStr.includes('RUSAK');
        const isGood = rowStr.includes('GOOD') || rowStr.includes('OK') || rowStr.includes('PASSED') || rowStr.includes('SELESAI') || rowStr.includes('VERIFIED') || rowStr.includes('ACC');

        row.eachCell((cell, colNum) => {
          const valStr = String(cell.value || '');
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

          if (colNum === 1 || colNum === 4 || colNum === 9 || colNum === 10) {
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
          } else {
            cell.alignment = { horizontal: 'left', vertical: 'middle' };
          }

          if (isZebra) {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
          }

          if (hasFailure) {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEF2F2' } };
            const cellUpper = String(cell.value || '').toUpperCase();
            if (cellUpper.includes('HOLD') || cellUpper.includes('NOT_GOOD') || cellUpper.includes('NOT GOOD') || cellUpper.includes('DECLINE') || cellUpper.includes('SCRAP')) {
              cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FFDC2626' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFEE2E2' } };
            }
          } else if (isGood && (String(cell.value || '').toUpperCase() === 'GOOD' || String(cell.value || '').toUpperCase() === 'VERIFIED' || String(cell.value || '').toUpperCase() === 'ACC')) {
            cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF059669' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD1FAE5' } };
          }
        });
      });

      columns.forEach((col, idx) => {
        const calculated = Math.max(col.width || 12, (colLengths[idx] || 10) + 3);
        sheet.getColumn(idx + 1).width = Math.min(Math.max(calculated, 8), 45);
      });

      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      triggerToast('Ekspor Excel Berhasil', `File ${filename} berhasil diunduh dengan highlight warna dan kolom rapi.`, 'success');
    };

    // ===================================================================
    // MODULE EXPORTS
    // ===================================================================
    return {
      reportSubTab,
      reportFilters,
      reportViewMode,
      selectedReportDoc,
      modalReportDocItems,
      laporanList,
      searchQuery,
      laporanFilter,
      distinctLaporanTrays,
      distinctLaporanOperators,
      assyTestReports,
      qcTestReports,
      warehouseReceiptReports,
      resetLaporanFilter,
      loadLaporan,
      loadAssyTestReports,
      loadQCTestReports,
      loadWarehouseReceiptReports,
      setReportSubTab,
      initReportsTab,
      compareReportValues,
      setReportSort,
      toggleReportSortOrder,
      getReportSortIconClass,
      getReportSortLabel,
      getReportCategoryTitle,
      resetCurrentReportFilter,
      filteredLaporan,
      filteredAssemblyReport,
      filteredWOReport,
      filteredAssyTestReport,
      filteredQCTestReport,
      filteredWarehouseReport,
      currentReportList,
      currentReportCounts,
      currentReportOperatorOptions,
      currentReportDocOptions,
      reportPipelineSummary,
      currentReportDocSummaryList,
      setReportViewMode,
      openReportDocDetail,
      openWOUnitDetail,
      closeReportDocDetail,
      openItemDetailFromDoc,
      pagedWOReport,
      pagedAssyReport,
      pagedAssyTestReport,
      pagedQCReport,
      pagedWarehouseReport,
      totalPgWOReport,
      totalPgAssyReport,
      totalPgAssyTestReport,
      totalPgQCReport,
      totalPgWarehouseReport,
      getHistoryAssemblyStatus,
      formatDocumentStatusText,
      exportCurrentReportToExcel
    };
  };
})(window);

