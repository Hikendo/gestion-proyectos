/**
 * Shared helpers, constants and types for E2E tests.
 */
import type { Page } from '@playwright/test';
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

// ── File-based projectId persistence ─────────────────────
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const PID_FILE = resolve(__dirname, '.projectId');

export function saveProjectId(id: number): void {
  writeFileSync(PID_FILE, String(id), 'utf-8');
}

export function loadProjectId(): number {
  return Number(readFileSync(PID_FILE, 'utf-8'));
}

// ── Constants ─────────────────────────────────────────────
export const USERS = {
  admin:    { email: 'superadmin@test.com', password: 'password' },
  pm:       { email: 'pm@test.com',         password: 'password' },
  developer:{ email: 'dev@test.com',        password: 'password' },
  qa:       { email: 'qa@test.com',         password: 'password' },
  support:  { email: 'support@test.com',    password: 'password' },
  client:   { email: 'client@test.com',     password: 'password' },
} as const;

export const PROJECT_CODE = `E2E-${Date.now()}`;
export const PROJECT_NAME = `E2E Project ${Date.now()}`;
export const API = 'http://localhost:8000/api/v1';

// ── Helpers ───────────────────────────────────────────────

/** Login via API, return Bearer token */
export async function apiLogin(email: string, password: string): Promise<string> {
  const res = await fetch(`${API}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  const data = await res.json();
  return data.items.token;
}

/** Build internal route URL for a project module */
export function url(projectId: number, module: string): string {
  return `/projects/${projectId}/${module}`;
}

/** Login to the SPA and wait for dashboard redirect */
export async function loginAs(page: Page, email: string, password: string): Promise<void> {
  await page.goto('/login');
  await page.locator('input[type="email"]').fill(email);
  await page.locator('input[type="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/dashboard/, { timeout: 15000 });
}

/**
 * Confirm a Vuetify VDialog by clicking "Confirmar".
 * Best practice: resolve by accessible role + name.
 */
export async function confirmDialog(page: Page): Promise<void> {
  // Wait for the VDialog to appear, then click "Confirmar" scoped inside it
  const dialog = page.getByRole('dialog');
  await dialog.waitFor({ state: 'visible', timeout: 5000 });
  await dialog.getByRole('button', { name: 'Confirmar' }).click();
}

/** Navigate to a creation form, fill, submit, confirm dialog, waitForURL, return to list. */
export async function createResource(
  page: Page,
  projectId: number,
  module: string,
  fields: Record<string, string | { selector: string; value: string }>,
): Promise<void> {
  await page.goto(url(projectId, `${module}/new`));
  for (const [, value] of Object.entries(fields)) {
    if (typeof value === 'string') {
      await page.locator(value).fill(value);
    } else {
      await page.locator(value.selector).selectOption(value.value);
    }
  }
  await page.locator('button[type="submit"]').click();
  await confirmDialog(page);
  await page.waitForURL(url(projectId, module), { timeout: 15000 });
}

/** Click a text element on the page (using text= locator) */
export async function clickText(page: Page, text: string): Promise<void> {
  await page.locator(`text=${text}`).click();
}

/** Verify that text is visible in a table or card */
export async function expectVisible(page: Page, text: string, timeout = 5000): Promise<void> {
  await page.locator(`text=${text}`).waitFor({ state: 'visible', timeout });
}

/** Verify that a selector is NOT visible */
export async function expectNotVisible(page: Page, selector: string, timeout = 2000): Promise<void> {
  await page.locator(selector).waitFor({ state: 'hidden', timeout });
}