<script>
    import { setProduct, settings } from "$lib/settings.svelte";
    import apiFetch from "@wordpress/api-fetch";
    import { SvelteMap } from "svelte/reactivity";

    let { ...restProps } = $props();

    let products = $state([]);
    let productStatus = $state("loading");
    let cache = new SvelteMap();
    let isOpen = $state(false);
    let highIdx = $state(0);
    let search = $state("");

    let input;
    let dropdown = $state();

    /**
     *
     * @param {Function} func
     * @param {number} delay
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

    /**
     * @param {string} q - The search query
     * @returns {Promise<Array>}
     */
    const fetchProducts = async (q = "") => {
        q = q.toLowerCase().trim();

        if (cache.has(q)) {
            products = cache.get(q);
            return Promise.resolve(products);
        }

        const resp = await apiFetch({
            url: `/wp-json/wc/v3/products?per_page=8&status=any&search=${encodeURIComponent(q)}`,
        });

        cache.set(q, resp);
        products = resp;

        return resp;
    };

    const searchProducts = debouncePromise(fetchProducts, 350);

    const scrollToItem = () => {
        const highlighted = dropdown?.querySelector(".hl");
        if (highlighted) {
            highlighted.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
            });
        }
    };

    /** @param {KeyboardEvent} e */
    const handleInput = (e) => {
        if (e.key === "ArrowUp" && highIdx > 0) {
            highIdx--;
            setTimeout(scrollToItem, 0);
        } else if (e.key === "ArrowDown" && highIdx + 1 < products.length) {
            highIdx++;
            setTimeout(scrollToItem, 0);
        } else if (e.key === "Enter") {
            e.preventDefault();
            let title = setCurProduct(products[highIdx]);
            input.blur();
            search = title;
        } else if (e.key === "Escape") {
            input.blur();
        }
    };

    /**
     * @param {import("$lib/settings.svelte").Product} prod
     * @returns {string}
     */
    const setCurProduct = (prod) => {
        highIdx = 0;
        let t = setProduct(prod);
        restProps.cb?.();
        return t;
    };
</script>

<div class="product-search-container">
    <input
        type="text"
        name="product"
        placeholder="Select a product"
        onfocusin={(e) => {
            isOpen = true;
            searchProducts(e.target.value);
        }}
        onfocusout={() => (isOpen = false)}
        onkeydown={handleInput}
        bind:value={search}
        style:border-bottom-left-radius={isOpen ? "0" : ".625em"}
        style:border-bottom-right-radius={isOpen ? "0" : ".625em"}
        bind:this={input}
    />
    {#if isOpen}
        <div class="product-search-wrap" bind:this={dropdown}>
            {#await searchProducts(search)}
                <div class="center-text">
                    <p><strong>Fetching products</strong></p>
                    <svg width="200" height="6">
                        <line
                            x1="10"
                            y1="3"
                            x2="190"
                            y2="3"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke="#D2C8E1"
                        />
                        <line
                            x1="10"
                            y1="3"
                            x2="50"
                            y2="3"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke="#639"
                        >
                            <animate
                                attributeName="x2"
                                values="50;190;50"
                                dur="2.5s"
                                repeatCount="indefinite"
                            />
                            <animate
                                attributeName="x1"
                                values="10;140;10"
                                dur="2.5s"
                                repeatCount="indefinite"
                            />
                        </line>
                    </svg>
                </div>
            {:then products}
                {#each products as product, idx (product.id)}
                    <div
                        class="product-item"
                        tabindex="0"
                        role="option"
                        aria-selected={settings.product.id == product.id}
                        onmousedown={() => {
                            setCurProduct(product);
                        }}
                        class:hl={idx === highIdx}
                    >
                        <div>
                            <h2>{product.name}</h2>
                            <div style="display:inline-flex;gap: 4ch;">
                                <p>
                                    Price: {!!product.regular_price
                                        ? product.regular_price
                                        : "0"}
                                    {settings.product.currency ?? "USD"}
                                </p>
                                <p>
                                    Sales price: {!!product.sale_price
                                        ? product.sale_price
                                        : "0"}
                                    {#if product.sale_price}
                                        {settings.product.currency ?? "USD"}
                                    {/if}
                                </p>
                            </div>
                        </div>
                        <div>
                            <img
                                src={product.images[0]?.src ??
                                    "https://placehold.co/150/D2C8E1/663399?text=%3F"}
                                alt={product.images[0]?.alt ?? "Product image"}
                            />
                        </div>
                    </div>
                {:else}
                    <div class="center-text"><p>No products found</p></div>
                {/each}
            {:catch}
                <div class="center-text">
                    <p>{productStatus}</p>
                </div>
            {/await}
        </div>
    {/if}
</div>
