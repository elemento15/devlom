<script setup>
defineProps({
    listing: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['change']);
</script>

<template>
    <div v-if="listing.last_page > 1" class="d-flex justify-content-between align-items-center border-top px-3 py-3">
        <span class="small text-secondary">Page {{ listing.current_page }} of {{ listing.last_page }}</span>
        <div class="d-flex gap-2">
            <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="listing.current_page <= 1"
                @click="emit('change', listing.current_page - 1)"
            >
                Previous
            </button>
            <button
                class="btn btn-sm btn-outline-secondary"
                type="button"
                :disabled="listing.current_page >= listing.last_page"
                @click="emit('change', listing.current_page + 1)"
            >
                Next
            </button>
        </div>
    </div>
</template>
