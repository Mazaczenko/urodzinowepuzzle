<script setup>
import DigitSlots from '@/Components/Game/DigitSlots.vue';
import { useGameAudio } from '@/Composables/useGameAudio';
import GameLayout from '@/Layouts/GameLayout.vue';
import { Fireworks } from '@fireworks-js/vue';
import { Head, Link } from '@inertiajs/vue3';
import { useClipboard, usePreferredReducedMotion } from '@vueuse/core';
import confetti from 'canvas-confetti';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    code: {
        type: String,
        required: true,
    },
    password: {
        type: String,
        default: null,
    },
    wishes: {
        type: String,
        default: '',
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
const { copy, copied } = useClipboard({ legacy: true, copiedDuring: 2500 });
const reducedMotion = usePreferredReducedMotion();

const digits = computed(() => props.code.split(''));
// The last digit arrives a moment after the page, continuing the reveal from the puzzle screen.
const shownDigits = ref(digits.value.slice(0, -1));
const celebrating = ref(false);
const wishesElement = ref(null);
const hasWishes = computed(() => props.wishes.replace(/<[^>]*>/g, '').trim() !== '');

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
        colors: ['#fbd77a', '#f6c453', '#f9a8d4', '#a5b4fc', '#ffffff'],
        disableForReducedMotion: true,
    };

    confetti({ ...options, angle: 60, origin: { x: 0, y: 0.7 } });
    confetti({ ...options, angle: 120, origin: { x: 1, y: 0.7 } });
}

/**
 * Type the wishes out letter by letter while keeping the formatting from the editor.
 */
function typeWishes() {
    const root = wishesElement.value;

    if (!root || reducedMotion.value === 'reduce') {
        return;
    }

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const textNodes = [];

    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    const letters = [];

    textNodes.forEach((node) => {
        const fragment = document.createDocumentFragment();

        [...node.textContent].forEach((character) => {
            const letter = document.createElement('span');

            letter.textContent = character;
            letter.style.opacity = '0';
            fragment.appendChild(letter);
            letters.push(letter);
        });

        node.replaceWith(fragment);
    });

    letters.forEach((letter, index) => later(() => (letter.style.opacity = '1'), 900 + index * 38));
}

onMounted(() => {
    playMusic(props.musicUrl, { fade: 3000 });
    typeWishes();

    later(() => (shownDigits.value = digits.value), 700);
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
                <p class="mt-2 text-lg text-white/80">Wszystkie obrazki ułożone. Oto Twój prezent.</p>
            </header>

            <section class="glass-card w-full px-3 py-7 text-center sm:px-8">
                <h2 class="text-sm font-medium uppercase tracking-widest text-white/70">Kod czeku BLIK</h2>

                <DigitSlots class="mt-4" :digits="shownDigits" :total="digits.length" large />

                <div class="mt-6">
                    <button type="button" class="btn-gold" @click="copy(code)">
                        {{ copied ? 'Skopiowano ✓' : 'Kopiuj kod' }}
                    </button>
                </div>

                <p v-if="password" class="mt-5 text-white/85">
                    Hasło do czeku:
                    <strong class="select-all text-gold-300">{{ password }}</strong>
                </p>

                <p class="mx-auto mt-4 max-w-md text-sm text-white/60">
                    Czekiem BLIK wypłacisz gotówkę w bankomacie albo zapłacisz w sklepie, podając ten kod zamiast
                    kodu z aplikacji.
                </p>
            </section>

            <section v-if="hasWishes" class="glass-card w-full px-6 py-7 sm:px-10">
                <!-- The typed copy is hidden from screen readers, which get the full text at once. -->
                <div class="sr-only" v-html="wishes" />
                <div
                    ref="wishesElement"
                    class="wishes font-display text-xl leading-relaxed text-white/95 sm:text-2xl"
                    aria-hidden="true"
                    v-html="wishes"
                />
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

<style scoped>
.wishes :deep(p + p),
.wishes :deep(ul),
.wishes :deep(ol) {
    margin-top: 0.75em;
}

.wishes :deep(h2),
.wishes :deep(h3) {
    margin-bottom: 0.4em;
    font-weight: 700;
    color: #fbd77a;
}

.wishes :deep(ul) {
    list-style: disc;
    padding-left: 1.25em;
}

.wishes :deep(ol) {
    list-style: decimal;
    padding-left: 1.25em;
}

.wishes :deep(span) {
    transition: opacity 0.25s ease;
}
</style>
