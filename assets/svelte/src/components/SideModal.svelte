<script>
    import Icon from "./Icon.svelte";

    let {
        trigger,
        class: classNames = "",
        position = "right",
        close = "Done",
        children,
        ...props
    } = $props();
    let id = $props.id();
    /** @type HTMLDialogElement */
    let el;
    let isOpen = $state(false);
    let scrollY = $state(0);

    let openDialog = () => {
        scrollY = window.scrollY;
        isOpen = true;
        el.showModal();
        window.scrollTo(0, scrollY);
    };

    let closeDialog = () => {
        isOpen = false;
        el.close();
    };

    /** @param {MouseEvent} e */
    let handleClose = (e) => {
        if (e.target === el) {
            closeDialog();
        }
    };

    /** @param {KeyboardEvent} e */
    let handleEscape = (e) => {
        if (e.key === "Escape") {
            closeDialog();
        }
    };

    $effect(() => {
        if (isOpen) {
            document.body.dataset.scrollLock = 1;
        } else {
            document.body.removeAttribute("data-scroll-lock");
        }
    });

    $effect(() => {
        el?.addEventListener("click", handleClose);
        document.addEventListener("keydown", handleEscape);

        return () => {
            el?.removeEventListener("click", handleClose);
            document.addEventListener("keydown", handleEscape);
        };
    });
</script>

<div class={["modal-wrap", id]}>
    <button
        type="button"
        class={["lighter-btn", classNames]}
        onclick={openDialog}>{trigger}</button
    >
    <dialog class={["sidebar-modal", position]} bind:this={el}>
        <button type="button" class="close-modal" onclick={closeDialog}
            ><Icon name="plus" /></button
        >
        <div class="content-wrap">
            {@render children?.()}
        </div>
        <div class="modal-footer">
            <button
                type="button"
                class="lighter-btn height-content"
                onclick={closeDialog}>{close}</button
            >
        </div>
    </dialog>
</div>
