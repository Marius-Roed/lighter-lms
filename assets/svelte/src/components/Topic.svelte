<script lang="ts">
    import { tooltip } from "$lib/tooltip.ts";
    import Lesson from "./Lesson.svelte";
    import Icon from "./Icon.svelte";
    import Editable from "./Editable.svelte";
    import type { Topic } from "$lib/models/state/course-topic.svelte.ts";
    import { getCourseService } from "$lib/utils/index.ts";
    import { hiddenInput } from "$lib/snippets.svelte";
    import ProgressBtn from "./ui/ProgressBtn.svelte";

    interface Props {
        topic: Topic;
    }
    let { topic }: Props = $props();
    const id = $props.id();

    const service = getCourseService();

    let canDrag = $state(false);

    let isPopoverOpen = $state(false);

    function handleHeadClick(e: MouseEvent) {
        if (
            (e.target as HTMLElement).closest(
                ".drag-handle, button:not(.expand-module), input, .editable-text",
            )
        )
            return;

        topic.toggleIsExpanded();
    }

    function handleMouseDown() {
        canDrag = true;
    }

    function handleMouseUp() {
        canDrag = false;
    }

    function handleDragStart(e: DragEvent) {
        if (!canDrag) {
            return e.preventDefault();
        }

        const dragging = (e.target as HTMLElement).closest(
            ".lighter-course-module",
        );
        const dummy = document.getElementById("dummy-topic");

        if (dragging) {
            const rect = dragging.getBoundingClientRect();
            const xOffset = e.clientX - rect.left;
            const yOffset = e.clientY - rect.top;

            const title = dummy.querySelector("#dummy-title");
            if (title)
                title.textContent =
                    (dragging.querySelector(".editable-text") as HTMLElement)
                        ?.innerText ?? "Topic";

            const lessons = dummy.querySelector("#dummy-lessons");
            if (lessons)
                lessons.textContent =
                    (dragging.querySelector(".lessons-amount p") as HTMLElement)
                        ?.innerText ?? "Lessons (0)";

            e.dataTransfer!.setDragImage(dummy, xOffset, yOffset);
        }

        e.dataTransfer!.effectAllowed = "copyMove";
        e.dataTransfer!.items.add(
            service.serializeTopic(topic),
            "application/x-lighterlms-topic",
        );

        requestAnimationFrame(
            () => ((e.target as HTMLElement).style.opacity = "0.5"),
        );
    }

    function handleDragEnd(e: DragEvent) {
        canDrag = false;
        (e.target as HTMLElement).style.opacity = "";
    }
</script>

<li
    class="lighter-course-module"
    {id}
    style:--anchor={`--${topic.key}`}
    draggable={canDrag}
    ondragstart={handleDragStart}
    ondragend={handleDragEnd}
>
    <div class="module-wrap" aria-expanded={topic.isExpanded}>
        <div class="module-data hidden">
            {@render hiddenInput(`topics[${topic.key}]`, topic.getHiddenData())}
        </div>
        <div
            class="head"
            onclick={handleHeadClick}
            onkeypress={(e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    topic.toggleIsExpanded();
                }
            }}
            tabindex="0"
            role="button"
        >
            <!-- svelte-ignore a11y_no_static_element_interactions -->
            <div
                class="drag-handle"
                aria-label="Drag to reorder module"
                onmousedown={handleMouseDown}
                onmouseup={handleMouseUp}
                onmouseleave={handleMouseUp}
            >
                <Icon name="sixDots" />
            </div>
            <div class="title">
                <Editable
                    bind:value={topic.title}
                    className="module-title"
                    tag="h3"
                />
            </div>
            <div class="lessons-amount">
                <p>Lessons ({topic.lessons?.length ?? 0})</p>
            </div>
            <div class="actions">
                <div class="add">
                    <button
                        type="button"
                        class="add-lesson"
                        onclick={() =>
                            service.createLesson(topic.key, {
                                title: "New Lesson",
                                lesson_type: "text",
                            })}
                        {@attach tooltip("Add a lesson")}
                    >
                        <Icon name="plus" />
                    </button>
                </div>
                <div class="expand">
                    <button
                        type="button"
                        class="expand-module"
                        onclick={topic.toggleIsExpanded}
                        {@attach tooltip(
                            topic.isExpanded ? "Collapse" : "Expand",
                        )}
                    >
                        <Icon name="chevron" />
                    </button>
                </div>
            </div>
        </div>
        <div class={["lighter-lesson-wrap", topic.isExpanded && "expanded"]}>
            {#each topic.sortedLessons as lesson (lesson.id)}
                <Lesson {lesson} />
            {:else}
                <div class="not-found-wrapper lesson">
                    <div class="not-found">
                        <p>No lessons found</p>
                        <button
                            type="button"
                            class="lighter-btn"
                            onclick={() =>
                                service.createLesson(topic.key, {
                                    title: "New Lesson",
                                })}>Create the first lesson</button
                        >
                    </div>
                </div>
            {/each}
        </div>
    </div>
    <button
        type="button"
        class="delete-topic"
        class:pressed={isPopoverOpen}
        popovertarget={`delete-${topic.key}`}
        popovertargetaction="show"
    >
        <Icon name="trash" size="1.5em" fill={true} />
    </button>
    <div
        id={`delete-${topic.key}`}
        class="delete-modal"
        ontoggle={(e) => {
            isPopoverOpen = e.newState === "open";
        }}
        popover
    >
        <p>Are you sure you want to delete <b>{topic.title}</b>?</p>
        <p><strong>This action cannot be undone</strong></p>
        <div class="flex row">
            <button type="button" class="lighter-btn transparent">Cancel</button
            >
            <ProgressBtn onhold={() => service.deleteTopic(topic.key)}
                >Confirm</ProgressBtn
            >
        </div>
    </div>
</li>

<style>
    .module-wrap:has(.expanded) .actions .expand-module {
        transform: rotate(180deg);
    }
</style>
