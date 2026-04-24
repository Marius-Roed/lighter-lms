<script lang="ts">
  import Icon from "./Icon.svelte";
  import { getCourseService } from "$lib/utils/index.ts";

  let service = getCourseService();

  let iframeEl: HTMLIFrameElement = $state(),
    iframeReady = $state(false);

  let src = $derived(service.editModal.currentLesson?.editLink ?? "");

  function close() {
    service.editModal.close();
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
      // TODO: service.editModal.currentLesson?.update(lesson);
    }
  }

  function handleIframeLoad() {
    if (!iframeEl.contentWindow || !service.editModal.currentLesson) return;

    const doc = iframeEl.contentDocument;
    if (doc?.body) {
      iframeReady = true;
    } else if (doc) {
      iframeEl.contentWindow.addEventListener("DOMContentLoaded", () => {
        iframeReady = true;
      });
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
      <iframe
        title={`Edit ${service.editModal.currentLesson?.title ?? "undefined"}`}
        id={"edit-" + service.editModal.currentLesson.key}
        {src}
        bind:this={iframeEl}
        onload={handleIframeLoad}
        style="width:100%;height:100%;border:1px solid #D2C8E1;border-bottom-right-radius:.5em;border-bottom-left-radius:.5em;"
      ></iframe>
    {/if}
  </section>
</dialog>
