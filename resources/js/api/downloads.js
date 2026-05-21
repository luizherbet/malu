import { apiFetch } from './client';

export function previewPlaylist(url) {
    return apiFetch('/api/playlists/preview', {
        method: 'POST',
        body: JSON.stringify({ url }),
    });
}

export function createDownload({ url, section = null }) {
    const payload = { url };

    if (section) {
        payload.section = section;
    }

    return apiFetch('/api/jobs', {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export function fetchDownload(id) {
    return apiFetch(`/api/jobs/${id}`);
}
