<script lang="ts">
  import { onMount, type Component } from "svelte";
  import type { HTMLAttributes } from "svelte/elements";

  interface TabItem {
    label: string;
    component: Component;
  }

  interface Props extends HTMLAttributes<HTMLElement> {
    items: TabItem[];
    activeTab?: number;
  }

  let { items, activeTab: initialTab = 0, ...props }: Props = $props();

  // svelte-ignore state_referenced_locally
  let activeTab = $state(initialTab);
  let list = $state();
  let tabRefs = $state([]);

  let activeItem = $derived(items[activeTab]);
  let tabId = $derived(toAttr(activeItem.label) + "-tab");
  let panelId = $derived(toAttr(activeItem.label) + "-panel");

  function toAttr(s: string) {
    return s
      .replaceAll(/[^a-zA-Z0-9\s-]/g, "")
      .replaceAll(/\s+/g, "-")
      .toLowerCase();
  }

  function setActive(index: number) {
    if (index < 0 || index >= items.length) return;

    activeTab = index;
    const url = new URL(window.location.href);
    url.searchParams.set("tab", toAttr(items[index].label));
    window.history.replaceState({}, "", url.toString());
  }

  function handleKeydown(e: KeyboardEvent, currentIdx: number) {
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
