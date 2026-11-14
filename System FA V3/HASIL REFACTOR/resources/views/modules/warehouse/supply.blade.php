@verbatim
      <!-- ================= TAB 2: WAREHOUSE SUPPLY KITTING ================= -->
      <section v-if="currentTab === 'warehouse'" class="space-y-4 sm:space-y-6">

        <!-- Input Part ke dalam Tray Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm space-y-4 sm:space-y-5">
          <div
            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 border-b border-slate-100 pb-3 sm:pb-4">
            <div>
              <h2 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Input Part ke dalam Tray</h2>
            </div>
            <div
              class="bg-red-50 text-red-700 border border-red-200 px-3 py-1 sm:px-3.5 sm:py-1.5 rounded-xl text-xs font-bold self-start sm:self-auto">
              Tahap: {{ gudangColStage === 1 ? 'P9 Part 1' : (gudangColStage === 2 ? 'P9 Part 12' : 'P9 Part 15') }}
            </div>
          </div>

          <!-- PIC, Jenis Produk & Operator Assignment Fields -->
          <div
            class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-slate-50 border border-slate-200 p-3.5 sm:p-4 rounded-xl">
            <!-- PIC Warehouse (Full Name) -->
            <div>
              <label class="text-xs font-bold text-slate-700 mb-1 block flex items-center">
                Petugas Gudang:
              </label>
              <input type="text" :value="userFullName || userName || 'Gudang'" disabled readonly
                class="w-full bg-slate-100 border border-slate-300 text-slate-700 font-bold rounded-lg px-3 py-2 text-xs sm:text-sm cursor-not-allowed select-none min-h-[38px]">
            </div>

            <!-- Jenis Produk (Terkunci Nanggala P9) -->
            <div>
              <label class="text-xs font-bold text-slate-700 mb-1 block flex items-center">
                Jenis Produk:
              </label>
              <input type="text" v-model="gudangProductType" readonly disabled value="Nanggala P9"
                class="w-full bg-slate-100 border border-slate-200 rounded-lg px-3 py-2 text-xs sm:text-sm text-slate-700 font-semibold cursor-not-allowed min-h-[38px]">
            </div>

            <!-- Assignment to Assembly Operator (Mandatory) -->
            <div>
              <label class="text-xs font-bold text-slate-700 mb-1 block flex items-center justify-between">
                <span>Tugaskan ke Operator: <span class="text-red-600 font-bold">*</span></span>
                
              </label>
              <select id="gudang-operator-select" v-model="gudangAssignedOperator"
                class="w-full bg-white border focus:border-red-600 focus:ring-2 focus:ring-red-100 rounded-lg px-3 py-2 text-xs sm:text-sm text-slate-900 font-semibold focus:outline-none min-h-[38px] cursor-pointer"
                :class="!gudangAssignedOperator ? 'border-amber-400 bg-amber-50/20' : 'border-slate-300'">
                <option value="" disabled>Pilih operator assembly</option>
                <option v-for="op in operatorUsersList" :key="op.username" :value="op.full_name || op.username">
                  {{ op.full_name || op.username }}
                </option>
              </select>
            </div>
          </div>

          <!-- Kitting Table - Mobile: card rows, Desktop: horizontal table -->

          <!-- Desktop Table (sm and above) -->
          <div class="hidden sm:block rounded-xl border border-slate-200 overflow-visible relative">
            <table class="w-full text-left text-xs min-w-[500px] overflow-visible">
              <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                <tr>
                  <th class="p-2.5 sm:p-3 w-14 text-center uppercase tracking-wider text-[11px]">Set</th>
                  <th class="p-2.5 sm:p-3 font-bold text-slate-800 tracking-wide">P9 Part 1</th>
                  <th v-if="gudangColStage >= 2" class="p-2.5 sm:p-3 font-bold text-slate-800 tracking-wide">P9 Part 12</th>
                  <th v-if="gudangColStage >= 3" class="p-2.5 sm:p-3 font-bold text-slate-800 tracking-wide">P9 Part 15</th>
                  <th class="p-2.5 sm:p-3 w-16 text-center uppercase tracking-wider text-[11px]">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-800">
                <tr v-for="(row, idx) in gudangRows" :key="idx" class="hover:bg-slate-50 transition-colors"
                  :class="inputDropdown.isOpen && inputDropdown.idx === idx ? 'relative z-[9999]' : 'relative z-10'">
                  <td class="p-2 text-center font-bold text-slate-500">#{{ idx + 1 }}</td>

                  <!-- P9 Part 1 Input (Stage 1) -->
                  <td class="p-1.5 sm:p-2 relative overflow-visible"
                    :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p1' ? 'z-[9999]' : ''">
                    <div v-if="getInputWarning(idx, 'p1')"
                      class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                      <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p1')
                      }}
                    </div>
                    <input type="text" v-model="row.p1" :id="'g-p1-' + idx" @focus="onInputFocus(idx, 'p1')"
                      @input="onInputP1(idx)" @keydown="handleGudangKeyDown($event, idx, 'p1')"
                      @blur="onInputBlur(idx, 'p1')" :disabled="gudangColStage !== 1"
                      :class="gudangColStage > 1 ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : (getInputWarning(idx, 'p1') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100')"
                      placeholder="P9 Part 1" autocomplete="off"
                      class="w-full border rounded-lg px-2.5 py-2 text-xs focus:outline-none font-mono min-h-[36px]">
                    <div
                      v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p1' && inputDropdown.list.length"
                      class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                      style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                      @touchmove="onDropdownTouchMove">
                      <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                      <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p1', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p1', item.sn)"
                        @click="selectDropdownSN(idx, 'p1', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                    </div>
                  </td>

                  <!-- P9 Part 12 Input (Stage 2) -->
                  <td v-if="gudangColStage >= 2" class="p-1.5 sm:p-2 relative overflow-visible"
                    :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p12' ? 'z-[9999]' : ''">
                    <div v-if="getInputWarning(idx, 'p12')"
                      class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                      <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p12')
                      }}
                    </div>
                    <input type="text" v-model="row.p12" :id="'g-p12-sm-' + idx" @focus="onInputFocus(idx, 'p12')"
                      @input="onInputP12(idx)" @keydown="handleGudangKeyDown($event, idx, 'p12')"
                      @blur="onInputBlur(idx, 'p12')" :disabled="gudangColStage !== 2"
                      :class="gudangColStage > 2 ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : (getInputWarning(idx, 'p12') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100')"
                      placeholder="P9 Part 12" autocomplete="off"
                      class="w-full border rounded-lg px-2.5 py-2 text-xs focus:outline-none font-mono min-h-[36px]">
                    <div
                      v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p12' && inputDropdown.list.length"
                      class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                      style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                      @touchmove="onDropdownTouchMove">
                      <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                      <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p12', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p12', item.sn)"
                        @click="selectDropdownSN(idx, 'p12', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                    </div>
                  </td>

                  <!-- P9 Part 15 Input (Stage 3) -->
                  <td v-if="gudangColStage >= 3" class="p-1.5 sm:p-2 relative overflow-visible"
                    :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p15' ? 'z-[9999]' : ''">
                    <div v-if="getInputWarning(idx, 'p15')"
                      class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                      <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p15')
                      }}
                    </div>
                    <input type="text" v-model="row.p15" :id="'g-p15-' + idx" @focus="onInputFocus(idx, 'p15')"
                      @input="onInputP15(idx)" @keydown="handleGudangKeyDown($event, idx, 'p15')"
                      @blur="onInputBlur(idx, 'p15')" :disabled="gudangColStage !== 3"
                      :class="getInputWarning(idx, 'p15') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100'"
                      placeholder="P9 Part 15" autocomplete="off"
                      class="w-full border rounded-lg px-2.5 py-2 text-xs focus:outline-none font-mono min-h-[36px]">
                    <div
                      v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p15' && inputDropdown.list.length"
                      class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                      style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                      @touchmove="onDropdownTouchMove">
                      <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                      <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p15', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p15', item.sn)"
                        @click="selectDropdownSN(idx, 'p15', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                    </div>
                  </td>

                  <td class="p-1.5 sm:p-2 text-center">
                    <button v-if="gudangRows.length > 1 && gudangColStage === 1" @click="removeGudangRow(idx)"
                      class="text-red-600 hover:text-red-800 p-1.5" title="Hapus Baris">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                    <span v-else class="text-slate-300">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card Layout (below sm) -->
          <div class="sm:hidden space-y-3">
            <div v-for="(row, idx) in gudangRows" :key="'m-' + idx"
              class="bg-white border border-slate-200 rounded-xl p-3 space-y-2.5 shadow-sm relative transition-all"
              :class="inputDropdown.isOpen && inputDropdown.idx === idx ? 'relative z-[9999]' : 'relative z-10'">
              <!-- Row header -->
              <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <span class="text-xs font-black text-slate-700 bg-slate-100 px-2 py-0.5 rounded-lg">#{{ idx + 1
                  }}</span>
                <button v-if="gudangRows.length > 1 && gudangColStage === 1" @click="removeGudangRow(idx)"
                  class="text-red-500 hover:text-red-700 p-1" title="Hapus Baris">
                  <i class="fa-solid fa-trash-can text-sm"></i>
                </button>
              </div>

              <!-- P9 Part 1 (Stage 1+) -->
              <div class="relative"
                :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p1' ? 'z-[9999]' : ''">
                <label class="text-xs font-bold text-slate-700 mb-1 block">P9 Part 1 &nbsp;&nbsp; ( Standar / Double
                  Marking )</label>
                <div v-if="getInputWarning(idx, 'p1')"
                  class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                  <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p1') }}
                </div>
                <input type="text" inputmode="numeric" v-model="row.p1" :id="'g-p1-m-' + idx"
                  @focus="onInputFocus(idx, 'p1')" @input="onInputP1(idx)"
                  @keydown="handleGudangKeyDown($event, idx, 'p1')" @blur="onInputBlur(idx, 'p1')"
                  :disabled="gudangColStage !== 1"
                  :class="gudangColStage > 1 ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : (getInputWarning(idx, 'p1') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100')"
                  placeholder="P9 Part 1" autocomplete="off"
                  class="w-full border rounded-lg px-3 py-2.5 text-xs focus:outline-none font-mono min-h-[40px]">
                <div
                  v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p1' && inputDropdown.list.length"
                  class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                  style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                  @touchmove="onDropdownTouchMove">
                  <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                  <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p1', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p1', item.sn)"
                        @click="selectDropdownSN(idx, 'p1', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                </div>
              </div>

              <!-- P9 Part 12 (Stage 2+) -->
              <div v-if="gudangColStage >= 2" class="relative"
                :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p12' ? 'z-[9999]' : ''">
                <label class="text-xs font-bold text-slate-700 mb-1 block">P9 Part 12</label>
                <div v-if="getInputWarning(idx, 'p12')"
                  class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                  <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p12') }}
                </div>
                <input type="text" inputmode="numeric" v-model="row.p12" :id="'g-p12-m-' + idx"
                  @focus="onInputFocus(idx, 'p12')" @input="onInputP12(idx)"
                  @keydown="handleGudangKeyDown($event, idx, 'p12')" @blur="onInputBlur(idx, 'p12')"
                  :disabled="gudangColStage !== 2"
                  :class="gudangColStage > 2 ? 'bg-emerald-50 border-emerald-300 text-emerald-800 font-bold' : (getInputWarning(idx, 'p12') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100')"
                  placeholder="P9 Part 12" autocomplete="off"
                  class="w-full border rounded-lg px-3 py-2.5 text-xs focus:outline-none font-mono min-h-[40px]">
                <div
                  v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p12' && inputDropdown.list.length"
                  class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                  style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                  @touchmove="onDropdownTouchMove">
                  <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                  <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p12', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p12', item.sn)"
                        @click="selectDropdownSN(idx, 'p12', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                </div>
              </div>

              <!-- P9 Part 15 (Stage 3+) -->
              <div v-if="gudangColStage >= 3" class="relative"
                :class="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p15' ? 'z-[9999]' : ''">
                <label class="text-xs font-bold text-slate-700 mb-1 block">P9 Part 15</label>
                <div v-if="getInputWarning(idx, 'p15')"
                  class="text-[10px] font-bold text-red-600 mb-1 flex items-center bg-red-50 px-2 py-0.5 rounded border border-red-200">
                  <i class="fa-solid fa-triangle-exclamation mr-1 flex-shrink-0"></i> {{ getInputWarning(idx, 'p15') }}
                </div>
                <input type="text" v-model="row.p15" :id="'g-p15-m-' + idx" @focus="onInputFocus(idx, 'p15')"
                  @input="onInputP15(idx)" @keydown="handleGudangKeyDown($event, idx, 'p15')"
                  @blur="onInputBlur(idx, 'p15')" :disabled="gudangColStage !== 3"
                  :class="getInputWarning(idx, 'p15') ? 'border-red-500 bg-red-50/40 text-red-900' : 'bg-white border-slate-300 text-slate-900 focus:border-red-600 focus:ring-1 focus:ring-red-100'"
                  placeholder="P9 Part 15" autocomplete="off"
                  class="w-full border rounded-lg px-3 py-2.5 text-xs focus:outline-none font-mono font-bold min-h-[40px]">
                <div
                  v-if="inputDropdown.isOpen && inputDropdown.idx === idx && inputDropdown.field === 'p15' && inputDropdown.list.length"
                  class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-lg z-[99999] max-h-[220px] overflow-y-auto overscroll-contain divide-y divide-slate-100 py-1 bg-white"
                  style="-webkit-overflow-scrolling: touch; touch-action: pan-y;" @touchstart="onDropdownTouchStart"
                  @touchmove="onDropdownTouchMove">
                  <div class="px-3 py-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 sticky top-0 z-10 border-b border-slate-100 flex items-center justify-between"><span>Rekomendasi ({{ inputDropdown.list.length }})</span></div>
                  <button v-for="item in inputDropdown.list" :key="item.sn" type="button"
                        @mousedown.prevent="selectDropdownSN(idx, 'p15', item.sn)"
                        @touchend="onDropdownTouchEnd($event, idx, 'p15', item.sn)"
                        @click="selectDropdownSN(idx, 'p15', item.sn)"
                        class="w-full text-left px-3 py-2 hover:bg-slate-100 text-xs font-mono font-semibold text-slate-800 transition cursor-pointer select-none flex items-center justify-between">
                        <span>{{ item.sn }}</span>
                      </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons for Kitting Stages -->
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
            <div class="flex items-center gap-2">
              <!-- Stage 1: Tambah Baris -->
              <button v-if="gudangColStage === 1" @click="addGudangRow(true)"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 min-h-[40px]">
                <i class="fa-solid fa-plus"></i>
                <span>Tambah Baris</span>
              </button>

              <!-- Stage 2: Kembali ke P9 Part 1 -->
              <button v-if="gudangColStage === 2" @click="goToGudangStage(1)"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 min-h-[40px]">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke P9 Part 1</span>
              </button>

              <!-- Stage 3: Kembali ke P9 Part 12 -->
              <button v-if="gudangColStage === 3" @click="goToGudangStage(2)"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center space-x-1.5 min-h-[40px]">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke P9 Part 12</span>
              </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <!-- Stage 1 Button: Selesai / Lanjut ke P9 Part 12 -->
              <button v-if="gudangColStage === 1" @click="verifyGudangStage1"
                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-2 min-h-[40px]">
                <span>Lanjut ke Part 12</span>
                <i class="fa-solid fa-arrow-right"></i>
              </button>

              <!-- Stage 2 Button: Selesai / Lanjut ke Part 15 -->
              <button v-if="gudangColStage === 2" @click="verifyGudangStage2"
                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-emerald-600/25 transition flex items-center justify-center space-x-2 min-h-[40px]">
                <span>Lanjut ke Part 15</span>
                <i class="fa-solid fa-arrow-right"></i>
              </button>

              <!-- Stage 3 Button: Triggers MO Modal Prompt -->
              <button v-if="gudangColStage === 3" @click="promptSaveGudangTray"
                class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-7 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-red-600/25 transition flex items-center justify-center space-x-2 min-h-[40px]">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Tray Baru</span>
              </button>
            </div>
          </div>

        </div>

      </section>

@endverbatim
