<script>
    let { items, activeTab = 0, ...props } = $props();

    let list = $state();

    /**
     * sets the active tab
     *
     * @param {number} val - Number of the tab to display
     */
    function setActive(val) {
        activeTab = val;
        const url = new URL(window.location.href);
        url.searchParams.set("tab", toAttr(items[val].label));
        window.history.replaceState([], "", url.toString());
    }

    function handleKeydown(e, val) {
        if (e.key === " " || e.key === "Enter") {
            e.preventDefault();
            setActive(val);
        }
        if (e.key === "ArrowLeft") {
            e.preventDefault();
            let i = document.activeElement?.dataset.i ?? 1;
            i = i-- <= 0 ? items.length - 1 : i;
            setActive(i);
            list.children[i].focus();
        }
        if (e.key === "ArrowRight") {
            e.preventDefault();
            let i = document.activeElement?.dataset.i ?? 1;
            i = i++ >= items.length - 1 ? 0 : i;
            setActive(i);
            list.children[i].focus();
        }
    }

    function toAttr(s) {
        return s.split(" ").join("-").toLowerCase();
    }
</script>

<ul {...props} bind:this={list}>
    {#each items as item, i}
        <li
            id={toAttr(item.label + " tab")}
            class={activeTab === i ? "active" : ""}
            data-i={i}
            onclick={() => setActive(i)}
            onkeydown={(e) => handleKeydown(e, i)}
            tabindex="0"
            role="tab"
        >
            <span>{item.label}</span>
        </li>
    {/each}
</ul>
{#each items as item, i}
    {#if activeTab === i}
        <div class={["box", item.label && toAttr(item.label)]}>
            <item.component />
        </div>
    {/if}
{/each}
