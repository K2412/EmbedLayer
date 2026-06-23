<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_access_policies', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignUlid('semantic_model_id')
                ->constrained('analytics_semantic_models')
                ->cascadeOnDelete();

            $table->string('name');
            $table->json('rules');

            $table->boolean('is_required')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_access_policies');
    }
};
