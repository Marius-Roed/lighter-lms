<script lang="ts">
  import Advanced from "$components/course-settings/Advanced.svelte";
  import General from "$components/course-settings/General.svelte";
  import Selling from "$components/course-settings/Selling.svelte";
  import Downloads from "$components/course-settings/Downloads.svelte";
  import Tabs from "$components/Tabs.svelte";
  import settings from "$lib/settings.svelte";
  import { hiddenInput } from "$lib/snippets.svelte";
  import { getCourseService } from "$lib/utils/index.ts";

  const service = getCourseService();

  let items = [
    {
      label: "General",
      component: General,
    },
    {
      label: "Advanced",
      component: Advanced,
    },
    {
      label: "Selling",
      component: Selling,
    },
    {
      label: "Downloads",
      component: Downloads,
    },
  ];

  const formatVal = (v: any) => {
    if (v instanceof Date) {
      return Math.floor(v.getTime() / 1000);
    }
    if (
      typeof v === "boolean" ||
      String(v)?.toLowerCase() === "on" ||
      String(v)?.toLowerCase() === "off"
    ) {
      return v ? (v === "off" ? "false" : "true") : "false";
    }

    return v ?? "";
  };
</script>

<div class="lighter-settings-data">
  <input
    type="hidden"
    id="excerpt"
    name="excerpt"
    bind:value={service.course.excerpt}
  />
  <input type="hidden" name="settings[slug]" bind:value={service.course.slug} />
  <input
    type="hidden"
    name="settings[status]"
    bind:value={service.course.status}
  />
  {@render hiddenInput("settings", service.settings.getHiddenData())}
</div>

<Tabs {items} class="settings-tabs" />
