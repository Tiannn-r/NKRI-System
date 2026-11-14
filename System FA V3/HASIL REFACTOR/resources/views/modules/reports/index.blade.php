@verbatim

      <section v-if="currentTab === 'report'" class="space-y-4 sm:space-y-6">

        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
          <!-- Report Header -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">History & Rekam Traceability
                P9</h2>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Laporan lengkap terintegrasi: Dokumen WO, Uji
                Fungsional Assy, Uji Mutu QC, dan Penerimaan Fisik Gudang.</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="exportCurrentReportToExcel"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl font-bold text-xs shadow-md shadow-emerald-600/25 flex items-center space-x-1.5 min-h-[38px] active:scale-95 transition cursor-pointer">
                <i class="fa-solid fa-file-excel"></i>
                <span>Ekspor Excel ({{ getReportCategoryTitle(reportSubTab) }})</span>
              </button>
            </div>
          </div>

          <!-- Category Sub-Tabs as Dropdown Selector -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2.5">
              <label for="reportSubTabSelect"
                class="text-xs font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                <i class="fa-solid fa-layer-group text-red-600"></i>
                <span>Menu Laporan:</span>
              </label>
              <div class="relative min-w-[260px] sm:min-w-[320px]">
                <select id="reportSubTabSelect" :value="reportSubTab" @change="setReportSubTab($event.target.value)"
                  class="w-full bg-white border border-slate-300 hover:border-slate-400 focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 shadow-2xs transition cursor-pointer">
                  <option value="wo">1. Work Order</option>
                  <option value="assembly">2. Perakitan</option>
                  <option value="assy_test">3. Test Functional 1</option>
                  <option value="qc_test">4. Test Functional 2</option>
                  <option value="warehouse_receipt">5. Receipts</option>
                </select>
              </div>
            </div>

            <!-- Total Summary (Adapts to Work Order Trays vs Set Assembly) -->
            <div class="flex items-center gap-2 text-slate-600">
              <span class="text-xs text-slate-500 font-medium">{{ reportSubTab === 'wo' ? 'Total Dokumen Tray (WO):' :
                'Total Seluruh Set Rakitan:' }}</span>
              <span
                class="text-sm font-black font-mono text-slate-900 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-lg shadow-2xs">
                {{ currentReportCounts.total }} {{ reportSubTab === 'wo' ? 'Tray' : 'Set' }}
              </span>
              <div v-if="currentReportCounts.total > 0" class="flex items-center gap-2.5 text-xs font-medium ml-1">
                <template v-if="reportSubTab === 'wo'">
                  <span class="text-emerald-700 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px] text-emerald-500"></i> Selesai: <strong
                      class="font-mono font-bold">{{ currentReportCounts.good }}</strong>
                  </span>
                  <span v-if="currentReportCounts.hold > 0" class="text-blue-700 flex items-center gap-1">
                    <i class="fa-solid fa-truck-ramp-box text-[10px] text-blue-500"></i> Tersupply: <strong
                      class="font-mono font-bold">{{ currentReportCounts.hold }}</strong>
                  </span>
                </template>
                <template v-else>
                  <span class="text-emerald-700 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px] text-emerald-500"></i> Good: <strong
                      class="font-mono font-bold">{{ currentReportCounts.good }}</strong>
                  </span>
                  <span v-if="currentReportCounts.hold > 0" class="text-amber-700 flex items-center gap-1">
                    <i class="fa-solid fa-hand text-[10px] text-amber-500"></i> Hold: <strong
                      class="font-mono font-bold">{{ currentReportCounts.hold }}</strong>
                  </span>

                </template>
              </div>
            </div>
          </div>

          <!-- Universal Filter Bar -->
          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 sm:p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-xs font-bold uppercase text-slate-700 tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-filter text-red-600"></i>
                <span>Filter {{ getReportCategoryTitle(reportSubTab) }}</span>
              </div>
              <button @click="resetCurrentReportFilter"
                class="text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
                <i class="fa-solid fa-rotate-left text-[10px]"></i>
                <span>Atur Ulang Filter</span>
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5">
              <!-- Tanggal Mulai -->
              <div>
                <label class="text-[10px] font-bold text-slate-500 block mb-1">Tanggal mulai:</label>
                <input type="date" v-model="reportFilters[reportSubTab].startDate"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px]">
              </div>

              <!-- Tanggal Selesai -->
              <div>
                <label class="text-[10px] font-bold text-slate-500 block mb-1">Tanggal selesai:</label>
                <input type="date" v-model="reportFilters[reportSubTab].endDate"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px]">
              </div>

              <!-- Filter Operator / Inspector (Dropdown) -->
              <div>
                <label class="text-[10px] font-bold text-slate-500 block mb-1">Operator / petugas:</label>
                <select v-model="reportFilters[reportSubTab].operator"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                  <option value="">Semua Operator</option>
                  <option v-for="op in currentReportOperatorOptions" :key="op" :value="op">{{ op }}</option>
                </select>
              </div>

              <!-- Filter No. Dokumen (Dropdown) -->
              <div>
                <label class="text-[10px] font-bold text-slate-500 block mb-1">Nomor dokumen / tray:</label>
                <select v-model="reportFilters[reportSubTab].docNumber"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                  <option value="">Semua Dokumen / Tray</option>
                  <option v-for="doc in currentReportDocOptions" :key="doc" :value="doc">{{ doc }}</option>
                </select>
              </div>

              <!-- Filter by Status -->
              <div>
                <label class="text-[10px] font-bold text-slate-500 block mb-1 flex items-center gap-1">
                  <i class="fa-solid fa-filter text-red-600"></i>
                  <span>Filter by Status:</span>
                </label>
                <select v-model="reportFilters[reportSubTab].status"
                  class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                  <template v-if="reportSubTab === 'wo'">
                    <option value="">Semua Status</option>
                    <option value="TERSUPPLY">TERSUPPLY</option>
                    <option value="SELESAI">SELESAI</option>
                  </template>
                  <template v-else>
                    <option value="">Semua Status</option>
                    <option value="GOOD">GOOD</option>
                    <option value="HOLD">HOLD</option>
                    <option value="RU">RU (Rakit Ulang)</option>
                    <option value="SCRAP">SCRAP</option>
                    <option value="MENUNGGU">MENUNGGU / BELUM SELESAI</option>
                    <option v-if="reportSubTab === 'warehouse_receipt'" value="ACCEPTED">DITERIMA GUDANG</option>
                    <option v-if="reportSubTab === 'warehouse_receipt'" value="DECLINED">DITOLAK GUDANG</option>
                  </template>
                </select>
              </div>
            </div>

            <!-- Free text search -->
            <div class="relative pt-1">
              <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
              <input type="text" v-model="reportFilters[reportSubTab].searchQuery"
                placeholder="Cari SN P9 Part 15 / P9 Part 1 / P9 Part 12 / Dokumen / Alasan..."
                class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px]">
            </div>
          </div>


          <!-- ================= MODE TAMPILAN TOOLBAR: PER DOKUMEN VS PER BARIS ITEM ================= -->
          <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs mb-4">
            <div class="flex flex-wrap items-center gap-2.5">
              <span class="text-xs font-bold text-slate-700 ml-1 flex items-center gap-1.5">
                <i class="fa-solid fa-table-cells-large text-red-600"></i>
                <span>Mode Tampilan:</span>
              </span>
              <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200 shadow-2xs">
                <button type="button" @click="setReportViewMode('document')"
                  :class="reportViewMode === 'document' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition cursor-pointer">
                  <i class="fa-solid fa-folder-tree"></i>
                  <span>Per Dokumen (Ringkasan)</span>
                  <span class="ml-0.5 px-2 py-0.2 rounded-full text-[10px] font-mono font-bold"
                    :class="reportViewMode === 'document' ? 'bg-red-100 text-red-800' : 'bg-slate-200 text-slate-700'">
                    {{ currentReportDocSummaryList.length }}
                  </span>
                </button>
                <button type="button" @click="setReportViewMode('detailed')"
                  :class="reportViewMode === 'detailed' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3.5 py-1.5 rounded-lg text-xs flex items-center gap-2 transition cursor-pointer">
                  <i class="fa-solid fa-list-ol"></i>
                  <span>Per Baris Item (Rinci Unit)</span>
                  <span class="ml-0.5 px-2 py-0.2 rounded-full text-[10px] font-mono font-bold"
                    :class="reportViewMode === 'detailed' ? 'bg-red-100 text-red-800' : 'bg-slate-200 text-slate-700'">
                    {{ currentReportList.length }}
                  </span>
                </button>
              </div>
            </div>
          </div>

          <!-- ================= TAMPILAN 1: MODE PER DOKUMEN (REKAPITULASI DOKUMEN) ================= -->
          <div v-if="reportViewMode === 'document'"
            class="overflow-x-auto rounded-xl border border-slate-200 mb-4 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[950px]">
              <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px]">
                <tr>
                  <th class="p-2.5 sm:p-3 w-10 text-center font-bold tracking-wider">No</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider whitespace-nowrap">No. Dokumen</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider whitespace-nowrap">Dokumen Asal</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider whitespace-nowrap">Operator / Petugas</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider whitespace-nowrap">Waktu Dokumen</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider text-center whitespace-nowrap min-w-[90px]">Total
                    Unit</th>
                  <th class="p-2.5 sm:p-3 font-bold tracking-wider text-center w-40 whitespace-nowrap">{{ reportSubTab
                    === 'wo' ? 'Status WO' : 'Rincian Status' }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-sans text-xs">
                <tr v-if="currentReportDocSummaryList.length === 0">
                  <td colspan="7" class="p-8 text-center text-slate-400">
                    <i class="fa-solid fa-folder-open text-3xl mb-2 block text-slate-300"></i>
                    <span>Tidak ada data dokumen yang sesuai dengan filter saat ini.</span>
                  </td>
                </tr>
                <tr v-for="(doc, idx) in currentReportDocSummaryList" :key="doc.doc_code || idx"
                  @click="openReportDocDetail(doc)"
                  class="hover:bg-red-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk membuka rincian unit dokumen ini">
                  <td
                    class="p-2.5 sm:p-3 text-center font-bold text-slate-400 group-hover:text-red-600 transition font-mono">
                    <span class="group-hover:hidden">{{ idx + 1 }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-red-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 font-mono">
                    <span
                      class="font-bold text-slate-900 bg-slate-100 border border-slate-200 group-hover:border-red-300 group-hover:bg-red-50 group-hover:text-red-800 px-2.5 py-1 rounded-lg text-xs inline-flex items-center gap-1.5 transition">
                      <i class="fa-solid fa-file-lines text-slate-400 group-hover:text-red-600 text-[11px]"></i>
                      <span>{{ doc.doc_code }}</span>
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 font-mono">
                    <span v-if="doc.origin_doc && doc.origin_doc !== '-'"
                      class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                      {{ doc.origin_doc }}
                    </span>
                    <span v-else class="text-slate-400 italic text-xs">-</span>
                  </td>
                  <td class="p-2.5 sm:p-3 font-semibold text-slate-700">
                    {{ doc.operator || '-' }}
                  </td>
                  <td class="p-2.5 sm:p-3 text-slate-500 whitespace-nowrap text-xs font-mono">
                    {{ formatDateTime(doc.timestamp) }}
                  </td>
                  <td class="p-2.5 sm:p-3 text-center whitespace-nowrap">
                    <span
                      class="inline-flex items-center justify-center gap-1 min-w-[76px] bg-slate-100 border border-slate-200 text-slate-800 px-2.5 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap shadow-2xs">
                      <span>{{ doc.total_units }}</span>
                      <span class="text-[10.5px] font-semibold text-slate-500">Unit</span>
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center">
                    <!-- JIKA WO: HANYA TERSUPPLY ATAU SELESAI -->
                    <template v-if="reportSubTab === 'wo'">
                      <span
                        :class="doc.status === 'SELESAI' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-blue-100 text-blue-800 border-blue-300'"
                        class="px-2.5 py-1 rounded-lg text-xs font-black uppercase border inline-flex items-center gap-1.5 font-mono shadow-2xs">
                        <i
                          :class="doc.status === 'SELESAI' ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-truck-ramp-box text-blue-600'"></i>
                        <span>{{ doc.status || 'TERSUPPLY' }}</span>
                      </span>
                    </template>
                    <!-- SUBTAB LAINNYA: TOMBOL LIHAT DETAIL STATUS -->
                    <template v-else>
                      <button type="button" @click.stop="openReportDocDetail(doc)"
                        class="px-3 py-1 bg-white hover:bg-red-50 hover:text-red-700 hover:border-red-300 border border-slate-300 text-slate-700 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-circle-info text-blue-600 text-[11px]"></i>
                        <span>Lihat Detail Status</span>
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ================= SUB-TAB 1: DOKUMEN WO ================= -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'wo'"
            class="overflow-x-auto max-h-[600px] overflow-y-auto rounded-xl border border-slate-200 mb-2 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[1050px] border-collapse">
              <thead
                class="h-11 bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px] sticky top-0 z-10">
                <tr>
                  <th class="p-2.5 sm:p-3 w-12 text-center font-bold tracking-wider">No</th>
                  <th @click="setReportSort('doc')"
                    class="p-2.5 sm:p-3 w-48 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'doc'}">
                    <span class="inline-flex items-center gap-1">No. Dokumen <i
                        :class="getReportSortIconClass('doc')"></i></span>
                  </th>
                  <th @click="setReportSort('mo')"
                    class="p-2.5 sm:p-3 w-40 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'mo'}">
                    <span class="inline-flex items-center gap-1">Dokumen Asal <i
                        :class="getReportSortIconClass('mo')"></i></span>
                  </th>
                  <th @click="setReportSort('sn15')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn15'}">
                    <span class="inline-flex items-center gap-1">SN P15 <i
                        :class="getReportSortIconClass('sn15')"></i></span>
                  </th>
                  <th @click="setReportSort('sn1')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn1'}">
                    <span class="inline-flex items-center gap-1">PN P1 <i
                        :class="getReportSortIconClass('sn1')"></i></span>
                  </th>
                  <th @click="setReportSort('sn12')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn12'}">
                    <span class="inline-flex items-center gap-1">PN P12 <i
                        :class="getReportSortIconClass('sn12')"></i></span>
                  </th>
                  <th @click="setReportSort('operator')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'operator'}">
                    <span class="inline-flex items-center gap-1">Operator / Petugas <i
                        :class="getReportSortIconClass('operator')"></i></span>
                  </th>
                  <th @click="setReportSort('date')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'date'}">
                    <span class="inline-flex items-center gap-1">Waktu Transaksi <i
                        :class="getReportSortIconClass('date')"></i></span>
                  </th>
                  <th @click="setReportSort('status')"
                    class="p-2.5 sm:p-3 w-36 font-bold tracking-wider text-center cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'status'}">
                    <span class="inline-flex items-center gap-1">Status <i
                        :class="getReportSortIconClass('status')"></i></span>
                  </th>
                  <th class="p-2.5 sm:p-3 min-w-[180px] font-bold tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                <tr v-if="filteredWOReport.length === 0">
                  <td colspan="10" class="p-8 text-center text-slate-400 font-sans">
                    <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                    <span>Tidak ada data riwayat Work Order yang sesuai filter.</span>
                  </td>
                </tr>
                <tr v-for="(it, idx) in pagedWOReport" :key="it.id || idx" @click="openWOUnitDetail(it)"
                  class="hover:bg-blue-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk melihat rincian unit">
                  <td
                    class="p-2.5 sm:p-3 w-12 text-center font-bold text-slate-400 font-sans group-hover:text-blue-600 transition">
                    <span class="group-hover:hidden">{{ idx + 1 + (pgState.woReport.page - 1) * pgState.woReport.size
                      }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-blue-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 w-48 whitespace-nowrap font-mono">
                    <span
                      class="font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded text-[11px]">
                      {{ getFormattedDocCode(it.tray_code, 'wo') }}
                    </span>
                    <span v-if="it.set_number" class="ml-1 text-[10px] font-sans font-bold text-slate-500">Set #{{
                      it.set_number }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-40 whitespace-nowrap font-mono">
                    <span v-if="it.source_document_mo && it.source_document_mo !== '-'"
                      class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                      {{ it.source_document_mo }}
                    </span>
                    <span v-else class="text-slate-400 italic font-sans text-xs">-</span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-red-900 text-xs px-2.5 py-1 bg-red-50 border border-red-200 rounded-lg inline-block shadow-2xs">
                      {{ it.part15_sn || it.p9_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-slate-800 text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                      {{ it.part1_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-slate-800 text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                      {{ it.part12_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs">
                    <span class="font-semibold text-slate-700">{{ it.operator || it.pic_warehouse || '-' }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs text-slate-500">
                    {{ formatDateTime(it.created_at || it.tray_created_at || it.started_at) }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-36 text-center whitespace-nowrap font-sans">
                    <span
                      :class="((it.finished_count >= it.total_sets && it.total_sets > 0) || it.finished_at || it.session_status === 'COMPLETED_SHIFT' || it.tray_status === 'returned' || it.status === 'Good' || it.status === 'SELESAI' || it.status === 'Dikembalikan ke Gudang') ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-blue-100 text-blue-800 border-blue-300'"
                      class="px-2.5 py-0.5 rounded-md text-[10.5px] font-black uppercase border inline-flex items-center gap-1 font-mono shadow-2xs">
                      <i
                        :class="((it.finished_count >= it.total_sets && it.total_sets > 0) || it.finished_at || it.session_status === 'COMPLETED_SHIFT' || it.tray_status === 'returned' || it.status === 'Good' || it.status === 'SELESAI' || it.status === 'Dikembalikan ke Gudang') ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-truck-ramp-box text-blue-600'"></i>
                      <span>{{ ((it.finished_count >= it.total_sets && it.total_sets > 0) || it.finished_at ||
                        it.session_status === 'COMPLETED_SHIFT' || it.tray_status === 'returned' || it.status === 'Good' || it.status === 'SELESAI' || it.status === 'Dikembalikan ke Gudang') ?
                        'SELESAI' : 'TERSUPPLY' }}</span>
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 min-w-[180px] font-sans italic text-xs">
                    <template v-if="it.is_declined || it.status === 'Decline' || it.status === 'Declined'">
                      <span class="text-rose-600 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                        {{ it.declined_reason ? 'Dikembalikan ke Gudang (Declined: ' + it.declined_reason + ')' : 'Dikembalikan ke Gudang (Declined)' }}
                      </span>
                    </template>
                    <template v-else-if="it.status === 'Dikembalikan ke Gudang' || (!it.finished_at && (it.session_status === 'COMPLETED_SHIFT' || it.tray_status === 'returned'))">
                      <span class="text-amber-700 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-arrow-rotate-left text-amber-600"></i>
                        Dikembalikan ke Gudang
                      </span>
                    </template>
                    <template v-else-if="it.finished_at || it.status === 'Good' || it.status === 'SELESAI'">
                      <span class="text-emerald-700 font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-check text-emerald-600"></i>
                        Selesai Dirakit
                      </span>
                    </template>
                    <template v-else>
                      <span class="text-slate-500">{{ it.reason || 'Tray Tersupply ke Perakitan' }}</span>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination: woReport -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'wo'"
            class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2 mb-4">
            <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                pgState.woReport.page }}</strong> / {{ totalPgWOReport }} <span class="text-slate-400 ml-1">({{
                (filteredWOReport||[]).length }} data)</span></span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.woReport.page = 1" :disabled="pgState.woReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angles-left"></i></button>
              <button type="button" @click="pgState.woReport.page--" :disabled="pgState.woReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angle-left"></i></button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.woReport.page }}</span>
              <button type="button" @click="pgState.woReport.page++"
                :disabled="pgState.woReport.page >= totalPgWOReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angle-right"></i></button>
              <button type="button" @click="pgState.woReport.page = totalPgWOReport"
                :disabled="pgState.woReport.page >= totalPgWOReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angles-right"></i></button>
            </div>
          </div>

          <!-- ================= SUB-TAB 2: DOKUMEN PERAKITAN ================= -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'assembly'"
            class="overflow-x-auto max-h-[600px] overflow-y-auto rounded-xl border border-slate-200 mb-2 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[1050px] border-collapse">
              <thead
                class="h-11 bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px] sticky top-0 z-10">
                <tr>
                  <th class="p-2.5 sm:p-3 w-12 text-center font-bold tracking-wider">No</th>
                  <th @click="setReportSort('doc')"
                    class="p-2.5 sm:p-3 w-48 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'doc'}">
                    <span class="inline-flex items-center gap-1">No. Dokumen <i
                        :class="getReportSortIconClass('doc')"></i></span>
                  </th>
                  <th @click="setReportSort('mo')"
                    class="p-2.5 sm:p-3 w-40 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'mo'}">
                    <span class="inline-flex items-center gap-1">Dokumen Asal <i
                        :class="getReportSortIconClass('mo')"></i></span>
                  </th>
                  <th @click="setReportSort('sn15')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn15'}">
                    <span class="inline-flex items-center gap-1">SN P15 <i
                        :class="getReportSortIconClass('sn15')"></i></span>
                  </th>
                  <th @click="setReportSort('sn1')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn1'}">
                    <span class="inline-flex items-center gap-1">PN P1 <i
                        :class="getReportSortIconClass('sn1')"></i></span>
                  </th>
                  <th @click="setReportSort('sn12')"
                    class="p-2.5 sm:p-3 text-center font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn12'}">
                    <span class="inline-flex items-center gap-1">PN P12 <i
                        :class="getReportSortIconClass('sn12')"></i></span>
                  </th>
                  <th @click="setReportSort('operator')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'operator'}">
                    <span class="inline-flex items-center gap-1">Operator / Petugas <i
                        :class="getReportSortIconClass('operator')"></i></span>
                  </th>
                  <th @click="setReportSort('date')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'date'}">
                    <span class="inline-flex items-center gap-1">Waktu Transaksi <i
                        :class="getReportSortIconClass('date')"></i></span>
                  </th>
                  <th @click="setReportSort('status')"
                    class="p-2.5 sm:p-3 w-36 font-bold tracking-wider text-center cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'status'}">
                    <span class="inline-flex items-center gap-1">Status <i
                        :class="getReportSortIconClass('status')"></i></span>
                  </th>
                  <th class="p-2.5 sm:p-3 min-w-[180px] font-bold tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                <tr v-if="filteredAssemblyReport.length === 0">
                  <td colspan="10" class="p-8 text-center text-slate-400 font-sans">
                    <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                    <span>Tidak ada data hasil perakitan yang sesuai dengan filter.</span>
                  </td>
                </tr>
                <tr v-for="(it, idx) in pagedAssyReport" :key="it.assembly_id || it.id || idx"
                  @click="openDetailModal(it, idx, 'assembly')"
                  class="hover:bg-purple-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk melihat detail riwayat lengkap">
                  <td
                    class="p-2.5 sm:p-3 w-12 text-center font-bold text-slate-400 font-sans group-hover:text-purple-600 transition">
                    <span class="group-hover:hidden">{{ idx + 1 + (pgState.assyReport.page - 1) *
                      pgState.assyReport.size }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-purple-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 w-48 whitespace-nowrap font-mono">
                    <span
                      class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-300">
                      {{ (typeof isSetAllocatedForReassembly === 'function' && isSetAllocatedForReassembly(it)) ?
                      (getSetPUDocNumber(it) || getFormattedDocCode(it.tray_code, 'assy_rework')) :
                      ((it.assy_doc_number && it.assy_doc_number.toUpperCase().startsWith('WH/ASSY')) ?
                      it.assy_doc_number : getFormattedDocCode(it.tray_code, 'assy')) }}
                    </span>
                    <span v-if="it.set_number" class="ml-1 text-[10px] font-sans font-bold text-slate-500">Set #{{
                      it.set_number }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-40 whitespace-nowrap font-mono">
                    <span
                      class="font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded text-[11px]">
                      {{ getFormattedDocCode(it.tray_code, 'wo') }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-red-900 text-xs px-2.5 py-1 bg-red-50 border border-red-200 rounded-lg inline-block shadow-2xs">
                      {{ it.part15_sn || it.p9_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-slate-800 text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                      {{ it.part1_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 text-center font-mono whitespace-nowrap">
                    <span
                      class="font-bold text-slate-800 text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                      {{ it.part12_sn || '-' }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs font-semibold text-slate-700">
                    {{ it.pic_assembly || '-' }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs text-slate-500">
                    {{ formatDateTime(it.finished_at || it.tray_created_at) }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-36 text-center whitespace-nowrap font-sans">
                    <span :class="getStatusTextClass(it.original_assembly_status || it.assembly_status || it.status || 'Good')"
                      class="text-xs uppercase tracking-wide">
                      {{ formatDocumentStatusText(it.original_assembly_status || it.assembly_status || it.status || 'Good') }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 min-w-[180px] text-slate-500 font-sans italic text-xs">
                    <span
                      v-if="(it.assembly_status || it.status) && ((it.assembly_status || it.status).includes('Menunggu') || (it.assembly_status || it.status).includes('Belum'))"
                      class="text-amber-800 italic">
                      <i class="fa-solid fa-clock text-xs mr-1"></i>Menunggu perakitan oleh Operator
                    </span>
                    <span v-else>{{ it.assembly_reason || it.reason || it.qc_failure_reasons || '-' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination: assyReport -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'assembly'"
            class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2 mb-4">
            <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                pgState.assyReport.page }}</strong> / {{ totalPgAssyReport }} <span class="text-slate-400 ml-1">({{
                (filteredAssemblyReport||[]).length }} data)</span></span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.assyReport.page = 1" :disabled="pgState.assyReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angles-left"></i></button>
              <button type="button" @click="pgState.assyReport.page--" :disabled="pgState.assyReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angle-left"></i></button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.assyReport.page }}</span>
              <button type="button" @click="pgState.assyReport.page++"
                :disabled="pgState.assyReport.page >= totalPgAssyReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angle-right"></i></button>
              <button type="button" @click="pgState.assyReport.page = totalPgAssyReport"
                :disabled="pgState.assyReport.page >= totalPgAssyReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angles-right"></i></button>
            </div>
          </div>

          <!-- ================= SUB-TAB 3: TEST FUNCTIONAL 1 (Assy/QC) ================= -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'assy_test'"
            class="overflow-x-auto max-h-[600px] overflow-y-auto rounded-xl border border-slate-200 mb-2 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[1050px] border-collapse">
              <thead
                class="h-11 bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px] sticky top-0 z-10">
                <tr>
                  <th class="p-2.5 sm:p-3 w-12 text-center font-bold tracking-wider">No</th>
                  <th @click="setReportSort('doc')"
                    class="p-2.5 sm:p-3 w-48 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'doc'}">
                    <span class="inline-flex items-center gap-1">No. Dokumen <i
                        :class="getReportSortIconClass('doc')"></i></span>
                  </th>
                  <th @click="setReportSort('mo')"
                    class="p-2.5 sm:p-3 w-40 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'mo'}">
                    <span class="inline-flex items-center gap-1">Dokumen Asal <i
                        :class="getReportSortIconClass('mo')"></i></span>
                  </th>
                  <th @click="setReportSort('sn15')"
                    class="p-2.5 sm:p-3 w-64 min-w-[240px] font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn15'}">
                    <span class="inline-flex items-center gap-1">Serial Number &amp; Komponen <i
                        :class="getReportSortIconClass('sn15')"></i></span>
                  </th>
                  <th @click="setReportSort('operator')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'operator'}">
                    <span class="inline-flex items-center gap-1">Operator / Petugas <i
                        :class="getReportSortIconClass('operator')"></i></span>
                  </th>
                  <th @click="setReportSort('date')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'date'}">
                    <span class="inline-flex items-center gap-1">Waktu Transaksi <i
                        :class="getReportSortIconClass('date')"></i></span>
                  </th>
                  <th @click="setReportSort('status')"
                    class="p-2.5 sm:p-3 w-36 font-bold tracking-wider text-center cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'status'}">
                    <span class="inline-flex items-center gap-1">Status <i
                        :class="getReportSortIconClass('status')"></i></span>
                  </th>
                  <th class="p-2.5 sm:p-3 min-w-[180px] font-bold tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                <tr v-if="filteredAssyTestReport.length === 0">
                  <td colspan="8" class="p-8 text-center text-slate-400 font-sans">
                    <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                    <span>Tidak ada data Uji Fungsional 1 yang sesuai dengan filter.</span>
                  </td>
                </tr>
                <tr v-for="(it, idx) in pagedAssyTestReport" :key="it.id || idx" @click="openDetailModal(it, idx, 'assy_test')"
                  class="hover:bg-indigo-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk melihat detail riwayat lengkap">
                  <td
                    class="p-2.5 sm:p-3 w-12 text-center font-bold text-slate-400 font-sans group-hover:text-indigo-600 transition">
                    <span class="group-hover:hidden">{{ idx + 1 + (pgState.assyTestReport.page - 1) *
                      pgState.assyTestReport.size }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-indigo-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 w-48 whitespace-nowrap font-mono">
                    <span
                      class="font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded text-[11px]">
                      {{ (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code,
                      'assy_qc') }}
                    </span>
                    <span v-if="it.set_number" class="ml-1 text-[10px] font-sans font-bold text-slate-500">Set #{{
                      it.set_number }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-40 whitespace-nowrap font-mono">
                    <span
                      class="bg-slate-100 border border-slate-300 text-slate-800 px-2 py-0.5 rounded font-bold text-[11px]">
                      {{ getFormattedDocCode(it.tray_code, 'assy') }}
                    </span>
                  </td>
                  <td class="p-2 sm:p-2.5 w-64">
                    <div
                      class="w-full max-w-[240px] border border-slate-200 rounded-md overflow-hidden text-[11px] font-mono bg-white shadow-2xs">
                      <table class="w-full border-collapse">
                        <tbody>
                          <tr class="bg-slate-50/90 border-b border-slate-200">
                            <td colspan="2" class="px-2 py-1">
                              <div class="flex items-center justify-between gap-1.5">
                                <span
                                  class="text-[9px] font-sans font-black px-1.5 py-0.5 bg-red-100 text-red-800 rounded uppercase">P15</span>
                                <span class="font-bold text-slate-900 text-xs truncate"
                                  :title="it.part15_sn || it.p9_sn || '-'">{{ it.part15_sn || it.p9_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                          <tr class="bg-white divide-x divide-slate-200 text-[10.5px]">
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P12:</span>
                                <span class="font-bold text-slate-700 font-mono truncate"
                                  :title="it.part12_sn || '-'">{{ it.part12_sn || '-' }}</span>
                              </div>
                            </td>
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P1:</span>
                                <span class="font-bold text-slate-700 font-mono truncate" :title="it.part1_sn || '-'">{{
                                  it.part1_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans font-semibold text-xs text-slate-700">
                    {{ it.tester_full_name || it.tester_username || '-' }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs text-slate-500">
                    {{ formatDateTime(it.created_at) }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-36 text-center whitespace-nowrap font-sans">
                    <span :class="getStatusTextClass(it.status)"
                      class="text-xs uppercase tracking-wide">
                      {{ formatDocumentStatusText(it.status) }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 min-w-[180px] font-sans text-xs">
                    <span v-if="it.failure_reasons"
                      class="text-red-900 bg-red-50 border border-red-200 px-2 py-0.5 rounded font-semibold inline-flex items-center gap-1">
                      <i class="fa-solid fa-triangle-exclamation text-[10px] text-red-600"></i>
                      <span>{{ formatFailureReason(it.failure_reasons) }}</span>
                    </span>
                    <span v-else-if="it.status && (it.status.includes('Menunggu') || it.status.includes('Belum'))"
                      class="text-amber-800 italic">
                      <i class="fa-solid fa-clock text-xs mr-1"></i>Menunggu pengujian oleh Operator Assembly
                    </span>
                    <span v-else-if="it.notes" class="text-slate-600 italic">{{ it.notes }}</span>
                    <span v-else class="text-emerald-700 font-semibold"><i
                        class="fa-solid fa-check text-xs mr-1"></i>Lolos Seluruh Uji</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination: assyTestReport -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'assy_test'"
            class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2 mb-4">
            <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                pgState.assyTestReport.page }}</strong> / {{ totalPgAssyTestReport }} <span
                class="text-slate-400 ml-1">({{ (filteredAssyTestReport||[]).length }} data)</span></span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.assyTestReport.page = 1"
                :disabled="pgState.assyTestReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angles-left"></i></button>
              <button type="button" @click="pgState.assyTestReport.page--" :disabled="pgState.assyTestReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angle-left"></i></button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.assyTestReport.page }}</span>
              <button type="button" @click="pgState.assyTestReport.page++"
                :disabled="pgState.assyTestReport.page >= totalPgAssyTestReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angle-right"></i></button>
              <button type="button" @click="pgState.assyTestReport.page = totalPgAssyTestReport"
                :disabled="pgState.assyTestReport.page >= totalPgAssyTestReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angles-right"></i></button>
            </div>
          </div>

          <!-- ================= SUB-TAB 4: TEST FUNCTIONAL 2 (QC) ================= -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'qc_test'"
            class="overflow-x-auto max-h-[600px] overflow-y-auto rounded-xl border border-slate-200 mb-2 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[1050px] border-collapse">
              <thead
                class="h-11 bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px] sticky top-0 z-10">
                <tr>
                  <th class="p-2.5 sm:p-3 w-12 text-center font-bold tracking-wider">No</th>
                  <th @click="setReportSort('doc')"
                    class="p-2.5 sm:p-3 w-48 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'doc'}">
                    <span class="inline-flex items-center gap-1">No. Dokumen <i
                        :class="getReportSortIconClass('doc')"></i></span>
                  </th>
                  <th @click="setReportSort('mo')"
                    class="p-2.5 sm:p-3 w-40 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'mo'}">
                    <span class="inline-flex items-center gap-1">Dokumen Asal <i
                        :class="getReportSortIconClass('mo')"></i></span>
                  </th>
                  <th @click="setReportSort('sn15')"
                    class="p-2.5 sm:p-3 w-64 min-w-[240px] font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn15'}">
                    <span class="inline-flex items-center gap-1">Serial Number &amp; Komponen <i
                        :class="getReportSortIconClass('sn15')"></i></span>
                  </th>
                  <th @click="setReportSort('operator')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'operator'}">
                    <span class="inline-flex items-center gap-1">Operator / Petugas <i
                        :class="getReportSortIconClass('operator')"></i></span>
                  </th>
                  <th @click="setReportSort('date')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'date'}">
                    <span class="inline-flex items-center gap-1">Waktu Transaksi <i
                        :class="getReportSortIconClass('date')"></i></span>
                  </th>
                  <th @click="setReportSort('status')"
                    class="p-2.5 sm:p-3 w-36 font-bold tracking-wider text-center cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'status'}">
                    <span class="inline-flex items-center gap-1">Status <i
                        :class="getReportSortIconClass('status')"></i></span>
                  </th>
                  <th class="p-2.5 sm:p-3 min-w-[180px] font-bold tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                <tr v-if="filteredQCTestReport.length === 0">
                  <td colspan="8" class="p-8 text-center text-slate-400 font-sans">
                    <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                    <span>Tidak ada data Uji Mutu QC yang sesuai dengan filter.</span>
                  </td>
                </tr>
                <tr v-for="(it, idx) in pagedQCReport" :key="it.id || idx" @click="openDetailModal(it, idx, 'qc_test')"
                  class="hover:bg-emerald-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk melihat detail riwayat lengkap">
                  <td
                    class="p-2.5 sm:p-3 w-12 text-center font-bold text-slate-400 font-sans group-hover:text-emerald-600 transition">
                    <span class="group-hover:hidden">{{ idx + 1 + (pgState.qcReport.page - 1) * pgState.qcReport.size
                      }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-emerald-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 w-48 whitespace-nowrap font-mono">
                    <span
                      class="font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded text-[11px]">
                      {{ (it.doc_number && it.doc_number !== '-') ? it.doc_number : getFormattedDocCode(it.tray_code,
                      'qc') }}
                    </span>
                    <span v-if="it.set_number" class="ml-1 text-[10px] font-sans font-bold text-slate-500">Set #{{
                      it.set_number }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-40 whitespace-nowrap font-mono">
                    <span
                      class="bg-indigo-50 border border-indigo-200 text-indigo-700 px-2 py-0.5 rounded font-bold text-[11px]">
                      {{ (it.tf1_doc_number && it.tf1_doc_number !== '-') ? it.tf1_doc_number :
                      getFormattedDocCode(it.tray_code, 'assy_qc') }}
                    </span>
                  </td>
                  <td class="p-2 sm:p-2.5 w-64">
                    <div
                      class="w-full max-w-[240px] border border-slate-200 rounded-md overflow-hidden text-[11px] font-mono bg-white shadow-2xs">
                      <table class="w-full border-collapse">
                        <tbody>
                          <tr class="bg-slate-50/90 border-b border-slate-200">
                            <td colspan="2" class="px-2 py-1">
                              <div class="flex items-center justify-between gap-1.5">
                                <span
                                  class="text-[9px] font-sans font-black px-1.5 py-0.5 bg-red-100 text-red-800 rounded uppercase">P15</span>
                                <span class="font-bold text-slate-900 text-xs truncate"
                                  :title="it.part15_sn || it.p9_sn || '-'">{{ it.part15_sn || it.p9_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                          <tr class="bg-white divide-x divide-slate-200 text-[10.5px]">
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P12:</span>
                                <span class="font-bold text-slate-700 font-mono truncate"
                                  :title="it.part12_sn || '-'">{{ it.part12_sn || '-' }}</span>
                              </div>
                            </td>
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P1:</span>
                                <span class="font-bold text-slate-700 font-mono truncate" :title="it.part1_sn || '-'">{{
                                  it.part1_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans font-semibold text-xs text-slate-700">
                    {{ it.tester_full_name || it.tester_username || '-' }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs text-slate-500">
                    {{ formatDateTime(it.created_at) }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-36 text-center whitespace-nowrap font-sans">
                    <span :class="getStatusTextClass(it.status)"
                      class="text-xs uppercase tracking-wide">
                      {{ formatDocumentStatusText(it.status) }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 min-w-[180px] font-sans text-xs">
                    <span v-if="it.failure_reasons"
                      class="text-red-900 bg-red-50 border border-red-200 px-2 py-0.5 rounded font-semibold inline-flex items-center gap-1">
                      <i class="fa-solid fa-triangle-exclamation text-[10px] text-red-600"></i>
                      <span>{{ formatFailureReason(it.failure_reasons) }}</span>
                    </span>
                    <span v-else-if="it.status && (it.status.includes('Menunggu') || it.status.includes('Belum'))"
                      class="text-amber-800 italic">
                      <i class="fa-solid fa-clock text-xs mr-1"></i>Menunggu pengujian mutu oleh Inspector QC
                    </span>
                    <span v-else-if="it.notes" class="text-slate-600 italic">{{ it.notes }}</span>
                    <span v-else class="text-emerald-700 font-semibold"><i
                        class="fa-solid fa-circle-check text-xs mr-1"></i>Lolos Seluruh Uji QC</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination: qcReport -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'qc_test'"
            class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2 mb-4">
            <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                pgState.qcReport.page }}</strong> / {{ totalPgQCReport }} <span class="text-slate-400 ml-1">({{
                (filteredQCTestReport||[]).length }} data)</span></span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.qcReport.page = 1" :disabled="pgState.qcReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angles-left"></i></button>
              <button type="button" @click="pgState.qcReport.page--" :disabled="pgState.qcReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angle-left"></i></button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.qcReport.page }}</span>
              <button type="button" @click="pgState.qcReport.page++"
                :disabled="pgState.qcReport.page >= totalPgQCReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angle-right"></i></button>
              <button type="button" @click="pgState.qcReport.page = totalPgQCReport"
                :disabled="pgState.qcReport.page >= totalPgQCReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angles-right"></i></button>
            </div>
          </div>

          <!-- ================= SUB-TAB 5: PENERIMAAN FISIK GUDANG ================= -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'warehouse_receipt'"
            class="overflow-x-auto max-h-[600px] overflow-y-auto rounded-xl border border-slate-200 mb-2 bg-white shadow-2xs">
            <table class="w-full text-left text-xs min-w-[1050px] border-collapse">
              <thead
                class="h-11 bg-slate-100 text-slate-700 font-bold border-b border-slate-200 select-none text-[11px] sticky top-0 z-10">
                <tr>
                  <th class="p-2.5 sm:p-3 w-12 text-center font-bold tracking-wider">No</th>
                  <th @click="setReportSort('doc')"
                    class="p-2.5 sm:p-3 w-48 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'doc'}">
                    <span class="inline-flex items-center gap-1">No. Dokumen <i
                        :class="getReportSortIconClass('doc')"></i></span>
                  </th>
                  <th @click="setReportSort('mo')"
                    class="p-2.5 sm:p-3 w-40 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'mo'}">
                    <span class="inline-flex items-center gap-1">Dokumen Asal <i
                        :class="getReportSortIconClass('mo')"></i></span>
                  </th>
                  <th @click="setReportSort('sn15')"
                    class="p-2.5 sm:p-3 w-64 min-w-[240px] font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'sn15'}">
                    <span class="inline-flex items-center gap-1">Serial Number &amp; Komponen <i
                        :class="getReportSortIconClass('sn15')"></i></span>
                  </th>
                  <th @click="setReportSort('operator')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'operator'}">
                    <span class="inline-flex items-center gap-1">Operator / Petugas <i
                        :class="getReportSortIconClass('operator')"></i></span>
                  </th>
                  <th @click="setReportSort('date')"
                    class="p-2.5 sm:p-3 w-44 font-bold tracking-wider cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'date'}">
                    <span class="inline-flex items-center gap-1">Waktu Transaksi <i
                        :class="getReportSortIconClass('date')"></i></span>
                  </th>
                  <th @click="setReportSort('status')"
                    class="p-2.5 sm:p-3 w-36 font-bold tracking-wider text-center cursor-pointer hover:bg-slate-200 transition group whitespace-nowrap"
                    :class="{'bg-red-50 text-red-900': reportFilters[reportSubTab].sortBy === 'status'}">
                    <span class="inline-flex items-center gap-1">Status <i
                        :class="getReportSortIconClass('status')"></i></span>
                  </th>
                  <th class="p-2.5 sm:p-3 min-w-[180px] font-bold tracking-wider">Keterangan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                <tr v-if="filteredWarehouseReport.length === 0">
                  <td colspan="8" class="p-8 text-center text-slate-400 font-sans">
                    <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                    <span>Tidak ada data Penerimaan Gudang yang sesuai dengan filter.</span>
                  </td>
                </tr>
                <tr v-for="(it, idx) in pagedWarehouseReport" :key="it.assembly_id || it.id || idx"
                  @click="openDetailModal(it, idx, 'warehouse_receipt')"
                  class="hover:bg-amber-50/50 cursor-pointer transition select-none group"
                  title="Klik baris untuk melihat detail riwayat lengkap">
                  <td
                    class="p-2.5 sm:p-3 w-12 text-center font-bold text-slate-400 font-sans group-hover:text-amber-600 transition">
                    <span class="group-hover:hidden">{{ idx + 1 + (pgState.warehouseReport.page - 1) *
                      pgState.warehouseReport.size }}</span>
                    <i class="fa-solid fa-eye hidden group-hover:inline-block text-xs text-amber-600"></i>
                  </td>
                  <td class="p-2.5 sm:p-3 w-48 whitespace-nowrap font-mono">
                    <span
                      class="font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded text-[11px]">
                      {{ (it.warehouse_doc_number && it.warehouse_doc_number !== '-') ? it.warehouse_doc_number :
                      ((it.qc_doc_number && it.qc_doc_number !== '-') ? it.qc_doc_number :
                      getFormattedDocCode(it.tray_code, 'qc')) }}
                    </span>
                    <span v-if="it.set_number" class="ml-1 text-[10px] font-sans font-bold text-slate-500">Set #{{
                      it.set_number }}</span>
                  </td>
                  <td class="p-2.5 sm:p-3 w-40 whitespace-nowrap font-mono">
                    <span
                      class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-2 py-0.5 rounded font-bold text-[11px]">
                      {{ (it.qc_doc_number && it.qc_doc_number !== '-') ? it.qc_doc_number :
                      getFormattedDocCode(it.tray_code, 'qc') }}
                    </span>
                  </td>
                  <td class="p-2 sm:p-2.5 w-64">
                    <div
                      class="w-full max-w-[240px] border border-slate-200 rounded-md overflow-hidden text-[11px] font-mono bg-white shadow-2xs">
                      <table class="w-full border-collapse">
                        <tbody>
                          <tr class="bg-slate-50/90 border-b border-slate-200">
                            <td colspan="2" class="px-2 py-1">
                              <div class="flex items-center justify-between gap-1.5">
                                <span
                                  class="text-[9px] font-sans font-black px-1.5 py-0.5 bg-red-100 text-red-800 rounded uppercase">P15</span>
                                <span class="font-bold text-slate-900 text-xs truncate"
                                  :title="it.part15_sn || it.p9_sn || '-'">{{ it.part15_sn || it.p9_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                          <tr class="bg-white divide-x divide-slate-200 text-[10.5px]">
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P12:</span>
                                <span class="font-bold text-slate-700 font-mono truncate"
                                  :title="it.part12_sn || '-'">{{ it.part12_sn || '-' }}</span>
                              </div>
                            </td>
                            <td class="px-2 py-0.5 w-1/2">
                              <div class="flex items-center justify-between gap-1">
                                <span class="text-[9px] font-sans font-bold text-slate-400">P1:</span>
                                <span class="font-bold text-slate-700 font-mono truncate" :title="it.part1_sn || '-'">{{
                                  it.part1_sn || '-' }}</span>
                              </div>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans font-semibold text-xs text-slate-700">
                    {{ it.warehouse_receipt_by_name || it.warehouse_receipt_by ||
                    getOperatorFullName(it.verified_by_user) || it.verified_by_user || '-' }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-44 whitespace-nowrap font-sans text-xs text-slate-500">
                    {{ formatDateTime(it.warehouse_receipt_at || it.verified_at || it.qc_tested_at || it.created_at) }}
                  </td>
                  <td class="p-2.5 sm:p-3 w-36 text-center whitespace-nowrap font-sans">
                    <span :class="getStatusTextClass(it.warehouse_receipt_status === 'ACCEPTED' ? 'accepted' : (it.warehouse_receipt_status === 'DECLINED' ? 'declined' : 'pending'))"
                      class="text-xs uppercase tracking-wide">
                      {{ it.warehouse_receipt_status === 'ACCEPTED' ? 'ACCEPTED' : (it.warehouse_receipt_status === 'DECLINED' ? 'DECLINED' : 'PENDING') }}
                    </span>
                  </td>
                  <td class="p-2.5 sm:p-3 min-w-[180px] font-sans text-xs">
                    <span v-if="it.warehouse_receipt_status === 'DECLINED'"
                      class="text-rose-900 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded font-semibold">
                      Ditolak: {{ it.declined_reason || it.reason || '-' }}
                    </span>
                    <span v-else-if="it.warehouse_receipt_status === 'ACCEPTED'" class="text-emerald-700 font-semibold">
                      <i class="fa-solid fa-check text-xs mr-1"></i>Diterima di Gudang
                    </span>
                    <span v-else class="text-amber-800 italic">
                      <i class="fa-solid fa-clock text-xs mr-1"></i>Menunggu verifikasi fisik oleh Petugas Gudang
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination: warehouseReport -->
          <div v-if="reportViewMode === 'detailed' && reportSubTab === 'warehouse_receipt'"
            class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2 mb-4">
            <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
                pgState.warehouseReport.page }}</strong> / {{ totalPgWarehouseReport }} <span
                class="text-slate-400 ml-1">({{ (filteredWarehouseReport||[]).length }} data)</span></span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.warehouseReport.page = 1"
                :disabled="pgState.warehouseReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angles-left"></i></button>
              <button type="button" @click="pgState.warehouseReport.page--"
                :disabled="pgState.warehouseReport.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                  class="fa-solid fa-angle-left"></i></button>
              <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.warehouseReport.page }}</span>
              <button type="button" @click="pgState.warehouseReport.page++"
                :disabled="pgState.warehouseReport.page >= totalPgWarehouseReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angle-right"></i></button>
              <button type="button" @click="pgState.warehouseReport.page = totalPgWarehouseReport"
                :disabled="pgState.warehouseReport.page >= totalPgWarehouseReport"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                  class="fa-solid fa-angles-right"></i></button>
            </div>
          </div>


        </div>

      </section>


@endverbatim
