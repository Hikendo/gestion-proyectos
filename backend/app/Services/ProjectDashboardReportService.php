<?php

namespace App\Services;

use App\DTOs\ReportPeriodDTO;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectDashboardReportService
{
    // ── Brand colours (ARGB) ────────────────────────────────────────────────

    private const BG_HEADER  = 'FF1E3A5F'; // dark navy
    private const BG_ACCENT  = 'FF2B6CB0'; // blue
    private const BG_SUCCESS = 'FF276749'; // green
    private const BG_WARN    = 'FF975A16'; // amber
    private const BG_DANGER  = 'FF9B2335'; // red
    private const BG_LIGHT   = 'FFEDF2F7'; // light grey
    private const BG_WHITE   = 'FFFFFFFF';
    private const FG_WHITE   = 'FFFFFFFF';
    private const FG_DARK    = 'FF2D3748';
    private const FG_MUTED   = 'FF718096';

    // ── Public API ───────────────────────────────────────────────────────────

    /** Generate XLSX and return temporary file path. */
    public function generate(Project $project, ReportPeriodDTO $period): string
    {
        $data        = $this->loadData($project, $period);
        $spreadsheet = $this->buildWorkbook($project, $data, $period);

        $tempFile = tempnam(sys_get_temp_dir(), 'dashboard_report_') . '.xlsx';
        $writer   = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setIncludeCharts(true);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return $tempFile;
    }

    // ── Data loading ─────────────────────────────────────────────────────────

    private function loadData(Project $project, ReportPeriodDTO $period): array
    {
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

        $tasks    = $this->filterByPeriod($allTasks,    $period);
        $tickets  = $this->filterByPeriod($allTickets,  $period);
        $blockers = $this->filterByPeriod($allBlockers, $period);

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

        $totalTickets  = $tickets->count();
        $openTickets   = $tickets->whereIn('status', ['open', 'in_progress'])->count();
        $closedTickets = $tickets->whereIn('status', ['resolved', 'closed'])->count();

        $openBlockers     = $blockers->where('resolved', false)->count();
        $criticalBlockers = $blockers->where('resolved', false)->where('severity', 'critical')->count();

        $teamProductivity  = 0;
        $scheduleCompliance = $totalTasks > 0 ? round(($totalTasks - $overdueTasks) / $totalTasks * 100, 1) : 100;

        $members = $project->members->map(function ($member) use ($allTasks) {
            $memberTasks   = $allTasks->where('assigned_to', $member->user_id);
            $memberDone    = $memberTasks->where('status', 'done')->count();
            $memberTotal   = $memberTasks->count();
            $productivity  = $memberTotal > 0 ? round($memberDone / $memberTotal * 100) : 0;

            return [
                'name'         => $member->user?->name ?? 'N/A',
                'role'         => $member->role,
                'assigned'     => $memberTotal,
                'completed'    => $memberDone,
                'open'         => $memberTotal - $memberDone,
                'productivity' => $productivity,
            ];
        });

        if ($members->isNotEmpty()) {
            $teamProductivity = round($members->avg('productivity'));
        }

        // Status distributions (for charts)
        $taskByStatus    = $tasks->groupBy(fn($t) => $t->status->value ?? $t->status)
            ->map->count()->sortByDesc(fn($v) => $v);
        $ticketByStatus  = $tickets->groupBy(fn($t) => $t->status->value ?? $t->status)
            ->map->count()->sortByDesc(fn($v) => $v);
        $ticketByPriority = $tickets->groupBy(fn($t) => $t->priority->value ?? $t->priority)
            ->map->count()->sortByDesc(fn($v) => $v);
        $blockerBySeverity = $blockers->groupBy(fn($b) => $b->severity->value ?? $b->severity)
            ->map->count()->sortByDesc(fn($v) => $v);

        return compact(
            'tasks',
            'tickets',
            'blockers',
            'members',
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
            'openBlockers',
            'criticalBlockers',
            'teamProductivity',
            'scheduleCompliance',
            'taskByStatus',
            'ticketByStatus',
            'ticketByPriority',
            'blockerBySeverity',
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

    // ── Workbook assembly ────────────────────────────────────────────────────

    private function buildWorkbook(Project $project, array $data, ReportPeriodDTO $period): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Dashboard — ' . $project->name)
            ->setSubject('Reporte de Proyecto')
            ->setDescription('Generado automáticamente por el sistema de gestión de proyectos')
            ->setCreator('Sistema de Gestión');

        // Create all named sheets
        $sheetNames = ['Dashboard', 'Summary', 'Tasks', 'Tickets', 'Blockers', 'Team', 'Metrics', 'Gantt'];
        $spreadsheet->getActiveSheet()->setTitle('Dashboard');

        for ($i = 1; $i < count($sheetNames); $i++) {
            $spreadsheet->createSheet($i)->setTitle($sheetNames[$i]);
        }

        // Build each sheet
        $this->buildMetricsSheet($spreadsheet->getSheetByName('Metrics'), $data);
        $this->buildSummarySheet($spreadsheet->getSheetByName('Summary'), $project, $data, $period);
        $this->buildTasksSheet($spreadsheet->getSheetByName('Tasks'), $data);
        $this->buildTicketsSheet($spreadsheet->getSheetByName('Tickets'), $data);
        $this->buildBlockersSheet($spreadsheet->getSheetByName('Blockers'), $data);
        $this->buildTeamSheet($spreadsheet->getSheetByName('Team'), $data);
        $this->buildGanttSheet($spreadsheet->getSheetByName('Gantt'), $data);
        // Dashboard last (uses references to Metrics)
        $this->buildDashboardSheet($spreadsheet->getSheetByName('Dashboard'), $project, $data, $period);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // ── Dashboard Sheet ──────────────────────────────────────────────────────

    private function buildDashboardSheet(Worksheet $sheet, Project $project, array $data, ReportPeriodDTO $period): void
    {
        $sheet->getTabColor()->setRGB('1E3A5F');

        // Title header
        $this->mergeAndStyle($sheet, 'A1', 'P1', strtoupper('DASHBOARD — ' . $project->name), [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => self::FG_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BG_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        $this->mergeAndStyle($sheet, 'A2', 'P2', 'Período: ' . $period->label . '  |  Generado: ' . now()->format('d/m/Y H:i'), [
            'font' => ['size' => 10, 'color' => ['argb' => self::FG_WHITE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BG_ACCENT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // KPI Cards (row 4-8)
        $kpis = [
            ['A', 'Progreso',           $project->progress . '%',   self::BG_ACCENT],
            ['C', 'Completitud',        $data['completionRate'] . '%', $data['completionRate'] >= 80 ? self::BG_SUCCESS : self::BG_WARN],
            ['E', 'Total Tareas',       $data['totalTasks'],         self::BG_HEADER],
            ['G', 'Completadas',        $data['doneTasks'],          self::BG_SUCCESS],
            ['I', 'Pendientes',         $data['pendingTasks'],       self::BG_WARN],
            ['K', 'Vencidas',           $data['overdueTasks'],       $data['overdueTasks'] > 0 ? self::BG_DANGER : self::BG_SUCCESS],
            ['M', 'Tickets Abiertos',   $data['openTickets'],        $data['openTickets'] > 0 ? self::BG_WARN : self::BG_SUCCESS],
            ['O', 'Bloqueadores',       $data['openBlockers'],       $data['openBlockers'] > 0 ? self::BG_DANGER : self::BG_SUCCESS],
        ];

        foreach ($kpis as [$col, $label, $value, $bg]) {
            $nextCol = chr(ord($col) + 1);
            $this->buildKpiCard($sheet, $col . '4', $nextCol . '8', $label, (string) $value, $bg);
        }

        // Second row of KPI cards (row 10-14)
        $kpis2 = [
            ['A', 'Tickets Cerrados',  $data['closedTickets'],       self::BG_SUCCESS],
            ['C', 'Productividad',     round($data['teamProductivity']) . '%', self::BG_ACCENT],
            ['E', 'Cumpl. Cronograma', $data['scheduleCompliance'] . '%', $data['scheduleCompliance'] >= 80 ? self::BG_SUCCESS : self::BG_WARN],
            ['G', 'En Progreso',       $data['inProgTasks'],         self::BG_ACCENT],
            ['I', 'En Revisión',       $data['reviewTasks'],         self::BG_ACCENT],
            ['K', 'Críticos',          $data['criticalBlockers'],    $data['criticalBlockers'] > 0 ? self::BG_DANGER : self::BG_SUCCESS],
        ];

        foreach ($kpis2 as [$col, $label, $value, $bg]) {
            $nextCol = chr(ord($col) + 1);
            $this->buildKpiCard($sheet, $col . '10', $nextCol . '14', $label, (string) $value, $bg);
        }

        $sheet->getRowDimension(9)->setRowHeight(8);

        // Charts (rows 16+)
        $this->addPieChart(
            $sheet,
            'taskStatusChart',
            'Distribución de Tareas por Estado',
            'Metrics!$A$2:$A$7',
            'Metrics!$B$2:$B$7',
            6,
            'A16',
            'H34'
        );

        $this->addPieChart(
            $sheet,
            'ticketStatusChart',
            'Tickets por Estado',
            'Metrics!$D$2:$D$7',
            'Metrics!$E$2:$E$7',
            6,
            'I16',
            'P34'
        );

        $this->addBarChart(
            $sheet,
            'teamWorkloadChart',
            'Carga de Trabajo del Equipo',
            'Metrics!$G$2:$G$12',
            'Metrics!$H$2:$H$12',
            min($data['members']->count(), 10),
            'A36',
            'H52'
        );

        $this->addBarChart(
            $sheet,
            'blockerSevChart',
            'Bloqueadores por Severidad',
            'Metrics!$J$2:$J$6',
            'Metrics!$K$2:$K$6',
            4,
            'I36',
            'P52'
        );

        // Column widths
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }
    }

    private function buildKpiCard(
        Worksheet $sheet,
        string $topLeft,
        string $bottomRight,
        string $label,
        string $value,
        string $bgArgb
    ): void {
        $labelCell = $topLeft;
        $valueCell = preg_replace('/\d+/', (string) ((int) substr($topLeft, 1) + 2), $bottomRight);
        $valueMid  = $topLeft[0] . (string) ((int) substr($topLeft, 1) + 2);

        $sheet->mergeCells($topLeft . ':' . $bottomRight);
        $sheet->setCellValue($topLeft, $label . "\n" . $value);
        $sheet->getStyle($topLeft . ':' . $bottomRight)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgArgb]],
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => self::FG_WHITE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
    }

    // ── Summary Sheet ────────────────────────────────────────────────────────

    private function buildSummarySheet(Worksheet $sheet, Project $project, array $data, ReportPeriodDTO $period): void
    {
        $sheet->getTabColor()->setRGB('2B6CB0');

        $this->sheetTitle($sheet, 'A1', 'E1', 'Resumen del Proyecto');

        $rows = [
            ['Nombre',          $project->name],
            ['Código',          $project->code ?? 'N/A'],
            ['Estado',          $project->status->value ?? $project->status],
            ['Propietario',     $project->owner?->name ?? 'N/A'],
            ['Fecha Inicio',    $project->start_date?->format('d/m/Y') ?? '—'],
            ['Fecha Fin',       $project->end_date?->format('d/m/Y') ?? '—'],
            ['Presupuesto',     $project->budget ? '$' . number_format((float) $project->budget, 2) : '—'],
            ['Progreso',        $project->progress . '%'],
            ['Período Reporte', $period->label],
            ['Generado',        now()->format('d/m/Y H:i')],
        ];

        $r = 3;
        foreach ($rows as $i => $row) {
            $sheet->setCellValue('A' . $r, $row[0]);
            $sheet->setCellValue('B' . $r, $row[1]);
            $this->styleInfoRow($sheet, 'A' . $r . ':B' . $r, $i % 2 === 0);
            $r++;
        }

        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(40);

        // KPI summary table
        $r += 2;
        $this->headerRow($sheet, 'A' . $r . ':B' . $r, ['KPI', 'Valor']);
        $r++;

        $kpis = [
            ['Total Tareas',           $data['totalTasks']],
            ['Tareas Completadas',     $data['doneTasks']],
            ['Tasa Completitud',       $data['completionRate'] . '%'],
            ['Tareas Vencidas',        $data['overdueTasks']],
            ['Total Tickets',          $data['totalTickets']],
            ['Tickets Abiertos',       $data['openTickets']],
            ['Tickets Cerrados',       $data['closedTickets']],
            ['Bloqueadores Abiertos',  $data['openBlockers']],
            ['Bloqueadores Críticos',  $data['criticalBlockers']],
            ['Productividad Equipo',   round($data['teamProductivity']) . '%'],
            ['Cumpl. Cronograma',      $data['scheduleCompliance'] . '%'],
        ];

        foreach ($kpis as $i => $kpi) {
            $sheet->setCellValue('A' . $r, $kpi[0]);
            $sheet->setCellValue('B' . $r, $kpi[1]);
            $this->styleInfoRow($sheet, 'A' . $r . ':B' . $r, $i % 2 === 0);
            $r++;
        }
    }

    // ── Tasks Sheet ──────────────────────────────────────────────────────────

    private function buildTasksSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('2D3748');

        $headers = ['ID', 'Título', 'Descripción', 'Estado', 'Prioridad', 'Asignado', 'Fecha Inicio', 'Fecha Límite', 'Progreso %', 'Horas Est.', 'Horas Trab.'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $widths = [8, 35, 50, 14, 12, 22, 14, 14, 12, 12, 12];
        $cols   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        $r = 2;
        foreach ($data['tasks'] as $task) {
            $row = [
                $task->id,
                $task->title,
                strip_tags($task->description ?? ''),
                $task->status->value ?? $task->status,
                $task->priority ?? '',
                $task->assignee?->name ?? '—',
                $task->created_at->format('d/m/Y'),
                $task->due_date?->format('d/m/Y') ?? '—',
                $task->progress ?? 0,
                $task->estimated_hours ?? 0,
                $task->worked_hours ?? 0,
            ];

            foreach ($row as $c => $val) {
                $sheet->setCellValue($cols[$c] . $r, $val);
            }

            // Conditional formatting by status
            $bg = match ($task->status->value ?? $task->status) {
                'done'        => 'FFD1FAE5',
                'in_progress' => 'FFDBEAFE',
                'blocked'     => 'FFFEE2E2',
                default       => ($r % 2 === 0 ? 'FFFFFFFF' : 'FFEDF2F7'),
            };
            $sheet->getStyle('A' . $r . ':K' . $r)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($bg);

            $r++;
        }

        $lastRow = $r - 1;
        if ($lastRow > 1) {
            $sheet->setAutoFilter('A1:K1');
            $sheet->freezePane('A2');
            $sheet->getStyle('I2:I' . $lastRow)->getNumberFormat()->setFormatCode('0"%"');
        }

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
    }

    // ── Tickets Sheet ────────────────────────────────────────────────────────

    private function buildTicketsSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('975A16');

        $headers = ['ID', 'Asunto', 'Estado', 'Prioridad', 'Asignado', 'Creado Por', 'Fecha Creación', 'Última Actualización'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $cols   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $widths = [8, 40, 14, 14, 22, 22, 18, 18];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        $r = 2;
        foreach ($data['tickets'] as $ticket) {
            $row = [
                $ticket->id,
                $ticket->subject,
                $ticket->status->value ?? $ticket->status,
                $ticket->priority->value ?? $ticket->priority,
                $ticket->assignee?->name ?? '—',
                $ticket->creator?->name ?? '—',
                $ticket->created_at->format('d/m/Y'),
                $ticket->updated_at->format('d/m/Y'),
            ];

            foreach ($row as $c => $val) {
                $sheet->setCellValue($cols[$c] . $r, $val);
            }

            $bg = $r % 2 === 0 ? 'FFFFFFFF' : 'FFEDF2F7';
            $sheet->getStyle('A' . $r . ':H' . $r)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($bg);

            $r++;
        }

        if ($r > 2) {
            $sheet->setAutoFilter('A1:H1');
            $sheet->freezePane('A2');
        }
    }

    // ── Blockers Sheet ───────────────────────────────────────────────────────

    private function buildBlockersSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('9B2335');

        $headers = ['ID', 'Título', 'Severidad', 'Estado', 'Tarea Asociada', 'Creado Por', 'Fecha Creación', 'Fecha Actualización'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $cols   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $widths = [8, 40, 14, 14, 30, 22, 18, 18];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        $r = 2;
        foreach ($data['blockers'] as $blocker) {
            $row = [
                $blocker->id,
                $blocker->title,
                $blocker->severity->label(),
                $blocker->resolved ? 'Resuelto' : 'Activo',
                $blocker->task?->title ?? '—',
                $blocker->createdBy?->name ?? '—',
                $blocker->created_at->format('d/m/Y'),
                $blocker->updated_at->format('d/m/Y'),
            ];

            foreach ($row as $c => $val) {
                $sheet->setCellValue($cols[$c] . $r, $val);
            }

            $bg = $blocker->resolved ? 'FFD1FAE5' : ($blocker->severity->value === 'critical' ? 'FFFEE2E2' : ($r % 2 === 0 ? 'FFFFFFFF' : 'FFEDF2F7'));
            $sheet->getStyle('A' . $r . ':H' . $r)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($bg);

            $r++;
        }

        if ($r > 2) {
            $sheet->setAutoFilter('A1:H1');
            $sheet->freezePane('A2');
        }
    }

    // ── Team Sheet ───────────────────────────────────────────────────────────

    private function buildTeamSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('276749');

        $headers = ['Miembro', 'Rol', 'Tareas Asignadas', 'Completadas', 'Abiertas', 'Productividad %'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $cols   = ['A', 'B', 'C', 'D', 'E', 'F'];
        $widths = [28, 18, 20, 16, 14, 18];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        $r = 2;
        foreach ($data['members'] as $i => $member) {
            $sheet->setCellValue('A' . $r, $member['name']);
            $sheet->setCellValue('B' . $r, $member['role']);
            $sheet->setCellValue('C' . $r, $member['assigned']);
            $sheet->setCellValue('D' . $r, $member['completed']);
            $sheet->setCellValue('E' . $r, $member['open']);
            $sheet->setCellValue('F' . $r, $member['productivity']);

            $bg = $i % 2 === 0 ? 'FFFFFFFF' : 'FFEDF2F7';
            $sheet->getStyle('A' . $r . ':F' . $r)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($bg);

            $r++;
        }

        if ($r > 2) {
            $sheet->getStyle('F2:F' . ($r - 1))->getNumberFormat()->setFormatCode('0"%"');
        }
    }

    // ── Metrics Sheet (chart data) ───────────────────────────────────────────

    private function buildMetricsSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('4A5568');

        // --- Column A-B: Task by Status ---
        $sheet->setCellValue('A1', 'Estado Tarea');
        $sheet->setCellValue('B1', 'Cantidad');

        $statuses = ['pending', 'in_progress', 'review', 'done', 'blocked', 'cancelled'];
        foreach ($statuses as $i => $s) {
            $sheet->setCellValue('A' . ($i + 2), $s);
            $sheet->setCellValue('B' . ($i + 2), $data['taskByStatus'][$s] ?? 0);
        }

        // --- Column D-E: Ticket by Status ---
        $sheet->setCellValue('D1', 'Estado Ticket');
        $sheet->setCellValue('E1', 'Cantidad');

        $tStatuses = ['open', 'in_progress', 'resolved', 'closed', 'cancelled', 'other'];
        foreach ($tStatuses as $i => $s) {
            $sheet->setCellValue('D' . ($i + 2), $s);
            $sheet->setCellValue('E' . ($i + 2), $data['ticketByStatus'][$s] ?? 0);
        }

        // --- Column G-H: Team workload ---
        $sheet->setCellValue('G1', 'Miembro');
        $sheet->setCellValue('H1', 'Tareas Asignadas');

        $members = $data['members']->take(10)->values();
        foreach ($members as $i => $m) {
            $sheet->setCellValue('G' . ($i + 2), $m['name']);
            $sheet->setCellValue('H' . ($i + 2), $m['assigned']);
        }

        // --- Column J-K: Blocker severity ---
        $sheet->setCellValue('J1', 'Severidad');
        $sheet->setCellValue('K1', 'Cantidad');

        $severities = ['low', 'medium', 'high', 'critical'];
        foreach ($severities as $i => $s) {
            $sheet->setCellValue('J' . ($i + 2), $s);
            $sheet->setCellValue('K' . ($i + 2), $data['blockerBySeverity'][$s] ?? 0);
        }

        // --- Column M-N: Ticket by Priority ---
        $sheet->setCellValue('M1', 'Prioridad Ticket');
        $sheet->setCellValue('N1', 'Cantidad');

        $priorities = ['low', 'medium', 'high', 'urgent'];
        foreach ($priorities as $i => $p) {
            $sheet->setCellValue('M' . ($i + 2), $p);
            $sheet->setCellValue('N' . ($i + 2), $data['ticketByPriority'][$p] ?? 0);
        }

        // Style headers
        $this->writeHeaderRow($sheet, 1, ['Estado Tarea', 'Cnt', '', 'Estado Ticket', 'Cnt', '', 'Miembro', 'Asignadas', '', 'Severidad', 'Cnt', '', 'Prioridad', 'Cnt']);
    }

    // ── Gantt Sheet ──────────────────────────────────────────────────────────

    private function buildGanttSheet(Worksheet $sheet, array $data): void
    {
        $sheet->getTabColor()->setRGB('553C9A');

        $headers = ['Tarea', 'Inicio', 'Fin', 'Duración (días)', 'Progreso %', 'Estado', 'Asignado', 'Offset (días)'];
        $this->writeHeaderRow($sheet, 1, $headers);

        $widths = [35, 14, 14, 16, 12, 14, 22, 14];
        $cols   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($cols as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth($widths[$i]);
        }

        $tasks    = $data['tasks']->filter(fn($t) => $t->due_date !== null)->sortBy('created_at');
        $minDate  = $tasks->min(fn($t) => $t->created_at) ?? now();

        $r = 2;
        foreach ($tasks as $task) {
            $start    = $task->created_at;
            $end      = $task->due_date ?? $start->copy()->addDays(1);
            $duration = max(1, (int) $start->diffInDays($end));
            $offset   = max(0, (int) $minDate->diffInDays($start));

            $sheet->setCellValue('A' . $r, $task->title);
            $sheet->setCellValue('B' . $r, $start->format('d/m/Y'));
            $sheet->setCellValue('C' . $r, $end->format('d/m/Y'));
            $sheet->setCellValue('D' . $r, $duration);
            $sheet->setCellValue('E' . $r, $task->progress ?? 0);
            $sheet->setCellValue('F' . $r, $task->status->value ?? $task->status);
            $sheet->setCellValue('G' . $r, $task->assignee?->name ?? '—');
            $sheet->setCellValue('H' . $r, $offset);

            $bg = $r % 2 === 0 ? 'FFFFFFFF' : 'FFEDF2F7';
            $sheet->getStyle('A' . $r . ':H' . $r)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($bg);

            $r++;
        }

        $lastRow = $r - 1;
        if ($lastRow < 2) {
            $sheet->setCellValue('A2', 'Sin tareas con fecha límite en el período');
            return;
        }

        $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('0"%"');

        // Gantt chart: stacked horizontal bar (Offset + Duration)
        $taskCount = $lastRow - 1;

        $seriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Offset']),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Duración']),
        ];

        $xAxisValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Gantt!$A$2:$A$' . $lastRow, null, $taskCount),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Gantt!$A$2:$A$' . $lastRow, null, $taskCount),
        ];

        $offsetValues   = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Gantt!$H$2:$H$' . $lastRow, null, $taskCount);
        $durationValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Gantt!$D$2:$D$' . $lastRow, null, $taskCount);
        $durationValues->setFillColor('4472C4');

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_STACKED,
            [0, 1],
            $seriesLabels,
            $xAxisValues,
            [$offsetValues, $durationValues]
        );
        $series->setPlotDirection(DataSeries::DIRECTION_BAR);

        $plotArea  = new PlotArea(null, [$series]);
        $legend    = new Legend(Legend::POSITION_BOTTOM, null, false);
        $chartTitle = new ChartTitle('Diagrama de Gantt — ' . now()->format('d/m/Y'));

        $chart = new Chart('ganttChart', $chartTitle, $legend, $plotArea, true, 0, null, null);
        $chart->setTopLeftPosition('J1');
        $chart->setBottomRightPosition('W' . max(20, $lastRow + 5));
        $sheet->addChart($chart);
    }

    // ── Chart helpers ─────────────────────────────────────────────────────────

    private function addPieChart(
        Worksheet $sheet,
        string $chartName,
        string $title,
        string $labelsRange,
        string $valuesRange,
        int $pointCount,
        string $topLeft,
        string $bottomRight
    ): void {
        $seriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 0, [$title]),
        ];
        $xAxisValues  = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $labelsRange, null, $pointCount),
        ];
        $seriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valuesRange, null, $pointCount),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            $seriesLabels,
            $xAxisValues,
            $seriesValues
        );

        $plotArea   = new PlotArea(null, [$series]);
        $legend     = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new ChartTitle($title);

        $chart = new Chart($chartName, $chartTitle, $legend, $plotArea, true, 0, null, null);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);
        $sheet->addChart($chart);
    }

    private function addBarChart(
        Worksheet $sheet,
        string $chartName,
        string $title,
        string $labelsRange,
        string $valuesRange,
        int $pointCount,
        string $topLeft,
        string $bottomRight
    ): void {
        if ($pointCount <= 0) {
            return;
        }

        $seriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 0, [$title]),
        ];
        $xAxisValues  = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $labelsRange, null, $pointCount),
        ];
        $seriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valuesRange, null, $pointCount),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            $seriesLabels,
            $xAxisValues,
            $seriesValues
        );

        $plotArea   = new PlotArea(null, [$series]);
        $legend     = new Legend(Legend::POSITION_BOTTOM, null, false);
        $chartTitle = new ChartTitle($title);

        $chart = new Chart($chartName, $chartTitle, $legend, $plotArea, true, 0, null, null);
        $chart->setTopLeftPosition($topLeft);
        $chart->setBottomRightPosition($bottomRight);
        $sheet->addChart($chart);
    }

    // ── Styling helpers ──────────────────────────────────────────────────────

    private function sheetTitle(Worksheet $sheet, string $from, string $to, string $text): void
    {
        $sheet->mergeCells($from . ':' . $to);
        $sheet->setCellValue($from, $text);
        $sheet->getStyle($from . ':' . $to)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::FG_WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BG_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    private function headerRow(Worksheet $sheet, string $range, array $headers): void
    {
        [$from] = explode(':', $range);
        $col    = $from[0];
        $row    = (int) substr($from, 1);
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col = chr(ord($col) + 1);
        }
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => self::FG_WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BG_ACCENT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function writeHeaderRow(Worksheet $sheet, int $row, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => self::FG_WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::BG_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E0']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    private function styleInfoRow(Worksheet $sheet, string $range, bool $even): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $even ? self::BG_WHITE : self::BG_LIGHT]],
            'font'      => ['color' => ['argb' => self::FG_DARK]],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFCBD5E0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        // Make label column bold
        [$fromCell] = explode(':', $range);
        $sheet->getStyle($fromCell)->getFont()->setBold(true)->getColor()->setARGB(self::FG_DARK);
    }

    private function mergeAndStyle(Worksheet $sheet, string $from, string $to, string $text, array $style): void
    {
        $sheet->mergeCells($from . ':' . $to);
        $sheet->setCellValue($from, $text);
        $sheet->getStyle($from . ':' . $to)->applyFromArray($style);
    }
}
