import socket

def get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

import os
import json
import random
import string
import base64
import uuid
import re
import sqlite3
import time
import threading
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
import datetime as dt_mod
from datetime import datetime, date, timezone, timedelta
try:
    import pl_generator
except Exception as _ex_pl:
    pl_generator = None
    print(f"[Warning] pl_generator tidak dapat dimuat ({_ex_pl}). Pasang reportlab dengan: pip install reportlab", flush=True)

PORT = int(os.environ.get("PORT", 8000))
STATIC_DIR = os.path.join(os.path.dirname(__file__), "static")
DB_PATH = os.environ.get("DB_PATH", os.path.join(os.path.dirname(os.path.abspath(__file__)), "database.db"))
DB_URL = DB_PATH

SESSIONS = {}
_db_lock = threading.Lock()

class ContextCursor(sqlite3.Cursor):
    def __enter__(self):
        return self
    def __exit__(self, exc_type, exc_val, exc_tb):
        self.close()
    def _normalize_sql(self, sql):
        if '%s' in sql:
            sql = sql.replace('%s', '?')
        if 'NOW()' in sql:
            sql = sql.replace('NOW()', "datetime('now', 'localtime')")
        sql = re.sub(r"datetime\(\s*'now'\s*\)", "datetime('now', 'localtime')", sql)
        sql = re.sub(r'datetime\(\s*"now"\s*\)', "datetime('now', 'localtime')", sql)
        sql = re.sub(r"datetime\(\s*'now'\s*,\s*'-30 minutes'\s*\)", "datetime('now', 'localtime', '-30 minutes')", sql)
        sql = re.sub(r"unixepoch\(\s*'now'\s*\)", "unixepoch(datetime('now', 'localtime'))", sql)
        if ' ILIKE ' in sql:
            sql = sql.replace(' ILIKE ', ' LIKE ')
        if '::' in sql:
            sql = re.sub(r'::[a-zA-Z]+', '', sql)
        return sql
    def execute(self, sql, params=None):
        sql = self._normalize_sql(sql)
        if params is not None and re.search(r'=\s*ANY\s*\(\s*\?\s*\)', sql, re.IGNORECASE):
            token_pattern = re.compile(r'(=\s*ANY\s*\(\s*\?\s*\)|\?)', re.IGNORECASE)
            tokens = token_pattern.split(sql)
            new_sql_parts = []
            new_params = []
            p_idx = 0
            for part in tokens:
                if token_pattern.fullmatch(part):
                    if p_idx < len(params):
                        val = params[p_idx]
                        p_idx += 1
                        if re.search(r'ANY', part, re.IGNORECASE):
                            if isinstance(val, (list, tuple, set)):
                                val_list = list(val)
                                placeholders = ', '.join(['?'] * len(val_list)) if val_list else 'NULL'
                                new_sql_parts.append(f"IN ({placeholders})")
                                new_params.extend(val_list)
                            else:
                                new_sql_parts.append("= ?")
                                new_params.append(val)
                        else:
                            new_sql_parts.append("?")
                            new_params.append(val)
                    else:
                        new_sql_parts.append(part)
                else:
                    new_sql_parts.append(part)
            if p_idx < len(params):
                new_params.extend(params[p_idx:])
            sql = ''.join(new_sql_parts)
            params = tuple(new_params)
        if params is None:
            return super().execute(sql)
        return super().execute(sql, params)
    def executemany(self, sql, seq_of_params):
        sql = self._normalize_sql(sql)
        return super().executemany(sql, seq_of_params)

class ContextConnection(sqlite3.Connection):
    def cursor(self, factory=ContextCursor):
        return super().cursor(factory=factory)

BOOLEAN_COLUMNS = {
    'verified_by_gudang', 'is_declined', 'is_reregistered',
    'return_notified', 'return_verified', 'is_active_elsewhere'
}

def dict_factory(cursor, row):
    d = {}
    for idx, col in enumerate(cursor.description):
        col_name = col[0]
        val = row[idx]
        if col_name in BOOLEAN_COLUMNS and val is not None:
            val = bool(val)
        d[col_name] = val
    return d

def get_conn():
    conn = sqlite3.connect(DB_PATH, check_same_thread=False, timeout=30, factory=ContextConnection)
    conn.row_factory = dict_factory
    conn.create_function("REGEXP", 2, lambda pat, item: bool(re.search(pat, str(item or ''))))
    conn.create_function("GREATEST", -1, lambda *args: max([a for a in args if a is not None], default=None))
    conn.create_function("LEAST", -1, lambda *args: min([a for a in args if a is not None], default=None))
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")
    conn.execute("PRAGMA synchronous=NORMAL")
    conn.execute("PRAGMA cache_size=-65536")    # 64MB page cache (Fix A)
    conn.execute("PRAGMA mmap_size=268435456")  # 256MB memory-mapped I/O (Fix A)
    conn.execute("PRAGMA temp_store=MEMORY")    # temp tables in RAM (Fix A)
    return conn

def send_json(handler, data, status=200):
    try:
        body = json.dumps(data, default=str).encode("utf-8")
        handler.send_response(status)
        handler.send_header("Content-Type", "application/json; charset=utf-8")
        handler.send_header("Access-Control-Allow-Origin", "*")
        handler.send_header("Access-Control-Allow-Headers", "*")
        handler.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        handler.send_header("Content-Length", str(len(body)))
        handler.end_headers()
        handler.wfile.write(body)
        handler.wfile.flush()
    except (ConnectionResetError, BrokenPipeError, ConnectionAbortedError):
        pass
    except Exception as ex:
        if "10053" not in str(ex) and "10054" not in str(ex):
            print(f"Error in send_json: {ex}", flush=True)

def check_auth(handler):
    auth = handler.headers.get("Authorization")
    if not auth or not auth.startswith("Bearer "):
        return None
    token = auth.split(" ")[1]
    # Fix C: Check in-memory cache first (60-second TTL)
    _now = time.time()
    _cached = SESSIONS.get(token)
    if _cached and _cached.get("_cached_at", 0) > _now - 60:
        return _cached
    try:
        with get_conn() as conn:
            with conn.cursor() as cur:
                # Check active session within 30 minutes activity window
                cur.execute("""
                    SELECT s.user_id as id, s.username, s.role, s.full_name, s.nik, s.last_activity,
                           COALESCE(u.department, 'assembly') as department,
                           COALESCE(u.permissions, '{}') as permissions,
                           COALESCE(u.role, s.role) as user_role
                    FROM user_sessions s
                    LEFT JOIN users u ON s.user_id = u.id
                    WHERE s.token = ? AND (s.last_activity IS NULL OR s.last_activity >= datetime('now', '-30 minutes'))
                """, (token,))
                sess = cur.fetchone()
                if sess:
                    # Refresh last activity timestamp
                    cur.execute("UPDATE user_sessions SET last_activity = datetime('now', 'localtime') WHERE token = ?", (token,))
                    conn.commit()
                    u_data = dict(sess)
                    u_data['role'] = (u_data.get('user_role') or u_data.get('role') or 'user').strip().lower()
                    u_data['is_admin'] = is_super_admin(u_data) or is_admin_user(u_data)
                    u_data["_cached_at"] = time.time()  # Fix C: TTL timestamp
                    SESSIONS[token] = u_data
                    return u_data
                else:
                    SESSIONS.pop(token, None)
                    cur.execute("DELETE FROM user_sessions WHERE token = ? OR (last_activity IS NOT NULL AND last_activity < datetime('now', '-30 minutes'))", (token,))
                    conn.commit()
    except Exception as ex:
        print(f"Error in check_auth: {ex}", flush=True)
    return None

def is_super_admin(user):
    """Akun root permanen yang selalu memiliki akses penuh ke seluruh modul sistem."""
    if not user:
        return False
    u = (user.get('username') or '').strip().lower()
    fn = (user.get('full_name') or '').strip().lower()
    return u in ('admin', 'miranti') or 'miranti' in fn

def is_admin_user(user):
    """Pengecekan administrator: super admin (admin/miranti) atau user dengan hak tulis kelola pengguna."""
    if not user:
        return False
    if is_super_admin(user):
        return True
    return user_has_perm(user, 'users', require_write=True)

def user_has_perm(user, perm_name, require_write=False):
    """Cek apakah user memiliki permission tertentu secara manual.
    require_write=True: hanya level 2 (full/write) yang diizinkan (untuk create/update/delete/approve).
    require_write=False: level 1 (read) dan 2 (full/write) diizinkan (untuk view/list)."""
    if not user:
        return False
    # Akun admin dan miranti selalu memiliki semua hak akses (super-admin guarantee)
    if is_super_admin(user):
        return True
    raw_perms = user.get('permissions')
    if not raw_perms:
        return False
    if isinstance(raw_perms, str):
        try:
            raw_perms = json.loads(raw_perms)
        except Exception:
            return False
    if isinstance(raw_perms, dict):
        val = raw_perms.get(perm_name)
        # Fallback & alias pairs
        if val is None:
            if perm_name in ('verify_set', 'verify_part', 'verify_replacement'):
                val = raw_perms.get('verify_items')
            elif perm_name == 'verify_items':
                val = raw_perms.get('verify_set') if raw_perms.get('verify_set') is not None else (raw_perms.get('verify_part') if raw_perms.get('verify_part') is not None else raw_perms.get('verify_replacement'))
            elif perm_name == 'warehouse':
                val = raw_perms.get('work_order')
            elif perm_name == 'work_order':
                val = raw_perms.get('warehouse')
            elif perm_name == 'report':
                val = raw_perms.get('history')
            elif perm_name == 'history':
                val = raw_perms.get('report')
            elif perm_name == 'qc':
                val = raw_perms.get('functional_test')
            elif perm_name == 'functional_test':
                val = raw_perms.get('qc')
            elif perm_name == 'functional_hold':
                val = raw_perms.get('production_hold')

        if require_write:
            # Hanya level 2 (full) yang bisa melakukan aksi tulis (create/update/delete/approve)
            return val in (2, '2', 'full')
        else:
            # Level 1 (read) dan 2 (full) bisa mengakses/membaca
            return val in (2, '2', 'full', True, 1, '1', 'read')
    return False

def normalize_sn(part_type, sn_raw):
    s = (sn_raw or "").strip().upper()
    if part_type == '1':
        # Accepts '1'..'999' -> '001'..'999' (3 digits)
        # Accepts '1000'..'6000' -> '1000'..'6000' (4 digits)
        if s.isdigit():
            val = int(s)
            if 1 <= val <= 999:
                return f"{val:03d}"
            elif 1000 <= val <= 6000:
                return f"{val:04d}"
        return s
    elif part_type == '12':
        # Accepts '60001'..'63500' and '70001'..'71500' (5 digits)
        clean = s.replace(".", "").replace(",", "").replace(" ", "")
        if clean.isdigit():
            val = int(clean)
            if (60001 <= val <= 63500) or (70001 <= val <= 71500):
                return str(val)
        return clean
    elif part_type == '15':
        return s.replace(" ", "")
    return s

def get_tray_doc_number(tray_code, stage):
    if not tray_code:
        code_str = ""
    else:
        code_str = str(tray_code).strip()
    
    stage_upper = str(stage or '').upper()
    is_pu = bool('-PU' in code_str.upper() or 'ASSY-PU' in code_str.upper() or '/PU/' in code_str.upper())
    
    if is_pu:
        m_full = re.search(r'WH/ASSY-PU/(\d+)', code_str, re.IGNORECASE)
        if m_full:
            seq = m_full.group(1).zfill(6)
        else:
            m_old = re.search(r'-PU(\d+)', code_str, re.IGNORECASE)
            if m_old:
                seq = str(int(m_old.group(1))).zfill(6)
            else:
                digits = re.findall(r'\d+', code_str)
                seq = digits[-1].zfill(6) if digits else '000001'
        
        if stage_upper in ['ASSY', 'TF1', 'ASSY_TEST']:
            return f"Assy/QC/PU/{seq}"
        elif stage_upper in ['QC', 'QC_TEST']:
            return f"WH/QC/PU/{seq}"
        elif stage_upper in ['GR', 'WAREHOUSE', 'RECEIPT', 'WH_GR']:
            return f"WH/GR/PU/{seq}"
        elif stage_upper in ['WO', 'SUPPLY', 'WH_WO']:
            return f"WH/WO/PU/{seq}"
        elif stage_upper in ['MO', 'WH_MO']:
            return f"WH/MO/PU/{seq}"
        return f"Assy/QC/PU/{seq}"
    else:
        digits = re.findall(r'\d+', code_str)
        seq = digits[-1].zfill(6) if digits else '000001'
        if stage_upper in ['ASSY', 'TF1', 'ASSY_TEST']:
            return f"Assy/QC/{seq}"
        elif stage_upper in ['QC', 'QC_TEST']:
            return f"WH/QC/{seq}"
        elif stage_upper in ['GR', 'WAREHOUSE', 'RECEIPT', 'WH_GR']:
            return f"WH/GR/{seq}"
        elif stage_upper in ['WO', 'SUPPLY', 'WH_WO']:
            return f"WH/WO/{seq}"
        elif stage_upper in ['MO', 'WH_MO']:
            return f"WH/MO/{seq}"
        return f"Assy/QC/{seq}"

def get_next_doc_number(cur, stage):
    prefix = "Assy/QC/" if stage == "ASSY" else "WH/QC/"
    cur.execute("SELECT doc_number FROM assembly_tests WHERE stage = ? AND doc_number LIKE ?", (stage, prefix + '%'))
    r1 = cur.fetchall()
    col = 'assy_doc_number' if stage == 'ASSY' else 'qc_doc_number'
    cur.execute(f"SELECT {col} FROM assemblies WHERE {col} LIKE ?", (prefix + '%',))
    r2 = cur.fetchall()
    
    max_val = 0
    for r in r1:
        doc = str(r['doc_number'] or '') if 'doc_number' in r else ''
        m = re.search(r'/(\d+)$', doc)
        if m:
            max_val = max(max_val, int(m.group(1)))
    for r in r2:
        doc = str(r[col] or '') if col in r else ''
        m = re.search(r'/(\d+)$', doc)
        if m:
            max_val = max(max_val, int(m.group(1)))
            
    val = max_val + 1
    candidate = f"{prefix}{val:06d}"
    while True:
        cur.execute("SELECT 1 FROM assembly_tests WHERE stage = ? AND doc_number = ?", (stage, candidate))
        in_tests = cur.fetchone()
        cur.execute(f"SELECT 1 FROM assemblies WHERE {col} = ?", (candidate,))
        in_assy = cur.fetchone()
        if not in_tests and not in_assy:
            break
        val += 1
        candidate = f"{prefix}{val:06d}"
    return candidate

# ==============================================================================
# CLOUDINARY INTEGRATION — File PDF Packing List untuk QR Code Surat Jalan
# ------------------------------------------------------------------------------
# - Tanpa dependensi tambahan: upload via REST API + signature SHA-1 (urllib).
# - Prioritas kredensial: Environment Variable  >  tabel cloud_settings (via UI).
#   ENV yang didukung: CLOUDINARY_URL=cloudinary://<key>:<secret>@<cloud_name>
#   atau CLOUDINARY_CLOUD_NAME / CLOUDINARY_API_KEY / CLOUDINARY_API_SECRET,
#   opsional CLOUDINARY_FOLDER.
# - public_id deterministik dari nomor DO, sehingga URL QR sudah pasti sebelum
#   file di-upload. Upload ulang memakai overwrite + invalidate (CDN di-refresh).
# - API Secret TIDAK PERNAH dikirim ke browser.
# ==============================================================================
import hashlib
import urllib.request
import urllib.error
from urllib.parse import urlencode

CLOUDINARY_DEFAULT_FOLDER = "nkri-pl"
CLOUDINARY_MAX_PDF_BYTES = 10 * 1024 * 1024  # Batas upload file akun Free Cloudinary

def _ensure_cloud_settings_table(cur):
    cur.execute("""
        CREATE TABLE IF NOT EXISTS cloud_settings (
            id INTEGER PRIMARY KEY DEFAULT 1,
            cloud_name TEXT DEFAULT '',
            api_key TEXT DEFAULT '',
            api_secret TEXT DEFAULT '',
            folder TEXT DEFAULT 'nkri-pl',
            updated_at TEXT DEFAULT (datetime('now', 'localtime'))
        )
    """)
    cur.execute("INSERT OR IGNORE INTO cloud_settings (id) VALUES (1)")

def sanitize_cloud_folder(folder):
    folder = re.sub(r"[^A-Za-z0-9_/-]+", "-", (folder or "").strip()).strip("/-")
    folder = re.sub(r"/{2,}", "/", folder)
    return folder or CLOUDINARY_DEFAULT_FOLDER

def get_cloudinary_config():
    cfg = {"cloud_name": "", "api_key": "", "api_secret": "", "folder": CLOUDINARY_DEFAULT_FOLDER, "source": "none"}
    try:
        with get_conn() as conn:
            with conn.cursor() as cur:
                _ensure_cloud_settings_table(cur)
                conn.commit()
                cur.execute("SELECT * FROM cloud_settings WHERE id = 1")
                row = cur.fetchone()
                if row and (row.get("cloud_name") or "").strip():
                    cfg.update({
                        "cloud_name": (row.get("cloud_name") or "").strip(),
                        "api_key": (row.get("api_key") or "").strip(),
                        "api_secret": (row.get("api_secret") or "").strip(),
                        "folder": sanitize_cloud_folder(row.get("folder")),
                        "source": "database",
                    })
    except Exception as ex:
        print(f"[Cloudinary] Gagal membaca cloud_settings: {ex}", flush=True)

    # Environment variable selalu menang (lebih aman untuk PC produksi)
    env_url = (os.environ.get("CLOUDINARY_URL") or "").strip()
    m = re.match(r"^cloudinary://([^:]+):([^@]+)@(.+)$", env_url) if env_url else None
    if m:
        cfg.update({"api_key": m.group(1), "api_secret": m.group(2), "cloud_name": m.group(3).strip("/"), "source": "env"})
    elif os.environ.get("CLOUDINARY_CLOUD_NAME"):
        cfg.update({
            "cloud_name": os.environ.get("CLOUDINARY_CLOUD_NAME", "").strip(),
            "api_key": os.environ.get("CLOUDINARY_API_KEY", "").strip(),
            "api_secret": os.environ.get("CLOUDINARY_API_SECRET", "").strip(),
            "source": "env",
        })
    if os.environ.get("CLOUDINARY_FOLDER"):
        cfg["folder"] = sanitize_cloud_folder(os.environ.get("CLOUDINARY_FOLDER"))
    return cfg

def cloudinary_is_configured(cfg):
    return bool(cfg and cfg.get("cloud_name") and cfg.get("api_key") and cfg.get("api_secret"))

def build_pl_cloud_slug(do_number):
    """'001/SJ/PPIC/09/2026' -> '001-PL-PPIC-09-2026' (algoritma sama persis dengan frontend)."""
    base = (do_number or "").strip().replace("/SJ/", "/PL/")
    slug = re.sub(r"[^A-Za-z0-9_-]+", "-", base).strip("-")
    return slug or "packing-list"

def build_pl_public_id(do_number, cfg):
    return f"{sanitize_cloud_folder(cfg.get('folder'))}/{build_pl_cloud_slug(do_number)}"

def cloudinary_base_delivery_url(cfg):
    if not cfg or not cfg.get("cloud_name"):
        return ""
    return f"https://res.cloudinary.com/{cfg['cloud_name']}/image/upload/{sanitize_cloud_folder(cfg.get('folder'))}/"

def build_pl_cloud_url(do_number, cfg):
    """URL tanpa nomor versi -> tetap sama walau file di-upload ulang (dipakai di QR)."""
    if not cloudinary_is_configured(cfg) or not do_number:
        return ""
    return f"{cloudinary_base_delivery_url(cfg)}{build_pl_cloud_slug(do_number)}.pdf"

def cloudinary_public_info(cfg):
    key = cfg.get("api_key") or ""
    masked = (key[:4] + "•••" + key[-3:]) if len(key) > 8 else ("•••" if key else "")
    return {
        "configured": cloudinary_is_configured(cfg),
        "cloud_name": cfg.get("cloud_name") or "",
        "folder": cfg.get("folder") or CLOUDINARY_DEFAULT_FOLDER,
        "source": cfg.get("source") or "none",
        "api_key_masked": masked,
        "has_secret": bool(cfg.get("api_secret")),
        "base_delivery_url": cloudinary_base_delivery_url(cfg) if cloudinary_is_configured(cfg) else "",
    }

def attach_cloud_fields(do_dict, cfg):
    if isinstance(do_dict, dict):
        do_dict["cloud_qr_url"] = build_pl_cloud_url(do_dict.get("do_number"), cfg)
    return do_dict

def cloudinary_sign(params, api_secret):
    items = sorted(
        (k, str(v)) for k, v in params.items()
        if v not in (None, "") and k not in ("file", "api_key", "resource_type", "cloud_name", "signature")
    )
    to_sign = "&".join(f"{k}={v}" for k, v in items)
    return hashlib.sha1((to_sign + api_secret).encode("utf-8")).hexdigest()

def _cloudinary_post(cfg, action, params, timeout=90):
    form = dict(params)
    form["timestamp"] = str(int(time.time()))
    form["signature"] = cloudinary_sign(form, cfg["api_secret"])
    form["api_key"] = cfg["api_key"]
    url = f"https://api.cloudinary.com/v1_1/{cfg['cloud_name']}/image/{action}"
    req = urllib.request.Request(
        url, data=urlencode(form).encode("ascii"), method="POST",
        headers={"Content-Type": "application/x-www-form-urlencoded", "User-Agent": "NKRI-FA-System/3"}
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as he:
        msg = None
        try:
            msg = json.loads(he.read().decode("utf-8")).get("error", {}).get("message")
        except Exception:
            pass
        raise RuntimeError(f"Cloudinary menolak permintaan ({he.code}): {msg or he.reason}")
    except urllib.error.URLError as ue:
        raise RuntimeError(f"Tidak dapat terhubung ke Cloudinary ({ue.reason}). Pastikan PC server terhubung ke internet.")

def cloudinary_upload_pdf(pdf_bytes, public_id, cfg):
    data_uri = "data:application/pdf;base64," + base64.b64encode(pdf_bytes).decode("ascii")
    return _cloudinary_post(cfg, "upload", {
        "file": data_uri,
        "public_id": public_id,
        "overwrite": "true",
        "invalidate": "true",
    }, timeout=120)

def cloudinary_destroy(public_id, cfg):
    return _cloudinary_post(cfg, "destroy", {"public_id": public_id, "invalidate": "true"}, timeout=30)

def cloudinary_check_delivery(url, timeout=20):
    """Cek apakah file benar-benar bisa dibuka publik (akun Free memblokir PDF secara default)."""
    req = urllib.request.Request(url, method="GET", headers={"Range": "bytes=0-1023", "User-Agent": "NKRI-FA-System/3"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return {"ok": True, "status": resp.status, "content_type": resp.headers.get("Content-Type", "")}
    except urllib.error.HTTPError as he:
        return {"ok": False, "status": he.code, "error": he.headers.get("x-cld-error") or he.reason}
    except urllib.error.URLError as ue:
        return {"ok": False, "status": 0, "error": str(ue.reason)}

def build_test_pdf_bytes(text="Tes Koneksi Cloudinary - PT NKRI"):
    safe = re.sub(r"[()\\]", "", text)
    content = f"BT /F1 14 Tf 24 60 Td ({safe}) Tj ET".encode("latin-1", "replace")
    objs = [
        b"<< /Type /Catalog /Pages 2 0 R >>",
        b"<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        b"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 120] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
        b"<< /Length " + str(len(content)).encode() + b" >>\nstream\n" + content + b"\nendstream",
        b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
    ]
    out = b"%PDF-1.4\n"
    offsets = []
    for i, o in enumerate(objs, 1):
        offsets.append(len(out))
        out += f"{i} 0 obj\n".encode() + o + b"\nendobj\n"
    xref_pos = len(out)
    out += f"xref\n0 {len(objs) + 1}\n0000000000 65535 f \n".encode()
    for off in offsets:
        out += f"{off:010d} 00000 n \n".encode()
    out += f"trailer\n<< /Size {len(objs) + 1} /Root 1 0 R >>\nstartxref\n{xref_pos}\n%%EOF\n".encode()
    return out

def decode_pdf_payload(raw):
    """Terima data URI / base64 murni, kembalikan bytes PDF yang tervalidasi."""
    if not raw or not isinstance(raw, str):
        raise ValueError("Data PDF kosong")
    if raw.startswith("data:"):
        raw = raw.split(",", 1)[1] if "," in raw else ""
    pdf_bytes = base64.b64decode(raw, validate=False)
    if not pdf_bytes.startswith(b"%PDF"):
        raise ValueError("File yang dikirim bukan PDF yang valid")
    if len(pdf_bytes) > CLOUDINARY_MAX_PDF_BYTES:
        raise ValueError(f"Ukuran PDF {len(pdf_bytes) / 1024 / 1024:.1f} MB melebihi batas 10 MB Cloudinary")
    return pdf_bytes

def parse_target_date(d=None):
    if not d:
        return datetime.now()
    if isinstance(d, datetime):
        return d
    if isinstance(d, date):
        return datetime.combine(d, datetime.min.time())
    if isinstance(d, str):
        s = d.strip()
        for fmt in ("%Y-%m-%d", "%Y/%m/%d", "%d-%m-%Y", "%d/%m/%Y", "%Y-%m-%d %H:%M:%S"):
            try:
                return datetime.strptime(s[:19], fmt)
            except Exception:
                pass
        month_map = {
            "januari": 1, "jan": 1,
            "februari": 2, "feb": 2,
            "maret": 3, "mar": 3,
            "april": 4, "apr": 4,
            "mei": 5, "may": 5,
            "juni": 6, "jun": 6,
            "juli": 7, "jul": 7,
            "agustus": 8, "agt": 8, "aug": 8,
            "september": 9, "sep": 9,
            "oktober": 10, "okt": 10, "oct": 10,
            "november": 11, "nov": 11,
            "desember": 12, "des": 12, "dec": 12
        }
        parts = s.split()
        if len(parts) >= 3:
            try:
                day = int(parts[0])
                m_str = parts[1].lower()
                m = month_map.get(m_str)
                year = int(parts[2])
                if m and 1 <= day <= 31 and 2000 <= year <= 2100:
                    return datetime(year, m, day)
            except Exception:
                pass
    return datetime.now()

def format_indo_date(d=None):
    dt = parse_target_date(d)
    months = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
              "Juli", "Agustus", "September", "Oktober", "November", "Desember"]
    return f"{dt.day} {months[dt.month]} {dt.year}"

def get_next_do_number(cur, target_date=None):
    now = parse_target_date(target_date)
    mm_yyyy = now.strftime("%m/%Y")

    # Ambil offset dari settings (hanya berlaku untuk bulan yang sama)
    offset = 0
    try:
        cur.execute("SELECT do_sequence_offset, do_offset_month FROM do_settings WHERE id = 1")
        s_row = cur.fetchone()
        if s_row:
            saved_month = (s_row['do_offset_month'] or '').strip()
            if saved_month == mm_yyyy:
                offset = int(s_row['do_sequence_offset'] or 0)
    except Exception:
        offset = 0

    # Cari nomor urut MAKSIMUM dari semua DO yang ada di bulan ini (tidak pakai COUNT(*) agar tidak mundur saat data terhapus/gaps)
    cur.execute("SELECT do_number FROM delivery_orders WHERE do_number LIKE ?", (f"%/SJ/PPIC/{mm_yyyy}",))
    rows = cur.fetchall()
    max_do_seq = 0
    for r in rows:
        dn = str(r[0] if isinstance(r, (tuple, list)) else (r.get('do_number') if isinstance(r, dict) else r['do_number']) or '').strip()
        m = re.match(r'^(\d+)/', dn)
        if m:
            max_do_seq = max(max_do_seq, int(m.group(1)))

    seq = max(offset, max_do_seq) + 1
    candidate = f"{seq:03d}/SJ/PPIC/{mm_yyyy}"
    while True:
        cur.execute("SELECT 1 FROM delivery_orders WHERE do_number = ?", (candidate,))
        if not cur.fetchone():
            break
        seq += 1
        candidate = f"{seq:03d}/SJ/PPIC/{mm_yyyy}"
    return candidate

def get_next_pl_numbers(cur, count=1, target_date=None):
    now = parse_target_date(target_date)
    mm_yyyy = now.strftime("%m/%Y")

    # Cari nomor urut MAKSIMUM dari packing_lists DAN standalone_packing_labels
    cur.execute("SELECT pl_number FROM packing_lists WHERE pl_number LIKE ?", (f"%/PL/PPIC/{mm_yyyy}",))
    rows_pl = cur.fetchall()
    cur.execute("SELECT pl_number FROM standalone_packing_labels WHERE pl_number LIKE ?", (f"%/PL/PPIC/{mm_yyyy}",))
    rows_lbl = cur.fetchall()

    max_pl_seq = 0
    for r in rows_pl + rows_lbl:
        pn = str(r[0] if isinstance(r, (tuple, list)) else (r.get('pl_number') if isinstance(r, dict) else r['pl_number']) or '').strip()
        m = re.match(r'^(\d+)/', pn)
        if m:
            max_pl_seq = max(max_pl_seq, int(m.group(1)))

    results = []
    seq = max_pl_seq + 1
    needed = max(1, int(count or 1))
    while len(results) < needed:
        candidate = f"{seq:03d}/PL/PPIC/{mm_yyyy}"
        cur.execute("SELECT 1 FROM packing_lists WHERE pl_number = ?", (candidate,))
        in_pl = cur.fetchone()
        cur.execute("SELECT 1 FROM standalone_packing_labels WHERE pl_number = ?", (candidate,))
        in_lbl = cur.fetchone()
        if not in_pl and not in_lbl:
            results.append(candidate)
        seq += 1
    return results

def get_next_pl_number(cur, target_date=None):
    res = get_next_pl_numbers(cur, count=1, target_date=target_date)
    return res[0] if res else f"001/PL/PPIC/{parse_target_date(target_date).strftime('%m/%Y')}"


def get_next_batch_number(cur):
    max_do = 0
    max_lbl = 0
    try:
        cur.execute("SELECT COALESCE(MAX(batch_number), 0) as max_b FROM delivery_orders")
        row_do = cur.fetchone()
        if row_do:
            b_val = row_do[0] if isinstance(row_do, (tuple, list)) else row_do.get('max_b')
            if b_val:
                max_do = int(b_val)
    except Exception:
        max_do = 0

    try:
        cur.execute("SELECT COALESCE(MAX(batch_number), 0) as max_b FROM standalone_packing_labels")
        row_lbl = cur.fetchone()
        if row_lbl:
            b_val = row_lbl[0] if isinstance(row_lbl, (tuple, list)) else row_lbl.get('max_b')
            if b_val:
                max_lbl = int(b_val)
    except Exception:
        max_lbl = 0

    highest = max_do
    return highest + 1

def get_next_crate_id(cur, batch_number=1, crate_index=None, target_date=None):
    now = parse_target_date(target_date)
    yymm = now.strftime("%y%m")
    prefix = f"{yymm}-{batch_number}"
    
    max_crate_seq = 0
    # 1. Cek semua crate_id di packing_lists untuk prefix ini
    try:
        cur.execute("SELECT crate_id FROM packing_lists WHERE crate_id LIKE ?", (f"{prefix}%",))
        rows = cur.fetchall()
        for r in rows:
            cid = str(r[0] if isinstance(r, (tuple, list)) else (r.get('crate_id') if isinstance(r, dict) else r['crate_id']) or '').strip()
            suffix = cid[len(prefix):]
            if suffix.isdigit():
                max_crate_seq = max(max_crate_seq, int(suffix))
    except Exception:
        pass

    # 2. Cek semua crate_id di standalone_packing_labels untuk prefix ini
    try:
        cur.execute("SELECT crates_json FROM standalone_packing_labels")
        rows = cur.fetchall()
        for r in rows:
            cj = r[0] if isinstance(r, (tuple, list)) else (r.get('crates_json') if isinstance(r, dict) else r['crates_json'])
            crates = json.loads(cj or '[]')
            for c in crates:
                cid = str(c.get('crate_id') or '').strip()
                if cid.startswith(prefix):
                    suffix = cid[len(prefix):]
                    if suffix.isdigit():
                        max_crate_seq = max(max_crate_seq, int(suffix))
    except Exception:
        pass

    if crate_index is not None and isinstance(crate_index, int) and crate_index > 0:
        seq = max_crate_seq + crate_index
    else:
        seq = max_crate_seq + 1

    candidate = f"{prefix}{seq:03d}"
    while True:
        cur.execute("SELECT 1 FROM packing_lists WHERE crate_id = ?", (candidate,))
        if not cur.fetchone():
            break
        seq += 1
        candidate = f"{prefix}{seq:03d}"
    return candidate

def get_next_box_id(cur, batch_number=1, box_index=1, target_date=None, exclude_ids=None):
    now = parse_target_date(target_date)
    yymm = now.strftime("%y%m")
    parent_crate_id = f"{yymm}-{batch_number}000"
    prefix = f"{parent_crate_id}-"
    exclude_set = set(exclude_ids or [])
    
    max_box_seq = 0
    # 1. Check packing_list_boxes
    try:
        cur.execute("SELECT box_id FROM packing_list_boxes WHERE box_id LIKE ?", (f"{prefix}%",))
        for r in cur.fetchall():
            bid = str(r[0] if isinstance(r, (tuple, list)) else (r.get('box_id') if isinstance(r, dict) else r['box_id']) or '').strip()
            suffix = bid[len(prefix):]
            if suffix.isdigit():
                max_box_seq = max(max_box_seq, int(suffix))
    except Exception:
        pass

    # 2. Check packing_lists (where box_id might be stored in crate_id)
    try:
        cur.execute("SELECT crate_id FROM packing_lists WHERE crate_id LIKE ?", (f"{prefix}%",))
        for r in cur.fetchall():
            cid = str(r[0] if isinstance(r, (tuple, list)) else (r.get('crate_id') if isinstance(r, dict) else r['crate_id']) or '').strip()
            suffix = cid[len(prefix):]
            if suffix.isdigit():
                max_box_seq = max(max_box_seq, int(suffix))
    except Exception:
        pass

    # 3. Check standalone_packing_labels
    try:
        cur.execute("SELECT crates_json FROM standalone_packing_labels")
        for r in cur.fetchall():
            cj = r[0] if isinstance(r, (tuple, list)) else (r.get('crates_json') if isinstance(r, dict) else r['crates_json'])
            crates = json.loads(cj or '[]')
            for c in crates:
                bid = str(c.get('box_id') or '').strip()
                if bid.startswith(prefix):
                    suffix = bid[len(prefix):]
                    if suffix.isdigit():
                        max_box_seq = max(max_box_seq, int(suffix))
                for b in (c.get('boxes') or []):
                    bid = str(b.get('box_id') or '').strip()
                    if bid.startswith(prefix):
                        suffix = bid[len(prefix):]
                        if suffix.isdigit():
                            max_box_seq = max(max_box_seq, int(suffix))
    except Exception:
        pass

    next_seq = max_box_seq + (box_index if (box_index and box_index > 0) else 1)
    candidate = f"{prefix}{next_seq:02d}"
    while True:
        if candidate in exclude_set:
            next_seq += 1
            candidate = f"{prefix}{next_seq:02d}"
            continue
        cur.execute("SELECT 1 FROM packing_list_boxes WHERE box_id = ?", (candidate,))
        in_boxes = cur.fetchone()
        cur.execute("SELECT 1 FROM packing_lists WHERE crate_id = ?", (candidate,))
        in_pl = cur.fetchone()
        if not in_boxes and not in_pl:
            break
        next_seq += 1
        candidate = f"{prefix}{next_seq:02d}"
    return candidate


def find_tray_by_code(cur, code_str):
    if not code_str:
        return None
    code_str = str(code_str).strip()
    query_sql = """
        SELECT t.id, t.tray_code, 
               COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
               COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
               t.source_document_mo, 
               COALESCE(t.product_type, 'Nanggala P9') as product_type, 
               COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
               t.verified_at, t.verified_by_user,
               COALESCE(t.session_status, 'active') as session_status,
               t.return_reason, t.return_notified,
               t.created_at
        FROM trays t
        LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
        LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
        WHERE t.tray_code = ?
    """
    cur.execute(query_sql, (code_str,))
    tray = cur.fetchone()
    if tray:
        return tray
    # Try fuzzy variations: strip -PU suffix, try numeric padding, etc.
    clean_code = re.sub(r'-PU\d*$', '', code_str, flags=re.IGNORECASE).strip()
    digits = re.findall(r'\d+', clean_code)
    candidates = [clean_code]
    if digits:
        last_digits = digits[-1]
        candidates.append(f"WH/ASSY/{last_digits.zfill(6)}")
        candidates.append(f"WH/ASSY/{last_digits}")
        candidates.append(last_digits.zfill(6))
        candidates.append(last_digits)
    for cand in candidates:
        cur.execute(query_sql, (cand,))
        tray = cur.fetchone()
        if tray:
            return tray
    return None

DEFAULT_TESTING_POINTS = [
    {
        "test_num": 1,
        "category": "Cek Visual",
        "instruction": "1. Pengecekan visual secara menyeluruh mulai dari sisi atas-bawah, samping kanan-kiri, depan belakang",
        "output": "- Penebalan pada hasil pengecatan\\n- Retak pada area yang berpasangan dengan part berbahan metal"
    },
    {
        "test_num": 2,
        "category": "Cek Rigiditas",
        "instruction": "1. Cek upper dan lower dengan cara digoyangkan secara horizontal",
        "output": "Tidak mengeluarkan suara akibat hasil rakitan longgar"
    },
    {
        "test_num": 3,
        "category": "Cek Rigiditas",
        "instruction": "2. Cek front sight dan rear sight dengan cara digoyangkan",
        "output": "Tidak boleh bergeser"
    },
    {
        "test_num": 4,
        "category": "Cek Mekanik",
        "instruction": "1. Uji pelepas magasen sebanyak 3x",
        "output": "Magasen tidak jatuh / lepas setelah dikunci\\nMagasen dapat masuk - keluar dengan baik ketika pelepas magasen di tekan"
    },
    {
        "test_num": 5,
        "category": "Cek Mekanik",
        "instruction": "2. Uji kokang sebanyak 3x",
        "output": "Tidak menyangkut ataupun tersendat ketika di kokang dan dapat kembali dengan lancar"
    },
    {
        "test_num": 6,
        "category": "Cek Mekanik",
        "instruction": "3. Uji picu dengan cara menarik picu untuk mengaktifkan pemukul",
        "output": "Pemukul dapat memukul pena pukul"
    },
    {
        "test_num": 7,
        "category": "Cek Mekanik",
        "instruction": "4. Uji pemukul dengan cara menarik pemukul kemudian tarik picu",
        "output": "Pemukul dapat memukul pena pukul"
    },
    {
        "test_num": 8,
        "category": "Cek Mekanik",
        "instruction": "5. Uji pemukul dengan cara menarik pemukul 1/2 kemudian tarik picu",
        "output": "Pemukul dapat terkunci pada kondisi 1/2 tegang\\nPemukul dapat memukul pena pukul"
    },
    {
        "test_num": 9,
        "category": "Cek Mekanik",
        "instruction": "6. Uji pemukul dengan cara menarik penuh pemukul kemudian tarik picu",
        "output": "Pemukul dapat memukul pena pukul"
    },
    {
        "test_num": 10,
        "category": "Cek Mekanik",
        "instruction": "7. Uji pemukul dengan cara menarik penuh pemukul kemudian dorong kedepan",
        "output": "Pemukul tidak boleh memukul pena pukul"
    },
    {
        "test_num": 11,
        "category": "Cek Mekanik",
        "instruction": "7.1. Dorong keatas tuas pengaman kemudian tarik picu",
        "output": "Pemukul tidak boleh memukul pena pukul"
    },
    {
        "test_num": 12,
        "category": "Cek Mekanik",
        "instruction": "7.1.1 Dorong kebawah tuas pengaman (untuk menormalkan kembali)",
        "output": "Tuas pengaman kembali ke posisi normal"
    },
    {
        "test_num": 13,
        "category": "Cek Mekanik",
        "instruction": "7.2 Tarik pemukul secara penuh kemudian kokang penuh lalu aktifkan kunci eretan",
        "output": "Eretan dapat terkunci dengan baik dan tidak bergerak maju kedepan"
    },
    {
        "test_num": 14,
        "category": "Cek Mekanik",
        "instruction": "7.3 Turunkan kunci eretan",
        "output": "Eretan dapat maju kedepan dengan baik dan tidak tersendat"
    },
    {
        "test_num": 15,
        "category": "Cek Mekanik",
        "instruction": "8. Kokang penuh kemudian aktifkan kunci eretan dan putar keatas tuas pembongkar",
        "output": "Tuas pembongkar dapat diputar keatas"
    },
    {
        "test_num": 16,
        "category": "Cek Mekanik",
        "instruction": "8.1 Putar kembali kebawah tuas pembongkar kemudian non-aktifkan kunci eretan",
        "output": "Tuas pembongkar dan kunci eretan dapat dikembalikan ke posisi awal"
    },
    {
        "test_num": 17,
        "category": "Cek Mekanik",
        "instruction": "9. Tarik pemukul secara penuh, kemudian dorong laras dari depan ke belakang",
        "output": "Laras tidak dapat bergerak mundur ke belakang"
    }
]
DEFAULT_15_TESTING_POINTS = DEFAULT_TESTING_POINTS

def append_status_history(current_history, next_status):
    if not next_status:
        return current_history or 'Good'
    next_clean = str(next_status).strip()
    if not current_history or not str(current_history).strip():
        return next_clean
    parts = [p.strip() for p in str(current_history).split('->') if p.strip()]
    if parts and parts[-1].lower() in ['good', 'good qc'] and next_clean.lower() in ['good', 'good qc']:
        return " -> ".join(parts)
    if parts and parts[-1].lower() in ['hold', 'hold qc'] and next_clean.lower() in ['hold', 'hold qc']:
        return " -> ".join(parts)
    if len(parts) == 1 and parts[0].lower() in ['good', 'good qc'] and next_clean.lower() in ['hold', 'hold qc', 'rework', 'rework qc']:
        parts = [next_clean]
    elif parts and parts[-1].lower() in ['good', 'good qc'] and next_clean.lower() in ['hold', 'hold qc']:
        if len(parts) == 2 and parts[0].lower() in ['hold', 'hold qc']:
            return "Hold"
        parts.append(next_clean)
    else:
        parts.append(next_clean)
    return " -> ".join(parts)

def save_uploaded_image(data_uri, test_num, idx=0):
    if not data_uri or not isinstance(data_uri, str):
        return None
    data_str = data_uri.strip()
    if data_str.startswith('/uploads/') or data_str.startswith('uploads/'):
        return data_str if data_str.startswith('/') else '/' + data_str
    if not data_str.startswith('data:image/'):
        return None
    try:
        header, b64data = data_str.split(',', 1)
        ext = 'png'
        if 'jpeg' in header.lower() or 'jpg' in header.lower():
            ext = 'jpg'
        elif 'webp' in header.lower():
            ext = 'webp'
        elif 'gif' in header.lower():
            ext = 'gif'
        img_bytes = base64.b64decode(b64data)
        upload_dir = os.path.join(STATIC_DIR, "uploads", "testing_points")
        os.makedirs(upload_dir, exist_ok=True)
        filename = f"tp_{test_num}_{uuid.uuid4().hex[:8]}_{idx}.{ext}"
        filepath = os.path.join(upload_dir, filename)
        with open(filepath, "wb") as f:
            f.write(img_bytes)
        return f"/uploads/testing_points/{filename}"
    except Exception as ex:
        print(f"Error saving uploaded image: {ex}", flush=True)
        return None

def _exec_schema_stmt(cur, stmt):
    stmt = stmt.strip()
    if not stmt or stmt.startswith("--"):
        return
    try:
        cur.execute(stmt)
    except Exception as e:
        err = str(e).lower()
        if "duplicate column" in err or "already exists" in err or "no such column" in err:
            pass
        elif "syntax error" in err:
            print(f"Schema stmt warning: {e} -> {stmt[:60]}", flush=True)

def ensure_db_schema():
    try:
        with get_conn() as conn:
            with conn.cursor() as cur:
                # 1. Base tables
                cur.execute("""
                    CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        nik TEXT UNIQUE,
                        full_name TEXT NOT NULL,
                        username TEXT NOT NULL UNIQUE,
                        password TEXT NOT NULL,
                        role TEXT NOT NULL DEFAULT 'assembly',
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("""
                    CREATE TABLE IF NOT EXISTS master_stocks (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        sn TEXT NOT NULL,
                        cs TEXT,
                        part_type TEXT NOT NULL,
                        status TEXT NOT NULL DEFAULT 'Tersedia',
                        allocated_tray_id INTEGER,
                        allocated_at TEXT,
                        used_at TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        UNIQUE(sn, part_type)
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_master_stocks_type_status ON master_stocks(part_type, status);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_master_stocks_tray ON master_stocks(allocated_tray_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_master_stocks_type_sn ON master_stocks(part_type, sn);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_master_stocks_sn ON master_stocks(sn);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assemblies_verified_status ON assemblies(verified_status);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assemblies_is_declined ON assemblies(is_declined);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assy_tests_assy_id_stage ON assembly_tests(assembly_id, stage);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_re_register_sn_status ON re_register_requests(sn, status);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS trays (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tray_code TEXT NOT NULL UNIQUE,
                        pic_warehouse TEXT NOT NULL,
                        assigned_operator TEXT,
                        source_document_mo TEXT,
                        product_type TEXT DEFAULT 'Nanggala P9',
                        status TEXT NOT NULL DEFAULT 'active',
                        verified_by_gudang INTEGER DEFAULT 0,
                        verified_at TEXT,
                        verified_by_user TEXT,
                        session_status TEXT DEFAULT 'active',
                        return_reason TEXT,
                        return_notified INTEGER DEFAULT 0,
                        return_verified INTEGER DEFAULT 0,
                        return_verified_at TEXT,
                        return_verified_by TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_trays_code ON trays(tray_code);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_trays_created_at ON trays(created_at DESC);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS parts (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
                        part_type TEXT NOT NULL,
                        sn TEXT NOT NULL,
                        status TEXT NOT NULL DEFAULT 'Supplied',
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_parts_tray_id ON parts(tray_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_parts_type_sn ON parts(part_type, sn);")
                cur.execute("CREATE UNIQUE INDEX IF NOT EXISTS uq_parts_tray_sn ON parts(tray_id, part_type, sn);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS assemblies (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
                        set_number INTEGER NOT NULL,
                        pic_assembly TEXT NOT NULL,
                        part15_sn TEXT NOT NULL,
                        part12_sn TEXT NOT NULL,
                        part1_sn TEXT NOT NULL,
                        status TEXT NOT NULL DEFAULT 'Good',
                        reason TEXT,
                        defective_parts TEXT,
                        verified_status TEXT,
                        is_declined INTEGER DEFAULT 0,
                        is_reregistered INTEGER DEFAULT 0,
                        declined_reason TEXT,
                        qc_status TEXT DEFAULT 'PENDING',
                        qc_doc_number TEXT,
                        assy_doc_number TEXT,
                        qc_tested_at TEXT,
                        qc_tested_by TEXT,
                        qc_failure_reasons TEXT,
                        warehouse_receipt_status TEXT DEFAULT 'PENDING',
                        warehouse_receipt_at TEXT,
                        warehouse_receipt_by TEXT,
                        warehouse_doc_number TEXT,
                        assy_test_checklist TEXT,
                        qc_notes TEXT,
                        assy_status_history TEXT,
                        qc_status_history TEXT,
                        rework_reason TEXT,
                        lifecycle_path TEXT,
                        started_at TEXT,
                        finished_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        UNIQUE(tray_id, set_number)
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assemblies_tray_id ON assemblies(tray_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assemblies_status ON assemblies(status);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assemblies_parts ON assemblies(part15_sn, part12_sn, part1_sn);")

                # 2. Additional features & logging tables
                cur.execute("""
                    CREATE TABLE IF NOT EXISTS part_audit_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        part_type TEXT NOT NULL,
                        sn TEXT NOT NULL,
                        old_status TEXT NOT NULL,
                        new_status TEXT NOT NULL,
                        action_type TEXT NOT NULL,
                        actor_username TEXT NOT NULL,
                        actor_role TEXT NOT NULL,
                        reason TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_audit_created_at ON part_audit_logs(created_at DESC);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_audit_sn ON part_audit_logs(sn);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS re_register_requests (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        part_type TEXT NOT NULL,
                        sn TEXT NOT NULL,
                        current_status TEXT NOT NULL,
                        target_status TEXT NOT NULL,
                        requestor_username TEXT NOT NULL,
                        requestor_role TEXT NOT NULL,
                        reason TEXT,
                        status TEXT NOT NULL DEFAULT 'PENDING',
                        admin_username TEXT,
                        admin_notes TEXT,
                        approved_at TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_re_register_status ON re_register_requests(status);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_re_register_sn ON re_register_requests(sn);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS part_replacement_requests (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        assembly_id INTEGER NOT NULL,
                        tray_id INTEGER,
                        set_number INTEGER,
                        replacements_json TEXT NOT NULL,
                        old_part_status TEXT NOT NULL,
                        reason TEXT,
                        requestor_username TEXT,
                        requestor_role TEXT,
                        status TEXT DEFAULT 'PENDING',
                        admin_username TEXT,
                        admin_notes TEXT,
                        created_at TEXT DEFAULT (datetime('now', 'localtime')),
                        verified_at TEXT
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_part_replacement_status ON part_replacement_requests(status);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_part_replacement_assy ON part_replacement_requests(assembly_id);")


                cur.execute("""
                    CREATE TABLE IF NOT EXISTS user_sessions (
                        token TEXT PRIMARY KEY,
                        user_id INTEGER,
                        username TEXT,
                        role TEXT,
                        full_name TEXT,
                        nik TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        last_activity TEXT DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(token);")

                for _col in [
                    "ALTER TABLE assemblies ADD COLUMN rereg_pending_operator TEXT",
                    "ALTER TABLE assemblies ADD COLUMN rereg_wo_suffix TEXT",
                ]:
                    try:
                        cur.execute(_col)
                    except Exception:
                        pass

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS assembly_downtimes (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tray_id INTEGER REFERENCES trays(id) ON DELETE CASCADE,
                        tray_code TEXT NOT NULL,
                        set_number INTEGER,
                        operator TEXT NOT NULL,
                        reason TEXT NOT NULL,
                        paused_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        resumed_at TEXT,
                        duration_seconds INTEGER DEFAULT 0,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_downtimes_tray ON assembly_downtimes(tray_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_downtimes_operator ON assembly_downtimes(operator);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_downtimes_created_at ON assembly_downtimes(created_at DESC);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS assembly_in_progress (
                        tray_id INTEGER PRIMARY KEY REFERENCES trays(id) ON DELETE CASCADE,
                        tray_code TEXT NOT NULL,
                        set_number INTEGER NOT NULL,
                        operator TEXT NOT NULL,
                        part15 TEXT,
                        part1 TEXT,
                        part12 TEXT,
                        started_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS assembly_tests (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        stage TEXT NOT NULL,
                        doc_number TEXT,
                        tray_id INTEGER REFERENCES trays(id) ON DELETE CASCADE,
                        tray_code TEXT,
                        set_number INTEGER,
                        assembly_id INTEGER REFERENCES assemblies(id) ON DELETE CASCADE,
                        p9_sn TEXT,
                        part15_sn TEXT,
                        part12_sn TEXT,
                        part1_sn TEXT,
                        tester_username TEXT,
                        tester_role TEXT,
                        test_checklist TEXT,
                        status TEXT NOT NULL,
                        failure_reasons TEXT,
                        notes TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        admin_approved_at TEXT,
                        admin_approved_by TEXT,
                        admin_action TEXT,
                        status_history TEXT,
                        lifecycle_path TEXT
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assy_tests_stage ON assembly_tests(stage);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assy_tests_doc ON assembly_tests(doc_number);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assy_tests_p9_sn ON assembly_tests(p9_sn);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_assy_tests_created ON assembly_tests(created_at DESC);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS assembly_wo_history (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
                        tray_code TEXT,
                        set_number INTEGER NOT NULL,
                        pic_assembly TEXT,
                        part15_sn TEXT,
                        part12_sn TEXT,
                        part1_sn TEXT,
                        status TEXT NOT NULL DEFAULT 'Good',
                        reason TEXT,
                        defective_parts TEXT,
                        started_at TEXT,
                        finished_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        source TEXT DEFAULT 'ASSEMBLY',
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_wo_history_tray ON assembly_wo_history(tray_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_wo_history_parts ON assembly_wo_history(part15_sn, part12_sn, part1_sn);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_wo_history_finished ON assembly_wo_history(finished_at DESC);")

                # Auto-sync assemblies to assembly_wo_history if missing
                cur.execute("""
                    INSERT INTO assembly_wo_history (
                        tray_id, tray_code, set_number, pic_assembly,
                        part15_sn, part12_sn, part1_sn, status, reason, defective_parts,
                        started_at, finished_at, source, created_at
                    )
                    SELECT 
                        a.tray_id, t.tray_code, a.set_number, a.pic_assembly,
                        a.part15_sn, a.part12_sn, a.part1_sn, COALESCE(a.status, 'Good'), a.reason, a.defective_parts,
                        a.started_at, COALESCE(a.finished_at, a.started_at, t.created_at), 'AUTO_SYNC', COALESCE(a.finished_at, a.started_at, t.created_at)
                    FROM assemblies a
                    JOIN trays t ON a.tray_id = t.id
                    WHERE NOT EXISTS (
                        SELECT 1 FROM assembly_wo_history h WHERE h.tray_id = a.tray_id AND h.set_number = a.set_number
                    )
                """)

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS testing_points (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        test_num INTEGER NOT NULL UNIQUE,
                        category TEXT DEFAULT 'Umum / Fungsional',
                        instruction TEXT NOT NULL,
                        output TEXT NOT NULL,
                        images TEXT DEFAULT '[]',
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_testing_points_num ON testing_points(test_num);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS customers (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT NOT NULL UNIQUE,
                        address TEXT NOT NULL,
                        phone TEXT,
                        fax TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS delivery_orders (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        do_number TEXT NOT NULL UNIQUE,
                        do_date TEXT NOT NULL,
                        contract_number TEXT NOT NULL,
                        program_name TEXT NOT NULL,
                        customer_id INTEGER,
                        customer_name TEXT NOT NULL,
                        customer_address TEXT NOT NULL,
                        customer_phone TEXT,
                        customer_fax TEXT,
                        product_name TEXT NOT NULL DEFAULT 'Nanggala P9',
                        total_quantity INTEGER NOT NULL DEFAULT 0,
                        total_crates INTEGER NOT NULL DEFAULT 0,
                        batch_number INTEGER NOT NULL DEFAULT 1,
                        created_by TEXT NOT NULL DEFAULT 'Rezka Rachdana',
                        status TEXT NOT NULL DEFAULT 'ISSUED',
                        notes TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
                        updated_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_do_number ON delivery_orders(do_number);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_do_date ON delivery_orders(do_date);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS packing_lists (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        delivery_order_id INTEGER NOT NULL REFERENCES delivery_orders(id) ON DELETE CASCADE,
                        pl_number TEXT NOT NULL,
                        pl_date TEXT NOT NULL,
                        crate_id TEXT NOT NULL UNIQUE,
                        crate_number INTEGER NOT NULL,
                        total_crates INTEGER NOT NULL,
                        product_name TEXT NOT NULL DEFAULT 'Nanggala P9',
                        total_quantity INTEGER NOT NULL DEFAULT 20,
                        notes TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_pl_crate_id ON packing_lists(crate_id);")
                cur.execute("CREATE INDEX IF NOT EXISTS idx_pl_do_id ON packing_lists(delivery_order_id);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS packing_list_boxes (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        packing_list_id INTEGER NOT NULL REFERENCES packing_lists(id) ON DELETE CASCADE,
                        delivery_order_id INTEGER NOT NULL REFERENCES delivery_orders(id) ON DELETE CASCADE,
                        box_id TEXT NOT NULL UNIQUE,
                        box_number INTEGER NOT NULL,
                        quantity INTEGER NOT NULL DEFAULT 10,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_box_id ON packing_list_boxes(box_id);")

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS packing_list_items (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        box_id INTEGER NOT NULL REFERENCES packing_list_boxes(id) ON DELETE CASCADE,
                        packing_list_id INTEGER NOT NULL REFERENCES packing_lists(id) ON DELETE CASCADE,
                        delivery_order_id INTEGER NOT NULL REFERENCES delivery_orders(id) ON DELETE CASCADE,
                        slot_number INTEGER NOT NULL,
                        serial_number TEXT NOT NULL,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                cur.execute("CREATE INDEX IF NOT EXISTS idx_pl_item_sn ON packing_list_items(serial_number);")

                # Seed customer KEMHAN RI if table empty
                cur.execute("SELECT COUNT(*) as c FROM customers")
                c_row = cur.fetchone()
                if c_row and c_row['c'] == 0:
                    cur.execute("""
                        INSERT INTO customers (name, address, phone, fax)
                        VALUES (?, ?, ?, ?)
                    """, (
                        "KEMHAN RI",
                        "Jl. Tanah Abang Timur No. 6 - 7\nJakarta, 10110\nIndonesia",
                        "(021) 3840889",
                        "(021) 3840889"
                    ))

                cur.execute("""
                    CREATE TABLE IF NOT EXISTS do_settings (
                        id INTEGER PRIMARY KEY DEFAULT 1,
                        company_name TEXT DEFAULT 'PT Nanggala Kencana Rekatama Indonesia',
                        company_logo TEXT DEFAULT 'nkri-logo.png',
                        head_office TEXT DEFAULT 'Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130',
                        factory_address TEXT DEFAULT 'Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292',
                        contact_email TEXT DEFAULT 'info@nanggala.co.id',
                        default_customer_name TEXT DEFAULT 'KEMHAN RI',
                        default_customer_address TEXT DEFAULT 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
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

                # 3. Column migration alters for existing tables
                alters = [
                    "ALTER TABLE user_sessions ADD COLUMN last_activity TEXT DEFAULT (datetime('now', 'localtime'))",
                    "ALTER TABLE trays ADD COLUMN verified_by_gudang INTEGER DEFAULT 0",
                    "ALTER TABLE trays ADD COLUMN verified_at TEXT",
                    "ALTER TABLE trays ADD COLUMN verified_by_user TEXT",
                    "ALTER TABLE trays ADD COLUMN session_status TEXT DEFAULT 'active'",
                    "ALTER TABLE trays ADD COLUMN return_reason TEXT",
                    "ALTER TABLE trays ADD COLUMN return_notified INTEGER DEFAULT 0",
                    "ALTER TABLE trays ADD COLUMN return_verified INTEGER DEFAULT 0",
                    "ALTER TABLE trays ADD COLUMN return_verified_at TEXT",
                    "ALTER TABLE trays ADD COLUMN return_verified_by TEXT",
                    "ALTER TABLE assemblies ADD COLUMN defective_parts TEXT",
                    "ALTER TABLE assemblies ADD COLUMN verified_status TEXT",
                    "ALTER TABLE assemblies ADD COLUMN is_declined INTEGER DEFAULT 0",
                    "ALTER TABLE assemblies ADD COLUMN is_reregistered INTEGER DEFAULT 0",
                    "ALTER TABLE assemblies ADD COLUMN declined_reason TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_status TEXT DEFAULT 'PENDING'",
                    "ALTER TABLE assemblies ADD COLUMN qc_doc_number TEXT",
                    "ALTER TABLE assemblies ADD COLUMN assy_doc_number TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_tested_at TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_tested_by TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_failure_reasons TEXT",
                    "ALTER TABLE assemblies ADD COLUMN warehouse_receipt_status TEXT DEFAULT 'PENDING'",
                    "ALTER TABLE assemblies ADD COLUMN warehouse_receipt_at TEXT",
                    "ALTER TABLE assemblies ADD COLUMN warehouse_receipt_by TEXT",
                    "ALTER TABLE assemblies ADD COLUMN warehouse_doc_number TEXT",
                    "ALTER TABLE assemblies ADD COLUMN assy_test_checklist TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_notes TEXT",
                    "ALTER TABLE assemblies ADD COLUMN assy_status_history TEXT",
                    "ALTER TABLE assemblies ADD COLUMN qc_status_history TEXT",
                    "ALTER TABLE assemblies ADD COLUMN rework_reason TEXT",
                    "ALTER TABLE assemblies ADD COLUMN lifecycle_path TEXT",
                    "ALTER TABLE assemblies ADD COLUMN assy_test_status TEXT DEFAULT 'PENDING'",
                    "ALTER TABLE assemblies ADD COLUMN assy_failure_reasons TEXT",
                    "ALTER TABLE assembly_tests ADD COLUMN status_history TEXT",
                    "ALTER TABLE assembly_tests ADD COLUMN lifecycle_path TEXT",
                    "ALTER TABLE delivery_orders ADD COLUMN created_by_title TEXT DEFAULT 'Staf'",
                    "ALTER TABLE delivery_orders ADD COLUMN created_by_department TEXT DEFAULT 'PPIC'",
                    "ALTER TABLE delivery_orders ADD COLUMN package_type TEXT DEFAULT 'crate'",
                    "ALTER TABLE master_stocks ADD COLUMN last_reason TEXT",
                    "ALTER TABLE master_stocks ADD COLUMN last_tray_id INTEGER",
                    "ALTER TABLE master_stocks ADD COLUMN last_operator TEXT",
                    "ALTER TABLE master_stocks ADD COLUMN is_replaced_from_assy INTEGER DEFAULT 0",
                    "ALTER TABLE users ADD COLUMN department TEXT",
                    "ALTER TABLE users ADD COLUMN permissions TEXT",
                    "ALTER TABLE do_settings ADD COLUMN tracking_base_url TEXT DEFAULT ''",
                    "ALTER TABLE do_settings ADD COLUMN do_sequence_offset INTEGER DEFAULT 0",
                    "ALTER TABLE do_settings ADD COLUMN do_offset_month TEXT DEFAULT ''",
                    "ALTER TABLE assemblies ADD COLUMN original_assembly_status TEXT",
                    "ALTER TABLE assemblies ADD COLUMN rereg_pending_operator TEXT",
                    "ALTER TABLE assemblies ADD COLUMN rereg_wo_suffix TEXT",
                    "ALTER TABLE delivery_orders ADD COLUMN cloud_public_id TEXT",
                    "ALTER TABLE delivery_orders ADD COLUMN cloud_pdf_url TEXT",
                    "ALTER TABLE delivery_orders ADD COLUMN cloud_version TEXT",
                    "ALTER TABLE delivery_orders ADD COLUMN cloud_uploaded_at TEXT"
                ]
                for alt in alters:
                    _exec_schema_stmt(cur, alt)
                _ensure_cloud_settings_table(cur)
                
                cur.execute("""
                    CREATE TABLE IF NOT EXISTS standalone_packing_labels (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        label_batch_id TEXT NOT NULL,
                        pl_number TEXT,
                        package_type TEXT NOT NULL DEFAULT 'crate',
                        batch_number INTEGER NOT NULL DEFAULT 1,
                        product_name TEXT NOT NULL DEFAULT 'Nanggala P9',
                        total_quantity INTEGER NOT NULL DEFAULT 0,
                        total_containers INTEGER NOT NULL DEFAULT 0,
                        crates_json TEXT NOT NULL DEFAULT '[]',
                        notes TEXT,
                        created_by TEXT,
                        created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                    );
                """)
                conn.commit()

                # Self-healing migration: Isolasi mutlak status Perakitan vs Testing & Status Aktual History
                try:
                    cur.execute("""
                        UPDATE assemblies 
                        SET status = 'Good',
                            assy_test_status = 'Hold',
                            assy_failure_reasons = COALESCE(assy_failure_reasons, reason),
                            reason = NULL
                        WHERE status = 'Hold' 
                          AND (qc_status = 'HOLD_ASSY' OR reason LIKE 'gagal dalam test%' OR reason LIKE '%Single Retest%')
                          AND (defective_parts IS NULL OR defective_parts = '' OR defective_parts = '[]');
                    """)
                    cur.execute("""
                        UPDATE parts 
                        SET status = 'Done'
                        WHERE status = 'Hold'
                          AND tray_id IN (
                              SELECT tray_id FROM assemblies 
                              WHERE status = 'Good' AND qc_status = 'HOLD_ASSY'
                          );
                    """)
                    # Populate original_assembly_status for all assemblies
                    cur.execute("""
                        UPDATE assemblies
                        SET original_assembly_status = CASE
                            WHEN status IN ('Hold', 'Rework', 'Scrap') THEN status
                            WHEN (defective_parts IS NOT NULL AND defective_parts != '' AND defective_parts != '-') THEN 'Hold'
                            WHEN (reason IS NOT NULL AND reason != '' AND reason != '-' AND NOT reason LIKE '%Dirakit Ulang%') THEN 'Hold'
                            WHEN status = 'Dirakit Ulang' THEN 'Hold'
                            ELSE 'Good'
                        END
                        WHERE original_assembly_status IS NULL OR original_assembly_status = '';
                    """)
                    # Clean up any loose re_register_requests for assemblies that have been replaced or are waiting reassembly
                    cur.execute("""
                        DELETE FROM re_register_requests
                        WHERE status = 'PENDING'
                          AND sn IN (
                              SELECT part15_sn FROM assemblies WHERE verified_status IN ('PENDING_REPLACEMENT', 'WAITING_REASSEMBLY', 'COMPLETED_PU')
                              UNION SELECT part1_sn FROM assemblies WHERE verified_status IN ('PENDING_REPLACEMENT', 'WAITING_REASSEMBLY', 'COMPLETED_PU')
                              UNION SELECT part12_sn FROM assemblies WHERE verified_status IN ('PENDING_REPLACEMENT', 'WAITING_REASSEMBLY', 'COMPLETED_PU')
                          );
                    """)
                    # Restore master_stocks for parts attached to active/waiting assemblies that were wrongly set to Pending Verif Admin
                    cur.execute("""
                        UPDATE master_stocks
                        SET status = 'Used'
                        WHERE status = 'Pending Verif Admin'
                          AND sn IN (
                              SELECT part15_sn FROM assemblies WHERE verified_status IN ('WAITING_REASSEMBLY', 'COMPLETED_PU')
                              UNION SELECT part1_sn FROM assemblies WHERE verified_status IN ('WAITING_REASSEMBLY', 'COMPLETED_PU')
                              UNION SELECT part12_sn FROM assemblies WHERE verified_status IN ('WAITING_REASSEMBLY', 'COMPLETED_PU')
                          );
                    """)
                    conn.commit()
                except Exception as e_heal:
                    print(f"[WARN] Self-healing migration warning: {e_heal}")

                # 4. Seed default admin & operator users if users table is empty
                cur.execute("SELECT COUNT(*) as cnt FROM users")
                u_cnt = cur.fetchone()
                if u_cnt and u_cnt['cnt'] == 0:
                    default_users = [
                        ('ADM001', 'Administrator System', 'admin', 'admin123', 'admin'),
                        ('GDG001', 'Operator Gudang', 'gudang', 'gudang123', 'gudang'),
                        ('ASY001', 'Operator Perakitan', 'assembly', 'assembly123', 'assembly'),
                        ('QC001', 'Quality Control Inspector', 'qc', 'qc123', 'qc'),
                    ]
                    for nik, fname, uname, pwd, role in default_users:
                        cur.execute("""
                            INSERT INTO users (nik, full_name, username, password, role)
                            VALUES (?, ?, ?, ?, ?)
                        """, (nik, fname, uname, pwd, role))
                    conn.commit()

                # 5. Seed initial 15 testing points if empty
                cur.execute("SELECT COUNT(*) as cnt FROM testing_points")
                row_cnt = cur.fetchone()
                if row_cnt and row_cnt['cnt'] == 0:
                    for pt in DEFAULT_15_TESTING_POINTS:
                        cur.execute("""
                            INSERT OR IGNORE INTO testing_points (test_num, category, instruction, output, images, created_at, updated_at)
                            VALUES (?, ?, ?, ?, '[]', datetime('now', 'localtime'), datetime('now', 'localtime'))
                        """, (pt['test_num'], pt['category'], pt['instruction'], pt['output']))
                    conn.commit()

                # 6. Self-healing migration for legacy timestamps (UTC vs Localtime / WIB offset)
                try:
                    # Fix assemblies where finished_at was recorded in UTC (~7 hours behind started_at)
                    cur.execute("""
                        UPDATE assemblies 
                        SET finished_at = datetime(finished_at, '+7 hours') 
                        WHERE finished_at IS NOT NULL 
                          AND started_at IS NOT NULL 
                          AND finished_at < started_at 
                          AND (unixepoch(started_at) - unixepoch(finished_at)) >= 18000 
                          AND (unixepoch(started_at) - unixepoch(finished_at)) <= 32400;
                    """)
                    cur.execute("""
                        UPDATE assembly_wo_history 
                        SET finished_at = datetime(finished_at, '+7 hours') 
                        WHERE finished_at IS NOT NULL 
                          AND started_at IS NOT NULL 
                          AND finished_at < started_at 
                          AND (unixepoch(started_at) - unixepoch(finished_at)) >= 18000 
                          AND (unixepoch(started_at) - unixepoch(finished_at)) <= 32400;
                    """)
                    # Fix trays created_at where tray was created in UTC (~7h before its first assembly)
                    cur.execute("""
                        UPDATE trays
                        SET created_at = datetime(created_at, '+7 hours')
                        WHERE id IN (
                            SELECT t.id FROM trays t
                            JOIN assemblies a ON t.id = a.tray_id
                            GROUP BY t.id
                            HAVING (unixepoch(MIN(a.started_at)) - unixepoch(t.created_at)) >= 18000 
                               AND (unixepoch(MIN(a.started_at)) - unixepoch(t.created_at)) <= 32400
                        );
                    """)
                    # Fix assembly_tests created_at that were recorded in UTC (~7 hours behind started_at/finished_at)
                    cur.execute("""
                        UPDATE assembly_tests 
                        SET created_at = datetime(created_at, '+7 hours') 
                        WHERE created_at IS NOT NULL 
                          AND (
                              id IN (
                                  SELECT at.id FROM assembly_tests at
                                  JOIN assemblies a ON at.assembly_id = a.id
                                  WHERE COALESCE(a.started_at, a.finished_at) IS NOT NULL
                                    AND at.created_at < COALESCE(a.started_at, a.finished_at)
                                    AND (unixepoch(COALESCE(a.started_at, a.finished_at)) - unixepoch(at.created_at)) >= 18000
                                    AND (unixepoch(COALESCE(a.started_at, a.finished_at)) - unixepoch(at.created_at)) <= 32400
                              )
                              OR
                              id IN (
                                  SELECT at.id FROM assembly_tests at
                                  JOIN trays t ON at.tray_id = t.id
                                  JOIN assemblies a ON a.tray_id = t.id AND a.set_number = at.set_number
                                  WHERE COALESCE(a.started_at, a.finished_at) IS NOT NULL
                                    AND at.created_at < COALESCE(a.started_at, a.finished_at)
                                    AND (unixepoch(COALESCE(a.started_at, a.finished_at)) - unixepoch(at.created_at)) >= 18000
                                    AND (unixepoch(COALESCE(a.started_at, a.finished_at)) - unixepoch(at.created_at)) <= 32400
                              )
                              OR
                              id IN (
                                  SELECT at.id FROM assembly_tests at
                                  JOIN trays t ON at.tray_id = t.id
                                  WHERE t.created_at IS NOT NULL
                                    AND at.created_at < t.created_at
                                    AND (unixepoch(t.created_at) - unixepoch(at.created_at)) >= 18000
                                    AND (unixepoch(t.created_at) - unixepoch(at.created_at)) <= 32400
                              )
                          );
                    """)
                    # Fix assemblies qc_tested_at if recorded in UTC
                    cur.execute("""
                        UPDATE assemblies 
                        SET qc_tested_at = datetime(qc_tested_at, '+7 hours') 
                        WHERE qc_tested_at IS NOT NULL 
                          AND started_at IS NOT NULL 
                          AND qc_tested_at < started_at 
                          AND (unixepoch(started_at) - unixepoch(qc_tested_at)) >= 18000 
                          AND (unixepoch(started_at) - unixepoch(qc_tested_at)) <= 32400;
                    """)
                    conn.commit()
                except Exception as ex_heal:
                    print(f"Timestamp self-healing info: {ex_heal}", flush=True)
    except Exception as e:
        print(f"Error initializing schema: {e}", flush=True)

class Handler(SimpleHTTPRequestHandler):
    def address_string(self):
        return str(self.client_address[0])

    def log_message(self, format, *args):
        return

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Headers", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Content-Length", "0")
        self.end_headers()
        self.wfile.flush()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = parsed.path
        query = parse_qs(parsed.query)

        try:
            if path.startswith("/api/"):
                if path == "/api/public/tracking/pl":
                    do_param = (query.get("do", [""])[0] or query.get("do_number", [""])[0] or query.get("id", [""])[0] or query.get("pl", [""])[0] or query.get("packing_list", [""])[0] or query.get("q", [""])[0]).strip()
                    if not do_param:
                        return send_json(self, {"ok": False, "error": "Parameter DO, Packing List, atau ID wajib diisi"}, 400)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT * FROM delivery_orders WHERE id = ? OR do_number = ?", (do_param, do_param))
                            do_row = cur.fetchone()
                            if not do_row and '/PL/' in do_param:
                                sj_param = do_param.replace('/PL/', '/SJ/')
                                cur.execute("SELECT * FROM delivery_orders WHERE do_number = ?", (sj_param,))
                                do_row = cur.fetchone()
                            if not do_row:
                                cur.execute("""
                                    SELECT do.* FROM delivery_orders do
                                    JOIN packing_lists pl ON pl.delivery_order_id = do.id
                                    WHERE pl.pl_number = ? OR pl.crate_id = ?
                                    ORDER BY do.id DESC LIMIT 1
                                """, (do_param, do_param))
                                do_row = cur.fetchone()
                            if not do_row:
                                cur.execute("""
                                    SELECT do.* FROM delivery_orders do
                                    JOIN packing_lists pl ON pl.delivery_order_id = do.id
                                    JOIN packing_list_boxes pb ON pb.packing_list_id = pl.id
                                    JOIN packing_list_items pi ON pi.box_id = pb.id
                                    WHERE pi.serial_number = ? OR pb.box_id = ?
                                    ORDER BY do.id DESC LIMIT 1
                                """, (do_param, do_param))
                                do_row = cur.fetchone()
                            if not do_row:
                                cur.execute("SELECT * FROM delivery_orders WHERE do_number LIKE ? ORDER BY id DESC LIMIT 1", (f"%{do_param}%",))
                                do_row = cur.fetchone()
                            if not do_row:
                                return send_json(self, {"ok": False, "error": "Delivery Order atau Packing List tidak ditemukan"}, 404)
                            do_data = dict(do_row)
                            if not do_data.get('created_by_name'):
                                do_data['created_by_name'] = do_data.get('created_by')

                            cur.execute("""
                                SELECT * FROM packing_lists 
                                WHERE delivery_order_id = ? 
                                ORDER BY crate_number ASC, id ASC
                            """, (do_data['id'],))
                            crates = [dict(c) for c in cur.fetchall()]

                            for crate in crates:
                                cur.execute("""
                                    SELECT * FROM packing_list_boxes 
                                    WHERE packing_list_id = ? 
                                    ORDER BY box_number ASC, id ASC
                                """, (crate['id'],))
                                boxes = [dict(b) for b in cur.fetchall()]
                                for box in boxes:
                                    cur.execute("""
                                        SELECT slot_number, serial_number 
                                        FROM packing_list_items 
                                        WHERE box_id = ? 
                                        ORDER BY slot_number ASC
                                    """, (box['id'],))
                                    box['items'] = [dict(it) for it in cur.fetchall()]
                                crate['boxes'] = boxes
                            cur.execute("SELECT * FROM do_settings LIMIT 1")
                            s_row = cur.fetchone()
                            cfg = get_cloudinary_config()
                            st = dict(s_row) if s_row else {}
                            st["cloudinary"] = cloudinary_public_info(cfg)
                            do_data['settings'] = st
                            do_data['crates'] = crates
                            attach_cloud_fields(do_data, cfg)
                    return send_json(self, {"ok": True, "data": do_data})

                user = check_auth(self)
                if not user:
                    return send_json(self, {"ok": False, "error": "Unauthorized / Sesi habis"}, 401)

                if path == "/api/auth/me":
                    return send_json(self, {"ok": True, "user": user})

                if path == "/api/operators":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT id, nik, full_name, username, role, department 
                                FROM users 
                                WHERE LOWER(COALESCE(department, '')) = 'assembly' 
                                   OR LOWER(COALESCE(role, '')) IN ('assembly', 'operator')
                                ORDER BY full_name ASC, username ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/testing-points":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT id, test_num, category, instruction, output, images, created_at, updated_at
                                FROM testing_points
                                ORDER BY test_num ASC
                            """)
                            rows = []
                            for r in cur.fetchall():
                                row = dict(r)
                                imgs = row.get('images')
                                if isinstance(imgs, str):
                                    try:
                                        row['images'] = json.loads(imgs)
                                    except Exception:
                                        row['images'] = []
                                if not isinstance(row.get('images'), list):
                                    row['images'] = []
                                rows.append(row)
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/users/lookup":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT id, nik, full_name, username, role, department FROM users ORDER BY full_name ASC, username ASC")
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/admin/users":
                    if not is_admin_user(user):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT id, nik, full_name, username, role, department, permissions, created_at FROM users ORDER BY id ASC")
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/admin/audit-logs":
                    if not is_admin_user(user):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    start_date = query.get("start_date", [""])[0].strip()
                    end_date = query.get("end_date", [""])[0].strip()
                    status_f = query.get("status", [""])[0].strip()
                    q_str = query.get("q", [""])[0].strip()

                    conds = ["1=1"]
                    params = []
                    if start_date:
                        conds.append("DATE(created_at) >= DATE(?)")
                        params.append(start_date)
                    if end_date:
                        conds.append("DATE(created_at) <= DATE(?)")
                        params.append(end_date)
                    if status_f and status_f != 'ALL':
                        conds.append("(new_status LIKE ? OR old_status LIKE ?)")
                        params.extend([status_f, status_f])
                    if q_str:
                        conds.append("(sn LIKE ? OR part_type LIKE ? OR actor_username LIKE ? OR reason LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like])

                    where_sql = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT id, part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at
                                FROM part_audit_logs
                                WHERE {where_sql}
                                ORDER BY created_at DESC
                                LIMIT 1000
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/admin/pending-verifications-all":
                    if not (is_admin_user(user) or user_has_perm(user, 'verify_set') or user_has_perm(user, 'verify_part') or user_has_perm(user, 'verify_replacement') or user_has_perm(user, 'verify_items')):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT r.*, m.cs 
                                FROM re_register_requests r
                                LEFT JOIN master_stocks m ON (r.part_type = m.part_type AND (r.sn = m.sn OR REPLACE(r.sn, '.', '') = REPLACE(m.sn, '.', '')))
                                ORDER BY r.created_at DESC
                            """)
                            requests_rows = [dict(r) for r in cur.fetchall()]

                            # 1. Assemblies from Assembly Testing (BARANG RAKITAN)
                            cur.execute("""
                                SELECT 
                                    a.id as assembly_id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn,
                                    CASE 
                                        WHEN a.status = 'Rework' OR rr.target_status = 'Rework' OR rr.current_status = 'Rework' THEN 'Rework'
                                        ELSE a.status
                                    END as assembly_status, 
                                    a.qc_status, a.verified_status, a.is_declined, a.declined_reason, 
                                    COALESCE(a.reason, rr.reason) as scrap_reason, a.defective_parts, a.rework_reason,
                                    a.started_at, a.finished_at,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                    t.created_at as tray_created_at,
                                    'ASSY' as stage
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN re_register_requests rr ON rr.id = (
                                    SELECT id FROM re_register_requests 
                                    WHERE sn IN (a.part15_sn, a.part1_sn, a.part12_sn)
                                    ORDER BY id DESC LIMIT 1
                                )
                                WHERE (
                                    (
                                        a.verified_status IN ('PENDING_ADMIN', 'PENDING_ADMIN_ASSY_REREG')
                                        OR (a.is_declined = TRUE AND (a.verified_status IS NULL OR a.verified_status NOT IN ('ACC', 'APPROVED', 'REJECTED')))
                                        OR (a.verified_status = 'DECLINED' AND (a.verified_status IS NULL OR a.verified_status NOT IN ('ACC', 'APPROVED', 'REJECTED')))
                                    )
                                    AND (a.qc_status IS NULL OR a.qc_status NOT IN ('HOLD_QC', 'REWORK_QC'))
                                    AND (a.verified_status IS NULL OR a.verified_status NOT IN ('HOLD_GUDANG', 'REWORK_GUDANG', 'ACC', 'APPROVED', 'REJECTED', 'APPROVED_QC', 'APPROVED_REREG', 'PENDING_REPLACEMENT', 'HOLD_QC_GUDANG'))
                                )
                                ORDER BY a.id DESC
                            """)
                            assemblies_rows = [dict(r) for r in cur.fetchall()]

                            # 2. Assemblies from QC Testing (HASIL UJI MUTU QC)
                            cur.execute("""
                                SELECT 
                                    a.id as assembly_id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn,
                                    COALESCE(a.status, 'Good') as assembly_status, 
                                    a.qc_status, a.verified_status, a.is_declined, a.declined_reason, 
                                    COALESCE(a.qc_failure_reasons, a.reason, rr.reason) as scrap_reason, a.defective_parts, a.rework_reason,
                                    a.started_at, a.finished_at, a.qc_tested_at, a.qc_doc_number,
                                    COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                    t.created_at as tray_created_at,
                                    'QC' as stage
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN re_register_requests rr ON rr.id = (
                                    SELECT id FROM re_register_requests 
                                    WHERE sn IN (a.part15_sn, a.part1_sn, a.part12_sn)
                                    ORDER BY id DESC LIMIT 1
                                )
                                WHERE (
                                    (
                                        a.verified_status = 'PENDING_ADMIN_QC'
                                        OR (rr.reason IS NOT NULL AND rr.reason LIKE '%gagal qc%' AND a.verified_status = 'PENDING_ADMIN_QC')
                                    )
                                    AND (a.is_declined = FALSE OR a.is_declined = 0 OR a.is_declined IS NULL)
                                    AND (a.verified_status IS NULL OR a.verified_status NOT IN ('HOLD_QC_GUDANG', 'REWORK_QC_GUDANG', 'APPROVED_QC', 'ACC', 'APPROVED', 'REJECTED', 'PENDING_REPLACEMENT'))
                                )
                                ORDER BY a.id DESC
                            """)
                            qc_assemblies_rows = [dict(r) for r in cur.fetchall()]

                            cur.execute("""
                                SELECT id, part_type, sn, cs, status
                                FROM master_stocks
                                WHERE status NOT IN ('Tersedia', 'Good')
                                ORDER BY id ASC
                            """)
                            parts_rows = [dict(r) for r in cur.fetchall()]

                    return send_json(self, {
                        "ok": True,
                        "requests": requests_rows,
                        "assemblies": assemblies_rows,
                        "qc_assemblies": qc_assemblies_rows,
                        "parts": parts_rows
                    })

                elif path == "/api/admin/pending-verifications":
                    if not (is_admin_user(user) or user_has_perm(user, 'verify_part') or user_has_perm(user, 'verify_items')):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT 
                                    r.*, 
                                    m.cs,
                                    m.last_reason as stock_last_reason,
                                    m.is_replaced_from_assy,
                                    prev_a.id as previous_assembly_id,
                                    prev_a.set_number as previous_set_number,
                                    prev_a.status as previous_assembly_status,
                                    COALESCE(prev_a.reason, prev_a.declined_reason) as previous_assembly_reason,
                                    COALESCE(prev_a.qc_failure_reasons, prev_a.assy_failure_reasons) as previous_failure_reasons,
                                    prev_a.part15_sn as previous_part15_sn,
                                    prev_a.part1_sn as previous_part1_sn,
                                    prev_a.part12_sn as previous_part12_sn,
                                    COALESCE(prev_a.finished_at, prev_a.started_at) as previous_assembly_date,
                                    prev_t.tray_code as previous_tray_code,
                                    prev_t.source_document_mo as previous_mo,
                                    COALESCE(u_prev_assy.full_name, prev_a.pic_assembly) as previous_pic_assembly,
                                    (
                                        SELECT reason FROM part_audit_logs 
                                        WHERE sn = r.sn AND action_type IN ('REPLACE_PART_REMOVED', 'REREGISTER_REQUESTED')
                                        ORDER BY id DESC LIMIT 1
                                    ) as audit_log_reason
                                FROM re_register_requests r
                                LEFT JOIN master_stocks m ON (r.part_type = m.part_type AND (r.sn = m.sn OR REPLACE(r.sn, '.', '') = REPLACE(m.sn, '.', '')))
                                LEFT JOIN assemblies prev_a ON prev_a.id = (
                                    SELECT id FROM assemblies 
                                    WHERE part15_sn = r.sn OR part1_sn = r.sn OR part12_sn = r.sn 
                                    ORDER BY id DESC LIMIT 1
                                )
                                LEFT JOIN trays prev_t ON prev_a.tray_id = prev_t.id
                                LEFT JOIN users u_prev_assy ON (prev_a.pic_assembly = u_prev_assy.username OR prev_a.pic_assembly = u_prev_assy.full_name)
                                WHERE (r.status = 'PENDING' OR r.status IN ('APPROVED', 'REJECTED'))
                                ORDER BY CASE WHEN r.status = 'PENDING' THEN 0 ELSE 1 END, r.id DESC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/admin/pending-assemblies":
                    if not (is_admin_user(user) or user_has_perm(user, 'verify_set') or user_has_perm(user, 'verify_items')):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            # 1. Assemblies from Assembly & TF1 Testing (BARANG RAKITAN) - KHUSUS VERIFIKASI SATU SET
                            cur.execute("""
                                WITH candidate_assemblies AS (
                                    SELECT a.*
                                    FROM assemblies a
                                    WHERE (
                                        (a.is_declined = TRUE OR a.is_declined = 1 OR a.verified_status = 'DECLINED' OR a.warehouse_receipt_status = 'DECLINED' OR a.declined_reason IS NOT NULL)
                                        OR
                                        (
                                            a.verified_status IN ('PENDING_ADMIN', 'PENDING_ADMIN_ASSY_REREG', 'APPROVED', 'APPROVED_REREG', 'WAITING_REASSEMBLY', 'COMPLETED_PU')
                                            AND (a.is_declined = FALSE OR a.is_declined = 0 OR a.is_declined IS NULL)
                                            AND (a.warehouse_receipt_status IS NULL OR a.warehouse_receipt_status != 'DECLINED')
                                            AND (a.qc_status IS NULL OR a.qc_status NOT IN ('HOLD_QC', 'REWORK_QC'))
                                        )
                                    )
                                )
                                SELECT
                                    a.id as assembly_id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn,
                                    COALESCE(ms15.status, 'Tersedia') as p15_status,
                                    COALESCE(ms1.status, 'Tersedia') as p1_status,
                                    COALESCE(ms12.status, 'Tersedia') as p12_status,
                                    a.status as raw_assembly_status,
                                    a.assy_test_status, a.assy_failure_reasons, a.assy_test_checklist,
                                    CASE
                                        WHEN a.status = 'Rework' OR rr.target_status = 'Rework' OR rr.current_status = 'Rework' THEN 'Rework'
                                        ELSE a.status
                                    END as assembly_status,
                                    a.qc_status, a.verified_status, a.is_declined, a.declined_reason, a.warehouse_receipt_status,
                                    COALESCE(a.reason, rr.reason) as scrap_reason, a.defective_parts, a.rework_reason,
                                    a.started_at, a.finished_at,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                    t.created_at as tray_created_at,
                                    CASE
                                        WHEN a.verified_status = 'PENDING_ADMIN_ASSY_REREG' THEN 'perakitan'
                                        WHEN a.assy_test_status IN ('Hold', 'Rework') OR a.qc_status = 'HOLD_ASSY' OR (a.assy_failure_reasons IS NOT NULL AND a.assy_failure_reasons != '') THEN 'test_functional_1'
                                        ELSE 'perakitan'
                                    END as process_stage,
                                    a.rereg_pending_operator,
                                    rr.id as request_id,
                                    rr.created_at as request_created_at
                                FROM candidate_assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN master_stocks ms15 ON (ms15.part_type = '15' AND (ms15.sn = a.part15_sn OR ms15.sn = REPLACE(a.part15_sn, '.', '')))
                                LEFT JOIN master_stocks ms1 ON (ms1.part_type = '1' AND (ms1.sn = a.part1_sn OR ms1.sn = REPLACE(a.part1_sn, '.', '')))
                                LEFT JOIN master_stocks ms12 ON (ms12.part_type = '12' AND (ms12.sn = a.part12_sn OR ms12.sn = REPLACE(a.part12_sn, '.', '')))
                                LEFT JOIN re_register_requests rr ON rr.id = (
                                    SELECT id FROM re_register_requests
                                    WHERE sn IN (a.part15_sn, a.part1_sn, a.part12_sn)
                                    ORDER BY id DESC LIMIT 1
                                )
                                ORDER BY CASE WHEN a.verified_status LIKE 'PENDING%' THEN 0 ELSE 1 END, COALESCE(rr.id, a.id) DESC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]

                            for r in rows:
                                is_dec = bool(r.get('is_declined')) or (r.get('verified_status') or '').upper() == 'DECLINED' or (r.get('warehouse_receipt_status') or '').upper() == 'DECLINED'
                                if is_dec:
                                    r['display_status'] = 'Declined'
                                    r['process_stage'] = 'declined'
                                    r['is_declined'] = True
                                else:
                                    p_stage = r.get('process_stage') or 'perakitan'
                                    a_stat = r.get('assembly_status') or 'Hold'
                                    raw_stat = r.get('raw_assembly_status') or a_stat
                                    if p_stage == 'test_functional_1':
                                        r['display_status'] = 'Rework Test' if (a_stat == 'Rework' or raw_stat == 'Rework') else 'Hold Test'
                                    else:
                                        if raw_stat == 'Scrap' or a_stat == 'Scrap' or r.get('status') == 'Scrap':
                                            r['display_status'] = 'Scrap Perakitan'
                                        elif raw_stat == 'Rework' or a_stat == 'Rework':
                                            r['display_status'] = 'Rework Perakitan'
                                        else:
                                            r['display_status'] = 'Hold Perakitan'

                            # 2. Assemblies from QC Testing (HASIL UJI MUTU QC) - KHUSUS VERIFIKASI SATU SET
                            cur.execute("""
                                WITH pending_rr AS (
                                    SELECT id, sn, reason, created_at, target_status, current_status
                                    FROM re_register_requests
                                    WHERE status = 'PENDING'
                                ),
                                candidate_assemblies AS (
                                    SELECT a.*
                                    FROM assemblies a
                                    WHERE (a.is_declined = FALSE OR a.is_declined = 0 OR a.is_declined IS NULL)
                                      AND (a.warehouse_receipt_status IS NULL OR a.warehouse_receipt_status != 'DECLINED')
                                      AND (a.verified_status IS NULL OR a.verified_status != 'HOLD_QC_GUDANG' OR a.is_reregistered = TRUE)
                                      AND (
                                          a.verified_status IN ('PENDING_ADMIN_QC', 'APPROVED_QC')
                                          OR (a.is_reregistered = TRUE AND a.qc_status IN ('HOLD_QC', 'REWORK_QC'))
                                          OR a.part15_sn IN (SELECT sn FROM pending_rr)
                                          OR a.part1_sn IN (SELECT sn FROM pending_rr)
                                          OR a.part12_sn IN (SELECT sn FROM pending_rr)
                                      )
                                )
                                SELECT
                                    a.id as assembly_id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn,
                                    COALESCE(ms15.status, 'Tersedia') as p15_status,
                                    COALESCE(ms1.status, 'Tersedia') as p1_status,
                                    COALESCE(ms12.status, 'Tersedia') as p12_status,
                                    a.status as raw_assembly_status,
                                    a.assy_test_status, a.assy_failure_reasons, a.assy_test_checklist,
                                    COALESCE(a.status, 'Good') as assembly_status,
                                    a.qc_status, a.verified_status, a.is_declined, a.declined_reason, a.warehouse_receipt_status,
                                    COALESCE(a.qc_failure_reasons, a.reason, rr.reason) as scrap_reason, a.defective_parts,
                                    a.started_at, a.finished_at, a.qc_tested_at, a.qc_doc_number,
                                    COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                    t.created_at as tray_created_at,
                                    'qc' as process_stage,
                                    rr.id as request_id,
                                    rr.created_at as request_created_at
                                FROM candidate_assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN master_stocks ms15 ON (ms15.part_type = '15' AND (ms15.sn = a.part15_sn OR ms15.sn = REPLACE(a.part15_sn, '.', '')))
                                LEFT JOIN master_stocks ms1 ON (ms1.part_type = '1' AND (ms1.sn = a.part1_sn OR ms1.sn = REPLACE(a.part1_sn, '.', '')))
                                LEFT JOIN master_stocks ms12 ON (ms12.part_type = '12' AND (ms12.sn = a.part12_sn OR ms12.sn = REPLACE(a.part12_sn, '.', '')))
                                LEFT JOIN pending_rr rr ON rr.sn IN (a.part15_sn, a.part1_sn, a.part12_sn)
                                ORDER BY CASE WHEN a.verified_status LIKE 'PENDING%' THEN 0 ELSE 1 END, COALESCE(rr.id, a.id) DESC
                            """)
                            qc_rows = [dict(r) for r in cur.fetchall()]

                            for r in qc_rows:
                                r['process_stage'] = 'qc'
                                a_stat = r.get('assembly_status') or 'Hold'
                                qc_stat = r.get('qc_status') or ''
                                r['display_status'] = 'Rework QC' if (a_stat == 'Rework' or qc_stat == 'REWORK_QC') else 'Hold QC'

                            # 3. Part Replacement Requests - KHUSUS VERIFIKASI PERGANTIAN PART
                            cur.execute("""
                                SELECT 
                                    prr.id as replacement_id,
                                    prr.assembly_id,
                                    prr.tray_id,
                                    prr.set_number,
                                    prr.replacements_json as replacement_details,
                                    prr.old_part_status as replacement_old_status,
                                    prr.reason as replacement_reason,
                                    prr.requestor_username as replacement_requestor,
                                    prr.created_at as replacement_created_at,
                                    prr.status as replacement_status,
                                    prr.admin_username,
                                    prr.admin_notes,
                                    prr.verified_at,
                                    a.part15_sn, a.part1_sn, a.part12_sn,
                                    a.status as assembly_status, a.assy_test_status, a.qc_status, a.verified_status,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    t.created_at as tray_created_at
                                FROM part_replacement_requests prr
                                JOIN assemblies a ON prr.assembly_id = a.id
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                ORDER BY CASE WHEN prr.status = 'PENDING' THEN 0 ELSE 1 END, prr.id DESC
                            """)
                            replacement_rows = [dict(r) for r in cur.fetchall()]

                            for r in replacement_rows:
                                r['display_status'] = 'Pengajuan Ganti Part'
                                r['is_replacement'] = True
                                r['status'] = r.get('replacement_status') or 'PENDING'

                    return send_json(self, {"ok": True, "data": rows, "qc_data": qc_rows, "replacement_data": replacement_rows})

                elif path == "/api/admin/pending-replacements":
                    if not (is_admin_user(user) or user_has_perm(user, 'verify_replacement') or user_has_perm(user, 'verify_items')):
                        return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT 
                                    prr.id as replacement_id,
                                    prr.assembly_id,
                                    prr.tray_id,
                                    prr.set_number,
                                    prr.replacements_json as replacement_details,
                                    prr.old_part_status as replacement_old_status,
                                    prr.reason as replacement_reason,
                                    prr.requestor_username as replacement_requestor,
                                    prr.created_at as replacement_created_at,
                                    prr.status as replacement_status,
                                    prr.admin_username,
                                    prr.admin_notes,
                                    prr.verified_at,
                                    a.part15_sn, a.part1_sn, a.part12_sn,
                                    a.status as assembly_status, a.assy_test_status, a.qc_status, a.verified_status,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    t.tray_code, t.source_document_mo, t.product_type,
                                    t.created_at as tray_created_at
                                FROM part_replacement_requests prr
                                JOIN assemblies a ON prr.assembly_id = a.id
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                ORDER BY CASE WHEN prr.status = 'PENDING' THEN 0 ELSE 1 END, prr.id DESC
                            """)
                            replacement_rows = [dict(r) for r in cur.fetchall()]
                            for r in replacement_rows:
                                r['display_status'] = 'Pengajuan Ganti Part'
                                r['is_replacement'] = True
                                r['status'] = r.get('replacement_status') or 'PENDING'
                    return send_json(self, {"ok": True, "data": replacement_rows})

                elif path == "/api/assembly/pending-reassembly":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT 
                                    a.id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn,
                                    a.status, a.verified_status, a.rework_reason, a.rereg_pending_operator,
                                    t.tray_code, t.product_type,
                                    COALESCE(prr.admin_username, 'Admin') as replaced_by_admin,
                                    prr.replacements_json
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN part_replacement_requests prr ON prr.id = (
                                    SELECT id FROM part_replacement_requests 
                                    WHERE assembly_id = a.id AND status = 'APPROVED'
                                    ORDER BY id DESC LIMIT 1
                                )
                                WHERE (
                                    a.verified_status = 'WAITING_REASSEMBLY'
                                    OR (a.rework_reason LIKE '%Ganti Part%' AND a.status IN ('Dirakit Ulang', 'Rework', 'Hold'))
                                )
                                AND (a.rereg_pending_operator IS NULL OR a.rereg_pending_operator = '')
                                AND COALESCE(a.verified_status, '') NOT IN ('COMPLETED_PU', 'APPROVED_REREG')
                                AND (a.is_declined IS NULL OR a.is_declined = 0)
                                AND a.status != 'Declined'
                                AND COALESCE(a.verified_status, '') != 'DECLINED'
                                ORDER BY a.id DESC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                            for r in rows:
                                if r.get('replacements_json'):
                                    try:
                                        r['replacements'] = json.loads(r['replacements_json'])
                                    except Exception:
                                        r['replacements'] = []
                                else:
                                    r['replacements'] = []
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/admin/downtime-logs":
                    if not (user_has_perm(user, 'downtime') or user_has_perm(user, 'assembly') or user_has_perm(user, 'warehouse')):
                        return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin Audit Downtime / Assembly / Warehouse)"}, 403)
                    start_date = query.get("start_date", [""])[0].strip()
                    end_date = query.get("end_date", [""])[0].strip()
                    operator = query.get("operator", [""])[0].strip()
                    tray_code = query.get("tray_code", [""])[0].strip()
                    q_str = query.get("q", [""])[0].strip()

                    conds = ["1=1"]
                    params = []
                    if start_date:
                        conds.append("DATE(paused_at) >= DATE(?)")
                        params.append(start_date)
                    if end_date:
                        conds.append("DATE(paused_at) <= DATE(?)")
                        params.append(end_date)
                    if operator and operator != 'ALL' and operator != 'Semua Operator':
                        conds.append("(operator LIKE ?)")
                        params.append(f"%{operator}%")
                    if tray_code:
                        conds.append("tray_code LIKE ?")
                        params.append(f"%{tray_code}%")
                    if q_str:
                        conds.append("(reason LIKE ? OR operator LIKE ? OR tray_code LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like])

                    where_sql = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT id, tray_id, tray_code, set_number, operator, reason, paused_at, resumed_at, duration_seconds, created_at
                                FROM assembly_downtimes
                                WHERE {where_sql}
                                ORDER BY paused_at DESC
                                LIMIT 1000
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})



                elif path == "/api/assembly/trays":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            op_filter = query.get("operator", [""])[0].strip()
                            if not is_super_admin(user) and not user_has_perm(user, 'warehouse') and user_has_perm(user, 'assembly'):
                                cur.execute("""
                                    SELECT t.id, t.tray_code, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
                                           t.source_document_mo, 
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type, 
                                           t.created_at,
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           COALESCE(t.session_status, 'active') as session_status,
                                           t.return_reason, t.return_notified,
                                           CASE WHEN t.session_status = 'COMPLETED_SHIFT' THEN COUNT(DISTINCT a.id) * 3
                                                ELSE GREATEST(COUNT(DISTINCT p.id), COUNT(DISTINCT a.id) * 3)
                                           END as total_parts,
                                           CASE WHEN t.session_status = 'COMPLETED_SHIFT' THEN COUNT(DISTINCT a.id)
                                                ELSE GREATEST(COUNT(DISTINCT CASE WHEN p.part_type = '15' THEN p.id END), COUNT(DISTINCT a.id))
                                           END as total_sets,
                                           COUNT(DISTINCT a.id) as finished_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Hold' THEN a.id END) as hold_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Scrap' THEN a.id END) as scrap_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Rework' THEN a.id END) as rework_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' THEN a.id END) as good_assembly_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' AND (a.assy_doc_number IS NOT NULL OR (a.assy_test_status IS NOT NULL AND a.assy_test_status NOT IN ('PENDING', '')) OR a.qc_status IN ('HOLD_ASSY', 'REWORK_ASSY', 'PENDING_QC', 'GOOD', 'HOLD_QC') OR EXISTS (SELECT 1 FROM assembly_tests atest WHERE atest.assembly_id = a.id AND atest.stage = 'ASSY')) THEN a.id END) as tested_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' AND a.assy_doc_number IS NOT NULL AND a.qc_status != 'HOLD_ASSY' THEN a.id END) as good_assy_test_count,
                                           COUNT(DISTINCT CASE WHEN a.is_reregistered = 1 OR a.status = 'Dirakit Ulang' OR a.qc_status = 'RU' OR a.rereg_wo_suffix IS NOT NULL THEN a.id END) as ru_count
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    LEFT JOIN parts p ON t.id = p.tray_id
                                    LEFT JOIN assemblies a ON t.id = a.tray_id
                                    WHERE (t.assigned_operator IS NULL OR t.assigned_operator = '' OR t.assigned_operator = ? OR t.assigned_operator = ? OR t.assigned_operator = 'ALL')
                                      AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR EXISTS (SELECT 1 FROM assemblies a WHERE a.tray_id = t.id))
                                    GROUP BY t.id, t.tray_code, u_wh.full_name, t.pic_warehouse, u_op.full_name, t.assigned_operator, t.source_document_mo, t.product_type, t.created_at, t.verified_by_gudang, t.verified_at, t.verified_by_user, t.session_status, t.return_reason, t.return_notified
                                    ORDER BY t.tray_code ASC
                                """, (user['username'], user.get('full_name') or user['username']))
                            else:
                                where_sql = "1=1"
                                params = []
                                if op_filter and op_filter not in ['ALL', 'Semua Operator', 'Semua Operator Assembly']:
                                    where_sql += " AND (t.assigned_operator = ? OR u_op.full_name = ? OR t.assigned_operator LIKE ? OR u_op.full_name LIKE ?)"
                                    params = [op_filter, op_filter, f"%{op_filter}%", f"%{op_filter}%"]

                                cur.execute(f"""
                                    SELECT t.id, t.tray_code, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
                                           t.source_document_mo, 
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type, 
                                           t.created_at,
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           COALESCE(t.session_status, 'active') as session_status,
                                           t.return_reason, t.return_notified,
                                           CASE WHEN t.session_status = 'COMPLETED_SHIFT' THEN COUNT(DISTINCT a.id) * 3
                                                ELSE GREATEST(COUNT(DISTINCT p.id), COUNT(DISTINCT a.id) * 3)
                                           END as total_parts,
                                           CASE WHEN t.session_status = 'COMPLETED_SHIFT' THEN COUNT(DISTINCT a.id)
                                                ELSE GREATEST(COUNT(DISTINCT CASE WHEN p.part_type = '15' THEN p.id END), COUNT(DISTINCT a.id))
                                           END as total_sets,
                                           COUNT(DISTINCT a.id) as finished_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Hold' THEN a.id END) as hold_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Scrap' THEN a.id END) as scrap_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Rework' THEN a.id END) as rework_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' THEN a.id END) as good_assembly_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' AND (a.assy_doc_number IS NOT NULL OR (a.assy_test_status IS NOT NULL AND a.assy_test_status NOT IN ('PENDING', '')) OR a.qc_status IN ('HOLD_ASSY', 'REWORK_ASSY', 'PENDING_QC', 'GOOD', 'HOLD_QC') OR EXISTS (SELECT 1 FROM assembly_tests atest WHERE atest.assembly_id = a.id AND atest.stage = 'ASSY')) THEN a.id END) as tested_count,
                                           COUNT(DISTINCT CASE WHEN a.status = 'Good' AND a.assy_doc_number IS NOT NULL AND a.qc_status != 'HOLD_ASSY' THEN a.id END) as good_assy_test_count,
                                           COUNT(DISTINCT CASE WHEN a.is_reregistered = 1 OR a.status = 'Dirakit Ulang' OR a.qc_status = 'RU' OR a.rereg_wo_suffix IS NOT NULL THEN a.id END) as ru_count
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    LEFT JOIN parts p ON t.id = p.tray_id
                                    LEFT JOIN assemblies a ON t.id = a.tray_id
                                    WHERE {where_sql}
                                    GROUP BY t.id, t.tray_code, u_wh.full_name, t.pic_warehouse, u_op.full_name, t.assigned_operator, t.source_document_mo, t.product_type, t.created_at, t.verified_by_gudang, t.verified_at, t.verified_by_user, t.session_status, t.return_reason, t.return_notified
                                    ORDER BY t.tray_code ASC
                                """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path in ["/api/tray/detail", "/api/assembly/tray"]:
                    raw_id = query.get("id", [""])[0]
                    tray_id = int(raw_id) if raw_id and str(raw_id).strip().isdigit() else None
                    raw_code = query.get("code", [""])[0]
                    tray_code = raw_code.strip() if raw_code and raw_code != 'undefined' else ""
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            if tray_id:
                                cur.execute("""
                                    SELECT t.id, t.tray_code, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
                                           t.source_document_mo, 
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type, 
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           COALESCE(t.session_status, 'active') as session_status,
                                           t.return_reason, t.return_notified,
                                           t.created_at
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.id = ?
                                """, (tray_id,))
                            elif tray_code:
                                tray = find_tray_by_code(cur, tray_code)
                            else:
                                cur.execute("""
                                    SELECT t.id, t.tray_code, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse, 
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator, 
                                           t.source_document_mo, 
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type, 
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           COALESCE(t.session_status, 'active') as session_status,
                                           t.return_reason, t.return_notified,
                                           t.created_at
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    ORDER BY t.id DESC LIMIT 1
                                """)
                                tray = cur.fetchone()
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                            
                            # Security check: if assembly operator, verify tray assignment
                            if not is_super_admin(user) and not user_has_perm(user, 'warehouse') and user_has_perm(user, 'assembly'):
                                if tray.get('assigned_operator') and tray['assigned_operator'] not in ['', user['username'], user.get('full_name'), 'ALL']:
                                    return send_json(self, {"ok": False, "error": f"Tray ini ditugaskan khusus untuk operator '{tray['assigned_operator']}'"}, 403)

                            if tray.get('session_status') == 'COMPLETED_SHIFT':
                                cur.execute('''
                                    SELECT p.* FROM parts p
                                    JOIN assemblies a ON p.tray_id = a.tray_id AND (p.sn = a.part15_sn OR p.sn = a.part1_sn OR p.sn = a.part12_sn)
                                    WHERE p.tray_id = ?
                                    ORDER BY p.id
                                ''', (tray['id'],))
                                parts = [dict(r) for r in cur.fetchall()]
                            else:
                                cur.execute("SELECT * FROM parts WHERE tray_id = ? ORDER BY id", (tray['id'],))
                                parts = [dict(r) for r in cur.fetchall()]

                            cur.execute("""
                                SELECT a.id, a.tray_id, a.set_number, a.part15_sn, a.part12_sn, a.part1_sn, 
                                       COALESCE(a.status, 'Good') as status, 
                                       COALESCE(a.original_assembly_status, (
                                           SELECT wo.status FROM assembly_wo_history wo 
                                           WHERE wo.tray_id = a.tray_id AND wo.set_number = a.set_number 
                                           ORDER BY wo.id ASC LIMIT 1
                                       ), CASE 
                                           WHEN (a.defective_parts IS NOT NULL AND a.defective_parts != '' AND a.defective_parts != '-') THEN 'Hold'
                                           WHEN (a.reason IS NOT NULL AND a.reason != '' AND a.reason != '-' AND NOT a.reason LIKE ' | %' AND NOT a.reason LIKE '%Dirakit Ulang%') THEN 'Hold'
                                           WHEN a.status IN ('Hold', 'Rework', 'Scrap', 'Good') THEN a.status
                                           ELSE 'Good' 
                                       END) as original_assembly_status,
                                       COALESCE(a.declined_reason, a.reason) as reason,
                                       COALESCE(a.assy_test_status, CASE WHEN a.assy_doc_number IS NOT NULL THEN 'Good' WHEN a.qc_status = 'HOLD_ASSY' THEN 'Hold' ELSE 'PENDING' END) as assy_test_status,
                                       COALESCE(a.assy_failure_reasons, at.failure_reasons) as assy_failure_reasons,
                                       a.defective_parts, a.verified_status, a.is_declined, a.is_reregistered, a.declined_reason,
                                       COALESCE(a.started_at, a.finished_at) as started_at,
                                       COALESCE(a.finished_at, a.started_at) as finished_at,
                                       a.assy_doc_number, a.qc_status, a.qc_doc_number, a.qc_tested_at, a.qc_tested_by, a.qc_failure_reasons,
                                       a.warehouse_receipt_status, a.warehouse_receipt_at, a.warehouse_receipt_by,
                                       COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                       a.assy_status_history as assy_status_history,
                                       a.qc_status_history as qc_status_history,
                                       at.test_checklist as assy_test_checklist,
                                       at.notes as assy_test_notes,
                                       at.created_at as assy_tested_at,
                                       at.tester_username as assy_tester_username,
                                       COALESCE(u_tester.full_name, at.tester_username) as assy_tester_fullname,
                                       COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                       COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc
                                FROM assemblies a
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN assembly_tests at ON at.id = (
                                     SELECT id FROM assembly_tests 
                                     WHERE assembly_id = a.id AND stage = 'ASSY' 
                                     ORDER BY id DESC LIMIT 1
                                )
                                LEFT JOIN users u_tester ON (at.tester_username = u_tester.username OR at.tester_username = u_tester.full_name)
                                WHERE a.tray_id = ? 
                                ORDER BY a.set_number
                            """, (tray['id'],))
                            assemblies = [dict(r) for r in cur.fetchall()]
                            tray_created = tray.get('created_at')
                            for r in assemblies:
                                if not r.get('started_at'):
                                    r['started_at'] = r.get('finished_at') or tray_created
                                if not r.get('finished_at'):
                                    r['finished_at'] = r.get('started_at') or tray_created

                            # Query active downtime (pause)
                            cur.execute("""
                                SELECT id, reason, paused_at, operator,
                                       CAST((unixepoch('now') - unixepoch(paused_at)) AS INTEGER) as elapsed_seconds
                                FROM assembly_downtimes
                                WHERE tray_id = ? AND resumed_at IS NULL
                                ORDER BY id DESC LIMIT 1
                            """, (tray['id'],))
                            active_dt = cur.fetchone()
                            active_downtime = None
                            if active_dt:
                                active_downtime = {
                                    "is_paused": True,
                                    "downtime_id": active_dt['id'],
                                    "reason": active_dt['reason'],
                                    "operator": active_dt['operator'],
                                    "paused_at": active_dt['paused_at'],
                                    "elapsed_seconds": max(0, active_dt['elapsed_seconds'] or 0)
                                }

                            # Query active in-progress set
                            cur.execute("""
                                SELECT tray_id, tray_code, set_number, operator, part15, part1, part12, started_at, updated_at
                                FROM assembly_in_progress
                                WHERE tray_id = ?
                            """, (tray['id'],))
                            in_prog_row = cur.fetchone()
                            in_prog = dict(in_prog_row) if in_prog_row else None

                    return send_json(self, {"ok": True, "data": {
                        "tray": dict(tray),
                        "parts": parts,
                        "assemblies": assemblies,
                        "in_progress": in_prog,
                        "active_downtime": active_downtime
                    }})
                
                elif path == "/api/laporan":
                    start_date = query.get("start_date", [""])[0]
                    end_date = query.get("end_date", [""])[0]
                    operator = query.get("operator", [""])[0]
                    status_p = query.get("status", [""])[0]
                    tray_code = query.get("tray_code", [""])[0]
                    q_str = query.get("q", [""])[0].strip()
                    order_param = query.get("order", ["desc"])[0].lower()
                    sort_dir = "ASC" if order_param in ["asc", "ascending"] else "DESC"
                    nulls_clause = "NULLS FIRST" if sort_dir == "ASC" else "NULLS LAST"
                    sort_by_param = query.get("sort_by", ["date"])[0].lower()

                    if sort_by_param == "sn15":
                        order_clause = f"COALESCE(a.part15_sn, h.part15_sn) {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "sn1":
                        order_clause = f"COALESCE(a.part1_sn, h.part1_sn) {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "sn12":
                        order_clause = f"COALESCE(a.part12_sn, h.part12_sn) {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "doc":
                        order_clause = f"t.tray_code {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "mo":
                        order_clause = f"t.source_document_mo {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "operator":
                        order_clause = f"COALESCE(u_wh.full_name, t.pic_warehouse, u_op.full_name, t.assigned_operator, u_assy.full_name, a.pic_assembly, h.pic_assembly) {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "status":
                        order_clause = f"COALESCE(a.status, h.status, 'Good') {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    else:
                        order_clause = f"COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at) {sort_dir} {nulls_clause}, t.tray_code {sort_dir}, a.set_number {sort_dir}, a.id {sort_dir}"

                    conds = ["1=1"]
                    params = []
                    if start_date:
                        conds.append("(DATE(COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at)) >= DATE(?))")
                        params.append(start_date)
                    if end_date:
                        conds.append("(DATE(COALESCE(a.finished_at, h.finished_at, a.started_at, h.started_at, t.created_at)) <= DATE(?))")
                        params.append(end_date)
                    if operator:
                        conds.append("(COALESCE(a.pic_assembly, h.pic_assembly) LIKE ? OR u_assy.full_name LIKE ? OR t.pic_warehouse LIKE ? OR u_wh.full_name LIKE ? OR t.assigned_operator LIKE ? OR u_op.full_name LIKE ?)")
                        op_like = f"%{operator}%"
                        params.extend([op_like, op_like, op_like, op_like, op_like, op_like])
                    if status_p and status_p.strip() and status_p.strip() not in ['Semua Status', 'ALL']:
                        conds.append("UPPER(COALESCE(a.status, h.status, 'Good')) = UPPER(?)")
                        params.append(status_p.strip())
                    if tray_code:
                        conds.append("(t.tray_code LIKE ? OR a.assy_doc_number LIKE ? OR t.source_document_mo LIKE ?)")
                        tc_like = f"%{tray_code}%"
                        params.extend([tc_like, tc_like, tc_like])
                    if q_str:
                        conds.append("(COALESCE(a.part15_sn, h.part15_sn) LIKE ? OR COALESCE(a.part1_sn, h.part1_sn) LIKE ? OR COALESCE(a.part12_sn, h.part12_sn) LIKE ? OR t.tray_code LIKE ? OR t.source_document_mo LIKE ? OR COALESCE(a.reason, h.reason) LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like, q_like])

                    where_sql = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT a.id, a.tray_id, a.set_number, 
                                       COALESCE(a.part15_sn, h.part15_sn) as part15_sn, 
                                       COALESCE(a.part12_sn, h.part12_sn) as part12_sn, 
                                       COALESCE(a.part1_sn, h.part1_sn) as part1_sn, 
                                       COALESCE(a.status, h.status, 'Good') as status, 
                                       COALESCE(a.reason, h.reason) as reason,
                                       COALESCE(a.defective_parts, h.defective_parts) as defective_parts, 
                                       a.verified_status, a.is_declined, a.is_reregistered, a.declined_reason,
                                       COALESCE(a.started_at, h.started_at) as started_at, 
                                       COALESCE(a.finished_at, h.finished_at) as finished_at,
                                       COALESCE(u_assy.full_name, a.pic_assembly, h.pic_assembly) as pic_assembly,
                                       t.tray_code,
                                       COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                       COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                       t.source_document_mo,
                                       COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                       t.verified_at, t.verified_by_user,
                                       t.created_at as created_at,
                                       t.created_at as tray_created_at,
                                       a.assy_doc_number, a.qc_doc_number, a.qc_status,
                                       a.qc_failure_reasons, a.qc_notes, a.rework_reason,
                                       a.lifecycle_path,
                                       COALESCE(at.test_checklist, a.assy_test_checklist) as assy_test_checklist,
                                       COALESCE(at.test_checklist, a.assy_test_checklist) as test_checklist,
                                       COALESCE(at.failure_reasons, a.assy_failure_reasons) as assy_failure_reasons
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id 
                                LEFT JOIN assembly_wo_history h ON (h.tray_id = a.tray_id AND h.set_number = a.set_number)
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name OR h.pic_assembly = u_assy.username OR h.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                LEFT JOIN assembly_tests at ON at.id = (
                                    SELECT id FROM assembly_tests 
                                    WHERE assembly_id = a.id AND stage = 'ASSY' 
                                    ORDER BY id DESC LIMIT 1
                                )
                                WHERE {where_sql}
                                ORDER BY {order_clause}
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path in ["/api/qc/pending-items", "/api/qc/items"]:
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT 
                                    a.id as assembly_id, a.tray_id, a.set_number, 
                                    a.part15_sn, a.part12_sn, a.part1_sn,
                                    a.status as assembly_status, a.assy_test_status, a.qc_status, a.assy_doc_number, a.qc_doc_number,
                                    COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                    a.assy_status_history as assy_status_history,
                                    a.qc_status_history as qc_status_history,
                                    a.finished_at, a.started_at,
                                    a.qc_tested_at, a.qc_tested_by, a.qc_notes, a.qc_failure_reasons, a.rework_reason,
                                    COALESCE(a.assy_test_checklist, at_sub.test_checklist) as assy_test_checklist,
                                    at_sub.failure_reasons as assy_failure_reasons,
                                    at_sub.tester_username as assy_tester_username,
                                    at_sub.created_at as assy_tested_at,
                                    COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                    COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc,
                                    t.tray_code, t.source_document_mo, t.product_type
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN assembly_tests at_sub ON at_sub.id = (
                                    SELECT id FROM assembly_tests 
                                    WHERE assembly_id = a.id AND stage = 'ASSY' 
                                    ORDER BY id DESC LIMIT 1
                                )
                                WHERE (
                                    -- 1. PENDING QC: Wajib Good di perakitan & Good di TF1 (lolos Uji Fungsional 1)
                                    (
                                        a.status = 'Good' 
                                        AND COALESCE(a.assy_test_status, '') = 'Good'
                                        AND a.assy_doc_number IS NOT NULL
                                        AND (a.qc_status = 'PENDING_QC' OR (a.qc_status = 'PENDING' AND a.assy_doc_number IS NOT NULL))
                                        AND a.qc_status NOT IN ('HOLD_ASSY', 'REWORK_ASSY', 'HOLD', 'RU', 'HOLD_QC', 'REWORK_QC', 'GOOD')
                                        AND a.qc_doc_number IS NULL
                                        AND NOT EXISTS (SELECT 1 FROM assembly_tests at_qc WHERE at_qc.assembly_id = a.id AND at_qc.stage = 'QC')
                                    )
                                    OR 
                                    -- 2. QC HISTORY: Unit yang benar-benar telah diuji pada tahap QC (stage = 'QC')
                                    (
                                        (
                                            a.qc_doc_number IS NOT NULL 
                                            OR a.qc_tested_at IS NOT NULL 
                                            OR EXISTS (SELECT 1 FROM assembly_tests at_qc WHERE at_qc.assembly_id = a.id AND at_qc.stage = 'QC')
                                        )
                                        AND (
                                            a.qc_status IN ('GOOD', 'HOLD_QC', 'REWORK_QC')
                                            OR (a.qc_status = 'RU' AND EXISTS (SELECT 1 FROM assembly_tests at_qc2 WHERE at_qc2.assembly_id = a.id AND at_qc2.stage = 'QC'))
                                        )
                                    )
                                )
                                ORDER BY a.finished_at DESC NULLS LAST, a.id DESC
                            """)
                            all_rows = [dict(r) for r in cur.fetchall()]
                            pending_rows = [r for r in all_rows if (r.get('qc_status') in ['PENDING_QC', 'PENDING'] and r.get('assembly_status') == 'Good' and r.get('assy_test_status') == 'Good' and r.get('qc_status') not in ['HOLD_ASSY', 'REWORK_ASSY', 'HOLD', 'RU'])]
                            history_rows = [r for r in all_rows if (r.get('qc_status') in ['GOOD', 'HOLD_QC', 'REWORK_QC'] or (r.get('qc_status') == 'RU' and r.get('qc_doc_number')))]
                    
                    if path == "/api/qc/items":
                        return send_json(self, {"ok": True, "data": all_rows, "pending": pending_rows, "history": history_rows})
                    else:
                        return send_json(self, {"ok": True, "data": pending_rows, "history_data": history_rows, "all_data": all_rows})

                elif path == "/api/production/hold-items":
                    product_type = query.get("product_type", [""])[0].strip()
                    source = query.get("source", [""])[0].strip().upper()
                    q_str = query.get("q", [""])[0].strip()

                    conds = ["""(
                        a.qc_status IN ('HOLD_QC', 'HOLD_ASSY', 'REWORK_QC', 'RU')
                        OR a.assy_test_status = 'RU'
                        OR a.status IN ('Dirakit Ulang', 'RU')
                        OR a.verified_status IN ('HOLD_QC_GUDANG', 'WAITING_REASSEMBLY', 'COMPLETED_PU', 'PENDING_ADMIN', 'PENDING_ADMIN_QC', 'PENDING_ADMIN_ASSY_REREG', 'PENDING_REPLACEMENT')
                        OR (a.verified_status = 'HOLD_GUDANG' AND a.qc_status = 'HOLD_ASSY')
                    )""",
                    """(
                        EXISTS (SELECT 1 FROM assembly_tests at_chk WHERE at_chk.assembly_id = a.id)
                        OR a.qc_failure_reasons IS NOT NULL
                        OR a.verified_status IN ('PENDING_ADMIN', 'PENDING_ADMIN_QC', 'PENDING_ADMIN_ASSY_REREG', 'PENDING_REPLACEMENT')
                    )"""]
                    params = []

                    if product_type and product_type != 'ALL':
                        conds.append("t.product_type = ?")
                        params.append(product_type)

                    if source in ['ASSY', 'TESTER_ASSEMBLY']:
                        conds.append("(a.qc_status = 'HOLD_ASSY' OR a.assy_test_status IN ('Hold', 'RU') OR a.verified_status IN ('PENDING_ADMIN', 'PENDING_ADMIN_ASSY_REREG') OR (a.qc_status != 'HOLD_QC' AND a.status IN ('Dirakit Ulang', 'RU')))")
                    elif source == 'QC':
                        conds.append("(a.qc_status IN ('HOLD_QC', 'REWORK_QC') OR a.verified_status IN ('HOLD_QC_GUDANG', 'PENDING_ADMIN_QC') OR (a.qc_status = 'RU' AND (a.qc_failure_reasons IS NOT NULL OR EXISTS (SELECT 1 FROM assembly_tests at_chk2 WHERE at_chk2.assembly_id = a.id AND at_chk2.stage = 'QC'))))")

                    if q_str:
                        conds.append("(a.part15_sn LIKE ? OR a.part12_sn LIKE ? OR a.part1_sn LIKE ? OR t.tray_code LIKE ? OR a.reason LIKE ? OR a.qc_failure_reasons LIKE ? OR at.failure_reasons LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like, q_like, q_like])

                    where_clause = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT a.id as assembly_id, a.tray_id, a.set_number,
                                       a.part15_sn, a.part12_sn, a.part1_sn,
                                       a.status as assembly_status, a.qc_status, a.verified_status, a.lifecycle_path,
                                       a.assy_test_status,
                                       a.is_reregistered, a.is_declined, a.declined_reason,
                                       a.reason as assembly_reason, a.qc_failure_reasons, a.qc_notes, a.rework_reason,
                                       a.started_at, a.finished_at, a.qc_tested_at,
                                       a.assy_doc_number, a.qc_doc_number,
                                       t.tray_code, t.source_document_mo,
                                       COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly,
                                       COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc,
                                       at.tester_username as assy_tester_username,
                                       COALESCE(u_tester.full_name, at.tester_username) as assy_tester_fullname,
                                       at.failure_reasons as assy_failure_reasons,
                                       at.test_checklist as assy_test_checklist,
                                       at.created_at as assy_tested_at,
                                       at_qc.tester_username as qc_tester_username,
                                       COALESCE(u_qc_tester.full_name, at_qc.tester_username, a.qc_tested_by) as qc_tester_fullname,
                                       COALESCE(at_qc.failure_reasons, a.qc_failure_reasons) as qc_failure_reasons,
                                       at_qc.test_checklist as qc_test_checklist,
                                       at_qc.created_at as qc_test_tested_at,
                                       (SELECT prr.status FROM part_replacement_requests prr WHERE prr.assembly_id = a.id ORDER BY prr.id DESC LIMIT 1) as latest_replacement_status,
                                       (SELECT 1 FROM part_replacement_requests prr WHERE prr.assembly_id = a.id AND prr.status = 'PENDING') as has_pending_replacement,
                                       (SELECT 1 FROM re_register_requests rrr WHERE rrr.sn IN (a.part15_sn, a.part1_sn, a.part12_sn) AND rrr.status = 'PENDING') as has_pending_reregister,
                                       CASE 
                                            WHEN a.verified_status = 'PENDING_ADMIN_QC' OR a.qc_status IN ('HOLD_QC', 'REWORK_QC') OR (a.qc_status = 'RU' AND (at_qc.id IS NOT NULL OR a.qc_failure_reasons IS NOT NULL)) THEN 'QC'
                                            ELSE 'TESTER_ASSEMBLY'
                                       END as hold_source,
                                       CASE 
                                            WHEN a.qc_status IN ('HOLD_QC', 'REWORK_QC') THEN COALESCE(at_qc.failure_reasons, a.qc_failure_reasons, a.qc_notes, 'Tertahan di Quality Control (QC)')
                                            ELSE COALESCE(at.failure_reasons, a.assy_failure_reasons, 'Gagal dalam pengujian Tester Assembly (TF1)')
                                       END as display_reason
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN assembly_tests at ON at.id = (
                                    SELECT id FROM assembly_tests 
                                    WHERE assembly_id = a.id AND stage = 'ASSY' 
                                    ORDER BY id DESC LIMIT 1
                                )
                                LEFT JOIN users u_tester ON (at.tester_username = u_tester.username OR at.tester_username = u_tester.full_name)
                                LEFT JOIN assembly_tests at_qc ON at_qc.id = (
                                    SELECT id FROM assembly_tests 
                                    WHERE assembly_id = a.id AND stage = 'QC' 
                                    ORDER BY id DESC LIMIT 1
                                )
                                LEFT JOIN users u_qc_tester ON (at_qc.tester_username = u_qc_tester.username OR at_qc.tester_username = u_qc_tester.full_name)
                                WHERE {where_clause}
                                ORDER BY COALESCE(a.qc_tested_at, at_qc.created_at, at.created_at, a.finished_at) DESC NULLS LAST, a.id DESC
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]

                    return send_json(self, {"ok": True, "data": rows, "count": len(rows)})

                elif path == "/api/production/products":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT DISTINCT COALESCE(product_type, 'Nanggala P9') as product_type 
                                FROM trays 
                                WHERE product_type IS NOT NULL AND product_type != ''
                                ORDER BY product_type
                            """)
                            products = [r['product_type'] for r in cur.fetchall()]
                            if not products or 'Nanggala P9' not in products:
                                products = ['Nanggala P9'] + [p for p in products if p != 'Nanggala P9']
                    return send_json(self, {"ok": True, "data": products})

                elif path == "/api/production/assembly-results":
                    q_str = query.get("q", [""])[0].strip()
                    product_type = query.get("product_type", [""])[0].strip()
                    status_filter = query.get("status", [""])[0].strip().upper()
                    tray_code_param = query.get("tray_code", [""])[0].strip()
                    operator_param = query.get("operator", [""])[0].strip()
                    assy_doc_param = query.get("assy_doc_number", [""])[0].strip() or query.get("doc_number", [""])[0].strip()

                    conds = ["1=1"]
                    params = []

                    if product_type and product_type != 'ALL':
                        conds.append("t.product_type = ?")
                        params.append(product_type)

                    if tray_code_param:
                        conds.append("t.tray_code LIKE ?")
                        params.append(f"%{tray_code_param}%")

                    if operator_param and operator_param != 'ALL' and operator_param != 'Semua Operator':
                        conds.append("(a.pic_assembly = ? OR t.assigned_operator = ? OR u_assy.full_name = ? OR u_assy.username = ?)")
                        params.extend([operator_param, operator_param, operator_param, operator_param])

                    if assy_doc_param and assy_doc_param != 'ALL' and assy_doc_param != 'Semua Dokumen':
                        conds.append("(t.tray_code = ? OR a.assy_doc_number = ?)")
                        params.extend([assy_doc_param, assy_doc_param])

                    if status_filter and status_filter != 'ALL':
                        conds.append("UPPER(COALESCE(a.status, 'Good')) = ?")
                        params.append(status_filter)

                    if q_str:
                        conds.append("(a.part15_sn LIKE ? OR a.part1_sn LIKE ? OR a.part12_sn LIKE ? OR t.tray_code LIKE ? OR t.source_document_mo LIKE ? OR a.reason LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like, q_like])

                    where_sql = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT t.id as tray_id, t.tray_code, t.source_document_mo,
                                       COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       t.status as tray_status, t.created_at as tray_created_at,
                                       COALESCE(u_assy.full_name, a.pic_assembly, t.assigned_operator) as pic_assembly,
                                       COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                       a.id as assembly_id, a.set_number,
                                       a.part15_sn, a.part1_sn, a.part12_sn,
                                       COALESCE(a.defective_parts, '') as defective_parts,
                                       COALESCE(a.status, 'Good') as assembly_status,
                                       COALESCE(a.original_assembly_status, (
                                           SELECT wo.status FROM assembly_wo_history wo 
                                           WHERE wo.tray_id = a.tray_id AND wo.set_number = a.set_number 
                                           ORDER BY wo.id ASC LIMIT 1
                                       ), CASE 
                                           WHEN (a.defective_parts IS NOT NULL AND a.defective_parts != '' AND a.defective_parts != '-') THEN 'Hold'
                                           WHEN (a.reason IS NOT NULL AND a.reason != '' AND a.reason != '-' AND NOT a.reason LIKE '%Dirakit Ulang%') THEN 'Hold'
                                           WHEN a.status IN ('Hold', 'Rework', 'Scrap') THEN a.status
                                           ELSE 'Good' 
                                       END) as original_assembly_status,
                                       COALESCE(a.qc_status, 'PENDING') as qc_status,
                                       a.verified_status, a.lifecycle_path,
                                       a.is_reregistered, a.is_declined, a.declined_reason,
                                       a.reason as assembly_reason, a.qc_failure_reasons, a.rework_reason,
                                       a.started_at, a.finished_at, a.qc_tested_at,
                                       a.assy_doc_number, a.qc_doc_number,
                                       COALESCE(u_qc.full_name, a.qc_tested_by) as pic_qc,
                                       at.failure_reasons as assy_failure_reasons,
                                       COALESCE(u_tester.full_name, at.tester_username) as assy_tester_name,
                                       (SELECT prr.status FROM part_replacement_requests prr WHERE prr.assembly_id = a.id ORDER BY prr.id DESC LIMIT 1) as latest_replacement_status,
                                       (SELECT 1 FROM part_replacement_requests prr WHERE prr.assembly_id = a.id AND prr.status = 'PENDING' AND a.verified_status IN ('PENDING_REPLACEMENT', 'PENDING_ADMIN', 'PENDING_ADMIN_QC', 'PENDING_ADMIN_ASSY_REREG')) as has_pending_replacement,
                                       (SELECT 1 FROM re_register_requests rrr WHERE rrr.sn IN (a.part15_sn, a.part1_sn, a.part12_sn) AND rrr.status = 'PENDING' AND a.verified_status IN ('PENDING_ADMIN_ASSY_REREG', 'PENDING_ADMIN', 'PENDING_ADMIN_QC')) as has_pending_reregister
                                FROM trays t
                                JOIN assemblies a ON a.tray_id = t.id
                                LEFT JOIN users u_assy ON (u_assy.username = COALESCE(NULLIF(a.pic_assembly, ''), t.assigned_operator) OR u_assy.full_name = COALESCE(NULLIF(a.pic_assembly, ''), t.assigned_operator))
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_qc ON (a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN assembly_tests at ON at.id = (
                                    SELECT id FROM assembly_tests 
                                    WHERE assembly_id = a.id AND stage = 'ASSY' 
                                    ORDER BY id DESC LIMIT 1
                                )
                                LEFT JOIN users u_tester ON (at.tester_username = u_tester.username OR at.tester_username = u_tester.full_name)
                                WHERE {where_sql}
                                ORDER BY t.tray_code ASC, a.set_number ASC
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]

                    # Group by tray (deduplicating assembly_id)
                    trays_dict = {}
                    seen_assembly_ids = set()
                    unique_rows = []
                    for r in rows:
                        aid = r.get('assembly_id')
                        if aid and aid in seen_assembly_ids:
                            continue
                        if aid:
                            seen_assembly_ids.add(aid)
                        unique_rows.append(r)
                        tid = r['tray_id']
                        if tid not in trays_dict:
                            trays_dict[tid] = {
                                'tray_id': tid,
                                'tray_code': r['tray_code'],
                                'source_document_mo': r['source_document_mo'],
                                'product_type': r['product_type'],
                                'tray_status': r['tray_status'],
                                'tray_created_at': r['tray_created_at'],
                                'pic_assembly': r['pic_assembly'],
                                'pic_warehouse': r['pic_warehouse'],
                                'sets_count': 0,
                                'good_count': 0,
                                'hold_count': 0,
                                'rework_count': 0,
                                'scrap_count': 0,
                                'items': []
                            }
                        trays_dict[tid]['items'].append(r)
                        trays_dict[tid]['sets_count'] += 1
                        st = (r.get('assembly_status') or 'Good').strip().upper()
                        if st == 'HOLD':
                            trays_dict[tid]['hold_count'] += 1
                        elif st == 'REWORK':
                            trays_dict[tid]['rework_count'] += 1
                        elif st == 'SCRAP':
                            trays_dict[tid]['scrap_count'] += 1
                        else:
                            trays_dict[tid]['good_count'] += 1

                    grouped = list(trays_dict.values())
                    return send_json(self, {"ok": True, "data": grouped, "total_trays": len(grouped), "total_items": len(unique_rows)})

                elif path == "/api/assemblies/pending-reassembly":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT a.id, a.id as assembly_id, a.tray_id, a.set_number, a.status, a.verified_status,
                                       a.part15_sn, a.part1_sn, a.part12_sn,
                                       a.reason, a.rework_reason, a.started_at, a.finished_at,
                                       COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly,
                                       t.tray_code, t.source_document_mo, COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       (SELECT prr.replacements_json FROM part_replacement_requests prr WHERE prr.assembly_id = a.id AND prr.status = 'APPROVED' ORDER BY prr.id DESC LIMIT 1) as replacement_details,
                                       (SELECT prr.verified_at FROM part_replacement_requests prr WHERE prr.assembly_id = a.id AND prr.status = 'APPROVED' ORDER BY prr.id DESC LIMIT 1) as replaced_at,
                                       (SELECT prr.admin_username FROM part_replacement_requests prr WHERE prr.assembly_id = a.id AND prr.status = 'APPROVED' ORDER BY prr.id DESC LIMIT 1) as replaced_by_admin
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)
                                WHERE a.verified_status = 'WAITING_REASSEMBLY'
                                ORDER BY a.id ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                            for r in rows:
                                if r.get('replacement_details'):
                                    try:
                                        r['replacements'] = json.loads(r['replacement_details'])
                                    except Exception:
                                        r['replacements'] = []
                                else:
                                    r['replacements'] = []
                            return send_json(self, {"ok": True, "data": rows, "count": len(rows)})

                elif path == "/api/reports/assy-tests":
                    start_date = query.get("start_date", [""])[0]
                    end_date = query.get("end_date", [""])[0]
                    operator = query.get("operator", [""])[0]
                    doc_number = query.get("doc_number", [""])[0]
                    status_p = query.get("status", [""])[0].strip()
                    q_str = query.get("q", [""])[0].strip()
                    order_param = query.get("order", ["desc"])[0].lower()
                    sort_dir = "ASC" if order_param in ["asc", "ascending"] else "DESC"
                    nulls_clause = "NULLS FIRST" if sort_dir == "ASC" else "NULLS LAST"
                    sort_by_param = query.get("sort_by", ["date"])[0].lower()

                    if sort_by_param == "sn15":
                        order_clause = f"t.part15_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "sn1":
                        order_clause = f"t.part1_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "sn12":
                        order_clause = f"t.part12_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "doc":
                        order_clause = f"t.doc_number {sort_dir} {nulls_clause}, t.assy_doc_number {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "operator":
                        order_clause = f"t.tester_full_name {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "status":
                        order_clause = f"t.status {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    else:
                        order_clause = f"t.created_at {sort_dir} {nulls_clause}, t.id {sort_dir}"

                    conds = ["1=1"]
                    params = []
                    if start_date:
                        conds.append("DATE(t.created_at) >= DATE(?)")
                        params.append(start_date)
                    if end_date:
                        conds.append("DATE(t.created_at) <= DATE(?)")
                        params.append(end_date)
                    if operator:
                        conds.append("(t.tester_username LIKE ? OR t.tester_full_name LIKE ?)")
                        op_like = f"%{operator}%"
                        params.extend([op_like, op_like])
                    if doc_number:
                        conds.append("(t.doc_number LIKE ? OR t.assy_doc_number LIKE ?)")
                        d_like = f"%{doc_number}%"
                        params.extend([d_like, d_like])
                    if status_p and status_p != 'Semua Status':
                        if status_p.upper() == 'MENUNGGU':
                            conds.append("(t.status LIKE '%Menunggu%' OR t.status LIKE '%Belum%')")
                        else:
                            conds.append("UPPER(t.status) = ?")
                            params.append(status_p.upper())
                    if q_str:
                        conds.append("(t.part15_sn LIKE ? OR t.part1_sn LIKE ? OR t.part12_sn LIKE ? OR t.doc_number LIKE ? OR t.assy_doc_number LIKE ? OR t.failure_reasons LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like, q_like])

                    where_clause = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                WITH latest_assy_tests AS (
                                    SELECT t.*
                                    FROM assembly_tests t
                                    WHERE t.stage = 'ASSY'
                                      AND t.id = (
                                          SELECT MAX(sub.id) FROM assembly_tests sub 
                                          WHERE sub.stage = 'ASSY' AND sub.assembly_id = t.assembly_id
                                      )
                                ),
                                all_assy_tests AS (
                                    SELECT t.id, t.stage,
                                           COALESCE(NULLIF(t.doc_number, '-'), CASE WHEN t.tray_code LIKE '%ASSY-PU/%' THEN 'Assy/QC/PU/' || SUBSTR(t.tray_code, 12) WHEN t.tray_code LIKE '%-PU%' THEN 'Assy/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(t.tray_code, INSTR(t.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'Assy/QC/' || SUBSTR(t.tray_code, INSTR(t.tray_code, 'WH/ASSY/') + 8) END) as doc_number,
                                           t.tray_id, t.tray_code, t.set_number, t.assembly_id,
                                           COALESCE(t.part15_sn, t.p9_sn) as part15_sn,
                                           t.part1_sn, t.part12_sn,
                                           t.tester_username, t.test_checklist,
                                           t.status, t.failure_reasons, t.notes,
                                           COALESCE(t.lifecycle_path, a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(t.status_history, a.assy_status_history, 'Good') as status_history,
                                           t.created_at,
                                           COALESCE(t.tray_code, a.assy_doc_number) as assy_doc_number,
                                           COALESCE(u.full_name, t.tester_username) as tester_full_name
                                    FROM latest_assy_tests t
                                    LEFT JOIN assemblies a ON t.assembly_id = a.id
                                    LEFT JOIN users u ON (t.tester_username = u.username OR t.tester_username = u.full_name)

                                    UNION ALL

                                    SELECT (1000000 + a.id) as id, 'ASSY' as stage,
                                           COALESCE(a.assy_doc_number, CASE WHEN tr.tray_code LIKE '%ASSY-PU/%' THEN 'Assy/QC/PU/' || SUBSTR(tr.tray_code, 12) WHEN tr.tray_code LIKE '%-PU%' THEN 'Assy/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(tr.tray_code, INSTR(tr.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'Assy/QC/' || SUBSTR(tr.tray_code, INSTR(tr.tray_code, 'WH/ASSY/') + 8) END) as doc_number,
                                           a.tray_id, tr.tray_code, a.set_number, a.id as assembly_id,
                                           a.part15_sn, a.part1_sn, a.part12_sn,
                                           COALESCE(tr.assigned_operator, a.pic_assembly, '-') as tester_username,
                                           '[]' as test_checklist,
                                           'Menunggu Test Functional 1' as status,
                                           NULL as failure_reasons,
                                           'Menunggu pengujian oleh Operator Assembly' as notes,
                                           COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(a.assy_status_history, 'Menunggu Uji TF1') as status_history,
                                           COALESCE(a.finished_at, a.started_at, tr.created_at) as created_at,
                                           COALESCE(tr.tray_code, a.assy_doc_number) as assy_doc_number,
                                           COALESCE(u_op.full_name, tr.assigned_operator, a.pic_assembly, '-') as tester_full_name
                                    FROM assemblies a
                                    JOIN trays tr ON a.tray_id = tr.id
                                    LEFT JOIN users u_op ON (tr.assigned_operator = u_op.username OR tr.assigned_operator = u_op.full_name)
                                    WHERE (
                                        (SELECT wo.status FROM assembly_wo_history wo WHERE wo.tray_id = a.tray_id AND wo.set_number = a.set_number ORDER BY wo.id ASC LIMIT 1) = 'Good'
                                        OR (a.status = 'Good' AND (a.reason IS NULL OR a.reason = '' OR a.reason = '-') AND (a.defective_parts IS NULL OR a.defective_parts = ''))
                                    )
                                      AND a.id NOT IN (SELECT assembly_id FROM assembly_tests WHERE stage = 'ASSY' AND assembly_id IS NOT NULL)
                                )
                                SELECT t.* FROM all_assy_tests t
                                WHERE {where_clause}
                                ORDER BY {order_clause}
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/reports/qc-tests":
                    start_date = query.get("start_date", [""])[0]
                    end_date = query.get("end_date", [""])[0]
                    operator = query.get("operator", [""])[0]
                    doc_number = query.get("doc_number", [""])[0]
                    status_p = query.get("status", [""])[0].strip()
                    q_str = query.get("q", [""])[0].strip()
                    order_param = query.get("order", ["desc"])[0].lower()
                    sort_dir = "ASC" if order_param in ["asc", "ascending"] else "DESC"
                    nulls_clause = "NULLS FIRST" if sort_dir == "ASC" else "NULLS LAST"
                    sort_by_param = query.get("sort_by", ["date"])[0].lower()

                    if sort_by_param == "sn15":
                        order_clause = f"t.part15_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "sn1":
                        order_clause = f"t.part1_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "sn12":
                        order_clause = f"t.part12_sn {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "doc":
                        order_clause = f"t.doc_number {sort_dir} {nulls_clause}, t.tf1_doc_number {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "operator":
                        order_clause = f"t.tester_full_name {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    elif sort_by_param == "status":
                        order_clause = f"t.status {sort_dir} {nulls_clause}, t.id {sort_dir}"
                    else:
                        order_clause = f"t.created_at {sort_dir} {nulls_clause}, t.id {sort_dir}"

                    conds = ["1=1"]
                    params = []
                    if start_date:
                        conds.append("DATE(t.created_at) >= DATE(?)")
                        params.append(start_date)
                    if end_date:
                        conds.append("DATE(t.created_at) <= DATE(?)")
                        params.append(end_date)
                    if operator:
                        conds.append("(t.tester_username LIKE ? OR t.tester_full_name LIKE ?)")
                        op_like = f"%{operator}%"
                        params.extend([op_like, op_like])
                    if doc_number:
                        conds.append("(t.doc_number LIKE ? OR t.tf1_doc_number LIKE ? OR t.assy_doc_number LIKE ?)")
                        d_like = f"%{doc_number}%"
                        params.extend([d_like, d_like, d_like])
                    if status_p and status_p != 'Semua Status':
                        if status_p.upper() == 'MENUNGGU':
                            conds.append("(t.status LIKE '%Menunggu%' OR t.status LIKE '%Belum%')")
                        else:
                            conds.append("UPPER(t.status) = ?")
                            params.append(status_p.upper())
                    if q_str:
                        conds.append("(t.part15_sn LIKE ? OR t.part1_sn LIKE ? OR t.part12_sn LIKE ? OR t.doc_number LIKE ? OR t.tf1_doc_number LIKE ? OR t.assy_doc_number LIKE ? OR t.failure_reasons LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like, q_like, q_like])

                    where_clause = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                WITH latest_qc_tests AS (
                                    SELECT t.*
                                    FROM assembly_tests t
                                    WHERE t.stage = 'QC'
                                      AND t.id = (
                                          SELECT MAX(sub.id) FROM assembly_tests sub 
                                          WHERE sub.stage = 'QC' AND sub.assembly_id = t.assembly_id
                                      )
                                ),
                                latest_assy_tests AS (
                                    SELECT t.*
                                    FROM assembly_tests t
                                    WHERE t.stage = 'ASSY'
                                      AND t.id = (
                                          SELECT MAX(sub.id) FROM assembly_tests sub 
                                          WHERE sub.stage = 'ASSY' AND sub.assembly_id = t.assembly_id
                                      )
                                ),
                                all_qc_tests AS (
                                    SELECT t.id, t.stage,
                                           COALESCE(NULLIF(t.doc_number, '-'), CASE WHEN t.tray_code LIKE '%ASSY-PU/%' THEN 'WH/QC/PU/' || SUBSTR(t.tray_code, 12) WHEN t.tray_code LIKE '%-PU%' THEN 'WH/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(t.tray_code, INSTR(t.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'WH/QC/' || SUBSTR(t.tray_code, INSTR(t.tray_code, 'WH/ASSY/') + 8) END) as doc_number,
                                           COALESCE(NULLIF(at_prev.doc_number, '-'), CASE WHEN t.tray_code LIKE '%ASSY-PU/%' THEN 'Assy/QC/PU/' || SUBSTR(t.tray_code, 12) WHEN t.tray_code LIKE '%-PU%' THEN 'Assy/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(t.tray_code, INSTR(t.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'Assy/QC/' || SUBSTR(t.tray_code, INSTR(t.tray_code, 'WH/ASSY/') + 8) END) as tf1_doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number) as assy_doc_number,
                                           t.tray_id, t.tray_code, t.set_number, t.assembly_id,
                                           COALESCE(t.part15_sn, t.p9_sn) as part15_sn,
                                           t.part1_sn, t.part12_sn,
                                           t.tester_username, t.test_checklist,
                                           t.test_checklist as qc_test_checklist,
                                           t.created_at as qc_tested_at,
                                           t.tester_username as qc_tested_by,
                                           COALESCE(u.full_name, t.tester_username) as qc_tester_name,
                                           t.status, t.failure_reasons, t.notes,
                                           COALESCE(t.lifecycle_path, a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(t.status_history, a.qc_status_history, 'Good QC') as status_history,
                                           t.created_at,
                                           COALESCE(u.full_name, t.tester_username) as tester_full_name,
                                           COALESCE(a.status, 'Good') as assembly_status,
                                           COALESCE(at_prev.status, a.assy_test_status, 'Good') as tf1_status,
                                           COALESCE(at_prev.failure_reasons, a.assy_failure_reasons) as tf1_failure_reasons,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as assy_test_checklist,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as tf1_test_checklist,
                                           a.pic_assembly,
                                           COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name,
                                           a.started_at, a.finished_at,
                                           t.status as qc_test_status,
                                           COALESCE(t.failure_reasons, t.notes, a.qc_failure_reasons) as qc_failure_reasons
                                    FROM latest_qc_tests t
                                    LEFT JOIN assemblies a ON t.assembly_id = a.id
                                    LEFT JOIN latest_assy_tests at_prev ON at_prev.assembly_id = t.assembly_id
                                    LEFT JOIN users u ON (t.tester_username = u.username OR t.tester_username = u.full_name)
                                    LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)
                                    WHERE (
                                        (COALESCE(at_prev.status, a.assy_test_status, '') = 'Good' AND a.qc_status != 'HOLD_ASSY')
                                    )

                                    UNION ALL

                                    SELECT (2000000 + a.id) as id, 'QC' as stage,
                                           COALESCE(a.qc_doc_number, CASE WHEN tr.tray_code LIKE '%ASSY-PU/%' THEN 'WH/QC/PU/' || SUBSTR(tr.tray_code, 12) WHEN tr.tray_code LIKE '%-PU%' THEN 'WH/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(tr.tray_code, INSTR(tr.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'WH/QC/' || SUBSTR(tr.tray_code, INSTR(tr.tray_code, 'WH/ASSY/') + 8) END) as doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number, CASE WHEN tr.tray_code LIKE '%ASSY-PU/%' THEN 'Assy/QC/PU/' || SUBSTR(tr.tray_code, 12) WHEN tr.tray_code LIKE '%-PU%' THEN 'Assy/QC/PU/' || PRINTF('%06d', CAST(SUBSTR(tr.tray_code, INSTR(tr.tray_code, '-PU') + 3) AS INTEGER)) ELSE 'Assy/QC/' || SUBSTR(tr.tray_code, INSTR(tr.tray_code, 'WH/ASSY/') + 8) END) as tf1_doc_number,
                                           COALESCE(at_prev.doc_number, a.assy_doc_number) as assy_doc_number,
                                           a.tray_id, tr.tray_code, a.set_number, a.id as assembly_id,
                                           a.part15_sn, a.part1_sn, a.part12_sn,
                                           '-' as tester_username,
                                           '[]' as test_checklist,
                                           '[]' as qc_test_checklist,
                                           NULL as qc_tested_at,
                                           NULL as qc_tested_by,
                                           NULL as qc_tester_name,
                                           'Menunggu Test Functional 2' as status,
                                           NULL as failure_reasons,
                                           'Menunggu pengujian mutu oleh Inspector QC' as notes,
                                           COALESCE(a.lifecycle_path, 'FRESH_GOOD') as lifecycle_path,
                                           COALESCE(a.qc_status_history, 'Menunggu QC') as status_history,
                                           COALESCE(at_prev.created_at, a.finished_at, tr.created_at) as created_at,
                                           'Menunggu Inspector QC' as tester_full_name,
                                           COALESCE(a.status, 'Good') as assembly_status,
                                           COALESCE(at_prev.status, a.assy_test_status, 'Good') as tf1_status,
                                           COALESCE(at_prev.failure_reasons, a.assy_failure_reasons) as tf1_failure_reasons,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as assy_test_checklist,
                                           COALESCE(at_prev.test_checklist, a.assy_test_checklist) as tf1_test_checklist,
                                           a.pic_assembly,
                                           COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name,
                                           a.started_at, a.finished_at,
                                           'Menunggu Test Functional 2' as qc_test_status,
                                           NULL as qc_failure_reasons
                                    FROM assemblies a
                                    JOIN trays tr ON a.tray_id = tr.id
                                    LEFT JOIN latest_assy_tests at_prev ON at_prev.assembly_id = a.id
                                    LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)

                                    WHERE a.id NOT IN (SELECT assembly_id FROM assembly_tests WHERE stage = 'QC' AND assembly_id IS NOT NULL)
                                      AND (
                                          a.id IN (SELECT assembly_id FROM assembly_tests WHERE stage = 'ASSY' AND UPPER(status) = 'GOOD')
                                          OR (
                                              a.assy_doc_number IS NOT NULL 
                                              AND COALESCE(a.assy_test_status, 'Good') = 'Good'
                                              AND a.id NOT IN (SELECT assembly_id FROM assembly_tests WHERE stage = 'ASSY' AND UPPER(status) != 'GOOD')
                                          )
                                      )
                                    AND a.id NOT IN (SELECT assembly_id FROM assembly_tests WHERE stage = 'QC' AND assembly_id IS NOT NULL)
                                )
                                SELECT t.* FROM all_qc_tests t
                                WHERE {where_clause}
                                ORDER BY {order_clause}
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/reports/warehouse-receipts":
                    start_date = query.get("start_date", [""])[0]
                    end_date = query.get("end_date", [""])[0]
                    operator = query.get("operator", [""])[0]
                    doc_number = query.get("doc_number", [""])[0]
                    status_p = query.get("status", [""])[0]
                    q_str = query.get("q", [""])[0].strip()
                    order_param = query.get("order", ["desc"])[0].lower()
                    sort_dir = "ASC" if order_param in ["asc", "ascending"] else "DESC"
                    nulls_clause = "NULLS FIRST" if sort_dir == "ASC" else "NULLS LAST"
                    sort_by_param = query.get("sort_by", ["date"])[0].lower()

                    if sort_by_param == "sn15":
                        order_clause = f"a.part15_sn {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "sn1":
                        order_clause = f"a.part1_sn {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "sn12":
                        order_clause = f"a.part12_sn {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "doc":
                        order_clause = f"a.qc_doc_number {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "operator":
                        order_clause = f"COALESCE(u_wh.full_name, a.warehouse_receipt_by) {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    elif sort_by_param == "status":
                        order_clause = f"COALESCE(a.warehouse_receipt_status, 'PENDING') {sort_dir} {nulls_clause}, a.id {sort_dir}"
                    else:
                        order_clause = f"COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at) {sort_dir} {nulls_clause}, a.id {sort_dir}"

                    conds = ["(UPPER(COALESCE(at_qc.status, a.qc_status, '')) IN ('GOOD', 'GOOD_QC', 'PASS', 'PASSED'))"]
                    params = []
                    if start_date:
                        conds.append("DATE(COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at)) >= DATE(?)")
                        params.append(start_date)
                    if end_date:
                        conds.append("DATE(COALESCE(a.warehouse_receipt_at, a.qc_tested_at, a.finished_at)) <= DATE(?)")
                        params.append(end_date)
                    if operator:
                        conds.append("(a.warehouse_receipt_by LIKE ? OR u_wh.full_name LIKE ?)")
                        op_like = f"%{operator}%"
                        params.extend([op_like, op_like])
                    if doc_number:
                        conds.append("a.qc_doc_number LIKE ?")
                        d_like = f"%{doc_number}%"
                        params.append(d_like)
                    if status_p:
                        if status_p.upper() == 'PENDING':
                            conds.append("(a.warehouse_receipt_status = 'PENDING' OR a.warehouse_receipt_status IS NULL OR a.warehouse_receipt_status = '')")
                        elif status_p.upper() in ['ACCEPTED', 'ACC']:
                            conds.append("(a.warehouse_receipt_status = 'ACCEPTED' OR a.warehouse_receipt_status = 'ACC')")
                        elif status_p.upper() == 'DECLINED':
                            conds.append("a.warehouse_receipt_status = 'DECLINED'")
                        else:
                            conds.append("a.warehouse_receipt_status = ?")
                            params.append(status_p)
                    if q_str:
                        conds.append("(a.part15_sn LIKE ? OR a.part1_sn LIKE ? OR a.part12_sn LIKE ? OR t.tray_code LIKE ? OR a.qc_doc_number LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like, q_like])

                    where_clause = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT a.id as assembly_id, a.tray_id, a.set_number,
                                       a.part15_sn, a.part12_sn, a.part1_sn,
                                       COALESCE(a.status, 'Good') as assembly_status, 
                                       COALESCE(a.status, 'Good') as barang_status,
                                       a.started_at, a.finished_at, a.pic_assembly,
                                       COALESCE(u_op.full_name, a.pic_assembly) as pic_assembly_name,
                                       COALESCE(a.assy_doc_number, at_assy.doc_number) as assy_doc_number,
                                       COALESCE(at_assy.doc_number, a.assy_doc_number) as tf1_doc_number,
                                       COALESCE(a.assy_test_checklist, at_assy.test_checklist) as assy_test_checklist,
                                       COALESCE(at_assy.test_checklist, a.assy_test_checklist) as tf1_test_checklist,
                                       COALESCE(a.assy_test_status, at_assy.status, 'Good') as assy_test_status,
                                       COALESCE(at_qc.doc_number, a.qc_doc_number) as qc_doc_number,
                                       COALESCE(at_qc.doc_number, a.qc_doc_number) as qc_test_doc_number,
                                       COALESCE(at_qc.status, a.qc_status) as qc_status,
                                       COALESCE(at_qc.status, a.qc_status) as qc_test_status,
                                       COALESCE(at_qc.test_checklist, a.qc_notes) as qc_test_checklist,
                                       COALESCE(at_qc.created_at, a.qc_tested_at) as qc_tested_at,
                                       COALESCE(at_qc.tester_username, a.qc_tested_by) as qc_tested_by,
                                       COALESCE(u_qc.full_name, at_qc.tester_username, a.qc_tested_by) as qc_tester_name,
                                       COALESCE(u_qc.full_name, at_qc.tester_username, a.qc_tested_by) as pic_qc,
                                       a.reason,
                                       COALESCE(a.warehouse_receipt_status, 'PENDING') as warehouse_receipt_status,
                                       a.warehouse_receipt_at,
                                       a.warehouse_receipt_by, 
                                       COALESCE(a.declined_reason, a.reason) as declined_reason,
                                       COALESCE(a.declined_reason, a.reason) as warehouse_decline_reason,
                                       a.warehouse_doc_number,
                                       t.tray_code, t.source_document_mo, t.product_type,
                                       COALESCE(u_wh.full_name, a.warehouse_receipt_by) as warehouse_receipt_by_name
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN users u_wh ON (a.warehouse_receipt_by = u_wh.username OR a.warehouse_receipt_by = u_wh.full_name)
                                LEFT JOIN users u_op ON (a.pic_assembly = u_op.username OR a.pic_assembly = u_op.full_name)
                                LEFT JOIN (
                                    SELECT assembly_id, MAX(id) as max_id 
                                    FROM assembly_tests 
                                    WHERE stage = 'QC' 
                                    GROUP BY assembly_id
                                ) latest_qc ON latest_qc.assembly_id = a.id
                                LEFT JOIN assembly_tests at_qc ON at_qc.id = latest_qc.max_id
                                LEFT JOIN users u_qc ON (at_qc.tester_username = u_qc.username OR at_qc.tester_username = u_qc.full_name OR a.qc_tested_by = u_qc.username OR a.qc_tested_by = u_qc.full_name)
                                LEFT JOIN (
                                    SELECT assembly_id, MAX(id) as max_id 
                                    FROM assembly_tests 
                                    WHERE stage = 'ASSY' 
                                    GROUP BY assembly_id
                                ) latest_assy ON latest_assy.assembly_id = a.id
                                LEFT JOIN assembly_tests at_assy ON at_assy.id = latest_assy.max_id
                                WHERE {where_clause}
                                ORDER BY {order_clause}
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/gudang/tray_verification_detail":
                    raw_code = query.get("code", [""])[0]
                    code = raw_code.strip() if raw_code and raw_code != 'undefined' else ""
                    raw_tray_id = query.get("tray_id", [""])[0]
                    tray_id = int(raw_tray_id) if raw_tray_id and str(raw_tray_id).strip().isdigit() else None
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            if tray_id:
                                cur.execute("""
                                    SELECT t.id, t.tray_code,
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                           t.source_document_mo,
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           t.created_at
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.id = ?
                                """, (tray_id,))
                            elif code:
                                tray = find_tray_by_code(cur, code)
                            else:
                                cur.execute("""
                                    SELECT t.id, t.tray_code,
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                           t.source_document_mo,
                                           COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                           COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                           t.verified_at, t.verified_by_user,
                                           t.created_at
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    ORDER BY t.id DESC LIMIT 1
                                """)
                                tray = cur.fetchone()
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                            
                            t_id = tray['id']
                            cur.execute("""
                                SELECT a.id, a.tray_id, a.set_number, a.part15_sn, a.part12_sn, a.part1_sn, 
                                       COALESCE(a.status, 'Good') as status, 
                                       COALESCE(a.declined_reason, a.reason) as reason,
                                       a.defective_parts, COALESCE(a.verified_status, '') as verified_status, 
                                       COALESCE(a.is_declined, FALSE) as is_declined, 
                                       COALESCE(a.is_reregistered, FALSE) as is_reregistered, 
                                       a.declined_reason, a.started_at, a.finished_at,
                                       COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly
                                FROM assemblies a
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                WHERE a.tray_id = ?
                                ORDER BY a.set_number ASC
                            """, (t_id,))
                            assemblies = [dict(r) for r in cur.fetchall()]

                            if not assemblies:
                                cur.execute("SELECT * FROM parts WHERE tray_id = ? ORDER BY id ASC", (t_id,))
                                parts = [dict(r) for r in cur.fetchall()]
                                p15_list = [p['sn'] for p in parts if p['part_type'] == '15']
                                p1_list = [p['sn'] for p in parts if p['part_type'] == '1']
                                p12_list = [p['sn'] for p in parts if p['part_type'] == '12']
                                count = max(len(p15_list), len(p1_list), len(p12_list), 1)
                                for i in range(count):
                                    assemblies.append({
                                        "id": None,
                                        "tray_id": t_id,
                                        "set_number": i + 1,
                                        "part15_sn": p15_list[i] if i < len(p15_list) else "",
                                        "part1_sn": p1_list[i] if i < len(p1_list) else "",
                                        "part12_sn": p12_list[i] if i < len(p12_list) else "",
                                        "status": "Good",
                                        "reason": "",
                                        "defective_parts": [],
                                        "verified_status": "",
                                        "is_declined": False,
                                        "is_reregistered": False,
                                        "declined_reason": ""
                                    })
                            return send_json(self, {"ok": True, "tray": dict(tray), "assemblies": assemblies})

                elif path == "/api/gudang/history":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT t.id, t.tray_code,
                                       COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                       COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                       t.source_document_mo,
                                       COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       t.created_at,
                                       COALESCE(t.verified_by_gudang, FALSE) as verified_by_gudang,
                                       t.verified_at, t.verified_by_user,
                                       GREATEST(COUNT(DISTINCT p.id), COUNT(DISTINCT a.id) * 3) as total_parts,
                                       GREATEST(COUNT(DISTINCT CASE WHEN p.part_type = '15' THEN p.id END), COUNT(DISTINCT a.id)) as total_sets,
                                       COUNT(DISTINCT a.id) as finished_count,
                                       COUNT(DISTINCT CASE WHEN a.status = 'Hold' THEN a.id END) as hold_count,
                                       COUNT(DISTINCT CASE WHEN a.status = 'Scrap' THEN a.id END) as scrap_count,
                                       COUNT(DISTINCT CASE WHEN a.status = 'Rework' THEN a.id END) as rework_count
                                FROM trays t
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                LEFT JOIN parts p ON t.id = p.tray_id
                                LEFT JOIN assemblies a ON t.id = a.tray_id
                                GROUP BY t.id, t.tray_code, u_wh.full_name, t.pic_warehouse, u_op.full_name, t.assigned_operator, t.source_document_mo, t.product_type, t.created_at, t.verified_by_gudang, t.verified_at, t.verified_by_user
                                ORDER BY t.tray_code ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/gudang/tray_detail":
                    tray_id = query.get("id", [""])[0]
                    tray_code = query.get("code", [""])[0]
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            if tray_id:
                                cur.execute("SELECT * FROM trays WHERE id = ?", (tray_id,))
                            else:
                                cur.execute("SELECT * FROM trays WHERE tray_code = ?", (tray_code,))
                            tray = cur.fetchone()
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                            if tray.get('session_status') == 'COMPLETED_SHIFT':
                                cur.execute('''
                                    SELECT p.* FROM parts p
                                    JOIN assemblies a ON p.tray_id = a.tray_id AND (p.sn = a.part15_sn OR p.sn = a.part1_sn OR p.sn = a.part12_sn)
                                    WHERE p.tray_id = ?
                                    ORDER BY p.id
                                ''', (tray['id'],))
                                parts = [dict(r) for r in cur.fetchall()]
                            else:
                                cur.execute("SELECT * FROM parts WHERE tray_id = ? ORDER BY id", (tray['id'],))
                                parts = [dict(r) for r in cur.fetchall()]

                            cur.execute("SELECT * FROM assemblies WHERE tray_id = ? ORDER BY set_number ASC", (tray['id'],))
                            sets = [dict(r) for r in cur.fetchall()]
                            return send_json(self, {"ok": True, "data": {"tray": dict(tray), "parts": parts, "sets": sets, "assemblies": sets}})

                elif path == "/api/gudang/returned-trays":
                    operator = query.get("operator", [""])[0].strip()
                    q_str = query.get("q", [""])[0].strip()
                    code_str = query.get("code", [""])[0].strip()
                    status_filter = query.get("status", [""])[0].strip()

                    conds = ["t.session_status = 'RETURNED_TO_WAREHOUSE'"]
                    params = []
                    if operator and operator != 'Semua Operator Assembly':
                        conds.append("(t.assigned_operator LIKE ? OR u_op.full_name LIKE ?)")
                        op_like = f"%{operator}%"
                        params.extend([op_like, op_like])
                    if code_str:
                        conds.append("t.tray_code LIKE ?")
                        params.append(f"%{code_str}%")
                    elif q_str:
                        conds.append("(t.tray_code LIKE ? OR t.assigned_operator LIKE ? OR u_op.full_name LIKE ? OR t.return_reason LIKE ?)")
                        q_like = f"%{q_str}%"
                        params.extend([q_like, q_like, q_like, q_like])
                    if status_filter == 'pending':
                        conds.append("(t.return_verified IS FALSE OR t.return_verified IS NULL)")
                    elif status_filter == 'verified':
                        conds.append("t.return_verified IS TRUE")

                    where_sql = " AND ".join(conds)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute(f"""
                                SELECT t.id, t.tray_code,
                                       COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                       COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                       t.source_document_mo,
                                       COALESCE(t.product_type, 'Nanggala P9') as product_type,
                                       t.session_status,
                                       t.return_reason,
                                       COALESCE(t.return_notified, FALSE) as return_notified,
                                       COALESCE(t.return_verified, FALSE) as return_verified,
                                       t.return_verified_at,
                                       t.return_verified_by,
                                       t.created_at,
                                       (SELECT COUNT(*) FROM assemblies a WHERE a.tray_id = t.id) as finished_count,
                                       GREATEST((
                                           SELECT COUNT(*) FROM parts p 
                                           WHERE p.tray_id = t.id 
                                             AND p.part_type = '15' 
                                             AND p.sn NOT IN (SELECT COALESCE(part15_sn, '') FROM assemblies a WHERE a.tray_id = t.id)
                                       ), (
                                           SELECT COUNT(*) / 3 FROM parts p 
                                           WHERE p.tray_id = t.id 
                                             AND p.sn NOT IN (
                                                 SELECT COALESCE(part15_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                                 UNION SELECT COALESCE(part1_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                                 UNION SELECT COALESCE(part12_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                             )
                                       ), 0) as total_sets,
                                       (
                                           SELECT COUNT(*) FROM parts p 
                                           WHERE p.tray_id = t.id 
                                             AND p.part_type = '15' 
                                             AND p.status = 'Tersedia'
                                             AND p.sn NOT IN (SELECT COALESCE(part15_sn, '') FROM assemblies a WHERE a.tray_id = t.id)
                                       ) as verified_sets_count,
                                       (
                                           SELECT COUNT(*) FROM parts p 
                                           WHERE p.tray_id = t.id 
                                             AND p.sn NOT IN (
                                                 SELECT COALESCE(part15_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                                 UNION SELECT COALESCE(part1_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                                 UNION SELECT COALESCE(part12_sn, '') FROM assemblies a WHERE a.tray_id = t.id
                                             )
                                       ) as total_parts
                                FROM trays t
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                WHERE {where_sql}
                                ORDER BY t.tray_code ASC
                            """, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/gudang/returned-tray-detail":
                    raw_tray_id = query.get("id", [""])[0] or query.get("tray_id", [""])[0]
                    tray_code = query.get("code", [""])[0]
                    tray_id = int(raw_tray_id) if raw_tray_id and str(raw_tray_id).strip().isdigit() else None
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            if tray_id:
                                cur.execute("""
                                    SELECT t.*, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse_name,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator_name
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.id = ?
                                """, (tray_id,))
                            else:
                                cur.execute("""
                                    SELECT t.*, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse_name,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator_name
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.tray_code = ?
                                """, (tray_code,))
                            tray = cur.fetchone()
                            if not tray:
                                return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                            
                            t_id = tray['id']
                            cur.execute("SELECT id, set_number, part15_sn, part1_sn, part12_sn, pic_assembly, status FROM assemblies WHERE tray_id = ? ORDER BY set_number ASC", (t_id,))
                            finished_assemblies = [dict(r) for r in cur.fetchall()]
                            assembled_sns = set()
                            for fa in finished_assemblies:
                                for col in ['part15_sn', 'part1_sn', 'part12_sn']:
                                    if fa.get(col) and fa[col] != '-':
                                        assembled_sns.add(fa[col])

                            cur.execute("SELECT part_type, sn, status FROM parts WHERE tray_id = ? ORDER BY id ASC", (t_id,))
                            all_parts = [dict(r) for r in cur.fetchall()]
                            
                            # Filter HANYA part yang BELUM dirakit untuk ditampilkan di verifikasi pengembalian gudang
                            parts = [p for p in all_parts if p['sn'] not in assembled_sns]
                            
                            if not parts and not finished_assemblies:
                                cur.execute("""
                                    SELECT DISTINCT sn, 
                                           CASE WHEN sn LIKE '%110%' OR sn LIKE 'J%' OR sn LIKE 'B%' OR sn LIKE 'Z%' THEN '15'
                                                WHEN LENGTH(sn) >= 5 THEN '12'
                                                ELSE '1' END as part_type,
                                           'Tersedia' as status
                                    FROM part_audit_logs
                                    WHERE reason LIKE ? AND action_type = 'VERIFY_RETURN_SET' AND part_type = 'PART'
                                """, (f"%{tray['tray_code']}%",))
                                parts = [dict(r) for r in cur.fetchall()]
                            
                            # Group into Sets: PN, SN Part 15, Part 1, Part 12
                            p15_items = [p for p in parts if p['part_type'] == '15']
                            p1_items = [p for p in parts if p['part_type'] == '1']
                            p12_items = [p for p in parts if p['part_type'] == '12']
                            num_sets = max(len(p15_items), len(p1_items), len(p12_items), 1)
                            
                            sets = []
                            all_sets_verified = True if len(parts) > 0 else False
                            for i in range(num_sets):
                                p15_obj = p15_items[i] if i < len(p15_items) else None
                                p1_obj = p1_items[i] if i < len(p1_items) else None
                                p12_obj = p12_items[i] if i < len(p12_items) else None
                                
                                p15_sn = p15_obj['sn'] if p15_obj else "-"
                                p1_sn = p1_obj['sn'] if p1_obj else "-"
                                p12_sn = p12_obj['sn'] if p12_obj else "-"
                                
                                is_declined = False
                                declined_parts = []
                                declined_reason = ""
                                if p15_obj and p15_obj.get('status') == 'Declined': 
                                    is_declined = True
                                    declined_parts.append('15')
                                if p1_obj and p1_obj.get('status') == 'Declined': 
                                    is_declined = True
                                    declined_parts.append('1')
                                if p12_obj and p12_obj.get('status') == 'Declined': 
                                    is_declined = True
                                    declined_parts.append('12')
                                
                                if is_declined:
                                    cur.execute("""
                                        SELECT reason FROM re_register_requests 
                                        WHERE sn IN (?, ?, ?) AND status = 'PENDING'
                                        ORDER BY id DESC LIMIT 1
                                    """, (p15_sn, p1_sn, p12_sn))
                                    rr = cur.fetchone()
                                    if rr:
                                        declined_reason = rr['reason']

                                set_verified = True
                                if p15_obj and p15_obj.get('status') != 'Tersedia': set_verified = False
                                if p1_obj and p1_obj.get('status') != 'Tersedia': set_verified = False
                                if p12_obj and p12_obj.get('status') != 'Tersedia': set_verified = False
                                
                                if bool(tray.get('return_verified')) and not is_declined:
                                    set_verified = True
                                
                                if not set_verified and not is_declined:
                                    all_sets_verified = False

                                set_status = "Tersedia" if set_verified else ("Declined" if is_declined else "Dikembalikan")

                                sets.append({
                                    "set_number": i + 1,
                                    "pn": "P9 Part 15" if tray.get('product_type') == 'Nanggala P9' else (tray.get('product_type') or 'P9 Part 15'),
                                    "p15_sn": p15_sn,
                                    "p1_sn": p1_sn,
                                    "p12_sn": p12_sn,
                                    "p15_status": p15_obj.get('status') if p15_obj else None,
                                    "p1_status": p1_obj.get('status') if p1_obj else None,
                                    "p12_status": p12_obj.get('status') if p12_obj else None,
                                    "is_verified": set_verified,
                                    "is_declined": is_declined,
                                    "declined_parts": declined_parts,
                                    "declined_reason": declined_reason,
                                    "status": set_status
                                })

                            tray_dict = dict(tray)
                            if all_sets_verified and not tray_dict.get('return_verified'):
                                tray_dict['return_verified'] = True
                                cur.execute("UPDATE trays SET return_verified = TRUE, return_verified_at = datetime('now', 'localtime'), return_verified_by = COALESCE(return_verified_by, ?) WHERE id = ?", (user.get('full_name') or user['username'], t_id))
                                conn.commit()

                            return send_json(self, {
                                "ok": True, 
                                "tray": tray_dict, 
                                "sets": sets, 
                                "total_parts": len(parts),
                                "finished_count": len(finished_assemblies),
                                "finished_assemblies": finished_assemblies
                            })

                elif path == "/api/master/available":
                    raw_type = str(query.get("type", [""])[0] or "").strip()
                    if raw_type in ['1', 'Part1', 'P1', 'part1']:
                        p_type = '1'
                    elif raw_type in ['12', 'Part12', 'P12', 'part12']:
                        p_type = '12'
                    elif raw_type in ['15', 'Part15', 'P15', 'P9', 'p9', 'part15']:
                        p_type = '15'
                    else:
                        p_type = raw_type

                    q = query.get("q", [""])[0].strip()
                    limit_param = query.get("limit", [""])[0].strip()
                    limit = int(limit_param) if limit_param and limit_param.isdigit() and int(limit_param) > 0 else None
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            sql = """
                                SELECT sn, cs, part_type, status 
                                FROM master_stocks 
                                WHERE part_type = ? AND status = 'Tersedia' 
                                  AND allocated_tray_id IS NULL
                                  AND sn NOT IN (
                                      SELECT p.sn 
                                      FROM parts p 
                                      JOIN trays t ON p.tray_id = t.id 
                                      WHERE p.part_type = ? 
                                        AND UPPER(COALESCE(p.status, '')) NOT IN ('RETURNED', 'TERSEDIA', 'DECLINED')
                                        AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR COALESCE(t.return_verified, 0) = 0)
                                  )
                            """
                            params = [p_type, p_type]
                            if q:
                                sql += " AND (sn LIKE ? OR REPLACE(sn, '.', '') LIKE ?)"
                                params.extend([f"%{q}%", f"%{q}%"])

                            sql += " ORDER BY sn ASC"
                            if limit is not None:
                                sql += " LIMIT ?"
                                params.append(limit)

                            cur.execute(sql, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/master/stocks_summary":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT m.part_type,
                                       count(*) as total,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status, 'Tersedia') = 'Tersedia' then 1 end) as available,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) = 'Supplied' then 1 end) as allocated,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) = 'Done' then 1 end) as used,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) IN ('Good', 'Done') then 1 end) as good_count,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) = 'Hold' then 1 end) as hold_count,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) = 'Scrap' then 1 end) as scrap_count,
                                       count(case when (a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin')) then NULL when COALESCE(a.status, m.status) = 'Rework' then 1 end) as rework_count,
                                       count(case when a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin') then 1 end) as decline_count,
                                       count(case when COALESCE(m.is_replaced_from_assy, 0) = 1 then 1 end) as replaced_count
                                FROM master_stocks m
                                LEFT JOIN assemblies a ON m.allocated_tray_id = a.tray_id AND (
                                    m.sn = a.part15_sn OR 
                                    m.sn = a.part1_sn OR '1' || m.sn = a.part1_sn OR '2' || m.sn = a.part1_sn OR
                                    m.sn = a.part12_sn OR '1' || m.sn = a.part12_sn OR '2' || m.sn = a.part12_sn
                                )
                                GROUP BY m.part_type
                                ORDER BY m.part_type
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/master/stocks_list":
                    p_type = query.get("type", [""])[0]
                    p_status = query.get("status", [""])[0]
                    p_replaced = query.get("replaced", [""])[0].strip()
                    q = query.get("q", [""])[0].strip()
                    page = max(1, int(query.get("page", ["1"])[0]))
                    limit_param = query.get("limit", ["100"])[0]
                    limit = max(1, min(5000, int(limit_param))) if limit_param.isdigit() else 100
                    offset = (page - 1) * limit

                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            conds = ["1=1"]
                            params = []
                            if p_type:
                                conds.append("m.part_type = ?")
                                params.append(p_type)
                            if p_replaced:
                                if p_replaced in ['1', 'true', 'yes', 'replaced']:
                                    conds.append("COALESCE(m.is_replaced_from_assy, 0) = 1")
                                elif p_replaced in ['0', 'false', 'no']:
                                    conds.append("COALESCE(m.is_replaced_from_assy, 0) = 0")
                            if p_status:
                                if p_status.lower() in ['part diganti', 'replaced']:
                                    conds.append("COALESCE(m.is_replaced_from_assy, 0) = 1")
                                elif p_status.lower() in ['decline', 'declined', 'pending verif admin']:
                                    conds.append("(COALESCE(a.is_declined, 0) = 1 OR COALESCE(a.verified_status, '') = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin'))")
                                else:
                                    conds.append("((COALESCE(a.is_declined, 0) != 1 AND COALESCE(a.verified_status, '') != 'DECLINED' AND m.status NOT IN ('Declined', 'Decline', 'Pending Verif Admin')) AND COALESCE(a.status, m.status, 'Tersedia') = ?)")
                                    params.append(p_status)
                            if q:
                                q_alt = q[1:] if len(q) in [5, 6, 7] and q[0] in ['1', '2'] else q
                                conds.append("(m.sn LIKE ? OR REPLACE(m.sn, '.', '') LIKE ? OR m.sn LIKE ? OR t.tray_code LIKE ? OR t.source_document_mo LIKE ? OR a.pic_assembly LIKE ? OR u_assy.full_name LIKE ? OR t.assigned_operator LIKE ? OR u_op.full_name LIKE ? OR m.last_reason LIKE ? OR m.last_operator LIKE ? OR a.part15_sn LIKE ? OR a.part1_sn LIKE ? OR a.part12_sn LIKE ?)")
                                params.extend([f"%{q}%", f"%{q}%", f"%{q_alt}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%"])

                            where_clause = " AND ".join(conds)
                            cur.execute(f"""
                                SELECT COUNT(*) as total 
                                FROM master_stocks m 
                                LEFT JOIN trays t ON COALESCE(m.allocated_tray_id, m.last_tray_id) = t.id
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                LEFT JOIN assemblies a ON m.allocated_tray_id = a.tray_id AND (
                                    m.sn = a.part15_sn OR 
                                    m.sn = a.part1_sn OR '1' || m.sn = a.part1_sn OR '2' || m.sn = a.part1_sn OR
                                    m.sn = a.part12_sn OR '1' || m.sn = a.part12_sn OR '2' || m.sn = a.part12_sn
                                )
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                WHERE {where_clause}
                            """, tuple(params))
                            total_cnt = cur.fetchone()['total']

                            cur.execute(f"""
                                SELECT m.id, m.part_type, m.sn, m.cs, m.status as stock_status, m.allocated_tray_id, m.allocated_at, m.used_at,
                                       m.last_reason, COALESCE(m.is_replaced_from_assy, 0) as is_replaced_from_assy,
                                       t.tray_code, 
                                       COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse,
                                       COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator,
                                       t.source_document_mo, t.product_type,
                                       a.id as assembly_id, a.set_number, a.status as assembly_status,
                                       COALESCE(
                                           m.last_reason,
                                           CASE 
                                               WHEN a.is_declined = TRUE OR a.verified_status = 'DECLINED' THEN COALESCE(a.declined_reason, a.reason, 'Barang di-Decline saat verifikasi fisik gudang')
                                               ELSE a.reason 
                                           END
                                       ) as assembly_reason,
                                       COALESCE(m.last_operator, u_assy.full_name, a.pic_assembly, u_op.full_name, t.assigned_operator) as pic_assembly,
                                       a.started_at as assembly_started_at, a.finished_at as assembly_finished_at,
                                       CASE 
                                           WHEN a.is_declined = TRUE OR a.verified_status = 'DECLINED' OR m.status IN ('Declined', 'Decline', 'Pending Verif Admin') THEN 'Decline'
                                           ELSE COALESCE(a.status, m.status, 'Tersedia') 
                                       END as effective_status
                                FROM master_stocks m
                                LEFT JOIN trays t ON COALESCE(m.allocated_tray_id, m.last_tray_id) = t.id
                                LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                LEFT JOIN assemblies a ON m.allocated_tray_id = a.tray_id AND (
                                    m.sn = a.part15_sn OR 
                                    m.sn = a.part1_sn OR '1' || m.sn = a.part1_sn OR '2' || m.sn = a.part1_sn OR
                                    m.sn = a.part12_sn OR '1' || m.sn = a.part12_sn OR '2' || m.sn = a.part12_sn
                                )
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                WHERE {where_clause}
                                ORDER BY m.id ASC
                                LIMIT ? OFFSET ?
                            """, tuple(params + [limit, offset]))
                            rows = [dict(r) for r in cur.fetchall()]
                            cur.execute("SELECT COUNT(*) as cnt FROM master_stocks WHERE COALESCE(is_replaced_from_assy, 0) = 1")
                            rep_cnt = cur.fetchone()['cnt']
                    return send_json(self, {"ok": True, "data": rows, "total": total_cnt, "page": page, "limit": limit, "replaced_count": rep_cnt})

                elif path == "/api/master/parts":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT id, part_type, sn, cs, status
                                FROM master_stocks
                                WHERE status NOT IN ('Tersedia', 'Good')
                                ORDER BY id ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})
                elif path == "/api/master/get-connected-set":
                    sn = query.get("sn", [""])[0].strip()
                    p_type = query.get("part_type", [""])[0].strip() or query.get("type", [""])[0].strip()
                    if not sn:
                        return send_json(self, {"ok": False, "error": "sn diperlukan"}, 400)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT a.id as assembly_id, a.tray_id, a.set_number, 
                                       a.part15_sn, a.part1_sn, a.part12_sn,
                                       a.status as assembly_status, a.qc_status, a.verified_status, a.is_declined, a.reason,
                                       t.tray_code, t.source_document_mo,
                                       ms15.status as p15_status, ms1.status as p1_status, ms12.status as p12_status
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                LEFT JOIN master_stocks ms15 ON (ms15.part_type = '15' AND (ms15.sn = a.part15_sn OR REPLACE(ms15.sn, '.', '') = REPLACE(a.part15_sn, '.', '')))
                                LEFT JOIN master_stocks ms1 ON (ms1.part_type = '1' AND (ms1.sn = a.part1_sn OR REPLACE(ms1.sn, '.', '') = REPLACE(a.part1_sn, '.', '')))
                                LEFT JOIN master_stocks ms12 ON (ms12.part_type = '12' AND (ms12.sn = a.part12_sn OR REPLACE(ms12.sn, '.', '') = REPLACE(a.part12_sn, '.', '')))
                                WHERE (a.part15_sn = ? OR REPLACE(a.part15_sn, '.', '') = REPLACE(?, '.', ''))
                                   OR (a.part1_sn = ? OR REPLACE(a.part1_sn, '.', '') = REPLACE(?, '.', ''))
                                   OR (a.part12_sn = ? OR REPLACE(a.part12_sn, '.', '') = REPLACE(?, '.', ''))
                                ORDER BY a.id DESC LIMIT 1
                            """, (sn, sn, sn, sn, sn, sn))
                            row = cur.fetchone()
                            # Hanya hubungkan sebagai set barang produksi jika unit tersebut terkena status hold/rework dari proses TESTING (QC atau Tester Assembly)
                            if row and (row.get('qc_status') in ['HOLD_QC', 'HOLD_ASSY', 'REWORK_QC'] or row.get('verified_status') in ['HOLD_GUDANG', 'HOLD_QC_GUDANG']):
                                return send_json(self, {"ok": True, "is_connected": True, "set_info": dict(row)})
                            else:
                                return send_json(self, {"ok": True, "is_connected": False})

                elif path == "/api/master/part-detail":
                    part_id = query.get("id", [""])[0].strip()
                    sn = query.get("sn", [""])[0].strip()
                    p_type = query.get("part_type", [""])[0].strip() or query.get("type", [""])[0].strip()

                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            stock = None
                            if part_id and part_id.isdigit():
                                cur.execute("SELECT * FROM master_stocks WHERE id = ?", (int(part_id),))
                                stock = cur.fetchone()
                            elif sn:
                                if p_type:
                                    cur.execute("SELECT * FROM master_stocks WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) LIMIT 1", (p_type, sn, sn))
                                else:
                                    cur.execute("SELECT * FROM master_stocks WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) LIMIT 1", (sn, sn))
                                stock = cur.fetchone()

                            if not stock:
                                return send_json(self, {"ok": False, "success": False, "error": "Part tidak ditemukan dalam Master Stok"}, 404)

                            stock_data = dict(stock)

                            # 1. Tray info
                            tray_id = stock_data.get('allocated_tray_id') or stock_data.get('last_tray_id')
                            tray_info = None
                            if tray_id:
                                cur.execute("""
                                    SELECT t.*, 
                                           COALESCE(u_wh.full_name, t.pic_warehouse) as pic_warehouse_name,
                                           COALESCE(u_op.full_name, t.assigned_operator) as assigned_operator_name
                                    FROM trays t
                                    LEFT JOIN users u_wh ON (t.pic_warehouse = u_wh.username OR t.pic_warehouse = u_wh.full_name)
                                    LEFT JOIN users u_op ON (t.assigned_operator = u_op.username OR t.assigned_operator = u_op.full_name)
                                    WHERE t.id = ?
                                """, (tray_id,))
                                t_row = cur.fetchone()
                                if t_row:
                                    tray_info = dict(t_row)

                            # 2. Assembly info
                            cur.execute("""
                                SELECT a.*,
                                       COALESCE(u_assy.full_name, a.pic_assembly) as pic_assembly_name
                                FROM assemblies a
                                LEFT JOIN users u_assy ON (a.pic_assembly = u_assy.username OR a.pic_assembly = u_assy.full_name)
                                WHERE (a.tray_id = ? AND (a.part15_sn = ? OR a.part1_sn = ? OR a.part12_sn = ?))
                                   OR (a.part15_sn = ? OR a.part1_sn = ? OR a.part12_sn = ?)
                                ORDER BY a.id DESC LIMIT 1
                            """, (tray_id or 0, stock_data['sn'], stock_data['sn'], stock_data['sn'], stock_data['sn'], stock_data['sn'], stock_data['sn']))
                            assy_row = cur.fetchone()
                            assembly_info = dict(assy_row) if assy_row else None

                            # 3. QC & Assembly Tests
                            assembly_tests = []
                            if assembly_info:
                                cur.execute("""
                                    SELECT at.id, at.doc_number, at.status, at.failure_reasons, at.notes, at.tester_username, 
                                           COALESCE(u.full_name, at.tester_username) as tester_full_name, at.created_at, at.lifecycle_path
                                    FROM assembly_tests at
                                    LEFT JOIN users u ON at.tester_username = u.username
                                    WHERE at.assembly_id = ?
                                    ORDER BY at.id DESC LIMIT 10
                                """, (assembly_info['id'],))
                                assembly_tests = [dict(r) for r in cur.fetchall()]

                            # 4. Audit Logs (part_audit_logs)
                            cur.execute("""
                                SELECT id, part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at
                                FROM part_audit_logs
                                WHERE (part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')))
                                   OR (part_type = 'ALL' AND sn LIKE ?)
                                ORDER BY id DESC LIMIT 50
                            """, (stock_data['part_type'], stock_data['sn'], stock_data['sn'], f"%Set#{assembly_info['set_number']}%" if assembly_info and assembly_info.get('set_number') else '%NOMATCH%'))
                            audit_logs = [dict(r) for r in cur.fetchall()]

                            # 5. Re-register requests
                            cur.execute("""
                                SELECT id, part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, admin_username, admin_notes, approved_at, created_at
                                FROM re_register_requests
                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                ORDER BY id DESC LIMIT 20
                            """, (stock_data['part_type'], stock_data['sn'], stock_data['sn']))
                            rereg_requests = [dict(r) for r in cur.fetchall()]

                            return send_json(self, {
                                "ok": True,
                                "success": True,
                                "data": {
                                    "stock": stock_data,
                                    "tray": tray_info,
                                    "assembly": assembly_info,
                                    "assembly_tests": assembly_tests,
                                    "audit_logs": audit_logs,
                                    "re_register_requests": rereg_requests
                                }
                            })

                elif path == "/api/customers":
                    q = query.get("q", [""])[0].strip()
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            if q:
                                cur.execute("""
                                    SELECT id, name, address, phone, fax, created_at
                                    FROM customers
                                    WHERE name LIKE ? OR phone LIKE ? OR address LIKE ?
                                    ORDER BY name ASC
                                """, (f"%{q}%", f"%{q}%", f"%{q}%"))
                            else:
                                cur.execute("""
                                    SELECT id, name, address, phone, fax, created_at
                                    FROM customers
                                    ORDER BY name ASC
                                """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/delivery-orders/settings":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                CREATE TABLE IF NOT EXISTS do_settings (
                                    id INTEGER PRIMARY KEY DEFAULT 1,
                                    company_name TEXT DEFAULT 'PT Nanggala Kencana Rekatama Indonesia',
                                    company_logo TEXT DEFAULT 'nkri-logo.png',
                                    head_office TEXT DEFAULT 'Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130',
                                    factory_address TEXT DEFAULT 'Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292',
                                    contact_email TEXT DEFAULT 'info@nanggala.co.id',
                                    default_customer_name TEXT DEFAULT 'KEMHAN RI',
                                    default_customer_address TEXT DEFAULT 'BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110',
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
                                    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
                                );
                            """)
                            # Migrasi: tambah kolom offset penomoran DO (per bulan)
                            try:
                                cur.execute("ALTER TABLE do_settings ADD COLUMN do_sequence_offset INTEGER DEFAULT 0")
                            except Exception:
                                pass
                            try:
                                cur.execute("ALTER TABLE do_settings ADD COLUMN do_offset_month TEXT DEFAULT ''")
                            except Exception:
                                pass
                            # Migrasi: tabel standalone packing labels
                            cur.execute("""
                                CREATE TABLE IF NOT EXISTS standalone_packing_labels (
                                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                                    label_batch_id TEXT NOT NULL,
                                    package_type TEXT NOT NULL DEFAULT 'crate',
                                    batch_number INTEGER NOT NULL DEFAULT 1,
                                    product_name TEXT NOT NULL DEFAULT 'Nanggala P9',
                                    total_quantity INTEGER NOT NULL DEFAULT 0,
                                    total_containers INTEGER NOT NULL DEFAULT 0,
                                    crates_json TEXT NOT NULL DEFAULT '[]',
                                    notes TEXT,
                                    created_by TEXT,
                                    created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                                );
                            """)
                            cur.execute("SELECT * FROM do_settings WHERE id = 1")
                            row = cur.fetchone()
                            if not row:
                                cur.execute("INSERT INTO do_settings (id) VALUES (1)")
                                cur.execute("SELECT * FROM do_settings WHERE id = 1")
                                row = cur.fetchone()
                            data = dict(row) if row else {}
                            detected_ip = get_local_ip()
                            data["detected_ip"] = detected_ip
                            data["auto_base_url"] = f"http://{detected_ip}:{PORT}"
                            cfg = get_cloudinary_config()
                            data["cloudinary"] = cloudinary_public_info(cfg)
                    return send_json(self, {"ok": True, "settings": data, "data": data})

                elif path == "/api/cloudinary/settings":
                    cfg = get_cloudinary_config()
                    return send_json(self, {"ok": True, "data": cloudinary_public_info(cfg)})

                elif path == "/api/delivery-orders/next-meta":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT * FROM do_settings WHERE id = 1")
                            s_row = cur.fetchone()
                            st = dict(s_row) if s_row else {}

                            req_date_str = query.get("date", [""])[0].strip() or query.get("do_date", [""])[0].strip()
                            target_dt = parse_target_date(req_date_str) if req_date_str else datetime.now()

                            next_do = get_next_do_number(cur, target_date=target_dt)
                            today_indo = format_indo_date(target_dt)
                            now = target_dt
                            mm_yyyy = now.strftime("%m/%Y")
                            
                            batch_num = get_next_batch_number(cur)
                            next_crate = get_next_crate_id(cur, batch_number=batch_num, target_date=target_dt)
                            next_pl = get_next_pl_number(cur, target_date=target_dt)
                            next_box = get_next_box_id(cur, batch_number=batch_num, box_index=1, target_date=target_dt)

                            c_prefix = f"{now.strftime('%y%m')}-{batch_num}"
                            c_seq = int(next_crate[len(c_prefix):]) if next_crate[len(c_prefix):].isdigit() else 1
                            b_seq = int(next_box.split('-')[-1]) if next_box.split('-')[-1].isdigit() else 1
                            cfg = get_cloudinary_config()
                    return send_json(self, {
                        "ok": True,
                        "do_number": next_do,
                        "do_date": today_indo,
                        "do_date_iso": now.strftime("%Y-%m-%d"),
                        "pl_number": next_pl,
                        "batch_number": batch_num,
                        "crate_id": next_crate,
                        "crate_sequence": c_seq,
                        "box_id": next_box,
                        "box_sequence": b_seq,
                        "customer_name": st.get("default_customer_name", "KEMHAN RI"),
                        "customer_address": st.get("default_customer_address", "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110"),
                        "customer_phone": st.get("default_customer_phone", "(021) 3840889"),
                        "customer_fax": st.get("default_customer_fax", "(021) 3840889"),
                        "product_name": st.get("default_product_name", "Nanggala P9"),
                        "contract_number": st.get("default_contract_number", "TRAK/2509/APBN/XI/2025/KEMHAN"),
                        "program_name": st.get("default_program_name", "Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025"),
                        "created_by": st.get("default_issuer_name", "Rezka Rachdana"),
                        "created_by_name": st.get("default_issuer_name", "Rezka Rachdana"),
                        "created_by_title": st.get("default_issuer_title", "Staf"),
                        "created_by_department": st.get("default_issuer_department", "PPIC"),
                        "company_name": st.get("company_name", "PT Nanggala Kencana Rekatama Indonesia"),
                        "head_office": st.get("head_office", "Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130"),
                        "factory_address": st.get("factory_address", "Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292"),
                        "contact_email": st.get("contact_email", "info@nanggala.co.id"),
                        "cloudinary": cloudinary_public_info(cfg),
                        "cloud_qr_url": build_pl_cloud_url(next_do, cfg)
                    })

                elif path == "/api/delivery-orders/next-number":
                    req_date_str = query.get("date", [""])[0].strip() or query.get("do_date", [""])[0].strip()
                    target_dt = parse_target_date(req_date_str) if req_date_str else datetime.now()
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            next_do = get_next_do_number(cur, target_date=target_dt)
                    return send_json(self, {"ok": True, "next_do_number": next_do, "do_number": next_do})

                elif path == "/api/delivery-orders/next-batch":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            batch_num = get_next_batch_number(cur)
                    return send_json(self, {"ok": True, "next_batch": batch_num, "batch_number": batch_num})

                elif path == "/api/delivery-orders/available-sns":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT a.id as assembly_id, a.part15_sn, a.tray_id, a.set_number, t.tray_code
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                WHERE (a.warehouse_receipt_status IN ('ACCEPTED', 'ACC'))
                                  AND (a.qc_status IN ('GOOD', 'GOOD_QC'))
                                  AND (a.is_declined IS NULL OR a.is_declined = 0)
                                  AND a.part15_sn IS NOT NULL AND a.part15_sn != '' AND a.part15_sn != '-'
                                  AND a.part15_sn NOT IN (SELECT serial_number FROM packing_list_items WHERE serial_number IS NOT NULL AND serial_number != '')
                                ORDER BY a.id ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                            sns = [r['part15_sn'] for r in rows if r.get('part15_sn')]
                    return send_json(self, {"ok": True, "data": rows, "serial_numbers": sns})

                elif path == "/api/delivery-orders":
                    q = query.get("q", [""])[0].strip()
                    cfg = get_cloudinary_config()
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cols = """
                                id, do_number, do_date, contract_number, program_name,
                                customer_name, customer_address, customer_phone, customer_fax,
                                product_name, total_quantity, total_crates, batch_number,
                                created_by, created_by as created_by_name, created_by_title, created_by_department, package_type, status, notes, created_at,
                                cloud_public_id, cloud_pdf_url, cloud_version, cloud_uploaded_at
                            """
                            if q:
                                cur.execute(f"""
                                    SELECT {cols}
                                    FROM delivery_orders
                                    WHERE do_number LIKE ? OR customer_name LIKE ? OR contract_number LIKE ? OR program_name LIKE ?
                                    ORDER BY id DESC
                                """, (f"%{q}%", f"%{q}%", f"%{q}%", f"%{q}%"))
                            else:
                                cur.execute(f"""
                                    SELECT {cols}
                                    FROM delivery_orders
                                    ORDER BY id DESC
                                """)
                            rows = [dict(r) for r in cur.fetchall()]
                            for r in rows:
                                attach_cloud_fields(r, cfg)
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/delivery-orders/export-data":
                    do_id = query.get("do_id", [""])[0].strip()
                    doc_number = query.get("doc_number", [""])[0].strip()
                    start_date = query.get("start_date", [""])[0].strip()
                    end_date = query.get("end_date", [""])[0].strip()

                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            sql = """
                                SELECT 
                                    d.id as do_id,
                                    d.do_number,
                                    d.do_date,
                                    d.created_at,
                                    COALESCE(d.created_by, 'Administrator') as issued_by,
                                    COALESCE(d.status, 'ISSUED') as delivery_status,
                                    COALESCE(d.customer_name, '-') as customer_name,
                                    COALESCE(d.customer_address, '-') as customer_address,
                                    pli.serial_number,
                                    COALESCE(a.part12_sn, '-') as part12_sn,
                                    COALESCE(a.part1_sn, '-') as part1_sn
                                FROM delivery_orders d
                                JOIN packing_list_items pli ON d.id = pli.delivery_order_id
                                LEFT JOIN assemblies a ON pli.serial_number = a.part15_sn
                                WHERE 1=1
                            """
                            params = []
                            if do_id:
                                sql += " AND d.id = ?"
                                params.append(do_id)
                            if doc_number:
                                sql += " AND d.do_number LIKE ?"
                                params.append(f"%{doc_number}%")
                            if start_date:
                                sql += " AND (d.do_date >= ? OR d.created_at >= ?)"
                                params.append(start_date)
                                params.append(start_date)
                            if end_date:
                                sql += " AND (d.do_date <= ? OR d.created_at <= ?)"
                                params.append(end_date + " 23:59:59")
                                params.append(end_date + " 23:59:59")

                            sql += " ORDER BY d.id DESC, pli.serial_number ASC"
                            cur.execute(sql, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/delivery-orders/detail":
                    do_id = query.get("id", [""])[0].strip()
                    if not do_id:
                        return send_json(self, {"ok": False, "error": "ID Delivery Order wajib diberikan"}, 400)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT * FROM delivery_orders WHERE id = ?", (do_id,))
                            do_row = cur.fetchone()
                            if not do_row:
                                return send_json(self, {"ok": False, "error": "Delivery Order tidak ditemukan"}, 404)
                            do_data = dict(do_row)
                            if not do_data.get('created_by_name'):
                                do_data['created_by_name'] = do_data.get('created_by')

                            cur.execute("""
                                SELECT * FROM packing_lists 
                                WHERE delivery_order_id = ? 
                                ORDER BY crate_number ASC, id ASC
                            """, (do_id,))
                            crates = [dict(c) for c in cur.fetchall()]

                            for crate in crates:
                                cur.execute("""
                                    SELECT * FROM packing_list_boxes 
                                    WHERE packing_list_id = ? 
                                    ORDER BY box_number ASC
                                """, (crate['id'],))
                                boxes = [dict(b) for b in cur.fetchall()]
                                for box in boxes:
                                    cur.execute("""
                                        SELECT slot_number, serial_number 
                                        FROM packing_list_items 
                                        WHERE box_id = ? 
                                        ORDER BY slot_number ASC
                                    """, (box['id'],))
                                    box['items'] = [dict(it) for it in cur.fetchall()]
                                crate['boxes'] = boxes
                            do_data['crates'] = crates

                            cfg = get_cloudinary_config()
                            attach_cloud_fields(do_data, cfg)
                            cur.execute("SELECT * FROM do_settings WHERE id = 1")
                            s_row = cur.fetchone()
                            st = dict(s_row) if s_row else {}
                            st["cloudinary"] = cloudinary_public_info(cfg)
                            do_data['settings'] = st
                    return send_json(self, {"ok": True, "data": do_data})

                elif path == "/api/delivery-orders/pdf":
                    do_id = query.get("id", [""])[0].strip() or query.get("do_id", [""])[0].strip()
                    if not do_id:
                        return send_json(self, {"ok": False, "error": "ID Delivery Order wajib diberikan"}, 400)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT * FROM delivery_orders WHERE id = ?", (do_id,))
                            do_row = cur.fetchone()
                            if not do_row:
                                return send_json(self, {"ok": False, "error": "Delivery Order tidak ditemukan"}, 404)
                            do_data = dict(do_row)
                            cur.execute("""
                                SELECT * FROM packing_lists 
                                WHERE delivery_order_id = ? 
                                ORDER BY crate_number ASC, id ASC
                            """, (do_id,))
                            crates = [dict(c) for c in cur.fetchall()]
                            for crate in crates:
                                cur.execute("""
                                    SELECT * FROM packing_list_boxes 
                                    WHERE packing_list_id = ? 
                                    ORDER BY box_number ASC
                                """, (crate['id'],))
                                boxes = [dict(b) for b in cur.fetchall()]
                                for box in boxes:
                                    cur.execute("""
                                        SELECT slot_number, serial_number 
                                        FROM packing_list_items 
                                        WHERE box_id = ? 
                                        ORDER BY slot_number ASC
                                    """, (box['id'],))
                                    box['items'] = [dict(it) for it in cur.fetchall()]
                                crate['boxes'] = boxes
                            do_data['crates'] = crates

                            cur.execute("SELECT * FROM do_settings WHERE id = 1")
                            s_row = cur.fetchone()
                            st = dict(s_row) if s_row else {}

                    if not pl_generator:
                        return send_json(self, {"ok": False, "error": "Modul 'reportlab' belum terinstall di server. Silakan jalankan 'pip install reportlab' di terminal."}, 500)
                    try:
                        pdf_bytes = pl_generator.generate_packing_list_pdf(do_data, st)
                        safe_name = (do_data.get('do_number') or 'DO').replace('/', '_').replace('\\', '_')
                        self.send_response(200)
                        self.send_header("Content-Type", "application/pdf")
                        self.send_header("Content-Length", str(len(pdf_bytes)))
                        self.send_header("Content-Disposition", f'inline; filename="PACKING_LIST_{safe_name}.pdf"')
                        self.end_headers()
                        self.wfile.write(pdf_bytes)
                        return
                    except Exception as ex_pdf:
                        return send_json(self, {"ok": False, "error": f"Gagal membuat PDF: {ex_pdf}"}, 500)

                elif path == "/api/delivery-orders/export-data":
                    do_id = query.get("id", [""])[0].strip() or query.get("do_id", [""])[0].strip()
                    do_num = query.get("do_number", [""])[0].strip()
                    start_date = query.get("start_date", [""])[0].strip()
                    end_date = query.get("end_date", [""])[0].strip()
                    q = query.get("q", [""])[0].strip()

                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            sql = """
                                SELECT 
                                    do.id as do_id,
                                    do.do_number,
                                    do.do_date,
                                    COALESCE(do.created_by, '') as issued_by,
                                    do.status as delivery_status,
                                    do.customer_name,
                                    do.customer_address,
                                    pli.serial_number,
                                    COALESCE(a.part12_sn, '') as part12_sn,
                                    COALESCE(a.part1_sn, '') as part1_sn
                                FROM packing_list_items pli
                                JOIN delivery_orders do ON pli.delivery_order_id = do.id
                                LEFT JOIN assemblies a ON a.id = (
                                    SELECT a2.id FROM assemblies a2 
                                    WHERE a2.part15_sn = pli.serial_number 
                                    ORDER BY a2.id DESC LIMIT 1
                                )
                                WHERE 1=1
                            """
                            params = []
                            if do_id:
                                sql += " AND do.id = ?"
                                params.append(do_id)
                            if do_num:
                                sql += " AND do.do_number LIKE ?"
                                params.append(f"%{do_num}%")
                            if start_date:
                                sql += " AND do.do_date >= ?"
                                params.append(start_date)
                            if end_date:
                                sql += " AND do.do_date <= ?"
                                params.append(end_date)
                            if q:
                                sql += " AND (do.do_number LIKE ? OR do.customer_name LIKE ? OR pli.serial_number LIKE ?)"
                                params.extend([f"%{q}%", f"%{q}%", f"%{q}%"])

                            sql += " ORDER BY do.id DESC, pli.id ASC"
                            cur.execute(sql, tuple(params))
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/available-serial-numbers":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT a.id as assembly_id, a.part15_sn, a.tray_id, a.set_number, t.tray_code
                                FROM assemblies a
                                JOIN trays t ON a.tray_id = t.id
                                WHERE (a.warehouse_receipt_status IN ('ACCEPTED', 'ACC'))
                                  AND (a.qc_status IN ('GOOD', 'GOOD_QC'))
                                  AND (a.is_declined IS NULL OR a.is_declined = 0)
                                  AND a.part15_sn IS NOT NULL AND a.part15_sn != '' AND a.part15_sn != '-'
                                  AND a.part15_sn NOT IN (SELECT serial_number FROM packing_list_items WHERE serial_number IS NOT NULL AND serial_number != '')
                                ORDER BY a.id ASC
                            """)
                            rows = [dict(r) for r in cur.fetchall()]
                    return send_json(self, {"ok": True, "data": rows})

                elif path == "/api/packing-labels":
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("""
                                SELECT id, label_batch_id, pl_number, package_type, batch_number,
                                       product_name, total_quantity, total_containers, crates_json, notes, created_by, created_at
                                FROM standalone_packing_labels
                                ORDER BY id DESC
                            """)
                            rows = []
                            for r in cur.fetchall():
                                d = dict(r)
                                try:
                                    d['crates'] = json.loads(d.get('crates_json') or '[]')
                                except Exception:
                                    d['crates'] = []
                                rows.append(d)
                            next_b = get_next_batch_number(cur)
                    return send_json(self, {"ok": True, "data": rows, "next_batch": next_b})

                elif path == "/api/packing-labels/detail":
                    lbl_id = query.get("id", [""])[0].strip()
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("SELECT * FROM standalone_packing_labels WHERE id = ?", (lbl_id,))
                            row = cur.fetchone()
                            if not row:
                                return send_json(self, {"ok": False, "error": "Data label tidak ditemukan"}, 404)
                            data = dict(row)
                            try:
                                data['crates'] = json.loads(data.get('crates_json') or '[]')
                            except Exception:
                                data['crates'] = []
                    return send_json(self, {"ok": True, "data": data})

                else:
                    return send_json(self, {"ok": False, "error": "Endpoint API tidak ditemukan"}, 404)
            else:
                if path == "/" or path == "":
                    self.send_response(302)
                    self.send_header("Location", "/login.html")
                    self.send_header("Access-Control-Allow-Origin", "*")
                    self.send_header("Content-Length", "0")
                    self.end_headers()
                    self.wfile.flush()
                    return
                elif path in ["/tracking/pl", "/tracking/pl/", "/tracking", "/tracking/", "/tracking_pl", "/tracking_pl.html"]:
                    safe_path = "tracking_pl.html"
                else:
                    safe_path = path.lstrip("/")
                full_path = os.path.join(STATIC_DIR, safe_path)
                if os.path.exists(full_path):
                    with open(full_path, "rb") as f:
                        data = f.read()
                    self.send_response(200)
                    if full_path.endswith(".css"): self.send_header("Content-Type", "text/css")
                    elif full_path.endswith(".js"): self.send_header("Content-Type", "application/javascript")
                    elif full_path.endswith(".png"): self.send_header("Content-Type", "image/png")
                    elif full_path.endswith(".jpg") or full_path.endswith(".jpeg"): self.send_header("Content-Type", "image/jpeg")
                    elif full_path.endswith(".svg"): self.send_header("Content-Type", "image/svg+xml")
                    elif full_path.endswith(".ico"): self.send_header("Content-Type", "image/x-icon")
                    elif full_path.endswith(".webp"): self.send_header("Content-Type", "image/webp")
                    elif full_path.endswith(".json"): self.send_header("Content-Type", "application/json")
                    else: self.send_header("Content-Type", "text/html; charset=utf-8")
                    self.send_header("Access-Control-Allow-Origin", "*")
                    self.send_header("Cache-Control", "no-cache, no-store, must-revalidate")
                    self.send_header("Pragma", "no-cache")
                    self.send_header("Expires", "0")
                    self.send_header("Content-Length", str(len(data)))
                    self.end_headers()
                    self.wfile.write(data)
                    self.wfile.flush()
                else:
                    self.send_error(404)
        except Exception as e:
            send_json(self, {"ok": False, "error": str(e)}, 500)

    def do_POST(self):
        try:
            parsed = urlparse(self.path)
            path = parsed.path
            length_hdr = self.headers.get("Content-Length") or self.headers.get("content-length") or 0
            length = int(length_hdr)
            body = json.loads(self.rfile.read(length).decode("utf-8")) if length > 0 else {}

            if path == "/api/login":
                username = (body.get("username") or "").strip()
                password = (body.get("password") or "").strip()
                with get_conn() as conn:
                    with conn.cursor() as cur:
                        # 1. Clean up expired sessions (older than 30 minutes)
                        cur.execute("DELETE FROM user_sessions WHERE last_activity IS NOT NULL AND last_activity < datetime('now', '-30 minutes')")

                        cur.execute("SELECT id, username, role, full_name, nik, department, permissions FROM users WHERE LOWER(username) = LOWER(?) AND password = ?", (username, password))
                        user = cur.fetchone()
                        if user:
                            # 2. Check if account is ALREADY active on another device
                            cur.execute("""
                                SELECT token, created_at, last_activity 
                                FROM user_sessions 
                                WHERE (user_id = ? OR username = ?) AND (last_activity IS NULL OR last_activity >= datetime('now', '-30 minutes'))
                            """, (user['id'], user['username']))
                            active_sess = cur.fetchone()
                            force_takeover = body.get("force_takeover") or body.get("force")
                            if active_sess and not force_takeover:
                                return send_json(self, {
                                    "ok": False,
                                    "error": "Akun sedang digunakan pada perangkat lain. Silakan keluar (logout) dari perangkat tersebut terlebih dahulu.",
                                    "is_active_elsewhere": True
                                }, 409)
                            elif active_sess and force_takeover:
                                cur.execute("DELETE FROM user_sessions WHERE user_id = ? OR username = ?", (user['id'], user['username']))
                                SESSIONS.pop(active_sess['token'], None)

                            user_dict = dict(user)
                            if is_admin_user(user_dict):
                                raw_p = user_dict.get('permissions')
                                full_admin_perms = {
                                    'warehouse': 2, 'work_order': 2, 'production_hold': 2, 'returns': 2,
                                    'warehouse_receipt': 2, 'traveler': 2, 'master': 2, 'report': 2, 'history': 2,
                                    'delivery_order': 2, 'assembly': 2, 'reassembly': 2, 'assy_testing': 2,
                                    'qc': 2, 'functional_test': 2, 'users': 2, 'verify_set': 2, 'verify_part': 2,
                                    'verify_replacement': 2, 'verify_items': 2, 'audit': 2, 'downtime': 2, 'testing_points': 2
                                }
                                if not raw_p or raw_p == '{}':
                                    user_dict['permissions'] = json.dumps(full_admin_perms)
                                else:
                                    try:
                                        curr_p = json.loads(raw_p) if isinstance(raw_p, str) else raw_p
                                        for k, v in full_admin_perms.items():
                                            if k not in curr_p or curr_p[k] == 0:
                                                curr_p[k] = 2
                                        user_dict['permissions'] = json.dumps(curr_p)
                                    except Exception:
                                        user_dict['permissions'] = json.dumps(full_admin_perms)

                            token = ''.join(random.choices(string.ascii_letters + string.digits, k=32))
                            SESSIONS[token] = user_dict
                            cur.execute("""
                                INSERT INTO user_sessions (token, user_id, username, role, full_name, nik, created_at, last_activity)
                                VALUES (?, ?, ?, ?, ?, ?, datetime('now', 'localtime'), datetime('now', 'localtime'))
                            """, (token, user_dict['id'], user_dict['username'], user_dict['role'], user_dict.get('full_name') or user_dict['username'], user_dict.get('nik') or ''))
                            conn.commit()
                            return send_json(self, {
                                "ok": True,
                                "token": token,
                                "role": user_dict['role'],
                                "department": user_dict.get('department') or 'assembly',
                                "permissions": user_dict.get('permissions') or '{}',
                                "username": user_dict['username'],
                                "full_name": user_dict.get('full_name') or user_dict['username'],
                                "nik": user_dict.get('nik') or ''
                            })
                        else:
                            return send_json(self, {"ok": False, "error": "Username atau Password salah"}, 401)

            elif path == "/api/logout":
                auth = self.headers.get("Authorization")
                token = auth.split(" ")[1] if auth and auth.startswith("Bearer ") else None
                if token:
                    SESSIONS.pop(token, None)
                    with get_conn() as conn:
                        with conn.cursor() as cur:
                            cur.execute("DELETE FROM user_sessions WHERE token = ?", (token,))
                            conn.commit()
                return send_json(self, {"ok": True, "message": "Berhasil logout."})

            # Semua POST butuh Auth
            user = check_auth(self)
            if not user:
                return send_json(self, {"ok": False, "error": "Unauthorized / Sesi habis"}, 401)

            with get_conn() as conn:
                with conn.cursor() as cur:
                    # User Management Endpoints (Admin Only)
                    if path == "/api/admin/users/reset-session":
                        if not user_has_perm(user, 'users', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses khusus Kelola Pengguna (Write)"}, 403)
                        target_username = (body.get("username") or "").strip()
                        target_user_id = body.get("user_id")
                        if not target_username and not target_user_id:
                            return send_json(self, {"ok": False, "error": "User ID atau Username wajib diisi"}, 400)
                        
                        cur.execute("DELETE FROM user_sessions WHERE LOWER(username) = LOWER(?) OR (user_id = ? AND ? IS NOT NULL)", (target_username, target_user_id, target_user_id))
                        conn.commit()
                        tokens_to_del = [t for t, u in SESSIONS.items() if (u.get('username') or '').lower() == target_username.lower() or (target_user_id is not None and u.get('id') == target_user_id)]
                        for t in tokens_to_del:
                            SESSIONS.pop(t, None)
                        return send_json(self, {"ok": True, "message": f"Sesi pengguna '{target_username}' berhasil di-reset. Akun kini dapat login kembali."})

                    elif path == "/api/admin/users":
                        if not user_has_perm(user, 'users', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses khusus Kelola Pengguna (Write)"}, 403)
                        nik = (body.get("nik") or "").strip()
                        full_name = (body.get("full_name") or "").strip()
                        username = (body.get("username") or "").strip()
                        password = (body.get("password") or "").strip()
                        role = (body.get("role") or "User").strip()
                        department = (body.get("department") or "assembly").strip().lower()
                        perms = body.get("permissions")
                        if isinstance(perms, dict):
                            perms_json = json.dumps(perms)
                        elif isinstance(perms, str) and perms:
                            perms_json = perms
                        else:
                            perms_json = '{}'

                        if not username or not password:
                            return send_json(self, {"ok": False, "error": "Username dan Password wajib diisi!"}, 400)

                        role = (body.get("role") or "User").strip()
                        # Jika akun baru diberi username admin atau miranti, pastikan perms_json selalu penuh
                        if username.lower() in ('admin', 'miranti'):
                            perms_json = json.dumps({
                                'warehouse': 2, 'work_order': 2, 'production_hold': 2, 'returns': 2,
                                'warehouse_receipt': 2, 'traveler': 2, 'master': 2, 'report': 2, 'history': 2,
                                'delivery_order': 2, 'assembly': 2, 'reassembly': 2, 'assy_testing': 2,
                                'qc': 2, 'functional_test': 2, 'users': 2, 'verify_set': 2, 'verify_part': 2,
                                'verify_replacement': 2, 'verify_items': 2, 'audit': 2, 'downtime': 2, 'testing_points': 2
                            })

                        cur.execute("SELECT id FROM users WHERE username = ?", (username,))
                        if cur.fetchone():
                            return send_json(self, {"ok": False, "error": f"Username '{username}' sudah digunakan!"}, 400)

                        if nik:
                            cur.execute("SELECT id FROM users WHERE nik = ?", (nik,))
                            if cur.fetchone():
                                return send_json(self, {"ok": False, "error": f"NIK '{nik}' sudah terdaftar pada pengguna lain!"}, 400)

                        cur.execute("""
                            INSERT INTO users (nik, full_name, username, password, role, department, permissions)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                            RETURNING id, nik, full_name, username, role, department, permissions, created_at
                        """, (nik, full_name, username, password, role, department, perms_json))
                        new_u = dict(cur.fetchone())
                        conn.commit()
                        return send_json(self, {"ok": True, "data": new_u})

                    elif path == "/api/admin/users/update":
                        if not user_has_perm(user, 'users', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses khusus Kelola Pengguna (Write)"}, 403)
                        user_id = body.get("id")
                        nik = (body.get("nik") or "").strip()
                        full_name = (body.get("full_name") or "").strip()
                        username = (body.get("username") or "").strip()
                        password = (body.get("password") or "").strip()
                        role = (body.get("role") or "User").strip()
                        department = (body.get("department") or "assembly").strip().lower()
                        perms = body.get("permissions")
                        if isinstance(perms, dict):
                            perms_json = json.dumps(perms)
                        elif isinstance(perms, str) and perms:
                            perms_json = perms
                        else:
                            perms_json = '{}'

                        # Jika username adalah admin atau miranti, SELALU pastikan akses penuh (super-admin guarantee)
                        if username.lower() in ('admin', 'miranti'):
                            perms_json = json.dumps({
                                'warehouse': 2, 'work_order': 2, 'production_hold': 2, 'returns': 2,
                                'warehouse_receipt': 2, 'traveler': 2, 'master': 2, 'report': 2, 'history': 2,
                                'delivery_order': 2, 'assembly': 2, 'reassembly': 2, 'assy_testing': 2,
                                'qc': 2, 'functional_test': 2, 'users': 2, 'verify_set': 2, 'verify_part': 2,
                                'verify_replacement': 2, 'verify_items': 2, 'audit': 2, 'downtime': 2, 'testing_points': 2
                            })

                        if not user_id or not username:
                            return send_json(self, {"ok": False, "error": "Data user tidak lengkap!"}, 400)

                        cur.execute("SELECT id FROM users WHERE username = ? AND id != ?", (username, user_id))
                        if cur.fetchone():
                            return send_json(self, {"ok": False, "error": f"Username '{username}' sudah digunakan oleh user lain!"}, 400)

                        if nik:
                            cur.execute("SELECT id FROM users WHERE nik = ? AND id != ?", (nik, user_id))
                            if cur.fetchone():
                                return send_json(self, {"ok": False, "error": f"NIK '{nik}' sudah terdaftar pada pengguna lain!"}, 400)

                        if password:
                            cur.execute("""
                                UPDATE users
                                SET nik = ?, full_name = ?, username = ?, password = ?, role = ?, department = ?, permissions = ?
                                WHERE id = ?
                                RETURNING id, nik, full_name, username, role, department, permissions, created_at
                            """, (nik, full_name, username, password, role, department, perms_json, user_id))
                        else:
                            cur.execute("""
                                UPDATE users
                                SET nik = ?, full_name = ?, username = ?, role = ?, department = ?, permissions = ?
                                WHERE id = ?
                                RETURNING id, nik, full_name, username, role, department, permissions, created_at
                            """, (nik, full_name, username, role, department, perms_json, user_id))

                        updated_u = cur.fetchone()
                        conn.commit()
                        return send_json(self, {"ok": True, "data": dict(updated_u) if updated_u else None})

                    elif path == "/api/admin/users/delete":
                        if not user_has_perm(user, 'users', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses khusus Kelola Pengguna (Write)"}, 403)
                        user_id = body.get("id")
                        if not user_id:
                            return send_json(self, {"ok": False, "error": "ID User diperlukan"}, 400)
                        
                        cur.execute("SELECT username FROM users WHERE id = ?", (user_id,))
                        target_u = cur.fetchone()
                        if not target_u:
                            return send_json(self, {"ok": False, "error": "User tidak ditemukan"}, 404)
                        if target_u['username'] == user['username']:
                            return send_json(self, {"ok": False, "error": "Tidak dapat menghapus akun Anda sendiri yang sedang aktif!"}, 400)
                        if target_u['username'].lower() in ('admin', 'miranti'):
                            return send_json(self, {"ok": False, "error": "Akun utama sistem (admin / miranti) tidak dapat dihapus!"}, 400)

                        cur.execute("DELETE FROM users WHERE id = ?", (user_id,))
                        conn.commit()
                        return send_json(self, {"ok": True})

                    elif path in ["/api/admin/clear_trays", "/api/admin/reset_production"]:
                        if not (is_super_admin(user) or user_has_perm(user, 'users', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        cur.execute("DELETE FROM assemblies")
                        cur.execute("DELETE FROM parts")
                        cur.execute("DELETE FROM trays")
                        cur.execute("DELETE FROM assembly_tests")
                        cur.execute("DELETE FROM assembly_in_progress")
                        cur.execute("DELETE FROM assembly_downtimes")
                        cur.execute("DELETE FROM assembly_wo_history")
                        cur.execute("DELETE FROM part_replacement_requests")
                        cur.execute("DELETE FROM re_register_requests")
                        # Bersihkan data anonim/non-standar jika ada agar stok master selalu murni
                        cur.execute("""
                            DELETE FROM master_stocks 
                            WHERE (
                                part_type = '12' AND (
                                    NOT (sn REGEXP '^[0-9]+$') OR NOT ((CAST(sn AS INTEGER) BETWEEN 60001 AND 63500) OR (CAST(sn AS INTEGER) BETWEEN 70001 AND 71500))
                                )
                            ) OR (
                                part_type = '1' AND (
                                    NOT (sn REGEXP '^[0-9]+$') OR NOT ((LENGTH(sn) = 3 AND CAST(sn AS INTEGER) BETWEEN 1 AND 999) OR (LENGTH(sn) = 4 AND CAST(sn AS INTEGER) BETWEEN 1000 AND 6000))
                                )
                            ) OR (
                                part_type = '15' AND (
                                    NOT (sn REGEXP '^[A-Z]110[0-9]{4}$') OR cs IS NULL OR cs = ''
                                )
                            )
                        """)
                        cur.execute("""
                            UPDATE master_stocks 
                            SET status = 'Tersedia', 
                                allocated_tray_id = NULL, 
                                allocated_at = NULL, 
                                used_at = NULL,
                                last_reason = NULL,
                                is_replaced_from_assy = 0,
                                last_tray_id = NULL,
                                last_operator = NULL
                        """)
                        conn.commit()
                        return send_json(self, {"ok": True, "message": "Seluruh data Tray dan Perakitan berhasil dikosongkan. Stok master telah di-reset menjadi Tersedia tanpa catatan bawaan."})

                    elif path in ["/api/gudang/input", "/api/gudang/save"]:
                        if not user_has_perm(user, 'warehouse', require_write=True) and not user_has_perm(user, 'work_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Work Order / Supply Part"}, 403)
                        pic = body.get("pic_warehouse") or user.get('full_name') or user['username']
                        assigned_op = (body.get("assigned_operator") or "").strip()
                        if not assigned_op or assigned_op in ["Semua Operator", "ALL", "Semua Operator Assembly", "-", "none"]:
                            return send_json(self, {"ok": False, "error": "Operator Assembly wajib dipilih saat membuat Tray!"}, 400)

                        source_mo = (body.get("source_document_mo") or "").strip()
                        product_type = (body.get("product_type") or "Nanggala P9").strip()
                        raw_parts = body.get("parts", [])
                        
                        # 1. Normalize and Validate all Parts against master_stocks
                        normalized_parts = []
                        stock_ids_to_allocate = []
                        seen_in_request = set()

                        for p in raw_parts:
                            p_type = str(p['part_type']).strip()
                            raw_sn = str(p['sn']).strip()
                            norm_sn = normalize_sn(p_type, raw_sn)
                            
                            if not norm_sn:
                                raise Exception(f"Serial Number Part {p_type} tidak boleh kosong!")

                            # Check duplicate within this request
                            if norm_sn in seen_in_request:
                                raise Exception(f"Nomor Produksi Part {p_type} '{norm_sn}' terduplikasi di dalam form!")
                            seen_in_request.add(norm_sn)

                            # Validate against master_stocks
                            cur.execute("""
                                SELECT id, sn, status, allocated_tray_id 
                                FROM master_stocks 
                                WHERE part_type = ? 
                                  AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                            """, (p_type, norm_sn, norm_sn))
                            stock_row = cur.fetchone()

                            if not stock_row:
                                if p_type == '1':
                                    hint = "Nomor Part 1 harus dalam rentang 001 - 999 (3 digit) atau 1000 - 6000 (4 digit)."
                                elif p_type == '12':
                                    hint = "Nomor Part 12 harus dalam rentang 60001 - 63500 atau 70001 - 71500."
                                elif p_type == '15':
                                    hint = "Nomor Part 15 harus terdaftar dalam dokumen resmi PDF (J1100001 - Z1102000)."
                                else:
                                    hint = ""
                                raise Exception(f"Nomor Produksi Part {p_type} '{raw_sn}' tidak terdaftar di Master Stock! {hint}")

                            if stock_row['status'] != 'Tersedia' or stock_row.get('allocated_tray_id') is not None:
                                raise Exception(f"Part {p_type} '{stock_row['sn']}' sudah pernah digunakan / dialokasikan (Status: {stock_row['status']}) dan tidak dapat disupply kembali!")

                            # Validasi ekstra: pastikan part belum terpasang di tray atau assembly aktif lain
                            cur.execute("""
                                SELECT t.tray_code 
                                FROM parts p 
                                JOIN trays t ON p.tray_id = t.id 
                                WHERE p.part_type = ? AND (p.sn = ? OR REPLACE(p.sn, '.', '') = REPLACE(?, '.', ''))
                                  AND UPPER(COALESCE(p.status, '')) NOT IN ('RETURNED', 'TERSEDIA', 'DECLINED')
                                  AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR COALESCE(t.return_verified, 0) = 0)
                                LIMIT 1
                            """, (p_type, stock_row['sn'], stock_row['sn']))
                            existing_part = cur.fetchone()
                            if existing_part:
                                raise Exception(f"Part {p_type} '{stock_row['sn']}' sudah pernah disupply pada Tray {existing_part['tray_code']} dan tidak dapat digunakan lagi!")

                            if p_type == '15':
                                cur.execute("""
                                    SELECT t.tray_code FROM assemblies a 
                                    JOIN trays t ON a.tray_id = t.id 
                                    WHERE (a.part15_sn = ? OR REPLACE(a.part15_sn, '.', '') = REPLACE(?, '.', ''))
                                      AND (a.is_declined IS FALSE OR a.is_declined IS NULL OR a.is_declined = 0)
                                      AND (a.verified_status NOT IN ('DECLINED', 'HOLD_GUDANG', 'REWORK_GUDANG', 'SCRAP') OR a.verified_status IS NULL)
                                      AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR COALESCE(t.return_verified, 0) = 0)
                                    LIMIT 1
                                """, (stock_row['sn'], stock_row['sn']))
                            elif p_type == '12':
                                cur.execute("""
                                    SELECT t.tray_code FROM assemblies a 
                                    JOIN trays t ON a.tray_id = t.id 
                                    WHERE (a.part12_sn = ? OR REPLACE(a.part12_sn, '.', '') = REPLACE(?, '.', ''))
                                      AND (a.is_declined IS FALSE OR a.is_declined IS NULL OR a.is_declined = 0)
                                      AND (a.verified_status NOT IN ('DECLINED', 'HOLD_GUDANG', 'REWORK_GUDANG', 'SCRAP') OR a.verified_status IS NULL)
                                      AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR COALESCE(t.return_verified, 0) = 0)
                                    LIMIT 1
                                """, (stock_row['sn'], stock_row['sn']))
                            else:
                                cur.execute("""
                                    SELECT t.tray_code FROM assemblies a 
                                    JOIN trays t ON a.tray_id = t.id 
                                    WHERE (a.part1_sn = ? OR REPLACE(a.part1_sn, '.', '') = REPLACE(?, '.', ''))
                                      AND (a.is_declined IS FALSE OR a.is_declined IS NULL OR a.is_declined = 0)
                                      AND (a.verified_status NOT IN ('DECLINED', 'HOLD_GUDANG', 'REWORK_GUDANG', 'SCRAP') OR a.verified_status IS NULL)
                                      AND (COALESCE(t.session_status, 'active') != 'RETURNED_TO_WAREHOUSE' OR COALESCE(t.return_verified, 0) = 0)
                                    LIMIT 1
                                """, (stock_row['sn'], stock_row['sn']))
                            existing_assy = cur.fetchone()
                            if existing_assy:
                                raise Exception(f"Part {p_type} '{stock_row['sn']}' sudah terpasang pada rakitan Tray {existing_assy['tray_code']} dan tidak dapat digunakan lagi!")

                            stock_ids_to_allocate.append(stock_row['id'])
                            normalized_parts.append({"part_type": p_type, "sn": stock_row['sn']})

                        # 2. Generate format berurutan: WH/ASSY/000001, WH/ASSY/000002, dst.
                        cur.execute("SELECT tray_code FROM trays WHERE tray_code LIKE 'WH/ASSY/%'")
                        all_trays = cur.fetchall()
                        max_tray_num = 0
                        for t in all_trays:
                            tc = str(t['tray_code'] or '')
                            m = re.search(r'WH/ASSY/(\d+)', tc)
                            if m:
                                max_tray_num = max(max_tray_num, int(m.group(1)))
                        next_num = max_tray_num + 1

                        tray_code = f"WH/ASSY/{next_num:06d}"
                        while True:
                            cur.execute("SELECT 1 FROM trays WHERE tray_code = ?", (tray_code,))
                            if not cur.fetchone():
                                break
                            next_num += 1
                            tray_code = f"WH/ASSY/{next_num:06d}"
                        
                        cur.execute("""
                            INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, created_at) 
                            VALUES (?, ?, ?, ?, ?, datetime('now', 'localtime')) 
                            RETURNING id, tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, created_at
                        """, (tray_code, pic, assigned_op, source_mo, product_type))
                        tray_row = dict(cur.fetchone())
                        tray_id = tray_row['id']
                        
                        # 3. Insert into parts table with status 'Supplied'
                        for p in normalized_parts:
                            cur.execute("""
                                INSERT INTO parts (tray_id, part_type, sn, status)
                                VALUES (?, ?, ?, 'Supplied')
                                ON CONFLICT (tray_id, part_type, sn) DO UPDATE
                                SET status = 'Supplied'
                            """, (tray_id, p['part_type'], p['sn']))
                        
                        # 4. Mark master_stocks as 'Supplied'
                        for sid in stock_ids_to_allocate:
                            cur.execute("UPDATE master_stocks SET status = 'Supplied', allocated_tray_id = ?, allocated_at = datetime('now', 'localtime') WHERE id = ?", (tray_id, sid))

                        conn.commit()
                        return send_json(self, {"ok": True, "data": {
                            "id": tray_id,
                            "tray_id": tray_id,
                            "tray_code": tray_row['tray_code'],
                            "pic_warehouse": tray_row['pic_warehouse'],
                            "assigned_operator": tray_row['assigned_operator'],
                            "source_document_mo": tray_row['source_document_mo'],
                            "created_at": str(tray_row['created_at'])
                        }})

                    elif path == "/api/tray/update_mo":
                        if not user_has_perm(user, 'warehouse', require_write=True) and not user_has_perm(user, 'work_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Work Order"}, 403)
                        tray_id = body.get("tray_id")
                        tray_code = body.get("tray_code")
                        mo = (body.get("source_document_mo") or "").strip()
                        if tray_id:
                            cur.execute("UPDATE trays SET source_document_mo = ? WHERE id = ? RETURNING id, tray_code, source_document_mo", (mo, tray_id))
                        else:
                            cur.execute("UPDATE trays SET source_document_mo = ? WHERE tray_code = ? RETURNING id, tray_code, source_document_mo", (mo, tray_code))
                        updated = cur.fetchone()
                        conn.commit()
                        return send_json(self, {"ok": True, "data": dict(updated) if updated else None})
                    
                    elif path == "/api/gudang/verify-tray":
                        if not (user_has_perm(user, 'warehouse', require_write=True) or user_has_perm(user, 'production_hold', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Hanya pihak dengan izin tulis Gudang / Hasil Perakitan yang berhak verifikasi aktual"}, 403)
                        tray_id = body.get("tray_id")
                        tray_code = body.get("tray_code")
                        if not tray_id and not tray_code:
                            return send_json(self, {"ok": False, "error": "ID atau Kode Tray wajib diisi"}, 400)
                        
                        # Validate that all assemblies in this tray have been verified (ACC or DECLINED)
                        if tray_id:
                            cur.execute("SELECT id, set_number, verified_status, is_declined FROM assemblies WHERE tray_id = ?", (tray_id,))
                        else:
                            cur.execute("SELECT a.id, a.set_number, a.verified_status, a.is_declined FROM assemblies a JOIN trays t ON a.tray_id = t.id WHERE t.tray_code = ?", (tray_code,))
                        
                        assy_rows = cur.fetchall()
                        if not assy_rows:
                            return send_json(self, {"ok": False, "error": "Belum ada produk rakitan dalam Tray ini yang dapat diverifikasi."}, 400)
                        
                        unverified = [r for r in assy_rows if r['verified_status'] != 'ACC' and not r['is_declined'] and r['verified_status'] != 'DECLINED']
                        if unverified:
                            return send_json(self, {
                                "ok": False, 
                                "error": f"Masih terdapat {len(unverified)} set rakitan yang belum diverifikasi (ACC / Decline). Harap verifikasi seluruh set terlebih dahulu!"
                            }, 400)
                        
                        if tray_id:
                            cur.execute("""
                                UPDATE trays 
                                SET verified_by_gudang = 1, verified_at = datetime('now', 'localtime'), verified_by_user = ? 
                                WHERE id = ? 
                                RETURNING id, tray_code, verified_by_gudang, verified_at, verified_by_user
                            """, (user['username'], tray_id))
                        else:
                            cur.execute("""
                                UPDATE trays 
                                SET verified_by_gudang = 1, verified_at = datetime('now', 'localtime'), verified_by_user = ? 
                                WHERE tray_code = ? 
                                RETURNING id, tray_code, verified_by_gudang, verified_at, verified_by_user
                            """, (user['username'], tray_code))
                        
                        updated_t = cur.fetchone()
                        conn.commit()
                        return send_json(self, {"ok": True, "data": dict(updated_t) if updated_t else None, "message": "Verifikasi Aktual berhasil dicatat. Akses cetak Laporan WH Assy terbuka."})

                    elif path in ["/api/master/override", "/api/gudang/override-status"]:
                        if not (user_has_perm(user, 'master', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Hanya pihak dengan izin tulis Master Stok / Gudang yang berhak mengubah status part"}, 403)
                        
                        p_type = str(body.get("part_type") or "").strip()
                        sn = str(body.get("sn") or "").strip()
                        new_status = str(body.get("target_status") or body.get("new_status") or "Tersedia").strip()
                        reason = (str(body.get("reason") or "").strip()) or "Override status part oleh Gudang"
                        action = str(body.get("action") or "REQUEST_OVERRIDE").strip()

                        if not p_type or not sn:
                            return send_json(self, {"ok": False, "error": "Part Type dan Serial Number wajib diisi!"}, 400)

                        cur.execute("SELECT id, status, allocated_tray_id FROM master_stocks WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (p_type, sn, sn))
                        stock_row = cur.fetchone()
                        if not stock_row:
                            return send_json(self, {"ok": False, "error": f"Part {p_type} SN {sn} tidak ditemukan di Master Stock!"}, 404)

                        old_status = stock_row['status']
                        
                        # Semua permintaan override masuk ke antrean verifikasi Admin (tidak langsung mengubah status)
                        cur.execute("""
                            INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                            RETURNING id
                        """, (p_type, sn, old_status, new_status, user['username'], user['role'], reason))
                        
                        cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE id = ?", (stock_row['id'],))
                        
                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES (?, ?, ?, 'Pending Verif Admin', 'REQUEST_OVERRIDE', ?, ?, ?, datetime('now', 'localtime'))
                        """, (p_type, sn, old_status, user['username'], user['role'], reason))

                        conn.commit()
                        return send_json(self, {
                            "ok": True,
                            "requires_admin": True,
                            "message": f"Permohonan override Part {p_type} SN {sn} ('{old_status}' -> '{new_status}') telah diajukan dan menunggu verifikasi Admin."
                        })

                    elif path == "/api/assembly/save":
                        if not user_has_perm(user, 'assembly', require_write=True): return send_json(self, {"ok":False,"error":"Akses ditolak (butuh izin tulis Assembly)"}, 403)
                        
                        tray_id = body.get('tray_id')
                        if not tray_id:
                            raise Exception("tray_id wajib diisi!")
                        
                        with get_conn() as conn_chk:
                            with conn_chk.cursor() as cur_chk:
                                cur_chk.execute("SELECT session_status FROM trays WHERE id = ?", (tray_id,))
                                tray_chk = cur_chk.fetchone()
                                if tray_chk and tray_chk.get('session_status') == 'COMPLETED_SHIFT':
                                    return send_json(self, {"ok": False, "error": "Tray ini telah selesai diserahterimakan (Shift Selesai) dan terkunci. Lanjutkan pengerjaan pada tray lanjutan."}, 400)

                        operator = (body.get('operator') or '').strip()
                        if not operator:
                            raise Exception("Nama Operator Assembly wajib diisi!")

                        part15 = (body.get('part15') or body.get('part15_sn') or '').strip()
                        part12 = (body.get('part12') or body.get('part12_sn') or '').strip()
                        part1 = (body.get('part1') or body.get('part1_sn') or '').strip()
                        if not part15 or not part12 or not part1:
                            raise Exception("Nomor SN Part 15, Part 12, dan Part 1 wajib lengkap!")

                        status = (body.get('status') or 'Good').strip()
                        raw_defective = body.get('defective_parts') or body.get('defective_part_types') or []
                        defective_parts = set(str(x).strip() for x in raw_defective)

                        # 1. Enforce Sequential Assembly Locking for new entries
                        if not body.get('is_edit'):
                            cur.execute("SELECT set_number FROM assemblies WHERE tray_id = ?", (tray_id,))
                            done_sets = {r['set_number'] for r in cur.fetchall()}
                            target_set = int(body['set_number'])
                            for s in range(1, target_set):
                                if s not in done_sets:
                                    raise Exception(f"Set #{s} belum dirakit! Anda harus menyelesaikan rakitan secara berurutan mulai dari Set #{s}.")

                        # 2. Enforce Tray Completed Locking (only 'Hold' can be edited if tray is 100% complete)
                        if body.get('is_edit'):
                            cur.execute("SELECT COUNT(DISTINCT CASE WHEN part_type = '15' THEN id END) as total_sets FROM parts WHERE tray_id = ?", (tray_id,))
                            total_s = cur.fetchone()['total_sets'] or 22
                            cur.execute("SELECT id, status FROM assemblies WHERE tray_id = ?", (tray_id,))
                            all_assys = cur.fetchall()
                            if len(all_assys) >= total_s:
                                cur.execute("SELECT status FROM assemblies WHERE id = ?", (body['assembly_id'],))
                                old_rec = cur.fetchone()
                                if old_rec and old_rec['status'] not in ('Hold', 'Rework'):
                                    raise Exception("Tray ini telah selesai 100% dan terkunci! Hanya item berstatus 'Hold' atau 'Rework' yang dapat diubah.")

                        for sn in [part15, part12, part1]:
                            cur.execute("SELECT status FROM parts WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                            res = cur.fetchone()
                            if not res: raise Exception(f"Part {sn} tidak ada di tray ini!")
                            if res['status'] == 'Done' and not body.get('is_edit'): 
                                raise Exception(f"Part {sn} sudah selesai dirakit (Done)!")

                        if body.get('is_edit') and body.get('old_sns'):
                            cur.execute("UPDATE parts SET status = 'Supplied' WHERE sn = ANY(?)", (body['old_sns'],))
                            cur.execute("UPDATE master_stocks SET status = 'Supplied' WHERE sn = ANY(?)", (body['old_sns'],))
                            cur.execute("DELETE FROM assemblies WHERE id = ?", (body['assembly_id'],))

                        # Auto-Update Master Database (Requirement B)
                        part_mapping = [('15', part15), ('12', part12), ('1', part1)]
                        for p_type, sn in part_mapping:
                            if status == 'Good':
                                # Jika Good, seluruh part statusnya 'Done'
                                cur.execute("UPDATE parts SET status = 'Done' WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                                cur.execute("UPDATE master_stocks SET status = 'Done', used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                            else:
                                # Jika ada kerusakan (Rework/Scrap/Hold/Reject):
                                if p_type in defective_parts or sn in defective_parts or f"part{p_type}" in defective_parts or f"p{p_type}" in defective_parts:
                                    # Hanya part yang di-checklist rusak yang statusnya berubah sesuai pelaporan
                                    cur.execute("UPDATE parts SET status = ? WHERE sn = ? AND tray_id = ?", (status, sn, tray_id))
                                    cur.execute("UPDATE master_stocks SET status = ?, used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (status, sn, sn))
                                else:
                                    # Sisa part yang TIDAK di-checklist rusak tetap berada di tray dengan status Good
                                    cur.execute("UPDATE parts SET status = 'Good' WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                                    cur.execute("UPDATE master_stocks SET status = 'Good', used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))

                        reason = (body.get('reason') or '').strip() if status in ('Hold', 'Scrap', 'Rework', 'Reject') else None
                        def_csv = ",".join(sorted(list(defective_parts))) if defective_parts else None
                        started_at_val = body.get('started_at')
                        if started_at_val and isinstance(started_at_val, str):
                            try:
                                if started_at_val.endswith('Z'):
                                    dt_utc = dt_mod.datetime.fromisoformat(started_at_val.replace('Z', '+00:00'))
                                    dt_local = dt_utc.astimezone(dt_mod.timezone(dt_mod.timedelta(hours=7)))
                                    started_at_val = dt_local.strftime('%Y-%m-%d %H:%M:%S')
                                elif '+' in started_at_val or '-' in started_at_val[10:]:
                                    dt_tz = dt_mod.datetime.fromisoformat(started_at_val)
                                    dt_local = dt_tz.astimezone(dt_mod.timezone(dt_mod.timedelta(hours=7)))
                                    started_at_val = dt_local.strftime('%Y-%m-%d %H:%M:%S')
                                elif 'T' in started_at_val:
                                    started_at_val = started_at_val.replace('T', ' ')
                            except Exception:
                                pass

                        finished_at_val = body.get('finished_at')
                        if finished_at_val and isinstance(finished_at_val, str):
                            try:
                                if finished_at_val.endswith('Z'):
                                    dt_utc = dt_mod.datetime.fromisoformat(finished_at_val.replace('Z', '+00:00'))
                                    dt_local = dt_utc.astimezone(dt_mod.timezone(dt_mod.timedelta(hours=7)))
                                    finished_at_val = dt_local.strftime('%Y-%m-%d %H:%M:%S')
                                elif '+' in finished_at_val or '-' in finished_at_val[10:]:
                                    dt_tz = dt_mod.datetime.fromisoformat(finished_at_val)
                                    dt_local = dt_tz.astimezone(dt_mod.timezone(dt_mod.timedelta(hours=7)))
                                    finished_at_val = dt_local.strftime('%Y-%m-%d %H:%M:%S')
                                elif 'T' in finished_at_val:
                                    finished_at_val = finished_at_val.replace('T', ' ')
                            except Exception:
                                pass

                        if not finished_at_val:
                            finished_at_val = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

                        # Guard against UTC/timezone skew where finished_at appears earlier than started_at
                        if started_at_val and finished_at_val and finished_at_val < started_at_val:
                            try:
                                dt_s = dt_mod.datetime.fromisoformat(started_at_val)
                                dt_f = dt_mod.datetime.fromisoformat(finished_at_val)
                                diff = (dt_s - dt_f).total_seconds()
                                if 18000 <= diff <= 32400:  # ~7 hours UTC gap
                                    dt_f = dt_f + dt_mod.timedelta(hours=7)
                                    finished_at_val = dt_f.strftime('%Y-%m-%d %H:%M:%S')
                                elif dt_f < dt_s:
                                    finished_at_val = started_at_val
                            except Exception:
                                pass

                        # Remove any existing record for this tray + set_number to avoid duplicates and ensure unique constraint
                        cur.execute("DELETE FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, body['set_number']))

                        cur.execute("""
                            INSERT INTO assemblies (tray_id, set_number, pic_assembly, part15_sn, part12_sn, part1_sn, status, original_assembly_status, reason, defective_parts, started_at, finished_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, datetime('now', 'localtime')), ?)
                        """, (tray_id, body['set_number'], operator, part15, part12, part1, status, status, reason, def_csv, started_at_val, finished_at_val))

                        # Record historical event into assembly_wo_history (append-only history)
                        cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                        t_code_row = cur.fetchone()
                        tray_code_val = t_code_row['tray_code'] if t_code_row else ''

                        cur.execute("""
                            INSERT INTO assembly_wo_history (
                                tray_id, tray_code, set_number, pic_assembly,
                                part15_sn, part12_sn, part1_sn,
                                status, reason, defective_parts,
                                started_at, finished_at, source, created_at
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, datetime('now', 'localtime')), ?, 'ASSEMBLY', datetime('now', 'localtime'))
                        """, (tray_id, tray_code_val, body['set_number'], operator, part15, part12, part1, status, reason, def_csv, started_at_val, finished_at_val))

                        # Automatically update tray assigned_operator to the operator working on it if unassigned or 'Semua Operator'
                        cur.execute("""
                            UPDATE trays 
                            SET assigned_operator = ? 
                            WHERE id = ? AND (assigned_operator IS NULL OR assigned_operator = '' OR assigned_operator = 'ALL' OR assigned_operator = 'Semua Operator' OR assigned_operator = 'Semua Operator Assembly')
                        """, (operator, tray_id))

                        # Clear active in-progress slot
                        cur.execute("DELETE FROM assembly_in_progress WHERE tray_id = ?", (tray_id,))

                        conn.commit()
                        return send_json(self, {"ok": True})

                    elif path == "/api/assembly/start-set":
                        if not user_has_perm(user, 'assembly', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin tulis Assembly)"}, 403)
                        tray_id = body.get('tray_id')
                        tray_code = (body.get('tray_code') or '').strip()
                        set_number = body.get('set_number')
                        operator = (body.get('operator') or user.get('full_name') or user['username']).strip()
                        part15 = (body.get('part15') or '').strip()
                        part1 = (body.get('part1') or '').strip()
                        part12 = (body.get('part12') or '').strip()
                        started_at_str = body.get('started_at')
                        
                        if not tray_id or not set_number:
                            return send_json(self, {"ok": False, "error": "tray_id dan set_number diperlukan"}, 400)
                        
                        if not tray_code:
                            cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                            tr = cur.fetchone()
                            tray_code = tr['tray_code'] if tr else ''

                        cur.execute("""
                            INSERT INTO assembly_in_progress (tray_id, tray_code, set_number, operator, part15, part1, part12, started_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, COALESCE(?, datetime('now', 'localtime')), datetime('now', 'localtime'))
                            ON CONFLICT (tray_id) DO UPDATE SET
                                tray_code = EXCLUDED.tray_code,
                                set_number = EXCLUDED.set_number,
                                operator = EXCLUDED.operator,
                                part15 = EXCLUDED.part15,
                                part1 = EXCLUDED.part1,
                                part12 = EXCLUDED.part12,
                                started_at = EXCLUDED.started_at,
                                updated_at = datetime('now', 'localtime')
                        """, (tray_id, tray_code, set_number, operator, part15, part1, part12, started_at_str))
                        conn.commit()
                        return send_json(self, {"ok": True, "message": f"Set #{set_number} tercatat sedang dirakit"})

                    elif path == "/api/assembly/clear-in-progress":
                        tray_id = body.get('tray_id')
                        if tray_id:
                            cur.execute("DELETE FROM assembly_in_progress WHERE tray_id = ?", (tray_id,))
                            conn.commit()
                        return send_json(self, {"ok": True})

                    elif path == "/api/assembly/session-action":
                        if not (user_has_perm(user, 'assembly', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses ditolak"}, 403)
                        tray_id = body.get('tray_id')
                        tray_code = (body.get('tray_code') or '').strip()
                        action = (body.get('action') or '').strip().upper() # 'HOLD', 'RETURN_WAREHOUSE', 'HANDOVER_SHIFT'
                        reason = (body.get('reason') or '').strip()
                        new_operator = (body.get('new_operator') or '').strip()

                        if not tray_id and not tray_code:
                            return send_json(self, {"ok": False, "error": "Tray ID atau Kode Tray diperlukan"}, 400)

                        if tray_id:
                            cur.execute("SELECT id, tray_code, assigned_operator, pic_warehouse FROM trays WHERE id = ?", (tray_id,))
                        else:
                            cur.execute("SELECT id, tray_code, assigned_operator, pic_warehouse FROM trays WHERE tray_code = ?", (tray_code,))
                        tray = cur.fetchone()
                        if not tray:
                            return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)

                        t_id = tray['id']
                        t_code = tray['tray_code']
                        actor_name = user.get('full_name') or user['username']

                        if action == 'RETURN_WAREHOUSE':
                            ret_reason = reason or "Dikembalikan ke gudang oleh operator assembly"
                            
                            # 1. Dapatkan rakitan yang sudah selesai di tray ini
                            cur.execute("SELECT id, set_number, part15_sn, part1_sn, part12_sn, pic_assembly FROM assemblies WHERE tray_id = ? ORDER BY set_number ASC", (t_id,))
                            finished_assemblies = cur.fetchall()
                            
                            used_sns = set()
                            for fa in finished_assemblies:
                                for col in ['part15_sn', 'part1_sn', 'part12_sn']:
                                    if fa.get(col) and fa[col] != '-':
                                        used_sns.add(fa[col])
                            
                            # Ambil semua part fisik di tray ini
                            cur.execute("SELECT id, part_type, sn, status FROM parts WHERE tray_id = ?", (t_id,))
                            all_parts = cur.fetchall()
                            
                            unused_parts = [p for p in all_parts if p['sn'] not in used_sns]
                            
                            if len(unused_parts) == 0:
                                return send_json(self, {
                                    "ok": False, 
                                    "error": f"Seluruh ({len(finished_assemblies)}) set pada Tray {t_code} sudah selesai dirakit! Tidak ada set sisa yang dapat dikembalikan ke gudang."
                                }, 400)
                            
                            unused_sns = [p['sn'] for p in unused_parts if p.get('sn')]
                            
                            # 2. Tandai part yang BELUM dirakit di master_stocks sebagai 'Dikembalikan'
                            # Part TETAP terikat ke tray (allocated_tray_id = t_id) dan TIDAK boleh langsung 'Tersedia'
                            # sampai diverifikasi fisik oleh petugas Gudang.
                            if unused_sns:
                                placeholders = ','.join(['?'] * len(unused_sns))
                                cur.execute(f"""
                                    UPDATE master_stocks 
                                    SET status = 'Dikembalikan', allocated_tray_id = ? 
                                    WHERE sn IN ({placeholders})
                                """, tuple([t_id] + list(unused_sns)))
                                
                                # 3. Tandai part yang BELUM dirakit sebagai RETURNED pada tabel parts tray
                                cur.execute(f"""
                                    UPDATE parts 
                                    SET status = 'RETURNED' 
                                    WHERE tray_id = ? AND sn IN ({placeholders})
                                """, tuple([t_id] + list(unused_sns)))
                            
                            # PERHATIAN: Rakitan yang sudah selesai (assemblies) TIDAK DIHAPUS,
                            # agar tetap utuh di riwayat perakitan, QC, dan dokumen.
                            
                            # 4. Mark tray as RETURNED_TO_WAREHOUSE with return_verified = FALSE
                            cur.execute("""
                                UPDATE trays 
                                SET session_status = 'RETURNED_TO_WAREHOUSE', 
                                    return_reason = ?, 
                                    return_notified = TRUE, 
                                    return_verified = FALSE, 
                                    return_verified_at = NULL, 
                                    return_verified_by = NULL 
                                WHERE id = ?
                            """, (ret_reason, t_id))
                            
                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES ('ALL', ?, 'ACTIVE', 'RETURNED_TO_WAREHOUSE', 'SESSION_RETURN_WAREHOUSE', ?, ?, ?, datetime('now', 'localtime'))
                            """, (t_code, user['username'], user['role'], ret_reason))
                            
                            conn.commit()
                            returned_sets_count = len([p for p in unused_parts if p['part_type'] == '15']) or (len(unused_parts) // 3)
                            return send_json(self, {
                                "ok": True, 
                                "message": f"Sisa {returned_sets_count} set yang belum dirakit pada Tray {t_code} berhasil dikembalikan ke Gudang. {len(finished_assemblies)} set yang sudah dirakit tetap tersimpan aman di riwayat perakitan."
                            })

                        elif action == 'HANDOVER_SHIFT':
                            if not new_operator:
                                return send_json(self, {"ok": False, "error": "Operator assembly penerima shift wajib dipilih!"}, 400)
                            
                            # Periksa rakitan yang sudah selesai di tray ini
                            cur.execute("SELECT id, set_number, part15_sn, part1_sn, part12_sn, pic_assembly FROM assemblies WHERE tray_id = ? ORDER BY set_number ASC", (t_id,))
                            finished_assemblies = cur.fetchall()
                            
                            if len(finished_assemblies) == 0:
                                # Belum ada rakitan selesai -> Alihkan penugasan tray secara utuh
                                cur.execute("""
                                    UPDATE trays 
                                    SET assigned_operator = ?, session_status = 'active' 
                                    WHERE id = ?
                                """, (new_operator, t_id))
                                msg = f"Seluruh Tray {t_code} (belum ada rakitan) berhasil diserahkan ke Operator '{new_operator}'."
                            else:
                                # Sudah ada rakitan selesai! Rakitan yang sudah selesai TETAP di operator awal
                                used_sns = set()
                                for fa in finished_assemblies:
                                    if fa['part15_sn'] and fa['part15_sn'] != '-':
                                        used_sns.add(fa['part15_sn'])
                                    if fa['part1_sn'] and fa['part1_sn'] != '-':
                                        used_sns.add(fa['part1_sn'])
                                    if fa['part12_sn'] and fa['part12_sn'] != '-':
                                        used_sns.add(fa['part12_sn'])
                                
                                # Ambil semua part fisik di tray ini
                                cur.execute("SELECT id, part_type, sn FROM parts WHERE tray_id = ?", (t_id,))
                                all_parts = cur.fetchall()
                                
                                unused_parts = [p for p in all_parts if p['sn'] not in used_sns]
                                
                                if len(unused_parts) == 0:
                                    return send_json(self, {"ok": False, "error": f"Semua rakitan di Tray {t_code} sudah selesai dirakit! Tidak ada sisa rakitan yang dapat dipindahkan."}, 400)
                                
                                # Buat Tray Lanjutan Baru untuk Operator Shift Selanjutnya (misal: WH/ASSY/000001-S2)
                                cur.execute("SELECT tray_code FROM trays WHERE tray_code LIKE ?", (f"{t_code}-S%",))
                                existing_s = cur.fetchall()
                                suffix_idx = len(existing_s) + 2
                                new_tray_code = f"{t_code}-S{suffix_idx}"
                                
                                cur.execute("""
                                    INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, session_status, created_at)
                                    VALUES (?, ?, ?, ?, ?, 'active', datetime('now', 'localtime'))
                                    RETURNING id
                                """, (new_tray_code, tray['pic_warehouse'], new_operator, tray.get('source_document_mo'), tray.get('product_type') or 'Nanggala P9'))
                                new_t = cur.fetchone()
                                new_tray_id = new_t['id']
                                
                                # Pindahkan part yang BELUM dirakit ke tray baru
                                for p in unused_parts:
                                    cur.execute("UPDATE parts SET tray_id = ? WHERE id = ?", (new_tray_id, p['id']))
                                    cur.execute("UPDATE master_stocks SET allocated_tray_id = ? WHERE sn = ? AND part_type = ?", (new_tray_id, p['sn'], p['part_type']))
                                
                                # Tandai tray awal sebagai COMPLETED_SHIFT agar rakitan selesai tetap tersimpan rapi
                                cur.execute("UPDATE trays SET session_status = 'COMPLETED_SHIFT' WHERE id = ?", (t_id,))
                                
                                sets_moved = len(unused_parts) // 3
                                msg = f"Sisa {sets_moved} set rakitan yang belum dirakit berhasil dipindahkan ke Tray baru '{new_tray_code}' untuk Operator '{new_operator}'. Rakitan yang sudah selesai ({len(finished_assemblies)} set) tetap tersimpan di riwayat operator sebelumnya."

                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES ('ALL', ?, ?, ?, 'SESSION_HANDOVER_SHIFT', ?, ?, ?, datetime('now', 'localtime'))
                            """, (t_code, tray['assigned_operator'] or 'Belum ada', new_operator, user['username'], user['role'], reason or f"Serah terima shift sisa rakitan ke {new_operator}."))
                            conn.commit()
                            return send_json(self, {"ok": True, "message": msg})
                        else:
                            return send_json(self, {"ok": False, "error": "Aksi sesi tidak valid"}, 400)

                    elif path == "/api/gudang/acknowledge-return":
                        if not (user_has_perm(user, 'returns', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin Pengembalian Barang / Gudang)"}, 403)
                        tray_id = body.get('tray_id')
                        if not tray_id:
                            return send_json(self, {"ok": False, "error": "tray_id diperlukan"}, 400)
                        cur.execute("UPDATE trays SET return_notified = FALSE WHERE id = ?", (tray_id,))
                        conn.commit()
                        return send_json(self, {"ok": True, "message": "Notifikasi pengembalian telah diarsipkan."})

                    elif path == "/api/gudang/verify-return-tray":
                        if not (user_has_perm(user, 'returns', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Gudang atau Administrator"}, 403)
                        tray_id = body.get("tray_id")
                        tray_code = (body.get("tray_code") or "").strip()
                        if not tray_id and not tray_code:
                            return send_json(self, {"ok": False, "error": "ID atau Kode Tray wajib disertakan"}, 400)
                        
                        if tray_id:
                            cur.execute("SELECT id, tray_code, session_status, return_verified FROM trays WHERE id = ?", (tray_id,))
                        else:
                            cur.execute("SELECT id, tray_code, session_status, return_verified FROM trays WHERE tray_code = ?", (tray_code,))
                        tray = cur.fetchone()
                        if not tray:
                            return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                        
                        if tray.get('return_verified'):
                            return send_json(self, {"ok": False, "error": "Tray pengembalian ini sudah selesai diverifikasi dan terkunci, tidak dapat diubah kembali."}, 400)
                        
                        t_id = tray['id']
                        t_code = tray['tray_code']
                        actor_name = user.get('full_name') or user['username']
                        
                        # 1. Update Tray verification status
                        cur.execute("""
                            UPDATE trays 
                            SET return_verified = TRUE, return_verified_at = datetime('now', 'localtime'), return_verified_by = ?, return_notified = FALSE 
                            WHERE id = ?
                        """, (actor_name, t_id))
                        
                        # Dapatkan serial number rakitan yang sudah selesai (TIDAK BOLEH di-reset ke Tersedia)
                        cur.execute("SELECT part15_sn, part1_sn, part12_sn FROM assemblies WHERE tray_id = ?", (t_id,))
                        finished_assemblies = cur.fetchall()
                        assembled_sns = set()
                        for fa in finished_assemblies:
                            for col in ['part15_sn', 'part1_sn', 'part12_sn']:
                                if fa.get(col) and fa[col] != '-':
                                    assembled_sns.add(fa[col])

                        # Dapatkan seluruh SN parts di tray ini
                        cur.execute("SELECT sn FROM parts WHERE tray_id = ?", (t_id,))
                        all_tray_sns = [r['sn'] for r in cur.fetchall()]
                        unassembled_sns = [s for s in all_tray_sns if s not in assembled_sns]

                        # 2. Reset status in master_stocks to 'Tersedia' HANYA untuk part yang BELUM dirakit
                        if unassembled_sns:
                            ph_unassembled = ', '.join(['?'] * len(unassembled_sns))
                            cur.execute(f"""
                                UPDATE master_stocks 
                                SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL,
                                    last_reason = NULL, is_replaced_from_assy = 0, last_tray_id = NULL, last_operator = NULL 
                                WHERE sn IN ({ph_unassembled})
                            """, tuple(unassembled_sns))
                            
                            # 3. Update status in parts table HANYA untuk part yang BELUM dirakit
                            cur.execute(f"UPDATE parts SET status = 'Tersedia' WHERE tray_id = ? AND sn IN ({ph_unassembled})", (t_id, *unassembled_sns))
                        
                        # 4. Insert Audit Log
                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES ('ALL', ?, 'RETURNED', 'Tersedia', 'VERIFY_RETURN_WAREHOUSE', ?, ?, ?, datetime('now', 'localtime'))
                        """, (t_code, user['username'], user['role'], f"Verifikasi fisik pengembalian barang tray {t_code} oleh Gudang ({len(unassembled_sns)//3 if len(unassembled_sns)>=3 else len(unassembled_sns)} set)"))
                        
                        conn.commit()
                        return send_json(self, {"ok": True, "message": f"Tray {t_code} berhasil diverifikasi pengembaliannya. Status part sisa yang belum dirakit otomatis kembali Tersedia."})

                    elif path == "/api/gudang/verify-return-set":
                        if not (user_has_perm(user, 'returns', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus pihak dengan izin tulis Pengembalian Barang / Gudang"}, 403)
                        tray_id = body.get("tray_id")
                        set_number = body.get("set_number")
                        p15_sn = (body.get("p15_sn") or "").strip()
                        p1_sn = (body.get("p1_sn") or "").strip()
                        p12_sn = (body.get("p12_sn") or "").strip()
                        
                        if not tray_id:
                            return send_json(self, {"ok": False, "error": "ID Tray wajib disertakan"}, 400)
                        
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT id, tray_code, session_status, return_verified FROM trays WHERE id = ?", (tray_id,))
                                tray = cur.fetchone()
                                if not tray:
                                    return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                                
                                if tray.get('return_verified'):
                                    return send_json(self, {"ok": False, "error": "Tray pengembalian ini sudah selesai diverifikasi dan terkunci, tidak dapat diubah kembali."}, 400)
                                
                                t_id = tray['id']
                                t_code = tray['tray_code']
                                actor_name = user.get('full_name') or user['username']
                                
                                sns_to_verify = [s for s in [p15_sn, p1_sn, p12_sn] if s and s != '-' and s != '']
                                if sns_to_verify:
                                    ph_sns = ', '.join(['?'] * len(sns_to_verify))
                                    cur.execute(f"SELECT COUNT(*) as c FROM parts WHERE tray_id = ? AND sn IN ({ph_sns}) AND status = 'Tersedia'", (t_id, *sns_to_verify))
                                    if cur.fetchone()['c'] == len(sns_to_verify):
                                        return send_json(self, {"ok": False, "error": f"Set #{set_number} sudah selesai diverifikasi dan terkunci."}, 400)
                                
                                # 1. Update parts in parts table for these serial numbers
                                if sns_to_verify:
                                    ph_sns = ', '.join(['?'] * len(sns_to_verify))
                                    cur.execute(f"UPDATE parts SET status = 'Tersedia' WHERE tray_id = ? AND sn IN ({ph_sns})", (t_id, *sns_to_verify))
                                    # 2. Update master_stocks to 'Tersedia' and unallocate
                                    cur.execute(f"""
                                        UPDATE master_stocks 
                                        SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL,
                                            last_reason = NULL, is_replaced_from_assy = 0, last_tray_id = NULL, last_operator = NULL
                                        WHERE sn IN ({ph_sns})
                                    """, tuple(sns_to_verify))
                                
                                # 3. Check if all returned parts for this tray are now verified (no more parts with status = 'RETURNED')
                                cur.execute("SELECT COUNT(*) as unverified_count FROM parts WHERE tray_id = ? AND status = 'RETURNED'", (t_id,))
                                unverified_count = cur.fetchone()['unverified_count']
                                
                                is_fully_verified = (unverified_count == 0)
                                if is_fully_verified:
                                    cur.execute("""
                                        UPDATE trays 
                                        SET return_verified = TRUE, return_verified_at = datetime('now', 'localtime'), return_verified_by = ?, return_notified = FALSE 
                                        WHERE id = ?
                                    """, (actor_name, t_id))
                                
                                # Audit log
                                for s in sns_to_verify:
                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES ('PART', ?, 'RETURNED', 'Tersedia', 'VERIFY_RETURN_SET', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (s, user['username'], user['role'], f"Verifikasi fisik Set #{set_number} Tray {t_code} oleh Gudang"))
                                
                                conn.commit()
                                return send_json(self, {
                                    "ok": True,
                                    "is_fully_verified": is_fully_verified,
                                    "message": f"Set #{set_number} Tray {t_code} berhasil diverifikasi. Part otomatis berstatus Tersedia."
                                })

                    elif path == "/api/gudang/decline-return-set":
                        if not (user_has_perm(user, 'returns', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Gudang atau Administrator"}, 403)
                        
                        tray_id = body.get("tray_id")
                        set_number = body.get("set_number")
                        declined_parts = body.get("declined_parts") or []  # List of '15', '1', '12'
                        p15_sn = (body.get("p15_sn") or "").strip()
                        p1_sn = (body.get("p1_sn") or "").strip()
                        p12_sn = (body.get("p12_sn") or "").strip()
                        reason = (body.get("reason") or "Barang/part tidak ditemukan saat verifikasi fisik pengembalian gudang").strip()
                        
                        if not tray_id:
                            return send_json(self, {"ok": False, "error": "ID Tray wajib disertakan"}, 400)
                        if not declined_parts or len(declined_parts) == 0:
                            return send_json(self, {"ok": False, "error": "Pilih minimal satu part yang di-decline!"}, 400)
                        if not reason:
                            return send_json(self, {"ok": False, "error": "Alasan penolakan / decline wajib diisi!"}, 400)
                        
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT id, tray_code, session_status, return_verified FROM trays WHERE id = ?", (tray_id,))
                                tray = cur.fetchone()
                                if not tray:
                                    return send_json(self, {"ok": False, "error": "Tray tidak ditemukan"}, 404)
                                
                                t_id = tray['id']
                                t_code = tray['tray_code']
                                
                                part_map = {'15': p15_sn, '1': p1_sn, '12': p12_sn}
                                
                                declined_sns = []
                                accepted_sns = []
                                
                                for p_type, sn in part_map.items():
                                    if not sn or sn == '-': continue
                                    if p_type in declined_parts or sn in declined_parts:
                                        declined_sns.append((p_type, sn))
                                    else:
                                        accepted_sns.append((p_type, sn))
                                
                                # 1. Process Declined Parts
                                for p_type, sn in declined_sns:
                                    # Set part status to Declined in tray
                                    cur.execute("UPDATE parts SET status = 'Declined' WHERE tray_id = ? AND sn = ?", (t_id, sn))
                                    # Set master stock to Pending Verif Admin so it is locked until Admin approves
                                    cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                    
                                    # Enqueue to Admin Verification (re_register_requests)
                                    cur.execute("""
                                        INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                                        VALUES (?, ?, 'Declined', 'Tersedia', ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                                    """, (p_type, sn, user['username'], user['role'], f"Decline saat pengembalian barang Tray {t_code} Set #{set_number}: {reason}"))
                                    
                                    # Audit log
                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, 'RETURNED', 'Pending Verif Admin', 'DECLINE_RETURN_PART', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (f"Part {p_type}", sn, user['username'], user['role'], f"Decline Set #{set_number} Tray {t_code}: {reason}"))
                                
                                # 2. Process Accepted Parts in this set (if any)
                                for p_type, sn in accepted_sns:
                                    cur.execute("UPDATE parts SET status = 'Tersedia' WHERE tray_id = ? AND sn = ?", (t_id, sn))
                                    cur.execute("""
                                        UPDATE master_stocks 
                                        SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL,
                                            last_reason = NULL, is_replaced_from_assy = 0, last_tray_id = NULL, last_operator = NULL
                                        WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                    """, (sn, sn))
                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, 'RETURNED', 'Tersedia', 'VERIFY_RETURN_PART', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (f"Part {p_type}", sn, user['username'], user['role'], f"Verifikasi fisik Set #{set_number} Tray {t_code} (Part diterima)"))

                                # 3. Update Assemblies record status if exists
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET status = 'Declined', verified_status = 'DECLINED', is_declined = TRUE, is_reregistered = FALSE, declined_reason = ?
                                    WHERE tray_id = ? AND set_number = ?
                                """, (reason, t_id, set_number))
                                
                                conn.commit()
                                return send_json(self, {
                                    "ok": True,
                                    "message": f"Set #{set_number} Tray {t_code} berhasil di-Decline ({len(declined_sns)} part) dan otomatis masuk ke antrean Verifikasi Admin."
                                })

                    elif path == "/api/assembly/pause":
                        if not (user_has_perm(user, 'assembly', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin tulis Assembly / Gudang)"}, 403)
                        tray_id = body.get('tray_id')
                        tray_code = (body.get('tray_code') or '').strip()
                        set_number = body.get('set_number')
                        reason = (body.get('reason') or '').strip()
                        operator = (body.get('operator') or user.get('full_name') or user['username']).strip()

                        if not reason:
                            return send_json(self, {"ok": False, "error": "Alasan jeda / downtime wajib diisi!"}, 400)
                        if not tray_id and not tray_code:
                            return send_json(self, {"ok": False, "error": "tray_id atau tray_code diperlukan"}, 400)

                        if not tray_id:
                            cur.execute("SELECT id FROM trays WHERE tray_code = ?", (tray_code,))
                            r = cur.fetchone()
                            tray_id = r['id'] if r else None

                        cur.execute("""
                            INSERT INTO assembly_downtimes (tray_id, tray_code, set_number, operator, reason, paused_at, created_at)
                            VALUES (?, ?, ?, ?, ?, datetime('now', 'localtime'), datetime('now', 'localtime'))
                            RETURNING id, paused_at
                        """, (tray_id, tray_code, set_number, operator, reason))
                        dt_row = cur.fetchone()
                        
                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES ('ALL', ?, 'RUNNING', 'PAUSED', 'DOWNTIME_PAUSE', ?, ?, ?, datetime('now', 'localtime'))
                        """, (f"{tray_code}_Set#{set_number or '-'}", user['username'], user['role'], f"Jeda Perakitan: {reason}"))

                        conn.commit()
                        return send_json(self, {
                            "ok": True,
                            "downtime_id": dt_row['id'],
                            "paused_at": dt_row['paused_at'],
                            "message": "Pengerjaan perakitan berhasil dijeda."
                        })

                    elif path == "/api/assembly/resume":
                        if not (user_has_perm(user, 'assembly', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin tulis Assembly / Gudang)"}, 403)
                        downtime_id = body.get('downtime_id')
                        tray_id = body.get('tray_id')

                        if downtime_id:
                            cur.execute("SELECT * FROM assembly_downtimes WHERE id = ?", (downtime_id,))
                        elif tray_id:
                            cur.execute("SELECT * FROM assembly_downtimes WHERE tray_id = ? AND resumed_at IS NULL ORDER BY id DESC LIMIT 1", (tray_id,))
                        else:
                            return send_json(self, {"ok": False, "error": "downtime_id atau tray_id diperlukan"}, 400)

                        dt_row = cur.fetchone()
                        if not dt_row:
                            return send_json(self, {"ok": True, "message": "Tidak ada jeda aktif yang perlu dilanjutkan."})

                        cur.execute("""
                            UPDATE assembly_downtimes 
                            SET resumed_at = datetime('now', 'localtime'), duration_seconds = CAST((unixepoch('now') - unixepoch(paused_at)) AS INTEGER) 
                            WHERE id = ?
                            RETURNING id, paused_at, resumed_at, duration_seconds, tray_code, set_number
                        """, (dt_row['id'],))
                        updated = cur.fetchone()

                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES ('ALL', ?, 'PAUSED', 'RESUMED', 'DOWNTIME_RESUME', ?, ?, ?, datetime('now', 'localtime'))
                        """, (f"{updated['tray_code']}_Set#{updated['set_number'] or '-'}", user['username'], user['role'], f"Lanjut Perakitan setelah downtime {updated['duration_seconds']} detik."))

                        conn.commit()
                        return send_json(self, {
                            "ok": True,
                            "data": dict(updated),
                            "message": "Pengerjaan perakitan berhasil dilanjutkan."
                        })

                    elif path == "/api/gudang/verify-set":
                        if not (user_has_perm(user, 'production_hold', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Hanya Pihak Gudang & Admin yang berhak melakukan verifikasi"}, 403)
                        
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')
                        action = (body.get('action') or 'ACC').upper() # 'ACC' or 'DECLINE'
                        
                        if not tray_id or set_number is None:
                            return send_json(self, {"ok": False, "error": "tray_id dan set_number wajib diisi!"}, 400)
                        
                        cur.execute("SELECT * FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, set_number))
                        assy = cur.fetchone()
                        if not assy:
                            return send_json(self, {"ok": False, "error": f"Set #{set_number} tidak ditemukan!"}, 404)
                        
                        if action == 'ACC':
                            new_status = (body.get('status') or assy['status'] or 'Good').strip()
                            raw_def = body.get('defective_parts') or []
                            defective_parts = set(str(x).strip() for x in raw_def)
                            def_csv = ",".join(sorted(list(defective_parts))) if defective_parts else None
                            reason = (body.get('reason') or assy['reason'] or '').strip() if new_status != 'Good' else None
                            
                            old_status = assy['status']
                            part_mapping = [('15', assy['part15_sn']), ('12', assy['part12_sn']), ('1', assy['part1_sn'])]
                            for p_type, sn in part_mapping:
                                if sn and sn != '-':
                                    if new_status == 'Good':
                                        cur.execute("UPDATE parts SET status = 'Done' WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                                        cur.execute("UPDATE master_stocks SET status = 'Done', allocated_tray_id = ?, used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (tray_id, sn, sn))
                                    else:
                                        if p_type in defective_parts or sn in defective_parts:
                                            cur.execute("UPDATE parts SET status = ? WHERE sn = ? AND tray_id = ?", (new_status, sn, tray_id))
                                            cur.execute("UPDATE master_stocks SET status = ?, allocated_tray_id = ?, used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (new_status, tray_id, sn, sn))
                                        else:
                                            cur.execute("UPDATE parts SET status = 'Good' WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                                            cur.execute("UPDATE master_stocks SET status = 'Good', allocated_tray_id = ?, used_at = datetime('now', 'localtime') WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (tray_id, sn, sn))

                                    # Auto-resolve any pending decline re_register_requests for these SNs
                                    cur.execute("""
                                        UPDATE re_register_requests 
                                        SET status = 'RESOLVED_ACC', admin_notes = 'Otomatis di-ACC saat Verifikasi Gudang', approved_at = datetime('now', 'localtime') 
                                        WHERE sn = ? AND status = 'PENDING'
                                    """, (sn,))
                            
                            if new_status != old_status or def_csv != assy.get('defective_parts') or assy.get('is_declined'):
                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('ALL', ?, ?, ?, 'VERIFY_OVERRIDE', ?, ?, ?, datetime('now', 'localtime'))
                                """, (f"Tray#{tray_id}_Set#{set_number}", old_status, new_status, user['username'], user['role'], reason or 'Status diubah saat Verifikasi Gudang'))

                            cur.execute("""
                                UPDATE assemblies 
                                SET status = ?, defective_parts = ?, reason = ?, verified_status = 'ACC', is_declined = FALSE 
                                WHERE id = ?
                            """, (new_status, def_csv, reason, assy['id']))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Set #{set_number} berhasil di-ACC (Status: {new_status})"})

                        elif action == 'DECLINE':
                            if assy.get('verified_status') == 'ACC':
                                return send_json(self, {"ok": False, "error": "Item yang sudah di-ACC telah terkunci dan tidak dapat di-Decline kembali!"}, 400)

                            dec_reason = body.get('reason') or "Barang/part tidak ditemukan saat verifikasi fisik gudang"
                            
                            part_mapping = [('15', assy['part15_sn']), ('12', assy['part12_sn']), ('1', assy['part1_sn'])]
                            for p_type, sn in part_mapping:
                                if sn and sn != '-':
                                    # Set part status to Declined in tray
                                    cur.execute("UPDATE parts SET status = 'Declined' WHERE sn = ? AND tray_id = ?", (sn, tray_id))
                                    # Set master stock to Pending Verif Admin so it is NOT available until Admin approves
                                    cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                    
                                    # Auto-enqueue to Admin Verification
                                    cur.execute("""
                                        INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                                        VALUES (?, ?, 'Declined', 'Tersedia', ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                                    """, (p_type, sn, user['username'], user['role'], f"Decline saat verifikasi fisik Tray#{tray_id} Set#{set_number}: {dec_reason}"))

                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, ?, 'Pending Verif Admin', 'VERIFY_DECLINE', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (p_type, sn, assy['status'], user['username'], user['role'], dec_reason))

                            cur.execute("""
                                UPDATE assemblies 
                                SET status = 'Declined', verified_status = 'DECLINED', is_declined = TRUE, is_reregistered = FALSE, declined_reason = ?
                                WHERE id = ?
                            """, (dec_reason, assy['id']))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Set #{set_number} telah di-Decline dan seluruh part otomatis masuk ke Verifikasi Admin untuk diperiksa."})

                    elif path in ["/api/gudang/verify-set-reregister", "/api/master/reregister-declined"]:
                        if not (user_has_perm(user, 'production_hold', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Hanya Pihak Gudang & Admin yang berhak mendaftarkan ulang"}, 403)
                        
                        assembly_id = body.get('assembly_id')
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')
                        target_status = (body.get('target_status') or 'Good').strip()
                        reason = (body.get('reason') or 'Didaftarkan ulang oleh Gudang setelah Decline').strip()
                        
                        if not assembly_id and (not tray_id or set_number is None):
                            return send_json(self, {"ok": False, "error": "assembly_id atau tray_id + set_number diperlukan!"}, 400)
                        
                        if assembly_id:
                            cur.execute("SELECT * FROM assemblies WHERE id = ?", (assembly_id,))
                        else:
                            cur.execute("SELECT * FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, set_number))
                        
                        target_assy = cur.fetchone()
                        if not target_assy:
                            return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                        part_mapping = [('15', target_assy['part15_sn']), ('12', target_assy['part12_sn']), ('1', target_assy['part1_sn'])]
                        for p_type, sn in part_mapping:
                            if sn and sn != '-':
                                cur.execute("""
                                    INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                                    VALUES (?, ?, 'Declined', ?, ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                                """, (p_type, sn, target_status, user['username'], user['role'], reason))
                                cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                        
                        is_from_qc = (target_assy.get('qc_status') in ['HOLD_QC', 'REWORK_QC']) or bool(target_assy.get('qc_failure_reasons')) or (target_assy.get('stage') == 'QC')
                        target_v_status = 'PENDING_ADMIN_QC' if is_from_qc else 'PENDING_ADMIN'
                        cur.execute("UPDATE assemblies SET is_reregistered = TRUE, verified_status = ? WHERE id = ?", (target_v_status, target_assy['id']))
                        
                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES ('ALL', ?, 'DECLINED', 'Pending Verif Admin', 'REQUEST_REREGISTER_DECLINED', ?, ?, ?, datetime('now', 'localtime'))
                        """, (f"Tray#{target_assy['tray_id']}_Set#{target_assy['set_number']}", user['username'], user['role'], reason))
                        
                        conn.commit()
                        return send_json(self, {"ok": True, "message": f"Permohonan pendaftaran ulang Set #{target_assy['set_number']} telah diajukan ke Admin untuk diverifikasi."})

                    elif path == "/api/assembly/reregister-set":
                        if not user_has_perm(user, 'assembly', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Perakitan"}, 403)
                        # NEW FLOW: Daftar Ulang Perakitan
                        # - Does NOT change target_status
                        # - Instead picks new_operator (who will re-assemble)
                        # - Assembly goes back to assembly queue after admin approval
                        # - New assembly document generated with WO-PU{n} suffix
                        assembly_id = body.get('assembly_id')
                        new_operator = str(body.get('new_operator') or body.get('rereg_operator') or '').strip()
                        reason = str(body.get('reason') or '').strip() or 'Didaftarkan ulang untuk perakitan ulang'

                        if not assembly_id:
                            return send_json(self, {"ok": False, "error": "assembly_id wajib diisi"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT a.*, t.tray_code FROM assemblies a JOIN trays t ON a.tray_id = t.id WHERE a.id = ?", (assembly_id,))
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                                if assy.get('verified_status') in ['PENDING_ADMIN', 'PENDING_ADMIN_QC', 'PENDING_ADMIN_ASSY_REREG']:
                                    return send_json(self, {"ok": False, "error": "Rakitan ini sudah diajukan dan sedang dalam antrean verifikasi Administrator. Harap tunggu konfirmasi Admin."}, 400)

                                # Use current operator if no new operator specified
                                rereg_operator = new_operator or assy.get('pic_assembly') or user['username']

                                # GUARD: Jika rakitan berasal dari Uji Fungsional 1 atau 2 (Testing), JANGAN ubah ke PENDING_ADMIN_ASSY_REREG!
                                is_test_item = bool(
                                    assy.get('qc_status') in ['HOLD_QC', 'HOLD_ASSY', 'REWORK_QC']
                                    or assy.get('assy_failure_reasons')
                                    or assy.get('qc_failure_reasons')
                                    or assy.get('assy_test_checklist')
                                )
                                if is_test_item:
                                    is_qc = (assy.get('qc_status') in ['HOLD_QC', 'REWORK_QC']) or bool(assy.get('qc_failure_reasons'))
                                    target_v = 'PENDING_ADMIN_QC' if is_qc else 'PENDING_ADMIN'
                                    cur.execute("""
                                        UPDATE assemblies 
                                        SET is_reregistered = TRUE, 
                                            verified_status = ?,
                                            rereg_pending_operator = NULL,
                                            reason = COALESCE(reason, '') || ' | ' || ?
                                        WHERE id = ?
                                    """, (target_v, reason, assembly_id))
                                    sn_list = [s for s in [assy['part15_sn'], assy['part1_sn'], assy['part12_sn']] if s and s != '-']
                                    for s in sn_list:
                                        cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (s, s))
                                    conn.commit()
                                    return send_json(self, {
                                        "ok": True,
                                        "message": f"Barang rakitan Set #{assy['set_number']} (Hasil Test) berhasil diajukan verifikasi ke Admin."
                                    })

                                # Update assembly: mark as pending re-assembly verification
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET is_reregistered = TRUE, 
                                        verified_status = 'PENDING_ADMIN_ASSY_REREG',
                                        rereg_pending_operator = ?,
                                        reason = COALESCE(reason, '') || ' | Pengajuan Daftar Ulang oleh Operator: ' || ?
                                    WHERE id = ?
                                """, (rereg_operator, reason, assembly_id))

                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('ALL', ?, ?, 'PENDING_ADMIN_ASSY_REREG', 'REQUEST_ASSY_REREG', ?, ?, ?, datetime('now', 'localtime'))
                                """, (f"Tray#{assy['tray_id']}_Set#{assy['set_number']}", assy.get('status', 'Hold'), user['username'], user['role'], f"Daftar Ulang Perakitan - Operator: {rereg_operator}: {reason}"))

                                conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "message": f"Permohonan pendaftaran ulang rakitan Set #{assy['set_number']} telah diajukan ke Admin. Operator yang akan merakit ulang: {rereg_operator}.",
                            "rereg_operator": rereg_operator
                        })

                    elif path == "/api/assembly/transfer-to-warehouse":
                        if not user_has_perm(user, 'assembly', require_write=True) and not user_has_perm(user, 'production_hold', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin untuk transfer ke Gudang"}, 403)
                        assembly_id = body.get('assembly_id')
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')
                        reason = (body.get('reason') or '').strip() or 'Dipindahkan ke gudang oleh operator dari Hasil Perakitan'

                        if not assembly_id and not (tray_id and set_number):
                            return send_json(self, {"ok": False, "error": "assembly_id atau (tray_id, set_number) diperlukan"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if assembly_id:
                                    cur.execute("""
                                        SELECT a.*, t.tray_code 
                                        FROM assemblies a 
                                        JOIN trays t ON a.tray_id = t.id 
                                        WHERE a.id = ?
                                    """, (assembly_id,))
                                else:
                                    cur.execute("""
                                        SELECT a.*, t.tray_code 
                                        FROM assemblies a 
                                        JOIN trays t ON a.tray_id = t.id 
                                        WHERE a.tray_id = ? AND a.set_number = ?
                                    """, (tray_id, set_number))
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data rakitan tidak ditemukan"}, 404)

                                a_id = assy['id']
                                current_st = assy.get('status') or 'Hold'
                                target_st = current_st if current_st in ['Hold', 'Rework', 'Scrap'] else 'Hold'
                                verif_st = 'HOLD_GUDANG' if target_st == 'Hold' else ('REWORK_GUDANG' if target_st == 'Rework' else 'SCRAP')

                                cur.execute("""
                                    UPDATE assemblies
                                    SET status = ?,
                                        verified_status = ?,
                                        is_declined = FALSE,
                                        reason = COALESCE(reason, '') || ' | Dipindahkan ke gudang: ' || ?
                                    WHERE id = ?
                                """, (target_st, verif_st, reason, a_id))

                                for p_type, sn in [('15', assy['part15_sn']), ('1', assy['part1_sn']), ('12', assy['part12_sn'])]:
                                    if sn and sn != '-':
                                        cur.execute("""
                                            UPDATE master_stocks
                                            SET status = ?, allocated_tray_id = ?
                                            WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                        """, (target_st, assy['tray_id'], p_type, sn, sn))
                                        cur.execute("""
                                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                            VALUES (?, ?, ?, ?, 'TRANSFER_TO_WAREHOUSE', ?, ?, ?, datetime('now', 'localtime'))
                                        """, (p_type, sn, current_st, target_st, user['username'], user.get('role', 'operator'), f"Transfer Set #{assy['set_number']} Tray {assy['tray_code']} ke Gudang: {reason}"))

                                cur.execute("""
                                    UPDATE trays 
                                    SET return_notified = TRUE,
                                        return_verified = FALSE
                                    WHERE id = ?
                                """, (assy['tray_id'],))

                                conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "message": f"Set #{assy['set_number']} pada Work Order {assy['tray_code']} berhasil dipindahkan ke Gudang."
                        })

                    elif path == "/api/assembly/replace-part":
                        if not user_has_perm(user, 'assembly', require_write=True) and not user_has_perm(user, 'production_hold', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin untuk mengajukan penggantian part"}, 403)
                        assembly_id = body.get('assembly_id')
                        reason = str(body.get('reason') or '').strip() or 'Penggantian part bermasalah'
                        old_part_status = str(body.get('old_part_status') or 'Hold').strip()
                        if old_part_status not in ['Hold', 'Rework', 'Scrap']:
                            old_part_status = 'Hold'

                        # Support both batch replacements array and legacy single part payload
                        replacements = body.get('replacements')
                        if not replacements or not isinstance(replacements, list):
                            part_type = str(body.get('part_type') or '').strip()
                            new_sn = str(body.get('new_sn') or '').strip()
                            if part_type and new_sn:
                                replacements = [{'part_type': part_type, 'new_sn': new_sn}]
                            else:
                                replacements = []

                        if not assembly_id or not replacements:
                            return send_json(self, {"ok": False, "error": "assembly_id dan minimal 1 part baru pengganti wajib diisi"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT a.*, t.tray_code FROM assemblies a JOIN trays t ON a.tray_id = t.id WHERE a.id = ?", (assembly_id,))
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                                cur.execute("SELECT id FROM part_replacement_requests WHERE assembly_id = ? AND status = 'PENDING'", (assembly_id,))
                                if cur.fetchone() or assy.get('verified_status') == 'PENDING_REPLACEMENT':
                                    return send_json(self, {"ok": False, "error": "Rakitan ini sudah memiliki pengajuan pergantian part yang sedang menunggu verifikasi Administrator."}, 400)

                                detailed_replacements = []

                                for rep in replacements:
                                    p_type = str(rep.get('part_type') or '').strip()
                                    n_sn = str(rep.get('new_sn') or '').strip()
                                    if not p_type or not n_sn or p_type not in ['15', '1', '12']:
                                        continue

                                    cur.execute("""
                                        SELECT id, sn, part_type, status FROM master_stocks 
                                        WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status IN ('Tersedia', 'Good')
                                    """, (p_type, n_sn, n_sn))
                                    new_stock = cur.fetchone()
                                    if not new_stock:
                                        return send_json(self, {"ok": False, "error": f"Part baru {p_type} SN {n_sn} tidak ditemukan atau statusnya bukan 'Tersedia' di Master Stok!"}, 400)

                                    if p_type == '15':
                                        old_sn = assy['part15_sn']
                                    elif p_type == '1':
                                        old_sn = assy['part1_sn']
                                    elif p_type == '12':
                                        old_sn = assy['part12_sn']
                                    else:
                                        old_sn = '-'

                                    detailed_replacements.append({
                                        'part_type': p_type,
                                        'old_sn': old_sn,
                                        'new_sn': new_stock['sn'],
                                        'new_stock_id': new_stock['id']
                                    })

                                if not detailed_replacements:
                                    return send_json(self, {"ok": False, "error": "Tidak ada part valid yang diajukan untuk diganti"}, 400)

                                # Mark new parts in master_stocks as 'Pending Verif Admin' so they are reserved
                                for dr in detailed_replacements:
                                    cur.execute("""
                                        UPDATE master_stocks 
                                        SET status = 'Pending Verif Admin',
                                            last_reason = ?,
                                            last_tray_id = ?,
                                            last_operator = ?
                                        WHERE id = ?
                                    """, (f"Diajukan ganti part Set #{assy['set_number']} WO {assy['tray_code']}: {reason}", assy['tray_id'], user.get('full_name') or user.get('username'), dr['new_stock_id']))

                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, 'Tersedia', 'Pending Verif Admin', 'REPLACE_PART_REQUESTED', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (dr['part_type'], dr['new_sn'], user['username'], user.get('role') or 'operator', f"Diajukan ganti part Set #{assy['set_number']} WO {assy['tray_code']} menggantikan SN {dr['old_sn']}: {reason}"))

                                # Record into part_replacement_requests
                                cur.execute("""
                                    INSERT INTO part_replacement_requests (
                                        assembly_id, tray_id, set_number, replacements_json, old_part_status,
                                        reason, requestor_username, requestor_role, status, created_at
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                                """, (
                                    assembly_id, assy['tray_id'], assy['set_number'],
                                    json.dumps([{ 'part_type': r['part_type'], 'old_sn': r['old_sn'], 'new_sn': r['new_sn'] } for r in detailed_replacements]),
                                    old_part_status, reason, user['username'], user.get('role') or 'operator'
                                ))

                                # Preserve original assembly status and set verified_status to PENDING_REPLACEMENT
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET original_assembly_status = COALESCE(original_assembly_status, status),
                                        verified_status = 'PENDING_REPLACEMENT' 
                                    WHERE id = ?
                                """, (assembly_id,))

                                # Crucial: Unreplaced parts in this assembly MUST NOT enter /part verification.
                                # Delete any pending re_register_requests for all parts of this assembly.
                                cur.execute("""
                                    DELETE FROM re_register_requests 
                                    WHERE sn IN (?, ?, ?) AND status = 'PENDING'
                                """, (assy['part15_sn'], assy['part1_sn'], assy['part12_sn']))

                                # Make sure unreplaced parts remain 'Used' in master_stocks
                                new_ids_csv = ','.join(str(dr['new_stock_id']) for dr in detailed_replacements)
                                cur.execute(f"""
                                    UPDATE master_stocks 
                                    SET status = 'Used', allocated_tray_id = ?, last_reason = NULL
                                    WHERE sn IN (?, ?, ?) 
                                      AND status = 'Pending Verif Admin'
                                      AND id NOT IN ({new_ids_csv})
                                """, (assy['tray_id'], assy['part15_sn'], assy['part1_sn'], assy['part12_sn']))

                                conn.commit()

                        msg = f"Permohonan penggantian {len(detailed_replacements)} part pada Set #{assy['set_number']} berhasil diajukan untuk verifikasi Administrator!"
                        return send_json(self, {"ok": True, "message": msg})

                    elif path == "/api/master/re-register":
                        if not (user_has_perm(user, 'master', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Hanya Pihak Gudang & Admin yang berhak mendaftarkan ulang part"}, 403)
                        
                        assembly_id = body.get("assembly_id")
                        p_type = str(body.get("part_type") or "").strip()
                        sn = str(body.get("sn") or "").strip()
                        target_status = str(body.get("target_status") or "Tersedia").strip()
                        rework_reason_in = str(body.get("rework_reason") or "").strip()
                        reason = rework_reason_in or (str(body.get("reason") or "").strip()) or "Didaftarkan ulang oleh Gudang"

                        connected_assy = None
                        if assembly_id:
                            cur.execute("""
                                SELECT a.id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn, 
                                       a.status as assembly_status, a.assy_test_status, a.assy_failure_reasons, 
                                       a.qc_status, a.qc_failure_reasons, a.rework_reason
                                FROM assemblies a
                                WHERE a.id = ?
                            """, (assembly_id,))
                            connected_assy = cur.fetchone()
                            if not connected_assy:
                                return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)
                        elif p_type and sn:
                            cur.execute("""
                                SELECT a.id, a.tray_id, a.set_number, a.part15_sn, a.part1_sn, a.part12_sn, 
                                       a.status as assembly_status, a.assy_test_status, a.assy_failure_reasons, 
                                       a.qc_status, a.qc_failure_reasons, a.rework_reason
                                FROM assemblies a
                                WHERE (a.part15_sn = ? OR REPLACE(a.part15_sn, '.', '') = REPLACE(?, '.', ''))
                                   OR (a.part1_sn = ? OR REPLACE(a.part1_sn, '.', '') = REPLACE(?, '.', ''))
                                   OR (a.part12_sn = ? OR REPLACE(a.part12_sn, '.', '') = REPLACE(?, '.', ''))
                                ORDER BY a.id DESC LIMIT 1
                            """, (sn, sn, sn, sn, sn, sn))
                            connected_assy = cur.fetchone()

                        if connected_assy:
                            parts_to_rereg = [
                                ('15', connected_assy['part15_sn']),
                                ('1', connected_assy['part1_sn']),
                                ('12', connected_assy['part12_sn'])
                            ]
                            for pt, psn in parts_to_rereg:
                                if psn and psn != '-':
                                    cur.execute("SELECT id, status FROM master_stocks WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (pt, psn, psn))
                                    stk = cur.fetchone()
                                    curr_st = stk['status'] if stk else 'Hold'
                                    cur.execute("DELETE FROM re_register_requests WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status = 'PENDING'", (pt, psn, psn))
                                    cur.execute("""
                                        INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                                    """, (pt, psn, curr_st, target_status, user['username'], user['role'], reason))
                                    if stk:
                                        cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE id = ?", (stk['id'],))
                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, ?, 'Pending Verif Admin', 'REQUEST_REREGISTER', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (pt, psn, curr_st, user['username'], user['role'], f"{reason} (Daftar Ulang Set #{connected_assy['set_number']})"))

                            is_tf1 = (connected_assy.get('assy_test_status') in ['Hold', 'Rework']) or (connected_assy.get('qc_status') in ['HOLD_ASSY', 'REWORK_ASSY']) or bool(connected_assy.get('assy_failure_reasons'))
                            is_from_qc = not is_tf1 and ((connected_assy.get('qc_status') in ['HOLD_QC', 'REWORK_QC']) or bool(connected_assy.get('qc_failure_reasons')))
                            target_v_status = 'PENDING_ADMIN_QC' if is_from_qc else 'PENDING_ADMIN'
                            existing_rw = (connected_assy.get('rework_reason') or '').strip()
                            if rework_reason_in:
                                if existing_rw and rework_reason_in not in existing_rw:
                                    updated_rw = f"{existing_rw} | {rework_reason_in}"
                                else:
                                    updated_rw = existing_rw or rework_reason_in
                            else:
                                updated_rw = existing_rw

                            cur.execute("""
                                UPDATE assemblies 
                                SET is_reregistered = TRUE, 
                                    verified_status = ?,
                                    rework_reason = ?
                                WHERE id = ?
                            """, (target_v_status, updated_rw, connected_assy['id']))

                            conn.commit()
                            return send_json(self, {
                                "ok": True,
                                "requires_admin": True,
                                "is_set": True,
                                "message": f"Satu set perakitan (Part 15, 1, 12) berhasil diajukan daftar ulang ke Admin (Menunggu Verifikasi Admin)."
                            })
                        elif p_type and sn:
                            # Single part not connected to an assembly
                            cur.execute("SELECT id, status, is_replaced_from_assy, last_reason FROM master_stocks WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (p_type, sn, sn))
                            stock_row = cur.fetchone()
                            if not stock_row:
                                return send_json(self, {"ok": False, "error": f"Part {p_type} SN {sn} tidak ditemukan di Master Stock!"}, 404)

                            curr_status = stock_row['status']
                            cur.execute("""
                                DELETE FROM re_register_requests 
                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status = 'PENDING'
                            """, (p_type, sn, sn))

                            cur.execute("""
                                INSERT INTO re_register_requests (part_type, sn, current_status, target_status, requestor_username, requestor_role, reason, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', datetime('now', 'localtime'))
                            """, (p_type, sn, curr_status, target_status, user['username'], user['role'], reason))
                            
                            cur.execute("UPDATE master_stocks SET status = 'Pending Verif Admin' WHERE id = ?", (stock_row['id'],))
                            
                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES (?, ?, ?, 'Pending Verif Admin', 'REQUEST_REREGISTER', ?, ?, ?, datetime('now', 'localtime'))
                            """, (p_type, sn, curr_status, user['username'], user['role'], f"{reason} (Daftar Ulang Part Satuan)"))

                            conn.commit()
                            return send_json(self, {
                                "ok": True,
                                "requires_admin": True,
                                "is_set": False,
                                "message": f"Part {p_type} SN {sn} berhasil diajukan daftar ulang ke Admin (Menunggu Verifikasi Admin)."
                            })
                        else:
                            return send_json(self, {"ok": False, "error": "assembly_id atau (part_type dan sn) wajib diisi!"}, 400)

                    elif path == "/api/admin/approve-re-register":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_part', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        
                        req_id = body.get('request_id')
                        action = (body.get('action') or 'APPROVE').upper() # 'APPROVE' or 'REJECT'
                        target_status = (body.get('target_status') or '').strip()
                        admin_notes = (body.get('notes') or '').strip()

                        if not req_id:
                            return send_json(self, {"ok": False, "error": "request_id diperlukan!"}, 400)

                        cur.execute("SELECT * FROM re_register_requests WHERE id = ?", (req_id,))
                        req = cur.fetchone()
                        if not req:
                            return send_json(self, {"ok": False, "error": "Data permohonan tidak ditemukan"}, 404)

                        final_status = target_status if target_status else (req['target_status'] or 'Tersedia')

                        if action == 'APPROVE':
                            resolved_status = 'Tersedia' if (final_status in ['Good', 'Tersedia']) else final_status
                            cur.execute("""
                                UPDATE re_register_requests 
                                SET status = 'APPROVED', target_status = ?, admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') 
                                WHERE id = ?
                            """, (resolved_status, user['username'], admin_notes, req_id))

                            if resolved_status == 'Tersedia':
                                cur.execute("""
                                    UPDATE master_stocks 
                                    SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL, used_at = NULL,
                                        is_replaced_from_assy = 0, last_reason = NULL, last_tray_id = NULL, last_operator = NULL
                                    WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                """, (req['part_type'], req['sn'], req['sn']))
                                cur.execute("""
                                    UPDATE parts 
                                    SET status = 'Tersedia' 
                                    WHERE sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')
                                """, (req['sn'], req['sn']))
                            else:
                                cur.execute("""
                                    UPDATE master_stocks 
                                    SET status = ? 
                                    WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                """, (resolved_status, req['part_type'], req['sn'], req['sn']))
                            # Parts are restored to master_stocks as 'Tersedia' (declined assembly stays declined)
                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES (?, ?, ?, ?, 'ADMIN_APPROVE_REREGISTER', ?, ?, ?, datetime('now', 'localtime'))
                            """, (req['part_type'], req['sn'], req['current_status'], resolved_status, user['username'], user['role'], f"Diverifikasi Admin ({resolved_status}): {admin_notes or 'Disetujui'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Part {req['part_type']} SN {req['sn']} berhasil diverifikasi menjadi status '{resolved_status}' dan siap digunakan kembali di Gudang."})
                        else:
                            cur.execute("""
                                UPDATE re_register_requests 
                                SET status = 'REJECTED', admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') 
                                WHERE id = ?
                            """, (user['username'], admin_notes, req_id))

                            cur.execute("""
                                UPDATE master_stocks 
                                SET status = ? 
                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                            """, (req['current_status'], req['part_type'], req['sn'], req['sn']))

                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES (?, ?, 'Pending Verif Admin', ?, 'ADMIN_REJECT_REREGISTER', ?, ?, ?, datetime('now', 'localtime'))
                            """, (req['part_type'], req['sn'], req['current_status'], user['username'], user['role'], f"Ditolak Admin: {admin_notes or 'Tidak disetujui'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Permohonan Part {req['part_type']} SN {req['sn']} telah DITOLAK. Status tetap '{req['current_status']}'."})

                    elif path == "/api/admin/approve-part-direct":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_part', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        
                        part_type = str(body.get('part_type') or '').strip()
                        sn = str(body.get('sn') or '').strip()
                        target_status = str(body.get('target_status') or 'Tersedia').strip()
                        notes = str(body.get('notes') or '').strip()

                        if not part_type or not sn:
                            return send_json(self, {"ok": False, "error": "part_type dan sn diperlukan!"}, 400)

                        resolved_status = 'Tersedia' if target_status in ['Good', 'Tersedia'] else target_status

                        # Check if there is an existing pending request for this part
                        cur.execute("""
                            UPDATE re_register_requests 
                            SET status = 'APPROVED', target_status = ?, admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') 
                            WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status = 'PENDING'
                        """, (resolved_status, user['username'], notes, part_type, sn, sn))

                        # Update master stock
                        if resolved_status == 'Tersedia':
                            cur.execute("""
                                UPDATE master_stocks 
                                SET status = 'Tersedia', allocated_tray_id = NULL, allocated_at = NULL, used_at = NULL,
                                    last_reason = NULL, is_replaced_from_assy = 0, last_tray_id = NULL, last_operator = NULL
                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                            """, (part_type, sn, sn))
                            cur.execute("""
                                UPDATE parts 
                                SET status = 'Tersedia' 
                                WHERE sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')
                            """, (sn, sn))
                        else:
                            cur.execute("""
                                UPDATE master_stocks 
                                SET status = ? 
                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                            """, (resolved_status, part_type, sn, sn))

                        # Parts are restored to master_stocks as 'Tersedia' (declined assembly stays declined)
                        cur.execute("""
                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                            VALUES (?, ?, 'Declined/Scrap', ?, 'ADMIN_APPROVE_REREGISTER', ?, ?, ?, datetime('now', 'localtime'))
                        """, (part_type, sn, resolved_status, user['username'], user['role'], f"Verifikasi Admin ({resolved_status}): {notes or 'Disetujui'}"))

                        conn.commit()
                        return send_json(self, {"ok": True, "message": f"P9 Part {part_type} SN {sn} berhasil diverifikasi & dipulihkan menjadi status '{resolved_status}'!"})

                    elif path == "/api/admin/approve-replacement":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_replacement', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        
                        replacement_id = body.get('replacement_id')
                        assembly_id = body.get('assembly_id')
                        notes = (body.get('notes') or '').strip()

                        if not replacement_id and not assembly_id:
                            return send_json(self, {"ok": False, "error": "replacement_id atau assembly_id diperlukan"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if replacement_id:
                                    cur.execute("SELECT * FROM part_replacement_requests WHERE id = ? AND status = 'PENDING'", (replacement_id,))
                                else:
                                    cur.execute("SELECT * FROM part_replacement_requests WHERE assembly_id = ? AND status = 'PENDING' ORDER BY id DESC LIMIT 1", (assembly_id,))
                                rep_req = cur.fetchone()
                                if not rep_req:
                                    return send_json(self, {"ok": False, "error": "Permohonan pergantian part tidak ditemukan atau sudah diverifikasi"}, 404)

                                target_assy_id = rep_req['assembly_id']
                                cur.execute("SELECT a.*, t.tray_code FROM assemblies a JOIN trays t ON a.tray_id = t.id WHERE a.id = ?", (target_assy_id,))
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                                replacements = json.loads(rep_req['replacements_json'])
                                old_part_status = rep_req['old_part_status']
                                reason = rep_req['reason'] or 'Penggantian part disetujui Admin'
                                req_user = rep_req['requestor_username']

                                curr_def = [p.strip() for p in (assy.get('defective_parts') or '').split(',') if p.strip()]
                                replaced_summary = []

                                for rep in replacements:
                                    p_type = str(rep.get('part_type') or '').strip()
                                    n_sn = str(rep.get('new_sn') or '').strip()
                                    old_sn = str(rep.get('old_sn') or '').strip()
                                    if not old_sn or old_sn == '-':
                                        if p_type == '15':
                                            old_sn = assy['part15_sn']
                                        elif p_type == '1':
                                            old_sn = assy['part1_sn']
                                        elif p_type == '12':
                                            old_sn = assy['part12_sn']

                                    # Verify new stock
                                    cur.execute("""
                                        SELECT id, sn, part_type, status FROM master_stocks 
                                        WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                    """, (p_type, n_sn, n_sn))
                                    new_stock = cur.fetchone()
                                    if not new_stock:
                                        return send_json(self, {"ok": False, "error": f"Part baru {p_type} SN {n_sn} tidak ditemukan di Master Stok!"}, 400)

                                    if p_type == '15':
                                        cur.execute("UPDATE assemblies SET part15_sn = ? WHERE id = ?", (new_stock['sn'], target_assy_id))
                                    elif p_type == '1':
                                        cur.execute("UPDATE assemblies SET part1_sn = ? WHERE id = ?", (new_stock['sn'], target_assy_id))
                                    elif p_type == '12':
                                        cur.execute("UPDATE assemblies SET part12_sn = ? WHERE id = ?", (new_stock['sn'], target_assy_id))

                                    # Update parts table for this tray (synchronizing WO documents)
                                    cur.execute("UPDATE parts SET sn = ? WHERE tray_id = ? AND part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (new_stock['sn'], assy['tray_id'], p_type, old_sn, old_sn))

                                    cur.execute("""
                                        UPDATE master_stocks 
                                        SET status = 'Used', allocated_tray_id = ?, used_at = datetime('now', 'localtime') 
                                        WHERE id = ?
                                    """, (assy['tray_id'], new_stock['id']))

                                    if old_sn and old_sn != '-':
                                        cur.execute("""
                                            UPDATE master_stocks 
                                            SET status = ?, allocated_tray_id = NULL, used_at = NULL,
                                                last_reason = ?,
                                                last_tray_id = ?,
                                                last_operator = ?,
                                                is_replaced_from_assy = 1
                                            WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                        """, (old_part_status, reason, assy['tray_id'], req_user, p_type, old_sn, old_sn))

                                        cur.execute("""
                                            DELETE FROM re_register_requests 
                                            WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status = 'PENDING'
                                        """, (p_type, old_sn, old_sn))

                                        cur.execute("""
                                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                            VALUES (?, ?, ?, 'Used', 'REPLACE_PART_INSTALLED', ?, ?, ?, datetime('now', 'localtime'))
                                        """, (p_type, new_stock['sn'], new_stock['status'], user['username'], user['role'], f"Disetujui Admin: Menggantikan SN {old_sn} pada Set #{assy['set_number']} WO {assy['tray_code']}"))

                                        cur.execute("""
                                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                            VALUES (?, ?, 'Used', ?, 'REPLACE_PART_REMOVED', ?, ?, ?, datetime('now', 'localtime'))
                                        """, (p_type, old_sn, old_part_status, user['username'], user['role'], f"Disetujui Admin: Dilepas dari Set #{assy['set_number']} WO {assy['tray_code']}: {reason}"))

                                    if p_type in curr_def:
                                        curr_def.remove(p_type)
                                    replaced_summary.append(f"Part {p_type} ({old_sn} -> {new_stock['sn']})")

                                new_def_str = ",".join(curr_def) if curr_def else None
                                replacement_note = "Ganti Part: " + ", ".join(replaced_summary)
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET defective_parts = ?,
                                        original_assembly_status = COALESCE(original_assembly_status, status),
                                        status = 'Dirakit Ulang',
                                        assy_test_status = CASE 
                                            WHEN assy_test_status IS NOT NULL AND assy_test_status NOT IN ('Pending', 'PENDING') THEN 'RU'
                                            ELSE assy_test_status
                                        END,
                                        qc_status = CASE 
                                            WHEN qc_status IS NOT NULL AND qc_status NOT IN ('PENDING_QC', 'PENDING') THEN 'RU'
                                            ELSE qc_status
                                        END,
                                        verified_status = 'WAITING_REASSEMBLY',
                                        rework_reason = CASE 
                                            WHEN rework_reason IS NULL OR rework_reason = '' THEN ?
                                            ELSE rework_reason || ' | ' || ?
                                        END
                                    WHERE id = ?
                                """, (new_def_str, replacement_note, replacement_note, target_assy_id))

                                # Delete any pending re_register_requests for all parts of this assembly
                                cur.execute("""
                                    DELETE FROM re_register_requests 
                                    WHERE sn IN (
                                        SELECT part15_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part1_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part12_sn FROM assemblies WHERE id = ?
                                    ) AND status = 'PENDING'
                                """, (target_assy_id, target_assy_id, target_assy_id))

                                # Ensure unreplaced parts remain 'Used' in master_stocks allocated to this tray
                                cur.execute("""
                                    UPDATE master_stocks 
                                    SET status = 'Used', allocated_tray_id = ?, last_reason = NULL
                                    WHERE sn IN (
                                        SELECT part15_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part1_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part12_sn FROM assemblies WHERE id = ?
                                    ) AND status = 'Pending Verif Admin'
                                """, (assy['tray_id'], target_assy_id, target_assy_id, target_assy_id))

                                cur.execute("""
                                    UPDATE part_replacement_requests 
                                    SET status = 'APPROVED',
                                        admin_username = ?,
                                        admin_notes = ?,
                                        verified_at = datetime('now', 'localtime')
                                    WHERE id = ?
                                """, (user['username'], notes or 'Disetujui Admin', rep_req['id']))

                                cur.execute("""
                                    UPDATE re_register_requests
                                    SET status = 'APPROVED',
                                        admin_username = ?,
                                        admin_notes = ?,
                                        approved_at = datetime('now', 'localtime')
                                    WHERE sn IN (
                                        SELECT part15_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part1_sn FROM assemblies WHERE id = ?
                                        UNION SELECT part12_sn FROM assemblies WHERE id = ?
                                    ) AND status = 'PENDING'
                                """, (user['username'], notes or 'Disetujui Admin Ganti Part', target_assy_id, target_assy_id, target_assy_id))

                                conn.commit()

                        msg = f"Pergantian part Set #{assy['set_number']} berhasil disetujui: {', '.join(replaced_summary)}. Unit otomatis masuk ke antrean Perakitan Ulang."
                        return send_json(self, {"ok": True, "message": msg})

                    elif path == "/api/admin/reject-replacement":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_replacement', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        
                        replacement_id = body.get('replacement_id')
                        assembly_id = body.get('assembly_id')
                        notes = (body.get('notes') or '').strip() or 'Ditolak oleh Administrator'

                        if not replacement_id and not assembly_id:
                            return send_json(self, {"ok": False, "error": "replacement_id atau assembly_id diperlukan"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if replacement_id:
                                    cur.execute("SELECT * FROM part_replacement_requests WHERE id = ? AND status = 'PENDING'", (replacement_id,))
                                else:
                                    cur.execute("SELECT * FROM part_replacement_requests WHERE assembly_id = ? AND status = 'PENDING' ORDER BY id DESC LIMIT 1", (assembly_id,))
                                rep_req = cur.fetchone()
                                if not rep_req:
                                    return send_json(self, {"ok": False, "error": "Permohonan pergantian part tidak ditemukan atau sudah diverifikasi"}, 404)

                                target_assy_id = rep_req['assembly_id']
                                replacements = json.loads(rep_req['replacements_json'])

                                for rep in replacements:
                                    p_type = str(rep.get('part_type') or '').strip()
                                    n_sn = str(rep.get('new_sn') or '').strip()

                                    cur.execute("""
                                        UPDATE master_stocks 
                                        SET status = 'Tersedia', allocated_tray_id = NULL,
                                            last_reason = ?
                                        WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                    """, (f"Pengajuan ganti part ditolak Admin: {notes}", p_type, n_sn, n_sn))

                                    cur.execute("""
                                        INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                        VALUES (?, ?, 'Pending Verif Admin', 'Tersedia', 'REPLACE_PART_REJECTED', ?, ?, ?, datetime('now', 'localtime'))
                                    """, (p_type, n_sn, user['username'], user['role'], f"Pengajuan ganti part ditolak Admin: {notes}"))

                                cur.execute("""
                                    UPDATE assemblies 
                                    SET verified_status = NULL
                                    WHERE id = ? AND verified_status = 'PENDING_REPLACEMENT'
                                """, (target_assy_id,))

                                cur.execute("""
                                    UPDATE part_replacement_requests 
                                    SET status = 'REJECTED',
                                        admin_username = ?,
                                        admin_notes = ?,
                                        verified_at = datetime('now', 'localtime')
                                    WHERE id = ?
                                """, (user['username'], notes, rep_req['id']))

                                conn.commit()

                        return send_json(self, {"ok": True, "message": f"Pengajuan pergantian part telah ditolak. Stok part baru dikembalikan ke status Tersedia."})

                    elif path == "/api/admin/approve-hold-item":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_set', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator / Verifikator (akses tulis)"}, 403)
                        
                        assembly_id = body.get('assembly_id')
                        raw_dec = str(body.get('decision') or body.get('action') or 'APPROVE_GOOD').strip()
                        if raw_dec.upper() in ['APPROVE', 'GOOD', 'APPROVE_GOOD']:
                            decision = 'APPROVE_GOOD'
                        elif raw_dec.upper() in ['SET_REWORK', 'REWORK']:
                            decision = 'REWORK'
                        else:
                            decision = raw_dec
                        notes = (body.get('notes') or '').strip()

                        if not assembly_id:
                            return send_json(self, {"ok": False, "error": "assembly_id diperlukan!"}, 400)

                        cur.execute("SELECT * FROM assemblies WHERE id = ?", (assembly_id,))
                        assy = cur.fetchone()
                        if not assy:
                            return send_json(self, {"ok": False, "error": "Data rakitan tidak ditemukan"}, 404)

                        sn_list = [s for s in [assy['part15_sn'], assy['part1_sn'], assy['part12_sn']] if s and s != '-']

                        if decision == 'APPROVE_GOOD':
                            prev_st = str(assy.get('status') or '')
                            prev_life = str(assy.get('lifecycle_path') or '')
                            new_lifecycle = 'HOLD_REWORK_APPROVED' if (prev_st == 'Rework' or prev_life == 'REWORK' or 'REWORK' in prev_life) else 'HOLD_APPROVED'

                            # Auto-reset seluruh 15 parameter test menjadi GOOD jika didaftarkan ulang & disetujui GOOD
                            good_15 = json.dumps([{"test_num": i, "name": f"Test Step {i}", "status": "GOOD"} for i in range(1, 16)])

                            new_assy_hist = append_status_history(assy.get('assy_status_history') or 'Hold', 'Good')
                            cur.execute("""
                                UPDATE assemblies 
                                SET status = 'Good', 
                                    qc_status = 'PENDING_QC', 
                                    verified_status = 'APPROVED', 
                                    lifecycle_path = ?,
                                    is_declined = FALSE, 
                                    is_reregistered = FALSE,
                                    reason = NULL,
                                    assy_test_checklist = ?,
                                    assy_status_history = ?,
                                    assy_test_status = 'Good',
                                    assy_failure_reasons = NULL
                                WHERE id = ?
                            """, (new_lifecycle, good_15, new_assy_hist, assembly_id))

                            # Fetch tray details for history & doc generation
                            cur.execute("SELECT tray_code FROM trays WHERE id = ?", (assy['tray_id'],))
                            tr_row = cur.fetchone()
                            tray_code_val = tr_row['tray_code'] if tr_row else ''
                            assy_doc = get_tray_doc_number(tray_code_val, 'ASSY')
                            if assy_doc:
                                cur.execute("UPDATE assemblies SET assy_doc_number = ? WHERE id = ?", (assy_doc, assembly_id))

                            # 1. Record historical Dokumen WO event into assembly_wo_history (Hold -> Good)
                            cur.execute("""
                                INSERT INTO assembly_wo_history (
                                    tray_id, tray_code, set_number, pic_assembly,
                                    part15_sn, part12_sn, part1_sn,
                                    status, reason, defective_parts,
                                    started_at, finished_at, source, created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Good', NULL, NULL, ?, datetime('now', 'localtime'), 'ADMIN_APPROVAL', datetime('now', 'localtime'))
                            """, (
                                assy['tray_id'], tray_code_val, assy['set_number'], assy['pic_assembly'],
                                assy['part15_sn'], assy['part12_sn'], assy['part1_sn'],
                                assy.get('started_at') or assy.get('finished_at')
                            ))

                            # Fetch original tester who performed the assembly test
                            cur.execute("""
                                SELECT tester_username, tester_role 
                                FROM assembly_tests 
                                WHERE assembly_id = ? AND stage = 'ASSY' AND (tester_role != 'admin' OR tester_username != ?)
                                ORDER BY id DESC LIMIT 1
                            """, (assembly_id, user['username']))
                            prev_test = cur.fetchone()
                            orig_tester = prev_test['tester_username'] if prev_test else (assy.get('pic_assembly') or user['username'])
                            orig_role = prev_test['tester_role'] if prev_test else 'assembly'

                            # 2. Record new Good test event into assembly_tests (allows printing new certificate)
                            cur.execute("""
                                INSERT INTO assembly_tests (
                                    stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                                    p9_sn, part15_sn, part12_sn, part1_sn,
                                    tester_username, tester_role, test_checklist,
                                    status, failure_reasons, notes, lifecycle_path, created_at,
                                    admin_approved_at, admin_approved_by, admin_action
                                ) VALUES (
                                    'ASSY', ?, ?, ?, ?, ?,
                                    ?, ?, ?, ?,
                                    ?, ?, ?,
                                    'Good', NULL, ?, ?, datetime('now', 'localtime'),
                                    datetime('now', 'localtime'), ?, 'APPROVE_GOOD'
                                )
                            """, (
                                assy_doc or '-', assy['tray_id'], tray_code_val, assy['set_number'], assembly_id,
                                assy['part15_sn'], assy['part15_sn'], assy['part12_sn'], assy['part1_sn'],
                                orig_tester, orig_role, good_15,
                                f"Disetujui Good oleh Admin: {notes or 'ACC'}", new_lifecycle,
                                user['username']
                            ))

                            for sn in sn_list:
                                cur.execute("UPDATE master_stocks SET status = 'Good' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                cur.execute("UPDATE parts SET status = 'Good' WHERE sn = ? AND tray_id = ?", (sn, assy['tray_id']))
                                cur.execute("UPDATE re_register_requests SET status = 'APPROVED', admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') WHERE sn = ? AND status = 'PENDING'", (user['username'], notes or 'Disetujui Admin Lanjut QC', sn))
                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('PART', ?, 'Hold', 'Good', 'ADMIN_APPROVE_HOLD', ?, ?, ?, datetime('now', 'localtime'))
                                """, (sn, user['username'], user['role'], f"Disetujui Good (Lanjut QC) oleh Admin: {notes or 'ACC'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Barang rakitan Set #{assy['set_number']} berhasil disetujui sebagai Good dan langsung diteruskan ke Quality Control (QC)."})

                        elif decision == 'REWORK':
                            new_assy_hist = append_status_history(assy.get('assy_status_history') or 'Hold', 'Rework')
                            cur.execute("""
                                UPDATE assemblies 
                                SET status = 'Rework', 
                                    verified_status = 'REWORK_GUDANG', 
                                    lifecycle_path = 'REWORK',
                                    is_declined = FALSE, 
                                    is_reregistered = FALSE,
                                    assy_status_history = ?
                                WHERE id = ?
                            """, (new_assy_hist, assembly_id))
                            for sn in sn_list:
                                cur.execute("UPDATE master_stocks SET status = 'Rework' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                cur.execute("UPDATE parts SET status = 'Rework' WHERE sn = ? AND tray_id = ?", (sn, assy['tray_id']))
                                cur.execute("UPDATE re_register_requests SET status = 'APPROVED', admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') WHERE sn = ? AND status = 'PENDING'", (user['username'], notes or 'Dialihkan ke Rework oleh Admin', sn))
                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('PART', ?, 'Hold', 'Rework', 'ADMIN_REWORK', ?, ?, ?, datetime('now', 'localtime'))
                                """, (sn, user['username'], user['role'], f"Dialihkan ke Rework oleh Admin: {notes or 'Perlu Rework'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Barang rakitan Set #{assy['set_number']} dialihkan ke status Rework dan dikembalikan ke Gudang."})

                        elif decision == 'APPROVE_GOOD_QC':
                            prev_qc = str(assy.get('qc_status') or '')
                            prev_life = str(assy.get('lifecycle_path') or '')
                            new_lifecycle = 'HOLD_QC_REWORK_APPROVED' if ('REWORK' in prev_qc or 'REWORK' in prev_life) else 'HOLD_QC_APPROVED'

                            # Auto-reset seluruh 15 parameter test menjadi GOOD jika disetujui Good QC
                            good_15 = json.dumps([{"test_num": i, "name": f"Test Step {i}", "status": "GOOD"} for i in range(1, 16)])

                            # Fetch tray details for history & doc generation
                            cur.execute("SELECT tray_code FROM trays WHERE id = ?", (assy['tray_id'],))
                            tr_row = cur.fetchone()
                            tray_code_val = tr_row['tray_code'] if tr_row else ''
                            qc_doc = get_tray_doc_number(tray_code_val, 'QC')

                            new_qc_hist = append_status_history(assy.get('qc_status_history') or 'Hold QC', 'Good QC')
                            cur.execute("""
                                UPDATE assemblies 
                                SET qc_status = 'GOOD', 
                                    status = 'Good',
                                    assy_test_status = 'Good',
                                    qc_doc_number = COALESCE(?, qc_doc_number),
                                    verified_status = 'APPROVED_QC', 
                                    warehouse_receipt_status = 'PENDING',
                                    lifecycle_path = ?,
                                    is_declined = FALSE, 
                                    is_reregistered = FALSE,
                                    qc_failure_reasons = NULL,
                                    assy_test_checklist = ?,
                                    qc_status_history = ?
                                WHERE id = ?
                            """, (qc_doc, new_lifecycle, good_15, new_qc_hist, assembly_id))

                            # Fetch original tester who performed the QC test
                            cur.execute("""
                                SELECT tester_username, tester_role 
                                FROM assembly_tests 
                                WHERE assembly_id = ? AND stage = 'QC' AND (tester_role != 'admin' OR tester_username != ?)
                                ORDER BY id DESC LIMIT 1
                            """, (assembly_id, user['username']))
                            prev_qc = cur.fetchone()
                            orig_qc_tester = prev_qc['tester_username'] if prev_qc else (assy.get('qc_tested_by') or user['username'])
                            orig_qc_role = prev_qc['tester_role'] if prev_qc else (user['role'] if prev_qc is None else 'qc')

                            # Record new Good test event into assembly_tests for QC
                            cur.execute("""
                                INSERT INTO assembly_tests (
                                    stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                                    p9_sn, part15_sn, part12_sn, part1_sn,
                                    tester_username, tester_role, test_checklist,
                                    status, failure_reasons, notes, lifecycle_path, created_at,
                                    admin_approved_at, admin_approved_by, admin_action
                                ) VALUES (
                                    'QC', ?, ?, ?, ?, ?,
                                    ?, ?, ?, ?,
                                    ?, ?, ?,
                                    'Good', NULL, ?, ?, datetime('now', 'localtime'),
                                    datetime('now', 'localtime'), ?, 'APPROVE_GOOD_QC'
                                )
                            """, (
                                qc_doc or '-', assy['tray_id'], tray_code_val, assy['set_number'], assembly_id,
                                assy['part15_sn'], assy['part15_sn'], assy['part12_sn'], assy['part1_sn'],
                                orig_qc_tester, orig_qc_role, good_15,
                                f"Disetujui Good QC oleh Admin: {notes or 'ACC QC'}", new_lifecycle,
                                user['username']
                            ))
                            for sn in sn_list:
                                cur.execute("UPDATE master_stocks SET status = 'Good' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                cur.execute("UPDATE parts SET status = 'Good' WHERE sn = ? AND tray_id = ?", (sn, assy['tray_id']))
                                cur.execute("UPDATE re_register_requests SET status = 'APPROVED', admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') WHERE sn = ? AND status = 'PENDING'", (user['username'], notes or 'Disetujui Good QC oleh Admin', sn))
                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('PART', ?, 'Hold', 'Good', 'ADMIN_APPROVE_QC_HOLD', ?, ?, ?, datetime('now', 'localtime'))
                                """, (sn, user['username'], user['role'], f"Disetujui Good (Lanjut Dokumen/Gudang) oleh Admin: {notes or 'ACC QC'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Barang rakitan Set #{assy['set_number']} berhasil disetujui Good dan diteruskan ke Verifikasi Dokumen & Penerimaan Gudang."})

                        elif decision == 'REWORK_QC':
                            new_qc_hist = append_status_history(assy.get('qc_status_history') or 'Hold QC', 'Rework QC')
                            cur.execute("""
                                UPDATE assemblies 
                                SET qc_status = 'REWORK_QC', 
                                    verified_status = 'REWORK_QC_GUDANG', 
                                    lifecycle_path = 'REWORK_QC',
                                    is_declined = FALSE, 
                                    is_reregistered = FALSE,
                                    qc_status_history = ?
                                WHERE id = ?
                            """, (new_qc_hist, assembly_id))
                            for sn in sn_list:
                                cur.execute("UPDATE master_stocks SET status = 'Rework' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                cur.execute("UPDATE parts SET status = 'Rework' WHERE sn = ? AND tray_id = ?", (sn, assy['tray_id']))
                                cur.execute("UPDATE re_register_requests SET status = 'APPROVED', admin_username = ?, admin_notes = ?, approved_at = datetime('now', 'localtime') WHERE sn = ? AND status = 'PENDING'", (user['username'], notes or 'Dialihkan ke Rework QC oleh Admin', sn))
                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('PART', ?, 'Hold', 'Rework', 'ADMIN_REWORK_QC', ?, ?, ?, datetime('now', 'localtime'))
                                """, (sn, user['username'], user['role'], f"Dialihkan ke Rework QC oleh Admin: {notes or 'Perlu Rework QC'}"))

                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Barang rakitan Set #{assy['set_number']} dialihkan ke status Rework QC dan dikembalikan ke Gudang."})

                        return send_json(self, {"ok": False, "error": f"Keputusan tidak valid: {decision}"}, 400)

                    elif path == "/api/admin/approve-assy-rereg":
                        if not (is_admin_user(user) or user_has_perm(user, 'verify_set', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator / Verifikator (akses tulis)"}, 403)
                        # Approve or reject a Daftar Ulang Perakitan request
                        # On approval: creates new tray with -PU{n} suffix and new assembly record
                        assembly_id = body.get('assembly_id')
                        action = str(body.get('action') or 'APPROVE').upper()  # APPROVE or REJECT
                        approved_operator = str(body.get('approved_operator') or '').strip()
                        admin_notes = str(body.get('notes') or '').strip()

                        if not assembly_id:
                            return send_json(self, {"ok": False, "error": "assembly_id wajib diisi"}, 400)
                        if action not in ['APPROVE', 'REJECT']:
                            return send_json(self, {"ok": False, "error": "action harus APPROVE atau REJECT"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT a.*, t.tray_code, t.source_document_mo, t.product_type FROM assemblies a JOIN trays t ON a.tray_id = t.id WHERE a.id = ?", (assembly_id,))
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                                if assy.get('verified_status') not in ['PENDING_ADMIN_ASSY_REREG', 'PENDING_ADMIN']:
                                    return send_json(self, {"ok": False, "error": "Status tidak valid untuk disetujui"}, 400)

                                # Resolve final_operator through users table if possible
                                candidate_op = approved_operator or assy.get('rereg_pending_operator') or assy.get('pic_assembly')
                                cur.execute("SELECT username, full_name FROM users WHERE username = ? OR full_name = ? COLLATE NOCASE", (candidate_op, candidate_op))
                                u_match = cur.fetchone()
                                if u_match:
                                    final_operator = u_match['full_name'] or u_match['username']
                                else:
                                    final_operator = candidate_op

                                tray_code = assy['tray_code']

                                if action == 'REJECT':
                                    # Reject: restore assembly status to previous state
                                    cur.execute("""
                                        UPDATE assemblies 
                                        SET verified_status = NULL,
                                            is_reregistered = FALSE,
                                            rereg_pending_operator = NULL,
                                            reason = COALESCE(reason, '') || ' | Ditolak Admin: ' || ?
                                        WHERE id = ?
                                    """, (admin_notes or 'Ditolak oleh Admin', assembly_id))
                                    cur.execute("""
                                        UPDATE re_register_requests
                                        SET status = 'REJECTED',
                                            admin_username = ?,
                                            admin_notes = ?
                                        WHERE sn IN (?, ?, ?) AND status = 'PENDING'
                                    """, (user['username'], admin_notes or 'Ditolak oleh Admin', assy['part15_sn'], assy['part1_sn'], assy['part12_sn']))
                                    conn.commit()
                                    return send_json(self, {"ok": True, "message": "Pengajuan daftar ulang ditolak oleh Admin."})

                                # APPROVE flow:
                                # Sesuai instruksi user: Saat admin approve, set masuk ke menu "Perakitan Ulang (RU)"
                                # untuk dipilih set berapa saja kemudian ditujukan ke operator mana, baru masuk ke perakitan biasa.
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET verified_status = 'WAITING_REASSEMBLY',
                                        status = 'Dirakit Ulang',
                                        is_reregistered = TRUE,
                                        rereg_pending_operator = NULL,
                                        reason = COALESCE(reason, '') || ' | Disetujui Admin untuk Perakitan Ulang: ' || ?
                                    WHERE id = ?
                                """, (admin_notes or 'Disetujui Admin untuk Perakitan Ulang', assembly_id))

                                cur.execute("""
                                    UPDATE re_register_requests
                                    SET status = 'APPROVED',
                                        admin_username = ?,
                                        admin_notes = ?,
                                        approved_at = datetime('now', 'localtime')
                                    WHERE sn IN (?, ?, ?) AND status = 'PENDING'
                                """, (user['username'], admin_notes or 'Disetujui Admin', assy['part15_sn'], assy['part1_sn'], assy['part12_sn']))

                                cur.execute("""
                                    UPDATE master_stocks 
                                    SET status = 'Used'
                                    WHERE sn IN (?, ?, ?) AND status = 'Pending Verif Admin'
                                """, (assy['part15_sn'], assy['part1_sn'], assy['part12_sn']))

                                cur.execute("""
                                    INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                    VALUES ('ALL', ?, 'PENDING_ADMIN_ASSY_REREG', 'WAITING_REASSEMBLY', 'APPROVE_ASSY_REREG', ?, 'admin', ?, datetime('now', 'localtime'))
                                """, (f"Tray#{assy['tray_id']}_Set#{assy['set_number']}", user['username'], f"Disetujui Admin untuk masuk ke Perakitan Ulang (RU). {admin_notes}".strip()))

                                conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "message": f"Perakitan ulang Set #{assy['set_number']} disetujui Admin dan berhasil dialihkan ke antrean Perakitan Ulang (RU)."
                        })

                    elif path == "/api/assembly/dispatch-reassembly":
                        if not user_has_perm(user, 'reassembly', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Perakitan Ulang"}, 403)
                        # Dispatch sets from Perakitan Ulang menu into new PU Tray(s)
                        assembly_ids = body.get('assembly_ids')
                        assigned_operator = str(body.get('assigned_operator') or body.get('operator_name') or '').strip()

                        if not assembly_ids or not isinstance(assembly_ids, list):
                            return send_json(self, {"ok": False, "error": "assembly_ids wajib berupa list ID set"}, 400)
                        if not assigned_operator:
                            return send_json(self, {"ok": False, "error": "Operator assembly wajib dipilih"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                # Resolve final_operator through users table if possible
                                cur.execute("SELECT username, full_name FROM users WHERE username = ? OR full_name = ? COLLATE NOCASE", (assigned_operator, assigned_operator))
                                u_match = cur.fetchone()
                                final_operator = (u_match['full_name'] or u_match['username']) if u_match else assigned_operator

                                placeholders = ','.join(['?'] * len(assembly_ids))
                                cur.execute(f"""
                                    SELECT a.*, t.tray_code, t.source_document_mo, t.product_type, t.pic_warehouse 
                                    FROM assemblies a 
                                    JOIN trays t ON a.tray_id = t.id 
                                    WHERE a.id IN ({placeholders}) AND (a.verified_status IN ('WAITING_REASSEMBLY', 'HOLD_GUDANG') OR a.status IN ('Dirakit Ulang', 'RU'))
                                """, assembly_ids)
                                selected_assys = cur.fetchall()

                                if not selected_assys:
                                    return send_json(self, {"ok": False, "error": "Tidak ada set valid berstatus Menunggu Perakitan Ulang yang ditemukan"}, 400)

                                # Group by base tray
                                tray_groups = {}
                                for a_row in selected_assys:
                                    tid = a_row['tray_id']
                                    if tid not in tray_groups:
                                        tray_groups[tid] = []
                                    tray_groups[tid].append(a_row)

                                created_trays = []

                                for orig_tray_id, assy_list in tray_groups.items():
                                    first_assy = dict(assy_list[0])
                                    orig_tray_code = first_assy['tray_code']
                                    base_tray_code = re.sub(r'-PU\d+$', '', orig_tray_code, flags=re.IGNORECASE)

                                    # Calculate next global PU sequential number
                                    cur.execute("SELECT tray_code FROM trays WHERE tray_code LIKE 'WH/ASSY-PU/%' OR tray_code LIKE '%-PU%'")
                                    all_m = cur.fetchall()
                                    max_pu = 0
                                    for m in all_m:
                                        tc = m['tray_code']
                                        m_pu_std = re.search(r'WH/ASSY-PU/(\d+)', tc, re.IGNORECASE)
                                        if m_pu_std:
                                            max_pu = max(max_pu, int(m_pu_std.group(1)))
                                        else:
                                            m_pu_old = re.search(r'-PU(\d+)$', tc, re.IGNORECASE)
                                            if m_pu_old:
                                                max_pu = max(max_pu, int(m_pu_old.group(1)))
                                    next_pu = max_pu + 1
                                    pu_seq = f"{next_pu:06d}"
                                    pu_suffix = f"-PU{next_pu}"
                                    new_tray_code = f"WH/ASSY-PU/{pu_seq}"
                                    new_source_mo = f"WH/MO/PU/{pu_seq}"
                                    while True:
                                        cur.execute("SELECT 1 FROM trays WHERE tray_code = ?", (new_tray_code,))
                                        if not cur.fetchone():
                                            break
                                        next_pu += 1
                                        pu_seq = f"{next_pu:06d}"
                                        pu_suffix = f"-PU{next_pu}"
                                        new_tray_code = f"WH/ASSY-PU/{pu_seq}"
                                        new_source_mo = f"WH/MO/PU/{pu_seq}"

                                    # 1. Create new tray
                                    cur.execute("""
                                        INSERT INTO trays (tray_code, pic_warehouse, assigned_operator, source_document_mo, product_type, status, session_status, verified_by_gudang, verified_at, verified_by_user, created_at)
                                        VALUES (?, ?, ?, ?, ?, 'active', 'active', 1, datetime('now', 'localtime'), ?, datetime('now', 'localtime'))
                                    """, (
                                        new_tray_code,
                                        first_assy.get('pic_warehouse') or user['username'],
                                        final_operator,
                                        new_source_mo,
                                        first_assy.get('product_type') or 'Nanggala P9',
                                        user['username']
                                    ))
                                    new_tray_id = cur.lastrowid
                                    created_trays.append(new_tray_code)

                                    # 2. Insert parts into `parts` as Supplied for new_tray_id
                                    for a_item in assy_list:
                                        for p_type, sn in [('15', a_item['part15_sn']), ('1', a_item['part1_sn']), ('12', a_item['part12_sn'])]:
                                            if sn and sn != '-':
                                                cur.execute("""
                                                    INSERT INTO parts (tray_id, part_type, sn, status)
                                                    VALUES (?, ?, ?, 'Supplied')
                                                    ON CONFLICT (tray_id, part_type, sn) DO UPDATE
                                                    SET status = 'Supplied'
                                                """, (new_tray_id, p_type, sn))
                                                cur.execute("""
                                                    UPDATE master_stocks 
                                                    SET status = 'Supplied', allocated_tray_id = ?, allocated_at = datetime('now', 'localtime')
                                                    WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                                """, (new_tray_id, p_type, sn, sn))

                                        # 3. Update the old assembly record:
                                        # Keep status as 'Dirakit Ulang' permanently as requested by user
                                        cur.execute("""
                                            UPDATE assemblies 
                                            SET verified_status = 'COMPLETED_PU',
                                                status = 'Dirakit Ulang',
                                                is_reregistered = TRUE,
                                                rereg_wo_suffix = ?,
                                                reason = COALESCE(reason, '') || ' | Dirakit Ulang ke ' || ? || ' oleh ' || ?
                                            WHERE id = ?
                                        """, (pu_suffix, new_tray_code, final_operator, a_item['id']))

                                        # Audit log
                                        cur.execute("""
                                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                            VALUES ('ALL', ?, 'WAITING_REASSEMBLY', 'COMPLETED_PU', 'DISPATCH_REASSEMBLY', ?, ?, ?, datetime('now', 'localtime'))
                                        """, (f"Set#{a_item['set_number']} ({first_assy['tray_code']})", user['username'], user['role'], f"Dikirim untuk dirakit ulang ke WO {new_tray_code} oleh operator {final_operator}"))

                                conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "created_trays": created_trays,
                            "message": f"Berhasil mengirim {len(selected_assys)} set ke operator {final_operator} dengan Work Order baru: {', '.join(created_trays)}!"
                        })

                    elif path == "/api/admin/delete-re-register":
                        if not is_admin_user(user):
                            return send_json(self, {"ok": False, "error": "Akses khusus Administrator"}, 403)
                        
                        req_id = body.get('request_id') or body.get('id')
                        sn = str(body.get('sn') or '').strip()
                        part_type = str(body.get('part_type') or '').strip()

                        if req_id:
                            cur.execute("SELECT * FROM re_register_requests WHERE id = ?", (req_id,))
                            req = cur.fetchone()
                        elif sn and part_type:
                            cur.execute("SELECT * FROM re_register_requests WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) ORDER BY id DESC LIMIT 1", (part_type, sn, sn))
                            req = cur.fetchone()
                        elif sn:
                            cur.execute("SELECT * FROM re_register_requests WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) ORDER BY id DESC LIMIT 1", (sn, sn))
                            req = cur.fetchone()
                        else:
                            return send_json(self, {"ok": False, "error": "request_id atau SN wajib disertakan"}, 400)

                        if req:
                            # Restore stock status if it was 'Pending Verif Admin'
                            cur.execute("SELECT id, status FROM master_stocks WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (req['part_type'], req['sn'], req['sn']))
                            stock = cur.fetchone()
                            if stock and stock['status'] == 'Pending Verif Admin':
                                restored_status = req['current_status'] if req['current_status'] and req['current_status'] != 'Pending Verif Admin' else 'Scrap'
                                cur.execute("UPDATE master_stocks SET status = ? WHERE id = ?", (restored_status, stock['id']))
                            
                            cur.execute("DELETE FROM re_register_requests WHERE id = ?", (req['id'],))
                            
                            cur.execute("""
                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                VALUES (?, ?, ?, ?, 'ADMIN_DELETE_REQUEST', ?, ?, 'Permohonan verifikasi dihapus oleh Admin', datetime('now', 'localtime'))
                            """, (req['part_type'], req['sn'], req['current_status'], req['current_status'], user['username'], user['role']))
                            
                            conn.commit()
                            return send_json(self, {"ok": True, "message": f"Part {req['part_type']} SN {req['sn']} berhasil dihapus dari daftar verifikasi."})
                        else:
                            if sn:
                                cur.execute("UPDATE master_stocks SET status = 'Scrap' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status = 'Pending Verif Admin'", (sn, sn))
                                cur.execute("DELETE FROM re_register_requests WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))", (sn, sn))
                                conn.commit()
                                return send_json(self, {"ok": True, "message": f"Part SN {sn} berhasil dihapus dari verifikasi."})
                            return send_json(self, {"ok": False, "error": "Data permohonan tidak ditemukan"}, 404)

                    elif path == "/api/admin/testing-points":
                        if not is_admin_user(user):
                            return send_json(self, {"ok": False, "error": "Akses khusus Super Admin"}, 403)
                        
                        test_num = body.get('test_num')
                        instruction = (body.get('instruction') or body.get('name') or '').strip()
                        output_desc = (body.get('output') or body.get('description') or '').strip()
                        category = (body.get('category') or 'Umum / Fungsional').strip()
                        raw_images = body.get('images') or []

                        if not test_num or not instruction or not output_desc:
                            return send_json(self, {"ok": False, "error": "Nomor test, instruksi kerja, dan output wajib diisi!"}, 400)

                        try:
                            test_num = int(test_num)
                        except Exception:
                            return send_json(self, {"ok": False, "error": "Nomor test harus berupa angka!"}, 400)

                        # Process multiple images
                        saved_images = []
                        for idx, img in enumerate(raw_images):
                            saved = save_uploaded_image(img, test_num, idx)
                            if saved:
                                saved_images.append(saved)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                # Check duplicate test_num
                                cur.execute("SELECT id FROM testing_points WHERE test_num = ?", (test_num,))
                                if cur.fetchone():
                                    return send_json(self, {"ok": False, "error": f"Nomor Test #{test_num} sudah ada. Gunakan nomor lain atau edit data yang ada."}, 409)

                                cur.execute("""
                                    INSERT INTO testing_points (test_num, category, instruction, output, images, created_at, updated_at)
                                    VALUES (?, ?, ?, ?, ?, datetime('now', 'localtime'), datetime('now', 'localtime'))
                                    RETURNING id, test_num, category, instruction, output, images, created_at
                                """, (test_num, category, instruction, output_desc, json.dumps(saved_images)))
                                rec = cur.fetchone()
                                conn.commit()

                        data = dict(rec)
                        if isinstance(data.get('images'), str):
                            try:
                                data['images'] = json.loads(data['images'])
                            except Exception:
                                data['images'] = []
                        return send_json(self, {"ok": True, "message": f"Testing point #{test_num} berhasil ditambahkan.", "data": data})

                    elif path == "/api/admin/testing-points/update":
                        if not is_admin_user(user):
                            return send_json(self, {"ok": False, "error": "Akses khusus Super Admin"}, 403)
                        
                        point_id = body.get('id')
                        test_num = body.get('test_num')
                        instruction = (body.get('instruction') or body.get('name') or '').strip()
                        output_desc = (body.get('output') or body.get('description') or '').strip()
                        category = (body.get('category') or 'Umum / Fungsional').strip()
                        raw_images = body.get('images') or []

                        if not instruction or not output_desc:
                            return send_json(self, {"ok": False, "error": "Instruksi kerja dan output wajib diisi!"}, 400)

                        saved_images = []
                        for idx, img in enumerate(raw_images):
                            saved = save_uploaded_image(img, test_num or point_id, idx)
                            if saved:
                                saved_images.append(saved)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if point_id:
                                    if test_num:
                                        try:
                                            test_num = int(test_num)
                                        except Exception:
                                            return send_json(self, {"ok": False, "error": "Nomor test harus berupa angka!"}, 400)
                                        
                                        # Check if another testing point already uses this test_num
                                        cur.execute("SELECT id FROM testing_points WHERE test_num = ? AND id != ?", (test_num, point_id))
                                        if cur.fetchone():
                                            return send_json(self, {"ok": False, "error": f"Nomor Test #{test_num} sudah digunakan oleh point pengujian lain. Gunakan nomor urut lain."}, 409)

                                        cur.execute("""
                                            UPDATE testing_points 
                                            SET test_num = ?, category = ?, instruction = ?, output = ?, images = ?, updated_at = datetime('now', 'localtime')
                                            WHERE id = ?
                                            RETURNING id, test_num, category, instruction, output, images, updated_at
                                        """, (test_num, category, instruction, output_desc, json.dumps(saved_images), point_id))
                                    else:
                                        cur.execute("""
                                            UPDATE testing_points 
                                            SET category = ?, instruction = ?, output = ?, images = ?, updated_at = datetime('now', 'localtime')
                                            WHERE id = ?
                                            RETURNING id, test_num, category, instruction, output, images, updated_at
                                        """, (category, instruction, output_desc, json.dumps(saved_images), point_id))
                                elif test_num:
                                    cur.execute("""
                                        UPDATE testing_points 
                                        SET category = ?, instruction = ?, output = ?, images = ?, updated_at = datetime('now', 'localtime')
                                        WHERE test_num = ?
                                        RETURNING id, test_num, category, instruction, output, images, updated_at
                                    """, (category, instruction, output_desc, json.dumps(saved_images), int(test_num)))
                                else:
                                    return send_json(self, {"ok": False, "error": "ID atau Nomor Test diperlukan untuk update"}, 400)
                                
                                updated = cur.fetchone()
                                conn.commit()

                        if not updated:
                            return send_json(self, {"ok": False, "error": "Data testing point tidak ditemukan"}, 404)
                        data = dict(updated)
                        if isinstance(data.get('images'), str):
                            try:
                                data['images'] = json.loads(data['images'])
                            except Exception:
                                data['images'] = []
                        return send_json(self, {"ok": True, "message": "Testing point berhasil diperbarui.", "data": data})

                    elif path == "/api/admin/testing-points/delete":
                        if not user_has_perm(user, 'testing_points', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses khusus Master Testing Point (Write)"}, 403)
                        
                        point_id = body.get('id')
                        test_num = body.get('test_num')
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if point_id:
                                    cur.execute("DELETE FROM testing_points WHERE id = ? RETURNING id, test_num", (point_id,))
                                elif test_num:
                                    cur.execute("DELETE FROM testing_points WHERE test_num = ? RETURNING id, test_num", (int(test_num),))
                                else:
                                    return send_json(self, {"ok": False, "error": "ID atau Nomor Test diperlukan"}, 400)
                                deleted = cur.fetchone()
                                conn.commit()

                        if not deleted:
                            return send_json(self, {"ok": False, "error": "Testing point tidak ditemukan"}, 404)
                        return send_json(self, {"ok": True, "message": f"Testing point #{deleted['test_num']} berhasil dihapus."})

                    elif path == "/api/assembly/submit-single-retest":
                        if not (user_has_perm(user, 'assy_testing', require_write=True) or user_has_perm(user, 'qc', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses ditolak"}, 403)
                        
                        assembly_id = body.get('assembly_id')
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')
                        test_num = body.get('test_num')
                        status = (body.get('status') or '').strip().upper()
                        notes = (body.get('notes') or '').strip()

                        if not test_num or status not in ['GOOD', 'NOT_GOOD']:
                            return send_json(self, {"ok": False, "error": "test_num dan status ('GOOD'/'NOT_GOOD') wajib disertakan!"}, 400)

                        try:
                            test_num = int(test_num)
                        except Exception:
                            return send_json(self, {"ok": False, "error": "test_num harus angka"}, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                if assembly_id:
                                    cur.execute("SELECT * FROM assemblies WHERE id = ?", (assembly_id,))
                                elif tray_id and set_number:
                                    cur.execute("SELECT * FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, set_number))
                                else:
                                    return send_json(self, {"ok": False, "error": "assembly_id atau tray_id + set_number diperlukan"}, 400)
                                
                                assy = cur.fetchone()
                                if not assy:
                                    return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                                if assy.get('status') != 'Good':
                                    return send_json(self, {"ok": False, "error": f"Set #{assy.get('set_number')} berstatus {assy.get('status')} di perakitan fisik. Hanya unit dengan status perakitan 'Good' yang dapat diuji pada Test Functional 1!"}, 400)

                                if (assy.get('assy_test_status') in ['Good', 'Hold'] or assy.get('qc_status') in ['HOLD_ASSY', 'PENDING_QC', 'GOOD', 'HOLD_QC']) and assy.get('verified_status') not in ['APPROVED', 'APPROVED_QC', 'ACC', 'REWORK_GUDANG']:
                                    return send_json(self, {"ok": False, "error": "Unit telah selesai diuji dan terkunci dari pengujian ulang!"}, 400)

                                assembly_id = assy['id']
                                tray_id = assy['tray_id']
                                set_number = assy['set_number']

                                # Fetch tray_code
                                cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                                t_row = cur.fetchone()
                                tray_code = t_row['tray_code'] if t_row else ''

                                # Load checklist
                                chk = assy.get('assy_test_checklist')
                                if isinstance(chk, str):
                                    try: chk = json.loads(chk)
                                    except Exception: chk = None
                                if not isinstance(chk, list) or len(chk) == 0:
                                    chk = [{"test_num": p["test_num"], "name": p["instruction"], "description": p["output"], "status": "GOOD"} for p in DEFAULT_15_TESTING_POINTS]

                                # Update the targeted test_num
                                found_target = False
                                for item in chk:
                                    if int(item.get('test_num', 0)) == test_num:
                                        item['status'] = status
                                        found_target = True
                                        break
                                if not found_target:
                                    chk.append({"test_num": test_num, "name": f"Test {test_num}", "status": status})

                                # Recalculate failed tests
                                failed_tests = []
                                for t in chk:
                                    st = str(t.get('status') or '').strip().upper()
                                    if st in ['NOT_GOOD', 'NOT GOOD', 'NG', 'FAIL']:
                                        t_n = t.get('test_num') or ''
                                        t_nm = t.get('name') or f"test {t_n}"
                                        failed_tests.append(f"gagal dalam test {t_n}" if t_n else f"gagal dalam {t_nm}")

                                overall_status = 'Good' if len(failed_tests) == 0 else 'Hold'
                                failure_reasons = ", ".join(failed_tests) if failed_tests else None

                                prev_hist = assy.get('assy_status_history') or ('Hold' if assy.get('status') == 'Hold' else 'Good')
                                new_assy_history = append_status_history(prev_hist, overall_status)

                                doc_number = assy.get('assy_doc_number') or get_tray_doc_number(tray_code, 'ASSY')
                                lifecycle_val = 'HOLD_REWORK_APPROVED' if ('REWORK' in str(assy.get('lifecycle_path') or '')) else ('HOLD_APPROVED' if overall_status == 'Good' and assy.get('status') == 'Hold' else (assy.get('lifecycle_path') or 'FRESH_GOOD'))

                                if overall_status == 'Good':
                                    cur.execute("""
                                        UPDATE assemblies 
                                        SET assy_doc_number = COALESCE(assy_doc_number, ?),
                                            assy_test_status = 'Good',
                                            assy_failure_reasons = NULL,
                                            qc_status = 'PENDING_QC',
                                            verified_status = 'ACC',
                                            lifecycle_path = ?,
                                            assy_test_checklist = ?,
                                            assy_status_history = ?,
                                            finished_at = COALESCE(finished_at, datetime('now', 'localtime'))
                                        WHERE id = ?
                                    """, (doc_number, lifecycle_val, json.dumps(chk), new_assy_history, assembly_id))
                                else:
                                    cur.execute("""
                                        UPDATE assemblies 
                                        SET assy_doc_number = COALESCE(assy_doc_number, ?),
                                            assy_test_status = 'Hold',
                                            assy_failure_reasons = ?,
                                            qc_status = 'HOLD_ASSY',
                                            verified_status = 'HOLD_GUDANG',
                                            lifecycle_path = ?,
                                            assy_test_checklist = ?,
                                            assy_status_history = ?,
                                            finished_at = COALESCE(finished_at, datetime('now', 'localtime'))
                                        WHERE id = ?
                                    """, (doc_number, failure_reasons, lifecycle_val, json.dumps(chk), new_assy_history, assembly_id))

                                cur_tester = user['username']
                                cur_role = user['role']
                                if cur_role == 'admin':
                                    cur.execute("SELECT assigned_operator FROM trays WHERE id = ?", (tray_id,))
                                    op_r = cur.fetchone()
                                    if op_r and op_r.get('assigned_operator'):
                                        cur_tester = op_r['assigned_operator']
                                        cur_role = 'assembly'

                                # Record into assembly_tests
                                cur.execute("""
                                    INSERT INTO assembly_tests (
                                        stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                                        p9_sn, part15_sn, part12_sn, part1_sn,
                                        tester_username, tester_role, test_checklist,
                                        status, failure_reasons, notes, lifecycle_path, status_history, created_at
                                    ) VALUES (
                                        'ASSY', ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?,
                                        ?, ?, ?,
                                        ?, ?, ?, ?, ?, datetime('now', 'localtime')
                                    ) RETURNING id, doc_number, created_at
                                """, (
                                    doc_number, tray_id, tray_code, set_number, assembly_id,
                                    assy['part15_sn'], assy['part15_sn'], assy['part12_sn'], assy['part1_sn'],
                                    cur_tester, cur_role, json.dumps(chk),
                                    overall_status, failure_reasons, f"Single Retest #{test_num}: {notes or status}", lifecycle_val, new_assy_history
                                ))
                                conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "test_num": test_num,
                            "test_status": status,
                            "overall_status": overall_status,
                            "failed_tests_count": len(failed_tests),
                            "assy_status_history": new_assy_history,
                            "message": f"Uji Ulang Test #{test_num} selesai (Status Unit: {overall_status})."
                        })

                    elif path == "/api/admin/delete-declined-set":
                        if not (user_has_perm(user, 'verify_set', require_write=True) or user_has_perm(user, 'verify_items', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Verifikasi Set / Items (Write)"}, 403)
                        
                        assembly_id = body.get('assembly_id')
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')

                        if not assembly_id and (not tray_id or set_number is None):
                            return send_json(self, {"ok": False, "error": "assembly_id atau tray_id + set_number diperlukan!"}, 400)

                        if assembly_id:
                            cur.execute("SELECT * FROM assemblies WHERE id = ?", (assembly_id,))
                        else:
                            cur.execute("SELECT * FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, set_number))
                        
                        target_assy = cur.fetchone()
                        if not target_assy:
                            return send_json(self, {"ok": False, "error": "Data rakitan tidak ditemukan"}, 404)

                        # Delete any pending re-register requests for these parts
                        part_mapping = [('15', target_assy['part15_sn']), ('12', target_assy['part12_sn']), ('1', target_assy['part1_sn'])]
                        for p_type, sn in part_mapping:
                            if sn and sn != '-':
                                cur.execute("DELETE FROM re_register_requests WHERE sn = ? AND status = 'PENDING'", (sn,))
                                cur.execute("UPDATE master_stocks SET status = 'Scrap' WHERE (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')) AND status IN ('Pending Verif Admin', 'Declined')", (sn, sn))
                        
                        cur.execute("UPDATE assemblies SET is_declined = FALSE, verified_status = 'REJECTED' WHERE id = ?", (target_assy['id'],))
                        conn.commit()
                        return send_json(self, {"ok": True, "message": f"Barang rakitan Set #{target_assy['set_number']} berhasil dihapus dari verifikasi Admin."})

                    elif path == "/api/assembly/submit-test":
                        if not user_has_perm(user, 'assy_testing', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin tulis Test Functional 1)"}, 403)
                        
                        tray_id = body.get('tray_id')
                        set_number = body.get('set_number')
                        assembly_id = body.get('assembly_id')
                        part15_sn = (body.get('part15_sn') or body.get('part15') or '').strip()
                        part12_sn = (body.get('part12_sn') or body.get('part12') or '').strip()
                        part1_sn = (body.get('part1_sn') or body.get('part1') or '').strip()
                        test_checklist = body.get('test_checklist') or []
                        notes = (body.get('notes') or '').strip()

                        # Determine failure reasons & overall status
                        failed_tests = []
                        for t in test_checklist:
                            st = str(t.get('status') or '').strip().upper()
                            if st in ['NOT_GOOD', 'NOT GOOD', 'NG', 'FAIL']:
                                t_num = t.get('test_num') or t.get('id') or ''
                                t_name = t.get('name') or f"test {t_num}"
                                failed_tests.append(f"gagal dalam test {t_num}" if t_num else f"gagal dalam {t_name}")
                        
                        # Fetch tray_code
                        cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                        t_row = cur.fetchone()
                        tray_code = t_row['tray_code'] if t_row else ''

                        doc_number = get_tray_doc_number(tray_code, 'ASSY')
                        pending_tests = [t for t in test_checklist if str(t.get('status') or '').strip().upper() in ['PENDING', '-', 'UNCHECKED']]
                        if pending_tests:
                            overall_status = 'Pending'
                            failure_reasons = (", ".join(failed_tests) + " (Sisa pengujian belum dicek / Pending)") if failed_tests else "Sesi pengujian diakhiri lebih awal (ada pengujian berstatus Pending)"
                        elif failed_tests:
                            overall_status = 'Hold'
                            failure_reasons = ", ".join(failed_tests)
                        else:
                            overall_status = 'Good'
                            failure_reasons = None

                        # Find or create assembly record
                        if not assembly_id and tray_id and set_number:
                            cur.execute("SELECT id FROM assemblies WHERE tray_id = ? AND set_number = ?", (tray_id, set_number))
                            row = cur.fetchone()
                            if row:
                                assembly_id = row['id']

                        if assembly_id:
                            cur.execute("SELECT status, defective_parts, reason, verified_status, assy_test_status, qc_status FROM assemblies WHERE id = ?", (assembly_id,))
                            assy_check = cur.fetchone()
                            if assy_check:
                                if assy_check['status'] != 'Good':
                                    return send_json(self, {
                                        "ok": False,
                                        "error": f"Unit Set #{set_number} berstatus {assy_check['status']} di perakitan fisik. Hanya unit dengan status perakitan 'Good' yang dapat diuji pada Test Functional 1!"
                                    }, 400)
                                if (assy_check.get('assy_test_status') in ['Good', 'Hold'] or assy_check.get('qc_status') in ['HOLD_ASSY', 'PENDING_QC', 'GOOD', 'HOLD_QC']) and assy_check.get('verified_status') not in ['APPROVED', 'APPROVED_QC', 'REWORK_GUDANG']:
                                    return send_json(self, {
                                        "ok": False,
                                        "error": f"Set #{set_number} telah selesai diuji dan terkunci dari pengujian ulang!"
                                    }, 400)

                        # Update assemblies
                        lifecycle_val = 'FRESH_GOOD' if overall_status == 'Good' else 'HOLD'
                        cur.execute("SELECT status, assy_status_history FROM assemblies WHERE id = ?", (assembly_id,))
                        cur_hist_row = cur.fetchone()
                        if cur_hist_row:
                            assy_st = cur_hist_row.get('status')
                            if assy_st and assy_st != 'Good':
                                return send_json(self, {
                                    "ok": False,
                                    "error": f"Unit Set #{set_number} berstatus {assy_st} di perakitan fisik. Hanya unit dengan status perakitan 'Good' yang dapat diuji pada Test Functional 1!"
                                }, 400)
                        prev_hist = cur_hist_row.get('assy_status_history') if cur_hist_row else None
                        new_assy_history = append_status_history(prev_hist, overall_status)

                        if assembly_id:
                            if overall_status == 'Good':
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET assy_doc_number = ?,
                                        assy_test_status = 'Good',
                                        assy_failure_reasons = NULL,
                                        qc_status = 'PENDING_QC',
                                        verified_status = 'ACC',
                                        lifecycle_path = ?,
                                        assy_test_checklist = ?,
                                        assy_status_history = ?,
                                        finished_at = COALESCE(finished_at, datetime('now', 'localtime'))
                                    WHERE id = ?
                                """, (doc_number, lifecycle_val, json.dumps(test_checklist), new_assy_history, assembly_id))
                            else:
                                assy_test_stat = 'Pending' if pending_tests else 'Hold'
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET assy_doc_number = COALESCE(assy_doc_number, ?),
                                        assy_test_status = ?,
                                        assy_failure_reasons = ?,
                                        qc_status = 'HOLD_ASSY',
                                        verified_status = 'HOLD_GUDANG',
                                        lifecycle_path = ?,
                                        assy_test_checklist = ?,
                                        assy_status_history = ?,
                                        is_declined = FALSE,
                                        finished_at = COALESCE(finished_at, datetime('now', 'localtime'))
                                    WHERE id = ?
                                """, (doc_number, assy_test_stat, failure_reasons, lifecycle_val, json.dumps(test_checklist), new_assy_history, assembly_id))

                                cur.execute("""
                                    UPDATE master_stocks 
                                    SET status = ?, last_reason = ?
                                    WHERE sn IN (?, ?, ?) OR REPLACE(sn, '.', '') IN (REPLACE(?, '.', ''), REPLACE(?, '.', ''), REPLACE(?, '.', ''))
                                """, (overall_status, failure_reasons or 'Uji Fungsional Assembly Hold', part15_sn, part1_sn, part12_sn, part15_sn, part1_sn, part12_sn))
                        
                        # Fetch tray_code
                        cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                        t_row = cur.fetchone()
                        tray_code = t_row['tray_code'] if t_row else ''

                        cur_tester = user['username']
                        cur_role = user['role']
                        if cur_role == 'admin':
                            cur.execute("SELECT assigned_operator FROM trays WHERE id = ?", (tray_id,))
                            op_r = cur.fetchone()
                            if op_r and op_r.get('assigned_operator'):
                                cur_tester = op_r['assigned_operator']
                                cur_role = 'assembly'

                        # Insert into assembly_tests (Always assign doc_number)
                        cur.execute("""
                            INSERT INTO assembly_tests (
                                stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                                p9_sn, part15_sn, part12_sn, part1_sn,
                                tester_username, tester_role, test_checklist,
                                status, failure_reasons, notes, lifecycle_path, status_history, created_at
                            ) VALUES (
                                'ASSY', ?, ?, ?, ?, ?,
                                ?, ?, ?, ?,
                                ?, ?, ?,
                                ?, ?, ?, ?, ?, datetime('now', 'localtime')
                            ) RETURNING id, doc_number, created_at
                        """, (
                            doc_number, tray_id, tray_code, set_number, assembly_id,
                            part15_sn, part15_sn, part12_sn, part1_sn,
                            cur_tester, cur_role, json.dumps(test_checklist),
                            overall_status, failure_reasons, notes, lifecycle_val, new_assy_history
                        ))
                        test_rec = cur.fetchone()
                        conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "status": overall_status,
                            "doc_number": doc_number,
                            "failure_reasons": failure_reasons,
                            "test_id": test_rec['id'],
                            "message": f"Uji Fungsional Assy Set #{set_number} selesai (Status: {overall_status}). No. Dokumen: {doc_number}" + (f" (Alasan: {failure_reasons})" if failure_reasons else "")
                        })

                    elif path == "/api/qc/submit-test":
                        if not user_has_perm(user, 'qc', require_write=True):
                            return send_json(self, {"ok": False, "error": "Akses ditolak (butuh izin tulis Quality Control / QC)"}, 403)
                        
                        assembly_id = body.get('assembly_id')
                        if not assembly_id:
                            return send_json(self, {"ok": False, "error": "assembly_id wajib diisi"}, 400)
                        
                        test_checklist = body.get('test_checklist') or []
                        notes = (body.get('notes') or '').strip()

                        cur.execute("""
                            SELECT a.*, t.tray_code 
                            FROM assemblies a
                            JOIN trays t ON a.tray_id = t.id
                            WHERE a.id = ?
                        """, (assembly_id,))
                        assy = cur.fetchone()
                        if not assy:
                            return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)

                        # Strict Guard: Items that are Hold in assembly or have not passed functional assembly test cannot be tested by QC
                        if assy.get('status') != 'Good' or (assy.get('assy_test_status') or 'Good') != 'Good' or assy.get('qc_status') == 'HOLD_ASSY' or not assy.get('assy_doc_number'):
                            return send_json(self, {
                                "ok": False, 
                                "error": f"Set #{assy.get('set_number')} belum lulus perakitan / uji Test Functional 1, sehingga tidak dapat diproses oleh QC!"
                            }, 400)

                        explicit_status = (body.get('status') or '').strip().title()
                        failed_tests = []
                        for t in test_checklist:
                            st = str(t.get('status') or '').strip().upper()
                            if st in ['NOT_GOOD', 'NOT GOOD', 'NG', 'FAIL']:
                                t_num = t.get('test_num') or t.get('id') or ''
                                t_name = t.get('name') or f"test {t_num}"
                                failed_tests.append(f"gagal dalam test {t_num}" if t_num else f"gagal dalam {t_name}")

                        doc_number = get_tray_doc_number(assy.get('tray_code'), 'QC')
                        if explicit_status == 'Hold' and not (body.get('failure_reasons') or notes or failed_tests):
                            return send_json(self, {"ok": False, "error": "Alasan penahanan mutu QC (Hold) wajib diisi!"}, 400)

                        new_qc_history = append_status_history(assy.get('qc_status_history'), 'Hold QC' if (explicit_status == 'Hold' or (not explicit_status and failed_tests)) else 'Good QC')

                        if explicit_status == 'Hold' or (not explicit_status and failed_tests):
                            overall_status = 'Hold'
                            failure_reasons = body.get('failure_reasons') or notes or (", ".join(failed_tests) if failed_tests else 'Tertahan di Quality Control (QC)')
                            cur.execute("""
                                UPDATE assemblies 
                                SET qc_status = 'HOLD_QC',
                                    qc_doc_number = COALESCE(qc_doc_number, ?),
                                    qc_failure_reasons = ?,
                                    qc_notes = ?,
                                    qc_tested_at = datetime('now', 'localtime'),
                                    qc_tested_by = ?,
                                    qc_status_history = ?,
                                    verified_status = 'HOLD_QC_GUDANG',
                                    warehouse_receipt_status = 'PENDING'
                                WHERE id = ?
                            """, (doc_number, failure_reasons, notes, user['username'], new_qc_history, assembly_id))
                            for sn in [assy['part15_sn'], assy['part12_sn'], assy['part1_sn']]:
                                if sn:
                                    cur.execute("UPDATE master_stocks SET status = 'Hold' WHERE sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')", (sn, sn))
                        else:
                            overall_status = 'Good'
                            failure_reasons = None
                            cur.execute("""
                                UPDATE assemblies 
                                SET qc_status = 'GOOD',
                                    qc_doc_number = ?,
                                    qc_failure_reasons = NULL,
                                    qc_notes = ?,
                                    qc_tested_at = datetime('now', 'localtime'),
                                    qc_tested_by = ?,
                                    qc_status_history = ?,
                                    warehouse_receipt_status = 'PENDING'
                                WHERE id = ?
                            """, (doc_number, notes, user['username'], new_qc_history, assembly_id))
                            for sn in [assy['part15_sn'], assy['part12_sn'], assy['part1_sn']]:
                                if sn:
                                    cur.execute("UPDATE master_stocks SET status = 'Good' WHERE sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', '')", (sn, sn))

                        qc_lifecycle = assy.get('lifecycle_path') or 'FRESH_GOOD'
                        cur.execute("""
                            INSERT INTO assembly_tests (
                                stage, doc_number, tray_id, tray_code, set_number, assembly_id,
                                p9_sn, part15_sn, part12_sn, part1_sn,
                                tester_username, tester_role, test_checklist,
                                status, failure_reasons, notes, lifecycle_path, status_history, created_at
                            ) VALUES (
                                'QC', ?, ?, ?, ?, ?,
                                ?, ?, ?, ?,
                                ?, ?, ?,
                                ?, ?, ?, ?, ?, datetime('now', 'localtime')
                            ) RETURNING id, doc_number, created_at
                        """, (
                            doc_number, assy['tray_id'], assy['tray_code'], assy['set_number'], assembly_id,
                            assy['part15_sn'], assy['part15_sn'], assy['part12_sn'], assy['part1_sn'],
                            user['username'], user['role'], json.dumps(test_checklist),
                            overall_status, failure_reasons, notes, qc_lifecycle, new_qc_history
                        ))
                        test_rec = cur.fetchone()
                        conn.commit()

                        return send_json(self, {
                            "ok": True,
                            "status": overall_status,
                            "doc_number": doc_number,
                            "failure_reasons": failure_reasons,
                            "test_id": test_rec['id'],
                            "message": f"Uji Mutu QC Set #{assy['set_number']} selesai (Status: {overall_status}). No. Dokumen: {doc_number}" + (f" (Alasan: {failure_reasons})" if failure_reasons else "")
                        })

                    elif path == "/api/warehouse/verify-qc-receipt":
                        if not (user_has_perm(user, 'warehouse_receipt', require_write=True) or user_has_perm(user, 'warehouse', require_write=True)):
                            return send_json(self, {"ok": False, "error": "Akses khusus Penerimaan Gudang (Write)"}, 403)
                        
                        tray_id = body.get('tray_id')
                        assembly_id = body.get('assembly_id')
                        action = str(body.get('action') or 'ACCEPT').strip().upper()
                        notes = (body.get('notes') or '').strip()

                        if not assembly_id and not tray_id:
                            return send_json(self, {"ok": False, "error": "assembly_id atau tray_id wajib diisi"}, 400)

                        if assembly_id:
                            cur.execute("SELECT a.*, tr.tray_code FROM assemblies a JOIN trays tr ON a.tray_id = tr.id WHERE a.id = ?", (assembly_id,))
                            assy = cur.fetchone()
                            if not assy:
                                return send_json(self, {"ok": False, "error": "Data perakitan tidak ditemukan"}, 404)
                            
                            if assy['qc_status'] not in ['GOOD', 'GOOD_QC']:
                                return send_json(self, {"ok": False, "error": "Gudang hanya dapat memverifikasi barang yang status akhirnya mutlak Good dari hasil QC!"}, 400)

                            if assy.get('status') != 'Good' or (assy.get('assy_test_status') or 'Good') != 'Good':
                                return send_json(self, {"ok": False, "error": "Gudang hanya dapat memverifikasi barang yang sudah lulus perakitan dan Test Functional 1!"}, 400)

                            if assy.get('warehouse_receipt_status') in ['ACCEPTED', 'ACC', 'DECLINED']:
                                return send_json(self, {"ok": False, "error": "Sudah diverifikasi fisik oleh Gudang dan terkunci permanen, tidak dapat diubah kembali."}, 400)

                            receipt_status = 'ACCEPTED' if action in ['ACCEPT', 'ACCEPTED'] else 'DECLINED'
                            if receipt_status == 'ACCEPTED':
                                gr_doc = get_tray_doc_number(assy.get('tray_code'), 'GR')
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET warehouse_receipt_status = 'ACCEPTED',
                                        warehouse_receipt_at = datetime('now', 'localtime'),
                                        warehouse_receipt_by = ?,
                                        warehouse_doc_number = COALESCE(warehouse_doc_number, ?),
                                        declined_reason = NULL,
                                        is_declined = FALSE,
                                        verified_status = 'ACC'
                                    WHERE id = ?
                                """, (user['username'], gr_doc, assembly_id))
                                # If any parts had pending re_register_requests from decline, cancel them
                                part_mapping = [('15', assy.get('part15_sn')), ('1', assy.get('part1_sn')), ('12', assy.get('part12_sn'))]
                                for p_type, sn in part_mapping:
                                    if sn and sn != '-':
                                        cur.execute("""
                                            UPDATE re_register_requests 
                                            SET status = 'CANCELLED', admin_notes = 'Accepted by warehouse'
                                            WHERE sn = ? AND status = 'PENDING'
                                        """, (sn,))
                                msg = f"Penerimaan fisik P9 SN {assy['part15_sn']} dari laporan QC berhasil di-Accept oleh Gudang."
                            else:
                                dec_reason = notes or 'Ditolak fisik oleh gudang saat verifikasi penerimaan QC'
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET warehouse_receipt_status = 'DECLINED',
                                        warehouse_receipt_at = datetime('now', 'localtime'),
                                        warehouse_receipt_by = ?,
                                        declined_reason = ?,
                                        is_declined = TRUE,
                                        verified_status = 'DECLINED',
                                        is_reregistered = FALSE,
                                        status = COALESCE(status, 'Good')
                                    WHERE id = ?
                                """, (user['username'], dec_reason, assembly_id))

                                # Update master_stocks and register into re_register_requests for Admin Verification
                                part_mapping = [('15', assy.get('part15_sn')), ('1', assy.get('part1_sn')), ('12', assy.get('part12_sn'))]
                                for p_type, sn in part_mapping:
                                    if sn and sn != '-':
                                        cur.execute("""
                                            UPDATE master_stocks 
                                            SET status = 'Declined' 
                                            WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                        """, (p_type, sn, sn))

                                        cur.execute("""
                                            INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                            VALUES (?, ?, 'Good/QC', 'Declined', 'WAREHOUSE_DECLINE_QC_RECEIPT', ?, ?, ?, datetime('now', 'localtime'))
                                        """, (p_type, sn, user['username'], user['role'], dec_reason))

                                msg = f"Penerimaan fisik P9 SN {assy['part15_sn']} di-Decline oleh Gudang dan otomatis masuk ke antrean Verifikasi Admin (Khusus Declined)."

                            # Check if all good assemblies in this tray are now verified
                            t_id = assy.get('tray_id')
                            tray_fully_verified = False
                            if t_id:
                                cur.execute("""
                                    SELECT COUNT(*) as total_good,
                                           COUNT(CASE WHEN warehouse_receipt_status IN ('ACCEPTED', 'DECLINED') THEN 1 END) as total_verified
                                    FROM assemblies 
                                    WHERE tray_id = ? AND (qc_status = 'GOOD' OR qc_status = 'GOOD_QC')
                                """, (t_id,))
                                cnt = cur.fetchone()
                                if cnt and cnt['total_good'] > 0 and cnt['total_good'] == cnt['total_verified']:
                                    cur.execute("UPDATE trays SET verified_by_gudang = 1, verified_by_user = ?, verified_at = datetime('now', 'localtime') WHERE id = ?", (user['username'], t_id))
                                    tray_fully_verified = True
                                else:
                                    cur.execute("UPDATE trays SET verified_by_gudang = 0 WHERE id = ?", (t_id,))

                            conn.commit()
                            return send_json(self, {
                                "ok": True, 
                                "action": receipt_status, 
                                "assembly_id": assembly_id,
                                "tray_fully_verified": tray_fully_verified,
                                "message": msg
                            })

                        elif tray_id:
                            cur.execute("SELECT * FROM assemblies WHERE tray_id = ? AND (qc_status = 'GOOD' OR qc_status = 'GOOD_QC')", (tray_id,))
                            good_assys = cur.fetchall()
                            if not good_assys:
                                return send_json(self, {"ok": False, "error": "Tidak ada unit Good dari hasil QC pada tray ini yang siap diverifikasi"}, 400)
                            
                            already_locked = [a for a in good_assys if a.get('warehouse_receipt_status') in ['ACCEPTED', 'ACC', 'DECLINED']]
                            if len(already_locked) == len(good_assys):
                                return send_json(self, {"ok": False, "error": "Seluruh barang pada tray ini sudah diverifikasi fisik oleh Gudang dan terkunci permanen, tidak dapat diubah kembali."}, 400)
                            
                            receipt_status = 'ACCEPTED' if action in ['ACCEPT', 'ACCEPTED'] else 'DECLINED'
                            if receipt_status == 'ACCEPTED':
                                cur.execute("SELECT tray_code FROM trays WHERE id = ?", (tray_id,))
                                t_code_row = cur.fetchone()
                                t_code_val = t_code_row['tray_code'] if t_code_row else ''
                                gr_doc = get_tray_doc_number(t_code_val, 'GR')
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET warehouse_receipt_status = 'ACCEPTED',
                                        warehouse_receipt_at = datetime('now', 'localtime'),
                                        warehouse_receipt_by = ?,
                                        warehouse_doc_number = COALESCE(warehouse_doc_number, ?),
                                        declined_reason = NULL,
                                        is_declined = FALSE,
                                        verified_status = 'ACC'
                                    WHERE tray_id = ? AND (qc_status = 'GOOD' OR qc_status = 'GOOD_QC')
                                """, (user['username'], gr_doc, tray_id))
                                for a_item in good_assys:
                                    for p_type, sn in [('15', a_item.get('part15_sn')), ('1', a_item.get('part1_sn')), ('12', a_item.get('part12_sn'))]:
                                        if sn and sn != '-':
                                            cur.execute("""
                                                UPDATE re_register_requests 
                                                SET status = 'CANCELLED', admin_notes = 'Accepted by warehouse'
                                                WHERE sn = ? AND status = 'PENDING'
                                            """, (sn,))
                                msg = f"Verifikasi aktual fisik {len(good_assys)} unit Good untuk Tray berhasil di-Accept oleh Gudang."
                            else:
                                dec_reason = notes or 'Ditolak fisik oleh gudang saat verifikasi penerimaan QC'
                                cur.execute("""
                                    UPDATE assemblies 
                                    SET warehouse_receipt_status = 'DECLINED',
                                        warehouse_receipt_at = datetime('now', 'localtime'),
                                        warehouse_receipt_by = ?,
                                        declined_reason = ?,
                                        is_declined = TRUE,
                                        verified_status = 'DECLINED',
                                        is_reregistered = FALSE
                                    WHERE tray_id = ? AND (qc_status = 'GOOD' OR qc_status = 'GOOD_QC')
                                """, (user['username'], dec_reason, tray_id))

                                for a_item in good_assys:
                                    for p_type, sn in [('15', a_item.get('part15_sn')), ('1', a_item.get('part1_sn')), ('12', a_item.get('part12_sn'))]:
                                        if sn and sn != '-':
                                            cur.execute("""
                                                UPDATE master_stocks 
                                                SET status = 'Declined' 
                                                WHERE part_type = ? AND (sn = ? OR REPLACE(sn, '.', '') = REPLACE(?, '.', ''))
                                            """, (p_type, sn, sn))

                                            cur.execute("""
                                                INSERT INTO part_audit_logs (part_type, sn, old_status, new_status, action_type, actor_username, actor_role, reason, created_at)
                                                VALUES (?, ?, 'Good/QC', 'Declined', 'WAREHOUSE_DECLINE_QC_RECEIPT', ?, ?, ?, datetime('now', 'localtime'))
                                            """, (p_type, sn, user['username'], user['role'], dec_reason))

                                msg = f"Verifikasi aktual fisik {len(good_assys)} unit Good untuk Tray di-Decline oleh Gudang dan masuk ke Verifikasi Admin (Khusus Declined)."

                            cur.execute("UPDATE trays SET verified_by_gudang = 1, verified_by_user = ?, verified_at = datetime('now', 'localtime') WHERE id = ?", (user['username'], tray_id))
                            conn.commit()
                            return send_json(self, {
                                "ok": True, 
                                "action": receipt_status, 
                                "count": len(good_assys), 
                                "message": msg
                            })

                    elif path == "/api/customers":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Customer / Delivery Order"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                name = (body.get("name") or "").strip()
                                address = (body.get("address") or "").strip()
                                phone = (body.get("phone") or "").strip()
                                fax = (body.get("fax") or "").strip()

                                if not name:
                                    return send_json(self, {"ok": False, "error": "Nama Customer wajib diisi!"}, 400)
                                if not address:
                                    return send_json(self, {"ok": False, "error": "Alamat Customer wajib diisi!"}, 400)

                                cur.execute("SELECT * FROM customers WHERE LOWER(name) = LOWER(?)", (name,))
                                existing = cur.fetchone()
                                if existing:
                                    cur.execute("""
                                        UPDATE customers 
                                        SET address = ?, phone = ?, fax = ?, updated_at = datetime('now', 'localtime')
                                        WHERE id = ?
                                    """, (address, phone, fax, existing['id']))
                                    conn.commit()
                                    cur.execute("SELECT * FROM customers WHERE id = ?", (existing['id'],))
                                    cust = dict(cur.fetchone())
                                else:
                                    cur.execute("""
                                        INSERT INTO customers (name, address, phone, fax)
                                        VALUES (?, ?, ?, ?)
                                    """, (name, address, phone, fax))
                                    conn.commit()
                                    new_id = cur.lastrowid
                                    cur.execute("SELECT * FROM customers WHERE id = ?", (new_id,))
                                    cust = dict(cur.fetchone())

                                return send_json(self, {"ok": True, "customer": cust, "message": "Data customer berhasil disimpan!"})

                    elif path == "/api/customers/update":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Customer / Delivery Order"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cust_id = body.get("id")
                                name = (body.get("name") or "").strip()
                                address = (body.get("address") or "").strip()
                                phone = (body.get("phone") or "").strip()
                                fax = (body.get("fax") or "").strip()

                                if not cust_id or not name or not address:
                                    return send_json(self, {"ok": False, "error": "ID, Nama, dan Alamat Customer wajib diisi!"}, 400)

                                cur.execute("""
                                    UPDATE customers 
                                    SET name = ?, address = ?, phone = ?, fax = ?, updated_at = datetime('now', 'localtime')
                                    WHERE id = ?
                                """, (name, address, phone, fax, cust_id))
                                conn.commit()
                                return send_json(self, {"ok": True, "message": "Data customer berhasil diperbarui!"})

                    elif path == "/api/customers/delete":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Customer / Delivery Order"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cust_id = body.get("id")
                                if not cust_id:
                                    return send_json(self, {"ok": False, "error": "ID Customer wajib diberikan"}, 400)
                                cur.execute("DELETE FROM customers WHERE id = ?", (cust_id,))
                                conn.commit()
                                return send_json(self, {"ok": True, "message": "Customer berhasil dihapus!"})

                    elif path == "/api/delivery-orders/settings":
                        if not is_admin_user(user):
                            return send_json(self, {"ok": False, "error": "Pengaturan Delivery Order hanya dapat diubah oleh Administrator"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("""
                                    UPDATE do_settings SET
                                        company_name = ?,
                                        company_logo = ?,
                                        head_office = ?,
                                        factory_address = ?,
                                        contact_email = ?,
                                        default_customer_name = ?,
                                        default_customer_address = ?,
                                        default_customer_phone = ?,
                                        default_customer_fax = ?,
                                        default_contract_number = ?,
                                        default_program_name = ?,
                                        default_product_name = ?,
                                        default_issuer_name = ?,
                                        default_issuer_title = ?,
                                        default_issuer_department = ?,
                                        do_prefix = ?,
                                        pl_prefix = ?,
                                        crate_prefix = ?,
                                        crate_capacity = ?,
                                        box_capacity = ?,
                                        tracking_base_url = ?,
                                        do_sequence_offset = ?,
                                        do_offset_month = ?,
                                        updated_at = datetime('now', 'localtime')
                                    WHERE id = 1
                                """, (
                                    body.get("company_name", "PT Nanggala Kencana Rekatama Indonesia"),
                                    body.get("company_logo", "nkri-logo.png"),
                                    body.get("head_office", "Jalan Mendawai I No. 11 Kramat Pela\nJakarta Selatan, 12130"),
                                    body.get("factory_address", "Jalan Gempol No. 9, Mekar Mulya\nBandung, 40292"),
                                    body.get("contact_email", "info@nanggala.co.id"),
                                    body.get("default_customer_name", "KEMHAN RI"),
                                    body.get("default_customer_address", "BADAN LOGISTIK PERTAHANAN\nJL. TANAH ABANG TIMUR NO. 6 – 7\nJAKARTA, 10110"),
                                    body.get("default_customer_phone", "(021) 3840889"),
                                    body.get("default_customer_fax", "(021) 3840889"),
                                    body.get("default_contract_number", "TRAK/2509/APBN/XI/2025/KEMHAN"),
                                    body.get("default_program_name", "Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025"),
                                    body.get("default_product_name", "Nanggala P9"),
                                    body.get("default_issuer_name", "Rezka Rachdana"),
                                    body.get("default_issuer_title", "Staf"),
                                    body.get("default_issuer_department", "PPIC"),
                                    body.get("do_prefix", "/SJ/PPIC/"),
                                    body.get("pl_prefix", "/PL/PPIC/"),
                                    body.get("crate_prefix", "2609-"),
                                    int(body.get("crate_capacity", 20)),
                                    int(body.get("box_capacity", 10)),
                                    body.get("tracking_base_url", "").strip(),
                                    int(body.get("do_sequence_offset", 0)),
                                    body.get("do_offset_month", "").strip()
                                ))
                                conn.commit()
                                cur.execute("SELECT * FROM do_settings WHERE id = 1")
                                updated = dict(cur.fetchone())
                        return send_json(self, {"ok": True, "settings": updated, "message": "Pengaturan DO & PL berhasil disimpan"})

                    elif path == "/api/delivery-orders":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Delivery Order"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                do_date = (body.get("do_date") or "").strip() or format_indo_date()
                                target_dt = parse_target_date(do_date)
                                yymm = target_dt.strftime("%y%m")
                                do_num = (body.get("do_number") or "").strip() or get_next_do_number(cur, target_date=target_dt)
                                contract_num = (body.get("contract_number") or "").strip() or "TRAK/2509/APBN/XI/2025/KEMHAN"
                                program_name = (body.get("program_name") or "").strip() or "Modernisasi Alutsista, Non Alutsista dan Sarpras Pertahanan TA 2025"
                                
                                customer_id = body.get("customer_id")
                                customer_name = (body.get("customer_name") or "").strip()
                                customer_address = (body.get("customer_address") or "").strip()
                                customer_phone = (body.get("customer_phone") or "").strip()
                                customer_fax = (body.get("customer_fax") or "").strip()

                                if not customer_name:
                                    return send_json(self, {"ok": False, "error": "Nama Customer / Penerima wajib diisi!"}, 400)
                                if not customer_address:
                                    return send_json(self, {"ok": False, "error": "Alamat Penerima wajib diisi!"}, 400)

                                # Auto-save customer if not exist or update
                                if customer_name:
                                    cur.execute("SELECT id FROM customers WHERE LOWER(name) = LOWER(?)", (customer_name,))
                                    c_row = cur.fetchone()
                                    if c_row:
                                        customer_id = c_row['id']
                                    else:
                                        try:
                                            cur.execute("""
                                                INSERT INTO customers (name, address, phone, fax)
                                                VALUES (?, ?, ?, ?)
                                            """, (customer_name, customer_address, customer_phone, customer_fax))
                                            customer_id = cur.lastrowid
                                        except Exception:
                                            cur.execute("SELECT id FROM customers WHERE LOWER(name) = LOWER(?)", (customer_name,))
                                            c_row = cur.fetchone()
                                            if c_row:
                                                customer_id = c_row['id']

                                product_name = (body.get("product_name") or "").strip() or "Nanggala P9"
                                total_qty = int(body.get("total_quantity") or 0)
                                batch_number = int(body.get("batch_number") or 1)
                                created_by_name = (body.get("created_by_name") or body.get("created_by") or "").strip() or user.get('full_name') or 'Rezka Rachdana'
                                created_by_title = (body.get("created_by_title") or "").strip() or 'Staf'
                                created_by_department = (body.get("created_by_department") or "").strip() or 'PPIC'
                                package_type = (body.get("package_type") or "crate").strip()
                                notes = (body.get("notes") or "").strip()
                                
                                crates_data = body.get("crates") or []
                                total_crates = len(crates_data) if crates_data else max(1, (total_qty + 19) // 20)

                                # Check duplicate DO number dan auto-advance jika sudah terpakai
                                cur.execute("SELECT id FROM delivery_orders WHERE do_number = ?", (do_num,))
                                if cur.fetchone():
                                    do_num = get_next_do_number(cur, target_date=target_dt)

                                cur.execute("""
                                    INSERT INTO delivery_orders (
                                        do_number, do_date, contract_number, program_name,
                                        customer_id, customer_name, customer_address, customer_phone, customer_fax,
                                        product_name, total_quantity, total_crates, batch_number,
                                        created_by, created_by_title, created_by_department, package_type, status, notes
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ISSUED', ?)
                                """, (
                                    do_num, do_date, contract_num, program_name,
                                    customer_id, customer_name, customer_address, customer_phone, customer_fax,
                                    product_name, total_qty, total_crates, batch_number,
                                    created_by_name, created_by_title, created_by_department, package_type, notes
                                ))
                                do_id = cur.lastrowid

                                consumed_standalone_id = body.get("consumed_standalone_id")
                                if consumed_standalone_id:
                                    try:
                                        cur.execute("""
                                            UPDATE standalone_packing_labels
                                            SET notes = CASE 
                                                WHEN notes IS NULL OR notes = '' THEN ? 
                                                ELSE notes || ' ' || ? 
                                            END
                                            WHERE id = ?
                                        """, (f"[Digunakan pada {do_num}]", f"[Digunakan pada {do_num}]", consumed_standalone_id))
                                    except Exception as ex:
                                        logger.warning("Could not link standalone packing label: %s", ex)

                                # Process Crates (Packing Lists) based on package_type
                                if package_type == 'unit':
                                    # Satuan: ID Parent menggunakan Serial Number langsung
                                    unit_items = []
                                    for c_obj in crates_data:
                                        for b_obj in (c_obj.get("boxes") or []):
                                            for it in (b_obj.get("items") or []):
                                                sn_val = (it.get("serial_number") if isinstance(it, dict) else str(it or "")).strip()
                                                if sn_val:
                                                    unit_items.append(sn_val)

                                    total_crates = len(unit_items) if unit_items else 1
                                    cur.execute("UPDATE delivery_orders SET total_crates = ? WHERE id = ?", (total_crates, do_id))

                                    unit_pl = get_next_pl_number(cur, target_date=target_dt)
                                    for u_idx, sn_val in enumerate(unit_items, 1):
                                        pl_num = unit_pl
                                        unit_crate_id = sn_val
                                        cur.execute("SELECT 1 FROM packing_lists WHERE crate_id = ?", (unit_crate_id,))
                                        if cur.fetchone():
                                            unit_crate_id = f"{sn_val}-{do_id}"

                                        cur.execute("""
                                            INSERT INTO packing_lists (
                                                delivery_order_id, pl_number, pl_date, crate_id,
                                                crate_number, total_crates, product_name, total_quantity, notes
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                        """, (
                                            do_id, pl_num, do_date, unit_crate_id,
                                            u_idx, total_crates, product_name, 1, notes
                                        ))
                                        pl_id = cur.lastrowid

                                        unit_box_id = unit_crate_id
                                        cur.execute("SELECT 1 FROM packing_list_boxes WHERE box_id = ?", (unit_box_id,))
                                        if cur.fetchone():
                                            unit_box_id = f"{unit_crate_id}-{u_idx}"

                                        cur.execute("""
                                            INSERT INTO packing_list_boxes (
                                                packing_list_id, delivery_order_id, box_id, box_number, quantity
                                            ) VALUES (?, ?, ?, ?, ?)
                                        """, (pl_id, do_id, unit_box_id, 1, 1))
                                        box_pk = cur.lastrowid

                                        cur.execute("""
                                            INSERT INTO packing_list_items (
                                                box_id, packing_list_id, delivery_order_id, slot_number, serial_number
                                            ) VALUES (?, ?, ?, ?, ?)
                                        """, (box_pk, pl_id, do_id, 1, sn_val))

                                elif package_type == 'box':
                                    # Per Dus: ID Parent menggunakan ID Dus
                                    total_crates = len(crates_data)
                                    cur.execute("UPDATE delivery_orders SET total_crates = ? WHERE id = ?", (total_crates, do_id))

                                    # 1 Lembar Packing List fisik memuat 2 dus. Alokasikan nomor PL berurutan per lembar.
                                    num_sheets = max(1, (len(crates_data) + 1) // 2)
                                    sheet_pl_numbers = get_next_pl_numbers(cur, count=num_sheets, target_date=target_dt)

                                    assigned_boxes = set()
                                    for c_idx, c_obj in enumerate(crates_data, 1):
                                        box_id = (c_obj.get("box_id") or "").strip()
                                        boxes_data = c_obj.get("boxes") or []
                                        if not box_id and boxes_data and boxes_data[0].get("box_id"):
                                            box_id = str(boxes_data[0]["box_id"]).strip()

                                        is_imported = bool(c_obj.get("consumed_source_box_id") or (boxes_data and boxes_data[0].get("consumed_source_box_id")))
                                        if not is_imported and (not box_id or not box_id.startswith(f"{yymm}-")):
                                            box_id = get_next_box_id(cur, batch_number=batch_number, box_index=c_idx, target_date=target_dt, exclude_ids=assigned_boxes)

                                        # Pastikan box_id belum terpakai di packing_list_boxes maupun packing_lists
                                        cur.execute("SELECT 1 FROM packing_list_boxes WHERE box_id = ?", (box_id,))
                                        in_b = cur.fetchone()
                                        cur.execute("SELECT 1 FROM packing_lists WHERE crate_id = ?", (box_id,))
                                        in_p = cur.fetchone()
                                        if in_b or in_p or box_id in assigned_boxes:
                                            box_id = get_next_box_id(cur, batch_number=batch_number, box_index=c_idx, target_date=target_dt, exclude_ids=assigned_boxes)

                                        assigned_boxes.add(box_id)

                                        sheet_idx = (c_idx - 1) // 2
                                        default_sheet_pl = sheet_pl_numbers[sheet_idx] if sheet_idx < len(sheet_pl_numbers) else sheet_pl_numbers[-1]
                                        pl_num = (c_obj.get("pl_number") or "").strip() or default_sheet_pl

                                        cur.execute("""
                                            INSERT INTO packing_lists (
                                                delivery_order_id, pl_number, pl_date, crate_id,
                                                crate_number, total_crates, product_name, total_quantity, notes
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                        """, (
                                            do_id, pl_num, do_date, box_id,
                                            c_idx, total_crates, product_name, 10, notes
                                        ))
                                        pl_id = cur.lastrowid

                                        cur.execute("""
                                            INSERT INTO packing_list_boxes (
                                                packing_list_id, delivery_order_id, box_id, box_number, quantity
                                            ) VALUES (?, ?, ?, ?, ?)
                                        """, (pl_id, do_id, box_id, 1, 10))
                                        box_pk = cur.lastrowid

                                        items_data = boxes_data[0].get("items") if boxes_data else []
                                        for slot_idx in range(1, 11):
                                            sn_val = ""
                                            if slot_idx - 1 < len(items_data):
                                                item_raw = items_data[slot_idx - 1]
                                                if isinstance(item_raw, dict):
                                                    sn_val = (item_raw.get("serial_number") or "").strip()
                                                else:
                                                    sn_val = str(item_raw or "").strip()

                                            cur.execute("""
                                                INSERT INTO packing_list_items (
                                                    box_id, packing_list_id, delivery_order_id, slot_number, serial_number
                                                ) VALUES (?, ?, ?, ?, ?)
                                            """, (box_pk, pl_id, do_id, slot_idx, sn_val))

                                else:
                                    # Per Peti: ID Parent menggunakan ID Peti (1 Peti = 2 Dus @ 10 SN)
                                    # 1 Lembar Packing List fisik memuat 2 peti. Alokasikan nomor PL berurutan per lembar.
                                    num_sheets = max(1, (len(crates_data) + 1) // 2)
                                    sheet_pl_numbers = get_next_pl_numbers(cur, count=num_sheets, target_date=target_dt)

                                    for c_idx, c_obj in enumerate(crates_data, 1):
                                        crate_id = (c_obj.get("crate_id") or "").strip()
                                        is_imported = bool(c_obj.get("consumed_source_crate_id"))
                                        if not is_imported and (not crate_id or not crate_id.startswith(f"{yymm}-")):
                                            crate_id = get_next_crate_id(cur, batch_number=batch_number, crate_index=c_idx, target_date=target_dt)
                                        else:
                                            # Pastikan crate_id tidak bentrok dengan data yang sudah ada
                                            cur.execute("SELECT 1 FROM packing_lists WHERE crate_id = ?", (crate_id,))
                                            if cur.fetchone():
                                                crate_id = get_next_crate_id(cur, batch_number=batch_number, crate_index=c_idx, target_date=target_dt)
                                        
                                        sheet_idx = (c_idx - 1) // 2
                                        default_sheet_pl = sheet_pl_numbers[sheet_idx] if sheet_idx < len(sheet_pl_numbers) else sheet_pl_numbers[-1]
                                        pl_num = (c_obj.get("pl_number") or "").strip() or default_sheet_pl

                                        crate_qty = int(c_obj.get("total_quantity") or 20)

                                        cur.execute("""
                                            INSERT INTO packing_lists (
                                                delivery_order_id, pl_number, pl_date, crate_id,
                                                crate_number, total_crates, product_name, total_quantity, notes
                                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                                        """, (
                                            do_id, pl_num, do_date, crate_id,
                                            c_idx, total_crates, product_name, crate_qty, notes
                                        ))
                                        pl_id = cur.lastrowid

                                        boxes_data = c_obj.get("boxes") or []
                                        if not boxes_data:
                                            boxes_data = [
                                                {"box_number": 1, "items": []},
                                                {"box_number": 2, "items": []}
                                            ]

                                        # Standarisasi: No dus pada peti hanya sampai 2 (maksimal 2 dus)
                                        for b_idx, b_obj in enumerate(boxes_data[:2], 1):
                                            b_num = int(b_obj.get("box_number") or b_idx)
                                            if b_num < 1 or b_num > 2:
                                                b_num = b_idx if 1 <= b_idx <= 2 else 1
                                            box_id = f"{crate_id}-{b_num:02d}"

                                            b_qty = int(b_obj.get("quantity") or 10)

                                            cur.execute("""
                                                INSERT INTO packing_list_boxes (
                                                    packing_list_id, delivery_order_id, box_id, box_number, quantity
                                                ) VALUES (?, ?, ?, ?, ?)
                                            """, (pl_id, do_id, box_id, b_num, b_qty))
                                            box_pk = cur.lastrowid

                                            items_data = b_obj.get("items") or []
                                            for slot_idx in range(1, 11):
                                                sn_val = ""
                                                if slot_idx - 1 < len(items_data):
                                                    item_raw = items_data[slot_idx - 1]
                                                    if isinstance(item_raw, dict):
                                                        sn_val = (item_raw.get("serial_number") or "").strip()
                                                    else:
                                                        sn_val = str(item_raw or "").strip()

                                                cur.execute("""
                                                    INSERT INTO packing_list_items (
                                                        box_id, packing_list_id, delivery_order_id, slot_number, serial_number
                                                    ) VALUES (?, ?, ?, ?, ?)
                                                """, (box_pk, pl_id, do_id, slot_idx, sn_val))

                                conn.commit()
                                return send_json(self, {
                                    "ok": True,
                                    "id": do_id,
                                    "do_number": do_num,
                                    "message": f"Surat Jalan {do_num} berhasil diterbitkan!"
                                })

                    elif path == "/api/delivery-orders/delete":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk menghapus Delivery Order"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                do_id = body.get("id")
                                if not do_id:
                                    return send_json(self, {"ok": False, "error": "ID Delivery Order wajib diberikan"}, 400)
                                cur.execute("DELETE FROM packing_list_items WHERE delivery_order_id = ?", (do_id,))
                                cur.execute("DELETE FROM packing_list_boxes WHERE delivery_order_id = ?", (do_id,))
                                cur.execute("DELETE FROM packing_lists WHERE delivery_order_id = ?", (do_id,))
                                cur.execute("DELETE FROM delivery_orders WHERE id = ?", (do_id,))
                                conn.commit()
                                return send_json(self, {"ok": True, "message": "Delivery Order berhasil dihapus!"})

                    elif path == "/api/cloudinary/settings":
                        if not is_admin_user(user):
                            return send_json(self, {"ok": False, "error": "Pengaturan Cloudinary hanya dapat diubah oleh Administrator"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                _ensure_cloud_settings_table(cur)
                                cloud_name = (body.get("cloud_name") or "").strip()
                                api_key = (body.get("api_key") or "").strip()
                                api_secret_in = (body.get("api_secret") or "").strip()
                                folder = sanitize_cloud_folder(body.get("folder") or CLOUDINARY_DEFAULT_FOLDER)

                                if not api_secret_in:
                                    cur.execute("SELECT api_secret FROM cloud_settings WHERE id = 1")
                                    ex = cur.fetchone()
                                    api_secret = (ex.get("api_secret") or "") if ex else ""
                                else:
                                    api_secret = api_secret_in

                                cur.execute("""
                                    UPDATE cloud_settings SET
                                        cloud_name = ?,
                                        api_key = ?,
                                        api_secret = ?,
                                        folder = ?,
                                        updated_at = datetime('now', 'localtime')
                                    WHERE id = 1
                                """, (cloud_name, api_key, api_secret, folder))
                                conn.commit()
                        cfg = get_cloudinary_config()
                        return send_json(self, {
                            "ok": True,
                            "data": cloudinary_public_info(cfg),
                            "message": "Pengaturan Cloudinary berhasil disimpan!"
                        })

                    elif path == "/api/cloudinary/test":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin untuk menguji Cloudinary"}, 403)
                        cfg = get_cloudinary_config()
                        # Baca parameter dari form input jika pengguna sedang mengetik sebelum menyimpan
                        c_name = (body.get("cloud_name") or "").strip()
                        c_key = (body.get("api_key") or "").strip()
                        c_secret = (body.get("api_secret") or "").strip()
                        c_folder = (body.get("folder") or "").strip()
                        if c_name:
                            cfg["cloud_name"] = c_name
                        if c_key:
                            cfg["api_key"] = c_key
                        if c_secret:
                            cfg["api_secret"] = c_secret
                        if c_folder:
                            cfg["folder"] = sanitize_cloud_folder(c_folder)

                        if not cloudinary_is_configured(cfg):
                            return send_json(self, {
                                "ok": False,
                                "error": "Kredensial Cloudinary belum lengkap. Harap isi Cloud Name, API Key, dan API Secret."
                            }, 400)
                        test_id = f"test-ping-{int(time.time())}"
                        full_public_id = f"{sanitize_cloud_folder(cfg.get('folder'))}/{test_id}"
                        test_bytes = build_test_pdf_bytes(f"Tes Koneksi Cloudinary - PT NKRI ({datetime.now().strftime('%Y-%m-%d %H:%M:%S')})")
                        try:
                            up_res = cloudinary_upload_pdf(test_bytes, full_public_id, cfg)
                            sec_url = up_res.get("secure_url") or ""
                            deliv = cloudinary_check_delivery(sec_url) if sec_url else {"ok": False, "error": "URL kosong"}
                            try:
                                cloudinary_destroy(full_public_id, cfg)
                            except Exception:
                                pass

                            # Otomatis simpan kredensial valid ke database
                            if up_res.get("public_id"):
                                with get_conn() as conn:
                                    with conn.cursor() as cur:
                                        _ensure_cloud_settings_table(cur)
                                        cur.execute("""
                                            UPDATE cloud_settings SET
                                                cloud_name = ?,
                                                api_key = ?,
                                                api_secret = ?,
                                                folder = ?,
                                                updated_at = datetime('now', 'localtime')
                                            WHERE id = 1
                                        """, (cfg.get("cloud_name"), cfg.get("api_key"), cfg.get("api_secret"), cfg.get("folder")))
                                        conn.commit()

                            return send_json(self, {
                                "ok": True,
                                "cloud_name": cfg.get("cloud_name"),
                                "folder": cfg.get("folder"),
                                "upload_status": "SUCCESS",
                                "delivery_ok": deliv.get("ok", False),
                                "delivery_http_status": deliv.get("status"),
                                "delivery_error": deliv.get("error"),
                                "note": (
                                    "Koneksi Cloudinary BERHASIL dan file PDF dapat diakses publik!"
                                    if deliv.get("ok") else
                                    "Upload berhasil, TETAPI Cloudinary memblokir pembukaan PDF (HTTP 401/404). Silakan aktifkan opsi: Settings -> Security -> 'Allow delivery of PDF and ZIP files' di dashboard Cloudinary."
                                )
                            })
                        except Exception as ex:
                            return send_json(self, {"ok": False, "error": str(ex)}, 500)

                    elif path == "/api/delivery-orders/cloud-pdf":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Delivery Order"}, 403)
                        do_id = body.get("do_id") or body.get("id")
                        raw_pdf = body.get("pdf_base64") or body.get("pdf")
                        if not do_id:
                            return send_json(self, {"ok": False, "error": "Parameter do_id wajib diisi"}, 400)

                        cfg = get_cloudinary_config()
                        if not cloudinary_is_configured(cfg):
                            return send_json(self, {
                                "ok": False,
                                "error": "Kredensial Cloudinary belum dikonfigurasi di server. Silakan isi Cloud Name, API Key, dan API Secret di Pengaturan DO / Cloudinary."
                            }, 400)

                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT * FROM delivery_orders WHERE id = ?", (do_id,))
                                do_row = cur.fetchone()
                                if not do_row:
                                    return send_json(self, {"ok": False, "error": "Delivery Order tidak ditemukan"}, 404)
                                do_data = dict(do_row)
                                do_num = do_data['do_number']

                                cur.execute("""
                                    SELECT * FROM packing_lists 
                                    WHERE delivery_order_id = ? 
                                    ORDER BY crate_number ASC, id ASC
                                """, (do_id,))
                                crates = [dict(c) for c in cur.fetchall()]
                                for crate in crates:
                                    cur.execute("""
                                        SELECT * FROM packing_list_boxes 
                                        WHERE packing_list_id = ? 
                                        ORDER BY box_number ASC
                                    """, (crate['id'],))
                                    boxes = [dict(b) for b in cur.fetchall()]
                                    for box in boxes:
                                        cur.execute("""
                                            SELECT slot_number, serial_number 
                                            FROM packing_list_items 
                                            WHERE box_id = ? 
                                            ORDER BY slot_number ASC
                                        """, (box['id'],))
                                        box['items'] = [dict(it) for it in cur.fetchall()]
                                    crate['boxes'] = boxes
                                do_data['crates'] = crates

                                cur.execute("SELECT * FROM do_settings WHERE id = 1")
                                s_row = cur.fetchone()
                                st_cfg = dict(s_row) if s_row else {}

                        pdf_bytes = None
                        if raw_pdf:
                            try:
                                candidate = decode_pdf_payload(raw_pdf)
                                if len(candidate) >= 8000:
                                    pdf_bytes = candidate
                            except Exception:
                                pass

                        if not pdf_bytes:
                            if not pl_generator:
                                return send_json(self, {"ok": False, "error": "Modul 'reportlab' belum terinstall di server. Silakan jalankan 'pip install reportlab' di terminal."}, 500)
                            try:
                                pdf_bytes = pl_generator.generate_packing_list_pdf(do_data, st_cfg)
                            except Exception as ex_gen:
                                return send_json(self, {"ok": False, "error": f"Gagal membuat PDF Packing List: {ex_gen}"}, 500)

                        try:
                            public_id = build_pl_public_id(do_num, cfg)
                            up_res = cloudinary_upload_pdf(pdf_bytes, public_id, cfg)
                            version_val = str(up_res.get("version") or "")
                            deterministic_url = build_pl_cloud_url(do_num, cfg)
                            secure_url = up_res.get("secure_url") or deterministic_url

                            with get_conn() as conn:
                                with conn.cursor() as cur:
                                    cur.execute("""
                                        UPDATE delivery_orders SET
                                            cloud_public_id = ?,
                                            cloud_pdf_url = ?,
                                            cloud_version = ?,
                                            cloud_uploaded_at = datetime('now', 'localtime')
                                        WHERE id = ?
                                    """, (public_id, secure_url, version_val, do_id))
                                    conn.commit()

                            return send_json(self, {
                                "ok": True,
                                "message": f"PDF Packing List {do_num} berhasil diunggah ke Cloudinary",
                                "cloud_pdf_url": secure_url,
                                "cloud_public_id": public_id,
                                "cloud_version": version_val,
                                "bytes": len(pdf_bytes)
                            })
                        except Exception as e:
                            print(f"Error saat mengunggah PDF ke Cloudinary: {e}", flush=True)
                            return send_json(self, {
                                "ok": False,
                                "error": f"Gagal mengunggah ke Cloudinary: {e}"
                            }, 500)

                    elif path == "/api/delivery-orders/cloud-pdf/delete":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Delivery Order"}, 403)
                        do_id = body.get("do_id") or body.get("id")
                        if not do_id:
                            return send_json(self, {"ok": False, "error": "Parameter do_id wajib diisi"}, 400)
                        cfg = get_cloudinary_config()
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                cur.execute("SELECT id, do_number, cloud_public_id FROM delivery_orders WHERE id = ?", (do_id,))
                                do_row = cur.fetchone()
                                if not do_row:
                                    return send_json(self, {"ok": False, "error": "Delivery Order tidak ditemukan"}, 404)
                                pub_id = do_row.get("cloud_public_id")
                                if pub_id and cloudinary_is_configured(cfg):
                                    try:
                                        cloudinary_destroy(pub_id, cfg)
                                    except Exception as ex:
                                        print(f"Cloud destroy error: {ex}", flush=True)
                                cur.execute("""
                                    UPDATE delivery_orders SET
                                        cloud_public_id = NULL,
                                        cloud_pdf_url = NULL,
                                        cloud_version = NULL,
                                        cloud_uploaded_at = NULL
                                    WHERE id = ?
                                """, (do_id,))
                                conn.commit()
                        return send_json(self, {"ok": True, "message": "File di Cloudinary berhasil dihapus"})

                    elif path == "/api/packing-labels":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk Packing Labels"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                pkg_type = (body.get("package_type") or "crate").strip()
                                batch_num = int(body.get("batch_number") or 1)
                                prod_name = (body.get("product_name") or "Nanggala P9").strip()
                                notes = (body.get("notes") or "").strip()
                                created_by = (body.get("created_by") or (user.get("full_name") if 'user' in locals() and user else "") or "Rezka Rachdana").strip()
                                crates_data = body.get("crates") or []
                                target_dt = parse_target_date(body.get("date") or body.get("pl_date") or body.get("do_date"))
                                yymm = target_dt.strftime("%y%m")
                                target_day_str = target_dt.strftime("%Y%m%d")

                                # Generate label_batch_id (cari MAX pada tanggal target) & pl_number
                                cur.execute("SELECT label_batch_id FROM standalone_packing_labels WHERE label_batch_id LIKE ?", (f"LBL-{target_day_str}-%",))
                                rows_lbl_today = cur.fetchall()
                                max_lbl_today = 0
                                for r in rows_lbl_today:
                                    lid = str((r['label_batch_id'] if isinstance(r, dict) else r[0]) or '')
                                    m = re.search(r'LBL-\d+-(\d+)', lid)
                                    if m:
                                        max_lbl_today = max(max_lbl_today, int(m.group(1)))
                                next_lbl_seq = max_lbl_today + 1
                                label_batch_id = f"LBL-{target_day_str}-{next_lbl_seq:03d}"
                                while True:
                                    cur.execute("SELECT 1 FROM standalone_packing_labels WHERE label_batch_id = ?", (label_batch_id,))
                                    if not cur.fetchone():
                                        break
                                    next_lbl_seq += 1
                                    label_batch_id = f"LBL-{target_day_str}-{next_lbl_seq:03d}"

                                pl_number = get_next_pl_number(cur, target_date=target_dt)

                                # Assign crate IDs if missing or generic
                                total_qty = 0
                                parent_crate_id = f"{yymm}-{batch_num}000" if pkg_type == 'box' else None
                                expected_crate_prefix = f"{yymm}-{batch_num}"
                                assigned_boxes = set()
                                for idx, c in enumerate(crates_data, 1):
                                    cid = (c.get("crate_id") or "").strip()
                                    if pkg_type == 'box':
                                        c["crate_id"] = parent_crate_id
                                        c_box_id = (c.get("box_id") or "").strip()
                                        boxes = c.get("boxes") or []
                                        if not c_box_id and boxes and boxes[0].get("box_id"):
                                            c_box_id = str(boxes[0]["box_id"]).strip()
                                        if not c_box_id or not c_box_id.startswith(f"{parent_crate_id}-") or c_box_id in assigned_boxes:
                                            c_box_id = get_next_box_id(cur, batch_number=batch_num, box_index=idx, exclude_ids=assigned_boxes, target_date=target_dt)
                                        assigned_boxes.add(c_box_id)
                                        c["box_id"] = c_box_id
                                    elif not cid or not cid.startswith(expected_crate_prefix) or cid.startswith("PT-"):
                                        c["crate_id"] = get_next_crate_id(cur, batch_number=batch_num, crate_index=idx, target_date=target_dt)
                                    boxes = c.get("boxes") or []
                                    if pkg_type == 'crate' and len(boxes) > 2:
                                        boxes = boxes[:2]
                                        c["boxes"] = boxes
                                    c_qty = 0
                                    for b_idx, b in enumerate(boxes, 1):
                                        if pkg_type == 'box':
                                            b["box_id"] = c["box_id"]
                                        else:
                                            b["box_id"] = f"{c['crate_id']}-{b_idx:02d}"
                                        items = b.get("items") or []
                                        for it in items:
                                            s_val = (it.get("serial_number") if isinstance(it, dict) else str(it or "")).strip()
                                            if s_val:
                                                c_qty += 1
                                    c["total_quantity"] = c_qty if c_qty > 0 else (20 if pkg_type == 'crate' else 10)
                                    total_qty += c["total_quantity"]

                                total_containers = len(crates_data)
                                crates_json = json.dumps(crates_data)

                                cur.execute("""
                                    INSERT INTO standalone_packing_labels (
                                        label_batch_id, pl_number, package_type, batch_number,
                                        product_name, total_quantity, total_containers, crates_json,
                                        notes, created_by, created_at
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                """, (
                                    label_batch_id, pl_number, pkg_type, batch_num,
                                    prod_name, total_qty, total_containers, crates_json,
                                    notes, created_by, target_dt.strftime("%Y-%m-%d %H:%M:%S")
                                ))
                                label_id = cur.lastrowid
                                next_b = get_next_batch_number(cur)
                                conn.commit()

                                return send_json(self, {
                                    "ok": True,
                                    "id": label_id,
                                    "label_batch_id": label_batch_id,
                                    "pl_number": pl_number,
                                    "crates": crates_data,
                                    "next_batch": next_b,
                                    "message": f"Label Packing List {label_batch_id} berhasil dibuat dan disimpan!"
                                })

                    elif path == "/api/packing-labels/delete":
                        if not user_has_perm(user, 'delivery_order', require_write=True):
                            return send_json(self, {"ok": False, "error": "Anda tidak memiliki izin tulis untuk menghapus Packing Label"}, 403)
                        with get_conn() as conn:
                            with conn.cursor() as cur:
                                lbl_id = body.get("id")
                                if not lbl_id:
                                    return send_json(self, {"ok": False, "error": "ID label wajib diberikan"}, 400)
                                cur.execute("DELETE FROM standalone_packing_labels WHERE id = ?", (lbl_id,))
                                conn.commit()
                                return send_json(self, {"ok": True, "message": "Label Packing List berhasil dihapus!"})

                    else:
                        return send_json(self, {"ok": False, "error": f"Endpoint POST {path} tidak ditemukan"}, 404)
        except Exception as e:
            import traceback
            traceback.print_exc()
            try:
                if 'conn' in locals() and conn and not getattr(conn, 'closed', False):
                    conn.rollback()
            except Exception:
                pass
            err_msg = str(e)
            if "parts_sn_key" in err_msg:
                match = re.search(r"Key \(sn\)=\((.*?)\)", err_msg)
                sn_val = match.group(1) if match else "tersebut"
                err_msg = f"Serial Number (SN) '{sn_val}' sudah terdaftar di database sebelumnya! Gunakan Serial Number yang unik."
            send_json(self, {"ok": False, "error": err_msg}, 400)

if __name__ == "__main__":
    ensure_db_schema()
    ThreadingHTTPServer.allow_reuse_address = True
    server = ThreadingHTTPServer(("0.0.0.0", PORT), Handler)
    print(f"Server jalan di port {PORT}", flush=True)
    server.serve_forever()