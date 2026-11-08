    const { createApp, ref, computed, nextTick, onMounted, onUnmounted, watch } = Vue;

    const app = createApp({
      setup() {
        if (typeof window !== 'undefined' && window.logger) {
          logger.init('Sistem Final Assembly & Traceability P9', '3.0');
        }
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


        // ===================================================================
        // MODULAR DOMAIN MODULES (FASE 2)
        // ===================================================================
        let assemblyModule = {};
        let deliveryModule = {};
        let qcModule = {};
        let tf1Module = {};
        let holdsModule = {};
        let warehouseModule = {};
        let reportsModule = {};
        let travelerModule = {};

        // Forward references to avoid Temporal Dead Zone (TDZ)
        let loadProductionHoldItems = async () => {};
        let isReassemblySet = () => false;
        let isAssyDocDropdownOpen = ref(false);

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
            key: 'delivery_order',
            title: 'Delivery Order (Logistik & PPIC)',
            icon: 'fa-solid fa-truck-fast',
            badgeClass: 'bg-indigo-100 text-indigo-900 border-indigo-300',
            menus: [
              { key: 'do_dashboard', title: 'Dashboard DO', icon: 'fa-solid fa-chart-pie', desc: 'Pemantauan status pengiriman & grafik produksi' },
              { key: 'do_customers', title: 'Database Customer', icon: 'fa-solid fa-users', desc: 'Data master customer tujuan pengiriman' },
              { key: 'do_packing_list', title: 'Packing List', icon: 'fa-solid fa-boxes-packing', desc: 'Pembuatan & riwayat label packing list' },
              { key: 'do_surat_jalan', title: 'Surat Jalan (DO)', icon: 'fa-solid fa-file-invoice', desc: 'Penerbitan surat jalan & packing list resmi' }
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
          delivery_order: false,
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
            do_dashboard: isAdmin ? 2 : 1,
            do_customers: isAdmin ? 2 : 0,
            do_packing_list: isAdmin ? 2 : 0,
            do_surat_jalan: isAdmin ? 2 : 0,
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
          if (['delivery_order', 'do_dashboard', 'do_customers', 'do_packing_list', 'do_surat_jalan'].includes(menuKey)) {
            return dept.includes('gudang') || dept.includes('warehouse') || dept.includes('delivery') || dept.includes('logistik') || dept.includes('ppic') || r.includes('admin');
          }
          if (dept.includes('gudang') || dept.includes('warehouse') || dept.includes('delivery') || dept.includes('logistik') || dept.includes('ppic')) {
            return ['warehouse', 'work_order', 'production_hold', 'functional_hold', 'returns', 'warehouse_receipt', 'traveler', 'master', 'report', 'history', 'delivery_order', 'do_dashboard', 'do_customers', 'do_packing_list', 'do_surat_jalan'].includes(menuKey);
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
          let tab = '';
          let subTab = '';
          if (window.location.pathname.startsWith('/admin') || window.__IS_ADMIN_PORTAL) {
            return { tab: 'users', subTab: 'cms_portal', queryParams: {} };
          }

          // Clean URL Routing Map (Direct URL bar access)
          const PATH_ROUTE_MAP = {
            '/perakitan': { tab: 'assembly' },
            '/assembly': { tab: 'assembly' },
            '/tf1': { tab: 'assy_testing' },
            '/assy-test': { tab: 'assy_testing' },
            '/qc': { tab: 'qc' },
            '/tf2': { tab: 'qc' },
            '/work-order': { tab: 'warehouse' },
            '/input-part': { tab: 'warehouse' },
            '/gudang': { tab: 'warehouse' },
            '/hasil-perakitan': { tab: 'production_hold' },
            '/hasil-test': { tab: 'functional_hold' },
            '/penerimaan': { tab: 'warehouse_receipt' },
            '/wh-gr': { tab: 'warehouse_receipt' },
            '/rakit-ulang': { tab: 'reassembly' },
            '/pengembalian': { tab: 'returns' },
            '/traveler': { tab: 'traveler' },
            '/cetak': { tab: 'traveler' },
            '/master-stok': { tab: 'master' },
            '/master': { tab: 'master' },
            '/history': { tab: 'report' },
            '/laporan': { tab: 'report' },
            '/delivery': { tab: 'delivery_order', subTab: 'dashboard' },
            '/delivery-order': { tab: 'delivery_order', subTab: 'dashboard' },
            '/packing-list': { tab: 'delivery_order', subTab: 'packing_list' },
            '/packinglist': { tab: 'delivery_order', subTab: 'packing_list' },
            '/pl': { tab: 'delivery_order', subTab: 'packing_list' },
            '/surat-jalan': { tab: 'delivery_order', subTab: 'surat_jalan' },
            '/suratjalan': { tab: 'delivery_order', subTab: 'surat_jalan' },
            '/sj': { tab: 'delivery_order', subTab: 'surat_jalan' },
            '/database-customer': { tab: 'delivery_order', subTab: 'customers' },
            '/database-do': { tab: 'delivery_order', subTab: 'customers' },
            '/customers': { tab: 'delivery_order', subTab: 'customers' },
            '/dashboard-do': { tab: 'delivery_order', subTab: 'dashboard' },
            '/verifikasi': { tab: 'users', subTab: 'verify_set' }
          };

          const rawPath = (window.location.pathname || '').toLowerCase().replace(/\/+$/, '');
          if (PATH_ROUTE_MAP[rawPath]) {
            hash = PATH_ROUTE_MAP[rawPath].tab;
            if (PATH_ROUTE_MAP[rawPath].subTab) {
              subTab = PATH_ROUTE_MAP[rawPath].subTab;
            }
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

        // Live Clock & Time Helpers (Strictly WIB - UTC+7 Asia/Jakarta)
        const getNowWIBDate = () => {
          const now = new Date();
          const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
          return new Date(utc + (7 * 3600000));
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

        const getNowLocalDateTimeStr = () => {
          const w = getNowWIBDate();
          const pad = (n) => String(n).padStart(2, '0');
          return `${w.getFullYear()}-${pad(w.getMonth() + 1)}-${pad(w.getDate())} ${pad(w.getHours())}:${pad(w.getMinutes())}:${pad(w.getSeconds())}`;
        };

        const liveClock = ref(new Date());
        let liveClockTimer = null;

        const liveClockString = computed(() => {
          const d = liveClock.value;
          return d.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        });

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
          delivery_order: (initialRoute && initialRoute.tab === 'delivery_order') || false,
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

        // Global Clean Route Path Mapper for all navigation
        const TAB_CLEAN_URLS = {
          'assembly': '/perakitan',
          'assy_testing': '/tf1',
          'qc': '/qc',
          'warehouse': '/work-order',
          'production_hold': '/hasil-perakitan',
          'functional_hold': '/hasil-test',
          'warehouse_receipt': '/penerimaan',
          'reassembly': '/rakit-ulang',
          'returns': '/pengembalian',
          'traveler': '/traveler',
          'master': '/master-stok',
          'report': '/history',
          'delivery_order': '/delivery',
          'users': '/verifikasi'
        };

        const resolveCleanUrl = (tab, subTab = null) => {
          if (tab === 'delivery_order') {
            const sub = subTab || (deliveryModule && deliveryModule.doSubTab ? deliveryModule.doSubTab.value : 'dashboard');
            if (sub === 'packing_list' || sub === 'label_pl_only' || sub === 'labels_history') return '/packing-list';
            if (sub === 'surat_jalan' || sub === 'form' || sub === 'history') return '/surat-jalan';
            if (sub === 'customers' || sub === 'database_do') return '/database-customer';
            return '/delivery';
          }
          if (tab === 'users') {
            return '/verifikasi';
          }
          if (tab === 'report') {
            return '/history';
          }
          return TAB_CLEAN_URLS[tab] || ('/' + tab);
        };

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
          if (typeof window !== 'undefined' && window.logger) {
            logger.navigate(tab, subTab);
          }

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
            if (reportsModule && typeof reportsModule.initReportsTab === 'function') {
              reportsModule.initReportsTab(targetSub);
            } else {
              if (targetSub === 'wo') {
                if (typeof loadLaporan === 'function') loadLaporan();
                if (typeof loadTrays === 'function') loadTrays();
                if (typeof loadAssemblyResults === 'function' && (!assemblyResults.value || !assemblyResults.value.length)) loadAssemblyResults();
              } else if (targetSub === 'assembly') {
                if (typeof loadAssemblyResults === 'function') loadAssemblyResults();
                if (typeof loadLaporan === 'function') loadLaporan();
              } else if (targetSub === 'assy_test') {
                if (typeof loadAssyTestReports === 'function') loadAssyTestReports();
              } else if (targetSub === 'qc_test') {
                if (typeof loadQCTestReports === 'function') loadQCTestReports();
              } else if (targetSub === 'warehouse_receipt') {
                if (typeof loadWarehouseReceiptReports === 'function') loadWarehouseReceiptReports();
              }
            }
            // Load active subtab only (lazy on-demand)
          } else {
            targetHash = '#' + tab;
            targetKey = tab;
            if (tab === 'qc') {
              if (qcModule && typeof qcModule.initQCTab === 'function') {
                qcModule.initQCTab();
              } else if (typeof loadQCPendingItems === 'function') {
                loadQCPendingItems();
              }
            } else if (tab === 'delivery_order') {
              targetHash = subTab ? `#delivery_order/${subTab}` : '#delivery_order';
              targetKey = subTab ? `delivery_order/${subTab}` : 'delivery_order';
              if (subTab) {
                if (deliveryModule && typeof deliveryModule.setDoSubTab === 'function') {
                  deliveryModule.setDoSubTab(subTab);
                } else if (deliveryModule && deliveryModule.doSubTab) {
                  deliveryModule.doSubTab.value = subTab;
                }
              }
              if (deliveryModule && typeof deliveryModule.initDeliveryTab === 'function') {
                deliveryModule.initDeliveryTab(subTab);
              }
            } else if (tab === 'master') {
              loadMasterData();
            } else if (tab === 'add_part') {
              loadRecentlyAddedParts();
            } else if (tab === 'warehouse') {
              loadTrays();
              loadOperatorsList();
            } else if (tab === 'returns') {
              loadReturnedTrays();
              loadOperatorsList();
            } else if (tab === 'reassembly') {
              if (holdsModule && typeof holdsModule.initReassemblyTab === 'function') {
                holdsModule.initReassemblyTab();
              } else if (typeof loadPendingReassembly === 'function') {
                loadPendingReassembly();
              }
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
              if (holdsModule && typeof holdsModule.initProductionHoldTab === 'function') {
                holdsModule.initProductionHoldTab();
              } else {
                loadAssemblyResults();
                loadProductionHoldItems();
                loadProductionProducts();
              }
            } else if (tab === 'functional_hold') {
              if (holdsModule && typeof holdsModule.initFunctionalHoldTab === 'function') {
                holdsModule.initFunctionalHoldTab();
              } else {
                loadProductionHoldItems();
                loadProductionProducts();
              }
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

          // Clean URL Address Bar Resolver
          const targetUrl = resolveCleanUrl(tab, subTab);

          // Track sequence for back navigation across all roles & tabs
          if (!isFromPopState) {
            const last = _tabNavStack[_tabNavStack.length - 1];
            if (!last || last.key !== targetKey) {
              _tabNavStack.push({ tab, subTab: subTab || null, key: targetKey });
              try {
                history.pushState({ type: 'tab', key: targetKey, tab, subTab }, '', targetUrl);
              } catch (_) { }
            } else {
              try {
                history.replaceState({ type: 'tab', key: targetKey, tab, subTab }, '', targetUrl);
              } catch (_) { }
            }
          } else {
            try {
              history.replaceState({ type: 'tab', key: targetKey, tab, subTab }, '', targetUrl);
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

        const getModuleFromApiPath = (p) => {
          if (!p) return 'API';
          const lp = String(p).toLowerCase();
          if (lp.includes('assembly')) return 'PERAKITAN';
          if (lp.includes('tf1') || lp.includes('functional') || lp.includes('assy-test')) return 'TF1';
          if (lp.includes('qc') || lp.includes('tf2')) return 'TF2/QC';
          if (lp.includes('warehouse') || lp.includes('receipts') || lp.includes('supply') || lp.includes('returns')) return 'GUDANG';
          if (lp.includes('report') || lp.includes('laporan')) return 'LAPORAN';
          if (lp.includes('delivery')) return 'DELIVERY';
          if (lp.includes('hold')) return 'HOLD';
          if (lp.includes('traveler')) return 'TRAVELER';
          if (lp.includes('admin') || lp.includes('user') || lp.includes('audit') || lp.includes('downtime')) return 'ADMIN';
          return 'SISTEM';
        };

        // API Helper with Auto-Retry for Transient Gateway Glitches
        const fetchAPI = async (path, options = {}, retryCount = 0) => {
          const token = localStorage.getItem('token');
          if (!options.headers) options.headers = {};
          options.headers['Content-Type'] = 'application/json';
          options.headers['Accept'] = 'application/json';
          options.headers['ngrok-skip-browser-warning'] = 'true';
          if (token) options.headers['Authorization'] = 'Bearer ' + token;

          const actUser = (typeof userName !== 'undefined' && userName?.value) ? userName.value : (localStorage.getItem('username') || '');
          const actFull = (typeof userFullName !== 'undefined' && userFullName?.value) ? userFullName.value : (localStorage.getItem('full_name') || '');
          const actRole = (typeof userRole !== 'undefined' && userRole?.value) ? userRole.value : (localStorage.getItem('role') || '');
          if (actUser) options.headers['X-Actor-Username'] = actUser;
          if (actFull) options.headers['X-Actor-Fullname'] = encodeURIComponent(actFull);
          if (actRole) options.headers['X-Actor-Role'] = actRole;

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
            const method = (options.method || 'GET').toUpperCase();
            const modName = getModuleFromApiPath(path);

            const actionLabel = (typeof window !== 'undefined' && window.logger && typeof logger.getActionLabel === 'function')
              ? logger.getActionLabel(method, path)
              : (method + ' ' + path);

            if (!res.ok || (json && json.success === false)) {
              if (typeof window !== 'undefined' && window.logger) {
                const errMsg = (json && (json.message || json.error)) || ('HTTP ' + res.status);
                logger.error(modName, actionLabel, {
                  status: res.status,
                  url: method + ' /api' + path,
                  message: errMsg,
                  details: json
                });
              }
            } else if (method !== 'GET') {
              if (typeof window !== 'undefined' && window.logger) {
                const sMsg = (json && json.message) || (actionLabel + ' berhasil');
                logger.success(modName, sMsg);
              }
            }
            return json;
          } catch (jsonErr) {
            console.error('JSON parse error:', jsonErr);
            if (!res.ok) {
              throw new Error(`Kesalahan Server (${res.status}): Format data respon tidak dapat dibaca.`);
            }
            throw new Error('Gagal memproses data server: Format JSON dari server tidak valid.');
          }
        };

        // =========================================================
        // PAGINATION STATE (Moved up for modular modules)
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
          recentAdded: { page: 1, size: 100 },
        });


                // ===================================================================
        // SHARED UTILITIES & HOISTED CROSS-MODULE REFS
        // ===================================================================
        const showToast = (message, type = 'info') => {
          triggerToast('', message, type);
        };

        const isStatusGood = (status) => {
          const s = String(status || '').toUpperCase().trim();
          return ['GOOD', 'GOOD_QC', 'PASS', 'PASSED', 'ACC', 'ACCEPTED', 'APPROVED', 'APPROVED_QC'].includes(s);
        };

        const getEffectiveFinishedAt = (start, finish) => {
          if (!finish) return finish;
          if (!start) return finish;
          try {
            const d1 = parseWIBDate(start);
            let d2 = parseWIBDate(finish);
            if (d1 && d2 && !isNaN(d1.getTime()) && !isNaN(d2.getTime()) && d2 < d1) {
              const diff = d1.getTime() - d2.getTime();
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
            if (sl.includes('rework') || sl.includes('hold')) return 'Hold';
            if (sl.includes('scrap')) return 'Scrap';
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
            if (low === 'hold' || low === 'rework') return 'Hold';
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

        // Shared cross-module reactive state refs
        const activeAssyQCDocSlot = ref(null);
        const warehouseReceiptReports = ref([]);
        const selectedReceiptTrayCode = ref('');
        const selectedTravelerTrayCode = ref('');
        const currentLaporanSubTab = ref('dokumen');

        // ===================================================================
        // INITIALIZE DELIVERY ORDER & PACKING LIST MODULE (FASE 2)
        // ===================================================================
        if (typeof window.createDeliveryModule === 'function') {
          deliveryModule = window.createDeliveryModule({
            currentTab,
            initialSubTab: (initialRoute.tab === 'delivery_order' && initialRoute.subTab) ? initialRoute.subTab : null,
            fetchAPI,
            triggerToast,
            showConfirmCard,
            pgState,
            userRole,
            operatorName,
            userFullName,
            getNowWIBDateString,
            getYearMonthFromDate
          });
        }


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

        // Trays State & Searchable Dropdown moved to public/js/modules/assembly.js (Fase 2E)

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

        // ===================================================================
        // 10. AREA PERAKITAN (ASSEMBLY) MODULE (FASE 2E)
        // ===================================================================
        if (typeof window.createAssemblyModule === 'function') {
          assemblyModule = window.createAssemblyModule({
            Vue,
            fetchAPI,
            triggerToast,
            showConfirmCard,
            userRole,
            operatorName,
            userFullName,
            currentTab,
            getOperatorFullName,
            getNowTimeStr,
            getNowLocalDateTimeStr,
            formatTimeInput,
            openLabelPreview: (...args) => (typeof openLabelPreview === 'function' ? openLabelPreview(...args) : null),
            openDetailModal: (...args) => (typeof openDetailModal === 'function' ? openDetailModal(...args) : null),
            loadAssemblyResults: (...args) => (typeof loadAssemblyResults === 'function' ? loadAssemblyResults(...args) : null),
            loadProductionHoldItems: (...args) => (typeof loadProductionHoldItems === 'function' ? loadProductionHoldItems(...args) : null),
            loadReturnedTrays: (...args) => (typeof loadReturnedTrays === 'function' ? loadReturnedTrays(...args) : null),
            loadLaporan: (...args) => (typeof loadLaporan === 'function' ? loadLaporan(...args) : null),
            loadMasterData: (...args) => (typeof loadMasterData === 'function' ? loadMasterData(...args) : null),
            liveClock
          });
        }

        // Bridge refs/methods for internal app.js usage
        const {
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
        } = assemblyModule;

        // filteredTraysList moved to public/js/modules/assembly.js (Fase 2E)

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
            good += (t.good_assembly_count || t.good_count || 0);
            hold += (t.hold_count || 0);
            scrap += (t.scrap_count || 0);
            total += (t.finished_count || 0);
          });
          const sumCats = good + hold + scrap;
          if (total !== sumCats && sumCats > 0) {
            total = sumCats;
          }
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
            const tGoodAssy = Number(t.good_assembly_count || t.good_count || 0);
            const tTested = Number(t.tested_count || 0);
            const tPassed = Number(t.good_assy_test_count !== undefined ? t.good_assy_test_count : (tTested - (t.hold_assy_test_count || 0)));
            const tHold = Number(t.hold_assy_test_count || 0);
            passed += tPassed;
            hold += tHold;
            total += (tPassed + tHold);
            pending += Math.max(0, tGoodAssy - (tPassed + tHold));
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

        // Assembly Form State moved to public/js/modules/assembly.js (Fase 2E)

        // Label & Traveler Slip state moved to public/js/modules/traveler.js (Fase 2I)

        // Laporan Traceability State & Filters moved to public/js/modules/reports.js (Fase 2H)

        // ==========================================
        // 14. ADMIN & MASTER PENGATURAN MODULE (FASE 2J)
        // ==========================================
        let adminModule = {};
        if (typeof window.createAdminModule === 'function') {
          adminModule = window.createAdminModule({
            Vue,
            fetchAPI,
            triggerToast,
            showToast,
            showConfirmCard,
            showPromptCard,
            getTodayDateStr,
            formatDateTime,
            getOperatorFullName,
            pgState,
            userRole,
            operatorName,
            userName,
            userPermissions,
            currentTab,
            adminSubTab,
            navigateTo,
            currentTray,
            currentTrayParts,
            currentTrayAssemblies,
            selectedTrayCode,
            inProgressMap,
            defaultPermissions,
            parsePermValue,
            openPermissionCards,
            loadOperatorsList: (...args) => (typeof loadOperatorsList === 'function' ? loadOperatorsList(...args) : null),
            formatExcelDateTime,
            showAdminUnsavedModal,
            adminUnsavedContext,
            pendingAdminNavigation,
            isSavingAdminUnsaved,
            bypassAdminUnsavedCheck,
            getSelectedAssemblyItem: () => (typeof holdsModule !== 'undefined' && holdsModule?.selectedAssemblyItem ? holdsModule.selectedAssemblyItem.value : null),
            getSelectedHoldItem: () => (typeof holdsModule !== 'undefined' && holdsModule?.selectedHoldItem ? holdsModule.selectedHoldItem.value : null),
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            loadQCPendingItems: (...args) => (typeof loadQCPendingItems === 'function' ? loadQCPendingItems(...args) : null),
            loadProductionHoldItems: (...args) => (typeof loadProductionHoldItems === 'function' ? loadProductionHoldItems(...args) : null),
            loadPendingReassembly: (...args) => (typeof loadPendingReassembly === 'function' ? loadPendingReassembly(...args) : null),
            loadAssemblyResults: (...args) => (typeof loadAssemblyResults === 'function' ? loadAssemblyResults(...args) : null),
            loadTravelerTray: (...args) => (typeof loadTravelerTray === 'function' ? loadTravelerTray(...args) : null),
            isReassemblySet: (...args) => (typeof isReassemblySet === 'function' ? isReassemblySet(...args) : false)
          });
        }

        // Bridge refs/methods for internal app.js usage
        const {
          isUserFormDirty: adminIsUserFormDirty,
          isTestingPointFormDirty: adminIsTestingPointFormDirty,
          confirmAdminUnsavedNavigation: adminConfirmAdminUnsavedNavigation,
          cancelAdminUnsavedNavigation: adminCancelAdminUnsavedNavigation,
          testingPointsList,
          userForm,
          loadMasterData,
          loadMasterStocksSummary,
          fetchMasterStockList,
          loadUsersList,
          loadTestingPoints,
          loadAuditLogs,
          loadDowntimeLogs,
          loadPendingVerifications,
          loadRecentlyAddedParts,
          resetUserForm,
          openReregisterModal,
          openReregisterSetConfirmModal,
          showResetDbModal,
          isResettingDb,
          showAdminVerifyModal,
          showAdminVerifySetModal,
          showInstructionGuideModal,
          showLaporanDetailModal,
          showMasterPartTrackingModal,
          showOverrideModal,
          showReregisterModal,
          showReregisterSetModal,
          showStatusDrillModal
        } = adminModule;

        if (adminIsUserFormDirty) isUserFormDirty = adminIsUserFormDirty;
        if (adminIsTestingPointFormDirty) isTestingPointFormDirty = adminIsTestingPointFormDirty;
        if (adminConfirmAdminUnsavedNavigation) confirmAdminUnsavedNavigation = adminConfirmAdminUnsavedNavigation;
        if (adminCancelAdminUnsavedNavigation) cancelAdminUnsavedNavigation = adminCancelAdminUnsavedNavigation;

        // Slot Calculation & Parts Availability moved to public/js/modules/assembly.js (Fase 2E)

        // ==========================================
        // 13. TRAVELER & DOKUMEN CETAK MODULE (FASE 2I)
        // ==========================================
        if (typeof window.createTravelerModule === 'function') {
          travelerModule = window.createTravelerModule({
            Vue,
            fetchAPI,
            triggerToast,
            showPromptCard,
            currentTab,
            selectedTrayCode,
            currentTray,
            currentTrayAssemblies,
            traysList,
            traySlots,
            activeAssyQCDocSlot,
            warehouseReceiptReports,
            isCurrentTravelerWHQCVerified: computed(() => warehouseModule?.isCurrentTravelerWHQCVerified?.value ?? true),
            isCurrentTravelerQCDone: computed(() => warehouseModule?.isCurrentTravelerQCDone?.value ?? true),
            selectedReceiptTrayCode,
            activeReceiptTraySummary: computed(() => warehouseModule?.activeReceiptTraySummary?.value ?? null),
            getOperatorFullName,
            getTodayDateStr,
            formatDateOnly,
            formatTimeOnly,
            parseWIBDate,
            isStatusGood,
            getTravelerItemActualStatus: (...args) => (typeof getTravelerItemActualStatus === 'function' ? getTravelerItemActualStatus(...args) : '-'),
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            loadAssemblyTray: (...args) => (typeof loadAssemblyTray === 'function' ? loadAssemblyTray(...args) : null),
            loadWarehouseReceiptReports: (...args) => (typeof loadWarehouseReceiptReports === 'function' ? loadWarehouseReceiptReports(...args) : null),
            navigateTo: (...args) => (typeof navigateTo === 'function' ? navigateTo(...args) : null),
            switchTab: (...args) => (typeof switchTab === 'function' ? switchTab(...args) : null)
          });
        }

        // Bridge travelerModule methods & state for internal app.js usage
        const {
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
        } = travelerModule;

        // Audit Trail filter moved to public/js/modules/admin.js (Fase 2J)

        // Traveler items & document computations moved to public/js/modules/traveler.js (Fase 2I)

        // filteredLaporan moved to public/js/modules/reports.js (Fase 2H)

        // Assembly Live Polling Timer moved to public/js/modules/assembly.js (Fase 2E)

        // Tab Switching — Unified through navigateTo so history is always tracked
        const switchTab = (tab, subTab = null, isFromPopState = false) => {
          navigateTo(tab, subTab, isFromPopState);
        };

        // Load Data Helpers
        // loadTrays & loadAssemblyTray moved to public/js/modules/assembly.js (Fase 2E)

        watch(selectedTrayCode, async (newCode) => {
          if (newCode && (currentTab.value === 'assembly' || currentTab.value === 'assy_testing')) {
            if (!currentTray.value || currentTray.value.tray_code !== newCode) {
              await loadAssemblyTray();
            }
          }
        });

        // loadTravelerTray & updateTravelerMO moved to public/js/modules/traveler.js (Fase 2I)

        // loadLaporan moved to public/js/modules/reports.js (Fase 2H)

        // Master Stocks summary & status drilldown moved to public/js/modules/admin.js (Fase 2J)

        // Modal & Action State Variables
        const showDetailModal = ref(false);
        const activeDetailSlot = ref(null);
        // Quick Status & Quick Finish moved to public/js/modules/assembly.js (Fase 2E)

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
          let context = explicitContext;
          if (!context) {
            if (currentTab.value === 'report') {
              context = reportSubTab.value || 'assembly';
            } else if (currentTab.value === 'assy_testing') {
              context = 'assy_test';
            } else if (currentTab.value === 'qc') {
              context = 'qc_test';
            } else if (currentTab.value === 'warehouse_receipt' || currentTab.value === 'gudang') {
              context = 'warehouse_receipt';
            } else if (currentTab.value === 'assembly' || currentTab.value === 'perakitan') {
              context = 'assembly';
            } else if (slot.process_stage) {
              context = slot.process_stage;
            } else if (slot.stage === 'QC') {
              context = 'qc_test';
            } else if (slot.stage === 'ASSY') {
              context = 'assy_test';
            } else if (slot.warehouse_receipt_by || slot.warehouse_doc_number || slot.verified_by_user) {
              context = 'warehouse_receipt';
            } else {
              context = 'assembly';
            }
          }

          // 1. Parse TF1 Checklist (17 Parameter Uji Fungsional P9)
          const p9TF1Defaults = [
            { num: 1, name: "1. Pengecekan visual secara menyeluruh", category: "Cek Visual", desc: "- Penebalan pada hasil pengecatan\n- Retak pada area yang berpasangan dengan part berbahan metal" },
            { num: 2, name: "1. Cek upper dan lower secara horizontal", category: "Cek Rigiditas", desc: "Tidak mengeluarkan suara akibat hasil rakitan longgar" },
            { num: 3, name: "2. Cek front sight dan rear sight", category: "Cek Rigiditas", desc: "Tidak boleh bergeser saat digoyangkan" },
            { num: 4, name: "1. Uji pelepas magasen sebanyak 3x", category: "Cek Mekanik", desc: "Magasen tidak jatuh/lepas setelah dikunci; Magasen dapat masuk-keluar dengan baik saat pelepas magasen ditekan" },
            { num: 5, name: "2. Uji kokang sebanyak 3x", category: "Cek Mekanik", desc: "Tidak menyangkut ataupun tersendat ketika dikokang dan dapat kembali dengan lancar" },
            { num: 6, name: "3. Uji picu mengaktifkan pemukul", category: "Cek Mekanik", desc: "Pemukul dapat memukul pena pukul saat picu ditarik" },
            { num: 7, name: "4. Uji pemukul dan picu", category: "Cek Mekanik", desc: "Pemukul dapat memukul pena pukul" },
            { num: 8, name: "5. Uji pemukul 1/2 tegang", category: "Cek Mekanik", desc: "Pemukul dapat terkunci pada kondisi 1/2 tegang dan memukul pena pukul saat picu ditarik" },
            { num: 9, name: "6. Uji pemukul penuh", category: "Cek Mekanik", desc: "Pemukul dapat memukul pena pukul saat ditarik penuh" },
            { num: 10, name: "7. Uji dorong pemukul kedepan", category: "Cek Mekanik", desc: "Pemukul tidak boleh memukul pena pukul saat didorong kedepan" },
            { num: 11, name: "7.1. Uji tuas pengaman aktif", category: "Cek Mekanik", desc: "Pemukul tidak boleh memukul pena pukul saat tuas pengaman didorong keatas" },
            { num: 12, name: "7.1.1 Uji tuas pengaman normal", category: "Cek Mekanik", desc: "Tuas pengaman dapat dikembalikan ke posisi normal dengan lancar" },
            { num: 13, name: "7.2 Uji kunci eretan", category: "Cek Mekanik", desc: "Eretan dapat terkunci dengan baik dan tidak bergerak maju kedepan" },
            { num: 14, name: "7.3 Uji turunkan kunci eretan", category: "Cek Mekanik", desc: "Eretan dapat maju kedepan dengan baik dan tidak tersendat" },
            { num: 15, name: "8. Uji tuas pembongkar keatas", category: "Cek Mekanik", desc: "Tuas pembongkar dapat diputar keatas saat kunci eretan aktif" },
            { num: 16, name: "8.1 Uji kembalikan tuas pembongkar", category: "Cek Mekanik", desc: "Tuas pembongkar dan kunci eretan dapat dikembalikan ke posisi awal" },
            { num: 17, name: "9. Uji rigiditas laras", category: "Cek Mekanik", desc: "Laras tidak dapat bergerak mundur ke belakang saat didorong dari depan" }
          ];

          let rawTF1 = slot.tf1_test_checklist || slot.assy_test_checklist || slot.test_checklist;
          let parsedTF1 = null;
          if (Array.isArray(rawTF1)) parsedTF1 = rawTF1;
          else if (typeof rawTF1 === 'string') {
            try {
              parsedTF1 = JSON.parse(rawTF1);
              if (typeof parsedTF1 === 'string') {
                try { parsedTF1 = JSON.parse(parsedTF1); } catch (_) {}
              }
            } catch (_) { parsedTF1 = null; }
          }

          const hasActualTF1Checklist = Array.isArray(parsedTF1) && parsedTF1.length > 0;
          const hasValidTF1Status = Boolean(
            (slot.assy_test_status && !['PENDING', 'Pending', 'Belum Diuji'].includes(slot.assy_test_status)) ||
            (context === 'assy_test' && slot.status && !['PENDING', 'Pending', 'Belum Diuji', 'Menunggu Test Functional 1'].includes(slot.status))
          );
          const hasValidTF1Time = Boolean(slot.assy_tested_at || slot.tested_at || slot.created_at);
          const isAlreadyInNextStage = (context === 'qc_test' || context === 'warehouse_receipt' || slot.stage === 'QC' || Boolean(slot.qc_status && slot.qc_status !== 'PENDING') || Boolean(slot.qc_tested_at));

          // Set rakitan yang sudah diuji TF 1
          const isTF1Tested = hasActualTF1Checklist ||
            (context === 'assy_test' && hasValidTF1Status) ||
            (hasValidTF1Time && hasValidTF1Status) ||
            (isAlreadyInNextStage && slot.assy_test_status !== 'PENDING' && slot.assy_test_status !== 'Belum Diuji');

          let finalTF1Checklist = [];
          if (hasActualTF1Checklist) {
            finalTF1Checklist = parsedTF1.map((c, idx) => {
              const tNum = Number(c.test_num || c.no || (idx + 1));
              const def = p9TF1Defaults[tNum - 1] || {};
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
                test_num: tNum,
                name: c.name || def.name || `Uji #${tNum}`,
                category: c.category || def.category || 'Cek Mekanik',
                description: c.description || def.desc || '',
                status: mappedStatus,
                notes: c.notes || ''
              };
            });
          } else if (isTF1Tested && (slot.assy_test_status === 'Good' || slot.status === 'Good' || slot.status === 'OK' || isAlreadyInNextStage)) {
            finalTF1Checklist = p9TF1Defaults.map(def => ({
              test_num: def.num,
              name: def.name,
              category: def.category,
              description: def.desc,
              status: 'GOOD',
              notes: ''
            }));
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

          let slotStatus = 'Good';
          if (context === 'assembly') {
            slotStatus = slot.original_assembly_status || (slot.assembly_status && !['Dirakit Ulang', 'RU'].includes(slot.assembly_status) ? slot.assembly_status : null) || (slot.status && !['Dirakit Ulang', 'RU'].includes(slot.status) ? slot.status : null) || 'Good';
          } else if (context === 'assy_test') {
            slotStatus = slot.assy_test_status || (slot.status && !['Dirakit Ulang', 'RU'].includes(slot.status) ? slot.status : null) || 'Good';
          } else if (context === 'qc_test') {
            slotStatus = slot.qc_test_status || slot.qc_status || (slot.status && !['Dirakit Ulang', 'RU'].includes(slot.status) ? slot.status : null) || 'Good';
          } else if (context === 'warehouse_receipt') {
            slotStatus = slot.warehouse_receipt_status || slot.verified_status || (slot.status && !['Dirakit Ulang', 'RU'].includes(slot.status) ? slot.status : null) || 'Good';
          } else {
            slotStatus = slot.status || slot.assembly_status || 'Good';
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
            is_tf1_tested: isTF1Tested,
            index: index,
            process_stage: context,
            status: slotStatus,
            started_at: slot.started_at || slot.created_at || slot.tray_created_at,
            finished_at: slot.finished_at || slot.created_at,
            tested_at: slot.tested_at || slot.assy_tested_at || slot.created_at,
            assy_tested_at: slot.assy_tested_at || slot.tested_at || slot.created_at || null,
            assy_test_status: slot.assy_test_status || (context === 'assy_test' ? slot.status : null),
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

        const isTF1ResultCardVisible = (slot) => {
          if (!slot) return false;
          // Di modal perakitan atau work order, HANYA tampilkan hasil perakitan saja (jangan tampilkan TF 1)
          if (slot.process_stage === 'assembly' || slot.process_stage === 'wo') return false;
          if (currentTab.value === 'assembly') return false;
          if (currentTab.value === 'report' && (reportSubTab.value === 'assembly' || reportSubTab.value === 'wo')) return false;

          // Muncul pada stage TF 1 (assy_test), TF 2 / QC (qc_test), dan Penerimaan Gudang (warehouse_receipt)
          // Jika belum diuji, card TF 1 akan otomatis menampilkan status "Menunggu Uji TF 1" (Pending)
          if (slot.process_stage === 'assy_test' || slot.process_stage === 'qc_test' || slot.process_stage === 'warehouse_receipt') return true;
          if (currentTab.value === 'assy_testing' || currentTab.value === 'qc' || currentTab.value === 'warehouse_receipt') return true;
          if (currentTab.value === 'report' && ['assy_test', 'qc_test', 'warehouse_receipt'].includes(reportSubTab.value)) return true;

          return Boolean(slot.is_tf1_tested || slot.tf1_doc_number || (slot.parsed_tf1_checklist && slot.parsed_tf1_checklist.length));
        };

        const isWarehouseReceiptVisible = (slot) => {
          if (!slot) return false;
          // Di modal perakitan, TF 1, QC / TF 2, atau WO, jangan tampilkan card verifikasi penerimaan gudang
          if (slot.process_stage === 'assembly' || slot.process_stage === 'assy_test' || slot.process_stage === 'qc_test' || slot.process_stage === 'wo') {
            return false;
          }
          if (currentTab.value === 'assembly' || currentTab.value === 'assy_testing' || currentTab.value === 'qc') {
            return false;
          }
          if (currentTab.value === 'report' && (reportSubTab.value === 'assembly' || reportSubTab.value === 'assy_test' || reportSubTab.value === 'qc_test' || reportSubTab.value === 'wo')) {
            return false;
          }

          // Muncul HANYA pada stage Penerimaan Gudang (warehouse_receipt)
          // Jika belum diverifikasi, card Gudang akan otomatis menampilkan status "MENUNGGU RECEIPTS (WH/GR)" (Pending)
          if (slot.process_stage === 'warehouse_receipt') return true;
          if (currentTab.value === 'warehouse_receipt') return true;
          if (currentTab.value === 'report' && reportSubTab.value === 'warehouse_receipt') return true;

          return Boolean(slot.warehouse_receipt_status || slot.warehouse_doc_number || slot.warehouse_receipt_by || slot.warehouse_receipt_at);
        };

        const isQCResultCardVisible = (slot) => {
          if (!slot) return false;
          // Di modal perakitan, TF 1, atau WO, jangan tampilkan card QC
          if (slot.process_stage === 'assembly' || slot.process_stage === 'assy_test' || slot.process_stage === 'wo') return false;
          if (currentTab.value === 'assembly' || currentTab.value === 'assy_testing') return false;
          if (currentTab.value === 'report' && (reportSubTab.value === 'assembly' || reportSubTab.value === 'assy_test' || reportSubTab.value === 'wo')) return false;

          // Muncul pada stage TF 2 / QC (qc_test) dan Penerimaan Gudang (warehouse_receipt)
          // Jika belum diuji, card QC akan otomatis menampilkan status "Menunggu QC" (Pending)
          if (slot.process_stage === 'qc_test' || slot.process_stage === 'warehouse_receipt') return true;
          if (currentTab.value === 'qc' || currentTab.value === 'warehouse_receipt') return true;
          if (currentTab.value === 'report' && ['qc_test', 'warehouse_receipt'].includes(reportSubTab.value)) return true;

          return Boolean(slot.qc_doc_number || slot.qc_test_doc_number || slot.qc_status || slot.qc_test_status || (Array.isArray(slot.parsed_tf2_checklist) && slot.parsed_tf2_checklist.length > 0));
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

        // renderQRCodeDetail moved to public/js/modules/traveler.js (Fase 2I)

        // formatExcelDateTime & getShiftName hoisted above (Fase 2)

        const exportTrayToExcel = async (code = null) => {
          triggerToast('Info', 'Format ekspor Traveler tersedia melalui cetak PDF/ZPL pada tab Traveler.', 'info');
        };

        const exportLaporanToExcel = async () => {
          if (typeof reportsModule.exportMultiReportExcel === 'function') {
            await reportsModule.exportMultiReportExcel();
          } else {
            triggerToast('Info', 'Gunakan tombol Ekspor Excel pada tab Laporan.', 'info');
          }
        };

        const exportToExcel = exportLaporanToExcel;

        // exportDeliveryOrderExcel moved to public/js/modules/delivery.js

        onMounted(() => {
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
            if (typeof isAssyDocDropdownOpen !== 'undefined' && isAssyDocDropdownOpen && isAssyDocDropdownOpen.value) {
              const elDoc = document.getElementById('assyDocDropdownContainer');
              if (elDoc && !elDoc.contains(e.target)) {
                isAssyDocDropdownOpen.value = false;
              }
            }
          });
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

        const getSetEffectiveStatus = (slot) => {
          if (!slot) return '';
          // Mengacu murni pada status rakitan set (original_assembly_status || status)
          const raw = String(slot.original_assembly_status || slot.status || '').trim().toLowerCase();
          if (raw.includes('good') || raw === 'tersedia') return 'good';
          if (raw.includes('hold') || raw === 'tertahan') return 'hold';
          if (raw.includes('scrap') || raw === 'afkir' || raw.includes('decline')) return 'scrap';
          if (raw.includes('rework') || raw.includes('ru') || raw.includes('dirakit ulang')) return 'rework';
          return raw;
        };

        const isSetAllocatedForReassembly = (slot) => {
          if (!slot) return false;
          // 1. Jika tray yang sedang dibuka adalah tray PU itu sendiri:
          if (currentTray && currentTray.value && (/-PU/i.test(currentTray.value.tray_code || '') || /\/PU\//i.test(currentTray.value.tray_code || ''))) {
            return true;
          }
          // 2. Jika set di tray lama sudah dialokasikan ke operator / tray PU (reregistered):
          const isRereg = Boolean(slot.is_reregistered) && (slot.is_reregistered === 1 || slot.is_reregistered === true || slot.is_reregistered === '1');
          const hasSuffix = Boolean(slot.rereg_wo_suffix && String(slot.rereg_wo_suffix).trim());
          const hasOp = Boolean(slot.rereg_pending_operator && String(slot.rereg_pending_operator).trim());
          return isRereg || hasSuffix || hasOp;
        };

        const isSetPendingReassemblyAllocation = (slot) => {
          if (!slot) return false;
          if (isSetAllocatedForReassembly(slot)) return false;

          const st = String(slot.status || '').trim().toLowerCase();
          const vStat = String(slot.verified_status || '').trim().toUpperCase();
          const qcSt = String(slot.qc_status || '').trim().toUpperCase();
          const assyTestSt = String(slot.assy_test_status || '').trim().toUpperCase();

          if (['WAITING_REASSEMBLY', 'APPROVED_REASSEMBLY'].includes(vStat)) return true;
          if (st === 'dirakit ulang' || st === 'ru' || qcSt === 'RU' || assyTestSt === 'RU') return true;
          if (typeof isReassemblySet === 'function' && isReassemblySet(slot)) return true;
          return false;
        };

        const getSetPUDocNumber = (slot) => {
          if (!slot || !isSetAllocatedForReassembly(slot)) return '';
          if (slot.pu_doc_number) return slot.pu_doc_number;
          if (slot.rereg_wo_suffix) {
            const num = String(slot.rereg_wo_suffix).replace(/\D/g, '').padStart(6, '0') || '000001';
            return `WH/ASSY/PU/${num}`;
          }
          if (currentTray && currentTray.value && (/-PU/i.test(currentTray.value.tray_code || '') || /\/PU\//i.test(currentTray.value.tray_code || ''))) {
            return getFormattedDocCode(currentTray.value.tray_code, 'assy_rework');
          }
          return '';
        };

        const getDefectivePartClass = (slot, partType) => {
          if (!isPartDefective(slot, partType)) {
            const eff = getSetEffectiveStatus(slot);
            if (eff && eff !== 'good') {
              return 'text-slate-700 bg-white/90 border border-slate-200';
            }
            return 'text-slate-600 bg-slate-50 border-slate-100';
          }
          const eff = getSetEffectiveStatus(slot);
          if (eff === 'hold') return 'border-2 border-amber-500 bg-amber-50 text-amber-950 font-bold shadow-xs';
          if (eff === 'scrap') return 'border-2 border-red-500 bg-red-50 text-red-950 font-bold shadow-xs';
          if (eff === 'rework') return 'border-2 border-purple-500 bg-purple-50 text-purple-950 font-bold shadow-xs';
          return 'border-2 border-red-500 bg-red-50 text-red-950 font-bold shadow-xs';
        };

        const getDefectiveLabelClass = (slot, partType) => {
          if (!isPartDefective(slot, partType)) return 'text-slate-400';
          const eff = getSetEffectiveStatus(slot);
          if (eff === 'hold') return 'text-amber-800 font-extrabold';
          if (eff === 'scrap') return 'text-red-800 font-extrabold';
          if (eff === 'rework') return 'text-purple-800 font-extrabold';
          return 'text-red-800 font-extrabold';
        };

        const getDefectiveBadgeClass = (slot) => {
          const eff = getSetEffectiveStatus(slot);
          if (eff === 'hold') return 'bg-amber-500 text-white';
          if (eff === 'scrap') return 'bg-red-600 text-white';
          if (eff === 'rework') return 'bg-purple-600 text-white';
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
          const eff = getSetEffectiveStatus(item);
          if (eff === 'hold') return 'font-black text-amber-600';
          if (eff === 'scrap') return 'font-black text-red-600';
          if (eff === 'rework') return 'font-black text-purple-600';
          return 'font-black text-red-600';
        };

        const getItemCardClass = (slot, index) => {
          if (!slot) return 'bg-slate-50/80 border-slate-200 opacity-80';
          if (slot.status === 'RETURNED') return 'bg-amber-50/40 border-dashed border-amber-300 ring-1 ring-amber-200/70 opacity-80';

          // Sesuai status hasil perakitan set:
          // Kalo Good yaudah hijau (tetap hijau meskipun ada dokumen perubahan PU)
          // Begitupun Hold dengan warna kuning, dan Scrap dengan warna merah
          if (slot.status || slot.finished_at || slot.original_assembly_status) {
            const eff = getSetEffectiveStatus(slot);
            if (eff === 'good') {
              return 'bg-white border-2 border-emerald-400 ring-1 ring-emerald-200/80 shadow-xs';
            }
            if (eff === 'hold') {
              return 'bg-amber-50/50 border-2 border-amber-400 ring-1 ring-amber-200/80 shadow-xs';
            }
            if (eff === 'scrap') {
              return 'bg-red-50/50 border-2 border-red-400 ring-1 ring-red-200/80 shadow-xs';
            }
            if (eff === 'rework') {
              return 'bg-purple-50/50 border-2 border-purple-400 ring-1 ring-purple-200/80 shadow-xs';
            }
            return 'bg-white border-2 border-slate-300 shadow-xs';
          }

          if (slot.started_at) return 'bg-amber-50/40 border-2 border-amber-400 ring-2 ring-amber-300 shadow-md animate-pulse-border';
          if (index === firstUnfinishedIndex.value) return 'bg-white border-2 border-red-500 ring-2 ring-red-200 shadow-md';
          return 'bg-slate-50/80 border border-slate-200 opacity-80';
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
          if (s === 'hold' || s === 'rework') return 'bg-amber-50 text-amber-700 border-amber-200';
          if (s === 'ru' || s === 'dirakit ulang' || s === 'rakit ulang') return 'bg-purple-50 text-purple-700 border-purple-200';
          if (s === 'scrap') return 'bg-red-50 text-red-700 border-red-200';
          if (s === 'pending verif admin' || s === 'menunggu verif') return 'bg-blue-50 text-blue-800 border-blue-200';
          if (s === 'decline' || s === 'declined') return 'bg-rose-50 text-rose-700 border-rose-200';
          return 'bg-slate-100 text-slate-700 border-slate-200';
        };

        const getStatusTextClass = (status) => {
          if (!status || status === '-') return 'text-slate-500 font-bold';
          const s = String(status).toLowerCase().trim();
          if (['good', 'tersedia', 'pass', 'terima', 'accepted', 'acc', 'completed'].includes(s) || (s.includes('good') && !s.includes('not'))) return 'text-emerald-600 font-bold';
          if (['hold', 'hold_assy', 'hold_qc', 'pending', 'rework', 'rework_assy', 'rework_qc'].includes(s) || s.includes('menunggu') || s.includes('belum') || s.startsWith('hold')) return 'text-amber-600 font-bold';
          if (['ru', 'dirakit ulang', 'rakit ulang'].includes(s)) return 'text-purple-600 font-bold';
          if (['scrap', 'rusak', 'not_good', 'decline', 'declined', 'fail'].includes(s)) return 'text-red-600 font-bold';
          return 'text-slate-600 font-bold';
        };

        const getDocStatusTextClass = (status) => {
          return getStatusTextClass(status);
        };

        // formatDateTime hoisted above

        // ================= FUNCTIONAL TESTING STATE & METHODS (ASSY & QC) =================

        // ================= TESTING POINTS & SINGLE RETEST STATE =================
        // Testing points & guide carousel moved to public/js/modules/admin.js (Fase 2J)

        // Single retest methods moved to public/js/modules/tf1.js (Fase 2D)

        // ===================================================================
        // 11. TEST FUNCTIONAL 1 (ASSY TEST) MODULE (FASE 2D)
        // ===================================================================
        tf1Module = {};
        if (typeof window.createTF1Module === 'function') {
          tf1Module = window.createTF1Module({
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
            activeAssyQCDocSlot,
            showDetailModal,
            openDetailModal: (...args) => (typeof openDetailModal === 'function' ? openDetailModal(...args) : null),
            loadAssemblyTray: (...args) => (typeof loadAssemblyTray === 'function' ? loadAssemblyTray(...args) : null),
            loadQCPendingItems: (...args) => (typeof loadQCPendingItems === 'function' ? loadQCPendingItems(...args) : null),
            loadProductionHoldItems: (...args) => (typeof loadProductionHoldItems === 'function' ? loadProductionHoldItems(...args) : null),
            loadPendingVerifications: (...args) => (typeof loadPendingVerifications === 'function' ? loadPendingVerifications(...args) : null),
            loadTravelerTray: (...args) => (typeof loadTravelerTray === 'function' ? loadTravelerTray(...args) : null),
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            filteredTraysList,
            isReassemblySet: (...args) => (typeof isReassemblySet === 'function' ? isReassemblySet(...args) : false),
            navigateTo,
            activePrintDocType,
            getCurrentTabHash: (...args) => (typeof getCurrentTabHash === 'function' ? getCurrentTabHash(...args) : ''),
            getNowLocalDateTimeStr: (...args) => (typeof getNowLocalDateTimeStr === 'function' ? getNowLocalDateTimeStr(...args) : '')
          });
        }

        // Bridge refs/methods for internal app.js usage
        const {
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
          testedGoodSlots,
          countTestedGoodInTray,
          countTestedHoldInTray,
          countTestedRUInTray,
          countUntestedInTray,
          getTestingTrayCounts,
          getTestingSetProgress,
          selectAndPrintAssyQC,
          getTestItemParamStatus
        } = tf1Module;

        const defaultQCTestItems = [
          { test_num: 1, name: 'Pemeriksaan Visual & Label Mutu', description: 'Kondisi fisik, kelengkapan label, dan kebersihan', status: 'GOOD' },
          { test_num: 2, name: 'Pengujian Fungsi Elektronik & Power', description: 'Verifikasi stabilitas arus daya dan sirkuit', status: 'GOOD' },
          { test_num: 3, name: 'Pengujian Kinerja Sensor & Respon', description: 'Akurasi sensor dan respon real-time', status: 'GOOD' },
          { test_num: 4, name: 'Uji Ketahanan & Stress Test', description: 'Ketahanan operasional beban tinggi', status: 'GOOD' },
          { test_num: 5, name: 'Kalibrasi Akhir Standar Mutu QC', description: 'Verifikasi deviasi toleransi standar mutu akhir', status: 'GOOD' }
        ];

        // ===================================================================
        // 12. QUALITY CONTROL & TEST FUNCTIONAL 2 (QC) MODULE (FASE 2C)
        // ===================================================================
        if (typeof window.createQCModule === 'function') {
          qcModule = window.createQCModule({
            Vue,
            fetchAPI,
            triggerToast,
            showConfirmCard,
            pgState,
            userRole,
            operatorName,
            currentTab,
            defaultAssyTestItems,
            getOperatorFullName,
            selectedTrayCode,
            activePrintDocType,
            navigateTo,
            loadTravelerTray: (...args) => (typeof loadTravelerTray === 'function' ? loadTravelerTray(...args) : null),
            loadProductionHoldItems: (...args) => (typeof loadProductionHoldItems === 'function' ? loadProductionHoldItems(...args) : null),
            loadPendingVerifications: (...args) => (typeof loadPendingVerifications === 'function' ? loadPendingVerifications(...args) : null),
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            reportSubTab: { get value() { return typeof reportSubTab !== 'undefined' ? reportSubTab.value : ''; }, set value(v) { if (typeof reportSubTab !== 'undefined') reportSubTab.value = v; } },
            loadQCTestReports: (...args) => (typeof loadQCTestReports === 'function' ? loadQCTestReports(...args) : null),
            openReregisterSetConfirmModal: (...args) => (typeof openReregisterSetConfirmModal === 'function' ? openReregisterSetConfirmModal(...args) : null),
            openReregisterModal: (...args) => (typeof openReregisterModal === 'function' ? openReregisterModal(...args) : null),
            openDetailModal: (...args) => (typeof openDetailModal === 'function' ? openDetailModal(...args) : null)
          });
        }

        // Bridge refs/methods for internal app.js usage
        const {
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
          confirmQCHold
        } = qcModule;

        // ==========================================
        // 13. HOLD & REASSEMBLY MODULE (FASE 2F)
        // ==========================================
        if (typeof window.createHoldsModule === 'function') {
          holdsModule = window.createHoldsModule({
            Vue,
            fetchAPI,
            triggerToast,
            showConfirmCard,
            showPromptCard,
            userRole,
            userName,
            userFullName,
            operatorName,
            currentTab,
            activePrintDocType,
            pgState,
            traysList,
            currentTray,
            operatorUsersList,
            getPUDocNumber,
            formatTimeOnly,
            openReregisterModal: (...args) => (typeof openReregisterModal === 'function' ? openReregisterModal(...args) : null),
            openReregisterSetConfirmModal: (...args) => (typeof openReregisterSetConfirmModal === 'function' ? openReregisterSetConfirmModal(...args) : null),
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            loadPendingVerifications: (...args) => (typeof loadPendingVerifications === 'function' ? loadPendingVerifications(...args) : null),
            loadQCPendingItems: (...args) => (typeof loadQCPendingItems === 'function' ? loadQCPendingItems(...args) : null),
            loadPendingAssemblies: (...args) => (typeof loadPendingAssemblies === 'function' ? loadPendingAssemblies(...args) : null)
          });
        }

        // Bridge holdsModule methods & state for internal app.js usage
        // Provide safe fallback refs so Vue template never gets undefined.value crashes
        const _nullRef = (def = null) => ref(def);
        const _nullRefArr = () => ref([]);
        const _nullFn = () => {};
        const {
          isReassemblySet: holdsIsReassemblySet,
          isTravelerItemAssembled = _nullFn,
          getTravelerItemActualStatus = _nullFn,
          getAssemblyStageStatus = _nullFn,
          getItemStartTime = _nullFn,
          getItemFinishTime = _nullFn,
          assemblyResults = _nullRefArr(),
          isLoadingAssemblyResults = _nullRef(false),
          assemblyResultsStatusFilter = _nullRef(''),
          selectedProductionOperator = _nullRef(''),
          selectedProductionDocAssy = _nullRef(''),
          productionSearchQuery = _nullRef(''),
          productionViewMode = _nullRef('grouped'),
          loadAssemblyResults = _nullFn,
          assemblyResultsSummary = _nullRef({}),
          distinctAssemblyOperators = _nullRefArr(),
          distinctAssyDocNumbers = _nullRefArr(),
          isAssyDocDropdownOpen: holdsIsAssyDocDropdownOpen,
          assyDocSearchText = _nullRef(''),
          toggleAssyDocDropdown = _nullFn,
          selectAssyDocFromDropdown = _nullFn,
          filteredAssyDocNumbers = _nullRefArr(),
          calculateAssemblyDuration = _nullFn,
          filteredGroupedAssemblyResults = _nullRef({}),
          flatFilteredAssemblyResults = _nullRefArr(),
          filteredAssemblyResults = _nullRefArr(),
          pagedAssemblyResults = _nullRefArr(),
          totalPgAssemblyResults = _nullRef(0),
          productionHoldList = _nullRefArr(),
          productionProductsList = _nullRefArr(),
          selectedProductionProduct = _nullRef(''),
          productionSourceFilter = _nullRef(''),
          functionalHoldViewMode = _nullRef('grouped'),
          isLoadingProductionHold = _nullRef(false),
          loadProductionProducts = _nullFn,
          loadProductionHoldItems: holdsLoadProductionHoldItems,
          isItemInReassemblyProcess = _nullFn,
          baseProductionHoldList = _nullRefArr(),
          productionHoldCounts = _nullRef({}),
          filteredProductionHoldList = _nullRefArr(),
          productionHoldCount = _nullRef(0),
          pagedProductionHoldList = _nullRefArr(),
          totalPgProductionHold = _nullRef(0),
          isAllProblematicPartsReplaced = _nullFn,
          getHoldProceedButtonLabel = _nullFn,
          getHoldProceedButtonTitle = _nullFn,
          getItemPendingLabel = _nullFn,
          isItemPendingAdmin = _nullFn,
          isFunctionalHoldItem = _nullFn,
          proceedHoldItem = _nullFn,
          showAssemblyDetailModal = _nullRef(false),
          selectedAssemblyItem = _nullRef(null),
          selectedAssemblyTray = _nullRef(null),
          showHoldDetailModal = _nullRef(false),
          selectedHoldItem = _nullRef(null),
          selectedHoldTray = _nullRef(null),
          openAssemblyDetailModal = _nullFn,
          openHoldDetailModal = _nullFn,
          transferSingleSetToWarehouse = _nullFn,
          confirmReturnTrayToWarehouse = _nullFn,
          showReregisterSubModal = _nullRef(false),
          reregisterTargetStatus = _nullRef(''),
          reregOperator = _nullRef(''),
          reregisterReason = _nullRef(''),
          isSubmittingReregisterSetAction = _nullRef(false),
          availableReregisterStatuses = _nullRefArr(),
          openReregisterSubModal = _nullFn,
          submitReregisterSetAction = _nullFn,
          showReplacePartModal = _nullRef(false),
          replaceOldStatus = _nullRef(''),
          replaceReason = _nullRef(''),
          isSubmittingReplacePartAction = _nullRef(false),
          showAllReplaceParts = _nullRef(false),
          replaceSelections = _nullRef({}),
          replaceDropdownOpen = _nullRef({}),
          replaceSearchQueries = _nullRef({}),
          availableReplacePartsByType = _nullRef({}),
          isLoadingPartsByType = _nullRef({}),
          replacePartType = _nullRef(''),
          replaceNewSN = _nullRef(''),
          availableReplaceParts = _nullRefArr(),
          isLoadingReplaceParts = _nullRef(false),
          isReplacePartDropdownOpen = _nullRef(false),
          replacePartSearchQuery = _nullRef(''),
          problematicPartTypes = _nullRefArr(),
          isProblemPart = _nullFn,
          hasNonProblematicParts = _nullFn,
          getParsedReplacementHistory = _nullFn,
          getItemProblemReason = _nullFn,
          getFilteredPartsForType = _nullFn,
          filteredReplaceParts = _nullRefArr(),
          selectReplaceSNForPart = _nullFn,
          clearReplaceSNForPart = _nullFn,
          toggleReplaceDropdown = _nullFn,
          closeAllReplaceDropdowns = _nullFn,
          selectReplaceNewSN = _nullFn,
          totalPartsToReplace = _nullRef(0),
          loadAllAvailableReplaceParts = _nullFn,
          openReregisterForPart = _nullFn,
          closeReplacePartModal = _nullFn,
          openReplacePartSubModal = _nullFn,
          onSelectReplacePartType = _nullFn,
          openReplaceForPart = _nullFn,
          submitReplacePartAction = _nullFn,
          pendingReassemblyList = _nullRefArr(),
          selectedReassemblyIds = _nullRefArr(),
          selectedReassemblyOperator = _nullRef(''),
          isLoadingPendingReassembly = _nullRef(false),
          isSubmittingReassemblyDispatch = _nullRef(false),
          loadPendingReassembly = _nullFn,
          pendingReassemblyCount = _nullRef(0),
          toggleSelectReassembly = _nullFn,
          isReassemblySelected = _nullFn,
          selectAllReassembly = _nullFn,
          clearSelectedReassembly = _nullFn,
          submitReassemblyDispatch = _nullFn,
          openReplaceForHoldItem = _nullFn
        } = holdsModule;

        // Reassign hoisted forward references
        if (holdsIsReassemblySet) isReassemblySet = holdsIsReassemblySet;
        if (holdsLoadProductionHoldItems) loadProductionHoldItems = holdsLoadProductionHoldItems;
        if (holdsIsAssyDocDropdownOpen) isAssyDocDropdownOpen = holdsIsAssyDocDropdownOpen;

        // ==========================================
        // 14. MULTI-CATEGORY REPORTS MODULE (FASE 2H)
        // ==========================================
        if (typeof window.createReportsModule === 'function') {
          reportsModule = window.createReportsModule({
            Vue,
            fetchAPI,
            triggerToast,
            currentTab,
            pgState,
            warehouseReceiptReports,
            operatorUsersList,
            traysList,
            assemblyResults,
            isReassemblySet,
            getOperatorFullName,
            getTodayDateStr,
            getFormattedDocCode,
            getPUDocNumber,
            getAssemblyStageStatus,
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            loadAssemblyResults: (...args) => (typeof loadAssemblyResults === 'function' ? loadAssemblyResults(...args) : null),
            openDetailModal: (...args) => (typeof openDetailModal === 'function' ? openDetailModal(...args) : null),
            navigateTo: (...args) => (typeof navigateTo === 'function' ? navigateTo(...args) : null)
          });
        }

        // Bridge reportsModule methods & state for internal app.js usage
        const {
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
          exportCurrentReportToExcel
        } = reportsModule;

        // ==========================================
        // 14. WAREHOUSE & LOGISTICS MODULE (FASE 2G)
        // ==========================================
        if (typeof window.createWarehouseModule === 'function') {
          warehouseModule = window.createWarehouseModule({
            Vue,
            fetchAPI,
            triggerToast,
            showConfirmCard,
            showPromptCard,
            userRole,
            userName,
            userFullName,
            operatorName,
            currentTab,
            activePrintDocType,
            pgState,
            traysList,
            selectedTrayCode,
            currentTray,
            travelerTray,
            currentTrayAssemblies,
            selectedTravelerTrayCode,
            selectedReceiptTrayCode,
            currentLaporanSubTab,
            travelerItems,
            whQCQualifiedItems,
            warehouseReceiptReports,
            reportFilters,
            currentTravelerMO,
            currentTravelerMOSuffix,
            getOperatorFullName,
            getNowLocalDateTimeStr,
            loadTrays: (...args) => (typeof loadTrays === 'function' ? loadTrays(...args) : null),
            loadAssemblyTray: (...args) => (typeof loadAssemblyTray === 'function' ? loadAssemblyTray(...args) : null),
            loadTravelerTray: (...args) => (typeof loadTravelerTray === 'function' ? loadTravelerTray(...args) : null),
            loadMasterData: (...args) => (typeof loadMasterData === 'function' ? loadMasterData(...args) : null),
            loadOperatorsList: (...args) => (typeof loadOperatorsList === 'function' ? loadOperatorsList(...args) : null),
            loadWarehouseReceiptReports: (...args) => (typeof loadWarehouseReceiptReports === 'function' ? loadWarehouseReceiptReports(...args) : null),
            selectPrintDocType: (...args) => (typeof selectPrintDocType === 'function' ? selectPrintDocType(...args) : null),
            selectAndPrintTraveler: (...args) => (typeof selectAndPrintTraveler === 'function' ? selectAndPrintTraveler(...args) : null),
            triggerPrint: (...args) => (typeof triggerPrint === 'function' ? triggerPrint(...args) : null),
            navigateTo: (...args) => (typeof navigateTo === 'function' ? navigateTo(...args) : null),
            switchTab: (...args) => (typeof switchTab === 'function' ? switchTab(...args) : null),
            gudangStageHistoryDepth,
            programmaticBackCount,
            isNavigatingBack
          });
        }

        // Bridge warehouseModule methods & state for internal app.js usage
        const {
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
          batchAcceptWarehouseReceipt
        } = warehouseModule;

        const gudangTotalBadgeCount = computed(() => {
          return (warehouseReceiptCounts.value?.pending || 0) +
                 (pendingReturnsCount.value || 0) +
                 (productionHoldCounts.value?.active_hold || 0) +
                 (assemblyResultsSummary.value?.notifCount || 0) +
                 (pendingReassemblyCount.value || 0);
        });

        // Multi-Category Reports & Traceability methods moved to public/js/modules/reports.js (Fase 2H)

        const refreshAllMenuBadges = async (isInitial = false) => {
          if (!localStorage.getItem('token')) return;
          if (!isInitial && document.hidden) return;
          if (!isInitial && document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA')) return;
          if (!isInitial && inputDropdown && inputDropdown.value && inputDropdown.value.isOpen) return;
          if (!isInitial && ((showStatusDrillModal && showStatusDrillModal.value) || (showMasterPartTrackingModal && showMasterPartTrackingModal.value))) return;

          try {
            await Promise.allSettled([
              loadProductionHoldItems(true),
              loadPendingVerifications(),
              loadQCPendingItems(),
              loadWarehouseReceiptReports(),
              loadReturnedTrays(),
              loadPendingReassembly(true),
              loadAssemblyResults(true)
            ]);
          } catch (e) {
            console.error('Error in refreshAllMenuBadges:', e);
          }
        };

        let globalBadgePollTimer = null;
        const startGlobalBadgePolling = () => {
          if (globalBadgePollTimer) clearInterval(globalBadgePollTimer);
          // Langsung jalankan saat inisialisasi agar seluruh notifikasi badge menu langsung muncul tanpa menunggu jeda!
          refreshAllMenuBadges(true);

          globalBadgePollTimer = setInterval(() => {
            refreshAllMenuBadges(false);

            if (!document.hidden && localStorage.getItem('token')) {
              if (currentTab.value === 'production_hold' || currentTab.value === 'functional_hold') {
                loadProductionProducts();
              }
              if ((currentTab.value === 'assembly' || currentTab.value === 'assy_testing') && selectedTrayCode.value) {
                if (!document.activeElement || (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
                  loadAssemblyTray();
                }
              }
              if (currentTab.value === 'warehouse' || currentTab.value === 'traveler') {
                loadTrays();
              }
            }
          }, 10000);
        };

        // -------------------------------------------------------------------
        // UNIVERSAL BACK BUTTON & MULTI-LAYER NAVIGATION SYSTEM
        // Mobile / Tablet / Hardware & Gesture Back Button Handler
        // -------------------------------------------------------------------
        const getCurrentTabCleanUrl = () => {
          return resolveCleanUrl(currentTab.value);
        };
        const getCurrentTabHash = () => {
          return getCurrentTabCleanUrl();
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
              } else if (prev.tab === 'delivery_order') {
                navigateTo('delivery_order', prev.subTab || 'dashboard', true);
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
            window.history.pushState({ type: 'root_sentinel' }, '', getCurrentTabCleanUrl());
          } catch (_) { }
          triggerToast('Keluar Aplikasi', 'Tekan sekali lagi untuk keluar dari sistem.', 'warn');
          clearTimeout(exitGuardTimer);
          exitGuardTimer = setTimeout(() => {
            exitGuardArmed = false;
          }, 2000);
        };

        // ===================================================================
        // ===================================================================
        // MODULE: DELIVERY ORDER (DO) & PACKING LIST (PL) & PELABELAN
        // (Modularized to public/js/modules/delivery.js in Fase 2)
        // ===================================================================

        onMounted(() => {
          // Inisialisasi stack dengan tab aktif saat ini
          const initRoute = getInitialRoute();
          const initTab = (initRoute && initRoute.tab) ? initRoute.tab : (currentTab.value || defaultTab.value || 'assembly');
          const initSubTab = (initRoute && initRoute.subTab) ? initRoute.subTab : null;
          const initKey = initTab === 'users'
            ? `users/${normalizeAdminSubTab(initSubTab) || 'verify_set'}`
            : (initTab === 'report' ? `report/${initSubTab || 'wo'}` : (initTab === 'delivery_order' ? `delivery_order/${initSubTab || 'dashboard'}` : initTab));
          _tabNavStack.length = 0;
          _tabNavStack.push({ tab: initTab, subTab: initSubTab, key: initKey });

          // Inisialisasi Buffer Dua Tingkat O(1) di root
          try {
            window.history.replaceState({ type: 'root_base' }, '', getCurrentTabHash());
            window.history.pushState({ type: 'root_sentinel' }, '', getCurrentTabHash());
          } catch (_) { }

          window.addEventListener('popstate', handleMobileBackNavigation);

          window.addEventListener('beforeunload', (e) => {
            if (isUserFormDirty() || isTestingPointFormDirty()) {
              e.preventDefault();
              e.returnValue = '';
              return '';
            }
          });

          try {
            if (deliveryModule && typeof deliveryModule.syncPackagingStructure === 'function') {
              deliveryModule.syncPackagingStructure();
            }
            syncCurrentUserPermissions();
            if (!hasTabAccess(currentTab.value, adminSubTab.value)) {
              const fallback = defaultTab.value;
              const target = hasTabAccess(fallback) ? fallback : (['assembly', 'warehouse', 'qc', 'traveler', 'returns', 'report'].find(t => hasTabAccess(t)) || 'assembly');
              currentTab.value = target;
              localStorage.setItem('activeTab', target);
            }
            // Strip any remaining hash from the address bar for pure clean URLs
            try {
              if (window.location.hash && window.history && window.history.replaceState) {
                const initClean = getCurrentTabCleanUrl();
                window.history.replaceState({ type: 'tab', key: currentTab.value, tab: currentTab.value }, '', initClean);
              }
            } catch (_) { }

            if (currentTab.value === 'delivery_order') {
              if (deliveryModule && typeof deliveryModule.initDeliveryTab === 'function') {
                deliveryModule.initDeliveryTab(initialRoute.subTab);
              }
            }
            if (currentTab.value === 'warehouse') {
              loadTrays();
              loadOperatorsList();
            } else if (currentTab.value === 'assembly' || currentTab.value === 'assy_testing') {
              loadTrays().then(() => {
                if (selectedTrayCode.value) loadAssemblyTray();
              });
            }

            // Mulai polling dan muat notifikasi badge menu secara instan tanpa jeda
            startGlobalBadgePolling();
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
        });

        // =========================================================
        // Admin pagination slices moved to public/js/modules/admin.js (Fase 2J)
        const pagedGudangVerif = computed(() => { const s = pgState.gudangVerif; return (gudangVerifySets.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to reports.js] pagedWOReport, pagedAssyReport, pagedAssyTestReport, pagedQCReport, pagedWarehouseReport
        // [Moved to holds.js] const pagedProductionHoldList = ...
        // [Moved to qc.js] const pagedQcTraySummaryList = computed(() => { const s = pgState.qcTraySummary; return (qcTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to holds.js] const pagedAssemblyResults = ...

        // Admin total page computeds moved to public/js/modules/admin.js (Fase 2J)
        const totalPgGudangVerif = computed(() => Math.max(1, Math.ceil((gudangVerifySets.value || []).length / pgState.gudangVerif.size)));
        // [Moved to reports.js] totalPgWOReport, totalPgAssyReport, totalPgAssyTestReport, totalPgQCReport, totalPgWarehouseReport
        // [Moved to holds.js] const totalPgProductionHold = ...
        // [Moved to qc.js] const totalPgQcTraySummary = computed(() => Math.max(1, Math.ceil((qcTraySummaryList.value || []).length / pgState.qcTraySummary.size)));
        // [Moved to holds.js] const totalPgAssemblyResults = ...

        watch([selectedProductionProduct, selectedProductionOperator, selectedProductionDocAssy, productionSearchQuery, assemblyResultsStatusFilter], () => {
          if (pgState.assemblyResults) pgState.assemblyResults.page = 1;
        });


        watch([() => returnsFilter.operator, () => returnsFilter.traySuffix, () => returnsFilter.status], () => {
          if (pgState.returns) pgState.returns.page = 1;
        });

        watch([() => reportFilters?.warehouse_receipt?.searchQuery], () => {
          if (pgState.receiptTray) pgState.receiptTray.page = 1;
        });

        
        // CMS & server diagnostics moved to public/js/modules/admin.js (Fase 2J)

        return {
          // Module domain exports (Modularized Fase 2)
          ...deliveryModule,
          ...assemblyModule,
          ...adminModule,
          ...travelerModule,
          ...tf1Module,
          ...qcModule,
          ...holdsModule,
          ...reportsModule,
          ...warehouseModule,
          availablePartsByType: (typeof availablePartsByType === 'function' ? availablePartsByType : () => []),

          // Core Global App State & Auth
          pgState,
          currentTab, defaultTab, userRole, userName, userFullName, operatorName, userDepartment,
          operatorUsersList, allUsersLookupList, loadOperatorsList, getOperatorFullName,
          userPermissions, managedMenuGroups, managedMenuList, defaultPermissions,
          hasMenuAccess, hasGroupAccess, isMenuReadOnly, hasTabAccess, isAdminUser,
          applyUserPermissionPreset, setGroupPermissions, onPermissionChange,
          countGroupActiveMenus, countUserActiveMenus, countUserWriteMenus,
          formatDepartmentDisplay, getDepartmentBadgeClass, onRoleOrDeptChange,
          openPermissionCards, togglePermissionCard, toggleAllPermissionCards,
          syncCurrentUserPermissions, logout,

          // Navigation & Routing
          navigateTo, switchTab, adminSubTab, setAdminSubTab, isMobileMenuOpen, sidebarOpen, toggleSidebarGroup,
          showAdminUnsavedModal, adminUnsavedContext, isSavingAdminUnsaved,
          confirmAdminUnsavedNavigation, cancelAdminUnsavedNavigation,

          // Global Alerts & Modals
          toast, triggerToast, showToast, confirmModalState, showConfirmCard, showPromptCard,
          handleConfirmCard, safeCloseModal, showDetailModal, activeDetailSlot,
          showDetailChecklistFull, getFailedChecklistItems, openDetailModal,

          // Global Clocks & Database
          liveClock, liveClockString, activeDatabase, fetchDatabaseStatus, swipeDatabase,

          // Shared Helpers & Classes
          defaultCrateIdForNow,
          isPartDefective, getDefectivePartClass, getDefectiveLabelClass, getDefectiveBadgeClass,
          getDefectiveTableCellClass, getItemCardClass, getSetEffectiveStatus,
          isSetAllocatedForReassembly, isSetPendingReassemblyAllocation, getSetPUDocNumber,
          getStatusBadgeClass, getStatusTextClass,
          getDocStatusTextClass, formatLifecycleHistory, getStatusHistoryBadgeClass,
          formatDateTime, formatDateOnly, formatTimeOnly, formatDuration, getEffectiveFinishedAt,
          exportTrayToExcel, exportLaporanToExcel, exportToExcel,

          // Slot Stage Visibility
          isTF1ResultCardVisible, isWarehouseReceiptVisible, isQCResultCardVisible, getQCReportStatus,

          // Global Badges & Polling
          assemblyOverallCounts, tf1OverallCounts, gudangTotalBadgeCount,
          refreshAllMenuBadges, onOperatorFilterChange, defaultQCTestItems,
          pagedGudangVerif, totalPgGudangVerif
        };
      }
    });
    app.config.globalProperties.window = window;
    app.mount('#app');
