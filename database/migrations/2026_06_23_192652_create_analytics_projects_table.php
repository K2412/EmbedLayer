<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_projects');
    }
};
