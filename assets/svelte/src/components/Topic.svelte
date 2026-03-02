<script lang="ts">
    import { tooltip } from "$lib/tooltip.ts";
    import Lesson from "./Lesson.svelte";
    import Icon from "./Icon.svelte";
    import Editable from "./Editable.svelte";
    import type { Topic } from "$lib/models/state/course-topic.svelte.ts";
    import { getCourseService } from "$lib/utils/index.ts";

    interface Props {
        topic: Topic;
    }
    let { topic }: Props = $props();
    const id = $props.id();

    const service = getCourseService();

    let canDrag = $state(false);

    function handleHeadClick(e: MouseEvent) {
        if (
            (e.target as HTMLElement).closest(
                ".drag-handle, button, input, .editable-text",
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
    draggable={canDrag}
    ondragstart={handleDragStart}
    ondragend={handleDragEnd}
>
    <div class="module-wrap" aria-expanded={topic.isExpanded}>
        <div class="module-data hidden">
            <input
                type="hidden"
                name={`topics[${topic.key}][key]`}
                value={topic.key}
            />
            <input
                type="hidden"
                name={`topics[${topic.key}][title]`}
                value={topic.title}
            />
            <input
                type="hidden"
                name={`topics[${topic.key}][sortOrder]`}
                value={topic.sortOrder}
            />
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
        <button
            type="button"
            class="delete-topic"
            onclick={() => confirmDeleteTopic(topic)}
            ><Icon
                name="trash"
                className="delete"
                size="1.5em"
                fill={true}
            /></button
        >
        <div class={["lighter-lesson-wrap", topic.isExpanded && "expanded"]}>
            {#each topic.sortedLessons as lesson (lesson.id)}
                <Lesson {lesson} />
            {/each}
        </div>
    </div>
</li>

<style>
    .drag-handle {
        display: flex;
        align-items: center;

        &:hover {
            cursor: grab;
        }
    }

    .lighter-lesson-wrap {
        display: none;

        &.expanded {
            display: block;
        }
    }

    .module-wrap:has(.expanded) .actions .expand-module {
        transform: rotate(180deg);
    }
</style>
