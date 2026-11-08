@verbatim
    <!-- ================= MODAL CAROUSEL PETUNJUK GAMBAR TESTING POINT ================= -->
    <div v-if="showInstructionGuideModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">

        <!-- Header Modal -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <span class="bg-indigo-600 text-white font-mono font-black text-xs px-2.5 py-1 rounded-lg shadow-xs">
              Uji #{{ currentGuideTestPoint ? currentGuideTestPoint.test_num : '' }}
            </span>
            <div>
              <h3 class="font-black text-sm sm:text-base text-slate-900 leading-snug">
                Petunjuk: {{ currentGuideTestPoint ? (currentGuideTestPoint.instruction || currentGuideTestPoint.name) :
                '' }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                Kategori: {{ (currentGuideTestPoint && currentGuideTestPoint.category) || 'Umum / Fungsional' }}
              </p>
            </div>
          </div>
          <button @click="showInstructionGuideModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Carousel / Slider Body -->
        <div v-if="currentGuideImages.length > 0" class="space-y-3">
          <div
            class="relative bg-slate-950 rounded-2xl overflow-hidden flex items-center justify-center min-h-[300px] sm:min-h-[400px] max-h-[500px]">
            <img :src="currentGuideImages[guideCarouselIndex]" alt="Petunjuk Gambar"
              class="max-h-[480px] w-auto max-w-full object-contain mx-auto transition-all duration-300 select-none">

            <!-- Prev Button -->
            <button v-if="currentGuideImages.length > 1" type="button" @click="prevGuideSlide"
              class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white flex items-center justify-center transition active:scale-90 shadow-xl border border-white/20 cursor-pointer">
              <i class="fa-solid fa-chevron-left text-sm"></i>
            </button>

            <!-- Next Button -->
            <button v-if="currentGuideImages.length > 1" type="button" @click="nextGuideSlide"
              class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-slate-900/80 hover:bg-slate-900 text-white flex items-center justify-center transition active:scale-90 shadow-xl border border-white/20 cursor-pointer">
              <i class="fa-solid fa-chevron-right text-sm"></i>
            </button>

            <!-- Counter Badge -->
            <div
              class="absolute bottom-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-black/70 text-white text-xs font-mono font-bold border border-white/20">
              Gambar {{ guideCarouselIndex + 1 }} dari {{ currentGuideImages.length }}
            </div>
          </div>

          <!-- Dots Indicator -->
          <div v-if="currentGuideImages.length > 1" class="flex items-center justify-center gap-1.5 pt-1">
            <button v-for="(img, idx) in currentGuideImages" :key="idx" @click="guideCarouselIndex = idx"
              :class="guideCarouselIndex === idx ? 'bg-indigo-600 w-6' : 'bg-slate-300 w-2.5 hover:bg-slate-400'"
              class="h-2.5 rounded-full transition-all cursor-pointer"></button>
          </div>
        </div>

        <!-- Fallback if no images -->
        <div v-else
          class="bg-slate-50 border border-slate-200 rounded-2xl p-8 sm:p-12 text-center text-slate-500 space-y-2">
          <i class="fa-regular fa-image text-3xl sm:text-4xl text-slate-300"></i>
          <p class="font-bold text-slate-700 text-sm">Belum Ada Gambar Petunjuk</p>
          <p class="text-xs text-slate-500">Super Admin belum mengunggah gambar instruksi untuk pengujian tahap ini.</p>
        </div>

        <!-- Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
          <div class="min-w-0 flex-1 pr-3">
            <span class="text-slate-400 block uppercase text-[10px] font-bold">Kriteria Output:</span>
            <p class="text-slate-800 font-semibold whitespace-pre-line text-xs">{{ currentGuideTestPoint ?
              (currentGuideTestPoint.output ||
              currentGuideTestPoint.description) : '-' }}</p>
          </div>
          <button type="button" @click="showInstructionGuideModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer shrink-0">
            Tutup
          </button>
        </div>

      </div>
    </div>


    <!-- ================= MODAL VALIDASI SIMPAN PERUBAHAN (MASTER TESTING & KELOLA PENGGUNA) ================= -->
    <div v-if="showAdminUnsavedModal"
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100005] flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
        <div
          class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center text-xl shadow-xs">
          <i class="fa-solid fa-floppy-disk"></i>
        </div>
        <div class="text-center space-y-1">
          <h3 class="font-black text-base sm:text-lg text-slate-900">
            Apakah Anda ingin menyimpan perubahan?
          </h3>
          <p class="text-xs text-slate-500 font-medium">
            <template v-if="adminUnsavedContext === 'users'">
              Terdapat perubahan data pada formulir {{ editingUserId ? 'edit pengguna' : 'pengguna baru' }} yang belum
              disimpan.
            </template>
            <template v-else-if="adminUnsavedContext === 'testing_points'">
              Terdapat perubahan data pada formulir {{ editingTestingPointId ? 'edit testing point' : 'testing point baru' }} yang belum disimpan.
            </template>
            <template v-else>
              Terdapat perubahan input data yang belum disimpan.
            </template>
          </p>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-2">
          <button type="button" @click="confirmAdminUnsavedNavigation(false)" :disabled="isSavingAdminUnsaved"
            class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
            <i class="fa-solid fa-xmark"></i>
            <span>Tidak</span>
          </button>
          <button type="button" @click="confirmAdminUnsavedNavigation(true)" :disabled="isSavingAdminUnsaved"
            class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/25 transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 min-h-[42px] disabled:opacity-50">
            <i v-if="isSavingAdminUnsaved" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>Iya (Simpan)</span>
          </button>
        </div>
        <div class="pt-1 text-center">
          <button type="button" @click="cancelAdminUnsavedNavigation" :disabled="isSavingAdminUnsaved"
            class="text-[11px] text-slate-400 hover:text-slate-600 font-medium underline cursor-pointer">
            Batal (Tetap di Halaman Ini)
          </button>
        </div>
      </div>
    </div>


    <!-- ================= MODAL PROMPT SOURCE DOCUMENT MO SAAT SIMPAN TRAY ================= -->
    <div v-if="showPromptMOModal" @click.self="safeCloseModal(() => { showPromptMOModal = false; })"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-4 my-auto relative">
        <button @click="safeCloseModal(() => { showPromptMOModal = false; })"
          class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer"
          title="Tutup Modal">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>

        <div class="text-center space-y-2">
          <div
            class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl mx-auto flex items-center justify-center text-xl shadow-inner">
            <i class="fa-solid fa-file-invoice"></i>
          </div>
          <h3 class="font-black text-lg text-slate-900">Masukkan Source Document MO</h3>
          <p class="text-xs text-slate-500">Masukkan 6 digit angka akhir untuk generate nomor dokumen MO.</p>
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase text-slate-700 tracking-wider">
            Source document MO:
          </label>
          <div
            class="flex items-stretch rounded-xl overflow-hidden border border-slate-300 focus-within:border-red-600 focus-within:ring-2 focus-within:ring-red-100 shadow-sm">
            <span
              class="bg-slate-100 px-3.5 py-3 text-xs sm:text-sm text-slate-700 font-mono font-black border-r border-slate-300 select-none flex items-center">
              WH/MO/
            </span>
            <input type="text" ref="moInputRef" v-model="gudangSourceMOSuffix" @input="onSourceMOSuffixInput"
              @keyup.enter="confirmSaveGudangTray" maxlength="6" placeholder="000001"
              class="w-full bg-slate-50 focus:bg-white px-3.5 py-3 text-xs sm:text-sm text-slate-900 font-mono font-bold focus:outline-none transition min-h-[44px]">
          </div>
          <p class="text-[10px] text-slate-400 font-mono">Format otomatis: WH/MO/{{ gudangSourceMOSuffix ?
            gudangSourceMOSuffix.padStart(6, '0') : '000001' }}</p>
        </div>

        <div class="space-y-2 pt-2">
          <button @click="confirmSaveGudangTray"
            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-2 active:scale-[0.99] min-h-[44px]">
            <i class="fa-solid fa-check"></i>
            <span>Simpan</span>
          </button>

          <button @click="gudangSourceMOSuffix = ''; confirmSaveGudangTray()"
            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-xl text-xs transition flex items-center justify-center space-x-1.5 min-h-[40px]">
            <span>Lewati</span>
            <i class="fa-solid fa-forward text-slate-400"></i>
          </button>

          <button @click="showPromptMOModal = false"
            class="w-full bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-slate-700 font-semibold py-2 rounded-xl text-xs transition flex items-center justify-center space-x-1.5 min-h-[36px]">
            <i class="fa-solid fa-xmark text-slate-400"></i>
            <span>Batal</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL PREVIEW LABEL P9 (PRINT 80x20mm + QR CODE) ================= -->
    <div v-if="showLabelPreviewModal"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-black text-base text-slate-900">Output Cetak Label P9</h3>
            <p class="text-[11px] text-slate-500 font-mono">Format Thermal Sticker: 80mm &times; 20mm (SN: {{
              activeLabelSN.part15_sn || '-' }})</p>
          </div>
          <button @click="showLabelPreviewModal = false" class="text-slate-400 hover:text-slate-700 p-1.5">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Sticker Design 80x20mm Preview -->
        <div
          class="bg-slate-100 p-3 sm:p-5 rounded-2xl flex flex-col justify-center items-center overflow-x-auto space-y-2">
          <div class="bg-white border-2 border-slate-900 rounded-none shadow-md">
            <div id="label-printable-area" class="bg-white text-slate-900 select-none"
              style="width: 320px; height: 80px; padding: 5px 8px 5px 12px; box-sizing: border-box; display: flex; flex-direction: row; align-items: center; justify-content: space-between; overflow: hidden; background: #ffffff; font-family: 'Roboto', Arial, sans-serif;">

              <!-- Kiri: Info Block (Jarak Rapi, Proporsional & Elegan) -->
              <div
                style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; padding-right: 6px; box-sizing: border-box;">

                <!-- Baris 1: Logo NKRI -->
                <div style="display: flex; align-items: center; height: 14px; margin-bottom: 2px;">
                  <img :src="nkriLabelLogoBase64 || nkriLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                    alt="NKRI Logo"
                    style="height: 14px; max-height: 16px; width: auto; max-width: 90px; object-fit: contain; display: block;">
                </div>

                <!-- Baris 2: Nama Produk (Bold, Presisi - Digeser ke atas dikit) -->
                <div
                  style="font-size: 14px; font-weight: 900; color: #000000; line-height: 15px; font-family: 'Roboto', Arial, sans-serif; white-space: nowrap; margin-top: -1px; margin-bottom: 4.5px;">
                  {{ activeLabelSN.product_type || 'Nanggala P9' }}
                </div>

                <!-- Baris 3: Serial Number: (Ukuran Font Kecil, Jelas) -->
                <div
                  style="font-size: 7.5px; font-weight: 700; color: #000000; line-height: 9px; font-family: 'Roboto', Arial, sans-serif; margin-bottom: 2px;">
                  Serial Number:
                </div>

                <!-- Baris 4: SN Besar & Tebal + Made In Indonesia 2026 (Sedikit ke Tengah) -->
                <div
                  style="display: flex; align-items: baseline; justify-content: flex-start; width: 100%; white-space: nowrap; line-height: 16px;">
                  <span
                    style="font-size: 15px; font-weight: 900; color: #000000; font-family: 'Roboto', Arial, sans-serif; letter-spacing: 0.2px; line-height: 16px; flex-shrink: 0;">
                    {{ activeLabelSN.part15_sn || 'J1100001' }}
                  </span>
                  <span
                    style="font-size: 7.5px; font-weight: 700; color: #000000; font-family: 'Roboto', Arial, sans-serif; line-height: 16px; margin-left: 20px;">
                    Made In Indonesia 2026
                  </span>
                </div>

              </div>

              <!-- Kanan: Gambar QR Code Kotak Standar (Pas, Tajam & Mudah Di-scan) -->
              <div
                style="flex-shrink: 0; width: 68px; height: 68px; padding: 3px; display: flex; align-items: center; justify-content: center; background: #ffffff; box-sizing: border-box;">
                <div id="qrcode-container"
                  style="width: 62px; height: 62px; background: #ffffff; display: flex; align-items: center; justify-content: center;">
                </div>
              </div>

            </div>
          </div>

          <p class="text-[10px] text-slate-500 text-center font-medium">
            <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
            Ukuran label fisik: <strong>80mm &times; 20mm</strong> (Margin 0 / Pas Ukuran Stiker)
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-2.5 pt-2">
          <button @click="showLabelPreviewModal = false"
            class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 font-bold py-2.5 rounded-xl text-xs transition cursor-pointer min-h-[40px]">
            Tutup
          </button>
          <button @click="downloadP9LabelPDF"
            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-1.5 active:scale-95 cursor-pointer min-h-[40px]">
            <i class="fa-solid fa-file-arrow-down"></i>
            <span>Unduh PDF</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL CETAK LABEL BATCH (SEMUA SN ACC DALAM DOKUMEN) ================= -->
    <div v-if="showBatchLabelModal"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4"
      @click.self="showBatchLabelModal = false">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-amber-100 border border-amber-200 text-amber-600 flex items-center justify-center text-lg shadow-xs">
              <i class="fa-solid fa-tags"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="text-[10px] font-black px-2 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded uppercase">
                  Hanya SN Terverifikasi (ACC)
                </span>
                <span class="text-[10px] font-mono text-slate-500 font-bold">
                  Dokumen: {{ batchLabelDocInfo.docNumber || '-' }}
                </span>
              </div>
              <h3 class="text-base font-black text-slate-900 mt-0.5">
                Cetak Label Serial Number ({{ batchLabelItems.length }} Unit ACC)
              </h3>
            </div>
          </div>
          <button type="button" @click="showBatchLabelModal = false"
            class="w-8 h-8 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
            <i class="fa-solid fa-xmark text-sm"></i>
          </button>
        </div>

        <!-- Info alert banner -->
        <div
          class="bg-amber-50/70 border-b border-amber-200/70 px-5 py-2.5 flex items-center justify-between text-xs text-amber-900">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>Format Thermal Sticker <strong>80mm &times; 20mm</strong>. Menampilkan seluruh SN yang telah lolos
              verifikasi ACC pada dokumen ini.</span>
          </div>
          <span
            class="font-mono font-bold text-[11px] bg-white px-2 py-0.5 rounded border border-amber-200 text-amber-950">
            Total: {{ batchLabelItems.length }} Label
          </span>
        </div>

        <!-- Body: Preview Label Cards -->
        <div class="overflow-y-auto flex-1 p-4 sm:p-6 bg-slate-100/60 space-y-4">
          <div v-if="batchLabelItems.length === 0"
            class="bg-white p-8 text-center rounded-2xl border border-slate-200 text-slate-400">
            <i class="fa-solid fa-tags text-3xl mb-2 block"></i>
            <span>Tidak ada serial number berstatus ACC yang dapat dicetak.</span>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="(item, idx) in batchLabelItems" :key="idx"
              class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-xs flex flex-col items-center space-y-2">
              <div
                class="w-full flex items-center justify-between text-[11px] font-mono text-slate-500 border-b border-slate-100 pb-1.5">
                <span class="font-bold text-slate-800">#{{ idx + 1 }} &bull; SN: {{ item.sn }}</span>
                <span
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">ACC</span>
              </div>

              <!-- Exact 80x20mm thermal label layout -->
              <div class="bg-white border-2 border-slate-900 rounded-none shadow-sm overflow-hidden">
                <div :id="'batch-label-card-' + idx" class="bg-white text-slate-900 select-none"
                  style="width: 320px; height: 80px; padding: 5px 8px 5px 12px; box-sizing: border-box; display: flex; flex-direction: row; align-items: center; justify-content: space-between; overflow: hidden; background: #ffffff; font-family: 'Roboto', Arial, sans-serif;">
                  <!-- Kiri: Info Block -->
                  <div
                    style="flex: 1; display: flex; flex-direction: column; justify-content: flex-start; min-width: 0; padding-right: 6px; box-sizing: border-box;">
                    <!-- Baris 1: Logo NKRI -->
                    <div style="display: flex; align-items: center; height: 14px; margin-bottom: 2px;">
                      <img :src="nkriLabelLogoBase64 || nkriLogoBase64 || (window.NKRI_LOGO || 'nkri-logo.png')"
                        alt="NKRI Logo"
                        style="height: 14px; max-height: 16px; width: auto; max-width: 90px; object-fit: contain; display: block;">
                    </div>
                    <div
                      style="font-size: 14px; font-weight: 900; color: #000000; line-height: 15px; font-family: 'Roboto', Arial, sans-serif; white-space: nowrap; margin-top: -1px; margin-bottom: 4.5px;">
                      {{ item.product_type || 'Nanggala P9' }}
                    </div>
                    <div
                      style="font-size: 7.5px; font-weight: 700; color: #000000; line-height: 9px; font-family: 'Roboto', Arial, sans-serif; margin-bottom: 2px;">
                      Serial Number:
                    </div>
                    <div
                      style="display: flex; align-items: baseline; justify-content: flex-start; width: 100%; white-space: nowrap; line-height: 16px;">
                      <span
                        style="font-size: 15px; font-weight: 900; color: #000000; font-family: 'Roboto', Arial, sans-serif; letter-spacing: 0.2px; line-height: 16px; flex-shrink: 0;">
                        {{ item.sn }}
                      </span>
                      <span
                        style="font-size: 7.5px; font-weight: 700; color: #000000; font-family: 'Roboto', Arial, sans-serif; line-height: 16px; margin-left: 20px;">
                        Made In Indonesia 2026
                      </span>
                    </div>
                  </div>
                  <!-- Kanan: QR Code -->
                  <div
                    style="flex-shrink: 0; width: 68px; height: 68px; padding: 3px; display: flex; align-items: center; justify-content: center; background: #ffffff; box-sizing: border-box;">
                    <div :id="'batch-label-preview-qr-' + idx"
                      style="width: 62px; height: 62px; background: #ffffff; display: flex; align-items: center; justify-content: center;">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Quick action for single card -->
              <div class="w-full flex items-center justify-end pt-1">
                <button type="button" @click="downloadSingleLabelFromBatch(item, idx)"
                  class="text-[10px] text-slate-500 hover:text-slate-900 font-bold flex items-center gap-1 cursor-pointer">
                  <i class="fa-solid fa-file-arrow-down text-slate-400"></i> Unduh PDF Unit Ini
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div
          class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
          <div class="text-xs text-slate-600 font-medium">
            Total Dokumen: <strong class="text-slate-900 font-mono">{{ batchLabelItems.length }} Unit ACC</strong>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" @click="showBatchLabelModal = false"
              class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
              Tutup
            </button>
            <button type="button" @click="downloadBatchLabelsPDF"
              :disabled="isGeneratingBatchPDF || batchLabelItems.length === 0"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md shadow-red-600/20 active:scale-95 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
              <i v-if="isGeneratingBatchPDF" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-file-arrow-down"></i>
              <span>Unduh Semua Label (PDF)</span>
            </button>
            <button type="button" @click="printBatchLabelsDirect" :disabled="batchLabelItems.length === 0"
              class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-md shadow-amber-600/20 active:scale-95 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
              <i class="fa-solid fa-print"></i>
              <span>Cetak Semua Label (Thermal)</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL LIHAT DETAIL RAKITAN (SETELAH SELESAI) ================= -->
    <div v-if="showDetailModal && activeDetailSlot" @click.self="showDetailModal = false"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] overflow-y-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-3">
            <div
              class="bg-slate-900 text-white w-9 h-9 rounded-xl font-bold flex items-center justify-center shadow-md text-sm flex-shrink-0 font-mono">
              #{{ activeDetailSlot.set_number }}
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900">
                <template v-if="activeDetailSlot.process_stage === 'assy_test'">
                  Detail Uji Fungsional 1 (TF 1) Set #{{ activeDetailSlot.set_number }}
                </template>
                <template v-else-if="activeDetailSlot.process_stage === 'qc_test'">
                  Detail Quality Control (QC) Set #{{ activeDetailSlot.set_number }}
                </template>
                <template v-else-if="activeDetailSlot.process_stage === 'warehouse_receipt'">
                  Detail Penerimaan Gudang Set #{{ activeDetailSlot.set_number }}
                </template>
                <template v-else>
                  Detail Perakitan Set #{{ activeDetailSlot.set_number }}
                </template>
              </h3>
              <p class="text-[11px] text-slate-500 font-mono">Tray: {{ activeDetailSlot.tray_code }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup Modal">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3.5">
          <!-- 1. Kode Jadinya (Identitas Produk Jadi & QR Code) -->
          <div
            class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 sm:p-4 flex items-center justify-between shadow-sm">
            <div class="space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kode Jadi /
                Produk:</span>
              <div class="text-base sm:text-lg font-black text-slate-900 leading-tight font-sans">
                {{ activeDetailSlot.product_type || 'Nanggala P9' }}
              </div>
              <div class="font-mono text-xl font-black text-red-600 tracking-wider">
                {{ activeDetailSlot.part15_sn || '-' }}
              </div>
              <span :class="getStatusBadgeClass(activeDetailSlot.status)"
                class="inline-block text-[10px] font-black uppercase px-2.5 py-0.5 rounded-lg border">
                Status: {{ activeDetailSlot.status }}
              </span>
            </div>
            <div class="flex-shrink-0 bg-white p-2 border border-slate-200 rounded-xl shadow-inner">
              <div id="detail-qrcode-container" class="w-[72px] h-[72px] flex items-center justify-center"></div>
            </div>
          </div>

          <!-- 2. Waktu Pengerjaan: Jam Mulai & Jam Selesai -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <i class="fa-regular fa-clock text-red-600"></i>
              <span>Riwayat Waktu Pengerjaan</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs font-mono">
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] font-sans font-bold text-slate-400 block uppercase">Jam Mulai:</span>
                <strong class="text-slate-900">{{ formatDateTime(activeDetailSlot?.started_at) }}</strong>
              </div>
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] font-sans font-bold text-slate-400 block uppercase">Jam Selesai:</span>
                <strong class="text-slate-900">{{ formatDateTime(activeDetailSlot?.finished_at,
                  activeDetailSlot?.started_at) }}</strong>
              </div>
            </div>
            <div
              class="text-[11px] text-slate-600 bg-white px-2.5 py-1.5 rounded-lg border border-slate-200 flex justify-between items-center">
              <span class="font-sans font-medium text-slate-500">Total Durasi Perakitan:</span>
              <strong class="font-mono font-bold text-slate-900">{{ formatDuration(activeDetailSlot?.started_at,
                activeDetailSlot?.finished_at) }}</strong>
            </div>
          </div>

          <!-- 3. Kode Per Part (Komponen Penyusun) -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2">
            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
              <i class="fa-solid fa-cubes-stacked text-red-600"></i>
              <span>Kode Part Penyusun</span>
            </div>
            <div class="space-y-1.5 text-xs font-mono">
              <div :class="getDefectivePartClass(activeDetailSlot, '15')"
                class="flex justify-between items-center p-2 rounded-lg border transition-all">
                <div class="flex items-center gap-1.5">
                  <span :class="getDefectiveLabelClass(activeDetailSlot, '15')"
                    class="font-sans font-bold text-[11px]">Part 15:</span>
                  <span v-if="isPartDefective(activeDetailSlot, '15')" :class="getDefectiveBadgeClass(activeDetailSlot)"
                    class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded uppercase">
                    {{ activeDetailSlot.status }}
                  </span>
                  <span v-else-if="activeDetailSlot.status && activeDetailSlot.status !== 'Good'"
                    class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                    <i class="fa-solid fa-check text-[8px]"></i> Aman
                  </span>
                </div>
                <strong
                  :class="isPartDefective(activeDetailSlot, '15') ? 'text-slate-950 font-black' : 'text-slate-900'">{{
                  activeDetailSlot.part15_sn || '-' }}</strong>
              </div>

              <div :class="getDefectivePartClass(activeDetailSlot, '1')"
                class="flex justify-between items-center p-2 rounded-lg border transition-all">
                <div class="flex items-center gap-1.5">
                  <span :class="getDefectiveLabelClass(activeDetailSlot, '1')"
                    class="font-sans font-bold text-[11px]">Part 1:</span>
                  <span v-if="isPartDefective(activeDetailSlot, '1')" :class="getDefectiveBadgeClass(activeDetailSlot)"
                    class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded uppercase">
                    {{ activeDetailSlot.status }}
                  </span>
                  <span v-else-if="activeDetailSlot.status && activeDetailSlot.status !== 'Good'"
                    class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                    <i class="fa-solid fa-check text-[8px]"></i> Aman
                  </span>
                </div>
                <strong
                  :class="isPartDefective(activeDetailSlot, '1') ? 'text-slate-950 font-black' : 'text-slate-800'">{{
                  activeDetailSlot.part1_sn || '-' }}</strong>
              </div>

              <div :class="getDefectivePartClass(activeDetailSlot, '12')"
                class="flex justify-between items-center p-2 rounded-lg border transition-all">
                <div class="flex items-center gap-1.5">
                  <span :class="getDefectiveLabelClass(activeDetailSlot, '12')"
                    class="font-sans font-bold text-[11px]">Part 12:</span>
                  <span v-if="isPartDefective(activeDetailSlot, '12')" :class="getDefectiveBadgeClass(activeDetailSlot)"
                    class="text-[9px] font-sans font-black px-1.5 py-0.5 rounded uppercase">
                    {{ activeDetailSlot.status }}
                  </span>
                  <span v-else-if="activeDetailSlot.status && activeDetailSlot.status !== 'Good'"
                    class="text-[9px] font-sans font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                    <i class="fa-solid fa-check text-[8px]"></i> Aman
                  </span>
                </div>
                <strong
                  :class="isPartDefective(activeDetailSlot, '12') ? 'text-slate-950 font-black' : 'text-slate-800'">{{
                  activeDetailSlot.part12_sn || '-' }}</strong>
              </div>
            </div>
          </div>

          <!-- 4. Catatan / Alasan Status (Jika ada) -->
          <div
            v-if="activeDetailSlot.reason || activeDetailSlot.rework_reason || activeDetailSlot.qc_failure_reasons || activeDetailSlot.assy_failure_reasons"
            class="p-3 rounded-xl border text-xs space-y-1 bg-amber-50/80 border-amber-200 text-amber-950">
            <div class="font-bold flex items-center justify-between uppercase text-[10px] text-amber-900">
              <span class="flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation"></i>
                Catatan / Alasan Kendala:
              </span>
            </div>
            <p class="font-medium italic leading-relaxed">{{ formatQCReworkReason(activeDetailSlot) ||
              activeDetailSlot.rework_reason || activeDetailSlot.assy_failure_reasons ||
              activeDetailSlot.qc_failure_reasons || activeDetailSlot.reason }}</p>
          </div>

          <!-- 4.5a: Hasil Pengujian Fungsional Perakitan (TF1) -->
          <div
            v-if="isTF1ResultCardVisible(activeDetailSlot)"
            class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2">
            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-list-check text-indigo-600"></i>
                <span>Hasil Pengujian Fungsional Perakitan (TF1)</span>
              </div>
              <span
                v-if="activeDetailSlot.tf1_doc_number || (activeDetailSlot.assy_doc_number && activeDetailSlot.assy_doc_number.toUpperCase().startsWith('ASSY/QC')) || (activeDetailSlot.process_stage === 'assy_test' && activeDetailSlot.doc_number)"
                class="font-mono text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded">
                {{ activeDetailSlot.tf1_doc_number || (activeDetailSlot.assy_doc_number &&
                activeDetailSlot.assy_doc_number.toUpperCase().startsWith('ASSY/QC') ? activeDetailSlot.assy_doc_number
                : (activeDetailSlot.process_stage === 'assy_test' ? activeDetailSlot.doc_number : '-')) }}
              </span>
            </div>

            <!-- Mini 17 Stages Badges Grid / Menunggu Uji TF 1 -->
            <div
              v-if="!activeDetailSlot.is_tf1_tested"
              class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-center gap-2 font-medium">
              <i class="fa-solid fa-clock text-amber-600"></i>
              <span>Status TF 1: <strong>Menunggu Uji TF 1</strong> (Unit rakitan belum dilakukan pengujian fungsional oleh operator TF 1).</span>
            </div>
            <div
              v-else-if="activeDetailSlot.is_tf1_tested && ((activeDetailSlot.parsed_tf1_checklist && activeDetailSlot.parsed_tf1_checklist.length) || (activeDetailSlot.process_stage === 'assy_test' && activeDetailSlot.parsed_checklist && activeDetailSlot.parsed_checklist.length))"
              class="grid grid-cols-5 gap-1 text-[8.5px] font-mono">
              <div
                v-for="t in (activeDetailSlot.parsed_tf1_checklist && activeDetailSlot.parsed_tf1_checklist.length ? activeDetailSlot.parsed_tf1_checklist : activeDetailSlot.parsed_checklist)"
                :key="t.test_num"
                :class="String(t.status || '').toUpperCase() === 'GOOD' ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : (String(t.status || '').toUpperCase() === 'NOT_GOOD' ? 'bg-red-50 text-red-800 border-red-300' : 'bg-slate-50 text-slate-600 border-slate-200')"
                class="p-1.5 rounded border text-center font-bold flex items-center justify-center gap-0.5 shadow-2xs"
                :title="'Hasil Test #' + t.test_num + ': ' + (String(t.status || '').toUpperCase() === 'GOOD' ? 'Good' : (String(t.status || '').toUpperCase() === 'NOT_GOOD' ? 'Not Good' : 'Pending'))">
                <span class="text-[8px] opacity-75">T{{ t.test_num }}:</span>
                <span v-if="String(t.status || '').toUpperCase() === 'GOOD'"
                  class="text-emerald-700 font-black text-[9px]">&#10003;</span>
                <span v-else-if="String(t.status || '').toUpperCase() === 'NOT_GOOD'"
                  class="text-red-600 font-black text-[9px]">&#10007;</span>
                <span v-else class="text-slate-400 font-black text-[9px]">-</span>
              </div>
            </div>

            <!-- Summary count TF1 & Toggle Detail -->
            <div
              v-if="activeDetailSlot.is_tf1_tested && ((activeDetailSlot.parsed_tf1_checklist && activeDetailSlot.parsed_tf1_checklist.length) || (activeDetailSlot.process_stage === 'assy_test' && activeDetailSlot.parsed_checklist && activeDetailSlot.parsed_checklist.length))"
              class="flex items-center justify-between text-[10px] pt-1 border-t border-slate-200/80">
              <div class="flex items-center gap-2">
                <span v-if="(activeDetailSlot.tf1_good_count || activeDetailSlot.checklist_good_count || 0) > 0"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold">
                  <i class="fa-solid fa-check text-[8px]"></i>
                  {{ activeDetailSlot.tf1_good_count || activeDetailSlot.checklist_good_count || 0 }} Good
                </span>
                <span v-if="(activeDetailSlot.tf1_fail_count || activeDetailSlot.checklist_fail_count || 0) > 0"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-red-100 text-red-800 border border-red-300 font-bold">
                  <i class="fa-solid fa-xmark text-[8px]"></i>
                  {{ activeDetailSlot.tf1_fail_count || activeDetailSlot.checklist_fail_count }} Not Good
                </span>
                <span v-if="(activeDetailSlot.tf1_pending_count || activeDetailSlot.checklist_pending_count || 0) > 0"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-300 font-bold">
                  <i class="fa-solid fa-minus text-[8px]"></i>
                  {{ activeDetailSlot.tf1_pending_count || activeDetailSlot.checklist_pending_count }} Pending
                </span>
              </div>
              <button type="button" @click="showDetailChecklistFull = !showDetailChecklistFull"
                class="text-indigo-600 hover:text-indigo-800 font-bold text-[10.5px] flex items-center gap-1 cursor-pointer transition">
                <span>{{ showDetailChecklistFull ? 'Tutup Rincian' : 'Lihat ' + (activeDetailSlot.parsed_tf1_checklist?.length || activeDetailSlot.parsed_checklist?.length || 17) + ' Parameter Uji' }}</span>
                <i :class="showDetailChecklistFull ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'" class="text-[9px]"></i>
              </button>
            </div>

            <!-- Tabel Rincian 17 Parameter Pengujian Fungsional TF 1 -->
            <div v-if="showDetailChecklistFull && (activeDetailSlot.parsed_tf1_checklist?.length || activeDetailSlot.parsed_checklist?.length)"
              class="mt-2 space-y-1.5 border-t border-slate-200 pt-2 max-h-56 overflow-y-auto animate-fadeIn">
              <div v-for="t in (activeDetailSlot.parsed_tf1_checklist?.length ? activeDetailSlot.parsed_tf1_checklist : activeDetailSlot.parsed_checklist)" :key="t.test_num"
                class="p-2 rounded-xl border text-xs flex items-start justify-between gap-2.5 transition"
                :class="String(t.status || '').toUpperCase() === 'GOOD' ? 'bg-emerald-50/50 border-emerald-200' : (String(t.status || '').toUpperCase() === 'NOT_GOOD' ? 'bg-red-50/70 border-red-200' : 'bg-white border-slate-200')">
                <div class="space-y-0.5 min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-mono font-black text-[10px] px-1.5 py-0.2 rounded"
                      :class="String(t.status || '').toUpperCase() === 'GOOD' ? 'bg-emerald-200 text-emerald-900' : 'bg-slate-200 text-slate-700'">
                      #{{ t.test_num }}
                    </span>
                    <span class="font-bold text-slate-900 text-[11px]">{{ t.name }}</span>
                    <span v-if="t.category" class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold uppercase tracking-wider">{{ t.category }}</span>
                  </div>
                  <p v-if="t.description" class="text-[10px] text-slate-500 leading-tight whitespace-pre-line">{{ t.description }}</p>
                </div>
                <span class="px-2 py-0.5 rounded font-black text-[10px] uppercase shrink-0"
                  :class="String(t.status || '').toUpperCase() === 'GOOD' ? 'bg-emerald-600 text-white' : (String(t.status || '').toUpperCase() === 'NOT_GOOD' ? 'bg-red-600 text-white' : 'bg-slate-200 text-slate-700')">
                  {{ t.status }}
                </span>
              </div>
            </div>
          </div>

          <!-- 4.5b: Hasil Quality Control & Test Functional 2 (TF2) -->
          <div v-if="isQCResultCardVisible(activeDetailSlot)"
            class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2.5">
            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-microscope text-emerald-600"></i>
                <span>Hasil Quality Control &amp; Test Functional 2 (TF2)</span>
              </div>
              <span
                v-if="activeDetailSlot.qc_doc_number || (activeDetailSlot.process_stage === 'qc_test' && activeDetailSlot.doc_number)"
                class="font-mono text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded">
                {{ activeDetailSlot.qc_doc_number || (activeDetailSlot.process_stage === 'qc_test' ?
                activeDetailSlot.doc_number : '-') }}
              </span>
            </div>

            <!-- Laporan Hasil QC: Good / Not Good / Menunggu QC -->
            <div class="flex items-center gap-2">
              <span v-if="getQCReportStatus(activeDetailSlot).state === 'good'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-black text-xs shadow-xs">
                <i class="fa-solid fa-circle-check"></i>
                Hasil QC: Good
              </span>
              <span v-else-if="getQCReportStatus(activeDetailSlot).state === 'not_good'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 text-white font-black text-xs shadow-xs">
                <i class="fa-solid fa-circle-xmark"></i>
                Hasil QC: Not Good
              </span>
              <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 text-white font-black text-xs shadow-xs">
                <i class="fa-solid fa-clock"></i>
                Hasil QC: Menunggu QC
              </span>
            </div>


            <!-- Detail Info QC Inspector & Waktu Uji -->
            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/70">
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] text-slate-400 font-bold block">Inspector QC:</span>
                <strong class="text-slate-800 font-semibold">{{ activeDetailSlot.qc_tester_name ||
                  activeDetailSlot.tester_full_name || activeDetailSlot.qc_tested_by || '-' }}</strong>
              </div>
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] text-slate-400 font-bold block">Waktu Pengujian QC:</span>
                <strong class="text-slate-800 font-mono text-[11px]">{{ activeDetailSlot.qc_tested_at ?
                  formatDateTime(activeDetailSlot.qc_tested_at) : (activeDetailSlot.process_stage === 'qc_test' &&
                  activeDetailSlot.created_at ? formatDateTime(activeDetailSlot.created_at) : '-') }}</strong>
              </div>
            </div>

            <!-- Catatan / Kendala QC (Jika Ada) -->
            <div
              v-if="activeDetailSlot.qc_failure_reasons || (activeDetailSlot.process_stage === 'qc_test' && (activeDetailSlot.failure_reasons || activeDetailSlot.notes))"
              class="bg-rose-50 border border-rose-200 rounded-lg p-2 text-xs text-rose-800">
              <span class="font-bold block text-[10px] uppercase">Catatan / Kendala QC:</span>
              <p class="italic">{{ formatFailureReason(activeDetailSlot.qc_failure_reasons ||
                activeDetailSlot.failure_reasons || activeDetailSlot.notes) }}</p>
            </div>
          </div>

          <!-- 4.5c: Good Receipts (WH/GR) — Warehouse Receipt -->
          <div v-if="isWarehouseReceiptVisible(activeDetailSlot)"
            class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2.5">
            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-warehouse text-blue-600"></i>
                <span>Good Receipts (WH/GR)</span>
              </div>
              <span v-if="activeDetailSlot.warehouse_doc_number"
                class="font-mono text-[10px] font-black text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded">
                {{ activeDetailSlot.warehouse_doc_number }}
              </span>
            </div>

            <!-- Status Keputusan Gudang -->
            <div class="flex items-center gap-2">
              <span
                v-if="String(activeDetailSlot.warehouse_receipt_status || '').toUpperCase() === 'ACC' || String(activeDetailSlot.warehouse_receipt_status || '').toUpperCase() === 'ACCEPTED'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-black text-xs shadow-sm">
                <i class="fa-solid fa-circle-check"></i>
                DITERIMA GUDANG (ACC)
              </span>
              <span
                v-else-if="String(activeDetailSlot.warehouse_receipt_status || '').toUpperCase() === 'DECLINED' || String(activeDetailSlot.warehouse_receipt_status || '').toUpperCase() === 'DECLINE'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-600 text-white font-black text-xs shadow-sm">
                <i class="fa-solid fa-circle-xmark"></i>
                DITOLAK GUDANG (DECLINE)
              </span>
              <span v-else
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 text-white font-black text-xs shadow-sm">
                <i class="fa-solid fa-clock"></i>
                MENUNGGU RECEIPTS (WH/GR)
              </span>
            </div>

            <!-- Detail Info Gudang -->
            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-slate-200/70">
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] text-slate-400 font-bold block">Petugas Penerima:</span>
                <strong class="text-slate-800 font-semibold">{{ activeDetailSlot.warehouse_receipt_by ||
                  activeDetailSlot.pic_warehouse || '-' }}</strong>
              </div>
              <div class="bg-white p-2 rounded-lg border border-slate-200">
                <span class="text-[10px] text-slate-400 font-bold block">Waktu Verifikasi:</span>
                <strong class="text-slate-800 font-mono text-[11px]">{{ activeDetailSlot.warehouse_receipt_at ?
                  formatDateTime(activeDetailSlot.warehouse_receipt_at) : '-' }}</strong>
              </div>
            </div>

            <!-- Alasan Penolakan Gudang (Jika Ada) -->
            <div v-if="activeDetailSlot.warehouse_decline_reason"
              class="bg-rose-50 border border-rose-200 rounded-lg p-2 text-xs text-rose-800">
              <span class="font-bold block text-[10px] uppercase">Alasan Penolakan:</span>
              <p class="italic">{{ activeDetailSlot.warehouse_decline_reason }}</p>
            </div>
          </div>

          <!-- 5. PIC Operator -->
          <div
            class="flex items-center justify-between text-xs bg-slate-50 p-3 rounded-xl border border-slate-200 text-slate-600">
            <span class="font-medium text-slate-500">
              {{ activeDetailSlot.process_stage === 'assy_test' ? 'Operator Tester TF 1:' : (activeDetailSlot.process_stage === 'qc_test' ? 'Inspector Quality Control:' : (activeDetailSlot.process_stage === 'warehouse_receipt' ? 'Petugas Gudang:' : 'Operator Perakitan (PIC):')) }}
            </span>
            <strong class="text-slate-900 font-bold">
              {{ activeDetailSlot.process_stage === 'assy_test' ? (activeDetailSlot.pic_tester || activeDetailSlot.pic_assembly_name || '-') : (activeDetailSlot.process_stage === 'qc_test' ? (activeDetailSlot.qc_tester_name || activeDetailSlot.pic_tester || '-') : (activeDetailSlot.process_stage === 'warehouse_receipt' ? (activeDetailSlot.warehouse_receipt_by || activeDetailSlot.pic_warehouse || '-') : (activeDetailSlot.pic_assembly_name || activeDetailSlot.pic_assembly || '-'))) }}
            </strong>
          </div>
        </div>

        <!-- Modal Footer: HANYA ADA TOMBOL KELUAR / X -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
          <button @click="showDetailModal = false"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-2.5 rounded-xl text-xs transition active:scale-95 cursor-pointer">
            Tutup
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL KONFIRMASI RESET DATABASE TRAY (ADMIN ONLY) ================= -->
    <div v-if="showResetDbModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto text-center">
        <div
          class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner">
          <i class="fa-solid fa-trash-can"></i>
        </div>

        <div class="space-y-1.5">
          <h3 class="font-black text-lg text-slate-900">Kosongkan Database Tray & Rakitan?</h3>
          <p class="text-xs text-slate-500 leading-relaxed">
            Tindakan ini akan menghapus semua dokumen Tray, alokasi Part, dan data Perakitan. Seluruh stok master akan
            kembali menjadi <strong>Tersedia</strong>.
          </p>
        </div>

        <div
          class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-[11px] text-amber-800 font-medium text-left flex items-start gap-2">
          <i class="fa-solid fa-circle-exclamation text-amber-600 text-sm flex-shrink-0 mt-0.5"></i>
          <span>Data akun pengguna dan master stock 2.000 Part 15 tidak akan terhapus.</span>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-2">
          <button @click="showResetDbModal = false" :disabled="isResettingDb"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl text-xs transition">
            Batal
          </button>
          <button @click="confirmResetDatabase" :disabled="isResettingDb"
            class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-1.5 active:scale-95">
            <i v-if="isResettingDb" class="fa-solid fa-spinner fa-spin"></i>
            <span v-else><i class="fa-solid fa-check mr-1"></i> Ya, Kosongkan</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= POPUP NOTIFIKASI HASIL BUAT TRAY GUDANG ================= -->
    <div v-if="showTrayResultPopup"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-7 shadow-2xl space-y-5 my-auto text-center">
        <div
          class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner">
          <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="space-y-1">
          <h3 class="font-black text-lg sm:text-xl text-slate-900">Tray Berhasil Dibuat!</h3>
          <p class="text-xs text-slate-500">Nomor Tray Dokumen Unik telah terdaftar di database:</p>
        </div>

        <div class="bg-slate-50 border-2 border-dashed border-red-300 p-4 rounded-2xl space-y-1.5">
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">NOMOR DOKUMEN TRAY</span>
          <div class="text-2xl sm:text-3xl font-mono font-black text-red-700 tracking-wider">
            {{ newTrayResult.tray_code }}
          </div>
          <div class="text-[11px] text-slate-600 pt-1 font-semibold space-y-0.5">
            <div>PIC Warehouse: <strong>{{ newTrayResult.pic_warehouse }}</strong></div>
            <div v-if="newTrayResult.source_document_mo">Source MO: <strong class="font-mono text-red-800">{{
                newTrayResult.source_document_mo }}</strong></div>
            <div>Ditugaskan ke: <strong class="text-slate-800">{{ newTrayResult.assigned_operator || 'Semua Operator'
                }}</strong></div>
          </div>
        </div>

        <div class="space-y-2 pt-1">
          <button @click="printTrayLabel"
            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-2 active:scale-[0.99] min-h-[40px]">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Dokumen Supply</span>
          </button>
          <button @click="startAssemblyFromPopup"
            class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs shadow-sm transition flex items-center justify-center space-x-2 active:scale-[0.99] min-h-[40px]">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <span>Mulai Perakitan</span>
          </button>
          <button @click="closeTrayResultPopup"
            class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 font-bold py-2 rounded-xl text-xs shadow-sm transition min-h-[36px]">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ================= POPUP DETAIL PARTS TRAY ================= -->
    <div v-if="showTrayDetailModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base sm:text-lg font-black text-slate-900">Detail Parts Tray: <span
                class="text-red-700 font-mono">{{ trayDetailData.tray ? trayDetailData.tray.tray_code : '' }}</span>
            </h3>
            <p class="text-[11px] sm:text-xs text-slate-500">
              PIC: {{ trayDetailData.tray ? trayDetailData.tray.pic_warehouse : '-' }} |
              MO: <span class="font-mono font-bold text-slate-700">{{ trayDetailData.tray &&
                trayDetailData.tray.source_document_mo ? trayDetailData.tray.source_document_mo : '-' }}</span> |
              Untuk: {{ trayDetailData.tray && trayDetailData.tray.assigned_operator ?
              trayDetailData.tray.assigned_operator : 'Semua Operator' }}
            </p>
          </div>
          <button @click="showTrayDetailModal = false" class="text-slate-400 hover:text-slate-700 p-1.5">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="max-h-72 sm:max-h-80 overflow-y-auto rounded-xl border border-slate-200">
          <table class="w-full text-left text-xs min-w-[500px]">
            <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 sticky top-0 text-[11px]">
              <tr>
                <th class="p-2.5 sm:p-3 text-center w-12">Set #</th>
                <th class="p-2.5 sm:p-3">SN P9 (Part 15)</th>
                <th class="p-2.5 sm:p-3">P9 Part 1</th>
                <th class="p-2.5 sm:p-3">P9 Part 12</th>
                <th class="p-2.5 sm:p-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px]">
              <tr v-if="!trayDetailData.sets || trayDetailData.sets.length === 0">
                <td colspan="5" class="p-6 text-center text-slate-400 font-sans">
                  Belum ada data set pada Work Order ini.
                </td>
              </tr>
              <tr v-for="(s, i) in (trayDetailData.sets || [])" :key="s.set_number || i"
                class="hover:bg-blue-50/50 transition">
                <td class="p-2.5 sm:p-3 text-center font-bold text-slate-400 font-sans">{{ s.set_number || (i + 1) }}
                </td>
                <td class="p-2.5 sm:p-3 font-bold text-slate-900">
                  <span
                    class="bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded font-mono font-black text-xs">
                    {{ s.part15_sn || '-' }}
                  </span>
                </td>
                <td class="p-2.5 sm:p-3 font-semibold text-slate-700">{{ s.part1_sn || '-' }}</td>
                <td class="p-2.5 sm:p-3 font-semibold text-slate-700">{{ s.part12_sn || '-' }}</td>
                <td class="p-2.5 sm:p-3 text-center font-sans">
                  <span
                    :class="(s.status === 'Done' || s.status === 'Good') ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-blue-700 bg-blue-50 border-blue-200'"
                    class="px-2 py-0.5 rounded border text-[10px] sm:text-[11px] font-bold uppercase">
                    {{ s.status || 'Tersedia' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pt-2 flex justify-end">
          <button @click="showTrayDetailModal = false"
            class="bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-xs font-bold min-h-[38px]">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DRILLDOWN STATUS PART DI MASTER DB ================= -->
    <div v-if="showStatusDrillModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-shrink-0">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg font-black"
              :class="getStatusBadgeClass(drillData.status)">
              <i v-if="drillData.status === 'Good'" class="fa-solid fa-circle-check"></i>
              <i v-else-if="drillData.status === 'Hold'" class="fa-solid fa-hand"></i>
              <i v-else-if="drillData.status === 'Scrap'" class="fa-solid fa-trash-can"></i>
              
              <i v-else-if="drillData.status === 'Supplied'" class="fa-solid fa-boxes-packing"></i>
              <i v-else class="fa-solid fa-cubes"></i>
            </div>
            <div>
              <div class="flex items-center space-x-2">
                <h3 class="font-black text-base sm:text-lg text-slate-900">
                  Data Part {{ drillData.part_type ? drillData.part_type : 'Semua' }} &bull; Status: {{ drillData.status
                  }}
                </h3>
                <span
                  class="bg-slate-100 text-slate-700 text-xs font-black px-2.5 py-0.5 rounded-full border border-slate-200 font-mono">
                  {{ filteredDrillList.length }} Item
                </span>
              </div>
              <p class="text-xs text-slate-500">Daftar lengkap seluruh barang dengan status <strong>{{ drillData.status
                  }}</strong> pada Master Database.</p>
            </div>
          </div>
          <button @click="showStatusDrillModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup Modal">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Live Filter & Search inside Modal -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 flex-shrink-0 pt-1">
          <div class="relative flex-grow">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
            <input type="text" v-model="drillSearchQuery" @input="onDrillSearchInput"
              placeholder="Cari SN / No. Tray / Source MO / Operator..."
              class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-9 pr-4 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-600 min-h-[38px]">
          </div>
          <div class="flex items-center gap-2">
            <button @click="exportDrillListToExcel"
              class="bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 cursor-pointer">
              <i class="fa-solid fa-file-excel text-emerald-600"></i>
              <span>Ekspor Excel</span>
            </button>
            <button @click="applyFilterFromDrilldown"
              class="bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 min-h-[38px] active:scale-95 shadow-md shadow-red-600/25">
              <i class="fa-solid fa-filter"></i>
              <span>Tampilkan di Tabel Utama</span>
            </button>
          </div>
        </div>

        <!-- Table Container with Scroll -->
        <div class="overflow-y-auto overflow-x-auto flex-grow rounded-xl border border-slate-200 max-h-[55vh]">
          <table class="w-full text-left text-xs min-w-[780px]">
            <thead class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-200 sticky top-0 z-10">
              <tr>
                <th class="p-2.5 w-12 text-center">No</th>
                <th class="p-2.5">Part Type</th>
                <th class="p-2.5">Nomor Produksi / SN</th>
                <th class="p-2.5">Kode CS</th>
                <th class="p-2.5">Status</th>
                <th class="p-2.5">Catatan / Alasan</th>
                <th class="p-2.5">No. Tray & MO</th>
                <th class="p-2.5">Operator Assembly</th>
                <th class="p-2.5 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px]">
              <tr v-for="(item, idx) in filteredDrillList" :key="item.id" @click="openMasterPartTrackingModal(item)"
                class="hover:bg-red-50/40 transition-colors group cursor-pointer"
                title="Klik untuk melihat detail tracking & riwayat part ini">
                <td class="p-2.5 text-center font-bold text-slate-400 font-sans">{{ idx + 1 }}</td>
                <td class="p-2.5 font-sans font-bold text-red-700">
                  <div>P9 Part {{ item.part_type }}</div>
                  <span v-if="item.is_replaced_from_assy"
                    class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[8.5px] bg-amber-100 text-amber-800 font-bold border border-amber-200 uppercase tracking-tight">
                    Part Diganti
                  </span>
                </td>
                <td class="p-2.5 font-black text-slate-900 group-hover:text-red-700 transition-colors">
                  <div class="flex items-center gap-1.5">
                    <span>{{ item.sn }}</span>
                    <i
                      class="fa-solid fa-circle-info text-[10px] text-slate-400 group-hover:text-red-600 transition"></i>
                  </div>
                </td>
                <td class="p-2.5 text-slate-500">{{ item.cs || '-' }}</td>
                <td class="p-2.5 font-sans">
                  <span :class="getStatusBadgeClass(item.effective_status || item.stock_status)"
                    class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase border">
                    <span>{{ (item.effective_status === 'Decline' || item.effective_status === 'Declined' ||
                      item.effective_status === 'Pending Verif Admin') ? 'Menunggu Verifikasi' : (item.effective_status
                      || item.stock_status) }}</span>
                  </span>
                </td>
                <td class="p-2.5 text-slate-700 font-sans">
                  <span v-if="item.assembly_reason || item.last_reason"
                    class="text-amber-900 font-medium bg-amber-50 px-2 py-0.5 rounded border border-amber-200 block max-w-xs leading-tight">
                    {{ item.assembly_reason || item.last_reason }}
                  </span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-2.5 text-slate-700">
                  <div v-if="item.tray_code">
                    <span class="font-bold text-red-700 block">{{ item.tray_code }}</span>
                    <span v-if="item.source_document_mo" class="text-[10px] text-slate-500">{{ item.source_document_mo
                      }}</span>
                  </div>
                  <span v-else class="text-slate-300 font-sans">-</span>
                </td>
                <td class="p-2.5 text-slate-700 font-sans">
                  <span v-if="item.pic_assembly" class="font-bold text-slate-900 block">{{
                    getOperatorFullName(item.pic_assembly) }}</span>
                  <span
                    v-else-if="item.assigned_operator && item.assigned_operator !== 'Semua Operator' && item.assigned_operator !== 'ALL'"
                    class="text-slate-700 font-semibold block">{{ getOperatorFullName(item.assigned_operator) }}</span>
                  <span v-else class="text-slate-300 font-normal">-</span>
                </td>
                <td class="p-2.5 text-center font-sans">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Aksi Lihat Detail (Teks/Icon Klikable, Tanpa Elemen Button) -->
                    <span
                      class="text-red-600 group-hover:text-red-700 group-hover:underline font-bold text-[11px] inline-flex items-center gap-1 cursor-pointer"
                      title="Klik untuk melihat detail & riwayat part ini">
                      <i class="fa-solid fa-eye text-[10px]"></i>
                      <span>Lihat Detail</span>
                    </span>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredDrillList.length === 0">
                <td colspan="9" class="p-8 text-center text-slate-400 font-sans">
                  <i class="fa-solid fa-inbox text-2xl mb-1 block text-slate-300"></i>
                  Tidak ada data barang dengan status '{{ drillData.status }}' yang cocok.
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Pagination Bar: gudangVerif -->
          <div
            class="flex items-center justify-between px-3 py-2.5 border-t border-slate-100 bg-slate-50/60 rounded-b-xl  select-none no-print">
            <span class="text-[11px] text-slate-500 font-semibold">
              Hal <strong class="text-slate-700">{{ pgState.gudangVerif.page }}</strong> / {{ totalPgGudangVerif }}
              <span class="text-slate-400 ml-1">({{ (gudangVerifySets||[]).length }} data)</span>
            </span>
            <div class="flex items-center gap-1">
              <button type="button" @click="pgState.gudangVerif.page = 1" :disabled="pgState.gudangVerif.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-500 text-[10px] shadow-sm">
                <i class="fa-solid fa-angles-left"></i>
              </button>
              <button type="button" @click="pgState.gudangVerif.page--" :disabled="pgState.gudangVerif.page <= 1"
                class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-500 text-[10px] shadow-sm">
                <i class="fa-solid fa-angle-left"></i>
              </button>
              <span class="text-[11px] font-black text-slate-800 px-2">{{ pgState.gudangVerif.page }}</span>
              <button type="button" @click="pgState.gudangVerif.page++"
                :disabled="pgState.gudangVerif.page >= totalPgGudangVerif"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px] shadow-sm">
                <i class="fa-solid fa-angle-right"></i>
              </button>
              <button type="button" @click="pgState.gudangVerif.page = totalPgGudangVerif"
                :disabled="pgState.gudangVerif.page >= totalPgGudangVerif"
                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px] shadow-sm">
                <i class="fa-solid fa-angles-right"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="pt-2 flex items-center justify-between border-t border-slate-100 flex-shrink-0">
          <span class="text-xs text-slate-500">
            Menampilkan <strong>{{ filteredDrillList.length }}</strong> dari <strong>{{ drillData.list.length
              }}</strong> data.
          </span>
          <button @click="showStatusDrillModal = false"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-2 rounded-xl text-xs transition active:scale-95">
            Tutup
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL OVERRIDE STATUS PART (PIHAK GUDANG & ADMIN) ================= -->
    <div v-if="showOverrideModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-wrench"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Override Status Part</h3>
              <p class="text-[11px] text-slate-500 font-mono">Otoritas Pihak Gudang & Administrator</p>
            </div>
          </div>
          <button @click="showOverrideModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3">
          <!-- Info Barang -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-xs font-mono">
            <div class="flex justify-between">
              <span class="text-slate-500 font-sans">Tipe Komponen:</span>
              <strong class="text-red-700 font-sans font-bold">Part {{ overrideForm.part_type }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 font-sans">Serial Number (SN):</span>
              <strong class="text-slate-900 font-black text-sm">{{ overrideForm.sn }}</strong>
            </div>
            <div class="flex justify-between items-center pt-1 border-t border-slate-200 font-sans">
              <span class="text-slate-500 text-xs">Status Saat Ini:</span>
              <span :class="getStatusBadgeClass(overrideForm.old_status)"
                class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase border">
                {{ overrideForm.old_status }}
              </span>
            </div>
          </div>

          <!-- Status Pilihan Buttons -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
              Pilih Status Baru <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
              <button type="button" @click="overrideForm.new_status = 'Tersedia'"
                :class="overrideForm.new_status === 'Tersedia' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-boxes-stacked"></i> Tersedia
              </button>
              <button type="button" @click="overrideForm.new_status = 'Good'"
                :class="overrideForm.new_status === 'Good' ? 'bg-teal-600 text-white font-bold ring-2 ring-teal-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-circle-check"></i> Good
              </button>
              <button type="button" @click="overrideForm.new_status = 'Hold'"
                :class="overrideForm.new_status === 'Hold' ? 'bg-amber-600 text-white font-bold ring-2 ring-amber-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-hand"></i> Hold
              </button>
              <button type="button" @click="overrideForm.new_status = 'Scrap'"
                :class="overrideForm.new_status === 'Scrap' ? 'bg-red-600 text-white font-bold ring-2 ring-red-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-trash-can"></i> Scrap
              </button>
              <button type="button" @click="overrideForm.new_status = 'Decline'"
                :class="overrideForm.new_status === 'Decline' ? 'bg-rose-600 text-white font-bold ring-2 ring-rose-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-ban"></i> Decline
              </button>
            </div>
          </div>

          <!-- Alasan / Catatan Override -->
          <div class="space-y-1.5 p-3 rounded-xl border transition-all animate-fadeIn"
            :class="overrideForm.new_status === 'Tersedia' ? 'bg-emerald-50/80 border-emerald-300' : (overrideForm.new_status === 'Good' ? 'bg-teal-50/80 border-teal-300' : (overrideForm.new_status === 'Hold' ? 'bg-amber-50/80 border-amber-300' : (overrideForm.new_status === 'Decline' ? 'bg-rose-50/80 border-rose-300' : 'bg-red-50/80 border-red-300')))">
            <label class="block text-xs font-bold uppercase tracking-wider flex items-center justify-between"
              :class="overrideForm.new_status === 'Tersedia' ? 'text-emerald-900' : (overrideForm.new_status === 'Good' ? 'text-teal-900' : (overrideForm.new_status === 'Hold' ? 'text-amber-900' : (overrideForm.new_status === 'Decline' ? 'text-rose-900' : 'text-red-900')))">
              <span>
                <i class="fa-solid fa-pen-to-square mr-1"></i>
                Alasan / Catatan Override: <span class="text-red-600">*</span>
              </span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/90 border border-slate-200 text-slate-700">Wajib
                Diisi</span>
            </label>
            <textarea v-model="overrideForm.reason" rows="2" placeholder="Tuliskan alasan perubahan status part..."
              class="w-full bg-white border rounded-xl p-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 transition shadow-inner font-sans font-medium"
              :class="overrideForm.new_status === 'Tersedia' ? 'border-emerald-300 focus:ring-emerald-500' : (overrideForm.new_status === 'Good' ? 'border-teal-300 focus:ring-teal-500' : (overrideForm.new_status === 'Hold' ? 'border-amber-300 focus:ring-amber-500' : (overrideForm.new_status === 'Decline' ? 'border-rose-300 focus:ring-rose-500' : 'border-red-300 focus:ring-red-500')))"></textarea>
          </div>

          <div
            class="p-2.5 bg-blue-50 border border-blue-200 rounded-xl text-[11px] text-blue-800 flex items-start gap-2">
            <i class="fa-solid fa-shield-halved text-blue-600 text-sm flex-shrink-0 mt-0.5"></i>
            <span>Permohonan override ini akan diajukan ke <strong>Administrator</strong> untuk diverifikasi sebelum
              status diperbarui.</span>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button @click="showOverrideModal = false"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[40px]">
            Batal
          </button>
          <button @click="submitOverrideStatus"
            class="bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center gap-1.5 active:scale-95 min-h-[40px]">
            <i class="fa-solid fa-paper-plane"></i>
            <span>Kirim Permohonan Override</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL VERIFIKASI AKTUAL FISIK GUDANG (ACC & DECLINE PER SET) ================= -->
    <div v-if="showGudangVerifyModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-5xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[94vh] flex flex-col">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-shrink-0">
          <div class="flex items-center space-x-3">
            <div
              class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold shadow-sm">
              <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900">
                Verifikasi Aktual Fisik Gudang &bull; <span class="font-mono text-red-700">{{ travelerTray ?
                  travelerTray.tray_code : '' }}</span>
              </h3>
              <p class="text-xs text-slate-500 font-medium">Periksa fisik setiap rakitan. Berikan <strong>ACC</strong>
                untuk konfirmasi/update status kondisi atau <strong>Decline</strong> jika barang/part tidak ditemukan.
              </p>
            </div>
          </div>
          <button @click="showGudangVerifyModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Table Container -->
        <div class="overflow-y-auto overflow-x-auto flex-grow rounded-xl border border-slate-200 max-h-[58vh]">
          <table class="w-full text-left text-xs min-w-[800px]">
            <thead class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-200 sticky top-0 z-10">
              <tr>
                <th class="p-2.5 w-12 text-center">Set</th>
                <th class="p-2.5">P9 Part 15</th>
                <th class="p-2.5">P9 Part 1</th>
                <th class="p-2.5">P9 Part 12</th>
                <th class="p-2.5 text-center">Status Rakitan</th>
                <th class="p-2.5 text-center">Status Verifikasi</th>
                <th class="p-2.5">Catatan / Alasan</th>
                <th class="p-2.5 text-center w-40">Aksi Verifikasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px]">
              <tr v-for="set in pagedGudangVerif" :key="set.set_number" class="hover:bg-slate-50 transition-colors"
                :class="set.is_declined ? 'bg-amber-50/40 text-slate-400' : ''">
                <td class="p-2.5 text-center font-bold font-sans text-slate-500">#{{ set.set_number }}</td>
                <td class="p-2.5 font-black" :class="set.is_declined ? 'text-slate-600 font-bold' : 'text-slate-900'">
                  {{ set.part15_sn || '-' }}
                </td>
                <td class="p-2.5" :class="set.is_declined ? 'text-slate-600' : 'text-slate-800'">
                  {{ set.part1_sn || '-' }}
                </td>
                <td class="p-2.5" :class="set.is_declined ? 'text-slate-600' : 'text-slate-800'">
                  {{ set.part12_sn || '-' }}
                </td>
                <td class="p-2.5 text-center font-sans">
                  <span v-if="set.is_declined"
                    class="bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase">
                    Siap Siaga
                  </span>
                  <span v-else :class="getStatusBadgeClass(set.status)"
                    class="px-2 py-0.5 rounded-lg font-black text-[10px] uppercase border">
                    {{ set.status || 'Good' }}
                  </span>
                </td>
                <td class="p-2.5 text-center font-sans">
                  <span v-if="set.verified_status === 'ACC'"
                    class="bg-emerald-50 text-emerald-700 border border-emerald-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase flex items-center justify-center">
                    ACC
                  </span>
                  <span v-else-if="set.is_declined || set.verified_status === 'DECLINED'"
                    class="bg-amber-50 text-amber-800 border border-amber-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase flex items-center justify-center gap-1">
                    <i class="fa-solid fa-ban text-amber-600"></i> DECLINED
                  </span>
                  <span v-else
                    class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded-lg font-semibold text-[10px]">
                    Belum
                  </span>
                </td>
                <td class="p-2.5 font-sans text-slate-600 text-xs">
                  <span v-if="set.reason"
                    class="text-amber-900 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded text-[11px] block max-w-xs">{{
                    set.reason }}</span>
                  <span v-else class="text-slate-300">-</span>
                </td>
                <td class="p-2.5 text-center font-sans">
                  <!-- JIKA SUDAH ACC: Terkunci, otomatis masuk ke laporan dan tidak bisa diubah lagi -->
                  <div v-if="set.verified_status === 'ACC'" class="flex items-center justify-center">
                    <span
                      class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm select-none">
                      <i class="fa-solid fa-lock text-[9px]"></i> ACC
                    </span>
                  </div>

                  <!-- JIKA DECLINED: TAMPILKAN BADGE DECLINED TERKUNCI -->
                  <div v-else-if="set.is_declined || set.verified_status === 'DECLINED'"
                    class="flex items-center justify-center">
                    <span
                      class="text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-sm select-none"
                      title="Item ini berstatus Decline dan menunggu Verifikasi Administrator">
                      <i class="fa-solid fa-ban text-[9px]"></i> Declined
                    </span>
                  </div>

                  <!-- JIKA BELUM DIVERIFIKASI (BELUM ACC & BELUM DECLINE): TOMBOL ACC & DECLINE -->
                  <div v-else class="flex items-center justify-center gap-1.5">
                    <button @click="openAccOverrideModal(set)"
                      class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] px-2.5 py-1.5 rounded-lg shadow-sm transition active:scale-95 flex items-center gap-1"
                      title="Setujui (ACC) atau Sesuaikan Status Kondisi Fisik">
                      <i class="fa-solid fa-check"></i>
                      <span>ACC</span>
                    </button>
                    <button @click="openDeclineModal(set)"
                      class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] px-2.5 py-1.5 rounded-lg shadow-sm transition active:scale-95 flex items-center gap-1"
                      title="Decline: Barang / Part tidak ditemukan">
                      <i class="fa-solid fa-xmark"></i>
                      <span>Decline</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination: gudangVerif -->
        <div
          class="flex items-center justify-between px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50/50 select-none no-print mt-2">
          <span class="text-[11px] text-slate-500 font-semibold">Hal <strong class="text-slate-700">{{
              pgState.gudangVerif.page }}</strong> / {{ totalPgGudangVerif }} <span class="text-slate-400 ml-1">({{
              (gudangVerifySets||[]).length }} data)</span></span>
          <div class="flex items-center gap-1">
            <button type="button" @click="pgState.gudangVerif.page = 1" :disabled="pgState.gudangVerif.page <= 1"
              class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                class="fa-solid fa-angles-left"></i></button>
            <button type="button" @click="pgState.gudangVerif.page--" :disabled="pgState.gudangVerif.page <= 1"
              class="w-7 h-7 rounded-lg bg-white border border-slate-200 hover:bg-slate-100 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-slate-600 text-[10px]"><i
                class="fa-solid fa-angle-left"></i></button>
            <span class="text-[11px] font-black text-slate-800 px-1.5">{{ pgState.gudangVerif.page }}</span>
            <button type="button" @click="pgState.gudangVerif.page++"
              :disabled="pgState.gudangVerif.page >= totalPgGudangVerif"
              class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                class="fa-solid fa-angle-right"></i></button>
            <button type="button" @click="pgState.gudangVerif.page = totalPgGudangVerif"
              :disabled="pgState.gudangVerif.page >= totalPgGudangVerif"
              class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-30 flex items-center justify-center transition cursor-pointer disabled:cursor-not-allowed text-white text-[10px]"><i
                class="fa-solid fa-angles-right"></i></button>
          </div>
        </div>

        <!-- Footer -->
        <div
          class="pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-slate-100 flex-shrink-0">
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">Set Terverifikasi:</span>
            <span
              :class="gudangVerifySets.length > 0 && verifiedSetsCount === gudangVerifySets.length ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300'"
              class="px-2.5 py-0.5 rounded-lg border font-bold text-xs font-mono">
              {{ verifiedSetsCount }} / {{ gudangVerifySets.length }} Set
            </span>
            <span v-if="gudangVerifySets.length > 0 && verifiedSetsCount < gudangVerifySets.length"
              class="text-[11px] text-amber-700 font-semibold flex items-center gap-1">
              <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> ({{ gudangVerifySets.length -
              verifiedSetsCount }} belum diverifikasi)
            </span>
            <span v-else-if="gudangVerifySets.length > 0 && verifiedSetsCount === gudangVerifySets.length"
              class="text-[11px] text-emerald-700 font-bold flex items-center gap-1">
              <i class="fa-solid fa-circle-check text-[10px]"></i> Lengkap
            </span>
          </div>
          <div class="flex items-center gap-2">
            <button @click="showGudangVerifyModal = false"
              class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2 rounded-xl text-xs transition">
              Tutup
            </button>
            <button @click="completeGudangTrayVerification"
              :disabled="gudangVerifySets.length === 0 || verifiedSetsCount < gudangVerifySets.length"
              :class="gudangVerifySets.length > 0 && verifiedSetsCount === gudangVerifySets.length ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/25 cursor-pointer active:scale-95' : 'bg-slate-200 text-slate-400 cursor-not-allowed border border-slate-300'"
              class="font-bold px-6 py-2 rounded-xl text-xs transition flex items-center gap-1.5 min-h-[38px]">
              <i class="fa-solid fa-clipboard-check"></i>
              <span>Selesaikan Verifikasi Dokumen</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= SUB-MODAL ACC / OVERRIDE STATUS KONDISI RAKITAN OLEH GUDANG ================= -->
    <div v-if="showAccOverrideModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-check"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Verifikasi ACC Set #{{ accOverrideForm.set_number }}</h3>
              <p class="text-[11px] text-slate-500">Sesuaikan status barang jika ditemukan cacat/kerusakan</p>
            </div>
          </div>
          <button @click="showAccOverrideModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3">
          <!-- Status Pilihan -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
              Status Kondisi Fisik Barang <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button"
                @click="accOverrideForm.status = 'Good'; accOverrideForm.defective_parts = []; accOverrideForm.reason = ''"
                :class="accOverrideForm.status === 'Good' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="py-2.5 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1">
                <i class="fa-solid fa-circle-check"></i> Good
              </button>
              <button type="button" @click="accOverrideForm.status = 'Hold'"
                :class="accOverrideForm.status === 'Hold' ? 'bg-amber-600 text-white font-bold ring-2 ring-amber-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="py-2.5 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1">
                <i class="fa-solid fa-hand"></i> Hold
              </button>
              <button type="button" @click="accOverrideForm.status = 'Scrap'"
                :class="accOverrideForm.status === 'Scrap' ? 'bg-red-600 text-white font-bold ring-2 ring-red-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                class="py-2.5 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1">
                <i class="fa-solid fa-trash-can"></i> Scrap
              </button>
            </div>
          </div>

          <!-- Checklist Part Rusak (Jika non-Good) -->
          <div v-if="accOverrideForm.status !== 'Good'"
            class="space-y-2 p-3 bg-slate-50 border border-slate-300 rounded-xl">
            <label class="block text-xs font-bold uppercase text-slate-800 flex items-center justify-between">
              <span>Pilih Part yang Rusak / Bermasalah: <span class="text-red-600">*</span></span>
              <span
                class="text-[10px] font-bold bg-white px-2 py-0.5 rounded border border-slate-200 text-slate-700">Wajib
                Dipilih</span>
            </label>
            <div class="grid grid-cols-1 gap-2">
              <label class="flex items-center space-x-2 p-2 rounded-lg border bg-white cursor-pointer select-none"
                :class="accOverrideForm.defective_parts && accOverrideForm.defective_parts.includes('15') ? 'border-red-500 bg-red-50 text-red-900 font-bold' : 'border-slate-200 text-slate-700'">
                <input type="checkbox" value="15" v-model="accOverrideForm.defective_parts"
                  class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                <span class="text-xs font-mono">P9 Part 15</span>
              </label>
              <label class="flex items-center space-x-2 p-2 rounded-lg border bg-white cursor-pointer select-none"
                :class="accOverrideForm.defective_parts && accOverrideForm.defective_parts.includes('1') ? 'border-red-500 bg-red-50 text-red-900 font-bold' : 'border-slate-200 text-slate-700'">
                <input type="checkbox" value="1" v-model="accOverrideForm.defective_parts"
                  class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                <span class="text-xs font-mono">P9 Part 1</span>
              </label>
              <label class="flex items-center space-x-2 p-2 rounded-lg border bg-white cursor-pointer select-none"
                :class="accOverrideForm.defective_parts && accOverrideForm.defective_parts.includes('12') ? 'border-red-500 bg-red-50 text-red-900 font-bold' : 'border-slate-200 text-slate-700'">
                <input type="checkbox" value="12" v-model="accOverrideForm.defective_parts"
                  class="rounded text-red-600 focus:ring-red-500 w-4 h-4">
                <span class="text-xs font-mono">P9 Part 12</span>
              </label>
            </div>
          </div>

          <!-- Alasan / Catatan jika Hold / Rework / Scrap -->
          <div v-if="accOverrideForm.status !== 'Good'"
            class="space-y-1.5 p-3 rounded-xl border transition-all animate-fadeIn"
            :class="accOverrideForm.status === 'Hold' ? 'bg-amber-50/80 border-amber-300' : (accOverrideForm.status === 'Scrap' ? 'bg-red-50/80 border-red-300' : 'bg-purple-50/80 border-purple-300')">
            <label class="block text-xs font-bold uppercase tracking-wider flex items-center justify-between"
              :class="accOverrideForm.status === 'Hold' ? 'text-amber-900' : (accOverrideForm.status === 'Scrap' ? 'text-red-900' : 'text-purple-900')">
              <span>
                <i class="fa-solid fa-pen-to-square mr-1"></i>
                Alasan / Catatan {{ accOverrideForm.status }}: <span class="text-red-600">*</span>
              </span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/90 border border-slate-200 text-slate-700">Wajib
                Diisi</span>
            </label>
            <textarea v-model="accOverrideForm.reason" rows="2"
              :placeholder="'Tuliskan alasan kerusakan atau kendala ' + accOverrideForm.status + '...'"
              class="w-full bg-white border rounded-xl p-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 transition shadow-inner font-sans font-medium"
              :class="accOverrideForm.status === 'Hold' ? 'border-amber-300 focus:ring-amber-500' : (accOverrideForm.status === 'Scrap' ? 'border-red-300 focus:ring-red-500' : 'border-purple-300 focus:ring-purple-500')"></textarea>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button @click="showAccOverrideModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
            Batal
          </button>
          <button @click="submitAccOverride"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center gap-1.5 active:scale-95">
            <i class="fa-solid fa-check"></i>
            <span>Simpan ACC</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= SUB-MODAL KONFIRMASI DECLINE BARANG/PART GUDANG ================= -->
    <div v-if="showDeclineConfirmModal"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto text-center">
        <div
          class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl mx-auto flex items-center justify-center text-xl shadow-inner">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="space-y-1.5">
          <h3 class="font-black text-lg text-slate-900">Konfirmasi Decline Set #{{ declineData.set_number }}</h3>
          <p class="text-xs text-slate-600 leading-relaxed font-semibold">
            Apakah Anda yakin tidak menemukan barang/part pada <strong>Set #{{ declineData.set_number }}</strong>
            tersebut?
          </p>
        </div>

        <div
          class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-left text-xs text-rose-900 leading-relaxed font-medium">
          <i class="fa-solid fa-circle-info text-rose-600 mr-1"></i>
          Seluruh part dalam set ini akan berstatus <strong>DECLINED</strong> dan langsung masuk ke antrean
          <strong>Verifikasi Administrator</strong> untuk pemeriksaan fisik sebelum dapat digunakan kembali.
        </div>

        <div class="grid grid-cols-2 gap-2 pt-2">
          <button @click="showDeclineConfirmModal = false"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 rounded-xl text-xs transition">
            Tidak (Batal)
          </button>
          <button @click="confirmDeclineItem"
            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 rounded-xl text-xs shadow-md shadow-rose-600/25 transition flex items-center justify-center space-x-1 active:scale-95">
            <i class="fa-solid fa-check mr-1"></i> Ya, Decline
          </button>
        </div>
      </div>
    </div>

    <!-- ================= SUB-MODAL VERIFIKASI SET DECLINE/SCRAP OLEH ADMIN (LAYOUT PERAKITAN) ================= -->
    <div v-if="showAdminVerifySetModal && activeAdminSet"
      @click.self="safeCloseModal(() => showAdminVerifySetModal = false, hasUnverifiedPartsInActiveSet)"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5 sm:space-x-3">
            <div
              class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm font-bold shadow-md flex-shrink-0 font-mono">
              #{{ activeAdminSet.set_number }}
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                Verifikasi Rakitan Set #{{ activeAdminSet.set_number }}
              </h3>
              <p class="text-[11px] text-slate-500 font-mono">
                Dokumen: <strong class="text-red-700">{{ activeAdminSet.tray_code || ('Tray #' + activeAdminSet.tray_id)
                  }}</strong> &bull; Status: <strong class="text-slate-800">{{ activeAdminSet.qc_status === 'HOLD_QC' ?
                  'Hold QC' : (activeAdminSet.qc_status === 'REWORK_QC' ? 'Hold QC' : (activeAdminSet.assembly_status
                  === 'Rework' || activeAdminSet.status === 'Rework' ? 'Hold' : (activeAdminSet.assembly_status ===
                  'Hold' || activeAdminSet.status === 'Hold' || activeAdminSet.is_hold_test ? 'Hold' :
                  (activeAdminSet.is_declined || activeAdminSet.verified_status === 'DECLINED' ? ('Declined (' + getDeclinedSource(activeAdminSet) + ')') :
                  (activeAdminSet.assembly_status || activeAdminSet.status || 'Scrap'))))) }}</strong>
              </p>
            </div>
          </div>
          <button @click="safeCloseModal(() => showAdminVerifySetModal = false, hasUnverifiedPartsInActiveSet)"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup Modal">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Decision Box: KHUSUS RAKITAN HOLD HASIL UJI OPERATOR / QC -->
        <div v-if="activeAdminSet.is_hold_test"
          class="p-3.5 bg-amber-50 border border-amber-200 rounded-2xl space-y-3 shadow-xs">
          <div class="flex items-center space-x-2.5">
            <span
              class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center text-sm font-black flex-shrink-0 shadow-sm">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
            <div>
              <h4 class="font-black text-xs text-amber-950">Keputusan Admin: Verifikasi Set #{{
                activeAdminSet.set_number }}</h4>
              <p class="text-[11px] text-amber-800">
                <span
                  v-if="activeAdminSet.process_stage === 'perakitan' || activeAdminSet.verified_status === 'PENDING_ADMIN_ASSY_REREG'">Stage:
                  <strong>Rakit Ulang (RU) — Pilih Operator</strong></span>
                <span v-else>Alasan: <strong>{{ activeAdminSet.scrap_reason || activeAdminSet.declined_reason || 'Gagal dalam uji' }}</strong></span>
              </p>
            </div>
          </div>

          <p class="text-xs text-slate-700 leading-snug">
            Tentukan keputusan tindak lanjut untuk rakitan ini:
          </p>

          <!-- *** CASE A: Rakit Ulang (Daftar Ulang dari Perakitan) *** -->
          <div
            v-if="activeAdminSet.process_stage === 'perakitan' || activeAdminSet.verified_status === 'PENDING_ADMIN_ASSY_REREG'"
            class="space-y-3 pt-1">
            <!-- Info panel -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-900 space-y-1">
              <p class="font-bold text-amber-950">Permintaan Rakit Ulang (RU) dari Gudang:</p>
              <div class="flex justify-between"><span class="text-amber-700">Operator Diminta:</span><strong>{{
                  activeAdminSet.rereg_pending_operator || activeAdminSet.pic_assembly }}</strong></div>
              <div class="flex justify-between"><span class="text-amber-700">WO Baru yang akan dibuat:</span><strong>{{
                  activeAdminSet.candidate_rereg_code || getNextPUTrayCode(activeAdminSet.tray_code) }}</strong></div>
            </div>
            <!-- Operator picker for admin -->
            <div>
              <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">
                <i class="fa-solid fa-user-gear mr-1 text-amber-600"></i>
                Ubah Operator (opsional):
              </label>
              <div class="relative">
                <select v-model="adminReregApprovedOperator"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600 appearance-none cursor-pointer">
                  <option value="">— Gunakan operator yang diminta gudang —</option>
                  <option v-for="op in operatorUsersList" :key="op.username || op.id"
                    :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
                <i
                  class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
              </div>
            </div>
            <!-- Admin admin notes -->
            <div>
              <label class="block text-[10px] font-bold uppercase text-slate-600 mb-1">Catatan Admin (opsional):</label>
              <input type="text" v-model="adminReregNotes" placeholder="Catatan untuk operator..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold focus:outline-none focus:border-emerald-600" />
            </div>
            <!-- Action buttons -->
            <div class="grid grid-cols-2 gap-2">
              <button type="button"
                @click="adminApproveAssyRereg(activeAdminSet, 'APPROVE'); showAdminVerifySetModal = false;"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold p-2.5 rounded-xl text-xs shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-hammer"></i>
                <span>Setujui — Rakit Ulang (RU)</span>
              </button>
              <button type="button"
                @click="adminApproveAssyRereg(activeAdminSet, 'REJECT'); showAdminVerifySetModal = false;"
                class="bg-slate-500 hover:bg-slate-600 text-white font-bold p-2.5 rounded-xl text-xs flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-xmark"></i>
                <span>Tolak Pengajuan</span>
              </button>
            </div>
          </div>

          <!-- *** CASE B: Regular Hold/Rework Verification *** -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
            <!-- Option 1: Good -->
            <button type="button"
              @click="adminApproveHoldItem(activeAdminSet, (activeAdminSet.stage === 'QC' || activeAdminSet.qc_status === 'HOLD_QC' || activeAdminSet.qc_status === 'REWORK_QC') ? 'APPROVE_GOOD_QC' : 'APPROVE_GOOD'); showAdminVerifySetModal = false;"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold p-2.5 rounded-xl text-xs shadow-md shadow-emerald-600/20 flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[42px]">
              <i class="fa-solid fa-circle-check"></i>
              <span
                v-if="activeAdminSet.stage === 'QC' || activeAdminSet.qc_status === 'HOLD_QC' || activeAdminSet.qc_status === 'REWORK_QC'">Lanjut ke Good Receipts</span>
              <span v-else>Good (Lanjut TF 2 / QC)</span>
            </button>

            <!-- Option 2: Hold -->
            <button type="button"
              @click="adminApproveHoldItem(activeAdminSet, (activeAdminSet.stage === 'QC' || activeAdminSet.qc_status === 'HOLD_QC' || activeAdminSet.qc_status === 'REWORK_QC') ? 'HOLD_QC' : 'HOLD'); showAdminVerifySetModal = false;"
              class="bg-amber-600 hover:bg-amber-700 text-white font-bold p-2.5 rounded-xl text-xs shadow-md shadow-amber-600/20 flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[42px]">
              <i class="fa-solid fa-hand"></i>
              <span
                v-if="activeAdminSet.stage === 'QC' || activeAdminSet.qc_status === 'HOLD_QC' || activeAdminSet.qc_status === 'REWORK_QC'">Hold
                QC (Kembali Gudang)</span>
              <span v-else>Hold (Kembali Gudang)</span>
            </button>

            <!-- Option 3: Scrap -->
            <button type="button"
              @click="adminApproveHoldItem(activeAdminSet, 'SCRAP'); showAdminVerifySetModal = false;"
              class="bg-red-600 hover:bg-red-700 text-white font-bold p-2.5 rounded-xl text-xs shadow-md shadow-red-600/20 flex items-center justify-center gap-1.5 transition active:scale-95 cursor-pointer min-h-[42px]">
              <i class="fa-solid fa-trash-can"></i>
              <span>Scrap</span>
            </button>
          </div>
        </div>

        <!-- Info Card: Untuk Part Declined / Scrap -->
        <div v-else class="bg-amber-50/70 border border-amber-200 rounded-xl p-3 text-xs text-amber-900 space-y-1">
          <div class="flex items-center justify-between font-bold">
            <span>Pemeriksaan Fisik Part Rakitan</span>
            <span class="font-mono text-[11px] uppercase">{{ activeAdminSet.qc_status === 'HOLD_QC' ? 'HOLD QC' :
              (activeAdminSet.qc_status === 'REWORK_QC' ? 'HOLD QC' : (activeAdminSet.assembly_status === 'Rework' || activeAdminSet.status === 'Rework' ? 'HOLD' : (activeAdminSet.assembly_status === 'Hold' ||
              activeAdminSet.status === 'Hold' || activeAdminSet.is_hold_test ? 'HOLD' : (activeAdminSet.is_declined ||
              activeAdminSet.verified_status === 'DECLINED' ? ('DECLINED (' + getDeclinedSource(activeAdminSet) + ')') : (activeAdminSet.assembly_status ||
              activeAdminSet.status || 'SCRAP'))))) }}</span>
          </div>
          <p class="text-[11px] text-amber-800 leading-relaxed">
            Silakan verifikasi kelayakan fisik setiap part di bawah ini. Anda dapat menyetujui part secara individu (1
            per 1) atau memulihkan semua part sekaligus ke stok <strong>Tersedia</strong>.
          </p>
        </div>

        <!-- 3-Part Rows (Layout Perakitan) -->
        <div class="space-y-2.5">
          <div v-for="part in activeAdminSet.parts" :key="part.type"
            class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center justify-between gap-3 transition-all hover:border-slate-300">

            <div class="space-y-1 min-w-0">
              <span class="text-[11px] font-bold uppercase text-slate-600 block truncate">
                {{ part.label }}
              </span>
              <div class="flex items-center space-x-2">
                <span
                  class="font-mono font-black text-sm text-slate-900 bg-white border border-slate-200 px-2 py-0.5 rounded-md shadow-xs">
                  {{ part.sn || '-' }}
                </span>
                <span v-if="part.status === 'Tersedia'"
                  class="bg-emerald-50 text-emerald-700 border border-emerald-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                  <i class="fa-solid fa-circle-check text-[9px]"></i> Tersedia
                </span>
                <span
                  v-else-if="activeAdminSet.qc_status === 'HOLD_QC' || activeAdminSet.qc_status === 'HOLD' || activeAdminSet.assembly_status === 'Hold' || activeAdminSet.status === 'Hold' || activeAdminSet.is_hold_test"
                  class="bg-amber-50 text-amber-800 border border-amber-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                  <i class="fa-solid fa-hand text-[9px]"></i> {{ activeAdminSet.qc_status === 'HOLD_QC' ? 'Hold QC' :
                  'Hold' }}
                </span>
                <span
                  v-else-if="activeAdminSet.qc_status === 'REWORK_QC' || activeAdminSet.assembly_status === 'Rework' || activeAdminSet.status === 'Rework'"
                  class="bg-amber-50 text-amber-800 border border-amber-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                  <i class="fa-solid fa-hand text-[9px]"></i> Hold
                </span>
                <span v-else-if="activeAdminSet.assembly_status === 'Scrap' || activeAdminSet.status === 'Scrap'"
                  class="bg-rose-50 text-rose-700 border border-rose-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                  <i class="fa-solid fa-ban text-[9px]"></i> Scrap
                </span>
                <span v-else
                  class="bg-rose-50 text-rose-700 border border-rose-300 font-bold text-[10px] uppercase px-2 py-0.5 rounded-md flex items-center gap-1">
                  <i class="fa-solid fa-ban text-[9px]"></i> {{ activeAdminSet.is_declined ? 'Declined' : (part.status
                  || 'Hold') }}
                </span>
              </div>
            </div>

            <!-- Di ujung kanan ada icon/button buat verif daftar ulangin nya & icon hapus -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
              <button v-if="part.status !== 'Tersedia'" type="button" :disabled="part.is_submitting"
                @click="verifyAdminSinglePart(part)"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition active:scale-95 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                title="Pulihkan part ke status Tersedia">
                <i v-if="part.is_submitting" class="fa-solid fa-spinner fa-spin"></i>
                <i v-else class="fa-solid fa-check text-xs"></i>
                <span>Verifikasi</span>
              </button>

              <span v-else
                class="text-emerald-700 font-bold text-xs flex items-center gap-1 bg-emerald-50 border border-emerald-200 px-2.5 py-1.5 rounded-xl">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Pulih</span>
              </span>

              <!-- Icon Sampah untuk Menghapus Barang / Part (Hanya jika belum Tersedia) -->
              <button v-if="part.status !== 'Tersedia'" type="button" :disabled="part.is_submitting" @click="deleteAdminSetPart(part)"
                class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 hover:border-red-300 font-bold p-2 rounded-xl text-xs transition active:scale-95 flex items-center justify-center cursor-pointer disabled:opacity-50"
                title="Hapus / Batalkan verifikasi part ini">
                <i class="fa-solid fa-trash-can text-xs"></i>
              </button>
            </div>

          </div>
        </div>

        <!-- Footer / Action Buttons -->
        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row gap-2 justify-between items-center">
          <button @click="showAdminVerifySetModal = false" type="button"
            class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>

          <button v-if="hasUnverifiedPartsInActiveSet" type="button" :disabled="isSubmittingAllAdminVerify"
            @click="verifyAllPartsInActiveSet"
            class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-1.5 cursor-pointer disabled:opacity-50 active:scale-95">
            <i v-if="isSubmittingAllAdminVerify" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check-double mr-1"></i>
            <span>Pulihkan Semua Part (Tersedia)</span>
          </button>
          <div v-else
            class="text-xs text-emerald-800 font-bold flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-xl">
            <i class="fa-solid fa-lock text-emerald-600"></i>
            <span>Seluruh Part Selesai Diverifikasi (Terkunci)</span>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= SUB-MODAL VERIFIKASI PART SCRAP OLEH ADMIN ================= -->
    <div v-if="showAdminVerifyModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Verifikasi Part ({{ adminVerifyForm.current_status ||
                'Scrap' }})</h3>
              <p class="text-[11px] text-slate-500">P9 Part {{ adminVerifyForm.part_type }} &bull; <strong
                  class="font-mono">{{ adminVerifyForm.sn }}</strong></p>
            </div>
          </div>
          <button @click="showAdminVerifyModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3">
          <div class="bg-red-50/70 border border-red-200 rounded-xl p-3 text-xs text-red-900 space-y-1">
            <span class="font-bold block">Status Saat Ini: <strong>{{ (adminVerifyForm.current_status ||
                'SCRAP').toUpperCase() }}</strong></span>
            <p class="text-[11px]">Tentukan status hasil verifikasi fisik untuk memulihkan atau memperbarui status part
              ini.</p>
          </div>

          <!-- Pilihan Status Hasil Verifikasi (Good, Scrap, Hold, Rework) -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
              Pilih Status Hasil Verifikasi <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button" @click="adminVerifyForm.target_status = 'Tersedia'"
                :class="adminVerifyForm.target_status === 'Tersedia' || adminVerifyForm.target_status === 'Good' ? 'bg-emerald-600 text-white ring-2 ring-emerald-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px] cursor-pointer">
                <i class="fa-solid fa-circle-check text-sm mb-0.5"></i> Tersedia (Ready)
              </button>
              <button type="button" @click="adminVerifyForm.target_status = 'Scrap'"
                :class="adminVerifyForm.target_status === 'Scrap' ? 'bg-red-600 text-white ring-2 ring-red-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px] cursor-pointer">
                <i class="fa-solid fa-trash-can text-sm mb-0.5"></i> Tetap Scrap
              </button>
              <button type="button" @click="adminVerifyForm.target_status = 'Hold'"
                :class="adminVerifyForm.target_status === 'Hold' ? 'bg-amber-600 text-white ring-2 ring-amber-400 font-bold shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 rounded-xl font-semibold text-xs transition flex flex-col items-center justify-center min-h-[44px] cursor-pointer">
                <i class="fa-solid fa-hand text-sm mb-0.5"></i> Hold
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
              Catatan Verifikasi Admin <span class="text-slate-400 font-normal">(Opsional)</span>
            </label>
            <textarea v-model="adminVerifyForm.notes" rows="2"
              placeholder="Catatan hasil verifikasi fisik atau rekomendasi..."
              class="w-full bg-slate-50 border border-slate-300 focus:bg-white focus:border-red-600 rounded-xl p-2.5 text-xs text-slate-900 focus:outline-none"></textarea>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button @click="showAdminVerifyModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
            Batal
          </button>
          <button @click="submitAdminVerify"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/25 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
            <i class="fa-solid fa-check"></i>
            <span>Simpan Verifikasi</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL LENGKAP SATU SET ================= -->
    <div v-if="showAdminSetDetailModal && selectedAdminSetDetail"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-3">
            <div
              class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-sm font-black shadow-md flex-shrink-0 font-mono">
              #{{ selectedAdminSetDetail.set_number }}
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                Detail Unit Rakitan Set #{{ selectedAdminSetDetail.set_number }}
              </h3>
              <p class="text-xs text-slate-500 font-mono">
                Tray: <strong class="text-red-600">{{ selectedAdminSetDetail.tray_code || ('Tray #' +
                  selectedAdminSetDetail.tray_id) }}</strong>
                &bull; MO: <span class="font-bold text-slate-700">{{ selectedAdminSetDetail.mo_number ||
                  selectedAdminSetDetail.mo || '-' }}</span>
              </p>
            </div>
          </div>
          <button @click="showAdminSetDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-2 rounded-xl transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Banner Proses & Status -->
        <div class="p-3.5 rounded-2xl border space-y-2"
          :class="isSetFullyVerified(selectedAdminSetDetail) ? 'bg-emerald-50/80 border-emerald-200' : (selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG') ? 'bg-indigo-50/80 border-indigo-200' : (selectedAdminSetDetail.process_stage === 'qc' ? 'bg-purple-50/80 border-purple-200' : (selectedAdminSetDetail.process_stage === 'test_functional_1' ? 'bg-blue-50/80 border-blue-200' : 'bg-amber-50/80 border-amber-200'))">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white shadow-xs"
                :class="isSetFullyVerified(selectedAdminSetDetail) ? 'bg-emerald-600' : (selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG') ? 'bg-indigo-600' : (selectedAdminSetDetail.process_stage === 'qc' ? 'bg-purple-600' : (selectedAdminSetDetail.process_stage === 'test_functional_1' ? 'bg-blue-600' : 'bg-amber-600'))">
                <i
                  :class="isSetFullyVerified(selectedAdminSetDetail) ? 'fa-solid fa-lock' : (selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG') ? 'fa-solid fa-screwdriver-wrench' : (selectedAdminSetDetail.process_stage === 'qc' ? 'fa-solid fa-award' : (selectedAdminSetDetail.process_stage === 'test_functional_1' ? 'fa-solid fa-microchip' : 'fa-solid fa-wrench'))"></i>
              </span>
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block">Tahap Proses</span>
                <h4 class="text-xs font-black text-slate-900">
                  <span v-if="isSetFullyVerified(selectedAdminSetDetail)">Selesai Diverifikasi (Terkunci)</span>
                  <span
                    v-else-if="selectedAdminSetDetail.is_declined || selectedAdminSetDetail.process_stage === 'declined' || (selectedAdminSetDetail.status || '').toUpperCase() === 'DECLINE' || (selectedAdminSetDetail.verified_status || '').toUpperCase() === 'DECLINED'">{{ getDeclinedSource(selectedAdminSetDetail) === 'GR' ? 'Penerimaan Fisik Gudang (GR) — Ditolak' : 'Pengembalian Tray ke Gudang (PG) — Ditolak' }}</span>
                  <span
                    v-else-if="selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG'">Pengajuan
                    Rakit Ulang (RU)</span>
                  <span v-else-if="selectedAdminSetDetail.process_stage === 'qc'">Gagal pada: Test Functional 2 (Quality
                    Control)</span>
                  <span v-else-if="selectedAdminSetDetail.process_stage === 'test_functional_1'">Gagal pada: Test
                    Functional 1 (Uji Fungsional)</span>
                  <span v-else>Gagal pada: Proses Perakitan (Assembly)</span>
                </h4>
              </div>
            </div>
            <span v-if="isSetFullyVerified(selectedAdminSetDetail)"
              class="px-2.5 py-1 rounded-xl text-xs font-black border shadow-2xs uppercase tracking-wide bg-emerald-100 text-emerald-900 border-emerald-300 flex items-center gap-1">
              <i class="fa-solid fa-lock text-[10px]"></i> Selesai
            </span>
            <span v-else class="px-2.5 py-1 rounded-xl text-xs font-black border shadow-2xs uppercase tracking-wide"
              :class="(selectedAdminSetDetail.is_declined || selectedAdminSetDetail.process_stage === 'declined' || (selectedAdminSetDetail.status || '').toUpperCase() === 'DECLINE') ? 'bg-rose-100 text-rose-900 border-rose-300' : (selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG') ? 'bg-indigo-100 text-indigo-900 border-indigo-300' : (selectedAdminSetDetail.display_status && selectedAdminSetDetail.display_status.includes('Scrap') ? 'bg-rose-100 text-rose-800 border-rose-300' : (selectedAdminSetDetail.display_status && selectedAdminSetDetail.display_status.includes('Rework') ? 'bg-purple-100 text-purple-800 border-purple-300' : 'bg-amber-100 text-amber-900 border-amber-300'))">
              {{ (selectedAdminSetDetail.is_declined || selectedAdminSetDetail.process_stage === 'declined' || (selectedAdminSetDetail.status || '').toUpperCase() === 'DECLINE') ? 'DECLINED' : (selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status ===
              'PENDING_ADMIN_ASSY_REREG') ? 'Rakit Ulang (RU)' : (selectedAdminSetDetail.display_status || 'Hold Rakitan') }}
            </span>
          </div>

          <!-- Status Proses Berdampingan: Test Assembly & QC -->
          <div
            class="bg-white/90 p-2.5 rounded-xl border border-slate-200/80 flex items-center justify-between text-xs font-sans">
            <div class="flex items-center gap-1.5">
              <span class="font-bold text-slate-700">Test Assembly:</span>
              <span
                v-if="selectedAdminSetDetail.is_declined || selectedAdminSetDetail.process_stage === 'declined' || (selectedAdminSetDetail.status || '').toUpperCase() === 'DECLINE'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-ban text-[9px]"></i> DECLINED
              </span>
              <span
                v-else-if="selectedAdminSetDetail.assy_test_status === 'Hold' || selectedAdminSetDetail.qc_status === 'HOLD_ASSY' || (selectedAdminSetDetail.process_stage === 'test_functional_1' && (selectedAdminSetDetail.assembly_status === 'Hold' || selectedAdminSetDetail.status === 'Hold'))"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-hand text-[9px]"></i> HOLD
              </span>
              <span
                v-else-if="selectedAdminSetDetail.assy_test_status === 'Rework' || (selectedAdminSetDetail.process_stage === 'test_functional_1' && (selectedAdminSetDetail.assembly_status === 'Rework' || selectedAdminSetDetail.status === 'Rework'))"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-hand text-[9px]"></i> HOLD
              </span>
              <span
                v-else-if="selectedAdminSetDetail.status === 'Dirakit Ulang' || selectedAdminSetDetail.qc_status === 'RU' || selectedAdminSetDetail.verified_status === 'COMPLETED_PU'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-arrows-rotate text-[9px]"></i> DIRAKIT ULANG
              </span>
              <span
                v-else-if="((selectedAdminSetDetail.assy_test_status === 'Good' || selectedAdminSetDetail.assy_tested_at) && !['HOLD', 'REWORK', 'PENDING', 'BELUM DIUJI'].includes(String(selectedAdminSetDetail.assy_test_status || '').toUpperCase())) || ['qc', 'QC'].includes(selectedAdminSetDetail.process_stage) || selectedAdminSetDetail.stage === 'QC' || ['HOLD_QC', 'REWORK_QC', 'GOOD', 'GOOD_QC'].includes(selectedAdminSetDetail.qc_status) || selectedAdminSetDetail.qc_tested_at || selectedAdminSetDetail.qc_doc_number"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-check text-[9px]"></i> GOOD
              </span>
              <span v-else
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-slate-100 text-slate-700 border border-slate-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-clock text-[9px]"></i> BELUM DIUJI
              </span>
            </div>
            <div v-if="selectedAdminSetDetail.qc_status && selectedAdminSetDetail.qc_status !== 'PENDING'"
              class="flex items-center gap-1.5">
              <span class="font-bold text-slate-700">QC:</span>
              <span v-if="selectedAdminSetDetail.qc_status === 'HOLD_QC' || selectedAdminSetDetail.qc_status === 'HOLD'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> HOLD
              </span>
              <span
                v-else-if="selectedAdminSetDetail.qc_status === 'REWORK_QC' || selectedAdminSetDetail.qc_status === 'REWORK'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> HOLD
              </span>
              <span
                v-else-if="selectedAdminSetDetail.qc_status === 'GOOD' || selectedAdminSetDetail.qc_status === 'GOOD_QC'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                <i class="fa-solid fa-check text-[9px]"></i> GOOD
              </span>
            </div>
          </div>

          <!-- Parameter checklist uji jika ada -->
          <div v-if="selectedAdminSetDetail.qc_failure_reasons || selectedAdminSetDetail.assy_failure_reasons"
            class="pt-2 border-t border-slate-200/60 text-xs">
            <span class="font-bold text-slate-700 block mb-1">Parameter Pengujian yang Gagal / Not Good:</span>
            <div
              class="bg-white/80 border border-slate-200 rounded-xl p-2.5 font-mono text-[11px] text-rose-800 whitespace-pre-wrap leading-relaxed">
              {{ selectedAdminSetDetail.qc_failure_reasons || selectedAdminSetDetail.assy_failure_reasons }}
            </div>
          </div>
        </div>

        <!-- Detail Alasan / Catatan Operator -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5 text-xs">
          <span class="font-bold text-slate-600 uppercase text-[10px] tracking-wider block">Alasan</span>
          <p class="text-slate-800 font-medium italic leading-relaxed">
            "{{ selectedAdminSetDetail.scrap_reason || selectedAdminSetDetail.declined_reason ||
            selectedAdminSetDetail.reason || 'Tidak ada catatan tambahan.' }}"
          </p>
          <div
            class="pt-2 border-t border-slate-200/80 flex flex-wrap items-center justify-between text-[11px] text-slate-500 font-sans">
            <span>Petugas: <strong>{{ getOperatorFullName(selectedAdminSetDetail.pic_qc ||
                selectedAdminSetDetail.pic_assembly || selectedAdminSetDetail.operator ||
                selectedAdminSetDetail.rereg_pending_operator || selectedAdminSetDetail.requestor_username)
                }}</strong></span>
            <span>Waktu: {{ formatDateTime(selectedAdminSetDetail.qc_tested_at || selectedAdminSetDetail.finished_at ||
              selectedAdminSetDetail.tray_created_at) }}</span>
          </div>
        </div>

        <!-- KHUSUS DAFTAR ULANG: PANEL PENUGASAN OPERATOR PERAKITAN ULANG (HANYA DARI STAGE PERAKITAN) -->
        <div
          v-if="selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG' && !isSetFullyVerified(selectedAdminSetDetail)"
          class="bg-blue-50/70 border-2 border-blue-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center justify-between border-b border-blue-200/60 pb-2">
            <span class="text-xs font-black uppercase text-blue-900 tracking-wider flex items-center gap-1.5">
              <i class="fa-solid fa-user-gear text-blue-600"></i>
              <span>Penugasan Operator Rakit Ulang (RU)</span>
            </span>
            <span
              class="text-[11px] font-mono font-bold bg-white text-blue-800 px-2.5 py-0.5 rounded-lg border border-blue-300">
              WO Baru: {{ selectedAdminSetDetail.candidate_rereg_code ||
              getNextPUTrayCode(selectedAdminSetDetail.tray_code) }}
            </span>
          </div>

          <!-- Operator yang Dipilih di Gudang -->
          <div class="bg-white border border-blue-200 rounded-xl p-2.5 flex items-center justify-between text-xs">
            <span class="text-slate-500 font-medium">Operator yang Dipilih di Gudang:</span>
            <span class="font-bold text-slate-900 flex items-center gap-1.5">
              <i class="fa-solid fa-user-tag text-amber-500"></i>
              <span>{{ getOperatorFullName(selectedAdminSetDetail.rereg_pending_operator ||
                selectedAdminSetDetail.pic_assembly) }}</span>
            </span>
          </div>

          <!-- Admin Picker: Pilih Operator yang Benar-Benar Ingin Ditunjuk -->
          <div class="space-y-1">
            <label class="block text-xs font-black text-slate-800 flex items-center gap-1">
              <i class="fa-solid fa-user-check text-emerald-600"></i>
              <span>Pilih Operator yang Ditunjuk untuk Merakit Ulang (RU):</span>
            </label>
            <div class="relative">
              <select v-model="adminReregApprovedOperator"
                class="w-full bg-white border-2 border-blue-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-blue-600 shadow-sm appearance-none cursor-pointer">
                <option v-for="op in operatorUsersList" :key="op.username || op.id"
                  :value="op.full_name || op.username">
                  {{ op.full_name || op.username }}
                </option>
              </select>
              <i
                class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-blue-500 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Catatan Admin (Opsional) -->
          <div class="space-y-1">
            <label class="block text-[11px] font-bold text-slate-600">Catatan Admin (Opsional):</label>
            <input type="text" v-model="adminReregNotes" placeholder="Catatan atau instruksi perakitan ulang..."
              class="w-full bg-white border border-blue-200 rounded-xl px-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500" />
          </div>
        </div>

        <!-- 3 Part Breakdown -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
            <i class="fa-solid fa-puzzle-piece text-slate-400"></i>
            <span>Komponen Terpasang (3 Part)</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">P9 Part 15</span>
              <p class="font-mono font-black text-xs text-slate-800 truncate"
                :title="selectedAdminSetDetail.part15_sn || '-'">
                {{ selectedAdminSetDetail.part15_sn || '-' }}
              </p>
            </div>
            <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">P9 Part 1</span>
              <p class="font-mono font-black text-xs text-slate-800 truncate"
                :title="selectedAdminSetDetail.part1_sn || '-'">
                {{ selectedAdminSetDetail.part1_sn || '-' }}
              </p>
            </div>
            <div class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 space-y-1">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">P9 Part 12</span>
              <p class="font-mono font-black text-xs text-slate-800 truncate"
                :title="selectedAdminSetDetail.part12_sn || '-'">
                {{ selectedAdminSetDetail.part12_sn || '-' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
          <button @click="showAdminSetDetailModal = false" type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>

          <!-- KONDISI 0: SUDAH SELESAI / TERKUNCI -> HANYA STATUS INFORMASI, TIDAK ADA TOMBOL AKSI LAGI -->
          <template v-if="isSetFullyVerified(selectedAdminSetDetail)">
            <div
              class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-200 select-none">
              <i class="fa-solid fa-lock text-emerald-600 text-xs"></i>
              <span>Selesai Diverifikasi (Terkunci)</span>
            </div>
          </template>

          <!-- KONDISI 1: PENGAJUAN DAFTAR ULANG PERAKITAN / TAHAP PERAKITAN (BELUM DIVERIFIKASI) -->
          <template
            v-else-if="selectedAdminSetDetail.process_stage === 'perakitan' || selectedAdminSetDetail.verified_status === 'PENDING_ADMIN_ASSY_REREG'">
            <div class="flex items-center gap-2">
              <button type="button"
                @click="adminApproveAssyRereg(selectedAdminSetDetail, 'REJECT'); showAdminSetDetailModal = false;"
                class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-md shadow-rose-600/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
                <span>Tolak Pengajuan</span>
              </button>
              <button type="button"
                @click="adminApproveAssyRereg(selectedAdminSetDetail, 'APPROVE'); showAdminSetDetailModal = false;"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-4 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-2 active:scale-95 cursor-pointer">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Setujui Rakit Ulang (RU)</span>
              </button>
            </div>
          </template>

          <!-- KONDISI 2: BUKAN DAFTAR ULANG PERAKITAN (HOLD / REWORK TEST FUNCTIONAL 1 & 2) (BELUM DIVERIFIKASI) -->
          <template v-else>
            <div class="flex items-center gap-2">
              <!-- Keputusan Cepat Set: Good -->
              <button type="button"
                @click="adminApproveHoldItem(selectedAdminSetDetail, (selectedAdminSetDetail.stage === 'QC' || selectedAdminSetDetail.process_stage === 'qc' || selectedAdminSetDetail.qc_status === 'HOLD_QC' || selectedAdminSetDetail.qc_status === 'REWORK_QC') ? 'APPROVE_GOOD_QC' : 'APPROVE_GOOD'); showAdminSetDetailModal = false;"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <i class="fa-solid fa-circle-check"></i>
                <span
                  v-if="selectedAdminSetDetail.stage === 'QC' || selectedAdminSetDetail.process_stage === 'qc' || selectedAdminSetDetail.qc_status === 'HOLD_QC' || selectedAdminSetDetail.qc_status === 'REWORK_QC'">Lanjut ke Good Receipts</span>
                <span v-else>Setujui Good (Lanjut TF 2 / QC)</span>
              </button>
              <!-- Opsi Ganti Part -->
              <button type="button"
                @click="openReplaceForHoldItem(selectedAdminSetDetail); showAdminSetDetailModal = false;"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
                <i class="fa-solid fa-wrench"></i>
                <span>Ganti Part</span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL TRACKING 1 PART SATUAN ================= -->
    <div v-if="showAdminPartDetailModal && selectedAdminPartDetail"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-3">
            <div
              class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-sm font-black shadow-md flex-shrink-0">
              <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                Tracking Riwayat Part Satuan
              </h3>
              <p class="text-xs text-slate-500 font-mono">
                P9 Part <strong class="text-indigo-600">{{ selectedAdminPartDetail.part_type }}</strong> &bull; SN:
                <strong class="text-slate-900">{{ selectedAdminPartDetail.sn }}</strong>
              </p>
            </div>
          </div>
          <button @click="showAdminPartDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-2 rounded-xl transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Status Part Saat Ini & Target -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Saat Ini</span>
            <span class="px-2 py-0.5 rounded-lg text-xs font-black uppercase inline-block"
              :class="selectedAdminPartDetail.current_status === 'SCRAP' ? 'bg-rose-100 text-rose-800' : (selectedAdminPartDetail.current_status === 'REWORK' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-900')">
              {{ selectedAdminPartDetail.current_status || 'Hold' }}
            </span>
          </div>
          <div class="p-3 bg-emerald-50/60 border border-emerald-200 rounded-2xl space-y-1">
            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Target Pemulihan</span>
            <span
              class="px-2 py-0.5 rounded-lg text-xs font-black text-emerald-800 uppercase inline-block bg-emerald-100/80">
              {{ selectedAdminPartDetail.target_status || 'Tersedia (Ready)' }}
            </span>
          </div>
        </div>

        <!-- KOTAK RIWAYAT BEKAS SET ASAL -->
        <div class="bg-indigo-50/50 border border-indigo-200 rounded-2xl p-4 space-y-3">
          <div class="flex items-center space-x-2 text-indigo-900">
            <i class="fa-solid fa-timeline text-indigo-600"></i>
            <h4 class="font-black text-xs uppercase tracking-wider">Riwayat Bekas Rakitan Asal</h4>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
            <div class="bg-white p-2.5 rounded-xl border border-indigo-100">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Nomor Set Asal</span>
              <strong class="text-slate-900 font-mono">
                {{ selectedAdminPartDetail.previous_set_number ? '#' + selectedAdminPartDetail.previous_set_number :
                'Part Lepasan / Stok' }}
              </strong>
            </div>
            <div class="bg-white p-2.5 rounded-xl border border-indigo-100">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Kode Tray Asal</span>
              <strong class="text-red-700 font-mono">
                {{ selectedAdminPartDetail.previous_tray_code || '-' }}
              </strong>
            </div>
            <div class="bg-white p-2.5 rounded-xl border border-indigo-100">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Dokumen MO</span>
              <strong class="text-slate-800 font-mono">
                {{ (selectedAdminPartDetail.previous_mo && !selectedAdminPartDetail.previous_mo.startsWith('WH/ASSY')) ? selectedAdminPartDetail.previous_mo : '-' }}
              </strong>
            </div>
            <div class="bg-white p-2.5 rounded-xl border border-indigo-100">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Operator Perakitan</span>
              <span class="font-bold text-slate-800">
                {{ selectedAdminPartDetail.previous_pic_assembly || '-' }}
              </span>
            </div>
            <div class="bg-white p-2.5 rounded-xl border border-indigo-100 sm:col-span-2">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Waktu Perakitan Asal</span>
              <span class="text-slate-700 font-sans">
                {{ formatDateTime(selectedAdminPartDetail.previous_assembly_date) || '-' }}
              </span>
            </div>
          </div>

          <!-- Pasangan Part Lain Saat Terpasang di Rakitan -->
          <div
            v-if="selectedAdminPartDetail.previous_part15_sn || selectedAdminPartDetail.previous_part1_sn || selectedAdminPartDetail.previous_part12_sn"
            class="pt-2 border-t border-indigo-200/60">
            <span class="text-[11px] font-bold text-indigo-950 block mb-1.5">Pasangan Part Terpasang Pada Rakitan
              Tersebut:</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 text-[11px] font-mono">
              <div class="p-1.5 rounded-lg bg-white border border-indigo-100 text-slate-700 truncate"
                :title="selectedAdminPartDetail.previous_part15_sn">
                <span class="font-bold text-indigo-700">P15:</span> {{ selectedAdminPartDetail.previous_part15_sn || '-'
                }}
              </div>
              <div class="p-1.5 rounded-lg bg-white border border-indigo-100 text-slate-700 truncate"
                :title="selectedAdminPartDetail.previous_part1_sn">
                <span class="font-bold text-indigo-700">P1:</span> {{ selectedAdminPartDetail.previous_part1_sn || '-'
                }}
              </div>
              <div class="p-1.5 rounded-lg bg-white border border-indigo-100 text-slate-700 truncate"
                :title="selectedAdminPartDetail.previous_part12_sn">
                <span class="font-bold text-indigo-700">P12:</span> {{ selectedAdminPartDetail.previous_part12_sn || '-'
                }}
              </div>
            </div>
          </div>
        </div>

        <!-- KOTAK ALASAN KENAPA DI-HOLD -->
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2 text-xs">
          <div class="flex items-center space-x-2 text-slate-700">
            <i class="fa-solid fa-circle-question text-amber-500"></i>
            <h4 class="font-black text-xs uppercase tracking-wider">Alasan Pelepasan / Di-Hold</h4>
          </div>
          <div class="bg-white border border-slate-200 rounded-xl p-3 text-slate-800 italic leading-relaxed">
            "{{ selectedAdminPartDetail.previous_failure_reasons || selectedAdminPartDetail.previous_assembly_reason || selectedAdminPartDetail.audit_log_reason || selectedAdminPartDetail.stock_last_reason || selectedAdminPartDetail.reason || 'Tidak ada alasan khusus dicatat pada saat pelepasan part.' }}"
          </div>
          <div v-if="selectedAdminPartDetail.reason && selectedAdminPartDetail.reason !== selectedAdminPartDetail.previous_failure_reasons && selectedAdminPartDetail.reason !== selectedAdminPartDetail.previous_assembly_reason"
            class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-2.5">
            <strong>Catatan Pengajuan Daftar Ulang:</strong> {{ selectedAdminPartDetail.reason }}
          </div>
          <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
            <span>Diajukan oleh: <strong>{{ getOperatorFullName(selectedAdminPartDetail.requestor_username)
                }}</strong></span>
            <span>Tanggal Pengajuan: {{ formatDateTime(selectedAdminPartDetail.created_at) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button @click="showAdminPartDetailModal = false" type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>
          <button v-if="selectedAdminPartDetail.status === 'PENDING'" type="button"
            @click="openAdminVerifyModal(selectedAdminPartDetail); showAdminPartDetailModal = false;"
            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Verifikasi Part Ini Sekarang</span>
          </button>
          <span v-else
            class="text-emerald-700 font-bold bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl text-xs inline-flex items-center gap-1 select-none">
            <i class="fa-solid fa-lock text-[10px]"></i> Selesai (Terkunci)
          </span>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL PERGANTIAN PART ================= -->
    <div v-if="showAdminReplacementDetailModal && selectedAdminReplacementDetail"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 sticky top-0 bg-white z-10">
          <div class="flex items-center space-x-3">
            <div
              class="w-11 h-11 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-lg shadow-md shadow-amber-600/20 flex-shrink-0">
              <i class="fa-solid fa-wrench"></i>
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                  Detail Penggantian Part Rakitan
                </h3>
                <span class="bg-amber-600 text-white text-[11px] font-black px-2 py-0.5 rounded-lg font-mono">
                  Set #{{ selectedAdminReplacementDetail.set_number }}
                </span>
                <!-- Badge Tahap Proses Terakhir -->
                <span v-if="getReplacementStage(selectedAdminReplacementDetail) === 'qc'"
                  class="bg-purple-100 text-purple-900 border border-purple-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-award text-purple-600 text-[9px]"></i> QC (TF 2)
                </span>
                <span v-else-if="getReplacementStage(selectedAdminReplacementDetail) === 'test_functional_1'"
                  class="bg-blue-100 text-blue-900 border border-blue-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-microchip text-blue-600 text-[9px]"></i> TF 1
                </span>
                <span v-else
                  class="bg-slate-100 text-slate-800 border border-slate-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-screwdriver-wrench text-slate-600 text-[9px]"></i> Perakitan
                </span>

                <span
                  v-if="selectedAdminReplacementDetail.replacement_status === 'APPROVED' || selectedAdminReplacementDetail.status === 'APPROVED'"
                  class="bg-emerald-100 text-emerald-900 border border-emerald-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[9px]"></i> Selesai (Disetujui)
                </span>
                <span
                  v-else-if="selectedAdminReplacementDetail.replacement_status === 'REJECTED' || selectedAdminReplacementDetail.status === 'REJECTED'"
                  class="bg-rose-100 text-rose-900 border border-rose-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-lock text-[9px]"></i> Selesai (Ditolak)
                </span>
                <span v-else
                  class="bg-amber-100 text-amber-900 border border-amber-300 font-black text-[10px] uppercase px-2 py-0.5 rounded-lg">
                  Menunggu Verifikasi
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium mt-0.5 font-mono">
                {{ selectedAdminReplacementDetail.tray_code }}
              </p>
            </div>
          </div>
          <button @click="showAdminReplacementDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-2 rounded-xl transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Body -->
        <div class="space-y-3.5 text-xs">
          <!-- Info Grid Transaksi -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Dokumen WO / Tray</span>
              <span class="font-bold text-slate-800 font-mono">{{ selectedAdminReplacementDetail.tray_code || '-'
                }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor MO</span>
              <span class="font-bold text-slate-800 font-mono">{{ selectedAdminReplacementDetail.source_document_mo ||
                '-' }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Nomor Set</span>
              <span class="font-bold text-slate-800 font-mono">Set #{{ selectedAdminReplacementDetail.set_number
                }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">PIC Perakitan</span>
              <span class="font-bold text-slate-800">{{ getOperatorFullName(selectedAdminReplacementDetail.pic_assembly
                || selectedAdminReplacementDetail.replacement_requestor) }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Tahap Proses Terakhir</span>
              <span class="font-black uppercase text-[11px]"
                :class="getReplacementStage(selectedAdminReplacementDetail) === 'qc' ? 'text-purple-800' : (getReplacementStage(selectedAdminReplacementDetail) === 'test_functional_1' ? 'text-blue-800' : 'text-slate-800')">
                {{ getReplacementStageLabel(selectedAdminReplacementDetail) }}
              </span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Status Assembly</span>
              <span class="font-bold text-slate-700 uppercase">{{ selectedAdminReplacementDetail.assembly_status ||
                selectedAdminReplacementDetail.status || '-' }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Pengujian TF 1</span>
              <span class="font-bold uppercase text-[11px]"
                :class="['Good', 'PASS'].includes(selectedAdminReplacementDetail.assy_test_status) ? 'text-emerald-700' : (['Hold TF 1', 'Hold', 'FAIL'].includes(selectedAdminReplacementDetail.assy_test_status) ? 'text-amber-700' : 'text-slate-600')">{{
                selectedAdminReplacementDetail.assy_test_status || 'Belum Uji' }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase block">Status QC (TF 2)</span>
              <span class="font-bold uppercase text-[11px]"
                :class="['GOOD', 'PASS'].includes(selectedAdminReplacementDetail.qc_status) ? 'text-emerald-700' : (['HOLD_QC', 'REWORK_QC', 'HOLD'].includes(selectedAdminReplacementDetail.qc_status) ? 'text-rose-700' : 'text-slate-600')">{{
                selectedAdminReplacementDetail.qc_status || 'Belum QC' }}</span>
            </div>
          </div>

          <!-- Alur Tindak Lanjut Verifikasi Rakit Ulang -->
          <div class="bg-indigo-50/80 border border-indigo-200 rounded-2xl p-3 flex items-start gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
              <i class="fa-solid fa-arrows-rotate"></i>
            </div>
            <div class="space-y-0.5 text-xs text-indigo-950">
              <span class="font-black uppercase text-[10.5px] text-indigo-900 block">Alur Verifikasi Rakit Ulang:</span>
              <p class="leading-relaxed text-[11px]">
                Unit Set ini diajukan dari kendala tahap <strong>{{ getReplacementStageLabel(selectedAdminReplacementDetail) }}</strong>. Setelah persetujuan Admin, part rusak akan diperbarui dan unit otomatis masuk ke antrean <strong>Rakit Ulang (RU)</strong> untuk diverifikasi dan dirakit kembali sebelum diuji ulang.
              </p>
            </div>
          </div>

          <!-- Rincian Set Serial Number Rakitan Unit -->
          <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-3.5 space-y-2.5">
            <div class="flex items-center justify-between border-b border-amber-200/70 pb-1.5">
              <span class="text-xs font-black uppercase text-amber-950 flex items-center gap-1.5">
                <i class="fa-solid fa-boxes-stacked text-amber-600"></i> Rincian Lengkap Set Serial Number Unit
              </span>
              <span class="text-[10.5px] font-bold text-slate-500">
                Pengaju Ganti: <strong class="text-slate-800">{{
                  getOperatorFullName(selectedAdminReplacementDetail.replacement_requestor) }}</strong>
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs font-mono">
              <!-- Part 15 -->
              <div class="bg-white rounded-xl p-2.5 border shadow-2xs"
                :class="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '15') ? 'border-amber-400 ring-2 ring-amber-200 bg-amber-50/30' : 'border-slate-200'">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-sans font-bold text-[10.5px] text-slate-600">P9 Part 15</span>
                  <span v-if="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '15')"
                    class="font-sans text-[9px] bg-amber-500 text-white font-black px-1.5 py-0.2 rounded">
                    DIGANTI
                  </span>
                  <span v-else class="font-sans text-[9px] text-slate-400 font-bold">TETAP</span>
                </div>
                <div class="font-bold text-slate-800 truncate" :title="selectedAdminReplacementDetail.part15_sn">
                  {{ selectedAdminReplacementDetail.part15_sn || '-' }}
                </div>
              </div>
              <!-- Part 1 -->
              <div class="bg-white rounded-xl p-2.5 border shadow-2xs"
                :class="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '1') ? 'border-amber-400 ring-2 ring-amber-200 bg-amber-50/30' : 'border-slate-200'">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-sans font-bold text-[10.5px] text-slate-600">P9 Part 1</span>
                  <span v-if="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '1')"
                    class="font-sans text-[9px] bg-amber-500 text-white font-black px-1.5 py-0.2 rounded">
                    DIGANTI
                  </span>
                  <span v-else class="font-sans text-[9px] text-slate-400 font-bold">TETAP</span>
                </div>
                <div class="font-bold text-slate-800 truncate" :title="selectedAdminReplacementDetail.part1_sn">
                  {{ selectedAdminReplacementDetail.part1_sn || '-' }}
                </div>
              </div>
              <!-- Part 12 -->
              <div class="bg-white rounded-xl p-2.5 border shadow-2xs"
                :class="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '12') ? 'border-amber-400 ring-2 ring-amber-200 bg-amber-50/30' : 'border-slate-200'">
                <div class="flex items-center justify-between mb-1">
                  <span class="font-sans font-bold text-[10.5px] text-slate-600">P9 Part 12</span>
                  <span v-if="parseReplacements(selectedAdminReplacementDetail).some(r => String(r.part_type) === '12')"
                    class="font-sans text-[9px] bg-amber-500 text-white font-black px-1.5 py-0.2 rounded">
                    DIGANTI
                  </span>
                  <span v-else class="font-sans text-[9px] text-slate-400 font-bold">TETAP</span>
                </div>
                <div class="font-bold text-slate-800 truncate" :title="selectedAdminReplacementDetail.part12_sn">
                  {{ selectedAdminReplacementDetail.part12_sn || '-' }}
                </div>
              </div>
            </div>
            <div class="flex items-center justify-between text-[10.5px] text-slate-500 pt-1">
              <span>Waktu Pengajuan: <strong class="font-mono text-slate-700">{{
                  formatDateTime(selectedAdminReplacementDetail.replacement_created_at) }}</strong></span>
              <span v-if="selectedAdminReplacementDetail.verified_at">Diverifikasi: <strong
                  class="font-mono text-slate-700">{{ formatDateTime(selectedAdminReplacementDetail.verified_at)
                  }}</strong></span>
            </div>
          </div>

          <!-- Alasan Penggantian -->
          <div v-if="selectedAdminReplacementDetail.replacement_reason"
            class="p-3 bg-amber-50 rounded-2xl border border-amber-200">
            <span class="text-[10.5px] font-bold text-amber-900 block mb-1">
              <i class="fa-solid fa-comment-dots mr-1"></i> Alasan Penggantian:
            </span>
            <p class="text-xs text-amber-950 font-medium">{{ selectedAdminReplacementDetail.replacement_reason }}</p>
          </div>

          <!-- Daftar Komponen yang Diganti -->
          <div class="space-y-2">
            <span class="text-xs font-black text-slate-800 uppercase tracking-wider block">
              Daftar Komponen Part yang Diganti:
            </span>
            <div class="space-y-2">
              <div v-for="(rep, rIdx) in parseReplacements(selectedAdminReplacementDetail)" :key="rIdx"
                class="bg-white rounded-xl p-3 border border-slate-200 shadow-xs flex items-center justify-between">
                <div>
                  <span class="text-xs font-bold text-slate-800 block">P9 Part {{ rep.part_type }}</span>
                  <span class="text-[11px] text-slate-500 font-sans">{{ rep.part_name || ('Komponen Part ' +
                    rep.part_type) }}</span>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs">
                  <div class="text-right">
                    <span class="text-[9.5px] text-slate-400 block uppercase font-sans font-bold">Part Lama</span>
                    <span
                      class="line-through text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded border border-rose-200">{{
                      rep.old_sn || '-' }}</span>
                  </div>
                  <i class="fa-solid fa-arrow-right text-amber-600 text-xs"></i>
                  <div>
                    <span class="text-[9.5px] text-slate-400 block uppercase font-sans font-bold">Part Pengganti</span>
                    <span
                      class="font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">{{
                      rep.new_sn }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Admin Notes if any -->
          <div v-if="selectedAdminReplacementDetail.admin_notes"
            class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
            <span class="text-[10.5px] font-bold text-slate-700 block mb-1">Catatan Admin:</span>
            <p class="text-xs text-slate-800">{{ selectedAdminReplacementDetail.admin_notes }}</p>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button @click="showAdminReplacementDetailModal = false" type="button"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>
          <div
            v-if="selectedAdminReplacementDetail.status === 'PENDING' || selectedAdminReplacementDetail.replacement_status === 'PENDING'"
            class="flex items-center gap-2">
            <button type="button"
              @click="adminRejectReplacement(selectedAdminReplacementDetail); showAdminReplacementDetailModal = false;"
              class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition cursor-pointer">
              Tolak
            </button>
            <button type="button"
              @click="adminApproveReplacement(selectedAdminReplacementDetail); showAdminReplacementDetailModal = false;"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs shadow-md shadow-emerald-600/20 transition cursor-pointer">
              Setujui Ganti Part
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL & TRACKING PART MASTER STOK ================= -->
    <div v-if="showMasterPartTrackingModal"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] overflow-y-auto">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 sticky top-0 bg-white z-10">
          <div class="flex items-center space-x-3">
            <div
              class="w-11 h-11 rounded-2xl bg-red-600 text-white flex items-center justify-center text-lg shadow-md shadow-red-600/20 flex-shrink-0">
              <i class="fa-solid fa-microchip"></i>
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">
                  Tracking Detail &amp; Riwayat Part
                </h3>
                <span
                  class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase font-mono bg-red-100 text-red-800 border border-red-200">
                  Part {{ selectedMasterPartRow?.part_type }}
                </span>
              </div>
              <p class="text-xs text-slate-500 font-mono mt-0.5">
                SN: <strong class="text-slate-900">{{ selectedMasterPartRow?.sn }}</strong>
                <span v-if="selectedMasterPartRow?.cs" class="text-slate-400 font-sans"> &bull; Kode CS: <strong
                    class="text-slate-700 font-mono">{{ selectedMasterPartRow?.cs }}</strong></span>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="showMasterPartTrackingModal = false" type="button"
              class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer active:scale-95">
              <i class="fa-solid fa-arrow-left text-xs"></i>
              <span>Kembali</span>
            </button>
            <button @click="showMasterPartTrackingModal = false"
              class="text-slate-400 hover:text-slate-700 p-2 rounded-xl transition cursor-pointer" title="Tutup">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoadingMasterPartDetail" class="py-12 text-center text-slate-400 space-y-2">
          <i class="fa-solid fa-circle-notch fa-spin text-2xl text-red-600"></i>
          <p class="text-xs font-medium">Memuat riwayat dan alur proses part...</p>
        </div>

        <!-- Content Loaded -->
        <div v-else class="space-y-4">

          <!-- 3 Quick Stat Cards: Status Terkini, Alasan, & Lokasi/Proses -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <!-- 1. Status Terkini -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Kondisi</span>
              <div class="flex items-center gap-1.5 flex-wrap">
                <span
                  :class="getStatusBadgeClass(masterPartTrackingData?.stock?.status || selectedMasterPartRow?.effective_status || selectedMasterPartRow?.stock_status)"
                  class="px-2.5 py-0.5 rounded-lg text-xs font-black uppercase border inline-flex items-center gap-1">
                  {{ masterPartTrackingData?.stock?.status || selectedMasterPartRow?.effective_status ||
                  selectedMasterPartRow?.stock_status || 'Tersedia' }}
                </span>
                <span
                  v-if="masterPartTrackingData?.stock?.is_replaced_from_assy || selectedMasterPartRow?.is_replaced_from_assy"
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300">
                  Lepasan Rakitan
                </span>
              </div>
            </div>

            <!-- 2. Alasan Terakhir -->
            <div class="p-3 bg-amber-50/60 border border-amber-200 rounded-2xl space-y-1">
              <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Alasan Terakhir</span>
              <p class="text-xs font-semibold text-amber-950 truncate"
                :title="masterPartTrackingData?.stock?.last_reason || masterPartTrackingData?.assembly?.reason || '-'">
                {{ masterPartTrackingData?.stock?.last_reason || masterPartTrackingData?.assembly?.reason || 'Tidak ada catatan khusus' }}
              </p>
              <span class="text-[9.5px] text-amber-700 block truncate"
                v-if="(masterPartTrackingData?.stock?.last_operator || masterPartTrackingData?.assembly?.pic_assembly_name || masterPartTrackingData?.assembly?.pic_assembly) && (masterPartTrackingData?.stock?.last_operator !== '-' || masterPartTrackingData?.assembly?.pic_assembly_name !== '-')">
                Oleh: {{ masterPartTrackingData?.stock?.last_operator ||
                masterPartTrackingData?.assembly?.pic_assembly_name || masterPartTrackingData?.assembly?.pic_assembly }}
              </span>
            </div>

            <!-- 3. Posisi / Alokasi Saat Ini -->
            <div class="p-3 bg-indigo-50/60 border border-indigo-200 rounded-2xl space-y-1">
              <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Posisi / Alokasi</span>
              <p class="text-xs font-black text-indigo-950 font-mono truncate">
                {{ (!masterPartTrackingData?.stock?.allocated_tray_id || masterPartTrackingData?.stock?.status === 'Tersedia') ? 'Gudang (Ready)' : (masterPartTrackingData?.tray?.tray_code || 'Gudang (Ready)') }}
              </p>
              <span class="text-[9.5px] text-indigo-700 block truncate">
                {{ masterPartTrackingData?.stock?.status === 'Tersedia' ? 'Stok bebas, siap alokasi baru' : (masterPartTrackingData?.stock?.allocated_tray_id && masterPartTrackingData?.assembly ? 'Terpasang di Set #' + masterPartTrackingData.assembly.set_number : (masterPartTrackingData?.assembly ? 'Pernah di Set #' + masterPartTrackingData.assembly.set_number + ' (Lepasan)' : 'Belum dialokasikan ke Tray')) }}
              </span>
            </div>
          </div>

          <!-- SECTION ALUR PROSES & SIKLUS PART (STEPPER) -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 space-y-3 shadow-xs">
            <div class="flex items-center space-x-2 text-slate-800 border-b border-slate-100 pb-2.5">
              <i class="fa-solid fa-timeline text-red-600"></i>
              <h4 class="font-black text-xs uppercase tracking-wider">Alur Proses &amp; Siklus Part</h4>
            </div>

            <div
              class="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">

              <!-- Step 1: Registrasi Master Database -->
              <div class="relative">
                <div
                  class="absolute -left-6 top-0.5 w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] ring-4 ring-white">
                  <i class="fa-solid fa-check"></i>
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1">
                  <div class="flex items-center justify-between flex-wrap gap-1">
                    <strong class="text-slate-900 font-bold">1. Registrasi Master Stok</strong>
                    <span class="text-[10px] text-slate-500">{{
                      formatDateTime(masterPartTrackingData?.stock?.created_at) || '-' }}</span>
                  </div>
                  <p class="text-slate-600 text-[11px]">
                    Part P9 tipe <strong class="text-slate-800">Part {{ masterPartTrackingData?.stock?.part_type
                      }}</strong> terdaftar dengan nomor seri
                    <strong class="font-mono text-slate-900">{{ masterPartTrackingData?.stock?.sn }}</strong>.
                    <span v-if="masterPartTrackingData?.stock?.cs">Kode PDF CS: <strong
                        class="font-mono text-slate-800">{{ masterPartTrackingData?.stock?.cs }}</strong>.</span>
                  </p>
                </div>
              </div>

              <!-- Step 2: Alokasi Tray / Work Order -->
              <div class="relative">
                <div
                  class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] ring-4 ring-white"
                  :class="masterPartTrackingData?.tray ? 'bg-blue-600 text-white' : 'bg-slate-300 text-slate-600'">
                  <i :class="masterPartTrackingData?.tray ? 'fa-solid fa-boxes-packing' : 'fa-solid fa-minus'"></i>
                </div>
                <div class="p-3 rounded-xl border text-xs space-y-1"
                  :class="masterPartTrackingData?.tray ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 text-slate-400'">
                  <div class="flex items-center justify-between flex-wrap gap-1">
                    <strong :class="masterPartTrackingData?.tray ? 'text-blue-900 font-bold' : 'text-slate-500'">2.
                      Supply Gudang &amp; Alokasi Tray (WO)</strong>
                    <span v-if="masterPartTrackingData?.tray" class="text-[10px] text-blue-700 font-mono">
                      {{ formatDateTime(masterPartTrackingData?.stock?.allocated_at ||
                      masterPartTrackingData?.tray?.created_at) }}
                    </span>
                  </div>
                  <div v-if="masterPartTrackingData?.tray" class="text-slate-700 text-[11px] space-y-0.5">
                    <div>No. Dokumen Tray: <strong class="font-mono text-blue-700">{{
                        masterPartTrackingData.tray.tray_code }}</strong> (MO: {{
                      masterPartTrackingData.tray.source_document_mo || '-' }})</div>
                    <div>Petugas Gudang: <strong class="text-slate-900">{{
                        masterPartTrackingData.tray.pic_warehouse_name || masterPartTrackingData.tray.pic_warehouse ||
                        '-' }}</strong></div>
                    <div>Operator Ditugaskan: <strong class="text-slate-900">{{
                        masterPartTrackingData.tray.assigned_operator_name ||
                        masterPartTrackingData.tray.assigned_operator || 'Semua Operator' }}</strong></div>
                  </div>
                  <p v-else class="text-[11px] italic">Part belum disupply ke dalam Tray / Work Order.</p>
                </div>
              </div>

              <!-- Step 3: Pengerjaan Perakitan (Assembly) -->
              <div class="relative">
                <div
                  class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] ring-4 ring-white"
                  :class="masterPartTrackingData?.assembly ? 'bg-purple-600 text-white' : 'bg-slate-300 text-slate-600'">
                  <i
                    :class="masterPartTrackingData?.assembly ? 'fa-solid fa-screwdriver-wrench' : 'fa-solid fa-minus'"></i>
                </div>
                <div class="p-3 rounded-xl border text-xs space-y-1.5"
                  :class="masterPartTrackingData?.assembly ? 'bg-purple-50/40 border-purple-200' : 'bg-slate-50 border-slate-200 text-slate-400'">
                  <div class="flex items-center justify-between flex-wrap gap-1">
                    <strong
                      :class="masterPartTrackingData?.assembly ? 'text-purple-950 font-bold' : 'text-slate-500'">3.
                      Proses Perakitan (Assembly)</strong>
                    <span v-if="masterPartTrackingData?.assembly" class="text-[10px] text-purple-700 font-mono">
                      {{ formatDateTime(masterPartTrackingData?.assembly?.finished_at ||
                      masterPartTrackingData?.assembly?.started_at) }}
                    </span>
                  </div>
                  <div v-if="masterPartTrackingData?.assembly" class="text-slate-700 text-[11px] space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span>Set: <strong class="font-mono text-purple-900">#{{
                          masterPartTrackingData.assembly.set_number }}</strong></span>
                      <span>Operator: <strong class="text-slate-900">{{
                          masterPartTrackingData.assembly.pic_assembly_name ||
                          masterPartTrackingData.assembly.pic_assembly || '-' }}</strong></span>
                      <span>Status:
                        <span :class="getStatusBadgeClass(masterPartTrackingData.assembly.status)"
                          class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase border">
                          {{ masterPartTrackingData.assembly.status || 'Good' }}
                        </span>
                      </span>
                    </div>

                    <!-- Pasangan Part dalam Set Tersebut -->
                    <div class="p-2 bg-white rounded-lg border border-purple-100 text-[10.5px]">
                      <span class="font-bold text-slate-500 block mb-0.5">Pasangan Part Saat Dirakit:</span>
                      <div class="grid grid-cols-3 gap-1 font-mono">
                        <div class="truncate"
                          :class="selectedMasterPartRow?.part_type === '15' ? 'text-red-700 font-black' : 'text-slate-600'">
                          P15: {{ masterPartTrackingData.assembly.part15_sn || '-' }}
                        </div>
                        <div class="truncate"
                          :class="selectedMasterPartRow?.part_type === '1' ? 'text-red-700 font-black' : 'text-slate-600'">
                          P1: {{ masterPartTrackingData.assembly.part1_sn || '-' }}
                        </div>
                        <div class="truncate"
                          :class="selectedMasterPartRow?.part_type === '12' ? 'text-red-700 font-black' : 'text-slate-600'">
                          P12: {{ masterPartTrackingData.assembly.part12_sn || '-' }}
                        </div>
                      </div>
                    </div>

                    <div v-if="masterPartTrackingData.assembly.reason"
                      class="text-amber-900 bg-amber-50/80 border border-amber-200 p-2 rounded-lg text-[10.5px]">
                      <strong>Catatan / Alasan:</strong> {{ masterPartTrackingData.assembly.reason }}
                    </div>
                  </div>
                  <p v-else class="text-[11px] italic">Part belum dirakit ke dalam unit set rakitan.</p>
                </div>
              </div>

              <!-- Step 4: Pengujian Mutu (QC) & Receipts -->
              <div class="relative">
                <div
                  class="absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] ring-4 ring-white"
                  :class="(masterPartTrackingData?.assembly?.qc_status || masterPartTrackingData?.assembly_tests?.length) ? 'bg-teal-600 text-white' : 'bg-slate-300 text-slate-600'">
                  <i
                    :class="(masterPartTrackingData?.assembly?.qc_status || masterPartTrackingData?.assembly_tests?.length) ? 'fa-solid fa-clipboard-check' : 'fa-solid fa-minus'"></i>
                </div>
                <div class="p-3 rounded-xl border text-xs space-y-1"
                  :class="(masterPartTrackingData?.assembly?.qc_status || masterPartTrackingData?.assembly_tests?.length) ? 'bg-teal-50/40 border-teal-200' : 'bg-slate-50 border-slate-200 text-slate-400'">
                  <div class="flex items-center justify-between flex-wrap gap-1">
                    <strong
                      :class="(masterPartTrackingData?.assembly?.qc_status || masterPartTrackingData?.assembly_tests?.length) ? 'text-teal-950 font-bold' : 'text-slate-500'">
                      4. Pengujian Mutu QC &amp; Receipts
                    </strong>
                    <span v-if="masterPartTrackingData?.assembly?.qc_tested_at"
                      class="text-[10px] text-teal-700 font-mono">
                      {{ formatDateTime(masterPartTrackingData.assembly.qc_tested_at) }}
                    </span>
                  </div>
                  <div
                    v-if="masterPartTrackingData?.assembly?.qc_status || masterPartTrackingData?.assembly_tests?.length"
                    class="text-slate-700 text-[11px] space-y-1">
                    <div>
                      Status QC: <strong class="text-teal-900">{{ masterPartTrackingData?.assembly?.qc_status ||
                        'SELESAI' }}</strong>
                      <span v-if="masterPartTrackingData?.assembly?.qc_tested_by"> &bull; Tester: {{
                        masterPartTrackingData.assembly.qc_tested_by }}</span>
                    </div>
                    <div v-if="masterPartTrackingData?.assembly?.qc_failure_reasons"
                      class="text-red-900 bg-red-50 border border-red-200 p-1.5 rounded text-[10px]">
                      <strong>Kegagalan Uji QC:</strong> {{ masterPartTrackingData.assembly.qc_failure_reasons }}
                    </div>
                    <div v-if="masterPartTrackingData?.assembly?.warehouse_receipt_status">
                      Status Receipts:
                      <span class="font-bold text-slate-900 uppercase">{{
                        masterPartTrackingData.assembly.warehouse_receipt_status }}</span>
                      <span v-if="masterPartTrackingData.assembly.warehouse_receipt_by"> (Petugas: {{
                        masterPartTrackingData.assembly.warehouse_receipt_by }})</span>
                    </div>
                  </div>
                  <p v-else class="text-[11px] italic">Part belum masuk tahap pengujian mutu QC atau Receipts.</p>
                </div>
              </div>

            </div>
          </div>

          <!-- SECTION RIWAYAT AUDIT LOG PERGANTIAN PART / PELEPASAN -->
          <div v-if="masterPartTrackingData?.audit_logs?.length"
            class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2.5 text-xs">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
              <div class="flex items-center space-x-2 text-slate-800">
                <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                <h4 class="font-black text-xs uppercase tracking-wider">Riwayat Log Pergantian &amp; Status Part</h4>
              </div>
              <span class="text-[10px] text-slate-500 font-bold font-mono">{{ masterPartTrackingData.audit_logs.length
                }} Catatan</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-[11px]">
                <thead class="text-[10px] text-slate-500 uppercase font-bold border-b border-slate-200">
                  <tr>
                    <th class="py-1.5 px-2">Waktu</th>
                    <th class="py-1.5 px-2">Aksi</th>
                    <th class="py-1.5 px-2">Perubahan Status</th>
                    <th class="py-1.5 px-2">Aktor</th>
                    <th class="py-1.5 px-2">Alasan &amp; Keterangan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-sans">
                  <tr v-for="log in masterPartTrackingData.audit_logs" :key="log.id" class="hover:bg-white transition">
                    <td class="py-2 px-2 text-slate-500 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
                    <td class="py-2 px-2 font-bold text-slate-800">{{ log.action_type }}</td>
                    <td class="py-2 px-2 whitespace-nowrap">
                      <span class="text-slate-500">{{ log.old_status }}</span> &rarr;
                      <strong class="text-slate-900">{{ log.new_status }}</strong>
                    </td>
                    <td class="py-2 px-2 font-medium text-slate-800">
                      <span class="font-bold text-slate-900 block">{{ (log.actor_full_name && log.actor_full_name !== '-' && log.actor_full_name !== 'operator' && log.actor_full_name !== 'gudang') ? log.actor_full_name : (getOperatorFullName(log.actor_username) !== '-' ? getOperatorFullName(log.actor_username) : (log.actor_username && !['operator','gudang','tester','-'].includes(log.actor_username) ? log.actor_username : 'Administrator NKRI')) }}</span>
                    </td>
                    <td class="py-2 px-2 text-slate-600 italic leading-relaxed">{{ log.reason || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- SECTION RIWAYAT PENDAFTARAN ULANG JIKA ADA -->
          <div v-if="masterPartTrackingData?.re_register_requests?.length"
            class="bg-amber-50/50 border border-amber-200 rounded-2xl p-4 space-y-2 text-xs">
            <div class="flex items-center space-x-2 text-amber-900 border-b border-amber-200 pb-2">
              <i class="fa-solid fa-list-check text-amber-600"></i>
              <h4 class="font-black text-xs uppercase tracking-wider">Riwayat Pengajuan Pendaftaran Ulang</h4>
            </div>
            <div class="space-y-2">
              <div v-for="req in masterPartTrackingData.re_register_requests" :key="req.id"
                class="bg-white p-3 rounded-xl border border-amber-200/80 text-[11px] space-y-1">
                <div class="flex items-center justify-between flex-wrap gap-1">
                  <span class="font-bold text-slate-900">
                    {{ req.current_status }} &rarr; Target: <span class="text-emerald-700">{{ req.target_status
                      }}</span>
                  </span>
                  <span
                    :class="req.status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : (req.status === 'DECLINED' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')"
                    class="px-2 py-0.5 rounded text-[9.5px] font-black uppercase border">
                    {{ req.status }}
                  </span>
                </div>
                <div class="text-slate-700 italic">"{{ req.reason || 'Tidak ada alasan' }}"</div>
                <div
                  class="text-[10px] text-slate-500 flex items-center justify-between pt-1 border-t border-slate-100">
                  <span>Diajukan: {{ getOperatorFullName(req.requestor_username) }} ({{ formatDateTime(req.created_at)
                    }})</span>
                  <span v-if="req.admin_username">Disetujui: {{ getOperatorFullName(req.admin_username) }} ({{
                    formatDateTime(req.approved_at) }})</span>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Footer Actions -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button @click="showMasterPartTrackingModal = false" type="button"
            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition flex items-center gap-2 active:scale-95 cursor-pointer shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali</span>
          </button>

          <button
            v-if="(masterPartTrackingData?.stock?.is_replaced_from_assy || selectedMasterPartRow?.is_replaced_from_assy)
              && !String(masterPartTrackingData?.stock?.status || selectedMasterPartRow?.effective_status || selectedMasterPartRow?.stock_status || '').toLowerCase().includes('tersedia')
              && !String(masterPartTrackingData?.stock?.status || selectedMasterPartRow?.effective_status || selectedMasterPartRow?.stock_status || '').toLowerCase().includes('pending')
              && !String(masterPartTrackingData?.stock?.status || selectedMasterPartRow?.effective_status || selectedMasterPartRow?.stock_status || '').toLowerCase().includes('menunggu')
              && !(masterPartTrackingData?.re_register_requests && masterPartTrackingData.re_register_requests.some(r => String(r.status || '').toUpperCase() === 'PENDING'))"
            @click="openReregisterModal(masterPartTrackingData?.stock || selectedMasterPartRow); showMasterPartTrackingModal = false;"
            type="button"
            class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md shadow-amber-600/20 transition flex items-center gap-1.5 active:scale-95 cursor-pointer">
            <i class="fa-solid fa-rotate-left"></i>
            <span>Daftar Ulang Part Ini</span>
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL SELESAIKAN SESI (3 OPSI) ================= -->
    <div v-if="showSessionModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-flag-checkered"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Selesaikan Sesi Pengerjaan</h3>
              <p class="text-[11px] text-slate-500 font-mono">Tray: <strong>{{ currentTray ? currentTray.tray_code : ''
                  }}</strong></p>
            </div>
          </div>
          <button @click="showSessionModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
          Pilih salah satu tindakan untuk mengakhiri sesi pengerjaan perakitan Tray ini:
        </p>

        <!-- 2 Action Options -->
        <div class="space-y-3">
          <!-- Option 1: KEMBALIKAN KE GUDANG -->
          <div @click="sessionForm.action = 'RETURN_WAREHOUSE'"
            :class="sessionForm.action === 'RETURN_WAREHOUSE' ? 'border-red-500 bg-red-50/40 ring-2 ring-red-200' : 'border-slate-200 hover:border-slate-300 bg-white'"
            class="border rounded-2xl p-3.5 transition cursor-pointer flex items-start space-x-3">
            <div class="mt-0.5">
              <input type="radio" v-model="sessionForm.action" value="RETURN_WAREHOUSE"
                class="text-red-600 focus:ring-red-500">
            </div>
            <div class="space-y-0.5">
              <h4 class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                <i class="fa-solid fa-boxes-packing text-red-600"></i>
                <span>1. Kembalikan ke Gudang</span>
              </h4>
              <p class="text-[11px] text-slate-500">Hanya set rakitan yang <strong>belum dirakit</strong> yang akan
                dikembalikan ke Gudang (menunggu verifikasi fisik oleh Gudang sebelum berstatus <strong
                  class="text-emerald-700">Tersedia</strong>).
                Rakitan yang sudah selesai tetap aman tersimpan pada riwayat perakitan dan QC.</p>
            </div>
          </div>

          <!-- Option 2: PINDAHKAN SISA RAKITAN KE SHIFT BERIKUTNYA -->
          <div @click="sessionForm.action = 'HANDOVER_SHIFT'"
            :class="sessionForm.action === 'HANDOVER_SHIFT' ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-200' : 'border-slate-200 hover:border-slate-300 bg-white'"
            class="border rounded-2xl p-3.5 transition cursor-pointer flex items-start space-x-3">
            <div class="mt-0.5">
              <input type="radio" v-model="sessionForm.action" value="HANDOVER_SHIFT"
                class="text-emerald-600 focus:ring-emerald-500">
            </div>
            <div class="space-y-0.5 w-full">
              <h4 class="text-xs font-black text-slate-900 flex items-center gap-1.5">
                <i class="fa-solid fa-users text-emerald-600"></i>
                <span>2. Pindahkan Sisa Rakitan ke Shift Selanjutnya (Handover Operator)</span>
              </h4>
              <p class="text-[11px] text-slate-500">Hanya rakitan yang <strong>belum dirakit</strong> yang akan
                dipindahkan ke operator penerima. Rakitan yang sudah selesai tetap tersimpan rapi pada riwayat operator
                awal.</p>

              <!-- Dropdown Operator Penerima (Hanya muncul jika opsi 2 dipilih) -->
              <div v-if="sessionForm.action === 'HANDOVER_SHIFT'" class="pt-2">
                <label class="block text-[10px] font-bold uppercase text-slate-700 mb-1">
                  Pilih Operator Shift Penerima <span class="text-red-600">*</span>
                </label>
                <select v-model="sessionForm.new_operator"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-600 min-h-[38px] cursor-pointer">
                  <option value="" disabled>-- Pilih Operator Assembly --</option>
                  <option v-for="op in operatorUsersList" :key="op.username" :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Catatan Alasan / Keterangan Sesi -->
        <div>
          <label class="block text-xs font-bold uppercase text-slate-700 mb-1">
            Catatan / Keterangan Sesi:
          </label>
          <textarea v-model="sessionForm.reason" rows="2"
            placeholder="Tuliskan catatan sesi, kendala, atau pesan untuk penerima..."
            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-xs text-slate-900 focus:outline-none focus:border-red-600 font-medium"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="showSessionModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Batal
          </button>
          <button type="button" @click="submitSessionAction"
            class="bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-red-600/25 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-check"></i>
            <span>Konfirmasi Selesaikan Sesi</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL JEDA / DOWNTIME PERAKITAN ================= -->
    <div v-if="showPauseModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-pause"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Jeda Pengerjaan Perakitan</h3>
              <p class="text-[11px] text-slate-500 font-mono">Tray: <strong>{{ currentTray ? currentTray.tray_code : ''
                  }}</strong></p>
            </div>
          </div>
          <button @click="showPauseModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3.5">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">
              Pilih Alasan Jeda / Downtime <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="r in ['Istirahat / Sholat / Toilet', 'Menunggu Komponen Gudang', 'Kerusakan Alat / Mesin', 'Briefing / Diskusi Tim', 'Pemeriksaan Kualitas (QC)', 'Lainnya']"
                :key="r" type="button" @click="pauseForm.reason = r"
                :class="pauseForm.reason === r ? 'bg-amber-600 text-white font-bold shadow-sm ring-2 ring-amber-400' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200 font-semibold'"
                class="p-2.5 rounded-xl text-xs transition text-center cursor-pointer min-h-[40px] flex items-center justify-center">
                {{ r }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Keterangan Tambahan / Catatan
              Kendala:</label>
            <input type="text" v-model="pauseForm.customReason" placeholder="Rincian kendala atau catatan..."
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-amber-600 font-medium">
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="showPauseModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
            Batal
          </button>
          <button type="button" @click="submitPauseAssembly"
            class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-amber-600/25 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-pause"></i>
            <span>Mulai Jeda</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DETAIL VERIFIKASI DOKUMEN LAPORAN ================= -->
    <div v-if="showLaporanDetailModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-shrink-0">
          <div>
            <h3 class="font-black text-base sm:text-lg text-slate-900">
              Detail Verifikasi Dokumen Tray &bull; <span class="font-mono text-red-700">{{ laporanDetailData.tray ?
                laporanDetailData.tray.tray_code : '' }}</span>
            </h3>
            <p class="text-xs text-slate-500">
              MO: <strong class="font-mono text-slate-800">{{ (laporanDetailData.tray &&
                laporanDetailData.tray.source_document_mo) || '-' }}</strong> |
              PIC Gudang: <strong>{{ getOperatorFullName(laporanDetailData.tray ? laporanDetailData.tray.pic_warehouse :
                '') }}</strong> |
              Verifikasi: <span
                :class="laporanDetailData.tray && laporanDetailData.tray.verified_by_gudang ? 'text-emerald-700 font-bold' : 'text-amber-600 font-bold'">
                {{ laporanDetailData.tray && laporanDetailData.tray.verified_by_gudang ? 'Terverifikasi Aktual (' +
                (laporanDetailData.tray.verified_by_user || 'Gudang') + ')' : 'Menunggu Receipts (WH/GR)' }}
              </span>
            </p>
          </div>
          <button @click="showLaporanDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="overflow-y-auto overflow-x-auto flex-grow rounded-xl border border-slate-200 max-h-[55vh]">
          <table class="w-full text-left text-xs min-w-[700px]">
            <thead class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-200 sticky top-0 z-10">
              <tr>
                <th class="p-2.5 w-12 text-center">Set</th>
                <th class="p-2.5">P9 Part 15</th>
                <th class="p-2.5">P9 Part 1</th>
                <th class="p-2.5">P9 Part 12</th>
                <th class="p-2.5 text-center">Status QC</th>
                <th class="p-2.5 text-center">Status Receipts</th>
                <th class="p-2.5">Catatan / Alasan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-800 font-mono text-[11px]">
              <tr v-for="item in (laporanDetailData.assemblies || [])" :key="item.set_number" class="hover:bg-slate-50">
                <td class="p-2.5 text-center font-bold font-sans text-slate-400">#{{ item.set_number }}</td>
                <td class="p-2.5 font-black text-slate-900">
                  {{ item.part15_sn || '-' }}
                </td>
                <td class="p-2.5 text-slate-800 font-semibold">
                  {{ item.part1_sn || '-' }}
                </td>
                <td class="p-2.5 text-slate-800 font-semibold">
                  {{ item.part12_sn || '-' }}
                </td>
                <td class="p-2.5 text-center font-sans">
                  <span :class="getStatusBadgeClass(item.status || 'Good')"
                    class="px-2 py-0.5 rounded-lg font-black text-[10px] uppercase border">
                    {{ item.status || 'Good' }}
                  </span>
                </td>
                <td class="p-2.5 text-center font-sans">
                  <span v-if="item.is_declined || item.verified_status === 'DECLINED'"
                    class="bg-amber-100 text-amber-900 border border-amber-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase">
                    DECLINED (SIAP SIAGA)
                  </span>
                  <span v-else-if="item.verified_status === 'ACC'"
                    class="bg-emerald-50 text-emerald-700 border border-emerald-300 px-2 py-0.5 rounded-lg font-bold text-[10px] uppercase">
                    ACC
                  </span>
                  <span v-else class="text-slate-400 font-sans text-[11px]">-</span>
                </td>
                <td class="p-2.5 font-sans text-slate-700 text-xs">
                  <span v-if="item.declined_reason || item.reason"
                    class="text-amber-900 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded font-medium">{{
                    item.declined_reason || item.reason }}</span>
                  <span v-else class="text-slate-300">-</span>
                </td>
              </tr>
              <tr v-if="!laporanDetailData.assemblies || laporanDetailData.assemblies.length === 0">
                <td colspan="7" class="p-8 text-center text-slate-400 font-sans">Tidak ada data rakitan.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pt-2 flex justify-end border-t border-slate-100 flex-shrink-0">
          <button @click="showLaporanDetailModal = false"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-2 rounded-xl text-xs transition active:scale-95">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL DAFTAR ULANG PART MASTER DB (HOLD, REWORK, SCRAP) ================= -->
    <div v-if="showReregisterModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base font-bold shadow-sm">
              <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Daftar Ulang Part</h3>
              <p class="text-[11px] text-slate-500 font-mono">P9 Part {{ reregisterForm.part_type }} &bull; {{
                reregisterForm.sn }}</p>
            </div>
          </div>
          <button @click="showReregisterModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3">
          <!-- Info Part -->
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-xs font-mono">
            <div class="flex justify-between">
              <span class="text-slate-500 font-sans">Komponen:</span>
              <strong class="text-red-700 font-sans font-bold">P9 Part {{ reregisterForm.part_type }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 font-sans">Serial Number (SN):</span>
              <strong class="text-slate-900 font-black text-sm">{{ reregisterForm.sn }}</strong>
            </div>
            <div class="flex justify-between items-center pt-1 border-t border-slate-200 font-sans">
              <span class="text-slate-500 text-xs">Status Saat Ini:</span>
              <span :class="getStatusBadgeClass(reregisterForm.current_status)"
                class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase border">
                {{ reregisterForm.current_status }}
              </span>
            </div>
            <div v-if="reregisterForm.original_reason" class="pt-2 border-t border-slate-200 font-sans space-y-1">
              <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">Catatan / Alasan Dilepas
                dari Perakitan:</span>
              <div
                class="text-amber-900 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-lg text-xs font-semibold leading-relaxed">
                <i class="fa-solid fa-circle-exclamation text-amber-600 mr-1.5"></i>{{ reregisterForm.original_reason }}
              </div>
            </div>
          </div>

          <!-- Connected Assembly Set Info (Jika Part ini Terhubung dalam Satu Set Rakitan) -->
          <div v-if="reregisterForm.connected_set"
            class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl space-y-2 text-xs text-indigo-950">
            <div class="font-bold flex items-center justify-between text-indigo-900">
              <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-link text-indigo-600"></i>
                <span>Terhubung dalam 1 Set Rakitan:</span>
              </span>
              <span
                class="font-mono text-[11px] bg-white border border-indigo-200 px-1.5 py-0.5 rounded text-indigo-700">
                {{ reregisterForm.connected_set.tray_code }} &bull; Set #{{ reregisterForm.connected_set.set_number }}
              </span>
            </div>
            <div class="grid grid-cols-3 gap-1.5 text-[11px] font-mono">
              <div class="bg-white p-1.5 rounded-lg border border-indigo-200">
                <span class="block text-[9px] font-sans text-slate-500 font-bold uppercase">P9 Part 15:</span>
                <strong class="text-indigo-950 block truncate">{{ reregisterForm.connected_set.part15_sn || '-'
                  }}</strong>
              </div>
              <div class="bg-white p-1.5 rounded-lg border border-indigo-200">
                <span class="block text-[9px] font-sans text-slate-500 font-bold uppercase">P9 Part 1:</span>
                <strong class="text-slate-800 block truncate">{{ reregisterForm.connected_set.part1_sn || '-'
                  }}</strong>
              </div>
              <div class="bg-white p-1.5 rounded-lg border border-indigo-200">
                <span class="block text-[9px] font-sans text-slate-500 font-bold uppercase">P9 Part 12:</span>
                <strong class="text-slate-800 block truncate">{{ reregisterForm.connected_set.part12_sn || '-'
                  }}</strong>
              </div>
            </div>
            <p class="text-[10.5px] text-indigo-800 leading-snug">
              <i class="fa-solid fa-circle-info mr-1 text-indigo-600"></i>
              Seluruh <strong>3 part terhubung dalam set ini</strong> akan otomatis diajukan verifikasi bersama dan
              berstatus <strong>Menunggu Verifikasi Admin</strong>.
            </p>
          </div>

          <!-- Alert Verifikasi Admin -->
          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-amber-800">
              <i class="fa-solid fa-shield-halved text-amber-600"></i>
              <span>Memerlukan Verifikasi Admin</span>
            </div>
            <p class="text-[11px] leading-relaxed">
              Permohonan pendaftaran ulang part ini akan diajukan ke <strong>Administrator</strong> untuk diverifikasi
              sebelum status dipulihkan.
            </p>
          </div>

          <!-- Target Status Selection -->
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700 mb-2">
              Pilih Target Status Pemulihan <span class="text-red-600">*</span>
            </label>
            <div class="grid grid-cols-2 gap-2">
              <!-- Opsi Tersedia: Target utama pemulihan part (dari Hold atau Scrap) -->
              <button type="button" @click="reregisterForm.target_status = 'Tersedia'"
                :class="reregisterForm.target_status === 'Tersedia' ? 'bg-emerald-600 text-white font-bold ring-2 ring-emerald-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-boxes-stacked"></i> Tersedia
              </button>
              <!-- Opsi Hold: Sembunyikan jika status saat ini Hold (hanya muncul jika saat ini Scrap) -->
              <button v-if="!(reregisterForm.current_status || '').toUpperCase().includes('HOLD')"
                type="button" @click="reregisterForm.target_status = 'Hold'"
                :class="reregisterForm.target_status === 'Hold' ? 'bg-amber-600 text-white font-bold ring-2 ring-amber-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-hand"></i> Hold
              </button>
              <!-- Opsi Scrap: Sembunyikan jika status saat ini Scrap (hanya muncul jika saat ini Hold) -->
              <button v-if="!(reregisterForm.current_status || '').toUpperCase().includes('SCRAP')"
                type="button" @click="reregisterForm.target_status = 'Scrap'"
                :class="reregisterForm.target_status === 'Scrap' ? 'bg-red-600 text-white font-bold ring-2 ring-red-400 shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200'"
                class="py-2.5 px-2 rounded-xl text-xs transition font-semibold flex items-center justify-center gap-1.5 min-h-[40px] cursor-pointer">
                <i class="fa-solid fa-trash-can"></i> Scrap
              </button>
            </div>
          </div>

          <!-- Alasan / Catatan Daftar Ulang -->
          <div class="space-y-1.5 p-3 rounded-xl border transition-all animate-fadeIn"
            :class="reregisterForm.target_status === 'Tersedia' ? 'bg-emerald-50/80 border-emerald-300' : (reregisterForm.target_status === 'Hold' ? 'bg-amber-50/80 border-amber-300' : 'bg-red-50/80 border-red-300')">
            <label class="block text-xs font-bold uppercase tracking-wider flex items-center justify-between"
              :class="reregisterForm.target_status === 'Tersedia' ? 'text-emerald-900' : (reregisterForm.target_status === 'Hold' ? 'text-amber-900' : 'text-red-900')">
              <span>
                <i class="fa-solid fa-pen-to-square mr-1"></i>
                Alasan / Catatan Pemulihan: <span class="text-red-600">*</span>
              </span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/90 border border-slate-200 text-slate-700">Wajib
                Diisi</span>
            </label>
            <textarea v-model="reregisterForm.reason" rows="2"
              placeholder="Tuliskan alasan kondisi part siap dipulihkan..."
              class="w-full bg-white border rounded-xl p-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 transition shadow-inner font-sans font-medium"
              :class="reregisterForm.target_status === 'Tersedia' ? 'border-emerald-300 focus:ring-emerald-500' : (reregisterForm.target_status === 'Hold' ? 'border-amber-300 focus:ring-amber-500' : 'border-red-300 focus:ring-red-500')"></textarea>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button @click="showReregisterModal = false"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[40px]">
            Batal
          </button>
          <button @click="submitReregisterPart"
            class="bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md shadow-red-600/25 transition flex items-center gap-1.5 active:scale-95 min-h-[40px]">
            <i class="fa-solid fa-paper-plane"></i>
            <span>Kirim Permohonan ke Admin</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL VALIDASI KHUSUS DAFTAR ULANG 1 SET UTUH ================= -->
    <div v-if="showReregisterSetModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">

        <!-- Header / Icon -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-lg font-bold">
              <i class="fa-solid fa-rotate-left"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Validasi Daftar Ulang</h3>
              <p class="text-[11px] text-slate-500 font-medium">Pengajuan Verifikasi Admin 1 Set Rakitan</p>
            </div>
          </div>
          <button @click="showReregisterSetModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition" title="Batal">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Pop-up Question Prompt (Exact user requirement) -->
        <div class="text-center py-2 px-1">
          <p class="text-base sm:text-lg font-black text-slate-900 leading-snug">
            Apakah Anda ingin mendaftarkan kembali barang ini?
          </p>
          <p class="text-xs text-slate-500 font-medium mt-1">
            Pengajuan ini akan dikirimkan ke antrean Administrator untuk verifikasi.
          </p>
        </div>

        <!-- Ringkasan Unit Set yang Didaftarkan Ulang -->
        <div v-if="targetReregisterSet" class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="text-slate-500 font-sans font-bold">Unit Rakitan:</span>
            <div class="flex items-center gap-1.5 font-mono">
              <span class="bg-slate-900 text-white text-[10.5px] font-black px-2 py-0.5 rounded-md">
                {{ targetReregisterSet.tray_code }}
              </span>
              <span class="bg-slate-200 text-slate-800 text-[10.5px] font-bold px-2 py-0.5 rounded-md">
                Set #{{ targetReregisterSet.set_number }}
              </span>
            </div>
          </div>

          <div class="flex items-center justify-between font-mono pt-1 border-t border-slate-200">
            <span class="text-slate-500 font-sans">SN P9 Part 15:</span>
            <strong class="text-slate-900 font-black">{{ targetReregisterSet.part15_sn || '-' }}</strong>
          </div>

          <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
            <div class="bg-white p-1.5 rounded-lg border border-slate-200">
              <span class="text-slate-400 text-[9px] block">Part 12:</span>
              <strong class="text-slate-800">{{ targetReregisterSet.part12_sn || '-' }}</strong>
            </div>
            <div class="bg-white p-1.5 rounded-lg border border-slate-200">
              <span class="text-slate-400 text-[9px] block">Part 1:</span>
              <strong class="text-slate-800">{{ targetReregisterSet.part1_sn || '-' }}</strong>
            </div>
          </div>

          <!-- Status Proses Berdampingan (Sesuai Permintaan User: Tampilin Status di Samping Test Assembly & QC) -->
          <div class="pt-2 border-t border-slate-200 space-y-1.5 font-sans">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600 font-bold">Test Assembly:</span>
              <span
                v-if="targetReregisterSet.hold_source !== 'QC' && (targetReregisterSet.assy_status === 'Hold' || targetReregisterSet.status === 'Hold' || targetReregisterSet.verified_status === 'HOLD_GUDANG' || targetReregisterSet.hold_source === 'TESTER_ASSEMBLY')"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                <i class="fa-solid fa-hand text-[9px]"></i> HOLD
              </span>
              <span
                v-else-if="targetReregisterSet.assy_test_status === 'Good' || targetReregisterSet.assy_doc_number || targetReregisterSet.stage === 'QC'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1">
                <i class="fa-solid fa-check text-[9px]"></i> GOOD
              </span>
              <span v-else
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-slate-100 text-slate-700 border border-slate-300 flex items-center gap-1">
                <i class="fa-solid fa-clock text-[9px]"></i> BELUM DIUJI
              </span>
            </div>

            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600 font-bold">QC:</span>
              <span
                v-if="targetReregisterSet.qc_status === 'HOLD_QC' || targetReregisterSet.qc_status === 'HOLD' || targetReregisterSet.hold_source === 'QC'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-rose-100 text-rose-900 border border-rose-300 flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> HOLD
              </span>
              <span v-else-if="targetReregisterSet.qc_status === 'GOOD' || targetReregisterSet.qc_status === 'GOOD_QC'"
                class="px-2 py-0.5 rounded-md font-black text-[10px] uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 flex items-center gap-1">
                <i class="fa-solid fa-check text-[9px]"></i> GOOD
              </span>
              <span v-else
                class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase bg-slate-100 text-slate-700 border border-slate-300">
                PENDING
              </span>
            </div>
          </div>

        </div>

        <!-- Tampilan Alasan Sebelumnya untuk status HOLD -->
        <div v-if="targetReregisterSet"
          class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-3 text-left space-y-1">
          <span class="text-[10px] font-bold text-amber-900 block">Alasan kendala sebelumnya:</span>
          <p class="text-xs text-amber-950 font-medium">
            {{ targetReregisterSet.qc_failure_reasons || targetReregisterSet.assy_failure_reasons ||
            targetReregisterSet.assembly_reason || targetReregisterSet.reason || targetReregisterSet.display_reason ||
            'Tertahan hold' }}
          </p>
        </div>

        <!-- Input Alasan: Opsional -->
        <div class="space-y-1.5 text-left">
          <label class="block text-xs font-bold text-slate-700 flex items-center justify-between">
            <span>Catatan / alasan tambahan (opsional):</span>
            <span class="text-[10px] text-slate-500 font-semibold bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">Opsional</span>
          </label>
          <textarea v-model="reregisterNewReworkReason" rows="2"
            placeholder="Tuliskan catatan tambahan jika ada (opsional, default gunakan alasan sebelumnya)..."
            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-xs text-slate-900 focus:outline-none focus:border-amber-600 font-medium"></textarea>
        </div>

        <!-- Action Buttons: "Minta" dan "Batal" (Sesuai Permintaan User) -->
        <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
          <button type="button" @click="showReregisterSetModal = false"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[40px] cursor-pointer">
            Batal
          </button>
          <button type="button" @click="confirmReregisterSet" :disabled="isSubmittingReregisterSet"
            class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-md shadow-amber-600/25 transition flex items-center gap-2 active:scale-95 min-h-[40px] cursor-pointer disabled:opacity-50">
            <i :class="isSubmittingReregisterSet ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'"></i>
            <span>Minta</span>
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL VERIFIKASI AKTUAL FISIK GUDANG PASCA-QC (ACC / DECLINE) ================= -->
    <div v-if="showQCWarehouseActualVerifyModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto animate-fadeIn">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-lg font-bold">
              <i class="fa-solid fa-stamp"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">Verifikasi Fisik Gudang Pasca-QC</h3>
              <p class="text-[11px] text-slate-500 font-medium">Penerimaan Tray {{ qcVerifyTargetTray ?
                qcVerifyTargetTray.tray_code : '' }}</p>
            </div>
          </div>
          <button @click="showQCWarehouseActualVerifyModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <p class="text-slate-700 leading-relaxed font-medium">
            Konfirmasikan kondisi fisik penerimaan barang per-set yang telah lolos Quality Control (QC) untuk dimasukkan
            ke inventori Gudang.
          </p>

          <!-- List of Items (One by one individual verification) -->
          <div
            class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2.5 max-h-60 overflow-y-auto font-sans">
            <div v-for="it in qcVerifyItemsList" :key="it.set_number || it.id"
              class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80 shadow-xs hover:border-slate-300 transition">
              <div class="flex items-center gap-2.5">
                <div
                  class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center font-bold text-slate-600 text-xs">
                  #{{ it.set_number }}
                </div>
                <div>
                  <div class="font-black text-slate-900 font-mono text-xs">{{ it.part15_sn || '-' }}</div>
                  <div class="text-[10px] text-slate-400 font-sans">PN: {{ it.part1_sn || '-' }} / {{ it.part12_sn ||
                    '-' }}</div>
                </div>
                <span
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                  GOOD QC
                </span>
              </div>

              <!-- Action buttons per set -->
              <div>
                <!-- Already ACC (Locked Permanently) -->
                <div v-if="it.warehouse_receipt_status === 'ACCEPTED' || it.warehouse_receipt_status === 'ACC'"
                  class="flex items-center gap-1.5">
                  <span
                    class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1 shadow-xs select-none"
                    title="Verifikasi Selesai & Terkunci">
                    <i class="fa-solid fa-lock text-[9px] text-emerald-700"></i> ACC
                  </span>
                </div>

                <!-- Already DECLINED (Locked Permanently) -->
                <div v-else-if="it.warehouse_receipt_status === 'DECLINED'" class="flex items-center gap-1.5">
                  <span
                    class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1 shadow-xs select-none"
                    title="Verifikasi Selesai & Terkunci">
                    <i class="fa-solid fa-lock text-[9px] text-rose-700"></i> DECLINED (Terkunci)
                  </span>
                </div>

                <!-- Pending (Verify One-by-One Buttons) -->
                <div v-else class="flex items-center gap-1.5">
                  <button type="button" @click="verifySingleQCItem(it, 'DECLINED')"
                    :disabled="isSubmittingQCItem[it.id]"
                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1.5 rounded-lg text-[10.5px] shadow-sm transition inline-flex items-center gap-1 active:scale-95 cursor-pointer disabled:opacity-50">
                    <i class="fa-solid fa-xmark text-[9px]"></i>
                    <span>Decline</span>
                  </button>
                  <button type="button" @click="verifySingleQCItem(it, 'ACCEPT')" :disabled="isSubmittingQCItem[it.id]"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-[10.5px] shadow-sm transition inline-flex items-center gap-1 active:scale-95 cursor-pointer disabled:opacity-50">
                    <i class="fa-solid fa-check text-[9px]"></i>
                    <span>ACC</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes / Alasan (khusus jika DECLINED / catatan unit) -->
          <div>
            <label class="block text-[10.5px] font-bold uppercase text-slate-600 mb-1">
              Catatan Petugas Gudang (Opsional):
            </label>
            <input type="text" v-model="qcActualVerifyNotes" placeholder="Catatan kondisi fisik unit / tray..."
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-emerald-600">
          </div>
        </div>

        <!-- Footer: Progress & Selesai -->
        <div class="pt-2 flex items-center justify-between gap-2 border-t border-slate-100">
          <div class="text-[11px] font-medium text-slate-500">
            <span>Status Verifikasi: </span>
            <strong class="text-slate-900">{{ qcVerifyItemsList.filter(i => i.warehouse_receipt_status === 'ACCEPTED' ||
              i.warehouse_receipt_status === 'ACC' || i.warehouse_receipt_status === 'DECLINED').length }}</strong> dari
            {{ qcVerifyItemsList.length }} unit terverifikasi
          </div>
          <button type="button" @click="showQCWarehouseActualVerifyModal = false"
            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition min-h-[38px] cursor-pointer shadow-sm active:scale-95">
            Selesai
          </button>
        </div>

      </div>
    </div>

    <!-- ================= MODAL DETAIL PENGEMBALIAN BARANG ================= -->
    <div v-if="showReturnDetailModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto max-h-[92vh] flex flex-col">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-shrink-0">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center text-sm font-black shadow-md shadow-red-600/20">
              <i class="fa-solid fa-boxes-packing"></i>
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">Detail Pengembalian Dokumen Tray
              </h3>
              <p class="text-xs font-mono font-bold text-red-600">{{ activeReturnTray ? activeReturnTray.tray_code : ''
                }}</p>
            </div>
          </div>
          <button type="button" @click="showReturnDetailModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-xl"></i>
          </button>
        </div>

        <!-- Document Meta Cards -->
        <div v-if="activeReturnTray"
          class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-slate-50 border border-slate-200 p-3 rounded-xl text-xs flex-shrink-0">
          <div>
            <span class="text-[10px] font-bold text-slate-500 block">Nomor dokumen:</span>
            <span class="font-mono font-black text-slate-900">{{ activeReturnTray.tray_code }}</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-500 block">Operator asal:</span>
            <span class="font-bold text-slate-900">{{ activeReturnTray.assigned_operator || '-' }}</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-500 block">Tanggal pengembalian:</span>
            <span class="font-semibold text-slate-800">{{ formatDateTime(activeReturnTray.created_at) }}</span>
          </div>
          <div>
            <span class="text-[10px] font-bold text-slate-500 block">Status verifikasi:</span>
            <span v-if="activeReturnTray.return_verified"
              class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
              <i class="fa-solid fa-circle-check text-[10px]"></i> Sudah diverifikasi
            </span>
            <span v-else
              class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
              <i class="fa-solid fa-clock text-[10px]"></i> Menunggu verifikasi
            </span>
          </div>
          <div class="col-span-2 sm:col-span-4 border-t border-slate-200/80 pt-2 mt-1">
            <span class="text-[10px] font-bold text-slate-500 block">Alasan pengembalian:</span>
            <p class="text-xs text-amber-950 font-medium bg-amber-50 p-2 rounded-lg border border-amber-200 mt-0.5">
              {{ activeReturnTray.return_reason || 'Dikembalikan ke gudang oleh operator assembly' }}
            </p>
          </div>
        </div>

        <!-- Layout Table Pop Up Sesuai Permintaan User: PN | P9 Part 15 | P9 Part 1 | P9 Part 12 | Status | Aksi -->
        <div class="flex-grow overflow-y-auto border border-slate-200 rounded-xl">
          <table class="w-full text-left text-xs">
            <thead
              class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200 sticky top-0 z-10 shadow-xs">
              <tr>
                <th class="py-2.5 px-3 text-center w-12 bg-slate-100">Set #</th>
                <th class="py-2.5 px-3 bg-slate-100">PN (Part Name)</th>
                <th class="py-2.5 px-3 bg-slate-100 text-red-700">P9 Part 15 (SN)</th>
                <th class="py-2.5 px-3 bg-slate-100 text-slate-800">P9 Part 1 (SN)</th>
                <th class="py-2.5 px-3 bg-slate-100 text-slate-800">P9 Part 12 (SN)</th>
                <th class="py-2.5 px-3 text-center bg-slate-100">Status Part</th>
                <th class="py-2.5 px-3 text-center bg-slate-100 w-36">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-mono">
              <tr v-for="s in activeReturnSets" :key="s.set_number" class="hover:bg-slate-50/80 transition">
                <td class="py-2.5 px-3 text-center font-bold text-slate-500 bg-slate-50/50">{{ s.set_number }}</td>
                <td class="py-2.5 px-3 font-sans font-bold text-slate-800">{{ s.pn || 'P9 Part 15' }}</td>
                <td class="py-2.5 px-3 font-bold text-red-600 bg-red-50/30">
                  <span>{{ s.p15_sn }}</span>
                  <span v-if="s.p15_status === 'Declined' || (s.is_declined && s.p15_status !== 'Tersedia')"
                    class="ml-1 text-[9px] bg-rose-100 text-rose-800 px-1 py-0.2 rounded font-sans font-bold">Declined</span>
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">
                  <span>{{ s.p1_sn }}</span>
                  <span v-if="s.p1_status === 'Declined' || (s.is_declined && s.p1_status !== 'Tersedia')"
                    class="ml-1 text-[9px] bg-rose-100 text-rose-800 px-1 py-0.2 rounded font-sans font-bold">Declined</span>
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">
                  <span>{{ s.p12_sn }}</span>
                  <span v-if="s.p12_status === 'Declined' || (s.is_declined && s.p12_status !== 'Tersedia')"
                    class="ml-1 text-[9px] bg-rose-100 text-rose-800 px-1 py-0.2 rounded font-sans font-bold">Declined</span>
                </td>

                <!-- Status Part (ACC / Declined / Menunggu Verifikasi) -->
                <td class="py-2.5 px-3 text-center font-sans">
                  <span v-if="s.is_declined || s.status === 'Declined'"
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1"
                    :title="s.declined_reason ? 'Alasan: ' + s.declined_reason : 'Menunggu Verifikasi Admin'">
                    <i class="fa-solid fa-xmark text-rose-600"></i> Declined
                  </span>
                  <span v-else-if="s.is_verified || (activeReturnTray && activeReturnTray.return_verified) || s.status === 'ACC' || s.status === 'Tersedia'"
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center justify-center">
                    ACC
                  </span>
                  <span v-else
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Menunggu Verifikasi
                  </span>
                </td>

                <!-- Aksi Verifikasi per Set -->
                <td class="py-2.5 px-3 text-center font-sans">
                  <span v-if="s.is_declined || s.status === 'Declined'"
                    class="inline-flex items-center gap-1 text-rose-700 bg-rose-50 border border-rose-200 font-bold px-2.5 py-1 rounded-lg text-[11px] select-none mx-auto"
                    :title="s.declined_reason ? 'Alasan: ' + s.declined_reason : 'Menunggu Verifikasi Admin'">
                    <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                    <span>Declined</span>
                  </span>
                  <span v-else-if="s.is_verified || (activeReturnTray && activeReturnTray.return_verified) || s.status === 'ACC' || s.status === 'Tersedia'"
                    class="inline-flex items-center text-emerald-700 bg-emerald-50 border border-emerald-200 font-bold px-2.5 py-1 rounded-lg text-[11px] select-none mx-auto">
                    <span>ACC</span>
                  </span>
                  <div v-else class="flex items-center justify-center gap-1.5">
                    <button type="button" @click="verifyReturnSet(s)"
                      :disabled="verifyingSetIndex === s.set_number || isVerifyingReturn"
                      class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg text-[11px] transition active:scale-95 shadow-xs flex items-center justify-center cursor-pointer min-h-[28px]"
                      title="Verifikasi ACC set ini">
                      <i v-if="verifyingSetIndex === s.set_number" class="fa-solid fa-spinner fa-spin text-[10px] mr-1"></i>
                      <span>ACC</span>
                    </button>
                    <button type="button" @click="openDeclineReturnModal(s)"
                      :disabled="verifyingSetIndex === s.set_number || isVerifyingReturn"
                      class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2 py-1 rounded-lg text-[11px] transition active:scale-95 shadow-xs flex items-center gap-1 cursor-pointer min-h-[28px]"
                      title="Tolak / Decline part yang tidak ditemukan">
                      <i class="fa-solid fa-xmark text-[10px]"></i>
                      <span>Decline</span>
                    </button>
                  </div>
                </td>
                      <i class="fa-solid fa-xmark text-[10px]"></i>
                      <span>Decline</span>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Loading State -->
              <tr v-if="isLoadingReturnDetail">
                <td colspan="7" class="py-10 text-center text-slate-500 font-sans text-xs">
                  <div class="flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-notch fa-spin text-red-600 text-sm"></i>
                    <span class="font-bold">Memuat daftar part pengembalian...</span>
                  </div>
                </td>
              </tr>

              <!-- Empty State: Ketika sudah selesai loading dan tidak ada sisa part (seluruh set sudah selesai dirakit) -->
              <tr v-else-if="!activeReturnSets || activeReturnSets.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-500 font-sans text-xs">
                  <div class="flex flex-col items-center justify-center space-y-1.5 py-1">
                    <div class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm border border-emerald-200 shadow-xs">
                      <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="font-bold text-slate-800 text-xs">Tidak ada part yang perlu dikembalikan</p>
                    <p class="text-[11px] text-slate-400">Seluruh set telah selesai dirakit dan tersimpan aman di riwayat perakitan &amp; QC.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Modal Footer Actions -->
        <div
          class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
          <div class="text-[11px] text-slate-500 font-medium">
            <span v-if="(activeReturnTray && activeReturnTray.return_verified) || isAllActiveReturnSetsVerified"
              class="text-emerald-700 font-bold flex items-center gap-1">
              <i class="fa-solid fa-circle-check"></i>
              Diverifikasi oleh {{ activeReturnTray.return_verified_by || 'Gudang' }} pada {{
              formatDateTime(activeReturnTray.return_verified_at) }}
            </span>
            <span v-else class="text-amber-800 font-medium">
              * Verifikasi dapat dilakukan <strong>Per Set</strong> (Verif / Decline) atau langsung seluruhnya dengan
              <strong>Verifikasi Semua</strong>.
            </span>
          </div>

          <div class="flex items-center gap-2 self-end sm:self-auto">
            <button type="button" @click="showReturnDetailModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer min-h-[38px]">
              Tutup
            </button>

            <!-- Button Verifikasi Semua (Jika belum semua selesai) -->
            <button v-if="!(activeReturnTray && activeReturnTray.return_verified) && !isAllActiveReturnSetsVerified"
              type="button" @click="verifyReturnTray(activeReturnTray)" :disabled="isVerifyingReturn"
              class="bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md shadow-emerald-600/25 transition active:scale-95 flex items-center gap-2 cursor-pointer min-h-[38px]">
              <i v-if="isVerifyingReturn" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-check-double"></i>
              <span>Verifikasi Semua</span>
            </button>

            <!-- Button Sudah Diverifikasi / Selesai (Disabled jika semua set sudah diverifikasi) -->
            <button v-else type="button" disabled
              class="bg-slate-100 border border-emerald-300 text-emerald-800 font-bold text-xs px-5 py-2.5 rounded-xl cursor-not-allowed flex items-center gap-2 select-none min-h-[38px]">
              <i class="fa-solid fa-circle-check text-emerald-600"></i>
              <span>Sudah Diverifikasi</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ================= MODAL KONFIRMASI DECLINE PENGEMBALIAN BARANG ================= -->
    <div v-if="showDeclineReturnModal"
      class="modal-backdrop-full modal-backdrop-nested flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-2.5">
            <div
              class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xs font-black shadow-md shadow-rose-600/20">
              <i class="fa-solid fa-ban"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Decline Pengembalian Part</h3>
              <p class="text-[11px] font-mono font-bold text-rose-600">Set #{{ declineReturnForm.set_number }} - {{
                declineReturnForm.tray_code }}</p>
            </div>
          </div>
          <button type="button" @click="showDeclineReturnModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3.5 text-xs">
          <!-- Pilihan Part yang ingin di-decline -->
          <div class="space-y-2 p-3 bg-slate-50 border border-slate-300 rounded-xl">
            <label
              class="block text-xs font-bold uppercase text-slate-800 tracking-wider flex items-center justify-between">
              <span><i class="fa-solid fa-list-check text-rose-600 mr-1.5"></i>Pilih Part yang Ditolak / Tidak Ditemukan
                <span class="text-rose-600">*</span></span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">Wajib
                Minimal 1</span>
            </label>

            <div class="space-y-1.5 font-mono">
              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer select-none text-xs transition"
                :class="declineReturnForm.selectedParts.includes('15') ? 'bg-rose-50 border-rose-400 text-rose-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                <input type="checkbox" value="15" v-model="declineReturnForm.selectedParts"
                  class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                <span>P9 Part 15 (SN: {{ declineReturnForm.p15_sn || '-' }})</span>
              </label>

              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer select-none text-xs transition"
                :class="declineReturnForm.selectedParts.includes('1') ? 'bg-rose-50 border-rose-400 text-rose-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                <input type="checkbox" value="1" v-model="declineReturnForm.selectedParts"
                  class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                <span>P9 Part 1 (SN: {{ declineReturnForm.p1_sn || '-' }})</span>
              </label>

              <label
                class="flex items-center space-x-2 p-2 rounded-lg border cursor-pointer select-none text-xs transition"
                :class="declineReturnForm.selectedParts.includes('12') ? 'bg-rose-50 border-rose-400 text-rose-900 font-bold' : 'bg-white border-slate-200 text-slate-700'">
                <input type="checkbox" value="12" v-model="declineReturnForm.selectedParts"
                  class="rounded text-rose-600 focus:ring-rose-500 w-4 h-4">
                <span>P9 Part 12 (SN: {{ declineReturnForm.p12_sn || '-' }})</span>
              </label>
            </div>
            <p class="text-[10px] text-slate-500 font-sans italic">
              * Part yang tidak dicentang otomatis diverifikasi sebagai Tersedia. Part yang dicentang masuk status
              Declined dan dikirim ke Verifikasi Admin.
            </p>
          </div>

          <!-- Textbox Alasan Penolakan (Reason) -->
          <div class="space-y-1.5 p-3 rounded-xl border bg-rose-50/70 border-rose-300">
            <label
              class="block text-xs font-bold uppercase tracking-wider text-rose-900 flex items-center justify-between">
              <span class="flex items-center gap-1.5">
                <i class="fa-solid fa-comment-dots text-rose-600"></i>
                <span>Alasan Penolakan / Decline <span class="text-rose-600">*</span></span>
              </span>
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700">Wajib
                Diisi</span>
            </label>
            <textarea v-model="declineReturnForm.reason" rows="2"
              placeholder="Tuliskan alasan penolakan (misal: Part tidak ditemukan saat verifikasi fisik gudang)..."
              class="w-full bg-white border border-slate-300 focus:border-rose-600 focus:ring-2 focus:ring-rose-100 rounded-lg p-2 text-xs text-slate-900 font-medium focus:outline-none shadow-sm"></textarea>
          </div>
        </div>

        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
          <button type="button" @click="showDeclineReturnModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px] cursor-pointer">
            Batal
          </button>
          <button type="button" @click="submitDeclineReturnSet" :disabled="isSubmittingDeclineReturn"
            class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md shadow-rose-600/25 transition flex items-center gap-1.5 active:scale-95 min-h-[38px] cursor-pointer">
            <i v-if="isSubmittingDeclineReturn" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-paper-plane"></i>
            <span>Kirim Decline ke Admin</span>
          </button>
        </div>
      </div>
    </div>

    <!-- CATATAN: Fitur Uji Fungsional Operator Assembly & QC kini berupa Halaman Penuh In-Tab (Bukan Pop-Up Floating Modal) -->


    <!-- ================= MODAL POP-UP ALASAN WAJIB HOLD QC ================= -->
    <div v-if="showQCHoldReasonModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-3">
            <div
              class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg font-black shadow-md shadow-rose-600/25">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
              <h3 class="font-black text-base sm:text-lg text-slate-900 leading-tight">Alasan Penahanan Mutu QC</h3>
              <p class="text-[11px] text-slate-500 font-medium">
                Wajib isi alasan penahanan mutu sebelum status Hold disimpan.
              </p>
            </div>
          </div>
          <button type="button" @click="showQCHoldReasonModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Detail Unit Info -->
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs space-y-1.5 font-sans">
          <div class="flex items-center justify-between">
            <span class="text-rose-700 font-bold uppercase text-[10px]">SN P9 Part 15:</span>
            <strong class="font-mono text-slate-900 text-sm font-black">{{ qcTestForm.part15_sn || '-' }}</strong>
          </div>
          <div class="flex items-center justify-between text-[11px] text-slate-600">
            <span>Tray &amp; Set:</span>
            <strong class="font-mono text-slate-800">{{ qcTestForm.tray_code }} &bull; Set #{{ qcTestForm.set_number
              }}</strong>
          </div>
          <div class="flex items-center justify-between text-[11px] text-slate-600">
            <span>Komponen:</span>
            <span class="font-mono text-slate-700 font-bold">P12: {{ qcTestForm.part12_sn || '-' }} | P1: {{
              qcTestForm.part1_sn || '-' }}</span>
          </div>
        </div>

        <!-- Alasan Input Wajib -->
        <div>
          <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1">
            Alasan Penahanan Mutu / Masalah Teridentifikasi <span class="text-rose-600 font-black">*</span>
          </label>
          <textarea v-model="qcHoldReasonInput" rows="3"
            placeholder="Jelaskan secara spesifik ketidaksesuaian dimensi, fisik, atau fungsional yang ditemukan..."
            class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs text-slate-900 focus:outline-none focus:border-rose-600 focus:bg-white transition resize-none"></textarea>
          <span class="text-[10px] text-slate-400 mt-1 block">Unit yang di-Hold akan masuk ke menu <strong>Barang
              Produksi</strong> untuk isolasi dan perbaikan.</span>
        </div>

        <!-- Action Buttons -->
        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button type="button" @click="showQCHoldReasonModal = false"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px] cursor-pointer">
            Batal
          </button>
          <button type="button" @click="confirmQCHold" :disabled="isSubmittingQCTest"
            class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs shadow-md shadow-rose-600/25 transition flex items-center gap-1.5 active:scale-95 min-h-[38px] cursor-pointer">
            <i v-if="isSubmittingQCTest" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-triangle-exclamation"></i>
            <span>Simpan Status Hold</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL VERIFIKASI PENERIMAAN FISIK GUDANG DARI QC ================= -->
    <div v-if="showWarehouseQCModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center space-x-3">
            <div
              :class="warehouseQCForm.action === 'ACCEPT' ? 'bg-emerald-600 shadow-emerald-600/25' : 'bg-red-600 shadow-red-600/25'"
              class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-lg font-black shadow-md">
              <i :class="warehouseQCForm.action === 'ACCEPT' ? 'fa-solid fa-warehouse' : 'fa-solid fa-ban'"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">
                {{ warehouseQCForm.action === 'ACCEPT' ? 'Terima Fisik Barang (Gudang)' : 'Tolak Fisik Barang (Decline)'
                }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">Verifikasi fisik barang berdasarkan laporan hasil QC</p>
            </div>
          </div>
          <button type="button" @click="showWarehouseQCModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer" title="Tutup">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Detail Barang Lolos QC -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2 text-xs font-mono">
          <div class="flex justify-between items-center pb-1.5 border-b border-slate-200">
            <span class="text-slate-500 font-sans font-bold text-[10px]">No. Dokumen QC:</span>
            <span class="text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded font-bold">{{
              warehouseQCForm.qc_doc_number || '-' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">SN P9 Part 15:</span>
            <strong class="text-slate-900 font-black text-sm">{{ warehouseQCForm.part15_sn || '-' }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">PN P9 Part 1:</span>
            <strong class="text-slate-800 font-bold">{{ warehouseQCForm.part1_sn || '-' }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-sans">PN P9 Part 12:</span>
            <strong class="text-slate-800 font-bold">{{ warehouseQCForm.part12_sn || '-' }}</strong>
          </div>
        </div>

        <!-- Catatan / Alasan -->
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
            <span>{{ warehouseQCForm.action === 'ACCEPT' ? 'Catatan Penerimaan (Opsional):' : 'Alasan Penolakan / Decline Fisik:' }}</span>
            <span v-if="warehouseQCForm.action === 'DECLINE'" class="text-red-600">*</span>
          </label>
          <textarea v-model="warehouseQCForm.notes" rows="2"
            :placeholder="warehouseQCForm.action === 'ACCEPT' ? 'Tuliskan catatan verifikasi fisik...' : 'Tuliskan alasan penolakan fisik (misal: barang fisik tidak ditemukan/hilang)...'"
            class="w-full bg-white border border-slate-300 rounded-xl p-2.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-red-600 shadow-sm"></textarea>
        </div>

        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
          <button type="button" @click="showWarehouseQCModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition min-h-[38px] cursor-pointer">
            Batal
          </button>
          <button type="button" @click="submitWarehouseQCVerify" :disabled="isSubmittingWarehouseQC"
            :class="warehouseQCForm.action === 'ACCEPT' ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25' : 'bg-red-600 hover:bg-red-700 shadow-red-600/25'"
            class="text-white font-bold px-5 py-2 rounded-xl text-xs shadow-md transition flex items-center gap-1.5 active:scale-95 min-h-[38px] cursor-pointer">
            <i v-if="isSubmittingWarehouseQC" class="fa-solid fa-spinner fa-spin"></i>
            <i v-else class="fa-solid fa-check"></i>
            <span>{{ warehouseQCForm.action === 'ACCEPT' ? 'Konfirmasi Accept Fisik' : 'Konfirmasi Decline Fisik'
              }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- TOAST NOTIFICATION (NO-PRINT: Always on top of all modals) -->
    <div v-if="toast.show" style="display: none;" :style="{ display: toast.show ? 'block' : 'none' }"
      class="no-print fixed bottom-5 right-5 z-[999999] max-w-sm w-full transition-all pointer-events-auto">
      <div
        :class="toast.type === 'error' ? 'bg-red-600 text-white shadow-2xl shadow-red-600/50' : (toast.type === 'warn' ? 'bg-amber-600 text-white shadow-2xl shadow-amber-600/50' : 'bg-slate-900 text-white shadow-2xl shadow-black/50')"
        class="p-4 rounded-2xl flex items-center space-x-3 border border-white/20 ring-2 ring-black/10 animate-bounce-short">
        <i :class="toast.type === 'error' ? 'fa-solid fa-circle-exclamation' : (toast.type === 'warn' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-check')"
          class="text-2xl flex-shrink-0"></i>
        <div class="flex-grow">
          <div class="font-black text-xs tracking-wide">{{ toast.title }}</div>
          <div class="text-[11px] opacity-95 leading-snug mt-0.5">{{ toast.message }}</div>
        </div>
        <button @click="toast.show = false"
          class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition" title="Tutup Notifikasi">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- GLOBAL IN-APP CONFIRMATION & PROMPT CARD MODAL                            -->
    <!-- ========================================================================= -->
    <div v-if="confirmModalState.isOpen" style="display: none;" :style="{ display: confirmModalState.isOpen ? 'flex' : 'none' }"
      class="no-print fixed inset-0 z-[999998] items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity animate-fadeIn">
      <div
        class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-5 sm:p-6 space-y-4 animate-scale-up">
        <div class="flex items-start gap-3.5">
          <div
            :class="['w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs', confirmModalState.iconBg]">
            <i :class="[confirmModalState.icon, 'text-lg']"></i>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-base font-black text-slate-900 leading-snug">{{ confirmModalState.title }}</h3>
            <p class="text-xs text-slate-600 mt-1 leading-relaxed whitespace-pre-line font-medium">{{
              confirmModalState.message }}</p>
          </div>
        </div>

        <!-- Optional Input Field (Prompt mode) -->
        <div v-if="confirmModalState.hasInput" class="space-y-1.5 pt-1">
          <label v-if="confirmModalState.inputLabel"
            class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
            {{ confirmModalState.inputLabel }}
          </label>
          <input type="text" v-model="confirmModalState.inputValue" :placeholder="confirmModalState.inputPlaceholder"
            @keyup.enter="handleConfirmCard(true)" autofocus
            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 font-semibold focus:outline-none focus:border-red-600 focus:bg-white transition shadow-inner">
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
          <button type="button" @click="handleConfirmCard(false)"
            class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition active:scale-95 cursor-pointer min-h-[40px]">
            {{ confirmModalState.cancelText }}
          </button>
          <button type="button" @click="handleConfirmCard(true)"
            :class="['px-5 py-2.5 rounded-xl text-xs font-bold shadow-md transition active:scale-95 cursor-pointer flex items-center gap-1.5 min-h-[40px]', confirmModalState.confirmBtnClass]">
            <span>{{ confirmModalState.confirmText }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL TAMBAH CUSTOMER BARU (INLINE DARI FORM DO) ================= -->
    <div v-if="showNewCustomerModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center font-black text-sm">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Tambah Customer Baru</h3>
              <p class="text-[11px] text-slate-500">Tersimpan ke database dan otomatis terisi di form Surat Jalan</p>
            </div>
          </div>
          <button type="button" @click="showNewCustomerModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700">Nama Customer / Instansi <span
                class="text-red-500">*</span></label>
            <input type="text" v-model="newCustomerForm.name" placeholder="Contoh: KEMHAN RI / PT ABC..."
              class="w-full bg-slate-50 border border-slate-300 focus:border-red-600 focus:bg-white rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 shadow-2xs transition min-h-[44px]">
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700">Alamat Lengkap <span class="text-red-500">*</span></label>
            <textarea v-model="newCustomerForm.address" rows="3"
              placeholder="Alamat lengkap, Gedung, Jalan, Kota, Kode Pos..."
              class="w-full bg-slate-50 border border-slate-300 focus:border-red-600 focus:bg-white rounded-xl px-4 py-3 text-xs font-medium text-slate-800 shadow-2xs transition leading-relaxed min-h-[95px] resize-y"></textarea>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-700">No. Telepon</label>
              <input type="text" v-model="newCustomerForm.phone"
                @input="onPhoneFaxInput(newCustomerForm, 'phone', $event)"
                @keypress="blockLetterKeys($event)"
                placeholder="Contoh: (021) 3840889"
                class="w-full bg-slate-50 border border-slate-300 focus:border-red-600 focus:bg-white rounded-xl px-3.5 py-2 text-xs font-medium text-slate-800 shadow-2xs transition min-h-[42px]">
            </div>
            <div class="space-y-1">
              <label class="text-xs font-bold text-slate-700">No. Fax</label>
              <input type="text" v-model="newCustomerForm.fax"
                @input="onPhoneFaxInput(newCustomerForm, 'fax', $event)"
                @keypress="blockLetterKeys($event)"
                placeholder="Contoh: (021) 3840889"
                class="w-full bg-slate-50 border border-slate-300 focus:border-red-600 focus:bg-white rounded-xl px-3.5 py-2 text-xs font-medium text-slate-800 shadow-2xs transition min-h-[42px]">
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
          <button type="button" @click="showNewCustomerModal = false"
            class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold transition cursor-pointer min-h-[42px]">
            Batal
          </button>
          <button type="button" @click="saveNewCustomer" :disabled="isSavingCustomer"
            class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black shadow-md shadow-red-600/25 flex items-center gap-1.5 transition cursor-pointer min-h-[42px]">
            <i v-if="!isSavingCustomer" class="fa-solid fa-check"></i>
            <i v-else class="fa-solid fa-spinner fa-spin"></i>
            <span>{{ isSavingCustomer ? 'Menyimpan...' : 'Simpan Customer' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL TEMPEL / PASTE MASSAL SERIAL NUMBER ================= -->
    <div v-if="showBatchPasteModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm">
              <i class="fa-solid fa-paste"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Tempel Massal Serial Number</h3>
              <p class="text-[11px] text-slate-500">
                <span v-if="batchPasteTarget.isUnit">Alokasi massal unit satuan</span>
                <span v-else-if="batchPasteTarget.mode === 'crate'">Peti #{{ batchPasteTarget.crateIdx + 1 }} (Maks 20 SN per Peti)</span>
                <span v-else>Dus #{{ batchPasteTarget.boxNum }} (Maks 10 SN per Dus)</span>
              </p>
            </div>
          </div>
          <button type="button" @click="showBatchPasteModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-700">Daftar Serial Number (Pisahkan dengan baris baru atau koma):</label>
            <button v-if="batchPasteRawText" type="button" @click="batchPasteRawText = ''"
              class="text-[11px] font-bold text-slate-400 hover:text-rose-600 transition cursor-pointer">
              Bersihkan
            </button>
          </div>
          <textarea v-model="batchPasteRawText" rows="7" placeholder="Tempelkan daftar SN dari Excel / catatan di sini...
Contoh:
C1101624
G1101625
K1101621
..." class="w-full bg-slate-50 border border-slate-300 focus:border-indigo-600 focus:bg-white rounded-xl p-3 text-xs font-mono font-bold text-slate-900 shadow-2xs transition min-h-[140px] focus:outline-none"></textarea>
          <p class="text-[11px] text-slate-500 italic">
            * Sebanyak {{ batchPasteTarget.isUnit ? 'seluruh' : (batchPasteTarget.mode === 'crate' ? '20' : '10') }} Serial Number pertama akan otomatis dialokasikan ke slot {{ batchPasteTarget.isUnit ? 'satuan' : (batchPasteTarget.mode === 'crate' ? 'Peti ini' : 'Dus ini') }}.
          </p>
        </div>

        <!-- Live Validation / Analisis Data Batch Paste -->
        <div v-if="parsedBatchPasteSNs && parsedBatchPasteSNs.length > 0" class="space-y-2">
          <div class="flex items-center justify-between text-xs bg-slate-100 rounded-xl px-3 py-2 border border-slate-200">
            <span class="font-bold text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-chart-pie text-indigo-600"></i>
              <span>Total: <strong>{{ batchPasteStats.total }} SN</strong></span>
            </span>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <i class="fa-solid fa-check mr-0.5"></i> {{ batchPasteStats.validCount }} Tersedia
              </span>
              <span v-if="batchPasteStats.unverifiedCount > 0"
                class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                <i class="fa-solid fa-circle-xmark mr-0.5"></i> {{ batchPasteStats.unverifiedCount }} Tidak Tersedia
              </span>
              <span v-if="batchPasteStats.duplicatesCount > 0"
                class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                <i class="fa-solid fa-copy mr-0.5"></i> {{ batchPasteStats.duplicatesCount }} Duplikat
              </span>
            </div>
          </div>

          <!-- Alert jika ada SN belum lolos / tidak tersedia -->
          <div v-if="batchPasteStats.unverifiedCount > 0"
            class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] leading-relaxed flex items-start gap-2">
            <i class="fa-solid fa-circle-xmark text-rose-600 mt-0.5 shrink-0"></i>
            <div>
              <strong>SN Tidak Tersedia:</strong> Ditemukan <strong>{{ batchPasteStats.unverifiedCount }} Serial Number</strong> yang tidak terdaftar di stok selesai atau belum lolos penerimaan fisik gudang. Data tidak dapat diterapkan sampai seluruh nomor seri valid.
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
          <button type="button" @click="showBatchPasteModal = false"
            class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold transition cursor-pointer min-h-[42px]">
            Batal
          </button>
          <button type="button" @click="applyBatchPaste"
            :disabled="!parsedBatchPasteSNs || parsedBatchPasteSNs.length === 0 || (batchPasteStats && batchPasteStats.unverifiedCount > 0)"
            :class="!parsedBatchPasteSNs || parsedBatchPasteSNs.length === 0 || (batchPasteStats && batchPasteStats.unverifiedCount > 0) ? 'opacity-40 cursor-not-allowed bg-slate-300 text-slate-500' : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 cursor-pointer'"
            class="px-5 py-2.5 rounded-xl text-xs font-black flex items-center gap-1.5 transition min-h-[42px]">
            <i class="fa-solid fa-check"></i>
            <span>Terapkan ke {{ batchPasteTarget.isUnit ? 'Satuan' : (batchPasteTarget.mode === 'crate' ? 'Peti' : 'Dus') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL BULKING DATA SERIAL NUMBER (PACKING LIST STANDALONE) ================= -->
    <div v-if="showLabelBulkModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div
              class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-base shadow-xs">
              <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Input Bulking Serial Number</h3>
              <p class="text-[11px] text-slate-500">
                Alokasi data Serial Number massal ke kemasan {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }}
              </p>
            </div>
          </div>
          <button type="button" @click="closeLabelBulkModal"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Scope & Mode Options -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 border border-slate-200/90 rounded-2xl p-3 text-xs">
          <!-- Pilihan Jangkauan / Target -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-crosshairs text-emerald-600"></i>
              <span>Target Alokasi:</span>
            </label>
            <div class="relative">
              <select v-model="labelBulkScope"
                class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs text-slate-800 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100 shadow-2xs transition cursor-pointer">
                <option value="all">Mulai dari Awal ({{ labelForm.package_type === 'crate' ? 'Peti 1' : 'Dus 1' }})</option>
                <option value="from_active">Mulai dari {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} Aktif (Ke-{{ activeLabelCrateIndex + 1 }})</option>
                <option value="active_only">Hanya {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} Ini Saja (Ke-{{ activeLabelCrateIndex + 1 }})</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                <i class="fa-solid fa-chevron-down text-[11px]"></i>
              </div>
            </div>
          </div>

          <!-- Pilihan Mode Pengisian -->
          <div class="space-y-1.5">
            <label class="font-bold text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-sliders text-emerald-600"></i>
              <span>Metode Pengisian:</span>
            </label>
            <div class="relative">
              <select v-model="labelBulkMode"
                class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs text-slate-800 font-bold focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100 shadow-2xs transition cursor-pointer">
                <option value="overwrite">Timpa berurutan dari slot pertama</option>
                <option value="empty_only">Hanya isi slot yang masih kosong</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                <i class="fa-solid fa-chevron-down text-[11px]"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Textarea Input Bulking -->
        <div class="space-y-1.5">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-700 flex items-center gap-1">
              <span>Daftar Serial Number:</span>
              <span class="text-slate-400 font-normal">(Paste dari Excel / Scan Barcode)</span>
            </label>
            <button v-if="labelBulkRawText" type="button" @click="labelBulkRawText = ''"
              class="text-[11px] font-bold text-slate-400 hover:text-red-600 transition cursor-pointer">
              Bersihkan
            </button>
          </div>
          <textarea v-model="labelBulkRawText" rows="6"
            placeholder="Tempelkan daftar Serial Number di sini (satu per baris, koma, atau spasi)...
Contoh:
C1101624
G1101625
K1101621
..."
            class="w-full bg-slate-50 border border-slate-300 focus:border-emerald-600 focus:bg-white rounded-xl p-3 text-xs font-mono font-bold text-slate-900 shadow-2xs transition min-h-[140px] focus:outline-none"></textarea>
        </div>

        <!-- Live Validation / Analisis Data -->
        <div v-if="parsedLabelBulkSNs.length > 0" class="space-y-2">
          <div class="flex items-center justify-between text-xs bg-slate-100 rounded-xl px-3 py-2 border border-slate-200">
            <span class="font-bold text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-chart-pie text-emerald-600"></i>
              <span>Total Terdeteksi: <strong>{{ labelBulkStats.total }} SN</strong></span>
            </span>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <i class="fa-solid fa-check mr-0.5"></i> {{ labelBulkStats.validCount }} Tersedia
              </span>
              <span v-if="labelBulkStats.unverifiedCount > 0"
                class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                <i class="fa-solid fa-circle-xmark mr-0.5"></i> {{ labelBulkStats.unverifiedCount }} Tidak Tersedia
              </span>
              <span v-if="labelBulkStats.duplicatesCount > 0"
                class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                <i class="fa-solid fa-copy mr-0.5"></i> {{ labelBulkStats.duplicatesCount }} Duplikat
              </span>
            </div>
          </div>

          <!-- Alert jika ada SN belum lolos / tidak tersedia -->
          <div v-if="labelBulkStats.unverifiedCount > 0"
            class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-[11px] leading-relaxed flex items-start gap-2">
            <i class="fa-solid fa-circle-xmark text-rose-600 mt-0.5 shrink-0"></i>
            <div>
              <strong>SN Tidak Tersedia:</strong> Ditemukan <strong>{{ labelBulkStats.unverifiedCount }} Serial Number</strong> yang tidak terdaftar di stok selesai atau belum lolos penerimaan fisik gudang. Data tidak dapat diterapkan sampai seluruh nomor seri valid.
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <button type="button" @click="closeLabelBulkModal"
            class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold transition cursor-pointer min-h-[40px]">
            Batal
          </button>
          <button type="button" @click="applyLabelBulkData"
            :disabled="parsedLabelBulkSNs.length === 0 || labelBulkStats.unverifiedCount > 0"
            :class="parsedLabelBulkSNs.length === 0 || labelBulkStats.unverifiedCount > 0 ? 'opacity-40 cursor-not-allowed bg-slate-300 text-slate-500' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/25 cursor-pointer'"
            class="px-5 py-2.5 rounded-xl text-xs font-black flex items-center gap-2 transition min-h-[40px]">
            <i class="fa-solid fa-check"></i>
            <span>Terapkan Data Bulking ({{ parsedLabelBulkSNs.length }} SN)</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL PILIH ID PETI DARI PACKING LIST ================= -->
    <div v-if="showSelectCrateModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shadow-xs">
              <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Pilih ID Peti dari Packing List</h3>
              <p class="text-[11px] text-slate-500">
                Alokasikan data Peti (2 Dus &amp; 20 Serial Number) ke Peti {{ selectTarget.crateIdx + 1 }}
              </p>
            </div>
          </div>
          <button type="button" @click="showSelectCrateModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Search Bar -->
        <div class="relative">
          <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input type="text" v-model="selectCrateSearchQuery" placeholder="Cari ID Peti atau No. Packing List..."
            class="w-full pl-9 pr-8 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-emerald-500 focus:outline-none transition">
          <button v-if="selectCrateSearchQuery" type="button" @click="selectCrateSearchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>

        <!-- Daftar Peti yang Tersedia -->
        <div class="max-h-[60vh] overflow-y-auto space-y-3 pr-1">
          <div v-if="filteredAvailablePLCratesList.length === 0"
            class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-500 text-xs">
            <i class="fa-solid fa-box-open text-2xl text-slate-400 mb-2 block"></i>
            <template v-if="getAvailablePLCratesList().length === 0">
              <span class="font-bold block text-slate-700 mb-1">Belum ada Packing List Peti yang tersedia untuk mode {{ sjMode === 'backdate' ? 'Backdate' : 'Realtime' }}</span>
              <span class="text-[11px] text-slate-400">Hanya Packing List yang bertipe sama ({{ sjMode === 'backdate' ? 'Backdate' : 'Realtime' }}) dan belum pernah dikonsumsi yang dapat digunakan.</span>
            </template>
            <span v-else>Tidak ada Peti yang cocok dengan pencarian.</span>
          </div>

          <div v-for="c in filteredAvailablePLCratesList" :key="c.pl_id + '_' + c.crate_id"
            class="border rounded-2xl p-4 transition space-y-3"
            :class="isPLCrateUsed(c.crate_id, selectTarget.crateIdx) ? 'bg-slate-50 border-slate-200 opacity-60' : 'bg-white border-emerald-200 hover:border-emerald-400 hover:shadow-sm'">
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
              <div class="flex items-center gap-2 flex-wrap">
                <span
                  class="font-black font-mono text-sm text-emerald-900 bg-emerald-50 border border-emerald-300 px-2.5 py-1 rounded-lg">
                  {{ c.crate_id }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase font-mono"
                  :class="c.pl_mode === 'backdate' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'">
                  {{ c.pl_mode || 'realtime' }}
                </span>
                <span class="text-[10px] text-slate-500 font-mono">PL: {{ c.pl_number }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="isPLCrateUsed(c.crate_id, selectTarget.crateIdx)"
                  class="text-[10px] font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-1 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-ban"></i> Sudah Digunakan
                </span>
                <button v-else type="button" @click="applySelectedCrateToDO(c)"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-check"></i>
                  <span>Gunakan ID Peti Ini</span>
                </button>
              </div>
            </div>

            <!-- Preview 2 Dus -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <div v-for="(box, bIdx) in c.boxes" :key="bIdx"
                class="bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                <div class="font-bold text-[11px] text-slate-700 mb-1 flex items-center justify-between">
                  <span>Dus {{ box.box_number || (bIdx + 1) }}</span>
                  <span class="font-mono text-[10px] text-slate-500">{{ box.box_id || (c.crate_id + '-0' + (bIdx + 1))
                    }}</span>
                </div>
                <div class="text-[10.5px] font-mono text-slate-600 line-clamp-2">
                  {{ (box.items || []).map(it => it.serial_number).filter(Boolean).slice(0, 5).join(', ') }}...
                  ({{ (box.items || []).filter(it => it.serial_number).length }} SN)
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400 font-medium">
            Total: <strong>{{ filteredAvailablePLCratesList.length }}</strong> Peti tersedia
          </span>
          <button type="button" @click="showSelectCrateModal = false"
            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL PILIH ID DUS DARI PACKING LIST ================= -->
    <div v-if="showSelectBoxModal"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-2.5">
            <div
              class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shadow-xs">
              <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">
                {{ selectTarget.isBoxDO ? 'Pilih ID Dus untuk Kemasan Ini' : 'Pilih ID Dus untuk Peti ' +
                (selectTarget.crateIdx + 1) + ' (Dus ' + selectTarget.boxNumber + ')' }}
              </h3>
              <p class="text-[11px] text-slate-500">
                <span v-if="!selectTarget.isBoxDO">
                  ID Dus akan otomatis disesuaikan menjadi <strong>{{ doForm.crates[selectTarget.crateIdx]?.crate_id
                    }}-0{{ selectTarget.boxNumber }}</strong> mengikuti nomor Peti.
                </span>
                <span v-else>
                  Pilih ID Dus yang telah dibuat di Packing List Mandiri untuk mengisi 10 Serial Number secara instan.
                </span>
              </p>
            </div>
          </div>
          <button type="button" @click="showSelectBoxModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Search Bar -->
        <div class="relative">
          <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input type="text" v-model="selectBoxSearchQuery" placeholder="Cari ID Dus, No. Packing List, atau SN..."
            class="w-full pl-9 pr-8 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-indigo-500 focus:outline-none transition">
          <button v-if="selectBoxSearchQuery" type="button" @click="selectBoxSearchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        </div>

        <!-- Daftar Dus yang Tersedia -->
        <div class="max-h-[60vh] overflow-y-auto space-y-3 pr-1">
          <div v-if="filteredAvailablePLBoxesList.length === 0"
            class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200 text-slate-500 text-xs">
            <i class="fa-solid fa-boxes-packing text-2xl text-slate-400 mb-2 block"></i>
            <template v-if="getAvailablePLBoxesList().length === 0">
              <span class="font-bold block text-slate-700 mb-1">Belum ada Packing List Dus yang tersedia untuk mode {{ sjMode === 'backdate' ? 'Backdate' : 'Realtime' }}</span>
              <span class="text-[11px] text-slate-400">Hanya Packing List yang bertipe sama ({{ sjMode === 'backdate' ? 'Backdate' : 'Realtime' }}) dan belum pernah dikonsumsi yang dapat digunakan.</span>
            </template>
            <span v-else>Tidak ada Dus yang cocok dengan pencarian.</span>
          </div>

          <div v-for="b in filteredAvailablePLBoxesList" :key="b.pl_id + '_' + b.box_id"
            class="border rounded-2xl p-3.5 transition space-y-2.5"
            :class="isPLBoxUsed(b.box_id, selectTarget.crateIdx, selectTarget.boxNumber, selectTarget.isBoxDO) ? 'bg-slate-50 border-slate-200 opacity-60' : 'bg-white border-indigo-200 hover:border-indigo-400 hover:shadow-sm'">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-2">
              <div class="flex items-center gap-2 flex-wrap">
                <span
                  class="font-black font-mono text-xs text-indigo-900 bg-indigo-50 border border-indigo-300 px-2.5 py-1 rounded-lg">
                  {{ b.box_id }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase font-mono"
                  :class="b.pl_mode === 'backdate' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-indigo-100 text-indigo-800 border border-indigo-300'">
                  {{ b.pl_mode || 'realtime' }}
                </span>
                <span class="text-[10px] text-slate-500 font-mono">PL: {{ b.pl_number }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="isPLBoxUsed(b.box_id, selectTarget.crateIdx, selectTarget.boxNumber, selectTarget.isBoxDO)"
                  class="text-[10px] font-bold text-red-700 bg-red-50 border border-red-200 px-2 py-1 rounded-lg flex items-center gap-1">
                  <i class="fa-solid fa-ban"></i> Sudah Digunakan
                </span>
                <button v-else type="button" @click="applySelectedBoxToDO(b)"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                  <i class="fa-solid fa-check"></i>
                  <span>Pilih Dus Ini</span>
                </button>
              </div>
            </div>

            <!-- Preview 10 SN chips -->
            <div class="bg-slate-50/80 p-2.5 rounded-xl border border-slate-200">
              <div class="text-[10px] font-bold text-slate-500 mb-1 flex items-center justify-between">
                <span>Daftar 10 Serial Number:</span>
                <span class="font-mono">{{ b.filled_items }} / 10 SN</span>
              </div>
              <div class="flex flex-wrap gap-1">
                <span v-for="(it, iIdx) in b.items" :key="iIdx"
                  class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded border"
                  :class="it.serial_number ? 'bg-white text-slate-800 border-slate-300' : 'bg-slate-100 text-slate-400 border-slate-200'">
                  {{ it.serial_number || '-' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400 font-medium">
            Total: <strong>{{ filteredAvailablePLBoxesList.length }}</strong> Dus tersedia
          </span>
          <button type="button" @click="showSelectBoxModal = false"
            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold transition cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- ================= FULLSCREEN MODAL PROSES SINKRONISASI CLOUDINARY ================= -->
    <div v-if="isBatchSyncingCloud" style="display: none;" :style="{ display: isBatchSyncingCloud ? 'flex' : 'none' }"
      class="fixed inset-0 z-[999999] items-center justify-center bg-slate-950/75 backdrop-blur-md p-4 select-none no-print">
      <div
        class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 sm:p-7 shadow-2xl space-y-5 text-center my-auto animate-in fade-in zoom-in-95 duration-200">
        <div class="relative flex items-center justify-center">
          <div
            class="w-20 h-20 rounded-3xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-3xl shadow-inner mx-auto">
            <i class="fa-solid fa-cloud-arrow-up fa-bounce"></i>
          </div>
        </div>
        <div>
          <h3 class="font-black text-slate-900 text-base sm:text-lg">Mengunggah ke Cloudinary</h3>
          <p class="text-xs text-slate-500 mt-1 font-medium">
            Memproses dokumen <strong class="text-indigo-600">{{ batchSyncProgress.current }}</strong> dari <strong>{{
              batchSyncProgress.total }}</strong>
          </p>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: (batchSyncProgress.total > 0 ? (batchSyncProgress.current / batchSyncProgress.total * 100) : 0) + '%' }">
          </div>
        </div>
        <p class="text-[11px] text-slate-400 font-mono">
          <i class="fa-solid fa-spinner fa-spin mr-1"></i> Mengonversi dokumen ke PDF &amp; menyinkronkan cloud...
        </p>
      </div>
    </div>

    <!-- ================= MODAL SUKSES PENERBITAN DELIVERY ORDER (OPSI CETAK DOKUMEN & LABEL) ================= -->
    <div v-if="showDOPostPublishModal && lastPublishedDO"
      class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
      <div
        class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-4 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-base shadow-md shadow-emerald-600/30">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900 leading-tight">Surat Jalan Berhasil Diterbitkan!</h3>
              <p class="text-[11px] text-slate-500">Pilih dokumen atau label yang ingin dicetak sekarang</p>
            </div>
          </div>
          <button type="button" @click="showDOPostPublishModal = false"
            class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
            <i class="fa-solid fa-xmark text-lg"></i>
          </button>
        </div>

        <!-- Info Ringkas DO -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5 text-xs">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Nomor Surat Jalan:</span>
            <span class="font-bold text-red-700 font-mono">{{ lastPublishedDO.do_number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Customer:</span>
            <span class="font-bold text-slate-900">{{ lastPublishedDO.customer_name }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Total Unit:</span>
            <span class="font-bold text-emerald-700">{{ lastPublishedDO.total_quantity }} Unit</span>
          </div>
        </div>

        <!-- Opsi Tombol Cetak -->
        <div class="space-y-2 pt-1">
          <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Pilihan Cetak Dokumen Resmi
            &amp; Label:</label>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button type="button"
              @click="openDODetailAndPrint(lastPublishedDO.id || lastPublishedDO.do_id, 'surat_jalan')"
              class="flex items-center gap-2.5 p-3 rounded-xl border border-red-200 bg-red-50/70 hover:bg-red-100/80 text-red-800 text-xs font-bold transition text-left cursor-pointer group">
              <div
                class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-file-invoice"></i>
              </div>
              <div>
                <div class="font-black text-slate-900">Surat Jalan</div>
                <div class="text-[10px] text-slate-500 font-normal">Dokumen pengiriman resmi</div>
              </div>
            </button>

            <button v-if="lastPublishedDO.package_type !== 'unit'" type="button"
              @click="openDODetailAndPrint(lastPublishedDO.id || lastPublishedDO.do_id, 'packing_list')"
              class="flex items-center gap-2.5 p-3 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100/80 text-indigo-800 text-xs font-bold transition text-left cursor-pointer group">
              <div
                class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-list-check"></i>
              </div>
              <div>
                <div class="font-black text-slate-900">Packing List</div>
                <div class="text-[10px] text-slate-500 font-normal">Daftar rincian isi kemasan</div>
              </div>
            </button>

            <button type="button"
              @click="openDODetailAndPrint(lastPublishedDO.id || lastPublishedDO.do_id, lastPublishedDO.package_type === 'unit' ? 'label_sn' : (lastPublishedDO.package_type === 'box' ? 'label_dus' : 'label_peti'))"
              class="flex items-center gap-2.5 p-3 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100/80 text-amber-800 text-xs font-bold transition text-left cursor-pointer group">
              <div
                class="w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                <i :class="lastPublishedDO.package_type === 'unit' ? 'fa-solid fa-barcode' : 'fa-solid fa-tags'"></i>
              </div>
              <div>
                <div class="font-black text-slate-900">
                  <span v-if="lastPublishedDO.package_type === 'box'">Label Dus (10 SN)</span>
                  <span v-else-if="lastPublishedDO.package_type === 'unit'">Label Satuan (SN)</span>
                  <span v-else>Label Peti (20 SN)</span>
                </div>
                <div class="text-[10px] text-slate-500 font-normal">Label stiker barcode</div>
              </div>
            </button>

            <button v-if="!lastPublishedDO.package_type || lastPublishedDO.package_type === 'crate'" type="button"
              @click="openDODetailAndPrint(lastPublishedDO.id || lastPublishedDO.do_id, 'label_child')"
              class="flex items-center gap-2.5 p-3 rounded-xl border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100/80 text-emerald-800 text-xs font-bold transition text-left cursor-pointer group">
              <div
                class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-tag"></i>
              </div>
              <div>
                <div class="font-black text-slate-900">Label Tiap Dus</div>
                <div class="text-[10px] text-slate-500 font-normal">Stiker per Dus (10 SN)</div>
              </div>
            </button>

            <button v-if="lastPublishedDO.package_type !== 'unit'" type="button"
              @click="openDODetailAndPrint(lastPublishedDO.id || lastPublishedDO.do_id, 'label_sn')"
              class="flex items-center gap-2.5 p-3 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100/80 text-blue-800 text-xs font-bold transition text-left cursor-pointer group">
              <div
                class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-barcode"></i>
              </div>
              <div>
                <div class="font-black text-slate-900">Label Tiap Unit (SN)</div>
                <div class="text-[10px] text-slate-500 font-normal">Stiker thermal 80x20mm</div>
              </div>
            </button>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
          <button type="button" @click="showDOPostPublishModal = false"
            class="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold transition cursor-pointer">
            Selesai / Tutup
          </button>
        </div>
      </div>
    </div>




          <!-- ================= MODAL RINCIAN DOKUMEN (BREAKDOWN PER UNIT) ================= -->
          <div v-if="selectedReportDoc"
            class="modal-backdrop-full flex items-center justify-center p-3 sm:p-4 animate-fadeIn">
            <div
              class="bg-white border border-slate-200 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-scaleUp">
              <!-- Header Modal -->
              <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 rounded-xl bg-red-100 border border-red-200 text-red-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-invoice"></i>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span
                        class="text-[10px] font-bold px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded uppercase">RINCIAN
                        DOKUMEN</span>
                      <span class="text-xs text-slate-400">|</span>
                      <span class="text-xs font-semibold text-slate-500">{{ getReportCategoryTitle(reportSubTab)
                        }}</span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 font-mono mt-0.5">{{ selectedReportDoc.doc_code }}
                    </h3>
                  </div>
                </div>
                <button type="button" @click="closeReportDocDetail"
                  class="w-8 h-8 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition cursor-pointer">
                  <i class="fa-solid fa-xmark text-sm"></i>
                </button>
              </div>

              <!-- Metadata Summary Card -->
              <div class="p-4 bg-slate-50/50 border-b border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                  <span class="text-[10.5px] font-bold text-slate-400 block">Dokumen Acuan:</span>
                  <span class="font-mono font-bold text-blue-700">{{ selectedReportDoc.origin_doc || '-' }}</span>
                </div>
                <div>
                  <span class="text-[10.5px] font-bold text-slate-400 block">Operator / Petugas:</span>
                  <span class="font-bold text-slate-800">{{ selectedReportDoc.operator || '-' }}</span>
                </div>
                <div>
                  <span class="text-[10.5px] font-bold text-slate-400 block">Waktu Transaksi:</span>
                  <span class="font-mono text-slate-700">{{ formatDateTime(selectedReportDoc.timestamp) }}</span>
                </div>
                <div>
                  <span class="text-[10.5px] font-bold text-slate-400 block">Total Unit / Set:</span>
                  <span class="font-mono font-black text-slate-900">{{ modalReportDocItems.length }} Set</span>
                </div>
              </div>

              <!-- List Unit Items in Document -->
              <div class="p-4 overflow-y-auto flex-1">
                <table class="w-full text-left text-xs border border-slate-200 rounded-xl overflow-hidden">
                  <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 text-[11px]">
                    <!-- 1. SUBTAB WO / MO: No | Set # | SN P15 | PN -> P1 | P12 | Status Unit -->
                    <template v-if="reportSubTab === 'wo'">
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-20 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center w-52 border-b border-slate-200 align-middle">
                          Status Unit / Set</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/5">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/5">P12</th>
                      </tr>
                    </template>

                    <!-- 2. SUBTAB PERAKITAN: Status Perakitan & Perubahan / Rework -->
                    <template v-else-if="reportSubTab === 'assembly'">
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-10 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-16 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center w-28 border-b border-slate-200 align-middle">Status
                          Perakitan</th>
                        <th rowspan="2" class="p-2.5 border-b border-slate-200 align-middle">Keterangan &amp; Perubahan
                        </th>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-b border-slate-200 align-middle">Aksi</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P12</th>
                      </tr>
                    </template>

                    <!-- 3. SUBTAB TEST FUNCTIONAL 1 (TF1): Perakitan Good + Hasil TF1 -->
                    <template v-else-if="reportSubTab === 'assy_test'">
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-10 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-16 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center w-24 border-b border-slate-200 align-middle">Perakitan
                        </th>
                        <th rowspan="2" class="p-2.5 text-center w-36 border-b border-slate-200 align-middle">Hasil TF1
                        </th>
                        <th rowspan="2" class="p-2.5 border-b border-slate-200 align-middle">Keterangan TF1</th>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-b border-slate-200 align-middle">Aksi</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P12</th>
                      </tr>
                    </template>

                    <!-- 4. SUBTAB TEST FUNCTIONAL 2 (QC): Perakitan Good + TF1 Good Semua + Hasil QC -->
                    <template v-else-if="reportSubTab === 'qc_test'">
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-10 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-16 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center w-20 border-b border-slate-200 align-middle">Perakitan
                        </th>
                        <th rowspan="2" class="p-2.5 text-center w-24 border-b border-slate-200 align-middle">TF1</th>
                        <th rowspan="2" class="p-2.5 text-center w-36 border-b border-slate-200 align-middle">Hasil QC
                          (TF2)</th>
                        <th rowspan="2" class="p-2.5 border-b border-slate-200 align-middle">Keterangan QC</th>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-b border-slate-200 align-middle">Aksi</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P12</th>
                      </tr>
                    </template>

                    <!-- 5. SUBTAB RECEIPTS (WH/GR): Semua Good (Perakitan, TF1, TF2) + Status Receipts -->
                    <template v-else-if="reportSubTab === 'warehouse_receipt'">
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-10 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-16 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center w-20 border-b border-slate-200 align-middle">Perakitan
                        </th>
                        <th rowspan="2" class="p-2.5 text-center w-20 border-b border-slate-200 align-middle">TF1</th>
                        <th rowspan="2" class="p-2.5 text-center w-20 border-b border-slate-200 align-middle">TF2 (QC)
                        </th>
                        <th rowspan="2" class="p-2.5 text-center w-28 border-b border-slate-200 align-middle">Receipts
                          (WH/GR)</th>
                        <th rowspan="2" class="p-2.5 border-b border-slate-200 align-middle">Keterangan</th>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-b border-slate-200 align-middle">Aksi</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P12</th>
                      </tr>
                    </template>

                    <!-- FALLBACK DEFAULT -->
                    <template v-else>
                      <tr>
                        <th rowspan="2" class="p-2.5 text-center w-10 border-r border-b border-slate-200 align-middle">
                          No</th>
                        <th rowspan="2" class="p-2.5 text-center w-16 border-r border-b border-slate-200 align-middle">
                          Set #</th>
                        <th rowspan="2" class="p-2.5 text-center border-r border-b border-slate-200 align-middle">SN P15
                        </th>
                        <th colspan="2"
                          class="p-2 text-center bg-slate-200/70 border-b border-slate-200 tracking-wider">PN</th>
                        <th rowspan="2" class="p-2.5 text-center border-b border-slate-200 align-middle">Status</th>
                        <th rowspan="2" class="p-2.5 border-b border-slate-200 align-middle">Keterangan</th>
                        <th rowspan="2" class="p-2.5 text-center w-14 border-b border-slate-200 align-middle">Aksi</th>
                      </tr>
                      <tr>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P1</th>
                        <th class="p-2 text-center border-r border-b border-slate-200 bg-slate-100/90 w-1/6">P12</th>
                      </tr>
                    </template>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                    <tr v-for="(it, i) in modalReportDocItems" :key="it.assembly_id || it.id || i"
                      @click="reportSubTab !== 'wo' && openItemDetailFromDoc(it, i)"
                      :class="reportSubTab === 'wo' ? 'select-none bg-white hover:bg-slate-50/70 transition' : 'hover:bg-red-50/50 transition cursor-pointer select-none group'"
                      :title="reportSubTab === 'wo' ? '' : 'Klik baris atau tombol panah untuk melihat rincian hasil'">
                      <!-- No -->
                      <td class="p-2.5 text-center text-slate-400 font-sans"
                        :class="reportSubTab === 'wo' ? 'border-r border-slate-100' : ''">{{ i + 1 }}</td>
                      <!-- Set # -->
                      <td class="p-2.5 text-center font-bold text-slate-800"
                        :class="reportSubTab === 'wo' ? 'border-r border-slate-100' : ''">Set #{{ it.set_number || (i +
                        1) }}</td>

                      <!-- 1. JIKA WO / MO: Kolom dipanjangkan secara terpisah SN P15 | P1 | P12 | Status Unit -->
                      <template v-if="reportSubTab === 'wo'">
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-red-900 text-xs px-3 py-1 bg-red-50 border border-red-200 rounded-lg inline-block shadow-2xs">
                            {{ it.part15_sn || it.p9_sn || it.sn || '-' }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-slate-800 text-xs px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                            {{ it.part1_sn || '-' }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-slate-800 text-xs px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                            {{ it.part12_sn || '-' }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <!-- Status item di modal WO -->
                          <template v-if="it.is_declined || it.status === 'Decline' || it.status === 'Declined'">
                            <span
                              class="px-2.5 py-1 rounded-lg text-xs font-bold border bg-rose-50 text-rose-700 border-rose-200 inline-flex items-center gap-1.5 shadow-2xs"
                              :title="it.declined_reason ? 'Alasan: ' + it.declined_reason : 'Declined'">
                              <i class="fa-solid fa-circle-xmark text-rose-500"></i>
                              <span>Dikembalikan (Declined)</span>
                            </span>
                          </template>
                          <template v-else-if="it.status === 'Dikembalikan ke Gudang' || it.status === 'Tersedia' || (!it.finished_at && (selectedReportDoc.status === 'SELESAI' || selectedReportDoc.session_status === 'COMPLETED_SHIFT' || selectedReportDoc.tray_status === 'returned'))">
                            <span
                              class="px-2.5 py-1 rounded-lg text-xs font-bold border bg-amber-50 text-amber-700 border-amber-200 inline-flex items-center gap-1.5 shadow-2xs">
                              <i class="fa-solid fa-arrow-rotate-left text-amber-600"></i>
                              <span>Dikembalikan ke Gudang</span>
                            </span>
                          </template>
                          <template v-else-if="it.finished_at || it.status === 'Good' || it.status === 'SELESAI'">
                            <span
                              class="px-2.5 py-1 rounded-lg text-xs font-bold border bg-emerald-50 text-emerald-700 border-emerald-200 inline-flex items-center gap-1.5 shadow-2xs">
                              <i class="fa-solid fa-circle-check text-emerald-600"></i>
                              <span>Selesai Dirakit</span>
                            </span>
                          </template>
                          <template v-else>
                            <span
                              class="px-2.5 py-1 rounded-lg text-xs font-bold border bg-blue-50 text-blue-700 border-blue-200 inline-flex items-center gap-1.5 shadow-2xs">
                              <i class="fa-solid fa-truck-ramp-box text-blue-600"></i>
                              <span>Tersupply</span>
                            </span>
                          </template>
                        </td>
                      </template>

                      <!-- SUBTAB LAIN: Kolom terpisah SN P15 | P1 | P12 -->
                      <template v-else>
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-red-900 text-xs px-3 py-1 bg-red-50 border border-red-200 rounded-lg inline-block shadow-2xs">
                            {{ it.part15_sn || it.p9_sn || it.sn || '-' }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-slate-800 text-xs px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                            {{ it.part1_sn || '-' }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-mono border-r border-slate-100">
                          <span
                            class="font-bold text-slate-800 text-xs px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg inline-block">
                            {{ it.part12_sn || '-' }}
                          </span>
                        </td>
                      </template>

                      <!-- 1. JIKA WO: HANYA TAMPIL SET DAN SN, TIDAK ADA KOLOM ATAU AKSI LAIN -->

                      <!-- 2. JIKA PERAKITAN -->
                      <template v-if="reportSubTab === 'assembly'">
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(getHistoryAssemblyStatus(it))"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(getHistoryAssemblyStatus(it)) }}
                          </span>
                        </td>
                        <td class="p-2.5 font-sans text-slate-600 text-xs">
                          <div v-if="it.assembly_reason || it.reason" class="text-slate-700 italic">
                            {{ it.assembly_reason || it.reason }}
                          </div>
                          <div v-else-if="it.rework_reason" class="text-purple-700 font-medium">
                            <i class="fa-solid fa-rotate-left text-[10px] mr-1"></i>{{ it.rework_reason }}
                          </div>
                          <div v-else class="text-slate-400 italic">-</div>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <button type="button" @click.stop="openItemDetailFromDoc(it, i)"
                            class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-red-600 text-slate-500 group-hover:text-white border border-slate-200 group-hover:border-red-600 flex items-center justify-center transition shadow-2xs mx-auto cursor-pointer"
                            title="Buka Rincian Hasil">
                            <i
                              class="fa-solid fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                          </button>
                        </td>
                      </template>

                      <!-- 3. JIKA TEST FUNCTIONAL 1 (TF1) -->
                      <template v-else-if="reportSubTab === 'assy_test'">
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(getHistoryAssemblyStatus(it))"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(getHistoryAssemblyStatus(it)) }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(it.assy_test_status || it.status)"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.assy_test_status || it.status) }}
                          </span>
                        </td>
                        <td class="p-2.5 font-sans italic text-slate-500 text-xs">
                          {{ it.failure_reasons ? formatFailureReason(it.failure_reasons) : (it.assy_failure_reasons ||
                          it.notes || '-') }}
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <button type="button" @click.stop="openItemDetailFromDoc(it, i)"
                            class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-red-600 text-slate-500 group-hover:text-white border border-slate-200 group-hover:border-red-600 flex items-center justify-center transition shadow-2xs mx-auto cursor-pointer"
                            title="Buka Rincian Hasil">
                            <i
                              class="fa-solid fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                          </button>
                        </td>
                      </template>

                      <!-- 4. JIKA TEST FUNCTIONAL 2 (QC) -->
                      <template v-else-if="reportSubTab === 'qc_test'">
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(getHistoryAssemblyStatus(it))"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(getHistoryAssemblyStatus(it)) }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(it.tf1_status || it.assy_test_status || 'Good')"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.tf1_status || it.assy_test_status || 'Good') }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(it.qc_test_status || it.qc_status || it.status)"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.qc_test_status || it.qc_status || it.status) }}
                          </span>
                        </td>
                        <td class="p-2.5 font-sans italic text-slate-500 text-xs">
                          {{ it.qc_failure_reasons || it.failure_reasons || it.qc_notes || it.notes || '-' }}
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <button type="button" @click.stop="openItemDetailFromDoc(it, i)"
                            class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-red-600 text-slate-500 group-hover:text-white border border-slate-200 group-hover:border-red-600 flex items-center justify-center transition shadow-2xs mx-auto cursor-pointer"
                            title="Buka Rincian Hasil">
                            <i
                              class="fa-solid fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                          </button>
                        </td>
                      </template>

                      <!-- 5. JIKA RECEIPTS (WH/GR) -->
                      <template v-else-if="reportSubTab === 'warehouse_receipt'">
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(getHistoryAssemblyStatus(it))"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(getHistoryAssemblyStatus(it)) }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(it.tf1_status || it.assy_test_status || 'Good')"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.tf1_status || it.assy_test_status || 'Good') }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span :class="getStatusTextClass(it.qc_test_status || it.qc_status || 'Good')"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.qc_test_status || it.qc_status || 'Good') }}
                          </span>
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <span
                            :class="getStatusTextClass(it.warehouse_receipt_status === 'ACCEPTED' ? 'accepted' : (it.warehouse_receipt_status === 'DECLINED' ? 'declined' : (it.warehouse_receipt_status || it.verified_status)))"
                            class="text-xs uppercase tracking-wide">
                            {{ formatDocumentStatusText(it.warehouse_receipt_status || it.verified_status) }}
                          </span>
                        </td>
                        <td class="p-2.5 font-sans italic text-slate-500 text-xs">
                          {{ it.warehouse_decline_reason || it.declined_reason || it.notes || '-' }}
                        </td>
                        <td class="p-2.5 text-center font-sans">
                          <button type="button" @click.stop="openItemDetailFromDoc(it, i)"
                            class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-red-600 text-slate-500 group-hover:text-white border border-slate-200 group-hover:border-red-600 flex items-center justify-center transition shadow-2xs mx-auto cursor-pointer"
                            title="Buka Rincian Hasil">
                            <i
                              class="fa-solid fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                          </button>
                        </td>
                      </template>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Footer Modal -->
              <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div>
                  <span class="text-xs text-slate-500 font-medium">Menampilkan {{ modalReportDocItems.length }} set
                    perakitan pada dokumen ini.</span>
                </div>
                <button type="button" @click="closeReportDocDetail"
                  class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition cursor-pointer">
                  Tutup
                </button>
              </div>
            </div>
          </div>


      <!-- ================= MODAL DETAIL HASIL PERAKITAN (MURNI RAKITAN TANPA TEST FUNCTIONAL) ================= -->
      <div v-if="showAssemblyDetailModal || showHoldDetailModal"
        @click.self="showAssemblyDetailModal = false; showHoldDetailModal = false"
        class="modal-backdrop-full !m-0 !top-0 !left-0 !right-0 !bottom-0 flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
          <!-- Modal Header -->
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                :class="(getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU') ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold') ? 'bg-amber-50 text-amber-700 border-amber-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Rework') ? 'bg-purple-50 text-purple-700 border-purple-200' : ((getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap') ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200')))"
                class="w-10 h-10 rounded-xl border flex items-center justify-center text-lg font-bold shadow-xs">
                <i v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU'"
                  class="fa-solid fa-arrows-rotate"></i>
                <i v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold'"
                  class="fa-solid fa-hand"></i>
                
                <i v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap'"
                  class="fa-solid fa-ban"></i>
                <i v-else class="fa-solid fa-check"></i>
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900">Detail Hasil Perakitan</h3>
                <p class="text-[11px] text-slate-500 font-medium font-mono">
                  {{ selectedAssemblyItem?.tray_code || selectedAssemblyTray?.tray_code }} &bull; Set #{{
                  selectedAssemblyItem?.set_number }}
                </p>
              </div>
            </div>
            <button type="button" @click="showAssemblyDetailModal = false; showHoldDetailModal = false"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>

          <!-- Unit Information Card -->
          <div class="space-y-3 text-xs">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">No. Dokumen
                  Perakitan:</span>
                <strong class="font-mono text-slate-900 text-xs">{{ (selectedAssemblyItem?.status === 'Dirakit Ulang' ||
                  selectedAssemblyItem?.qc_status === 'RU' || (typeof isReassemblySet === 'function' &&
                  isReassemblySet(selectedAssemblyItem))) ? getFormattedDocCode(selectedAssemblyItem?.tray_code,
                  'assy_rework') : (selectedAssemblyItem?.assy_doc_number ||
                  getFormattedDocCode(selectedAssemblyItem?.tray_code, 'assy') || '-') }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">No. Dokumen WO:</span>
                <strong class="font-mono text-slate-900 text-xs">{{ selectedAssemblyItem?.tray_code ||
                  selectedAssemblyTray?.tray_code || '-' }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">Operator Perakitan:</span>
                <strong class="text-slate-800 text-xs">{{ selectedAssemblyItem?.pic_assembly ||
                  selectedAssemblyTray?.pic_assembly || '-' }}</strong>
              </div>
              <div>
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase block">Status Perakitan:</span>
                <span v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Good'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-circle-check text-[9px]"></i> GOOD
                </span>
                <span v-else-if="isItemPendingAdmin(selectedAssemblyItem || selectedHoldItem)"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> PENDING (VERIF ADMIN)
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'RU'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-indigo-100 text-indigo-900 border border-indigo-300 inline-flex items-center gap-1">
                  <i class="fa-solid fa-arrows-rotate text-[9px]"></i> RU
                </span>
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Hold'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-block">
                  <i class="fa-solid fa-hand text-[9px] mr-0.5"></i> HOLD
                </span>
                
                <span v-else-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) === 'Scrap'"
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-slate-200 text-slate-800 border border-slate-300 inline-block">
                  <i class="fa-solid fa-ban text-[9px] mr-0.5"></i> SCRAP
                </span>
                <span v-else
                  class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-block">
                  <i class="fa-solid fa-circle-check text-[9px] mr-0.5"></i> GOOD
                </span>
              </div>
            </div>

            <!-- Timestamps & Duration -->
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 grid grid-cols-3 gap-2 text-[11px]">
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Waktu Mulai:</span>
                <span class="font-mono text-slate-700 text-[10.5px]">{{ formatDateTime(selectedAssemblyItem?.started_at)
                  || '-' }}</span>
              </div>
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Waktu Selesai:</span>
                <span class="font-mono text-slate-700 text-[10.5px]">{{
                  formatDateTime(selectedAssemblyItem?.finished_at) || '-' }}</span>
              </div>
              <div>
                <span class="text-[9.5px] font-bold text-slate-400 uppercase block">Durasi Perakitan:</span>
                <strong class="font-mono text-indigo-700 text-[10.5px]">{{
                  calculateAssemblyDuration(selectedAssemblyItem?.started_at, selectedAssemblyItem?.finished_at)
                  }}</strong>
              </div>
            </div>

            <!-- Serial Numbers -->
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2 font-mono">
              <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">SN P9 (Part 15):</span>
                <strong class="text-slate-900 text-sm">{{ selectedAssemblyItem?.part15_sn || selectedHoldItem?.part15_sn
                  || '-' }}</strong>
              </div>
              <div class="flex justify-between items-center border-b border-slate-200/60 pb-1.5">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">PN Part 1:</span>
                <strong class="text-slate-800">{{ selectedAssemblyItem?.part1_sn || selectedHoldItem?.part1_sn || '-'
                  }}</strong>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-[10px] font-sans font-bold text-slate-500 uppercase">PN Part 12:</span>
                <strong class="text-slate-800">{{ selectedAssemblyItem?.part12_sn || selectedHoldItem?.part12_sn || '-'
                  }}</strong>
              </div>
            </div>

            <!-- Problem / Failure Details Box if any -->
            <div
              v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) !== 'Good' && (selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.reason)"
              class="bg-amber-50 border border-amber-200 p-3 rounded-xl space-y-1">
              <div class="text-[10px] font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Catatan Kendala / Part Cacat:</span>
              </div>
              <div class="text-xs text-amber-950 font-medium leading-relaxed">
                {{ selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.reason || 'Tidak ada catatan kendala'
                }}
              </div>
            </div>

            <!-- Riwayat Pergantian Part / Perubahan Status -->
            <div
              v-if="selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || (selectedAssemblyItem?.reason && selectedAssemblyItem?.reason.includes('Ganti Part'))"
              class="bg-blue-50 border border-blue-200 p-3 rounded-xl space-y-2 text-xs">
              <div class="text-[10px] font-black uppercase tracking-wider text-blue-900 flex items-center gap-1.5">
                <i class="fa-solid fa-wrench text-blue-600"></i>
                <span>Riwayat Pergantian Part:</span>
              </div>
              <div
                v-if="getParsedReplacementHistory(selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason).length > 0"
                class="flex flex-wrap gap-2">
                <div
                  v-for="r in getParsedReplacementHistory(selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason)"
                  :key="r.partType"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-blue-200 rounded-lg text-xs shadow-2xs">
                  <span class="font-bold text-blue-800 text-[10.5px] uppercase">Part {{ r.partType }}:</span>
                  <span class="font-mono line-through text-rose-500 font-bold">{{ r.oldSN }}</span>
                  <i class="fa-solid fa-arrow-right text-[10px] text-slate-400 mx-0.5"></i>
                  <span class="font-mono text-emerald-600 font-black">{{ r.newSN }}</span>
                </div>
              </div>
              <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                {{ selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason ||
                selectedAssemblyItem?.reason }}
              </div>
            </div>

            <!-- Rework / Hold Reason Section (Hanya jika status bukan Good) -->
            <div
              v-if="getAssemblyStageStatus(selectedAssemblyItem || selectedHoldItem) !== 'Good' && (selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason || selectedAssemblyItem?.reason || selectedAssemblyItem?.assembly_reason || selectedAssemblyItem?.latest_replacement_reason)"
              class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 space-y-2 text-xs">
              <div class="flex items-center space-x-2 text-blue-900 font-bold">
                <i class="fa-solid fa-file-lines text-blue-600"></i>
                <span>Catatan / Alasan Unit Perakitan:</span>
              </div>
              <div>
                <div v-if="selectedAssemblyItem?.latest_replacement_reason"
                  class="font-mono text-blue-950 font-medium leading-relaxed">
                  <span class="font-bold text-blue-800">Alasan Ganti Part:</span> {{
                  selectedAssemblyItem?.latest_replacement_reason }}
                  <div v-if="selectedAssemblyItem?.rework_reason" class="mt-1 text-[11px] text-slate-600">
                    <span class="font-bold text-slate-700">Catatan Tambahan:</span> {{
                    selectedAssemblyItem?.rework_reason }}
                  </div>
                </div>
                <div v-else class="font-mono text-blue-950 font-medium leading-relaxed">
                  {{ selectedAssemblyItem?.rework_reason || selectedHoldItem?.rework_reason ||
                  selectedAssemblyItem?.reason }}
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons Bar -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
            <!-- 1. Tombol KEMBALI -->
            <button type="button" @click="showAssemblyDetailModal = false; showHoldDetailModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
              <i class="fa-solid fa-arrow-left text-[11px]"></i>
              <span>Kembali</span>
            </button>

            <!-- Tombol Aksi: DAFTAR ULANG & GANTI PART -->
            <!-- 1. Jika Sedang Menunggu Verifikasi Admin: Sembunyikan Tombol & Tampilkan Status Pending (hanya untuk yang bermasalah di perakitan) -->
            <div
              v-if="isItemPendingAdmin(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold shadow-xs">
              <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
              <div>
                <span class="block text-amber-950 font-black">{{ getItemPendingLabel(selectedAssemblyItem ||
                  selectedHoldItem) }}</span>
                <span class="block text-[10px] text-amber-700 font-medium">Pengajuan sedang diproses Admin. Tombol aksi
                  akan muncul kembali setelah dikonfirmasi.</span>
              </div>
            </div>

            <!-- 2. Jika SUDAH MASUK PERAKITAN ULANG (RU): GABISA DIAPA-APAIN, HANYA LIHAT DETAIL PERUBAHAN PART -->
            <div v-else-if="isReassemblySet(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs shadow-xs">
              <i class="fa-solid fa-arrows-rotate text-indigo-600 text-base shrink-0"></i>
              <div>
                <span class="block text-indigo-950 font-black uppercase text-[11px]">Dirakit Ulang (RU)</span>
                <span class="block text-[10.5px] text-indigo-700 font-medium leading-relaxed">Unit telah masuk antrean
                  perakitan ulang dan tidak dapat diubah lagi.</span>
              </div>
            </div>

            <div
              v-else-if="(selectedAssemblyItem || selectedHoldItem)?.assembly_status !== 'Good' && ((selectedAssemblyItem || selectedHoldItem)?.verified_status === 'APPROVED_REREG' || (selectedAssemblyItem || selectedHoldItem)?.is_reregistered)"
              class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs shadow-xs">
              <i class="fa-solid fa-circle-check text-indigo-600 text-base shrink-0"></i>
              <div>
                <span class="block text-indigo-950 font-black uppercase text-[11px]">Selesai Didaftarkan Ulang &bull;
                  Disetujui Admin</span>
                <span class="block text-[10.5px] text-indigo-700 font-medium leading-relaxed">Unit ini telah selesai
                  disetujui Admin untuk dirakit ulang ke dokumen baru (<strong class="font-mono text-indigo-900">{{
                    (selectedAssemblyItem || selectedHoldItem)?.rereg_wo_suffix ? ((selectedAssemblyItem ||
                    selectedHoldItem)?.tray_code + '-' + (selectedAssemblyItem || selectedHoldItem)?.rereg_wo_suffix) :
                    getNextPUTrayCode((selectedAssemblyItem || selectedHoldItem)?.tray_code) }}</strong>). Unit ini
                  tidak dapat didaftarkan ulang lagi.</span>
              </div>
            </div>

            <!-- 3. Jika Belum Didaftarkan Ulang & Status Perakitan Bukan Good: Tampilkan Tombol -->
            <div
              v-else-if="String(selectedAssemblyItem?.assembly_status || selectedHoldItem?.assembly_status || 'Good').trim().toUpperCase() !== 'GOOD' && !isReassemblySet(selectedAssemblyItem || selectedHoldItem)"
              class="flex items-center gap-2 flex-wrap">

              <!-- KONDISI A: DARI HASIL TEST FUNCTIONAL (Uji Fungsional 1 atau QC) -> LANJUT PROSES & GANTI PART -->
              <template
                v-if="currentTab === 'functional_hold' || (showHoldDetailModal && currentTab !== 'production_hold')">
                <button type="button"
                  @click="proceedHoldItem(selectedHoldItem || selectedAssemblyItem)"
                  class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-circle-arrow-right text-[11px]"></i>
                  <span>{{ getHoldProceedButtonLabel(selectedHoldItem || selectedAssemblyItem) }}</span>
                </button>
                <button type="button" @click="openReplacePartSubModal"
                  class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-wrench text-[11px]"></i>
                  <span>Ganti Part</span>
                </button>
              </template>

              <!-- KONDISI B: DARI HASIL PERAKITAN (ASSEMBLY MURNI) -> LANJUT PROSES & GANTI PART -->
              <template v-else>
                <!-- Keterangan Jika Semua Part Bermasalah Sudah Diganti: Informasi Tambahan Siap Daftar Ulang -->
                <div v-if="isAllProblematicPartsReplaced(selectedAssemblyItem || selectedHoldItem)"
                  class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                  <span>Part bermasalah telah diganti &bull; Siap untuk Rakit Ulang</span>
                </div>

                <!-- Tombol LANJUT TF 1 / PROSES BERIKUTNYA -->
                <button type="button"
                  @click="proceedHoldItem(selectedAssemblyItem || selectedHoldItem)"
                  class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-circle-arrow-right text-[11px]"></i>
                  <span>{{ getHoldProceedButtonLabel(selectedAssemblyItem || selectedHoldItem) }}</span>
                </button>

                <!-- Tombol GANTI PART -->
                <button type="button" @click="openReplacePartSubModal"
                  class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 cursor-pointer active:scale-95 whitespace-nowrap">
                  <i class="fa-solid fa-wrench text-[11px]"></i>
                  <span>Ganti Part</span>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SUB-MODAL 1: DAFTAR ULANG 1 SET RAKITAN (Perakitan Ulang) ================= -->
      <div v-if="showReregisterSubModal"
        @click.self="safeCloseModal(() => showReregisterSubModal = false, !!(reregOperator || reregisterReason))"
        class="modal-backdrop-full modal-backdrop-nested !m-0 !top-0 !left-0 !right-0 !bottom-0 flex items-center justify-center p-3 sm:p-4 overflow-y-auto no-print">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-md w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto animate-fadeIn">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm font-bold">
                <i class="fa-solid fa-hammer"></i>
              </div>
              <div>
                <h3 class="font-black text-sm text-slate-900">Daftar Ulang — Perakitan Ulang</h3>
                <p class="text-[11px] text-slate-500 font-medium">Set ini akan dikembalikan ke antrean perakitan
                  operator</p>
              </div>
            </div>
            <button type="button"
              @click="safeCloseModal(() => showReregisterSubModal = false, !!(reregOperator || reregisterReason))"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="space-y-3 text-xs">
            <!-- Info Alur Baru -->
            <div
              class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-900 flex items-start gap-2.5">
              <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm mt-0.5 shrink-0"></i>
              <div class="space-y-1">
                <span class="font-bold block text-amber-950">Alur Perakitan Ulang:</span>
                <ul class="leading-relaxed text-amber-800 space-y-0.5 list-disc pl-3">
                  <li>Status set akan menjadi <strong>Supplied</strong> dan perlu dirakit ulang oleh operator</li>
                  <li>Dokumen WO asli <strong>tidak berubah</strong> — dokumen baru akan dibuat dengan suffix
                    inkremental <strong>-PU1, -PU2, dst</strong></li>
                  <li>Pengajuan ini membutuhkan persetujuan <strong>Admin</strong> terlebih dahulu</li>
                </ul>
              </div>
            </div>

            <!-- Set Info Summary -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-1.5">
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Set #</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.set_number
                  }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">WO / Tray</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.tray_code
                  }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-500 font-semibold">Operator Sebelumnya</span>
                <span class="font-black text-slate-900">{{ (selectedHoldItem || selectedAssemblyItem)?.pic_assembly ||
                  '-' }}</span>
              </div>
            </div>

            <!-- Pilih Operator Perakitan Ulang -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1.5">
                <i class="fa-solid fa-user-gear mr-1 text-amber-600"></i>
                Operator yang Akan Merakit Ulang:
              </label>
              <div class="relative">
                <select v-model="reregOperator"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-600 appearance-none cursor-pointer">
                  <option value="">— Pilih Operator —</option>
                  <option v-for="op in operatorUsersList" :key="op.username || op.id"
                    :value="op.full_name || op.username">
                    {{ op.full_name || op.username }}
                  </option>
                </select>
                <i
                  class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
              </div>
              <p class="text-[10px] text-slate-400 mt-1">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Kosongkan untuk menggunakan operator yang sama ({{ (selectedHoldItem ||
                selectedAssemblyItem)?.pic_assembly || 'sebelumnya' }})
              </p>
            </div>

            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1">
                Catatan / Alasan Perakitan Ulang:
              </label>
              <textarea v-model="reregisterReason" rows="2"
                placeholder="Tuliskan alasan kenapa set ini perlu dirakit ulang..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-600"></textarea>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="showReregisterSubModal = false"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
              Batal
            </button>
            <button type="button" @click="submitReregisterSetAction" :disabled="isSubmittingReregisterSetAction"
              class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/20 transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50">
              <i :class="isSubmittingReregisterSetAction ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-hammer'"></i>
              <span>Ajukan Perakitan Ulang ke Admin</span>
            </button>
          </div>
        </div>
      </div>

      <!-- ================= SUB-MODAL 2: GANTI PART BERMASALAH ================= -->
      <div v-if="showReplacePartModal" @click.self="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
        class="modal-backdrop-full modal-backdrop-nested flex items-start justify-center p-3 sm:p-4 overflow-y-auto no-print pt-6 sm:pt-12 pb-16">
        <div
          class="bg-white border border-slate-200 rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 my-auto sm:my-0 animate-fadeIn">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
              <div
                class="w-9 h-9 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-sm font-bold">
                <i class="fa-solid fa-wrench"></i>
              </div>
              <div>
                <h3 class="font-black text-sm text-slate-900">Ganti Part Bermasalah</h3>
                <p class="text-[11px] text-slate-500 font-medium">Ganti komponen rusak dengan part baru dari Master Stok
                </p>
              </div>
            </div>
            <button type="button" @click="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
              class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg transition cursor-pointer">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="space-y-3.5 text-xs">
            <!-- 1. Komponen yang Terpasang Saat Ini & Status -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="block text-[10.5px] font-bold uppercase text-slate-700 flex items-center gap-1.5">
                  <span>1. Komponen yang terpasang saat ini:</span>
                </label>
              </div>

              <!-- Grid status 3 part -->
              <div class="grid grid-cols-3 gap-2">
                <!-- Part 15 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 15 (P9)</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part15_sn
                    || '-' }}</span>
                </div>

                <!-- Part 1 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 1</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part1_sn
                    || '-' }}</span>
                </div>

                <!-- Part 12 -->
                <div class="p-2.5 rounded-xl border text-center transition relative"
                  :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')) ? 'bg-red-50/70 border-red-300 ring-1 ring-red-300' : 'bg-slate-50 border-slate-200'">
                  <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')"
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                  <span v-else
                    class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[8px] bg-emerald-500 text-white font-black leading-none">Normal</span>
                  <span class="block text-[10px] opacity-80 uppercase font-bold text-slate-600">Part 12</span>
                  <span class="font-mono text-xs truncate block font-bold text-slate-900">{{ selectedHoldItem?.part12_sn
                    || '-' }}</span>
                </div>
              </div>

              <!-- Info Alasan Rework / Hold / Scrap -->
              <div
                class="mt-2.5 p-3 rounded-xl border bg-amber-50/80 border-amber-200 text-amber-900 space-y-1.5 shadow-xs">
                <div class="flex items-center justify-between gap-2">
                  <div
                    class="flex items-center gap-1.5 font-bold text-[10.5px] uppercase tracking-wider text-amber-900">
                    <i class="fa-solid fa-circle-exclamation text-amber-600"></i>
                    <span>Alasan {{ selectedHoldItem?.assembly_status || selectedHoldItem?.status || 'Bermasalah'
                      }}:</span>
                  </div>
                  <span :class="[
                    (selectedHoldItem?.assembly_status === 'Rework' || selectedHoldItem?.status === 'Rework') ? 'bg-purple-100 text-purple-800 border-purple-200' :
                    (selectedHoldItem?.assembly_status === 'Hold' || selectedHoldItem?.status === 'Hold') ? 'bg-amber-100 text-amber-800 border-amber-200' :
                    'bg-red-100 text-red-800 border-red-200'
                  ]" class="text-[9.5px] font-bold px-2 py-0.5 rounded-md border font-mono uppercase">
                    Status: {{ selectedHoldItem?.assembly_status || selectedHoldItem?.status || 'Hold' }}
                  </span>
                </div>
                <div
                  class="text-xs font-semibold text-slate-800 bg-white/90 border border-amber-200/70 rounded-lg px-3 py-2 leading-relaxed break-words shadow-xs">
                  {{ getItemProblemReason(selectedHoldItem) }}
                </div>
              </div>
            </div>

            <!-- 2. Status untuk Part Lama di Master Stok -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-2">
                2. Status Part Lama yang Dilepas ke Master Stok:
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="replaceOldStatus = 'Hold'"
                  :class="replaceOldStatus === 'Hold' ? 'bg-amber-600 text-white ring-2 ring-amber-400 font-black shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold'"
                  class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-2 min-h-[40px] cursor-pointer active:scale-95 select-none">
                  <i class="fa-solid fa-hand text-sm"></i>
                  <span>Status: Hold</span>
                </button>
                <button type="button" @click="replaceOldStatus = 'Scrap'"
                  :class="replaceOldStatus === 'Scrap' ? 'bg-red-600 text-white ring-2 ring-red-400 font-black shadow-md' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 font-bold'"
                  class="py-2.5 px-3 rounded-xl text-xs transition flex items-center justify-center gap-2 min-h-[40px] cursor-pointer active:scale-95 select-none">
                  <i class="fa-solid fa-trash-can text-sm"></i>
                  <span>Status: Scrap</span>
                </button>
              </div>
              <p class="text-[10px] text-slate-500 mt-1.5 italic">
                * Part lama yang diganti akan masuk ke Master Database dan antrean verifikasi untuk didaftarkan ulang
                secara satuan.
              </p>
            </div>

            <!-- 3. PILIH PART BARU PENGGANTI (HANYA PART BERMASALAH) -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <label class="block text-[10.5px] font-bold uppercase text-slate-700">
                  3. Pilih Part Baru Pengganti dari Stok Gudang (Bebas pilih 1 s/d 3 part):
                </label>
                <span class="text-[10px] font-bold"
                  :class="totalPartsToReplace > 0 ? 'text-blue-700' : 'text-slate-500'">
                  {{ totalPartsToReplace }} part dipilih untuk diganti
                </span>
              </div>

              <!-- ================= DROPDOWN PART 15 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 15 (P9)</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('15')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part15_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['15']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['15']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('15')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['15'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['15'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['15']" class="text-blue-700 font-black">
                        {{ replaceSelections['15'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 15)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['15']" @click.stop="clearReplaceSNForPart('15')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['15'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['15']" @click="replaceDropdownOpen['15'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['15']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['15']" placeholder="Cari SN Part 15..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('15'); replaceDropdownOpen['15'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part15_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['15']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('15').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 15 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('15')" :key="p.sn"
                        @click="selectReplaceSNForPart('15', p.sn)"
                        :class="replaceSelections['15'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['15'] === p.sn"
                            class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================= DROPDOWN PART 1 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 1</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('1')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part1_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['1']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['1']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('1')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['1'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['1'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['1']" class="text-blue-700 font-black">
                        {{ replaceSelections['1'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 1)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['1']" @click.stop="clearReplaceSNForPart('1')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['1'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['1']" @click="replaceDropdownOpen['1'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['1']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['1']" placeholder="Cari SN Part 1..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('1'); replaceDropdownOpen['1'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part1_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['1']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('1').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 1 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('1')" :key="p.sn"
                        @click="selectReplaceSNForPart('1', p.sn)"
                        :class="replaceSelections['1'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['1'] === p.sn" class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================= DROPDOWN PART 12 ================= -->
              <div class="p-3 rounded-xl border space-y-1.5"
                :class="(!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')) ? 'border-red-200 bg-red-50/20 ring-1 ring-red-200' : 'border-slate-200 bg-slate-50/30'">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">Part 12</span>
                    <span v-if="!isFunctionalHoldItem(selectedHoldItem) && isProblemPart('12')"
                      class="px-1.5 py-0.5 rounded text-[8px] bg-red-500 text-white font-black leading-none">Bermasalah</span>
                    <span class="text-[10.5px] text-slate-500 font-mono">Lama: <strong>{{ selectedHoldItem?.part12_sn ||
                        '-' }}</strong></span>
                  </div>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">
                    <span v-if="isLoadingPartsByType['12']"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Memuat...</span>
                    <span v-else>{{ availableReplacePartsByType['12']?.length || 0 }} tersedia</span>
                  </span>
                </div>

                <div class="relative">
                  <button type="button" @click="toggleReplaceDropdown('12')"
                    class="w-full bg-white border border-slate-300 hover:bg-slate-50 focus:bg-white rounded-xl px-3 py-2 text-xs font-mono font-bold text-left transition flex items-center justify-between min-h-[40px] cursor-pointer"
                    :class="replaceDropdownOpen['12'] ? 'border-blue-600 ring-2 ring-blue-500/20' : (replaceSelections['12'] ? 'border-blue-500 bg-blue-50/30' : '')">
                    <div class="flex items-center gap-2 truncate">
                      <i class="fa-solid fa-barcode text-slate-400 text-xs"></i>
                      <span v-if="replaceSelections['12']" class="text-blue-700 font-black">
                        {{ replaceSelections['12'] }} <span
                          class="text-[10.5px] font-sans font-bold text-emerald-600">(Pengganti Part 12)</span>
                      </span>
                      <span v-else class="text-slate-400 font-sans font-medium">
                        Pilih part pengganti
                      </span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-400">
                      <span v-if="replaceSelections['12']" @click.stop="clearReplaceSNForPart('12')"
                        class="hover:text-red-500 p-0.5 rounded transition cursor-pointer" title="Hapus pilihan">
                        <i class="fa-solid fa-xmark text-xs"></i>
                      </span>
                      <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="{ 'rotate-180': replaceDropdownOpen['12'] }"></i>
                    </div>
                  </button>

                  <!-- Backdrop to close -->
                  <div v-if="replaceDropdownOpen['12']" @click="replaceDropdownOpen['12'] = false"
                    class="fixed inset-0 z-40"></div>

                  <!-- Dropdown Popover -->
                  <div v-if="replaceDropdownOpen['12']"
                    class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-2 max-h-56 flex flex-col animate-fadeIn">
                    <div class="relative">
                      <input type="text" v-model="replaceSearchQueries['12']" placeholder="Cari SN Part 12..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs font-mono text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-600 focus:bg-white"
                        @click.stop>
                      <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-[10px] text-slate-400"></i>
                    </div>
                    <div class="overflow-y-auto max-h-40 divide-y divide-slate-100 pr-1 space-y-0.5">
                      <div @click="clearReplaceSNForPart('12'); replaceDropdownOpen['12'] = false"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100 cursor-pointer transition flex items-center justify-between">
                        <span>Tetap {{ selectedHoldItem?.part12_sn || '-' }}</span>
                        <span class="text-[10.5px] text-slate-400 font-sans font-normal">(Tidak diganti)</span>
                      </div>
                      <div v-if="isLoadingPartsByType['12']"
                        class="py-3 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat stok...
                      </div>
                      <div v-else-if="getFilteredPartsForType('12').length === 0"
                        class="py-3 text-center text-xs text-slate-400">
                        Tidak ada stok Part 12 tersedia
                      </div>
                      <div v-for="p in getFilteredPartsForType('12')" :key="p.sn"
                        @click="selectReplaceSNForPart('12', p.sn)"
                        :class="replaceSelections['12'] === p.sn ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="px-3 py-1.5 rounded-lg text-xs font-mono flex items-center justify-between cursor-pointer transition">
                        <span class="flex items-center gap-2">
                          <i v-if="replaceSelections['12'] === p.sn"
                            class="fa-solid fa-check text-blue-600 text-xs"></i>
                          <span>{{ p.sn }}</span>
                        </span>
                        <span
                          class="text-[9.5px] font-sans px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">
                          {{ p.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Info Komponen Normal Tidak Perlu Diganti -->
              <div v-if="hasNonProblematicParts"
                class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xs"></i>
                <span class="text-[11px] font-medium">Komponen berstatus <strong>Normal</strong> tidak perlu diganti dan
                  otomatis dipertahankan.</span>
              </div>
            </div>

            <!-- 4. Alasan Penggantian -->
            <div>
              <label class="block text-[10.5px] font-bold uppercase text-slate-700 mb-1">
                4. Alasan Penggantian Part:
              </label>
              <input type="text" v-model="replaceReason"
                placeholder="Contoh: Part retak / gagal uji tahap fungsional..."
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-blue-600 min-h-[38px]">
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="safeCloseModal(closeReplacePartModal, totalPartsToReplace > 0)"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
              Batal
            </button>
            <button type="button" @click="submitReplacePartAction"
              :disabled="isSubmittingReplacePartAction || totalPartsToReplace === 0"
              class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50">
              <i :class="isSubmittingReplacePartAction ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-check'"></i>
              <span v-if="totalPartsToReplace > 0">Pasang {{ totalPartsToReplace }} Part Baru &amp; Simpan</span>
              <span v-else>Pilih Part Pengganti</span>
            </button>
          </div>
        </div>
      </div>


@endverbatim
