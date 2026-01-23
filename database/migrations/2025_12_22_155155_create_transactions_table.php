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
        Schema::create('transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained('categories')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignUlid('organization_id')->constrained('organizations')->restrictOnDelete()->cascadeOnUpdate();
            $table->integer('total_transactions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
