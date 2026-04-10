<script lang="ts">
  import { tooltip } from "$lib/tooltip.ts";
  import Lesson from "./Lesson.svelte";
  import Icon from "./Icon.svelte";
  import Editable from "./Editable.svelte";
  import type { Topic } from "$lib/models/state/course-topic.svelte.ts";
  import { getCourseService } from "$lib/utils/index.ts";
  import { hiddenInput } from "$lib/snippets.svelte";
  import ProgressBtn from "./ui/ProgressBtn.svelte";
  import type { LessonData } from "$types/course.js";

  interface Props {
    topic: Topic;
  }
  let { topic }: Props = $props();
  const id = $props.id();

  const service = getCourseService();

  let canDrag = $state(false),
    isPopoverOpen = $state(false),
    lessonList: HTMLDivElement;

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

  function makeDummyDragged(event: DragEvent) {
    const el = document.createElement("div");
    el.classList.add("lighter-lesson", "clone");
    el.innerHTML =
      '<div class="lesson-title"><h4>Lesson</h4></div><div class="actions"><button type="button" class="lighter-btn transparent">Edit Lesson</button></div>';
    return el;
  }

  function handleDragOver(e: DragEvent) {
    if (!e.dataTransfer!.types.includes("application/x-lighterlms-lesson"))
      return;

    e.preventDefault();

    const dragged = (lessonList.querySelector(".clone") ??
      document.querySelector(".lighter-lesson.clone") ??
      makeDummyDragged(e)) as HTMLElement;

    dragged.style.opacity = "0.5";
    dragged.style.display = "flex";
    for (const lesson of lessonList.children) {
      if (lesson === dragged) continue;
      const rect = lesson.getBoundingClientRect();
      const mid = rect.top + rect.height / 2;
      if (mid >= e.clientY) {
        if (lesson === dragged || lesson.previousElementSibling === dragged)
          return;

        lesson.insertAdjacentElement("beforebegin", dragged);
        return;
      }
    }

    dragged?.remove();
    if (lessonList.lastElementChild === dragged) return;

    lessonList.insertAdjacentElement("beforeend", dragged);
  }

  function handleDragLeave(e: DragEvent) {
    if ((e.relatedTarget as HTMLElement)?.closest(".lighter-lesson-wrap"))
      return;

    (lessonList.querySelector(".clone") as HTMLElement)?.remove();
  }

  function handleDrop(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();

    const dropped = lessonList.querySelector(".clone");
    const childIndex = Array.prototype.indexOf.call(
      dropped.parentNode.children,
      dropped,
    );
    const toIndex = childIndex > 0 ? childIndex - 1 : 0;

    for (const lesson of e.dataTransfer!.items) {
      if (lesson.type !== "application/x-lighterlms-lesson") continue;

      lesson.getAsString((raw) => {
        const data = JSON.parse(raw) as LessonData;
        if (!data) return;

        const sortOrder =
          data._lighter_meta[window.LighterLMS.course.id]?.topics.find(
            (t) => t.key === data.parentTopic,
          ).sort_order ?? 10;
        const fromIndex = sortOrder / 10 - 1;
        const toTopic = data.parentTopic !== topic.key ? topic.key : null;

        service.moveLesson(fromIndex, toIndex, data.parentTopic, toTopic);
      });
    }
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
        if ((e.target as HTMLElement).tagName == "INPUT") return;

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
          save={(val) => new Promise(() => service.renameTopic(topic.key, val))}
        />
      </div>
      <div class="lessons-amount">
        <p>Lessons ({topic.sortedLessons?.length ?? 0})</p>
      </div>
      <div class="actions">
        <div class="add">
          <button
            type="button"
            class="add-lesson"
            onclick={() => {
              topic.toggleIsExpanded(true);
              service.createLesson(topic.key, {
                title: "New Lesson",
                lesson_type: "text",
              });
            }}
            {@attach tooltip("Add a lesson")}
          >
            <Icon name="plus" />
          </button>
        </div>
        <div class="expand">
          <button
            type="button"
            class="expand-module"
            onclick={() => topic.toggleIsExpanded()}
            {@attach tooltip(topic.isExpanded ? "Collapse" : "Expand")}
          >
            <Icon name="chevron" />
          </button>
        </div>
      </div>
    </div>
    <div
      bind:this={lessonList}
      class={["lighter-lesson-wrap", topic.isExpanded && "expanded"]}
      ondragover={handleDragOver}
      ondragleave={handleDragLeave}
      ondrop={handleDrop}
    >
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
      <button type="button" class="lighter-btn transparent">Cancel</button>
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
