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
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->string('baileys_session_id')->nullable()->after('meta_app_id');
            $table->boolean('baileys_connected')->default(false)->after('baileys_session_id');
            $table->timestamp('baileys_connected_at')->nullable()->after('baileys_connected');
            $table->string('baileys_phone_number')->nullable()->after('baileys_connected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['baileys_session_id', 'baileys_connected', 'baileys_connected_at', 'baileys_phone_number']);
        });
    }
};
