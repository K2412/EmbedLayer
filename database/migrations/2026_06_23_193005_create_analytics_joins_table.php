<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_joins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('semantic_model_id')
                ->constrained('analytics_semantic_models')
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('left_table_alias');
            $table->string('left_column');

            $table->string('right_table');
            $table->string('right_table_alias');
            $table->string('right_column');

            $table->string('type')->default('left'); // inner, left
            $table->string('relationship'); // one_to_one, many_to_one, one_to_many

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_joins');
    }
};
