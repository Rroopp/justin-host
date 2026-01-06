<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\ProductAttribute;

class MedicalProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\InitialMedicalAttributesSeeder::class);
    }

    public function test_can_create_medical_product_with_dynamic_attributes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Ensure category exists (from Seeder)
        $category = Category::where('name', 'Orthopaedic Plates')->first();
        if (!$category) $this->markTestSkipped('Seeder not run');

        $payload = [
            'product_name' => 'Test Plate', // Will be overwritten by auto-generate
            'category' => 'Orthopaedic Plates',
            'subcategory' => 'Distal Femur',
            'quantity_in_stock' => 10,
            'price' => 100,
            'selling_price' => 150,
            'attributes' => [
                'material' => 'Titanium',
                'side' => 'Left',
                'holes' => 6,
                'length' => 120,
            ]
        ];

        $response = $this->post(route('inventory.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_master', [
            'category' => 'Orthopaedic Plates',
            'subcategory' => 'Distal Femur',
        ]);
        
        // Verify JSON attributes are stored
        $product = \App\Models\Inventory::where('category', 'Orthopaedic Plates')->latest()->first();
        $this->assertEquals('Titanium', $product->attributes['material']);
        $this->assertEquals('Left', $product->attributes['side']);
    }

    public function test_validation_fails_if_required_attribute_missing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::where('name', 'Orthopaedic Plates')->first();
        if (!$category) $this->markTestSkipped('Seeder not run');

        $payload = [
            'category' => 'Orthopaedic Plates',
            'subcategory' => 'Distal Femur',
            'quantity_in_stock' => 10,
            'price' => 100,
            'selling_price' => 150,
            'attributes' => [
                'material' => 'Titanium',
                // 'side' is required but missing
                'holes' => 6,
            ]
        ];

        $response = $this->post(route('inventory.store'), $payload);

        $response->assertSessionHasErrors(['attributes.side']);
    }
}
