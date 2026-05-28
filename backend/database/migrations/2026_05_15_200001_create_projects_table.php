<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();           // ← agregado
            $table->text('description')->nullable();

            $table->enum('status', [
                'planning',
                'active',
                'on_hold',
                'completed',
                'cancelled',
            ])->default('planning');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('budget', 14, 2)->nullable();

            $table->unsignedTinyInteger('progress')->default(0);

            $table->foreignId('owner_id')              // ← era created_by
                ->constrained('users');

            $table->softDeletes();                     // ← agregado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
