<script>
    import { getContext } from "svelte";
    import FilterSearch from "./FilterSearch.svelte";
    import Icon from "./Icon.svelte";
    import Calendar from "./Calendar.svelte";

    let filterDate = $state(null),
        filterEndDate = $state(null);
    let showCalendar = $state(false);

    let popover;
    let courseStore = getContext("course-list");

    let currentYear = $derived(filterDate.getFullYear());
    let currentMonth = $derived(filterDate.getMonth());
    let selectedDay = $derived(filterDate.getDate());

    const years = Array.from({ length: 55 }, (_, i) => i + 1985);
    const months = Array.from({ length: 12 }, (_, i) => {
        let d = new Date(1970, i, 1);
        return d.toLocaleDateString(undefined, { month: "short" });
    });

    const formatter = new Intl.DateTimeFormat(undefined, {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });

    const postStatus = {
        publish: "Published",
        pending: "Pending review",
        future: "Scheduled",
        private: "Private",
        draft: "Draft",
        trash: "Trashed",
    };

    /** @param {MouseEvent} e */
    function handleShiftClick(e) {
        if (!e.shiftKey) return;
        courseStore.filterStatus = [e.target.value];
    }

    /**
     * Changes the current month
     *
     * @param {number} month
     */
    function changeMonth(month) {
        filterDate = new Date(currentYear, month, selectedDay);
    }

    /**
     * Changes the current year
     *
     * @param {number} year
     */
    function changeYear(year) {
        filterDate = new Date(year, currentMonth, selectedDay);
    }
</script>

<div class="filter-posts">
    <button
        type="button"
        class="lighter-btn transparent wp-background"
        popovertarget="post-filters"
        popovertargetaction="show"
    >
        <Icon name="sliders" />
        Filter</button
    >

    <div bind:this={popover} id="post-filters" popover>
        <div class="status">
            <p><b>Status</b></p>
            <div class="status cbs">
                {#each Object.entries(postStatus) as [key, val]}
                    <div class="filter-cb">
                        <input
                            type="checkbox"
                            id={"cb-" + key}
                            name={key}
                            value={key}
                            bind:group={courseStore.filterStatus}
                            onclick={handleShiftClick}
                        />
                        <label for={"cb-" + key} onmousedown={handleShiftClick}>
                            {val}
                        </label>
                    </div>
                {/each}
            </div>
        </div>
        <div class="tags">
            <p>
                <b>Tags</b>
            </p>
            <FilterSearch
                name="filter-tags"
                tags={lighterCourses.tags.all}
                bind:selected={courseStore.filterTags}
            />
        </div>
        <div class="publish-date">
            <p class="publish-date">
                <b>Publish date</b>
            </p>
            <button
                type="button"
                class="text"
                onclick={() => (showCalendar = !showCalendar)}
            >
                {filterDate ? formatter.format(filterDate) : "dd-mm-yyyy"}
                {#if filterEndDate && formatter.format(filterEndDate) !== formatter.format(filterDate)}
                    &mdash;
                    {formatter.format(filterEndDate)}
                {/if}
            </button>
            {#if showCalendar}
                <div class="header">
                    <button
                        type="button"
                        onclick={(e) => {
                            if (e.shiftKey) {
                                changeYear(currentYear - 1);
                            } else {
                                changeMonth((currentMonth + 11) % 12);
                            }
                        }}
                    >
                        <Icon name="chevron" size="0.75em" />
                    </button>
                    <select
                        id="date-picker-year"
                        bind:value={currentYear}
                        onchange={(e) => changeYear(+e.target.value)}
                    >
                        {#each years as year}
                            <option value={year}>{year}</option>
                        {/each}
                    </select>
                    &ndash;
                    <select
                        name="month"
                        id="date-picker-month"
                        bind:value={currentMonth}
                        onchange={(e) => changeMonth(+e.target.value)}
                    >
                        {#each months as month, i}
                            <option value={i}>{month}</option>
                        {/each}
                    </select>
                    <button
                        type="button"
                        onclick={(e) => {
                            if (e.shiftKey) {
                                changeYear(currentYear + 1);
                            } else {
                                changeMonth((currentMonth + 1) % 12);
                            }
                        }}
                    >
                        <Icon name="chevron" size="0.75em" />
                    </button>
                </div>
                <Calendar
                    bind:date={filterDate}
                    bind:endDate={filterEndDate}
                    range
                />
            {/if}
        </div>
        <button
            type="button"
            class="lighter-btn"
            onclick={() => courseStore.loadPosts(1)}
            popovertarget="post-filters"
            popovertargetaction="hide">Apply</button
        >
    </div>
</div>
