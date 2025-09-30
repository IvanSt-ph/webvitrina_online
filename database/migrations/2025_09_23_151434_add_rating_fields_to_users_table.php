<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_rating_fields_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0)->after('remember_token');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'reviews_count']);
        });
    }
};
