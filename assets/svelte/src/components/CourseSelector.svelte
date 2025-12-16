<script>
    import Icon from "./Icon.svelte";

    let { course, selected = $bindable() } = $props();
    let group = $state([]),
        isOpen = $state(false);
    const les = course.topics.flatMap((t) => t.lessons.map((l) => l.ID));

    let parent = $derived.by(() => {
        if (les < 1) return false;
        return group.length === les.length;
    });
    let indeterminate = $derived.by(() => {
        if (group.length < 1) return false;
        return group.length < les.length;
    });

    const checkAll = () => (group = les),
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
                onclick={(e) => (e.target.checked ? checkAll() : uncheckAll())}
                name={course.title}
                id={course.id}
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
                                value={lesson.ID}
                                id={`${course.title}-${idx}`}
                            />
                            {lesson.post_title}
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
