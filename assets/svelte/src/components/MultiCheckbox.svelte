<script>
    import Icon from "./Icon.svelte";
    import Switch from "./Switch.svelte";

    let {
        title = null,
        group = $bindable([]),
        items = [],
        ...props
    } = $props();

    const getItemVal = (i) => i.id ?? i.key ?? i;

    let isOpen = $state(false);
    let trigger = $derived(title ?? "Undefined");
    let totalItems = $derived(Object.keys(items).length);

    let indeterminate = $derived(group.length > 0 && group.length < totalItems);
    let groupChecked = $derived(group.length == totalItems);

    /**
     * @param {MouseEvent & { currentTarget: EventTarget & HTMLInputElement }} e
     */
    const onclick = (e) => {
        const cb = e.currentTarget;
        if (!cb.checked) {
            group = [];
        } else {
            group = items.map(getItemVal);
        }
    };
</script>

<div class="multi-select" aria-expanded={isOpen} {...props}>
    <div class="multi-select-trig cb-middle">
        <div class="select-info row">
            <label>
                <input
                    type="checkbox"
                    {onclick}
                    bind:indeterminate
                    bind:checked={groupChecked}
                />
                {trigger}
            </label>
            <span class="select-group">({group.length} / {totalItems})</span>
        </div>
        <button
            type="button"
            onclick={() => (isOpen = !isOpen)}
            style:rotate={isOpen ? "180deg" : "0deg"}
        >
            <Icon name="chevron" />
        </button>
    </div>
    {#if isOpen}
        <div class="multi-select-sub cb-middle">
            {#each items as item}
                <label>
                    <input
                        type="checkbox"
                        bind:group
                        value={getItemVal(item)}
                    />
                    {item.title ?? item}
                </label>
            {:else}
                <p>No items found for group</p>
            {/each}
        </div>
    {/if}
</div>
