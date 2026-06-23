<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_semantic_models', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('semantic_provider_id')
                ->constrained('analytics_semantic_providers')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('label');
            $table->text('description')->nullable();

            $table->string('base_table')->nullable();
            $table->string('base_table_alias')->nullable();

            $table->boolean('is_enabled')->default(true);
            $table->integer('version')->default(1);

            $table->timestamps();

            $table->unique(['semantic_provider_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_semantic_models');
    }
};
