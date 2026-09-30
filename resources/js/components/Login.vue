<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { signIn } from '../store/auth';
import { loadOptions } from '../store/options';

const router = useRouter();
const login = reactive({ email: '', password: '' });
const busy = ref(false);
const notice = ref('');

async function handleSubmit() {
    busy.value = true;
    notice.value = '';
    try {
        await signIn(login);
        await loadOptions();
        await router.push({ name: 'clients' });
    } catch (error) {
        notice.value = error.message;
    } finally {
        busy.value = false;
        login.password = '';
    }
}
</script>

<template>
    <main class="login-page">
        <form class="login-card" @submit.prevent="handleSubmit">
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
</template>
