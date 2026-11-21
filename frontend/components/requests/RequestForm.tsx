"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";

import { createRequest } from "@/lib/api/requests";
import { Priority } from "@/lib/types/request";

import BadgeSelect, {
  BadgeSelectOption,
} from "@/components/requests/BadgeSelect";
import RequestPriorityBadge from "@/components/requests/RequestPriorityBadge";

import styles from "./RequestForm.module.css";

const TITLE_MAX = 150;
const DESCRIPTION_MAX = 5000;

const PRIORITY_OPTIONS: BadgeSelectOption[] = [
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

export default function RequestForm() {
  const router = useRouter();

  const [title, setTitle] = useState("");
  const [priority, setPriority] = useState<Priority>("MEDIUM");
  const [description, setDescription] = useState("");

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    setLoading(true);
    setError("");

    try {
      await createRequest({ title, priority, description });
      setTitle("");
      setPriority("MEDIUM");
      setDescription("");
      router.push("/requests");
      router.refresh();
    } catch {
      setError("Unable to create the request. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className={styles.form}>
      <div className={styles.field}>
        <div className={styles.labelRow}>
          <label htmlFor="title" className={styles.label}>
            Title
          </label>
          <span className={styles.counter}>
            {title.length}/{TITLE_MAX}
          </span>
        </div>

        <input
          id="title"
          className={styles.input}
          placeholder="Short summary of the issue"
          value={title}
          onChange={(event) => setTitle(event.target.value)}
          minLength={5}
          maxLength={TITLE_MAX}
          required
        />

        <p className={styles.hint}>At least 5 characters.</p>
      </div>

      <div className={styles.field}>
        <div className={styles.labelRow}>
          <label htmlFor="priority" className={styles.label}>
            Priority
          </label>
        </div>

        <BadgeSelect
          id="priority"
          ariaLabel="Priority"
          value={priority}
          options={PRIORITY_OPTIONS}
          onChange={(value) => setPriority(value as Priority)}
        />
      </div>

      <div className={styles.field}>
        <div className={styles.labelRow}>
          <label htmlFor="description" className={styles.label}>
            Description
          </label>
          <span className={styles.counter}>
            {description.length}/{DESCRIPTION_MAX}
          </span>
        </div>

        <textarea
          id="description"
          className={styles.textarea}
          placeholder="Describe what you need in as much detail as possible"
          value={description}
          onChange={(event) => setDescription(event.target.value)}
          minLength={10}
          maxLength={DESCRIPTION_MAX}
          rows={6}
          required
        />

        <p className={styles.hint}>At least 10 characters.</p>
      </div>

      {error && (
        <p role="alert" className={styles.error}>
          {error}
        </p>
      )}

      <div className={styles.actions}>
        <Link href="/requests" className={styles.cancel}>
          Cancel
        </Link>

        <button type="submit" className={styles.submit} disabled={loading}>
          {loading && <span className={styles.spinner} aria-hidden="true" />}
          {loading ? "Creating..." : "Create Request"}
        </button>
      </div>
    </form>
  );
}