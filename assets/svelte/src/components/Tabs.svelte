<script>
    /** @typedef {object} TabItem
     * @property {string} label - The item label
     * @property {import("svelte").Component} component - The items component
     */

    /** @typedef {object} TabProps
     * @property {Array<TabItem>} items
     * @property {number} [activeTab]
     */

    import { onMount } from "svelte";

    /** @type {TabProps} */
    let { items, activeTab: initialTab = 0, ...props } = $props();

    let activeTab = $state(initialTab);
    let list = $state();
    let tabRefs = $state([]);

    let activeItem = $derived(items[activeTab]);
    let tabId = $derived(toAttr(activeItem.label) + "-tab");
    let panelId = $derived(toAttr(activeItem.label) + "-panel");

    /**
     * @param {string} s
     */
    function toAttr(s) {
        return s
            .replaceAll(/[^a-zA-Z0-9\s-]/g, "")
            .replaceAll(/\s+/g, "-")
            .toLowerCase();
    }

    /**
     * sets the active tab
     *
     * @param {number} index - Number of the tab to display
     */
    function setActive(index) {
        if (index < 0 || index >= items.length) return;

        activeTab = index;
        const url = new URL(window.location.href);
        url.searchParams.set("tab", toAttr(items[index].label));
        window.history.replaceState({}, "", url.toString());
    }

    /**
     * @param {KeyboardEvent} e
     * @param {number} currentIdx
     */
    function handleKeydown(e, currentIdx) {
        if (e.metaKey) return;

        let handled = false;
        let newIndex = currentIdx;

        if (e.key === " " || e.key === "Enter") {
            handled = true;
            setActive(currentIdx);
        } else if (e.key === "ArrowLeft") {
            handled = true;
            newIndex = currentIdx - 1 < 0 ? items.length - 1 : currentIdx - 1;
        } else if (e.key === "ArrowRight") {
            handled = true;
            newIndex = currentIdx + 1 >= items.length ? 0 : currentIdx + 1;
        }

        if (handled) {
            e.preventDefault();
            if (newIndex !== currentIdx) {
                setActive(newIndex);
                tabRefs[newIndex]?.focus();
            }
        }
    }

    onMount(() => {
        const urlTab = new URL(window.location.href).searchParams.get("tab");
        if (!urlTab) return;

        const index = items.findIndex((item) => toAttr(item.label) === urlTab);
        if (index !== -1) setActive(index);
    });
</script>

<ul {...props} bind:this={list} role="tablist">
    {#each items as item, i}
        {@const id = toAttr(item.label) + "-tab"}
        {@const panelId = toAttr(item.label) + "-panel"}
        <li
            {id}
            bind:this={tabRefs[i]}
            class={activeTab === i ? "active" : ""}
            onclick={() => setActive(i)}
            onkeydown={(e) => handleKeydown(e, i)}
            tabindex={activeTab === i ? 0 : -1}
            role="tab"
            aria-selected={activeTab === i}
            aria-controls={panelId}
        >
            <span>{item.label}</span>
        </li>
    {/each}
</ul>

<div
    id={panelId}
    role="tabpanel"
    aria-labelledby={tabId}
    tabindex="0"
    class={["box", activeItem.label && toAttr(activeItem.label)]}
>
    <activeItem.component />
</div>
