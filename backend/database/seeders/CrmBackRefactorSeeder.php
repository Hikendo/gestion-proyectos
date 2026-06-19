<?php

namespace Database\Seeders;

use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Objective;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectPhase;
use App\Models\ProjectPlan;
use App\Models\Risk;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskTimeLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmBackRefactorSeeder extends Seeder
{
    public function run(): void
    {
        $pm      = User::where('email', 'pm@test.com')->firstOrFail();
        $dev     = User::where('email', 'dev@test.com')->firstOrFail();
        $qa      = User::where('email', 'qa@test.com')->firstOrFail();
        $support = User::where('email', 'support@test.com')->firstOrFail();
        $client  = User::where('email', 'client@test.com')->firstOrFail();

        // ════════════════════════════════════════════════════════════════════
        //  PROYECTO — crm-back Refactorización
        // ════════════════════════════════════════════════════════════════════
        $project = Project::firstOrCreate(
            ['code' => 'CRM-REFACT-001'],
            [
                'name'        => 'crm-back — Refactorización Integral',
                'description' => 'Modernizar y estabilizar el sistema CRM-Back (Sistema de Inversión) de forma incremental, sin interrumpir la operación, para alcanzar madurez técnica que permita: cumplimiento regulatorio (KYC/AML), automatización de flujos financieros, arquitectura mantenible y escalable, y un modelo de autorización granular.',
                'status'      => 'active',
                'start_date'  => '2026-06-17',
                'end_date'    => '2026-10-17',
                'budget'      => 180000.00,
                'progress'    => 5,
                'owner_id'    => $pm->id,
            ]
        );

        // ── Miembros ────────────────────────────────────────────────────────
        $this->assignMembers($project, [
            [$pm->id,      'manager'],
            [$dev->id,     'developer'],
            [$qa->id,      'qa'],
            [$support->id, 'support'],
            [$client->id,  'client'],
        ]);

        // ── Fases ────────────────────────────────────────────────────────────
        $phase0 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 0 — Descubrimiento y Alineación'],
            ['start_date' => '2026-06-17', 'end_date' => '2026-07-08', 'progress' => 15]
        );
        $phase1 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 1 — Documentación Viva y Línea Base'],
            ['start_date' => '2026-06-25', 'end_date' => '2026-07-22', 'progress' => 0]
        );
        $phase2 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 2 — Estabilización Regulatoria y Operativa'],
            ['start_date' => '2026-07-09', 'end_date' => '2026-08-19', 'progress' => 0]
        );
        $phase3 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 3 — Estandarización Arquitectónica'],
            ['start_date' => '2026-08-06', 'end_date' => '2026-09-10', 'progress' => 0]
        );
        $phase4 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 4 — Refactorización Controlada (Calidad Interna)'],
            ['start_date' => '2026-08-27', 'end_date' => '2026-09-24', 'progress' => 0]
        );
        $phase5 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 5 — Extensión Funcional'],
            ['start_date' => '2026-09-10', 'end_date' => '2026-10-15', 'progress' => 0]
        );
        $phase6 = ProjectPhase::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Fase 6 — Validación y Estabilización Final'],
            ['start_date' => '2026-10-01', 'end_date' => '2026-10-17', 'progress' => 0]
        );

        // ── Objetivos generales y específicos ────────────────────────────────

        // Objetivo general del proyecto
        Objective::firstOrCreate(
            ['project_id' => $project->id, 'type' => 'general', 'title' => 'Modernizar y estabilizar incrementalmente el sistema CRM-Back'],
            ['description' => '<p>Alcanzar un estado de madurez técnica que permita: cumplimiento regulatorio (KYC/AML), automatización de flujos financieros, arquitectura mantenible y escalable, y un modelo de autorización granular.</p>', 'completed' => false]
        );

        // Objetivos específicos (asociados a fases)
        $this->createObjectives($project, [
            [$phase2->id, 'specific', 'Cerrar brechas regulatorias críticas (KYC/AML)',         '<p>Implementar validación de identidad, listas negras y firma electrónica vinculante en contratos.</p>',                              false],
            [$phase2->id, 'specific', 'Automatizar flujos manuales (fondeo, scheduler, conciliación)', '<p>Activar Scheduler con tareas mínimas y notificaciones email transaccional.</p>',                                                       false],
            [$phase3->id, 'specific', 'Unificar arquitectura híbrida en estándar consistente',    '<p>Service Layer + Repository Pattern + Policies en todas las entidades core.</p>',                                                      false],
            [$phase4->id, 'specific', 'Eliminar deuda técnica observable',                        '<p>Modelos huérfanos, código duplicado, nomenclatura inconsistente, DTOs unificados.</p>',                                               false],
            [$phase1->id, 'specific', 'Establecer base de documentación técnica viva',            '<p>ADR, OpenAPI 3.0, diagramas Mermaid mantenibles, colección Postman actualizada, README de onboarding.</p>',                          false],
            [$phase4->id, 'specific', 'Preparar arquitectura para evolución futura',              '<p>Implementar Event-Driven Architecture sin comprometer estabilidad operativa.</p>',                                                    false],
        ]);

        // ── Hitos ────────────────────────────────────────────────────────────
        $this->createMilestones($project, [
            ['Auditoría Validada con Stakeholders',                  '2026-07-08', false],
            ['Documentación Base Publicada (ADR + OpenAPI)',         '2026-07-22', false],
            ['Cumplimiento Regulatorio Mínimo Alcanzado',            '2026-08-19', false],
            ['Automatización Operativa Básica (Scheduler + Email)',  '2026-08-19', false],
            ['Arquitectura Unificada (Services + Repos + Policies)', '2026-09-10', false],
            ['Sistema Desacoplado con Eventos de Dominio',           '2026-09-24', false],
            ['Funcionalidad Extendida (Workflow, Dashboard, CRM)',   '2026-10-15', false],
            ['Sistema Estabilizado y Validado (Test Suite + Docs)',  '2026-10-17', false],
        ]);

        // ── Entregables ──────────────────────────────────────────────────────
        // Fase 0
        $this->createDeliverables($project, [
            [$phase0->id, 'Acta de validación de hallazgos',                '<p>Hallazgos confirmados vs descartados con stakeholders.</p>',                                   '2026-07-01', false],
            [$phase0->id, 'Mapa de stakeholders y responsables funcionales', '<p>Responsables designados por módulo y área funcional.</p>',                                      '2026-07-05', false],
            [$phase0->id, 'Lista priorizada de requerimientos no cubiertos', '<p>Requerimientos de negocio identificados y priorizados.</p>',                                    '2026-07-08', false],
            // Fase 1
            [$phase1->id, 'ADR inicial (5 decisiones documentadas)',        '<p>Sanctum vs Passport, Spatie vs Policies, Monolito, Redis queues, DomPDF.</p>',                 '2026-07-15', false],
            [$phase1->id, 'Especificación OpenAPI 3.0 (76 endpoints)',      '<p>Documentación interactiva con Schemas, Requests y Responses.</p>',                             '2026-07-20', false],
            [$phase1->id, 'Colección Postman actualizada',                  '<p>Todos los endpoints ejecutables con variables de entorno.</p>',                                '2026-07-22', false],
            // Fase 2
            [$phase2->id, 'Módulo de validación KYC/AML',                   '<p>Integración con proveedor externo de identidad y listas negras.</p>',                          '2026-08-10', false],
            [$phase2->id, 'Integración de firma electrónica vinculante',    '<p>Contratos de Adhesión, Invitación y LPOA con e-Firma.</p>',                                   '2026-08-15', false],
            [$phase2->id, 'Sistema de emails transaccionales (5 tipos)',    '<p>Bienvenida, cambio de contraseña, retiro creado/aprobado/rechazado, EDC disponible.</p>',      '2026-08-19', false],
            // Fase 3
            [$phase3->id, 'DTOs unificados en carpeta única',               '<p>Eliminar duplicación DTO/DTOS, actualizar todos los imports.</p>',                            '2026-08-25', false],
            [$phase3->id, 'Controladores de Inversionistas unificados',     '<p>Un solo controller con Service Layer consistente.</p>',                                      '2026-09-01', false],
            [$phase3->id, 'Repositorios implementados (4 entidades core)',  '<p>Interfaces + implementaciones Eloquent para Inversionista, Movimiento, Retiro, Comisión.</p>', '2026-09-08', false],
        ]);

        // ── Riesgos ──────────────────────────────────────────────────────────
        $this->createRisks($project, [
            // Alto
            ['Sin KYC/AML — riesgo regulatorio',         '<p>El sistema opera sin validación de identidad ni listas negras, exponiendo a sanciones.</p>',                                                                     'high',   'high',   'active',    '<p>Implementar KYC/AML en Fase 2. Contratar proveedor especializado.</p>'],
            ['Firma no vinculante en contratos',          '<p>Contratos firmados con PNG no tienen validez legal plena.</p>',                                                                                                'high',   'high',   'active',    '<p>Implementar firma electrónica vinculante. Definir tratamiento de contratos PNG existentes.</p>'],
            ['Conciliación manual — errores financieros', '<p>Errores en registro de fondeos pueden causar discrepancias en saldos.</p>',                                                                                     'high',   'medium', 'active',    '<p>Integrar conciliación bancaria automática vía STP/SPEI. Doble validación en movimientos.</p>'],
            ['Regresiones al unificar controladores',     '<p>Fusionar InversionistaController e InversionistasController puede romper endpoints sin test suite.</p>',                                                         'high',   'medium', 'active',    '<p>Priorizar test suite de regresión. Feature flags y despliegue canary.</p>'],
            ['Pérdida de notificaciones si FCM falla',    '<p>Sin canal alternativo (email), eventos críticos pueden no ser comunicados al cliente.</p>',                                                                     'high',   'low',    'active',    '<p>Implementar email transaccional como canal alternativo en Fase 2.</p>'],
            // Medio
            ['Modelos huérfanos usados externamente',     '<p>Si otros sistemas leen las tablas de modelos a eliminar, se romperían integraciones.</p>',                                                                      'medium', 'medium', 'active',    '<p>Auditar dependencias externas antes de eliminar. Mantener tablas, eliminar solo modelos Eloquent.</p>'],
            ['Scheduler sin tareas programadas',          '<p>No hay recordatorios automáticos, limpieza de tokens, ni reportes periódicos.</p>',                                                                             'medium', 'high',   'active',    '<p>Activar Scheduler con tareas mínimas en Fase 2.</p>'],
            ['Roles insuficientes (solo 4)',              '<p>No cubren necesidades operativas reales (Comercial, Finanzas, MesaNegocios, etc.).</p>',                                                                        'medium', 'high',   'active',    '<p>Crear 5 roles adicionales en Fase 2.</p>'],
            ['Conflicto Spatie Permission vs Policies',   '<p>Dos sistemas de autorización pueden generar confusión si no se documenta la convivencia.</p>',                                                                  'medium', 'low',    'mitigated', '<p>Documentar regla: Policies para autorización de entidad, Spatie para permisos de módulo.</p>'],
            ['Renombrar tablas rompe integraciones',      '<p>Cambiar nombres de tablas legacy afectaría sistemas externos que las lean directamente.</p>',                                                                   'medium', 'low',    'active',    '<p>Coordinar con dueños de sistemas externos. Usar migraciones con feature flags.</p>'],
            // Bajo
            ['Código comentado en PermissionsCheck',      '<p>Los truncates comentados pueden inducir a error si se descomentan sin entender consecuencias.</p>',                                                             'low',    'low',    'active',    '<p>Limpiar código comentado en Fase 4.</p>'],
            ['ConsolidarRetiroRequest faltante',           '<p>El import existe pero el archivo no. Si se intenta usar causará error.</p>',                                                                                   'low',    'low',    'active',    '<p>Crear archivo o eliminar import en Fase 4.</p>'],
            ['Job SendFcmTokenJob sin uso',                '<p>Código muerto que genera confusión.</p>',                                                                                                                       'low',    'low',    'active',    '<p>Eliminar job o documentar propósito futuro en Fase 4.</p>'],
            ['Falta de API Versioning formal',             '<p>Si se requieren breaking changes no hay mecanismo para versionar.</p>',                                                                                        'low',    'low',    'active',    '<p>Implementar versionado por headers en Fase 3.</p>'],
        ]);

        // ── Tareas ───────────────────────────────────────────────────────────

        // --- Fase 0 ---
        $t1 = $this->createTask($project, $phase0, $pm, $pm, [
            'title'           => 'Validar hallazgos de auditoría con stakeholders',
            'description'     => '<p>Revisar los 33 módulos identificados, los 14 módulos recomendados y las brechas encontradas con al menos un representante de cada área: comercial, finanzas, legal, sistemas.</p>',
            'priority'        => 'critical',
            'status'          => 'in_progress',
            'due_date'        => '2026-06-28',
            'estimated_hours' => 40,
            'worked_hours'    => 8,
            'progress'        => 20,
        ]);
        $this->addComment($t1, $pm, 'Reunión inicial con Comercial agendada para mañana. Finanzas confirmó para el viernes.');

        $t2 = $this->createTask($project, $phase0, $dev, $pm, [
            'title'           => 'Definir requisitos regulatorios KYC/AML con Legal',
            'description'     => '<p>Documentar el nivel exacto de KYC/AML requerido por la regulación aplicable (CNBV, CONDUSEF, SAT). Identificar proveedores potenciales.</p>',
            'priority'        => 'critical',
            'status'          => 'pending',
            'due_date'        => '2026-07-05',
            'estimated_hours' => 24,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t3 = $this->createTask($project, $phase0, $support, $pm, [
            'title'           => 'Identificar bloqueadores externos',
            'description'     => '<p>Mapear dependencias: APIs bancarias (STP/SPEI), proveedores de KYC, firma electrónica. Confirmar disponibilidad y costos.</p>',
            'priority'        => 'high',
            'status'          => 'in_progress',
            'due_date'        => '2026-07-03',
            'estimated_hours' => 16,
            'worked_hours'    => 4,
            'progress'        => 25,
        ]);
        $this->addComment($t3, $support, 'Contacté a DocuSign y Jumio. Ambos enviaron cotización. Pendiente revisar con Finanzas.');

        // --- Fase 1 ---
        $t4 = $this->createTask($project, $phase1, $dev, $pm, [
            'title'           => 'Generar especificación OpenAPI 3.0 de endpoints existentes',
            'description'     => '<p>Documentar los 76 endpoints con Schemas, Requests y Responses. Evaluar uso de <code>scramble</code> o <code>scribe</code> para generación automática.</p>',
            'priority'        => 'high',
            'status'          => 'pending',
            'due_date'        => '2026-07-20',
            'estimated_hours' => 40,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t5 = $this->createTask($project, $phase1, $dev, $pm, [
            'title'           => 'Documentar ADR (Architecture Decision Records)',
            'description'     => '<p>Al menos 5 decisiones: Sanctum vs Passport, Spatie vs Policies nativas, monolito vs microservicios, Redis queues, DomPDF.</p>',
            'priority'        => 'high',
            'status'          => 'pending',
            'due_date'        => '2026-07-15',
            'estimated_hours' => 24,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t6 = $this->createTask($project, $phase1, $qa, $pm, [
            'title'           => 'Actualizar colección Postman con tests básicos',
            'description'     => '<p>Enriquecer la colección existente con variables de entorno y tests de smoke para cada endpoint.</p>',
            'priority'        => 'medium',
            'status'          => 'pending',
            'due_date'        => '2026-07-22',
            'estimated_hours' => 16,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        // --- Fase 2 ---
        $t7 = $this->createTask($project, $phase2, $dev, $pm, [
            'title'           => 'Implementar validación KYC/AML en flujo de registro',
            'description'     => '<p>Integrar con proveedor externo (Jumio/Onfido). Validación de INE, CURP, RFC. Scoring de riesgo básico. Listas negras AML.</p>',
            'priority'        => 'critical',
            'status'          => 'pending',
            'due_date'        => '2026-08-10',
            'estimated_hours' => 48,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t8 = $this->createTask($project, $phase2, $dev, $pm, [
            'title'           => 'Implementar firma electrónica en contratos',
            'description'     => '<p>Reemplazar captura PNG por integración con DocuSign/SignNow. Aplicar a contratos de Adhesión, Invitación y LPOA.</p>',
            'priority'        => 'critical',
            'status'          => 'pending',
            'due_date'        => '2026-08-15',
            'estimated_hours' => 40,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t9 = $this->createTask($project, $phase2, $qa, $pm, [
            'title'           => 'Implementar emails transaccionales (5 tipos)',
            'description'     => '<p>Templates HTML responsivos para: bienvenida, cambio de contraseña, retiro creado/aprobado/rechazado, EDC disponible. Queue con Redis.</p>',
            'priority'        => 'high',
            'status'          => 'pending',
            'due_date'        => '2026-08-19',
            'estimated_hours' => 32,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t10 = $this->createTask($project, $phase2, $dev, $pm, [
            'title'           => 'Activar Scheduler con tareas mínimas',
            'description'     => '<p>Configurar cron job para: limpieza de tokens expirados, recordatorios de EDC, generación de reportes periódicos.</p>',
            'priority'        => 'high',
            'status'          => 'pending',
            'due_date'        => '2026-08-01',
            'estimated_hours' => 16,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        // Bloqueador activo
        $this->createBlockers($project, [
            [$t2->id, $pm,  'Área Legal no ha definido alcance regulatorio', '<p>Sin claridad sobre qué nivel de KYC/AML exige la CNBV, no se puede dimensionar el esfuerzo de la Fase 2.</p>',                              'high',   false],
            [null,    $pm,  'Proveedor de firma electrónica no contratado',  '<p>DocuSign envió cotización pero el proceso de procurement está detenido en compras.</p>',                                                   'medium', false],
            [$t1->id, $dev, 'Stakeholder de Finanzas no disponible hasta julio', '<p>El director financiero está de vacaciones hasta el 5 de julio. No se puede validar el módulo de conciliación sin su input.</p>',           'medium', false],
        ]);

        // ── Tickets ──────────────────────────────────────────────────────────
        $this->createTickets($project, [
            [$client->id,  $pm->id,     '¿El sistema legacy seguirá funcionando durante la migración?', '<p>Necesitamos garantizar que los usuarios de RRHH puedan seguir usando el sistema antiguo mientras migramos.</p>',                                       'open',        'high'],
            [$qa->id,      $dev->id,    'Error 502 intermitente en staging al probar endpoints',         '<p>Al ejecutar más de 10 requests concurrentes en el entorno de staging, algunas devuelven 502 Bad Gateway.</p>',                                         'in_progress', 'high'],
            [$support->id, $dev->id,    'Falta documentar el flujo de fondeo PayPal manual',              '<p>El procedimiento operativo estándar no está escrito en ninguna parte. Solo lo conoce una persona.</p>',                                                'open',        'medium'],
            [$client->id,  null,        'Solicito dashboard de KPIs para seguimiento ejecutivo',          '<p>Como stakeholder necesito un dashboard con AUM, captación neta, churn y pipeline de retiros para el comité mensual.</p>',                            'open',        'low'],
        ]);

        // ── Plan del proyecto ──────────────────────────────────────────────
        ProjectPlan::firstOrCreate(
            ['project_id' => $project->id],
            [
                'scope' => '<h2>Alcance</h2>'
                    . '<p>Modernizar y estabilizar el sistema CRM-Back de forma incremental, sin interrumpir la operación, para que alcance un estado de madurez técnica que permita: cumplimiento regulatorio (KYC/AML), automatización de flujos financieros, arquitectura mantenible y escalable, y un modelo de autorización granular.</p>'
                    . '<h3>Incluye</h3>'
                    . '<ul>'
                    . '<li>Los 33 módulos identificados en la auditoría documental.</li>'
                    . '<li>Los 14 módulos recomendados, priorizados según su criticidad.</li>'
                    . '<li>6 fases de refactorización incremental.</li>'
                    . '<li>Documentación técnica viva (ADR, OpenAPI, diagramas Mermaid).</li>'
                    . '</ul>'
                    . '<h3>Queda fuera</h3>'
                    . '<ul>'
                    . '<li>Microservicios: se mantiene el monolito modular.</li>'
                    . '<li>Reescritura total: refactorización incremental sobre código existente.</li>'
                    . '<li>Cambios de framework: se permanece en Laravel 12.x.</li>'
                    . '<li>Frontend: alcance exclusivamente back-end (API).</li>'
                    . '<li>Infraestructura: no incluye cambios de servidor, CI/CD o cloud.</li>'
                    . '</ul>',
                'requirements' => '<h2>Requerimientos</h2>'
                    . '<h3>Técnicos</h3>'
                    . '<ol>'
                    . '<li><strong>PHP 8.3+</strong> — actualizar desde ^8.1 para aprovechar mejoras de rendimiento y typing.</li>'
                    . '<li><strong>Laravel 12.x</strong> — mantener versión actual, actualizar parches.</li>'
                    . '<li><strong>Redis</strong> — ya configurado para queues y cache.</li>'
                    . '<li><strong>MySQL/MariaDB</strong> — mantener motor de BD actual.</li>'
                    . '<li><strong>Laravel Octane</strong> — ya configurado; validar uso en producción.</li>'
                    . '<li><strong>Laravel Horizon</strong> — ya configurado; asegurar persistencia.</li>'
                    . '<li><strong>Proveedor KYC/AML</strong> — a contratar (Jumio, Onfido, Trulioo).</li>'
                    . '<li><strong>Proveedor de firma electrónica</strong> — a contratar (DocuSign, SignNow).</li>'
                    . '<li><strong>API bancaria</strong> — STP/SPEI para conciliación automática.</li>'
                    . '<li><strong>Servicio de email transaccional</strong> — Resend, Mailgun o Postmark.</li>'
                    . '<li><strong>Firebase Cloud Messaging</strong> — ya integrado; mantener.</li>'
                    . '</ol>'
                    . '<h3>Funcionales</h3>'
                    . '<ol>'
                    . '<li>KYC/AML: validación de identidad (INE, CURP, RFC), listas negras, scoring de riesgo.</li>'
                    . '<li>Firma electrónica vinculante en contratos (Adhesión, Invitación, LPOA).</li>'
                    . '<li>Email transaccional para eventos críticos (5 tipos).</li>'
                    . '<li>Scheduler con tareas programadas (limpieza tokens, recordatorios, reportes).</li>'
                    . '<li>Workflow de aprobación multi-nivel para solicitudes de retiro.</li>'
                    . '<li>Dashboard operativo con KPIs y gráficos.</li>'
                    . '<li>CRM/Prospectos con pipeline comercial básico.</li>'
                    . '<li>Importación de movimientos masivos con validación.</li>'
                    . '<li>5 roles nuevos: Comercial, Finanzas, MesaNegocios, AuxiliarAdministrativo, AnalisisProduccion.</li>'
                    . '</ol>',
                'technical_notes' => '<h2>Notas Técnicas</h2>'
                    . '<h3>Arquitectura encontrada</h3>'
                    . '<ul>'
                    . '<li><strong>Monolito híbrido</strong> con capas: Controllers → Services → Models (Eloquent) → DB.</li>'
                    . '<li>Dos aproximaciones coexistentes: controladores con lógica directa vs controladores con Service Layer.</li>'
                    . '<li><strong>Sin Repository Pattern:</strong> Services acceden directamente a Eloquent.</li>'
                    . '<li><strong>Sin Event-Driven Architecture:</strong> solo existe evento Registered de Laravel y MovimientoObserver.</li>'
                    . '<li><strong>Autorización:</strong> Spatie Permission con middleware custom PermissionCheck. Sin Policies de Laravel.</li>'
                    . '<li><strong>DTOs:</strong> spatie/laravel-data con dos carpetas inconsistentes (DTO y DTOS).</li>'
                    . '<li><strong>Autenticación dual:</strong> dos modelos Authenticatable (User para admin, Inversionista para cliente) con Sanctum.</li>'
                    . '</ul>'
                    . '<h3>Integraciones</h3>'
                    . '<ul>'
                    . '<li><strong>OpenPay</strong> (SDK openpay/sdk ^3.1): cargos y webhooks.</li>'
                    . '<li><strong>Firebase Cloud Messaging</strong> (HTTP v1 API): push notifications.</li>'
                    . '<li><strong>PayPal</strong> (manual): solo registro, sin SDK.</li>'
                    . '<li><strong>Resend</strong>: configurado pero sin uso extensivo.</li>'
                    . '</ul>'
                    . '<h3>Decisiones técnicas importantes</h3>'
                    . '<ol>'
                    . '<li><strong>Spatie Permission vs Policies:</strong> middleware PermissionCheck permite validación dinámica sin definir Policies por entidad. Flexible pero menos granular.</li>'
                    . '<li><strong>Sanctum vs Passport:</strong> para SPA + app móvil, Sanctum es más ligero y suficiente. No se requiere OAuth2 completo.</li>'
                    . '<li><strong>Dos modelos Authenticatable:</strong> separación completa de sesiones admin y clientes para evitar mezclar roles en una sola tabla.</li>'
                    . '<li><strong>Redis para queues:</strong> mejor rendimiento y ya está configurado con Horizon.</li>'
                    . '<li><strong>DomPDF para PDFs:</strong> maduro, soporta CSS/HTML, se integra bien con Blade.</li>'
                    . '</ol>'
                    . '<h3>Deuda técnica observable</h3>'
                    . '<ul>'
                    . '<li>Arquitectura híbrida inconsistente (dos estilos de controladores).</li>'
                    . '<li>15+ modelos huérfanos sin uso aparente.</li>'
                    . '<li>Modelos duplicados: DatoInversionista/DatosInversionistas, WebPrecioBG/WebPreciosBG.</li>'
                    . '<li>Carpetas DTO/DTOS con nomenclatura inconsistente.</li>'
                    . '<li>Relación incorrecta: Aportacion.inversionista() usa hasOne en lugar de belongsTo.</li>'
                    . '<li>Scheduler vacío sin tareas programadas.</li>'
                    . '<li>Código comentado en PermissionsCheck.</li>'
                    . '<li>Archivo faltante: ConsolidarRetiroRequest.</li>'
                    . '<li>Naming inconsistente de tablas: edc_x_mes, accesos_CRM, CETS.</li>'
                    . '<li>Sin API Versioning formal.</li>'
                    . '</ul>',
            ]
        );

        $this->command->info('✓ Proyecto crm-back Refactorización creado correctamente.');
        $this->command->info('  CRM-REFACT-001 | crm-back — Refactorización Integral (activo 5%)');
        $this->command->info('  7 fases, 7 objetivos, 8 hitos, 12 entregables, 14 riesgos, 10 tareas, 3 bloqueadores, 4 tickets');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function assignMembers(Project $project, array $members): void
    {
        foreach ($members as [$userId, $role]) {
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $userId],
                ['role' => $role]
            );
        }
    }

    private function createObjectives(Project $project, array $objectives): void
    {
        foreach ($objectives as [$phaseId, $type, $title, $description, $completed]) {
            Objective::firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                ['phase_id' => $phaseId, 'type' => $type, 'description' => $description, 'completed' => $completed]
            );
        }
    }

    private function createMilestones(Project $project, array $milestones): void
    {
        foreach ($milestones as [$title, $targetDate, $completed]) {
            Milestone::firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                ['target_date' => $targetDate, 'completed' => $completed]
            );
        }
    }

    private function createDeliverables(Project $project, array $deliverables): void
    {
        foreach ($deliverables as [$phaseId, $name, $description, $deliveryDate, $approved]) {
            Deliverable::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                ['phase_id' => $phaseId, 'description' => $description, 'approved' => $approved, 'delivery_date' => $deliveryDate]
            );
        }
    }

    private function createTask(Project $project, ProjectPhase $phase, User $assignee, User $creator, array $data): Task
    {
        return Task::firstOrCreate(
            ['project_id' => $project->id, 'title' => $data['title']],
            array_merge($data, [
                'phase_id'    => $phase->id,
                'assigned_to' => $assignee->id,
                'created_by'  => $creator->id,
            ])
        );
    }

    private function addComment(Task $task, User $user, string $comment): void
    {
        TaskComment::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id, 'comment' => $comment]
        );
    }

    private function addTimeLog(Task $task, User $user, int $minutes, string $description): void
    {
        TaskTimeLog::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id, 'description' => $description],
            ['minutes' => $minutes]
        );
    }

    private function createTickets(Project $project, array $tickets): void
    {
        foreach ($tickets as [$createdBy, $assignedTo, $subject, $description, $status, $priority]) {
            Ticket::firstOrCreate(
                ['project_id' => $project->id, 'subject' => $subject],
                [
                    'created_by'  => $createdBy,
                    'assigned_to' => $assignedTo,
                    'description' => $description,
                    'status'      => $status,
                    'priority'    => $priority,
                ]
            );
        }
    }

    private function createRisks(Project $project, array $risks): void
    {
        foreach ($risks as [$title, $description, $impact, $probability, $status, $mitigation]) {
            Risk::firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'description'     => $description,
                    'impact'          => $impact,
                    'probability'     => $probability,
                    'status'          => $status,
                    'mitigation_plan' => $mitigation,
                ]
            );
        }
    }

    private function createBlockers(Project $project, array $blockers): void
    {
        foreach ($blockers as [$taskId, $createdBy, $title, $description, $severity, $resolved]) {
            Blocker::firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'task_id'     => $taskId,
                    'created_by'  => $createdBy->id,
                    'description' => $description,
                    'severity'    => $severity,
                    'resolved'    => $resolved,
                ]
            );
        }
    }
}
