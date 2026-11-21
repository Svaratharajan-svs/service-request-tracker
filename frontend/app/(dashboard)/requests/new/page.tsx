import Link from "next/link";

import RequestForm from "@/components/requests/RequestForm";

import styles from "./page.module.css";

export default function NewRequestPage() {
  return (
    <section className={styles.page}>
      <Link href="/requests" className={styles.back}>
        ← Back to requests
      </Link>

      <div className={styles.heading}>
        <h1 className={styles.title}>Create Service Request</h1>
        <p className={styles.subtitle}>
          Describe the issue you need assistance with.
        </p>
      </div>

      <RequestForm />
    </section>
  );
}