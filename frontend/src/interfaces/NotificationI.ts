export interface NotificationI {
  id: number;
  user_id: number;
  title: string;
  body: string;
  type: string;
  data: Record<string, unknown> | null;
  status: string;
  sent_at: string | null;
  read_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface NotificationsPaginatedResponse {
  data: NotificationI[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}