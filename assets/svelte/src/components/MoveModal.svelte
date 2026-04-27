<script lang="ts">
  import Icon from "./Icon.svelte";
  import { getCourseService } from "$lib/utils/index.ts";

  let service = getCourseService();
  let selectedTopic = $state(""),
    selectedLesson = $state(0);

  function close() {
    service.moveModal.isOpen = false;
  }

  /**
   * Attach dialog to state
   *
   * @param {HTMLDialogElement} el
   */
  function dialogControl(el: HTMLDialogElement) {
    $effect(() => {
      if (service.moveModal.isOpen) {
        if (!el.open) el.showModal();
        document.body.style.position = "fixed";
        document.body.style.top = `-${window.scrollY}px`;
      } else {
        if (el.open) el.close();
        const scrollY = document.body.style.top;
        document.body.style.position = "";
        document.body.style.top = "";
        window.scrollTo(0, parseInt(scrollY || "0") * -1);
      }
    });

    return () => {
      if (el.open) el.close();
    };
  }

  function handleDialogToggle(e: ToggleEvent) {
    if (e.newState === "open") {
      service.moveModal.loadCourses();
    }
    const select = document.getElementById("move-lesson") as HTMLSelectElement;
    selectedTopic =
      select?.selectedOptions[0]?.closest("optgroup")?.dataset.key ?? null;
  }

  function handleLessonChange(e: Event) {
    const target = e.target as HTMLSelectElement;
    selectedLesson = parseInt(target.value ?? "0");
    selectedTopic =
      target.selectedOptions[0]?.closest("optgroup")?.dataset.key ?? null;
  }

  function handleMove() {
    const fromIdx = service.moveModal.openedLesson.sortOrder / 10 - 1;
    service.moveLesson(
      fromIdx,
      selectedLesson,
      service.moveModal.openedTopic,
      selectedTopic,
    );
  }
</script>

<dialog
  class="lighter-modal modal-move"
  {@attach dialogControl}
  oncancel={close}
  onclick={(e) => {
    if (e.target === e.currentTarget) close();
  }}
  ontoggle={handleDialogToggle}
>
  <header>
    <div class="lighter-dia-info">
      <h3>Move lesson</h3>
    </div>
    <div class="lighter-dia-actions">
      <button type="button" class="dia-close" onclick={close}>
        <Icon name="plus" />
      </button>
    </div>
  </header>
  <section>
    <div>
      <p>
        <label>
          Moving within course: <select
            bind:value={service.moveModal.chosenCourse}
          >
            {#each service.moveModal.courses as course (course.id)}
              <option value={course.id}
                >{course.title?.rendered ?? "Untitled"}</option
              >
            {/each}
          </select>
        </label>
      </p>
    </div>
    <p>
      <label for="move-lesson"> Move lessons after </label>
      <select id="move-lesson" onchange={handleLessonChange}>
        <button>
          <selectedcontent></selectedcontent>
        </button>
        {#each service.moveModal.filteredTopics as topic (topic.key)}
          <optgroup label={`${topic.title} →`} data-key={topic.key}>
            <legend>{topic.title}</legend>
            {#if topic.showAfterTopic}
              <option value={0}>
                <span class="label-selected">{topic.title}</span>
                <span class="label-picker">After topic</span>
              </option>
            {/if}
            {#each topic.lessons as lesson, idx (lesson.id)}
              <option value={idx + 1}>
                <span class="label-selected">
                  {topic.title} → {lesson.title?.rendered ||
                    lesson.title ||
                    "Untitled"}
                </span>
                <span class="label-picker">
                  {lesson.title?.rendered || lesson.title || "Untitled"}
                </span>
              </option>
            {:else}
              <span class="no-lessons-found">This topic has no lessons</span>
            {/each}
          </optgroup>
        {/each}
      </select>
    </p>

    <p>
      <button type="button" class="lighter-btn" onclick={handleMove}
        >Move lesson</button
      >
    </p>
  </section>
</dialog>

<style>
  #move-lesson,
  #move-lesson::picker(select) {
    appearance: base-select;
  }

  #move-lesson {
    &::picker-icon {
      display: none;
    }

    optgroup:not(:last-child) {
      border-bottom: 1px solid #a7a7a74a;
    }

    legend {
      padding: 0.75rem;
      padding-block-end: 0.25rem;

      &:hover {
        cursor: auto;
      }
    }

    option {
      padding: 0.25rem 0.5rem;

      &::checkmark {
        float: right;
      }

      &:hover {
        background-color: oklch(0.85 0.04 303.81 / 0.56);
      }
    }

    .no-lessons-found {
      padding: 0.25rem 0.5rem;
      padding-inline-start: 1.6rem;
      font-weight: 400;
      line-height: 1.6;
      cursor: auto;
    }

    option .label-selected {
      display: none;
    }

    selectedcontent {
      display: block;
      line-height: 2.25;

      .label-picker {
        display: none;
      }
    }

    &::picker(select) {
      border: 1px solid #a7a7a74a;
      border-radius: 0.5rem;
      box-shadow: 0 3px 6px -3px oklch(0 0 0 / 0.35);
    }
  }
</style>
