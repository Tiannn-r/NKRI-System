@verbatim
        <!-- ================= SUB-TAB 1: FORM SURAT JALAN & PACKING LIST (STEPPER FORM) ================= -->
        <div v-if="(doSubTab === 'surat_jalan' || doSubTab === 'form') && suratJalanInnerTab === 'form'" class="w-full space-y-4 sm:space-y-5 animate-fadeIn">

          <!-- Inner Tab Switcher: Form vs Riwayat Surat Jalan -->
          <div class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center font-bold shadow-xs">
                <i class="fa-solid fa-file-invoice text-sm"></i>
              </span>
              <div>
                <h3 class="font-black text-sm text-slate-900 leading-tight">Surat Jalan</h3>
                <p class="text-[11px] text-slate-500">Penerbitan dokumen Surat Jalan dan alokasi unit pengiriman</p>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button type="button" @click="suratJalanInnerTab = 'form'; doSubTab = 'surat_jalan'"
                :class="suratJalanInnerTab === 'form' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-file-circle-plus"></i>
                <span>Buat Surat Jalan</span>
              </button>
              <button type="button" @click="suratJalanInnerTab = 'history'; doSubTab = 'surat_jalan'; loadDOList()"
                :class="suratJalanInnerTab === 'history' ? 'bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-bold'"
                class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Riwayat Surat Jalan ({{ doList.length }})</span>
              </button>
            </div>
          </div>

          <!-- PILIH JENIS SURAT JALAN -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-red-600 text-white text-xs font-black flex items-center justify-center">A</span>
                <h4 class="font-black text-sm text-slate-900">Pilih Jenis Surat Jalan</h4>
              </div>
              <span v-if="sjMode" class="text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md flex items-center gap-1.5">
                <i class="fa-solid fa-lock text-[9px] text-slate-500"></i> Mode Terkunci ({{ sjMode === 'realtime' ? 'Realtime' : 'Backdate' }})
              </span>
              <span v-else class="text-[11px] font-bold text-slate-500 uppercase">Mode Penerbitan</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <!-- Mode 1: Realtime -->
              <div @click="selectSjMode('realtime')"
                :class="sjMode === 'realtime' 
                  ? 'border-red-600 bg-red-50/70 ring-2 ring-red-500/20 shadow-xs cursor-default' 
                  : (sjMode ? 'opacity-40 border-slate-200 bg-slate-50 cursor-not-allowed pointer-events-none' : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer')"
                class="p-4 rounded-xl border-2 text-left transition select-none space-y-1.5">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-red-600 text-sm"></i>
                    <span>Realtime</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span v-if="sjMode === 'realtime'" class="text-[9.5px] font-bold text-red-700 bg-red-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                      <i class="fa-solid fa-lock text-[8px]"></i> Terkunci
                    </span>
                  </div>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">
                  Tanggal hari ini dan penomoran dokumen dibuat otomatis oleh sistem.
                </p>
              </div>

              <!-- Mode 2: Backdate -->
              <div @click="selectSjMode('backdate')"
                :class="sjMode === 'backdate' 
                  ? 'border-amber-600 bg-amber-50/70 ring-2 ring-amber-500/20 shadow-xs cursor-default' 
                  : (sjMode ? 'opacity-40 border-slate-200 bg-slate-50 cursor-not-allowed pointer-events-none' : 'border-slate-200 hover:border-slate-300 bg-white cursor-pointer')"
                class="p-4 rounded-xl border-2 text-left transition select-none space-y-1.5">
                <div class="flex items-center justify-between">
                  <div class="font-black text-xs text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                    <span>Backdate</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <span v-if="sjMode === 'backdate'" class="text-[9.5px] font-bold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded flex items-center gap-1">
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

          <!-- STEPPER INDIKATOR ALUR SURAT JALAN (SJ) - TERKUNCI & BERTAHAP -->
          <div v-show="sjMode" class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-xs select-none">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
              <!-- Step 1: Customer -->
              <div class="py-2.5 px-2 rounded-xl border flex items-center justify-center gap-1.5 font-bold transition cursor-default"
                :class="activeFormStep === 'customer' 
                  ? 'border-red-600 bg-red-600 text-white shadow-xs' 
                  : 'border-emerald-300 bg-emerald-50 text-emerald-800'">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                  :class="activeFormStep === 'customer' ? 'bg-white/20 text-white' : 'bg-emerald-600 text-white'">
                  <i v-if="activeFormStep !== 'customer'" class="fa-solid fa-check text-[9px]"></i>
                  <span v-else>1</span>
                </span>
                <span class="truncate">1. Data Penerima</span>
              </div>

              <!-- Step 2: Referensi -->
              <div class="py-2.5 px-2 rounded-xl border flex items-center justify-center gap-1.5 font-bold transition cursor-default"
                :class="activeFormStep === 'reference' 
                  ? 'border-red-600 bg-red-600 text-white shadow-xs' 
                  : (['batch', 'allocation'].includes(activeFormStep) 
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-800' 
                    : 'border-slate-200 bg-slate-50/80 text-slate-400')">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                  :class="activeFormStep === 'reference' 
                    ? 'bg-white/20 text-white' 
                    : (['batch', 'allocation'].includes(activeFormStep) ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500')">
                  <i v-if="['batch', 'allocation'].includes(activeFormStep)" class="fa-solid fa-check text-[9px]"></i>
                  <i v-else class="fa-solid fa-lock text-[8px]"></i>
                </span>
                <span class="truncate">2. Dok. Referensi</span>
              </div>

              <!-- Step 3: Pengemasan -->
              <div class="py-2.5 px-2 rounded-xl border flex items-center justify-center gap-1.5 font-bold transition cursor-default"
                :class="activeFormStep === 'batch' 
                  ? 'border-red-600 bg-red-600 text-white shadow-xs' 
                  : (activeFormStep === 'allocation' 
                    ? 'border-emerald-300 bg-emerald-50 text-emerald-800' 
                    : 'border-slate-200 bg-slate-50/80 text-slate-400')">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                  :class="activeFormStep === 'batch' 
                    ? 'bg-white/20 text-white' 
                    : (activeFormStep === 'allocation' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500')">
                  <i v-if="activeFormStep === 'allocation'" class="fa-solid fa-check text-[9px]"></i>
                  <i v-else class="fa-solid fa-lock text-[8px]"></i>
                </span>
                <span class="truncate">3. Pengemasan</span>
              </div>

              <!-- Step 4: Alokasi SN -->
              <div class="py-2.5 px-2 rounded-xl border flex items-center justify-center gap-1.5 font-bold transition cursor-default"
                :class="activeFormStep === 'allocation' 
                  ? 'border-red-600 bg-red-600 text-white shadow-xs' 
                  : 'border-slate-200 bg-slate-50/80 text-slate-400'">
                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px]"
                  :class="activeFormStep === 'allocation' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-500'">
                  <span v-if="activeFormStep === 'allocation'">4</span>
                  <i v-else class="fa-solid fa-lock text-[8px]"></i>
                </span>
                <span class="truncate">4. Alokasi SN</span>
              </div>
            </div>
          </div>

          <!-- KARTU 1: IDENTITAS PENERIMA (CUSTOMER) -->
          <div v-show="sjMode && activeFormStep === 'customer'" class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none cursor-default">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition bg-red-600 text-white border-red-600">
                  1
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm text-slate-900 leading-tight">Data Penerima</h3>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 1 Accordion Transition -->
            <div class="smooth-accordion" :class="{ 'is-open': isFormCardOpen('customer') }">
              <div class="accordion-inner space-y-4 pt-1">
              <!-- Nama Customer Search Autocomplete Dropdown -->
              <div class="relative space-y-1.5" ref="customerDropdownRef">
                <label class="text-xs font-bold text-slate-700 flex items-center justify-between">
                  <span>Nama Customer <span class="text-red-500">*</span></span>
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
                  @mousedown.prevent
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
                  Alamat Pengiriman <span class="text-red-500">*</span>
                </label>
                <textarea v-model="doForm.customer_address" rows="3"
                  placeholder="Alamat lengkap tujuan pengiriman (termasuk Gedung, Jalan, Kota, Kode Pos)..."
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:bg-white transition leading-relaxed min-h-[95px] resize-y"></textarea>
              </div>

              <!-- No. Telepon & Fax -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center">
                    <label class="text-xs font-bold text-slate-700">No. Telepon Penerima</label>
                  </div>
                  <input type="text" v-model="doForm.customer_phone"
                    @input="onPhoneFaxInput(doForm, 'customer_phone', $event)"
                    @keypress="blockLetterKeys($event)"
                    placeholder="Contoh: (021) 3840889"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs">
                </div>
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center">
                    <label class="text-xs font-bold text-slate-700">No. Fax Penerima</label>
                  </div>
                  <input type="text" v-model="doForm.customer_fax"
                    @input="onPhoneFaxInput(doForm, 'customer_fax', $event)"
                    @keypress="blockLetterKeys($event)"
                    placeholder="Contoh: (021) 3840889"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs">
                </div>
              </div>

              <!-- Aksi Kembali & Lanjut Card 1 -->
              <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                <button type="button" @click="resetDOFormSteps()"
                  class="border border-slate-300 hover:bg-slate-100 active:scale-95 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer min-h-[42px] flex items-center gap-2 shadow-2xs">
                  <i class="fa-solid fa-arrow-left text-xs"></i>
                  <span>Kembali (Pilih Ulang Mode)</span>
                </button>
                <button type="button" @click="goToFormStep('reference')"
                  class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px] flex items-center gap-2">
                  <span>Lanjut ke Dokumen Referensi (Kartu 2)</span>
                  <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

          <!-- KARTU 2: DOKUMEN REFERENSI & PROGRAM -->
          <div v-show="sjMode && activeFormStep === 'reference'" class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none cursor-default">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition bg-red-600 text-white border-red-600">
                  2
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm text-slate-900 leading-tight">
                    Dokumen Referensi &amp; Program
                  </h3>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 2 Accordion Transition -->
            <div class="smooth-accordion" :class="{ 'is-open': isFormCardOpen('reference') }">
              <div class="accordion-inner space-y-4 pt-1">
              <!-- Grid No. SJ, Tanggal & Nomor Pengiriman -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                <!-- No. Surat Jalan (Auto Generated / ID SJ) -->
                <div class="space-y-1.5 sm:col-span-1">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Nomor Surat Jalan (ID SJ) <span
                        class="text-red-500 font-black">*</span></label>
                  </div>
                  <div class="relative">
                    <input type="text" :value="sjGeneratedId || doForm.do_number" readonly
                      placeholder="001/SJ/PPIC/..."
                      class="w-full bg-slate-100/90 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-black text-slate-900 cursor-not-allowed select-none h-[42px] min-h-[42px] pr-10 focus:outline-none shadow-2xs"
                      title="Nomor Surat Jalan otomatis dihasilkan oleh sistem (Auto Generated)">
                    <button type="button" @click="updateGeneratedSJNumber" title="Perbarui Nomor Surat Jalan Otomatis"
                      class="absolute right-2.5 top-2.5 p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer">
                      <i class="fa-solid fa-arrows-rotate text-xs" :class="{'fa-spin': isCheckingSjUnique}"></i>
                    </button>
                  </div>
                </div>

                <!-- Nomor Batch -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Nomor Batch</label>
                  </div>
                  <div v-if="sjMode === 'backdate'">
                    <input type="number" min="1" v-model.number="doForm.batch_number"
                      @input="updateGeneratedSJNumber(); syncPackagingStructure()"
                      @change="updateGeneratedSJNumber(); syncPackagingStructure()"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                      title="Nomor Batch (Backdate dapat disesuaikan)">
                  </div>
                  <div v-else>
                    <input type="text" :value="doForm.batch_number || 1" readonly disabled
                      class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                      title="Nomor Batch otomatis dari Admin CMS">
                  </div>
                </div>

                <!-- Tanggal DO (Realtime Lock vs Backdate Picker) -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700 flex items-center gap-1">
                      <span>Tanggal Pengiriman</span>
                      <span class="text-red-500 font-black">*</span>
                    </label>
                    <span v-if="sjMode === 'realtime'" class="text-[9.5px] font-bold bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-sans">Realtime</span>
                    <span v-else class="text-[9.5px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-sans">Backdate</span>
                  </div>
                  <div class="relative">
                    <input v-if="sjMode === 'backdate'" type="date" v-model="doForm.do_date" @input="updateGeneratedSJNumber(); onDODateChanged()" @change="updateGeneratedSJNumber(); onDODateChanged()"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] shadow-2xs"
                      title="Pilih tanggal pengiriman Surat Jalan (Backdate)">
                    <input v-else type="text" :value="doForm.do_date" readonly disabled
                      class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                      title="Tanggal realtime hari ini">
                  </div>
                </div>

                <!-- ALERT DUPLICATE ID SJ (PERSYARATAN WAJIB USER) -->
                <div v-if="sjDuplicateWarning" class="col-span-full bg-red-50 border-2 border-red-500 rounded-2xl p-4 flex items-start gap-3 shadow-md animate-shake">
                  <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                  </div>
                  <div class="space-y-1">
                    <h4 class="font-black text-sm text-red-950">Peringatan: ID Surat Jalan Duplikat!</h4>
                    <p class="text-xs font-black text-red-700 leading-relaxed">
                      Surat jalan atau packing list sudah dibuat silahkan cek terlebih dahulu.
                    </p>
                    <p class="text-[11px] text-red-600">
                      Nomor Surat Jalan <strong>{{ sjGeneratedId || doForm.do_number }}</strong> sudah terdaftar di sistem. Silakan ubah nomor pengiriman atau tanggal pengiriman agar nomor unik.
                    </p>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
                <!-- Nomor Kontrak -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Nomor Kontrak <span
                        class="text-red-500 font-black">*</span></label>
                  </div>
                  <input type="text" v-model="doForm.contract_number"
                    placeholder="Contoh: TRAK/2509/APBN/XI/2025/KEMHAN"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs">
                </div>

                <!-- Program Pengadaan -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Program Pengadaan <span
                        class="text-red-500 font-black">*</span></label>
                  </div>
                  <input type="text" v-model="doForm.program_name" placeholder="Contoh: PENGADAAN ALPALHANKAM MATSUSSUS"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs">
                </div>
              </div>

              <!-- Pilihan Produk (Terkunci Nanggala P9) -->
              <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                  <i class="fa-solid fa-cubes-stacked text-red-600 text-xs"></i>
                  <span>Jenis Produk <span class="text-red-500">*</span></span>
                </label>
                <div class="relative">
                  <input type="text" v-model="doForm.product_name" readonly disabled
                    value="Nanggala P9"
                    class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-700 h-[42px] min-h-[42px] cursor-not-allowed shadow-2xs">
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                    <i class="fa-solid fa-lock text-xs"></i>
                  </div>
                </div>
              </div>

              <!-- Identitas Pengeluar Dokumen -->
              <div class="bg-slate-50/70 border border-slate-200 rounded-xl p-3.5 space-y-3">
                <div class="text-[11px] font-bold uppercase text-slate-600 flex items-center gap-1.5">
                  <i class="fa-solid fa-user-pen text-red-600"></i>
                  <span>Petugas Pengeluar</span>
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
                  class="border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition cursor-pointer min-h-[42px] flex items-center gap-1.5">
                  <i class="fa-solid fa-arrow-left text-xs"></i>
                  <span>Kembali ke Customer</span>
                </button>
                <button type="button" @click="goToFormStep('batch')"
                  class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px] flex items-center gap-2">
                  <span>Lanjut ke Pengemasan (Kartu 3)</span>
                  <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

          <!-- KARTU 3: KONSEP PENGEMASAN & JUMLAH PENGIRIMAN -->
          <div v-show="sjMode && activeFormStep === 'batch'" class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 select-none cursor-default">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition bg-red-600 text-white border-red-600">
                  3
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm text-slate-900 leading-tight">
                    Konsep Pengemasan &amp; Jumlah Pengiriman
                  </h3>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 3 Accordion Transition -->
            <div class="smooth-accordion" :class="{ 'is-open': isFormCardOpen('batch') }">
              <div class="accordion-inner space-y-4 pt-1">
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
                      <div class="text-[11px] text-slate-500 mt-0.5">2 Dus isi 10 SN = 20 SN per Peti</div>
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
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                <!-- Input Jumlah Kemasan -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">
                      <span v-if="doForm.package_type === 'crate'">Jumlah Peti</span>
                      <span v-else-if="doForm.package_type === 'box'">Jumlah Dus</span>
                      <span v-else>Jumlah Unit (Otomatis)</span>
                      <span class="text-red-500 font-black">*</span>
                    </label>
                  </div>
                  <input type="number" min="1" max="500" v-model.number="doForm.package_count"
                    @input="syncPackagingStructure" :disabled="doForm.package_type === 'unit'"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                    :class="doForm.package_type === 'unit' ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''">
                  <p v-if="doForm.package_type === 'unit'" class="text-[10px] text-emerald-600 font-bold">
                    <i class="fa-solid fa-circle-check mr-1"></i> Bertambah otomatis saat input SN
                  </p>
                </div>

                <!-- Total Kuantitas Unit (Dihitung Otomatis) -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Total Kuantitas Pengiriman</label>
                    <span class="text-[10px] font-bold text-slate-400 font-mono">(Otomatis)</span>
                  </div>
                  <div
                    class="w-full bg-slate-100/90 border border-slate-300 rounded-xl px-3.5 py-2.5 h-[42px] min-h-[42px] flex items-center shadow-2xs">
                    <span class="text-xs font-black font-mono text-emerald-700 mr-1.5">{{ doForm.total_quantity }}</span>
                    <span class="text-xs font-bold text-slate-600">Unit Produk {{ doForm.product_name || 'P9' }}</span>
                  </div>
                </div>

                <!-- Nomor Pengiriman -->
                <div class="space-y-1.5">
                  <div class="h-6 flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-700">Nomor Pengiriman</label>
                  </div>
                  <div v-if="sjMode === 'backdate'">
                    <input type="number" min="1" v-model.number="sjDeliverySeq"
                      @input="updateGeneratedSJNumber(); syncPackagingStructure()"
                      @change="updateGeneratedSJNumber(); syncPackagingStructure()"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:bg-white h-[42px] min-h-[42px] transition shadow-2xs"
                      title="Nomor Urut Pengiriman (Backdate dapat disesuaikan)">
                  </div>
                  <div v-else>
                    <input type="text" :value="sjDeliverySeq" readonly disabled
                      class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-700 cursor-not-allowed h-[42px] min-h-[42px] shadow-2xs"
                      title="Nomor Pengiriman otomatis">
                  </div>
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
                  class="border border-slate-300 hover:bg-slate-100 active:scale-95 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition cursor-pointer min-h-[42px] flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-arrow-left text-xs"></i>
                  <span>Kembali ke Dokumen Referensi</span>
                </button>
                <button type="button" @click="goToFormStep('allocation')"
                  class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-xs transition cursor-pointer min-h-[42px] flex items-center gap-2">
                  <span>Lanjut ke Alokasi Serial Number (Kartu 4)</span>
                  <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
              </div>
            </div>
          </div>
          </div>

          <!-- KARTU 4: ALOKASI SERIAL NUMBER -->
          <div v-show="sjMode && activeFormStep === 'allocation'" class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex items-center justify-between gap-3 select-none cursor-default transition">
              <div class="flex items-center gap-2.5">
                <span
                  class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs border shadow-2xs transition bg-red-600 text-white border-red-600">
                  4
                </span>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-sm text-slate-900 leading-tight">
                    Alokasi Serial Number
                  </h3>
                  <span class="text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-lg border flex items-center gap-1"
                    :class="getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0 ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-slate-700 bg-slate-100 border-slate-200'">
                    <i v-if="getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0"
                      class="fa-solid fa-check-double text-emerald-600"></i>
                    <span>{{ getTotalFilledSNs() }} / {{ doForm.total_quantity }} Terisi</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Konten Kartu 4: Pengisian Satu per Satu Berurutan (Bebas Scroll Panjang) -->
            <div class="smooth-accordion" :class="{ 'is-open': isFormCardOpen('allocation') }">
              <div class="accordion-inner space-y-4 pt-1">

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
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-visible">
                  <div
                    class="bg-slate-50/80 border-b border-slate-200 p-3.5 sm:p-4 flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-red-600 flex items-center justify-center shadow-2xs font-black">
                        <i class="fa-solid fa-boxes-stacked text-sm"></i>
                      </div>
                      <div>
                        <div class="flex items-center gap-2 flex-wrap">
                          <span class="font-black font-mono text-sm text-slate-900">ID PETI: {{
                            doForm.crates[activeContainerIndex].crate_id }}</span>
                          <span class="text-xs text-slate-500">(Peti {{ activeContainerIndex + 1 }} dari {{
                            doForm.crates.length }})</span>
                          <span v-if="doForm.crates[activeContainerIndex].consumed_source_crate_id && doForm.crates[activeContainerIndex].consumed_source_crate_id !== doForm.crates[activeContainerIndex].crate_id"
                            class="text-[10px] font-mono font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded-lg flex items-center gap-1 shadow-2xs">
                            <i class="fa-solid fa-arrow-right-arrow-left text-[9px] text-emerald-600"></i>
                            Alokasi dari Peti PL: {{ doForm.crates[activeContainerIndex].consumed_source_crate_id }}
                          </span>
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

                      <!-- 10 Slot Alokasi SN -->
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div v-for="(item, sIdx) in box.items" :key="item.slot_number"
                          class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 transition border shadow-2xs relative bg-white border-slate-300 hover:border-slate-400 focus-within:border-red-600 focus-within:ring-2 focus-within:ring-red-100"
                          :class="isDOSlotDropdownOpen('do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx) ? 'z-[9999]' : ''">
                          <span class="w-5 text-right font-mono font-bold text-xs select-none shrink-0 text-slate-600" v-text="item.slot_number + '.'"></span>

                          <!-- Searchable Custom Dropdown -->
                          <div class="relative flex-1 flex items-center min-w-0">
                            <input type="text" v-model="item.serial_number"
                              @focus="openDOSlotDropdown('do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx)"
                              @keydown="handleDOSlotKeyDown($event, item, 'do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx)"
                              @blur="closeDOSlotDropdown()"
                              autocomplete="off"
                              autocorrect="off"
                              autocapitalize="off"
                              spellcheck="false"
                              placeholder="Pilih / ketik SN..."
                              class="w-full bg-transparent border-none focus:outline-none text-xs font-mono font-bold py-1.5 pr-14 min-h-[36px]"
                              :class="item.serial_number && !isSNInList(item.serial_number, item) ? 'text-rose-600 font-black' : 'text-slate-900'">

                            <!-- Actions (Clear + Dropdown Chevron) -->
                            <div class="absolute right-0 inset-y-0 flex items-center gap-0.5">
                              <button v-if="item.serial_number" type="button"
                                @mousedown.prevent="clearDOSlotSN(item)"
                                @click="clearDOSlotSN(item)"
                                class="text-slate-400 hover:text-red-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-red-50"
                                title="Hapus Serial Number">
                                <i class="fa-solid fa-xmark text-xs"></i>
                              </button>
                              <button type="button"
                                @mousedown.prevent="toggleDOSlotDropdown('do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx)"
                                class="text-slate-400 hover:text-red-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-slate-100"
                                title="Buka rekomendasi SN">
                                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                  :class="isDOSlotDropdownOpen('do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx) ? 'rotate-180 text-red-600' : ''"></i>
                              </button>
                            </div>

                            <!-- Popover Dropdown -->
                            <div
                              v-if="isDOSlotDropdownOpen('do-crate-' + activeContainerIndex + '-' + bIdx + '-' + sIdx)"
                              @mousedown.prevent
                              class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-[99999] max-h-[200px] overflow-y-auto divide-y divide-slate-100 py-1 ring-4 ring-red-500/15"
                              style="-webkit-overflow-scrolling: touch; touch-action: pan-y;">
                              <div class="px-2.5 py-1 text-[9.5px] font-bold text-red-700 uppercase tracking-wider flex justify-between items-center bg-red-50 sticky top-0 z-10 border-b border-red-100">
                                <span><i class="fa-solid fa-list-check text-red-600 mr-1"></i> Stok Tersedia ({{ getFilteredSNsForDOSlot(item.serial_number).length }})</span>
                                <span class="text-[9px] text-red-500 font-normal">Pilih / Ketik</span>
                              </div>
                              <div v-if="getFilteredSNsForDOSlot(item.serial_number).length > 0">
                                <button v-for="sn in getFilteredSNsForDOSlot(item.serial_number)" :key="sn" type="button"
                                  @mousedown.prevent="selectDOSlotSN(item, sn)"
                                  class="w-full text-left px-2.5 py-2 hover:bg-red-50 hover:text-red-900 text-xs font-mono font-bold flex items-center justify-between text-slate-800 transition cursor-pointer select-none">
                                  <span>{{ sn }}</span>
                                  <span class="text-[9px] text-emerald-700 font-sans font-bold bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded-full pointer-events-none">
                                    TERSEDIA
                                  </span>
                                </button>
                              </div>
                              <div v-else class="px-2.5 py-2.5 text-center text-xs text-slate-400 font-medium">
                                <i class="fa-solid fa-circle-exclamation text-amber-500 mr-1"></i>
                                <span>Tidak ada SN yang cocok</span>
                              </div>
                            </div>
                          </div>
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
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-visible">
                  <div
                    class="bg-slate-50/80 border-b border-slate-200 p-3.5 sm:p-4 flex items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-indigo-600 flex items-center justify-center shadow-2xs font-black">
                        <i class="fa-solid fa-box text-sm"></i>
                      </div>
                      <div>
                        <div class="flex items-center gap-2 flex-wrap">
                          <span class="font-black font-mono text-sm text-slate-900">ID DUS: {{
                            getAllBoxesInForm()[activeContainerIndex].box_id }}</span>
                          <span class="text-xs text-slate-500">(Dus {{ activeContainerIndex + 1 }} dari {{
                            getAllBoxesInForm().length }})</span>
                          <span v-if="getAllBoxesInForm()[activeContainerIndex].consumed_source_box_id && getAllBoxesInForm()[activeContainerIndex].consumed_source_box_id !== getAllBoxesInForm()[activeContainerIndex].box_id"
                            class="text-[10px] font-mono font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-2 py-0.5 rounded-lg flex items-center gap-1 shadow-2xs">
                            <i class="fa-solid fa-arrow-right-arrow-left text-[9px] text-emerald-600"></i>
                            Alokasi dari Dus PL: {{ getAllBoxesInForm()[activeContainerIndex].consumed_source_box_id }}
                          </span>
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

                  <!-- 10 Slot Alokasi SN -->
                  <div class="p-3 sm:p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <div v-for="(item, sIdx) in getAllBoxesInForm()[activeContainerIndex].items"
                        :key="item.slot_number"
                        class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 transition border shadow-2xs relative bg-white border-slate-300 hover:border-slate-400 focus-within:border-indigo-600 focus-within:ring-2 focus-within:ring-indigo-100"
                        :class="isDOSlotDropdownOpen('do-box-' + activeContainerIndex + '-' + sIdx) ? 'z-[9999]' : ''">
                        <span class="w-5 text-right font-mono font-bold text-xs select-none shrink-0 text-slate-600" v-text="item.slot_number + '.'"></span>

                        <!-- Searchable Custom Dropdown -->
                        <div class="relative flex-1 flex items-center min-w-0">
                          <input type="text" v-model="item.serial_number"
                            @focus="openDOSlotDropdown('do-box-' + activeContainerIndex + '-' + sIdx)"
                            @keydown="handleDOSlotKeyDown($event, item, 'do-box-' + activeContainerIndex + '-' + sIdx)"
                            @blur="closeDOSlotDropdown()"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                            placeholder="Pilih / ketik SN..."
                            class="w-full bg-transparent border-none focus:outline-none text-xs font-mono font-bold py-1.5 pr-14 min-h-[36px]"
                            :class="item.serial_number && !isSNInList(item.serial_number, item) ? 'text-rose-600 font-black' : 'text-slate-900'">

                          <!-- Actions (Clear + Dropdown Chevron) -->
                          <div class="absolute right-0 inset-y-0 flex items-center gap-0.5">
                            <button v-if="item.serial_number" type="button"
                              @mousedown.prevent="clearDOSlotSN(item)"
                              @click="clearDOSlotSN(item)"
                              class="text-slate-400 hover:text-indigo-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-indigo-50"
                              title="Hapus Serial Number">
                              <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                            <button type="button"
                              @mousedown.prevent="toggleDOSlotDropdown('do-box-' + activeContainerIndex + '-' + sIdx)"
                              class="text-slate-400 hover:text-indigo-600 p-1 text-xs cursor-pointer transition rounded-md hover:bg-slate-100"
                              title="Buka rekomendasi SN">
                              <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                                :class="isDOSlotDropdownOpen('do-box-' + activeContainerIndex + '-' + sIdx) ? 'rotate-180 text-indigo-600' : ''"></i>
                            </button>
                          </div>

                          <!-- Popover Dropdown -->
                          <div
                            v-if="isDOSlotDropdownOpen('do-box-' + activeContainerIndex + '-' + sIdx)"
                            @mousedown.prevent
                            class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl z-[99999] max-h-[200px] overflow-y-auto divide-y divide-slate-100 py-1 ring-4 ring-indigo-500/15"
                            style="-webkit-overflow-scrolling: touch; touch-action: pan-y;">
                            <div class="px-2.5 py-1 text-[9.5px] font-bold text-indigo-700 uppercase tracking-wider flex justify-between items-center bg-indigo-50 sticky top-0 z-10 border-b border-indigo-100">
                              <span><i class="fa-solid fa-list-check text-indigo-600 mr-1"></i> Stok Tersedia ({{ getFilteredSNsForDOSlot(item.serial_number).length }})</span>
                              <span class="text-[9px] text-indigo-500 font-normal">Pilih / Ketik</span>
                            </div>
                            <div v-if="getFilteredSNsForDOSlot(item.serial_number).length > 0">
                              <button v-for="sn in getFilteredSNsForDOSlot(item.serial_number)" :key="sn" type="button"
                                @mousedown.prevent="selectDOSlotSN(item, sn)"
                                class="w-full text-left px-2.5 py-2 hover:bg-indigo-50 hover:text-indigo-900 text-xs font-mono font-bold flex items-center justify-between text-slate-800 transition cursor-pointer select-none">
                                <span>{{ sn }}</span>
                                <span class="text-[9px] text-emerald-700 font-sans font-bold bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded-full pointer-events-none">
                                  TERSEDIA
                                </span>
                              </button>
                            </div>
                            <div v-else class="px-2.5 py-2.5 text-center text-xs text-slate-400 font-medium">
                              <i class="fa-solid fa-circle-exclamation text-amber-500 mr-1"></i>
                              <span>Tidak ada SN yang cocok</span>
                            </div>
                          </div>
                        </div>
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
                <!-- Alert Peringatan Serial Number Tidak Tersedia / Belum ACC -->
                <div v-if="hasInvalidDOSNs"
                  class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs font-semibold flex items-center gap-2">
                  <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                  <span>Terdapat Serial Number yang tidak valid atau belum lolos penerimaan ACC gudang. Penerbitan Delivery Order dinonaktifkan.</span>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                  <button type="button" @click="goToFormStep('batch')"
                    class="w-full sm:w-auto border border-slate-300 hover:bg-slate-100 active:scale-95 text-slate-700 font-bold text-xs px-5 py-3 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer min-h-[46px] shadow-2xs shrink-0">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Kembali ke Konsep Pengemasan</span>
                  </button>
                  <button type="button" @click="submitDeliveryOrder"
                    :disabled="isSubmittingDO || getTotalFilledSNs() !== doForm.total_quantity || sjDuplicateWarning || hasInvalidDOSNs" :class="(getTotalFilledSNs() === doForm.total_quantity && doForm.total_quantity > 0 && !sjDuplicateWarning && !hasInvalidDOSNs)
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

        </div>


        <!-- ================= SUB-TAB 2: RIWAYAT & DOKUMEN DELIVERY ORDER TERBIT ================= -->
        <div v-if="(doSubTab === 'surat_jalan' && suratJalanInnerTab === 'history') || doSubTab === 'history'" class="space-y-4 animate-fadeIn">

          <!-- Top Inner Tab Switcher: Form vs Riwayat Surat Jalan -->
          <div class="bg-white border border-slate-200 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center font-bold shadow-xs">
                <i class="fa-solid fa-file-invoice text-sm"></i>
              </span>
              <div>
                <h3 class="font-black text-sm text-slate-900 leading-tight">Surat Jalan</h3>
                <p class="text-[11px] text-slate-500">Riwayat seluruh surat jalan dan status pengiriman produk</p>
              </div>
            </div>

            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button type="button" @click="suratJalanInnerTab = 'form'; doSubTab = 'surat_jalan'"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-file-circle-plus"></i>
                <span>Buat Surat Jalan</span>
              </button>
              <button type="button" @click="suratJalanInnerTab = 'history'; doSubTab = 'surat_jalan'; loadDOList()"
                class="bg-red-600 text-white font-black shadow-md shadow-red-600/25 ring-2 ring-red-400/30 px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer min-h-[38px]">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Riwayat Surat Jalan ({{ doList.length }})</span>
              </button>
            </div>
          </div>

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
                <div class="relative">
                  <select v-model="doSortOrder"
                    class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-100 min-h-[38px] cursor-pointer shadow-2xs transition">
                    <option value="desc">Terbaru (Paling Atas)</option>
                    <option value="asc">Terlama (Paling Atas)</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-[11px]"></i>
                  </div>
                </div>
              </div>

              <!-- Filter Dokumen DO / Surat Jalan -->
              <div>
                <label class="block text-[10px] font-bold text-slate-500 mb-1">
                  <i class="fa-solid fa-file-invoice mr-1 text-red-600"></i> Filter Doc DO:
                </label>
                <div class="relative">
                  <select v-model="doDocFilter"
                    class="w-full appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-xl pl-3 pr-8 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100 min-h-[38px] cursor-pointer shadow-2xs transition">
                    <option value="">Semua Dokumen DO</option>
                    <option v-for="doc in distinctDODocNumbers" :key="doc" :value="doc">{{ doc }}</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                    <i class="fa-solid fa-chevron-down text-[11px]"></i>
                  </div>
                </div>
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
                  <span v-if="isBatchSyncingCloud" v-text="'Sinkron (' + batchSyncProgress.current + '/' + batchSyncProgress.total + ')...'"></span>
                  <span v-else>Sinkron Cloud</span>
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
              <table class="w-full text-left text-xs min-w-[950px]">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase text-[10px]">
                  <tr>
                    <th class="p-3.5 w-12 text-center">No</th>
                    <th @click="toggleDOSortBy('do_number')" class="p-3.5 cursor-pointer hover:bg-slate-100 transition select-none" title="Klik untuk mengurutkan Nomor Surat Jalan">
                      <div class="flex items-center gap-1.5">
                        <span>No. Surat Jalan</span>
                        <i v-if="doSortField === 'do_number'" class="fa-solid text-[10px] text-indigo-600" :class="doSortOrder === 'desc' ? 'fa-arrow-down-wide-short' : 'fa-arrow-up-wide-short'"></i>
                        <i v-else class="fa-solid fa-sort text-[10px] text-slate-300"></i>
                      </div>
                    </th>
                    <th @click="toggleDOSortBy('date')" class="p-3.5 cursor-pointer hover:bg-slate-100 transition select-none" title="Klik untuk membalik urutan tanggal">
                      <div class="flex items-center gap-1.5">
                        <span>Tanggal</span>
                        <i v-if="doSortField === 'date'" class="fa-solid text-[10px] text-indigo-600" :class="doSortOrder === 'desc' ? 'fa-arrow-down-wide-short' : 'fa-arrow-up-wide-short'"></i>
                        <i v-else class="fa-solid fa-sort text-[10px] text-slate-300"></i>
                      </div>
                    </th>
                    <th class="p-3.5">Penerima (Customer)</th>
                    <th class="p-3.5">Nomor Kontrak</th>
                    <th class="p-3.5 text-center w-28 whitespace-nowrap">Kuantitas</th>
                    <th class="p-3.5 text-center w-24 whitespace-nowrap">Peti</th>
                    <th class="p-3.5 text-center w-28 whitespace-nowrap">Cloud PDF</th>
                    <th class="p-3.5 text-center w-36 whitespace-nowrap">Aksi</th>
                    <th class="p-3.5 text-center w-28 whitespace-nowrap">Jenis SJ</th>
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
                    <td class="p-3.5 text-slate-700 font-bold whitespace-nowrap">{{ formatDocDate(item.do_date) }}</td>
                    <td class="p-3.5">
                      <div class="font-black text-slate-900">{{ item.customer_name }}</div>
                      <div class="text-[10px] text-slate-500 line-clamp-1">{{ item.customer_address }}</div>
                    </td>
                    <td class="p-3.5 font-mono text-[11px] text-slate-600 font-bold">{{ item.contract_number }}</td>
                    <td class="p-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                      <span
                        class="inline-flex items-center justify-center min-w-[96px] bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap tracking-wide">
                        {{ getDOTotalQuantity(item) }} Unit
                      </span>
                    </td>
                    <td class="p-3.5 text-center font-bold text-slate-800 whitespace-nowrap">
                      <span
                        class="inline-flex items-center justify-center min-w-[80px] bg-slate-100 border border-slate-200 text-slate-800 px-2.5 py-1 rounded-lg font-mono text-xs font-bold whitespace-nowrap">
                        <template v-if="item.package_type === 'box'">{{ getDOBoxCount(item) }} Dus</template>
                        <template v-else-if="item.package_type === 'unit'">{{ getDOTotalQuantity(item) }} Satuan</template>
                        <template v-else>{{ getDOCrateCount(item) }} Peti</template>
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

                    <!-- Jenis SJ (Hanya lingkaran warna tanpa tulisan) -->
                    <td class="p-3.5 text-center whitespace-nowrap">
                      <div class="inline-flex items-center justify-center"
                        :title="isDOBackdate(item) ? 'Backdate' : 'Realtime'">
                        <span class="w-3.5 h-3.5 rounded-full inline-block shadow-xs transition-transform hover:scale-110"
                          :class="isDOBackdate(item) ? 'bg-amber-400 ring-2 ring-amber-300' : 'bg-emerald-500 ring-2 ring-emerald-300'"></span>
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
                    <div class="relative">
                      <select v-model="pgState.doHistory.size" @change="pgState.doHistory.page = 1"
                        class="appearance-none bg-white border border-slate-300 hover:border-slate-400 rounded-lg pl-2.5 pr-6 py-1 text-xs font-bold text-slate-700 outline-none cursor-pointer shadow-2xs">
                        <option :value="5">5</option>
                        <option :value="10">10</option>
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-1.5 text-slate-400">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                      </div>
                    </div>
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
              {{ (doSearchText || doDocFilter || doStartDate || doEndDate) ? 'Tidak Ada Dokumen Surat Jalan yang Sesuai Filter' :
              'Belum Ada Dokumen Surat Jalan' }}
            </h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
              {{ (doSearchText || doDocFilter || doStartDate || doEndDate) ? 'Coba gunakan kata kunci pencarian yang berbeda atau sesuaikan filter dokumen dan tanggal.' : 'Anda dapat membuat dan menerbitkan dokumen Surat Jalan lengkap dengan rincian kemasan dan data customer.' }}
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


@endverbatim
