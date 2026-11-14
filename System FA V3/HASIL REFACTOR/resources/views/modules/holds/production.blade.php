@verbatim
      <!-- ================= SECTION 6: HASIL PERAKITAN (REKAP WORK ORDER & HOLD DETAIL) ================= -->
      <section v-if="currentTab === 'production_hold'" class="space-y-4 sm:space-y-6 animate-fadeIn">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
          <!-- Header Hasil Perakitan -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <div class="flex items-center space-x-2.5">
                <div
                  class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-lg font-bold">
                  <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Hasil Perakitan</h2>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">
                    Monitoring seluruh hasil perakitan dikelompokkan berdasarkan nomor Work Order (No. WH/ASSY).
                  </p>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <!-- View Mode Toggle: Table View (Default) vs Card View (Opsi) vs Grouped WH/ASSY -->
              <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                <button type="button" @click="productionViewMode = 'table'"
                  :class="productionViewMode === 'table' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-table-list text-xs"></i>
                  <span>Tabel (Default)</span>
                </button>
                <button type="button" @click="productionViewMode = 'cards'"
                  :class="productionViewMode === 'cards' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-grip text-xs"></i>
                  <span>Card (Opsi)</span>
                </button>
                <button type="button" @click="productionViewMode = 'grouped'"
                  :class="productionViewMode === 'grouped' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-layer-group text-xs"></i>
                  <span>Per WH/ASSY</span>
                </button>
              </div>

              <!-- Refresh Button -->
              <button @click="loadAssemblyResults"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl font-bold text-xs border border-slate-300 flex items-center space-x-1.5 min-h-[36px] active:scale-95 transition cursor-pointer">
                <i :class="isLoadingAssemblyResults ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-arrows-rotate'"
                  class="text-slate-500"></i>
                <span>Muat Ulang</span>
              </button>
            </div>
          </div>

          <!-- Summary KPI Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Total Trays/WO -->
            <div
              class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Work
                  Order</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{
                  assemblyResultsSummary.totalTrays }}</span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Dokumen tray diproses</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-clipboard-list"></i>
              </div>
            </div>

            <!-- Total Good -->
            <div
              class="bg-gradient-to-br from-emerald-50/60 to-emerald-100/30 p-4 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Total Unit
                  Good</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{
                  assemblyResultsSummary.totalGood }}</span>
                <span class="text-[10px] text-emerald-700 block mt-0.5">Lolos uji &amp; terverifikasi</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-circle-check"></i>
              </div>
            </div>

            <!-- Total Hold -->
            <div
              class="bg-gradient-to-br from-amber-50/60 to-amber-100/30 p-4 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Total Unit
                  Hold</span>
                <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{
                  assemblyResultsSummary.totalHold }}</span>
                <span class="text-[10px] text-amber-700 block mt-0.5">Perlu daftar ulang / ganti part</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-hand"></i>
              </div>
            </div>

            <!-- Total Scrap -->
            <div
              class="bg-gradient-to-br from-red-50/60 to-red-100/30 p-4 rounded-2xl border border-red-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-red-800 block">Total Scrap</span>
                <span class="text-xl sm:text-2xl font-black text-red-950 font-mono">{{ assemblyResultsSummary.totalScrap
                  }}</span>
                <span class="text-[10px] text-red-700 block mt-0.5">Part / Unit Afkir</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-trash-can"></i>
              </div>
            </div>
          </div>

          <!-- Controls: Filters by Product, Status, and Search Bar -->
          <div class="space-y-3 pt-1">
            <!-- Filter Pills by Status (Active: Good, Hold, RU, Scrap) -->
            <div class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
              <button type="button" @click="assemblyResultsStatusFilter = 'ALL'"
                :class="assemblyResultsStatusFilter === 'ALL' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-layer-group text-xs text-slate-400"></i>
                <span>Semua Status</span>
                <span
                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold">{{
                  assemblyResultsSummary.totalSets }}</span>
              </button>
              <button type="button" @click="assemblyResultsStatusFilter = 'GOOD'"
                :class="assemblyResultsStatusFilter === 'GOOD' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-circle-check text-xs"></i>
                <span>Good</span>
                <span
                  :class="assemblyResultsStatusFilter === 'GOOD' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800'"
                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{
                  assemblyResultsSummary.totalGood }}</span>
              </button>
              <button type="button" @click="assemblyResultsStatusFilter = 'HOLD'"
                :class="assemblyResultsStatusFilter === 'HOLD' ? 'bg-amber-600 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-hand text-xs"></i>
                <span>Hold</span>
                <span
                  :class="assemblyResultsStatusFilter === 'HOLD' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800'"
                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{
                  assemblyResultsSummary.totalHold }}</span>
              </button>
              <button type="button" @click="assemblyResultsStatusFilter = 'RU'"
                :class="assemblyResultsStatusFilter === 'RU' ? 'bg-indigo-600 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-arrows-rotate text-xs"></i>
                <span>RU</span>
                <span
                  :class="assemblyResultsStatusFilter === 'RU' ? 'bg-indigo-700 text-white' : 'bg-indigo-100 text-indigo-800'"
                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{
                  assemblyResultsSummary.totalRU || 0 }}</span>
              </button>
              <button type="button" @click="assemblyResultsStatusFilter = 'SCRAP'"
                :class="assemblyResultsStatusFilter === 'SCRAP' ? 'bg-slate-700 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-ban text-xs"></i>
                <span>Scrap</span>
                <span
                  :class="assemblyResultsStatusFilter === 'SCRAP' ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-800'"
                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{
                  assemblyResultsSummary.totalScrap }}</span>
              </button>
            </div>

            <!-- Product, Operator, Doc No, and Search Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter Produk:</label>
                <select v-model="selectedProductionProduct"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600 min-h-[38px] cursor-pointer">
                  <option value="ALL">Semua jenis produk</option>
                  <option v-for="prod in productionProductsList" :key="prod" :value="prod">{{ prod }}</option>
                </select>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter Operator Perakitan:</label>
                <select v-model="selectedProductionOperator"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-blue-600 min-h-[38px] cursor-pointer">
                  <option value="ALL">Semua Operator Assembly</option>
                  <option v-for="op in distinctAssemblyOperators" :key="op" :value="op">{{ op }}</option>
                </select>
              </div>

              <div class="relative" id="assyDocDropdownContainer">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter No. Dokumen WH/ASSY:</label>
                <div @click="toggleAssyDocDropdown"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 flex items-center justify-between cursor-pointer min-h-[38px] hover:border-blue-600 transition">
                  <span class="font-mono truncate">{{ selectedProductionDocAssy === 'ALL' ? 'Semua Dokumen WH/ASSY' :
                    selectedProductionDocAssy }}</span>
                  <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform"
                    :class="{ 'rotate-180': isAssyDocDropdownOpen }"></i>
                </div>

                <!-- Custom Dropdown Panel (Selalu Membuka ke Bawah) -->
                <div v-if="isAssyDocDropdownOpen"
                  class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-2 space-y-2 max-h-64 overflow-y-auto animate-fadeIn">
                  <input type="text" v-model="assyDocSearchText" placeholder="Cari No. Dokumen..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-600">
                  <div class="divide-y divide-slate-100 max-h-44 overflow-y-auto">
                    <div @click="selectAssyDocFromDropdown('ALL')"
                      class="p-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs flex items-center justify-between transition">
                      <span class="font-semibold text-slate-700">Semua Dokumen WH/ASSY</span>
                      <i v-if="selectedProductionDocAssy === 'ALL'" class="fa-solid fa-check text-blue-600 text-xs"></i>
                    </div>
                    <div v-for="doc in filteredAssyDocNumbers" :key="doc" @click="selectAssyDocFromDropdown(doc)"
                      class="p-2 hover:bg-blue-50 rounded-xl cursor-pointer text-xs flex items-center justify-between transition">
                      <strong class="font-mono text-slate-900 font-bold block">{{ doc }}</strong>
                      <i v-if="selectedProductionDocAssy === doc" class="fa-solid fa-check text-blue-600 text-xs"></i>
                    </div>
                    <div v-if="filteredAssyDocNumbers.length === 0" class="p-3 text-center text-slate-400 text-xs">
                      Tidak ada dokumen cocok
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Cari Data Rakitan:</label>
                <div class="relative">
                  <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                  <input type="text" v-model="productionSearchQuery"
                    placeholder="Cari SN Part 15 / 1 / 12, MO, alasan..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-blue-600 min-h-[38px]">
                </div>
              </div>
            </div>
          </div>

          <!-- VIEW MODE 1: GROUPED BY NO WH/ASSY/0001 (USER'S PRIMARY REQUIREMENT) -->
          <div v-if="productionViewMode === 'grouped'" class="space-y-4">
            <div v-if="filteredGroupedAssemblyResults.length === 0"
              class="p-12 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <i class="fa-solid fa-boxes-packing text-4xl mb-2 block"></i>
              <span>Tidak ada data hasil perakitan yang sesuai dengan kriteria pencarian</span>
            </div>

            <!-- Tray Accordion Card -->
            <div v-for="tray in filteredGroupedAssemblyResults" :key="tray.tray_id"
              class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs transition hover:shadow-md">
              <!-- Tray Card Header -->
              <div
                class="p-4 sm:p-5 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center space-x-3">
                  <div
                    class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                    <i class="fa-solid fa-pallet"></i>
                  </div>
                  <div>
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-black text-base text-slate-900 font-mono tracking-tight">{{ tray.tray_code
                        }}</span>
                      <span v-if="tray.source_document_mo"
                        class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">
                        MO: {{ tray.source_document_mo }}
                      </span>
                      <span
                        class="px-2 py-0.5 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ tray.product_type || 'Nanggala P9' }}
                      </span>
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium mt-1 flex items-center gap-3 flex-wrap">
                      <span><i class="fa-solid fa-user-gear mr-1 text-[10px]"></i>Assy: {{ tray.pic_assembly || '-'
                        }}</span>
                      <span><i class="fa-solid fa-calendar mr-1 text-[10px]"></i>{{ formatDateTime(tray.tray_created_at)
                        }}</span>
                    </div>
                  </div>
                </div>

                <!-- Tray Badges Summary & Actions -->
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200 font-mono">
                    Total: {{ tray.sets_count }} Set
                  </span>
                  <span
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 font-mono">
                    <i class="fa-solid fa-check text-[10px] text-emerald-600 mr-1"></i>{{ tray.good_count }} Good
                  </span>
                  <span v-if="tray.ru_count > 0"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-900 border border-indigo-300 font-mono">
                    <i class="fa-solid fa-arrows-rotate text-[10px] text-indigo-700 mr-1"></i>{{ tray.ru_count }} RU
                  </span>
                  <span v-if="tray.hold_count > 0"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 font-mono">
                    <i class="fa-solid fa-hand text-[10px] text-amber-700 mr-1"></i>{{ tray.hold_count }} Hold
                  </span>
                  
                  <span v-if="tray.scrap_count > 0"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-200 text-slate-800 border border-slate-300 font-mono">
                    <i class="fa-solid fa-ban text-[10px] text-slate-700 mr-1"></i>{{ tray.scrap_count }} Scrap
                  </span>
                </div>
              </div>

              <!-- Sets Grid Inside Tray -->
              <div class="p-4 sm:p-5 bg-slate-50/40">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                  <div v-for="it in tray.items" :key="it.assembly_id"
                    :class="getAssemblyStageStatus(it) === 'RU' ? 'border-indigo-300 bg-indigo-50/20' : (getAssemblyStageStatus(it) === 'Hold' ? 'border-amber-300 bg-amber-50/20' : (getAssemblyStageStatus(it) === 'Scrap' ? 'border-slate-300 bg-slate-100/40' : 'border-slate-200 bg-white'))"
                    class="p-3.5 rounded-xl border shadow-xs transition hover:shadow-sm flex flex-col justify-between h-full">
                    <div class="space-y-2.5">
                      <!-- Top set line -->
                      <div class="flex items-center justify-between">
                        <span class="px-2 py-0.5 rounded-md font-black text-xs bg-slate-900 text-white font-mono">
                          Set #{{ it.set_number }}
                        </span>
                        <!-- Status Badge (Pure Assembly Status) -->
                        <span v-if="isItemPendingAdmin(it) && getAssemblyStageStatus(it) !== 'Good'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> MENUNGGU VERIFIKASI
                        </span>
                        <span
                          v-else-if="getAssemblyStageStatus(it) === 'Declined'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-circle-xmark text-[9px]"></i> DECLINED
                        </span>
                        <span v-else-if="getAssemblyStageStatus(it) === 'RU'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-arrows-rotate text-[9px]"></i> RU
                        </span>
                        <span v-else-if="getAssemblyStageStatus(it) === 'Good'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-check text-[9px]"></i> GOOD
                        </span>
                        <span v-else-if="getAssemblyStageStatus(it) === 'Hold'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-hand text-[9px]"></i> HOLD
                        </span>
                        <span v-else-if="getAssemblyStageStatus(it) === 'Scrap'"
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-ban text-[9px]"></i> SCRAP
                        </span>
                        <span v-else
                          class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-check text-[9px]"></i> GOOD
                        </span>
                      </div>

                      <!-- Serial Numbers -->
                      <div class="space-y-1 font-mono text-xs">
                        <div class="bg-white/80 p-2 rounded-lg border border-slate-200/80">
                          <div class="text-[9px] font-sans font-bold text-slate-500 uppercase">SN P9 (Part 15):</div>
                          <div class="font-black text-slate-900 truncate">{{ it.part15_sn || '-' }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                          <div class="bg-white/80 p-1.5 rounded-lg border border-slate-200/80 truncate">
                            <span class="text-[8.5px] font-sans text-slate-400 block">PN 1:</span>
                            <strong class="text-slate-800">{{ it.part1_sn || '-' }}</strong>
                          </div>
                          <div class="bg-white/80 p-1.5 rounded-lg border border-slate-200/80 truncate">
                            <span class="text-[8.5px] font-sans text-slate-400 block">PN 12:</span>
                            <strong class="text-slate-800">{{ it.part12_sn || '-' }}</strong>
                          </div>
                        </div>
                      </div>

                      <!-- Failure reason snippet if not good -->
                      <div v-if="getAssemblyStageStatus(it) !== 'Good' && (it.assembly_reason || it.reason)"
                        class="bg-amber-100/60 border border-amber-200 p-2 rounded-lg text-[10.5px] text-amber-950 italic">
                        <i class="fa-solid fa-triangle-exclamation text-amber-700 mr-1"></i>
                        <span>{{ it.assembly_reason || it.reason }}</span>
                      </div>
                    </div>

                    <!-- Action Button: Detail Rakitan -->
                    <div class="pt-2 mt-auto">
                      <button type="button" @click="openAssemblyDetailModal(it, tray)"
                        class="w-full bg-white hover:bg-slate-100 text-slate-800 font-bold py-1.5 px-3 rounded-xl text-xs border border-slate-300 shadow-xs transition flex items-center justify-center space-x-1.5 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-circle-info text-blue-600"></i>
                        <span>Detail Rakitan</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- VIEW MODE 2: TABLE VIEW (SEMUA UNIT) - DEFAULT -->
          <div v-else-if="productionViewMode === 'table'"
            class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr
                    class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3.5 text-center w-12">No</th>
                    <th class="py-3 px-3.5">No. Work Order</th>
                    <th class="py-3 px-3 text-center">Set #</th>
                    <th class="py-3 px-3.5">SN P9 (Part 15)</th>
                    <th class="py-3 px-3 font-mono">PN 1</th>
                    <th class="py-3 px-3 font-mono">PN 12</th>
                    <th class="py-3 px-3.5 text-center">Status Rakitan</th>
                    <th class="py-3 px-3.5">Operator Assembly</th>
                    <th class="py-3 px-3.5">Alasan / Catatan</th>
                    <th class="py-3 px-3.5 text-center w-28">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-if="flatFilteredAssemblyResults.length === 0">
                    <td colspan="10" class="py-8 text-center text-slate-400 font-bold">
                      Tidak ada data unit rakitan yang sesuai dengan filter.
                    </td>
                  </tr>
                  <tr v-for="(it, idx) in pagedAssemblyResults" :key="it.assembly_id || idx"
                    class="hover:bg-slate-50/80 transition-colors">
                    <td class="py-2.5 px-3.5 text-center text-slate-400 font-mono text-[11px]">
                      {{ (((pgState?.assemblyResults?.page || 1) - 1) * (pgState?.assemblyResults?.size || 10)) + idx + 1 }}
                    </td>
                    <td class="py-2.5 px-3.5 font-mono font-bold text-slate-900">
                      <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded-md border border-slate-200">{{
                        it.tray_code || '-' }}</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-black text-slate-800">
                      Set #{{ it.set_number }}
                    </td>
                    <td class="py-2.5 px-3.5 font-mono font-black text-slate-900">{{ it.part15_sn || '-' }}</td>
                    <td class="py-2.5 px-3 font-mono font-medium text-slate-700">{{ it.part1_sn || '-' }}</td>
                    <td class="py-2.5 px-3 font-mono font-medium text-slate-700">{{ it.part12_sn || '-' }}</td>
                    <td class="py-2.5 px-3.5 text-center">
                      <span v-if="isItemPendingAdmin(it) && getAssemblyStageStatus(it) !== 'Good'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-clock-rotate-left text-[8.5px]"></i> {{ getItemPendingLabel(it) }}
                      </span>
                      <span
                        v-else-if="getAssemblyStageStatus(it) === 'Declined'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-circle-xmark text-[8.5px]"></i> DECLINED
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'RU'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-arrows-rotate text-[8.5px]"></i> RU
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Good'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[8.5px]"></i> GOOD
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Hold'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Scrap'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-ban text-[8.5px]"></i> SCRAP
                      </span>
                      <span v-else
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[8.5px]"></i> GOOD
                      </span>
                    </td>
                    <td class="py-2.5 px-3.5 text-slate-700 font-medium">
                      {{ it.pic_assembly_fullname || it.pic_assembly || '-' }}
                    </td>
                    <td class="py-2.5 px-3.5 text-slate-600 max-w-xs truncate"
                      :title="it.assembly_reason || it.reason || ''">
                      <span v-if="getAssemblyStageStatus(it) !== 'Good' && (it.assembly_reason || it.reason)"
                        class="text-amber-800 text-[11px] italic">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1 text-[10px]"></i>
                        {{ it.assembly_reason || it.reason }}
                      </span>
                      <span v-else class="text-slate-400">-</span>
                    </td>
                    <td class="py-2.5 px-3.5 text-center">
                      <button type="button" @click="openAssemblyDetailModal(it)"
                        class="bg-white hover:bg-slate-100 text-slate-800 font-bold py-1 px-2.5 rounded-lg text-xs border border-slate-300 shadow-2xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer">
                        <i class="fa-solid fa-circle-info text-blue-600 text-[11px]"></i>
                        <span>Detail</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination: assemblyResults Table View -->
              <div v-if="pgState?.assemblyResults"
                class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/70 select-none no-print flex-wrap gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs text-slate-500 font-semibold">
                    Menampilkan <strong class="text-slate-800">{{ flatFilteredAssemblyResults.length === 0 ? 0 :
                      (((pgState.assemblyResults.page - 1) * pgState.assemblyResults.size) + 1) }}-{{
                      Math.min(pgState.assemblyResults.page * pgState.assemblyResults.size,
                      flatFilteredAssemblyResults.length) }}</strong> dari <strong class="text-slate-800">{{
                      flatFilteredAssemblyResults.length }}</strong> unit rakitan
                  </span>
                  <div class="flex items-center gap-1.5 ml-2 text-xs text-slate-500">
                    <span class="text-[11px]">Per halaman:</span>
                    <select v-model="pgState.assemblyResults.size" @change="pgState.assemblyResults.page = 1"
                      class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-bold text-slate-700 outline-none cursor-pointer">
                      <option :value="10">10</option>
                      <option :value="20">20</option>
                      <option :value="50">50</option>
                      <option :value="100">100</option>
                    </select>
                  </div>
                </div>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.assemblyResults.page = 1"
                    :disabled="pgState.assemblyResults.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angles-left"></i>
                  </button>
                  <button type="button" @click="pgState.assemblyResults.page--"
                    :disabled="pgState.assemblyResults.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angle-left"></i>
                  </button>
                  <span class="text-xs font-black text-slate-800 px-2">Hal {{ pgState.assemblyResults.page }} / {{
                    totalPgAssemblyResults }}</span>
                  <button type="button" @click="pgState.assemblyResults.page++"
                    :disabled="pgState.assemblyResults.page >= totalPgAssemblyResults"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angle-right"></i>
                  </button>
                  <button type="button" @click="pgState.assemblyResults.page = totalPgAssemblyResults"
                    :disabled="pgState.assemblyResults.page >= totalPgAssemblyResults"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angles-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- VIEW MODE 3: CARD VIEW (OPSI) -->
          <div v-else-if="productionViewMode === 'cards'" class="space-y-4">
            <div v-if="flatFilteredAssemblyResults.length === 0"
              class="p-12 text-center text-slate-400 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <i class="fa-solid fa-boxes-packing text-4xl mb-2 block"></i>
              <span>Tidak ada data unit rakitan yang sesuai dengan filter</span>
            </div>
            <div v-else class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5">
                <div v-for="(it, idx) in pagedAssemblyResults" :key="it.assembly_id || idx"
                  class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs hover:shadow-md transition flex flex-col justify-between space-y-3">
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <span
                        class="font-mono font-black text-xs bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg border border-slate-200">{{
                        it.tray_code || '-' }}</span>
                      <span
                        class="text-xs font-mono font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100">Set
                        #{{ it.set_number }}</span>
                    </div>
                    <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 space-y-1 text-xs">
                      <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-[10px]">SN P9 (Part 15):</span>
                        <strong class="font-mono text-slate-900">{{ it.part15_sn || '-' }}</strong>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-[10px]">PN 1 / PN 12:</span>
                        <span class="font-mono text-slate-700 text-[11px]">{{ it.part1_sn || '-' }} / {{ it.part12_sn ||
                          '-' }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-400 text-[10px]">Operator:</span>
                        <span class="text-slate-700 font-semibold">{{ it.pic_assembly_fullname || it.pic_assembly || '-'
                          }}</span>
                      </div>
                    </div>
                    <div>
                      <span v-if="isItemPendingAdmin(it) && getAssemblyStageStatus(it) !== 'Good'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-clock-rotate-left text-[8.5px]"></i> {{ getItemPendingLabel(it) }}
                      </span>
                      <span
                        v-else-if="getAssemblyStageStatus(it) === 'Declined'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-circle-xmark text-[8.5px]"></i> DECLINED
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'RU'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-arrows-rotate text-[8.5px]"></i> RU
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Good'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-circle-check text-[8.5px]"></i> GOOD
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Hold'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                      </span>
                      <span v-else-if="getAssemblyStageStatus(it) === 'Scrap'"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-ban text-[8.5px]"></i> SCRAP
                      </span>
                      <span v-else
                        class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1 w-full justify-center">
                        <i class="fa-solid fa-circle-check text-[8.5px]"></i> GOOD
                      </span>
                    </div>
                    <div v-if="getAssemblyStageStatus(it) !== 'Good' && (it.assembly_reason || it.reason)"
                      class="p-2 bg-amber-50 rounded-lg border border-amber-200 text-[10px] text-amber-900 italic">
                      <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i>
                      {{ it.assembly_reason || it.reason }}
                    </div>
                  </div>
                  <button type="button" @click="openAssemblyDetailModal(it)"
                    class="w-full bg-slate-50 hover:bg-slate-100 text-slate-800 font-bold py-1.5 px-3 rounded-xl text-xs border border-slate-300 shadow-2xs transition flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                    <i class="fa-solid fa-circle-info text-blue-600 text-[11px]"></i>
                    <span>Detail Rakitan</span>
                  </button>
                </div>
              </div>

              <!-- Pagination: assemblyResults Card View -->
              <div v-if="pgState?.assemblyResults"
                class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-2xl shadow-xs select-none no-print flex-wrap gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs text-slate-500 font-semibold">
                    Menampilkan <strong class="text-slate-800">{{ flatFilteredAssemblyResults.length === 0 ? 0 :
                      (((pgState.assemblyResults.page - 1) * pgState.assemblyResults.size) + 1) }}-{{
                      Math.min(pgState.assemblyResults.page * pgState.assemblyResults.size,
                      flatFilteredAssemblyResults.length) }}</strong> dari <strong class="text-slate-800">{{
                      flatFilteredAssemblyResults.length }}</strong> unit rakitan
                  </span>
                  <div class="flex items-center gap-1.5 ml-2 text-xs text-slate-500">
                    <span class="text-[11px]">Per halaman:</span>
                    <select v-model="pgState.assemblyResults.size" @change="pgState.assemblyResults.page = 1"
                      class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-bold text-slate-700 outline-none cursor-pointer">
                      <option :value="10">10</option>
                      <option :value="20">20</option>
                      <option :value="50">50</option>
                      <option :value="100">100</option>
                    </select>
                  </div>
                </div>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.assemblyResults.page = 1"
                    :disabled="pgState.assemblyResults.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angles-left"></i>
                  </button>
                  <button type="button" @click="pgState.assemblyResults.page--"
                    :disabled="pgState.assemblyResults.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angle-left"></i>
                  </button>
                  <span class="text-xs font-black text-slate-800 px-2">Hal {{ pgState.assemblyResults.page }} / {{
                    totalPgAssemblyResults }}</span>
                  <button type="button" @click="pgState.assemblyResults.page++"
                    :disabled="pgState.assemblyResults.page >= totalPgAssemblyResults"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angle-right"></i>
                  </button>
                  <button type="button" @click="pgState.assemblyResults.page = totalPgAssemblyResults"
                    :disabled="pgState.assemblyResults.page >= totalPgAssemblyResults"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angles-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>


@endverbatim
