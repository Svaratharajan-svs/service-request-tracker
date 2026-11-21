"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect } from "react";

import styles from "./Siderbar.module.css";

const links = [
  {
    href: "/requests",
    label: "My Requests",
    icon: (
      <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 5h6M9 12h6M9 16h4" />
    ),
  },
  {
    href: "/requests/new",
    label: "Create Request",
    icon: <path d="M12 5v14M5 12h14" />,
  },
];

export default function Sidebar({
  open,
  onClose,
}: {
  open: boolean;
  onClose: () => void;
}) {
  const pathname = usePathname();

  // Close with Escape and lock page scroll while the drawer is open
  useEffect(() => {
    if (!open) return;

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose();
    }

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", onKeyDown);

    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open, onClose]);

  return (
    <>
      <div
        className={`${styles.backdrop} ${open ? styles.backdropOpen : ""}`}
        onClick={onClose}
        aria-hidden="true"
      />

      <aside
        id="app-sidebar"
        className={`${styles.sidebar} ${open ? styles.open : ""}`}
      >
        <div className={styles.brand}>
          <div className={styles.logo} aria-hidden="true">
            S
          </div>
          <h2 className={styles.brandName}>Service Tracker</h2>

          <button
            type="button"
            className={styles.closeButton}
            onClick={onClose}
            aria-label="Close navigation menu"
          >
            <svg
              viewBox="0 0 24 24"
              width="18"
              height="18"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinecap="round"
              aria-hidden="true"
            >
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          </button>
        </div>

        <div>
          <div className={styles.sectionLabel}>Menu</div>

          <nav className={styles.nav}>
            {links.map(({ href, label, icon }) => {
              // "/requests" should not stay active on "/requests/new"
              const isActive =
                href === "/requests"
                  ? pathname === "/requests" ||
                    (pathname.startsWith("/requests/") &&
                      pathname !== "/requests/new")
                  : pathname === href;

              return (
                <Link
                  key={href}
                  href={href}
                  onClick={onClose}
                  className={`${styles.link} ${isActive ? styles.active : ""}`}
                >
                  <svg
                    className={styles.icon}
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    aria-hidden="true"
                  >
                    {icon}
                  </svg>
                  {label}
                </Link>
              );
            })}
          </nav>
        </div>
      </aside>
    </>
  );
}