<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rentals = DB::table('rentals')->get();

        foreach ($rentals as $rental) {
            $items = json_decode($rental->items, true);
            
            if (is_array($items)) {
                foreach ($items as $item) {
                    // Skip if inventory_id doesn't exist (integrity check)
                    $exists = DB::table('inventory_master')->where('id', $item['inventory_id'])->exists();
                    if (!$exists) continue;

                    DB::table('rental_items')->insert([
                        'rental_id' => $rental->id,
                        'inventory_id' => $item['inventory_id'],
                        'quantity' => $item['quantity'],
                        'condition_out' => $item['condition_out'] ?? 'Good',
                        'condition_in' => $item['condition_in'] ?? null,
                        'price_at_rental' => $item['price_at_rental'] ?? 0,
                        'created_at' => $rental->created_at,
                        'updated_at' => $rental->updated_at,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rental_items')->truncate();
    }
};
