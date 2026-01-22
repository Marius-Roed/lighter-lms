<script>
    import CoursePicker from "$components/CoursePicker.svelte";
    import Icon from "$components/Icon.svelte";
    import UserList from "$components/UserList.svelte";
    import settings from "$lib/settings.svelte";

    let LighterLMS = window.LighterLMS;
    let users = $state([]);
    let courses = $derived(settings.courses);
    let selectedCourses = $state({});

    const attrify = (/** @type {string} */ s) => {
        return s.replaceAll(/\s|_/gi, "-").toLowerCase();
    };
</script>

<h2>Course Access</h2>
<p>Give users access to courses</p>
<div>
    {#each users as user}
        <input type="hidden" name="users[]" value={user.id} />
    {/each}
    {#each Object.entries(selectedCourses) as [course, selected]}
        {#if selected.length}
            <input type="hidden" name={`courses[${course}]`} value={selected} />
        {/if}
    {/each}
</div>
<div class="course-access">
    <UserList bind:users />
    <span class="row center middle"
        ><Icon name="chevron" color="rebeccapurple" size="1.5rem" /></span
    >
    <CoursePicker {courses} bind:selectedCourses />
</div>
<h2>Default editor</h2>
<div class="editors">
    {#each LighterLMS.settings.builders.plugins as plugin}
        {@const editor = plugin["slug"]}
        {@const bg = plugin["background"]}
        {@const fg = plugin["foreground"]}
        <label for={editor} style={`--bg-color: ${bg}`}>
            <input
                type="radio"
                id={editor}
                name="default-editor"
                value={editor}
                checked={editor == LighterLMS.settings.builders.active}
            />
            <div class={[editor, "editor-card", "col"]}>
                <Icon name={editor} size="222" color={fg} />
                <span>{plugin["name"][0]}</span>
            </div>
        </label>
    {:else}
        <label for="classic-editor">
            <input
                type="radio"
                name="default-editor"
                id="classic-editor"
                checked
            />
            <div class="editor-card">
                <Icon name="classic-editor" size="222" />
                Classic editor
            </div>
        </label>
    {/each}
</div>
<h2>Connected store</h2>
<div class="stores">
    {#each LighterLMS.settings.stores.plugins as plugin}
        {@const editor = plugin["slug"]}
        {@const bg = plugin["background"]}
        {@const fg = plugin["foreground"]}
        <label for={editor} style={`--bg-color: ${bg}`}>
            <input
                type="radio"
                id={editor}
                name="default-store"
                value={editor}
                checked={editor == LighterLMS.settings.stores.active}
            />
            <div class={[editor, "store-card", "col"]}>
                <Icon name={editor} size="222" color={fg} />
                <span>{plugin["name"][0]}</span>
            </div>
        </label>
    {:else}
        <p>No stores found.</p>
    {/each}
</div>
