<script setup>
import { reactive, ref } from 'vue';
import { api } from '../../api';
import { options } from '../../store/options';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({ collaborator_id: '', hours: '' });
const errors = ref({});
const notice = ref('');
const busy = ref(false);

function fieldError(field) {
    return errors.value?.[field]?.[0] ?? '';
}

async function saveTime() {
    busy.value = true;
    errors.value = {};
    notice.value = '';
    try {
        await api('/api/time-entries', 'POST', {
            task_id: props.task.id,
            collaborator_id: form.collaborator_id,
            hours: form.hours,
        });
        emit('saved');
    } catch (error) {
        errors.value = error.validation;
        notice.value = error.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="modal d-block" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="time-modal-title" @click.self="emit('close')">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" @submit.prevent="saveTime">
                <div class="modal-header">
                    <h2 id="time-modal-title" class="modal-title fs-5">Add Time — {{ task?.folio }}</h2>
                    <button type="button" class="btn-close" aria-label="Close" @click="emit('close')"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="task_id" :value="task?.id">
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
                    <button type="button" class="btn btn-outline-secondary" @click="emit('close')">Cancel</button>
                    <button class="btn btn-primary" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Save time' }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-backdrop show"></div>
</template>
