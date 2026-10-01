<script setup>
import TypedText from '@/Components/Game/TypedText.vue';
import { useGameAudio } from '@/Composables/useGameAudio';
import GameLayout from '@/Layouts/GameLayout.vue';
import { Fireworks } from '@fireworks-js/vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePreferredReducedMotion } from '@vueuse/core';
import { themeColor } from '@/theme';
import confetti from 'canvas-confetti';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    finaleText: {
        type: String,
        default: '',
    },
    // The last picture, which shows the whole gift.
    pictureUrl: {
        type: String,
        default: null,
    },
    musicUrl: {
        type: String,
        default: null,
    },
    preview: {
        type: Boolean,
        default: false,
    },
});

const { playMusic } = useGameAudio();
const reducedMotion = usePreferredReducedMotion();

const celebrating = ref(false);
const hasText = computed(() => props.finaleText.replace(/<[^>]*>/g, '').trim() !== '');

const fireworksOptions = {
    intensity: 18,
    particles: 70,
    explosion: 6,
    opacity: 0.45,
    traceSpeed: 5,
    acceleration: 1.02,
    friction: 0.97,
    gravity: 1.4,
    rocketsPoint: { min: 15, max: 85 },
    delay: { min: 35, max: 70 },
    mouse: { click: false, move: false, max: 1 },
    sound: { enabled: false },
};

let timers = [];

const later = (callback, delay) => timers.push(setTimeout(callback, delay));

function burst() {
    const options = {
        particleCount: 90,
        spread: 75,
        colors: [themeColor('gold-300'), themeColor('gold-400'), themeColor('glow-a'), themeColor('glow-b'), '#ffffff'],
        disableForReducedMotion: true,
    };

    confetti({ ...options, angle: 60, origin: { x: 0, y: 0.7 } });
    confetti({ ...options, angle: 120, origin: { x: 1, y: 0.7 } });
}

onMounted(() => {
    playMusic(props.musicUrl, { fade: 3000 });

    later(() => {
        celebrating.value = true;
        burst();
    }, 1500);
    later(burst, 2600);
});

onBeforeUnmount(() => {
    timers.forEach(clearTimeout);
    timers = [];
});
</script>

<template>
    <GameLayout mute :preview="preview">
        <Head title="Sto lat!" />

        <Fireworks
            v-if="celebrating && reducedMotion !== 'reduce'"
            class="pointer-events-none fixed inset-0 z-0 h-full w-full"
            :options="fireworksOptions"
            aria-hidden="true"
        />

        <main class="relative z-10 mx-auto flex w-full max-w-2xl flex-1 flex-col items-center gap-6 px-3 py-10 sm:px-4 sm:py-14">
            <header class="text-center">
                <div class="text-6xl" aria-hidden="true">🎂</div>
                <h1 class="mt-3 font-display text-5xl font-bold text-gold-300 sm:text-6xl">Sto lat!</h1>
                <p class="mt-2 text-lg text-white/80">Wszystkie obrazki ułożone!</p>
            </header>

            <img
                v-if="pictureUrl"
                :src="pictureUrl"
                alt=""
                class="w-full max-w-md rounded-3xl border border-white/20 shadow-2xl shadow-black/40"
                style="aspect-ratio: 1 / 1"
            />

            <section v-if="hasText" class="glass-card w-full px-6 py-7 sm:px-10">
                <TypedText :html="finaleText" class="font-display text-xl leading-relaxed text-white/95 sm:text-2xl" />
            </section>

            <Link
                v-if="!preview"
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-white/50 underline hover:text-white"
            >
                Wyloguj
            </Link>
        </main>
    </GameLayout>
</template>

