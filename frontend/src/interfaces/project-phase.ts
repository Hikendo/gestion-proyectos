import type { BaseModel, DateString } from './ModelBaseI';
import type { Project } from './project';
import type { Task } from './task';

export interface ProjectPhase extends BaseModel {
    project_id: number;
    name: string;
    start_date?: DateString | null;
    end_date?: DateString | null;
    progress: number;
    project?: Project;
    tasks?: Task[];
    tasks_count?: number;
}
