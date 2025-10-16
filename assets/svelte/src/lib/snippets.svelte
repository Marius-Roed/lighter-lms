<script module>
    export { calendar };
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
