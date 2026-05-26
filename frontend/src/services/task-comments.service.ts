import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { TaskCommentI } from "@/interfaces/TaskCommentI";
import type { TaskCommentPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface CommentsResponseI extends ResponseBaseI {
  items: TaskCommentI[];
}

export const index = async (taskId: number) => {
  try {
    const { data } = await apiWithToken.get<CommentsResponseI>(`/tasks/${taskId}/comments`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Store ────────────────────────────────────────────────────────────────────

interface CommentResponseI extends ResponseBaseI {
  items: TaskCommentI;
}

export const store = async (taskId: number, payload: TaskCommentPayload) => {
  try {
    const { data } = await apiWithToken.post<CommentResponseI>(`/tasks/${taskId}/comments`, payload);
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

export const destroy = async (taskId: number, commentId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/tasks/${taskId}/comments/${commentId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
