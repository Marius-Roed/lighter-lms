<script lang="ts">
  import Icon from "./Icon.svelte";
  import { lighterFetch } from "$lib/api/lighter-fetch.ts";
  import { getCourseService } from "$lib/utils/index.ts";
  import type { LessonData } from "$types/course.js";

  let service = getCourseService();

  let iframeEl: HTMLIFrameElement = $state(),
    iframeReady = $state(false);

  async function getPermalink() {
    if (service.editModal.currentLesson.id) {
      return service.editModal.currentLesson.editLink;
    }

    const res = await lighterFetch<LessonData>({
      path: "lesson",
      method: "POST",
      data: {
        title: service.editModal.currentLesson.title,
        meta: {
          _lighter_lesson_key: service.editModal.currentLesson.key,
        },
      },
    });

    return "";
  }

  let src = $derived.by(async () => {
    const editor =
      LighterLMS.globals.editor !== "classic-editor"
        ? (LighterLMS.globals.editor ?? "edit")
        : "edit";

    if (!service.editModal.currentLesson) return "";

    if (!service.editModal.currentLesson?.id) {
      await getPermalink();
    }

    const url = new URL(service.editModal.currentLesson.editLink);
    const params = new URLSearchParams(url.search);

    if (editor) {
      params.set("action", editor.toLowerCase());
    }

    const newUrl = new URL(`${url.origin}${url.pathname}?${params.toString()}`);
    return newUrl.href;
  });

  function close() {
    service.editModal.close();

    // syncLesson(editModal.lesson.key);
  }

  function dialogControl(el: HTMLDialogElement) {
    $effect(() => {
      if (service.editModal.currentLesson) {
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

  function handleMessage(e: MessageEvent) {
    if (e.origin !== window.location.origin) return;

    if (e.data?.type === "LIGHTER_LESSON_UPDATE") {
      const lesson = e.data.payload;
      console.log(lesson);
    }
  }

  function handleIframeLoad() {
    if (!iframeEl.contentWindow || !service.editModal.currentLesson) return;

    try {
      const doc = iframeEl.contentDocument;
      if (doc) {
        if (doc.body) {
          iframeReady = true;
        } else {
          iframeEl.contentWindow.addEventListener("DOMContentLoaded", () => {
            iframeReady = true;
          });
        }
      }

      const url = new URL(iframeEl.contentWindow.location.href);
      if (!url.pathname.includes("post.php")) {
        console.warn("Lighter Error: URL not allowed.", url.href);
        close();
        return;
      }

      const body = iframeEl.contentDocument?.body;
      if (body && !body.classList.contains("dia-editor")) {
        body.classList.add("dia-editor");
      }

      iframeEl.contentWindow.postMessage(
        {
          type: "LIGHTER_DIALOG_INIT",
          payload: service.editModal.currentLesson.serialize(),
        },
        window.location.origin,
      );
    } catch (e) {
      console.warn("Iframe naviagtion check failed:", e);
    }
  }

  $effect(() => {
    service.editModal.currentLessonId;
    iframeReady = false;
  });
</script>

<svelte:window onmessage={handleMessage} />

<dialog
  class="lighter-modal modal-edit"
  {@attach dialogControl}
  oncancel={close}
  onclick={(e) => {
    if (e.target === e.currentTarget) close();
  }}
>
  <header>
    <div class="lighter-dia-info">
      <h3>Edit "{service.editModal.currentLesson?.title ?? ""}"</h3>
    </div>
    <div class="lighter-dia-actions">
      <button
        type="button"
        class="change-lesson"
        title="Previous lesson"
        style="rotate:90deg;"
        onclick={() => service.editModal.goPrev()}
        disabled={!service.editModal.previousLesson}
      >
        <Icon name="chevron" />
      </button>
      <button
        type="button"
        class="change-lesson"
        title="Next lesson"
        style="rotate:-90deg"
        onclick={() => service.editModal.goNext()}
        disabled={!service.editModal.nextLesson}
      >
        <Icon name="chevron" />
      </button>
      <button type="button" class="dia-close" onclick={close}>
        <Icon name="plus" />
      </button>
    </div>
  </header>
  <section>
    {#if !iframeReady}
      <div class="lighter-editor-skeleton"></div>
    {/if}
    {#if service.editModal.currentLesson}
      {#await src then src}
        <iframe
          title={`Edit ${service.editModal.currentLesson?.title ?? "undefined"}`}
          id={"edit-" + service.editModal.currentLesson.key}
          {src}
          bind:this={iframeEl}
          onload={handleIframeLoad}
          style="width:100%;height:100%;border:1px solid #D2C8E1;border-bottom-right-radius:.5em;border-bottom-left-radius:.5em;"
        ></iframe>
      {/await}
    {/if}
  </section>
</dialog>
