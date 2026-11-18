@verbatim
      <!-- REALTIME DATA LOADING PROGRESS MONITOR (HALAMAN OPERASIONAL USER) -->
      <div v-if="userLoadingProgress.active" class="no-print bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs text-slate-900 transition-all duration-300 animate-fadeIn mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-2.5">
          <div class="flex items-center gap-3">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
            </span>
            <div>
              <div class="text-xs font-bold tracking-wide text-slate-900">
                <span>Sinkronisasi {{ userLoadingProgress.title || 'Data Operasional' }}</span>
              </div>
              <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5 font-medium">
                <i class="fa-solid fa-cloud-arrow-down text-red-500 text-[10px]"></i>
                <span>{{ userLoadingProgress.detail || 'Memuat data dari server...' }}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="text-right">
              <span class="text-xl font-black font-mono text-red-600">{{ userLoadingProgress.percent }}%</span>
              <div class="text-[10px] font-medium text-slate-500">
                <span v-if="userLoadingProgress.recordsLoaded > 0">{{ userLoadingProgress.recordsLoaded.toLocaleString() }} baris data</span>
                <span v-else>Memproses data...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Animated Clean Progress Bar (Sesuai Tema Website NKRI) -->
        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-200">
          <div class="bg-gradient-to-r from-red-600 to-rose-500 h-full rounded-full transition-all duration-300 ease-out shadow-2xs"
               :style="{ width: userLoadingProgress.percent + '%' }"></div>
        </div>
      </div>
@endverbatim
