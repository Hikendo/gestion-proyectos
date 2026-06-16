<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acceptance_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')
                ->constrained('project_phases')
                ->cascadeOnDelete();
            $table->text('description');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acceptance_criteria');
    }
};
