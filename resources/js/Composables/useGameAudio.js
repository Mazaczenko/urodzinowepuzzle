import { useStorage } from '@vueuse/core';
import { Howl, Howler } from 'howler';
import { watch } from 'vue';

const MUSIC_VOLUME = 0.45;

// Module-level state: the music keeps playing while Inertia swaps pages.
const muted = useStorage('puzzle-muted', false);

let music = null;
let musicSrc = null;
let pausedByVisibility = false;
let listening = false;

function applyMute() {
    Howler.mute(muted.value);
}

function listenForVisibility() {
    if (listening || typeof document === 'undefined') {
        return;
    }

    listening = true;
    watch(muted, applyMute);

    // iOS Safari pauses audio when the screen locks and does not bring it back on its own.
    document.addEventListener('visibilitychange', () => {
        if (!music) {
            return;
        }

        if (document.hidden) {
            pausedByVisibility = music.playing();
            music.pause();
        } else if (pausedByVisibility) {
            pausedByVisibility = false;
            Howler.ctx?.resume?.();
            music.play();
        }
    });
}

/**
 * Loop a track, crossfading from whatever is playing now.
 * Browsers hold playback until the first tap or click; Howler queues it until then.
 */
function playMusic(src, { fade = 2000 } = {}) {
    listenForVisibility();
    applyMute();

    if (!src) {
        stopMusic(fade);
        return;
    }

    if (music && musicSrc === src) {
        if (!music.playing()) {
            music.play();
        }
        return;
    }

    stopMusic(fade);

    const track = new Howl({ src: [src], loop: true, volume: 0 });
    track.once('play', () => track.fade(0, MUSIC_VOLUME, fade));
    track.play();

    music = track;
    musicSrc = src;
}

function stopMusic(fade = 1000) {
    if (!music) {
        return;
    }

    const track = music;
    music = null;
    musicSrc = null;

    if (track.playing()) {
        track.once('fade', () => track.unload());
        track.fade(track.volume(), 0, fade);
    } else {
        track.unload();
    }
}

/**
 * Sound effects are synthesised, so the game needs no audio files besides the music.
 */
function tone(frequency, { at = 0, duration = 0.12, type = 'sine', volume = 0.2, slideTo = null } = {}) {
    // Reading the volume makes Howler create its AudioContext if it has none yet.
    Howler.volume();

    const context = Howler.ctx;

    if (!context || context.state !== 'running' || muted.value) {
        return;
    }

    const start = context.currentTime + at;
    const oscillator = context.createOscillator();
    const gain = context.createGain();

    oscillator.type = type;
    oscillator.frequency.setValueAtTime(frequency, start);

    if (slideTo) {
        oscillator.frequency.exponentialRampToValueAtTime(slideTo, start + duration);
    }

    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);

    oscillator.connect(gain);
    gain.connect(Howler.masterGain ?? context.destination);
    oscillator.start(start);
    oscillator.stop(start + duration + 0.05);
}

const sfx = {
    pick() {
        tone(520, { duration: 0.06, type: 'triangle', volume: 0.12, slideTo: 340 });
    },
    snap() {
        tone(880, { duration: 0.09, type: 'triangle', volume: 0.2 });
        tone(1320, { at: 0.05, duration: 0.12, volume: 0.16 });
    },
    complete() {
        [523.25, 659.25, 783.99].forEach((frequency) => tone(frequency, { duration: 0.7, type: 'triangle', volume: 0.12 }));
        tone(1046.5, { at: 0.18, duration: 0.8, volume: 0.12 });
    },
    fanfare() {
        [523.25, 659.25, 783.99, 1046.5].forEach((frequency, index) =>
            tone(frequency, { at: index * 0.11, duration: 0.22, type: 'triangle', volume: 0.2 }),
        );
        tone(1318.5, { at: 0.46, duration: 0.6, volume: 0.16 });
    },
};

export function useGameAudio() {
    return {
        muted,
        toggleMute: () => {
            muted.value = !muted.value;
            applyMute();
        },
        playMusic,
        stopMusic,
        sfx,
    };
}
