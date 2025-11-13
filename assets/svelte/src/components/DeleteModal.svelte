<script>
    import {
        deleteModal,
        deleteTopic,
        deleteLesson,
    } from "$lib/state.svelte.js";

    function close() {
        deleteModal.open = false;
    }

    async function confirm() {
        if (deleteModal.type === "topic") {
            await deleteTopic(deleteModal.key);
        } else {
            await deleteLesson(deleteModal.id, deleteModal.key);
        }

        close();
    }

    /**
     * Attach dialog to state
     *
     * @param {HTMLDialogElement} el
     */
    function dialogControl(el) {
        $effect(() => {
            if (deleteModal.open) {
                if (!el.open) el.showModal();
            } else {
                if (el.open) el.close();
            }
        });

        return () => {
            if (el.open) el.close();
        };
    }
</script>

<dialog
    class="lighter-modal-delete"
    {@attach dialogControl}
    oncancel={close}
    onclick={(e) => {
        if (e.target === e.currentTarget) close();
    }}
>
    <header>
        <h3>Delete {deleteModal.type}</h3>
    </header>
    <section>
        <p>
            Are you sure you want to delete <strong>{deleteModal.title}</strong
            >?
        </p>
        {#if deleteModal.type === "topic"}
            This will also delete {deleteModal.lessonCount}
            {deleteModal.lessonCount > 1 ? "lessons" : "lesson"}.
        {/if}

        <p>This action cannot be undone!</p>
    </section>
    <footer>
        <button type="button" class="lighter-btn transparent" onclick={close}>
            Cancel
        </button>
        <button type="button" class="lighter-btn danger" onclick={confirm}>
            Delete
        </button>
    </footer>
</dialog>
