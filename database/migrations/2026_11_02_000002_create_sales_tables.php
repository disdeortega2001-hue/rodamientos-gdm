<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->unique();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->nullable()->unique();
            $t->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('status')->default('new')->index();
            $t->string('source')->default('web');
            $t->text('note')->nullable();
            $t->decimal('usd_rate', 14, 4)->nullable();
            $t->string('checkout_token')->nullable()->unique();
            $t->timestamp('confirmed_at')->nullable()->index();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('code');
            $t->string('measures')->nullable();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('unit_price_cents')->nullable();
            $t->unsignedInteger('unit_cost_cents')->default(0);
            $t->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');
            $t->integer('quantity');
            $t->unsignedInteger('unit_cost_cents')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('quotes', function (Blueprint $t) {
            $t->id();
            $t->string('number')->nullable()->unique();
            $t->string('public_token', 40)->unique();
            $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->text('note')->nullable();
            $t->decimal('usd_rate', 14, 4)->nullable();
            $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('code');
            $t->string('measures')->nullable();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('unit_price_cents')->nullable();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('method');
            $t->string('reference')->nullable();
            $t->unsignedInteger('amount_cents');
            $t->date('paid_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'quote_items', 'quotes', 'stock_movements', 'order_items', 'orders', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
