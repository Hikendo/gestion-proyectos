import type {
    CollectionResponse,
    MessageResponse,
    ResourceResponse,
    TaskCommentItem,
    TaskCommentPayload,
} from './types';
import { requestJson } from './http';

export const taskCommentsService = {
    list(taskId: number): Promise<CollectionResponse<TaskCommentItem>> {
        return requestJson<CollectionResponse<TaskCommentItem>>(`/tasks/${taskId}/comments`);
    },

    create(taskId: number, payload: TaskCommentPayload): Promise<ResourceResponse<TaskCommentItem>> {
        return requestJson<ResourceResponse<TaskCommentItem>>(`/tasks/${taskId}/comments`, {
            method: 'POST',
            body: payload,
        });
    },

    remove(taskId: number, commentId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/tasks/${taskId}/comments/${commentId}`, {
            method: 'DELETE',
        });
    },
};
