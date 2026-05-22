import type {
    Blocker,
    Deliverable,
    Milestone,
    Objective,
    Project,
    ProjectMember,
    ProjectPhase,
    ProjectPlan,
    Risk,
    Task,
    TaskAttachment,
    TaskComment,
    TaskTimeLog,
    Ticket,
    User,
    UserMetric,
    ProjectStatus,
    TaskPriority,
    TaskStatus,
    TicketPriority,
    TicketStatus,
    BlockerSeverity,
    RiskImpact,
    RiskProbability,
    ProjectMemberRole,
    ObjectiveType,
} from '../interfaces';

export type { CollectionResponse, MessageResponse, PaginatedResponse, ResourceResponse } from './http';

export interface AuthSessionUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    permissions: string[];
    metrics?: UserMetric | null;
}

export interface LoginResponse {
    token: string;
    user: AuthSessionUser;
}

export interface RegisterResponse {
    message: string;
    user: Pick<User, 'id' | 'name' | 'email'> & { roles: string[] };
}

export interface MeResponse extends AuthSessionUser {}

export interface RoleItem {
    name: ProjectMemberRole;
    label: string;
    permissions: string[];
}

export interface UserMetricsResponse {
    data: {
        assigned_tasks: number;
        completed_tasks: number;
        worked_hours: number;
        performance_score: number;
    };
}

export interface DashboardSummary {
    total_projects: number;
    my_pending_tasks: number;
    open_tickets: number;
}

export interface DashboardProjectItem extends Pick<Project, 'id' | 'name' | 'code' | 'status' | 'progress' | 'end_date'> {
    tasks_count?: number;
    tickets_count?: number;
}

export interface DashboardTaskItem extends Pick<Task, 'id' | 'title' | 'status' | 'priority' | 'due_date' | 'project_id'> {
    project?: Pick<Project, 'id' | 'name' | 'code'>;
}

export interface DashboardTicketItem extends Pick<Ticket, 'id' | 'subject' | 'priority' | 'project_id'> {
    created_at?: string;
    project?: Pick<Project, 'id' | 'name' | 'code'>;
}

export interface DashboardResponse {
    summary: DashboardSummary;
    projects: DashboardProjectItem[];
    my_tasks: DashboardTaskItem[];
    open_tickets: DashboardTicketItem[];
}

export interface ProjectMemberItem extends Pick<ProjectMember, 'id' | 'role'> {
    user?: User;
}

export interface ProjectPhaseItem extends Pick<ProjectPhase, 'id' | 'name' | 'start_date' | 'end_date' | 'progress'> {
    tasks_count?: number;
}

export interface ProjectPlanItem extends ProjectPlan {}

export interface ObjectiveItem extends Pick<Objective, 'id' | 'type' | 'title' | 'description' | 'completed'> {}

export interface MilestoneItem extends Pick<Milestone, 'id' | 'title' | 'target_date' | 'completed'> {}

export interface DeliverableItem extends Pick<Deliverable, 'id' | 'name' | 'description' | 'delivery_date' | 'approved'> {}

export interface RiskItem extends Pick<Risk, 'id' | 'title' | 'description' | 'impact' | 'probability' | 'mitigation_plan'> {
    created_at?: string;
}

export interface BlockerItem extends Pick<Blocker, 'id' | 'title' | 'description' | 'severity' | 'resolved'> {
    task?: Pick<Task, 'id' | 'title'>;
    created_at?: string;
}

export interface TaskCommentItem extends Pick<TaskComment, 'id' | 'comment'> {
    user?: User;
    created_at: string;
}

export interface TaskTimeLogItem extends Pick<TaskTimeLog, 'id' | 'minutes' | 'description'> {
    hours: number;
    user?: User;
    created_at: string;
}

export interface BlockerResolutionResponse {
    data: Blocker;
}

export interface TaskAttachmentItem extends Pick<TaskAttachment, 'id' | 'file_name' | 'file_path' | 'mime_type'> {
    user?: User;
}

export interface ProjectSummaryItem extends Pick<Project, 'id' | 'name' | 'code' | 'status' | 'progress' | 'start_date' | 'end_date' | 'budget'> {
    owner?: User;
    tasks_count?: number;
    tickets_count?: number;
    risks_count?: number;
    blockers_count?: number;
}

export interface ProjectResourceItem extends Project {}

export interface TaskResourceItem extends Task {}

export interface TicketResourceItem extends Ticket {}

export interface UserResourceItem extends User {}

export interface PaginationQuery {
    page?: number;
}

export interface UserListQuery extends PaginationQuery {
    role?: string;
    search?: string;
}

export interface TaskListQuery extends PaginationQuery {
    status?: TaskStatus;
    assigned_to?: number;
    priority?: TaskPriority;
}

export interface TicketListQuery extends PaginationQuery {
    status?: TicketStatus;
    priority?: TicketPriority;
}

export interface BlockerListQuery {
    include_resolved?: boolean;
}

export interface ProjectListQuery extends PaginationQuery {
    search?: string;
    status?: ProjectStatus;
}

export interface ProjectPayload extends Partial<Project> {}

export interface UserPayload extends Partial<User> {
    password?: string;
    password_confirmation?: string;
    role?: string;
}

export interface TaskPayload extends Partial<Task> {}

export interface TicketPayload extends Partial<Ticket> {}

export interface ProjectMemberPayload {
    user_id: number;
    role: ProjectMemberRole;
}

export interface ProjectPhasePayload extends Partial<ProjectPhase> {}

export interface ProjectPlanPayload extends Partial<ProjectPlan> {}

export interface ObjectivePayload extends Partial<Objective> {}

export interface MilestonePayload extends Partial<Milestone> {}

export interface DeliverablePayload extends Partial<Deliverable> {}

export interface RiskPayload extends Partial<Risk> {}

export interface BlockerPayload extends Partial<Blocker> {}

export interface TaskCommentPayload {
    comment: string;
}

export interface TaskTimeLogPayload {
    minutes: number;
    description?: string;
}
