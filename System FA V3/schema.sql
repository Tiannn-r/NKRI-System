-- ====================================================================
-- SKEMA DATABASE: SISTEM TRACEABILITY & INVENTORY FINAL ASSEMBLY P9
-- Database: PostgreSQL
-- ====================================================================

-- 1. Tabel Pengguna (Users & Roles)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    nik VARCHAR(50) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'assembly', -- 'admin', 'gudang', 'assembly', 'qc'
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 2. Tabel Master Stok Komponen (Master Stocks)
-- Menyimpan 18.000 data master part:
-- - Part 1: SN 001 s/d 999 (3 digit) dan 1000 s/d 6000 (4 digit) (Total 6.000 items)
-- - Part 12: SN 60001 s/d 65000 & 70001 s/d 75000 (10.000 items)
-- - Part 15: SN J1100001 s/d Z1102000 (2.000 items dengan mapping CS)
CREATE TABLE IF NOT EXISTS master_stocks (
    id SERIAL PRIMARY KEY,
    sn VARCHAR(100) NOT NULL,
    cs VARCHAR(100),
    part_type VARCHAR(10) NOT NULL, -- '1', '12', '15'
    status VARCHAR(20) NOT NULL DEFAULT 'Tersedia', -- 'Tersedia', 'Supplied', 'Done'
    allocated_tray_id INTEGER,
    allocated_at TIMESTAMP,
    used_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_master_stocks_sn_type UNIQUE(sn, part_type)
);

CREATE INDEX IF NOT EXISTS idx_master_stocks_type_status ON master_stocks(part_type, status);
CREATE INDEX IF NOT EXISTS idx_master_stocks_tray ON master_stocks(allocated_tray_id);

-- 3. Tabel Batch / Tray Supply Gudang (Trays)
CREATE TABLE IF NOT EXISTS trays (
    id SERIAL PRIMARY KEY,
    tray_code VARCHAR(50) NOT NULL UNIQUE, -- Contoh: 'WH/ASSY/000001' atau 'WH/WO/000001'
    pic_warehouse VARCHAR(100) NOT NULL,
    assigned_operator VARCHAR(100),
    source_document_mo VARCHAR(100),
    product_type VARCHAR(100) DEFAULT 'Nanggala P9',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    verified_by_gudang BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP,
    verified_by_user VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_trays_code ON trays(tray_code);
CREATE INDEX IF NOT EXISTS idx_trays_created_at ON trays(created_at DESC);

-- 4. Tabel Alokasi Part dalam Tray (Parts)
CREATE TABLE IF NOT EXISTS parts (
    id SERIAL PRIMARY KEY,
    tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
    part_type VARCHAR(10) NOT NULL, -- '1', '12', '15'
    sn VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Supplied', -- 'Supplied', 'Used'
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_parts_tray_id ON parts(tray_id);
CREATE INDEX IF NOT EXISTS idx_parts_type_sn ON parts(part_type, sn);

-- 5. Tabel Hasil Pengerjaan Perakitan (Assemblies)
CREATE TABLE IF NOT EXISTS assemblies (
    id SERIAL PRIMARY KEY,
    tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
    set_number INTEGER NOT NULL,
    pic_assembly VARCHAR(100) NOT NULL,
    part15_sn VARCHAR(100) NOT NULL,
    part12_sn VARCHAR(100) NOT NULL,
    part1_sn VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Good', -- 'Good', 'Hold', 'Scrap', 'Rework'
    reason TEXT,
    defective_parts VARCHAR(255),               -- CSV or JSON string: '15,1', '1', '12'
    verified_status VARCHAR(50),                -- 'ACC', 'DECLINED', 'PENDING'
    is_declined BOOLEAN DEFAULT FALSE,
    is_reregistered BOOLEAN DEFAULT FALSE,
    declined_reason TEXT,
    started_at TIMESTAMP,
    finished_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_tray_set UNIQUE(tray_id, set_number)
);

CREATE INDEX IF NOT EXISTS idx_assemblies_tray_id ON assemblies(tray_id);
CREATE INDEX IF NOT EXISTS idx_assemblies_status ON assemblies(status);
CREATE INDEX IF NOT EXISTS idx_assemblies_parts ON assemblies(part15_sn, part12_sn, part1_sn);

-- 6. Tabel Log Audit Trail (Histori Override Pihak Gudang untuk Admin)
CREATE TABLE IF NOT EXISTS part_audit_logs (
    id SERIAL PRIMARY KEY,
    part_type VARCHAR(10) NOT NULL,          -- '1', '12', atau '15'
    sn VARCHAR(100) NOT NULL,                -- Serial Number Part
    old_status VARCHAR(50) NOT NULL,         -- Status sebelum diubah
    new_status VARCHAR(50) NOT NULL,         -- Status setelah diubah
    action_type VARCHAR(50) NOT NULL,        -- 'OVERRIDE_STATUS', 'RE_REGISTER'
    actor_username VARCHAR(100) NOT NULL,    -- User Pihak Gudang / Admin
    actor_role VARCHAR(50) NOT NULL,         -- 'gudang' / 'admin'
    reason TEXT,                             -- Alasan override/kondisi fisik
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audit_created_at ON part_audit_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_sn ON part_audit_logs(sn);

-- 7. Tabel Permohonan Verifikasi Daftar Ulang Part (Scrap -> Admin Approval)
CREATE TABLE IF NOT EXISTS re_register_requests (
    id SERIAL PRIMARY KEY,
    part_type VARCHAR(10) NOT NULL,          -- '1', '12', atau '15'
    sn VARCHAR(100) NOT NULL,                -- Serial Number Part
    current_status VARCHAR(50) NOT NULL,     -- 'Scrap', 'Hold', 'Rework'
    target_status VARCHAR(50) NOT NULL,      -- 'Tersedia', 'Rework', etc.
    requestor_username VARCHAR(100) NOT NULL,
    requestor_role VARCHAR(50) NOT NULL,
    reason TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING', -- 'PENDING', 'APPROVED', 'REJECTED'
    admin_username VARCHAR(100),
    admin_notes TEXT,
    approved_at TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_re_register_status ON re_register_requests(status);
CREATE INDEX IF NOT EXISTS idx_re_register_sn ON re_register_requests(sn);

-- 8. Tabel Audit Downtime & Jeda Pengerjaan Perakitan
CREATE TABLE IF NOT EXISTS assembly_downtimes (
    id SERIAL PRIMARY KEY,
    tray_id INTEGER REFERENCES trays(id) ON DELETE CASCADE,
    tray_code VARCHAR(50) NOT NULL,
    set_number INTEGER,
    operator VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    paused_at TIMESTAMP NOT NULL DEFAULT NOW(),
    resumed_at TIMESTAMP,
    duration_seconds INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_downtimes_tray ON assembly_downtimes(tray_id);
CREATE INDEX IF NOT EXISTS idx_downtimes_operator ON assembly_downtimes(operator);
CREATE INDEX IF NOT EXISTS idx_downtimes_created_at ON assembly_downtimes(created_at DESC);

-- 9. Tabel Riwayat Dokumen WO / Perakitan (Append-Only Event History)
CREATE TABLE IF NOT EXISTS assembly_wo_history (
    id SERIAL PRIMARY KEY,
    tray_id INTEGER NOT NULL REFERENCES trays(id) ON DELETE CASCADE,
    tray_code VARCHAR(50),
    set_number INTEGER NOT NULL,
    pic_assembly VARCHAR(100),
    part15_sn VARCHAR(100),
    part12_sn VARCHAR(100),
    part1_sn VARCHAR(100),
    status VARCHAR(20) NOT NULL DEFAULT 'Good',
    reason TEXT,
    defective_parts VARCHAR(255),
    started_at TIMESTAMP,
    finished_at TIMESTAMP NOT NULL DEFAULT NOW(),
    source VARCHAR(50) DEFAULT 'ASSEMBLY',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_wo_history_tray ON assembly_wo_history(tray_id);
CREATE INDEX IF NOT EXISTS idx_wo_history_parts ON assembly_wo_history(part15_sn, part12_sn, part1_sn);
CREATE INDEX IF NOT EXISTS idx_wo_history_finished ON assembly_wo_history(finished_at DESC);

