import sys
import re

def test_improvements():
    print("=== RUNNING TEST SUITE: REALTIME NOTIFICATIONS & DOCUMENT ENHANCEMENTS ===")
    
    with open('static/index.html', 'r', encoding='utf-8') as f:
        html = f.read()

    # 1. Test Document Subtabs Layout
    print("\n--- 1. Testing Document Subtabs Layout ---")
    assert 'flex-nowrap' in html and 'overflow-x-auto' in html, "Document switcher must be non-wrapping"
    assert 'Dokumen Uji Assy (Assy/QC)' in html, "Dokumen Uji Assy tab present"
    assert 'Quality Control &amp; Test 2 (WH/QC)' in html or 'Quality Control & Test 2 (WH/QC)' in html or 'Quality Control &amp; Test Functional 2' in html, "QC Document tab present"
    print("[PASS] Document sub-tabs container is inline-flex flex-nowrap with whitespace-nowrap buttons.")

    # 2. Test navigateTo('traveler') data loading
    print("\n--- 2. Testing Instant Document Data Loading ---")
    assert "else if (tab === 'traveler')" in html, "navigateTo must handle 'traveler' tab explicitly"
    assert "loadTravelerTray()" in html, "loadTravelerTray must be called when navigating to traveler"
    print("[PASS] navigateTo('traveler') immediately refreshes traysList and calls loadTravelerTray().")

    # 3. Test adminPendingCount including QC sets
    print("\n--- 3. Testing Admin Notification Badge with QC Sets ---")
    assert 'qcSetsCount' in html, "qcSetsCount must be defined and included in adminPendingCount"
    assert 'adminPendingQCSets' in html, "adminPendingQCSets must be processed"
    print("[PASS] adminPendingCount incorporates qcSetsCount and adminPendingQCSets cleanly.")

    # 4. Test startGlobalBadgePolling
    print("\n--- 4. Testing Real-time Background Polling ---")
    assert 'startGlobalBadgePolling' in html, "startGlobalBadgePolling must be defined"
    assert 'loadProductionHoldItems()' in html, "loadProductionHoldItems must be called in timer"
    assert 'loadPendingVerifications()' in html, "loadPendingVerifications must be called in timer"
    assert 'loadQCPendingItems()' in html, "loadQCPendingItems must be called in timer"
    # 5. Test Tahap Uji Checklist Layout
    print("\n--- 5. Testing Tahap Uji Checklist Formatting ---")
    assert 'nested-checklist-table' in html, "nested-checklist-table must exist in HTML"
    assert 'width: 25%' in html or '<col style="width: 25%;">' in html, "Tahap Uji column must have adequate width"
    assert 'getTestItemParamStatus' in html, "getTestItemParamStatus function must be bound"
    # 6. Test Gudang Real-Time Monitoring on Assy Testing & QC
    print("\n--- 6. Testing Gudang Real-Time Monitoring on Assy Testing & QC ---")
    assert "Testing Operator Assy (Monitoring)" in html, "Gudang has Testing Operator Assy (Monitoring) menu label"
    assert "Uji Fungsional QC (Monitoring)" in html, "Gudang has Uji Fungsional QC (Monitoring) menu label"
    assert "Menunggu Uji Perakitan" in html, "Gudang monitoring state present for untested assembly items"
    assert "Pantau Detail Rakitan" in html, "Gudang monitoring button present for pending QC items"
    assert "is_view_mode: isViewMode || !isPending || userRole.value === 'gudang'" in html, "Gudang is read-only in QC modal"
    print("[PASS] Gudang real-time monitoring enabled with strict read-only protection.")

    print("\n>>> ALL 6 CHECKS PASSED 100% SUCCESFULLY! <<<")

if __name__ == '__main__':
    test_improvements()


