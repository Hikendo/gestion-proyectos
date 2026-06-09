/**
 * 06-client.spec.ts
 * Client actions: create ticket, view resources, verify UI restrictions.
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loadProjectId,
  url,
  loginAs,
  confirmDialog,
} from './helpers';

test.describe('06 - Client', () => {
  let projectId: number;

  test.beforeAll(() => {
    projectId = loadProjectId();
  });

  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.client.email, USERS.client.password);
  });

  test('create ticket', async ({ page }) => {
    await page.goto(url(projectId, 'tickets/new'));
    await page.locator('input[name="subject"]').fill('Cliente: Nueva funcionalidad');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await expect(page.locator('.v-card')).toContainText('Nueva funcionalidad');
  });

  test('view milestones', async ({ page }) => {
    await page.goto(url(projectId, 'milestones'));
    await expect(page.locator('.v-card')).toContainText('MVP Release');
  });

  test('view deliverables', async ({ page }) => {
    await page.goto(url(projectId, 'deliverables'));
    await expect(page.locator('.v-card')).toContainText('Manual de Usuario');
  });

  test('view objectives', async ({ page }) => {
    await page.goto(url(projectId, 'objectives'));
    await expect(page.locator('.v-card')).toContainText('Aumentar ROI');
  });

  test('view metrics', async ({ page }) => {
    await page.goto(url(projectId, 'metrics'));
    await expect(page.locator('.v-card')).toBeVisible();
  });

  test('cannot create tasks', async ({ page }) => {
    await page.goto(url(projectId, 'tasks'));
    await expect(page.locator('text=Nueva Tarea')).not.toBeVisible();
  });

  test('cannot create risks', async ({ page }) => {
    await page.goto(url(projectId, 'risks'));
    await expect(page.locator('text=Nuevo riesgo')).not.toBeVisible();
  });

  test('cannot see admin panel', async ({ page }) => {
    await expect(page.locator('text=Administración')).not.toBeVisible();
  });
});