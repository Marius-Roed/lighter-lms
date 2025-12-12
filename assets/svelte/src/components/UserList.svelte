<script>
    import lighterFetch from "$lib/lighterFetch";
    import { onMount } from "svelte";
    import { SvelteMap } from "svelte/reactivity";
    import Icon from "./Icon.svelte";

    let { users = $bindable([]) } = $props();

    let value = $state("");
    let dropdownIdx = $state(0);
    let isOpen = $state(false);
    let results = $state([]);
    let userIdx = $state(0);
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
            appendUser(results[dropdownIdx]);
        } else if (e.key == "ArrowUp" || e.key == "ArrowDown") {
            e.preventDefault();
            let move = e.key == "ArrowUp" ? -1 : 1;
            if (
                (move < 0 && dropdownIdx > 0) ||
                (move > 0 && dropdownIdx < results.length - 1)
            ) {
                dropdownIdx += move;
            }
        }
    };

    /**
     * Appends a user to the selected users list
     *
     * @param {Object} user
     */
    const appendUser = (user) => {
        if (users.some((u) => u.id == user.id)) return;
        users.push(user);
        users.sort((a, b) => a.id - b.id);
    };

    /**
     * Removes a user from the selected list
     *
     * @param {Object} user
     */
    const removeUser = (user) => {
        users = users.filter((u) => u.id != user.id);
    };

    function handleDropdownKey(/** @type {KeyboardEvent} */ e) {
        if (e.key == "Enter") {
            e.preventDefault();
            appendUser(this);
        }
    }

    function handleUserListKeyEvent(/** @type {KeyboardEvent} */ e) {
        if (e.key == "Enter") {
            e.preventDefault();
            console.log(users[userIdx]);
        } else if (e.key == "ArrowDown" || e.key == "ArrowUp") {
            e.preventDefault();
            let move = e.key == "ArrowUp" ? -1 : 1;
            if (
                (move < 0 && userIdx > 0) ||
                (move > 0 && userIdx < users.length - 1)
            ) {
                userIdx += move;
            }
        }
    }

    onMount(() => {
        doFetch();
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
                        {#each results as person, idx}
                            <li>
                                <div
                                    onmousedown={(e) => {
                                        e.preventDefault();
                                        appendUser(person);
                                        value = "";
                                    }}
                                    tabindex="0"
                                    aria-selected={users.some(
                                        (u) => u.slug == person.slug,
                                    )}
                                    role="option"
                                    class:hover={dropdownIdx == idx &&
                                        "--hovered-opt"}
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
            <!-- svelte-ignore a11y_no_noninteractive_tabindex, a11y_no_noninteractive_element_interactions -->
            <ul
                class="lighter-user-access-list"
                tabindex="0"
                onkeydown={handleUserListKeyEvent}
            >
                {#each users as user}
                    <li>
                        <div class="user-pp">
                            <img
                                src={user.avatar_urls[48]}
                                alt="User profile picutre"
                            />
                        </div>
                        <div class="user-info col">
                            <strong>{user.name}</strong>
                            {user.email}
                        </div>
                        <div class="user-id">
                            ID: {user.id}
                        </div>
                        <!-- svelte-ignore a11y_click_events_have_key_events -->
                        <span
                            onclick={() => removeUser(user)}
                            tabindex="-1"
                            role="button"><Icon name="plus" /></span
                        >
                    </li>
                {/each}
            </ul>
        {:else}
            <span class="no-users">Select users to grant course access.</span>
        {/if}
    </div>
</div>
