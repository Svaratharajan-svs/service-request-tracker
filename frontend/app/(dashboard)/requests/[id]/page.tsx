
"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { useParams } from "next/navigation";

import {
  getRequest,
  getRequestHistory,
} from "@/lib/api/requests";

import type {
  RequestHistory,
  ServiceRequest,
} from "@/lib/types/request";

import RequestStatusBadge from "@/components/requests/RequestStatusBadge";
import StatusUpdate from "@/components/requests/StatusUpdate";
import RequestHistoryTimeline from "@/components/requests/RequestHistoryTimeline";

import styles from "./page.module.css";

export default function RequestDetailsPage() {
  const params = useParams<{ id: string }>();
  const id = Number(params.id);

  const [request, setRequest] = useState<ServiceRequest | null>(null);
  const [history, setHistory] = useState<RequestHistory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Fetch request details and history.
  const fetchData = useCallback(async () => {
    if (!Number.isInteger(id) || id <= 0) {
      setError("Invalid request ID.");
      setLoading(false);
      return;
    }

    setLoading(true);
    setError("");

    try {
      const [requestResult, historyResult] = await Promise.all([
        getRequest(id),
        getRequestHistory(id),
      ]);

      setRequest(requestResult);
      setHistory(historyResult);
    } catch {
      setError("Unable to load this request.");
    } finally {
      setLoading(false);
    }
  }, [id]);

  // Initial fetch.
  useEffect(() => {
    let cancelled = false;

    async function loadData() {
      if (!Number.isInteger(id) || id <= 0) {
        setError("Invalid request ID.");
        setLoading(false);
        return;
      }

      setLoading(true);
      setError("");

      try {
        const [requestResult, historyResult] = await Promise.all([
          getRequest(id),
          getRequestHistory(id),
        ]);

        if (cancelled) return;

        setRequest(requestResult);
        setHistory(historyResult);
      } catch {
        if (cancelled) return;

        setError("Unable to load this request.");
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    }

    void loadData();

    return () => {
      cancelled = true;
    };
  }, [id]);

  if (loading) {
    return (
      <div className={styles.page} aria-busy="true">
        <div className={`${styles.skeleton} ${styles.skeletonHeader}`} />
        <div className={`${styles.skeleton} ${styles.skeletonCard}`} />
        <div className={`${styles.skeleton} ${styles.skeletonCard}`} />
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.page}>
        <Link href="/requests" className={styles.back}>
          ← Back to requests
        </Link>

        <p role="alert" className={styles.error}>
          {error}
        </p>
      </div>
    );
  }

  if (!request) {
    return (
      <div className={styles.page}>
        <Link href="/requests" className={styles.back}>
          ← Back to requests
        </Link>

        <div className={styles.empty}>
          <p className={styles.emptyTitle}>Request not found</p>
          <p className={styles.emptyText}>
            It may have been removed or you may not have access to it.
          </p>
        </div>
      </div>
    );
  }

  return (
    <section className={styles.page}>
      <Link href="/requests" className={styles.back}>
        ← Back to requests
      </Link>

      <div className={styles.card}>
        <div className={styles.titleRow}>
          <h1 className={styles.title}>{request.title}</h1>
          <RequestStatusBadge status={request.status} />
        </div>

        <p className={styles.description}>{request.description}</p>

        <dl className={styles.meta}>
          <div className={styles.metaItem}>
            <dt>Request ID</dt>
            <dd>#{request.id}</dd>
          </div>

          <div className={styles.metaItem}>
            <dt>Version</dt>
            <dd>v{request.version}</dd>
          </div>

          <div className={styles.metaItem}>
            <dt>Created</dt>
            <dd>{new Date(request.created_at).toLocaleString()}</dd>
          </div>
        </dl>
      </div>

      <div className={styles.card}>
        <h2 className={styles.sectionTitle}>Update Status</h2>

        <StatusUpdate
          request={request}
          onUpdated={fetchData}
        />
      </div>

      <RequestHistoryTimeline history={history} />
    </section>
  );
}
