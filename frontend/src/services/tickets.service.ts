import type {
    MessageResponse,
    PaginatedResponse,
    ResourceResponse,
    TicketListQuery,
    TicketPayload,
    TicketResourceItem,
} from './types';
import { requestJson } from './http';

export const ticketsService = {
    list(projectId: number, query?: TicketListQuery): Promise<PaginatedResponse<TicketResourceItem>> {
        return requestJson<PaginatedResponse<TicketResourceItem>>(`/projects/${projectId}/tickets`, { query });
    },

    get(projectId: number, ticketId: number): Promise<ResourceResponse<TicketResourceItem>> {
        return requestJson<ResourceResponse<TicketResourceItem>>(`/projects/${projectId}/tickets/${ticketId}`);
    },

    create(projectId: number, payload: TicketPayload): Promise<ResourceResponse<TicketResourceItem>> {
        return requestJson<ResourceResponse<TicketResourceItem>>(`/projects/${projectId}/tickets`, {
            method: 'POST',
            body: payload,
        });
    },

    update(projectId: number, ticketId: number, payload: TicketPayload): Promise<ResourceResponse<TicketResourceItem>> {
        return requestJson<ResourceResponse<TicketResourceItem>>(`/projects/${projectId}/tickets/${ticketId}`, {
            method: 'PUT',
            body: payload,
        });
    },

    remove(projectId: number, ticketId: number): Promise<MessageResponse> {
        return requestJson<MessageResponse>(`/projects/${projectId}/tickets/${ticketId}`, {
            method: 'DELETE',
        });
    },
};
