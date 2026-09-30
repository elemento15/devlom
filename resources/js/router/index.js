import { createRouter, createWebHistory } from 'vue-router';
import Layout from '../components/Layout.vue';
import Login from '../components/Login.vue';
import Clients from '../components/sections/Clients.vue';
import Collaborators from '../components/sections/Collaborators.vue';
import Projects from '../components/sections/Projects.vue';
import Tasks from '../components/sections/Tasks.vue';
import { fetchCurrentUser, user } from '../store/auth';

let authChecked = false;

const routes = [
    { path: '/login', name: 'login', component: Login, meta: { public: true } },
    {
        path: '/',
        component: Layout,
        redirect: { name: 'clients' },
        children: [
            { path: 'clients', name: 'clients', component: Clients, meta: { label: 'Clients' } },
            { path: 'collaborators', name: 'collaborators', component: Collaborators, meta: { label: 'Collaborators' } },
            { path: 'projects', name: 'projects', component: Projects, meta: { label: 'Projects' } },
            { path: 'tasks', name: 'tasks', component: Tasks, meta: { label: 'Tasks' } },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: { name: 'clients' } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    if (!authChecked) {
        await fetchCurrentUser();
        authChecked = true;
    }

    if (!to.meta.public && !user.value) {
        return { name: 'login' };
    }

    if (to.name === 'login' && user.value) {
        return { name: 'clients' };
    }

    return true;
});

export default router;
