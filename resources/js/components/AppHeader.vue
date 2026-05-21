<script setup>
defineProps({
    appName: {
        type: String,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['logout']);
</script>

<template>
    <header class="mb-6 flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl dark:text-stone-50">
                {{ appName }}
            </h1>
            <p class="malu-muted mt-1.5 leading-relaxed">
                Playlist do YouTube em MP3 — escolha faixa por faixa.
            </p>
        </div>

        <div v-if="user" class="shrink-0 text-right">
            <p class="max-w-[8rem] truncate text-xs font-medium text-stone-700 sm:max-w-[10rem] dark:text-stone-300">
                {{ user.name }}
            </p>
            <button type="button" class="malu-btn-ghost mt-0.5 text-xs" @click="emit('logout')">
                Sair
            </button>
        </div>
    </header>
</template>
