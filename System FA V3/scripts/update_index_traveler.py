import re

with open('static/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update selectPrintDocType and filteredPrintTrays
old_doc_type_block = '''        const selectPrintDocType = (type) => {
          activePrintDocType.value = type;
          activeAssyQCDocSlot.value = null;
          showPrintDocTypeDropdown.value = false;
          // Auto select matching tray from filtered list or trays list
          setTimeout(() => {
            const avail = filteredPrintTrays.value;
            if (avail && avail.length > 0) {
              if (!avail.some(t => t.tray_code === selectedTrayCode.value)) {
                selectedTrayCode.value = avail[0].tray_code;
              }
            } else if (type === 'assy_rework') {
              const pu = (traysList.value || []).find(t => (t.tray_code || '').toUpperCase().includes('-PU'));
              if (pu) {
                selectedTrayCode.value = pu.tray_code;
              }
            }
            loadTravelerTray();
          }, 60);
        };'''

new_doc_type_block = '''        const selectPrintDocType = (type) => {
          activePrintDocType.value = type;
          activeAssyQCDocSlot.value = null;
          showPrintDocTypeDropdown.value = false;
          // Keep current selected tray code if it exists, otherwise pick the first available
          if (!selectedTrayCode.value && filteredPrintTrays.value && filteredPrintTrays.value.length > 0) {
            selectedTrayCode.value = filteredPrintTrays.value[0].tray_code;
          } else if (!selectedTrayCode.value && traysList.value && traysList.value.length > 0) {
            selectedTrayCode.value = traysList.value[0].tray_code;
          }
          loadTravelerTray();
        };'''

if old_doc_type_block in content:
    content = content.replace(old_doc_type_block, new_doc_type_block, 1)
    print("1. Replaced selectPrintDocType")
else:
    print("WARNING: old_doc_type_block not matched")

old_filtered_block = '''        const filteredPrintTrays = computed(() => {
          return traysList.value.filter(t => {
            // 0. Filter by document type (assy_rework = only trays with -PU suffix)
            const tc = (t.tray_code || '').toUpperCase();
            if (activePrintDocType.value === 'assy_rework') {
              const hasRereg = tc.includes('-PU') || (t.slots && t.slots.some(s => s.is_reregistered || s.rereg_wo_suffix));
              if (!hasRereg) return false;
            } else if (activePrintDocType.value === 'assy' || activePrintDocType.value === 'supply') {
              // Regular assy/supply: exclude -PU trays from standard assy view
              // (keep supply as-is since WO uses different logic, only exclude from assy)
              if (activePrintDocType.value === 'assy' && tc.includes('-PU')) return false;
            }

            // 0b. Dokumen dengan 0 set valid (misal seluruhnya Hold/Scrap) tidak boleh muncul
            if (typeof getPrintDocSetCount === 'function' && getPrintDocSetCount(t) <= 0) return false;

            // 1. Date filter'''

new_filtered_block = '''        const filteredPrintTrays = computed(() => {
          return traysList.value.filter(t => {
            // 1. Date filter'''

if old_filtered_block in content:
    content = content.replace(old_filtered_block, new_filtered_block, 1)
    print("2. Replaced filteredPrintTrays")
else:
    print("WARNING: old_filtered_block not matched")

# 3. Update getPrintDocSetCount
old_count_block = '''          // 3. Dokumen Perakitan (WH/ASSY & WH/ASSY-PU):
          // Menampilkan jumlah rakitan yang sudah selesai dirakit / total set dalam dokumen
          if (docType === 'assy' || docType === 'assy_rework') {
            if (isCurrent) {
              const finishedInMem = traySlots.value.filter(s => s && s.id).length;
              if (finishedInMem > 0) return finishedInMem;
            }
            const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
            const tot = Number(t.total_sets || 0);
            return fin > 0 ? fin : (tot > 0 ? tot : 1);
          }'''

new_count_block = '''          // 3. Dokumen Perakitan Ulang (WH/ASSY-PU):
          if (docType === 'assy_rework') {
            if (isCurrent) {
              const ruInMem = traySlots.value.filter(s => s && (s.is_reregistered || s.status === 'Dirakit Ulang' || s.qc_status === 'RU' || isReassemblySet(s))).length;
              if (ruInMem > 0) return ruInMem;
            }
            const ru = t.ru_count !== undefined && t.ru_count !== null ? Number(t.ru_count) : 0;
            if (ru > 0) return ru;
            const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
            const tot = Number(t.total_sets || 0);
            return fin > 0 ? fin : (tot > 0 ? tot : 15);
          }

          // 4. Dokumen Perakitan (WH/ASSY):
          if (docType === 'assy') {
            if (isCurrent) {
              const finishedInMem = traySlots.value.filter(s => s && s.id).length;
              if (finishedInMem > 0) return finishedInMem;
            }
            const fin = t.finished_count !== undefined && t.finished_count !== null ? Number(t.finished_count) : 0;
            const tot = Number(t.total_sets || 0);
            return fin > 0 ? fin : (tot > 0 ? tot : 15);
          }'''

if old_count_block in content:
    content = content.replace(old_count_block, new_count_block, 1)
    print("3. Replaced getPrintDocSetCount")
else:
    print("WARNING: old_count_block not matched")

# 4. Update travelerItems
old_traveler_items = '''        const travelerItems = computed(() => {
          return traySlots.value;
        });'''

new_traveler_items = '''        const travelerItems = computed(() => {
          const slots = traySlots.value || [];
          if (activePrintDocType.value === 'assy_rework') {
            const reworkSlots = slots.filter(s => s && (s.is_reregistered || s.status === 'Dirakit Ulang' || s.qc_status === 'RU' || isReassemblySet(s) || (s.reason && s.reason.toLowerCase().includes('dirakit ulang'))));
            if (reworkSlots.length > 0) return reworkSlots;
          }
          return slots;
        });'''

if old_traveler_items in content:
    content = content.replace(old_traveler_items, new_traveler_items, 1)
    print("4. Replaced travelerItems")
else:
    print("WARNING: old_traveler_items not matched")

# 5. Update parseWIBDate and getNowWIBDate
old_parse_wib = '''        const parseWIBDate = (dtStr) => {
          if (!dtStr) return null;
          if (dtStr instanceof Date) return dtStr;
          let str = String(dtStr).trim();
          if (!str) return null;
          if (str.endsWith('Z') || str.includes('+') || (str.length > 10 && str.substring(10).includes('-'))) {
            return new Date(str);
          }
          str = str.replace(' ', 'T');
          if (str.length === 10) str += 'T00:00:00';
          return new Date(str + '+07:00');
        };'''

new_parse_wib = '''        const parseWIBDate = (dtStr) => {
          if (!dtStr) return null;
          if (dtStr instanceof Date) return isNaN(dtStr.getTime()) ? null : dtStr;
          let str = String(dtStr).trim();
          if (!str || ['none', 'null', '-', 'undefined', 'invalid date', 'n/a', ''].includes(str.toLowerCase())) return null;
          if (str.endsWith('Z') || str.includes('+') || (str.length > 10 && str.substring(10).includes('-'))) {
            const d = new Date(str);
            return isNaN(d.getTime()) ? null : d;
          }
          str = str.replace(' ', 'T');
          if (str.length === 10) str += 'T00:00:00';
          const d = new Date(str + '+07:00');
          return isNaN(d.getTime()) ? null : d;
        };'''

if old_parse_wib in content:
    content = content.replace(old_parse_wib, new_parse_wib, 1)
    print("5. Replaced parseWIBDate")
else:
    print("WARNING: old_parse_wib not matched")

with open('static/index.html', 'w', encoding='utf-8') as f:
    f.write(content)

print("Finished updating static/index.html!")
