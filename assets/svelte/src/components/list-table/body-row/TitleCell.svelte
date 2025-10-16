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
        </a>
    </strong>
</td>
