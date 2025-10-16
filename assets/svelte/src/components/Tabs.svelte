<script>
    let { items, activeTab = 0, ...props } = $props();

    /**
     * sets the active tab
     *
     * @param {number} val - Number of the tab to display
     */
    function setAtive(val) {
        activeTab = val;
    }

    function handleKeydown(e, val) {
        if (e.key === " " || e.key === "Enter") {
            e.preventDefault();
            setAtive(val);
        }
    }

    function toAttr(s) {
        return s.split(" ").join("-").toLowerCase();
    }
</script>

<ul {...props}>
    {#each items as item, i}
        <li
            id={toAttr(item.label + " tab")}
            class={activeTab === i ? "active" : ""}
            onclick={() => setAtive(i)}
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
