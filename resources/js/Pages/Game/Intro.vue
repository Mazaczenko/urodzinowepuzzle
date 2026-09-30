<script setup>
import { useGameAudio } from '@/Composables/useGameAudio';
import GameLayout from '@/Layouts/GameLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import gsap from 'gsap';
import { onMounted, ref } from 'vue';

const props = defineProps({
    solvedCount: {
        type: Number,
        required: true,
    },
    totalPuzzles: {
        type: Number,
        required: true,
    },
    musicUrl: {
        type: String,
        default: null,
    },
});

const { playMusic } = useGameAudio();
const card = ref(null);
const starting = ref(false);

/**
 * The click doubles as the gesture browsers require before audio may play.
 */
function start() {
    starting.value = true;
    playMusic(props.musicUrl);
    router.visit(route('game.play'), { onFinish: () => (starting.value = false) });
}

onMounted(() => {
    gsap.from(card.value.children, { y: 24, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power2.out' });
});
</script>

<template>
    <GameLayout mute>
        <Head title="Niespodzianka" />

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div ref="card" class="glass-card w-full max-w-xl px-6 py-10 text-center sm:px-10">
                <div class="text-6xl" aria-hidden="true">🧩</div>

                <h1 class="mt-4 font-display text-4xl font-bold text-gold-300 sm:text-5xl">Wszystkiego najlepszego!</h1>

                <p class="mt-5 text-lg leading-relaxed text-white/85">
                    Mamy dla Ciebie prezent, ale trzeba na niego trochę zapracować. Przed Tobą
                    {{ totalPuzzles }} obrazków do ułożenia. Każdy odsłoni jedną cyfrę tajemniczego kodu.
                </p>

                <p v-if="solvedCount > 0" class="mt-3 text-white/70">
                    Masz już {{ solvedCount }} z {{ totalPuzzles }} cyfr. Gramy dalej?
                </p>

                <div class="mt-8">
                    <button type="button" class="btn-gold" :disabled="starting" @click="start">
                        {{ solvedCount > 0 ? 'Gramy dalej' : 'Zaczynamy' }}
                    </button>
                </div>

                <p class="mt-4 text-sm text-white/50">Włącz dźwięk, będzie muzyka 🎵</p>
            </div>
        </main>
    </GameLayout>
</template>
