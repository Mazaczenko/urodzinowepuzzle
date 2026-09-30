<script setup>
import GameLayout from '@/Layouts/GameLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    // Stay logged in by default, so the session does not run out halfway through the game.
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GameLayout>
        <Head title="Logowanie" />

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="glass-card w-full max-w-md px-6 py-9 sm:px-9">
                <div class="text-center">
                    <div class="text-5xl" aria-hidden="true">🧩</div>
                    <h1 class="mt-3 font-display text-3xl font-bold text-gold-300">Urodzinowe puzzle</h1>
                    <p class="mt-2 text-white/70">Zaloguj się, żeby odebrać niespodziankę.</p>
                </div>

                <div v-if="status" class="mt-4 text-center text-sm font-medium text-gold-300">
                    {{ status }}
                </div>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-sm font-medium text-white/80">E-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full rounded-xl border-white/20 bg-white/10 px-4 py-3 text-white placeholder-white/40 focus:border-gold-300 focus:ring-gold-300"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-pink-300">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-white/80">Hasło</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full rounded-xl border-white/20 bg-white/10 px-4 py-3 text-white placeholder-white/40 focus:border-gold-300 focus:ring-gold-300"
                            required
                            autocomplete="current-password"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-sm text-pink-300">{{ form.errors.password }}</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-white/70">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            name="remember"
                            class="rounded border-white/30 bg-white/10 text-gold-500 focus:ring-gold-300 focus:ring-offset-night-900"
                        />
                        Zapamiętaj mnie
                    </label>

                    <button type="submit" class="btn-gold w-full" :disabled="form.processing">Wchodzę</button>
                </form>
            </div>
        </main>
    </GameLayout>
</template>
