<!--
@component
- Creates a tag which will be editiable on click
- Usage:
    ```html
    <Editable value="Edit me" placeholder="Undefined" tag="h1" />
    ```
- Notes: You can sanitize the value with the "sanitize" attribute, which requires a callback.
-->
<script lang="ts" generics="S extends keyof HTMLElementTagNameMap">
  import { tooltip } from "$lib/tooltip.ts";
  import type { HTMLAttributes } from "svelte/elements";
  import Icon from "./Icon.svelte";

  interface Props extends HTMLAttributes<HTMLInputElement> {
    value: string;
    className?: string;
    placeholder?: string;
    tag?: S;
    sanitize?: (val: string, oldVal: string) => string;
    save?: (val: string) => Promise<void>;
  }

  let {
    value = $bindable(""),
    className = "",
    placeholder = "Untitled",
    tag = "span" as S,
    sanitize = (val) => val,
    save,
    ...restProps
  }: Props = $props();

  let isEditing = $state(false);
  let isSaving = $state(false);
  let oldValue: string = $state(value);
  let el: HTMLElement = $state();

  function startEdit() {
    oldValue = value;
    isEditing = true;
    setTimeout(() => el?.focus(), 0);
  }

  async function finishEdit() {
    isEditing = false;
    value = sanitize(value, oldValue);

    if (!save || value === oldValue) return;

    isSaving = true;
    try {
      await save(value);
    } catch {
      value = oldValue;
    } finally {
      isSaving = false;
    }
  }

  function cancelEdit() {
    value = oldValue;
    isEditing = false;
  }

  function handleKeydown(e: KeyboardEvent) {
    if (e.key === "Enter") {
      e.preventDefault();
      el.blur();
    } else if (e.key === "Escape") {
      cancelEdit();
    }
  }
</script>

{#if isEditing}
  <input
    bind:this={el}
    bind:value
    onblur={finishEdit}
    onkeydown={handleKeydown}
    aria-label="Editable text"
    type="text"
  />
{:else if tag === "a"}
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <a class={["editable-text", className]} href={restProps?.href ?? "#"}
    >{value || placeholder}
  </a>
  <Icon
    name="pencil"
    tabindex="0"
    onclick={startEdit}
    onkeydown={(e) => e.key === "Enter" && startEdit()}
    {...restProps}
    {@attach tooltip("Click to edit")}
  />
{:else}
  <!-- svelte-ignore a11y_no_static_element_interactions -->
  <svelte:element
    this={tag as string}
    class={["editable-text", className]}
    onclick={() => {
      startEdit();
    }}
    onkeydown={(e) => e.key === "Enter" && startEdit()}
    {...restProps}
    {@attach tooltip("Click to edit")}
    >{value || placeholder}
    <Icon name="pencil" tabindex="0" />
  </svelte:element>
{/if}

<style>
  .editable-text:hover {
    cursor: pointer;
  }
</style>
