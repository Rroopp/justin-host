<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\PosSale;
use App\Models\PosSalePayment;
use App\Models\PayrollRun;
use App\Models\Staff;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Refund;
use App\Models\ChartOfAccount;
use App\Models\User;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TestSpriteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting TestSprite Comprehensive Seeding...');

        // 1. Ensure Staff (Admin)
        $admin = Staff::where('username', 'Rop')->first();
        if (!$admin) {
            $this->call(AdminRopSeeder::class);
            $admin = Staff::where('username', 'Rop')->first();
        }
        
        // Ensure generic staff
        $staff = Staff::where('username', '!=', 'Rop')->first();
        if (!$staff) {
            $this->call(StaffSeeder::class);
            $staff = Staff::first();
        }

        // 2. Ensure Chart of Accounts (Using existing seeder)
        if (ChartOfAccount::count() < 5) {
             $this->call(ChartOfAccountsSeeder::class);
             $this->call(PayrollAccountsSeeder::class);
        }

        // 3. Ensure Inventory Products
        $this->seedInventory();

        // 4. Seed Customers
        $this->seedCustomers();
        $customer = Customer::first();

        // 5. Seed Sales & Payments
        $this->seedSales($admin, $staff, $customer);

        // 6. Seed Payroll Runs
        $this->seedPayroll($admin);

        // 7. Seed Stock Transfers
        $this->seedStockTransfers($admin);

        // 8. Seed Budgets
        $this->seedBudgets($admin);

        // 9. Seed Expenses
        $this->seedExpenses($admin);

        // 10. Seed Rentals
        $this->seedRentals($customer);

        // 11. Seed Refunds
        $this->seedRefunds($admin, $customer);

        $this->command->info('TestSprite Comprehensive Seeding Completed!');
    }

    private function seedInventory()
    {
        if (Inventory::count() > 0) return;

        $this->call(MedicalInventorySeeder::class);

        Inventory::create([
            'product_name' => 'Test Paracetamol 500mg',
            'code' => 'TEST-PARA-001',
            'category' => 'Consumables',
            'quantity_in_stock' => 100,
            'selling_price' => 10.00,
            'price' => 5.00,
            'min_stock_level' => 10,
            'size_unit' => 'tablets',
            'is_rentable' => false,
        ]);

        Inventory::create([
            'product_name' => 'Test Crutches (Pair)',
            'code' => 'TEST-CRUTCH-001',
            'category' => 'Walking Aids',
            'quantity_in_stock' => 20,
            'selling_price' => 2500.00,
            'price' => 1500.00,
            'is_rentable' => true,
        ]);
    }

    private function seedCustomers()
    {
        if (Customer::count() > 0) return;
        
        Customer::create([
            'name' => 'John Doe Test',
            'phone' => '0700000000',
            'email' => 'john.test@example.com',
        ]);
    }

    private function seedSales($admin, $staff, $customer)
    {
        if (PosSale::count() > 0) return;

        $product = Inventory::first();
        $seller = $staff ?? $admin;

        // Sale 1: Paid Walk-in
        $sale1 = PosSale::create([
            'sale_number' => 'SALE-' . time() . '-1',
            // 'staff_id' => $seller->id, // Removed as it might not be in fillable
            'customer_name' => 'Walk-in Customer',
            'subtotal' => 100.00,
            'vat' => 16.00,
            'discount_amount' => 0.00,
            'total' => 116.00,
            'payment_method' => 'Cash',
            'payment_status' => 'paid',
            'sale_status' => 'completed',
            'seller_username' => $seller->username,
            'sale_items' => [
                [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'price' => 10.00,
                    'quantity' => 10,
                    'subtotal' => 100.00
                ]
            ]
        ]);

        PosSalePayment::create([
            'pos_sale_id' => $sale1->id,
            'amount' => 116.00,
            'payment_method' => 'Cash',
            'payment_date' => now(),
            'processed_by' => $admin->id, // Assuming this model has processed_by
        ]);

        // Sale 2: Unpaid Credit Sale (for Aging Report)
        if ($customer) {
            PosSale::create([
                'sale_number' => 'SALE-' . time() . '-2',
                // 'staff_id' => $seller->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'subtotal' => 5000.00,
                'total' => 5000.00,
                'payment_status' => 'pending', // Unpaid
                'sale_status' => 'completed',
                'seller_username' => $seller->username,
                'due_date' => now()->subDays(45), // Overdue by 45 days
                'sale_items' => []
            ]);
        }
    }

    private function seedPayroll($user)
    {
        if (PayrollRun::count() > 0) return;

        PayrollRun::create([
            // 'run_number' => 'PR-' . date('Ym'), // Column does not exist
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'total_gross' => 150000.00,
            'total_net' => 120000.00,
            'status' => 'COMPLETED', // Enum is uppercase COMPLETED
            'created_by' => $user->username, // String column
            // 'approved_by' => $user->id, // Column does not exist
        ]);
    }

    private function seedStockTransfers($user)
    {
        if (StockTransfer::count() > 0) return;

        // Ensure Locations exist
        // Check if Location model exists and fetch/create
        $loc1 = \App\Models\Location::firstOrCreate(['name' => 'Main Store'], ['type' => 'Store', 'status' => 'active']);
        $loc2 = \App\Models\Location::firstOrCreate(['name' => 'Pharmacy'], ['type' => 'Dispensing', 'status' => 'active']);

        $transfer = StockTransfer::create([
            // 'transfer_number' => 'TRF-' . Str::random(6), // Column does not exist
            'from_location_id' => $loc1->id,
            'to_location_id' => $loc2->id,
            'status' => 'completed',
            'user_id' => $user->id,
            'notes' => 'Test Transfer',
            'transfer_date' => now(),
        ]);

        $product = Inventory::first();
        if ($product) {
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'inventory_id' => $product->id,
                'quantity' => 10,
            ]);
        }
    }

    private function seedBudgets($staffUser)
    {
        if (Budget::count() > 0) return;

        // Budgets requires 'created_by' referencing 'users' table, not 'staff'.
        // We need to ensure a User exists.
        $authUser = User::firstOrCreate(
            ['email' => 'admin@hospital.com'], 
            ['name' => 'Admin User', 'password' => '$2y$12$hash...']
        );

        // Try to find an expense account
        $expenseAccount = ChartOfAccount::where('account_type', 'Expense')->first(); 
        
        Budget::create([
            'reference_number' => 'BUD-' . date('Y') . '-001',
            'name' => 'Q1 Office Supplies',
            'total_allocated' => 50000.00, // Correct column name
            'start_date' => now()->startOfQuarter(),
            'end_date' => now()->endOfQuarter(),
            'description' => 'Budget for office supplies',
            // 'account_id' => $expenseAccount?->id, // Column might not exist in this migration, check if needed or JSON items
            'created_by' => $authUser->id,
            'status' => 'active'
        ]);
    }

    private function seedExpenses($user)
    {
        if (Expense::count() > 0) return;

        $category = ChartOfAccount::where('account_type', 'Expense')->first();
        $paymentAccount = ChartOfAccount::where('account_type', 'Asset')->first();

        if ($category) {
            Expense::create([
                'expense_date' => now(),
                'amount' => 1500.00,
                'description' => 'Test Utility Bill',
                'payee' => 'KPLC', // Added field
                'category_id' => $category->id,
                'payment_account_id' => $paymentAccount?->id,
                // 'reference' => 'REF-001', // Column does not exist
                // 'status' => 'paid', // Column does not exist
                'created_by' => $user->username, // String column
            ]);
        }
    }

    private function seedRentals($customer)
    {
        if (Rental::count() > 0) return;

        if (!$customer) return;

        $rentalItem = Inventory::where('is_rentable', true)->first();
        if (!$rentalItem) return;

        $itemsJson = [
            [
                'inventory_id' => $rentalItem->id,
                'quantity' => 1,
                'condition_out' => 'Good',
                'condition_in' => null
            ]
        ];

        $rental = Rental::create([
            // 'rental_number' => 'RNT-' . time(), // Column does not exist
            'customer_id' => $customer->id,
            'status' => 'active',
            'rented_at' => now()->subDays(2),
            'expected_return_at' => now()->addDays(5),
            // 'total_deposit' => 1000.00, // Column does not exist
            // 'total_cost' => 500.00, // Column does not exist
            'items' => json_encode($itemsJson),
            'notes' => 'Test Rental'
        ]);

        // Also seed the pivot table
        RentalItem::create([
            'rental_id' => $rental->id,
            'inventory_id' => $rentalItem->id,
            'quantity' => 1,
            'price_at_rental' => $rentalItem->selling_price,
            'condition_out' => 'Good',
        ]);
    }

    private function seedRefunds($user, $customer)
    {
        if (Refund::count() > 0) return;
        
        // Find a sale to refund
        $sale = PosSale::first();
        if (!$sale) return;

        Refund::create([
            'refund_number' => 'REF-' . time(),
            'pos_sale_id' => $sale->id,
            'refund_amount' => 50.00, // Correct column name
            'reason' => 'Customer Request',
            'status' => 'approved',
            'requested_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'refund_items' => json_encode(['items' => 'sample']), // Required JSON
        ]);
    }
}
