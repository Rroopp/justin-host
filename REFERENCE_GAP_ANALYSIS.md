# Reference Gap Analysis (FastAPI/React → Laravel/Blade)

This document compares the reference system:
- **Backend reference**: `/home/kimrop/Downloads/justinepos-main (1)/justinepos-main/backend`
- **Frontend reference**: `/home/kimrop/Downloads/justinepos-main (1)/justinepos-main/frontend`

…against the current Laravel implementation:
- **Laravel app**: `larevel-version/` (this repo)

The goal is to list **features and behaviors implemented in the reference** that are **missing or incomplete** in `larevel-version`. This will be the single roadmap reference for closing gaps.

---

## 1) Real‑time updates + synchronization (missing)

### What exists in reference
- **WebSocket inventory channel**: `GET ws://<host>:8000/ws/inventory`
  - Reference: `backend/main.py` (`@app.websocket("/ws/inventory")`)
- **Data synchronization manager** (push/subscribe model, event types like inventory update, sale created, payment recorded)
  - Reference: `backend/data_sync_manager.py`, `backend/transaction_manager.py`
- **Frontend websocket usage** to update dashboard/sales/inventory without manual refresh
  - Reference: `frontend/src/hooks/useWebSocket.js`, `frontend/src/pages/ProfessionalDashboard.jsx`, `frontend/src/pages/Sales.jsx`
- **Client-side DataSync manager** (cache + subscriptions to invalidate/refresh sections)
  - Reference: `frontend/src/utils/dataSync.js`

### What’s missing in `larevel-version`
- No websocket endpoints (no `/ws/*`)
- No broadcast/event system for inventory/sales changes
- No “live updating” dashboard widgets / sales / low stock

### Suggested Laravel targets
- **Backend**: Laravel Broadcasting (Redis/Pusher) or a simple websocket server (beyond basic PHP-FPM). If staying “simple”, implement polling; if matching reference, implement websockets.
- **Frontend (Blade/Alpine)**: add a lightweight polling or SSE/WebSocket client to refresh:
  - low stock alerts
  - recent sales
  - dashboard KPIs

---

## 2) Enterprise Settings features (missing / partial)

### What exists in reference
- **Company info management** (company name/address/phone/email/registration/tax)
  - Reference: `backend/settings_router.py` (`/settings/company`, `/settings/system`, `/settings/security`)
- **Company logo upload**
  - Reference: `backend/settings_router.py` (`POST /settings/company/logo`) + `uploads/logos/*`
- **User preferences** (theme, UI preferences, per-user saved values)
  - Reference: `backend/settings_router.py` (`GET/PUT /settings/user/preferences`)
  - Reference UI: theme stored in `localStorage`, `frontend/src/ThemeContext.jsx`
- **Settings audit log**
  - Reference: `backend/settings_router.py` (`GET /settings/audit-log`)
- **Backup**
  - Reference: `backend/settings_router.py` (`POST /settings/backup`)
- **Module toggles** (enable/disable modules like orders/staff/accounting/etc.)
  - Reference: `backend/settings_router.py` (`GET/PUT /settings/modules`)

### What’s missing in `larevel-version`
- Current `SettingsController` is a basic key/value editor (`settings` table) but lacks:
  - company info form + validation
  - logo upload + storage
  - per-user preferences
  - settings audit log
  - backup tooling
  - module enable/disable toggles enforced across routes/UI

### Suggested Laravel targets
- Add tables:
  - `user_preferences` (staff_id, key, value JSON, updated_at)
  - `settings_audit_log` (key, old_value, new_value, changed_by, reason, timestamp)
- Extend `SettingsController` + `resources/views/settings/index.blade.php` into tabs:
  - System, Company, Security, Preferences, Modules
- Add logo upload via `Storage::disk('public')` and store URL in settings.

---

## 3) Accounting depth (missing / partial)

### What exists in reference
- Full report suite:
  - Trial balance: `GET /api/accounting/reports/trial-balance`
  - Balance sheet: `GET /api/accounting/reports/balance-sheet`
  - Profit & loss: `GET /api/accounting/reports/profit-loss`
  - Cash flow: `GET /api/accounting/reports/cash-flow`
  - Aging report (A/R): `GET /api/accounting/reports/aging-report`
  - Per-account ledger: `GET /api/accounting/accounts/{account_id}/ledger`
  - Reference: `backend/enhanced_accounting.py`
- Auto journal posting for sales:
  - Reference: `backend/enhanced_accounting.py` (`/auto-journal-entry/sale/{sale_id}`) and also POS flow in `backend/main.py`
- “Accounts dashboard” metrics:
  - Reference: `backend/main.py` (`GET /api/financial-metrics`)

### What’s missing in `larevel-version`
- We have `AccountingController` with:
  - chart of accounts CRUD
  - journal entries + posting
  - trial balance + basic financial statements
- Missing relative to reference:
  - cash-flow statement
  - AR aging report
  - account ledger view/report
  - automatic journal entries triggered by POS sales (we do not auto-post accounting from POS)
  - “record payment” workflow for invoices and corresponding accounting entries

### Suggested Laravel targets
- Add endpoints + UI pages for:
  - account ledger
  - aging report
  - cash-flow
- Extend POS flow:
  - on sale completion (or later “post” action) auto-create a journal entry for posted sales.

---

## 4) Invoice / Credit lifecycle + payment recording (missing)

### What exists in reference
- Explicit payment recording endpoint for invoices:
  - Reference: `backend/main.py` (`POST /api/payments`)
  - Updates: `pos_sales.payment_status`, `payment_method`, `payment_date`, `payment_reference`, `payment_notes`
- Frontend supports multiple payment method labels beyond ours (e.g. Card, Mobile Money, Bank Transfer) and document modals:
  - Reference: `frontend/src/pages/EnhancedPOS.jsx`, `frontend/src/components/DocumentModal.jsx`

### What’s missing in `larevel-version`
- We store `payment_date`, `payment_reference`, `payment_notes` columns in `pos_sales`, but we do not provide:
  - UI or endpoint to **record payment** for an invoice/credit sale
  - payment status transitions: pending → partial → paid
  - validation around partial payments
  - optional customer credit/balance updates tied to payment

### Suggested Laravel targets
- Add `PaymentsController` or extend `POSController`:
  - `POST /payments` or `POST /pos-sales/{id}/payments`
  - update `pos_sales` payment fields + optionally create accounting journal entry
- Add UI on Sales page to “Record Payment” for invoices.

---

## 5) Orders module enhancements (missing)

### What exists in reference
- Orders dashboards and suggestions:
  - `GET /orders/dashboard`
  - suggestions:
    - `/orders/suggestions/top-selling`
    - `/orders/suggestions/low-stock`
    - `/orders/suggestions/by-supplier/{supplier_id}`
  - inventory health: `/orders/inventory/health`
  - Reference: `backend/orders_router.py`
- Order templates:
  - `GET/POST /orders/templates/`

### What’s missing in `larevel-version`
- We have a working `OrderController` and suppliers, but missing:
  - top-selling suggestions
  - supplier-based suggestions
  - inventory health analytics page
  - templates UI/workflow (even if table exists)

---

## 6) Staff module depth (partial)

### What exists in reference
- Staff endpoints beyond CRUD:
  - `/staff/roles/available`, `/staff/statuses/available`
  - `/staff/{id}/reset-password`
  - `/staff/{id}/roles` add/remove, set primary role
  - staff dashboard stats
  - Reference: `backend/staff_router.py`

### What’s missing in `larevel-version`
- We have CRUD, multi-role UI, and activity logging.
- Missing relative to reference:
  - dedicated endpoints for reset-password / role management (we embed in update)
  - staff dashboard stats endpoint
  - role/status “available options” endpoint (not critical but useful)

---

## 7) Assets + Payroll modules (missing)

### What exists in reference
- **Assets router**:
  - `GET /assets`, `POST /assets`, `POST /assets/{id}/allocate`, `DELETE /assets/{id}`
  - Reference: `backend/assets_router.py`
- **Payroll router**:
  - Reference: `backend/payroll_router.py`

### What’s missing in `larevel-version`
- No `AssetsController`, no `PayrollController`, no views/routes for these modules.

---

## 8) Advanced POS UX features (missing / partial)

### What exists in reference UI
- Rich POS experience:
  - customer autocomplete + add dialog
  - discount % input and VAT % input
  - quick actions: save cart / load cart
  - document modal with Receipt/Invoice/Delivery Note print components
  - Reference: `frontend/src/pages/EnhancedPOS.jsx`, `frontend/src/components/DocumentModal.jsx`

### What’s missing in `larevel-version`
- We have:
  - cart + stock validation
  - customer combobox + add customer
  - receipt print page (basic)
- Missing relative to reference:
  - editable discount % (currently hardcoded to 0 in POS view)
  - configurable VAT rate (reference supports variable VAT%)
  - save/load cart
  - delivery note generation workflow (we store fields but no generation endpoint/page)
  - richer document template usage (template-driven formatting)

---

## 9) Document templates integration (partial)

### What exists in reference
- Templates are used for documents (receipt/invoice/delivery note) and managed via settings/templates endpoints.
- Reference: `backend/settings_router.py` + `frontend/src/components/DocumentModal.jsx`

### What’s missing in `larevel-version`
- We have `DocumentTemplateController` and a templates page, but:
  - templates are not applied to printed documents
  - no “set default template” usage in POS print flow

---

## 10) Priority recommendations (what to build next)

### MVP-critical (highest)
- **Real invoice payment recording** (pending/partial/paid + references) + UI
- **Realtime updates** (at least polling; ideally websockets) for inventory alerts + dashboard
- **Discount/VAT controls** in POS (data capture must match reference)

### High value
- Orders suggestions + inventory health
- Accounting: ledger + aging + cash flow
- Settings: company info + logo + preferences + module toggles + audit log

### Nice-to-have / later
- Save/load cart
- Full template-driven document rendering
- Assets + payroll


