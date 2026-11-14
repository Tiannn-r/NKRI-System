@verbatim

      <section v-if="currentTab === 'traveler'" class="space-y-4 sm:space-y-6">

        <!-- Control Bar (NO-PRINT: Will not be printed on paper) -->
        <div class="no-print bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3.5">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-3">
            <div>
              <h2 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Cetak Dokumen</h2>
              <p class="text-xs text-slate-500 font-medium">Pilih Dokumen Tray dan jenis form untuk mencetak fisik
                dokumen ke kertas.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <!-- Document Type Dropdown (Jenis Form Dokumen) -->
              <div
                class="flex items-center bg-white border border-slate-300 rounded-xl px-2.5 py-1 min-h-[38px] shadow-sm focus-within:border-red-600 transition">
                <span class="text-xs font-bold text-slate-600 mr-1.5 flex items-center gap-1 whitespace-nowrap">
                  <i class="fa-solid fa-file-lines text-red-600"></i> Form:
                </span>
                <div
                  class="flex items-center bg-white border border-slate-300 rounded-lg px-2 py-1 focus-within:border-red-600">
                  <select :value="activePrintDocType" @change="selectPrintDocType($event.target.value)"
                    class="bg-transparent text-xs font-bold text-slate-800 outline-none cursor-pointer pr-1">
                    <option value="supply">Supply (WH/WO)</option>
                    <option value="assy">Perakitan (WH/ASSY)</option>
                    <option value="assy_rework">Perakitan Ulang (WH/ASSY/PU)</option>
                    <option value="assy_qc">TF 1 — Tes Fungsional 1 (ASSY/QC)</option>
                    <option value="wh_qc">TF 2 — Tes Fungsional 2 (WH/QC)</option>
                    <option value="wh_gr">Receipts — Penerimaan Gudang (WH/GR)</option>
                  </select>
                </div>
              </div>

              <!-- Quick Edit Source Document MO (Locked Prefix WH/MO/) -->
              <div
                class="flex items-center bg-white border border-slate-300 rounded-xl px-2.5 py-1 min-h-[38px] shadow-sm">
                <span class="text-xs font-bold text-slate-600 mr-1.5 flex items-center gap-1"><i
                    class="fa-solid fa-file-invoice text-slate-400"></i> MO:</span>
                <div
                  class="flex items-center bg-white border border-slate-300 rounded-lg overflow-hidden focus-within:border-red-600">
                  <span
                    class="text-xs font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-1 border-r border-slate-200 select-none">WH/MO/</span>
                  <input type="text" v-model="currentTravelerMOSuffix" @input="onTravelerMOSuffixInput"
                    @keyup.enter="updateTravelerMO" maxlength="6" placeholder="000001"
                    class="bg-transparent text-xs font-mono font-bold text-slate-800 outline-none w-20 px-1.5 py-1 placeholder:font-normal placeholder:italic">
                </div>
                <button @click="updateTravelerMO"
                  class="ml-1.5 text-[10px] px-2.5 py-1.5 bg-slate-100 hover:bg-red-600 hover:text-white rounded-lg font-bold transition flex items-center gap-1"
                  title="Simpan perubahan MO">
                  <i class="fa-solid fa-check text-[9px]"></i> Simpan
                </button>
              </div>



              <!-- Print Button: Text is simply 'Cetak' with icon (Terkunci jika TF 2 / QC atau Penerimaan Gudang belum selesai, atau dokumen PU belum ada) -->
              <button @click="triggerPrint"
                :disabled="(activePrintDocType === 'wh_qc' && (!isCurrentTravelerQCDone || whQCQualifiedItems.length === 0)) || (activePrintDocType === 'wh_gr' && (!isCurrentTravelerWHQCVerified || whGRQualifiedItems.length === 0)) || (activePrintDocType === 'assy_qc' && assyQCQualifiedItems.length === 0) || (activePrintDocType === 'assy_rework' && (!travelerTray || !isPUTray(travelerTray))) || filteredPrintTrays.length === 0 || !selectedTrayCode"
                :class="((activePrintDocType === 'wh_qc' && (!isCurrentTravelerQCDone || whQCQualifiedItems.length === 0)) || (activePrintDocType === 'wh_gr' && (!isCurrentTravelerWHQCVerified || whGRQualifiedItems.length === 0)) || (activePrintDocType === 'assy_qc' && assyQCQualifiedItems.length === 0) || (activePrintDocType === 'assy_rework' && (!travelerTray || !isPUTray(travelerTray))) || filteredPrintTrays.length === 0 || !selectedTrayCode) ? 'opacity-40 cursor-not-allowed bg-slate-400 text-white shadow-none' : 'bg-red-600 hover:bg-red-700 text-white shadow-md shadow-red-600/25 active:scale-95 cursor-pointer'"
                class="px-5 py-2 rounded-xl font-bold text-xs flex items-center justify-center space-x-1.5 min-h-[38px] transition"
                :title="(activePrintDocType === 'wh_qc' && (!isCurrentTravelerQCDone || whQCQualifiedItems.length === 0)) ? 'Dokumen belum bisa dicetak sebelum Test Functional 2 (QC) selesai' : ((activePrintDocType === 'wh_gr' && (!isCurrentTravelerWHQCVerified || whGRQualifiedItems.length === 0)) ? 'Dokumen belum bisa dicetak sebelum seluruh unit diverifikasi fisik (ACC) oleh Gudang' : ((activePrintDocType === 'assy_qc' && assyQCQualifiedItems.length === 0) ? 'Dokumen belum bisa dicetak karena rakitan pada tray ini belum melalui pengujian fungsional (TF 1)' : ((activePrintDocType === 'assy_rework' && (!travelerTray || !isPUTray(travelerTray))) ? 'Dokumen belum tersedia karena belum ada perakitan ulang yang ditugaskan ke operator' : 'Cetak Dokumen ke Kertas')))">
                <i
                  :class="((activePrintDocType === 'wh_qc' && (!isCurrentTravelerQCDone || whQCQualifiedItems.length === 0)) || (activePrintDocType === 'wh_gr' && (!isCurrentTravelerWHQCVerified || whGRQualifiedItems.length === 0)) || (activePrintDocType === 'assy_qc' && assyQCQualifiedItems.length === 0) || (activePrintDocType === 'assy_rework' && (!travelerTray || !isPUTray(travelerTray))) || filteredPrintTrays.length === 0 || !selectedTrayCode) ? 'fa-solid fa-lock text-[11px]' : 'fa-solid fa-print'"></i>
                <span>Cetak</span>
              </button>
            </div>
          </div>

          <!-- Filter & Search Bar for Document Trays -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 pt-1 items-end">
            <!-- Filter Tanggal Mulai -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 mb-1">
                <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal mulai
              </label>
              <input type="date" v-model="printFilter.startDate"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-red-600 min-h-[36px]">
            </div>

            <!-- Filter Tanggal Selesai -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 mb-1">
                <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal selesai
              </label>
              <input type="date" v-model="printFilter.endDate"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-red-600 min-h-[36px]">
            </div>

            <!-- Filter Operator Assembly -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 mb-1">
                <i class="fa-solid fa-user-gear mr-1"></i> Operator assembly
              </label>
              <select v-model="printFilter.operator"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-bold focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                <option value="">Semua operator</option>
                <option v-for="op in distinctPrintOperators" :key="op" :value="op">{{ getOperatorFullName(op) }}
                </option>
              </select>
            </div>

            <!-- Filter Status Set (Khusus Good Saja sesuai revisi) -->
            <div>
              <label class="block text-[10px] font-bold text-slate-500 mb-1">
                <i class="fa-solid fa-filter mr-1"></i> Filter status set:
              </label>
              <select v-model="printStatusFilter"
                class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-bold focus:outline-none focus:border-red-600 min-h-[36px] cursor-pointer">
                <option value="ALL">Semua status</option>
                <option value="GOOD">Hanya good saja</option>
              </select>
            </div>

            <!-- Select Tray from Filtered List -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-[10px] font-bold text-slate-700">
                  <i class="fa-solid fa-file-lines mr-1 text-red-600"></i> Pilih dokumen tray:
                </label>
                <span v-if="filteredPrintTrays.length > 0" class="text-[10px] text-slate-400 font-medium">({{
                  filteredPrintTrays.length }} tray)</span>
              </div>
              <select v-model="selectedTrayCode" @change="loadTravelerTray"
                class="w-full bg-white border border-slate-300 text-slate-900 font-black text-xs px-3 py-1.5 rounded-xl min-h-[36px] focus:outline-none focus:border-red-600">
                <option v-for="t in filteredPrintTrays" :key="t.tray_code" :value="t.tray_code">
                  {{ getFormattedDocCode(t.tray_code, activePrintDocType) }} - {{ t.assigned_operator ?
                  getOperatorFullName(t.assigned_operator) : 'Belum Ditugaskan' }} ({{ getPrintDocSetCount(t) }} Set)
                </option>
                <option v-if="filteredPrintTrays.length === 0" disabled value="">
                  {{ activePrintDocType === 'assy_rework' ? '(Belum ada dokumen perakitan ulang)' : '(Tidak ada tray yang cocok dengan filter)' }}
                </option>
              </select>
            </div>
          </div>

        </div>

        <!-- Printable Official Document Paper Sheet (ONLY this section prints) -->
        <div class="doc-viewer-shell">
          <div id="printable-area" class="doc-paper text-slate-900" style="font-family: 'Roboto', Arial, sans-serif;">

            <!-- ================= 1. FORM DOC SUPPLY (WH/WO) ================= -->
            <template v-if="activePrintDocType === 'supply'">
              <!-- Empty state: jika dokumen supply belum ada / tray belum dipilih -->
              <div
                v-if="!travelerTray || filteredPrintTrays.length === 0"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Dokumen Supply Material Belum Tersedia</h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Belum ada dokumen perintah supply material (WH/WO) yang dipilih atau sesuai dengan filter pencarian saat ini.
                </p>
              </div>
              <div v-else class="print-doc-wrapper flex flex-col justify-between flex-grow h-full w-full">
                <!-- Top Section: Header + Meta + Table -->
                <div class="print-top-section space-y-3">
                  <!-- Top Title Bar with Logo & Document Number -->
                  <div class="border-b-2 border-slate-900 pb-2.5 flex items-end justify-between">
                    <div class="flex flex-col items-start space-y-1">
                      <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo" class="h-9 sm:h-11 w-auto object-contain flex-shrink-0">
                      <h2 class="text-base sm:text-lg font-black tracking-wider text-slate-900 leading-none pt-0.5">
                        Final Assembly</h2>
                      <span class="text-[11px] sm:text-xs font-bold text-slate-600 tracking-wide">Surat
                        Perintah Supply Material (Work Order)</span>
                    </div>
                    <div class="text-right">
                      <div class="text-[9px] sm:text-[10px] font-bold text-slate-500">No. Dokumen Supply (Gudang)</div>
                      <div class="text-lg sm:text-xl font-mono font-black text-red-800 tracking-wider">
                        {{ getFormattedDocCode(travelerTray ? travelerTray.tray_code : '', 'supply') }}
                      </div>
                    </div>
                  </div>

                  <!-- Metadata Box (Crisp Rectangular Grid, Pure White Background) -->
                  <div class="print-meta-box">
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 text-[10px] sm:text-[11px]">
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Tanggal Supply</span>
                        <strong class="text-slate-900 font-semibold whitespace-nowrap">{{ travelerTray ?
                          formatDateTime(travelerTray.created_at) : '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Petugas Gudang</span>
                        <strong class="text-slate-900 font-semibold">{{ getOperatorFullName(travelerTray ?
                          travelerTray.pic_warehouse : '') || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Ditugaskan Ke</span>
                        <strong class="text-red-700 font-bold">{{ travelerAssyOperator ?
                          getOperatorFullName(travelerAssyOperator) : (travelerTray && travelerTray.assigned_operator ?
                          getOperatorFullName(travelerTray.assigned_operator) : '-') }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Jenis Produk</span>
                        <strong class="text-slate-900 font-bold">{{ travelerTray && travelerTray.product_type ?
                          travelerTray.product_type : 'Nanggala P9' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Dokumen Asal</span>
                        <strong class="font-mono text-red-800 font-bold">{{ (travelerTray &&
                          travelerTray.source_document_mo && travelerTray.source_document_mo.startsWith('WH/MO/')) ?
                          travelerTray.source_document_mo : getFormattedDocCode(travelerTray ? travelerTray.tray_code :
                          '', 'mo') }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- Supply Material Items Table (Crisp Sharp Borders) -->
                  <div class="print-items-wrapper border border-slate-800 bg-white">
                    <table class="w-full text-left text-xs border-collapse table-fixed">
                      <colgroup>
                        <col style="width: 10%;">
                        <col style="width: 32%;">
                        <col style="width: 29%;">
                        <col style="width: 29%;">
                      </colgroup>
                      <thead
                        class="bg-white text-slate-900 border-b border-slate-800 font-bold text-[10px] tracking-wider">
                        <tr class="divide-x divide-slate-300 border-b border-slate-300">
                          <th rowspan="2" class="p-1.5 text-center bg-white">No</th>
                          <th class="p-1.5 text-center bg-white">Serial Number</th>
                          <th colspan="2" class="p-1.5 text-center bg-white">Production Number</th>
                        </tr>
                        <tr class="divide-x divide-slate-300">
                          <th class="p-1.5 text-center bg-white">P9 Part 15</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 1</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 12</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-300 text-slate-900 font-mono text-[11px]">
                        <tr v-for="item in travelerItems" :key="item.set_number"
                          class="divide-x divide-slate-200 bg-white">
                          <td class="p-2 text-center font-bold font-sans text-slate-700 bg-white">{{
                            item.set_number }}</td>
                          <td class="p-2 text-center font-bold text-slate-900 break-all">{{ item.part15_sn || '-' }}
                          </td>
                          <td class="p-2 text-center text-slate-800 break-all">{{ item.part1_sn || '-' }}</td>
                          <td class="p-2 text-center text-slate-800 break-all">{{ item.part12_sn || '-' }}</td>
                        </tr>
                        <tr v-if="travelerItems.length === 0">
                          <td colspan="4" class="p-4 text-center font-sans" :class="isAllSetsReturnedWithoutFinish ? 'text-red-700 font-bold uppercase tracking-wider bg-red-50/30' : 'text-slate-400'">
                            <span v-if="isAllSetsReturnedWithoutFinish" class="inline-flex items-center justify-center gap-2">
                              <i class="fa-solid fa-boxes-packing text-red-600"></i>
                              Seluruh set dikembalikan ke gudang
                            </span>
                            <span v-else>Tidak ada data part dalam dokumen supply ini.</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Signatures (Pinned to the bottom with spacious vertical gap) -->
                <div
                  class="print-footer-signatures grid grid-cols-2 gap-8 pt-6 sm:pt-7 border-t-2 border-black text-xs font-sans text-center mt-auto">
                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diserahkan
                      Oleh (Gudang):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ travelerTray ? getOperatorFullName(travelerTray.pic_warehouse) : '........................'
                        }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Petugas Gudang Supply
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diterima
                      Oleh (Perakitan):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ travelerAssyOperator ? getOperatorFullName(travelerAssyOperator) : (travelerTray &&
                        travelerTray.assigned_operator ? getOperatorFullName(travelerTray.assigned_operator) :
                        '........................') }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Operator Final Assembly
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- ================= 2. FORM DOC ASSY (WH/ASSY & WH/ASSY-PU) ================= -->
            <template v-else-if="activePrintDocType === 'assy' || activePrintDocType === 'assy_rework'">
              <!-- Empty state: jika dokumen PU belum ada / belum ditunjuk ke operator & mulai dirakit ulang -->
              <div
                v-if="activePrintDocType === 'assy_rework' && (!travelerTray || !isPUTray(travelerTray) || filteredPrintTrays.length === 0)"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Dokumen Perakitan Ulang Belum Tersedia
                </h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Belum ada dokumen perakitan ulang (-PU) yang dibuat atau sesuai dengan filter pencarian saat ini.
                </p>
              </div>
              <!-- Empty state: jika dokumen perakitan normal belum ada / belum dipilih -->
              <div
                v-else-if="activePrintDocType === 'assy' && (!travelerTray || filteredPrintTrays.length === 0)"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Dokumen Hasil Perakitan Belum Tersedia</h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Belum ada data perakitan pada tray yang dipilih atau sesuai dengan filter pencarian saat ini.
                </p>
              </div>
              <div v-else class="print-doc-wrapper flex flex-col justify-between flex-grow h-full w-full">
                <!-- Top Section: Header + Meta + Table -->
                <div class="print-top-section space-y-3">
                  <!-- Top Title Bar with Logo & Document Number -->
                  <div class="border-b-2 border-slate-900 pb-2.5 flex items-end justify-between">
                    <div class="flex flex-col items-start space-y-1">
                      <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo" class="h-9 sm:h-11 w-auto object-contain flex-shrink-0">
                      <h2 class="text-base sm:text-lg font-black tracking-wider text-slate-900 leading-none pt-0.5">
                        {{ activePrintDocType === 'assy_rework' ? 'Perakitan Ulang' : 'Final Assembly'
                        }}
                      </h2>
                      <span class="text-[11px] sm:text-xs font-bold text-slate-600 tracking-wide">
                        {{ activePrintDocType === 'assy_rework' ? 'Laporan Perakitan Ulang & Traceability Suffix PU' :
                        'Laporan Hasil Perakitan & Traceability (Traveler)' }}
                      </span>
                    </div>
                    <div class="text-right">
                      <div class="text-[9px] sm:text-[10px] font-bold text-slate-500">
                        {{ activePrintDocType === 'assy_rework' ? 'No. Dokumen Perakitan Ulang (Assy PU)' : 'No. Dokumen Perakitan (Assy)' }}
                      </div>
                      <div class="text-lg sm:text-xl font-mono font-black text-red-800 tracking-wider">
                        {{ getFormattedDocCode(travelerTray ? travelerTray.tray_code : '', activePrintDocType) }}
                      </div>
                    </div>
                  </div>

                  <!-- Metadata Box (Crisp Rectangular Grid, Pure White Background) -->
                  <div class="print-meta-box">
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 text-[10px] sm:text-[11px]">
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Tanggal Supply</span>
                        <strong class="text-slate-900 font-semibold whitespace-nowrap">{{ travelerTray ?
                          formatDateTime(travelerTray.created_at) : '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Petugas Gudang</span>
                        <strong class="text-slate-900 font-semibold">{{ getOperatorFullName(travelerTray ?
                          travelerTray.pic_warehouse : '') || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Ditugaskan Ke</span>
                        <strong class="text-red-700 font-bold">{{ travelerAssyOperator ?
                          getOperatorFullName(travelerAssyOperator) : '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Jenis Produk</span>
                        <strong class="text-slate-900 font-bold">{{ travelerTray && travelerTray.product_type ?
                          travelerTray.product_type : 'Nanggala P9' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Dokumen Asal</span>
                        <strong class="font-mono text-red-800 font-bold">{{ activePrintDocType === 'assy_rework' ?
                          getFormattedDocCode(travelerTray ? travelerTray.tray_code : '', 'origin_assy') :
                          getFormattedDocCode(travelerTray ? travelerTray.tray_code : '', 'wo') }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- Assembly Items Table (Crisp Sharp Borders) -->
                  <div class="print-items-wrapper border border-slate-800 bg-white">
                    <table class="w-full text-left text-xs border-collapse table-fixed">
                      <colgroup>
                        <col style="width: 5.5%;">
                        <col style="width: 23%;">
                        <col style="width: 19%;">
                        <col style="width: 19%;">
                        <col style="width: 10.5%;">
                        <col style="width: 10.5%;">
                        <col style="width: 12.5%;">
                      </colgroup>
                      <thead
                        class="bg-white text-slate-900 border-b border-slate-800 font-bold text-[10px] tracking-wider">
                        <tr class="divide-x divide-slate-300 border-b border-slate-300">
                          <th rowspan="2" class="p-1.5 text-center bg-white">No</th>
                          <th class="p-1.5 text-center bg-white">Serial Number</th>
                          <th colspan="2" class="p-1.5 text-center bg-white">Production Number</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Mulai</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Selesai</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Status</th>
                        </tr>
                        <tr class="divide-x divide-slate-300">
                          <th class="p-1.5 text-center bg-white">P9 Part 15</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 1</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 12</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-300 text-slate-900 font-mono text-[11px]">
                        <tr v-for="item in travelerItems" :key="item.set_number"
                          class="divide-x divide-slate-200 bg-white">
                          <td class="p-2 text-center font-bold font-sans text-slate-700 bg-white">{{
                            item.set_number }}</td>
                          <td class="p-2 text-center break-all">
                            <span :class="getDefectiveTableCellClass(item, '15')">{{ item.part15_sn || '-' }}</span>
                          </td>
                          <td class="p-2 text-center break-all">
                            <span :class="getDefectiveTableCellClass(item, '1')">{{ item.part1_sn || '-' }}</span>
                          </td>
                          <td class="p-2 text-center break-all">
                            <span :class="getDefectiveTableCellClass(item, '12')">{{ item.part12_sn || '-' }}</span>
                          </td>
                          <td class="p-2 text-center text-slate-700 font-sans text-[10px]">{{ getItemStartTime(item) }}
                          </td>
                          <td class="p-2 text-center text-slate-700 font-sans text-[10px]">{{ getItemFinishTime(item) }}
                          </td>
                          <td class="p-1.5 text-center font-sans">
                            <span v-if="getTravelerItemActualStatus(item) && getTravelerItemActualStatus(item) !== '-'"
                              :class="getDocStatusTextClass(getTravelerItemActualStatus(item))"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block whitespace-nowrap">
                              {{ formatDocStatus(getTravelerItemActualStatus(item)) }}
                            </span>
                            <span v-else class="text-slate-400 font-mono text-xs font-bold">-</span>
                          </td>
                        </tr>
                        <tr v-if="travelerItems.length === 0">
                          <td colspan="7" class="p-4 text-center font-sans" :class="isAllSetsReturnedWithoutFinish ? 'text-red-700 font-bold uppercase tracking-wider bg-red-50/30' : 'text-slate-400'">
                            <span v-if="isAllSetsReturnedWithoutFinish" class="inline-flex items-center justify-center gap-2">
                              <i class="fa-solid fa-boxes-packing text-red-600"></i>
                              Seluruh set dikembalikan ke gudang
                            </span>
                            <span v-else>Tidak ada data part dalam dokumen perakitan ini.</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Signatures (Pinned to the bottom with spacious vertical gap - Diserahkan Oleh Gudang & Dirakit Oleh Perakitan) -->
                <div
                  class="print-footer-signatures grid grid-cols-2 gap-8 pt-6 sm:pt-7 border-t-2 border-black text-xs font-sans text-center mt-auto">
                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diserahkan
                      Oleh (Gudang):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ travelerTray ? getOperatorFullName(travelerTray.pic_warehouse) : '........................'
                        }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Petugas Gudang Supply
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Dirakit
                      Oleh (Perakitan):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ travelerAssyOperator ? getOperatorFullName(travelerAssyOperator) : '........................'
                        }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Operator Final Assembly
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- ================= 3. FORM DOC UJI FUNGSIONAL ASSY (Assy/QC) ================= -->
            <template v-else-if="activePrintDocType === 'assy_qc'">
              <!-- Empty state: jika dokumen hasil uji fungsional belum ada / belum diuji TF 1 -->
              <div
                v-if="!travelerTray || filteredPrintTrays.length === 0 || assyQCQualifiedItems.length === 0"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Laporan Hasil Uji Fungsional Belum Tersedia</h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Rakitan pada tray ini belum melalui pengujian fungsional assembly (TF 1). Laporan hanya dapat diterbitkan setelah pengujian dilakukan oleh operator tester.
                </p>
              </div>
              <div v-else class="print-doc-wrapper flex flex-col justify-between flex-grow h-full w-full">
                <!-- Top Section: Header + Meta + Table -->
                <div class="print-top-section space-y-3">
                  <!-- Top Title Bar with Logo & Document Number -->
                  <div class="border-b-2 border-slate-900 pb-2.5 flex items-end justify-between">
                    <div class="flex flex-col items-start space-y-1">
                      <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo" class="h-9 sm:h-11 w-auto object-contain flex-shrink-0">
                      <h2 class="text-base sm:text-lg font-black tracking-wider text-slate-900 leading-none pt-0.5">
                        Test Functional 1 (TF 1)</h2>
                      <span class="text-[11px] sm:text-xs font-bold text-slate-600 tracking-wide">Laporan Hasil Uji
                        Fungsional Assembly (ASSY/QC)</span>
                    </div>
                    <div class="text-right">
                      <div class="text-[9px] sm:text-[10px] font-bold text-slate-500">No. Dokumen TF 1 (ASSY/QC)</div>
                      <div class="text-lg sm:text-xl font-mono font-black text-indigo-900 tracking-wider">
                        {{ activeAssyQCDocSlot && activeAssyQCDocSlot.assy_doc_number ?
                        activeAssyQCDocSlot.assy_doc_number : (assyQCQualifiedItems.find(i =>
                        i.assy_doc_number)?.assy_doc_number || '-') }}
                      </div>
                    </div>
                  </div>

                  <!-- Metadata Box (Crisp Rectangular Grid, Pure White Background) -->
                  <div class="print-meta-box">
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 text-[10px] sm:text-[11px]">
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Tanggal Uji</span>
                        <strong class="text-slate-900 font-semibold">{{ activeAssyQCDocSlot &&
                          activeAssyQCDocSlot.assy_tested_at ? formatDateTime(activeAssyQCDocSlot.assy_tested_at) :
                          (assyQCQualifiedItems.find(i => i.assy_tested_at)?.assy_tested_at ?
                          formatDateTime(assyQCQualifiedItems.find(i => i.assy_tested_at).assy_tested_at) : '-')
                          }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Operator Tester</span>
                        <strong class="text-indigo-900 font-bold">{{ activeAssyQCDocSlot &&
                          activeAssyQCDocSlot.assy_tester_fullname ? activeAssyQCDocSlot.assy_tester_fullname :
                          (assyQCQualifiedItems.find(i => i.assy_tester_fullname)?.assy_tester_fullname ||
                          (travelerAssyOperator ? getOperatorFullName(travelerAssyOperator) : '-')) }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Petugas Gudang</span>
                        <strong class="text-slate-900 font-semibold">{{ getOperatorFullName(travelerTray ?
                          travelerTray.pic_warehouse : '') || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Jenis Produk</span>
                        <strong class="text-slate-900 font-bold">{{ travelerTray && travelerTray.product_type ?
                          travelerTray.product_type : 'Nanggala P9' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Dokumen Asal</span>
                        <strong class="font-mono text-indigo-900 font-bold">{{ (activeAssyQCDocSlot &&
                          activeAssyQCDocSlot.assy_doc_number &&
                          activeAssyQCDocSlot.assy_doc_number.toUpperCase().startsWith('WH/ASSY') ?
                          activeAssyQCDocSlot.assy_doc_number : null) || getFormattedDocCode(travelerTray ?
                          travelerTray.tray_code : '', 'assy') }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- Assembly Items / Test Table (Crisp Sharp Borders) -->
                  <div class="print-items-wrapper border border-slate-800 bg-white">
                    <table class="w-full text-left text-xs border-collapse table-fixed">
                      <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 11%;">
                        <col style="width: 13%;">
                        <col style="width: 9%;">
                        <col style="width: 9%;">
                        <col style="width: 25%;">
                        <col style="width: 28%;">
                      </colgroup>
                      <thead
                        class="bg-white text-slate-900 border-b border-slate-800 font-bold text-[9.5px] tracking-wider">
                        <tr class="divide-x divide-slate-300 border-b border-slate-300">
                          <th rowspan="2" class="p-1.5 text-center bg-white">Set</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Tanggal</th>
                          <th class="p-1.5 text-center bg-white">Serial Number</th>
                          <th colspan="2" class="p-1.5 text-center bg-white">Production Number</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Status Uji</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Tahap Uji (1 - 15)</th>
                        </tr>
                        <tr class="divide-x divide-slate-300">
                          <th class="p-1.5 text-center bg-white">P9 Part 15</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 1</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 12</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-300 text-slate-900 font-mono text-[10px]">
                        <tr v-for="item in assyQCQualifiedItems" :key="item.set_number"
                          class="divide-x divide-slate-200 bg-white">
                          <td class="p-1 text-center font-bold font-sans text-slate-700 bg-white">#{{
                            item.set_number }}</td>
                          <td class="p-1 text-center font-sans text-slate-700 text-[9px] leading-tight">{{
                            formatDateOnly(item.assy_tested_at || item.finished_at || (travelerTray ?
                            travelerTray.created_at : '')) }}</td>
                          <td class="p-1 text-center font-bold text-slate-900 break-all text-[9px] leading-tight">{{
                            item.part15_sn || '-' }}</td>
                          <td class="p-1 text-center text-slate-800 break-all text-[9px] leading-tight">{{ item.part1_sn
                            || '-' }}</td>
                          <td class="p-1 text-center text-slate-800 break-all text-[9px] leading-tight">{{
                            item.part12_sn || '-' }}</td>
                          <td class="p-1 text-center font-sans">
                            <!-- Status Aktual Pengujian Assembly (TF 1) -->
                            <span :class="getDocStatusTextClass(getAssyTestItemActualStatus(item))"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-tight whitespace-nowrap">
                              {{ formatDocStatus(getAssyTestItemActualStatus(item)) }}
                            </span>
                          </td>
                          <td class="p-0.5 font-sans">
                            <table
                              class="nested-checklist-table w-full text-center border border-slate-600 font-sans text-[7px] border-collapse bg-white">
                              <tbody>
                                <tr v-for="(row, rIdx) in [[1,2,3,4,5], [6,7,8,9,10], [11,12,13,14,15]]" :key="rIdx"
                                  class="divide-x divide-slate-300 border-b border-slate-300 last:border-b-0">
                                  <td v-for="n in row" :key="n" class="p-0.5 text-center whitespace-nowrap"
                                    :class="getTestItemParamStatus(item, n) === 'GOOD' ? 'bg-emerald-50/30' : (getTestItemParamStatus(item, n) === 'NOT_GOOD' ? 'bg-red-50/50' : 'bg-white')"
                                    style="width: 20%;">
                                    <span class="font-bold text-slate-700 text-[6.5px]">T{{ n }}:</span>
                                    <span v-if="getTestItemParamStatus(item, n) === 'GOOD'"
                                      class="text-emerald-700 font-black text-[7.5px] ml-0.5">&#10003;</span>
                                    <span v-else-if="getTestItemParamStatus(item, n) === 'NOT_GOOD'"
                                      class="text-red-600 font-black text-[7.5px] ml-0.5">&#10007;</span>
                                    <span v-else class="text-slate-400 font-black text-[7.5px] ml-0.5">-</span>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                        <tr v-if="assyQCQualifiedItems.length === 0">
                          <td colspan="7" class="p-6 text-center text-slate-500 font-sans">
                            <div v-if="isAllSetsReturnedWithoutFinish" class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-boxes-packing text-red-600 text-lg"></i>
                              <span class="font-bold text-xs text-red-700 uppercase tracking-wide">Seluruh set dikembalikan ke gudang</span>
                              <span class="text-[11px] text-slate-400">Tray ini telah dikembalikan seluruhnya ke gudang dan belum menyelesaikan perakitan set.</span>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-flask-vial text-slate-400 text-lg"></i>
                              <span class="font-bold text-xs">Laporan Hasil Uji Fungsional Belum Tersedia</span>
                              <span class="text-[11px] text-slate-400">Rakitan pada tray ini belum melalui pengujian
                                fungsional assembly. Laporan hanya dapat diterbitkan setelah pengujian dilakukan oleh
                                operator tester.</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Signatures (2 Blok Tanda Tangan: Diserahkan Oleh Gudang & Dirakit/Diuji Oleh Perakitan) -->
                <div
                  class="print-footer-signatures grid grid-cols-2 gap-8 pt-6 sm:pt-7 border-t-2 border-black text-xs font-sans text-center mt-auto">
                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diserahkan
                      Oleh (Gudang):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ travelerTray ? getOperatorFullName(travelerTray.pic_warehouse) : '........................'
                        }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Petugas Gudang Supply
                      </div>
                    </div>
                  </div>

                  <div class="flex flex-col justify-between items-center text-center">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Dirakit
                      &amp; Diuji Oleh (Perakitan):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ activeAssyQCDocSlot && activeAssyQCDocSlot.assy_tester_fullname ?
                        activeAssyQCDocSlot.assy_tester_fullname : (travelerAssyOperator ?
                        getOperatorFullName(travelerAssyOperator) : '........................') }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Operator Final Assembly
                        &amp; Tester</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- ================= 4. DOKUMEN MUTU QC (WH/QC) ================= -->
            <template v-if="activePrintDocType === 'wh_qc'">
              <!-- Empty state: jika dokumen mutu QC belum ada / belum lulus uji QC -->
              <div
                v-if="!travelerTray || filteredPrintTrays.length === 0 || whQCQualifiedItems.length === 0 || !isCurrentTravelerQCDone"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Laporan Dokumen Mutu (WH/QC) Belum Tersedia</h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Rakitan pada tray ini belum menyelesaikan tahapan Uji Perakitan dan Pengujian QC. Dokumen resmi serah terima mutu baru dapat diterbitkan setelah seluruh rakitan lulus uji perakitan dan selesai diverifikasi oleh Inspector QC.
                </p>
              </div>
              <div v-else class="print-doc-wrapper flex flex-col justify-between flex-grow h-full w-full">
                <!-- Top Section: Header + Meta + Table -->
                <div class="print-top-section space-y-3">
                  <!-- Top Title Bar with Logo & Document Number -->
                  <div class="border-b-2 border-slate-900 pb-2.5 flex items-end justify-between">
                    <div class="flex flex-col items-start space-y-1">
                      <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo" class="h-9 sm:h-11 w-auto object-contain flex-shrink-0">
                      <h2 class="text-base sm:text-lg font-black tracking-wider text-slate-900 leading-none pt-0.5">
                        Test Functional 2 (TF 2)</h2>
                      <span class="text-[11px] sm:text-xs font-bold text-slate-600 tracking-wide">Surat Verifikasi &amp;
                        Serah Terima Mutu (WH/QC)</span>
                    </div>
                    <div class="text-right">
                      <div class="text-[9px] sm:text-[10px] font-bold text-slate-500">No. Dokumen TF 2 (WH/QC)</div>
                      <div class="text-lg sm:text-xl font-mono font-black text-emerald-800 tracking-wider">
                        {{ (whQCQualifiedItems.find(i => i.qc_doc_number)?.qc_doc_number) ||
                        getFormattedDocCode(travelerTray ? travelerTray.tray_code : '', 'qc') }}
                      </div>
                    </div>
                  </div>

                  <!-- Metadata Box -->
                  <div class="print-meta-box">
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 text-[10px] sm:text-[11px]">
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Tanggal Verif QC</span>
                        <strong class="text-slate-900 font-semibold">{{ (whQCQualifiedItems.find(i =>
                          i.qc_tested_at)?.qc_tested_at ? formatDateTime(whQCQualifiedItems.find(i =>
                          i.qc_tested_at).qc_tested_at) : (travelerTray ? formatDateTime(travelerTray.created_at) :
                          '-')) }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Inspector QC</span>
                        <strong class="text-emerald-900 font-bold">{{ getInspectorQC(whQCQualifiedItems) || '-'
                          }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Petugas Gudang</span>
                        <strong class="text-slate-900 font-semibold">{{ getOperatorFullName(travelerTray ?
                          travelerTray.pic_warehouse : '') || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Jenis Produk</span>
                        <strong class="text-slate-900 font-bold">{{ travelerTray && travelerTray.product_type ?
                          travelerTray.product_type : 'Nanggala P9' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Dokumen Asal</span>
                        <strong class="font-mono text-emerald-900 font-bold">{{ (whQCQualifiedItems.find(i =>
                          i.assy_doc_number)?.assy_doc_number) || getFormattedDocCode(travelerTray ?
                          travelerTray.tray_code : '', 'assy_qc') }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- QC Verified Items Table -->
                  <div class="print-items-wrapper border border-slate-800 bg-white">
                    <table class="w-full text-left text-xs border-collapse table-fixed">
                      <colgroup>
                        <col style="width: 6%;">
                        <col style="width: 16%;">
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 20%;">
                        <col style="width: 34%;">
                      </colgroup>
                      <thead
                        class="bg-white text-slate-900 border-b border-slate-800 font-bold text-[10px] tracking-wider">
                        <tr class="divide-x divide-slate-300 border-b border-slate-300">
                          <th rowspan="2" class="p-1.5 text-center bg-white">Set</th>
                          <th class="p-1.5 text-center bg-white">Serial Number</th>
                          <th colspan="2" class="p-1.5 text-center bg-white">Production Number</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Status Testing Assy</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Hasil Test Functional 2 (QC)</th>
                        </tr>
                        <tr class="divide-x divide-slate-300">
                          <th class="p-1.5 text-center bg-white">P9 Part 15</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 1</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 12</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-300 text-slate-900 font-mono text-[11px]">
                        <tr v-for="item in whQCQualifiedItems" :key="item.set_number"
                          class="divide-x divide-slate-200 bg-white">
                          <td class="p-1.5 text-center font-bold font-sans text-slate-700 bg-white">#{{
                            item.set_number }}</td>
                          <td class="p-1.5 text-center font-black text-slate-900 break-all text-[10px]">{{
                            item.part15_sn || '-' }}
                          </td>
                          <td class="p-1.5 text-center text-slate-800 break-all text-[10px]">{{ item.part1_sn || '-' }}
                          </td>
                          <td class="p-1.5 text-center text-slate-800 break-all text-[10px]">{{ item.part12_sn || '-' }}
                          </td>
                          <td class="p-1.5 text-center font-sans">
                            <!-- Status Testing Assy di Dokumen QC selalu GOOD karena hanya unit lulus yang masuk QC -->
                            <span class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-tight text-emerald-700 whitespace-nowrap">
                              GOOD
                            </span>
                          </td>
                          <td class="p-1.5 text-center font-sans">
                            <!-- Status Riwayat Pengujian yang terjadi di tahap QC (TF 2) -->
                            <span :class="getDocStatusTextClass(item.qc_status || 'Good')"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-tight whitespace-nowrap">
                              {{ formatDocStatus(item.qc_status || 'Good') }}
                            </span>
                          </td>
                        </tr>
                        <tr v-if="whQCQualifiedItems.length === 0">
                          <td colspan="6" class="p-6 text-center text-slate-500 font-sans">
                            <div v-if="isAllSetsReturnedWithoutFinish" class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-boxes-packing text-red-600 text-lg"></i>
                              <span class="font-bold text-xs text-red-700 uppercase tracking-wide">Seluruh set dikembalikan ke gudang</span>
                              <span class="text-[11px] text-slate-400">Tray ini telah dikembalikan seluruhnya ke gudang dan belum menyelesaikan perakitan set.</span>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-clock-rotate-left text-amber-500 text-lg"></i>
                              <span class="font-bold text-xs text-slate-700">Laporan Dokumen Mutu (WH/QC) Belum
                                Tersedia</span>
                              <span class="text-[11px] text-slate-400">Rakitan pada tray ini belum menyelesaikan tahapan
                                Uji Perakitan dan Pengujian QC. Dokumen resmi serah terima mutu baru dapat diterbitkan
                                setelah seluruh rakitan lulus uji perakitan dan selesai diverifikasi oleh Inspector
                                QC.</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Signatures: QC Inspector only (Warehouse Receiver moved to Receipts doc) -->
                <div
                  class="print-footer-signatures flex justify-center pt-6 sm:pt-7 border-t-2 border-black text-xs font-sans text-center mt-auto">
                  <div class="flex flex-col justify-between items-center text-center min-w-[220px]">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diperiksa
                      &amp; Disetujui (Quality Control):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ getInspectorQC(whQCQualifiedItems) || '........................' }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Inspector Quality
                        Control (QC)</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <!-- ================= 5. DOKUMEN PENERIMAAN GUDANG (WH/GR) ================= -->
            <template v-else-if="activePrintDocType === 'wh_gr'">
              <!-- Empty state: jika dokumen penerimaan gudang belum ada / belum diverifikasi gudang -->
              <div
                v-if="!travelerTray || filteredPrintTrays.length === 0 || whGRQualifiedItems.length === 0 || !isCurrentTravelerWHQCVerified"
                class="print-doc-wrapper flex flex-col justify-center items-center flex-grow h-full w-full py-20 px-8 text-center bg-white rounded-2xl border-2 border-dashed border-slate-200 my-auto min-h-[420px]">
                <div
                  class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-4 shadow-xs">
                  <i class="fa-solid fa-file-circle-xmark text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-800 tracking-wide">Laporan Penerimaan Gudang (WH/GR) Belum Tersedia</h3>
                <p class="text-xs text-slate-500 font-medium mt-1.5 max-w-md leading-relaxed">
                  Rakitan pada tray ini belum diverifikasi secara fisik oleh Petugas Gudang di tab Receipts. Dokumen bukti penerimaan barang jadi akan otomatis tersedia setelah verifikasi selesai.
                </p>
              </div>
              <div v-else class="print-doc-wrapper flex flex-col justify-between flex-grow h-full w-full">
                <!-- Top Section: Header + Meta + Table -->
                <div class="print-top-section space-y-3">
                  <!-- Top Title Bar with Logo & Document Number -->
                  <div class="border-b-2 border-slate-900 pb-2.5 flex items-end justify-between">
                    <div class="flex flex-col items-start space-y-1">
                      <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo" class="h-9 sm:h-11 w-auto object-contain flex-shrink-0">
                      <h2 class="text-base sm:text-lg font-black tracking-wider text-slate-900 leading-none pt-0.5">
                        Receipts — Penerimaan Gudang</h2>
                      <span class="text-[11px] sm:text-xs font-bold text-slate-600 tracking-wide">Surat Bukti Penerimaan
                        Barang Jadi Gudang (WH/GR)</span>
                    </div>
                    <div class="text-right">
                      <div class="text-[9px] sm:text-[10px] font-bold text-slate-500">No. Dokumen Penerimaan (WH/GR)
                      </div>
                      <div class="text-lg sm:text-xl font-mono font-black text-blue-900 tracking-wider">
                        {{ (whGRQualifiedItems.find(i => i.warehouse_doc_number)?.warehouse_doc_number) ||
                        getFormattedDocCode(travelerTray?.tray_code, 'wh_gr') }}
                      </div>
                    </div>
                  </div>

                  <!-- Metadata Box -->
                  <div class="print-meta-box">
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-y sm:divide-y-0 text-[10px] sm:text-[11px]">
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Tanggal Penerimaan</span>
                        <strong class="text-slate-900 font-semibold">{{ (whGRQualifiedItems.find(i =>
                          i.warehouse_receipt_at)?.warehouse_receipt_at ? formatDateTime(whGRQualifiedItems.find(i =>
                          i.warehouse_receipt_at).warehouse_receipt_at) : (travelerTray ?
                          formatDateTime(travelerTray.created_at) :
                          '-')) }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Petugas Gudang</span>
                        <strong class="text-blue-950 font-bold">{{ getOperatorFullName(whGRQualifiedItems.find(i =>
                          i.warehouse_receipt_by)?.warehouse_receipt_by_name || whGRQualifiedItems.find(i =>
                          i.warehouse_receipt_by)?.warehouse_receipt_by) || getOperatorFullName(travelerTray ?
                          travelerTray.pic_warehouse : '') || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Inspector QC
                          (Penyerah)</span>
                        <strong class="text-slate-900 font-semibold">{{ getInspectorQC(whGRQualifiedItems) ||
                          getInspectorQC(whQCQualifiedItems) || '-' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5 print-meta-cell">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Jenis Produk</span>
                        <strong class="text-slate-900 font-bold">{{ travelerTray && travelerTray.product_type ?
                          travelerTray.product_type : 'Nanggala P9' }}</strong>
                      </div>
                      <div class="p-2 sm:p-2.5">
                        <span class="block text-slate-500 text-[9px] font-bold tracking-wider">Dokumen Asal</span>
                        <strong class="font-mono text-blue-900 font-bold">{{ getFormattedDocCode(travelerTray ?
                          travelerTray.tray_code : '', 'wo') }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- WH/GR Items Table -->
                  <div class="print-items-wrapper border border-slate-800 bg-white">
                    <table class="w-full text-left text-xs border-collapse table-fixed">
                      <colgroup>
                        <col style="width: 6%;">
                        <col style="width: 16%;">
                        <col style="width: 12%;">
                        <col style="width: 12%;">
                        <col style="width: 20%;">
                        <col style="width: 34%;">
                      </colgroup>
                      <thead
                        class="bg-white text-slate-900 border-b border-slate-800 font-bold text-[10px] tracking-wider uppercase select-none">
                        <tr class="divide-x divide-slate-300 border-b border-slate-300">
                          <th rowspan="2" class="p-1.5 text-center bg-white">Set</th>
                          <th class="p-1.5 text-center bg-white">Serial Number</th>
                          <th colspan="2" class="p-1.5 text-center bg-white">Production Number</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Hasil Test QC (TF 2)</th>
                          <th rowspan="2" class="p-1.5 text-center bg-white">Verifikasi Fisik Gudang</th>
                        </tr>
                        <tr class="divide-x divide-slate-300">
                          <th class="p-1.5 text-center bg-white">P9 Part 15</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 1</th>
                          <th class="p-1.5 text-center bg-white">P9 Part 12</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-300 text-slate-900 font-mono text-[11px]">
                        <tr v-for="item in whGRQualifiedItems" :key="item.set_number"
                          class="divide-x divide-slate-200 bg-white">
                          <td class="p-1.5 text-center font-bold font-sans text-slate-700 bg-white">#{{
                            item.set_number }}</td>
                          <td class="p-1.5 text-center font-black text-slate-900 break-all text-[10px]">{{
                            item.part15_sn || '-' }}</td>
                          <td class="p-1.5 text-center text-slate-800 break-all text-[10px]">{{ item.part1_sn || '-' }}
                          </td>
                          <td class="p-1.5 text-center text-slate-800 break-all text-[10px]">{{ item.part12_sn || '-' }}
                          </td>
                          <td class="p-1.5 text-center font-sans">
                            <span :class="getDocStatusTextClass(item.qc_status || 'PENDING')"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-none whitespace-nowrap">
                              {{ formatDocStatus(item.qc_status || 'PENDING') }}
                            </span>
                          </td>
                          <td class="p-1.5 text-center font-sans">
                            <span
                              v-if="item.warehouse_receipt_status === 'ACCEPTED' || item.warehouse_receipt_status === 'ACC'"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-none text-emerald-700 whitespace-nowrap">
                              <i class="fa-solid fa-check text-[8px] mr-0.5"></i> ACC
                            </span>
                            <span v-else-if="item.warehouse_receipt_status === 'DECLINED'"
                              class="font-black text-[9.5px] uppercase tracking-wide inline-block leading-none text-rose-600 whitespace-nowrap">
                              DECLINED
                            </span>
                            <span v-else
                              class="font-black text-[9px] uppercase tracking-wide inline-block leading-none text-amber-600 whitespace-nowrap">
                              MENUNGGU VERIFIKASI FISIK GUDANG
                            </span>
                          </td>
                        </tr>
                        <tr v-if="whGRQualifiedItems.length === 0">
                          <td colspan="6" class="p-6 text-center text-slate-500 font-sans">
                            <div v-if="isAllSetsReturnedWithoutFinish" class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-boxes-packing text-red-600 text-lg"></i>
                              <span class="font-bold text-xs text-red-700 uppercase tracking-wide">Seluruh set dikembalikan ke gudang</span>
                              <span class="text-[11px] text-slate-400">Tray ini telah dikembalikan seluruhnya ke gudang sebelum proses penerimaan produk jadi.</span>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center space-y-1">
                              <i class="fa-solid fa-clock-rotate-left text-amber-500 text-lg"></i>
                              <span class="font-bold text-xs text-slate-700">Laporan Penerimaan Gudang (WH/GR) Belum
                                Tersedia</span>
                              <span class="text-[11px] text-slate-400">Rakitan pada tray ini belum diverifikasi secara
                                fisik oleh Petugas Gudang di tab Receipts.</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Signatures: Serah Terima Fisik (Diserahkan oleh QC, Diterima oleh Petugas Gudang) -->
                <div
                  class="print-footer-signatures flex justify-between pt-6 sm:pt-7 border-t-2 border-black text-xs font-sans text-center mt-auto">
                  <div class="flex flex-col justify-between items-center text-center min-w-[200px]">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diserahkan
                      Oleh (Quality Control):</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ getInspectorQC(whGRQualifiedItems) || getInspectorQC(whQCQualifiedItems) ||
                        '........................' }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Inspector Quality
                        Control (QC)</div>
                    </div>
                  </div>

                  <div class="flex flex-col justify-between items-center text-center min-w-[200px]">
                    <span class="block text-black font-bold text-[10px] sm:text-[10.5px] tracking-wider">Diterima
                      &amp; Diverifikasi Oleh:</span>
                    <div class="h-14 sm:h-16 flex items-center justify-center">
                      <!-- Area Ruang Tanda Tangan Fisik / Stempel -->
                    </div>
                    <div>
                      <div class="font-bold underline text-black text-sm tracking-wide leading-tight">
                        {{ getOperatorFullName(whGRQualifiedItems.find(i =>
                        i.warehouse_receipt_by)?.warehouse_receipt_by_name || whGRQualifiedItems.find(i =>
                        i.warehouse_receipt_by)?.warehouse_receipt_by) || getOperatorFullName(travelerTray ?
                        travelerTray.pic_warehouse : '') || '........................' }}
                      </div>
                      <div class="text-[10px] sm:text-[10.5px] text-black font-medium mt-1">Petugas Gudang
                        (Warehouse)</div>
                    </div>
                  </div>
                </div>
              </div>
            </template>

          </div>
        </div>

      </section>


@endverbatim
