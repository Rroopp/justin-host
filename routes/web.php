<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\RentalController;

// Login page
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

// Login handler
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Current authenticated user (handy for frontend)
Route::get('/me', [AuthController::class, 'me'])->middleware('auth');

// Protected routes
// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    
    // Notifications
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Audit Logs (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/export', [App\Http\Controllers\AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/audit-logs/{id}', [App\Http\Controllers\AuditLogController::class, 'show'])->name('audit-logs.show');
    });
    
    // Banking & Cash Management
    Route::prefix('accounting/banking')->middleware(['auth', 'role:admin,accountant'])->name('banking.')->group(function () {
        Route::get('/', [App\Http\Controllers\BankingController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\BankingController::class, 'show'])->name('show');
        Route::post('/deposit', [App\Http\Controllers\BankingController::class, 'storeDeposit'])->name('deposit');
        Route::post('/transfer', [App\Http\Controllers\BankingController::class, 'storeTransfer'])->name('transfer');
        Route::post('/expense', [App\Http\Controllers\BankingController::class, 'storeExpense'])->name('expense');
    });

    // POS (Staff & Admin)
    Route::get('/pos/search', [POSController::class, 'search'])->name('pos.search')->middleware(['role:admin,staff']);
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index')->middleware(['role:admin,staff', 'permission:pos.access']);
    Route::get('/receipts/{id}/print', [POSController::class, 'printReceipt'])->name('receipts.print')->middleware('role:admin,staff');
    Route::get('/pos/receipt/{id}', [POSController::class, 'getReceipt'])->name('pos.receipt.data')->middleware('auth');
    Route::get('/pos/receipt-view/{id}', [POSController::class, 'printReceipt'])->name('pos.receipt.view')->middleware('auth');
    Route::post('/pos/cart/save', [POSController::class, 'saveCart'])->name('pos.cart.save')->middleware('auth');
    Route::get('/pos/cart/list', [POSController::class, 'listCarts'])->name('pos.cart.list')->middleware('auth');
    Route::delete('/pos/cart/{id}', [POSController::class, 'deleteCart'])->name('pos.cart.delete')->middleware('auth');
    
    // Inventory (Staff & Admin)
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store')->middleware(['role:admin,staff', 'permission:inventory.edit']);
    Route::group(['middleware' => ['role:admin,staff']], function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:inventory.view');
        Route::get('/inventory/categories', [CategoryController::class, 'index'])->name('inventory.categories')->middleware('permission:inventory.view');
        Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update')->middleware('permission:inventory.edit');
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy')->middleware('permission:inventory.edit');
    });
    
    // Sales Reports / Invoices (Accountant & Admin)
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index')->middleware(['role:admin,accountant,staff', 'permission:sales.view']);
    Route::get('/sales/invoices', [SalesController::class, 'invoices'])->name('sales.invoices.index')->middleware('role:admin,accountant');
    Route::get('/sales/invoices/summary', [SalesController::class, 'summaryInvoice'])->name('sales.invoices.summary')->middleware('role:admin,accountant');
    Route::get('/sales/invoices/pending/{customerId}', [SalesController::class, 'getPendingInvoices'])->name('sales.invoices.pending')->middleware('auth');
    Route::get('/sales/invoices/summary/print', [SalesController::class, 'printSummaryInvoice'])->name('sales.invoices.summary.print')->middleware('role:admin,accountant');
    Route::get('/sales/invoices/{sale}', [SalesController::class, 'showInvoice'])->name('sales.invoices.show')->middleware('role:admin,accountant');
    Route::post('/sales/invoices/{sale}/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('sales.invoices.payments.store')->middleware('role:admin,accountant,staff');
    Route::post('/sales/{sale}/update-payment-method', [SalesController::class, 'updatePaymentMethod'])->name('sales.update-payment-method')->middleware('role:admin,accountant,staff');
    
    // Refunds (Staff can request, Admin can approve)
    Route::get('/sales/{sale}/refund', [App\Http\Controllers\RefundController::class, 'create'])->name('refunds.create')->middleware('role:admin,staff,accountant');
    Route::post('/sales/{sale}/refund', [App\Http\Controllers\RefundController::class, 'store'])->name('refunds.store')->middleware('role:admin,staff,accountant');
    Route::get('/refunds', [App\Http\Controllers\RefundController::class, 'index'])->name('refunds.index')->middleware('role:admin,accountant');
    Route::get('/refunds/export', [App\Http\Controllers\RefundController::class, 'export'])->name('refunds.export')->middleware('role:admin');
    Route::get('/refunds/{refund}', [App\Http\Controllers\RefundController::class, 'show'])->name('refunds.show')->middleware('role:admin,staff,accountant');
    Route::post('/refunds/{refund}/approve', [App\Http\Controllers\RefundController::class, 'approve'])->name('refunds.approve')->middleware('role:admin');
    Route::post('/refunds/{refund}/reject', [App\Http\Controllers\RefundController::class, 'reject'])->name('refunds.reject')->middleware('role:admin');
    
    // Surgery Consignments (Staff & Admin - assuming staff needed to operate)
    // Surgery Consignments (Staff, Admin & Accountant)
    // Consignment Sales Reconciliation
    Route::get('/sales/consignments', [App\Http\Controllers\ConsignmentSaleController::class, 'index'])->name('sales.consignments.index')->middleware('role:admin,accountant,staff');
    Route::get('/sales/consignments/{sale}', [App\Http\Controllers\ConsignmentSaleController::class, 'show'])->name('sales.consignments.show')->middleware('role:admin,accountant,staff');
    Route::post('/sales/consignments/{sale}/reconcile', [App\Http\Controllers\ConsignmentSaleController::class, 'reconcile'])->name('sales.consignments.reconcile')->middleware('role:admin,accountant,staff');

    // Customers (Accessible to most for POS/Sales)
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index')->middleware('role:admin,staff,accountant');
    
    // Orders (Staff & Admin)
    // Orders (Staff & Admin - Staff can view, but creation is restricted below or via controller)
    // Actually, splitting resource would be cleaner, but for now we rely on controller policy or separated routes if needed.
    // However, existing resource usage or index usage suggests we just protect the routes.
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')->middleware('role:admin,staff,accountant');
    Route::get('/orders/create-direct', [OrderController::class, 'createDirect'])->name('orders.create-direct')->middleware('role:admin,accountant,staff');
    Route::post('/orders/direct', [OrderController::class, 'storeDirect'])->name('orders.store-direct')->middleware('role:admin,accountant,staff');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store')->middleware('role:admin,accountant');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create')->middleware('role:admin,accountant');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status')->middleware('role:admin,accountant');
    Route::post('/orders/{order}/pay', [OrderController::class, 'storePayment'])->name('orders.pay')->middleware('role:admin,accountant');
    
    Route::get('/orders/suggestions', [App\Http\Controllers\OrderSuggestionsController::class, 'index'])->name('orders.suggestions.index')->middleware('role:admin,staff,accountant');
    Route::get('/orders/suggestions/top-selling', [App\Http\Controllers\OrderSuggestionsController::class, 'topSelling'])->name('orders.suggestions.top-selling')->middleware('role:admin,staff,accountant');
    Route::get('/orders/suggestions/low-stock', [App\Http\Controllers\OrderSuggestionsController::class, 'lowStock'])->name('orders.suggestions.low-stock')->middleware('role:admin,staff,accountant');
    Route::get('/orders/suggestions/by-supplier', [App\Http\Controllers\OrderSuggestionsController::class, 'bySupplier'])->name('orders.suggestions.by-supplier')->middleware('role:admin,staff,accountant');
    
    // Payroll (Admin Only - typically sensitive)
    Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payroll.approve')->middleware('role:admin');
    Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay')->middleware('role:admin');
    Route::resource('payroll', PayrollController::class)->middleware('role:admin');

    // Assets (Admin Only)
    Route::resource('assets', AssetController::class)->middleware('role:admin');
    
    // Inventory Health
    Route::get('/inventory/health', [App\Http\Controllers\InventoryHealthController::class, 'index'])->name('inventory.health')->middleware('role:admin');
    
    // Inventory Movements (Movement History & Audit Trail)
    Route::get('/inventory/movements', [App\Http\Controllers\InventoryMovementController::class, 'index'])->name('inventory.movements.index')->middleware('role:admin,accountant');
    Route::get('/inventory/movements/{movement}', [App\Http\Controllers\InventoryMovementController::class, 'show'])->name('inventory.movements.show')->middleware('role:admin,accountant');
    
    // Inventory Valuation Dashboard
    Route::get('/inventory/valuation', [App\Http\Controllers\InventoryValuationController::class, 'index'])->name('inventory.valuation.index')->middleware('role:admin,accountant');

    // Case Reservations (Surgery Management)
    Route::get('/reservations/search-items', [App\Http\Controllers\CaseReservationController::class, 'searchItems'])->name('reservations.search_items')->middleware('role:admin,staff');
    Route::resource('reservations', App\Http\Controllers\CaseReservationController::class)->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/add-set', [App\Http\Controllers\CaseReservationController::class, 'addSet'])->name('reservations.add_set')->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/items', [App\Http\Controllers\CaseReservationController::class, 'addItem'])->name('reservations.items.add')->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/add-package', [App\Http\Controllers\CaseReservationController::class, 'addPackage'])->name('reservations.add_package')->middleware('role:admin,staff');
    Route::delete('/reservations/{reservation}/items/{item}', [App\Http\Controllers\CaseReservationController::class, 'removeItem'])->name('reservations.items.remove')->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/confirm', [App\Http\Controllers\CaseReservationController::class, 'confirm'])->name('reservations.confirm')->middleware('role:admin,staff');
    Route::get('/reservations/{reservation}/confirm', function ($reservation) { return redirect()->route('reservations.show', $reservation); })->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/complete', [App\Http\Controllers\CaseReservationController::class, 'complete'])->name('reservations.complete')->middleware('role:admin,staff');
    Route::post('/reservations/{reservation}/cancel', [App\Http\Controllers\CaseReservationController::class, 'cancel'])->name('reservations.cancel')->middleware('role:admin,staff');
    
    // Set Dispatch Workflow
    Route::get('/reservations/{id}/dispatch', [App\Http\Controllers\SetDispatchController::class, 'create'])->name('dispatch.create')->middleware('role:admin,staff');
    Route::post('/reservations/{id}/dispatch', [App\Http\Controllers\SetDispatchController::class, 'store'])->name('dispatch.store')->middleware('role:admin,staff');
    Route::get('/reservations/{id}/reconcile-set', [App\Http\Controllers\SetDispatchController::class, 'reconcile'])->name('reconcile.create')->middleware('role:admin,staff');
    Route::post('/reservations/{id}/reconcile-set', [App\Http\Controllers\SetDispatchController::class, 'storeReconciliation'])->name('reconcile.store')->middleware('role:admin,staff');
    
    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index')->middleware('role:admin,staff');

    // Surgical Sets (Location-Assets)
    Route::resource('sets', App\Http\Controllers\SetController::class)->middleware('role:admin,staff');
    
    // Staff (Admin Only)
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index')->middleware('role:admin');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store')->middleware('role:admin');
    Route::get('/staff/{staff}', [StaffController::class, 'show'])->name('staff.show')->middleware('role:admin');
    Route::post('/staff/{staff}/deductions', [App\Http\Controllers\StaffDeductionController::class, 'store'])->name('staff.deductions.store')->middleware('role:admin');
    Route::delete('/staff/{staff}/deductions/{deduction}', [App\Http\Controllers\StaffDeductionController::class, 'destroy'])->name('staff.deductions.destroy')->middleware('role:admin');

    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update')->middleware('role:admin');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy')->middleware('role:admin');
    Route::post('/staff/{staff}/suspend', [StaffController::class, 'suspend'])->name('staff.suspend')->middleware('role:admin');
    Route::post('/staff/{staff}/reinstate', [StaffController::class, 'reinstate'])->name('staff.reinstate')->middleware('role:admin');
    
    // Staff Reimbursements (All authenticated staff can submit, Admin can approve)
    Route::resource('reimbursements', App\Http\Controllers\StaffReimbursementController::class);
    Route::post('/reimbursements/{reimbursement}/approve', [App\Http\Controllers\StaffReimbursementController::class, 'approve'])
        ->name('reimbursements.approve')->middleware('role:admin');
    Route::post('/reimbursements/{reimbursement}/reject', [App\Http\Controllers\StaffReimbursementController::class, 'reject'])
        ->name('reimbursements.reject')->middleware('role:admin');
    Route::post('/reimbursements/{reimbursement}/mark-paid', [App\Http\Controllers\StaffReimbursementController::class, 'markAsPaid'])
        ->name('reimbursements.mark-paid')->middleware('role:admin');
    
    // Stock Takes (Admin, Accountant)
    Route::get('/stock-takes', [App\Http\Controllers\StockTakeController::class, 'index'])->name('stock-takes.index')->middleware('role:admin,accountant');
    Route::get('/stock-takes/create', [App\Http\Controllers\StockTakeController::class, 'create'])->name('stock-takes.create')->middleware('role:admin,accountant');
    Route::post('/stock-takes', [App\Http\Controllers\StockTakeController::class, 'store'])->name('stock-takes.store')->middleware('role:admin,accountant');
    Route::get('/stock-takes/{stockTake}', [App\Http\Controllers\StockTakeController::class, 'show'])->name('stock-takes.show')->middleware('role:admin,accountant');
    Route::get('/stock-takes/{stockTake}/sheet', [App\Http\Controllers\StockTakeController::class, 'generateSheet'])->name('stock-takes.sheet')->middleware('role:admin,accountant');
    Route::post('/stock-takes/{stockTake}/counts', [App\Http\Controllers\StockTakeController::class, 'updateCounts'])->name('stock-takes.update-counts')->middleware('role:admin,accountant');
    Route::post('/stock-takes/{stockTake}/complete', [App\Http\Controllers\StockTakeController::class, 'complete'])->name('stock-takes.complete')->middleware('role:admin,accountant');
    Route::post('/stock-takes/{stockTake}/reconcile', [App\Http\Controllers\StockTakeController::class, 'reconcile'])->name('stock-takes.reconcile')->middleware('role:admin');
    Route::get('/stock-takes/{stockTake}/export', [App\Http\Controllers\StockTakeController::class, 'export'])->name('stock-takes.export')->middleware('role:admin,accountant');
    
    // Settings (Admin Only)
    // Settings (Admin, Staff, Accountant - handled in controller for granular access)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('role:admin,staff,accountant');
    
    // User Preferences (available to all authenticated users)
    Route::get('/settings/user/preferences', [SettingsController::class, 'userPreferences'])->name('settings.user.preferences');
    Route::put('/settings/user/preferences', [SettingsController::class, 'updateUserPreferences'])->name('settings.user.preferences.update');
    
    // Settings - Company (admin only)
    Route::get('/settings/company', [SettingsController::class, 'company'])->name('settings.company')->middleware('role:admin');
    Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update')->middleware('role:admin');
    Route::post('/settings/company/logo', [SettingsController::class, 'uploadCompanyLogo'])->name('settings.company.logo')->middleware('role:admin');
    
    // Settings - Modules (admin only)
    Route::get('/settings/modules', [SettingsController::class, 'modules'])->name('settings.modules')->middleware('role:admin');
    Route::put('/settings/modules', [SettingsController::class, 'updateModules'])->name('settings.modules.update')->middleware('role:admin');
    
    // Settings - Audit Log (admin only)
    Route::get('/settings/audit-log', [SettingsController::class, 'auditLog'])->name('settings.audit-log')->middleware('role:admin');
    
    // Settings API
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('role:admin');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('role:admin');
    Route::get('/settings/company', [SettingsController::class, 'company'])->name('settings.company')->middleware('role:admin');
    Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update')->middleware('role:admin');
    Route::get('/settings/modules', [SettingsController::class, 'modules'])->name('settings.modules')->middleware('role:admin');
    Route::put('/settings/modules', [SettingsController::class, 'updateModules'])->name('settings.modules.update')->middleware('role:admin');
    Route::get('/settings/user/preferences', [SettingsController::class, 'userPreferences'])->name('settings.preferences');
    Route::put('/settings/user/preferences', [SettingsController::class, 'updateUserPreferences'])->name('settings.preferences.update');
    Route::get('/settings/audit-log', [SettingsController::class, 'auditLog'])->name('settings.audit')->middleware('role:admin');
    Route::get('/settings/permissions', [SettingsController::class, 'getPermissions'])->name('settings.permissions')->middleware('role:admin');
    Route::put('/settings/permissions/{staff}', [SettingsController::class, 'updatePermissions'])->name('settings.permissions.update')->middleware('role:admin');
    
    // Audit Log API Routes (admin only)
    Route::get('/api/audit-logs', [SettingsController::class, 'getAuditLogs'])->name('api.audit-logs')->middleware('role:admin');
    Route::get('/api/audit-logs/modules', [SettingsController::class, 'getAuditModules'])->name('api.audit-logs.modules')->middleware('role:admin');
    Route::get('/api/audit-logs/actions', [SettingsController::class, 'getAuditActions'])->name('api.audit-logs.actions')->middleware('role:admin');
    Route::get('/api/audit-logs/users', [SettingsController::class, 'getAuditUsers'])->name('api.audit-logs.users')->middleware('role:admin');
    Route::get('/api/audit-logs/export', [SettingsController::class, 'exportAuditLogs'])->name('api.audit-logs.export')->middleware('role:admin');

    // Settings - Backup (admin only)
    Route::post('/settings/backup', [SettingsController::class, 'backup'])->name('settings.backup')->middleware('role:admin');
    
    // Document Templates
    Route::get('/document-templates', [DocumentTemplateController::class, 'index'])->name('document-templates.index')->middleware('role:admin');
    
    // Accounting (Accountant & Admin)
    Route::get('/accounting', [AccountingController::class, 'index'])->name('accounting.index')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/journal-entries', [AccountingController::class, 'journalEntries'])->name('accounting.journal-entries')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/trial-balance', [AccountingController::class, 'trialBalance'])->name('accounting.trial-balance')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/financial-statements', [AccountingController::class, 'financialStatements'])->name('accounting.financial-statements')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/ledger/{account}', [AccountingController::class, 'ledger'])->name('accounting.ledger')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/aging-report', [AccountingController::class, 'agingReport'])->name('accounting.aging-report')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::get('/accounting/cash-flow', [AccountingController::class, 'cashFlow'])->name('accounting.cash-flow')->middleware(['role:admin,accountant', 'permission:finance.view']);
    
    // Chart of Accounts CRUD (Consumed by Vue/Alpine)
    Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'index'])->middleware('role:admin,accountant');
    Route::post('/accounting/chart-of-accounts', [AccountingController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/accounting/chart-of-accounts/{chartOfAccount}', [AccountingController::class, 'updateAccount'])->middleware('role:admin,accountant');
    Route::post('/accounting/chart-of-accounts/{chartOfAccount}/toggle-active', [AccountingController::class, 'toggleAccountActive'])->middleware('role:admin,accountant');
    Route::delete('/accounting/chart-of-accounts/{chartOfAccount}', [AccountingController::class, 'destroyAccount'])->middleware('role:admin,accountant');

    // Transfers (Accounting)
    Route::get('/accounting/transfers/create', [App\Http\Controllers\Accounting\TransferController::class, 'create'])->name('accounting.transfers.create')->middleware(['role:admin,accountant']);
    Route::post('/accounting/transfers', [App\Http\Controllers\Accounting\TransferController::class, 'store'])->name('accounting.transfers.store')->middleware(['role:admin,accountant']);

    // Stock Transfers (Inventory / Consignment)
    Route::get('/stock-transfers', [App\Http\Controllers\StockTransferController::class, 'view'])->name('stock-transfers.index')->middleware('role:admin,staff');
    Route::get('/stock-transfers/create', [App\Http\Controllers\StockTransferController::class, 'createView'])->name('stock-transfers.create')->middleware('role:admin,staff');
    
    // JSON Endpoints for Stock Transfers (Session Auth)
    Route::get('/stock-transfers/locations', [App\Http\Controllers\StockTransferController::class, 'locations'])->middleware('role:admin,staff');
    Route::get('/stock-transfers/data', [App\Http\Controllers\StockTransferController::class, 'index'])->name('stock-transfers.data')->middleware('role:admin,staff');
    Route::post('/stock-transfers', [App\Http\Controllers\StockTransferController::class, 'store'])->name('stock-transfers.store')->middleware('role:admin,staff');
    Route::post('/stock-transfers/{id}/complete', [App\Http\Controllers\StockTransferController::class, 'complete'])->name('stock-transfers.complete')->middleware('role:admin');

    // Capital Investment
    Route::post('/accounting/capital-investment', [AccountingController::class, 'storeCapitalInvestment'])->name('accounting.capital-investment')->middleware('role:admin,accountant');
    
    // Expenses (Accountant & Admin)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware(['role:admin,accountant', 'permission:finance.view']);
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware(['role:admin,accountant']);
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware(['role:admin,accountant']);
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware(['role:admin,accountant']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware(['role:admin,accountant']);
    
    // Bills & Payables (Accountant & Admin)
    Route::get('/expenses/unpaid', [ExpenseController::class, 'unpaidBills'])->name('expenses.unpaid')->middleware(['role:admin,accountant']);
    Route::post('/expenses/{expense}/pay', [ExpenseController::class, 'payBill'])->name('expenses.pay')->middleware(['role:admin,accountant']);
    
    // Vendors (Accountant & Admin)
    Route::resource('vendors', App\Http\Controllers\VendorController::class)->middleware(['role:admin,accountant']);

    // Shareholder Management (Accountant & Admin)
    Route::get('/accounting/shareholders', [AccountingController::class, 'indexShareholders'])->name('accounting.shareholders.index')->middleware('role:admin,accountant');
    Route::post('/accounting/shareholders', [AccountingController::class, 'storeShareholder'])->name('accounting.shareholders.store')->middleware('role:admin,accountant');
    Route::put('/accounting/shareholders/{shareholder}', [AccountingController::class, 'updateShareholder'])->name('accounting.shareholders.update')->middleware('role:admin,accountant');
    Route::delete('/accounting/shareholders/{shareholder}', [AccountingController::class, 'destroyShareholder'])->name('accounting.shareholders.destroy')->middleware('role:admin,accountant');
    Route::get('/accounting/dividends/preview', [AccountingController::class, 'dividendPreview'])->name('accounting.dividends.preview')->middleware('role:admin,accountant');

    // Budgets (Admin & Accountant)
    Route::get('/budgets/dashboard', [App\Http\Controllers\BudgetController::class, 'dashboard'])->name('budgets.dashboard')->middleware('role:admin,accountant');
    Route::post('/budgets/{budget}/approve', [App\Http\Controllers\BudgetController::class, 'approve'])->name('budgets.approve')->middleware('role:admin');
    Route::post('/budgets/{budget}/complete', [App\Http\Controllers\BudgetController::class, 'complete'])->name('budgets.complete')->middleware('role:admin,accountant');
    Route::post('/budgets/{budget}/archive', [App\Http\Controllers\BudgetController::class, 'archive'])->name('budgets.archive')->middleware('role:admin');
    Route::post('/budgets/generate-forecast', [App\Http\Controllers\BudgetController::class, 'generateForecast'])->name('budgets.generate-forecast')->middleware('role:admin,accountant');
    Route::post('/budgets/generate-ai', [App\Http\Controllers\BudgetController::class, 'generateAiBudget'])->name('budgets.generate-ai')->middleware('role:admin,accountant');
    Route::get('/budgets/{budget}/export', [App\Http\Controllers\BudgetController::class, 'export'])->name('budgets.export')->middleware('role:admin,accountant');
    Route::resource('budgets', App\Http\Controllers\BudgetController::class)->middleware(['role:admin,accountant', 'permission:finance.view']);

    // Reports & Analytics
    Route::get('/reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/sales', [App\Http\Controllers\ReportsController::class, 'sales'])->name('reports.sales')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/inventory', [App\Http\Controllers\ReportsController::class, 'inventory'])->name('reports.inventory')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/deep-analysis', [App\Http\Controllers\ReportsController::class, 'deepAnalysis'])->name('reports.deep-analysis')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::post('/reports/ai-summary', [App\Http\Controllers\ReportsController::class, 'aiSummary'])->name('reports.ai-summary')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::post('/reports/chat', [App\Http\Controllers\ReportsController::class, 'chat'])->name('reports.chat')->middleware(['role:admin,accountant', 'permission:reports.view']);
    // New Financial Reports
    Route::get('/reports/profit-loss', [App\Http\Controllers\Reports\ProfitLossController::class, 'index'])->name('reports.profit-loss')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/expenses', [App\Http\Controllers\Reports\ExpenseAnalysisController::class, 'index'])->name('reports.expenses')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/suppliers', [App\Http\Controllers\Reports\SupplierReportController::class, 'index'])->name('reports.suppliers')->middleware(['role:admin,accountant', 'permission:reports.view']);
    Route::get('/reports/inventory-aging', [App\Http\Controllers\Reports\InventoryAgingController::class, 'index'])->name('reports.inventory-aging')->middleware(['role:admin,accountant', 'permission:reports.view']);

    // Rentals (Surgical Sets)
    Route::get('/rentals/{rental}/return', [RentalController::class, 'returnForm'])->name('rentals.return-form')->middleware('role:admin,staff,accountant');
    Route::post('/rentals/{rental}/return', [RentalController::class, 'processReturn'])->name('rentals.process-return')->middleware('role:admin,staff,accountant');
    Route::resource('rentals', RentalController::class)->middleware('role:admin,staff,accountant');

    // LPOs (Facilities)
    Route::get('/api/customers/{customerId}/lpos', [App\Http\Controllers\LpoController::class, 'getActiveLpos'])->name('api.lpos.active')->middleware('auth');
    Route::resource('lpos', App\Http\Controllers\LpoController::class)->middleware('role:admin,staff,accountant');
    // Packages / Procedure Bundles
    Route::get('/api/packages/{package}/details', [App\Http\Controllers\PackageController::class, 'getDetails'])->name('api.packages.details')->middleware('auth');
    Route::get('/api/packages/{package}/details', [App\Http\Controllers\PackageController::class, 'getDetails'])->name('api.packages.details')->middleware('auth');
    Route::resource('packages', App\Http\Controllers\PackageController::class)->middleware('role:admin,accountant');

    // Commissions
    Route::resource('commissions', App\Http\Controllers\CommissionController::class)->only(['index', 'store', 'update'])->middleware('role:admin,accountant,staff');

    // MinIO Storage Test Routes (Admin Only)
    Route::middleware('role:admin')->prefix('test/minio')->group(function () {
        Route::get('/', [App\Http\Controllers\MinioTestController::class, 'index'])->name('test.minio.index');
        Route::get('/connection', [App\Http\Controllers\MinioTestController::class, 'testConnection'])->name('test.minio.connection');
        Route::post('/upload', [App\Http\Controllers\MinioTestController::class, 'testUpload'])->name('test.minio.upload');
        Route::get('/files', [App\Http\Controllers\MinioTestController::class, 'listFiles'])->name('test.minio.files');
        Route::post('/cleanup', [App\Http\Controllers\MinioTestController::class, 'cleanupTestFiles'])->name('test.minio.cleanup');
        Route::get('/download/{path}', [App\Http\Controllers\MinioTestController::class, 'testDownload'])->name('test.minio.download')->where('path', '.*');
    });

    // Batch & Serial Number Tracking (Admin & Staff)
    Route::middleware('role:admin,staff')->group(function () {
        // Batch CRUD
        Route::get('/batches', [App\Http\Controllers\BatchController::class, 'index'])->name('batches.index');
        Route::post('/batches', [App\Http\Controllers\BatchController::class, 'store'])->name('batches.store');
        Route::put('/batches/{batch}', [App\Http\Controllers\BatchController::class, 'update'])->name('batches.update');
        Route::delete('/batches/{batch}', [App\Http\Controllers\BatchController::class, 'destroy'])->name('batches.destroy');
        
        // Recall Management
        Route::post('/batches/{batch}/recall', [App\Http\Controllers\BatchController::class, 'recall'])->name('batches.recall');
        Route::post('/batches/{batch}/resolve-recall', [App\Http\Controllers\BatchController::class, 'resolveRecall'])->name('batches.resolve-recall');
        
        // Traceability & Reports
        Route::get('/batches/traceability', [App\Http\Controllers\BatchController::class, 'traceability'])->name('batches.traceability');
        Route::get('/batches/expiring', [App\Http\Controllers\BatchController::class, 'expiring'])->name('batches.expiring');
        Route::get('/batches/recalled', [App\Http\Controllers\BatchController::class, 'recalled'])->name('batches.recalled');
    });

    // Consignment Stock Management (Admin & Staff)
    Route::middleware('role:admin,staff')->prefix('consignment')->group(function () {
        // Dashboard & Overview
        Route::get('/', [App\Http\Controllers\ConsignmentController::class, 'index'])->name('consignment.index');
        
        // Stock Operations
        Route::get('/locations/{location}/stock', [App\Http\Controllers\ConsignmentController::class, 'locationStock'])->name('consignment.location-stock');
        Route::post('/place-stock', [App\Http\Controllers\ConsignmentController::class, 'placeStock'])->name('consignment.place-stock');
        Route::post('/use-stock', [App\Http\Controllers\ConsignmentController::class, 'useStock'])->name('consignment.use-stock');
        Route::post('/return-stock', [App\Http\Controllers\ConsignmentController::class, 'returnStock'])->name('consignment.return-stock');
        
        // Billing
        Route::get('/unbilled', [App\Http\Controllers\ConsignmentController::class, 'unbilled'])->name('consignment.unbilled');
        Route::post('/generate-bill', [App\Http\Controllers\ConsignmentController::class, 'generateBill'])->name('consignment.generate-bill');
        
        // Reports
        Route::get('/locations/{location}/ledger', [App\Http\Controllers\ConsignmentController::class, 'ledger'])->name('consignment.ledger');
        Route::get('/aging', [App\Http\Controllers\ConsignmentController::class, 'aging'])->name('consignment.aging');
    });

    // Surgery Sales (Consignment Reconciliation)
    Route::middleware('role:admin,staff,accountant')->prefix('sales/consignments')->group(function () {
        Route::get('/', [App\Http\Controllers\ConsignmentController::class, 'salesIndex'])->name('sales.consignments.index');
        Route::get('/{sale}/reconcile', [App\Http\Controllers\ConsignmentController::class, 'reconcileView'])->name('sales.consignments.reconcile');
        Route::post('/{sale}/process', [App\Http\Controllers\ConsignmentController::class, 'processReconciliation'])->name('sales.consignments.process');
    });
});
