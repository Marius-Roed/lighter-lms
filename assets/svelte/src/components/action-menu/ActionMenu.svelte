<script lang="ts">
    import { setContext } from "svelte";
    import { MENU_CONTEXT } from "./menu.ts";

    interface Props {
        children: Function; // NOTE: Should actually be a svelte snippet type
        trigger: Function;
    }

    let { children, trigger }: Props = $props();
    const id = $props.id();

    setContext(MENU_CONTEXT, true);

    export const close = () => {
        document.getElementById(id).hidePopover();
    };
</script>

<div class="action-menu">
    <button type="button" popovertarget={id} style="anchor-name: --{id}">
        {@render trigger?.()}
    </button>
    <div class="menu-wrap" {id} popover style="position-anchor: --{id}">
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
