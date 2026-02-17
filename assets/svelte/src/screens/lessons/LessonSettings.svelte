<script>
    import Editable from "$components/Editable.svelte";
    import FetchSearch from "$components/FetchSearch.svelte";
    import { lessonSettings } from "$lib/models/state/lesson-post.svelte";

    const formatVal = (v) => {
        if (v instanceof Date) {
            return Math.floor(v.getTime() / 1000);
        }
        if (
            typeof v === "boolean" ||
            String(v)?.toLowerCase() === "on" ||
            String(v)?.toLowerCase() === "off"
        ) {
            return v ? (v === "off" ? "false" : "true") : "false";
        }

        return v ?? "";
    };

    const normSettings = $derived.by(() => {
        const parents = lessonSettings.parents.flatMap(
            (t) => Object.keys(t)[0],
        );
        return {
            ...lessonSettings,
            parents,
        };
    });
</script>

{#snippet hiddenInput(objects, baseName = "")}
    {@const oldName = baseName}
    {#each Object.entries(objects) as [n, value]}
        {@const baseName = `${oldName}[${n}]`}
        {#if typeof value === "object" && value !== null && !(value instanceof Date)}
            {@render hiddenInput(value, baseName)}
        {:else}
            {@const name = `settings${baseName}`}
            <input type="hidden" {name} value={formatVal(value)} />
        {/if}
    {/each}
{/snippet}

<div class="lighter-settings-data">
    {@render hiddenInput(normSettings)}
</div>

<div class="settings-wrap">
    <div class="slug">
        <h2>Lesson slug</h2>
        <p>
            {window.location.origin}/my-course?lesson=<Editable
                bind:value={lessonSettings.slug}
                placeholder="new-lesson"
                tag="b"
                sanitize={(/** @type {string} */ v) => {
                    return v.replaceAll(" ", "-").toLowerCase();
                }}
            />
        </p>
    </div>
    <div class="parents">
        <h2>Linked courses</h2>
        <FetchSearch
            url="/wp-json/lighterlms/v1/topic?q="
            multi={true}
            bind:value={lessonSettings.parents}
            placeholder="Link to course"
        />
    </div>
</div>
