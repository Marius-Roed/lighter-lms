<script>
    import lighterFetch from "$lib/lighterFetch";

    let {
        url,
        multi = true,
        value = $bindable([]),
        placeholder = null,
    } = $props();

    let search = $state("");
    let highIdx = $state(0);
    let opts = $state([]);
    let total = $state(0);
    let cache = new Map();

    let searchInput;

    /**
     * Select a tag, respecting multi.
     *
     * @param {Object} tag
     */
    const selectTag = (tag) => {
        if (value.some((o) => Object.keys(tag)[0] in o)) return;
        if (multi) {
            value.push(tag);
        } else {
            value = [tag];
        }
    };

    /**
     * Remove a tag.
     *
     * @param {Object} tag
     */
    const removeTag = (tag) => {
        value = value.filter((v) => v !== tag);
    };

    /**
     * Find an option by global index
     *
     * @param {number} idx
     * @returns {Object|null} topic
     */
    const findOptByIdx = (idx) => {
        let curr = 0;
        for (const opt of opts) {
            if (opt.topics && Array.isArray(opt.topics)) {
                for (const topic of opt.topics) {
                    if (curr === idx) {
                        return topic;
                    }
                    curr++;
                }
            }
        }

        return null;
    };

    /**
     *
     * @param {Function} func
     * @param {number} delay
     * @returns {Function} Debounced function returning a promise.
     */
    const debouncePromise = (func, delay) => {
        let timeoutId = null;
        let rejectFn = null;

        /** @param {any} args */
        return (...args) => {
            if (timeoutId) {
                window.clearTimeout(timeoutId);
            }

            const promise = new Promise((res, outerRej) => {
                rejectFn = outerRej;

                timeoutId = window.setTimeout(async () => {
                    try {
                        const result = await func(...args);
                        res(result);
                        rejectFn = null;
                    } catch (error) {
                        outerRej(error);
                        rejectFn = null;
                    }
                    timeoutId = null;
                }, delay);
            });

            return promise;
        };
    };

    const handleKeyDown = (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            if (!total || !e.target.value || !opts.length) return;
            const topic = findOptByIdx(highIdx);
            if (topic) {
                selectTag(topic);
                highIdx = 0;
                search = "";
            }
        } else if (e.key === "Backspace" && !search.length && value.length) {
            value.pop();
        } else if (e.key === "ArrowUp" && highIdx > 0) {
            e.preventDefault();
            highIdx--;
        } else if (e.key === "ArrowDown" && highIdx + 1 < total) {
            e.preventDefault();
            highIdx++;
        }
    };

    const doFetch = async (q = "") => {
        q = q.toLowerCase().trim();

        if (cache.has(q)) {
            const { data, total: cachedTotal } = cache.get(q);
            opts = data;
            total = cachedTotal;
            return Promise.resolve(opts);
        }

        try {
            const resp = await lighterFetch({
                url: url + encodeURIComponent(q),
                method: "GET",
                parse: false,
            });

            if (!resp.ok) {
                throw new Error("Error fetching topics");
            }

            const data = await resp.json();
            total = parseInt(resp.headers.get("topics_total"));

            let globalIdx = 0;
            for (const opt of data) {
                if (opt.topics && Array.isArray(opt.topics)) {
                    for (const topic of opt.topics) {
                        topic._idx = globalIdx++;
                    }
                }
            }

            cache.set(q, { data, total });
            opts = data;

            return data;
        } catch {
            opts = [];
            total = 0;
            return [];
        }
    };

    const debouncedFetch = debouncePromise(doFetch, 350);
</script>

<div class="search-container tag-search">
    <div class="search-wrap">
        <div class="selected-tags">
            {#each value as opt}
                {@const [val] = Object.values(opt)}
                <span class="tag">
                    {val}
                    <button
                        type="button"
                        class="remove-tag"
                        onclick={(e) => {
                            e.stopPropagation();
                            removeTag(opt);
                        }}>×</button
                    >
                </span>
            {/each}
        </div>

        <input
            type="text"
            class="search"
            placeholder={!value.length ? (placeholder ?? "Add tag") : ""}
            bind:value={search}
            bind:this={searchInput}
            onkeydown={handleKeyDown}
        />
    </div>

    {#if search.trim()}
        <ul class="dropdown">
            {#await debouncedFetch(search) then opts}
                {#each opts as opt}
                    <li>
                        {opt.title}
                        <ul>
                            {#each opt.topics as topic}
                                {@const [val] = Object.values(topic)}
                                <li
                                    onmousedown={() => selectTag(topic)}
                                    role="option"
                                    tabindex="0"
                                    aria-selected={topic._idx === highIdx}
                                    class:hl={topic._idx === highIdx}
                                >
                                    {val}
                                </li>
                            {/each}
                        </ul>
                    </li>
                {/each}
            {/await}
        </ul>
    {/if}
</div>
