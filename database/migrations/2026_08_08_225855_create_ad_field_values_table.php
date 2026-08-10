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
        Schema::create('ad_field_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ad_id')
                ->constrained('ads')
                ->cascadeOnDelete();

            $table->foreignId('category_field_id')
                ->constrained('category_fields')
                ->restrictOnDelete();

            $table->foreignId('category_field_option_id')
                ->nullable()
                ->constrained('category_field_options')
                ->restrictOnDelete();

            $table->text('value')->nullable();

            $table->timestamps();

            $table->unique(['ad_id', 'category_field_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_field_values');
    }
};
