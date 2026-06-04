<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $Blueprint) {
            $Blueprint->id();
            $Blueprint->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $Blueprint->string('title');
            $Blueprint->text('body');
            $Blueprint->string('type'); // e.g., 'task_assigned', 'project_updated'
            $Blueprint->json('data')->nullable(); // Payload personalizado
            $Blueprint->string('status')->default('pending'); // 'pending', 'sent', 'failed'
            $Blueprint->timestamp('sent_at')->nullable();
            $Blueprint->timestamp('read_at')->nullable();
            $Blueprint->timestamps();

            // Índices compuestos para optimizar bandejas de entrada y filtrados de estado
            $Blueprint->index(['user_id', 'status']);
            $Blueprint->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
