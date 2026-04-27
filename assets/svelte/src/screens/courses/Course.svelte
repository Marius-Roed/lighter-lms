<script lang="ts">
  import Topic from "$components/Topic.svelte";
  import Icon from "$components/Icon.svelte";
  import DeleteModal from "$components/DeleteModal.svelte";
  import EditModal from "$components/EditModal.svelte";
  import MoveModal from "$components/MoveModal.svelte";
  import { flip } from "svelte/animate";
  import type { TopicData } from "$types/course.js";
  import { setContext } from "svelte";
  import { getCourseService } from "$lib/utils/index.ts";

  const service = getCourseService();

  let empty = $derived(!service.course.sortedTopics.length);
  let list: HTMLOListElement;

  const metaHover = $state({
    metaPressed: false,
  });
  setContext("metaHover", metaHover);

  function makeDummyDragged(event: DragEvent) {
    const el = document.createElement("div");
    el.classList.add("placeholder");
    el.innerHTML =
      '<li draggable="true" class="lighter-course-module"><div class="module-wrap"><div class="head"><div></div><div class="title"><h3 class="module-title">New topic</h3></div></div></div></li>';
    return el;
  }

  function handleDragOver(e: DragEvent) {
    if (!e.dataTransfer!.types.includes("application/x-lighterlms-topic")) {
      return;
    }
    e.preventDefault();

    const dragged =
      (list.querySelector('div:has(>[draggable="true"])') as HTMLLIElement) ??
      makeDummyDragged(e);

    for (const topic of list.children) {
      if (topic === dragged) continue;
      const rect = topic.getBoundingClientRect();
      const mid = rect.top + rect.height / 2;
      if (mid >= e.clientY) {
        if (topic === dragged || topic.previousElementSibling === dragged) {
          return;
        }

        topic.insertAdjacentElement("beforebegin", dragged);
        return;
      }
    }

    dragged?.remove();
    if (list.lastElementChild === dragged) return;

    list.insertAdjacentElement("beforeend", dragged);
  }

  function handleDragLeave(e: DragEvent) {
    if ((e.relatedTarget as HTMLElement)?.closest(".topics-wrap")) return;

    for (const topic of e.dataTransfer!.items) {
      if (topic.type !== "application/x-lighterlms-topic") continue;
      topic.getAsString((raw) => {
        const data = JSON.parse(raw) as TopicData;
        if (!data) return;

        if (data.courseId !== service.course.id) {
          list.querySelector(`[data-lighter-key="${data.key}"]`)?.remove();
        }
      });
    }
    list.querySelector(".placeholder")?.remove();
  }

  function handleOnDrop(e: DragEvent) {
    e.preventDefault();

    const dropped = list.querySelector(
      'div:has(>[draggable="true"]',
    ) as HTMLElement;
    let sibling = dropped.previousElementSibling as HTMLElement;

    list.querySelector(".placeholder")?.remove();

    for (const topic of e.dataTransfer!.items) {
      if (topic.type !== "application/x-lighterlms-topic") continue;

      topic.getAsString((raw) => {
        const data = JSON.parse(raw) as TopicData;
        if (!data) return;

        if (
          e.dataTransfer!.effectAllowed === "copy" ||
          data.courseId !== service.course.id
        ) {
          e.dataTransfer!.effectAllowed = "copy";
          service.insertTopic(
            data.title + " (copy)",
            sibling.dataset.lighterKey,
            data.lessons,
          );
        } else {
          service.moveTopic(
            parseInt(dropped.dataset.index),
            parseInt(sibling?.dataset?.index ?? "0"),
          );
        }
      });
    }
  }
</script>

<div class={["lighter-topics-wrap", empty && "empty"]}>
  <div class="lighter-no-topics">
    <h3>This course has no topics yet.</h3>
    <button
      type="button"
      class="lighter-btn"
      onclick={() => service.createTopic("New Topic")}
    >
      Add the first topic
    </button>
  </div>
  <ol
    bind:this={list}
    class="topics-wrap"
    ondragover={handleDragOver}
    ondragleave={handleDragLeave}
    ondrop={handleOnDrop}
  >
    {#each service.course.sortedTopics as topic, i (topic.key)}
      <div data-index={i} animate:flip>
        <Topic {topic} />
      </div>
    {/each}
  </ol>

  <div class="foot">
    <button
      type="button"
      class="lighter-btn transparent"
      onclick={() => service.createTopic("New Topic")}
    >
      <Icon name="plus" />
      Add topic
    </button>
  </div>
  <DeleteModal />
  <EditModal />
  <MoveModal />

  <div id="dummy-topic">
    <div class="head" tabindex="0" role="button">
      <div class="drag-handle">
        <Icon name="sixDots" />
      </div>
      <div class="title">
        <h3 id="dummy-title" class="editable-text module-title">
          This is the last one
        </h3>
      </div>
      <div class="lessons-amount">
        <p id="dummy-lessons">Lessons (0)</p>
      </div>
      <div class="actions">
        <div class="add">
          <Icon name="plus" />
        </div>
        <div class="expand">
          <Icon name="chevron" />
        </div>
      </div>
    </div>
  </div>
</div>
