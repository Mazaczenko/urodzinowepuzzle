<script setup>
import { useGameAudio } from '@/Composables/useGameAudio';
import { themeColor } from '@/theme';
import confetti from 'canvas-confetti';
import gsap from 'gsap';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    solved: {
        type: Number,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
});

const { sfx } = useGameAudio();
const slots = ref([]);

/**
 * Flip a freshly finished picture's slot and throw a little confetti from it.
 */
function celebrate(index) {
    const slot = slots.value[index];

    if (!slot) {
        return;
    }

    const { left, top, width, height } = slot.getBoundingClientRect();

    gsap.fromTo(
        slot,
        { rotateY: 180, scale: 1.9 },
        { rotateY: 0, scale: 1, duration: 0.9, ease: 'back.out(1.8)', clearProps: 'transform' },
    );

    confetti({
        particleCount: 45,
        spread: 70,
        startVelocity: 26,
        scalar: 0.8,
        colors: [themeColor('gold-300'), themeColor('gold-400'), themeColor('glow-a'), '#ffffff'],
        origin: {
            x: (left + width / 2) / window.innerWidth,
            y: (top + height / 2) / window.innerHeight,
        },
        disableForReducedMotion: true,
    });

    sfx.fanfare();
}

watch(
    () => props.solved,
    async (solved, previouslySolved) => {
        if (solved <= previouslySolved) {
            return;
        }

        await nextTick();

        for (let index = previouslySolved; index < solved; index++) {
            celebrate(index);
        }
    },
);
</script>

<template>
    <div
        class="flex items-center justify-center gap-1 sm:gap-2"
        role="group"
        :aria-label="`Ułożone obrazki: ${solved} z ${total}`"
        style="perspective: 600px"
    >
        <div
            v-for="index in total"
            :key="index"
            ref="slots"
            class="flex h-9 w-7 items-center justify-center rounded-xl border text-base sm:h-11 sm:w-9 sm:text-xl"
            :class="
                index <= solved
                    ? 'border-gold-300/70 bg-gradient-to-b from-gold-300/30 to-gold-500/20 shadow-lg shadow-gold-500/20'
                    : 'border-white/20 bg-white/5 text-white/50'
            "
        >
            <span v-if="index <= solved" aria-hidden="true">🧩</span>
            <span v-else class="animate-pulse-soft text-sm font-bold tabular-nums" aria-hidden="true">{{ index }}</span>
        </div>
    </div>
</template>
