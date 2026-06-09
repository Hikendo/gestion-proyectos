<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support modifyColumn, so we drop and recreate
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate the columns as nullable
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropMorphs('attachable');
            });
            Schema::table('attachments', function (Blueprint $table) {
                $table->nullableMorphs('attachable');
            });
        } else {
            // MySQL / PostgreSQL: standard column modification
            Schema::table('attachments', function (Blueprint $table) {
                $table->string('attachable_type')->nullable()->change();
                $table->unsignedBigInteger('attachable_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropMorphs('attachable');
            });
            Schema::table('attachments', function (Blueprint $table) {
                $table->morphs('attachable');
            });
        } else {
            Schema::table('attachments', function (Blueprint $table) {
                $table->string('attachable_type')->nullable(false)->change();
                $table->unsignedBigInteger('attachable_id')->nullable(false)->change();
            });
        }
    }
};