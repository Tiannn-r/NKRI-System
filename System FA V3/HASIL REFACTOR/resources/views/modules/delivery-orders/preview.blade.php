@verbatim
        <!-- ================= SUB-TAB 0: PRATINJAU DOKUMEN FISIK RESMI (SURAT JALAN, PL, LABEL) ================= -->
        <div v-if="doSubTab === 'preview'" class="space-y-4 animate-fadeIn">
          <!-- Document Control & Action Toolbar -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div
              class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
              <div>
                <div class="flex items-center gap-2">
                  <span
                    class="px-2.5 py-0.5 rounded-lg bg-red-50 text-red-700 font-black text-[10px] uppercase font-mono tracking-wider border border-red-200">
                    FORMAT RESMI PT NKRI (1:1 SESUAI DOKUMEN FISIK)
                  </span>
                  <span class="text-xs text-slate-400">|</span>
                  <span class="text-xs font-semibold text-slate-600">Surat Jalan, Packing List, Label Peti &amp; Label
                    Dus</span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1 flex items-center gap-2">
                  <i class="fa-solid fa-file-contract text-red-600"></i>
                  <span>Pratinjau Dokumen Fisik Distribusi</span>
                </h3>
              </div>

              <!-- Action Buttons -->
              <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="loadDOPreviewData(previewDOId)" :disabled="isLoadingPreviewDO"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5 min-h-[38px]">
                  <i class="fa-solid fa-rotate-right" :class="{'fa-spin': isLoadingPreviewDO}"></i>
                  <span>Segarkan</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(previewDOData?.id, previewDocType)"
                  :disabled="!previewDOData"
                  class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold text-xs px-4 py-2 rounded-xl shadow-md shadow-red-600/25 flex items-center gap-2 transition cursor-pointer min-h-[38px]">
                  <i class="fa-solid fa-print"></i>
                  <span>Cetak Dokumen Ini</span>
                </button>
                <a v-if="previewDOData?.do_number"
                  :href="'/tracking_pl.html?do=' + encodeURIComponent(previewDOData.do_number) + (currentRealtimePrintDate ? '&printed_at=' + encodeURIComponent(currentRealtimePrintDate) : '')"
                  target="_blank"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]"
                  title="Buka halaman pelacakan publik resmi (Tracking PL) di tab browser baru">
                  <i class="fa-solid fa-magnifying-glass-location"></i>
                  <span>Buka Tracking</span>
                </a>
              </div>
            </div>

            <!-- Notice Banner jika Cloudinary belum disetting -->
            <div v-if="!doSettings.cloudinary?.configured"
              class="bg-amber-50 border border-amber-300/80 rounded-xl p-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
              <div class="flex items-center gap-2.5 text-amber-900">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base shrink-0"></i>
                <div>
                  <span class="font-bold">Cloudinary Belum Dikonfigurasi!</span>
                  <span class="text-amber-800 ml-1">QR Code Surat Jalan saat ini masih menggunakan link jaringan lokal kantor (Wi-Fi). Kredensial Cloudinary dikelola oleh Administrator di Portal Admin CMS.</span>
                </div>
              </div>
              <a href="/admin" target="_blank"
                class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer self-start sm:self-auto shrink-0 flex items-center gap-1.5 shadow-2xs">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Buka Portal Admin CMS</span>
              </a>
            </div>

            <!-- Selector Toolbar: Pilih DO, Jenis Dokumen, & Pilih Peti -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
              <!-- Select DO -->
              <div class="md:col-span-4 space-y-1">
                <label class="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                  <i class="fa-solid fa-folder-open text-slate-400"></i>
                  <span>Pilih Dokumen DO Terbit:</span>
                </label>
                <div class="relative">
                  <select v-model="previewDOId" @change="loadDOPreviewData(previewDOId)"
                    class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-9 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100 transition-all duration-200 min-h-[38px] cursor-pointer shadow-2xs">
                    <option v-for="d in doList" :key="d.id" :value="d.id">
                      #{{ d.do_number }} - {{ d.customer_name }} ({{ d.total_quantity }} Unit / {{ d.total_crates }} Peti)
                    </option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                  </div>
                </div>
              </div>

              <!-- Document Type Tabs -->
              <div class="md:col-span-5 space-y-1">
                <label class="text-[11px] font-bold text-slate-700 flex items-center gap-1">
                  <i class="fa-solid fa-layer-group text-slate-400"></i>
                  <span>Pilih Jenis Dokumen:</span>
                </label>
                <div class="grid gap-1 p-1 bg-slate-100 rounded-xl border border-slate-200"
                  :class="previewDOData?.package_type === 'unit' ? 'grid-cols-2' : (previewDOData?.package_type === 'box' ? 'grid-cols-4' : 'grid-cols-5')">
                  <button type="button" @click="previewDocType = 'surat_jalan'"
                    :class="previewDocType === 'surat_jalan' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                    class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                    Surat Jalan
                  </button>
                  <template v-if="previewDOData?.package_type !== 'unit'">
                    <button type="button" @click="previewDocType = 'packing_list'"
                      :class="previewDocType === 'packing_list' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                      class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                      Packing List
                    </button>
                    <button v-if="!previewDOData?.package_type || previewDOData?.package_type === 'crate'" type="button"
                      @click="previewDocType = 'label_peti'"
                      :class="previewDocType === 'label_peti' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                      class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                      Label Peti
                    </button>
                    <button type="button" @click="previewDocType = 'label_dus'"
                      :class="previewDocType === 'label_dus' ? 'bg-white text-red-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                      class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                      Label Dus
                    </button>
                    <button type="button" @click="previewDocType = 'label_sn'"
                      :class="previewDocType === 'label_sn' ? 'bg-white text-blue-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                      class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                      Label SN
                    </button>
                  </template>
                  <template v-else>
                    <button type="button" @click="previewDocType = 'label_sn'"
                      :class="previewDocType === 'label_sn' ? 'bg-white text-blue-700 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900 font-bold'"
                      class="py-1.5 px-2 rounded-lg text-[11px] text-center transition cursor-pointer truncate">
                      <i class="fa-solid fa-barcode mr-1"></i> Label SN
                    </button>
                  </template>
                </div>
              </div>

              <!-- Packing List Sheet Selector (Active when viewing Packing List) -->
              <div class="md:col-span-3 space-y-1"
                v-if="previewDocType === 'packing_list' && previewPackingListSheets.length > 0">
                <label class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                  <span>Lembar Packing List:</span>
                  <span class="text-[10px] text-red-600 font-mono font-bold">{{ previewPackingListSheetIndex + 1 }} dari
                    {{ previewPackingListSheets.length }}</span>
                </label>
                <div class="flex items-center gap-1.5">
                  <button type="button"
                    @click="previewPackingListSheetIndex = Math.max(0, previewPackingListSheetIndex - 1)"
                    :disabled="previewPackingListSheetIndex === 0"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-slate-700 text-xs font-bold transition cursor-pointer min-h-[38px]">
                    <i class="fa-solid fa-chevron-left"></i>
                  </button>
                  <div class="relative flex-1">
                    <select v-model="previewPackingListSheetIndex"
                      class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-1.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100 transition min-h-[38px] cursor-pointer shadow-2xs">
                      <option v-for="(sheet, sIdx) in previewPackingListSheets" :key="sIdx" :value="sIdx">
                        Lembar {{ sIdx + 1 }} (Peti {{ sheet.crate1?.crate_id || sheet.crate1?.crate_number }} {{
                        sheet.crate2 ? '& ' + (sheet.crate2?.crate_id || sheet.crate2?.crate_number) : '' }})
                      </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                      <i class="fa-solid fa-chevron-down text-[11px]"></i>
                    </div>
                  </div>
                  <button type="button"
                    @click="previewPackingListSheetIndex = Math.min(previewPackingListSheets.length - 1, previewPackingListSheetIndex + 1)"
                    :disabled="previewPackingListSheetIndex >= previewPackingListSheets.length - 1"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-slate-700 text-xs font-bold transition cursor-pointer min-h-[38px]">
                    <i class="fa-solid fa-chevron-right"></i>
                  </button>
                </div>
              </div>

              <!-- Crate Selector (Active when viewing Label Peti or Label Dus) -->
              <div class="md:col-span-3 space-y-1"
                v-else-if="previewDocType !== 'surat_jalan' && previewDocType !== 'label_sn' && previewDOData?.package_type !== 'unit' && previewDOData?.crates?.length > 0">
                <label class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                  <span>Pilih Peti:</span>
                  <span class="text-[10px] text-red-600 font-mono font-bold">{{ previewCrateIndex + 1 }} dari {{
                    previewDOData.crates.length }}</span>
                </label>
                <div class="flex items-center gap-1.5">
                  <button type="button" @click="previewCrateIndex = Math.max(0, previewCrateIndex - 1)"
                    :disabled="previewCrateIndex === 0"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-slate-700 text-xs font-bold transition cursor-pointer min-h-[38px]">
                    <i class="fa-solid fa-chevron-left"></i>
                  </button>
                  <div class="relative flex-1">
                    <select v-model="previewCrateIndex"
                      class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-1.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100 transition min-h-[38px] cursor-pointer shadow-2xs">
                      <option v-for="(c, cIdx) in previewDOData.crates" :key="c.id || cIdx" :value="cIdx">
                        Peti {{ cIdx + 1 }} ({{ c.crate_number }})
                      </option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                      <i class="fa-solid fa-chevron-down text-[11px]"></i>
                    </div>
                  </div>
                  <button type="button"
                    @click="previewCrateIndex = Math.min(previewDOData.crates.length - 1, previewCrateIndex + 1)"
                    :disabled="previewCrateIndex >= previewDOData.crates.length - 1"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-slate-700 text-xs font-bold transition cursor-pointer min-h-[38px]">
                    <i class="fa-solid fa-chevron-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Document Paper Container (Live Visual Preview) -->
          <div v-if="isLoadingPreviewDO"
            class="bg-white border border-slate-200 rounded-2xl p-16 text-center shadow-xs">
            <i class="fa-solid fa-spinner fa-spin text-3xl text-red-600 mb-3"></i>
            <p class="text-xs font-bold text-slate-600">Memuat lembar dokumen resmi...</p>
          </div>

          <div v-else-if="!previewDOData"
            class="bg-white border border-slate-200 rounded-2xl p-16 text-center shadow-xs">
            <i class="fa-solid fa-file-circle-question text-4xl text-slate-300 mb-3"></i>
            <h4 class="text-sm font-black text-slate-800">Belum Ada Dokumen DO Dipilih</h4>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Silakan pilih nomor Surat Jalan di toolbar atas atau
              buat Surat Jalan baru.</p>
            <button type="button" @click="doSubTab = 'form'"
              class="mt-4 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded-xl inline-flex items-center gap-1.5 cursor-pointer shadow-md shadow-red-600/25">
              <i class="fa-solid fa-plus"></i>
              <span>Buat Surat Jalan Sekarang</span>
            </button>
          </div>

          <div v-else
            class="bg-white p-4 sm:p-8 rounded-2xl border border-slate-200 overflow-x-auto do-preview-scope"
            style="font-family: 'Roboto', Arial, sans-serif;">

            <!-- ================================================================= -->
            <!-- 1. DOKUMEN SURAT JALAN (1:1 PERSIS DENGAN CANVA PDF 1) -->
            <!-- ================================================================= -->
            <!-- ================================================================= -->
            <!-- 1. DOKUMEN SURAT JALAN (A5 LANDSCAPE SESUAI CONTOH FOTO RESMI BARU) -->
            <!-- ================================================================= -->
            <div v-if="previewDocType === 'surat_jalan'">
              <!-- LEMBAR FISIK 8.5 x 5.5 INCH LANDSCAPE (MARGIN WORD: TOP 1cm, RIGHT 2.33cm, BOTTOM 0.5cm, LEFT 1.66cm) -->
              <div
                class="bg-white mx-auto shadow-2xl border border-slate-300 text-black overflow-hidden select-text"
                style="width: 8.5in; height: 5.5in; min-width: 8.5in; max-width: 8.5in; min-height: 5.5in; max-height: 5.5in; padding: 1.0cm 2.33cm 0.5cm 1.66cm; box-sizing: border-box; font-family: 'Roboto', Arial, sans-serif; position: relative;">

                <!-- BINGKAI LUAR HITAM (KOTAK PERSEGI SESUAI CONTOH FOTO USER) -->
                <div
                  style="border: 1.5px solid #000000; width: 100%; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; position: relative; background: #ffffff; overflow: hidden;">

                  <!-- WATERMARK LOGO DI TENGAH DOKUMEN (TRANSPARANSI 65% = OPACITY 0.35) -->
                  <div class="pointer-events-none select-none absolute inset-0 flex items-center justify-center"
                    style="z-index: 0;">
                    <img :src="nkriEmblemBase64 || '/emblem.png'" alt="" class="do-watermark-img"
                      style="width: 195px; opacity: 0.35 !important; object-fit: contain;" />
                  </div>

                  <!-- KONTEN ATAS: HEADER, BANNER, PENERIMA, TABEL -->
                  <div style="position: relative; z-index: 1;">

                    <!-- 1. HEADER (Kiri: PT & Alamat, Kanan: No Surat Jalan & Tanggal) -->
                    <div
                      style="padding: 7px 14px 6px 14px; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1.5px solid #000000;">
                      <!-- Kiri -->
                      <div>
                        <div style="font-weight: 800; font-size: 13px; color: #000000; letter-spacing: -0.2px;">
                          {{ doSettings.company_name || 'PT Nanggala Kencana Rekatama Indonesia' }}
                        </div>
                        <div style="font-size: 10px; color: #111827; line-height: 1.35; margin-top: 2px;">
                          Jl. Mekar Raya Kav. 12, Komplek Industri Mekar Mulya,<br>Gedebage, Bandung
                        </div>
                      </div>
                      <!-- Kanan -->
                      <div style="text-align: right;">
                        <table
                          style="font-size: 11px; line-height: 1.45; border-collapse: collapse; margin-left: auto;">
                          <tbody>
                            <tr>
                              <td style="font-weight: 700; white-space: nowrap; padding-right: 4px; text-align: left;">
                                No. Surat Jalan</td>
                              <td style="padding: 0 4px;">:</td>
                              <td style="font-weight: 400; white-space: nowrap; text-align: left;">{{
                                previewDOData.do_number }}</td>
                            </tr>
                            <tr>
                              <td style="font-weight: 700; white-space: nowrap; padding-right: 4px; text-align: left;">
                                Tanggal</td>
                              <td style="padding: 0 4px;">:</td>
                              <td style="font-weight: 400; white-space: nowrap; text-align: left;">{{
                                previewDOData.do_date }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <!-- 2. BANNER SURAT JALAN -->
                    <div
                      style="text-align: center; font-weight: 800; font-size: 13px; letter-spacing: 0.8px; padding: 3px 0; border-bottom: 1.5px solid #000000; text-transform: uppercase;">
                      SURAT JALAN
                    </div>

                    <!-- 3. DATA PENERIMA & PROGRAM -->
                    <div
                      style="padding: 6px 20px 4px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: flex-start;">
                      <!-- Kiri: Penerima, Alamat, Nomor -->
                      <table style="font-size: 10.5px; line-height: 1.35; border-collapse: collapse;">
                        <tbody>
                          <tr>
                            <td style="font-weight: 700; vertical-align: top; width: 64px;">Penerima</td>
                            <td style="vertical-align: top; width: 8px;">:</td>
                            <td style="font-weight: 600; vertical-align: top;">{{ previewDOData.customer_name }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; vertical-align: top; padding-top: 2px;">Alamat</td>
                            <td style="vertical-align: top; padding-top: 2px;">:</td>
                            <td
                              style="vertical-align: top; padding-top: 2px; white-space: pre-line; max-width: 320px; line-height: 1.3;">
                              {{ previewDOData.customer_address }}
                            </td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; vertical-align: top; padding-top: 3px;">Nomor</td>
                            <td style="vertical-align: top; padding-top: 3px;">:</td>
                            <td style="vertical-align: top; padding-top: 3px;">{{ previewDOData.contract_number }}</td>
                          </tr>
                        </tbody>
                      </table>

                      <!-- Kanan: Program (Mulai tepat di tengah halaman / 50%) -->
                      <div style="display: flex; justify-content: flex-start;">
                        <table style="font-size: 10.5px; line-height: 1.35; border-collapse: collapse;">
                          <tbody>
                            <tr>
                              <td style="font-weight: 700; vertical-align: top; width: 60px;">Program</td>
                              <td style="vertical-align: top; width: 8px;">:</td>
                              <td style="vertical-align: top;">{{ previewDOData.program_name || '-' }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <!-- 4. TABEL PRODUK -->
                    <div style="margin: 4px 20px 0 20px;">
                      <table
                        style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1.5px solid #000000; font-family: 'Roboto', Arial, sans-serif; font-size: 11px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid #000000; height: 25px;">
                            <th
                              style="border-right: 1.5px solid #000000; width: 6%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              No</th>
                            <th
                              :style="{ width: previewDOData.package_type === 'unit' ? '34%' : '52%', borderRight: '1.5px solid #000000' }"
                              style="text-align: center; font-weight: 700; padding: 2px 4px;">
                              Produk</th>
                            <th
                              :style="{ width: previewDOData.package_type === 'unit' ? '18%' : '21%', borderRight: '1.5px solid #000000' }"
                              style="text-align: center; font-weight: 700; padding: 2px 4px;">
                              Jumlah</th>
                            <th
                              :style="{ width: previewDOData.package_type === 'unit' ? '18%' : '21%', borderRight: previewDOData.package_type === 'unit' ? '1.5px solid #000000' : 'none' }"
                              style="text-align: center; font-weight: 700; padding: 2px 4px;">
                              Satuan</th>
                            <th v-if="previewDOData.package_type === 'unit'" style="width: 24%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              Serial Number
                            </th>
                          </tr>
                        </thead>
                        <tbody>
                          <!-- KEMASAN SATUAN (package_type === 'unit'): Baris kebawah mengurut sebanyak produk -->
                          <template v-if="previewDOData.package_type === 'unit'">
                            <tr v-for="(sn, snIdx) in getDOSerialNumbers(previewDOData)"
                              :key="'preview_unit_sn_' + snIdx"
                              :style="{ borderBottom: snIdx === getDOSerialNumbers(previewDOData).length - 1 ? 'none' : '1px solid #000000', height: '22px' }">
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">{{
                                snIdx + 1 }}</td>
                              <td
                                style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px; font-weight: 600;">
                                {{ previewDOData.product_name || 'Nanggala P9' }}</td>
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">1</td>
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">UNIT
                              </td>
                              <td
                                style="text-align: center; padding: 2px 4px; font-family: 'Roboto', Arial, sans-serif;">
                                {{ sn }}</td>
                            </tr>
                          </template>

                          <!-- BUKAN SATUAN (PETI / DUS): Ringkasan dengan QR Packing List -->
                          <template v-else>
                            <tr style="min-height: 26px;">
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">1</td>
                              <td
                                style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px; font-weight: 600;">
                                <div>{{ previewDOData.product_name || 'Nanggala P9' }}</div>
                              </td>
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">
                                <div style="font-weight: 600;">{{ getDOTotalQuantity(previewDOData) }}</div>
                                <div v-if="previewDOData.package_type === 'crate'"
                                  style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                                  ({{ getDOCrateCount(previewDOData) }} Peti / {{ getDOBoxCount(previewDOData) }} Dus)
                                </div>
                                <div v-else-if="previewDOData.package_type === 'box'"
                                  style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                                  ({{ getDOBoxCount(previewDOData) }} Dus)
                                </div>
                              </td>
                              <td style="text-align: center; padding: 2px 4px;">UNIT</td>
                            </tr>
                          </template>
                        </tbody>
                      </table>
                    </div>

                  </div>

                  <!-- 5. TANDA TANGAN (Kiri: Dikeluarkan Oleh, Kanan: Diterima Oleh - Rapih, Sejajar, Simetris) -->
                  <div
                    style="position: relative; z-index: 1; padding: 10px 52px 14px 52px; display: flex; justify-content: space-between; align-items: flex-start; font-size: 11px;">
                    <!-- Kiri: Dikeluarkan Oleh -->
                    <div style="width: 190px; text-align: center;">
                      <div style="font-weight: 700; font-size: 11px; letter-spacing: 0.2px;">Dikeluarkan Oleh</div>
                      <div style="height: 42px;"></div>
                      <div style="font-weight: 700; font-size: 11px; line-height: 1.25;">({{
                        previewDOData.created_by_name || previewDOData.created_by || doSettings.default_issuer_name ||
                        'Rezka Rachdana' }})</div>
                      <div style="font-weight: 400; font-size: 10px; color: #1f2937; margin-top: 1.5px;">
                        {{ previewDOData.created_by_title || doSettings.default_issuer_title || 'Staf PPIC' }}
                      </div>
                    </div>

                    <!-- Kanan: Diterima Oleh -->
                    <div style="width: 190px; text-align: center;">
                      <div style="font-weight: 700; font-size: 11px; letter-spacing: 0.2px;">Diterima Oleh</div>
                      <div style="height: 42px;"></div>
                      <div style="font-weight: 700; font-size: 11px; line-height: 1.25;">(
                        .............................. )</div>
                      <div style="font-size: 10px; margin-top: 1.5px; visibility: hidden;">&nbsp;</div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

            <!-- ================================================================= -->
            <!-- 2. DOKUMEN PACKING LIST (LAYOUT PERSIS DENGAN DO, KONTEN PACKING LIST) -->
            <!-- ================================================================= -->
            <div v-else-if="previewDocType === 'packing_list'">
              <!-- LEMBAR A4 FISIK PACKING LIST -->
              <div
                class="bg-white mx-auto shadow-2xl border border-slate-300 w-[210mm] h-[297mm] min-h-[297mm] max-h-[297mm] p-0 text-black flex flex-col justify-between overflow-hidden"
                style="font-family: 'Roboto', Arial, sans-serif; box-sizing: border-box; position: relative;">

                <!-- TOP SECTION: HEADER, BANNER, METADATA, & 4 TABLES -->
                <div>
                  <!-- 1. Header Atas dengan Background Polygon Pink Sesuai Canva (Full Width Mentok Kanan Kiri) -->
                  <div
                    style="position: relative; width: 100%; height: 124px; box-sizing: border-box; overflow: hidden; margin-bottom: 18px;">
                    <!-- SVG Background Polygon Pink Lembut (#F7EAEB) -->
                    <svg
                      style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: block; z-index: 0;"
                      viewBox="0 0 794 124" preserveAspectRatio="none">
                      <polygon points="0,0 794,0 794,68 395,68 360,124 0,124" fill="#F7EAEB" />
                    </svg>

                    <!-- Foreground Elements -->
                    <div
                      style="position: relative; z-index: 1; height: 100%; padding: 0 45px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
                      <!-- Baris Atas (Logo NKRI Kiri & Nama PT Kanan, Diturunkan Sesuai Permintaan User) -->
                      <div
                        style="height: 68px; padding-top: 14px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
                        <div class="flex items-center" style="padding-top: 10px;">
                          <img :src="nkriLogoBase64 || nkriLabelLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                            alt="NKRI" style="height: 38px; width: auto;" class="object-contain">
                        </div>
                        <div class="text-right">
                          <span style="font-weight: 700; font-size: 15px; color: #000000; letter-spacing: -0.2px;">
                            {{ doSettings.company_name || 'PT Nanggala Kencana Rekatama Indonesia' }}
                          </span>
                        </div>
                      </div>

                      <!-- Baris Bawah: Area Cutout Putih -->
                      <div style="height: 56px;"></div>
                    </div>
                  </div>

                  <!-- Content Section with 45px Side Margins -->
                  <div style="padding: 0 45px;">
                    <!-- 2. Metadata Grid Sesuai Dokumen Packing List (Font Digedein Sesuai Permintaan User) -->
                    <div style="font-size: 13px; color: #000000; line-height: 1.45; margin-bottom: 12px;">
                      <table style="border-collapse: collapse; border: none; width: 100%;">
                        <tbody>
                          <tr>
                            <td
                              style="font-weight: 700; padding: 2.5px 0; width: 130px; vertical-align: top; font-size: 13px;">
                              No. Packing List</td>
                            <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                              getSheetPLNumber(previewCurrentPackingListSheet, previewDOData) }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">
                              Tanggal
                            </td>
                            <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                              formatDocDate(previewDOData.do_date) }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">{{
                              previewCurrentPackingListSheet.containerLabel || (previewDOData.package_type === 'box' ? 'ID Dus' : 'ID Peti') }}
                            </td>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;"
                              class="font-sans text-slate-900">
                              : {{ previewCurrentPackingListSheet.containerId || [previewCurrentPackingListSheet.crate1?.crate_id ||
                              previewCurrentPackingListSheet.crate1?.crate_number,
                              previewCurrentPackingListSheet.crate2?.crate_id ||
                              previewCurrentPackingListSheet.crate2?.crate_number].filter(Boolean).join(' & ') ||
                              defaultCrateIdForNow }}
                            </td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nama
                              Produk</td>
                            <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                              previewDOData.product_name || 'Nanggala P9' }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nomor
                            </td>
                            <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                              previewDOData.contract_number || doSettings?.default_contract_number || 'TRAK/2509/APBN/XI/2025/KEMHAN' }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">
                              Program
                            </td>
                            <td
                              style="font-weight: 400; padding: 2.5px 0; vertical-align: top; max-width: 440px; line-height: 1.4; font-size: 13px;">
                              : {{ previewDOData.program_name || doSettings?.default_program_name || 'PENGADAAN ALPALHANKAM MATSUSSUS' }}
                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <!-- Penerima Block -->
                      <div style="margin-top: 10px;">
                        <div style="font-weight: 700; font-size: 13px;">Penerima &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                          &nbsp;
                          :</div>
                        <div style="margin-top: 2px; line-height: 1.4; font-size: 13px;">
                          <div style="font-weight: 400;">{{ previewDOData.customer_name }}</div>
                          <div style="white-space: pre-line;">{{ previewDOData.customer_address }}</div>
                        </div>
                      </div>
                    </div>

                    <!-- 3. TABEL ISI SERIAL NUMBER (DINAMIS MENYESUAIKAN JUMLAH DUS / PETI) -->
                    <div class="grid grid-cols-2 gap-x-5 gap-y-3.5 my-2"
                      style="font-family: 'Roboto', Arial, sans-serif;">
                      <div v-for="(tbl, tIdx) in (previewCurrentPackingListSheet.tables || [])" :key="'preview_pl_tbl_' + tIdx">
                        <div class="mb-1 font-bold text-slate-900" style="font-size: 12.5px;">
                          {{ tbl.title }}
                        </div>
                        <table
                          style="border-collapse: collapse; border: 1.5px solid #000000; width: 100%; font-size: 11.5px; font-family: 'Roboto', Arial, sans-serif;">
                          <thead>
                            <tr style="border-bottom: 1.5px solid #000000; font-weight: 700;">
                              <th
                                style="border-right: 1.5px solid #000000; padding: 3px 6px; text-align: center; width: 42px; font-size: 12px;">
                                No</th>
                              <th style="padding: 3px 8px; text-align: left; font-size: 12px;">Serial Number</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="s in 10" :key="'preview_pl_row_' + tIdx + '_' + s"
                              style="border-bottom: 1px solid #000000; height: 18.5px;">
                              <td
                                style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">
                                {{ s }}</td>
                              <td
                                style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">
                                {{ (tbl.items && tbl.items[s - 1]) ? (typeof tbl.items[s - 1] === 'string' ? tbl.items[s - 1] : (tbl.items[s - 1].serial_number || '')) : '' }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BOTTOM SECTION: SHEET INDICATOR & PINK FOOTER (MENEMPEL MENTOK KE BAWAH & MENTOK KANAN KIRI) -->
                <div style="margin-top: auto; width: 100%;" class="shrink-0">
                  <div style="padding: 0 45px;"
                    class="flex items-center justify-between text-[11px] text-slate-500 font-bold mb-1.5 font-sans">
                    <span>Tanggal Cetak: {{ currentRealtimePrintDate }}</span>
                    <span>Lembar {{ previewCurrentPackingListSheet.sheetIndex }} dari {{
                      previewCurrentPackingListSheet.totalSheets }}</span>
                  </div>
                  <!-- Pink Footer Full Width (Menempel Mentok ke Bawah dan Mentok Kanan Kiri) -->
                  <div class="do-pink-footer"
                    style="background-color: #F7EAEB; width: 100%; padding: 14px 45px 36px 45px; box-sizing: border-box; margin: 0;">
                    <div class="flex justify-between items-start" style="font-size: 11px;">
                      <!-- Head Office -->
                      <div style="max-width: 240px;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Head
                          Office :</div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                          Jalan Mendawai I No. 11 Kramat Pela<br>Jakarta Selatan, 12130
                        </div>
                      </div>

                      <!-- Factory -->
                      <div style="max-width: 240px;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Factory :
                        </div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                          Jalan Gempol No. 9, Mekar Mulya<br>Bandung, 40292
                        </div>
                      </div>

                      <!-- Kontak -->
                      <div style="text-align: left;">
                        <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Kontak :
                        </div>
                        <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                          {{ doSettings.contact_email || 'info@nanggala.co.id' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- ================================================================= -->
            <!-- 3. LABEL PETI (1:1 DENGAN PDF 3) -->
            <!-- ================================================================= -->
            <div v-else-if="previewDocType === 'label_peti'" class="flex justify-center py-4">
              <!-- Kotak Label Bingkai Hitam Tebal Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
              <div class="bg-white text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden;">
                <div class="w-full h-full bg-white border-[2.5px] border-black text-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
                <!-- Header Label: Logo Kiri, Nama PT Kanan, Judul PACKING LIST -->
                <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center shrink-0">
                      <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;"
                        class="object-contain">
                    </div>
                    <div
                      class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
                      PT NANGGALA KENCANA REKATAMA INDONESIA
                    </div>
                  </div>
                  <div class="text-center mt-2">
                    <div class="font-black text-[25px] text-black tracking-[3px] uppercase leading-none">
                      PACKING LIST
                    </div>
                  </div>
                </div>

                <!-- Metadata List Sesuai PDF 3 (Garis Menyatu Penuh ke Pinggir) -->
                <div class="px-3 py-1.5 border-b-[2.5px] border-black text-xs space-y-1 leading-snug shrink-0">
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PROGRAM</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.program_name }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">NOMOR</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.contract_number }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PRODUK</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.product_name || 'Nanggala P9' }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">ID. PETI</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewCurrentCrate.crate_id ||
                      previewCurrentCrate.crate_number || defaultCrateIdForNow }}</span>
                  </div>

                  <div class="grid grid-cols-12 gap-1 pt-0.5">
                    <span class="col-span-3 font-bold">PENERIMA</span>
                    <span class="col-span-1 font-bold">:</span>
                    <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                      <div>{{ previewDOData.customer_name }}</div>
                      <div>BADAN LOGISTIK PERTAHANAN</div>
                      <div>{{ previewDOData.customer_address }}</div>
                    </div>
                  </div>
                </div>

                <!-- Serial Number 2 Kolom: Garis Vertikal Tengah Menyambung Penuh ke Bawah (Tanpa Terputus) -->
                <div class="flex-1 min-h-0 border-b-[2.5px] border-black text-xs flex">
                  <!-- Pak 1 (Kiri 50%) -->
                  <div class="w-1/2 border-r-[2.5px] border-black flex flex-col justify-between">
                    <!-- Baris Judul Pak 1: Garis menyatu penuh sampe ujung -->
                    <div
                      class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                      PAK 1 DARI 2
                    </div>
                    <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                    <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                      <span class="w-6">NO.</span>
                      <span>SERIAL NUMBER</span>
                    </div>
                    <!-- Daftar 10 Serial Number -->
                    <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                      <div v-for="s in 10" :key="'lp_p1_'+s" class="flex items-center leading-tight">
                        <span class="w-6 font-bold">{{ s }}.</span>
                        <span class="font-black">{{ previewCurrentCrate.boxes?.[0]?.items?.[s - 1]?.serial_number || ''
                          }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Pak 2 (Kanan 50%) -->
                  <div class="w-1/2 flex flex-col justify-between">
                    <!-- Baris Judul Pak 2: Garis menyatu penuh sampe ujung -->
                    <div
                      class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                      PAK 2 DARI 2
                    </div>
                    <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                    <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                      <span class="w-6">NO.</span>
                      <span>SERIAL NUMBER</span>
                    </div>
                    <!-- Daftar 10 Serial Number -->
                    <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                      <div v-for="s in 10" :key="'lp_p2_'+s" class="flex items-center leading-tight">
                        <span class="w-6 font-bold">{{ s }}.</span>
                        <span class="font-black">{{ previewCurrentCrate.boxes?.[1]?.items?.[s - 1]?.serial_number || ''
                          }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Bottom Row: Garis Kotak Menyatu Utuh & Garis Vertikal Menyambung Lurus 50/50 Langsung Menempel -->
                <div class="flex text-black text-xs shrink-0">
                  <!-- Kolom Kiri 50%: TANGGAL & JUMLAH -->
                  <div class="w-1/2 border-r-[2.5px] border-black flex flex-col justify-between">
                    <div class="border-b-[2.5px] border-black px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">TANGGAL</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">{{ formatNumericDate(previewDOData.do_date)
                        }}</span>
                    </div>
                    <div class="px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">JUMLAH</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">{{ previewCurrentCrate.total_quantity || 20 }}
                        UNIT</span>
                    </div>
                  </div>

                  <!-- Kolom Kanan 50%: NO. PETI & Angka Disatukan Utuh Tanpa Garis Pemisah -->
                  <div class="w-1/2 px-3 py-1.5 flex flex-col items-center justify-center">
                    <div class="font-black uppercase tracking-wider text-[11px] mb-1">
                      NO. PETI :
                    </div>
                    <div class="text-xl font-black tracking-wider leading-none">
                      {{ previewCrateIndex + 1 }} DARI {{ previewDOData.crates?.length || previewDOData.total_crates ||
                      20 }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

            <!-- ================================================================= -->
            <!-- 4. LABEL DUS (1:1 DENGAN PDF 4) -->
            <!-- ================================================================= -->
            <div v-else-if="previewDocType === 'label_dus'"
              class="flex flex-col sm:flex-row items-center justify-center gap-6 py-4">
              <!-- DUS 1 (PAK 1 DARI 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
              <div class="bg-white text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden;">
                <div class="w-full h-full bg-white border-[2.5px] border-black text-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
                <!-- Header Label Sesuai Permintaan User -->
                <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center shrink-0">
                      <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;"
                        class="object-contain">
                    </div>
                    <div
                      class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
                      PT NANGGALA KENCANA REKATAMA INDONESIA
                    </div>
                  </div>
                  <div class="text-center mt-2">
                    <div class="font-black text-[25px] text-black tracking-[3px] uppercase leading-none">
                      PACKING LIST
                    </div>
                  </div>
                </div>
                <!-- Metadata List Sesuai PDF 4 (Garis Menyatu Penuh ke Pinggir) -->
                <div class="px-3 py-1.5 border-b-[2.5px] border-black text-xs space-y-1 leading-snug shrink-0">
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PROGRAM</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.program_name }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">NOMOR</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.contract_number }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PRODUK</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.product_name || 'Nanggala P9' }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">{{ previewDOData.package_type === 'box' ? 'ID. DUS' : 'ID. PETI' }}</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.package_type === 'box' ? (previewCurrentCrate.box_id || previewCurrentCrate.boxes?.[0]?.box_id || previewCurrentCrate.crate_id || '') : ((previewCurrentCrate.crate_id || previewCurrentCrate.crate_number || defaultCrateIdForNow) + '-01') }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1 pt-0.5">
                    <span class="col-span-3 font-bold">PENERIMA</span>
                    <span class="col-span-1 font-bold">:</span>
                    <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                      <div>{{ previewDOData.customer_name }}</div>
                      <div>BADAN LOGISTIK PERTAHANAN</div>
                      <div>{{ previewDOData.customer_address }}</div>
                    </div>
                  </div>
                </div>
                <!-- Serial Number 1 Kolom Sesuai Dus 1 (Garis Menyatu Penuh Langsung ke Baris Bawah) -->
                <div class="flex-1 min-h-0 border-b-[2.5px] border-black flex flex-col justify-between text-xs">
                  <!-- Baris Judul Pak 1: HANYA MUNCUL JIKA PER PETI (Sesuai File Word: PAK 1 DARI 2). JIKA PER DUS: HILANGKAN PAKNYA! -->
                  <div v-if="previewDOData.package_type !== 'box'"
                    class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                    PAK 1 DARI 2
                  </div>
                  <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                  <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                    <span class="w-6">NO.</span>
                    <span>SERIAL NUMBER</span>
                  </div>
                  <!-- Daftar 10 Serial Number -->
                  <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                    <div v-for="s in 10" :key="'dus1_'+s" class="flex items-center leading-tight">
                      <span class="w-6 font-bold">{{ s }}.</span>
                      <span class="font-black">{{ (previewCurrentCrate.boxes?.[0]?.items?.[s - 1]?.serial_number) || (previewCurrentCrate.items?.[s - 1]?.serial_number) || (typeof previewCurrentCrate.items?.[s - 1] === 'string' ? previewCurrentCrate.items[s - 1] : '') || '' }}</span>
                    </div>
                  </div>
                </div>
                <!-- Bottom Row: Garis Kotak Menyatu Utuh & Garis Vertikal Menyambung Lurus 50/50 Langsung Menempel -->
                <div class="flex text-black text-xs shrink-0">
                  <!-- Kolom Kiri 50%: TANGGAL & JUMLAH -->
                  <div class="w-1/2 border-r-[2.5px] border-black flex flex-col justify-between">
                    <div class="border-b-[2.5px] border-black px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">TANGGAL</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">{{ formatNumericDate(previewDOData.do_date)
                        }}</span>
                    </div>
                    <div class="px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">JUMLAH</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">10 UNIT</span>
                    </div>
                  </div>

                  <!-- Kolom Kanan 50%: NO. DUS (jika Per Dus) atau NO. PETI (jika Per Peti) -->
                  <div class="w-1/2 px-3 py-1.5 flex flex-col items-center justify-center">
                    <div class="font-black uppercase tracking-wider text-[11px] mb-1">
                      {{ previewDOData.package_type === 'box' ? 'NO. DUS :' : 'NO. PETI :' }}
                    </div>
                    <div class="text-xl font-black tracking-wider leading-none">
                      {{ previewCrateIndex + 1 }} DARI {{ previewDOData.crates?.length || (previewDOData.package_type === 'box' ? getDOBoxCount(previewDOData) : (previewDOData.total_crates || 20)) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

              <!-- DUS 2 (PAK 2 DARI 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
              <div v-if="previewDOData.package_type !== 'box' && previewCurrentCrate.boxes && previewCurrentCrate.boxes.length > 1"
                class="bg-white text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden;">
                <div class="w-full h-full bg-white border-[2.5px] border-black text-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
                <!-- Header Label Sesuai Permintaan User -->
                <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center shrink-0">
                      <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;"
                        class="object-contain">
                    </div>
                    <div
                      class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
                      PT NANGGALA KENCANA REKATAMA INDONESIA
                    </div>
                  </div>
                  <div class="text-center mt-2">
                    <div class="font-black text-[25px] text-black tracking-[3px] uppercase leading-none">
                      PACKING LIST
                    </div>
                  </div>
                </div>
                <!-- Metadata List Sesuai PDF 4 (Garis Menyatu Penuh ke Pinggir) -->
                <div class="px-3 py-1.5 border-b-[2.5px] border-black text-xs space-y-1 leading-snug shrink-0">
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PROGRAM</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.program_name }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">NOMOR</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.contract_number }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">PRODUK</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ previewDOData.product_name || 'Nanggala P9' }}</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1">
                    <span class="col-span-3 font-bold">ID. PETI</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ (previewCurrentCrate.crate_id ||
                      previewCurrentCrate.crate_number || defaultCrateIdForNow) }}-02</span>
                  </div>
                  <div class="grid grid-cols-12 gap-1 pt-0.5">
                    <span class="col-span-3 font-bold">PENERIMA</span>
                    <span class="col-span-1 font-bold">:</span>
                    <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                      <div>{{ previewDOData.customer_name }}</div>
                      <div>BADAN LOGISTIK PERTAHANAN</div>
                      <div>{{ previewDOData.customer_address }}</div>
                    </div>
                  </div>
                </div>
                <!-- Serial Number 1 Kolom Sesuai Dus 2 (Garis Menyatu Penuh Langsung ke Baris Bawah) -->
                <div class="flex-1 min-h-0 border-b-[2.5px] border-black flex flex-col justify-between text-xs">
                  <!-- Baris Judul Pak 2: Garis menyatu penuh sampe ujung -->
                  <div
                    class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                    PAK 2 DARI 2
                  </div>
                  <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                  <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                    <span class="w-6">NO.</span>
                    <span>SERIAL NUMBER</span>
                  </div>
                  <!-- Daftar 10 Serial Number -->
                  <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                    <div v-for="s in 10" :key="'dus2_'+s" class="flex items-center leading-tight">
                      <span class="w-6 font-bold">{{ s }}.</span>
                      <span class="font-black">{{ previewCurrentCrate.boxes?.[1]?.items?.[s - 1]?.serial_number || ''
                        }}</span>
                    </div>
                  </div>
                </div>
                <!-- Bottom Row: Garis Kotak Menyatu Utuh & Garis Vertikal Menyambung Lurus 50/50 Langsung Menempel -->
                <div class="flex text-black text-xs shrink-0">
                  <!-- Kolom Kiri 50%: TANGGAL & JUMLAH -->
                  <div class="w-1/2 border-r-[2.5px] border-black flex flex-col justify-between">
                    <div class="border-b-[2.5px] border-black px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">TANGGAL</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">{{ formatNumericDate(previewDOData.do_date)
                        }}</span>
                    </div>
                    <div class="px-3 py-1.5 flex items-center">
                      <span class="font-black text-xs w-[64px] shrink-0">JUMLAH</span>
                      <span class="font-black mr-1.5">:</span>
                      <span class="font-black text-sm tracking-wide">10 UNIT</span>
                    </div>
                  </div>

                  <!-- Kolom Kanan 50%: NO. PETI & Angka Disatukan Utuh Tanpa Garis Pemisah -->
                  <div class="w-1/2 px-3 py-1.5 flex flex-col items-center justify-center">
                    <div class="font-black uppercase tracking-wider text-[11px] mb-1">
                      NO. PETI :
                    </div>
                    <div class="text-xl font-black tracking-wider leading-none">
                      {{ previewCrateIndex + 1 }} DARI {{ previewDOData.crates?.length || 20 }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

            <!-- ================================================================= -->
            <!-- 5. LABEL SN SATUAN (THERMAL 80x20mm) -->
            <!-- ================================================================= -->
            <div v-else-if="previewDocType === 'label_sn'"
              class="flex flex-col items-center justify-center py-6 space-y-4">
              <!-- Label SN Cards -->
              <div v-for="(sn, snIdx) in getDOSerialNumbers(previewDOData)" :key="'preview_sn_'+snIdx"
                class="bg-white border-2 border-slate-900 shadow-lg rounded-none select-none"
                style="width: 320px; height: 80px; padding: 5px 8px 5px 12px; box-sizing: border-box; display: flex; flex-direction: row; align-items: center; justify-content: space-between; overflow: hidden; font-family: 'Roboto', Arial, sans-serif;">

                <!-- Kiri: Info Block -->
                <div
                  style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; padding-right: 6px; box-sizing: border-box;">
                  <!-- Baris 1: Logo NKRI -->
                  <div style="display: flex; align-items: center; height: 14px; margin-bottom: 2px;">
                    <img :src="nkriLabelLogoBase64 || nkriLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                      alt="NKRI Logo"
                      style="height: 14px; max-height: 16px; width: auto; max-width: 90px; object-fit: contain; display: block;">
                  </div>

                  <!-- Baris 2: Nama Produk -->
                  <div
                    style="font-size: 14px; font-weight: 900; color: #000000; line-height: 15px; font-family: 'Roboto', Arial, sans-serif; white-space: nowrap; margin-top: -1px; margin-bottom: 4.5px;">
                    {{ previewDOData.product_name || 'Nanggala P9' }}
                  </div>

                  <!-- Baris 3: Serial Number: -->
                  <div
                    style="font-size: 7.5px; font-weight: 700; color: #000000; line-height: 9px; font-family: 'Roboto', Arial, sans-serif; margin-bottom: 2px;">
                    Serial Number:
                  </div>

                  <!-- Baris 4: SN Besar & Tebal + Made In Indonesia 2026 -->
                  <div
                    style="display: flex; align-items: baseline; justify-content: flex-start; width: 100%; white-space: nowrap; line-height: 16px;">
                    <span
                      style="font-size: 15px; font-weight: 900; color: #000000; font-family: 'Roboto', Arial, sans-serif; letter-spacing: 0.2px; line-height: 16px; flex-shrink: 0;">
                      {{ sn }}
                    </span>
                    <span
                      style="font-size: 7.5px; font-weight: 700; color: #000000; font-family: 'Roboto', Arial, sans-serif; line-height: 16px; margin-left: 20px;">
                      Made In Indonesia 2026
                    </span>
                  </div>
                </div>

                <!-- Kanan: QR Code -->
                <div :id="'do-sn-qr-preview-' + snIdx"
                  style="flex-shrink: 0; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: #ffffff;">
                </div>
              </div>

              <p class="text-[10px] text-slate-500 text-center font-medium">
                <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                Format label stiker barcode thermal: <strong>80mm &times; 20mm</strong> (Tiap unit serial number dicetak
                1 label)
              </p>
            </div>

          </div>
        </div>

@endverbatim
