@verbatim
      <!-- ================= SECTION PERAKITAN ULANG (RU) ================= -->
      <section v-if="currentTab === 'reassembly'" class="space-y-4 sm:space-y-6 animate-fadeIn">
        <!-- Header Banner & Operator Assignment Control -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <div class="flex items-center space-x-2 mb-1">
                <span class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-red-50 text-red-700 border border-red-200">
                  Assembly Rework
                </span>
                <span class="text-xs text-slate-500 font-medium">
                  {{ pendingReassemblyList.length }} unit menunggu penugasan
                </span>
              </div>
              <h2 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-arrows-rotate text-red-600 text-base"></i>
                <span>Perakitan Ulang</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Pilih set yang disetujui ganti part, tentukan operator assembly, lalu kirim penugasan.
              </p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button @click="loadPendingReassembly" type="button"
                class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 hover:bg-slate-50 text-slate-700 flex items-center space-x-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-arrows-rotate text-slate-400"
                  :class="{'fa-spin': isLoadingPendingReassembly}"></i>
                <span>Refresh</span>
              </button>
              <button
                v-if="pendingReassemblyList.length > 0 && selectedReassemblyIds.length < pendingReassemblyList.length"
                @click="selectAllReassembly" type="button"
                class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-300 hover:bg-slate-50 text-slate-700 transition cursor-pointer min-h-[38px]">
                Pilih Semua ({{ pendingReassemblyList.length }})
              </button>
              <button v-if="selectedReassemblyIds.length > 0" @click="clearSelectedReassembly" type="button"
                class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer min-h-[38px]">
                Batal Pilih ({{ selectedReassemblyIds.length }})
              </button>
            </div>
          </div>

          <!-- Penugasan Operator: Selalu terpasang rapi, tidak tiba-tiba muncul, warna profesional -->
          <div
            class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/80 -mx-5 -mb-5 p-4 rounded-b-2xl">
            <div class="flex items-center space-x-2.5">
              <span
                class="inline-flex items-center justify-center min-w-[24px] h-6 px-1.5 rounded-full text-xs font-bold transition"
                :class="selectedReassemblyIds.length > 0 ? 'bg-red-600 text-white' : 'bg-slate-200 text-slate-600'">
                {{ selectedReassemblyIds.length }}
              </span>
              <span class="text-xs font-bold text-slate-800">
                {{ selectedReassemblyIds.length > 0 ? `${selectedReassemblyIds.length} Set Dipilih untuk Dirakit Ulang`
                : 'Pilih set di bawah untuk dirakit ulang' }}
              </span>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto">
              <!-- Dropdown Operator Assembly: label bersih, tanpa logo orang, hanya arrow down -->
              <div class="relative flex-1 sm:w-64">
                <select v-model="selectedReassemblyOperator" :disabled="selectedReassemblyIds.length === 0"
                  class="w-full appearance-none bg-white text-slate-900 text-xs font-bold pl-3 pr-8 py-2 rounded-xl border border-slate-300 shadow-xs focus:outline-none focus:border-red-600 focus:ring-1 focus:ring-red-600 disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed cursor-pointer transition min-h-[38px]">
                  <option value="" disabled>Pilih Operator Assembly</option>
                  <option v-for="op in operatorUsersList" :key="op.username || op.id"
                    :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                  <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </div>
              </div>

              <!-- Tombol Kirim -->
              <button @click="submitReassemblyDispatch"
                :disabled="selectedReassemblyIds.length === 0 || !selectedReassemblyOperator || isSubmittingReassemblyDispatch"
                type="button"
                class="px-5 py-2 rounded-xl text-xs font-bold bg-red-600 hover:bg-red-700 disabled:bg-slate-200 disabled:text-slate-400 text-white transition flex items-center justify-center space-x-1.5 shadow-xs disabled:shadow-none cursor-pointer disabled:cursor-not-allowed min-h-[38px] whitespace-nowrap">
                <i v-if="isSubmittingReassemblyDispatch" class="fa-solid fa-spinner fa-spin text-xs"></i>
                <i v-else class="fa-solid fa-paper-plane text-xs"></i>
                <span>{{ isSubmittingReassemblyDispatch ? 'Memproses...' : 'Kirim' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Grid of Cards -->
        <div v-if="pendingReassemblyList.length > 0"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <div v-for="item in pendingReassemblyList" :key="item.id" @click="toggleSelectReassembly(item.id)"
            :class="isReassemblySelected(item.id) ? 'border-red-500 ring-2 ring-red-400/40 bg-red-50/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
            class="border rounded-2xl p-4 shadow-xs transition duration-150 flex flex-col justify-between cursor-pointer group">

            <div>
              <!-- Card Header: Checkbox + Set Number + Status -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                <div class="flex items-center space-x-2.5">
                  <input type="checkbox" :checked="isReassemblySelected(item.id)"
                    @click.stop="toggleSelectReassembly(item.id)"
                    class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300 cursor-pointer">
                  <div>
                    <span class="text-xs font-bold text-slate-800">Set #{{ item.set_number }}</span>
                    <span class="text-[10px] text-slate-400 block font-mono">Tray {{ item.tray_code }}</span>
                  </div>
                </div>
                <div class="flex items-center gap-1">
                  <span v-if="item.hold_source === 'QC' || ['HOLD_QC', 'REWORK_QC'].includes(item.qc_status)"
                    class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    Asal: QC (TF 2)
                  </span>
                  <span v-else-if="item.hold_source === 'TESTER_ASSEMBLY' || ['Hold TF 1', 'Hold', 'Not Good', 'FAIL'].includes(item.assy_test_status) || item.assy_failure_reasons"
                    class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Asal: TF 1
                  </span>
                  <span v-else
                    class="px-2 py-0.5 rounded-md text-[9.5px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Asal: Perakitan
                  </span>
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                    RU
                  </span>
                </div>
              </div>

              <!-- Product type & Replaced By -->
              <div class="text-[11px] text-slate-600 space-y-1 mb-3">
                <div class="flex justify-between">
                  <span class="text-slate-400">Tipe Produk:</span>
                  <span class="font-bold text-slate-800">{{ item.product_type || 'Nanggala P9' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">Ganti Part Oleh:</span>
                  <span class="font-bold text-slate-700">{{ item.replaced_by_admin || 'Admin' }}</span>
                </div>
              </div>

              <!-- SN Breakdown (15, 1, 12) -->
              <div class="space-y-1.5 bg-slate-50/80 rounded-xl p-2.5 border border-slate-100 text-xs">
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-400 font-medium">Part 15 (Frame):</span>
                  <span class="font-mono font-bold text-slate-800 truncate max-w-[130px]" :title="item.part15_sn">{{
                    item.part15_sn || '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-400 font-medium">Part 1 (Trigger):</span>
                  <span class="font-mono font-bold text-slate-800 truncate max-w-[130px]" :title="item.part1_sn">{{
                    item.part1_sn || '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-400 font-medium">Part 12 (Slide):</span>
                  <span class="font-mono font-bold text-slate-800 truncate max-w-[130px]" :title="item.part12_sn">{{
                    item.part12_sn || '-' }}</span>
                </div>
              </div>

              <!-- Replacements detail if available -->
              <div v-if="item.replacements && item.replacements.length > 0" class="mt-2.5 space-y-1">
                <div class="text-[10px] font-bold text-emerald-700">
                  Part Baru Terganti:
                </div>
                <div v-for="r in item.replacements" :key="r.id"
                  class="text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-200 rounded px-2 py-1 flex items-center justify-between">
                  <span>Part {{ r.part_type }}</span>
                  <span class="font-mono font-bold">{{ r.new_sn }}</span>
                </div>
              </div>

              <!-- Problem/Reason Note -->
              <div v-if="item.rework_reason || item.reason"
                class="mt-2 text-[10px] text-slate-600 bg-amber-50/70 border border-amber-200/70 rounded-lg p-2">
                "{{ item.rework_reason || item.reason }}"
              </div>
            </div>

            <!-- Footer Card Action: Clean Select toggle button -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end">
              <button type="button" @click.stop="toggleSelectReassembly(item.id)"
                :class="isReassemblySelected(item.id) ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                {{ isReassemblySelected(item.id) ? 'Dipilih' : 'Pilih' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else
          class="p-12 text-center text-slate-400 font-sans border border-slate-200 rounded-2xl bg-slate-50/50 space-y-3">
          <div
            class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-arrows-rotate"></i>
          </div>
          <div>
            <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Unit Menunggu Perakitan Ulang</h4>
            <p class="text-xs text-slate-500 mt-0.5">Semua unit telah ditugaskan atau belum ada persetujuan ganti part
              baru.</p>
          </div>
        </div>
      </section>

      <!-- ================= MODAL DETAIL HASIL PERAKITAN (MURNI RAKITAN TANPA TEST FUNCTIONAL) ================= -->
      <div v-if="showAssemblyDetailModal || showHoldDetailModal"
        @click.self="showAssemblyDetailModal = false; showHoldDetailModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 overflow-y-auto no-print">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                :class="(getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU') ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold') ? 'bg-amber-50 text-amber-700 border-amber-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Rework') ? 'bg-purple-50 text-purple-700 border-purple-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap') ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')))"
                class="w-10 h-10 rounded-xl border flex items-center justify-center text-lg font-bold shadow-xs">
                <i v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU'"
                  class="fa-solid fa-arrows-rotate"></i>
                <i v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold'"
                  class="fa-solid fa-hand"></i>
                <i v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Rework'"
                  class="fa-solid fa-rotate-left"></i>
                <i v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap'"
                  class="fa-solid fa-ban"></i>
                <i v-else class="fa-solid fa-check"></i>
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900">Detail Hasil Perakitan</h3>
                <p class="text-[11px] text-slate-500 font-medium font-mono">
                  {{ selectedAssemblyItem?.tray_code || selectedAssemblyTray?.tray_code }} &bull; Set #{{
                  selectedAssemblyItem?.set_number }}
                </p>
              </div>
            </div>
            <button type="button" @click="showAssemblyDetailModal = false; showHoldDetailModal = false"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>

          <!-- Unit Information Card -->
          <div class="space-y-3 text-xs">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">No. Dokumen
                  Perakitan:</span>
                <strong class="font-mono text-slate-900 text-xs">{{ (selectedAssemblyItem?.status === 'Dirakit Ulang' ||
                  selectedAssemblyItem?.qc_status === 'RU' || (typeof isReassemblySet === 'function' &&
                  isReassemblySet(selectedAssemblyItem))) ? getFormattedDocCode(selectedAssemblyItem?.tray_code,
                  'assy_rework') : (selectedAssemblyItem?.assy_doc_number ||
                  getFormattedDocCode(selectedAssemblyItem?.tray_code, 'assy') || '-') }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">No. Dokumen WO:</span>
                <strong class="font-mono text-slate-900 text-xs">{{ selectedAssemblyItem?.tray_code ||
                  selectedAssemblyTray?.tray_code || '-' }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">Operator Perakitan:</span>
                <strong class="text-slate-800 text-xs">{{ selectedAssemblyItem?.pic_assembly ||
                  selectedAssemblyTray?.pic_assembly || '-' }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">Status Perakitan:</span>
                <span v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Good'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-circle-check text-[9px]"></i> GOOD
                </span>
                <span v-else-if="isItemPendingAdmin(selectedAssemblyItem || selectedHoldItem)"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> PENDING (VERIF ADMIN)
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-arrows-rotate text-[9px]"></i> RU
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-block">
                  <i class="fa-solid fa-hand text-[9px] mr-0.5"></i> HOLD
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Rework'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-purple-100 text-purple-900 border border-purple-300 inline-block">
                  <i class="fa-solid fa-rotate-left text-[9px] mr-0.5"></i> REWORK
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-block">
                  <i class="fa-solid fa-ban text-[9px] mr-0.5"></i> SCRAP
                </span>
                <span v-else
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-block">
                  <i class="fa-solid fa-circle-check text-[9px] mr-0.5"></i> GOOD
                </span>
              </div>
            </div>

            <!-- Timestamps & Duration -->
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 grid grid-cols-3 gap-2 text-[11px]">
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Waktu Mulai:</span>
                <span class="font-mono text-slate-700 text-[10.5px]">{{ formatDateTime(selectedAssemblyItem?.started_at)
                  || '-' }}</span>
              </div>
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Waktu Selesai:</span>
                <span class="font-mono text-slate-700 text-[10.5px]">{{
                  formatDateTime(selectedAssemblyItem?.finished_at) || '-' }}</span>
              </div>
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Durasi Perakitan:</span>
                <strong class="font-mono text-indigo-700 text-[10.5px]">{{
                  calculateAssemblyDuration(selectedAssemblyItem?.started_at, selectedAssemblyItem?.finished_at)
                  }}</strong>
              </div>
            </div>

            <!-- Serial Numbers -->
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2 font-mono">
              <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">SN P9 (Part 15):</span>
                <strong class="text-slate-900 text-sm">{{ selectedAssemblyItem?.part15_sn || selectedHoldItem?.part15_sn
                  || '-' }}</strong>
              </div>
              <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">PN Part 1:</span>
                <strong class="text-slate-800">{{ selectedAssemblyItem?.part1_sn || selectedHoldItem?.part1_sn || '-'
                  }}</strong>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">PN Part 12:</span>
                <strong class="text-slate-800">{{ selectedAssemblyItem?.part12_sn || selectedHoldItem?.part12_sn || '-'
                  }}</strong>
              </div>
            </div>

            <!-- Problem / Failure Details Box if any -->
            <div
              v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) !== 'Good' && (selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.reason)"
              class="bg-amber-50 border border-amber-200 p-3 rounded-xl space-y-1">
              <div class="text-[10px] font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Catatan Kendala / Part Cacat:</span>
              </div>
              <div class="text-xs text-amber-950 font-medium leading-relaxed">
                {{ selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.reason || 'Tidak ada catatan kendala'
                }}
              </div>
            </div>

            <!-- Riwayat Pergantian Part / Perubahan Status -->
            <div
              v-if="selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || (selectedAssemblyItem?.reason && selectedAssemblyItem?.reason.includes('Ganti Part'))"
              class="bg-blue-50 border border-blue-200 p-3 rounded-xl space-y-2 text-xs">
              <div class="text-[10px] font-black uppercase tracking-wider text-blue-900 flex items-center gap-1.5">
                <i class="fa-solid fa-wrench text-blue-600"></i>
                <span>Riwayat Pergantian Part:</span>
              </div>
              <div
                v-if="getParsedReplacementHistory(selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason).length > 0"
                class="flex flex-wrap gap-2">
                <div
                  v-for="r in getParsedReplacementHistory(selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason)"
                  :key="r.partType"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-blue-200 rounded-lg text-xs shadow-2xs">
                  <span class="font-bold text-blue-800 text-[10.5px] uppercase">Part {{ r.partType }}:</span>
                  <span class="font-mono line-through text-rose-500 font-bold">{{ r.oldSN }}</span>
                  <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-0.5"></i>
                  <span class="font-mono text-emerald-600 font-black">{{ r.newSN }}</span>
                </div>
              </div>
              <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                {{ selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason ||
                selectedAssemblyItem?.reason }}
              </div>
            </div>

            <!-- Rework / Hold Reason Section (Hanya jika status bukan Good) -->
            <div
              v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) !== 'Good' && (selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason || selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.latest_replacement_reason)"
              class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 space-y-2 text-xs">
              <div class="flex items-center space-x-2 text-blue-900 font-bold">
                <i class="fa-solid fa-file-lines text-blue-600"></i>
                <span>Catatan / Alasan Unit Perakitan:</span>
              </div>
              <div>
                <div v-if="selectedAssemblyItem?.latest_replacement_reason"
                  class="font-mono text-blue-950 font-medium leading-relaxed">
                  <span class="font-bold text-blue-800">Alasan Ganti Part:</span> {{
                  selectedAssemblyItem?.latest_replacement_reason }}
                  <div v-if="selectedAssemblyItem?.rework_reason" class="mt-1 text-[11px] text-slate-600">
                    <span class="font-bold text-slate-700">Catatan Tambahan:</span> {{
                    selectedAssemblyItem?.rework_reason }}
                  </div>
                </div>
                <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                  {{ selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason ||
                  selectedAssemblyItem?.reason }}
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons Bar -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
            <!-- 1. Tombol KEMBALI -->
            <button type="button" @click="showAssemblyDetailModal = false; showHoldDetailModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
              <i class="fa-solid fa-arrow-left text-[11px]"></i>
              <span>Kembali</span>
            </button>

            <!-- Tombol Aksi: DAFTAR ULANG & GANTI PART -->
            <!-- 1. Jika Sedang Menunggu Verifikasi Admin: Sembunyikan Tombol & Tampilkan Status Pending (hanya untuk yang bermasalah di perakitan) -->
            <div
              v-if="isItemPendingAdmin(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold shadow-xs">
              <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
              <div>
                <span class="block text-amber-950 font-black">{{ getItemPendingLabel(selectedAssemblyItem ||
                  selectedHoldItem) }}</span>
                <span class="block text-[10px] text-amber-700 font-medium">Pengajuan sedang diproses Admin. Tombol aksi
                  akan muncul kembali setelah dikonfirmasi.</span>
              </div>
            </div>

            <!-- 2. Jika SUDAH MASUK PERAKITAN ULANG (RU): GABISA DIAPA-APAIN, HANYA LIHAT DETAIL PERUBAHAN PART -->
            <div v-else-if="isReassemblySet(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs shadow-xs">
              <i class="fa-solid fa-arrows-rotate text-indigo-600 text-base shrink-0"></i>
              <div>
                <span class="block text-indigo-950 font-black uppercase text-[11px]">Dirakit Ulang (RU)</span>
                <span class="block text-[10.5px] text-indigo-700 font-medium leading-relaxed">Unit telah masuk antrean
                  perakitan ulang dan tidak dapat diubah lagi.</span>
              </div>
            </div>

            <div
              v-else-if="(selectedAssemblyItem || selectedHoldItem)?.assembly_status !== 'Good' && ((selectedAssemblyItem || selectedHoldItem)?.verified_status === 'APPROVED_REREG' || (selectedAssemblyItem || selectedHoldItem)?.is_reregistered)"
              class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs shadow-xs">
              <i class="fa-solid fa-circle-check text-indigo-600 text-base shrink-0"></i>
              <div>
                <span class="block text-indigo-950 font-black uppercase text-[11px]">Selesai Didaftarkan Ulang &bull;
                  Disetujui Admin</span>
                <span class="block text-[10.5px] text-indigo-700 font-medium leading-relaxed">Unit ini telah selesai
                  disetujui Admin untuk dirakit ulang ke dokumen baru (<strong class="font-mono text-indigo-900">{{
                    (selectedAssemblyItem || selectedHoldItem)?.rereg_wo_suffix ? ((selectedAssemblyItem ||
                    selectedHoldItem)?.tray_code + '-' + (selectedAssemblyItem || selectedHoldItem)?.rereg_wo_suffix) :
                    getNextPUTrayCode((selectedAssemblyItem || selectedHoldItem)?.tray_code) }}</strong>). Unit ini
                  tidak dapat didaftarkan ulang lagi.</span>
              </div>
            </div>

            <!-- 3. Jika Belum Didaftarkan Ulang & Status Perakitan Bukan Good: Tampilkan Tombol -->
            <div
              v-else-if="String(selectedAssemblyItem?.assembly_status || selectedHoldItem?.assembly_status || 'Good').trim().toUpperCase() !== 'GOOD' && !isReassemblySet(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2 flex-wrap">

              <!-- KONDISI A: DARI HASIL TEST FUNCTIONAL (Uji Fungsional 1 atau QC) -> LANJUT PROSES & GANTI PART -->
              <template
                v-if="currentTab === 'functional_hold' || (showHoldDetailModal && currentTab !== 'production_hold')">
                <button type="button"
                  @click="proceedHoldItem(selectedHoldItem || selectedAssemblyItem)"
                  class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-circle-arrow-right text-[11px]"></i>
                  <span>{{ getHoldProceedButtonLabel(selectedHoldItem || selectedAssemblyItem) }}</span>
                </button>
                <button type="button" @click="openReplacePartSubModal"
                  class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-wrench text-[11px]"></i>
                  <span>Ganti Part</span>
                </button>
              </template>

              <!-- KONDISI B: DARI HASIL PERAKITAN (ASSEMBLY MURNI) -> LANJUT PROSES & GANTI PART -->
              <template v-else>
                <!-- Keterangan Jika Semua Part Bermasalah Sudah Diganti: Informasi Tambahan Siap Daftar Ulang -->
                <div v-if="isAllProblematicPartsReplaced(selectedAssemblyItem || selectedHoldItem)"
                  class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                  <span>Part bermasalah telah diganti &bull; Siap untuk Rakit Ulang</span>
                </div>

                <!-- Tombol LANJUT TF 1 / PROSES BERIKUTNYA -->
                <button type="button"
                  @click="proceedHoldItem(selectedAssemblyItem || selectedHoldItem)"
                  class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-circle-arrow-right text-[11px]"></i>
                  <span>{{ getHoldProceedButtonLabel(selectedAssemblyItem || selectedHoldItem) }}</span>
                </button>

                <!-- Tombol GANTI PART -->
                <button type="button" @click="openReplacePartSubModal"
                  class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-wrench text-[11px]"></i>
                  <span>Ganti Part</span>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SUB-MODAL 1: DAFTAR ULANG 1 SET RAKITAN (Perakitan Ulang) ================= -->
      <div v-if="showReregisterSubModal"
        @click.self="safeCloseModal(() => showReregisterSubModal = false, !!(reregOperator || reregisterReason))"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 overflow-y-auto no-print">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm font-bold">
                <i class="fa-solid fa-hammer"></i>
              </div>
              <div>
                <h3 class="font-black text-sm text-slate-900">Daftar Ulang — Perakitan Ulang</h3>
                <p class="text-[11px] text-slate-500 font-medium">Set ini akan dikembalikan ke antrean perakitan
                  operator</p>
              </div>
            </div>
            <button type="button"
              @click="safeCloseModal(() => showReregisterSubModal = false, !!(reregOperator || reregisterReason))"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <!-- Info Alur Baru -->
            <div
              class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-900 flex items-start gap-2.5">
              <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm mt-0.5 shrink-0"></i>
              <div class="space-y-1">
                <span class="font-bold block text-amber-950">Alur Perakitan Ulang:</span>
                <ul class="leading-relaxed text-amber-800 space-y-0.5 list-disc pl-3">
                  <li>Status set akan menjadi <strong>Supplied</strong> dan perlu dirakit ulang oleh operator</li>
                  <li>Dokumen WO asli <strong>tidak berubah</strong> — dokumen baru akan dibuat dengan suffix
                    inkremental <strong>-PU1, -PU2, dst</strong></li>
                  <li>Pengajuan ini membutuhkan persetujuan <strong>Admin</strong> terlebih dahulu</li>
                </ul>
              </div>
            </div>

            <!-- Set Info Summary -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Set #</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.set_number
                  }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">WO / Tray</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.tray_code
                  }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Operator Sebelumnya</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.pic_assembly ||
                  '-' }}</span>
              </div>
            </div>

            <!-- Pilih Operator Perakitan Ulang -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1.5">
                <i class="fa-solid fa-user-gear mr-1 text-amber-600"></i>
                Operator yang Akan Merakit Ulang:
              </label>
              <div class="relative">
                <select v-model="reregOperator"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-600 appearance-none cursor-pointer">
                  <option value="">— Pilih Operator —</option>
                  <option v-for="op in operatorUsersList" :key="op.username || op.id"
                    :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
                <i
                  class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
              </div>
              <p class="text-[10px] text-slate-400 mt-1">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Kosongkan untuk menggunakan operator yang sama ({{ (selectedHoldItem ||
                selectedAssemblyItem)?.pic_assembly || 'sebelumnya' }})
              </p>
            </div>

            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1">
                Catatan / Alasan Perakitan Ulang:
              </label>
              <textarea v-model="reregisterReason" rows="2"
                placeholder="Tuliskan alasan kenapa set ini perlu dirakit ulang..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-600"></textarea>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="showReregisterSubModal = false"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
              Batal
            </button>
            <button type="button" @click="submitReregisterSetAction" :disabled="isSubmittingReregisterSetAction"
              class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/20 transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50">
              <i :class="isSubmittingReregisterSetAction ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-hammer'"></i>
              <span>Ajukan Perakitan Ulang ke Admin</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ================= SUB-MODAL 2: GANTI PART BERMASALAH ================= -->
      <div v-if="showReplacePartModal" @click.self="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
        class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 overflow-y-auto no-print pt-6 sm:pt-12 pb-16">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto sm:my-0 animate-fadeIn">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-sm font-bold">
                <i class="fa-solid fa-wrench"></i>
              </div>
              <div>
                <h3 class="font-black text-sm text-slate-900">Ganti Part Bermasalah</h3>
                <p class="text-[11px] text-slate-500 font-medium">Ganti komponen rusak dengan part baru dari Master Stok
                </p>
              </div>
            </div>
            <button type="button" @click="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="space-y-3.5 text-xs">
            <!-- 1. Komponen yang Terpasang Saat Ini & Status -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[10.5px] font-bold uppercase text-slate-700 flex items-center gap-1.5">
                  <span>1. Komponen yang terpasang saat ini:</span>
                </label>
              </div>

              <!-- Grid status 3 part -->
              <div class="grid grid-cols-3 gap-2">
                <!-- Part 15 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 15 (P9)</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part15_sn
                    || '-' }}</span>
                </div>

                <!-- Part 1 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 1</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part1_sn
                    || '-' }}</span>
                </div>

                <!-- Part 12 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 12</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part12_sn
                    || '-' }}</span>
                </div>
              </div>

              <!-- Info Alasan Rework / Hold / Scrap -->
              <div
                class="mt-2.5 p-3 rounded-xl border bg-amber-50/80 border-amber-200 text-amber-900 space-y-1.5 shadow-xs">
                <div class="flex items-center justify-between gap-2">
                  <div
                    class="flex items-center gap-1.5 font-bold text-[10.5px] uppercase tracking-wider text-amber-900">
                    <i class="fa-solid fa-circle-exclamation text-amber-600"></i>
                    <span>Alasan {{ selectedHoldItem?.assembly_status || selectedHoldItem?.status || 'Bermasalah'
                      }}:</span>
                  </div>
                  <span :class="[
                    (selectedHoldItem?.assembly_status === 'Rework' || selectedHoldItem?.status === 'Rework') ? 'bg-purple-100 text-purple-800 border-purple-200' :
                    (selectedHoldItem?.assembly_status === 'Hold' || selectedHoldItem?.status === 'Hold') ? 'bg-amber-100 text-amber-800 border-amber-200' :
                    'bg-red-100 text-red-800 border-red-200'
                  ]" class="text-[9.5px] font-bold px-2 py-0.5 rounded-md border font-mono uppercase">
                    Status: {{ selectedHoldItem?.assembly_status || selectedHoldItem?.status || 'Hold' }}
                  </span>
                </div>
                <div
                  class="text-xs font-semibold text-slate-800 bg-white/90 border border-amber-200/70 rounded-lg px-3 py-2 leading-relaxed break-words shadow-xs">
                  {{ getItemProblemReason(selectedHoldItem) }}
                </div>
              </div>
            </div>

            <!-- 2. Status untuk Part Lama di Master Stok -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-2">
                2. Status Part Lama yang Dilepas ke Master Stok:
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="replaceOldStatus = 'Hold'"
                  :class="replaceOldStatus === 'Hold' ? 'bg-amber-600 text-white ring-2 ring-amber-400 font-black shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold'"
                  class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-2 min-h-[40px] cursor-pointer active:scale-95 select-none">
                  <i class="fa-solid fa-hand text-sm"></i>
                  <span>Status: Hold</span>
                </button>
                <button type="button" @click="replaceOldStatus = 'Scrap'"
                  :class="replaceOldStatus === 'Scrap' ? 'bg-red-600 text-white ring-2 ring-red-400 font-black shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold'"
                  class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-2 min-h-[40px] cursor-pointer active:scale-95 select-none">
                  <i class="fa-solid fa-trash-can text-sm"></i>
                  <span>Status: Scrap</span>
                </button>
              </div>
              <p class="text-[10px] text-slate-500 mt-1.5 italic">
                * Part lama yang diganti akan masuk ke Master Database dan antrean verifikasi untuk didaftarkan ulang
                secara satuan.
              </p>
            </div>

            <!-- 3. PILIH PART BARU PENGGANTI (HANYA PART BERMASALAH) -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <label class="block text-[10.5px] font-bold uppercase text-slate-700">
                  3. Pilih Part Baru Pengganti dari Stok Gudang (Bebas pilih 1 s/d 3 part):
                </label>
                <span class="text-[10px] font-bold"
                  :class="totalPartsToReplace > 0 ? 'text-blue-700' : 'text-slate-500'">
                  {{ totalPartsToReplace }} part dipilih untuk diganti
                </span>
              </div>

              <!-- ================= DROPDOWN PART 15 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 15 (P9)</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part15_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['15']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['15']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('15')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['15'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['15'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['15']" class="text-blue-700 font-black">
                        {{ replaceSelections['15'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 15)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['15']" @click.stop="clearReplaceSNForPart('15')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['15'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['15']" @click="replaceDropdownOpen['15'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['15']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['15']" placeholder="Cari SN Part 15..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('15'); replaceDropdownOpen['15'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part15_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['15']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('15').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 15 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('15')" :key="p.sn"
                        @click="selectReplaceSNForPart('15', p.sn)"
                        :class="replaceSelections['15'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['15'] === p.sn"
                            class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================= DROPDOWN PART 1 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 1</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part1_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['1']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['1']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('1')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['1'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['1'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['1']" class="text-blue-700 font-black">
                        {{ replaceSelections['1'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 1)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['1']" @click.stop="clearReplaceSNForPart('1')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['1'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['1']" @click="replaceDropdownOpen['1'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['1']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['1']" placeholder="Cari SN Part 1..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('1'); replaceDropdownOpen['1'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part1_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['1']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('1').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 1 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('1')" :key="p.sn"
                        @click="selectReplaceSNForPart('1', p.sn)"
                        :class="replaceSelections['1'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['1'] === p.sn" class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================= DROPDOWN PART 12 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 12</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part12_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['12']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['12']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('12')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['12'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['12'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['12']" class="text-blue-700 font-black">
                        {{ replaceSelections['12'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 12)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['12']" @click.stop="clearReplaceSNForPart('12')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['12'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['12']" @click="replaceDropdownOpen['12'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['12']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['12']" placeholder="Cari SN Part 12..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('12'); replaceDropdownOpen['12'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part12_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['12']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('12').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 12 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('12')" :key="p.sn"
                        @click="selectReplaceSNForPart('12', p.sn)"
                        :class="replaceSelections['12'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['12'] === p.sn"
                            class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Info Komponen Normal Tidak Perlu Diganti -->
              <div v-if="hasNonProblematicParts"
                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                <span class="text-[11px] font-medium">Komponen berstatus <strong>Normal</strong> tidak perlu diganti dan
                  otomatis dipertahankan.</span>
              </div>
            </div>

            <!-- 4. Alasan Penggantian -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1">
                4. Alasan Penggantian Part:
              </label>
              <input type="text" v-model="replaceReason"
                placeholder="Contoh: Part retak / gagal uji tahap fungsional..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-blue-600 min-h-[38px]">
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
              Batal
            </button>
            <button type="button" @click="submitReplacePartAction"
              :disabled="isSubmittingReplacePartAction || totalPartsToReplace === 0"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50">
              <i :class="isSubmittingReplacePartAction ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-check'"></i>
              <span v-if="totalPartsToReplace > 0">Pasang {{ totalPartsToReplace }} Part Baru &amp; Simpan</span>
              <span v-else>Pilih Part Pengganti</span>
            </button>
          </div>
        </div>
      </div>

@endverbatim
