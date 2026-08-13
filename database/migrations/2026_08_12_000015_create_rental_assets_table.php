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
        Schema::create('rental_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->unsignedInteger('daily_rate_cents')->default(0);
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->timestamps();

            $table->index(['rental_id', 'asset_id']);
        });

        // Hacer asset_id opcional en rentals para permitir contratos multi-activo sin requerir un único asset principal
        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_assets');
    }
};
