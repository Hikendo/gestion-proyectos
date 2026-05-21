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
        Schema::create('tasks', function (Blueprint $table) {

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('phase_id')
                ->nullable()
                ->constrained('project_phases')
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->constrained('users');

            $table->string('title');

            $table->text('description')->nullable();

            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'critical'
            ])->default('medium');

            $table->enum('status', [
                'pending',
                'in_progress',
                'review',    // ← era 'testing'
                'done',      // ← era 'completed'
                'blocked',
            ])->default('pending');

            $table->timestamp('due_date')->nullable();

            $table->integer('estimated_hours')->nullable();
            $table->integer('worked_hours')->default(0);

            $table->unsignedTinyInteger('progress')
                ->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
