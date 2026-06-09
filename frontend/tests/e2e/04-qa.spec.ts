/**
 * 04-qa.spec.ts
 * QA actions: view tasks, create ticket, create blocker.
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loadProjectId,
  url,
  loginAs,
  confirmDialog,
} from './helpers';

test.describe('04 - QA', () => {
  let projectId: number;

  test.beforeAll(() => {
    projectId = loadProjectId();
  });

  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.qa.email, USERS.qa.password);
  });

  test('view tasks', async ({ page }) => {
    await page.goto(url(projectId, 'tasks'));
    await expect(page.locator('.v-card')).toContainText('Implementar login OAuth');
  });

  test('create ticket', async ({ page }) => {
    await page.goto(url(projectId, 'tickets/new'));
    await page.locator('input[name="subject"]').fill('QA: Error de validación');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('Error de validación');
  });

  test('create blocker', async ({ page }) => {
    await page.goto(url(projectId, 'blockers/new'));
    await page.locator('input[name="title"]').fill('QA Blocker: Test suite rota');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('Test suite rota');
  });
});