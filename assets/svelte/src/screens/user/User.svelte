<script>
    import Icon from "$components/Icon.svelte";

    let { courses } = window.LighterLMS.user;
</script>

<div class="lighter-search">
    <input
        type="text"
        placeholder="Search: Programming 101, Yoga poses..."
        style="width:100%"
    />
</div>
<div class="lighter-courses small">
    {#each courses as course}
        <div class="lighter-course bordered">
            <div
                class="row middle"
                style="justify-content:space-between;width:100%;"
            >
                <h3>{course.title}</h3>
                <Icon name="chevron" />
            </div>
            {#each course.topics as topic}
                <div class="course-topics">
                    <label>
                        <b>{topic.title}</b>
                        {#if !topic.lessons}<input type="checkbox" />{/if}
                    </label>
                    {#each topic.lessons as lesson}
                        <p>
                            <label>
                                {lesson.post_title}
                                <input type="checkbox" />
                            </label>
                        </p>
                    {:else}
                        <p>This topic has no lessons</p>
                    {/each}
                </div>
            {:else}
                <p>This course has no topics</p>
            {/each}
        </div>
    {/each}
</div>
