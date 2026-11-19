@verbatim
        <!-- ================= SUB-TAB: BUAT LABEL PACKING LIST SAJA (STANDALONE) ================= -->
        <div v-if="(doSubTab === 'packing_list' || doSubTab === 'label_pl_only') && packingListInnerTab === 'form'" class="w-full space-y-4 sm:space-y-5 animate-fadeIn">
          
          <!-- Inner Tab Switcher: Form vs Riwayat Packing List -->
          <div class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-xs">
                <i class="fa-solid fa-boxes-packing text-sm"></i>
              </span>
              <div>
                <h3 class="font-black text-sm text-slate-900 leading-tight">Packing List</h3>
                <p class="text-[11px] text-slate-500">Penerbitan label kemasan dan alokasi nomor seri unit</p>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button type="button" @click="packingListInnerTab = 'form'; doSubTab = 'packing_list'"
                :class="packingListInnerTab === 'form' ? 'bg-emerald-600 text-white font-black shadow-md shadow-emerald-600/25 ring-2 ring-emerald-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-file-circle-plus"></i>
                <span>Buat Packing List</span>
              </button>
              <button type="button" @click="packingListInnerTab = 'history'; doSubTab = 'packing_list'; loadStandaloneLabels()"
                :class="packingListInnerTab === 'history' ? 'bg-emerald-600 text-white font-black shadow-md shadow-emerald-600/25 ring-2 ring-emerald-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Riwayat Packing List ({{ standaloneLabelsList.length }})</span>
              </button>
            </div>
          </div>

          <!-- PILIH JENIS PACKING LIST -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-xs font-black flex items-center justify-center">A</span>
                <h4 class="font-black text-sm text-slate-900">Pilih Jenis Packing List</h4>
              </div>
              <span v-if="plMode" class="text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-[9px] text-slate-500"></i> Mode Terkunci ({{ plMode === 'realtime' ? 'Realtime' : 'Backdate' }})
              </span>
              <span v-else class="text-[11px] font-bold text-slate-500 uppercase">Waktu &amp; Pengiriman</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <!-- Mode 1: Realtime -->
              <div @click="selectPlMode('realtime')"
                :class="plMode === 'realtime' 
                  ? 'border-emerald-600 bg-emerald-50/70 ring-2 ring-emerald-500/20 shadow-xs cursor-default' 
                  : (plMode ? 'opacity-40 border-slate-200 bg-slate-50 cursor-not-allowed pointer-events-none' : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer')"
                class="p-4 rounded-xl border-2 text-left transition select-none space-y-1.5">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-emerald-600 text-sm"></i>
                    <span>Realtime</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span v-if="plMode === 'realtime'" class="text-[9.5px] font-bold text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                      <i class="fa-solid fa-lock text-[8px]"></i> Terkunci
                    </span>
                  </div>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                  Tanggal hari ini dan penomoran dokumen dibuat otomatis oleh sistem.
                </p>
              </div>

              <!-- Mode 2: Backdate -->
              <div @click="selectPlMode('backdate')"
                :class="plMode === 'backdate' 
                  ? 'border-amber-600 bg-amber-50/70 ring-2 ring-amber-500/20 shadow-xs cursor-default' 
                  : (plMode ? 'opacity-40 border-slate-200 bg-slate-50 cursor-not-allowed pointer-events-none' : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer')"
                class="p-4 rounded-xl border-2 text-left transition select-none space-y-1.5">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                    <span>Backdate</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span v-if="plMode === 'backdate'" class="text-[9.5px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                      <i class="fa-solid fa-lock text-[8px]"></i> Terkunci
                    </span>
                  </div>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                  Tanggal pengiriman lampau dengan penyesuaian batch dan nomor pengiriman.
                </p>
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
              <button type="button" @click="packingListInnerTab = 'history'; doSubTab = 'packing_list'; loadStandaloneLabels()"
                class="text-xs font-bold text-emerald-900 bg-white hover:bg-emerald-100 border border-emerald-300 px-3 py-1.5 rounded-xl transition cursor-pointer shadow-2xs">
                Lihat di Riwayat
              </button>
              <button type="button" @click="lastSavedPLInfo = null"
                class="text-emerald-600 hover:text-emerald-900 p-1.5 rounded-lg cursor-pointer transition">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>
          </div>

          <!-- STEPPER INDIKATOR ALUR PACKING LIST (PL) - TERKUNCI & BERTAHAP -->
          <div v-show="plMode" class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-xs select-none">
            <div class="grid grid-cols-2 gap-2 text-center text-xs">
              <div class="py-2.5 px-3 rounded-xl border flex items-center justify-center gap-2 font-black transition cursor-default"
                :class="plActiveStep === 1 ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs' : 'border-emerald-300 bg-emerald-50 text-emerald-800'">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[11px]" :class="plActiveStep === 1 ? 'bg-white/20 text-white' : 'bg-emerald-600 text-white'">
                  <i v-if="plActiveStep > 1" class="fa-solid fa-check text-[9px]"></i>
                  <span v-else>1</span>
                </span>
                <span>1. Jenis Kemasan &amp; Parameter</span>
              </div>

              <div class="py-2.5 px-3 rounded-xl border flex items-center justify-center gap-2 font-black transition cursor-default"
                :class="plActiveStep === 2 ? 'border-emerald-600 bg-emerald-600 text-white shadow-xs' : 'border-slate-200 bg-slate-50/80 text-slate-400'">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[11px]" :class="plActiveStep === 2 ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-500'">
                  <i v-if="plActiveStep === 1" class="fa-solid fa-lock text-[9px]"></i>
                  <span v-else>2</span>
                </span>
                <span>2. Alokasi Serial Number</span>
              </div>
            </div>
          </div>

          <!-- KARTU 1: PENGATURAN KEMASAN & PARAMETER PENGIRIMAN -->
          <div v-show="plMode && plActiveStep === 1" class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none cursor-default">
              <div class="flex items-center gap-2">
                <span
                  class="w-6 h-6 rounded-lg text-xs font-black flex items-center justify-center transition bg-emerald-600 text-white">
                  1
                </span>
                <h4 class="font-black text-sm text-slate-900">Jenis Kemasan &amp; Parameter Pengiriman</h4>
              </div>
              <div class="flex items-center gap-2">
                <span
                  class="text-[11px] font-mono font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200">
                  Total: {{ (labelForm.package_type === 'crate' ? labelForm.package_count * 20 : labelForm.package_count * 10) }} Unit
                </span>
              </div>
            </div>

            <!-- Body Kartu 1 Accordion Transition -->
            <div class="smooth-accordion" :class="{ 'is-open': isPlCardOpen(1) }">
              <div class="accordion-inner space-y-4 pt-1">

            <!-- Pilihan Jenis Kemasan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <div class="text-[11px] text-slate-500 mt-1">2 Dus isi 10 SN per Peti</div>
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
                <div class="text-[11px] text-slate-500 mt-1">1 Dus isi 10 SN langsung</div>
              </button>
            </div>

            <!-- Input Tanggal, Nomor Batch, Nomor Pengiriman & Jumlah -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 pt-1">
              <!-- Tanggal Packing List (Realtime Lock vs Backdate Picker) -->
              <div class="space-y-1.5">
                <div class="h-5 flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-700 flex items-center gap-1">
                    <span>Tanggal Pengiriman</span>
                    <span class="text-red-500 font-black">*</span>
                  </label>
                  <span v-if="plMode === 'realtime'" class="text-[9.5px] font-bold bg-emerald-100 text-emerald-800 px-1.5 py-0.2 rounded font-sans">Realtime</span>
                  <span v-else class="text-[9.5px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.2 rounded font-sans">Backdate</span>
                </div>
                <input v-if="plMode === 'backdate'" type="date" v-model="labelForm.date" @input="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                  @change="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                  title="Pilih tanggal Packing List (Backdate)">
                <input v-else type="text" :value="labelForm.date" readonly disabled
                  class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                  title="Tanggal realtime otomatis hari ini">
              </div>

              <!-- Nomor Batch Pengiriman -->
              <div class="space-y-1.5">
                <div class="h-5 flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-700">Nomor Batch</label>
                </div>
                <div v-if="plMode === 'backdate'">
                  <input type="number" min="1" v-model.number="labelForm.batch_number"
                    @input="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                    @change="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                    title="Nomor Batch (Backdate dapat disesuaikan)">
                </div>
                <div v-else>
                  <input type="text" :value="labelForm.batch_number || 1" readonly disabled
                    class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                    title="Nomor Batch otomatis dari Admin CMS">
                </div>
              </div>

              <!-- Nomor Pengiriman -->
              <div class="space-y-1.5">
                <div class="h-5 flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-700">Nomor Pengiriman</label>
                </div>
                <div v-if="plMode === 'backdate'">
                  <input type="number" min="1" v-model.number="plDeliverySeq"
                    @input="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                    @change="updateGeneratedPLNumber(); syncLabelPackagingStructure()"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                    title="Nomor Pengiriman (Backdate dapat disesuaikan)">
                </div>
                <div v-else>
                  <input type="text" :value="plDeliverySeq" readonly disabled
                    class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                    title="Nomor Pengiriman otomatis">
                </div>
              </div>

              <!-- Jumlah Kemasan (Peti / Dus) -->
              <div class="space-y-1.5">
                <div class="h-5 flex items-center">
                  <label class="text-xs font-bold text-slate-700">
                    Banyak {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} <span class="text-red-500 font-black">*</span>
                  </label>
                </div>
                <input type="number" v-model.number="labelForm.package_count" @input="syncLabelPackagingStructure"
                  min="1" max="100"
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs">
              </div>

              <!-- Nama Produk (Terkunci Nanggala P9) -->
              <div class="space-y-1.5">
                <div class="h-5 flex items-center">
                  <label class="text-xs font-bold text-slate-700">Nama Produk</label>
                </div>
                <input type="text" v-model="labelForm.product_name" readonly disabled
                  value="Nanggala P9"
                  class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 h-[42px] min-h-[42px] cursor-not-allowed shadow-2xs">
              </div>
            </div>

            <!-- KOTAK GENERATE ID KEMASAN (ID PETI / ID DUS) -->
            <div class="mt-3 pt-3 border-t border-slate-100 space-y-3">
              <div class="bg-slate-50/80 border border-slate-300/80 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                  <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider">
                    ID Kemasan yang Dihasilkan ({{ labelForm.package_type === 'crate' ? 'ID Peti' : 'ID Dus' }}):
                  </span>
                  <div class="text-base sm:text-lg font-mono font-black text-slate-900 flex items-center gap-2">
                    <span class="text-emerald-900">{{ generatedPackagingIdPreview }}</span>
                    <button type="button" @click="syncLabelPackagingStructure()" class="text-xs text-slate-400 hover:text-emerald-600 transition cursor-pointer p-1" :title="'Perbarui ID ' + (labelForm.package_type === 'crate' ? 'Peti' : 'Dus')">
                      <i class="fa-solid fa-arrows-rotate" :class="{'fa-spin': isCheckingPlUnique}"></i>
                    </button>
                  </div>
                  <p class="text-[11px] text-slate-500">
                    ID {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} dibuat otomatis berdasarkan format standar (YYMM-Batch-Pengiriman+Urutan) untuk ditempel pada kemasan fisik dan dikonsumsi ke Surat Jalan.
                  </p>
                </div>

                <!-- Status Keunikan ID Kemasan -->
                <div class="shrink-0">
                  <div v-if="isCheckingPlUnique" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold">
                    <i class="fa-solid fa-spinner fa-spin text-slate-500"></i>
                    <span>Memeriksa keunikan...</span>
                  </div>
                  <div v-else-if="!plDuplicateWarning && generatedPackagingIdPreview !== '-'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs font-bold">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>ID {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} Tersedia &amp; Unik</span>
                  </div>
                </div>
              </div>

              <!-- ALERT DUPLICATE ID KEMASAN -->
              <div v-if="plDuplicateWarning" class="bg-red-50 border-2 border-red-500 rounded-2xl p-4 flex items-start gap-3 shadow-md animate-shake">
                <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
                  <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="space-y-1">
                  <h4 class="font-black text-sm text-red-950">Peringatan: ID Kemasan Duplikat!</h4>
                  <p class="text-xs font-black text-red-700 leading-relaxed">
                    {{ plDuplicateMessage || 'ID Kemasan sudah pernah dibuat silahkan cek terlebih dahulu.' }}
                  </p>
                  <p class="text-[11px] text-red-600">
                    Silakan ubah nomor pengiriman atau nomor batch agar ID {{ labelForm.package_type === 'crate' ? 'Peti' : 'Dus' }} unik.
                  </p>
                </div>
              </div>
            </div>

            <!-- Tombol Kembali & Lanjut ke Kartu 2 (Alokasi SN) -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
              <button type="button" @click="resetLabelForm()"
                class="border border-slate-300 hover:bg-slate-100 active:scale-95 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer min-h-[40px] flex items-center gap-2 shadow-2xs">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali (Pilih Ulang Mode)</span>
              </button>
              <button type="button" @click="goToPlStep(2)"
                class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs px-5 py-2.5 rounded-xl shadow-md shadow-emerald-600/25 flex items-center gap-2 transition cursor-pointer min-h-[40px] shrink-0">
                <span>Lanjut ke Alokasi Serial Number (Kartu 2)</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
              </button>
            </div>
              </div>
            </div>
          </div>

          <!-- KARTU 2: PENGISIAN SERIAL NUMBER -->
          <div v-show="plMode && plActiveStep === 2" class="relative z-30 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3 select-none cursor-default">
              <div class="flex items-center gap-2">
                <span
                  class="w-6 h-6 rounded-lg text-xs font-black flex items-center justify-center transition bg-emerald-600 text-white">
                  2
                </span>
                <h4 class="font-black text-sm text-slate-900">Alokasi Serial Number Unit</h4>
              </div>
              <div class="flex items-center gap-2">
                <button v-if="isPlCardOpen(2)" type="button" @click.stop="openLabelBulkModal"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer min-h-[34px]"
                  title="Input data Serial Number secara bulking / tempel massal">
                  <i class="fa-solid fa-list-check text-xs"></i>
                  <span>Input Bulking SN</span>
                </button>
              </div>
            </div>

            <!-- Body Kartu 2 Accordion Transition -->
            <div class="smooth-accordion" :class="{ 'is-open': isPlCardOpen(2) }">
              <div class="accordion-inner space-y-4 pt-1">

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
                  class="bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 space-y-3 relative z-20">
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
                        <span class="w-6 text-[10px] font-bold text-slate-400 font-mono text-right shrink-0" v-text="(sIdx + 1) + '.'"></span>
                        <div class="relative w-full overflow-visible"
                          :class="isLabelSlotDropdownOpen(activeLabelCrateIndex, bIdx, sIdx) ? 'z-[9999]' : ''">
                          
                          <!-- Input & Action Controls -->
                          <div class="relative flex items-center">
                            <input type="text" v-model="item.serial_number"
                              @focus="openLabelSlotDropdown(activeLabelCrateIndex, bIdx, sIdx)"
                              @input="onLabelSlotInput(item, activeLabelCrateIndex, bIdx, sIdx)"
                              @keydown="handleLabelSlotKeyDown($event, item, activeLabelCrateIndex, bIdx, sIdx)"
                              @blur="closeLabelSlotDropdown()"
                              autocomplete="off"
                              autocorrect="off"
                              autocapitalize="off"
                              spellcheck="false"
                              placeholder="Pilih / ketik SN..."
                              :class="{
                                'border-rose-500 bg-rose-50/70 text-rose-900 ring-2 ring-rose-300': getLabelSlotStatus(item) === 'duplicate' || getLabelSlotStatus(item) === 'invalid',
                                'border-emerald-500 bg-emerald-50/30 text-emerald-900 font-black': getLabelSlotStatus(item) === 'valid',
                                'border-slate-300 bg-white text-slate-800 hover:border-slate-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100': getLabelSlotStatus(item) === 'empty'
                              }"
                              class="w-full border rounded-xl pl-3 pr-14 py-1.5 text-xs font-mono font-bold focus:outline-none transition min-h-[36px] shadow-2xs">

                            <!-- Action Icons (Clear + Dropdown Chevron) -->
                            <div class="absolute right-2 inset-y-0 flex items-center gap-1">
                              <button v-if="item.serial_number" type="button"
                                @mousedown.prevent="clearLabelSlotSN(item)"
                                @click="clearLabelSlotSN(item)"
                                class="text-slate-400 hover:text-rose-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-rose-50"
                                title="Hapus Serial Number">
                                <i class="fa-solid fa-xmark text-xs"></i>
                              </button>
                              <button type="button"
                                @mousedown.prevent="toggleLabelSlotDropdown(activeLabelCrateIndex, bIdx, sIdx)"
                                class="text-slate-400 hover:text-emerald-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-slate-100"
                                title="Buka daftar SN tersedia">
                                <i class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200"
                                  :class="isLabelSlotDropdownOpen(activeLabelCrateIndex, bIdx, sIdx) ? 'rotate-180 text-emerald-600' : ''"></i>
                              </button>
                            </div>
                          </div>

                          <!-- Elegant Custom Floating Dropdown Menu -->
                          <div
                            v-if="isLabelSlotDropdownOpen(activeLabelCrateIndex, bIdx, sIdx)"
                            @mousedown.prevent
                            class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-[99999] max-h-[220px] overflow-y-auto divide-y divide-slate-100 py-1 ring-4 ring-emerald-500/15"
                            style="-webkit-overflow-scrolling: touch; touch-action: pan-y;">

                            <!-- Sticky Header -->
                            <div class="px-3 py-1.5 text-[10px] font-bold text-emerald-800 uppercase tracking-wider flex justify-between items-center bg-emerald-50 sticky top-0 z-10 border-b border-emerald-100">
                              <span class="flex items-center gap-1">
                                <i class="fa-solid fa-list-check text-emerald-600"></i>
                                <span>Stok Tersedia ({{ getFilteredSNsForLabelSlot(item.serial_number).length }})</span>
                              </span>
                              <span class="text-[9px] text-emerald-600 font-sans font-normal">Klik untuk memilih</span>
                            </div>

                            <!-- List Items -->
                            <div v-if="getFilteredSNsForLabelSlot(item.serial_number).length > 0">
                              <button v-for="sn in getFilteredSNsForLabelSlot(item.serial_number)" :key="sn" type="button"
                                @mousedown.prevent="selectLabelSlotSN(item, sn)"
                                class="w-full text-left px-3 py-2 hover:bg-emerald-50 hover:text-emerald-900 text-xs font-mono font-bold flex items-center justify-between text-slate-800 transition cursor-pointer select-none">
                                <span>{{ sn }}</span>
                                <span class="text-[9px] text-emerald-700 font-sans font-bold bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-full pointer-events-none">
                                  TERSEDIA
                                </span>
                              </button>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="px-3 py-3 text-center text-xs text-slate-400 font-medium">
                              <i class="fa-solid fa-circle-exclamation text-amber-500 mr-1"></i>
                              <span>Tidak ada SN yang cocok / tersedia</span>
                            </div>

                          </div>
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
          </div>

          <!-- KARTU 3: CATATAN & AKSI SIMPAN / CETAK -->
          <div v-show="plMode && plActiveStep === 2" class="relative z-10 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Catatan Label (Opsional)</label>
              <input type="text" v-model="labelForm.notes" placeholder="Contoh: Pengiriman batch alutsista tahap 1"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white min-h-[40px]">
            </div>

            <!-- Status Kelengkapan SN & Tombol Reset Form -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-3.5 py-2.5 bg-slate-50/80 border border-slate-200/80 rounded-xl">
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg border whitespace-nowrap"
                  :class="isLabelFormComplete ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-50 text-amber-800 border-amber-300'">
                  <i class="fa-solid" :class="isLabelFormComplete ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-amber-600'"></i>
                  <span>{{ isLabelFormComplete ? 'Semua slot SN terisi lengkap (' + getLabelFormFilledCount() + '/' + getLabelFormRequiredCount() + ')' : 'Slot SN belum lengkap: ' + getLabelFormFilledCount() + '/' + getLabelFormRequiredCount() + ' terisi' }}</span>
                </span>
              </div>
              <button type="button" @click="confirmResetLabelForm"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition cursor-pointer self-start sm:self-auto whitespace-nowrap">
                <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i>
                <span>Reset Form</span>
              </button>
            </div>

            <!-- Alert Peringatan Serial Number Tidak Tersedia / Belum ACC -->
            <div v-if="hasInvalidLabelSNs"
              class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2">
              <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
              <span>Terdapat Serial Number yang tidak valid atau belum lolos penerimaan ACC gudang. Tombol simpan dan cetak dinonaktifkan.</span>
            </div>

            <!-- Bar Navigasi & Aksi Utama (Satu Baris Sejajar & Simetris) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
              <!-- Kiri: Tombol Kembali -->
              <button type="button" @click="goToPlStep(1)"
                class="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl border border-slate-300 flex items-center justify-center gap-2 transition cursor-pointer min-h-[42px] shadow-2xs shrink-0">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali ke Parameter Kemasan</span>
              </button>

              <!-- Kanan: Tombol Aksi Simpan & Cetak -->
              <div class="flex flex-wrap items-center justify-end gap-2.5 w-full sm:w-auto">
                <button type="button" @click="saveStandaloneLabel(null)"
                  :disabled="isSavingStandaloneLabel || !isLabelFormComplete || plDuplicateWarning || hasInvalidLabelSNs"
                  :title="hasInvalidLabelSNs ? 'Terdapat Serial Number yang tidak valid atau belum lolos penerimaan ACC gudang.' : (plDuplicateWarning ? 'Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.' : (!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat disimpan' : 'Simpan label ke database'))"
                  :class="(!isLabelFormComplete || isSavingStandaloneLabel || plDuplicateWarning || hasInvalidLabelSNs) ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed shadow-none' : 'bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 cursor-pointer shadow-2xs active:scale-95'"
                  class="flex-1 sm:flex-initial font-bold text-xs px-4 py-2.5 rounded-xl border flex items-center justify-center gap-2 transition min-h-[42px] whitespace-nowrap">
                  <i class="fa-solid fa-floppy-disk" :class="{'fa-spin': isSavingStandaloneLabel}"></i>
                  <span>Simpan Saja</span>
                </button>

                <button v-if="labelForm.package_type === 'crate'" type="button"
                  @click="saveStandaloneLabel('label_peti')" :disabled="isSavingStandaloneLabel || !isLabelFormComplete || plDuplicateWarning || hasInvalidLabelSNs"
                  :title="hasInvalidLabelSNs ? 'Terdapat Serial Number yang tidak valid atau belum lolos penerimaan ACC gudang.' : (plDuplicateWarning ? (plDuplicateMessage || 'ID Kemasan sudah pernah dibuat silahkan cek terlebih dahulu.') : (!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat dicetak' : 'Simpan dan cetak label peti'))"
                  :class="(!isLabelFormComplete || isSavingStandaloneLabel || plDuplicateWarning || hasInvalidLabelSNs) ? 'bg-slate-200 text-slate-400 border border-transparent cursor-not-allowed shadow-none' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/25 cursor-pointer active:scale-95'"
                  class="flex-1 sm:flex-initial font-bold text-xs px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 transition min-h-[42px] whitespace-nowrap">
                  <i class="fa-solid fa-print"></i>
                  <span>Simpan &amp; Cetak Label Peti</span>
                </button>

                <button v-if="labelForm.package_type === 'box'" type="button"
                  @click="saveStandaloneLabel('label_dus')"
                  :disabled="isSavingStandaloneLabel || !isLabelFormComplete || plDuplicateWarning || hasInvalidLabelSNs"
                  :title="hasInvalidLabelSNs ? 'Terdapat Serial Number yang tidak valid atau belum lolos penerimaan ACC gudang.' : (plDuplicateWarning ? (plDuplicateMessage || 'ID Kemasan sudah pernah dibuat silahkan cek terlebih dahulu.') : (!isLabelFormComplete ? 'Seluruh slot Serial Number harus terisi penuh sebelum dapat dicetak' : 'Simpan dan cetak label dus'))"
                  :class="(!isLabelFormComplete || isSavingStandaloneLabel || plDuplicateWarning || hasInvalidLabelSNs) ? 'bg-slate-200 text-slate-400 border border-transparent cursor-not-allowed shadow-none' : 'bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/25 cursor-pointer active:scale-95'"
                  class="flex-1 sm:flex-initial font-bold text-xs px-5 py-2.5 rounded-xl flex items-center justify-center gap-2 transition min-h-[42px] whitespace-nowrap">
                  <i class="fa-solid fa-print"></i>
                  <span>Simpan &amp; Cetak Label Dus</span>
                </button>
              </div>
            </div>
          </div>
        </div>
        </div>


        <!-- ================= SUB-TAB: RIWAYAT LABEL PACKING LIST (STANDALONE) ================= -->
        <div v-if="(doSubTab === 'packing_list' && packingListInnerTab === 'history') || doSubTab === 'labels_history'" class="space-y-4 animate-fadeIn">
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
              <div>
                <h3 class="font-black text-base text-slate-900 tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-box-archive text-emerald-600"></i>
                  <span>Riwayat Packing List</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                  Daftar seluruh label packing list yang dibuat dan dicetak tanpa Surat Jalan.
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="packingListInnerTab = 'form'; doSubTab = 'packing_list'"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/25 flex items-center gap-1.5 transition cursor-pointer min-h-[40px]">
                  <i class="fa-solid fa-plus text-xs"></i>
                  <span>Buat Packing List</span>
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
              <!-- Pencarian Teks -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari Data:
                </label>
                <div class="relative">
                  <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                  <input type="text" v-model="labelSearchText" placeholder="Cari ID Peti / Dus, SN, petugas..."
                    class="w-full bg-slate-50 border border-slate-300 focus:border-emerald-600 focus:bg-white rounded-xl pl-8 pr-3 py-2 text-xs font-semibold text-slate-800 transition min-h-[38px]">
                </div>
              </div>

              <!-- Filter ID Peti / Dus -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-box-archive mr-1 text-emerald-600"></i> Filter ID Peti / Dus:
                </label>
                <div class="relative">
                  <select v-model="labelDocFilter"
                    class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100 min-h-[38px] cursor-pointer shadow-2xs transition">
                    <option value="">Semua ID Kemasan</option>
                    <option v-for="doc in distinctPLLabelNumbers" :key="doc" :value="doc">{{ doc }}</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-[11px]"></i>
                  </div>
                </div>
              </div>

              <!-- Filter Tipe Kemasan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-boxes-stacked mr-1 text-teal-600"></i> Tipe Kemasan:
                </label>
                <div class="relative">
                  <select v-model="labelPackageFilter"
                    class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100 min-h-[38px] cursor-pointer shadow-2xs transition">
                    <option value="">Semua Kemasan</option>
                    <option value="crate">Per Peti (20 Unit)</option>
                    <option value="box">Per Dus (10 Unit)</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-[11px]"></i>
                  </div>
                </div>
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
                    <th class="py-3 px-3.5">ID Peti / Dus</th>
                    <th class="py-3 px-3.5">Jenis Kemasan</th>
                    <th class="py-3 px-3.5">Kemasan &amp; Unit</th>
                    <th class="py-3 px-3.5">Status Konsumsi</th>
                    <th class="py-3 px-3.5">Tanggal</th>
                    <th class="py-3 px-3.5">Petugas</th>
                    <th class="py-3 px-3.5 text-center">Aksi Cetak</th>
                    <th class="py-3 px-3.5 text-center">Jenis PL</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800">
                  <tr v-for="(lbl, idx) in pagedStandaloneLabelsList" :key="lbl.id"
                    class="hover:bg-slate-50/80 transition">
                    <td class="py-3 px-3.5 text-center font-mono text-slate-400 font-bold">{{
                      (pgState.labelsHistory.page - 1) * pgState.labelsHistory.size + idx + 1 }}</td>
                    
                    <!-- ID Peti / Dus -->
                    <td class="py-3 px-3.5 whitespace-nowrap">
                      <div v-if="getLabelPackagingIds(lbl).length > 0" class="inline-flex items-center gap-1.5">
                        <span
                          class="font-mono font-black text-xs px-2.5 py-1 rounded-lg border shadow-2xs tracking-wide"
                          :class="lbl.package_type === 'crate' ? 'bg-emerald-50 text-emerald-900 border-emerald-300' : 'bg-teal-50 text-teal-900 border-teal-300'"
                          :title="'Seluruh ID ' + (lbl.package_type === 'crate' ? 'Peti' : 'Dus') + ' (' + getLabelPackagingIds(lbl).length + '):\n' + getLabelPackagingIds(lbl).join('\n')">
                          {{ formatLabelPackagingRange(lbl) }}
                        </span>
                      </div>
                      <span v-else class="font-mono text-slate-400 text-xs">-</span>
                    </td>

                    <!-- Jenis Kemasan -->
                    <td class="py-3 px-3.5">
                      <span v-if="lbl.package_type === 'crate'"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <i class="fa-solid fa-boxes-stacked text-emerald-600 text-[10px]"></i>
                        <span>Per Peti</span>
                      </span>
                      <span v-else
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                        <i class="fa-solid fa-box text-teal-600 text-[10px]"></i>
                        <span>Per Dus</span>
                      </span>
                    </td>

                    <!-- Kemasan & Unit -->
                    <td class="py-3 px-3.5">
                      <div class="font-bold text-slate-800">
                        {{ lbl.total_containers }} {{ lbl.package_type === 'crate' ? 'Peti' : 'Dus' }}
                      </div>
                      <div class="text-slate-400 text-[11px] font-medium">
                        {{ lbl.total_quantity }} Unit SN
                      </div>
                    </td>

                    <!-- Status Konsumsi -->
                    <td class="py-3 px-3.5">
                      <span v-if="lbl.is_consumed"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs"
                        :title="'Telah dikonsumsi ke Surat Jalan ' + (lbl.consumed_by_do || '')">
                        <i class="fa-solid fa-truck-fast text-[10px]"></i>
                        <span>Dikonsumsi DO ({{ lbl.consumed_by_do || 'SJ Terbit' }})</span>
                      </span>
                      <span v-else
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Tersedia (Siap SJ)</span>
                      </span>
                    </td>

                    <!-- Tanggal -->
                    <td class="py-3 px-3.5 text-slate-600 font-mono text-[11px] whitespace-nowrap">
                      {{ formatLabelDateOnly(lbl.label_date || lbl.created_at) }}
                    </td>

                    <!-- Petugas -->
                    <td class="py-3 px-3.5 text-slate-600 font-medium">
                      {{ lbl.created_by || 'PPIC' }}
                    </td>

                    <!-- Aksi Cetak & Hapus -->
                    <td class="py-3 px-3.5 text-center">
                      <div class="flex items-center justify-center gap-1.5">
                        <button v-if="lbl.package_type === 'crate'" type="button"
                          @click="printStandaloneLabel(lbl, 'label_peti')" title="Cetak Label Peti (100x150mm)"
                          class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 font-bold text-[11px] px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition cursor-pointer shadow-2xs">
                          <i class="fa-solid fa-print text-xs"></i>
                          <span>Label Peti</span>
                        </button>
                        <button v-if="lbl.package_type === 'box'" type="button"
                          @click="printStandaloneLabel(lbl, 'label_dus')" title="Cetak Label Dus (100x150mm)"
                          class="bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-300 font-bold text-[11px] px-2.5 py-1.5 rounded-lg flex items-center gap-1 transition cursor-pointer shadow-2xs">
                          <i class="fa-solid fa-print text-xs"></i>
                          <span>Label Dus</span>
                        </button>
                        <button type="button" @click="deleteStandaloneLabel(lbl.id, getLabelPackagingIds(lbl)[0] || lbl.label_batch_id)"
                          title="Hapus Arsip Label"
                          class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 p-1.5 rounded-lg transition cursor-pointer shadow-2xs">
                          <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                      </div>
                    </td>

                    <!-- Jenis PL (Hanya lingkaran warna tanpa tulisan) -->
                    <td class="py-3 px-3.5 text-center whitespace-nowrap">
                      <div class="inline-flex items-center justify-center"
                        :title="isLabelBackdate(lbl) ? 'Backdate' : 'Realtime'">
                        <span class="w-3.5 h-3.5 rounded-full inline-block shadow-xs transition-transform hover:scale-110"
                          :class="isLabelBackdate(lbl) ? 'bg-amber-400 ring-2 ring-amber-300' : 'bg-emerald-500 ring-2 ring-emerald-300'"></span>
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
                <div class="relative">
                  <select v-model="pgState.labelsHistory.size"
                    class="appearance-none border border-slate-200 hover:border-slate-300 rounded-lg pl-2.5 pr-6 py-1 bg-white text-slate-700 font-bold focus:outline-none cursor-pointer shadow-2xs">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-[9px]"></i>
                  </div>
                </div>
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
                v-if="labelSearchText || labelDocFilter || labelPackageFilter || labelStartDate || labelEndDate"
                type="button" @click="resetLabelFilter"
                class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer inline-flex items-center gap-1.5">
                <i class="fa-solid fa-rotate-left text-xs"></i>
                <span>Reset Filter</span>
              </button>
            </div>
          </div>
        </div>


@endverbatim
