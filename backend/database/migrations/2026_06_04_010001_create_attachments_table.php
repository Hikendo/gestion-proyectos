<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->morphs('attachable');          // attachable_type + attachable_id
            $table->string('original_name');
            $table->string('disk_path');           // projects/{project_uuid}/{attachment_uuid}.ext
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
