import os

def clean_file(path, replacements):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    orig = content
    for old, new in replacements:
        content = content.replace(old, new)
    if content != orig:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"[UPDATED] {path}")
    else:
        print(f"[NO CHANGE] {path}")

# 1. delivery-orders/index.blade.php
do_path = os.path.join('resources', 'views', 'modules', 'delivery-orders', 'index.blade.php')
clean_file(do_path, [
    ('title="Cloudinary Aktif & Siap Scan Luar"', 'title="Cloudinary Aktif & Siap Akses Luar"'),
    ('placeholder="Scan / ketik SN..."', 'placeholder="Pilih / ketik SN..."'),
    ('<!-- Petunjuk Input / Scanner (Sama Seperti di Gudang) -->', '<!-- Petunjuk Input Serial Number (Sama Seperti di Gudang) -->'),
    ('<i class="fa-solid fa-barcode text-emerald-600 text-sm"></i>', '<i class="fa-solid fa-keyboard text-emerald-600 text-sm"></i>'),
    ('Ketik atau scan <strong>Serial Number</strong> unit.', 'Ketik atau pilih <strong>Serial Number</strong> unit.'),
    ('title="Buka langsung file PDF di Cloudinary (Akses Publik / Scanner HP)"', 'title="Buka langsung file PDF di Cloudinary (Akses Publik / Handphone)"'),
    ('agar kamera HP yang men-scan di jaringan Wi-Fi bisa langsung', 'agar perangkat HP yang terhubung ke jaringan Wi-Fi bisa langsung'),
    ('QR Code di Surat Jalan dapat langsung di-scan dari HP /', 'QR Code di Surat Jalan dapat langsung diakses publik dari HP /'),
])

# 2. admin.blade.php
admin_path = os.path.join('resources', 'views', 'admin.blade.php')
clean_file(admin_path, [
    ('agar QR Code fisik dapat di-scan dari luar jaringan pabrik.', 'agar dokumen fisik dapat diakses secara publik dari luar jaringan pabrik.'),
])

# 3. public/js/app.js
app_js_path = os.path.join('public', 'js', 'app.js')
clean_file(app_js_path, [
    ("{ key: 'warehouse', title: 'Work Order (Input Part)', icon: 'fa-solid fa-boxes-packing', desc: 'Pembuatan work order & scan part gudang' }",
     "{ key: 'warehouse', title: 'Work Order (Input Part)', icon: 'fa-solid fa-boxes-packing', desc: 'Pembuatan work order & input part gudang' }"),
    ("{ key: 'assembly', title: 'Area Perakitan (Assembly)', icon: 'fa-solid fa-gear', desc: 'Meja kerja rakit & scan part operator' }",
     "{ key: 'assembly', title: 'Area Perakitan (Assembly)', icon: 'fa-solid fa-gear', desc: 'Meja kerja rakit & pengerjaan part operator' }"),
    ('agar QR Code dapat di-scan dari luar jaringan.', 'agar dokumen Packing List dapat diakses publik dari luar jaringan.')
])

# 4. public/tracking_pl.html
tracking_path = os.path.join('public', 'tracking_pl.html')
clean_file(tracking_path, [
    ("showError('Parameter Belum Diisi', 'Silakan scan QR Code yang tertera pada dokumen fisik atau masukkan nomor DO/PL.');",
     "showError('Parameter Belum Diisi', 'Silakan buka tautan dokumen fisik atau masukkan nomor DO/PL.');")
])

print("All files cleaned.")
