import { apiFetch } from './client';

export function createDownload({ url, format, quality }) {
    return apiFetch('/api/jobs', {
        method: 'POST',
        body: JSON.stringify({ url, format, quality }),
    });
}

export function fetchDownload(id) {
    return apiFetch(`/api/jobs/${id}`);
}
