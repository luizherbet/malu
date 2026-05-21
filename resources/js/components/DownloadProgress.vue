<script setup>
import { computed } from 'vue';

const props = defineProps({
    job: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['reset']);

const statusLabel = computed(() => {
    const labels = {
        queued: 'Na fila',
        processing: 'Baixando',
        done: 'Concluído',
        failed: 'Falhou',
    };
    return labels[props.job.status] ?? props.job.status;
});

const statusColor = computed(() => {
    const colors = {
        queued: 'text-[#706f6c]',
        processing: 'text-[#f8b803]',
        done: 'text-emerald-600 dark:text-emerald-400',
        failed: 'text-[#f53003] dark:text-[#FF4433]',
    };
    return colors[props.job.status] ?? '';
});

const showProgress = computed(() =>
    ['queued', 'processing'].includes(props.job.status),
);
</script>

<template>
    <div class="w-full space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Status</p>
                <p class="text-lg font-medium" :class="statusColor">{{ statusLabel }}</p>
            </div>
            <span
                v-if="showProgress"
                class="text-2xl font-semibold tabular-nums text-[#1b1b18] dark:text-[#EDEDEC]"
            >
                {{ job.progress }}%
            </span>
        </div>

        <div
            v-if="showProgress"
            class="h-2 w-full rounded-full bg-[#e3e3e0] dark:bg-[#3E3E3A] overflow-hidden"
        >
            <div
                class="h-full rounded-full bg-[#f53003] dark:bg-[#FF4433] transition-all duration-300"
                :style="{ width: `${job.progress}%` }"
            />
        </div>

        <p v-if="job.status === 'failed' && job.error" class="text-sm text-[#f53003] dark:text-[#FF4433]">
            {{ job.error }}
        </p>

        <p v-if="job.file_name && job.status === 'done'" class="text-sm text-[#706f6c] dark:text-[#A1A09A] truncate">
            {{ job.file_name }}
        </p>

        <a
            v-if="job.download_url"
            :href="job.download_url"
            class="flex w-full items-center justify-center rounded-lg bg-[#f53003] dark:bg-[#FF4433] text-white py-3 text-sm font-medium hover:opacity-90 transition-opacity"
            download
        >
            Baixar arquivo
        </a>

        <button
            type="button"
            class="w-full rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] py-2.5 text-sm text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#f5f5f4] dark:hover:bg-[#161615] transition-colors"
            @click="emit('reset')"
        >
            Novo download
        </button>
    </div>
</template>
