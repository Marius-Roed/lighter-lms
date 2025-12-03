<script>
    import Icon from "$components/Icon.svelte";

    const ICON_COLOR = {
        blocks: "#21759B",
        bricks: "#212121",
        breakdance: "black",
        elementor: "#F0F0F1",
        "classic-editor": "#21759B",
    };

    let LighterLMS = window.LighterLMS;

    const attrify = (/** @type {string} */ s) => {
        return s.replaceAll(/\s|_/gi, "-").toLowerCase();
    };
</script>

<h2>Default editor</h2>
<div class="editors">
    {#each LighterLMS.settings.builders.plugins as plugin}
        {@const editor = attrify(plugin)}
        {@const color = ICON_COLOR[editor]}
        <label for={editor}>
            <input
                type="radio"
                id={editor}
                name="default-editor"
                value={editor}
                checked={editor == LighterLMS.settings.builders.active}
            />
            <div class={[editor, "editor-card"]}>
                <Icon name={editor} size="222" {color} />
                <span>{plugin}</span>
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
