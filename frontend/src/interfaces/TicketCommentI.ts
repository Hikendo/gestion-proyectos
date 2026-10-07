import { ModelBaseI } from "@/interfaces/ModelBaseI";

import { TicketI } from "@/interfaces/TicketI";
import { UserI } from "@/interfaces/UserI";

export interface TicketCommentI extends ModelBaseI {
  ticket_id: number; // Required en Laravel
  user_id: number; // Required en Laravel

  comment: string; // Required en Laravel

  ticket?: TicketI;
  user?: UserI;
}

export interface TicketCommentErroresFormI {
  ticket_id: string[]; // Required en Laravel
  user_id: string[]; // Required en Laravel

  comment: string[]; // Required en Laravel
}

export interface TicketCommentAxiosErrorI {
  message: string;
  errors: TicketCommentErroresFormI;
}
