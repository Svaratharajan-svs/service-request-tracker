import styles from "./RequestPriorityBadge.module.css";

const LABELS = {
  LOW: "Low",
  MEDIUM: "Medium",
  HIGH: "High",
} as const;

export type Priority = keyof typeof LABELS;

export default function RequestPriorityBadge({
  priority,
}: {
  priority: Priority;
}) {
  return (
    <span className={`${styles.badge} ${styles[priority.toLowerCase()]}`}>
      <span className={styles.dot} aria-hidden="true" />
      {LABELS[priority] ?? priority}
    </span>
  );
}