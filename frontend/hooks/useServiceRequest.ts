
"use client";

import { useEffect, useState } from "react";

import { getRequests } from "@/lib/api/requests";

import type {
  PaginatedResponse,
  ServiceRequest,
} from "@/lib/types/request";

interface UseServiceRequestsParams {
  page: number;
  status: string;
  priority:string;
  search: string;
}

export function useServiceRequests({
  page,
  status,
  priority,
  search,
}: UseServiceRequestsParams) {
  const [requests, setRequests] = useState<ServiceRequest[]>([]);
  const [lastPage, setLastPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;

    const fetchRequests = async () => {
      setLoading(true);
      setError("");

      try {
        const result: PaginatedResponse<ServiceRequest> =
          await getRequests({
            page,
            per_page: 10,
            status: status || undefined,
            search: search || undefined,
            priority :priority || undefined
          });

        if (!active) return;

        setRequests(result.data);
        setLastPage(result.last_page);
      } catch {
        if (!active) return;

        setError("Unable to load service requests.");
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void fetchRequests();

    return () => {
      active = false;
    };
  }, [page, status, search,priority]);

  return {
    requests,
    lastPage,
    loading,
    error,
  };
}
