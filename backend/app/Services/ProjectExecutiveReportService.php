<?php

namespace App\Services;

use App\DTOs\ReportPeriodDTO;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class ProjectExecutiveReportService
{
    // ── Brand colours ────────────────────────────────────────────────────────

    private const C_DARK    = '1A365D'; // cover bg
    private const C_PRIMARY = '2C5282'; // section headings
    private const C_ACCENT  = '2B6CB0'; // table headers
    private const C_LIGHT   = 'EBF8FF'; // alternating row
    private const C_WHITE   = 'FFFFFF';
    private const C_TEXT    = '2D3748';
    private const C_MUTED   = '718096';
    private const C_SUCCESS = '276749';
    private const C_ERROR   = '9B2335';
    private const C_WARN    = '975A16';

    // ── Public API ───────────────────────────────────────────────────────────

    /** Generate DOCX and return temporary file path. */
    public function generate(Project $project, ReportPeriodDTO $period): string
    {
        $data     = $this->loadData($project, $period);
        $phpWord  = $this->buildDocument($project, $data, $period);

        $tempFile = tempnam(sys_get_temp_dir(), 'exec_report_') . '.docx';
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }

    // ── Data loading ─────────────────────────────────────────────────────────

    private function loadData(Project $project, ReportPeriodDTO $period): array
    {
        // Eager-load all relationships at once to avoid N+1
        $project->load([
            'owner',
            'members.user',
            'tasks.assignee',
            'tickets.assignee',
            'tickets.creator',
            'blockers.task',
            'blockers.createdBy',
            'risks',
            'milestones',
            'deliverables',
            'objectives',
        ]);

        $allTasks    = $project->tasks;
        $allTickets  = $project->tickets;
        $allBlockers = $project->blockers;

        // Period-filtered subsets (applied to created_at of each collection)
        $tasks    = $this->filterByPeriod($allTasks,    $period);
        $tickets  = $this->filterByPeriod($allTickets,  $period);
        $blockers = $this->filterByPeriod($allBlockers, $period);

        // Task KPIs
        $totalTasks     = $tasks->count();
        $doneTasks      = $tasks->where('status', 'done')->count();
        $pendingTasks   = $tasks->where('status', 'pending')->count();
        $inProgTasks    = $tasks->where('status', 'in_progress')->count();
        $reviewTasks    = $tasks->where('status', 'review')->count();
        $overdueTasks   = $tasks->filter(
            fn($t) =>
            $t->due_date && $t->due_date->isPast() && $t->status !== 'done'
        )->count();
        $completionRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 1) : 0;

        // Ticket KPIs
        $totalTickets    = $tickets->count();
        $openTickets     = $tickets->whereIn('status', ['open', 'in_progress'])->count();
        $closedTickets   = $tickets->whereIn('status', ['resolved', 'closed'])->count();
        $avgResolutionHrs = $this->avgResolutionHours($tickets);

        // Blocker KPIs
        $openBlockers     = $blockers->where('resolved', false)->count();
        $criticalBlockers = $blockers->where('resolved', false)->where('severity', 'critical')->count();
        $resolvedBlockers = $blockers->where('resolved', true)->count();

        // Team metrics per member
        $members = $project->members->map(function ($member) use ($allTasks) {
            $memberTasks     = $allTasks->where('assigned_to', $member->user_id);
            $memberDone      = $memberTasks->where('status', 'done')->count();
            $memberAssigned  = $memberTasks->count();
            $productivity    = $memberAssigned > 0 ? round($memberDone / $memberAssigned * 100) : 0;

            return [
                'name'        => $member->user?->name ?? 'N/A',
                'role'        => $member->role,
                'assigned'    => $memberAssigned,
                'completed'   => $memberDone,
                'open'        => $memberAssigned - $memberDone,
                'productivity' => $productivity,
            ];
        });

        // Schedule compliance
        $scheduleCompliance = $totalTasks > 0
            ? round(($totalTasks - $overdueTasks) / $totalTasks * 100, 1)
            : 100;

        // Team productivity (avg)
        $teamProductivity = $members->avg('productivity') ?? 0;

        return compact(
            'tasks',
            'tickets',
            'blockers',
            'totalTasks',
            'doneTasks',
            'pendingTasks',
            'inProgTasks',
            'reviewTasks',
            'overdueTasks',
            'completionRate',
            'totalTickets',
            'openTickets',
            'closedTickets',
            'avgResolutionHrs',
            'openBlockers',
            'criticalBlockers',
            'resolvedBlockers',
            'members',
            'scheduleCompliance',
            'teamProductivity',
        );
    }

    private function filterByPeriod(Collection $items, ReportPeriodDTO $period): Collection
    {
        return $items->when(
            $period->from,
            fn($c) => $c->filter(fn($i) => $i->created_at >= $period->from)
        )->when(
            $period->to,
            fn($c) => $c->filter(fn($i) => $i->created_at <= $period->to)
        );
    }

    private function avgResolutionHours(Collection $tickets): float
    {
        $closed = $tickets->whereIn('status', ['resolved', 'closed']);
        if ($closed->isEmpty()) {
            return 0;
        }

        $totalHours = $closed->sum(fn($t) => $t->created_at->diffInHours($t->updated_at));

        return round($totalHours / $closed->count(), 1);
    }

    // ── Document assembly ────────────────────────────────────────────────────

    private function buildDocument(Project $project, array $data, ReportPeriodDTO $period): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->getSettings()->setUpdateFields(true);

        // Named font styles
        $phpWord->addFontStyle('h1',        ['size' => 28, 'bold' => true,  'color' => self::C_WHITE]);
        $phpWord->addFontStyle('h1Dark',    ['size' => 26, 'bold' => true,  'color' => self::C_DARK]);
        $phpWord->addFontStyle('h2',        ['size' => 16, 'bold' => true,  'color' => self::C_PRIMARY]);
        $phpWord->addFontStyle('h3',        ['size' => 12, 'bold' => true,  'color' => self::C_ACCENT]);
        $phpWord->addFontStyle('normal',    ['size' => 10, 'color' => self::C_TEXT]);
        $phpWord->addFontStyle('small',     ['size' => 9,  'color' => self::C_MUTED]);
        $phpWord->addFontStyle('bold',      ['size' => 10, 'bold' => true,  'color' => self::C_TEXT]);
        $phpWord->addFontStyle('tblHdr',    ['size' => 9,  'bold' => true,  'color' => self::C_WHITE]);
        $phpWord->addFontStyle('tblCell',   ['size' => 9,  'color' => self::C_TEXT]);
        $phpWord->addFontStyle('success',   ['size' => 9,  'bold' => true,  'color' => self::C_SUCCESS]);
        $phpWord->addFontStyle('danger',    ['size' => 9,  'bold' => true,  'color' => self::C_ERROR]);
        $phpWord->addFontStyle('warn',      ['size' => 9,  'bold' => true,  'color' => self::C_WARN]);

        // Heading title styles
        $phpWord->addTitleStyle(1, ['size' => 18, 'bold' => true, 'color' => self::C_PRIMARY]);
        $phpWord->addTitleStyle(2, ['size' => 14, 'bold' => true, 'color' => self::C_ACCENT]);

        // Paragraph styles
        $phpWord->addParagraphStyle('pCenter',   ['alignment' => Jc::CENTER]);
        $phpWord->addParagraphStyle('pSpacing',  ['spaceAfter' => 200]);
        $phpWord->addParagraphStyle('pIndent',   ['indentation' => ['left' => 360]]);

        $section = $phpWord->addSection([
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
            'marginTop'    => 1440,
            'marginBottom' => 1440,
        ]);

        $this->addCoverPage($section, $project, $period, $data);
        $section->addPageBreak();

        $this->addExecutiveSummary($section, $project, $data);
        $section->addPageBreak();

        $this->addProjectOverview($section, $project);
        $this->addKpiSection($section, $data);
        $section->addPageBreak();

        $this->addTeamSection($section, $data);
        $this->addMilestonesSection($section, $project);
        $section->addPageBreak();

        $this->addRisksSection($section, $project);
        $this->addBlockersSection($section, $data);
        $section->addPageBreak();

        $this->addTicketsSection($section, $data);
        $section->addPageBreak();

        $this->addConclusionsSection($section, $project, $data);

        return $phpWord;
    }

    // ── Cover Page ───────────────────────────────────────────────────────────

    private function addCoverPage(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
        ReportPeriodDTO $period,
        array $data,
    ): void {
        // Top spacer
        for ($i = 0; $i < 8; $i++) {
            $section->addTextBreak();
        }

        $section->addText(
            'REPORTE EJECUTIVO DE PROYECTO',
            'h1Dark',
            'pCenter'
        );

        $section->addTextBreak();

        $section->addText($project->name, ['size' => 22, 'bold' => true, 'color' => self::C_PRIMARY], 'pCenter');
        $section->addTextBreak();
        $section->addText(
            'Código: ' . ($project->code ?? 'N/A'),
            ['size' => 12, 'color' => self::C_MUTED],
            'pCenter'
        );

        $section->addTextBreak(3);

        // Meta info table
        $tblStyle = [
            'borderSize'  => 0,
            'cellMargin'  => 100,
            'alignment'   => JcTable::CENTER,
            'width'       => 5000,
            'unit'        => TblWidth::TWIP,
        ];
        $table = $section->addTable($tblStyle);

        $this->addCoverRow($table, 'Estado',     $project->status->value ?? (string) $project->status);
        $this->addCoverRow($table, 'Propietario', $project->owner?->name ?? 'N/A');
        $this->addCoverRow($table, 'Progreso',    $project->progress . '%');
        $this->addCoverRow($table, 'Período',     $period->label);
        $this->addCoverRow($table, 'Generado',    now()->format('d/m/Y H:i'));

        $section->addTextBreak(4);

        $section->addText(
            'Documento confidencial — Solo para uso interno',
            ['size' => 8, 'italic' => true, 'color' => self::C_MUTED],
            'pCenter'
        );
    }

    private function addCoverRow(\PhpOffice\PhpWord\Element\Table $table, string $label, string $value): void
    {
        $table->addRow(360);
        $table->addCell(2400, ['bgColor' => '2C5282'])->addText(
            $label,
            ['size' => 10, 'bold' => true, 'color' => self::C_WHITE],
            ['alignment' => Jc::RIGHT]
        );
        $table->addCell(2800)->addText($value, 'normal', 'pSpacing');
    }

    // ── Executive Summary ────────────────────────────────────────────────────

    private function addExecutiveSummary(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
        array $data,
    ): void {
        $this->addSectionTitle($section, 'Resumen Ejecutivo');

        $rate    = $data['completionRate'];
        $health  = $this->healthLabel($data);
        $concern = $this->primaryConcern($data);

        $section->addText(
            "El proyecto \"{$project->name}\" presenta una salud general {$health}. "
                . "La tasa de completitud de tareas en el período seleccionado es del {$rate}%.",
            'normal',
            'pSpacing'
        );

        // Key achievements
        $this->addSubTitle($section, 'Logros Principales');
        $completed   = $project->milestones->where('completed', true)->count();
        $deliverables = $project->deliverables->where('approved', true)->count();
        $section->addText("• {$data['doneTasks']} tareas completadas.", 'normal');
        $section->addText("• {$completed} hitos alcanzados.", 'normal');
        $section->addText("• {$deliverables} entregables aprobados.", 'normal');
        $section->addText("• {$data['closedTickets']} tickets cerrados.", 'normal');
        $section->addTextBreak();

        // Major concerns
        $this->addSubTitle($section, 'Puntos de Atención');
        if ($data['overdueTasks'] > 0) {
            $section->addText("⚠ {$data['overdueTasks']} tareas vencidas sin completar.", 'warn');
        }
        if ($data['criticalBlockers'] > 0) {
            $section->addText("🚨 {$data['criticalBlockers']} bloqueadores críticos activos.", 'danger');
        }
        if ($data['openBlockers'] > 0) {
            $section->addText("• {$data['openBlockers']} bloqueadores sin resolver.", 'normal');
        }
        if ($data['openTickets'] > 0) {
            $section->addText("• {$data['openTickets']} tickets abiertos pendientes.", 'normal');
        }
        if ($data['overdueTasks'] === 0 && $data['criticalBlockers'] === 0 && $data['openBlockers'] === 0) {
            $section->addText('✓ No se identificaron puntos críticos de atención.', 'success');
        }
    }

    // ── Project Overview ─────────────────────────────────────────────────────

    private function addProjectOverview(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
    ): void {
        $this->addSectionTitle($section, 'Descripción del Proyecto');

        $section->addText($project->description ?? 'Sin descripción.', 'normal', 'pSpacing');

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Campo', 'Valor'], [2400, 5000]);

        $this->addDataRow($tbl, ['Fecha inicio',  $project->start_date?->format('d/m/Y') ?? '—'], 0);
        $this->addDataRow($tbl, ['Fecha fin',     $project->end_date?->format('d/m/Y') ?? '—'], 1);
        $this->addDataRow($tbl, ['Presupuesto',   $project->budget ? '$' . number_format((float) $project->budget, 2) : '—'], 0);
        $this->addDataRow($tbl, ['Progreso',      $project->progress . '%'], 1);
        $section->addTextBreak();

        if ($project->objectives->isNotEmpty()) {
            $this->addSubTitle($section, 'Objetivos');
            foreach ($project->objectives as $obj) {
                $section->addText('• ' . $obj->title, 'normal');
            }
            $section->addTextBreak();
        }
    }

    // ── KPI Section ──────────────────────────────────────────────────────────

    private function addKpiSection(
        \PhpOffice\PhpWord\Element\Section $section,
        array $data,
    ): void {
        $this->addSectionTitle($section, 'Indicadores Clave de Desempeño (KPIs)');

        $kpis = [
            ['Total de Tareas',              $data['totalTasks']],
            ['Tareas Completadas',           $data['doneTasks']],
            ['Tareas Pendientes',            $data['pendingTasks']],
            ['Tareas Vencidas',              $data['overdueTasks']],
            ['Tasa de Completitud',          $data['completionRate'] . '%'],
            ['Total de Tickets',             $data['totalTickets']],
            ['Tickets Abiertos',             $data['openTickets']],
            ['Tickets Cerrados',             $data['closedTickets']],
            ['Tiempo Prom. Resolución (h)',  $data['avgResolutionHrs']],
            ['Bloqueadores Abiertos',        $data['openBlockers']],
            ['Bloqueadores Críticos',        $data['criticalBlockers']],
            ['Productividad del Equipo',     round($data['teamProductivity']) . '%'],
            ['Cumplimiento de Cronograma',   $data['scheduleCompliance'] . '%'],
        ];

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Indicador', 'Valor'], [5500, 1900]);

        foreach ($kpis as $i => [$label, $value]) {
            $this->addDataRow($tbl, [$label, (string) $value], $i % 2);
        }

        $section->addTextBreak();
    }

    // ── Team Section ─────────────────────────────────────────────────────────

    private function addTeamSection(
        \PhpOffice\PhpWord\Element\Section $section,
        array $data,
    ): void {
        $this->addSectionTitle($section, 'Análisis del Equipo');

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Miembro', 'Rol', 'Asignadas', 'Completadas', 'Abiertas', 'Productividad'], [2200, 1500, 1100, 1200, 1100, 1300]);

        foreach ($data['members'] as $i => $m) {
            $this->addDataRow($tbl, [
                $m['name'],
                $m['role'],
                (string) $m['assigned'],
                (string) $m['completed'],
                (string) $m['open'],
                $m['productivity'] . '%',
            ], $i % 2);
        }

        $section->addTextBreak();
    }

    // ── Milestones Section ───────────────────────────────────────────────────

    private function addMilestonesSection(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
    ): void {
        $milestones = $project->milestones;
        if ($milestones->isEmpty()) {
            return;
        }

        $this->addSectionTitle($section, 'Hitos del Proyecto');

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Hito', 'Fecha Objetivo', 'Estado'], [4800, 1800, 1800]);

        foreach ($milestones as $i => $m) {
            $isDelayed = ! $m->completed && $m->target_date && $m->target_date->isPast();
            $status    = $m->completed ? 'Completado' : ($isDelayed ? 'Retrasado' : 'Pendiente');
            $fStyle    = $m->completed ? 'success' : ($isDelayed ? 'danger' : 'tblCell');

            $tbl->addRow(360);
            $bgColor = $i % 2 === 0 ? null : self::C_LIGHT;
            $this->addCell($tbl, $m->title, 4800, $bgColor);
            $this->addCell($tbl, $m->target_date?->format('d/m/Y') ?? '—', 1800, $bgColor);
            $tbl->addCell(1800, $bgColor ? ['bgColor' => $bgColor] : [])->addText($status, $fStyle);
        }

        $section->addTextBreak();
    }

    // ── Risks Section ────────────────────────────────────────────────────────

    private function addRisksSection(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
    ): void {
        $risks = $project->risks;
        if ($risks->isEmpty()) {
            return;
        }

        $this->addSectionTitle($section, 'Análisis de Riesgos');

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Riesgo', 'Impacto', 'Probabilidad', 'Estado'], [3600, 1400, 1500, 1900]);

        foreach ($risks as $i => $r) {
            $this->addDataRow($tbl, [
                $r->title,
                $r->impact->value ?? $r->impact,
                $r->probability->value ?? $r->probability,
                $r->status->value ?? $r->status,
            ], $i % 2);
        }

        $section->addTextBreak();
    }

    // ── Blockers Section ─────────────────────────────────────────────────────

    private function addBlockersSection(
        \PhpOffice\PhpWord\Element\Section $section,
        array $data,
    ): void {
        $blockers = $data['blockers'];
        if ($blockers->isEmpty()) {
            return;
        }

        $this->addSectionTitle($section, 'Análisis de Bloqueadores');

        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Título', 'Severidad', 'Estado', 'Tarea'], [3000, 1400, 1400, 2600]);

        foreach ($blockers as $i => $b) {
            $status  = $b->resolved ? 'Resuelto' : 'Activo';
            $fStyle  = $b->resolved ? 'success' : ($b->severity->value === 'critical' ? 'danger' : 'tblCell');

            $tbl->addRow(360);
            $bgColor = $i % 2 === 0 ? null : self::C_LIGHT;
            $this->addCell($tbl, $b->title, 3000, $bgColor);
            $this->addCell($tbl, $b->severity->label(), 1400, $bgColor);
            $tbl->addCell(1400, $bgColor ? ['bgColor' => $bgColor] : [])->addText($status, $fStyle);
            $this->addCell($tbl, $b->task?->title ?? '—', 2600, $bgColor);
        }

        $section->addTextBreak();
    }

    // ── Tickets Section ──────────────────────────────────────────────────────

    private function addTicketsSection(
        \PhpOffice\PhpWord\Element\Section $section,
        array $data,
    ): void {
        $tickets = $data['tickets'];
        if ($tickets->isEmpty()) {
            return;
        }

        $this->addSectionTitle($section, 'Análisis de Tickets');

        // By status
        $this->addSubTitle($section, 'Tickets por Estado');
        $byStatus = $tickets->groupBy(fn($t) => $t->status->value ?? $t->status);
        $tbl      = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Estado', 'Cantidad'], [4000, 3400]);
        $i = 0;
        foreach ($byStatus as $status => $group) {
            $this->addDataRow($tbl, [$status, (string) $group->count()], $i++ % 2);
        }
        $section->addTextBreak();

        // By priority
        $this->addSubTitle($section, 'Tickets por Prioridad');
        $byPriority = $tickets->groupBy(fn($t) => $t->priority->value ?? $t->priority);
        $tbl2       = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl2, ['Prioridad', 'Cantidad'], [4000, 3400]);
        $j = 0;
        foreach ($byPriority as $priority => $group) {
            $this->addDataRow($tbl2, [$priority, (string) $group->count()], $j++ % 2);
        }
        $section->addTextBreak();
    }

    // ── Conclusions & Recommendations ────────────────────────────────────────

    private function addConclusionsSection(
        \PhpOffice\PhpWord\Element\Section $section,
        Project $project,
        array $data,
    ): void {
        $this->addSectionTitle($section, 'Conclusiones');

        $conclusions = $this->generateConclusions($project, $data);
        foreach ($conclusions as $line) {
            $section->addText($line, 'normal', 'pSpacing');
        }

        $section->addTextBreak();
        $this->addSectionTitle($section, 'Recomendaciones');

        $recommendations = $this->generateRecommendations($data);
        foreach ($recommendations as $line) {
            $section->addText($line, 'normal', 'pSpacing');
        }
    }

    private function generateConclusions(Project $project, array $data): array
    {
        $lines   = [];
        $rate    = $data['completionRate'];
        $health  = $this->healthLabel($data);

        $lines[] = "El proyecto \"{$project->name}\" (código: {$project->code}) se encuentra en estado "
            . "\"{$project->status->value}\" con un progreso general del {$project->progress}%.";

        $lines[] = "Durante el período analizado se completó el {$rate}% de las tareas, "
            . "lo que indica una salud del proyecto {$health}.";

        if ($data['overdueTasks'] > 0) {
            $lines[] = "Se detectaron {$data['overdueTasks']} tareas vencidas que representan un riesgo "
                . "para el cronograma establecido.";
        }

        if ($data['criticalBlockers'] > 0) {
            $lines[] = "Existen {$data['criticalBlockers']} bloqueadores críticos activos que requieren "
                . "atención inmediata para no afectar la entrega del proyecto.";
        }

        if ($data['teamProductivity'] >= 80) {
            $lines[] = "El equipo de trabajo muestra una alta productividad del "
                . round($data['teamProductivity']) . "%, lo que es un factor positivo.";
        }

        return $lines;
    }

    private function generateRecommendations(array $data): array
    {
        $recs = [];

        if ($data['completionRate'] < 50) {
            $recs[] = '1. Revisar la planificación y redistribuir tareas para mejorar la tasa de completitud.';
        }

        if ($data['overdueTasks'] > 0) {
            $recs[] = ($data['completionRate'] < 50 ? '2' : '1')
                . '. Priorizar y resolver las tareas vencidas para evitar retrasos en cascada.';
        }

        if ($data['criticalBlockers'] > 0) {
            $n = count($recs) + 1;
            $recs[] = "{$n}. Asignar recursos dedicados para resolver los bloqueadores críticos de forma inmediata.";
        }

        if ($data['openTickets'] > 5) {
            $n = count($recs) + 1;
            $recs[] = "{$n}. Establecer un proceso de triaje de tickets para reducir el backlog abierto.";
        }

        if ($data['teamProductivity'] < 70) {
            $n = count($recs) + 1;
            $recs[] = "{$n}. Evaluar la carga de trabajo del equipo y considerar incorporar recursos adicionales.";
        }

        if ($data['scheduleCompliance'] < 80) {
            $n = count($recs) + 1;
            $recs[] = "{$n}. Realizar una revisión del cronograma y ajustar fechas de entrega donde sea necesario.";
        }

        if (empty($recs)) {
            $recs[] = '1. Mantener las buenas prácticas actuales y documentar las lecciones aprendidas del proyecto.';
            $recs[] = '2. Continuar monitoreando los KPIs semanalmente para detectar desviaciones a tiempo.';
        }

        return $recs;
    }

    // ── Layout Helpers ───────────────────────────────────────────────────────

    private function addSectionTitle(\PhpOffice\PhpWord\Element\Section $section, string $title): void
    {
        $section->addTitle($title, 1);
        $section->addTextBreak();
    }

    private function addSubTitle(\PhpOffice\PhpWord\Element\Section $section, string $title): void
    {
        $section->addText($title, 'h3', 'pSpacing');
    }

    private function addHeaderRow(\PhpOffice\PhpWord\Element\Table $tbl, array $headers, array $widths): void
    {
        $tbl->addRow(400);
        foreach ($headers as $i => $header) {
            $tbl->addCell($widths[$i], ['bgColor' => self::C_ACCENT])->addText(
                $header,
                'tblHdr',
                ['alignment' => Jc::CENTER]
            );
        }
    }

    private function addDataRow(\PhpOffice\PhpWord\Element\Table $tbl, array $cells, int $stripe): void
    {
        $tbl->addRow(360);
        $bgColor = $stripe === 1 ? self::C_LIGHT : null;
        foreach ($cells as $i => $value) {
            $cellStyle = $bgColor ? ['bgColor' => $bgColor] : [];
            $tbl->addCell(0, $cellStyle)->addText((string) $value, 'tblCell');
        }
    }

    private function addCell(
        \PhpOffice\PhpWord\Element\Table $tbl,
        string $text,
        int $width,
        ?string $bgColor
    ): void {
        $style = $bgColor ? ['bgColor' => $bgColor] : [];
        $tbl->addCell($width, $style)->addText($text, 'tblCell');
    }

    private function defaultTableStyle(): array
    {
        return [
            'borderSize'  => 4,
            'borderColor' => 'CBD5E0',
            'cellMargin'  => 80,
            'width'       => 100,
            'unit'        => TblWidth::PERCENT,
        ];
    }

    // ── Misc helpers ─────────────────────────────────────────────────────────

    private function healthLabel(array $data): string
    {
        $rate = $data['completionRate'];
        $crit = $data['criticalBlockers'];

        if ($crit > 0 || $rate < 30) {
            return 'crítica';
        }
        if ($data['overdueTasks'] > 3 || $rate < 60) {
            return 'regular';
        }
        if ($rate >= 80) {
            return 'excelente';
        }

        return 'buena';
    }

    private function primaryConcern(array $data): string
    {
        if ($data['criticalBlockers'] > 0) {
            return 'bloqueadores críticos activos';
        }
        if ($data['overdueTasks'] > 0) {
            return 'tareas vencidas';
        }

        return 'ninguno relevante';
    }
}
