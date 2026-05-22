import { createRouter, createWebHistory } from 'vue-router';
import { getAuthToken } from '../services';
import MainLayout from '../layouts/MainLayout.vue';
import LoginPage from '../pages/LoginPage.vue';
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import ProjectsPage from '../pages/ProjectsPage.vue';
import RolesPage from '../pages/RolesPage.vue';
import UsersLayoutPage from '../pages/users/UsersLayoutPage.vue';
import UsersListPage from '../pages/users/UsersListPage.vue';
import UsersCreatePage from '../pages/users/UsersCreatePage.vue';
import UsersUpdatePage from '../pages/users/UsersUpdatePage.vue';
import UsersDeletePage from '../pages/users/UsersDeletePage.vue';
import ProjectFeaturesLayoutPage from '../pages/projects/ProjectFeaturesLayoutPage.vue';
import MembersFeature from '../features/projects/members/MembersFeature.vue';
import PhasesFeature from '../features/projects/phases/PhasesFeature.vue';
import PlansFeature from '../features/projects/plans/PlansFeature.vue';
import ObjectivesFeature from '../features/projects/objectives/ObjectivesFeature.vue';
import MilestonesFeature from '../features/projects/milestones/MilestonesFeature.vue';
import DeliverablesFeature from '../features/projects/deliverables/DeliverablesFeature.vue';
import TasksFeature from '../features/projects/tasks/TasksFeature.vue';
import TicketsFeature from '../features/projects/tickets/TicketsFeature.vue';
import RisksFeature from '../features/projects/risks/RisksFeature.vue';
import BlockersFeature from '../features/projects/blockers/BlockersFeature.vue';

const projectFeatureProps = (route) => ({
    projectId: Number(route.params.id),
});

const routes = [
    {
        path: '/',
        redirect: '/login',
    },
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: {
            requiresGuest: true,
        },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordPage,
        meta: {
            requiresGuest: true,
        },
    },
    {
        path: '/app',
        component: MainLayout,
        meta: {
            requiresAuth: true,
        },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: '/dashboard',
                name: 'dashboard',
                component: DashboardPage,
            },
            {
                path: '/users',
                component: UsersLayoutPage,
                children: [
                    {
                        path: '',
                        redirect: '/users/list',
                    },
                    {
                        path: 'list',
                        name: 'users-list',
                        component: UsersListPage,
                    },
                    {
                        path: 'create',
                        name: 'users-create',
                        component: UsersCreatePage,
                    },
                    {
                        path: 'update',
                        name: 'users-update',
                        component: UsersUpdatePage,
                    },
                    {
                        path: 'delete',
                        name: 'users-delete',
                        component: UsersDeletePage,
                    },
                ],
            },
            {
                path: '/roles',
                name: 'roles',
                component: RolesPage,
            },
            {
                path: '/projects',
                name: 'projects',
                component: ProjectsPage,
            },
            {
                path: '/projects/:id',
                name: 'projects-detail',
                component: ProjectFeaturesLayoutPage,
                children: [
                    {
                        path: '',
                        redirect: (to) => ({
                            name: 'project-members',
                            params: to.params,
                        }),
                    },
                    {
                        path: 'members',
                        name: 'project-members',
                        component: MembersFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'phases',
                        name: 'project-phases',
                        component: PhasesFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'plan',
                        name: 'project-plan',
                        component: PlansFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'objectives',
                        name: 'project-objectives',
                        component: ObjectivesFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'milestones',
                        name: 'project-milestones',
                        component: MilestonesFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'deliverables',
                        name: 'project-deliverables',
                        component: DeliverablesFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'tasks',
                        name: 'project-tasks',
                        component: TasksFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'tickets',
                        name: 'project-tickets',
                        component: TicketsFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'risks',
                        name: 'project-risks',
                        component: RisksFeature,
                        props: projectFeatureProps,
                    },
                    {
                        path: 'blockers',
                        name: 'project-blockers',
                        component: BlockersFeature,
                        props: projectFeatureProps,
                    },
                ],
            },
        ],
    },
    {
        path: '/auth',
        redirect: '/login',
    },
    {
        path: '/admin',
        redirect: '/users/list',
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const isAuthenticated = Boolean(getAuthToken());

    if (to.meta.requiresAuth && !isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (to.meta.requiresGuest && isAuthenticated) {
        const redirectTarget = typeof to.query.redirect === 'string' ? to.query.redirect : '/dashboard';

        return redirectTarget;
    }

    return true;
});
