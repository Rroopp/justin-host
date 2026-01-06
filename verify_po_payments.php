<?php
// verify_po_payments.php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

// Mock User
$user = new \App\Models\User();
$user->id = 1;
$user->username = 'test_admin';

// Mock Request setup helper
function mockRequest($user, $data = []) {
    $req = Request::create('/', 'POST', $data);
    $req->headers->set('Accept', 'application/json');
    $req->setUserResolver(function () use ($user) { return $user; });
    return $req;
}

use App\Services\AccountingService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;

// Cleanup Previous Verification Artifacts
\App\Models\JournalEntry::where('created_by', 'test_admin')->delete();
\App\Models\PurchaseOrder::where('order_number', 'PO-TEST-001')->delete();
\App\Models\PosSale::where('invoice_number', 'INV-TEST-001')->delete();

echo "\n--- STARTING PHASE 5 VERIFICATION ---\n";

// 1. TEST: Purchase Order Flow (AP Accounting)
echo "\n--- TEST 1: Purchase Order Flow ---\n";

// Create Supplier
$supplier = Supplier::firstOrCreate(
    ['name' => 'Test Supplier Co'],
    ['contact_person' => 'John Doe', 'phone' => '1234567890']
);

// Create Inventory Item
$product = Inventory::firstOrCreate(
    ['product_name' => 'Test Product for PO'],
    [
        'price' => 100.00,
        'selling_price' => 150.00,
        'quantity_in_stock' => 0,
        'code' => 'TEST-PO-001'
    ]
);

// Create Purchase Order
$po = PurchaseOrder::create([
    'order_number' => 'PO-TEST-001',
    'supplier_id' => $supplier->id,
    'supplier_name' => $supplier->name,
    'status' => 'pending',
    'subtotal' => 1000.00,
    'tax_amount' => 0,
    'total_amount' => 1000.00,
    'order_date' => now(),
    'created_by' => 'test_admin'
]);

PurchaseOrderItem::create([
    'order_id' => $po->id,
    'product_id' => $product->id,
    'product_name' => $product->product_name,
    'quantity' => 10,
    'unit_cost' => 100.00,
    'total_cost' => 1000.00,
]);

echo "Created PO #{$po->order_number}\n";

// Update Status to Received
$orderController = new OrderController();
$updateRequest = mockRequest($user, ['status' => 'received']);

try {
    $response = $orderController->updateStatus($updateRequest, $po);
    
    if ($response->getStatusCode() >= 400) {
        echo "Response Error: " . $response->getContent() . "\n";
    } else {
        echo "[PASS] PO status updated to 'received'\n";
        
        // Check Inventory Update
        $product->refresh();
        echo "Inventory Stock: {$product->quantity_in_stock} (Expected 10)\n";
        
        // Check Journal Entry
        $je = \App\Models\JournalEntry::where('reference_type', 'PURCHASE_ORDER')
            ->where('reference_id', $po->id)
            ->latest()
            ->first();
            
        if ($je) {
            echo "[PASS] Journal Entry created (#{$je->entry_number})\n";
            echo "Total Debits: {$je->total_debit} (Expected 1000.00)\n";
            
            // Verify Dr Inventory, Cr AP
            $inventoryLine = $je->lines()->whereHas('account', function($q) {
                $q->where('code', AccountingService::ACCOUNT_INVENTORY);
            })->first();
            
            $apLine = $je->lines()->whereHas('account', function($q) {
                $q->where('code', AccountingService::ACCOUNT_ACCOUNTS_PAYABLE);
            })->first();
            
            if ($inventoryLine && $inventoryLine->debit_amount == 1000.00) {
                echo "[PASS] Inventory debited correctly\n";
            } else {
                echo "[FAIL] Inventory debit incorrect\n";
            }
            
            if ($apLine && $apLine->credit_amount == 1000.00) {
                echo "[PASS] Accounts Payable credited correctly\n";
            } else {
                echo "[FAIL] AP credit incorrect\n";
            }
        } else {
            echo "[FAIL] No Journal Entry for PO\n";
        }
    }
} catch (\Exception $e) {
    echo "[ERROR] PO Test Failed: " . $e->getMessage() . "\n";
}

// 2. TEST: Payment Flow (AR Reduction)
echo "\n--- TEST 2: Payment Flow ---\n";

// Create Customer
$customer = Customer::firstOrCreate(
    ['name' => 'Test Customer'],
    ['phone' => '0987654321', 'email' => 'customer@test.com']
);

// Create Credit Sale (Invoice)
$sale = PosSale::create([
    'sale_items' => [['product_id' => $product->id, 'product_name' => $product->product_name, 'quantity' => 1, 'unit_price' => 150, 'item_total' => 150]],
    'payment_method' => 'Credit',
    'payment_status' => 'Pending',
    'sale_status' => 'completed',
    'subtotal' => 150.00,
    'total' => 150.00,
    'customer_id' => $customer->id,
    'customer_name' => $customer->name,
    'invoice_number' => 'INV-TEST-001',
    'document_type' => 'invoice',
    'seller_username' => 'test_admin',
    'timestamp' => now(),
]);

echo "Created Invoice #{$sale->invoice_number} (Total: {$sale->total})\n";

// Record Payment
$paymentController = new PaymentController();
$paymentRequest = mockRequest($user, [
    'amount' => 150.00,
    'payment_method' => 'Cash',
    'payment_date' => now()->toDateString(),
    'payment_reference' => 'TEST-PMT-001',
    'payment_notes' => 'Test payment verification',
]);

try {
    $response = $paymentController->store($paymentRequest, $sale->id);
    
    if ($response->getStatusCode() >= 400) {
        echo "Response Error: " . $response->getContent() . "\n";
    } else {
        echo "[PASS] Payment recorded\n";
        
        // Check Sale Status
        $sale->refresh();
        echo "Payment Status: {$sale->payment_status} (Expected 'Paid')\n";
        
        if ($sale->payment_status === 'Paid') {
            echo "[PASS] Payment status updated to 'Paid' (Title Case)\n";
        } else {
            echo "[FAIL] Payment status incorrect: {$sale->payment_status}\n";
        }
        
        // Check Journal Entry
        $payment = PosSalePayment::where('pos_sale_id', $sale->id)->latest()->first();
        if ($payment) {
            $je = \App\Models\JournalEntry::where('reference_type', 'PosSalePayment')
                ->where('reference_id', $payment->id)
                ->latest()
                ->first();
                
            if ($je) {
                echo "[PASS] Journal Entry created for payment (#{$je->entry_number})\n";
                echo "Total Credits: {$je->total_credit} (Expected 150.00)\n";
            } else {
                echo "[FAIL] No Journal Entry for payment\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "[ERROR] Payment Test Failed: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";
