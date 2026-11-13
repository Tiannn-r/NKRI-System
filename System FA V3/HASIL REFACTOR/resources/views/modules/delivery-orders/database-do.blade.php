@verbatim
        <!-- ================= SUB-TAB 3: DATABASE CUSTOMER ================= -->
        <div v-if="doSubTab === 'customers'" class="w-full space-y-4 animate-fadeIn">
          <div
            class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs">
                <i class="fa-solid fa-users text-sm"></i>
              </span>
              <div>
                <h3 class="font-black text-sm text-slate-900 leading-tight">Database Customer ({{ customersList.length }})</h3>
                <p class="text-[11px] text-slate-500">Daftar instansi penerima barang untuk Surat Jalan</p>
              </div>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
              <button type="button" @click="loadCustomers"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-3.5 py-2.5 rounded-xl border border-slate-300 flex items-center gap-1.5 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-arrows-rotate text-xs"></i>
                <span>Segarkan</span>
              </button>
              <button type="button" @click="openNewCustomerModal"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-md shadow-blue-600/25 flex items-center gap-1.5 transition cursor-pointer min-h-[42px]">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Customer Baru</span>
              </button>
            </div>
          </div>

          <!-- List Customer Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div v-for="c in customersList" :key="c.id"
              class="bg-white border border-slate-200 hover:border-blue-300 rounded-2xl p-4 shadow-xs flex flex-col justify-between space-y-3 transition">
              <div>
                <div class="flex items-start justify-between gap-2">
                  <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs">
                      <i class="fa-solid fa-building"></i>
                    </span>
                    <h4 class="font-black text-slate-900 text-sm">{{ c.name }}</h4>
                  </div>
                  <button type="button" @click="deleteCustomer(c.id, c.name)"
                    class="text-slate-400 hover:text-red-600 text-xs p-1.5 rounded-lg hover:bg-red-50 transition cursor-pointer"
                    title="Hapus customer">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
                <p class="text-xs text-slate-600 mt-2 whitespace-pre-line leading-relaxed bg-slate-50 border border-slate-100 rounded-xl p-2.5">{{ c.address }}</p>
              </div>
              <div
                class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-[11px] text-slate-500 font-mono">
                <span><i class="fa-solid fa-phone text-slate-400 mr-1"></i>{{ c.phone || '-' }}</span>
                <span><i class="fa-solid fa-fax text-slate-400 mr-1"></i>{{ c.fax || '-' }}</span>
              </div>
            </div>
            <div v-if="customersList.length === 0"
              class="col-span-full p-8 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
              <i class="fa-solid fa-users text-3xl mb-2 text-slate-300 block"></i>
              Belum ada data customer terdaftar. Klik tombol Tambah Customer Baru untuk mendaftarkan instansi.
            </div>
          </div>
        </div>

@endverbatim
