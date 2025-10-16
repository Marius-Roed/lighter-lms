<script>
    let { post } = $props();
</script>

<th scope="row" class="check-column">
    <input id={`cb-select-${post.id}`} type="checkbox" />
    <label for={`cb-select-${post.id}`}>
        <span class="screen-reader-text">Select {post.title}</span>
    </label>
</th>
