<?php

namespace Database\Seeders;

use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Objective;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectPhase;
use App\Models\Risk;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskTimeLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuarios ────────────────────────────────────────────────────────
        $admin   = User::where('email', 'superadmin@test.com')->firstOrFail();
        $pm      = User::where('email', 'pm@test.com')->firstOrFail();
        $dev     = User::where('email', 'dev@test.com')->firstOrFail();
        $qa      = User::where('email', 'qa@test.com')->firstOrFail();
        $support = User::where('email', 'support@test.com')->firstOrFail();
        $client  = User::where('email', 'client@test.com')->firstOrFail();

        // ════════════════════════════════════════════════════════════════════
        //  PROYECTO 1 — E-commerce Platform  (activo, ~60%)
        // ════════════════════════════════════════════════════════════════════
        $p1 = Project::firstOrCreate(
            ['code' => 'ECOM-001'],
            [
                'name'        => 'E-commerce Platform',
                'description' => 'Plataforma de comercio electrónico con carrito, pagos y panel de vendedor.',
                'status'      => 'active',
                'start_date'  => '2026-01-15',
                'end_date'    => '2026-07-31',
                'budget'      => 85000.00,
                'progress'    => 60,
                'owner_id'    => $pm->id,
            ]
        );

        // Miembros
        $this->assignMembers($p1, [
            [$pm->id,      'manager'],
            [$dev->id,     'developer'],
            [$qa->id,      'qa'],
            [$support->id, 'analyst'],
            [$client->id,  'client'],
        ]);

        // Fases
        $p1Phase1 = ProjectPhase::firstOrCreate(
            ['project_id' => $p1->id, 'name' => 'Análisis y Diseño'],
            ['start_date' => '2026-01-15', 'end_date' => '2026-02-15', 'progress' => 100]
        );
        $p1Phase2 = ProjectPhase::firstOrCreate(
            ['project_id' => $p1->id, 'name' => 'Desarrollo Backend'],
            ['start_date' => '2026-02-16', 'end_date' => '2026-04-30', 'progress' => 85]
        );
        $p1Phase3 = ProjectPhase::firstOrCreate(
            ['project_id' => $p1->id, 'name' => 'Desarrollo Frontend'],
            ['start_date' => '2026-03-01', 'end_date' => '2026-05-31', 'progress' => 55]
        );
        $p1Phase4 = ProjectPhase::firstOrCreate(
            ['project_id' => $p1->id, 'name' => 'QA y Pruebas'],
            ['start_date' => '2026-06-01', 'end_date' => '2026-07-15', 'progress' => 10]
        );

        // Objetivos
        $this->createObjectives($p1, [
            ['general',  'Lanzar la plataforma antes del Q3 2026',                          'Fecha límite: 31 julio 2026.',                   true],
            ['specific', 'Implementar pasarela de pagos con Stripe',                         'Integrar Stripe Checkout y webhooks.',            false],
            ['specific', 'Soportar al menos 500 usuarios concurrentes',                      'Pruebas de carga con k6.',                       false],
            ['specific', 'Panel de vendedor con métricas de ventas en tiempo real',          'Dashboard con Chart.js.',                        false],
        ]);

        // Hitos
        $this->createMilestones($p1, [
            ['Diseño UX aprobado',              '2026-02-15', true],
            ['API REST completada',             '2026-04-30', true],
            ['Integración de pagos lista',      '2026-05-20', false],
            ['Beta interna',                    '2026-06-15', false],
            ['Lanzamiento producción',          '2026-07-31', false],
        ]);

        // Entregables
        $this->createDeliverables($p1, [
            ['Documento de Requerimientos (SRS)', 'Especificación funcional completa.',      '2026-02-10', true],
            ['Prototipos Figma',                  'Wireframes y UI del portal y admin.',     '2026-02-15', true],
            ['API de Catálogo',                   'CRUD de productos con búsqueda.',         '2026-04-01', true],
            ['Módulo de Carrito',                 'Carrito con sesión y persistencia.',      '2026-04-30', false],
            ['Módulo de Pagos',                   'Checkout con Stripe.',                    '2026-05-20', false],
            ['Portal del Vendedor',               'Dashboard y estadísticas.',               '2026-06-30', false],
        ]);

        // Tareas
        $t1_1 = $this->createTask($p1, $p1Phase2, $dev, $pm, [
            'title'           => 'Diseñar esquema de base de datos',
            'description'     => 'Modelar tablas de productos, pedidos, usuarios y pagos.',
            'priority'        => 'high',
            'status'          => 'done',
            'due_date'        => '2026-02-20',
            'estimated_hours' => 16,
            'worked_hours'    => 14,
            'progress'        => 100,
        ]);
        $this->addComment($t1_1, $pm, 'Esquema revisado y aprobado. Buen trabajo.');
        $this->addTimeLog($t1_1, $dev, 480, 'Diseño inicial de tablas y relaciones.');
        $this->addTimeLog($t1_1, $dev, 360, 'Ajustes tras revisión con PM.');

        $t1_2 = $this->createTask($p1, $p1Phase2, $dev, $pm, [
            'title'           => 'Implementar autenticación JWT',
            'description'     => 'Login, registro, refresh token y middleware de auth.',
            'priority'        => 'critical',
            'status'          => 'done',
            'due_date'        => '2026-03-05',
            'estimated_hours' => 20,
            'worked_hours'    => 18,
            'progress'        => 100,
        ]);
        $this->addComment($t1_2, $dev, 'Tokens implementados con expiración de 1h y refresh de 7d.');
        $this->addTimeLog($t1_2, $dev, 600, 'Implementación completa de JWT.');

        $t1_3 = $this->createTask($p1, $p1Phase2, $dev, $pm, [
            'title'           => 'API CRUD de productos',
            'description'     => 'Endpoints para listar, crear, actualizar y eliminar productos con imágenes.',
            'priority'        => 'high',
            'status'          => 'in_progress',
            'due_date'        => '2026-04-10',
            'estimated_hours' => 24,
            'worked_hours'    => 16,
            'progress'        => 65,
        ]);
        $this->addComment($t1_3, $qa, 'Faltan los endpoints de búsqueda filtrada por categoría.');
        $this->addComment($t1_3, $dev, 'Añadiré filtros en el próximo commit.');
        $this->addTimeLog($t1_3, $dev, 960, 'CRUD básico implementado.');

        $t1_4 = $this->createTask($p1, $p1Phase3, $dev, $pm, [
            'title'           => 'Componente de carrito de compras',
            'description'     => 'Carrito con gestión de cantidades, eliminación y cálculo de totales.',
            'priority'        => 'high',
            'status'          => 'in_progress',
            'due_date'        => '2026-05-01',
            'estimated_hours' => 20,
            'worked_hours'    => 8,
            'progress'        => 40,
        ]);
        $this->addComment($t1_4, $dev, 'Lógica de cantidades lista, falta el resumen de pedido.');

        $t1_5 = $this->createTask($p1, $p1Phase3, $dev, $pm, [
            'title'           => 'Integración Stripe Checkout',
            'description'     => 'Conectar el frontend con el endpoint de pago y manejar webhooks.',
            'priority'        => 'critical',
            'status'          => 'blocked',
            'due_date'        => '2026-05-20',
            'estimated_hours' => 16,
            'worked_hours'    => 3,
            'progress'        => 15,
        ]);
        $this->addComment($t1_5, $dev, 'Bloqueado: necesito las credenciales de Stripe del cliente.');

        $t1_6 = $this->createTask($p1, $p1Phase4, $qa, $pm, [
            'title'           => 'Plan de pruebas E2E',
            'description'     => 'Definir casos de prueba para flujos de compra, pago y devolución.',
            'priority'        => 'medium',
            'status'          => 'pending',
            'due_date'        => '2026-06-05',
            'estimated_hours' => 12,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        // Tickets
        $this->createTickets($p1, [
            [$client->id,  $support->id, 'No puedo ver mis pedidos anteriores',          'El historial de pedidos muestra error 500.',                    'open',        'high'],
            [$client->id,  $dev->id,     'Las imágenes del producto no cargan en móvil', 'En iOS Safari las imágenes aparecen en blanco.',                'in_progress',  'medium'],
            [$qa->id,      $dev->id,     'El precio con descuento no se calcula bien',   'Con cupón del 20% el precio final es incorrecto.',              'in_progress',  'high'],
            [$client->id,  null,         'Agregar filtro de precio en el catálogo',      'Necesitamos filtrar productos por rango de precio.',            'open',        'low'],
            [$support->id, $dev->id,     'Error al subir imágenes mayores a 2MB',        'El endpoint devuelve 422 para imágenes de 3MB.',               'resolved',    'medium'],
        ]);

        // Riesgos
        $this->createRisks($p1, [
            ['Retraso en integración de pagos',     'El proveedor de pagos puede tardar en aprobar la cuenta mercantil.',      'high',   'medium', 'active',    'Iniciar proceso de verificación con 4 semanas de anticipación.'],
            ['Bajo rendimiento en carga alta',      'La plataforma puede no soportar picos de tráfico en temporada alta.',     'high',   'high',   'active',    'Implementar caché con Redis y pruebas de carga en staging.'],
            ['Cambio de requerimientos a mitad',    'El cliente puede solicitar cambios mayores en el módulo de vendedores.',   'medium', 'low',    'mitigated', 'Contrato con cláusula de gestión de cambios firmado.'],
        ]);

        // Bloqueadores
        $this->createBlockers($p1, [
            [$t1_5->id, $dev, 'Credenciales Stripe no entregadas',    'El cliente no ha proporcionado las API keys de Stripe producción.',  'critical', false],
            [null,      $pm,  'Servidor de staging sin configurar',   'DevOps no ha desplegado el entorno de staging.',                     'high',     false],
            [$t1_3->id, $dev, 'Librería de búsqueda con bug conocido','Elasticsearch versión actual tiene un bug en filtros anidados.',      'medium',   true],
        ]);


        // ════════════════════════════════════════════════════════════════════
        //  PROYECTO 2 — App Móvil de Delivery  (en planificación, ~25%)
        // ════════════════════════════════════════════════════════════════════
        $p2 = Project::firstOrCreate(
            ['code' => 'DELIV-002'],
            [
                'name'        => 'App Móvil de Delivery',
                'description' => 'Aplicación móvil para pedidos de comida a domicilio con tracking en tiempo real.',
                'status'      => 'active',
                'start_date'  => '2026-03-01',
                'end_date'    => '2026-10-30',
                'budget'      => 120000.00,
                'progress'    => 25,
                'owner_id'    => $pm->id,
            ]
        );

        $this->assignMembers($p2, [
            [$pm->id,  'manager'],
            [$dev->id, 'developer'],
            [$qa->id,  'qa'],
        ]);

        // Fases
        $p2Phase1 = ProjectPhase::firstOrCreate(
            ['project_id' => $p2->id, 'name' => 'Discovery y UX'],
            ['start_date' => '2026-03-01', 'end_date' => '2026-04-15', 'progress' => 100]
        );
        $p2Phase2 = ProjectPhase::firstOrCreate(
            ['project_id' => $p2->id, 'name' => 'Sprint 1 — Auth y Perfil'],
            ['start_date' => '2026-04-16', 'end_date' => '2026-05-15', 'progress' => 60]
        );
        $p2Phase3 = ProjectPhase::firstOrCreate(
            ['project_id' => $p2->id, 'name' => 'Sprint 2 — Catálogo y Pedidos'],
            ['start_date' => '2026-05-16', 'end_date' => '2026-06-30', 'progress' => 0]
        );

        // Objetivos
        $this->createObjectives($p2, [
            ['general',  'Lanzar MVP en App Store y Play Store antes de Q4 2026',     'Fecha objetivo: octubre 2026.',               false],
            ['specific', 'Implementar tracking de repartidor en tiempo real con WebSockets', 'Socket.io + Google Maps API.',          false],
            ['specific', 'Soporte para múltiples métodos de pago',                     'Tarjeta, efectivo y billeteras digitales.',   false],
            ['specific', 'Tiempo de carga del menú menor a 2 segundos',                'Optimizar imágenes y usar CDN.',              false],
        ]);

        // Hitos
        $this->createMilestones($p2, [
            ['Prototipos de alta fidelidad aprobados', '2026-04-15', true],
            ['Módulo de autenticación completado',     '2026-05-15', false],
            ['Módulo de pedidos completado',           '2026-06-30', false],
            ['Beta pública disponible',                '2026-09-01', false],
        ]);

        // Entregables
        $this->createDeliverables($p2, [
            ['Estudio de UX y flujos de usuario', 'Journey maps y wireframes.',          '2026-04-01', true],
            ['Diseño UI en Figma',                'Pantallas completas con design system.','2026-04-15', true],
            ['API de autenticación móvil',        'Registro, login y perfil.',           '2026-05-15', false],
            ['Módulo de pedidos',                 'Carrito, checkout y estado.',         '2026-06-30', false],
        ]);

        // Tareas
        $t2_1 = $this->createTask($p2, $p2Phase1, $dev, $pm, [
            'title'           => 'Configurar repositorio y CI/CD',
            'description'     => 'Monorepo con React Native, configurar GitHub Actions para lint, test y build.',
            'priority'        => 'high',
            'status'          => 'done',
            'due_date'        => '2026-03-10',
            'estimated_hours' => 8,
            'worked_hours'    => 10,
            'progress'        => 100,
        ]);
        $this->addComment($t2_1, $dev, 'Pipeline funcionando. Build tarda ~8 min.');
        $this->addTimeLog($t2_1, $dev, 600, 'Configuración completa de CI/CD.');

        $t2_2 = $this->createTask($p2, $p2Phase2, $dev, $pm, [
            'title'           => 'Pantalla de login y registro (React Native)',
            'description'     => 'Formularios con validación, biometría y persistencia de sesión.',
            'priority'        => 'critical',
            'status'          => 'in_progress',
            'due_date'        => '2026-05-05',
            'estimated_hours' => 20,
            'worked_hours'    => 12,
            'progress'        => 60,
        ]);
        $this->addComment($t2_2, $qa, 'Revisar que el logout borre correctamente el token en keychain.');
        $this->addTimeLog($t2_2, $dev, 720, 'Login y registro implementados, falta biometría.');

        $t2_3 = $this->createTask($p2, $p2Phase2, $dev, $pm, [
            'title'           => 'Pantalla de perfil de usuario',
            'description'     => 'Ver y editar nombre, foto, dirección y teléfono.',
            'priority'        => 'medium',
            'status'          => 'pending',
            'due_date'        => '2026-05-12',
            'estimated_hours' => 12,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t2_4 = $this->createTask($p2, $p2Phase3, $dev, $pm, [
            'title'           => 'Listado de restaurantes y catálogo de platos',
            'description'     => 'Fetch paginado, filtros por categoría y búsqueda.',
            'priority'        => 'high',
            'status'          => 'pending',
            'due_date'        => '2026-06-01',
            'estimated_hours' => 24,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        $t2_5 = $this->createTask($p2, $p2Phase3, $qa, $pm, [
            'title'           => 'Pruebas de usabilidad en dispositivos físicos',
            'description'     => 'Testing en iOS 17 y Android 14 con usuarios reales.',
            'priority'        => 'medium',
            'status'          => 'pending',
            'due_date'        => '2026-06-25',
            'estimated_hours' => 16,
            'worked_hours'    => 0,
            'progress'        => 0,
        ]);

        // Tickets
        $this->createTickets($p2, [
            [$dev->id, $pm->id,  'API de Google Maps excede cuota gratuita',    'Con el volumen de requests estimado superamos el free tier.',      'open',       'high'],
            [$qa->id,  $dev->id, 'Crash al rotar pantalla en Android',          'La app se cierra al rotar el dispositivo en la pantalla de menú.', 'in_progress', 'high'],
            [$qa->id,  $dev->id, 'Imágenes de platos se ven pixeladas en iPad', 'Usar imágenes @2x para pantallas retina.',                         'open',       'low'],
        ]);

        // Riesgos
        $this->createRisks($p2, [
            ['Rechazo de App Store por políticas',     'Apple puede rechazar la app por uso de pagos externos.',       'high',   'medium', 'active',    'Revisar guías de Apple y preparar versión sin pagos externos.'],
            ['Dependencia de Google Maps API',         'Costo alto de la API en producción con muchos usuarios.',      'medium', 'high',   'active',    'Evaluar Mapbox como alternativa más económica.'],
            ['Retraso en entregas por scope creep',    'El cliente puede pedir funciones no planificadas.',            'medium', 'medium', 'active',    'Backlog gestionado con priorización estricta en cada sprint.'],
        ]);

        // Bloqueadores
        $this->createBlockers($p2, [
            [$t2_2->id, $dev, 'SDK de biometría incompatible con Expo 51', 'El módulo expo-local-authentication falla en Android 14.',  'high',   false],
            [null,      $pm,  'Falta definir gateway de pagos móvil',      'Pendiente decisión entre Culqi, Niubiz o Mercado Pago.',     'medium', false],
        ]);


        // ════════════════════════════════════════════════════════════════════
        //  PROYECTO 3 — Sistema de RRHH Interno  (completado)
        // ════════════════════════════════════════════════════════════════════
        $p3 = Project::firstOrCreate(
            ['code' => 'RRHH-003'],
            [
                'name'        => 'Sistema de RRHH Interno',
                'description' => 'Portal interno para gestión de empleados, vacaciones, nómina y evaluaciones.',
                'status'      => 'completed',
                'start_date'  => '2025-09-01',
                'end_date'    => '2026-02-28',
                'budget'      => 45000.00,
                'progress'    => 100,
                'owner_id'    => $admin->id,
            ]
        );

        $this->assignMembers($p3, [
            [$pm->id,      'manager'],
            [$dev->id,     'developer'],
            [$qa->id,      'qa'],
            [$client->id,  'client'],
        ]);

        // Fases (todas completadas)
        $p3Phase1 = ProjectPhase::firstOrCreate(
            ['project_id' => $p3->id, 'name' => 'Análisis de procesos RRHH'],
            ['start_date' => '2025-09-01', 'end_date' => '2025-10-01', 'progress' => 100]
        );
        $p3Phase2 = ProjectPhase::firstOrCreate(
            ['project_id' => $p3->id, 'name' => 'Desarrollo del portal'],
            ['start_date' => '2025-10-02', 'end_date' => '2025-12-31', 'progress' => 100]
        );
        $p3Phase3 = ProjectPhase::firstOrCreate(
            ['project_id' => $p3->id, 'name' => 'Pruebas y capacitación'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-02-28', 'progress' => 100]
        );

        // Objetivos (todos completados)
        $this->createObjectives($p3, [
            ['general',  'Digitalizar el proceso de solicitud de vacaciones',           'Eliminar formularios en papel.',                        true],
            ['specific', 'Módulo de nómina con cálculo automático de deducciones',      'Incluir AFP, seguro y bonos.',                          true],
            ['specific', 'Dashboard de evaluación de desempeño trimestral',             'Con indicadores KPI por empleado.',                     true],
            ['specific', 'Capacitar al equipo de RRHH en el uso del sistema',          'Al menos 10 horas de entrenamiento.',                   true],
        ]);

        // Hitos (todos completados)
        $this->createMilestones($p3, [
            ['Mapeo de procesos aprobado',     '2025-10-01', true],
            ['Portal desplegado en staging',   '2025-12-15', true],
            ['Capacitación completada',        '2026-02-15', true],
            ['Go-live en producción',          '2026-02-28', true],
        ]);

        // Entregables (todos aprobados)
        $this->createDeliverables($p3, [
            ['Documento de procesos RRHH',    'Mapeo de procesos AS-IS y TO-BE.',      '2025-10-01', true],
            ['Portal de empleados',            'Portal web con autenticación SSO.',     '2025-12-31', true],
            ['Módulo de nómina',               'Cálculo y exportación de nómina PDF.', '2026-01-31', true],
            ['Manual de usuario',              'Guía completa del sistema.',            '2026-02-20', true],
        ]);

        // Tareas (todas done)
        $t3_1 = $this->createTask($p3, $p3Phase1, $dev, $pm, [
            'title'           => 'Relevamiento de requerimientos con RRHH',
            'description'     => 'Entrevistas con el equipo de RRHH para mapear los procesos actuales.',
            'priority'        => 'high',
            'status'          => 'done',
            'due_date'        => '2025-09-20',
            'estimated_hours' => 10,
            'worked_hours'    => 12,
            'progress'        => 100,
        ]);
        $this->addComment($t3_1, $pm, 'Proceso documentado. 3 entrevistas realizadas.');
        $this->addTimeLog($t3_1, $dev, 720, 'Entrevistas y documentación de procesos.');

        $t3_2 = $this->createTask($p3, $p3Phase2, $dev, $pm, [
            'title'           => 'Módulo de solicitud de vacaciones',
            'description'     => 'Flujo de solicitud, aprobación por jefe y notificación por email.',
            'priority'        => 'critical',
            'status'          => 'done',
            'due_date'        => '2025-11-15',
            'estimated_hours' => 32,
            'worked_hours'    => 30,
            'progress'        => 100,
        ]);
        $this->addComment($t3_2, $qa, 'Probado en todos los flujos. Aprobado.');
        $this->addTimeLog($t3_2, $dev, 1800, 'Desarrollo completo del módulo de vacaciones.');

        $t3_3 = $this->createTask($p3, $p3Phase2, $dev, $pm, [
            'title'           => 'Módulo de nómina',
            'description'     => 'Cálculo de nómina con AFP, seguro de salud y bonos variables.',
            'priority'        => 'critical',
            'status'          => 'done',
            'due_date'        => '2025-12-31',
            'estimated_hours' => 40,
            'worked_hours'    => 45,
            'progress'        => 100,
        ]);
        $this->addComment($t3_3, $pm, 'El cálculo fue validado por el contador. Correcto.');
        $this->addTimeLog($t3_3, $dev, 2700, 'Implementación del motor de nómina.');

        $t3_4 = $this->createTask($p3, $p3Phase3, $qa, $pm, [
            'title'           => 'Capacitación del equipo RRHH',
            'description'     => 'Sesiones de entrenamiento presenciales y video tutoriales.',
            'priority'        => 'medium',
            'status'          => 'done',
            'due_date'        => '2026-02-15',
            'estimated_hours' => 16,
            'worked_hours'    => 14,
            'progress'        => 100,
        ]);
        $this->addComment($t3_4, $qa, '12 personas capacitadas. Encuesta de satisfacción: 4.5/5.');

        // Tickets (algunos resueltos, uno aún abierto)
        $this->createTickets($p3, [
            [$client->id,  $dev->id,  'Error en cálculo de vacaciones proporcionales', 'Para empleados contratados a mitad de año el cálculo es incorrecto.', 'resolved', 'high'],
            [$client->id,  $support->id, 'No puedo descargar el recibo de nómina en PDF', 'El botón de descarga no funciona en Internet Explorer 11.',          'closed',   'medium'],
            [$qa->id,      $dev->id,  'El módulo de evaluación no guarda comentarios',  'Al guardar una evaluación se pierde el campo de observaciones.',       'resolved', 'medium'],
            [$client->id,  null,      'Agregar exportación a Excel del reporte de nómina', 'El cliente solicita exportar la nómina en formato .xlsx.',          'open',     'low'],
        ]);

        // Riesgos (todos mitigados o resueltos)
        $this->createRisks($p3, [
            ['Resistencia al cambio del equipo RRHH',  'Usuarios pueden no adoptar el sistema.',      'medium', 'medium', 'resolved',  'Plan de cambio con capacitación y soporte presencial durante 1 mes.'],
            ['Inconsistencia de datos en migración',   'Los datos históricos de nómina pueden tener errores al migrar.', 'high', 'low', 'mitigated', 'Proceso de validación con double-check entre sistema legacy y nuevo.'],
        ]);

        // Sin bloqueadores activos (proyecto completado)
        $this->createBlockers($p3, [
            [null, $pm, 'Acceso a BD legado denegado inicialmente', 'El equipo de IT tardó 2 semanas en dar acceso a la BD de RRHH antigua.', 'high', true],
        ]);

        $this->command->info('✓ 3 proyectos de demostración creados correctamente.');
        $this->command->info('  ECOM-001 | E-commerce Platform       (activo  60%)');
        $this->command->info('  DELIV-002 | App Móvil de Delivery     (activo  25%)');
        $this->command->info('  RRHH-003  | Sistema de RRHH Interno  (completado 100%)');
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
        foreach ($objectives as [$type, $title, $description, $completed]) {
            Objective::firstOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                compact('type', 'description', 'completed')
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
        foreach ($deliverables as [$name, $description, $deliveryDate, $approved]) {
            Deliverable::firstOrCreate(
                ['project_id' => $project->id, 'name' => $name],
                compact('description', 'approved') + ['delivery_date' => $deliveryDate]
            );
        }
    }

    private function createTask(Project $project, ProjectPhase $phase, User $assignee, User $creator, array $data): Task
    {
        return Task::firstOrCreate(
            ['project_id' => $project->id, 'title' => $data['title']],
            array_merge($data, [
                'phase_id'   => $phase->id,
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
                    'description'      => $description,
                    'impact'           => $impact,
                    'probability'      => $probability,
                    'status'           => $status,
                    'mitigation_plan'  => $mitigation,
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
