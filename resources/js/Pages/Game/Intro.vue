<script setup>
import TypedText from '@/Components/Game/TypedText.vue';
import { useGameAudio } from '@/Composables/useGameAudio';
import GameLayout from '@/Layouts/GameLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import gsap from 'gsap';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    introText: {
        type: String,
        default: '',
    },
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
    startUrl: {
        type: String,
        required: true,
    },
    preview: {
        type: Boolean,
        default: false,
    },
});

const { playMusic } = useGameAudio();
const card = ref(null);
const starting = ref(false);
const hasText = computed(() => props.introText.replace(/<[^>]*>/g, '').trim() !== '');

function start() {
    starting.value = true;
    // Also unlocks audio if the browser held it back until now.
    playMusic(props.musicUrl);
    router.visit(props.startUrl, { onFinish: () => (starting.value = false) });
}

onMounted(() => {
    // Right after logging in the click on "Zaloguj" lets the music start at once. Otherwise
    // the browser holds it until the first tap or click, and Howler starts it then.
    playMusic(props.musicUrl);

    gsap.from(card.value.children, { y: 24, opacity: 0, duration: 0.7, stagger: 0.12, ease: 'power2.out' });
});
</script>

<template>
    <GameLayout mute :preview="preview">
        <Head title="Niespodzianka" />

        <main class="flex flex-1 items-center justify-center px-4 py-10">
            <div ref="card" class="glass-card w-full max-w-2xl px-6 py-10 text-center sm:px-10">
                <div class="text-6xl" aria-hidden="true">🎂</div>

                <TypedText
                    v-if="hasText"
                    :html="introText"
                    :delay="solvedCount > 0 ? 0 : 700"
                    :speed="solvedCount > 0 ? 0 : 38"
                    class="mt-5 font-display text-xl leading-relaxed text-white/95 sm:text-2xl"
                />

                <h1 v-if="!hasText" class="mt-4 font-display text-4xl font-bold text-gold-300 sm:text-5xl">
                    Wszystkiego najlepszego!
                </h1>

                <p v-if="!hasText" class="mt-5 text-lg leading-relaxed text-white/85">
                    Mamy dla Ciebie niespodziankę, ale trzeba na nią trochę zapracować. Przed Tobą
                    {{ totalPuzzles }} obrazków do ułożenia, a przy każdym czeka na Ciebie kilka słów.
                </p>

                <p v-if="solvedCount > 0" class="mt-3 text-white/70">
                    Masz już {{ solvedCount }} z {{ totalPuzzles }} obrazków. Gramy dalej?
                </p>

                <div class="mt-8">
                    <button type="button" class="btn-gold" :disabled="starting" @click="start">
                        {{ solvedCount > 0 ? 'Gramy dalej' : 'Zaczynamy' }}
                    </button>
                </div>

                <p class="mt-4 text-sm text-white/50">Podkręć dźwięk, gra muzyka 🎵</p>
            </div>
        </main>
    </GameLayout>
</template>
