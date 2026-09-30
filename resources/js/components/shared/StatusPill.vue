<script setup>
defineProps({
    active: {
        type: Boolean,
        required: true,
    },
    label: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <span class="status-pill" :class="{ finished: !active }">{{ label ?? (active ? 'Active' : 'Inactive') }}</span>
</template>
