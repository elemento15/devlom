<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../../api';
import { useCrudSection } from '../../composables/useCrudSection';
import { options } from '../../store/options';
import Pagination from '../shared/Pagination.vue';
import StatusPill from '../shared/StatusPill.vue';
import TimeEntryModal from '../shared/TimeEntryModal.vue';

const {
    listing,
    form,
    notice,
    editing,
    showForm,
    busy,
    loadListing,
    addNew,
    editItem,
    saveItem,
    fieldError,
} = useCrudSection('tasks', {
    blankForm: () => ({ description: '', project_id: '' }),
    editValues: (item) => ({ description: item.description }),
});

const showTimeForm = ref(false);
const selectedTask = ref(null);

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
    showTimeForm.value = true;
}

async function handleTimeSaved() {
    showTimeForm.value = false;
    await loadListing();
}

onMounted(loadListing);
</script>

<template>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Tasks</h1>
            <div class="text-secondary small">{{ listing.total }} records</div>
        </div>
        <button class="btn btn-primary" type="button" @click="addNew">Add New</button>
    </div>

    <div v-if="notice" class="alert alert-danger" role="alert">{{ notice }}</div>

    <section v-if="showForm" class="content-card p-4 mb-4">
        <h2 class="h5 mb-3">{{ editing ? 'Edit' : 'Add' }} Task</h2>
        <form @submit.prevent="saveItem">
            <div class="row g-3">
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
                    <tr><th>Folio</th><th>Description</th><th>Project</th><th>Status</th><th>Time</th><th>Cost</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <tr v-for="item in listing.data" :key="item.id">
                        <td class="fw-medium">{{ item.folio }}</td>
                        <td>{{ item.description }}</td>
                        <td>{{ item.project?.name }}</td>
                        <td><StatusPill :active="item.status?.code !== 'FIN'" :label="item.status?.name" /></td>
                        <td>{{ Number(item.total_hours ?? 0).toFixed(2) }} h</td>
                        <td>{{ Number(item.total_cost ?? 0).toFixed(2) }}</td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="editItem(item)">Edit</button>
                                <button v-if="item.status?.code !== 'FIN'" class="btn btn-sm btn-outline-success" type="button" @click="finishTask(item)">Finish</button>
                                <button v-if="item.status?.code !== 'FIN'" class="btn btn-sm btn-outline-dark" type="button" @click="openTimeForm(item)">Add Time</button>
                                <button v-if="!item.time_entries_count" class="btn btn-sm btn-outline-danger" type="button" @click="deleteTask(item)">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="listing.data.length === 0">
                        <td colspan="7" class="py-5 text-center text-secondary">No tasks yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :listing="listing" @change="loadListing" />
    </section>

    <TimeEntryModal
        v-if="showTimeForm"
        :task="selectedTask"
        @close="showTimeForm = false"
        @saved="handleTimeSaved"
    />
</template>
