import { createRouter, createWebHistory } from 'vue-router';
import { getAuthToken, clearAuthToken } from '../services';
import { me } from '../services/auth.service';
import * as projectsService from '../services/projects.service';
import { useAuthStore } from '../store/useAuthStore';
import { useAppStore } from '../store/useAppStore';
import MainLayout from '../layouts/MainLayout.vue';

// ── Auth / Guest pages ─────────────────────────────────────────────────────
const LoginPage = () => import('../pages/LoginPage.vue');
const ForgotPasswordPage = () => import('../pages/ForgotPasswordPage.vue');

// ── Core ───────────────────────────────────────────────────────────────────
const DashboardPage = () => import('../pages/DashboardPage.vue');
const ProfilePage = () => import('../pages/profile/index.vue');
const ProfileEditPage = () => import('../pages/profile/edit.vue');

// ── Projects ───────────────────────────────────────────────────────────────
const ProjectsIndexPage = () => import('../pages/projects/index.vue');
const ProjectsNewPage = () => import('../pages/projects/new.vue');
const ProjectDetailPage = () => import('../pages/projects/[id].vue');
const ProjectEditPage = () => import('../pages/projects/edit.vue');

// ── Admin ──────────────────────────────────────────────────────────────────
const AdminPage = () => import('../pages/admin/index.vue');
const AdminUsersIndex = () => import('../pages/admin/users/index.vue');
const AdminUsersNew = () => import('../pages/admin/users/new.vue');
const AdminUsersEdit = () => import('../pages/admin/users/[id].vue');

// ── Helper: lazy sub-resource pages ────────────────────────────────────────
const p = (path) => () => import(`../pages/${path}`);

const routes = [
    { path: '/', redirect: '/login' },

    // ── Guest ──────────────────────────────────────────────────────────────
    { path: '/login', name: 'login', component: LoginPage, meta: { requiresGuest: true } },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordPage, meta: { requiresGuest: true } },

    // ── Authenticated ──────────────────────────────────────────────────────
    {
        path: '/app',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },

            // Dashboard + Perfil
            { path: '/dashboard', name: 'dashboard', component: DashboardPage },
            { path: '/profile', name: 'profile', component: ProfilePage },
            { path: '/profile/edit', name: 'profile-edit', component: ProfileEditPage },

            // Proyectos
            { path: '/projects', name: 'projects', component: ProjectsIndexPage },
            { path: '/projects/new', name: 'projects-new', component: ProjectsNewPage },
            { path: '/projects/:projectId', name: 'project-detail', component: ProjectDetailPage },
            { path: '/projects/:projectId/edit', name: 'project-edit', component: ProjectEditPage },

            // Members
            { path: '/projects/:projectId/members', name: 'members', component: p('members/index.vue') },
            { path: '/projects/:projectId/members/new', name: 'members-new', component: p('members/new.vue') },
            { path: '/projects/:projectId/members/view/:id', name: 'members-view', component: p('members/view/[id].vue') },
            { path: '/projects/:projectId/members/:id', name: 'members-id', component: p('members/[id].vue') },

            // Objectives
            { path: '/projects/:projectId/objectives', name: 'objectives', component: p('objectives/index.vue') },
            { path: '/projects/:projectId/objectives/new', name: 'objectives-new', component: p('objectives/new.vue') },
            { path: '/projects/:projectId/objectives/view/:id', name: 'objectives-view', component: p('objectives/view/[id].vue') },
            { path: '/projects/:projectId/objectives/:id', name: 'objectives-id', component: p('objectives/[id].vue') },

            // Phases
            { path: '/projects/:projectId/phases', name: 'phases', component: p('phases/index.vue') },
            { path: '/projects/:projectId/phases/new', name: 'phases-new', component: p('phases/new.vue') },
            { path: '/projects/:projectId/phases/view/:id', name: 'phases-view', component: p('phases/view/[id].vue') },
            { path: '/projects/:projectId/phases/:id', name: 'phases-id', component: p('phases/[id].vue') },

            // Plans
            { path: '/projects/:projectId/plans', name: 'plans', component: p('plans/index.vue') },
            { path: '/projects/:projectId/plans/new', name: 'plans-new', component: p('plans/new.vue') },
            { path: '/projects/:projectId/plans/view/:id', name: 'plans-view', component: p('plans/view/[id].vue') },
            { path: '/projects/:projectId/plans/:id', name: 'plans-id', component: p('plans/[id].vue') },

            // Tasks
            { path: '/projects/:projectId/tasks', name: 'tasks', component: p('tasks/index.vue') },
            { path: '/projects/:projectId/tasks/new', name: 'tasks-new', component: p('tasks/new.vue') },
            { path: '/projects/:projectId/tasks/view/:id', name: 'tasks-view', component: p('tasks/view/[id].vue') },
            { path: '/projects/:projectId/tasks/:id', name: 'tasks-id', component: p('tasks/[id].vue') },

            // Tickets
            { path: '/projects/:projectId/tickets', name: 'tickets', component: p('tickets/index.vue') },
            { path: '/projects/:projectId/tickets/new', name: 'tickets-new', component: p('tickets/new.vue') },
            { path: '/projects/:projectId/tickets/view/:id', name: 'tickets-view', component: p('tickets/view/[id].vue') },
            { path: '/projects/:projectId/tickets/:id', name: 'tickets-id', component: p('tickets/[id].vue') },

            // Risks
            { path: '/projects/:projectId/risks', name: 'risks', component: p('risks/index.vue') },
            { path: '/projects/:projectId/risks/new', name: 'risks-new', component: p('risks/new.vue') },
            { path: '/projects/:projectId/risks/view/:id', name: 'risks-view', component: p('risks/view/[id].vue') },
            { path: '/projects/:projectId/risks/:id', name: 'risks-id', component: p('risks/[id].vue') },

            // Blockers
            { path: '/projects/:projectId/blockers', name: 'blockers', component: p('blockers/index.vue') },
            { path: '/projects/:projectId/blockers/new', name: 'blockers-new', component: p('blockers/new.vue') },
            { path: '/projects/:projectId/blockers/view/:id', name: 'blockers-view', component: p('blockers/view/[id].vue') },
            { path: '/projects/:projectId/blockers/:id', name: 'blockers-id', component: p('blockers/[id].vue') },

            // Deliverables
            { path: '/projects/:projectId/deliverables', name: 'deliverables', component: p('deliverables/index.vue') },
            { path: '/projects/:projectId/deliverables/new', name: 'deliverables-new', component: p('deliverables/new.vue') },
            { path: '/projects/:projectId/deliverables/view/:id', name: 'deliverables-view', component: p('deliverables/view/[id].vue') },
            { path: '/projects/:projectId/deliverables/:id', name: 'deliverables-id', component: p('deliverables/[id].vue') },

            // Milestones
            { path: '/projects/:projectId/milestones', name: 'milestones', component: p('milestones/index.vue') },
            { path: '/projects/:projectId/milestones/new', name: 'milestones-new', component: p('milestones/new.vue') },
            { path: '/projects/:projectId/milestones/view/:id', name: 'milestones-view', component: p('milestones/view/[id].vue') },
            { path: '/projects/:projectId/milestones/:id', name: 'milestones-id', component: p('milestones/[id].vue') },

            // Metrics
            { path: '/projects/:projectId/metrics', name: 'metrics', component: p('metrics/index.vue') },

            // Reports
            { path: '/projects/:projectId/reports', name: 'project-reports', component: () => import('../pages/project-detail/ProjectReportsTab.vue') },

            // Admin (superadmin)
            { path: '/admin', name: 'admin', component: AdminPage, meta: { requiresSuperAdmin: true } },
            { path: '/admin/users', name: 'admin-users', component: AdminUsersIndex, meta: { requiresSuperAdmin: true } },
            { path: '/admin/users/new', name: 'admin-users-new', component: AdminUsersNew, meta: { requiresSuperAdmin: true } },
            { path: '/admin/users/:id', name: 'admin-users-id', component: AdminUsersEdit, meta: { requiresSuperAdmin: true } },

            // Notificaciones
            { path: '/notifications', name: 'notifications', component: p('notifications/index.vue') },

            // Roles
            { path: '/roles', name: 'roles', component: () => import('../pages/RolesPage.vue') },
        ],
    },

    // ── Catch-all ──────────────────────────────────────────────────────────
    { path: '/:pathMatch(.*)*', redirect: '/login' },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

// ── Navigation guard ────────────────────────────────────────────────────────
router.beforeEach(async (to) => {
    const token = getAuthToken();
    const isAuthenticated = Boolean(token);

    // ── Restaurar sesión tras recarga ──────────────────────────────────────
    if (token) {
        const authStore = useAuthStore();
        if (!authStore.authUser) {
            const result = await me();
            if (result.status) {
                authStore.setSession(result.items, token);
            } else {
                clearAuthToken();
                if (to.meta.requiresAuth || to.meta.requiresSuperAdmin) {
                    return { name: 'login', query: { redirect: to.fullPath } };
                }
            }
        }
    }

    if (to.meta.requiresAuth && !isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresGuest && isAuthenticated) {
        const redirect = typeof to.query.redirect === 'string'
            ? to.query.redirect
            : '/dashboard';
        return redirect;
    }

    // ── Pre-cache currentProject con loader para feedback visual ───────────
    if (to.params.projectId && isAuthenticated) {
        const authStore = useAuthStore();
        const projectId = Number(to.params.projectId);
        if (!authStore.currentProject || authStore.currentProject.id !== projectId) {
            const appStore = useAppStore();
            appStore.loader = true;
            try {
                const response = await projectsService.show(projectId);
                if (response.status && response.items) {
                    authStore.setCurrentProject(response.items);
                }
            } finally {
                appStore.loader = false;
            }
        }
    }

    if (to.meta.requiresSuperAdmin && !isAuthenticated) {
        return { name: 'login' };
    }
    if (to.meta.requiresSuperAdmin && isAuthenticated) {
        const authStore = useAuthStore();
        if (!authStore.isSuperAdmin) {
            return { name: 'dashboard' };
        }
    }

    return true;
});
