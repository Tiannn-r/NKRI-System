/**
 * Area Perakitan (Assembly) Module
 * Sistem Final Assembly & Traceability
 * 
 * Modularized in Fase 2E - Refactor Arsitektur
 */

(function (root, factory) {
  if (typeof window !== 'undefined') {
    window.createAssemblyModule = factory();
  }
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.createAssemblyModule = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  return function createAssemblyModule(deps) {
    const {
      Vue = window.Vue || {},
      fetchAPI = window.api?.fetchAPI || window.fetchAPI,
      triggerToast = (title, message, type) => window.utils?.triggerToast ? window.utils.triggerToast(title, message, type) : console.log(`[${type}] ${title}: ${message}`),
      showConfirmCard = () => {},
      userRole = { value: 'operator' },
      operatorName = { value: 'Operator' },
      userFullName = { value: 'Operator Perakitan' },
      currentTab = { value: 'assembly' },
      getOperatorFullName = (name) => name || '-',
      getNowTimeStr = () => {
        const d = new Date();
        return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
      },
      getNowLocalDateTimeStr = () => {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
      },
      formatTimeInput = (dt) => {
        if (!dt) return '';
        const d = (dt instanceof Date) ? dt : new Date(dt);
        if (isNaN(d.getTime())) return String(dt);
        return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
      },
      openLabelPreview = () => {},
      openDetailModal = () => {},
      loadAssemblyResults = async () => {},
      loadProductionHoldItems = async () => {},
      loadReturnedTrays = async () => {},
      loadLaporan = async () => {},
      loadMasterData = async () => {},
      liveClock = { value: new Date() }
    } = deps || {};

    const { ref, computed } = Vue;

    // ===================================================================
    // 1. TRAYS STATE & SEARCHABLE DROPDOWN
    // ===================================================================
    const traysList = ref([]);
    const selectedTrayCode = ref('');
    const currentTray = ref(null);
    const currentTrayParts = ref([]);
    const currentTrayAssemblies = ref([]);

    const isTrayDropdownOpen = ref(false);
    const traySearchText = ref('');
    const trayDropdownRef = ref(null);
    const selectedOperatorFilter = ref('');

    const toggleTrayDropdown = () => {
      isTrayDropdownOpen.value = !isTrayDropdownOpen.value;
      if (isTrayDropdownOpen.value) {
        traySearchText.value = '';
      }
    };

    const selectTrayFromDropdown = (code) => {
      selectedTrayCode.value = code;
      isTrayDropdownOpen.value = false;
      loadAssemblyTray();
    };

    const filteredTraysList = computed(() => {
      let list = (traysList.value || []).filter(t => t.session_status !== 'RETURNED_TO_WAREHOUSE' || (t.finished_count && t.finished_count > 0));
      if (currentTab.value === 'assy_testing') {
        list = list.filter(t => (t.good_assembly_count || t.good_count || 0) > 0);
      }
      if (selectedOperatorFilter.value && selectedOperatorFilter.value !== '' && selectedOperatorFilter.value !== 'Semua Operator Assembly') {
        const op = selectedOperatorFilter.value.toLowerCase().trim();
        list = list.filter(t => {
          const assigned = (t.assigned_operator || '').toLowerCase().trim();
          const assignedFull = getOperatorFullName(t.assigned_operator).toLowerCase().trim();
          return assigned === op || assignedFull === op || assigned.includes(op) || assignedFull.includes(op);
        });
      }
      if (traySearchText.value && traySearchText.value.trim()) {
        const q = traySearchText.value.toLowerCase().trim();
        list = list.filter(t =>
          (t.tray_code && t.tray_code.toLowerCase().includes(q)) ||
          (t.assigned_operator && t.assigned_operator.toLowerCase().includes(q)) ||
          (getOperatorFullName(t.assigned_operator).toLowerCase().includes(q)) ||
          (t.source_document_mo && t.source_document_mo.toLowerCase().includes(q))
        );
      }
      return list;
    });

    // ===================================================================
    // 2. IN-PROGRESS STORAGE & PROGRESS TRACKING
    // ===================================================================
    const inProgressMap = ref({});

    const loadSavedProgress = (trayCode) => {
      const code = trayCode || selectedTrayCode.value;
      if (!code) {
        inProgressMap.value = {};
        return;
      }
      try {
        const key = `inProgressMap_${code}`;
        const raw = localStorage.getItem(key);
        if (raw) {
          inProgressMap.value = JSON.parse(raw);
        } else {
          inProgressMap.value = {};
        }
      } catch (_) {
        inProgressMap.value = {};
      }
    };

    const saveProgress = (trayCode) => {
      const code = trayCode || selectedTrayCode.value;
      if (!code) return;
      try {
        const key = `inProgressMap_${code}`;
        if (!inProgressMap.value || Object.keys(inProgressMap.value).length === 0) {
          localStorage.removeItem(key);
        } else {
          localStorage.setItem(key, JSON.stringify(inProgressMap.value));
        }
      } catch (_) { }
    };

    // ===================================================================
    // 3. COMPUTED SLOTS, PARTS AVAILABILITY & LOCKING
    // ===================================================================
    const currentTraySetsCount = computed(() => {
      const assyCount = (currentTrayAssemblies.value || []).length;
      if (currentTray.value && (currentTray.value.session_status === 'COMPLETED_SHIFT' || currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE')) {
        return assyCount;
      }
      const maxAssySet = (currentTrayAssemblies.value || []).reduce((max, a) => Math.max(max, Number(a.set_number) || 0), 0);
      const p15Count = (currentTrayParts.value || []).filter(p => p.part_type === '15').length;
      const p1Count = (currentTrayParts.value || []).filter(p => p.part_type === '1').length;
      const p12Count = (currentTrayParts.value || []).filter(p => p.part_type === '12').length;
      const partsCount = Math.max(p15Count, p1Count, p12Count, 0);
      return Math.max(assyCount, maxAssySet, partsCount, 0);
    });

    const finishedSlotsCount = computed(() => {
      if (!traySlots.value || traySlots.value.length === 0) {
        return (currentTrayAssemblies.value || []).length;
      }
      return traySlots.value.filter(s => s && (s.finished_at || (s.status && !['TERSUPPLY', 'Dikembalikan ke Gudang', 'RETURNED'].includes(s.status)))).length;
    });

    const isTrayFullyFinished = computed(() => {
      return currentTraySetsCount.value > 0 && finishedSlotsCount.value >= currentTraySetsCount.value;
    });

    const hasHoldItem = computed(() => {
      return (currentTrayAssemblies.value || []).some(a => a.status === 'Hold');
    });

    const isTrayLocked = computed(() => {
      if (currentTray.value && (currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE' || currentTray.value.session_status === 'COMPLETED_SHIFT')) return true;
      return isTrayFullyFinished.value && !hasHoldItem.value;
    });

    const firstUnfinishedIndex = computed(() => {
      if (currentTray.value && currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE') {
        return currentTraySetsCount.value;
      }
      const finishedSetNums = new Set((currentTrayAssemblies.value || []).map(a => a.set_number));
      for (let i = 1; i <= currentTraySetsCount.value; i++) {
        if (!finishedSetNums.has(i)) return i - 1;
      }
      return currentTraySetsCount.value;
    });

    const traySlots = computed(() => {
      const count = currentTraySetsCount.value || 0;
      const slots = [];
      const assyMap = {};
      (currentTrayAssemblies.value || []).forEach(a => {
        if (a.set_number && (!assyMap[a.set_number] || (a.id && (!assyMap[a.set_number].id || a.id > assyMap[a.set_number].id)))) {
          assyMap[a.set_number] = a;
        }
      });

      // Collect parts that are already assembled
      const usedP15 = new Set();
      const usedP1 = new Set();
      const usedP12 = new Set();
      (currentTrayAssemblies.value || []).forEach(a => {
        if (a.part15_sn) usedP15.add(a.part15_sn);
        if (a.part1_sn) usedP1.add(a.part1_sn);
        if (a.part12_sn) usedP12.add(a.part12_sn);
      });

      // Also account for parts in-progress
      Object.entries(inProgressMap.value).forEach(([sNumStr, prog]) => {
        if (prog.part15) usedP15.add(prog.part15);
        if (prog.part1) usedP1.add(prog.part1);
        if (prog.part12) usedP12.add(prog.part12);
      });

      // Available unused parts in tray
      const availP15 = (currentTrayParts.value || []).filter(p => p.part_type === '15' && !usedP15.has(p.sn));
      const availP1 = (currentTrayParts.value || []).filter(p => p.part_type === '1' && !usedP1.has(p.sn));
      const availP12 = (currentTrayParts.value || []).filter(p => p.part_type === '12' && !usedP12.has(p.sn));

      let idxP15 = 0;
      let idxP1 = 0;
      let idxP12 = 0;

      for (let i = 0; i < count; i++) {
        const setNum = i + 1;
        const assy = assyMap[setNum];
        if (assy) {
          slots.push(assy);
        } else {
          if (currentTray.value && (currentTray.value.session_status === 'COMPLETED_SHIFT' || currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE')) {
            continue;
          }
          const prog = inProgressMap.value[setNum];

          let p15Val = '';
          let isReturned15 = false;
          if (prog && prog.part15) {
            p15Val = prog.part15;
          } else if (idxP15 < availP15.length) {
            p15Val = availP15[idxP15].sn;
            isReturned15 = availP15[idxP15].status === 'RETURNED';
            idxP15++;
          }

          let p1Val = '';
          if (prog && prog.part1) {
            p1Val = prog.part1;
          } else if (idxP1 < availP1.length) {
            p1Val = availP1[idxP1].sn;
            idxP1++;
          }

          let p12Val = '';
          if (prog && prog.part12) {
            p12Val = prog.part12;
          } else if (idxP12 < availP12.length) {
            p12Val = availP12[idxP12].sn;
            idxP12++;
          }

          const isReturned = (currentTray.value && currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE') || isReturned15;

          slots.push({
            set_number: setNum,
            part15_sn: p15Val,
            part1_sn: p1Val,
            part12_sn: p12Val,
            started_at: prog ? prog.started_at : null,
            finished_at: null,
            status: isReturned ? 'RETURNED' : null
          });
        }
      }
      return slots;
    });

    const isSlotLocked = (index) => {
      if (index > firstUnfinishedIndex.value) return true;
      return false;
    };

    const canEditSlot = (slot) => {
      if (currentTray.value && (currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE' || currentTray.value.session_status === 'COMPLETED_SHIFT')) return false;
      if (!isTrayFullyFinished.value) return true;
      return slot.status === 'Hold';
    };

    const isStatusGood = (status) => {
      return (status || '').toLowerCase() === 'good';
    };

    const availablePartsByType = (type) => {
      if (!currentTrayParts.value || !currentTrayParts.value.length) return [];

      const assembledSNs = new Set();
      const currentSlot = activeModalIndex.value !== null ? traySlots.value[activeModalIndex.value] : null;

      (currentTrayAssemblies.value || []).forEach(a => {
        if (currentSlot && currentSlot.id && a.id === currentSlot.id) return;
        if (a.part15_sn) assembledSNs.add(a.part15_sn);
        if (a.part12_sn) assembledSNs.add(a.part12_sn);
        if (a.part1_sn) assembledSNs.add(a.part1_sn);
      });

      Object.entries(inProgressMap.value).forEach(([setNumStr, prog]) => {
        const sNum = parseInt(setNumStr, 10);
        if (activeModalIndex.value !== null && sNum === (activeModalIndex.value + 1)) return;
        if (prog.part15) assembledSNs.add(prog.part15);
        if (prog.part1) assembledSNs.add(prog.part1);
        if (prog.part12) assembledSNs.add(prog.part12);
      });

      return (currentTrayParts.value || []).filter(p => p.part_type === type && !assembledSNs.has(p.sn));
    };

    // ===================================================================
    // 4. DATA LOADING & AUTO-POLLING
    // ===================================================================
    const loadTrays = async () => {
      try {
        let url = '/assembly/trays';
        if (selectedOperatorFilter.value && (userRole.value === 'gudang' || userRole.value === 'admin' || userRole.value === 'Super Admin' || String(userRole.value || '').toLowerCase().includes('admin'))) {
          url += `?operator=${encodeURIComponent(selectedOperatorFilter.value)}`;
        }
        const json = await fetchAPI(url);
        if (json.ok) {
          const items = json.data || [];
          items.sort((a, b) => (a.tray_code || '').localeCompare(b.tray_code || '', undefined, { numeric: true, sensitivity: 'base' }));
          traysList.value = items;

          if (currentTab.value === 'assembly' || currentTab.value === 'assy_testing') {
            const list = filteredTraysList.value || [];
            if (list.length > 0) {
              const savedCode = localStorage.getItem('selectedTrayCode');
              const exists = savedCode && list.some(t => t.tray_code === savedCode);
              if (exists) {
                selectedTrayCode.value = savedCode;
              } else if (!selectedTrayCode.value || !list.some(t => t.tray_code === selectedTrayCode.value)) {
                selectedTrayCode.value = list[0].tray_code;
              }
              await loadAssemblyTray();
            } else {
              selectedTrayCode.value = '';
              currentTray.value = null;
              currentTrayParts.value = [];
              currentTrayAssemblies.value = [];
            }
          }
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const loadAssemblyTray = async () => {
      if (!selectedTrayCode.value) return;
      localStorage.setItem('selectedTrayCode', selectedTrayCode.value);
      loadSavedProgress(selectedTrayCode.value);
      try {
        const json = await fetchAPI(`/assembly/tray?code=${encodeURIComponent(selectedTrayCode.value)}`);
        if (json.ok) {
          currentTray.value = json.data.tray;
          currentTrayParts.value = json.data.parts;
          currentTrayAssemblies.value = json.data.assemblies;

          // Sync active downtime (pause)
          if (json.data.active_downtime) {
            isWorkstationPaused.value = json.data.active_downtime.is_paused;
            activePauseReason.value = json.data.active_downtime.reason || 'Jeda Operasional';
            activeDowntimeId.value = json.data.active_downtime.downtime_id;
            pausedDurationSeconds.value = json.data.active_downtime.elapsed_seconds || 0;
            if (json.data.active_downtime.paused_at) {
              pausedStartTime.value = new Date(json.data.active_downtime.paused_at);
            }
          } else {
            isWorkstationPaused.value = false;
            activePauseReason.value = '';
            activeDowntimeId.value = null;
            pausedDurationSeconds.value = 0;
            pausedStartTime.value = null;
          }

          // Sync active in-progress from server
          if (json.data.in_progress) {
            const inp = json.data.in_progress;
            inProgressMap.value[inp.set_number] = {
              started_at: inp.started_at,
              part15: inp.part15 || '',
              part1: inp.part1 || '',
              part12: inp.part12 || '',
              operator: inp.operator || ''
            };
          } else if (userRole.value === 'gudang' || (currentTray.value && currentTray.value.session_status === 'COMPLETED_SHIFT')) {
            inProgressMap.value = {};
            if (selectedTrayCode.value) saveProgress(selectedTrayCode.value);
          }

          // Ensure already finished assemblies in DB are removed from inProgressMap
          if (json.data.assemblies && json.data.assemblies.length) {
            let changed = false;
            json.data.assemblies.forEach(a => {
              if (inProgressMap.value[a.set_number]) {
                delete inProgressMap.value[a.set_number];
                changed = true;
              }
            });
            if (changed) {
              saveProgress(selectedTrayCode.value);
            }
          }
        } else {
          triggerToast('Info', json.error || 'Tray tidak dapat diakses.', 'warn');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    let assemblyPollTimer = null;
    const startAssemblyAutoPoll = () => {
      if (assemblyPollTimer) clearInterval(assemblyPollTimer);
      assemblyPollTimer = setInterval(() => {
        if (currentTab.value === 'assembly' && selectedTrayCode.value && activeModalIndex.value === null && !showPauseModal.value && !showSessionModal.value) {
          fetchAPI(`/assembly/tray?code=${encodeURIComponent(selectedTrayCode.value)}`)
            .then(json => {
              if (json && json.ok && json.data) {
                currentTray.value = json.data.tray;
                currentTrayParts.value = json.data.parts;
                currentTrayAssemblies.value = json.data.assemblies;

                if (json.data.active_downtime) {
                  isWorkstationPaused.value = json.data.active_downtime.is_paused;
                  activePauseReason.value = json.data.active_downtime.reason || 'Jeda Operasional';
                  activeDowntimeId.value = json.data.active_downtime.downtime_id;
                  pausedDurationSeconds.value = json.data.active_downtime.elapsed_seconds || 0;
                  if (json.data.active_downtime.paused_at) {
                    pausedStartTime.value = new Date(json.data.active_downtime.paused_at);
                  }
                } else {
                  isWorkstationPaused.value = false;
                  activePauseReason.value = '';
                  activeDowntimeId.value = null;
                  pausedDurationSeconds.value = 0;
                  pausedStartTime.value = null;
                }

                if (json.data.in_progress) {
                  const inp = json.data.in_progress;
                  inProgressMap.value[inp.set_number] = {
                    started_at: inp.started_at,
                    part15: inp.part15 || '',
                    part1: inp.part1 || '',
                    part12: inp.part12 || '',
                    operator: inp.operator || ''
                  };
                } else if (userRole.value === 'gudang') {
                  inProgressMap.value = {};
                }
              }
            })
            .catch(() => { });
        }
      }, 3500);
    };

    // ===================================================================
    // 5. ASSEMBLY FORM & MODAL ACTIONS
    // ===================================================================
    const activeModalIndex = ref(null);
    const assyForm = ref({
      part15: '',
      part1: '',
      part12: '',
      startTime: '',
      endTime: '',
      started_at: null,
      status: 'Good',
      reason: ''
    });

    const setStartTimeNow = () => {
      assyForm.value.startTime = getNowTimeStr();
    };

    const setEndTimeNow = () => {
      assyForm.value.endTime = getNowTimeStr();
    };

    const captureStartTime = () => {
      assyForm.value.startTime = getNowTimeStr();
    };

    const startAssemblyNow = async () => {
      if (!assyForm.value.part15?.trim() || !assyForm.value.part1?.trim() || !assyForm.value.part12?.trim()) {
        triggerToast('Validasi Part', 'Harap pilih Part 15, Part 1, dan Part 12 terlebih dahulu sebelum menekan Mulai!', 'warn');
        return;
      }
      const now = getNowLocalDateTimeStr();
      assyForm.value.started_at = now;
      if (activeModalIndex.value !== null) {
        const setNum = activeModalIndex.value + 1;
        inProgressMap.value[setNum] = {
          started_at: now,
          part15: assyForm.value.part15.trim(),
          part1: assyForm.value.part1.trim(),
          part12: assyForm.value.part12.trim(),
          status: assyForm.value.status || '',
          defective_parts: assyForm.value.defective_parts || [],
          reason: assyForm.value.reason ? assyForm.value.reason.trim() : ''
        };
        saveProgress(selectedTrayCode.value);

        if (currentTray.value) {
          fetchAPI('/assembly/start-set', {
            method: 'POST',
            body: JSON.stringify({
              tray_id: currentTray.value.id,
              tray_code: currentTray.value.tray_code,
              set_number: setNum,
              operator: userFullName.value || operatorName.value,
              part15: assyForm.value.part15.trim(),
              part1: assyForm.value.part1.trim(),
              part12: assyForm.value.part12.trim(),
              started_at: now
            })
          }).catch(() => { });
        }
      }
      triggerToast('Pengerjaan Dimulai', 'Timer pengerjaan ON dan form part terkunci. Silakan tentukan status hasil perakitan.', 'success');
    };

    const openAssemblyModal = (index) => {
      if (userRole.value === 'gudang' || userRole.value === 'qc') {
        triggerToast('Akses Terbatas', (userRole.value === 'qc' ? 'Inspector QC' : 'Petugas Gudang') + ' hanya memiliki hak akses untuk memantau (Read Only).', 'info');
        return;
      }
      const slot = traySlots.value[index];
      if (slot && (slot.status || slot.finished_at)) {
        triggerToast('Set Terkunci', `Set #${index + 1} sudah selesai dirakit (${slot.status || 'Selesai'}) dan terkunci permanen. Proses tidak dapat diulang.`, 'warn');
        openDetailModal(slot, index);
        return;
      }
      if (slot && slot.status === 'RETURNED') {
        triggerToast('Selesai Dikembalikan', 'Set ini telah dikembalikan ke Gudang dan tidak dapat dirakit.', 'warn');
        return;
      }
      if (currentTray.value && currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE' && (!slot || !slot.id)) {
        triggerToast('Sesi Ditutup', 'Tray ini telah diselesaikan dan sisa set telah dikembalikan ke Gudang.', 'warn');
        return;
      }
      if (isSlotLocked(index)) {
        triggerToast('Slot Terkunci', `Harap selesaikan perakitan Set #${firstUnfinishedIndex.value + 1} terlebih dahulu!`, 'error');
        return;
      }

      activeModalIndex.value = index;
      const nowStr = getNowTimeStr();
      const setNum = index + 1;
      const prog = inProgressMap.value[setNum];

      if (prog) {
        assyForm.value = {
          part15: prog.part15 || '',
          part1: prog.part1 || '',
          part12: prog.part12 || '',
          startTime: formatTimeInput(prog.started_at),
          endTime: nowStr,
          started_at: prog.started_at,
          status: prog.status || '',
          defective_parts: prog.defective_parts || [],
          reason: prog.reason || ''
        };
      } else {
        const slot = traySlots.value[index];
        const avail15 = availablePartsByType('15');
        const avail1 = availablePartsByType('1');
        const avail12 = availablePartsByType('12');

        const defaultP15 = (slot && slot.part15_sn && slot.part15_sn !== '-') ? slot.part15_sn : (avail15.length ? avail15[0].sn : '');
        const defaultP1 = (slot && slot.part1_sn && slot.part1_sn !== '-') ? slot.part1_sn : (avail1.length ? avail1[0].sn : '');
        const defaultP12 = (slot && slot.part12_sn && slot.part12_sn !== '-') ? slot.part12_sn : (avail12.length ? avail12[0].sn : '');

        assyForm.value = {
          part15: defaultP15,
          part1: defaultP1,
          part12: defaultP12,
          startTime: nowStr,
          endTime: nowStr,
          started_at: null,
          status: '',
          defective_parts: [],
          reason: ''
        };
      }
    };

    const closeModal = () => {
      if (activeModalIndex.value !== null && assyForm.value.started_at) {
        const setNum = activeModalIndex.value + 1;
        inProgressMap.value[setNum] = {
          started_at: assyForm.value.started_at,
          part15: assyForm.value.part15 ? assyForm.value.part15.trim() : '',
          part1: assyForm.value.part1 ? assyForm.value.part1.trim() : '',
          part12: assyForm.value.part12 ? assyForm.value.part12.trim() : '',
          status: assyForm.value.status || '',
          defective_parts: assyForm.value.defective_parts || [],
          reason: assyForm.value.reason ? assyForm.value.reason.trim() : ''
        };
        saveProgress(selectedTrayCode.value);
      }
      activeModalIndex.value = null;
    };

    const saveAssemblyItem = async (andPrint = false) => {
      if (userRole.value === 'gudang') {
        triggerToast('Akses Ditolak', 'Petugas Gudang tidak diizinkan menyimpan hasil rakitan.', 'error');
        return;
      }
      if (!assyForm.value.part15.trim() || !assyForm.value.part1.trim() || !assyForm.value.part12.trim()) {
        triggerToast('Validasi Gagal', 'Semua 3 Part (Part 15, Part 1, Part 12) wajib dipilih!', 'error');
        return;
      }

      if (!assyForm.value.status) {
        triggerToast('Status Belum Dipilih', 'Harap pilih salah satu Status Hasil Assembly (Good, Scrap, atau Hold)!', 'warn');
        return;
      }

      if (assyForm.value.status !== 'Good') {
        if (!assyForm.value.defective_parts || !assyForm.value.defective_parts.length) {
          triggerToast('Pilih Part Cacat', 'Harap centang minimal satu part (Part 15, Part 1, atau Part 12) yang bermasalah!', 'warn');
          return;
        }
        if (!assyForm.value.reason?.trim()) {
          triggerToast('Alasan Diperlukan', `Harap isi alasan mengapa part di-${assyForm.value.status.toLowerCase()}!`, 'warn');
          return;
        }
      }

      const existingSlot = traySlots.value[activeModalIndex.value];
      const setNum = activeModalIndex.value + 1;
      const payload = {
        tray_id: currentTray.value.id,
        set_number: setNum,
        operator: userFullName.value || operatorName.value,
        part15: assyForm.value.part15.trim(),
        part15_sn: assyForm.value.part15.trim(),
        part12: assyForm.value.part12.trim(),
        part12_sn: assyForm.value.part12.trim(),
        part1: assyForm.value.part1.trim(),
        part1_sn: assyForm.value.part1.trim(),
        status: assyForm.value.status,
        defective_parts: assyForm.value.status !== 'Good' ? assyForm.value.defective_parts : [],
        reason: assyForm.value.reason ? assyForm.value.reason.trim() : null,
        started_at: assyForm.value.started_at || getNowLocalDateTimeStr(),
        finished_at: getNowLocalDateTimeStr(),
        is_edit: !!existingSlot.id,
        assembly_id: existingSlot.id || null,
        old_sns: existingSlot.id ? [existingSlot.part15_sn, existingSlot.part12_sn, existingSlot.part1_sn] : null
      };

      try {
        const json = await fetchAPI('/assembly/save', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (json.ok) {
          delete inProgressMap.value[setNum];
          saveProgress(selectedTrayCode.value);
          triggerToast('Berhasil', `Set #${setNum} berhasil disimpan (Status: ${assyForm.value.status})!`, 'success');
          const savedSN = { ...assyForm.value, set_number: setNum, pic_assembly: userFullName.value || operatorName.value, finished_at: new Date() };
          const activeAssemblyCode = selectedTrayCode.value;
          
          // Reset modal index & started_at before closeModal agar tidak tersimpan ulang ke inProgressMap
          activeModalIndex.value = null;
          assyForm.value.started_at = null;
          closeModal();

          // Reset assyForm ke kondisi bersih
          assyForm.value = {
            part15: '',
            part1: '',
            part12: '',
            startTime: '',
            endTime: '',
            started_at: null,
            status: '',
            defective_parts: [],
            reason: ''
          };

          if (json.data) {
            const updatedAssy = json.data;
            const existingIdx = (currentTrayAssemblies.value || []).findIndex(a => a.set_number === setNum);
            if (existingIdx >= 0) {
              currentTrayAssemblies.value[existingIdx] = updatedAssy;
            } else {
              currentTrayAssemblies.value.push(updatedAssy);
            }
          }

          const currentTrayItem = (traysList.value || []).find(t => t.tray_code === activeAssemblyCode);
          if (currentTrayItem) {
            const totalFin = (currentTrayAssemblies.value || []).filter(a => a.finished_at || a.status).length;
            currentTrayItem.finished_count = totalFin;
            currentTrayItem.total_sets = currentTraySetsCount.value || currentTrayItem.total_sets;
            if (totalFin >= (currentTrayItem.total_sets || currentTraySetsCount.value || 1)) {
              currentTrayItem.session_status = 'COMPLETED';
              if (currentTray.value) currentTray.value.session_status = 'COMPLETED';
            }
          }

          await loadAssemblyTray();
          await loadTrays();
          if (activeAssemblyCode && selectedTrayCode.value !== activeAssemblyCode) {
            selectedTrayCode.value = activeAssemblyCode;
            await loadAssemblyTray();
          }
          loadAssemblyResults();
          loadProductionHoldItems();

          if (andPrint && isStatusGood(savedSN.status)) {
            openLabelPreview(savedSN);
          }
        } else {
          triggerToast('Gagal Simpan', json.error || 'Terjadi kesalahan sistem.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // ===================================================================
    // 6. QUICK STATUS & QUICK FINISH MODAL
    // ===================================================================
    const showQuickStatusModal = ref(false);
    const quickStatusData = ref({
      index: null,
      set_number: null,
      part15: '',
      part1: '',
      part12: '',
      status: '',
      defective_parts: [],
      reason: ''
    });

    const quickFinishSlot = (index) => {
      if (userRole.value === 'gudang') {
        triggerToast('Akses Terbatas', 'Petugas Gudang tidak memiliki hak untuk melakukan perakitan.', 'warn');
        return;
      }
      const slot = traySlots.value[index];
      if (!slot) return;
      if (slot.status || slot.finished_at) {
        triggerToast('Set Terkunci', `Set #${index + 1} sudah selesai dirakit (${slot.status || 'Selesai'}) dan terkunci permanen.`, 'warn');
        return;
      }

      const setNum = index + 1;
      const prog = inProgressMap.value[setNum] || {};
      let p15 = slot.part15_sn || prog.part15 || '';
      let p1 = slot.part1_sn || prog.part1 || '';
      let p12 = slot.part12_sn || prog.part12 || '';

      if (!p15 || !p1 || !p12) {
        const avail15 = availablePartsByType('15');
        const avail1 = availablePartsByType('1');
        const avail12 = availablePartsByType('12');
        if (!p15 && avail15.length) p15 = avail15[0].sn;
        if (!p1 && avail1.length) p1 = avail1[0].sn;
        if (!p12 && avail12.length) p12 = avail12[0].sn;
      }

      if (!p15 || !p1 || !p12) {
        openAssemblyModal(index);
        triggerToast('Lengkapi Part', 'Harap pastikan semua part (Part 15, Part 1, Part 12) telah dipilih!', 'warn');
        return;
      }

      const currentStatus = prog.status || slot.status || '';
      const defParts = prog.defective_parts || (slot.defective_parts ? (Array.isArray(slot.defective_parts) ? slot.defective_parts : [slot.defective_parts]) : []);
      const rsn = prog.reason || slot.reason || '';

      quickStatusData.value = {
        index: index,
        set_number: setNum,
        part15: p15,
        part1: p1,
        part12: p12,
        status: currentStatus || '',
        defective_parts: defParts,
        reason: rsn
      };
      showQuickStatusModal.value = true;
    };

    const submitQuickStatusModal = async () => {
      if (!quickStatusData.value.status) {
        triggerToast('Pilih Status', 'Harap pilih salah satu status hasil perakitan!', 'warn');
        return;
      }
      if (quickStatusData.value.status !== 'Good') {
        if (!quickStatusData.value.defective_parts || !quickStatusData.value.defective_parts.length) {
          triggerToast('Pilih Part Cacat', 'Harap centang minimal satu part yang bermasalah!', 'warn');
          return;
        }
        if (!quickStatusData.value.reason?.trim()) {
          triggerToast('Alasan Diperlukan', `Harap isi alasan status ${quickStatusData.value.status}!`, 'warn');
          return;
        }
      }
      const idx = quickStatusData.value.index;
      const st = quickStatusData.value.status;
      const rsn = quickStatusData.value.reason;
      const defParts = quickStatusData.value.defective_parts || [];
      showQuickStatusModal.value = false;
      await submitQuickFinish(idx, st, rsn, defParts);
    };

    const submitQuickFinish = async (index, statusVal, reasonVal, defectivePartsVal = []) => {
      if (userRole.value === 'gudang') {
        triggerToast('Akses Ditolak', 'Petugas Gudang tidak diizinkan menyelesaikan perakitan.', 'error');
        return;
      }
      const slot = traySlots.value[index];
      const setNum = index + 1;
      const prog = inProgressMap.value[setNum] || {};
      let p15 = slot.part15_sn || prog.part15 || '';
      let p1 = slot.part1_sn || prog.part1 || '';
      let p12 = slot.part12_sn || prog.part12 || '';

      if (!p15 || !p1 || !p12) {
        const avail15 = availablePartsByType('15');
        const avail1 = availablePartsByType('1');
        const avail12 = availablePartsByType('12');
        if (!p15 && avail15.length) p15 = avail15[0].sn;
        if (!p1 && avail1.length) p1 = avail1[0].sn;
        if (!p12 && avail12.length) p12 = avail12[0].sn;
      }

      const payload = {
        tray_id: currentTray.value.id,
        set_number: setNum,
        operator: userFullName.value || operatorName.value,
        part15: p15.trim(),
        part15_sn: p15.trim(),
        part12: p12.trim(),
        part12_sn: p12.trim(),
        part1: p1.trim(),
        part1_sn: p1.trim(),
        status: statusVal,
        defective_parts: statusVal !== 'Good' ? defectivePartsVal : [],
        reason: reasonVal ? reasonVal.trim() : null,
        started_at: slot.started_at || prog.started_at || getNowLocalDateTimeStr(),
        finished_at: getNowLocalDateTimeStr(),
        is_edit: !!slot.id,
        assembly_id: slot.id || null,
        old_sns: slot.id ? [slot.part15_sn, slot.part12_sn, slot.part1_sn] : null
      };

      try {
        const json = await fetchAPI('/assembly/save', {
          method: 'POST',
          body: JSON.stringify(payload)
        });

        if (json.ok) {
          delete inProgressMap.value[setNum];
          saveProgress(selectedTrayCode.value);
          triggerToast('Selesai', `Set #${setNum} berhasil diselesaikan (Status: ${statusVal})!`, 'success');
          const activeAssemblyCode = selectedTrayCode.value;

          if (json.data) {
            const updatedAssy = json.data;
            const existingIdx = (currentTrayAssemblies.value || []).findIndex(a => a.set_number === setNum);
            if (existingIdx >= 0) {
              currentTrayAssemblies.value[existingIdx] = updatedAssy;
            } else {
              currentTrayAssemblies.value.push(updatedAssy);
            }
          }

          const currentTrayItem = (traysList.value || []).find(t => t.tray_code === activeAssemblyCode);
          if (currentTrayItem) {
            const totalFin = (currentTrayAssemblies.value || []).filter(a => a.finished_at || a.status).length;
            currentTrayItem.finished_count = totalFin;
            currentTrayItem.total_sets = currentTraySetsCount.value || currentTrayItem.total_sets;
            if (totalFin >= (currentTrayItem.total_sets || currentTraySetsCount.value || 1)) {
              currentTrayItem.session_status = 'COMPLETED';
              if (currentTray.value) currentTray.value.session_status = 'COMPLETED';
            }
          }

          await loadAssemblyTray();
          await loadTrays();
          if (activeAssemblyCode && selectedTrayCode.value !== activeAssemblyCode) {
            selectedTrayCode.value = activeAssemblyCode;
            await loadAssemblyTray();
          }
          loadAssemblyResults();
          loadProductionHoldItems();
        } else {
          triggerToast('Gagal Simpan', json.error || 'Terjadi kesalahan sistem.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // ===================================================================
    // 7. WORKSTATION PAUSE / DOWNTIME STATE & ACTIONS
    // ===================================================================
    const showPauseModal = ref(false);
    const pauseForm = ref({
      reason: 'Istirahat / Sholat / Toilet',
      estimatedDuration: '',
      customReason: ''
    });
    const isWorkstationPaused = ref(false);
    const activePauseReason = ref('');
    const activeDowntimeId = ref(null);
    const pausedStartTime = ref(null);
    const pausedDurationSeconds = ref(0);

    const pausedDurationDisplay = computed(() => {
      const sec = pausedDurationSeconds.value || 0;
      const mins = Math.floor(sec / 60);
      const remSec = sec % 60;
      return `${String(mins).padStart(2, '0')}:${String(remSec).padStart(2, '0')}`;
    });

    const openPauseModal = () => {
      if (!currentTray.value) {
        triggerToast('Peringatan', 'Pilih Tray aktif terlebih dahulu sebelum jeda!', 'warn');
        return;
      }
      pauseForm.value = {
        reason: 'Istirahat / Sholat / Toilet',
        estimatedDuration: '',
        customReason: ''
      };
      showPauseModal.value = true;
    };

    const submitPauseAssembly = async () => {
      if (!currentTray.value) return;
      try {
        let finalReason = pauseForm.value.reason;
        if (finalReason === 'Lainnya' && pauseForm.value.customReason) {
          finalReason = pauseForm.value.customReason.trim();
        }

        const json = await fetchAPI('/assembly/pause', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: currentTray.value.id,
            tray_code: currentTray.value.tray_code,
            set_number: (firstUnfinishedIndex.value || 0) + 1,
            reason: finalReason,
            operator: userFullName.value || operatorName.value
          })
        });

        if (json.ok) {
          isWorkstationPaused.value = true;
          activePauseReason.value = finalReason;
          activeDowntimeId.value = json.downtime_id;
          pausedStartTime.value = new Date();
          pausedDurationSeconds.value = 0;
          showPauseModal.value = false;
          triggerToast('Pengerjaan Dijeda', 'Waktu jeda pengerjaan mulai dihitung.', 'info');
        } else {
          triggerToast('Gagal', json.error || 'Gagal menjeda perakitan.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const resumeAssemblyWork = async () => {
      try {
        const pauseStart = pausedStartTime.value ? pausedStartTime.value.getTime() : null;
        const nowMs = new Date().getTime();
        const pauseDeltaMs = pauseStart ? Math.max(0, nowMs - pauseStart) : 0;

        const json = await fetchAPI('/assembly/resume', {
          method: 'POST',
          body: JSON.stringify({
            downtime_id: activeDowntimeId.value,
            tray_id: currentTray.value ? currentTray.value.id : null,
            tray_code: selectedTrayCode.value || (currentTray.value ? currentTray.value.tray_code : '')
          })
        });

        if (json.ok) {
          isWorkstationPaused.value = false;
          activeDowntimeId.value = null;
          pausedStartTime.value = null;
          pausedDurationSeconds.value = 0;

          liveClock.value = new Date();

          if (pauseDeltaMs > 0 && selectedTrayCode.value) {
            Object.keys(inProgressMap.value).forEach(setNum => {
              const prog = inProgressMap.value[setNum];
              if (prog && prog.started_at) {
                const origStart = new Date(prog.started_at).getTime();
                if (!isNaN(origStart)) {
                  const newStart = new Date(origStart + pauseDeltaMs);
                  const pad = (n) => String(n).padStart(2, '0');
                  prog.started_at = `${newStart.getFullYear()}-${pad(newStart.getMonth() + 1)}-${pad(newStart.getDate())} ${pad(newStart.getHours())}:${pad(newStart.getMinutes())}:${pad(newStart.getSeconds())}`;
                }
              }
            });
            saveProgress(selectedTrayCode.value);
          }

          if (activeModalIndex.value !== null && assyForm.value.started_at && pauseDeltaMs > 0) {
            const origStart = new Date(assyForm.value.started_at).getTime();
            if (!isNaN(origStart)) {
              const newStart = new Date(origStart + pauseDeltaMs);
              const pad = (n) => String(n).padStart(2, '0');
              assyForm.value.started_at = `${newStart.getFullYear()}-${pad(newStart.getMonth() + 1)}-${pad(newStart.getDate())} ${pad(newStart.getHours())}:${pad(newStart.getMinutes())}:${pad(newStart.getSeconds())}`;
            }
          }

          triggerToast('Dilanjutkan', 'Pengerjaan perakitan berhasil dilanjutkan dengan jam aktual.', 'success');
        } else {
          triggerToast('Gagal', json.error || 'Gagal melanjutkan perakitan.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // Live interval for pause duration ticker
    setInterval(() => {
      if (isWorkstationPaused.value && pausedStartTime.value) {
        const elapsed = Math.floor((new Date().getTime() - pausedStartTime.value.getTime()) / 1000);
        pausedDurationSeconds.value = Math.max(0, elapsed);
      }
    }, 1000);

    // ===================================================================
    // 8. ASSEMBLY SESSION ACTIONS (RETURN, HANDOVER)
    // ===================================================================
    const showSessionModal = ref(false);
    const sessionForm = ref({
      action: 'RETURN_WAREHOUSE',
      reason: '',
      new_operator: ''
    });

    const openSessionModal = () => {
      if (!currentTray.value) {
        triggerToast('Peringatan', 'Pilih Tray aktif terlebih dahulu!', 'warn');
        return;
      }
      sessionForm.value = {
        action: 'RETURN_WAREHOUSE',
        reason: '',
        new_operator: ''
      };
      showSessionModal.value = true;
    };

    const openReturnToWarehouseFromAssembly = () => {
      openSessionModal();
    };

    const submitSessionAction = async () => {
      if (!currentTray.value) return;
      try {
        if (sessionForm.value.action === 'HANDOVER_SHIFT' && !sessionForm.value.new_operator) {
          triggerToast('Perhatian', 'Harap pilih operator assembly penerima shift!', 'warn');
          return;
        }

        const codeToClear = currentTray.value.tray_code;

        const json = await fetchAPI('/assembly/session-action', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: currentTray.value.id,
            tray_code: currentTray.value.tray_code,
            action: sessionForm.value.action,
            reason: sessionForm.value.reason,
            new_operator: sessionForm.value.new_operator
          })
        });

        if (json.ok) {
          triggerToast('Sesi Selesai', json.message || 'Sesi pengerjaan berhasil diselesaikan.', 'success');
          showSessionModal.value = false;

          if (codeToClear) {
            localStorage.removeItem(`inProgressMap_${codeToClear}`);
            inProgressMap.value = {};
          }

          await loadTrays();
          await loadReturnedTrays();
          await loadLaporan();
          await loadMasterData();

          if (codeToClear && filteredTraysList.value.some(t => t.tray_code === codeToClear)) {
            selectedTrayCode.value = codeToClear;
            await loadAssemblyTray();
          } else if (filteredTraysList.value.length > 0) {
            selectedTrayCode.value = filteredTraysList.value[0].tray_code;
            await loadAssemblyTray();
          } else {
            selectedTrayCode.value = '';
            currentTray.value = null;
            currentTrayParts.value = [];
            currentTrayAssemblies.value = [];
          }
        } else {
          triggerToast('Gagal', json.error || 'Gagal memproses aksi sesi.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // Return entire module state and methods
    return {
      traysList,
      selectedTrayCode,
      currentTray,
      currentTrayParts,
      currentTrayAssemblies,
      isTrayDropdownOpen,
      traySearchText,
      trayDropdownRef,
      selectedOperatorFilter,
      toggleTrayDropdown,
      selectTrayFromDropdown,
      filteredTraysList,
      inProgressMap,
      loadSavedProgress,
      saveProgress,
      currentTraySetsCount,
      finishedSlotsCount,
      isTrayFullyFinished,
      hasHoldItem,
      isTrayLocked,
      firstUnfinishedIndex,
      traySlots,
      isSlotLocked,
      canEditSlot,
      isStatusGood,
      availablePartsByType,
      loadTrays,
      loadAssemblyTray,
      assemblyPollTimer,
      startAssemblyAutoPoll,
      activeModalIndex,
      assyForm,
      setStartTimeNow,
      setEndTimeNow,
      captureStartTime,
      startAssemblyNow,
      openAssemblyModal,
      closeModal,
      saveAssemblyItem,
      showQuickStatusModal,
      quickStatusData,
      quickFinishSlot,
      submitQuickStatusModal,
      submitQuickFinish,
      showPauseModal,
      pauseForm,
      isWorkstationPaused,
      activePauseReason,
      activeDowntimeId,
      pausedStartTime,
      pausedDurationSeconds,
      pausedDurationDisplay,
      openPauseModal,
      submitPauseAssembly,
      resumeAssemblyWork,
      showSessionModal,
      sessionForm,
      openSessionModal,
      openReturnToWarehouseFromAssembly,
      submitSessionAction
    };
  };
});

