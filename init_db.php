<?php

require_once __DIR__ . '/lib/db.php';

$pdo = get_db();

$schema = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    entity_type TEXT,
    entity_id INTEGER,
    details TEXT,
    created_at TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    message TEXT,
    created_at TEXT,
    read_at TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    tag TEXT,
    lead_status TEXT DEFAULT 'yangi',
    notes TEXT,
    last_activity TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS client_addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    label TEXT,
    address TEXT,
    preferences TEXT,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS client_documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    path TEXT,
    description TEXT,
    uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS client_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    note TEXT,
    reminder_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS communications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    channel TEXT,
    summary TEXT,
    occurred_at TEXT,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    points INTEGER,
    type TEXT,
    description TEXT,
    expires_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS loyalty_tiers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    min_points INTEGER,
    benefits TEXT
);

CREATE TABLE IF NOT EXISTS quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    number TEXT,
    status TEXT,
    subtotal REAL,
    tax REAL,
    total REAL,
    currency TEXT DEFAULT 'UZS',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    quote_id INTEGER,
    number TEXT,
    status TEXT,
    subtotal REAL,
    tax REAL,
    total REAL,
    currency TEXT DEFAULT 'UZS',
    due_date TEXT,
    paid_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id),
    FOREIGN KEY(quote_id) REFERENCES quotes(id)
);

CREATE TABLE IF NOT EXISTS invoice_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER,
    amount REAL,
    method TEXT,
    paid_at TEXT,
    reference TEXT,
    FOREIGN KEY(invoice_id) REFERENCES invoices(id)
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    base_price REAL,
    price_per_sqm REAL,
    description TEXT,
    location TEXT,
    client_type TEXT,
    bundle TEXT,
    seasonal_notes TEXT,
    promo_code TEXT,
    recurrence_discount REAL
);

CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    service_id INTEGER,
    crew TEXT,
    status TEXT,
    scheduled_at TEXT,
    end_at TEXT,
    recurring_rule TEXT,
    geo_start TEXT,
    geo_end TEXT,
    route_url TEXT,
    urgent INTEGER DEFAULT 0,
    notes TEXT,
    FOREIGN KEY(client_id) REFERENCES clients(id),
    FOREIGN KEY(service_id) REFERENCES services(id)
);

CREATE TABLE IF NOT EXISTS job_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER NOT NULL,
    stage TEXT NOT NULL,
    happened_at TEXT DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    created_by INTEGER,
    FOREIGN KEY(job_id) REFERENCES jobs(id),
    FOREIGN KEY(created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS job_financials (
    job_id INTEGER PRIMARY KEY,
    amount REAL DEFAULT 0,
    due_date TEXT,
    status TEXT DEFAULT 'awaiting',
    paid_at TEXT,
    FOREIGN KEY(job_id) REFERENCES jobs(id)
);

CREATE TABLE IF NOT EXISTS job_financial_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    amount REAL,
    status TEXT,
    note TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(job_id) REFERENCES jobs(id)
);

CREATE TABLE IF NOT EXISTS job_photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    path TEXT,
    type TEXT,
    uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(job_id) REFERENCES jobs(id)
);

CREATE TABLE IF NOT EXISTS checklists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    description TEXT
);

CREATE TABLE IF NOT EXISTS job_qc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    checklist_id INTEGER,
    score INTEGER,
    result TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(job_id) REFERENCES jobs(id),
    FOREIGN KEY(checklist_id) REFERENCES checklists(id)
);

CREATE TABLE IF NOT EXISTS reworks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id INTEGER,
    issue TEXT,
    penalty REAL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(job_id) REFERENCES jobs(id)
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    category TEXT,
    stock INTEGER,
    min_stock INTEGER,
    cost REAL,
    supplier TEXT,
    maintenance_schedule TEXT,
    serial_number TEXT,
    warranty TEXT
);

CREATE TABLE IF NOT EXISTS inventory_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER,
    change_qty INTEGER,
    reason TEXT,
    job_id INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(item_id) REFERENCES inventory_items(id),
    FOREIGN KEY(job_id) REFERENCES jobs(id)
);

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier TEXT,
    total REAL,
    status TEXT,
    ordered_at TEXT,
    received_at TEXT
);

CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    role TEXT,
    team TEXT,
    hourly_rate REAL,
    share_rate REAL,
    salary_type TEXT,
    bonus REAL,
    penalty REAL,
    loan REAL,
    advance REAL,
    warnings TEXT,
    certifications TEXT,
    contract_path TEXT
);

CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER,
    check_in TEXT,
    check_out TEXT,
    FOREIGN KEY(staff_id) REFERENCES staff(id)
);

CREATE TABLE IF NOT EXISTS payroll_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER,
    period_start TEXT,
    period_end TEXT,
    gross REAL,
    net REAL,
    notes TEXT,
    FOREIGN KEY(staff_id) REFERENCES staff(id)
);

CREATE TABLE IF NOT EXISTS leaves (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER,
    type TEXT,
    start_date TEXT,
    end_date TEXT,
    status TEXT,
    FOREIGN KEY(staff_id) REFERENCES staff(id)
);

CREATE TABLE IF NOT EXISTS client_portal_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER,
    token TEXT,
    expires_at TEXT,
    channel TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    channel TEXT,
    audience TEXT,
    template TEXT,
    status TEXT,
    scheduled_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS campaign_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER,
    client_id INTEGER,
    sent_at TEXT,
    response TEXT,
    FOREIGN KEY(campaign_id) REFERENCES marketing_campaigns(id),
    FOREIGN KEY(client_id) REFERENCES clients(id)
);

CREATE TABLE IF NOT EXISTS reports_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT,
    filters TEXT,
    emailed_to TEXT,
    generated_at TEXT,
    path TEXT
);
SQL;

$pdo->exec($schema);

$exists = fetch_one('SELECT id FROM users WHERE username = ?', ['owner']);
if (!$exists) {
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute(['owner', password_hash('owner123', PASSWORD_DEFAULT), 'owner']);
}

echo "Bazaga sozlamalar qo'yildi.\n";
