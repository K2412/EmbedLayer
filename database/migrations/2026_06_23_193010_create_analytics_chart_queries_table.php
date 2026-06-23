<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_chart_queries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('chart_id')
                ->constrained('analytics_charts')
                ->cascadeOnDelete();

            $table->json('semantic_query');

            $table->timestamps();

            $table->unique('chart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_chart_queries');
    }
};
