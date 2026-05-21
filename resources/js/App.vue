<script setup>
import { ref } from 'vue';
import { previewPlaylist } from './api/downloads';
import DownloadForm from './components/DownloadForm.vue';
import PlaylistTrackList from './components/PlaylistTrackList.vue';

const appName = import.meta.env.VITE_APP_NAME ?? 'Malu';
const formRef = ref(null);
const playlist = ref(null);

async function handlePreview({ url }) {
    formRef.value?.setLoading(true);

    try {
        const { data } = await previewPlaylist(url);
        playlist.value = data;
    } catch (error) {
        formRef.value?.setError(error.message);
    } finally {
        formRef.value?.setLoading(false);
    }
}

function reset() {
    playlist.value = null;
}
</script>

<template>
    <main
        class="min-h-screen flex flex-col items-center justify-center p-6 lg:p-10 bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]"
    >
        <div class="w-full max-w-lg">
            <header class="text-center mb-8">
                <h1 class="text-3xl font-semibold tracking-tight">{{ appName }}</h1>
                <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A] text-sm">
                    Cole um link de playlist ou vídeo, escolha cada música e baixe em MP3.
                </p>
            </header>

            <div
                class="rounded-2xl border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
            >
                <DownloadForm v-if="!playlist" ref="formRef" @preview="handlePreview" />
                <PlaylistTrackList
                    v-else
                    :source-url="playlist.source_url"
                    :tracks="playlist.tracks"
                    @back="reset"
                />
            </div>
        </div>
    </main>
</template>
