
"use client";

import {
  createContext,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react";

import {
  getCurrentUser,
  logout as logoutRequest,
} from "@/lib/api/auth";

import type { User } from "@/lib/types/auth";

interface AuthContextType {
  user: User | null;
  loading: boolean;
  authenticated: boolean;
  refreshUser: () => Promise<User | null>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | null>(null);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [authenticated, setAuthenticated] = useState(false);

  const mounted = useRef(false);

  // Fetch the current authenticated user.
  const refreshUser = useCallback(async (): Promise<User | null> => {
    try {
      const currentUser = await getCurrentUser();

      if (mounted.current) {
        setUser(currentUser);
        setAuthenticated(true);
      }

      return currentUser;
    } catch {
      if (mounted.current) {
        setUser(null);
        setAuthenticated(false);
      }

      return null;
    } finally {
      if (mounted.current) {
        setLoading(false);
      }
    }
  }, []);

  // Initialize authentication.
  useEffect(() => {
    let active = true;

    mounted.current = true;

    const initializeAuth = async () => {
      try {
        const currentUser = await getCurrentUser();

        if (!active) return;

        setUser(currentUser);
        setAuthenticated(true);
      } catch {
        if (!active) return;

        setUser(null);
        setAuthenticated(false);
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void initializeAuth();

    return () => {
      active = false;
      mounted.current = false;
    };
  }, []);

  // Logout and clear authentication state.
  const logout = useCallback(async () => {
    try {
      await logoutRequest();
    } finally {
      if (mounted.current) {
        setUser(null);
        setAuthenticated(false);
        setLoading(false);
      }
    }
  }, []);

  // Memoize context value.
  const contextValue = useMemo(
    () => ({
      user,
      loading,
      authenticated,
      refreshUser,
      logout,
    }),
    [user, loading, authenticated, refreshUser, logout],
  );

  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
}
