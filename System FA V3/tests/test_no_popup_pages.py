import os
import re

def test_html_structure():
    with open('static/index.html', 'r', encoding='utf-8') as f:
        html = f.read()

    print("=== TESTING ASSY TESTING SECTION ===")
    assert 'section v-if="currentTab === \'assy_testing\'"' in html, "assy_testing section not found"
    assert '<div v-if="!showAssyTestModal"' in html, "View 1 in assy_testing not found"
    assert '<!-- ================= VIEW 2: HALAMAN PENUH UJI FUNGSIONAL OPERATOR ASSEMBLY (BUKA PAGE BARU) ================= -->' in html, "View 2 header comment not found"
    assert 'Tahap: <strong class="text-indigo-700 font-black">{{ assyTestStep + 1 }}</strong> / 15' in html, "Step counter in header not found"
    assert 'v-if="assyTestStep < 15"' in html, "Step-by-step section not found"
    assert 'chooseAssyTestStatus(\'GOOD\')' in html, "chooseAssyTestStatus GOOD button not found"
    assert 'chooseAssyTestStatus(\'NOT_GOOD\')' in html, "chooseAssyTestStatus NOT_GOOD button not found"
    assert 'v-for="t in assyTestForm.checklist" :key="t.test_num"' in html, "Single table checklist loop in assy testing not found"
    print("[PASS] Tab Test Assembly memiliki View 1 (Slot), View 2 (Full Page Step-by-Step + Single Table No-Scroll)")

    print("\n=== TESTING QC SECTION ===")
    assert 'section v-if="currentTab === \'qc\'"' in html, "qc section not found"
    assert 'v-if="!selectedQCTrayCode"' in html, "QC View 1 not found"
    assert 'v-else-if="!showQCTestModal"' in html, "QC View 2 not found"
    assert '<!-- ================= TAB QC: VIEW 3 - HALAMAN PENUH PEMERIKSAAN MUTU QC (BUKA PAGE BARU) ================= -->' in html, "QC View 3 comment not found"
    assert 'Kembali ke Daftar Rakitan Tray ({{ selectedQCTrayCode }})' in html, "Back button in QC View 3 not found"
    assert 'v-for="t in (qcTestForm.assy_test_checklist || [])" :key="t.test_num"' in html, "Single table checklist loop in QC View 3 not found"
    print("[PASS] Tab QC memiliki View 1 (Daftar Tray), View 2 (Daftar Rakitan), View 3 (Full Page Pemeriksaan Mutu QC + Single Table No-Scroll)")

    print("\n=== VERIFYING REMOVAL OF OLD FLOATING DIALOG MODALS ===")
    assert not re.search(r'<div\s+v-if="showAssyTestModal"\s+class="fixed inset-0', html), "Floating modal showAssyTestModal still exists!"
    assert not re.search(r'<div\s+v-if="showQCTestModal"\s+class="fixed inset-0', html), "Floating modal showQCTestModal still exists!"
    print("[PASS] Modal dialog mengambang (floating modal) lama showAssyTestModal dan showQCTestModal telah berhasil dihapus!")

    print("\n=== VERIFYING FORMAL SINGLE HTML TABLE FORMAT ===")
    assert '<table class="w-full text-left text-xs border-collapse">' in html, "Formal table element not found"
    assert '>Deskripsi</th>' in html, "Deskripsi header column not found"
    assert '>Status</th>' in html, "Status header column not found"
    print("[PASS] Elemen <table> tunggal formal lengkap dengan thead (No | Deskripsi | Status) terverifikasi!")

    print("\n=== VERIFYING NO-SCROLL TABLE CONTAINERS ===")
    # Pastikan tidak ada max-h-[...] overflow-y-auto pada tabel hasil uji
    assy_section = html[html.find('VIEW 2: HALAMAN PENUH UJI FUNGSIONAL'):html.find('<!-- ================= MODAL FORM ASSEMBLY ITEM')]
    assert 'max-h-[320px] overflow-y-auto' not in assy_section, "Found scroll container in assy test results table!"
    assert 'max-h-[260px] overflow-y-auto' not in html[html.find('VIEW 3 - HALAMAN PENUH PEMERIKSAAN MUTU QC'):html.find('<!-- ================= TAB: BARANG PRODUKSI')], "Found scroll container in QC test results table!"
    print("[PASS] Tidak ada container vertikal ber-scroll (max-h-X overflow-y-auto) pada tabel hasil pengujian!")

    print("\nALL AUTOMATED VERIFICATION CHECKS PASSED 100%!")

if __name__ == '__main__':
    test_html_structure()
