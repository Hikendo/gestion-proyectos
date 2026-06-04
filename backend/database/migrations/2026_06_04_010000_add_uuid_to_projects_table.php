<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'uuid')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->nullable()->unique();
        });

        // Poblar UUIDs para proyectos existentes
        DB::table('projects')->whereNull('uuid')->orderBy('id')->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                DB::table('projects')->where('id', $project->id)->update([
                    'uuid' => (string) Str::uuid(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
