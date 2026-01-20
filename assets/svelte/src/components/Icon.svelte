<script lang="ts">
    import type { HTMLAttributes } from "svelte/elements";

    import BeaverBuilderLogo from "$icons/beaver-builder-logo.svg?raw";
    import BellIcon from "$icons/bell.svg?raw";
    import BellFilled from "$icons/bell-fill.svg?raw";
    import BreackdanceLogo from "$icons/breakdance-logo.svg?raw";
    import BricksLogo from "$icons/bricks-logo.svg?raw";
    import BrizyLogo from "$icons/brizy-logo.svg?raw";
    import CancelIcon from "$icons/cancel.svg?raw";
    import CheckmarkIcon from "$icons/checkmark.svg";
    import ChevronIcon from "$icons/chevron-down.svg?raw";
    import DiviLogo from "$icons/divi-logo.svg?raw";
    import ElementorLogo from "$icons/elementor-logo.svg?raw";
    import FusionBuilderLogo from "$icons/fusion-builder-logo.svg?raw";
    import LiveComposerLogo from "$icons/live-composer-logo.svg?raw";
    import LoopIcon from "$icons/loop.svg?raw";
    import MinusIcon from "$icons/minus.svg?raw";
    import OxygenLogo from "$icons/oxygen-logo.svg?raw";
    import PauseIcon from "$icons/pause.svg?raw";
    import PencilIcon from "$icons/pencil.svg?raw";
    import PlayIcon from "$icons/play.svg?raw";
    import PlusIcon from "$icons/plus.svg?raw";
    import SaveIcon from "$icons/save.svg?raw";
    import SixDotsIcon from "$icons/six-dots.svg?raw";
    import SlidersIcon from "$icons/sliders.svg?raw";
    import SpectraLogo from "$icons/spectra-logo.svg?raw";
    import ThreeDotsIcon from "$icons/three-dots.svg?raw";
    import TrashIcon from "$icons/trash.svg?raw";
    import WordpressLogo from "$icons/wordpress-logo.svg?raw";

    const iconMap = {
        "beaver-builder": BeaverBuilderLogo,
        bell: BellIcon,
        bellFilled: BellFilled,
        breakdance: BreackdanceLogo,
        bricks: BricksLogo,
        brizy: BrizyLogo,
        cancel: CancelIcon,
        checkmark: CheckmarkIcon,
        chevron: ChevronIcon,
        divi: DiviLogo,
        elementor: ElementorLogo,
        "fusion-builder": FusionBuilderLogo,
        "live-composer": LiveComposerLogo,
        loop: LoopIcon,
        minus: MinusIcon,
        oxygen: OxygenLogo,
        pause: PauseIcon,
        pencil: PencilIcon,
        play: PlayIcon,
        plus: PlusIcon,
        save: SaveIcon,
        sixDots: SixDotsIcon,
        sliders: SlidersIcon,
        spectra: SpectraLogo,
        threeDots: ThreeDotsIcon,
        trash: TrashIcon,
        "classic-editor": WordpressLogo,
        gutenberg: WordpressLogo,
    } as const;

    type IconName = keyof typeof iconMap;

    interface IconProps extends HTMLAttributes<HTMLDivElement> {
        name: IconName;
        className?: string;
        size?: string | number;
        color?: string;
        fill?: boolean;
    }

    let {
        name,
        className = "",
        size = "1em",
        color = "currentColor",
        fill = false,
        ...rest
    }: IconProps = $props();

    let fallback =
        '<svg viewBox="0 0 24 24" fill="red"><circle cx="12" cy="12" r="10" fill="red"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="10" fill="white">!</text></svg>';

    let svgContent = $derived(iconMap[name] ?? fallback);
</script>

<div
    class={["icon-wrapper", className]}
    style="--icon-size: {size}; --icon-color: {color}; {fill &&
        'fill: currentColor;'}"
    {...rest}
>
    {@html svgContent}
</div>

<style>
    .icon-wrapper {
        width: var(--icon-size, 1em);
        height: var(--icon-size, 1em);
        display: inline-flex;
        justify-content: center;
        align-items: center;
        color: var(--icon-color, #333);

        :global(svg) {
            width: 100%;
            height: 100%;
        }
    }
</style>
