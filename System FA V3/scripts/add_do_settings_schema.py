with open('app.py', 'r', encoding='utf-8') as f:
    content = f.read()

target = '''                # 3. Column migration alters for existing tables'''

do_settings_schema = '''                cur.execute("""
                    CREATE TABLE IF NOT EXISTS do_settings (
                        id INTEGER PRIMARY KEY DEFAULT 1,
                        company_name TEXT DEFAULT 'PT Nanggala Kencana Rekatama Indonesia',
                        company_logo TEXT DEFAULT 'nkri-logo.png',
                        head_office TEXT DEFAULT 'Jalan Mendawai I No. 11 Kramat Pela\\nJakarta Selatan, 12130',
                        factory_address TEXT DEFAULT 'Jalan Gempol No. 9, Mekar Mulya\\nBandung, 40292',
                        contact_email TEXT DEFAULT 'info@nanggala.co.id',
                        default_customer_name TEXT DEFAULT 'KEMHAN RI',
                        default_customer_address TEXT DEFAULT 'BADAN LOGISTIK PERTAHANAN\\nJL. TANAH ABANG TIMUR NO. 6 – 7\\nJAKARTA, 10110',
                        default_customer_phone TEXT DEFAULT '(021) 3840889',
                        default_customer_fax TEXT DEFAULT '(021) 3840889',
                        default_contract_number TEXT DEFAULT 'TRAK/2509/APBN/XI/2025/KEMHAN',
                        default_program_name TEXT DEFAULT 'Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025',
                        default_product_name TEXT DEFAULT 'Nanggala P9',
                        default_issuer_name TEXT DEFAULT 'Rezka Rachdana',
                        default_issuer_title TEXT DEFAULT 'Staf',
                        default_issuer_department TEXT DEFAULT 'PPIC',
                        do_prefix TEXT DEFAULT '/SJ/PPIC/',
                        pl_prefix TEXT DEFAULT '/PL/PPIC/',
                        crate_prefix TEXT DEFAULT '2609-',
                        crate_capacity INTEGER DEFAULT 20,
                        box_capacity INTEGER DEFAULT 10,
                        tracking_base_url TEXT DEFAULT '',
                        updated_at TEXT DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("INSERT OR IGNORE INTO do_settings (id) VALUES (1);")

                # 3. Column migration alters for existing tables'''

if target in content and "CREATE TABLE IF NOT EXISTS do_settings" not in content[:3500]:
    content = content.replace(target, do_settings_schema, 1)
    with open('app.py', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added do_settings to ensure_db_schema successfully!")
else:
    print("Target not found or already added")
