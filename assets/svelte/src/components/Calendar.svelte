<script lang="ts">
    import type { HTMLAttributes } from "svelte/elements";
    import { SvelteDate } from "svelte/reactivity";

    interface Props extends HTMLAttributes<HTMLSpanElement> {
        date: Date;
        rendered: Date;
        endDate?: Date;
        range?: boolean;
        startMonday?: boolean;
        locale?: string;
        class?: string;
    }

    /** @type {CalendarProps} */
    let {
        date: selected = $bindable(),
        endDate: endSelected = $bindable(),
        rendered = selected,
        range = false,
        startMonday = true,
        locale = "",
        class: classNames = "",
        ...restProps
    }: Props = $props();

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
        new Date(rendered.getFullYear(), rendered.getMonth() + 1, 0).getDate(),
    );

    let firstDay = $derived(
        new Date(rendered.getFullYear(), rendered.getMonth(), 1).getDay(),
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
            rendered.getFullYear(),
            rendered.getMonth(),
            0,
        ).getDate();

        const cells = [];

        for (let i = offset - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            const { datetime, selectedPart } = generateCellInfo(day, -1);

            cells.push({ day, datetime, outside: true, selectedPart });
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const { datetime, selectedPart } = generateCellInfo(i);

            cells.push({ day: i, datetime, outside: false, selectedPart });
        }

        const remaining = 42 - cells.length;
        for (let i = 1; i <= remaining; i++) {
            const { datetime, selectedPart } = generateCellInfo(i, 1);

            cells.push({ day: i, datetime, outside: true, selectedPart });
        }

        return cells;
    });

    function generateCellInfo(
        day: number,
        monthAdjust: number = 0,
    ): { datetime: Date; selectedPart: boolean } {
        const datetime = new Date(
            rendered.getFullYear(),
            rendered.getMonth() + monthAdjust,
            day,
            rendered.getHours(),
            rendered.getMinutes(),
        );

        const selectedPart =
            range &&
            datetime.getTime() >= selected.getTime() &&
            datetime.getTime() <= endSelected.getTime();

        return { datetime, selectedPart };
    }

    function isSameDay(a: Date, b: Date): boolean {
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
                    // @ts-ignore
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
