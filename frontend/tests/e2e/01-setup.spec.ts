/**
 * 01-setup.spec.ts
 * Global setup: creates the project and adds all members via API.
 * Saves projectId to a file so subsequent specs can read it.
 */
import { test } from '@playwright/test';
import {
  USERS,
  PROJECT_CODE,
  PROJECT_NAME,
  API,
  apiLogin,
  saveProjectId,
} from './helpers';

test.describe('01 - Setup', () => {
  test('create project and add members via API', async () => {
    // PM logs in via API
    const pmToken = await apiLogin(USERS.pm.email, USERS.pm.password);

    // Create project
    const projRes = await fetch(`${API}/projects`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${pmToken}` },
      body: JSON.stringify({ name: PROJECT_NAME, code: PROJECT_CODE }),
    });
    const projectId: number = (await projRes.json()).items.id;
    saveProjectId(projectId);
    console.log(`✅ Project created with id ${projectId}`);

    // Get user IDs
    const adminToken = await apiLogin(USERS.admin.email, USERS.admin.password);
    const usersRes = await fetch(`${API}/users/all`, {
      headers: { Authorization: `Bearer ${adminToken}` },
    });
    const allUsers: any[] = (await usersRes.json()).items;
    const findId = (email: string) =>
      allUsers.find((u: any) => u.email === email)?.id;

    // Add members
    const members = [
      { id: findId(USERS.developer.email), role: 'developer' },
      { id: findId(USERS.qa.email),        role: 'qa' },
      { id: findId(USERS.support.email),   role: 'support' },
      { id: findId(USERS.client.email),     role: 'client' },
    ];

    for (const m of members) {
      if (m.id) {
        await fetch(`${API}/projects/${projectId}/members`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${pmToken}`,
          },
          body: JSON.stringify({ user_id: m.id, role: m.role }),
        });
      }
    }

    console.log('✅ Members added to project');
  });
});