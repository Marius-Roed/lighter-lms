<script>
    import { getTopicLessons, topics } from "$lib/state.svelte.js";
    import MultiCheckbox from "./MultiCheckbox.svelte";

    let { ...props } = $props();
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
        {#each topics as topic (topic.key)}
            {@const items = getTopicLessons(topic)}
            <MultiCheckbox
                {items}
                title={topic.title}
                bind:group={topic.access}
            />
        {/each}
    </div>
</div>
