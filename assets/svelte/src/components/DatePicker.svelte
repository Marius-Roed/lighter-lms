<script>
    import Calendar from "./Calendar.svelte";
    import Icon from "./Icon.svelte";

    /**
     * @typedef {Object} DatePickerProps
     * @property {Date} [value = new Date()]
     * @property {string} [locale = ""]
     * @property {boolean} [startMonday = true]
     * @property {boolean} [inline = false]
     */

    /** @type {DatePickerProps} */
    let {
        value = $bindable(new Date()),
        locale = "",
        startMonday = true,
    } = $props();

    if (!(value instanceof Date)) {
        value = new Date(value);
    }

    let currentYear = $derived(value.getFullYear());
    let currentMonth = $derived(value.getMonth());
    let selectedDay = $derived(value.getDate());

    let hours = $derived(value.getHours().toString().padStart(2, "0"));
    let minutes = $derived(value.getMinutes().toString().padStart(2, "0"));

    const years = Array.from({ length: 55 }, (_, i) => i + 1985);
    const months = Array.from({ length: 12 }, (_, i) => {
        let d = new Date(1970, i, 1);
        return d.toLocaleDateString(locale || undefined, { month: "short" });
    });

    /**
     * changes the current date value
     *
     * @param {Date} date
     */
    function changeDay(date) {
        value = new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate(),
            parseInt(hours),
            parseInt(minutes),
        );
    }

    /**
     * Changes the current month
     *
     * @param {number} month
     */
    function changeMonth(month) {
        value = new Date(
            currentYear,
            month,
            selectedDay,
            parseInt(hours),
            parseInt(minutes),
        );
    }

    /**
     * Changes the current year
     *
     * @param {number} year
     */
    function changeYear(year) {
        value = new Date(
            year,
            currentMonth,
            selectedDay,
            parseInt(hours),
            parseInt(minutes),
        );
    }

    /**
     * Change the current time
     *
     * @param {number} h - The hours
     * @param {number} m - The minutes
     */
    function changeTime(h, m) {
        value = new Date(currentYear, currentMonth, selectedDay, h, m);
    }

    /**
     * Rejects any keyboard input that contains "-+eE,." characters.
     *
     * @param {KeyboardEvent} e
     */
    function blockInvalid(e) {
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
                    changeMonth((currentMonth + 11) % 12);
                }
            }}
        >
            <Icon name="chevron" size="0.75em" />
        </button>
        <select
            id="date-picker-year"
            bind:value={currentYear}
            onchange={(e) => changeYear(+e.target.value)}
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
            onchange={(e) => changeMonth(+e.target.value)}
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
                    changeMonth((currentMonth + 1) % 12);
                }
            }}
        >
            <Icon name="chevron" size="0.75em" />
        </button>
    </div>
    <Calendar bind:date={value} class="cal-1" {startMonday} />
    <div class="time">
        <input
            type="number"
            min="0"
            max="23"
            name="time-hours"
            value={hours}
            onkeydown={blockInvalid}
            oninput={(e) => {
                const hour = e.target.value % 24;
                if (hour >= 10) {
                    queueMicrotask(() => e.target.nextElementSibling.focus());
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
                if (e.target.value < 60) {
                    changeTime(+hours, +e.target.value);
                } else {
                    e.target.value = e.target.value.slice(0, 2);
                    e.target.blur();
                }
            }}
        />
    </div>
</div>
