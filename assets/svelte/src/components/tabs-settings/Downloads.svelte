<script>
    import Editable from "$components/Editable.svelte";
    import { settings } from "$lib/settings.svelte";

    let frame;

    const downloads = $derived(settings.product.downloads);

    /** @param {string} file */
    const getExtension = (file) => {
        if (!file) return "";
        const url = new URL(file);
        const pathname = url.pathname;

        const filename = pathname.split("/").pop();

        if (!filename) return "File type not found";

        const idx = filename.indexOf(".");
        if (idx === -1) return "No file type found";

        let ext = filename.slice(idx);
        ext = ext.split("?")[0].split("#")[0];

        return ext.replace(/^\.+/, "").toLowerCase();
    };

    /**
     * @param {Object} obj
     */
    function isEmpty(obj) {
        for (const prop in obj) {
            if (obj.hasOwnProperty(prop)) {
                return false;
            }
        }
        return true;
    }

    function openMediaModal(idx) {
        if (!wp || !wp.media) {
            console.error(
                "Could not open media modal. Did you forget to enqueue wp_media?",
            );
            return;
        }

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: "",
            button: {
                text: "Use this media",
            },
            multiple: false,
        });

        frame.on("select", () => {
            const attachment = frame.state().get("selection").first().toJSON();

            settings.product.downloads[idx] = {
                file: attachment.url,
                name: attachment.title,
            };
        });

        frame.open();
    }
</script>

{#if downloads}
    <table class="lighter-downloads">
        <thead>
            <tr>
                <td>File name</td>
                <td>File url</td>
                <td align="center">File type</td>
                <td>
                    <span class="screen-reader-text">Actions</span>
                </td>
            </tr>
        </thead>
        <tbody>
            {#each downloads as download, i}
                {@const fileType = getExtension(download.file)}
                <tr>
                    <td><Editable bind:value={download.name} /></td>
                    <td><input type="text" bind:value={download.file} /></td>
                    <td align="center">{fileType}</td>
                    <td>
                        <button
                            type="button"
                            class="lighter-btn transparent"
                            onclick={() => openMediaModal(i)}
                        >
                            Choose file
                        </button>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
    <button
        type="button"
        class="lighter-btn"
        onclick={() => downloads.push({ name: "", file: "" })}
        >Add new file</button
    >
{:else}
    <div class="no-downloads">
        <p>No downloads found for this course</p>
        <div>
            <button
                type="button"
                class="lighter-btn"
                onclick={() => {
                    if (downloads) {
                        downloads.push({ name: "", file: "" });
                    } else {
                        settings.product.downloads = [{ name: "", file: "" }];
                    }
                }}>Add first file</button
            >
            {#if isEmpty(settings.product)}
                or
                <button
                    type="button"
                    class="lighter-btn transparent"
                    onclick={() =>
                        document.getElementById("selling-tab").click()}
                    >link to a product</button
                >
            {/if}
        </div>
    </div>
{/if}
