export type RequestStatus =
  | "OPEN"
  | "IN_PROGRESS"
  | "RESOLVED";
export type Priority ="LOW" | "MEDIUM" | "HIGH"

export interface ServiceRequest {
  id: number;
  title: string;
  description: string;
  status: RequestStatus;
  version: number;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    name: string;
  };
  priority:Priority
}

export interface RequestHistory {
  id: number;
  from_status: RequestStatus | null;
  to_status: RequestStatus;
  version: number;
  changed_by: {
    id: number;
    name: string;
  };
  created_at: string;
}

export interface CreateRequestInput {
  title: string;
  description: string;
  priority: Priority
}

export interface UpdateStatusInput {
  status: RequestStatus;
  expected_version: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}