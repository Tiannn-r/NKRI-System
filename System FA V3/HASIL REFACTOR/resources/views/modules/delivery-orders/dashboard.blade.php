@verbatim
        <!-- ================= DASHBOARD DELIVERY ORDER ================= -->
        <div v-if="doSubTab === 'dashboard'" class="space-y-5 animate-fadeIn">
          <!-- Top Sub-Bar Info & Refresh -->
          <div class="bg-red-600 text-white rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
              <div class="w-11 h-11 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white text-lg font-black shadow-inner">
                <i class="fa-solid fa-chart-line"></i>
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-base sm:text-lg font-black tracking-tight text-white">Monitoring Delivery Order &amp; Distribusi</h3>
                </div>
                <p class="text-xs text-white/90 mt-0.5">Pemantauan volume perakitan, kesiapan gudang, pengiriman, dan status mutu part</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="loadDoDashboardStats" :disabled="isLoadingDoStats"
                class="bg-white/10 hover:bg-white/20 active:scale-95 text-white font-bold text-xs px-3.5 py-2 rounded-xl border border-white/15 transition flex items-center gap-2 min-h-[38px] cursor-pointer">
                <i class="fa-solid fa-arrows-rotate" :class="{'fa-spin': isLoadingDoStats}"></i>
                <span>Segarkan Data</span>
              </button>
            </div>
          </div>

          <!-- 4 Ringkasan Metrik (Per Set & Per Part: 1 Set = 3 Part) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Total Keseluruhan Set yang Sudah Dirakit -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
              <div class="flex items-start justify-between">
                <div>
                  <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Set Dirakit</span>
                  <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-slate-900">{{ (doDashboardStats.total_assembled_sets || 0).toLocaleString('id-ID') }}</span>
                    <span class="text-xs font-black text-slate-600">Set</span>
                  </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-base shadow-2xs">
                  <i class="fa-solid fa-boxes-stacked"></i>
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Hitungan Part:</span>
                <span class="font-black text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-lg border border-blue-200 font-mono">
                  {{ (doDashboardStats.total_assembled_parts || 0).toLocaleString('id-ID') }} Part
                </span>
              </div>
            </div>

            <!-- 2. Set Siap Kirim (Good Receipts Gudang ACC) -->
            <div class="bg-white border border-emerald-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
              <div class="flex items-start justify-between">
                <div>
                  <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Siap Kirim (Good Receipts)</span>
                  <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-700">{{ (doDashboardStats.ready_to_ship_sets || 0).toLocaleString('id-ID') }}</span>
                    <span class="text-xs font-black text-emerald-800">Set</span>
                  </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center text-base shadow-2xs">
                  <i class="fa-solid fa-box-open"></i>
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Hitungan Part:</span>
                <span class="font-black text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200 font-mono">
                  {{ (doDashboardStats.ready_to_ship_parts || 0).toLocaleString('id-ID') }} Part
                </span>
              </div>
            </div>

            <!-- 3. Sudah Dikirim (Masuk PL & Surat Jalan) -->
            <div class="bg-white border border-purple-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
              <div class="flex items-start justify-between">
                <div>
                  <span class="text-[11px] font-bold text-purple-700 uppercase tracking-wider block">Sudah Dikirim</span>
                  <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-purple-700">{{ (doDashboardStats.shipped_sets || 0).toLocaleString('id-ID') }}</span>
                    <span class="text-xs font-black text-purple-800">Set</span>
                  </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center text-base shadow-2xs">
                  <i class="fa-solid fa-truck-fast"></i>
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Hitungan Part:</span>
                <span class="font-black text-purple-800 bg-purple-50 px-2.5 py-0.5 rounded-lg border border-purple-200 font-mono">
                  {{ (doDashboardStats.shipped_parts || 0).toLocaleString('id-ID') }} Part
                </span>
              </div>
            </div>

            <!-- 4. Ringkasan Dokumen Surat Jalan & Customer -->
            <div class="bg-white border border-amber-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition">
              <div class="flex items-start justify-between">
                <div>
                  <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">Dokumen Surat Jalan</span>
                  <div class="mt-1.5 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-amber-700">{{ (doDashboardStats.summary?.total_do || 0).toLocaleString('id-ID') }}</span>
                    <span class="text-xs font-black text-amber-800">Surat Jalan</span>
                  </div>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-base shadow-2xs">
                  <i class="fa-solid fa-file-invoice"></i>
                </div>
              </div>
              <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <span class="text-slate-500 font-medium">{{ doDashboardStats.summary?.total_pl || 0 }} Packing List</span>
                <span class="font-black text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                  {{ doDashboardStats.summary?.total_customers || 0 }} Customer
                </span>
              </div>
            </div>
          </div>

          <!-- Section Mutu & Chart (Good, Hold, Scrap) -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Chart Doughnut Good, Hold, Scrap -->
            <div class="lg:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                  <div class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-blue-600 text-sm"></i>
                    <h4 class="font-black text-sm text-slate-900">Distribusi Status Mutu Barang</h4>
                  </div>
                  <span class="text-[10px] font-bold text-slate-500 uppercase bg-slate-100 px-2 py-0.5 rounded">Keseluruhan</span>
                </div>
                <p class="text-xs text-slate-500 mt-2">Komposisi kondisi seluruh barang rakitan berdasarkan hasil uji mutu &amp; QC.</p>
              </div>

              <!-- Canvas Chart Container -->
              <div class="relative h-60 w-full my-4 flex items-center justify-center">
                <canvas id="doQualityChartCanvas"></canvas>
              </div>

              <div class="text-[11px] text-center text-slate-400">
                Data disinkronkan secara realtime dengan sistem perakitan &amp; verifikasi admin.
              </div>
            </div>

            <!-- Detail Breakdown Status Barang (Good, Hold, Scrap) -->
            <div class="lg:col-span-7 bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                  <i class="fa-solid fa-layer-group text-slate-700 text-sm"></i>
                  <h4 class="font-black text-sm text-slate-900">Rincian Fisik Kondisi Barang (Set &amp; /Part)</h4>
                </div>
                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">1 Set = 3 Part (P15, P1, P12)</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                <!-- Status 1: Good -->
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-xs text-emerald-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Good
                      </span>
                      <span class="text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 px-1.5 py-0.5 rounded">Lolos</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-emerald-800">
                      {{ (doDashboardStats.quality?.good_sets || 0).toLocaleString('id-ID') }}
                      <span class="text-xs font-bold text-emerald-700">Set</span>
                    </div>
                  </div>
                  <div class="mt-3 pt-2.5 border-t border-emerald-200/80 flex items-center justify-between text-xs">
                    <span class="text-emerald-700 font-medium">Part:</span>
                    <strong class="font-mono text-emerald-950 font-black">{{ (doDashboardStats.quality?.good_parts || 0).toLocaleString('id-ID') }} Part</strong>
                  </div>
                </div>

                <!-- Status 2: Hold -->
                <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-xs text-amber-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-hand text-amber-600"></i> Hold
                      </span>
                      <span class="text-[10px] font-bold uppercase bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Tertahan</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-amber-800">
                      {{ (doDashboardStats.quality?.hold_sets || 0).toLocaleString('id-ID') }}
                      <span class="text-xs font-bold text-amber-700">Set</span>
                    </div>
                  </div>
                  <div class="mt-3 pt-2.5 border-t border-amber-200/80 flex items-center justify-between text-xs">
                    <span class="text-amber-700 font-medium">Part:</span>
                    <strong class="font-mono text-amber-950 font-black">{{ (doDashboardStats.quality?.hold_parts || 0).toLocaleString('id-ID') }} Part</strong>
                  </div>
                </div>

                <!-- Status 3: Scrap -->
                <div class="bg-rose-50/70 border border-rose-200 rounded-2xl p-4 flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between">
                      <span class="font-bold text-xs text-rose-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can text-rose-600"></i> Scrap
                      </span>
                      <span class="text-[10px] font-bold uppercase bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded">Afkir</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-rose-800">
                      {{ (doDashboardStats.quality?.scrap_sets || 0).toLocaleString('id-ID') }}
                      <span class="text-xs font-bold text-rose-700">Set</span>
                    </div>
                  </div>
                  <div class="mt-3 pt-2.5 border-t border-rose-200/80 flex items-center justify-between text-xs">
                    <span class="text-rose-700 font-medium">Part:</span>
                    <strong class="font-mono text-rose-950 font-black">{{ (doDashboardStats.quality?.scrap_parts || 0).toLocaleString('id-ID') }} Part</strong>
                  </div>
                </div>
              </div>

              <!-- Quick Shortcut Action Tiles -->
              <div class="pt-3 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2.5">Aksi Cepat Menu Distribusi:</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                  <button type="button" @click="setDoSubTab('packing_list')"
                    class="p-3 rounded-xl border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-100/70 transition flex items-center gap-2.5 text-left cursor-pointer group">
                    <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                      <i class="fa-solid fa-boxes-packing text-xs"></i>
                    </span>
                    <div>
                      <div class="text-xs font-black text-emerald-950">Packing List</div>
                      <div class="text-[10px] text-emerald-700 font-medium">Buat &amp; Riwayat Label</div>
                    </div>
                  </button>

                  <button type="button" @click="setDoSubTab('surat_jalan')"
                    class="p-3 rounded-xl border border-red-200 bg-red-50/50 hover:bg-red-100/70 transition flex items-center gap-2.5 text-left cursor-pointer group">
                    <span class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                      <i class="fa-solid fa-file-invoice text-xs"></i>
                    </span>
                    <div>
                      <div class="text-xs font-black text-red-950">Surat Jalan</div>
                      <div class="text-[10px] text-red-700 font-medium">Buat &amp; Riwayat Dokumen</div>
                    </div>
                  </button>

                  <button type="button" @click="setDoSubTab('customers')"
                    class="p-3 rounded-xl border border-blue-200 bg-blue-50/50 hover:bg-blue-100/70 transition flex items-center gap-2.5 text-left cursor-pointer group">
                    <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 group-hover:scale-105 transition">
                      <i class="fa-solid fa-users text-xs"></i>
                    </span>
                    <div>
                      <div class="text-xs font-black text-blue-950">Customer</div>
                      <div class="text-[10px] text-blue-700 font-medium">Kelola Instansi</div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

@endverbatim
