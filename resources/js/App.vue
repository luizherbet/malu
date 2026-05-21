<script setup>
import { onUnmounted, ref } from 'vue';
import { createDownload, fetchDownload } from './api/downloads';
import DownloadForm from './components/DownloadForm.vue';
import DownloadProgress from './components/DownloadProgress.vue';

const appName = import.meta.env.VITE_APP_NAME ?? 'Malu';
const formRef = ref(null);
const job = ref(null);
const pollTimer = ref(null);

const terminalStatuses = ['done', 'failed'];

function stopPolling() {
    if (pollTimer.value !== null) {
        clearInterval(pollTimer.value);
        pollTimer.value = null;
    }
}

async function refreshJob(id) {
    const { data } = await fetchDownload(id);
    job.value = data;

    if (terminalStatuses.includes(data.status)) {
        stopPolling();
    }
}

function startPolling(id) {
    stopPolling();

    refreshJob(id).catch(() => stopPolling());

    pollTimer.value = setInterval(() => {
        refreshJob(id).catch(() => stopPolling());
    }, 2000);
}

async function handleSubmit(payload) {
    try {
        const { data } = await createDownload(payload);
        job.value = data;
        startPolling(data.id);
    } catch (error) {
        formRef.value?.setError(error.message);
    }
}

function reset() {
    stopPolling();
    job.value = null;
}

onUnmounted(stopPolling);
</script>

<template>
    <main
        class="min-h-screen flex flex-col items-center justify-center p-6 lg:p-10 bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]"
    >
        <div class="w-full max-w-lg">
            <header class="text-center mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">{{ appName }}</h1>
                <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A] text-sm">
                    Cole o link e baixe vídeo ou áudio com um clique.
                </p>
            </header>

            <div
                class="rounded-2xl border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
            >
                <DownloadForm v-if="!job" ref="formRef" @submit="handleSubmit" />
                <DownloadProgress v-else :job="job" @reset="reset" />
            </div>
        </div>
    </main>
</template>
