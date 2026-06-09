/**
 * 05-support.spec.ts
 * Support actions: create ticket, view blockers, no admin access.
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loadProjectId,
  url,
  loginAs,
  confirmDialog,
} from './helpers';

test.describe('05 - Support', () => {
  let projectId: number;

  test.beforeAll(() => {
    projectId = loadProjectId();
  });

  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.support.email, USERS.support.password);
  });

  test('create ticket', async ({ page }) => {
    await page.goto(url(projectId, 'tickets/new'));
    await page.locator('input[name="subject"]').fill('Support: Reset contraseña');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('Reset contraseña');
  });

  test('view blockers', async ({ page }) => {
    await page.goto(url(projectId, 'blockers'));
    await expect(page.locator('.v-card')).toBeVisible();
  });

  test('cannot see admin panel', async ({ page }) => {
    await expect(page.locator('text=Administración')).not.toBeVisible();
  });
});