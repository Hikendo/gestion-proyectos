export interface ModelBaseI {
    id: number;
    created_at?: string | null;
    updated_at?: string | null;
}

export interface SoftDeletesModelI extends ModelBaseI {
    deleted_at?: string | null;
}

export type DateString = string;
export type DateTimeString = string;
