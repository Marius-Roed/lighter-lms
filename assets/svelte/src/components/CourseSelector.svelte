<script lang="ts">
    import type { Lesson } from "$lib/models/state/course-lesson.svelte.ts";
    import type { Course } from "$lib/models/state/course-post.svelte.ts";
    import Icon from "./Icon.svelte";

    interface Props {
        course: Course;
        selected: Lesson[];
    }

    let { course, selected = $bindable() }: Props = $props();
    let group = $state<Lesson[]>([]),
        isOpen = $state(false);

    let parent = $derived.by(() => {
        if (course.allLessons.length < 1) return false;
        return group.length === course.allLessons.length;
    });
    let indeterminate = $derived.by(() => {
        if (group.length < 1) return false;
        return group.length < course.allLessons.length;
    });

    const checkAll = () => (group = course.allLessons),
        uncheckAll = () => (group = []);

    $effect(() => {
        selected = group;
    });
</script>

<div class="course-wrap col" aria-expanded={isOpen}>
    <div class="course">
        <label>
            <input
                type="checkbox"
                bind:checked={parent}
                bind:indeterminate
                onclick={(e) =>
                    (e.target as HTMLInputElement).checked
                        ? checkAll()
                        : uncheckAll()}
                name={course.title}
                id={String(course.id)}
            />
            {course.title}
        </label>
        <button type="button" onclick={() => (isOpen = !isOpen)}
            ><Icon name="chevron" /></button
        >
    </div>
    {#if isOpen}
        {#each course.topics as topic}
            <div class="topic col">
                <b>{topic.title}</b>
                <div class="grid">
                    {#each topic.lessons as lesson, idx}
                        <label>
                            <input
                                type="checkbox"
                                bind:group
                                name={course.title}
                                value={lesson.id}
                                id={`${course.title}-${idx}`}
                            />
                            {lesson.title}
                        </label>
                    {:else}
                        <span class="col center">This topic has no lessons</span
                        >
                    {/each}
                </div>
            </div>
        {:else}
            <span class="col center">This course has no topics yet.</span>
        {/each}
    {/if}
</div>
