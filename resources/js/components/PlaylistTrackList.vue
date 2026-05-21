<script setup>
import { onUnmounted, reactive, ref } from 'vue';
import { createDownload, fetchDownload } from '../api/downloads';

const props = defineProps({
    sourceUrl: {
        type: String,
        required: true,
    },
    tracks: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['back']);

const trackJobs = reactive({});
const pollTimers = ref({});

const terminalStatuses = ['done', 'failed'];

function trackKey(track) {
    return track.id;
}

function jobFor(track) {
    return trackJobs[trackKey(track)] ?? null;
}

function stopPolling(key) {
    if (pollTimers.value[key] !== undefined) {
        clearInterval(pollTimers.value[key]);
        delete pollTimers.value[key];
    }
}

function stopAllPolling() {
    Object.keys(pollTimers.value).forEach(stopPolling);
}

async function refreshJob(key, jobId) {
    const { data } = await fetchDownload(jobId);
    trackJobs[key] = data;

    if (terminalStatuses.includes(data.status)) {
        stopPolling(key);
    }
}

function startPolling(key, jobId) {
    stopPolling(key);

    refreshJob(key, jobId).catch(() => stopPolling(key));

    pollTimers.value[key] = setInterval(() => {
        refreshJob(key, jobId).catch(() => stopPolling(key));
    }, 2000);
}

async function downloadTrack(track) {
    const key = trackKey(track);
    const existing = jobFor(track);

    if (existing && ['queued', 'processing'].includes(existing.status)) {
        return;
    }

    trackJobs[key] = { status: 'queued', progress: 0 };

    try {
        const { data } = await createDownload({
            url: track.url,
            section: track.section,
        });

        trackJobs[key] = data;
        startPolling(key, data.id);
    } catch (error) {
        trackJobs[key] = {
            status: 'failed',
            progress: 0,
            error: error.message,
        };
    }
}

function buttonLabel(track) {
    const job = jobFor(track);

    if (!job || job.status === 'failed') {
        return 'Baixar';
    }

    if (job.status === 'queued') {
        return 'Na fila…';
    }

    if (job.status === 'processing') {
        return `${job.progress}%`;
    }

    if (job.status === 'done') {
        return 'Baixar MP3';
    }

    return 'Baixar';
}

function isBusy(track) {
    const job = jobFor(track);

    return job && ['queued', 'processing'].includes(job.status);
}

function downloadHref(track) {
    const job = jobFor(track);

    if (job?.status === 'done') {
        return job.download_url ?? job.tracks?.[0]?.url ?? null;
    }

    return null;
}

onUnmounted(stopAllPolling);
</script>

<template>
    <div class="w-full space-y-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                {{ tracks.length }} {{ tracks.length === 1 ? 'faixa' : 'faixas' }}
            </p>
            <button
                type="button"
                class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]"
                @click="emit('back')"
            >
                ← Outro link
            </button>
        </div>

        <ul class="max-h-[28rem] overflow-y-auto space-y-2 rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] p-2">
            <li
                v-for="track in tracks"
                :key="trackKey(track)"
                class="rounded-lg px-3 py-2.5 hover:bg-[#f5f5f4] dark:hover:bg-[#1f1f1d]"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="shrink-0 w-7 text-center text-xs tabular-nums text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        {{ track.index }}
                    </span>
                    <span class="flex-1 min-w-0 text-sm truncate" :title="track.title">
                        {{ track.title }}
                    </span>

                    <a
                        v-if="downloadHref(track)"
                        :href="downloadHref(track)"
                        class="shrink-0 rounded-lg bg-emerald-600 dark:bg-emerald-500 text-white px-3 py-1.5 text-xs font-medium hover:opacity-90"
                        download
                    >
                        Baixar MP3
                    </a>
                    <button
                        v-else
                        type="button"
                        class="shrink-0 rounded-lg bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] px-3 py-1.5 text-xs font-medium hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed min-w-[5.5rem]"
                        :disabled="isBusy(track)"
                        @click="downloadTrack(track)"
                    >
                        {{ buttonLabel(track) }}
                    </button>
                </div>
                <p
                    v-if="jobFor(track)?.status === 'failed' && jobFor(track)?.error"
                    class="mt-1.5 ml-10 text-xs text-[#f53003] dark:text-[#FF4433] truncate"
                    :title="jobFor(track).error"
                >
                    {{ jobFor(track).error }}
                </p>
            </li>
        </ul>

        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
            Clique em Baixar em cada música — o download é feito uma faixa por vez.
        </p>
    </div>
</template>
