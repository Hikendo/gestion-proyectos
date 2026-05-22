import type {
    CollectionResponse,
    MessageResponse,
    ProjectPhaseItem,
    ProjectPhasePayload,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectPhasesService = {
    list(projectId: number): Promise<CollectionResponse<ProjectPhaseItem>> {
        return requestJson<CollectionResponse<ProjectPhaseItem>>(`/projects/${projectId}/phases`);
    },

    create(projectId: number, payload: ProjectPhasePayload): Promise<ResourceResponse<ProjectPhaseItem>> {
        return requestJson<ResourceResponse<ProjectPhaseItem>>(`/projects/${projectId}/phases`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, phaseId: number, payload: ProjectPhasePayload): Promise<ResourceResponse<ProjectPhaseItem>> {
        return requestJson<ResourceResponse<ProjectPhaseItem>>(`/projects/${projectId}/phases/${phaseId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number, phaseId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/phases/${phaseId}`, {
            method: 'DELETE',
        });
    },
};
