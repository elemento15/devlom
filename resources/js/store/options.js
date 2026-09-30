import { ref } from 'vue';
import { api } from '../api';

export const options = ref({ clients: [], projects: [], collaborators: [] });

export async function loadOptions() {
    options.value = await api('/api/options');
}
