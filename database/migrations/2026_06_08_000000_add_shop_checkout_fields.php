<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend device_orders for the public shop checkout flow (cash on delivery)
 * and add max_order_quantity to products for per-product checkout limits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_orders', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('device_type_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('claim_code', 8)
                ->nullable()
                ->unique()
                ->after('status');

            $table->string('shipping_name')->nullable()->after('claim_code');
            $table->string('shipping_phone', 20)->nullable()->after('shipping_name');
            $table->json('shipping_address')->nullable()->after('shipping_phone');
            $table->json('billing_address')->nullable()->after('shipping_address');

            $table->string('payment_method', 20)
                ->default('cod')
                ->after('billing_address');

            $table->decimal('total_amount', 10, 2)
                ->nullable()
                ->after('payment_method');

            $table->string('currency', 3)
                ->default('PKR')
                ->after('total_amount');
        });

        // Make tenant_id and device_type_id nullable for direct shop purchases
        // (user may not belong to a tenant yet, and order links to product).
        Schema::table('device_orders', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->change();
            $table->foreignId('device_type_id')->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_order_quantity')
                ->default(10)
                ->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('max_order_quantity');
        });

        Schema::table('device_orders', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable(false)->change();
            $table->foreignId('device_type_id')->nullable(false)->change();
        });

        Schema::table('device_orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id',
                'claim_code',
                'shipping_name',
                'shipping_phone',
                'shipping_address',
                'billing_address',
                'payment_method',
                'total_amount',
                'currency',
            ]);
        });
    }
};
