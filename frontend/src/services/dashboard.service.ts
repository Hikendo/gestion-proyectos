import type { DashboardResponse } from './types';
import { requestJson } from './http';

export const dashboardService = {
    get(): Promise<DashboardResponse> {
        return requestJson<DashboardResponse>('/dashboard');
    },
};
