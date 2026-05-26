import { AxiosError } from "axios";
import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import { PaginacionScoutI } from "@/interfaces/PaginacionScoutI";
import { TicketI } from "@/interfaces/TicketI";
import type { TicketListQuery, TicketPayload } from "./types";

// ─── Index ────────────────────────────────────────────────────────────────────

interface TicketsItemsI extends PaginacionScoutI {
  data: TicketI[];
}

interface TicketsResponseI extends ResponseBaseI {
  items: TicketsItemsI;
}

export const index = async (projectId: number, query?: TicketListQuery) => {
  try {
    const { data } = await apiWithToken.get<TicketsResponseI>(
      `/projects/${projectId}/tickets`,
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

// ─── Show ─────────────────────────────────────────────────────────────────────

interface TicketResponseI extends ResponseBaseI {
  items: TicketI;
}

export const show = async (projectId: number, ticketId: number) => {
  try {
    const { data } = await apiWithToken.get<TicketResponseI>(`/projects/${projectId}/tickets/${ticketId}`);
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

export const store = async (projectId: number, payload: TicketPayload) => {
  try {
    const { data } = await apiWithToken.post<TicketResponseI>(`/projects/${projectId}/tickets`, payload);
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

export const update = async (projectId: number, ticketId: number, payload: TicketPayload) => {
  try {
    const { data } = await apiWithToken.put<TicketResponseI>(`/projects/${projectId}/tickets/${ticketId}`, payload);
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

export const destroy = async (projectId: number, ticketId: number) => {
  try {
    const { data } = await apiWithToken.delete<ResponseBaseI>(`/projects/${projectId}/tickets/${ticketId}`);
    return {
      status: true,
      message: data.message,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
