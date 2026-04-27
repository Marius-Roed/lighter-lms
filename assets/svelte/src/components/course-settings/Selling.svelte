<script lang="ts">
  import CourseAccess from "$components/CourseAccess.svelte";
  import ProductSearch from "$components/ProductSearch.svelte";
  import SideModal from "$components/SideModal.svelte";
  import Switch from "$components/Switch.svelte";
  import type { CourseProductInstance } from "$lib/models/state/course-product.svelte.ts";
  import { capitalize } from "$lib/settings.svelte";
  import { getCourseService, isEmpty } from "$lib/utils/index.ts";
  import { onMount } from "svelte";

  const service = getCourseService();

  let src = $derived(
    service.settings.product?.images?.[0]?.src ??
      "https://placehold.co/350/D2C8E1/663399?text=%3F",
  );
  let alt = $derived(
    service.settings.product.images?.[0]?.alt ?? "Product image",
  );

  let imgContainer = $state();
  let showMore = $state(false);

  let frame: Media;
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

  onMount(() => {
    if (typeof wp !== undefined && wp.editor) {
      wp.editor.initialize("product_desc", editorSettings);
      wp.editor.initialize("product_short_desc", editorSettings);
    }

    return () => {
      wp.editor.remove("product_desc");
      wp.editor.remove("product_short_desc");
    };
  });

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
      // @ts-ignore
      const attachment = frame.state().get("selection").first().toJSON();

      service.settings.product.images = [
        {
          id: attachment.id,
          src: attachment.url,
          alt: attachment.alt,
        },
      ];
    });

    frame.open();
  }

  function initialiseProduct() {
    service.settings.createEmptyProduct(() => service.course);
    if (typeof tinymce !== undefined) {
      tinymce.get("product_desc").setContent("");
      tinymce.get("product_short_desc").setContent("");
    }
  }
</script>

<div class="product-select">
  <h3>
    Link to a product {#if LighterLMS.globals.store}
      in {capitalize(LighterLMS.globals.store)}
    {/if}
  </h3>
  <ProductSearch
    cb={() => {
      if (typeof tinymce !== undefined) {
        tinymce
          .get("product_desc")
          .setContent(service.settings.product.description);
        tinymce
          .get("product_short_desc")
          .setContent(service.settings.product.shortDescription);
      }
    }}
  />
  <button
    type="button"
    class="lighter-btn transparent"
    onclick={() => service.settings.createEmptyProduct(() => service.course)}
    >Create product</button
  >
</div>

<div class="sell-container">
  {#if !isEmpty(service.settings.product)}
    <div class="top">
      <div class="data">
        <div class="general">
          <h4>General</h4>
          <label>
            Product name:
            <input
              type="text"
              name="product_title"
              placeholder="Course: Learn to sell"
              bind:value={service.settings.product.name}
            />
          </label>
          <label>
            Price ({LighterLMS.globals.currency ?? "USD"}):
            <input
              type="number"
              name="product_price"
              placeholder="Ex. 0.00"
              bind:value={service.settings.product.price}
            />
          </label>
          <label>
            Sales price ({LighterLMS.globals.currency ?? "USD"}):
            <input
              type="number"
              name="product_sales_price"
              bind:value={service.settings.product.salePrice}
            />
          </label>
          <SideModal trigger="Mange access" class="transparent">
            <CourseAccess course={() => service.course} />
          </SideModal>
        </div>
        <div class="additional">
          <h4>Additional</h4>
          {#if LighterLMS.globals.store === "woocommerce"}
            {@const product = service.settings
              .product as CourseProductInstance<"woocommerce">}
            <label for="store-sort">
              Menu order
              <input
                bind:value={product.menuOrder}
                type="number"
                name="store-sort"
                id="store-sort"
                min="0"
              />
            </label>
            <label>
              SKU
              <input
                bind:value={product.sku}
                type="text"
                name="product_sku"
                id="product_sku"
              />
            </label>
          {/if}
          <button type="button" onclick={() => (showMore = !showMore)}
            >Show more</button
          >
          {#if showMore}
            <Switch
              bind:checked={service.settings.product.autoHide}
              name="auto_hide"
              onLabel="Hide the product when bought"
            />
            {#if LighterLMS.globals.store === "woocommerce"}
              {@const product = service.settings
                .product as CourseProductInstance<"woocommerce">}
              <label>
                Catalog visibility
                <select
                  name="product_visiblity"
                  id="prod_vis"
                  bind:value={product.catalogVisibility}
                >
                  <option value="visible">Search & shop</option>
                  <option value="catalog">Shop only</option>
                  <option value="search">Search only</option>
                  <option value="hidden">Hidden</option>
                </select>
              </label>
            {/if}
          {/if}
        </div>
      </div>
      <div class="img">
        <h4>Product image</h4>
        <div class="prod-img-wrap">
          <img {src} {alt} style="max-width:100%;" bind:this={imgContainer} />
          <button
            type="button"
            class="change-img"
            onclick={() => openImageModal()}>Change image</button
          >
        </div>
      </div>
    </div>
    <div class="description">
      <label for="product_desc"><h4>Product description:</h4></label>
      <textarea
        name="product_desc"
        id="product_desc"
        rows="4"
        bind:value={service.settings.product.description}
      ></textarea>
      <label for="product_short_desc"><h4>Product short description:</h4></label
      >
      <textarea
        name="product_short_desc"
        id="product_short_desc"
        rows="4"
        bind:value={service.settings.product.shortDescription}
      ></textarea>
    </div>
  {:else}
    <div class="no-prod col center">
      <p>There is no product linked to this course yet.</p>
      <button type="button" class="lighter-btn" onclick={initialiseProduct}
        >Create a new product</button
      >
    </div>
  {/if}
</div>
