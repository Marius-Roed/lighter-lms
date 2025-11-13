<script>
    import settings, { initAccess, isEmpty } from "$lib/settings.svelte";
    import { getTopic, getTopicLessons } from "$lib/state.svelte.js";
    import MultiCheckbox from "./MultiCheckbox.svelte";

    let { ...props } = $props();

    if (isEmpty(settings.product.access)) {
        settings.product.access = initAccess();
    }
</script>

<div class="course-access-wrap" {...props}>
    <h2>Limit availability</h2>
    <div class="col">
        <label>
            Limit seating
            <input type="number" name="stock_limit" min="0" />
        </label>
        <span>Leave empty to not limit</span>
    </div>
    <hr />
    <h2>Grant access</h2>
    <div class="course-access-selectors">
        {#each Object.keys(settings.product.access) as key (key)}
            {@const topic = getTopic(key)}
            {@const items = getTopicLessons(topic)}
            <MultiCheckbox
                {items}
                title={topic.title}
                bind:group={settings.product.access[key]}
            />
        {:else}
            <p>No topics found</p>
        {/each}
    </div>
</div>
