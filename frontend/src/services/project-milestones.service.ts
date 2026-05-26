import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { MilestoneI } from "@/interfaces/MilestoneI";
import type { MilestonePayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface MilestonesResponseI extends ResponseBaseI {
  items: MilestoneI[];
}

export const index = async (projectId: number) => {
  try {
    const { data } = await apiWithToken.get<MilestonesResponseI>(`/projects/${projectId}/milestones`);
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

interface MilestoneResponseI extends ResponseBaseI {
  items: MilestoneI;
}

export const store = async (projectId: number, payload: MilestonePayload) => {
  try {
    const { data } = await apiWithToken.post<MilestoneResponseI>(`/projects/${projectId}/milestones`, payload);
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

export const update = async (projectId: number, milestoneId: number, payload: MilestonePayload) => {
  try {
    const { data } = await apiWithToken.put<MilestoneResponseI>(`/projects/${projectId}/milestones/${milestoneId}`, payload);
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

export const destroy = async (projectId: number, milestoneId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/milestones/${milestoneId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
