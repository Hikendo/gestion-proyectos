<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $Blueprint) {
            $Blueprint->id();
            $Blueprint->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $Blueprint->string('token')->unique();
            $Blueprint->string('platform')->nullable(); // e.g., 'web', 'mobile'
            $Blueprint->string('browser')->nullable();  // e.g., 'Chrome', 'Safari'
            $Blueprint->string('device_name')->nullable();
            $Blueprint->timestamp('last_used_at')->nullable();
            $Blueprint->timestamps();

            // Índices para búsquedas masivas rápidas por usuario
            $Blueprint->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
