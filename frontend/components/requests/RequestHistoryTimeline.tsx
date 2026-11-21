import type { RequestHistory } from "@/lib/types/request";

import styles from "./RequestHistoryTimeline.module.css";

export default function RequestHistoryTimeline({
  history,
}: {
  history: RequestHistory[];
}) {
  if (history.length === 0) {
    return (
      <section className={styles.section}>
        <div className={styles.empty}>
          <p className={styles.emptyTitle}>No status history available</p>
          <p className={styles.emptyText}>
            Status changes will appear here once the request is updated.
          </p>
        </div>
      </section>
    );
  }

  return (
    <section className={styles.section}>
      <h2 className={styles.title}>Status History</h2>

      <ol className={styles.list}>
        {history.map((item, index) => (
          <li
            key={item.id}
            className={`${styles.item} ${index === 0 ? styles.latest : ""}`}
          >
            <span className={styles.dot} aria-hidden="true" />

            <div className={styles.card}>
              <div className={styles.cardHeader}>
                {item.from_status ? (
                  <p className={styles.change}>
                    <span className={styles.status}>{item.from_status}</span>
                    <span className={styles.arrow} aria-hidden="true">
                      →
                    </span>
                    <span className={`${styles.status} ${styles.statusTo}`}>
                      {item.to_status}
                    </span>
                  </p>
                ) : (
                  <p className={styles.change}>
                    <span className={styles.created}>Created as</span>
                    <span className={`${styles.status} ${styles.statusTo}`}>
                      {item.to_status}
                    </span>
                  </p>
                )}

                <span className={styles.version}>v{item.version}</span>
              </div>

              <div className={styles.meta}>
                <span>
                  Changed by{" "}
                  <strong className={styles.author}>
                    {item.changed_by.name}
                  </strong>
                </span>

                <time
                  className={styles.time}
                  dateTime={item.created_at}
                >
                  {new Date(item.created_at).toLocaleString()}
                </time>
              </div>
            </div>
          </li>
        ))}
      </ol>
    </section>
  );
}