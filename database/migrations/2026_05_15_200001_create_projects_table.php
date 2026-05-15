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
        Schema::create('projects', function (Blueprint $table) {

    $table->id();

    $table->string('name');
    $table->text('description')->nullable();

    $table->enum('status', [
        'planning',
        'active',
        'on_hold',
        'completed',
        'cancelled'
    ])->default('planning');

    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();

    $table->decimal('budget', 14, 2)->nullable();

    $table->unsignedTinyInteger('progress')->default(0);

    $table->foreignId('created_by')
        ->constrained('users');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
