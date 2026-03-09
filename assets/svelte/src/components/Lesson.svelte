<script lang="ts">
    import type { Lesson } from "$lib/models/state/course-lesson.svelte.ts";
    import settings from "$lib/settings.svelte";
    import { hiddenInput } from "$lib/snippets.svelte";
    import { getCourseService, POSTSTATUS } from "$lib/utils/index.ts";
    import type { SvelteComponent } from "svelte";
    import ActionMenu from "./action-menu/ActionMenu.svelte";
    import Editable from "./Editable.svelte";
    import EditLesson from "./EditLesson.svelte";
    import Icon from "./Icon.svelte";
    import Submenu from "./action-menu/Submenu.svelte";

    interface Props {
        lesson: Lesson;
    }

    let { lesson }: Props = $props();

    const service = getCourseService();

    const warn = $derived(lesson.status == "publish" ? "" : "warn");

    let actionMenu: SvelteComponent;

    function editLesson() {
        const editContainer = this.closest(".actions");
        editContainer.querySelector(".edit-container button").click();
    }
</script>

<div class="lighter-lesson">
    <div class="lesson-data hidden">
        {@render hiddenInput(
            `topics[${lesson.parentKey}][lessons][${lesson.sortOrder}]`,
            lesson.getHiddenData(),
        )}
    </div>
    <div class="lesson-title">
        {#if settings.showIcons}
            <Icon name={lesson.lessonType} className="lesson-icon" />
        {/if}
        <Editable bind:value={lesson.title} tag="h4" />
    </div>
    <div class="actions">
        <EditLesson text="Edit lesson" {lesson} />
        <div class="lesson-menu">
            <ActionMenu bind:this={actionMenu}>
                {#snippet trigger()}
                    <Icon name="threeDots" className={warn} />
                {/snippet}
                <button type="button" onclick={editLesson}>Edit</button>
                <button
                    type="button"
                    onclick={() => service.moveLessonDirection(lesson, "up")}
                    >Move up</button
                >
                <button
                    type="button"
                    onclick={() => service.moveLessonDirection(lesson, "down")}
                    >Move down</button
                >
                <button
                    type="button"
                    onclick={() => confirmDeleteLesson(lesson)}>Delete</button
                >
                <hr />
                <Submenu anchor={lesson.title}>
                    {#snippet trigger()}
                        <button type="button" class="submenu-trig"
                            >Status: <span
                                class={[
                                    lesson.status != "publish" && "text-warn",
                                ]}>{lesson.status}</span
                            ></button
                        >
                    {/snippet}
                    {#each POSTSTATUS as key}
                        <button
                            type="button"
                            class={{
                                active: lesson.status === key,
                                hidden: key === "auto-draft",
                            }}
                            onclick={() =>
                                service.setLessonStatus(lesson.key, key)}
                            >{key}</button
                        >
                    {/each}
                </Submenu>
            </ActionMenu>
        </div>
    </div>
</div>

<style>
    .lighter-lesson {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .actions {
        display: flex;
        gap: 0.875em;
        align-items: center;
    }

    hr {
        margin: 0;
        height: 1px;
        width: 100%;
        background-color: #d2c8e1;
        border: 0;
    }

    button {
        width: 100%;
    }

    .submenu-trig {
        position: relative;
        padding-inline-end: 1.25em;

        &::after {
            content: "";
            position: absolute;
            top: 0.875lh;
            right: 0.5em;
            display: inline-flex;
            width: 0.625lh;
            height: 0.625lh;
            background-color: #666;
            border: none;
            border-radius: 0;
            mask-size: contain;
            mask-repeat: no-repeat;
            mask-position: center;
            mask-image: url("data:image/svg+xml,%3Csvg width='14' height='9' viewBox='0 0 14 9' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M13 7.05524L6.98258 1.05524L1 7.05524' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E%0A");
            rotate: 90deg;
        }
    }
</style>
