import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { PaginacionScoutI } from "@/interfaces/PaginacionScoutI";
import { ProjectI } from "@/interfaces/ProjectI";
import type { ProjectListQuery, ProjectPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface ProjectsItemsI extends PaginacionScoutI {
  data: ProjectI[];
}

interface ProjectsResponseI extends ResponseBaseI {
  items: ProjectsItemsI;
}

export const index = async (query?: ProjectListQuery) => {
  try {
    const { data } = await apiWithToken.get<ProjectsResponseI>("/projects", { params: query });
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Show ─────────────────────────────────────────────────────────────────────

interface ProjectResponseI extends ResponseBaseI {
  items: ProjectI;
}

export const show = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<ProjectResponseI>(`/projects/${projectId}`);
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

export const store = async (payload: ProjectPayload | FormData) => {
  try {
    const config = payload instanceof FormData
      ? { headers: { 'Content-Type': 'multipart/form-data' } }
      : {};

    const { data } = await apiWithToken.post<ProjectResponseI>("/projects", payload, config);
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

export const update = async (projectId: number, payload: ProjectPayload | FormData) => {
  try {
    const config = payload instanceof FormData
      ? { headers: { 'Content-Type': 'multipart/form-data' } }
      : {};

    // Laravel usa POST con _method=PUT para FormData
    const method = payload instanceof FormData ? 'post' : 'put';
    const url = `/projects/${projectId}`;

    if (payload instanceof FormData) {
      payload.append('_method', 'PUT');
      const { data } = await apiWithToken.post<ProjectResponseI>(url, payload, config);
      return { status: true, message: data.message, items: data.items };
    }

    const { data } = await apiWithToken.put<ProjectResponseI>(url, payload);
    return { status: true, message: data.message, items: data.items };
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

export const destroy = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Metrics ──────────────────────────────────────────────────────────────────

export const getMetrics = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get(`/projects/${projectId}/metrics`);
    return {
      status: true,
      message: data.message,
      items: data.items as ProjectMetricsResponseI,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor", items: null };
  }
};

export interface ProjectMetricsResponseI {
  project: {
    id: number; name: string; status: string; progress: number;
    start_date: string | null; end_date: string | null; budget: number | null;
    owner: { id: number; name: string; email: string } | null;
    members: { user_id: number; name: string; email: string; role: string }[];
  };
  tasks: {
    total: number; completed: number; in_progress: number; pending: number; blocked: number;
    by_status: { status: string; label: string; count: number }[];
    by_member: { user_id: number; name: string; role: string; total: number; completed: number; blocked: number }[];
  };
  tickets: {
    total: number; open: number; in_progress: number; resolved: number; closed: number;
    by_status: { status: string; label: string; count: number }[];
  };
  risks: {
    total: number; active: number; resolved: number;
    by_impact: { impact: string; label: string; count: number }[];
  };
  blockers: {
    total: number; active: number; resolved: number;
    by_severity: { severity: string; label: string; count: number }[];
    by_creator: { user_id: number; name: string; count: number }[];
  };
  objectives: {
    total: number; completed: number; pending: number;
    by_type: { type: string; total: number; completed: number }[];
  };
  milestones: { total: number; completed: number; pending: number };
  deliverables: { total: number; approved: number; pending: number };
}
