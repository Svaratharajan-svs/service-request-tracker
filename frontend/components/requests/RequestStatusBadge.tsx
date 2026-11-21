import type { RequestStatus } from "@/lib/types/request";

import styles from "./RequestStatusBadge.module.css";

const statusClasses: Record<RequestStatus, string> = {
  OPEN: styles.open,
  IN_PROGRESS: styles.progress,
  RESOLVED: styles.resolved,
};

export default function RequestStatusBadge({
  status,
}: {
  status: RequestStatus;
}) {
  return (
    <span className={`${styles.badge} ${statusClasses[status]}`}>
      <span className={styles.dot} aria-hidden="true" />
      {status.replace("_", " ")}
    </span>
  );
}