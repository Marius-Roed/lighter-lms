<script lang="ts">
    interface Props {
        onhold: Function;
        children: Function;
    }

    let { onhold: fn, children }: Props = $props();

    let confirmed = $state(false);
    let progress = $state(0);
    let timer: ReturnType<typeof setInterval> | null = null;

    function start() {
        confirmed = false;
        progress = 0;

        const startTime = Date.now();

        timer = setInterval(() => {
            progress = Math.min((Date.now() - startTime) / 1500, 1);

            if (progress === 1) {
                confirm();
                stop();
            }
        }, 16);
    }

    function stop() {
        if (timer) clearInterval(timer);
        timer = null;
        if (!confirmed) progress = 0;
    }

    function confirm() {
        confirmed = true;
        setTimeout(() => {
            fn?.();
        }, 400);
    }
</script>

<button
    type="button"
    class="lighter-progress-btn"
    onmousedown={start}
    ontouchstart={(e) => {
        e.preventDefault();
        start();
    }}
    onmouseup={stop}
    onmouseleave={stop}
    ontouchend={stop}
>
    <div class="fill" style="transform:scaleX({progress})"></div>
    <span class="text base">{@render children?.()}</span>
    <span
        class="text masked"
        style="clip-path: inset( 0 {100 - progress * 100}% 0 0)"
        >{@render children?.()}</span
    >
    <svg class="check" viewBox="0 0 24 24" class:show={confirmed}>
        <path d="M5 13l4 4L19 7" pathLength="1" />
    </svg>
</button>

<style>
    .lighter-progress-btn {
        display: grid;
        place-items: center;

        border: 2px solid oklch(0.6808 0.2525 29.65);
        background: canvas;
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        cursor: pointer;
        overflow: hidden;

        & > * {
            grid-area: 1 / 1;
        }
    }

    .fill {
        background: oklch(0.6808 0.2525 29.65);
        width: 100%;
        height: 100%;
        transform-origin: left;
        transform: scaleX(0);
    }

    .text {
        font-weight: 600;
        pointer-events: none;
    }

    .base {
        color: oklch(0.6808 0.2525 29.65);
    }

    .masked {
        color: canvas;
    }

    .check {
        width: 20px;
        height: 20px;
        opacity: 0;
    }

    .check path {
        fill: none;
        stroke: canvas;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;

        stroke-dasharray: 1;
        stroke-dashoffset: 1;
    }

    .check.show {
        opacity: 1;
    }

    .check.show path {
        animation: draw 0.4 ease forwards;
    }

    @keyframes draw {
        to {
            stroke-dashoffset: 0;
        }
    }
</style>
