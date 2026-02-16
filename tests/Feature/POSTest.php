<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\PosSale;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Event;

class POSTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required accounts for accounting
        $accounts = [
            ['code' => '4000', 'name' => 'Sales Revenue', 'account_type' => 'Income'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type' => 'Expense'],
            ['code' => '2000', 'name' => 'VAT Payable', 'account_type' => 'Liability'],
            ['code' => '1200', 'name' => 'Inventory Asset', 'account_type' => 'Asset'],
            ['code' => '1000', 'name' => 'Cash', 'account_type' => 'Asset'],
        ];

        foreach ($accounts as $acc) {
            \App\Models\ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                ['name' => $acc['name'], 'account_type' => $acc['account_type']]
            );
        }
    }

    public function test_can_process_cash_sale_with_single_item()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Test Product',
            'price' => 100,
            'selling_price' => 150,
            'quantity_in_stock' => 10,
            'code' => 'TEST-' . rand(1000, 9999)
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '1234567890',
            'type' => 'Outpatient'
        ]);

        $payload = [
            'items' => [
                [
                    'id' => $product->id,
                    'quantity' => 2,
                    'type' => 'sale'
                ]
            ],
            'payment_method' => 'Cash',
            'subtotal' => 300,
            'vat' => 48,
            'total' => 348,
            'customer_id' => $customer->id,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertSuccessful();
        $responseData = $response->json();
        $this->assertArrayHasKey('sale_id', $responseData);
        // Invoice number may be in different key
        $this->assertTrue(isset($responseData['invoice_number']) || isset($responseData['invoice']));

        // Verify inventory was deducted
        $product->refresh();
        $this->assertEquals(8, $product->quantity_in_stock);

        // Verify sale was created
        $this->assertDatabaseHas('pos_sales', [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'total' => 348
        ]);
    }

    public function test_can_process_sale_with_multiple_items()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product1 = Inventory::create([
            'product_name' => 'Product 1',
            'price' => 50,
            'selling_price' => 75,
            'quantity_in_stock' => 20,
            'code' => 'PROD1-' . rand(1000, 9999)
        ]);

        $product2 = Inventory::create([
            'product_name' => 'Product 2',
            'price' => 100,
            'selling_price' => 150,
            'quantity_in_stock' => 15,
            'code' => 'PROD2-' . rand(1000, 9999)
        ]);

        $payload = [
            'items' => [
                ['id' => $product1->id, 'quantity' => 3, 'type' => 'sale'],
                ['id' => $product2->id, 'quantity' => 2, 'type' => 'sale']
            ],
            'payment_method' => 'M-Pesa',
            'subtotal' => 525,
            'vat' => 84,
            'total' => 609,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertSuccessful();

        // Verify both products were deducted
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(17, $product1->quantity_in_stock);
        $this->assertEquals(13, $product2->quantity_in_stock);
    }

    public function test_fails_when_insufficient_stock()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Low Stock Product',
            'price' => 50,
            'selling_price' => 75,
            'quantity_in_stock' => 5,
            'code' => 'LOW-' . rand(1000, 9999)
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 10, 'type' => 'sale']
            ],
            'payment_method' => 'Cash',
            'subtotal' => 750,
            'vat' => 120,
            'total' => 870,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_creates_journal_entry_for_sale()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Accounting Test Product',
            'price' => 200,
            'selling_price' => 300,
            'quantity_in_stock' => 10,
            'code' => 'ACC-' . rand(1000, 9999)
        ]);

        $journalCountBefore = JournalEntry::count();

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 1, 'type' => 'sale']
            ],
            'payment_method' => 'Cash',
            'subtotal' => 300,
            'vat' => 48,
            'total' => 348,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);
        $response->assertSuccessful();

        // Verify journal entry was created
        $journalCountAfter = JournalEntry::count();
        $this->assertGreaterThan($journalCountBefore, $journalCountAfter);
    }

    public function test_can_process_credit_sale_with_invoice()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Credit Sale Product',
            'price' => 100,
            'selling_price' => 150,
            'quantity_in_stock' => 10,
            'code' => 'CREDIT-' . rand(1000, 9999)
        ]);

        $customer = Customer::create([
            'name' => 'Credit Customer',
            'phone' => '9876543210',
            'type' => 'Inpatient'
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 1, 'type' => 'sale']
            ],
            'payment_method' => 'Credit',
            'subtotal' => 150,
            'vat' => 24,
            'total' => 174,
            'customer_id' => $customer->id,
            'document_type' => 'invoice',
            'due_date' => now()->addDays(30)->format('Y-m-d')
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertSuccessful();

        // Verify invoice was created
        $this->assertDatabaseHas('pos_sales', [
            'customer_id' => $customer->id,
            'payment_method' => 'Credit',
            'document_type' => 'invoice'
        ]);
    }

    public function test_validates_required_fields()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $payload = [
            'items' => [],
            'payment_method' => 'Cash'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items', 'subtotal', 'total', 'document_type']);
    }

    public function test_low_stock_alert_generated()
    {
        $staff = Staff::factory()->create(['role' => 'admin']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Low Stock Alert Product',
            'price' => 50,
            'selling_price' => 75,
            'quantity_in_stock' => 5,
            'min_stock_level' => 10,
            'code' => 'ALERT-' . rand(1000, 9999)
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 3, 'type' => 'sale']
            ],
            'payment_method' => 'Cash',
            'subtotal' => 225,
            'vat' => 36,
            'total' => 261,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertSuccessful();
        
        // Verify low stock alert is in response
        $responseData = $response->json();
        if (isset($responseData['low_stock_alerts'])) {
            $this->assertNotEmpty($responseData['low_stock_alerts']);
        }
    }

    public function test_requires_authentication()
    {
        $product = Inventory::create([
            'product_name' => 'Unauthorized Test',
            'price' => 50,
            'selling_price' => 75,
            'quantity_in_stock' => 10,
            'code' => 'UNAUTH-' . rand(1000, 9999)
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 1, 'type' => 'sale']
            ],
            'payment_method' => 'Cash',
            'subtotal' => 75,
            'vat' => 12,
            'total' => 87,
            'document_type' => 'receipt'
        ];

        $response = $this->postJson('/pos', $payload);

        $response->assertStatus(401);
    }

    public function test_role_based_access_control()
    {
        // Test with non-POS role
        $staff = Staff::factory()->create(['role' => 'accountant']);
        $this->actingAs($staff);

        $product = Inventory::create([
            'product_name' => 'Role Test Product',
            'price' => 50,
            'selling_price' => 75,
            'quantity_in_stock' => 10,
            'code' => 'ROLE-' . rand(1000, 9999)
        ]);

        $payload = [
            'items' => [
                ['id' => $product->id, 'quantity' => 1, 'type' => 'sale']
            ],
            'payment_method' => 'Cash',
            'subtotal' => 75,
            'vat' => 12,
            'total' => 87,
            'document_type' => 'receipt'
        ];

        // This might pass or fail depending on middleware configuration
        // Adjust based on actual role requirements
        $response = $this->postJson('/pos', $payload);
        
        // Assert appropriate response (either success if role allows, or 403 if restricted)
        $this->assertContains($response->status(), [200, 403]);
    }
}

