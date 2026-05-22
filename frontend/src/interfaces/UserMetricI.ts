import { ModelBaseI } from "@/interfaces/ModelBaseI";

import { UserI } from "@/interfaces/UserI";

export interface UserMetricI extends ModelBaseI {
  user_id: number; // Required en Laravel

  assigned_tasks: number; // Required en Laravel
  completed_tasks: number; // Required en Laravel
  worked_minutes: number; // Required en Laravel
  performance_score: number; // Required en Laravel

  user?: UserI;
}

export interface UserMetricErroresFormI {
  user_id: string[]; // Required en Laravel

  assigned_tasks: string[]; // Required en Laravel
  completed_tasks: string[]; // Required en Laravel
  worked_minutes: string[]; // Required en Laravel
  performance_score: string[]; // Required en Laravel
}

export interface UserMetricAxiosErrorI {
  message: string;
  errors: UserMetricErroresFormI;
}
