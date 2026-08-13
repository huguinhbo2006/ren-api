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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_cents')->default(0);
            $table->date('payment_date');
            $table->enum('method', ['cash', 'transfer', 'card', 'check'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->enum('type', ['income', 'deposit'])->default('income');
            $table->timestamps();

            $table->index(['rental_id', 'type']);
            $table->index(['user_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
