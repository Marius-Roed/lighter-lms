<script>
    import Icon from "$components/Icon.svelte";

    const ICON_COLOR = {
        "beaver-builder": {
            foreground: "transparent",
            background: "#FEAF52",
        },
        breakdance: {
            foreground: "black",
            background: "#FFC514",
        },
        bricks: {
            foreground: "#212121",
            background: "#FFD54C",
        },
        brizy: {
            foreground: "transparent",
            background: "#0E0736",
        },
        divi: {
            foreground: "white",
            background: "transparent",
        },
        elementor: {
            foreground: "#F0F0F1",
            background: "#92003B",
        },
        gutenberg: {
            foreground: "#1e1e1e",
            background: "#F0F0F1",
        },
        "fusion-builder": {
            foreground: "white",
            background: "#50B3C4",
        },
        "live-composer": {
            foreground: "transparent",
            background: "#2EDCE7",
        },
        spectra: {
            foreground: "#F0F0F1",
            background: "#5733FF",
        },
        oxygen: {
            foreground: "white",
            background: "black",
        },
        "classic-editor": {
            foreground: "#21759B",
            background: "#F0F0F1",
        },
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
        {@const fg = ICON_COLOR[editor]?.["foreground"]}
        {@const bg = ICON_COLOR[editor]?.["background"]}
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
