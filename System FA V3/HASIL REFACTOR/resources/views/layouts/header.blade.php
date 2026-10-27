@verbatim
    <header class="no-print bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14 sm:h-16 gap-2">

          <!-- Left: Sidebar Toggle Icon + Brand Logo & Title -->
          <div class="flex items-center space-x-2 sm:space-x-3">
            <!-- Sidebar Toggle Button -->
            <button type="button" @click="isMobileMenuOpen = true"
              class="p-2 -ml-1 text-slate-700 hover:text-red-600 hover:bg-slate-100 rounded-xl transition active:scale-95 flex items-center justify-center cursor-pointer select-none"
              title="Buka Menu Navigasi" aria-label="Buka Menu Navigasi">
              <i class="fa-solid fa-bars text-lg sm:text-xl"></i>
            </button>

            <!-- Brand Title with Clean Emblem Logo -->
            <div class="flex items-center space-x-2 sm:space-x-2.5 cursor-pointer flex-shrink-0"
              @click="navigateTo(defaultTab)">
              <img src="/emblem.png" alt="Logo" class="h-8 sm:h-9 w-auto object-contain flex-shrink-0">
              <div class="leading-tight whitespace-nowrap">
                <h1 class="font-black text-sm sm:text-base text-slate-900 tracking-wide">FINAL ASSEMBLY</h1>
                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider">Traceability System</p>
              </div>
            </div>
          </div>

          <!-- Right: User Info & Role (Click opens navigation drawer) -->
          <div
            class="flex items-center space-x-3 cursor-pointer py-1 px-2.5 sm:px-3 rounded-xl hover:bg-slate-100/80 transition select-none flex-shrink-0"
            @click="isMobileMenuOpen = true" title="Buka Menu & Profil">
            <div class="flex flex-col text-right justify-center space-y-0.5 sm:space-y-1">
              <span class="text-xs sm:text-sm font-black text-slate-800 tracking-wide whitespace-nowrap leading-tight">
                {{ userFullName || userName || 'User' }}
              </span>
              <span class="text-[9px] sm:text-[10px] text-red-600 font-black uppercase tracking-wider leading-tight">
                {{ userRole }}
              </span>
            </div>
            <div
              class="hidden sm:flex w-8 h-8 rounded-lg bg-red-50 text-red-600 items-center justify-center text-xs font-bold border border-red-200 shadow-xs flex-shrink-0">
              <i class="fa-solid fa-user"></i>
            </div>
          </div>

        </div>
      </div>
    </header>

    <!-- UNIVERSAL SIDEBAR DRAWER (NO-PRINT) -->
    <div class="no-print">
      <!-- Backdrop Overlay -->
      <transition enter-active-class="transition-opacity ease-out duration-300" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-opacity ease-in duration-200"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isMobileMenuOpen" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[99998]"
          @click="isMobileMenuOpen = false"></div>
      </transition>

      <!-- Sidebar Drawer (Clean White Theme) -->
      <transition enter-active-class="transition-transform ease-out duration-300" enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0" leave-active-class="transition-transform ease-in duration-200"
        leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
        <aside v-if="isMobileMenuOpen"
          class="fixed inset-y-0 left-0 z-[99999] w-72 sm:w-80 max-w-[85vw] bg-white text-slate-800 shadow-2xl border-r border-slate-200 flex flex-col justify-between overflow-y-auto">

          <!-- Top Brand & Close -->
          <div>
            <div class="p-4 sm:p-5 flex items-center justify-between border-b border-slate-100 bg-slate-50/70">
              <div class="flex items-center space-x-2.5">
                <img src="/emblem.png" alt="Logo"
                  class="h-8 w-auto object-contain bg-white p-1 rounded-lg border border-slate-200 shadow-xs">
                <div class="leading-tight">
                  <h2 class="font-black text-sm text-slate-900 tracking-wider">FINAL ASSEMBLY</h2>
                  <p class="text-[9px] text-red-600 font-bold uppercase tracking-widest">Sistem Traceability</p>
                </div>
              </div>
              <button type="button" @click="isMobileMenuOpen = false"
                class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-100 transition active:scale-95 cursor-pointer"
                title="Tutup Menu">
                <i class="fa-solid fa-xmark text-lg"></i>
              </button>
            </div>

            <!-- Navigation Menu Groups (Collapsible Accordion Dropdowns by Department) -->
            <div class="p-3.5 sm:p-4 space-y-3">

              <!-- Group 1: GUDANG -->
              <div v-if="hasGroupAccess('gudang')"
                class="border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/60 shadow-xs">
                <!-- Accordion Header -->
                <button type="button" @click="toggleSidebarGroup('gudang')"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between text-slate-700 hover:text-red-600 hover:bg-slate-100/80 transition cursor-pointer select-none">
                  <div class="flex items-center space-x-2">
                    <div
                      class="w-6 h-6 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xs shadow-xs">
                      <i class="fa-solid fa-warehouse text-[10.5px]"></i>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Gudang</span>
                  </div>
                  <div class="flex items-center space-x-1.5">
                    <span v-if="gudangTotalBadgeCount > 0"
                      class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full"
                      :title="'Total Notifikasi Gudang: ' + gudangTotalBadgeCount">
                      {{ gudangTotalBadgeCount }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
                      :class="{ 'rotate-180': sidebarOpen.gudang }"></i>
                  </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="sidebarOpen.gudang"
                  class="p-1.5 space-y-1 bg-white border-t border-slate-100 animate-fadeIn">
                  <!-- 1. Work Order (Input Part) -->
                  <button v-if="hasMenuAccess('warehouse')" type="button" @click="navigateTo('warehouse')"
                    :class="currentTab === 'warehouse' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-boxes-packing text-xs"
                        :class="currentTab === 'warehouse' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Work Order (Input Part)</span>
                    </div>
                    <i v-if="currentTab === 'warehouse'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- 2. Hasil Perakitan -->
                  <button v-if="hasMenuAccess('production_hold')" type="button" @click="navigateTo('production_hold')"
                    :class="currentTab === 'production_hold' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-boxes-stacked text-xs"
                        :class="currentTab === 'production_hold' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Hasil Perakitan</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span v-if="assemblyResultsSummary.notifCount > 0"
                        class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full"
                        title="Total unit Not Good (Hold, Scrap)">
                        {{ assemblyResultsSummary.notifCount }}
                      </span>
                      <i v-if="currentTab === 'production_hold'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 2b. Hasil Test Functional 1 dan 2 (Hanya di Gudang) -->
                  <button v-if="hasMenuAccess('functional_hold') || hasMenuAccess('production_hold')" type="button"
                    @click="navigateTo('functional_hold')"
                    :class="currentTab === 'functional_hold' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-vial-circle-check text-xs"
                        :class="currentTab === 'functional_hold' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Hasil Test Functional 1 dan 2</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span v-if="productionHoldCounts.active_hold > 0"
                        class="px-1.5 py-0.5 bg-rose-500 text-white font-black text-[9px] rounded-full">
                        {{ productionHoldCounts.active_hold }}
                      </span>
                      <i v-if="currentTab === 'functional_hold'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 3. Pengembalian Barang -->
                  <button v-if="hasMenuAccess('returns')" type="button" @click="navigateTo('returns')"
                    :class="currentTab === 'returns' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-rotate-left text-xs"
                        :class="currentTab === 'returns' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Pengembalian Barang</span>
                    </div>
                    <span v-if="pendingReturnsCount > 0"
                      class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full">
                      {{ pendingReturnsCount }}
                    </span>
                    <i v-else-if="currentTab === 'returns'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- 4. Good Receipts (WH/GR) -->
                  <button v-if="hasMenuAccess('warehouse_receipt')" type="button"
                    @click="navigateTo('warehouse_receipt')"
                    :class="currentTab === 'warehouse_receipt' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-clipboard-check text-xs"
                        :class="currentTab === 'warehouse_receipt' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Good Receipts (WH/GR)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span v-if="warehouseReceiptCounts.pending > 0"
                        class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full">
                        {{ warehouseReceiptCounts.pending }}
                      </span>
                      <i v-if="currentTab === 'warehouse_receipt'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 4b. Perakitan Ulang (RU) -->
                  <button v-if="hasMenuAccess('reassembly')" type="button" @click="navigateTo('reassembly')"
                    :class="currentTab === 'reassembly' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-arrows-rotate text-xs"
                        :class="currentTab === 'reassembly' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Perakitan Ulang</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span v-if="pendingReassemblyCount > 0"
                        class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full">
                        {{ pendingReassemblyCount }}
                      </span>
                      <i v-if="currentTab === 'reassembly'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 5. Cetak Dokumen -->
                  <button v-if="hasMenuAccess('traveler')" type="button" @click="navigateTo('traveler')"
                    :class="currentTab === 'traveler' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-print text-xs"
                        :class="currentTab === 'traveler' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Cetak Dokumen</span>
                    </div>
                    <i v-if="currentTab === 'traveler'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- 6. Master Stok -->
                  <button v-if="hasMenuAccess('master')" type="button" @click="navigateTo('master')"
                    :class="currentTab === 'master' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-database text-xs"
                        :class="currentTab === 'master' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Master Stok</span>
                    </div>
                    <i v-if="currentTab === 'master'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- 7. History -->
                  <button v-if="hasMenuAccess('report')" type="button" @click="navigateTo('report')"
                    :class="currentTab === 'report' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-clock-rotate-left text-xs"
                        :class="currentTab === 'report' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>History</span>
                    </div>
                    <i v-if="currentTab === 'report'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- 8. Delivery Order & PL -->
                  <button v-if="hasMenuAccess('delivery_order')" type="button" @click="navigateTo('delivery_order')"
                    :class="currentTab === 'delivery_order' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-truck-ramp-box text-xs"
                        :class="currentTab === 'delivery_order' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Delivery Order &amp; PL</span>
                    </div>
                    <i v-if="currentTab === 'delivery_order'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>
                </div>
              </div>

              <!-- Group 2: PERAKITAN -->
              <div v-if="hasGroupAccess('perakitan')"
                class="border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/60 shadow-xs">
                <!-- Accordion Header -->
                <button type="button" @click="toggleSidebarGroup('perakitan')"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between text-slate-700 hover:text-blue-600 hover:bg-slate-100/80 transition cursor-pointer select-none">
                  <div class="flex items-center space-x-2">
                    <div
                      class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shadow-xs">
                      <i class="fa-solid fa-screwdriver-wrench text-[10.5px]"></i>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Perakitan</span>
                  </div>
                  <div class="flex items-center space-x-1.5">
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
                      :class="{ 'rotate-180': sidebarOpen.perakitan }"></i>
                  </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="sidebarOpen.perakitan"
                  class="p-1.5 space-y-1 bg-white border-t border-slate-100 animate-fadeIn">
                  <!-- Area Perakitan -->
                  <button v-if="hasMenuAccess('assembly')" type="button" @click="navigateTo('assembly')"
                    :class="currentTab === 'assembly' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-gear text-xs"
                        :class="currentTab === 'assembly' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>{{ userDepartment === 'gudang' && userRole !== 'admin' && userRole !== 'Super Admin' ? 'Area Perakitan (Monitoring)' : 'Area Perakitan' }}</span>
                    </div>
                    <i v-if="currentTab === 'assembly'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>


                  <!-- Testing Operator Assembly (TF1) -->
                  <button v-if="hasMenuAccess('assy_testing')" type="button" @click="navigateTo('assy_testing')"
                    :class="currentTab === 'assy_testing' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-vial-circle-check text-xs"
                        :class="currentTab === 'assy_testing' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>{{ userDepartment === 'gudang' && userRole !== 'admin' && userRole !== 'Super Admin' ? 'Test Functional 1 (Monitoring)' : 'Test Functional 1 (Assy)' }}</span>
                    </div>
                    <i v-if="currentTab === 'assy_testing'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>
                </div>
              </div>

              <!-- Group 3: QUALITY CONTROL (QC) -->
              <div v-if="hasGroupAccess('qc')"
                class="border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/60 shadow-xs">
                <!-- Accordion Header -->
                <button type="button" @click="toggleSidebarGroup('qc')"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between text-slate-700 hover:text-emerald-600 hover:bg-slate-100/80 transition cursor-pointer select-none">
                  <div class="flex items-center space-x-2">
                    <div
                      class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs shadow-xs">
                      <i class="fa-solid fa-list-check text-[10.5px]"></i>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Quality Control</span>
                  </div>
                  <div class="flex items-center space-x-1.5">
                    <span v-if="qcPendingCount > 0"
                      class="px-1.5 py-0.5 bg-emerald-500 text-white font-black text-[9px] rounded-full">
                      {{ qcPendingCount }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
                      :class="{ 'rotate-180': sidebarOpen.qc }"></i>
                  </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="sidebarOpen.qc" class="p-1.5 space-y-1 bg-white border-t border-slate-100 animate-fadeIn">
                  <!-- Uji Fungsional QC (TF2) -->
                  <button v-if="hasMenuAccess('qc')" type="button" @click="navigateTo('qc')"
                    :class="currentTab === 'qc' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-vial-circle-check text-xs"
                        :class="currentTab === 'qc' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Test Functional 2 (QC)</span>
                    </div>
                    <span v-if="qcPendingCount > 0"
                      class="px-1.5 py-0.5 bg-emerald-500 text-white font-black text-[9px] rounded-full">
                      {{ qcPendingCount }}
                    </span>
                    <i v-else-if="currentTab === 'qc'" class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>
                </div>
              </div>

              <!-- Group 4: ADMINISTRATOR -->
              <div v-if="hasGroupAccess('admin')"
                class="border border-slate-200/90 rounded-2xl overflow-hidden bg-slate-50/60 shadow-xs">
                <!-- Accordion Header -->
                <button type="button" @click="toggleSidebarGroup('admin')"
                  class="w-full px-3.5 py-2.5 flex items-center justify-between text-slate-700 hover:text-purple-600 hover:bg-slate-100/80 transition cursor-pointer select-none">
                  <div class="flex items-center space-x-2">
                    <div
                      class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs shadow-xs">
                      <i class="fa-solid fa-shield-halved text-[10.5px]"></i>
                    </div>
                    <span class="text-xs font-black uppercase tracking-wider text-slate-800">Administrator</span>
                  </div>
                  <div class="flex items-center space-x-1.5">
                    <span v-if="adminPendingCount > 0"
                      class="px-1.5 py-0.5 bg-amber-400 text-slate-950 font-black text-[9px] rounded-full">
                      {{ adminPendingCount }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform duration-200"
                      :class="{ 'rotate-180': sidebarOpen.admin }"></i>
                  </div>
                </button>

                <!-- Accordion Body -->
                <div v-show="sidebarOpen.admin"
                  class="p-1.5 space-y-1 bg-white border-t border-slate-100 animate-fadeIn">
                  <!-- 1. Verifikasi Satu Set -->
                  <button v-if="hasMenuAccess('verify_set') || hasMenuAccess('verify_items')" type="button"
                    @click="navigateTo('users', 'verify_set')"
                    :class="currentTab === 'users' && adminSubTab === 'verify_set' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-boxes-stacked text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'verify_set' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Verifikasi Hasil Rakitan dan Test</span>
                    </div>
                    <div class="flex items-center gap-1">
                      <span v-if="adminPendingSetsTotalCount > 0"
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] px-1.5 py-0.2 rounded-full shadow-xs">
                        {{ adminPendingSetsTotalCount }}
                      </span>
                      <i v-if="currentTab === 'users' && adminSubTab === 'verify_set'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 2. Verifikasi 1 Part (Satuan) -->
                  <button v-if="hasMenuAccess('verify_part') || hasMenuAccess('verify_items')" type="button"
                    @click="navigateTo('users', 'verify_part')"
                    :class="currentTab === 'users' && adminSubTab === 'verify_part' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-microchip text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'verify_part' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Verifikasi Status Part</span>
                    </div>
                    <div class="flex items-center gap-1">
                      <span v-if="adminPendingRequestsCount > 0"
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] px-1.5 py-0.2 rounded-full shadow-xs">
                        {{ adminPendingRequestsCount }}
                      </span>
                      <i v-if="currentTab === 'users' && adminSubTab === 'verify_part'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- 3. Verifikasi Pergantian Part -->
                  <button v-if="hasMenuAccess('verify_replacement') || hasMenuAccess('verify_items')" type="button"
                    @click="navigateTo('users', 'verify_replacement')"
                    :class="currentTab === 'users' && adminSubTab === 'verify_replacement' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-wrench text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'verify_replacement' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Verifikasi Pergantian Part</span>
                    </div>
                    <div class="flex items-center gap-1">
                      <span v-if="adminPendingReplacementsCount > 0"
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] px-1.5 py-0.2 rounded-full shadow-xs">
                        {{ adminPendingReplacementsCount }}
                      </span>
                      <i v-if="currentTab === 'users' && adminSubTab === 'verify_replacement'"
                        class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                    </div>
                  </button>

                  <!-- Riwayat Audit Gudang -->
                  <button v-if="hasMenuAccess('audit')" type="button" @click="navigateTo('users', 'audit')"
                    :class="currentTab === 'users' && adminSubTab === 'audit' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-clock-rotate-left text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'audit' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Riwayat Audit Gudang</span>
                    </div>
                    <i v-if="currentTab === 'users' && adminSubTab === 'audit'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- Audit Downtime Perakitan -->
                  <button v-if="hasMenuAccess('downtime')" type="button" @click="navigateTo('users', 'downtime')"
                    :class="currentTab === 'users' && adminSubTab === 'downtime' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-business-time text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'downtime' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Audit Downtime Perakitan</span>
                    </div>
                    <i v-if="currentTab === 'users' && adminSubTab === 'downtime'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                  <!-- Master Testing Point -->
                  <button v-if="hasMenuAccess('testing_points')" type="button"
                    @click="navigateTo('users', 'testing_points')"
                    :class="currentTab === 'users' && adminSubTab === 'testing_points' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-list-check text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'testing_points' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Master Testing Point</span>
                    </div>
                  </button>

                  <!-- Tambah Part Number -->
                  <button v-if="hasMenuAccess('add_part')" type="button"
                    @click="navigateTo('users', 'add_part')"
                    :class="currentTab === 'users' && adminSubTab === 'add_part' ? 'bg-red-600 text-white font-bold shadow-md shadow-red-600/20' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'"
                    class="w-full text-left px-3 py-2 rounded-xl text-xs transition flex items-center justify-between group cursor-pointer">
                    <div class="flex items-center space-x-2.5">
                      <i class="fa-solid fa-plus-circle text-xs"
                        :class="currentTab === 'users' && adminSubTab === 'add_part' ? 'text-white' : 'text-slate-400 group-hover:text-slate-600'"></i>
                      <span>Tambah Part Number</span>
                    </div>
                    <i v-if="currentTab === 'users' && adminSubTab === 'add_part'"
                      class="fa-solid fa-chevron-right text-[10px] text-white"></i>
                  </button>

                </div>
              </div>

            </div>
          </div>

          <!-- Bottom User Profile & Clean Logout Button -->
          <div class="p-4 border-t border-slate-100 bg-slate-50/80 space-y-3">
            <div class="flex items-center space-x-3">
              <div
                class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-sm font-bold text-red-600 shadow-xs flex-shrink-0">
                <i class="fa-solid fa-user"></i>
              </div>
              <div class="leading-tight overflow-hidden">
                <span class="block text-xs font-black text-slate-900 truncate">{{ userFullName || userName || 'Pengguna'
                  }}</span>
                <span
                  class="text-[10px] text-red-700 font-bold uppercase tracking-wider bg-red-100/70 px-2 py-0.5 rounded-md inline-block mt-0.5 border border-red-200">{{
                  userRole }}</span>
              </div>
            </div>
            <button type="button" @click="logout"
              class="w-full bg-white hover:bg-red-50 text-red-700 hover:text-red-800 font-bold py-2.5 px-3 rounded-xl text-xs border border-red-200 shadow-sm transition flex items-center justify-center space-x-2 active:scale-95 cursor-pointer">
              <i class="fa-solid fa-right-from-bracket text-xs text-red-600"></i>
              <span>Keluar</span>
            </button>
          </div>

        </aside>
      </transition>
    </div>

@endverbatim
