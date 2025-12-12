<script>
    let { courses } = $props();
</script>

<div class="course-list">
    {#each courses as course}
        {course.title}
    {/each}
</div>
