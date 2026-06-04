export interface AttachmentI {
  id: number;
  uuid: string;
  original_name: string;
  disk_path: string;
  mime_type: string | null;
  size: number;
  download_url: string;
  created_at?: string | null;
  updated_at?: string | null;
  uploaded_by?: number;
}