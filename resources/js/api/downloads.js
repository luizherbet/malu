import { apiDownload, apiFetch } from './client';

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

export async function downloadJobFile(downloadId) {
    const { blob, filename } = await apiDownload(`/api/jobs/${downloadId}/file`);

    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
}
