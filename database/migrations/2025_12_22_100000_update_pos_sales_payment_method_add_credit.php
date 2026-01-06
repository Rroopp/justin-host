<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SQLite stores enums as `varchar check (...)`, so we must rebuild the table
     * to extend the allowed values.
     */
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');

            // SQLite keeps index names globally; a previous failed attempt may have left
            // `pos_sales_old` with indexes named like `pos_sales_*`. Drop them first.
            DB::statement('DROP INDEX IF EXISTS pos_sales_seller_username_index');
            DB::statement('DROP INDEX IF EXISTS pos_sales_customer_id_index');
            DB::statement('DROP INDEX IF EXISTS pos_sales_document_type_index');
            DB::statement('DROP INDEX IF EXISTS pos_sales_payment_status_index');
            DB::statement('DROP INDEX IF EXISTS pos_sales_created_at_index');
            DB::statement('DROP INDEX IF EXISTS pos_sales_invoice_number_unique');

            // Handle partial state from a failed migration:
            // If both pos_sales_old and pos_sales exist, drop the new one and rebuild cleanly.
            if (Schema::hasTable('pos_sales_old')) {
                if (Schema::hasTable('pos_sales')) {
                    Schema::drop('pos_sales');
                }
            } else {
                // Normal path: rename existing table into pos_sales_old
                if (Schema::hasTable('pos_sales')) {
                    Schema::rename('pos_sales', 'pos_sales_old');
                }
            }

            // Recreate table with updated enum values
            Schema::create('pos_sales', function (Blueprint $table) {
                $table->id();
                $table->json('sale_items');
                $table->enum('payment_method', ['Cash', 'M-Pesa', 'Bank', 'Cheque', 'Credit'])->default('Cash');
                $table->enum('payment_status', ['paid', 'pending', 'partial'])->default('paid');
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('vat', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('customer_email')->nullable();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->json('customer_snapshot')->nullable();
                $table->string('seller_username');
                $table->string('timestamp');
                $table->boolean('receipt_generated')->default(true);
                $table->text('receipt_data')->nullable();
                $table->enum('document_type', ['receipt', 'invoice', 'delivery_note'])->default('receipt');
                $table->string('invoice_number')->nullable()->unique();
                $table->date('due_date')->nullable();
                $table->string('lpo_number')->nullable();
                $table->enum('patient_type', ['Inpatient', 'Outpatient'])->nullable();
                $table->boolean('delivery_note_generated')->default(false);
                $table->text('delivery_note_data')->nullable();
                $table->date('payment_date')->nullable();
                $table->string('payment_reference')->nullable();
                $table->text('payment_notes')->nullable();
                $table->timestamps();

                $table->index('seller_username');
                $table->index('customer_id');
                $table->index('document_type');
                $table->index('payment_status');
                $table->index('created_at');
            });

            // Copy data across if source table exists
            if (Schema::hasTable('pos_sales_old')) {
                DB::statement('DELETE FROM pos_sales');
                DB::statement('
                    INSERT INTO pos_sales (
                        id, sale_items, payment_method, payment_status, subtotal, discount_percentage, discount_amount, vat, total,
                        customer_name, customer_phone, customer_email, customer_id, customer_snapshot, seller_username, timestamp,
                        receipt_generated, receipt_data, document_type, invoice_number, due_date, lpo_number, patient_type,
                        delivery_note_generated, delivery_note_data, payment_date, payment_reference, payment_notes,
                        created_at, updated_at
                    )
                    SELECT
                        id, sale_items, payment_method, payment_status, subtotal, discount_percentage, discount_amount, vat, total,
                        customer_name, customer_phone, customer_email, customer_id, customer_snapshot, seller_username, timestamp,
                        receipt_generated, receipt_data, document_type, invoice_number, due_date, lpo_number, patient_type,
                        delivery_note_generated, delivery_note_data, payment_date, payment_reference, payment_notes,
                        created_at, updated_at
                    FROM pos_sales_old
                ');

                Schema::drop('pos_sales_old');
            }

            DB::statement('PRAGMA foreign_keys=ON');

            return;
        }

        // MySQL can alter enum in-place. (Other DBs: keep as-is; prefer SQLite in this project.)
        if ($connection === 'mysql') {
            DB::statement("ALTER TABLE pos_sales MODIFY payment_method ENUM('Cash','M-Pesa','Bank','Cheque','Credit') NOT NULL DEFAULT 'Cash'");
        }
    }

    public function down(): void
    {
        // No-op: reversing enum expansions safely is DB-specific and can drop data.
    }
};


