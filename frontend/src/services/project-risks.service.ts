import type {
    CollectionResponse,
    MessageResponse,
    ResourceResponse,
    RiskItem,
    RiskPayload,
} from './types';
import { requestJson } from './http';

export const projectRisksService = {
    list(projectId: number): Promise<CollectionResponse<RiskItem>> {
        return requestJson<CollectionResponse<RiskItem>>(`/projects/${projectId}/risks`);
    },

    create(projectId: number, payload: RiskPayload): Promise<ResourceResponse<RiskItem>> {
        return requestJson<ResourceResponse<RiskItem>>(`/projects/${projectId}/risks`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, riskId: number, payload: RiskPayload): Promise<ResourceResponse<RiskItem>> {
        return requestJson<ResourceResponse<RiskItem>>(`/projects/${projectId}/risks/${riskId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number, riskId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/risks/${riskId}`, {
            method: 'DELETE',
        });
    },
};
