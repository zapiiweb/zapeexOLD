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
        Schema::table('ai_user_settings', function (Blueprint $table) {
            $table->boolean('auto_reactivate_after_fallback')->default(false)->after('fallback_response');
            $table->integer('reactivate_delay_minutes')->nullable()->after('auto_reactivate_after_fallback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_reactivate_after_fallback', 'reactivate_delay_minutes']);
        });
    }
};
