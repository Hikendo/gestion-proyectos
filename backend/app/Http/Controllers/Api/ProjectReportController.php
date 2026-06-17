<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ReportPeriodDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ProjectReportRequest;
use App\Models\Project;
use App\Services\ProjectDashboardReportService;
use App\Services\ProjectDocumentationReportService;
use App\Services\ProjectExecutiveReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectReportController extends Controller
{
    public function __construct(
        private readonly ProjectExecutiveReportService  $executiveService,
        private readonly ProjectDashboardReportService  $dashboardService,
        private readonly ProjectDocumentationReportService $documentationService,
    ) {}

    /**
     * GET /api/v1/projects/{project}/reports/executive?period=full
     */
    public function executive(ProjectReportRequest $request, Project $project): BinaryFileResponse
    {
        $period   = new ReportPeriodDTO($request->period());
        $filePath = $this->executiveService->generate($project, $period);
        $fileName = $this->safeFileName($project->name) . '_reporte_ejecutivo_' . now()->format('Ymd_His') . '.docx';

        return response()
            ->download($filePath, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/projects/{project}/reports/executive-odt?period=full
     */
    public function executiveOdt(ProjectReportRequest $request, Project $project): BinaryFileResponse
    {
        $period   = new ReportPeriodDTO($request->period());
        $filePath = $this->executiveService->generateOdt($project, $period);
        $fileName = $this->safeFileName($project->name) . '_reporte_ejecutivo_' . now()->format('Ymd_His') . '.odt';

        return response()
            ->download($filePath, $fileName, ['Content-Type' => 'application/vnd.oasis.opendocument.text'])
            ->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/projects/{project}/reports/dashboard?period=full
     */
    public function dashboard(ProjectReportRequest $request, Project $project): BinaryFileResponse
    {
        $period   = new ReportPeriodDTO($request->period());
        $filePath = $this->dashboardService->generate($project, $period);
        $fileName = $this->safeFileName($project->name) . '_dashboard_' . now()->format('Ymd_His') . '.xlsx';

        return response()
            ->download($filePath, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/projects/{project}/reports/documentation
     */
    public function documentation(Request $request, Project $project): BinaryFileResponse
    {
        $filePath = $this->documentationService->generate($project);
        $fileName = $this->safeFileName($project->name) . '_documentacion_' . now()->format('Ymd_His') . '.docx';

        return response()
            ->download($filePath, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/projects/{project}/reports/documentation-odt
     */
    public function documentationOdt(Request $request, Project $project): BinaryFileResponse
    {
        $filePath = $this->documentationService->generateOdt($project);
        $fileName = $this->safeFileName($project->name) . '_documentacion_' . now()->format('Ymd_His') . '.odt';

        return response()
            ->download($filePath, $fileName, ['Content-Type' => 'application/vnd.oasis.opendocument.text'])
            ->deleteFileAfterSend(true);
    }

    private function safeFileName(string $name): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '_', $name));
    }
}
