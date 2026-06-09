import {
  DateString,
  SoftDeletesModelI,
} from "@/interfaces/ModelBaseI";

import { AttachmentI } from "@/interfaces/AttachmentI";
import { ProjectStatus } from "@/interfaces/enums";

import { BlockerI } from "@/interfaces/BlockerI";
import { DeliverableI } from "@/interfaces/DeliverableI";
import { MilestoneI } from "@/interfaces/MilestoneI";
import { ObjectiveI } from "@/interfaces/ObjectiveI";
import { ProjectMemberI } from "@/interfaces/ProjectMemberI";
import { ProjectMetricI } from "@/interfaces/ProjectMetricI";
import { ProjectPhaseI } from "@/interfaces/ProjectPhaseI";
import { ProjectPlanI } from "@/interfaces/ProjectPlanI";
import { RiskI } from "@/interfaces/RiskI";
import { TaskI } from "@/interfaces/TaskI";
import { TicketI } from "@/interfaces/TicketI";
import { UserI } from "@/interfaces/UserI";

export interface ProjectI extends SoftDeletesModelI {
  name: string; // Required en Laravel

  code?: string | null; // Nullable en Laravel
  description?: string | null; // Nullable en Laravel

  status: ProjectStatus; // Enum Laravel

  start_date?: DateString | null; // Nullable en Laravel
  end_date?: DateString | null; // Nullable en Laravel

  budget?: number | null; // Nullable en Laravel
  progress?: number | null; // Nullable en Laravel

  owner_id: number; // Required en Laravel

  owner?: UserI;

  members?: ProjectMemberI[];
  phases?: ProjectPhaseI[];
  objectives?: ObjectiveI[];

  tasks?: TaskI[];
  tickets?: TicketI[];
  risks?: RiskI[];

  blockers?: BlockerI[];
  deliverables?: DeliverableI[];

  plans?: ProjectPlanI[];
  milestones?: MilestoneI[];

  metrics?: ProjectMetricI | null;

  tasks_count?: number;
  tickets_count?: number;
  risks_count?: number;
  blockers_count?: number;

  attachments?: AttachmentI[];
}

export interface ProjectErroresFormI {
  name: string[]; // Required en Laravel

  code?: string[]; // Nullable en Laravel
  description?: string[]; // Nullable en Laravel

  status: string[]; // Required en Laravel

  start_date?: string[]; // Nullable en Laravel
  end_date?: string[]; // Nullable en Laravel

  budget?: string[]; // Nullable en Laravel
  progress?: string[]; // Nullable en Laravel

  owner_id: string[]; // Required en Laravel
}

export interface ProjectAxiosErrorI {
  message: string;
  errors: ProjectErroresFormI;
}
