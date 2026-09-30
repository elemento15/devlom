<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { signOut, user } from '../store/auth';
import { loadOptions } from '../store/options';

const sections = [
    { name: 'clients', label: 'Clients' },
    { name: 'collaborators', label: 'Collaborators' },
    { name: 'projects', label: 'Projects' },
    { name: 'tasks', label: 'Tasks' },
];

const route = useRoute();
const router = useRouter();
const userMenuOpen = ref(false);

async function handleSignOut() {
    await signOut();
    userMenuOpen.value = false;
    router.push({ name: 'login' });
}

watch(user, (value) => {
    if (!value) router.push({ name: 'login' });
});

onMounted(() => {
    loadOptions();
});
</script>

<template>
    <nav class="navbar navbar-dark bg-dark app-navbar px-4">
        <router-link class="navbar-brand fw-semibold" :to="{ name: 'clients' }">DevLom</router-link>
        <div class="user-menu">
            <button class="btn btn-dark" type="button" aria-haspopup="true" :aria-expanded="userMenuOpen" @click="userMenuOpen = !userMenuOpen">
                {{ user?.name }} <span aria-hidden="true">⌄</span>
            </button>
            <div v-if="userMenuOpen" class="user-menu-panel">
                <button type="button" @click="handleSignOut">Log out</button>
            </div>
        </div>
    </nav>

    <div class="app-shell">
        <aside class="app-sidebar">
            <div class="sidebar-label mb-3">Workspace</div>
            <router-link
                v-for="item in sections"
                :key="item.name"
                :to="{ name: item.name }"
                custom
                v-slot="{ navigate, isActive }"
            >
                <button
                    type="button"
                    class="sidebar-link"
                    :class="{ active: isActive }"
                    @click="navigate"
                >
                    {{ item.label }}
                </button>
            </router-link>
        </aside>

        <main class="app-content">
            <router-view :key="route.fullPath" />
        </main>
    </div>
</template>
