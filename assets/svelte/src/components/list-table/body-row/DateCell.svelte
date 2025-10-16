<script>
    import { displayDate } from "$lib/settings.svelte.js";
    import { postStatus } from "$lib/state.svelte.js";

    let { post, text } = $props();

    const statusLabel = (status) => {
        if (status in postStatus) return postStatus[status];
        if (status === "trash") return "Trashed";
        return status;
    };
</script>

<td class={["date", "column-date"]} data-colname={text}>
    {statusLabel(post.status)}
    {displayDate(post.date)}
</td>
