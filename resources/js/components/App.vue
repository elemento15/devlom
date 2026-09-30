<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const sections = [
    { key: 'clients', label: 'Clients' },
    { key: 'collaborators', label: 'Collaborators' },
    { key: 'projects', label: 'Projects' },
    { key: 'tasks', label: 'Tasks' },
];
const user = ref(null);
const section = ref('clients');
const page = ref(1);
const listing = ref({ data: [], current_page: 1, last_page: 1, total: 0 });
const options = ref({ clients: [], projects: [], collaborators: [] });
const form = reactive({});
const errors = ref({});
const notice = ref('');
const editing = ref(null);
const showForm = ref(false);
const showTimeForm = ref(false);
const selectedTask = ref(null);
const userMenuOpen = ref(false);
const busy = ref(false);
const login = reactive({ email: '', password: '' });

const currentSection = computed(() => sections.find((item) => item.key === section.value));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

async function api(url, method = 'GET', data = null) {
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() };
    if (data !== null) headers['Content-Type'] = 'application/json';

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: data === null ? undefined : JSON.stringify(data),
    });
    const result = response.status === 204 ? null : await response.json();

    if (!response.ok) {
        if (response.status === 401 && user.value) user.value = null;
        const error = new Error(result?.message ?? 'Something went wrong.');
        error.validation = result?.errors ?? {};
        throw error;
    }

    return result;
}

async function loadOptions() {
    options.value = await api('/api/options');
}

async function loadListing(targetPage = page.value) {
    page.value = targetPage;
    listing.value = await api(`/api/${section.value}?page=${targetPage}`);
}

async function openSection(key) {
    section.value = key;
    page.value = 1;
    showForm.value = false;
    errors.value = {};
    await loadListing(1);
}

function blankForm() {
    return section.value === 'clients'
        ? { name: '', rfc: '' }
        : section.value === 'collaborators'
            ? { name: '', price: '' }
            : section.value === 'projects'
                ? { name: '', alias: '', client_id: '' }
                : { description: '', project_id: '' };
}

function addNew() {
    Object.assign(form, blankForm());
    editing.value = null;
    errors.value = {};
    notice.value = '';
    showForm.value = true;
}

function editItem(item) {
    const values = section.value === 'clients'
        ? { name: item.name, rfc: item.rfc }
        : section.value === 'collaborators'
            ? { name: item.name, price: item.price }
            : section.value === 'projects'
                ? { name: item.name }
                : { description: item.description };
    Object.assign(form, values);
    editing.value = item.id;
    errors.value = {};
    notice.value = '';
    showForm.value = true;
}

async function saveItem() {
    busy.value = true;
    errors.value = {};
    notice.value = '';
    try {
        const url = editing.value ? `/api/${section.value}/${editing.value}` : `/api/${section.value}`;
        await api(url, editing.value ? 'PUT' : 'POST', { ...form });
        showForm.value = false;
        await Promise.all([loadListing(), loadOptions()]);
    } catch (error) {
        errors.value = error.validation;
        notice.value = error.message;
    } finally {
        busy.value = false;
    }
}

async function toggleActive(item) {
    try {
        await api(`/api/${section.value}/${item.id}/toggle`, 'PATCH');
        await Promise.all([loadListing(), loadOptions()]);
    } catch (error) {
        notice.value = error.message;
    }
}

async function finishTask(task) {
    try {
        await api(`/api/tasks/${task.id}/finish`, 'PATCH');
        await loadListing();
    } catch (error) {
        notice.value = error.message;
    }
}

async function deleteTask(task) {
    if (!window.confirm(`Delete task ${task.folio}?`)) return;
    try {
        await api(`/api/tasks/${task.id}`, 'DELETE');
        await loadListing();
    } catch (error) {
        notice.value = error.message;
    }
}

function openTimeForm(task) {
    selectedTask.value = task;
    Object.assign(form, { collaborator_id: '', hours: '' });
    errors.value = {};
    notice.value = '';
    showTimeForm.value = true;
}

async function saveTime() {
    busy.value = true;
    errors.value = {};
    try {
        await api('/api/time-entries', 'POST', {
            task_id: selectedTask.value.id,
            collaborator_id: form.collaborator_id,
            hours: form.hours,
        });
        showTimeForm.value = false;
        await loadListing();
    } catch (error) {
        errors.value = error.validation;
        notice.value = error.message;
    } finally {
        busy.value = false;
    }
}

async function signIn() {
    busy.value = true;
    errors.value = {};
    notice.value = '';
    try {
        const result = await api('/api/login', 'POST', login);
        user.value = result.user;
        await Promise.all([loadOptions(), loadListing()]);
    } catch (error) {
        errors.value = error.validation;
        notice.value = error.message;
    } finally {
        busy.value = false;
    }
}

async function signOut() {
    try {
        await api('/api/logout', 'POST');
    } finally {
        user.value = null;
        userMenuOpen.value = false;
        login.password = '';
    }
}

function fieldError(field) {
    return errors.value?.[field]?.[0] ?? '';
}

onMounted(async () => {
    try {
        const result = await api('/api/user');
        user.value = result.user;
        await Promise.all([loadOptions(), loadListing()]);
    } catch {
        user.value = null;
    }
});
</script>

<template>
    <main v-if="!user" class="login-page">
        <form class="login-card" @submit.prevent="signIn">
            <div class="login-brand mb-2">DevLom</div>
            <p class="text-secondary mb-4">Sign in to manage your projects and tasks.</p>
            <div v-if="notice" class="alert alert-danger py-2" role="alert">{{ notice }}</div>
            <div class="mb-3">
                <label for="login-email" class="form-label">Email</label>
                <input id="login-email" v-model="login.email" class="form-control" type="email" autocomplete="username" required>
            </div>
            <div class="mb-4">
                <label for="login-password" class="form-label">Password</label>
                <input id="login-password" v-model="login.password" class="form-control" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit" :disabled="busy">
                {{ busy ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>
    </main>

    <template v-else>
        <nav class="navbar navbar-dark bg-dark app-navbar px-4">
            <a class="navbar-brand fw-semibold" href="#" @click.prevent="openSection('clients')">DevLom</a>
            <div class="user-menu">
                <button class="btn btn-dark" type="button" aria-haspopup="true" :aria-expanded="userMenuOpen" @click="userMenuOpen = !userMenuOpen">
                    {{ user.name }} <span aria-hidden="true">⌄</span>
                </button>
                <div v-if="userMenuOpen" class="user-menu-panel">
                    <button type="button" @click="signOut">Log out</button>
                </div>
            </div>
        </nav>

        <div class="app-shell">
            <aside class="app-sidebar">
                <div class="sidebar-label mb-3">Workspace</div>
                <button
                    v-for="item in sections"
                    :key="item.key"
                    type="button"
                    class="sidebar-link"
                    :class="{ active: section === item.key }"
                    @click="openSection(item.key)"
                >
                    {{ item.label }}
                </button>
            </aside>

            <main class="app-content">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h3 mb-1">{{ currentSection.label }}</h1>
                        <div class="text-secondary small">{{ listing.total }} records</div>
                    </div>
                    <button class="btn btn-primary" type="button" @click="addNew">Add New</button>
                </div>

                <div v-if="notice && user" class="alert alert-danger" role="alert">{{ notice }}</div>

                <section v-if="showForm" class="content-card p-4 mb-4">
                    <h2 class="h5 mb-3">{{ editing ? 'Edit' : 'Add' }} {{ currentSection.label.slice(0, -1) }}</h2>
                    <form @submit.prevent="saveItem">
                        <div v-if="section === 'clients'" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="client-name">Name</label>
                                <input id="client-name" v-model="form.name" class="form-control" maxlength="50" required>
                                <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="client-rfc">RFC</label>
                                <input id="client-rfc" v-model="form.rfc" class="form-control" maxlength="13" required>
                                <div v-if="fieldError('rfc')" class="text-danger small mt-1">{{ fieldError('rfc') }}</div>
                            </div>
                        </div>

                        <div v-else-if="section === 'collaborators'" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="collaborator-name">Name</label>
                                <input id="collaborator-name" v-model="form.name" class="form-control" maxlength="50" required>
                                <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="collaborator-price">Price per hour</label>
                                <input id="collaborator-price" v-model="form.price" class="form-control" type="number" min="0.01" max="1000" step="0.01" required>
                                <div v-if="fieldError('price')" class="text-danger small mt-1">{{ fieldError('price') }}</div>
                            </div>
                        </div>

                        <div v-else-if="section === 'projects'" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="project-name">Name</label>
                                <input id="project-name" v-model="form.name" class="form-control" maxlength="50" required>
                                <div v-if="fieldError('name')" class="text-danger small mt-1">{{ fieldError('name') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="project-alias">Alias</label>
                                <input id="project-alias" v-model="form.alias" class="form-control" maxlength="4" :disabled="!!editing" required>
                                <div v-if="fieldError('alias')" class="text-danger small mt-1">{{ fieldError('alias') }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="project-client">Client</label>
                                <select id="project-client" v-model="form.client_id" class="form-select" :disabled="!!editing" required>
                                    <option value="" disabled>Select a client</option>
                                    <option v-for="client in options.clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </select>
                                <div v-if="fieldError('client_id')" class="text-danger small mt-1">{{ fieldError('client_id') }}</div>
                            </div>
                        </div>

                        <div v-else class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="task-description">Description</label>
                                <input id="task-description" v-model="form.description" class="form-control" maxlength="100" required>
                                <div v-if="fieldError('description')" class="text-danger small mt-1">{{ fieldError('description') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="task-project">Project</label>
                                <select id="task-project" v-model="form.project_id" class="form-select" :disabled="!!editing" required>
                                    <option value="" disabled>Select a project</option>
                                    <option v-for="project in options.projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                                </select>
                                <div v-if="fieldError('project_id')" class="text-danger small mt-1">{{ fieldError('project_id') }}</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Save' }}</button>
                            <button class="btn btn-outline-secondary" type="button" @click="showForm = false">Cancel</button>
                        </div>
                    </form>
                </section>

                <section class="content-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr v-if="section === 'clients'">
                                    <th>Name</th><th>RFC</th><th>Status</th><th class="text-end">Actions</th>
                                </tr>
                                <tr v-else-if="section === 'projects'">
                                    <th>Name</th><th>Alias</th><th>Client</th><th>Status</th><th class="text-end">Actions</th>
                                </tr>
                                <tr v-else-if="section === 'collaborators'">
                                    <th>Name</th><th>Price / hour</th><th>Status</th><th class="text-end">Actions</th>
                                </tr>
                                <tr v-else>
                                    <th>Folio</th><th>Description</th><th>Project</th><th>Status</th><th>Time</th><th>Cost</th><th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in listing.data" :key="item.id">
                                    <template v-if="section === 'clients'">
                                        <td class="fw-medium">{{ item.name }}</td><td>{{ item.rfc }}</td>
                                        <td><span class="status-pill" :class="{ finished: !item.active }">{{ item.active ? 'Active' : 'Inactive' }}</span></td>
                                    </template>
                                    <template v-else-if="section === 'projects'">
                                        <td class="fw-medium">{{ item.name }}</td><td>{{ item.alias }}</td><td>{{ item.client?.name }}</td>
                                        <td><span class="status-pill" :class="{ finished: !item.active }">{{ item.active ? 'Active' : 'Inactive' }}</span></td>
                                    </template>
                                    <template v-else-if="section === 'collaborators'">
                                        <td class="fw-medium">{{ item.name }}</td><td>{{ Number(item.price).toFixed(2) }}</td>
                                        <td><span class="status-pill" :class="{ finished: !item.active }">{{ item.active ? 'Active' : 'Inactive' }}</span></td>
                                    </template>
                                    <template v-else>
                                        <td class="fw-medium">{{ item.folio }}</td><td>{{ item.description }}</td><td>{{ item.project?.name }}</td>
                                        <td><span class="status-pill" :class="{ finished: item.status?.code === 'FIN' }">{{ item.status?.name }}</span></td>
                                        <td>{{ Number(item.total_hours ?? 0).toFixed(2) }} h</td><td>{{ Number(item.total_cost ?? 0).toFixed(2) }}</td>
                                    </template>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <button class="btn btn-sm btn-outline-primary" type="button" @click="editItem(item)">Edit</button>
                                            <button v-if="section !== 'tasks'" class="btn btn-sm btn-outline-secondary" type="button" @click="toggleActive(item)">
                                                {{ item.active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                            <template v-else>
                                                <button v-if="item.status?.code !== 'FIN'" class="btn btn-sm btn-outline-success" type="button" @click="finishTask(item)">Finish</button>
                                                <button v-if="item.status?.code !== 'FIN'" class="btn btn-sm btn-outline-dark" type="button" @click="openTimeForm(item)">Add Time</button>
                                                <button v-if="!item.time_entries_count" class="btn btn-sm btn-outline-danger" type="button" @click="deleteTask(item)">Delete</button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="listing.data.length === 0">
                                    <td :colspan="section === 'tasks' ? 7 : 5" class="py-5 text-center text-secondary">No {{ currentSection.label.toLowerCase() }} yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="listing.last_page > 1" class="d-flex justify-content-between align-items-center border-top px-3 py-3">
                        <span class="small text-secondary">Page {{ listing.current_page }} of {{ listing.last_page }}</span>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="listing.current_page <= 1" @click="loadListing(listing.current_page - 1)">Previous</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button" :disabled="listing.current_page >= listing.last_page" @click="loadListing(listing.current_page + 1)">Next</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <div v-if="showTimeForm" class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="time-modal-title" @click.self="showTimeForm = false">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" @submit.prevent="saveTime">
                    <div class="modal-header">
                        <h2 id="time-modal-title" class="modal-title fs-5">Add Time — {{ selectedTask?.folio }}</h2>
                        <button type="button" class="btn-close" aria-label="Close" @click="showTimeForm = false"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task_id" :value="selectedTask?.id">
                        <div v-if="notice" class="alert alert-danger py-2" role="alert">{{ notice }}</div>
                        <div class="mb-3">
                            <label class="form-label" for="time-collaborator">Collaborator</label>
                            <select id="time-collaborator" v-model="form.collaborator_id" class="form-select" required>
                                <option value="" disabled>Select a collaborator</option>
                                <option v-for="collaborator in options.collaborators" :key="collaborator.id" :value="collaborator.id">{{ collaborator.name }}</option>
                            </select>
                            <div v-if="fieldError('collaborator_id')" class="text-danger small mt-1">{{ fieldError('collaborator_id') }}</div>
                        </div>
                        <div>
                            <label class="form-label" for="time-hours">Hours</label>
                            <input id="time-hours" v-model="form.hours" class="form-control" type="number" min="0.01" max="120" step="0.01" required>
                            <div v-if="fieldError('hours')" class="text-danger small mt-1">{{ fieldError('hours') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="showTimeForm = false">Cancel</button>
                        <button class="btn btn-primary" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Save time' }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div v-if="showTimeForm" class="modal-backdrop show"></div>
    </template>
</template>
