# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: 02-pm-resources.spec.ts >> 02 - PM: Create resources >> create risk
- Location: tests/e2e/02-pm-resources.spec.ts:62:3

# Error details

```
Error: locator.selectOption: Target page, context or browser has been closed
Call log:
  - waiting for locator('[name="impact"]')
    - locator resolved to <select hidden="" name="impact">…</select>
  - attempting select option action
    2 × waiting for element to be visible and enabled
      - element is not visible
    - retrying select option action
    - waiting 20ms
    2 × waiting for element to be visible and enabled
      - element is not visible
    - retrying select option action
      - waiting 100ms
    23 × waiting for element to be visible and enabled
       - element is not visible
     - retrying select option action
       - waiting 500ms
    - waiting for element to be visible and enabled
  - element was detached from the DOM, retrying

```

# Test source

```ts
  1   | /**
  2   |  * 02-pm-resources.spec.ts
  3   |  * Project Manager creates each resource type one by one (small, focused tests).
  4   |  */
  5   | import { test, expect } from '@playwright/test';
  6   | import {
  7   |   USERS,
  8   |   loadProjectId,
  9   |   url,
  10  |   loginAs,
  11  |   confirmDialog,
  12  | } from './helpers';
  13  | 
  14  | test.describe('02 - PM: Create resources', () => {
  15  |   let projectId: number;
  16  | 
  17  |   test.beforeAll(() => {
  18  |     projectId = loadProjectId();
  19  |   });
  20  | 
  21  |   test.beforeEach(async ({ page }) => {
  22  |     await loginAs(page, USERS.pm.email, USERS.pm.password);
  23  |   });
  24  | 
  25  |   test('create objective', async ({ page }) => {
  26  |     await page.goto(url(projectId, 'objectives/new'));
  27  |     await page.locator('input[name="title"]').fill('Aumentar ROI en 20%');
  28  |     await page.locator('textarea[name="description"]').fill('Incrementar retorno de inversión');
  29  |     await page.locator('button[type="submit"]').click();
  30  |     await confirmDialog(page);
  31  |     await page.waitForURL(url(projectId, 'objectives'), { timeout: 15000 });
  32  |     await expect(page.getByRole('table')).toContainText('Aumentar ROI');
  33  |   });
  34  | 
  35  |   test('create phase', async ({ page }) => {
  36  |     await page.goto(url(projectId, 'phases/new'));
  37  |     // PhaseForm uses Vuetify VTextField without explicit name → getByLabel
  38  |     await page.getByLabel('Nombre de la fase').fill('Fase 1 - Inicio');
  39  |     await page.locator('button[type="submit"]').click();
  40  |     await confirmDialog(page);
  41  |     await page.waitForURL(url(projectId, 'phases'), { timeout: 15000 });
  42  |     await expect(page.getByRole('table')).toContainText('Fase 1');
  43  |   });
  44  | 
  45  |   test('create plan', async ({ page }) => {
  46  |     await page.goto(url(projectId, 'plans'));
  47  |     // Plan page heading confirms we're on the right page
  48  |     await expect(page.getByText('Plan del proyecto')).toBeVisible();
  49  |     // Click "Editar plan" or "Crear plan" — whichever is visible
  50  |     const planButton = page.locator('button', { hasText: /plan/i });
  51  |     if (await planButton.isVisible()) {
  52  |       await planButton.click();
  53  |       // Vuetify VTextarea uses label, not name attr → getByLabel
  54  |       await page.getByLabel('Alcance').fill('Alcance del proyecto E2E');
  55  |       await page.getByRole('button', { name: 'Guardar' }).click();
  56  |       // Plans saves inline, no redirect → verify the text appears
  57  |       await expect(page.locator('.v-card-text')).toContainText('Alcance del proyecto E2E');
  58  |     }
  59  |     // If no button (plan auto-created), test is still valid — skip creation
  60  |   });
  61  | 
  62  |   test('create risk', async ({ page }) => {
  63  |     await page.goto(url(projectId, 'risks/new'));
  64  |     await page.locator('input[name="title"]').fill('Riesgo de retraso en entrega');
> 65  |     await page.locator('[name="impact"]').selectOption('high');
      |                                           ^ Error: locator.selectOption: Target page, context or browser has been closed
  66  |     await page.locator('[name="probability"]').selectOption('medium');
  67  |     await page.locator('button[type="submit"]').click();
  68  |     await confirmDialog(page);
  69  |     await page.waitForURL(url(projectId, 'risks'), { timeout: 15000 });
  70  |     await expect(page.getByRole('table')).toContainText('Riesgo de retraso');
  71  |   });
  72  | 
  73  |   test('create milestone', async ({ page }) => {
  74  |     await page.goto(url(projectId, 'milestones/new'));
  75  |     await page.locator('input[name="title"]').fill('MVP Release v1.0');
  76  |     await page.locator('button[type="submit"]').click();
  77  |     await confirmDialog(page);
  78  |     await page.waitForURL(url(projectId, 'milestones'), { timeout: 15000 });
  79  |     await expect(page.getByRole('table')).toContainText('MVP Release');
  80  |   });
  81  | 
  82  |   test('create deliverable', async ({ page }) => {
  83  |     await page.goto(url(projectId, 'deliverables/new'));
  84  |     await page.locator('input[name="name"]').fill('Manual de Usuario');
  85  |     await page.locator('button[type="submit"]').click();
  86  |     await confirmDialog(page);
  87  |     await page.waitForURL(url(projectId, 'deliverables'), { timeout: 15000 });
  88  |     await expect(page.getByRole('table')).toContainText('Manual de Usuario');
  89  |   });
  90  | 
  91  |   test('create task assigned to Developer', async ({ page }) => {
  92  |     await page.goto(url(projectId, 'tasks/new'));
  93  |     await page.locator('input[name="title"]').fill('Implementar login OAuth');
  94  |     await page.locator('[name="priority"]').selectOption('high');
  95  |     await page.locator('[name="assigned_to"]').click();
  96  |     await page.locator('.v-list-item').filter({ hasText: 'Developer' }).click();
  97  |     await page.locator('button[type="submit"]').click();
  98  |     await confirmDialog(page);
  99  |     await page.waitForURL(url(projectId, 'tasks'), { timeout: 15000 });
  100 |     await expect(page.getByRole('table')).toContainText('Implementar login OAuth');
  101 |   });
  102 | 
  103 |   test('create ticket', async ({ page }) => {
  104 |     await page.goto(url(projectId, 'tickets/new'));
  105 |     await page.locator('input[name="subject"]').fill('Bug crítico en producción');
  106 |     await page.locator('[name="priority"]').selectOption('critical');
  107 |     await page.locator('button[type="submit"]').click();
  108 |     await confirmDialog(page);
  109 |     await page.waitForURL(url(projectId, 'tickets'), { timeout: 15000 });
  110 |     await expect(page.getByRole('table')).toContainText('Bug crítico');
  111 |   });
  112 | });
```