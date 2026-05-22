import type { BaseModel } from './ModelBaseI';
import type { Project } from './project';

export interface ProjectPlan extends BaseModel {
    project_id: number;
    scope?: string | null;
    requirements?: string | null;
    technical_notes?: string | null;
    project?: Project;
}
