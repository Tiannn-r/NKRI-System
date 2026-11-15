// ===================================================================
// SISTEM FINAL ASSEMBLY & TRACEABILITY NANGGALA P9 (VERSI 3.0)
// MODULE: TEST FUNCTIONAL 1 / OPERATOR ASSEMBLY TESTING (TF1)
// FILE: public/js/modules/tf1.js
// ===================================================================

(function (window) {
  'use strict';

  window.createTF1Module = function (deps) {
    const ctx = deps || {};
    const {
      Vue,
      fetchAPI,
      triggerToast,
      showConfirmCard,
      pgState,
      userRole,
      operatorName,
      currentTab,
      getOperatorFullName,
      selectedTrayCode,
      currentTray,
      traySlots,
      testingPointsList,
      showDetailModal,
      openDetailModal,
      loadAssemblyTray,
      loadQCPendingItems,
      loadProductionHoldItems,
      loadPendingVerifications,
      loadTravelerTray,
      loadTrays,
      filteredTraysList,
      isReassemblySet,
      navigateTo,
      activePrintDocType,
      getCurrentTabHash,
      getNowLocalDateTimeStr
    } = deps;

    const { ref, reactive, computed, watch, nextTick } = Vue;

        const isSingleRetestMode = ref(false);
        const singleRetestTestNum = ref(null);
        const singleRetestStatus = ref('GOOD');
        const singleRetestReturnSource = ref('view2_table');
        const singleRetestSlot = ref(null);
        const singleRetestNotes = ref('');
        const isSubmittingSingleRetest = ref(false);

        const assyTotalSteps = computed(() => {
          return (assyTestForm.value && assyTestForm.value.checklist && assyTestForm.value.checklist.length) || 15;
        });

        const currentActiveChecklistItem = computed(() => {
          if (!assyTestForm.value || !assyTestForm.value.checklist) return null;
          if (isSingleRetestMode.value && singleRetestTestNum.value) {
            return assyTestForm.value.checklist.find(t => Number(t.test_num) === Number(singleRetestTestNum.value)) || assyTestForm.value.checklist[0];
          }
          return assyTestForm.value.checklist[assyTestStep.value] || null;
        });



        const startSingleRetest = (slot, testNum, returnSource = 'view2_table') => {
          if (userRole.value === 'gudang' || userRole.value === 'qc') {
            triggerToast('Akses Terbatas', (userRole.value === 'qc' ? 'Inspector QC' : 'Petugas Gudang') + ' tidak berhak melakukan pengujian perakitan.', 'error');
            return;
          }
          if (slot && isSlotTested(slot)) {
            triggerToast('Pengujian Terkunci', 'Rakitan ini sudah selesai diuji dan terkunci dari pengujian ulang.', 'warning');
            return;
          }
          isSingleRetestMode.value = true;
          singleRetestTestNum.value = Number(testNum);
          singleRetestReturnSource.value = returnSource;
          singleRetestSlot.value = slot;
          singleRetestNotes.value = '';

          // Find current status of this test item in slot
          let currentStatus = 'GOOD';
          let checklist = slot.test_checklist || slot.assy_test_checklist || (assyTestForm.value && assyTestForm.value.checklist);
          if (typeof checklist === 'string') {
            try { checklist = JSON.parse(checklist); } catch (_) { checklist = null; }
          }
          if (Array.isArray(checklist)) {
            const found = checklist.find(t => Number(t.test_num) === Number(testNum) || Number(t.id) === Number(testNum));
            if (found && found.status) currentStatus = String(found.status).toUpperCase();
          }
          singleRetestStatus.value = currentStatus;

          // Prepare assyTestForm with this slot
          if (slot && (slot.set_number || slot.id)) {
            const baseSource = (testingPointsList.value.length ? testingPointsList.value : defaultAssyTestItems);
            let initialChecklist = baseSource.map(item => ({
              test_num: item.test_num,
              name: item.instruction || item.name,
              description: item.output || item.description,
              category: item.category || 'Umum / Fungsional',
              status: ''
            }));

            if (checklist && Array.isArray(checklist) && checklist.length) {
              initialChecklist = initialChecklist.map((defItem) => {
                const ex = checklist.find(p => p && (Number(p.test_num) === Number(defItem.test_num) || Number(p.id) === Number(defItem.test_num)));
                return {
                  ...defItem,
                  status: ex && ex.status ? ex.status : ''
                };
              });
            }

            assyTestForm.value = {
              tray_id: slot.tray_id || (currentTray.value ? currentTray.value.id : null),
              tray_code: slot.tray_code || (currentTray.value ? currentTray.value.tray_code : ''),
              set_number: slot.set_number || 1,
              assembly_id: slot.id || null,
              part15_sn: slot.part15_sn || '',
              part12_sn: slot.part12_sn || '',
              part1_sn: slot.part1_sn || '',
              checklist: initialChecklist,
              notes: ''
            };
          }

          // Cari indeks step untuk nomor uji ini agar active card focus dan progress indicator akurat
          let targetStepIdx = -1;
          if (assyTestForm.value && assyTestForm.value.checklist) {
            targetStepIdx = assyTestForm.value.checklist.findIndex(t => Number(t.test_num) === Number(testNum));
          }
          if (targetStepIdx !== -1) {
            assyTestStep.value = targetStepIdx;
          } else {
            assyTestStep.value = Math.max(0, Number(testNum) - 1);
          }

          // Close detail modal if open & buka form uji retest
          showDetailModal.value = false;
          showAssyTestModal.value = true;
          try {
            const setNo = slot ? (slot.set_number || (assyTestForm.value ? assyTestForm.value.set_number : 1)) : 1;
            history.replaceState(null, '', `#assy_testing?retest=${testNum}&set=${setNo}`);
          } catch (_) { }
        };

        const cancelSingleRetest = () => {
          isSingleRetestMode.value = false;
          if (singleRetestReturnSource.value === 'view2_table') {
            showAssyTestModal.value = true;
            assyTestStep.value = assyTotalSteps.value; // Tampilkan tabel rekap selesai
          } else if (singleRetestReturnSource.value === 'detail_modal') {
            showAssyTestModal.value = false;
            if (singleRetestSlot.value) {
              openDetailModal(singleRetestSlot.value, (singleRetestSlot.value.set_number || 1) - 1);
            }
          } else {
            showAssyTestModal.value = false;
          }
          try { history.replaceState(null, '', '#assy_testing'); } catch (_) { }
        };

        const setSingleRetestStatus = (status) => {
          singleRetestStatus.value = status;
          if (assyTestForm.value && assyTestForm.value.checklist) {
            const target = assyTestForm.value.checklist.find(t => Number(t.test_num) === Number(singleRetestTestNum.value));
            if (target) target.status = status;
          }
        };

        const setSingleRetestAndSubmit = async (status) => {
          if (isSubmittingSingleRetest.value) return;
          singleRetestStatus.value = status;
          if (assyTestForm.value && assyTestForm.value.checklist) {
            const target = assyTestForm.value.checklist.find(t => Number(t.test_num) === Number(singleRetestTestNum.value));
            if (target) target.status = status;
          }

          // Otomatis langsung alihkan kembali ke halaman rekap status seketika (0ms response time)
          const source = singleRetestReturnSource.value;
          const targetSetNum = assyTestForm.value ? assyTestForm.value.set_number : 1;
          isSingleRetestMode.value = false;

          if (source === 'view2_table') {
            showAssyTestModal.value = true;
            assyTestStep.value = assyTotalSteps.value; // Tampilkan tabel rekap selesai
            try { history.replaceState(null, '', '#assy_testing'); } catch (_) { }
            triggerToast('Status Diperbarui', `Test #${singleRetestTestNum.value} diubah menjadi ${status === 'GOOD' ? 'Good' : 'Not Good'}. Perubahan akan disimpan saat tombol Simpan ditekan.`, 'info');
            return;
          } else if (source === 'detail_modal') {
            showAssyTestModal.value = false;
          }
          try { history.replaceState(null, '', '#assy_testing'); } catch (_) { }

          // Simpan data hasil retest ke server di latar belakang
          isSubmittingSingleRetest.value = true;
          try {
            if (assyTestForm.value && (assyTestForm.value.assembly_id || (assyTestForm.value.tray_id && assyTestForm.value.set_number))) {
              const payload = {
                assembly_id: assyTestForm.value.assembly_id,
                tray_id: assyTestForm.value.tray_id,
                set_number: assyTestForm.value.set_number,
                test_num: singleRetestTestNum.value,
                status: status,
                notes: singleRetestNotes.value || ''
              };
              const res = await fetchAPI('/assembly/submit-single-retest', {
                method: 'POST',
                body: JSON.stringify(payload)
              });
              if (res && res.ok) {
                triggerToast('Retest Selesai', res.message || `Test #${singleRetestTestNum.value} berhasil diuji ulang (${res.overall_status || status}).`, (res.overall_status || status) === 'Good' || status === 'GOOD' ? 'success' : 'warn');
              } else {
                triggerToast('Peringatan', (res && res.error) || 'Gagal menyimpan hasil uji ulang', 'warn');
              }
            } else {
              triggerToast('Status Terupdate', `Test #${singleRetestTestNum.value} berhasil diubah menjadi ${status}.`, 'success');
            }

            // Reload data secara asynchronous
            await Promise.all([
              loadAssemblyTray(),
              loadQCPendingItems(),
              loadProductionHoldItems(),
              loadPendingVerifications(),
              loadTravelerTray()
            ]);

            if (source === 'detail_modal') {
              const freshSlot = (traySlots.value || []).find(s => s && s.set_number === targetSetNum) || singleRetestSlot.value;
              if (freshSlot) {
                openDetailModal(freshSlot, targetSetNum - 1);
              }
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSubmittingSingleRetest.value = false;
          }
        };

        const submitSingleRetest = async () => {
          return setSingleRetestAndSubmit(singleRetestStatus.value || 'GOOD');
        };



        const defaultAssyTestItems = [
          {
            test_num: 1,
            name: "1. Pengecekan visual secara menyeluruh mulai dari sisi atas-bawah, samping kanan-kiri, depan belakang",
            description: "- Penebalan pada hasil pengecatan\n- Retak pada area yang berpasangan dengan part berbahan metal",
            category: "Cek Visual",
            status: "GOOD"
          },
          {
            test_num: 2,
            name: "1. Cek upper dan lower dengan cara digoyangkan secara horizontal",
            description: "Tidak mengeluarkan suara akibat hasil rakitan longgar",
            category: "Cek Rigiditas",
            status: "GOOD"
          },
          {
            test_num: 3,
            name: "2. Cek front sight dan rear sight dengan cara digoyangkan",
            description: "Tidak boleh bergeser",
            category: "Cek Rigiditas",
            status: "GOOD"
          },
          {
            test_num: 4,
            name: "1. Uji pelepas magasen sebanyak 3x",
            description: "Magasen tidak jatuh / lepas setelah dikunci\nMagasen dapat masuk - keluar dengan baik ketika pelepas magasen di tekan",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 5,
            name: "2. Uji kokang sebanyak 3x",
            description: "Tidak menyangkut ataupun tersendat ketika di kokang dan dapat kembali dengan lancar",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 6,
            name: "3. Uji picu dengan cara menarik picu untuk mengaktifkan pemukul",
            description: "Pemukul dapat memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 7,
            name: "4. Uji pemukul dengan cara menarik pemukul kemudian tarik picu",
            description: "Pemukul dapat memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 8,
            name: "5. Uji pemukul dengan cara menarik pemukul 1/2 kemudian tarik picu",
            description: "Pemukul dapat terkunci pada kondisi 1/2 tegang\nPemukul dapat memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 9,
            name: "6. Uji pemukul dengan cara menarik penuh pemukul kemudian tarik picu",
            description: "Pemukul dapat memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 10,
            name: "7. Uji pemukul dengan cara menarik penuh pemukul kemudian dorong kedepan",
            description: "Pemukul tidak boleh memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 11,
            name: "7.1. Dorong keatas tuas pengaman kemudian tarik picu",
            description: "Pemukul tidak boleh memukul pena pukul",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 12,
            name: "7.1.1 Dorong kebawah tuas pengaman (untuk menormalkan kembali)",
            description: "Tuas pengaman kembali ke posisi normal",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 13,
            name: "7.2 Tarik pemukul secara penuh kemudian kokang penuh lalu aktifkan kunci eretan",
            description: "Eretan dapat terkunci dengan baik dan tidak bergerak maju kedepan",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 14,
            name: "7.3 Turunkan kunci eretan",
            description: "Eretan dapat maju kedepan dengan baik dan tidak tersendat",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 15,
            name: "8. Kokang penuh kemudian aktifkan kunci eretan dan putar keatas tuas pembongkar",
            description: "Tuas pembongkar dapat diputar keatas",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 16,
            name: "8.1 Putar kembali kebawah tuas pembongkar kemudian non-aktifkan kunci eretan",
            description: "Tuas pembongkar dan kunci eretan dapat dikembalikan ke posisi awal",
            category: "Cek Mekanik",
            status: "GOOD"
          },
          {
            test_num: 17,
            name: "9. Tarik pemukul secara penuh, kemudian dorong laras dari depan ke belakang",
            description: "Laras tidak dapat bergerak mundur ke belakang",
            category: "Cek Mekanik",
            status: "GOOD"
          }
        ];


        // 1. Operator Assembly Functional Testing
        const showAssyTestModal = ref(false);
        const isSubmittingAssyTest = ref(false);
        const assyTestStep = ref(0);
        const assyTestTableViewMode = ref('dual'); // 'dual' (2 kolom tabel) atau 'single' (1 tabel penuh)
        const qcTestTableViewMode = ref('dual');
        const showAssySaveProgressModal = ref(false);
        const assyDraftProgress = ref({}); // key: `${tray_id}_${set_number}`

        try {
          const savedDrafts = localStorage.getItem('assy_draft_progress');
          if (savedDrafts) {
            assyDraftProgress.value = JSON.parse(savedDrafts);
          }
        } catch (_) { }

        const saveAssyDraftToStorage = () => {
          try {
            localStorage.setItem('assy_draft_progress', JSON.stringify(assyDraftProgress.value));
          } catch (_) { }
        };

        // Otomatis bersihkan draft lama/kadaluarsa saat tray/slots dimuat
        if (typeof watch === 'function' && traySlots) {
          watch(traySlots, (newSlots) => {
            if (!Array.isArray(newSlots)) return;
            let changed = false;
            newSlots.forEach(slot => {
              if (!slot) return;
              const trayId = slot.tray_id || (currentTray.value ? currentTray.value.id : null);
              const key = `${trayId}_${slot.set_number}`;
              const draft = assyDraftProgress.value[key];
              if (!draft) return;

              const isTested = isSlotTested(slot);
              const vSt = String(slot.verified_status || '').toUpperCase();
              const snMismatch = draft.part15_sn && slot.part15_sn && draft.part15_sn !== slot.part15_sn;
              const idMismatch = draft.assembly_id && slot.id && draft.assembly_id !== slot.id;
              const newlyApproved = (vSt === 'APPROVED_PROCEED_TF1' || vSt === 'APPROVED_PROCEED') && !draft.saved_after_verified;
              const legacyStale = !draft.part15_sn && !draft.assembly_id && (vSt === 'APPROVED_PROCEED_TF1' || vSt === 'APPROVED_PROCEED' || (!slot.assy_tested_at && !slot.assy_test_checklist));

              if (isTested || snMismatch || idMismatch || newlyApproved || legacyStale) {
                delete assyDraftProgress.value[key];
                changed = true;
              }
            });
            if (changed) {
              saveAssyDraftToStorage();
            }
          }, { immediate: true });
        }

        const hasSlotTestingHistory = (s) => {
          if (!s) return false;
          if (s.assy_doc_number) return true;
          if (s.assy_failure_reasons) return true;
          const chk = s.assy_test_checklist;
          if (chk && (typeof chk === 'string' ? chk.length > 5 : Array.isArray(chk) && chk.length > 0)) return true;
          const qcSt = String(s.qc_status || '').trim().toUpperCase();
          if (qcSt && !['', 'PENDING'].includes(qcSt)) return true;
          const atSt = String(s.assy_test_status || '').trim().toUpperCase();
          if (atSt && !['', 'PENDING'].includes(atSt)) return true;
          return false;
        };

        const isSlotTested = (slot) => {
          if (!slot) return false;
          if (slot.assy_doc_number) return true;
          if (slot.assy_test_status && slot.assy_test_status !== 'PENDING' && slot.assy_test_status !== '') return true;
          if (slot.qc_status && ['HOLD_ASSY', 'REWORK_ASSY', 'PENDING_QC', 'GOOD', 'HOLD_QC', 'RU'].includes(slot.qc_status)) return true;
          const isRU = (typeof isReassemblySet === 'function' && isReassemblySet(slot)) || slot.status === 'Dirakit Ulang' || slot.verified_status === 'WAITING_REASSEMBLY' || slot.assy_test_status === 'RU';
          if (isRU && hasSlotTestingHistory(slot)) return true;
          return false;
        };

        const isSlotTF1Hold = (slot) => {
          if (!slot) return false;
          const atSt = String(slot.assy_test_status || '').trim().toUpperCase();
          const qcSt = String(slot.qc_status || '').trim().toUpperCase();
          const st = String(slot.status || '').trim().toUpperCase();
          const vSt = String(slot.verified_status || '').trim().toUpperCase();
          const path = String(slot.lifecycle_path || '').trim().toUpperCase();

          // Jangan anggap Hold jika RU
          if (atSt === 'RU' || qcSt === 'RU' || st === 'DIRAKIT ULANG' || vSt === 'WAITING_REASSEMBLY' || (typeof isReassemblySet === 'function' && isReassemblySet(slot))) {
            return false;
          }
          // Jangan anggap Hold jika Rework Test
          if (qcSt === 'REWORK_ASSY' || atSt === 'REWORK') {
            return false;
          }
          // Jika pasca hold / pasca rework sudah diapprove Good
          if (path === 'HOLD_APPROVED' || path === 'HOLD_REWORK_APPROVED' || vSt === 'APPROVED') {
            return false;
          }

          // Kriteria Hold TF 1:
          if (atSt === 'HOLD' || atSt === 'HOLD TF 1' || qcSt === 'HOLD_ASSY' || atSt === 'NOT_GOOD' || atSt === 'NOT GOOD') {
            return true;
          }
          if (st === 'HOLD' && (atSt === 'HOLD' || qcSt === 'HOLD_ASSY' || (atSt !== 'GOOD' && atSt !== 'PENDING' && isSlotTested(slot)))) {
            return true;
          }
          return false;
        };

        const isSlotTF1Good = (slot) => {
          if (!slot) return false;
          if (!isSlotTested(slot)) return false;
          if (isSlotTF1Hold(slot)) return false;

          const atSt = String(slot.assy_test_status || '').trim().toUpperCase();
          const qcSt = String(slot.qc_status || '').trim().toUpperCase();
          const st = String(slot.status || '').trim().toUpperCase();
          const vSt = String(slot.verified_status || '').trim().toUpperCase();
          const path = String(slot.lifecycle_path || '').trim().toUpperCase();

          // Jangan anggap Good jika RU atau Rework
          if (atSt === 'RU' || qcSt === 'RU' || st === 'DIRAKIT ULANG' || vSt === 'WAITING_REASSEMBLY' || (typeof isReassemblySet === 'function' && isReassemblySet(slot))) {
            return false;
          }
          if (qcSt === 'REWORK_ASSY' || atSt === 'REWORK') {
            return false;
          }

          // Pasca hold / rework diapprove
          if (path === 'HOLD_APPROVED' || path === 'HOLD_REWORK_APPROVED' || vSt === 'APPROVED') {
            return true;
          }

          // Standard Good TF1:
          if (atSt === 'GOOD' || qcSt === 'GOOD' || qcSt === 'PENDING_QC') {
            return true;
          }
          if (Boolean(slot.assy_doc_number && slot.assy_doc_number !== '-')) {
            return true;
          }
          return false;
        };

        const getSlotDraftProgress = (slot) => {
          if (!slot || isSlotTested(slot)) return null;
          const trayId = slot.tray_id || (currentTray.value ? currentTray.value.id : null);
          const key = `${trayId}_${slot.set_number}`;
          const draft = assyDraftProgress.value[key];
          if (!draft) return null;

          // 1. Validasi identitas part: jika draft tidak cocok dengan SN atau ID slot, bersihkan!
          if (draft.part15_sn && slot.part15_sn && draft.part15_sn !== slot.part15_sn) {
            delete assyDraftProgress.value[key];
            saveAssyDraftToStorage();
            return null;
          }
          if (draft.assembly_id && slot.id && draft.assembly_id !== slot.id) {
            delete assyDraftProgress.value[key];
            saveAssyDraftToStorage();
            return null;
          }

          // 2. Set yang baru diverifikasi Admin untuk lanjut ke TF 1:
          // Status: APPROVED_PROCEED_TF1 atau APPROVED_PROCEED
          // Harus mulai dari 0% (belum ada test berjalan). Bersihkan draft warisan lama jika bukan disimpan setelah verifikasi.
          const vSt = String(slot.verified_status || '').toUpperCase();
          if ((vSt === 'APPROVED_PROCEED_TF1' || vSt === 'APPROVED_PROCEED') && !draft.saved_after_verified) {
            delete assyDraftProgress.value[key];
            saveAssyDraftToStorage();
            return null;
          }

          // 3. Draft warisan tanpa data SN / ID: jika status slot PENDING dan belum pernah dites di TF 1, bersihkan
          if (!draft.part15_sn && !draft.assembly_id) {
            if (vSt === 'APPROVED_PROCEED_TF1' || vSt === 'APPROVED_PROCEED' || (!slot.assy_tested_at && !slot.assy_test_checklist)) {
              delete assyDraftProgress.value[key];
              saveAssyDraftToStorage();
              return null;
            }
          }

          return draft;
        };

        const initialSessionSnapshot = ref(null);

        const handleAssyBackNavigation = () => {
          if (isSingleRetestMode.value) {
            cancelSingleRetest();
            return;
          }

          // Periksa apakah ada perubahan jawaban atau catatan dari saat pertama dibuka di sesi ini
          const form = assyTestForm.value;
          const snap = initialSessionSnapshot.value;
          let hasChanges = false;

          if (snap && Array.isArray(snap.checklist) && Array.isArray(form.checklist)) {
            const currentStatusStr = form.checklist.map(c => `${c.test_num}:${c.status || ''}`).join(';');
            const initialStatusStr = snap.checklist.map(c => `${c.test_num}:${c.status || ''}`).join(';');
            const currentNotes = (form.notes || '').trim();
            const initialNotes = (snap.notes || '').trim();

            if (currentStatusStr !== initialStatusStr || currentNotes !== initialNotes) {
              hasChanges = true;
            }
          }

          // Jika TIDAK ADA perubahan atau posisi berhenti di test itu lagi tanpa input baru:
          // Langsung kembali ke halaman card testing tanpa perlu validasi konfirmasi!
          if (!hasChanges) {
            showAssyTestModal.value = false;
            return;
          }

          // Jika ada perubahan penilaian baru, tanyakan apakah ingin simpan progres
          showAssySaveProgressModal.value = true;
        };

        const confirmSaveAssyProgress = (shouldSave) => {
          const form = assyTestForm.value;
          const key = `${form.tray_id}_${form.set_number}`;
          const snap = initialSessionSnapshot.value;

          if (shouldSave) {
            assyDraftProgress.value[key] = {
              assembly_id: form.assembly_id,
              part15_sn: form.part15_sn,
              step: assyTestStep.value,
              totalSteps: assyTotalSteps.value,
              checklist: JSON.parse(JSON.stringify(form.checklist || [])),
              notes: form.notes || '',
              savedAt: getNowLocalDateTimeStr(),
              saved_after_verified: true
            };
            saveAssyDraftToStorage();
            triggerToast('Progres Disimpan', `Progres pengujian Set #${form.set_number} berhasil disimpan.`, 'success');
          } else {
            // User memilih "Tidak": JANGAN hapus progres jika sebelumnya sudah pernah disimpan (misal draf 50%)!
            if (snap && snap.hadSavedDraft && snap.savedDraftData) {
              // Kembalikan ke progres terakhir yang tersimpan sebelumnya (misal 50%)
              assyDraftProgress.value[key] = JSON.parse(JSON.stringify(snap.savedDraftData));
              saveAssyDraftToStorage();
              triggerToast('Info', `Perubahan dibatalkan. Progres dikembalikan ke draf tersimpan sebelumnya.`, 'info');
            } else {
              delete assyDraftProgress.value[key];
              saveAssyDraftToStorage();
              triggerToast('Info', `Progres pengujian Set #${form.set_number} tidak disimpan.`, 'info');
            }
          }
          showAssySaveProgressModal.value = false;
          showAssyTestModal.value = false;
        };

        const hasEncounteredNotGoodInSession = ref(false);
        const showEarlyEndButton = ref(false);
        const showFirstNGPromptModal = ref(false);
        const showSecondNGPromptModal = ref(false);

        const assyTestForm = ref({
          tray_id: null,
          tray_code: '',
          set_number: null,
          assembly_id: null,
          part15_sn: '',
          part12_sn: '',
          part1_sn: '',
          checklist: [],
          notes: ''
        });

        const openAssyTestModal = (slot, index) => {
          if (userRole.value === 'gudang' || userRole.value === 'qc') {
            triggerToast('Akses Terbatas', 'Role ' + (userRole.value === 'qc' ? 'QC' : 'Gudang') + ' hanya memiliki hak akses pantau (Read Only) pada pengujian perakitan.', 'info');
            return;
          }
          const rawAssyStatus = String(slot.assembly_status || slot.status || '').trim();
          if (!rawAssyStatus || rawAssyStatus.toLowerCase() !== 'good') {
            triggerToast('Perakitan Belum Good', 'Unit ini belum berstatus Good di proses perakitan (Status: ' + (rawAssyStatus || 'Belum Dirakit') + '). Hanya unit berstatus Good yang dapat diuji pada Test Functional 1.', 'warning');
            return;
          }
          if (isSlotTested(slot)) {
            triggerToast('Pengujian Terkunci', 'Rakitan ini sudah selesai diuji dan terkunci dari pengujian ulang.', 'warning');
            return;
          }
          isSingleRetestMode.value = false;
          const existingDraft = getSlotDraftProgress(slot);
          if (existingDraft && existingDraft.step != null) {
            assyTestStep.value = existingDraft.step;
          } else {
            assyTestStep.value = 0;
          }

          const baseSource = (testingPointsList.value && testingPointsList.value.length) ? testingPointsList.value : defaultAssyTestItems;
          let initialChecklist = baseSource.map(item => ({
            test_num: item.test_num,
            name: item.instruction || item.name,
            description: item.output || item.description,
            category: item.category || 'Umum / Fungsional',
            status: ''
          }));

          if (existingDraft && Array.isArray(existingDraft.checklist) && existingDraft.checklist.length) {
            initialChecklist = existingDraft.checklist;
          } else if (slot.assy_test_checklist && slot.verified_status !== 'APPROVED_PROCEED_TF1' && slot.verified_status !== 'APPROVED_PROCEED') {
            let parsed = slot.assy_test_checklist;
            if (typeof parsed === 'string') {
              try { parsed = JSON.parse(parsed); } catch (_) { parsed = null; }
            }
            if (Array.isArray(parsed) && parsed.length) {
              initialChecklist = baseSource.map((item, i) => {
                const existing = parsed.find(p => p && (p.test_num === item.test_num || p.id === item.test_num)) || parsed[i];
                return {
                  test_num: item.test_num,
                  name: item.instruction || item.name,
                  description: item.output || item.description,
                  category: item.category || 'Umum / Fungsional',
                  status: existing && existing.status ? existing.status : ''
                };
              });
            }
          }

          const sessionNotes = (existingDraft && existingDraft.notes) ? existingDraft.notes : '';

          assyTestForm.value = {
            tray_id: slot.tray_id || (currentTray.value ? currentTray.value.id : null),
            tray_code: slot.tray_code || (currentTray.value ? currentTray.value.tray_code : ''),
            set_number: slot.set_number || (index + 1),
            assembly_id: slot.id || null,
            part15_sn: slot.part15_sn || '',
            part12_sn: slot.part12_sn || '',
            part1_sn: slot.part1_sn || '',
            checklist: initialChecklist,
            notes: sessionNotes
          };

          // Reset status pop-up Not Good & tombol Akhiri Sesi per sesi testing
          showFirstNGPromptModal.value = false;
          showSecondNGPromptModal.value = false;
          const hasExistingNG = initialChecklist.some(t => {
            const st = String(t.status || '').toUpperCase();
            return st === 'NOT_GOOD' || st === 'NOT GOOD' || st === 'NG' || st === 'FAIL';
          });
          if (hasExistingNG) {
            hasEncounteredNotGoodInSession.value = true;
            showEarlyEndButton.value = true;
          } else {
            hasEncounteredNotGoodInSession.value = false;
            showEarlyEndButton.value = false;
          }

          // Simpan snapshot kondisi awal sesi ini untuk deteksi perubahan saat tombol Kembali ditekan
          initialSessionSnapshot.value = {
            step: assyTestStep.value,
            checklist: JSON.parse(JSON.stringify(initialChecklist)),
            notes: sessionNotes,
            hadSavedDraft: !!existingDraft,
            savedDraftData: existingDraft ? JSON.parse(JSON.stringify(existingDraft)) : null
          };

          showAssyTestModal.value = true;
          try {
            history.pushState({ type: 'assy_test', step: assyTestStep.value }, '', getCurrentTabHash());
            assyTestHistoryDepth.value = 1;
          } catch (_) { }
        };

        const chooseAssyTestStatus = (status) => {
          if (assyTestForm.value.checklist && assyTestForm.value.checklist[assyTestStep.value]) {
            assyTestForm.value.checklist[assyTestStep.value].status = status;
          }

          // Kondisi 1: Status "Not Good" Pertama Kali dalam satu sesi testing
          if (status === 'NOT_GOOD' && !hasEncounteredNotGoodInSession.value) {
            hasEncounteredNotGoodInSession.value = true;
            showFirstNGPromptModal.value = true;
            return; // Tahan perpindahan step, tunggu pilihan user: [Iya] atau [Tidak]
          }

          assyTestStep.value++;
          try {
            history.pushState({ type: 'assy_test', step: assyTestStep.value }, '', getCurrentTabHash());
            assyTestHistoryDepth.value++;
          } catch (_) { }
        };

        const handleFirstNGChoice = async (shouldEndEarly) => {
          if (shouldEndEarly) {
            // Jika klik [Iya]: Langsung akhiri sesi testing, ubah statusset produk menjadi "Hold"
            await confirmEndSessionEarly();
          } else {
            // Jika klik [Tidak]: Tutup pop-up, lanjutkan ke langkah berikutnya, munculkan button [Akhiri Sesi]
            showFirstNGPromptModal.value = false;
            showEarlyEndButton.value = true;
            assyTestStep.value++;
            try {
              history.pushState({ type: 'assy_test', step: assyTestStep.value }, '', getCurrentTabHash());
              assyTestHistoryDepth.value++;
            } catch (_) { }
          }
        };

        const confirmEndSessionEarly = async () => {
          if (isSubmittingAssyTest.value) return;

          // Penanganan Data: Untuk sisa task/test yang belum dikerjakan, set statusnya menjadi "Pending"
          if (assyTestForm.value && Array.isArray(assyTestForm.value.checklist)) {
            const currentStep = assyTestStep.value;
            assyTestForm.value.checklist.forEach((t, idx) => {
              const st = String(t.status || '').trim().toUpperCase();
              if (idx > currentStep) {
                // Tests AFTER current step: force to Pending regardless of existing status
                // (may have leftover 'GOOD' from previous session data)
                t.status = 'Pending';
              } else if (!st || st === '' || st === 'PENDING') {
                // Tests at/before current step with no status: also Pending
                t.status = 'Pending';
              }
            });
          }

          showFirstNGPromptModal.value = false;
          showSecondNGPromptModal.value = false;

          await submitAssyTest();
        };

        const prevAssyTestStep = (isFromPopState = false) => {
          if (assyTestStep.value > 0) {
            assyTestStep.value--;
            if (!isFromPopState && assyTestHistoryDepth.value > 0) {
              assyTestHistoryDepth.value--;
              programmaticBackCount++;
              try {
                window.history.back();
              } catch (_) { }
            }
          }
        };

        const nextAssyTestStep = () => {
          if (assyTestStep.value < assyTotalSteps.value) {
            assyTestStep.value++;
          }
        };

        const setAllAssyTestStatus = (status) => {
          (assyTestForm.value.checklist || []).forEach(t => {
            t.status = status;
          });
        };

        const assyTestCalculatedFailures = computed(() => {
          return (assyTestForm.value.checklist || [])
            .filter(t => t.status === 'NOT_GOOD' || t.status === 'NOT GOOD' || t.status === 'NG' || t.status === 'FAIL')
            .map(t => t.test_num);
        });

        const assyTestGeneratedFailureReason = computed(() => {
          const fails = assyTestCalculatedFailures.value;
          if (fails.length === 0) return '';
          if (fails.length === 1) return `Gagal dalam Test ${fails[0]}`;
          return `Gagal dalam Test: ${fails.join(', ')}`;
        });

        const submitAssyTest = async () => {
          if (isSubmittingAssyTest.value) return;
          isSubmittingAssyTest.value = true;
          try {
            const payload = {
              tray_id: assyTestForm.value.tray_id,
              set_number: assyTestForm.value.set_number,
              assembly_id: assyTestForm.value.assembly_id,
              part15_sn: assyTestForm.value.part15_sn,
              part12_sn: assyTestForm.value.part12_sn,
              part1_sn: assyTestForm.value.part1_sn,
              test_checklist: assyTestForm.value.checklist,
              notes: assyTestForm.value.notes
            };
            const res = await fetchAPI('/assembly/submit-test', {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (res.ok) {
              const draftKey = `${assyTestForm.value.tray_id}_${assyTestForm.value.set_number}`;
              if (assyDraftProgress.value[draftKey]) {
                delete assyDraftProgress.value[draftKey];
                saveAssyDraftToStorage();
              }
              const isGoodTest = (res.data && res.data.assy_test_status === 'Good') || (res.message && res.message.includes('(Good)'));
              triggerToast(isGoodTest ? 'Uji Fungsional Selesai' : 'Uji Fungsional Hold', res.message || 'Hasil uji berhasil disimpan.', isGoodTest ? 'success' : 'warn');
              showAssyTestModal.value = false;
              await loadAssemblyTray();
              await loadQCPendingItems();
              await loadProductionHoldItems();
              await loadPendingVerifications();
              await loadTrays();
              await loadTravelerTray();
            } else {
              triggerToast('Gagal Simpan Uji', res.error || 'Terjadi kesalahan sistem', 'error');
              if (res.error && (res.error.includes('selesai diuji') || res.error.includes('terkunci'))) {
                const draftKey = `${assyTestForm.value.tray_id}_${assyTestForm.value.set_number}`;
                if (assyDraftProgress.value[draftKey]) {
                  delete assyDraftProgress.value[draftKey];
                  saveAssyDraftToStorage();
                }
                showAssyTestModal.value = false;
                await loadAssemblyTray();
              }
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSubmittingAssyTest.value = false;
          }
        };

        const activeAssyQCDocSlot = ctx.activeAssyQCDocSlot || ref(null);

        const testedGoodSlots = computed(() => {
          // ATURAN BAKU: HANYA BARANG BERKONDISI GOOD DI PERAKITAN YANG MASUK KE TF 1!
          // Unit yang belum dirakit, hold, rework, scrap, atau dirakit ulang di perakitan TIDAK MASUK ke TF 1.
          // Dan jika unit sudah lolos/pernah diuji di TF 1, perubahan status di perakitan tidak memengaruhi TF 1.
          return (traySlots.value || []).filter(s => {
            if (!s || !s.id) return false; // Belum selesai dirakit di perakitan
            const hasAssyTest = Boolean(s.assy_doc_number || (s.assy_test_status && s.assy_test_status !== 'PENDING' && s.assy_test_status !== 'Pending'));
            return hasAssyTest || s.status === 'Good';
          });
        });

        const countTestedGoodInTray = computed(() => {
          return (testedGoodSlots.value || []).filter(s => s && isSlotTF1Good(s)).length;
        });

        const countTestedHoldInTray = computed(() => {
          return (testedGoodSlots.value || []).filter(s => s && isSlotTF1Hold(s)).length;
        });

        const countTestedRUInTray = computed(() => {
          return (testedGoodSlots.value || []).filter(s => s && (s.assy_test_status === 'RU' || s.qc_status === 'RU')).length;
        });

        const countUntestedInTray = computed(() => {
          return (testedGoodSlots.value || []).filter(s => s && !isSlotTested(s)).length;
        });

        const getTestingTrayCounts = (t) => {
          if (!t) return { tested: 0, total: 0 };
          if (currentTray.value && (currentTray.value.tray_code === t.tray_code || currentTray.value.id === t.id)) {
            const total = testedGoodSlots.value ? testedGoodSlots.value.length : (t.good_assembly_count || 0);
            const tested = (countTestedGoodInTray.value || 0) + (countTestedHoldInTray.value || 0) + (countTestedRUInTray.value || 0);
            return { tested, total };
          }
          return {
            tested: t.tested_count || 0,
            total: t.good_assembly_count || 0
          };
        };

        const getTestingSetProgress = (t) => {
          const c = getTestingTrayCounts(t);
          return `${c.tested}/${c.total}`;
        };

        const selectAndPrintAssyQC = async (tray, slot) => {
          if (tray && tray.tray_code) {
            selectedTrayCode.value = tray.tray_code;
          }
          activePrintDocType.value = 'assy_qc';
          activeAssyQCDocSlot.value = slot || null;
          await loadTravelerTray();
          navigateTo('traveler');
          triggerToast('Dokumen Siap Cetak', `Membuka Dokumen Uji Assy No. ${slot && slot.assy_doc_number ? slot.assy_doc_number : '-'}`, 'info');
        };

        const getTestItemParamStatus = (item, paramNum) => {
          if (!item) return 'GOOD';

          // Sesuai permintaan user: history/dokumen uji tetap tampilkan data aktual (tidak di-bypass lolos semua)
          let checklist = item.assy_test_checklist || item.test_checklist;
          if (typeof checklist === 'string') {
            try {
              checklist = JSON.parse(checklist);
            } catch (_) { }
          }

          if (Array.isArray(checklist) && checklist.length > 0) {
            const target = checklist.find(c => c && (Number(c.test_num) === Number(paramNum) || Number(c.id) === Number(paramNum)));
            if (target) {
              const st = String(target.status || '').toUpperCase();
              if (st === 'NOT_GOOD' || st === 'NOT GOOD' || st === 'NG' || st === 'FAIL' || st === 'NOT_OK') {
                return 'NOT_GOOD';
              }
              if (st === 'PENDING' || st === '-' || !st) {
                return 'PENDING';
              }
              return 'GOOD';
            }
          }

          const reasons = (item.assy_failure_reasons || item.failure_reasons || item.reason || '').toLowerCase();
          if (reasons.includes(`test ${paramNum}`) || reasons.includes(`uji ${paramNum}`) || reasons.includes(`parameter ${paramNum}`) || reasons.includes(`tahap ${paramNum}`)) {
            return 'NOT_GOOD';
          }

          return 'GOOD';
        };

    // ===================================================================
    // EXCEL EXPORT ENGINE FOR TF 1 (TEST FUNCTIONAL 1)
    // ===================================================================
    const exportTF1ToExcel = async () => {
      try {
        let rawItems = [];
        const trayCode = (currentTray && currentTray.value) ? currentTray.value.tray_code : '';
        const operatorStr = (currentTray && currentTray.value && currentTray.value.assigned_operator) ? getOperatorFullName(currentTray.value.assigned_operator) : '-';

        // 1. Tentukan sumber data dari traySlots / testedGoodSlots
        if (traySlots && traySlots.value && traySlots.value.length > 0) {
          rawItems = (testedGoodSlots && testedGoodSlots.value && testedGoodSlots.value.length > 0)
            ? testedGoodSlots.value
            : traySlots.value.filter(s => s && s.id);
        }

        // Jika tray belum terpilih atau kosong, fetch data dari /assy-tests
        if (rawItems.length === 0) {
          try {
            const res = await fetchAPI('/assy-tests');
            if (res && res.ok && Array.isArray(res.data)) {
              rawItems = res.data;
            }
          } catch (err) {
            console.error('Fetch assy-tests error:', err);
          }
        }

        if (rawItems.length === 0) {
          triggerToast('Data Kosong', 'Tidak ada data pengujian TF 1 untuk diekspor ke Excel.', 'warn');
          return;
        }

        const todayStr = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateOnly === 'function')
          ? window.utils.formatDateOnly(new Date()).replace(/\//g, '-')
          : new Date().toISOString().substring(0, 10);
        const filename = `Laporan_TF1_Operator_Assembly_${trayCode ? (trayCode.replace(/\//g, '_') + '_') : ''}${todayStr}.xlsx`;
        const title = trayCode
          ? `LAPORAN TEST FUNCTIONAL 1 (TF 1) - TRAY ${trayCode}`
          : 'LAPORAN TEST FUNCTIONAL 1 (TF 1) - OPERATOR ASSEMBLY';

        const columns = [
          { header: 'No', width: 6, align: 'center' },
          { header: 'No Dokumen TF 1', width: 22, align: 'center' },
          { header: 'No Dokumen Asal / Tray', width: 20, align: 'center' },
          { header: 'Set #', width: 8, align: 'center' },
          { header: 'SN Part 15 (P9)', width: 20, align: 'center' },
          { header: 'PN Part 1', width: 16, align: 'center' },
          { header: 'PN Part 12', width: 16, align: 'center' },
          { header: 'Operator Tester', width: 22, align: 'left' },
          { header: 'Waktu Pengujian', width: 20, align: 'center' },
          { header: 'Status Aktual', width: 20, align: 'center' },
          { header: 'Hasil Parameter Uji', width: 26, align: 'left' },
          { header: 'Kendala / Catatan', width: 30, align: 'left' }
        ];

        let countGood = 0;
        let countHold = 0;
        let countRU = 0;
        let countPending = 0;

        const rows = rawItems.map((slot, idx) => {
          // Status aktual TF 1
          let statusAktual = 'BELUM DIUJI';
          if (typeof isSlotTF1Good === 'function' && isSlotTF1Good(slot)) {
            statusAktual = 'LULUS (GOOD)';
            countGood++;
          } else if (typeof isSlotTF1Hold === 'function' && isSlotTF1Hold(slot)) {
            statusAktual = 'HOLD TF 1';
            countHold++;
          } else if ((typeof isReassemblySet === 'function' && isReassemblySet(slot)) || slot.assy_test_status === 'RU' || slot.qc_status === 'RU') {
            statusAktual = 'DIRAKIT ULANG (RU)';
            countRU++;
          } else if (slot.status === 'Good' || slot.assy_test_status === 'Good' || slot.status === 'LULUS' || slot.status === 'OK') {
            statusAktual = 'LULUS (GOOD)';
            countGood++;
          } else if (slot.status === 'Hold' || slot.assy_test_status === 'Hold' || slot.status === 'HOLD_TF1') {
            statusAktual = 'HOLD TF 1';
            countHold++;
          } else {
            countPending++;
          }

          // Format Dokumen TF 1
          let docTF1 = slot.doc_number || slot.assy_doc_number;
          const tc = slot.tray_code || trayCode;
          if (!docTF1 && tc) {
            const suffix = tc.split('/').pop();
            docTF1 = tc.startsWith('ASSY/QC/') ? tc : `ASSY/QC/${suffix}`;
          }

          // Format Dokumen Asal / Tray
          const originDoc = slot.tray_code || trayCode || '-';

          // Parameter checklist info
          let paramInfo = '-';
          let cl = slot.test_checklist || slot.assy_test_checklist;
          if (typeof cl === 'string') {
            try { cl = JSON.parse(cl); } catch (_) { cl = null; }
          }
          if (Array.isArray(cl) && cl.length > 0) {
            const passedCount = cl.filter(c => c && (c.status === 'GOOD' || c.status === 'OK' || c.status === 'PASS')).length;
            const failedItems = cl.filter(c => c && (c.status === 'NOT_GOOD' || c.status === 'HOLD' || c.status === 'FAIL' || c.status === 'REWORK'));
            if (failedItems.length > 0) {
              paramInfo = `${passedCount}/${cl.length} OK (Gagal: ${failedItems.map(f => '#' + (f.test_num || f.id)).join(', ')})`;
            } else {
              paramInfo = `${passedCount}/${cl.length} Parameter Lolos`;
            }
          } else if (statusAktual === 'LULUS (GOOD)') {
            paramInfo = 'Semua Parameter Lolos';
          }

          const tester = getOperatorFullName(slot.assy_tester_username || slot.tester_username || slot.pic_assembly || operatorStr) || '-';
          const testTime = (typeof window.utils !== 'undefined' && typeof window.utils.formatDateTime === 'function')
            ? window.utils.formatDateTime(slot.assy_tested_at || slot.tested_at || slot.created_at || slot.finished_at)
            : (slot.assy_tested_at || slot.tested_at || slot.created_at || slot.finished_at || '-');

          const catatan = slot.assy_failure_reasons || slot.failure_reasons || slot.reason || slot.notes || '-';

          return [
            idx + 1,
            docTF1 || '-',
            originDoc,
            slot.set_number ? ('#' + slot.set_number) : ('#' + (idx + 1)),
            slot.part15_sn || slot.sn || '-',
            slot.part1_sn || '-',
            slot.part12_sn || '-',
            tester,
            testTime,
            statusAktual,
            paramInfo,
            catatan
          ];
        });

        const summaryText = `Total Set: ${rawItems.length}   |   Lulus (Good): ${countGood}   |   Hold Uji: ${countHold}   |   Dirakit Ulang (RU): ${countRU}   |   Belum Diuji: ${countPending}`;

        if (window.utils && typeof window.utils.exportToExcel === 'function') {
          await window.utils.exportToExcel({
            filename,
            sheetName: 'TF 1 Testing',
            title,
            subtitle: `Dicetak dari Menu Test Functional 1 (TF 1) • Tanggal: ${new Date().toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB`,
            summary: summaryText,
            columns,
            rows,
            triggerToast
          });
        }
      } catch (err) {
        console.error('exportTF1ToExcel error:', err);
        triggerToast('Gagal Ekspor', err.message || 'Terjadi kesalahan saat mengekspor Excel.', 'error');
      }
    };

    // ===================================================================
    // TAB INITIALIZATION HELPER
    // ===================================================================
    const initTF1Tab = () => {
      if (window.logger) {
        logger.info('TF1', 'Memuat data Test Functional 1 (Assy Testing)...');
      }
      if (typeof loadTrays === 'function') {
        loadTrays().then(() => {
          if (filteredTraysList && filteredTraysList.value && filteredTraysList.value.length > 0) {
            let defaultTray = filteredTraysList.value.find(t => t.tray_code === (selectedTrayCode ? selectedTrayCode.value : ''));
            if (!defaultTray) {
              defaultTray = filteredTraysList.value.find(t => (t.good_assembly_count || 0) > 0) || filteredTraysList.value[0];
            }
          }
        });
      }
    };

    // ===================================================================
    // MODULE EXPORTS
    // ===================================================================
    return {
      initTF1Tab,
      defaultAssyTestItems,
      isSingleRetestMode,
      singleRetestTestNum,
      singleRetestStatus,
      singleRetestReturnSource,
      singleRetestSlot,
      singleRetestNotes,
      isSubmittingSingleRetest,
      assyTotalSteps,
      currentActiveChecklistItem,
      startSingleRetest,
      cancelSingleRetest,
      setSingleRetestStatus,
      setSingleRetestAndSubmit,
      submitSingleRetest,
      showAssyTestModal,
      isSubmittingAssyTest,
      assyTestStep,
      assyTestTableViewMode,
      qcTestTableViewMode,
      showAssySaveProgressModal,
      assyDraftProgress,
      saveAssyDraftToStorage,
      hasSlotTestingHistory,
      isSlotTested,
      isSlotTF1Hold,
      isSlotTF1Good,
      getSlotDraftProgress,
      handleAssyBackNavigation,
      confirmSaveAssyProgress,
      showFirstNGPromptModal,
      showSecondNGPromptModal,
      showEarlyEndButton,
      handleFirstNGChoice,
      confirmEndSessionEarly,
      assyTestForm,
      openAssyTestModal,
      chooseAssyTestStatus,
      prevAssyTestStep,
      nextAssyTestStep,
      setAllAssyTestStatus,
      assyTestCalculatedFailures,
      assyTestGeneratedFailureReason,
      submitAssyTest,
      activeAssyQCDocSlot,
      testedGoodSlots,
      countTestedGoodInTray,
      countTestedHoldInTray,
      countTestedRUInTray,
      countUntestedInTray,
      getTestingTrayCounts,
      getTestingSetProgress,
      selectAndPrintAssyQC,
      getTestItemParamStatus,
      exportTF1ToExcel
    };
  };

  // Expose global constants for backwards compatibility
  if (typeof window.defaultAssyTestItems === 'undefined') {
    // will be initialized on createTF1Module or fallback
  }
})(window);
