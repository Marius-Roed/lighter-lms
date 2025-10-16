<!--
@component
- Creates a tag which will be editiable on click
- Usage:
    ```html
    <Editable value="Edit me" placeholder="Undefined" tag="h1" />
    ```
-->
<script>
    import { tooltip } from "$lib/tooltip";
    import Icon from "./Icon.svelte";

    /**
     * @typedef {Object} EditableProps
     * @property {string} value - The current value
     * @property {string} [className] - Classes to add to the element
     * @property {string} [placeholder] - The placeholder to show
     * @property {string} [tag] - The default tag to render
     */

    /** @type {EditableProps} */
    let {
        value = $bindable(""),
        className = "",
        placeholder = "Untitled",
        tag = "span",
        ...restProps
    } = $props();

    let isEditing = $state(false);
    let oldValue = $state(value);
    let el = $state();

    function startEdit() {
        oldValue = value;
        isEditing = true;
        setTimeout(() => el?.focus(), 0);
    }

    function finishEdit() {
        isEditing = false;
    }

    function cancelEdit() {
        value = oldValue;
        isEditing = false;
    }

    /**
     * @param {KeyboardEvent} e
     */
    function handleKeydown(e) {
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
    <a class={["editable-text", className]} href={restProps.href ?? "#"}
        >{value || placeholder}
    </a>
    <Icon
        name="pencil"
        tabindex="0"
        onclick={startEdit}
        onkeydown={(e) => e.key === "Enter" && startEdit()}
        {@attach tooltip("Click to edit")}
    />
{:else}
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <svelte:element
        this={tag}
        class={["editable-text", className]}
        onclick={() => {
            startEdit();
        }}
        onkeydown={(e) => e.key === "Enter" && startEdit()}
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
