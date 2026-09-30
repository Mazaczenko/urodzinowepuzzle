<script setup>
import MuteButton from '@/Components/Game/MuteButton.vue';
import StarField from '@/Components/Game/StarField.vue';

defineProps({
    // Lock the page to the viewport, for screens that must not scroll (the puzzle board).
    fill: {
        type: Boolean,
        default: false,
    },
    preview: {
        type: Boolean,
        default: false,
    },
    mute: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="relative flex flex-col overflow-hidden bg-gradient-to-br from-night-900 via-night-800 to-night-950 font-sans text-white"
        :class="fill ? 'h-[100dvh] overscroll-none' : 'min-h-[100dvh]'"
    >
        <StarField />

        <div v-if="preview" class="relative z-20 bg-gold-400 px-4 py-1 text-center text-sm font-medium text-night-950">
            Podgląd — postęp gracza nie jest zapisywany.
            <a href="/admin" class="underline">Wróć do panelu</a>
        </div>

        <div v-if="mute" class="absolute right-3 top-3 z-20 sm:right-5 sm:top-5" :class="{ 'mt-7': preview }">
            <MuteButton />
        </div>

        <div class="relative z-10 flex min-h-0 flex-1 flex-col">
            <slot />
        </div>
    </div>
</template>
