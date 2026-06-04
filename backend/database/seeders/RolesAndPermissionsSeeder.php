<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar cache de permisos antes de empezar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ----------------------------------------------------------------
        // PERMISOS
        // ----------------------------------------------------------------

        $permissions = [

            // Proyectos
            'project.view',
            'project.create',
            'project.edit',
            'project.delete',
            'project.assign-members',

            // Fases
            'phase.view',
            'phase.create',
            'phase.edit',
            'phase.delete',

            // Tareas
            'task.view',
            'task.create',
            'task.edit',
            'task.delete',
            'task.assign',           // asignar tarea a un usuario
            'task.update-status',    // mover estado (in progress, done, etc)
            'task.log-time',         // registrar tiempo trabajado

            // Tickets
            'ticket.view',
            'ticket.create',
            'ticket.edit',
            'ticket.delete',
            'ticket.assign',

            // Riesgos
            'risk.view',
            'risk.create',
            'risk.edit',
            'risk.delete',

            // Blockers
            'blocker.view',
            'blocker.create',
            'blocker.edit',
            'blocker.resolve',

            // Milestones
            'milestone.view',
            'milestone.create',
            'milestone.edit',
            'milestone.delete',

            // Entregables
            'deliverable.view',
            'deliverable.create',
            'deliverable.edit',
            'deliverable.approve',

            // Objetivos
            'objective.view',
            'objective.create',
            'objective.edit',

            // Métricas y reportes
            'metrics.view',
            'reports.view',

            // Usuarios
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            // Dashboard
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ----------------------------------------------------------------
        // ROLES Y ASIGNACIÓN DE PERMISOS
        // ----------------------------------------------------------------

        // ── SUPER ADMIN ─────────────────────────────────────────────────
        // Tiene todos los permisos. En Spatie con gate-before esto es
        // suficiente, pero asignamos explícitamente para que getAllPermissions()
        // lo devuelva correctamente en el AuthController.
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        // ── PROJECT MANAGER ─────────────────────────────────────────────
        // Crea proyectos, arma equipo, asigna tareas, gestiona todo el ciclo.
        $projectManager = Role::firstOrCreate(['name' => 'project-manager']);
        $projectManager->syncPermissions([
            'dashboard.view',
            'project.view',
            'project.create',
            'project.edit',
            'project.assign-members',
            'phase.view',
            'phase.create',
            'phase.edit',
            'phase.delete',
            'task.view',
            'task.create',
            'task.edit',
            'task.assign',
            'task.update-status',
            'ticket.view',
            'ticket.edit',
            'ticket.assign',
            'risk.view',
            'risk.create',
            'risk.edit',
            'blocker.view',
            'blocker.create',
            'blocker.edit',
            'blocker.resolve',
            'milestone.view',
            'milestone.create',
            'milestone.edit',
            'deliverable.view',
            'deliverable.create',
            'deliverable.edit',
            'deliverable.approve',
            'objective.view',
            'objective.create',
            'objective.edit',
            'metrics.view',
            'reports.view',
            'user.view',
        ]);

        // ── DEVELOPER ───────────────────────────────────────────────────
        // Recibe tareas, registra tiempo, reporta blockers, abre tickets.
        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view',
            'task.create',
            'task.edit',           // ← agregar
            'task.update-status',
            'task.log-time',
            'ticket.view',
            'ticket.create',
            'risk.view',
            'blocker.view',
            'blocker.create',
            'milestone.view',
            'deliverable.view',
            'objective.view',
            'metrics.view',
        ]);

        // ── QA ──────────────────────────────────────────────────────────
        // Valida entregables, reporta bugs via tickets, mueve estados de tarea.
        $qa = Role::firstOrCreate(['name' => 'qa']);
        $qa->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view',
            'task.create',
            'task.edit',
            'task.update-status',
            'ticket.view',
            'ticket.create',
            'ticket.edit',
            'risk.view',
            'blocker.view',
            'blocker.create',
            'milestone.view',
            'deliverable.view',
            'objective.view',
            'metrics.view',
        ]);

        // ── SUPPORT ─────────────────────────────────────────────────────
        // Gestiona tickets de soporte, los asigna internamente.
        $support = Role::firstOrCreate(['name' => 'support']);
        $support->syncPermissions([
            'dashboard.view',
            'project.view',
            'task.view',
            'ticket.view',
            'ticket.create',
            'ticket.edit',
            'ticket.assign',
            'blocker.view',
            'user.view',
        ]);

        // ── CLIENT ──────────────────────────────────────────────────────
        // Ve el progreso, abre tickets, ve entregables y milestones.
        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'dashboard.view',
            'project.view',
            'ticket.view',
            'ticket.create',
            'milestone.view',
            'deliverable.view',
            'objective.view',
            'metrics.view',
            'reports.view',
        ]);

        if ($this->command) {
            $this->command->info('Roles y permisos creados correctamente.');
        }
    }
}
