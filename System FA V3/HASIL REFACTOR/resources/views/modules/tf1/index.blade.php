@verbatim
      <!-- ================= TAB 1.5: TESTING OPERATOR ASSEMBLY (TAHAP 1) ================= -->
      <section v-if="currentTab === 'assy_testing'" class="space-y-4 sm:space-y-6">

        <!-- ================= VIEW 1: DAFTAR SLOT TRAY (TAMPIL KETIKA TIDAK SEDANG TESTING) ================= -->
        <div v-if="!showAssyTestModal" class="space-y-4 sm:space-y-6">

          <!-- Top Header & Filter Control Bar (Same as Perakitan, without Selesai Sesi & Jeda) -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-3">
              <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Testing Operator Assembly</h2>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <div v-if="currentTray"
                  class="bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl flex items-center gap-2">
                  <span class="text-[10px] font-bold text-slate-400 uppercase">Total Set Uji:</span>
                  <span class="text-xs font-black text-indigo-700 font-mono">{{ testedGoodSlots.length }} Set</span>
                </div>
              </div>
            </div>

            <!-- Tray Selection & Operator Filter Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
              <!-- Filter Operator (If Admin / Gudang / QC) -->
              <div v-if="['admin', 'super admin', 'gudang', 'qc'].includes(String(userRole || '').toLowerCase().trim()) || String(userRole || '').toLowerCase().includes('admin')">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-user-gear mr-1"></i> Filter operator:
                </label>
                <select v-model="selectedOperatorFilter" @change="onOperatorFilterChange"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 font-bold focus:outline-none focus:border-indigo-600 min-h-[38px] cursor-pointer">
                  <option value="">Semua operator assembly</option>
                  <option v-for="op in operatorUsersList" :key="op.username" :value="op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
              </div>

              <!-- Select Tray Dropdown -->
              <div
                :class="(['admin', 'super admin', 'gudang', 'qc'].includes(String(userRole || '').toLowerCase().trim()) || String(userRole || '').toLowerCase().includes('admin')) ? 'col-span-1 sm:col-span-2' : 'col-span-3'"
                class="relative" ref="trayDropdownRef">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-boxes-stacked mr-1"></i> Pilih nomor tray:
                </label>

                <div @click="toggleTrayDropdown"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 flex items-center justify-between cursor-pointer min-h-[38px] hover:border-indigo-500 transition">
                  <span v-if="selectedTrayCode" class="font-mono font-black text-indigo-950 flex items-center gap-1.5">
                    <i class="fa-solid fa-box text-indigo-600"></i>
                    {{ isPUTray(selectedTrayCode) ? getPUDocNumber(selectedTrayCode) : selectedTrayCode }} - {{
                    currentTray ? (currentTray.assigned_operator ?
                    getOperatorFullName(currentTray.assigned_operator) : 'Belum Ditugaskan') : '' }}
                  </span>
                  <span v-else class="text-slate-400 font-normal">Pilih tray perakitan</span>
                  <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform"
                    :class="{ 'rotate-180': isTrayDropdownOpen }"></i>
                </div>

                <!-- Searchable Dropdown Panel -->
                <div v-if="isTrayDropdownOpen"
                  class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 p-2 space-y-2 max-h-64 overflow-y-auto">
                  <input type="text" v-model="traySearchText" placeholder="Cari kode tray / operator..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-indigo-600">
                  <div class="divide-y divide-slate-100">
                    <div v-for="t in filteredTraysList" :key="t.tray_code" @click="selectTrayFromDropdown(t.tray_code)"
                      class="p-2 hover:bg-indigo-50 rounded-xl cursor-pointer text-xs flex items-center justify-between transition">
                      <div>
                        <strong class="font-mono text-slate-900 font-black block">{{ isPUTray(t) ?
                          getPUDocNumber(t.tray_code) : t.tray_code }}</strong>
                        <span class="text-[10px] text-slate-500">{{ getOperatorFullName(t.assigned_operator) }} &bull;
                          {{ getTestingSetProgress(t) }} Set Selesai</span>
                      </div>
                      <span v-if="t.tray_code === selectedTrayCode" class="text-indigo-600 font-bold text-xs"><i
                          class="fa-solid fa-check"></i></span>
                    </div>
                    <div v-if="filteredTraysList.length === 0" class="p-3 text-center text-slate-400 text-xs">
                      Tidak ada tray yang sesuai.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Summary KPI Cards TF 1 -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Lulus TF 1 / Good -->
            <div
              class="bg-gradient-to-br from-emerald-50 to-emerald-100/40 p-4 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Lulus Uji TF 1
                  (Good)</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{ tf1OverallCounts.passed
                  }}</span>
                <span class="text-[10px] text-emerald-700 block mt-0.5">Lolos tes fungsi tahap 1</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-circle-check"></i>
              </div>
            </div>

            <!-- Hold TF 1 -->
            <div
              class="bg-gradient-to-br from-amber-50 to-amber-100/40 p-4 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Hold TF 1</span>
                <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{ tf1OverallCounts.hold }}</span>
                <span class="text-[10px] text-amber-700 block mt-0.5">Perlu perbaikan fungsi</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-hand"></i>
              </div>
            </div>

            <!-- Menunggu Uji TF 1 -->
            <div
              class="bg-gradient-to-br from-blue-50 to-blue-100/40 p-4 rounded-2xl border border-blue-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-800 block">Menunggu Uji TF
                  1</span>
                <span class="text-xl sm:text-2xl font-black text-blue-950 font-mono">{{ tf1OverallCounts.pending
                  }}</span>
                <span class="text-[10px] text-blue-700 block mt-0.5">Antrean tes fungsi</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-clock"></i>
              </div>
            </div>

            <!-- Total Diuji TF 1 -->
            <div
              class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Selesai TF
                  1</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{ tf1OverallCounts.total
                  }}</span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Total unit melewati TF 1</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-microchip"></i>
              </div>
            </div>
          </div>

          <!-- Testing Content / Card Grid Layout (HANYA RAKITAN BERSTATUS GOOD) -->
          <div v-if="currentTray" class="space-y-4">
            <!-- Summary Info Bar -->
            <div
              class="bg-indigo-50/70 border border-indigo-200 rounded-2xl p-3 flex flex-wrap items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-indigo-600 text-sm"></i>
                <span class="text-indigo-950 font-medium">
                  Menampilkan <strong>{{ testedGoodSlots.length }}</strong> rakitan pada
                  Tray <strong class="font-mono">{{ currentTray.tray_code }}</strong>.
                </span>
              </div>
              <div class="flex items-center gap-2 font-mono text-[11px]">
                <span class="bg-white border border-emerald-300 text-emerald-800 px-2 py-0.5 rounded-lg font-bold">
                  Lolos Uji: {{ countTestedGoodInTray }}
                </span>
                <span class="bg-white border border-amber-300 text-amber-800 px-2 py-0.5 rounded-lg font-bold">
                  Hold Uji: {{ countTestedHoldInTray }}
                </span>
                <span v-if="countTestedRUInTray > 0"
                  class="bg-white border border-indigo-300 text-indigo-800 px-2 py-0.5 rounded-lg font-bold">
                  Status RU: {{ countTestedRUInTray }}
                </span>
                <span class="bg-white border border-blue-300 text-blue-800 px-2 py-0.5 rounded-lg font-bold">
                  Belum Diuji: {{ countUntestedInTray }}
                </span>
              </div>
            </div>

            <!-- Cards Grid -->
            <div v-if="testedGoodSlots.length > 0"
              class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
              <div v-for="(slot, idx) in testedGoodSlots" :key="slot.set_number || idx"
                class="bg-white border rounded-2xl p-3.5 shadow-sm transition-all duration-200 flex flex-col justify-between relative"
                :class="(isReassemblySet(slot) || slot.assy_test_status === 'RU' || slot.qc_status === 'RU' || slot.status === 'Dirakit Ulang' || slot.verified_status === 'WAITING_REASSEMBLY') ? 'border-indigo-300 ring-1 ring-indigo-100 bg-indigo-50/20' : ((slot.qc_status === 'HOLD_ASSY' || slot.assy_test_status === 'Hold') ? 'border-amber-300 ring-1 ring-amber-100 bg-amber-50/20' : ((slot.qc_status === 'REWORK_ASSY' || slot.assy_test_status === 'Rework') ? 'border-purple-300 ring-1 ring-purple-100 bg-purple-50/20' : ((slot.assy_doc_number || slot.assy_test_status === 'Good' || slot.qc_status === 'PENDING_QC' || slot.qc_status === 'GOOD' || slot.qc_status === 'HOLD_QC') ? 'border-emerald-300 ring-1 ring-emerald-100 bg-emerald-50/20' : 'border-indigo-200 ring-1 ring-indigo-100 hover:border-indigo-400')))">

                <!-- Slot Card Top Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-2.5 min-h-[32px] gap-2">
                  <div class="flex items-center space-x-1.5 min-w-0 flex-1">
                    <span
                      class="bg-slate-900 text-white text-[11px] font-black px-2 py-0.5 rounded-lg font-mono shrink-0">
                      #{{ slot.set_number }}
                    </span>
                    <span class="text-[10px] font-bold text-slate-500 font-mono truncate">{{ currentTray.tray_code
                      }}</span>
                  </div>

                  <!-- JIKA ADA DRAFT PROGRES: Tampilkan Progress Bar Persentase di pojok kanan sejajar nama dokumen/tray -->
                  <div v-if="!isSlotTested(slot) && getSlotDraftProgress(slot)"
                    class="shrink-0 flex items-center gap-1.5 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-lg shadow-2xs"
                    :title="'Progres pengerjaan: ' + getSlotDraftProgress(slot).step + ' dari ' + (getSlotDraftProgress(slot).totalSteps || assyTotalSteps) + ' tahap'">
                    <div class="w-12 sm:w-14 bg-slate-200 h-1.5 rounded-full overflow-hidden">
                      <div class="bg-indigo-600 h-full rounded-full transition-all duration-300"
                        :style="{ width: Math.round(((getSlotDraftProgress(slot).step) / (getSlotDraftProgress(slot).totalSteps || assyTotalSteps)) * 100) + '%' }">
                      </div>
                    </div>
                    <span class="font-mono text-[9.5px] font-black text-indigo-700">
                      {{ Math.round(((getSlotDraftProgress(slot).step) / (getSlotDraftProgress(slot).totalSteps ||
                      assyTotalSteps)) * 100) }}%
                    </span>
                  </div>

                  <!-- Status Uji Badge: Belum Diuji jika belum dites -->
                  <div v-else-if="!isSlotTested(slot)" class="shrink-0 flex items-center gap-1 flex-wrap justify-end">
                    <span
                      class="bg-indigo-50 text-indigo-700 border border-indigo-200 font-bold text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 shadow-2xs">
                      <i class="fa-solid fa-clock text-[8.5px]"></i> Belum Diuji
                    </span>
                  </div>

                  <!-- Status Uji Badge (Hanya tampil jika SUDAH diuji) -->
                  <div v-else class="shrink-0 flex items-center gap-1 flex-wrap justify-end">
                    <!-- 0. RU (Perakitan Ulang) -->
                    <span
                      v-if="isReassemblySet(slot) || slot.assy_test_status === 'RU' || slot.qc_status === 'RU' || slot.status === 'Dirakit Ulang' || slot.verified_status === 'WAITING_REASSEMBLY'"
                      class="bg-indigo-100 text-indigo-900 border border-indigo-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                      title="Set dalam proses Perakitan Ulang (RU)">
                      <i class="fa-solid fa-arrows-rotate text-indigo-600 text-[8.5px]"></i> RU
                    </span>
                    <!-- 1. Hold Test -->
                    <span v-else-if="slot.qc_status === 'HOLD_ASSY' || slot.assy_test_status === 'Hold'"
                      class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs">
                      <i class="fa-solid fa-hand text-amber-600 text-[8.5px]"></i> Hold Test
                    </span>
                    <!-- 2. Rework Test -->
                    <span v-else-if="slot.qc_status === 'REWORK_ASSY' || slot.assy_test_status === 'Rework'"
                      class="bg-purple-100 text-purple-900 border border-purple-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs">
                      <i class="fa-solid fa-rotate-left text-purple-600 text-[8.5px]"></i> Rework Test
                    </span>
                    <!-- 3. Pasca Rework disetujui Good (Rework -> Good) -->
                    <template
                      v-else-if="(slot.assy_doc_number || slot.assy_test_status === 'Good') && (slot.lifecycle_path === 'HOLD_REWORK_APPROVED' || slot.lifecycle_path === 'HOLD_QC_REWORK_APPROVED' || slot.verified_status === 'REWORK_GUDANG' || slot.verified_status === 'REWORK_QC_GUDANG')">
                      <span
                        class="bg-purple-100 text-purple-900 border border-purple-300 font-black text-[9px] uppercase px-1.5 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Riwayat: Rework">
                        <i class="fa-solid fa-rotate-left text-purple-600 text-[8.5px]"></i> Rework
                      </span>
                      <span
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[9px] uppercase px-1.5 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Status Saat Ini: Good">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8.5px]"></i> Good
                      </span>
                    </template>
                    <!-- 4. Pasca Hold disetujui Good (Hold -> Good) -->
                    <template
                      v-else-if="(slot.assy_doc_number || slot.assy_test_status === 'Good') && (slot.lifecycle_path === 'HOLD_APPROVED' || slot.lifecycle_path === 'HOLD_QC_APPROVED' || slot.verified_status === 'APPROVED' || slot.verified_status === 'APPROVED_QC')">
                      <span
                        class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[9px] uppercase px-1.5 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Riwayat: Hold">
                        <i class="fa-solid fa-hand text-amber-600 text-[8.5px]"></i> Hold
                      </span>
                      <span
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[9px] uppercase px-1.5 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Status Saat Ini: Good">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8.5px]"></i> Good
                      </span>
                    </template>
                    <!-- 5. Fresh Good (Lolos Uji TF1 / Ke QC) -->
                    <template v-else-if="slot.assy_doc_number || slot.assy_test_status === 'Good'">
                      <span v-if="slot.qc_status === 'GOOD'"
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Lolos Uji Fungsional & Lolos QC">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8.5px]"></i> Lolos QC
                      </span>
                      <span
                        v-else-if="slot.qc_status === 'PENDING_QC' || slot.qc_status === 'PENDING' || !slot.qc_status"
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs"
                        title="Lolos Uji TF1, Siap / Sedang Masuk Antrean QC">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8.5px]"></i> Lolos Uji (Ke QC)
                      </span>
                      <span v-else
                        class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[9px] uppercase px-2 py-0.5 rounded-lg inline-flex items-center gap-1 whitespace-nowrap shadow-xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-[8.5px]"></i> Good
                      </span>
                    </template>
                  </div>
                </div>

                <!-- Document Number Badge if generated -->
                <div v-if="slot.assy_doc_number"
                  class="mb-2 p-1.5 bg-indigo-50 border border-indigo-200 rounded-lg text-center font-mono font-black text-[10.5px] text-indigo-900">
                  Doc: {{ slot.assy_doc_number }}
                </div>

                <!-- 3 Component Serial Breakdown (SN P9 Part 15, PN Part 1, PN Part 12) -->
                <div class="space-y-1.5 text-xs font-mono mb-3">
                  <div
                    class="p-1.5 rounded-lg border bg-indigo-50/40 border-indigo-200 flex justify-between items-center">
                    <span class="text-[10px] font-sans font-bold text-indigo-900">P9 Part 15:</span>
                    <strong class="text-indigo-950 font-black">{{ slot.part15_sn || '-' }}</strong>
                  </div>
                  <div class="p-1.5 rounded-lg border bg-slate-50 border-slate-200 flex justify-between items-center">
                    <span class="text-[10px] font-sans font-bold text-slate-600">P9 Part 1:</span>
                    <strong class="text-slate-800 font-bold">{{ slot.part1_sn || '-' }}</strong>
                  </div>
                  <div class="p-1.5 rounded-lg border bg-slate-50 border-slate-200 flex justify-between items-center">
                    <span class="text-[10px] font-sans font-bold text-slate-600">P9 Part 12:</span>
                    <strong class="text-slate-800 font-bold">{{ slot.part12_sn || '-' }}</strong>
                  </div>

                  <!-- Info Operator & Waktu -->
                  <div class="pt-1 text-[10px] text-slate-500 font-sans space-y-0.5 border-t border-slate-100">
                    <div class="flex justify-between">
                      <span>Operator:</span>
                      <strong class="text-slate-800">{{ getOperatorFullName(slot.pic_assembly ||
                        currentTray.assigned_operator) }}</strong>
                    </div>
                    <div v-if="slot.assy_tested_at" class="flex justify-between">
                      <span>Waktu Uji:</span>
                      <span class="text-slate-600">{{ formatDateTime(slot.assy_tested_at, slot.started_at ||
                        slot.finished_at) }}</span>
                    </div>
                  </div>

                  <!-- Info Perakitan Ulang (RU) jika masuk status RU -->
                  <div
                    v-if="isReassemblySet(slot) || slot.assy_test_status === 'RU' || slot.qc_status === 'RU' || slot.status === 'Dirakit Ulang' || slot.verified_status === 'WAITING_REASSEMBLY'"
                    class="p-1.5 border rounded-lg text-[10px] leading-tight space-y-0.5 bg-indigo-50 border-indigo-200 text-indigo-900">
                    <span class="font-bold block uppercase text-[8.5px] text-indigo-800 flex items-center gap-1">
                      <i class="fa-solid fa-arrows-rotate text-indigo-600"></i> Status: Perakitan Ulang (RU)
                    </span>
                    <p v-if="slot.assy_failure_reasons || slot.reason" class="italic text-[9.5px] text-slate-600">
                      Riwayat Kendala: {{ slot.assy_failure_reasons || slot.reason }}</p>
                  </div>
                  <!-- Alasan Gagal jika masih Hold / Rework -->
                  <div
                    v-else-if="(slot.qc_status === 'HOLD_ASSY' || slot.assy_test_status === 'Hold') && (slot.assy_failure_reasons || slot.reason)"
                    class="p-1.5 border rounded-lg text-[10px] leading-tight space-y-0.5 bg-amber-50 border-amber-200 text-amber-900">
                    <span class="font-bold block uppercase text-[8.5px] text-amber-800">
                      Alasan Gagal:
                    </span>
                    <p class="italic">{{ slot.assy_failure_reasons || slot.reason }}</p>
                  </div>
                  <!-- Notifikasi Disetujui Admin jika Good Pasca Hold/Rework -->
                  <div
                    v-else-if="(slot.assy_doc_number || slot.assy_test_status === 'Good') && (slot.lifecycle_path === 'HOLD_APPROVED' || slot.lifecycle_path === 'HOLD_REWORK_APPROVED' || slot.verified_status === 'APPROVED')"
                    class="p-1.5 bg-emerald-50/80 border border-emerald-200 rounded-lg text-[10px] text-emerald-900 leading-tight flex items-center justify-between">
                    <span class="font-bold text-[9.5px] text-emerald-800 flex items-center gap-1">
                      <i class="fa-solid fa-check-double text-emerald-600"></i>
                      <span
                        v-if="slot.lifecycle_path === 'HOLD_REWORK_APPROVED' || slot.verified_status === 'REWORK_GUDANG'">Disetujui
                        Good (Pasca Rework)</span>
                      <span v-else>Disetujui Good (Pasca Hold)</span>
                    </span>
                    <span
                      class="text-[9px] font-mono text-emerald-700 font-black bg-white px-1.5 py-0.5 rounded border border-emerald-300">ACC</span>
                  </div>
                </div>

                <!-- Card Action Buttons -->
                <!-- 1. JIKA BELUM DIUJI -> TOMBOL MULAI TEST (ATAU BADGE MONITORING UNTUK GUDANG) -->
                <div v-if="!isSlotTested(slot)" class="mt-auto">
                  <div v-if="userRole === 'gudang' || userRole === 'qc'"
                    class="w-full bg-slate-100 border border-slate-200 text-slate-600 font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center space-x-1.5 min-h-[38px] select-none">
                    <i class="fa-solid fa-clock text-amber-500"></i>
                    <span>{{ userRole === 'qc' ? 'Monitoring QC (Read Only)' : 'Menunggu Uji Perakitan' }}</span>
                  </div>
                  <div
                    v-else-if="(slot.status || slot.assembly_status) && (slot.status || slot.assembly_status) !== 'Good'"
                    class="w-full bg-amber-50 border border-amber-300 text-amber-800 font-bold py-2.5 px-3 rounded-xl text-xs flex items-center justify-center space-x-1.5 min-h-[38px] select-none"
                    title="Unit belum berstatus Good di perakitan sehingga belum bisa diuji">
                    <i class="fa-solid fa-lock text-amber-600"></i>
                    <span>Perakitan Belum Good ({{ slot.status || slot.assembly_status }})</span>
                  </div>
                  <button v-else type="button" @click="openAssyTestModal(slot, idx)"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-3 rounded-xl text-xs shadow-md shadow-indigo-600/25 transition active:scale-95 flex items-center justify-center space-x-1.5 cursor-pointer min-h-[38px]">
                    <i :class="getSlotDraftProgress(slot) ? 'fa-solid fa-play' : 'fa-solid fa-vial-circle-check'"></i>
                    <span>{{ getSlotDraftProgress(slot) ? 'Lanjutkan Test' : 'Mulai Test' }}</span>
                  </button>
                </div>

                <!-- 2. JIKA SUDAH DIUJI -> TERKUNCI DARI PENGUJIAN ULANG + TOMBOL DETAIL & CETAK DOKUMEN -->
                <!-- 2. JIKA SUDAH DIUJI -> TERKUNCI DARI PENGUJIAN ULANG + TOMBOL DETAIL -->
                <div v-else class="mt-auto space-y-1.5">
                  <div class="flex items-center gap-1.5">
                    <button type="button" @click="openDetailModal(slot, idx)"
                      class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 rounded-xl text-[11px] border border-slate-300 transition flex items-center justify-center space-x-1 min-h-[34px] cursor-pointer">
                      <i class="fa-solid fa-eye text-slate-500"></i>
                      <span>Detail</span>
                    </button>
                    <span
                      v-if="isReassemblySet(slot) || slot.assy_test_status === 'RU' || slot.qc_status === 'RU' || slot.status === 'Dirakit Ulang' || slot.verified_status === 'WAITING_REASSEMBLY'"
                      class="flex-1 text-[10px] text-indigo-800 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center justify-center font-bold px-1 text-center shadow-2xs whitespace-nowrap select-none min-h-[34px]">
                      <i class="fa-solid fa-arrows-rotate text-indigo-600 mr-1 text-[9px]"></i> Status RU
                    </span>
                    <span
                      v-else-if="slot.assy_test_status === 'Good' || (slot.assy_doc_number && slot.qc_status !== 'HOLD_ASSY' && slot.assy_test_status !== 'Hold')"
                      class="flex-1 text-[10px] text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-center font-bold px-1 text-center shadow-2xs whitespace-nowrap select-none min-h-[34px]"
                      :title="slot.qc_status === 'GOOD' ? 'Unit telah lolos QC' : 'Unit telah lolos uji TF1 dan masuk ke antrean QC'">
                      <i class="fa-solid fa-check text-emerald-600 mr-1 text-[9px]"></i>
                      <span>{{ slot.qc_status === 'GOOD' ? 'Lolos QC' : 'Lolos Uji (QC)' }}</span>
                    </span>
                    <span v-else-if="slot.qc_status === 'REWORK_ASSY' || slot.assy_test_status === 'Rework'"
                      class="flex-1 text-[10px] text-purple-800 bg-purple-50 border border-purple-200 rounded-xl flex items-center justify-center font-bold px-1 text-center shadow-2xs whitespace-nowrap select-none min-h-[34px]">
                      <i class="fa-solid fa-rotate-left text-purple-600 mr-1 text-[9px]"></i> Rework Test
                    </span>
                    <span v-else-if="slot.qc_status === 'HOLD_ASSY' || slot.assy_test_status === 'Hold'"
                      class="flex-1 text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-center font-bold px-1 text-center shadow-2xs whitespace-nowrap select-none min-h-[34px]">
                      <i class="fa-solid fa-hand text-amber-600 mr-1 text-[9px]"></i> Hold Test
                    </span>
                    <span v-else
                      class="flex-1 text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-center font-bold px-1 text-center shadow-2xs whitespace-nowrap select-none min-h-[34px]">
                      <i class="fa-solid fa-hand text-amber-600 mr-1 text-[9px]"></i> Hold Test
                    </span>
                  </div>
                </div>

              </div>
            </div>

            <!-- Empty Good Slots placeholder -->
            <div v-else
              class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-500 shadow-sm">
              <i class="fa-solid fa-vial-circle-check text-3xl sm:text-4xl mb-3 text-slate-300"></i>
              <p class="text-sm sm:text-base font-bold text-slate-800 mb-1">Tidak Ada Rakitan Siap Uji</p>
              <p class="text-xs text-slate-500">Belum ada rakitan dengan status perakitan 'Good' pada Tray ini yang siap
                diuji fungsional.</p>
            </div>

          </div>

          <!-- No Tray Selected Placeholder -->
          <div v-else
            class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-500 shadow-sm">
            <i class="fa-solid fa-box-archive text-3xl sm:text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm sm:text-base font-bold text-slate-800 mb-1">Belum ada Tray yang dipilih</p>
            <p class="text-xs text-slate-500">Pilih Nomor Dokumen Tray di atas untuk mulai melakukan pengujian operator
              assembly.</p>
          </div>

        </div>

        <!-- ================= VIEW 2: HALAMAN PENUH UJI FUNGSIONAL OPERATOR ASSEMBLY (BUKA PAGE BARU) ================= -->
        <div v-else class="space-y-4 sm:space-y-6">

          <!-- Top Navigation Header -->
          <div
            class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
              <button type="button" @click="handleAssyBackNavigation"
                class="bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-3.5 rounded-xl text-xs border border-slate-300 flex items-center gap-2 shadow-xs active:scale-95 transition cursor-pointer">
                <i class="fa-solid fa-arrow-left"></i>
                <span v-if="isSingleRetestMode">Batal</span>
                <span v-else>Kembali</span>
              </button>
              <!-- Button Akhiri Sesi (Muncul jika user pernah menolak pop-up pertama Not Good) -->
              <button v-if="showEarlyEndButton && !isSingleRetestMode" type="button"
                @click="showSecondNGPromptModal = true"
                class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold py-2 px-3.5 rounded-xl text-xs flex items-center gap-2 shadow-xs active:scale-95 transition cursor-pointer animate-fadeIn">
                <i class="fa-solid fa-circle-stop text-rose-600"></i>
                <span>Akhiri Sesi</span>
              </button>
              <div class="flex items-center gap-2">
                <span v-if="isSingleRetestMode"
                  class="bg-purple-100 text-purple-800 text-xs font-bold px-2.5 py-1 rounded-lg">
                  Uji ulang
                </span>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                  {{ isSingleRetestMode ? 'Pengujian ulang #' + singleRetestTestNum : 'Uji fungsional assembly' }}
                </h3>
              </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-center">
              <template v-if="isSingleRetestMode">
                <span
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-50 text-purple-900 border border-purple-200 flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-arrows-rotate text-purple-600"></i>
                  <span>Fokus uji #{{ singleRetestTestNum }}</span>
                </span>
              </template>
              <template v-else>
                <span v-if="assyTestStep < assyTotalSteps"
                  class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                  Uji: <strong class="text-indigo-700 font-bold">{{ assyTestStep + 1 }}</strong> / {{ assyTotalSteps }}
                </span>
                <span v-else-if="assyTestCalculatedFailures.length === 0"
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-circle-check text-emerald-600"></i>
                  <span>Semua lolos uji</span>
                </span>
                <span v-else
                  class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                  <span>{{ assyTestCalculatedFailures.length }} parameter gagal (Hold)</span>
                </span>
              </template>
            </div>
          </div>

          <!-- Identitas Part & Unit Traceability Card -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400">Tray &amp; Set:</span>
                <span
                  class="font-mono font-black text-slate-900 bg-slate-100 border border-slate-300 px-2.5 py-0.5 rounded-lg text-xs">
                  {{ assyTestForm.tray_code }}
                </span>
                <span
                  class="font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-lg text-xs font-mono">
                  Set #{{ assyTestForm.set_number }}
                </span>
              </div>
              <div class="text-xs text-slate-500 font-medium">
                Operator: <strong class="text-slate-800">{{ currentTray ?
                  (getOperatorFullName(currentTray.assigned_operator) || currentTray.assigned_operator) : '-'
                  }}</strong>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs font-mono">
              <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                <span class="text-[10px] font-sans font-bold text-slate-400 block">SN Part 15 (Frame):</span>
                <strong class="text-indigo-950 font-black text-sm block mt-0.5">{{ assyTestForm.part15_sn || '-'
                  }}</strong>
              </div>
              <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                <span class="text-[10px] font-sans font-bold text-slate-400 block">PN Part 1 (Upper):</span>
                <strong class="text-slate-800 font-bold text-xs sm:text-sm block mt-0.5">{{ assyTestForm.part1_sn || '-'
                  }}</strong>
              </div>
              <div class="bg-slate-50 border border-slate-200 p-3 rounded-xl">
                <span class="text-[10px] font-sans font-bold text-slate-400 block">PN Part 12 (Lower):</span>
                <strong class="text-slate-800 font-bold text-xs sm:text-sm block mt-0.5">{{ assyTestForm.part12_sn ||
                  '-' }}</strong>
              </div>
            </div>

            <!-- Riwayat Pergantian Part / Perubahan Status -->
            <div
              v-if="assyTestForm.rework_reason || (assyTestForm.reason && assyTestForm.reason.includes('Ganti Part'))"
              class="bg-blue-50 border border-blue-200 p-3 rounded-xl space-y-2 text-xs mt-3">
              <div class="text-[10px] font-black uppercase tracking-wider text-blue-900 flex items-center gap-1.5">
                <i class="fa-solid fa-wrench text-blue-600"></i>
                <span>Riwayat Pergantian Part:</span>
              </div>
              <div v-if="getParsedReplacementHistory(assyTestForm.rework_reason || assyTestForm.reason).length > 0"
                class="flex flex-wrap gap-2">
                <div v-for="r in getParsedReplacementHistory(assyTestForm.rework_reason || assyTestForm.reason)"
                  :key="r.partType"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-blue-200 rounded-lg text-xs shadow-2xs">
                  <span class="font-bold text-blue-800 text-[10.5px] uppercase">Part {{ r.partType }}:</span>
                  <span class="font-mono line-through text-rose-500 font-bold">{{ r.oldSN }}</span>
                  <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-0.5"></i>
                  <span class="font-mono text-emerald-600 font-black">{{ r.newSN }}</span>
                </div>
              </div>
              <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                {{ assyTestForm.rework_reason || assyTestForm.reason }}
              </div>
            </div>
          </div>

          <!-- TAMPILAN STEP-BY-STEP PENGUJIAN (TETAP SATU PER SATU SEPERTI AWAL) -->
          <!-- assyTestStep < 15 -->
          <div v-if="isSingleRetestMode || assyTestStep < assyTotalSteps"
            class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-7 shadow-sm space-y-6">
            <!-- Progress Bar / Mode Retest Banner -->
            <div v-if="isSingleRetestMode"
              class="bg-purple-50 border border-purple-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-2xs">
              <div class="flex items-center gap-3">
                <div
                  class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                  <i class="fa-solid fa-arrows-rotate text-sm"></i>
                </div>
                <div>
                  <div class="font-bold text-purple-950 text-sm flex items-center gap-2">
                    <span>Uji ulang #{{ singleRetestTestNum }}</span>
                  </div>
                </div>
              </div>
              <button type="button" @click="cancelSingleRetest"
                class="px-3.5 py-2 rounded-xl bg-white hover:bg-purple-100 text-purple-800 font-bold border border-purple-300 text-xs transition cursor-pointer shadow-2xs shrink-0 flex items-center justify-center gap-1.5 active:scale-95">
                <i class="fa-solid fa-xmark text-xs"></i>
                <span>Batal</span>
              </button>
            </div>
            <div v-else class="space-y-2">
              <div class="flex items-center justify-between text-xs font-semibold">
                <span class="text-slate-700 flex items-center gap-2">
                  <i class="fa-solid fa-list-check text-indigo-600"></i>
                  <strong class="text-indigo-900 font-mono text-sm">Test {{ assyTestStep + 1 }} dari {{ assyTotalSteps
                    }}</strong>
                </span>
                <span class="font-mono font-bold text-indigo-700">{{ Math.round(((assyTestStep + 1) / assyTotalSteps) *
                  100) }}% Selesai</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200">
                <div class="bg-indigo-600 h-full rounded-full transition-all duration-300 shadow-xs"
                  :style="{ width: (((assyTestStep + 1) / assyTotalSteps) * 100) + '%' }"></div>
              </div>
            </div>

            <!-- Active Test Card Focus -->
            <div v-if="currentActiveChecklistItem"
              class="bg-white border-2 border-indigo-200/90 rounded-2xl p-5 sm:p-6 space-y-4 shadow-sm relative">
              <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3 flex-wrap">
                <div class="flex items-center gap-2.5 min-w-0">
                  <span
                    class="bg-slate-900 text-white font-mono font-bold text-xs sm:text-sm px-3 py-1 rounded-xl shadow-xs shrink-0">
                    Uji #{{ currentActiveChecklistItem.test_num }}
                  </span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <!-- Badge Kategori Pengujian -->
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs border border-slate-200 shadow-2xs">
                    <i class="fa-solid fa-layer-group text-slate-400 text-xs"></i>
                    <span>{{ currentActiveChecklistItem.category || getTestCategory(currentActiveChecklistItem.test_num)
                      }}</span>
                  </span>

                  <!-- Tombol Lihat Petunjuk -->
                  <button type="button" @click="openInstructionGuideModal(currentActiveChecklistItem)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-800 font-bold text-xs border border-indigo-200 transition shadow-2xs shrink-0 cursor-pointer group">
                    <i class="fa-solid fa-book-open text-xs text-indigo-600 group-hover:text-white transition"></i>
                    <span>Lihat petunjuk</span>
                    <span v-if="getTestImagesCount(currentActiveChecklistItem.test_num) > 0"
                      class="bg-indigo-600 group-hover:bg-white group-hover:text-indigo-900 text-white text-[9px] font-black px-1.5 py-0.2 rounded-full ml-0.5">
                      {{ getTestImagesCount(currentActiveChecklistItem.test_num) }}
                    </span>
                  </button>
                </div>
              </div>

              <!-- Deskripsi Instruksi Kerja & Output Detail -->
              <div class="space-y-2.5">
                <div
                  class="bg-slate-50 border border-slate-200/90 rounded-xl p-3.5 shadow-2xs space-y-2 text-xs sm:text-sm">
                  <div>
                    <span class="block text-[10px] font-bold text-slate-400">Instruksi:</span>
                    <p class="font-semibold text-slate-900 text-xs sm:text-sm mt-0.5 whitespace-pre-line">{{
                      currentActiveChecklistItem.name }}</p>
                  </div>
                  <div class="pt-2 border-t border-slate-200/70">
                    <span class="block text-[10px] font-bold text-slate-400">Kriteria kelayakan:</span>
                    <p class="text-slate-700 text-xs sm:text-sm mt-0.5 whitespace-pre-line">{{
                      currentActiveChecklistItem.description }}</p>
                  </div>
                </div>
              </div>

              <!-- Status Penilaian jika sudah dipilih -->
              <div
                v-if="currentActiveChecklistItem.status === 'GOOD' || currentActiveChecklistItem.status === 'NOT_GOOD'"
                class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <div>
                  <span v-if="currentActiveChecklistItem.status === 'GOOD'"
                    class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold px-2.5 py-1 rounded-xl inline-flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-check"></i> Good
                  </span>
                  <span v-else-if="currentActiveChecklistItem.status === 'NOT_GOOD'"
                    class="bg-red-100 text-red-800 border border-red-300 text-xs font-bold px-2.5 py-1 rounded-xl inline-flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-xmark"></i> Not Good
                  </span>
                </div>
              </div>
            </div>

            <!-- Decision Buttons & Actions -->
            <div v-if="isSingleRetestMode" class="space-y-4 pt-2">
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-600 flex items-center gap-1.5">
                  <i class="fa-solid fa-pen-to-square text-indigo-600"></i>
                  <span>Catatan retest (opsional):</span>
                </label>
                <input type="text" v-model="singleRetestNotes" placeholder="Keterangan hasil pengujian ulang..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-indigo-600">
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <button type="button" @click="setSingleRetestAndSubmit('GOOD')" :disabled="isSubmittingSingleRetest"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-6 rounded-2xl text-sm sm:text-base shadow-md transition active:scale-95 cursor-pointer min-h-[52px] flex items-center justify-center space-x-2.5">
                  <i class="fa-solid fa-circle-check text-lg"></i>
                  <span>Good</span>
                </button>
                <button type="button" @click="setSingleRetestAndSubmit('NOT_GOOD')" :disabled="isSubmittingSingleRetest"
                  class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-4 px-6 rounded-2xl text-sm sm:text-base shadow-md transition active:scale-95 cursor-pointer min-h-[52px] flex items-center justify-center space-x-2.5">
                  <i class="fa-solid fa-circle-xmark text-lg"></i>
                  <span>Not Good</span>
                </button>
              </div>

              <div class="flex items-center justify-start pt-2 border-t border-slate-100">
                <button type="button" @click="cancelSingleRetest"
                  class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer flex items-center gap-1.5">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>Batal</span>
                </button>
              </div>
            </div>

            <!-- JIKA MODE REGULAR BULK TESTING (1 s.d. 15 Step-by-Step) -->
            <div v-else class="space-y-3 pt-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <button type="button" @click="chooseAssyTestStatus('GOOD')"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3.5 px-6 rounded-2xl text-sm sm:text-base shadow-md shadow-emerald-600/25 flex items-center justify-center space-x-2.5 transition active:scale-95 cursor-pointer min-h-[50px]">
                  <i class="fa-solid fa-circle-check text-lg"></i>
                  <span>Good</span>
                </button>
                <button type="button" @click="chooseAssyTestStatus('NOT_GOOD')"
                  class="bg-rose-600 hover:bg-rose-700 text-white font-black py-3.5 px-6 rounded-2xl text-sm sm:text-base shadow-md shadow-rose-600/25 flex items-center justify-center space-x-2.5 transition active:scale-95 cursor-pointer min-h-[50px]">
                  <i class="fa-solid fa-circle-xmark text-lg"></i>
                  <span>Not Good</span>
                </button>
              </div>

              <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <button v-if="assyTestStep > 0" type="button" @click="prevAssyTestStep"
                  class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-2 min-h-[38px] cursor-pointer">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>Kembali ke Test {{ assyTestStep }}</span>
                </button>
                <div v-else></div>

                <template
                  v-if="currentActiveChecklistItem && (currentActiveChecklistItem.status === 'GOOD' || currentActiveChecklistItem.status === 'NOT_GOOD')">
                  <button v-if="assyTestStep < assyTotalSteps - 1" type="button" @click="nextAssyTestStep"
                    class="px-4 py-2.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold transition flex items-center gap-2 min-h-[38px] cursor-pointer">
                    <span>Selanjutnya ke Test {{ assyTestStep + 2 }}</span>
                    <i class="fa-solid fa-arrow-right"></i>
                  </button>
                  <button v-else-if="assyTestStep === assyTotalSteps - 1" type="button" @click="nextAssyTestStep"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-2 min-h-[38px] cursor-pointer shadow-sm">
                    <span>Selanjutnya ke Rekap Uji</span>
                    <i class="fa-solid fa-arrow-right"></i>
                  </button>
                </template>
              </div>
            </div>
          </div>

          <!-- ================= TABEL HASIL TESTING 15 TAHAP DUAL-KOLOM (NO-SCROLL) PASCA SELESAI TEST 15 ================= -->
          <div v-else class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
            <!-- Header Ringkasan Pasca 15 Uji -->
            <div class="p-4 rounded-2xl border flex items-center justify-between gap-4"
              :class="assyTestCalculatedFailures.length === 0 ? 'bg-emerald-50/80 border-emerald-200' : 'bg-amber-50/80 border-amber-200'">
              <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shadow-xs flex-shrink-0"
                  :class="assyTestCalculatedFailures.length === 0 ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white'">
                  <i
                    :class="assyTestCalculatedFailures.length === 0 ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                </div>
                <div class="min-w-0 flex-1">
                  <h4 class="font-bold text-sm sm:text-base leading-tight"
                    :class="assyTestCalculatedFailures.length === 0 ? 'text-emerald-950' : 'text-amber-950'">
                    {{ assyTestCalculatedFailures.length === 0 ? 'Pengujian selesai (Good)' : 'Pengujian selesai (Hold)'
                    }}
                  </h4>
                  <p class="text-xs font-medium mt-0.5"
                    :class="assyTestCalculatedFailures.length === 0 ? 'text-emerald-700' : 'text-amber-800'">
                    {{ assyTestCalculatedFailures.length === 0 ? 'Semua parameter uji lolos.' : 'Terdapat parameter uji tidak lolos.' }}
                  </p>
                </div>
              </div>
              <div class="flex-shrink-0">
                <span v-if="assyTestCalculatedFailures.length === 0"
                  class="px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shadow-xs whitespace-nowrap inline-block">
                  Good
                </span>
                <span v-else
                  class="px-4 py-1.5 rounded-xl text-xs font-bold bg-amber-600 text-white shadow-xs whitespace-nowrap inline-block">
                  Hold
                </span>
              </div>
            </div>

            <!-- Judul Tabel Hasil Pengujian Resmi -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-table-list text-indigo-600"></i>
                <h4 class="text-xs font-bold text-slate-800">
                  Hasil pengujian:
                </h4>
              </div>
            </div>

            <!-- SATU TABEL TUNGGAL HASIL 15 TAHAP UJI -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
              <table class="w-full text-left text-xs border-collapse">
                <thead
                  class="bg-slate-100 text-slate-700 font-bold text-[10px] tracking-wider border-b border-slate-200">
                  <tr>
                    <th class="py-2.5 px-3 text-center w-12 bg-slate-100 border-r border-slate-200">No</th>
                    <th class="py-2.5 px-4 bg-slate-100 border-r border-slate-200">Deskripsi</th>
                    <th class="py-2.5 px-3 text-center w-36 bg-slate-100">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                  <tr v-for="t in assyTestForm.checklist" :key="t.test_num"
                    @click="startSingleRetest(assyTestForm, t.test_num, 'view2_table')"
                    class="hover:bg-indigo-50/60 transition cursor-pointer group"
                    :class="t.status === 'GOOD' ? 'bg-white' : (t.status === 'NOT_GOOD' ? 'bg-red-50/25' : 'bg-slate-50/25')"
                    title="Klik untuk uji ulang poin ini">
                    <td
                      class="py-2.5 px-3 text-center font-mono font-bold text-slate-800 text-xs border-r border-slate-200 bg-slate-50/40 group-hover:bg-indigo-100/50">
                      <span class="inline-flex items-center gap-1">
                        {{ t.test_num }}
                        <i
                          class="fa-solid fa-rotate text-[9px] text-indigo-400 group-hover:text-indigo-700 transition"></i>
                      </span>
                    </td>
                    <td class="py-2.5 px-4 border-r border-slate-200">
                      <div
                        class="font-bold text-slate-800 text-xs leading-snug group-hover:text-indigo-900 transition whitespace-pre-line">
                        {{ t.name }}</div>
                      <div class="text-[10px] text-slate-500 mt-0.5 leading-relaxed whitespace-pre-line">{{
                        t.description }}</div>
                    </td>
                    <td class="py-2.5 px-3 text-center">
                      <div class="flex items-center justify-center">
                        <span v-if="t.status === 'GOOD'"
                          class="px-2.5 py-1 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center justify-center gap-1 shadow-2xs">
                          <i class="fa-solid fa-check text-[9px]"></i> Good
                        </span>
                        <span v-else-if="t.status === 'NOT_GOOD'"
                          class="px-2.5 py-1 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-300 inline-flex items-center justify-center gap-1 shadow-2xs">
                          <i class="fa-solid fa-xmark text-[9px]"></i> Not Good
                        </span>
                        <span v-else class="text-slate-400 text-xs italic font-medium">
                          -
                        </span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Catatan Input -->
            <div class="space-y-1.5 pt-1">
              <label class="block text-xs font-bold text-slate-600 mb-1">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Catatan (opsional):
              </label>
              <input type="text" v-model="assyTestForm.notes" placeholder="Catatan kendala jika ada..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-indigo-600">
            </div>

            <!-- Footer Action Buttons -->
            <div
              class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
              <button type="button" @click="assyTestStep = (assyTotalSteps - 1)"
                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center justify-center gap-2 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Tinjau Ulang</span>
              </button>

              <div class="flex items-center gap-2">
                <button type="button" @click="submitAssyTest" :disabled="isSubmittingAssyTest"
                  :class="assyTestCalculatedFailures.length > 0 ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25' : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/25'"
                  class="text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md transition flex items-center justify-center gap-2 active:scale-95 min-h-[40px] cursor-pointer">
                  <i v-if="isSubmittingAssyTest" class="fa-solid fa-spinner fa-spin"></i>
                  <i v-else class="fa-solid fa-floppy-disk"></i>
                  <span>{{ assyTestCalculatedFailures.length > 0 ? 'Simpan (Hold)' : 'Simpan' }}</span>
                </button>
              </div>
            </div>

          </div>

        </div>

        <!-- ================= MODAL VALIDASI SIMPAN PROGRES UJI ASSEMBLY ================= -->
        <div v-if="showAssySaveProgressModal"
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100005] flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
          <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
            <div
              class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-xl shadow-xs">
              <i class="fa-solid fa-floppy-disk"></i>
            </div>
            <div class="text-center space-y-1">
              <h3 class="font-black text-base sm:text-lg text-slate-900">
                Apakah Anda ingin menyimpan progres?
              </h3>
              <p class="text-xs text-slate-500 font-medium">
                Progres pengujian Set #{{ assyTestForm.set_number }} pada tahap {{ assyTestStep < assyTotalSteps ?
                  (assyTestStep + 1) : assyTotalSteps }} dari {{ assyTotalSteps }}. </p>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button type="button" @click="confirmSaveAssyProgress(false)"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px]">
                <i class="fa-solid fa-xmark"></i>
                <span>Tidak</span>
              </button>
              <button type="button" @click="confirmSaveAssyProgress(true)"
                class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px]">
                <i class="fa-solid fa-check"></i>
                <span>Iya (Simpan)</span>
              </button>
            </div>
            <div class="pt-1 text-center">
              <button type="button" @click="showAssySaveProgressModal = false"
                class="text-[11px] text-slate-400 hover:text-slate-600 font-medium underline cursor-pointer">
                Batal (Tetap Lanjutkan Tes)
              </button>
            </div>
          </div>
        </div>

        <!-- ================= MODAL KONFIRMASI NOT GOOD PERTAMA KALI ================= -->
        <div v-if="showFirstNGPromptModal"
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100005] flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
          <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
            <div
              class="w-12 h-12 mx-auto rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center text-xl shadow-xs">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="text-center space-y-1">
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-snug">
                Apakah Anda ingin langsung mengakhiri sesi testing ini?
              </h3>
              <p class="text-xs text-slate-500 font-medium">
                Hasil "Not Good" terdeteksi pertama kali. Mengakhiri sesi akan menyimpan status set sebagai Hold dan
                sisa tes yang belum dikerjakan diset Pending (-).
              </p>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button type="button" @click="handleFirstNGChoice(true)" :disabled="isSubmittingAssyTest"
                class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/25 transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
                <i v-if="isSubmittingAssyTest" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-check"></i>
                <span>Iya</span>
              </button>
              <button type="button" @click="handleFirstNGChoice(false)" :disabled="isSubmittingAssyTest"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
                <i class="fa-solid fa-xmark"></i>
                <span>Tidak</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= MODAL KONFIRMASI AKHIRI SESI TESTING ================= -->
        <div v-if="showSecondNGPromptModal"
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100005] flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
          <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
            <div
              class="w-12 h-12 mx-auto rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-xl shadow-xs">
              <i class="fa-solid fa-circle-stop"></i>
            </div>
            <div class="text-center space-y-1">
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-snug">
                Apakah Anda yakin untuk mengakhiri sesi testing?
              </h3>
              <p class="text-xs text-slate-500 font-medium">
                Sesi testing akan diakhiri dan status set produk menjadi Hold. Sisa pengujian yang belum dikerjakan akan
                diset statusnya menjadi Pending (-).
              </p>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-2">
              <button type="button" @click="confirmEndSessionEarly()" :disabled="isSubmittingAssyTest"
                class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md shadow-rose-600/25 transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
                <i v-if="isSubmittingAssyTest" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-check"></i>
                <span>Ya</span>
              </button>
              <button type="button" @click="showSecondNGPromptModal = false" :disabled="isSubmittingAssyTest"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
                <i class="fa-solid fa-xmark"></i>
                <span>Tidak</span>
              </button>
            </div>
          </div>
        </div>

      </section>

      <!-- ================= MODAL FORM ASSEMBLY ITEM (NO-PRINT) ================= -->
      <div v-if="activeModalIndex !== null" @click.self="safeCloseModal(closeModal, !!assyForm.started_at)"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] overflow-y-auto">

          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5 sm:space-x-3">
              <div
                class="bg-red-600 text-white w-8 h-8 sm:w-9 sm:h-9 rounded-xl font-bold flex items-center justify-center shadow-md shadow-red-600/30 text-xs sm:text-sm flex-shrink-0">
                #{{ activeModalIndex + 1 }}
              </div>
              <div>
                <h3 class="font-black text-base sm:text-lg text-slate-900">Form Penggabungan Assembly Set #{{
                  activeModalIndex + 1 }}</h3>
                <p class="text-[11px] sm:text-xs text-slate-500 font-mono">Tray: {{ currentTray.tray_code }}</p>
              </div>
            </div>
            <button @click="safeCloseModal(closeModal, !!assyForm.started_at)"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer"
              title="Tutup Modal">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>

          <div class="space-y-3.5 sm:space-y-4">
            <!-- 1. JAM SECTION -->
            <!-- State A: Jam LOCKED (Abu-abu) Saat Belum Klik Mulai -->
            <div v-if="!assyForm.started_at"
              class="bg-slate-100 border border-slate-300 rounded-xl sm:rounded-2xl p-3.5 space-y-2">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div class="w-7 h-7 rounded-lg bg-slate-200 text-slate-500 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-lock"></i>
                  </div>
                  <div>
                    <span class="block text-xs font-bold uppercase text-slate-700">Timer Pengerjaan (Terkunci)</span>
                    <span class="text-[10px] text-slate-500 font-medium">Jam pengerjaan terkunci. Klik tombol
                      <strong>"Mulai"</strong> di bawah untuk mengaktifkan timer.</span>
                  </div>
                </div>
                <span
                  class="text-[10px] font-bold bg-slate-200 text-slate-600 px-2 py-0.5 rounded border border-slate-300 font-mono flex-shrink-0">
                  OFF / LOCKED
                </span>
              </div>
              <div
                class="bg-slate-200/80 border border-slate-300 rounded-lg px-3 py-2 flex items-center justify-between text-slate-500 font-mono text-xs">
                <span class="flex items-center gap-1.5 font-bold"><i class="fa-regular fa-clock"></i> 00:00:00
                  WIB</span>
                <span
                  class="text-[9px] uppercase font-sans font-bold bg-white/70 px-1.5 py-0.5 rounded text-slate-600 border border-slate-300">Menunggu
                  Tombol Mulai</span>
              </div>
            </div>

            <!-- State B: Jam ON (Menyala Hijau) Setelah Klik Mulai -->
            <div v-else
              class="bg-emerald-50 border-2 border-emerald-400 rounded-xl sm:rounded-2xl p-3.5 space-y-2 text-emerald-900 shadow-sm">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <div
                    class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs shadow-md shadow-emerald-600/30">
                    <i class="fa-solid fa-stopwatch animate-pulse"></i>
                  </div>
                  <div>
                    <span class="block text-xs font-black uppercase text-emerald-950">Timer Pengerjaan Aktif (ON)</span>
                    <span class="text-[10px] text-emerald-700 font-semibold">Mulai: <strong>{{
                        formatTimeOnly(assyForm.started_at) }} WIB</strong></span>
                  </div>
                </div>
                <span
                  class="text-[10px] font-black bg-emerald-600 text-white px-2.5 py-0.5 rounded-full flex items-center gap-1 shadow-sm flex-shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                  LIVE ON
                </span>
              </div>
              <div
                class="bg-white border border-emerald-300 rounded-lg px-3 py-2 flex items-center justify-between font-mono text-xs shadow-inner">
                <span class="font-black text-emerald-950 flex items-center gap-1.5">
                  <i class="fa-regular fa-clock text-emerald-600"></i>
                  {{ liveClockString }}
                </span>
                <span
                  class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-sans border border-emerald-200">Sedang
                  Dirakit</span>
              </div>
            </div>

            <!-- 2. FORM PILIH PART (LOCKED / DISABLED SETELAH MULAI) -->
            <!-- Input 1: P9 Part 15 Selection from Tray -->
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700 mb-1 flex items-center justify-between">
                <span>1. P9 Part 15 (Identitas P9) <span class="text-red-600">*</span></span>
                <span v-if="assyForm.started_at"
                  class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[9px]"></i> Terkunci
                </span>
              </label>
              <select v-model="assyForm.part15" :disabled="!!assyForm.started_at"
                :class="assyForm.started_at ? 'bg-slate-100 border-slate-300 text-slate-600 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 text-slate-900 cursor-pointer'"
                class="w-full border rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-mono font-bold focus:outline-none min-h-[42px]">
                <option value="">Pilih P9 Part 15</option>
                <option v-for="p in availablePartsByType('15')" :key="p.sn" :value="p.sn">
                  {{ p.sn }} (Tersedia)
                </option>
              </select>
            </div>

            <!-- Input 2: P9 Part 1 Selection from Tray -->
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700 mb-1 flex items-center justify-between">
                <span>2. P9 Part 1 <span class="text-red-600">*</span></span>
                <span v-if="assyForm.started_at"
                  class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[9px]"></i> Terkunci
                </span>
              </label>
              <select v-model="assyForm.part1" :disabled="!!assyForm.started_at"
                :class="assyForm.started_at ? 'bg-slate-100 border-slate-300 text-slate-600 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 text-slate-900 cursor-pointer'"
                class="w-full border rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-mono font-bold focus:outline-none min-h-[42px]">
                <option value="">Pilih P9 Part 1</option>
                <option v-for="p in availablePartsByType('1')" :key="p.sn" :value="p.sn">
                  {{ p.sn }} (Tersedia)
                </option>
              </select>
            </div>

            <!-- Input 3: P9 Part 12 Selection from Tray -->
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700 mb-1 flex items-center justify-between">
                <span>3. P9 Part 12 <span class="text-red-600">*</span></span>
                <span v-if="assyForm.started_at"
                  class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[9px]"></i> Terkunci
                </span>
              </label>
              <select v-model="assyForm.part12" :disabled="!!assyForm.started_at"
                :class="assyForm.started_at ? 'bg-slate-100 border-slate-300 text-slate-600 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-300 focus:bg-white focus:border-red-600 focus:ring-2 focus:ring-red-100 text-slate-900 cursor-pointer'"
                class="w-full border rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-mono font-bold focus:outline-none min-h-[42px]">
                <option value="">Pilih P9 Part 12</option>
                <option v-for="p in availablePartsByType('12')" :key="p.sn" :value="p.sn">
                  {{ p.sn }} (Tersedia)
                </option>
              </select>
            </div>

            <!-- 3. QC & STATUS SELECTION -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
              <!-- Jika Belum Klik Mulai: Pilihan Status Di-hide Sesuai Kesepakatan -->
              <div v-if="!assyForm.started_at"
                class="p-3.5 bg-amber-50/90 border border-amber-200 rounded-xl flex items-center gap-2.5 text-xs text-amber-900 font-semibold shadow-sm">
                <i class="fa-solid fa-circle-info text-amber-600 text-base flex-shrink-0"></i>
                <span>Silakan klik tombol <strong>Mulai Perakitan</strong> di bawah terlebih dahulu untuk mengaktifkan
                  pilihan status hasil perakitan.</span>
              </div>

              <!-- Jika Sudah Klik Mulai: Pilihan Status Muncul dan Bisa Dipilih -->
              <div v-else>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
                  Status Hasil Perakitan <span class="text-red-600">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2">
                  <button type="button"
                    @click="assyForm.status = 'Good'; assyForm.defective_parts = []; assyForm.reason = ''"
                    :class="assyForm.status === 'Good' ? 'bg-emerald-600 text-white ring-2 ring-emerald-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                    class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                    <i class="fa-solid fa-circle-check text-sm mb-0.5"></i> Good
                  </button>
                  <button type="button" @click="assyForm.status = 'Hold'"
                    :class="assyForm.status === 'Hold' ? 'bg-amber-600 text-white ring-2 ring-amber-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                    class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                    <i class="fa-solid fa-hand text-sm mb-0.5"></i> Hold
                  </button>
                  <button type="button" @click="assyForm.status = 'Scrap'"
                    :class="assyForm.status === 'Scrap' ? 'bg-red-600 text-white ring-2 ring-red-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                    class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                    <i class="fa-solid fa-trash-can text-sm mb-0.5"></i> Scrap
                  </button>
                </div>
              </div>

              <!-- Pilihan Checklist Part yang Bermasalah (Wajib Pilih Minimal 1 Part jika non-Good) -->
              <div v-if="assyForm.status && assyForm.status !== 'Good'"
                class="space-y-2 p-3 bg-slate-50 border border-slate-300 rounded-xl">
                <label
                  class="block text-xs font-bold uppercase text-slate-800 tracking-wider flex items-center justify-between">
                  <span><i class="fa-solid fa-list-check text-red-600 mr-1.5"></i>Pilih Part yang Bermasalah / Cacat
                    <span class="text-red-600">*</span></span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">Wajib
                    Dipilih</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                  <label
                    class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition select-none"
                    :class="assyForm.defective_parts && assyForm.defective_parts.includes('15') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                    <input type="checkbox" value="15" v-model="assyForm.defective_parts"
                      class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                    <span class="text-xs font-mono">P9 Part 15 ({{ assyForm.part15 || '-' }})</span>
                  </label>
                  <label
                    class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition select-none"
                    :class="assyForm.defective_parts && assyForm.defective_parts.includes('1') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                    <input type="checkbox" value="1" v-model="assyForm.defective_parts"
                      class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                    <span class="text-xs font-mono">P9 Part 1 ({{ assyForm.part1 || '-' }})</span>
                  </label>
                  <label
                    class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer transition select-none"
                    :class="assyForm.defective_parts && assyForm.defective_parts.includes('12') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                    <input type="checkbox" value="12" v-model="assyForm.defective_parts"
                      class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                    <span class="text-xs font-mono">P9 Part 12 ({{ assyForm.part12 || '-' }})</span>
                  </label>
                </div>
                <p v-if="!assyForm.defective_parts || !assyForm.defective_parts.length"
                  class="text-[11px] text-red-600 font-medium flex items-center gap-1">
                  <i class="fa-solid fa-triangle-exclamation"></i> Pilih minimal satu part yang bermasalah untuk
                  menampilkan kolom alasan.
                </p>
              </div>

              <!-- Dynamic Reason Field ONLY AFTER AT LEAST 1 PART IS CHECKED -->
              <div
                v-if="assyForm.status && assyForm.status !== 'Good' && assyForm.defective_parts && assyForm.defective_parts.length > 0"
                class="space-y-1.5 p-3 rounded-xl border transition-all animate-fadeIn"
                :class="assyForm.status === 'Hold' ? 'bg-amber-50/80 border-amber-300' : (assyForm.status === 'Scrap' ? 'bg-red-50/80 border-red-300' : 'bg-purple-50/80 border-purple-300')">
                <label class="block text-xs font-bold uppercase tracking-wider flex items-center justify-between"
                  :class="assyForm.status === 'Hold' ? 'text-amber-900' : (assyForm.status === 'Scrap' ? 'text-red-900' : 'text-purple-900')">
                  <span class="flex items-center gap-1.5">
                    <i
                      :class="assyForm.status === 'Hold' ? 'fa-solid fa-hand text-amber-600' : (assyForm.status === 'Scrap' ? 'fa-solid fa-trash-can text-red-600' : 'fa-solid fa-rotate-left text-purple-600')"></i>
                    <span>Alasan / Catatan Status {{ assyForm.status }} <span class="text-red-600">*</span></span>
                  </span>
                  <span
                    class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/90 border border-slate-200 text-slate-700">Wajib
                    Diisi</span>
                </label>
                <textarea v-model="assyForm.reason" rows="2"
                  :placeholder="'Tuliskan alasan lengkap mengapa part ini di-' + assyForm.status.toLowerCase() + '...'"
                  class="w-full bg-white border border-slate-300 focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-lg p-2.5 text-xs text-slate-900 font-medium focus:outline-none shadow-sm"></textarea>
              </div>

              <!-- Status Notice -->
              <div v-if="assyForm.status && assyForm.status !== 'Good'"
                class="bg-amber-50 border border-amber-200 text-amber-800 text-[11px] p-2.5 rounded-xl font-semibold flex items-center space-x-2">
                <i class="fa-solid fa-circle-info text-amber-600 flex-shrink-0"></i>
                <span>Catatan: Hanya part terpilih yang berstatus {{ assyForm.status }}. Part lain yang tidak dipilih
                  akan otomatis kembali menjadi Tersedia di Master DB.</span>
              </div>
            </div>
          </div>

          <!-- 4. MODAL FOOTER BUTTONS -->
          <div class="pt-3 flex items-center justify-between gap-3 border-t border-slate-100">
            <!-- Button Batal / Tutup Jeda -->
            <button @click="closeModal"
              class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold text-center transition min-h-[40px]">
              {{ assyForm.started_at ? 'Tutup (Jeda)' : 'Batal' }}
            </button>

            <!-- Button Mulai (Saat Belum Mulai) ATAU Button Selesai (Saat Sudah Mulai) -->
            <button v-if="!assyForm.started_at" type="button" @click="startAssemblyNow"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-7 py-2.5 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-2 active:scale-95 min-h-[40px]">
              <i class="fa-solid fa-play"></i>
              <span>Mulai</span>
            </button>

            <button v-else type="button" @click="saveAssemblyItem(false)"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-7 py-2.5 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-2 active:scale-95 min-h-[40px]">
              <i class="fa-solid fa-circle-check"></i>
              <span>Selesai</span>
            </button>
          </div>

        </div>
      </div>

      <!-- ================= MODAL QUICK PILIH STATUS (NO-PRINT) ================= -->
      <div v-if="showQuickStatusModal"
        @click.self="safeCloseModal(() => showQuickStatusModal = false, !!quickStatusData.status)"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                class="bg-emerald-600 text-white w-8 h-8 rounded-xl font-bold flex items-center justify-center shadow-md shadow-emerald-600/30 text-xs flex-shrink-0">
                #{{ quickStatusData.set_number }}
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900">Pilih Status Hasil Assembly</h3>
                <p class="text-[11px] text-slate-500 font-mono">Set #{{ quickStatusData.set_number }} - {{ currentTray ?
                  currentTray.tray_code : '' }}</p>
              </div>
            </div>
            <button @click="safeCloseModal(() => showQuickStatusModal = false, !!quickStatusData.status)"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>

          <div class="space-y-3.5">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
                Status Hasil Perakitan <span class="text-red-600">*</span>
              </label>
              <div class="grid grid-cols-3 gap-2">
                <button type="button"
                  @click="quickStatusData.status = 'Good'; quickStatusData.defective_parts = []; quickStatusData.reason = ''"
                  :class="quickStatusData.status === 'Good' ? 'bg-emerald-600 text-white ring-2 ring-emerald-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                  class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                  <i class="fa-solid fa-circle-check text-sm mb-0.5"></i> Good
                </button>
                <button type="button" @click="quickStatusData.status = 'Hold'"
                  :class="quickStatusData.status === 'Hold' ? 'bg-amber-600 text-white ring-2 ring-amber-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                  class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                  <i class="fa-solid fa-hand text-sm mb-0.5"></i> Hold
                </button>
                <button type="button" @click="quickStatusData.status = 'Scrap'"
                  :class="quickStatusData.status === 'Scrap' ? 'bg-red-600 text-white ring-2 ring-red-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                  class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px]">
                  <i class="fa-solid fa-trash-can text-sm mb-0.5"></i> Scrap
                </button>
              </div>
            </div>

            <!-- Pilihan Part yang Bermasalah di Quick Status -->
            <div v-if="quickStatusData.status && quickStatusData.status !== 'Good'"
              class="space-y-2 p-3 bg-slate-50 border border-slate-300 rounded-xl">
              <label
                class="block text-xs font-bold uppercase text-slate-800 tracking-wider flex items-center justify-between">
                <span><i class="fa-solid fa-list-check text-red-600 mr-1.5"></i>Pilih Part yang Bermasalah / Cacat <span
                    class="text-red-600">*</span></span>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">Wajib
                  Dipilih</span>
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer select-none text-xs transition"
                  :class="quickStatusData.defective_parts && quickStatusData.defective_parts.includes('15') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                  <input type="checkbox" value="15" v-model="quickStatusData.defective_parts"
                    class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                  <span class="font-mono">P9 Part 15 ({{ quickStatusData.part15 || '-' }})</span>
                </label>
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer select-none text-xs transition"
                  :class="quickStatusData.defective_parts && quickStatusData.defective_parts.includes('1') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                  <input type="checkbox" value="1" v-model="quickStatusData.defective_parts"
                    class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                  <span class="font-mono">P9 Part 1 ({{ quickStatusData.part1 || '-' }})</span>
                </label>
                <label
                  class="flex items-center space-x-2 p-2.5 rounded-lg border cursor-pointer select-none text-xs transition"
                  :class="quickStatusData.defective_parts && quickStatusData.defective_parts.includes('12') ? 'bg-red-50 border-red-400 text-red-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                  <input type="checkbox" value="12" v-model="quickStatusData.defective_parts"
                    class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                  <span class="font-mono">P9 Part 12 ({{ quickStatusData.part12 || '-' }})</span>
                </label>
              </div>
              <p v-if="!quickStatusData.defective_parts || !quickStatusData.defective_parts.length"
                class="text-[11px] text-red-600 font-medium flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i> Pilih minimal satu part yang bermasalah untuk
                menampilkan kolom alasan.
              </p>
            </div>

            <!-- Alasan jika Hold/Scrap/Rework (HANYA MUNCUL JIKA MINIMAL 1 PART DIPILIH) -->
            <div
              v-if="quickStatusData.status && quickStatusData.status !== 'Good' && quickStatusData.defective_parts && quickStatusData.defective_parts.length > 0"
              class="space-y-1.5 p-3 rounded-xl border transition-all animate-fadeIn"
              :class="quickStatusData.status === 'Hold' ? 'bg-amber-50/80 border-amber-300' : (quickStatusData.status === 'Scrap' ? 'bg-red-50/80 border-red-300' : 'bg-purple-50/80 border-purple-300')">
              <label class="block text-xs font-bold uppercase tracking-wider flex items-center justify-between"
                :class="quickStatusData.status === 'Hold' ? 'text-amber-900' : (quickStatusData.status === 'Scrap' ? 'text-red-900' : 'text-purple-900')">
                <span class="flex items-center gap-1.5">
                  <i
                    :class="quickStatusData.status === 'Hold' ? 'fa-solid fa-hand text-amber-600' : (quickStatusData.status === 'Scrap' ? 'fa-solid fa-trash-can text-red-600' : 'fa-solid fa-rotate-left text-purple-600')"></i>
                  <span>Alasan Status {{ quickStatusData.status }} <span class="text-red-600">*</span></span>
                </span>
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/90 border border-slate-200 text-slate-700">Wajib
                  Diisi</span>
              </label>
              <textarea v-model="quickStatusData.reason" rows="2"
                :placeholder="'Tuliskan alasan lengkap mengapa part ini di-' + quickStatusData.status.toLowerCase() + '...'"
                class="w-full bg-white border border-slate-300 focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-lg p-2.5 text-xs text-slate-900 font-medium focus:outline-none shadow-sm"></textarea>
            </div>
          </div>

          <div class="pt-3 flex items-center justify-end gap-2.5 border-t border-slate-100">
            <button @click="showQuickStatusModal = false"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px]">
              Batal
            </button>
            <button @click="submitQuickStatusModal"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center gap-1.5 active:scale-95 min-h-[38px]">
              <i class="fa-solid fa-circle-check"></i>
              <span>Simpan & Selesaikan</span>
            </button>
          </div>
        </div>
      </div>


@endverbatim
