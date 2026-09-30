<script setup>
import GameLayout from '@/Layouts/GameLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GameLayout>
        <Head title="Niespodzianka w drodze" />

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div class="glass-card max-w-md px-8 py-10 text-center">
                <div class="text-6xl" aria-hidden="true">🎁</div>
                <h1 class="mt-4 font-display text-3xl font-bold text-gold-300">Niespodzianka jeszcze się pakuje</h1>
                <p class="mt-3 text-lg text-white/80">Zajrzyj tu trochę później. Będzie warto!</p>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="mt-8 text-sm text-white/60 underline hover:text-white"
                >
                    Wyloguj
                </Link>
            </div>
        </main>
    </GameLayout>
</template>
