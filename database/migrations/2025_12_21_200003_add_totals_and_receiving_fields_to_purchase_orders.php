<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('purchase_orders', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('purchase_orders', 'actual_delivery_date')) {
                $table->date('actual_delivery_date')->nullable()->after('expected_delivery_date');
            }
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'quantity_received')) {
                $table->integer('quantity_received')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('purchase_order_items', 'received_date')) {
                $table->date('received_date')->nullable()->after('quantity_received');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'received_date')) {
                $table->dropColumn('received_date');
            }
            if (Schema::hasColumn('purchase_order_items', 'quantity_received')) {
                $table->dropColumn('quantity_received');
            }
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'actual_delivery_date')) {
                $table->dropColumn('actual_delivery_date');
            }
            if (Schema::hasColumn('purchase_orders', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
            if (Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->dropColumn('subtotal');
            }
        });
    }
};


