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
        Schema::create('deliverables', function (Blueprint $table) {

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('phase_id')
                ->nullable()
                ->constrained('project_phases')
                ->nullOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('deliverables')
                ->nullOnDelete();

            $table->string('name');

            $table->text('description')->nullable();

            $table->date('delivery_date')->nullable();

            $table->boolean('approved')
                ->default(false);

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
