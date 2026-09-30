import { ref } from 'vue';
import { api, setUnauthorizedHandler } from '../api';

export const user = ref(null);

let currentUserPromise = null;

export function fetchCurrentUser() {
    if (!currentUserPromise) {
        currentUserPromise = api('/api/user')
            .then((result) => {
                user.value = result.user;
            })
            .catch(() => {
                user.value = null;
            });
    }

    return currentUserPromise;
}

export async function signIn(credentials) {
    const result = await api('/api/login', 'POST', credentials);
    user.value = result.user;
    currentUserPromise = Promise.resolve();
}

export async function signOut() {
    try {
        await api('/api/logout', 'POST');
    } finally {
        user.value = null;
        currentUserPromise = null;
    }
}

setUnauthorizedHandler(() => {
    user.value = null;
    currentUserPromise = null;
});
