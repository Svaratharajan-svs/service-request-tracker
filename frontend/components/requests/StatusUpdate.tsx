"use client";

import { useState } from "react";
import axios from "axios";

import { updateRequestStatus } from "@/lib/api/requests";

import type {
  RequestStatus,
  ServiceRequest,
} from "@/lib/types/request";

import styles from "./StatusUpdate.module.css";

export default function StatusUpdate({
  request,
  onUpdated,
}: {
  request: ServiceRequest;
  onUpdated: (updated: ServiceRequest) => void;
}) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [conflict, setConflict] = useState(false);

  const nextStatus: Partial<Record<RequestStatus, RequestStatus>> = {
    OPEN: "IN_PROGRESS",
    IN_PROGRESS: "RESOLVED",
  };

  const targetStatus = nextStatus[request.status];

  async function handleUpdate() {
    if (!targetStatus) return;

    setLoading(true);
    setError("");
    setConflict(false);

    try {
      const result = await updateRequestStatus(request.id, {
        status: targetStatus,
        expected_version: request.version,
      });

      // Adapt this extraction to the actual API response.
      onUpdated(result);
    } catch (err) {
      if (
        axios.isAxiosError(err) &&
        err.response?.status === 409
      ) {
        setConflict(true);
        setError(
          "This request was updated elsewhere. Reload the latest version before trying again."
        );
      } else if (
        axios.isAxiosError(err) &&
        err.response?.status === 422
      ) {
        setError("This status transition is not allowed.");
      } else {
        setError("Unable to update request status.");
      }
    } finally {
      setLoading(false);
    }
  }

  if (!targetStatus) {
    return (
      <div className={styles.done}>
        <span className={styles.doneIcon} aria-hidden="true">
          ✓
        </span>
        <p className={styles.doneText}>
          This request is resolved. No further updates are allowed.
        </p>
      </div>
    );
  }

  return (
    <div className={styles.wrapper}>
      <div className={styles.row}>
        <div className={styles.info}>
          <p className={styles.version}>
            Current version <strong>v{request.version}</strong>
          </p>

          <p className={styles.transition}>
            <span className={styles.pill}>
              {request.status.replace("_", " ")}
            </span>
            <span className={styles.arrow} aria-hidden="true">
              →
            </span>
            <span className={`${styles.pill} ${styles.pillTarget}`}>
              {targetStatus.replace("_", " ")}
            </span>
          </p>
        </div>

        <button
          className={styles.primary}
          onClick={handleUpdate}
          disabled={loading}
        >
          {loading && <span className={styles.spinner} aria-hidden="true" />}
          {loading
            ? "Updating..."
            : `Move to ${targetStatus.replace("_", " ")}`}
        </button>
      </div>

      {error && (
        <p role="alert" className={styles.error}>
          {error}
        </p>
      )}

      {conflict && (
        <button
          type="button"
          className={styles.secondary}
          onClick={() => window.location.reload()}
        >
          Reload latest request
        </button>
      )}
    </div>
  );
}