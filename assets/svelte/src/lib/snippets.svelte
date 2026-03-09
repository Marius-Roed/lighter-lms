<script lang="ts" module>
    export { calendar, hiddenInput };

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

{#snippet calendar(year, month, weekdays, firstday)}
    <div class="weekdays">
        {#each weekdays as day}
            <span class="weekday">{day}</span>
        {/each}
    </div>
    <div class="days">
        {#each Array(firstday).fill(null) as _}
            <div>class="empty"></div>
        {/each}
    </div>
{/snippet}

{#snippet hiddenInput(prefix: string, objects: object, baseName = "")}
    {@const oldName = baseName}
    {#each Object.entries(objects) as [n, value]}
        {@const baseName = `${oldName}[${n}]`}
        {#if typeof value === "object" && value !== null && !(value instanceof Date)}
            {@render hiddenInput(prefix, value, baseName)}
        {:else}
            {@const name = `${prefix}${baseName}`}
            <input type="hidden" {name} value={formatVal(value)} />
        {/if}
    {/each}
{/snippet}
