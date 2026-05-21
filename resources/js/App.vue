<script setup>
import { onMounted, ref } from 'vue';
import { fetchConfig, fetchUser, logout } from './api/auth';
import { previewPlaylist } from './api/downloads';
import AppHeader from './components/AppHeader.vue';
import AuthPanel from './components/AuthPanel.vue';
import DownloadForm from './components/DownloadForm.vue';
import PlaylistTrackList from './components/PlaylistTrackList.vue';

const appName = ref(import.meta.env.VITE_APP_NAME ?? 'Malu');
const config = ref(null);
const user = ref(null);
const booting = ref(true);
const formRef = ref(null);
const playlist = ref(null);

async function loadSession() {
    const [configResponse, userResponse] = await Promise.all([
        fetchConfig(),
        fetchUser(),
    ]);

    config.value = configResponse.data;
    appName.value = configResponse.data.app_name ?? appName.value;
    user.value = userResponse.data;
}

onMounted(async () => {
    try {
        await loadSession();
    } finally {
        booting.value = false;
    }
});

const needsAuth = () => config.value?.require_auth && !user.value;

async function handleAuthenticated() {
    await loadSession();
}

async function handleLogout() {
    await logout();
    user.value = null;
    playlist.value = null;
}

async function handlePreview({ url }) {
    formRef.value?.setLoading(true);

    try {
        const { data } = await previewPlaylist(url);
        playlist.value = data;
    } catch (error) {
        if (error.status === 401) {
            user.value = null;
        }
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
    <div class="min-h-dvh bg-stone-50 dark:bg-stone-950">
        <main class="malu-shell flex min-h-dvh flex-col justify-center">
            <div v-if="booting" class="malu-card py-12 text-center">
                <p class="malu-muted">Carregando…</p>
            </div>

            <template v-else>
                <AppHeader
                    :app-name="appName"
                    :user="user"
                    @logout="handleLogout"
                />

                <div class="malu-card">
                    <AuthPanel
                        v-if="needsAuth()"
                        :allow-registration="config?.allow_registration ?? false"
                        @authenticated="handleAuthenticated"
                    />

                    <template v-else>
                        <DownloadForm
                            v-if="!playlist"
                            ref="formRef"
                            @preview="handlePreview"
                        />
                        <PlaylistTrackList
                            v-else
                            :source-url="playlist.source_url"
                            :tracks="playlist.tracks"
                            @back="reset"
                        />
                    </template>
                </div>

                <p
                    v-if="!needsAuth() && !booting"
                    class="malu-muted mt-4 text-center text-xs leading-relaxed"
                >
                    Uso pessoal. Respeite os direitos autorais das músicas.
                </p>
            </template>
        </main>
    </div>
</template>
