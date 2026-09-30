<script setup>
import { useGameAudio } from '@/Composables/useGameAudio';
import confetti from 'canvas-confetti';
import gsap from 'gsap';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    digits: {
        type: Array,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
    large: {
        type: Boolean,
        default: false,
    },
});

const { sfx } = useGameAudio();
const slots = ref([]);

/**
 * Flip a freshly earned digit into its slot and throw a little confetti from it.
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
        colors: ['#fbd77a', '#f6c453', '#f9a8d4', '#ffffff'],
        origin: {
            x: (left + width / 2) / window.innerWidth,
            y: (top + height / 2) / window.innerHeight,
        },
        disableForReducedMotion: true,
    });

    sfx.fanfare();
}

watch(
    () => props.digits.length,
    async (length, previousLength) => {
        if (length <= previousLength) {
            return;
        }

        await nextTick();

        for (let index = previousLength; index < length; index++) {
            celebrate(index);
        }
    },
);
</script>

<template>
    <div
        class="flex items-center justify-center"
        :class="large ? 'gap-1.5 sm:gap-3' : 'gap-1 sm:gap-2'"
        role="group"
        :aria-label="`Odkryte cyfry kodu: ${digits.length} z ${total}`"
        style="perspective: 600px"
    >
        <div
            v-for="index in total"
            :key="index"
            ref="slots"
            class="flex items-center justify-center rounded-xl border font-bold tabular-nums"
            :class="[
                large
                    ? 'h-12 w-8 text-2xl min-[400px]:h-14 min-[400px]:w-9 min-[400px]:text-3xl sm:h-20 sm:w-14 sm:text-5xl'
                    : 'h-10 w-7 text-xl sm:h-12 sm:w-10 sm:text-2xl',
                digits[index - 1] !== undefined
                    ? 'border-gold-300/70 bg-gradient-to-b from-gold-300/30 to-gold-500/20 text-gold-300 shadow-lg shadow-gold-500/20'
                    : 'border-white/20 bg-white/5 text-white/50',
                // The code reads as three groups of three, like on a BLIK cheque.
                index > 1 && (index - 1) % 3 === 0 ? (large ? 'ml-1.5 sm:ml-3' : 'ml-1 sm:ml-2') : '',
            ]"
        >
            <span v-if="digits[index - 1] !== undefined">{{ digits[index - 1] }}</span>
            <span v-else class="animate-pulse-soft" aria-hidden="true">?</span>
        </div>
    </div>
</template>
