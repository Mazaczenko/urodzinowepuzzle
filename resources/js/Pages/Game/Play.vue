<script setup>
import JigsawBoard from '@/Components/Game/JigsawBoard.vue';
import MuteButton from '@/Components/Game/MuteButton.vue';
import PuzzleProgress from '@/Components/Game/PuzzleProgress.vue';
import { useGameAudio } from '@/Composables/useGameAudio';
import GameLayout from '@/Layouts/GameLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useWakeLock } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    puzzle: {
        type: Object,
        required: true,
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
    completionSoundUrl: {
        type: String,
        default: null,
    },
    advanceUrl: {
        type: String,
        required: true,
    },
    preview: {
        type: Boolean,
        default: false,
    },
});

const { playMusic, playSound, stopSound, sfx } = useGameAudio();
const wakeLock = useWakeLock();

// The server moves on to the next puzzle as soon as this one is solved, while the screen
// keeps showing the finished picture and its message until the player presses "Dalej". So the
// board and the progress on screen are local copies that follow the props only at those moments.
const board = ref({ ...props.puzzle, advanceUrl: props.advanceUrl });
const shownSolved = ref(props.solvedCount);

// playing → merging (picture slides together) → merged (message shows) → saved (progress is stored)
const phase = ref('playing');
const submitting = ref(false);
const failed = ref(false);

let solveSeconds = 0;
let advanced = false;

const isLast = computed(() => board.value.number >= props.totalPuzzles);

function onSolved(seconds) {
    solveSeconds = seconds;
    phase.value = 'merging';

    // The last picture is followed by the finale music instead.
    if (props.completionSoundUrl && !isLast.value) {
        playSound(props.completionSoundUrl);
    } else {
        sfx.complete();
    }
}

function onMerged() {
    phase.value = 'merged';

    // The last picture leads straight to the finale, so it waits until the message has been read.
    if (!isLast.value) {
        advance();
    }
}

/**
 * Tell the server the picture is done. It answers with the next puzzle, or with the finale
 * after the last picture. The preview only walks through the pictures.
 */
function advance() {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    failed.value = false;
    advanced = false;

    const options = {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (page) => {
            advanced = true;

            if (page.component === 'Game/Play') {
                shownSolved.value = page.props.solvedCount;
                phase.value = 'saved';
            }
        },
        onFinish: () => {
            submitting.value = false;
            failed.value = !advanced;
        },
    };

    if (props.preview) {
        router.get(board.value.advanceUrl, {}, options);
    } else {
        router.post(board.value.advanceUrl, { seconds: solveSeconds }, options);
    }
}

function next() {
    stopSound();
    board.value = { ...props.puzzle, advanceUrl: props.advanceUrl };
    phase.value = 'playing';
}

function reload() {
    window.location.reload();
}

onMounted(() => {
    playMusic(props.musicUrl);

    if (wakeLock.isSupported.value) {
        wakeLock.request('screen').catch(() => {});
    }
});

onBeforeUnmount(() => stopSound());
</script>

<template>
    <GameLayout fill :preview="preview">
        <Head :title="`Poziom ${board.number}`" />

        <div class="flex min-h-0 flex-1 flex-col gap-2 p-2 sm:gap-3 sm:p-4">
            <header class="grid grid-cols-[1fr_auto] items-center gap-2 sm:grid-cols-[1fr_auto_1fr]">
                <div class="order-1">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm backdrop-blur-md sm:text-base"
                    >
                        Poziom
                        <strong class="text-gold-300">{{ board.number }}</strong>
                        <span class="text-white/60">/ {{ totalPuzzles }}</span>
                    </span>
                </div>

                <PuzzleProgress
                    class="order-3 col-span-2 sm:order-2 sm:col-span-1"
                    :solved="shownSolved"
                    :total="totalPuzzles"
                />

                <div class="order-2 justify-self-end sm:order-3">
                    <MuteButton />
                </div>
            </header>

            <main class="glass-card relative min-h-0 flex-1 overflow-hidden">
                <JigsawBoard
                    :key="board.id"
                    :image-url="board.imageUrl"
                    @grab="sfx.pick()"
                    @connect="sfx.snap()"
                    @solved="onSolved"
                    @merged="onMerged"
                />

                <Transition
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="translate-y-4 opacity-0"
                    leave-active-class="pointer-events-none transition duration-200 ease-in"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="phase === 'merged' || phase === 'saved'"
                        class="absolute inset-x-3 bottom-3 top-3 flex items-end justify-center sm:bottom-5"
                    >
                        <div
                            class="flex max-h-full w-full max-w-2xl flex-col items-center gap-3 rounded-3xl border border-white/20 bg-night-950/85 px-5 py-4 text-center shadow-xl backdrop-blur-md sm:px-8 sm:py-6"
                            aria-live="polite"
                        >
                            <p
                                class="min-h-0 overflow-y-auto whitespace-pre-line font-display text-lg leading-relaxed text-white sm:text-2xl"
                            >
                                {{ board.message || 'Pięknie ułożone!' }}
                            </p>

                            <p v-if="failed" class="w-full text-sm text-pink-300">Nie udało się zapisać postępu.</p>

                            <button v-if="failed" type="button" class="btn-gold px-5 py-2 text-base" @click="reload">
                                Spróbuj ponownie
                            </button>
                            <button
                                v-else-if="phase === 'saved'"
                                type="button"
                                class="btn-gold px-5 py-2 text-base"
                                @click="next"
                            >
                                Dalej →
                            </button>
                            <button
                                v-else-if="isLast"
                                type="button"
                                class="btn-gold px-5 py-2 text-base"
                                :disabled="submitting"
                                @click="advance"
                            >
                                Do finału 🎉
                            </button>
                            <span v-else class="animate-pulse-soft text-sm text-gold-300">Zapisuję…</span>
                        </div>
                    </div>
                </Transition>
            </main>
        </div>
    </GameLayout>
</template>
