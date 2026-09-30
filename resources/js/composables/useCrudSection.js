import { reactive, ref } from 'vue';
import { api } from '../api';
import { loadOptions } from '../store/options';

export function useCrudSection(section, { blankForm, editValues }) {
    const listing = ref({ data: [], current_page: 1, last_page: 1, total: 0 });
    const page = ref(1);
    const form = reactive({});
    const errors = ref({});
    const notice = ref('');
    const editing = ref(null);
    const showForm = ref(false);
    const busy = ref(false);

    async function loadListing(targetPage = page.value) {
        page.value = targetPage;
        listing.value = await api(`/api/${section}?page=${targetPage}`);
    }

    function addNew() {
        Object.assign(form, blankForm());
        editing.value = null;
        errors.value = {};
        notice.value = '';
        showForm.value = true;
    }

    function editItem(item) {
        Object.assign(form, editValues(item));
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
            const url = editing.value ? `/api/${section}/${editing.value}` : `/api/${section}`;
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
            await api(`/api/${section}/${item.id}/toggle`, 'PATCH');
            await Promise.all([loadListing(), loadOptions()]);
        } catch (error) {
            notice.value = error.message;
        }
    }

    function fieldError(field) {
        return errors.value?.[field]?.[0] ?? '';
    }

    return {
        listing,
        page,
        form,
        errors,
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
    };
}
