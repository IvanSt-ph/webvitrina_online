<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
            $table->string('archive_reason', 50)->nullable();
        });

        // Existing detached values are history, not candidates for automatic restoration.
        DB::table('attribute_values')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('products')
                ->join('attribute_category', 'attribute_category.category_id', '=', 'products.category_id')
                ->whereColumn('products.id', 'attribute_values.product_id')
                ->whereColumn('attribute_category.attribute_id', 'attribute_values.attribute_id');
        })->update(['archived_at' => now(), 'archive_reason' => 'legacy_detached']);

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign(['attribute_id']);
            $table->foreign('attribute_id')->references('id')->on('attributes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible without losing archive semantics. Restore a reviewed backup instead.');
    }
};
