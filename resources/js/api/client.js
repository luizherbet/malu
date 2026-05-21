const TOKEN_KEY = 'malu_token';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
}

export async function apiFetch(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...options.headers,
    };

    if (!options.skipAuth) {
        const token = getToken();

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }
    }

    const response = await fetch(path, {
        ...options,
        headers,
    });

    if (response.status === 401) {
        clearToken();
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

function filenameFromDisposition(header) {
    if (!header) {
        return 'track.mp3';
    }

    const match = header.match(/filename="?([^"]+)"?/i);

    return match?.[1] ?? 'track.mp3';
}

export async function apiDownload(path) {
    const token = getToken();
    const headers = { Accept: 'application/octet-stream' };

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(path, { headers });

    if (response.status === 401) {
        clearToken();
        throw new Error('Authentication required.');
    }

    if (!response.ok) {
        throw new Error(`Download failed (${response.status})`);
    }

    const blob = await response.blob();
    const filename = filenameFromDisposition(response.headers.get('Content-Disposition'));

    return { blob, filename };
}
