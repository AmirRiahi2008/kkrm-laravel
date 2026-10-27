"use client";

const apiOrigin = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000").replace(/\/$/, "");

export interface Pagination {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  pagination?: Pagination;
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = "ApiError";
  }
}

function xsrfToken(): string | undefined {
  const cookie = document.cookie
    .split("; ")
    .find((item) => item.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.slice("XSRF-TOKEN=".length)) : undefined;
}

export async function initializeCsrf(): Promise<void> {
  const response = await fetch(apiOrigin + "/sanctum/csrf-cookie", {
    credentials: "include",
    headers: { Accept: "application/json" },
    cache: "no-store",
  });
  if (!response.ok) {
    throw new ApiError(response.status, "دریافت نشست امن ناموفق بود.");
  }
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {},
): Promise<ApiResponse<T>> {
  if (!path.startsWith("/api/v1/") || path.startsWith("//")) {
    throw new Error("مسیر API معتبر نیست.");
  }
  const method = (options.method ?? "GET").toUpperCase();
  const writes = !["GET", "HEAD", "OPTIONS"].includes(method);
  if (writes && !xsrfToken()) {
    await initializeCsrf();
  }
  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");
  if (options.body && !(options.body instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }
  if (writes) {
    const token = xsrfToken();
    if (!token) {
      throw new Error("کوکی CSRF قابل خواندن نیست؛ دامنه نشست را بررسی کنید.");
    }
    headers.set("X-XSRF-TOKEN", token);
  }
  const response = await fetch(apiOrigin + path, {
    ...options,
    method,
    headers,
    credentials: "include",
    cache: "no-store",
  });
  const payload = await response.json();
  if (!response.ok) {
    throw new ApiError(response.status, payload.message ?? "درخواست ناموفق بود.", payload.errors);
  }
  return payload as ApiResponse<T>;
}

export async function login(email: string, password: string) {
  await initializeCsrf();
  return apiFetch<{
    id: number;
    name: string;
    email: string;
    role: "super_admin" | "relations_manager" | "editor";
  }>("/api/v1/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
}

export async function uploadMedia(file: File, alt: string) {
  const form = new FormData();
  form.set("file", file);
  form.set("alt", alt);
  return apiFetch<{ id: number; url: string }>("/api/v1/admin/media", {
    method: "POST",
    body: form,
  });
}
