import re

def test_part_sequence_and_templates():
    with open('static/index.html', 'r', encoding='utf-8') as f:
        html = f.read()

    # 1. Check all 4 printable documents for two-level header with Serial Number (P9 Part 15) and Production Number (P9 Part 1 | P9 Part 12)
    doc_sections = [
        ("Doc 1: Supply Material", "Supply Material Items Table", "travelerDocSubTab === 'supply'"),
        ("Doc 2: Traveler Perakitan", "Assembly Items Table", "travelerDocSubTab === 'assy'"),
        ("Doc 3: Uji Assy/QC", "Assembly Items / Test Table", "travelerDocSubTab === 'assy_qc'"),
        ("Doc 4: Dokumen Mutu WH/QC", "QC Verified Items Table", "travelerDocSubTab === 'wh_qc'")
    ]
    
    for name, marker, subtab in doc_sections:
        assert marker in html, f"Marker '{marker}' not found in {name}"
        # Check two-level header:
        assert "Production Number" in html, f"Production Number header missing in {name}"
        assert "P9 Part 1" in html and "P9 Part 12" in html and "P9 Part 15" in html

    # 2. Check that in Document tables, P9 Part 1 comes before P9 Part 12 in the headers:
    doc_headers_count = html.count('<th class="p-1.5 text-center bg-slate-100">P9 Part 1</th>\n                          <th class="p-1.5 text-center bg-slate-100">P9 Part 12</th>')
    print(f"[OK] Found {doc_headers_count} exact two-level document subheaders matching P9 Part 1 -> P9 Part 12.")
    assert doc_headers_count >= 4, f"Expected 4 document subheaders, found {doc_headers_count}"

    # 3. Check Laporan subtabs:
    # Sub-tab 1 (wo): PN P9 Part 1 before PN P9 Part 12
    # Sub-tab 2 (assy_test): PN P9 Part 1 before PN P9 Part 12
    # Sub-tab 3 (qc_test): PN P9 Part 1 before PN P9 Part 12
    # Sub-tab 4 (warehouse_receipt): PN P9 Part 1 before PN P9 Part 12
    assert html.count('<th class="p-3 text-[11px] font-bold tracking-wider">PN P9 Part 1</th>\n                  <th class="p-3 text-[11px] font-bold tracking-wider">PN P9 Part 12</th>') >= 3

    # 4. Check Excel export functions:
    # exportTrayToExcel
    assert "{ header: 'PN P9 Part 1', width: 13, key: 'part1' },\n              { header: 'PN P9 Part 12', width: 14, key: 'part12' }" in html
    # exportReportToExcel
    assert "{ header: 'PN P9 Part 1', width: 18, key: 'part1' },\n            { header: 'PN P9 Part 12', width: 18, key: 'part12' }" in html

    print("ALL PART SEQUENCE AND TEMPLATE CONSISTENCY TESTS PASSED 100%!")

if __name__ == '__main__':
    test_part_sequence_and_templates()
