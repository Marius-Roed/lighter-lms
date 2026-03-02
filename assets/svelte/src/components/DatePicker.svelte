<script lang="ts">
    import Calendar from "./Calendar.svelte";
    import Icon from "./Icon.svelte";

    interface Props {
        value?: Date;
        locale?: string;
        startMonday?: boolean;
    }

    let {
        value = $bindable(new Date()),
        locale = "",
        startMonday = true,
    }: Props = $props();

    if (!(value instanceof Date)) {
        value = new Date(value);
    }

    let rendered = $derived(value);

    let currentYear = $derived(rendered.getFullYear());
    let currentMonth = $derived(rendered.getMonth());
    let selectedDay = $derived(rendered.getDate());

    let hours = $derived(rendered.getHours().toString().padStart(2, "0"));
    let minutes = $derived(rendered.getMinutes().toString().padStart(2, "0"));

    const years = Array.from({ length: 55 }, (_, i) => i + 1985);
    const months = Array.from({ length: 12 }, (_, i) => {
        let d = new Date(1970, i, 1);
        return d.toLocaleDateString(locale || undefined, { month: "short" });
    });

    function changeDay(date: Date) {
        rendered = new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate(),
            parseInt(hours),
            parseInt(minutes),
        );
    }

    function changeMonth(month: number) {
        rendered = new Date(
            currentYear,
            month,
            selectedDay,
            parseInt(hours),
            parseInt(minutes),
        );
    }

    function changeYear(year: number) {
        rendered = new Date(
            year,
            currentMonth,
            selectedDay,
            parseInt(hours),
            parseInt(minutes),
        );
    }

    function changeTime(h: number, m: number) {
        rendered = new Date(currentYear, currentMonth, selectedDay, h, m);
    }

    /**
     * Rejects any keyboard input that contains "-+eE,." characters.
     */
    function blockInvalid(e: KeyboardEvent) {
        if (["-", "+", "e", "E", ",", "."].includes(e.key)) {
            e.preventDefault();
        }
    }
</script>

<div class="datetime-picker">
    <div class="header">
        <button
            type="button"
            onclick={(e) => {
                if (e.shiftKey) {
                    changeYear(currentYear - 1);
                } else {
                    const prev = (currentMonth + 11) % 12;
                    if (prev > currentMonth) changeYear(currentYear - 1);
                    changeMonth(prev);
                }
            }}
        >
            <Icon name="chevron" size="0.75em" />
        </button>
        <select
            id="date-picker-year"
            bind:value={currentYear}
            onchange={(e) => changeYear(+(e.target as HTMLSelectElement).value)}
        >
            {#each years as year}
                <option value={year}>{year}</option>
            {/each}
        </select>
        &ndash;
        <select
            name="month"
            id="date-picker-month"
            bind:value={currentMonth}
            onchange={(e) =>
                changeMonth(+(e.target as HTMLSelectElement).value)}
        >
            {#each months as month, i}
                <option value={i}>{month}</option>
            {/each}
        </select>
        <button
            type="button"
            onclick={(e) => {
                if (e.shiftKey) {
                    changeYear(currentYear + 1);
                } else {
                    const next = (currentMonth + 1) % 12;
                    if (next < currentMonth) changeYear(currentYear + 1);
                    changeMonth(next);
                }
            }}
        >
            <Icon name="chevron" size="0.75em" />
        </button>
    </div>
    <Calendar bind:date={value} class="cal-1" {rendered} {startMonday} />
    <div class="time">
        <input
            type="number"
            min="0"
            max="23"
            name="time-hours"
            value={hours}
            onkeydown={blockInvalid}
            oninput={(e) => {
                const hour = +(e.target as HTMLInputElement).value % 24;
                if (hour >= 10) {
                    queueMicrotask(() =>
                        (
                            (e.target as HTMLInputElement)
                                .nextElementSibling as HTMLElement
                        ).focus(),
                    );
                }
                changeTime(+hour, +minutes);
            }}
        />
        :
        <input
            type="number"
            min="0"
            max="59"
            name="time-minutes"
            value={minutes}
            onkeydown={blockInvalid}
            oninput={(e) => {
                if (+(e.target as HTMLInputElement).value < 60) {
                    changeTime(+hours, +(e.target as HTMLInputElement).value);
                } else {
                    (e.target as HTMLInputElement).value = (
                        e.target as HTMLInputElement
                    ).value.slice(0, 2);
                    (e.target as HTMLInputElement).blur();
                }
            }}
        />
    </div>
</div>
