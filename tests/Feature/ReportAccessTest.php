<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportAccessTest extends TestCase
{
    use RefreshDatabase;
    public function test_profit_and_loss_report_is_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.profit-loss'));
        $response->assertStatus(200);
    }

    public function test_expense_analysis_report_is_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.expenses'));
        $response->assertStatus(200);
    }

    public function test_supplier_report_is_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.suppliers'));
        $response->assertStatus(200);
    }

    public function test_inventory_aging_report_is_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.inventory-aging'));
        $response->assertStatus(200);
    }

    public function test_ai_chat_route_is_accessible()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reports.deep-analysis'));
        $response->assertStatus(200);
    }
}
