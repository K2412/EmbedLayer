<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_measures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('semantic_model_id')
                ->constrained('analytics_semantic_models')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('label');
            $table->text('description')->nullable();

            $table->string('type');
            // count, count_distinct, sum, avg, min, max, ratio, calculated

            $table->string('column')->nullable();
            $table->json('expression')->nullable();
            $table->json('filters')->nullable();

            $table->string('format')->nullable();
            // currency, percent, number, duration

            $table->boolean('is_public')->default(true);

            $table->timestamps();

            $table->unique(['semantic_model_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_measures');
    }
};
