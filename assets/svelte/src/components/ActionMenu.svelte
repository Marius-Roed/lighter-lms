<script>
    let { children, trigger } = $props();

    const id = $props.id();

    export const close = () => {
        document.getElementById(id).hidePopover();
    };
</script>

<div class="action-menu">
    <button type="button" popovertarget={id} style="anchor-name: --{id}">
        {@render trigger?.()}
    </button>
    <div class="menu-wrap" {id} popover style="anchor-position: --{id}">
        {@render children?.()}
    </div>
</div>

<style>
    .action-menu button {
        background: transparent;
        border: none;
        position: relative;

        &:hover {
            cursor: pointer;
        }
    }
</style>
