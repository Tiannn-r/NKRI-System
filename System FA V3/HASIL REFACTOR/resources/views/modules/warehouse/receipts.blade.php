@verbatim
      <!-- ================= SECTION 4.5: PENERIMAAN FISIK GUDANG (STANDALONE MENU) ================= -->
      <section v-if="currentTab === 'warehouse_receipt'" class="space-y-4 sm:space-y-6 animate-fadeIn">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-5">
          <!-- Header Penerimaan Fisik -->
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
              <div class="flex items-center space-x-2.5">
                <div
                  class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-lg font-bold">
                  <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                  <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Penerimaan Fisik Gudang</h2>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">
                    Verifikasi aktual fisik unit yang telah lolos Uji Mutu (QC) sebelum masuk ke stok inventori Gudang.
                  </p>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="loadWarehouseReceiptReports"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-xl font-bold text-xs border border-slate-300 flex items-center space-x-1.5 min-h-[36px] active:scale-95 transition cursor-pointer">
                <i class="fa-solid fa-arrows-rotate text-slate-500"></i>
                <span>Muat Ulang</span>
              </button>
            </div>
          </div>

          <!-- Summary KPI Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Menunggu Verifikasi -->
            <div
              class="bg-gradient-to-br from-amber-50 to-amber-100/40 p-4 rounded-2xl border border-amber-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">Menunggu
                  Verifikasi</span>
                <span class="text-xl sm:text-2xl font-black text-amber-950 font-mono">{{ warehouseReceiptCounts.pending
                  }}</span>
                <span class="text-[10px] text-amber-700 block mt-0.5">Perlu konfirmasi fisik</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-clock"></i>
              </div>
            </div>

            <!-- Diterima / ACC -->
            <div
              class="bg-gradient-to-br from-emerald-50 to-emerald-100/40 p-4 rounded-2xl border border-emerald-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">Diterima
                  (ACC)</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-950 font-mono">{{
                  warehouseReceiptCounts.accepted }}</span>
                <span class="text-[10px] text-emerald-700 block mt-0.5">Fisik valid & masuk stok</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-circle-check"></i>
              </div>
            </div>

            <!-- Ditolak / Declined -->
            <div
              class="bg-gradient-to-br from-rose-50 to-rose-100/40 p-4 rounded-2xl border border-rose-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-rose-800 block">Ditolak
                  (Declined)</span>
                <span class="text-xl sm:text-2xl font-black text-rose-950 font-mono">{{ warehouseReceiptCounts.declined
                  }}</span>
                <span class="text-[10px] text-rose-700 block mt-0.5">Fisik cacat / tidak sesuai</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-circle-xmark"></i>
              </div>
            </div>

            <!-- Total Masuk -->
            <div
              class="bg-gradient-to-br from-slate-50 to-slate-100/60 p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 block">Total Seluruh
                  Unit</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono">{{ warehouseReceiptCounts.total
                  }}</span>
                <span class="text-[10px] text-slate-500 block mt-0.5">Hasil QC pasca-inspeksi</span>
              </div>
              <div
                class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-xs">
                <i class="fa-solid fa-boxes-packing"></i>
              </div>
            </div>
          </div>

          <!-- ================= VIEW 1: DAFTAR DOKUMEN PENERIMAAN GUDANG (KETIKA selectedReceiptTrayCode NULL) ================= -->
          <div v-if="!selectedReceiptTrayCode" class="space-y-4">
            <!-- Universal Filter Bar -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 sm:p-4 space-y-3">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-xs font-bold uppercase text-slate-700 tracking-wider flex items-center gap-2">
                  <i class="fa-solid fa-filter text-emerald-600"></i>
                  <span>Filter Dokumen Penerimaan Fisik</span>
                </div>
                <button @click="resetCurrentReportFilter"
                  class="text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition flex items-center gap-1 cursor-pointer">
                  <i class="fa-solid fa-rotate-left text-[10px]"></i>
                  <span>Atur Ulang Filter</span>
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5">
                <!-- Tanggal Mulai -->
                <div>
                  <label class="text-[10px] font-bold text-slate-500 block mb-1">Tanggal mulai:</label>
                  <input type="date" v-model="reportFilters.warehouse_receipt.startDate"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[36px]">
                </div>

                <!-- Tanggal Selesai -->
                <div>
                  <label class="text-[10px] font-bold text-slate-500 block mb-1">Tanggal selesai:</label>
                  <input type="date" v-model="reportFilters.warehouse_receipt.endDate"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[36px]">
                </div>

                <!-- Filter Status -->
                <div>
                  <label class="text-[10px] font-bold text-slate-500 block mb-1">Status verifikasi:</label>
                  <select v-model="reportFilters.warehouse_receipt.status"
                    class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[36px] cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="PENDING">Menunggu Verifikasi</option>
                    <option value="ACCEPTED">Lengkap Diverifikasi (ACC)</option>
                    <option value="DECLINED">Ada Unit Ditolak (Declined)</option>
                  </select>
                </div>

                <!-- Pencarian -->
                <div>
                  <label class="text-[10px] font-bold text-slate-500 block mb-1">Pencarian dokumen:</label>
                  <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" v-model="reportFilters.warehouse_receipt.searchQuery"
                      placeholder="Cari Dokumen QC / Tray / SN..."
                      class="w-full bg-white border border-slate-300 rounded-xl pl-8 pr-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[36px]">
                  </div>
                </div>
              </div>
            </div>

            <!-- Table of Documents (View 1) -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-xs bg-white">
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-xs">
                  <thead>
                    <tr
                      class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10.5px]">
                      <th class="p-3 text-center w-12">No</th>
                      <th class="p-3 whitespace-nowrap">No. Dokumen QC / Tray</th>
                      <th class="p-3 whitespace-nowrap">Waktu Selesai QC</th>
                      <th class="p-3 whitespace-nowrap">Inspector QC</th>
                      <th class="p-3 text-center whitespace-nowrap min-w-[90px]">Total Unit</th>
                      <th class="p-3 text-center whitespace-nowrap">Status Fisik Gudang</th>
                      <th class="p-3 whitespace-nowrap">Petugas Gudang</th>
                      <th class="p-3 text-center w-36 whitespace-nowrap">Aksi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-medium">
                    <tr v-if="warehouseReceiptTraySummaryList.length === 0">
                      <td colspan="8" class="p-8 text-center text-slate-400">
                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                        <span>Tidak ada dokumen penerimaan fisik QC yang sesuai filter</span>
                      </td>
                    </tr>
                    <tr v-for="(t, idx) in pagedWarehouseReceiptTraySummaryList" :key="t.tray_code"
                      class="hover:bg-slate-50/80 transition">
                      <td class="p-3 text-center font-bold text-slate-500 text-xs">{{ ((pgState.receiptTray.page - 1) *
                        pgState.receiptTray.size) + idx + 1 }}</td>
                      <td class="p-3 whitespace-nowrap">
                        <div class="font-black text-slate-900 font-mono text-xs">{{ t.qc_doc_number }}</div>
                        <div class="text-[10px] text-slate-500 font-mono">Tray: {{ t.tray_code }}</div>
                      </td>
                      <td class="p-3 whitespace-nowrap text-slate-600 font-medium font-mono text-[11px]">
                        {{ formatDateTime(t.qc_tested_at) }}
                      </td>
                      <td class="p-3 text-slate-700 whitespace-nowrap">
                        <div class="font-bold">{{ getOperatorFullName(t.pic_qc) || t.pic_qc || '-' }}</div>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <span
                          class="inline-flex items-center justify-center gap-1 min-w-[76px] bg-slate-100 border border-slate-200 text-slate-800 px-2.5 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap shadow-2xs">
                          <span>{{ t.total_sets }}</span>
                          <span class="text-[10.5px] font-semibold text-slate-500">Unit</span>
                        </span>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <span v-if="t.pending_count > 0"
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1 shadow-xs">
                          <i class="fa-solid fa-clock text-[9px] text-amber-700"></i>
                          <span>{{ t.pending_count }} Menunggu Verifikasi</span>
                        </span>
                        <span v-else-if="t.total_sets > 0 && t.declined_count === 0"
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-xs">
                          <i class="fa-solid fa-circle-check text-[9px] text-emerald-700"></i>
                          <span>Lengkap Diverifikasi ({{ t.accepted_count }} ACC)</span>
                        </span>
                        <span v-else
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1 shadow-xs">
                          <i class="fa-solid fa-circle-xmark text-[9px] text-rose-700"></i>
                          <span>{{ t.declined_count }} Ditolak, {{ t.accepted_count }} ACC</span>
                        </span>
                      </td>
                      <td class="p-3 text-slate-600 text-xs">
                        <div>{{ t.warehouse_receipt_by || '-' }}</div>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1.5">
                          <!-- Tombol Buka Verifikasi -->
                          <button type="button" @click="openReceiptTrayDetail(t.tray_code)"
                            class="w-8 h-8 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl flex items-center justify-center shadow-xs active:scale-95 transition cursor-pointer"
                            title="Buka Verifikasi Fisik Unit">
                            <i class="fa-solid fa-folder-open text-xs"></i>
                          </button>
                          <!-- Tombol Dokumen Cetak / Traveler (Terkunci jika belum selesai diverifikasi) -->
                          <button v-if="t.pending_count === 0 && t.total_sets > 0" type="button"
                            @click="openTravelerWHGRDoc(t.tray_code)"
                            class="w-8 h-8 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-xs active:scale-95 transition cursor-pointer"
                            title="Buka & Cetak Dokumen Penerimaan Gudang (WH/GR)">
                            <i class="fa-solid fa-print text-xs"></i>
                          </button>
                          <button v-else type="button"
                            @click="triggerToast('Dokumen Terkunci', 'Dokumen belum bisa dibuka atau dicetak sebelum seluruh unit diverifikasi fisik (ACC). Klik tombol Buka untuk memverifikasi.', 'warn')"
                            class="w-8 h-8 opacity-60 cursor-pointer bg-slate-100 hover:bg-amber-50 text-slate-500 border border-slate-300 rounded-xl flex items-center justify-center transition"
                            title="Dokumen Terkunci (Perlu verifikasi seluruh unit fisik)">
                            <i class="fa-solid fa-lock text-[11px] text-amber-600"></i>
                          </button>
                          <!-- Tombol Cetak Label (Hanya SN yang sudah ACC) -->
                          <button v-if="t.accepted_count > 0" type="button" @click="openBatchLabelForDoc(t)"
                            class="w-8 h-8 bg-amber-500 hover:bg-amber-600 text-white rounded-xl flex items-center justify-center shadow-xs active:scale-95 transition cursor-pointer"
                            title="Cetak Label Semua SN yang telah ACC pada Dokumen Ini">
                            <i class="fa-solid fa-tags text-xs"></i>
                          </button>
                          <button v-else type="button"
                            @click="triggerToast('Belum Ada Unit ACC', 'Dokumen ini belum memiliki unit yang diverifikasi fisik (ACC).', 'warn')"
                            class="w-8 h-8 opacity-50 cursor-pointer bg-slate-100 hover:bg-amber-50 text-slate-400 border border-slate-200 rounded-xl flex items-center justify-center transition"
                            title="Cetak Label (Belum ada unit ACC)">
                            <i class="fa-solid fa-tags text-[11px] text-slate-400"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination: warehouse_receipt (Good Receipts WH/GR) -->
              <div
                class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/70 select-none no-print">
                <span class="text-[11px] text-slate-500 font-semibold">
                  Hal <strong class="text-slate-700">{{ pgState.receiptTray.page }}</strong> / {{ totalPgReceiptTray }}
                  <span class="text-slate-400 ml-1">({{ (warehouseReceiptTraySummaryList||[]).length }} data)</span>
                </span>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.receiptTray.page = 1" :disabled="pgState.receiptTray.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">
                    <i class="fa-solid fa-angles-left"></i>
                  </button>
                  <button type="button" @click="pgState.receiptTray.page--" :disabled="pgState.receiptTray.page <= 1"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]">
                    <i class="fa-solid fa-angle-left"></i>
                  </button>
                  <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.receiptTray.page }}</span>
                  <button type="button" @click="pgState.receiptTray.page++"
                    :disabled="pgState.receiptTray.page >= totalPgReceiptTray"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">
                    <i class="fa-solid fa-angle-right"></i>
                  </button>
                  <button type="button" @click="pgState.receiptTray.page = totalPgReceiptTray"
                    :disabled="pgState.receiptTray.page >= totalPgReceiptTray"
                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]">
                    <i class="fa-solid fa-angles-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ================= VIEW 2: DETAIL VERIFIKASI FISIK UNIT (KETIKA selectedReceiptTrayCode AKTIF) ================= -->
          <div v-else class="space-y-4">
            <!-- Navigation Header & Actions Bar -->
            <div
              class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-xs">
              <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="selectedReceiptTrayCode = null"
                  class="bg-white hover:bg-slate-100 text-slate-700 font-bold py-2 px-3.5 rounded-xl text-xs border border-slate-300 flex items-center gap-2 shadow-sm active:scale-95 transition cursor-pointer">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span>Kembali ke Daftar Dokumen</span>
                </button>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-slate-500 font-medium">Dokumen:</span>
                  <span class="text-xs font-mono font-black px-3 py-1 rounded-xl bg-slate-900 text-white shadow-xs">
                    {{ activeReceiptTraySummary?.qc_doc_number || selectedReceiptTrayCode }}
                  </span>
                  <span
                    class="text-xs font-mono font-bold text-slate-600 bg-white border border-slate-200 px-2 py-0.5 rounded-lg">
                    Tray: {{ selectedReceiptTrayCode }}
                  </span>
                </div>
              </div>

              <!-- Action Buttons in View 2 -->
              <div class="flex items-center gap-2 flex-wrap">
                <!-- Batch ACC Button for all pending Good QC units -->
                <button v-if="activeReceiptTraySummary && activeReceiptTraySummary.pending_count > 0" type="button"
                  @click="batchAcceptWarehouseReceipt(selectedReceiptTrayCode)" :disabled="isSubmittingBatchReceipt"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3.5 rounded-xl text-xs flex items-center gap-1.5 shadow-md shadow-emerald-600/20 active:scale-95 transition cursor-pointer disabled:opacity-50">
                  <i class="fa-solid fa-check-double"></i>
                  <span>ACC Semua Unit ({{ activeReceiptTraySummary.pending_count }} Unit)</span>
                </button>

                <!-- Document Access Button: Open when verified, Locked if pending -->
                <button v-if="activeReceiptTraySummary && activeReceiptTraySummary.pending_count === 0" type="button"
                  @click="openTravelerWHGRDoc(selectedReceiptTrayCode)"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3.5 rounded-xl text-xs flex items-center gap-1.5 shadow-md shadow-blue-600/20 active:scale-95 transition cursor-pointer"
                  title="Buka & Cetak Dokumen Penerimaan Gudang (WH/GR)">
                  <i class="fa-solid fa-print"></i>
                  <span>Cetak Dokumen (WH/GR)</span>
                </button>
                <button v-else type="button"
                  @click="triggerToast('Dokumen Terkunci', 'Masih ada ' + (activeReceiptTraySummary?.pending_count || 0) + ' unit yang belum diverifikasi. Silakan klik ACC Semua Unit terlebih dahulu untuk membuka dokumen cetak.', 'warn')"
                  class="opacity-60 cursor-pointer bg-slate-200 hover:bg-amber-100 text-slate-600 border border-slate-300 font-bold py-2 px-3 rounded-xl text-xs flex items-center gap-1.5 transition"
                  title="Dokumen belum bisa dibuka sebelum seluruh unit diverifikasi fisik. Klik untuk info.">
                  <i class="fa-solid fa-lock text-slate-500"></i>
                  <span>Dokumen Terkunci (Perlu Verifikasi)</span>
                </button>
                <!-- Cetak Label: Icon Only - Show all SN in document -->
                <button type="button" @click="openBatchLabelList(activeReceiptTrayItems)"
                  class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-3 rounded-xl text-xs flex items-center justify-center shadow-md shadow-amber-500/20 active:scale-95 transition cursor-pointer"
                  title="Cetak Label SN (Semua Unit dalam Dokumen Ini)">
                  <i class="fa-solid fa-tags"></i>
                </button>
              </div>
            </div>

            <!-- Summary Meta Box -->
            <div
              class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-3.5 text-xs text-emerald-950 flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-emerald-700 text-sm"></i>
                <span>Verifikasi fisik barang unit hasil uji mutu QC sebelum dicatat resmi ke dokumen serah terima dan
                  stok gudang.</span>
              </div>
              <div class="flex items-center gap-3 font-mono font-bold text-[11px]">
                <span class="text-amber-800">Menunggu: {{ activeReceiptTraySummary?.pending_count || 0 }}</span>
                <span class="text-emerald-800">Diterima (ACC): {{ activeReceiptTraySummary?.accepted_count || 0
                  }}</span>
                <span class="text-rose-800">Ditolak: {{ activeReceiptTraySummary?.declined_count || 0 }}</span>
              </div>
            </div>

            <!-- Table of Items in this Document -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden shadow-xs bg-white">
              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse font-sans text-xs">
                  <thead>
                    <tr
                      class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10.5px]">
                      <th class="p-3 text-center w-12">No</th>
                      <th class="p-3">SN P9 (Part 15)</th>
                      <th class="p-3">PN Part 1 &amp; 12</th>
                      <th class="p-3 text-center">Status QC</th>
                      <th class="p-3 text-center">Status Fisik Gudang</th>
                      <th class="p-3">Petugas</th>
                      <th class="p-3 text-center w-40">Aksi Verifikasi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(it, idx) in activeReceiptTrayItems" :key="it.assembly_id || idx"
                      class="hover:bg-slate-50/80 transition">
                      <td class="p-3 text-center font-bold text-slate-500 text-xs">{{ idx + 1 }}</td>
                      <td class="p-3 font-mono font-black text-slate-900 text-xs">
                        {{ it.part15_sn || '-' }}
                      </td>
                      <td class="p-3 font-mono text-[11px] text-slate-600">
                        <div>P1: {{ it.part1_sn || '-' }}</div>
                        <div>P12: {{ it.part12_sn || '-' }}</div>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <span
                          class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1">
                          <i class="fa-solid fa-circle-check text-[9px]"></i> GOOD QC
                        </span>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <span v-if="it.warehouse_receipt_status === 'ACCEPTED' || it.warehouse_receipt_status === 'ACC' || it.verified_status === 'ACC' || it.verified_status === 'ACCEPTED'"
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-xs">
                          <i class="fa-solid fa-lock text-[9px] text-emerald-700"></i> ACC
                        </span>
                        <span v-else-if="it.warehouse_receipt_status === 'DECLINED' || it.verified_status === 'DECLINED' || it.is_declined"
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1 shadow-xs"
                          :title="it.declined_reason || 'Ditolak fisik oleh gudang'">
                          <i class="fa-solid fa-lock text-[9px] text-rose-700"></i> DECLINED (Terkunci)
                        </span>
                        <span v-else
                          class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1 shadow-xs">
                          <i class="fa-solid fa-clock text-[9px] text-amber-700"></i> Menunggu Verifikasi
                        </span>
                      </td>
                      <td class="p-3 text-slate-600 text-xs">
                        <div>{{ it.warehouse_receipt_by_name || it.warehouse_receipt_by || '-' }}</div>
                      </td>
                      <td class="p-3 text-center whitespace-nowrap">
                        <!-- If already ACC / ACCEPTED: Allow Cetak Label -->
                        <div v-if="it.warehouse_receipt_status === 'ACCEPTED' || it.warehouse_receipt_status === 'ACC' || it.verified_status === 'ACC' || it.verified_status === 'ACCEPTED'"
                          class="flex items-center justify-center gap-1.5">
                          <button type="button" @click="openLabelPreview(it)"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-[11px] shadow-xs transition inline-flex items-center gap-1.5 active:scale-95 cursor-pointer"
                            title="Cetak Label SN Thermal P9">
                            <i class="fa-solid fa-barcode text-xs"></i>
                            <span>Cetak Label</span>
                          </button>
                        </div>
                        <!-- If declined: Locked -->
                        <div v-else-if="it.warehouse_receipt_status === 'DECLINED' || it.verified_status === 'DECLINED' || it.is_declined">
                          <span
                            class="text-[11px] font-bold text-rose-500 italic flex items-center justify-center gap-1">
                            <i class="fa-solid fa-ban text-[10px]"></i> Unit Ditolak
                          </span>
                        </div>
                        <!-- Action buttons if pending: ACC and Decline only -->
                        <div v-else class="flex items-center justify-center gap-1.5">
                          <button type="button" @click="openWarehouseQCVerifyModal(it, 'DECLINE')"
                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1.5 rounded-lg text-[10.5px] shadow-xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer"
                            title="Decline / Tolak Unit">
                            <i class="fa-solid fa-xmark text-[9px]"></i>
                            <span>Decline</span>
                          </button>
                          <button type="button" @click="openWarehouseQCVerifyModal(it, 'ACCEPT')"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-[10.5px] shadow-xs transition inline-flex items-center gap-1 active:scale-95 cursor-pointer"
                            title="ACC / Terima Fisik">
                            <i class="fa-solid fa-check text-[9px]"></i>
                            <span>ACC</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>


@endverbatim
