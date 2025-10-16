<script>
    import DatePicker from "$components/DatePicker.svelte";
    import FilterSearch from "$components/FilterSearch.svelte";
    import { displayDate, settings } from "$lib/settings.svelte";
    import { course, postStatus } from "$lib/state.svelte";

    /** @var {HTMLButtonElement} */
    const saveBtn = document.getElementById("save-post");

    let btnText = $derived.by(() => {
        const status = settings?.status ?? "default";
        switch (status) {
            case "draft":
                return "Save draft";
            case "auto-draft":
                return "Publish";
            case "pending":
                return "For review";
            case "future":
                return "Schedule";
            default:
                return "Save";
        }
    });

    const openCalendar = () => {
        console.log("Hello world");
    };

    let currentListener = null;

    $effect(() => {
        const isSchedule = "schedule" === btnText.toLowerCase();
        if (isSchedule) {
            saveBtn.removeAttribute("form");
            saveBtn.setAttribute("type", "button");

            if (currentListener) {
                saveBtn.addEventListener("click", currentListener);
                currentListener = null;
            }

            currentListener = openCalendar;
            saveBtn.addEventListener("click", currentListener);
        } else {
            saveBtn.removeAttribute("type");
            saveBtn.setAttribute("form", "post");

            if (currentListener) {
                saveBtn.removeEventListener("click", currentListener);
                currentListener = null;
            }
        }

        const lastChild = saveBtn.lastChild;
        if (lastChild && lastChild.textContent !== undefined) {
            lastChild.textContent = btnText.toUpperCase();
        }
    });
</script>

<div class="col">
    <div class="row gen-top">
        <div class="course-vis">
            <h3>Course visibilty</h3>
            <span>
                <b>Status:</b>
                <select name="post_status" bind:value={settings.status}>
                    {#each Object.entries(postStatus) as [value, text]}
                        <option {value}>{text}</option>
                    {/each}
                    {#if settings.status === "auto-draft"}
                        <option value="auto-draft" disabled>Draft (Auto)</option
                        >
                    {/if}
                </select>
            </span>
            <button type="button" class="open-date" popovertarget="datetime"
                ><b>Published on:</b>
                {displayDate(settings.publishedOn)}</button
            >
            <div id="datetime" popover>
                <DatePicker bind:value={settings.publishedOn} />
            </div>
        </div>
        <div class="course-tags">
            <h3>Tags</h3>
            <FilterSearch
                name="course-tags"
                width="100%"
                tags={course.tags.all}
                bind:selected={settings.tags}
                createNew
            />
        </div>
    </div>
    <div class="course-desc">
        <h3>Description</h3>
        <textarea
            id="course-description"
            name="course_description"
            cols="35"
            rows="8"
            placeholder="Enter an eye catching description..."
            bind:value={settings.description}
        ></textarea>
    </div>
</div>
