<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reports', function (Blueprint $table) {
            $table->string('action_taken')->nullable()->after('resolution');
        });
    }

    public function down(): void
    {
        Schema::table('product_reports', function (Blueprint $table) {
            $table->dropColumn('action_taken');
        });
    }
};
