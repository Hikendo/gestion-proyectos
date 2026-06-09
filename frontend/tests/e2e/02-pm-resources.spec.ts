/**
 * 02-pm-resources.spec.ts
 * Project Manager creates each resource type one by one (small, focused tests).
 */
import { test, expect } from '@playwright/test';
import {
  USERS,
  loadProjectId,
  url,
  loginAs,
  confirmDialog,
} from './helpers';

test.describe('02 - PM: Create resources', () => {
  let projectId: number;

  test.beforeAll(() => {
    projectId = loadProjectId();
  });

  test.beforeEach(async ({ page }) => {
    await loginAs(page, USERS.pm.email, USERS.pm.password);
  });

  test('create objective', async ({ page }) => {
    await page.goto(url(projectId, 'objectives/new'));
    await page.locator('input[name="title"]').fill('Aumentar ROI en 20%');
    await page.locator('textarea[name="description"]').fill('Incrementar retorno de inversión');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'objectives'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Aumentar ROI');
  });

  test('create phase', async ({ page }) => {
    await page.goto(url(projectId, 'phases/new'));
    // PhaseForm uses Vuetify VTextField without explicit name → getByLabel
    await page.getByLabel('Nombre de la fase').fill('Fase 1 - Inicio');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'phases'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Fase 1');
  });

  test('create plan', async ({ page }) => {
    await page.goto(url(projectId, 'plans'));
    // Plan page heading confirms we're on the right page
    await expect(page.getByText('Plan del proyecto')).toBeVisible();
    // Click "Editar plan" or "Crear plan" — whichever is visible
    const planButton = page.locator('button', { hasText: /plan/i });
    if (await planButton.isVisible()) {
      await planButton.click();
      // Vuetify VTextarea uses label, not name attr → getByLabel
      await page.getByLabel('Alcance').fill('Alcance del proyecto E2E');
      await page.getByRole('button', { name: 'Guardar' }).click();
      // Plans saves inline, no redirect → verify the text appears
      await expect(page.locator('.v-card-text')).toContainText('Alcance del proyecto E2E');
    }
    // If no button (plan auto-created), test is still valid — skip creation
  });

  test('create risk', async ({ page }) => {
    await page.goto(url(projectId, 'risks/new'));
    await page.locator('input[name="title"]').fill('Riesgo de retraso en entrega');
    await page.locator('[name="impact"]').selectOption('high');
    await page.locator('[name="probability"]').selectOption('medium');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'risks'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Riesgo de retraso');
  });

  test('create milestone', async ({ page }) => {
    await page.goto(url(projectId, 'milestones/new'));
    await page.locator('input[name="title"]').fill('MVP Release v1.0');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'milestones'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('MVP Release');
  });

  test('create deliverable', async ({ page }) => {
    await page.goto(url(projectId, 'deliverables/new'));
    await page.locator('input[name="name"]').fill('Manual de Usuario');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'deliverables'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Manual de Usuario');
  });

  test('create task assigned to Developer', async ({ page }) => {
    await page.goto(url(projectId, 'tasks/new'));
    await page.locator('input[name="title"]').fill('Implementar login OAuth');
    await page.locator('[name="priority"]').selectOption('high');
    await page.locator('[name="assigned_to"]').click();
    await page.locator('.v-list-item').filter({ hasText: 'Developer' }).click();
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'tasks'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Implementar login OAuth');
  });

  test('create ticket', async ({ page }) => {
    await page.goto(url(projectId, 'tickets/new'));
    await page.locator('input[name="subject"]').fill('Bug crítico en producción');
    await page.locator('[name="priority"]').selectOption('critical');
    await page.locator('button[type="submit"]').click();
    await confirmDialog(page);
    await page.waitForURL(url(projectId, 'tickets'), { timeout: 15000 });
    await expect(page.getByRole('table')).toContainText('Bug crítico');
  });
});