<script>
    import * as Cells from "./body-row";

    /**
     * @typedef {Object} PostRowProps
     * @property {import('$lib/posts.svelte').Post} post
     * @property {Object} columns
     */

    /** @type {PostRowProps} */
    let { post, columns } = $props();
</script>

<tr id={`post-${post.id}`} class="lighter-post">
    {#each Object.entries(columns) as [type, text]}
        {#if type === "html"}
            <td>wrong</td>
        {:else if type === "cb"}
            <Cells.CheckCell {post} />
        {:else if type === "date"}
            <Cells.DateCell {post} {text} />
        {:else if type === "tags"}
            <Cells.TagsCell {post} />
        {:else if type === "title"}
            <Cells.TitleCell {post} />
        {/if}
    {/each}
</tr>
