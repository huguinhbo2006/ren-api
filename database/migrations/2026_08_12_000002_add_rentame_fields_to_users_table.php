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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete()->after('avatar');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_id');
            $table->boolean('is_active')->default(true)->after('plan_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'phone',
                'avatar',
                'plan_id',
                'plan_expires_at',
                'is_active',
            ]);
        });
    }
};
