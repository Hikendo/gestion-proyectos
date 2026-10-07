import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { TicketCommentI } from "@/interfaces/TicketCommentI";
import type { TicketCommentPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface CommentsResponseI extends ResponseBaseI {
  items: TicketCommentI[];
}

export const index = async (projectId: number, ticketId: number) => {
  try {
    const { data } = await apiWithToken.get<CommentsResponseI>(
      `/projects/${projectId}/tickets/${ticketId}/comments`
    );
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor", items: [] as TicketCommentI[] };
  }
};

// ─── Store ────────────────────────────────────────────────────────────────────

interface CommentResponseI extends ResponseBaseI {
  items: TicketCommentI;
}

export const store = async (projectId: number, ticketId: number, payload: TicketCommentPayload) => {
  try {
    const { data } = await apiWithToken.post<CommentResponseI>(
      `/projects/${projectId}/tickets/${ticketId}/comments`,
      payload
    );
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    const err = error as AxiosError;
    if (err?.response?.status === 422) {
      return {
        status: false,
        message: "Llena correctamente el formulario",
        errors: (err.response.data as any)?.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Destroy ──────────────────────────────────────────────────────────────────

export const destroy = async (projectId: number, ticketId: number, commentId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(
      `/projects/${projectId}/tickets/${ticketId}/comments/${commentId}`
    );
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
