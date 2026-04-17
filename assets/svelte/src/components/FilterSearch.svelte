<script lang="ts">
  import { FuzzyTrie } from "$lib/fuzzytrie.ts";
  import { SvelteSet } from "svelte/reactivity";

  interface Props {
    name: string;
    tags: tag[];
    createNew?: boolean;
    width?: string | number;
    selected?: number[];
  }

  let {
    name,
    tags,
    createNew = false,
    width = "",
    selected = $bindable([]),
  }: Props = $props();

  let search = $state("");
  let searchInput: HTMLInputElement;
  let trie: FuzzyTrie | undefined;
  let isFocused = $state(false);
  let highIdx = $state(0);

  const selectedSet = $derived(new SvelteSet(selected));
  const selectedTags = $derived(tags.filter((t) => selectedSet.has(t.id)));

  let results = $derived.by(() => {
    if (!trie) return [];
    if (!search.trim()) {
      return [...tags]
        .filter((t) => !selectedSet.has(t.id))
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

  function selectTag(tag: tag) {
    if (!selectedSet.has(tag.id)) {
      selected = [...selected, tag.id];
    }
    search = "";
    highIdx = 0;
  }

  function removeTag(tag: tag) {
    selected = selected.filter((s) => s !== tag.id);
  }

  function handleKeyDown(e) {
    if (e.key === "Enter") {
      e.preventDefault();
      if (!results.length && !search.trim()) return;

      if (results.length > 0) {
        selectTag(results[highIdx]);
      } else if (createNew && search.trim()) {
        const newTag: tag = {
          id: Date.now(),
          name: search.trim(),
          count: 1,
          description: "",
          slug: search.trim().toLowerCase().replace(/\s+/g, "-"),
          taxonomy: "course-tag",
        };
        tags = [...tags, newTag];
        trie?.insert(newTag.name, newTag);
        selectTag(newTag);
      }
    } else if (e.key === "Backspace" && !search.length) {
      selected = selected.slice(0, -1);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      if (highIdx > 0) highIdx--;
    } else if (e.key === "ArrowDown") {
      e.preventDefault();
      if (highIdx + 1 < results.length) highIdx++;
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
      {#each selectedTags as tag (tag.id)}
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
      {#each results as tag, idx (tag.id)}
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
