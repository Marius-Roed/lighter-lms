<script>
    import Icon from "$components/Icon.svelte";

    const COURSES_PARAM = "lighter-courses";
    const INIT_COUNT = 6;
    const PLACEHOLDER_IMG = "https://placehold.co/230/D2C8E1/663399?text=%3F";
    const owned = new Map(
        (window.LighterLMS.user.owns ?? []).map(({ course_id, lessons }) => [
            course_id,
            lessons.map(String),
        ]),
    );

    /** @type {string} */
    let search = $state("");

    let userRequestedAll = $state(false);

    /** @type {Course[]} */
    const rawCourses = $state(
        (window.LighterLMS?.user?.courses ?? []).map((c) => ({
            ...c,
            topics: c.topics.map((t) => {
                const allowed = new Set(owned.get(c.id) ?? []);
                return {
                    ...t,
                    group: t.lessons
                        .map((l) => l.ID)
                        .filter((id) => allowed.has(id)),
                };
            }),
            open: false,
            hidden: false,
        })),
    );

    let shouldShowAll = $derived.by(() => {
        if (userRequestedAll || !!search) return true;
        const params = new URLSearchParams(window.location.search);
        return params.get(COURSES_PARAM) === "-1";
    });

    const courses = $derived.by(() => {
        return shouldShowAll ? rawCourses : rawCourses.slice(0, INIT_COUNT);
    });

    const showAllUrl = $derived.by(() => {
        const url = new URL(window.location.href);
        url.searchParams.set(COURSES_PARAM, "-1");
        return url.toString();
    });

    /**
     * @param {Topic} topic
     * @returns {boolean}
     */
    function isIndeterminate(topic) {
        const selected = topic.group.length;
        return selected > 0 && selected < topic.lessons.length;
    }

    /**
     * @param {Topic} topic
     * @param {boolean} checked
     */
    function toggleAllLessons(topic, checked) {
        topic.group = checked ? Array.from(topic.lessons.map((l) => l.ID)) : [];
    }

    /**
     * @param {Course} course
     */
    function toggleCourse(course) {
        course.open = !course.open;
    }

    /**
     * @param {Event} e
     */
    function showAllCourses(e) {
        e.preventDefault();
        const url = new URL(window.location.href);
        url.searchParams.set(COURSES_PARAM, "-1");
        window.history.replaceState({}, "", url.toString());
        userRequestedAll = true;
    }

    /**
     * @param {Course} course
     */
    function getCourseImage(course) {
        return course.image?.src ?? PLACEHOLDER_IMG;
    }

    $effect(() => {
        courses.forEach((c) => {
            if (
                search.length &&
                !c.title.toLowerCase().includes(search.toLowerCase())
            ) {
                c.hidden = true;
            } else {
                c.hidden = false;
            }
        });
    });
</script>

<div class="lighter-search">
    <input
        type="text"
        placeholder="Search: Programming 101, Yoga poses..."
        bind:value={search}
        aria-label="Search courses"
        id="course-search"
    />
</div>

<div class="lighter-courses small">
    {#each courses as course (course.id)}
        <div
            class="lighter-course bordered"
            style:display={course.hidden ? "none" : ""}
        >
            <div class="col" style="gap:1em;">
                <img
                    src={getCourseImage(course)}
                    alt="{course.title} thumbnail"
                    loading="lazy"
                />

                {#if course.topics.length}
                    <button
                        class="row middle course-btn"
                        style="justify-content:space-between;width:100%;"
                        type="button"
                        onclick={() => toggleCourse(course)}
                        aria-expanded={course.open}
                        aria-label="Toggle {course.title} topics"
                    >
                        <h3>{course.title}</h3>
                        <Icon name="chevron" />
                    </button>
                {:else}
                    <div>
                        <h3>{course.title}</h3>
                    </div>
                {/if}
            </div>

            {#each course.topics as topic (topic.key)}
                <div class={["course-topics col", !course.open && "hidden"]}>
                    {#if topic.lessons.length}
                        <label>
                            <b>{topic.title}</b>
                            <input
                                type="checkbox"
                                checked={topic.group.length ===
                                    topic.lessons.length}
                                indeterminate={isIndeterminate(topic)}
                                onchange={(e) => {
                                    toggleAllLessons(
                                        topic,
                                        e.currentTarget.checked,
                                    );
                                }}
                                id={topic.key}
                                aria-label="Select all lessons in {topic.title}"
                            />
                        </label>
                    {:else}
                        <span><b>{topic.title}</b></span>
                    {/if}

                    {#each topic.lessons as lesson (lesson.ID)}
                        <label>
                            {lesson.post_title}
                            <input
                                type="hidden"
                                name="lighter-courses[{course.id}][{lesson.ID}]"
                                value="false"
                            />
                            <input
                                type="checkbox"
                                id={lesson.ID}
                                value={lesson.ID}
                                name="lighter-courses[{course.id}][{lesson.ID}]"
                                bind:group={topic.group}
                            />
                        </label>
                    {:else}
                        <p>This topic has no lessons</p>
                    {/each}
                </div>
            {:else}
                <p><b>This course has no topics</b></p>
            {/each}
        </div>
    {/each}

    {#if !shouldShowAll}
        <div class="lighter-show-more">
            <a
                href={showAllUrl}
                class="show-lighter-courses"
                onclick={showAllCourses}>Show all courses</a
            >
        </div>
    {/if}
</div>
