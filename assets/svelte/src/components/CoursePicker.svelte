<script lang="ts">
    import type { Course } from "$lib/models/state/course-post.svelte.ts";
    import CourseSelector from "./CourseSelector.svelte";

    interface Props {
        courses: Course[];
        selectedCourses: Object;
    }

    let { courses, selectedCourses = $bindable() }: Props = $props();
</script>

<div class="course-list bordered col">
    {#each courses as course}
        <CourseSelector {course} bind:selected={selectedCourses[course.id]} />
    {/each}
</div>
