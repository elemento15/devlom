<script setup>
import { onMounted } from 'vue';
import { useCrudSection } from '../../composables/useCrudSection';
import Pagination from '../shared/Pagination.vue';
import StatusPill from '../shared/StatusPill.vue';

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
    toggleActive,
    fieldError,
} = useCrudSection('clients', {
    blankForm: () => ({ name: '', rfc: '' }),
    editValues: (item) => ({ name: item.name, rfc: item.rfc }),
});

onMounted(loadListing);
</script>

<template>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Clients</h1>
            <div class="text-secondary small">{{ listing.total }} records</div>
        </div>
        <button class="btn btn-primary" type="button" @click="addNew">Add New</button>
    </div>

    <div v-if="notice" class="alert alert-danger" role="alert">{{ notice }}</div>

    <section v-if="showForm" class="content-card p-4 mb-4">
        <h2 class="h5 mb-3">{{ editing ? 'Edit' : 'Add' }} Client</h2>
        <form @submit.prevent="saveItem">
            <div class="row g-3">
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
                    <tr><th>Name</th><th>RFC</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <tr v-for="item in listing.data" :key="item.id">
                        <td class="fw-medium">{{ item.name }}</td>
                        <td>{{ item.rfc }}</td>
                        <td><StatusPill :active="item.active" /></td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <button class="btn btn-sm btn-outline-primary" type="button" @click="editItem(item)">Edit</button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" @click="toggleActive(item)">
                                    {{ item.active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="listing.data.length === 0">
                        <td colspan="4" class="py-5 text-center text-secondary">No clients yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Pagination :listing="listing" @change="loadListing" />
    </section>
</template>
