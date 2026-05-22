import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";

export interface ProjectMetricI extends ModelBaseI {
  project_id: number; // Required en Laravel

  total_tasks: number; // Required en Laravel
  completed_tasks: number; // Required en Laravel
  open_tickets: number; // Required en Laravel
  total_blockers: number; // Required en Laravel
  completion_rate: number; // Required en Laravel

  project?: ProjectI;
}

export interface ProjectMetricErroresFormI {
  project_id: string[]; // Required en Laravel

  total_tasks: string[]; // Required en Laravel
  completed_tasks: string[]; // Required en Laravel
  open_tickets: string[]; // Required en Laravel
  total_blockers: string[]; // Required en Laravel
  completion_rate: string[]; // Required en Laravel
}

export interface ProjectMetricAxiosErrorI {
  message: string;
  errors: ProjectMetricErroresFormI;
}
