BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    role TEXT NOT NULL,
    phone TEXT,
    email TEXT UNIQUE,
    password_hash TEXT NOT NULL,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);

CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL
);

CREATE TABLE role_permission (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    source TEXT,
    stage TEXT,
    rating INTEGER,
    tags TEXT,
    phone TEXT,
    email TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lead_id INTEGER,
    name TEXT NOT NULL,
    status TEXT DEFAULT 'active',
    phone TEXT,
    email TEXT,
    preferred_days TEXT,
    preferred_time TEXT,
    tags TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    label TEXT,
    address_line TEXT,
    latitude REAL,
    longitude REAL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    base_price INTEGER NOT NULL,
    duration_min INTEGER NOT NULL,
    active INTEGER DEFAULT 1
);

CREATE TABLE price_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER,
    client_tier TEXT,
    min_qty INTEGER,
    day_of_week TEXT,
    time_range TEXT,
    discount_pct INTEGER,
    surcharge_pct INTEGER
);

CREATE TABLE quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    items_json TEXT,
    validity TEXT,
    accepted_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    start_at TEXT,
    end_at TEXT,
    cancellation_terms TEXT,
    attachment_path TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    address_id INTEGER NOT NULL,
    status TEXT DEFAULT 'draft',
    scheduled_at TEXT,
    duration INTEGER,
    subtotal INTEGER DEFAULT 0,
    discount INTEGER DEFAULT 0,
    surcharge INTEGER DEFAULT 0,
    tax INTEGER DEFAULT 0,
    total INTEGER DEFAULT 0,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    qty INTEGER NOT NULL,
    unit_price INTEGER NOT NULL,
    line_total INTEGER NOT NULL
);

CREATE TABLE assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT,
    planned_minutes INTEGER,
    actual_minutes INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE checklists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER,
    name TEXT NOT NULL,
    template_json TEXT
);

CREATE TABLE checklist_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    item TEXT,
    done INTEGER DEFAULT 0,
    note TEXT,
    updated_at TEXT
);

CREATE TABLE photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    kind TEXT,
    path TEXT,
    taken_at TEXT
);

CREATE TABLE recurrences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    address_id INTEGER NOT NULL,
    rrule TEXT NOT NULL,
    next_run_at TEXT,
    defaults_json TEXT,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    invoice_no TEXT,
    status TEXT,
    issued_at TEXT,
    due_at TEXT,
    paid_at TEXT,
    total INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER,
    invoice_id INTEGER,
    amount INTEGER NOT NULL,
    method TEXT,
    txn_ref TEXT,
    received_at TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT,
    amount INTEGER NOT NULL,
    paid_at TEXT,
    note TEXT,
    vendor TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    period TEXT,
    amount INTEGER NOT NULL,
    method TEXT,
    paid_at TEXT,
    note TEXT
);

CREATE TABLE inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku TEXT,
    name TEXT NOT NULL,
    unit TEXT,
    min_stock INTEGER DEFAULT 0,
    current_stock INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_txn (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    type TEXT,
    qty INTEGER,
    cost_tiyin INTEGER,
    reason TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    day TEXT,
    check_in TEXT,
    check_out TEXT,
    method TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    score INTEGER,
    comment TEXT,
    source TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    channel TEXT,
    recipient TEXT,
    template_key TEXT,
    payload_json TEXT,
    status TEXT,
    scheduled_at TEXT,
    sent_at TEXT,
    error TEXT
);

CREATE TABLE integrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT UNIQUE NOT NULL,
    value_encrypted TEXT
);

CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    entity TEXT,
    meta_json TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments_unique (
    method TEXT,
    txn_ref TEXT,
    UNIQUE(method, txn_ref)
);

INSERT INTO users (name, role, phone, email, password_hash) VALUES ('Admin', 'owner', '+998900000000', 'admin@cleantrack.test', '$2y$12$UQ3iaS4pS1XpAK2gOb3FD.0DvJlnyKJw6n0h6i8XPi5sRzVgnBXKq');
COMMIT;
