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
        Schema::create('risks', function (Blueprint $table) {

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('phase_id')
                ->nullable()
                ->constrained('project_phases')
                ->nullOnDelete();

            $table->string('title');

            $table->text('description');

            $table->enum('impact', [
                'low',
                'medium',
                'high'
            ]);

            $table->enum('probability', [
                'low',
                'medium',
                'high'
            ]);

            $table->text('mitigation_plan')
                ->nullable();

            $table->string('status')->default('active');

            $table->foreignId('reported_by')
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
        Schema::dropIfExists('risks');
    }
};
