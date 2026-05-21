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
    <form class="w-full space-y-4" @submit.prevent="onSubmit">
        <div>
            <label for="url" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">
                Link do YouTube
            </label>
            <input
                id="url"
                v-model="url"
                type="url"
                placeholder="https://www.youtube.com/playlist?list=... ou watch?v=..."
                class="w-full rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-3 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#706f6c] focus:outline-none focus:ring-2 focus:ring-[#f53003]/30 dark:focus:ring-[#FF4433]/30"
                :disabled="loading"
            />
        </div>

        <p v-if="localError" class="text-sm text-[#f53003] dark:text-[#FF4433]">
            {{ localError }}
        </p>

        <button
            type="submit"
            class="w-full rounded-lg bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] py-3 text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="loading"
        >
            {{ loading ? 'Carregando…' : 'Listar músicas' }}
        </button>
    </form>
</template>
