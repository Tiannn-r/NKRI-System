/**
 * Traveler & Document Printing Module (Fase 2I)
 * Sistem Final Assembly & Traceability P9 - NKRI
 *
 * Encapsulates:
 * 1. Traveler Slip & MO Synchronization (currentTravelerMO, currentTravelerMOSuffix, loadTravelerTray, updateTravelerMO)
 * 2. Multi-Format Traveler Documents (Supply WH/WO, Assembly WH/ASSY, PU WH/ASSY/PU, TF1 ASSY/QC, TF2 WH/QC, WH/GR)
 * 3. Print Filters & Tray Selection (printFilter, distinctPrintOperators, filteredPrintTrays, resetPrintFilter, selectPrintDocType)
 * 4. Qualified Document Item Computations (assyQCQualifiedItems, whQCQualifiedItems, whGRQualifiedItems, travelerTray, travelerItems)
 * 5. Part 15 SN Label Preview, PDF Generator, Direct Thermal Printing & Zebra ZPL (openLabelPreview, downloadP9LabelPDF, printP9LabelDirect, copyZPLCode)
 * 6. Batch Labeling System (openBatchLabelForDoc, openBatchLabelList, printBatchLabelsDirect, downloadBatchLabelsPDF, downloadSingleLabelFromBatch)
 * 7. QR Code Generators for Part 15 SN and Document IDs (renderQRCodeDetail, renderQRCode, renderSNLabelQRCode)
 */
(function (window) {
  'use strict';

  window.createTravelerModule = function (ctx) {
    const { ref, reactive, computed, watch, nextTick } = ctx.Vue || Vue;

    // External dependencies provided by app context
    const fetchAPI = ctx.fetchAPI || window.api?.fetchAPI;
    const triggerToast = ctx.triggerToast || function (title, msg, type) {
      if (window.Swal) Swal.fire({ title, text: msg, icon: type || 'info' });
      else alert((title || '') + ': ' + (msg || ''));
    };
    const showPromptCard = ctx.showPromptCard || function (opts) { alert(opts?.message || ''); };

    const currentTab = ctx.currentTab || ref('traveler');
    const selectedTrayCode = ctx.selectedTrayCode || ref('');
    const currentTray = ctx.currentTray || ref(null);
    const currentTrayAssemblies = ctx.currentTrayAssemblies || ref([]);
    const traysList = ctx.traysList || ref([]);
    const traySlots = ctx.traySlots || ref([]);
    const activeAssyQCDocSlot = ctx.activeAssyQCDocSlot || ref(null);
    const warehouseReceiptReports = ctx.warehouseReceiptReports || ref([]);
    const isCurrentTravelerWHQCVerified = ctx.isCurrentTravelerWHQCVerified || ref(true);
    const isCurrentTravelerQCDone = ctx.isCurrentTravelerQCDone || ref(true);
    const selectedReceiptTrayCode = ctx.selectedReceiptTrayCode || ref('');
    const activeReceiptTraySummary = ctx.activeReceiptTraySummary || ref(null);

    const getOperatorFullName = ctx.getOperatorFullName || function (name) { return name || '-'; };
    const getTodayDateStr = ctx.getTodayDateStr || function () {
      const d = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    };
    const formatDateOnly = ctx.formatDateOnly || function (dt) { return dt ? String(dt).substring(0, 10) : '-'; };
    const formatTimeOnly = ctx.formatTimeOnly || function (dt) { return dt ? String(dt).substring(11, 19) : '-'; };
    const parseWIBDate = ctx.parseWIBDate || function (d) { return new Date(d); };
    const isStatusGood = ctx.isStatusGood || function (st) {
      const s = String(st || '').toLowerCase().trim();
      return s === 'good' || s === 'ok' || s === 'pass' || s === 'passed' || s === 'acc' || s === 'accepted';
    };
    const getTravelerItemActualStatus = ctx.getTravelerItemActualStatus || function (item) {
      if (!item) return '-';
      return item.status || '-';
    };
    const formatDocStatus = (status) => {
      if (!status || status === '-') return '-';
      const s = String(status).trim().toUpperCase();
      if (s === 'NOT_GOOD' || s === 'NOT GOOD' || s === 'NG') return 'NOT GOOD';
      if (s === 'PASS' || s === 'PASSED' || s === 'GOOD' || s === 'ACC' || s === 'ACCEPTED') return 'GOOD';
      if (s === 'HOLD' || s === 'HOLD_ASSY' || s === 'HOLD_QC') return 'HOLD';
      if (s === 'RU' || s === 'DIRAKIT ULANG' || s === 'RAKIT ULANG') return 'DIRAKIT ULANG';
      if (s === 'REWORK' || s === 'REWORK_ASSY' || s === 'REWORK_QC') return 'HOLD';
      if (s === 'SCRAP' || s === 'DECLINED' || s === 'FAIL') return 'SCRAP';
      if (s === 'PENDING' || s.includes('MENUNGGU') || s.includes('BELUM')) return 'PENDING';
      return String(status);
    };
    const getAssyTestItemActualStatus = (item) => {
      if (!item) return '-';
      return item.assy_test_status || item.status || '-';
    };

    const loadTrays = ctx.loadTrays || (async () => {});
    const loadAssemblyTray = ctx.loadAssemblyTray || (async () => {});
    const loadWarehouseReceiptReports = ctx.loadWarehouseReceiptReports || (async () => {});
    const navigateTo = ctx.navigateTo || function () {};
    const switchTab = ctx.switchTab || function (tab) { navigateTo(tab); };

    // ===================================================================
    // 1. DOKUMEN IDENTITAS & KODE HELPER
    // ===================================================================
    const isPUTray = (tray) => {
      if (!tray) return false;
      const code = typeof tray === 'string' ? tray : (tray.tray_code || '');
      return /-PU/i.test(code) || /WH\/ASSY-PU\//i.test(code) || /\/PU\//i.test(code);
    };

    const getPUNumberOnly = (trayCode) => {
      if (!trayCode) return '000001';
      const mFull = String(trayCode).match(/WH\/ASSY-PU\/(\d+)/i);
      if (mFull) return mFull[1].padStart(6, '0');
      const matchPU = String(trayCode).match(/-PU(\d+)/i);
      if (matchPU) {
        const puNum = parseInt(matchPU[1], 10);
        return String(puNum).padStart(6, '0');
      }
      const digits = String(trayCode).replace(/\D/g, '');
      return digits ? digits.slice(-6).padStart(6, '0') : '000001';
    };

    const getPUDocNumber = (trayCode) => {
      if (!trayCode || !isPUTray(trayCode)) return '-';
      return `WH/ASSY/PU/${getPUNumberOnly(trayCode)}`;
    };

    const getPUWODocNumber = (trayCode) => {
      if (!trayCode || !isPUTray(trayCode)) return '-';
      return `WH/WO/PU/${getPUNumberOnly(trayCode)}`;
    };

    const getFormattedDocCode = (trayCode, type) => {
      if (!trayCode) return '-';
      const isPU = isPUTray(trayCode);
      const puNum = isPU ? getPUNumberOnly(trayCode) : '';
      const numPart = trayCode.includes('/') ? trayCode.split('/').pop() : trayCode;
      const sessionMatch = numPart.match(/-S\d+/i);
      const sessionSuffix = sessionMatch ? sessionMatch[0].toUpperCase() : '';
      const cleanNum = numPart.replace(/-PU\d*/i, '').replace(/-S\d*/i, '').replace(/\D/g, '').slice(-6).padStart(6, '0') || '000001';

      const t = String(type || '').toLowerCase();

      if (t === 'mo' || t === 'wh_mo') {
        return isPU ? `WH/MO/PU/${puNum}` : `WH/MO/${cleanNum}`;
      } else if (t === 'supply' || t === 'wo' || t === 'wh_wo') {
        return isPU ? `WH/WO/PU/${puNum}` : `WH/WO/${cleanNum}${sessionSuffix}`;
      } else if (t === 'qc' || t === 'wh_qc') {
        return isPU ? `WH/QC/PU/${puNum}` : `WH/QC/${cleanNum}${sessionSuffix}`;
      } else if (t === 'assy_qc' || t === 'tf1') {
        return isPU ? `ASSY/QC/PU/${puNum}` : `ASSY/QC/${cleanNum}${sessionSuffix}`;
      } else if (t === 'warehouse_receipt' || t === 'receipt' || t === 'gr' || t === 'wh_gr') {
        return isPU ? `WH/GR/PU/${puNum}` : `WH/GR/${cleanNum}${sessionSuffix}`;
      } else if (t === 'assy_rework' || t === 'rework') {
        return `WH/ASSY/PU/${puNum || cleanNum}`;
      } else if (t === 'origin_assy') {
        return `WH/ASSY/${cleanNum}${sessionSuffix}`;
      } else if (t === 'assy') {
        return isPU ? `WH/ASSY/PU/${puNum}` : `WH/ASSY/${cleanNum}${sessionSuffix}`;
      } else if (isPU) {
        return `WH/ASSY/PU/${puNum}`;
      }
      return `WH/ASSY/${cleanNum}${sessionSuffix}`;
    };

    // ===================================================================
    // 2. DOKUMEN CETAK SELEKSI & FILTER STATE
    // ===================================================================
    const activePrintDocType = ref('supply'); // 'supply' | 'assy' | 'assy_rework' | 'assy_qc' | 'wh_qc' | 'wh_gr'
    const showPrintDocTypeDropdown = ref(false);
    const printStatusFilter = ref('ALL');

    const printFilter = ref({
      startDate: '',
      endDate: getTodayDateStr(),
      operator: '',
      search: ''
    });

    const getPrintDocSetCount = (t) => {
      if (!t) return 0;
      const docType = activePrintDocType.value;

      // 1. Dokumen Penerimaan Gudang (WH/GR):
      if (docType === 'wh_gr') {
        const rcptCount = (warehouseReceiptReports.value || []).filter(it => {
          return it.tray_code === t.tray_code || (t.id && (it.tray_id === t.id || it.tray_id === t.tray_id));
        }).length;
        if (rcptCount > 0) return rcptCount;
        const fin = Number(t.finished_count || 0);
        return fin > 0 ? fin : Number(t.total_sets || 15);
      }

      // 2. Dokumen Uji Assy / Test Functional 1 (Assy/QC):
      if (docType === 'assy_qc') {
        if (t.good_assembly_count !== undefined && t.good_assembly_count !== null) return Number(t.good_assembly_count);
        const fin = Number(t.finished_count || 0);
        return fin > 0 ? fin : Number(t.total_sets || 15);
      }

      // 3. Dokumen Perakitan Ulang (WH/ASSY-PU):
      if (docType === 'assy_rework') {
        if (!isPUTray(t)) return 0;
        const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
        const tot = Number(t.total_sets || 0);
        return tot > 0 ? tot : (fin > 0 ? fin : (t.sets ? t.sets.length : 15));
      }

      // 4. Dokumen Perakitan (WH/ASSY):
      if (docType === 'assy') {
        const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
        const tot = Number(t.total_sets || 0);
        return fin > 0 ? fin : (tot > 0 ? tot : 15);
      }

      // 5. Dokumen Supply (WH/WO):
      if (t.session_status === 'COMPLETED_SHIFT' || t.session_status === 'HANDED_OVER' || t.status === 'returned') {
        const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
        if (fin > 0) return fin;
      }
      return Number(t.total_sets || 0);
    };

    const distinctPrintOperators = computed(() => {
      const set = new Set();
      (traysList.value || []).forEach(t => {
        if (t.assigned_operator && t.assigned_operator !== 'ALL' && t.assigned_operator !== 'Semua Operator') {
          set.add(t.assigned_operator);
        }
      });
      return Array.from(set).sort();
    });

    const filteredPrintTrays = computed(() => {
      let list = (traysList.value || []).filter(t => {
        const isPU = isPUTray(t);

        if (activePrintDocType.value === 'assy') {
          if (isPU) return false;
        } else if (activePrintDocType.value === 'assy_rework') {
          if (!isPU) return false;
        } else if (activePrintDocType.value === 'wh_gr') {
          if (isPU) {
            const puHasSpecificReceipt = (warehouseReceiptReports.value || []).some(it => it.tray_code === t.tray_code);
            if (!puHasSpecificReceipt) return false;
          }
        }

        // Exclude dokumen yang memiliki 0 set valid
        if (getPrintDocSetCount(t) <= 0) return false;

        // 1. Date filter
        const dateKey = t.created_at ? t.created_at.substring(0, 10) : '';
        if (printFilter.value.startDate && dateKey && dateKey < printFilter.value.startDate) return false;
        if (printFilter.value.endDate && dateKey && dateKey > printFilter.value.endDate) return false;

        // 2. Operator filter
        if (printFilter.value.operator) {
          const target = printFilter.value.operator.toLowerCase();
          const opFullName = getOperatorFullName(t.assigned_operator).toLowerCase();
          const rawOp = (t.assigned_operator || '').toLowerCase();
          if (!opFullName.includes(target) && !rawOp.includes(target)) return false;
        }

        // 3. Search query
        if (printFilter.value.search && printFilter.value.search.trim()) {
          const q = printFilter.value.search.toLowerCase().trim();
          const match = (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
            (t.source_document_mo && t.source_document_mo.toLowerCase().includes(q)) ||
            (t.pic_warehouse && t.pic_warehouse.toLowerCase().includes(q)) ||
            (t.assigned_operator && t.assigned_operator.toLowerCase().includes(q)) ||
            getOperatorFullName(t.assigned_operator).toLowerCase().includes(q) ||
            getOperatorFullName(t.pic_warehouse).toLowerCase().includes(q);
          if (!match) return false;
        }
        return true;
      });

      if (activePrintDocType.value === 'wh_gr') {
        const hasReceipt = (tray) => {
          return (warehouseReceiptReports.value || []).some(it => {
            return (it.tray_code === tray.tray_code) || (tray.id && it.tray_id === tray.id);
          });
        };
        return list.sort((a, b) => (hasReceipt(b) ? 1 : 0) - (hasReceipt(a) ? 1 : 0) || (b.id || 0) - (a.id || 0));
      }

      return list.sort((a, b) => (a.tray_code || '').localeCompare(b.tray_code || '', undefined, { numeric: true, sensitivity: 'base' }));
    });

    // Auto synchronize selectedTrayCode when filtered list updates
    watch(filteredPrintTrays, (newTrays) => {
      if (currentTab.value !== 'traveler') return;
      if (!newTrays || newTrays.length === 0) {
        selectedTrayCode.value = '';
        return;
      }
      const isStillValid = newTrays.some(t => t.tray_code === selectedTrayCode.value);
      const needsPURefresh = activePrintDocType.value === 'assy_rework' && !isPUTray(selectedTrayCode.value);
      if (!isStillValid || needsPURefresh) {
        selectedTrayCode.value = newTrays[0].tray_code;
        loadTravelerTray();
      }
    });

    const resetPrintFilter = () => {
      printFilter.value = {
        startDate: '',
        endDate: getTodayDateStr(),
        operator: '',
        search: ''
      };
    };

    const selectPrintDocType = (type) => {
      activePrintDocType.value = type;
      if (activeAssyQCDocSlot) activeAssyQCDocSlot.value = null;
      showPrintDocTypeDropdown.value = false;

      if (type === 'wh_gr') {
        printStatusFilter.value = 'ALL';
        loadWarehouseReceiptReports();
      }

      if (type === 'assy_rework') {
        printFilter.value.startDate = '';
        printFilter.value.endDate = '';
        printFilter.value.operator = '';
        printFilter.value.search = '';
      }

      const validTrays = filteredPrintTrays.value || [];
      if (validTrays.length > 0) {
        const isCurrentStillValid = validTrays.some(t => t.tray_code === selectedTrayCode.value);
        if (!isCurrentStillValid || (type === 'assy_rework' && !isPUTray(selectedTrayCode.value))) {
          selectedTrayCode.value = validTrays[0].tray_code;
        }
      } else {
        selectedTrayCode.value = '';
      }
      loadTravelerTray();
    };

    const openTravelerPUDoc = async (puTrayCode) => {
      await loadTrays();
      let targetCode = puTrayCode;
      if (!targetCode) {
        const puTrays = (traysList.value || []).filter(t => isPUTray(t));
        if (puTrays.length > 0) {
          targetCode = puTrays[0].tray_code;
        }
      }
      selectPrintDocType('assy_rework');
      if (targetCode) {
        selectedTrayCode.value = targetCode;
      }
      await loadTravelerTray();
      navigateTo('traveler');
    };

    // ===================================================================
    // 3. COMPUTED TRAY & ITEMS FOR TRAVELER PRINTING
    // ===================================================================
    const travelerTray = computed(() => {
      if (!selectedTrayCode.value) return null;
      const found = (traysList.value || []).find(t => t.tray_code === selectedTrayCode.value);
      if (found) return found;
      if (currentTray.value && currentTray.value.tray_code === selectedTrayCode.value) return currentTray.value;
      return null;
    });

    const travelerItems = computed(() => {
      const slots = traySlots.value || [];
      if (activePrintDocType.value === 'assy_rework') {
        if (!travelerTray.value || !isPUTray(travelerTray.value)) return [];
        return slots;
      }
      return slots;
    });

    const assyQCQualifiedItems = computed(() => {
      if (activeAssyQCDocSlot && activeAssyQCDocSlot.value) return [activeAssyQCDocSlot.value];
      return (travelerItems.value || []).filter(i => {
        if (!i) return false;
        if (i.status === 'Scrap' || i.status === 'Reject') return false;
        if (i.defective_parts && !i.assy_doc_number && !i.assy_test_checklist) return false;

        const hasAssyTest = Boolean(i.assy_doc_number || (i.assy_test_checklist && i.assy_test_checklist.length > 0));
        if (!hasAssyTest) return false;

        if (printStatusFilter.value === 'GOOD') {
          return i.status === 'Good' && i.qc_status !== 'HOLD_ASSY' && !i.assy_failure_reasons;
        }
        return true;
      });
    });

    const whQCQualifiedItems = computed(() => {
      return (travelerItems.value || []).filter(i => {
        if (!i) return false;
        const isGoodFromAssy = i.status === 'Good' && i.qc_status !== 'HOLD_ASSY' && Boolean(i.assy_doc_number);
        if (!isGoodFromAssy) return false;

        const hasQCTest = Boolean(i.qc_doc_number || i.qc_tested_at || (i.qc_status && i.qc_status !== 'PENDING' && i.qc_status !== 'PENDING_QC') || i.warehouse_receipt_status === 'ACCEPTED' || i.warehouse_receipt_status === 'ACC' || i.warehouse_receipt_status === 'DECLINED');
        if (!hasQCTest) return false;

        if (printStatusFilter.value === 'GOOD') {
          return i.qc_status === 'GOOD' || i.warehouse_receipt_status === 'ACCEPTED' || i.warehouse_receipt_status === 'ACC';
        }
        return true;
      });
    });

    const whGRQualifiedItems = computed(() => {
      const selected = (selectedTrayCode.value || '').trim();
      if (!selected) return [];

      const rcptItems = (warehouseReceiptReports.value || []).filter(it => {
        if (!it) return false;
        return it.tray_code === selected || (travelerTray.value && (it.tray_id === travelerTray.value.id || it.tray_id === travelerTray.value.tray_id));
      });

      const slotItems = (travelerItems.value || []).filter(i => {
        if (!i || (!i.id && !i.assembly_id)) return false;
        const qc = String(i.qc_status || '').toUpperCase();
        return qc === 'GOOD' || qc === 'PASS' || qc === 'PASSED' || Boolean(i.warehouse_receipt_status);
      });

      let items = rcptItems.length > 0 ? rcptItems : slotItems;

      return items.filter(i => {
        if (!i || (!i.id && !i.assembly_id)) return false;
        const qc = String(i.qc_status || '').toUpperCase();
        const isGoodTF2 = qc === 'GOOD' || qc === 'PASS' || qc === 'PASSED' || Boolean(i.warehouse_receipt_status);
        if (!isGoodTF2) return false;

        const ws = String(i.warehouse_receipt_status || '').toUpperCase();
        if (printStatusFilter.value === 'GOOD') {
          return ws === 'ACCEPTED' || ws === 'ACC';
        } else if (printStatusFilter.value === 'HOLD' || printStatusFilter.value === 'DECLINED') {
          return ws === 'DECLINED';
        } else if (printStatusFilter.value === 'PENDING') {
          return ws !== 'ACCEPTED' && ws !== 'ACC' && ws !== 'DECLINED';
        }
        return true;
      });
    });

    const travelerAssyOperator = computed(() => {
      if (travelerTray.value && travelerTray.value.assigned_operator && travelerTray.value.assigned_operator !== 'Semua Operator' && travelerTray.value.assigned_operator !== 'ALL') {
        return travelerTray.value.assigned_operator;
      }
      if (travelerItems.value && travelerItems.value.length) {
        const itemWithPic = travelerItems.value.find(it => it.pic_assembly);
        if (itemWithPic) return itemWithPic.pic_assembly;
      }
      return '';
    });

    const isAllSetsReturnedWithoutFinish = computed(() => {
      const tray = travelerTray.value || currentTray.value;
      if (!tray) return false;

      // Status tray harus mengindikasikan pengembalian ke gudang
      const isReturned = Boolean(
        tray.status === 'returned' ||
        tray.status === 'Dikembalikan ke Gudang' ||
        tray.session_status === 'RETURNED_TO_WAREHOUSE' ||
        Boolean(tray.return_verified) ||
        Boolean(tray.return_verified_at) ||
        Boolean(tray.return_reason) ||
        (tray.session_status === 'COMPLETED_SHIFT' && tray.status === 'returned')
      );

      if (!isReturned) return false;

      // Cek jumlah set yang telah selesai dirakit
      const assemblies = currentTrayAssemblies.value || [];
      const finishedInAssemblies = assemblies.filter(a => a && (a.finished_at || (a.status && !['TERSUPPLY', 'Dikembalikan ke Gudang', 'RETURNED'].includes(a.status)))).length;

      const finishedCount = Number(tray.finished_count !== undefined && tray.finished_count !== null ? tray.finished_count : finishedInAssemblies);

      // Jika belum ada satu pun set yang selesai dirakit (finishedCount === 0)
      return finishedCount === 0;
    });

    const getInspectorQC = (items) => {
      if (!items) return '';
      if (Array.isArray(items)) {
        const found = items.find(i => i && (i.pic_qc || i.qc_tester_name || i.qc_tested_by));
        return found ? getInspectorQC(found) : '';
      }
      const val = items.pic_qc || items.qc_tester_name || getOperatorFullName(items.qc_tested_by) || items.qc_tested_by || '';
      return val === '-' ? '' : val;
    };

    // ===================================================================
    // 4. TRAVELER SLIP MO STATE & UPDATE
    // ===================================================================
    const currentTravelerMO = ref('');
    const currentTravelerMOSuffix = ref('');
    let isLoadingTravelerTray = false;

    const loadTravelerTray = async () => {
      if (!selectedTrayCode.value || isLoadingTravelerTray) return;
      isLoadingTravelerTray = true;
      try {
        await loadAssemblyTray();
        if (currentTray.value) {
          const rawMO = currentTray.value.source_document_mo || '';
          currentTravelerMO.value = rawMO;
          if (rawMO.startsWith('WH/MO/')) {
            currentTravelerMOSuffix.value = rawMO.substring(6);
          } else if (rawMO.includes('/')) {
            currentTravelerMOSuffix.value = rawMO.split('/').pop().replace(/\D/g, '').slice(0, 6);
          } else {
            currentTravelerMOSuffix.value = rawMO.replace(/\D/g, '').slice(0, 6);
          }
        }
      } finally {
        isLoadingTravelerTray = false;
      }
    };

    const updateTravelerMO = async () => {
      if (!currentTray.value) return;
      const suffix = (currentTravelerMOSuffix.value || '').trim();
      const fullMO = suffix ? `WH/MO/${suffix.padStart(6, '0')}` : '';
      try {
        const json = await fetchAPI('/tray/update_mo', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: currentTray.value.id,
            source_document_mo: fullMO
          })
        });
        if (json.ok) {
          currentTray.value.source_document_mo = fullMO;
          currentTravelerMO.value = fullMO;
          currentTravelerMOSuffix.value = suffix ? suffix.padStart(6, '0') : '';
          triggerToast('Tersimpan', `Nomor Source Document MO berhasil diperbarui: ${fullMO || '-'}`, 'success');
          loadTrays();
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const selectAndPrintTraveler = async (trayCode, docType = 'supply') => {
      selectedTrayCode.value = trayCode;
      activePrintDocType.value = docType;
      switchTab('traveler');
      await loadTravelerTray();
    };

    const triggerPrint = () => {
      if (activePrintDocType.value === 'wh_qc' && whQCQualifiedItems.value.length === 0) {
        triggerToast('Dokumen Belum Siap', 'Laporan Mutu (WH/QC) belum dapat dicetak karena pengujian QC pada tray ini belum selesai.', 'warn');
        return;
      }
      if (activePrintDocType.value === 'wh_gr' && !isCurrentTravelerWHQCVerified.value) {
        triggerToast('Dokumen Belum Siap', 'Laporan Penerimaan Gudang (WH/GR) belum dapat dicetak karena verifikasi fisik gudang belum selesai.', 'warn');
        return;
      }
      if (activePrintDocType.value === 'assy_qc' && assyQCQualifiedItems.value.length === 0) {
        triggerToast('Dokumen Belum Siap', 'Laporan Uji Perakitan belum dapat dicetak karena pengujian fungsional assembly belum dilakukan.', 'warn');
        return;
      }

      const origTitle = document.title;
      const targetTrayCode = travelerTray.value ? travelerTray.value.tray_code : (selectedTrayCode.value || '000001');
      const isSupply = activePrintDocType.value === 'supply';

      let rawCode = '';
      if (isSupply) {
        rawCode = (travelerTray.value && travelerTray.value.source_document_mo) ? travelerTray.value.source_document_mo : getFormattedDocCode(targetTrayCode, 'supply');
      } else if (activePrintDocType.value === 'wh_qc') {
        rawCode = (whQCQualifiedItems.value.find(i => i.qc_doc_number)?.qc_doc_number) || getFormattedDocCode(targetTrayCode, 'wh_qc');
      } else if (activePrintDocType.value === 'wh_gr') {
        rawCode = (whGRQualifiedItems.value.find(i => i.warehouse_doc_number)?.warehouse_doc_number) || getFormattedDocCode(targetTrayCode, 'wh_gr');
      } else if (activePrintDocType.value === 'assy_qc') {
        rawCode = (activeAssyQCDocSlot && activeAssyQCDocSlot.value && activeAssyQCDocSlot.value.assy_doc_number) || (assyQCQualifiedItems.value.find(i => i.assy_doc_number)?.assy_doc_number) || getFormattedDocCode(targetTrayCode, 'assy');
      } else {
        rawCode = getFormattedDocCode(targetTrayCode, 'assy');
      }

      const cleanFileName = rawCode.replace(/[\/\\]/g, '_');
      document.title = cleanFileName;

      window.print();
      setTimeout(() => {
        document.title = origTitle || 'Sistem Traceability Final Assembly P9';
      }, 2000);
    };

    // ===================================================================
    // 5. QR CODE RENDERING ENGINE
    // ===================================================================
    const renderQRCodeDetail = (text) => {
      const container = document.getElementById('detail-qrcode-container');
      if (!container) return;
      container.innerHTML = '';
      if (window.QRCode) {
        new window.QRCode(container, {
          text: text,
          width: 72,
          height: 72,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: window.QRCode.CorrectLevel.M
        });
      } else {
        const url = 'https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=' + encodeURIComponent(text);
        container.innerHTML = `<img src="${url}" alt="QR" style="width:72px;height:72px;object-fit:contain;" />`;
      }
    };

    const renderQRCode = (text) => {
      const container = document.getElementById('qrcode-container');
      if (!container) return;
      container.innerHTML = '';
      if (window.QRCode) {
        new window.QRCode(container, {
          text: text,
          width: 52,
          height: 52,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: window.QRCode.CorrectLevel.M
        });
      } else {
        const url = 'https://api.qrserver.com/v1/create-qr-code/?size=52x52&data=' + encodeURIComponent(text);
        container.innerHTML = `<img src="${url}" alt="QR" style="width:52px;height:52px;object-fit:contain;" />`;
      }
    };

    const renderSNLabelQRCode = (elementId, snText) => {
      if (!snText) return;
      const container = document.getElementById(elementId);
      if (!container) return;
      container.innerHTML = '';
      if (window.QRCode) {
        new window.QRCode(container, {
          text: snText,
          width: 52,
          height: 52,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: window.QRCode.CorrectLevel.M
        });
      } else {
        const url = 'https://api.qrserver.com/v1/create-qr-code/?size=52x52&data=' + encodeURIComponent(snText);
        container.innerHTML = `<img src="${url}" alt="QR" style="width:52px;height:52px;object-fit:contain;" />`;
      }
    };

    // ===================================================================
    // 6. SINGLE PART 15 SN LABEL PRINTING & ZPL
    // ===================================================================
    const showLabelPreviewModal = ref(false);
    const activeLabelSN = ref({});

    const openLabelPreview = (slot) => {
      if (!slot) return;
      const wrStatus = slot.warehouse_receipt_status || '';
      if (wrStatus && wrStatus !== 'ACCEPTED' && wrStatus !== 'ACC') {
        triggerToast('Peringatan', 'Label hanya dapat dicetak setelah unit diverifikasi fisik (ACC) di Penerimaan Gudang!', 'warn');
        return;
      }
      const status = slot.qc_status || slot.status || slot.assembly_status || '';
      const isGood = wrStatus === 'ACCEPTED' || wrStatus === 'ACC' || isStatusGood(status) || status.toUpperCase() === 'APPROVED' || status.toUpperCase() === 'APPROVED_QC';
      if (!isGood) {
        triggerToast('Peringatan', `Label hanya dapat dicetak setelah diverifikasi fisik (ACC) di Penerimaan Gudang!`, 'warn');
        return;
      }
      activeLabelSN.value = {
        ...slot,
        part15_sn: slot.part15_sn || slot.sn || '',
        product_type: (currentTray.value && currentTray.value.product_type) || slot.product_type || 'Nanggala P9'
      };
      showLabelPreviewModal.value = true;
      nextTick(() => {
        renderQRCode(activeLabelSN.value.part15_sn || 'J1100001');
      });
    };

    const downloadP9LabelPDF = () => {
      const sn = (activeLabelSN.value && activeLabelSN.value.part15_sn) ? activeLabelSN.value.part15_sn : 'Product_P9';
      const safeSN = sn.replace(/[\/\\:*?"<>|]/g, '_');

      const labelEl = document.getElementById('label-printable-area');
      if (!labelEl) {
        triggerToast('Perhatian', 'Preview label tidak ditemukan. Buka modal preview terlebih dahulu.', 'warn');
        return;
      }

      if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        triggerToast('Perhatian', 'Library PDF belum siap. Periksa koneksi internet dan refresh halaman.', 'warn');
        return;
      }

      if (window.getSelection) {
        window.getSelection().removeAllRanges();
      }
      if (document.activeElement && document.activeElement.blur) {
        document.activeElement.blur();
      }

      const { jsPDF } = window.jspdf;

      html2canvas(labelEl, {
        scale: 4,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff',
        width: labelEl.offsetWidth || 320,
        height: labelEl.offsetHeight || 80,
        scrollX: 0,
        scrollY: 0,
        x: 0,
        y: 0,
        logging: false
      }).then(canvas => {
        const imgData = canvas.toDataURL('image/png', 1.0);

        const doc = new jsPDF({
          orientation: 'landscape',
          unit: 'mm',
          format: [80, 20],
          compress: true
        });
        doc.addImage(imgData, 'PNG', 0, 0, 80, 20, undefined, 'FAST');
        doc.save(`${safeSN}.pdf`);
        triggerToast('Sukses Unduh', `File PDF label ${safeSN}.pdf berhasil diunduh.`, 'success');
      }).catch(err => {
        console.error('PDF error:', err);
        triggerToast('Gagal', 'Gagal membuat PDF: ' + err.message, 'error');
      });
    };

    const printP9LabelDirect = () => {
      const sn = (activeLabelSN.value && activeLabelSN.value.part15_sn) ? activeLabelSN.value.part15_sn : 'Product_P9';

      let styleEl = document.getElementById('zebra-print-override');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'zebra-print-override';
        document.head.appendChild(styleEl);
      }
      styleEl.innerHTML = `
        @page {
          size: 80mm 20mm landscape;
          margin: 0;
        }
        @media print {
          @page {
            size: 80mm 20mm landscape;
            margin: 0;
          }
          html, body {
            width: 80mm !important;
            height: 20mm !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
          }
        }
      `;

      document.body.classList.add('printing-label-only');
      const originalTitle = document.title;
      document.title = `${sn}`;
      window.print();
      setTimeout(() => {
        document.body.classList.remove('printing-label-only');
        document.title = originalTitle;
        if (styleEl) styleEl.innerHTML = '';
      }, 500);
    };

    const copyZPLCode = () => {
      const sn = (activeLabelSN.value && activeLabelSN.value.part15_sn) ? activeLabelSN.value.part15_sn : 'Product_P9';
      const pType = (activeLabelSN.value && activeLabelSN.value.product_type) || 'Nanggala P9';
      const zpl = `^XA
^PW640
^LL160
^LH0,0
^FO32,10^A0N,16,16^FDNKRI^FS
^FO32,28^A0N,28,28^FD${pType}^FS
^FO32,60^GB200,2,2^FS
^FO32,68^A0N,14,14^FDSerial Number:^FS
^FO32,88^A0N,34,34^FD${sn}^FS
^FO220,105^A0N,16,16^FDMade In Indonesia 2026^FS
^FO460,16^BQN,2,4^FDMA,${sn}^FS
^XZ`;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(zpl).then(() => {
          triggerToast('ZPL Disalin', `Kode ZPL untuk ${sn} berhasil disalin ke clipboard!`, 'success');
        }).catch(() => {
          showPromptCard({ title: 'Salin Kode ZPL', message: 'Silakan salin kode ZPL di bawah:', defaultValue: zpl, confirmText: 'Selesai' });
        });
      } else {
        showPromptCard({ title: 'Salin Kode ZPL', message: 'Silakan salin kode ZPL di bawah:', defaultValue: zpl, confirmText: 'Selesai' });
      }
    };

    const printP9Label = downloadP9LabelPDF;

    // ===================================================================
    // 7. BATCH LABELING ENGINE (MASSAL ACC UNITS)
    // ===================================================================
    const showBatchLabelModal = ref(false);
    const batchLabelItems = ref([]);
    const batchLabelDocInfo = ref({ docNumber: '', trayCode: '', totalACC: 0, productType: 'Nanggala P9' });
    const isGeneratingBatchPDF = ref(false);

    const openBatchLabelForDoc = (docOrTray) => {
      if (!docOrTray) return;
      const trayCode = docOrTray.tray_code || (typeof docOrTray === 'string' ? docOrTray : '');
      const docNumber = docOrTray.qc_doc_number || trayCode || 'WH/QC';

      let allItems = [];
      if (docOrTray.items && docOrTray.items.length) {
        allItems = docOrTray.items;
      } else {
        const list = warehouseReceiptReports.value || [];
        allItems = list.filter(it => it.tray_code === trayCode || it.qc_doc_number === docNumber);
      }

      const accItems = allItems.filter(it => {
        const wrStatus = String(it.warehouse_receipt_status || '').trim().toUpperCase();
        return wrStatus === 'ACCEPTED' || wrStatus === 'ACC';
      });

      if (accItems.length === 0) {
        triggerToast('Perhatian', 'Dokumen ini belum memiliki serial number yang berstatus ACC untuk dicetak.', 'warn');
        return;
      }

      const defaultProduct = accItems[0]?.product_type || (currentTray.value && currentTray.value.product_type) || 'Nanggala P9';
      batchLabelDocInfo.value = {
        docNumber: docNumber,
        trayCode: trayCode,
        totalACC: accItems.length,
        productType: defaultProduct
      };

      batchLabelItems.value = accItems.map(it => ({
        ...it,
        sn: it.part15_sn || it.p9_sn || it.sn || '-',
        product_type: it.product_type || defaultProduct
      }));

      showBatchLabelModal.value = true;

      nextTick(() => {
        batchLabelItems.value.forEach((it, idx) => {
          renderSNLabelQRCode('batch-label-preview-qr-' + idx, it.sn);
          renderSNLabelQRCode('batch-label-print-qr-' + idx, it.sn);
        });
      });
    };

    const openBatchLabelList = (items) => {
      if (!items || items.length === 0) {
        triggerToast('Info', 'Tidak ada unit dalam dokumen ini.', 'info');
        return;
      }
      const accItems = items.filter(it => {
        const wrStatus = String(it.warehouse_receipt_status || '').trim().toUpperCase();
        return wrStatus === 'ACCEPTED' || wrStatus === 'ACC';
      });
      if (accItems.length === 0) {
        triggerToast('Perhatian', 'Tidak ada unit berstatus ACC dalam dokumen ini.', 'warn');
        return;
      }
      const docNum = activeReceiptTraySummary.value?.qc_doc_number || selectedReceiptTrayCode.value || 'Dokumen';
      const defaultProduct = accItems[0]?.product_type || (currentTray.value && currentTray.value.product_type) || 'Nanggala P9';
      batchLabelDocInfo.value = {
        docNumber: docNum,
        trayCode: selectedReceiptTrayCode.value || '-',
        totalACC: accItems.length,
        productType: defaultProduct
      };
      batchLabelItems.value = accItems.map(it => ({
        ...it,
        sn: it.part15_sn || it.p9_sn || it.sn || '-',
        product_type: it.product_type || defaultProduct
      }));
      showBatchLabelModal.value = true;
      nextTick(() => {
        batchLabelItems.value.forEach((it, idx) => {
          renderSNLabelQRCode('batch-label-preview-qr-' + idx, it.sn);
          renderSNLabelQRCode('batch-label-print-qr-' + idx, it.sn);
        });
      });
    };

    const printBatchLabelsDirect = () => {
      if (!batchLabelItems.value || batchLabelItems.value.length === 0) return;
      let styleEl = document.getElementById('batch-label-dynamic-page-rule');
      if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'batch-label-dynamic-page-rule';
        document.head.appendChild(styleEl);
      }
      styleEl.innerHTML = `
        @page {
          size: 80mm 20mm landscape;
          margin: 0 !important;
        }
        @media print {
          @page {
            size: 80mm 20mm landscape;
            margin: 0 !important;
          }
          html, body {
            width: 80mm !important;
            margin: 0 !important;
            padding: 0 !important;
          }
          .batch-label-page {
            page-break-after: always !important;
            break-after: page !important;
          }
        }
      `;
      document.body.classList.add('printing-batch-labels');
      const origTitle = document.title;
      const docNum = (batchLabelDocInfo.value?.docNumber || 'Batch_Labels').replace(/[\/\\]/g, '_');
      document.title = `Labels_${docNum}`;

      let cleanedUp = false;
      const cleanup = () => {
        if (cleanedUp) return;
        cleanedUp = true;
        document.body.classList.remove('printing-batch-labels');
        document.title = origTitle;
        if (styleEl) styleEl.innerHTML = '';
        window.onafterprint = null;
      };
      window.onafterprint = cleanup;
      window.print();
      setTimeout(cleanup, 2500);
    };

    const downloadBatchLabelsPDF = async () => {
      if (!batchLabelItems.value || batchLabelItems.value.length === 0) return;
      if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        triggerToast('Perhatian', 'Library PDF belum siap. Silakan refresh halaman.', 'warn');
        return;
      }
      const { jsPDF } = window.jspdf;
      isGeneratingBatchPDF.value = true;
      triggerToast('Memproses PDF', `Sedang menyusun PDF label untuk ${batchLabelItems.value.length} unit ACC...`, 'info');

      try {
        const doc = new jsPDF({
          orientation: 'landscape',
          unit: 'mm',
          format: [80, 20],
          compress: true
        });

        for (let i = 0; i < batchLabelItems.value.length; i++) {
          const el = document.getElementById('batch-label-card-' + i);
          if (!el) continue;

          const canvas = await html2canvas(el, {
            scale: 4,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            width: el.offsetWidth || 320,
            height: el.offsetHeight || 80,
            scrollX: 0,
            scrollY: 0,
            x: 0,
            y: 0,
            logging: false
          });

          const imgData = canvas.toDataURL('image/png', 1.0);
          if (i > 0) {
            doc.addPage([80, 20], 'landscape');
          }
          doc.addImage(imgData, 'PNG', 0, 0, 80, 20, undefined, 'FAST');
        }

        const safeDoc = (batchLabelDocInfo.value?.docNumber || 'Labels_ACC').replace(/[\/\\:*?"<>|]/g, '_');
        doc.save(`${safeDoc}_Labels_ACC_${batchLabelItems.value.length}Unit.pdf`);
        triggerToast('Sukses Unduh', `File PDF berisi ${batchLabelItems.value.length} label ACC berhasil diunduh.`, 'success');
      } catch (err) {
        console.error('Batch PDF generation error:', err);
        triggerToast('Gagal', 'Gagal membuat PDF: ' + (err.message || err), 'error');
      } finally {
        isGeneratingBatchPDF.value = false;
      }
    };

    const downloadSingleLabelFromBatch = async (item, idx) => {
      const el = document.getElementById('batch-label-card-' + idx);
      if (!el) return;
      if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
        triggerToast('Perhatian', 'Library PDF belum siap.', 'warn');
        return;
      }
      const { jsPDF } = window.jspdf;
      try {
        const canvas = await html2canvas(el, {
          scale: 4,
          useCORS: true,
          allowTaint: true,
          backgroundColor: '#ffffff',
          width: el.offsetWidth || 320,
          height: el.offsetHeight || 80,
          scrollX: 0,
          scrollY: 0,
          x: 0,
          y: 0,
          logging: false
        });
        const imgData = canvas.toDataURL('image/png', 1.0);
        const doc = new jsPDF({
          orientation: 'landscape',
          unit: 'mm',
          format: [80, 20],
          compress: true
        });
        doc.addImage(imgData, 'PNG', 0, 0, 80, 20, undefined, 'FAST');
        const safeSN = (item.sn || 'SN').replace(/[\/\\:*?"<>|]/g, '_');
        doc.save(`${safeSN}.pdf`);
        triggerToast('Sukses Unduh', `File PDF label ${safeSN}.pdf berhasil diunduh.`, 'success');
      } catch (err) {
        triggerToast('Gagal', 'Gagal membuat PDF label: ' + err.message, 'error');
      }
    };

    // Return all public state and methods
    return {
      isPUTray,
      getPUNumberOnly,
      getPUDocNumber,
      getPUWODocNumber,
      getFormattedDocCode,
      activePrintDocType,
      showPrintDocTypeDropdown,
      printStatusFilter,
      printFilter,
      getPrintDocSetCount,
      distinctPrintOperators,
      filteredPrintTrays,
      resetPrintFilter,
      selectPrintDocType,
      openTravelerPUDoc,
      getTravelerItemActualStatus,
      formatDocStatus,
      getAssyTestItemActualStatus,
      travelerTray,
      travelerItems,
      assyQCQualifiedItems,
      whQCQualifiedItems,
      whGRQualifiedItems,
      travelerAssyOperator,
      isAllSetsReturnedWithoutFinish,
      getInspectorQC,
      currentTravelerMO,
      currentTravelerMOSuffix,
      loadTravelerTray,
      updateTravelerMO,
      selectAndPrintTraveler,
      triggerPrint,
      renderQRCodeDetail,
      renderQRCode,
      renderSNLabelQRCode,
      showLabelPreviewModal,
      activeLabelSN,
      openLabelPreview,
      downloadP9LabelPDF,
      printP9LabelDirect,
      copyZPLCode,
      printP9Label,
      showBatchLabelModal,
      batchLabelItems,
      batchLabelDocInfo,
      isGeneratingBatchPDF,
      openBatchLabelForDoc,
      openBatchLabelList,
      printBatchLabelsDirect,
      downloadBatchLabelsPDF,
      downloadSingleLabelFromBatch
    };
  };
})(window);

