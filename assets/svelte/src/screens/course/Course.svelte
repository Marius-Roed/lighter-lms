<script>
    import { topics, addTopic } from "$lib/state.svelte.js";
    import Topic from "$components/Topic.svelte";
    import DeleteModal from "$components/DeleteModal.svelte";
    import Icon from "$components/Icon.svelte";

    let { screen, wpData } = $props();

    let empty = $derived(!topics.length);
</script>

<div class={["lighter-topics-wrap", empty && "empty"]}>
    <div class="lighter-no-topics">
        <h3>This course has no topics yet.</h3>
        <button type="button" class="lighter-btn" onclick={() => addTopic()}>
            Add the first topic
        </button>
    </div>
    <ol class="topics-wrap">
        {#each topics as topic, i}
            <Topic bind:topic={topics[i]} {i} />
        {/each}
    </ol>

    <div class="foot">
        <button
            type="button"
            class="lighter-btn transparent"
            onclick={() => addTopic()}
        >
            <Icon name="plus" />
            Add topic
        </button>
    </div>
    <DeleteModal />
</div>
