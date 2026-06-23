<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_dimensions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('semantic_model_id')
                ->constrained('analytics_semantic_models')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('label');
            $table->text('description')->nullable();

            $table->string('type'); // string, number, boolean, time

            $table->string('column');
            $table->string('table_alias')->nullable();

            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_groupable')->default(true);
            $table->boolean('is_public')->default(true);

            $table->json('allowed_time_grains')->nullable();

            $table->timestamps();

            $table->unique(['semantic_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_dimensions');
    }
};
