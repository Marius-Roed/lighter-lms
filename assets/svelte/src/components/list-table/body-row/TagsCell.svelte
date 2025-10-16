<script>
    let { post } = $props();
</script>

<td class="tags">
    <span aria-hidden={!post.tags}>
        {#each post.tags as tag, i}
            {#if i !== 0}{", "}
            {/if}{tag}
        {:else}
            —
        {/each}
    </span>
</td>
