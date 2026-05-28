<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->string('status')->default('active')->after('mitigation_plan');
        });

        Schema::table('blockers', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('resolved');
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('blockers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
