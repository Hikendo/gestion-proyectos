interface LinkI {
  url: null | string;
  label: string;
  active: boolean;
}

export interface PaginacionScoutI {
  current_page: number;
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: LinkI[];
  next_page_url: null | string;
  path: string;
  per_page: number;
  prev_page_url: null | string;
  to: number;
  total: number;
}

export interface PaginacionScoutParamsI {
  page: number;
  query: string | undefined;
}

export interface PaginacionYQueryI {
  page: number;
  query: string | undefined;
  last_page: number | string;
}
