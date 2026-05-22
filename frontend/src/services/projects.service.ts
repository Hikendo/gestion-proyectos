import type {
    MessageResponse,
    PaginatedResponse,
    ProjectListQuery,
    ProjectPayload,
    ProjectResourceItem,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectsService = {
    list(query?: ProjectListQuery): Promise<PaginatedResponse<ProjectResourceItem>> {
        return requestJson<PaginatedResponse<ProjectResourceItem>>('/projects', { query });
    },

    get(projectId: number): Promise<ResourceResponse<ProjectResourceItem>> {
        return requestJson<ResourceResponse<ProjectResourceItem>>(`/projects/${projectId}`);
    },

    create(payload: ProjectPayload): Promise<ResourceResponse<ProjectResourceItem>> {
        return requestJson<ResourceResponse<ProjectResourceItem>>('/projects', {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, payload: ProjectPayload): Promise<ResourceResponse<ProjectResourceItem>> {
        return requestJson<ResourceResponse<ProjectResourceItem>>(`/projects/${projectId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}`, {
            method: 'DELETE',
        });
    },
};
