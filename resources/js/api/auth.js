import { apiFetch } from './client';

export function fetchConfig() {
    return apiFetch('/api/config');
}

export function fetchUser() {
    return apiFetch('/api/auth/user');
}

export function login(credentials) {
    return apiFetch('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify(credentials),
    });
}

export function register(payload) {
    return apiFetch('/api/auth/register', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export function logout() {
    return apiFetch('/api/auth/logout', { method: 'POST' });
}
