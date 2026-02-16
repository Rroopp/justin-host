<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryImportController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DocumentTemplateController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\InventoryAdjustmentController;
use Illuminate\Support\Facades\Route;

// Data / action routes (no /api prefix; same-origin + session-authenticated)
Route::middleware('auth')->group(function () {
    // Inventory actions
    Route::get('/inventory/low-stock-alerts', [InventoryController::class, 'lowStockAlerts'])->middleware('role:admin');
    Route::get('/inventory/types', [InventoryController::class, 'getTypes'])->middleware('role:admin,staff');
    Route::get('/inventory/sizes', [InventoryController::class, 'getSizes'])->middleware('role:admin,staff');
    Route::post('/inventory/import', [InventoryImportController::class, '__invoke'])->middleware('role:admin');
    
    // Inventory actions (parameterized)
    Route::post('/inventory', [InventoryController::class, 'store'])->middleware('role:admin,staff');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->middleware('role:admin,staff');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->middleware('role:admin,staff');
    Route::post('/inventory/{inventory}/restock', [InventoryController::class, 'restock'])->middleware('role:admin,staff');
    Route::post('/inventory/{inventory}/adjust', [InventoryAdjustmentController::class, 'store'])->middleware('role:admin,staff');
    Route::get('/inventory/{inventory}/adjustments', [InventoryAdjustmentController::class, 'index'])->middleware('role:admin,staff');
    Route::get('/categories', [CategoryController::class, 'list'])->middleware('role:admin,staff');
    Route::get('/categories/{categoryName}/subcategories', [CategoryController::class, 'getSubcategories'])->middleware('role:admin,staff');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin');

    Route::post('/subcategories', [SubcategoryController::class, 'store'])->middleware('role:admin');
    Route::put('/subcategories/{subcategory}', [SubcategoryController::class, 'update'])->middleware('role:admin');
    Route::delete('/subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])->middleware('role:admin');
    
    // POS
    Route::post('/pos', [POSController::class, 'store'])->middleware('role:admin,staff');
    Route::get('/receipts/{id}', [POSController::class, 'getReceipt'])->middleware('role:admin,staff');
    
    // Sales (data endpoints)
    Route::get('/sales/summary', [SalesController::class, 'summary'])->middleware('role:admin,staff,accountant');
    
    // Customers actions
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('role:admin,staff');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('role:admin,staff');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('role:admin,staff');
    
    // Orders actions
    Route::post('/orders', [OrderController::class, 'store'])->middleware('role:admin,staff');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin,staff');
    // Route::get('/orders/suggestions', [OrderController::class, 'suggestions'])->middleware('role:admin,staff');
    Route::get('/orders/dashboard', [OrderController::class, 'dashboard'])->middleware('role:admin,staff');
    
    // Suppliers actions
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('role:admin,staff');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('role:admin,staff');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('role:admin,staff');
    
    // Staff actions
    Route::post('/staff', [StaffController::class, 'store'])->middleware('role:admin');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->middleware('role:admin');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->middleware('role:admin');
    Route::get('/staff/{staff}/activity', [StaffController::class, 'activity'])->middleware('role:admin');
    
    // Settings actions
    Route::get('/settings/{category}', [SettingsController::class, 'getByCategory'])->middleware('role:admin');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:admin');
    Route::get('/settings/company', [SettingsController::class, 'company'])->middleware('role:admin');
    Route::put('/settings/company', [SettingsController::class, 'updateCompany'])->middleware('role:admin');
    Route::post('/settings/company/logo', [SettingsController::class, 'uploadCompanyLogo'])->middleware('role:admin');
    Route::get('/settings/modules', [SettingsController::class, 'modules'])->middleware('role:admin');
    Route::put('/settings/modules', [SettingsController::class, 'updateModules'])->middleware('role:admin');
    Route::get('/settings/audit-log', [SettingsController::class, 'auditLog'])->middleware('role:admin');
    Route::post('/settings/backup', [SettingsController::class, 'backup'])->middleware('role:admin');
    Route::get('/settings/user/preferences', [SettingsController::class, 'userPreferences']);
    Route::put('/settings/user/preferences', [SettingsController::class, 'updateUserPreferences']);
    
    // Document Templates actions
    Route::post('/document-templates', [DocumentTemplateController::class, 'store'])->middleware('role:admin,supervisor');
    Route::put('/document-templates/{documentTemplate}', [DocumentTemplateController::class, 'update'])->middleware('role:admin,supervisor');
    Route::delete('/document-templates/{documentTemplate}', [DocumentTemplateController::class, 'destroy'])->middleware('role:admin,supervisor');
    
    // Accounting
    Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'index'])->middleware('role:admin,accountant');
    Route::post('/accounting/chart-of-accounts', [AccountingController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/accounting/chart-of-accounts/{chartOfAccount}', [AccountingController::class, 'updateAccount'])->middleware('role:admin,accountant');
    Route::delete('/accounting/chart-of-accounts/{chartOfAccount}', [AccountingController::class, 'destroyAccount'])->middleware('role:admin,accountant');
    Route::post('/accounting/chart-of-accounts/{chartOfAccount}/toggle-active', [AccountingController::class, 'toggleAccountActive'])->middleware('role:admin,accountant');
    Route::post('/accounting/journal-entries', [AccountingController::class, 'storeJournalEntry'])->middleware('role:admin,accountant');
    Route::put('/accounting/journal-entries/{journalEntry}', [AccountingController::class, 'updateJournalEntry'])->middleware('role:admin,accountant');
    Route::post('/accounting/journal-entries/{journalEntry}/post', [AccountingController::class, 'postJournalEntry'])->middleware('role:admin,accountant');
    Route::post('/accounting/journal-entries/{journalEntry}/unpost', [AccountingController::class, 'unpostJournalEntry'])->middleware('role:admin,accountant');
    Route::post('/accounting/journal-entries/{journalEntry}/cancel', [AccountingController::class, 'cancelJournalEntry'])->middleware('role:admin,accountant');
    // NOTE: trial-balance and financial-statements GET routes are served from web.php (HTML + JSON via content negotiation)
    
    // Expenses actions
    Route::post('/expenses', [ExpenseController::class, 'store'])->middleware('role:admin,accountant');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->middleware('role:admin,accountant');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('role:admin,accountant');
});

