<script>
    import CourseAccess from "$components/CourseAccess.svelte";
    import ProductSearch from "$components/ProductSearch.svelte";
    import SideModal from "$components/SideModal.svelte";
    import Switch from "$components/Switch.svelte";
    import {
        settings,
        capitalize,
        setProduct,
        isEmpty,
    } from "$lib/settings.svelte";
    import { onMount } from "svelte";

    let src = $derived(
        settings.product?.images?.[0]?.src ??
            "https://placehold.co/350/D2C8E1/663399?text=%3F",
    );
    let alt = $derived(settings.product.images?.[0]?.alt ?? "Product image");

    let imgContainer = $state();

    let frame;

    /**
     * @param {string} id - ID of the HMTL element
     * @param {Function} cb - Callback to update svelte
     */
    function initTextEditor(id, cb) {
        let editorSettings = {
            tinymce: {
                wpautop: true,
                wp_keep_scroll_position: true,
                mediaButtons: true,
                quicktags: true,
                height: 300,
                plugins: [
                    "lists link image charmap",
                    "media",
                    "wordpress",
                    "wpview",
                    "wplink",
                    "wpdialogs",
                    "wpeditimage",
                    "wpemoji",
                    "wptextpattern",
                    "wpgallery",
                ].join(" "),
                toolbar1:
                    "formatselect | bold italic | bullist numlist | link unlink image | wp_adv",
            },
            quicktags: true,
        };

        if (typeof wp !== undefined && wp.editor) {
            wp.editor.initialize(id, editorSettings);

            const editor = tinymce.get(id);
            if (editor) {
                editor.on("change", function () {
                    const content = editor.getContent({ format: "html" });
                    if (cb) {
                        cb(content);
                    }
                });
            }
        }
    }

    onMount(() => {
        initEditors();

        return () => {
            wp.editor.remove("product_desc");
            wp.editor.remove("product_short_desc");
        };
    });

    function initEditors() {
        wp.editor.remove("product_desc");
        wp.editor.remove("product_short_desc");

        initTextEditor(
            "product_desc",
            /** @param {string} v */ (v) => {
                settings.product.description = v;
            },
        );
        initTextEditor(
            "product_short_desc",
            /** @param {string} v */ (v) => {
                settings.product.short_description = v;
            },
        );
    }

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

            settings.product.images[0] = {
                id: attachment.id,
                src: attachment.url,
                alt: attachment.alt,
            };
        });

        frame.open();
    }

    function initialiseProduct() {
        setProduct();
        wp.editor.remove("product_desc");
        wp.editor.remove("product_short_desc");
        queueMicrotask(initEditors);
    }

    // INFO: Track product, to update all values on change.
    $effect(() => {
        settings.product;
    });
</script>

<div class="product-select">
    <h3>
        Link to a product {#if settings.store}
            in {capitalize(settings.store)}
        {/if}
    </h3>
    <ProductSearch cb={() => queueMicrotask(initEditors)} />
    <button
        type="button"
        class="lighter-btn transparent"
        onclick={initialiseProduct}>Create product</button
    >
</div>

<div class="sell-container">
    {#if !isEmpty(settings.product)}
        <div class="top">
            <div class="general">
                <div>
                    <h4>General</h4>
                    <label>
                        Title:
                        <input
                            type="text"
                            name="product_title"
                            placeholder="Course: Learn to sell"
                            bind:value={settings.product.name}
                        />
                    </label>
                    <label>
                        Price ({settings.currency ?? "USD"}):
                        <input
                            type="number"
                            name="product_price"
                            placeholder="Ex. 0.00"
                            bind:value={settings.product.regular_price}
                        />
                    </label>
                    <label>
                        Sales price ({settings.currency ?? "USD"}):
                        <input
                            type="number"
                            name="product_sales_price"
                            bind:value={settings.product.sale_price}
                        />
                    </label>
                </div>
                <div>
                    <h4>Additional</h4>
                    {#if settings.store === "woocommerce"}
                        <Switch
                            bind:checked={settings.product.auto_comp}
                            name="auto_comp"
                            onLabel="Auto complete WooCommerce status on purchase"
                        />
                    {/if}
                    <Switch
                        bind:checked={settings.product.auto_hide}
                        name="auto_hide"
                        onLabel="Hide the product when bought"
                    />
                    <label for="store-sort">
                        Menu order
                        <input
                            bind:value={settings.product.menu_order}
                            type="number"
                            name="store-sort"
                            id="store-sort"
                            min="0"
                        />
                    </label>
                    <SideModal trigger="Mange access" class="transparent">
                        <CourseAccess />
                    </SideModal>
                </div>
            </div>
            <div class="img">
                <h4>Product image</h4>
                <div class="prod-img-wrap">
                    <img
                        {src}
                        {alt}
                        style="max-width:100%;"
                        bind:this={imgContainer}
                    />
                </div>
                <button type="button" onclick={() => openImageModal()}
                    >Change image</button
                >
            </div>
        </div>
        <div class="description">
            <label for="product_desc"><h4>Product description:</h4></label>
            <textarea
                name="product_desc"
                id="product_desc"
                rows="4"
                bind:value={settings.product.description}
            ></textarea>
            <label for="product_short_desc"
                ><h4>Product short description:</h4></label
            >
            <textarea
                name="product_short_desc"
                id="product_short_desc"
                rows="4"
                bind:value={settings.product.short_description}
            ></textarea>
        </div>
    {:else}
        <div class="no-prod col center">
            <p>There is no product linked to this course yet.</p>
            <button
                type="button"
                class="lighter-btn"
                onclick={initialiseProduct}>Create a new product</button
            >
        </div>
    {/if}
</div>
