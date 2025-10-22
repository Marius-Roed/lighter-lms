<script>
    /**
     * @typedef {object} EditLessonProps
     * @property {string} text
     * @property {import('$lib/state.svelte.js').Lesson} lesson
     */

    import Icon from "./Icon.svelte";
    import lighterFetch from "$lib/lighterFetch";
    import { editLesson, updateLesson } from "$lib/state.svelte.js";

    /** @type {EditLessonProps} */
    let { text, lesson, ...props } = $props();

    let isEditing = $state(false);

    /** @type {HTMLDialogElement} */
    let dialogEl = $state();
    /** @type {HTMLIFrameElement} */
    let iframeEl = $state();

    let lessonPromise = $state();
    let iframeSrc = $state(null);
    let iframeReady = $state(false);

    async function refreshLesson() {
        if (!lesson.id) return;

        try {
            const post = await lighterFetch({
                path: "lesson/" + lesson.id,
                method: "GET",
            });

            if (lesson.id !== post.lesson.ID) {
                throw new Error(
                    `Lesson fetched ID (${post.lesson.ID}) and the current lesson ID (${lesson.id}) did not match`,
                );
            }

            const data = {
                title: post.lesson.post_title,
                status: post.lesson.post_status,
            };

            updateLesson(lesson.key, data);
            console.log("Updated lesson", lesson.key);
        } catch (err) {
            console.warn("Failed to update lesson:", err);
        }
    }

    function closeDialog() {
        isEditing = false;
        dialogEl?.close?.();

        setTimeout(refreshLesson, 500);
    }

    function openDialog() {
        isEditing = true;
        iframeSrc = null;
        iframeReady = false;

        if (lesson.permalink) {
            lessonPromise = new Promise((resolve) => {
                setTimeout(() => resolve(lesson.permalink), 0);
            });
        } else {
            lessonPromise = getPermalink(lesson);
        }
    }

    /**
     * @param {HTMLDialogElement} el
     */
    function dialogControl(el) {
        $effect(() => {
            if (isEditing) {
                if (!el.open) el.showModal();
            } else {
                if (el.open) el.close();
            }
        });
    }

    /**
     * @param {Event} e
     */
    function handleCancel(e) {
        e.preventDefault();
        closeDialog();
    }

    /**
     * @param {import('$lib/state.svelte').Lesson} lesson
     */
    async function getPermalink(lesson) {
        if (lesson.id) {
            return lesson.permalink;
        }

        const res = await lighterFetch({
            path: "lesson",
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title: lesson.title,
                parent_topic: lesson.parentTopicKey,
                meta: {
                    _lighter_lesson_key: lesson.key,
                },
            }),
        });

        lesson.permalink = res.permalink;
        lesson.id = res.ID;
        return lesson.permalink;
    }

    function handleIframeLoad() {
        if (!iframeEl.contentWindow) return;

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
                closeDialog();
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

<div class="edit-container">
    <button
        type="button"
        class="lighter-btn transparent"
        onclick={() => editLesson(lesson.key)}
        {...props}>{text}</button
    >
    {#if isEditing}
        <dialog
            class="edit-lesson-dia"
            {@attach dialogControl}
            bind:this={dialogEl}
            oncancel={handleCancel}
            onclick={(e) => {
                if (e.target === dialogEl) closeDialog();
            }}
        >
            <header>
                <div class="lighter-dia-info">
                    <h3>Edit {lesson.title}</h3>
                </div>
                <div class="lighter-dia-actions">
                    <button
                        type="button"
                        class="change-lesson"
                        title="Previous lesson"
                        style="rotate:90deg;"
                    >
                        <Icon name="chevron" />
                    </button>
                    <button
                        type="button"
                        class="change-lesson"
                        title="Next lesson"
                        style="rotate:-90deg"
                    >
                        <Icon name="chevron" />
                    </button>
                    <button
                        type="button"
                        class="dia-close"
                        onclick={closeDialog}><Icon name="plus" /></button
                    >
                </div>
            </header>
            <section>
                {#await lessonPromise}
                    <div class="lighter-editor-skeleton"></div>
                {:then src}
                    {#if !iframeSrc}
                        {@html (() => {
                            iframeSrc = src;
                            return "";
                        })()}
                    {/if}
                    {#if !iframeReady}
                        <div class="lighter-editor-skeleton"></div>
                    {/if}
                    <iframe
                        title={`Edit ${lesson.title}`}
                        id={"edit-" + lesson.id}
                        {src}
                        bind:this={iframeEl}
                        onload={handleIframeLoad}
                        style="width:100%; height:100%; border:1px solid #D2C8E1; border-bottom-right-radius: .5em;border-bottom-left-radius: .5em;"
                    ></iframe>
                {:catch error}
                    <div class="err">
                        <p>Failed to load lesson: {error.message}</p>
                    </div>
                {/await}
            </section>
        </dialog>
    {/if}
</div>
