/**
 * 07-admin.spec.ts
 * Super-Admin actions: view admin menu, users list, roles.
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loginAs,
} from './helpers';

test.describe('07 - Admin', () => {
  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.admin.email, USERS.admin.password);
  });

  test('see admin panel in menu', async ({ page }) => {
    await expect(page.locator('text=Administración')).toBeVisible();
  });

  test('list users', async ({ page }) => {
    await page.goto('/admin/users');
    await expect(page).toHaveURL(/\/admin\/users/, { timeout: 5000 });
    await expect(page.locator('.v-card')).toContainText('Project Manager');
  });

  test('view roles', async ({ page }) => {
    await page.goto('/roles');
    await expect(page).toHaveURL(/\/roles/, { timeout: 5000 });
    await expect(page.locator('.v-card')).toBeVisible();
  });
});