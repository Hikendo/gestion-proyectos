import axios from 'axios';

export const apiWithToken = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || '/api/v1',
    headers: { Accept: 'application/json' },
});

apiWithToken.interceptors.request.use((config) => {
    const token = getAuthToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export type QueryValue = string | number | boolean | null | undefined;
export type QueryParams = Record<string, QueryValue>;
export type FieldErrors<TField extends string = string> = Partial<Record<TField, string[]>>;

export interface ValidationErrorResponse<TField extends string = string> {
    message: string;
    errors: FieldErrors<TField>;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    links: PaginationLink[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

export interface CollectionResponse<T> {
    data: T[];
}

export interface PaginatedResponse<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

export interface ResourceResponse<T> {
    data: T;
}

export interface MessageResponse {
    message: string;
}

export class ApiError extends Error {
    readonly status: number;
    readonly body: unknown;

    constructor(status: number, body: unknown) {
        super(extractErrorMessage(body, `API request failed with status ${status}`));
        this.name = 'ApiError';
        this.status = status;
        this.body = body;
    }
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function extractErrorMessage(body: unknown, fallback = 'Ha ocurrido un error inesperado.'): string {
    if (isRecord(body) && typeof body.message === 'string' && body.message.length > 0) {
        return body.message;
    }

    return fallback;
}

function isStringArray(value: unknown): value is string[] {
    return Array.isArray(value) && value.every((item) => typeof item === 'string');
}

export function isValidationErrorResponse<TField extends string = string>(
    value: unknown,
): value is ValidationErrorResponse<TField> {
    if (!isRecord(value) || typeof value.message !== 'string' || !isRecord(value.errors)) {
        return false;
    }

    return Object.values(value.errors).every((fieldErrors) => isStringArray(fieldErrors));
}

export function isApiError(error: unknown): error is ApiError {
    return error instanceof ApiError;
}

export function createFieldErrors<TField extends string>(fields: readonly TField[]): FieldErrors<TField> {
    return fields.reduce<FieldErrors<TField>>((accumulator, field) => {
        accumulator[field] = [];
        return accumulator;
    }, {});
}

export function getApiErrorMessage(error: unknown, fallback = 'Ha ocurrido un error inesperado.'): string {
    if (isApiError(error)) {
        return extractErrorMessage(error.body, error.message || fallback);
    }

    if (error instanceof Error && error.message) {
        return error.message;
    }

    return fallback;
}

export function getApiValidationErrors<TField extends string>(
    error: unknown,
    fields: readonly TField[] = [],
): FieldErrors<TField> {
    const defaults = createFieldErrors(fields);

    if (isApiError(error) && isValidationErrorResponse<TField>(error.body)) {
        return {
            ...defaults,
            ...error.body.errors,
        };
    }

    return defaults;
}

const authTokenKey = 'gestion_proyectos_auth_token';

export function getAuthToken(): string | null {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    return localStorage.getItem(authTokenKey);
}

export function setAuthToken(token: string): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    localStorage.setItem(authTokenKey, token);
}

export function clearAuthToken(): void {
    if (typeof localStorage === 'undefined') {
        return;
    }

    localStorage.removeItem(authTokenKey);
}

export function buildQueryString(params?: QueryParams): string {
    if (!params) {
        return '';
    }

    const searchParams = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        searchParams.set(key, String(value));
    });

    const queryString = searchParams.toString();

    return queryString ? `?${queryString}` : '';
}

export function getApiBaseUrl(): string {
    return import.meta.env.VITE_API_BASE_URL || '/api/v1';
}

function getApiTimeoutMs(): number {
    const rawTimeout = Number(import.meta.env.VITE_API_TIMEOUT_MS ?? 15000);

    if (!Number.isFinite(rawTimeout) || rawTimeout <= 0) {
        return 15000;
    }

    return rawTimeout;
}

async function parseResponseBody(response: Response): Promise<unknown> {
    if (response.status === 204) {
        return undefined;
    }

    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    return response.text();
}

export async function requestJson<T>(path: string, options: RequestInit & { query?: QueryParams } = {}): Promise<T> {
    const { query, headers, body, ...requestOptions } = options;
    const requestHeaders = new Headers(headers || {});

    requestHeaders.set('Accept', 'application/json');

    const token = getAuthToken();

    if (token) {
        requestHeaders.set('Authorization', `Bearer ${token}`);
    }

    let requestBody = body;

    if (
        requestBody !== undefined &&
        requestBody !== null &&
        typeof requestBody !== 'string' &&
        !(requestBody instanceof FormData) &&
        !(requestBody instanceof Blob) &&
        !(requestBody instanceof ArrayBuffer) &&
        !(requestBody instanceof URLSearchParams)
    ) {
        requestHeaders.set('Content-Type', 'application/json');
        requestBody = JSON.stringify(requestBody);
    }

    const controller = new AbortController();
    const timeoutId: ReturnType<typeof setTimeout> = setTimeout(() => {
        controller.abort();
    }, getApiTimeoutMs());

    let response: Response;

    try {
        response = await fetch(`${getApiBaseUrl()}${path}${buildQueryString(query)}`, {
            ...requestOptions,
            headers: requestHeaders,
            body: requestBody,
            signal: controller.signal,
        });
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') {
            throw new Error('La solicitud tardo demasiado. Verifica la conexion con la API e intenta de nuevo.');
        }

        throw error;
    } finally {
        clearTimeout(timeoutId);
    }

    const payload = await parseResponseBody(response);

    if (!response.ok) {
        throw new ApiError(response.status, payload);
    }

    return payload as T;
}
