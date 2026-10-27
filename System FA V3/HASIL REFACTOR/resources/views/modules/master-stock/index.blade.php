@verbatim
      <!-- ================= TAB 5: MASTER DB INVENTORY MONITOR ================= -->
      <section v-if="currentTab === 'master'" class="space-y-4 sm:space-y-6">

        <!-- Admin Danger Zone Reset Banner (Visible only for Admin) -->
        <div
          v-if="userRole === 'admin' || userRole === 'Super Admin' || String(userRole || '').toLowerCase().includes('admin') || userDepartment === 'administrator'"
          class="bg-red-50/80 border-2 border-red-200 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
          <div class="flex items-center space-x-3 text-red-800">
            <div
              class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-lg flex-shrink-0">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
              <h4 class="font-black text-sm sm:text-base leading-tight">Danger Zone: Bersihkan Database Tray & Rakitan
              </h4>
              <p class="text-xs text-red-600 font-medium">Kosongkan seluruh data Tray & riwayat Perakitan dan kembalikan
                9.000 Master Stock ke status <strong>Tersedia</strong>.</p>
            </div>
          </div>
          <button @click="showResetDbModal = true"
            class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded-xl text-xs shadow-md shadow-red-600/30 transition flex items-center justify-center space-x-1.5 self-start sm:self-auto flex-shrink-0 active:scale-95 min-h-[38px]">
            <i class="fa-solid fa-trash-can"></i>
            <span>Kosongkan Database Tray</span>
          </button>
        </div>

        <!-- Quick Hint Bar -->
        <div
          class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-600 flex items-center justify-between">
          <span class="flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-red-600 text-sm"></i>
            <span><strong>Tips Tracking:</strong> Klik langsung pada baris part di tabel untuk melihat <strong>Detail
                &amp; Tracking Riwayat Proses</strong> (alur pengerjaan, alasan, status perakitan, dan log pergantian).
              Klik badge status untuk melihat popup ringkasan status.</span>
          </span>
          <span></span>
        </div>

        <!-- 3 Summary Stat Cards with 6 Interactive Status Badges (Part 1, Part 12, Part 15) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5 sm:gap-4">

          <!-- Part 1 Card -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2.5">
                <div class="bg-red-50 text-red-700 p-2.5 rounded-xl text-base font-bold">
                  <i class="fa-solid fa-cube"></i>
                </div>
                <div>
                  <h3 class="font-black text-slate-900 text-sm sm:text-base">P9 Part 1</h3>
                  <p class="text-[10px] text-slate-500 font-mono">Rentang: 001-999 & 1000-6000</p>
                </div>
              </div>
              <span class="text-xs font-black font-mono bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg">
                Total: {{ stockSummary['1'] ? stockSummary['1'].total : 6000 }}
              </span>
            </div>

            <!-- 6 Interactive Status Buttons -->
            <div class="grid grid-cols-3 gap-1.5 text-center text-xs pt-1 border-t border-slate-100">
              <button type="button" @click="openStatusDrilldown('1', 'Tersedia')"
                class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 p-1.5 rounded-xl border border-emerald-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Tersedia</span>
                <strong class="font-mono text-xs">{{ stockSummary['1'] ? stockSummary['1'].available : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('1', 'Supplied')"
                class="bg-blue-50 hover:bg-blue-100 text-blue-800 p-1.5 rounded-xl border border-blue-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Supplied</span>
                <strong class="font-mono text-xs">{{ stockSummary['1'] ? stockSummary['1'].allocated : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('1', 'Good')"
                class="bg-teal-50 hover:bg-teal-100 text-teal-800 p-1.5 rounded-xl border border-teal-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Good</span>
                <strong class="font-mono text-xs">{{ stockSummary['1'] ? (stockSummary['1'].good_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('1', 'Hold')"
                class="bg-amber-50 hover:bg-amber-100 text-amber-800 p-1.5 rounded-xl border border-amber-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Hold</span>
                <strong class="font-mono text-xs">{{ stockSummary['1'] ? (stockSummary['1'].hold_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('1', 'Scrap')"
                class="bg-red-50 hover:bg-red-100 text-red-800 p-1.5 rounded-xl border border-red-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Scrap</span>
                <strong class="font-mono text-xs">{{ stockSummary['1'] ? (stockSummary['1'].scrap_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('1', 'Decline')"
                class="bg-rose-50 hover:bg-rose-100 text-rose-800 p-1.5 rounded-xl border border-rose-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block text-rose-700">Decline</span>
                <strong class="font-mono text-xs text-rose-900">{{ stockSummary['1'] ? (stockSummary['1'].decline_count
                  || 0) : 0 }}</strong>
              </button>
            </div>
          </div>

          <!-- Part 12 Card (Unified) -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2.5">
                <div class="bg-red-50 text-red-700 p-2.5 rounded-xl text-base font-bold">
                  <i class="fa-solid fa-cubes"></i>
                </div>
                <div>
                  <h3 class="font-black text-slate-900 text-sm sm:text-base">P9 Part 12</h3>
                  <p class="text-[10px] text-slate-500 font-mono">60001-63500 & 70001-71500</p>
                </div>
              </div>
              <span class="text-xs font-black font-mono bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg">
                Total: {{ stockSummary['12'] ? stockSummary['12'].total : 5000 }}
              </span>
            </div>

            <!-- 6 Interactive Status Buttons -->
            <div class="grid grid-cols-3 gap-1.5 text-center text-xs pt-1 border-t border-slate-100">
              <button type="button" @click="openStatusDrilldown('12', 'Tersedia')"
                class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 p-1.5 rounded-xl border border-emerald-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Tersedia</span>
                <strong class="font-mono text-xs">{{ stockSummary['12'] ? stockSummary['12'].available : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('12', 'Supplied')"
                class="bg-blue-50 hover:bg-blue-100 text-blue-800 p-1.5 rounded-xl border border-blue-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Supplied</span>
                <strong class="font-mono text-xs">{{ stockSummary['12'] ? stockSummary['12'].allocated : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('12', 'Good')"
                class="bg-teal-50 hover:bg-teal-100 text-teal-800 p-1.5 rounded-xl border border-teal-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Good</span>
                <strong class="font-mono text-xs">{{ stockSummary['12'] ? (stockSummary['12'].good_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('12', 'Hold')"
                class="bg-amber-50 hover:bg-amber-100 text-amber-800 p-1.5 rounded-xl border border-amber-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Hold</span>
                <strong class="font-mono text-xs">{{ stockSummary['12'] ? (stockSummary['12'].hold_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('12', 'Scrap')"
                class="bg-red-50 hover:bg-red-100 text-red-800 p-1.5 rounded-xl border border-red-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Scrap</span>
                <strong class="font-mono text-xs">{{ stockSummary['12'] ? (stockSummary['12'].scrap_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('12', 'Decline')"
                class="bg-rose-50 hover:bg-rose-100 text-rose-800 p-1.5 rounded-xl border border-rose-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block text-rose-700">Decline</span>
                <strong class="font-mono text-xs text-rose-900">{{ stockSummary['12'] ?
                  (stockSummary['12'].decline_count || 0) : 0 }}</strong>
              </button>
            </div>
          </div>

          <!-- Part 15 Card (Induk) -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-2.5">
                <div class="bg-red-50 text-red-700 p-2.5 rounded-xl text-base font-bold">
                  <i class="fa-solid fa-barcode"></i>
                </div>
                <div>
                  <h3 class="font-black text-slate-900 text-sm sm:text-base">P9 Part 15</h3>
                  <p class="text-[10px] text-slate-500 font-mono">J1100001 - Z1102000</p>
                </div>
              </div>
              <span class="text-xs font-black font-mono bg-slate-100 text-slate-800 px-2 py-0.5 rounded-lg">
                Total: {{ stockSummary['15'] ? stockSummary['15'].total : 2000 }}
              </span>
            </div>

            <!-- 6 Interactive Status Buttons -->
            <div class="grid grid-cols-3 gap-1.5 text-center text-xs pt-1 border-t border-slate-100">
              <button type="button" @click="openStatusDrilldown('15', 'Tersedia')"
                class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 p-1.5 rounded-xl border border-emerald-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Tersedia</span>
                <strong class="font-mono text-xs">{{ stockSummary['15'] ? stockSummary['15'].available : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('15', 'Supplied')"
                class="bg-blue-50 hover:bg-blue-100 text-blue-800 p-1.5 rounded-xl border border-blue-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Supplied</span>
                <strong class="font-mono text-xs">{{ stockSummary['15'] ? stockSummary['15'].allocated : 0 }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('15', 'Good')"
                class="bg-teal-50 hover:bg-teal-100 text-teal-800 p-1.5 rounded-xl border border-teal-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Good</span>
                <strong class="font-mono text-xs">{{ stockSummary['15'] ? (stockSummary['15'].good_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('15', 'Hold')"
                class="bg-amber-50 hover:bg-amber-100 text-amber-800 p-1.5 rounded-xl border border-amber-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Hold</span>
                <strong class="font-mono text-xs">{{ stockSummary['15'] ? (stockSummary['15'].hold_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('15', 'Scrap')"
                class="bg-red-50 hover:bg-red-100 text-red-800 p-1.5 rounded-xl border border-red-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block">Scrap</span>
                <strong class="font-mono text-xs">{{ stockSummary['15'] ? (stockSummary['15'].scrap_count || 0) : 0
                  }}</strong>
              </button>
              <button type="button" @click="openStatusDrilldown('15', 'Decline')"
                class="bg-rose-50 hover:bg-rose-100 text-rose-800 p-1.5 rounded-xl border border-rose-200 transition text-center active:scale-95 cursor-pointer">
                <span class="text-[9px] font-bold block text-rose-700">Decline</span>
                <strong class="font-mono text-xs text-rose-900">{{ stockSummary['15'] ?
                  (stockSummary['15'].decline_count || 0) : 0 }}</strong>
              </button>
            </div>
          </div>

        </div>

        <!-- Master Stock Search & Filter Table -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-base sm:text-lg font-black text-slate-900">Daftar Stok Master & Serial Locking</h3>

            </div>
            <div class="flex flex-wrap items-center gap-2">
              <!-- Filter Type -->
              <select v-model="masterFilter.type" @change="fetchMasterStockList(1)"
                class="bg-slate-50 border border-slate-300 text-xs font-bold rounded-xl px-3 py-2 text-slate-800 min-h-[38px] cursor-pointer focus:outline-none focus:border-red-600">
                <option value="">Semua Part</option>
                <option value="1">P9 Part 1</option>
                <option value="12">P9 Part 12</option>
                <option value="15">P9 Part 15</option>
              </select>

              <!-- Filter Status -->
              <select v-model="masterFilter.status" @change="fetchMasterStockList(1)"
                class="bg-slate-50 border border-slate-300 text-xs font-bold rounded-xl px-3 py-2 text-slate-800 min-h-[38px] cursor-pointer focus:outline-none focus:border-red-600">
                <option value="">Semua Status</option>
                <option value="Tersedia">Tersedia</option>
                <option value="Supplied">Supplied</option>
                <option value="Good">Good</option>
                <option value="Hold">Hold</option>
                <option value="Scrap">Scrap</option>
                <option value="Decline">Decline</option>
                <option value="Part Diganti">Part Diganti</option>
              </select>


              <!-- Quick Filter Button: Part Diganti -->
              <button type="button"
                @click="masterFilter.replaced = (masterFilter.replaced === '1' ? '' : '1'); fetchMasterStockList(1);"
                :class="masterFilter.replaced === '1' ? 'bg-amber-500 hover:bg-amber-600 text-white font-black shadow-sm ring-2 ring-amber-300' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold'"
                class="px-3 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer min-h-[38px] whitespace-nowrap active:scale-95"
                title="Filter cepat khusus part yang telah diganti dari perakitan">
                <i class="fa-solid fa-arrows-rotate text-[11px]"
                  :class="masterFilter.replaced === '1' ? 'text-white' : 'text-amber-600'"></i>
                <span>Part Diganti</span>
                <span v-if="replacedPartsTotalCount > 0"
                  class="px-1.5 py-0.5 rounded-full text-[10px] font-mono leading-none"
                  :class="masterFilter.replaced === '1' ? 'bg-amber-600 text-white' : 'bg-amber-100 text-amber-900 border border-amber-200'">
                  {{ replacedPartsTotalCount }}
                </span>
              </button>

              <!-- Search -->
              <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                <input type="text" v-model="masterFilter.q" @input="debouncedFetchMasterStockList"
                  placeholder="Cari SN / No Doc / MO..."
                  class="bg-slate-50 border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px] w-40 sm:w-56">
              </div>

              <!-- Reset Filter Button -->
              <button v-if="masterFilter.type || masterFilter.status || masterFilter.replaced || masterFilter.q"
                type="button"
                @click="masterFilter.type = ''; masterFilter.status = ''; masterFilter.replaced = ''; masterFilter.q = ''; fetchMasterStockList(1);"
                class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-2.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1 border border-slate-300 min-h-[38px] active:scale-95 cursor-pointer"
                title="Reset semua filter ke default">
                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                <span>Reset</span>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto rounded-xl border border-slate-200 -mx-1 sm:mx-0">
            <table class="w-full text-left text-xs min-w-[850px]">
              <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                <tr>
                  <th class="p-3 w-12 text-center">No</th>
                  <th class="p-3">Tipe Part</th>
                  <th class="p-3">Nomor Produksi / SN</th>
                  <th class="p-3">Kode CS (PDF)</th>
                  <th class="p-3">Status Kondisi</th>
                  <th class="p-3">Catatan / Alasan</th>
                  <th class="p-3">No. Dok & MO</th>
                  <th class="p-3">Operator Perakitan</th>
                  <th
                    class="p-3 text-center w-36 sticky right-0 bg-slate-100 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] z-10">
                    Otoritas Gudang
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px] sm:text-xs">
                <tr v-for="(m, i) in pagedMasterStock" :key="m.id" @click="openMasterPartTrackingModal(m)"
                  class="hover:bg-red-50/30 transition-colors group cursor-pointer"
                  title="Klik untuk melihat detail tracking & riwayat pengerjaan part ini">
                  <td class="p-3 text-center font-bold text-slate-400 font-sans">{{ ((masterFilter.page - 1) * 100) + i
                    + 1 }}</td>
                  <td class="p-3 font-sans font-bold text-red-700">
                    <div>P9 Part {{ m.part_type }}</div>
                    <span v-if="m.is_replaced_from_assy"
                      class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[8.5px] bg-amber-100 text-amber-800 font-bold border border-amber-200 uppercase tracking-tight">
                      Part Diganti
                    </span>
                  </td>
                  <td class="p-3 font-black text-slate-900 group-hover:text-red-700 transition-colors">
                    <div class="flex items-center gap-1.5">
                      <span>{{ m.sn }}</span>
                      <i
                        class="fa-solid fa-circle-info text-[10px] text-slate-400 group-hover:text-red-600 transition"></i>
                    </div>
                  </td>
                  <td class="p-3 text-slate-500">{{ m.cs || '-' }}</td>
                  <td class="p-3 font-sans" @click.stop>
                    <!-- Clickable Status Badge to trigger status drilldown -->
                    <button type="button"
                      @click="openStatusDrilldown(m.part_type, m.effective_status || m.stock_status)"
                      :class="getStatusBadgeClass(m.effective_status || m.stock_status)"
                      class="px-2.5 py-1 rounded-lg font-black text-[10px] uppercase border transition active:scale-95 hover:opacity-80 flex items-center gap-1 cursor-pointer shadow-xs"
                      :title="'Klik untuk melihat popup data barang dengan status ' + (m.effective_status || m.stock_status)">
                      <i v-if="m.effective_status === 'Good'" class="fa-solid fa-circle-check text-[10px]"></i>
                      <i v-else-if="m.effective_status === 'Hold'" class="fa-solid fa-hand text-[10px]"></i>
                      <i v-else-if="m.effective_status === 'Scrap'" class="fa-solid fa-trash-can text-[10px]"></i>
                      <i v-else-if="m.effective_status === 'Rework'" class="fa-solid fa-rotate-left text-[10px]"></i>
                      <i v-else-if="m.effective_status === 'Supplied'"
                        class="fa-solid fa-boxes-packing text-[10px]"></i>
                      <i v-else-if="m.effective_status === 'Decline' || m.effective_status === 'Declined' || m.effective_status === 'Pending Verif Admin'"
                        class="fa-solid fa-ban text-[10px]"></i>
                      <i v-else class="fa-solid fa-check text-[10px]"></i>
                      <span>{{ (m.effective_status === 'Decline' || m.effective_status === 'Declined' ||
                        m.effective_status === 'Pending Verif Admin') ? 'Menunggu Verifikasi' : (m.effective_status ||
                        m.stock_status) }}</span>
                    </button>
                  </td>
                  <td class="p-3 text-slate-700 font-sans text-xs">
                    <div v-if="m.assembly_reason || m.last_reason"
                      class="text-amber-900 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg max-w-xs leading-tight">
                      <i class="fa-solid fa-circle-info text-amber-600 mr-1"></i>{{ m.assembly_reason || m.last_reason
                      }}
                    </div>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                  <td class="p-3 text-slate-700">
                    <div v-if="m.tray_code">
                      <span class="font-bold text-red-700 block">{{ m.tray_code }}</span>
                      <span v-if="m.source_document_mo" class="text-[10px] text-slate-500">{{ m.source_document_mo
                        }}</span>
                    </div>
                    <span v-else class="text-slate-400 font-sans font-normal">-</span>
                  </td>
                  <td class="p-3 text-slate-700 font-sans text-xs">
                    <span v-if="m.pic_assembly" class="font-bold text-slate-900 block">{{
                      getOperatorFullName(m.pic_assembly) }}</span>
                    <span
                      v-else-if="m.assigned_operator && m.assigned_operator !== 'Semua Operator' && m.assigned_operator !== 'ALL'"
                      class="text-slate-700 font-semibold block">{{ getOperatorFullName(m.assigned_operator) }}</span>
                    <span v-else class="text-slate-300 font-normal">-</span>
                  </td>
                  <td
                    class="p-3 text-center font-sans sticky right-0 bg-white group-hover:bg-slate-50 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.06)] z-10"
                    @click.stop>
                    <div class="flex items-center justify-center gap-1.5">
                      <!-- Tombol Detail / Tracking -->
                      <button type="button" @click="openMasterPartTrackingModal(m)"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-2.5 py-1.5 rounded-lg text-[10.5px] transition flex items-center justify-center gap-1 shadow-xs active:scale-95 cursor-pointer whitespace-nowrap"
                        title="Lihat alur proses & riwayat part">
                        <i class="fa-solid fa-timeline text-[10px] text-red-600"></i>
                        <span>Detail</span>
                      </button>

                      <!-- Status Decline: Menunggu verifikasi di master stok -->
                      <span
                        v-if="m.effective_status === 'Decline' || m.effective_status === 'Declined' || m.effective_status === 'Pending Verif Admin'"
                        class="text-slate-400 text-[11px] italic font-medium">
                        Menunggu Verif
                      </span>
                      <!-- Hanya Part Lepasan Perakitan (Kuning) yang ada tombol Daftar Ulang -->
                      <button v-else-if="m.is_replaced_from_assy" @click="openReregisterModal(m)"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-2.5 py-1.5 rounded-lg text-[10.5px] transition flex items-center justify-center gap-1 shadow-xs active:scale-95 cursor-pointer whitespace-nowrap"
                        title="Daftarkan Ulang Part Lepasan Perakitan ini">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i>
                        <span>Daftar Ulang</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="masterStockList.length === 0">
                  <td colspan="9" class="p-8 text-center text-slate-400 font-sans">Tidak ada stok yang sesuai kriteria
                    pencarian.</td>
                </tr>
              </tbody>
            </table>
            <!-- Pagination Bar: masterStock -->
            <div v-if="totalPgMasterStock > 1"
              class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/60 rounded-b-xl  select-none no-print">
              <span class="text-[11px] text-slate-500 font-semibold">
                Hal <strong class="text-slate-700">{{ pgState.masterStock.page }}</strong> / {{ totalPgMasterStock }}
                <span class="text-slate-400 ml-1">({{ (masterStockList||[]).length }} data)</span>
              </span>
              <div class="flex items-center gap-1">
                <button type="button" @click="pgState.masterStock.page = 1" :disabled="pgState.masterStock.page <= 1"
                  class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-500 text-[10px] shadow-sm">
                  <i class="fa-solid fa-angles-left"></i>
                </button>
                <button type="button" @click="pgState.masterStock.page--" :disabled="pgState.masterStock.page <= 1"
                  class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-500 text-[10px] shadow-sm">
                  <i class="fa-solid fa-angle-left"></i>
                </button>
                <span class="text-[11px] font-black text-slate-800 px-2">{{ pgState.masterStock.page }}</span>
                <button type="button" @click="pgState.masterStock.page++"
                  :disabled="pgState.masterStock.page >= totalPgMasterStock"
                  class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px] shadow-sm">
                  <i class="fa-solid fa-angle-right"></i>
                </button>
                <button type="button" @click="pgState.masterStock.page = totalPgMasterStock"
                  :disabled="pgState.masterStock.page >= totalPgMasterStock"
                  class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px] shadow-sm">
                  <i class="fa-solid fa-angles-right"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination Controls -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
            <span class="text-slate-500">
              Total: <strong>{{ masterFilter.total }}</strong> item (Halaman {{ masterFilter.page }} dari {{
              masterFilter.totalPages }})
            </span>
            <div class="flex items-center space-x-1.5">
              <button :disabled="masterFilter.page <= 1" @click="fetchMasterStockList(masterFilter.page - 1)"
                class="bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-slate-700 font-bold px-3 py-1.5 rounded-lg transition">
                <i class="fa-solid fa-chevron-left"></i> Sebelumnya
              </button>
              <button :disabled="masterFilter.page >= masterFilter.totalPages"
                @click="fetchMasterStockList(masterFilter.page + 1)"
                class="bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-slate-700 font-bold px-3 py-1.5 rounded-lg transition">
                Berikutnya <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>

      </section>


@endverbatim
