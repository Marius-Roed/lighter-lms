<script lang="ts">
  import type { HTMLAttributes } from "svelte/elements";
  import MultiCheckbox from "./MultiCheckbox.svelte";
  import { getCourseService } from "$lib/utils/index.ts";

  interface Props extends HTMLAttributes<HTMLDivElement> {}

  let { ...props }: Props = $props();

  const service = getCourseService();
</script>

<div class="course-access-wrap" {...props}>
  <h2>Limit availability</h2>
  <div class="col">
    <label>
      Limit seating
      <input type="number" name="stock_limit" min="0" />
    </label>
    <span>Leave empty to not limit</span>
  </div>
  <hr />
  <h2>Grant access</h2>
  <div class="course-access-selectors">
    {#each Object.keys(service.settings.product.access) as key (key)}
      {@const topic = service.course.topics?.find((t) => t.key === key)}
      <MultiCheckbox
        items={topic?.lessons ?? []}
        title={topic?.title ?? "New topic"}
        bind:group={service.settings.product.access[key]}
      />
    {:else}
      <p>No topics found</p>
    {/each}
  </div>
</div>
