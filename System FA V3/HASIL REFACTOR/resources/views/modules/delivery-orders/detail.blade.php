@verbatim
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
                  <div class="font-black text-emerald-700 text-sm">{{ getDOTotalQuantity(selectedDOForDetail) }} Unit</div>
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
                <button type="button" @click="openDODetailAndPrint(selectedDOForDetail.id, 'label_dus')"
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
                  Total: {{ getDOTotalQuantity(selectedDOForDetail) }} Unit
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



@endverbatim
