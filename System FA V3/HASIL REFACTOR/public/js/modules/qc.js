/**
 * Quality Control (QC / Test Functional 2) Module
 * Sistem Final Assembly & Traceability
 * 
 * Modularized in Fase 2C - Refactor Arsitektur
 */

(function (root, factory) {
  if (typeof window !== 'undefined') {
    window.createQCModule = factory();
  }
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.createQCModule = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  return function createQCModule(deps) {
    const {
      Vue = window.Vue || {},
      fetchAPI = window.api?.fetchAPI || window.fetchAPI,
      triggerToast = (title, message, type) => window.utils?.triggerToast ? window.utils.triggerToast(title, message, type) : console.log(`[${type}] ${title}: ${message}`),
      showConfirmCard = () => {},
      pgState = {},
      userRole = { value: 'admin' },
      operatorName = { value: 'Administrator' },
      currentTab = { value: 'qc' },
      defaultAssyTestItems = [],
      getOperatorFullName = (name) => name || '-',
      selectedTrayCode = { value: null },
      activePrintDocType = { value: 'wh_qc' },
      navigateTo = () => {},
      loadTravelerTray = async () => {},
      loadProductionHoldItems = async () => {},
      loadPendingVerifications = async () => {},
      loadTrays = async () => {},
      reportSubTab = { value: '' },
      loadQCTestReports = () => {},
      openReregisterSetConfirmModal = () => {},
      openReregisterModal = () => {},
      openDetailModal = () => {}
    } = deps || {};

    const { ref, computed, watch } = Vue;

        // 2. QC Functional Testing & Card Management
        const qcPendingList = ref([]);
        const qcHistoryList = ref([]);
        const qcAllList = ref([]);
        const qcStatusFilter = ref('ALL'); // 'ALL', 'PENDING', 'GOOD', 'HOLD'
        const qcSearchQuery = ref('');
        const qcTrayFilter = ref('');
        const showQCTestModal = ref(false);
        const isQCAssyTableExpanded = ref(false);
        const isSubmittingQCTest = ref(false);
        const qcTestForm = ref({
          assembly_id: null,
          tray_id: null,
          tray_code: '',
          set_number: null,
          part15_sn: '',
          part12_sn: '',
          part1_sn: '',
          assy_doc_number: '',
          qc_doc_number: '',
          pic_assembly: '',
          finished_at: '',
          assy_tester_username: '',
          assy_tested_at: '',
          assy_test_checklist: [],
          qc_status: '',
          qc_failure_reasons: '',
          pic_qc: '',
          qc_tested_at: '',
          notes: '',
          is_view_mode: false
        });

        const isQCHoldItem = (item) => {
          if (!item) return false;
          const st = String(item.qc_status || '').trim().toUpperCase();
          const assySt = String(item.assembly_status || item.status || '').trim();
          if (['HOLD_QC', 'HOLD', 'REWORK_QC', 'REWORK', 'RU'].includes(st)) return true;
          if (item.status === 'Dirakit Ulang' || item.status === 'Rework' || item.status === 'Hold') return true;
          if (item.qc_failure_reasons || item.rework_reason) return true;
          if ((item.qc_tested_at || item.qc_doc_number) && !['GOOD', 'GOOD_QC', 'PASS', 'ACC', 'ACCEPTED'].includes(st)) {
            return true;
          }
          return false;
        };

        const isQCPendingItem = (item) => {
          if (!item) return false;
          // Menunggu HANYA ketika unit belum dilakukan test di QC dan tidak memiliki status hold/rework/good
          if (item.qc_doc_number || item.qc_tested_at) return false;
          if (isQCHoldItem(item)) return false;
          const st = String(item.qc_status || '').trim().toUpperCase();
          if (['GOOD', 'GOOD_QC', 'PASS', 'ACC', 'ACCEPTED'].includes(st)) return false;
          return (st === 'PENDING_QC' || st === 'PENDING' || !st) && item.assembly_status === 'Good';
        };

        const isEligibleForQC = (item) => {
          if (!item) return false;
          // 1. Sudah diuji di QC (Memiliki nomor dokumen QC atau hasil uji QC yang sah / Hold QC)
          if (item.qc_doc_number || item.qc_tested_at || item.qc_status === 'GOOD' || isQCHoldItem(item)) {
            return true;
          }
          // 2. Menunggu QC: Wajib berstatus Good di perakitan & Good di TF1 (lolos Uji Fungsional 1) dan belum diuji QC
          return isQCPendingItem(item) &&
            item.assy_test_status === 'Good' &&
            Boolean(item.assy_doc_number);
        };

        const getQCCardClass = (item) => {
          if (!item) return 'border-slate-200 bg-white';
          const qs = String(item.qc_status || '').toUpperCase();
          if (qs === 'GOOD') return 'border-emerald-300 ring-1 ring-emerald-100 bg-emerald-50/20';
          if (qs === 'HOLD_QC' || qs === 'HOLD') return 'border-amber-300 ring-1 ring-amber-100 bg-amber-50/20';
          if (qs === 'REWORK_QC' || qs === 'REWORK') return 'border-purple-300 ring-1 ring-purple-100 bg-purple-50/20';
          if (qs === 'SCRAP' || qs === 'NOT_GOOD') return 'border-red-300 ring-1 ring-red-100 bg-red-50/20';
          return 'border-indigo-200 ring-1 ring-indigo-100 bg-white';
        };

        const loadQCPendingItems = async () => {
          try {
            const res = await fetchAPI('/qc/pending-items');
            if (res.ok) {
              const filterQC = (arr) => (arr || []).filter(isEligibleForQC);
              qcPendingList.value = filterQC(res.data);
              qcHistoryList.value = filterQC(res.history_data);
              qcAllList.value = filterQC(res.all_data || ((res.data || []).concat(res.history_data || [])));
            }
          } catch (_) { }
        };

        const selectedQCTrayCode = ref(null);

        const openQCTrayDetail = (trayCode) => {
          selectedQCTrayCode.value = trayCode;
          try {
            history.pushState({ type: 'qc_tray', code: trayCode }, '', '#qc/' + trayCode);
          } catch (_) { }
        };

        const getParsedAssyChecklist = (item) => {
          if (!item) return defaultAssyTestItems;
          let cl = item.assy_test_checklist || item.test_checklist;
          if (typeof cl === 'string') {
            try { cl = JSON.parse(cl); } catch (_) { cl = null; }
          }
          if (Array.isArray(cl) && cl.length) {
            return defaultAssyTestItems.map((defItem, idx) => {
              const match = cl.find(c => c && (Number(c.test_num) === Number(defItem.test_num) || Number(c.id) === Number(defItem.test_num))) || cl[idx];
              let mappedStatus = 'PENDING';
              if (match && match.status) {
                const rawSt = String(match.status).trim().toUpperCase();
                if (rawSt === 'GOOD' || rawSt === 'PASS' || rawSt === 'OK') mappedStatus = 'GOOD';
                else if (rawSt === 'NOT_GOOD' || rawSt === 'NOT GOOD' || rawSt === 'FAIL' || rawSt === 'HOLD' || rawSt === 'REWORK' || rawSt === 'NG') mappedStatus = 'NOT_GOOD';
                else mappedStatus = 'PENDING';
              }
              return {
                test_num: defItem.test_num,
                name: defItem.name,
                description: defItem.description,
                status: mappedStatus
              };
            });
          }
          return defaultAssyTestItems;
        };

        const qcTraySummaryList = computed(() => {
          const map = {};
          (qcAllList.value || []).forEach(item => {
            const code = item.tray_code || 'No Tray';
            if (!map[code]) {
              map[code] = {
                tray_code: code,
                pic_assembly: item.pic_assembly || item.assigned_operator || '',
                finished_at: item.finished_at || item.tray_created_at || item.started_at,
                items: [],
                total_sets: 0,
                good_qc_count: 0,
                pending_qc_count: 0,
                hold_qc_count: 0
              };
            }
            map[code].items.push(item);
            map[code].total_sets++;
            if (item.qc_status === 'GOOD' || item.qc_status === 'GOOD_QC') {
              map[code].good_qc_count++;
            } else if (isQCHoldItem(item)) {
              map[code].hold_qc_count++;
            } else if (isQCPendingItem(item)) {
              map[code].pending_qc_count++;
            }
          });

          let list = Object.values(map);
          if (qcSearchQuery.value && qcSearchQuery.value.trim()) {
            const q = qcSearchQuery.value.toLowerCase().trim();
            list = list.filter(t =>
              t.tray_code.toLowerCase().includes(q) ||
              (t.pic_assembly && t.pic_assembly.toLowerCase().includes(q)) ||
              getOperatorFullName(t.pic_assembly).toLowerCase().includes(q)
            );
          }
          list.sort((a, b) => (a.tray_code || '').localeCompare(b.tray_code || '', undefined, { numeric: true, sensitivity: 'base' }));
          return list;
        });

        const distinctQCTrays = computed(() => {
          const s = new Set();
          (qcAllList.value || []).forEach(item => {
            if (item.tray_code) s.add(item.tray_code);
          });
          return Array.from(s).sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' }));
        });

        const qcOverallPendingCount = computed(() => {
          const all = qcAllList.value || [];
          return all.filter(i => isQCPendingItem(i)).length;
        });

        const qcOverallSummary = computed(() => {
          const all = qcAllList.value || [];
          const docSet = new Set(all.map(i => i.tray_code).filter(Boolean));
          const docCount = docSet.size;
          const pcsCount = all.length;
          const goodCount = all.filter(i => i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC').length;
          const holdCount = all.filter(i => isQCHoldItem(i)).length;
          const pendingCount = all.filter(i => isQCPendingItem(i)).length;
          return {
            docCount,
            pcsCount,
            goodCount,
            holdCount,
            pendingCount
          };
        });

        const qcCounts = computed(() => {
          let all = qcAllList.value || [];
          if (selectedQCTrayCode.value) {
            all = all.filter(item => item.tray_code === selectedQCTrayCode.value);
          }
          const pending = all.filter(i => isQCPendingItem(i)).length;
          const good = all.filter(i => i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC').length;
          const hold = all.filter(i => isQCHoldItem(i)).length;
          return {
            all: all.length,
            pending,
            good,
            hold
          };
        });

        const filteredQCList = computed(() => {
          let list = qcAllList.value || [];
          if (selectedQCTrayCode.value) {
            list = list.filter(item => item.tray_code === selectedQCTrayCode.value);
          }
          if (qcStatusFilter.value === 'PENDING') {
            list = list.filter(i => isQCPendingItem(i));
          } else if (qcStatusFilter.value === 'GOOD') {
            list = list.filter(i => i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC');
          } else if (qcStatusFilter.value === 'HOLD') {
            list = list.filter(i => isQCHoldItem(i));
          }

          if (qcTrayFilter.value) {
            list = list.filter(item => item.tray_code === qcTrayFilter.value);
          }
          if (qcSearchQuery.value && qcSearchQuery.value.trim()) {
            const q = qcSearchQuery.value.toLowerCase().trim();
            list = list.filter(item =>
              (item.part15_sn && item.part15_sn.toLowerCase().includes(q)) ||
              (item.part12_sn && item.part12_sn.toLowerCase().includes(q)) ||
              (item.part1_sn && item.part1_sn.toLowerCase().includes(q)) ||
              (item.tray_code && item.tray_code.toLowerCase().includes(q)) ||
              (item.assy_doc_number && item.assy_doc_number.toLowerCase().includes(q)) ||
              (item.qc_doc_number && item.qc_doc_number.toLowerCase().includes(q)) ||
              (item.pic_assembly && item.pic_assembly.toLowerCase().includes(q)) ||
              (item.assy_tester_username && item.assy_tester_username.toLowerCase().includes(q))
            );
          }
          return list;
        });

        const qcPendingCount = computed(() => qcOverallPendingCount.value);

        const parseChecklistHelper = (raw, isHoldOrRework) => {
          let cl = raw;
          if (typeof cl === 'string') {
            try { cl = JSON.parse(cl); } catch (_) { cl = []; }
          }
          if (!isHoldOrRework && (!Array.isArray(cl) || cl.length === 0)) {
            return defaultAssyTestItems.map(defItem => ({
              test_num: defItem.test_num,
              name: defItem.name,
              description: defItem.description,
              status: 'GOOD'
            }));
          }
          if (!Array.isArray(cl) || cl.length === 0) {
            return JSON.parse(JSON.stringify(defaultAssyTestItems));
          }
          return defaultAssyTestItems.map((defItem, idx) => {
            const match = cl.find(c => c && (Number(c.test_num) === defItem.test_num || Number(c.id) === defItem.test_num)) || cl[idx];
            let mappedStatus = 'PENDING';
            if (match && match.status) {
              const rawSt = String(match.status).trim().toUpperCase();
              if (rawSt === 'GOOD' || rawSt === 'PASS' || rawSt === 'OK') mappedStatus = 'GOOD';
              else if (rawSt === 'NOT_GOOD' || rawSt === 'NOT GOOD' || rawSt === 'FAIL' || rawSt === 'HOLD' || rawSt === 'REWORK' || rawSt === 'NG') mappedStatus = 'NOT_GOOD';
              else mappedStatus = 'PENDING';
            }
            return {
              test_num: defItem.test_num,
              name: defItem.name,
              description: defItem.description,
              status: mappedStatus
            };
          });
        };

        const openQCTestModal = (item, isViewMode = false) => {
          const isAssemblyHoldOrRework = (item.assembly_status === 'Hold' || item.status === 'Hold' || item.assembly_status === 'Rework' || item.status === 'Rework')
            && item.verified_status !== 'APPROVED' && item.verified_status !== 'APPROVED_QC' && item.qc_status !== 'GOOD' && !item.qc_doc_number;

          const isQCHoldOrRework = item.hold_source === 'QC' || item.stage === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'REWORK_QC' || item.qc_status === 'HOLD' || Boolean(item.qc_failure_reasons) || Boolean(item.qc_test_checklist);

          const assyChecklist = parseChecklistHelper(item.assy_test_checklist || (item.stage === 'ASSY' ? item.test_checklist : null), isAssemblyHoldOrRework);
          const qcChecklist = parseChecklistHelper(item.qc_test_checklist || (item.stage === 'QC' ? item.test_checklist : null), isQCHoldOrRework);

          const defaultActiveTestTab = isQCHoldOrRework ? 'qc' : 'assy';
          const isPending = (item.qc_status === 'PENDING_QC' || item.qc_status === 'PENDING') && item.assembly_status === 'Good';

          qcTestForm.value = {
            assembly_id: item.assembly_id || item.id,
            tray_id: item.tray_id,
            tray_code: item.tray_code || '',
            set_number: item.set_number,
            part15_sn: item.part15_sn || '',
            part12_sn: item.part12_sn || '',
            part1_sn: item.part1_sn || '',
            assy_doc_number: item.assy_doc_number || '',
            qc_doc_number: item.qc_doc_number || '',
            pic_assembly: item.pic_assembly || '',
            finished_at: item.finished_at || item.started_at || '',
            assy_tester_username: item.assy_tester_username || item.pic_assembly || '',
            assy_tester_fullname: item.assy_tester_fullname || item.assy_tester_username || item.pic_assembly || '',
            assy_tested_at: item.assy_tested_at || item.finished_at || '',
            assy_failure_reasons: item.assy_failure_reasons || '',
            assy_test_checklist: assyChecklist,
            qc_tester_username: item.qc_tester_username || item.pic_qc || '',
            qc_tester_fullname: item.qc_tester_fullname || item.qc_tester_username || item.pic_qc || '',
            qc_status: item.qc_status || (isPending ? 'PENDING_QC' : 'GOOD'),
            qc_failure_reasons: item.qc_failure_reasons || '',
            rework_reason: item.rework_reason || '',
            pic_qc: item.pic_qc || item.qc_tested_by || '',
            qc_tested_at: item.qc_test_tested_at || item.qc_tested_at || '',
            notes: item.qc_notes || '',
            is_view_mode: isViewMode || !isPending || userRole.value === 'gudang' || userRole.value === 'assembly'
          };
          if (currentTab.value !== 'qc') {
            currentTab.value = 'qc';
          }
          if (item.tray_code) {
            selectedQCTrayCode.value = item.tray_code;
          }
          isQCAssyTableExpanded.value = true;
          showQCTestModal.value = true;
        };

        const handleHoldItemDetail = (item) => {
          if (!item) return;
          // Kalo hold di QC baru munculin halaman QC
          const isQCHold = item.hold_source === 'QC' || item.stage === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'REWORK_QC';
          if (isQCHold) {
            openQCTestModal(item, true);
          } else {
            // Munculkan card modal (persis foto 2) tanpa pindah ke halaman QC
            openDetailModal(item, (item.set_number ? item.set_number - 1 : 0), 'assy_test');
          }
        };

        const submitQCTestDecision = async (decision) => {
          if (userRole.value === 'assembly' || userRole.value === 'gudang') {
            triggerToast('Akses Ditolak', 'Hanya Inspector QC atau Admin yang berhak melakukan pengujian QC.', 'error');
            return;
          }
          if (isSubmittingQCTest.value) return;
          isSubmittingQCTest.value = true;
          try {
            const payload = {
              assembly_id: qcTestForm.value.assembly_id,
              status: decision,
              notes: qcTestForm.value.notes,
              failure_reasons: qcTestForm.value.notes || (decision === 'Hold' ? 'Tertahan di Quality Control (QC)' : null),
              test_checklist: qcTestForm.value.assy_test_checklist
            };
            const res = await fetchAPI('/qc/submit-test', {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (res.ok) {
              triggerToast('Keputusan QC Berhasil Disimpan', res.message || `Status QC telah diset menjadi ${decision}.`, decision === 'Good' ? 'success' : 'warn');
              showQCTestModal.value = false;
              await loadQCPendingItems();
              await loadProductionHoldItems();
              await loadPendingVerifications();
              await loadTrays();
              await loadTravelerTray();
              if (reportSubTab.value === 'qc_test') loadQCTestReports();
            } else {
              triggerToast('Gagal Simpan QC', res.error || 'Terjadi kesalahan sistem', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSubmittingQCTest.value = false;
          }
        };

        const printWHQCDoc = async (trayCode, docNumber) => {
          if (trayCode) selectedTrayCode.value = trayCode;
          activePrintDocType.value = 'wh_qc';
          await loadTravelerTray();
          navigateTo('traveler');
          triggerToast('Dokumen Siap Cetak', `Membuka Dokumen Mutu WH/QC No. ${docNumber || '-'}`, 'info');
        };

        const reregisterDeclinedItem = (item) => {
          if (!item) return;
          if (item.set_number || item.assembly_id || item.tray_code || item.part15_sn || item.stage) {
            openReregisterSetConfirmModal(item);
          } else {
            const sn = item.part15_sn || item.sn;
            const part_type = item.part_type || '15';
            openReregisterModal({ part_type, sn, status: item.qc_status || item.status || 'Hold' });
          }
        };

        const filteredQCPendingList = filteredQCList;
        const submitQCTest = submitQCTestDecision;

        // Pop-up Alasan Hold QC
        const showQCHoldReasonModal = ref(false);
        const qcHoldReasonInput = ref('');

        const openQCHoldReasonModal = () => {
          qcHoldReasonInput.value = '';
          showQCHoldReasonModal.value = true;
        };

        const confirmQCHold = async () => {
          const reason = (qcHoldReasonInput.value || '').trim();
          if (!reason) {
            triggerToast('Alasan Wajib Diisi', 'Mohon masukkan alasan penahanan mutu QC!', 'warn');
            return;
          }
          qcTestForm.value.notes = reason;
          showQCHoldReasonModal.value = false;
          await submitQCTestDecision('Hold');
        };



    // Pagination Computed Properties for QC Module
    const pagedQCList = computed(() => {
      const s = pgState.qcList || { page: 1, size: 20 };
      return (filteredQCList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const pagedQcTraySummaryList = computed(() => {
      const s = pgState.qcTraySummary || { page: 1, size: 20 };
      return (qcTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgQCList = computed(() => {
      const s = pgState.qcList || { page: 1, size: 20 };
      return Math.max(1, Math.ceil((filteredQCList.value || []).length / s.size));
    });

    const totalPgQcTraySummary = computed(() => {
      const s = pgState.qcTraySummary || { page: 1, size: 20 };
      return Math.max(1, Math.ceil((qcTraySummaryList.value || []).length / s.size));
    });

    // ===================================================================
    // EXCEL EXPORT ENGINE FOR TF 2 (QUALITY CONTROL - QC)
    // ===================================================================
    const exportQCToExcel = async () => {
      try {
        let rawItems = [];
        const isTrayScoped = Boolean(selectedQCTrayCode.value);

        if (isTrayScoped) {
          rawItems = (filteredQCList.value || []).filter(i => i.tray_code === selectedQCTrayCode.value);
          if (rawItems.length === 0) {
            rawItems = (qcAllList.value || []).filter(i => i.tray_code === selectedQCTrayCode.value);
          }
        } else {
          rawItems = (filteredQCList.value && filteredQCList.value.length > 0)
            ? filteredQCList.value
            : (qcAllList.value || []);
        }

        if (rawItems.length === 0) {
          triggerToast('Data Kosong', 'Tidak ada data pengujian QC untuk diekspor ke Excel.', 'warn');
          return;
        }

        const todayStr = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateOnly === 'function')
          ? window.utils.formatDateOnly(new Date()).replace(/\//g, '-')
          : new Date().toISOString().substring(0, 10);

        const filename = `Laporan_TF2_Quality_Control_${isTrayScoped ? (selectedQCTrayCode.value.replace(/\//g, '_') + '_') : ''}${todayStr}.xlsx`;
        const title = isTrayScoped
          ? `LAPORAN TEST FUNCTIONAL 2 (QC) - TRAY ${selectedQCTrayCode.value}`
          : 'LAPORAN TEST FUNCTIONAL 2 (TF 2 - QUALITY CONTROL)';

        const columns = [
          { header: 'No', width: 6, align: 'center' },
          { header: 'No Dokumen QC', width: 22, align: 'center' },
          { header: 'No Dokumen TF 1', width: 22, align: 'center' },
          { header: 'No Dokumen Tray', width: 20, align: 'center' },
          { header: 'Set #', width: 8, align: 'center' },
          { header: 'SN Part 15 (P9)', width: 20, align: 'center' },
          { header: 'PN Part 1', width: 16, align: 'center' },
          { header: 'PN Part 12', width: 16, align: 'center' },
          { header: 'Operator Assembly', width: 22, align: 'left' },
          { header: 'Inspector QC', width: 22, align: 'left' },
          { header: 'Waktu Uji QC', width: 20, align: 'center' },
          { header: 'Status Aktual', width: 20, align: 'center' },
          { header: 'Catatan / Detail Kendala', width: 32, align: 'left' }
        ];

        let countGood = 0;
        let countHold = 0;
        let countPending = 0;
        let countOther = 0;

        const rows = rawItems.map((it, idx) => {
          // Status aktual QC
          let statusAktual = 'MENUNGGU QC';
          const qs = String(it.qc_status || '').toUpperCase();
          if (qs === 'GOOD' || qs === 'GOOD_QC' || qs === 'PASS' || qs === 'ACC' || qs === 'ACCEPTED') {
            statusAktual = 'LOLOS QC (GOOD)';
            countGood++;
          } else if (typeof isQCHoldItem === 'function' && isQCHoldItem(it)) {
            statusAktual = 'HOLD QC';
            countHold++;
          } else if (qs === 'REWORK_QC' || qs === 'REWORK') {
            statusAktual = 'REWORK QC';
            countHold++;
          } else if (qs === 'SCRAP' || qs === 'NOT_GOOD') {
            statusAktual = 'NOT GOOD (QC)';
            countOther++;
          } else if (typeof isQCPendingItem === 'function' && isQCPendingItem(it)) {
            statusAktual = 'MENUNGGU QC';
            countPending++;
          } else if (it.status === 'Dirakit Ulang') {
            statusAktual = 'DIRAKIT ULANG (RU)';
            countOther++;
          } else {
            statusAktual = qs || 'MENUNGGU QC';
            countPending++;
          }

          // Format Dokumen QC & TF1
          let docQC = it.qc_doc_number || it.doc_number;
          if (!docQC && it.tray_code) {
            const suffix = it.tray_code.split('/').pop();
            docQC = it.tray_code.startsWith('WH/QC/') ? it.tray_code : `WH/QC/${suffix}`;
          }
          let docTF1 = it.tf1_doc_number || it.assy_doc_number;
          if (!docTF1 && it.tray_code) {
            const suffix = it.tray_code.split('/').pop();
            docTF1 = it.tray_code.startsWith('ASSY/QC/') ? it.tray_code : `ASSY/QC/${suffix}`;
          }

          const qcTime = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateTime === 'function')
            ? window.utils.formatDateTime(it.qc_test_tested_at || it.qc_tested_at || it.finished_at || it.created_at)
            : (it.qc_test_tested_at || it.qc_tested_at || it.finished_at || it.created_at || '-');

          const notes = it.qc_failure_reasons || it.failure_reasons || it.rework_reason || it.qc_notes || it.notes || '-';

          return [
            idx + 1,
            docQC || '-',
            docTF1 || '-',
            it.tray_code || '-',
            it.set_number ? ('#' + it.set_number) : ('#' + (idx + 1)),
            it.part15_sn || it.sn || '-',
            it.part1_sn || '-',
            it.part12_sn || '-',
            getOperatorFullName(it.pic_assembly || it.assigned_operator) || '-',
            getOperatorFullName(it.qc_tester_fullname || it.qc_tester_username || it.pic_qc || it.qc_tested_by) || '-',
            qcTime,
            statusAktual,
            notes
          ];
        });

        const summaryText = `Total Unit: ${rawItems.length}   |   Lolos QC (Good): ${countGood}   |   Hold QC: ${countHold}   |   Menunggu QC: ${countPending}${countOther > 0 ? ('   |   Lainnya: ' + countOther) : ''}`;

        if (window.utils && typeof window.utils.exportToExcel === 'function') {
          await window.utils.exportToExcel({
            filename,
            sheetName: 'QC Testing',
            title,
            subtitle: `Dicetak dari Menu Quality Control (TF 2) • Filter: ${qcStatusFilter.value || 'SEMUA'} • Tanggal: ${new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB`,
            summary: summaryText,
            columns,
            rows,
            triggerToast
          });
        }
      } catch (err) {
        console.error('exportQCToExcel error:', err);
        triggerToast('Gagal Ekspor', err.message || 'Terjadi kesalahan saat mengekspor Excel.', 'error');
      }
    };

    // Lifecycle / Tab Initializer
    const initQCTab = async () => {
      if (typeof window.logger !== 'undefined') {
        window.logger.info('QC', 'Memuat data Test Functional 2 (QC)...');
      }
      await loadQCPendingItems();
    };

    return {
      initQCTab,
      qcPendingList,
      qcHistoryList,
      qcAllList,
      qcStatusFilter,
      qcSearchQuery,
      qcTrayFilter,
      showQCTestModal,
      isQCAssyTableExpanded,
      isSubmittingQCTest,
      selectedQCTrayCode,
      qcTestForm,
      showQCHoldReasonModal,
      qcHoldReasonInput,
      isQCHoldItem,
      isQCPendingItem,
      isEligibleForQC,
      getQCCardClass,
      loadQCPendingItems,
      openQCTrayDetail,
      getParsedAssyChecklist,
      qcTraySummaryList,
      distinctQCTrays,
      qcOverallPendingCount,
      qcOverallSummary,
      qcCounts,
      filteredQCList,
      pagedQCList,
      pagedQcTraySummaryList,
      totalPgQCList,
      totalPgQcTraySummary,
      qcPendingCount,
      openQCTestModal,
      handleHoldItemDetail,
      submitQCTestDecision,
      printWHQCDoc,
      reregisterDeclinedItem,
      filteredQCPendingList,
      submitQCTest,
      openQCHoldReasonModal,
      confirmQCHold,
      exportQCToExcel
    };
  };
});
