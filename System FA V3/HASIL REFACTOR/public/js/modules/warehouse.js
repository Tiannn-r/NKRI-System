/**
 * Warehouse & Logistics Module (Fase 2G)
 * Sistem Final Assembly & Traceability P9 - NKRI
 *
 * Encapsulates:
 * 1. Supply Part & Work Order Creation (/work-order / warehouse)
 * 2. Pengembalian Part / Tray dari Assembly (/pengembalian / returns)
 * 3. Penerimaan Barang Masuk & QC Receipt (/penerimaan / warehouse_receipt)
 * 4. Verifikasi Fisik Aktual & Decline Gudang
 */
(function (window) {
  'use strict';

  window.createWarehouseModule = function (ctx) {
    const { ref, reactive, computed, watch, nextTick } = ctx.Vue || Vue;

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
    const currentTab = ctx.currentTab || ref('warehouse');
    const activePrintDocType = ctx.activePrintDocType || ref('supply');

    const pgState = ctx.pgState || reactive({
      returns: { page: 1, size: 10 },
      receiptTray: { page: 1, size: 10 },
      gudangVerif: { page: 1, size: 10 }
    });

    const traysList = ctx.traysList || ref([]);
    const selectedTrayCode = ctx.selectedTrayCode || ref('');
    const currentTray = ctx.currentTray || ref(null);
    const travelerTray = ctx.travelerTray || ref(null);
    const currentTrayAssemblies = ctx.currentTrayAssemblies || ref([]);
    const selectedTravelerTrayCode = ctx.selectedTravelerTrayCode || ref('');
    const currentLaporanSubTab = ctx.currentLaporanSubTab || ref('dokumen');
    const travelerItems = ctx.travelerItems || ref([]);
    const whQCQualifiedItems = ctx.whQCQualifiedItems || ref([]);
    const warehouseReceiptReports = ctx.warehouseReceiptReports || ref([]);
    const reportFilters = ctx.reportFilters || ref({ warehouse_receipt: {} });

    const getOperatorFullName = ctx.getOperatorFullName || function (name) { return name || '-'; };
    const getNowLocalDateTimeStr = ctx.getNowLocalDateTimeStr || function () { return new Date().toISOString(); };
    const loadTrays = ctx.loadTrays || (async () => {});
    const loadAssemblyTray = ctx.loadAssemblyTray || (async () => {});
    const loadTravelerTray = ctx.loadTravelerTray || (async () => {});
    const loadMasterData = ctx.loadMasterData || (async () => {});
    const loadOperatorsList = ctx.loadOperatorsList || (async () => {});
    const loadWarehouseReceiptReports = ctx.loadWarehouseReceiptReports || (async () => {});
    const selectPrintDocType = ctx.selectPrintDocType || function () {};
    const selectAndPrintTraveler = ctx.selectAndPrintTraveler || (async () => {});
    const triggerPrint = ctx.triggerPrint || function () {};
    const navigateTo = ctx.navigateTo || function () {};
    const switchTab = ctx.switchTab || function () {};

    let gudangStageHistoryDepth = ctx.gudangStageHistoryDepth || ref(0);
    let programmaticBackCount = ctx.programmaticBackCount || 0;
    let isNavigatingBack = ctx.isNavigatingBack || false;

    // ===================================================================
    // 1. SUPPLY PART & WORK ORDER CREATION STATE & LOGIC
    // ===================================================================
    const gudangPicName = ref(localStorage.getItem('full_name') || localStorage.getItem('username') || 'Petugas Gudang');
    const gudangProductType = ref('Nanggala P9');
    const gudangAssignedOperator = ref('');
    const gudangSourceMO = ref('');
    const gudangSourceMOSuffix = ref('');
    const showPromptMOModal = ref(false);
    const moInputRef = ref(null);
    const inputDropdown = ref({ isOpen: false, idx: null, field: null, list: [], hasSearched: false });
    const gudangColStage = ref(1);
    const gudangRows = ref([{ p1: '', p12: '', p15: '' }]);
    const showTrayResultPopup = ref(false);
    const newTrayResult = ref({});
    const showTrayDetailModal = ref(false);
    const trayDetailData = ref({ tray: null, parts: [] });

    const currentTravelerMO = ctx.currentTravelerMO || ref('');
    const currentTravelerMOSuffix = ctx.currentTravelerMOSuffix || ref('');

    const addGudangRow = (focus = false) => {
      gudangRows.value.push({ p1: '', p12: '', p15: '' });
      if (focus) {
        nextTick(() => {
          const nextIdx = gudangRows.value.length - 1;
          const stageField = gudangColStage.value === 1 ? 'p1' : (gudangColStage.value === 2 ? 'p12' : 'p15');
          const target = document.getElementById(`g-${stageField}-${nextIdx}`) ||
            document.getElementById(`g-${stageField}-sm-${nextIdx}`) ||
            document.getElementById(`g-${stageField}-m-${nextIdx}`);
          if (target) target.focus();
        });
      }
    };

    let fetchDebounceTimer = null;

    const fetchInputSuggestions = (idx, field, delay = 0, openDropdown = true) => {
      clearTimeout(fetchDebounceTimer);
      const doFetch = async () => {
        const row = gudangRows.value[idx];
        if (!row) return;
        const pType = field === 'p1' ? '1' : (field === 'p12' ? '12' : '15');
        const queryVal = (row[field] || '').trim();

        // Used in other rows of the CURRENT tray form
        const usedInForm = new Set();
        gudangRows.value.forEach((r, i) => {
          if (i !== idx && r[field] && r[field].trim()) {
            usedInForm.add(r[field].trim().toUpperCase());
            usedInForm.add(r[field].trim().replace(/[.,\s]/g, ''));
          }
        });

        try {
          // Selalu panggil endpoint live real-time agar part yang sudah dipakai/disimpan ke tray langsung hilang tanpa perlu refresh
          const json = await fetchAPI(`/master/available?type=${pType}&q=${encodeURIComponent(queryVal)}&limit=60`);
          if (json.ok && Array.isArray(json.data)) {
            if (openDropdown) {
              const filtered = json.data.filter(item => {
                const snClean = item.sn.toUpperCase();
                const snNoDot = item.sn.replace(/[.,\s]/g, '');
                return !usedInForm.has(snClean) && !usedInForm.has(snNoDot);
              }).slice(0, 40);

              inputDropdown.value = {
                isOpen: filtered.length > 0,
                idx,
                field,
                list: filtered,
                hasSearched: true
              };
            }
          }
        } catch (_) { }
      };

      if (delay > 0) {
        fetchDebounceTimer = setTimeout(doFetch, delay);
      } else {
        doFetch();
      }
    };

    const onInputFocus = (idx, field) => {
      fetchInputSuggestions(idx, field, 0);
    };

    const onInputP1 = (idx) => {
      const row = gudangRows.value[idx];
      if (!row) return;
      row.p1 = (row.p1 || '').replace(/\D/g, '').slice(0, 10);
      fetchInputSuggestions(idx, 'p1', 200);
    };

    const onInputP12 = (idx) => {
      const row = gudangRows.value[idx];
      if (!row) return;
      row.p12 = (row.p12 || '').replace(/\D/g, '').slice(0, 10);
      fetchInputSuggestions(idx, 'p12', 200);
    };

    const onInputP15 = (idx) => {
      const row = gudangRows.value[idx];
      if (!row) return;
      row.p15 = (row.p15 || '').toUpperCase();
      fetchInputSuggestions(idx, 'p15', 200);
    };

    let dropdownTouchStartX = 0;
    let dropdownTouchStartY = 0;
    let dropdownTouchMoved = false;

    const onDropdownTouchStart = (e) => {
      if (e.touches && e.touches.length > 0) {
        dropdownTouchStartX = e.touches[0].clientX;
        dropdownTouchStartY = e.touches[0].clientY;
        dropdownTouchMoved = false;
      }
    };

    const onDropdownTouchMove = (e) => {
      if (e.touches && e.touches.length > 0) {
        const dx = Math.abs(e.touches[0].clientX - dropdownTouchStartX);
        const dy = Math.abs(e.touches[0].clientY - dropdownTouchStartY);
        if (dx > 8 || dy > 8) {
          dropdownTouchMoved = true;
        }
      }
    };

    const onDropdownTouchEnd = (e, idx, field, sn) => {
      if (dropdownTouchMoved) return;
      e.preventDefault();
      selectDropdownSN(idx, field, sn);
    };

    const onInputBlur = (idx, field) => {
      formatGudangInput(idx, field);
      setTimeout(() => {
        if (inputDropdown.value.idx === idx && inputDropdown.value.field === field) {
          inputDropdown.value.isOpen = false;
        }
      }, 300);
    };

    const selectDropdownSN = (idx, field, sn) => {
      const row = gudangRows.value[idx];
      if (row) {
        row[field] = sn;
        formatGudangInput(idx, field);
      }
      inputDropdown.value.isOpen = false;

      const nextIdx = idx + 1;
      if (nextIdx < gudangRows.value.length) {
        const nextEl = document.getElementById(`g-${field}-${nextIdx}`) ||
          document.getElementById(`g-${field}-sm-${nextIdx}`) ||
          document.getElementById(`g-${field}-m-${nextIdx}`);
        if (nextEl) nextEl.focus();
      } else {
        if (gudangColStage.value === 1) {
          addGudangRow(true);
        }
      }
    };

    const getInputWarning = (idx, field) => {
      const row = gudangRows.value[idx];
      if (!row) return '';
      const val = (row[field] || '').trim();
      if (!val) return '';

      const valClean = val.toUpperCase().replace(/[.,\s]/g, '');
      for (let i = 0; i < gudangRows.value.length; i++) {
        if (i !== idx) {
          const otherVal = (gudangRows.value[i][field] || '').trim().toUpperCase().replace(/[.,\s]/g, '');
          if (otherVal && otherVal === valClean) {
            return `Nomor sudah dipilih di Baris #${i + 1}`;
          }
        }
      }

      if (inputDropdown.value.idx === idx && inputDropdown.value.field === field && inputDropdown.value.hasSearched) {
        if (inputDropdown.value.list.length === 0) {
          return 'Data tidak tersedia / sudah dipakai di Master Stock';
        }
      }

      return '';
    };

    const formatGudangInput = (idx, field) => {
      const row = gudangRows.value[idx];
      if (!row) return;
      if (field === 'p1') {
        const val = (row.p1 || '').trim();
        if (!val) return;
        if (/^\d+$/.test(val)) {
          if (val.length <= 2) {
            const num = parseInt(val, 10);
            if (num >= 1 && num <= 99) {
              row.p1 = String(num).padStart(3, '0');
            }
          }
        }
      } else if (field === 'p12') {
        const clean = (row.p12 || '').trim().replace(/\D/g, '');
        if (clean) {
          row.p12 = clean;
        }
      } else if (field === 'p15') {
        row.p15 = (row.p15 || '').trim().toUpperCase();
      }
    };

    const removeGudangRow = (idx) => {
      if (gudangRows.value.length > 1) {
        gudangRows.value.splice(idx, 1);
      }
    };

    const goToGudangStage = (stage) => {
      const prevStage = gudangColStage.value;
      if (stage === 1) {
        gudangColStage.value = 1;
        triggerToast('Tahap 1', 'Kembali ke pengisian Part 1', 'info');
        nextTick(() => {
          const el = document.getElementById('g-p1-0') || document.getElementById('g-p1-m-0');
          if (el) el.focus();
        });
      } else if (stage === 2) {
        if (gudangColStage.value < 2) {
          verifyGudangStage1();
        } else {
          gudangColStage.value = 2;
          triggerToast('Tahap 2', 'Kembali ke pengisian Part 12', 'info');
          nextTick(() => {
            const el = document.getElementById('g-p12-sm-0') || document.getElementById('g-p12-m-0') || document.getElementById('g-p12-0');
            if (el) el.focus();
          });
        }
      } else if (stage === 3) {
        if (gudangColStage.value === 1) {
          verifyGudangStage1();
          if (gudangColStage.value === 2) verifyGudangStage2();
        } else if (gudangColStage.value === 2) {
          verifyGudangStage2();
        }
      }

      if (stage > prevStage) {
        try {
          history.replaceState({ type: 'gudang_stage', stage }, '', window.location.pathname);
        } catch (_) { }
      }
    };

    const handleGudangKeyDown = async (e, idx, col) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const row = gudangRows.value[idx];
        if (!row) return;
        const rawVal = (row[col] || '').trim();

        if (!rawVal) {
          triggerToast('Pilih SN Terlebih Dahulu', `Harap cari dan pilih SN Part ${col === 'p1' ? '1' : (col === 'p12' ? '12' : '15')} dari dropdown rekomendasi!`, 'warn');
          return;
        }

        formatGudangInput(idx, col);
        const val = (row[col] || '').trim();

        const suggestions = inputDropdown.value.list || [];
        let matchedItem = null;

        if (suggestions.length > 0) {
          const cleanVal = val.toUpperCase().replace(/[.,\s]/g, '');
          matchedItem = suggestions.find(it =>
            it.sn.toUpperCase().replace(/[.,\s]/g, '') === cleanVal ||
            it.sn.toUpperCase() === val.toUpperCase()
          );
          if (!matchedItem && suggestions.length === 1) {
            matchedItem = suggestions[0];
          }
        }

        if (!matchedItem) {
          try {
            const pType = col === 'p1' ? '1' : (col === 'p12' ? '12' : '15');
            const json = await fetchAPI(`/master/available?type=${pType}&q=${encodeURIComponent(val)}&limit=10`);
            if (json && json.ok && Array.isArray(json.data) && json.data.length > 0) {
              const cleanVal = val.toUpperCase().replace(/[.,\s]/g, '');
              matchedItem = json.data.find(it =>
                it.sn.toUpperCase().replace(/[.,\s]/g, '') === cleanVal ||
                it.sn.toUpperCase() === val.toUpperCase()
              ) || json.data[0];
            }
          } catch (_) { }
        }

        if (matchedItem) {
          selectDropdownSN(idx, col, matchedItem.sn);
        } else {
          const pType = col === 'p1' ? '1' : (col === 'p12' ? '12' : '15');
          let rangeHint = '';
          if (pType === '1') rangeHint = ' (Rentang 001 - 999 / 1000 - 6000 / Double Part)';
          else if (pType === '12') rangeHint = ' (Rentang 60001 - 63500 / 70001 - 71500 / Double Part)';
          else if (pType === '15') rangeHint = ' (Induk J11..)';

          triggerToast('SN Tidak Tersedia', `Nomor Part ${pType} '${rawVal}' tidak tersedia di Master Stock${rangeHint}! Silakan pilih nomor dari dropdown.`, 'error');

          const currentEl = document.getElementById(`g-${col}-${idx}`) ||
            document.getElementById(`g-${col}-sm-${idx}`) ||
            document.getElementById(`g-${col}-m-${idx}`);
          if (currentEl) currentEl.focus();
        }
      }
    };

    const verifyGudangStage1 = () => {
      if (!gudangAssignedOperator.value || !gudangAssignedOperator.value.trim()) {
        triggerToast('Operator Belum Dipilih', 'Silakan pilih Operator Assembly yang ditugaskan terlebih dahulu pada form di atas!', 'error');
        const opEl = document.getElementById('gudang-operator-select');
        if (opEl) {
          opEl.focus();
          opEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }

      for (let i = 0; i < gudangRows.value.length; i++) {
        formatGudangInput(i, 'p1');
        const val = (gudangRows.value[i].p1 || '').trim();
        if (!val) {
          triggerToast('Validasi Part 1 Gagal', `Baris #${i + 1} kolom Part 1 belum diisi!`, 'error');
          const el = document.getElementById(`g-p1-${i}`) || document.getElementById(`g-p1-m-${i}`);
          if (el) el.focus();
          return;
        }
        if (!/^\d+$/.test(val) || val.length < 3) {
          triggerToast('Validasi Part 1 Gagal', `Baris #${i + 1}: Nomor Part 1 '${val}' tidak valid! Nomor harus berupa digit angka (minimal 3 digit).`, 'error');
          const el = document.getElementById(`g-p1-${i}`) || document.getElementById(`g-p1-m-${i}`);
          if (el) el.focus();
          return;
        }
        const warn = getInputWarning(i, 'p1');
        if (warn) {
          triggerToast('Validasi Part 1 Gagal', `Baris #${i + 1}: ${warn}`, 'error');
          const el = document.getElementById(`g-p1-${i}`) || document.getElementById(`g-p1-m-${i}`);
          if (el) el.focus();
          return;
        }
      }
      gudangColStage.value = 2;
      triggerToast('Tahap 1 Selesai', 'Sekarang isi kolom Part 12 untuk setiap baris.', 'success');
      nextTick(() => {
        const el = document.getElementById('g-p12-sm-0') || document.getElementById('g-p12-m-0') || document.getElementById('g-p12-0');
        if (el) el.focus();
      });
    };

    const verifyGudangStage2 = () => {
      for (let i = 0; i < gudangRows.value.length; i++) {
        formatGudangInput(i, 'p12');
        const val = (gudangRows.value[i].p12 || '').trim();
        if (!val) {
          triggerToast('Validasi Part 12 Gagal', `Baris #${i + 1} kolom Part 12 belum diisi!`, 'error');
          const el = document.getElementById(`g-p12-sm-${i}`) || document.getElementById(`g-p12-m-${i}`) || document.getElementById(`g-p12-${i}`);
          if (el) el.focus();
          return;
        }
        if (!/^\d+$/.test(val) || val.length < 4) {
          triggerToast('Validasi Part 12 Gagal', `Baris #${i + 1}: Nomor Part 12 '${val}' tidak valid! Nomor harus berupa digit angka (minimal 4 digit).`, 'error');
          const el = document.getElementById(`g-p12-sm-${i}`) || document.getElementById(`g-p12-m-${i}`) || document.getElementById(`g-p12-${i}`);
          if (el) el.focus();
          return;
        }
        const warn = getInputWarning(i, 'p12');
        if (warn) {
          triggerToast('Validasi Part 12 Gagal', `Baris #${i + 1}: ${warn}`, 'error');
          const el = document.getElementById(`g-p12-sm-${i}`) || document.getElementById(`g-p12-m-${i}`) || document.getElementById(`g-p12-${i}`);
          if (el) el.focus();
          return;
        }
      }
      gudangColStage.value = 3;
      triggerToast('Tahap 2 Selesai', 'Sekarang isi kolom Part 15 untuk setiap baris.', 'success');
      nextTick(() => {
        const el = document.getElementById('g-p15-0') || document.getElementById('g-p15-m-0');
        if (el) el.focus();
      });
    };

    const onSourceMOSuffixInput = (e) => {
      let val = (e.target.value || '').replace(/\D/g, '').slice(0, 6);
      gudangSourceMOSuffix.value = val;
    };

    const onTravelerMOSuffixInput = (e) => {
      let val = (e.target.value || '').replace(/\D/g, '').slice(0, 6);
      currentTravelerMOSuffix.value = val;
    };

    const formatMOInput = (e) => {
      let val = (e.target.value || '').toUpperCase();
      if (/^\d{1,6}$/.test(val)) {
        val = 'WH/MO/' + val;
      }
      gudangSourceMO.value = val;
    };

    const promptSaveGudangTray = () => {
      if (!gudangAssignedOperator.value || !gudangAssignedOperator.value.trim()) {
        triggerToast('Operator Belum Dipilih', 'Silakan pilih Operator Assembly yang ditugaskan terlebih dahulu pada form di atas!', 'error');
        const opEl = document.getElementById('gudang-operator-select');
        if (opEl) {
          opEl.focus();
          opEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }

      for (let i = 0; i < gudangRows.value.length; i++) {
        formatGudangInput(i, 'p15');
        const val = gudangRows.value[i].p15.trim();
        if (!val) {
          triggerToast('Validasi Part 15 Gagal', `Baris #${i + 1} kolom Part 15 belum diisi!`, 'error');
          const el = document.getElementById(`g-p15-${i}`) || document.getElementById(`g-p15-m-${i}`);
          if (el) el.focus();
          return;
        }
        const warn = getInputWarning(i, 'p15');
        if (warn) {
          triggerToast('Validasi Part 15 Gagal', `Baris #${i + 1}: ${warn}`, 'error');
          const el = document.getElementById(`g-p15-${i}`) || document.getElementById(`g-p15-m-${i}`);
          if (el) el.focus();
          return;
        }
      }
      gudangSourceMOSuffix.value = '';
      showPromptMOModal.value = true;
      nextTick(() => {
        if (moInputRef.value) moInputRef.value.focus();
      });
    };

    const confirmSaveGudangTray = async () => {
      const suffix = (gudangSourceMOSuffix.value || '').trim();
      gudangSourceMO.value = suffix ? `WH/MO/${suffix.padStart(6, '0')}` : '';
      showPromptMOModal.value = false;
      await saveGudangTray();
    };

    const saveGudangTray = async () => {
      if (!gudangAssignedOperator.value || !gudangAssignedOperator.value.trim()) {
        triggerToast('Operator Belum Dipilih', 'Operator Assembly wajib dipilih saat membuat Tray!', 'error');
        return;
      }

      const partsPayload = [];
      gudangRows.value.forEach((r) => {
        partsPayload.push({ part_type: '1', sn: r.p1.trim() });
        partsPayload.push({ part_type: '12', sn: r.p12.trim() });
        partsPayload.push({ part_type: '15', sn: r.p15.trim() });
      });

      try {
        const json = await fetchAPI('/gudang/save', {
          method: 'POST',
          body: JSON.stringify({
            pic_warehouse: gudangPicName.value.trim(),
            product_type: gudangProductType.value.trim(),
            assigned_operator: gudangAssignedOperator.value.trim(),
            source_document_mo: gudangSourceMO.value.trim(),
            parts: partsPayload
          })
        });

        if (json.ok) {
          newTrayResult.value = json.data;
          showTrayResultPopup.value = true;
          selectedTrayCode.value = json.data.tray_code;

          // 1. Reset seluruh state reactive form WO ke kondisi awal
          gudangRows.value = [{ p1: '', p12: '', p15: '' }];
          gudangColStage.value = 1;
          gudangAssignedOperator.value = '';
          gudangSourceMO.value = '';
          gudangSourceMOSuffix.value = '';
          gudangStageHistoryDepth.value = 0;
          inputDropdown.value = { isOpen: false, idx: null, field: '', list: [], hasSearched: false };

          // 2. Bersihkan URL dan history entry stage tanpa trigger history.go (agar browser tidak merestorasi data lama dari cache)
          try {
            if (window.history && window.history.replaceState) {
              window.history.replaceState({ type: 'tab', key: 'warehouse', tab: 'warehouse' }, '', '/gudang');
            }
          } catch (_) { }

          // 3. Pastikan seluruh elemen input di DOM benar-benar kosong
          nextTick(() => {
            const inputs = document.querySelectorAll('input[id^="g-p1-"], input[id^="g-p12-"], input[id^="g-p15-"]');
            inputs.forEach(inp => { if (inp) inp.value = ''; });
          });

          await loadTrays();
          await loadAssemblyTray();
        } else {
          triggerToast('Gagal Simpan Tray', json.error || 'Terjadi kesalahan sistem.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const closeTrayResultPopup = () => {
      showTrayResultPopup.value = false;
      // Pastikan form benar-benar bersih saat popup ditutup dan siap untuk input WO baru
      gudangRows.value = [{ p1: '', p12: '', p15: '' }];
      gudangColStage.value = 1;
      gudangAssignedOperator.value = '';
      gudangSourceMO.value = '';
      gudangSourceMOSuffix.value = '';
      inputDropdown.value = { isOpen: false, idx: null, field: '', list: [], hasSearched: false };
      nextTick(() => {
        const inputs = document.querySelectorAll('input[id^="g-p1-"], input[id^="g-p12-"], input[id^="g-p15-"]');
        inputs.forEach(inp => { if (inp) inp.value = ''; });
      });
    };

    const startAssemblyFromPopup = () => {
      showTrayResultPopup.value = false;
      if (newTrayResult.value && newTrayResult.value.tray_code) {
        selectedTrayCode.value = newTrayResult.value.tray_code;
      }
      switchTab('assembly');
    };

    const printTrayLabel = async () => {
      const code = newTrayResult.value.tray_code;
      selectedTrayCode.value = code;
      activePrintDocType.value = 'supply';
      showTrayResultPopup.value = false;
      switchTab('traveler');
      await loadAssemblyTray();
      if (currentTray.value) {
        currentTravelerMO.value = currentTray.value.source_document_mo || '';
      }
      setTimeout(() => {
        triggerPrint();
      }, 350);
    };

    const openTrayDetailModal = async (trayId) => {
      try {
        const json = await fetchAPI(`/gudang/tray_detail?id=${trayId}`);
        if (json.ok) {
          trayDetailData.value = json.data;
          showTrayDetailModal.value = true;
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // ===================================================================
    // 2. GUDANG ACTUAL VERIFICATION & DECLINE MODAL LOGIC
    // ===================================================================
    const showGudangVerifyModal = ref(false);
    const gudangVerifySets = ref([]);
    const showAccOverrideModal = ref(false);
    const accOverrideForm = ref({
      set_number: 1,
      status: 'Good',
      defective_parts: [],
      reason: ''
    });

    const showDeclineConfirmModal = ref(false);
    const declineData = ref({
      set_number: 1,
      return_to_stock: true
    });

    const verifiedSetsCount = computed(() => {
      return (gudangVerifySets.value || []).filter(s => s.verified_status === 'ACC' || s.is_declined || s.verified_status === 'DECLINED').length;
    });

    const parseDefectivePartsArray = (raw) => {
      if (!raw) return [];
      if (Array.isArray(raw)) {
        return raw.map(x => String(x).replace(/\D/g, '')).filter(Boolean);
      }
      if (typeof raw === 'number') {
        return [String(raw)];
      }
      if (typeof raw === 'string') {
        const trimmed = raw.trim();
        if (!trimmed) return [];
        if (trimmed.startsWith('[')) {
          try {
            const parsed = JSON.parse(trimmed);
            if (Array.isArray(parsed)) {
              return parsed.map(x => String(x).replace(/\D/g, '')).filter(Boolean);
            }
          } catch (_) { }
        }
        return trimmed.split(/[\s,]+/).map(x => String(x).replace(/\D/g, '')).filter(Boolean);
      }
      return [];
    };

    const openGudangVerifyModal = async (trayArg) => {
      let t = null;
      if (trayArg && typeof trayArg === 'object' && !(trayArg instanceof Event) && (trayArg.id || trayArg.tray_code)) {
        t = trayArg;
      } else {
        t = travelerTray.value || currentTray.value || (traysList.value || []).find(x => x.tray_code === selectedTrayCode.value);
      }
      if (!t) {
        triggerToast('Pilih Tray', 'Silakan pilih dokumen Tray terlebih dahulu.', 'warn');
        return;
      }
      const tId = (t.id && !isNaN(Number(t.id))) ? t.id : '';
      const tCode = t.tray_code || selectedTrayCode.value || '';
      try {
        const json = await fetchAPI(`/gudang/tray_verification_detail?tray_id=${tId}&code=${encodeURIComponent(tCode)}`);
        if (json.ok) {
          gudangVerifySets.value = (json.assemblies || []).map(a => {
            const defs = parseDefectivePartsArray(a.defective_parts);
            return {
              ...a,
              defective_parts: defs,
              is_declined: Boolean(a.is_declined),
              is_reregistered: Boolean(a.is_reregistered)
            };
          });
          showGudangVerifyModal.value = true;
        } else {
          triggerToast('Gagal', json.error || 'Gagal memuat detail rakitan tray', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const verifyCurrentTrayActual = () => {
      openGudangVerifyModal(travelerTray.value);
    };

    const openAccOverrideModal = (set) => {
      let defs = parseDefectivePartsArray(set.defective_parts);
      if (defs.length === 0 && travelerItems.value && travelerItems.value.length) {
        const matchSlot = travelerItems.value.find(s => s.set_number === set.set_number);
        if (matchSlot && matchSlot.defective_parts) {
          defs = parseDefectivePartsArray(matchSlot.defective_parts);
        }
      }
      accOverrideForm.value = {
        set_number: set.set_number,
        status: set.status || 'Good',
        defective_parts: defs,
        reason: set.reason || ''
      };
      showAccOverrideModal.value = true;
    };

    const submitAccOverride = async () => {
      if (!travelerTray.value) return;
      if (accOverrideForm.value.status !== 'Good') {
        if (!accOverrideForm.value.defective_parts || accOverrideForm.value.defective_parts.length === 0) {
          triggerToast('Peringatan', 'Silakan pilih minimal 1 part yang rusak/bermasalah!', 'warn');
          return;
        }
        if (!accOverrideForm.value.reason || !accOverrideForm.value.reason.trim()) {
          triggerToast('Alasan Diperlukan', `Harap isi alasan mengapa status diubah menjadi ${accOverrideForm.value.status}!`, 'warn');
          return;
        }
      }

      try {
        const defReason = (accOverrideForm.value.reason ? accOverrideForm.value.reason.trim() : ('Kondisi fisik ' + accOverrideForm.value.status));
        const json = await fetchAPI('/gudang/verify-set', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: travelerTray.value.id,
            set_number: accOverrideForm.value.set_number,
            action: 'ACC',
            status: accOverrideForm.value.status,
            defective_parts: accOverrideForm.value.defective_parts,
            reason: defReason
          })
        });

        if (json.ok) {
          triggerToast('ACC Berhasil', `Set #${accOverrideForm.value.set_number} berhasil di-ACC (${accOverrideForm.value.status})`, 'success');
          showAccOverrideModal.value = false;
          const target = (gudangVerifySets.value || []).find(s => s.set_number === accOverrideForm.value.set_number);
          if (target) {
            target.verified_status = 'ACC';
            target.status = accOverrideForm.value.status;
            target.reason = accOverrideForm.value.reason.trim();
            target.defective_parts = [...accOverrideForm.value.defective_parts];
            target.is_declined = false;
          }
          await loadMasterData();
        } else {
          triggerToast('Gagal', json.error || 'Gagal ACC set', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const openDeclineModal = (set) => {
      declineData.value = {
        set_number: set.set_number,
        return_to_stock: true
      };
      showDeclineConfirmModal.value = true;
    };

    const confirmDeclineItem = async () => {
      if (!travelerTray.value) return;
      try {
        const json = await fetchAPI('/gudang/verify-set', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: travelerTray.value.id,
            set_number: declineData.value.set_number,
            action: 'DECLINE',
            return_to_stock: declineData.value.return_to_stock
          })
        });

        if (json.ok) {
          triggerToast('Decline Berhasil', `Set #${declineData.value.set_number} berhasil di-decline dan masuk status Siap Siaga`, 'info');
          showDeclineConfirmModal.value = false;
          const target = (gudangVerifySets.value || []).find(s => s.set_number === declineData.value.set_number);
          if (target) {
            target.is_declined = true;
            target.verified_status = 'DECLINED';
            target.is_reregistered = false;
          }
          await loadMasterData();
        } else {
          triggerToast('Gagal', json.error || 'Gagal decline set', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const completeGudangTrayVerification = async () => {
      if (!travelerTray.value) return;
      if (!gudangVerifySets.value || gudangVerifySets.value.length === 0) {
        triggerToast('Belum Ada Rakitan', 'Tray ini belum memiliki data rakitan untuk diverifikasi.', 'warn');
        return;
      }
      const unverifiedCount = gudangVerifySets.value.length - verifiedSetsCount.value;
      if (unverifiedCount > 0) {
        triggerToast('Verifikasi Belum Lengkap', `Masih terdapat ${unverifiedCount} set rakitan yang belum diverifikasi (ACC atau Decline). Harap periksa dan tentukan status seluruh set terlebih dahulu!`, 'warn');
        return;
      }
      try {
        const json = await fetchAPI('/gudang/verify-tray', {
          method: 'POST',
          body: JSON.stringify({ tray_id: travelerTray.value.id, tray_code: travelerTray.value.tray_code })
        });
        if (json.ok) {
          triggerToast('Verifikasi Selesai', 'Pemeriksaan aktual fisik dokumen tray berhasil diselesaikan!', 'success');
          showGudangVerifyModal.value = false;
          if (travelerTray.value) {
            travelerTray.value.verified_by_gudang = true;
            travelerTray.value.verified_by_user = userFullName.value || userName.value;
          }
          await loadTrays();
          await loadAssemblyTray();
          if (currentTab.value === 'traveler') {
            await selectAndPrintTraveler(travelerTray.value.tray_code);
          }
        } else {
          triggerToast('Gagal', json.error || 'Gagal menyelesaikan verifikasi', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    const showQCWarehouseActualVerifyModal = ref(false);
    const qcVerifyTargetTray = ref(null);
    const qcVerifyItemsList = ref([]);
    const isSubmittingQCActualVerify = ref(false);
    const isSubmittingQCItem = ref({});
    const qcActualVerifyNotes = ref('');

    const isTrayQCReceiptVerified = (tray) => {
      if (!tray) return false;
      return Boolean(tray.verified_by_gudang);
    };

    const openQCWarehouseVerificationForTray = async (trayIdOrCode) => {
      currentTab.value = 'laporan';
      currentLaporanSubTab.value = 'dokumen';
      let targetTray = (traysList.value || []).find(t => t.id === trayIdOrCode || t.tray_code === trayIdOrCode);
      if (!targetTray && typeof trayIdOrCode === 'string') {
        targetTray = (traysList.value || []).find(t => t.tray_code && t.tray_code.includes(trayIdOrCode));
      }
      if (targetTray) {
        travelerTray.value = targetTray;
        selectedTravelerTrayCode.value = targetTray.tray_code;
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
      if (targetTray) {
        await openQCWarehouseActualVerifyModal(targetTray);
      }
    };

    const openQCWarehouseActualVerifyModal = async (trayArg) => {
      if (activePrintDocType.value === 'wh_qc' && whQCQualifiedItems.value.length === 0) {
        triggerToast('Dokumen Belum Tersedia', 'Laporan dokumen mutu (WH/QC) belum tersedia sehingga belum dapat diverifikasi.', 'warn');
        return;
      }
      let tray = trayArg || travelerTray.value;
      if (!tray && selectedTravelerTrayCode && selectedTravelerTrayCode.value) {
        tray = (traysList.value || []).find(t => t.tray_code === selectedTravelerTrayCode.value);
      }
      if (!tray) {
        triggerToast('Pilih Tray', 'Pilih tray terlebih dahulu!', 'warn');
        return;
      }
      qcVerifyTargetTray.value = tray;
      qcActualVerifyNotes.value = '';

      try {
        const res = await fetchAPI(`/assemblies?tray_id=${tray.id}`);
        if (res && res.data) {
          qcVerifyItemsList.value = (res.data || []).filter(i => i && (i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC' || (i.status === 'Good' && i.assy_doc_number)));
        } else {
          qcVerifyItemsList.value = (travelerItems.value || []).filter(i => i && (i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC' || (i.status === 'Good' && i.assy_doc_number)));
        }
      } catch (e) {
        qcVerifyItemsList.value = (travelerItems.value || []).filter(i => i && (i.qc_status === 'GOOD' || i.qc_status === 'GOOD_QC' || (i.status === 'Good' && i.assy_doc_number)));
      }
      showQCWarehouseActualVerifyModal.value = true;
    };

    const verifySingleQCItem = async (it, action = 'ACCEPT') => {
      if (!it || !it.id || isSubmittingQCItem.value[it.id]) return;
      isSubmittingQCItem.value[it.id] = true;
      try {
        const isAccepted = (action === 'ACCEPT' || action === 'ACCEPTED');
        const res = await fetchAPI('/warehouse/verify-qc-receipt', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: it.id,
            action: action,
            operator: userFullName.value || userName.value || 'Administrator NKRI',
            notes: (qcActualVerifyNotes.value || '').trim()
          })
        });
        if (res.ok) {
          const newStatus = isAccepted ? 'ACCEPTED' : 'DECLINED';
          it.warehouse_receipt_status = newStatus;

          const matchedSlot = (travelerItems.value || []).find(s => s.id === it.id || s.set_number === it.set_number);
          if (matchedSlot) {
            matchedSlot.warehouse_receipt_status = newStatus;
          }
          const matchedAssy = (currentTrayAssemblies.value || []).find(s => s.id === it.id || s.set_number === it.set_number);
          if (matchedAssy) {
            matchedAssy.warehouse_receipt_status = newStatus;
          }

          const allVerified = qcVerifyItemsList.value.length > 0 && qcVerifyItemsList.value.every(item => item.warehouse_receipt_status === 'ACCEPTED' || item.warehouse_receipt_status === 'ACC' || item.warehouse_receipt_status === 'DECLINED');
          if (allVerified && travelerTray.value) {
            travelerTray.value.verified_by_gudang = true;
            travelerTray.value.verified_by_user = userFullName.value || userName.value || 'Gudang';
            travelerTray.value.verified_at = getNowLocalDateTimeStr();
          } else if (!allVerified && travelerTray.value) {
            travelerTray.value.verified_by_gudang = false;
          }

          triggerToast(
            isAccepted ? 'Verifikasi ACC' : 'Verifikasi Declined',
            `Set #${it.set_number} (${it.part15_sn || 'Item'}) berhasil ${isAccepted ? 'di-ACC' : 'di-Decline'}!`,
            isAccepted ? 'success' : 'warn'
          );

          await loadWarehouseReceiptReports();
        } else {
          triggerToast('Gagal Verifikasi', res.error || 'Terjadi kesalahan', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isSubmittingQCItem.value[it.id] = false;
      }
    };

    const submitQCWarehouseActualVerify = async (action = 'ACCEPT') => {
      if (!qcVerifyTargetTray.value || isSubmittingQCActualVerify.value) return;
      isSubmittingQCActualVerify.value = true;
      try {
        const isAccepted = (action === 'ACCEPT' || action === 'ACCEPTED');
        const res = await fetchAPI('/warehouse/verify-qc-receipt', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: qcVerifyTargetTray.value.id,
            action: action,
            operator: userFullName.value || userName.value || 'Administrator NKRI',
            notes: (qcActualVerifyNotes.value || '').trim()
          })
        });
        if (res.ok) {
          triggerToast('Verifikasi Berhasil', res.message || 'Verifikasi aktual fisik berhasil disimpan!', 'success');
          if (travelerTray.value) {
            travelerTray.value.verified_by_gudang = isAccepted;
            travelerTray.value.warehouse_receipt_status = isAccepted ? 'ACC' : 'DECLINED';
            travelerTray.value.verified_by_user = userFullName.value || userName.value || 'Gudang';
            travelerTray.value.verified_at = getNowLocalDateTimeStr();
          }
          qcVerifyItemsList.value.forEach(i => i.warehouse_receipt_status = isAccepted ? 'ACC' : 'DECLINED');
          showQCWarehouseActualVerifyModal.value = false;
          qcActualVerifyNotes.value = '';
          await loadWarehouseReceiptReports();
        } else {
          triggerToast('Gagal Verifikasi', res.error || 'Terjadi kesalahan', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isSubmittingQCActualVerify.value = false;
      }
    };

    const goToQCReceiptPrint = async (w) => {
      activePrintDocType.value = 'qc_receipt';
      if (w && w.tray_code) {
        await selectAndPrintTraveler(w.tray_code);
      }
      switchTab('traveler');
    };

    // ===================================================================
    // 3. PENGEMBALIAN BARANG / RETURNS STATE & ACTIONS
    // ===================================================================
    const returnedTraysList = ref([]);
    const returnsFilter = ref({
      operator: '',
      traySuffix: '',
      status: ''
    });
    const showReturnDetailModal = ref(false);
    const activeReturnTray = ref(null);
    const activeReturnSets = ref([]);
    const isLoadingReturnDetail = ref(false);
    const isVerifyingReturn = ref(false);
    const verifyingSetIndex = ref(null);

    const pendingReturnsCount = computed(() => {
      return (returnedTraysList.value || []).filter(t => !t.return_verified).length;
    });

    const isAllActiveReturnSetsVerified = computed(() => {
      if (!activeReturnSets.value || activeReturnSets.value.length === 0) return true;
      return activeReturnSets.value.every(s => s.is_verified || s.is_declined || s.status === 'ACC' || s.status === 'Tersedia' || s.status === 'Declined');
    });

    const filteredReturnsList = computed(() => {
      let list = returnedTraysList.value || [];

      if (returnsFilter.value.operator && returnsFilter.value.operator !== 'Semua Operator Assembly') {
        const op = returnsFilter.value.operator.toLowerCase().trim();
        list = list.filter(t => {
          const assigned = (t.assigned_operator || '').toLowerCase().trim();
          const assignedFull = getOperatorFullName(t.assigned_operator).toLowerCase().trim();
          return assigned === op || assignedFull === op || assigned.includes(op) || assignedFull.includes(op);
        });
      }

      if (returnsFilter.value.traySuffix && returnsFilter.value.traySuffix.trim()) {
        const rawSuffix = returnsFilter.value.traySuffix.toLowerCase().trim();
        const cleanSuffix = rawSuffix.replace(/^wh\/assy\//i, '').trim();
        list = list.filter(t => {
          const code = (t.tray_code || '').toLowerCase().trim();
          return code.includes(cleanSuffix) || code.endsWith(cleanSuffix) || code.includes(rawSuffix);
        });
      }

      if (returnsFilter.value.status) {
        if (returnsFilter.value.status === 'pending') {
          list = list.filter(t => !t.return_verified);
        } else if (returnsFilter.value.status === 'verified') {
          list = list.filter(t => t.return_verified);
        }
      }

      return list;
    });

    const pagedReturnsList = computed(() => {
      const s = pgState.returns || { page: 1, size: 10 };
      return (filteredReturnsList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgReturns = computed(() => {
      const s = pgState.returns || { page: 1, size: 10 };
      return Math.max(1, Math.ceil((filteredReturnsList.value || []).length / s.size));
    });

    const onReturnSuffixInput = (e) => {
      let val = e.target.value;
      if (val.toUpperCase().startsWith('WH/ASSY/')) {
        val = val.substring(8);
      }
      returnsFilter.value.traySuffix = val;
    };

    const resetReturnsFilter = () => {
      returnsFilter.value = {
        operator: '',
        traySuffix: '',
        status: ''
      };
    };

    const loadReturnedTrays = async () => {
      try {
        const json = await fetchAPI('/gudang/returned-trays');
        if (json.ok) {
          returnedTraysList.value = json.data || [];
        }
      } catch (e) {
        console.error('Error loadReturnedTrays:', e);
      }
    };

    const openReturnDetailModal = async (tray) => {
      activeReturnTray.value = tray;
      activeReturnSets.value = [];
      isLoadingReturnDetail.value = true;
      showReturnDetailModal.value = true;
      try {
        const tId = tray.id || '';
        const tCode = tray.tray_code || '';
        const json = await fetchAPI(`/gudang/returned-tray-detail?tray_id=${tId}&code=${encodeURIComponent(tCode)}`);
        if (json.ok) {
          activeReturnTray.value = { ...json.tray, finished_count: json.finished_count };
          activeReturnSets.value = json.sets || [];
        } else {
          triggerToast('Gagal', json.error || 'Gagal memuat detail part pengembalian', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isLoadingReturnDetail.value = false;
      }
    };

    const verifyReturnSet = async (s) => {
      if (!activeReturnTray.value) return;
      verifyingSetIndex.value = s.set_number;
      try {
        const json = await fetchAPI('/gudang/verify-return-set', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: activeReturnTray.value.id,
            set_number: s.set_number,
            p15_sn: s.p15_sn,
            p1_sn: s.p1_sn,
            p12_sn: s.p12_sn
          })
        });
        if (json.ok) {
          triggerToast('Set Diverifikasi', json.message || `Set #${s.set_number} berhasil diverifikasi.`, 'success');
          s.is_verified = true;
          s.status = 'Tersedia';
          if (json.is_fully_verified) {
            activeReturnTray.value.return_verified = true;
            activeReturnTray.value.return_verified_by = userFullName.value || userName.value;
            activeReturnTray.value.return_verified_at = getNowLocalDateTimeStr();
            const targetInList = (returnedTraysList.value || []).find(t => t.id === activeReturnTray.value.id || t.tray_code === activeReturnTray.value.tray_code);
            if (targetInList) {
              targetInList.return_verified = true;
              targetInList.return_verified_by = activeReturnTray.value.return_verified_by;
              targetInList.return_verified_at = activeReturnTray.value.return_verified_at;
            }
          }
          await loadReturnedTrays();
          await loadMasterData();
        } else {
          triggerToast('Gagal Verifikasi', json.error || 'Gagal memverifikasi set', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        verifyingSetIndex.value = null;
      }
    };

    const showDeclineReturnModal = ref(false);
    const isSubmittingDeclineReturn = ref(false);
    const declineReturnForm = ref({
      tray_id: null,
      tray_code: '',
      set_number: null,
      pn: '',
      p15_sn: '',
      p1_sn: '',
      p12_sn: '',
      selectedParts: ['15', '1', '12'],
      reason: ''
    });

    const openDeclineReturnModal = (s) => {
      declineReturnForm.value = {
        tray_id: activeReturnTray.value ? activeReturnTray.value.id : null,
        tray_code: activeReturnTray.value ? activeReturnTray.value.tray_code : '',
        set_number: s.set_number,
        pn: s.pn || 'P9 Part 15',
        p15_sn: s.p15_sn,
        p1_sn: s.p1_sn,
        p12_sn: s.p12_sn,
        selectedParts: ['15', '1', '12'],
        reason: ''
      };
      showDeclineReturnModal.value = true;
    };

    const submitDeclineReturnSet = async () => {
      if (!declineReturnForm.value.selectedParts || declineReturnForm.value.selectedParts.length === 0) {
        triggerToast('Peringatan', 'Pilih minimal satu part yang ingin di-decline!', 'warn');
        return;
      }
      if (!declineReturnForm.value.reason.trim()) {
        triggerToast('Peringatan', 'Alasan penolakan / decline wajib diisi!', 'warn');
        return;
      }
      isSubmittingDeclineReturn.value = true;
      try {
        const json = await fetchAPI('/gudang/decline-return-set', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: declineReturnForm.value.tray_id,
            set_number: declineReturnForm.value.set_number,
            declined_parts: declineReturnForm.value.selectedParts,
            p15_sn: declineReturnForm.value.p15_sn,
            p1_sn: declineReturnForm.value.p1_sn,
            p12_sn: declineReturnForm.value.p12_sn,
            reason: declineReturnForm.value.reason.trim()
          })
        });
        if (json.ok) {
          triggerToast('Decline Berhasil', json.message || 'Set berhasil di-decline dan masuk antrean Verifikasi Admin.', 'success');
          showDeclineReturnModal.value = false;
          if (activeReturnTray.value) {
            await openReturnDetailModal(activeReturnTray.value);
          }
          await loadReturnedTrays();
          await loadMasterData();
        } else {
          triggerToast('Gagal Decline', json.error || 'Gagal memproses decline set', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isSubmittingDeclineReturn.value = false;
      }
    };

    const verifyReturnTray = async (tray) => {
      if (!tray) return;
      isVerifyingReturn.value = true;
      try {
        const json = await fetchAPI('/gudang/verify-return-tray', {
          method: 'POST',
          body: JSON.stringify({
            tray_id: tray.id,
            tray_code: tray.tray_code
          })
        });
        if (json.ok) {
          triggerToast('Verifikasi Berhasil', json.message || `Pengembalian barang untuk Tray ${tray.tray_code} berhasil diverifikasi. Status seluruh part otomatis kembali Tersedia.`, 'success');
          if (activeReturnTray.value && (activeReturnTray.value.id === tray.id || activeReturnTray.value.tray_code === tray.tray_code)) {
            activeReturnTray.value.return_verified = true;
            activeReturnTray.value.return_verified_by = userFullName.value || userName.value;
            activeReturnTray.value.return_verified_at = getNowLocalDateTimeStr();
            if (activeReturnSets.value && activeReturnSets.value.length) {
              activeReturnSets.value.forEach(s => {
                if (!s.is_declined && s.status !== 'Declined') {
                  s.is_verified = true;
                  s.status = 'Tersedia';
                }
              });
            }
          }
          const targetInList = (returnedTraysList.value || []).find(t => t.id === tray.id || t.tray_code === tray.tray_code);
          if (targetInList) {
            targetInList.return_verified = true;
            targetInList.return_verified_by = userFullName.value || userName.value;
            targetInList.return_verified_at = getNowLocalDateTimeStr();
          }
          await loadReturnedTrays();
          await loadMasterData();
        } else {
          triggerToast('Gagal', json.error || 'Gagal memverifikasi pengembalian barang', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isVerifyingReturn.value = false;
      }
    };

    const acknowledgeReturnedTray = async (tray) => {
      try {
        const json = await fetchAPI('/gudang/acknowledge-return', {
          method: 'POST',
          body: JSON.stringify({ tray_id: tray.id })
        });
        if (json.ok) {
          triggerToast('Sukses', json.message || 'Tray pengembalian telah diarsipkan.', 'success');
          await loadReturnedTrays();
          await loadTrays();
        } else {
          triggerToast('Gagal', json.error || 'Gagal mengarsipkan notifikasi.', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      }
    };

    // ===================================================================
    // 4. PENERIMAAN BARANG / WAREHOUSE QC RECEIPTS STATE & LOGIC
    // ===================================================================
    const warehouseReceiptCounts = computed(() => {
      const list = warehouseReceiptReports.value || [];
      let pending = 0;
      let accepted = 0;
      let declined = 0;
      list.forEach(it => {
        const st = String(it.warehouse_receipt_status || it.status || '').toUpperCase();
        if (st === 'ACCEPTED' || st === 'ACC') accepted++;
        else if (st === 'DECLINED') declined++;
        else pending++;
      });
      return {
        pending,
        accepted,
        declined,
        total: list.length
      };
    });

    const filteredWarehouseReceiptList = computed(() => {
      let list = warehouseReceiptReports.value || [];
      const f = reportFilters.value ? reportFilters.value.warehouse_receipt : null;
      if (!f) return list;
      if (f.startDate) {
        list = list.filter(it => (it.warehouse_receipt_at || it.qc_tested_at || it.finished_at || '').substring(0, 10) >= f.startDate);
      }
      if (f.endDate) {
        list = list.filter(it => (it.warehouse_receipt_at || it.qc_tested_at || it.finished_at || '').substring(0, 10) <= f.endDate);
      }
      if (f.status) {
        list = list.filter(it => {
          const st = String(it.warehouse_receipt_status || it.status || '').toUpperCase();
          if (f.status === 'PENDING') return st !== 'ACCEPTED' && st !== 'ACC' && st !== 'DECLINED';
          if (f.status === 'ACCEPTED') return st === 'ACCEPTED' || st === 'ACC';
          if (f.status === 'DECLINED') return st === 'DECLINED';
          return true;
        });
      }
      if (f.searchQuery) {
        const q = f.searchQuery.toLowerCase().trim();
        list = list.filter(it => {
          return (it.part15_sn || '').toLowerCase().includes(q) ||
            (it.part1_sn || '').toLowerCase().includes(q) ||
            (it.part12_sn || '').toLowerCase().includes(q) ||
            (it.tray_code || '').toLowerCase().includes(q) ||
            (it.source_document_mo || '').toLowerCase().includes(q) ||
            (it.qc_doc_number || '').toLowerCase().includes(q);
        });
      }
      return list;
    });

    const showWarehouseQCModal = ref(false);
    const isSubmittingWarehouseQC = ref(false);
    const warehouseQCForm = ref({
      assembly_id: null,
      qc_doc_number: '',
      part15_sn: '',
      part12_sn: '',
      part1_sn: '',
      action: 'ACCEPT',
      notes: ''
    });

    const openWarehouseQCVerifyModal = (item, action = 'ACCEPT') => {
      warehouseQCForm.value = {
        assembly_id: item.assembly_id || item.id,
        qc_doc_number: item.qc_doc_number || '',
        part15_sn: item.part15_sn || '',
        part12_sn: item.part12_sn || '',
        part1_sn: item.part1_sn || '',
        action: action,
        notes: ''
      };
      showWarehouseQCModal.value = true;
    };

    const submitWarehouseQCVerify = async () => {
      if (isSubmittingWarehouseQC.value) return;
      if (warehouseQCForm.value.action === 'DECLINE' && !warehouseQCForm.value.notes.trim()) {
        triggerToast('Alasan Wajib', 'Mohon isi alasan penolakan/decline fisik!', 'warn');
        return;
      }
      isSubmittingWarehouseQC.value = true;
      try {
        const res = await fetchAPI('/warehouse/verify-qc-receipt', {
          method: 'POST',
          body: JSON.stringify({
            assembly_id: warehouseQCForm.value.assembly_id,
            action: warehouseQCForm.value.action,
            operator: userFullName.value || userName.value || 'Administrator NKRI',
            notes: warehouseQCForm.value.notes
          })
        });
        if (res.ok) {
          triggerToast('Verifikasi Fisik Berhasil', res.message, 'success');
          showWarehouseQCModal.value = false;
          loadWarehouseReceiptReports();
        } else {
          triggerToast('Gagal Verifikasi', res.error || 'Terjadi kesalahan', 'error');
        }
      } catch (e) {
        triggerToast('Error', e.message, 'error');
      } finally {
        isSubmittingWarehouseQC.value = false;
      }
    };

    const selectedReceiptTrayCode = ctx.selectedReceiptTrayCode || ref(null);
    const isSubmittingBatchReceipt = ref(false);

    const warehouseReceiptTraySummaryList = computed(() => {
      const list = filteredWarehouseReceiptList.value || [];
      const groups = {};
      list.forEach(item => {
        const key = item.tray_code || 'TANPA_TRAY';
        if (!groups[key]) {
          groups[key] = {
            tray_code: key,
            qc_doc_number: item.qc_doc_number || key,
            qc_tested_at: item.qc_tested_at || item.warehouse_receipt_at || item.finished_at || '',
            pic_qc: item.qc_tested_by_name || item.pic_qc || item.qc_tested_by || '',
            warehouse_receipt_by: item.warehouse_receipt_by_name || item.warehouse_receipt_by || '',
            total_sets: 0,
            pending_count: 0,
            accepted_count: 0,
            declined_count: 0,
            items: []
          };
        }
        groups[key].total_sets++;
        groups[key].items.push(item);

        const st = String(item.warehouse_receipt_status || item.status || '').toUpperCase();
        if (st === 'ACCEPTED' || st === 'ACC') {
          groups[key].accepted_count++;
        } else if (st === 'DECLINED') {
          groups[key].declined_count++;
        } else {
          groups[key].pending_count++;
        }

        if (!groups[key].qc_doc_number && item.qc_doc_number) {
          groups[key].qc_doc_number = item.qc_doc_number;
        }
        if (!groups[key].warehouse_receipt_by && (item.warehouse_receipt_by_name || item.warehouse_receipt_by)) {
          groups[key].warehouse_receipt_by = item.warehouse_receipt_by_name || item.warehouse_receipt_by;
        }
      });
      return Object.values(groups).sort((a, b) => (a.tray_code || '').localeCompare(b.tray_code || '', undefined, { numeric: true, sensitivity: 'base' }));
    });

    const pagedWarehouseReceiptTraySummaryList = computed(() => {
      const s = pgState.receiptTray || { page: 1, size: 10 };
      return (warehouseReceiptTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
    });

    const totalPgReceiptTray = computed(() => {
      const s = pgState.receiptTray || { page: 1, size: 10 };
      return Math.max(1, Math.ceil((warehouseReceiptTraySummaryList.value || []).length / s.size));
    });

    const activeReceiptTrayItems = computed(() => {
      if (!selectedReceiptTrayCode.value) return [];
      const all = warehouseReceiptReports.value || [];
      return all.filter(it => it.tray_code === selectedReceiptTrayCode.value);
    });

    const activeReceiptTraySummary = computed(() => {
      if (!selectedReceiptTrayCode.value) return null;
      return warehouseReceiptTraySummaryList.value.find(s => s.tray_code === selectedReceiptTrayCode.value) || null;
    });

    const openReceiptTrayDetail = (trayCode) => {
      selectedReceiptTrayCode.value = trayCode;
    };

    const openTravelerWHQCDoc = async (trayCode) => {
      if (trayCode) selectedTrayCode.value = trayCode;
      selectPrintDocType('wh_qc');
      await loadTravelerTray();
      navigateTo('traveler');
    };

    const openTravelerWHGRDoc = async (trayCode) => {
      if (trayCode) selectedTrayCode.value = trayCode;
      selectPrintDocType('wh_gr');
      await loadWarehouseReceiptReports();
      if (trayCode) selectedTrayCode.value = trayCode;
      await loadTravelerTray();
      navigateTo('traveler');
    };

    const isCurrentTravelerWHQCVerified = computed(() => {
      if (activeReceiptTraySummary.value) {
        return activeReceiptTraySummary.value.pending_count === 0 && activeReceiptTraySummary.value.total_sets > 0;
      }
      const items = travelerItems.value || [];
      if (items.length === 0) return false;
      return items.some(i => i.warehouse_receipt_status === 'ACCEPTED' || i.warehouse_receipt_status === 'ACC' || i.warehouse_receipt_status === 'DECLINED');
    });

    const isCurrentTravelerQCDone = computed(() => {
      const items = travelerItems.value || [];
      return items.some(i =>
        i.qc_doc_number ||
        i.qc_tested_at ||
        (i.qc_status && i.qc_status !== 'PENDING' && i.qc_status !== 'PENDING_QC')
      );
    });

    const batchAcceptWarehouseReceipt = async (trayCode) => {
      if (isSubmittingBatchReceipt.value) return;
      const items = activeReceiptTrayItems.value.filter(it => {
        const st = String(it.warehouse_receipt_status || it.status || '').toUpperCase();
        return st !== 'ACCEPTED' && st !== 'ACC' && st !== 'DECLINED';
      });
      if (items.length === 0) {
        triggerToast('Info', 'Semua unit pada tray ini sudah diverifikasi.', 'info');
        return;
      }
      isSubmittingBatchReceipt.value = true;
      try {
        let successCount = 0;
        for (const it of items) {
          const res = await fetchAPI('/warehouse/verify-qc-receipt', {
            method: 'POST',
            body: JSON.stringify({
              assembly_id: it.assembly_id || it.id,
              action: 'ACCEPT',
              operator: userFullName.value || userName.value || 'Administrator NKRI',
              notes: 'Batch ACC Penerimaan Fisik Gudang'
            })
          });
          if (res && res.ok) successCount++;
        }
        triggerToast('Batch Verifikasi Sukses', `${successCount} unit berhasil diverifikasi (ACC) ke gudang.`, 'success');
        await loadWarehouseReceiptReports();
      } catch (err) {
        console.error('Batch accept error:', err);
        triggerToast('Error', err.message || 'Gagal melakukan batch verifikasi', 'error');
      } finally {
        isSubmittingBatchReceipt.value = false;
      }
    };

    // ===================================================================
    // EXCEL EXPORT ENGINE FOR GOOD RECEIPTS (WH/GR - WAREHOUSE RECEIPTS)
    // ===================================================================
    const exportGoodReceiptsToExcel = async () => {
      try {
        let rawItems = [];
        const isTrayScoped = Boolean(selectedReceiptTrayCode.value);

        if (isTrayScoped && activeReceiptTrayItems.value && activeReceiptTrayItems.value.length > 0) {
          rawItems = activeReceiptTrayItems.value;
        } else {
          rawItems = (filteredWarehouseReceiptList.value && filteredWarehouseReceiptList.value.length > 0)
            ? filteredWarehouseReceiptList.value
            : (warehouseReceiptReports.value || []);
        }

        if (rawItems.length === 0) {
          try {
            const qp = isTrayScoped ? `?tray_code=${encodeURIComponent(selectedReceiptTrayCode.value)}` : '';
            let res = await fetchAPI(`/reports/warehouse-receipts${qp}`);
            if (!res || !res.ok || !Array.isArray(res.data) || res.data.length === 0) {
              res = await fetchAPI(`/warehouse-receipts${qp}`);
            }
            if (res && res.ok && Array.isArray(res.data)) {
              rawItems = res.data;
            }
          } catch (_) { }
        }

        if (rawItems.length === 0) {
          triggerToast('Data Kosong', 'Tidak ada data Penerimaan Fisik Gudang untuk diekspor ke Excel.', 'warn');
          return;
        }

        const todayStr = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateOnly === 'function')
          ? window.utils.formatDateOnly(new Date()).replace(/\//g, '-')
          : new Date().toISOString().substring(0, 10);

        const filename = `Laporan_Penerimaan_Fisik_Gudang_GR_${isTrayScoped ? (selectedReceiptTrayCode.value.replace(/\//g, '_') + '_') : ''}${todayStr}.xlsx`;
        const title = isTrayScoped
          ? `LAPORAN PENERIMAAN FISIK GUDANG (WH/GR) - TRAY ${selectedReceiptTrayCode.value}`
          : 'LAPORAN PENERIMAAN FISIK GUDANG (WH/GR - GOOD RECEIPTS)';

        const columns = [
          { header: 'No', width: 6, align: 'center' },
          { header: 'No Dokumen Penerimaan (WH/GR)', width: 24, align: 'center' },
          { header: 'Set #', width: 8, align: 'center' },
          { header: 'SN Part 15 (P9)', width: 20, align: 'center' },
          { header: 'PN Part 1', width: 16, align: 'center' },
          { header: 'PN Part 12', width: 16, align: 'center' },
          { header: 'Operator Assembly', width: 22, align: 'left' },
          { header: 'Tester TF 1', width: 22, align: 'left' },
          { header: 'Inspector QC', width: 22, align: 'left' },
          { header: 'Petugas Gudang', width: 22, align: 'left' },
          { header: 'Waktu Verifikasi', width: 20, align: 'center' },
          { header: 'Status Aktual', width: 22, align: 'center' },
          { header: 'Catatan Penerimaan / Alasan', width: 32, align: 'left' }
        ];

        let countAccepted = 0;
        let countDeclined = 0;
        let countPending = 0;

        const rows = rawItems.map((it, idx) => {
          // Status aktual Good Receipts
          let statusAktual = 'MENUNGGU VERIFIKASI';
          const wst = String(it.warehouse_receipt_status || it.status || '').toUpperCase();
          if (wst === 'ACCEPTED' || wst === 'ACC' || wst === 'VERIFIED') {
            statusAktual = 'DITERIMA (ACC)';
            countAccepted++;
          } else if (wst === 'DECLINED' || it.is_declined || wst === 'DECLINE') {
            statusAktual = 'DITOLAK (DECLINED)';
            countDeclined++;
          } else {
            statusAktual = 'MENUNGGU VERIFIKASI';
            countPending++;
          }

          // Format Dokumen Penerimaan WH/GR (Hanya nomor dokumen WH/GR saja)
          let docGR = it.warehouse_doc_number || it.doc_number;
          const tc = it.tray_code || (selectedReceiptTrayCode && selectedReceiptTrayCode.value) || '';
          if (docGR) {
            const suffix = docGR.split('/').pop();
            docGR = `WH/GR/${suffix}`;
          } else if (tc) {
            const suffix = tc.split('/').pop();
            docGR = `WH/GR/${suffix}`;
          } else {
            docGR = '-';
          }

          const rawOpAssy = it.pic_assembly_name || it.pic_assembly || it.assigned_operator;
          const opAssembly = (typeof getOperatorFullName === 'function' ? getOperatorFullName(rawOpAssy) : rawOpAssy) || rawOpAssy || '-';

          const rawTester = it.tf1_tester_name || it.assy_tester_username || it.pic_assembly_name || it.pic_assembly;
          const testerTF1 = (typeof getOperatorFullName === 'function' ? getOperatorFullName(rawTester) : rawTester) || rawTester || '-';

          const rawInspector = it.qc_tested_by || it.qc_tester_name || it.pic_qc || it.qc_inspector_username;
          const inspectorQC = (typeof getOperatorFullName === 'function' ? getOperatorFullName(rawInspector) : rawInspector) || rawInspector || '-';

          const rawWH = it.warehouse_receipt_by_name || it.warehouse_receipt_by || it.verified_by_user || it.pic_warehouse;
          const petugasGudang = (typeof getOperatorFullName === 'function' ? getOperatorFullName(rawWH) : rawWH) || rawWH || '-';

          const receiptTime = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateTime === 'function')
            ? window.utils.formatDateTime(it.warehouse_receipt_at || it.qc_tested_at || it.finished_at)
            : (it.warehouse_receipt_at || it.qc_tested_at || it.finished_at || '-');

          const notes = it.warehouse_receipt_notes || it.declined_reason || it.notes || '-';

          return [
            idx + 1,
            docGR || '-',
            it.set_number ? ('#' + it.set_number) : ('#' + (idx + 1)),
            it.part15_sn || it.sn || '-',
            it.part1_sn || '-',
            it.part12_sn || '-',
            opAssembly,
            testerTF1,
            inspectorQC,
            petugasGudang,
            receiptTime,
            statusAktual,
            notes
          ];
        });

        const summaryText = `Total Unit: ${rawItems.length}   |   Diterima (ACC): ${countAccepted}   |   Ditolak (Declined): ${countDeclined}   |   Menunggu Verifikasi: ${countPending}`;

        if (window.utils && typeof window.utils.exportToExcel === 'function') {
          await window.utils.exportToExcel({
            filename,
            sheetName: 'Penerimaan Fisik',
            title,
            subtitle: `Dicetak dari Menu Penerimaan Fisik Gudang (WH/GR) • Tanggal: ${new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB`,
            summary: summaryText,
            columns,
            rows,
            triggerToast
          });
        }
      } catch (err) {
        console.error('exportGoodReceiptsToExcel error:', err);
        triggerToast('Gagal Ekspor', err.message || 'Terjadi kesalahan saat mengekspor Excel.', 'error');
      }
    };

    // ===================================================================
    // TAB INITIALIZATION HELPERS
    // ===================================================================
    const initWarehouseTab = () => {
      if (window.logger) logger.info('WAREHOUSE', 'Memuat data Work Order & Tray Gudang...');
      loadTrays();
      loadOperatorsList();
    };

    const initReturnsTab = () => {
      if (window.logger) logger.info('WAREHOUSE', 'Memuat data Pengembalian Barang (Returns)...');
      loadReturnedTrays();
      loadOperatorsList();
    };

    const initReceiptsTab = () => {
      if (window.logger) logger.info('WAREHOUSE', 'Memuat data Penerimaan Barang (Receipts)...');
      loadWarehouseReceiptReports();
    };

    // ===================================================================
    // MODULE EXPORTS
    // ===================================================================
    return {
      initWarehouseTab,
      initReturnsTab,
      initReceiptsTab,
      gudangPicName,
      gudangProductType,
      gudangAssignedOperator,
      gudangSourceMO,
      gudangSourceMOSuffix,
      showPromptMOModal,
      moInputRef,
      inputDropdown,
      gudangColStage,
      gudangRows,
      showTrayResultPopup,
      newTrayResult,
      showTrayDetailModal,
      trayDetailData,
      currentTravelerMO,
      currentTravelerMOSuffix,
      addGudangRow,
      removeGudangRow,
      goToGudangStage,
      handleGudangKeyDown,
      verifyGudangStage1,
      verifyGudangStage2,
      onSourceMOSuffixInput,
      onTravelerMOSuffixInput,
      formatMOInput,
      promptSaveGudangTray,
      confirmSaveGudangTray,
      saveGudangTray,
      closeTrayResultPopup,
      startAssemblyFromPopup,
      printTrayLabel,
      openTrayDetailModal,
      fetchInputSuggestions,
      onInputFocus,
      onInputP1,
      onInputP12,
      onInputP15,
      onDropdownTouchStart,
      onDropdownTouchMove,
      onDropdownTouchEnd,
      onInputBlur,
      selectDropdownSN,
      getInputWarning,
      formatGudangInput,
      showGudangVerifyModal,
      gudangVerifySets,
      showAccOverrideModal,
      accOverrideForm,
      verifiedSetsCount,
      parseDefectivePartsArray,
      openGudangVerifyModal,
      verifyCurrentTrayActual,
      openAccOverrideModal,
      submitAccOverride,
      showDeclineConfirmModal,
      declineData,
      openDeclineModal,
      confirmDeclineItem,
      completeGudangTrayVerification,
      showQCWarehouseActualVerifyModal,
      qcVerifyTargetTray,
      qcVerifyItemsList,
      isSubmittingQCActualVerify,
      isSubmittingQCItem,
      qcActualVerifyNotes,
      isTrayQCReceiptVerified,
      openQCWarehouseVerificationForTray,
      openQCWarehouseActualVerifyModal,
      verifySingleQCItem,
      submitQCWarehouseActualVerify,
      goToQCReceiptPrint,
      returnedTraysList,
      returnsFilter,
      showReturnDetailModal,
      activeReturnTray,
      activeReturnSets,
      isLoadingReturnDetail,
      isVerifyingReturn,
      verifyingSetIndex,
      pendingReturnsCount,
      isAllActiveReturnSetsVerified,
      filteredReturnsList,
      pagedReturnsList,
      totalPgReturns,
      showDeclineReturnModal,
      isSubmittingDeclineReturn,
      declineReturnForm,
      onReturnSuffixInput,
      resetReturnsFilter,
      loadReturnedTrays,
      openReturnDetailModal,
      verifyReturnSet,
      openDeclineReturnModal,
      submitDeclineReturnSet,
      verifyReturnTray,
      acknowledgeReturnedTray,
      warehouseReceiptCounts,
      filteredWarehouseReceiptList,
      showWarehouseQCModal,
      isSubmittingWarehouseQC,
      warehouseQCForm,
      openWarehouseQCVerifyModal,
      submitWarehouseQCVerify,
      selectedReceiptTrayCode,
      isSubmittingBatchReceipt,
      warehouseReceiptTraySummaryList,
      pagedWarehouseReceiptTraySummaryList,
      totalPgReceiptTray,
      activeReceiptTrayItems,
      activeReceiptTraySummary,
      openReceiptTrayDetail,
      openTravelerWHQCDoc,
      openTravelerWHGRDoc,
      isCurrentTravelerWHQCVerified,
      isCurrentTravelerQCDone,
      batchAcceptWarehouseReceipt,
      exportGoodReceiptsToExcel
    };
  };
})(window);
