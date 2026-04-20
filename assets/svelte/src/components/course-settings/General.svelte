<script lang="ts">
  import DatePicker from "$components/DatePicker.svelte";
  import FilterSearch from "$components/FilterSearch.svelte";
  import {
    displayDate,
    getCourseService,
    isEmpty,
    POSTSTATUS,
  } from "$lib/utils/index.ts";

  const service = getCourseService();

  const statusMap = {
    publish: "Published",
    pending: "Pending review",
    draft: "Draft",
    trash: "Trashed",
    future: "Schedule",
    private: "Private",
  };

  let src = $derived(
    service.settings.syncProductImg && !isEmpty(service.settings.product)
      ? service.settings.product.images[0].src
      : service.settings.thumbnail.src,
  );
  let alt = $derived(
    service.settings.syncProductImg
      ? service.settings.product.images[0].alt
      : service.settings.thumbnail.alt,
  );
  let frame: Media;

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

      if (
        service.settings.syncProductImg &&
        !isEmpty(service.settings.product)
      ) {
        service.settings.product.images[0] = {
          id: attachment.id,
          src: attachment.url,
          alt: attachment.alt,
        };
      }
      service.settings.thumbnail = {
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
        <select name="post_status" bind:value={service.course.status}>
          {#each POSTSTATUS as value}
            {#if value !== "auto-draft" && value !== "trash"}
              <option {value}>{statusMap[value]}</option>
            {/if}
          {/each}
        </select>
      </span>
      <button
        type="button"
        popovertarget="datetime"
        style="anchor-name:--publish-date;"
        ><b>Published on:</b>
        {displayDate(service.course.date)}</button
      >
      <div id="datetime" popover>
        <DatePicker bind:value={service.course.date} />
      </div>
    </div>
  </div>
  <div class="course-tags">
    <h3>Tags</h3>
    <FilterSearch
      name="course-tags"
      width="100%"
      tags={LighterLMS.globals.courseTags}
      bind:selected={service.settings.tags}
      createNew
    />
  </div>
  <div class="course-desc">
    <h3>Description</h3>
    <textarea
      id="course-description"
      cols="35"
      rows="12"
      placeholder="Enter an eye catching description..."
      bind:value={service.course.excerpt}
    ></textarea>
  </div>
  <div class="course-img">
    <h3>Feature image</h3>
    <div class="col center">
      <div class="course-img-wrap">
        <img {src} {alt} />
        <button type="button" class="change-img" onclick={openImageModal}
          >Change image</button
        >
      </div>
    </div>
  </div>
</div>
