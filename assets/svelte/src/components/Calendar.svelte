<script>
    /**
     * @typedef {Object} CalendarProps
     * @property {Date} date
     * @property {Date} [endDate]
     * @property {boolean} [range=false]
     * @property {boolean} [startMonday=true]
     * @property {string} [locale]
     * @property {string} [class]
     */

    import { SvelteDate } from "svelte/reactivity";

    /** @type {CalendarProps} */
    let {
        date: selected = $bindable(),
        endDate: endSelected = $bindable(),
        range = false,
        startMonday = true,
        locale = "",
        class: classNames = "",
        ...restProps
    } = $props();

    if (!(selected instanceof SvelteDate)) {
        selected = new SvelteDate(selected ?? Date.now());
    }

    if (range && !(endSelected instanceof SvelteDate)) {
        endSelected = new SvelteDate(endSelected ?? Date.now());
    }

    if (range) {
        $effect(() => {
            if (selected > endSelected) endSelected = selected;
            if (endSelected < selected) selected = endSelected;
        });
    }

    let toPick = range ? "endSelected" : "selected";

    let daysInMonth = $derived(
        new Date(selected.getFullYear(), selected.getMonth() + 1, 0).getDate(),
    );

    let firstDay = $derived(
        new Date(selected.getFullYear(), selected.getMonth(), 1).getDay(),
    );

    let weekdays = $derived.by(() => {
        const base = new Date(1973, 0, 7 + +startMonday);
        return Array.from({ length: 7 }, (_, i) => {
            let d = new Date(base);
            d.setDate(d.getDate() + i);
            return d
                .toLocaleDateString(locale || undefined, { weekday: "short" })
                .slice(0, 2);
        });
    });

    let calendarDays = $derived.by(() => {
        const offset = (firstDay - +startMonday + 7) % 7;
        const prevMonthDays = new Date(
            selected.getFullYear(),
            selected.getMonth(),
            0,
        ).getDate();

        const cells = [];

        for (let i = offset - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            const datetime = new Date(
                selected.getFullYear(),
                selected.getMonth() - 1,
                day,
                selected.getHours(),
                selected.getMinutes(),
            );
            const selectedPart =
                range &&
                datetime.getTime() >= selected.getTime() &&
                datetime.getTime() <= endSelected.getTime();
            cells.push({ day, datetime, outside: true, selectedPart });
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const datetime = new Date(
                selected.getFullYear(),
                selected.getMonth(),
                i,
                selected.getHours(),
                selected.getMinutes(),
            );
            const selectedPart =
                range &&
                datetime.getTime() >= selected.getTime() &&
                datetime.getTime() <= endSelected.getTime();

            cells.push({ day: i, datetime, outside: false, selectedPart });
        }

        const remaining = 42 - cells.length;
        for (let i = 1; i <= remaining; i++) {
            const datetime = new Date(
                selected.getFullYear(),
                selected.getMonth() + 1,
                i,
                selected.getHours(),
                selected.getMinutes(),
            );
            const selectedPart =
                range &&
                datetime.getTime() >= selected.getTime() &&
                datetime.getTime() <= endSelected.getTime();

            cells.push({ day: i, datetime, outside: true, selectedPart });
        }

        return cells;
    });

    /**
     * @param {Date} a
     * @param {Date} b
     */
    function isSameDay(a, b) {
        if (!a || !b) return false;
        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }
</script>

<div class="calendar">
    <div class="weekdays">
        {#each weekdays as weekday}
            <span class="weekday">{weekday}</span>
        {/each}
    </div>
    <div class="cells">
        {#each calendarDays as { day, datetime, outside, selectedPart }}
            <span
                class={[
                    "cell",
                    isSameDay(selected, datetime) && "selected",
                    isSameDay(endSelected, datetime) && range && "selected",
                    selectedPart && range && "selected-part",
                    restProps.class,
                ]}
                class:outside
                onclick={() => {
                    if (!range) {
                        selected = datetime;
                    } else if (range && toPick == "selected") {
                        selected = datetime;
                        toPick = "endSelected";
                    } else if (range && toPick == "endSelected") {
                        endSelected = datetime;
                        toPick = "selected";
                    }
                }}
                {...restProps}
            >
                {day}
            </span>
        {/each}
    </div>
</div>
