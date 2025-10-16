<script>
    /**
     * @typedef {Object} TopicProps
     * @property {import('$lib/state.svelte').Topic} topic
     * @property {number} i
     */

    import {
        lessons,
        addLesson,
        moveTopic,
        confirmDeleteTopic,
    } from "$lib/state.svelte.js";
    import { tooltip } from "$lib/tooltip.js";
    import Lesson from "./Lesson.svelte";
    import Icon from "./Icon.svelte";
    import Editable from "./Editable.svelte";

    /** @type TopicProps */
    let { topic = $bindable(), i } = $props();

    // INFO: Component state
    let isExpanded = $state(false);
    const topicLessons = $derived(
        lessons
            .filter((l) => l.parentTopicKey === topic.key)
            .sort((a, b) => a.sortOrder - b.sortOrder),
    );

    $effect(() => {
        const expanded = JSON.parse(
            localStorage.getItem("expandedTopics") || "[]",
        );
        isExpanded = expanded.includes(topic.key);
    });

    function toggleExpand() {
        isExpanded = !isExpanded;

        const expanded = JSON.parse(
            localStorage.getItem("expandedTopics") || "[]",
        );
        if (isExpanded) {
            if (!expanded.includes(topic.key)) expanded.push(topic.key);
        } else {
            const idx = expanded.indexOf(topic.key);
            if (idx > -1) expanded.splice(idx, 1);
        }
        localStorage.setItem("expandedTopics", JSON.stringify(expanded));
    }

    let allowDrag = false;

    function handleMouseDown() {
        allowDrag = true;
    }

    function handleMouseUp() {
        allowDrag = false;
    }

    /**
     * @param {DragEvent} e
     */
    function handleDragStart(e) {
        if (!allowDrag) {
            e.preventDefault();
            return;
        }

        e.dataTransfer.setData(
            "text/plain",
            JSON.stringify({ sourceIndex: i }),
        );
        e.dataTransfer.effectAllowed = "move";
    }

    /**
     *
     * @param {DragEvent} e
     */
    function handleDragOver(e) {
        e.preventDefault();
    }

    /**
     * @param {DragEvent} e
     */
    function handleDrop(e) {
        e.preventDefault();
        const dragDataStr = e.dataTransfer.getData("text/plain");
        if (!dragDataStr) return;
        const { sourceIndex } = JSON.parse(dragDataStr);
        const target = i;
        if (sourceIndex === target) return;
        moveTopic(sourceIndex, target);
    }

    function addNewLesson() {
        addLesson(topic.key, "New lesson");
        isExpanded = true;
    }
</script>

<li
    class="lighter-course-module"
    id={(i + 1).toString()}
    draggable="true"
    ondragstart={handleDragStart}
    ondragover={handleDragOver}
    ondrop={handleDrop}
>
    <div class="module-wrap" aria-expanded={isExpanded}>
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
        <div class="head">
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
                <p>Lessons ({topicLessons.length})</p>
            </div>
            <div class="actions">
                <div class="add">
                    <button
                        type="button"
                        class="add-lesson"
                        onclick={addNewLesson}
                        {@attach tooltip("Add a lesson")}
                    >
                        <Icon name="plus" />
                    </button>
                </div>
                <div class="expand">
                    <button
                        type="button"
                        class="expand-module"
                        onclick={toggleExpand}
                        {@attach tooltip(isExpanded ? "Collapse" : "Expand")}
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
        <div class={["lighter-lesson-wrap", isExpanded && "expanded"]}>
            {#each topicLessons as lesson (lesson.key)}
                <Lesson key={lesson.key} len={topicLessons.length} />
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
