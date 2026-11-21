
"use client";

import { useEffect, useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import axios from "axios";

import { login } from "@/lib/api/auth";
import { useAuth } from "@/hooks/useAuth";

import styles from "./login.module.css";

export default function LoginPage() {
  const router = useRouter();

  const { user, authenticated, refreshUser } = useAuth();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  // Redirect already authenticated users.
  useEffect(() => {
    if (authenticated && user) {
      router.replace("/requests");
    }
  }, [authenticated, user, router]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (loading) return;

    setLoading(true);
    setError("");

    try {
      await login({ email, password });

      // Update the shared authentication state.
      const currentUser = await refreshUser();

      if (!currentUser) {
        setError("Login succeeded, but unable to retrieve your user profile.");
        return;
      }

      // Authentication effect handles navigation.
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        if (err.response?.status === 422 || err.response?.status === 401) {
          setError("Invalid email or password.");
        } else if (err.response?.status === 419) {
          setError("Your session expired. Please try again.");
        } else {
          setError(
            err.response?.data?.message ??
              "Unable to log in. Please try again.",
          );
        }
      } else {
        setError("An unexpected error occurred.");
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className={styles.container}>
      <section className={styles.card}>
        <h1>Service Request Tracker</h1>

        <p>Sign in to manage your service requests.</p>

        <form onSubmit={handleSubmit}>
          <label htmlFor="email">Email</label>

          <input
            id="email"
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
            autoComplete="username"
            disabled={loading}
          />

          <label htmlFor="password">Password</label>

          <input
            id="password"
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
            autoComplete="current-password"
            disabled={loading}
          />

          {error && <p className={styles.error}>{error}</p>}

          <button type="submit" disabled={loading}>
            {loading ? "Signing in..." : "Sign in"}
          </button>
        </form>
      </section>
    </main>
  );
}
