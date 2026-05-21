export async function apiFetch(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
    };

    const response = await fetch(path, { ...options, headers });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        const message =
            body.message ??
            (body.errors ? Object.values(body.errors).flat().join(' ') : null) ??
            `Request failed (${response.status})`;
        throw new Error(message);
    }

    return response.json();
}
