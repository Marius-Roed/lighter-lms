<script lang="ts">
    import { getContext, onDestroy } from "svelte";
    import { MENU_CONTEXT } from "./menu.ts";

    interface Props {
        anchor: string;
        trigger: Function;
        children: Function;
    }

    let { trigger, children }: Props = $props();
    const id = $props.id();

    if (!getContext(MENU_CONTEXT)) {
        throw new Error("Submenu must be inside of an ActionMenu component");
    }

    let open = $state(false);
    let closeTimer: ReturnType<typeof setTimeout> | null = null;

    const anchor = `--${id}`;

    const openNow = () => {
        if (closeTimer) clearTimeout(closeTimer);
        open = true;
    };
    const closeSoon = (delay = 150) => {
        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = setTimeout(() => (open = false), delay);
    };

    function onKeyDown(e: KeyboardEvent) {
        if (e.key === "ArrowRight") {
            openNow();
            e.stopPropagation();
        } else if (e.key === "ArrowLeft" || e.key === "Escape") {
            open = false;
            e.stopPropagation();
        }
    }

    onDestroy(() => {
        if (closeTimer) clearTimeout(closeTimer);
    });
</script>

<div
    class="submenu"
    role="menu"
    tabindex="0"
    onpointerenter={openNow}
    onpointerleave={() => closeSoon()}
    onfocusin={openNow}
    onfocusout={() => closeSoon()}
>
    <div class="submenu-trigger" style="anchor-name:{anchor}">
        {@render trigger?.()}
    </div>
    {#if open}
        <div
            class="submenu-panel"
            role="menuitem"
            tabindex="0"
            style:position-anchor={anchor}
            onpointerenter={openNow}
            onpointerleave={() => closeSoon()}
            onfocusin={openNow}
            onfocusout={() => closeSoon()}
        >
            {@render children?.()}
        </div>
    {/if}
</div>

<style>
    .submenu {
        position: relative;
        width: 100%;
    }

    .submenu-panel {
        position: absolute;
        background: #fff;
        left: anchor(right);
        bottom: anchor(bottom);

        position-try-fallbacks: flip-inline;
    }
</style>
