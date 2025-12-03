<script>
    import General from "$components/plugin-settings/General.svelte";
    import Lessons from "$components/plugin-settings/Lessons.svelte";
    import Template from "$components/plugin-settings/Template.svelte";
    import Tabs from "$components/Tabs.svelte";

    const items = [
        {
            label: "General",
            component: General,
        },
        {
            label: "Lessons",
            component: Lessons,
        },
        {
            label: "Template",
            component: Template,
        },
    ];
</script>

<Tabs {items} class="lighter-tab-menu" />
