<script>
    import lighterFetch from "$lib/lighterFetch";
    import { onMount } from "svelte";
    import { SvelteMap } from "svelte/reactivity";

    let { users = $bindable([]) } = $props();

    let value = $state("");
    let dropdownIdx = $state(0);
    let isOpen = $state(false);
    let results = $state([]);
    let cache = new SvelteMap();

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

    const doFetch = async (q = "") => {
        q = q.trim().toLowerCase();

        if (cache.has(q)) {
            results = cache.get(q);
            return Promise.resolve(results);
        }

        let url = "/wp-json/wp/v2/users?search=" + encodeURIComponent(q);

        try {
            const resp = await lighterFetch({
                url,
                method: "GET",
                parse: false,
            });

            if (!resp.ok) {
                throw new Error("Error fetching topics");
            }

            const data = await resp.json();
            results = data;

            cache.set(q, data);

            return results;
        } catch {
            results = [];
        }
    };
    const debouncedFetch = (() => {
        const debounced = debouncePromise(doFetch, 350);
        return (q = "") => {
            const normq = q.trim().toLowerCase();

            if (cache.has(normq)) {
                return Promise.resolve(cache.get(normq));
            }

            return debounced(q);
        };
    })();

    const handleSearchInput = (/** @type {KeyboardEvent} */ e) => {
        if (e.key === "Enter") {
            e.preventDefault();
        }
    };

    /**
     * Appends a user to the selected users list
     *
     * @param {Object} user
     */
    const appendUser = (user) => {
        if (users.some((u) => u.slug == user.slug)) return;
        users.push(user);
    };

    const handleDropdownKey = (/** @type {KeyboardEvent} */ e) => {
        if (e.key == "Enter") {
            e.preventDefault();
        }
    };

    onMount(async () => {
        let url = "/wp-json/wp/v2/users";
        let resp = await lighterFetch({
            url,
            method: "GET",
            parse: false,
        });
        if (resp.ok) {
            cache.set("", await resp.json());
        }
    });

    $effect(() => {
        $inspect(cache);
    });
</script>

<div class="users-wrap bordered">
    <div class="user-list col">
        <input
            type="text"
            onkeydown={handleSearchInput}
            bind:value
            onfocusin={() => {
                isOpen = true;
            }}
            onfocusout={() => (isOpen = false)}
            style:border-bottom-left-radius={isOpen ? "0" : ".625em"}
            style:border-bottom-right-radius={isOpen ? "0" : ".625em"}
        />
        {#if isOpen}
            <div class="dropdown">
                {#await debouncedFetch(value) then results}
                    <ul>
                        {#each results as person}
                            <li>
                                <div
                                    onmousedown={() => {
                                        appendUser(person);
                                        value = "";
                                    }}
                                    onkeydown={handleDropdownKey}
                                    tabindex="0"
                                    aria-selected={users.some(
                                        (u) => u.slug == person.slug,
                                    )}
                                    role="option"
                                >
                                    <b>{person.name}</b>
                                    {person.email}
                                    <span class="user-id">ID: {person.id}</span>
                                </div>
                            </li>
                        {:else}
                            No users found. Try a different search term.
                        {/each}
                    </ul>
                {/await}
            </div>
        {/if}
        {#if users.length}
            <ul class="lighter-user-access-list">
                {#each users as user}
                    <li>
                        <div>
                            <img
                                src={user.avatar_urls[48]}
                                alt="User profile picutre"
                            />
                        </div>
                        {user.name}
                    </li>
                {/each}
            </ul>
        {:else}
            <span class="no-users">Select users to grant course access.</span>
        {/if}
    </div>
</div>
