<script>
    import { FuzzyTrie } from "$lib/fuzzytrie";

    /**
     * @typedef {Object} FitlerSearchProps
     * @property {string} name - Name of the hidden input, to be process on the server
     * @property {array} tags
     * @property {boolean} [createNew=false]
     * @property {string | number} [width=""]
     * @property {array} [selected]
     */

    /** @type {FitlerSearchProps} */
    let {
        name,
        tags,
        createNew = false,
        width = "",
        selected = $bindable([]),
    } = $props();

    let search = $state("");
    let searchInput;
    let trie;
    let isFocused = $state(false);
    let highIdx = $state(0);

    let results = $derived.by(() => {
        if (!trie) return [];
        if (!search.trim()) {
            const toExclude = new Set(selected.map((s) => s.id));
            return [...tags]
                .filter((t) => !toExclude.has(t.id))
                .sort((a, b) => b.count - a.count)
                .slice(0, 5);
        }
        const tolerance = search.length > 5 ? 2 : 1;
        return trie.fuzzySearch(search, tolerance, 15, selected);
    });

    function genTrie() {
        if (trie) return;

        trie = new FuzzyTrie();
        tags.forEach((tag) => trie.insert(tag.name, tag));
    }

    function selectTag(tag) {
        if (!selected.some((t) => t.id === tag.id)) {
            selected = [...selected, tag];
        }
        search = "";
    }

    function removeTag(tag) {
        selected = selected.filter((t) => t.id !== tag.id);
    }

    /** @param {number} n */
    function toHex(n) {
        return n.toString(16);
    }

    function handleKeyDown(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            if (!results.length && !e.target.value) return;
            let name = results.length > 0 ? results[highIdx] : e.target.value;
            let tag =
                typeof name === "object"
                    ? name
                    : {
                          name,
                          id: toHex(Math.floor(Date.now() / 1000)),
                          count: 1,
                      };
            if (!createNew && !tags.includes(tag)) return;
            if (!tags.includes(tag)) tags.push(tag);
            selectTag(tag);
            highIdx = 0;
        } else if (e.key === "Backspace" && !search.length) {
            selected.pop();
        } else if (e.key === "ArrowUp" && highIdx > 0) {
            highIdx--;
        } else if (e.key === "ArrowDown" && highIdx + 1 < results.length) {
            highIdx++;
        }
    }
</script>

<div class="tag-search">
    <!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_noninteractive_element_interactions -->
    <div
        class="search-wrap"
        style:width
        onclick={() => searchInput.focus()}
        role="search"
    >
        <div class="selected-tags">
            {#each selected as tag}
                <span class="tag">
                    {tag.name}
                    <button
                        type="button"
                        class="remove-tag"
                        onclick={(e) => {
                            e.stopPropagation();
                            removeTag(tag);
                        }}>×</button
                    >
                </span>
            {/each}
        </div>

        <input
            type="text"
            placeholder={!selected.length ? "Add tag" : ""}
            class="search"
            bind:value={search}
            bind:this={searchInput}
            onfocusin={() => {
                genTrie();
                isFocused = true;
            }}
            onfocusout={() => (isFocused = false)}
            onkeydown={handleKeyDown}
        />
    </div>

    <input type="hidden" {name} value={JSON.stringify(selected)} />

    {#if isFocused || search.trim()}
        <ul class="dropdown">
            {#each results as tag, idx}
                <li
                    onmousedown={() => selectTag(tag)}
                    role="option"
                    tabindex="0"
                    aria-selected={idx === highIdx}
                    class:hl={idx === highIdx}
                >
                    {tag.name}
                </li>
            {:else}
                <li>
                    No tags found. {#if createNew}
                        Press "Enter" to create a new tag{/if}
                </li>
            {/each}
        </ul>
    {/if}
</div>
