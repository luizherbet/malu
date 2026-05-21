import { apiFetch, clearToken, getToken, setToken } from './client';

export { clearToken, getToken, setToken };

export function fetchConfig() {
    return apiFetch('/api/config');
}

export function fetchUser() {
    const token = getToken();

    if (!token) {
        return Promise.resolve({ data: null });
    }

    return apiFetch('/api/auth/user');
}

export async function login(credentials) {
    const response = await apiFetch('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
        skipAuth: true,
    });

    setToken(response.data.token);

    return response;
}

export async function logout() {
    try {
        await apiFetch('/api/auth/logout', { method: 'POST' });
    } finally {
        clearToken();
    }
}
