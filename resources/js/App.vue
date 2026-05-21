<script setup>
import { onMounted, ref } from 'vue';
import { clearToken, fetchConfig, fetchUser, getToken, logout } from './api/auth';
import { previewPlaylist } from './api/downloads';
import AppFooter from './components/AppFooter.vue';
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
    const configResponse = await fetchConfig();
    config.value = configResponse.data;
    appName.value = configResponse.data.app_name ?? appName.value;

    if (!config.value.require_auth || !getToken()) {
        user.value = null;

        return;
    }

    const userResponse = await fetchUser();
    user.value = userResponse.data;
}

onMounted(async () => {
    try {
        await loadSession();
    } catch {
        clearToken();
        user.value = null;
    } finally {
        booting.value = false;
    }
});

const needsAuth = () => config.value?.require_auth && !user.value;

async function handleAuthenticated(authUser) {
    user.value = authUser;
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
        <main class="malu-shell flex min-h-dvh flex-col justify-center py-6">
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
                        :login-email="config?.login_email ?? 'malu@malu.com'"
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

                <AppFooter />
            </template>
        </main>
    </div>
</template>
