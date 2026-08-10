<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_field_values', function (Blueprint $table) {
            $table->dropUnique([
                'ad_id',
                'category_field_id',
            ]);
        });

        DB::statement(
            'CREATE UNIQUE INDEX ad_field_values_scalar_unique
            ON ad_field_values (ad_id, category_field_id)
            WHERE category_field_option_id IS NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX ad_field_values_option_unique
            ON ad_field_values (ad_id, category_field_id, category_field_option_id)
            WHERE category_field_option_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS ad_field_values_scalar_unique'
        );

        DB::statement(
            'DROP INDEX IF EXISTS ad_field_values_option_unique'
        );

        Schema::table('ad_field_values', function (Blueprint $table) {
            $table->unique([
                'ad_id',
                'category_field_id',
            ]);
        });
    }
};
