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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('olx_id')->unique();

            $table->string('olx_external_id')->unique();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('name');

            $table->string('slug')->nullable();

            $table->unsignedTinyInteger('level')->nullable();

            $table->integer('display_priority')->nullable();

            $table->string('purpose')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
