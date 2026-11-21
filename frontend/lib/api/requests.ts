
import { api } from "./client";

import type {
  CreateRequestInput,
  PaginatedResponse,
  RequestHistory,
  ServiceRequest,
  UpdateStatusInput,
} from "@/lib/types/request";

interface ResourceResponse<T> {
  data: T;
}

export async function getRequests(params?: {
  page?: number;
  per_page?: number;
  status?: string;
  search?: string;
  priority?:string
}) {
  const response = await api.get<PaginatedResponse<ServiceRequest>>(
    "/api/requests",
    { params }
  );

  return response.data;
}

export async function getRequest(id: number) {
  const response = await api.get<ResourceResponse<ServiceRequest>>(
    `/api/requests/${id}`
  );

  return response.data.data;
}

export async function createRequest(input: CreateRequestInput) {
  const response = await api.post<ResourceResponse<ServiceRequest>>(
    "/api/requests",
    input
  );

  return response.data.data;
}

export async function updateRequestStatus(
  id: number,
  input: UpdateStatusInput
) {
  const response = await api.patch<{
    success: boolean;
    message: string;
    data: ServiceRequest;
  }>(`/api/requests/${id}/status`, input);

  return response.data.data;
}

export async function getRequestHistory(id: number) {
  const response = await api.get<ResourceResponse<RequestHistory[]>>(
    `/api/requests/${id}/history`
  );

  return response.data.data;
}
