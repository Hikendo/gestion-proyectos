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
        Schema::create('project_metrics', function (Blueprint $table) {

    $table->id();

    $table->foreignId('project_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->integer('total_tasks')->default(0);
    $table->integer('completed_tasks')->default(0);

    $table->integer('open_tickets')->default(0);

    $table->integer('total_blockers')->default(0);

    $table->decimal('completion_rate', 5, 2)
        ->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_metrics');
    }
};
