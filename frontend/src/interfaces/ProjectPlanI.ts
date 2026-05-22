import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { ProjectI } from "@/interfaces/ProjectI";

export interface ProjectPlanI extends ModelBaseI {
  project_id: number; // Required en Laravel

  scope?: string | null; // Nullable en Laravel
  requirements?: string | null; // Nullable en Laravel
  technical_notes?: string | null; // Nullable en Laravel

  project?: ProjectI;
}

export interface ProjectPlanErroresFormI {
  project_id: string[]; // Required en Laravel

  scope?: string[]; // Nullable en Laravel
  requirements?: string[]; // Nullable en Laravel
  technical_notes?: string[]; // Nullable en Laravel
}

export interface ProjectPlanAxiosErrorI {
  message: string;
  errors: ProjectPlanErroresFormI;
}
