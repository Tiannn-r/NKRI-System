@verbatim

      <!-- ================= TAB 6: USER MANAGEMENT & VERIFICATION & AUDIT TRAIL ================= -->
      <section v-if="currentTab === 'users' && hasTabAccess('users')" class="space-y-4 sm:space-y-6">

        <!-- VIEW: VERIFIKASI SATU SET (APPROVAL UNIT RAKITAN)                   -->
        <!-- =================================================================== -->
        <div v-if="adminSubTab === 'verify_set' || adminSubTab === 'verify_items'" class="space-y-4 sm:space-y-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <div class="flex items-center gap-2">
                  <span
                    class="w-7 h-7 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-boxes-stacked"></i>
                  </span>
                  <h3 class="text-base sm:text-lg font-black text-slate-900">Verifikasi Hasil Rakitan dan Test</h3>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                  Persetujuan unit rakitan bermasalah dari Perakitan, Test Functional 1, dan QC.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <!-- View Mode Toggle: Table (Default) vs Cards (Opsi) -->
                <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                  <button type="button" @click="adminSetViewMode = 'table'"
                    :class="adminSetViewMode === 'table' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-table-list text-xs"></i>
                    <span>Tabel (Default)</span>
                  </button>
                  <button type="button" @click="adminSetViewMode = 'cards'"
                    :class="adminSetViewMode === 'cards' ? 'bg-white text-slate-900 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-grip text-xs"></i>
                    <span>Card (Opsi)</span>
                  </button>
                </div>
                <button @click="loadPendingVerifications"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 cursor-pointer">
                  <i class="fa-solid fa-rotate"></i>
                  <span>Segarkan Antrean</span>
                </button>
              </div>
            </div>

            <!-- Filter Bar Verifikasi Satu Set -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 sm:p-4 space-y-3">
              <!-- Baris 1: Filter Tab Tahap Proses & Khusus Declined -->
              <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
                <div class="flex items-center gap-2 flex-wrap">
                  <!-- Tab Semua Proses -->
                  <button type="button" @click="adminSetProcessFilter = 'ALL'"
                    :class="adminSetProcessFilter === 'ALL' ? 'bg-slate-900 text-white font-bold shadow-md ring-2 ring-slate-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer font-sans min-h-[38px]">
                    <i class="fa-solid fa-layer-group text-xs"></i>
                    <span>Semua Proses</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-black"
                      :class="adminSetProcessFilter === 'ALL' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800 border border-slate-200'">
                      {{ adminSetProcessCounts.ALL }}
                    </span>
                  </button>



                  <!-- Tab 1. Perakitan -->
                  <button type="button" @click="adminSetProcessFilter = 'perakitan'"
                    :class="adminSetProcessFilter === 'perakitan' ? 'bg-slate-700 text-white font-bold shadow-md ring-2 ring-slate-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer font-sans min-h-[38px]">
                    <i class="fa-solid fa-tools text-xs"></i>
                    <span>1. Perakitan</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-black"
                      :class="adminSetProcessFilter === 'perakitan' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-800 border border-slate-200'">
                      {{ adminSetProcessCounts.perakitan }}
                    </span>
                  </button>

                  <!-- Tab 2. Test Functional 1 -->
                  <button type="button" @click="adminSetProcessFilter = 'test_functional_1'"
                    :class="adminSetProcessFilter === 'test_functional_1' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-600/20 ring-2 ring-blue-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer font-sans min-h-[38px]">
                    <i class="fa-solid fa-microchip text-xs"></i>
                    <span>2. Test Functional 1</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-black"
                      :class="adminSetProcessFilter === 'test_functional_1' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-900 border border-blue-200'">
                      {{ adminSetProcessCounts.test_functional_1 }}
                    </span>
                  </button>

                  <!-- Tab 3. Test Functional 2 (QC) -->
                  <button type="button" @click="adminSetProcessFilter = 'qc'"
                    :class="adminSetProcessFilter === 'qc' ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/20 ring-2 ring-purple-400' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer font-sans min-h-[38px]">
                    <i class="fa-solid fa-award text-xs"></i>
                    <span>3. Test Functional 2 (QC)</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-black"
                      :class="adminSetProcessFilter === 'qc' ? 'bg-white/20 text-white' : 'bg-purple-100 text-purple-900 border border-purple-200'">
                      {{ adminSetProcessCounts.qc }}
                    </span>
                  </button>

                  <!-- Tab Declined (khusus) - visual separator -->
                  <div class="w-px h-6 bg-slate-200 self-center mx-1"></div>
                  <button type="button" @click="adminSetProcessFilter = 'declined'"
                    :class="adminSetProcessFilter === 'declined' ? 'bg-rose-600 text-white font-bold shadow-md shadow-rose-600/20 ring-2 ring-rose-400' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'"
                    class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 cursor-pointer font-sans min-h-[38px]">
                    <i class="fa-solid fa-ban text-xs"></i>
                    <span>Declined</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-mono font-black"
                      :class="adminSetProcessFilter === 'declined' ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-900 border border-rose-200'">
                      {{ adminSetProcessCounts.declined || 0 }}
                    </span>
                  </button>
                </div>
              </div>

              <!-- Baris 2: Filtering /Part, /Status, /Cari SN, dan /Tanggal -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 items-end">
                <!-- Filter /Part -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-microchip text-slate-400 mr-1"></i>Tipe part:
                  </label>
                  <select v-model="adminSetPartTypeFilter"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                    <option value="">Semua part (15, 1, 12)</option>
                    <option value="15">P9 Part 15 (Identitas P9)</option>
                    <option value="1">P9 Part 1</option>
                    <option value="12">P9 Part 12</option>
                  </select>
                </div>

                <!-- Filter Status Part -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-tag text-slate-400 mr-1"></i>Status part:
                  </label>
                  <select v-model="adminSetStatusFilter"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                    <option value="">Semua status</option>
                    <option value="Hold">Hold</option>
                    <option value="Scrap">Scrap</option>
                    <option value="Declined">Declined</option>
                  </select>
                </div>

                <!-- Cari SN / Petugas -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 mr-1"></i>Cari SN / petugas:
                  </label>
                  <div class="relative">
                    <input type="text" v-model="adminSetSearchQuery" placeholder="Ketik SN part / username / tray..."
                      class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-2.5 py-1.5 text-xs font-mono font-medium text-slate-900 focus:outline-none focus:border-red-600 min-h-[36px]">
                    <i class="fa-solid fa-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                  </div>
                </div>

                <!-- Filter /Tanggal -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>Rentang tanggal:
                  </label>
                  <div
                    class="flex items-center gap-1 bg-white border border-slate-300 rounded-xl px-2 py-1 min-h-[36px]">
                    <input type="date" v-model="adminSetStartDate" title="Tanggal Mulai"
                      class="text-xs font-semibold text-slate-900 focus:outline-none bg-transparent w-full">
                    <span class="text-slate-400 text-xs">-</span>
                    <input type="date" v-model="adminSetEndDate" title="Tanggal Selesai"
                      class="text-xs font-semibold text-slate-900 focus:outline-none bg-transparent w-full">
                  </div>
                </div>

                <!-- Tombol Reset -->
                <div>
                  <button type="button" @click="resetAdminSetFilter"
                    class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold px-3 py-1.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer min-h-[36px]">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                    <span>Reset Filter</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Tampilan Data Verifikasi Admin: Tabel (Default) atau Card (Opsi) -->
            <div v-if="filteredAdminSetsOnly.length > 0" class="space-y-3">
              <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
                  <i class="fa-solid fa-boxes-stacked"
                    :class="adminSetProcessFilter === 'declined' ? 'text-rose-600' : 'text-slate-700'"></i>
                  <span>Barang Rakitan ({{ filteredAdminSetsOnly.length }}) — {{ adminSetViewMode === 'table' ?
                    'Tampilan Tabel (Default)' : 'Tampilan Card (Opsi)' }}</span>
                </h4>
              </div>

              <!-- VIEW MODE 1: TABEL (DEFAULT, LEBIH USER FRIENDLY) -->
              <div v-if="adminSetViewMode === 'table'"
                class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left border-collapse text-xs">
                    <thead>
                      <tr
                        class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-3 text-center w-12">No</th>
                        <th class="py-3 px-3">No. Dokumen / Tray</th>
                        <th class="py-3 px-2 text-center w-14">Set #</th>
                        <th class="py-3 px-3 whitespace-nowrap">SN Part 15 (P9)</th>
                        <th class="py-3 px-3 font-mono whitespace-nowrap">PN Part 1</th>
                        <th class="py-3 px-3 font-mono whitespace-nowrap">PN Part 12</th>
                        <th class="py-3 px-3 whitespace-nowrap">Tahap Proses</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Catatan / Alasan</th>
                        <th class="py-3 px-3">Petugas</th>
                        <th class="py-3 px-3 text-center w-36">Aksi Persetujuan</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                      <tr v-for="(set, idx) in pagedAdminSets"
                        :key="set.request_id ? ('req-' + set.request_id) : ('assy-' + (set.assembly_id || set.id))"
                        class="hover:bg-blue-50/40 transition-colors"
                        :class="isSetFullyVerified(set) ? 'bg-emerald-50/20' : (set.is_declined ? 'bg-rose-50/20' : '')">
                        <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">{{ idx + 1 +
                          (pgState.adminSets.page - 1) * pgState.adminSets.size }}</td>
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                          <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded-md border border-slate-200">{{
                            set.tray_code || ('Tray #' + set.tray_id) }}</span>
                        </td>
                        <td class="py-2.5 px-2 text-center font-mono font-black text-slate-800 whitespace-nowrap">
                          #{{ set.set_number }}
                        </td>
                        <td class="py-2.5 px-3 font-mono font-black text-slate-900 whitespace-nowrap">
                          {{ set.part15_sn || '-' }}
                        </td>
                        <td class="py-2.5 px-3 font-mono text-slate-700 whitespace-nowrap">
                          {{ set.part1_sn || '-' }}
                        </td>
                        <td class="py-2.5 px-3 font-mono text-slate-700 whitespace-nowrap">
                          {{ set.part12_sn || '-' }}
                        </td>
                        <td class="py-2.5 px-3 whitespace-nowrap">
                          <span v-if="set.is_declined"
                            class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wide bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1.5 shadow-2xs whitespace-nowrap">
                            <i class="fa-solid fa-ban text-[9px] text-rose-500"></i> Declined
                          </span>
                          <span v-else-if="set.process_stage === 'qc'"
                            class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wide bg-purple-50 text-purple-700 border border-purple-200 inline-flex items-center gap-1.5 shadow-2xs whitespace-nowrap">
                            <i class="fa-solid fa-award text-[10px] text-purple-600"></i> QC (TF 2)
                          </span>
                          <span v-else-if="set.process_stage === 'test_functional_1'"
                            class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wide bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center gap-1.5 shadow-2xs whitespace-nowrap">
                            <i class="fa-solid fa-microchip text-[10px] text-blue-600"></i> TF 1
                          </span>
                          <span v-else
                            class="px-2.5 py-1 rounded-lg text-[10.5px] font-black uppercase tracking-wide bg-slate-100 text-slate-700 border border-slate-200 inline-flex items-center gap-1.5 shadow-2xs whitespace-nowrap">
                            <i class="fa-solid fa-screwdriver-wrench text-[9px] text-slate-500"></i> Perakitan
                          </span>
                        </td>
                        <td class="py-2.5 px-3">
                          <!-- Jika masih tahap Perakitan, tampilkan status yang dialaminya (Hold, Rework, Scrap, dll) tanpa QC PENDING -->
                          <div v-if="set.process_stage === 'perakitan'">
                            <span
                              v-if="set.assembly_status === 'Hold' || set.status === 'Hold' || set.qc_status === 'HOLD_ASSY'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                            </span>
                            <span v-else-if="set.assembly_status === 'Rework' || set.status === 'Rework'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                            </span>
                            <span
                              v-else-if="set.assembly_status === 'Scrap' || set.status === 'Scrap' || (set.display_status && set.display_status.toLowerCase().includes('scrap'))"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-ban text-[8.5px]"></i> SCRAP
                            </span>
                            <span v-else-if="set.is_declined || set.status === 'Declined'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-ban text-[8.5px]"></i> DECLINED
                            </span>
                            <span v-else
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-hand text-[8.5px]"></i> {{ (set.assembly_status || set.status ||
                              'HOLD').toUpperCase() }}
                            </span>
                          </div>
                          <!-- Jika tahap Test Functional 1 -->
                          <div v-else-if="set.process_stage === 'test_functional_1'">
                            <span v-if="set.assy_test_status === 'Rework' || set.assembly_status === 'Rework'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                            </span>
                            <span v-else
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                            </span>
                          </div>
                          <!-- Jika tahap QC -->
                          <div v-else-if="set.process_stage === 'qc'">
                            <span v-if="set.qc_status === 'REWORK_QC' || set.qc_status === 'REWORK'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                            </span>
                            <span v-else-if="set.qc_status === 'GOOD' || set.qc_status === 'GOOD_QC'"
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-check text-[8.5px]"></i> GOOD
                            </span>
                            <span v-else
                              class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                              <i class="fa-solid fa-triangle-exclamation text-[8.5px]"></i> HOLD
                            </span>
                          </div>
                          <!-- Fallback jika proses gabungan -->
                          <div v-else class="space-y-1 text-[10.5px]">
                            <div class="flex items-center gap-1">
                              <span class="text-slate-400 text-[10px]">Assy:</span>
                              <span
                                v-if="!(['qc', 'QC'].includes(set.process_stage) || set.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(set.qc_status) || set.qc_tested_at || set.qc_doc_number) && (set.assy_test_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.assembly_status === 'Hold' || set.status === 'Hold')"
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300">HOLD</span>
                              <span
                                v-else-if="!(['qc', 'QC'].includes(set.process_stage) || set.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(set.qc_status) || set.qc_tested_at || set.qc_doc_number) && ((set.assembly_status === 'Rework' || set.status === 'Rework' || set.assy_test_status === 'Rework') && set.qc_status !== 'REWORK_QC')"
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300">REWORK</span>
                              <span v-else
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300">GOOD</span>
                            </div>
                            <div v-if="set.qc_status && set.qc_status !== 'PENDING'" class="flex items-center gap-1">
                              <span class="text-slate-400 text-[10px]">QC:</span>
                              <span v-if="set.qc_status === 'HOLD_QC' || set.qc_status === 'HOLD'"
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300">HOLD</span>
                              <span v-else-if="set.qc_status === 'REWORK_QC' || set.qc_status === 'REWORK'"
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300">REWORK</span>
                              <span v-else-if="set.qc_status === 'GOOD' || set.qc_status === 'GOOD_QC'"
                                class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300">GOOD</span>
                            </div>
                          </div>
                        </td>
                        <td class="py-2.5 px-3 text-slate-600 max-w-xs truncate"
                          :title="set.qc_failure_reasons || set.assy_failure_reasons || set.declined_reason || set.scrap_reason || set.reason">
                          <span
                            v-if="set.qc_failure_reasons || set.assy_failure_reasons || set.declined_reason || set.scrap_reason || set.reason"
                            class="text-amber-800 text-[11px] italic">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1 text-[10px]"></i>
                            {{ set.qc_failure_reasons || set.assy_failure_reasons || set.declined_reason ||
                            set.scrap_reason || set.reason }}
                          </span>
                          <span v-else class="text-slate-400">-</span>
                        </td>
                        <td class="py-2.5 px-3 text-slate-700 text-xs">
                          <div class="font-bold truncate max-w-[120px]">{{ getOperatorFullName(set.pic_qc ||
                            set.pic_assembly || set.pic_warehouse) }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDateTime(set.qc_tested_at ||
                            set.tray_created_at || set.finished_at) }}</div>
                        </td>
                        <td class="py-2.5 px-3 text-center">
                          <div v-if="isSetFullyVerified(set)" class="flex items-center justify-center gap-1.5">
                            <span
                              class="text-[10.5px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 inline-flex items-center gap-1 select-none">
                              <i class="fa-solid fa-lock text-emerald-600 text-[9px]"></i> Selesai (Terkunci)
                            </span>
                            <button type="button" @click.stop="openAdminSetDetailModal(set)"
                              class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold border border-slate-300 flex items-center justify-center shadow-xs transition cursor-pointer"
                              title="Lihat Detail Rakitan">
                              <i class="fa-solid fa-circle-info text-blue-600 text-xs"></i>
                            </button>
                          </div>
                          <div
                            v-else-if="set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || set.process_stage === 'declined' || (set.status || '').toUpperCase() === 'DECLINED' || (set.display_status && set.display_status.toLowerCase().includes('declin'))"
                            class="flex items-center justify-center">
                            <button type="button" @click.stop="openAdminVerifySetModal(set)"
                              class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow-xs transition active:scale-95 flex items-center gap-1.5 cursor-pointer"
                              title="Pilih set declined untuk verifikasi pemulihan part ke Tersedia">
                              <i class="fa-solid fa-check-to-slot text-xs"></i>
                              <span>Pilih</span>
                            </button>
                          </div>
                          <div
                            v-else-if="set.process_stage === 'perakitan' || set.verified_status === 'PENDING_ADMIN_ASSY_REREG'"
                            class="flex items-center justify-center">
                            <button type="button" @click.stop="openAdminSetDetailModal(set)"
                              class="w-8 h-8 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black shadow-xs flex items-center justify-center transition active:scale-95 cursor-pointer"
                              title="Verifikasi & Tunjuk Operator">
                              <i class="fa-solid fa-user-gear text-xs"></i>
                            </button>
                          </div>
                          <div v-else class="flex items-center justify-center gap-1.5">
                            <button type="button"
                              @click.stop="adminApproveHoldItem(set, set.process_stage === 'qc' ? 'APPROVE_GOOD_QC' : 'APPROVE_GOOD')"
                              class="w-8 h-8 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center justify-center shadow-xs transition active:scale-95 cursor-pointer"
                              :title="set.process_stage === 'qc' ? 'Setujui Good (Lanjut Dokumen)' : 'Setujui Good (Lanjut QC)'">
                              <i class="fa-solid fa-check text-xs"></i>
                            </button>
                            <button type="button" @click.stop="openReplaceForHoldItem(set)"
                              class="w-8 h-8 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold flex items-center justify-center shadow-xs transition active:scale-95 cursor-pointer"
                              title="Ganti Part Bermasalah">
                              <i class="fa-solid fa-wrench text-xs"></i>
                            </button>
                            <button type="button" @click.stop="openAdminSetDetailModal(set)"
                              class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold border border-slate-300 flex items-center justify-center shadow-xs transition cursor-pointer"
                              title="Detail Rakitan">
                              <i class="fa-solid fa-circle-info text-blue-600 text-xs"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- VIEW MODE 2: CARD GRID (OPSI) -->
              <div v-else-if="adminSetViewMode === 'cards'"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-4">
                <div v-for="(set, idx) in pagedAdminSets"
                  :key="set.request_id ? ('req-' + set.request_id) : ('assy-' + (set.assembly_id || set.id))"
                  @click="isSetFullyVerified(set) ? openAdminSetDetailModal(set) : ((set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || set.process_stage === 'declined' || (set.status || '').toUpperCase() === 'DECLINED' || (set.display_status && set.display_status.toLowerCase().includes('declin'))) ? openAdminVerifySetModal(set) : openAdminSetDetailModal(set))"
                  class="bg-white border rounded-2xl p-4 shadow-sm transition-all duration-200 flex flex-col justify-between relative overflow-hidden cursor-pointer hover:border-blue-400 hover:shadow-md"
                  :class="isSetFullyVerified(set) ? 'border-emerald-300 bg-emerald-50/20 ring-1 ring-emerald-100 opacity-90' : set.is_declined ? 'border-rose-300 bg-rose-50/20 ring-1 ring-rose-100 hover:border-rose-400' : 'border-slate-200'">

                  <!-- Card Top Header -->
                  <div class="flex items-center justify-between border-b border-slate-100 pb-2.5 mb-3 gap-1.5">
                    <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                      <span
                        class="bg-slate-900 text-white text-[11px] font-black px-2 py-0.5 rounded-lg font-mono shrink-0">
                        #{{ set.set_number }}
                      </span>
                      <span class="text-xs font-black text-slate-800 font-mono truncate" :title="set.tray_code">
                        {{ set.tray_code || ('Tray #' + set.tray_id) }}
                      </span>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                      <span v-if="isSetFullyVerified(set)"
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-lock text-[9px]"></i> Selesai
                      </span>
                      <span
                        v-else-if="set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || set.process_stage === 'declined' || (set.status || '').toUpperCase() === 'DECLINED' || (set.display_status && set.display_status.toLowerCase().includes('declin'))"
                        class="bg-rose-100 text-rose-900 border border-rose-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-ban text-[9px]"></i> Declined
                      </span>
                      <span
                        v-else-if="set.process_stage === 'perakitan' || set.verified_status === 'PENDING_ADMIN_ASSY_REREG'"
                        class="bg-indigo-100 text-indigo-900 border border-indigo-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-screwdriver-wrench text-[9px]"></i> RU ASSY
                      </span>
                      <span v-else-if="set.display_status && set.display_status.toLowerCase().includes('scrap')"
                        class="bg-rose-100 text-rose-900 border border-rose-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-trash-can text-[9px]"></i> Scrap
                      </span>
                      <span v-else-if="set.display_status && set.display_status.toLowerCase().includes('rework')"
                        class="bg-purple-100 text-purple-900 border border-purple-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-rotate-left text-[9px]"></i> {{ set.display_status || 'Rework' }}
                      </span>
                      <span v-else
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap">
                        <i class="fa-solid fa-hand text-[9px]"></i> {{ set.display_status || 'Hold' }}
                      </span>
                    </div>
                  </div>

                  <!-- Identitas Rakitan & 3 Part SN Breakdown -->
                  <div class="space-y-2.5 mb-3">

                    <!-- Identitas P9 Box -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5">
                      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Identitas
                        P9 (Part 15):</span>
                      <div
                        class="font-mono text-sm font-black text-slate-900 truncate flex items-center justify-between">
                        <span>{{ set.part15_sn || '-' }}</span>
                        <span
                          v-if="set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || (set.display_status && set.display_status.toLowerCase().includes('declin'))"
                          class="text-[10px] font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Decline</span>
                        <span v-else-if="getStockStatusBySN('15', set.part15_sn) === 'Tersedia'"
                          class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Tersedia</span>
                        <span
                          v-else-if="set.assembly_status === 'Rework' || set.status === 'Rework' || getStockStatusBySN('15', set.part15_sn) === 'Rework'"
                          class="text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">Rework</span>
                        <span
                          v-else-if="set.assembly_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.qc_status === 'HOLD' || getStockStatusBySN('15', set.part15_sn) === 'Hold'"
                          class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Hold</span>
                        <span v-else
                          class="text-[10px] font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{
                          set.display_status || '-' }}</span>
                      </div>
                    </div>

                    <!-- Part 1 & Part 12 Mini List -->
                    <div class="bg-slate-50/80 border border-slate-100 rounded-xl p-2 text-xs space-y-1 font-mono">
                      <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 font-sans">P9 Part 1:</span>
                        <div class="flex items-center gap-1">
                          <strong class="text-slate-800">{{ set.part1_sn || '-' }}</strong>
                          <i v-if="getStockStatusBySN('1', set.part1_sn) === 'Tersedia'"
                            class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i>
                          <i v-else-if="set.assembly_status === 'Rework' || set.status === 'Rework' || getStockStatusBySN('1', set.part1_sn) === 'Rework'"
                            class="fa-solid fa-rotate-left text-purple-600 text-[10px]"></i>
                          <i v-else-if="set.assembly_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.qc_status === 'HOLD' || getStockStatusBySN('1', set.part1_sn) === 'Hold'"
                            class="fa-solid fa-hand text-amber-600 text-[10px]"></i>
                          <i v-else class="fa-solid fa-circle-info text-slate-400 text-[10px]"></i>
                        </div>
                      </div>
                      <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 font-sans">P9 Part 12:</span>
                        <div class="flex items-center gap-1">
                          <strong class="text-slate-800">{{ set.part12_sn || '-' }}</strong>
                          <i v-if="getStockStatusBySN('12', set.part12_sn) === 'Tersedia'"
                            class="fa-solid fa-circle-check text-emerald-600 text-[10px]"></i>
                          <i v-else-if="set.assembly_status === 'Rework' || set.status === 'Rework' || getStockStatusBySN('12', set.part12_sn) === 'Rework'"
                            class="fa-solid fa-rotate-left text-purple-600 text-[10px]"></i>
                          <i v-else-if="set.assembly_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.qc_status === 'HOLD' || getStockStatusBySN('12', set.part12_sn) === 'Hold'"
                            class="fa-solid fa-hand text-amber-600 text-[10px]"></i>
                          <i v-else class="fa-solid fa-circle-info text-slate-400 text-[10px]"></i>
                        </div>
                      </div>
                    </div>

                    <!-- Status Proses: Perakitan / TF1 / QC -->
                    <div class="bg-slate-50/90 rounded-xl p-2 text-xs space-y-1 font-sans border border-slate-200">
                      <!-- Jika Perakitan -->
                      <div v-if="set.process_stage === 'perakitan'"
                        class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-700">Status Perakitan:</span>
                        <span
                          v-if="set.assembly_status === 'Hold' || set.status === 'Hold' || set.qc_status === 'HOLD_ASSY'"
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                          <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                        </span>
                        <span v-else-if="set.assembly_status === 'Rework' || set.status === 'Rework'"
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 flex items-center gap-1">
                          <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                        </span>
                        <span
                          v-else-if="set.assembly_status === 'Scrap' || set.status === 'Scrap' || (set.display_status && set.display_status.toLowerCase().includes('scrap'))"
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 flex items-center gap-1">
                          <i class="fa-solid fa-ban text-[8.5px]"></i> SCRAP
                        </span>
                        <span v-else
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                          <i class="fa-solid fa-hand text-[8.5px]"></i> {{ (set.assembly_status || set.status ||
                          'HOLD').toUpperCase() }}
                        </span>
                      </div>
                      <!-- Jika TF1 -->
                      <div v-else-if="set.process_stage === 'test_functional_1'"
                        class="flex items-center justify-between text-[11px]">
                        <span class="font-bold text-slate-700">Status TF 1:</span>
                        <span v-if="set.assy_test_status === 'Rework' || set.assembly_status === 'Rework'"
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 flex items-center gap-1">
                          <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                        </span>
                        <span v-else
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                          <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                        </span>
                      </div>
                      <!-- Jika QC / Lainnya -->
                      <template v-else>
                        <div class="flex items-center justify-between text-[11px]">
                          <span class="font-bold text-slate-700">Test Assembly:</span>
                          <span
                            v-if="!(['qc', 'QC'].includes(set.process_stage) || set.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(set.qc_status) || set.qc_tested_at || set.qc_doc_number) && (set.assy_test_status === 'Hold' || set.qc_status === 'HOLD_ASSY' || set.assembly_status === 'Hold' || set.status === 'Hold')"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-hand text-[8.5px]"></i> HOLD
                          </span>
                          <span
                            v-else-if="!(['qc', 'QC'].includes(set.process_stage) || set.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(set.qc_status) || set.qc_tested_at || set.qc_doc_number) && (set.assy_test_status === 'Rework' || ((set.assembly_status === 'Rework' || set.status === 'Rework') && set.qc_status !== 'REWORK_QC'))"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                          </span>
                          <span
                            v-else-if="set.assy_test_status === 'Good' || set.assy_doc_number || ['qc', 'QC'].includes(set.process_stage) || set.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(set.qc_status) || set.qc_tested_at || set.qc_doc_number"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-check text-[8.5px]"></i> GOOD
                          </span>
                          <span v-else
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-clock text-[8.5px]"></i> BELUM DIUJI
                          </span>
                        </div>
                        <div v-if="set.qc_status && set.qc_status !== 'PENDING'"
                          class="flex items-center justify-between text-[11px]">
                          <span class="font-bold text-slate-700">QC:</span>
                          <span v-if="set.qc_status === 'HOLD_QC' || set.qc_status === 'HOLD'"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-triangle-exclamation text-[8.5px]"></i> HOLD
                          </span>
                          <span v-else-if="set.qc_status === 'REWORK_QC' || set.qc_status === 'REWORK'"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-rotate-left text-[8.5px]"></i> REWORK
                          </span>
                          <span v-else-if="set.qc_status === 'GOOD' || set.qc_status === 'GOOD_QC'"
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-0.5">
                            <i class="fa-solid fa-check text-[8.5px]"></i> GOOD
                          </span>
                          <span v-else
                            class="px-1.5 py-0.5 rounded text-[9.5px] font-bold text-slate-500 bg-slate-100 border border-slate-200">
                            Menunggu QC
                          </span>
                        </div>
                      </template>
                    </div>

                    <!-- Petugas & Waktu -->
                    <div class="bg-slate-50/80 rounded-xl p-2 text-[11px] text-slate-600 space-y-1">
                      <div class="flex items-center justify-between">
                        <span class="text-slate-400">Petugas:</span>
                        <strong class="text-slate-800 truncate max-w-[140px]">{{ getOperatorFullName(set.pic_qc ||
                          set.pic_assembly || set.pic_warehouse) }}</strong>
                      </div>
                      <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-400">Waktu:</span>
                        <span class="font-sans text-slate-500">{{ formatDateTime(set.qc_tested_at || set.tray_created_at
                          || set.finished_at) }}</span>
                      </div>
                    </div>

                    <!-- Catatan Alasan -->
                    <div
                      v-if="set.qc_failure_reasons || set.assy_failure_reasons || set.declined_reason || set.scrap_reason || set.reason"
                      class="p-2 bg-amber-50/80 border border-amber-200 rounded-xl text-[11px] text-amber-900 leading-tight">
                      <span class="font-bold block text-[10px] text-amber-800 uppercase mb-0.5">Catatan / Alasan:</span>
                      <p class="line-clamp-2">{{ set.qc_failure_reasons || set.assy_failure_reasons ||
                        set.declined_reason || set.scrap_reason || set.reason }}</p>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="mt-auto pt-2 border-t border-slate-100 space-y-1.5">

                    <!-- 1. Jika Selesai Diverifikasi (baik normal maupun declined) -> KUNCI DENGAN SELESAI -->
                    <template v-if="isSetFullyVerified(set)">
                      <button type="button" @click.stop="openAdminSetDetailModal(set)"
                        class="w-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center space-x-1.5 cursor-pointer min-h-[38px] transition active:scale-95 select-none"
                        title="Klik untuk melihat detail rakitan">
                        <i class="fa-solid fa-lock text-slate-500"></i>
                        <span>Selesai Diverifikasi (Terkunci)</span>
                      </button>
                    </template>

                    <!-- 2. Jika Declined belum diverifikasi -> Tombol Pilih -->
                    <template
                      v-else-if="set.is_declined || (set.verified_status || '').toUpperCase() === 'DECLINED' || (set.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || set.process_stage === 'declined' || (set.status || '').toUpperCase() === 'DECLINED' || (set.display_status && set.display_status.toLowerCase().includes('declin'))">
                      <button type="button" @click.stop="openAdminVerifySetModal(set)"
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-2.5 rounded-xl text-xs shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[36px]"
                        title="Pilih set declined untuk verifikasi pemulihan part ke Tersedia">
                        <i class="fa-solid fa-check-to-slot"></i>
                        <span>Pilih Verifikasi Declined</span>
                      </button>
                    </template>

                    <!-- 3. Jika Pengajuan Daftar Ulang Perakitan / Tahap Perakitan -> PILIH OPERATOR ASSY -->
                    <template
                      v-else-if="set.process_stage === 'perakitan' || set.verified_status === 'PENDING_ADMIN_ASSY_REREG'">
                      <button type="button" @click.stop="openAdminSetDetailModal(set)"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-2 px-2.5 rounded-xl text-xs shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[36px]"
                        title="Buka untuk verifikasi dan tentukan operator rakit ulang">
                        <i class="fa-solid fa-user-gear"></i>
                        <span>Verifikasi & Tunjuk Operator</span>
                      </button>
                    </template>

                    <!-- 4. Tindakan Persetujuan Unit Rakitan Reguler -->
                    <template v-else>
                      <button type="button"
                        @click.stop="adminApproveHoldItem(set, set.process_stage === 'qc' ? 'APPROVE_GOOD_QC' : 'APPROVE_GOOD')"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-2.5 rounded-xl text-xs shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[36px]"
                        title="Setujui sebagai Good dan teruskan">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Good {{ set.process_stage === 'qc' ? '(Lanjut Dokumen)' : '(Lanjut QC)' }}</span>
                      </button>
                      <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click.stop="openReplaceForHoldItem(set)"
                          class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-2 rounded-xl text-[10.5px] shadow-sm flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[32px]"
                          title="Ganti Part Bermasalah">
                          <i class="fa-solid fa-wrench"></i>
                          <span>Ganti Part</span>
                        </button>
                        <button type="button" @click.stop="openAdminSetDetailModal(set)"
                          class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold py-1.5 px-2 rounded-xl text-[10.5px] flex items-center justify-center gap-1.5 transition cursor-pointer min-h-[32px]"
                          title="Lihat detail rakitan">
                          <i class="fa-solid fa-circle-info text-slate-500"></i>
                          <span>Detail</span>
                        </button>
                      </div>
                    </template>
                  </div>

                </div>
              </div>

              <!-- Pagination: adminSets -->
              <div
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print mt-1">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.adminSets.page }}</strong> / {{ totalPgAdminSets }} <span class="text-slate-400 ml-1">({{
                    (filteredAdminSetsOnly||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.adminSets.page = 1" :disabled="pgState.adminSets.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.adminSets.page--" :disabled="pgState.adminSets.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.adminSets.page }}</span>
                  <button type="button" @click="pgState.adminSets.page++"
                    :disabled="pgState.adminSets.page >= totalPgAdminSets"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.adminSets.page = totalPgAdminSets"
                    :disabled="pgState.adminSets.page >= totalPgAdminSets"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </div>

            <!-- EMPTY STATE: JIKA TIDAK ADA DATA SAMA SEKALI -->
            <div v-else
              class="bg-slate-50 border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-400 space-y-2">
              <i class="fa-solid fa-boxes-stacked text-3xl text-slate-300"></i>
              <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Data Unit Rakitan</h4>
              <p class="text-xs text-slate-500 max-w-md mx-auto">Tidak ada barang rakitan yang menunggu persetujuan
                sesuai filter yang dipilih.</p>
            </div>

          </div>
        </div>

        <!-- =================================================================== -->
        <!-- VIEW: VERIFIKASI 1 PART (APPROVAL PART SATUAN)                      -->
        <!-- =================================================================== -->
        <div v-else-if="adminSubTab === 'verify_part'" class="space-y-4 sm:space-y-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <div class="flex items-center gap-2">
                  <span
                    class="w-7 h-7 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-microchip"></i>
                  </span>
                  <h3 class="text-base sm:text-lg font-black text-slate-900">Verifikasi Status Part</h3>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                  Persetujuan part satuan lepasan (Hold, Rework, Scrap) untuk verifikasi dan pemulihan status ke Master
                  Stok.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="loadPendingVerifications"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 cursor-pointer">
                  <i class="fa-solid fa-rotate"></i>
                  <span>Segarkan Antrean</span>
                </button>
              </div>
            </div>

            <!-- Filter Bar Verifikasi 1 Part -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 sm:p-4 space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
                <!-- Dropdown Tipe Part -->
                <div>
                  <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Tipe Part</label>
                  <select v-model="adminPartFilter.partType"
                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none min-h-[38px]">
                    <option value="">Semua Part</option>
                    <option value="15">P9 Part 15 (Identitas P9)</option>
                    <option value="1">P9 Part 1</option>
                    <option value="12">P9 Part 12</option>
                  </select>
                </div>

                <!-- Dropdown Status Part -->
                <div>
                  <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Status Part</label>
                  <select v-model="adminPartFilter.status"
                    class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none min-h-[38px]">
                    <option value="">Semua Status</option>
                    <option value="Hold">Hold</option>
                    <option value="Scrap">Scrap</option>
                  </select>
                </div>

                <!-- Input Pencarian SN / Pengaju / Bekas Tray -->
                <div>
                  <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Pencarian SN / Tray /
                    CS</label>
                  <div class="relative">
                    <i
                      class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" v-model="adminPartFilter.search"
                      placeholder="Cari SN Part / Pengaju / Tray Asal..."
                      class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none min-h-[38px]">
                  </div>
                </div>

                <!-- Filter Tanggal Mulai -->
                <div>
                  <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Tanggal Mulai</label>
                  <input type="date" v-model="adminPartFilter.startDate"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none min-h-[38px]">
                </div>

                <!-- Filter Tanggal Selesai + Tombol Reset -->
                <div class="flex items-end gap-1.5">
                  <div class="flex-1">
                    <label class="block text-[10px] font-black uppercase text-slate-500 mb-1">Tanggal Selesai</label>
                    <input type="date" v-model="adminPartFilter.endDate"
                      class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none min-h-[38px]">
                  </div>
                  <button type="button" @click="resetAdminPartFilter"
                    class="bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center min-h-[38px] cursor-pointer"
                    title="Reset Filter Part">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- TABEL PERMOHONAN PART SATUAN -->
            <div v-if="filteredAdminPartsOnly.length > 0"
              class="border border-slate-200 rounded-2xl overflow-hidden shadow-2xs">
              <div class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-700">
                  <thead
                    class="bg-slate-100/90 text-slate-700 uppercase font-black tracking-wider text-[10px] border-b border-slate-200">
                    <tr>
                      <th class="p-3 text-center w-12">No</th>
                      <th class="p-3">Tipe Part</th>
                      <th class="p-3 font-mono">Serial Number</th>
                      <th class="p-3 text-center">Status Part</th>
                      <th class="p-3 text-center">Target Pemulihan</th>
                      <th class="p-3">Bekas Rakitan Asal</th>
                      <th class="p-3">Pengaju</th>
                      <th class="p-3">Alasan / Catatan</th>
                      <th class="p-3 text-center">Status Verifikasi</th>
                      <th class="p-3 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(req, idx) in pagedAdminParts" :key="req.id" class="hover:bg-slate-50/70 transition">
                      <!-- 1. No -->
                      <td class="p-3 text-center font-bold text-slate-400 text-[11px]">{{ idx + 1 }}</td>

                      <!-- 2. Tipe Part -->
                      <td class="p-3 font-bold text-slate-900 whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold border"
                          :class="req.part_type === '15' ? 'bg-purple-50 text-purple-800 border-purple-200' : (req.part_type === '1' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200')">
                          P9 Part {{ req.part_type }}
                        </span>
                      </td>

                      <!-- 3. Serial Number -->
                      <td class="p-3 font-mono font-bold text-slate-900 whitespace-nowrap">
                        <span>{{ req.sn }}</span>
                        <span v-if="req.cs" class="ml-1 px-1.5 py-0.2 rounded bg-slate-200 text-slate-700 text-[9px]">
                          CS: {{ req.cs }}
                        </span>
                      </td>

                      <!-- 4. Status Part Saat Ini -->
                      <td class="p-3 text-center whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase border"
                          :class="(req.current_status || '').toLowerCase().startsWith('declin') ? 'bg-rose-100 text-rose-800 border-rose-300' : ((req.current_status || '').toUpperCase() === 'SCRAP' ? 'bg-rose-50 text-rose-700 border-rose-200' : ((req.current_status || '').toUpperCase() === 'REWORK' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-amber-50 text-amber-800 border-amber-200'))">
                          {{ req.current_status || 'HOLD' }}
                        </span>
                      </td>

                      <!-- 5. Target Pemulihan -->
                      <td class="p-3 text-center whitespace-nowrap">
                        <span
                          class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase">
                          {{ req.target_status || 'Tersedia' }}
                        </span>
                      </td>

                      <!-- 6. Bekas Rakitan Asal (Tracking) -->
                      <td class="p-3 whitespace-nowrap">
                        <div v-if="req.previous_set_number || req.previous_tray_code" class="space-y-0.5">
                          <span class="font-mono font-bold text-slate-800 text-[11px] block">
                            Set #{{ req.previous_set_number || '-' }}
                          </span>
                          <span class="font-mono text-red-600 text-[10px] block">
                            {{ req.previous_tray_code || '-' }}
                          </span>
                        </div>
                        <span v-else class="text-slate-400 text-[11px] italic">Part Lepasan / Master Stok</span>
                      </td>

                      <!-- 7. Pengaju -->
                      <td class="p-3 whitespace-nowrap">
                        <span class="font-bold text-slate-800 block text-[11px]">{{
                          getOperatorFullName(req.requestor_username) }}</span>
                        <span class="text-[10px] text-slate-400 block">{{ formatDateTime(req.created_at) }}</span>
                      </td>

                      <!-- 8. Alasan Pelepasan -->
                      <td class="p-3 max-w-xs">
                        <p class="text-slate-700 text-[11px] line-clamp-2 leading-relaxed" :title="req.reason">
                          {{ req.reason || req.stock_last_reason || '-' }}
                        </p>
                      </td>

                      <!-- 9. Status Permohonan -->
                      <td class="p-3 text-center font-sans whitespace-nowrap">
                        <span v-if="req.status === 'PENDING'"
                          class="bg-amber-50 text-amber-700 border border-amber-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase">
                          Menunggu Verifikasi
                        </span>
                        <span v-else-if="req.status === 'APPROVED'"
                          class="bg-emerald-50 text-emerald-700 border border-emerald-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase inline-flex items-center gap-1">
                          <i class="fa-solid fa-lock text-[9px]"></i> Selesai
                        </span>
                        <span v-else
                          class="bg-rose-50 text-rose-700 border border-rose-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase inline-flex items-center gap-1">
                          <i class="fa-solid fa-lock text-[9px]"></i> Selesai (Ditolak)
                        </span>
                      </td>

                      <!-- 10. Aksi -->
                      <td class="p-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                          <!-- Tombol Detail Tracking Part -->
                          <button type="button" @click="openAdminPartDetailModal(req)"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-2.5 py-1.5 rounded-lg text-xs transition active:scale-95 flex items-center gap-1 cursor-pointer"
                            title="Lihat riwayat pelacakan bekas set rakitan asal dan histori hold">
                            <i class="fa-solid fa-timeline text-indigo-600"></i>
                            <span class="text-[11px]">Detail</span>
                          </button>

                          <!-- Tombol Verifikasi Part -->
                          <button v-if="req.status === 'PENDING'" @click="openAdminVerifyModal(req)"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs shadow-xs transition active:scale-95 flex items-center gap-1 cursor-pointer"
                            title="Verifikasi dan tentukan status baru part">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span class="text-[11px]">Verify</span>
                          </button>
                          <span v-else
                            class="text-emerald-700 font-bold bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg text-xs inline-flex items-center gap-1 select-none">
                            <i class="fa-solid fa-lock text-[10px]"></i> Selesai (Terkunci)
                          </span>

                          <!-- Tombol Hapus Permohonan Part -->
                          <button type="button" @click="deleteAdminVerifyRequest(req)"
                            class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 hover:border-red-300 font-bold p-1.5 px-2 rounded-lg text-xs transition active:scale-95 flex items-center gap-1 cursor-pointer"
                            title="Hapus permohonan verifikasi part ini">
                            <i class="fa-solid fa-trash-can text-xs text-red-600"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <!-- Pagination: adminParts -->
                <div
                  class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print">
                  <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                      pgState.adminParts.page }}</strong> / {{ totalPgAdminParts }} <span
                      class="text-slate-400 ml-1">({{ (filteredAdminPartsOnly||[]).length }} data)</span></span>
                  <div class="flex items-center gap-1">
                    <button type="button" @click="pgState.adminParts.page = 1" :disabled="pgState.adminParts.page <= 1"
                      class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                        class="fa-solid fa-angles-left"></i></button>
                    <button type="button" @click="pgState.adminParts.page--" :disabled="pgState.adminParts.page <= 1"
                      class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                        class="fa-solid fa-angle-left"></i></button>
                    <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.adminParts.page }}</span>
                    <button type="button" @click="pgState.adminParts.page++"
                      :disabled="pgState.adminParts.page >= totalPgAdminParts"
                      class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                        class="fa-solid fa-angle-right"></i></button>
                    <button type="button" @click="pgState.adminParts.page = totalPgAdminParts"
                      :disabled="pgState.adminParts.page >= totalPgAdminParts"
                      class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                        class="fa-solid fa-angles-right"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <!-- EMPTY STATE VERIFIKASI 1 PART -->
            <div v-else
              class="bg-slate-50 border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-400 space-y-2">
              <i class="fa-solid fa-microchip text-3xl text-slate-300"></i>
              <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Permohonan Part Satuan</h4>
              <p class="text-xs text-slate-500 max-w-md mx-auto">
                Tidak ada data part satuan lepasan (Hold, Rework, Scrap) yang menunggu persetujuan verifikasi.
              </p>
            </div>

          </div>
        </div>

        <!-- =================================================================== -->
        <!-- VIEW: VERIFIKASI PERGANTIAN PART (APPROVAL KOMPONEN LEPASAN/GANTI)   -->
        <!-- =================================================================== -->
        <div v-else-if="adminSubTab === 'verify_replacement'" class="space-y-4 sm:space-y-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <div class="flex items-center gap-2">
                  <span
                    class="w-7 h-7 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-wrench"></i>
                  </span>
                  <h3 class="text-base sm:text-lg font-black text-slate-900">Verifikasi Pergantian Part: Persetujuan
                    Penggantian Komponen</h3>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                  Persetujuan permohonan penggantian komponen part rakitan yang diajukan dari Hasil Perakitan.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="loadPendingVerifications"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 cursor-pointer">
                  <i class="fa-solid fa-rotate"></i>
                  <span>Segarkan Antrean</span>
                </button>
              </div>
            </div>

            <!-- Filter Bar Verifikasi Pergantian Part -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 sm:p-4 space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 items-end">
                <!-- Filter Tipe Part -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-microchip text-slate-400 mr-1"></i>Tipe Part:
                  </label>
                  <select v-model="adminRepPartTypeFilter"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                    <option value="">Semua Part (15, 1, 12)</option>
                    <option value="15">P9 Part 15</option>
                    <option value="1">P9 Part 1</option>
                    <option value="12">P9 Part 12</option>
                  </select>
                </div>

                <!-- Filter Status Target Part Lama -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-hand text-slate-400 mr-1"></i>Status Part Lama:
                  </label>
                  <select v-model="adminRepStatusFilter"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="Hold">Hold</option>
                    <option value="Scrap">Scrap</option>
                  </select>
                </div>

                <!-- Tanggal Mulai -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-calendar-day text-slate-400 mr-1"></i>Dari Tanggal:
                  </label>
                  <input type="date" v-model="adminRepStartDate"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px]">
                </div>

                <!-- Tanggal Selesai -->
                <div>
                  <label class="block text-[10px] font-bold text-slate-500 mb-1">
                    <i class="fa-solid fa-calendar-day text-slate-400 mr-1"></i>Sampai Tanggal:
                  </label>
                  <input type="date" v-model="adminRepEndDate"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px]">
                </div>

                <!-- Search box & Reset -->
                <div class="flex items-center gap-1.5">
                  <div class="relative flex-1">
                    <i class="fa-solid fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" v-model="adminRepSearchQuery" placeholder="Cari WO, SN, alasan..."
                      class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px]">
                  </div>
                  <button type="button" @click="resetAdminRepFilter"
                    class="bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 font-bold px-3 py-1.5 rounded-xl text-xs transition flex items-center gap-1 min-h-[36px] cursor-pointer"
                    title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- List / Grid Card Pengajuan Ganti Part -->
            <template v-if="filteredAdminReplacements.length > 0">
              <div class="space-y-3">
                <div class="flex items-center justify-between text-xs font-bold text-slate-500 px-1">
                  <span>Daftar Pengajuan Pergantian Part ({{ filteredAdminReplacements.length }})</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-4">
                  <div v-for="(req, idx) in pagedAdminRepl" :key="'rep-' + req.replacement_id"
                    class="bg-white border rounded-2xl p-4 shadow-sm transition flex flex-col justify-between relative overflow-hidden"
                    :class="(req.status !== 'PENDING' && req.replacement_status !== 'PENDING') ? 'border-emerald-300 bg-emerald-50/20 ring-1 ring-emerald-100 opacity-90' : 'border-slate-200 hover:border-amber-300 hover:shadow-md'">

                    <!-- Card Header -->
                    <div
                      class="flex items-center justify-between border-b border-slate-100 pb-2.5 mb-3 gap-1.5 flex-wrap">
                      <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                        <span
                          class="bg-amber-600 text-white text-[11px] font-black px-2 py-0.5 rounded-lg font-mono shrink-0">
                          #{{ req.set_number }}
                        </span>
                        <span class="text-xs font-black text-slate-800 font-mono truncate" :title="req.tray_code">
                          {{ req.tray_code || ('Tray #' + req.tray_id) }}
                        </span>
                        <span v-if="req.source_document_mo"
                          class="text-[9.5px] bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-mono font-bold shrink-0 border border-slate-200"
                          title="Nomor Dokumen MO">
                          MO: {{ req.source_document_mo }}
                        </span>
                      </div>
                      <span v-if="req.replacement_status === 'APPROVED' || req.status === 'APPROVED'"
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap shrink-0">
                        <i class="fa-solid fa-lock text-[9px]"></i> Selesai
                      </span>
                      <span v-else-if="req.replacement_status === 'REJECTED' || req.status === 'REJECTED'"
                        class="bg-rose-100 text-rose-900 border border-rose-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap shrink-0">
                        <i class="fa-solid fa-lock text-[9px]"></i> Selesai (Ditolak)
                      </span>
                      <span v-else
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1 whitespace-nowrap shrink-0">
                        <i class="fa-solid fa-wrench text-[9px]"></i> Ganti Part
                      </span>
                    </div>

                    <!-- Detail Penggantian & Info Set SN -->
                    <div class="space-y-2.5 mb-3 font-sans">
                      <!-- Box Rincian Set SN Rakitan Unit -->
                      <div
                        class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 space-y-1.5 text-[11px] font-mono">
                        <div
                          class="flex items-center justify-between text-[10px] font-sans font-bold text-slate-600 border-b border-slate-200/80 pb-1">
                          <span class="flex items-center gap-1 text-slate-700 uppercase">
                            <i class="fa-solid fa-boxes-stacked text-amber-600"></i> Set SN Unit Rakitan
                          </span>
                          <span class="text-slate-500 font-normal">PIC: {{ req.pic_assembly || req.replacement_requestor
                            || '-' }}</span>
                        </div>
                        <div class="space-y-1">
                          <div
                            class="flex items-center justify-between bg-white px-2 py-1 rounded border border-slate-200/70"
                            :class="parseReplacements(req).some(r => String(r.part_type) === '15') ? 'ring-1 ring-amber-400 bg-amber-50/40' : ''">
                            <span class="text-[10px] font-bold text-slate-500 font-sans flex items-center gap-1">
                              P9 Part 15:
                              <span v-if="parseReplacements(req).some(r => String(r.part_type) === '15')"
                                class="text-[9px] bg-amber-500 text-white font-bold px-1 rounded">Diganti</span>
                            </span>
                            <span class="font-bold text-slate-800">{{ req.part15_sn || '-' }}</span>
                          </div>
                          <div
                            class="flex items-center justify-between bg-white px-2 py-1 rounded border border-slate-200/70"
                            :class="parseReplacements(req).some(r => String(r.part_type) === '1') ? 'ring-1 ring-amber-400 bg-amber-50/40' : ''">
                            <span class="text-[10px] font-bold text-slate-500 font-sans flex items-center gap-1">
                              P9 Part 1:
                              <span v-if="parseReplacements(req).some(r => String(r.part_type) === '1')"
                                class="text-[9px] bg-amber-500 text-white font-bold px-1 rounded">Diganti</span>
                            </span>
                            <span class="font-bold text-slate-800">{{ req.part1_sn || '-' }}</span>
                          </div>
                          <div
                            class="flex items-center justify-between bg-white px-2 py-1 rounded border border-slate-200/70"
                            :class="parseReplacements(req).some(r => String(r.part_type) === '12') ? 'ring-1 ring-amber-400 bg-amber-50/40' : ''">
                            <span class="text-[10px] font-bold text-slate-500 font-sans flex items-center gap-1">
                              P9 Part 12:
                              <span v-if="parseReplacements(req).some(r => String(r.part_type) === '12')"
                                class="text-[9px] bg-amber-500 text-white font-bold px-1 rounded">Diganti</span>
                            </span>
                            <span class="font-bold text-slate-800">{{ req.part12_sn || '-' }}</span>
                          </div>
                        </div>
                      </div>

                      <!-- Box Part Lama -> Part Baru -->
                      <div class="bg-amber-50/90 border border-amber-300 rounded-xl p-2.5 space-y-2">
                        <div class="flex items-center justify-between border-b border-amber-200 pb-1">
                          <span class="text-[10px] font-black uppercase text-amber-900 flex items-center gap-1">
                            <i class="fa-solid fa-arrows-rotate text-amber-600"></i> Part yang Diganti
                          </span>
                          <span
                            class="text-[9.5px] font-bold bg-amber-200 text-amber-900 px-1.5 py-0.2 rounded border border-amber-300">
                            Part Lama: {{ req.replacement_old_status || 'Hold' }}
                          </span>
                        </div>

                        <!-- Daftar Komponen -->
                        <div class="space-y-1.5">
                          <div v-for="(rep, rIdx) in parseReplacements(req)" :key="rIdx"
                            class="bg-white rounded-lg p-1.5 border border-amber-200/80 text-[11px] font-mono">
                            <div class="flex items-center justify-between">
                              <span class="font-bold text-slate-800 font-sans">P9 Part {{ rep.part_type }}:</span>
                              <div class="flex items-center gap-1.5">
                                <span class="line-through text-rose-500 font-bold" title="Part Lama (Akan dilepas)">{{
                                  rep.old_sn || '-' }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-amber-600"></i>
                                <span
                                  class="font-black text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-300"
                                  title="Part Baru (Pengganti)">{{ rep.new_sn }}</span>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Alasan & Pengaju -->
                        <div v-if="req.replacement_reason"
                          class="text-[10.5px] text-amber-900 bg-amber-100/60 p-1.5 rounded-lg border border-amber-200 leading-snug">
                          <strong>Alasan:</strong> {{ req.replacement_reason }}
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-500 pt-0.5">
                          <span>Diajukan:</span>
                          <strong class="text-slate-800">{{ getOperatorFullName(req.replacement_requestor) }}</strong>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-400">
                          <span>Waktu:</span>
                          <span class="font-mono text-slate-600">{{ formatDateTime(req.replacement_created_at) }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-auto pt-2 border-t border-slate-100 space-y-2">
                      <!-- Tombol Lihat Detail Transaksi Lengkap (Tersedia untuk semua status) -->
                      <button type="button" @click="openAdminReplacementDetailModal(req)"
                        class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold py-1.5 px-2.5 rounded-xl text-[11px] flex items-center justify-center space-x-1.5 cursor-pointer transition active:scale-95"
                        title="Lihat rincian lengkap transaksi dan perakitan">
                        <i class="fa-solid fa-file-invoice text-amber-600"></i>
                        <span>Lihat Detail Transaksi</span>
                      </button>

                      <div v-if="req.status === 'PENDING' || req.replacement_status === 'PENDING'"
                        class="grid grid-cols-2 gap-1.5">
                        <button type="button" @click="adminApproveReplacement(req)"
                          class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-2 rounded-xl text-[11px] shadow-sm flex items-center justify-center gap-1 transition active:scale-95 cursor-pointer min-h-[34px]"
                          title="Setujui pergantian part ini">
                          <i class="fa-solid fa-check-double"></i>
                          <span>Setujui Ganti</span>
                        </button>
                        <button type="button" @click="adminRejectReplacement(req)"
                          class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-2 rounded-xl text-[11px] shadow-sm flex items-center justify-center gap-1 transition active:scale-95 cursor-pointer min-h-[34px]"
                          title="Tolak pengajuan pergantian part">
                          <i class="fa-solid fa-xmark"></i>
                          <span>Tolak</span>
                        </button>
                      </div>
                      <div v-else>
                        <div
                          class="w-full bg-slate-100 border border-slate-200 text-slate-500 font-bold py-1.5 px-2 rounded-xl text-xs flex items-center justify-center space-x-1.5">
                          <i class="fa-solid fa-lock text-slate-400"></i>
                          <span>Selesai (Terkunci)</span>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- Pagination: adminRepl -->
              <div
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print mt-1">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.adminRepl.page }}</strong> / {{ totalPgAdminRepl }} <span class="text-slate-400 ml-1">({{
                    (filteredAdminReplacements||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.adminRepl.page = 1" :disabled="pgState.adminRepl.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.adminRepl.page--" :disabled="pgState.adminRepl.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.adminRepl.page }}</span>
                  <button type="button" @click="pgState.adminRepl.page++"
                    :disabled="pgState.adminRepl.page >= totalPgAdminRepl"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.adminRepl.page = totalPgAdminRepl"
                    :disabled="pgState.adminRepl.page >= totalPgAdminRepl"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </template>

            <!-- Empty State -->
            <div v-else
              class="bg-slate-50 border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-400 space-y-2">
              <i class="fa-solid fa-wrench text-3xl text-slate-300"></i>
              <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Antrean Pergantian Part</h4>
              <p class="text-xs text-slate-500 max-w-md mx-auto">
                Seluruh pengajuan pergantian part telah selesai diverifikasi atau belum ada pengajuan baru dari Hasil
                Perakitan.
              </p>
            </div>

          </div>
        </div>


        <!-- VIEW 3: AUDIT TRAIL OVERRIDE GUDANG -->
        <div v-else-if="adminSubTab === 'audit'" class="space-y-4 sm:space-y-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900">Log Audit Trail: Override Status Pihak Gudang
                </h3>
                <p class="text-xs text-slate-500 font-medium">Rekaman histori lengkap atas setiap tindakan
                  pemulihan/pengubahan status stok oleh Gudang.</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="loadAuditLogs"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95">
                  <i class="fa-solid fa-rotate"></i>
                  <span>Segarkan Log</span>
                </button>
              </div>
            </div>

            <!-- Filter Bar Audit Trail (Tanggal Mulai, Tanggal Selesai, Status, Search) -->
            <div
              class="bg-slate-50 border border-slate-200 rounded-xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
              <!-- Tanggal Mulai -->
              <div>
                <label class="block text-slate-500 font-bold text-[10px] mb-1">Tanggal Mulai</label>
                <input type="date" v-model="auditFilter.startDate"
                  class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 font-semibold text-slate-900 focus:outline-none focus:border-red-600">
              </div>

              <!-- Tanggal Selesai (Default Hari Ini) -->
              <div>
                <label class="block text-slate-500 font-bold text-[10px] mb-1">Tanggal Selesai</label>
                <input type="date" v-model="auditFilter.endDate"
                  class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 font-semibold text-slate-900 focus:outline-none focus:border-red-600">
              </div>

              <!-- Filter Status Barang -->
              <div>
                <label class="block text-slate-500 font-bold text-[10px] mb-1">Status Barang</label>
                <select v-model="auditFilter.status"
                  class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 font-semibold text-slate-900 focus:outline-none focus:border-red-600">
                  <option value="">Semua Status</option>
                  <option value="Good">Good</option>
                  <option value="Scrap">Scrap</option>
                  <option value="Hold">Hold</option>
                  <option value="Rework">Rework</option>
                  <option value="Tersedia">Tersedia</option>
                  <option value="Supplied">Supplied</option>
                </select>
              </div>

              <!-- Search Box -->
              <div>
                <label class="block text-slate-500 font-bold text-[10px] mb-1">Cari Log / SN</label>
                <div class="relative">
                  <input type="text" v-model="auditFilter.search" placeholder="Cari SN / Petugas / Alasan..."
                    class="w-full bg-white border border-slate-300 rounded-lg pl-8 pr-2.5 py-1.5 font-semibold text-slate-900 focus:outline-none focus:border-red-600">
                  <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                </div>
              </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 -mx-1 sm:mx-0">
              <table class="w-full text-left text-xs min-w-[800px]">
                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                  <tr>
                    <th class="p-3 w-12 text-center">No</th>
                    <th class="p-3">Waktu (Timestamp)</th>
                    <th class="p-3">Part Type</th>
                    <th class="p-3">Serial Number (SN)</th>
                    <th class="p-3">Status Lama</th>
                    <th class="p-3">Status Baru</th>
                    <th class="p-3">User Gudang / Pelaku</th>
                    <th class="p-3">Alasan / Catatan Kondisi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px] sm:text-xs">
                  <tr v-for="(log, idx) in pagedAuditLogs" :key="log.id" class="hover:bg-slate-50">
                    <td class="p-3 text-center font-bold text-slate-400 font-sans">{{ idx + 1 }}</td>
                    <td class="p-3 text-slate-600 font-sans">{{ formatDateTime(log.created_at) }}</td>
                    <td class="p-3 font-sans font-bold text-red-700">P9 Part {{ log.part_type }}</td>
                    <td class="p-3 font-black text-slate-900">{{ log.sn }}</td>
                    <td class="p-3 font-sans">
                      <span :class="getStatusBadgeClass(log.old_status)"
                        class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase border">
                        {{ log.old_status }}
                      </span>
                    </td>
                    <td class="p-3 font-sans">
                      <span :class="getStatusBadgeClass(log.new_status)"
                        class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase border">
                        {{ log.new_status }}
                      </span>
                    </td>
                    <td class="p-3 font-sans">
                      <strong class="text-slate-900 block">{{ getOperatorFullName(log.actor_username) }}</strong>
                      <span class="text-[10px] text-slate-500 font-mono">{{ log.actor_role }}</span>
                    </td>
                    <td class="p-3 font-sans text-slate-700">
                      <div
                        class="bg-amber-50/80 border border-amber-200 text-amber-900 px-2.5 py-1 rounded-lg text-xs leading-tight">
                        {{ log.reason || '-' }}
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredAuditLogs.length === 0">
                    <td colspan="8" class="p-8 text-center text-slate-400 font-sans">Tidak ada riwayat audit trail yang
                      cocok dengan filter.</td>
                  </tr>
                </tbody>
              </table>
              <!-- Pagination: auditLogs -->
              <div v-if="totalPgAuditLogs > 1"
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.auditLogs.page }}</strong> / {{ totalPgAuditLogs }} <span class="text-slate-400 ml-1">({{
                    (filteredAuditLogs||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.auditLogs.page = 1" :disabled="pgState.auditLogs.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.auditLogs.page--" :disabled="pgState.auditLogs.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.auditLogs.page }}</span>
                  <button type="button" @click="pgState.auditLogs.page++"
                    :disabled="pgState.auditLogs.page >= totalPgAuditLogs"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.auditLogs.page = totalPgAuditLogs"
                    :disabled="pgState.auditLogs.page >= totalPgAuditLogs"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- VIEW 4: RIWAYAT AUDIT DOWNTIME PERAKITAN -->
        <div v-else-if="adminSubTab === 'downtime'" class="space-y-4 sm:space-y-6">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900">Audit Downtime &amp; Jeda Perakitan</h3>
                <p class="text-xs text-slate-500 font-medium">Log pencatatan waktu jeda kerja operator, alasan downtime,
                  dan durasi penghentian perakitan.</p>
              </div>
              <button @click="loadDowntimeLogs"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 cursor-pointer">
                <i class="fa-solid fa-rotate"></i>
                <span>Segarkan Log</span>
              </button>
            </div>

            <!-- Filter Controls -->
            <div
              class="bg-slate-50 border border-slate-200 rounded-xl p-3 sm:p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs items-end">
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Filter operator:</label>
                <select v-model="downtimeFilter.operator" @change="loadDowntimeLogs"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[38px] cursor-pointer">
                  <option value="">Semua operator</option>
                  <option v-for="op in operatorUsersList" :key="op.username" :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Cari kode tray / alasan:</label>
                <div class="relative">
                  <input type="text" v-model="downtimeFilter.q" @input="loadDowntimeLogs"
                    placeholder="Ketik tray / alasan..."
                    class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-2.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px]">
                  <i class="fa-solid fa-search absolute left-2.5 top-3 text-slate-400 text-xs"></i>
                </div>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Rentang tanggal:</label>
                <div
                  class="flex items-center gap-1 bg-white border border-slate-300 rounded-xl px-2 py-1.5 min-h-[38px]">
                  <input type="date" v-model="downtimeFilter.startDate" @change="loadDowntimeLogs"
                    class="text-xs font-semibold text-slate-900 focus:outline-none bg-transparent w-full">
                  <span class="text-slate-400">-</span>
                  <input type="date" v-model="downtimeFilter.endDate" @change="loadDowntimeLogs"
                    class="text-xs font-semibold text-slate-900 focus:outline-none bg-transparent w-full">
                </div>
              </div>

              <div>
                <button type="button" @click="resetDowntimeFilter"
                  class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 font-bold px-3 py-2 rounded-xl text-xs transition flex items-center justify-center gap-1.5 cursor-pointer min-h-[38px]">
                  <i class="fa-solid fa-rotate-left"></i>
                  <span>Reset Filter</span>
                </button>
              </div>
            </div>

            <!-- Downtime Table -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-left text-xs min-w-[750px]">
                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                  <tr>
                    <th class="p-3 w-12 text-center">No</th>
                    <th class="p-3">Waktu Mulai Jeda</th>
                    <th class="p-3">Waktu Lanjut</th>
                    <th class="p-3 text-center">Durasi Downtime</th>
                    <th class="p-3">Operator</th>
                    <th class="p-3">Kode Tray &amp; Set</th>
                    <th class="p-3">Alasan / Penyebab Jeda</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 text-[11px] sm:text-xs">
                  <tr v-for="(dt, idx) in pagedDowntime" :key="dt.id" class="hover:bg-slate-50">
                    <td class="p-3 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                    <td class="p-3 font-mono text-slate-600">{{ formatDateTime(dt.paused_at) }}</td>
                    <td class="p-3 font-mono text-slate-600">{{ dt.resumed_at ? formatDateTime(dt.resumed_at) : 'Masih Berlangsung' }}</td>
                    <td class="p-3 text-center font-mono font-bold">
                      <span v-if="!dt.resumed_at"
                        class="bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 rounded-lg text-xs animate-pulse">
                        <i class="fa-solid fa-pause text-[10px] mr-1"></i> Sedang Dijeda
                      </span>
                      <span v-else
                        class="bg-amber-50 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-mono font-bold">
                        {{ formatDuration(dt.duration_seconds) }}
                      </span>
                    </td>
                    <td class="p-3 font-bold text-slate-900">{{ getOperatorFullName(dt.operator) }}</td>
                    <td class="p-3 font-mono font-bold text-red-700">
                      {{ dt.tray_code }} <span v-if="dt.set_number" class="text-slate-600 font-normal">#{{ dt.set_number
                        }}</span>
                    </td>
                    <td class="p-3 text-slate-800">
                      <div
                        class="bg-amber-50 border border-amber-200 text-amber-900 px-2.5 py-1 rounded-lg text-xs leading-tight font-medium">
                        {{ dt.reason || '-' }}
                      </div>
                    </td>
                  </tr>
                  <tr v-if="downtimeLogsList.length === 0">
                    <td colspan="7" class="p-8 text-center text-slate-400">Tidak ada data riwayat downtime perakitan.
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Pagination: downtime -->
              <div v-if="totalPgDowntime > 1"
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.downtime.page }}</strong> / {{ totalPgDowntime }} <span class="text-slate-400 ml-1">({{
                    (downtimeLogsList||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.downtime.page = 1" :disabled="pgState.downtime.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.downtime.page--" :disabled="pgState.downtime.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.downtime.page }}</span>
                  <button type="button" @click="pgState.downtime.page++"
                    :disabled="pgState.downtime.page >= totalPgDowntime"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.downtime.page = totalPgDowntime"
                    :disabled="pgState.downtime.page >= totalPgDowntime"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- VIEW 5: MASTER TESTING POINT (SUPER ADMIN) -->
        <div v-else-if="adminSubTab === 'testing_points'" class="space-y-4 sm:space-y-6">
          <!-- Card Form Testing Point -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 sm:pb-4">
              <div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                  {{ editingTestingPointId ? 'Edit Master Testing Point #' + testingPointForm.test_num : 'Tambah Master Testing Point Baru' }}
                </h3>
                <p class="text-xs text-slate-500 font-medium">
                  Konfigurasi daftar point pengujian operator assembly, instruksi kerja, kriteria output lolos, dan
                  upload gambar petunjuk.
                </p>
              </div>
              <div class="flex items-center gap-2 self-start sm:self-auto">
                <button v-if="editingTestingPointId" type="button" @click="resetTestingPointForm"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                  <i class="fa-solid fa-xmark"></i>
                  <span>Batal Edit</span>
                </button>
                <button type="button" @click="loadTestingPoints"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                  <i class="fa-solid fa-rotate"></i>
                  <span>Segarkan</span>
                </button>
              </div>
            </div>

            <!-- Form Testing Point: 4 Field (Kategori di Kiri Bawah, Instruksi, Output, Multiple Upload Gambar) -->
            <form @submit.prevent="saveTestingPoint" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Field 0: Nomor Test Urut -->
                <div class="sm:col-span-1">
                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                    No<span class="text-red-500">*</span>
                  </label>
                  <input type="number" v-model.number="testingPointForm.test_num" placeholder="Contoh: 1, 2, 16..."
                    min="1" required
                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none min-h-[40px]">
                </div>

                <!-- Field 1: Instruksi Kerja (Required) -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                    Instruksi Kerja <span class="text-red-500">*</span>
                  </label>
                  <input type="text" v-model="testingPointForm.instruction" required
                    class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none min-h-[40px]">
                </div>
              </div>

              <!-- Field 2: Output (Required) -->
              <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                  Output <span class="text-red-500">*</span>
                </label>
                <textarea v-model="testingPointForm.output" rows="2" required
                  class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl px-3.5 py-2 text-xs font-medium text-slate-900 focus:outline-none leading-relaxed"></textarea>
              </div>

              <!-- Field 3: Upload Gambar (Opsional - Multiple Upload) -->
              <div class="space-y-2">
                <label class="block text-xs font-bold uppercase text-slate-700">
                  Upload Gambar <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <div class="flex flex-wrap items-center gap-3">
                  <label
                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 hover:border-indigo-300 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-2xs">
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                    <span>Pilih Gambar (Multiple)</span>
                    <input type="file" multiple accept="image/*" @change="handleImageUpload" class="hidden">
                  </label>
                  <span class="text-[11px] text-slate-500 font-medium">
                    {{ testingPointForm.images.length }} gambar terpilih.
                  </span>
                </div>

                <!-- Preview Thumbnail Gambar -->
                <div v-if="testingPointForm.images.length > 0" class="flex flex-wrap gap-2.5 pt-2">
                  <div v-for="(img, idx) in testingPointForm.images" :key="idx"
                    class="relative group w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-2xs flex items-center justify-center">
                    <img :src="img" alt="Preview" class="w-full h-full object-cover">
                    <button type="button" @click="removeImageFromForm(idx)"
                      class="absolute top-1 right-1 bg-rose-600 text-white w-5 h-5 rounded-full flex items-center justify-center text-[10px] opacity-0 group-hover:opacity-100 transition shadow-xs cursor-pointer"
                      title="Hapus Gambar Ini">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                    <span class="absolute bottom-1 left-1 bg-black/60 text-white text-[9px] font-mono px-1 rounded">
                      #{{ idx + 1 }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Baris Bawah Form: Kategori Test di Kiri Bawah, Tombol Simpan di Kanan Bawah -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end pt-3 border-t border-slate-100">
                <!-- Field 4: Kategori Test (Opsional - Posisikan di Bagian Kiri Bawah Form) -->
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
                    Kategori Test <span class="text-slate-400 font-normal">(Opsional)</span>
                  </label>
                  <div class="relative">
                    <input type="text" v-model="testingPointForm.category" list="testingCategorySuggestions"
                      placeholder="Pilih atau ketik kategori test..."
                      class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:outline-none min-h-[40px]">
                    <datalist id="testingCategorySuggestions">
                      <option value="Cek Visual"></option>
                      <option value="Cek Rigiditas"></option>
                      <option value="Cek Mekanik"></option>
                      <option value="Umum / Fungsional"></option>
                    </datalist>
                  </div>
                </div>

                <!-- Tombol Submit Form (Kanan Bawah) -->
                <div class="flex items-center justify-end gap-2">
                  <button v-if="editingTestingPointId" type="button" @click="resetTestingPointForm"
                    class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[40px] cursor-pointer">
                    Batal
                  </button>
                  <button type="submit" :disabled="isSavingTestingPoint"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center gap-2 cursor-pointer min-h-[40px] active:scale-95">
                    <i v-if="isSavingTestingPoint" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-floppy-disk"></i>
                    <span>{{ editingTestingPointId ? 'Perbarui Testing Point' : 'Simpan Testing Point' }}</span>
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Tabel Daftar Master Testing Points -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <span class="bg-slate-900 text-white text-xs font-mono font-black px-2.5 py-1 rounded-lg">
                  {{ testingPointsList.length }} Point
                </span>
                <h4 class="text-sm sm:text-base font-black text-slate-900">Daftar Point Pengujian Operator Assembly</h4>
              </div>
              <div class="w-full sm:w-64">
                <input type="text" v-model="testingPointFilterQ" placeholder="Cari nomor / instruksi..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-red-600">
              </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-left text-xs min-w-[700px] border-collapse">
                <thead
                  class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] tracking-wider">
                  <tr>
                    <th class="p-3 w-14 text-center">No</th>
                    <th class="p-3 w-36">Kategori</th>
                    <th class="p-3">Instruksi Kerja</th>
                    <th class="p-3">Output yang Diharapkan</th>
                    <th class="p-3 w-28 text-center">Gambar</th>
                    <th class="p-3 w-28 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                  <tr v-for="pt in pagedTestPoint" :key="pt.id || pt.test_num" class="hover:bg-slate-50 transition"
                    :class="editingTestingPointId === pt.id ? 'bg-amber-50/50' : ''">
                    <td class="p-3 text-center font-mono font-black text-slate-900 bg-slate-50/50">
                      #{{ pt.test_num }}
                    </td>
                    <td class="p-3 font-semibold text-slate-700">
                      <span
                        class="bg-indigo-50 border border-indigo-200 text-indigo-900 text-[10.5px] px-2 py-0.5 rounded-md inline-block font-bold">
                        {{ pt.category || 'Umum / Fungsional' }}
                      </span>
                    </td>
                    <td class="p-3 font-bold text-slate-900 text-xs whitespace-pre-line">
                      {{ pt.instruction }}
                    </td>
                    <td class="p-3 text-slate-600 text-xs whitespace-pre-line">
                      {{ pt.output }}
                    </td>
                    <td class="p-3 text-center">
                      <button v-if="getTestPointImagesCount(pt) > 0" type="button"
                        @click="openInstructionGuideModal(pt)"
                        class="px-2 py-1 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-bold text-[10.5px] border border-indigo-300 inline-flex items-center gap-1 cursor-pointer transition"
                        title="Klik untuk melihat carousel gambar">
                        <i class="fa-regular fa-images"></i>
                        <span>{{ getTestPointImagesCount(pt) }} Foto</span>
                      </button>
                      <span v-else class="text-slate-400 text-[11px] italic">-</span>
                    </td>
                    <td class="p-3 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <button type="button" @click="editTestingPoint(pt)"
                          class="p-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-600 text-xs transition cursor-pointer"
                          title="Edit Testing Point">
                          <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button type="button" @click="deleteTestingPoint(pt)"
                          class="p-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-600 text-slate-600 text-xs transition cursor-pointer"
                          title="Hapus Testing Point">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredTestingPointsList.length === 0">
                    <td colspan="6" class="p-8 text-center text-slate-400">
                      Tidak ada data testing point yang sesuai.
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Pagination: testPoint -->
              <div v-if="totalPgTestPoint > 1"
                class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/50 rounded-b-xl select-none no-print">
                <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                    pgState.testPoint.page }}</strong> / {{ totalPgTestPoint }} <span class="text-slate-400 ml-1">({{
                    (filteredTestingPointsList||[]).length }} data)</span></span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.testPoint.page = 1" :disabled="pgState.testPoint.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angles-left"></i></button>
                  <button type="button" @click="pgState.testPoint.page--" :disabled="pgState.testPoint.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                      class="fa-solid fa-angle-left"></i></button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.testPoint.page }}</span>
                  <button type="button" @click="pgState.testPoint.page++"
                    :disabled="pgState.testPoint.page >= totalPgTestPoint"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angle-right"></i></button>
                  <button type="button" @click="pgState.testPoint.page = totalPgTestPoint"
                    :disabled="pgState.testPoint.page >= totalPgTestPoint"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                      class="fa-solid fa-angles-right"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

      

        <!-- VIEW 8: TAMBAH PART NUMBER (MASTER STOK) -->
        <!-- ================= TAB: TAMBAH PART NUMBER (MASTER STOK) ================= -->
      <div v-else-if="adminSubTab === 'add_part' && (hasMenuAccess('add_part') || hasMenuAccess('users') || isAdminUser)" class="space-y-4 sm:space-y-6">

        <!-- Top Header Card -->
        <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 rounded-2xl p-5 sm:p-6 text-white shadow-lg shadow-red-600/15 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="flex items-center space-x-2.5">
              <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center text-white text-lg shadow-inner">
                <i class="fa-solid fa-plus-circle"></i>
              </div>
              <div>
                <h2 class="text-lg sm:text-xl font-black tracking-tight">Tambah Part Number (Master Stok)</h2>
                <p class="text-xs text-red-100 font-medium">Registrasi Serial Number baru ke Master Stok dengan status awal <strong>Tersedia</strong> secara otomatis.</p>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2 self-start sm:self-auto">
            <button @click="navigateTo('master')"
              class="px-3.5 py-2 rounded-xl bg-white/15 hover:bg-white/25 active:scale-95 text-xs font-bold text-white transition flex items-center space-x-1.5 border border-white/20">
              <i class="fa-solid fa-database text-xs"></i>
              <span>Katalog Master Stok</span>
            </button>
            <button @click="loadRecentlyAddedParts()" :disabled="recentAddedLoading"
              class="px-3.5 py-2 rounded-xl bg-white text-red-600 hover:bg-red-50 active:scale-95 text-xs font-black transition flex items-center space-x-1.5 shadow-sm">
              <i class="fa-solid fa-rotate-right text-xs" :class="{ 'fa-spin': recentAddedLoading }"></i>
              <span>Refresh Riwayat</span>
            </button>
          </div>
        </div>

        <!-- Main Form & History Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">

          <!-- Left Column: Input Form (Single or Bulk) -->
          <div class="xl:col-span-5 space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">

              <!-- Form Mode Tabs (Single vs Bulk) -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center space-x-2">
                  <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Mode Input:</span>
                </div>
                <div class="inline-flex p-1 bg-slate-100 rounded-xl space-x-1">
                  <button type="button" @click="addPartTab = 'single'"
                    :class="addPartTab === 'single' ? 'bg-white text-slate-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-keyboard text-xs"></i>
                    <span>Tunggal (Single)</span>
                  </button>
                  <button type="button" @click="addPartTab = 'bulk'"
                    :class="addPartTab === 'bulk' ? 'bg-white text-slate-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-layer-group text-xs"></i>
                    <span>Masal (Bulk)</span>
                  </button>
                </div>
              </div>

              <!-- ================= MODE 1: SINGLE PART FORM ================= -->
              <form v-if="addPartTab === 'single'" @submit.prevent="submitAddSinglePart" class="space-y-4">
                
                <!-- Part Type Selector -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Part <span class="text-red-500">*</span></label>
                  <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="addPartForm.part_type = '1'"
                      :class="addPartForm.part_type === '1' ? 'border-blue-500 bg-blue-50/70 text-blue-900 font-black ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 1</span>
                      <span class="text-[10px] text-slate-500">Cover / Housing</span>
                    </button>
                    <button type="button" @click="addPartForm.part_type = '12'"
                      :class="addPartForm.part_type === '12' ? 'border-purple-500 bg-purple-50/70 text-purple-900 font-black ring-2 ring-purple-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 12</span>
                      <span class="text-[10px] text-slate-500">Slide / Body</span>
                    </button>
                    <button type="button" @click="addPartForm.part_type = '15'"
                      :class="addPartForm.part_type === '15' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 font-black ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 15</span>
                      <span class="text-[10px] text-slate-500">Tube Assy (CS)</span>
                    </button>
                  </div>
                </div>

                <!-- PN/SN Input -->
                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">
                      <span v-if="addPartForm.part_type === '1' || addPartForm.part_type === '12'">Part Number (PN)</span>
                      <span v-else>Serial Number (SN)</span>
                      <span class="text-red-500"> *</span>
                    </label>
                    <span v-if="addPartForm.part_type === '1' || addPartForm.part_type === '12'" class="text-[10px] text-slate-500">Bebas numerik (standar / double)</span>
                    <span v-else class="text-[10px] text-slate-500">Huruf + Angka</span>
                  </div>
                  <div class="relative">
                    <input type="text" v-model="addPartForm.sn" required
                      :placeholder="addPartForm.part_type === '1' ? 'Contoh: 01271, 26371, 55851' : (addPartForm.part_type === '12' ? 'Contoh: 162607, 70121' : 'Contoh: K1103581')"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 uppercase">
                    <div v-if="addPartForm.sn" class="absolute right-2.5 top-1/2 -translate-y-1/2">
                      <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-200 text-slate-700">
                        {{ addPartForm.sn.trim().length }} digit
                      </span>
                    </div>
                  </div>
                </div>

                <!-- CS Input (Khusus Part 15) -->
                <div v-if="addPartForm.part_type === '15'">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode CS (Customer Special) <span class="text-red-500">*</span></label>
                  <input type="text" v-model="addPartForm.cs" required maxlength="10" placeholder="Contoh: K, N, V, X, Z, C"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 uppercase">
                </div>

                <!-- Alasan / Catatan (Opsional) -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan / Keterangan <span class="text-slate-400 font-normal">(Opsional)</span></label>
                  <input type="text" v-model="addPartForm.reason" placeholder="Contoh: Double marking part 1 / Tambahan vendor"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                </div>

                <!-- Preview Card -->
                <div v-if="addPartForm.sn" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500 font-medium">Preview Master Stok:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                      <i class="fa-solid fa-circle-check mr-1 text-[9px]"></i> Tersedia
                    </span>
                  </div>
                  <div class="flex items-center space-x-2 text-sm font-mono font-bold text-slate-900">
                    <span class="px-2 py-0.5 rounded text-xs bg-slate-200 text-slate-800">Part {{ addPartForm.part_type }}</span>
                    <span>{{ addPartForm.sn.trim() }}</span>
                    <span v-if="addPartForm.part_type === '15' && addPartForm.cs" class="text-xs text-slate-600 font-sans font-medium">(CS: {{ addPartForm.cs.trim().toUpperCase() }})</span>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center space-x-2 pt-2">
                  <button type="submit" :disabled="addPartLoading || !addPartForm.sn"
                    class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md shadow-red-600/20 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i v-if="addPartLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                    <i v-else class="fa-solid fa-plus"></i>
                    <span>Tambah Part Number</span>
                  </button>
                  <button type="button" @click="resetAddPartForm"
                    class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition cursor-pointer">
                    Reset
                  </button>
                </div>
              </form>

              <!-- ================= MODE 2: BULK / BATCH FORM ================= -->
              <form v-if="addPartTab === 'bulk'" @submit.prevent="submitAddBulkPart" class="space-y-4">
                
                <!-- Part Type Selector -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Part <span class="text-red-500">*</span></label>
                  <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="addPartBulkForm.part_type = '1'"
                      :class="addPartBulkForm.part_type === '1' ? 'border-blue-500 bg-blue-50/70 text-blue-900 font-black ring-2 ring-blue-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 1</span>
                      <span class="text-[10px] text-slate-500">Cover / Housing</span>
                    </button>
                    <button type="button" @click="addPartBulkForm.part_type = '12'"
                      :class="addPartBulkForm.part_type === '12' ? 'border-purple-500 bg-purple-50/70 text-purple-900 font-black ring-2 ring-purple-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 12</span>
                      <span class="text-[10px] text-slate-500">Slide / Body</span>
                    </button>
                    <button type="button" @click="addPartBulkForm.part_type = '15'; addPartBulkMode = 'list'"
                      :class="addPartBulkForm.part_type === '15' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 font-black ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium'"
                      class="border rounded-xl p-2.5 text-center transition cursor-pointer flex flex-col items-center justify-center">
                      <span class="text-xs font-bold">Part 15</span>
                      <span class="text-[10px] text-slate-500">Tube Assy (CS)</span>
                    </button>
                  </div>
                </div>

                <!-- Submode Selector (List vs Range) -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Metode Batch</label>
                  <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="addPartBulkMode = 'list'"
                      :class="addPartBulkMode === 'list' ? 'border-slate-800 bg-slate-900 text-white font-bold' : 'border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50'"
                      class="border rounded-xl p-2 text-center text-xs transition cursor-pointer flex items-center justify-center space-x-1.5">
                      <i class="fa-solid fa-list-check text-xs"></i>
                      <span>Paste Daftar SN</span>
                    </button>
                    <button type="button" @click="addPartBulkMode = 'range'" :disabled="addPartBulkForm.part_type === '15'"
                      :class="addPartBulkMode === 'range' ? 'border-slate-800 bg-slate-900 text-white font-bold' : 'border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed'"
                      class="border rounded-xl p-2 text-center text-xs transition cursor-pointer flex items-center justify-center space-x-1.5">
                      <i class="fa-solid fa-arrow-down-1-9 text-xs"></i>
                      <span>Rentang Angka</span>
                    </button>
                  </div>
                </div>

                <!-- Multi-line PN/SN Textarea (Mode List) -->
                <div v-if="addPartBulkMode === 'list'">
                  <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700">
                      <span v-if="addPartBulkForm.part_type === '1' || addPartBulkForm.part_type === '12'">Daftar Part Number (PN)</span>
                      <span v-else>Daftar Serial Number (SN)</span>
                      <span class="text-red-500"> *</span>
                    </label>
                    <span class="text-[10px] text-slate-500">1 baris per nomor atau pisahkan koma</span>
                  </div>
                  <textarea v-model="addPartBulkForm.sns_text" rows="5" required
                    placeholder="Contoh:&#10;01271&#10;26371&#10;38821&#10;55851"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-mono text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 uppercase"></textarea>
                </div>

                <!-- Range Input (Mode Range) -->
                <div v-if="addPartBulkMode === 'range'" class="space-y-3">
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1">
                        <span v-if="addPartBulkForm.part_type === '1' || addPartBulkForm.part_type === '12'">PN Awal</span>
                        <span v-else>SN Awal</span>
                        <span class="text-red-500"> *</span>
                      </label>
                      <input type="text" v-model="addPartBulkForm.sn_start" required placeholder="Misal: 01271"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-slate-700 mb-1">
                        <span v-if="addPartBulkForm.part_type === '1' || addPartBulkForm.part_type === '12'">PN Akhir</span>
                        <span v-else>SN Akhir</span>
                        <span class="text-red-500"> *</span>
                      </label>
                      <input type="text" v-model="addPartBulkForm.sn_end" required placeholder="Misal: 01280"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                    </div>
                  </div>
                  <p class="text-[10px] text-slate-500">
                    Sistem akan membuat nomor berurutan secara otomatis dan mempertahankan panjang digit dengan angka nol di depan. Maksimal 500 unit sekali jalan.
                  </p>
                </div>

                <!-- CS Input (Khusus Part 15 Bulk) -->
                <div v-if="addPartBulkForm.part_type === '15'">
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode CS Seluruh Part 15 <span class="text-red-500">*</span></label>
                  <input type="text" v-model="addPartBulkForm.cs" required maxlength="10" placeholder="Contoh: K"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono text-slate-900 uppercase">
                </div>

                <!-- Alasan / Catatan Bulk -->
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Batch <span class="text-slate-400 font-normal">(Opsional)</span></label>
                  <input type="text" v-model="addPartBulkForm.reason" placeholder="Contoh: Batch double marking gelombang 2"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-900">
                </div>

                <!-- Submit Button -->
                <div class="flex items-center space-x-2 pt-2">
                  <button type="submit" :disabled="addPartLoading"
                    class="flex-1 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-2.5 px-4 rounded-xl text-xs shadow-md shadow-red-600/20 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i v-if="addPartLoading" class="fa-solid fa-circle-notch fa-spin"></i>
                    <i v-else class="fa-solid fa-layer-group"></i>
                    <span>Proses Batch Part Number</span>
                  </button>
                  <button type="button" @click="resetAddPartBulkForm"
                    class="px-3.5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-xs font-bold transition cursor-pointer">
                    Reset
                  </button>
                </div>

                <!-- Bulk Feedback Card -->
                <div v-if="addPartBulkResult" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                  <div class="flex items-center justify-between">
                    <h5 class="text-xs font-black text-slate-900">Hasil Penambahan Batch:</h5>
                    <div class="flex items-center gap-1.5">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        {{ addPartBulkResult.added_count }} Berhasil Ditambahkan
                      </span>
                      <span v-if="addPartBulkResult.duplicate_count > 0" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        {{ addPartBulkResult.duplicate_count }} Duplikat Dilewati
                      </span>
                    </div>
                  </div>
                  <div v-if="addPartBulkResult.added && addPartBulkResult.added.length > 0" class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase">Part yang berhasil didaftarkan:</span>
                    <div class="flex flex-wrap gap-1 max-h-28 overflow-y-auto p-2 bg-white rounded-lg border border-slate-200">
                      <span v-for="sn in addPartBulkResult.added" :key="sn" class="px-1.5 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded text-[11px] font-mono font-bold">
                        {{ sn }}
                      </span>
                    </div>
                  </div>
                  <div v-if="addPartBulkResult.duplicates && addPartBulkResult.duplicates.length > 0" class="space-y-1">
                    <span class="text-[10px] font-bold text-slate-500 uppercase">Part yang sudah ada di Master Stok:</span>
                    <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto p-2 bg-white rounded-lg border border-slate-200">
                      <span v-for="dup in addPartBulkResult.duplicates" :key="dup.sn" class="px-1.5 py-0.5 bg-amber-50 text-amber-800 border border-amber-200 rounded text-[11px] font-mono">
                        {{ dup.sn }} ({{ dup.status }})
                      </span>
                    </div>
                  </div>
                </div>

              </form>

            </div>
          </div>

          <!-- Right Column: Riwayat Part Terbaru di Master Stok -->
          <div class="xl:col-span-7 space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
              
              <!-- History Header & Filter -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 sm:pb-4">
                <div class="flex items-center space-x-2.5">
                  <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                  </div>
                  <div>
                    <h3 class="text-sm sm:text-base font-black text-slate-900 leading-tight">Riwayat Part Terbaru di Master Stok</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Part yang baru ditambahkan ke database (status awal Tersedia)</p>
                  </div>
                </div>

                <!-- Filter Part Type Pills -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl space-x-1 self-start sm:self-auto">
                  <button type="button" @click="loadRecentlyAddedParts('')"
                    :class="recentAddedFilter === '' ? 'bg-white text-slate-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                    Semua
                  </button>
                  <button type="button" @click="loadRecentlyAddedParts('1')"
                    :class="recentAddedFilter === '1' ? 'bg-white text-blue-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                    Part 1
                  </button>
                  <button type="button" @click="loadRecentlyAddedParts('12')"
                    :class="recentAddedFilter === '12' ? 'bg-white text-purple-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                    Part 12
                  </button>
                  <button type="button" @click="loadRecentlyAddedParts('15')"
                    :class="recentAddedFilter === '15' ? 'bg-white text-emerald-900 font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 font-medium'"
                    class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                    Part 15
                  </button>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="recentAddedLoading" class="py-12 text-center text-slate-400 space-y-2">
                <i class="fa-solid fa-circle-notch fa-spin text-2xl text-red-600"></i>
                <p class="text-xs font-medium">Memuat riwayat part terbaru...</p>
              </div>

              <!-- Empty State -->
              <div v-else-if="!recentAddedParts || recentAddedParts.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                <i class="fa-solid fa-boxes-packing text-3xl text-slate-300"></i>
                <p class="text-xs font-medium">Belum ada part yang ditambahkan atau filter tidak menemukan hasil.</p>
              </div>

              <!-- History Table -->
              <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                  <thead>
                    <tr class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] tracking-wider">
                      <th class="py-2.5 px-3">Tipe</th>
                      <th class="py-2.5 px-3">Serial Number</th>
                      <th class="py-2.5 px-3">CS</th>
                      <th class="py-2.5 px-3">Status</th>
                      <th class="py-2.5 px-3">Ditambahkan Oleh</th>
                      <th class="py-2.5 px-3">Keterangan</th>
                      <th class="py-2.5 px-3 text-right">Waktu</th>
                      <th class="py-2.5 px-3 text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="part in recentAddedParts" :key="part.id" class="hover:bg-slate-50/80 transition">
                      
                      <!-- Tipe Badge -->
                      <td class="py-2.5 px-3 whitespace-nowrap">
                        <span v-if="part.part_type === '1'" class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                          Part 1
                        </span>
                        <span v-else-if="part.part_type === '12'" class="px-2 py-0.5 rounded text-[10px] font-black bg-purple-100 text-purple-800 border border-purple-200">
                          Part 12
                        </span>
                        <span v-else class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                          Part 15
                        </span>
                      </td>

                      <!-- SN -->
                      <td class="py-2.5 px-3 whitespace-nowrap">
                        <span class="font-mono font-bold text-slate-900">{{ part.sn }}</span>
                      </td>

                      <!-- CS -->
                      <td class="py-2.5 px-3 whitespace-nowrap">
                        <span v-if="part.cs" class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded text-[11px]">{{ part.cs }}</span>
                        <span v-else class="text-slate-300">-</span>
                      </td>

                      <!-- Status -->
                      <td class="py-2.5 px-3 whitespace-nowrap">
                        <span v-if="part.status === 'Tersedia'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                          <i class="fa-solid fa-check text-[9px] mr-1"></i> Tersedia
                        </span>
                        <span v-else-if="part.status === 'Done' || part.status === 'Good'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                          {{ part.status }}
                        </span>
                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                          {{ part.status }}
                        </span>
                      </td>

                      <!-- Ditambahkan Oleh -->
                      <td class="py-2.5 px-3 whitespace-nowrap">
                        <span v-if="part.actor_username" class="font-semibold text-slate-800 flex items-center space-x-1">
                          <i class="fa-solid fa-user text-[10px] text-slate-400"></i>
                          <span>{{ part.actor_username }}</span>
                        </span>
                        <span v-else class="text-slate-400 italic">Sistem / Seed</span>
                      </td>

                      <!-- Keterangan / Reason -->
                      <td class="py-2.5 px-3 max-w-[180px] truncate text-slate-600" :title="part.reason || ''">
                        {{ part.reason || '-' }}
                      </td>

                      <!-- Waktu -->
                      <td class="py-2.5 px-3 whitespace-nowrap text-right font-mono text-[11px] text-slate-500">
                        {{ part.created_at || '-' }}
                      </td>

                      <!-- Aksi: Icon Sampah -->
                      <td class="py-2.5 px-3 whitespace-nowrap text-center">
                        <button type="button" @click="deleteRecentlyAddedPart(part)"
                          :disabled="part.status !== 'Tersedia'"
                          :title="part.status === 'Tersedia' ? 'Hapus Part' : 'Part sudah digunakan/dialokasikan, tidak dapat dihapus'"
                          :class="part.status === 'Tersedia' ? 'bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 border-red-200 active:scale-95 cursor-pointer shadow-xs' : 'bg-slate-100 text-slate-300 border-slate-200 cursor-not-allowed'"
                          class="w-7 h-7 inline-flex items-center justify-center rounded-lg border transition-all">
                          <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                      </td>

                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </div>

        </div>

      </div>
</section>


@endverbatim
