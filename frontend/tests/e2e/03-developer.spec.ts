/**
 * 03-developer.spec.ts
 * Developer actions: change task status, create ticket, create blocker.
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loadProjectId,
  url,
  loginAs,
  confirmDialog,
} from './helpers';

test.describe('03 - Developer', () => {
  let projectId: number;

  test.beforeAll(() => {
    projectId = loadProjectId();
  });

  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.developer.email, USERS.developer.password);
  });

  test('view task and change status to in_progress', async ({ page }) => {
    await page.goto(url(projectId, 'tasks'));
    await expect(page.locator('.v-card')).toContainText('Implementar login OAuth');
    await page.locator('text=Implementar login OAuth').click();
    await page.locator('[name="status"]').selectOption('in_progress');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForTimeout(500);
  });

  test('create ticket', async ({ page }) => {
    await page.goto(url(projectId, 'tickets/new'));
    await page.locator('input[name="subject"]').fill('Dev report: API lenta');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('API lenta');
  });

  test('create blocker', async ({ page }) => {
    await page.goto(url(projectId, 'blockers/new'));
    await page.locator('input[name="title"]').fill('Bloqueo: Servidor staging');
    await page.locator('[name="severity"]').selectOption('critical');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('Servidor staging');
  });

  test('cannot see admin panel', async ({ page }) => {
    await expect(page.locator('text=Administración')).not.toBeVisible();
  });
});