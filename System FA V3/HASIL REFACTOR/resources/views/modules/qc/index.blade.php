@verbatim
      <!-- ================= TAB: UJI FUNGSIONAL QC (QUALITY CONTROL TAHAP 2) ================= -->
      <section v-if="currentTab === 'qc'" class="space-y-4 sm:space-y-6">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Quality Control (QC)</h2>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 font-mono">
                Menunggu QC: <strong class="text-amber-700">{{ qcOverallPendingCount }}</strong> item
              </span>
              <button @click="loadQCPendingItems"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl font-bold text-xs border border-slate-300 flex items-center space-x-1.5 min-h-[38px] active:scale-95 transition cursor-pointer">
                <i class="fa-solid fa-arrows-rotate text-slate-500"></i>
                <span>Muat Ulang</span>
              </button>
            </div>
          </div>

          <!-- KPI Summary Cards (Doc, Pcs, Good, Hold, Belum di-verify) -->
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <!-- 1. Doc Count -->
            <div
              class="bg-gradient-to-br from-slate-50 to-slate-100/70 p-3.5 sm:p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Dokumen</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{ qcOverallSummary.docCount
                  }}</span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Dokumen Tray QC</span>
              </div>
              <div
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-file-lines"></i>
              </div>
            </div>

            <!-- 2. Pcs Count -->
            <div
              class="bg-gradient-to-br from-blue-50/60 to-blue-100/30 p-3.5 sm:p-4 rounded-2xl border border-blue-200 shadow-2xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-800 block">Total Pcs / Set</span>
                <span class="text-xl sm:text-2xl font-black text-blue-950 font-mono">{{ qcOverallSummary.pcsCount
                  }}</span>
                <span class="text-[10px] text-blue-700 block mt-0.5">Set Siap &amp; Diuji</span>
              </div>
              <div
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-boxes-stacked"></i>
              </div>
            </div>

            <!-- 3. Good Count -->
            <div
              class="bg-gradient-to-br from-emerald-50/60 to-emerald-100/30 p-3.5 sm:p-4 rounded-2xl border border-emerald-200 shadow-2xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Good (Lolos)</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{ qcOverallSummary.goodCount
                  }}</span>
                <span class="text-[10px] text-emerald-700 block mt-0.5">Siap ke Gudang</span>
              </div>
              <div
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-circle-check"></i>
              </div>
            </div>

            <!-- 4. Hold Count -->
            <div
              class="bg-gradient-to-br from-amber-50/60 to-amber-100/30 p-3.5 sm:p-4 rounded-2xl border border-amber-200 shadow-2xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Hold QC</span>
                <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{ qcOverallSummary.holdCount
                  }}</span>
                <span class="text-[10px] text-amber-700 block mt-0.5">Butuh Perbaikan</span>
              </div>
              <div
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-hand"></i>
              </div>
            </div>

            <!-- 5. Belum Diverifikasi Count -->
            <div
              class="bg-gradient-to-br from-indigo-50/60 to-indigo-100/30 p-3.5 sm:p-4 rounded-2xl border border-indigo-200 shadow-2xs flex items-center justify-between col-span-2 sm:col-span-1">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-indigo-800 block">Belum
                  Diverifikasi</span>
                <span class="text-xl sm:text-2xl font-black text-indigo-950 font-mono">{{ qcOverallSummary.pendingCount
                  }}</span>
                <span class="text-[10px] text-indigo-700 block mt-0.5">Antrean Uji QC</span>
              </div>
              <div
                class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-hourglass-half"></i>
              </div>
            </div>
          </div>

          <!-- ================= TAB QC: VIEW 1 - DAFTAR DOKUMEN TRAY (TAMPILAN AWAL) ================= -->
          <div v-if="!selectedQCTrayCode" class="space-y-4">
            <!-- Search & Tray Count Toolbar -->
            <div
              class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-200">
              <div class="relative flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                <input type="text" v-model="qcSearchQuery" placeholder="Cari Dokumen Tray / Operator Assembly..."
                  class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600 min-h-[38px]">
              </div>
              <div class="flex items-center gap-2 text-xs text-slate-600 font-medium self-end sm:self-center">
                <span>Total Dokumen:</span>
                <span
                  class="font-bold font-mono bg-white border border-slate-200 px-2.5 py-1 rounded-lg text-slate-900">
                  {{ qcTraySummaryList.length }} Tray
                </span>
              </div>
            </div>

            <!-- Tabel Dokumen Tray -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-white">
              <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                <table class="w-full text-left text-xs border-collapse font-sans">
                  <thead
                    class="bg-slate-100/90 text-slate-700 font-bold uppercase text-[10.5px] border-b border-slate-200 sticky top-0 z-10">
                    <tr>
                      <th class="py-3 px-3 text-center w-12">No</th>
                      <th class="py-3 px-4">Nomor Dokumen Tray</th>
                      <th class="py-3 px-4">Operator Assembly</th>
                      <th class="py-3 px-4">Waktu Pengerjaan</th>
                      <th class="py-3 px-4 text-center">Total Set Siap QC</th>
                      <th class="py-3 px-4">Status Tray QC</th>
                      <th class="py-3 px-4 text-center w-28">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium">
                    <tr v-for="(t, idx) in pagedQcTraySummaryList" :key="t.tray_code"
                      class="hover:bg-slate-50/80 transition">
                      <td class="py-3 px-3 text-center font-mono font-bold text-slate-500">{{ idx + 1 +
                        (pgState.qcTraySummary.page - 1) * pgState.qcTraySummary.size }}</td>
                      <td class="py-3 px-4">
                        <span
                          class="font-mono font-black text-slate-900 bg-slate-100 border border-slate-300 px-2 py-0.5 rounded-md text-[11px]">
                          {{ t.tray_code }}
                        </span>
                      </td>
                      <td class="py-3 px-4 text-slate-700">
                        <div class="font-bold">{{ getOperatorFullName(t.pic_assembly) || t.pic_assembly || '-' }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ t.pic_assembly }}</div>
                      </td>
                      <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                        {{ formatDateTime(t.finished_at) }}
                      </td>
                      <td class="py-3 px-4 text-center font-mono font-bold text-slate-800">
                        <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                          {{ t.total_sets }} Set
                        </span>
                      </td>
                      <td class="py-3 px-4">
                        <div class="flex items-center gap-1.5 flex-wrap text-[10.5px]">
                          <span v-if="t.pending_qc_count > 0"
                            class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-300 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-clock text-[10px]"></i> {{ t.pending_qc_count }} Menunggu
                          </span>
                          <span v-if="t.good_qc_count > 0"
                            class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle-check text-[10px]"></i> {{ t.good_qc_count }} Lolos
                          </span>
                          <span v-if="t.hold_qc_count > 0"
                            class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-300 font-bold flex items-center gap-1">
                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> {{ t.hold_qc_count }} Hold
                          </span>
                        </div>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <button type="button" @click="openQCTrayDetail(t.tray_code)"
                          class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-1.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-sm active:scale-95 transition cursor-pointer">
                          <i class="fa-solid fa-folder-open text-xs"></i>
                          <span>Buka</span>
                        </button>
                      </td>
                    </tr>
                    <tr v-if="qcTraySummaryList.length === 0">
                      <td colspan="7" class="py-12 text-center text-slate-400 font-medium">
                        <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                        Tidak ada data dokumen tray yang menunggu atau telah diproses QC.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination: qcTraySummary -->
              <div
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print mt-1">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.qcTraySummary.page }}</strong> / {{ totalPgQcTraySummary }} <span
                    class="text-slate-400 ml-1">({{ qcTraySummaryList.length }} dokumen)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.qcTraySummary.page = 1"
                    :disabled="pgState.qcTraySummary.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.qcTraySummary.page--"
                    :disabled="pgState.qcTraySummary.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.qcTraySummary.page }}</span>
                  <button type="button" @click="pgState.qcTraySummary.page++"
                    :disabled="pgState.qcTraySummary.page >= totalPgQcTraySummary"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.qcTraySummary.page = totalPgQcTraySummary"
                    :disabled="pgState.qcTraySummary.page >= totalPgQcTraySummary"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- ================= TAB QC: VIEW 2 - CARD GRID PER TRAY DENGAN HASIL TEST ASSEMBLY ================= -->
          <div v-else-if="!showQCTestModal" class="space-y-4">
            <!-- Tray Navigation Bar & Breadcrumb -->
            <div
              class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
              <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="selectedQCTrayCode = null"
                  class="bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-3.5 rounded-xl text-xs border border-slate-300 flex items-center gap-2 shadow-sm active:scale-95 transition cursor-pointer">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>Kembali</span>
                </button>
                <div class="flex items-center gap-1.5">
                  <span class="text-xs text-slate-500 font-medium">Dokumen:</span>
                  <span class="text-xs font-black font-mono px-3 py-1 rounded-xl bg-slate-900 text-white shadow-xs">
                    {{ selectedQCTrayCode }}
                  </span>
                </div>
              </div>

              <div class="text-xs font-mono font-bold text-slate-600 flex items-center gap-2">
                <span>Menampilkan <strong class="text-slate-900">{{ filteredQCList.length }}</strong> Card</span>
              </div>
            </div>

            <!-- QC Filter Pills & Search Bar Scoped to Tray -->
            <div class="space-y-3">
              <!-- Filter Status Tabs -->
              <div class="flex flex-wrap items-center gap-2 bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                <button type="button" @click="qcStatusFilter = 'ALL'"
                  :class="qcStatusFilter === 'ALL' ? 'bg-white text-slate-900 font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-layer-group text-xs text-slate-500"></i>
                  <span>Semua Card</span>
                  <span
                    class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold">{{
                    qcCounts.all }}</span>
                </button>
                <button type="button" @click="qcStatusFilter = 'PENDING'"
                  :class="qcStatusFilter === 'PENDING' ? 'bg-amber-500 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-clock text-xs"></i>
                  <span>Menunggu QC</span>
                  <span
                    :class="qcStatusFilter === 'PENDING' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-800'"
                    class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{ qcCounts.pending
                    }}</span>
                </button>
                <button type="button" @click="qcStatusFilter = 'GOOD'"
                  :class="qcStatusFilter === 'GOOD' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-circle-check text-xs"></i>
                  <span>Lolos QC (Good)</span>
                  <span
                    :class="qcStatusFilter === 'GOOD' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800'"
                    class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{ qcCounts.good }}</span>
                </button>
                <button type="button" @click="qcStatusFilter = 'HOLD'"
                  :class="qcStatusFilter === 'HOLD' ? 'bg-rose-600 text-white font-black shadow-sm' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                  <span>Hold QC</span>
                  <span :class="qcStatusFilter === 'HOLD' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800'"
                    class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold">{{ qcCounts.hold }}</span>
                </button>
              </div>

              <!-- Search in Tray -->
              <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                <input type="text" v-model="qcSearchQuery"
                  placeholder="Cari SN P9 Part 15 / Part 12 / Part 1 di dokumen tray ini..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600 min-h-[38px]">
              </div>
            </div>

            <!-- QC Cards Grid Layout Per Set with Assembly Test Table -->
            <template v-if="filteredQCList.length > 0">
              <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
                <div v-for="item in pagedQCList" :key="item.assembly_id || item.id"
                  :class="getQCCardClass(item)"
                  class="border-2 rounded-2xl p-4 sm:p-5 flex flex-col justify-between space-y-4 shadow-sm hover:shadow-md transition duration-200">

                  <!-- Card Top Header -->
                  <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-3">
                    <div class="min-w-0">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-black font-mono px-2.5 py-0.5 rounded-lg bg-slate-900 text-white">
                          {{ item.tray_code }}
                        </span>
                        <span
                          class="text-xs font-black text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg">
                          Set #{{ item.set_number }}
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 font-medium mt-1 truncate">
                        <i class="fa-solid fa-user-gear text-[10px] mr-1"></i>
                        <span>Assy: {{ getOperatorFullName(item.pic_assembly) || '-' }}</span>
                      </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="flex-shrink-0">
                      <span
                        v-if="(typeof isReassemblySet === 'function' && isReassemblySet(item)) || item.qc_status === 'RU' || item.status === 'Dirakit Ulang'"
                        class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-300 flex items-center gap-1">
                        <i class="fa-solid fa-arrows-rotate text-xs"></i> RU
                      </span>
                      <span
                        v-else-if="isQCHoldItem(item) || item.qc_status === 'HOLD_QC' || item.qc_status === 'HOLD' || item.qc_status === 'REWORK_QC'"
                        class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300 flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation text-xs"></i> Hold QC
                      </span>
                      <span v-else-if="item.qc_status === 'GOOD' || item.qc_status === 'GOOD_QC'"
                        class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-xs"></i> Lolos QC
                      </span>
                      <span v-else
                        class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1 animate-pulse">
                        <i class="fa-solid fa-clock text-xs"></i> Menunggu QC
                      </span>
                    </div>
                  </div>

                  <!-- Component Serial Numbers (3 Parts Set) -->
                  <div class="space-y-2 font-mono text-xs">
                    <!-- Part 15 SN (Prominent) -->
                    <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                      <div class="text-[9px] font-sans font-bold text-slate-500 uppercase tracking-wider">SN P9 Part 15:
                      </div>
                      <div
                        class="text-sm sm:text-base font-black text-slate-900 tracking-wide font-mono mt-0.5 truncate">
                        {{ item.part15_sn || '-' }}
                      </div>
                    </div>

                    <!-- Part 1 & Part 12 PN Grid -->
                    <div class="grid grid-cols-2 gap-2">
                      <div class="bg-slate-50 p-2 rounded-xl border border-slate-200">
                        <div class="text-[9px] font-sans font-bold text-slate-500 uppercase tracking-wider">PN Part 1:
                        </div>
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

                  <!-- Traceability Documents & Test Metadata -->
                  <div
                    class="bg-slate-50/80 rounded-xl p-2.5 border border-slate-200 text-[11px] space-y-1.5 font-sans">
                    <div class="flex items-center justify-between">
                      <span class="text-slate-500 font-medium">No. Doc Assy:</span>
                      <span
                        class="font-bold font-mono text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded">
                        {{ item.assy_doc_number || '-' }}
                      </span>
                    </div>
                    <div v-if="item.qc_doc_number" class="flex items-center justify-between">
                      <span class="text-slate-500 font-medium">No. Doc WH/QC:</span>
                      <span
                        class="font-bold font-mono text-emerald-800 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                        {{ item.qc_doc_number }}
                      </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                      <span>Tester Assy:</span>
                      <span class="font-bold text-slate-800">{{ getOperatorFullName(item.assy_tester_username) ||
                        item.assy_tester_username || item.pic_assembly || '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500 text-[10.5px]">
                      <span>Waktu Selesai:</span>
                      <span class="font-bold text-slate-800">{{ formatDateTime(item.finished_at || item.assy_tested_at)
                        }}</span>
                    </div>
                    <div v-if="item.qc_tested_by"
                      class="flex items-center justify-between text-slate-500 text-[10.5px] border-t border-slate-200/60 pt-1">
                      <span>Inspector QC:</span>
                      <span class="font-bold text-emerald-800">{{ getOperatorFullName(item.qc_tested_by) ||
                        item.qc_tested_by }}</span>
                    </div>
                    <div v-if="item.qc_failure_reasons || item.rework_reason"
                      class="rounded-lg p-2 text-[10.5px] border mt-1.5 space-y-1"
                      :class="(item.qc_status === 'REWORK_QC' || item.rework_reason) ? 'bg-purple-50 border-purple-200 text-purple-900' : 'bg-red-50 border-red-200 text-red-800'">
                      <div class="text-[9px] uppercase font-bold flex items-center justify-between"
                        :class="(item.qc_status === 'REWORK_QC' || item.rework_reason) ? 'text-purple-700' : 'text-red-700'">
                        <span class="flex items-center gap-1">
                          <i
                            :class="(item.qc_status === 'REWORK_QC' || item.rework_reason) ? 'fa-solid fa-rotate-left' : 'fa-solid fa-circle-exclamation'"></i>
                          {{ (item.qc_status === 'REWORK_QC' || item.rework_reason) ? 'Alasan Kendala / Rework:' :
                          'Alasan Hold QC:' }}
                        </span>
                        <span v-if="item.rework_reason"
                          class="text-[8px] bg-purple-200/80 text-purple-900 font-mono font-bold px-1 rounded">REWORK</span>
                      </div>
                      <div class="font-medium italic leading-tight">
                        {{ formatQCReworkReason(item) }}
                      </div>
                    </div>
                  </div>

                  <!-- QC Action Buttons -->
                  <div class="mt-auto pt-2 border-t border-slate-100 flex items-center gap-2">
                    <!-- If Pending QC: Button Pilih / Proses QC (atau Detail Rakitan untuk Gudang & Assembly) -->
                    <button v-if="isQCPendingItem(item)"
                      @click="openQCTestModal(item, userRole === 'assembly' || userRole === 'gudang')"
                      :class="(userRole === 'assembly' || userRole === 'gudang') ? 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20'"
                      class="w-full font-bold py-2.5 px-3 rounded-xl text-xs shadow-sm flex items-center justify-center space-x-1.5 transition active:scale-98 cursor-pointer min-h-[38px]">
                      <i
                        :class="(userRole === 'assembly' || userRole === 'gudang') ? 'fa-solid fa-circle-info text-slate-500' : 'fa-solid fa-list-check'"></i>
                      <span>{{ (userRole === 'assembly' || userRole === 'gudang') ? 'Detail Rakitan (Menunggu QC)' :
                        'Pilih / Proses QC' }}</span>
                    </button>

                    <!-- If Already Processed Good: View Detail + Print Document -->
                    <template v-else-if="item.qc_status === 'GOOD' || item.qc_status === 'GOOD_QC'">
                      <button @click="openQCTestModal(item, true)"
                        class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold py-2.5 px-3 rounded-xl text-xs border border-slate-300 flex items-center justify-center space-x-1.5 transition cursor-pointer min-h-[38px]">
                        <i class="fa-solid fa-circle-info text-slate-500"></i>
                        <span>Detail Rakitan</span>
                      </button>
                    </template>

                    <!-- If Hold QC: View Detail & Reasons -->
                    <template v-else>
                      <button @click="openQCTestModal(item, true)"
                        class="w-full bg-rose-50 hover:bg-rose-100 text-rose-800 font-bold py-2.5 px-3 rounded-xl text-xs border border-rose-200 flex items-center justify-center space-x-1.5 transition cursor-pointer min-h-[38px]">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                        <span>Detail Hold</span>
                      </button>
                    </template>
                  </div>

                </div>
              </div>

              <!-- Pagination: qcList -->
              <div
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print mt-1">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.qcList.page }}</strong> / {{ totalPgQCList }} <span class="text-slate-400 ml-1">({{
                    (filteredQCList||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.qcList.page = 1" :disabled="pgState.qcList.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.qcList.page--" :disabled="pgState.qcList.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.qcList.page }}</span>
                  <button type="button" @click="pgState.qcList.page++" :disabled="pgState.qcList.page >= totalPgQCList"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.qcList.page = totalPgQCList"
                    :disabled="pgState.qcList.page >= totalPgQCList"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </template>

            <!-- Empty State -->
            <div v-else
              class="p-12 text-center text-slate-400 font-sans border border-slate-200 rounded-2xl bg-slate-50/50 space-y-3">
              <div
                class="w-14 h-14 mx-auto rounded-2xl bg-slate-200/70 text-slate-400 flex items-center justify-center text-2xl">
                <i class="fa-solid fa-clipboard-check"></i>
              </div>
              <div>
                <h4 class="font-bold text-slate-700 text-sm">Tidak ada kartu rakitan</h4>
                <p class="text-xs text-slate-500 mt-0.5">Tidak ditemukan data barang rakitan pada filter status atau
                  pencarian di dokumen tray ini.</p>
              </div>
            </div>
          </div>

          <!-- ================= TAB QC: VIEW 3 - HALAMAN PENUH PEMERIKSAAN MUTU QC (BUKA PAGE BARU) ================= -->
          <div v-else class="space-y-4 sm:space-y-6">

            <!-- Navigation Header -->
            <div
              class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
              <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="showQCTestModal = false"
                  class="bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-3.5 rounded-xl text-xs border border-slate-300 flex items-center gap-2 shadow-xs active:scale-95 transition cursor-pointer">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>Kembali</span>
                </button>
                <div class="flex items-center gap-2">
                  <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                    Verifikasi QC Set #{{ qcTestForm.set_number }}
                  </h3>
                </div>
              </div>

              <div class="flex items-center gap-2 self-end sm:self-center">
                <span v-if="qcTestForm.assy_doc_number"
                  class="text-indigo-900 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-xl font-mono font-bold text-xs">
                  Assy Doc: {{ qcTestForm.assy_doc_number }}
                </span>
                <span v-if="qcTestForm.qc_doc_number"
                  class="text-emerald-900 bg-emerald-50 border border-emerald-300 px-3 py-1 rounded-xl font-mono font-bold text-xs">
                  WH/QC: {{ qcTestForm.qc_doc_number }}
                </span>
              </div>
            </div>

            <!-- Banner Mode Read Only untuk Operator Assembly / Gudang -->
            <div v-if="userRole === 'assembly' || userRole === 'gudang'"
              class="bg-blue-50 border border-blue-200 text-blue-900 rounded-2xl p-3.5 px-4 text-xs flex items-center justify-between shadow-2xs">
              <div class="flex items-center gap-2.5 font-semibold">
                <i class="fa-solid fa-circle-info text-blue-600 text-sm"></i>
                <span>Mode Pemantauan Mutu (Read Only) &bull; Hak akses Anda hanya untuk melihat data rakitan dan hasil
                  pengujian QC.</span>
              </div>
              <span class="text-[10px] bg-blue-200/80 text-blue-900 font-bold px-2 py-0.5 rounded font-mono uppercase">
                {{ userRole === 'assembly' ? 'ASSEMBLY' : 'GUDANG' }}
              </span>
            </div>

            <!-- Identitas Part & Traceability Unit -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
              <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5 text-xs">
                <div class="flex items-center gap-2 font-mono">
                  <span class="text-slate-400 font-sans font-bold uppercase text-[10px]">Tray &amp; Set:</span>
                  <span class="font-black text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300">{{
                    qcTestForm.tray_code }}</span>
                  <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">Set
                    #{{ qcTestForm.set_number }}</span>
                </div>
                <div class="flex items-center gap-3 text-slate-600 flex-wrap">
                  <span>Operator: <strong class="text-slate-900">{{ getOperatorFullName(qcTestForm.pic_assembly) ||
                      qcTestForm.pic_assembly || '-' }}</strong></span>
                  <span>Tester: <strong class="text-slate-900">{{ getOperatorFullName(qcTestForm.assy_tester_username)
                      || qcTestForm.assy_tester_username || qcTestForm.pic_assembly || '-' }}</strong></span>
                  <span>Waktu Selesai: <strong class="text-slate-900">{{ formatDateTime(qcTestForm.assy_tested_at ||
                      qcTestForm.finished_at) }}</strong></span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs font-mono">
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                  <span class="text-[9.5px] font-sans font-bold text-slate-400 block uppercase">SN P9 Part 15
                    (Frame):</span>
                  <strong class="text-emerald-950 font-black text-sm block mt-0.5">{{ qcTestForm.part15_sn || '-'
                    }}</strong>
                </div>
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                  <span class="text-[9.5px] font-sans font-bold text-slate-400 block uppercase">PN P9 Part 1
                    (Upper):</span>
                  <strong class="text-slate-800 font-bold text-xs sm:text-sm block mt-0.5">{{ qcTestForm.part1_sn || '-'
                    }}</strong>
                </div>
                <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                  <span class="text-[9.5px] font-sans font-bold text-slate-400 block uppercase">PN P9 Part 12
                    (Lower):</span>
                  <strong class="text-slate-800 font-bold text-xs sm:text-sm block mt-0.5">{{ qcTestForm.part12_sn ||
                    '-' }}</strong>
                </div>
              </div>

              <!-- Riwayat Pergantian Part / Perubahan Status -->
              <div v-if="qcTestForm.rework_reason || (qcTestForm.reason && qcTestForm.reason.includes('Ganti Part'))"
                class="bg-blue-50 border border-blue-200 p-3 rounded-xl space-y-2 text-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-blue-900 flex items-center gap-1.5">
                  <i class="fa-solid fa-wrench text-blue-600"></i>
                  <span>Riwayat Pergantian Part:</span>
                </div>
                <div v-if="getParsedReplacementHistory(qcTestForm.rework_reason || qcTestForm.reason).length > 0"
                  class="flex flex-wrap gap-2">
                  <div v-for="r in getParsedReplacementHistory(qcTestForm.rework_reason || qcTestForm.reason)"
                    :key="r.partType"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-blue-200 rounded-lg text-xs shadow-2xs">
                    <span class="font-bold text-blue-800 text-[10.5px] uppercase">Part {{ r.partType }}:</span>
                    <span class="font-mono line-through text-rose-500 font-bold">{{ r.oldSN }}</span>
                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-0.5"></i>
                    <span class="font-mono text-emerald-600 font-black">{{ r.newSN }}</span>
                  </div>
                </div>
                <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                  {{ qcTestForm.rework_reason || qcTestForm.reason }}
                </div>
              </div>
            </div>

            <!-- Tabel Hasil 15 Uji Fungsional Tester Assembly: SATU TABEL RESMI -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
              <!-- Judul Tabel QC & Toggle Accordion -->
              <div @click="isQCAssyTableExpanded = !isQCAssyTableExpanded"
                class="flex items-center justify-between cursor-pointer select-none py-1 group">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-table-list text-emerald-600"></i>
                  <h4 class="text-xs font-black uppercase tracking-wider text-slate-800">
                    Hasil Uji Fungsional Assembly
                  </h4>
                </div>
                <div class="flex items-center gap-3">
                  <span class="text-xs text-slate-500 font-medium">
                    Tester: <strong class="text-slate-800">{{ getOperatorFullName(qcTestForm.assy_tester_username) ||
                      qcTestForm.assy_tester_username || qcTestForm.pic_assembly || '-' }}</strong>
                  </span>
                  <div
                    class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200"
                      :class="{ 'rotate-180': isQCAssyTableExpanded }"></i>
                  </div>
                </div>
              </div>

              <!-- SATU TABEL TUNGGAL HASIL PENGUJIAN QC (COLLAPSIBLE) -->
              <div v-show="isQCAssyTableExpanded"
                class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs transition-all">
                <table class="w-full text-left text-xs border-collapse">
                  <thead
                    class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                    <tr>
                      <th class="py-2.5 px-3 text-center w-12 bg-slate-100 border-r border-slate-200">No</th>
                      <th class="py-2.5 px-4 bg-slate-100 border-r border-slate-200">Deskripsi</th>
                      <th class="py-2.5 px-3 text-center w-36 bg-slate-100">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-sans">
                    <tr v-for="t in (qcTestForm.assy_test_checklist || [])" :key="t.test_num"
                      class="hover:bg-slate-50 transition"
                      :class="t.status === 'GOOD' ? 'bg-white' : (t.status === 'NOT_GOOD' ? 'bg-red-50/25' : 'bg-slate-50/25')">
                      <td
                        class="py-2.5 px-3 text-center font-mono font-bold text-slate-800 text-xs border-r border-slate-200 bg-slate-50/40">
                        {{ t.test_num }}
                      </td>
                      <td class="py-2.5 px-4 border-r border-slate-200">
                        <div class="font-bold text-slate-800 text-xs leading-snug whitespace-pre-line">{{ t.name }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5 leading-relaxed whitespace-pre-line">{{
                          t.description }}</div>
                      </td>
                      <td class="py-2.5 px-3 text-center">
                        <span v-if="t.status === 'GOOD'"
                          class="px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-2xs">
                          <i class="fa-solid fa-check text-[9px]"></i> Good
                        </span>
                        <span v-else-if="t.status === 'NOT_GOOD'"
                          class="px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-red-100 text-red-800 border border-red-300 inline-flex items-center gap-1 shadow-2xs">
                          <i class="fa-solid fa-xmark text-[9px]"></i> Not Good
                        </span>
                        <span v-else class="text-slate-400 text-xs font-black">
                          -
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Catatan / Alasan Kendala & Rework (Tampil baik di Mode Uji maupun View) -->
              <div v-if="qcTestForm.notes || qcTestForm.qc_failure_reasons || qcTestForm.rework_reason"
                class="p-3 rounded-xl text-xs space-y-1 border"
                :class="(qcTestForm.qc_status === 'REWORK_QC' || qcTestForm.rework_reason) ? 'bg-purple-50 border-purple-200 text-purple-950' : 'bg-slate-50 border-slate-200 text-slate-800'">
                <div class="text-[9.5px] font-bold uppercase flex items-center justify-between"
                  :class="(qcTestForm.qc_status === 'REWORK_QC' || qcTestForm.rework_reason) ? 'text-purple-800' : 'text-slate-600'">
                  <span class="flex items-center gap-1">
                    <i
                      :class="(qcTestForm.qc_status === 'REWORK_QC' || qcTestForm.rework_reason) ? 'fa-solid fa-rotate-left' : 'fa-solid fa-clipboard-list'"></i>
                    {{ (qcTestForm.qc_status === 'REWORK_QC' || qcTestForm.rework_reason) ? 'Alasan Kendala / Rework:' :
                    'Catatan / Alasan Penahanan:' }}
                  </span>
                  <span v-if="qcTestForm.rework_reason"
                    class="text-[8.5px] bg-purple-100 text-purple-800 border border-purple-200 font-mono font-bold px-1.5 py-0.2 rounded">RIWAYAT
                    REWORK</span>
                </div>
                <div class="font-semibold italic leading-relaxed">{{ formatQCReworkReason(qcTestForm) }}</div>
              </div>
            </div>

            <!-- Panel Keputusan Mutu QC -->
            <!-- 1. Mode Pending QC Decision -->
            <div v-if="!qcTestForm.is_view_mode"
              class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
              <h4 class="text-xs font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-stamp text-emerald-600"></i>
                <span>Keputusan mutu QC:</span>
              </h4>

              <!-- Dua Tombol Keputusan: Good & Not Good (Hold) -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <button type="button" @click="submitQCTestDecision('Good')" :disabled="isSubmittingQCTest"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 px-6 rounded-2xl text-sm shadow-md shadow-emerald-600/25 flex items-center justify-center space-x-2 transition active:scale-95 cursor-pointer min-h-[48px]">
                  <i v-if="isSubmittingQCTest" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-circle-check text-lg"></i>
                  <span>Good (Lolos)</span>
                </button>

                <button type="button" @click="openQCHoldReasonModal" :disabled="isSubmittingQCTest"
                  class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-3.5 px-6 rounded-2xl text-sm shadow-md shadow-rose-600/25 flex items-center justify-center space-x-2 transition active:scale-95 cursor-pointer min-h-[48px]">
                  <i v-if="isSubmittingQCTest" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-hand text-lg"></i>
                  <span>Not Good (Hold)</span>
                </button>
              </div>

              <div class="pt-2 flex justify-end">
                <button type="button" @click="showQCTestModal = false"
                  class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px] cursor-pointer">
                  Batal
                </button>
              </div>
            </div>

            <!-- 2. Mode Selesai / View Mode -->
            <div v-else
              class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
              <div class="text-xs font-bold">
                <span v-if="qcTestForm.qc_status === 'GOOD'"
                  class="text-emerald-700 font-bold flex items-center gap-1.5">
                  <i class="fa-solid fa-circle-check text-base"></i>
                  <span>Lolos QC | Dokumen WH/QC: {{ qcTestForm.qc_doc_number || '-' }}</span>
                </span>
                <span v-else-if="qcTestForm.qc_status === 'PENDING_QC' || qcTestForm.qc_status === 'PENDING'"
                  class="text-amber-700 font-bold flex items-center gap-1.5">
                  <i class="fa-solid fa-clock text-base"></i>
                  <span>Menunggu pemeriksaan QC</span>
                </span>
                <span v-else class="text-rose-700 font-bold flex items-center gap-1.5">
                  <i class="fa-solid fa-triangle-exclamation text-base"></i>
                  <span>Status: Hold QC | {{ qcTestForm.qc_failure_reasons || 'Tertahan mutu QC' }}</span>
                </span>
              </div>

              <div class="flex items-center gap-2 self-end sm:self-center">
                <button type="button" @click="showQCTestModal = false"
                  class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px] cursor-pointer">
                  Kembali
                </button>
              </div>
            </div>

          </div>

        </div>
      </section>

@endverbatim
