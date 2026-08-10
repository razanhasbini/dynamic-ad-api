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
        Schema::create('category_field_options', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('olx_id')->unique();

            $table->foreignId('category_field_id')
                ->constrained('category_fields')
                ->restrictOnDelete();

            $table->foreignId('parent_option_id')
                ->nullable()
                ->constrained('category_field_options')
                ->restrictOnDelete();

            $table->string('value');

            $table->string('label');

            $table->string('slug')->nullable();

            $table->integer('display_priority')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_options');
    }
};
