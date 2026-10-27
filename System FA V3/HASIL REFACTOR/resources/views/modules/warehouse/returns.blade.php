@verbatim
      <!-- ================= TAB: PENGEMBALIAN BARANG DARI PERAKITAN ================= -->
      <section v-if="currentTab === 'returns'" class="space-y-4 sm:space-y-6">

        <!-- Top Header Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center space-x-3">
              <div
                class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg font-black flex-shrink-0 shadow-md shadow-red-600/20">
                <i class="fa-solid fa-rotate-left"></i>
              </div>
              <div>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Pengembalian Barang dari
                  Perakitan</h2>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start md:self-auto">
              <button @click="loadReturnedTrays"
                class="bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 font-bold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-95">
                <i class="fa-solid fa-rotate text-xs"></i>
                <span>Segarkan Data</span>
              </button>
            </div>
          </div>

          <!-- Filter Bar: Operator Dropdown | Text Search Template WH/ASSY/ [ suffix ] | Status Filter | Reset -->
          <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-slate-50 border border-slate-200 p-3.5 sm:p-4 rounded-xl items-end">

            <!-- 1. Filtering Dropdown by Operator -->
            <div>
              <label class="block text-[10px] font-bold text-slate-600 mb-1">
                <i class="fa-solid fa-user-gear text-slate-400 mr-1"></i>Filter operator:
              </label>
              <select v-model="returnsFilter.operator"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px] cursor-pointer">
                <option value="">Semua operator assembly</option>
                <option v-for="op in operatorUsersList" :key="op.username" :value="op.full_name || op.username">
                  {{ op.full_name || op.username }}
                </option>
              </select>
            </div>

            <!-- 2. Input Text Search dengan Template Nomor WH/ASSY/ -->
            <div>
              <label class="block text-[10px] font-bold text-slate-600 mb-1">
                <i class="fa-solid fa-magnifying-glass text-slate-400 mr-1"></i>Cari nomor tray (WH/ASSY):
              </label>
              <div class="flex items-center">
                <span
                  class="bg-slate-200 border border-r-0 border-slate-300 px-2.5 py-2 rounded-l-xl text-xs font-mono font-black text-slate-700 select-none whitespace-nowrap min-h-[38px] flex items-center">
                  WH/ASSY/
                </span>
                <input type="text" v-model="returnsFilter.traySuffix" @input="onReturnSuffixInput"
                  placeholder="000003..."
                  class="w-full bg-white border border-slate-300 rounded-r-xl px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px]">
              </div>
            </div>

            <!-- 3. Filter Status Verifikasi -->
            <div>
              <label class="block text-[10px] font-bold text-slate-600 mb-1">
                <i class="fa-solid fa-shield-halved text-slate-400 mr-1"></i>Status verifikasi:
              </label>
              <select v-model="returnsFilter.status"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px] cursor-pointer">
                <option value="">Semua Status</option>
                <option value="pending">Menunggu Verifikasi Gudang</option>
                <option value="verified">Sudah Diverifikasi</option>
              </select>
            </div>

            <!-- 4. Tombol Reset Filter -->
            <div>
              <button type="button" @click="resetReturnsFilter"
                class="w-full bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold px-3 py-2 rounded-xl text-xs transition flex items-center justify-center gap-1.5 min-h-[38px] cursor-pointer shadow-xs active:scale-95">
                <i class="fa-solid fa-arrow-rotate-left text-xs"></i>
                <span>Reset Filter</span>
              </button>
            </div>

          </div>
        </div>

        <!-- Layout Halaman: Table List Dokumen Pengembalian -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
          <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-black text-sm sm:text-base text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-list-check text-red-600"></i>
              <span>Daftar Dokumen Pengembalian Tray</span>
              <span
                class="text-xs bg-slate-100 text-slate-700 font-mono font-bold px-2 py-0.5 rounded-full border border-slate-200">
                {{ filteredReturnsList.length }} Dokumen
              </span>
            </h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead
                class="bg-slate-50 text-slate-600 font-black uppercase text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3 px-4 text-center w-12">No.</th>
                  <th class="py-3 px-4">No. Dokumen Tray</th>
                  <th class="py-3 px-4">Nama Dokumen / Operator</th>
                  <th class="py-3 px-4">Alasan Pengembalian</th>
                  <th class="py-3 px-4 text-center">Jumlah Set</th>
                  <th class="py-3 px-4 text-center">Status Verifikasi</th>
                  <th class="py-3 px-4 text-center w-28">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="(tray, idx) in pagedReturnsList" :key="tray.id" class="hover:bg-slate-50/80 transition">
                  <td class="py-3.5 px-4 text-center font-bold text-slate-500">{{ ((pgState.returns.page - 1) *
                    pgState.returns.size) + idx + 1 }}</td>

                  <!-- No. Dokumen -->
                  <td class="py-3.5 px-4">
                    <div class="font-mono font-black text-sm text-red-700">{{ tray.tray_code }}</div>
                    <div class="text-[10px] font-mono text-slate-500">{{ tray.source_document_mo || 'Tanpa MO' }}</div>
                  </td>

                  <!-- Nama Dokumen / Operator -->
                  <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-900">{{ tray.assigned_operator || '-' }}</div>
                    <div class="text-[11px] text-slate-500">
                      PIC WH: <span class="font-semibold text-slate-700">{{ tray.pic_warehouse }}</span> &bull; {{
                      formatDateTime(tray.created_at) }}
                    </div>
                  </td>

                  <!-- Alasan Pengembalian -->
                  <td class="py-3.5 px-4 max-w-xs">
                    <div
                      class="text-xs text-slate-700 bg-amber-50/70 text-amber-950 p-2 rounded-lg border border-amber-200/80 leading-relaxed">
                      {{ tray.return_reason || 'Dikembalikan ke gudang oleh operator assembly' }}
                    </div>
                  </td>

                  <!-- Jumlah Set -->
                  <td class="py-3.5 px-4 text-center">
                    <div class="flex flex-col items-center gap-1">
                      <span
                        class="font-mono font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                        {{ tray.total_sets || 0 }} Set Dikembalikan
                      </span>
                      <span v-if="tray.finished_count > 0"
                        class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                        <i class="fa-solid fa-check-double mr-1"></i>{{ tray.finished_count }} Set Sudah Dirakit
                      </span>
                    </div>
                  </td>

                  <!-- Status Verifikasi -->
                  <td class="py-3.5 px-4 text-center">
                    <span v-if="tray.return_verified"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                      <i class="fa-solid fa-circle-check text-emerald-600"></i>
                      Sudah Diverifikasi
                    </span>
                    <span v-else
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                      <i class="fa-solid fa-clock text-amber-600"></i>
                      Menunggu Verifikasi Gudang
                    </span>
                  </td>

                  <!-- Button Detail -->
                  <td class="py-3.5 px-4 text-center">
                    <button type="button" @click="openReturnDetailModal(tray)"
                      class="bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200 font-bold px-3 py-1.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 mx-auto cursor-pointer shadow-xs active:scale-95">
                      <i class="fa-solid fa-file-lines text-xs"></i>
                      <span>Detail</span>
                    </button>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="filteredReturnsList.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-500">
                    <div class="flex flex-col items-center justify-center space-y-2">
                      <div
                        class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                        <i class="fa-solid fa-box-open"></i>
                      </div>
                      <p class="text-xs font-bold text-slate-700">Tidak ada data dokumen pengembalian barang yang cocok
                        dengan filter.</p>
                      <p class="text-[11px] text-slate-400">Silakan sesuaikan filter operator atau nomor dokumen
                        WH/ASSY.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination: returns (Pengembalian Barang) -->
          <div
            class="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl select-none no-print">
            <span class="text-[11px] text-slate-500 font-semibold">
              Hal <strong class="text-slate-700">{{ pgState.returns.page }}</strong> / {{ totalPgReturns }}
              <span class="text-slate-400 ml-1">({{ (filteredReturnsList||[]).length }} data)</span>
            </span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.returns.page = 1" :disabled="pgState.returns.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">
                <i class="fa-solid fa-angles-left"></i>
              </button>
              <button type="button" @click="pgState.returns.page--" :disabled="pgState.returns.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">
                <i class="fa-solid fa-angle-left"></i>
              </button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.returns.page }}</span>
              <button type="button" @click="pgState.returns.page++" :disabled="pgState.returns.page >= totalPgReturns"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">
                <i class="fa-solid fa-angle-right"></i>
              </button>
              <button type="button" @click="pgState.returns.page = totalPgReturns"
                :disabled="pgState.returns.page >= totalPgReturns"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">
                <i class="fa-solid fa-angles-right"></i>
              </button>
            </div>
          </div>
        </div>

      </section>


@endverbatim
