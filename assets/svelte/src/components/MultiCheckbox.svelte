<script lang="ts">
  import type { Lesson } from "$lib/models/state/course-lesson.svelte.ts";
  import Icon from "./Icon.svelte";

  interface Props {
    title?: string;
    group?: (string | number)[];
    items?: Lesson[];
  }

  let {
    title = "",
    group = $bindable([]),
    items = [],
    ...props
  }: Props = $props();

  let isOpen = $state(false);
  let trigger = $derived(title ?? "Undefined");
  let totalItems = $derived(Object.keys(items).length);

  let indeterminate = $derived(group.length > 0 && group.length < totalItems);
  let groupChecked = $derived(group.length && group.length == totalItems);

  const onclick = (e: MouseEvent) => {
    const cb = e.currentTarget as HTMLInputElement;
    if (!cb.checked) {
      group = [];
    } else {
      group = items.map((l) => l.id);
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
          disabled={!items.length}
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
          <input type="checkbox" bind:group value={item.id} />
          {item.title}
        </label>
      {:else}
        <p>No items found for group</p>
      {/each}
    </div>
  {/if}
</div>
