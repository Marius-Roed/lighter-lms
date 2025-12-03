<script>
    import Editable from "$components/Editable.svelte";
    import Switch from "$components/Switch.svelte";
    import settings from "$lib/settings.svelte";
</script>

<div class="course-slug">
    <h3>Lesson url</h3>
    {window.location.origin}/{settings.baseUrl}/<Editable
        bind:value={settings.slug}
        placeholder="new-course"
        tag="b"
        sanitize={(v) => v.replaceAll(" ", "-").toLowerCase()}
    />
</div>

<div>
    <h3>Lesson settings</h3>

    <Switch
        bind:checked={settings.showIcons}
        name="lesson-icons"
        onLabel="Show lesson icons"
    />

    <Switch
        bind:checked={settings.showProgress}
        name="lesson-progress"
        onLabel="Hide lesson progress"
        offLabel="Show lesson progress"
    />
</div>

<div class="course-template-set">
    <h3>Template</h3>
    <Switch
        bind:checked={settings.displayHeader}
        name="displayHeader"
        onLabel="Disable theme header"
        offLabel="Display theme header"
    />
    <Switch
        bind:checked={settings.displaySidebar}
        name="displaySidebar"
        onLabel="Disable theme sidebar"
        offLabel="Display theme sidebar"
    />
    <Switch
        bind:checked={settings.displayFooter}
        name="displayFooter"
        onLabel="Disable theme footer"
        offLabel="Display theme footer"
    />
</div>
