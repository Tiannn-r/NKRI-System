import unittest
import re
import json
import sqlite3
import os
import sys

class TestLatest6UserRevisions(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        with open("static/index.html", "r", encoding="utf-8") as f:
            cls.html = f.read()

        with open("app.py", "r", encoding="utf-8") as f:
            cls.app_code = f.read()

    def test_point_1_assy_test_sequential_and_table(self):
        """1. Revisi Fitur Test Assembly: Sequential 1-15 step-by-step, Good/Not Good, Kembali, Step 15 Table Review."""
        self.assertTrue("assyTestStep < 15" in self.html, "Missing assyTestStep < 15")
        self.assertTrue("Test {{ assyTestStep + 1 }} dari 15" in self.html, "Missing Test count")
        self.assertTrue("chooseAssyTestStatus('GOOD')" in self.html, "Missing chooseAssyTestStatus GOOD")
        self.assertTrue("chooseAssyTestStatus('NOT_GOOD')" in self.html, "Missing chooseAssyTestStatus NOT_GOOD")
        self.assertTrue("prevAssyTestStep" in self.html, "Missing prevAssyTestStep")

        # Step 15 review table (No | Deskripsi | Status)
        self.assertTrue("15 Tahap Pengujian Selesai" in self.html, "Missing Step 15 heading")
        self.assertTrue(">Deskripsi<" in self.html, "Missing Deskripsi header in table")
        self.assertTrue("submitAssyTest" in self.html, "Missing submitAssyTest")

    def test_point_2_remove_cetak_label_from_perakitan_detail(self):
        """2. Pada perakitan ketika lihat detail hilangkan button cetak label."""
        start_idx = self.html.find('v-if="showDetailModal && activeDetailSlot"')
        self.assertNotEqual(start_idx, -1)
        end_idx = self.html.find('v-if="showResetDbModal"', start_idx)
        detail_modal_chunk = self.html[start_idx:end_idx]

        self.assertTrue("printP9Label" not in detail_modal_chunk, "printP9Label should not be in showDetailModal")
        self.assertTrue("openLabelPreview" not in detail_modal_chunk, "openLabelPreview should not be in showDetailModal")
        self.assertTrue("Cetak Label" not in detail_modal_chunk, "Cetak Label should not be in showDetailModal")

    def test_point_3_verification_lock_permanent(self):
        """3. Verifikasi ketika selesai maka lock tidak bisa diubah kembali."""
        start_idx = self.html.find('v-if="showQCWarehouseActualVerifyModal"')
        self.assertNotEqual(start_idx, -1)
        end_idx = self.html.find('v-if="showWarehouseQCModal"', start_idx)
        verify_modal_chunk = self.html[start_idx:end_idx]

        self.assertTrue("Ubah Keputusan" not in verify_modal_chunk, "Ubah Keputusan should not be present")
        self.assertTrue("Ubah Status" not in verify_modal_chunk, "Ubah Status should not be present")
        self.assertTrue("ACC (Terkunci)" in verify_modal_chunk, "ACC (Terkunci) should be present")
        self.assertTrue("DECLINED (Terkunci)" in verify_modal_chunk, "DECLINED (Terkunci) should be present")

        # Frontend: Traveler button shows locked badge if verified
        self.assertTrue("Terverifikasi Fisik (Terkunci)" in self.html, "Terverifikasi Fisik (Terkunci) should be in traveler")

        # Backend app.py checks immutability
        self.assertTrue("Sudah diverifikasi fisik oleh Gudang dan terkunci permanen" in self.app_code, "Missing gudang receipt lock")
        self.assertTrue("Tray pengembalian ini sudah selesai diverifikasi dan terkunci" in self.app_code, "Missing return tray lock")
        self.assertTrue("sudah selesai diverifikasi dan terkunci" in self.app_code, "Missing return set lock")

    def test_point_4_remove_salin_zpl_and_print_langsung_from_label(self):
        """4. Hilangkan salin zpl dan print langsung di cetak label."""
        start_idx = self.html.find('v-if="showLabelPreviewModal"')
        self.assertNotEqual(start_idx, -1)
        end_idx = self.html.find('v-if="showStatusDrillModal"', start_idx)
        label_modal_chunk = self.html[start_idx:end_idx]

        self.assertTrue("copyZPLCode" not in label_modal_chunk, "copyZPLCode should be removed")
        self.assertTrue("Salin ZPL" not in label_modal_chunk, "Salin ZPL should be removed")
        self.assertTrue("printP9LabelDirect" not in label_modal_chunk, "printP9LabelDirect should be removed")
        self.assertTrue("Print Langsung" not in label_modal_chunk, "Print Langsung should be removed")
        self.assertTrue("downloadP9LabelPDF" in label_modal_chunk, "downloadP9LabelPDF should be present")
        self.assertTrue("Unduh PDF" in label_modal_chunk, "Unduh PDF should be present")

    def test_point_5_qc_page_table_first_and_card_test_table(self):
        """5. Halaman QC awal dibuka berbentuk tabel dokumen tray, tombol buka, setelah dibuka card per set dengan tabel hasil uji perakitan."""
        start_idx = self.html.find("<section v-if=\"currentTab === 'qc'\"")
        self.assertNotEqual(start_idx, -1)
        end_idx = self.html.find("<section v-if=\"currentTab === 'production_hold'\"", start_idx)
        qc_chunk = self.html[start_idx:end_idx]

        # Initial view: table of tray documents
        self.assertTrue("!selectedQCTrayCode" in qc_chunk, "Missing !selectedQCTrayCode condition")
        self.assertTrue("Nomor Dokumen Tray" in qc_chunk, "Missing Nomor Dokumen Tray column")
        self.assertTrue("Total Set Siap QC" in qc_chunk, "Missing Total Set Siap QC column")
        self.assertTrue("openQCTrayDetail(t.tray_code)" in qc_chunk, "Missing openQCTrayDetail click")
        self.assertTrue("Buka" in qc_chunk, "Missing Buka button")

        # Detail view: card grid
        self.assertTrue("Kembali ke Daftar Dokumen Tray" in qc_chunk, "Missing Kembali button")
        self.assertTrue("selectedQCTrayCode = null" in qc_chunk, "Missing selectedQCTrayCode reset")
        self.assertTrue("filteredQCList" in qc_chunk, "Missing filteredQCList iteration")

        # Inside card: test assembly table format
        self.assertTrue("Hasil Uji Perakitan (15 Tahap)" in qc_chunk, "Missing Hasil Uji Perakitan heading")
        self.assertTrue("getParsedAssyChecklist(item)" in qc_chunk, "Missing getParsedAssyChecklist call")

        # Continues with QC flow buttons
        self.assertTrue("openQCTestModal(item, false)" in qc_chunk, "Missing openQCTestModal action")
        self.assertTrue("Pilih / Proses QC" in qc_chunk, "Missing Pilih / Proses QC button")
        self.assertTrue("printWHQCDoc" in qc_chunk, "Missing printWHQCDoc action")

    def test_point_6_document_system_actual_status_and_filter_good(self):
        """6. Sistem dokumen menampilkan data aktual, status aktual, dan filter good saja."""
        self.assertTrue('v-model="printStatusFilter"' in self.html, "Missing printStatusFilter v-model")
        self.assertTrue('<option value="ALL">Semua Status</option>' in self.html, "Missing Semua Status option")
        self.assertTrue('<option value="GOOD">Hanya Good Saja</option>' in self.html, "Missing Hanya Good Saja option")

        # assy_qc table uses printStatusFilter and shows actual status
        self.assertTrue("printStatusFilter !== 'GOOD' || (i.status === 'Good'" in self.html, "Missing assy_qc printStatusFilter check")
        self.assertTrue("GOOD (LOLOS)" in self.html, "Missing GOOD (LOLOS) badge")
        self.assertTrue("HOLD (GAGAL)" in self.html, "Missing HOLD (GAGAL) badge")

        # wh_qc table uses printStatusFilter
        self.assertTrue("printStatusFilter !== 'GOOD' || (i.warehouse_receipt_status === 'ACCEPTED'" in self.html, "Missing wh_qc printStatusFilter check")

    def test_vue_exports_integrity(self):
        """Verify that all new variables and functions are exported in Vue setup return block."""
        last_return_idx = self.html.rfind("return {")
        self.assertNotEqual(last_return_idx, -1)
        ret_block = self.html[last_return_idx:self.html.find("};", last_return_idx)]
        returned_keys = set(re.findall(r'(\b[a-zA-Z0-9_$]+\b)', ret_block))

        new_keys = [
            'assyTestStep', 'chooseAssyTestStatus', 'prevAssyTestStep',
            'printStatusFilter', 'selectedQCTrayCode', 'openQCTrayDetail',
            'getParsedAssyChecklist', 'qcTraySummaryList'
        ]
        for k in new_keys:
            self.assertTrue(k in returned_keys, f"Key {k} must be exported in Vue return statement")

if __name__ == "__main__":
    unittest.main()
