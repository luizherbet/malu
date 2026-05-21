function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function apiFetch(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
    };

    const method = (options.method ?? 'GET').toUpperCase();

    if (method !== 'GET' && method !== 'HEAD') {
        headers['X-CSRF-TOKEN'] = csrfToken();
    }

    const response = await fetch(path, {
        ...options,
        headers,
        credentials: 'same-origin',
    });

    if (response.status === 401) {
        const body = await response.json().catch(() => ({}));
        const error = new Error(body.message ?? 'Authentication required.');
        error.status = 401;
        throw error;
    }

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        const message =
            body.message ??
            (body.errors ? Object.values(body.errors).flat().join(' ') : null) ??
            `Request failed (${response.status})`;
        throw new Error(message);
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}
