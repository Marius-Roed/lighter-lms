<script lang="ts">
  interface Props {
    onhold: Function;
    children: Function;
  }

  let { onhold: fn, children }: Props = $props();

  let confirmed = $state(false);
  let progress = $state(0);
  let timer: ReturnType<typeof setInterval> | null = null;

  function start(e: MouseEvent) {
    confirmed = false;
    progress = 0;

    if (e.metaKey) {
      confirm();
      stop();
      return;
    }

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
    }, 600);
  }
</script>

<button
  type="button"
  class="lighter-progress-btn"
  onmousedown={start}
  ontouchstart={(e) => {
    e.preventDefault();
    start(e);
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
    position: relative;
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

    &:has(.check.show)::after {
      content: "";
      position: absolute;
      inset: 0;
      background-color: oklch(0.6808 0.2525 29.65);
      z-index: 15;
    }
  }

  .fill {
    position: absolute;
    background: oklch(0.6808 0.2525 29.65);
    top: -0.5rem;
    left: -1.25rem;
    right: 0;
    bottom: 0;
    transform-origin: left;
    transform: scaleX(0);
  }

  .text {
    font-weight: 600;
    pointer-events: none;
    text-transform: uppercase;
  }

  .base {
    color: oklch(0.6808 0.2525 29.65);
  }

  .masked {
    position: absolute;
    text-transform: uppercase;
    color: canvas;
    padding: 0.725rem 1.25rem;
    top: -0.5rem;
    left: -1.25rem;
    bottom: 0;
    right: 0;
  }

  .check {
    width: 20px;
    height: 20px;
    opacity: 0;
    z-index: 16;
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
    animation: draw 0.35s ease-out forwards;
  }

  @keyframes draw {
    to {
      stroke-dashoffset: 0;
    }
  }
</style>
