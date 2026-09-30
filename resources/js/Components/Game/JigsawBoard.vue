<script setup>
import { useDebounceFn, useResizeObserver } from '@vueuse/core';
import gsap from 'gsap';
import headbreaker from 'headbreaker';
import Konva from 'konva';
import { onBeforeUnmount, onMounted, ref } from 'vue';

// Pictures are 5:2, so a 5×2 grid gives square pieces in every orientation.
const COLUMNS = 5;
const ROWS = 2;
const STROKE_WIDTH = 1.5;

const props = defineProps({
    imageUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['ready', 'grab', 'connect', 'solved', 'merged']);

const wrapper = ref(null);
const loading = ref(true);
const failed = ref(false);
const stageId = `jigsaw-${Math.random().toString(36).slice(2)}`;

let image = null;
let canvas = null;
let stage = null;
let layout = null;
let solved = false;
let startedAt = 0;
let animations = [];

function measure() {
    return {
        width: wrapper.value?.clientWidth ?? 0,
        height: wrapper.value?.clientHeight ?? 0,
    };
}

/**
 * Size the pieces and place the frame the picture is assembled in:
 * centred on wide boards, at the top on tall ones so pieces can lie below it.
 */
function computeLayout({ width, height }) {
    const portrait = height > width;
    const margin = 12;
    const piece = Math.max(
        36,
        Math.floor(
            Math.min(
                (width - 2 * margin) / (COLUMNS + (portrait ? 0.4 : 2.2)),
                (height - 2 * margin) / (ROWS + (portrait ? 3.4 : 1.7)),
            ),
        ),
    );
    const frame = {
        x: Math.round((width - COLUMNS * piece) / 2),
        y: portrait ? margin + Math.round(piece * 0.2) : Math.round((height - ROWS * piece) / 2),
        width: COLUMNS * piece,
        height: ROWS * piece,
    };
    const scale = Math.max(frame.width / image.naturalWidth, frame.height / image.naturalHeight);

    return {
        width,
        height,
        portrait,
        piece,
        frame,
        scale,
        // How far the scaled picture overflows the frame when it is not exactly 5:2.
        overflow: {
            x: (image.naturalWidth * scale - frame.width) / 2,
            y: (image.naturalHeight * scale - frame.height) / 2,
        },
    };
}

/**
 * Pick a starting spot for every piece: a shuffled grid of cells outside the frame, with some jitter.
 */
function scatterSpots({ width, height, piece, frame }, count) {
    const inset = piece * 0.62;
    const columns = Math.max(1, Math.floor((width - 2 * inset) / (piece * 1.02)) + 1);
    const rows = Math.max(1, Math.floor((height - 2 * inset) / (piece * 1.02)) + 1);
    const stepX = columns > 1 ? (width - 2 * inset) / (columns - 1) : 0;
    const stepY = rows > 1 ? (height - 2 * inset) / (rows - 1) : 0;
    const free = [];
    const covered = [];

    for (let row = 0; row < rows; row++) {
        for (let column = 0; column < columns; column++) {
            const spot = { x: inset + column * stepX, y: inset + row * stepY };
            const insideFrame =
                spot.x > frame.x && spot.x < frame.x + frame.width && spot.y > frame.y && spot.y < frame.y + frame.height;

            (insideFrame ? covered : free).push(spot);
        }
    }

    const spots = [...gsap.utils.shuffle(free), ...gsap.utils.shuffle(covered)];
    const jitter = piece * 0.08;

    return Array.from({ length: count }, (_, index) => {
        const spot = spots[index % spots.length];

        return {
            x: gsap.utils.clamp(inset, width - inset, spot.x + gsap.utils.random(-jitter, jitter)),
            y: gsap.utils.clamp(inset, height - inset, spot.y + gsap.utils.random(-jitter, jitter)),
        };
    });
}

function pictureNode(attributes = {}) {
    const { frame, scale, overflow } = layout;
    const clip = new Konva.Group({ clip: { x: frame.x, y: frame.y, width: frame.width, height: frame.height } });

    const picture = new Konva.Image({
        image,
        x: frame.x - overflow.x,
        y: frame.y - overflow.y,
        width: image.naturalWidth * scale,
        height: image.naturalHeight * scale,
        ...attributes,
    });

    clip.add(picture);

    return { clip, picture };
}

function build() {
    destroy();

    const size = measure();

    if (!image || size.width < 120 || size.height < 120) {
        return;
    }

    layout = computeLayout(size);

    const { piece, frame, scale, overflow } = layout;

    canvas = new headbreaker.Canvas(stageId, {
        width: size.width,
        height: size.height,
        pieceSize: piece,
        proximity: gsap.utils.clamp(14, 28, piece * 0.22),
        strokeWidth: STROKE_WIDTH,
        strokeColor: 'rgba(255, 255, 255, 0.85)',
        // Headbreaker lays the puzzle out one piece away from the origin; shift the picture to match.
        image: { content: image, scale, offset: { x: overflow.x - piece, y: overflow.y - piece } },
        outline: new headbreaker.outline.Rounded(),
        painter: new headbreaker.painters.Konva(),
        preventOffstageDrag: true,
        fixed: true,
    });

    canvas.autogenerate({
        horizontalPiecesCount: COLUMNS,
        verticalPiecesCount: ROWS,
        insertsGenerator: headbreaker.generators.random,
    });

    // Without this any tab fits any slot, and wrong pieces would stick together.
    const isNeighbour = (dx, dy) => (one, other) => {
        const from = one.metadata.targetPosition;
        const to = other.metadata.targetPosition;

        return Math.abs(to.x - from.x - dx) < 1 && Math.abs(to.y - from.y - dy) < 1;
    };

    canvas.puzzle.attachHorizontalConnectionRequirement(isNeighbour(piece, 0));
    canvas.puzzle.attachVerticalConnectionRequirement(isNeighbour(0, piece));
    canvas.puzzle.forceConnectionWhileDragging();

    // Shuffling connects neighbours that happen to land side by side, so deal again until none are.
    for (let attempt = 0; attempt < 30; attempt++) {
        const spots = scatterSpots(layout, canvas.puzzle.pieces.length);
        canvas.puzzle.shuffleWith(() => spots);

        if (!canvas.puzzle.pieces.some((puzzlePiece) => puzzlePiece.connected)) {
            break;
        }
    }

    canvas.puzzle.disconnect();
    canvas.autoconnected = true;

    canvas.attachSolvedValidator();
    canvas.onConnect((_piece, figure, _target, targetFigure) => {
        bump(figure);
        bump(targetFigure);
        emit('connect');
    });
    canvas.onValid(onSolved);

    const pieceLayer = canvas['__konvaLayer__'];
    stage = pieceLayer.getStage();

    const hintLayer = new Konva.Layer({ listening: false });
    const hint = pictureNode({ opacity: 0.16 });

    hintLayer.add(
        new Konva.Rect({
            ...frame,
            fill: 'rgba(255, 255, 255, 0.05)',
            stroke: 'rgba(255, 255, 255, 0.4)',
            strokeWidth: 1.5,
            dash: [8, 6],
        }),
    );
    hintLayer.add(hint.clip);
    stage.add(hintLayer);
    hintLayer.moveToBottom();

    canvas.puzzle.pieces.forEach((puzzlePiece) => {
        const figure = canvas.getFigure(puzzlePiece);

        figure.group.on('dragstart', () => {
            lift(figure, true);
            emit('grab');
        });

        // Headbreaker only snaps the piece under the finger; let the pieces joined to it snap as well.
        figure.group.on('dragend', () => {
            lift(figure, false);
            canvas.puzzle.autoconnect();
            canvas.puzzle.validate();
            canvas.redraw();
        });
    });

    canvas.draw();
    stage.batchDraw();

    startedAt ||= performance.now();
}

function lift(figure, lifted) {
    figure.shape.shadowColor('#000000');
    figure.shape.shadowBlur(lifted ? 16 : 0);
    figure.shape.shadowOpacity(lifted ? 0.5 : 0);
    figure.shape.shadowOffset({ x: 0, y: lifted ? 8 : 0 });
    figure.group.scale({ x: lifted ? 1.05 : 1, y: lifted ? 1.05 : 1 });
    canvas.redraw();
}

function bump(figure) {
    const state = { scale: 1.12 };

    animations.push(
        gsap.to(state, {
            scale: 1,
            duration: 0.35,
            ease: 'back.out(3)',
            onUpdate: () => {
                figure.group.scale({ x: state.scale, y: state.scale });
                canvas?.redraw();
            },
        }),
    );
}

/**
 * Slide the finished picture into the frame, then fade the cut lines away.
 */
function onSolved() {
    if (solved) {
        return;
    }

    solved = true;
    emit('solved', Math.round((performance.now() - startedAt) / 1000));

    animations.forEach((animation) => animation.kill());
    animations = [];
    document.body.style.cursor = 'default';

    const { piece, frame } = layout;
    const figures = canvas.puzzle.pieces.map((puzzlePiece) => canvas.getFigure(puzzlePiece));
    const head = canvas.puzzle.pieces[0];
    const starts = figures.map((figure) => {
        figure.group.draggable(false);
        figure.group.off('mouseover');
        figure.group.scale({ x: 1, y: 1 });

        return figure.group.position();
    });
    const shift = {
        x: frame.x - piece / 2 - (starts[0].x - head.metadata.targetPosition.x),
        y: frame.y - piece / 2 - (starts[0].y - head.metadata.targetPosition.y),
    };

    const coverLayer = new Konva.Layer({ listening: false });
    const glow = new Konva.Rect({
        ...frame,
        fill: '#f6c453',
        shadowColor: '#f6c453',
        shadowBlur: 36,
        shadowOpacity: 0.9,
        opacity: 0,
    });
    const cover = pictureNode({ opacity: 0 });

    coverLayer.add(glow);
    coverLayer.add(cover.clip);
    stage.add(coverLayer);

    const state = { slide: 0, merge: 0 };
    const timeline = gsap.timeline({ onComplete: () => emit('merged') });

    timeline.to(state, {
        slide: 1,
        duration: 0.7,
        ease: 'power2.inOut',
        onUpdate: () => {
            figures.forEach((figure, index) => {
                figure.group.position({
                    x: starts[index].x + shift.x * state.slide,
                    y: starts[index].y + shift.y * state.slide,
                });
            });
            stage.batchDraw();
        },
    });

    timeline.to(state, {
        merge: 1,
        duration: 0.9,
        ease: 'power1.inOut',
        onUpdate: () => {
            figures.forEach((figure) => figure.shape.strokeWidth(STROKE_WIDTH * (1 - state.merge)));
            glow.opacity(state.merge);
            cover.picture.opacity(state.merge);
            stage.batchDraw();
        },
    });

    animations.push(timeline);
}

function destroy() {
    animations.forEach((animation) => animation.kill());
    animations = [];
    stage?.destroy();
    stage = null;
    canvas = null;
}

/**
 * Keep the pieces where they are and rescale the stage, unless the board flipped orientation.
 */
function fit() {
    if (!image) {
        return;
    }

    const size = measure();

    if (!canvas) {
        build();
        return;
    }

    if (size.width < 120 || size.height < 120) {
        return;
    }

    if (size.height > size.width !== layout.portrait && !solved) {
        build();
        return;
    }

    const factor = Math.min(size.width / layout.width, size.height / layout.height);

    canvas.resize(layout.width * factor, layout.height * factor);
    canvas.scale(factor);
    stage.batchDraw();
}

useResizeObserver(wrapper, useDebounceFn(fit, 120));

onMounted(() => {
    const picture = new Image();

    picture.onload = () => {
        image = picture;
        loading.value = false;
        build();
        emit('ready');
    };
    picture.onerror = () => {
        loading.value = false;
        failed.value = true;
    };
    picture.src = props.imageUrl;
});

onBeforeUnmount(() => {
    destroy();
    document.body.style.cursor = 'default';
});
</script>

<template>
    <div ref="wrapper" class="relative h-full w-full touch-none select-none overflow-hidden">
        <div :id="stageId" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" />

        <div v-if="loading" class="absolute inset-0 flex items-center justify-center text-white/70">
            <span class="animate-pulse-soft text-lg">Rozsypuję puzzle…</span>
        </div>

        <div v-if="failed" class="absolute inset-0 flex items-center justify-center px-6 text-center text-white/80">
            Nie udało się wczytać obrazka. Odśwież stronę, żeby spróbować jeszcze raz.
        </div>
    </div>
</template>
