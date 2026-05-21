<script setup>
import { onMounted, ref } from 'vue';
import { login } from '../api/auth';

const props = defineProps({
    loginEmail: {
        type: String,
        default: 'malu@malu.com',
    },
});

const emit = defineEmits(['authenticated']);

const loading = ref(false);
const error = ref('');
const form = ref({
    email: props.loginEmail,
    password: '',
});

onMounted(() => {
    form.value.email = props.loginEmail;
});

async function onSubmit() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await login({
            email: form.value.email,
            password: form.value.password,
        });

        emit('authenticated', data.user);
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
                Entrar
            </h2>
            <p class="malu-muted mt-1">
                Acesso restrito — use seu e-mail e senha.
            </p>
        </div>

        <form class="space-y-4" @submit.prevent="onSubmit">
            <div>
                <label for="email" class="malu-label">E-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    class="malu-input"
                    required
                    readonly
                />
            </div>

            <div>
                <label for="password" class="malu-label">Senha</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="malu-input"
                    required
                />
            </div>

            <p v-if="error" class="malu-error">{{ error }}</p>

            <button type="submit" class="malu-btn-primary" :disabled="loading">
                {{ loading ? 'Aguarde…' : 'Entrar' }}
            </button>
        </form>
    </div>
</template>
