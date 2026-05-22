import type {
    MessageResponse,
    PaginatedResponse,
    ResourceResponse,
    TaskListQuery,
    TaskPayload,
    TaskResourceItem,
} from './types';
import { requestJson } from './http';

export const projectTasksService = {
    list(projectId: number, query?: TaskListQuery): Promise<PaginatedResponse<TaskResourceItem>> {
        return requestJson<PaginatedResponse<TaskResourceItem>>(`/projects/${projectId}/tasks`, { query });
    },

    get(projectId: number, taskId: number): Promise<ResourceResponse<TaskResourceItem>> {
        return requestJson<ResourceResponse<TaskResourceItem>>(`/projects/${projectId}/tasks/${taskId}`);
    },

    create(projectId: number, payload: TaskPayload): Promise<ResourceResponse<TaskResourceItem>> {
        return requestJson<ResourceResponse<TaskResourceItem>>(`/projects/${projectId}/tasks`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, taskId: number, payload: TaskPayload): Promise<ResourceResponse<TaskResourceItem>> {
        return requestJson<ResourceResponse<TaskResourceItem>>(`/projects/${projectId}/tasks/${taskId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number, taskId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/tasks/${taskId}`, {
            method: 'DELETE',
        });
    },
};
