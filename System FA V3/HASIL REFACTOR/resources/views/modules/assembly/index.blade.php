@verbatim

      <!-- ================= TAB 1: WORKSTATION OPERATOR ASSEMBLY ================= -->
      <section v-if="currentTab === 'assembly'" class="space-y-4 sm:space-y-6">

        <!-- Summary KPI Cards Perakitan -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <!-- Good / Selesai Dirakit -->
          <div
            class="bg-gradient-to-br from-emerald-50 to-emerald-100/40 p-4 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Good (Dirakit
                Baik)</span>
              <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{ assemblyOverallCounts.good
                }}</span>
              <span class="text-[10px] text-emerald-700 block mt-0.5">Selesai rakit tanpa cacat</span>
            </div>
            <div
              class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs">
              <i class="fa-solid fa-circle-check"></i>
            </div>
          </div>

          <!-- Hold Perakitan -->
          <div
            class="bg-gradient-to-br from-amber-50 to-amber-100/40 p-4 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Hold Perakitan</span>
              <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{ assemblyOverallCounts.hold
                }}</span>
              <span class="text-[10px] text-amber-700 block mt-0.5">Menunggu part pengganti</span>
            </div>
            <div
              class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">
              <i class="fa-solid fa-hand"></i>
            </div>
          </div>

          <!-- Scrap / Defect -->
          <div
            class="bg-gradient-to-br from-rose-50 to-rose-100/40 p-4 rounded-2xl border border-rose-200 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-rose-800 block">Scrap / Defect</span>
              <span class="text-xl sm:text-2xl font-black text-rose-950 font-mono">{{ assemblyOverallCounts.scrap
                }}</span>
              <span class="text-[10px] text-rose-700 block mt-0.5">Part rusak/tidak terpakai</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm shadow-xs">
              <i class="fa-solid fa-circle-xmark"></i>
            </div>
          </div>

          <!-- Total Dirakit -->
          <div
            class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Dirakit</span>
              <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{ assemblyOverallCounts.total
                }}</span>
              <span class="text-[10px] text-slate-500 block mt-0.5">Seluruh unit selesai rakit</span>
            </div>
            <div
              class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-xs">
              <i class="fa-solid fa-boxes-packing"></i>
            </div>
          </div>
        </div>

        <!-- Top Tray Selection & Header Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Workstation Perakitan P9</h2>
            </div>

            <!-- Tray Selection Dropdown with Live Search Filter -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">

              <!-- Filter per Operator untuk Akses Gudang, QC & Admin -->
              <div v-if="['admin', 'super admin', 'gudang', 'qc'].includes(String(userRole || '').toLowerCase().trim()) || String(userRole || '').toLowerCase().includes('admin')" class="w-full sm:w-60">
                <label class="text-[10px] font-bold text-slate-500 block mb-1">
                  <i class="fa-solid fa-user-gear mr-1 text-slate-400"></i>Filter operator:
                </label>
                <select v-model="selectedOperatorFilter" @change="onOperatorFilterChange"
                  class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-300 text-slate-900 font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-sm transition min-h-[42px] cursor-pointer">
                  <option value="">Semua operator assembly</option>
                  <option v-for="op in operatorUsersList" :key="op.username" :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
              </div>

              <div class="relative w-full sm:w-72" ref="trayDropdownRef">
                <label class="text-[10px] font-bold text-slate-500 block mb-1">
                  Pilih nomor tray aktif:
                </label>

                <div class="relative">
                  <button type="button" @click="toggleTrayDropdown"
                    class="w-full bg-slate-50 hover:bg-slate-100 border border-slate-300 text-slate-900 font-bold text-xs px-3.5 py-2.5 rounded-xl flex items-center justify-between shadow-sm transition min-h-[42px]">
                    <span class="truncate"
                      :class="selectedTrayCode ? 'font-mono font-bold text-slate-900' : 'text-slate-500 font-medium'">
                      {{ selectedTrayCode ? (isPUTray(selectedTrayCode) ? getPUDocNumber(selectedTrayCode) :
                      selectedTrayCode) : 'Pilih Tray' }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs ml-2"></i>
                  </button>

                  <!-- Searchable Floating Dropdown Menu -->
                  <div v-if="isTrayDropdownOpen"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 p-2 space-y-2 max-h-72 flex flex-col">
                    <div class="relative">
                      <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                      <input type="text" v-model="traySearchText" placeholder="Ketik nomor tray / PIC..."
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-600 min-h-[36px]"
                        @click.stop>
                    </div>
                    <div class="overflow-y-auto space-y-1 divide-y divide-slate-100 flex-grow max-h-56">
                      <button v-for="t in filteredTraysList" :key="t.tray_code"
                        @click="selectTrayFromDropdown(t.tray_code)"
                        class="w-full text-left px-3 py-2 rounded-xl text-xs hover:bg-red-50 hover:text-red-700 transition flex items-center justify-between font-mono"
                        :class="selectedTrayCode === t.tray_code ? 'bg-red-50 text-red-700 font-bold' : 'text-slate-700'">
                        <div>
                          <div class="font-bold">{{ isPUTray(t) ? getPUDocNumber(t.tray_code) : t.tray_code }}</div>
                          <div class="text-[10px] text-slate-500 font-sans">
                            PIC: {{ t.pic_warehouse || '-' }} |
                            {{ t.finished_count || 0 }}/{{ (t.total_sets !== undefined && t.total_sets !== null) ?
                            t.total_sets : 0 }} Set Selesai
                          </div>
                        </div>
                        <div class="flex items-center gap-1 flex-wrap justify-end">
                          <span v-if="t.session_status === 'COMPLETED_SHIFT'"
                            class="text-[9px] bg-indigo-100 text-indigo-800 border border-indigo-300 px-1.5 py-0.5 rounded font-sans font-bold flex items-center gap-1">
                            <i class="fa-solid fa-people-arrows text-[8px]"></i> Shift Selesai
                          </span>
                          <span v-else-if="t.session_status === 'RETURNED_TO_WAREHOUSE'"
                            class="text-[9px] bg-amber-100 text-amber-800 border border-amber-300 px-1.5 py-0.5 rounded font-sans font-bold flex items-center gap-1">
                            <i class="fa-solid fa-boxes-packing text-[8px]"></i> Selesai (Sisa Kembali)
                          </span>
                          <span v-if="t.hold_count > 0"
                            class="text-[9px] bg-amber-100 text-amber-800 border border-amber-300 px-1.5 py-0.5 rounded font-sans font-bold flex items-center gap-1">
                            <i class="fa-solid fa-hand text-[8px]"></i> Hold ({{ t.hold_count }})
                          </span>
                          <span v-if="t.scrap_count > 0"
                            class="text-[9px] bg-red-100 text-red-800 border border-red-300 px-1.5 py-0.5 rounded font-sans font-bold flex items-center gap-1">
                            <i class="fa-solid fa-trash-can text-[8px]"></i> Scrap ({{ t.scrap_count }})
                          </span>
                          <span v-if="t.rework_count > 0"
                            class="text-[9px] bg-purple-100 text-purple-800 border border-purple-300 px-1.5 py-0.5 rounded font-sans font-bold flex items-center gap-1">
                            <i class="fa-solid fa-rotate-left text-[8px]"></i> Rework ({{ t.rework_count }})
                          </span>
                          <span v-if="t.session_status === 'COMPLETED' || (t.finished_count >= (t.total_sets || 22) && (t.total_sets || 22) > 0)"
                            class="text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-300 px-1.5 py-0.5 rounded font-sans font-bold">
                            Selesai
                          </span>
                          <span v-else-if="!t.hold_count && !t.scrap_count && !t.rework_count && t.finished_count === 0"
                            class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-sans">
                            Baru
                          </span>
                          <span v-else-if="!t.hold_count && !t.scrap_count && !t.rework_count"
                            class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-sans">
                            Proses ({{ t.finished_count || 0 }}/{{ t.total_sets || 22 }})
                          </span>
                        </div>
                      </button>
                      <div v-if="filteredTraysList.length === 0" class="p-3 text-center text-xs text-slate-400">
                        Tray tidak ditemukan
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Operator Name -->
              <div class="w-full sm:w-auto flex flex-col justify-end">
                <label class="text-[10px] font-bold text-slate-500 block mb-1">
                  {{ (userRole === 'gudang' || userRole === 'qc') ? (userRole === 'qc' ? 'Inspector QC (monitoring):' :
                  'Petugas gudang (monitoring):') : 'Operator saat ini:' }}
                </label>
                <div
                  class="bg-slate-100 border border-slate-300 text-slate-800 font-bold text-xs px-3.5 py-2.5 rounded-xl min-h-[42px] flex items-center justify-between gap-2">
                  <div class="flex items-center">
                    <i
                      :class="userRole === 'qc' ? 'fa-solid fa-list-check text-emerald-600 mr-2' : (userRole === 'gudang' ? 'fa-solid fa-warehouse text-red-600 mr-2' : 'fa-solid fa-user-gear text-red-600 mr-2')"></i>
                    <span>{{ userFullName || userName }}</span>
                  </div>
                  <span v-if="userRole === 'gudang' || userRole === 'qc'"
                    :class="userRole === 'qc' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-red-100 text-red-700 border-red-200'"
                    class="text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider border">
                    {{ userRole === 'qc' ? 'QC' : 'Gudang' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Sesi Selesai (Serah Terima Shift) Toolbar -->
          <div v-if="currentTray && currentTray.session_status === 'COMPLETED_SHIFT'"
            class="flex flex-wrap items-center justify-between gap-2.5 bg-indigo-50 border border-indigo-300 p-3 rounded-xl text-indigo-900 shadow-xs">
            <div class="flex items-center space-x-2">
              <i class="fa-solid fa-people-arrows text-indigo-600 text-base"></i>
              <span class="text-xs font-bold">Status Sesi: Serah Terima Shift Selesai.a Sisa rakitan telah dipindahkan
                ke operator selanjutnya</span>
            </div>
            <span
              class="bg-indigo-200/80 text-indigo-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border border-indigo-400 font-mono">
              {{ finishedSlotsCount }} Set Dirakit Tersimpan
            </span>
          </div>

          <!-- Status Sesi Selesai (Sisa Kembali ke Gudang) Toolbar -->
          <div v-else-if="currentTray && currentTray.session_status === 'RETURNED_TO_WAREHOUSE'"
            class="flex flex-wrap items-center justify-between gap-2.5 bg-amber-50 border border-amber-300 p-3 rounded-xl text-amber-900 shadow-xs">
            <div class="flex items-center space-x-2">
              <i class="fa-solid fa-boxes-packing text-amber-600 text-base"></i>
              <span class="text-xs font-bold">Status Sesi Tray: Selesai • Sisa part belum terakit telah dikembalikan ke
                Gudang</span>
            </div>
            <span
              class="bg-amber-200/80 text-amber-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border border-amber-400 font-mono">
              {{ finishedSlotsCount }} Set Dirakit Tersimpan
            </span>
          </div>

          <!-- Selesaikan Sesi & Jeda Toolbar (Hanya untuk Operator / Admin saat Tray Aktif) -->
          <div v-if="currentTray && userRole !== 'gudang' && userRole !== 'qc' && !isTrayLocked"
            class="flex flex-wrap items-center justify-between gap-2.5 bg-slate-50 border border-slate-200 p-3 rounded-xl">
            <div class="flex items-center space-x-2">
              <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-stopwatch text-red-600"></i>
                <span>Kontrol Sesi Pengerjaan Tray:</span>
              </span>
              <span v-if="isWorkstationPaused"
                class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-md animate-pulse">
                Sedang Dijeda ({{ pausedDurationDisplay }})
              </span>
              <span v-else
                class="bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md">
                Sesi Aktif Berjalan
              </span>
            </div>

            <div class="flex items-center gap-2">
              <!-- Tombol Jeda / Lanjutkan -->
              <button v-if="!isWorkstationPaused" type="button" @click="openPauseModal"
                class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-pause"></i>
                <span>Jeda Pengerjaan</span>
              </button>
              <button v-else type="button" @click="resumeAssemblyWork"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/30 transition active:scale-95 flex items-center gap-1.5 cursor-pointer animate-bounce">
                <i class="fa-solid fa-play"></i>
                <span>Lanjutkan Pengerjaan</span>
              </button>

              <!-- Tombol Selesaikan Sesi -->
              <button type="button" @click="openSessionModal"
                class="bg-slate-800 hover:bg-slate-900 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-sm transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-flag-checkered text-red-400"></i>
                <span>Selesaikan Sesi</span>
              </button>
            </div>
          </div>

          <!-- Status Monitoring Toolbar Khusus Gudang & QC (Read Only) -->
          <div v-if="currentTray && (userRole === 'gudang' || userRole === 'qc')"
            class="flex flex-wrap items-center justify-between gap-2.5 bg-slate-900 text-white p-3.5 rounded-2xl shadow-md border border-slate-800">
            <div class="flex items-center space-x-2.5">
              <div
                class="w-8 h-8 rounded-xl bg-red-600/30 border border-red-500/40 text-red-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                <i class="fa-solid fa-eye text-red-400"></i>
              </div>
              <div>
                <span class="text-xs font-black uppercase tracking-wider block text-slate-200">Mode Monitoring {{
                  userRole === 'qc' ? 'QC' : 'Gudang' }}
                  (Real-Time)</span>
                <p class="text-[10.5px] text-slate-400">{{ userRole === 'qc' ? 'Seluruh proses perakitan hanya dapat dilihat untuk kesesuaian mutu.' : 'Seluruh status pengerjaan operator, jeda pengerjaan, dan slot card terhubung secara langsung.' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="isWorkstationPaused"
                class="bg-amber-500/20 text-amber-300 border border-amber-500/40 font-black text-xs uppercase px-3 py-1.5 rounded-xl flex items-center gap-1.5 animate-pulse">
                <i class="fa-solid fa-pause"></i> Sedang Dijeda ({{ pausedDurationDisplay }})
              </span>
              <span v-else
                class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold text-xs uppercase px-3 py-1.5 rounded-xl flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                  <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                Operator Sedang Merakit (Aktif)
              </span>
            </div>
          </div>

          <!-- Paused Banner Alert (Jika Sedang Jeda) -->
          <div v-if="isWorkstationPaused"
            class="bg-amber-500 text-slate-950 p-4 rounded-2xl shadow-md border-2 border-amber-400 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
              <div
                class="w-10 h-10 rounded-xl bg-slate-950 text-amber-400 flex items-center justify-center text-lg font-bold flex-shrink-0">
                <i class="fa-solid fa-pause"></i>
              </div>
              <div>
                <h4 class="font-black text-sm sm:text-base leading-tight">Pengerjaan Perakitan Sedang Dijeda</h4>
                <p class="text-xs font-semibold text-slate-900">Alasan: <strong>{{ activePauseReason || 'Istirahat / Jeda' }}</strong> &bull; Waktu Berhenti: <strong class="font-mono">{{ pausedDurationDisplay
                    }}</strong></p>
              </div>
            </div>

          </div>

          <!-- Tray Metadata & Progress Bar -->
          <div v-if="currentTray"
            class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 border border-slate-200 p-3.5 sm:p-4 rounded-xl text-xs">
            <div>
              <span class="text-slate-500 text-[10px] font-bold block">Status tray</span>
              <span v-if="currentTray.session_status === 'COMPLETED_SHIFT'"
                class="text-indigo-800 font-bold flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-people-arrows text-indigo-600"></i> Shift Selesai
              </span>
              <span v-else-if="currentTray.session_status === 'RETURNED_TO_WAREHOUSE'"
                class="text-amber-800 font-bold flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-boxes-packing text-amber-600"></i> Selesai (Sisa Kembali)
              </span>
              <span v-else-if="currentTray.session_status === 'COMPLETED' || isTrayFullyFinished" class="text-emerald-800 font-bold flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> Selesai Dirakit
              </span>
              <span v-else-if="hasHoldItem" class="text-amber-700 font-bold flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-triangle-exclamation"></i> Ada Item HOLD
              </span>
              <span v-else class="text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-spinner fa-spin"></i> Dalam Pengerjaan
              </span>
            </div>

            <div>
              <span class="text-slate-500 text-[10px] font-bold block">Source doc MO</span>
              <span class="font-mono font-bold text-slate-800 mt-0.5 block">{{ currentTray.source_document_mo || '-'
                }}</span>
            </div>

            <div>
              <span class="text-slate-500 text-[10px] font-bold block">Ditugaskan ke</span>
              <span class="font-bold text-slate-800 mt-0.5 block">
                {{ currentTray.assigned_operator ? currentTray.assigned_operator : 'Semua Operator' }}
              </span>
            </div>

            <div>
              <span class="text-slate-500 text-[10px] font-bold block">Kemajuan rakitan</span>
              <div class="flex items-center space-x-2 mt-0.5">
                <strong class="text-slate-900 font-mono">{{ finishedSlotsCount }} / {{ currentTraySetsCount }}
                  Set</strong>
                <span class="text-[10px] text-slate-500">({{ Math.round((finishedSlotsCount / (currentTraySetsCount ||
                  1)) * 100) }}%)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid Cards of Sets in Active Tray -->
        <div v-if="currentTray" class="space-y-4">

          <!-- Shift Handover Completed Notice Banner -->
          <div v-if="currentTray && currentTray.session_status === 'COMPLETED_SHIFT'"
            class="bg-indigo-50 border border-indigo-300 text-indigo-950 text-xs p-3.5 sm:p-4 rounded-xl flex items-start space-x-3 shadow-xs">
            <i class="fa-solid fa-people-arrows text-indigo-600 text-lg mt-0.5 flex-shrink-0"></i>
            <div>
              <strong class="font-bold text-sm text-indigo-950">Riwayat Perakitan Tersimpan.</strong>
            </div>
          </div>

          <!-- Returned Tray Notice Banner -->
          <div v-else-if="currentTray && currentTray.session_status === 'RETURNED_TO_WAREHOUSE'"
            class="bg-amber-50 border border-amber-300 text-amber-950 text-xs p-3.5 sm:p-4 rounded-xl flex items-start space-x-3 shadow-xs">
            <i class="fa-solid fa-boxes-packing text-amber-600 text-lg mt-0.5 flex-shrink-0"></i>
            <div>
              <strong class="font-bold text-sm text-amber-950">Tray Selesai. Riwayat Perakitan Tersimpan:</strong>
              <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                Tray ini telah diselesaikan. Sebanyak <strong>{{ finishedSlotsCount }} set</strong> yang telah dirakit
                tetap tersimpan aman di riwayat perakitan ini, sedangkan set sisa yang belum dirakit telah dikembalikan
                ke Gudang. Seluruh data rakitan di bawah ini dapat dilihat detailnya.
              </p>
            </div>
          </div>

          <!-- Sequential Lock Notice Banner -->
          <div v-else
            class="bg-red-50 border border-red-200 text-red-800 text-xs p-3 sm:p-4 rounded-xl flex items-start space-x-2.5">
            <i class="fa-solid fa-shield-halved text-red-600 text-base mt-0.5 flex-shrink-0"></i>
            <div>
              <strong>Aturan Sistem Perakitan Berurutan:</strong>
              <p class="text-[11px] text-red-700 mt-0.5">
                Rakitan harus diselesaikan secara berurutan mulai dari Set #1 hingga Set #{{ currentTraySetsCount }}.
                Slot berikutnya hanya akan terbuka jika slot sebelumnya telah selesai dirakit.
              </p>
            </div>
          </div>

          <!-- Sets Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5 sm:gap-4">
            <div v-for="(slot, index) in traySlots" :key="index" :class="getItemCardClass(slot, index)"
              class="border rounded-2xl p-4 shadow-sm transition-all duration-200 flex flex-col justify-between relative overflow-hidden min-h-[220px]">

              <!-- Set Header Badge -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                <div class="flex items-center space-x-2">
                  <span class="bg-slate-900 text-white text-[11px] font-black px-2 py-0.5 rounded-lg font-mono">
                    #{{ index + 1 }}
                  </span>
                  <span class="text-xs font-bold text-slate-700">Set Rakitan</span>
                </div>

                <!-- Status Badge -->
                <div v-if="slot.status" class="flex items-center gap-1">
                  <span v-if="slot.status === 'RETURNED'"
                    class="text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border bg-amber-100 text-amber-800 border-amber-300 flex items-center gap-1">
                    <i class="fa-solid fa-boxes-packing text-[9px]"></i> Dikembalikan
                  </span>
                  <template v-else>
                    <!-- Dokumen Badge (Tetap tampil untuk unit Good maupun unit PU / Dirakit Ulang) -->
                    <span
                      v-if="slot.assy_doc_number || ((slot.status === 'Dirakit Ulang' || slot.qc_status === 'RU' || (typeof isReassemblySet === 'function' && isReassemblySet(slot))) && currentTray)"
                      class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-lg border bg-indigo-50 text-indigo-800 border-indigo-200"
                      :title="'Dokumen: ' + ((slot.status === 'Dirakit Ulang' || slot.qc_status === 'RU' || (typeof isReassemblySet === 'function' && isReassemblySet(slot))) ? getFormattedDocCode(currentTray.tray_code, 'assy_rework') : slot.assy_doc_number)">
                      <i :class="slot.status === 'Good' ? 'fa-solid fa-circle-check text-indigo-600' : 'fa-solid fa-file-lines text-indigo-600'"
                        class="mr-0.5"></i>
                      {{ (slot.status === 'Dirakit Ulang' || slot.qc_status === 'RU' || (typeof isReassemblySet ===
                      'function' && isReassemblySet(slot))) ? getFormattedDocCode(currentTray.tray_code, 'assy_rework')
                      : slot.assy_doc_number }}
                    </span>
                    <span :class="getStatusBadgeClass(slot.original_assembly_status || slot.status)"
                      class="text-[10px] font-black uppercase px-2 py-0.5 rounded-lg border">
                      {{ slot.original_assembly_status || slot.status }}
                    </span>
                  </template>
                </div>
                <!-- Slot Sedang Dikerjakan (In Progress) -->
                <span v-else-if="slot.started_at"
                  class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-300 px-2 py-0.5 rounded-lg flex items-center gap-1.5 shadow-sm">
                  <span class="relative flex h-2 w-2">
                    <span
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                  </span>
                  Sedang Dikerjakan
                </span>
                <!-- Slot Siap Dirakit -->
                <span v-else-if="index === firstUnfinishedIndex"
                  class="text-[10px] font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Siap Dirakit
                </span>
                <span v-else class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-lg">
                  Menunggu
                </span>
              </div>

              <!-- Serial Numbers Details -->
              <div class="space-y-1.5 text-xs mb-3 flex-grow font-mono">
                <div :class="getDefectivePartClass(slot, '15')"
                  class="flex justify-between items-center p-1.5 rounded-lg border transition-all">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-sans font-bold" :class="getDefectiveLabelClass(slot, '15')">P9 Part
                      15:</span>
                    <span v-if="isPartDefective(slot, '15')" :class="getDefectiveBadgeClass(slot)"
                      class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded leading-none uppercase">
                      {{ slot.original_assembly_status || slot.status }}
                    </span>
                    <span v-else-if="(slot.original_assembly_status || slot.status) && (slot.original_assembly_status || slot.status) !== 'Good'"
                      class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 leading-none">
                      <i class="fa-solid fa-check text-[8px]"></i> Aman
                    </span>
                  </div>
                  <strong :class="isPartDefective(slot, '15') ? 'text-slate-950 font-black' : 'text-slate-900'">{{
                    slot.part15_sn || '-' }}</strong>
                </div>

                <div :class="getDefectivePartClass(slot, '1')"
                  class="flex justify-between items-center p-1.5 rounded-lg border transition-all">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-sans font-bold" :class="getDefectiveLabelClass(slot, '1')">P9 Part
                      1:</span>
                    <span v-if="isPartDefective(slot, '1')" :class="getDefectiveBadgeClass(slot)"
                      class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded leading-none uppercase">
                      {{ slot.original_assembly_status || slot.status }}
                    </span>
                    <span v-else-if="(slot.original_assembly_status || slot.status) && (slot.original_assembly_status || slot.status) !== 'Good'"
                      class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 leading-none">
                      <i class="fa-solid fa-check text-[8px]"></i> Aman
                    </span>
                  </div>
                  <strong :class="isPartDefective(slot, '1') ? 'text-slate-950 font-black' : 'text-slate-800'">{{
                    slot.part1_sn || '-' }}</strong>
                </div>

                <div :class="getDefectivePartClass(slot, '12')"
                  class="flex justify-between items-center p-1.5 rounded-lg border transition-all">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-sans font-bold" :class="getDefectiveLabelClass(slot, '12')">P9 Part
                      12:</span>
                    <span v-if="isPartDefective(slot, '12')" :class="getDefectiveBadgeClass(slot)"
                      class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded leading-none uppercase">
                      {{ slot.original_assembly_status || slot.status }}
                    </span>
                    <span v-else-if="(slot.original_assembly_status || slot.status) && (slot.original_assembly_status || slot.status) !== 'Good'"
                      class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 leading-none">
                      <i class="fa-solid fa-check text-[8px]"></i> Aman
                    </span>
                  </div>
                  <strong :class="isPartDefective(slot, '12') ? 'text-slate-950 font-black' : 'text-slate-800'">{{
                    slot.part12_sn || '-' }}</strong>
                </div>
              </div>

              <!-- Card Actions -->
              <!-- 1. JIKA STATUS RETURNED (DIKEMBALIKAN KE GUDANG) -->
              <div v-if="slot.status === 'RETURNED'" class="mt-auto">
                <div
                  class="w-full bg-amber-50 border border-amber-200 text-amber-800 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-1.5 min-h-[38px]">
                  <i class="fa-solid fa-boxes-packing text-amber-600"></i>
                  <span>Dikembalikan ke Gudang</span>
                </div>
              </div>

              <!-- 2. JIKA SUDAH SELESAI (Status Ada) -> HANYA ADA TOMBOL DETAIL -->
              <div v-else-if="slot.status || slot.finished_at" class="mt-auto">
                <button @click="openDetailModal(slot, index)"
                  class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 border border-slate-300 min-h-[38px] active:scale-95 shadow-xs cursor-pointer">
                  <i class="fa-solid fa-eye text-slate-500"></i>
                  <span>Lihat Detail</span>
                </button>
              </div>

              <!-- 2. JIKA BELUM SELESAI -->
              <div v-else class="mt-auto">
                <!-- Mode Gudang & QC (Hanya Memantau, Tidak Bisa Ikut Merakit) -->
                <div v-if="userRole === 'gudang' || userRole === 'qc'" class="w-full">
                  <div v-if="slot.started_at"
                    class="w-full bg-amber-50 border border-amber-300 text-amber-800 font-bold py-2.5 px-2 rounded-xl text-xs flex items-center justify-center space-x-2 shadow-sm min-h-[40px]">
                    <span class="relative flex h-2 w-2 flex-shrink-0">
                      <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                    <span class="truncate">Sedang Dirakit Operator</span>
                  </div>
                  <div v-else
                    class="w-full bg-slate-100 text-slate-500 font-semibold py-2.5 px-2 rounded-xl text-xs border border-slate-200 min-h-[40px] flex items-center justify-center space-x-1.5 opacity-80">
                    <i class="fa-solid fa-clock text-slate-400 text-xs"></i>
                    <span>Belum Dirakit</span>
                  </div>
                </div>

                <!-- Mode Operator Assembly / Admin -->
                <template v-else>
                  <!-- Sedang Dikerjakan -> Dua tombol: [Sedang Dikerjakan] & [Selesai] di ujung kanan card -->
                  <div v-if="!isSlotLocked(index) && slot.started_at" class="grid grid-cols-5 gap-1.5">
                    <button @click="openAssemblyModal(index)"
                      class="col-span-3 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-2 rounded-xl text-xs shadow-md shadow-amber-500/25 transition flex items-center justify-center space-x-1.5 active:scale-95 min-h-[40px]">
                      <span class="relative flex h-2 w-2 flex-shrink-0">
                        <span
                          class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                      </span>
                      <span class="truncate">Sedang Dikerjakan</span>
                    </button>
                    <button @click="quickFinishSlot(index)" title="Selesaikan perakitan langsung dari card"
                      class="col-span-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-2 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-1 active:scale-95 min-h-[40px]">
                      <i class="fa-solid fa-circle-check text-xs"></i>
                      <span>Selesai</span>
                    </button>
                  </div>

                  <!-- Belum Dimulai -> Button Pilih -->
                  <button v-else-if="!isSlotLocked(index)" @click="openAssemblyModal(index)"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-2 active:scale-95 min-h-[40px]">
                    <i class="fa-solid fa-arrow-pointer"></i>
                    <span>Pilih</span>
                  </button>

                  <!-- Terkunci Antrian Berurutan -->
                  <button v-else disabled
                    class="w-full bg-slate-100 text-slate-400 font-semibold py-2.5 rounded-xl text-xs border border-slate-200 min-h-[40px] cursor-not-allowed opacity-60 flex items-center justify-center space-x-1">
                    <i class="fa-solid fa-lock text-xs mr-1"></i>
                    <span>Terkunci (Set #{{ firstUnfinishedIndex + 1 }} Belum Selesai)</span>
                  </button>
                </template>
              </div>

            </div>
          </div>

        </div>

        <div v-else
          class="bg-white border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-500 shadow-sm">
          <i class="fa-solid fa-box-archive text-3xl sm:text-4xl mb-3 text-slate-300"></i>
          <p class="text-sm sm:text-base font-bold text-slate-800 mb-1">Belum ada Tray yang dipilih</p>
          <p class="text-xs text-slate-500">Pilih No. Doc Tray di atas untuk mulai merakit.</p>
        </div>

      </section>


@endverbatim
