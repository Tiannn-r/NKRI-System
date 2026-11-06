@verbatim
      <!-- ================= TAB: DELIVERY ORDER & PACKING LIST (DO & PL) ================= -->
      <section v-if="currentTab === 'delivery_order'" class="space-y-4 sm:space-y-6 animate-fadeIn">

        <!-- Top Header & Sub-Tab Bar -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="px-2.5 py-0.5 rounded-lg bg-red-100 text-red-700 font-bold text-[10px] uppercase tracking-wider">
                  Logistik &amp; PPIC
                </span>
                <span class="text-xs text-slate-400">|</span>
                <span class="text-xs font-semibold text-slate-500">PT Nanggala Kencana Rekatama Indonesia</span>
              </div>
              <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
                Delivery Order (DO) &amp; Packing List (PL)
              </h2>
            </div>

            <!-- Sub-Tab Switcher (Responsive Stack on Mobile/Tablet) -->
            <div class="flex flex-wrap items-center gap-2">
              <button type="button" @click="doSubTab = 'label_pl_only'"
                :class="doSubTab === 'label_pl_only' ? 'bg-emerald-600 text-white font-black shadow-md shadow-emerald-600/25 ring-2 ring-emerald-400/30' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold border border-emerald-300/80'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-tags text-sm"></i>
                <span>Buat Label PL Saja</span>
                <span
                  class="px-1.5 py-0.5 rounded text-[9.5px] font-black uppercase bg-emerald-700/20 text-emerald-900 border border-emerald-300">Cepat</span>
              </button>

              <button type="button" @click="doSubTab = 'form'"
                :class="doSubTab === 'form' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-file-circle-plus text-sm"></i>
                <span>Buat Surat Jalan Lengkap</span>
              </button>

              <button type="button" @click="doSubTab = 'history'"
                :class="doSubTab === 'history' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                <span>Riwayat DO ({{ doList.length }})</span>
              </button>

              <button type="button" @click="openStandaloneLabelsHistory"
                :class="doSubTab === 'labels_history' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-box-archive text-sm"></i>
                <span>Riwayat Label PL ({{ standaloneLabelsList.length }})</span>
              </button>

              <button type="button" @click="doSubTab = 'customers'"
                :class="doSubTab === 'customers' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-users text-sm"></i>
                <span>Database Customer</span>
              </button>

            </div>
          </div>
        </div>

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
                <select v-model="previewDOId" @change="loadDOPreviewData(previewDOId)"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white transition min-h-[38px]">
                  <option v-for="d in doList" :key="d.id" :value="d.id">
                    #{{ d.do_number }} - {{ d.customer_name }} ({{ d.total_quantity }} Unit / {{ d.total_crates }} Peti)
                  </option>
                </select>
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
                  <select v-model="previewPackingListSheetIndex"
                    class="flex-1 bg-slate-50 border border-slate-300 rounded-xl px-2 py-1.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 transition min-h-[38px]">
                    <option v-for="(sheet, sIdx) in previewPackingListSheets" :key="sIdx" :value="sIdx">
                      Lembar {{ sIdx + 1 }} (Peti {{ sheet.crate1?.crate_id || sheet.crate1?.crate_number }} {{
                      sheet.crate2 ? '& ' + (sheet.crate2?.crate_id || sheet.crate2?.crate_number) : '' }})
                    </option>
                  </select>
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
                  <select v-model="previewCrateIndex"
                    class="flex-1 bg-slate-50 border border-slate-300 rounded-xl px-2 py-1.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 transition min-h-[38px]">
                    <option v-for="(c, cIdx) in previewDOData.crates" :key="c.id || cIdx" :value="cIdx">
                      Peti {{ cIdx + 1 }} ({{ c.crate_number }})
                    </option>
                  </select>
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
            class="bg-slate-200/80 p-4 sm:p-8 rounded-2xl border border-slate-300 overflow-x-auto do-preview-scope"
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
                              style="border-right: 1.5px solid #000000; width: 34%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              Produk</th>
                            <th
                              style="border-right: 1.5px solid #000000; width: 18%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              Jumlah</th>
                            <th
                              style="border-right: 1.5px solid #000000; width: 18%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              Satuan</th>
                            <th style="width: 24%; text-align: center; font-weight: 700; padding: 2px 4px;">
                              {{ previewDOData.package_type === 'unit' ? 'Serial Number' : 'Packing List' }}
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
                                <div style="font-weight: 600;">{{ previewDOData.total_quantity || 400 }}</div>
                                <div v-if="previewDOData.package_type === 'crate'"
                                  style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                                  ({{ getDOCrateCount(previewDOData) }} Peti / {{ getDOBoxCount(previewDOData) }} Dus)
                                </div>
                                <div v-else-if="previewDOData.package_type === 'box'"
                                  style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                                  ({{ getDOBoxCount(previewDOData) }} Dus)
                                </div>
                              </td>
                              <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">UNIT
                              </td>
                              <td style="text-align: center; padding: 2px 4px; vertical-align: middle;">
                                <div class="flex items-center justify-center">
                                  <a :href="getDOTrackingFullUrl(previewDOData)" target="_blank"
                                    :title="previewDOData?.cloud_pdf_url ? 'Buka Dokumen PDF Resmi di Cloudinary' : 'Buka Dokumen Tracking PL'"
                                    class="cursor-pointer hover:opacity-85 transition inline-flex items-center justify-center">
                                    <div id="do-sj-qr-preview" class="flex items-center justify-center mx-auto qr-crisp"
                                      style="width: 50px; height: 50px; min-width: 50px; max-width: 50px; min-height: 50px; max-height: 50px; aspect-ratio: 1 / 1; overflow: hidden; background: #ffffff; padding: 1px; box-sizing: border-box;">
                                    </div>
                                  </a>
                                </div>
                              </td>
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
                              previewDOData.do_date }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">ID
                              Peti
                            </td>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;"
                              class="font-sans text-slate-900">
                              : {{ [previewCurrentPackingListSheet.crate1?.crate_id ||
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
                              previewDOData.contract_number }}</td>
                          </tr>
                          <tr>
                            <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">
                              Program
                            </td>
                            <td
                              style="font-weight: 400; padding: 2.5px 0; vertical-align: top; max-width: 440px; line-height: 1.4; font-size: 13px;">
                              : {{ previewDOData.program_name }}
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

                    <!-- 3. TABEL ISI SERIAL NUMBER (4 TABEL: 2 PETI DENGAN MASING-MASING 2 DUS @ 10 SN = MAKS 20 SN PER PETI, FONT DIGEDEIN) -->
                    <div class="grid grid-cols-2 gap-x-5 gap-y-3.5 my-2"
                      style="font-family: 'Roboto', Arial, sans-serif;">
                      <!-- BARIS 1: PETI 1 (Dus 1 & Dus 2) -->
                      <!-- Tabel 1: Peti 1 - Pak 1 -->
                      <div>
                        <div class="mb-1 font-bold" style="font-size: 12.5px;">
                          No. Pak : <span class="font-bold">{{ previewCurrentPackingListSheet.crate1?.boxes?.[0]?.box_id
                            || (previewCurrentPackingListSheet.crate1?.crate_id ?
                            previewCurrentPackingListSheet.crate1.crate_id + '-01' : defaultCrateIdForNow + '-01')
                            }}</span>
                          <span class="text-[11px] text-slate-500 font-normal ml-1">(Peti {{
                            previewCurrentPackingListSheet.crate1?.crate_id ||
                            previewCurrentPackingListSheet.crate1?.crate_number || '1' }})</span>
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
                            <tr v-for="s in 10" :key="'preview_pl_c1b1_'+s"
                              style="border-bottom: 1px solid #000000; height: 18.5px;">
                              <td
                                style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">
                                {{ s }}</td>
                              <td
                                style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">
                                {{ previewCurrentPackingListSheet.crate1?.boxes?.[0]?.items?.[s - 1]?.serial_number ||
                                ''
                                }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <!-- Tabel 2: Peti 1 - Pak 2 -->
                      <div>
                        <div class="mb-1 font-bold" style="font-size: 12.5px;">
                          No. Pak : <span class="font-bold">{{ previewCurrentPackingListSheet.crate1?.boxes?.[1]?.box_id
                            || (previewCurrentPackingListSheet.crate1?.crate_id ?
                            previewCurrentPackingListSheet.crate1.crate_id + '-02' : defaultCrateIdForNow + '-02')
                            }}</span>
                          <span class="text-[11px] text-slate-500 font-normal ml-1">(Peti {{
                            previewCurrentPackingListSheet.crate1?.crate_id ||
                            previewCurrentPackingListSheet.crate1?.crate_number || '1' }})</span>
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
                            <tr v-for="s in 10" :key="'preview_pl_c1b2_'+s"
                              style="border-bottom: 1px solid #000000; height: 18.5px;">
                              <td
                                style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">
                                {{ s }}</td>
                              <td
                                style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">
                                {{ previewCurrentPackingListSheet.crate1?.boxes?.[1]?.items?.[s - 1]?.serial_number ||
                                ''
                                }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <!-- BARIS 2: PETI 2 (Dus 1 & Dus 2) -->
                      <!-- Tabel 3: Peti 2 - Pak 1 -->
                      <div>
                        <div class="mb-1 font-bold" style="font-size: 12.5px;">
                          No. Pak : <span class="font-bold">{{ previewCurrentPackingListSheet.crate2 ?
                            (previewCurrentPackingListSheet.crate2.boxes?.[0]?.box_id ||
                            (previewCurrentPackingListSheet.crate2.crate_id ?
                            previewCurrentPackingListSheet.crate2.crate_id + '-01' : '')) : '-' }}</span>
                          <span v-if="previewCurrentPackingListSheet.crate2"
                            class="text-[11px] text-slate-500 font-normal ml-1">(Peti {{
                            previewCurrentPackingListSheet.crate2.crate_id ||
                            previewCurrentPackingListSheet.crate2.crate_number }})</span>
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
                            <tr v-for="s in 10" :key="'preview_pl_c2b1_'+s"
                              style="border-bottom: 1px solid #000000; height: 18.5px;">
                              <td
                                style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">
                                {{ s }}</td>
                              <td
                                style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">
                                {{ previewCurrentPackingListSheet.crate2?.boxes?.[0]?.items?.[s - 1]?.serial_number ||
                                ''
                                }}
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>

                      <!-- Tabel 4: Peti 2 - Pak 2 -->
                      <div>
                        <div class="mb-1 font-bold" style="font-size: 12.5px;">
                          No. Pak : <span class="font-bold">{{ previewCurrentPackingListSheet.crate2 ?
                            (previewCurrentPackingListSheet.crate2.boxes?.[1]?.box_id ||
                            (previewCurrentPackingListSheet.crate2.crate_id ?
                            previewCurrentPackingListSheet.crate2.crate_id + '-02' : '')) : '-' }}</span>
                          <span v-if="previewCurrentPackingListSheet.crate2"
                            class="text-[11px] text-slate-500 font-normal ml-1">(Peti {{
                            previewCurrentPackingListSheet.crate2.crate_id ||
                            previewCurrentPackingListSheet.crate2.crate_number }})</span>
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
                            <tr v-for="s in 10" :key="'preview_pl_c2b2_'+s"
                              style="border-bottom: 1px solid #000000; height: 18.5px;">
                              <td
                                style="border-right: 1.5px solid #000000; padding: 1.5px 6px; text-align: center; font-weight: 700; font-size: 11.5px;">
                                {{ s }}</td>
                              <td
                                style="padding: 1.5px 8px; font-family: 'Roboto', Arial, sans-serif; font-weight: 700; letter-spacing: 0.3px; font-size: 12px;">
                                {{ previewCurrentPackingListSheet.crate2?.boxes?.[1]?.items?.[s - 1]?.serial_number ||
                                ''
                                }}
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
              <div class="bg-white border-[3px] border-black text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; box-sizing: border-box; overflow: hidden;">
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

            <!-- ================================================================= -->
            <!-- 4. LABEL DUS (1:1 DENGAN PDF 4) -->
            <!-- ================================================================= -->
            <div v-else-if="previewDocType === 'label_dus'"
              class="flex flex-col sm:flex-row items-center justify-center gap-6 py-4">
              <!-- DUS 1 (PAK 1 DARI 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
              <div class="bg-white border-[3px] border-black text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; box-sizing: border-box; overflow: hidden;">
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
                    <span class="col-span-3 font-bold">{{ (previewCurrentCrate.boxes && previewCurrentCrate.boxes.length
                      > 1) ? 'ID. PETI' : 'ID. DUS' }}</span>
                    <span class="col-span-1 font-bold">:</span>
                    <span class="col-span-8 font-normal">{{ (previewCurrentCrate.boxes &&
                      previewCurrentCrate.boxes.length > 1) ? ((previewCurrentCrate.crate_id ||
                      previewCurrentCrate.crate_number || defaultCrateIdForNow) + '-01') : (previewCurrentCrate.box_id
                      || previewCurrentCrate.boxes?.[0]?.box_id || (previewCurrentCrate.crate_id ?
                      previewCurrentCrate.crate_id + '-01' : 'BOX')) }}</span>
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
                  <!-- Baris Judul Pak 1: Garis menyatu penuh sampe ujung -->
                  <div
                    class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                    {{ (previewCurrentCrate.boxes && previewCurrentCrate.boxes.length > 1) ? 'PAK 1 DARI 2' : ('DUS ' +
                    (previewCrateIndex + 1) + ' DARI ' + (previewDOData.crates?.length || 10)) }}
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
                      <span class="font-black">{{ previewCurrentCrate.boxes?.[0]?.items?.[s - 1]?.serial_number || ''
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

                  <!-- Kolom Kanan 50%: NO. PETI / NO. DUS & Angka Disatukan Utuh Tanpa Garis Pemisah -->
                  <div class="w-1/2 px-3 py-1.5 flex flex-col items-center justify-center">
                    <div class="font-black uppercase tracking-wider text-[11px] mb-1">
                      {{ (previewCurrentCrate.boxes && previewCurrentCrate.boxes.length > 1) ? 'NO. PETI :' : 'NO. DUS :' }}
                    </div>
                    <div class="text-xl font-black tracking-wider leading-none">
                      {{ previewCrateIndex + 1 }} DARI {{ previewDOData.crates?.length || 20 }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- DUS 2 (PAK 2 DARI 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
              <div v-if="previewCurrentCrate.boxes && previewCurrentCrate.boxes.length > 1"
                class="bg-white border-[3px] border-black text-black shadow-2xl flex flex-col"
                style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; height: 150mm; max-height: 150mm; box-sizing: border-box; overflow: hidden;">
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
        <!-- ================= SUB-TAB: BUAT LABEL PACKING LIST SAJA (STANDALONE) ================= -->
        <div v-if="doSubTab === 'label_pl_only'" class="max-w-4xl mx-auto space-y-4 sm:space-y-5 animate-fadeIn">
          <!-- Banner Pengantar -->
          <div
            class="bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border border-emerald-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-start gap-3">
                <span
                  class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-600/20 shrink-0">
                  <i class="fa-solid fa-tags text-lg"></i>
                </span>
                <div>
                  <h3 class="font-black text-base text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Pembuatan Label Packing List Mandiri</span>
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">Standalone</span>
                  </h3>
                  <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Khusus untuk mencetak <strong>Label Peti &amp; Label Dus</strong> langsung untuk penempelan fisik di
                    gudang. Tidak memerlukan pengisian Surat Jalan atau Customer. Data disimpan ke riwayat dan nomor ID
                    Peti otomatis direset per bulan.
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
                <button type="button" @click="openStandaloneLabelsHistory"
                  class="bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs px-3.5 py-2 rounded-xl border border-slate-300 shadow-2xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                  <i class="fa-solid fa-box-archive text-slate-500"></i>
                  <span>Lihat Riwayat Label ({{ standaloneLabelsList.length }})</span>
                </button>
              </div>
            </div>
          </div>

          <!-- NOTIFIKASI FORM DIRESET SETELAH SUKSES SIMPAN -->
          <div v-if="lastSavedPLInfo"
            class="bg-emerald-50 border border-emerald-300 rounded-2xl p-4 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
              <div
                class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs shrink-0">
                <i class="fa-solid fa-circle-check text-base"></i>
              </div>
              <div>
                <div class="text-xs font-black text-emerald-950 flex items-center gap-2">
                  <span>Packing List Tersimpan: {{ lastSavedPLInfo.pl_number }}</span>
                  <span class="text-[10px] bg-emerald-200 text-emerald-900 px-1.5 py-0.2 rounded font-mono">Batch #{{
                    lastSavedPLInfo.batch_number }}</span>
                </div>
                <div class="text-[11px] text-emerald-800 mt-0.5">
                  Data telah tersimpan di sistem. Form otomatis direset dari awal untuk penyiapan dokumen berikutnya
                  (Pukul {{ lastSavedPLInfo.saved_at }}).
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="openStandaloneLabelsHistory"
                class="text-xs font-bold text-emerald-900 bg-white hover:bg-emerald-100 border border-emerald-300 px-3 py-1.5 rounded-xl transition cursor-pointer shadow-2xs">
                Lihat di Riwayat
              </button>
              <button type="button" @click="lastSavedPLInfo = null"
                class="text-emerald-600 hover:text-emerald-900 p-1.5 rounded-lg cursor-pointer transition">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
          </div>

          <!-- KARTU 1: PENGATURAN KEMASAN -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <span
                  class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">1</span>
                <h4 class="font-black text-sm text-slate-900">Jenis Kemasan &amp; Jumlah</h4>
              </div>
              <span
                class="text-[11px] font-mono font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200">
                Total: {{ (labelForm.package_type === 'crate' ? labelForm.package_count * 20 : (labelForm.package_type
                === 'box' ? labelForm.package_count * 10 : labelForm.package_count)) }} Unit
              </span>
            </div>

            <!-- Pilihan Jenis Kemasan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <button type="button" @click="labelForm.package_type = 'crate'; syncLabelPackagingStructure()"
                :class="labelForm.package_type === 'crate' ? 'border-emerald-600 bg-emerald-50/70 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                class="p-3.5 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-1.5">
                    <i class="fa-solid fa-boxes-stacked text-emerald-600"></i>
                    <span>Per Peti</span>
                  </div>
                  <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">20 Unit</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">2 Dus @ 10 SN per Peti</div>
              </button>

              <button type="button" @click="labelForm.package_type = 'box'; syncLabelPackagingStructure()"
                :class="labelForm.package_type === 'box' ? 'border-emerald-600 bg-emerald-50/70 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                class="p-3.5 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-1.5">
                    <i class="fa-solid fa-box text-emerald-600"></i>
                    <span>Per Dus</span>
                  </div>
                  <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">10 Unit</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">1 Dus @ 10 SN langsung</div>
              </button>

              <button type="button" @click="labelForm.package_type = 'unit'; syncLabelPackagingStructure()"
                :class="labelForm.package_type === 'unit' ? 'border-emerald-600 bg-emerald-50/70 ring-2 ring-emerald-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                class="p-3.5 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-1.5">
                    <i class="fa-solid fa-tag text-emerald-600"></i>
                    <span>Satuan</span>
                  </div>
                  <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">1 Unit</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Label stiker barcode SN</div>
              </button>
            </div>

            <!-- Input Tanggal, Jumlah & Batch -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-1">
              <!-- Tanggal Packing List (Bisa Diubah & Back-Date) -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-700">Tanggal Label / PL <span
                      class="text-red-500">*</span></label>
                </div>
                <input type="date" v-model="labelForm.date" @input="syncLabelPackagingStructure"
                  @change="syncLabelPackagingStructure"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]"
                  title="Pilih tanggal Packing List (menyesuaikan prefix ID Peti & Dus)">
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">
                  Jumlah {{ labelForm.package_type === 'crate' ? 'Peti' : (labelForm.package_type === 'box' ? 'Dus' :
                  'Unit') }}
                </label>
                <input type="number" v-model.number="labelForm.package_count" @input="syncLabelPackagingStructure"
                  min="1" max="50"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]">
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Nomor Batch</label>
                <input type="number" v-model.number="labelForm.batch_number" @input="syncLabelPackagingStructure"
                  min="1"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]">
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Nama Produk</label>
                <input type="text" v-model="labelForm.product_name" placeholder="Nanggala P9"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]">
              </div>
            </div>
          </div>

          <!-- KARTU 2: PENGISIAN SERIAL NUMBER -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <span
                  class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">2</span>
                <h4 class="font-black text-sm text-slate-900">Alokasi Serial Number Unit</h4>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="openLabelBulkModal"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]"
                  title="Input data Serial Number secara bulking / tempel massal">
                  <i class="fa-solid fa-list-check text-xs"></i>
                  <span>Input Bulking SN</span>
                </button>
              </div>
            </div>

            <!-- Stepper Antar Peti/Dus dengan Pagination Rapih & Simetris -->
            <div v-if="labelForm.crates.length > 1" class="bg-slate-50/70 border border-slate-200/90 rounded-2xl p-3 sm:p-4 space-y-3">
              <!-- Toolbar Atas: Info Status & Kontrol Navigasi -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2.5 border-b border-slate-200/80">
                <!-- Info Status Kemasan & Jangkauan -->
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/70 text-xs font-bold">
                    <i class="fa-solid fa-boxes-stacked text-emerald-600 text-[11px]"></i>
                    <span>Total {{ labelForm.crates.length.toLocaleString('id-ID') }} {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }}</span>
                  </span>
                  <span class="text-slate-300 hidden sm:inline">|</span>
                  <span class="text-xs text-slate-600 font-medium">
                    Menampilkan <strong class="text-slate-800">{{ ((labelCratePage - 1) * labelCratesPerPage) + 1 }}</strong> - <strong class="text-slate-800">{{ Math.min(labelCratePage * labelCratesPerPage, labelForm.crates.length) }}</strong>
                  </span>
                </div>

                <!-- Kontrol Navigasi Halaman Compact (Muncul jika > 1 Halaman) -->
                <div v-if="totalLabelCratePages > 1" class="flex items-center gap-1.5 self-end sm:self-auto flex-wrap">
                  <!-- Tombol First & Prev -->
                  <div class="inline-flex rounded-lg shadow-2xs border border-slate-200 bg-white p-0.5">
                    <button type="button" @click="setLabelCratePage(1)" :disabled="labelCratePage <= 1"
                      :class="labelCratePage <= 1 ? 'opacity-30 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100 hover:text-emerald-700 cursor-pointer'"
                      class="w-7 h-7 rounded-md flex items-center justify-center text-xs transition" title="Halaman Pertama">
                      <i class="fa-solid fa-angles-left text-[10px]"></i>
                    </button>
                    <button type="button" @click="prevLabelCratePage" :disabled="labelCratePage <= 1"
                      :class="labelCratePage <= 1 ? 'opacity-30 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100 hover:text-emerald-700 cursor-pointer'"
                      class="w-7 h-7 rounded-md flex items-center justify-center text-xs transition" title="Halaman Sebelumnya">
                      <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>
                  </div>

                  <!-- Input Halaman Aktif -->
                  <div class="flex items-center gap-1 text-xs bg-white border border-slate-200 px-2 py-1 rounded-lg shadow-2xs">
                    <span class="text-slate-500 font-medium">Hal</span>
                    <input type="number" min="1" :max="totalLabelCratePages" :value="labelCratePage"
                      @change="onLabelCratePageInput($event)"
                      @keyup.enter="onLabelCratePageInput($event)"
                      class="w-12 text-center font-bold text-slate-800 bg-slate-50 border border-slate-300 rounded px-1 py-0.5 text-xs focus:outline-none focus:border-emerald-600 focus:bg-white">
                    <span class="text-slate-500 font-medium">/ {{ totalLabelCratePages }}</span>
                  </div>

                  <!-- Tombol Next & Last -->
                  <div class="inline-flex rounded-lg shadow-2xs border border-slate-200 bg-white p-0.5">
                    <button type="button" @click="nextLabelCratePage" :disabled="labelCratePage >= totalLabelCratePages"
                      :class="labelCratePage >= totalLabelCratePages ? 'opacity-30 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100 hover:text-emerald-700 cursor-pointer'"
                      class="w-7 h-7 rounded-md flex items-center justify-center text-xs transition" title="Halaman Berikutnya">
                      <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                    <button type="button" @click="setLabelCratePage(totalLabelCratePages)" :disabled="labelCratePage >= totalLabelCratePages"
                      :class="labelCratePage >= totalLabelCratePages ? 'opacity-30 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100 hover:text-emerald-700 cursor-pointer'"
                      class="w-7 h-7 rounded-md flex items-center justify-center text-xs transition" title="Halaman Terakhir">
                      <i class="fa-solid fa-angles-right text-[10px]"></i>
                    </button>
                  </div>

                  <!-- Lompat Cepat ke Dus / Peti Tertentu -->
                  <div class="flex items-center gap-1 pl-1">
                    <input type="number" min="1" :max="labelForm.crates.length" placeholder="No..."
                      v-model.number="quickJumpTarget"
                      @keyup.enter="jumpToLabelCrate(quickJumpTarget)"
                      class="w-14 text-center text-xs font-semibold bg-white border border-slate-200 rounded-lg px-1.5 py-1 text-slate-700 placeholder-slate-400 focus:outline-none focus:border-emerald-600 shadow-2xs">
                    <button type="button" @click="jumpToLabelCrate(quickJumpTarget)"
                      class="px-2 py-1 bg-white hover:bg-emerald-600 hover:text-white text-slate-700 rounded-lg text-xs font-bold border border-slate-200 transition cursor-pointer shadow-2xs"
                      title="Lompat ke Nomor Ini">
                      Ke
                    </button>
                  </div>
                </div>
              </div>

              <!-- Grid Stepper Kartu Dus/Peti: Simetris 5 Kolom x 2 Baris (Tepat 10 Tombol per Halaman) -->
              <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                <button v-for="item in pagedLabelCrates" :key="item.originalIndex" type="button"
                  @click="selectLabelCrate(item.originalIndex)"
                  :class="activeLabelCrateIndex === item.originalIndex 
                    ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-300 border-emerald-600' 
                    : 'bg-white hover:bg-slate-100/80 text-slate-700 border-slate-200 hover:border-slate-300 shadow-2xs'"
                  class="border rounded-xl p-2 sm:p-2.5 transition-all cursor-pointer flex flex-col justify-between text-left min-h-[52px] group">
                  
                  <!-- Baris Atas: Label & Status SN Terisi -->
                  <div class="flex items-center justify-between gap-1 w-full">
                    <span class="text-xs font-black truncate"
                      :class="activeLabelCrateIndex === item.originalIndex ? 'text-white' : 'text-slate-800'">
                      {{ labelForm.package_type === 'crate' ? 'Peti ' + (item.originalIndex + 1) : 'Dus ' + (item.originalIndex + 1) }}
                    </span>
                    <span class="text-[9.5px] font-mono font-bold px-1.5 py-0.5 rounded shrink-0"
                      :class="getLabelCrateFilledCount(item.originalIndex) === getLabelCrateRequiredCount(item.originalIndex)
                        ? (activeLabelCrateIndex === item.originalIndex ? 'bg-emerald-800 text-white' : 'bg-emerald-100 text-emerald-800')
                        : (activeLabelCrateIndex === item.originalIndex ? 'bg-emerald-700 text-emerald-100' : 'bg-slate-100 text-slate-600')">
                      {{ getLabelCrateFilledCount(item.originalIndex) }}/{{ getLabelCrateRequiredCount(item.originalIndex) }}
                    </span>
                  </div>

                  <!-- Baris Bawah: Monospace Container ID -->
                  <div class="mt-1 flex items-center justify-between text-[10px] font-mono truncate w-full"
                    :class="activeLabelCrateIndex === item.originalIndex ? 'text-emerald-100' : 'text-slate-500'">
                    <span class="truncate">
                      {{ (labelForm.package_type === 'box' ? (item.crate.box_id || (item.crate.boxes && item.crate.boxes[0] && item.crate.boxes[0].box_id) || item.crate.crate_id) : item.crate.crate_id) || '-' }}
                    </span>
                    <i v-if="activeLabelCrateIndex === item.originalIndex" class="fa-solid fa-circle-check text-[10px] text-emerald-200 shrink-0 ml-1"></i>
                  </div>
                </button>
              </div>
            </div>

            <!-- Detail Isi Peti / Dus Aktif -->
            <div v-if="labelForm.crates[activeLabelCrateIndex]" class="space-y-4">
              <div
                class="flex items-center justify-between bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200/80">
                <div class="flex items-center gap-2">
                  <span class="font-black font-mono text-xs text-slate-900">
                    {{ labelForm.package_type === 'box' ? 'ID DUS: ' + (labelForm.crates[activeLabelCrateIndex]?.box_id
                    || labelForm.crates[activeLabelCrateIndex]?.boxes?.[0]?.box_id) : 'ID PETI: ' +
                    labelForm.crates[activeLabelCrateIndex]?.crate_id }}
                  </span>
                  <span class="text-[10.5px] text-slate-500 font-medium">
                    ({{ labelForm.package_type === 'box' ? '10 Unit SN' :
                    (labelForm.crates[activeLabelCrateIndex]?.boxes?.length || 1) + ' Dus' }})
                  </span>
                  <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                    :class="getLabelCrateFilledCount(activeLabelCrateIndex) === getLabelCrateRequiredCount(activeLabelCrateIndex) ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'">
                    {{ getLabelCrateFilledCount(activeLabelCrateIndex) }} / {{ getLabelCrateRequiredCount(activeLabelCrateIndex) }} SN Terisi
                  </span>
                </div>
                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">
                  {{ labelForm.package_type === 'crate' ? 'Peti Ke-' + (activeLabelCrateIndex + 1) : 'Dus Ke-' +
                  (activeLabelCrateIndex + 1) }}
                </span>
              </div>

              <!-- Grid Dus: 2 Dus jika Peti, atau 1 Dus (2 kolom slot) jika Kemasan Dus -->
              <div
                :class="labelForm.package_type === 'box' ? 'max-w-3xl mx-auto' : 'grid grid-cols-1 sm:grid-cols-2 gap-4'">
                <div v-for="(box, bIdx) in labelForm.crates[activeLabelCrateIndex].boxes" :key="bIdx"
                  class="bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 space-y-3">
                  <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                    <span class="font-black text-xs text-slate-800 flex items-center gap-1.5">
                      <i class="fa-solid fa-box text-emerald-600 text-xs"></i>
                      <span>{{ labelForm.package_type === 'box' ? 'Dus ' + (activeLabelCrateIndex + 1) : 'Dus ' + (bIdx
                        + 1) }}</span>
                    </span>
                    <span
                      class="text-[10px] font-mono font-bold text-slate-600 bg-white px-2 py-0.5 rounded border border-slate-200">
                      {{ box.box_id }}
                    </span>
                  </div>

                  <!-- Slot Input SN 1 to 10 -->
                  <div
                    :class="labelForm.package_type === 'box' ? 'grid grid-cols-1 sm:grid-cols-2 gap-2.5' : 'space-y-2'">
                    <div v-for="(item, sIdx) in box.items" :key="sIdx" class="space-y-0.5">
                      <div class="flex items-center gap-2">
                        <span class="w-6 text-[10px] font-bold text-slate-400 font-mono text-right shrink-0">
                          #{{ sIdx + 1 }}
                        </span>
                        <div class="relative w-full">
                          <input type="text" v-model="item.serial_number"
                            @input="onLabelSNInput(item)"
                            @change="onLabelSNInput(item)"
                            @blur="onLabelSNInput(item)"
                            placeholder="Pilih / ketik SN..."
                            :list="'available-label-sns-' + activeLabelCrateIndex + '-' + bIdx + '-' + sIdx"
                            :class="{
                              'border-rose-500 bg-rose-50/70 text-rose-900 ring-2 ring-rose-300': getLabelSlotStatus(item) === 'duplicate' || getLabelSlotStatus(item) === 'invalid',
                              'border-emerald-500 bg-emerald-50/30 text-emerald-900': getLabelSlotStatus(item) === 'valid',
                              'border-slate-300 bg-white text-slate-800': getLabelSlotStatus(item) === 'empty'
                            }"
                            class="w-full border rounded-lg pl-2.5 pr-7 py-1.5 text-xs font-mono font-bold focus:outline-none focus:border-emerald-600 transition min-h-[34px]">
                          <button v-if="item.serial_number" type="button" @click="item.serial_number = ''; onLabelSNInput(item)"
                            class="absolute right-2 top-2 text-slate-400 hover:text-rose-600 transition cursor-pointer"
                            title="Hapus Serial Number">
                            <i class="fa-solid fa-xmark text-xs"></i>
                          </button>
                          <datalist :id="'available-label-sns-' + activeLabelCrateIndex + '-' + bIdx + '-' + sIdx">
                            <option v-for="sn in getAvailableSNsForLabelSlot(item.serial_number)" :key="sn" :value="sn">{{ sn }}</option>
                          </datalist>
                        </div>
                      </div>
                      <div v-if="getLabelSlotStatus(item) === 'duplicate'" class="text-[9.5px] font-bold text-rose-600 ml-8 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[9px]"></i>
                        <span>Nomor seri duplikat! Sudah diinput pada slot lain.</span>
                      </div>
                      <div v-else-if="getLabelSlotStatus(item) === 'invalid'" class="text-[9.5px] font-bold text-amber-600 ml-8 flex items-center gap-1">
                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                        <span>SN belum lolos gudang (belum ACC penerimaan fisik).</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tombol Navigasi Cepat Dus/Peti Sebelumnya & Selanjutnya -->
              <div v-if="labelForm.crates.length > 1" class="flex items-center justify-between pt-3 border-t border-slate-200">
                <button type="button" @click="prevLabelCrate" :disabled="activeLabelCrateIndex <= 0"
                  :class="activeLabelCrateIndex <= 0 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300 font-bold cursor-pointer shadow-xs'"
                  class="px-3.5 py-2 rounded-xl border text-xs flex items-center gap-2 transition min-h-[36px]">
                  <i class="fa-solid fa-chevron-left text-[10px]"></i>
                  <span>{{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} Sebelumnya</span>
                </button>
                <div class="text-xs font-bold text-slate-700 font-mono flex items-center gap-1.5">
                  <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                    {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} {{ (activeLabelCrateIndex + 1).toLocaleString('id-ID') }} dari {{ labelForm.crates.length.toLocaleString('id-ID') }}
                  </span>
                </div>
                <button type="button" @click="nextLabelCrate" :disabled="activeLabelCrateIndex >= labelForm.crates.length - 1"
                  :class="activeLabelCrateIndex >= labelForm.crates.length - 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200' : 'bg-emerald-600 text-white hover:bg-emerald-700 font-bold cursor-pointer shadow-xs'"
                  class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 transition min-h-[36px]">
                  <span>{{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} Berikutnya</span>
                  <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- KARTU 3: CATATAN & AKSI SIMPAN / CETAK -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Catatan Label (Opsional)</label>
              <input type="text" v-model="labelForm.notes" placeholder="Contoh: Pengiriman batch alutsista tahap 1"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]">
            </div>

            <!-- Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
              <div class="flex items-center gap-3">
                <button type="button" @click="confirmResetLabelForm"
                  class="text-xs font-bold text-slate-600 hover:text-red-700 flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto">
                  <i class="fa-solid fa-arrow-rotate-left"></i>
                  <span>Reset Form</span>
                </button>
                <div class="text-[11px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1.5"
                  :class="isLabelFormComplete ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'">
                  <i class="fa-solid"
                    :class="isLabelFormComplete ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-amber-600'"></i>
                  <span>{{ isLabelFormComplete ? 'Semua slot SN terisi lengkap (' + getLabelFormFilledCount() + '/' +
                    getLabelFormRequiredCount() + ')' : 'Slot SN belum lengkap: ' + getLabelFormFilledCount() + '/' +
                    getLabelFormRequiredCount() + ' terisi' }}</span>
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="saveStandaloneLabel(null)"
                  :disabled="isSavingStandaloneLabel || !isLabelFormComplete"
                  :title="!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat disimpan' : 'Simpan label ke database'"
                  class="bg-slate-100 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed text-slate-800 font-bold text-xs px-4 py-2.5 rounded-xl border border-slate-300 flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                  <i class="fa-solid fa-floppy-disk" :class="{'fa-spin': isSavingStandaloneLabel}"></i>
                  <span>Simpan Saja</span>
                </button>

                <button v-if="labelForm.package_type === 'crate'" type="button"
                  @click="saveStandaloneLabel('label_peti')" :disabled="isSavingStandaloneLabel || !isLabelFormComplete"
                  :title="!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat dicetak' : 'Simpan dan cetak label peti'"
                  class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-emerald-600/25 flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                  <i class="fa-solid fa-print"></i>
                  <span>Simpan &amp; Cetak Label Peti</span>
                </button>

                <button type="button"
                  @click="saveStandaloneLabel(labelForm.package_type === 'unit' ? 'label_sn' : 'label_dus')"
                  :disabled="isSavingStandaloneLabel || !isLabelFormComplete"
                  :title="!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat dicetak' : 'Simpan dan cetak label dus'"
                  class="bg-teal-600 hover:bg-teal-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-teal-600/25 flex items-center gap-2 transition cursor-pointer min-h-[42px]">
                  <i class="fa-solid fa-print"></i>
                  <span>Simpan &amp; Cetak Label Dus</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= SUB-TAB 1: FORM SURAT JALAN & PACKING LIST (STEPPER FORM) ================= -->
        <div v-if="doSubTab === 'form'" class="max-w-4xl mx-auto space-y-4 sm:space-y-5">

          <!-- KARTU 1: IDENTITAS PENERIMA (CUSTOMER) -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition"
                  :class="isFormCardOpen('customer') ? 'bg-red-600 text-white border-red-600' : (isStepCompleted('customer') ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-700 border-slate-200')">
                  <i v-if="!isFormCardOpen('customer') && isStepCompleted('customer')"
                    class="fa-solid fa-check text-[10px]"></i>
                  <span v-else>1</span>
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm text-slate-900 leading-tight">Data Penerima (Customer)</h3>
                  <span v-if="!isFormCardOpen('customer') && isStepCompleted('customer') && doForm.customer_name"
                    class="text-[11px] font-bold text-red-700 bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                    <i class="fa-solid fa-user-check text-[10px]"></i>
                    <span>{{ doForm.customer_name }}</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 1 -->
            <div v-show="isFormCardOpen('customer')" class="space-y-4 pt-1">
              <!-- Nama Customer Search Autocomplete Dropdown -->
              <div class="relative space-y-1.5" ref="customerDropdownRef">
                <label class="text-xs font-bold text-slate-700 flex items-center justify-between">
                  <span>Nama Customer / Instansi <span class="text-red-500">*</span></span>
                  <span class="text-[10px] text-slate-400 font-normal">Ketik untuk mencari di database</span>
                </label>
                <div class="relative">
                  <input type="text" v-model="customerSearchQuery" @focus="isCustomerDropdownOpen = true"
                    @input="isCustomerDropdownOpen = true; doForm.customer_name = customerSearchQuery"
                    placeholder="Pilih atau ketik nama customer (contoh: KEMHAN RI)..."
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:bg-white transition min-h-[42px]">
                  <i
                    class="fa-solid fa-building text-slate-400 absolute right-3.5 top-3.5 text-xs pointer-events-none"></i>
                </div>

                <!-- Autocomplete Dropdown Popover -->
                <div v-if="isCustomerDropdownOpen && filteredCustomersList.length > 0"
                  class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto divide-y divide-slate-100 animate-fadeIn">
                  <div v-for="cust in filteredCustomersList" :key="cust.id" @click="selectCustomer(cust)"
                    class="p-3 hover:bg-red-50/80 cursor-pointer transition flex items-start justify-between gap-2">
                    <div>
                      <div class="font-bold text-xs text-slate-800">{{ cust.name }}</div>
                      <div class="text-[11px] text-slate-500 line-clamp-1">{{ cust.address }}</div>
                    </div>
                    <span v-if="cust.phone" class="text-[10px] text-slate-400 font-mono flex-shrink-0">{{ cust.phone
                      }}</span>
                  </div>
                </div>
              </div>

              <!-- Alamat Lengkap -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">
                  Alamat Lengkap Pengiriman <span class="text-red-500">*</span>
                </label>
                <textarea v-model="doForm.customer_address" rows="3"
                  placeholder="Alamat lengkap tujuan pengiriman (termasuk Gedung, Jalan, Kota, Kode Pos)..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:bg-white transition leading-relaxed min-h-[95px] resize-y"></textarea>
              </div>

              <!-- No. Telepon & Fax -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">No. Telepon Penerima</label>
                  <input type="text" v-model="doForm.customer_phone" placeholder="Contoh: (021) 3840889"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]">
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">No. Fax Penerima</label>
                  <input type="text" v-model="doForm.customer_fax" placeholder="Contoh: (021) 3840889"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]">
                </div>
              </div>

              <!-- Aksi Lanjut Card 1 -->
              <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                <button type="button" @click="goToFormStep('reference')"
                  class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px]">
                  Lanjut
                </button>
              </div>
            </div>
          </div>

          <!-- KARTU 2: DOKUMEN REFERENSI & PROGRAM -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4"
            :class="!canAccessStep('reference') ? 'opacity-80' : ''">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition"
                  :class="isFormCardOpen('reference') ? 'bg-red-600 text-white border-red-600' : (isStepCompleted('reference') ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : (canAccessStep('reference') ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-slate-100 text-slate-300 border-slate-200'))">
                  <i v-if="!isFormCardOpen('reference') && isStepCompleted('reference')"
                    class="fa-solid fa-check text-[10px]"></i>
                  <span v-else>2</span>
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm leading-tight"
                    :class="canAccessStep('reference') ? 'text-slate-900' : 'text-slate-400'">
                    Dokumen Referensi &amp; Program
                  </h3>
                  <span v-if="!isFormCardOpen('reference') && isStepCompleted('reference') && doForm.contract_number"
                    class="text-[11px] font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                    <span>{{ doForm.contract_number }}</span>
                    <span class="text-slate-400 font-sans">•</span>
                    <span class="text-red-700 font-sans">{{ doForm.product_name }}</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 2 -->
            <div v-show="isFormCardOpen('reference')" class="space-y-4 pt-1">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- No. Surat Jalan (Editable / Auto Counter) -->
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Nomor Surat Jalan (DO) <span
                        class="text-red-500">*</span></label>
                    <span
                      class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                      <i class="fa-solid fa-wand-magic-sparkles text-[9px] mr-1"></i>Auto Counter / Bisa Edit
                    </span>
                  </div>
                  <div class="relative">
                    <input type="text" v-model="doForm.do_number"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px] pr-10"
                      title="Nomor Surat Jalan otomatis terhitung, dapat disesuaikan jika diperlukan">
                    <button type="button" @click="loadNextDONumber" title="Perbarui Nomor Surat Jalan Otomatis"
                      class="absolute right-2.5 top-2.5 p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer">
                      <i class="fa-solid fa-arrows-rotate text-xs"></i>
                    </button>
                  </div>
                </div>

                <!-- Tanggal DO (Unlocked & Flexible) -->
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Tanggal Surat Jalan <span
                        class="text-red-500">*</span></label>
                  </div>
                  <div class="relative">
                    <input type="date" v-model="doForm.do_date" @input="onDODateChanged" @change="onDODateChanged"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]"
                      title="Pilih tanggal pengiriman Surat Jalan (tidak terkunci)">
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nomor Kontrak -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Nomor Kontrak / Referensi <span
                      class="text-red-500">*</span></label>
                  <input type="text" v-model="doForm.contract_number"
                    placeholder="Contoh: TRAK/2509/APBN/XI/2025/KEMHAN"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]">
                </div>

                <!-- Program Pengadaan -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Program / Proyek Pengadaan <span
                      class="text-red-500">*</span></label>
                  <input type="text" v-model="doForm.program_name" placeholder="Contoh: PENGADAAN ALPALHANKAM MATSUSSUS"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]">
                </div>
              </div>

              <!-- Pilihan Produk -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700">Jenis Produk / Komoditas <span
                    class="text-red-500">*</span></label>
                <div class="relative">
                  <select v-model="doForm.product_name"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px] cursor-pointer">
                    <option v-for="p in productTypesList" :key="p" :value="p">{{ p }}</option>
                  </select>
                </div>
              </div>

              <!-- Identitas Pengeluar Dokumen -->
              <div class="bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 space-y-3">
                <div class="text-[11px] font-bold uppercase text-slate-600 flex items-center gap-1.5">
                  <i class="fa-solid fa-user-pen text-red-600"></i>
                  <span>Pejabat / Petugas Pengeluar (Yang Menyerahkan)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div class="space-y-1">
                    <label class="text-[11px] font-semibold text-slate-600">Nama Lengkap</label>
                    <input type="text" v-model="doForm.issued_by_name" placeholder="Nama pejabat"
                      class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 min-h-[38px]">
                  </div>
                  <div class="space-y-1">
                    <label class="text-[11px] font-semibold text-slate-600">Jabatan</label>
                    <input type="text" v-model="doForm.issued_by_title" placeholder="Kepala Departemen"
                      class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 min-h-[38px]">
                  </div>
                  <div class="space-y-1">
                    <label class="text-[11px] font-semibold text-slate-600">Departemen</label>
                    <input type="text" v-model="doForm.issued_by_dept" placeholder="Logistik & PPIC"
                      class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 min-h-[38px]">
                  </div>
                </div>
              </div>

              <!-- Aksi Kembali & Lanjut Card 2 -->
              <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                <button type="button" @click="goToFormStep('customer')"
                  class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition cursor-pointer min-h-[42px]">
                  Kembali
                </button>
                <button type="button" @click="goToFormStep('batch')"
                  class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px]">
                  Lanjut
                </button>
              </div>
            </div>
          </div>

          <!-- KARTU 3: KONSEP PENGEMASAN & JUMLAH PENGIRIMAN -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4"
            :class="!canAccessStep('batch') ? 'opacity-80' : ''">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition"
                  :class="isFormCardOpen('batch') ? 'bg-red-600 text-white border-red-600' : (isStepCompleted('batch') ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : (canAccessStep('batch') ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-slate-100 text-slate-300 border-slate-200'))">
                  <i v-if="!isFormCardOpen('batch') && isStepCompleted('batch')"
                    class="fa-solid fa-check text-[10px]"></i>
                  <span v-else>3</span>
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm leading-tight"
                    :class="canAccessStep('batch') ? 'text-slate-900' : 'text-slate-400'">
                    Konsep Pengemasan &amp; Jumlah Pengiriman
                  </h3>
                  <span v-if="!isFormCardOpen('batch') && isStepCompleted('batch')"
                    class="text-[11px] font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                    <span>{{ doForm.total_quantity }} Unit</span>
                    <span class="text-slate-300">•</span>
                    <span>Batch {{ doForm.batch_number }}</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 3 -->
            <div v-show="isFormCardOpen('batch')" class="space-y-4 pt-1">
              <!-- Jenis Pengemasan (3 Pilihan: Per Peti, Per Dus, Satuan) -->
              <div class="space-y-2">
                <label class="text-xs font-bold text-slate-700 block">Pilih Tipe Kemasan Pengiriman <span
                    class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <!-- Tipe 1: Per Peti -->
                  <div @click="setPackageType('crate')"
                    :class="doForm.package_type === 'crate' ? 'border-red-600 bg-red-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                    class="p-3.5 rounded-2xl border-2 cursor-pointer transition flex items-start space-x-3 select-none">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                      :class="doForm.package_type === 'crate' ? 'bg-red-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-500'">
                      <i class="fa-solid fa-boxes-stacked text-sm"></i>
                    </div>
                    <div>
                      <div class="font-black text-xs text-slate-900">Per Peti</div>
                      <div class="text-[11px] text-slate-500 mt-0.5">2 Dus @ 10 SN = 20 SN per Peti</div>
                      <span class="inline-block mt-1 text-[10px] font-bold text-red-700 font-mono">1 Peti = 20
                        Unit</span>
                    </div>
                  </div>

                  <!-- Tipe 2: Per Dus -->
                  <div @click="setPackageType('box')"
                    :class="doForm.package_type === 'box' ? 'border-indigo-600 bg-indigo-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                    class="p-3.5 rounded-2xl border-2 cursor-pointer transition flex items-start space-x-3 select-none">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                      :class="doForm.package_type === 'box' ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-500'">
                      <i class="fa-solid fa-box text-sm"></i>
                    </div>
                    <div>
                      <div class="font-black text-xs text-slate-900">Per Dus</div>
                      <div class="text-[11px] text-slate-500 mt-0.5">Langsung Dus tanpa Peti</div>
                      <span class="inline-block mt-1 text-[10px] font-bold text-indigo-700 font-mono">1 Dus = 10
                        Unit</span>
                    </div>
                  </div>

                  <!-- Tipe 3: Satuan -->
                  <div @click="setPackageType('unit')"
                    :class="doForm.package_type === 'unit' ? 'border-emerald-600 bg-emerald-50/50 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                    class="p-3.5 rounded-2xl border-2 cursor-pointer transition flex items-start space-x-3 select-none">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                      :class="doForm.package_type === 'unit' ? 'bg-emerald-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-500'">
                      <i class="fa-solid fa-cube text-sm"></i>
                    </div>
                    <div>
                      <div class="font-black text-xs text-slate-900">Satuan Unit</div>
                      <div class="text-[11px] text-slate-500 mt-0.5">Tanpa Peti &amp; Dus</div>
                      <span class="inline-block mt-1 text-[10px] font-bold text-emerald-700 font-mono">1 SN = 1
                        Unit</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Jumlah Kemasan & Nomor Batch -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Input Jumlah Kemasan -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">
                    <span v-if="doForm.package_type === 'crate'">Jumlah Peti</span>
                    <span v-else-if="doForm.package_type === 'box'">Jumlah Dus</span>
                    <span v-else>Jumlah Unit (Otomatis)</span>
                    <span class="text-red-500">*</span>
                  </label>
                  <input type="number" min="1" max="500" v-model.number="doForm.package_count"
                    @input="syncPackagingStructure" :disabled="doForm.package_type === 'unit'"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-black text-slate-900 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]"
                    :class="doForm.package_type === 'unit' ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''">
                  <p v-if="doForm.package_type === 'unit'" class="text-[10px] text-emerald-600 font-bold">
                    <i class="fa-solid fa-circle-check mr-1"></i> Bertambah otomatis saat input SN
                  </p>
                </div>

                <!-- Total Kuantitas Unit (Dihitung Otomatis) -->
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Total Kuantitas Pengiriman</label>
                  <div
                    class="flex items-center bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 min-h-[42px]">
                    <span class="text-sm font-black font-mono text-emerald-700">{{ doForm.total_quantity }}</span>
                    <span class="text-xs font-bold text-slate-500 ml-1.5">Unit Produk P9</span>
                  </div>
                </div>

                <!-- Batch Pengiriman -->
                <div class="space-y-1.5">
                  <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Batch Pengiriman Ke-</label>
                    <button type="button"
                      @click="doForm.batch_number = 1; syncPackagingStructure(); triggerToast('Batch Direset', 'Nomor batch berhasil diset ke 1.', 'info')"
                      class="text-[10px] text-red-600 hover:text-red-700 font-bold hover:underline cursor-pointer flex items-center gap-1 active:scale-95 transition"
                      title="Set nomor batch ke 1">
                      <i class="fa-solid fa-rotate-left text-[9px]"></i> Set ke 1
                    </button>
                  </div>
                  <input type="number" min="1" v-model.number="doForm.batch_number" @input="syncPackagingStructure"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:bg-white min-h-[42px]">
                </div>
              </div>

              <!-- Preview Format Pengemasan Ringkas -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs space-y-1.5 font-mono">
                <div class="text-[10px] font-sans font-bold uppercase text-slate-500">Ringkasan Format Kemasan:</div>
                <div class="flex items-center gap-3 flex-wrap font-bold text-slate-800 text-[11px]">
                  <span>Kemasan: {{ doForm.package_type === 'box' ? 'Per Dus' : (doForm.package_type === 'unit' ?
                    'Satuan' : 'Per Peti') }}</span>
                  <span>•</span>
                  <span>Total: {{ doForm.total_quantity }} Unit</span>
                  <span>•</span>
                  <span v-if="doForm.package_type === 'crate'">Format: No. Peti ({{ doForm.crates[0]?.crate_id || '-'
                    }})</span>
                  <span v-else-if="doForm.package_type === 'box'">Format: No. Dus ({{ doForm.crates[0]?.box_id ||
                    doForm.crates[0]?.crate_id || '-'
                    }})</span>
                  <span v-else>Format: No. Serial Unit (SN)</span>
                </div>
              </div>

              <!-- Aksi Kembali & Lanjut Card 3 -->
              <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                <button type="button" @click="goToFormStep('reference')"
                  class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition cursor-pointer min-h-[42px]">
                  Kembali
                </button>
                <button type="button" @click="goToFormStep('allocation')"
                  class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px]">
                  Lanjut
                </button>
              </div>
            </div>
          </div>

          <!-- KARTU 4: ALOKASI SERIAL NUMBER -->
          <div class="space-y-4" :class="!canAccessStep('allocation') ? 'opacity-80' : ''">
            <div
              class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3 select-none">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition"
                  :class="isFormCardOpen('allocation') ? 'bg-red-600 text-white border-red-600' : (isStepCompleted('allocation') ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : (canAccessStep('allocation') ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-slate-100 text-slate-300 border-slate-200'))">
                  <i v-if="!isFormCardOpen('allocation') && isStepCompleted('allocation')"
                    class="fa-solid fa-check text-[10px]"></i>
                  <span v-else>4</span>
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm leading-tight"
                    :class="canAccessStep('allocation') ? 'text-slate-900' : 'text-slate-400'">
                    Alokasi Serial Number
                  </h3>
                  <span v-if="canAccessStep('allocation')"
                    class="text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-lg border flex items-center gap-1"
                    :class="getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-700 bg-slate-100 border-slate-200'">
                    <i v-if="getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0"
                      class="fa-solid fa-check-double text-emerald-600"></i>
                    <span>{{ getTotalFilledSNs() }} / {{ doForm.total_quantity }} Terisi</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 4: Pengisian Satu per Satu Berurutan (Bebas Scroll Panjang) -->
            <div v-show="isFormCardOpen('allocation')" class="space-y-4">

              <!-- Pilihan Menggunakan Packing List yang Sudah Dibuat (Konsumsi PL ke DO) -->
              <div v-if="doForm.package_type !== 'unit'"
                class="bg-gradient-to-r from-blue-50/90 via-indigo-50/80 to-slate-50 border border-blue-200 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs flex-shrink-0">
                      <i class="fa-solid fa-file-invoice text-sm"></i>
                    </div>
                    <div>
                      <div class="text-xs font-black text-slate-800 flex items-center gap-2">
                        <span>Gunakan Packing List yang Sudah Ada</span>
                        <span v-if="selectedConsumedPL"
                          class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                          <i class="fa-solid fa-check"></i> Tersambung
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 mt-0.5">
                        Pilih Packing List mandiri yang telah dibuat untuk mengisi kemasan &amp; daftar Serial Number
                        unit secara otomatis.
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="relative min-w-[240px] sm:min-w-[280px]">
                      <select v-model="selectedConsumedPLId" @change="applyConsumedPackingList"
                        :disabled="isLoadingConsumedPL"
                        class="w-full text-xs font-bold bg-white text-slate-800 border border-blue-300 rounded-xl px-3 py-2 pr-8 focus:ring-2 focus:ring-blue-500 outline-none shadow-2xs cursor-pointer disabled:bg-slate-100 disabled:cursor-not-allowed">
                        <option value="">-- Pilih Packing List Mandiri --</option>
                        <option v-for="pl in standaloneLabelsList" :key="pl.id" :value="pl.id">
                          {{ pl.pl_number || pl.label_batch_id }} &bull; {{ pl.total_quantity }} Unit ({{
                          pl.package_type === 'crate' ? pl.total_containers + ' Peti' : (pl.package_type === 'box' ?
                          pl.total_containers + ' Dus' : 'Satuan') }})
                        </option>
                      </select>
                      <i v-if="isLoadingConsumedPL"
                        class="fa-solid fa-circle-notch fa-spin absolute right-2.5 top-2.5 text-blue-600 text-xs"></i>
                    </div>
                    <button v-if="selectedConsumedPLId" type="button" @click="resetConsumedPackingList"
                      class="px-3 py-2 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl border border-red-200 transition flex items-center gap-1 flex-shrink-0 cursor-pointer"
                      title="Batalkan penggunaan Packing List ini dan reset form SN">
                      <i class="fa-solid fa-xmark"></i>
                      <span>Reset</span>
                    </button>
                  </div>
                </div>

                <!-- Banner Informasi ketika Terhubung -->
                <div v-if="selectedConsumedPL"
                  class="mt-3 pt-3 border-t border-blue-200/60 flex flex-wrap items-center justify-between gap-2 text-xs text-blue-900">
                  <div class="flex items-center gap-2 font-medium">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>Tersambung ke <strong>{{ selectedConsumedPL.pl_number || selectedConsumedPL.label_batch_id
                        }}</strong>: <strong>{{ selectedConsumedPL.total_quantity }} Unit</strong> telah dialokasikan ke
                      kemasan.</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-lg bg-blue-100 text-blue-800 font-mono">
                      {{ selectedConsumedPL.package_type === 'crate' ? 'Kemasan Per Peti' :
                      (selectedConsumedPL.package_type === 'box' ? 'Kemasan Per Dus' : 'Kemasan Satuan') }}
                    </span>
                    <span class="text-[11px] text-slate-500">Batch #{{ selectedConsumedPL.batch_number || 1 }}</span>
                  </div>
                </div>
              </div>

              <!-- Stepper Pills Navigasi Antar Peti / Dus (Kemasan Satuan mengalir otomatis ke bawah seperti Gudang) -->
              <div
                v-if="(doForm.package_type === 'box' && getAllBoxesInForm().length > 1) || (doForm.package_type === 'crate' && doForm.crates.length > 1)"
                class="bg-white border border-slate-200 rounded-2xl p-2.5 sm:p-3 shadow-xs">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 mb-2">
                  <span class="text-xs font-bold text-slate-700">
                    <span v-if="doForm.package_type === 'crate'">Pilih Peti:</span>
                    <span v-else>Pilih Dus:</span>
                  </span>
                  <span class="text-[11px] text-slate-500 font-medium">Isi satu per satu secara berurutan</span>
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar select-none">
                  <!-- Stepper Dus -->
                  <template v-if="doForm.package_type === 'box'">
                    <button v-for="(b, bIdx) in getAllBoxesInForm()" :key="b.box_id" type="button"
                      @click="selectContainer(bIdx)"
                      :class="activeContainerIndex === bIdx
                        ? 'bg-indigo-600 text-white shadow-xs font-black ring-2 ring-indigo-300'
                        : (isBoxComplete(b)
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold hover:bg-emerald-100 cursor-pointer'
                          : (isBoxIndexLocked(bIdx)
                            ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                            : 'bg-white text-slate-700 border border-slate-300 font-bold hover:bg-slate-50 cursor-pointer'))"
                      class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 flex-shrink-0 transition min-h-[36px]">
                      <i v-if="isBoxComplete(b)" class="fa-solid fa-check text-[10px]"
                        :class="activeContainerIndex === bIdx ? 'text-white' : 'text-emerald-600'"></i>
                      <i v-else-if="isBoxIndexLocked(bIdx)" class="fa-solid fa-lock text-[10px] text-slate-400"></i>
                      <i v-else class="fa-solid fa-box text-[10px]"></i>
                      <span>Dus {{ bIdx + 1 }}</span>
                      <span class="text-[10px] opacity-80">({{ getBoxFilledCount(b) }}/10)</span>
                    </button>
                  </template>

                  <!-- Stepper Peti -->
                  <template v-else-if="doForm.package_type === 'crate'">
                    <button v-for="(c, cIdx) in doForm.crates" :key="c.crate_id" type="button"
                      @click="selectContainer(cIdx)"
                      :class="activeContainerIndex === cIdx
                        ? 'bg-red-600 text-white shadow-xs font-black ring-2 ring-red-300'
                        : (isCrateComplete(c)
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold hover:bg-emerald-100 cursor-pointer'
                          : (isCrateIndexLocked(cIdx)
                            ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60'
                            : 'bg-white text-slate-700 border border-slate-300 font-bold hover:bg-slate-50 cursor-pointer'))"
                      class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 flex-shrink-0 transition min-h-[36px]">
                      <i v-if="isCrateComplete(c)" class="fa-solid fa-check text-[10px]"
                        :class="activeContainerIndex === cIdx ? 'text-white' : 'text-emerald-600'"></i>
                      <i v-else-if="isCrateIndexLocked(cIdx)" class="fa-solid fa-lock text-[10px] text-slate-400"></i>
                      <i v-else class="fa-solid fa-boxes-stacked text-[10px]"></i>
                      <span>Peti {{ cIdx + 1 }}</span>
                      <span class="text-[10px] opacity-80">({{ getCrateFilledCount(c) }}/20)</span>
                    </button>
                  </template>
                </div>
              </div>

              <!-- 1. TAMPILAN PER PETI (Hanya 1 Peti Aktif) -->
              <template v-if="doForm.package_type === 'crate' && doForm.crates[activeContainerIndex]">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                  <div
                    class="bg-slate-50/80 border-b border-slate-200 p-3.5 sm:p-4 flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-red-600 flex items-center justify-center shadow-2xs font-black">
                        <i class="fa-solid fa-boxes-stacked text-sm"></i>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="font-black font-mono text-sm text-slate-900">ID PETI: {{
                            doForm.crates[activeContainerIndex].crate_id }}</span>
                          <span class="text-xs text-slate-500">(Peti {{ activeContainerIndex + 1 }} dari {{
                            doForm.crates.length }})</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded border font-mono mt-0.5 inline-block"
                          :class="getCrateFilledCount(doForm.crates[activeContainerIndex]) === 20 ? 'text-emerald-700 bg-emerald-50 border-emerald-200 font-bold' : 'text-slate-600 bg-white border-slate-200'">
                          {{ getCrateFilledCount(doForm.crates[activeContainerIndex]) }} / 20 SN Terisi
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <button type="button" @click="openSelectCrateModal(activeContainerIndex)"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <i class="fa-solid fa-boxes-stacked text-[11px]"></i>
                        <span>Pilih ID Peti</span>
                      </button>
                      <button type="button" @click="openBatchPasteModal(activeContainerIndex, 1, false, 'crate')"
                        class="bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        title="Tempel data Serial Number secara bulking ke Peti ini">
                        <i class="fa-solid fa-paste text-[10px]"></i>
                        <span>Bulking SN Peti</span>
                      </button>
                    </div>
                  </div>

                  <div class="p-3 sm:p-4 space-y-4">
                    <div v-for="box in doForm.crates[activeContainerIndex].boxes" :key="box.box_id"
                      class="bg-slate-50/70 border border-slate-200 rounded-xl p-3 sm:p-3.5 space-y-2.5">
                      <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div class="flex items-center gap-2">
                          <i class="fa-solid fa-box text-indigo-600 text-xs"></i>
                          <span class="font-black text-xs text-slate-900 font-mono">ID DUS: {{ box.box_id }}</span>
                          <span class="text-[11px] font-bold text-slate-500">(Dus {{ box.box_number }} — 10 SN)</span>
                          <span v-if="box.consumed_source_box_id && box.consumed_source_box_id !== box.box_id"
                            class="text-[10px] font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                            <i class="fa-solid fa-arrow-right-arrow-left text-[9px]"></i>
                            Dari Dus {{ box.consumed_source_box_id }}
                          </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                          <button type="button" @click="openSelectBoxModal(activeContainerIndex, box.box_number, false)"
                            class="text-indigo-700 hover:text-indigo-900 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200 px-2 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                            <i class="fa-solid fa-box-open text-[10px]"></i>
                            <span>Pilih ID Dus</span>
                          </button>
                        </div>
                      </div>

                      <!-- 10 Slot dengan Sequential Locking -->
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div v-for="(item, sIdx) in box.items" :key="item.slot_number"
                          class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 transition border shadow-2xs"
                          :class="isDOSlotLocked(box.items, sIdx) ? 'bg-slate-100 border-slate-200 opacity-60' : 'bg-white border-slate-300 focus-within:border-red-600'">
                          <span class="w-5 text-right font-mono font-bold text-xs select-none"
                            :class="isDOSlotLocked(box.items, sIdx) ? 'text-slate-400' : 'text-slate-600'">
                            {{ item.slot_number }}.
                          </span>

                          <select v-model="item.serial_number" :disabled="isDOSlotLocked(box.items, sIdx)"
                            class="w-full bg-transparent border-none focus:outline-none text-xs font-mono font-bold py-1.5 min-h-[36px]"
                            :class="isDOSlotLocked(box.items, sIdx) ? 'text-slate-400 cursor-not-allowed' : 'text-slate-900 cursor-pointer'">
                            <option v-if="isDOSlotLocked(box.items, sIdx)" value="">Terkunci</option>
                            <option v-else value="">Pilih SN</option>
                            <option v-if="item.serial_number && !isSNInList(item.serial_number)"
                              :value="item.serial_number">
                              {{ item.serial_number }}
                            </option>
                            <option v-if="item.serial_number && isSNInList(item.serial_number)"
                              :value="item.serial_number">
                              {{ item.serial_number }}
                            </option>
                            <option v-for="sn in getAvailableSNsForSlot(item.serial_number)" :key="sn" :value="sn">
                              {{ sn }}
                            </option>
                          </select>

                          <button v-if="item.serial_number && !isDOSlotLocked(box.items, sIdx)" type="button"
                            @click="item.serial_number = ''"
                            class="text-slate-400 hover:text-red-600 p-1 text-xs cursor-pointer transition">
                            <i class="fa-solid fa-xmark"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Navigasi Stepper Antar Peti -->
                    <div v-if="doForm.crates.length > 1"
                      class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                      <button type="button" @click="prevContainer" :disabled="activeContainerIndex === 0"
                        :class="activeContainerIndex === 0 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 cursor-pointer shadow-2xs'"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center min-h-[38px]">
                        Peti Sebelumnya
                      </button>

                      <span class="text-xs font-mono font-bold text-slate-600">
                        Peti {{ activeContainerIndex + 1 }} dari {{ doForm.crates.length }}
                      </span>

                      <button v-if="activeContainerIndex < doForm.crates.length - 1" type="button"
                        @click="nextContainer"
                        :class="isCrateComplete(doForm.crates[activeContainerIndex])
                          ? 'bg-red-600 hover:bg-red-700 text-white shadow-xs font-black cursor-pointer'
                          : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-75 font-bold'"
                        class="w-full sm:w-auto px-5 py-2 rounded-xl text-xs transition flex items-center justify-center min-h-[38px]">
                        <span>Lanjut ke Peti {{ activeContainerIndex + 2 }}</span>
                      </button>
                      <div v-else
                        class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>Seluruh Peti Selesai Diisi</span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <!-- 2. TAMPILAN PER DUS (Hanya 1 Dus Aktif) -->
              <template v-else-if="doForm.package_type === 'box' && getAllBoxesInForm()[activeContainerIndex]">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
                  <div
                    class="bg-slate-50/80 border-b border-slate-200 p-3.5 sm:p-4 flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-indigo-600 flex items-center justify-center shadow-2xs font-black">
                        <i class="fa-solid fa-box text-sm"></i>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="font-black font-mono text-sm text-slate-900">ID DUS: {{
                            getAllBoxesInForm()[activeContainerIndex].box_id }}</span>
                          <span class="text-xs text-slate-500">(Dus {{ activeContainerIndex + 1 }} dari {{
                            getAllBoxesInForm().length }})</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded border font-mono mt-0.5 inline-block"
                          :class="isBoxComplete(getAllBoxesInForm()[activeContainerIndex]) ? 'text-emerald-700 bg-emerald-50 border-emerald-200 font-bold' : 'text-slate-600 bg-white border-slate-200'">
                          {{ getBoxFilledCount(getAllBoxesInForm()[activeContainerIndex]) }} / 10 SN Terisi
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <button type="button" @click="openSelectBoxModal(activeContainerIndex, 1, true)"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <i class="fa-solid fa-box-open text-[11px]"></i>
                        <span>Pilih ID Dus</span>
                      </button>
                      <button type="button" @click="openBatchPasteModal(0, activeContainerIndex + 1, false, 'box')"
                        class="bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        title="Tempel data Serial Number secara bulking ke Dus ini">
                        <i class="fa-solid fa-paste text-[10px]"></i>
                        <span>Bulking SN Dus</span>
                      </button>
                    </div>
                  </div>

                  <!-- 10 Slot dengan Sequential Locking -->
                  <div class="p-3 sm:p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <div v-for="(item, sIdx) in getAllBoxesInForm()[activeContainerIndex].items"
                        :key="item.slot_number"
                        class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 transition border shadow-2xs"
                        :class="isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx) ? 'bg-slate-100 border-slate-200 opacity-60' : 'bg-white border-slate-300 focus-within:border-indigo-600'">
                        <span class="w-5 text-right font-mono font-bold text-xs select-none"
                          :class="isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx) ? 'text-slate-400' : 'text-slate-600'">
                          {{ item.slot_number }}.
                        </span>

                        <select v-model="item.serial_number"
                          :disabled="isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx)"
                          class="w-full bg-transparent border-none focus:outline-none text-xs font-mono font-bold py-1.5 min-h-[36px]"
                          :class="isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx) ? 'text-slate-400 cursor-not-allowed' : 'text-slate-900 cursor-pointer'">
                          <option v-if="isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx)" value="">
                            Terkunci</option>
                          <option v-else value="">Pilih SN</option>
                          <option v-if="item.serial_number && !isSNInList(item.serial_number)"
                            :value="item.serial_number">
                            {{ item.serial_number }}
                          </option>
                          <option v-if="item.serial_number && isSNInList(item.serial_number)"
                            :value="item.serial_number">
                            {{ item.serial_number }}
                          </option>
                          <option v-for="sn in getAvailableSNsForSlot(item.serial_number)" :key="sn" :value="sn">
                            {{ sn }}
                          </option>
                        </select>

                        <button
                          v-if="item.serial_number && !isDOSlotLocked(getAllBoxesInForm()[activeContainerIndex].items, sIdx)"
                          type="button" @click="item.serial_number = ''"
                          class="text-slate-400 hover:text-red-600 p-1 text-xs cursor-pointer transition">
                          <i class="fa-solid fa-xmark"></i>
                        </button>
                      </div>
                    </div>

                    <!-- Navigasi Stepper Antar Dus -->
                    <div v-if="getAllBoxesInForm().length > 1"
                      class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2.5">
                      <button type="button" @click="prevContainer" :disabled="activeContainerIndex === 0"
                        :class="activeContainerIndex === 0 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border-slate-200' : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-300 cursor-pointer shadow-2xs'"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl text-xs font-bold border transition flex items-center justify-center min-h-[38px]">
                        Dus Sebelumnya
                      </button>

                      <span class="text-xs font-mono font-bold text-slate-600">
                        Dus {{ activeContainerIndex + 1 }} dari {{ getAllBoxesInForm().length }}
                      </span>

                      <button v-if="activeContainerIndex < getAllBoxesInForm().length - 1" type="button"
                        @click="nextContainer"
                        :class="isBoxComplete(getAllBoxesInForm()[activeContainerIndex])
                          ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs font-black cursor-pointer'
                          : 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-75 font-bold'"
                        class="w-full sm:w-auto px-5 py-2 rounded-xl text-xs transition flex items-center justify-center min-h-[38px]">
                        <span>Lanjut ke Dus {{ activeContainerIndex + 2 }}</span>
                      </button>
                      <div v-else
                        class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>Seluruh Dus Selesai Diisi</span>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <!-- 3. TAMPILAN SATUAN (Input Bertambah Otomatis Tanpa Ribet Tombol - Setting Identik Input Part) -->
              <template v-else-if="doForm.package_type === 'unit'">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-visible relative">
                  <!-- Header Alokasi Satuan -->
                  <div
                    class="bg-slate-50/80 border-b border-slate-200 p-3.5 sm:p-4 rounded-t-2xl flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-emerald-600 flex items-center justify-center shadow-2xs font-black">
                        <i class="fa-solid fa-cube text-sm"></i>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <span class="font-black text-sm text-slate-900">Alokasi Serial Number Satuan</span>
                          <span class="text-xs text-slate-500 font-mono font-bold">({{ getAllUnitsInForm().length }}
                            Unit)</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded border font-mono mt-0.5 inline-block"
                          :class="getTotalFilledSNs() > 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200 font-bold' : 'text-slate-600 bg-white border-slate-200'">
                          {{ getTotalFilledSNs() }} / {{ getAllUnitsInForm().length }} SN Terisi
                        </span>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                      <button type="button" @click="addUnitToDOForm(1)"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1 cursor-pointer shadow-2xs"
                        title="Tambah baris input SN baru">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Tambah Baris</span>
                      </button>
                      <button type="button" @click="openBatchPasteModal(0, 1, true)"
                        class="bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                        title="Input data Serial Number satuan secara bulking">
                        <i class="fa-solid fa-paste text-[10px]"></i>
                        <span>Bulking SN Satuan</span>
                      </button>
                    </div>
                  </div>

                  <!-- Petunjuk Input Serial Number (Sama Seperti di Gudang) -->
                  <div
                    class="bg-emerald-50/60 border-b border-emerald-100 px-3.5 py-2 text-xs text-emerald-900 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                      <i class="fa-solid fa-keyboard text-emerald-600 text-sm"></i>
                      <span>Ketik atau pilih <strong>Serial Number</strong> unit. Tekan <strong>Enter</strong> untuk
                        menambah baris otomatis.</span>
                    </div>
                    <span
                      class="text-[10px] text-emerald-700 font-bold hidden sm:inline-block bg-emerald-100/60 px-2 py-0.5 rounded">Auto-Append
                      Row</span>
                  </div>

                  <!-- List Text Field Input Satuan (Tampilan Bebas Overflow Tanpa Terpotong) -->
                  <div class="p-3 sm:p-4 space-y-2.5 overflow-visible relative">
                    <div v-for="(item, uIdx) in getAllUnitsInForm()" :key="uIdx"
                      class="flex items-center gap-2.5 p-2 sm:px-3 sm:py-2 rounded-xl border transition-all overflow-visible"
                      :class="[
                        item.serial_number ? 'bg-emerald-50/25 border-emerald-300 ring-1 ring-emerald-200/50' : 'bg-white border-slate-300 focus-within:border-red-600 focus-within:ring-2 focus-within:ring-red-100',
                        unitDropdown.isOpen && unitDropdown.idx === uIdx ? 'relative z-[9999]' : 'relative z-10'
                      ]">

                      <!-- Nomor Unit -->
                      <span class="w-14 font-mono font-bold text-xs text-slate-600 select-none flex-shrink-0">
                        Unit #{{ uIdx + 1 }}.
                      </span>

                      <!-- Text Input dengan Dropdown Rekomendasi SN Stok -->
                      <div class="relative flex-1 overflow-visible"
                        :class="unitDropdown.isOpen && unitDropdown.idx === uIdx ? 'z-[9999]' : ''">
                        <input type="text" :id="'unit-sn-input-' + uIdx" v-model="item.serial_number"
                          @focus="onUnitInputFocus(uIdx)" @input="onUnitInputChange(uIdx)"
                          @keydown="handleUnitInputKeyDown($event, uIdx)" @blur="onUnitInputBlur(uIdx)"
                          placeholder="Cari / Pilih SN Unit..." autocomplete="off"
                          class="w-full bg-white border border-slate-200 focus:border-red-600 focus:ring-1 focus:ring-red-100 rounded-lg px-3 py-1.5 text-xs font-mono font-bold text-slate-900 placeholder-slate-400 focus:outline-none min-h-[36px]">

                        <!-- Popover Dropdown Rekomendasi SN yang Tersedia (Identik dengan Setting Input Part) -->
                        <div
                          v-if="unitDropdown.isOpen && unitDropdown.idx === uIdx && getFilteredUnitSuggestions(item.serial_number).length"
                          class="absolute left-0 right-0 top-full mt-1.5 bg-white border-2 border-red-600 rounded-xl shadow-2xl z-[99999] max-h-[250px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 ring-4 ring-red-500/20"
                          style="-webkit-overflow-scrolling: touch; touch-action: pan-y;">
                          <div
                            class="px-2.5 py-1 text-[10px] font-bold text-red-700 uppercase tracking-wider flex justify-between bg-red-50 sticky top-0 z-10 border-b border-red-200">
                            <span><i class="fa-solid fa-list-check mr-1"></i> Rekomendasi Stok ({{
                              getFilteredUnitSuggestions(item.serial_number).length }} item)</span>
                            <span>Ketuk / Enter untuk memilih</span>
                          </div>
                          <button v-for="sn in getFilteredUnitSuggestions(item.serial_number)" :key="sn" type="button"
                            @mousedown.prevent="selectUnitSN(uIdx, sn)" @click="selectUnitSN(uIdx, sn)"
                            class="w-full text-left px-3 py-2.5 hover:bg-red-50 hover:text-red-700 text-xs font-mono font-bold flex items-center justify-between text-slate-800 transition cursor-pointer select-none">
                            <span>{{ sn }}</span>
                            <span
                              class="text-[10px] text-emerald-600 font-sans font-bold bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded pointer-events-none">TERSEDIA</span>
                          </button>
                        </div>
                      </div>

                      <!-- Badge SN Terisi -->
                      <span v-if="item.serial_number"
                        class="text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 px-2 py-1 rounded-md border border-emerald-200 flex-shrink-0 flex items-center gap-1">
                        <i class="fa-solid fa-check text-emerald-600"></i>
                        <span>OK</span>
                      </span>

                      <!-- Tombol Kosongkan SN -->
                      <button v-if="item.serial_number" type="button" @click="item.serial_number = ''"
                        class="text-slate-400 hover:text-amber-600 p-1.5 text-xs cursor-pointer transition flex-shrink-0"
                        title="Kosongkan Serial Number">
                        <i class="fa-solid fa-eraser"></i>
                      </button>

                      <!-- Tombol Hapus Baris -->
                      <button v-if="getAllUnitsInForm().length > 1" type="button" @click="removeUnitRowDirectly(uIdx)"
                        class="text-slate-300 hover:text-red-600 p-1.5 text-xs cursor-pointer transition flex-shrink-0"
                        title="Hapus baris unit ini">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </div>

                    <!-- Tombol Tambah Baris Manual (Mirip Penginputan WO di Form WO) -->
                    <div class="pt-1">
                      <button type="button" @click="addUnitToDOForm(1)"
                        class="w-full py-2.5 px-3 bg-slate-50 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 border-2 border-dashed border-slate-300 hover:border-emerald-400 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-2xs group">
                        <i class="fa-solid fa-plus text-slate-400 group-hover:text-emerald-600"></i>
                        <span>Tambah Baris Unit (atau Tekan Enter di baris terakhir)</span>
                      </button>
                    </div>
                  </div>

                  <!-- Footer Card -->
                  <div
                    class="bg-slate-50 border-t border-slate-200 px-3.5 py-2.5 rounded-b-2xl text-[11px] text-slate-500 italic flex items-center justify-between">
                    <span>Pengiriman satuan tidak dikemas ke dalam dus atau peti. Baris baru otomatis muncul setelah
                      baris terakhir diisi.</span>
                    <span class="font-mono font-bold text-slate-600 not-italic">Total: {{ getAllUnitsInForm().length }}
                      Unit</span>
                  </div>
                </div>
              </template>

              <!-- Aksi Kembali & Terbitkan DO di Card 4 -->
              <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                  <button type="button" @click="goToFormStep('batch')"
                    class="w-full sm:w-auto border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs px-6 py-3 rounded-xl transition flex items-center justify-center cursor-pointer min-h-[46px]">
                    Kembali
                  </button>
                  <button type="button" @click="submitDeliveryOrder"
                    :disabled="isSubmittingDO || getTotalFilledSNs() !== doForm.total_quantity" :class="getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0
                      ? 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white cursor-pointer shadow-md shadow-red-600/25'
                      : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                    class="w-full sm:flex-1 font-black text-xs sm:text-sm py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition min-h-[46px]">
                    <i v-if="!isSubmittingDO" class="fa-solid fa-paper-plane"></i>
                    <i v-else class="fa-solid fa-spinner fa-spin"></i>
                    <span>{{ isSubmittingDO ? 'Memproses Penerbitan Dokumen...' : 'Simpan &amp; Terbitkan Delivery Order' }}</span>
                  </button>
                </div>
                <p class="text-center text-[11px] text-slate-400 font-medium">
                  Dokumen Surat Jalan, Packing List, dan Label Peti/Dus akan otomatis dibuat dan siap dicetak.
                </p>
              </div>

            </div>
          </div>

        </div>

        <!-- ================= SUB-TAB 2: RIWAYAT & DOKUMEN DELIVERY ORDER TERBIT ================= -->
        <div v-if="doSubTab === 'history'" class="space-y-4">

          <!-- Search & Filter Bar -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3.5">
            <!-- Filter Grid: Search, Lacak SN, Doc DO, Urutan, Tanggal Mulai, Tanggal Selesai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
              <!-- Search query umum -->
              <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Data DO:
                </label>
                <div class="relative">
                  <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                  <input type="text" v-model="doSearchText" placeholder="Cari nomor SJ, customer, kontrak..."
                    class="w-full bg-slate-50 border border-slate-300 focus:border-red-600 focus:bg-white rounded-xl pl-8 pr-3 py-2 text-xs font-semibold text-slate-800 transition min-h-[38px]">
                </div>
              </div>

              <!-- Lacak Serial Number Part 15 Langsung -->
              <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-emerald-700 mb-1 flex items-center justify-between">
                  <span><i class="fa-solid fa-barcode mr-1 text-emerald-600"></i> Lacak SN Part 15 (Tracking):</span>
                  <span class="text-[9px] text-slate-400 font-normal">Tekan Enter / Klik Lacak</span>
                </label>
                <div class="flex items-center gap-1.5">
                  <div class="relative flex-1">
                    <i class="fa-solid fa-crosshairs absolute left-3 top-3 text-emerald-500 text-xs"></i>
                    <input type="text" v-model="doSearchSN" @keyup.enter="trackSerialNumberDirect"
                      placeholder="Input SN (FA-2610-001 / P9...)"
                      class="w-full bg-emerald-50/50 border border-emerald-300 focus:border-emerald-600 focus:bg-white rounded-xl pl-8 pr-3 py-2 text-xs font-bold font-mono text-emerald-950 uppercase transition min-h-[38px]">
                  </div>
                  <button type="button" @click="trackSerialNumberDirect" :disabled="isTrackingSN || !doSearchSN.trim()"
                    class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 min-h-[38px] shadow-xs cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-magnifying-glass-location text-xs" :class="{'fa-spin': isTrackingSN}"></i>
                    <span>Lacak</span>
                  </button>
                </div>
              </div>

              <!-- Filter Urutan Tanggal -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-arrow-down-up-across-line mr-1 text-indigo-600"></i> Urutan Tanggal:
                </label>
                <select v-model="doSortOrder"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-indigo-600 min-h-[38px] cursor-pointer">
                  <option value="desc">Terbaru (Paling Atas) ▼</option>
                  <option value="asc">Terlama (Paling Atas) ▲</option>
                </select>
              </div>

              <!-- Filter Dokumen DO / Surat Jalan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-file-invoice mr-1 text-red-600"></i> Filter Doc DO:
                </label>
                <select v-model="doDocFilter"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 min-h-[38px] cursor-pointer">
                  <option value="">Semua Dokumen DO</option>
                  <option v-for="doc in distinctDODocNumbers" :key="doc" :value="doc">{{ doc }}</option>
                </select>
              </div>
            </div>

            <!-- Baris Tambahan: Tanggal Dari & Sampai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100">
              <!-- Filter Tanggal Dari -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal Dari:
                </label>
                <input type="date" v-model="doStartDate"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-red-600 min-h-[38px]">
              </div>

              <!-- Filter Tanggal Sampai -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal Sampai:
                </label>
                <input type="date" v-model="doEndDate"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-red-600 min-h-[38px]">
              </div>
            </div>

            <!-- Action buttons row: Reset filter, Export Excel, Segarkan Data -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <button type="button" @click="resetDOFilter"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-rotate-left text-xs"></i>
                  <span>Reset Filter</span>
                </button>
                <span class="text-xs text-slate-400 font-medium">Ditemukan <strong>{{ filteredDOList.length }}</strong>
                  dokumen DO</span>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="syncAllDOsToCloud" :disabled="isBatchSyncingCloud"
                  title="Unggah semua dokumen Packing List yang belum tersimpan di Cloudinary agar QR Code aktif di luar jaringan"
                  class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 min-h-[38px] cursor-pointer shadow-xs">
                  <i class="fa-solid fa-cloud-arrow-up text-xs" :class="{'fa-bounce': isBatchSyncingCloud}"></i>
                  <span>{{ isBatchSyncingCloud ? `Sinkron (${batchSyncProgress.current}/${batchSyncProgress.total})...`
                    : 'Sinkron Cloud' }}</span>
                </button>
                <button type="button" @click="exportDeliveryOrderExcel(null)"
                  title="Export Laporan Delivery Order ke format Excel (.xlsx) dengan 10 kolom standar"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 min-h-[38px] cursor-pointer shadow-xs">
                  <i class="fa-solid fa-file-excel text-xs"></i>
                  <span>Export Excel (.xlsx)</span>
                </button>
                <button type="button" @click="loadDOList"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 min-h-[38px] cursor-pointer">
                  <i class="fa-solid fa-rotate-right text-xs"></i>
                  <span>Segarkan Data</span>
                </button>
              </div>
            </div>
          </div>

          <!-- KARTU HASIL PELACAKAN SERIAL NUMBER (TRACKING RESULT CARD) -->
          <div v-if="snTrackingResult" class="bg-gradient-to-r from-emerald-50 via-teal-50 to-blue-50 border-2 border-emerald-500/80 rounded-2xl p-4 sm:p-5 shadow-md animate-fadeIn relative space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black shadow-md shadow-emerald-600/30 text-lg">
                  <i class="fa-solid fa-box-check"></i>
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-emerald-600 text-white shadow-xs">
                      HASIL PELACAKAN SERIAL NUMBER
                    </span>
                    <span class="text-sm font-mono font-black text-emerald-950 bg-white px-2.5 py-0.5 rounded-md border border-emerald-300">
                      {{ snTrackingResult.serial_number }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-600 font-medium mt-0.5">
                    Unit teridentifikasi pada database logistik pengiriman pabrik.
                  </p>
                </div>
              </div>
              <button type="button" @click="snTrackingResult = null" title="Tutup Hasil Pelacakan"
                class="text-slate-400 hover:text-slate-600 w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/80 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>

            <!-- Grid 4 Kolom: Pengiriman Ke, Peti Ke, Dus Ke, Slot -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 pt-1">
              <!-- Pengiriman ke-X -->
              <div class="bg-white/95 backdrop-blur-xs border border-emerald-200 rounded-xl p-3 shadow-2xs">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-truck-fast text-emerald-600"></i> Pengiriman Ke:
                </div>
                <div class="text-lg font-black text-slate-900 mt-0.5">
                  Pengiriman #{{ snTrackingResult.delivery_seq || 1 }}
                </div>
                <div class="text-[11px] text-slate-600 font-semibold truncate mt-0.5" :title="snTrackingResult.do_number">
                  {{ snTrackingResult.do_number }}
                </div>
              </div>

              <!-- Peti ke-Y -->
              <div class="bg-white/95 backdrop-blur-xs border border-teal-200 rounded-xl p-3 shadow-2xs">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-pallet text-teal-600"></i> Peti Ke:
                </div>
                <div class="text-lg font-black text-teal-800 mt-0.5">
                  Peti #{{ snTrackingResult.crate_number || 1 }}
                </div>
                <div class="text-[11px] text-teal-700 font-mono font-bold truncate mt-0.5" :title="snTrackingResult.crate_id">
                  ID: {{ snTrackingResult.crate_id }}
                </div>
              </div>

              <!-- Dus ke-Z -->
              <div class="bg-white/95 backdrop-blur-xs border border-blue-200 rounded-xl p-3 shadow-2xs">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-box text-blue-600"></i> Dus Ke:
                </div>
                <div class="text-lg font-black text-blue-800 mt-0.5">
                  Dus #{{ snTrackingResult.box_number || 1 }}
                </div>
                <div class="text-[11px] text-blue-700 font-mono font-bold truncate mt-0.5" :title="snTrackingResult.box_id">
                  ID: {{ snTrackingResult.box_id }}
                </div>
              </div>

              <!-- Slot & Tanggal -->
              <div class="bg-white/95 backdrop-blur-xs border border-amber-200 rounded-xl p-3 shadow-2xs">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i class="fa-solid fa-location-dot text-amber-600"></i> Posisi Slot:
                </div>
                <div class="text-lg font-black text-amber-700 mt-0.5">
                  Slot #{{ snTrackingResult.slot_number || 1 }}
                </div>
                <div class="text-[11px] text-slate-500 font-semibold truncate mt-0.5">
                  {{ snTrackingResult.do_date || snTrackingResult.customer_name }}
                </div>
              </div>
            </div>

            <!-- Footer Action Card -->
            <div class="flex items-center justify-between pt-1 border-t border-emerald-200/60 text-xs">
              <span class="text-[11px] text-slate-600 font-medium">
                Penerima / Instansi: <strong class="text-slate-900">{{ snTrackingResult.customer_name }}</strong>
              </span>
              <div class="flex items-center gap-2">
                <button v-if="snTrackingResult.delivery_id" type="button" @click="openDODetailView(snTrackingResult.delivery_id)"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-eye text-xs"></i>
                  <span>Buka Dokumen DO</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Tabel Riwayat DO -->
          <div v-if="filteredDOList.length > 0"
            class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs min-w-[850px]">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
                  <tr>
                    <th class="p-3.5 w-12 text-center">No</th>
                    <th class="p-3.5">No. Surat Jalan</th>
                    <th @click="toggleDOSortOrder" class="p-3.5 cursor-pointer hover:bg-slate-100 transition select-none" title="Klik untuk membalik urutan tanggal">
                      <div class="flex items-center gap-1.5">
                        <span>Tanggal</span>
                        <i class="fa-solid text-[10px] text-indigo-600" :class="doSortOrder === 'desc' ? 'fa-arrow-down-wide-short' : 'fa-arrow-up-wide-short'"></i>
                      </div>
                    </th>
                    <th class="p-3.5">Penerima (Customer)</th>
                    <th class="p-3.5">Kontrak / Referensi</th>
                    <th class="p-3.5 text-center w-28 whitespace-nowrap">Kuantitas</th>
                    <th class="p-3.5 text-center w-24 whitespace-nowrap">Peti</th>
                    <th class="p-3.5 text-center w-28 whitespace-nowrap">Cloud PDF</th>
                    <th class="p-3.5 text-center w-36 whitespace-nowrap">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(item, idx) in pagedDOList" :key="item.id" class="hover:bg-slate-50 transition">
                    <td class="p-3.5 text-center font-bold text-slate-400">
                      {{ (pgState.doHistory.page - 1) * pgState.doHistory.size + idx + 1 }}
                    </td>
                    <td class="p-3.5 font-mono font-black text-red-700">
                      {{ item.do_number }}
                    </td>
                    <td class="p-3.5 text-slate-700 font-bold whitespace-nowrap">{{ item.do_date }}</td>
                    <td class="p-3.5">
                      <div class="font-black text-slate-900">{{ item.customer_name }}</div>
                      <div class="text-[10px] text-slate-500 line-clamp-1">{{ item.customer_address }}</div>
                    </td>
                    <td class="p-3.5 font-mono text-[11px] text-slate-600 font-bold">{{ item.contract_number }}</td>
                    <td class="p-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                      <span
                        class="inline-flex items-center justify-center min-w-[96px] bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap tracking-wide">
                        {{ item.total_quantity }} Unit
                      </span>
                    </td>
                    <td class="p-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                      <span
                        class="inline-flex items-center justify-center min-w-[80px] bg-slate-100 border border-slate-200 text-slate-800 px-2.5 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap">
                        {{ item.total_crates }} Peti
                      </span>
                    </td>
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <!-- Cloud PDF Badge & Direct Actions -->
                      <div v-if="item.cloud_pdf_url" class="inline-flex items-center gap-1">
                        <a :href="item.cloud_pdf_url" target="_blank"
                          title="Buka langsung file PDF di Cloudinary (Akses Publik / Handphone)"
                          class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 px-2.5 py-1 rounded-lg font-bold text-[11px] inline-flex items-center gap-1 transition">
                          <i class="fa-solid fa-cloud-arrow-down text-emerald-600"></i>
                          <span>Tersedia</span>
                        </a>
                        <button type="button" @click="uploadDOPackingListToCloud(item)"
                          :disabled="isUploadingCloudPdf[item.id]"
                          title="Unggah Ulang / Perbarui file PDF Packing List ini ke Cloudinary"
                          class="bg-slate-100 hover:bg-indigo-100 text-slate-500 hover:text-indigo-700 border border-slate-200 px-2 py-1 rounded-lg font-bold text-[10px] inline-flex items-center transition cursor-pointer">
                          <i class="fa-solid"
                            :class="isUploadingCloudPdf[item.id] ? 'fa-spinner fa-spin' : 'fa-rotate-right'"></i>
                        </button>
                      </div>
                      <div v-else>
                        <button type="button" @click="uploadDOPackingListToCloud(item)"
                          :disabled="isUploadingCloudPdf[item.id]" title="Unggah Packing List dokumen ini ke Cloudinary"
                          class="bg-slate-100 hover:bg-indigo-100 text-slate-600 hover:text-indigo-700 border border-slate-200 px-2 py-1 rounded-lg font-bold text-[10px] inline-flex items-center gap-1 transition cursor-pointer">
                          <i class="fa-solid"
                            :class="isUploadingCloudPdf[item.id] ? 'fa-spinner fa-spin' : 'fa-cloud-arrow-up'"></i>
                          <span>{{ isUploadingCloudPdf[item.id] ? 'Unggah...' : 'Upload' }}</span>
                        </button>
                      </div>
                    </td>
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <div class="flex items-center justify-center gap-1.5">
                        <!-- Single Detail Button -->
                        <button type="button" @click="openDODetailView(item.id)"
                          title="Buka Halaman Rincian &amp; Pusat Cetak DO"
                          class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-xl font-bold text-xs shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                          <i class="fa-solid fa-eye text-xs"></i>
                          <span>Detail</span>
                        </button>

                        <!-- Export Excel per DO -->
                        <button type="button" @click="exportDeliveryOrderExcel(item.id)"
                          title="Export Excel untuk DO ini"
                          class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-2.5 py-1.5 rounded-xl font-bold text-xs transition flex items-center gap-1 cursor-pointer">
                          <i class="fa-solid fa-file-excel text-xs"></i>
                          <span>Excel</span>
                        </button>

                        <!-- Hapus DO -->
                        <button type="button" @click="deleteDeliveryOrder(item.id, item.do_number)"
                          title="Hapus Dokumen DO"
                          class="bg-slate-100 hover:bg-rose-100 text-slate-500 hover:text-rose-700 p-2 rounded-xl transition cursor-pointer">
                          <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination: doHistory -->
              <div
                class="flex items-center justify-between px-4 py-3 border-t border-slate-200 bg-slate-50/70 select-none no-print flex-wrap gap-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs text-slate-500 font-semibold">
                    Menampilkan <strong class="text-slate-800">{{ filteredDOList.length === 0 ? 0 :
                      (((pgState.doHistory.page - 1) * pgState.doHistory.size) + 1) }}-{{
                      Math.min(pgState.doHistory.page * pgState.doHistory.size, filteredDOList.length) }}</strong> dari
                    <strong class="text-slate-800">{{ filteredDOList.length }}</strong> dokumen DO
                  </span>
                  <div class="flex items-center gap-1.5 ml-2 text-xs text-slate-500">
                    <span class="text-[11px]">Per halaman:</span>
                    <select v-model="pgState.doHistory.size" @change="pgState.doHistory.page = 1"
                      class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-bold text-slate-700 outline-none cursor-pointer">
                      <option :value="5">5</option>
                      <option :value="10">10</option>
                      <option :value="20">20</option>
                      <option :value="50">50</option>
                    </select>
                  </div>
                </div>
                <div class="flex items-center gap-1">
                  <button type="button" @click="pgState.doHistory.page = 1" :disabled="pgState.doHistory.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angles-left"></i>
                  </button>
                  <button type="button" @click="pgState.doHistory.page--" :disabled="pgState.doHistory.page <= 1"
                    class="w-8 h-8 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-xs">
                    <i class="fa-solid fa-angle-left"></i>
                  </button>
                  <span class="text-xs font-black text-slate-800 px-2">Hal {{ pgState.doHistory.page }} / {{
                    totalPgDOList }}</span>
                  <button type="button" @click="pgState.doHistory.page++"
                    :disabled="pgState.doHistory.page >= totalPgDOList"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angle-right"></i>
                  </button>
                  <button type="button" @click="pgState.doHistory.page = totalPgDOList"
                    :disabled="pgState.doHistory.page >= totalPgDOList"
                    class="w-8 h-8 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-xs">
                    <i class="fa-solid fa-angles-right"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State DO (Tampilan indah seperti Packing List) -->
          <div v-else
            class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-200 p-8 space-y-3 shadow-xs">
            <span
              class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold shadow-xs">
              <i class="fa-solid fa-file-invoice"></i>
            </span>
            <h4 class="font-bold text-base text-slate-800">
              {{ (doSearchText || doDocFilter || doStartDate || doEndDate) ? 'Tidak Ada Dokumen DO yang Sesuai Filter' :
              'Belum Ada Dokumen Surat Jalan / Delivery Order' }}
            </h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
              {{ (doSearchText || doDocFilter || doStartDate || doEndDate) ? 'Coba gunakan kata kunci pencarian yang berbeda atau sesuaikan filter dokumen dan tanggal.' : 'Anda dapat membuat dan menerbitkan dokumen Surat Jalan / Delivery Order lengkap dengan rincian kemasan peti dan data customer.' }}
            </p>
            <div class="pt-1">
              <button v-if="!(doSearchText || doDocFilter || doStartDate || doEndDate)" type="button"
                @click="doSubTab = 'form'"
                class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-red-600/25 transition cursor-pointer inline-flex items-center gap-1.5 min-h-[38px]">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Surat Jalan Sekarang</span>
              </button>
              <button v-else type="button" @click="resetDOFilter"
                class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer inline-flex items-center gap-1.5 min-h-[38px]">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                <span>Reset Filter DO</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= SUB-TAB: RIWAYAT LABEL PACKING LIST (STANDALONE) ================= -->
        <div v-if="doSubTab === 'labels_history'" class="space-y-4 animate-fadeIn">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <h3 class="font-black text-base text-slate-900 tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-box-archive text-emerald-600"></i>
                  <span>Riwayat Label Packing List Mandiri</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                  Daftar seluruh label packing list yang dibuat dan dicetak tanpa Surat Jalan.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="doSubTab = 'label_pl_only'"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/25 flex items-center gap-1.5 transition cursor-pointer min-h-[40px]">
                  <i class="fa-solid fa-plus text-xs"></i>
                  <span>Buat Label PL Baru</span>
                </button>
                <button type="button" @click="loadStandaloneLabels" :disabled="isLoadingStandaloneLabels"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 flex items-center gap-1.5 transition cursor-pointer min-h-[40px]">
                  <i class="fa-solid fa-arrows-rotate text-xs" :class="{'fa-spin': isLoadingStandaloneLabels}"></i>
                  <span>Segarkan</span>
                </button>
              </div>
            </div>

            <!-- Filter Grid: Cari Data, Filter Doc PL, Tipe Kemasan, Tanggal Dari, Tanggal Sampai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 pt-1">
              <!-- Cari Data -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Data:
                </label>
                <div class="relative">
                  <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                  <input type="text" v-model="labelSearchText" placeholder="No. PL, batch ID, petugas..."
                    class="w-full bg-slate-50 border border-slate-300 focus:border-emerald-600 focus:bg-white rounded-xl pl-8 pr-3 py-2 text-xs font-semibold text-slate-800 transition min-h-[38px]">
                </div>
              </div>

              <!-- Filter Doc PL -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-box-archive mr-1 text-emerald-600"></i> Filter Doc PL:
                </label>
                <select v-model="labelDocFilter"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[38px] cursor-pointer">
                  <option value="">Semua Dokumen PL</option>
                  <option v-for="doc in distinctPLLabelNumbers" :key="doc" :value="doc">{{ doc }}</option>
                </select>
              </div>

              <!-- Filter Tipe Kemasan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-boxes-stacked mr-1 text-teal-600"></i> Tipe Kemasan:
                </label>
                <select v-model="labelPackageFilter"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 min-h-[38px] cursor-pointer">
                  <option value="">Semua Kemasan</option>
                  <option value="crate">Peti (Crate)</option>
                  <option value="box">Dus / Box</option>
                  <option value="unit">Unit (SN Langsung)</option>
                </select>
              </div>

              <!-- Filter Tanggal Dari -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal Dari:
                </label>
                <input type="date" v-model="labelStartDate"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 min-h-[38px]">
              </div>

              <!-- Filter Tanggal Sampai -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-calendar-day mr-1"></i> Tanggal Sampai:
                </label>
                <input type="date" v-model="labelEndDate"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 min-h-[38px]">
              </div>
            </div>

            <!-- Action buttons row: Reset filter, counter, & refresh -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <button type="button" @click="resetLabelFilter"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-rotate-left text-xs"></i>
                  <span>Reset Filter</span>
                </button>
                <span class="text-xs text-slate-400 font-medium">Ditemukan <strong>{{
                    filteredStandaloneLabelsList.length }}</strong> riwayat label</span>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="loadStandaloneLabels" :disabled="isLoadingStandaloneLabels"
                  class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 min-h-[38px] cursor-pointer">
                  <i class="fa-solid fa-rotate-right text-xs" :class="{'fa-spin': isLoadingStandaloneLabels}"></i>
                  <span>Segarkan Data</span>
                </button>
              </div>
            </div>

            <!-- Tabel Riwayat Label -->
            <div v-if="filteredStandaloneLabelsList.length > 0"
              class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-left text-xs">
                <thead
                  class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200 uppercase text-[10px] tracking-wider">
                  <tr>
                    <th class="py-3 px-3.5 text-center w-12">No</th>
                    <th class="py-3 px-3.5">ID Batch Label</th>
                    <th class="py-3 px-3.5">No. Packing List</th>
                    <th class="py-3 px-3.5">Kemasan &amp; Unit</th>
                    <th class="py-3 px-3.5">Tanggal</th>
                    <th class="py-3 px-3.5">Petugas</th>
                    <th class="py-3 px-3.5 text-center">Aksi Cetak</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                  <tr v-for="(lbl, idx) in pagedStandaloneLabelsList" :key="lbl.id"
                    class="hover:bg-slate-50/80 transition">
                    <td class="py-3 px-3.5 text-center font-mono text-slate-400 font-bold">{{
                      (pgState.labelsHistory.page - 1) * pgState.labelsHistory.size + idx + 1 }}</td>
                    <td class="py-3 px-3.5 font-mono font-black text-slate-900">{{ lbl.label_batch_id }}</td>
                    <td class="py-3 px-3.5 font-mono font-bold text-emerald-700">{{ lbl.pl_number || '-' }}</td>
                    <td class="py-3 px-3.5">
                      <span class="font-bold text-slate-800">{{ lbl.total_containers }} {{ lbl.package_type === 'crate'
                        ? 'Peti' : (lbl.package_type === 'box' ? 'Dus' : 'Unit') }}</span>
                      <span class="text-slate-400 text-[11px] font-medium ml-1">({{ lbl.total_quantity }} Unit)</span>
                    </td>
                    <td class="py-3 px-3.5 text-slate-600 font-medium">{{ lbl.created_at }}</td>
                    <td class="py-3 px-3.5 text-slate-600 font-medium">{{ lbl.created_by || 'PPIC' }}</td>
                    <td class="py-3 px-3.5 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <button v-if="lbl.package_type === 'crate'" type="button"
                          @click="printStandaloneLabel(lbl, 'label_peti')" title="Cetak Label Peti (100x150mm)"
                          class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 font-bold text-[11px] px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition cursor-pointer">
                          <i class="fa-solid fa-print text-xs"></i>
                          <span>Label Peti</span>
                        </button>
                        <button type="button"
                          @click="printStandaloneLabel(lbl, lbl.package_type === 'unit' ? 'label_sn' : 'label_dus')"
                          title="Cetak Label Dus (100x150mm)"
                          class="bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-300 font-bold text-[11px] px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition cursor-pointer">
                          <i class="fa-solid fa-print text-xs"></i>
                          <span>Label Dus</span>
                        </button>
                        <button type="button" @click="deleteStandaloneLabel(lbl.id, lbl.label_batch_id)"
                          title="Hapus Arsip Label"
                          class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 p-1.5 rounded-lg transition cursor-pointer">
                          <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="filteredStandaloneLabelsList.length > 0"
              class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100 text-xs text-slate-500">
              <div class="flex items-center gap-2">
                <span>Tampilkan</span>
                <select v-model="pgState.labelsHistory.size"
                  class="border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-700 font-semibold focus:outline-none">
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                </select>
                <span>dari <strong class="text-slate-800">{{ filteredStandaloneLabelsList.length }}</strong> data</span>
              </div>
              <div class="flex items-center gap-1.5">
                <button type="button" @click="pgState.labelsHistory.page = Math.max(1, pgState.labelsHistory.page - 1)"
                  :disabled="pgState.labelsHistory.page <= 1"
                  class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition cursor-pointer">
                  <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                </button>
                <span class="px-2.5 py-1 text-slate-700 font-bold">
                  {{ pgState.labelsHistory.page }} / {{ totalPgStandaloneLabels }}
                </span>
                <button type="button"
                  @click="pgState.labelsHistory.page = Math.min(totalPgStandaloneLabels, pgState.labelsHistory.page + 1)"
                  :disabled="pgState.labelsHistory.page >= totalPgStandaloneLabels"
                  class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed font-medium transition cursor-pointer">
                  Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else
              class="text-center py-12 bg-slate-50 rounded-2xl border border-dashed border-slate-200 space-y-3">
              <span
                class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-xl font-bold">
                <i class="fa-solid fa-tags"></i>
              </span>
              <h4 class="font-bold text-sm text-slate-800">
                {{ (labelSearchText || labelDocFilter || labelPackageFilter || labelStartDate || labelEndDate) ? 'Tidak Ada Riwayat Label yang Sesuai Filter' : 'Belum Ada Riwayat Label Packing List' }}
              </h4>
              <p class="text-xs text-slate-500 max-w-sm mx-auto">
                {{ (labelSearchText || labelDocFilter || labelPackageFilter || labelStartDate || labelEndDate) ? 'Coba gunakan kata kunci pencarian yang berbeda atau sesuaikan filter Anda.' : 'Anda dapat membuat dan mencetak label kemasan secara cepat tanpa membuat surat jalan lengkap.' }}
              </p>
              <button
                v-if="!(labelSearchText || labelDocFilter || labelPackageFilter || labelStartDate || labelEndDate)"
                type="button" @click="doSubTab = 'label_pl_only'"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer inline-flex items-center gap-1.5 shadow-md shadow-emerald-600/25">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Buat Label PL Sekarang</span>
              </button>
              <button v-else type="button" @click="resetLabelFilter"
                class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                <span>Reset Filter</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ================= SUB-TAB 2.5: HALAMAN PENUH DETAIL DELIVERY ORDER ================= -->
        <div v-if="doSubTab === 'detail'" class="space-y-4 animate-fadeIn">
          <!-- Top Navigation & Action Header -->
          <div
            class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <button type="button" @click="doSubTab = 'history'"
              class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-indigo-600 bg-slate-100 hover:bg-slate-200 px-4 py-2.5 rounded-xl transition cursor-pointer w-fit">
              <i class="fa-solid fa-arrow-left"></i>
              <span>Kembali ke Riwayat DO</span>
            </button>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-500">Status Dokumen:</span>
              <span
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-black px-2.5 py-1 rounded-lg">
                <i class="fa-solid fa-circle-check text-[10px] mr-1"></i> Diterbitkan
              </span>
            </div>
          </div>

          <div v-if="isLoadingDODetail"
            class="p-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
            <i class="fa-solid fa-spinner fa-spin text-2xl text-red-600 mb-2"></i>
            <p class="text-xs font-bold">Memuat rincian dokumen Delivery Order...</p>
          </div>

          <div v-else-if="selectedDOForDetail" class="space-y-4">
            <!-- Panel 1: Header & Metadata Pengiriman -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-xs space-y-4">
              <div
                class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-600 to-red-700 text-white flex items-center justify-center font-black text-xl shadow-md shadow-red-600/30">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                  </div>
                  <div>
                    <div class="flex items-center gap-2 flex-wrap">
                      <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">{{
                        selectedDOForDetail.do_number }}</h2>
                      <span
                        class="bg-slate-100 border border-slate-200 text-slate-700 text-[11px] font-bold px-2.5 py-0.5 rounded-lg">
                        {{ selectedDOForDetail.package_type === 'box' ? 'Kemasan Per Dus' :
                        (selectedDOForDetail.package_type === 'unit' ? 'Kemasan Satuan' : 'Kemasan Per Peti') }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Surat Jalan Resmi &amp; Rincian Distribusi Produk P9</p>
                  </div>
                </div>
              </div>

              <!-- Grid Metadata -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1">
                  <div class="text-[10px] font-bold uppercase text-slate-400">Penerima (Customer)</div>
                  <div class="font-black text-slate-900 text-sm">{{ selectedDOForDetail.customer_name }}</div>
                  <div class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed">{{
                    selectedDOForDetail.customer_address }}</div>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1">
                  <div class="text-[10px] font-bold uppercase text-slate-400">Informasi Kontrak</div>
                  <div class="font-mono font-bold text-slate-900 text-xs">{{ selectedDOForDetail.contract_number }}
                  </div>
                  <div class="text-[11px] text-slate-600">{{ selectedDOForDetail.program_name }}</div>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1">
                  <div class="text-[10px] font-bold uppercase text-slate-400">Kuantitas &amp; Tanggal</div>
                  <div class="font-black text-emerald-700 text-sm">{{ selectedDOForDetail.total_quantity }} Unit</div>
                  <div class="text-[11px] text-slate-600">Tanggal: {{ selectedDOForDetail.do_date }}</div>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1">
                  <div class="text-[10px] font-bold uppercase text-slate-400">Diterbitkan Oleh</div>
                  <div class="font-bold text-slate-900">{{ selectedDOForDetail.created_by_name ||
                    selectedDOForDetail.created_by || 'Staf PPIC' }}</div>
                  <div class="text-[11px] text-slate-600">{{ selectedDOForDetail.created_by_title || 'Staf' }} {{
                    selectedDOForDetail.created_by_department || 'PPIC' }}</div>
                </div>
              </div>
            </div>

            <!-- Panel 2: Pusat Cetak Cerdas (Smart Print Center) -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
              <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-print text-indigo-600 text-sm"></i>
                  <h3 class="font-black text-xs sm:text-sm text-slate-900">Pusat Cetak Dokumen &amp; Label</h3>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">Disesuaikan otomatis sesuai jenis kemasan</span>
              </div>

              <!-- Opsi Cetak SATUAN -->
              <div v-if="selectedDOForDetail.package_type === 'unit'" class="flex flex-wrap gap-2.5">
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'surat_jalan')"
                  class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-file-invoice"></i>
                  <span>Cetak Dokumen Surat Jalan (DO)</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'label_sn')"
                  class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-barcode"></i>
                  <span>Cetak Label Barcode SN Satuan</span>
                </button>
              </div>

              <!-- Opsi Cetak PER DUS -->
              <div v-else-if="selectedDOForDetail.package_type === 'box'" class="flex flex-wrap gap-2.5">
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'surat_jalan')"
                  class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-file-invoice"></i>
                  <span>Cetak Dokumen Surat Jalan (DO)</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'packing_list')"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-list-check"></i>
                  <span>Cetak Dokumen Packing List (PL)</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'label_parent')"
                  class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-tags"></i>
                  <span>Cetak Label Dus (PL 1x10 Barcode SN)</span>
                </button>
              </div>

              <!-- Opsi Cetak PER PETI -->
              <div v-else class="flex flex-wrap gap-2.5">
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'surat_jalan')"
                  class="bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-file-invoice"></i>
                  <span>Cetak Dokumen Surat Jalan (DO)</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'packing_list')"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-list-check"></i>
                  <span>Cetak Dokumen Packing List Peti</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'label_parent')"
                  class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-tags"></i>
                  <span>Cetak Label Peti (20 SN Barcode)</span>
                </button>
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'label_child')"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-sm flex items-center gap-2 cursor-pointer transition">
                  <i class="fa-solid fa-tag"></i>
                  <span>Cetak Label Tiap Dus (PL 1x10 &amp; PL 2x10)</span>
                </button>
              </div>
            </div>

            <!-- Panel 3: Struktur Kemasan Fisik & Rincian Serial Number -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-xs space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-black text-sm text-slate-900 flex items-center gap-2">
                  <i class="fa-solid fa-boxes-stacked text-red-600"></i>
                  <span>Struktur Kemasan &amp; Rincian Serial Number</span>
                </h3>
                <span class="text-xs font-bold text-slate-600 font-mono">
                  Total: {{ selectedDOForDetail.total_quantity }} Unit
                </span>
              </div>

              <!-- KEMASAN PER PETI -->
              <div
                v-if="selectedDOForDetail.package_type === 'crate' || (!selectedDOForDetail.package_type && selectedDOForDetail.crates?.length)"
                class="space-y-4">
                <div v-for="(crate, cIdx) in (selectedDOForDetail.crates || [])" :key="crate.crate_id"
                  class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-3">
                  <div class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-200 pb-2.5">
                    <div class="flex items-center gap-2">
                      <span
                        class="w-7 h-7 rounded-lg bg-amber-600 text-white flex items-center justify-center font-black text-xs">
                        #{{ crate.crate_number }}
                      </span>
                      <div>
                        <span class="font-black text-slate-900 text-xs">Peti {{ crate.crate_number }}</span>
                        <span class="font-mono font-bold text-xs text-slate-600 ml-2">({{ crate.crate_id }})</span>
                      </div>
                    </div>
                    <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2.5 py-0.5 rounded-lg">
                      2 Dus (20 Unit)
                    </span>
                  </div>

                  <!-- 2 Dus dalam Peti -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div v-for="(box, bIdx) in (crate.boxes || [])" :key="box.box_id"
                      class="bg-white border border-slate-200 rounded-xl p-3.5 space-y-2.5">
                      <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="font-bold text-xs text-slate-800">Dus {{ box.box_number || (bIdx + 1) }}</span>
                        <span
                          class="font-mono text-[11px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{
                          box.box_id }}</span>
                      </div>
                      <div class="grid grid-cols-2 gap-1.5 font-mono text-xs">
                        <div v-for="(it, iIdx) in (box.items || [])" :key="it.slot_number || iIdx"
                          class="flex items-center justify-between bg-slate-50 border border-slate-100 px-2.5 py-1 rounded">
                          <span class="text-slate-400 text-[10px]">{{ it.slot_number || (iIdx + 1) }}.</span>
                          <span class="font-bold text-slate-900">{{ it.serial_number }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- KEMASAN PER DUS -->
              <div v-else-if="selectedDOForDetail.package_type === 'box'" class="space-y-4">
                <div v-for="(crate, cIdx) in (selectedDOForDetail.crates || [])" :key="crate.crate_id"
                  class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-3">
                  <div class="flex items-center justify-between flex-wrap gap-2 border-b border-slate-200 pb-2">
                    <div class="flex items-center gap-2">
                      <span
                        class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-xs">
                        #{{ crate.crate_number }}
                      </span>
                      <span class="font-black text-slate-900 text-xs">Dus {{ crate.crate_number }}</span>
                      <span class="font-mono font-bold text-xs text-slate-600 ml-1">({{ crate.crate_id }})</span>
                    </div>
                    <span class="bg-indigo-100 text-indigo-800 text-[11px] font-bold px-2.5 py-0.5 rounded-lg">
                      10 Unit
                    </span>
                  </div>

                  <div class="bg-white border border-slate-200 rounded-xl p-3.5">
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 font-mono text-xs">
                      <div v-for="(it, iIdx) in (crate.boxes?.[0]?.items || [])" :key="it.slot_number || iIdx"
                        class="flex items-center justify-between bg-slate-50 border border-slate-100 px-2.5 py-1.5 rounded">
                        <span class="text-slate-400 text-[10px]">{{ it.slot_number || (iIdx + 1) }}.</span>
                        <span class="font-bold text-slate-900">{{ it.serial_number }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- KEMASAN SATUAN -->
              <div v-else class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2 font-mono text-xs">
                  <div v-for="(crate, cIdx) in (selectedDOForDetail.crates || [])" :key="crate.crate_id"
                    class="flex items-center justify-between bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl">
                    <span class="text-slate-400 text-[10px]">#{{ cIdx + 1 }}</span>
                    <span class="font-bold text-slate-900">{{ crate.crate_id }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>


        <!-- ================= SUB-TAB 3: DATABASE CUSTOMER ================= -->
        <div v-if="doSubTab === 'customers'" class="max-w-4xl mx-auto space-y-4">
          <div
            class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3">
            <div>
              <h3 class="font-black text-sm text-slate-900">Database Pelanggan / Customer</h3>
              <p class="text-[11px] text-slate-500">Kelola daftar instansi penerima barang untuk auto-fill Surat Jalan
              </p>
            </div>
            <button type="button" @click="openNewCustomerModal"
              class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-3.5 py-2.5 rounded-xl shadow-md shadow-red-600/20 flex items-center gap-1.5 transition cursor-pointer min-h-[42px]">
              <i class="fa-solid fa-user-plus text-xs"></i>
              <span>Tambah Customer</span>
            </button>
          </div>

          <!-- List Customer Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="c in customersList" :key="c.id"
              class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex flex-col justify-between space-y-3">
              <div>
                <div class="flex items-start justify-between gap-2">
                  <h4 class="font-black text-slate-900 text-sm">{{ c.name }}</h4>
                  <button type="button" @click="deleteCustomer(c.id, c.name)"
                    class="text-slate-400 hover:text-red-600 text-xs p-1 transition cursor-pointer"
                    title="Hapus customer">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
                <p class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-relaxed">{{ c.address }}</p>
              </div>
              <div
                class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-[11px] text-slate-500">
                <span><i class="fa-solid fa-phone text-slate-400 mr-1"></i>{{ c.phone || '-' }}</span>
                <span><i class="fa-solid fa-fax text-slate-400 mr-1"></i>{{ c.fax || '-' }}</span>
              </div>
            </div>
            <div v-if="customersList.length === 0"
              class="col-span-full p-8 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
              Belum ada data customer terdaftar.
            </div>
          </div>
        </div>

      </section>


@endverbatim
