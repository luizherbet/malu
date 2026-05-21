<script setup>
import { ref } from 'vue';
import { login, register } from '../api/auth';

const props = defineProps({
    allowRegistration: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['authenticated']);

const mode = ref('login');
const loading = ref(false);
const error = ref('');
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

async function onSubmit() {
    loading.value = true;
    error.value = '';

    try {
        if (mode.value === 'login') {
            await login({
                email: form.value.email,
                password: form.value.password,
            });
        } else {
            await register({
                name: form.value.name,
                email: form.value.email,
                password: form.value.password,
                password_confirmation: form.value.password_confirmation,
            });
        }

        emit('authenticated');
    } catch (err) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="w-full space-y-5">
        <div class="text-center">
            <h2 class="text-xl font-semibold text-stone-900 dark:text-stone-100">
                {{ mode === 'login' ? 'Entrar' : 'Criar conta' }}
            </h2>
            <p class="malu-muted mt-1">
                Acesso necessário para usar o Malu neste servidor.
            </p>
        </div>

        <form class="space-y-4" @submit.prevent="onSubmit">
            <div v-if="mode === 'register'">
                <label for="name" class="malu-label">Nome</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    class="malu-input"
                    required
                />
            </div>

            <div>
                <label for="email" class="malu-label">E-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="malu-input"
                    required
                />
            </div>

            <div>
                <label for="password" class="malu-label">Senha</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
                    class="malu-input"
                    required
                />
            </div>

            <div v-if="mode === 'register'">
                <label for="password_confirmation" class="malu-label">Confirmar senha</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="malu-input"
                    required
                />
            </div>

            <p v-if="error" class="malu-error">{{ error }}</p>

            <button type="submit" class="malu-btn-primary" :disabled="loading">
                {{ loading ? 'Aguarde…' : mode === 'login' ? 'Entrar' : 'Cadastrar' }}
            </button>
        </form>

        <p v-if="allowRegistration" class="text-center text-sm">
            <button
                type="button"
                class="font-medium text-rose-600 hover:text-rose-500 dark:text-rose-400"
                @click="mode = mode === 'login' ? 'register' : 'login'"
            >
                {{ mode === 'login' ? 'Criar uma conta' : 'Já tenho conta' }}
            </button>
        </p>
    </div>
</template>
