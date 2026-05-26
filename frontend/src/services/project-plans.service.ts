import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { ProjectPlanI } from "@/interfaces/ProjectPlanI";
import type { ProjectPlanPayload } from "./types";

// ─── Show ─────────────────────────────────────────────────────────────────────

interface PlanResponseI extends ResponseBaseI {
  items: ProjectPlanI | null;
}

export const show = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<PlanResponseI>(`/projects/${projectId}/plan`);
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};

// ─── Save ─────────────────────────────────────────────────────────────────────

interface SavePlanResponseI extends ResponseBaseI {
  items: ProjectPlanI;
}

export const save = async (projectId: number, payload: ProjectPlanPayload) => {
  try {
    const { data } = await apiWithToken.post<SavePlanResponseI>(`/projects/${projectId}/plan`, payload);
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
