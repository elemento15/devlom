let unauthorizedHandler = null;

export function setUnauthorizedHandler(handler) {
    unauthorizedHandler = handler;
}

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export async function api(url, method = 'GET', data = null) {
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
        if (response.status === 401 && unauthorizedHandler) unauthorizedHandler();
        const error = new Error(result?.message ?? 'Something went wrong.');
        error.validation = result?.errors ?? {};
        throw error;
    }

    return result;
}
