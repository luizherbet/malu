<script setup>
const appName = import.meta.env.VITE_APP_NAME ?? 'Malu';
</script>

<template>
    <main class="min-h-screen flex flex-col items-center justify-center p-8">
        <h1 class="text-3xl font-semibold tracking-tight">{{ appName }}</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400 text-center max-w-md">
            Cole um link de vídeo ou áudio para baixar. A interface de download será adicionada nos próximos commits.
        </p>
    </main>
</template>
