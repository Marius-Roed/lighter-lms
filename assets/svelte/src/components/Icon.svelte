<script>
    let {
        name,
        className = "",
        size = "1em",
        color = "currentColor",
        fill = false,
        ...rest
    } = $props();

    import BreackdanceLogo from "$icons/breakdance-logo.svg?raw";
    import BricksLogo from "$icons/bricks-logo.svg?raw";
    import CheckmarkIcon from "$icons/checkmark.svg";
    import ChevronIcon from "$icons/chevron-down.svg?raw";
    import ElementorLogo from "$icons/elementor-logo.svg?raw";
    import LoopIcon from "$icons/loop.svg?raw";
    import MinusIcon from "$icons/minus.svg?raw";
    import PencilIcon from "$icons/pencil.svg?raw";
    import PlusIcon from "$icons/plus.svg?raw";
    import SaveIcon from "$icons/save.svg?raw";
    import SixDotsIcon from "$icons/six-dots.svg?raw";
    import SlidersIcon from "$icons/sliders.svg?raw";
    import ThreeDotsIcon from "$icons/three-dots.svg?raw";
    import TrashIcon from "$icons/trash.svg?raw";
    import WordpressLogo from "$icons/wordpress-logo.svg?raw";

    const iconMap = {
        breakdance: BreackdanceLogo,
        bricks: BricksLogo,
        checkmark: CheckmarkIcon,
        chevron: ChevronIcon,
        elementor: ElementorLogo,
        loop: LoopIcon,
        minus: MinusIcon,
        pencil: PencilIcon,
        plus: PlusIcon,
        save: SaveIcon,
        sixDots: SixDotsIcon,
        sliders: SlidersIcon,
        threeDots: ThreeDotsIcon,
        trash: TrashIcon,
        "classic-editor": WordpressLogo,
    };

    let svgContent = $state(
        '<svg viewBox="0 0 24 24" fill="red"><circle cx="12" cy="12" r="10" fill="red"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="10" fill="white">!</text></svg>',
    );

    if (name in iconMap) {
        svgContent = iconMap[name];
    }
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
