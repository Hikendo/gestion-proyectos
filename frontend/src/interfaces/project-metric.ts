import type { BaseModel } from './ModelBaseI';
import type { Project } from './project';

export interface ProjectMetric extends BaseModel {
    project_id: number;
    total_tasks: number;
    completed_tasks: number;
    open_tickets: number;
    total_blockers: number;
    completion_rate: number;
    project?: Project;
}
