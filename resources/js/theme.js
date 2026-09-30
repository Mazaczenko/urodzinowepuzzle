import { usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { computed, ref } from 'vue';

// The first one is the default.
export const THEMES = [
    { value: 'fortis', label: 'Fortis', sparkle: '▲' },
    { value: 'classic', label: 'Nocne niebo', sparkle: '✦' },
    { value: 'checkers', label: 'Warcaby', sparkle: '🏁' },
];

const PREVIEW_KEY = 'puzzle-preview-theme';

// The player's own pick overrides the theme set in the panel, on this device, the login screen included.
const playerTheme = useStorage('puzzle-theme', null);

function readPreviewTheme() {
    try {
        return sessionStorage.getItem(PREVIEW_KEY);
    } catch {
        return null;
    }
}

// Lets an admin compare themes in the preview without saving the game, in this tab only.
const previewTheme = ref(typeof window === 'undefined' ? null : readPreviewTheme());

export function setTheme(value, preview = false) {
    if (!preview) {
        playerTheme.value = value;
        return;
    }

    previewTheme.value = value;

    try {
        sessionStorage.setItem(PREVIEW_KEY, value);
    } catch {
        // Private mode: the choice lasts until the page reloads.
    }
}

const findTheme = (value) => THEMES.find((item) => item.value === value);

export function useTheme(preview) {
    const page = usePage();

    return computed(
        () =>
            findTheme(preview?.value ? previewTheme.value : playerTheme.value) ??
            findTheme(page.props.theme) ??
            THEMES[0],
    );
}

/**
 * A theme colour as hex, for canvas and confetti, which cannot read CSS variables.
 */
export function themeColor(name) {
    const channels = getComputedStyle(document.documentElement)
        .getPropertyValue(`--color-${name}`)
        .trim()
        .split(/\s+/)
        .map(Number);

    if (channels.length !== 3 || channels.some(Number.isNaN)) {
        return '#ffffff';
    }

    return `#${channels.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
}
