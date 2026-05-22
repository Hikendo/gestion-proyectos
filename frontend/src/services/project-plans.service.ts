import type { MessageResponse, ProjectPlanItem, ProjectPlanPayload } from './types';
import { requestJson } from './http';

export const projectPlansService = {
    get(projectId: number): Promise<ProjectPlanItem | null> {
        return requestJson<ProjectPlanItem | null>(`/projects/${projectId}/plan`);
    },

    save(projectId: number, payload: ProjectPlanPayload): Promise<ProjectPlanItem> {
        return requestJson<ProjectPlanItem>(`/projects/${projectId}/plan`, {
            method: 'POST',
            body: payload,
        });
    },
};
