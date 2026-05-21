<script setup>
import { onUnmounted, reactive, ref } from 'vue';
import { createDownload, downloadJobFile, fetchDownload } from '../api/downloads';

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
const downloadingFile = ref({});

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

    return 'Baixar';
}

function isBusy(track) {
    const job = jobFor(track);

    return job && ['queued', 'processing'].includes(job.status);
}

function isDone(track) {
    return jobFor(track)?.status === 'done';
}

function jobId(track) {
    return jobFor(track)?.id ?? null;
}

async function saveTrackFile(track) {
    const key = trackKey(track);
    const id = jobId(track);

    if (!id || downloadingFile.value[key]) {
        return;
    }

    downloadingFile.value[key] = true;

    try {
        await downloadJobFile(id);
    } catch (error) {
        const job = jobFor(track);

        if (job) {
            trackJobs[key] = {
                ...job,
                status: 'failed',
                error: error.message,
            };
        }
    } finally {
        downloadingFile.value[key] = false;
    }
}

function progressWidth(track) {
    const job = jobFor(track);

    if (job?.status === 'processing') {
        return `${job.progress}%`;
    }

    return '0%';
}

onUnmounted(stopAllPolling);
</script>

<template>
    <div class="w-full space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="malu-muted font-medium">
                {{ tracks.length }} {{ tracks.length === 1 ? 'faixa' : 'faixas' }}
            </p>
            <button type="button" class="malu-btn-ghost" @click="emit('back')">
                ← Outro link
            </button>
        </div>

        <ul
            class="max-h-[min(28rem,60dvh)] space-y-2 overflow-y-auto overscroll-contain rounded-xl border border-stone-200 p-2 dark:border-stone-700"
        >
            <li
                v-for="track in tracks"
                :key="trackKey(track)"
                class="rounded-xl bg-stone-50 p-3 dark:bg-stone-950/60"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-stone-200 text-xs font-semibold tabular-nums text-stone-600 dark:bg-stone-800 dark:text-stone-300"
                        >
                            {{ track.index }}
                        </span>
                        <span class="min-w-0 flex-1 text-sm leading-snug font-medium text-stone-800 dark:text-stone-100">
                            {{ track.title }}
                        </span>
                    </div>

                    <div class="flex shrink-0 sm:pl-0 pl-11">
                        <button
                            v-if="isDone(track)"
                            type="button"
                            class="malu-btn-success w-full sm:w-auto"
                            :disabled="downloadingFile[trackKey(track)]"
                            @click="saveTrackFile(track)"
                        >
                            {{ downloadingFile[trackKey(track)] ? 'Salvando…' : 'Baixar MP3' }}
                        </button>
                        <button
                            v-else
                            type="button"
                            class="malu-btn-primary w-full sm:min-w-[6.5rem] sm:w-auto"
                            :disabled="isBusy(track)"
                            @click="downloadTrack(track)"
                        >
                            {{ buttonLabel(track) }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="isBusy(track)"
                    class="mt-2.5 ml-11 h-1 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-800"
                >
                    <div
                        class="h-full rounded-full bg-rose-500 transition-all duration-300"
                        :style="{ width: progressWidth(track) }"
                    />
                </div>

                <p
                    v-if="jobFor(track)?.status === 'failed' && jobFor(track)?.error"
                    class="malu-error mt-2 ml-11 line-clamp-3"
                    :title="jobFor(track).error"
                >
                    {{ jobFor(track).error }}
                </p>
            </li>
        </ul>

        <p class="malu-muted text-xs leading-relaxed">
            Toque em Baixar em cada música. Os downloads são feitos um por vez na fila.
        </p>
    </div>
</template>
