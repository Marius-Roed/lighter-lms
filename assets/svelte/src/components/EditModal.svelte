<script>
    import {
        editModal,
        editNextLesson,
        editPrevLesson,
        getLesson,
        lessons,
        syncLesson,
    } from "$lib/state.svelte.js";
    import Icon from "./Icon.svelte";
    import lighterFetch from "$lib/lighterFetch";

    let iframeEl = $state();
    let iframeReady = $state(false);

    async function getPermalink() {
        if (editModal.lesson.id) {
            return editModal.lesson.permalink;
        }

        const res = await lighterFetch({
            path: "lesson",
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title: editModal.lesson.title,
                parent_topic: editModal.lesson.parentTopicKey,
                meta: {
                    _lighter_lesson_key: editModal.lesson.key,
                },
            }),
        });

        const idx = getLesson(editModal.lesson.key);

        lessons[idx].permalink = res.permalink;
        lessons[idx].id = res.ID;
        return lessons[idx].permalink;
    }

    let src = $derived.by(async () => {
        const editor =
            LighterLMS.course.settings.editor !== "classic-editor"
                ? (LighterLMS?.course?.settings?.editor ?? false)
                : false;

        if (!editModal.lesson) return "";

        if (!editModal.lesson.id) {
            await getPermalink();
        }

        const url = new URL(editModal.lesson.permalink);
        const params = new URLSearchParams(url.search);

        if (editor) {
            params.set("action", editor.toLowerCase());
        }

        const newUrl = new URL(
            `${url.origin}${url.pathname}?${params.toString()}`,
        );
        return newUrl.href;
    });

    function close() {
        editModal.open = false;
        iframeReady = false;

        syncLesson(editModal.lesson.key);
    }

    /**
     * Attach dialog to state
     *
     * @param {HTMLDialogElement} el
     */
    function dialogControl(el) {
        $effect(() => {
            if (editModal.open) {
                if (!el.open) el.showModal();
            } else {
                if (el.open) el.close();
            }
        });

        return () => {
            if (el.open) el.close();
        };
    }

    function handleIframeLoad() {
        if (!iframeEl.contentWindow || !editModal.open) return;

        try {
            const doc = iframeEl.contentDocument;
            if (doc) {
                if (doc.body) {
                    iframeReady = true;
                } else {
                    iframeEl.contentWindow.addEventListener(
                        "DOMContentLoaded",
                        () => {
                            iframeReady = true;
                        },
                    );
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
        } catch (e) {
            console.warn("Iframe naviagtion check failed:", e);
        }
    }
</script>

<dialog
    class="lighter-modal-edit"
    {@attach dialogControl}
    oncancel={close}
    onclick={(e) => {
        if (e.target === e.currentTarget) close();
    }}
>
    <header>
        <div class="lighter-dia-info">
            <h3>Edit {editModal.lesson?.title ?? ""}</h3>
        </div>
        <div class="lighter-dia-actions">
            <button
                type="button"
                class="change-lesson"
                title="Previous lesson"
                style="rotate:90deg;"
                onclick={editPrevLesson}
            >
                <Icon name="chevron" />
            </button>
            <button
                type="button"
                class="change-lesson"
                title="Next lesson"
                style="rotate:-90deg"
                onclick={editNextLesson}
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
        {#if editModal.open}
            {#await src then src}
                <iframe
                    title={`Edit ${editModal.lesson?.title ?? "undefined"}`}
                    id={"edit-" + editModal.key}
                    {src}
                    bind:this={iframeEl}
                    onload={handleIframeLoad}
                    style="width:100%;height:100%;border:1px solid #D2C8E1;border-bottom-right-radius:.5em;border-bottom-left-radius:.5em;"
                ></iframe>
            {/await}
        {/if}
    </section>
</dialog>
