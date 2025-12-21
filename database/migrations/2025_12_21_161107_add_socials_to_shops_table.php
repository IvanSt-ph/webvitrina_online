<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Добавляем поля соцсетей, nullable, чтобы не было обязательных
            $table->string('facebook')->nullable()->after('phone');
            $table->string('instagram')->nullable()->after('facebook');
            $table->string('telegram')->nullable()->after('instagram');
            $table->string('whatsapp')->nullable()->after('telegram');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['facebook', 'instagram', 'telegram', 'whatsapp']);
        });
    }
};
