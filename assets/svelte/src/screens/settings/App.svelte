<script>
    import General from "$components/plugin-settings/General.svelte";
    import ImportExport from "$components/plugin-settings/ImportExport.svelte";
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
        {
            label: "Import/Export",
            component: ImportExport,
        },
    ];
</script>

<Tabs {items} class="lighter-tab-menu" />
