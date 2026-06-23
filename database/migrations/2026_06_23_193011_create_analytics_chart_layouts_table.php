<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_chart_layouts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('chart_id')
                ->constrained('analytics_charts')
                ->cascadeOnDelete();
            $table->foreignUlid('dashboard_tab_id')->nullable()
                ->constrained('analytics_dashboard_tabs')
                ->nullOnDelete();

            $table->integer('grid_x')->default(0);
            $table->integer('grid_y')->default(0);
            $table->integer('grid_w')->default(4);
            $table->integer('grid_h')->default(3);

            $table->timestamps();

            $table->index(['dashboard_tab_id', 'chart_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_chart_layouts');
    }
};
