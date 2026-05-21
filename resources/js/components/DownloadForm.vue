<script setup>
import { computed, ref } from 'vue';

const emit = defineEmits(['submit']);

const url = ref('');
const format = ref('mp4');
const quality = ref('best');
const submitting = ref(false);
const localError = ref('');

const showQuality = computed(() => format.value === 'mp4');

function validate() {
    localError.value = '';

    if (!url.value.trim()) {
        localError.value = 'Cole um link para continuar.';
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

    submitting.value = true;
    localError.value = '';

    try {
        emit('submit', {
            url: url.value.trim(),
            format: format.value,
            quality: showQuality.value ? quality.value : 'best',
        });
    } finally {
        submitting.value = false;
    }
}

function setError(message) {
    localError.value = message;
}

defineExpose({ setError });
</script>

<template>
    <form class="w-full space-y-4" @submit.prevent="onSubmit">
        <div>
            <label for="url" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">
                Link do vídeo ou áudio
            </label>
            <input
                id="url"
                v-model="url"
                type="url"
                placeholder="https://www.youtube.com/watch?v=..."
                class="w-full rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-3 text-sm text-[#1b1b18] dark:text-[#EDEDEC] placeholder:text-[#706f6c] focus:outline-none focus:ring-2 focus:ring-[#f53003]/30 dark:focus:ring-[#FF4433]/30"
                :disabled="submitting"
            />
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="format" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">
                    Formato
                </label>
                <select
                    id="format"
                    v-model="format"
                    class="w-full rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-2.5 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-[#f53003]/30"
                    :disabled="submitting"
                >
                    <option value="mp4">Vídeo (MP4)</option>
                    <option value="mp3">Áudio (MP3)</option>
                </select>
            </div>

            <div v-show="showQuality">
                <label for="quality" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1.5">
                    Qualidade
                </label>
                <select
                    id="quality"
                    v-model="quality"
                    class="w-full rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-2.5 text-sm text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-[#f53003]/30"
                    :disabled="submitting"
                >
                    <option value="best">Melhor disponível</option>
                    <option value="1080p">Até 1080p</option>
                    <option value="720p">Até 720p</option>
                </select>
            </div>
        </div>

        <p v-if="localError" class="text-sm text-[#f53003] dark:text-[#FF4433]">
            {{ localError }}
        </p>

        <button
            type="submit"
            class="w-full rounded-lg bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] py-3 text-sm font-medium hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="submitting"
        >
            {{ submitting ? 'Enviando…' : 'Baixar' }}
        </button>
    </form>
</template>
