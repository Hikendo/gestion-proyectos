import { ModelBaseI, DateString } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";

export interface MilestoneI extends ModelBaseI {
  project_id: number; // Required en Laravel

  title: string; // Required en Laravel
  target_date?: DateString | null; // Nullable en Laravel

  completed: boolean; // Required en Laravel

  project?: ProjectI;
}

export interface MilestoneErroresFormI {
  project_id: string[]; // Required en Laravel

  title: string[]; // Required en Laravel
  target_date?: string[]; // Nullable en Laravel

  completed: string[]; // Required en Laravel
}

export interface MilestoneAxiosErrorI {
  message: string;
  errors: MilestoneErroresFormI;
}
