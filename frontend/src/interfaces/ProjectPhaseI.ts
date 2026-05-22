import { ModelBaseI, DateString } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";
import { TaskI } from "@/interfaces/TaskI";

export interface ProjectPhaseI extends ModelBaseI {
  project_id: number; // Required en Laravel

  name: string; // Required en Laravel

  start_date?: DateString | null; // Nullable en Laravel
  end_date?: DateString | null; // Nullable en Laravel

  progress: number; // Required en Laravel

  project?: ProjectI;

  tasks?: TaskI[];
  tasks_count?: number;
}

export interface ProjectPhaseErroresFormI {
  project_id: string[]; // Required en Laravel

  name: string[]; // Required en Laravel

  start_date?: string[]; // Nullable en Laravel
  end_date?: string[]; // Nullable en Laravel

  progress: string[]; // Required en Laravel
}

export interface ProjectPhaseAxiosErrorI {
  message: string;
  errors: ProjectPhaseErroresFormI;
}
