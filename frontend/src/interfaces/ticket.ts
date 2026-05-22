import type { BaseModel } from './ModelBaseI';
import type { TicketPriority, TicketStatus } from './enums';
import type { Project } from './project';
import type { User } from './user';

export interface Ticket extends BaseModel {
    project_id: number;
    created_by?: number | null;
    assigned_to?: number | null;
    subject: string;
    description?: string | null;
    status: TicketStatus;
    priority: TicketPriority;
    project?: Project;
    creator?: User;
    assignee?: User;
}
