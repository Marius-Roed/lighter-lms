<script>
    import DatePicker from "$components/DatePicker.svelte";
    import FilterSearch from "$components/FilterSearch.svelte";
    import settings, { displayDate, isEmpty } from "$lib/settings.svelte";
    import { course, postStatus } from "$lib/state.svelte";

    let src = $derived(
        (settings.sync_prod_img && !isEmpty(settings.product)
            ? settings.product.images?.[0]?.src
            : settings.thumbnail?.src) ??
            "https://placehold.co/350/D2C8E1/663399?text=%3F",
    );
    let alt = $derived(
        (settings.sync_prod_img
            ? settings.product.images?.[0]?.alt
            : settings.thumbnail?.alt) ?? "placeholder",
    );
    let frame;

    function openImageModal() {
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
            library: { type: "image" },
        });

        frame.on("select", () => {
            const attachment = frame.state().get("selection").first().toJSON();

            if (settings.sync_prod_img && !isEmpty(settings.product)) {
                settings.product.images[0] = {
                    id: attachment.id,
                    src: attachment.url,
                    alt: attachment.alt,
                };
            }
            settings.thumbnail = {
                id: attachment.id,
                src: attachment.url,
                alt: attachment.alt,
            };
        });

        frame.open();
    }
</script>

<div class="grid">
    <div class="course-vis">
        <h3>Course visibilty</h3>
        <div class="content row">
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
            <button
                type="button"
                popovertarget="datetime"
                style="anchor-name:--publish-date;"
                ><b>Published on:</b>
                {displayDate(settings.publishedOn)}</button
            >
            <div id="datetime" popover>
                <DatePicker bind:value={settings.publishedOn} />
            </div>
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
    <div class="course-desc">
        <h3>Description</h3>
        <textarea
            id="course-description"
            name="course_description"
            cols="35"
            rows="12"
            placeholder="Enter an eye catching description..."
            bind:value={settings.description}
        ></textarea>
    </div>
    <div class="course-img">
        <h3>Feature image</h3>
        <div class="col center">
            <div class="course-img-wrap">
                <img {src} {alt} />
                <button
                    type="button"
                    class="change-img"
                    onclick={openImageModal}>Change image</button
                >
            </div>
        </div>
    </div>
</div>
