<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_charts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('dashboard_id')
                ->constrained('analytics_dashboards')
                ->cascadeOnDelete();
            $table->foreignUlid('dashboard_tab_id')->nullable()
                ->constrained('analytics_dashboard_tabs')
                ->nullOnDelete();
            $table->foreignUlid('semantic_model_id')
                ->constrained('analytics_semantic_models')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('chart_type'); // number_card, bar_chart, line_chart, table

            $table->json('options')->nullable();

            $table->timestamps();

            $table->index(['dashboard_id', 'dashboard_tab_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_charts');
    }
};
