<script>
    import Editable from "$components/Editable.svelte";
    import Switch from "$components/Switch.svelte";
    import settings from "$lib/settings.svelte";
</script>

<div class="grid">
    <div class="course-settings">
        <h3>Course settings</h3>
        <Switch
            bind:checked={settings.sync_prod_img}
            name="sync-prod-img"
            onLabel="Syncronise product image and course thumbnail"
        />
    </div>
    <div class="course-slug">
        <h3>Lesson url</h3>
        <div class="content grid">
            <span class="col-full">
                {window.location.origin}/{settings.baseUrl}/<Editable
                    bind:value={settings.slug}
                    placeholder="new-course"
                    tag="b"
                    sanitize={(v) => v.replaceAll(" ", "-").toLowerCase()}
                />
            </span>
        </div>
    </div>

    <div class="lessons">
        <h3>Lesson settings</h3>
        <div class="content grid">
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
    </div>

    <div class="course-template-set">
        <h3>Template</h3>
        <div class="content grid">
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
    </div>
</div>
