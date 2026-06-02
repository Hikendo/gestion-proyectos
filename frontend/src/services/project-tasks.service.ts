import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { PaginacionScoutI } from "@/interfaces/PaginacionScoutI";
import { TaskI } from "@/interfaces/TaskI";
import type { TaskListQuery, TaskPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface TasksItemsI extends PaginacionScoutI {
  data: TaskI[];
}

interface TasksResponseI extends ResponseBaseI {
  items: TasksItemsI;
}

export const index = async (projectId: number, query?: TaskListQuery) => {
  try {
    const { data } = await apiWithToken.get<TasksResponseI>(
      `/projects/${projectId}/tasks`,
      { params: query }
    );
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Active (sin completadas, para selects) ───────────────────────────────────

interface TasksActiveResponseI extends ResponseBaseI {
  items: Pick<TaskI, 'id' | 'title' | 'status' | 'priority'>[];
}

export const active = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<TasksActiveResponseI>(
      `/projects/${projectId}/tasks/active`
    );
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor", items: [] as Pick<TaskI, 'id' | 'title' | 'status' | 'priority'>[] };
  }
};

// ─── Show ─────────────────────────────────────────────────────────────────────

interface TaskResponseI extends ResponseBaseI {
  items: TaskI;
}

export const show = async (projectId: number, taskId: number) => {
  try {
    const { data } = await apiWithToken.get<TaskResponseI>(`/projects/${projectId}/tasks/${taskId}`);
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

export const store = async (projectId: number, payload: TaskPayload) => {
  try {
    const { data } = await apiWithToken.post<TaskResponseI>(`/projects/${projectId}/tasks`, payload);
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

// ─── Update ───────────────────────────────────────────────────────────────────

export const update = async (projectId: number, taskId: number, payload: TaskPayload) => {
  try {
    const { data } = await apiWithToken.put<TaskResponseI>(`/projects/${projectId}/tasks/${taskId}`, payload);
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
        message: (err.response.data as any)?.message || "Llena correctamente el formulario",
        errors: (err.response.data as any)?.errors,
      };
    }
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Destroy ──────────────────────────────────────────────────────────────────

export const destroy = async (projectId: number, taskId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/tasks/${taskId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
