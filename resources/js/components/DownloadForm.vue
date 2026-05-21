<script setup>
import { ref } from 'vue';

const emit = defineEmits(['preview']);

const url = ref('');
const loading = ref(false);
const localError = ref('');

function validate() {
    localError.value = '';

    if (!url.value.trim()) {
        localError.value = 'Cole um link do YouTube para continuar.';
        return false;
    }

    try {
        new URL(url.value.trim());
    } catch {
        localError.value = 'Informe uma URL válida.';
        return false;
    }

    return true;
}

async function onSubmit() {
    if (!validate()) {
        return;
    }

    loading.value = true;
    localError.value = '';

    try {
        emit('preview', { url: url.value.trim() });
    } finally {
        loading.value = false;
    }
}

function setError(message) {
    localError.value = message;
}

function setLoading(value) {
    loading.value = value;
}

defineExpose({ setError, setLoading });
</script>

<template>
    <form class="w-full space-y-5" @submit.prevent="onSubmit">
        <div>
            <label for="url" class="malu-label">Link do YouTube</label>
            <input
                id="url"
                v-model="url"
                type="url"
                inputmode="url"
                autocomplete="off"
                placeholder="Playlist ou vídeo"
                class="malu-input"
                :disabled="loading"
            />
            <p class="malu-muted mt-2">
                Cole o link da playlist ou de um álbum em um único vídeo.
            </p>
        </div>

        <p v-if="localError" class="malu-error">{{ localError }}</p>

        <button type="submit" class="malu-btn-primary" :disabled="loading">
            {{ loading ? 'Carregando…' : 'Listar músicas' }}
        </button>
    </form>
</template>
