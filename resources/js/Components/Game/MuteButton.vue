<script setup>
import { useGameAudio } from '@/Composables/useGameAudio';

const { muted, toggleMute } = useGameAudio();
</script>

<template>
    <button
        type="button"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300"
        :aria-label="muted ? 'Włącz dźwięk' : 'Wycisz dźwięk'"
        :aria-pressed="muted"
        @click="toggleMute"
    >
        <svg
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M11 5 6 9H3v6h3l5 4V5Z" />
            <template v-if="muted">
                <path d="m16 9 5 6" />
                <path d="m21 9-5 6" />
            </template>
            <template v-else>
                <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                <path d="M18.5 5.5a9 9 0 0 1 0 13" />
            </template>
        </svg>
    </button>
</template>
