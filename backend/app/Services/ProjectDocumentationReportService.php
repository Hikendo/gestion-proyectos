<?php

namespace App\Services;

use App\Models\Project;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;

class ProjectDocumentationReportService
{
    private const C_DARK    = '1A365D';
    private const C_PRIMARY = '2C5282';
    private const C_ACCENT  = '2B6CB0';
    private const C_LIGHT   = 'EBF8FF';
    private const C_WHITE   = 'FFFFFF';
    private const C_TEXT    = '2D3748';
    private const C_MUTED   = '718096';
    private const C_SUCCESS = '276749';
    private const C_ERROR   = '9B2335';
    private const C_WARN    = '975A16';

    private function eagerLoad(Project $project): void
    {
        $project->load([
            'owner',
            'members.user',
            'phases.objectives',
            'phases.tasks.assignee',
            'phases.tasks.comments',
            'phases.deliverables',
            'objectives',
            'plans',
            'risks',
            'blockers.task',
            'blockers.createdBy',
            'tickets.assignee',
            'tickets.creator',
            'milestones',
        ]);
    }

    public function generate(Project $project): string
    {
        $this->eagerLoad($project);
        $phpWord  = $this->buildDocument($project);
        $tempFile = tempnam(sys_get_temp_dir(), 'doc_report_') . '.docx';
        $writer   = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        return $tempFile;
    }

    public function generateOdt(Project $project): string
    {
        $this->eagerLoad($project);
        $phpWord  = $this->buildDocument($project);
        $tempFile = tempnam(sys_get_temp_dir(), 'doc_report_') . '.odt';
        $writer   = IOFactory::createWriter($phpWord, 'ODText');
        $writer->save($tempFile);
        return $tempFile;
    }

    private function buildDocument(Project $project): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->getSettings()->setUpdateFields(true);

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
        $phpWord->addFontStyle('coverTitle', ['size' => 28, 'bold' => true,  'color' => self::C_WHITE]);

        $phpWord->addTitleStyle(1, ['size' => 18, 'bold' => true, 'color' => self::C_PRIMARY]);
        $phpWord->addTitleStyle(2, ['size' => 14, 'bold' => true, 'color' => self::C_ACCENT]);

        $phpWord->addParagraphStyle('pCenter',  ['alignment' => Jc::CENTER]);
        $phpWord->addParagraphStyle('pSpacing', ['spaceAfter' => 200]);

        $section = $phpWord->addSection([
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
            'marginTop'    => 1440,
            'marginBottom' => 1440,
        ]);

        $this->addCoverPage($section, $project);
        $section->addPageBreak();
        $this->addSectionTitle($section, 'Índice');
        $section->addText('(Actualice los campos en Word/LibreOffice para generar el índice automático.)', 'small', 'pSpacing');
        $section->addPageBreak();

        $this->addSectionTitle($section, '1. Descripción del Proyecto');
        $this->addProjectInfoTable($section, $project);
        $section->addText($project->description ?? 'Sin descripción.', 'normal', 'pSpacing');
        $section->addPageBreak();

        $this->addSectionTitle($section, '2. Objetivos');
        $genObjectives = $project->objectives->where('type', 'general');
        $specObjectives = $project->objectives->where('type', 'specific');
        if ($genObjectives->isNotEmpty()) {
            $this->addSubTitle($section, '2.1 Objetivos Generales');
            foreach ($genObjectives as $obj) {
                $status = $obj->completed ? '✓ COMPLETADO' : '○ Pendiente';
                $style  = $obj->completed ? 'success' : 'normal';
                $section->addText("{$status} — {$obj->title}", $style, 'pSpacing');
                if ($obj->description) {
                    $section->addText(strip_tags($obj->description), 'small');
                }
                $section->addTextBreak();
            }
        }
        if ($specObjectives->isNotEmpty()) {
            $this->addSubTitle($section, '2.2 Objetivos Específicos por Fase');
            $objectivesByPhase = $specObjectives->groupBy('phase_id');
            foreach ($project->phases as $phase) {
                $phaseObjs = $objectivesByPhase->get($phase->id) ?? collect();
                if ($phaseObjs->isEmpty()) continue;
                $this->addSubSubTitle($section, "Fase: {$phase->name}");
                foreach ($phaseObjs as $obj) {
                    $status = $obj->completed ? '✓' : '○';
                    $style  = $obj->completed ? 'success' : 'normal';
                    $section->addText("{$status} {$obj->title}", $style, 'pSpacing');
                    if ($obj->description) {
                        $section->addText(strip_tags($obj->description), 'small');
                    }
                    $section->addTextBreak();
                }
            }
            $unassigned = $specObjectives->whereNull('phase_id');
            if ($unassigned->isNotEmpty()) {
                $this->addSubSubTitle($section, 'Sin fase asignada');
                foreach ($unassigned as $obj) {
                    $status = $obj->completed ? '✓' : '○';
                    $section->addText("{$status} {$obj->title}", 'normal', 'pSpacing');
                }
            }
        }
        $section->addPageBreak();

        $plan = $project->plans->first();
        if ($plan) {
            $this->addSectionTitle($section, '3. Plan del Proyecto');
            if ($plan->scope) {
                $this->addSubTitle($section, '3.1 Alcance');
                $this->addHtmlContent($section, $plan->scope);
            }
            if ($plan->requirements) {
                $this->addSubTitle($section, '3.2 Requerimientos');
                $this->addHtmlContent($section, $plan->requirements);
            }
            if ($plan->technical_notes) {
                $this->addSubTitle($section, '3.3 Notas Técnicas');
                $this->addHtmlContent($section, $plan->technical_notes);
            }
            $section->addPageBreak();
        }

        $this->addSectionTitle($section, '4. Fases del Proyecto');
        foreach ($project->phases as $phase) {
            $this->addSubTitle($section, "Fase: {$phase->name}");
            $tbl = $section->addTable($this->defaultTableStyle());
            $this->addHeaderRow($tbl, ['Campo', 'Valor'], [2400, 4500]);
            $this->addDataRow($tbl, ['Inicio', $phase->start_date?->format('d/m/Y') ?? '—'], 0);
            $this->addDataRow($tbl, ['Fin', $phase->end_date?->format('d/m/Y') ?? '—'], 1);
            $this->addDataRow($tbl, ['Progreso', $phase->progress . '%'], 0);
            $section->addTextBreak();

            $phaseObjs = $project->objectives->where('phase_id', $phase->id);
            if ($phaseObjs->isNotEmpty()) {
                $this->addSubSubTitle($section, 'Objetivos de la fase');
                foreach ($phaseObjs as $obj) {
                    $section->addText("• {$obj->title}", 'normal');
                }
                $section->addTextBreak();
            }

            if ($project->milestones->isNotEmpty()) {
                $this->addSubSubTitle($section, 'Hitos');
                $tblM = $section->addTable($this->defaultTableStyle());
                $this->addHeaderRow($tblM, ['Hito', 'Fecha', 'Estado'], [4000, 1800, 1600]);
                foreach ($project->milestones as $i => $m) {
                    $isDelayed = !$m->completed && $m->target_date && $m->target_date->isPast();
                    $status    = $m->completed ? 'Completado' : ($isDelayed ? 'Retrasado' : 'Pendiente');
                    $fStyle    = $m->completed ? 'success' : ($isDelayed ? 'danger' : 'tblCell');
                    $tblM->addRow(360);
                    $bg = $i % 2 === 0 ? null : self::C_LIGHT;
                    $this->addCell($tblM, $m->title, 4000, $bg);
                    $this->addCell($tblM, $m->target_date?->format('d/m/Y') ?? '—', 1800, $bg);
                    $tblM->addCell(1600, $bg ? ['bgColor' => $bg] : [])->addText($status, $fStyle);
                }
                $section->addTextBreak();
            }

            $phaseDeliverables = $project->deliverables->where('phase_id', $phase->id);
            if ($phaseDeliverables->isNotEmpty()) {
                $this->addSubSubTitle($section, 'Entregables');
                $tblD = $section->addTable($this->defaultTableStyle());
                $this->addHeaderRow($tblD, ['Entregable', 'Fecha', 'Aprobado'], [4000, 1800, 1600]);
                foreach ($phaseDeliverables as $i => $d) {
                    $approved = $d->approved ? '✓ Aprobado' : 'Pendiente';
                    $fStyle   = $d->approved ? 'success' : 'normal';
                    $tblD->addRow(360);
                    $bg = $i % 2 === 0 ? null : self::C_LIGHT;
                    $this->addCell($tblD, $d->name, 4000, $bg);
                    $this->addCell($tblD, $d->delivery_date?->format('d/m/Y') ?? '—', 1800, $bg);
                    $tblD->addCell(1600, $bg ? ['bgColor' => $bg] : [])->addText($approved, $fStyle);
                }
                $section->addTextBreak();
            }

            $phaseTasks = $project->tasks->where('phase_id', $phase->id);
            if ($phaseTasks->isNotEmpty()) {
                $this->addSubSubTitle($section, 'Tareas');
                $tblT = $section->addTable($this->defaultTableStyle());
                $this->addHeaderRow($tblT, ['Tarea', 'Estado', 'Asignado', 'Progreso'], [3800, 1400, 1800, 1000]);
                foreach ($phaseTasks as $i => $t) {
                    $tblT->addRow(360);
                    $bg = $i % 2 === 0 ? null : self::C_LIGHT;
                    $this->addCell($tblT, $t->title, 3800, $bg);
                    $status = $t->status->label();
                    $fStyle = $t->status->value === 'done' ? 'success' : ($t->status->value === 'blocked' ? 'danger' : 'tblCell');
                    $tblT->addCell(1400, $bg ? ['bgColor' => $bg] : [])->addText($status, $fStyle);
                    $this->addCell($tblT, $t->assignee?->name ?? '—', 1800, $bg);
                    $this->addCell($tblT, ($t->progress ?? 0) . '%', 1000, $bg);
                }
                $section->addTextBreak();
            }
            $section->addPageBreak();
        }

        $risks = $project->risks;
        if ($risks->isNotEmpty()) {
            $this->addSectionTitle($section, '5. Análisis de Riesgos');
            foreach ($risks as $i => $r) {
                $impact = $r->impact->label();
                $prob   = $r->probability->label();
                $status = $r->status->label();
                $section->addText(($i + 1) . ". {$r->title}", 'bold', 'pSpacing');
                $section->addText("Impacto: {$impact}  |  Probabilidad: {$prob}  |  Estado: {$status}", 'small');
                if ($r->description) {
                    $section->addText(strip_tags($r->description), 'small');
                }
                if ($r->mitigation_plan) {
                    $section->addText("Plan de mitigación: " . strip_tags($r->mitigation_plan), 'small');
                }
                $section->addTextBreak();
            }
            $section->addPageBreak();
        }

        $blockers = $project->blockers;
        if ($blockers->isNotEmpty()) {
            $this->addSectionTitle($section, '6. Bloqueadores');
            $tblB = $section->addTable($this->defaultTableStyle());
            $this->addHeaderRow($tblB, ['Título', 'Severidad', 'Estado', 'Tarea asociada'], [3000, 1400, 1400, 2600]);
            foreach ($blockers as $i => $b) {
                $bStatus = $b->resolved ? 'Resuelto' : 'Activo';
                $fStyle  = $b->resolved ? 'success' : ($b->severity->value === 'critical' ? 'danger' : 'tblCell');
                $tblB->addRow(360);
                $bg = $i % 2 === 0 ? null : self::C_LIGHT;
                $this->addCell($tblB, $b->title, 3000, $bg);
                $this->addCell($tblB, $b->severity->label(), 1400, $bg);
                $tblB->addCell(1400, $bg ? ['bgColor' => $bg] : [])->addText($bStatus, $fStyle);
                $this->addCell($tblB, $b->task?->title ?? '—', 2600, $bg);
            }
            $section->addTextBreak();
            $section->addPageBreak();
        }

        $tickets = $project->tickets;
        if ($tickets->isNotEmpty()) {
            $this->addSectionTitle($section, '7. Tickets');
            $tblTk = $section->addTable($this->defaultTableStyle());
            $this->addHeaderRow($tblTk, ['Asunto', 'Estado', 'Prioridad', 'Asignado', 'Creado por'], [3000, 1200, 1200, 1500, 1500]);
            foreach ($tickets as $i => $tk) {
                $tblTk->addRow(360);
                $bg = $i % 2 === 0 ? null : self::C_LIGHT;
                $this->addCell($tblTk, $tk->subject, 3000, $bg);
                $this->addCell($tblTk, $tk->status->label(), 1200, $bg);
                $this->addCell($tblTk, $tk->priority->label(), 1200, $bg);
                $this->addCell($tblTk, $tk->assignee?->name ?? '—', 1500, $bg);
                $this->addCell($tblTk, $tk->creator?->name ?? '—', 1500, $bg);
            }
            $section->addTextBreak();
            $section->addPageBreak();
        }

        $this->addSectionTitle($section, '8. Miembros del Equipo');
        $tblM = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tblM, ['Nombre', 'Email', 'Rol en proyecto', 'Estado'], [2200, 2400, 1600, 1200]);
        foreach ($project->members as $i => $member) {
            $suspended = $member->suspended_at ? 'Suspendido' : 'Activo';
            $fStyle    = $member->suspended_at ? 'warn' : 'success';
            $tblM->addRow(360);
            $bg = $i % 2 === 0 ? null : self::C_LIGHT;
            $this->addCell($tblM, $member->user?->name ?? 'N/A', 2200, $bg);
            $this->addCell($tblM, $member->user?->email ?? '—', 2400, $bg);
            $this->addCell($tblM, $member->role, 1600, $bg);
            $tblM->addCell(1200, $bg ? ['bgColor' => $bg] : [])->addText($suspended, $fStyle);
        }
        $section->addTextBreak();
        $this->addSubTitle($section, 'Propietario del Proyecto');
        $section->addText(($project->owner?->name ?? 'N/A') . ' — ' . ($project->owner?->email ?? ''), 'normal');
        $section->addPageBreak();

        $this->addSectionTitle($section, '9. Anexo — Indicadores Clave (KPIs)');
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();
        $doneTasks  = $tasks->where('status', 'done')->count();
        $overdueTasks = $tasks->filter(fn($t) => $t->due_date && $t->due_date->isPast() && $t->status !== 'done')->count();
        $completionRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 1) : 0;

        $allTickets  = $project->tickets;
        $openTickets = $allTickets->whereIn('status', ['open', 'in_progress'])->count();
        $closedTickets = $allTickets->whereIn('status', ['resolved', 'closed'])->count();

        $openBlockers = $project->blockers->where('resolved', false)->count();
        $criticalBlockers = $project->blockers->where('resolved', false)->where('severity', 'critical')->count();

        $teamProd = 0;
        $membersData = $project->members->map(function ($m) use ($tasks) {
            $mTasks = $tasks->where('assigned_to', $m->user_id);
            $total  = $mTasks->count();
            $done   = $mTasks->where('status', 'done')->count();
            return $total > 0 ? round($done / $total * 100) : 0;
        });
        if ($membersData->isNotEmpty()) {
            $teamProd = round($membersData->avg());
        }
        $scheduleCompliance = $totalTasks > 0 ? round(($totalTasks - $overdueTasks) / $totalTasks * 100, 1) : 100;

        $kpis = [
            ['Total de Tareas',           (string)$totalTasks],
            ['Tareas Completadas',        (string)$doneTasks],
            ['Tasa de Completitud',       $completionRate . '%'],
            ['Tareas Vencidas',           (string)$overdueTasks],
            ['Tickets Abiertos',          (string)$openTickets],
            ['Tickets Cerrados',          (string)$closedTickets],
            ['Bloqueadores Abiertos',     (string)$openBlockers],
            ['Bloqueadores Críticos',     (string)$criticalBlockers],
            ['Productividad del Equipo',  $teamProd . '%'],
            ['Cumplimiento Cronograma',   $scheduleCompliance . '%'],
        ];
        $tblK = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tblK, ['Indicador', 'Valor'], [5500, 1900]);
        foreach ($kpis as $i => [$label, $value]) {
            $this->addDataRow($tblK, [$label, $value], $i % 2);
        }

        return $phpWord;
    }

    private function addCoverPage(\PhpOffice\PhpWord\Element\Section $section, Project $project): void
    {
        for ($i = 0; $i < 8; $i++) {
            $section->addTextBreak();
        }
        $section->addText('DOCUMENTACIÓN DEL PROYECTO', 'coverTitle', 'pCenter');
        $section->addTextBreak();
        $section->addText($project->name, ['size' => 22, 'bold' => true, 'color' => self::C_PRIMARY], 'pCenter');
        $section->addTextBreak();
        $section->addText('Código: ' . ($project->code ?? 'N/A'), ['size' => 12, 'color' => self::C_MUTED], 'pCenter');
        $section->addTextBreak(3);

        $tblStyle = [
            'borderSize'  => 0,
            'cellMargin'  => 100,
            'alignment'   => JcTable::CENTER,
            'width'       => 5000,
            'unit' => TblWidth::TWIP,
        ];
        $table = $section->addTable($tblStyle);
        $this->addCoverRow($table, 'Estado', $project->status->label());
        $this->addCoverRow($table, 'Propietario', $project->owner?->name ?? 'N/A');
        $this->addCoverRow($table, 'Progreso', $project->progress . '%');
        $this->addCoverRow($table, 'Fecha inicio', $project->start_date?->format('d/m/Y') ?? '—');
        $this->addCoverRow($table, 'Fecha fin', $project->end_date?->format('d/m/Y') ?? '—');
        $this->addCoverRow($table, 'Generado', now()->format('d/m/Y H:i'));
        $section->addTextBreak(4);
        $section->addText(
            'Documento generado automáticamente por el Sistema de Gestión de Proyectos',
            ['size' => 8, 'italic' => true, 'color' => self::C_MUTED],
            'pCenter'
        );
    }

    private function addCoverRow(\PhpOffice\PhpWord\Element\Table $table, string $label, string $value): void
    {
        $table->addRow(360);
        $table->addCell(2400, ['bgColor' => self::C_PRIMARY])->addText(
            $label,
            ['size' => 10, 'bold' => true, 'color' => self::C_WHITE],
            ['alignment' => Jc::RIGHT]
        );
        $table->addCell(2800)->addText($value, 'normal', 'pSpacing');
    }

    private function addProjectInfoTable(\PhpOffice\PhpWord\Element\Section $section, Project $project): void
    {
        $tbl = $section->addTable($this->defaultTableStyle());
        $this->addHeaderRow($tbl, ['Campo', 'Valor'], [2400, 5000]);
        $this->addDataRow($tbl, ['Nombre',        $project->name], 0);
        $this->addDataRow($tbl, ['Código',        $project->code ?? 'N/A'], 1);
        $this->addDataRow($tbl, ['Estado',        $project->status->label()], 0);
        $this->addDataRow($tbl, ['Propietario',   $project->owner?->name ?? 'N/A'], 1);
        $this->addDataRow($tbl, ['Fecha inicio',  $project->start_date?->format('d/m/Y') ?? '—'], 0);
        $this->addDataRow($tbl, ['Fecha fin',     $project->end_date?->format('d/m/Y') ?? '—'], 1);
        $this->addDataRow($tbl, ['Presupuesto',   $project->budget ? '$' . number_format((float)$project->budget, 2) : '—'], 0);
        $this->addDataRow($tbl, ['Progreso',      $project->progress . '%'], 1);
        $section->addTextBreak();
    }

    private function addHtmlContent(\PhpOffice\PhpWord\Element\Section $section, string $html): void
    {
        // Use str_replace for closing tags (no regex, avoids formatter mangling slash issues)
        $text = strip_tags($html, '<p><br><li><ul><ol><strong><em><h1><h2><h3><h4>');
        $text = str_replace(['</p>', '</P>'], "\n", $text);
        $text = preg_replace('~<br\s*/?>~i', "\n", $text);
        $text = str_replace(['</li>', '</LI>'], "\n", $text);
        $text = str_replace(['</h1>', '</H1>', '</h2>', '</H2>', '</h3>', '</H3>', '</h4>', '</H4>'], "\n", $text);
        $text = str_replace(['</ul>', '</UL>', '</ol>', '</OL>'], "\n", $text);
        $text = strip_tags($text);
        $text = preg_replace("~\n\s*\n~", "\n", trim($text));

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $section->addTextBreak();
            } else {
                $section->addText($trimmed, 'normal', 'pSpacing');
            }
        }
    }

    private function addSectionTitle(\PhpOffice\PhpWord\Element\Section $section, string $title): void
    {
        $section->addTitle($title, 1);
        $section->addTextBreak();
    }

    private function addSubTitle(\PhpOffice\PhpWord\Element\Section $section, string $title): void
    {
        $section->addTitle($title, 2);
        $section->addTextBreak();
    }

    private function addSubSubTitle(\PhpOffice\PhpWord\Element\Section $section, string $title): void
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
        foreach ($cells as $value) {
            $cellStyle = $bgColor ? ['bgColor' => $bgColor] : [];
            $tbl->addCell(0, $cellStyle)->addText((string)$value, 'tblCell');
        }
    }

    private function addCell(\PhpOffice\PhpWord\Element\Table $tbl, string $text, int $width, ?string $bgColor): void
    {
        $style = $bgColor ? ['bgColor' => $bgColor] : [];
        $tbl->addCell($width, $style)->addText($text, 'tblCell');
    }

    private function defaultTableStyle(): array
    {
        return [
            'borderSize' => 4,
            'borderColor' => 'CBD5E0',
            'cellMargin' => 80,
            'width' => 100,
            'unit' => TblWidth::PERCENT,
        ];
    }
}
