<script>
    import Icon from "$components/Icon.svelte";
    import ListFilter from "$components/ListFilter.svelte";

    let { actions } = $props();

    let search = new URL(window.location.href).searchParams;
</script>

<div class="tablenav top">
    <div class="align-left actions bulk-actions">
        <label for="bulk-action-selector-top" class="screen-reader-text">
            Choose bulk action
        </label>
        <select name="action" id="bulk-action-selector-top">
            <option value="">Bulk actions...</option>
            {#each Object.entries(actions) as [value, action]}
                <option {value}>{action}</option>
            {/each}
        </select>
        <input
            type="submit"
            name="bulk_action"
            id="doaction"
            class="action lighter-btn transparent wp-background"
            value="apply"
        />
    </div>
    <div class="right">
        <ListFilter />
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input"
                >Search Courses:</label
            >
            <input
                type="search"
                id="post-search-input"
                name="s"
                value={search.get("s")}
            />
        </p>
    </div>
</div>

<style>
    .tablenav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: fit-content;

        .right {
            display: flex;
            gap: 2em;
        }
    }
</style>
