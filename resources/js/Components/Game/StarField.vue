<script setup>
const random = (min, max) => min + Math.random() * (max - min);

const stars = Array.from({ length: 46 }, (_, id) => ({
    id,
    style: {
        left: `${random(0, 100)}%`,
        top: `${random(0, 100)}%`,
        width: `${random(1.5, 3.5)}px`,
        height: `${random(1.5, 3.5)}px`,
        animationDuration: `${random(2.5, 6)}s`,
        animationDelay: `${random(0, 5)}s`,
    },
}));

const sparkles = Array.from({ length: 12 }, (_, id) => ({
    id,
    gold: id % 2 === 0,
    style: {
        left: `${random(0, 100)}%`,
        fontSize: `${random(10, 20)}px`,
        animationDuration: `${random(18, 36)}s`,
        animationDelay: `${random(-36, 0)}s`,
    },
}));
</script>

<template>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-pink-500/20 blur-3xl" />
        <div class="absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-indigo-500/25 blur-3xl" />

        <span
            v-for="star in stars"
            :key="`star-${star.id}`"
            class="absolute animate-twinkle rounded-full bg-white"
            :style="star.style"
        />

        <span
            v-for="sparkle in sparkles"
            :key="`sparkle-${sparkle.id}`"
            class="absolute top-full animate-drift"
            :class="sparkle.gold ? 'text-gold-300/50' : 'text-pink-300/40'"
            :style="sparkle.style"
        >
            ✦
        </span>
    </div>
</template>
