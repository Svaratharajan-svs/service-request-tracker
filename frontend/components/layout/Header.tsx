import type { User } from "@/lib/types/auth";

import styles from "./Header.module.css";

function getInitials(name: string) {
  return (name ?? "")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("");
}

export default function Header({
  user,
  onLogout,
  onMenuClick,
  menuOpen,
}: {
  user: User;
  onLogout: () => void;
  onMenuClick: () => void;
  menuOpen: boolean;
}) {
  return (
    <header className={styles.header}>
      <div className={styles.welcome}>
        <button
          type="button"
          className={styles.menuButton}
          onClick={onMenuClick}
          aria-label="Open navigation menu"
          aria-expanded={menuOpen}
          aria-controls="app-sidebar"
        >
          <svg
            viewBox="0 0 24 24"
            width="20"
            height="20"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            aria-hidden="true"
          >
            <path d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div className={styles.avatar} aria-hidden="true">
          {getInitials(user.name)}
        </div>

        <div className={styles.text}>
          <span className={styles.greeting}>Welcome back</span>
          <span className={styles.name}>{user.name}</span>
        </div>
      </div>

      <button className={styles.logout} onClick={onLogout}>
        Logout
      </button>
    </header>
  );
}