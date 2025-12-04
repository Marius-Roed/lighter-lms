<script>
    import Icon from "$components/Icon.svelte";

    let LighterLMS = window.LighterLMS;

    const attrify = (/** @type {string} */ s) => {
        return s.replaceAll(/\s|_/gi, "-").toLowerCase();
    };
</script>

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
            <div class={[editor, "editor-card"]}>
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
<h2>Course Access</h2>
<p>Give users access to courses</p>
<div class="course-access">
    <div class="user-list"></div>
    →
    <div class="course-list"></div>
</div>
