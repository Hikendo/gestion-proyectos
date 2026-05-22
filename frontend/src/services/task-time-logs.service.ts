import type {
    CollectionResponse,
    ResourceResponse,
    TaskTimeLogItem,
    TaskTimeLogPayload,
} from './types';
import { requestJson } from './http';

export const taskTimeLogsService = {
    list(taskId: number): Promise<CollectionResponse<TaskTimeLogItem>> {
        return requestJson<CollectionResponse<TaskTimeLogItem>>(`/tasks/${taskId}/time-logs`);
    },

    create(taskId: number, payload: TaskTimeLogPayload): Promise<ResourceResponse<TaskTimeLogItem>> {
        return requestJson<ResourceResponse<TaskTimeLogItem>>(`/tasks/${taskId}/time-logs`, {
            method: 'POST',
            body: payload,
        });
    },
};
