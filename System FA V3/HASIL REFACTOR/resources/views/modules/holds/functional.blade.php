@verbatim

      <!-- ================= TAB: HASIL TEST FUNCTIONAL 1 DAN 2 (BARANG PRODUKSI HOLD) ================= -->

      <section v-if="currentTab === 'functional_hold'" class="space-y-4 sm:space-y-6 animate-fadeIn">

        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">

          <!-- Header Barang Produksi -->

          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">

            <div>

              <div class="flex items-center space-x-2.5">

                <div

                  class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-lg font-bold">

                  <i class="fa-solid fa-vial-circle-check"></i>

                </div>

                <div>

                  <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Barang Produksi (Unit Rakitan

                    Hold)</h2>

                  <p class="text-xs text-slate-500 font-medium mt-0.5">

                    Hasil Pengujian Test Functional 1 (Assembly) &amp; Test Functional 2 (Quality Control).

                  </p>

                </div>

              </div>

            </div>

            <div class="flex items-center gap-2 flex-wrap">

              <!-- View Mode Toggle: Default Tabel -->

              <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">

                <button type="button" @click="functionalHoldViewMode = 'table'"

                  :class="functionalHoldViewMode === 'table' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                  class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">

                  <i class="fa-solid fa-table-list text-xs"></i>

                  <span>Tabel</span>

                </button>

                <button type="button" @click="functionalHoldViewMode = 'cards'"

                  :class="functionalHoldViewMode === 'cards' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                  class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">

                  <i class="fa-solid fa-grip text-xs"></i>

                  <span>Card</span>

                </button>

              </div>



              <!-- Refresh Button -->

              <button @click="loadProductionHoldItems"

                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl font-bold text-xs border border-slate-300 flex items-center space-x-1.5 min-h-[36px] active:scale-95 transition cursor-pointer">

                <i :class="isLoadingProductionHold ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-arrows-rotate'"

                  class="text-slate-500"></i>

                <span>Muat Ulang</span>

              </button>

            </div>

          </div>



          <!-- Summary KPI Cards -->

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">

            <!-- Total Good -->

            <div

              class="bg-gradient-to-br from-emerald-50 to-emerald-100/40 p-3.5 sm:p-4 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">

              <div>

                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Total Unit

                  Good</span>

                <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{ Math.max(0, 1000 -

                  productionHoldCounts.active_hold) }}</span>

                <span class="text-[10px] text-emerald-700 block mt-0.5">Lolos Uji TF 1 &amp; QC</span>

              </div>

              <div

                class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs shrink-0">

                <i class="fa-solid fa-circle-check"></i>

              </div>

            </div>



            <!-- Total Hold -->

            <div

              class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-3.5 sm:p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">

              <div>

                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Unit

                  Hold</span>

                <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{

                  productionHoldCounts.active_hold }}</span>

                <span class="text-[10px] text-slate-500 block mt-0.5">{{ productionHoldCounts.active_hold }} Aktif butuh

                  tindakan ({{ productionHoldCounts.total }} total)</span>

              </div>

              <div

                class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-xs">

                <i class="fa-solid fa-boxes-stacked"></i>

              </div>

            </div>



            <!-- Hold Tester Assembly -->

            <div

              class="bg-gradient-to-br from-amber-50/60 to-amber-100/30 p-3.5 sm:p-4 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">

              <div>

                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Hold Tester

                  Assembly</span>

                <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{

                  productionHoldCounts.active_assy }}</span>

                <span class="text-[10px] text-amber-700 block mt-0.5">Aktif butuh tindakan</span>

              </div>

              <div

                class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">

                <i class="fa-solid fa-screwdriver-wrench"></i>

              </div>

            </div>



            <!-- Hold Quality Control -->

            <div

              class="bg-gradient-to-br from-rose-50/60 to-rose-100/30 p-3.5 sm:p-4 rounded-2xl border border-rose-200 shadow-xs flex items-center justify-between">

              <div>

                <span class="text-[10px] font-black uppercase tracking-wider text-rose-800 block">Hold Quality

                  Control</span>

                <span class="text-xl sm:text-2xl font-black text-rose-950 font-mono">{{ productionHoldCounts.active_qc

                  }}</span>

                <span class="text-[10px] text-rose-700 block mt-0.5">Aktif butuh tindakan</span>

              </div>

              <div

                class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm shadow-xs">

                <i class="fa-solid fa-triangle-exclamation"></i>

              </div>

            </div>



            <!-- Re-Registered / Processed -->

            <div

              class="bg-gradient-to-br from-indigo-50/60 to-indigo-100/30 p-3.5 sm:p-4 rounded-2xl border border-indigo-200 shadow-xs flex items-center justify-between">

              <div>

                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-800 block">Proses Daftar

                  Ulang</span>

                <span class="text-xl sm:text-2xl font-black text-indigo-950 font-mono">{{

                  productionHoldCounts.reregistered }}</span>

                <span class="text-[10px] text-indigo-700 block mt-0.5">Diajukan perbaikan / Rakit Ulang</span>

              </div>

              <div

                class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm shadow-xs">

                <i class="fa-solid fa-arrow-rotate-left"></i>

              </div>

            </div>

          </div>



          <!-- Controls: Filters by Product, Source, and Search Bar -->

          <div class="space-y-3 pt-1">

            <!-- Filter Pills by Source -->

            <div class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">

              <button type="button" @click="productionSourceFilter = 'ALL'"

                :class="productionSourceFilter === 'ALL' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">

                <i class="fa-solid fa-layer-group text-xs text-slate-400"></i>

                <span>Semua Sumber Hold</span>

                <span

                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold">{{

                  productionHoldCounts.total }}</span>

              </button>

              <button type="button" @click="productionSourceFilter = 'ASSY'"

                :class="productionSourceFilter === 'ASSY' ? 'bg-amber-500 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">

                <i class="fa-solid fa-screwdriver-wrench text-xs"></i>

                <span>Hold Tester Assembly</span>

                <span

                  :class="productionSourceFilter === 'ASSY' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800'"

                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{

                  productionHoldCounts.active_assy }}</span>

              </button>

              <button type="button" @click="productionSourceFilter = 'QC'"

                :class="productionSourceFilter === 'QC' ? 'bg-rose-600 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">

                <i class="fa-solid fa-triangle-exclamation text-xs"></i>

                <span>Hold Quality Control (QC)</span>

                <span :class="productionSourceFilter === 'QC' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800'"

                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{

                  productionHoldCounts.active_qc }}</span>

              </button>

              <button type="button" @click="productionSourceFilter = 'RU'"

                :class="productionSourceFilter.startsWith('RU') ? 'bg-indigo-600 text-white font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"

                class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">

                <i class="fa-solid fa-arrow-rotate-left text-xs"></i>

                <span>Perakitan Ulang (RU)</span>

                <span

                  :class="productionSourceFilter.startsWith('RU') ? 'bg-indigo-700 text-white' : 'bg-indigo-100 text-indigo-800'"

                  class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{

                  productionHoldCounts.reregistered }}</span>

              </button>

            </div>

            <!-- Sub-Filter Khusus Perakitan Ulang (RU) untuk Pelacakan Tahap Asal TF 1 vs TF 2 -->
            <div v-if="productionSourceFilter.startsWith('RU')" class="flex flex-wrap items-center gap-2 p-2 bg-indigo-50/70 border border-indigo-200 rounded-xl">
              <span class="text-xs font-black text-indigo-950 flex items-center gap-1">
                <i class="fa-solid fa-filter text-indigo-600"></i> Kelompokkan Asal RU:
              </span>
              <button type="button" @click="productionSourceFilter = 'RU'"
                :class="productionSourceFilter === 'RU' ? 'bg-indigo-600 text-white shadow-xs font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300 font-bold'"
                class="px-3 py-1 rounded-lg text-xs transition cursor-pointer">
                Semua RU ({{ productionHoldCounts.ru }})
              </button>
              <button type="button" @click="productionSourceFilter = 'RU_ASSY'"
                :class="productionSourceFilter === 'RU_ASSY' ? 'bg-amber-600 text-white shadow-xs font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300 font-bold'"
                class="px-3 py-1 rounded-lg text-xs transition cursor-pointer">
                RU dari TF 1 ({{ productionHoldCounts.ru_assy }})
              </button>
              <button type="button" @click="productionSourceFilter = 'RU_QC'"
                :class="productionSourceFilter === 'RU_QC' ? 'bg-rose-600 text-white shadow-xs font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-300 font-bold'"
                class="px-3 py-1 rounded-lg text-xs transition cursor-pointer">
                RU dari TF 2 / QC ({{ productionHoldCounts.ru_qc }})
              </button>
            </div>




            <!-- Product Dropdown & Search Bar -->

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

              <!-- Filtering by Product -->

              <div>

                <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter produk:</label>

                <select v-model="selectedProductionProduct"

                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-rose-600 min-h-[38px] cursor-pointer">

                  <option value="ALL">Semua jenis produk</option>

                  <option v-for="prod in productionProductsList" :key="prod" :value="prod">{{ prod }}</option>

                </select>

              </div>



              <!-- Search Bar -->

              <div class="sm:col-span-2">

                <label class="block text-[10px] font-bold text-slate-500 mb-1">Cari data unit:</label>

                <div class="relative">

                  <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>

                  <input type="text" v-model="productionSearchQuery"

                    placeholder="Cari SN P9 Part 15 / Part 12 / Part 1 / Tray / Alasan Kendala..."

                    class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-rose-600 min-h-[38px]">

                </div>

              </div>

            </div>

          </div>



          <!-- VIEW MODE 1: COMPACT DATA TABLE (DEFAULT SESUAI PERMINTAAN USER) -->

          <div v-if="functionalHoldViewMode === 'table' && filteredProductionHoldList.length > 0"

            class="overflow-x-auto border border-slate-200 rounded-2xl shadow-xs">

            <table class="w-full text-left text-xs border-collapse bg-white font-sans">

              <thead>

                <tr

                  class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px] tracking-wider">

                  <th class="py-3 px-3 text-center w-12">No</th>

                  <th class="py-3 px-3">Produk</th>

                  <th class="py-3 px-3">SN P9 Part 15</th>

                  <th class="py-3 px-3 font-mono">PN P9 Part 1</th>

                  <th class="py-3 px-3 font-mono">PN P9 Part 12</th>

                  <th class="py-3 px-3">Tray &amp; Set</th>

                  <th class="py-3 px-3">Sumber Hold</th>

                  <th class="py-3 px-3">Alasan Kendala</th>

                  <th class="py-3 px-3">Waktu Hold</th>

                  <th class="py-3 px-3 text-center">Aksi</th>

                </tr>

              </thead>

              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">

                <tr v-for="(item, idx) in pagedProductionHoldList" :key="item.assembly_id || item.id"

                  class="hover:bg-blue-50/40 transition-colors">

                  <td class="p-3 text-center font-sans font-bold text-slate-500">{{ idx + 1 +

                    (pgState.productionHold.page - 1) * pgState.productionHold.size }}</td>

                  <td class="p-3 font-sans font-bold text-slate-800">{{ item.product_type || 'Nanggala P9' }}</td>

                  <td class="p-3 font-black text-slate-900 bg-slate-50/60">{{ item.part15_sn || '-' }}</td>

                  <td class="p-3 text-slate-700">{{ item.part1_sn || '-' }}</td>

                  <td class="p-3 text-slate-700">{{ item.part12_sn || '-' }}</td>

                  <td class="p-3 font-sans font-bold text-slate-800">

                    <span>{{ item.tray_code }}</span>

                    <span class="text-[10px] text-slate-500 block">Set #{{ item.set_number }}</span>

                  </td>

                  <td class="p-3 font-sans space-y-1">

                    <div class="flex items-center gap-1">

                      <span class="font-bold text-[10px] text-slate-500">Assy:</span>

                      <span v-if="item.hold_source === 'TESTER_ASSEMBLY' && isItemPendingAdmin(item)"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-0.5"

                        title="Sedang menunggu persetujuan verifikasi Admin">

                        <i class="fa-solid fa-lock text-[8px]"></i> MENUNGGU VERIFIKASI

                      </span>

                      <span v-else-if="isReassemblySet(item) && (item.hold_source === 'TESTER_ASSEMBLY' || item.assy_test_status === 'Hold TF 1' || Boolean(item.assy_failure_reasons))"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-0.5">

                        <i class="fa-solid fa-arrows-rotate text-[8px]"></i> RU

                      </span>

                      <span

                        v-else-if="item.assy_test_status === 'Hold TF 1' || (item.hold_source === 'TESTER_ASSEMBLY' && !['Good', 'GOOD'].includes(item.assy_test_status))"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 inline-flex items-center gap-0.5">

                        <i class="fa-solid fa-hand text-[8px]"></i> HOLD TF 1

                      </span>

                      <span v-else-if="item.assy_test_status === 'Good' || item.assy_doc_number || item.hold_source === 'QC' || item.qc_tested_at || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(item.qc_status)"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">

                        GOOD

                      </span>

                      <span v-else

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-300">

                        BELUM DIUJI

                      </span>

                    </div>

                    <div class="flex items-center gap-1">

                      <span class="font-bold text-[10px] text-slate-500">QC:</span>

                      <span v-if="item.hold_source === 'QC' && isItemPendingAdmin(item)"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-0.5"

                        title="Sedang menunggu persetujuan verifikasi Admin">

                        <i class="fa-solid fa-lock text-[8px]"></i> MENUNGGU VERIFIKASI

                      </span>

                      <span v-else-if="item.hold_source === 'QC' && (isReassemblySet(item) || item.qc_status === 'REWORK_QC' || item.qc_status === 'RU')"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-0.5">

                        <i class="fa-solid fa-arrows-rotate text-[8px]"></i> RU

                      </span>

                      <span

                        v-else-if="item.hold_source === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'HOLD'"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-0.5">

                        <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> HOLD

                      </span>

                      <span v-else-if="item.qc_status === 'GOOD' || item.qc_status === 'GOOD_QC'"

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">

                        GOOD

                      </span>

                      <span v-else

                        class="px-1.5 py-0.5 rounded text-[9.5px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-300">

                        PENDING

                      </span>

                    </div>

                  </td>

                  <td class="p-3 font-sans text-xs max-w-xs text-slate-800 italic">

                    {{ formatQCReworkReason(item) }}

                  </td>

                  <td class="p-3 font-sans text-[10.5px] text-slate-600">

                    {{ formatDateTime(item.qc_tested_at || item.assy_tested_at || item.finished_at) }}

                  </td>

                  <td class="p-3 font-sans text-center">

                    <div class="flex items-center justify-center gap-1.5">

                      <button @click="handleHoldItemDetail(item)"

                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 flex items-center justify-center shadow-xs transition cursor-pointer active:scale-95 shrink-0"

                        title="Lihat Rincian Detail Rakitan">

                        <i class="fa-solid fa-eye text-xs"></i>

                      </button>

                      <!-- 1. JIKA SEDANG MENUNGGU VERIFIKASI ADMIN: TERKUNCI MUTLAK! -->

                      <template v-if="isItemPendingAdmin(item)">

                        <span

                          class="h-8 px-2.5 rounded-xl text-[10.5px] font-bold bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1 shadow-xs whitespace-nowrap"

                          title="Permohonan sedang dalam antrean verifikasi Admin (Terkunci)">

                          <i class="fa-solid fa-lock text-[10px] text-amber-700"></i> Menunggu Verifikasi

                        </span>

                      </template>

                      <!-- 2. JIKA SUDAH DIALIHKAN KE RU: HANYA ADA TOMBOL DETAIL (READ-ONLY) -->

                      <template v-else-if="isReassemblySet(item) || isItemInReassemblyProcess(item)">

                        <span

                          class="h-8 px-2.5 rounded-xl text-[10.5px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-300 inline-flex items-center gap-1 shadow-xs whitespace-nowrap select-none"

                          title="Set telah disetujui ganti part dan dialihkan ke antrean Perakitan Ulang">

                          <i class="fa-solid fa-arrows-rotate text-[10px] text-indigo-600"></i> Sudah Dialihkan ke RU

                        </span>

                      </template>

                      <!-- 3. JIKA AKTIF HOLD: TAMPILKAN AKSI LANJUT & GANTI PART -->

                      <template v-else>

                        <button @click="proceedHoldItem(item)"

                          class="h-8 px-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center justify-center gap-1 shadow-xs transition cursor-pointer active:scale-95 shrink-0 text-xs"

                          :title="getHoldProceedButtonTitle(item)">

                          <i class="fa-solid fa-circle-arrow-right text-xs"></i>

                          <span>{{ getHoldProceedButtonLabel(item) }}</span>

                        </button>

                        <button @click="openReplaceForHoldItem(item)"

                          class="h-8 px-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold flex items-center justify-center gap-1 shadow-xs transition cursor-pointer active:scale-95 shrink-0 text-xs"

                          title="Ganti Part Bermasalah dengan Stok Gudang">

                          <i class="fa-solid fa-wrench text-xs"></i>

                          <span>Ganti Part</span>

                        </button>

                      </template>

                    </div>

                  </td>

                </tr>

              </tbody>

            </table>

          </div>



          <!-- VIEW MODE 2: CARD GRID (OPSI TAMPILAN PERSIS FOTO 1) -->

          <div v-else-if="functionalHoldViewMode === 'cards' && filteredProductionHoldList.length > 0"

            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">

            <div v-for="item in pagedProductionHoldList" :key="item.assembly_id || item.id"

              :class="(item.assembly_status === 'Rework' || item.status === 'Rework' || item.qc_status === 'REWORK_QC' || item.verified_status === 'REWORK_GUDANG' || item.verified_status === 'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC') ? 'border-purple-200 bg-purple-50/15 hover:border-purple-300 hover:shadow-purple-100' : (item.hold_source === 'QC' ? 'border-rose-200 bg-rose-50/15 hover:border-rose-300 hover:shadow-rose-100' : 'border-amber-200 bg-amber-50/15 hover:border-amber-300 hover:shadow-amber-100')"

              class="border-2 rounded-2xl p-4 sm:p-5 flex flex-col justify-between space-y-3.5 shadow-xs hover:shadow-md transition duration-200 bg-white">



              <!-- Card Top Info -->

              <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">

                <div class="min-w-0">

                  <div class="flex items-center gap-1.5 flex-wrap">

                    <span class="text-xs font-black font-mono px-2.5 py-0.5 rounded-lg bg-slate-900 text-white">

                      {{ item.tray_code || '-' }}

                    </span>

                    <span

                      class="text-xs font-black text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg">

                      Set #{{ item.set_number }}

                    </span>

                    <span

                      class="text-[10.5px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-lg border border-slate-200">

                      {{ item.product_type || 'Nanggala P9' }}

                    </span>

                  </div>

                  <div class="text-[11px] text-slate-500 font-medium mt-1 truncate">

                    <i class="fa-solid fa-user-gear text-[10px] mr-1"></i>

                    <span>Assy: {{ getOperatorFullName(item.pic_assembly) || item.pic_assembly || '-' }}</span>

                  </div>

                </div>



                <!-- Hold / Verification Status Badge -->

                <div class="flex-shrink-0">

                  <span v-if="isReassemblySet(item)"

                    class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-900 border border-indigo-300 flex items-center gap-1 shadow-xs whitespace-nowrap">

                    <i class="fa-solid fa-arrows-rotate text-xs text-indigo-700"></i> RU (Dirakit Ulang)

                  </span>

                  <span

                    v-else-if="item.is_reregistered || (item.verified_status && item.verified_status.startsWith('PENDING_ADMIN')) || item.has_pending_replacement"

                    class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 shadow-xs whitespace-nowrap">

                    <i class="fa-solid fa-lock text-xs text-amber-700"></i> Pending Verifikasi

                  </span>

                  <span

                    v-else-if="item.qc_status === 'REWORK_QC' || item.verified_status === 'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK_QC'"

                    class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-300 flex items-center gap-1 shadow-xs whitespace-nowrap">

                    <i class="fa-solid fa-rotate-left text-xs text-purple-600"></i> Rework QC

                  </span>

                  <span

                    v-else-if="item.assembly_status === 'Rework' || item.status === 'Rework' || item.verified_status === 'REWORK_GUDANG' || item.lifecycle_path === 'REWORK'"

                    class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-300 flex items-center gap-1 shadow-xs whitespace-nowrap">

                    <i class="fa-solid fa-rotate-left text-xs text-purple-600"></i> Rework Test

                  </span>

                  <span

                    v-else-if="item.verified_status === 'APPROVED' || item.verified_status === 'APPROVED_QC' || item.verified_status === 'ACC'"

                    class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1 shadow-xs whitespace-nowrap">

                    <i class="fa-solid fa-check-double text-xs text-emerald-600"></i> Terverifikasi

                  </span>

                  <span

                    v-else-if="item.hold_source === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'HOLD'"

                    class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300 flex items-center gap-1 whitespace-nowrap">

                    <i class="fa-solid fa-hand text-xs"></i> Hold QC

                  </span>

                  <span v-else

                    class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1 whitespace-nowrap">

                    <i class="fa-solid fa-hand text-xs"></i> Hold Test

                  </span>

                </div>

              </div>



              <!-- Status Proses Berdampingan: Test Assembly & QC (Persis Foto 1) -->

              <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200 space-y-1.5 text-xs font-sans">

                <div class="flex items-center justify-between">

                  <div class="flex items-center gap-1.5">

                    <span class="font-bold text-slate-700">Test Assembly:</span>

                    <span v-if="item.hold_source === 'TESTER_ASSEMBLY' && isItemPendingAdmin(item)"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1"

                      title="Sedang menunggu persetujuan verifikasi Admin">

                      <i class="fa-solid fa-lock text-[9px]"></i> MENUNGGU VERIFIKASI

                    </span>

                    <span v-else-if="isReassemblySet(item) && (item.hold_source === 'TESTER_ASSEMBLY' || item.assy_test_status === 'Hold TF 1' || Boolean(item.assy_failure_reasons))"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-arrows-rotate text-[9px]"></i> RU

                    </span>

                    <span

                      v-else-if="item.assy_test_status === 'Hold TF 1' || (item.hold_source === 'TESTER_ASSEMBLY' && !['Good', 'GOOD'].includes(item.assy_test_status))"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-hand text-[9px]"></i> HOLD TF 1

                    </span>

                    <span

                      v-else-if="item.assy_test_status === 'Good' || item.assy_doc_number || ['qc', 'QC'].includes(item.process_stage) || item.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(item.qc_status) || item.qc_tested_at || item.qc_doc_number || item.hold_source === 'QC'"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-check text-[9px]"></i> GOOD

                    </span>

                    <span v-else

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-slate-100 text-slate-700 border border-slate-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-clock text-[9px]"></i> BELUM DIUJI

                    </span>

                  </div>

                  <div class="flex items-center gap-1.5">

                    <span class="font-bold text-slate-700">QC:</span>

                    <span v-if="item.hold_source === 'QC' && isItemPendingAdmin(item)"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1"

                      title="Sedang menunggu persetujuan verifikasi Admin">

                      <i class="fa-solid fa-lock text-[9px]"></i> MENUNGGU VERIFIKASI

                    </span>

                    <span v-else-if="item.hold_source === 'QC' && (isReassemblySet(item) || item.qc_status === 'REWORK_QC' || item.qc_status === 'RU')"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-arrows-rotate text-[9px]"></i> RU

                    </span>

                    <span

                      v-else-if="item.hold_source === 'QC' || item.qc_status === 'HOLD_QC' || item.qc_status === 'HOLD'"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> HOLD

                    </span>

                    <span v-else-if="item.qc_status === 'GOOD' || item.qc_status === 'GOOD_QC'"

                      class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">

                      <i class="fa-solid fa-check text-[9px]"></i> GOOD

                    </span>

                    <span v-else

                      class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase bg-slate-100 text-slate-700 border border-slate-300">

                      PENDING

                    </span>

                  </div>

                </div>

              </div>



              <!-- Component Serial Numbers (3 Parts Set) -->

              <div class="space-y-2 font-mono text-xs">

                <!-- Part 15 SN -->

                <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200">

                  <div class="text-[9px] font-sans font-bold text-slate-500 uppercase tracking-wider">SN P9 Part 15:

                  </div>

                  <div class="text-sm sm:text-base font-black text-slate-900 tracking-wide font-mono mt-0.5 truncate">

                    {{ item.part15_sn || '-' }}

                  </div>

                </div>



                <!-- Part 1 & Part 12 PN Grid -->

                <div class="grid grid-cols-2 gap-2">

                  <div class="bg-slate-50 p-2 rounded-xl border border-slate-200">

                    <div class="text-[9px] font-sans font-bold text-slate-500 uppercase tracking-wider">PN Part 1:</div>

                    <div class="font-bold text-slate-800 font-mono mt-0.5 truncate text-[11px]">

                      {{ item.part1_sn || '-' }}

                    </div>

                  </div>

                  <div class="bg-slate-50 p-2 rounded-xl border border-slate-200">

                    <div class="text-[9px] font-sans font-bold text-slate-500 uppercase tracking-wider">PN Part 12:

                    </div>

                    <div class="font-bold text-slate-800 font-mono mt-0.5 truncate text-[11px]">

                      {{ item.part12_sn || '-' }}

                    </div>

                  </div>

                </div>

              </div>



              <!-- Failure / Hold / Rework Reason Box -->

              <div

                :class="(item.assembly_status === 'Rework' || item.status === 'Rework' || item.qc_status === 'REWORK_QC' || item.verified_status === 'REWORK_GUDANG' || item.verified_status === 'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC') ? 'bg-purple-50 border-purple-200 text-purple-950' : (item.hold_source === 'QC' ? 'bg-rose-50 border-rose-200 text-rose-900' : 'bg-amber-50 border-amber-200 text-amber-950')"

                class="p-2.5 rounded-xl border text-xs space-y-1">

                <div class="text-[9.5px] font-bold uppercase tracking-wider flex items-center gap-1"

                  :class="(item.assembly_status === 'Rework' || item.status === 'Rework' || item.qc_status === 'REWORK_QC' || item.verified_status === 'REWORK_GUDANG' || item.verified_status === 'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC') ? 'text-purple-800' : (item.hold_source === 'QC' ? 'text-rose-700' : 'text-amber-800')">

                  <i class="fa-solid fa-circle-exclamation"></i>

                  <span>Alasan Kendala {{ (item.assembly_status === 'Rework' || item.status === 'Rework' ||

                    item.qc_status === 'REWORK_QC' || item.verified_status === 'REWORK_GUDANG' || item.verified_status

                    === 'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC') ?

                    '(REWORK)' : (item.hold_source === 'QC' ? '(HOLD QC)' : '(HOLD)') }}:</span>

                </div>

                <div class="font-semibold text-[11px] leading-relaxed italic">

                  "{{ formatQCReworkReason(item) }}"

                </div>

              </div>



              <!-- Inspector & Timestamp Details -->

              <div class="bg-slate-50/80 rounded-xl p-2.5 border border-slate-200 text-[11px] space-y-1 font-sans">

                <div v-if="item.hold_source === 'QC'" class="flex items-center justify-between text-slate-600">

                  <span>Inspector QC:</span>

                  <span class="font-bold text-rose-800">{{ getOperatorFullName(item.pic_qc) || item.pic_qc || '-'

                    }}</span>

                </div>

                <div v-else class="flex items-center justify-between text-slate-600">

                  <span>Tester Assembly:</span>

                  <span class="font-bold text-amber-800">{{ item.assy_tester_fullname || item.assy_tester_username ||

                    item.pic_assembly || '-' }}</span>

                </div>

                <div class="flex items-center justify-between text-slate-500 text-[10.5px]">

                  <span>{{ (item.assembly_status === 'Rework' || item.status === 'Rework' || item.qc_status ===

                    'REWORK_QC' || item.verified_status === 'REWORK_GUDANG' || item.verified_status ===

                    'REWORK_QC_GUDANG' || item.lifecycle_path === 'REWORK' || item.lifecycle_path === 'REWORK_QC') ?

                    'Waktu Rework:' : 'Waktu Hold:' }}</span>

                  <span class="font-medium text-slate-700">{{ formatDateTime(item.qc_tested_at || item.assy_tested_at ||

                    item.finished_at) }}</span>

                </div>

              </div>



              <!-- Action Buttons -->

              <div class="pt-2 border-t border-slate-100 grid grid-cols-3 gap-1.5 items-center">

                <!-- Detail Button -->

                <button @click="handleHoldItemDetail(item)"

                  class="h-9 w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-2 rounded-xl text-xs border border-slate-300 flex items-center justify-center space-x-1.5 transition cursor-pointer shadow-2xs whitespace-nowrap active:scale-95"

                  title="Lihat rincian detail rakitan">

                  <i class="fa-solid fa-list-check text-slate-500 text-xs shrink-0"></i>

                  <span class="truncate">Detail</span>

                </button>



                <!-- 1. JIKA SEDANG MENUNGGU VERIFIKASI ADMIN: TERKUNCI MUTLAK! -->

                <template v-if="isItemPendingAdmin(item)">

                  <button disabled

                    class="col-span-2 h-9 w-full bg-amber-50 border border-amber-300 text-amber-900 font-bold px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 cursor-not-allowed select-none shadow-2xs whitespace-nowrap truncate"

                    title="Permohonan sedang dalam antrean verifikasi Administrator (Terkunci)">

                    <i class="fa-solid fa-lock text-amber-700 text-xs shrink-0"></i>

                    <span class="truncate">Menunggu Verifikasi</span>

                  </button>

                </template>

                <!-- 2. JIKA SUDAH DIALIHKAN KE RU: HANYA LIHAT DETAIL (READ-ONLY) -->

                <template v-else-if="isReassemblySet(item) || isItemInReassemblyProcess(item)">

                  <div

                    class="col-span-2 h-9 w-full bg-indigo-50 border border-indigo-200 text-indigo-900 font-bold px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5 select-none shadow-2xs whitespace-nowrap truncate"

                    title="Set telah disetujui ganti part dan dialihkan ke antrean Perakitan Ulang">

                    <i class="fa-solid fa-arrows-rotate text-indigo-600 text-xs shrink-0"></i>

                    <span class="truncate">Sudah Dialihkan ke RU</span>

                  </div>

                </template>

                <!-- 3. JIKA AKTIF HOLD: TAMPILKAN AKSI LANJUT & GANTI PART -->

                <template v-else>

                  <button @click="proceedHoldItem(item)"

                    class="h-9 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2 rounded-xl text-xs shadow-2xs flex items-center justify-center space-x-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap truncate"

                    :title="getHoldProceedButtonTitle(item)">

                    <i class="fa-solid fa-circle-arrow-right text-xs shrink-0"></i>

                    <span class="truncate">{{ getHoldProceedButtonLabel(item) }}</span>

                  </button>

                  <button @click="openReplaceForHoldItem(item)"

                    class="h-9 w-full bg-blue-600 hover:bg-blue-700 text-white font-bold px-2 rounded-xl text-xs shadow-2xs flex items-center justify-center space-x-1.5 transition cursor-pointer active:scale-95 whitespace-nowrap truncate"

                    title="Ganti part bermasalah dengan stok gudang">

                    <i class="fa-solid fa-wrench text-xs shrink-0"></i>

                    <span class="truncate">Ganti Part</span>

                  </button>

                </template>

              </div>

            </div>

          </div>



          <!-- Pagination for Hold Perakitan -->

          <div v-if="filteredProductionHoldList.length > 0"

            class="flex items-center justify-between px-4 py-3 bg-white border border-slate-200 rounded-2xl shadow-2xs">

            <span class="text-[11px] text-slate-500 font-semibold">

              Hal <strong class="text-slate-700">{{ pgState.productionHold.page }}</strong> / {{ totalPgProductionHold

              }}

              <span class="text-slate-400 ml-1">({{ (filteredProductionHoldList||[]).length }} data)</span>

            </span>

            <div class="flex items-center space-x-1">

              <button type="button" @click="pgState.productionHold.page = 1"

                :disabled="pgState.productionHold.page <= 1"

                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">

                <i class="fa-solid fa-angles-left"></i>

              </button>

              <button type="button" @click="pgState.productionHold.page--" :disabled="pgState.productionHold.page <= 1"

                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">

                <i class="fa-solid fa-angle-left"></i>

              </button>

              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.productionHold.page }}</span>

              <button type="button" @click="pgState.productionHold.page++"

                :disabled="pgState.productionHold.page >= totalPgProductionHold"

                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">

                <i class="fa-solid fa-angle-right"></i>

              </button>

              <button type="button" @click="pgState.productionHold.page = totalPgProductionHold"

                :disabled="pgState.productionHold.page >= totalPgProductionHold"

                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">

                <i class="fa-solid fa-angles-right"></i>

              </button>

            </div>

          </div>



          <!-- Empty State -->

          <div v-else

            class="p-12 text-center text-slate-400 font-sans border border-slate-200 rounded-2xl bg-slate-50/50 space-y-3">

            <div

              class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl">

              <i class="fa-solid fa-circle-check"></i>

            </div>

            <div>

              <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Barang Produksi yang Hold</h4>

              <p class="text-xs text-slate-500 mt-0.5">Seluruh unit rakitan P9 berada dalam kondisi baik atau telah

                terselesaikan.</p>

            </div>

          </div>

        </div>

      </section>





@endverbatim

