<script>
    import CourseSelector from "./CourseSelector.svelte";

    let { courses, selectedCourses = $bindable() } = $props();
</script>

<div class="course-list bordered col">
    {#each courses as course}
        <CourseSelector {course} bind:selected={selectedCourses[course.id]} />
    {/each}
</div>
