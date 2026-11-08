/**
 * Admin & Master Pengaturan Module (Fase 2J)
 * Sistem Final Assembly & Traceability P9 - NKRI
 *
 * Encapsulates:
 * 1. User Management & Permissions (usersList, userForm, editUser, saveUser, deleteUser, resetUserSession, loadUsersList)
 * 2. Admin Unsaved Navigation Guard (isUserFormDirty, isTestingPointFormDirty, confirmAdminUnsavedNavigation, cancelAdminUnsavedNavigation)
 * 3. Master Stock Data & Operations (masterParts, stockSummary, masterStockList, masterFilter, loadMasterStocksSummary, fetchMasterStockList, loadMasterParts, loadMasterData)
 * 4. Add Part Single & Bulk Registration (addPartTab, addPartBulkMode, addPartForm, addPartBulkForm, submitAddSinglePart, submitAddBulkPart, deleteRecentlyAddedPart, loadRecentlyAddedParts)
 * 5. Master DB Status Drilldown Modal (showStatusDrillModal, drillData, filteredDrillList, openStatusDrilldown, exportDrillListToExcel)
 * 6. Admin Sets & Replacements Verification (adminPendingRequests, adminPendingSets, adminPendingQCSets, allAdminSets, filteredAdminSetsOnly, openAdminVerifySetModal, verifyAdminSinglePart, verifyAllPartsInActiveSet, adminApproveReplacement, adminRejectReplacement, adminApproveAssyRereg, adminApproveHoldItem, loadPendingVerifications)
 * 7. Status Override & Reregister Modals (showOverrideModal, submitOverrideStatus, showReregisterSetModal, confirmReregisterSet, showReregisterModal, submitReregisterPart)
 * 8. Audit Logs & Downtime Logs (auditLogsList, auditFilter, filteredAuditLogs, loadAuditLogs, downtimeLogsList, downtimeFilter, loadDowntimeLogs)
 * 9. Testing Points Editor & Guide Carousel (testingPointsList, testingPointForm, loadTestingPoints, saveTestingPoint, deleteTestingPoint, showInstructionGuideModal, openInstructionGuideModal)
 * 10. Admin Danger Zone Database Reset (showResetDbModal, isResettingDb, confirmResetDatabase)
 * 11. CMS Portal & Server Diagnostics (cmsActiveTab, cmsErrorLogs, cmsSlowQueries, cmsAnomalies, loadCmsPortalData, fetchCmsErrorLogs, clearCmsErrorLogs, fetchCmsSlowQueries, clearCmsSlowQueries, scanCmsAnomalies, repairCmsAnomalies, clearDatabaseTrays, clearDowntimeLogs, clearAuditLogs, fetchCmsDoSettings, saveCmsDoSettings)
 * 12. Admin Pagination Computeds (pagedAdminSets, pagedAdminParts, pagedAdminRepl, pagedAuditLogs, pagedDowntime, pagedTestPoint, pagedUsers, pagedMasterStock and their totalPg... counterparts)
 */
(function (window) {
  'use strict';

  window.createAdminModule = function (ctx) {
    const { ref, reactive, computed, watch, nextTick } = ctx.Vue || Vue;

    // External dependencies provided by app context
    const fetchAPI = ctx.fetchAPI || window.api?.fetchAPI;
    const triggerToast = ctx.triggerToast || function (title, msg, type) {
      if (window.Swal) Swal.fire({ title, text: msg, icon: type || 'info' });
      else alert((title || '') + ': ' + (msg || ''));
    };
    const showToast = ctx.showToast || triggerToast;
    const showConfirmCard = ctx.showConfirmCard || (async () => false);
    const showPromptCard = ctx.showPromptCard || (async () => null);

    const getTodayDateStr = ctx.getTodayDateStr || (() => new Date().toISOString().substring(0, 10));
    const formatDateTime = ctx.formatDateTime || (d => String(d));
    const getOperatorFullName = ctx.getOperatorFullName || (u => u || '-');

    const pgState = ctx.pgState;
    const userRole = ctx.userRole || ref('');
    const operatorName = ctx.operatorName || ref('');
    const userName = ctx.userName || ref('');
    const userPermissions = ctx.userPermissions || ref([]);
    const currentTab = ctx.currentTab || ref('admin_verif');
    const adminSubTab = ctx.adminSubTab || ref('verify_set');
    const navigateTo = ctx.navigateTo || (() => {});

    const currentTray = ctx.currentTray || ref(null);
    const currentTrayParts = ctx.currentTrayParts || ref([]);
    const currentTrayAssemblies = ctx.currentTrayAssemblies || ref([]);
    const selectedTrayCode = ctx.selectedTrayCode || ref('');
    const inProgressMap = ctx.inProgressMap || ref({});

    const loadTrays = ctx.loadTrays || (async () => {});
    const loadQCPendingItems = ctx.loadQCPendingItems || (async () => {});
    const loadProductionHoldItems = ctx.loadProductionHoldItems || (async () => {});
    const loadPendingReassembly = ctx.loadPendingReassembly || (async () => {});
    const loadAssemblyResults = ctx.loadAssemblyResults || (async () => {});
    const loadTravelerTray = ctx.loadTravelerTray || (async () => {});
    const isReassemblySet = ctx.isReassemblySet || (() => false);

    // Hoisted guard / modal states
    let isUserFormDirty = () => false;
    let isTestingPointFormDirty = () => false;
    let confirmAdminUnsavedNavigation = async () => {};
    let cancelAdminUnsavedNavigation = () => {};
    const showResetDbModal = ref(false);
    const isResettingDb = ref(false);

    // Permission helpers & Unsaved Navigation dependencies
    const defaultPermissions = ctx.defaultPermissions || function (role = 'User') {
      const r = String(role || '').toLowerCase().trim();
      const isAdmin = (r === 'admin' || r === 'super admin');
      return {
        warehouse: isAdmin ? 2 : 0,
        work_order: isAdmin ? 2 : 0,
        production_hold: isAdmin ? 2 : 1,
        returns: isAdmin ? 2 : 0,
        warehouse_receipt: isAdmin ? 2 : 0,
        traveler: isAdmin ? 2 : 1,
        master: isAdmin ? 2 : 1,
        report: isAdmin ? 2 : 1,
        history: isAdmin ? 2 : 1,
        delivery_order: isAdmin ? 2 : 0,
        assembly: isAdmin ? 2 : 0,
        reassembly: isAdmin ? 2 : 0,
        assy_testing: isAdmin ? 2 : 0,
        qc: isAdmin ? 2 : 0,
        functional_test: isAdmin ? 2 : 0,
        users: isAdmin ? 2 : 0,
        verify_set: isAdmin ? 2 : 0,
        verify_items: isAdmin ? 2 : 0,
        audit: isAdmin ? 2 : 0,
        downtime: isAdmin ? 2 : 0,
        testing_points: isAdmin ? 2 : 0,
        add_part: isAdmin ? 2 : 0
      };
    };

    const parsePermValue = ctx.parsePermValue || function (v) {
      if (v === undefined || v === null) return undefined;
      if (typeof v === 'number') return v;
      const s = String(v).toLowerCase().trim();
      if (s === 'full' || s === '2' || s === 'true') return 2;
      if (s === 'read' || s === 'read-only' || s === '1') return 1;
      if (s === 'none' || s === '0' || s === 'false') return 0;
      return Number(v) || 0;
    };

    const openPermissionCards = ctx.openPermissionCards || ref({
      gudang: true,
      perakitan: false,
      qc: false,
      admin: false
    });

    const loadOperatorsList = ctx.loadOperatorsList || (async () => {});

    const formatExcelDateTime = ctx.formatExcelDateTime || function (dt) {
      if (!dt) return '-';
      const d = new Date(dt);
      if (isNaN(d.getTime())) return String(dt);
      const day = d.getDate();
      const month = d.getMonth() + 1;
      const year = d.getFullYear();
      const hours = String(d.getHours()).padStart(2, '0');
      const mins = String(d.getMinutes()).padStart(2, '0');
      const secs = String(d.getSeconds()).padStart(2, '0');
      return `${day}/${month}/${year}, ${hours}.${mins}.${secs}`;
    };

    const defaultAssyTestItems = ctx.defaultAssyTestItems || window.defaultAssyTestItems || [];

    const selectedAssemblyItem = ctx.selectedAssemblyItem || (ctx.getSelectedAssemblyItem ? { get value() { return ctx.getSelectedAssemblyItem(); } } : ref(null));
    const selectedHoldItem = ctx.selectedHoldItem || (ctx.getSelectedHoldItem ? { get value() { return ctx.getSelectedHoldItem(); } } : ref(null));

    const showAdminUnsavedModal = ctx.showAdminUnsavedModal || ref(false);
    const adminUnsavedContext = ctx.adminUnsavedContext || ref('');
    const pendingAdminNavigation = ctx.pendingAdminNavigation || ref(null);
    const isSavingAdminUnsaved = ctx.isSavingAdminUnsaved || ref(false);
    let bypassAdminUnsavedCheck = ctx.bypassAdminUnsavedCheck || false;

        // Master Stock State
        const masterParts = ref([]);
        const stockSummary = ref({});
        const masterStockList = ref([]);
        const masterFilter = ref({
          type: '',
          status: '',
          replaced: '',
          q: '',
          page: 1,
          total: 0,
          totalPages: 1
        });

        const replacedPartsTotalCount = computed(() => {
          return ((stockSummary.value['1']?.replaced_count || 0) +
            (stockSummary.value['12']?.replaced_count || 0) +
            (stockSummary.value['15']?.replaced_count || 0));
        });

        // User Management & Audit Trail State (Admin Only)
        const usersList = ref([]);
        const auditLogsList = ref([]);
        const editingUserId = ref(null);
        const showUserPw = ref(false);
        const userForm = ref({
          nik: '',
          full_name: '',
          username: '',
          password: '',
          role: 'User',
          department: 'assembly',
          permissions: defaultPermissions()
        });

        const loadUsersList = async () => {
          try {
            const json = await fetchAPI('/admin/users');
            if (json.ok) {
              usersList.value = json.data;
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const loadAuditLogs = async () => {
          try {
            const json = await fetchAPI('/admin/audit-logs');
            if (json.ok) {
              auditLogsList.value = json.data || [];
            } else {
              triggerToast('Gagal', json.error || 'Gagal memuat log audit', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const userFormInitialSnapshot = ref(null);

        const captureUserSnapshot = () => {
          userFormInitialSnapshot.value = {
            id: editingUserId.value,
            nik: userForm.value.nik || '',
            full_name: userForm.value.full_name || '',
            username: userForm.value.username || '',
            role: userForm.value.role || 'gudang',
            password: ''
          };
        };

        isUserFormDirty = () => {
          if (currentTab.value !== 'users' || adminSubTab.value !== 'users') return false;
          if (!userForm.value) return false;
          const f = userForm.value;
          if (!editingUserId.value) {
            // Mode Tambah User Baru: dirty jika ada field teks yang telah diisi
            return Boolean(
              (f.nik && f.nik.trim().length > 0) ||
              (f.full_name && f.full_name.trim().length > 0) ||
              (f.username && f.username.trim().length > 0) ||
              (f.password && f.password.trim().length > 0)
            );
          }
          // Mode Edit User: bandingkan dengan snapshot saat edit dimulai
          const snap = userFormInitialSnapshot.value;
          if (!snap) return false;
          return Boolean(
            (f.nik || '') !== (snap.nik || '') ||
            (f.full_name || '') !== (snap.full_name || '') ||
            (f.username || '') !== (snap.username || '') ||
            (f.role || 'User') !== (snap.role || 'User') ||
            (f.password && f.password.trim().length > 0)
          );
        };

        const resetUserForm = () => {
          editingUserId.value = null;
          userForm.value = {
            nik: '',
            full_name: '',
            username: '',
            password: '',
            role: 'User',
            department: 'Assembly',
            permissions: defaultPermissions()
          };
          showUserPw.value = false;
          userFormInitialSnapshot.value = null;
          openPermissionCards.value = {
            gudang: true,
            perakitan: false,
            qc: false,
            admin: false
          };
        };

        const editUser = (u) => {
          editingUserId.value = u.id;
          let p = defaultPermissions(u.role);
          if (u.permissions) {
            let raw = u.permissions;
            if (typeof raw === 'string') {
              try { raw = JSON.parse(raw); } catch (_) { }
            }
            if (raw && typeof raw === 'object') {
              Object.keys(raw).forEach(k => {
                const parsed = parsePermValue(raw[k]);
                if (parsed !== undefined) p[k] = parsed;
              });
            }
          }
          let dept = u.department || 'Assembly';
          const d = String(dept).toLowerCase();
          if (d.includes('warehouse') || d.includes('gudang')) dept = 'Warehouse';
          else if (d.includes('admin')) dept = 'Administrator';
          else dept = 'Assembly';

          userForm.value = {
            nik: u.nik || '',
            full_name: u.full_name || '',
            username: u.username || '',
            password: '',
            role: u.role || 'User',
            department: dept,
            permissions: p
          };
          showUserPw.value = false;
          captureUserSnapshot();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        const saveUser = async () => {
          try {
            if (editingUserId.value) {
              // Update User
              if (!userForm.value.full_name.trim() || !userForm.value.username.trim()) {
                triggerToast('Validasi', 'Nama lengkap dan username tidak boleh kosong!', 'error');
                return false;
              }
              const isFullAdmin = Boolean(userForm.value.permissions && (userForm.value.permissions.users > 0 || userForm.value.permissions.audit > 0));
              const effectiveRole = (userForm.value.role && userForm.value.role !== 'User') ? userForm.value.role : (isFullAdmin ? 'Admin' : 'User');
              const payload = {
                id: editingUserId.value,
                nik: userForm.value.nik,
                full_name: userForm.value.full_name,
                username: userForm.value.username,
                role: effectiveRole,
                department: userForm.value.department,
                permissions: JSON.stringify(userForm.value.permissions)
              };
              if (userForm.value.password.trim()) {
                payload.password = userForm.value.password.trim();
              }
              const json = await fetchAPI('/admin/users/update', {
                method: 'POST',
                body: JSON.stringify(payload)
              });
              if (json.ok) {
                triggerToast('Berhasil', 'Data pengguna berhasil diperbarui!', 'success');
                const currentU = String(userName.value || '').toLowerCase();
                const formU = String(userForm.value.username || '').toLowerCase();
                if (formU === currentU) {
                  const newPerms = { ...userForm.value.permissions };
                  userPermissions.value = newPerms;
                  localStorage.setItem('permissions', JSON.stringify(newPerms));
                  userRole.value = effectiveRole;
                  localStorage.setItem('role', effectiveRole);
                }
                resetUserForm();
                await loadUsersList();
                await loadOperatorsList();
                return true;
              } else {
                triggerToast('Gagal', json.error || 'Gagal update user', 'error');
                return false;
              }
            } else {
              // Create New User
              if (!userForm.value.full_name.trim() || !userForm.value.username.trim()) {
                triggerToast('Validasi', 'Nama lengkap dan username wajib diisi!', 'error');
                return false;
              }
              if (!userForm.value.password.trim()) {
                triggerToast('Validasi', 'Password wajib diisi untuk pengguna baru!', 'error');
                return false;
              }
              const isFullAdmin = Boolean(userForm.value.permissions && (userForm.value.permissions.users > 0 || userForm.value.permissions.audit > 0));
              const effectiveRole = (userForm.value.role && userForm.value.role !== 'User') ? userForm.value.role : (isFullAdmin ? 'Admin' : 'User');
              const json = await fetchAPI('/admin/users', {
                method: 'POST',
                body: JSON.stringify({
                  nik: userForm.value.nik,
                  full_name: userForm.value.full_name,
                  username: userForm.value.username,
                  password: userForm.value.password,
                  role: effectiveRole,
                  department: userForm.value.department,
                  permissions: JSON.stringify(userForm.value.permissions)
                })
              });
              if (json.ok) {
                triggerToast('Berhasil', 'Pengguna baru berhasil ditambahkan!', 'success');
                resetUserForm();
                await loadUsersList();
                await loadOperatorsList();
                return true;
              } else {
                triggerToast('Gagal', json.error || 'Gagal buat user', 'error');
                return false;
              }
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
            return false;
          }
        };

        const deleteUser = async (u) => {
          const ok = await showConfirmCard({
            title: 'Hapus Pengguna',
            message: `Apakah Anda yakin ingin menghapus pengguna '${u.full_name || u.username}' (${u.username})?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/users/delete', {
              method: 'POST',
              body: JSON.stringify({ id: u.id })
            });
            if (json.ok) {
              triggerToast('Berhasil', 'Pengguna berhasil dihapus!', 'success');
              await loadUsersList();
              await loadOperatorsList();
            } else {
              triggerToast('Gagal', json.error || 'Gagal menghapus user', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const resetUserSession = async (u) => {
          const ok = await showConfirmCard({
            title: 'Reset Sesi Pengguna',
            message: `Buka kunci / reset sesi aktif untuk pengguna '${u.username}' (${u.full_name || u.username})? Akun ini akan dapat login kembali di perangkat mana saja.`,
            icon: 'fa-solid fa-key',
            iconBg: 'bg-amber-100 text-amber-600',
            confirmText: 'Reset Sesi',
            confirmBtnClass: 'bg-amber-600 hover:bg-amber-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/users/reset-session', {
              method: 'POST',
              body: JSON.stringify({ username: u.username, user_id: u.id })
            });
            if (json && json.ok) {
              triggerToast('Sesi Berhasil Di-reset', json.message || `Sesi pengguna ${u.username} berhasil dibuka kuncinya.`, 'success');
              await loadUsersList();
            } else {
              triggerToast('Gagal Reset Sesi', json ? json.error : 'Gagal mereset sesi pengguna.', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Master DB Override State (Gudang & Admin)
        const showOverrideModal = ref(false);
        const overrideForm = ref({
          part_type: '',
          sn: '',
          old_status: '',
          new_status: 'Tersedia',
          reason: ''
        });

        const openOverrideModal = (item) => {
          overrideForm.value = {
            part_type: item.part_type,
            sn: item.sn,
            old_status: item.effective_status || item.stock_status || item.status || 'Unknown',
            new_status: 'Tersedia',
            reason: ''
          };
          showOverrideModal.value = true;
        };

        const submitOverrideStatus = async () => {
          if (!overrideForm.value.reason || !overrideForm.value.reason.trim()) {
            triggerToast('Alasan Diperlukan', 'Harap isi alasan / catatan untuk permohonan override status part!', 'warn');
            return;
          }
          try {
            const json = await fetchAPI('/gudang/override-status', {
              method: 'POST',
              body: JSON.stringify({
                part_type: overrideForm.value.part_type,
                sn: overrideForm.value.sn,
                new_status: overrideForm.value.new_status,
                reason: overrideForm.value.reason.trim(),
                action: 'OVERRIDE_STATUS'
              })
            });
            if (json.ok) {
              triggerToast('Berhasil', json.message || 'Status part berhasil diubah!', 'success');
              showOverrideModal.value = false;
              await loadMasterData();
              if (showStatusDrillModal.value) {
                await openStatusDrilldown(drillData.value.part_type, drillData.value.status);
              }
              if (userRole.value === 'admin' || userRole.value === 'Super Admin' || String(userRole.value || '').toLowerCase().includes('admin')) {
                loadAuditLogs();
              }
            } else {
              triggerToast('Gagal Override', json.error || 'Gagal mengubah status part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Gudang Actual Verification & Decline moved to public/js/modules/warehouse.js (Fase 2G)

        const getStockStatusBySN = (partType, sn) => {
          if (!sn || sn === '-' || sn === '') return '';
          const cleanSn = String(sn).trim();
          const cleanNoDot = cleanSn.replace(/\./g, '');

          // Check if this part is in adminPendingRequests
          const pendingReq = (adminPendingRequests.value || []).find(r =>
            r.status === 'PENDING' &&
            String(r.part_type) === String(partType) &&
            (r.sn === cleanSn || (r.sn && r.sn.replace(/\./g, '') === cleanNoDot))
          );
          if (pendingReq) {
            return pendingReq.current_status || 'Declined';
          }

          // Check master parts
          const stock = (masterParts.value || []).find(p => String(p.part_type) === String(partType) && (p.sn === cleanSn || (p.sn && p.sn.replace(/\./g, '') === cleanNoDot)));
          if (stock) {
            return stock.status || '';
          }
          return '';
        };

        // ================= LAPORAN VERIFICATION DETAIL =================
        const showLaporanDetailModal = ref(false);
        const laporanDetailData = ref({
          tray: null,
          assemblies: []
        });

        // ================= OPERATOR FILTER FOR GUDANG / ADMIN =================

        // Pengembalian Barang (Returns) moved to public/js/modules/warehouse.js (Fase 2G)

        // Assembly Session Actions & Downtime moved to public/js/modules/assembly.js (Fase 2E)

        // ================= DOWNTIME AUDIT LOGS (ADMIN & GUDANG) =================
        const downtimeLogsList = ref([]);
        const downtimeFilter = ref({
          startDate: '',
          endDate: getTodayDateStr(),
          operator: '',
          trayCode: '',
          q: ''
        });

        const loadDowntimeLogs = async () => {
          try {
            const params = new URLSearchParams();
            if (downtimeFilter.value.startDate) params.append('start_date', downtimeFilter.value.startDate);
            if (downtimeFilter.value.endDate) params.append('end_date', downtimeFilter.value.endDate);
            if (downtimeFilter.value.operator) params.append('operator', downtimeFilter.value.operator);
            if (downtimeFilter.value.trayCode) params.append('tray_code', downtimeFilter.value.trayCode);
            if (downtimeFilter.value.q) params.append('q', downtimeFilter.value.q);

            const json = await fetchAPI(`/admin/downtime-logs?${params.toString()}`);
            if (json.ok) {
              downtimeLogsList.value = json.data || [];
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const resetDowntimeFilter = () => {
          downtimeFilter.value = {
            startDate: '',
            endDate: getTodayDateStr(),
            operator: '',
            trayCode: '',
            q: ''
          };
          loadDowntimeLogs();
        };

        const openLaporanVerificationDetail = async (item) => {
          try {
            const code = item.tray_code || '';
            const trayId = item.tray_id || '';
            const json = await fetchAPI(`/gudang/tray_verification_detail?code=${encodeURIComponent(code)}&tray_id=${trayId}`);
            if (json.ok) {
              laporanDetailData.value = {
                tray: json.tray || item,
                assemblies: json.assemblies || []
              };
              showLaporanDetailModal.value = true;
            } else {
              triggerToast('Gagal', json.error || 'Gagal memuat detail verifikasi laporan', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // Pop-up Validasi Khusus Daftar Ulang 1 Set Utuh
        const showReregisterSetModal = ref(false);
        const targetReregisterSet = ref(null);
        const reregisterNewReworkReason = ref('');
        const isSubmittingReregisterSet = ref(false);

        const isTargetSetRework = () => false;

        const openReregisterSetConfirmModal = (item) => {
          targetReregisterSet.value = item;
          reregisterNewReworkReason.value = '';
          showReregisterSetModal.value = true;
        };

        const confirmReregisterSet = async () => {
          if (!targetReregisterSet.value || isSubmittingReregisterSet.value) return;
          const item = targetReregisterSet.value;

          const fallbackReason = (item.qc_failure_reasons || item.assy_failure_reasons || item.assembly_reason || item.reason || item.display_reason || 'Didaftarkan ulang oleh Gudang').trim();
          const finalReason = reregisterNewReworkReason.value.trim() || fallbackReason;

          isSubmittingReregisterSet.value = true;
          try {
            const res = await fetchAPI('/master/re-register', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: item.assembly_id || item.id,
                part_type: item.part_type || '15',
                sn: item.part15_sn || item.sn,
                reason: finalReason
              })
            });
            if (res.ok) {
              triggerToast('Permohonan Terkirim', res.message || 'Permohonan pendaftaran ulang berhasil diajukan ke Admin!', 'success');
              showReregisterSetModal.value = false;
              item.is_reregistered = true;
              const isTF1 = item.hold_source === 'TESTER_ASSEMBLY' || ['Hold', 'Rework'].includes(item.assy_test_status) || ['HOLD_ASSY', 'REWORK_ASSY'].includes(item.qc_status) || Boolean(item.assy_failure_reasons);
              const isQC = !isTF1 && (item.stage === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'REWORK_QC' || item.hold_source === 'QC');
              item.verified_status = isQC ? 'PENDING_ADMIN_QC' : 'PENDING_ADMIN';
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              if (typeof loadPendingAssemblies === 'function') await loadPendingAssemblies();
              if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
              if (typeof loadQCPendingItems === 'function') await loadQCPendingItems();
            } else {
              triggerToast('Gagal Mendaftarkan', res.error || 'Terjadi kesalahan saat pendaftaran ulang', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSubmittingReregisterSet.value = false;
          }
        };

        // Standarisasi format alasan kegagalan: huruf awal kapital dan peringkasan profesional untuk multi-test
        const formatFailureReason = (text) => {
          if (!text || typeof text !== 'string') return '';
          const trimmed = text.trim();
          if (!trimmed || trimmed === '-') return '-';

          // Pola kegagalan uji berulang: "gagal dalam test X, gagal dalam test Y..."
          const pattern = /gagal\s+dalam\s+(?:test|uji)?\s*#?(\d+)/gi;
          const matches = [...trimmed.matchAll(pattern)];

          if (matches.length > 0) {
            const seen = new Set();
            const nums = [];
            matches.forEach(m => {
              const n = parseInt(m[1], 10);
              if (!isNaN(n) && !seen.has(n)) {
                seen.add(n);
                nums.push(n);
              }
            });
            nums.sort((a, b) => a - b);
            const numsStr = nums.join(', ');

            // Cek apakah ada catatan tambahan selain daftar nomor test
            let remainder = trimmed.replace(pattern, '').replace(/^[ ,;:\-]+|[ ,;:\-]+$/g, '').trim();
            if (!remainder) {
              if (nums.length === 1) {
                return `Gagal dalam Test ${nums[0]}`;
              } else {
                return `Gagal dalam Test: ${numsStr}`;
              }
            } else {
              const remCap = remainder.charAt(0).toUpperCase() + remainder.slice(1);
              if (nums.length === 1) {
                return `Gagal dalam Test ${nums[0]} (${remCap})`;
              } else {
                return `Gagal dalam Test: ${numsStr} (${remCap})`;
              }
            }
          }

          // Huruf pertama kapital untuk standarisasi kalimat umum
          return trimmed.charAt(0).toUpperCase() + trimmed.slice(1);
        };

        // Format visual penggabungan teks alasan rework khusus fitur QC: [Alasan Awal] | [Alasan Rework 1] | [Alasan Rework 2]
        const formatQCReworkReason = (item) => {
          if (!item) return '-';
          const parts = [];

          // 1. Initial failure reason
          const rawInitial = (item.qc_failure_reasons || item.assy_failure_reasons || item.assembly_reason || item.reason || item.display_reason || '').trim();
          const initialReason = formatFailureReason(rawInitial);
          if (initialReason && initialReason !== 'Tertahan mutu' && initialReason !== 'Tertahan di Quality Control (QC)' && initialReason !== '-') {
            parts.push(initialReason);
          }

          // 2. Multi-rework accumulated reasons
          const rwReason = (item.rework_reason || '').trim();
          if (rwReason) {
            const subReasons = rwReason.split('|').map(s => formatFailureReason(s.trim())).filter(Boolean);
            for (const sr of subReasons) {
              if (!parts.some(p => p.toLowerCase() === sr.toLowerCase())) {
                parts.push(sr);
              }
            }
          }

          if (parts.length === 0) {
            return initialReason || 'Tertahan mutu';
          }
          return parts.join(' | ');
        };

        // Master DB Re-Register State (Pendaftaran Ulang Part)
        const showReregisterModal = ref(false);
        const reregisterForm = ref({
          part_type: '',
          sn: '',
          current_status: '',
          target_status: 'Tersedia',
          reason: '',
          connected_set: null
        });

        const openReregisterModal = async (item) => {
          const rawStatus = item.effective_status || item.stock_status || item.status || 'Hold';
          const normalizedStatus = (rawStatus || '').toUpperCase();
          if (normalizedStatus.includes('TERSEDIA')) {
            triggerToast('Part Sudah Tersedia', 'Part ini sudah berstatus Tersedia dan siap digunakan, tidak bisa didaftarkan ulang.', 'info');
            return;
          }
          if (normalizedStatus.includes('PENDING') || normalizedStatus.includes('MENUNGGU')) {
            triggerToast('Sedang Menunggu Verifikasi', 'Part ini sedang dalam antrean verifikasi Admin, tidak bisa didaftarkan ulang kembali.', 'warn');
            return;
          }
          const defaultReason = (item.assembly_reason || item.last_reason || '').trim();
          reregisterForm.value = {
            part_type: item.part_type,
            sn: item.sn,
            current_status: rawStatus,
            target_status: 'Tersedia',
            reason: defaultReason,
            original_reason: defaultReason,
            is_replaced_from_assy: !!item.is_replaced_from_assy,
            connected_set: null
          };
          showReregisterModal.value = true;
          if (!item.is_replaced_from_assy) {
            try {
              const json = await fetchAPI(`/master/get-connected-set?sn=${encodeURIComponent(item.sn)}&part_type=${encodeURIComponent(item.part_type)}`);
              if (json.ok && json.is_connected) {
                reregisterForm.value.connected_set = json.set_info;
              }
            } catch (e) {
              console.error('Error fetching connected set:', e);
            }
          }
        };

        const submitReregisterPart = async () => {
          if (!reregisterForm.value.reason || !reregisterForm.value.reason.trim()) {
            triggerToast('Alasan Diperlukan', 'Harap isi alasan / catatan untuk pendaftaran ulang part!', 'warn');
            return;
          }
          try {
            const json = await fetchAPI('/master/re-register', {
              method: 'POST',
              body: JSON.stringify({
                part_type: reregisterForm.value.part_type,
                sn: reregisterForm.value.sn,
                target_status: reregisterForm.value.target_status,
                reason: reregisterForm.value.reason.trim()
              })
            });

            if (json.ok) {
              triggerToast('Terkirim ke Admin', json.message || 'Permohonan pendaftaran ulang telah dikirim ke Administrator!', 'info');
              showReregisterModal.value = false;
              if (selectedMasterPartRow.value && selectedMasterPartRow.value.sn === reregisterForm.value.sn) {
                selectedMasterPartRow.value.effective_status = 'Pending Verif Admin';
                selectedMasterPartRow.value.stock_status = 'Pending Verif Admin';
                selectedMasterPartRow.value.status = 'Pending Verif Admin';
              }
              if (masterPartTrackingData.value && masterPartTrackingData.value.stock && masterPartTrackingData.value.stock.sn === reregisterForm.value.sn) {
                masterPartTrackingData.value.stock.status = 'Pending Verif Admin';
              }
              await loadMasterData();
              if (showStatusDrillModal.value) {
                await openStatusDrilldown(drillData.value.part_type, drillData.value.status);
              }
              if (userRole.value === 'admin' || userRole.value === 'Super Admin' || String(userRole.value || '').toLowerCase().includes('admin')) {
                loadPendingVerifications();
              }
            } else {
              triggerToast('Gagal', json.error || 'Gagal mendaftarkan ulang part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        // ================= MASTER STOK PART TRACKING LOGIC =================
        const showMasterPartTrackingModal = ref(false);
        const selectedMasterPartRow = ref(null);
        const masterPartTrackingData = ref(null);
        const isLoadingMasterPartDetail = ref(false);

        const openMasterPartTrackingModal = async (part) => {
          if (!part) return;
          selectedMasterPartRow.value = part;
          showMasterPartTrackingModal.value = true;
          isLoadingMasterPartDetail.value = true;
          masterPartTrackingData.value = null;
          try {
            const queryParams = new URLSearchParams();
            if (part.id) queryParams.append('id', part.id);
            if (part.sn) queryParams.append('sn', part.sn);
            if (part.part_type) queryParams.append('part_type', part.part_type);
            const res = await fetchAPI(`/master/part-detail?${queryParams.toString()}`);
            if (res && (res.ok || res.success) && res.data) {
              masterPartTrackingData.value = res.data;
            } else if (res && res.data) {
              masterPartTrackingData.value = res.data;
            } else {
              masterPartTrackingData.value = {
                stock: part,
                tray: null,
                assembly: null,
                audit_logs: [],
                re_register_requests: []
              };
            }
          } catch (err) {
            console.error('Failed to load master part detail:', err);
            masterPartTrackingData.value = {
              stock: part,
              tray: null,
              assembly: null,
              audit_logs: [],
              re_register_requests: []
            };
          } finally {
            isLoadingMasterPartDetail.value = false;
          }
        };

        // ================= ADMIN PENDING VERIFICATIONS LOGIC =================
        const adminPendingRequests = ref([]);
        const adminPendingSets = ref([]);
        const adminPendingQCSets = ref([]);
        const showAdminVerifySetModal = ref(false);
        const activeAdminSet = ref(null);
        const isSubmittingAllAdminVerify = ref(false);

        const isSetFullyVerified = (set) => {
          if (!set) return false;

          const vStatus = (set.verified_status || '').toUpperCase();
          const isDec = Boolean(
            set.is_declined ||
            vStatus === 'DECLINED' ||
            (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' ||
            set.process_stage === 'declined' ||
            (set.status || '').toUpperCase() === 'DECLINED' ||
            set.status === 'Decline' ||
            set.status === 'Declined'
          );

          // 1. Jika activeAdminSet di memori sedang dibuka untuk set ini
          const curId = set.assembly_id || set.id;
          const actId = activeAdminSet.value ? (activeAdminSet.value.assembly_id || activeAdminSet.value.id) : null;
          if (actId && curId === actId && activeAdminSet.value && activeAdminSet.value.parts) {
            const actAllDone = activeAdminSet.value.parts.every(p => !p.sn || p.sn === '-' || p.status === 'Tersedia');
            if (actAllDone) return true;
          }

          // 2. KHUSUS SET DECLINED:
          if (isDec) {
            // Sudah dipulihkan/diverifikasi secara eksplisit oleh Admin
            if (['RESOLVED_RESTORED', 'APPROVED_RESTORED', 'ACC'].includes(vStatus)) {
              return true;
            }

            // Cek apakah seluruh part fisik di database/master stock berstatus Tersedia
            const s15 = set.p15_status || getStockStatusBySN('15', set.part15_sn);
            const s1 = set.p1_status || getStockStatusBySN('1', set.part1_sn);
            const s12 = set.p12_status || getStockStatusBySN('12', set.part12_sn);

            // Validasi ketat: ketiga part harus terbukti berstatus 'Tersedia'
            const p15Done = (!set.part15_sn || set.part15_sn === '-') || (s15 === 'Tersedia');
            const p1Done = (!set.part1_sn || set.part1_sn === '-') || (s1 === 'Tersedia');
            const p12Done = (!set.part12_sn || set.part12_sn === '-') || (s12 === 'Tersedia');

            if (p15Done && p1Done && p12Done && (s15 === 'Tersedia' || s1 === 'Tersedia' || s12 === 'Tersedia')) {
              return true;
            }

            // Masih berstatus Declined dan belum diverifikasi -> WAJIB FALSE (Verifikasi Declined)
            return false;
          }

          // 3. SET NON-DECLINED YANG SUDAH SELESAI
          if (['RESOLVED_RESTORED', 'APPROVED_RESTORED', 'ACC', 'APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'WAITING_REASSEMBLY', 'APPROVED_REASSEMBLY', 'COMPLETED_PU'].includes(vStatus)) {
            return true;
          }
          if (set.status === 'Dirakit Ulang' || set.status === 'Selesai') return true;

          // 4. Sets pending admin verification are NOT verified
          if (['PENDING_ADMIN', 'PENDING_ADMIN_ASSY_REREG', 'PENDING_ADMIN_QC', 'PENDING_ADMIN_TF1', 'PENDING_ADMIN_PROCEED_TF1', 'PENDING_ADMIN_PROCEED_QC'].includes(vStatus)) {
            return false;
          }

          return false;
        };

        const getSetSNSet = () => {
          const s = new Set();
          (adminPendingSets.value || []).forEach(set => {
            if (set.part15_sn && set.part15_sn !== '-') s.add(set.part15_sn);
            if (set.part1_sn && set.part1_sn !== '-') s.add(set.part1_sn);
            if (set.part12_sn && set.part12_sn !== '-') s.add(set.part12_sn);
          });
          (adminPendingQCSets.value || []).forEach(set => {
            if (set.part15_sn && set.part15_sn !== '-') s.add(set.part15_sn);
            if (set.part1_sn && set.part1_sn !== '-') s.add(set.part1_sn);
            if (set.part12_sn && set.part12_sn !== '-') s.add(set.part12_sn);
          });
          return s;
        };

        const declinedSetsCount = computed(() => {
          return (adminPendingSets.value || []).filter(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            return isDec && !isSetFullyVerified(s);
          }).length;
        });

        const declinedRequestsCount = computed(() => {
          const setSNs = getSetSNSet();
          return (adminPendingRequests.value || []).filter(r => r.status === 'PENDING' && (r.current_status || '').toLowerCase().startsWith('declin') && !setSNs.has(r.sn)).length;
        });

        const declinedTotalCount = computed(() => {
          return declinedSetsCount.value + declinedRequestsCount.value;
        });

        const otherSetsCount = computed(() => {
          return (adminPendingSets.value || []).filter(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            return !isDec && !isSetFullyVerified(s);
          }).length;
        });

        const otherRequestsCount = computed(() => {
          const setSNs = getSetSNSet();
          return (adminPendingRequests.value || []).filter(r => r.status === 'PENDING' && !(r.current_status || '').toLowerCase().startsWith('declin') && !setSNs.has(r.sn)).length;
        });

        const qcSetsCount = computed(() => {
          return (adminPendingQCSets.value || []).filter(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            const vStatus = (s.verified_status || '').toUpperCase();
            if (!isDec && vStatus === 'HOLD_QC_GUDANG' && !s.is_reregistered && !s.request_id) return false;
            return !isSetFullyVerified(s);
          }).length;
        });

        const otherTotalCount = computed(() => {
          return otherSetsCount.value + otherRequestsCount.value + qcSetsCount.value;
        });

        const allTotalCount = computed(() => {
          return declinedTotalCount.value + otherTotalCount.value;
        });

        // ===================================================================
        // VERIFIKASI SATU SET & VERIFIKASI 1 PART (PEMISAHAN ISOLASI PENUH)
        // ===================================================================
        const adminSetProcessFilter = ref('ALL'); // 'declined', 'ALL', 'perakitan', 'test_functional_1', 'qc'
        const adminSetPartTypeFilter = ref('');   // '', '15', '1', '12'
        const adminSetStatusFilter = ref('');     // '', 'Hold', 'Rework', 'Scrap', 'Declined'
        const adminSetSearchQuery = ref('');
        const adminSetStartDate = ref('');
        const adminSetEndDate = ref('');
        const adminSetViewMode = ref('table'); // 'table' (default) or 'cards' (opsi)

        const getNextPUTrayCode = (trayCode) => {
          if (!trayCode) return '-';
          const clean = trayCode.replace(/-PU\d*$/i, '');
          const match = trayCode.match(/-PU(\d+)$/i);
          const currentNum = match ? parseInt(match[1], 10) : 0;
          return `${clean}-PU${currentNum + 1}`;
        };

        const resetAdminSetFilter = () => {
          adminSetProcessFilter.value = 'ALL';
          adminSetPartTypeFilter.value = '';
          adminSetStatusFilter.value = '';
          adminSetSearchQuery.value = '';
          adminSetStartDate.value = '';
          adminSetEndDate.value = '';
        };

        const getDeclinedSource = (set) => {
          if (!set) return '';
          if (set.declined_source) return set.declined_source;
          const whStatus = (set.warehouse_receipt_status || '').toUpperCase();
          const isGR = whStatus === 'DECLINED' ||
                       Boolean(set.warehouse_receipt_at) ||
                       Boolean(set.warehouse_receipt_by) ||
                       (set.declined_reason || '').toLowerCase().includes('penerimaan') ||
                       (set.declined_reason || '').toLowerCase().includes('good receipt') ||
                       (set.declined_reason || '').toLowerCase().includes('receipt');
          return isGR ? 'GR' : 'PG';
        };

        const allAdminSets = computed(() => {
          const map = new Map();
          // 1. Assemblies from assembly / TF1 - include declined (tagged), exclude replacements
          (adminPendingSets.value || []).forEach(s => {
            const id = s.assembly_id || s.id;
            if (id && !map.has(id)) {
              const isDec = Boolean(s.is_declined) ||
                (s.verified_status || '').toUpperCase() === 'DECLINED' ||
                (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' ||
                (s.status || '').toUpperCase() === 'DECLINE' ||
                (s.status || '').toUpperCase() === 'DECLINED' ||
                Boolean(s.declined_reason);

              const decSource = isDec ? (s.declined_source || getDeclinedSource(s)) : '';

              const isRep = Boolean(s.replacement_id) || Boolean(s.replacement_details) || (s.verified_status || '').toUpperCase() === 'PENDING_REPLACEMENT';
              if (isRep) return; // Dedicated feature: verif pergantian part!

              let pStage = isDec ? 'declined' : ((s.process_stage && s.process_stage !== 'declined') ? s.process_stage : (
                (['Hold', 'Rework'].includes(s.assy_test_status) || s.qc_status === 'HOLD_ASSY' || s.qc_status === 'REWORK_ASSY' || s.assy_failure_reasons) ? 'test_functional_1' :
                  (s.stage === 'QC' || ['HOLD_QC', 'REWORK_QC'].includes(s.qc_status) || s.qc_tested_at || s.qc_failure_reasons) ? 'qc' :
                    'perakitan'
              ));

              let dStatus = s.display_status;
              if (!dStatus) {
                const aStat = s.assembly_status || s.status || 'Hold';
                if (isDec) {
                  dStatus = 'Declined';
                } else if (pStage === 'qc') {
                  dStatus = 'Hold QC';
                } else if (pStage === 'test_functional_1') {
                  dStatus = 'Hold Test';
                } else {
                  if (s.status === 'Scrap' || aStat === 'Scrap') dStatus = 'Scrap Perakitan';
                  else dStatus = 'Hold Perakitan';
                }
              }
              map.set(id, { ...s, is_declined: isDec, is_replacement: false, process_stage: pStage, display_status: dStatus, declined_source: decSource });
            }
          });

          // 2. Assemblies from QC - include declined (tagged), exclude replacements
          (adminPendingQCSets.value || []).forEach(s => {
            const id = s.assembly_id || s.id;
            if (id && !map.has(id)) {
              const isDec = Boolean(s.is_declined) ||
                (s.verified_status || '').toUpperCase() === 'DECLINED' ||
                (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' ||
                (s.status || '').toUpperCase() === 'DECLINE' ||
                (s.status || '').toUpperCase() === 'DECLINED' ||
                Boolean(s.declined_reason);

              const decSource = isDec ? (s.declined_source || getDeclinedSource(s)) : '';

              // Guard: Item Hold QC yang belum melewati aksi daftar ulang atau tindakan dari gudang/admin jangan masuk verifikasi
              const vStatus = (s.verified_status || '').toUpperCase();
              if (!isDec && vStatus === 'HOLD_QC_GUDANG' && !s.is_reregistered && !s.request_id) {
                return;
              }

              const aStat = s.assembly_status || s.status || 'Hold';
              const dStatus = isDec ? 'Declined' : (s.display_status || 'Hold QC');
              const pStage = isDec ? 'declined' : 'qc';
              map.set(id, { ...s, is_declined: isDec, process_stage: pStage, display_status: dStatus, declined_source: decSource });
            }
          });

          return Array.from(map.values());
        });

        const adminSetProcessCounts = computed(() => {
          const sets = allAdminSets.value;
          // Hitung antrean yang BELUM selesai diverifikasi (antrean aktif yang tersisa)
          const pendingSets = sets.filter(s => !isSetFullyVerified(s));
          const nonDeclinedPending = pendingSets.filter(s => !s.is_declined);
          return {
            ALL: nonDeclinedPending.length,
            perakitan: nonDeclinedPending.filter(s => s.process_stage === 'perakitan').length,
            test_functional_1: nonDeclinedPending.filter(s => s.process_stage === 'test_functional_1').length,
            qc: nonDeclinedPending.filter(s => s.process_stage === 'qc').length,
            declined: sets.filter(s => (s.is_declined || s.process_stage === 'declined' || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED') && !isSetFullyVerified(s)).length
          };
        });

        // Notification badge: strictly only count sets that are genuinely NOT YET VERIFIED (belum selesai)
        const adminPendingSetsTotalCount = computed(() => {
          return allAdminSets.value.filter(s => !isSetFullyVerified(s)).length;
        });

        // ===================================================================
        // VERIFIKASI PERGANTIAN PART (FITUR TERPISAH: PENGGANTIAN PART RAKITAN)
        // ===================================================================
        const adminPendingReplacements = ref([]);
        const adminRepSearchQuery = ref('');
        const adminRepPartTypeFilter = ref('');
        const adminRepStatusFilter = ref(''); // Default: '' (Semua Status, agar riwayat tetap tampil dan tidak hilang)
        const adminRepStageFilter = ref('ALL'); // 'ALL', 'perakitan', 'test_functional_1', 'qc'
        const adminRepStartDate = ref('');
        const adminRepEndDate = ref('');

        const getReplacementStage = (req) => {
          if (!req) return 'perakitan';
          if (req.source_stage) return req.source_stage;
          const qcSt = String(req.qc_status || '').toUpperCase();
          const assyTestSt = String(req.assy_test_status || '');
          const reason = String(req.replacement_reason || req.reason || '');
          if (['HOLD_QC', 'REWORK_QC', 'HOLD'].includes(qcSt) || req.qc_failure_reasons || /qc/i.test(reason)) {
            return 'qc';
          }
          if (['Hold TF 1', 'Hold', 'Not Good', 'FAIL', 'RU', 'Rework'].includes(assyTestSt) || req.assy_failure_reasons || /tf\s*1|uji\s*fungsional/i.test(reason)) {
            return 'test_functional_1';
          }
          return 'perakitan';
        };

        const getReplacementStageLabel = (req) => {
          const stage = getReplacementStage(req);
          if (stage === 'qc') return 'Test Functional 2 (QC)';
          if (stage === 'test_functional_1') return 'Test Functional 1 (TF 1)';
          return 'Perakitan';
        };

        const replStageCounts = computed(() => {
          let list = adminPendingReplacements.value || [];

          // Sesuaikan jumlah notifikasi/badge tahap proses dengan status verifikasi yang sedang dipilih
          if (adminRepStatusFilter.value) {
            const sf = adminRepStatusFilter.value.trim().toUpperCase();
            list = list.filter(r => {
              const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
              if (sf === 'PENDING') return st === 'PENDING' || st === '';
              if (sf === 'APPROVED') return st === 'APPROVED' || st === 'ACC';
              if (sf === 'REJECTED') return st === 'REJECTED';
              return true;
            });
          }

          // Sesuaikan juga dengan tipe part jika sedang difilter
          if (adminRepPartTypeFilter.value) {
            const pt = String(adminRepPartTypeFilter.value);
            list = list.filter(r => {
              const reps = parseReplacements(r);
              if (reps && reps.length > 0) {
                return reps.some(item => String(item.part_type) === pt);
              }
              return false;
            });
          }

          // Sesuaikan juga dengan rentang tanggal jika sedang diisi
          if (adminRepStartDate.value) {
            list = list.filter(r => {
              const dt = (r.replacement_created_at || '').substring(0, 10);
              return dt >= adminRepStartDate.value;
            });
          }
          if (adminRepEndDate.value) {
            list = list.filter(r => {
              const dt = (r.replacement_created_at || '').substring(0, 10);
              return dt <= adminRepEndDate.value;
            });
          }

          // Sesuaikan juga dengan kata kunci pencarian jika sedang diisi
          if (adminRepSearchQuery.value && adminRepSearchQuery.value.trim()) {
            const q = adminRepSearchQuery.value.trim().toLowerCase();
            list = list.filter(r => {
              const tc = (r.tray_code || '').toLowerCase();
              const mo = (r.source_document_mo || '').toLowerCase();
              const reqUser = (r.replacement_requestor || '').toLowerCase();
              const reason = (r.replacement_reason || '').toLowerCase();
              const setNum = String(r.set_number || '');
              const reps = parseReplacements(r);
              const repsMatch = reps.some(item =>
                (item.old_sn || '').toLowerCase().includes(q) ||
                (item.new_sn || '').toLowerCase().includes(q) ||
                (item.part_name || '').toLowerCase().includes(q)
              );
              return tc.includes(q) || mo.includes(q) || reqUser.includes(q) || reason.includes(q) || setNum.includes(q) || repsMatch;
            });
          }

          return {
            ALL: list.length,
            perakitan: list.filter(r => getReplacementStage(r) === 'perakitan').length,
            test_functional_1: list.filter(r => getReplacementStage(r) === 'test_functional_1').length,
            qc: list.filter(r => getReplacementStage(r) === 'qc').length
          };
        });

        const replCountPending = computed(() => {
          return (adminPendingReplacements.value || []).filter(r => {
            const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
            return st === 'PENDING' || st === '';
          }).length;
        });

        const replCountApproved = computed(() => {
          return (adminPendingReplacements.value || []).filter(r => {
            const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
            return st === 'APPROVED' || st === 'ACC';
          }).length;
        });

        const replCountRejected = computed(() => {
          return (adminPendingReplacements.value || []).filter(r => {
            const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
            return st === 'REJECTED';
          }).length;
        });

        const selectedAdminReplacementDetail = ref(null);
        const showAdminReplacementDetailModal = ref(false);
        const openAdminReplacementDetailModal = (req) => {
          selectedAdminReplacementDetail.value = req;
          showAdminReplacementDetailModal.value = true;
        };

        const resetAdminRepFilter = () => {
          adminRepSearchQuery.value = '';
          adminRepPartTypeFilter.value = '';
          adminRepStatusFilter.value = '';
          adminRepStageFilter.value = 'ALL';
          adminRepStartDate.value = '';
          adminRepEndDate.value = '';
          if (pgState && pgState.adminRepl) pgState.adminRepl.page = 1;
        };

        const isReplacementVerified = (r) => {
          if (!r) return true;
          const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
          if (st === 'APPROVED' || st === 'REJECTED' || st === 'ACC' || st === 'SELESAI') return true;
          if (st !== 'PENDING' && st !== '') return true;
          return false;
        };

        // Notification badge: only count genuinely PENDING (belum selesai) replacements
        const adminPendingReplacementsCount = computed(() => {
          return (adminPendingReplacements.value || []).filter(r => !isReplacementVerified(r)).length;
        });

        const filteredAdminReplacements = computed(() => {
          let list = adminPendingReplacements.value || [];

          // Filter by Source Stage (Perakitan / TF 1 / QC)
          if (adminRepStageFilter.value && adminRepStageFilter.value !== 'ALL') {
            list = list.filter(r => getReplacementStage(r) === adminRepStageFilter.value);
          }

          // Filter by Part Type
          if (adminRepPartTypeFilter.value) {
            const pt = String(adminRepPartTypeFilter.value);
            list = list.filter(r => {
              const reps = parseReplacements(r);
              if (reps && reps.length > 0) {
                return reps.some(item => String(item.part_type) === pt);
              }
              return false;
            });
          }

          // Filter by Replacement Verification Status (Menunggu Verif / Diterima / Ditolak)
          if (adminRepStatusFilter.value) {
            const sf = adminRepStatusFilter.value.trim().toUpperCase();
            list = list.filter(r => {
              const st = String(r.replacement_status || r.status || '').trim().toUpperCase();
              if (sf === 'PENDING') return st === 'PENDING' || st === '';
              if (sf === 'APPROVED') return st === 'APPROVED' || st === 'ACC';
              if (sf === 'REJECTED') return st === 'REJECTED';
              return true;
            });
          }

          // Filter by Search Query
          if (adminRepSearchQuery.value && adminRepSearchQuery.value.trim()) {
            const q = adminRepSearchQuery.value.trim().toLowerCase();
            list = list.filter(r => {
              const tc = (r.tray_code || '').toLowerCase();
              const mo = (r.source_document_mo || '').toLowerCase();
              const reqUser = (r.replacement_requestor || '').toLowerCase();
              const reason = (r.replacement_reason || '').toLowerCase();
              const setNum = String(r.set_number || '');
              const reps = parseReplacements(r);
              const repsMatch = reps.some(item =>
                (item.old_sn || '').toLowerCase().includes(q) ||
                (item.new_sn || '').toLowerCase().includes(q) ||
                (item.part_name || '').toLowerCase().includes(q)
              );
              return tc.includes(q) || mo.includes(q) || reqUser.includes(q) || reason.includes(q) || setNum.includes(q) || repsMatch;
            });
          }

          // Filter by Date Range
          if (adminRepStartDate.value) {
            list = list.filter(r => {
              const dt = (r.replacement_created_at || '').substring(0, 10);
              return dt >= adminRepStartDate.value;
            });
          }
          if (adminRepEndDate.value) {
            list = list.filter(r => {
              const dt = (r.replacement_created_at || '').substring(0, 10);
              return dt <= adminRepEndDate.value;
            });
          }

          return list;
        });

        const filteredAdminSetsOnly = computed(() => {
          let list = allAdminSets.value;

          // Filter by Process Stage
          // When 'ALL' or process-stage tabs: exclude declined items (they appear only in 'declined' tab)
          if (adminSetProcessFilter.value === 'declined') {
            list = list.filter(s => s.is_declined);
          } else if (adminSetProcessFilter.value && adminSetProcessFilter.value !== 'ALL') {
            list = list.filter(s => !s.is_declined && s.process_stage === adminSetProcessFilter.value);
          } else {
            // ALL: exclude declined
            list = list.filter(s => !s.is_declined);
          }

          // Filter by Part Type (15, 1, 12)
          if (adminSetPartTypeFilter.value) {
            const pt = String(adminSetPartTypeFilter.value);
            list = list.filter(s => {
              if (pt === '15') return s.part15_sn && s.part15_sn !== '-';
              if (pt === '1') return s.part1_sn && s.part1_sn !== '-';
              if (pt === '12') return s.part12_sn && s.part12_sn !== '-';
              return true;
            });
          }

          // Filter by Status Type
          if (adminSetStatusFilter.value) {
            const sf = adminSetStatusFilter.value.toLowerCase();
            if (sf === 'pending' || sf === 'menunggu') {
              list = list.filter(s => !isSetFullyVerified(s));
            } else if (sf === 'completed' || sf === 'selesai' || sf === 'terkunci') {
              list = list.filter(s => isSetFullyVerified(s));
            } else {
              list = list.filter(s => {
                const ds = (s.display_status || '').toLowerCase();
                return ds.includes(sf);
              });
            }
          }

          // Filter by Search Query
          if (adminSetSearchQuery.value && adminSetSearchQuery.value.trim()) {
            const q = adminSetSearchQuery.value.trim().toLowerCase();
            list = list.filter(s =>
              (s.part15_sn || '').toLowerCase().includes(q) ||
              (s.part1_sn || '').toLowerCase().includes(q) ||
              (s.part12_sn || '').toLowerCase().includes(q) ||
              (s.tray_code || '').toLowerCase().includes(q) ||
              String(s.set_number || '').toLowerCase().includes(q) ||
              (s.pic_assembly || '').toLowerCase().includes(q) ||
              (s.pic_qc || '').toLowerCase().includes(q) ||
              (s.scrap_reason || '').toLowerCase().includes(q) ||
              (s.declined_reason || '').toLowerCase().includes(q) ||
              (s.display_status || '').toLowerCase().includes(q)
            );
          }

          // Filter by Date Range
          if (adminSetStartDate.value) {
            const start = new Date(adminSetStartDate.value);
            start.setHours(0, 0, 0, 0);
            list = list.filter(s => {
              const dStr = s.qc_tested_at || s.tray_created_at || s.finished_at || s.started_at;
              if (!dStr) return true;
              return new Date(dStr) >= start;
            });
          }
          if (adminSetEndDate.value) {
            const end = new Date(adminSetEndDate.value);
            end.setHours(23, 59, 59, 999);
            list = list.filter(s => {
              const dStr = s.qc_tested_at || s.tray_created_at || s.finished_at || s.started_at;
              if (!dStr) return true;
              return new Date(dStr) <= end;
            });
          }

          return list;
        });

        // Set Detail Modal state
        const selectedAdminSetDetail = ref(null);
        const showAdminSetDetailModal = ref(false);
        const openAdminSetDetailModal = (set) => {
          selectedAdminSetDetail.value = set;
          adminReregApprovedOperator.value = set ? (set.rereg_pending_operator || set.pic_assembly || '') : '';
          adminReregNotes.value = '';
          showAdminSetDetailModal.value = true;
        };

        // -------------------------------------------------------------------
        // VERIFIKASI 1 PART SAJA (SATUAN)
        // -------------------------------------------------------------------
        const adminPartFilter = ref({
          partType: '',
          status: '',
          search: '',
          startDate: '',
          endDate: ''
        });

        const resetAdminPartFilter = () => {
          adminPartFilter.value = {
            partType: '',
            status: '',
            search: '',
            startDate: '',
            endDate: ''
          };
        };

        const filteredAdminPartsOnly = computed(() => {
          let list = adminPendingRequests.value || [];
          const setSNs = getSetSNSet();

          // STRICT AIRTIGHT: Exclude parts currently belonging to assembled sets
          list = list.filter(r => !setSNs.has(r.sn));

          // Filter by Part Type
          if (adminPartFilter.value.partType) {
            list = list.filter(r => String(r.part_type) === String(adminPartFilter.value.partType));
          }

          // Filter by Status
          if (adminPartFilter.value.status) {
            const st = adminPartFilter.value.status.toLowerCase();
            list = list.filter(r => {
              const cStatus = (r.current_status || '').toLowerCase();
              if (st.startsWith('declin')) return cStatus.startsWith('declin');
              return cStatus === st;
            });
          }

          // Filter by Search Query
          if (adminPartFilter.value.search && adminPartFilter.value.search.trim()) {
            const q = adminPartFilter.value.search.trim().toLowerCase();
            list = list.filter(r =>
              (r.sn || '').toLowerCase().includes(q) ||
              (r.requestor_username || '').toLowerCase().includes(q) ||
              (r.reason || '').toLowerCase().includes(q) ||
              (r.previous_tray_code || '').toLowerCase().includes(q) ||
              String(r.previous_set_number || '').toLowerCase().includes(q) ||
              (r.cs || '').toLowerCase().includes(q)
            );
          }

          // Filter by Date
          if (adminPartFilter.value.startDate) {
            const start = new Date(adminPartFilter.value.startDate);
            start.setHours(0, 0, 0, 0);
            list = list.filter(r => {
              if (!r.created_at) return true;
              return new Date(r.created_at) >= start;
            });
          }
          if (adminPartFilter.value.endDate) {
            const end = new Date(adminPartFilter.value.endDate);
            end.setHours(23, 59, 59, 999);
            list = list.filter(r => {
              if (!r.created_at) return true;
              return new Date(r.created_at) <= end;
            });
          }

          return list;
        });

        // Notification badge: only count parts that are genuinely pending (status is PENDING, not in an assembled set, and not yet Tersedia)
        const adminPendingRequestsCount = computed(() => {
          const setSNs = getSetSNSet();
          return (adminPendingRequests.value || []).filter(r => {
            const st = String(r.status || '').toUpperCase();
            if (st === 'APPROVED' || st === 'REJECTED' || st === 'ACC' || st === 'SELESAI') return false;
            if (st !== 'PENDING') return false; // Bukan pending, jangan masuk notif
            if (setSNs.has(r.sn)) return false; // Part of an assembled set, skip
            if (getStockStatusBySN(r.part_type, r.sn) === 'Tersedia') return false; // Part sudah dipulihkan ke Tersedia
            return true;
          }).length;
        });

        const adminPendingCount = computed(() => {
          // Total notif badge = pending sets + pending part requests + pending replacements
          // Each sub-count is already deduplicated separately
          return (adminPendingSetsTotalCount.value || 0) + (adminPendingRequestsCount.value || 0) + (adminPendingReplacementsCount.value || 0);
        });

        // Part Tracking Detail Modal state
        const selectedAdminPartDetail = ref(null);
        const showAdminPartDetailModal = ref(false);
        const openAdminPartDetailModal = (req) => {
          selectedAdminPartDetail.value = req;
          showAdminPartDetailModal.value = true;
        };

        const adminVerifyFilter = ref({
          status: '',         // Default to '' (Semua), 'Declined', 'StatusLain'
          partType: '',       // '', '15', '1', '12'
          partStatus: '',     // '', 'Declined', 'Hold', 'Rework', 'Scrap', 'Good', 'Tersedia'
          searchSN: '',       // SN / Text keyword
          startDate: '',
          endDate: ''
        });

        const resetAdminVerifyFilter = () => {
          adminVerifyFilter.value.partType = '';
          adminVerifyFilter.value.partStatus = '';
          adminVerifyFilter.value.searchSN = '';
          adminVerifyFilter.value.startDate = '';
          adminVerifyFilter.value.endDate = '';
        };

        const filteredAdminSets = computed(() => {
          let list = (adminPendingSets.value || []).filter(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            if (isDec) return true;
            if (s.stage === 'QC') return false;
            if (s.qc_status && ['HOLD_QC', 'REWORK_QC', 'HOLD'].includes(s.qc_status)) return false;
            if (s.qc_failure_reasons) return false;
            if (s.scrap_reason && s.scrap_reason.toLowerCase().includes('qc')) return false;
            return true;
          });

          // 1. Filter Status Tab (Khusus Declined vs Status Lain)
          if (adminVerifyFilter.value.status === 'Declined') {
            list = list.filter(s => s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED');
          } else if (adminVerifyFilter.value.status === 'StatusLain') {
            list = list.filter(s => !s.is_declined && (s.verified_status || '').toUpperCase() !== 'DECLINED' && (s.warehouse_receipt_status || '').toUpperCase() !== 'DECLINED');
          }

          // 2. Filter per Part Type (15, 1, 12)
          if (adminVerifyFilter.value.partType) {
            const p = String(adminVerifyFilter.value.partType);
            list = list.filter(s => {
              if (p === '15') return s.part15_sn && s.part15_sn !== '-';
              if (p === '1') return s.part1_sn && s.part1_sn !== '-';
              if (p === '12') return s.part12_sn && s.part12_sn !== '-';
              return true;
            });
          }

          // 3. Filter per Status Part (Declined, Hold, Rework, Scrap, Good, Tersedia)
          if (adminVerifyFilter.value.partStatus) {
            const st = adminVerifyFilter.value.partStatus.toLowerCase();
            list = list.filter(s => {
              const sStatus = (s.assembly_status || s.status || '').toLowerCase();
              const vStatus = (s.verified_status || '').toLowerCase();
              const s15 = (getStockStatusBySN('15', s.part15_sn) || '').toLowerCase();
              const s1 = (getStockStatusBySN('1', s.part1_sn) || '').toLowerCase();
              const s12 = (getStockStatusBySN('12', s.part12_sn) || '').toLowerCase();

              if (st.startsWith('declin')) {
                return s.is_declined || vStatus === 'declined' || s15.startsWith('declin') || s1.startsWith('declin') || s12.startsWith('declin');
              }
              return sStatus === st || s15 === st || s1 === st || s12 === st;
            });
          }

          // 4. Filter per Search SN / Keyword
          if (adminVerifyFilter.value.searchSN && adminVerifyFilter.value.searchSN.trim()) {
            const q = adminVerifyFilter.value.searchSN.trim().toLowerCase();
            list = list.filter(s =>
              (s.part15_sn || '').toLowerCase().includes(q) ||
              (s.part1_sn || '').toLowerCase().includes(q) ||
              (s.part12_sn || '').toLowerCase().includes(q) ||
              (s.tray_code || '').toLowerCase().includes(q) ||
              (s.pic_assembly || '').toLowerCase().includes(q) ||
              (s.pic_warehouse || '').toLowerCase().includes(q) ||
              (s.declined_reason || '').toLowerCase().includes(q) ||
              (s.scrap_reason || '').toLowerCase().includes(q)
            );
          }

          // 5. Filter Tanggal Mulai
          if (adminVerifyFilter.value.startDate) {
            const start = new Date(adminVerifyFilter.value.startDate);
            start.setHours(0, 0, 0, 0);
            list = list.filter(s => {
              const dStr = s.tray_created_at || s.finished_at || s.started_at;
              if (!dStr) return true;
              return new Date(dStr) >= start;
            });
          }

          // 6. Filter Tanggal Selesai
          if (adminVerifyFilter.value.endDate) {
            const end = new Date(adminVerifyFilter.value.endDate);
            end.setHours(23, 59, 59, 999);
            list = list.filter(s => {
              const dStr = s.tray_created_at || s.finished_at || s.started_at;
              if (!dStr) return true;
              return new Date(dStr) <= end;
            });
          }

          return list;
        });

        const filteredAdminQCSets = computed(() => {
          let list = [...(adminPendingQCSets.value || [])];
          (adminPendingSets.value || []).forEach(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            if (isDec) return;
            const isQC = s.stage === 'QC' ||
              (s.qc_status && ['HOLD_QC', 'REWORK_QC', 'HOLD'].includes(s.qc_status)) ||
              Boolean(s.qc_failure_reasons) ||
              (s.scrap_reason && s.scrap_reason.toLowerCase().includes('qc'));
            if (isQC && !list.some(existing => existing.assembly_id === s.assembly_id)) {
              list.push(s);
            }
          });

          // Filter out raw HOLD_QC_GUDANG items that have not yet had re-registration or action from warehouse
          list = list.filter(s => {
            const isDec = s.is_declined || (s.verified_status || '').toUpperCase() === 'DECLINED' || (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
            const vStatus = (s.verified_status || '').toUpperCase();
            if (!isDec && vStatus === 'HOLD_QC_GUDANG' && !s.is_reregistered && !s.request_id) return false;
            return true;
          });

          if (adminVerifyFilter.value.searchSN && adminVerifyFilter.value.searchSN.trim()) {
            const q = adminVerifyFilter.value.searchSN.trim().toLowerCase();
            list = list.filter(s =>
              (s.part15_sn || '').toLowerCase().includes(q) ||
              (s.part1_sn || '').toLowerCase().includes(q) ||
              (s.part12_sn || '').toLowerCase().includes(q) ||
              (s.tray_code || '').toLowerCase().includes(q) ||
              (s.pic_qc || '').toLowerCase().includes(q) ||
              (s.pic_assembly || '').toLowerCase().includes(q) ||
              (s.scrap_reason || '').toLowerCase().includes(q)
            );
          }

          if (adminVerifyFilter.value.startDate) {
            const start = new Date(adminVerifyFilter.value.startDate);
            start.setHours(0, 0, 0, 0);
            list = list.filter(s => {
              const dStr = s.qc_tested_at || s.tray_created_at || s.finished_at;
              if (!dStr) return true;
              return new Date(dStr) >= start;
            });
          }

          if (adminVerifyFilter.value.endDate) {
            const end = new Date(adminVerifyFilter.value.endDate);
            end.setHours(23, 59, 59, 999);
            list = list.filter(s => {
              const dStr = s.qc_tested_at || s.tray_created_at || s.finished_at;
              if (!dStr) return true;
              return new Date(dStr) <= end;
            });
          }

          return list;
        });

        const filteredAdminScrapRequests = computed(() => {
          let list = adminPendingRequests.value || [];
          const setSNs = getSetSNSet();

          // Exclude parts belonging to connected assembled sets so they only appear as Set Cards
          list = list.filter(r => !setSNs.has(r.sn));

          // 1. Filter Status Tab (Khusus Declined vs Status Lain)
          if (adminVerifyFilter.value.status === 'Declined') {
            list = list.filter(r => (r.current_status || '').toLowerCase().startsWith('declin'));
          } else if (adminVerifyFilter.value.status === 'StatusLain') {
            list = list.filter(r => !(r.current_status || '').toLowerCase().startsWith('declin'));
          }

          // 2. Filter per Part Type (P9 Part 15, 1, 12)
          if (adminVerifyFilter.value.partType) {
            list = list.filter(r => String(r.part_type) === String(adminVerifyFilter.value.partType));
          }

          // 3. Filter per Status Part (Hold, Rework, Scrap, Good, Tersedia, Declined)
          if (adminVerifyFilter.value.partStatus) {
            const st = adminVerifyFilter.value.partStatus.toLowerCase();
            list = list.filter(r => {
              const cStatus = (r.current_status || '').toLowerCase();
              if (st.startsWith('declin')) {
                return cStatus.startsWith('declin');
              }
              return cStatus === st;
            });
          }

          // 4. Filter per Search SN / Keyword
          if (adminVerifyFilter.value.searchSN && adminVerifyFilter.value.searchSN.trim()) {
            const q = adminVerifyFilter.value.searchSN.trim().toLowerCase();
            list = list.filter(r =>
              (r.sn || '').toLowerCase().includes(q) ||
              (r.requestor_username || '').toLowerCase().includes(q) ||
              (r.reason || '').toLowerCase().includes(q) ||
              (r.current_status || '').toLowerCase().includes(q) ||
              (r.target_status || '').toLowerCase().includes(q) ||
              (r.cs || '').toLowerCase().includes(q)
            );
          }

          // 5. Filter Tanggal Mulai
          if (adminVerifyFilter.value.startDate) {
            const start = new Date(adminVerifyFilter.value.startDate);
            start.setHours(0, 0, 0, 0);
            list = list.filter(r => {
              if (!r.created_at) return true;
              return new Date(r.created_at) >= start;
            });
          }

          // 6. Filter Tanggal Selesai
          if (adminVerifyFilter.value.endDate) {
            const end = new Date(adminVerifyFilter.value.endDate);
            end.setHours(23, 59, 59, 999);
            list = list.filter(r => {
              if (!r.created_at) return true;
              return new Date(r.created_at) <= end;
            });
          }

          return list;
        });

        const hasUnverifiedPartsInActiveSet = computed(() => {
          if (!activeAdminSet.value || !activeAdminSet.value.parts) return false;
          return activeAdminSet.value.parts.some(p => p.sn && p.sn !== '-' && p.status !== 'Tersedia');
        });

        
        // -------------------------------------------------------------------------
        // 1-CLICK PEMULIHAN DECLINED SET
        // -------------------------------------------------------------------------
        const isSubmittingDeclinedRestore = ref(false);
        const restoreDeclinedSetToAvailable = async (set) => {
          if (!set) return;
          const assyId = set.assembly_id || set.id;
          if (!assyId) return;

          const confirmed = confirm(`Konfirmasi Pemulihan Set:\nSet #${set.set_number || ''} (${set.part15_sn || '-'})\n\nSemua part dalam set ini akan dikembalikan ke Master Stock dengan status 'Tersedia' sehingga dapat di-input ulang oleh Gudang.\nLanjutkan?`);
          if (!confirmed) return;

          isSubmittingDeclinedRestore.value = true;
          try {
            const res = await fetchAPI('/admin/approve-declined-set-restore', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: assyId,
                notes: 'Diverifikasi Admin: barang dipulihkan ke Master Stock Tersedia'
              })
            });

            if (res.ok) {
              triggerToast('Berhasil Dipulihkan', res.message || 'Set berhasil dipulihkan ke status Tersedia.', 'success');
              if (typeof showAdminVerifySetModal !== 'undefined') showAdminVerifySetModal.value = false;
              if (typeof showAdminSetDetailModal !== 'undefined') showAdminSetDetailModal.value = false;
              if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
              if (typeof loadMasterData === 'function') await loadMasterData();
            } else {
              triggerToast('Gagal', res.error || 'Gagal memulihkan set', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
          } finally {
            isSubmittingDeclinedRestore.value = false;
          }
        };

        const openAdminVerifySetModal = (set) => {
          if (!set) return;
          if (isSetFullyVerified(set)) {
            openAdminSetDetailModal(set);
            return;
          }
          const isDec = Boolean(set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED');
          const isHoldTest = !isDec && (set.assembly_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.qc_status === 'HOLD' || (set.status === 'Hold'));
          const p15Status = set.p15_status || getStockStatusBySN('15', set.part15_sn) || (isDec ? 'Declined' : 'Hold');
          const p1Status = set.p1_status || getStockStatusBySN('1', set.part1_sn) || (isDec ? 'Declined' : 'Hold');
          const p12Status = set.p12_status || getStockStatusBySN('12', set.part12_sn) || (isDec ? 'Declined' : 'Hold');

          activeAdminSet.value = {
            assembly_id: set.assembly_id || set.id,
            tray_id: set.tray_id,
            tray_code: set.tray_code,
            set_number: set.set_number,
            is_declined: isDec,
            declined_source: getDeclinedSource(set),
            verified_status: set.verified_status,
            warehouse_receipt_status: set.warehouse_receipt_status,
            assembly_status: set.assembly_status || set.status,
            qc_status: set.qc_status,
            scrap_reason: set.scrap_reason || set.reason,
            declined_reason: set.declined_reason,
            is_hold_test: isHoldTest,
            parts: [
              { type: '15', label: '1. P9 Part 15 (Identitas P9)', sn: set.part15_sn, status: p15Status, is_submitting: false },
              { type: '1', label: '2. P9 Part 1', sn: set.part1_sn, status: p1Status, is_submitting: false },
              { type: '12', label: '3. P9 Part 12', sn: set.part12_sn, status: p12Status, is_submitting: false }
            ]
          };
          showAdminVerifySetModal.value = true;
        };

        const verifyAdminSinglePart = async (partItem) => {
          if (!partItem.sn || partItem.sn === '-') return;
          partItem.is_submitting = true;
          try {
            const setAssyId = activeAdminSet.value ? (activeAdminSet.value.assembly_id || activeAdminSet.value.id) : null;
            const json = await fetchAPI('/admin/approve-part-direct', {
              method: 'POST',
              body: JSON.stringify({
                part_type: partItem.type,
                sn: partItem.sn,
                target_status: 'Tersedia',
                assembly_id: setAssyId,
                notes: `Verifikasi fisik Admin dari Set #${activeAdminSet.value ? activeAdminSet.value.set_number : ''}`
              })
            });

            if (json.ok) {
              partItem.status = 'Tersedia';

              // Update status part pada set di tabel
              const found = (adminPendingSets.value || []).find(s => (s.assembly_id || s.id) === setAssyId);
              if (found) {
                if (partItem.type === '15') found.p15_status = 'Tersedia';
                else if (partItem.type === '1') found.p1_status = 'Tersedia';
                else if (partItem.type === '12') found.p12_status = 'Tersedia';
              }
              const foundQC = (adminPendingQCSets.value || []).find(s => (s.assembly_id || s.id) === setAssyId);
              if (foundQC) {
                if (partItem.type === '15') foundQC.p15_status = 'Tersedia';
                else if (partItem.type === '1') foundQC.p1_status = 'Tersedia';
                else if (partItem.type === '12') foundQC.p12_status = 'Tersedia';
              }

              // Periksa apakah sekarang seluruh part pada activeAdminSet sudah Tersedia
              const allDone = activeAdminSet.value && activeAdminSet.value.parts && activeAdminSet.value.parts.every(p => !p.sn || p.sn === '-' || p.status === 'Tersedia');
              if (allDone && activeAdminSet.value) {
                activeAdminSet.value.verified_status = 'RESOLVED_RESTORED';
                activeAdminSet.value.is_declined = false;
                if (found) {
                  found.verified_status = 'RESOLVED_RESTORED';
                  found.is_declined = false;
                }
                if (foundQC) {
                  foundQC.verified_status = 'RESOLVED_RESTORED';
                  foundQC.is_declined = false;
                }
              }

              triggerToast('Verifikasi Berhasil', json.message || `Part ${partItem.type} (${partItem.sn}) berhasil dipulihkan menjadi Tersedia!`, 'success');
              await loadMasterData();
              await loadPendingVerifications();
            } else {
              triggerToast('Gagal', json.error || 'Gagal memverifikasi part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            partItem.is_submitting = false;
          }
        };

        const verifyAllPartsInActiveSet = async () => {
          if (!activeAdminSet.value || !activeAdminSet.value.parts) return;
          const unverified = activeAdminSet.value.parts.filter(p => p.sn && p.sn !== '-' && p.status !== 'Tersedia');
          if (!unverified.length) return;
          isSubmittingAllAdminVerify.value = true;
          try {
            const setAssyId = activeAdminSet.value ? (activeAdminSet.value.assembly_id || activeAdminSet.value.id) : null;
            for (const p of unverified) {
              p.is_submitting = true;
              const json = await fetchAPI('/admin/approve-part-direct', {
                method: 'POST',
                body: JSON.stringify({
                  part_type: p.type,
                  sn: p.sn,
                  target_status: 'Tersedia',
                  assembly_id: setAssyId,
                  notes: `Verifikasi fisik Admin dari Set #${activeAdminSet.value.set_number}`
                })
              });
              p.is_submitting = false;
              if (json.ok) {
                p.status = 'Tersedia';
              }
            }

            if (activeAdminSet.value) {
              activeAdminSet.value.verified_status = 'RESOLVED_RESTORED';
              activeAdminSet.value.is_declined = false;
              const found = (adminPendingSets.value || []).find(s => (s.assembly_id || s.id) === setAssyId);
              if (found) {
                found.p15_status = 'Tersedia';
                found.p1_status = 'Tersedia';
                found.p12_status = 'Tersedia';
                found.verified_status = 'RESOLVED_RESTORED';
                found.is_declined = false;
              }
              const foundQC = (adminPendingQCSets.value || []).find(s => (s.assembly_id || s.id) === setAssyId);
              if (foundQC) {
                foundQC.p15_status = 'Tersedia';
                foundQC.p1_status = 'Tersedia';
                foundQC.p12_status = 'Tersedia';
                foundQC.verified_status = 'RESOLVED_RESTORED';
                foundQC.is_declined = false;
              }
            }

            triggerToast('Verifikasi Selesai', `Semua part Set #${activeAdminSet.value.set_number} berhasil dipulihkan menjadi Tersedia!`, 'success');
            await loadMasterData();
            await loadPendingVerifications();
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            isSubmittingAllAdminVerify.value = false;
          }
        };

        const deleteAdminVerifyRequest = async (req) => {
          const ok = await showConfirmCard({
            title: 'Hapus Verifikasi Part',
            message: `Apakah Anda yakin ingin menghapus barang P9 Part ${req.part_type} SN ${req.sn} dari antrean verifikasi Admin?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/delete-re-register', {
              method: 'POST',
              body: JSON.stringify({ request_id: req.id, sn: req.sn, part_type: req.part_type })
            });
            if (json.ok) {
              triggerToast('Barang Dihapus', json.message || 'Barang berhasil dihapus dari verifikasi Admin.', 'info');
              await loadPendingVerifications();
              await loadMasterData();
            } else {
              triggerToast('Gagal Hapus', json.error || 'Gagal menghapus barang', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const deleteAdminSetPart = async (partItem) => {
          if (!partItem.sn || partItem.sn === '-') return;
          const ok = await showConfirmCard({
            title: 'Hapus Part dari Verifikasi',
            message: `Hapus part ${partItem.label} SN ${partItem.sn} dari daftar verifikasi?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          partItem.is_submitting = true;
          try {
            const json = await fetchAPI('/admin/delete-re-register', {
              method: 'POST',
              body: JSON.stringify({ sn: partItem.sn, part_type: partItem.type })
            });
            if (json.ok) {
              triggerToast('Barang Dihapus', json.message || `Part ${partItem.sn} berhasil dihapus.`, 'info');
              partItem.status = 'Dihapus';
              await loadPendingVerifications();
              await loadMasterData();
            } else {
              triggerToast('Gagal Hapus', json.error || 'Gagal menghapus part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            partItem.is_submitting = false;
          }
        };

        const deleteAdminDeclinedSet = async (set) => {
          const ok = await showConfirmCard({
            title: 'Hapus Barang Rakitan',
            message: `Hapus barang rakitan Set #${set.set_number} (${set.tray_code || 'Tray #' + set.tray_id}) dari antrean verifikasi Admin?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/delete-declined-set', {
              method: 'POST',
              body: JSON.stringify({ assembly_id: set.assembly_id || set.id, tray_id: set.tray_id, set_number: set.set_number })
            });
            if (json.ok) {
              triggerToast('Barang Dihapus', json.message || 'Barang rakitan berhasil dihapus dari verifikasi Admin.', 'info');
              await loadPendingVerifications();
              await loadMasterData();
              await loadProductionHoldItems();
              await loadTrays();
              await loadQCPendingItems();
              await loadTravelerTray();
            } else {
              triggerToast('Gagal Hapus', json.error || 'Gagal menghapus barang rakitan', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const parseReplacements = (set) => {
          if (!set) return [];
          if (Array.isArray(set.replacement_details)) return set.replacement_details;
          if (typeof set.replacement_details === 'string') {
            try {
              const parsed = JSON.parse(set.replacement_details);
              if (Array.isArray(parsed)) return parsed;
              if (parsed && typeof parsed === 'object') return [parsed];
            } catch (e) {
              console.error('Failed to parse replacement_details', e);
            }
          }
          if (set.replacement_details && typeof set.replacement_details === 'object') {
            return [set.replacement_details];
          }
          if (set.part_type && (set.new_sn || set.old_sn)) {
            return [{
              part_type: String(set.part_type),
              old_sn: set.old_sn || (set.part_type === '15' ? set.part15_sn : (set.part_type === '1' ? set.part1_sn : set.part12_sn)) || '',
              new_sn: set.new_sn || '',
              part_name: set.part_name || `Komponen Part ${set.part_type}`
            }];
          }
          return [];
        };

        const adminApproveReplacement = async (set) => {
          if (!set) return;
          const setNum = set.set_number || set.id;
          const ok = await showConfirmCard({
            title: 'Setujui Pergantian Part',
            message: `Setujui permohonan pergantian part untuk Set #${setNum} (${set.tray_code || ''})?`,
            icon: 'fa-solid fa-wrench',
            iconBg: 'bg-emerald-100 text-emerald-600',
            confirmText: 'Ya, Setujui',
            confirmBtnClass: 'bg-emerald-600 hover:bg-emerald-700 text-white'
          });
          if (!ok) return;
          try {
            const res = await fetchAPI('/admin/approve-replacement', {
              method: 'POST',
              body: JSON.stringify({
                replacement_id: set.replacement_id,
                assembly_id: set.assembly_id || set.id
              })
            });
            if (res.ok) {
              triggerToast('Berhasil Disetujui', res.message || 'Pergantian part berhasil disetujui & diperbarui!', 'success');
              await loadPendingVerifications();
              if (typeof loadAssemblyResults === 'function') await loadAssemblyResults();
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              if (typeof loadMasterParts === 'function') await loadMasterParts();
              const targetId = set.assembly_id || set.id;
              if (selectedAssemblyItem.value && (selectedAssemblyItem.value.assembly_id === targetId || selectedAssemblyItem.value.id === targetId)) {
                selectedAssemblyItem.value.verified_status = 'APPROVED';
                selectedAssemblyItem.value.replacement_pending = false;
                selectedAssemblyItem.value.has_pending_replacement = false;
                selectedAssemblyItem.value.pending_action_type = '';
              }
              if (selectedHoldItem.value && (selectedHoldItem.value.assembly_id === targetId || selectedHoldItem.value.id === targetId)) {
                selectedHoldItem.value.verified_status = 'APPROVED';
                selectedHoldItem.value.replacement_pending = false;
                selectedHoldItem.value.has_pending_replacement = false;
                selectedHoldItem.value.pending_action_type = '';
              }
            } else {
              triggerToast('Gagal', res.error || 'Gagal menyetujui pergantian part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message || 'Terjadi kesalahan sistem', 'error');
          }
        };

        const adminRejectReplacement = async (set) => {
          if (!set) return;
          const setNum = set.set_number || set.id;
          const reason = await showPromptCard({
            title: 'Tolak Permohonan Pergantian Part',
            message: `Tolak permohonan pergantian part Set #${setNum} (${set.tray_code || ''}). Masukkan alasan penolakan:`,
            defaultValue: 'Part tidak sesuai standar',
            inputLabel: 'Alasan Penolakan:',
            inputPlaceholder: 'Masukkan alasan penolakan...',
            confirmText: 'Tolak Permohonan',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (reason === null) return;
          try {
            const res = await fetchAPI('/admin/reject-replacement', {
              method: 'POST',
              body: JSON.stringify({
                replacement_id: set.replacement_id,
                assembly_id: set.assembly_id || set.id,
                notes: reason
              })
            });
            if (res.ok) {
              triggerToast('Ditolak', res.message || 'Permohonan pergantian part telah ditolak.', 'info');
              await loadPendingVerifications();
              if (typeof loadAssemblyResults === 'function') await loadAssemblyResults();
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              if (typeof loadMasterParts === 'function') await loadMasterParts();
              const targetId = set.assembly_id || set.id;
              if (selectedAssemblyItem.value && (selectedAssemblyItem.value.assembly_id === targetId || selectedAssemblyItem.value.id === targetId)) {
                selectedAssemblyItem.value.verified_status = null;
                selectedAssemblyItem.value.replacement_pending = false;
                selectedAssemblyItem.value.has_pending_replacement = false;
                selectedAssemblyItem.value.pending_action_type = '';
              }
              if (selectedHoldItem.value && (selectedHoldItem.value.assembly_id === targetId || selectedHoldItem.value.id === targetId)) {
                selectedHoldItem.value.verified_status = null;
                selectedHoldItem.value.replacement_pending = false;
                selectedHoldItem.value.has_pending_replacement = false;
                selectedHoldItem.value.pending_action_type = '';
              }
            } else {
              triggerToast('Gagal', res.error || 'Gagal menolak pergantian part', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message || 'Terjadi kesalahan sistem', 'error');
          }
        };

        // === Admin Approve Assy Rereg (Daftar Ulang Perakitan) ===
        const adminReregApprovedOperator = ref('');
        const adminReregNotes = ref('');

        const adminApproveAssyRereg = async (set, action) => {
          const assyId = set?.assembly_id || set?.id;
          if (!set || !assyId) {
            triggerToast('Validasi', 'Data rakitan tidak valid!', 'warn');
            return;
          }
          try {
            const res = await fetchAPI('/admin/approve-assy-rereg', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: assyId,
                action: action,
                approved_operator: adminReregApprovedOperator.value || set.rereg_pending_operator || set.pic_assembly,
                notes: adminReregNotes.value
              })
            });
            if (res.ok) {
              const msg = action === 'APPROVE'
                ? `Perakitan ulang Set #${set.set_number || ''} disetujui! Berhasil dialihkan ke antrean Menu Perakitan Ulang (RU).`
                : 'Pengajuan perakitan ulang ditolak.';
              triggerToast(action === 'APPROVE' ? 'Disetujui' : 'Ditolak', res.message || msg, action === 'APPROVE' ? 'success' : 'warning');
              adminReregApprovedOperator.value = '';
              adminReregNotes.value = '';
              showAdminSetDetailModal.value = false;
              showAdminVerifySetModal.value = false;
              await loadPendingVerifications();
              if (typeof loadPendingReassembly === 'function') await loadPendingReassembly();
              if (typeof loadAssemblyResults === 'function') await loadAssemblyResults();
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              await loadTrays();
            } else {
              triggerToast('Gagal', res.error || 'Terjadi kesalahan saat memproses perakitan ulang', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
          }
        };

        const adminApproveHoldItem = async (set, decision) => {
          const isQC = set.stage === 'QC' || set.qc_status === 'HOLD_QC' || set.qc_status === 'REWORK_QC' || decision.endsWith('_QC');
          const actualDecision = isQC ? (decision.endsWith('_QC') ? decision : `${decision}_QC`) : decision;
          const isGood = actualDecision === 'APPROVE_GOOD' || actualDecision === 'APPROVE_GOOD_QC';
          const label = isGood
            ? (isQC ? 'menyetujui sebagai GOOD (Lanjut GR)' : 'menyetujui sebagai GOOD (lanjut ke QC)')
            : (isQC ? 'mengalihkan ke status REWORK QC (kembali ke Gudang)' : 'mengalihkan ke status REWORK (kembali ke Gudang)');
          const ok = await showConfirmCard({
            title: isGood ? 'Setujui Status Rakitan' : 'Alihkan Status Rakitan',
            message: `Apakah Anda yakin ingin ${label} untuk rakitan Set #${set.set_number} (${set.tray_code || 'Tray #' + set.tray_id})?`,
            icon: isGood ? 'fa-solid fa-circle-check' : 'fa-solid fa-arrows-rotate',
            iconBg: isGood ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600',
            confirmText: isGood ? 'Ya, Setujui' : 'Ya, Alihkan',
            confirmBtnClass: isGood ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-600 hover:bg-amber-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/approve-hold-item', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: set.assembly_id || set.id,
                decision: actualDecision,
                notes: isGood ? (isQC ? 'Disetujui Good QC oleh Admin' : 'Disetujui Good oleh Admin') : (isQC ? 'Dialihkan ke Rework QC oleh Admin' : 'Dialihkan ke Rework oleh Admin')
              })
            });
            if (json.ok) {
              triggerToast('Berhasil', json.message || 'Status rakitan berhasil diperbarui!', 'success');
              await loadPendingVerifications();
              await loadMasterData();
              await loadProductionHoldItems();
              await loadTrays();
              await loadQCPendingItems();
              await loadTravelerTray();
            } else {
              triggerToast('Gagal', json.error || 'Gagal memproses persetujuan', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const showAdminVerifyModal = ref(false);
        const adminVerifyForm = ref({
          request_id: null,
          part_type: '',
          sn: '',
          current_status: 'Scrap',
          target_status: 'Tersedia',
          notes: ''
        });

        const openAdminVerifyModal = (req) => {
          if (!req) return;
          if (req.status !== 'PENDING') {
            openAdminPartDetailModal(req);
            return;
          }
          adminVerifyForm.value = {
            request_id: req.id,
            part_type: req.part_type,
            sn: req.sn,
            current_status: req.current_status || 'Scrap',
            target_status: 'Tersedia',
            notes: ''
          };
          showAdminVerifyModal.value = true;
        };

        const submitAdminVerify = async () => {
          if (!adminVerifyForm.value.request_id) return;
          try {
            const json = await fetchAPI('/admin/approve-re-register', {
              method: 'POST',
              body: JSON.stringify({
                request_id: adminVerifyForm.value.request_id,
                action: 'APPROVE',
                target_status: adminVerifyForm.value.target_status,
                notes: adminVerifyForm.value.notes
              })
            });

            if (json.ok) {
              triggerToast('Berhasil', json.message || 'Permohonan pendaftaran ulang berhasil disetujui!', 'success');
              showAdminVerifyModal.value = false;
              await loadPendingVerifications();
              await loadMasterData();
            } else {
              triggerToast('Gagal', json.error || 'Gagal memproses verifikasi', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const loadPendingVerifications = async () => {
          try {
            const [reqRes, setsRes, replRes] = await Promise.all([
              fetchAPI('/admin/pending-verifications').catch(() => null),
              fetchAPI('/admin/pending-assemblies').catch(() => null),
              fetchAPI('/admin/pending-replacements').catch(() => null)
            ]);
            if (reqRes && reqRes.ok) {
              adminPendingRequests.value = reqRes.data || [];
            }
            if (setsRes && setsRes.ok) {
              adminPendingSets.value = setsRes.data || [];
              adminPendingQCSets.value = setsRes.qc_data || [];
              adminPendingReplacements.value = (replRes && replRes.ok && replRes.data) ? replRes.data : (setsRes.replacement_data || []);
            } else if (replRes && replRes.ok) {
              adminPendingReplacements.value = replRes.data || [];
            }
          } catch (e) {
            console.error('loadPendingVerifications error:', e);
          }
        };

        const approveReRegister = async (req, action) => {
          const actionText = action === 'APPROVE' ? 'menyetujui' : 'menolak';
          const isApprove = action === 'APPROVE';
          const ok = await showConfirmCard({
            title: isApprove ? 'Setujui Pendaftaran Ulang' : 'Tolak Pendaftaran Ulang',
            message: `Apakah Anda yakin ingin ${actionText} pendaftaran ulang part ${req.sn} (P9 Part ${req.part_type})?`,
            icon: isApprove ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark',
            iconBg: isApprove ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600',
            confirmText: isApprove ? 'Ya, Setujui' : 'Ya, Tolak',
            confirmBtnClass: isApprove ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const json = await fetchAPI('/admin/approve-re-register', {
              method: 'POST',
              body: JSON.stringify({
                request_id: req.id,
                action: action
              })
            });

            if (json.ok) {
              triggerToast('Berhasil', json.message || `Permohonan berhasil ${action === 'APPROVE' ? 'disetujui' : 'ditolak'}!`, 'success');
              await loadPendingVerifications();
              await loadMasterData();
            } else {
              triggerToast('Gagal', json.error || 'Gagal memproses verifikasi', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };


        // Audit Trail Filter State & Computed
        const auditFilter = ref({
          startDate: '',
          endDate: getTodayDateStr(),
          status: '',
          search: ''
        });

        const filteredAuditLogs = computed(() => {
          return auditLogsList.value.filter(log => {
            // 1. Date filter
            const dateKey = log.created_at ? log.created_at.substring(0, 10) : '';
            if (auditFilter.value.startDate && dateKey && dateKey < auditFilter.value.startDate) return false;
            if (auditFilter.value.endDate && dateKey && dateKey > auditFilter.value.endDate) return false;

            // 2. Status filter
            if (auditFilter.value.status && auditFilter.value.status !== 'ALL') {
              const targetStatus = auditFilter.value.status.toLowerCase();
              const newSt = (log.new_status || '').toLowerCase();
              const oldSt = (log.old_status || '').toLowerCase();
              if (newSt !== targetStatus && oldSt !== targetStatus) return false;
            }

            // 3. Search query
            if (auditFilter.value.search && auditFilter.value.search.trim()) {
              const q = auditFilter.value.search.toLowerCase().trim();
              const sn = (log.sn || '').toLowerCase();
              const partType = (String(log.part_type) || '').toLowerCase();
              const user = (log.actor_username || '').toLowerCase();
              const userFull = (getOperatorFullName(log.actor_username) || '').toLowerCase();
              const reason = (log.reason || '').toLowerCase();
              if (!sn.includes(q) && !partType.includes(q) && !user.includes(q) && !userFull.includes(q) && !reason.includes(q)) {
                return false;
              }
            }
            return true;
          });
        });

        const resetAuditFilter = () => {
          auditFilter.value = {
            startDate: '',
            endDate: getTodayDateStr(),
            status: '',
            search: ''
          };
        };


        const loadMasterStocksSummary = async () => {
          try {
            const json = await fetchAPI('/master/stocks_summary');
            if (json.ok) {
              const map = {};
              json.data.forEach(item => {
                map[item.part_type] = item;
              });
              stockSummary.value = map;
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const fetchMasterStockList = async (page = 1) => {
          masterFilter.value.page = page;
          try {
            let url = `/master/stocks_list?page=${page}`;
            if (masterFilter.value.type) url += `&type=${encodeURIComponent(masterFilter.value.type)}`;
            if (masterFilter.value.status) url += `&status=${encodeURIComponent(masterFilter.value.status)}`;
            if (masterFilter.value.replaced !== undefined && masterFilter.value.replaced !== '') {
              url += `&replaced=${encodeURIComponent(masterFilter.value.replaced)}`;
            }
            if (masterFilter.value.q) url += `&q=${encodeURIComponent(masterFilter.value.q.trim())}`;

            const json = await fetchAPI(url);
            if (json.ok) {
              masterStockList.value = json.data;
              masterFilter.value.total = json.total || 0;
              masterFilter.value.totalPages = Math.max(1, Math.ceil((json.total || 0) / (json.limit || 100)));
              if (pgState && pgState.masterStock) {
                pgState.masterStock.page = 1;
              }
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        let masterStockSearchTimer = null;
        const debouncedFetchMasterStockList = () => {
          if (masterStockSearchTimer) clearTimeout(masterStockSearchTimer);
          masterStockSearchTimer = setTimeout(() => {
            fetchMasterStockList(1);
          }, 300);
        };

        const loadMasterParts = async () => {
          try {
            const json = await fetchAPI('/master/parts');
            if (json && json.ok) {
              masterParts.value = json.data || [];
            }
          } catch (_) { }
        };

        const loadMasterData = async () => {
          await Promise.all([
            loadMasterStocksSummary(),
            fetchMasterStockList(1),
            loadMasterParts()
          ]);
        };

        // ==========================================
        // FITUR TAMBAH PART NUMBER (MASTER STOK)
        // ==========================================
        const addPartTab = ref('single'); // 'single' | 'bulk'
        const addPartBulkMode = ref('list'); // 'list' | 'range'
        const addPartForm = ref({
          part_type: '1',
          sn: '',
          cs: '',
          reason: ''
        });
        const addPartBulkForm = ref({
          part_type: '1',
          sns_text: '',
          sn_start: '',
          sn_end: '',
          cs: '',
          reason: ''
        });
        const addPartLoading = ref(false);
        const addPartBulkResult = ref(null);
        const recentAddedParts = ref([]);
        const recentAddedLoading = ref(false);
        const recentAddedFilter = ref('');
        const recentAddedSearch = ref('');
        const existingMasterPart = ref(null);
        const isCheckingExistingPart = ref(false);
        const bulkPreviewData = ref(null);

        const filteredRecentAddedParts = computed(() => {
          let list = recentAddedParts.value || [];
          if (recentAddedFilter.value) {
            list = list.filter(p => String(p.part_type) === String(recentAddedFilter.value));
          }
          if (recentAddedSearch.value && recentAddedSearch.value.trim()) {
            const q = recentAddedSearch.value.trim().toLowerCase();
            list = list.filter(p =>
              (p.sn && p.sn.toLowerCase().includes(q)) ||
              (p.cs && p.cs.toLowerCase().includes(q)) ||
              (p.created_by && p.created_by.toLowerCase().includes(q)) ||
              (p.reason && p.reason.toLowerCase().includes(q))
            );
          }
          return list;
        });

        const pagedRecentAdded = computed(() => {
          const s = (pgState && pgState.recentAdded) || { page: 1, size: 100 };
          const list = filteredRecentAddedParts.value || [];
          return list.slice((s.page - 1) * s.size, s.page * s.size);
        });

        const totalPgRecentAdded = computed(() => {
          const s = (pgState && pgState.recentAdded) || { page: 1, size: 100 };
          const list = filteredRecentAddedParts.value || [];
          return Math.max(1, Math.ceil(list.length / s.size));
        });

        const resetAddPartForm = () => {
          addPartForm.value = {
            part_type: addPartForm.value.part_type || '1',
            sn: '',
            cs: '',
            reason: ''
          };
          existingMasterPart.value = null;
          isCheckingExistingPart.value = false;
        };

        const resetAddPartBulkForm = () => {
          addPartBulkForm.value = {
            part_type: addPartBulkForm.value.part_type || '1',
            sns_text: '',
            sn_start: '',
            sn_end: '',
            cs: '',
            reason: ''
          };
          addPartBulkResult.value = null;
          bulkPreviewData.value = null;
        };

        const loadRecentlyAddedParts = async (type = null) => {
          if (type !== null) {
            recentAddedFilter.value = type;
          }
          recentAddedLoading.value = true;
          try {
            let url = '/master/recent-added?limit=30';
            if (recentAddedFilter.value) {
              url += '&part_type=' + encodeURIComponent(recentAddedFilter.value);
            }
            const res = await fetchAPI(url);
            if (res && res.ok) {
              recentAddedParts.value = res.data || [];
            } else {
              recentAddedParts.value = [];
            }
          } catch (err) {
            console.error('Error loading recently added parts:', err);
          } finally {
            recentAddedLoading.value = false;
          }
        };

        let singleCheckTimer = null;
        const onSingleSnInput = () => {
          const sn = (addPartForm.value.sn || '').trim();
          const partType = addPartForm.value.part_type || '1';
          if (!sn) {
            existingMasterPart.value = null;
            isCheckingExistingPart.value = false;
            return;
          }

          if (singleCheckTimer) clearTimeout(singleCheckTimer);
          isCheckingExistingPart.value = true;
          singleCheckTimer = setTimeout(async () => {
            try {
              const res = await fetchAPI(`/master/part-detail?sn=${encodeURIComponent(sn)}&part_type=${encodeURIComponent(partType)}`);
              if (res && res.ok && res.data) {
                const stockObj = (res.data && res.data.stock) ? res.data.stock : res.data;
                existingMasterPart.value = {
                  ...stockObj,
                  stock: stockObj,
                  lifecycle: res.data.lifecycle,
                  assembly: res.data.assembly,
                  tray: res.data.tray
                };
              } else {
                existingMasterPart.value = null;
              }
            } catch (err) {
              existingMasterPart.value = null;
            } finally {
              isCheckingExistingPart.value = false;
            }
          }, 350);
        };

        let bulkCheckTimer = null;
        const onBulkSnInput = () => {
          if (bulkCheckTimer) clearTimeout(bulkCheckTimer);
          bulkCheckTimer = setTimeout(async () => {
            const form = addPartBulkForm.value;
            const mode = addPartBulkMode.value;
            const payload = {
              part_type: form.part_type,
            };

            if (mode === 'range') {
              if (!form.sn_start || !form.sn_end) {
                bulkPreviewData.value = null;
                return;
              }
              payload.sn_start = form.sn_start.trim();
              payload.sn_end = form.sn_end.trim();
            } else {
              if (!form.sns_text || !form.sns_text.trim()) {
                bulkPreviewData.value = null;
                return;
              }
              payload.sns = form.sns_text;
            }

            try {
              const res = await fetchAPI('/master/check-bulk', {
                method: 'POST',
                body: JSON.stringify(payload)
              });
              if (res && res.ok && res.data) {
                bulkPreviewData.value = res.data;
              } else {
                bulkPreviewData.value = null;
              }
            } catch (err) {
              console.error('Error checking bulk preview:', err);
              bulkPreviewData.value = null;
            }
          }, 350);
        };

        const submitAddSinglePart = async () => {
          if (addPartLoading.value) return;
          const form = addPartForm.value;
          if (!form.sn || !form.sn.trim()) {
            triggerToast('Validasi Gagal', 'Serial Number (SN) wajib diisi!', 'warn');
            return;
          }
          if (form.part_type === '15' && (!form.cs || !form.cs.trim())) {
            triggerToast('Validasi Gagal', 'CS wajib diisi untuk Part 15!', 'warn');
            return;
          }
          if (existingMasterPart.value) {
            triggerToast('Part Duplikat', `Part dengan nomor '${existingMasterPart.value.sn}' sudah terdaftar di Master Stock (Status: ${existingMasterPart.value.status})!`, 'error');
            return;
          }

          addPartLoading.value = true;
          try {
            const res = await fetchAPI('/master/add-part', {
              method: 'POST',
              body: JSON.stringify({
                part_type: form.part_type,
                sn: form.sn.trim(),
                cs: form.part_type === '15' ? form.cs.trim().toUpperCase() : undefined,
                reason: form.reason ? form.reason.trim() : undefined
              })
            });

            if (res && res.ok) {
              triggerToast('Berhasil', res.message || 'Part number berhasil ditambahkan ke Master Stok!', 'success');
              resetAddPartForm();
              await loadRecentlyAddedParts();
              if (typeof loadMasterData === 'function') {
                loadMasterData();
              }
            } else {
              if (res && res.existing_part) {
                const stockObj = (res.existing_part && res.existing_part.stock) ? res.existing_part.stock : res.existing_part;
                existingMasterPart.value = {
                  ...stockObj,
                  stock: stockObj,
                  ...res.existing_part
                };
              }
              triggerToast('Gagal Menambahkan', (res && res.error) || 'Gagal menambahkan part number', 'error');
            }
          } catch (err) {
            console.error('Submit single part error:', err);
            triggerToast('Error Sistem', err.message || 'Terjadi kesalahan sistem saat menambahkan part', 'error');
          } finally {
            addPartLoading.value = false;
          }
        };

        const submitAddBulkPart = async () => {
          if (addPartLoading.value) return;
          const form = addPartBulkForm.value;
          const payload = {
            part_type: form.part_type,
            cs: form.part_type === '15' ? (form.cs || '').trim().toUpperCase() : undefined,
            reason: form.reason ? form.reason.trim() : undefined
          };

          if (form.part_type === '15' && (!payload.cs)) {
            triggerToast('Validasi Gagal', 'CS wajib diisi untuk Part 15!', 'warn');
            return;
          }

          if (addPartBulkMode.value === 'range') {
            if (!form.sn_start || !form.sn_end) {
              triggerToast('Validasi Gagal', 'SN Awal dan SN Akhir wajib diisi!', 'warn');
              return;
            }
            payload.sn_start = form.sn_start.trim();
            payload.sn_end = form.sn_end.trim();
          } else {
            if (!form.sns_text || !form.sns_text.trim()) {
              triggerToast('Validasi Gagal', 'Daftar SN tidak boleh kosong!', 'warn');
              return;
            }
            payload.sns = form.sns_text;
          }

          // Strict Atomic Validation Check:
          // Jika terdeteksi ada duplikat (meskipun hanya 1), tolak transaksi
          if (bulkPreviewData.value && bulkPreviewData.value.duplicate_count > 0) {
            triggerToast('Transaksi Ditolak', `Ditemukan ${bulkPreviewData.value.duplicate_count} part yang sudah ada di Master Stock. Seluruh transaksi dibatalkan!`, 'error');
            return;
          }

          addPartLoading.value = true;
          try {
            const res = await fetchAPI('/master/add-part-bulk', {
              method: 'POST',
              body: JSON.stringify(payload)
            });

            if (res && res.ok) {
              addPartBulkResult.value = res.data || null;
              triggerToast('Bulk Selesai', res.message || 'Seluruh part number berhasil didaftarkan!', 'success');
              resetAddPartBulkForm();
              await loadRecentlyAddedParts();
              if (typeof loadMasterData === 'function') {
                loadMasterData();
              }
            } else {
              triggerToast('Transaksi Dibatalkan', (res && res.error) || 'Gagal menambahkan batch part number', 'error');
              if (res && res.data && res.data.duplicates) {
                // Update preview data to show the duplicates that rejected the transaction
                bulkPreviewData.value = {
                  part_type: form.part_type,
                  total: res.data.total_submitted || res.data.duplicates.length,
                  duplicates: res.data.duplicates,
                  duplicate_count: res.data.duplicate_count || res.data.duplicates.length,
                  new_sns: [],
                  new_count: 0,
                  can_submit: false
                };
              }
            }
          } catch (err) {
            console.error('Submit bulk part error:', err);
            triggerToast('Error Sistem', err.message || 'Terjadi kesalahan sistem saat proses bulk', 'error');
          } finally {
            addPartLoading.value = false;
          }
        };
        const deleteRecentlyAddedPart = async (part) => {
          if (!part || !part.id) return;
          const ok = await showConfirmCard({
            title: 'Hapus Part',
            message: 'Apakah anda ingin menghapus part ini?',
            icon: 'fa-solid fa-trash-can',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya',
            cancelText: 'Tidak',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;

          try {
            const res = await fetchAPI('/master/delete-part', {
              method: 'POST',
              body: JSON.stringify({
                id: part.id,
                sn: part.sn,
                part_type: part.part_type
              })
            });

            if (res && res.ok) {
              triggerToast('Berhasil Dihapus', res.message || 'Part berhasil dihapus dari Master Stok!', 'success');
              await loadRecentlyAddedParts();
              if (typeof loadMasterData === 'function') {
                loadMasterData();
              }
            } else {
              triggerToast('Gagal Menghapus', (res && res.error) || 'Gagal menghapus part', 'error');
            }
          } catch (err) {
            console.error('Delete part error:', err);
            triggerToast('Error Sistem', err.message || 'Terjadi kesalahan sistem saat menghapus part', 'error');
          }
        };



        // ================= MASTER DB STATUS DRILLDOWN POPUP =================
        const showStatusDrillModal = ref(false);
        const drillData = ref({
          part_type: '',
          status: '',
          list: [],
          loading: false
        });
        const drillSearchQuery = ref('');

        const filteredDrillList = computed(() => {
          const q = (drillSearchQuery.value || '').trim().toLowerCase();
          if (!q) return drillData.value.list;
          return drillData.value.list.filter(item => {
            const sn = (item.sn || '').toLowerCase();
            const cs = (item.cs || '').toLowerCase();
            const tray = (item.tray_code || '').toLowerCase();
            const mo = (item.source_document_mo || '').toLowerCase();
            const op = (getOperatorFullName(item.pic_assembly || item.assigned_operator) || '').toLowerCase();
            const reason = (item.assembly_reason || '').toLowerCase();
            return sn.includes(q) || cs.includes(q) || tray.includes(q) || mo.includes(q) || op.includes(q) || reason.includes(q);
          });
        });

        const openStatusDrilldown = async (partType, status) => {
          drillData.value = {
            part_type: partType || '',
            status: status || '',
            list: [],
            loading: true
          };
          drillSearchQuery.value = '';
          showStatusDrillModal.value = true;

          try {
            let url = `/master/stocks_list?page=1&limit=1500`;
            if (partType) url += `&type=${encodeURIComponent(partType)}`;
            if (status === 'Part Diganti') {
              url += `&replaced=1`;
            } else if (status) {
              url += `&status=${encodeURIComponent(status)}`;
            }

            const json = await fetchAPI(url);
            if (json.ok) {
              drillData.value.list = json.data || [];
            } else {
              triggerToast('Gagal Muat Data', json.error || 'Gagal memuat detail status', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          } finally {
            drillData.value.loading = false;
          }
        };

        let drillSearchTimer = null;
        const onDrillSearchInput = () => {
          if (drillSearchTimer) clearTimeout(drillSearchTimer);
          drillSearchTimer = setTimeout(async () => {
            const q = (drillSearchQuery.value || '').trim();
            try {
              drillData.value.loading = true;
              let url = `/master/stocks_list?page=1&limit=1500`;
              if (drillData.value.part_type) url += `&type=${encodeURIComponent(drillData.value.part_type)}`;
              if (drillData.value.status === 'Part Diganti') {
                url += `&replaced=1`;
              } else if (drillData.value.status) {
                url += `&status=${encodeURIComponent(drillData.value.status)}`;
              }
              if (q) {
                url += `&q=${encodeURIComponent(q)}`;
              }
              const json = await fetchAPI(url);
              if (json.ok) {
                drillData.value.list = json.data || [];
              }
            } catch (e) {
              console.error(e);
            } finally {
              drillData.value.loading = false;
            }
          }, 300);
        };

        const applyFilterFromDrilldown = () => {
          masterFilter.value.type = drillData.value.part_type || '';
          masterFilter.value.status = drillData.value.status || '';
          masterFilter.value.q = drillSearchQuery.value || '';
          showStatusDrillModal.value = false;
          fetchMasterStockList(1);
          triggerToast('Filter Diterapkan', `Menampilkan tabel utama untuk status '${drillData.value.status}'`, 'success');
        };

        const exportDrillListToExcel = async () => {
          if (!filteredDrillList.value.length) {
            triggerToast('Export Excel', 'Tidak ada data untuk diexport!', 'warn');
            return;
          }

          try {
            if (typeof ExcelJS === 'undefined') {
              const data = filteredDrillList.value.map((item, idx) => ({
                'No': idx + 1,
                'Tipe Part': `Part ${item.part_type}`,
                'Nomor Produksi (SN)': item.sn,
                'Kode CS': item.cs || '-',
                'Status Kondisi': item.effective_status || item.stock_status,
                'Catatan / Alasan': item.assembly_reason || '-',
                'No. Doc': item.tray_code || '-',
                'Source Document MO': item.source_document_mo || '-',
                'Operator Perakitan': getOperatorFullName(item.pic_assembly || item.assigned_operator) || '-',
                'Tgl & Jam Selesai': formatExcelDateTime(item.assembly_finished_at || item.allocated_at)
              }));
              const worksheet = XLSX.utils.json_to_sheet(data);
              const workbook = XLSX.utils.book_new();
              const sheetName = `Part${drillData.value.part_type || 'All'}_${drillData.value.status}`;
              XLSX.utils.book_append_sheet(workbook, worksheet, sheetName.substring(0, 31));
              const fileName = `MasterStock_Part${drillData.value.part_type || 'All'}_${drillData.value.status}.xlsx`;
              XLSX.writeFile(workbook, fileName);
              triggerToast('Berhasil', `File ${fileName} berhasil didownload!`, 'success');
              return;
            }

            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Sistem Assembly & Traceability NKRI';
            workbook.created = new Date();

            const pTypeTitle = drillData.value.part_type ? `PART ${drillData.value.part_type}` : 'SEMUA PART';
            const statusTitle = (drillData.value.status || 'ALL').toUpperCase();

            const sheet = workbook.addWorksheet(`Master_${pTypeTitle}_${statusTitle}`.substring(0, 31), {
              pageSetup: {
                paperSize: 9, // A4
                orientation: 'landscape',
                fitToPage: true,
                fitToWidth: 1,
                fitToHeight: 0,
                showGridLines: true,
                horizontalCentered: true,
                margins: { left: 0.7, right: 0.7, top: 0.75, bottom: 0.75, header: 0.3, footer: 0.3 }
              }
            });

            const columns = [
              { header: 'No', width: 6 },
              { header: 'Tipe Part', width: 14 },
              { header: 'Nomor Produksi (SN)', width: 22 },
              { header: 'Kode CS', width: 14 },
              { header: 'Status Kondisi', width: 16 },
              { header: 'No. Doc / Tray', width: 16 },
              { header: 'Source Doc MO', width: 18 },
              { header: 'Operator Perakitan', width: 22 },
              { header: 'Tgl & Jam Selesai', width: 18 },
              { header: 'Catatan / Alasan', width: 26 }
            ];

            // 1. Title Banner
            const titleRow = sheet.addRow([`DAFTAR STOK MASTER ${pTypeTitle} - STATUS ${statusTitle}`]);
            titleRow.font = { name: 'Arial', size: 13, bold: true, color: { argb: 'FF1E293B' } };
            titleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 26;
            sheet.mergeCells(1, 1, 1, columns.length);

            // 2. Subtitle / Metadata
            const subTitleRow = sheet.addRow([`Waktu Ekspor: ${formatDateTime(new Date())} | Total Data: ${filteredDrillList.value.length} Item`]);
            subTitleRow.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF64748B' } };
            subTitleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            subTitleRow.height = 18;
            sheet.mergeCells(2, 1, 2, columns.length);

            sheet.addRow([]); // blank row

            // 3. Header Row (Row 4)
            const headerRow = sheet.addRow(columns.map(c => c.header));
            headerRow.height = 26;
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

            // 4. Data Rows
            const colMaxLengths = columns.map(c => c.header.length);

            filteredDrillList.value.forEach((item, idx) => {
              const curStatus = item.effective_status || item.stock_status || 'Tersedia';
              const rowValues = [
                idx + 1,
                `Part ${item.part_type}`,
                item.sn,
                item.cs || '-',
                curStatus.toUpperCase(),
                item.tray_code || '-',
                item.source_document_mo || '-',
                getOperatorFullName(item.pic_assembly || item.assigned_operator) || '-',
                formatExcelDateTime(item.assembly_finished_at || item.allocated_at),
                item.assembly_reason || '-'
              ];

              rowValues.forEach((val, cIdx) => {
                const strLen = String(val || '').length;
                if (strLen > colMaxLengths[cIdx]) colMaxLengths[cIdx] = strLen;
              });

              const row = sheet.addRow(rowValues);
              row.height = 22;

              let statusFont = 'FF475569';
              let statusBg = 'FFF1F5F9';
              const sUpper = curStatus.toUpperCase();
              if (sUpper === 'SCRAP') { statusFont = 'FFDC2626'; statusBg = 'FFFEE2E2'; }
              else if (sUpper === 'HOLD') { statusFont = 'FFD97706'; statusBg = 'FFFEF3C7'; }
              else if (sUpper === 'REWORK') { statusFont = 'FF7C3AED'; statusBg = 'FFF3E8FF'; }
              else if (sUpper === 'GOOD' || sUpper === 'TERSEDIA') { statusFont = 'FF059669'; statusBg = 'FFD1FAE5'; }

              row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                cell.font = { name: 'Arial', size: 9.5, color: { argb: 'FF1E293B' } };
                cell.border = {
                  top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                };

                if (colNumber === 1 || colNumber === 2 || colNumber === 4 || colNumber === 9) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (colNumber === 3) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0F172A' } };
                } else if (colNumber === 5) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: statusFont } };
                  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: statusBg } };
                } else {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                }

                if (idx % 2 === 1 && colNumber !== 5) {
                  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                }
              });
            });

            // Set dynamic column widths with padding
            sheet.columns.forEach((column, i) => {
              const maxLen = colMaxLengths[i] || 10;
              column.width = Math.max(maxLen + 5, columns[i].width || 12);
            });

            const fileName = `MasterStock_Part${drillData.value.part_type || 'All'}_${drillData.value.status}.xlsx`;
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            setTimeout(() => { document.body.removeChild(a); window.URL.revokeObjectURL(url); }, 250);

            triggerToast('Berhasil', `File ${fileName} berhasil diexport dengan tata letak cetak A4!`, 'success');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };


        // Admin Danger Zone Database Reset
        const confirmResetDatabase = async () => {
          isResettingDb.value = true;
          try {
            const res = await fetchAPI('/admin/clear_trays', { method: 'POST' });
            if (res.ok) {
              triggerToast('Reset Berhasil', res.message || 'Database Tray & Perakitan berhasil dikosongkan!', 'success');
              showResetDbModal.value = false;
              currentTray.value = null;
              currentTrayParts.value = [];
              currentTrayAssemblies.value = [];
              selectedTrayCode.value = '';
              inProgressMap.value = {};
              try {
                Object.keys(localStorage).forEach(k => {
                  if (k.startsWith('inProgressMap_')) localStorage.removeItem(k);
                });
              } catch (_) { }
              await loadTrays();
              await loadMasterStocksSummary();
              await fetchMasterStockList(1);
            } else {
              triggerToast('Gagal Reset', res.error || 'Gagal mereset database', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message, 'error');
          } finally {
            isResettingDb.value = false;
          }
        };


        const testingPointsList = ref([]);
        const isLoadingTestingPoints = ref(false);
        const isSavingTestingPoint = ref(false);
        const editingTestingPointId = ref(null);
        const testingPointFilterQ = ref('');
        const testingPointForm = ref({
          test_num: null,
          category: 'Umum / Fungsional',
          instruction: '',
          output: '',
          images: []
        });

        const showInstructionGuideModal = ref(false);
        const currentGuideTestPoint = ref(null);
        const currentGuideImages = ref([]);
        const guideCarouselIndex = ref(0);

        // Single retest state moved to public/js/modules/tf1.js (Fase 2D)

        const filteredTestingPointsList = computed(() => {
          const q = (testingPointFilterQ.value || '').toLowerCase().trim();
          if (!q) return testingPointsList.value;
          return (testingPointsList.value || []).filter(pt => {
            return String(pt.test_num).includes(q) ||
              (pt.instruction || '').toLowerCase().includes(q) ||
              (pt.output || '').toLowerCase().includes(q) ||
              (pt.category || '').toLowerCase().includes(q);
          });
        });

        const testingPointInitialSnapshot = ref(null);

        const captureTestingPointSnapshot = () => {
          testingPointInitialSnapshot.value = {
            id: editingTestingPointId.value,
            test_num: testingPointForm.value.test_num,
            category: testingPointForm.value.category || 'Umum / Fungsional',
            instruction: testingPointForm.value.instruction || '',
            output: testingPointForm.value.output || '',
            images: JSON.stringify(testingPointForm.value.images || [])
          };
        };

        isTestingPointFormDirty = () => {
          if (currentTab.value !== 'users' || adminSubTab.value !== 'testing_points') return false;
          if (!testingPointForm.value) return false;
          const f = testingPointForm.value;
          if (!editingTestingPointId.value) {
            // Mode Tambah Point Baru: dirty jika instruksi/output diisi, gambar dipilih, atau kategori diubah
            return Boolean(
              (f.instruction && f.instruction.trim().length > 0) ||
              (f.output && f.output.trim().length > 0) ||
              (f.images && f.images.length > 0) ||
              (f.category && f.category !== 'Umum / Fungsional')
            );
          }
          // Mode Edit Point: bandingkan dengan snapshot saat edit dimulai
          const snap = testingPointInitialSnapshot.value;
          if (!snap) return false;
          return Boolean(
            Number(f.test_num) !== Number(snap.test_num) ||
            (f.category || 'Umum / Fungsional') !== snap.category ||
            (f.instruction || '').trim() !== snap.instruction.trim() ||
            (f.output || '').trim() !== snap.output.trim() ||
            JSON.stringify(f.images || []) !== snap.images
          );
        };

        const loadTestingPoints = async () => {
          isLoadingTestingPoints.value = true;
          try {
            const res = await fetchAPI('/testing-points');
            if (res && res.ok && Array.isArray(res.data)) {
              testingPointsList.value = res.data.map(pt => {
                let imgs = pt.images;
                if (typeof imgs === 'string') {
                  try { imgs = JSON.parse(imgs); } catch (_) { imgs = []; }
                }
                if (!Array.isArray(imgs)) imgs = [];
                return {
                  ...pt,
                  images: imgs.filter(img => typeof img === 'string' && img.trim().length > 0)
                };
              });
              if (!editingTestingPointId.value && (!testingPointForm.value.test_num || testingPointForm.value.test_num === 1)) {
                testingPointForm.value.test_num = (res.data.length ? Math.max(...res.data.map(p => p.test_num)) + 1 : 1);
              }
            }
          } catch (e) {
            console.warn('Gagal memuat testing points:', e);
          } finally {
            isLoadingTestingPoints.value = false;
          }
        };

        const resetTestingPointForm = () => {
          editingTestingPointId.value = null;
          testingPointForm.value = {
            test_num: (testingPointsList.value.length ? Math.max(...testingPointsList.value.map(p => p.test_num)) + 1 : 1),
            category: 'Umum / Fungsional',
            instruction: '',
            output: '',
            images: []
          };
          testingPointInitialSnapshot.value = null;
        };

        const editTestingPoint = (pt) => {
          editingTestingPointId.value = pt.id;
          let imgs = pt.images || [];
          if (typeof imgs === 'string') {
            try { imgs = JSON.parse(imgs); } catch (_) { imgs = []; }
          }
          testingPointForm.value = {
            test_num: pt.test_num,
            category: pt.category || 'Umum / Fungsional',
            instruction: pt.instruction || '',
            output: pt.output || '',
            images: Array.isArray(imgs) ? [...imgs] : []
          };
          captureTestingPointSnapshot();
          window.scrollTo({ top: 180, behavior: 'smooth' });
        };

        const saveTestingPoint = async () => {
          if (isSavingTestingPoint.value) return false;
          if (!testingPointForm.value.instruction || !testingPointForm.value.instruction.trim() ||
            !testingPointForm.value.output || !testingPointForm.value.output.trim()) {
            triggerToast('Validasi', 'Instruksi kerja dan kriteria output wajib diisi!', 'error');
            return false;
          }
          isSavingTestingPoint.value = true;
          try {
            const payload = {
              id: editingTestingPointId.value,
              test_num: testingPointForm.value.test_num,
              category: testingPointForm.value.category || 'Umum / Fungsional',
              instruction: testingPointForm.value.instruction.trim(),
              output: testingPointForm.value.output.trim(),
              images: testingPointForm.value.images || []
            };
            const endpoint = editingTestingPointId.value ? '/admin/testing-points/update' : '/admin/testing-points';
            const res = await fetchAPI(endpoint, {
              method: 'POST',
              body: JSON.stringify(payload)
            });
            if (res && res.ok) {
              triggerToast('Sukses', res.message || 'Testing point berhasil disimpan.', 'success');
              resetTestingPointForm();
              await loadTestingPoints();
              return true;
            } else {
              triggerToast('Gagal', (res && res.error) || 'Terjadi kesalahan sistem', 'error');
              return false;
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
            return false;
          } finally {
            isSavingTestingPoint.value = false;
          }
        };

        confirmAdminUnsavedNavigation = async (shouldSave) => {
          if (isSavingAdminUnsaved.value) return;
          const nav = pendingAdminNavigation.value;
          const ctx = adminUnsavedContext.value;

          if (shouldSave) {
            isSavingAdminUnsaved.value = true;
            try {
              if (ctx === 'users') {
                const ok = await saveUser();
                if (!ok) {
                  showAdminUnsavedModal.value = false;
                  return;
                }
              } else if (ctx === 'testing_points') {
                const ok = await saveTestingPoint();
                if (!ok) {
                  showAdminUnsavedModal.value = false;
                  return;
                }
              }
            } finally {
              isSavingAdminUnsaved.value = false;
            }
          } else {
            if (ctx === 'users') {
              resetUserForm();
              triggerToast('Info', 'Perubahan input pengguna dibatalkan dan dihapus.', 'info');
            } else if (ctx === 'testing_points') {
              resetTestingPointForm();
              triggerToast('Info', 'Perubahan input testing point dibatalkan dan dihapus.', 'info');
            }
          }

          showAdminUnsavedModal.value = false;
          pendingAdminNavigation.value = null;

          if (nav) {
            bypassAdminUnsavedCheck = true;
            try {
              navigateTo(nav.tab, nav.subTab, nav.isFromPopState);
            } finally {
              bypassAdminUnsavedCheck = false;
            }
          }
        };

        cancelAdminUnsavedNavigation = () => {
          showAdminUnsavedModal.value = false;
          pendingAdminNavigation.value = null;
        };

        const deleteTestingPoint = async (pt) => {
          const ok = await showConfirmCard({
            title: 'Hapus Testing Point',
            message: `Apakah Anda yakin ingin menghapus Testing Point #${pt.test_num} (${pt.instruction})?`,
            icon: 'fa-solid fa-trash',
            iconBg: 'bg-red-100 text-red-600',
            confirmText: 'Ya, Hapus',
            confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
          });
          if (!ok) return;
          try {
            const res = await fetchAPI('/admin/testing-points/delete', {
              method: 'POST',
              body: JSON.stringify({ id: pt.id, test_num: pt.test_num })
            });
            if (res && res.ok) {
              triggerToast('Dihapus', res.message || 'Testing point berhasil dihapus', 'info');
              await loadTestingPoints();
            } else {
              triggerToast('Gagal', (res && res.error) || 'Gagal menghapus testing point', 'error');
            }
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const handleImageUpload = (event) => {
          const files = event.target.files;
          if (!files || !files.length) return;
          for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            reader.onload = (e) => {
              testingPointForm.value.images.push(e.target.result);
            };
            reader.readAsDataURL(file);
          }
          event.target.value = '';
        };

        const removeImageFromForm = (index) => {
          testingPointForm.value.images.splice(index, 1);
        };

        const getTestImagesCount = (testNum) => {
          const target = (testingPointsList.value || []).find(p => Number(p.test_num) === Number(testNum));
          if (!target || !target.images) return 0;
          let imgs = target.images;
          if (typeof imgs === 'string') {
            try { imgs = JSON.parse(imgs); } catch (_) { imgs = []; }
          }
          return Array.isArray(imgs) ? imgs.filter(x => typeof x === 'string' && x.trim().length > 0).length : 0;
        };

        const getTestPointImagesCount = (pt) => {
          if (!pt) return 0;
          let imgs = pt.images;
          if (typeof imgs === 'string') {
            try { imgs = JSON.parse(imgs); } catch (_) { imgs = []; }
          }
          return Array.isArray(imgs) ? imgs.filter(x => typeof x === 'string' && x.trim().length > 0).length : 0;
        };

        const getTestCategory = (testNum) => {
          const target = (testingPointsList.value || []).find(p => Number(p.test_num) === Number(testNum))
            || defaultAssyTestItems.find(p => Number(p.test_num) === Number(testNum));
          return (target && target.category) || 'Umum / Fungsional';
        };

        const openInstructionGuideModal = (checklistItem) => {
          if (!checklistItem) return;
          const tNum = Number(checklistItem.test_num);
          const target = (testingPointsList.value || []).find(p => Number(p.test_num) === tNum);
          let imgs = [];
          if (target && target.images) {
            imgs = target.images;
            if (typeof imgs === 'string') {
              try { imgs = JSON.parse(imgs); } catch (_) { imgs = []; }
            }
          }
          currentGuideTestPoint.value = target || {
            test_num: tNum,
            instruction: checklistItem.name || `Uji #${tNum}`,
            output: checklistItem.description || '-',
            category: 'Umum / Fungsional'
          };
          currentGuideImages.value = Array.isArray(imgs) ? imgs : [];
          guideCarouselIndex.value = 0;
          showInstructionGuideModal.value = true;
        };

        const nextGuideSlide = () => {
          if (currentGuideImages.value.length <= 1) return;
          guideCarouselIndex.value = (guideCarouselIndex.value + 1) % currentGuideImages.value.length;
        };

        const prevGuideSlide = () => {
          if (currentGuideImages.value.length <= 1) return;
          guideCarouselIndex.value = (guideCarouselIndex.value - 1 + currentGuideImages.value.length) % currentGuideImages.value.length;
        };


        const pagedAdminSets = computed(() => { const s = pgState.adminSets; return (filteredAdminSetsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminParts = computed(() => { const s = pgState.adminParts; return (filteredAdminPartsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminRepl = computed(() => { const s = pgState.adminRepl; const list = filteredAdminReplacements.value || []; const maxPg = Math.max(1, Math.ceil(list.length / s.size)); if (s.page > maxPg) s.page = 1; return list.slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAuditLogs = computed(() => { const s = pgState.auditLogs; return (filteredAuditLogs.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedDowntime = computed(() => { const s = pgState.downtime; return (downtimeLogsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedTestPoint = computed(() => { const s = pgState.testPoint; return (filteredTestingPointsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedUsers = computed(() => { const s = pgState.users; return (usersList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedMasterStock = computed(() => { const s = pgState.masterStock; return (masterStockList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to qc.js] const pagedQCList = computed(() => { const s = pgState.qcList; return (filteredQCList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });

        const totalPgAdminSets = computed(() => Math.max(1, Math.ceil((filteredAdminSetsOnly.value || []).length / pgState.adminSets.size)));
        const totalPgAdminParts = computed(() => Math.max(1, Math.ceil((filteredAdminPartsOnly.value || []).length / pgState.adminParts.size)));
        const totalPgAdminRepl = computed(() => Math.max(1, Math.ceil((filteredAdminReplacements.value || []).length / pgState.adminRepl.size)));
        const totalPgAuditLogs = computed(() => Math.max(1, Math.ceil((filteredAuditLogs.value || []).length / pgState.auditLogs.size)));
        const totalPgDowntime = computed(() => Math.max(1, Math.ceil((downtimeLogsList.value || []).length / pgState.downtime.size)));
        const totalPgTestPoint = computed(() => Math.max(1, Math.ceil((filteredTestingPointsList.value || []).length / pgState.testPoint.size)));
        const totalPgUsers = computed(() => Math.max(1, Math.ceil((usersList.value || []).length / pgState.users.size)));
        const totalPgMasterStock = computed(() => Math.max(1, Math.ceil((masterStockList.value || []).length / pgState.masterStock.size)));
        // [Moved to qc.js] const totalPgQCList = computed(() => Math.max(1, Math.ceil((filteredQCList.value || []).length / pgState.qcList.size)));

        // ==========================================
        // CMS & DIAGNOSTIK SERVER STATE & METHODS (/admin)
        // ==========================================
        const cmsActiveTab = ref('cleanup');
        const cmsLoading = ref(false);
        const cmsErrorLogs = ref([]);
        const cmsSlowQueries = ref([]);
        const cmsAnomalies = ref({ count: 0, orphan_parts: [], stuck_sessions: [], stock_desync: [], duplicate_parts: [] });
        const cmsScanning = ref(false);
        const cmsRepairing = ref(false);
        const cmsDoSettings = ref({});

        const loadCmsPortalData = async () => {
          if (cmsActiveTab.value === 'errors') {
            await fetchCmsErrorLogs();
          } else if (cmsActiveTab.value === 'slow_queries') {
            await fetchCmsSlowQueries();
          } else if (cmsActiveTab.value === 'anomalies') {
            await scanCmsAnomalies();
          } else if (cmsActiveTab.value === 'do_settings') {
            await fetchCmsDoSettings();
          }
        };

        const fetchCmsErrorLogs = async () => {
          try {
            const res = await fetch('/api/admin/error-logs?limit=100', {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) cmsErrorLogs.value = data.data || [];
          } catch (e) {
            console.error('Error fetching error logs:', e);
          }
        };

        const clearCmsErrorLogs = async () => {
          if (!confirm('Apakah Anda yakin ingin menghapus seluruh catatan error backend?')) return;
          try {
            const res = await fetch('/api/admin/error-logs/clear', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              cmsErrorLogs.value = [];
              showToast('Seluruh riwayat error berhasil dibersihkan.', 'success');
            }
          } catch (e) {
            showToast('Gagal membersihkan log error: ' + e.message, 'error');
          }
        };

        const fetchCmsSlowQueries = async () => {
          try {
            const res = await fetch('/api/admin/slow-queries?limit=100', {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) cmsSlowQueries.value = data.data || [];
          } catch (e) {
            console.error('Error fetching slow queries:', e);
          }
        };

        const clearCmsSlowQueries = async () => {
          if (!confirm('Apakah Anda yakin ingin menghapus seluruh log slow query?')) return;
          try {
            const res = await fetch('/api/admin/slow-queries/clear', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              cmsSlowQueries.value = [];
              showToast('Seluruh riwayat slow query berhasil dibersihkan.', 'success');
            }
          } catch (e) {
            showToast('Gagal membersihkan log slow query: ' + e.message, 'error');
          }
        };

        const scanCmsAnomalies = async () => {
          cmsScanning.value = true;
          try {
            const res = await fetch('/api/admin/anomalies/scan', {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              cmsAnomalies.value = data.data || { count: 0 };
              if (data.data && data.data.count === 0) {
                showToast('Pemindaian selesai: Seluruh data normal dan konsisten!', 'success');
              } else {
                showToast(`Pemindaian selesai: Ditemukan ${data.data.count} anomali data.`, 'warning');
              }
            }
          } catch (e) {
            showToast('Gagal memindai anomali: ' + e.message, 'error');
          } finally {
            cmsScanning.value = false;
          }
        };

        const repairCmsAnomalies = async () => {
          if (!confirm('Jalankan 1-Click Auto Repair untuk memperbaiki seluruh anomali data secara otomatis?')) return;
          cmsRepairing.value = true;
          try {
            const res = await fetch('/api/admin/anomalies/repair', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              showToast(data.message || '1-Click Auto Repair berhasil dijalankan!', 'success');
              await scanCmsAnomalies();
            } else {
              showToast(data.error || 'Gagal menjalankan repair', 'error');
            }
          } catch (e) {
            showToast('Gagal menjalankan repair: ' + e.message, 'error');
          } finally {
            cmsRepairing.value = false;
          }
        };

        const clearDatabaseTrays = async () => {
          const c1 = confirm('PERINGATAN TINGKAT TINGGI:\n\nTindakan ini akan mengosongkan seluruh data Tray, Part dalam Tray, Rakitan, dan mengembalikan 18.000 part master stock ke status TERSEDIA.\n\nApakah Anda yakin ingin melanjutkan?');
          if (!c1) return;
          const c2 = prompt('Ketik "KONFIRMASI" untuk mengeksekusi pembersihan database:');
          if (c2 !== 'KONFIRMASI') {
            showToast('Pembersihan dibatalkan.', 'info');
            return;
          }

          cmsLoading.value = true;
          try {
            const res = await fetch('/api/admin/cms/clear-trays', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              showToast(data.message || 'Database tray berhasil dibersihkan dan 18.000 stok dipulihkan.', 'success');
              if (typeof fetchMasterStockList === 'function') fetchMasterStockList();
            } else {
              showToast(data.error || 'Gagal membersihkan database', 'error');
            }
          } catch (e) {
            showToast('Error: ' + e.message, 'error');
          } finally {
            cmsLoading.value = false;
          }
        };

        const clearDowntimeLogs = async () => {
          if (!confirm('Apakah Anda yakin ingin menghapus seluruh riwayat jeda / downtime operator?')) return;
          cmsLoading.value = true;
          try {
            const res = await fetch('/api/admin/cms/clear-downtimes', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              showToast(data.message || 'Riwayat downtime berhasil dibersihkan.', 'success');
            } else {
              showToast(data.error || 'Gagal menghapus downtime', 'error');
            }
          } catch (e) {
            showToast('Error: ' + e.message, 'error');
          } finally {
            cmsLoading.value = false;
          }
        };

        const clearAuditLogs = async () => {
          if (!confirm('Apakah Anda yakin ingin menghapus seluruh catatan audit log sistem?')) return;
          cmsLoading.value = true;
          try {
            const res = await fetch('/api/admin/cms/clear-audit-logs', {
              method: 'POST',
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) {
              showToast(data.message || 'Seluruh audit log sistem berhasil dibersihkan.', 'success');
            } else {
              showToast(data.error || 'Gagal menghapus audit log', 'error');
            }
          } catch (e) {
            showToast('Error: ' + e.message, 'error');
          } finally {
            cmsLoading.value = false;
          }
        };

        const fetchCmsDoSettings = async () => {
          try {
            const res = await fetch('/api/admin/cms/do-settings', {
              headers: { 'Authorization': 'Bearer ' + localStorage.getItem('token') }
            });
            const data = await res.json();
            if (data.ok) cmsDoSettings.value = data.data || {};
          } catch (e) {
            console.error('Error fetching DO settings:', e);
          }
        };

        const saveCmsDoSettings = async () => {
          cmsLoading.value = true;
          try {
            const res = await fetch('/api/admin/cms/do-settings', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token')
              },
              body: JSON.stringify(cmsDoSettings.value)
            });
            const data = await res.json();
            if (data.ok) {
              showToast(data.message || 'Pengaturan Dokumen Pengiriman berhasil disimpan.', 'success');
            } else {
              showToast(data.error || 'Gagal menyimpan pengaturan', 'error');
            }
          } catch (e) {
            showToast('Error: ' + e.message, 'error');
          } finally {
            cmsLoading.value = false;
          }
        };


    return {
      activeAdminSet, addPartBulkForm, addPartBulkMode, addPartBulkResult,
      addPartForm, addPartLoading, addPartTab, adminApproveAssyRereg,
      adminApproveHoldItem, adminApproveReplacement, adminPartFilter, adminPendingCount,
      adminPendingQCSets, adminPendingReplacements, adminPendingReplacementsCount, adminPendingRequests,
      adminPendingRequestsCount, adminPendingSets, adminPendingSetsTotalCount, adminRejectReplacement,
      adminRepEndDate, adminRepPartTypeFilter, adminRepSearchQuery, adminRepStartDate,
      adminRepStatusFilter, adminRepStageFilter, getReplacementStage, getReplacementStageLabel, replStageCounts,
      getDeclinedSource,
      replCountPending, replCountApproved, replCountRejected, adminReregApprovedOperator, adminReregNotes, adminSetEndDate,
      adminSetPartTypeFilter, adminSetProcessCounts, adminSetProcessFilter, adminSetSearchQuery,
      adminSetStartDate, adminSetStatusFilter, adminSetViewMode, adminVerifyFilter,
      adminVerifyForm, allAdminSets, allTotalCount, applyFilterFromDrilldown,
      approveReRegister, auditFilter, auditLogsList, cancelAdminUnsavedNavigation,
      captureTestingPointSnapshot, captureUserSnapshot, clearAuditLogs, clearCmsErrorLogs,
      clearCmsSlowQueries, clearDatabaseTrays, clearDowntimeLogs, cmsActiveTab,
      cmsAnomalies, cmsDoSettings, cmsErrorLogs, cmsLoading,
      cmsRepairing, cmsScanning, cmsSlowQueries, confirmAdminUnsavedNavigation,
      confirmReregisterSet, confirmResetDatabase, currentGuideImages, currentGuideTestPoint,
      debouncedFetchMasterStockList, declinedRequestsCount, declinedSetsCount, declinedTotalCount,
      deleteAdminDeclinedSet, deleteAdminSetPart, deleteAdminVerifyRequest, deleteRecentlyAddedPart,
      deleteTestingPoint, deleteUser, downtimeFilter, downtimeLogsList,
      drillData, drillSearchQuery, drillSearchTimer, editTestingPoint,
      editUser, editingTestingPointId, editingUserId, exportDrillListToExcel,
      fetchCmsDoSettings, fetchCmsErrorLogs, fetchCmsSlowQueries, fetchMasterStockList,
      filteredAdminPartsOnly, filteredAdminQCSets, filteredAdminReplacements, filteredAdminScrapRequests,
      filteredAdminSets, filteredAdminSetsOnly, filteredAuditLogs, filteredDrillList,
      filteredTestingPointsList, formatFailureReason, formatQCReworkReason, getNextPUTrayCode,
      getSetSNSet, getStockStatusBySN, getTestCategory, getTestImagesCount,
      getTestPointImagesCount, guideCarouselIndex, handleImageUpload, hasUnverifiedPartsInActiveSet,
      isLoadingMasterPartDetail, isLoadingTestingPoints, isReplacementVerified, isResettingDb,
      isSavingTestingPoint, isSetFullyVerified, isSubmittingAllAdminVerify, isSubmittingDeclinedRestore,
      isSubmittingReregisterSet, isTargetSetRework, isTestingPointFormDirty, isUserFormDirty,
      laporanDetailData, loadAuditLogs, loadCmsPortalData, loadDowntimeLogs,
      loadMasterData, loadMasterParts, loadMasterStocksSummary, loadPendingVerifications,
      loadRecentlyAddedParts, loadTestingPoints, loadUsersList, masterFilter,
      masterPartTrackingData, masterParts, masterStockList, masterStockSearchTimer,
      nextGuideSlide, onDrillSearchInput, openAdminPartDetailModal, openAdminReplacementDetailModal,
      openAdminSetDetailModal, openAdminVerifyModal, openAdminVerifySetModal, openInstructionGuideModal,
      openLaporanVerificationDetail, openMasterPartTrackingModal, openOverrideModal, openReregisterModal,
      openReregisterSetConfirmModal, openStatusDrilldown, otherRequestsCount, otherSetsCount,
      otherTotalCount, overrideForm, pagedAdminParts, pagedAdminRepl,
      pagedAdminSets, pagedAuditLogs, pagedDowntime, pagedMasterStock,
      pagedTestPoint, pagedUsers, parseReplacements, prevGuideSlide,
      qcSetsCount, recentAddedFilter, recentAddedLoading, recentAddedParts,
      recentAddedSearch, filteredRecentAddedParts, pagedRecentAdded, totalPgRecentAdded,
      existingMasterPart, isCheckingExistingPart, bulkPreviewData, onSingleSnInput, onBulkSnInput,
      removeImageFromForm, repairCmsAnomalies, replacedPartsTotalCount, reregisterForm,
      reregisterNewReworkReason, resetAddPartBulkForm, resetAddPartForm, resetAdminPartFilter,
      resetAdminRepFilter, resetAdminSetFilter, resetAdminVerifyFilter, resetAuditFilter,
      resetDowntimeFilter, resetTestingPointForm, resetUserForm, resetUserSession,
      restoreDeclinedSetToAvailable, saveCmsDoSettings, saveTestingPoint, saveUser,
      scanCmsAnomalies, selectedAdminPartDetail, selectedAdminReplacementDetail, selectedAdminSetDetail,
      selectedMasterPartRow, showAdminPartDetailModal, showAdminReplacementDetailModal, showAdminSetDetailModal,
      showAdminVerifyModal, showAdminVerifySetModal, showInstructionGuideModal, showLaporanDetailModal,
      showMasterPartTrackingModal, showOverrideModal, showReregisterModal, showReregisterSetModal,
      showResetDbModal, showStatusDrillModal, showUserPw, stockSummary,
      submitAddBulkPart, submitAddSinglePart, submitAdminVerify, submitOverrideStatus,
      submitReregisterPart, targetReregisterSet, testingPointFilterQ, testingPointForm,
      testingPointInitialSnapshot, testingPointsList, totalPgAdminParts, totalPgAdminRepl,
      totalPgAdminSets, totalPgAuditLogs, totalPgDowntime, totalPgMasterStock,
      totalPgTestPoint, totalPgUsers, userForm, userFormInitialSnapshot,
      usersList, verifyAdminSinglePart, verifyAllPartsInActiveSet
    };
  };
})(window);
