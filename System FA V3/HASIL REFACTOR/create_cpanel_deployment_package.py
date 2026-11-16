# create_cpanel_deployment_package.py
"""
Script Pembuat Paket Deployment cPanel Otomatis
Sistem Final Assembly & Traceability Nanggala P9
"""

import os
import zipfile
import shutil

ROOT_DIR = os.path.dirname(os.path.abspath(__file__))
ZIP_OUTPUT = os.path.join(ROOT_DIR, "SOURCE_CODE_SIAP_DEPLOY_CPANEL.zip")

# Folder yang dimasukkan
INCLUDE_DIRS = [
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "vendor",
    "storage"
]

# File root yang dimasukkan
INCLUDE_FILES = [
    "artisan",
    "composer.json",
    "composer.lock",
    ".env.example",
    ".env.cpanel-production",
    ".htaccess",
    "README.md",
    "package.json",
    "tailwind.config.js"
]

# Pola yang dikecualikan (exclude)
EXCLUDE_EXTS = {".py", ".sql", ".pdf", ".bak", ".log"}
EXCLUDE_DIRS = {
    ".git",
    ".agents",
    "archive_monolith",
    "scratch",
    "__pycache__",
    "node_modules",
    ".gemini"
}

def make_clean_zip():
    print(f"[INFO] Memulai pembuatan paket deployment cPanel: {ZIP_OUTPUT}")
    
    # Hapus file zip lama jika ada
    if os.path.exists(ZIP_OUTPUT):
        os.remove(ZIP_OUTPUT)
        
    total_files = 0
    total_size = 0

    with zipfile.ZipFile(ZIP_OUTPUT, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as zipf:
        # 1. Tambahkan file root
        for fname in INCLUDE_FILES:
            fpath = os.path.join(ROOT_DIR, fname)
            if os.path.isfile(fpath):
                zipf.write(fpath, fname)
                total_files += 1
                total_size += os.path.getsize(fpath)
                print(f"  [+] File: {fname}")

        # 2. Tambahkan direktori
        for dname in INCLUDE_DIRS:
            dpath = os.path.join(ROOT_DIR, dname)
            if not os.path.isdir(dpath):
                continue
                
            print(f"  [+] Direktori: {dname}/...")
            for root, dirs, files in os.walk(dpath):
                # Filter folder yang dikecualikan
                dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]
                
                # Khusus storage: jangan masukkan cache file atau log error
                rel_root = os.path.relpath(root, ROOT_DIR)
                is_storage_views = "storage\\framework\\views" in rel_root or "storage/framework/views" in rel_root
                is_storage_sessions = "storage\\framework\\sessions" in rel_root or "storage/framework/sessions" in rel_root
                is_storage_logs = "storage\\logs" in rel_root or "storage/logs" in rel_root

                # Pastikan direktori kosong di dalam storage tetap tercatat dalam zip
                if len(files) == 0:
                    dir_arc = rel_root.replace('\\', '/') + '/'
                    zipf.writestr(dir_arc, '')

                for file in files:
                    ext = os.path.splitext(file)[1].lower()
                    
                    # Skip log files & views cache
                    if (is_storage_logs and ext == ".log") or (is_storage_views and ext == ".php"):
                        continue
                    if is_storage_sessions and file != ".gitignore":
                        continue
                    if ext in EXCLUDE_EXTS and not rel_root.startswith("vendor"):
                        continue

                    full_path = os.path.join(root, file)
                    rel_path = os.path.relpath(full_path, ROOT_DIR)
                    zipf.write(full_path, rel_path.replace('\\', '/'))
                    total_files += 1
                    total_size += os.path.getsize(full_path)

        # Pastikan struktur folder storage dasar ada
        storage_dirs = [
            "storage/app/public/",
            "storage/framework/cache/data/",
            "storage/framework/sessions/",
            "storage/framework/views/",
            "storage/logs/"
        ]
        for sdir in storage_dirs:
            try:
                zipf.writestr(sdir + '.gitkeep', '')
            except Exception:
                pass

    zip_size_mb = os.path.getsize(ZIP_OUTPUT) / (1024 * 1024)
    print("\n[SUCCESS] Pembuatan paket deployment selesai!")
    print(f"Total File Dimasukkan : {total_files} berkas")
    print(f"Ukuran Asli           : {total_size / (1024 * 1024):.2f} MB")
    print(f"Ukuran File ZIP Akhir : {zip_size_mb:.2f} MB")
    print(f"Lokasi File ZIP       : {ZIP_OUTPUT}")

if __name__ == '__main__':
    make_clean_zip()
