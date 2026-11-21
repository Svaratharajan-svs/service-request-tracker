import { api } from "./client";
import type { LoginCredentials, User } from "../types/auth";

interface LoginResponse {
  message: string;
  user?: User;
}

export async function getCsrfCookie(): Promise<void> {
  await api.get("/sanctum/csrf-cookie");
}

export async function login(
  credentials: LoginCredentials
): Promise<LoginResponse> {
  await getCsrfCookie();

  const response = await api.post<LoginResponse>(
    "/api/auth/login",
    credentials
  );

  return response.data;
}

export async function getCurrentUser(): Promise<User> {
  const response = await api.get<{ data: User } | User>(
    "/api/auth/me"
  );

  // Supports either a direct User response or { data: User }.
  return "data" in response.data
    ? response.data.data
    : response.data;
}

export async function logout(): Promise<void> {
  await api.post("/api/auth/logout");
}