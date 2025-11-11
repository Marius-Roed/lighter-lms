<script>
    let { post } = $props();

    const getTitle = (post) => {
        if (post.course_title) return post.course_title;

        if (post.title) {
            if (typeof post.title == "object") {
                return post.title.rendered;
            }
            return post.title;
        }

        return "Untitled";
    };
</script>

<td class={["title", "column-title"]}>
    <strong>
        <a
            class="row-title"
            href={`/wp-admin/post.php?post=${post.id}&action=edit`}
        >
            {getTitle(post)}
            {#if post.status != "publish"}
                <span
                    style="color:#000;display:inline-block;text-transform:capitalize;"
                    >&ndash; {post.status}</span
                >
            {/if}
        </a>
    </strong>
</td>
