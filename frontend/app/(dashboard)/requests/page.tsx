"use client";

import Link from "next/link";
import { useState } from "react";

import { useServiceRequests } from "@/hooks/useServiceRequest";
import { useDebounce } from "@/hooks/useDebounce";

import RequestStatusBadge from "@/components/requests/RequestStatusBadge";
import RequestPriorityBadge from "@/components/requests/RequestPriorityBadge";

import styles from "./requests.module.css";
import BadgeSelect, { BadgeSelectOption } from "@/components/requests/BadgeSelect";

export default function RequestsPage() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState("");
  const [priority, setPriority] = useState("");
  const [search, setSearch] = useState("");

  // Wait 300ms after the user stops typing
  const debouncedSearch = useDebounce(search, 300);
  const STATUS_OPTIONS: BadgeSelectOption[] = [
    { value: "", label: "All statuses" },
    {
      value: "OPEN",
      label: "Open",
      content: <RequestStatusBadge status="OPEN" />,
    },
    {
      value: "IN_PROGRESS",
      label: "In Progress",
      content: <RequestStatusBadge status="IN_PROGRESS" />,
    },
    {
      value: "RESOLVED",
      label: "Resolved",
      content: <RequestStatusBadge status="RESOLVED" />,
    },
  ];

  const PRIORITY_OPTIONS: BadgeSelectOption[] = [
    { value: "", label: "All priorities" },
    {
      value: "LOW",
      label: "Low",
      content: <RequestPriorityBadge priority="LOW" />,
    },
    {
      value: "MEDIUM",
      label: "Medium",
      content: <RequestPriorityBadge priority="MEDIUM" />,
    },
    {
      value: "HIGH",
      label: "High",
      content: <RequestPriorityBadge priority="HIGH" />,
    },
  ];

  const { requests, lastPage, loading, error } = useServiceRequests({
    page,
    status,
    priority,
    search: debouncedSearch,
  });

  return (
    <section className={styles.page}>
      <div className={styles.heading}>
        <div>
          <h1 className={styles.title}>My Service Requests</h1>
          <p className={styles.subtitle}>
            View and manage your service requests.
          </p>
        </div>

        <Link href="/requests/new" className={styles.createButton}>
          <span aria-hidden="true">+</span> Create Request
        </Link>
      </div>

      <div className={styles.filters}>
        <input
          className={styles.searchInput}
          placeholder="Search requests..."
          value={search}
          onChange={(event) => {
            setSearch(event.target.value);
            setPage(1);
          }}
        />

        <BadgeSelect
          ariaLabel="Filter by status"
          value={status}
          options={STATUS_OPTIONS}
          onChange={(value) => {
            setStatus(value);
            setPage(1);
          }}
        />

        <BadgeSelect
          ariaLabel="Filter by priority"
          value={priority}
          options={PRIORITY_OPTIONS}
          onChange={(value) => {
            setPriority(value);
            setPage(1);
          }}
        />
      </div>

      {error && (
        <p role="alert" className={styles.error}>
          {error}
        </p>
      )}

      {!error && (
        <div className={styles.card}>
          {loading && (
            <div aria-busy="true" aria-label="Loading requests">
              {Array.from({ length: 5 }).map((_, index) => (
                <p key={index} className={styles.skeletonRow} />
              ))}
            </div>
          )}

          {!loading && requests.length === 0 && (
            <div className={styles.state}>
              <p className={styles.stateTitle}>No service requests found</p>
              <p className={styles.stateText}>
                Try adjusting your search or filters, or create a new request.
              </p>
            </div>
          )}

          {!loading && requests.length > 0 && (
            <div className={styles.tableWrap}>
              <table className={styles.table}>
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Version</th>
                    <th>Created</th>
                    <th>Action</th>
                  </tr>
                </thead>

                <tbody>
                  {requests.map((request) => (
                    <tr key={request.id}>
                      <td className={styles.requestTitle}>{request.title}</td>

                      <td>
                        <RequestPriorityBadge priority={request.priority} />
                      </td>

                      <td>
                        <RequestStatusBadge status={request.status} />
                      </td>

                      <td>
                        <span className={styles.versionTag}>
                          v{request.version}
                        </span>
                      </td>

                      <td className={styles.muted}>
                        {new Date(request.created_at).toLocaleDateString()}
                      </td>

                      <td>
                        <Link
                          href={`/requests/${request.id}`}
                          className={styles.viewLink}
                        >
                          View
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      <div className={styles.pagination}>
        <button
          className={styles.pageButton}
          disabled={page <= 1 || loading}
          onClick={() => setPage((current) => current - 1)}
        >
          ← Previous
        </button>

        <span className={styles.pageInfo}>
          Page <strong>{page}</strong> of <strong>{lastPage}</strong>
        </span>

        <button
          className={styles.pageButton}
          disabled={page >= lastPage || loading}
          onClick={() => setPage((current) => current + 1)}
        >
          Next →
        </button>
      </div>
    </section>
  );
}