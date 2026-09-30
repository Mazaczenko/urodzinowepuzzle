<script setup>
import { usePreferredReducedMotion } from '@vueuse/core';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    // Sanitized HTML from the admin's rich editor.
    html: {
        type: String,
        required: true,
    },
    delay: {
        type: Number,
        default: 900,
    },
    // Milliseconds per letter.
    speed: {
        type: Number,
        default: 38,
    },
});

const reducedMotion = usePreferredReducedMotion();
const element = ref(null);

let timers = [];

/**
 * Type the text out letter by letter while keeping the formatting from the editor.
 */
function type() {
    const root = element.value;

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

    timers = letters.map((letter, index) =>
        setTimeout(() => (letter.style.opacity = '1'), props.delay + index * props.speed),
    );
}

onMounted(type);

onBeforeUnmount(() => {
    timers.forEach(clearTimeout);
    timers = [];
});
</script>

<template>
    <div>
        <!-- The typed copy is hidden from screen readers, which get the full text at once. -->
        <div class="sr-only" v-html="html" />
        <div ref="element" class="typed-text" aria-hidden="true" v-html="html" />
    </div>
</template>

<style scoped>
.typed-text :deep(p + p),
.typed-text :deep(ul),
.typed-text :deep(ol) {
    margin-top: 0.75em;
}

.typed-text :deep(h2),
.typed-text :deep(h3) {
    margin-bottom: 0.4em;
    font-weight: 700;
    color: rgb(var(--color-gold-300));
}

.typed-text :deep(ul) {
    list-style: disc;
    padding-left: 1.25em;
}

.typed-text :deep(ol) {
    list-style: decimal;
    padding-left: 1.25em;
}

.typed-text :deep(span) {
    transition: opacity 0.25s ease;
}
</style>
