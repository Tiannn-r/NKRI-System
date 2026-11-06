/**
 * Hold & Reassembly Module (Fase 2F)
 * Sistem Final Assembly & Traceability P9 - NKRI
 *
 * Encapsulates:
 * 1. Production Results & Hold (Hasil Perakitan / production_hold)
 * 2. Functional Hold (Hasil Test Fungsional / functional_hold)
 * 3. Perakitan Ulang (Reassembly Queue & Dispatch / reassembly)
 * 4. Part Replacement & Set Re-registration Modals/Workflows
 */
(function (window) {
  'use strict';

  window.createHoldsModule = function (ctx) {
    const { ref, reactive, computed, watch } = ctx.Vue || Vue;

    // External dependencies provided by app context
    const fetchAPI = ctx.fetchAPI || window.api?.fetchAPI;
    const triggerToast = ctx.triggerToast || function (title, msg, type) {
      if (window.Swal) Swal.fire({ title, text: msg, icon: type || 'info' });
      else alert((title || '') + ': ' + (msg || ''));
    };
    const showConfirmCard = ctx.showConfirmCard || function (opts) {
      return Promise.resolve(confirm(opts?.message || 'Apakah Anda yakin?'));
    };
    const showPromptCard = ctx.showPromptCard || function (opts) {
      return Promise.resolve(prompt(opts?.message || 'Masukkan keterangan:'));
    };

    const userRole = ctx.userRole || ref('');
    const userName = ctx.userName || ref('');
    const userFullName = ctx.userFullName || ref('');
    const operatorName = ctx.operatorName || ref('Operator');
    const currentTab = ctx.currentTab || ref('production_hold');
    const activePrintDocType = ctx.activePrintDocType || ref('assy');

    const pgState = ctx.pgState || reactive({
      productionHold: { page: 1, size: 15 },
      assemblyResults: { page: 1, size: 10 }
    });

    const traysList = ctx.traysList || ref([]);
    const currentTray = ctx.currentTray || ref(null);
    const operatorUsersList = ctx.operatorUsersList || ref([]);

    const getPUDocNumber = ctx.getPUDocNumber || function (tc) { return tc; };
    const formatTimeOnly = ctx.formatTimeOnly || function (val) { return val || '-'; };
    const openReregisterModal = ctx.openReregisterModal || function () {};
    const openReregisterSetConfirmModal = ctx.openReregisterSetConfirmModal || function () {};
    const loadTrays = ctx.loadTrays || (async () => {});
    const loadPendingVerifications = ctx.loadPendingVerifications || (async () => {});
    const loadQCPendingItems = ctx.loadQCPendingItems || (async () => {});
    const loadPendingAssemblies = ctx.loadPendingAssemblies || (async () => {});

    // ===================================================================
    // 1. REASSEMBLY CHECK HELPER (AUTHORITATIVE)
    // ===================================================================
    const isReassemblySet = (item) => {
      if (!item) return false;
      // Jika item telah di-Decline (ditolak gudang/admin), BUKAN set perakitan ulang:
      if (Boolean(item.is_declined) || String(item.status || '').toLowerCase() === 'declined' || String(item.verified_status || '').toUpperCase() === 'DECLINED') {
        return false;
      }
      const vStat = String(item.verified_status || '').trim().toUpperCase();
      // Jika statusnya WAITING_REASSEMBLY, APPROVED_REREG, atau COMPLETED_PU (hasil verifikasi admin), PASTI SUDAH RU:
      if (['WAITING_REASSEMBLY', 'APPROVED_REREG', 'COMPLETED_PU'].includes(vStat)) return true;

      // Set yang masih menunggu verifikasi admin untuk daftar ulang belum masuk RU
      if (vStat === 'PENDING_ADMIN_ASSY_REREG' || vStat === 'PENDING_ADMIN' || vStat === 'PENDING_ADMIN_QC' || item.pending_action_type === 'PENDING_DAFTAR_ULANG') {
        return false;
      }
      const st = String(item.assembly_status || item.status || '').trim().toLowerCase();
      const assyTestSt = String(item.assy_test_status || '').trim().toUpperCase();
      const qcSt = String(item.qc_status || '').trim().toUpperCase();
      if (st === 'dirakit ulang' || st === 'ru' || assyTestSt === 'RU' || qcSt === 'RU') return true;
      if (Boolean(item.is_reregistered && st !== 'good' && !vStat.startsWith('PENDING_ADMIN'))) return true;
      if (Boolean(item.rereg_pending_operator) || Boolean(item.rereg_wo_suffix)) return true;
      if (item.latest_replacement_status === 'APPROVED') return true;
      if (item.rework_reason && item.rework_reason.includes('Ganti Part') && (st === 'hold' || st === 'dirakit ulang' || vStat === 'WAITING_REASSEMBLY' || vStat === 'APPROVED' || vStat === 'COMPLETED_PU')) return true;
      const cleanR = String(item.assembly_reason || item.reason || '').toLowerCase();
      if (cleanR.includes('dirakit ulang')) return true;
      return false;
    };

    const isTravelerItemAssembled = (item) => {
      if (!item) return false;
      if (!item.finished_at && !item.id && !item.assembly_id) return false;
      const st = String(item.status || item.assembly_status || '').trim();
      if (!st || st.toUpperCase() === 'RETURNED') return false;
      if (activePrintDocType.value === 'assy_rework' && !item.finished_at) return false;
      return Boolean(item.finished_at || (item.id && st));
    };

    const getTravelerItemActualStatus = (item) => {
      if (!item) return '-';
      if (!isTravelerItemAssembled(item)) {
        return '-';
      }
      if (item.original_assembly_status) {
        const oSt = String(item.original_assembly_status).trim();
        if (oSt && oSt.toLowerCase() !== 'none' && oSt.toLowerCase() !== 'null') {
          return oSt.charAt(0).toUpperCase() + oSt.slice(1).toLowerCase();
        }
      }
      const cleanReason = String(item.reason || item.assembly_reason || '').trim();
      const hasDefectiveParts = Boolean(item.defective_parts && String(item.defective_parts).trim() !== '' && String(item.defective_parts).trim() !== '-');
      const isRealReason = Boolean(cleanReason && cleanReason !== '-' && !cleanReason.startsWith('|') && !cleanReason.toLowerCase().includes('dirakit ulang'));
      if (isRealReason || hasDefectiveParts) {
        if (cleanReason.toLowerCase().includes('scrap')) return 'Scrap';
        if (cleanReason.toLowerCase().includes('rework')) return 'Rework';
        return 'Hold';
      }
      const st = String(item.status || item.assembly_status || '').trim();
      if (st && !['dirakit ulang', 'ru'].includes(st.toLowerCase())) {
        return st;
      }
      return 'Good';
    };

    const getAssemblyStageStatus = (it) => {
      if (!it) return 'Good';

      const rawSt = String(it.assembly_status || it.status || '').trim().toLowerCase();
      const vStat = String(it.verified_status || '').trim().toUpperCase();

      // 0. Status Ditolak / Declined:
      if (Boolean(it.is_declined) || rawSt === 'declined' || vStat === 'DECLINED') {
        return 'Declined';
      }

      // 1. Prioritas Status Transisi Rakit Ulang (RU):
      if (typeof isReassemblySet === 'function' && isReassemblySet(it)) {
        return 'RU';
      }
      const qcSt = String(it.qc_status || '').trim().toUpperCase();
      const assyTestSt = String(it.assy_test_status || '').trim().toUpperCase();

      if (rawSt === 'dirakit ulang' || rawSt === 'ru' || assyTestSt === 'RU' || qcSt === 'RU' || ['WAITING_REASSEMBLY', 'APPROVED_REREG', 'COMPLETED_PU'].includes(vStat)) {
        return 'RU';
      }

      // 2. Ambil catatan status perakitan aktual awal (original_assembly_status dari database / history)
      if (it.original_assembly_status) {
        const oSt = String(it.original_assembly_status).trim().toLowerCase();
        if (oSt === 'good') return 'Good';
        if (oSt === 'hold') return 'Hold';
        if (oSt === 'scrap') return 'Scrap';
        if (oSt === 'rework') return 'Rework';
      }

      // 3. Cek apakah saat perakitan ada kendala/cacat/alasan asli
      const cleanReason = String(it.assembly_reason || it.reason || '').trim();
      const isRealAssyReason = Boolean(cleanReason && cleanReason !== '-' && cleanReason !== 'None' && cleanReason !== 'null' && !cleanReason.startsWith('|') && !cleanReason.toLowerCase().includes('dirakit ulang'));
      const hasDefectiveParts = Boolean(it.defective_parts && String(it.defective_parts).trim() !== '' && String(it.defective_parts).trim() !== '-');

      if (isRealAssyReason || hasDefectiveParts) {
        if (cleanReason.toLowerCase().includes('scrap')) return 'Scrap';
        if (cleanReason.toLowerCase().includes('rework')) return 'Rework';
        return 'Hold';
      }

      // 4. Status mentah perakitan sebelum dirubah
      if (rawSt === 'hold') return 'Hold';
      if (rawSt === 'scrap') return 'Scrap';
      if (rawSt === 'rework') return 'Rework';

      return 'Good';
    };

    const getItemStartTime = (item) => {
      if (!item) return '-';
      if (!isTravelerItemAssembled(item) && !item.started_at) {
        return '-';
      }
      let raw = item.started_at || item.finished_at;
      if (!raw) return '-';
      const t = formatTimeOnly(raw);
      return t && t !== '-' ? t : '-';
    };

    const getItemFinishTime = (item) => {
      if (!item) return '-';
      if (!isTravelerItemAssembled(item) || !item.finished_at) {
        return '-';
      }
      const t = formatTimeOnly(item.finished_at, item.started_at);
      return t && t !== '-' ? t : '-';
    };

    // ===================================================================
    // 2. HASIL PERAKITAN / PRODUCTION RESULTS STATE & LOGIC
    // ===================================================================
    const assemblyResults = ref([]);
    const isLoadingAssemblyResults = ref(false);
    const assemblyResultsStatusFilter = ref('ALL');
    const selectedProductionOperator = ref('ALL');
    const selectedProductionDocAssy = ref('ALL');
    const productionSearchQuery = ref('');
    const productionViewMode = ref('table'); // 'table', 'cards', 'grouped'
    const isAssemblyResultsInitialLoaded = ref(false);

    const loadAssemblyResults = async (isSilent = false) => {
      if (!isSilent && (!assemblyResults.value || !assemblyResults.value.length)) {
        isLoadingAssemblyResults.value = true;
      }
      try {
        const res = await fetchAPI('/production/assembly-results');
        if (res.ok && res.data) {
          const rawData = Array.isArray(res.data) ? res.data : (res.data?.trays || res.data?.data || []);
          let rawTrays = [];
          if (rawData.length > 0 && !rawData[0].items && rawData[0].tray_code) {
            const gMap = {};
            rawData.forEach(item => {
              const tKey = item.tray_id || item.tray_code;
              if (!gMap[tKey]) {
                gMap[tKey] = {
                  tray_id: item.tray_id,
                  tray_code: item.tray_code,
                  source_document_mo: item.source_document_mo,
                  product_type: item.product_type || 'Nanggala P9',
                  tray_status: item.tray_status || 'active',
                  tray_created_at: item.tray_created_at,
                  pic_assembly: item.pic_assembly,
                  pic_warehouse: item.pic_warehouse,
                  items: []
                };
              }
              gMap[tKey].items.push(item);
            });
            rawTrays = Object.values(gMap);
          } else {
            rawTrays = rawData;
          }

          assemblyResults.value = rawTrays.map(t => {
            let good_count = 0;
            let hold_count = 0;
            let ru_count = 0;
            let rework_count = 0;
            let scrap_count = 0;
            let declined_count = 0;
            (t.items || []).forEach(it => {
              const st = getAssemblyStageStatus(it);
              if (st === 'Hold') hold_count++;
              else if (st === 'RU') ru_count++;
              else if (st === 'Rework') rework_count++;
              else if (st === 'Scrap') scrap_count++;
              else if (st === 'Declined') declined_count++;
              else good_count++;
            });
            return {
              ...t,
              good_count,
              hold_count,
              ru_count,
              rework_count,
              scrap_count,
              declined_count
            };
          });

          isAssemblyResultsInitialLoaded.value = true;

          if (selectedAssemblyItem.value && selectedAssemblyItem.value.assembly_id) {
            for (const tray of (assemblyResults.value || [])) {
              const found = (tray.items || []).find(it => it.assembly_id === selectedAssemblyItem.value.assembly_id);
              if (found) {
                Object.assign(selectedAssemblyItem.value, found);
                if (selectedHoldItem.value) Object.assign(selectedHoldItem.value, found);
                break;
              }
            }
          }
        }
      } catch (e) {
        console.error('Failed to load assembly results', e);
      } finally {
        isLoadingAssemblyResults.value = false;
      }
    };

    const assemblyResultsSummary = computed(() => {
      const trays = assemblyResults.value || [];
      let totalTrays = trays.length;
      let totalSets = 0;
      let totalGood = 0;
      let totalHold = 0;
      let totalRU = 0;
      let totalScrap = 0;
      let unresolvedHoldScrap = 0;
      trays.forEach(t => {
        totalSets += (t.sets_count || t.items?.length || 0);
        (t.items || []).forEach(it => {
          const st = getAssemblyStageStatus(it).toUpperCase();
          const isResolved = !!(it.is_resolved || it.status === 'Selesai' || it.status === 'Dirakit Ulang' || it.verified_status === 'APPROVED' || it.verified_status === 'WAITING_REASSEMBLY' || it.status === 'Scrap_Confirmed');
          if (st === 'HOLD' || st === 'REWORK') {
            totalHold++;
            if (!isResolved) unresolvedHoldScrap++;
          }
          else if (st === 'RU') {
            totalRU++;
          }
          else if (st === 'SCRAP') {
            totalScrap++;
            if (!isResolved) unresolvedHoldScrap++;
          }
          else if (st === 'DECLINED') {
            // Declined items do not count towards Good or RU
          }
          else {
            totalGood++;
          }
        });
      });
      const totalNotGood = totalHold + totalRU + totalScrap;
      const notifCount = unresolvedHoldScrap;
      return { totalTrays, totalSets, totalGood, totalHold, totalRU, totalRework: 0, totalScrap, totalNotGood, notifCount };
    });

    const distinctAssemblyOperators = computed(() => {
      const ops = new Set();
      (assemblyResults.value || []).forEach(t => {
        if (t.pic_assembly && t.pic_assembly !== '-') ops.add(t.pic_assembly);
        (t.items || []).forEach(it => {
          if (it.pic_assembly && it.pic_assembly !== '-') ops.add(it.pic_assembly);
        });
      });
      return Array.from(ops).sort();
    });

    const distinctAssyDocNumbers = computed(() => {
      const docs = new Set();
      (assemblyResults.value || []).forEach(t => {
        const tc = (t.tray_code || '').trim();
        if (tc && tc.toUpperCase().startsWith('WH/ASSY')) docs.add(tc);
        if (t.ru_count > 0 || /-PU/i.test(tc)) {
          docs.add(getPUDocNumber(tc));
        }
        (t.items || []).forEach(it => {
          const adc = (it.assy_doc_number || '').trim();
          if (adc && adc.toUpperCase().startsWith('WH/ASSY')) docs.add(adc);
        });
      });
      return Array.from(docs).sort();
    });

    const isAssyDocDropdownOpen = ref(false);
    const assyDocSearchText = ref('');
    const toggleAssyDocDropdown = () => {
      isAssyDocDropdownOpen.value = !isAssyDocDropdownOpen.value;
      assyDocSearchText.value = '';
    };
    const selectAssyDocFromDropdown = (doc) => {
      selectedProductionDocAssy.value = doc;
      isAssyDocDropdownOpen.value = false;
    };
    const filteredAssyDocNumbers = computed(() => {
      const list = distinctAssyDocNumbers.value || [];
      if (!assyDocSearchText.value.trim()) return list;
      const q = assyDocSearchText.value.trim().toLowerCase();
      return list.filter(d => String(d).toLowerCase().includes(q));
    });

    const calculateAssemblyDuration = (startedAt, finishedAt) => {
      if (!startedAt || !finishedAt) return '-';
      try {
        const s = new Date(startedAt.replace(' ', 'T')).getTime();
        const f = new Date(finishedAt.replace(' ', 'T')).getTime();
        if (isNaN(s) || isNaN(f)) return '-';
        let diffSec = Math.floor((f - s) / 1000);
        if (diffSec < 0) return '-';
        const m = Math.floor(diffSec / 60);
        const sec = diffSec % 60;
        if (m === 0) return `${sec} detik`;
        return `${m} mnt ${sec} dtk`;
      } catch (_) {
        return '-';
      }
    };

    const filteredGroupedAssemblyResults = computed(() => {
      let list = assemblyResults.value || [];

      // 1. Filter Produk
      if (selectedProductionProduct.value && selectedProductionProduct.value !== 'ALL') {
        list = list.filter(t => (t.product_type || 'Nanggala P9') === selectedProductionProduct.value);
      }

      // 2. Filter Operator Perakitan
      if (selectedProductionOperator.value && selectedProductionOperator.value !== 'ALL') {
        const op = selectedProductionOperator.value.toLowerCase();
        list = list.map(t => {
          const trayOpMatch = (t.pic_assembly || '').toLowerCase().includes(op);
          const filteredItems = (t.items || []).filter(it => {
            return (it.pic_assembly && it.pic_assembly.toLowerCase().includes(op)) || trayOpMatch;
          });
          return { ...t, items: filteredItems };
        }).filter(t => t.items.length > 0);
      }

      // 3. Filter No. Dokumen WH/ASSY
      if (selectedProductionDocAssy.value && selectedProductionDocAssy.value !== 'ALL') {
        const doc = selectedProductionDocAssy.value.toLowerCase();
        list = list.map(t => {
          const trayDocMatch = (t.tray_code || '').toLowerCase() === doc;
          if (trayDocMatch) return t;
          const filteredItems = (t.items || []).filter(it => {
            return (it.assy_doc_number && it.assy_doc_number.toLowerCase() === doc);
          });
          return { ...t, items: filteredItems };
        }).filter(t => t.items.length > 0);
      }

      // 4. Filter Status Murni Perakitan (Good, Hold, Rework, Scrap)
      if (assemblyResultsStatusFilter.value && assemblyResultsStatusFilter.value !== 'ALL') {
        const stFilter = assemblyResultsStatusFilter.value.toUpperCase();
        list = list.map(t => {
          const filteredItems = (t.items || []).filter(it => {
            const itSt = getAssemblyStageStatus(it).toUpperCase();
            if (stFilter === 'GOOD') return itSt === 'GOOD';
            if (stFilter === 'HOLD') return itSt === 'HOLD';
            if (stFilter === 'RU') return itSt === 'RU';
            if (stFilter === 'REWORK') return itSt === 'REWORK';
            if (stFilter === 'SCRAP') return itSt === 'SCRAP';
            return true;
          });
          return { ...t, items: filteredItems };
        }).filter(t => t.items.length > 0);
      }

      // 5. Search Query
      if (productionSearchQuery.value) {
        const q = productionSearchQuery.value.trim().toLowerCase();
        list = list.map(t => {
          const trayMatch = (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
            (t.source_document_mo && t.source_document_mo.toLowerCase().includes(q)) ||
            (t.pic_assembly && t.pic_assembly.toLowerCase().includes(q));
          if (trayMatch) return t;
          const filteredItems = (t.items || []).filter(it => {
            return (it.part15_sn && it.part15_sn.toLowerCase().includes(q)) ||
              (it.part1_sn && it.part1_sn.toLowerCase().includes(q)) ||
              (it.part12_sn && it.part12_sn.toLowerCase().includes(q)) ||
              (it.assembly_reason && it.assembly_reason.toLowerCase().includes(q)) ||
              (it.reason && it.reason.toLowerCase().includes(q)) ||
              (it.pic_assembly && it.pic_assembly.toLowerCase().includes(q)) ||
              (it.assy_doc_number && it.assy_doc_number.toLowerCase().includes(q));
          });
          return { ...t, items: filteredItems };
        }).filter(t => t.items.length > 0);
      }

      return list;
    });

    const flatFilteredAssemblyResults = computed(() => {
      const grouped = filteredGroupedAssemblyResults.value || [];
      const items = [];
      grouped.forEach(t => {
        (t.items || []).forEach(it => {
          items.push({
            ...it,
            tray_code: t.tray_code,
            source_document_mo: t.source_document_mo,
            product_type: t.product_type
          });
        });
      });
      return items;
    });

    const filteredAssemblyResults = computed(() => flatFilteredAssemblyResults.value);

    const pagedAssemblyResults = computed(() => {
      const s = pgState.assemblyResults || { page: 1, size: 10 };
      return (flatFilteredAssemblyResults.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgAssemblyResults = computed(() => {
      const s = pgState.assemblyResults || { page: 1, size: 10 };
      return Math.max(1, Math.ceil((flatFilteredAssemblyResults.value || []).length / s.size));
    });

    // ===================================================================
    // 3. FUNCTIONAL HOLD / HASIL TEST STATE & LOGIC
    // ===================================================================
    const productionHoldList = ref([]);
    const productionProductsList = ref(['Nanggala P9']);
    const selectedProductionProduct = ref('ALL');
    const productionSourceFilter = ref('ALL');
    const functionalHoldViewMode = ref('table'); // 'table' or 'cards'
    const isLoadingProductionHold = ref(false);
    const isHoldItemsInitialLoaded = ref(false);

    const loadProductionProducts = async () => {
      try {
        const res = await fetchAPI('/production/products');
        if (res.ok && res.data) {
          productionProductsList.value = res.data;
        }
      } catch (e) {
        console.error('Failed to load production products', e);
      }
    };

    const loadProductionHoldItems = async (isSilent = false) => {
      if (!isSilent && (!productionHoldList.value || !productionHoldList.value.length)) {
        isLoadingProductionHold.value = true;
      }
      try {
        const res = await fetchAPI('/production/hold-items');
        if (res.ok && res.data) {
          const newItems = Array.isArray(res.data) ? res.data : [];
          productionHoldList.value = newItems;
          isHoldItemsInitialLoaded.value = true;

          if (selectedHoldItem.value && selectedHoldItem.value.assembly_id) {
            const found = (productionHoldList.value || []).find(it => it.assembly_id === selectedHoldItem.value.assembly_id);
            if (found) {
              Object.assign(selectedHoldItem.value, found);
              if (selectedAssemblyItem.value) Object.assign(selectedAssemblyItem.value, found);
            }
          }
        }
      } catch (e) {
        console.error('Failed to load production hold items', e);
      } finally {
        isLoadingProductionHold.value = false;
      }
    };

    const isItemInReassemblyProcess = (item) => {
      if (!item) return false;
      if (Boolean(item.is_declined) || String(item.status || '').toLowerCase() === 'declined' || String(item.verified_status || '').toUpperCase() === 'DECLINED') {
        return false;
      }
      if (typeof isReassemblySet === 'function' && isReassemblySet(item)) return true;
      const st = String(item.assembly_status || item.status || '').trim().toLowerCase();
      const vStat = String(item.verified_status || '').trim().toUpperCase();
      const assyTestSt = String(item.assy_test_status || '').trim().toUpperCase();
      const qcSt = String(item.qc_status || '').trim().toUpperCase();
      if (st === 'dirakit ulang' || st === 'ru' || assyTestSt === 'RU' || qcSt === 'RU') return true;
      if (['WAITING_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_REREG', 'APPROVED', 'APPROVED_QC'].includes(vStat)) return true;
      if (item.latest_replacement_status === 'APPROVED') return true;
      if (item.rework_reason && item.rework_reason.includes('Ganti Part') && ['WAITING_REASSEMBLY', 'COMPLETED_PU', 'APPROVED'].includes(vStat)) return true;
      return false;
    };

    const baseProductionHoldList = computed(() => {
      let list = productionHoldList.value || [];
      list = list.filter(item => {
        const isRU = isItemInReassemblyProcess(item);
        if (isRU) return true;
        const hasTestingRecord = Boolean(item.assy_failure_reasons || item.qc_failure_reasons || item.assy_test_checklist || item.qc_test_checklist || item.qc_tested_at || item.assy_tested_at);
        const hasAssyProblem = Boolean(item.assembly_reason || item.reason);
        if (hasAssyProblem && !item.assy_failure_reasons && !item.qc_failure_reasons) {
          return false;
        }
        return hasTestingRecord;
      });
      if (selectedProductionProduct.value && selectedProductionProduct.value !== 'ALL') {
        list = list.filter(item => (item.product_type || 'Nanggala P9') === selectedProductionProduct.value);
      }
      if (productionSearchQuery.value) {
        const q = productionSearchQuery.value.trim().toLowerCase();
        list = list.filter(item => {
          return (item.part15_sn && item.part15_sn.toLowerCase().includes(q)) ||
            (item.part12_sn && item.part12_sn.toLowerCase().includes(q)) ||
            (item.part1_sn && item.part1_sn.toLowerCase().includes(q)) ||
            (item.tray_code && item.tray_code.toLowerCase().includes(q)) ||
            (item.source_document_mo && item.source_document_mo.toLowerCase().includes(q)) ||
            (item.display_reason && item.display_reason.toLowerCase().includes(q)) ||
            (item.qc_failure_reasons && item.qc_failure_reasons.toLowerCase().includes(q)) ||
            (item.assy_failure_reasons && item.assy_failure_reasons.toLowerCase().includes(q));
        });
      }
      return list;
    });

    const productionHoldCounts = computed(() => {
      const all = baseProductionHoldList.value || [];
      const reregistered = all.filter(isItemInReassemblyProcess).length;
      const active_hold = all.filter(i => !isItemInReassemblyProcess(i)).length;
      const active_assy = all.filter(i => !isItemInReassemblyProcess(i) && (i.hold_source === 'TESTER_ASSEMBLY' || i.hold_source === 'ASSY')).length;
      const active_qc = all.filter(i => !isItemInReassemblyProcess(i) && i.hold_source === 'QC').length;
      return {
        total: all.length,
        active_hold,
        active_assy,
        active_qc,
        assy: active_assy,
        qc: active_qc,
        ru: reregistered,
        reregistered
      };
    });

    const filteredProductionHoldList = computed(() => {
      let list = baseProductionHoldList.value || [];
      if (productionSourceFilter.value && productionSourceFilter.value !== 'ALL') {
        list = list.filter(item => {
          if (productionSourceFilter.value === 'ASSY' || productionSourceFilter.value === 'TESTER_ASSEMBLY') {
            return (item.hold_source === 'ASSY' || item.hold_source === 'TESTER_ASSEMBLY') && !isItemInReassemblyProcess(item);
          }
          if (productionSourceFilter.value === 'QC') {
            return item.hold_source === 'QC' && !isItemInReassemblyProcess(item);
          }
          if (productionSourceFilter.value === 'RU' || productionSourceFilter.value === 'REREGISTERED') {
            return isItemInReassemblyProcess(item);
          }
          return item.hold_source === productionSourceFilter.value;
        });
      }
      return list;
    });

    const productionHoldCount = computed(() => productionHoldCounts.value.active_hold);

    const pagedProductionHoldList = computed(() => {
      const s = pgState.productionHold || { page: 1, size: 15 };
      return (filteredProductionHoldList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgProductionHold = computed(() => {
      const s = pgState.productionHold || { page: 1, size: 15 };
      return Math.max(1, Math.ceil((filteredProductionHoldList.value || []).length / s.size));
    });

    const isAllProblematicPartsReplaced = (item) => {
      if (!item) return false;
      if (isReassemblySet(item)) return false;
      if (item.verified_status === 'APPROVED_REREG' || item.is_reregistered) return false;
      if (item.latest_replacement_status === 'APPROVED') return true;
      if (item.rework_reason && item.rework_reason.includes('Ganti Part')) return true;
      return !!(item.replacement_pending || item.pending_action_type === 'PENDING_GANTI_PART');
    };

    const getHoldProceedButtonLabel = (item) => {
      if (!item) return 'Lanjut';
      if (isAllProblematicPartsReplaced(item)) return 'Rakit Ulang';
      const isQC = item.hold_source === 'QC' || item.stage === 'QC' || item.process_stage === 'qc' ||
        ['HOLD_QC', 'REWORK_QC'].includes(item.qc_status) ||
        (Boolean(item.qc_failure_reasons) && !item.assy_failure_reasons);
      if (isQC) return 'Lanjut ke Penerimaan';
      const isTF1 = item.hold_source === 'TESTER_ASSEMBLY' || item.stage === 'TF1' || item.process_stage === 'test_functional_1' ||
        ['Hold', 'Rework'].includes(item.assy_test_status) || ['HOLD_ASSY', 'REWORK_ASSY'].includes(item.qc_status) ||
        Boolean(item.assy_failure_reasons) || Boolean(item.assy_test_checklist);
      if (isTF1) return 'Lanjut ke TF2';
      return 'Lanjut TF1';
    };

    const getHoldProceedButtonTitle = (item) => {
      if (!item) return '';
      if (isAllProblematicPartsReplaced(item)) return 'Rakit Ulang (Part telah diganti)';
      const label = getHoldProceedButtonLabel(item);
      return `${label} (Tanpa ganti part)`;
    };

    const getItemPendingLabel = (item) => {
      if (!item) return 'MENUNGGU VERIFIKASI ADMIN';
      const v = String(item.verified_status || '').toUpperCase();
      if (v === 'PENDING_ADMIN_PROCEED_TF1') return 'MENUNGGU VERIFIKASI LANJUT TF 1';
      if (v === 'PENDING_ADMIN_PROCEED_QC') return 'MENUNGGU VERIFIKASI LANJUT TF 2';
      if (v === 'PENDING_ADMIN_QC') return 'MENUNGGU VERIFIKASI PENERIMAAN';
      const t = item.pending_action_type || '';
      if (t === 'PENDING_GANTI_PART' || item.has_pending_replacement || item.replacement_pending || item.verified_status === 'PENDING_REPLACEMENT') {
        return 'MENUNGGU VERIFIKASI GANTI PART';
      }
      if (t === 'PENDING_DAFTAR_ULANG' || item.has_pending_reregister || item.re_register_pending || item.verified_status === 'PENDING_ADMIN_ASSY_REREG') {
        return 'MENUNGGU VERIFIKASI DAFTAR ULANG';
      }
      return 'MENUNGGU VERIFIKASI ADMIN';
    };

    const isItemPendingAdmin = (item) => {
      if (!item) return false;
      const vStat = String(item.verified_status || '').trim().toUpperCase();
      const resolvedStatuses = ['WAITING_REASSEMBLY', 'APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'ACC', 'COMPLETED_PU', 'REJECTED', 'DECLINED', 'APPROVED_PROCEED_TF1', 'APPROVED_PROCEED_TF2', 'REJECTED_PROCEED'];
      if (resolvedStatuses.includes(vStat)) {
        item.re_register_pending = false;
        item.has_pending_reregister = false;
        item.replacement_pending = false;
        item.has_pending_replacement = false;
        item.has_pending_proceed = false;
        item.has_pending_request = false;
        item.pending_action_type = '';
        return false;
      }
      if (isReassemblySet(item)) return false;

      if (vStat.startsWith('PENDING_ADMIN') || vStat === 'PENDING_REPLACEMENT') return true;
      if (Boolean(item.replacement_pending) || Boolean(item.has_pending_replacement)) return true;
      if (Boolean(item.re_register_pending) || Boolean(item.has_pending_reregister)) return true;
      if (Boolean(item.has_pending_proceed)) return true;
      if (item.pending_action_type === 'PENDING_GANTI_PART' || item.pending_action_type === 'PENDING_DAFTAR_ULANG') return true;

      if (Boolean(item.is_reregistered) && !resolvedStatuses.includes(vStat)) return true;
      if (Boolean(item.has_pending_request) || Boolean(item.is_pending_verif) || Boolean(item.has_pending_proceed)) {
        if (!resolvedStatuses.includes(vStat)) return true;
      }
      return false;
    };

    const isFunctionalHoldItem = (item) => {
      if (!item) return false;
      if (currentTab.value === 'functional_hold') return true;
      const hs = String(item.hold_source || '').toUpperCase();
      if (hs === 'TESTER_ASSEMBLY' || hs === 'QC') return true;
      const stg = String(item.stage || item.process_stage || '').toUpperCase();
      if (stg === 'TF1' || stg === 'QC' || stg === 'TEST_FUNCTIONAL_1') return true;
      if (item.assy_failure_reasons || item.qc_failure_reasons || item.assy_test_checklist) return true;
      const qcSt = String(item.qc_status || '').toUpperCase();
      const atSt = String(item.assy_test_status || '').toUpperCase();
      if (['HOLD_ASSY', 'HOLD_QC', 'REWORK_ASSY', 'REWORK_QC'].includes(qcSt)) return true;
      if (['HOLD', 'REWORK'].includes(atSt)) return true;
      return false;
    };

    const proceedHoldItem = async (item) => {
      if (!item) return;

      if (isAllProblematicPartsReplaced(item)) {
        selectedHoldItem.value = item;
        selectedAssemblyItem.value = item;
        if (typeof openReregisterSubModal === 'function') {
          openReregisterSubModal();
        } else if (typeof openReregisterSetConfirmModal === 'function') {
          openReregisterSetConfirmModal(item);
        }
        return;
      }

      if (isItemPendingAdmin(item)) {
        triggerToast('Perhatian', 'Set ini sedang menunggu persetujuan verifikasi Administrator.', 'warning');
        return;
      }

      const targetLabel = getHoldProceedButtonLabel(item) || 'Lanjut';
      const setNum = item.set_number || (item.assembly ? item.assembly.set_number : '?');
      const trayCode = item.tray_code || (item.tray ? item.tray.tray_code : '') || (currentTray.value ? currentTray.value.tray_code : '');

      const ok = await showConfirmCard({
        title: `Permohonan ${targetLabel}`,
        message: `Apakah Anda yakin ingin mengajukan permohonan ${targetLabel} untuk rakitan Set #${setNum} (${trayCode}) ke Admin? Set ini akan dikunci sementara menunggu verifikasi Administrator.`,
        icon: 'fa-solid fa-circle-arrow-right',
        iconBg: 'bg-emerald-100 text-emerald-600',
        confirmText: `Ya, Ajukan ${targetLabel}`,
        confirmBtnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white'
      });
      if (!ok) return;

      try {
        const res = await fetchAPI('/assembly/request-proceed', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: item.assembly_id || item.id,
            notes: `Permohonan ${targetLabel} diajukan oleh ${userFullName.value || userName.value || 'Operator'}`
          })
        });

        if (res.ok) {
          triggerToast('Permohonan Terkirim', res.message || `Permohonan ${targetLabel} berhasil diajukan ke Admin!`, 'success');

          const pendingStatus = (targetLabel.includes('Penerimaan') ? 'PENDING_ADMIN_QC' : (targetLabel.includes('TF2') ? 'PENDING_ADMIN_PROCEED_QC' : 'PENDING_ADMIN_PROCEED_TF1'));
          item.verified_status = pendingStatus;
          item.has_pending_proceed = true;
          item.has_pending_request = true;

          if (selectedHoldItem.value && (selectedHoldItem.value.id === item.id || selectedHoldItem.value.assembly_id === item.id)) {
            selectedHoldItem.value.verified_status = pendingStatus;
            selectedHoldItem.value.has_pending_proceed = true;
          }
          if (selectedAssemblyItem.value && (selectedAssemblyItem.value.id === item.id || selectedAssemblyItem.value.assembly_id === item.id)) {
            selectedAssemblyItem.value.verified_status = pendingStatus;
            selectedAssemblyItem.value.has_pending_proceed = true;
          }

          showHoldDetailModal.value = false;
          showAssemblyDetailModal.value = false;

          await loadProductionHoldItems();
          if (typeof loadPendingAssemblies === 'function') await loadPendingAssemblies();
          if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
          if (typeof loadQCPendingItems === 'function') await loadQCPendingItems();
          await loadAssemblyResults();
        } else {
          triggerToast('Gagal', res.error || res.message || 'Gagal mengajukan permohonan ke Admin.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // ===================================================================
    // 4. MODALS & DETAIL ACTIONS (SHOW DETAIL / TRANSFER TO WAREHOUSE)
    // ===================================================================
    const showAssemblyDetailModal = ref(false);
    const selectedAssemblyItem = ref(null);
    const selectedAssemblyTray = ref(null);

    const showHoldDetailModal = ref(false);
    const selectedHoldItem = ref(null);
    const selectedHoldTray = ref(null);

    const openAssemblyDetailModal = (item, tray = null) => {
      if (item) {
        if (!item.assembly_id && item.id) item.assembly_id = item.id;
        const v = String(item.verified_status || '').toUpperCase();
        if (v === 'APPROVED' || v === 'APPROVED_REREG' || v === 'ACC' || item.latest_replacement_status === 'APPROVED') {
          item.replacement_pending = false;
          item.has_pending_replacement = false;
          if (item.pending_action_type === 'PENDING_GANTI_PART') {
            item.pending_action_type = '';
          }
        }
      }
      selectedAssemblyItem.value = item;
      selectedAssemblyTray.value = tray || (assemblyResults.value || []).find(t => t.tray_code === item?.tray_code);
      selectedHoldItem.value = item;
      selectedHoldTray.value = selectedAssemblyTray.value;
      showAssemblyDetailModal.value = true;
      showHoldDetailModal.value = false;
    };

    const openHoldDetailModal = (item, tray = null) => {
      openAssemblyDetailModal(item, tray);
      showHoldDetailModal.value = true;
    };

    const transferSingleSetToWarehouse = async (item, tray = null) => {
      const target = item || selectedAssemblyItem.value || selectedHoldItem.value;
      if (!target) return;
      const setNum = target.set_number || '?';
      const trayCode = target.tray_code || (tray ? tray.tray_code : (selectedAssemblyTray.value ? selectedAssemblyTray.value.tray_code : ''));
      const ok = await showConfirmCard({
        title: 'Pindahkan ke Gudang',
        message: `Pindahkan Set #${setNum} (${trayCode}) ke Gudang untuk penanganan fisik / verifikasi?`,
        icon: 'fa-solid fa-boxes-packing',
        iconBg: 'bg-amber-100 text-amber-600',
        confirmText: 'Ya, Pindahkan',
        confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
      });
      if (!ok) return;

      try {
        const res = await fetchAPI('/assembly/transfer-to-warehouse', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: target.assembly_id,
            tray_id: target.tray_id || (tray ? tray.tray_id : null),
            set_number: target.set_number,
            reason: 'Dipindahkan ke gudang oleh operator dari Hasil Perakitan'
          })
        });
        if (res.ok) {
          triggerToast('Berhasil', res.message || `Set #${setNum} berhasil dipindahkan ke Gudang!`, 'success');
          showAssemblyDetailModal.value = false;
          showHoldDetailModal.value = false;
          await loadAssemblyResults();
          await loadProductionHoldItems();
        } else {
          triggerToast('Gagal', res.error || 'Gagal memindahkan set ke gudang', 'error');
        }
      } catch (err) {
        triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
      }
    };

    const confirmReturnTrayToWarehouse = async (tray) => {
      if (!tray) return;
      const reason = await showPromptCard({
        title: 'Kembalikan Work Order ke Gudang',
        message: `Masukkan alasan pengembalian/pemindahan Work Order ${tray.tray_code} ke Gudang:`,
        defaultValue: 'Sisa set belum selesai dikembalikan ke gudang',
        inputLabel: 'Alasan Pemindahan:',
        confirmText: 'Pindahkan ke Gudang',
        confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
      });
      if (reason === null) return;

      try {
        const res = await fetchAPI('/assembly/session-action', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: tray.tray_id,
            action: 'RETURN_WAREHOUSE',
            reason: (reason && reason.trim()) ? reason.trim() : 'Dikembalikan ke gudang dari Hasil Perakitan'
          })
        });
        if (res.ok) {
          triggerToast('Berhasil', res.message || `Tray ${tray.tray_code} berhasil dipindahkan ke Gudang!`, 'success');
          await loadAssemblyResults();
        } else {
          triggerToast('Gagal', res.error || 'Gagal memindahkan tray ke gudang', 'error');
        }
      } catch (err) {
        triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
      }
    };

    // ===================================================================
    // 5. SUB-MODAL 1: DAFTAR ULANG 1-SET UTUH (REASSEMBLY SUBMISSION)
    // ===================================================================
    const showReregisterSubModal = ref(false);
    const reregisterTargetStatus = ref('');
    const reregOperator = ref('');
    const reregisterReason = ref('');
    const isSubmittingReregisterSetAction = ref(false);

    const availableReregisterStatuses = computed(() => {
      const cur = String(selectedHoldItem.value?.assembly_status || selectedHoldItem.value?.qc_status || 'Hold');
      const all = ['Good', 'Hold', 'Scrap'];
      return all.filter(s => s.toLowerCase() !== cur.toLowerCase());
    });

    const openReregisterSubModal = () => {
      const currentItem = selectedHoldItem.value || selectedAssemblyItem.value;
      if (!currentItem) return;

      const isFromTesting = currentTab.value === 'functional_hold' || Boolean(selectedHoldItem.value) ||
        currentItem.stage === 'QC' || currentItem.process_stage === 'qc' || currentItem.process_stage === 'test_functional_1' ||
        Boolean(currentItem.assy_test_checklist) || Boolean(currentItem.assy_failure_reasons) || Boolean(currentItem.qc_failure_reasons);
      if (isFromTesting) {
        showHoldDetailModal.value = false;
        showAssemblyDetailModal.value = false;
        openReregisterSetConfirmModal(currentItem);
        return;
      }

      if (currentItem.verified_status === 'APPROVED_REREG' || currentItem.is_reregistered) {
        triggerToast('Informasi', 'Set ini sudah berhasil didaftarkan ulang dan disetujui Admin ke dokumen perakitan ulang.', 'info');
        return;
      }
      if (isItemPendingAdmin(currentItem)) {
        triggerToast('Perhatian', 'Set ini sedang dalam antrean verifikasi Admin. Harap tunggu persetujuan Admin sebelum dapat mendaftar ulang kembali.', 'warning');
        return;
      }
      reregOperator.value = currentItem?.pic_assembly || '';
      reregisterReason.value = '';
      showReregisterSubModal.value = true;
    };

    const submitReregisterSetAction = async () => {
      const item = selectedHoldItem.value || selectedAssemblyItem.value;
      if (!item || !item.assembly_id) return;
      isSubmittingReregisterSetAction.value = true;
      try {
        const res = await fetchAPI('/assembly/reregister-set', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: item.assembly_id,
            new_operator: reregOperator.value || item.pic_assembly || '',
            reason: reregisterReason.value
          })
        });
        if (res.ok) {
          triggerToast('Berhasil', `Pengajuan Perakitan Ulang Set #${item.set_number} telah dikirim ke Admin untuk diverifikasi.`, 'success');
          const assyId = item.assembly_id;
          const chosenOp = reregOperator.value || item.pic_assembly;
          const reregState = {
            re_register_pending: true,
            has_pending_reregister: true,
            pending_action_type: 'PENDING_DAFTAR_ULANG',
            verified_status: 'PENDING_ADMIN_ASSY_REREG',
            rereg_pending_operator: chosenOp
          };
          if (selectedHoldItem.value) Object.assign(selectedHoldItem.value, reregState);
          if (selectedAssemblyItem.value) Object.assign(selectedAssemblyItem.value, reregState);
          if (assyId) {
            (assemblyResults.value || []).forEach(tray => {
              (tray.items || []).forEach(it => {
                if (it.assembly_id === assyId) Object.assign(it, reregState);
              });
            });
            (productionHoldList.value || []).forEach(it => {
              if (it.assembly_id === assyId) Object.assign(it, reregState);
            });
          }
          showReregisterSubModal.value = false;
          await loadAssemblyResults();
          await loadProductionHoldItems();
          if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
        } else {
          triggerToast('Gagal', res.error || 'Gagal mengajukan perakitan ulang', 'error');
        }
      } catch (err) {
        triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
      } finally {
        isSubmittingReregisterSetAction.value = false;
      }
    };

    // ===================================================================
    // 6. SUB-MODAL 2: GANTI PART RUSAK DENGAN STOK MASTER
    // ===================================================================
    const showReplacePartModal = ref(false);
    const replaceOldStatus = ref('Hold');
    const replaceReason = ref('');
    const isSubmittingReplacePartAction = ref(false);
    const showAllReplaceParts = ref(false);

    const replaceSelections = ref({ '15': '', '1': '', '12': '' });
    const replaceDropdownOpen = ref({ '15': false, '1': false, '12': false });
    const replaceSearchQueries = ref({ '15': '', '1': '', '12': '' });
    const availableReplacePartsByType = ref({ '15': [], '1': [], '12': [] });
    const isLoadingPartsByType = ref({ '15': false, '1': false, '12': false });

    const replacePartType = ref('15');
    const replaceNewSN = computed({
      get: () => replaceSelections.value['15'] || replaceSelections.value['1'] || replaceSelections.value['12'] || '',
      set: (val) => { replaceSelections.value['15'] = val; }
    });
    const availableReplaceParts = computed(() => availableReplacePartsByType.value[replacePartType.value] || []);
    const isLoadingReplaceParts = computed(() => isLoadingPartsByType.value[replacePartType.value] || false);
    const isReplacePartDropdownOpen = ref(false);
    const replacePartSearchQuery = ref('');

    const problematicPartTypes = computed(() => {
      const item = selectedAssemblyItem.value || selectedHoldItem.value;
      if (!item) return [];
      const raw = item.defective_parts || item.defective_part_types || '';
      if (!raw) return [];
      let list = [];
      if (Array.isArray(raw)) {
        list = raw.map(x => String(x).trim().toLowerCase());
      } else if (typeof raw === 'string') {
        let str = raw.trim();
        if (str.startsWith('[') && str.endsWith(']')) {
          try {
            const parsed = JSON.parse(str);
            if (Array.isArray(parsed)) list = parsed.map(x => String(x).trim().toLowerCase());
          } catch (_) { }
        }
        if (!list.length) {
          list = str.split(',').map(x => x.trim().toLowerCase()).filter(Boolean);
        }
      }
      const normalized = [];
      for (const p of list) {
        if (['15', 'part15', 'p15', 'p9'].includes(p)) normalized.push('15');
        else if (['1', 'part1', 'p1'].includes(p)) normalized.push('1');
        else if (['12', 'part12', 'p12'].includes(p)) normalized.push('12');
        else if (item.part15_sn && p === String(item.part15_sn).toLowerCase()) normalized.push('15');
        else if (item.part1_sn && p === String(item.part1_sn).toLowerCase()) normalized.push('1');
        else if (item.part12_sn && p === String(item.part12_sn).toLowerCase()) normalized.push('12');
      }
      return [...new Set(normalized)];
    });

    const isProblemPart = (type) => {
      const list = problematicPartTypes.value;
      if (!list || list.length === 0) return true;
      return list.includes(String(type));
    };

    const hasNonProblematicParts = computed(() => {
      return problematicPartTypes.value.length > 0 && problematicPartTypes.value.length < 3;
    });

    const getParsedReplacementHistory = (text) => {
      if (!text) return [];
      const str = String(text);
      const results = [];
      const regex = /Part\s+(\d+)\s*\(([^-\s>]+)\s*->\s*([^)\s]+)\)/gi;
      let m;
      while ((m = regex.exec(str)) !== null) {
        results.push({ partType: m[1], oldSN: m[2], newSN: m[3] });
      }
      return results;
    };

    const getItemProblemReason = (item) => {
      if (!item) return '-';
      const parts = [];
      const r = (item.reason || item.assembly_reason || '').trim();
      if (r) parts.push(r);
      const rw = (item.rework_reason || '').trim();
      if (rw && !parts.includes(rw)) parts.push(`Rework: ${rw}`);
      const assyFail = (item.assy_failure_reasons || '').trim();
      if (assyFail && !parts.includes(assyFail)) parts.push(`Gagal Uji Perakitan: ${assyFail}`);
      const qcFail = (item.qc_failure_reasons || '').trim();
      if (qcFail && !parts.includes(qcFail)) parts.push(`Gagal Uji QC: ${qcFail}`);
      const decl = (item.declined_reason || '').trim();
      if (decl && !parts.includes(decl)) parts.push(`Ditolak: ${decl}`);
      const notes = (item.qc_notes || '').trim();
      if (notes && !parts.includes(notes)) parts.push(`Catatan: ${notes}`);

      return parts.length > 0 ? parts.join(' • ') : 'Tidak ada keterangan alasan khusus yang tercatat.';
    };

    const getFilteredPartsForType = (type) => {
      const list = availableReplacePartsByType.value[type] || [];
      const q = (replaceSearchQueries.value[type] || '').trim().toLowerCase();
      if (!q) return list;
      return list.filter(p => (p.sn && p.sn.toLowerCase().includes(q)) || (p.status && p.status.toLowerCase().includes(q)));
    };

    const filteredReplaceParts = computed(() => getFilteredPartsForType('15'));

    const selectReplaceSNForPart = (type, sn) => {
      replaceSelections.value[type] = sn;
      replaceDropdownOpen.value[type] = false;
    };

    const clearReplaceSNForPart = (type) => {
      replaceSelections.value[type] = '';
    };

    const toggleReplaceDropdown = (type) => {
      const current = replaceDropdownOpen.value[type];
      replaceDropdownOpen.value = { '15': false, '1': false, '12': false };
      replaceDropdownOpen.value[type] = !current;
    };

    const closeAllReplaceDropdowns = () => {
      replaceDropdownOpen.value = { '15': false, '1': false, '12': false };
    };

    const selectReplaceNewSN = (sn) => {
      replaceSelections.value[replacePartType.value] = sn;
      isReplacePartDropdownOpen.value = false;
    };

    const totalPartsToReplace = computed(() => {
      let count = 0;
      if (isProblemPart('15') && replaceSelections.value['15']) count++;
      if (isProblemPart('1') && replaceSelections.value['1']) count++;
      if (isProblemPart('12') && replaceSelections.value['12']) count++;
      return count;
    });

    const loadAllAvailableReplaceParts = async () => {
      const types = ['15', '1', '12'];
      const curItem = selectedAssemblyItem.value || selectedHoldItem.value;
      const curTrayId = curItem?.tray_id || '';
      await Promise.all(types.map(async (t) => {
        isLoadingPartsByType.value[t] = true;
        try {
          const url = `/master/available?type=${encodeURIComponent(t)}&limit=100${curTrayId ? `&tray_id=${encodeURIComponent(curTrayId)}` : ''}`;
          const res = await fetchAPI(url);
          if (res && res.ok && Array.isArray(res.data)) {
            availableReplacePartsByType.value[t] = res.data;
          } else {
            availableReplacePartsByType.value[t] = [];
          }
        } catch (_) {
          availableReplacePartsByType.value[t] = [];
        } finally {
          isLoadingPartsByType.value[t] = false;
        }
      }));
    };

    const openReregisterForPart = (partType, sn) => {
      if (!sn || sn === '-') {
        triggerToast('SN Tidak Valid', 'Nomor seri part tidak ditemukan atau belum terisi.', 'warn');
        return;
      }
      const curItem = selectedAssemblyItem.value || selectedHoldItem.value || {};
      const status = curItem.assembly_status || curItem.status || 'Hold';
      const reason = getItemProblemReason(curItem) || curItem.assembly_reason || curItem.reason || '';

      openReregisterModal({
        part_type: String(partType),
        sn: sn,
        status: status,
        assembly_reason: reason,
        last_reason: reason,
        is_replaced_from_assy: 1
      });
    };

    const closeReplacePartModal = () => {
      showReplacePartModal.value = false;
      replaceSelections.value = { '15': '', '1': '', '12': '' };
      replaceDropdownOpen.value = { '15': false, '1': false, '12': false };
      replaceSearchQueries.value = { '15': '', '1': '', '12': '' };
      replaceReason.value = '';
    };

    const openReplacePartSubModal = async () => {
      showAllReplaceParts.value = false;
      replaceOldStatus.value = 'Hold';
      replaceSelections.value = { '15': '', '1': '', '12': '' };
      replaceDropdownOpen.value = { '15': false, '1': false, '12': false };
      replaceSearchQueries.value = { '15': '', '1': '', '12': '' };
      const curItem = selectedAssemblyItem.value || selectedHoldItem.value;
      replaceReason.value = getItemProblemReason(curItem) || curItem?.reason || curItem?.assembly_reason || '';
      showReplacePartModal.value = true;
      await loadAllAvailableReplaceParts();
    };

    const onSelectReplacePartType = async (type) => {
      replacePartType.value = type;
      toggleReplaceDropdown(type);
    };

    const openReplaceForPart = async (partType) => {
      await openReplacePartSubModal();
      if (partType && replaceDropdownOpen.value[String(partType)] !== undefined) {
        replaceDropdownOpen.value[String(partType)] = true;
      }
    };

    const submitReplacePartAction = async () => {
      const curItem = selectedAssemblyItem.value || selectedHoldItem.value;
      const targetAssemblyId = curItem?.assembly_id || curItem?.id;
      if (!curItem || !targetAssemblyId) {
        triggerToast('Validasi', 'Data rakitan tidak ditemukan!', 'warn');
        return;
      }

      const reps = [];
      if (isProblemPart('15') && replaceSelections.value['15']) {
        reps.push({ part_type: '15', new_sn: replaceSelections.value['15'] });
      }
      if (isProblemPart('1') && replaceSelections.value['1']) {
        reps.push({ part_type: '1', new_sn: replaceSelections.value['1'] });
      }
      if (isProblemPart('12') && replaceSelections.value['12']) {
        reps.push({ part_type: '12', new_sn: replaceSelections.value['12'] });
      }

      if (reps.length === 0) {
        triggerToast('Pilih Part', 'Silakan pilih minimal 1 part baru pengganti pada dropdown di atas!', 'warn');
        return;
      }

      isSubmittingReplacePartAction.value = true;
      try {
        const res = await fetchAPI('/assembly/replace-part', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: targetAssemblyId,
            tray_id: curItem?.tray_id || undefined,
            replacements: reps,
            part_type: reps[0] ? reps[0].part_type : '',
            new_sn: reps[0] ? reps[0].new_sn : '',
            old_part_status: replaceOldStatus.value,
            reason: replaceReason.value
          })
        });
        if (res.ok) {
          triggerToast('Berhasil', res.message || `${reps.length} part berhasil diganti! Menunggu verifikasi Admin.`, 'success');
          const assyId = selectedHoldItem.value?.assembly_id || selectedAssemblyItem.value?.assembly_id;
          const pendingState = {
            replacement_pending: true,
            has_pending_replacement: true,
            pending_action_type: 'PENDING_GANTI_PART',
            verified_status: 'PENDING_REPLACEMENT',
            latest_replacement_status: 'PENDING'
          };
          if (selectedHoldItem.value) Object.assign(selectedHoldItem.value, pendingState);
          if (selectedAssemblyItem.value) Object.assign(selectedAssemblyItem.value, pendingState);
          if (assyId) {
            (assemblyResults.value || []).forEach(tray => {
              (tray.items || []).forEach(it => {
                if (it.assembly_id === assyId) Object.assign(it, pendingState);
              });
            });
            (productionHoldList.value || []).forEach(it => {
              if (it.assembly_id === assyId) Object.assign(it, pendingState);
            });
          }
          showReplacePartModal.value = false;
          await loadAssemblyResults();
          await loadProductionHoldItems();
          if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
        } else {
          triggerToast('Gagal', res.error || res.message || 'Gagal melakukan penggantian part', 'error');
        }
      } catch (err) {
        triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
      } finally {
        isSubmittingReplacePartAction.value = false;
      }
    };

    // ===================================================================
    // 7. PERAKITAN ULANG (RU) MODULE & DISPATCH
    // ===================================================================
    const pendingReassemblyList = ref([]);
    const selectedReassemblyIds = ref([]);
    const selectedReassemblyOperator = ref('');
    const isLoadingPendingReassembly = ref(false);
    const isSubmittingReassemblyDispatch = ref(false);

    const loadPendingReassembly = async (isSilent = false) => {
      if (!isSilent && (!pendingReassemblyList.value || !pendingReassemblyList.value.length)) {
        isLoadingPendingReassembly.value = true;
      }
      try {
        const res = await fetchAPI('/assembly/pending-reassembly');
        if (res && res.ok && Array.isArray(res.data)) {
          pendingReassemblyList.value = res.data;
        } else {
          pendingReassemblyList.value = [];
        }
      } catch (e) {
        console.error('Error loadPendingReassembly:', e);
        pendingReassemblyList.value = [];
      } finally {
        isLoadingPendingReassembly.value = false;
      }
    };

    const pendingReassemblyCount = computed(() => (pendingReassemblyList.value || []).length);

    const toggleSelectReassembly = (id) => {
      const idx = selectedReassemblyIds.value.indexOf(id);
      if (idx >= 0) {
        selectedReassemblyIds.value.splice(idx, 1);
      } else {
        selectedReassemblyIds.value.push(id);
      }
    };

    const isReassemblySelected = (id) => {
      return selectedReassemblyIds.value.includes(id);
    };

    const selectAllReassembly = () => {
      selectedReassemblyIds.value = (pendingReassemblyList.value || []).map(x => x.id);
    };

    const clearSelectedReassembly = () => {
      selectedReassemblyIds.value = [];
    };

    const submitReassemblyDispatch = async () => {
      if (!selectedReassemblyIds.value || selectedReassemblyIds.value.length === 0) {
        triggerToast('Validasi', 'Pilih minimal satu set untuk dirakit ulang!', 'warn');
        return;
      }
      if (!selectedReassemblyOperator.value) {
        triggerToast('Validasi', 'Silakan pilih Operator Assembly terlebih dahulu!', 'warn');
        return;
      }

      isSubmittingReassemblyDispatch.value = true;
      try {
        const res = await fetchAPI('/assembly/dispatch-reassembly', {
          method: 'POST',
          body: JSON.stringify({
            assembly_ids: selectedReassemblyIds.value,
            operator_name: selectedReassemblyOperator.value
          })
        });
        if (res && res.ok) {
          triggerToast('Sukses Dispatched', res.message || 'Set berhasil ditugaskan ke operator assembly untuk perakitan ulang!', 'success');
          selectedReassemblyIds.value = [];
          selectedReassemblyOperator.value = '';
          await loadPendingReassembly();
          await loadTrays();
          await loadAssemblyResults();
        } else {
          triggerToast('Gagal', res?.error || 'Gagal mengirim penugasan perakitan ulang', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message || 'Terjadi kesalahan sistem', 'error');
      } finally {
        isSubmittingReassemblyDispatch.value = false;
      }
    };

    const openReplaceForHoldItem = async (item) => {
      selectedAssemblyItem.value = item;
      selectedHoldItem.value = item;
      if (item && item.tray_id) {
        const foundTray = (traysList.value || []).find(t => t.id === item.tray_id || t.tray_code === item.tray_code);
        selectedAssemblyTray.value = foundTray || { id: item.tray_id, tray_code: item.tray_code };
        selectedHoldTray.value = selectedAssemblyTray.value;
      }
      await openReplacePartSubModal();
    };

    // ===================================================================
    // TAB INITIALIZATION HELPERS
    // ===================================================================
    const initProductionHoldTab = () => {
      if (window.logger) logger.info('HOLDS', 'Memuat data Hasil Perakitan & Hold...');
      loadAssemblyResults();
      loadProductionHoldItems();
      loadProductionProducts();
    };

    const initFunctionalHoldTab = () => {
      if (window.logger) logger.info('HOLDS', 'Memuat data Hasil Test Functional Hold...');
      loadProductionHoldItems();
      loadProductionProducts();
    };

    const initReassemblyTab = () => {
      if (window.logger) logger.info('HOLDS', 'Memuat antrean Perakitan Ulang (RU)...');
      loadPendingReassembly();
    };

    // ===================================================================
    // MODULE EXPORTS
    // ===================================================================
    return {
      initProductionHoldTab,
      initFunctionalHoldTab,
      initReassemblyTab,
      isReassemblySet,
      isTravelerItemAssembled,
      getTravelerItemActualStatus,
      getAssemblyStageStatus,
      getItemStartTime,
      getItemFinishTime,
      assemblyResults,
      isLoadingAssemblyResults,
      assemblyResultsStatusFilter,
      selectedProductionOperator,
      selectedProductionDocAssy,
      productionSearchQuery,
      productionViewMode,
      loadAssemblyResults,
      assemblyResultsSummary,
      distinctAssemblyOperators,
      distinctAssyDocNumbers,
      isAssyDocDropdownOpen,
      assyDocSearchText,
      toggleAssyDocDropdown,
      selectAssyDocFromDropdown,
      filteredAssyDocNumbers,
      calculateAssemblyDuration,
      filteredGroupedAssemblyResults,
      flatFilteredAssemblyResults,
      filteredAssemblyResults,
      pagedAssemblyResults,
      totalPgAssemblyResults,
      productionHoldList,
      productionProductsList,
      selectedProductionProduct,
      productionSourceFilter,
      functionalHoldViewMode,
      isLoadingProductionHold,
      loadProductionProducts,
      loadProductionHoldItems,
      isItemInReassemblyProcess,
      baseProductionHoldList,
      productionHoldCounts,
      filteredProductionHoldList,
      productionHoldCount,
      pagedProductionHoldList,
      totalPgProductionHold,
      isAllProblematicPartsReplaced,
      getHoldProceedButtonLabel,
      getHoldProceedButtonTitle,
      getItemPendingLabel,
      isItemPendingAdmin,
      isFunctionalHoldItem,
      proceedHoldItem,
      showAssemblyDetailModal,
      selectedAssemblyItem,
      selectedAssemblyTray,
      showHoldDetailModal,
      selectedHoldItem,
      selectedHoldTray,
      openAssemblyDetailModal,
      openHoldDetailModal,
      transferSingleSetToWarehouse,
      confirmReturnTrayToWarehouse,
      showReregisterSubModal,
      reregisterTargetStatus,
      reregOperator,
      reregisterReason,
      isSubmittingReregisterSetAction,
      availableReregisterStatuses,
      openReregisterSubModal,
      submitReregisterSetAction,
      showReplacePartModal,
      replaceOldStatus,
      replaceReason,
      isSubmittingReplacePartAction,
      showAllReplaceParts,
      replaceSelections,
      replaceDropdownOpen,
      replaceSearchQueries,
      availableReplacePartsByType,
      isLoadingPartsByType,
      replacePartType,
      replaceNewSN,
      availableReplaceParts,
      isLoadingReplaceParts,
      isReplacePartDropdownOpen,
      replacePartSearchQuery,
      problematicPartTypes,
      isProblemPart,
      hasNonProblematicParts,
      getParsedReplacementHistory,
      getItemProblemReason,
      getFilteredPartsForType,
      filteredReplaceParts,
      selectReplaceSNForPart,
      clearReplaceSNForPart,
      toggleReplaceDropdown,
      closeAllReplaceDropdowns,
      selectReplaceNewSN,
      totalPartsToReplace,
      loadAllAvailableReplaceParts,
      openReregisterForPart,
      closeReplacePartModal,
      openReplacePartSubModal,
      onSelectReplacePartType,
      openReplaceForPart,
      submitReplacePartAction,
      pendingReassemblyList,
      selectedReassemblyIds,
      selectedReassemblyOperator,
      isLoadingPendingReassembly,
      isSubmittingReassemblyDispatch,
      loadPendingReassembly,
      pendingReassemblyCount,
      toggleSelectReassembly,
      isReassemblySelected,
      selectAllReassembly,
      clearSelectedReassembly,
      submitReassemblyDispatch,
      openReplaceForHoldItem
    };
  };
})(window);

