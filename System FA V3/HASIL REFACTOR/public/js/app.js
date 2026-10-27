    const { createApp, ref, computed, nextTick, onMounted, onUnmounted, watch } = Vue;

    const app = createApp({
      setup() {
        const userRole = ref(localStorage.getItem('role') || '');
        const userName = ref(localStorage.getItem('username') || 'Operator');
        const userFullName = ref(localStorage.getItem('full_name') || '');
        const operatorName = ref(localStorage.getItem('username') || 'Operator');
        const userDepartment = ref(localStorage.getItem('department') || 'assembly');
        let initialPerms = {};
        try {
          initialPerms = JSON.parse(localStorage.getItem('permissions') || '{}');
        } catch (_) { }
        const userPermissions = ref(initialPerms);

        const syncCurrentUserPermissions = () => {
          try {
            const rawPerms = localStorage.getItem('permissions');
            if (rawPerms) {
              const parsed = typeof rawPerms === 'string' ? JSON.parse(rawPerms) : rawPerms;
              if (parsed && typeof parsed === 'object') {
                userPermissions.value = { ...userPermissions.value, ...parsed };
              }
            }
            const currentDept = localStorage.getItem('department');
            if (currentDept) userDepartment.value = currentDept;
            const currentRole = localStorage.getItem('role');
            if (currentRole) userRole.value = currentRole;
            const currentFullName = localStorage.getItem('full_name');
            if (currentFullName) userFullName.value = currentFullName;

            // Self-healing: if role is admin / super admin or user is Yasin, ensure all admin & verification permissions are 2
            const r = String(userRole.value || '').toLowerCase().trim();
            const u = String(userName.value || '').toLowerCase().trim();
            if (r === 'admin' || r === 'super admin') {
              const adminKeys = ['users', 'verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'audit', 'downtime', 'testing_points', 'add_part'];
              let changed = false;
              if (!userPermissions.value || typeof userPermissions.value !== 'object') {
                userPermissions.value = {};
              }
              adminKeys.forEach(k => {
                if (!userPermissions.value[k] || userPermissions.value[k] < 2) {
                  userPermissions.value[k] = 2;
                  changed = true;
                }
              });
              if (changed) {
                localStorage.setItem('permissions', JSON.stringify(userPermissions.value));
              }
            }
          } catch (e) {
            console.warn('Error syncing user permissions:', e);
          }
        };

        const managedMenuGroups = [
          {
            key: 'gudang',
            title: 'Gudang (Warehouse & Logistics)',
            icon: 'fa-solid fa-warehouse',
            badgeClass: 'bg-amber-100 text-amber-900 border-amber-300',
            menus: [
              { key: 'warehouse', title: 'Work Order (Input Part)', icon: 'fa-solid fa-boxes-packing', desc: 'Pembuatan work order & input part gudang' },
              { key: 'production_hold', title: 'Hasil Perakitan', icon: 'fa-solid fa-boxes-stacked', desc: 'Monitoring hasil & status hold perakitan' },
              { key: 'returns', title: 'Pengembalian Barang', icon: 'fa-solid fa-rotate-left', desc: 'Pengembalian part & tray dari assembly' },
              { key: 'warehouse_receipt', title: 'Penerimaan Gudang', icon: 'fa-solid fa-clipboard-check', desc: 'Verifikasi penerimaan barang siap kirim' },
              { key: 'traveler', title: 'Cetak Dokumen & Label', icon: 'fa-solid fa-print', desc: 'Cetak Traveler, ID Tray, & Label box' },
              { key: 'master', title: 'Master Stok Part', icon: 'fa-solid fa-database', desc: 'Katalog stok part, search, & status' },
              { key: 'report', title: 'History Transaksi', icon: 'fa-solid fa-clock-rotate-left', desc: 'Laporan riwayat transaksi & perakitan' },
              { key: 'delivery_order', title: 'Delivery Order & PL', icon: 'fa-solid fa-truck-ramp-box', desc: 'Surat jalan (DO) & packing list pengiriman' }
            ]
          },
          {
            key: 'perakitan',
            title: 'Perakitan (Assembly)',
            icon: 'fa-solid fa-screwdriver-wrench',
            badgeClass: 'bg-blue-100 text-blue-900 border-blue-300',
            menus: [
              { key: 'assembly', title: 'Area Perakitan (Assembly)', icon: 'fa-solid fa-gear', desc: 'Meja kerja rakit & pengerjaan part operator' },
              { key: 'reassembly', title: 'Perakitan Ulang (RU)', icon: 'fa-solid fa-arrows-rotate', desc: 'Penugasan set rakitan hasil ganti part ke operator assembly' },
              { key: 'assy_testing', title: 'Test Functional 1 (Assy)', icon: 'fa-solid fa-vial-circle-check', desc: 'Pengujian fungsional tahap awal oleh perakit' }
            ]
          },
          {
            key: 'qc',
            title: 'Quality Control (QC)',
            icon: 'fa-solid fa-list-check',
            badgeClass: 'bg-emerald-100 text-emerald-900 border-emerald-300',
            menus: [
              { key: 'qc', title: 'Test Functional 2 (QC)', icon: 'fa-solid fa-vial-circle-check', desc: 'Verifikasi & pengujian akhir oleh tim QC' }
            ]
          },
          {
            key: 'admin',
            title: 'Administrator',
            icon: 'fa-solid fa-shield-halved',
            badgeClass: 'bg-purple-100 text-purple-900 border-purple-300',
            menus: [
              { key: 'verify_set', title: 'Verifikasi /Set (Satu Set)', icon: 'fa-solid fa-boxes-stacked', desc: 'Approval unit rakitan bermasalah dari perakitan, TF1, dan QC' },
              { key: 'verify_part', title: 'Verifikasi /Part (1 Part)', icon: 'fa-solid fa-microchip', desc: 'Approval part satuan lepasan & pemulihan status ke master stok' },
              { key: 'verify_replacement', title: 'Verifikasi Pergantian Part', icon: 'fa-solid fa-wrench', desc: 'Approval permohonan pergantian part dari Hasil Perakitan' },
              { key: 'audit', title: 'Riwayat Audit Gudang', icon: 'fa-solid fa-clock-rotate-left', desc: 'Log rekonsiliasi & selisih audit stok' },
              { key: 'downtime', title: 'Audit Downtime Perakitan', icon: 'fa-solid fa-business-time', desc: 'Rekap jeda & downtime lini perakitan' },
              { key: 'testing_points', title: 'Master Testing Point', icon: 'fa-solid fa-list-check', desc: 'Parameter checklist uji TF1 & TF2 sistem' },
              { key: 'add_part', title: 'Tambah Part Number', icon: 'fa-solid fa-plus-circle', desc: 'Input Part Number baru / double marking ke Master Stok' }
            ]
          }
        ];

        const openPermissionCards = ref({
          gudang: true,
          perakitan: false,
          qc: false,
          admin: false
        });

        const togglePermissionCard = (groupKey) => {
          openPermissionCards.value[groupKey] = !openPermissionCards.value[groupKey];
        };

        const toggleAllPermissionCards = (isOpen) => {
          Object.keys(openPermissionCards.value).forEach(k => {
            openPermissionCards.value[k] = isOpen;
          });
        };

        const managedMenuList = managedMenuGroups.flatMap(g => g.menus);

        const defaultPermissions = (role = 'User') => {
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
            verify_part: isAdmin ? 2 : 0,
            verify_replacement: isAdmin ? 2 : 0,
            verify_items: isAdmin ? 2 : 0,
            audit: isAdmin ? 2 : 0,
            downtime: isAdmin ? 2 : 0,
            testing_points: isAdmin ? 2 : 0,
            add_part: isAdmin ? 2 : 0
          };
        };

        const parsePermValue = (v) => {
          if (v === undefined || v === null) return undefined;
          if (typeof v === 'number') return v;
          const s = String(v).toLowerCase().trim();
          if (s === 'full' || s === '2' || s === 'true') return 2;
          if (s === 'read' || s === 'read-only' || s === '1') return 1;
          if (s === 'none' || s === '0' || s === 'false') return 0;
          return Number(v) || 0;
        };

        const isSuperAdminUser = computed(() => {
          const u = String(userName.value || '').toLowerCase().trim();
          return u === 'admin' || u === 'miranti';
        });

        const isAdminUser = computed(() => {
          if (isSuperAdminUser.value) return true;
          const r = String(userRole.value || '').toLowerCase().trim();
          if (r === 'super admin' || r === 'admin') return true;
          if (userPermissions.value && (userPermissions.value.users > 0 || userPermissions.value.audit > 0)) return true;
          return false;
        });

        const getEffectivePermission = (menuKey) => {
          if (isSuperAdminUser.value) return 2;
          if (!userPermissions.value) {
            if (isAdminUser.value) return 2;
            return undefined;
          }
          let val = userPermissions.value[menuKey];
          if (val === undefined) {
            if (menuKey === 'verify_set' || menuKey === 'verify_part' || menuKey === 'verify_replacement') {
              val = userPermissions.value['verify_items'];
            } else if (menuKey === 'verify_items') {
              val = userPermissions.value['verify_set'] ?? userPermissions.value['verify_part'] ?? userPermissions.value['verify_replacement'];
            } else if (menuKey === 'qc') {
              val = userPermissions.value['functional_test'];
            } else if (menuKey === 'functional_test') {
              val = userPermissions.value['qc'];
            } else if (menuKey === 'warehouse') {
              val = userPermissions.value['work_order'];
            } else if (menuKey === 'work_order') {
              val = userPermissions.value['warehouse'];
            } else if (menuKey === 'report') {
              val = userPermissions.value['history'];
            } else if (menuKey === 'history') {
              val = userPermissions.value['report'];
            } else if (menuKey === 'functional_hold') {
              val = userPermissions.value['production_hold'];
            } else if (menuKey === 'add_part') {
              val = userPermissions.value['add_part'] ?? userPermissions.value['users'];
            }
          }
          const parsed = parsePermValue(val);
          // If user is Admin, verification and administrator menus must be FULL access (2) by default
          if (isAdminUser.value) {
            if (['verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'users', 'audit', 'downtime', 'testing_points', 'add_part'].includes(menuKey)) {
              if (parsed === undefined || parsed === 0) return 2;
            }
            if (parsed === undefined) return 2;
          }
          return parsed;
        };

        const hasMenuAccess = (menuKey) => {
          // 1. If admin user, administrator & verification menus are always granted
          if (isAdminUser.value) {
            if (['users', 'verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'audit', 'downtime', 'testing_points', 'add_part'].includes(menuKey)) {
              return true;
            }
          }

          // 2. Strictly obey explicit user permission configuration if present (0, 1, or 2)
          const permVal = getEffectivePermission(menuKey);
          if (permVal !== undefined) {
            return permVal > 0;
          }

          // 3. Default fallback ONLY when this menu is completely unconfigured / undefined in userPermissions:
          if (isAdminUser.value) {
            return true;
          }

          if (['users', 'verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'audit', 'downtime', 'testing_points', 'add_part'].includes(menuKey)) {
            return false;
          }

          const r = String(userRole.value || '').toLowerCase().trim();
          const dept = String(userDepartment.value || (r === 'gudang' ? 'gudang' : 'assembly')).toLowerCase();
          if (dept.includes('gudang') || dept.includes('warehouse')) {
            return ['warehouse', 'work_order', 'production_hold', 'functional_hold', 'returns', 'warehouse_receipt', 'traveler', 'master', 'report', 'history', 'delivery_order'].includes(menuKey);
          } else if (dept.includes('qc') || dept.includes('quality')) {
            return ['qc', 'functional_test', 'production_hold', 'master', 'report', 'history', 'traveler'].includes(menuKey);
          } else {
            return ['assembly', 'reassembly', 'assy_testing', 'production_hold', 'traveler', 'report', 'history'].includes(menuKey);
          }
        };

        const hasTabAccess = (tab, subTab = null) => {
          if (tab === 'users') {
            if (isAdminUser.value) return true;
            const sub = subTab || adminSubTab.value || 'users';
            if (sub === 'users') return hasMenuAccess('users');
            if (sub === 'verify_set') return hasMenuAccess('verify_set');
            if (sub === 'verify_part') return hasMenuAccess('verify_part');
            if (sub === 'verify_replacement') return hasMenuAccess('verify_replacement');
            if (sub === 'audit') return hasMenuAccess('audit');
            if (sub === 'downtime') return hasMenuAccess('downtime');
            if (sub === 'testing_points') return hasMenuAccess('testing_points');
            if (sub === 'add_part') return hasMenuAccess('add_part') || hasMenuAccess('users') || isAdminUser.value;
            return hasMenuAccess('users') || hasMenuAccess('verify_set') || hasMenuAccess('verify_part') || hasMenuAccess('verify_replacement') || hasMenuAccess('audit') || hasMenuAccess('downtime') || hasMenuAccess('testing_points') || hasMenuAccess('add_part');
          }
          if (tab === 'functional_hold') return hasMenuAccess('functional_hold') || hasMenuAccess('production_hold');
          return hasMenuAccess(tab);
        };

        const hasGroupAccess = (groupKey) => {
          if (groupKey === 'admin' && isAdminUser.value) return true;
          const grp = managedMenuGroups.find(g => g.key === groupKey);
          if (!grp) return true;
          // Group accordion header is only shown if AT LEAST ONE menu item inside it is accessible
          return grp.menus.some(m => hasMenuAccess(m.key));
        };

        const isMenuReadOnly = (menuKey) => {
          const permVal = getEffectivePermission(menuKey);
          if (permVal !== undefined) return permVal === 1;
          const r = String(userRole.value || '').toLowerCase();
          const u = String(userName.value || '').toLowerCase();
          const fn = String(userFullName.value || '').toLowerCase();
          return false;
        };

        const applyUserPermissionPreset = (preset) => {
          if (!userForm.value) return;
          if (!userForm.value.permissions) userForm.value.permissions = {};

          if (preset === 'assembly') {
            userForm.value.department = 'Assembly';
            userForm.value.permissions = {
              warehouse: 1, work_order: 1, production_hold: 2, returns: 0,
              warehouse_receipt: 0, traveler: 2, master: 1, report: 1, history: 1,
              delivery_order: 0, assembly: 2, assy_testing: 2, qc: 0, functional_test: 0,
              users: 0, verify_items: 0, audit: 0, downtime: 0, testing_points: 0
            };
          } else if (preset === 'gudang') {
            userForm.value.department = 'Warehouse';
            userForm.value.permissions = {
              warehouse: 2, work_order: 2, production_hold: 2, returns: 2,
              warehouse_receipt: 2, traveler: 2, master: 2, report: 2, history: 2,
              delivery_order: 2, assembly: 1, assy_testing: 1, qc: 0, functional_test: 0,
              users: 0, verify_items: 0, audit: 0, downtime: 0, testing_points: 0
            };
          } else if (preset === 'qc') {
            userForm.value.department = 'Assembly';
            userForm.value.permissions = {
              warehouse: 0, work_order: 0, production_hold: 2, returns: 0,
              warehouse_receipt: 1, traveler: 2, master: 1, report: 2, history: 2,
              delivery_order: 0, assembly: 1, assy_testing: 1, qc: 2, functional_test: 2,
              users: 0, verify_items: 0, audit: 0, downtime: 0, testing_points: 1
            };
          } else if (preset === 'admin' || preset === 'Admin' || preset === 'super admin' || preset === 'Super Admin') {
            userForm.value.department = 'Administrator';
            if (userForm.value.role !== 'Admin' && userForm.value.role !== 'Super Admin') {
              userForm.value.role = 'Admin';
            }
            const allFull = {};
            managedMenuList.forEach(m => { allFull[m.key] = 2; });
            allFull.work_order = 2;
            allFull.history = 2;
            allFull.functional_test = 2;
            userForm.value.permissions = allFull;
          } else if (preset === 'none') {
            const allNone = {};
            managedMenuList.forEach(m => { allNone[m.key] = 0; });
            allNone.work_order = 0;
            allNone.history = 0;
            allNone.functional_test = 0;
            userForm.value.permissions = allNone;
          }
        };

        const setGroupPermissions = (groupKey, level) => {
          if (!userForm.value) return;
          if (!userForm.value.permissions) userForm.value.permissions = {};
          const grp = managedMenuGroups.find(g => g.key === groupKey);
          if (!grp) return;
          grp.menus.forEach(m => {
            userForm.value.permissions[m.key] = level;
            if (m.key === 'warehouse') userForm.value.permissions['work_order'] = level;
            if (m.key === 'report') userForm.value.permissions['history'] = level;
            if (m.key === 'qc') userForm.value.permissions['functional_test'] = level;
          });
        };

        const onPermissionChange = (key) => {
          if (!userForm.value || !userForm.value.permissions) return;
          const val = userForm.value.permissions[key];
          if (key === 'warehouse') userForm.value.permissions['work_order'] = val;
          else if (key === 'work_order') userForm.value.permissions['warehouse'] = val;
          else if (key === 'report') userForm.value.permissions['history'] = val;
          else if (key === 'history') userForm.value.permissions['report'] = val;
          else if (key === 'qc') userForm.value.permissions['functional_test'] = val;
          else if (key === 'functional_test') userForm.value.permissions['qc'] = val;
        };

        const countGroupActiveMenus = (groupKey) => {
          if (!userForm.value || !userForm.value.permissions) return 0;
          const grp = managedMenuGroups.find(g => g.key === groupKey);
          if (!grp) return 0;
          return grp.menus.filter(m => {
            const v = userForm.value.permissions[m.key];
            return v === undefined ? false : v > 0;
          }).length;
        };

        const countUserActiveMenus = (perms) => {
          if (!perms) return 19;
          let p = perms;
          if (typeof perms === 'string') {
            try { p = JSON.parse(perms); } catch (_) { return 19; }
          }
          let count = 0;
          managedMenuList.forEach(m => {
            let val = p[m.key];
            if (val === undefined) {
              if (m.key === 'warehouse') val = p['work_order'];
              else if (m.key === 'report') val = p['history'];
              else if (m.key === 'qc') val = p['functional_test'];
            }
            if (val === undefined || val > 0) count++;
          });
          return count;
        };

        const countUserWriteMenus = (perms) => {
          if (!perms) return 0;
          let p = perms;
          if (typeof perms === 'string') {
            try { p = JSON.parse(perms); } catch (_) { return 0; }
          }
          let count = 0;
          managedMenuList.forEach(m => {
            let val = p[m.key];
            if (val === undefined) {
              if (m.key === 'warehouse') val = p['work_order'];
              else if (m.key === 'report') val = p['history'];
              else if (m.key === 'qc') val = p['functional_test'];
            }
            if (val === 2 || val === '2' || val === 'full') count++;
          });
          return count;
        };

        const formatDepartmentDisplay = (dept) => {
          if (!dept) return 'Assembly';
          const d = String(dept).toLowerCase();
          if (d.includes('warehouse') || d.includes('gudang')) return 'Warehouse';
          if (d.includes('assembly') || d.includes('production')) return 'Assembly';
          if (d.includes('admin')) return 'Administrator';
          return dept;
        };

        const getDepartmentBadgeClass = (dept) => {
          if (!dept) return 'bg-blue-50 text-blue-800 border-blue-200';
          const d = String(dept).toLowerCase();
          if (d.includes('warehouse') || d.includes('gudang')) return 'bg-amber-50 text-amber-800 border-amber-200';
          if (d.includes('admin')) return 'bg-purple-50 text-purple-800 border-purple-200';
          if (d.includes('assembly') || d.includes('production')) return 'bg-blue-50 text-blue-800 border-blue-200';
          return 'bg-slate-50 text-slate-700 border-slate-200';
        };

        const onRoleOrDeptChange = () => {
          // Preset buttons (Gudang, Perakitan, QC, Admin, Kosongkan) provide explicit one-click presets.
        };

        const normalizeAdminSubTab = (raw) => {
          if (!raw) return 'verify_set';
          const s = String(raw).toLowerCase().trim();
          if (s === 'verify_replacement' || s.includes('verify_replacement') || s.includes('replacement') || s.includes('pergantian') || s.includes('ganti_part')) {
            return 'verify_replacement';
          }
          if (s === 'verify_part' || s.includes('verify_part') || s.includes('part_satuan') || s.includes('satuan')) {
            return 'verify_part';
          }
          if (s === 'verify_set' || s.includes('verify_set') || s.includes('satu_set') || s.includes('set') || s.includes('rakitan')) {
            return 'verify_set';
          }
          if (s === 'verify_items' || s.includes('verify') || s.includes('verif') || s.includes('scrap') || s.includes('pending')) {
            return 'verify_set';
          }
          if (s.includes('audit')) return 'audit';
          if (s.includes('downtime') || s.includes('jeda')) return 'downtime';
          if (s.includes('testing') || s.includes('point') || s.includes('master_test')) return 'testing_points';
          if (s.includes('add_part') || s.includes('tambah_part') || s === 'add_part') return 'add_part';
          if (s.includes('cms') || s.includes('server') || s.includes('diagnostik') || s === 'cms_portal') return 'cms_portal';
          if (s.includes('user') || s.includes('pengguna') || s.includes('kelola')) return 'verify_set';
          return s;
        };

        const getInitialRoute = () => {
          let rawHash = (window.location.hash || '').replace('#', '').trim();
          let queryParams = {};
          if (rawHash.includes('?')) {
            const qParts = rawHash.split('?');
            rawHash = qParts[0].trim();
            try {
              const sp = new URLSearchParams(qParts[1]);
              for (const [k, v] of sp.entries()) {
                queryParams[k] = v;
              }
            } catch (_) { }
          }

          let hash = rawHash;
          if (window.location.pathname.startsWith('/admin') || window.__IS_ADMIN_PORTAL) {
            return { tab: 'users', subTab: 'cms_portal', queryParams: {} };
          }
          const rawRole = String(userRole.value || localStorage.getItem('role') || 'admin').toLowerCase().trim();
          let normalizedRole = 'admin';
          if (rawRole.includes('admin') || rawRole.includes('super')) {
            normalizedRole = 'admin';
          } else if (rawRole.includes('qc') || rawRole.includes('quality')) {
            normalizedRole = 'qc';
          } else if (rawRole.includes('gudang') || rawRole.includes('warehouse')) {
            normalizedRole = 'gudang';
          } else {
            normalizedRole = 'assembly';
          }

          const savedTab = localStorage.getItem('activeTab') || '';
          const savedSubTab = normalizeAdminSubTab(localStorage.getItem('activeAdminSubTab') || 'verify_set');

          let tab = '';
          let subTab = '';

          if (hash.includes('/')) {
            const parts = hash.split('/');
            tab = parts[0].trim();
            const rawSub = parts[1].trim();
            if (tab === 'users') {
              subTab = normalizeAdminSubTab(rawSub);
            } else if (tab === 'report') {
              subTab = rawSub;
            } else {
              subTab = rawSub;
            }
          } else if (hash.includes(':')) {
            const parts = hash.split(':');
            tab = parts[0].trim();
            const rawSub = parts[1].trim();
            if (tab === 'users') {
              subTab = normalizeAdminSubTab(rawSub);
            } else if (tab === 'report') {
              subTab = rawSub;
            } else {
              subTab = rawSub;
            }
          } else if (['verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'verify', 'verifikasi', 'audit', 'downtime', 'testing_points', 'add_part', 'tambah_part'].includes(hash.toLowerCase())) {
            tab = 'users';
            subTab = normalizeAdminSubTab(hash);
          } else {
            tab = hash;
          }

          if (!tab) {
            tab = savedTab || (normalizedRole === 'assembly' ? 'assembly' : (normalizedRole === 'qc' ? 'qc' : 'warehouse'));
            if (tab === 'users') {
              subTab = savedSubTab;
            } else if (tab === 'report') {
              subTab = localStorage.getItem('activeReportSubTab') || 'wo';
            }
          }

          const validTabs = {
            admin: ['assembly', 'assy_testing', 'qc', 'warehouse', 'returns', 'traveler', 'report', 'master', 'users', 'production_hold', 'functional_hold', 'delivery_order', 'warehouse_receipt'],
            qc: ['qc', 'traveler', 'report', 'assembly', 'assy_testing', 'production_hold', 'delivery_order', 'warehouse_receipt'],
            gudang: ['warehouse', 'qc', 'returns', 'traveler', 'report', 'master', 'assembly', 'assy_testing', 'production_hold', 'functional_hold', 'delivery_order', 'warehouse_receipt'],
            assembly: ['assembly', 'assy_testing', 'qc']
          };

          const isTabAllowedForUser = (t) => {
            if (!t) return false;
            try {
              const perms = JSON.parse(localStorage.getItem('permissions') || '{}');
              if (perms && typeof perms[t] !== 'undefined') {
                return perms[t] > 0;
              }
            } catch (_) { }
            if (normalizedRole === 'admin') return true;
            const allowedList = validTabs[normalizedRole] || [];
            return allowedList.includes(t);
          };

          const defaultRoleTab = normalizedRole === 'assembly' ? 'assembly' : (normalizedRole === 'qc' ? 'qc' : 'warehouse');
          if (!isTabAllowedForUser(tab)) {
            tab = isTabAllowedForUser(defaultRoleTab) ? defaultRoleTab : ((validTabs[normalizedRole] || []).find(t => isTabAllowedForUser(t)) || defaultRoleTab);
          }

          if (tab === 'users') {
            const validSubTabs = ['verify_set', 'verify_part', 'verify_replacement', 'verify_items', 'audit', 'downtime', 'testing_points', 'add_part'];
            subTab = normalizeAdminSubTab(subTab);
            if (!validSubTabs.includes(subTab) || !hasMenuAccess(subTab)) {
              if (savedSubTab && validSubTabs.includes(savedSubTab) && hasMenuAccess(savedSubTab)) {
                subTab = savedSubTab;
              } else {
                const firstAllowed = validSubTabs.find(st => hasMenuAccess(st));
                subTab = firstAllowed || 'verify_set';
              }
            }
          } else if (tab === 'report') {
            const validReportSubs = ['wo', 'assembly', 'assy_test', 'qc_test', 'warehouse_receipt'];
            if (!validReportSubs.includes(subTab)) {
              const savedReportSub = localStorage.getItem('activeReportSubTab');
              subTab = (savedReportSub && validReportSubs.includes(savedReportSub)) ? savedReportSub : 'wo';
            }
          }

          return { tab, subTab, queryParams };
        };

        const initialRoute = getInitialRoute();
        const currentTab = ref(initialRoute.tab);
        const adminSubTab = ref(initialRoute.subTab || 'verify_set');
        const defaultTab = computed(() => {
          const r = String(userRole.value || '').toLowerCase();
          return r.includes('assembly') ? 'assembly' : (r.includes('qc') ? 'qc' : 'warehouse');
        });

        function getYearMonthFromDate(dateVal) {
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
              const mKey = parts[1].toLowerCase();
              const mm = monthMap[mKey];
              const yyyy = parts[2].replace(/\D/g, '');
              if (mm && yyyy && yyyy.length >= 4) {
                return {
                  yy: yyyy.substring(2),
                  mm: mm,
                  yyyy: yyyy
                };
              }
            }
          }
          const d = (dateVal instanceof Date && !isNaN(dateVal.getTime())) ? dateVal : (dateVal ? new Date(dateVal) : new Date());
          if (!isNaN(d.getTime())) {
            return {
              yy: String(d.getFullYear()).substring(2),
              mm: String(d.getMonth() + 1).padStart(2, '0'),
              yyyy: String(d.getFullYear())
            };
          }
          const now = new Date();
          return {
            yy: String(now.getFullYear()).substring(2),
            mm: String(now.getMonth() + 1).padStart(2, '0'),
            yyyy: String(now.getFullYear())
          };
        }

        const getTodayDateStr = () => {
          const d = new Date();
          const pad = (n) => String(n).padStart(2, '0');
          return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        };

        const getNowWIBDateString = () => {
          return getTodayDateStr();
        };

        const defaultCrateIdForNow = computed(() => {
          const targetDate = previewDOData?.value?.do_date || activePrintDO?.value?.do_date || doForm?.value?.do_date;
          const { yy, mm } = getYearMonthFromDate(targetDate);
          return `${yy}${mm}-1001`;
        });

        // Mobile Sidebar Drawer & Dropdown State
        const isMobileMenuOpen = ref(false);
        const sidebarOpen = ref({
          gudang: false,
          perakitan: false,
          qc: false,
          admin: false
        });

        const toggleSidebarGroup = (group) => {
          sidebarOpen.value[group] = !sidebarOpen.value[group];
        };

        const setAdminSubTab = (subTab, isFromPopState = false) => {
          const targetSub = normalizeAdminSubTab(subTab) || 'verify_set';
          navigateTo('users', targetSub, isFromPopState);
        };

        // -------------------------------------------------------------------
        // Internal tab & subtab history stack & Mobile Navigation Tracking
        // -------------------------------------------------------------------
        const _tabNavStack = [];
        const _activeModalStack = [];
        let programmaticBackCount = 0;
        let isNavigatingBack = false;
        let exitGuardArmed = false;
        let exitGuardTimer = null;
        const assyTestHistoryDepth = ref(0);
        const gudangStageHistoryDepth = ref(0);

        // -------------------------------------------------------------------
        // Admin Unsaved Form Changes Guard (Master Testing & Kelola Pengguna)
        // -------------------------------------------------------------------
        let bypassAdminUnsavedCheck = false;
        const showAdminUnsavedModal = ref(false);
        const adminUnsavedContext = ref(''); // 'users' or 'testing_points'
        const pendingAdminNavigation = ref(null);
        const isSavingAdminUnsaved = ref(false);

        let isUserFormDirty = () => false;
        let isTestingPointFormDirty = () => false;
        let confirmAdminUnsavedNavigation = async () => { };
        let cancelAdminUnsavedNavigation = () => { };

        const navigateTo = (tab, subTab = null, isFromPopState = false) => {
          isMobileMenuOpen.value = false;
          if (tab === 'add_part') {
            navigateTo('users', 'add_part', isFromPopState);
            return;
          }

          // Check for unsaved changes in Master Testing or Kelola Pengguna before navigating away
          const targetSub = (tab === 'users') ? (normalizeAdminSubTab(subTab) || 'verify_set') : null;
          const isSamePage = (
            (tab === currentTab.value && tab !== 'users' && tab !== 'report') ||
            (tab === 'users' && currentTab.value === 'users' && targetSub === adminSubTab.value)
          );

          if (!isSamePage && !bypassAdminUnsavedCheck) {
            if (isUserFormDirty()) {
              pendingAdminNavigation.value = { tab, subTab, isFromPopState };
              adminUnsavedContext.value = 'users';
              showAdminUnsavedModal.value = true;
              return;
            }
            if (isTestingPointFormDirty()) {
              pendingAdminNavigation.value = { tab, subTab, isFromPopState };
              adminUnsavedContext.value = 'testing_points';
              showAdminUnsavedModal.value = true;
              return;
            }
          }
          currentTab.value = tab;
          localStorage.setItem('activeTab', tab);

          let targetHash = '#' + tab;
          let targetKey = tab;

          if (tab === 'users') {
            const targetSub = normalizeAdminSubTab(subTab) || 'verify_set';
            adminSubTab.value = targetSub;
            localStorage.setItem('activeAdminSubTab', targetSub);
            targetHash = '#users/' + targetSub;
            targetKey = 'users/' + targetSub;
            if (targetSub === 'verify_items' || targetSub === 'verify_set' || targetSub === 'verify_part' || targetSub === 'verify_replacement') {
              loadPendingVerifications();
            } else if (targetSub === 'audit') {
              loadAuditLogs();
            } else if (targetSub === 'downtime') {
              loadDowntimeLogs();
            } else if (targetSub === 'testing_points') {
              loadTestingPoints();
            } else if (targetSub === 'add_part') {
              loadRecentlyAddedParts();
            } else if (targetSub === 'cms_portal') {
              loadCmsPortalData();
            } else {
              loadUsersList();
            }
          } else if (tab === 'report') {
            const targetSub = subTab || reportSubTab.value || 'wo';
            reportSubTab.value = targetSub;
            targetHash = '#report/' + targetSub;
            targetKey = 'report/' + targetSub;
            if (targetSub === 'wo') {
              loadLaporan();
              loadTrays();
              if (!assemblyResults.value || !assemblyResults.value.length) loadAssemblyResults();
            } else if (targetSub === 'assembly') {
              loadAssemblyResults();
              loadLaporan();
            } else if (targetSub === 'assy_test') {
              loadAssyTestReports();
            } else if (targetSub === 'qc_test') {
              loadQCTestReports();
            } else if (targetSub === 'warehouse_receipt') {
              loadWarehouseReceiptReports();
            }
            // Pre-load background pipeline counts if empty
            if (!laporanList.value || !laporanList.value.length) loadLaporan();
            if (targetSub !== 'assembly' && (!assemblyResults.value || !assemblyResults.value.length)) loadAssemblyResults();
            if (targetSub !== 'assy_test' && (!assyTestReports.value || !assyTestReports.value.length)) loadAssyTestReports();
            if (targetSub !== 'qc_test' && (!qcTestReports.value || !qcTestReports.value.length)) loadQCTestReports();
            if (targetSub !== 'warehouse_receipt' && (!warehouseReceiptReports.value || !warehouseReceiptReports.value.length)) loadWarehouseReceiptReports();
          } else {
            targetHash = '#' + tab;
            targetKey = tab;
            if (tab === 'qc') {
              loadQCPendingItems();
            } else if (tab === 'delivery_order') {
              loadCustomers();
              loadDOSettings();
              loadNextDOMeta();
              loadStandaloneLabels();
              syncLabelPackagingStructure();
              loadDOList().then(() => {
                if (doList.value && doList.value.length > 0) {
                  if (!previewDOId.value) {
                    previewDOId.value = doList.value[0].id;
                  }
                  loadDOPreviewData(previewDOId.value);
                }
              });
              loadAvailableFinishedSNs();
            } else if (tab === 'master') {
              loadMasterData();
            } else if (tab === 'add_part') {
              loadRecentlyAddedParts();
            } else if (tab === 'warehouse') {
              loadTrays();
              loadOperatorsList();
              if (typeof fetchInputSuggestions === 'function') {
                fetchInputSuggestions(0, 'p1', 0, false);
              }
            } else if (tab === 'returns') {
              loadReturnedTrays();
              loadOperatorsList();
            } else if (tab === 'reassembly') {
              loadPendingReassembly();
              loadOperatorsList();
            } else if (tab === 'assembly' || tab === 'assy_testing') {
              loadTrays().then(() => {
                if (filteredTraysList.value.length) {
                  const savedCode = localStorage.getItem('selectedTrayCode');
                  const exists = savedCode && filteredTraysList.value.some(t => t.tray_code === savedCode);
                  if (exists) {
                    selectedTrayCode.value = savedCode;
                  } else if (!selectedTrayCode.value || !filteredTraysList.value.some(t => t.tray_code === selectedTrayCode.value)) {
                    const unfinished = tab === 'assy_testing'
                      ? filteredTraysList.value.find(t => (t.good_assembly_count || 0) > 0 && (t.tested_count || 0) < (t.good_assembly_count || 0))
                      : filteredTraysList.value.find(t => (t.finished_count || 0) < (t.total_sets || 1) && t.session_status !== 'RETURNED_TO_WAREHOUSE');
                    selectedTrayCode.value = unfinished ? unfinished.tray_code : filteredTraysList.value[0].tray_code;
                  }
                  loadAssemblyTray();
                } else {
                  selectedTrayCode.value = '';
                  currentTray.value = null;
                  currentTrayParts.value = [];
                  currentTrayAssemblies.value = [];
                }
              });
              if (tab === 'assembly') {
                startAssemblyAutoPoll();
              }
            } else if (tab === 'production_hold') {
              loadAssemblyResults();
              loadProductionHoldItems();
              loadProductionProducts();
            } else if (tab === 'functional_hold') {
              loadProductionHoldItems();
              loadProductionProducts();
            } else if (tab === 'traveler') {
              loadTrays().then(() => {
                const savedCode = localStorage.getItem('selectedTrayCode');
                const validTrays = filteredPrintTrays.value || [];
                if (savedCode && validTrays.some(t => t.tray_code === savedCode)) {
                  selectedTrayCode.value = savedCode;
                } else if (validTrays.length > 0) {
                  selectedTrayCode.value = validTrays[0].tray_code;
                } else if (traysList.value.length && (!selectedTrayCode.value || !traysList.value.some(t => t.tray_code === selectedTrayCode.value))) {
                  selectedTrayCode.value = traysList.value[0].tray_code;
                }
                loadTravelerTray();
              });
            } else if (tab === 'warehouse_receipt') {
              loadWarehouseReceiptReports();
            }
          }

          // Track sequence for back navigation across all roles & tabs
          if (!isFromPopState) {
            const last = _tabNavStack[_tabNavStack.length - 1];
            if (!last || last.key !== targetKey) {
              _tabNavStack.push({ tab, subTab: subTab || null, key: targetKey });
              try {
                history.pushState({ type: 'tab', key: targetKey }, '', targetHash);
              } catch (_) { }
            } else {
              try {
                history.replaceState({ type: 'tab', key: targetKey }, '', targetHash);
              } catch (_) { }
            }
          } else {
            try {
              history.replaceState({ type: 'tab', key: targetKey }, '', targetHash);
            } catch (_) { }
          }
        };

        const toast = ref({ show: false, title: '', message: '', type: 'success' });
        const triggerToast = (title, message, type = 'success') => {
          toast.value = { show: true, title, message, type };
          setTimeout(() => { toast.value.show = false; }, 3500);
        };

        // GLOBAL IN-APP CONFIRMATION & PROMPT CARD MODAL STATE
        const confirmModalState = Vue.reactive({
          isOpen: false,
          title: 'Konfirmasi',
          message: '',
          icon: 'fa-solid fa-circle-question',
          iconBg: 'bg-blue-100 text-blue-600',
          confirmText: 'Ya, Lanjutkan',
          cancelText: 'Batal',
          confirmBtnClass: 'bg-blue-600 hover:bg-blue-700 text-white',
          hasInput: false,
          inputLabel: '',
          inputPlaceholder: '',
          inputValue: '',
          resolve: null
        });

        const showConfirmCard = (opts = {}) => {
          return new Promise((resolve) => {
            confirmModalState.title = opts.title || 'Konfirmasi';
            confirmModalState.message = opts.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            confirmModalState.icon = opts.icon || 'fa-solid fa-circle-question';
            confirmModalState.iconBg = opts.iconBg || (opts.confirmBtnClass && opts.confirmBtnClass.includes('red') ? 'bg-red-100 text-red-600' : (opts.confirmBtnClass && opts.confirmBtnClass.includes('emerald') ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-blue-600'));
            confirmModalState.confirmText = opts.confirmText || 'Ya, Lanjutkan';
            confirmModalState.cancelText = opts.cancelText || 'Batal';
            confirmModalState.confirmBtnClass = opts.confirmBtnClass || 'bg-blue-600 hover:bg-blue-700 text-white';
            confirmModalState.hasInput = false;
            confirmModalState.inputLabel = '';
            confirmModalState.inputPlaceholder = '';
            confirmModalState.inputValue = '';
            confirmModalState.resolve = resolve;
            confirmModalState.isOpen = true;
          });
        };

        const showPromptCard = (opts = {}) => {
          return new Promise((resolve) => {
            confirmModalState.title = opts.title || 'Masukkan Keterangan';
            confirmModalState.message = opts.message || '';
            confirmModalState.icon = opts.icon || 'fa-solid fa-pen-to-square';
            confirmModalState.iconBg = opts.iconBg || 'bg-amber-100 text-amber-600';
            confirmModalState.confirmText = opts.confirmText || 'Kirim / Simpan';
            confirmModalState.cancelText = opts.cancelText || 'Batal';
            confirmModalState.confirmBtnClass = opts.confirmBtnClass || 'bg-blue-600 hover:bg-blue-700 text-white';
            confirmModalState.hasInput = true;
            confirmModalState.inputLabel = opts.inputLabel || 'Keterangan / Alasan:';
            confirmModalState.inputPlaceholder = opts.inputPlaceholder || 'Ketik keterangan di sini...';
            confirmModalState.inputValue = opts.defaultValue || '';
            confirmModalState.resolve = (confirmed) => {
              if (!confirmed) resolve(null);
              else resolve(confirmModalState.inputValue);
            };
            confirmModalState.isOpen = true;
          });
        };

        const handleConfirmCard = (confirmed) => {
          confirmModalState.isOpen = false;
          if (confirmModalState.resolve) {
            const r = confirmModalState.resolve;
            confirmModalState.resolve = null;
            r(confirmed);
          }
        };

        const safeCloseModal = async (closeAction, isActiveProcessing = false) => {
          if (isActiveProcessing) {
            const confirmed = await showConfirmCard({
              title: 'Konfirmasi Keluar',
              message: 'Apakah Anda serius ingin keluar dari proses?',
              icon: 'fa-solid fa-triangle-exclamation',
              iconBg: 'bg-amber-100 text-amber-600',
              confirmText: 'Ya',
              cancelText: 'Tidak',
              confirmBtnClass: 'bg-red-600 hover:bg-red-700 text-white'
            });
            if (!confirmed) return;
          }
          if (typeof closeAction === 'function') {
            closeAction();
          } else if (closeAction && typeof closeAction.value !== 'undefined') {
            closeAction.value = false;
          }
        };

        // API Helper with Auto-Retry for Transient Gateway Glitches
        const fetchAPI = async (path, options = {}, retryCount = 0) => {
          const token = localStorage.getItem('token');
          if (!options.headers) options.headers = {};
          options.headers['Content-Type'] = 'application/json';
          options.headers['Accept'] = 'application/json';
          options.headers['ngrok-skip-browser-warning'] = 'true';
          if (token) options.headers['Authorization'] = 'Bearer ' + token;

          let res;
          try {
            res = await fetch('/api' + path, options);
          } catch (networkErr) {
            if (retryCount < 1 && (!options.method || options.method === 'GET')) {
              await new Promise(r => setTimeout(r, 600));
              return fetchAPI(path, options, retryCount + 1);
            }
            console.error('Fetch network error:', networkErr);
            throw new Error('Gagal terhubung ke server backend. Pastikan server aktif dan koneksi stabil.');
          }

          if (res.status === 401) {
            logout();
            throw new Error('Sesi habis, silakan login kembali.');
          }

          // If Ngrok / reverse proxy returned 502/503 momentarily, retry once automatically
          if ((res.status === 502 || res.status === 503 || res.status === 504) && retryCount < 1 && (!options.method || options.method === 'GET')) {
            await new Promise(r => setTimeout(r, 800));
            return fetchAPI(path, options, retryCount + 1);
          }

          const contentType = res.headers.get('content-type') || '';

          if (!contentType.includes('application/json')) {
            let text = '';
            try { text = await res.text(); } catch (_) { }
            if (!res.ok || res.redirected) {
              if (res.status === 502 || res.status === 503 || res.status === 504) {
                throw new Error(`Server Gateway Offline (${res.status}): Server backend sedang tidak merespons atau sedang restart.`);
              }
              if (res.status === 404) {
                throw new Error(`Endpoint API tidak ditemukan (404): ${path}`);
              }
              throw new Error(`Koneksi server bermasalah (${res.status}): Respon non-JSON diterima.`);
            }
            if (options.method && options.method !== 'GET') {
              throw new Error(`Format respon tidak valid: Server merespons non-JSON untuk ${path}`);
            }
            return { ok: true, data: text };
          }

          try {
            const json = await res.json();
            return json;
          } catch (jsonErr) {
            console.error('JSON parse error:', jsonErr);
            if (!res.ok) {
              throw new Error(`Kesalahan Server (${res.status}): Format data respon tidak dapat dibaca.`);
            }
            throw new Error('Gagal memproses data server: Format JSON dari server tidak valid.');
          }
        };

        const logout = async () => {
          showAdminUnsavedModal.value = false;
          confirmModalState.isOpen = false;
          resetUserForm();
          const token = localStorage.getItem('token');
          if (token) {
            try {
              await fetch('/api/logout', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': 'Bearer ' + token
                }
              });
            } catch (_) { }
          }
          localStorage.removeItem('token');
          localStorage.removeItem('role');
          localStorage.removeItem('username');
          localStorage.removeItem('full_name');
          localStorage.removeItem('nik');
          localStorage.removeItem('department');
          localStorage.removeItem('permissions');
          localStorage.removeItem('activeTab');
          localStorage.removeItem('activeAdminSubTab');
          localStorage.removeItem('activeReportSubTab');
          localStorage.removeItem('selectedTrayCode');
          try {
            window.location.hash = '';
          } catch (_) { }
          window.location.replace('/login');
        };

        // Trays State
        const traysList = ref([]);
        const selectedTrayCode = ref('');
        const currentTray = ref(null);
        const currentTrayParts = ref([]);
        const currentTrayAssemblies = ref([]);

        // Searchable Dropdown State
        const isTrayDropdownOpen = ref(false);
        const traySearchText = ref('');
        const trayDropdownRef = ref(null);

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

        // Operators list for Warehouse assignment (Role: assembly only)
        // Database Environment State (Production vs Testing)
        const activeDatabase = ref({
          active_db: 'db_final_assembly',
          is_testing: false,
          databases: {}
        });

        const fetchDatabaseStatus = async () => {
          try {
            const res = await fetch('/api/database/status');
            const data = await res.json();
            if (data.ok) {
              activeDatabase.value = {
                active_db: data.active_db,
                is_testing: data.is_testing,
                databases: data.databases || {}
              };
            }
          } catch (e) {
            console.error('fetchDatabaseStatus error:', e);
          }
        };

        const swipeDatabase = async (target) => {
          const targetName = target === 'testing' ? 'db_final_assembly_testing' : 'db_final_assembly';
          const label = target === 'testing' ? 'Database Testing (Simulasi)' : 'Database Produksi (Live)';
          const msg = target === 'testing'
            ? 'Apakah Anda yakin ingin beralih ke DATABASE TESTING? Seluruh transaksi dan pengujian tidak akan mempengaruhi database produksi.'
            : 'Apakah Anda yakin ingin beralih kembali ke DATABASE PRODUKSI LIVE?';

          if (!confirm(msg)) return;

          try {
            const res = await fetch('/api/database/switch', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ target_db: targetName })
            });
            const data = await res.json();
            if (data.ok) {
              alert(data.message || `Sistem berhasil dialihkan ke ${label}. Halaman akan dimuat ulang.`);
              window.location.reload();
            } else {
              alert(data.error || 'Gagal mengalihkan database.');
            }
          } catch (e) {
            alert('Error: ' + e.message);
          }
        };

        const operatorUsersList = ref([]);
        // All users list for name lookup across the entire system
        const allUsersLookupList = ref([]);
        // Operator Filter for Gudang / Admin
        const selectedOperatorFilter = ref('');

        const loadOperatorsList = async () => {
          try {
            const [opJson, allJson] = await Promise.all([
              fetchAPI('/operators'),
              fetchAPI('/users/lookup')
            ]);
            if (opJson && opJson.ok && Array.isArray(opJson.data)) {
              operatorUsersList.value = opJson.data;
            }
            if (allJson && allJson.ok && Array.isArray(allJson.data)) {
              allUsersLookupList.value = allJson.data;
            }
            if ((!operatorUsersList.value || operatorUsersList.value.length === 0) && allUsersLookupList.value && allUsersLookupList.value.length > 0) {
              operatorUsersList.value = allUsersLookupList.value.filter(u => {
                const dept = (u.department || '').toLowerCase();
                const role = (u.role || '').toLowerCase();
                const uname = (u.username || '').toLowerCase();
                return (dept === 'assembly' || role === 'assembly') &&
                       !['admin', 'yasin', 'hendrik', 'miranti', 'granada', 'ramdani'].includes(uname) &&
                       dept !== 'warehouse' && dept !== 'administrator';
              });
            }
          } catch (e) {
            console.error('Error loadOperatorsList:', e);
          }
        };

        const getOperatorFullName = (nameOrUsername) => {
          if (!nameOrUsername) return '-';
          const clean = String(nameOrUsername).trim();
          const lower = clean.toLowerCase();
          if (lower === 'admin' || lower === 'super admin' || lower === 'administrator') {
            const adminUser = (allUsersLookupList.value || []).find(u => u.username && u.username.toLowerCase() === 'admin');
            return adminUser?.full_name || 'Administrator NKRI';
          }
          const list = (allUsersLookupList.value && allUsersLookupList.value.length) ? allUsersLookupList.value : (operatorUsersList.value || []);
          const user = list.find(u =>
            (u.username && u.username.toLowerCase() === lower) ||
            (u.full_name && u.full_name.toLowerCase() === lower)
          );
          if (user && user.full_name) return user.full_name;
          const KNOWN_USERS = {
            'admin': 'Administrator NKRI',
            'olif': 'Kholifaturrohmah',
            'yasin': 'Yasin Alibi',
            'granada': 'Granada',
            'hendrik': 'Hendrik',
            'ramdani': 'Ramdani',
            'aziz': 'Aziz',
            'arini': 'Arini',
            'meisi': 'Meisi',
            'sopi': 'Sopi',
            'dila': 'Dila',
            'irsa': 'Irsa',
            'keisha': 'Keisha',
            'muthia': 'Muthia',
            'reva': 'Reva',
            'miranti': 'Miranti',
            'heni': 'Heni',
            'rezka': 'Rezka'
          };
          if (KNOWN_USERS[lower]) return KNOWN_USERS[lower];
          return clean;
        };

        const filteredTraysList = computed(() => {
          let list = (traysList.value || []).filter(t => t.session_status !== 'RETURNED_TO_WAREHOUSE' || (t.finished_count && t.finished_count > 0));
          if (currentTab.value === 'assy_testing') {
            list = list.filter(t => (t.good_assembly_count || 0) > 0);
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
              (isPUTray(t) && getPUDocNumber(t.tray_code).toLowerCase().includes(q)) ||
              (t.pic_warehouse && t.pic_warehouse.toLowerCase().includes(q)) ||
              (t.assigned_operator && t.assigned_operator.toLowerCase().includes(q))
            );
          }
          list.sort((a, b) => (a.tray_code || '').localeCompare(b.tray_code || '', undefined, { numeric: true, sensitivity: 'base' }));
          return list;
        });

        const assemblyOverallCounts = computed(() => {
          let list = traysList.value || [];
          if (selectedOperatorFilter.value && selectedOperatorFilter.value !== '' && selectedOperatorFilter.value !== 'Semua operator assembly' && selectedOperatorFilter.value !== 'Semua Operator Assembly') {
            const op = selectedOperatorFilter.value.toLowerCase().trim();
            list = list.filter(t => {
              const assigned = (t.assigned_operator || '').toLowerCase().trim();
              const assignedFull = getOperatorFullName(t.assigned_operator).toLowerCase().trim();
              return assigned === op || assignedFull === op || assigned.includes(op) || assignedFull.includes(op);
            });
          }
          let good = 0, hold = 0, scrap = 0, total = 0;
          list.forEach(t => {
            good += (t.good_assembly_count || 0);
            hold += (t.hold_count || 0);
            scrap += (t.scrap_count || 0);
            total += (t.finished_count || 0);
          });
          return { good, hold, scrap, total };
        });

        const tf1OverallCounts = computed(() => {
          let list = traysList.value || [];
          if (selectedOperatorFilter.value && selectedOperatorFilter.value !== '' && selectedOperatorFilter.value !== 'Semua operator assembly' && selectedOperatorFilter.value !== 'Semua Operator Assembly') {
            const op = selectedOperatorFilter.value.toLowerCase().trim();
            list = list.filter(t => {
              const assigned = (t.assigned_operator || '').toLowerCase().trim();
              const assignedFull = getOperatorFullName(t.assigned_operator).toLowerCase().trim();
              return assigned === op || assignedFull === op || assigned.includes(op) || assignedFull.includes(op);
            });
          }
          let passed = 0, hold = 0, pending = 0, total = 0;
          list.forEach(t => {
            const tGoodAssy = t.good_assembly_count || 0;
            const tTested = t.tested_count || 0;
            const tPassed = t.good_assy_test_count || tTested;
            passed += tPassed;
            total += tTested;
            pending += Math.max(0, tGoodAssy - tTested);
          });
          return { passed, hold, pending, total };
        });

        const onOperatorFilterChange = async () => {
          await loadTrays();
          if (filteredTraysList.value.length > 0) {
            const exists = filteredTraysList.value.some(t => t.tray_code === selectedTrayCode.value);
            if (!exists) {
              const unfinished = currentTab.value === 'assy_testing'
                ? filteredTraysList.value.find(t => (t.good_assembly_count || 0) > 0 && (t.tested_count || 0) < (t.good_assembly_count || 0))
                : filteredTraysList.value.find(t => (t.finished_count || 0) < (t.total_sets || 1) && t.session_status !== 'RETURNED_TO_WAREHOUSE');
              selectedTrayCode.value = unfinished ? unfinished.tray_code : filteredTraysList.value[0].tray_code;
            }
            await loadAssemblyTray();
          } else {
            selectedTrayCode.value = '';
            currentTray.value = null;
            currentTrayParts.value = [];
            currentTrayAssemblies.value = [];
          }
        };

        // Assembly Form State
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

        // Label Preview State
        const showLabelPreviewModal = ref(false);
        const activeLabelSN = ref({});
        // Batch Label List Modal State
        const showBatchLabelModal = ref(false);
        const batchLabelItems = ref([]);
        const batchLabelDocInfo = ref({ docNumber: '', trayCode: '', totalACC: 0, productType: 'Nanggala P9' });
        const isGeneratingBatchPDF = ref(false);

        // Gudang Supply State
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

        // Traveler Slip MO state
        const currentTravelerMO = ref('');
        const currentTravelerMOSuffix = ref('');

        // Laporan Traceability State & Filters
        const laporanList = ref([]);
        const searchQuery = ref('');
        const laporanFilter = ref({
          startDate: '',
          endDate: '',
          operator: '',
          status: '',
          trayCode: ''
        });

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
          laporanList.value.forEach(l => {
            if (l.tray_code) set.add(l.tray_code);
          });
          return Array.from(set).sort();
        });

        const distinctLaporanOperators = computed(() => {
          const set = new Set();
          laporanList.value.forEach(l => {
            if (l.pic_assembly) set.add(getOperatorFullName(l.pic_assembly));
          });
          operatorUsersList.value.forEach(u => {
            if (u.full_name) set.add(u.full_name);
            else if (u.username) set.add(u.username);
          });
          return Array.from(set).filter(Boolean).sort();
        });

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
            message: `Apakah Anda yakin ingin menghapus pengguna '${u.full_name || u.username}' (@${u.username})?`,
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
            message: `Buka kunci / reset sesi aktif untuk pengguna '@${u.username}' (${u.full_name || u.username})? Akun ini akan dapat login kembali di perangkat mana saja.`,
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
              triggerToast('Sesi Berhasil Di-reset', json.message || `Sesi pengguna @${u.username} berhasil dibuka kuncinya.`, 'success');
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

        // ================= GUDANG ACTUAL VERIFICATION & DECLINE LOGIC =================
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
          set_number: 1
        });

        const verifiedSetsCount = computed(() => {
          return gudangVerifySets.value.filter(s => s.verified_status === 'ACC' || s.is_declined || s.verified_status === 'DECLINED').length;
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
            t = travelerTray.value || currentTray.value || traysList.value.find(x => x.tray_code === selectedTrayCode.value);
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
              // Update local state
              const target = gudangVerifySets.value.find(s => s.set_number === accOverrideForm.value.set_number);
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
              const target = gudangVerifySets.value.find(s => s.set_number === declineData.value.set_number);
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

        const getStockStatusBySN = (partType, sn) => {
          if (!sn || sn === '-' || sn === '') return 'Tersedia';
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
            return stock.status || 'Tersedia';
          }
          return 'Tersedia';
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
                notes: (qcActualVerifyNotes.value || '').trim()
              })
            });
            if (res.ok) {
              const newStatus = isAccepted ? 'ACCEPTED' : 'DECLINED';
              it.warehouse_receipt_status = newStatus;

              // Also update in travelerItems / traySlots and currentTrayAssemblies
              const matchedSlot = (travelerItems.value || []).find(s => s.id === it.id || s.set_number === it.set_number);
              if (matchedSlot) {
                matchedSlot.warehouse_receipt_status = newStatus;
              }
              const matchedAssy = (currentTrayAssemblies.value || []).find(s => s.id === it.id || s.set_number === it.set_number);
              if (matchedAssy) {
                matchedAssy.warehouse_receipt_status = newStatus;
              }

              // Check if all items in qcVerifyItemsList are now verified
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

        // ================= LAPORAN VERIFICATION DETAIL =================
        const showLaporanDetailModal = ref(false);
        const laporanDetailData = ref({
          tray: null,
          assemblies: []
        });

        // ================= OPERATOR FILTER FOR GUDANG / ADMIN =================

        // ================= GUDANG RETURNED TRAYS STATE & ACTIONS (PENGEMBALIAN BARANG) =================
        const returnedTraysList = ref([]);
        const returnsFilter = ref({
          operator: '',
          traySuffix: '',
          status: ''
        });
        const showReturnDetailModal = ref(false);
        const activeReturnTray = ref(null);
        const activeReturnSets = ref([]);
        const isVerifyingReturn = ref(false);
        const verifyingSetIndex = ref(null);

        const pendingReturnsCount = computed(() => {
          return (returnedTraysList.value || []).filter(t => !t.return_verified).length;
        });

        const isAllActiveReturnSetsVerified = computed(() => {
          if (!activeReturnSets.value || activeReturnSets.value.length === 0) return false;
          return activeReturnSets.value.every(s => s.is_verified || s.status === 'Tersedia');
        });

        const filteredReturnsList = computed(() => {
          let list = returnedTraysList.value || [];

          // 1. Filter Dropdown by Operator
          if (returnsFilter.value.operator && returnsFilter.value.operator !== 'Semua Operator Assembly') {
            const op = returnsFilter.value.operator.toLowerCase().trim();
            list = list.filter(t => {
              const assigned = (t.assigned_operator || '').toLowerCase().trim();
              const assignedFull = getOperatorFullName(t.assigned_operator).toLowerCase().trim();
              return assigned === op || assignedFull === op || assigned.includes(op) || assignedFull.includes(op);
            });
          }

          // 2. Input text search template nomor WH/ASSY/ (suffix match)
          if (returnsFilter.value.traySuffix && returnsFilter.value.traySuffix.trim()) {
            const rawSuffix = returnsFilter.value.traySuffix.toLowerCase().trim();
            const cleanSuffix = rawSuffix.replace(/^wh\/assy\//i, '').trim();
            list = list.filter(t => {
              const code = (t.tray_code || '').toLowerCase().trim();
              return code.includes(cleanSuffix) || code.endsWith(cleanSuffix) || code.includes(rawSuffix);
            });
          }

          // 3. Filter Status Verifikasi
          if (returnsFilter.value.status) {
            if (returnsFilter.value.status === 'pending') {
              list = list.filter(t => !t.return_verified);
            } else if (returnsFilter.value.status === 'verified') {
              list = list.filter(t => t.return_verified);
            }
          }

          return list;
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
                    s.is_verified = true;
                    s.status = 'Tersedia';
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

        // ================= ASSEMBLY SESSION ACTIONS (RETURN, HANDOVER) =================
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

              // Bersihkan riwayat in-progress lokal untuk tray ini
              if (codeToClear) {
                localStorage.removeItem(`inProgressMap_${codeToClear}`);
                delete inProgressMap.value;
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

        // ================= ASSEMBLY JEDA / DOWNTIME STATE & ACTIONS =================
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
            let finalReason = pauseForm.value.reason || 'Jeda Operasional';
            if (pauseForm.value.estimatedDuration) {
              finalReason += ` [Est: ${pauseForm.value.estimatedDuration}]`;
            }
            if (pauseForm.value.customReason && pauseForm.value.customReason.trim()) {
              finalReason += ` - ${pauseForm.value.customReason.trim()}`;
            }

            const json = await fetchAPI('/assembly/pause', {
              method: 'POST',
              body: JSON.stringify({
                tray_id: currentTray.value.id,
                tray_code: currentTray.value.tray_code,
                set_number: (firstUnfinishedIndex.value || 0) + 1,
                reason: finalReason,
                operator: userFullName.value || userName.value
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

              // Waktu aktual langsung berjalan kembali (tidak stuck)
              liveClock.value = new Date();

              // Sesuaikan waktu mulai rakitan aktif agar durasi jeda tidak dihitung sebagai waktu kerja rakitan
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

        // Live timer for pause duration
        setInterval(() => {
          if (isWorkstationPaused.value && pausedStartTime.value) {
            const elapsed = Math.floor((new Date().getTime() - pausedStartTime.value.getTime()) / 1000);
            pausedDurationSeconds.value = Math.max(0, elapsed);
          }
        }, 1000);

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

        const isTargetSetRework = (item) => {
          if (!item) return false;
          return item.qc_status === 'REWORK_QC' || item.qc_status === 'REWORK' ||
            item.assembly_status === 'Rework' || item.status === 'Rework' ||
            item.verified_status === 'REWORK_GUDANG' || item.verified_status === 'REWORK_QC_GUDANG' ||
            item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC';
        };

        const openReregisterSetConfirmModal = (item) => {
          targetReregisterSet.value = item;
          reregisterNewReworkReason.value = '';
          showReregisterSetModal.value = true;
        };

        const confirmReregisterSet = async () => {
          if (!targetReregisterSet.value || isSubmittingReregisterSet.value) return;
          const item = targetReregisterSet.value;
          const isRework = isTargetSetRework(item);

          if (isRework && (!reregisterNewReworkReason.value || !reregisterNewReworkReason.value.trim())) {
            triggerToast('Wajib Diisi', 'Alasan rework baru wajib diisi sebelum mengajukan daftar ulang.', 'warning');
            return;
          }

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
                rework_reason: isRework ? (reregisterNewReworkReason.value.trim() || finalReason) : '',
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
          // Sets genuinely pending admin verification are NOT verified
          if (set.verified_status === 'PENDING_ADMIN' || set.verified_status === 'PENDING_ADMIN_ASSY_REREG' || set.verified_status === 'PENDING_ADMIN_QC') return false;
          if (set.verified_status === 'ACC' || set.verified_status === 'APPROVED' || set.verified_status === 'APPROVED_QC' || set.verified_status === 'APPROVED_REREG' || set.verified_status === 'WAITING_REASSEMBLY' || set.verified_status === 'COMPLETED_PU') return true;
          if (set.status === 'Dirakit Ulang' || set.status === 'Selesai') return true;
          if (set.is_declined && (set.verified_status === 'ACC' || set.verified_status === 'APPROVED')) return true;
          const p15Ok = !set.part15_sn || set.part15_sn === '-' || set.p15_status === 'Tersedia' || getStockStatusBySN('15', set.part15_sn) === 'Tersedia';
          const p1Ok = !set.part1_sn || set.part1_sn === '-' || set.p1_status === 'Tersedia' || getStockStatusBySN('1', set.part1_sn) === 'Tersedia';
          const p12Ok = !set.part12_sn || set.part12_sn === '-' || set.p12_status === 'Tersedia' || getStockStatusBySN('12', set.part12_sn) === 'Tersedia';
          if (p15Ok && p1Ok && p12Ok && set.is_declined) return true;
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

        const allAdminSets = computed(() => {
          const map = new Map();
          // 1. Assemblies from assembly / TF1 - include declined (tagged), exclude replacements
          (adminPendingSets.value || []).forEach(s => {
            const id = s.assembly_id || s.id;
            if (id && !map.has(id)) {
              const isDec = Boolean(s.is_declined) ||
                (s.verified_status || '').toUpperCase() === 'DECLINED' ||
                (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' ||
                Boolean(s.declined_reason);

              const isRep = Boolean(s.replacement_id) || Boolean(s.replacement_details) || (s.verified_status || '').toUpperCase() === 'PENDING_REPLACEMENT';
              if (isRep) return; // Dedicated feature: verif pergantian part!

              let pStage = isDec ? 'declined' : (s.process_stage || (
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
                  dStatus = (aStat === 'Rework' || s.qc_status === 'REWORK_QC') ? 'Rework QC' : 'Hold QC';
                } else if (pStage === 'test_functional_1') {
                  dStatus = (aStat === 'Rework' || s.status === 'Rework') ? 'Rework Test' : 'Hold Test';
                } else {
                  if (s.status === 'Scrap' || aStat === 'Scrap') dStatus = 'Scrap Perakitan';
                  else if (s.status === 'Rework' || aStat === 'Rework') dStatus = 'Rework Perakitan';
                  else dStatus = 'Hold Perakitan';
                }
              }
              map.set(id, { ...s, is_declined: isDec, is_replacement: false, process_stage: pStage, display_status: dStatus });
            }
          });

          // 2. Assemblies from QC - include declined (tagged), exclude replacements
          (adminPendingQCSets.value || []).forEach(s => {
            const id = s.assembly_id || s.id;
            if (id && !map.has(id)) {
              const isDec = Boolean(s.is_declined) ||
                (s.verified_status || '').toUpperCase() === 'DECLINED' ||
                (s.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' ||
                Boolean(s.declined_reason);

              // Guard: Item Hold QC yang belum melewati aksi daftar ulang atau tindakan dari gudang/admin jangan masuk verifikasi
              const vStatus = (s.verified_status || '').toUpperCase();
              if (!isDec && vStatus === 'HOLD_QC_GUDANG' && !s.is_reregistered && !s.request_id) {
                return;
              }

              const aStat = s.assembly_status || s.status || 'Hold';
              const dStatus = isDec ? 'Declined' : (s.display_status || ((aStat === 'Rework' || s.qc_status === 'REWORK_QC') ? 'Rework QC' : 'Hold QC'));
              const pStage = isDec ? 'declined' : 'qc';
              map.set(id, { ...s, is_declined: isDec, process_stage: pStage, display_status: dStatus });
            }
          });

          return Array.from(map.values());
        });

        const adminSetProcessCounts = computed(() => {
          const sets = allAdminSets.value;
          const nonDeclined = sets.filter(s => !s.is_declined);
          return {
            ALL: nonDeclined.length,
            perakitan: nonDeclined.filter(s => s.process_stage === 'perakitan').length,
            test_functional_1: nonDeclined.filter(s => s.process_stage === 'test_functional_1').length,
            qc: nonDeclined.filter(s => s.process_stage === 'qc').length,
            declined: sets.filter(s => s.is_declined).length
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
        const adminRepStatusFilter = ref('');
        const adminRepStartDate = ref('');
        const adminRepEndDate = ref('');

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
          adminRepStartDate.value = '';
          adminRepEndDate.value = '';
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

          // Filter by Old Part Target Status
          if (adminRepStatusFilter.value) {
            const sf = adminRepStatusFilter.value.toLowerCase();
            list = list.filter(r => (r.replacement_old_status || '').toLowerCase().includes(sf));
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
            list = list.filter(s => {
              const ds = (s.display_status || '').toLowerCase();
              return ds.includes(sf);
            });
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
        // OPSI: LANGSUNG LANJUT (DIRECT RESUME) & 1-CLICK PEMULIHAN DECLINED SET
        // -------------------------------------------------------------------------
        const isExecutingDirectResume = ref(false);
        const executeDirectResume = async (item) => {
          if (!item) return;
          const assyId = item.assembly_id || item.id;
          if (!assyId) {
            triggerToast('Perhatian', 'ID rakitan tidak valid.', 'warning');
            return;
          }

          const confirmed = confirm(`Konfirmasi Langsung Lanjut:\nSet #${item.set_number || ''} (${item.part15_sn || '-'})\n\nApakah Anda yakin barang fisik in-aktual telah diperiksa dan TIDAK ada masalah?\nStatus akan langsung diloloskan ke tahap berikutnya.`);
          if (!confirmed) return;

          isExecutingDirectResume.value = true;
          try {
            const res = await fetchAPI('/assembly/direct-resume', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: assyId,
                notes: 'Pemeriksaan fisik in-aktual OK: Langsung Lanjut Good tanpa ganti part'
              })
            });

            if (res.ok) {
              triggerToast('Berhasil Dilanjutkan', res.message || 'Set berhasil dilanjutkan ke tahap berikutnya.', 'success');
              if (typeof showAssemblyDetailModal !== 'undefined') showAssemblyDetailModal.value = false;
              if (typeof showHoldDetailModal !== 'undefined') showHoldDetailModal.value = false;
              if (typeof showAdminSetDetailModal !== 'undefined') showAdminSetDetailModal.value = false;
              if (typeof loadAssemblyResults === 'function') await loadAssemblyResults();
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
              if (typeof loadQCPendingItems === 'function') await loadQCPendingItems();
              if (typeof loadTesterAssemblyPendingItems === 'function') await loadTesterAssemblyPendingItems();
            } else {
              triggerToast('Gagal', res.error || 'Gagal melanjutkan set', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
          } finally {
            isExecutingDirectResume.value = false;
          }
        };

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
          const p15Status = set.p15_status || getStockStatusBySN('15', set.part15_sn);
          const p1Status = set.p1_status || getStockStatusBySN('1', set.part1_sn);
          const p12Status = set.p12_status || getStockStatusBySN('12', set.part12_sn);
          const isDec = set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED';
          const isHoldTest = !isDec && (set.assembly_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.qc_status === 'HOLD' || (set.status === 'Hold'));

          activeAdminSet.value = {
            assembly_id: set.assembly_id || set.id,
            tray_id: set.tray_id,
            tray_code: set.tray_code,
            set_number: set.set_number,
            is_declined: isDec,
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
            const json = await fetchAPI('/admin/approve-part-direct', {
              method: 'POST',
              body: JSON.stringify({
                part_type: partItem.type,
                sn: partItem.sn,
                target_status: 'Tersedia',
                notes: `Verifikasi fisik Admin dari Set #${activeAdminSet.value ? activeAdminSet.value.set_number : ''}`
              })
            });

            if (json.ok) {
              partItem.status = 'Tersedia';
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
            for (const p of unverified) {
              p.is_submitting = true;
              const json = await fetchAPI('/admin/approve-part-direct', {
                method: 'POST',
                body: JSON.stringify({
                  part_type: p.type,
                  sn: p.sn,
                  target_status: 'Tersedia',
                  notes: `Verifikasi fisik Admin dari Set #${activeAdminSet.value.set_number}`
                })
              });
              p.is_submitting = false;
              if (json.ok) {
                p.status = 'Tersedia';
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
              body: JSON.stringify({ assembly_id: set.assembly_id, tray_id: set.tray_id, set_number: set.set_number })
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
          if (!set || !set.assembly_id) return;
          try {
            const res = await fetchAPI('/admin/approve-assy-rereg', {
              method: 'POST',
              body: JSON.stringify({
                assembly_id: set.assembly_id,
                action: action,
                approved_operator: adminReregApprovedOperator.value || set.rereg_pending_operator || set.pic_assembly,
                notes: adminReregNotes.value
              })
            });
            if (res.ok) {
              const newTrayCode = res.new_tray_code || getNextPUTrayCode(set.tray_code);
              const chosenOp = adminReregApprovedOperator.value || set.rereg_pending_operator || set.pic_assembly;
              const msg = action === 'APPROVE'
                ? `Perakitan ulang disetujui! Dokumen WO: ${getPUWODocNumber(newTrayCode)}. Berhasil ditugaskan ke operator ${chosenOp} dan masuk ke antrean perakitan!`
                : 'Pengajuan perakitan ulang ditolak.';
              triggerToast(action === 'APPROVE' ? 'Disetujui' : 'Ditolak', msg, action === 'APPROVE' ? 'success' : 'warning');
              adminReregApprovedOperator.value = '';
              adminReregNotes.value = '';
              showAdminSetDetailModal.value = false;
              showAdminVerifySetModal.value = false;
              await loadPendingVerifications();
              if (typeof loadAssemblyResults === 'function') await loadAssemblyResults();
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              await loadTrays();
              if (action === 'APPROVE' && newTrayCode) {
                await openTravelerPUDoc(newTrayCode);
              }
            } else {
              triggerToast('Gagal', res.error || 'Terjadi kesalahan', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message, 'error');
          }
        };

        const adminApproveHoldItem = async (set, decision) => {
          const isQC = set.stage === 'QC' || set.qc_status === 'HOLD_QC' || set.qc_status === 'REWORK_QC' || decision.endsWith('_QC');
          const actualDecision = isQC ? (decision.endsWith('_QC') ? decision : `${decision}_QC`) : decision;
          const isGood = actualDecision === 'APPROVE_GOOD' || actualDecision === 'APPROVE_GOOD_QC';
          const label = isGood
            ? (isQC ? 'menyetujui sebagai GOOD (lanjut ke Verifikasi Dokumen & Gudang)' : 'menyetujui sebagai GOOD (lanjut ke QC)')
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

        // Computed Properties for Active Tray & Slots
        const currentTraySetsCount = computed(() => {
          const assyCount = (currentTrayAssemblies.value || []).length;
          const maxAssySet = (currentTrayAssemblies.value || []).reduce((max, a) => Math.max(max, Number(a.set_number) || 0), 0);
          if (currentTray.value && currentTray.value.session_status === 'COMPLETED_SHIFT') {
            return Math.max(assyCount, maxAssySet, 0);
          }
          const p15Count = (currentTrayParts.value || []).filter(p => p.part_type === '15').length;
          const p1Count = (currentTrayParts.value || []).filter(p => p.part_type === '1').length;
          const p12Count = (currentTrayParts.value || []).filter(p => p.part_type === '12').length;
          const partsCount = Math.max(p15Count, p1Count, p12Count, 0);
          return Math.max(assyCount, maxAssySet, partsCount, 0);
        });

        const finishedSlotsCount = computed(() => {
          return currentTrayAssemblies.value.length;
        });

        const isTrayFullyFinished = computed(() => {
          return currentTraySetsCount.value > 0 && finishedSlotsCount.value >= currentTraySetsCount.value;
        });

        const hasHoldItem = computed(() => {
          return currentTrayAssemblies.value.some(a => a.status === 'Hold');
        });

        const isTrayLocked = computed(() => {
          if (currentTray.value && (currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE' || currentTray.value.session_status === 'COMPLETED_SHIFT')) return true;
          return isTrayFullyFinished.value && !hasHoldItem.value;
        });

        const firstUnfinishedIndex = computed(() => {
          if (currentTray.value && currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE') {
            return currentTraySetsCount.value;
          }
          const finishedSetNums = new Set(currentTrayAssemblies.value.map(a => a.set_number));
          for (let i = 1; i <= currentTraySetsCount.value; i++) {
            if (!finishedSetNums.has(i)) return i - 1;
          }
          return currentTraySetsCount.value;
        });

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

          let uncompletedIdx = 0;
          for (let i = 0; i < count; i++) {
            const setNum = i + 1;
            const assy = assyMap[setNum];
            if (assy) {
              slots.push(assy);
            } else {
              const prog = inProgressMap.value[setNum];
              const p15Val = prog ? prog.part15 : (availP15[uncompletedIdx] ? availP15[uncompletedIdx].sn : '');
              const p1Val = prog ? prog.part1 : (availP1[uncompletedIdx] ? availP1[uncompletedIdx].sn : '');
              const p12Val = prog ? prog.part12 : (availP12[uncompletedIdx] ? availP12[uncompletedIdx].sn : '');

              const isReturned = (currentTray.value && currentTray.value.session_status === 'RETURNED_TO_WAREHOUSE') ||
                (availP15[uncompletedIdx] && availP15[uncompletedIdx].status === 'RETURNED');

              slots.push({
                set_number: setNum,
                part15_sn: p15Val,
                part1_sn: p1Val,
                part12_sn: p12Val,
                started_at: prog ? prog.started_at : null,
                finished_at: null,
                status: isReturned ? 'RETURNED' : null
              });
              uncompletedIdx++;
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

        // Available Parts per Tray calculation for Assembly modal
        const availablePartsByType = (type) => {
          if (!currentTrayParts.value || !currentTrayParts.value.length) return [];

          const assembledSNs = new Set();
          const currentSlot = activeModalIndex.value !== null ? traySlots.value[activeModalIndex.value] : null;

          currentTrayAssemblies.value.forEach(a => {
            // If editing current slot, keep its own parts available
            if (currentSlot && currentSlot.id && a.id === currentSlot.id) return;
            if (a.part15_sn) assembledSNs.add(a.part15_sn);
            if (a.part12_sn) assembledSNs.add(a.part12_sn);
            if (a.part1_sn) assembledSNs.add(a.part1_sn);
          });

          // Also exclude parts currently in other in-progress slots
          Object.entries(inProgressMap.value).forEach(([setNumStr, prog]) => {
            const sNum = parseInt(setNumStr, 10);
            if (activeModalIndex.value !== null && sNum === (activeModalIndex.value + 1)) return;
            if (prog.part15) assembledSNs.add(prog.part15);
            if (prog.part1) assembledSNs.add(prog.part1);
            if (prog.part12) assembledSNs.add(prog.part12);
          });

          return currentTrayParts.value.filter(p => p.part_type === type && !assembledSNs.has(p.sn));
        };

        // Traveler Items Computed
        const activePrintDocType = ref('supply'); // 'supply' | 'assy' | 'assy_rework' | 'assy_qc' | 'wh_qc'
        const showPrintDocTypeDropdown = ref(false);
        const printStatusFilter = ref('ALL');

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
          const cleanNum = numPart.replace(/-PU\d*/i, '').replace(/-S\d*/i, '').replace(/\D/g, '').slice(-6).padStart(6, '0') || '000001';

          const t = String(type || '').toLowerCase();

          if (t === 'mo' || t === 'wh_mo') {
            return isPU ? `WH/MO/PU/${puNum}` : `WH/MO/${cleanNum}`;
          } else if (t === 'supply' || t === 'wo' || t === 'wh_wo') {
            return isPU ? `WH/WO/PU/${puNum}` : `WH/WO/${cleanNum}`;
          } else if (t === 'qc' || t === 'wh_qc') {
            return isPU ? `WH/QC/PU/${puNum}` : `WH/QC/${cleanNum}`;
          } else if (t === 'assy_qc' || t === 'tf1') {
            return isPU ? `ASSY/QC/PU/${puNum}` : `ASSY/QC/${cleanNum}`;
          } else if (t === 'warehouse_receipt' || t === 'receipt' || t === 'gr' || t === 'wh_gr') {
            return isPU ? `WH/GR/PU/${puNum}` : `WH/GR/${cleanNum}`;
          } else if (t === 'assy_rework' || t === 'rework') {
            return `WH/ASSY/PU/${puNum || cleanNum}`;
          } else if (t === 'origin_assy') {
            return `WH/ASSY/${cleanNum}`;
          } else if (t === 'assy') {
            return isPU ? `WH/ASSY/PU/${puNum}` : `WH/ASSY/${cleanNum}`;
          } else if (isPU) {
            return `WH/ASSY/PU/${puNum}`;
          }
          return `WH/ASSY/${cleanNum}`;
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

        const selectPrintDocType = (type) => {
          activePrintDocType.value = type;
          activeAssyQCDocSlot.value = null;
          showPrintDocTypeDropdown.value = false;

          if (type === 'wh_gr') {
            printStatusFilter.value = 'ALL';
            loadWarehouseReceiptReports();
          }

          // Jika tab Perakitan Ulang dipilih, reset filter agar seluruh dokumen PU tampil
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

        // Print Document Filter State (Default End Date: Tanggal Hari Ini)
        const printFilter = ref({
          startDate: '',
          endDate: getTodayDateStr(),
          operator: '',
          search: ''
        });

        const distinctPrintOperators = computed(() => {
          const set = new Set();
          traysList.value.forEach(t => {
            if (t.assigned_operator && t.assigned_operator !== 'ALL' && t.assigned_operator !== 'Semua Operator') {
              set.add(t.assigned_operator);
            }
          });
          return Array.from(set).sort();
        });

        const filteredPrintTrays = computed(() => {
          let list = traysList.value.filter(t => {
            // Filter Khusus Pemisahan Dokumen Perakitan vs Perakitan Ulang:
            // - Tab Perakitan ('assy'): Hanya muncul tray biasa atau split (-S...), TIDAK boleh muncul PU khusus (-PU)
            // - Tab Perakitan Ulang ('assy_rework'): HANYA muncul tray yang berstatus PU (-PU)
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

        // Watch filteredPrintTrays agar jika tab berubah atau filter berubah,
        // selectedTrayCode otomatis sinkron ke tray terbaru HANYA jika pilihan sebelumnya sudah tidak ada
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

        const getPrintDocSetCount = (t) => {
          if (!t) return 0;
          const docType = activePrintDocType.value;

          // 0. Dokumen Penerimaan Gudang (WH/GR):
          // Yang masuk WH/GR adalah seluruh unit yang sudah LOLOS GOOD di TF 2 (QC)
          if (docType === 'wh_gr') {
            const matchingItems = (warehouseReceiptReports.value || []).filter(it => {
              if (!it) return false;
              return it.tray_code === t.tray_code || (t.id && it.tray_id === t.id);
            });

            if (printStatusFilter.value === 'GOOD') {
              return matchingItems.filter(it => it.warehouse_receipt_status === 'ACCEPTED' || it.warehouse_receipt_status === 'ACC').length;
            } else if (printStatusFilter.value === 'HOLD' || printStatusFilter.value === 'DECLINED') {
              return matchingItems.filter(it => it.warehouse_receipt_status === 'DECLINED').length;
            } else if (printStatusFilter.value === 'PENDING') {
              return matchingItems.filter(it => it.warehouse_receipt_status !== 'ACCEPTED' && it.warehouse_receipt_status !== 'ACC' && it.warehouse_receipt_status !== 'DECLINED').length;
            } else {
              // 'ALL' (Semua status): Seluruh unit yang lolos TF 2 (baik ACCEPTED, DECLINED, maupun Menunggu Penerimaan)
              return matchingItems.length;
            }
          }

          // 1. Dokumen Mutu Quality Control & Test Functional 2 (WH/QC):
          if (docType === 'wh_qc') {
            if (t.good_assy_test_count !== undefined && t.good_assy_test_count !== null) return Number(t.good_assy_test_count);
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
          return t.total_sets || 0;
        };

        const travelerTray = computed(() => {
          if (!selectedTrayCode.value) return null;
          const found = traysList.value.find(t => t.tray_code === selectedTrayCode.value);
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
          if (activeAssyQCDocSlot.value) return [activeAssyQCDocSlot.value];
          return (travelerItems.value || []).filter(i => {
            if (!i) return false;
            // ATURAN 1 MUTLAK: HANYA unit yang lolos "GOOD" dari Perakitan yang boleh masuk Dokumen Test Assy.
            // Unit yang berstatus Scrap/Reject, atau Hold/Rework dari tahap Perakitan WAJIB DIABAIKAN.
            if (i.status === 'Scrap' || i.status === 'Reject') return false;
            if (i.defective_parts && !i.assy_doc_number && !i.assy_test_checklist) return false;

            // Unit WAJIB benar-benar telah melalui proses pengujian fungsional assembly (ada nomor dokumen uji atau checklist pengujian)
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
            // ATURAN 2: HANYA unit dengan status "GOOD" dari tahap Test Assy yang boleh dimasukkan ke dalam Dokumen Test QC.
            // Unit yang berstatus Hold atau Rework di tahap Test Assy (atau tahap sebelumnya) WAJIB DIABAIKAN.
            const isGoodFromAssy = i.status === 'Good' && i.qc_status !== 'HOLD_ASSY' && Boolean(i.assy_doc_number);
            if (!isGoodFromAssy) return false;

            // Unit harus sudah memiliki proses / catatan pengujian QC
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

          // 1. Gather all items from warehouseReceiptReports matching strictly this exact tray
          const rcptItems = (warehouseReceiptReports.value || []).filter(it => {
            if (!it) return false;
            return it.tray_code === selected || (travelerTray.value && (it.tray_id === travelerTray.value.id || it.tray_id === travelerTray.value.tray_id));
          });

          // 2. Gather completed assemblies from travelerItems HANYA jika sudah selesai QC dan GOOD di TF 2
          const slotItems = (travelerItems.value || []).filter(i => {
            if (!i || (!i.id && !i.assembly_id)) return false;
            const qc = String(i.qc_status || '').toUpperCase();
            return qc === 'GOOD' || qc === 'PASS' || qc === 'PASSED' || Boolean(i.warehouse_receipt_status);
          });

          // Prefer rcptItems if available (since they contain full warehouse receipt timestamps & inspector names)
          let items = rcptItems.length > 0 ? rcptItems : slotItems;

          return items.filter(i => {
            if (!i || (!i.id && !i.assembly_id)) return false;
            // Yang masuk WH/GR HANYA yang sudah lolos QC / GOOD di TF 2 (tidak boleh yang belum selesai QC / Hold / RU)
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
            // 'ALL' (Semua status): Menampilkan semua barang yang sudah lolos TF 2 (ACCEPTED, DECLINED, maupun Menunggu)
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

        const getInspectorQC = (items) => {
          if (!items) return '';
          if (Array.isArray(items)) {
            const found = items.find(i => i && (i.pic_qc || i.qc_tester_name || i.qc_tested_by));
            return found ? getInspectorQC(found) : '';
          }
          const val = items.pic_qc || items.qc_tester_name || getOperatorFullName(items.qc_tested_by) || items.qc_tested_by || '';
          return val === '-' ? '' : val;
        };

        const filteredLaporan = computed(() => {
          const list = laporanList.value.filter(l => {
            // 1. Date Filter (start_date & end_date against finished_at or created_at)
            const dateKey = l.finished_at ? l.finished_at.substring(0, 10) : (l.tray_created_at ? l.tray_created_at.substring(0, 10) : '');
            if (laporanFilter.value.startDate && dateKey && dateKey < laporanFilter.value.startDate) {
              return false;
            }
            if (laporanFilter.value.endDate && dateKey && dateKey > laporanFilter.value.endDate) {
              return false;
            }

            // 2. Operator Filter (Strict Assembly Operator Match)
            if (laporanFilter.value.operator) {
              const targetOp = laporanFilter.value.operator.toLowerCase().trim();
              const pAssy = getOperatorFullName(l.pic_assembly).toLowerCase().trim();
              const rawAssy = (l.pic_assembly || '').toLowerCase().trim();
              const match = pAssy === targetOp || pAssy.includes(targetOp) || rawAssy === targetOp || rawAssy.includes(targetOp);
              if (!match) return false;
            }

            // 3. Status Filter
            if (laporanFilter.value.status) {
              const filterStat = laporanFilter.value.status.toLowerCase().trim();
              const itemStat = (l.status || 'Good').toLowerCase().trim();
              if (itemStat !== filterStat) {
                return false;
              }
            }

            // 4. No Doc Filter
            if (laporanFilter.value.trayCode) {
              if ((l.tray_code || '').toLowerCase() !== laporanFilter.value.trayCode.toLowerCase()) {
                return false;
              }
            }

            // 5. Text Search Query
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

          // Always sort so latest assembly records (finished_at / started_at / id) appear at the very top
          return list.sort((a, b) => {
            const timeA = new Date(a.finished_at || a.started_at || a.tray_created_at || 0).getTime();
            const timeB = new Date(b.finished_at || b.started_at || b.tray_created_at || 0).getTime();
            if (timeB !== timeA) return timeB - timeA;
            return (b.id || 0) - (a.id || 0);
          });
        });

        // Live Polling Timer for Assembly Monitoring
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
          }, 3000);
        };

        // Tab Switching — Unified through navigateTo so history is always tracked
        const switchTab = (tab, subTab = null, isFromPopState = false) => {
          navigateTo(tab, subTab, isFromPopState);
        };

        // Load Data Helpers
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

        watch(selectedTrayCode, async (newCode) => {
          if (newCode && (currentTab.value === 'assembly' || currentTab.value === 'assy_testing')) {
            if (!currentTray.value || currentTray.value.tray_code !== newCode) {
              await loadAssemblyTray();
            }
          }
        });

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

        const resetAddPartForm = () => {
          addPartForm.value = {
            part_type: addPartForm.value.part_type || '1',
            sn: '',
            cs: '',
            reason: ''
          };
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

          addPartLoading.value = true;
          try {
            const res = await fetchAPI('/master/add-part-bulk', {
              method: 'POST',
              body: JSON.stringify(payload)
            });

            if (res && res.ok) {
              addPartBulkResult.value = res.data || null;
              triggerToast('Bulk Selesai', res.message || 'Batch part number berhasil diproses!', 'success');
              if (res.data && res.data.added_count > 0) {
                await loadRecentlyAddedParts();
                if (typeof loadMasterData === 'function') {
                  loadMasterData();
                }
              }
            } else {
              triggerToast('Gagal Bulk', (res && res.error) || 'Gagal menambahkan batch part number', 'error');
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

        // Live Clock & Time Helpers (Strictly WIB - UTC+7 Asia/Jakarta)
        const getNowWIBDate = () => {
          const now = new Date();
          const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
          return new Date(utc + (7 * 3600000));
        };

        const getNowTimeStr = () => {
          const w = getNowWIBDate();
          const pad = (n) => String(n).padStart(2, '0');
          return `${pad(w.getHours())}:${pad(w.getMinutes())}`;
        };

        const formatTimeInput = (dtStr) => {
          if (!dtStr) return getNowTimeStr();
          try {
            const d = parseWIBDate(dtStr);
            if (!d || isNaN(d.getTime())) return getNowTimeStr();
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
          } catch (_) {
            return getNowTimeStr();
          }
        };

        const liveClock = ref(new Date());
        let liveClockTimer = null;

        const liveClockString = computed(() => {
          const d = liveClock.value;
          return d.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        });

        const getNowLocalDateTimeStr = () => {
          const w = getNowWIBDate();
          const pad = (n) => String(n).padStart(2, '0');
          return `${w.getFullYear()}-${pad(w.getMonth() + 1)}-${pad(w.getDate())} ${pad(w.getHours())}:${pad(w.getMinutes())}:${pad(w.getSeconds())}`;
        };

        const setStartTimeNow = () => {
          assyForm.value.startTime = getNowTimeStr();
        };

        const setEndTimeNow = () => {
          assyForm.value.endTime = getNowTimeStr();
        };

        // Modal & Action State Variables
        const showDetailModal = ref(false);
        const activeDetailSlot = ref(null);
        const showResetDbModal = ref(false);
        const isResettingDb = ref(false);
        const showQuickStatusModal = ref(false);
        const quickStatusData = ref({
          index: null,
          set_number: null,
          status: '',
          reason: ''
        });

        // Assembly Form Actions
        const startAssemblyNow = async () => {
          if (!assyForm.value.part15?.trim() || !assyForm.value.part1?.trim() || !assyForm.value.part12?.trim()) {
            triggerToast('Validasi Part', 'Harap pilih Part 15, Part 1, dan Part 12 terlebih dahulu sebelum menekan Mulai!', 'warn');
            return;
          }
          const now = getNowLocalDateTimeStr();
          assyForm.value.started_at = now;
          // Status default TIDAK memilih apapun (kosong) sampai operator mengklik status
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

            // Sync directly to server so Gudang and all devices see this card is in progress in real-time
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
            // In Progress (Sedang Dikerjakan)
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
            // New Mode: Autoselect first available from this tray (Default status: BELUM DIPILIH / KOSONG)
            const avail15 = availablePartsByType('15');
            const avail1 = availablePartsByType('1');
            const avail12 = availablePartsByType('12');

            assyForm.value = {
              part15: avail15.length ? avail15[0].sn : '',
              part1: avail1.length ? avail1[0].sn : '',
              part12: avail12.length ? avail12[0].sn : '',
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

        const captureStartTime = () => {
          assyForm.value.startTime = getNowTimeStr();
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
              closeModal();

              // Update local assembly collection realtime
              if (json.data) {
                const updatedAssy = json.data;
                const existingIdx = (currentTrayAssemblies.value || []).findIndex(a => a.set_number === setNum);
                if (existingIdx >= 0) {
                  currentTrayAssemblies.value[existingIdx] = updatedAssy;
                } else {
                  currentTrayAssemblies.value.push(updatedAssy);
                }
              }

              // Update traysList in realtime immediately
              const currentTrayItem = (traysList.value || []).find(t => t.tray_code === activeAssemblyCode);
              if (currentTrayItem) {
                const totalFin = (currentTrayAssemblies.value || []).filter(a => a.finished_at || a.status).length;
                currentTrayItem.finished_count = totalFin;
                if (totalFin >= (currentTrayItem.total_sets || 22)) {
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

        // Quick Finish Slot Directly From Card Without Entering Form
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

          // Selalu buka modal validasi status dengan status yang sudah terisi / terpilih sebelumnya
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

              // Update local assembly collection realtime
              if (json.data) {
                const updatedAssy = json.data;
                const existingIdx = (currentTrayAssemblies.value || []).findIndex(a => a.set_number === setNum);
                if (existingIdx >= 0) {
                  currentTrayAssemblies.value[existingIdx] = updatedAssy;
                } else {
                  currentTrayAssemblies.value.push(updatedAssy);
                }
              }

              // Update traysList in realtime immediately
              const currentTrayItem = (traysList.value || []).find(t => t.tray_code === activeAssemblyCode);
              if (currentTrayItem) {
                const totalFin = (currentTrayAssemblies.value || []).filter(a => a.finished_at || a.status).length;
                currentTrayItem.finished_count = totalFin;
                if (totalFin >= (currentTrayItem.total_sets || 22)) {
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

        const showDetailChecklistFull = ref(false);

        const getFailedChecklistItems = (checklist) => {
          if (!Array.isArray(checklist)) return [];
          return checklist.filter(t => {
            const st = String(t.status || '').toUpperCase();
            return st === 'NOT_GOOD' || st === 'FAIL' || st === 'HOLD' || st === 'REWORK';
          });
        };

        // Open Read-Only Detail Modal for Completed Slot
        const openDetailModal = (slot, index, explicitContext = null) => {
          if (!slot) return;
          const context = explicitContext ||
            (slot.stage === 'QC' ? 'qc_test' :
              slot.stage === 'ASSY' ? 'assy_test' :
                (slot.warehouse_receipt_by || slot.warehouse_doc_number || slot.verified_by_user) ? 'warehouse_receipt' :
                  (currentTab.value === 'report' ? reportSubTab.value : (currentTab.value === 'assy_testing' ? 'assy_test' : (currentTab.value === 'qc' ? 'qc_test' : 'assembly'))));

          // 1. Parse TF1 Checklist
          let rawTF1 = slot.tf1_test_checklist || slot.assy_test_checklist || (context === 'assy_test' ? slot.test_checklist : null);
          let parsedTF1 = null;
          if (Array.isArray(rawTF1)) parsedTF1 = rawTF1;
          else if (typeof rawTF1 === 'string') {
            try { parsedTF1 = JSON.parse(rawTF1); } catch (_) { parsedTF1 = null; }
          }
          let finalTF1Checklist = [];
          if (Array.isArray(parsedTF1) && parsedTF1.length > 0) {
            finalTF1Checklist = parsedTF1.map((c, idx) => {
              const rawSt = String(c.status || '').trim().toUpperCase();
              let mappedStatus = 'PENDING';
              if (rawSt === 'GOOD' || rawSt === 'PASS' || rawSt === 'OK') {
                mappedStatus = 'GOOD';
              } else if (rawSt === 'NOT_GOOD' || rawSt === 'NOT GOOD' || rawSt === 'FAIL' || rawSt === 'HOLD' || rawSt === 'REWORK' || rawSt === 'NG') {
                mappedStatus = 'NOT_GOOD';
              } else {
                mappedStatus = 'PENDING';
              }
              return {
                test_num: c.test_num || (idx + 1),
                name: c.name || `Uji #${c.test_num || (idx + 1)}`,
                description: c.description || '',
                status: mappedStatus,
                notes: c.notes || ''
              };
            });
          }
          let tf1GoodCount = finalTF1Checklist.filter(t => t.status === 'GOOD').length;
          let tf1FailCount = finalTF1Checklist.filter(t => t.status === 'NOT_GOOD').length;
          let tf1PendingCount = finalTF1Checklist.filter(t => t.status === 'PENDING').length;

          // 2. Parse TF2 / QC Checklist
          let rawTF2 = slot.qc_test_checklist || (context === 'qc_test' ? slot.test_checklist : null);
          let parsedTF2 = null;
          if (Array.isArray(rawTF2)) parsedTF2 = rawTF2;
          else if (typeof rawTF2 === 'string') {
            try { parsedTF2 = JSON.parse(rawTF2); } catch (_) { parsedTF2 = null; }
          }
          let finalTF2Checklist = [];
          if (Array.isArray(parsedTF2) && parsedTF2.length > 0) {
            finalTF2Checklist = parsedTF2.map((c, idx) => {
              const rawSt = String(c.status || '').trim().toUpperCase();
              let mappedStatus = 'PENDING';
              if (rawSt === 'GOOD' || rawSt === 'PASS' || rawSt === 'OK') {
                mappedStatus = 'GOOD';
              } else if (rawSt === 'NOT_GOOD' || rawSt === 'NOT GOOD' || rawSt === 'FAIL' || rawSt === 'HOLD' || rawSt === 'REWORK' || rawSt === 'NG') {
                mappedStatus = 'NOT_GOOD';
              } else {
                mappedStatus = 'PENDING';
              }
              return {
                test_num: c.test_num || (idx + 1),
                name: c.name || `Uji QC #${c.test_num || (idx + 1)}`,
                description: c.description || '',
                status: mappedStatus,
                notes: c.notes || ''
              };
            });
          }
          let tf2GoodCount = finalTF2Checklist.filter(t => t.status === 'GOOD').length;
          let tf2FailCount = finalTF2Checklist.filter(t => t.status === 'NOT_GOOD').length;
          let tf2PendingCount = finalTF2Checklist.filter(t => t.status === 'PENDING').length;

          // Fallback legacy checklist
          let finalChecklist = (context === 'qc_test' && finalTF2Checklist.length > 0) ? finalTF2Checklist : (finalTF1Checklist.length > 0 ? finalTF1Checklist : finalTF2Checklist);
          let checklistGoodCount = (context === 'qc_test' && finalTF2Checklist.length > 0) ? tf2GoodCount : tf1GoodCount;
          let checklistFailCount = (context === 'qc_test' && finalTF2Checklist.length > 0) ? tf2FailCount : tf1FailCount;
          let checklistPendingCount = (context === 'qc_test' && finalTF2Checklist.length > 0) ? tf2PendingCount : tf1PendingCount;

          let docNum = slot.doc_number;
          if (!docNum || docNum === '-') {
            if (context === 'assy_test') docNum = getFormattedDocCode(slot.tray_code, 'tf1');
            else if (context === 'qc_test') docNum = slot.qc_doc_number || getFormattedDocCode(slot.tray_code, 'qc');
            else if (context === 'warehouse_receipt') docNum = slot.warehouse_doc_number || getFormattedDocCode(slot.tray_code, 'wh_gr');
            else if (context === 'wo') docNum = getFormattedDocCode(slot.tray_code, 'wo');
            else docNum = (slot.assy_doc_number && slot.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? slot.assy_doc_number : getFormattedDocCode(slot.tray_code, 'assy');
          }

          let originDoc = slot.origin_doc;
          if (!originDoc || originDoc === '-') {
            if (context === 'assy_test') originDoc = (slot.assy_doc_number && slot.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? slot.assy_doc_number : getFormattedDocCode(slot.tray_code, 'assy');
            else if (context === 'qc_test') originDoc = slot.tf1_doc_number || (slot.assy_doc_number && slot.assy_doc_number.toUpperCase().startsWith('ASSY/QC') ? slot.assy_doc_number : null) || getFormattedDocCode(slot.tray_code, 'tf1');
            else if (context === 'warehouse_receipt') originDoc = getFormattedDocCode(slot.tray_code, 'wo');
            else if (context === 'wo') originDoc = slot.source_document_mo || 'MO';
            else originDoc = getFormattedDocCode(slot.tray_code, 'wo');
          }

          let slotStatus = (context === 'assembly')
            ? (slot.assembly_status || slot.status || 'Good')
            : (slot.status || slot.assembly_status || slot.warehouse_receipt_status || 'Good');
          // Untuk history & dokumen: jika status adalah 'Dirakit Ulang', tampilkan hasil aktual perakitan ulang
          // Hasil aktual ada di assy_status_history (last real status setelah RU selesai)
          const slotStatusLow = String(slotStatus).toLowerCase();
          if (slotStatusLow === 'dirakit ulang' || slotStatusLow === 'ru') {
            const lastActualStatus = String(slot.assy_status_history || '').trim();
            if (lastActualStatus) {
              const lastLow = lastActualStatus.toLowerCase();
              if (lastLow === 'good') slotStatus = 'Good (RU)';
              else if (lastLow === 'hold') slotStatus = 'Hold (RU)';
              else if (lastLow === 'pending') slotStatus = 'Dirakit Ulang';
              else slotStatus = 'Dirakit Ulang';
            } else {
              slotStatus = 'Dirakit Ulang';
            }
          }
          if (context !== 'assembly') {
            if (slot.assy_test_status === 'Pending' || slot.qc_test_status === 'Pending' || slotStatus === 'Pending') {
              slotStatus = 'Pending';
            } else if (tf1PendingCount > 0 || (context === 'qc_test' && tf2PendingCount > 0)) {
              if (slotStatus === 'Good') {
                slotStatus = 'Pending';
              }
            }
          }

          activeDetailSlot.value = {
            ...slot,
            index: index,
            process_stage: context,
            status: slotStatus,
            started_at: slot.started_at || slot.created_at || slot.tray_created_at,
            finished_at: slot.finished_at || slot.created_at,
            tested_at: slot.created_at || slot.tested_at || slot.assy_tested_at,
            pic_assembly: slot.pic_assembly || slot.operator || slot.assigned_operator || (travelerTray.value && travelerTray.value.assigned_operator ? travelerTray.value.assigned_operator : '-'),
            pic_assembly_name: slot.pic_assembly_name || getOperatorFullName(slot.pic_assembly) || getOperatorFullName(slot.operator) || (slot.operator || slot.assigned_operator ? getOperatorFullName(slot.operator || slot.assigned_operator) : null) || (travelerTray.value && travelerTray.value.assigned_operator ? getOperatorFullName(travelerTray.value.assigned_operator) : null) || slot.pic_assembly || '-',
            pic_tester: slot.tester_full_name || slot.tester_username || slot.assy_tester_fullname || slot.pic_qc || slot.pic_assembly || '-',
            pic_warehouse: slot.verified_by_user || slot.warehouse_receipt_by || slot.pic_warehouse || '-',
            doc_number: docNum,
            assy_doc_number: (slot.assy_doc_number && slot.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? slot.assy_doc_number : (context === 'assembly' ? docNum : getFormattedDocCode(slot.tray_code, 'assy')),
            tf1_doc_number: slot.tf1_doc_number || (slot.assy_doc_number && slot.assy_doc_number.toUpperCase().startsWith('ASSY/QC') ? slot.assy_doc_number : null) || (context === 'assy_test' ? docNum : null),
            qc_doc_number: slot.qc_test_doc_number || slot.qc_doc_number || (context === 'qc_test' ? docNum : null),
            qc_status: slot.qc_test_status || slot.qc_status || (context === 'qc_test' ? slot.status : (finalTF2Checklist.length > 0 ? (tf2FailCount > 0 ? 'NOT_GOOD' : 'GOOD') : null)),
            qc_tester_name: slot.qc_tester_name || slot.qc_tester_username || slot.tester_full_name || null,
            qc_tested_at: slot.qc_tested_at || (context === 'qc_test' ? (slot.created_at || slot.tested_at) : null),
            qc_failure_reasons: slot.qc_failure_reasons || (context === 'qc_test' ? (slot.failure_reasons || slot.notes) : null),
            warehouse_doc_number: slot.warehouse_doc_number || (context === 'warehouse_receipt' ? docNum : null),
            warehouse_receipt_status: slot.warehouse_receipt_status || slot.verified_status || (context === 'warehouse_receipt' ? (slot.status || 'PENDING') : null),
            warehouse_receipt_by: slot.warehouse_receipt_by || slot.verified_by_user || slot.pic_warehouse || null,
            warehouse_receipt_at: slot.warehouse_receipt_at || slot.verified_at || null,
            warehouse_decline_reason: slot.warehouse_decline_reason || slot.decline_reason || null,
            origin_doc: originDoc,
            failure_reasons: slot.failure_reasons || slot.qc_failure_reasons || slot.assy_failure_reasons || null,
            set_number: slot.set_number || (index !== undefined ? index + 1 : 1),
            product_type: (currentTray.value && currentTray.value.product_type) || slot.product_type || 'Nanggala P9',
            tray_code: (currentTray.value && currentTray.value.tray_code) || slot.tray_code || '-',
            parsed_checklist: finalChecklist,
            checklist_good_count: checklistGoodCount,
            checklist_fail_count: checklistFailCount,
            checklist_pending_count: checklistPendingCount,
            parsed_tf1_checklist: finalTF1Checklist,
            tf1_good_count: tf1GoodCount,
            tf1_fail_count: tf1FailCount,
            tf1_pending_count: tf1PendingCount,
            parsed_tf2_checklist: finalTF2Checklist,
            tf2_good_count: tf2GoodCount,
            tf2_fail_count: tf2FailCount,
            tf2_pending_count: tf2PendingCount
          };
          showDetailModal.value = true;
          showDetailChecklistFull.value = false;
          nextTick(() => {
            renderQRCodeDetail(slot.part15_sn || 'J1100001');
          });
        };

        const isWarehouseReceiptVisible = (slot) => {
          if (!slot) return false;
          // Di modal khusus perakitan / TF1 / TF2, jangan tampilkan card verifikasi gudang
          if (slot.process_stage === 'assembly' || slot.process_stage === 'assy_test' || slot.process_stage === 'qc_test') {
            return false;
          }
          const rawQcStatus = String(slot.qc_status || slot.qc_test_status || '').toUpperCase();
          const hasQcChecklist = Array.isArray(slot.parsed_tf2_checklist) && slot.parsed_tf2_checklist.length > 0;

          const isQCLolos = (rawQcStatus === 'GOOD' || rawQcStatus === 'GOOD_QC' || rawQcStatus === 'ACC' || rawQcStatus === 'ACCEPTED') ||
            (hasQcChecklist && (slot.tf2_fail_count || 0) === 0 && (slot.tf2_good_count || 0) > 0);

          if (!isQCLolos) {
            return false;
          }

          const isAtWarehouseStage = slot.process_stage === 'warehouse_receipt' ||
            Boolean(slot.warehouse_receipt_status) ||
            Boolean(slot.warehouse_doc_number) ||
            Boolean(slot.warehouse_receipt_by) ||
            Boolean(slot.warehouse_receipt_at) ||
            currentTab.value === 'warehouse_receipt' ||
            (currentTab.value === 'report' && reportSubTab.value === 'warehouse_receipt');

          return isAtWarehouseStage;
        };

        const isQCResultCardVisible = (slot) => {
          if (!slot) return false;
          if (slot.process_stage === 'qc_test' || slot.process_stage === 'warehouse_receipt') return true;
          if (currentTab.value === 'qc' || currentTab.value === 'warehouse_receipt') return true;
          if (currentTab.value === 'report' && (reportSubTab.value === 'qc_test' || reportSubTab.value === 'warehouse_receipt')) return true;
          if (slot.qc_doc_number || slot.qc_test_doc_number) return true;
          if (slot.qc_status || slot.qc_test_status) return true;
          if (Array.isArray(slot.parsed_tf2_checklist) && slot.parsed_tf2_checklist.length > 0) return true;
          return false;
        };

        const getQCReportStatus = (slot) => {
          if (!slot) return { text: 'Menunggu QC', state: 'pending' };
          let raw = '';
          if (slot.process_stage === 'qc_test') {
            raw = slot.qc_test_status || slot.status || slot.qc_status || '';
          } else {
            raw = slot.qc_status || slot.qc_test_status || '';
          }
          const s = String(raw).trim().toUpperCase();

          const hasTF2Checklist = Array.isArray(slot.parsed_tf2_checklist) && slot.parsed_tf2_checklist.length > 0;
          const tf2Fails = slot.tf2_fail_count || (hasTF2Checklist ? slot.parsed_tf2_checklist.filter(t => t.status === 'NOT_GOOD').length : 0);
          const tf2Goods = slot.tf2_good_count || (hasTF2Checklist ? slot.parsed_tf2_checklist.filter(t => t.status === 'GOOD').length : 0);

          if (s === 'NOT_GOOD' || s === 'NOT GOOD' || s === 'HOLD' || s === 'HOLD_QC' || s === 'REWORK' || s === 'REWORK_QC' || s === 'FAIL' || s === 'FAILED' || s === 'NG' || tf2Fails > 0) {
            return { text: 'Not Good', state: 'not_good' };
          }
          if (s === 'GOOD' || s === 'GOOD_QC' || s === 'PASS' || s === 'PASSED' || s === 'ACC' || s === 'ACCEPTED' || (hasTF2Checklist && tf2Goods > 0 && tf2Fails === 0)) {
            return { text: 'Good', state: 'good' };
          }
          if (s.includes('MENUNGGU') || s.includes('BELUM') || s === 'PENDING' || !s) {
            return { text: 'Menunggu QC', state: 'pending' };
          }
          return { text: slot.qc_status || slot.status || 'Menunggu QC', state: 'pending' };
        };

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

        const parseWIBDate = (dtStr) => {
          if (!dtStr) return null;
          if (dtStr instanceof Date) return isNaN(dtStr.getTime()) ? null : dtStr;
          let str = String(dtStr).trim();
          if (!str || ['none', 'null', '-', 'undefined', 'invalid date', 'n/a', ''].includes(str.toLowerCase())) return null;
          if (str.endsWith('Z') || str.includes('+') || (str.length > 10 && str.substring(10).includes('-'))) {
            const d = new Date(str);
            return isNaN(d.getTime()) ? null : d;
          }
          str = str.replace(' ', 'T');
          if (str.length === 10) str += 'T00:00:00';
          const d = new Date(str + '+07:00');
          return isNaN(d.getTime()) ? null : d;
        };

        const getEffectiveFinishedAt = (start, finish) => {
          if (!finish) return finish;
          if (!start) return finish;
          try {
            const d1 = parseWIBDate(start);
            let d2 = parseWIBDate(finish);
            if (d1 && d2 && !isNaN(d1.getTime()) && !isNaN(d2.getTime()) && d2 < d1) {
              const diff = d1.getTime() - d2.getTime();
              // If difference is between 5 hours and 9 hours (UTC vs WIB ~7h skew)
              if (diff >= 18000 * 1000 && diff <= 32400 * 1000) {
                d2 = new Date(d2.getTime() + 7 * 3600 * 1000);
                const pad = (n) => String(n).padStart(2, '0');
                return `${d2.getFullYear()}-${pad(d2.getMonth() + 1)}-${pad(d2.getDate())} ${pad(d2.getHours())}:${pad(d2.getMinutes())}:${pad(d2.getSeconds())}`;
              }
            }
          } catch (_) { }
          return finish;
        };

        const formatDuration = (start, finish) => {
          if (start === null || start === undefined || start === '') return '-';
          try {
            let diffSec = 0;
            if (finish !== undefined && finish !== null && finish !== '') {
              const d1 = parseWIBDate(start);
              let d2 = parseWIBDate(finish);
              if (d1 && d2 && !isNaN(d1.getTime()) && !isNaN(d2.getTime())) {
                if (d2 < d1) {
                  const diff = d1.getTime() - d2.getTime();
                  if (diff >= 18000 * 1000 && diff <= 32400 * 1000) {
                    d2 = new Date(d2.getTime() + 7 * 3600 * 1000);
                  }
                }
                diffSec = Math.max(0, Math.floor((d2 - d1) / 1000));
              }
            } else if (typeof start === 'number') {
              diffSec = Math.max(0, Math.floor(start));
            } else if (!isNaN(Number(start))) {
              diffSec = Math.max(0, Math.floor(Number(start)));
            } else {
              return '-';
            }

            const h = Math.floor(diffSec / 3600);
            const m = Math.floor((diffSec % 3600) / 60);
            const s = diffSec % 60;

            if (h > 0) return `${h} jam ${m} mnt ${s} dtk`;
            if (m > 0) return `${m} mnt ${s} dtk`;
            return `${s} dtk`;
          } catch (_) {
            return '-';
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

          // Sesuai arahan user: hanya ada ACC dan DECLINED, jadi ambil HANYA yang ACC saja
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
          // Ambil hanya unit berstatus ACC
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

          // Bersihkan fokus dan seleksi teks agar tidak terekam ke canvas/PDF
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

            // PDF 80mm x 20mm landscape presisi
            const doc = new jsPDF({
              orientation: 'landscape',
              unit: 'mm',
              format: [80, 20],
              compress: true
            });
            doc.addImage(imgData, 'PNG', 0, 0, 80, 20, undefined, 'FAST');

            // Unduh langsung dengan nama SN yang valid
            doc.save(`${safeSN}.pdf`);
            triggerToast('Sukses Unduh', `File PDF label ${safeSN}.pdf berhasil diunduh.`, 'success');
          }).catch(err => {
            console.error('PDF error:', err);
            triggerToast('Gagal', 'Gagal membuat PDF: ' + err.message, 'error');
          });
        };

        const printP9LabelDirect = () => {
          const sn = (activeLabelSN.value && activeLabelSN.value.part15_sn) ? activeLabelSN.value.part15_sn : 'Product_P9';

          // Inject rule @page 80mm 20mm landscape margin 0mm khusus cetak thermal
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

        // Alias untuk kompatibilitas fungsi cetak lama
        const printP9Label = downloadP9LabelPDF;

        // Gudang Supply Logic
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

        const suggestionCache = new Map();
        let fetchDebounceTimer = null;
        const fetchInputSuggestions = (idx, field, delay = 0, openDropdown = true) => {
          clearTimeout(fetchDebounceTimer);
          const doFetch = async () => {
            const row = gudangRows.value[idx];
            if (!row) return;
            const pType = field === 'p1' ? '1' : (field === 'p12' ? '12' : '15');
            const queryVal = (row[field] || '').trim();

            // Used in other rows
            const usedInForm = new Set();
            gudangRows.value.forEach((r, i) => {
              if (i !== idx && r[field] && r[field].trim()) {
                usedInForm.add(r[field].trim().toUpperCase());
                usedInForm.add(r[field].trim().replace(/[.,\s]/g, ''));
              }
            });

            const cacheKey = `${pType}:${queryVal}`;
            if (suggestionCache.has(cacheKey)) {
              const cachedData = suggestionCache.get(cacheKey);
              if (openDropdown) {
                const filtered = cachedData.filter(item => {
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
              return;
            }

            try {
              const json = await fetchAPI(`/master/available?type=${pType}&q=${encodeURIComponent(queryVal)}&limit=60`);
              if (json.ok && Array.isArray(json.data)) {
                suggestionCache.set(cacheKey, json.data);
                if (suggestionCache.size > 80) {
                  const firstK = suggestionCache.keys().next().value;
                  suggestionCache.delete(firstK);
                }

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

        // Numbers-Only Enforcement
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

        // Touch gesture tracking for tablet/mobile dropdowns to distinguish scroll vs tap
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
          if (dropdownTouchMoved) {
            // User was scrolling the list, do NOT select!
            return;
          }
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

          // Move to next row
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

        // Real-Time Warning Method
        const getInputWarning = (idx, field) => {
          const row = gudangRows.value[idx];
          if (!row) return '';
          const val = (row[field] || '').trim();
          if (!val) return '';

          // 1. Check duplicate within current form
          const valClean = val.toUpperCase().replace(/[.,\s]/g, '');
          for (let i = 0; i < gudangRows.value.length; i++) {
            if (i !== idx) {
              const otherVal = (gudangRows.value[i][field] || '').trim().toUpperCase().replace(/[.,\s]/g, '');
              if (otherVal && otherVal === valClean) {
                return `Nomor sudah dipilih di Baris #${i + 1}`;
              }
            }
          }

          // 2. Check if valid query length but 0 suggestions found in list when user was typing
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
              // Hanya auto-pad jika input pendek 1-2 digit (misal: '1' -> '001', '25' -> '025')
              if (val.length <= 2) {
                const num = parseInt(val, 10);
                if (num >= 1 && num <= 99) {
                  row.p1 = String(num).padStart(3, '0');
                }
              }
              // Jika sudah 3 digit atau lebih (misal: '001', '0712', '09856', '26371'), biarkan apa adanya dan jangan hilangkan angka 0 di depan!
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
              history.pushState({ type: 'gudang_stage', stage }, '', '#warehouse/stage-' + stage);
              gudangStageHistoryDepth.value++;
            } catch (_) { }
          } else if (stage < prevStage && !isNavigatingBack) {
            const diff = prevStage - stage;
            if (gudangStageHistoryDepth.value >= diff) {
              gudangStageHistoryDepth.value -= diff;
              programmaticBackCount += diff;
              try { window.history.go(-diff); } catch (_) { }
            }
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

            // Format input first
            formatGudangInput(idx, col);
            const val = (row[col] || '').trim();

            // Check if inputDropdown has suggestions
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

            // Fallback: check API directly if suggestion not populated yet
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
              // Not in available list / invalid number
              const pType = col === 'p1' ? '1' : (col === 'p12' ? '12' : '15');
              let rangeHint = '';
              if (pType === '1') rangeHint = ' (Rentang 001 - 999 / 1000 - 6000 / Double Part)';
              else if (pType === '12') rangeHint = ' (Rentang 60001 - 63500 / 70001 - 71500 / Double Part)';
              else if (pType === '15') rangeHint = ' (Induk J11..)';

              triggerToast('SN Tidak Tersedia', `Nomor Part ${pType} '${rawVal}' tidak tersedia di Master Stock${rangeHint}! Silakan pilih nomor dari dropdown.`, 'error');

              // Keep focus on current input
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
          gudangRows.value.forEach((r, idx) => {
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
              gudangRows.value = [{ p1: '', p12: '', p15: '' }];
              gudangColStage.value = 1;
              if (gudangStageHistoryDepth.value > 0) {
                const cleanCount = gudangStageHistoryDepth.value;
                gudangStageHistoryDepth.value = 0;
                programmaticBackCount += cleanCount;
                try { window.history.go(-cleanCount); } catch (_) { }
              }
              gudangSourceMO.value = '';
              gudangSourceMOSuffix.value = '';
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

        const formatExcelDateTime = (dt) => {
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

        const getShiftName = (dt) => {
          if (!dt) return 'Shift 1';
          const d = new Date(dt);
          if (isNaN(d.getTime())) return 'Shift 1';
          const h = d.getHours();
          if (h >= 6 && h < 14) return 'Shift 1';
          if (h >= 14 && h < 22) return 'Shift 2';
          return 'Shift 3';
        };

        const exportTrayToExcel = async (code = null) => {
          const targetCode = code || selectedTrayCode.value || (newTrayResult.value ? newTrayResult.value.tray_code : '');
          if (!targetCode) {
            triggerToast('Export Excel', 'Pilih Tray terlebih dahulu!', 'warn');
            return;
          }

          try {
            const json = await fetchAPI(`/assembly/tray?code=${encodeURIComponent(targetCode)}`);
            if (!json.ok || !json.data) {
              triggerToast('Export Excel', 'Gagal memuat data Tray!', 'error');
              return;
            }

            const tray = json.data.tray;
            const parts = json.data.parts || [];
            const assemblies = json.data.assemblies || [];
            const assyMap = {};
            assemblies.forEach(a => { assyMap[a.set_number] = a; });

            const p15List = parts.filter(p => p.part_type === '15');
            const p1List = parts.filter(p => p.part_type === '1');
            const p12List = parts.filter(p => p.part_type === '12');
            const totalSets = Math.max(p15List.length, assemblies.length);

            if (totalSets === 0) {
              triggerToast('Export Excel', 'Tidak ada data part dalam Tray ini!', 'warn');
              return;
            }
            const cleanName = (tray.tray_code || 'TRAY').replace(/[\\/:*?"<>|]/g, '_');
            const fileName = `${cleanName}.xlsx`;

            const columns = [
              { header: 'Set #', width: 7, key: 'set_number' },
              { header: 'SN Product (Part 15)', width: 18, key: 'part15' },
              { header: 'PN P9 Part 1', width: 13, key: 'part1' },
              { header: 'PN P9 Part 12', width: 14, key: 'part12' },
              { header: 'No. Dokumen / MO', width: 18, key: 'doc' },
              { header: 'Tgl Supply', width: 13, key: 'supply_date' },
              { header: 'Petugas Gudang', width: 18, key: 'wh' },
              { header: 'Tgl Selesai', width: 13, key: 'finish_date' },
              { header: 'Operator Assembly', width: 18, key: 'assy' },
              { header: 'Hasil Assembly', width: 14, key: 'status' },
              { header: 'Status Verifikasi', width: 14, key: 'verified' },
              { header: 'Catatan / Alasan', width: 20, key: 'reason' }
            ];

            const colMaxLengths = columns.map(c => c.header.length);

            const dataRows = [];
            for (let i = 0; i < totalSets; i++) {
              const setNum = i + 1;
              const a = assyMap[setNum];
              const p15Val = a ? (a.part15_sn || '-') : (p15List[i] ? p15List[i].sn : '-');
              const p1Val = a ? (a.part1_sn || '-') : (p1List[i] ? p1List[i].sn : '-');
              const p12Val = a ? (a.part12_sn || '-') : (p12List[i] ? p12List[i].sn : '-');
              const docVal = tray.source_document_mo || tray.tray_code || '-';
              const supplyDateVal = formatDateOnly(tray.created_at);
              const whVal = getOperatorFullName(tray.pic_warehouse) || '-';
              const finishDateVal = a && a.finished_at ? formatDateOnly(a.finished_at) : '-';
              const assyVal = a ? (getOperatorFullName(a.pic_assembly) || getOperatorFullName(tray.assigned_operator) || '-') : (getOperatorFullName(tray.assigned_operator) || '-');
              const statusVal = a ? (a.status || 'Good').toUpperCase() : 'PENDING';
              const verifVal = a ? (a.verified_status === 'ACC' ? 'ACC' : (a.is_declined || a.verified_status === 'DECLINED' ? 'DECLINED' : 'BELUM')) : 'BELUM';
              const reasonVal = (a && a.reason) ? a.reason : '-';

              const rowValues = [
                setNum,
                p15Val,
                p1Val,
                p12Val,
                docVal,
                supplyDateVal,
                whVal,
                finishDateVal,
                assyVal,
                statusVal,
                verifVal,
                reasonVal
              ];

              rowValues.forEach((val, cIdx) => {
                const strLen = String(val || '').length;
                if (strLen > colMaxLengths[cIdx]) colMaxLengths[cIdx] = strLen;
              });

              dataRows.push({ a, rowValues });
            }

            if (typeof ExcelJS === 'undefined') {
              const rows = dataRows.map(dr => {
                const rowObj = {};
                columns.forEach((col, idx) => {
                  rowObj[col.header] = dr.rowValues[idx];
                });
                return rowObj;
              });
              const worksheet = XLSX.utils.json_to_sheet(rows);
              worksheet['!cols'] = columns.map((colDef, i) => ({
                wch: Math.max((colMaxLengths[i] || 10) + 4, colDef.width || 14)
              }));
              const workbook = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(workbook, worksheet, 'Traceability_P9');
              XLSX.writeFile(workbook, fileName);
              triggerToast('Berhasil', `File ${fileName} berhasil didownload!`, 'success');
              return;
            }

            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Sistem Assembly & Traceability NKRI';
            workbook.created = new Date();

            const sheet = workbook.addWorksheet('Traceability_P9', {
              pageSetup: {
                paperSize: 9, // A4
                orientation: 'landscape',
                fitToPage: true,
                fitToWidth: 1,
                fitToHeight: dataRows.length <= 16 ? 1 : 0,
                showGridLines: true,
                horizontalCentered: true,
                verticalCentered: true,
                margins: { left: 0.2, right: 0.2, top: 0.25, bottom: 0.25, header: 0.1, footer: 0.1 }
              }
            });

            // 1. Title Banner
            const titleRow = sheet.addRow(['LEMBAR TRAVELER FINAL ASSEMBLY - NANGGALA P9']);
            titleRow.font = { name: 'Arial', size: 15, bold: true, color: { argb: 'FF1E293B' } };
            titleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 38;
            sheet.mergeCells(1, 1, 1, columns.length);

            // 2. Subtitle Banner
            const subTitleRow = sheet.addRow([`Tray: ${tray.tray_code} | MO: ${tray.source_document_mo || '-'} | PIC Gudang: ${getOperatorFullName(tray.pic_warehouse)} | Operator Assy: ${getOperatorFullName(tray.assigned_operator)}`]);
            subTitleRow.font = { name: 'Arial', size: 10, italic: true, color: { argb: 'FF64748B' } };
            subTitleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            subTitleRow.height = 22;
            sheet.mergeCells(2, 1, 2, columns.length);

            const blankRow = sheet.addRow([]); // Blank row
            blankRow.height = 10;

            // 3. Header Row (Row 4)
            const headerRow = sheet.addRow(columns.map(c => c.header));
            headerRow.height = 32;
            headerRow.eachCell((cell) => {
              cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FFFFFFFF' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
              cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
              cell.border = {
                top: { style: 'thin', color: { argb: 'FF0F172A' } },
                left: { style: 'thin', color: { argb: 'FF0F172A' } },
                bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
                right: { style: 'thin', color: { argb: 'FF0F172A' } }
              };
            });

            // 4. Data Rows
            const getStatusColorConfig = (statusStr) => {
              const s = (statusStr || '').toUpperCase();
              if (s === 'SCRAP') {
                return { font: 'FFDC2626' };
              } else if (s === 'HOLD') {
                return { font: 'FFD97706' };
              } else if (s === 'REWORK') {
                return { font: 'FF7C3AED' };
              } else if (s === 'GOOD') {
                return { font: 'FF059669' };
              }
              return { font: 'FF475569' };
            };

            const dynamicRowHeight = dataRows.length <= 15 ? Math.max(26, Math.min(36, Math.floor(360 / Math.max(dataRows.length, 1)))) : 25;

            dataRows.forEach((dr, i) => {
              const a = dr.a;
              const row = sheet.addRow(dr.rowValues);
              row.height = dynamicRowHeight;

              const statusVal = dr.rowValues[9];
              const isP15Def = isPartDefective(a, '15');
              const isP12Def = isPartDefective(a, '12');
              const isP1Def = isPartDefective(a, '1');
              const statusCfg = getStatusColorConfig(a ? a.status : 'Pending');

              row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                cell.font = { name: 'Arial', size: 10, color: { argb: 'FF1E293B' } };
                cell.border = {
                  top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                };

                if (colNumber === 1 || colNumber === 6 || colNumber === 8 || colNumber === 10 || colNumber === 11) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (colNumber >= 2 && colNumber <= 4) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F172A' } };
                } else if (colNumber === 5) {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF2563EB' } };
                } else {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                }

                if (i % 2 === 1) {
                  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                }

                // Part 15 Defective Color Styling (Text color only)
                if (colNumber === 2 && isP15Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Part 12 Defective Color Styling (Text color only)
                if (colNumber === 3 && isP12Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Part 1 Defective Color Styling (Text color only)
                if (colNumber === 4 && isP1Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Hasil Assembly Status Styling (Text color only)
                if (colNumber === 10) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Status Verifikasi Styling (Text color only)
                if (colNumber === 11 && a) {
                  if (a.verified_status === 'ACC') {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF059669' } };
                  } else if (a.is_declined || a.verified_status === 'DECLINED') {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFDC2626' } };
                  }
                }
              });
            });

            // Auto full width scaling for A4 Landscape print format
            const targetTotalA4Width = 196;
            const currentTotal = columns.reduce((sum, colDef, idx) => {
              const maxLen = colMaxLengths[idx] || 8;
              return sum + Math.max(maxLen + 2, colDef.width || 12);
            }, 0);
            const scaleFactor = currentTotal < targetTotalA4Width ? targetTotalA4Width / currentTotal : 1;

            columns.forEach((colDef, idx) => {
              const maxLen = colMaxLengths[idx] || 8;
              const baseWidth = Math.max(maxLen + 2, colDef.width || 12);
              const finalWidth = Math.round(baseWidth * scaleFactor * 10) / 10;
              const col = sheet.getColumn(idx + 1);
              col.width = finalWidth;
            });

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const aLink = document.createElement('a');
            aLink.href = url;
            aLink.download = fileName;
            document.body.appendChild(aLink);
            aLink.click();
            setTimeout(() => { document.body.removeChild(aLink); window.URL.revokeObjectURL(url); }, 250);

            triggerToast('Berhasil', `File ${fileName} berhasil diexport dengan tata letak rapi & lebar kolom otomatis!`, 'success');
          } catch (e) {
            triggerToast('Error', e.message, 'error');
          }
        };

        const idxZebra = (i) => i % 2 === 1;

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
            rawCode = (activeAssyQCDocSlot.value && activeAssyQCDocSlot.value.assy_doc_number) || (assyQCQualifiedItems.value.find(i => i.assy_doc_number)?.assy_doc_number) || getFormattedDocCode(targetTrayCode, 'assy');
          } else {
            rawCode = getFormattedDocCode(targetTrayCode, 'assy');
          }

          const cleanFileName = rawCode.replace(/[\/\\]/g, '_');
          document.title = cleanFileName;

          window.print();
          setTimeout(() => {
            document.title = origTitle || 'Sistem Traceability Final Assembly P9';
          }, 1000);
        };

        const formatDateOnly = (dt) => {
          if (!dt) return '-';
          try {
            const d = parseWIBDate(dt);
            if (!d || isNaN(d.getTime())) return String(dt);
            return d.toLocaleDateString('id-ID', {
              timeZone: 'Asia/Jakarta',
              day: '2-digit', month: '2-digit', year: 'numeric'
            });
          } catch (_) {
            return String(dt);
          }
        };

        const formatTimeOnly = (dtStr, refStartStr = null) => {
          if (!dtStr) return '-';
          try {
            let effective = dtStr;
            if (refStartStr) {
              effective = getEffectiveFinishedAt(refStartStr, dtStr);
            }
            const d = parseWIBDate(effective);
            if (!d || isNaN(d.getTime())) return String(dtStr);
            const parts = new Intl.DateTimeFormat('id-ID', {
              timeZone: 'Asia/Jakarta',
              hour: '2-digit',
              minute: '2-digit',
              hour12: false
            }).format(d);
            return parts.replace(':', '.');
          } catch (_) {
            return dtStr;
          }
        };

        const formatLifecycleHistory = (rawHistory, currentStatus = '') => {
          let hist = String(rawHistory || '').trim();
          if (!hist) {
            if (!currentStatus) return '-';
            const s = String(currentStatus).trim();
            const sl = s.toLowerCase();
            if (sl === 'good') return 'Good';
            if (sl.includes('rework')) return 'Hold -> Rework';
            if (sl.includes('hold')) return 'Hold';
            if (sl.includes('scrap')) return 'Scrap';
            // RU / Dirakit Ulang — tampilkan apa adanya, bukan raw 'RU'
            if (sl === 'ru' || sl === 'dirakit ulang') return 'Dirakit Ulang';
            return s;
          }
          hist = hist.replace(/\bQC\b/gi, '').replace(/\s+/g, ' ').trim();
          let parts = hist.split('->').map(p => p.trim()).filter(Boolean);
          if (parts.length > 1 && parts[0].toLowerCase() === 'good' && parts.some(p => p.toLowerCase() === 'hold')) {
            const holdIdx = parts.findIndex(p => p.toLowerCase() === 'hold');
            if (holdIdx !== -1) {
              parts = parts.slice(holdIdx);
            }
          }
          parts = parts.map(p => {
            const low = p.toLowerCase();
            if (low === 'good') return 'Good';
            if (low === 'hold') return 'Hold';
            if (low === 'rework') return 'Rework';
            if (low === 'scrap') return 'Scrap';
            if (low === 'ru' || low === 'dirakit ulang') return 'Dirakit Ulang';
            return p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
          });
          const deduped = [];
          for (let i = 0; i < parts.length; i++) {
            if (i === 0 || parts[i] !== parts[i - 1]) {
              deduped.push(parts[i]);
            }
          }
          return deduped.join(' -> ') || (currentStatus || '-');
        };

        const getStatusHistoryBadgeClass = (history, fallbackStatus = '') => {
          const formatted = formatLifecycleHistory(history, fallbackStatus);
          const low = formatted.toLowerCase();
          if (low.endsWith('good') || low === 'good') {
            return 'bg-emerald-50 text-emerald-800 border-emerald-300';
          }
          if (low.includes('dirakit ulang')) {
            return 'bg-indigo-50 text-indigo-800 border-indigo-300';
          }
          if (low.endsWith('rework') || low.includes('rework')) {
            return 'bg-amber-50 text-amber-800 border-amber-300';
          }
          if (low.endsWith('hold') || low.includes('hold')) {
            return 'bg-rose-50 text-rose-800 border-rose-300';
          }
          if (low === 'scrap') {
            return 'bg-slate-100 text-slate-800 border-slate-300';
          }
          return 'bg-slate-50 text-slate-600 border-slate-200';
        };

        const exportToExcel = async () => {
          if (!filteredLaporan.value.length) {
            triggerToast('Export Excel', 'Tidak ada data laporan yang cocok dengan filter untuk diexport!', 'warn');
            return;
          }

          // Determine file name
          let baseCode = '';
          if (searchQuery.value && searchQuery.value.trim()) {
            baseCode = searchQuery.value.trim().replace(/[\/:*?"<>|\\]/g, '_');
          } else if (laporanFilter.value.trayCode) {
            baseCode = laporanFilter.value.trayCode.replace(/[\/:*?"<>|\\]/g, '_');
          } else if (laporanFilter.value.operator) {
            baseCode = `Traceability_${laporanFilter.value.operator.replace(/[\/:*?"<>|\\]/g, '_')}`;
          } else if (laporanFilter.value.status) {
            baseCode = `Traceability_${laporanFilter.value.status}`;
          } else {
            const d = new Date();
            const dateStr = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
            baseCode = `Laporan_Traceability_P9_${dateStr}`;
          }
          const fileName = `${baseCode}.xlsx`;

          const columns = [
            { header: 'No', width: 6, key: 'no' },
            { header: 'SN P9 Part 15', width: 20, key: 'part15' },
            { header: 'PN P9 Part 1', width: 18, key: 'part1' },
            { header: 'PN P9 Part 12', width: 18, key: 'part12' },
            { header: 'No. Doc / Tray', width: 24, key: 'doc' },
            { header: 'Petugas Gudang', width: 24, key: 'wh' },
            { header: 'Operator Perakitan', width: 24, key: 'assy' },
            { header: 'Tanggal Mulai', width: 16, key: 'start_date' },
            { header: 'Waktu Mulai', width: 14, key: 'start_time' },
            { header: 'Tanggal Selesai', width: 16, key: 'end_date' },
            { header: 'Waktu Selesai', width: 14, key: 'end_time' },
            { header: 'Status Barang', width: 16, key: 'status' },
            { header: 'Catatan / Alasan', width: 28, key: 'reason' },
            { header: 'Status Diperiksa', width: 18, key: 'verified' }
          ];

          const colMaxLengths = columns.map(c => c.header.length);

          const dataRows = [];
          filteredLaporan.value.forEach((l, idx) => {
            const effStatus = (l.status || 'Good');
            const isFinished = !!(effStatus && effStatus.trim() && effStatus.toLowerCase() !== 'pending');
            const isGood = effStatus.toLowerCase() === 'good';
            const reasonText = l.reason || '-';
            const rawStatus = isFinished ? effStatus.toUpperCase() : '-';
            const verifText = l.verified_by_gudang ? 'VERIFIED' : 'NOT VERIFIED';

            const p15Val = l.part15_sn || '-';
            const p1Val = l.part1_sn || '-';
            const p12Val = l.part12_sn || '-';
            const docVal = l.tray_code || '-';
            const whVal = getOperatorFullName(l.pic_warehouse) || '-';
            const assyVal = getOperatorFullName(l.pic_assembly) || getOperatorFullName(l.assigned_operator) || '-';
            const startDateVal = isFinished ? formatDateOnly(l.started_at || l.tray_created_at) : '-';
            const startTimeVal = isFinished ? formatTimeOnly(l.started_at) : '-';
            const endDateVal = isFinished ? formatDateOnly(l.finished_at || l.started_at) : '-';
            const endTimeVal = isFinished ? formatTimeOnly(l.finished_at) : '-';

            const rowValues = [
              idx + 1,
              p15Val,
              p1Val,
              p12Val,
              docVal,
              whVal,
              assyVal,
              startDateVal,
              startTimeVal,
              endDateVal,
              endTimeVal,
              rawStatus,
              isFinished ? reasonText : '-',
              verifText
            ];

            rowValues.forEach((val, cIdx) => {
              const strLen = String(val || '').length;
              if (strLen > colMaxLengths[cIdx]) colMaxLengths[cIdx] = strLen;
            });

            dataRows.push({ l, rowValues, idx, isFinished });
          });

          try {
            if (typeof ExcelJS === 'undefined') {
              const rows = dataRows.map(dr => {
                const rowObj = {};
                columns.forEach((col, cIdx) => {
                  rowObj[col.header] = dr.rowValues[cIdx];
                });
                return rowObj;
              });
              const worksheet = XLSX.utils.json_to_sheet(rows);
              worksheet['!cols'] = columns.map((colDef, i) => ({
                wch: Math.max((colMaxLengths[i] || 10) + 4, colDef.width || 14)
              }));
              const workbook = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(workbook, worksheet, 'Laporan_Traceability');
              XLSX.writeFile(workbook, fileName);
              triggerToast('Berhasil', `File ${fileName} berhasil didownload (${dataRows.length} baris data)!`, 'success');
              return;
            }

            const workbook = new ExcelJS.Workbook();
            workbook.creator = 'Sistem Assembly & Traceability NKRI';
            workbook.created = new Date();

            const sheet = workbook.addWorksheet('Laporan_Traceability', {
              pageSetup: {
                paperSize: 9, // A4
                orientation: 'landscape',
                fitToPage: true,
                fitToWidth: 1,
                fitToHeight: dataRows.length <= 16 ? 1 : 0,
                showGridLines: true,
                horizontalCentered: true,
                verticalCentered: true,
                margins: {
                  left: 0.2,
                  right: 0.2,
                  top: 0.25,
                  bottom: 0.25,
                  header: 0.1,
                  footer: 0.1
                }
              }
            });

            // 1. Title Banner
            const titleRow = sheet.addRow(['LAPORAN TRACEABILITY & PERAKITAN - NANGGALA P9']);
            titleRow.font = { name: 'Arial', size: 15, bold: true, color: { argb: 'FF1E293B' } };
            titleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 38;
            sheet.mergeCells(1, 1, 1, columns.length);

            // 2. Subtitle / Metadata
            const filterOp = laporanFilter.value.operator ? `Operator: ${laporanFilter.value.operator}` : 'Semua Operator';
            const filterStat = laporanFilter.value.status ? `Status: ${laporanFilter.value.status}` : 'Semua Status';
            const subTitleRow = sheet.addRow([`Waktu Unduh: ${formatDateTime(new Date())} | Filter: ${filterOp} & ${filterStat} | Total Data: ${filteredLaporan.value.length} Item`]);
            subTitleRow.font = { name: 'Arial', size: 10, italic: true, color: { argb: 'FF64748B' } };
            subTitleRow.alignment = { horizontal: 'center', vertical: 'middle' };
            subTitleRow.height = 22;
            sheet.mergeCells(2, 1, 2, columns.length);

            const blankRow = sheet.addRow([]); // Blank separator
            blankRow.height = 10;

            // 3. Header Row (Row 4)
            const headerRow = sheet.addRow(columns.map(c => c.header));
            headerRow.height = 32;
            headerRow.eachCell((cell) => {
              cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: 'FFFFFFFF' } };
              cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
              cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
              cell.border = {
                top: { style: 'thin', color: { argb: 'FF0F172A' } },
                left: { style: 'thin', color: { argb: 'FF0F172A' } },
                bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
                right: { style: 'thin', color: { argb: 'FF0F172A' } }
              };
            });

            // Status Styling Configuration
            const getStatusColorConfig = (statusStr) => {
              const s = (statusStr || '').toUpperCase();
              if (s === 'SCRAP') {
                return { font: 'FFDC2626' };
              } else if (s === 'HOLD') {
                return { font: 'FFD97706' };
              } else if (s === 'REWORK') {
                return { font: 'FF7C3AED' };
              } else if (s === 'GOOD') {
                return { font: 'FF059669' };
              }
              return { font: 'FF475569' };
            };

            const dynamicRowHeight = dataRows.length <= 15 ? Math.max(26, Math.min(36, Math.floor(360 / Math.max(dataRows.length, 1)))) : 25;

            // 4. Data Rows
            dataRows.forEach((dr) => {
              const { l, rowValues, idx, isFinished } = dr;
              const row = sheet.addRow(rowValues);
              row.height = dynamicRowHeight;

              const isP15Def = isPartDefective(l, '15');
              const isP12Def = isPartDefective(l, '12');
              const isP1Def = isPartDefective(l, '1');
              const statusCfg = getStatusColorConfig(l.status);

              row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                cell.font = { name: 'Arial', size: 10, color: { argb: 'FF1E293B' } };
                cell.border = {
                  top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                  right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                };

                if (colNumber === 1 || (colNumber >= 8 && colNumber <= 11) || colNumber === 12 || colNumber === 14) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                } else if (colNumber >= 2 && colNumber <= 4) {
                  cell.alignment = { horizontal: 'center', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF0F172A' } };
                } else if (colNumber === 5) {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                  cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF2563EB' } };
                } else {
                  cell.alignment = { horizontal: 'left', vertical: 'middle' };
                }

                if (idx % 2 === 1) {
                  cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8FAFC' } };
                }

                // Part 15 Defective Color Styling (Text color only)
                if (colNumber === 2 && isP15Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Part 12 Defective Color Styling (Text color only)
                if (colNumber === 3 && isP12Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Part 1 Defective Color Styling (Text color only)
                if (colNumber === 4 && isP1Def) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Status Column Styling (Text color only)
                if (colNumber === 12 && isFinished) {
                  cell.font = { name: 'Arial', size: 10.5, bold: true, color: { argb: statusCfg.font } };
                }

                // Status Diperiksa Styling (Text color only)
                if (colNumber === 14) {
                  if (l.verified_by_gudang) {
                    cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF059669' } };
                  } else {
                    cell.font = { name: 'Arial', size: 10, color: { argb: 'FF64748B' } };
                  }
                }
              });
            });

            // Auto full width scaling for A4 Landscape print format
            const targetTotalA4Width = 198;
            const currentTotal = columns.reduce((sum, colDef, i) => {
              const maxLen = colMaxLengths[i] || 8;
              return sum + Math.max(maxLen + 2, colDef.width || 12);
            }, 0);
            const scaleFactor = currentTotal < targetTotalA4Width ? targetTotalA4Width / currentTotal : 1;

            columns.forEach((colDef, i) => {
              const maxLen = colMaxLengths[i] || 8;
              const baseWidth = Math.max(maxLen + 2, colDef.width || 12);
              const finalWidth = Math.round(baseWidth * scaleFactor * 10) / 10;
              const col = sheet.getColumn(i + 1);
              col.width = finalWidth;
            });

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const aLink = document.createElement('a');
            aLink.href = url;
            aLink.download = fileName;
            document.body.appendChild(aLink);
            aLink.click();
            setTimeout(() => { document.body.removeChild(aLink); window.URL.revokeObjectURL(url); }, 250);

            triggerToast('Berhasil', `File ${fileName} berhasil diexport dengan format rapi dan pewarnaan part!`, 'success');
          } catch (err) {
            triggerToast('Gagal Export', err.message, 'error');
          }
        };

        const formatIndoDateTime = (dt) => {
          if (!dt) return '-';
          const d = (dt instanceof Date) ? dt : new Date(dt);
          if (isNaN(d.getTime())) return String(dt);
          const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ];
          const day = d.getDate();
          const month = months[d.getMonth()];
          const year = d.getFullYear();
          const hours = String(d.getHours()).padStart(2, '0');
          const mins = String(d.getMinutes()).padStart(2, '0');
          const secs = String(d.getSeconds()).padStart(2, '0');
          return `${day} ${month} ${year}, ${hours}:${mins}:${secs} WIB`;
        };

        const formatIndoDate = (dt) => {
          if (!dt) dt = new Date();
          const d = (dt instanceof Date) ? dt : new Date(dt);
          if (isNaN(d.getTime())) return String(dt);
          const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
          ];
          const day = d.getDate();
          const month = months[d.getMonth()];
          const year = d.getFullYear();
          return `${day} ${month} ${year}`;
        };

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

        // Close dropdown on click outside
        window.addEventListener('click', (e) => {
          if (trayDropdownRef.value && !trayDropdownRef.value.contains(e.target)) {
            isTrayDropdownOpen.value = false;
          }
          if (showPrintDocTypeDropdown.value) {
            const el = document.getElementById('printDocTypeDropdownContainer');
            if (el && !el.contains(e.target)) {
              showPrintDocTypeDropdown.value = false;
            }
          }
          if (isAssyDocDropdownOpen.value) {
            const elDoc = document.getElementById('assyDocDropdownContainer');
            if (elDoc && !elDoc.contains(e.target)) {
              isAssyDocDropdownOpen.value = false;
            }
          }
        });

        const prefetchBackgroundData = () => {
          const tasks = [
            async () => {
              if (!traysList.value || !traysList.value.length) {
                await loadTrays();
              }
            },
            async () => {
              if (typeof loadAssemblyResults === 'function' && (!assemblyResults.value || !assemblyResults.value.length)) {
                await loadAssemblyResults(true);
              }
            },
            async () => {
              if (typeof loadProductionHoldItems === 'function' && (!productionHoldList.value || !productionHoldList.value.length)) {
                await loadProductionHoldItems(true);
              }
            },
            async () => {
              if (typeof loadQCPendingItems === 'function' && (!qcPendingList.value || !qcPendingList.value.length)) {
                await loadQCPendingItems();
              }
            },
            async () => {
              if (typeof loadLaporan === 'function' && (!laporanList.value || !laporanList.value.length)) {
                await loadLaporan();
              }
            },
            async () => {
              if (typeof loadPendingVerifications === 'function' && (!adminPendingSetsTotalCount.value)) {
                await loadPendingVerifications();
              }
            },
            async () => {
              if (typeof loadDOList === 'function' && (!doList.value || !doList.value.length)) {
                await loadDOList();
              }
            },
            async () => {
              if (typeof loadTestingPoints === 'function' && (!testingPointsList.value || !testingPointsList.value.length)) {
                await loadTestingPoints();
              }
            }
          ];

          let delay = 350;
          tasks.forEach(task => {
            setTimeout(async () => {
              try {
                await task();
              } catch (_) { }
            }, delay);
            delay += 400;
          });
        };

        onMounted(() => {
          const token = localStorage.getItem('token');
          if (!token) {
            const currentPath = window.location.pathname;
            const redirectParam = (currentPath && currentPath !== '/' && currentPath !== '/login') ? '?redirect=' + encodeURIComponent(currentPath + window.location.hash) : '';
            window.location.replace('/login' + redirectParam);
            return;
          }

          liveClockTimer = setInterval(() => {
            liveClock.value = new Date();
          }, 1000);

          fetchDatabaseStatus();
          loadOperatorsList();
          const route = getInitialRoute();
          switchTab(route.tab, route.subTab);

          // Auto-buka Single Retest jika ada parameter ?retest=X&set=Y pada URL saat awal muat (hanya jika belum diuji)
          if (route.tab === 'assy_testing' && route.queryParams && route.queryParams.retest) {
            setTimeout(() => {
              if (traySlots.value && traySlots.value.length) {
                const targetSet = Number(route.queryParams.set || 1);
                const slot = traySlots.value.find(s => s && Number(s.set_number) === targetSet) || traySlots.value[0];
                if (slot && !isSlotTested(slot)) {
                  startSingleRetest(slot, Number(route.queryParams.retest), 'view2_table');
                }
              }
            }, 600);
          }
        });

        onUnmounted(() => {
          if (liveClockTimer) clearInterval(liveClockTimer);
          if (assemblyPollTimer) clearInterval(assemblyPollTimer);
        });

        // Formatting & Badge Helpers
        const isPartDefective = (slot, partType) => {
          if (!slot) return false;

          // 1. Jika status aktual atau status unit adalah Good atau Pending, part tidak defective
          const actualStatus = (typeof getTravelerItemActualStatus === 'function' ? getTravelerItemActualStatus(slot) : (slot.status || '')).toLowerCase();
          if (actualStatus === 'good' || actualStatus === 'pending') return false;

          const rawStatus = String(slot.status || '').toLowerCase();
          if (rawStatus === 'good' || rawStatus === 'pending') return false;

          // 2. Status administratif / transisi (Declined, Dirakit Ulang, RU, RETURNED) bukan berarti part rusak
          // kecuali jika ada daftar defective_parts spesifik yang dicatat
          if (['declined', 'dirakit ulang', 'ru', 'returned'].includes(rawStatus) && (!slot.defective_parts || slot.defective_parts.length === 0)) {
            return false;
          }

          const target = String(partType).trim();
          let defs = slot.defective_parts;
          if (!defs || defs === '' || defs === 'null' || defs === 'undefined') {
            // Hanya anggap defective jika status eksplisit adalah Scrap, Hold, atau Rework
            if (['scrap', 'hold', 'rework'].includes(rawStatus)) {
              return true;
            }
            return false;
          }

          let list = [];
          if (Array.isArray(defs)) {
            list = defs;
          } else if (typeof defs === 'string') {
            const raw = defs.trim();
            if (raw.startsWith('[') && raw.endsWith(']')) {
              try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) list = parsed;
              } catch (_) {
                list = raw.replace(/[\[\]'"]/g, '').split(/[\s,]+/);
              }
            } else {
              list = raw.split(/[\s,]+/);
            }
          }

          return list.some(item => {
            if (!item) return false;
            const s = String(item).trim().toLowerCase();
            const digits = s.replace(/\D/g, '');
            return digits === target || s === target || s === 'part' + target || s === 'part ' + target || s === 'p' + target || s === 'p ' + target;
          });
        };

        const getDefectivePartClass = (slot, partType) => {
          if (!isPartDefective(slot, partType)) {
            if (slot && slot.status && slot.status.toLowerCase() !== 'good') {
              return 'text-slate-700 bg-white/90 border border-slate-200';
            }
            return 'text-slate-600 bg-slate-50 border-slate-100';
          }
          const s = (slot && slot.status ? slot.status : '').toLowerCase();
          if (s === 'hold') return 'border-2 border-amber-500 bg-amber-100/90 text-amber-950 font-bold shadow-xs';
          if (s === 'scrap') return 'border-2 border-red-500 bg-red-100/90 text-red-950 font-bold shadow-xs';
          if (s === 'rework') return 'border-2 border-purple-500 bg-purple-100/90 text-purple-950 font-bold shadow-xs';
          return 'border-2 border-red-500 bg-red-100/90 text-red-950 font-bold shadow-xs';
        };

        const getDefectiveLabelClass = (slot, partType) => {
          if (!isPartDefective(slot, partType)) return 'text-slate-400';
          const s = (slot && slot.status ? slot.status : '').toLowerCase();
          if (s === 'hold') return 'text-amber-800 font-extrabold';
          if (s === 'scrap') return 'text-red-800 font-extrabold';
          if (s === 'rework') return 'text-purple-800 font-extrabold';
          return 'text-red-800 font-extrabold';
        };

        const getDefectiveBadgeClass = (slot) => {
          const s = (slot && slot.status ? slot.status : '').toLowerCase();
          if (s === 'hold') return 'bg-amber-500 text-white';
          if (s === 'scrap') return 'bg-red-600 text-white';
          if (s === 'rework') return 'bg-purple-600 text-white';
          return 'bg-red-600 text-white';
        };

        const getDefectiveTableCellClass = (item, partType) => {
          if (!item) return 'text-slate-900';
          if (!isPartDefective(item, partType)) {
            return partType === '15' ? 'font-bold text-slate-900' : 'text-slate-800';
          }
          const actual = (typeof getTravelerItemActualStatus === 'function' ? getTravelerItemActualStatus(item) : (item && item.status ? item.status : '')).toLowerCase();
          if (actual === 'good') {
            return partType === '15' ? 'font-bold text-slate-900' : 'text-slate-800';
          }
          const s = (item && item.status ? item.status : '').toLowerCase();
          if (s === 'hold') return 'font-black text-amber-600';
          if (s === 'scrap') return 'font-black text-red-600';
          if (s === 'rework') return 'font-black text-purple-600';
          return 'font-black text-red-600';
        };

        const getItemCardClass = (slot, index) => {
          if (slot.status === 'RETURNED') return 'bg-amber-50/40 border-dashed border-amber-300 ring-1 ring-amber-200/70 opacity-80';
          if (slot.status === 'Good') return 'bg-white border-emerald-300 ring-1 ring-emerald-200';
          if (slot.status === 'Hold') return 'bg-amber-50/70 border-amber-300 ring-1 ring-amber-200';
          if (slot.status === 'Scrap') return 'bg-red-50/70 border-red-300 ring-1 ring-red-200';
          if (slot.status === 'Rework') return 'bg-purple-50/70 border-purple-300 ring-1 ring-purple-200';
          if (slot.started_at) return 'bg-amber-50/40 border-amber-400 ring-2 ring-amber-300 shadow-md animate-pulse-border';
          if (index === firstUnfinishedIndex.value) return 'bg-white border-red-500 ring-2 ring-red-200 shadow-md';
          return 'bg-slate-50/80 border-slate-200 opacity-80';
        };

        const getStatusBadgeClass = (status) => {
          if (!status || status === '-') return 'bg-slate-100 text-slate-500 border-slate-200';
          const s = status.toLowerCase();
          if (s === 'ru' || s === 'dirakit ulang') return 'bg-indigo-100 text-indigo-900 border-indigo-300';
          if (s === 'good (ru)') return 'bg-emerald-100 text-emerald-900 border-emerald-400';
          if (s === 'hold (ru)') return 'bg-rose-100 text-rose-900 border-rose-300';
          if (s === 'pending' || s.includes('menunggu') || s.includes('belum')) return 'bg-amber-50 text-amber-800 border-amber-300';
          if (s === 'returned' || s === 'dikembalikan') return 'bg-amber-100 text-amber-800 border-amber-300';
          if (s === 'good' || s === 'tersedia') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
          if (s === 'supplied') return 'bg-blue-50 text-blue-700 border-blue-200';
          if (s === 'hold') return 'bg-amber-50 text-amber-700 border-amber-200';
          if (s === 'scrap') return 'bg-red-50 text-red-700 border-red-200';
          if (s === 'rework') return 'bg-purple-50 text-purple-700 border-purple-200';
          if (s === 'decline' || s === 'declined' || s === 'pending verif admin') return 'bg-rose-50 text-rose-700 border-rose-200';
          return 'bg-slate-100 text-slate-700 border-slate-200';
        };

        const formatDateTime = (dtStr, refStartStr = null) => {
          if (!dtStr) return '-';
          try {
            let effective = dtStr;
            if (refStartStr) {
              effective = getEffectiveFinishedAt(refStartStr, dtStr);
            }
            const d = parseWIBDate(effective);
            if (!d || isNaN(d.getTime())) return String(dtStr);
            const datePart = d.toLocaleDateString('id-ID', {
              timeZone: 'Asia/Jakarta',
              day: 'numeric', month: 'short', year: 'numeric'
            });
            const timePart = new Intl.DateTimeFormat('id-ID', {
              timeZone: 'Asia/Jakarta',
              hour: '2-digit', minute: '2-digit',
              hour12: false
            }).format(d).replace(':', '.');
            return `${datePart}, ${timePart}`;
          } catch (_) {
            return dtStr;
          }
        };


        // ================= FUNCTIONAL TESTING STATE & METHODS (ASSY & QC) =================

        // ================= TESTING POINTS & SINGLE RETEST STATE =================
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

        const defaultQCTestItems = [
          { test_num: 1, name: 'Pemeriksaan Visual & Label Mutu', description: 'Kondisi fisik, kelengkapan label, dan kebersihan', status: 'GOOD' },
          { test_num: 2, name: 'Pengujian Fungsi Elektronik & Power', description: 'Verifikasi stabilitas arus daya dan sirkuit', status: 'GOOD' },
          { test_num: 3, name: 'Pengujian Kinerja Sensor & Respon', description: 'Akurasi sensor dan respon real-time', status: 'GOOD' },
          { test_num: 4, name: 'Uji Ketahanan & Stress Test', description: 'Ketahanan operasional beban tinggi', status: 'GOOD' },
          { test_num: 5, name: 'Kalibrasi Akhir Standar Mutu QC', description: 'Verifikasi deviasi toleransi standar mutu akhir', status: 'GOOD' }
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

        const getSlotDraftProgress = (slot) => {
          if (!slot || isSlotTested(slot)) return null;
          const trayId = slot.tray_id || (currentTray.value ? currentTray.value.id : null);
          const key = `${trayId}_${slot.set_number}`;
          return assyDraftProgress.value[key] || null;
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
              step: assyTestStep.value,
              totalSteps: assyTotalSteps.value,
              checklist: JSON.parse(JSON.stringify(form.checklist || [])),
              notes: form.notes || '',
              savedAt: getNowLocalDateTimeStr()
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
          } else if (slot.assy_test_checklist) {
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
              triggerToast('Uji Fungsional Selesai', res.message || 'Hasil uji berhasil disimpan.', res.status === 'Good' ? 'success' : 'warn');
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

        const activeAssyQCDocSlot = ref(null);

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
          return (testedGoodSlots.value || []).filter(s => s && s.assy_test_status === 'Good').length;
        });

        const countTestedHoldInTray = computed(() => {
          return (testedGoodSlots.value || []).filter(s => s && (s.assy_test_status === 'Hold' || s.qc_status === 'HOLD_ASSY') && s.assy_test_status !== 'RU' && s.qc_status !== 'RU').length;
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

        // ==========================================
        // 13. BARANG PRODUKSI (HOLD ASSEMBLED UNITS)
        // ==========================================
        const productionHoldList = ref([]);
        const productionProductsList = ref(['Nanggala P9']);
        const selectedProductionProduct = ref('ALL');
        const productionSourceFilter = ref('ALL');
        const productionSearchQuery = ref('');
        const productionViewMode = ref('table'); // 'table' (default, requested by user), 'cards', or 'grouped'
        const functionalHoldViewMode = ref('table'); // 'table' (default view as requested by user), or 'cards'
        const isLoadingProductionHold = ref(false);
        const assemblyResults = ref([]);
        const isLoadingAssemblyResults = ref(false);
        const assemblyResultsStatusFilter = ref('ALL');
        const selectedProductionOperator = ref('ALL');
        const selectedProductionDocAssy = ref('ALL');

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
          // Item perakitan resmi yang sudah selesai dirakit WAJIB memiliki record perakitan di DB atau finished_at
          if (!item.finished_at && !item.id && !item.assembly_id) return false;
          // Jika status kosong atau bernilai null/undefined/RETURNED tanpa adanya record perakitan nyata yang selesai
          const st = String(item.status || item.assembly_status || '').trim();
          if (!st || st.toUpperCase() === 'RETURNED') return false;
          // Khusus dokumen Perakitan Ulang (assy_rework): jika belum pernah ada proses perakitan ulang (finished_at kosong) maka prosesnya belum dilakuin
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
          // Jika item sudah disetujui / sedang dalam proses Rakit Ulang
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
          let raw = item.started_at;
          if (!raw && item.finished_at) {
            raw = item.finished_at;
          }
          if (!raw) {
            return '-';
          }
          const t = formatTimeOnly(raw);
          return t && t !== '-' ? t : '-';
        };

        const getItemFinishTime = (item) => {
          if (!item) return '-';
          if (!isTravelerItemAssembled(item) || !item.finished_at) {
            return '-';
          }
          let raw = item.finished_at;
          const t = formatTimeOnly(raw, item.started_at);
          return t && t !== '-' ? t : '-';
        };

        const previousPendingAssemblyItemIds = ref(new Set());
        const isAssemblyResultsInitialLoaded = ref(false);

        const loadAssemblyResults = async (isSilent = false) => {
          if (!isSilent && (!assemblyResults.value || !assemblyResults.value.length)) {
            isLoadingAssemblyResults.value = true;
          }
          try {
            const res = await fetchAPI('/production/assembly-results');
            if (res.ok && res.data) {
              const rawTrays = Array.isArray(res.data) ? res.data : (res.data?.trays || []);
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

              // Deteksi perubahan status setelah verifikasi admin (tanpa perlu refresh halaman)
              const currentPendingIds = new Set();
              (assemblyResults.value || []).forEach(t => {
                (t.items || []).forEach(it => {
                  const aid = it.assembly_id || it.id;
                  if (isItemPendingAdmin(it)) {
                    currentPendingIds.add(aid);
                  } else if (isAssemblyResultsInitialLoaded.value && previousPendingAssemblyItemIds.value.has(aid)) {
                    const newStatus = getAssemblyStageStatus(it);
                    triggerToast('Verifikasi Selesai', `Set #${it.set_number} (${t.tray_code}) telah diverifikasi oleh Admin. Status langsung diperbarui ke ${newStatus}.`, 'success');
                  }
                });
              });
              previousPendingAssemblyItemIds.value = currentPendingIds;
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
          const notifCount = unresolvedHoldScrap; // Auto-decrement / clear when process is DONE
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

        // Modal Detail Hasil Perakitan (Murni Perakitan)
        const showAssemblyDetailModal = ref(false);
        const selectedAssemblyItem = ref(null);
        const selectedAssemblyTray = ref(null);

        // Alias for backward compatibility
        const showHoldDetailModal = ref(false);
        const selectedHoldItem = ref(null);
        const selectedHoldTray = ref(null);



        // Helper: check if all problematic parts are already pending replacement or already replaced
        const isAllProblematicPartsReplaced = (item) => {
          if (!item) return false;
          if (isReassemblySet(item)) return false;
          // Jika sudah selesai didaftarkan ulang & disetujui admin, tidak lagi menampilkan siap daftar ulang
          if (item.verified_status === 'APPROVED_REREG' || item.is_reregistered) return false;
          if (item.latest_replacement_status === 'APPROVED') return true;
          if (item.rework_reason && item.rework_reason.includes('Ganti Part')) return true;
          return !!(item.replacement_pending || item.pending_action_type === 'PENDING_GANTI_PART');
        };

        const getHoldProceedButtonLabel = (item) => {
          if (!item) return 'Lanjut';
          // 1. Jika part bermasalah telah diganti -> Rakit Ulang
          if (isAllProblematicPartsReplaced(item)) {
            return 'Rakit Ulang';
          }
          // 2. Jika hold terjadi di TF2 (Quality Control / QC) -> Lanjut ke Penerimaan
          const isQC = item.hold_source === 'QC' || item.stage === 'QC' || item.process_stage === 'qc' ||
            ['HOLD_QC', 'REWORK_QC'].includes(item.qc_status) ||
            (Boolean(item.qc_failure_reasons) && !item.assy_failure_reasons);
          if (isQC) {
            return 'Lanjut ke Penerimaan';
          }
          // 3. Jika hold terjadi di TF1 (Tester Assembly / Test Functional 1) -> Lanjut ke TF2
          const isTF1 = item.hold_source === 'TESTER_ASSEMBLY' || item.stage === 'TF1' || item.process_stage === 'test_functional_1' ||
            ['Hold', 'Rework'].includes(item.assy_test_status) || ['HOLD_ASSY', 'REWORK_ASSY'].includes(item.qc_status) ||
            Boolean(item.assy_failure_reasons) || Boolean(item.assy_test_checklist);
          if (isTF1) {
            return 'Lanjut ke TF2';
          }
          // 4. Default: Hold pada proses Perakitan (Assembly) -> Lanjut TF1
          return 'Lanjut TF1';
        };

        const getHoldProceedButtonTitle = (item) => {
          if (!item) return '';
          if (isAllProblematicPartsReplaced(item)) {
            return 'Rakit Ulang (Part telah diganti)';
          }
          const label = getHoldProceedButtonLabel(item);
          return `${label} (Tanpa ganti part)`;
        };

        const getItemPendingLabel = (item) => {
          if (!item) return 'MENUNGGU VERIFIKASI ADMIN';
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

          // 0. Status final/disetujui oleh admin: PASTI BUKAN PENDING LAGI!
          const resolvedStatuses = ['WAITING_REASSEMBLY', 'APPROVED', 'APPROVED_QC', 'APPROVED_REREG', 'ACC', 'COMPLETED_PU', 'REJECTED', 'DECLINED'];
          if (resolvedStatuses.includes(vStat)) {
            item.re_register_pending = false;
            item.has_pending_reregister = false;
            item.replacement_pending = false;
            item.has_pending_replacement = false;
            item.pending_action_type = '';
            return false;
          }
          if (isReassemblySet(item)) return false;

          // 1. Explicit verified_status for pending admin verification:
          if (vStat === 'PENDING_REPLACEMENT' || vStat === 'PENDING_ADMIN_ASSY_REREG' || vStat === 'PENDING_ADMIN' || vStat === 'PENDING_ADMIN_QC') {
            return true;
          }

          // 2. Immediate local reactive flags & Authoritative DB status flags
          if (Boolean(item.replacement_pending) || Boolean(item.has_pending_replacement)) return true;
          if (Boolean(item.re_register_pending) || Boolean(item.has_pending_reregister)) return true;
          if (item.pending_action_type === 'PENDING_GANTI_PART' || item.pending_action_type === 'PENDING_DAFTAR_ULANG') return true;

          // 3. Fallbacks
          if (Boolean(item.is_reregistered) && !resolvedStatuses.includes(vStat)) {
            return true;
          }
          if (Boolean(item.has_pending_request) || Boolean(item.is_pending_verif)) {
            if (!resolvedStatuses.includes(vStat)) {
              return true;
            }
          }
          return false;
        };

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

        // Aksi Pindahkan ke Gudang
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
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
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

        // Sub-Modal 1: Daftar Ulang 1-Set Utuh (New Flow: Perakitan Ulang)
        const showReregisterSubModal = ref(false);
        const reregisterTargetStatus = ref(''); // Legacy, not used in new flow
        const reregOperator = ref(''); // NEW: operator yang akan merakit ulang
        const reregisterReason = ref('');
        const isSubmittingReregisterSetAction = ref(false);

        const availableReregisterStatuses = computed(() => {
          // Legacy computed, kept for compatibility
          const cur = String(selectedHoldItem.value?.assembly_status || selectedHoldItem.value?.qc_status || 'Hold');
          const all = ['Good', 'Hold', 'Scrap'];
          return all.filter(s => s.toLowerCase() !== cur.toLowerCase());
        });

        const openReregisterSubModal = () => {
          const currentItem = selectedHoldItem.value || selectedAssemblyItem.value;
          if (!currentItem) return;

          // GUARD KETAT: Jika item berasal dari Pengujian (Test Functional 1 atau 2), JANGAN PERNAH masuk ke Perakitan Ulang!
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
          // Pre-fill with current operator
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
              // Realtime immediate local mutation for open modal and background grid
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
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
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

        // Sub-Modal 2: Ganti Part Rusak dengan Stok Master (Support 3 Dropdown untuk Masing-Masing Part)
        const showReplacePartModal = ref(false);
        const replaceOldStatus = ref('Hold');
        const replaceReason = ref('');
        const isSubmittingReplacePartAction = ref(false);
        const showAllReplaceParts = ref(false);

        // State 3 Dropdown Part Pengganti (Part 15, Part 1, Part 12)
        const replaceSelections = ref({ '15': '', '1': '', '12': '' });
        const replaceDropdownOpen = ref({ '15': false, '1': false, '12': false });
        const replaceSearchQueries = ref({ '15': '', '1': '', '12': '' });
        const availableReplacePartsByType = ref({ '15': [], '1': [], '12': [] });
        const isLoadingPartsByType = ref({ '15': false, '1': false, '12': false });

        // Backward-compat aliases
        const replacePartType = ref('15');
        const replaceNewSN = computed({
          get: () => replaceSelections.value['15'] || replaceSelections.value['1'] || replaceSelections.value['12'] || '',
          set: (val) => { replaceSelections.value['15'] = val; }
        });
        const availableReplaceParts = computed(() => availableReplacePartsByType.value[replacePartType.value] || []);
        const isLoadingReplaceParts = computed(() => isLoadingPartsByType.value[replacePartType.value] || false);
        const isReplacePartDropdownOpen = ref(false);
        const replacePartSearchQuery = ref('');

        // Deteksi part yang bermasalah dari data rakitan yang dipilih
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
          await Promise.all(types.map(async (t) => {
            isLoadingPartsByType.value[t] = true;
            try {
              const res = await fetchAPI(`/master/available?type=${encodeURIComponent(t)}&limit=100`);
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
                replacements: reps,
                part_type: reps[0] ? reps[0].part_type : '',
                new_sn: reps[0] ? reps[0].new_sn : '',
                old_part_status: replaceOldStatus.value,
                reason: replaceReason.value
              })
            });
            if (res.ok) {
              triggerToast('Berhasil', res.message || `${reps.length} part berhasil diganti! Menunggu verifikasi Admin.`, 'success');
              // Realtime immediate local item mutation for open modal and background grid
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
              if (typeof loadProductionHoldItems === 'function') await loadProductionHoldItems();
              if (typeof loadPendingVerifications === 'function') await loadPendingVerifications();
            } else {
              triggerToast('Gagal', res.error || 'Gagal melakukan penggantian part', 'error');
            }
          } catch (err) {
            triggerToast('Error', err.message || 'Terjadi kesalahan sistem', 'error');
          } finally {
            isSubmittingReplacePartAction.value = false;
          }
        };

        // --- PERAKITAN ULANG (RU) MODULE ---
        const pendingReassemblyList = ref([]);
        const selectedReassemblyIds = ref([]);
        const selectedReassemblyOperator = ref('');
        const isLoadingPendingReassembly = ref(false);
        const isSubmittingReassemblyDispatch = ref(false);

        const loadPendingReassembly = async () => {
          isLoadingPendingReassembly.value = true;
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

        const previousPendingHoldItemMap = ref(new Map());
        const isHoldItemsInitialLoaded = ref(false);

        const loadProductionHoldItems = async (isSilent = false) => {
          if (!isSilent && (!productionHoldList.value || !productionHoldList.value.length)) {
            isLoadingProductionHold.value = true;
          }
          try {
            const res = await fetchAPI('/production/hold-items');
            if (res.ok && res.data) {
              const newItems = Array.isArray(res.data) ? res.data : [];
              productionHoldList.value = newItems;

              // Deteksi card / transaksi yang diverifikasi admin dan/atau hilang dari log kendala
              const currentPendingMap = new Map();
              const newItemIds = new Set(newItems.map(i => i.assembly_id || i.id));

              newItems.forEach(it => {
                const aid = it.assembly_id || it.id;
                const vStat = String(it.verified_status || '').trim().toUpperCase();
                const isPending = isItemPendingAdmin(it) || vStat.startsWith('PENDING_ADMIN') || vStat === 'PENDING_REPLACEMENT';
                if (isPending) {
                  currentPendingMap.set(aid, { set_number: it.set_number, tray_code: it.tray_code });
                } else if (isHoldItemsInitialLoaded.value && previousPendingHoldItemMap.value.has(aid)) {
                  const prev = previousPendingHoldItemMap.value.get(aid);
                  previousPendingHoldItemMap.value.delete(aid);
                  triggerToast('Verifikasi Selesai', `Unit Set #${prev.set_number} (${prev.tray_code}) telah selesai diverifikasi oleh Admin.`, 'success');
                }
              });

              if (isHoldItemsInitialLoaded.value) {
                previousPendingHoldItemMap.value.forEach((val, aid) => {
                  if (!newItemIds.has(aid)) {
                    triggerToast('Verifikasi Selesai', `Unit Set #${val.set_number} (${val.tray_code}) telah selesai diverifikasi oleh Admin dan dialihkan dari daftar kendala.`, 'info');
                  }
                });
              }
              previousPendingHoldItemMap.value = currentPendingMap;
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
          // Hanya jika admin SUDAH MENYETUJUI (bukan saat masih pending verifikasi admin):
          if (['WAITING_REASSEMBLY', 'COMPLETED_PU', 'APPROVED_REREG', 'APPROVED', 'APPROVED_QC'].includes(vStat)) return true;
          if (item.latest_replacement_status === 'APPROVED') return true;
          if (item.rework_reason && item.rework_reason.includes('Ganti Part') && ['WAITING_REASSEMBLY', 'COMPLETED_PU', 'APPROVED'].includes(vStat)) return true;
          return false;
        };

        const baseProductionHoldList = computed(() => {
          let list = productionHoldList.value || [];
          // Cut-Off Tahapan: Unit yang gagal di perakitan murni (Hold Perakitan) tidak boleh masuk ke riwayat/antrean Test Functional
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

        // 3. Warehouse QC Receipt Verification
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

        // ================= WAREHOUSE RECEIPT TRAY SUMMARY & BATCH VERIFICATION =================
        const selectedReceiptTrayCode = ref(null);
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

        // Apakah dokumen TF 2 (WH/QC) sudah bisa dicetak?
        // Kondisi: minimal ada 1 unit yang sudah selesai di-test QC (ada qc_doc_number / qc_tested_at / qc_status GOOD).
        // Ini TERPISAH dari penerimaan gudang (warehouse_receipt) yang ada di tab Receipts.
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

        // 4. Multi-Category Reporting
        const reportSubTab = ref('wo');
        const reportFilters = ref({
          wo: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
          assembly: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
          assy_test: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
          qc_test: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' },
          warehouse_receipt: { startDate: '', endDate: '', operator: '', docNumber: '', searchQuery: '', status: '', sortBy: 'date', sortOrder: 'desc' }
        });

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

        const setReportSubTab = (sub, isFromPopState = false) => {
          if (typeof selectedReportDoc !== 'undefined' && selectedReportDoc) {
            selectedReportDoc.value = null;
          }
          navigateTo('report', sub, isFromPopState);
        };

        const assyTestReports = ref([]);
        const qcTestReports = ref([]);
        const warehouseReceiptReports = ref([]);

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
          // Fallback to laporanList if assemblyResults has no items yet
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
              const isPU = it.status === 'Dirakit Ulang' || it.qc_status === 'RU' || (typeof isReassemblySet === 'function' && isReassemblySet(it));
              const assyDoc = (isPU ? getFormattedDocCode(it.tray_code, 'assy_rework') : ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy'))).toLowerCase();
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

        // Riwayat Pembuatan Tray (Work Order)
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
              const isFinished = (t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.status === 'Good' || t.status === 'SELESAI';
              if (targetStatus === 'SELESAI' || targetStatus === 'COMPLETED') return isFinished;
              if (targetStatus === 'AKTIF' || targetStatus === 'ACTIVE' || targetStatus === 'IN_PROGRESS') return !isFinished;
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

        const currentReportCounts = computed(() => {
          const sub = reportSubTab.value;
          if (sub === 'wo') {
            const trays = filteredWOReport.value || [];
            let finished = 0;
            let active = 0;
            trays.forEach(t => {
              if (t.finished_count >= t.total_sets && t.total_sets > 0) finished++;
              else active++;
            });
            return { total: trays.length, good: finished, hold: active, other: 0 };
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

        // Opsi Mode Tampilan Laporan / History: 'document' (Per Dokumen) vs 'detailed' (Rinci Per Unit)
        const reportViewMode = ref('document');
        const selectedReportDoc = ref(null);

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

          // DEDUPLIKASI: Pastikan tidak ada data unit yang dobel/ganda di detail modal
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
          // Urutkan rapi berdasarkan nomor set: 1, 2, 3, 4, 5...
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

          // Sinkronisasi data unit dengan tray_detail
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
                    clean.push(matched ? { ...item, ...matched } : item);
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
          const docGroup = {
            doc_code: getFormattedDocCode(trayCode, 'wo'),
            raw_tray_code: trayCode,
            origin_doc: it.source_document_mo || 'MO',
            operator: getOperatorFullName(it.pic_warehouse) || it.pic_warehouse || getOperatorFullName(it.assigned_operator) || it.assigned_operator || '-',
            timestamp: it.tray_created_at || it.created_at,
            status: it.status || it.session_status || 'SELESAI',
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

        const currentReportList = computed(() => {
          const sub = reportSubTab.value;
          if (sub === 'wo') return filteredWOReport.value || [];
          if (sub === 'assembly') return filteredAssemblyReport.value || [];
          if (sub === 'assy_test') return filteredAssyTestReport.value || [];
          if (sub === 'qc_test') return filteredQCTestReport.value || [];
          if (sub === 'warehouse_receipt') return filteredWarehouseReport.value || [];
          return [];
        });

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
              // SATUKAN DOKUMEN PERAKITAN PER TRAY / NO DOKUMEN ASSY
              // Jangan dipisah berdasarkan assy_doc_number (yang berisi nomor uji TF1 Assy/QC)
              docCode = (it.tray_code && it.tray_code.toUpperCase().startsWith('WH/ASSY'))
                ? it.tray_code
                : ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY'))
                  ? it.assy_doc_number
                  : getFormattedDocCode(it.tray_code, 'assy'));
              originDoc = getFormattedDocCode(it.tray_code, 'wo');
              operator = getOperatorFullName(it.pic_assembly) || it.pic_assembly || '-';
              timestamp = it.finished_at || it.tray_created_at;
              status = it.assembly_status || it.status || 'Good';
            } else if (sub === 'assy_test') {
              docCode = (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code, 'tf1');
              originDoc = (it.assy_doc_number && it.assy_doc_number !== '-') ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy');
              operator = getOperatorFullName(it.tester_username) || it.tester_full_name || it.tester_username || '-';
              timestamp = it.created_at;
              status = it.status || 'OK';
            } else if (sub === 'qc_test') {
              docCode = (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code, 'qc');
              originDoc = (it.tf1_doc_number && it.tf1_doc_number !== '-') ? it.tf1_doc_number : getFormattedDocCode(it.tray_code, 'tf1');
              operator = getOperatorFullName(it.tester_username) || it.tester_full_name || it.tester_username || '-';
              timestamp = it.created_at;
              status = it.status || 'Good';
            } else if (sub === 'warehouse_receipt') {
              docCode = (it.warehouse_doc_number && it.warehouse_doc_number !== '-') ? it.warehouse_doc_number : getFormattedDocCode(it.tray_code, 'wh_gr');
              originDoc = getFormattedDocCode(it.tray_code, 'wo');
              operator = getOperatorFullName(it.verified_by_user) || it.verified_by_user || it.warehouse_receipt_by || '-';
              timestamp = it.verified_at || it.warehouse_receipt_at || it.created_at;
              status = it.verified_status || it.status || 'Verified';
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

            // Cek apakah unit/set ini sudah ada di dalam dokumen ini (mencegah data ganda/dobel)
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

              const itemSt = (sub === 'assembly') ? getAssemblyStageStatus(it).toUpperCase() : String(status || '').toUpperCase();
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
          });

          // Urutkan rapi unit di setiap dokumen berdasarkan nomor set
          groupsMap.forEach(g => {
            g.items.sort((a, b) => (Number(a.set_number) || 0) - (Number(b.set_number) || 0));
          });

          return Array.from(groupsMap.values());
        });

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
              rawData = (filteredWOReport.value || []).map((t, idx) => [
                idx + 1,
                getFormattedDocCode(t.tray_code, 'wo') + (t.set_number ? ' Set #' + t.set_number : ''),
                t.source_document_mo || '-',
                t.part15_sn || t.p9_sn || '-',
                t.part1_sn || '-',
                t.part12_sn || '-',
                getOperatorFullName(t.pic_warehouse) || t.pic_warehouse || getOperatorFullName(t.assigned_operator) || t.assigned_operator || '-',
                formatDateTime(t.created_at || t.tray_created_at || t.started_at),
                ((t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.status === 'Good' || t.status === 'SELESAI') ? 'SELESAI' : 'TERSUPPLY',
                t.reason || (((t.finished_count >= t.total_sets && t.total_sets > 0) || t.finished_at || t.status === 'Good' || t.status === 'SELESAI') ? 'Pengerjaan Selesai' : 'Tray Tersupply ke Perakitan')
              ]);
            } else {
              filename = `Laporan_Riwayat_Tray_WO_${getTodayDateStr()}.xlsx`;
              title = 'LAPORAN RIWAYAT PEMBUATAN TRAY & WORK ORDER GUDANG';
              columns = [
                { header: 'No', width: 6 },
                { header: 'No Dokumen WO / Tray', width: 20 },
                { header: 'Dokumen Asal (MO)', width: 18 },
                { header: 'Waktu Supply', width: 20 },
                { header: 'Petugas Gudang', width: 22 },
                { header: 'Operator Ditugaskan', width: 22 },
                { header: 'Total Set', width: 14 },
                { header: 'Selesai', width: 14 },
                { header: 'Status Tray', width: 16 }
              ];
              rawData = (filteredWOReport.value || []).map((t, idx) => [
                idx + 1,
                getFormattedDocCode(t.tray_code, 'wo'),
                t.source_document_mo || 'MO',
                formatDateTime(t.created_at),
                getOperatorFullName(t.pic_warehouse) || t.pic_warehouse || '-',
                getOperatorFullName(t.assigned_operator) || t.assigned_operator || 'Semua Operator',
                t.total_sets || 0,
                t.finished_count || 0,
                (t.finished_count >= t.total_sets && t.total_sets > 0 ? 'SELESAI' : (t.session_status || 'AKTIF'))
              ]);
            }
          } else if (sub === 'assembly') {
            filename = `Laporan_Hasil_Perakitan_${getTodayDateStr()}.xlsx`;
            title = 'LAPORAN HASIL PERAKITAN UNIT TRACEABILITY';
            columns = [
              { header: 'No', width: 6 },
              { header: 'No Dokumen Perakitan', width: 22 },
              { header: 'Dokumen Asal (WO / Tray)', width: 20 },
              { header: 'SN P9 Part 15', width: 20 },
              { header: 'PN P9 Part 1', width: 16 },
              { header: 'PN P9 Part 12', width: 16 },
              { header: 'Operator Perakitan', width: 22 },
              { header: 'Waktu Perakitan', width: 20 },
              { header: 'Status', width: 14 },
              { header: 'Keterangan / Alasan', width: 28 }
            ];
            rawData = (filteredAssemblyReport.value || []).map((it, idx) => [
              idx + 1,
              ((it.assy_doc_number && it.assy_doc_number !== '-') ? it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy')) + (it.set_number ? ' Set #' + it.set_number : ''),
              getFormattedDocCode(it.tray_code, 'wo'),
              it.part15_sn || '-',
              it.part1_sn || '-',
              it.part12_sn || '-',
              getOperatorFullName(it.pic_assembly) || it.pic_assembly || '-',
              formatDateTime(it.finished_at || it.tray_created_at),
              (it.assembly_status || it.status || 'Good').toUpperCase(),
              it.assembly_reason || it.reason || '-'
            ]);
          } else if (sub === 'assy_test') {
            filename = `Laporan_Uji_Assy_${getTodayDateStr()}.xlsx`;
            title = 'LAPORAN HASIL UJI FUNGSIONAL PERAKITAN (15 TAHAP)';
            columns = [
              { header: 'No', width: 6 },
              { header: 'No Dokumen Uji (TF 1)', width: 22 },
              { header: 'Dokumen Asal (Assy)', width: 22 },
              { header: 'SN P9 Part 15', width: 22 },
              { header: 'PN P9 Part 1', width: 18 },
              { header: 'PN P9 Part 12', width: 18 },
              { header: 'Set', width: 8 },
              { header: 'Status Uji', width: 14 },
              { header: 'Alasan Gagal / Catatan', width: 32 },
              { header: 'Tester', width: 20 },
              { header: 'Waktu Uji', width: 20 }
            ];
            rawData = (filteredAssyTestReport.value || []).map((t, idx) => [
              idx + 1,
              (t.doc_number && t.doc_number !== '-') ? t.doc_number : getFormattedDocCode(t.tray_code, 'tf1'),
              (t.assy_doc_number && t.assy_doc_number !== '-') ? t.assy_doc_number : getFormattedDocCode(t.tray_code, 'assy'),
              t.part15_sn || '-',
              t.part1_sn || '-',
              t.part12_sn || '-',
              t.set_number || 1,
              (t.status || '').toUpperCase(),
              formatFailureReason(t.failure_reasons) || t.notes || '-',
              getOperatorFullName(t.tester_username) || t.tester_username || '-',
              formatDateTime(t.created_at)
            ]);
          } else if (sub === 'qc_test') {
            filename = `Laporan_Uji_QC_${getTodayDateStr()}.xlsx`;
            title = 'LAPORAN HASIL VERIFIKASI MUTU QUALITY CONTROL (WH/QC)';
            columns = [
              { header: 'No', width: 6 },
              { header: 'No Dokumen QC (TF 2)', width: 22 },
              { header: 'Dokumen Asal (TF 1)', width: 22 },
              { header: 'SN P9 Part 15', width: 22 },
              { header: 'PN P9 Part 1', width: 18 },
              { header: 'PN P9 Part 12', width: 18 },
              { header: 'Set', width: 8 },
              { header: 'Status Mutu', width: 16 },
              { header: 'Alasan Hold / Catatan', width: 32 },
              { header: 'Inspector QC', width: 20 },
              { header: 'Waktu Uji QC', width: 20 }
            ];
            rawData = (filteredQCTestReport.value || []).map((t, idx) => [
              idx + 1,
              (t.doc_number && t.doc_number !== '-') ? t.doc_number : getFormattedDocCode(t.tray_code, 'qc'),
              (t.tf1_doc_number && t.tf1_doc_number !== '-') ? t.tf1_doc_number : ((t.assy_doc_number && t.assy_doc_number !== '-') ? t.assy_doc_number : getFormattedDocCode(t.tray_code, 'tf1')),
              t.part15_sn || '-',
              t.part1_sn || '-',
              t.part12_sn || '-',
              t.set_number || 1,
              (t.status || '').toUpperCase(),
              formatFailureReason(t.failure_reasons) || t.notes || '-',
              getOperatorFullName(t.tester_username) || t.tester_username || '-',
              formatDateTime(t.created_at)
            ]);
          } else if (sub === 'warehouse_receipt') {
            filename = `Laporan_Penerimaan_Gudang_${getTodayDateStr()}.xlsx`;
            title = 'LAPORAN VERIFIKASI PENERIMAAN FISIK GUDANG DARI QC';
            columns = [
              { header: 'No', width: 6 },
              { header: 'Dokumen Asal (QC)', width: 22 },
              { header: 'SN P9 Part 15', width: 22 },
              { header: 'PN P9 Part 1', width: 18 },
              { header: 'PN P9 Part 12', width: 18 },
              { header: 'Status Penerimaan', width: 18 },
              { header: 'Petugas Gudang', width: 20 },
              { header: 'Waktu Penerimaan', width: 20 },
              { header: 'Alasan Penolakan / Decline', width: 32 }
            ];
            rawData = (filteredWarehouseReport.value || []).map((w, idx) => [
              idx + 1,
              (w.qc_doc_number && w.qc_doc_number !== '-') ? w.qc_doc_number : getFormattedDocCode(w.tray_code, 'qc'),
              w.part15_sn || '-',
              w.part1_sn || '-',
              w.part12_sn || '-',
              (w.warehouse_receipt_status || 'PENDING').toUpperCase(),
              getOperatorFullName(w.warehouse_receipt_by) || w.warehouse_receipt_by || '-',
              formatDateTime(w.warehouse_receipt_at),
              w.declined_reason || '-'
            ]);
          }

          if (typeof ExcelJS === 'undefined') {
            // SheetJS fallback
            const wsData = [columns.map(c => c.header), ...rawData];
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Laporan');
            XLSX.writeFile(wb, filename);
            triggerToast('Ekspor Berhasil', `File ${filename} berhasil diunduh.`, 'success');
            return;
          }

          const workbook = new ExcelJS.Workbook();
          workbook.creator = 'Sistem Assembly & Traceability NKRI';
          workbook.created = new Date();

          const sheet = workbook.addWorksheet('Laporan', {
            pageSetup: {
              paperSize: 9, // A4
              orientation: 'landscape',
              fitToPage: true,
              fitToWidth: 1,
              fitToHeight: 0,
              showGridLines: true,
              horizontalCentered: true,
              margins: { left: 0.3, right: 0.3, top: 0.4, bottom: 0.4, header: 0.2, footer: 0.2 }
            }
          });

          // 1. Title Banner
          const titleRow = sheet.addRow([title]);
          titleRow.font = { name: 'Arial', size: 14, bold: true, color: { argb: 'FF1E293B' } };
          titleRow.alignment = { horizontal: 'center', vertical: 'middle' };
          titleRow.height = 32;
          sheet.mergeCells(1, 1, 1, columns.length);

          // 2. Subtitle / Metadata
          const subTitleRow = sheet.addRow([`Waktu Unduh: ${formatDateTime(new Date())} | Total Data: ${rawData.length} Baris`]);
          subTitleRow.font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF64748B' } };
          subTitleRow.alignment = { horizontal: 'center', vertical: 'middle' };
          subTitleRow.height = 20;
          sheet.mergeCells(2, 1, 2, columns.length);

          const blankRow = sheet.addRow([]);
          blankRow.height = 10;

          // 3. Header Row
          const headerRow = sheet.addRow(columns.map(c => c.header));
          headerRow.height = 28;
          headerRow.eachCell((cell) => {
            cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1E293B' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = {
              top: { style: 'thin', color: { argb: 'FF0F172A' } },
              left: { style: 'thin', color: { argb: 'FF0F172A' } },
              bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
              right: { style: 'thin', color: { argb: 'FF0F172A' } }
            };
          });

          // 4. Data Rows with Visual Highlight & Auto-Fit Widths
          const colLengths = columns.map(c => c.header.length);

          rawData.forEach((rowValues) => {
            rowValues.forEach((val, cIdx) => {
              const strLen = String(val || '').length;
              if (strLen > colLengths[cIdx]) colLengths[cIdx] = strLen;
            });

            const row = sheet.addRow(rowValues);
            row.height = 22;

            // Check problematic status for visual highlighting
            const rowStr = rowValues.map(v => String(v || '').toUpperCase()).join(' ');
            const hasFailure = rowStr.includes('NOT_GOOD') || rowStr.includes('NOT GOOD') || rowStr.includes('HOLD') || rowStr.includes('SCRAP') || rowStr.includes('DECLINED') || rowStr.includes('REJECT');
            const isGood = rowStr.includes('GOOD') || rowStr.includes('ACC') || rowStr.includes('TERSEDIA') || rowStr.includes('VERIFIED');

            row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
              cell.font = { name: 'Arial', size: 9.5, color: { argb: 'FF1E293B' } };
              cell.border = {
                top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
              };

              // Alignment
              if (colNumber === 1 || colNumber === 7) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
              } else if (colNumber === 2 || colNumber === 3 || colNumber === 4 || colNumber === 5) {
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF0F172A' } };
              } else {
                cell.alignment = { horizontal: 'left', vertical: 'middle' };
              }

              // Visual Highlights for Not Good / Hold / Scrap / Declined / Reject
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

          // Apply Auto-fit Column Widths (clamped between 8 and 45)
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

        let globalBadgePollTimer = null;
        const startGlobalBadgePolling = () => {
          if (globalBadgePollTimer) clearInterval(globalBadgePollTimer);
          globalBadgePollTimer = setInterval(() => {
            if (showStatusDrillModal && showStatusDrillModal.value) return;
            if (showMasterPartTrackingModal && showMasterPartTrackingModal.value) return;
            if (inputDropdown && inputDropdown.value && inputDropdown.value.isOpen) return;
            if (localStorage.getItem('token') && !document.hidden) {
              loadProductionHoldItems();
              loadPendingVerifications();
              loadQCPendingItems();
              loadWarehouseReceiptReports();
              if (currentTab.value === 'production_hold' || currentTab.value === 'functional_hold') {
                if (currentTab.value === 'production_hold') loadAssemblyResults();
                loadProductionProducts();
              }
              if ((currentTab.value === 'assembly' || currentTab.value === 'assy_testing') && selectedTrayCode.value) {
                loadAssemblyTray();
              }
              if (currentTab.value === 'qc') {
                loadQCPendingItems();
              }
              if (currentTab.value === 'warehouse' || currentTab.value === 'traveler') {
                loadTrays();
              }
              if (currentTab.value === 'reassembly') {
                loadPendingReassembly();
              }
            }
          }, 12000);
        };

        // -------------------------------------------------------------------
        // UNIVERSAL BACK BUTTON & MULTI-LAYER NAVIGATION SYSTEM
        // Mobile / Tablet / Hardware & Gesture Back Button Handler
        // -------------------------------------------------------------------
        const getCurrentTabHash = () => {
          if (currentTab.value === 'users') {
            return '#users/' + (adminSubTab.value || 'verify_set');
          }
          if (currentTab.value === 'report') {
            return '#report/' + (reportSubTab.value || 'wo');
          }
          return '#' + (currentTab.value || defaultTab.value || 'assembly');
        };

        // 1. Unified Registry for All 28 Modals
        const registeredModalDefs = [
          { name: 'showDetailModal', get: () => showDetailModal.value, close: () => { showDetailModal.value = false; if (activeDetailSlot) activeDetailSlot.value = null; } },
          { name: 'activeModalIndex', get: () => activeModalIndex.value !== null, close: () => closeModal() },
          { name: 'showPauseModal', get: () => showPauseModal.value, close: () => { showPauseModal.value = false; } },
          { name: 'showSessionModal', get: () => !!(showSessionModal && showSessionModal.value), close: () => { if (showSessionModal) showSessionModal.value = false; } },
          { name: 'showInstructionGuideModal', get: () => showInstructionGuideModal.value, close: () => { showInstructionGuideModal.value = false; } },
          { name: 'showAssySaveProgressModal', get: () => showAssySaveProgressModal.value, close: () => { showAssySaveProgressModal.value = false; } },
          { name: 'showFirstNGPromptModal', get: () => showFirstNGPromptModal.value, close: () => { handleFirstNGChoice(false); } },
          { name: 'showSecondNGPromptModal', get: () => showSecondNGPromptModal.value, close: () => { showSecondNGPromptModal.value = false; } },
          { name: 'showAdminUnsavedModal', get: () => showAdminUnsavedModal.value, close: () => { cancelAdminUnsavedNavigation(); } },
          { name: 'showQCTestModal', get: () => showQCTestModal.value, close: () => { showQCTestModal.value = false; } },
          { name: 'showQCHoldReasonModal', get: () => showQCHoldReasonModal.value, close: () => { showQCHoldReasonModal.value = false; } },
          { name: 'showReregisterSetModal', get: () => showReregisterSetModal.value, close: () => { showReregisterSetModal.value = false; } },
          { name: 'showDeclineConfirmModal', get: () => !!(showDeclineConfirmModal && showDeclineConfirmModal.value), close: () => { if (showDeclineConfirmModal) showDeclineConfirmModal.value = false; } },
          { name: 'showDeclineReturnModal', get: () => !!(showDeclineReturnModal && showDeclineReturnModal.value), close: () => { if (showDeclineReturnModal) showDeclineReturnModal.value = false; } },
          { name: 'showPromptMOModal', get: () => showPromptMOModal.value, close: () => { showPromptMOModal.value = false; } },
          { name: 'showTrayResultPopup', get: () => showTrayResultPopup.value, close: () => { showTrayResultPopup.value = false; } },
          { name: 'showLabelPreviewModal', get: () => showLabelPreviewModal.value, close: () => { showLabelPreviewModal.value = false; } },
          { name: 'showBatchLabelModal', get: () => showBatchLabelModal.value, close: () => { showBatchLabelModal.value = false; } },
          { name: 'showTrayDetailModal', get: () => showTrayDetailModal.value, close: () => { showTrayDetailModal.value = false; } },
          { name: 'showGudangVerifyModal', get: () => !!(showGudangVerifyModal && showGudangVerifyModal.value), close: () => { if (showGudangVerifyModal) showGudangVerifyModal.value = false; } },
          { name: 'showQCWarehouseActualVerifyModal', get: () => showQCWarehouseActualVerifyModal.value, close: () => { showQCWarehouseActualVerifyModal.value = false; } },
          { name: 'showWarehouseQCModal', get: () => showWarehouseQCModal.value, close: () => { showWarehouseQCModal.value = false; } },
          { name: 'showReturnDetailModal', get: () => !!(showReturnDetailModal && showReturnDetailModal.value), close: () => { if (showReturnDetailModal) showReturnDetailModal.value = false; } },
          { name: 'showAdminVerifySetModal', get: () => !!(showAdminVerifySetModal && showAdminVerifySetModal.value), close: () => { if (showAdminVerifySetModal) showAdminVerifySetModal.value = false; } },
          { name: 'showAdminVerifyModal', get: () => !!(showAdminVerifyModal && showAdminVerifyModal.value), close: () => { if (showAdminVerifyModal) showAdminVerifyModal.value = false; } },
          { name: 'showResetDbModal', get: () => !!(showResetDbModal && showResetDbModal.value), close: () => { if (showResetDbModal) showResetDbModal.value = false; } },
          { name: 'showLaporanDetailModal', get: () => !!(showLaporanDetailModal && showLaporanDetailModal.value), close: () => { if (showLaporanDetailModal) showLaporanDetailModal.value = false; } },
          { name: 'showStatusDrillModal', get: () => !!(showStatusDrillModal && showStatusDrillModal.value), close: () => { if (showStatusDrillModal) showStatusDrillModal.value = false; } },
          { name: 'showQuickStatusModal', get: () => !!(showQuickStatusModal && showQuickStatusModal.value), close: () => { if (showQuickStatusModal) showQuickStatusModal.value = false; } },
          { name: 'showOverrideModal', get: () => !!(showOverrideModal && showOverrideModal.value), close: () => { if (showOverrideModal) showOverrideModal.value = false; } },
          { name: 'showAccOverrideModal', get: () => !!(showAccOverrideModal && showAccOverrideModal.value), close: () => { if (showAccOverrideModal) showAccOverrideModal.value = false; } },
          { name: 'showReregisterModal', get: () => !!(showReregisterModal && showReregisterModal.value), close: () => { if (showReregisterModal) showReregisterModal.value = false; } },
          { name: 'showMasterPartTrackingModal', get: () => !!(showMasterPartTrackingModal && showMasterPartTrackingModal.value), close: () => { if (showMasterPartTrackingModal) showMasterPartTrackingModal.value = false; } }
        ];

        // 2. Bidirectional State-History Synchronizers for All Modals
        registeredModalDefs.forEach(m => {
          watch(m.get, (isOpen, wasOpen) => {
            if (isOpen && !wasOpen) {
              _activeModalStack.push(m);
              try {
                history.pushState({ type: 'modal', name: m.name }, '', getCurrentTabHash());
              } catch (_) { }
            } else if (!isOpen && wasOpen) {
              const idx = _activeModalStack.findIndex(item => item.name === m.name);
              if (idx !== -1) {
                _activeModalStack.splice(idx, 1);
              }
              if (!isNavigatingBack) {
                programmaticBackCount++;
                try {
                  history.back();
                } catch (_) { }
              }
            }
          });
        });

        // 3. Bidirectional Synchronizer for Assembly Test Wizard (Clean Ghost Step Entries)
        watch(showAssyTestModal, (isOpen, wasOpen) => {
          if (!isOpen && wasOpen) {
            const cleanCount = assyTestHistoryDepth.value;
            if (cleanCount > 0 && !isNavigatingBack) {
              assyTestHistoryDepth.value = 0;
              programmaticBackCount += cleanCount;
              try {
                window.history.go(-cleanCount);
              } catch (_) { }
            }
          }
        });

        // 4. Bidirectional Synchronizer for QC Tray Detail View
        watch(selectedQCTrayCode, (newCode, oldCode) => {
          if (oldCode && !newCode) {
            if (!isNavigatingBack && history.state && history.state.type === 'qc_tray') {
              programmaticBackCount++;
              try {
                history.back();
              } catch (_) { }
            }
          }
        });

        // 5. Central Popstate Navigation Handler (Physical Back Button / Gesture)
        const handleMobileBackNavigation = (e) => {
          // 0. Konsumsi popstate yang dipicu secara programmatic (history.back() / history.go(-N))
          if (programmaticBackCount > 0) {
            programmaticBackCount--;
            return;
          }

          // 1. Dropdown & Micro-layer Popups
          if (inputDropdown.value && inputDropdown.value.isOpen) {
            inputDropdown.value.isOpen = false;
            return;
          }
          if (isTrayDropdownOpen.value) {
            isTrayDropdownOpen.value = false;
            return;
          }
          if (isMobileMenuOpen.value) {
            isMobileMenuOpen.value = false;
            return;
          }

          // 2. Active Modals Stack (LIFO: Tutup modal teratas terlebih dahulu)
          if (_activeModalStack.length > 0) {
            const topModal = _activeModalStack.pop();
            if (topModal && topModal.close) {
              isNavigatingBack = true;
              try {
                topModal.close();
              } finally {
                isNavigatingBack = false;
              }
            }
            return;
          }

          // 3. Step Wizard Test Assembly
          if (showAssyTestModal.value) {
            if (isSingleRetestMode.value) {
              cancelSingleRetest();
              if (assyTestHistoryDepth.value > 0) assyTestHistoryDepth.value--;
              return;
            }
            if (assyTestStep.value >= assyTotalSteps.value) {
              assyTestStep.value = assyTotalSteps.value - 1;
              if (assyTestHistoryDepth.value > 0) assyTestHistoryDepth.value--;
              return;
            }
            if (assyTestStep.value > 0) {
              prevAssyTestStep(true);
              if (assyTestHistoryDepth.value > 0) assyTestHistoryDepth.value--;
              return;
            }
            // Step 0: back button di awal pengujian
            if (assyTestHistoryDepth.value > 0) assyTestHistoryDepth.value--;
            isNavigatingBack = true;
            try {
              handleAssyBackNavigation();
            } finally {
              isNavigatingBack = false;
            }
            return;
          }

          // 4. Sub-view QC (Kartu Tray -> Rekap Tray)
          if (currentTab.value === 'qc' && selectedQCTrayCode.value) {
            isNavigatingBack = true;
            try {
              selectedQCTrayCode.value = null;
            } finally {
              isNavigatingBack = false;
            }
            return;
          }

          // 5. Multi-Stage Gudang (Stage 3 -> Stage 2 -> Stage 1)
          if (currentTab.value === 'warehouse' && gudangColStage.value > 1) {
            gudangColStage.value--;
            if (gudangStageHistoryDepth.value > 0) gudangStageHistoryDepth.value--;
            triggerToast('Tahap ' + gudangColStage.value, 'Kembali ke tahap sebelumnya', 'info');
            return;
          }

          // 6. Tab / Subtab Navigation Stack
          if (_tabNavStack.length > 1) {
            _tabNavStack.pop();
            const prev = _tabNavStack[_tabNavStack.length - 1];
            if (prev) {
              if (prev.tab === 'report') {
                navigateTo('report', prev.subTab || 'wo', true);
              } else if (prev.tab === 'users') {
                navigateTo('users', prev.subTab || 'verify_set', true);
              } else {
                navigateTo(prev.tab, null, true);
              }
              return;
            }
          }

          // 7. Accidental Exit Guard di Root Tab (Buffer Konstan O(1))
          if (exitGuardArmed) {
            clearTimeout(exitGuardTimer);
            exitGuardArmed = false;
            // Biarkan native browser keluar (tidak re-push sentinel)
            return;
          }

          exitGuardArmed = true;
          // Re-push root_sentinel agar kedalaman stack tetap konstan 2 entri (net 0)
          try {
            window.history.pushState({ type: 'root_sentinel' }, '', getCurrentTabHash());
          } catch (_) { }
          triggerToast('Keluar Aplikasi', 'Tekan sekali lagi untuk keluar dari sistem.', 'warn');
          clearTimeout(exitGuardTimer);
          exitGuardTimer = setTimeout(() => {
            exitGuardArmed = false;
          }, 2000);
        };

        // ===================================================================
        // MODULE: DELIVERY ORDER (DO) & PACKING LIST (PL) & PELABELAN
        // ===================================================================
        const nkriEmblemBase64 = 'data:image/png;base64,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';
        const nkriLogoBase64 = 'data:image/png;base64,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';
        const nkriLabelLogoBase64 = 'data:image/png;base64,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';
        const doSubTab = ref('history');
        const doTableDisplayMode = ref('pdf_exact'); // 'pdf_exact' (4 rows) or 'all_crates'
        const doSJCurrentPage = ref(1);

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

        const distinctPLLabelNumbers = computed(() => {
          const set = new Set();
          (standaloneLabelsList.value || []).forEach(lbl => {
            if (lbl.pl_number) set.add(lbl.pl_number);
            else if (lbl.label_batch_id) set.add(lbl.label_batch_id);
          });
          return Array.from(set).sort();
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

        const previewPackingListSheets = computed(() => {
          if (!previewDOData.value || !previewDOData.value.crates || previewDOData.value.crates.length === 0) return [];
          const crates = previewDOData.value.crates;
          const sheets = [];
          for (let i = 0; i < crates.length; i += 2) {
            sheets.push({
              sheetIndex: Math.floor(i / 2) + 1,
              totalSheets: Math.ceil(crates.length / 2),
              crate1: crates[i],
              crate2: crates[i + 1] || null
            });
          }
          return sheets;
        });

        const previewPackingListSheetIndex = ref(0);

        const previewCurrentPackingListSheet = computed(() => {
          const sheets = previewPackingListSheets.value;
          if (!sheets || sheets.length === 0) return { crate1: null, crate2: null, sheetIndex: 1, totalSheets: 1 };
          const idx = Math.min(Math.max(0, previewPackingListSheetIndex.value), sheets.length - 1);
          return sheets[idx] || { crate1: null, crate2: null, sheetIndex: 1, totalSheets: 1 };
        });

        const printPackingListSheets = computed(() => {
          if (!activePrintDO.value || !activePrintDO.value.crates || activePrintDO.value.crates.length === 0) return [];
          const crates = activePrintDO.value.crates;
          const sheets = [];
          for (let i = 0; i < crates.length; i += 2) {
            sheets.push({
              sheetIndex: Math.floor(i / 2) + 1,
              totalSheets: Math.ceil(crates.length / 2),
              crate1: crates[i],
              crate2: crates[i + 1] || null
            });
          }
          return sheets;
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
          if (doObj.crates && Array.isArray(doObj.crates)) {
            for (const crate of doObj.crates) {
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

        const getDOCrateCount = (doObj) => {
          if (!doObj) return 0;
          if (doObj.crates && Array.isArray(doObj.crates) && doObj.crates.length > 0) {
            return doObj.crates.length;
          }
          const qty = parseInt(doObj.total_quantity) || 0;
          return Math.ceil(qty / 20) || 0;
        };

        const getDOBoxCount = (doObj) => {
          if (!doObj) return 0;
          if (doObj.crates && Array.isArray(doObj.crates) && doObj.crates.length > 0) {
            let bCount = 0;
            doObj.crates.forEach(c => {
              if (c.boxes && Array.isArray(c.boxes)) {
                bCount += c.boxes.length;
              }
            });
            if (bCount > 0) return bCount;
          }
          const qty = parseInt(doObj.total_quantity) || 0;
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
        const syncLabelPackagingStructure = (forceBlank = false) => {
          const type = labelForm.value.package_type || 'crate';
          const count = Math.max(1, parseInt(labelForm.value.package_count) || 1);
          const batchNum = Math.max(1, parseInt(labelForm.value.batch_number) || 1);
          const crates = [];

          const targetDate = labelForm.value.date || getNowWIBDateString();
          const parsed = getYearMonthFromDate(targetDate);
          const yy = parsed.yy;
          const mm = parsed.mm;
          const prefix = `${yy}${mm}-${batchNum}`;

          let startSeq = 1;
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
            scanCrates(standaloneLabelsList.value);
            scanCrates(doList.value);
            startSeq = maxFound + 1;

            for (let c = 0; c < count; c++) {
              const crateSeq = startSeq + c;
              const crateId = `${prefix}${String(crateSeq).padStart(3, '0')}`;
              const boxes = [];
              for (let b = 1; b <= 2; b++) {
                const boxId = `${crateId}-${String(b).padStart(2, '0')}`;
                const items = [];
                for (let s = 1; s <= 10; s++) {
                  items.push({ slot_number: s, serial_number: '' });
                }
                boxes.push({ box_number: b, box_id: boxId, items });
              }
              crates.push({ crate_number: c + 1, crate_id: crateId, total_quantity: 20, boxes });
            }
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
            scanBoxes(standaloneLabelsList.value);
            scanBoxes(doList.value);
            startSeq = maxFound + 1;

            for (let b = 0; b < count; b++) {
              const boxSeq = startSeq + b;
              const boxId = `${parentCrateId}-${String(boxSeq).padStart(2, '0')}`;
              const items = [];
              for (let s = 1; s <= 10; s++) {
                items.push({ slot_number: s, serial_number: '' });
              }
              crates.push({
                crate_number: b + 1,
                crate_id: parentCrateId,
                box_id: boxId,
                total_quantity: 10,
                boxes: [{ box_number: 1, box_id: boxId, items }]
              });
            }
          } else {
            for (let u = 1; u <= count; u++) {
              crates.push({
                crate_number: u,
                crate_id: '',
                total_quantity: 1,
                boxes: [{
                  box_number: 1,
                  box_id: '',
                  items: [{ slot_number: 1, serial_number: '' }]
                }]
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
          if (activeLabelCrateIndex.value >= crates.length) {
            activeLabelCrateIndex.value = Math.max(0, crates.length - 1);
          }
        };

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

        const getLabelCrateFilledCount = (crate) => {
          if (!crate || !crate.boxes) return 0;
          let count = 0;
          crate.boxes.forEach(b => {
            (b.items || []).forEach(it => {
              if (it.serial_number && String(it.serial_number).trim()) count++;
            });
          });
          return count;
        };

        const getLabelCrateRequiredCount = (crate) => {
          if (!crate || !crate.boxes) return 0;
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

        const resetLabelForm = async (customBatch = null) => {
          let nextB = customBatch;
          if (!nextB) {
            try {
              const res = await fetchAPI('/packing-labels');
              if (res && res.next_batch) nextB = res.next_batch;
            } catch (_) { }
          }
          if (!nextB) {
            const maxExisting = (standaloneLabelsList.value || []).reduce((max, l) => Math.max(max, parseInt(l.batch_number) || 0), 0);
            nextB = maxExisting >= 1 ? maxExisting + 1 : 1;
          }
          labelForm.value = {
            date: getNowWIBDateString(),
            package_type: 'crate',
            package_count: 1,
            batch_number: nextB,
            product_name: doSettings.value.default_product_name || 'Nanggala P9',
            notes: '',
            crates: []
          };
          activeLabelCrateIndex.value = 0;
          syncLabelPackagingStructure(true);
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

        const autoFillAvailableSNsForLabel = () => {
          if (!availableFinishedSNs.value || availableFinishedSNs.value.length === 0) {
            triggerToast('Perhatian', 'Tidak ada Serial Number yang berstatus lulus QC di gudang', 'warning');
            return;
          }
          let snIdx = 0;
          let filledCount = 0;
          (labelForm.value.crates || []).forEach(c => {
            (c.boxes || []).forEach(b => {
              (b.items || []).forEach(it => {
                if (!it.serial_number && snIdx < availableFinishedSNs.value.length) {
                  it.serial_number = availableFinishedSNs.value[snIdx];
                  snIdx++;
                  filledCount++;
                }
              });
            });
          });
          if (filledCount > 0) {
            triggerToast('Berhasil', `${filledCount} Serial Number berhasil diisi otomatis ke dalam label!`, 'success');
          } else {
            triggerToast('Info', 'Seluruh slot Serial Number sudah terisi.', 'info');
          }
        };

        const loadStandaloneLabels = async () => {
          isLoadingStandaloneLabels.value = true;
          try {
            const res = await fetchAPI('/packing-labels');
            if (res && res.data) {
              standaloneLabelsList.value = res.data;
              if (res.next_batch) {
                const isFormUntouched = !(labelForm.value.crates || []).some(c => (c.boxes || []).some(b => (b.items || []).some(it => it.serial_number)));
                if (isFormUntouched) {
                  labelForm.value.batch_number = res.next_batch;
                  syncLabelPackagingStructure();
                }
              }
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

          isSavingStandaloneLabel.value = true;
          try {
            const labelDate = labelForm.value.date || getNowWIBDateString();
            const payload = {
              package_type: labelForm.value.package_type,
              batch_number: labelForm.value.batch_number,
              product_name: labelForm.value.product_name || doSettings.value.default_product_name || 'Nanggala P9',
              notes: labelForm.value.notes,
              crates: labelForm.value.crates,
              date: labelDate
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
        const doStartDate = ref('');
        const doEndDate = ref('');
        const doDocFilter = ref('');

        const distinctDODocNumbers = computed(() => {
          const set = new Set();
          (doList.value || []).forEach(d => {
            if (d.do_number) set.add(d.do_number);
          });
          return Array.from(set).sort();
        });

        const resetDOFilter = () => {
          doSearchText.value = '';
          doStartDate.value = '';
          doEndDate.value = '';
          doDocFilter.value = '';
          if (pgState.doHistory) pgState.doHistory.page = 1;
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
        const activeFormStep = ref('customer');
        const completedSteps = ref(new Set());
        const openFormCards = ref({
          customer: true,
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
          return completedSteps.value.has(stepKey) && isStepValid(stepKey);
        };

        const canAccessStep = (stepKey) => {
          if (stepKey === 'customer') return true;
          if (stepKey === 'reference') return isStepCompleted('customer') || isStepValid('customer');
          if (stepKey === 'batch') return isStepCompleted('reference');
          if (stepKey === 'allocation') return isStepCompleted('batch');
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
            completedSteps.value.delete('reference');
            completedSteps.value.delete('batch');
            completedSteps.value.delete('allocation');
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
            completedSteps.value.delete('batch');
            completedSteps.value.delete('allocation');
          } else if (targetStep === 'allocation') {
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
          } else if (targetStep === 'customer') {
            completedSteps.value.delete('customer');
            completedSteps.value.delete('reference');
            completedSteps.value.delete('batch');
            completedSteps.value.delete('allocation');
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
          // Dinonaktifkan: Berpindah kartu HANYA melalui tombol 'Lanjut' dan 'Kembali'
          return;
        };

        const toggleFormCard = (cardKey) => {
          onCardHeaderClick(cardKey);
        };

        const setAllFormCards = (isOpen) => {
          if (isOpen) {
            return;
          }
          resetDOFormSteps();
        };

        const resetDOFormSteps = () => {
          activeFormStep.value = 'customer';
          completedSteps.value.clear();
          openFormCards.value = {
            customer: true,
            reference: false,
            batch: false,
            allocation: false
          };
          activeContainerIndex.value = 0;
          selectedConsumedPLId.value = '';
          selectedConsumedPL.value = null;
          if (doForm.value) {
            delete doForm.value.consumed_standalone_id;
          }
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

        const doForm = ref({
          do_number: '',
          do_date: getTodayISODate(),
          customer_name: '',
          customer_address: '',
          customer_phone: '',
          customer_fax: '',
          contract_number: '',
          program_name: '',
          product_name: 'Nanggala P9',
          issued_by_name: '',
          issued_by_title: '',
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
                doForm.value.crates = JSON.parse(JSON.stringify(plData.crates));
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
          (standaloneLabelsList.value || []).forEach(pl => {
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
            return c.crate_id === crateId || c.consumed_source_crate_id === crateId;
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
          targetCrate.crate_id = plCrate.crate_id;
          targetCrate.consumed_source_crate_id = plCrate.crate_id;
          targetCrate.consumed_from_pl = plCrate.pl_number;

          // Copy boxes and SN
          targetCrate.boxes = (plCrate.boxes || []).map((b, bIdx) => {
            const bNum = b.box_number || (bIdx + 1);
            return {
              box_number: bNum,
              box_id: `${plCrate.crate_id}-${String(bNum).padStart(2, '0')}`,
              consumed_source_box_id: b.box_id || `${plCrate.crate_id}-${String(bNum).padStart(2, '0')}`,
              consumed_from_pl: plCrate.pl_number,
              items: (b.items || []).map(it => ({
                slot_number: it.slot_number,
                serial_number: it.serial_number || ''
              }))
            };
          });

          showSelectCrateModal.value = false;
          triggerToast('Peti Terpilih', `ID Peti ${plCrate.crate_id} berhasil diterapkan ke Peti ${cIdx + 1}!`, 'success');
        };

        const getAvailablePLBoxesList = () => {
          const list = [];
          (standaloneLabelsList.value || []).forEach(pl => {
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
                if (cIdx !== currentCrateIdx && (b.box_id === sourceBoxId || b.consumed_source_box_id === sourceBoxId)) {
                  return true;
                }
              } else {
                if ((cIdx !== currentCrateIdx || b.box_number !== currentBoxNumber) && (b.box_id === sourceBoxId || b.consumed_source_box_id === sourceBoxId)) {
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
            // DO Kemasan Dus
            targetCrate.box_id = plBox.box_id;
            targetCrate.consumed_source_box_id = plBox.box_id;
            targetCrate.consumed_from_pl = plBox.pl_number;
            if (targetCrate.boxes && targetCrate.boxes[0]) {
              targetCrate.boxes[0].box_id = plBox.box_id;
              targetCrate.boxes[0].consumed_source_box_id = plBox.box_id;
              targetCrate.boxes[0].consumed_from_pl = plBox.pl_number;
              targetCrate.boxes[0].items = (plBox.items || []).map(it => ({
                slot_number: it.slot_number,
                serial_number: it.serial_number || ''
              }));
            }
            showSelectBoxModal.value = false;
            triggerToast('Dus Terpilih', `ID Dus ${plBox.box_id} berhasil diterapkan ke Dus ${crateIdx + 1}!`, 'success');
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
            targetBox.items = (plBox.items || []).map(it => ({
              slot_number: it.slot_number,
              serial_number: it.serial_number || ''
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
          if (sIdx === 0) return false;
          const prev = items[sIdx - 1];
          return !prev || !prev.serial_number || !prev.serial_number.trim();
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
          if (item.do_date && /^\d{4}-\d{2}-\d{2}$/.test(String(item.do_date).trim())) {
            return String(item.do_date).trim();
          }
          if (item.created_at) {
            return String(item.created_at).substring(0, 10);
          }
          if (item.do_date) {
            const months = {
              'januari': '01', 'februari': '02', 'maret': '03', 'april': '04',
              'mei': '05', 'juni': '06', 'juli': '07', 'agustus': '08',
              'september': '09', 'oktober': '10', 'november': '11', 'desember': '12'
            };
            const parts = String(item.do_date).trim().toLowerCase().split(/\s+/);
            if (parts.length === 3 && months[parts[1]]) {
              const day = parts[0].padStart(2, '0');
              const month = months[parts[1]];
              const year = parts[2];
              return `${year}-${month}-${day}`;
            }
          }
          return '';
        };

        const filteredDOList = computed(() => {
          let list = doList.value || [];

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

          // 4. Search query
          const q = (doSearchText.value || '').trim().toLowerCase();
          if (q) {
            list = list.filter(d =>
              (d.do_number && d.do_number.toLowerCase().includes(q)) ||
              (d.customer_name && d.customer_name.toLowerCase().includes(q)) ||
              (d.contract_number && d.contract_number.toLowerCase().includes(q)) ||
              (d.program_name && d.program_name.toLowerCase().includes(q))
            );
          }

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

        const openNewCustomerModal = () => {
          newCustomerForm.value = { name: '', address: '', phone: '', fax: '', notes: '' };
          showNewCustomerModal.value = true;
        };

        const saveNewCustomer = async () => {
          if (!newCustomerForm.value.name.trim()) {
            triggerToast('Perhatian', 'Nama Customer wajib diisi', 'warn');
            return;
          }
          isSubmittingCustomer.value = true;
          try {
            const data = await fetchAPI('/customers', {
              method: 'POST',
              body: JSON.stringify(newCustomerForm.value)
            });
            if (data && data.customer) {
              customersList.value.unshift(data.customer);
              selectCustomer(data.customer);
              showNewCustomerModal.value = false;
              triggerToast('Sukses', 'Customer baru berhasil ditambahkan', 'success');
            } else {
              triggerToast('Gagal', data?.error || 'Gagal menyimpan customer', 'error');
            }
          } catch (err) {
            console.error(err);
            triggerToast('Error', err.message || 'Gagal menyimpan customer', 'error');
          } finally {
            isSubmittingCustomer.value = false;
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
            const data = await fetchAPI(`/delivery-orders/next-meta${queryParam}`);
            if (data && (data.ok || data.do_number)) {
              if (data.do_number) doForm.value.do_number = data.do_number;
              if (data.batch_number && (!doForm.value.batch_number || doForm.value.batch_number === 1)) {
                doForm.value.batch_number = data.batch_number;
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
              if (data.customer_name && !doForm.value.customer_name) doForm.value.customer_name = data.customer_name;
              if (data.customer_address && !doForm.value.customer_address) doForm.value.customer_address = data.customer_address;
              if (data.contract_number && !doForm.value.contract_number) doForm.value.contract_number = data.contract_number;
              if (data.program_name && !doForm.value.program_name) doForm.value.program_name = data.program_name;
              if (data.product_name && !doForm.value.product_name) doForm.value.product_name = data.product_name;
              if (data.created_by_name && !doForm.value.issued_by_name) doForm.value.issued_by_name = data.created_by_name;
              if (data.created_by_title && !doForm.value.issued_by_title) doForm.value.issued_by_title = data.created_by_title;
              if (data.created_by_department && !doForm.value.issued_by_dept) doForm.value.issued_by_dept = data.created_by_department;
              syncPackagingStructure();
            }
          } catch (err) {
            console.error('Failed to load next DO meta:', err);
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
          const prefix = `${yy}${mm}-${bNum}`;

          if (type === 'crate') {
            for (let i = 0; i < num; i++) {
              const c = start + i;
              const crateId = `${prefix}${String(c).padStart(3, '0')}`;
              const boxes = [];
              for (let b = 1; b <= 2; b++) {
                const boxId = `${crateId}-${String(b).padStart(2, '0')}`;
                const items = [];
                for (let s = 1; s <= 10; s++) {
                  items.push({ slot_number: s, serial_number: '' });
                }
                boxes.push({ box_number: b, box_id: boxId, items });
              }
              crates.push({ crate_number: i + 1, crate_id: crateId, boxes });
            }
          } else if (type === 'box') {
            const parentCrateId = `${yy}${mm}-${bNum}000`;
            for (let i = 0; i < num; i++) {
              const b = start + i;
              const boxId = `${parentCrateId}-${String(b).padStart(2, '0')}`;
              const items = [];
              for (let s = 1; s <= 10; s++) {
                items.push({ slot_number: s, serial_number: '' });
              }
              crates.push({
                crate_number: i + 1,
                crate_id: parentCrateId,
                box_id: boxId,
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
                c.crate_id = oldC.crate_id;
                c.consumed_source_crate_id = oldC.consumed_source_crate_id;
              }
              if (type === 'box' && oldC.consumed_source_box_id) {
                c.box_id = oldC.box_id;
                c.consumed_source_box_id = oldC.consumed_source_box_id;
                c.consumed_from_pl = oldC.consumed_from_pl;
              }
              if (oldC.boxes) {
                c.boxes.forEach((b, bIdx) => {
                  const oldB = oldC.boxes[bIdx];
                  if (oldB) {
                    if (oldB.consumed_source_box_id) {
                      b.consumed_source_box_id = oldB.consumed_source_box_id;
                      b.consumed_from_pl = oldB.consumed_from_pl;
                    }
                    if (oldB.items) {
                      b.items.forEach((it, sIdx) => {
                        const oldIt = oldB.items[sIdx];
                        if (oldIt && oldIt.serial_number) {
                          it.serial_number = oldIt.serial_number;
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

        const getCrateFilledCount = (crate) => {
          if (!crate || !crate.boxes) return 0;
          let count = 0;
          crate.boxes.forEach(b => {
            if (b.items) {
              b.items.forEach(it => {
                if (it.serial_number && String(it.serial_number).trim()) count++;
              });
            }
          });
          return count;
        };

        const isSNInList = (sn) => {
          if (!sn) return false;
          return availableFinishedSNs.value.includes(sn.trim().toUpperCase());
        };

        const getAvailableSNsForSlot = (currentVal) => {
          const used = new Set();
          getAllUnitsInForm().forEach(it => {
            if (it.serial_number && it.serial_number.trim() && it.serial_number !== currentVal) {
              used.add(it.serial_number.trim().toUpperCase());
            }
          });
          return availableFinishedSNs.value.filter(s => !used.has(s.trim().toUpperCase()));
        };

        const openBatchPasteModal = (crateIdx, boxNum, isUnit = false) => {
          batchPasteTarget.value = { crateIdx, boxNum, isUnit };
          batchPasteRawText.value = '';
          showBatchPasteModal.value = true;
        };

        const applyBatchPaste = () => {
          const rawLines = batchPasteRawText.value
            .split(/\r?\n/)
            .map(l => l.trim().toUpperCase())
            .filter(l => l.length > 0);

          if (rawLines.length === 0) {
            triggerToast('Perhatian', 'Tidak ada data Serial Number yang valid untuk ditempel.', 'warn');
            return;
          }

          const { crateIdx, boxNum, isUnit } = batchPasteTarget.value;
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
          for (const it of getAllUnitsInForm()) {
            const sn = it.serial_number.trim().toUpperCase();
            if (seen.has(sn)) {
              triggerToast('Duplikasi SN', `Serial Number "${sn}" terisi lebih dari satu kali.`, 'error');
              return;
            }
            seen.add(sn);
          }

          if (doForm.value.package_type === 'unit') {
            (doForm.value.crates || []).forEach(c => {
              const sn = c.boxes?.[0]?.items?.[0]?.serial_number;
              if (sn) c.crate_id = sn;
            });
          }

          isSubmittingDO.value = true;
          try {
            const data = await fetchAPI('/delivery-orders', {
              method: 'POST',
              body: JSON.stringify(doForm.value)
            });
            if (data && (data.success || data.delivery_order || data.id)) {
              triggerToast('Berhasil Diterbitkan', `Dokumen Surat Jalan ${data.do_number || doForm.value.do_number} berhasil dibuat!`, 'success');
              await loadDeliveryOrders();
              await loadAvailableFinishedSNs();
              await loadNextDONumber();
              await loadNextBatchNumber();

              // Gabungkan data form dengan response API agar modal menampilkan info lengkap
              lastPublishedDO.value = data.delivery_order || {
                ...doForm.value,
                id: data.id,
                do_number: data.do_number || doForm.value.do_number,
              };
              showDOPostPublishModal.value = true;
              doSubTab.value = 'history';
              resetDOFormSteps();

              // Otomatis unggah Packing List ke Cloudinary jika sudah dikonfigurasi
              if (doSettings.value?.cloudinary?.configured && lastPublishedDO.value?.id) {
                uploadDOPackingListToCloud(lastPublishedDO.value).catch(e => console.error('Auto cloud upload error:', e));
              }
            } else {
              triggerToast('Gagal Menerbitkan', data?.error || 'Gagal menyimpan Delivery Order', 'error');
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

        onMounted(() => {
          // Inisialisasi stack dengan tab aktif saat ini
          const initRoute = getInitialRoute();
          const initTab = (initRoute && initRoute.tab) ? initRoute.tab : (currentTab.value || defaultTab.value || 'assembly');
          const initSubTab = (initRoute && initRoute.subTab) ? initRoute.subTab : null;
          const initKey = initTab === 'users'
            ? `users/${normalizeAdminSubTab(initSubTab) || 'verify_set'}`
            : (initTab === 'report' ? `report/${initSubTab || 'wo'}` : initTab);
          _tabNavStack.length = 0;
          _tabNavStack.push({ tab: initTab, subTab: initSubTab, key: initKey });

          // Inisialisasi Buffer Dua Tingkat O(1) di root
          try {
            window.history.replaceState({ type: 'root_base' }, '', getCurrentTabHash());
            window.history.pushState({ type: 'root_sentinel' }, '', getCurrentTabHash());
          } catch (_) { }

          window.addEventListener('popstate', handleMobileBackNavigation);
          document.addEventListener('click', handleCustomerDropdownClickOutside);

          window.addEventListener('beforeunload', (e) => {
            if (isUserFormDirty() || isTestingPointFormDirty()) {
              e.preventDefault();
              e.returnValue = '';
              return '';
            }
          });

          try {
            syncPackagingStructure();
            syncCurrentUserPermissions();
            if (!hasTabAccess(currentTab.value, adminSubTab.value)) {
              const fallback = defaultTab.value;
              const target = hasTabAccess(fallback) ? fallback : (['assembly', 'warehouse', 'qc', 'traveler', 'returns', 'report'].find(t => hasTabAccess(t)) || 'assembly');
              currentTab.value = target;
              localStorage.setItem('activeTab', target);
              try {
                window.location.hash = '#' + target;
              } catch (_) { }
            }

            if (currentTab.value === 'warehouse') {
              if (typeof fetchInputSuggestions === 'function') {
                fetchInputSuggestions(0, 'p1', 0, false);
              }
            } else if (currentTab.value === 'assembly' || currentTab.value === 'assy_testing') {
              loadTrays().then(() => {
                if (selectedTrayCode.value) loadAssemblyTray();
              });
            }

            setTimeout(() => {
              startGlobalBadgePolling();
              prefetchBackgroundData();
            }, 600);
          } catch (err) {
            console.error('Error during onMounted initialization:', err);
          } finally {
            // Hide Fullscreen Loading Screen smoothly once Vue is ready
            const loader = document.getElementById('app-loading-screen');
            if (loader) {
              loader.style.opacity = '0';
              loader.style.pointerEvents = 'none';
              setTimeout(() => {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
              }, 500);
            }
          }
        });

        onUnmounted(() => {
          window.removeEventListener('popstate', handleMobileBackNavigation);
          document.removeEventListener('click', handleCustomerDropdownClickOutside);
        });

        // =========================================================
        // PAGINATION STATE (13 menus)
        // =========================================================
        const pgState = Vue.reactive({
          adminSets: { page: 1, size: 20 },
          adminParts: { page: 1, size: 20 },
          adminRepl: { page: 1, size: 20 },
          auditLogs: { page: 1, size: 20 },
          downtime: { page: 1, size: 20 },
          testPoint: { page: 1, size: 10 },
          users: { page: 1, size: 10 },
          masterStock: { page: 1, size: 50 },
          qcList: { page: 1, size: 10 },
          gudangVerif: { page: 1, size: 10 },
          woReport: { page: 1, size: 20 },
          assyReport: { page: 1, size: 20 },
          assyTestReport: { page: 1, size: 20 },
          qcReport: { page: 1, size: 20 },
          receiptReport: { page: 1, size: 20 },
          warehouseReport: { page: 1, size: 20 },
          productionHold: { page: 1, size: 15 },
          qcTraySummary: { page: 1, size: 10 },
          assemblyResults: { page: 1, size: 10 },
          doHistory: { page: 1, size: 10 },
          labelsHistory: { page: 1, size: 10 },
          returns: { page: 1, size: 10 },
          receiptTray: { page: 1, size: 10 },
        });

        const pagedAdminSets = computed(() => { const s = pgState.adminSets; return (filteredAdminSetsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminParts = computed(() => { const s = pgState.adminParts; return (filteredAdminPartsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminRepl = computed(() => { const s = pgState.adminRepl; return (filteredAdminReplacements.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAuditLogs = computed(() => { const s = pgState.auditLogs; return (filteredAuditLogs.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedDowntime = computed(() => { const s = pgState.downtime; return (downtimeLogsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedTestPoint = computed(() => { const s = pgState.testPoint; return (filteredTestingPointsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedUsers = computed(() => { const s = pgState.users; return (usersList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedMasterStock = computed(() => { const s = pgState.masterStock; return (masterStockList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedQCList = computed(() => { const s = pgState.qcList; return (filteredQCList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedGudangVerif = computed(() => { const s = pgState.gudangVerif; return (gudangVerifySets.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedWOReport = computed(() => { const s = pgState.woReport; return (filteredWOReport.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAssyReport = computed(() => { const s = pgState.assyReport; return (filteredAssemblyReport.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAssyTestReport = computed(() => { const s = pgState.assyTestReport; return (filteredAssyTestReport.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedQCReport = computed(() => { const s = pgState.qcReport; return (filteredQCTestReport.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedWarehouseReport = computed(() => { const s = pgState.warehouseReport || pgState.receiptReport; return (filteredWarehouseReport.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedProductionHoldList = computed(() => { const s = pgState.productionHold; return (filteredProductionHoldList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedQcTraySummaryList = computed(() => { const s = pgState.qcTraySummary; return (qcTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAssemblyResults = computed(() => { const s = pgState.assemblyResults; return (flatFilteredAssemblyResults.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedDOList = computed(() => { const s = pgState.doHistory; return (filteredDOList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const filteredStandaloneLabelsList = computed(() => {
          let list = standaloneLabelsList.value || [];

          // 1. Doc / No. PL Filter
          if (labelDocFilter.value) {
            const target = labelDocFilter.value.trim().toLowerCase();
            list = list.filter(l =>
              (l.pl_number && l.pl_number.trim().toLowerCase() === target) ||
              (l.label_batch_id && l.label_batch_id.trim().toLowerCase() === target)
            );
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
            list = list.filter(l =>
              (l.label_batch_id || '').toLowerCase().includes(q) ||
              (l.pl_number || '').toLowerCase().includes(q) ||
              (l.created_by || '').toLowerCase().includes(q) ||
              (l.product_name || '').toLowerCase().includes(q) ||
              (l.package_type || '').toLowerCase().includes(q) ||
              (l.notes || '').toLowerCase().includes(q)
            );
          }

          return list;
        });
        const pagedStandaloneLabelsList = computed(() => {
          const s = pgState.labelsHistory;
          return (filteredStandaloneLabelsList.value || []).slice((s.page - 1) * s.size, s.page * s.size);
        });
        const totalPgStandaloneLabels = computed(() => Math.max(1, Math.ceil((filteredStandaloneLabelsList.value || []).length / pgState.labelsHistory.size)));
        const pagedReturnsList = computed(() => { const s = pgState.returns; return (filteredReturnsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedWarehouseReceiptTraySummaryList = computed(() => { const s = pgState.receiptTray; return (warehouseReceiptTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });

        const totalPgAdminSets = computed(() => Math.max(1, Math.ceil((filteredAdminSetsOnly.value || []).length / pgState.adminSets.size)));
        const totalPgAdminParts = computed(() => Math.max(1, Math.ceil((filteredAdminPartsOnly.value || []).length / pgState.adminParts.size)));
        const totalPgAdminRepl = computed(() => Math.max(1, Math.ceil((filteredAdminReplacements.value || []).length / pgState.adminRepl.size)));
        const totalPgAuditLogs = computed(() => Math.max(1, Math.ceil((filteredAuditLogs.value || []).length / pgState.auditLogs.size)));
        const totalPgDowntime = computed(() => Math.max(1, Math.ceil((downtimeLogsList.value || []).length / pgState.downtime.size)));
        const totalPgTestPoint = computed(() => Math.max(1, Math.ceil((filteredTestingPointsList.value || []).length / pgState.testPoint.size)));
        const totalPgUsers = computed(() => Math.max(1, Math.ceil((usersList.value || []).length / pgState.users.size)));
        const totalPgMasterStock = computed(() => Math.max(1, Math.ceil((masterStockList.value || []).length / pgState.masterStock.size)));
        const totalPgQCList = computed(() => Math.max(1, Math.ceil((filteredQCList.value || []).length / pgState.qcList.size)));
        const totalPgGudangVerif = computed(() => Math.max(1, Math.ceil((gudangVerifySets.value || []).length / pgState.gudangVerif.size)));
        const totalPgWOReport = computed(() => Math.max(1, Math.ceil((filteredWOReport.value || []).length / pgState.woReport.size)));
        const totalPgAssyReport = computed(() => Math.max(1, Math.ceil((filteredAssemblyReport.value || []).length / pgState.assyReport.size)));
        const totalPgAssyTestReport = computed(() => Math.max(1, Math.ceil((filteredAssyTestReport.value || []).length / pgState.assyTestReport.size)));
        const totalPgQCReport = computed(() => Math.max(1, Math.ceil((filteredQCTestReport.value || []).length / pgState.qcReport.size)));
        const totalPgWarehouseReport = computed(() => Math.max(1, Math.ceil((filteredWarehouseReport.value || []).length / ((pgState.warehouseReport && pgState.warehouseReport.size) || (pgState.receiptReport && pgState.receiptReport.size) || 20))));
        const totalPgProductionHold = computed(() => Math.max(1, Math.ceil((filteredProductionHoldList.value || []).length / pgState.productionHold.size)));
        const totalPgQcTraySummary = computed(() => Math.max(1, Math.ceil((qcTraySummaryList.value || []).length / pgState.qcTraySummary.size)));
        const totalPgAssemblyResults = computed(() => Math.max(1, Math.ceil((flatFilteredAssemblyResults.value || []).length / pgState.assemblyResults.size)));
        const totalPgDOList = computed(() => Math.max(1, Math.ceil((filteredDOList.value || []).length / pgState.doHistory.size)));
        const totalPgReturns = computed(() => Math.max(1, Math.ceil((filteredReturnsList.value || []).length / pgState.returns.size)));
        const totalPgReceiptTray = computed(() => Math.max(1, Math.ceil((warehouseReceiptTraySummaryList.value || []).length / pgState.receiptTray.size)));

        watch([selectedProductionProduct, selectedProductionOperator, selectedProductionDocAssy, productionSearchQuery, assemblyResultsStatusFilter], () => {
          if (pgState.assemblyResults) pgState.assemblyResults.page = 1;
        });

        watch([doSearchText, doStartDate, doEndDate, doDocFilter], () => {
          if (pgState.doHistory) pgState.doHistory.page = 1;
        });

        watch([labelSearchText, labelDocFilter, labelPackageFilter, labelStartDate, labelEndDate], () => {
          if (pgState.labelsHistory) pgState.labelsHistory.page = 1;
        });

        watch([() => returnsFilter.operator, () => returnsFilter.traySuffix, () => returnsFilter.status], () => {
          if (pgState.returns) pgState.returns.page = 1;
        });

        watch([() => reportFilters?.warehouse_receipt?.searchQuery], () => {
          if (pgState.receiptTray) pgState.receiptTray.page = 1;
        });

        watch(() => doForm.value.do_date, (newVal, oldVal) => {
          if (newVal && newVal !== oldVal) {
            onDODateChanged();
          }
        });

        
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
          currentTab, defaultTab, userRole, userName, userFullName, operatorName, toast,
          confirmModalState, showConfirmCard, showPromptCard, handleConfirmCard, safeCloseModal,
          traysList, selectedTrayCode, currentTray, currentTrayParts, currentTrayAssemblies,
          currentTraySetsCount, finishedSlotsCount, traySlots, travelerTray, travelerItems, travelerAssyOperator, getInspectorQC, getTravelerItemActualStatus,
          assyQCQualifiedItems, whQCQualifiedItems, whGRQualifiedItems,
          activeModalIndex, assyForm, showLabelPreviewModal, activeLabelSN, showBatchLabelModal, batchLabelItems, openBatchLabelList,
          batchLabelDocInfo, isGeneratingBatchPDF, openBatchLabelForDoc, printBatchLabelsDirect, downloadBatchLabelsPDF, downloadSingleLabelFromBatch,
          liveClock, liveClockString, setStartTimeNow, setEndTimeNow, startAssemblyNow,
          gudangPicName, gudangProductType, gudangAssignedOperator, gudangSourceMO, gudangSourceMOSuffix, currentTravelerMO, currentTravelerMOSuffix,
          updateTravelerMO, onSourceMOSuffixInput, onTravelerMOSuffixInput,
          showPromptMOModal, moInputRef, promptSaveGudangTray, confirmSaveGudangTray, goToGudangStage,
          activeDatabase, fetchDatabaseStatus, swipeDatabase,
          operatorUsersList, getOperatorFullName, gudangColStage, gudangRows,
          showTrayResultPopup, newTrayResult, showTrayDetailModal, trayDetailData,
          inputDropdown, onInputFocus, onInputP1, onInputP12, onInputP15, onInputBlur, selectDropdownSN, getInputWarning,
          onDropdownTouchStart, onDropdownTouchMove, onDropdownTouchEnd,
          laporanList, searchQuery, filteredLaporan, laporanFilter, resetLaporanFilter, distinctLaporanTrays, distinctLaporanOperators,
          masterParts, stockSummary, masterStockList, masterFilter, replacedPartsTotalCount, loadMasterData, fetchMasterStockList, formatGudangInput,
          addPartTab, addPartBulkMode, addPartForm, addPartBulkForm, addPartLoading, addPartBulkResult,
          recentAddedParts, recentAddedLoading, recentAddedFilter, resetAddPartForm, resetAddPartBulkForm,
          loadRecentlyAddedParts, submitAddSinglePart, submitAddBulkPart, deleteRecentlyAddedPart,
          usersList, editingUserId, showUserPw, userForm, resetUserForm, editUser, saveUser, deleteUser, resetUserSession,
          isTrayDropdownOpen, traySearchText, filteredTraysList, trayDropdownRef,
          isTrayFullyFinished, hasHoldItem, isTrayLocked, firstUnfinishedIndex,
          isSlotLocked, canEditSlot, isStatusGood, isPartDefective, getDefectivePartClass, getDefectiveLabelClass, getDefectiveBadgeClass, getDefectiveTableCellClass,
          toggleTrayDropdown, selectTrayFromDropdown,
          switchTab, logout, loadAssemblyTray, loadTravelerTray, loadTrays, loadLaporan, loadMasterParts,
          openAssemblyModal, closeModal, captureStartTime, saveAssemblyItem,
          openLabelPreview, printP9Label, downloadP9LabelPDF, printP9LabelDirect, copyZPLCode, formatMOInput,
          addGudangRow, removeGudangRow, handleGudangKeyDown, verifyGudangStage1, verifyGudangStage2, saveGudangTray,
          closeTrayResultPopup, startAssemblyFromPopup, printTrayLabel, openTrayDetailModal, selectAndPrintTraveler, triggerPrint,
          exportToExcel, exportTrayToExcel, availablePartsByType, getItemCardClass, getStatusBadgeClass, formatDateTime, formatTimeOnly, formatDateOnly,
          formatLifecycleHistory, getStatusHistoryBadgeClass,
          showDetailModal, activeDetailSlot, showDetailChecklistFull, getFailedChecklistItems, showResetDbModal, isResettingDb, inProgressMap, openDetailModal, isWarehouseReceiptVisible, isQCResultCardVisible, getQCReportStatus, quickFinishSlot, confirmResetDatabase, formatDuration, getEffectiveFinishedAt,
          showStatusDrillModal, drillData, drillSearchQuery, filteredDrillList, openStatusDrilldown, onDrillSearchInput, debouncedFetchMasterStockList, applyFilterFromDrilldown, exportDrillListToExcel,
          showQuickStatusModal, quickStatusData, submitQuickStatusModal,
          activePrintDocType, showPrintDocTypeDropdown, selectPrintDocType, getFormattedDocCode, printStatusFilter,
          isPUTray, getPUDocNumber,
          printFilter, distinctPrintOperators, filteredPrintTrays, resetPrintFilter,
          adminSubTab, setAdminSubTab, auditLogsList, loadAuditLogs, auditFilter, filteredAuditLogs, resetAuditFilter,
          showOverrideModal, overrideForm, openOverrideModal, submitOverrideStatus,
          showGudangVerifyModal, gudangVerifySets, verifiedSetsCount, openGudangVerifyModal, completeGudangTrayVerification, verifyCurrentTrayActual,
          showAccOverrideModal, accOverrideForm, openAccOverrideModal, submitAccOverride,
          showDeclineConfirmModal, declineData, openDeclineModal, confirmDeclineItem,
          showLaporanDetailModal, laporanDetailData, openLaporanVerificationDetail,
          showReregisterModal, reregisterForm, openReregisterModal, submitReregisterPart,
          adminPendingRequests, adminPendingSets, adminPendingQCSets, adminPendingCount, loadPendingVerifications, approveReRegister,
          adminVerifyFilter, resetAdminVerifyFilter, filteredAdminSets, filteredAdminQCSets, filteredAdminScrapRequests,
          declinedSetsCount, declinedRequestsCount, declinedTotalCount, otherSetsCount, otherRequestsCount, otherTotalCount, allTotalCount,
          allAdminSets, adminSetViewMode, getNextPUTrayCode, adminSetProcessFilter, adminSetPartTypeFilter, adminSetStatusFilter, adminSetSearchQuery, adminSetStartDate, adminSetEndDate, resetAdminSetFilter, adminSetProcessCounts, adminPendingSetsTotalCount, filteredAdminSetsOnly, selectedAdminSetDetail, showAdminSetDetailModal, openAdminSetDetailModal,
          adminPartFilter, resetAdminPartFilter, filteredAdminPartsOnly, adminPendingRequestsCount, selectedAdminPartDetail, showAdminPartDetailModal, openAdminPartDetailModal,
          showAdminVerifySetModal, activeAdminSet, isSubmittingAllAdminVerify, hasUnverifiedPartsInActiveSet,
          isSetFullyVerified, getStockStatusBySN,
          pgState,
          pagedAdminSets, pagedAdminParts, pagedAdminRepl, pagedAuditLogs, pagedDowntime,
          pagedTestPoint, pagedUsers, pagedMasterStock, pagedQCList, pagedGudangVerif,
          pagedAssyReport, pagedAssyTestReport, pagedQCReport, pagedWOReport, pagedWarehouseReport, pagedProductionHoldList, pagedQcTraySummaryList,
          totalPgAdminSets, totalPgAdminParts, totalPgAdminRepl, totalPgAuditLogs,
          totalPgDowntime, totalPgTestPoint, totalPgUsers, totalPgMasterStock,
          totalPgQCList, totalPgGudangVerif, totalPgWOReport, totalPgAssyReport, totalPgAssyTestReport, totalPgQCReport, totalPgWarehouseReport,
          totalPgProductionHold, totalPgQcTraySummary,
          pagedReturnsList, pagedWarehouseReceiptTraySummaryList, totalPgReturns, totalPgReceiptTray,
          openAdminVerifySetModal, verifyAdminSinglePart, verifyAllPartsInActiveSet, isExecutingDirectResume, executeDirectResume, isSubmittingDeclinedRestore, restoreDeclinedSetToAvailable,
          deleteAdminVerifyRequest, deleteAdminSetPart, deleteAdminDeclinedSet,
          showAdminVerifyModal, adminVerifyForm, openAdminVerifyModal, submitAdminVerify,
          selectedOperatorFilter, onOperatorFilterChange,
          returnedTraysList, loadReturnedTrays, acknowledgeReturnedTray,
          returnsFilter, showReturnDetailModal, activeReturnTray, activeReturnSets, isVerifyingReturn, verifyingSetIndex, isAllActiveReturnSetsVerified, pendingReturnsCount, filteredReturnsList, openReturnDetailModal, verifyReturnTray, verifyReturnSet, onReturnSuffixInput, resetReturnsFilter,
          showDeclineReturnModal, isSubmittingDeclineReturn, declineReturnForm, openDeclineReturnModal, submitDeclineReturnSet,
          showSessionModal, sessionForm, openSessionModal, openReturnToWarehouseFromAssembly, submitSessionAction,
          showPauseModal, pauseForm, isWorkstationPaused, activePauseReason, pausedDurationDisplay, openPauseModal, submitPauseAssembly, resumeAssemblyWork,
          downtimeLogsList, downtimeFilter, loadDowntimeLogs, resetDowntimeFilter,
          isMobileMenuOpen, sidebarOpen, toggleSidebarGroup, navigateTo,
          defaultAssyTestItems, defaultQCTestItems,
          showAssyTestModal, isSubmittingAssyTest, assyTestForm, openAssyTestModal,
          testingPointsList, isLoadingTestingPoints, isSavingTestingPoint, editingTestingPointId, testingPointFilterQ,
          testingPointForm, loadTestingPoints, resetTestingPointForm, editTestingPoint, saveTestingPoint, deleteTestingPoint,
          handleImageUpload, removeImageFromForm, getTestImagesCount, getTestPointImagesCount, getTestCategory, openInstructionGuideModal,
          showAdminUnsavedModal, adminUnsavedContext, confirmAdminUnsavedNavigation, cancelAdminUnsavedNavigation, isSavingAdminUnsaved,
          showInstructionGuideModal, currentGuideTestPoint, currentGuideImages, guideCarouselIndex, nextGuideSlide, prevGuideSlide,
          isSingleRetestMode, singleRetestTestNum, singleRetestStatus, singleRetestNotes, isSubmittingSingleRetest,
          startSingleRetest, cancelSingleRetest, setSingleRetestStatus, setSingleRetestAndSubmit, submitSingleRetest, assyTotalSteps, currentActiveChecklistItem, filteredTestingPointsList, setAllAssyTestStatus, assyTestCalculatedFailures, assyTestGeneratedFailureReason, submitAssyTest, assyTestStep, chooseAssyTestStatus, prevAssyTestStep, nextAssyTestStep, assyTestTableViewMode, qcTestTableViewMode,
          showAssySaveProgressModal, assyDraftProgress, isSlotTested, getSlotDraftProgress, handleAssyBackNavigation, confirmSaveAssyProgress,
          showEarlyEndButton, showFirstNGPromptModal, showSecondNGPromptModal, handleFirstNGChoice, confirmEndSessionEarly,
          activeAssyQCDocSlot, testedGoodSlots, countTestedGoodInTray, countTestedHoldInTray, countTestedRUInTray, countUntestedInTray, selectAndPrintAssyQC, getTestItemParamStatus,
          getTestingTrayCounts, getTestingSetProgress, getPrintDocSetCount,
          qcPendingList, qcHistoryList, qcAllList, qcStatusFilter, qcCounts, filteredQCList, qcSearchQuery, qcTrayFilter, showQCTestModal, isQCAssyTableExpanded, isSubmittingQCTest, qcTestForm, loadQCPendingItems, isEligibleForQC, isQCHoldItem, isQCPendingItem, distinctQCTrays, filteredQCPendingList, qcPendingCount, qcOverallPendingCount, qcOverallSummary, openQCTestModal, handleHoldItemDetail, submitQCTestDecision, submitQCTest, printWHQCDoc, reregisterDeclinedItem, selectedQCTrayCode, openQCTrayDetail, getParsedAssyChecklist, qcTraySummaryList,
          showWarehouseQCModal, isSubmittingWarehouseQC, warehouseQCForm, openWarehouseQCVerifyModal, submitWarehouseQCVerify,
          assemblyOverallCounts, tf1OverallCounts,
          warehouseReceiptCounts, filteredWarehouseReceiptList,
          selectedReceiptTrayCode, warehouseReceiptTraySummaryList, activeReceiptTrayItems, activeReceiptTraySummary, openReceiptTrayDetail, openTravelerWHQCDoc, openTravelerWHGRDoc, isCurrentTravelerWHQCVerified, isCurrentTravelerQCDone, isSubmittingBatchReceipt, batchAcceptWarehouseReceipt,
          userDepartment, userPermissions, managedMenuGroups, managedMenuList, defaultPermissions, hasMenuAccess, hasGroupAccess, isMenuReadOnly, hasTabAccess, triggerToast, isAdminUser,
          applyUserPermissionPreset, setGroupPermissions, onPermissionChange, countGroupActiveMenus, countUserActiveMenus, countUserWriteMenus, formatDepartmentDisplay, getDepartmentBadgeClass, onRoleOrDeptChange,
          openPermissionCards, togglePermissionCard, toggleAllPermissionCards,
          assemblyResults, isLoadingAssemblyResults, assemblyResultsStatusFilter, loadAssemblyResults, assemblyResultsSummary, getAssemblyStageStatus,
          filteredGroupedAssemblyResults, flatFilteredAssemblyResults,
          pagedAssemblyResults, totalPgAssemblyResults,
          selectedProductionOperator, selectedProductionDocAssy, distinctAssemblyOperators, distinctAssyDocNumbers,
          isAssyDocDropdownOpen, assyDocSearchText, toggleAssyDocDropdown, selectAssyDocFromDropdown, filteredAssyDocNumbers,
          calculateAssemblyDuration, isItemPendingAdmin, showAssemblyDetailModal, selectedAssemblyItem, selectedAssemblyTray, openAssemblyDetailModal, transferSingleSetToWarehouse, confirmReturnTrayToWarehouse, showHoldDetailModal, selectedHoldItem, selectedHoldTray, openHoldDetailModal,
          showReregisterSubModal, reregisterTargetStatus, reregOperator, reregisterReason, isSubmittingReregisterSetAction,
          availableReregisterStatuses, openReregisterSubModal, submitReregisterSetAction,
          showReplacePartModal, replacePartType, replaceOldStatus, replaceNewSN, replaceReason,
          availableReplaceParts, isLoadingReplaceParts, isSubmittingReplacePartAction,
          isReplacePartDropdownOpen, replacePartSearchQuery, filteredReplaceParts, selectReplaceNewSN,
          showAllReplaceParts, problematicPartTypes, isProblemPart, hasNonProblematicParts, getParsedReplacementHistory, getItemProblemReason,
          replaceSelections, replaceDropdownOpen, replaceSearchQueries, availableReplacePartsByType, isLoadingPartsByType,
          getFilteredPartsForType, selectReplaceSNForPart, clearReplaceSNForPart, toggleReplaceDropdown, closeAllReplaceDropdowns, totalPartsToReplace,
          openReplacePartSubModal, closeReplacePartModal, onSelectReplacePartType, submitReplacePartAction,
            isAllProblematicPartsReplaced, getItemPendingLabel, getHoldProceedButtonLabel, getHoldProceedButtonTitle,
          adminApproveAssyRereg, adminReregApprovedOperator, adminReregNotes,
          openReregisterForPart, openReplaceForPart,
          filteredAssemblyReport,
          reportSubTab, setReportSubTab, reportFilters, assyTestReports, qcTestReports, warehouseReceiptReports, loadAssyTestReports, loadQCTestReports, loadWarehouseReceiptReports, filteredWOReport, filteredAssyTestReport, filteredQCTestReport, filteredWarehouseReport, getReportCategoryTitle, resetCurrentReportFilter, exportCurrentReportToExcel,
          reportViewMode, setReportViewMode, selectedReportDoc, modalReportDocItems, openReportDocDetail, openWOUnitDetail, closeReportDocDetail, openItemDetailFromDoc, currentReportList, currentReportDocSummaryList,
          currentReportCounts, reportPipelineSummary, currentReportOperatorOptions, currentReportDocOptions,
          setReportSort, toggleReportSortOrder, getReportSortIconClass, getReportSortLabel, compareReportValues,
          adminApproveHoldItem, parseReplacements, adminApproveReplacement, adminRejectReplacement,
          adminPendingReplacements, adminPendingReplacementsCount, filteredAdminReplacements,
          isReplacementVerified, selectedAdminReplacementDetail, showAdminReplacementDetailModal, openAdminReplacementDetailModal,
          adminRepSearchQuery, adminRepPartTypeFilter, adminRepStatusFilter, adminRepStartDate, adminRepEndDate, resetAdminRepFilter,
          showQCWarehouseActualVerifyModal, qcVerifyTargetTray, qcVerifyItemsList, isSubmittingQCActualVerify, isSubmittingQCItem, verifySingleQCItem, qcActualVerifyNotes, openQCWarehouseActualVerifyModal, submitQCWarehouseActualVerify, goToQCReceiptPrint, isTrayQCReceiptVerified,
          showReregisterSetModal, targetReregisterSet, isSubmittingReregisterSet, isTargetSetRework, isReassemblySet, openReregisterSetConfirmModal, confirmReregisterSet, reregisterNewReworkReason, formatQCReworkReason, formatFailureReason,
          showQCHoldReasonModal, qcHoldReasonInput, openQCHoldReasonModal, confirmQCHold,
          productionHoldList, productionProductsList, selectedProductionProduct, productionSourceFilter, productionSearchQuery, productionViewMode, functionalHoldViewMode, isLoadingProductionHold,
          loadProductionProducts, loadProductionHoldItems, filteredProductionHoldList, productionHoldCounts, productionHoldCount,
          isItemInReassemblyProcess, baseProductionHoldList,
          // DO & PL Module exports
          cloudForm, showCloudSecret, isTestingCloud, cloudTestResult, isSavingCloudSettings,
          isUploadingCloudPdf, isBatchSyncingCloud, batchSyncProgress,
          uploadDOPackingListToCloud, syncAllDOsToCloud, testCloudinaryConnection, saveCloudinarySettings,
          nkriLogoBase64, nkriLabelLogoBase64, nkriEmblemBase64, doSubTab, doTableDisplayMode, doSJCurrentPage, getSuratJalanVisibleCrates, doSettings, isSavingDOSettings, previewDOId, previewDocType, previewCrateIndex, previewDOData, isLoadingPreviewDO, previewCurrentCrate,
          previewPackingListSheets, previewPackingListSheetIndex, previewCurrentPackingListSheet, printPackingListSheets, getSheetPLNumber, renderDOTrackingQRCode, renderSNLabelQRCode, getDOTrackingFullUrl,
          standaloneLabelsList, isLoadingStandaloneLabels, isSavingStandaloneLabel, activeLabelCrateIndex, labelForm, lastSavedPLInfo, resetLabelForm, confirmResetLabelForm, syncLabelPackagingStructure, autoFillAvailableSNsForLabel, loadStandaloneLabels, openStandaloneLabelsHistory, saveStandaloneLabel, printStandaloneLabel, deleteStandaloneLabel,
          getLabelFormFilledCount, getLabelFormRequiredCount, getLabelCrateFilledCount, getLabelCrateRequiredCount, isLabelFormComplete,
          labelSearchText, labelDocFilter, labelPackageFilter, labelStartDate, labelEndDate, distinctPLLabelNumbers, resetLabelFilter, filteredStandaloneLabelsList, pagedStandaloneLabelsList, totalPgStandaloneLabels,
          loadDOSettings, saveDOSettings, applySettingsToNewDOForm, loadDOPreviewData, openDODocumentPreview, doList, doSearchText, filteredDOList, exportDeliveryOrderExcel, customersList, filteredCustomersList,
          doStartDate, doEndDate, doDocFilter, distinctDODocNumbers, resetDOFilter, pagedDOList, totalPgDOList,
          isCustomerDropdownOpen, customerDropdownRef, showNewCustomerModal, newCustomerForm,
          isSubmittingCustomer, customerSearchQuery, availableFinishedSNs, isSubmittingDO,
          showDOPostPublishModal, lastPublishedDO, selectedDOForDetail, isLoadingDODetail, openDODetailView,
          activeFormStep, completedSteps, openFormCards, isFormCardOpen, isStepValid, isStepCompleted,
          canAccessStep, goToFormStep, onCardHeaderClick, toggleFormCard, setAllFormCards, resetDOFormSteps,
          selectedConsumedPLId, selectedConsumedPL, isLoadingConsumedPL, applyConsumedPackingList, resetConsumedPackingList,
          productTypesList, doForm, showBatchPasteModal, batchPasteRawText, batchPasteTarget,
          showSelectCrateModal, showSelectBoxModal, selectTarget, selectCrateSearchQuery, selectBoxSearchQuery, filteredAvailablePLCratesList, filteredAvailablePLBoxesList, getAvailablePLCratesList, isPLCrateUsed, openSelectCrateModal, applySelectedCrateToDO, getAvailablePLBoxesList, isPLBoxUsed, openSelectBoxModal, applySelectedBoxToDO,
          activePrintDO, activePrintType, activeContainerIndex, getBoxFilledCount, isBoxComplete,
          isBoxIndexLocked, isCrateComplete, isCrateIndexLocked, getUnitBatches, getUnitBatchFilledCount,
          isUnitBatchComplete, isUnitBatchLocked, nextContainer, prevContainer, selectContainer,
          isDOSlotLocked, autofillSingleBox, autofillSingleCrate, autofillSingleUnitBatch,
          selectCustomer, openNewCustomerModal, saveNewCustomer, loadCustomers, loadDeliveryOrders,
          loadAvailableFinishedSNs, loadNextDONumber, loadNextBatchNumber, loadNextDOMeta, loadDOList, setPackageType,
          buildPackagingStructure, syncPackagingStructure, buildCratesForQuantity, getAllBoxesInForm, getAllUnitsInForm,
          getTotalFilledSNs, getCrateFilledCount, isSNInList, getAvailableSNsForSlot,
          openBatchPasteModal, applyBatchPaste, addUnitToDOForm, removeUnitFromDOForm, updateUnitCountDirectly, openDODetailAndPrint, deleteDeliveryOrder,
          submitDeliveryOrder, getCratesForPrint, getBoxesForPrint, getUnitsForPrint,
          formatIndoDate, formatNumericDate, currentRealtimePrintDate, updateRealtimePrintDate, getDOSerialNumbers, getDOCrateCount, getDOBoxCount,
          unitDropdown, onUnitInputFocus, onUnitInputBlur, onUnitInputChange, getFilteredUnitSuggestions, selectUnitSN, addUnitRowAutomatically, handleUnitInputKeyDown, removeUnitRowDirectly, autofillAllUnits,
          showMasterPartTrackingModal, selectedMasterPartRow, masterPartTrackingData, isLoadingMasterPartDetail, openMasterPartTrackingModal,
          pendingReassemblyList, selectedReassemblyIds, selectedReassemblyOperator, isLoadingPendingReassembly, isSubmittingReassemblyDispatch, loadPendingReassembly, toggleSelectReassembly, isReassemblySelected, selectAllReassembly, clearSelectedReassembly, submitReassemblyDispatch, openReplaceForHoldItem,
          syncCurrentUserPermissions,
          defaultCrateIdForNow,
          onDODateChanged, getYearMonthFromDate, getNowWIBDateString,
          getItemStartTime, getItemFinishTime, isTravelerItemAssembled,
          cmsActiveTab, cmsLoading, cmsErrorLogs, cmsSlowQueries, cmsAnomalies, cmsScanning, cmsRepairing, cmsDoSettings,
          loadCmsPortalData, fetchCmsErrorLogs, clearCmsErrorLogs, fetchCmsSlowQueries, clearCmsSlowQueries,
          scanCmsAnomalies, repairCmsAnomalies, clearDatabaseTrays, clearDowntimeLogs, clearAuditLogs,
          fetchCmsDoSettings, saveCmsDoSettings
        };
      }
    });
    app.config.globalProperties.window = window;
    app.mount('#app');
