<script setup>
import { setTheme, THEMES, useTheme } from '@/theme';
import { onClickOutside, onKeyStroke } from '@vueuse/core';
import { ref, toRef } from 'vue';

const props = defineProps({
    preview: {
        type: Boolean,
        default: false,
    },
});

const theme = useTheme(toRef(props, 'preview'));
const open = ref(false);
const root = ref(null);

onClickOutside(root, () => (open.value = false));
onKeyStroke('Escape', () => (open.value = false));

function choose(value) {
    setTheme(value, props.preview);
    open.value = false;
}
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-md transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-gold-300"
            aria-label="Zmień styl"
            aria-haspopup="true"
            :aria-expanded="open"
            @click="open = !open"
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
                <path
                    d="M12 22a10 10 0 1 1 10-10c0 2.2-1.8 3.5-4 3.5h-2a2 2 0 0 0-1.5 3.3c.6.7.4 1.7-.4 2.1-.6.3-1.3.1-2.1.1Z"
                />
                <circle cx="7.5" cy="10.5" r="1" fill="currentColor" />
                <circle cx="10.5" cy="6.5" r="1" fill="currentColor" />
                <circle cx="15.5" cy="7" r="1" fill="currentColor" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="absolute right-0 top-full z-30 mt-2 w-44 overflow-hidden rounded-2xl border border-white/20 bg-night-950/90 p-1 text-left shadow-xl backdrop-blur-md"
                role="menu"
            >
                <p class="px-3 pb-1 pt-2 text-xs font-medium uppercase tracking-wider text-white/50">Styl</p>
                <button
                    v-for="item in THEMES"
                    :key="item.value"
                    type="button"
                    role="menuitemradio"
                    :aria-checked="item.value === theme.value"
                    class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm text-white transition hover:bg-white/10 focus:bg-white/10 focus:outline-none"
                    @click="choose(item.value)"
                >
                    <span class="w-5 text-center" aria-hidden="true">{{ item.sparkle }}</span>
                    <span class="flex-1">{{ item.label }}</span>
                    <span v-if="item.value === theme.value" class="text-gold-300" aria-hidden="true">✓</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
