import { apiWithToken } from "@/services/http";
import { ResponseBaseI } from "@/interfaces/ResponseBaseI";
import type { DashboardResponse } from "./types";

// ─── Get ──────────────────────────────────────────────────────────────────────

interface DashboardResponseI extends ResponseBaseI {
  items: DashboardResponse;
}

export const get = async () => {
  try {
    const { data } = await apiWithToken.get<DashboardResponseI>("/dashboard");
    return {
      status: true,
      message: data.message,
      items: data.items,
    };
  } catch (error) {
    return { status: false, message: "Error en el servidor" };
  }
};
