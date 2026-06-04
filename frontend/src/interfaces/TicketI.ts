import { AttachmentI } from "@/interfaces/AttachmentI";
import { ModelBaseI } from "@/interfaces/ModelBaseI";

import {
  TicketPriority,
  TicketStatus,
} from "@/interfaces/enums";

import { ProjectI } from "@/interfaces/ProjectI";
import { UserI } from "@/interfaces/UserI";

export interface TicketI extends ModelBaseI {
  project_id: number; // Required en Laravel

  created_by?: number | null; // Nullable en Laravel
  assigned_to?: number | null; // Nullable en Laravel

  subject: string; // Required en Laravel
  description?: string | null; // Nullable en Laravel

  status: TicketStatus; // Enum Laravel
  priority: TicketPriority; // Enum Laravel

  project?: ProjectI;

  creator?: UserI;
  assignee?: UserI;

  attachments?: AttachmentI[];
}

export interface TicketErroresFormI {
  project_id: string[]; // Required en Laravel

  created_by?: string[]; // Nullable en Laravel
  assigned_to?: string[]; // Nullable en Laravel

  subject: string[]; // Required en Laravel
  description?: string[]; // Nullable en Laravel

  status: string[]; // Required en Laravel
  priority: string[]; // Required en Laravel
}

export interface TicketAxiosErrorI {
  message: string;
  errors: TicketErroresFormI;
}
