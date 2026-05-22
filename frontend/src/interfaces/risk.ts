import type { BaseModel } from './ModelBaseI';
import type { RiskImpact, RiskProbability } from './enums';
import type { Project } from './project';

export interface Risk extends BaseModel {
    project_id: number;
    title: string;
    description?: string | null;
    impact: RiskImpact;
    probability: RiskProbability;
    mitigation_plan?: string | null;
    project?: Project;
}
