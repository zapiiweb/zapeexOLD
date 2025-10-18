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
            // Habilita/desabilita reativação automática da IA após fallback
            $table->boolean('auto_reactivate_ai')->default(false)->after('fallback_response');
            
            // Tempo em minutos para reativar a IA (null = imediato após resposta manual)
            $table->integer('reactivation_delay_minutes')->nullable()->after('auto_reactivate_ai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_reactivate_ai', 'reactivation_delay_minutes']);
        });
    }
};
