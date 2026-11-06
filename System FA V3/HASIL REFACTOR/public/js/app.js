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
            '/delivery': { tab: 'delivery_order' },
            '/delivery-order': { tab: 'delivery_order' },
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
              if (deliveryModule && typeof deliveryModule.initDeliveryTab === 'function') {
                deliveryModule.initDeliveryTab();
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

          // Clean URL Address Bar Mapper
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
          const cleanPath = TAB_CLEAN_URLS[tab] || ('/' + tab);
          const targetUrl = cleanPath;

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
        });


        // ===================================================================
        // INITIALIZE DELIVERY ORDER & PACKING LIST MODULE (FASE 2)
        // ===================================================================
        if (typeof window.createDeliveryModule === 'function') {
          deliveryModule = window.createDeliveryModule({
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
            const tGoodAssy = t.good_assembly_count || t.good_count || 0;
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

        // Assembly Form State moved to public/js/modules/assembly.js (Fase 2E)

        // Label Preview State
        const showLabelPreviewModal = ref(false);
        const activeLabelSN = ref({});
        // Batch Label List Modal State
        const showBatchLabelModal = ref(false);
        const batchLabelItems = ref([]);
        const batchLabelDocInfo = ref({ docNumber: '', trayCode: '', totalACC: 0, productType: 'Nanggala P9' });
        const isGeneratingBatchPDF = ref(false);

        // Traveler Slip MO state
        const currentTravelerMO = ref('');
        const currentTravelerMOSuffix = ref('');

        // Laporan Traceability State & Filters moved to public/js/modules/reports.js (Fase 2H)

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

        // Gudang Actual Verification & Decline moved to public/js/modules/warehouse.js (Fase 2G)

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
          if (set.verified_status === 'ACC' || set.verified_status === 'APPROVED' || set.verified_status === 'APPROVED_QC' || set.verified_status === 'APPROVED_REREG' || set.verified_status === 'WAITING_REASSEMBLY' || set.verified_status === 'APPROVED_REASSEMBLY' || set.verified_status === 'COMPLETED_PU') return true;
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

        // Slot Calculation & Parts Availability moved to public/js/modules/assembly.js (Fase 2E)

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

        // loadLaporan moved to public/js/modules/reports.js (Fase 2H)

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

        // Modal & Action State Variables
        const showDetailModal = ref(false);
        const activeDetailSlot = ref(null);
        const showResetDbModal = ref(false);
        const isResettingDb = ref(false);
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
          const context = explicitContext ||
            (slot.stage === 'QC' ? 'qc_test' :
              slot.stage === 'ASSY' ? 'assy_test' :
                (slot.warehouse_receipt_by || slot.warehouse_doc_number || slot.verified_by_user) ? 'warehouse_receipt' :
                  (currentTab.value === 'report' ? reportSubTab.value : (currentTab.value === 'assy_testing' ? 'assy_test' : (currentTab.value === 'qc' ? 'qc_test' : 'assembly'))));

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
            try { parsedTF1 = JSON.parse(rawTF1); } catch (_) { parsedTF1 = null; }
          }

          const hasActualTF1Checklist = Array.isArray(parsedTF1) && parsedTF1.length > 0;
          const hasValidTF1Status = Boolean(slot.assy_test_status && !['PENDING', 'Pending', 'Belum Diuji'].includes(slot.assy_test_status));
          const hasValidTF1Time = Boolean(slot.assy_tested_at);
          const isAlreadyInNextStage = (context === 'qc_test' || context === 'warehouse_receipt' || slot.stage === 'QC' || Boolean(slot.qc_status && slot.qc_status !== 'PENDING') || Boolean(slot.qc_tested_at));

          // Set rakitan yang belum diuji TF 1 (masih di perakitan / belum ada catatan uji) TIDAK boleh menampilkan status tested
          const isTF1Tested = hasActualTF1Checklist ||
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
          } else if (isTF1Tested && (slot.assy_test_status === 'Good' || isAlreadyInNextStage)) {
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

          let slotStatus = (context === 'assembly')
            ? (slot.original_assembly_status || slot.assembly_status || slot.status || 'Good')
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
            is_tf1_tested: isTF1Tested,
            index: index,
            process_stage: context,
            status: slotStatus,
            started_at: slot.started_at || slot.created_at || slot.tray_created_at,
            finished_at: slot.finished_at || slot.created_at,
            tested_at: slot.created_at || slot.tested_at || slot.assy_tested_at,
            assy_tested_at: slot.assy_tested_at || slot.tested_at || null,
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

        // Gudang Supply Logic moved to public/js/modules/warehouse.js (Fase 2G)

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

        // openTrayDetailModal moved to public/js/modules/warehouse.js (Fase 2G)

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

        const getStatusTextClass = (status) => {
          if (!status || status === '-') return 'text-slate-500 font-bold';
          const s = String(status).toLowerCase().trim();
          if (['good', 'tersedia', 'pass', 'terima', 'accepted', 'completed'].includes(s)) return 'text-emerald-600 font-bold';
          if (['hold', 'hold_assy', 'hold_qc', 'pending'].includes(s) || s.includes('menunggu')) return 'text-amber-600 font-bold';
          if (['rework', 'rework_assy', 'rework_qc', 'ru', 'dirakit ulang'].includes(s)) return 'text-purple-600 font-bold';
          if (['scrap', 'rusak', 'not_good', 'decline', 'declined', 'fail'].includes(s)) return 'text-red-600 font-bold';
          return 'text-slate-600 font-bold';
        };

        const getDocStatusTextClass = (status) => {
          return getStatusTextClass(status);
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
          activeAssyQCDocSlot,
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
        const {
          isReassemblySet: holdsIsReassemblySet,
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
          isAssyDocDropdownOpen: holdsIsAssyDocDropdownOpen,
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
          loadProductionHoldItems: holdsLoadProductionHoldItems,
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
          if (currentTab.value === 'users') {
            return '/verifikasi';
          }
          if (currentTab.value === 'report') {
            return '/history';
          }
          return TAB_CLEAN_URLS[currentTab.value] || ('/' + (currentTab.value || 'perakitan'));
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
            window.history.pushState({ type: 'root_sentinel' }, '', (TAB_CLEAN_URLS[currentTab.value] || ('/' + currentTab.value)));
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
            : (initTab === 'report' ? `report/${initSubTab || 'wo'}` : initTab);
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
                const initClean = TAB_CLEAN_URLS[currentTab.value] || ('/' + currentTab.value);
                window.history.replaceState({ type: 'tab', key: currentTab.value, tab: currentTab.value }, '', initClean);
              }
            } catch (_) { }

            if (currentTab.value === 'delivery_order') {
              if (deliveryModule && typeof deliveryModule.initDeliveryTab === 'function') {
                deliveryModule.initDeliveryTab();
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
        const pagedAdminSets = computed(() => { const s = pgState.adminSets; return (filteredAdminSetsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminParts = computed(() => { const s = pgState.adminParts; return (filteredAdminPartsOnly.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAdminRepl = computed(() => { const s = pgState.adminRepl; return (filteredAdminReplacements.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedAuditLogs = computed(() => { const s = pgState.auditLogs; return (filteredAuditLogs.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedDowntime = computed(() => { const s = pgState.downtime; return (downtimeLogsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedTestPoint = computed(() => { const s = pgState.testPoint; return (filteredTestingPointsList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedUsers = computed(() => { const s = pgState.users; return (usersList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedMasterStock = computed(() => { const s = pgState.masterStock; return (masterStockList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to qc.js] const pagedQCList = computed(() => { const s = pgState.qcList; return (filteredQCList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        const pagedGudangVerif = computed(() => { const s = pgState.gudangVerif; return (gudangVerifySets.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to reports.js] pagedWOReport, pagedAssyReport, pagedAssyTestReport, pagedQCReport, pagedWarehouseReport
        // [Moved to holds.js] const pagedProductionHoldList = ...
        // [Moved to qc.js] const pagedQcTraySummaryList = computed(() => { const s = pgState.qcTraySummary; return (qcTraySummaryList.value || []).slice((s.page - 1) * s.size, s.page * s.size); });
        // [Moved to holds.js] const pagedAssemblyResults = ...
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
        // [Moved to qc.js] const totalPgQCList = computed(() => Math.max(1, Math.ceil((filteredQCList.value || []).length / pgState.qcList.size)));
        const totalPgGudangVerif = computed(() => Math.max(1, Math.ceil((gudangVerifySets.value || []).length / pgState.gudangVerif.size)));
        // [Moved to reports.js] totalPgWOReport, totalPgAssyReport, totalPgAssyTestReport, totalPgQCReport, totalPgWarehouseReport
        // [Moved to holds.js] const totalPgProductionHold = ...
        // [Moved to qc.js] const totalPgQcTraySummary = computed(() => Math.max(1, Math.ceil((qcTraySummaryList.value || []).length / pgState.qcTraySummary.size)));
        // [Moved to holds.js] const totalPgAssemblyResults = ...
        const totalPgReturns = computed(() => Math.max(1, Math.ceil((filteredReturnsList.value || []).length / pgState.returns.size)));
        const totalPgReceiptTray = computed(() => Math.max(1, Math.ceil((warehouseReceiptTraySummaryList.value || []).length / pgState.receiptTray.size)));

        watch([selectedProductionProduct, selectedProductionOperator, selectedProductionDocAssy, productionSearchQuery, assemblyResultsStatusFilter], () => {
          if (pgState.assemblyResults) pgState.assemblyResults.page = 1;
        });


        watch([() => returnsFilter.operator, () => returnsFilter.traySuffix, () => returnsFilter.status], () => {
          if (pgState.returns) pgState.returns.page = 1;
        });

        watch([() => reportFilters?.warehouse_receipt?.searchQuery], () => {
          if (pgState.receiptTray) pgState.receiptTray.page = 1;
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
          pagedProductionHoldList, pagedQcTraySummaryList,
          totalPgAdminSets, totalPgAdminParts, totalPgAdminRepl, totalPgAuditLogs,
          totalPgDowntime, totalPgTestPoint, totalPgUsers, totalPgMasterStock,
          totalPgQCList, totalPgGudangVerif,
          totalPgProductionHold, totalPgQcTraySummary,
          pagedReturnsList, pagedWarehouseReceiptTraySummaryList, totalPgReturns, totalPgReceiptTray,
          openAdminVerifySetModal, verifyAdminSinglePart, verifyAllPartsInActiveSet, isSubmittingDeclinedRestore, restoreDeclinedSetToAvailable,
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
          showAssySaveProgressModal, assyDraftProgress, isSlotTested, isSlotTF1Hold, isSlotTF1Good, getSlotDraftProgress, handleAssyBackNavigation, confirmSaveAssyProgress,
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
            isAllProblematicPartsReplaced, getItemPendingLabel, getHoldProceedButtonLabel, getHoldProceedButtonTitle, proceedHoldItem, isFunctionalHoldItem, getStatusTextClass, getDocStatusTextClass, getQCCardClass,
          adminApproveAssyRereg, adminReregApprovedOperator, adminReregNotes,
          openReregisterForPart, openReplaceForPart,
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
          // DO & PL Module exports (Modularized Fase 2)
          ...assemblyModule,
          ...deliveryModule,
          ...tf1Module,
          ...qcModule,
          ...holdsModule,
          ...warehouseModule,
          ...reportsModule,
          showMasterPartTrackingModal, selectedMasterPartRow, masterPartTrackingData, isLoadingMasterPartDetail, openMasterPartTrackingModal,
          pendingReassemblyList, pendingReassemblyCount, selectedReassemblyIds, selectedReassemblyOperator, isLoadingPendingReassembly, isSubmittingReassemblyDispatch, loadPendingReassembly, toggleSelectReassembly, isReassemblySelected, selectAllReassembly, clearSelectedReassembly, submitReassemblyDispatch, openReplaceForHoldItem,
          gudangTotalBadgeCount, refreshAllMenuBadges,
          syncCurrentUserPermissions,
          defaultCrateIdForNow,
          getYearMonthFromDate, getNowWIBDateString,
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
