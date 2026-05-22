import type { BaseModel } from './ModelBaseI';
import type { User } from './user';

export interface UserMetric extends BaseModel {
    user_id: number;
    assigned_tasks: number;
    completed_tasks: number;
    worked_minutes: number;
    performance_score: number;
    user?: User;
}
