import { apiWithToken } from './http';

export type ReportPeriod = 'last_month' | 'last_quarter' | 'full';

async function triggerDownload(projectId: number, endpoint: string, period: ReportPeriod, filename: string): Promise<void> {
    const response = await apiWithToken.get(`/projects/${projectId}/reports/${endpoint}`, {
        params: { period },
        responseType: 'blob',
    });

    // Guard: if server returned JSON instead of a binary (e.g. auth error)
    const contentType: string = response.headers['content-type'] ?? '';
    if (contentType.includes('application/json')) {
        const text = await (response.data as Blob).text();
        throw new Error(JSON.parse(text)?.message ?? 'Error al generar el reporte');
    }

    const url = URL.createObjectURL(response.data as Blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
}

export function downloadExecutiveReport(projectId: number, period: ReportPeriod = 'full'): Promise<void> {
    const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    return triggerDownload(projectId, 'executive', period, `reporte_ejecutivo_${date}.docx`);
}

export function downloadDashboardReport(projectId: number, period: ReportPeriod = 'full'): Promise<void> {
    const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    return triggerDownload(projectId, 'dashboard', period, `dashboard_${date}.xlsx`);
}
