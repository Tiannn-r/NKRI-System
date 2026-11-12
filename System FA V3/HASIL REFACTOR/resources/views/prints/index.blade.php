@verbatim
    <!-- ================= AREA CETAK DOKUMEN RESMI (SURAT JALAN, PACKING LIST, LABEL PETI, LABEL DUS) ================= -->
    <div id="do-printable-area" class="font-sans text-slate-900 bg-white"
      style="font-family: 'Roboto', Arial, sans-serif;">

      <!-- ================= 1. PRINT: SURAT JALAN (8.5 x 5.5 INCH LANDSCAPE, MARGIN WORD: TOP 1cm, RIGHT 2.33cm, BOTTOM 0.5cm, LEFT 1.66cm) ================= -->
      <div v-if="(activePrintType === 'surat_jalan' || activePrintType === 'all') && activePrintDO"
        class="mx-auto text-black font-sans bg-white do-page-break flex flex-col justify-between overflow-hidden select-text"
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
                <table style="font-size: 11px; line-height: 1.45; border-collapse: collapse; margin-left: auto;">
                  <tbody>
                    <tr>
                      <td style="font-weight: 700; white-space: nowrap; padding-right: 4px; text-align: left;">No. Surat
                        Jalan</td>
                      <td style="padding: 0 4px;">:</td>
                      <td style="font-weight: 400; white-space: nowrap; text-align: left;">{{ activePrintDO.do_number }}
                      </td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; white-space: nowrap; padding-right: 4px; text-align: left;">Tanggal
                      </td>
                      <td style="padding: 0 4px;">:</td>
                      <td style="font-weight: 400; white-space: nowrap; text-align: left;">{{ activePrintDO.do_date }}
                      </td>
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
                    <td style="font-weight: 600; vertical-align: top;">{{ activePrintDO.customer_name }}</td>
                  </tr>
                  <tr>
                    <td style="font-weight: 700; vertical-align: top; padding-top: 2px;">Alamat</td>
                    <td style="vertical-align: top; padding-top: 2px;">:</td>
                    <td
                      style="vertical-align: top; padding-top: 2px; white-space: pre-line; max-width: 320px; line-height: 1.3;">
                      {{ activePrintDO.customer_address }}
                    </td>
                  </tr>
                  <tr>
                    <td style="font-weight: 700; vertical-align: top; padding-top: 3px;">Nomor</td>
                    <td style="vertical-align: top; padding-top: 3px;">:</td>
                    <td style="vertical-align: top; padding-top: 3px;">{{ activePrintDO.contract_number }}</td>
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
                      <td style="vertical-align: top;">{{ activePrintDO.program_name || '-' }}</td>
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
                      :style="{ width: activePrintDO.package_type === 'unit' ? '34%' : '52%', borderRight: '1.5px solid #000000' }"
                      style="text-align: center; font-weight: 700; padding: 2px 4px;">
                      Produk</th>
                    <th
                      :style="{ width: activePrintDO.package_type === 'unit' ? '18%' : '21%', borderRight: '1.5px solid #000000' }"
                      style="text-align: center; font-weight: 700; padding: 2px 4px;">
                      Jumlah</th>
                    <th
                      :style="{ width: activePrintDO.package_type === 'unit' ? '18%' : '21%', borderRight: activePrintDO.package_type === 'unit' ? '1.5px solid #000000' : 'none' }"
                      style="text-align: center; font-weight: 700; padding: 2px 4px;">
                      Satuan</th>
                    <th v-if="activePrintDO.package_type === 'unit'" style="width: 24%; text-align: center; font-weight: 700; padding: 2px 4px;">
                      Serial Number
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <!-- KEMASAN SATUAN (package_type === 'unit'): Baris kebawah mengurut sebanyak produk -->
                  <template v-if="activePrintDO.package_type === 'unit'">
                    <tr v-for="(sn, snIdx) in getDOSerialNumbers(activePrintDO)" :key="'print_unit_sn_' + snIdx"
                      :style="{ borderBottom: snIdx === getDOSerialNumbers(activePrintDO).length - 1 ? 'none' : '1px solid #000000', height: '22px' }">
                      <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">{{ snIdx + 1 }}
                      </td>
                      <td
                        style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px; font-weight: 600;">
                        {{ activePrintDO.product_name || 'Nanggala P9' }}</td>
                      <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">1</td>
                      <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">UNIT</td>
                      <td style="text-align: center; padding: 2px 4px; font-family: 'Roboto', Arial, sans-serif;">{{ sn
                        }}</td>
                    </tr>
                  </template>

                  <!-- BUKAN SATUAN (PETI / DUS): Ringkasan dengan QR Packing List -->
                  <template v-else>
                    <tr style="min-height: 26px;">
                      <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">1</td>
                      <td
                        style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px; font-weight: 600;">
                        <div>{{ activePrintDO.product_name || 'Nanggala P9' }}</div>
                      </td>
                      <td style="border-right: 1px solid #000000; text-align: center; padding: 2px 4px;">
                        <div style="font-weight: 600;">{{ getDOTotalQuantity(activePrintDO) }}</div>
                        <div v-if="activePrintDO.package_type === 'crate'"
                          style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                          ({{ getDOCrateCount(activePrintDO) }} Peti / {{ getDOBoxCount(activePrintDO) }} Dus)
                        </div>
                        <div v-else-if="activePrintDO.package_type === 'box'"
                          style="font-size: 8.5px; font-weight: 500; color: #1e293b; margin-top: 1px; line-height: 1.2;">
                          ({{ getDOBoxCount(activePrintDO) }} Dus)
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
              <div style="font-weight: 700; font-size: 11px; line-height: 1.25;">({{ activePrintDO.created_by_name ||
                activePrintDO.created_by || doSettings.default_issuer_name || 'Rezka Rachdana' }})</div>
              <div style="font-weight: 400; font-size: 10px; color: #1f2937; margin-top: 1.5px;">
                {{ activePrintDO.created_by_title || doSettings.default_issuer_title || 'Staf PPIC' }}
              </div>
            </div>

            <!-- Kanan: Diterima Oleh -->
            <div style="width: 190px; text-align: center;">
              <div style="font-weight: 700; font-size: 11px; letter-spacing: 0.2px;">Diterima Oleh</div>
              <div style="height: 42px;"></div>
              <div style="font-weight: 700; font-size: 11px; line-height: 1.25;">( .............................. )
              </div>
              <div style="font-size: 10px; margin-top: 1.5px; visibility: hidden;">&nbsp;</div>
            </div>
          </div>

        </div>
      </div>

      <!-- ================= 2. PRINT: PACKING LIST (4 TABEL: 2 PETI DENGAN 2 DUS PER LEMBAR A4) ================= -->
      <div v-if="(activePrintType === 'packing_list' || activePrintType === 'all') && activePrintDO">
        <div v-for="(sheet, sIdx) in printPackingListSheets" :key="'print_pl_sheet_'+sIdx"
          class="w-[210mm] h-[297mm] min-h-[297mm] max-h-[297mm] p-0 mx-auto text-black font-sans bg-white do-page-break do-a4-doc flex flex-col justify-between overflow-hidden"
          style="font-family: 'Roboto', Arial, sans-serif; box-sizing: border-box; position: relative;">

          <!-- TOP SECTION: HEADER, METADATA, & 4 TABLES -->
          <div>
            <!-- Header Atas Polygon Pink Sesuai Canva (Full Width Mentok Kanan Kiri) -->
            <div
              style="position: relative; width: 100%; height: 124px; box-sizing: border-box; overflow: hidden; margin-bottom: 18px;">
              <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: block; z-index: 0;"
                viewBox="0 0 794 124" preserveAspectRatio="none">
                <polygon points="0,0 794,0 794,68 395,68 360,124 0,124" fill="#F7EAEB" />
              </svg>

              <div
                style="position: relative; z-index: 1; height: 100%; padding: 0 45px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
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

                <div style="height: 56px;"></div>
              </div>
            </div>

            <!-- Content Section with 45px Side Margins -->
            <div style="padding: 0 45px;">
              <!-- Metadata Grid (Font Digedein Sesuai Permintaan User) -->
              <div style="font-size: 13px; color: #000000; line-height: 1.45; margin-bottom: 12px;">
                <table style="border-collapse: collapse; border: none; width: 100%;">
                  <tbody>
                    <tr>
                      <td
                        style="font-weight: 700; padding: 2.5px 0; width: 130px; vertical-align: top; font-size: 13px;">
                        No. Packing List</td>
                      <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                        getSheetPLNumber(sheet, activePrintDO) }}</td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Tanggal</td>
                      <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                        formatDocDate(activePrintDO.do_date) }}</td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">{{ sheet.containerLabel || (activePrintDO.package_type === 'box' ? 'ID Dus' : 'ID Peti') }}</td>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;"
                        class="font-sans text-slate-900">
                        : {{ sheet.containerId || [sheet.crate1?.crate_id || sheet.crate1?.crate_number, sheet.crate2?.crate_id ||
                        sheet.crate2?.crate_number].filter(Boolean).join(' & ') || defaultCrateIdForNow }}
                      </td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nama Produk
                      </td>
                      <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                        activePrintDO.product_name || 'Nanggala P9' }}</td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Nomor</td>
                      <td style="font-weight: 400; padding: 2.5px 0; vertical-align: top; font-size: 13px;">: {{
                        activePrintDO.contract_number || doSettings?.default_contract_number || 'TRAK/2509/APBN/XI/2025/KEMHAN' }}</td>
                    </tr>
                    <tr>
                      <td style="font-weight: 700; padding: 2.5px 0; vertical-align: top; font-size: 13px;">Program</td>
                      <td
                        style="font-weight: 400; padding: 2.5px 0; vertical-align: top; max-width: 440px; line-height: 1.4; font-size: 13px;">
                        : {{ activePrintDO.program_name || doSettings?.default_program_name || 'PENGADAAN ALPALHANKAM MATSUSSUS' }}
                      </td>
                    </tr>
                  </tbody>
                </table>

                <!-- Penerima Block -->
                <div style="margin-top: 10px;">
                  <div style="font-weight: 700; font-size: 13px;">Penerima &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; :
                  </div>
                  <div style="margin-top: 2px; line-height: 1.4; font-size: 13px;">
                    <div style="font-weight: 400;">{{ activePrintDO.customer_name }}</div>
                    <div style="white-space: pre-line;">{{ activePrintDO.customer_address }}</div>
                  </div>
                </div>
              </div>

              <!-- 3. TABEL ISI SERIAL NUMBER (DINAMIS MENYESUAIKAN JUMLAH DUS / PETI) -->
              <div class="grid grid-cols-2 gap-x-5 gap-y-3.5 my-2" style="font-family: 'Roboto', Arial, sans-serif;">
                <div v-for="(tbl, tIdx) in (sheet.tables || [])" :key="'print_pl_tbl_' + tIdx">
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
                      <tr v-for="s in 10" :key="'print_pl_row_' + tIdx + '_' + s"
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

          <!-- Bottom Pink Footer (MENEMPEL MENTOK KE BAWAH & MENTOK KANAN KIRI) -->
          <div style="margin-top: auto; width: 100%;" class="shrink-0">
            <div style="padding: 0 45px;"
              class="flex items-center justify-between text-[11px] text-slate-500 font-bold mb-1.5 font-sans">
              <span>Tanggal Cetak: {{ currentRealtimePrintDate }}</span>
              <span>Lembar {{ sheet.sheetIndex }} dari {{ sheet.totalSheets }}</span>
            </div>
            <div class="do-pink-footer"
              style="background-color: #F7EAEB; width: 100%; padding: 14px 45px 36px 45px; box-sizing: border-box; margin: 0;">
              <div class="flex justify-between items-start" style="font-size: 11px;">
                <!-- Head Office -->
                <div style="max-width: 240px;">
                  <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Head Office :
                  </div>
                  <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                    Jalan Mendawai I No. 11 Kramat Pela<br>Jakarta Selatan, 12130
                  </div>
                </div>

                <!-- Factory -->
                <div style="max-width: 240px;">
                  <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Factory :</div>
                  <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                    Jalan Gempol No. 9, Mekar Mulya<br>Bandung, 40292
                  </div>
                </div>

                <!-- Kontak -->
                <div style="text-align: left;">
                  <div style="font-weight: 700; font-size: 11.5px; color: #CC0000; margin-bottom: 2px;">Kontak :</div>
                  <div style="font-weight: 400; color: #000000; line-height: 1.35;">
                    {{ doSettings.contact_email || 'info@nanggala.co.id' }}
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ================= 3. PRINT: LABEL PETI (1:1 DENGAN PDF 3) ================= -->
      <div
        v-if="(activePrintType === 'label_peti' || activePrintType === 'label_parent' || activePrintType === 'all') && activePrintDO && activePrintDO.package_type === 'crate'">
        <div v-for="(crate, cIdx) in activePrintDO.crates" :key="'print_lp_'+cIdx"
          class="mx-auto bg-white do-page-break do-label-100x150"
          style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; min-width: 100mm; max-width: 100mm; height: 150mm; min-height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden; margin: 0 auto;">
          <div class="w-full h-full text-black bg-white border-[2.5px] border-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
            <!-- Header Label: Logo Kiri, Nama PT Kanan, Judul PACKING LIST -->
            <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center shrink-0">
                  <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;" class="object-contain">
                </div>
                <div class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
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
                <span class="col-span-8 font-normal">{{ activePrintDO.program_name }}</span>
              </div>
              <div class="grid grid-cols-12 gap-1">
                <span class="col-span-3 font-bold">NOMOR</span>
                <span class="col-span-1 font-bold">:</span>
                <span class="col-span-8 font-normal">{{ activePrintDO.contract_number }}</span>
              </div>
              <div class="grid grid-cols-12 gap-1">
                <span class="col-span-3 font-bold">PRODUK</span>
                <span class="col-span-1 font-bold">:</span>
                <span class="col-span-8 font-normal">{{ activePrintDO.product_name || 'Nanggala P9' }}</span>
              </div>
              <div class="grid grid-cols-12 gap-1">
                <span class="col-span-3 font-bold">ID. PETI</span>
                <span class="col-span-1 font-bold">:</span>
                <span class="col-span-8 font-normal">{{ crate.crate_id || crate.crate_number || '2609-1001' }}</span>
              </div>
              <div class="grid grid-cols-12 gap-1 pt-0.5">
                <span class="col-span-3 font-bold">PENERIMA</span>
                <span class="col-span-1 font-bold">:</span>
                <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                  <div>{{ activePrintDO.customer_name }}</div>
                  <div>BADAN LOGISTIK PERTAHANAN</div>
                  <div>{{ activePrintDO.customer_address }}</div>
                </div>
              </div>
            </div>

            <!-- Serial Number 2 Kolom: Garis Vertikal Tengah Menyambung Penuh ke Bawah (Tanpa Terputus) -->
            <div class="flex-1 min-h-0 border-b-[2.5px] border-black text-xs flex">
              <!-- Pak 1 (Kiri 50%) -->
              <div class="w-1/2 border-r-[2.5px] border-black flex flex-col justify-between">
                <!-- Baris Judul Pak 1: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                  PAK 1 DARI 2
                </div>
                <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                  <span class="w-6">NO.</span>
                  <span>SERIAL NUMBER</span>
                </div>
                <!-- Daftar 10 Serial Number -->
                <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                  <div v-for="s in 10" :key="'print_lp1_'+s" class="flex items-center leading-tight">
                    <span class="w-6 font-bold">{{ s }}.</span>
                    <span class="font-black">{{ (crate.boxes?.[0]?.items?.[s - 1]?.serial_number) || (crate.items?.[s - 1]?.serial_number) || (typeof crate.items?.[s - 1] === 'string' ? crate.items[s - 1] : '') || '' }}</span>
                  </div>
                </div>
              </div>

              <!-- Pak 2 (Kanan 50%) -->
              <div class="w-1/2 flex flex-col justify-between">
                <!-- Baris Judul Pak 2: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                  PAK 2 DARI 2
                </div>
                <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                  <span class="w-6">NO.</span>
                  <span>SERIAL NUMBER</span>
                </div>
                <!-- Daftar 10 Serial Number -->
                <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                  <div v-for="s in 10" :key="'print_lp2_'+s" class="flex items-center leading-tight">
                    <span class="w-6 font-bold">{{ s }}.</span>
                    <span class="font-black">{{ crate.boxes?.[1]?.items?.[s - 1]?.serial_number || '' }}</span>
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
                  <span class="font-black text-sm tracking-wide">{{ formatNumericDate(activePrintDO.do_date) }}</span>
                </div>
                <div class="px-3 py-1.5 flex items-center">
                  <span class="font-black text-xs w-[64px] shrink-0">JUMLAH</span>
                  <span class="font-black mr-1.5">:</span>
                  <span class="font-black text-sm tracking-wide">{{ crate.total_quantity || 20 }} UNIT</span>
                </div>
              </div>

              <!-- Kolom Kanan 50%: NO. PETI & Angka Disatukan Utuh Tanpa Garis Pemisah -->
              <div class="w-1/2 px-3 py-1.5 flex flex-col items-center justify-center">
                <div class="font-black uppercase tracking-wider text-[11px] mb-1">
                  NO. PETI :
                </div>
                <div class="text-xl font-black tracking-wider leading-none">
                  {{ cIdx + 1 }} DARI {{ activePrintDO.crates?.length || activePrintDO.total_crates || 20 }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="(activePrintType === 'label_dus' || activePrintType === 'label_child' || (activePrintType === 'label_peti' && activePrintDO.package_type === 'box') || activePrintType === 'all') && activePrintDO">
        <template v-for="(crate, cIdx) in activePrintDO.crates" :key="'print_dus_'+cIdx">
          <!-- Dus 1 (Pak 1 dari 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
          <div
            class="mx-auto bg-white do-page-break do-label-100x150"
            style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; min-width: 100mm; max-width: 100mm; height: 150mm; min-height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden; margin: 0 auto;">
            <div class="w-full h-full text-black bg-white border-[2.5px] border-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
              <!-- Header Label Sesuai Permintaan User -->
              <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center shrink-0">
                    <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;" class="object-contain">
                  </div>
                  <div class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
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
                  <span class="col-span-8 font-normal">{{ activePrintDO.program_name }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">NOMOR</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ activePrintDO.contract_number }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">PRODUK</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ activePrintDO.product_name || 'Nanggala P9' }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">{{ activePrintDO.package_type === 'box' ? 'ID. DUS' : 'ID. PETI' }}</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ activePrintDO.package_type === 'box' ? (crate.box_id || crate.boxes?.[0]?.box_id || crate.crate_id || '') : ((crate.crate_id || crate.crate_number || '2609-1001') + '-01') }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1 pt-0.5">
                  <span class="col-span-3 font-bold">PENERIMA</span>
                  <span class="col-span-1 font-bold">:</span>
                  <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                    <div>{{ activePrintDO.customer_name }}</div>
                    <div>BADAN LOGISTIK PERTAHANAN</div>
                    <div>{{ activePrintDO.customer_address }}</div>
                  </div>
                </div>
              </div>
              <!-- Serial Number 1 Kolom Sesuai Dus 1 (Garis Menyatu Penuh Langsung ke Baris Bawah) -->
              <div class="flex-1 min-h-0 border-b-[2.5px] border-black flex flex-col justify-between text-xs">
                <!-- Baris Judul Pak 1: HANYA MUNCUL JIKA PER PETI (Sesuai File Word: PAK 1 DARI 2). JIKA PER DUS: HILANGKAN PAKNYA! -->
                <div v-if="activePrintDO.package_type !== 'box'" class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                  PAK 1 DARI 2
                </div>
                <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                  <span class="w-6">NO.</span>
                  <span>SERIAL NUMBER</span>
                </div>
                <!-- Daftar 10 Serial Number -->
                <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                  <div v-for="s in 10" :key="'print_dus1_'+s" class="flex items-center leading-tight">
                    <span class="w-6 font-bold">{{ s }}.</span>
                    <span class="font-black">{{ crate.boxes?.[0]?.items?.[s - 1]?.serial_number || '' }}</span>
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
                    <span class="font-black text-sm tracking-wide">{{ formatNumericDate(activePrintDO.do_date) }}</span>
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
                    {{ activePrintDO.package_type === 'box' ? 'NO. DUS :' : 'NO. PETI :' }}
                  </div>
                  <div class="text-xl font-black tracking-wider leading-none">
                    {{ cIdx + 1 }} DARI {{ activePrintDO.crates?.length || (activePrintDO.package_type === 'box' ? getDOBoxCount(activePrintDO) : (activePrintDO.total_crates || 20)) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Dus 2 (Pak 2 dari 2) Sesuai Ukuran Pasti W x H = 100mm x 150mm (Garis Kotak Penuh Menyatu) -->
          <div v-if="activePrintDO.package_type !== 'box' && crate.boxes && crate.boxes.length > 1"
            class="mx-auto bg-white do-page-break do-label-100x150"
            style="font-family: 'Roboto', Arial, sans-serif; width: 100mm; min-width: 100mm; max-width: 100mm; height: 150mm; min-height: 150mm; max-height: 150mm; padding: 4.5mm; box-sizing: border-box; overflow: hidden; margin: 0 auto;">
            <div class="w-full h-full text-black bg-white border-[2.5px] border-black flex flex-col" style="box-sizing: border-box; overflow: hidden;">
              <!-- Header Label Sesuai Permintaan User -->
              <div class="px-3 pt-2.5 pb-2 border-b-[2.5px] border-black shrink-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center shrink-0">
                    <img :src="nkriLabelLogoBase64" alt="NKRI" style="height: 18px; width: auto;" class="object-contain">
                  </div>
                  <div class="text-right font-black text-[9.5px] text-black uppercase whitespace-nowrap tracking-tight">
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
                  <span class="col-span-8 font-normal">{{ activePrintDO.program_name }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">NOMOR</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ activePrintDO.contract_number }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">PRODUK</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ activePrintDO.product_name || 'Nanggala P9' }}</span>
                </div>
                <div class="grid grid-cols-12 gap-1">
                  <span class="col-span-3 font-bold">ID. PETI</span>
                  <span class="col-span-1 font-bold">:</span>
                  <span class="col-span-8 font-normal">{{ (crate.crate_id || crate.crate_number || '2609-1001')
                    }}-02</span>
                </div>
                <div class="grid grid-cols-12 gap-1 pt-0.5">
                  <span class="col-span-3 font-bold">PENERIMA</span>
                  <span class="col-span-1 font-bold">:</span>
                  <div class="col-span-8 font-normal space-y-0.5 leading-tight">
                    <div>{{ activePrintDO.customer_name }}</div>
                    <div>BADAN LOGISTIK PERTAHANAN</div>
                    <div>{{ activePrintDO.customer_address }}</div>
                  </div>
                </div>
              </div>
              <!-- Serial Number 1 Kolom Sesuai Dus 2 (Garis Menyatu Penuh Langsung ke Baris Bawah) -->
              <div class="flex-1 min-h-0 border-b-[2.5px] border-black flex flex-col justify-between text-xs">
                <!-- Baris Judul Pak 2: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-1 border-b-[2.5px] border-black font-black uppercase text-left text-[11px] shrink-0">
                  PAK 2 DARI 2
                </div>
                <!-- Baris Kolom Header: Garis menyatu penuh sampe ujung -->
                <div class="px-3 py-0.5 border-b-[2.5px] border-black flex text-[10px] font-black shrink-0">
                  <span class="w-6">NO.</span>
                  <span>SERIAL NUMBER</span>
                </div>
                <!-- Daftar 10 Serial Number -->
                <div class="flex-1 flex flex-col justify-between text-xs px-3 py-1">
                  <div v-for="s in 10" :key="'print_dus2_'+s" class="flex items-center leading-tight">
                    <span class="w-6 font-bold">{{ s }}.</span>
                    <span class="font-black">{{ crate.boxes?.[1]?.items?.[s - 1]?.serial_number || '' }}</span>
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
                    <span class="font-black text-sm tracking-wide">{{ formatNumericDate(activePrintDO.do_date) }}</span>
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
                    {{ cIdx + 1 }} DARI {{ activePrintDO.crates?.length || 20 }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- ================= 5. PRINT: LABEL SN SATUAN (THERMAL 80x20mm) ================= -->
      <div v-if="activePrintType === 'label_sn' && activePrintDO">
        <div v-for="(sn, snIdx) in getDOSerialNumbers(activePrintDO)" :key="'print_sn_'+snIdx"
          class="do-page-break do-label-80x20 bg-white text-slate-900 select-none overflow-hidden mx-auto"
          style="font-family: 'Roboto', Arial, sans-serif; width: 80mm; min-width: 80mm; max-width: 80mm; height: 20mm; min-height: 20mm; max-height: 20mm; padding: 1.5mm 2.5mm 1.5mm 3mm; box-sizing: border-box; display: flex; flex-direction: row; align-items: center; justify-content: space-between;">

          <!-- Kiri: Info Block -->
          <div
            style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; padding-right: 2mm; box-sizing: border-box;">
            <!-- Baris 1: Logo NKRI -->
            <div style="display: flex; align-items: center; height: 3.2mm; margin-bottom: 0.5mm;">
              <img :src="nkriLabelLogoBase64 || nkriLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')" alt="NKRI Logo"
                style="height: 3.2mm; width: auto; object-fit: contain; display: block;">
            </div>

            <!-- Baris 2: Nama Produk -->
            <div
              style="font-size: 10.5px; font-weight: 900; color: #000000; line-height: 11px; font-family: 'Roboto', Arial, sans-serif; white-space: nowrap; margin-top: -0.5mm; margin-bottom: 0.8mm;">
              {{ activePrintDO.product_name || 'Nanggala P9' }}
            </div>

            <!-- Baris 3: Serial Number: -->
            <div
              style="font-size: 6.5px; font-weight: 700; color: #000000; line-height: 7.5px; font-family: 'Roboto', Arial, sans-serif; margin-bottom: 0.5mm;">
              Serial Number:
            </div>

            <!-- Baris 4: SN Besar & Tebal + Made In Indonesia 2026 -->
            <div
              style="display: flex; align-items: baseline; justify-content: flex-start; width: 100%; white-space: nowrap; line-height: 11px;">
              <span
                style="font-size: 11.5px; font-weight: 900; color: #000000; font-family: 'Roboto', Arial, sans-serif; letter-spacing: 0.2px; line-height: 11px; flex-shrink: 0;">
                {{ sn }}
              </span>
              <span
                style="font-size: 6px; font-weight: 700; color: #000000; font-family: 'Roboto', Arial, sans-serif; line-height: 11px; margin-left: 3mm;">
                Made In Indonesia 2026
              </span>
            </div>
          </div>

          <!-- Kanan: QR Code -->
          <div :id="'do-sn-qr-print-' + snIdx"
            style="flex-shrink: 0; width: 16mm; height: 16mm; padding: 1mm; display: flex; align-items: center; justify-content: center; background: #ffffff; box-sizing: border-box;">
          </div>
        </div>
      </div>

      <!-- ================= 6. PRINT: BATCH LABEL SN ACC (THERMAL 80x20mm) ================= -->
      <div id="batch-label-printable-area">
        <div v-for="(item, idx) in batchLabelItems" :key="'batch_print_page_' + idx"
          class="batch-label-page bg-white text-slate-900 select-none overflow-hidden mx-auto"
          style="font-family: 'Roboto', Arial, sans-serif; width: 80mm; min-width: 80mm; max-width: 80mm; height: 20mm; min-height: 20mm; max-height: 20mm; padding: 1.5mm 2.5mm 1.5mm 3mm; box-sizing: border-box; display: flex; flex-direction: row; align-items: center; justify-content: space-between;">

          <!-- Kiri: Info Block -->
          <div
            style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; padding-right: 2mm; box-sizing: border-box;">
            <!-- Baris 1: Logo NKRI -->
            <div style="display: flex; align-items: center; height: 3.2mm; margin-bottom: 0.5mm;">
              <img :src="nkriLabelLogoBase64 || nkriLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')" alt="NKRI Logo"
                style="height: 3.2mm; width: auto; object-fit: contain; display: block;">
            </div>

            <!-- Baris 2: Nama Produk -->
            <div
              style="font-size: 10.5px; font-weight: 900; color: #000000; line-height: 11px; font-family: 'Roboto', Arial, sans-serif; white-space: nowrap; margin-top: -0.5mm; margin-bottom: 0.8mm;">
              {{ item.product_type || 'Nanggala P9' }}
            </div>

            <!-- Baris 3: Serial Number: -->
            <div
              style="font-size: 6.5px; font-weight: 700; color: #000000; line-height: 7.5px; font-family: 'Roboto', Arial, sans-serif; margin-bottom: 0.5mm;">
              Serial Number:
            </div>

            <!-- Baris 4: SN Besar & Tebal + Made In Indonesia 2026 -->
            <div
              style="display: flex; align-items: baseline; justify-content: flex-start; width: 100%; white-space: nowrap; line-height: 11px;">
              <span
                style="font-size: 11.5px; font-weight: 900; color: #000000; font-family: 'Roboto', Arial, sans-serif; letter-spacing: 0.2px; line-height: 11px; flex-shrink: 0;">
                {{ item.sn }}
              </span>
              <span
                style="font-size: 6px; font-weight: 700; color: #000000; font-family: 'Roboto', Arial, sans-serif; line-height: 11px; margin-left: 3mm;">
                Made In Indonesia 2026
              </span>
            </div>
          </div>

          <!-- Kanan: QR Code -->
          <div :id="'batch-label-print-qr-' + idx"
            style="flex-shrink: 0; width: 17mm; height: 17mm; padding: 1mm; display: flex; align-items: center; justify-content: center; background: #ffffff; box-sizing: border-box;">
          </div>
        </div>
      </div>

    </div>

@endverbatim
