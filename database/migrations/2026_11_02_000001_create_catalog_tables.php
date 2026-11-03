<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('seller');
            $t->boolean('is_active')->default(true);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('code')->unique();
            $t->string('slug')->unique();
            $t->string('measures')->nullable();
            $t->string('description', 500);
            $t->string('brand')->nullable();
            $t->unsignedInteger('price_cents')->nullable();
            $t->unsignedInteger('avg_cost_cents')->default(0);
            $t->integer('stock')->default(0);
            $t->boolean('track_stock')->default(true);
            $t->unsignedInteger('low_stock_threshold')->default(3);
            $t->boolean('is_visible')->default(true);
            $t->boolean('is_featured')->default(false);
            $t->string('search_key', 1000);
            $t->timestamps();
        });

        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->boolean('is_reference')->default(false);
            $t->string('credit_author')->nullable();
            $t->string('credit_license')->nullable();
            $t->string('credit_url', 500)->nullable();
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('settings');
    }
};
