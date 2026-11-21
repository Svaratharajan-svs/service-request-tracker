
"use client";

import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { useAuth } from "@/hooks/useAuth";

import Sidebar from "@/components/layout/Sidebar";
import Header from "@/components/layout/Header";

import styles from "./layout.module.css";

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();

  const { user, loading, authenticated, logout } = useAuth();

  const [sidebarOpen, setSidebarOpen] = useState(false);

  const closeSidebar = useCallback(() => {
    setSidebarOpen(false);
  }, []);

  const openSidebar = useCallback(() => {
    setSidebarOpen(true);
  }, []);

  // Redirect unauthenticated users.
  useEffect(() => {
    if (!loading && !authenticated) {
      router.replace("/login");
    }
  }, [loading, authenticated, router]);

  // Prevent dashboard rendering before authentication.
  if (loading || !authenticated || !user) {
    return (
      <div className={styles.loadingScreen}>
        <div className={styles.spinner} aria-hidden="true" />
        <p>
          {loading
            ? "Checking authentication..."
            : "Redirecting to login..."}
        </p>
      </div>
    );
  }

  return (
    <div className={styles.dashboard}>
      <Sidebar
        open={sidebarOpen}
        onClose={closeSidebar}
      />

      <div className={styles.content}>
        <Header
          user={user}
          onLogout={logout}
          onMenuClick={openSidebar}
          menuOpen={sidebarOpen}
        />

        <main className={styles.main}>
          {children}
        </main>
      </div>
    </div>
  );
}
