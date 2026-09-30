<script setup>
import MuteButton from '@/Components/Game/MuteButton.vue';
import StarField from '@/Components/Game/StarField.vue';
import ThemeSwitcher from '@/Components/Game/ThemeSwitcher.vue';
import { themeColor, useTheme } from '@/theme';
import { toRef, watchEffect } from 'vue';

const props = defineProps({
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

const theme = useTheme(toRef(props, 'preview'));

// On <html>, so the page behind the layout and the browser bar follow the theme too.
watchEffect(() => {
    document.documentElement.dataset.theme = theme.value.value;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColor('night-900'));
});
</script>

<template>
    <div
        class="relative flex flex-col overflow-hidden bg-gradient-to-br from-night-900 via-night-800 to-night-950 font-sans text-white"
        :class="fill ? 'h-[100dvh] overscroll-none' : 'min-h-[100dvh]'"
    >
        <div class="theme-pattern pointer-events-none absolute inset-0" aria-hidden="true" />
        <StarField :key="theme.value" :sparkle="theme.sparkle" />

        <div v-if="preview" class="relative z-20 bg-gold-400 px-4 py-1 text-center text-sm font-medium text-night-950">
            Podgląd — postęp gracza nie jest zapisywany, a zmiana stylu jest tylko na próbę.
            <a href="/admin" class="underline">Wróć do panelu</a>
        </div>

        <!-- The puzzle board has these in its own header. -->
        <div v-if="!fill" class="absolute right-3 top-3 z-20 flex gap-2 sm:right-5 sm:top-5" :class="{ 'mt-7': preview }">
            <ThemeSwitcher :preview="preview" />
            <MuteButton v-if="mute" />
        </div>

        <div class="relative z-10 flex min-h-0 flex-1 flex-col">
            <slot />
        </div>
    </div>
</template>
