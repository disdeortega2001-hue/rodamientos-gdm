<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('delivery_note_number')->nullable()->unique();
            $t->string('delivery_note_token', 40)->nullable()->unique();
            $t->timestamp('delivery_note_issued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dropUnique(['delivery_note_number']);
            $t->dropUnique(['delivery_note_token']);
            $t->dropColumn(['delivery_note_number', 'delivery_note_token', 'delivery_note_issued_at']);
        });
    }
};
