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
        // PERMISOS GRANULARES
        // ----------------------------------------------------------------
        // Se separan permisos genéricos (p.ej. task.edit) en acciones
        // atómicas para evitar que un rol tenga poder no deseado sobre
        // contenido sensible o flujos críticos.

        $permissions = [

            // ── Proyectos ──────────────────────────────────────────────
            'project.view',
            'project.create',
            'project.edit',              // editar metadatos (nombre, fechas, presupuesto)
            'project.delete',
            'project.assign-members',
            'project.manage-attachments', // subir/eliminar adjuntos del proyecto

            // ── Fases ──────────────────────────────────────────────────
            'phase.view',
            'phase.create',
            'phase.edit',
            'phase.delete',

            // ── Tareas ─────────────────────────────────────────────────
            'task.view',
            'task.create',
            'task.edit-content',         // editar título, descripción, criterios de aceptación
            'task.edit-own',             // editar SOLO tareas propias (asignadas a mí)
            'task.delete',
            'task.assign',
            'task.update-status',        // mover estado (in progress, done, etc.)
            'task.log-time',
            'task.manage-attachments',   // subir/eliminar adjuntos de tareas

            // ── Tickets ────────────────────────────────────────────────
            'ticket.view',
            'ticket.create',
            'ticket.edit-own',           // editar SOLO tickets propios (creados por mí)
            'ticket.edit-any',           // editar CUALQUIER ticket del proyecto
            'ticket.delete',
            'ticket.assign',
            'ticket.manage-attachments',

            // ── Riesgos ────────────────────────────────────────────────
            'risk.view',
            'risk.create',
            'risk.edit',
            'risk.delete',

            // ── Blockers ───────────────────────────────────────────────
            'blocker.view',
            'blocker.create',
            'blocker.edit',
            'blocker.resolve',

            // ── Milestones ─────────────────────────────────────────────
            'milestone.view',
            'milestone.create',
            'milestone.edit',
            'milestone.delete',

            // ── Entregables ────────────────────────────────────────────
            'deliverable.view',
            'deliverable.create',
            'deliverable.edit',
            'deliverable.approve',

            // ── Objetivos ──────────────────────────────────────────────
            'objective.view',
            'objective.create',
            'objective.edit',

            // ── Métricas y reportes ────────────────────────────────────
            'metrics.view',
            'reports.view',

            // ── Usuarios ───────────────────────────────────────────────
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',

            // ── Dashboard ──────────────────────────────────────────────
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
            'project.manage-attachments',
            'phase.view',
            'phase.create',
            'phase.edit',
            'phase.delete',
            'task.view',
            'task.create',
            'task.edit-content',
            'task.delete',
            'task.assign',
            'task.update-status',
            'task.manage-attachments',
            'ticket.view',
            'ticket.edit-any',
            'ticket.assign',
            'ticket.manage-attachments',
            'risk.view',
            'risk.create',
            'risk.edit',
            'risk.delete',
            'blocker.view',
            'blocker.create',
            'blocker.edit',
            'blocker.resolve',
            'milestone.view',
            'milestone.create',
            'milestone.edit',
            'milestone.delete',
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
        // NO tiene task.edit-content (no puede alterar descripción/criterios).
        // NO tiene task.manage-attachments (no puede borrar adjuntos del PM).
        // task.edit-own se valida en Policy (solo tareas asignadas a él).
        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view',
            'task.create',
            'task.edit-own',           // solo su tarea, solo si no está Done
            'task.update-status',
            'task.log-time',
            'ticket.view',
            'ticket.create',
            'ticket.edit-own',         // solo tickets que él creó
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
        // NO tiene task.edit-content (no puede alterar criterios de aceptación).
        // NO tiene task.manage-attachments.
        $qa = Role::firstOrCreate(['name' => 'qa']);
        $qa->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view',
            'task.create',
            'task.edit-own',           // solo tareas asignadas a QA
            'task.update-status',
            'ticket.view',
            'ticket.create',
            'ticket.edit-own',         // solo tickets que creó
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
        // NO tiene ticket.edit-any (no puede modificar estimaciones técnicas
        // ni prioridades del PM). Solo edita sus propios tickets.
        $support = Role::firstOrCreate(['name' => 'support']);
        $support->syncPermissions([
            'dashboard.view',
            'project.view',
            'task.view',
            'ticket.view',
            'ticket.create',
            'ticket.edit-own',         // solo tickets que creó
            'ticket.assign',
            'blocker.view',
            'user.view',
        ]);

        // ── CLIENT ──────────────────────────────────────────────────────
        // Ve el progreso, abre tickets, ve entregables y milestones.
        // NO tiene ticket.edit-any (no puede alterar tickets en progreso/resueltos).
        // ticket.edit-own se valida en Policy (solo tickets que creó, y solo si
        // el ticket está en estado Open — no puede modificar tickets ya en progreso).
        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'dashboard.view',
            'project.view',
            'ticket.view',
            'ticket.create',
            'ticket.edit-own',         // solo tickets propios, solo si Open
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
