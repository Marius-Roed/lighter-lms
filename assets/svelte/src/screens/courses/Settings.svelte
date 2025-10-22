<script>
    import Advanced from "$components/tabs-settings/Advanced.svelte";
    import General from "$components/tabs-settings/General.svelte";
    import Selling from "$components/tabs-settings/Selling.svelte";
    import Downloads from "$components/tabs-settings/Downloads.svelte";
    import Tabs from "$components/Tabs.svelte";
    import { settings } from "$lib/settings.svelte";
    import { topics } from "$lib/state.svelte";

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

    const formatVal = (v) => {
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

    const normSettings = $derived.by(() => {
        const tags = settings.tags.flatMap((t) => t.name);
        return {
            ...settings,
            tags,
        };
    });
</script>

{#snippet hiddenInput(objects, baseName = "")}
    {@const oldName = baseName}
    {#each Object.entries(objects) as [n, value]}
        {@const baseName = `${oldName}[${n}]`}
        {#if typeof value === "object" && value !== null && !(value instanceof Date)}
            {@render hiddenInput(value, baseName)}
        {:else}
            {@const name = `settings${baseName}`}
            <input type="hidden" {name} value={formatVal(value)} />
        {/if}
    {/each}
{/snippet}

<div class="lighter-settings-data">
    {@render hiddenInput(normSettings)}
</div>

<Tabs {items} class="settings-tabs" />
