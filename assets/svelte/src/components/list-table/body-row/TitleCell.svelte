<script>
    let { post } = $props();
</script>

<td class={["title", "column-title"]}>
    <strong>
        <a
            class="row-title"
            href={`/wp-admin/post.php?post=${post.id}&action=edit`}
        >
            {post.course_title ?? post.title ?? "Untitled"}
            {#if post.status != "publish"}
                <span
                    style="color:#000;display:inline-block;text-transform:capitalize;"
                    >&ndash; {post.status}</span
                >
            {/if}
        </a>
    </strong>
</td>
