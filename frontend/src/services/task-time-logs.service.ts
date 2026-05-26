import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { TaskTimeLogI } from "@/interfaces/TaskTimeLogI";
import type { TaskTimeLogPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface TimeLogsResponseI extends ResponseBaseI {
  items: TaskTimeLogI[];
}

export const index = async (taskId: number) => {
  try {
    const { data } = await apiWithToken.get<TimeLogsResponseI>(`/tasks/${taskId}/time-logs`);
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

interface TimeLogResponseI extends ResponseBaseI {
  items: TaskTimeLogI;
}

export const store = async (taskId: number, payload: TaskTimeLogPayload) => {
  try {
    const { data } = await apiWithToken.post<TimeLogResponseI>(`/tasks/${taskId}/time-logs`, payload);
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
