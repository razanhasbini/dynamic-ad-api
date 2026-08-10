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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('olx_id')->unique();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->restrictOnDelete();

            $table->foreignId('parent_field_id')
                ->nullable()
                ->constrained('category_fields')
                ->restrictOnDelete();

            $table->string('attribute');
            $table->string('name');

            $table->string('value_type');
            $table->string('filter_type')->nullable();

            $table->boolean('is_mandatory')->default(false);

            $table->string('state')->nullable();

            $table->json('roles')->nullable();

            $table->decimal('min_value', 20, 4)->nullable();
            $table->decimal('max_value', 20, 4)->nullable();

            $table->unsignedInteger('min_length')->nullable();
            $table->unsignedInteger('max_length')->nullable();

            $table->integer('display_priority')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
