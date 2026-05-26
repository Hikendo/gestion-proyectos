import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { ProjectPhaseI } from "@/interfaces/ProjectPhaseI";
import type { ProjectPhasePayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface PhasesResponseI extends ResponseBaseI {
  items: ProjectPhaseI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<PhasesResponseI>(`/projects/${projectId}/phases`);
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

interface PhaseResponseI extends ResponseBaseI {
  items: ProjectPhaseI;
}

export const store = async (projectId: number, payload: ProjectPhasePayload) => {
  try {
    const { data } = await apiWithToken.post<PhaseResponseI>(`/projects/${projectId}/phases`, payload);
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

export const update = async (projectId: number, phaseId: number, payload: ProjectPhasePayload) => {
  try {
    const { data } = await apiWithToken.put<PhaseResponseI>(`/projects/${projectId}/phases/${phaseId}`, payload);
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

export const destroy = async (projectId: number, phaseId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/phases/${phaseId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
