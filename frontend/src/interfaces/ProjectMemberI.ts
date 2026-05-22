import { ModelBaseI } from "@/interfaces/ModelBaseI";
import { ProjectMemberRole } from "@/interfaces/enums";
import { ProjectI } from "@/interfaces/ProjectI";
import { UserI } from "@/interfaces/UserI";

export interface ProjectMemberI extends ModelBaseI {
  project_id: number; // Required en Laravel
  user_id: number; // Required en Laravel

  role: ProjectMemberRole; // Enum Laravel

  project?: ProjectI;
  user?: UserI;
}

export interface ProjectMemberErroresFormI {
  project_id: string[]; // Required en Laravel
  user_id: string[]; // Required en Laravel

  role: string[]; // Required en Laravel
}

export interface ProjectMemberAxiosErrorI {
  message: string;
  errors: ProjectMemberErroresFormI;
}
