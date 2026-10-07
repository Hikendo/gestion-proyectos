<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Valores válidos según App\Enums\ProjectMemberRole.
     *
     * @var array<int, string>
     */
    private array $finalRoles = ['manager', 'developer', 'qa', 'support', 'client'];

    public function up(): void
    {
        // Solo MySQL: la columna `role` de producción se creó durante la ventana
        // en la que el rol se llamaba 'analyst' (ver commits a6d0a63 y b395879),
        // por lo que su ENUM todavía contiene 'analyst' en lugar de 'support'.
        // SQLite (tests) recrea la tabla desde cero con el set correcto, así que
        // no requiere ninguna acción aquí.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $quote = fn (array $values): string
            => implode(',', array_map(static fn (string $v): string => "'{$v}'", $values));

        // 1) Ampliar el ENUM para aceptar tanto el valor antiguo ('analyst') como
        //    el nuevo ('support'), sin pérdida de datos.
        DB::statement(
            'ALTER TABLE project_members MODIFY role ENUM('
            .$quote([...$this->finalRoles, 'analyst'])
            .') NOT NULL'
        );

        // 2) Normalizar el valor heredado: 'analyst' era el nombre antiguo de 'support'.
        DB::table('project_members')
            ->where('role', 'analyst')
            ->update(['role' => 'support']);

        // 3) Fijar el ENUM definitivo (coincide con App\Enums\ProjectMemberRole).
        DB::statement(
            'ALTER TABLE project_members MODIFY role ENUM('
            .$quote($this->finalRoles)
            .') NOT NULL'
        );
    }

    public function down(): void
    {
        // No reversible de forma segura: no se puede saber qué miembros eran
        // originalmente 'analyst'. La migración es idempotente si se re-ejecuta.
    }
};
