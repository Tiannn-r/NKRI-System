@verbatim
      <!-- ================= TAB: DELIVERY ORDER & PACKING LIST (DO & PL) ================= -->
      <section v-if="currentTab === 'delivery_order'" class="space-y-4 sm:space-y-6 animate-fadeIn">

        <!-- Top Header & Sub-Tab Bar -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 sm:p-5 shadow-xs">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 font-bold text-[10px] uppercase tracking-wider">
                  Logistik, PPIC &amp; Distribusi
                </span>
                <span class="text-xs text-slate-400">|</span>
                <span class="text-xs font-semibold text-slate-500">PT Nanggala Kencana Rekatama Indonesia</span>
              </div>
              <h2 class="text-lg sm:text-2xl font-black text-slate-900 tracking-tight mt-1">
                Modul Delivery Order &amp; Distribusi Produk
              </h2>
            </div>
          </div>
        </div>
@endverbatim

        {{-- 1. Menu Dashboard DO --}}
        @include('modules.delivery-orders.dashboard')

        {{-- 2. Menu Database DO / Customer --}}
        @include('modules.delivery-orders.database-do')

        {{-- 3. Menu Packing List (Form Alokasi & Riwayat PL Standalone) --}}
        @include('modules.delivery-orders.packing-list')

        {{-- 4. Menu Surat Jalan (Form Alokasi DO & Riwayat DO Terbit) --}}
        @include('modules.delivery-orders.surat-jalan')

        {{-- 5. Sub-Tab Penunjang: Pratinjau Dokumen Fisik Resmi & Detail DO --}}
        @include('modules.delivery-orders.preview')
        @include('modules.delivery-orders.detail')

@verbatim
      </section>
@endverbatim
