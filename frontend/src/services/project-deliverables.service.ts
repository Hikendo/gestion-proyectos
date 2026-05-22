import type {
    CollectionResponse,
    DeliverableItem,
    DeliverablePayload,
    MessageResponse,
    ResourceResponse,
} from './types';
import { requestJson } from './http';

export const projectDeliverablesService = {
    list(projectId: number): Promise<CollectionResponse<DeliverableItem>> {
        return requestJson<CollectionResponse<DeliverableItem>>(`/projects/${projectId}/deliverables`);
    },

    create(projectId: number, payload: DeliverablePayload): Promise<ResourceResponse<DeliverableItem>> {
        return requestJson<ResourceResponse<DeliverableItem>>(`/projects/${projectId}/deliverables`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, deliverableId: number, payload: DeliverablePayload): Promise<ResourceResponse<DeliverableItem>> {
        return requestJson<ResourceResponse<DeliverableItem>>(`/projects/${projectId}/deliverables/${deliverableId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    approve(projectId: number, deliverableId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/deliverables/${deliverableId}/approve`, {
            method: 'PATCH',
        });
    },
};
