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

@endverbatim
