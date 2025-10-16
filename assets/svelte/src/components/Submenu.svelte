<script>
    let { anchor, trigger, children } = $props();
    let open = $state(false);

    let closeTimer;
    anchor = "--" + anchor.split(" ")[0].toLowerCase();

    const openNow = () => {
        if (closeTimer) clearTimeout(closeTimer);
        open = true;
    };
    const closeSoon = (delay = 150) => {
        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = setTimeout(() => (open = false), delay);
    };

    function onKeyDown(e) {
        if (e.key === "ArrowRight") {
            openNow();
            e.stopPropagation();
        } else if (e.key === "ArrowLeft" || e.key === "Escape") {
            open = false;
            e.stopPropagation();
        }
    }
</script>

<div
    class="submenu"
    onpointerenter={openNow}
    onpointerleave={() => closeSoon()}
    onfocusin={openNow}
    onfocusout={() => closeSoon()}
    style:anchor-name={anchor}
>
    <div
        class="submenu-trigger"
        tabindex="0"
        role="menuitem"
        aria-haspopup="menu"
        aria-expanded={open}
        onkeydown={onKeyDown}
    >
        {@render trigger?.()}
    </div>
</div>
{#if open}
    <div
        class="submenu-panel"
        role="menu"
        style:position-anchor={anchor}
        onpointerenter={openNow}
        onpointerleave={() => closeSoon()}
        onfocusin={openNow}
        onfocusout={() => closeSoon()}
    >
        {@render children?.()}
    </div>
{/if}

<style>
    .submenu {
        position: relative;
        width: 100%;
    }

    .submenu-panel {
        position: absolute;
        background: #fff;
    }
</style>
