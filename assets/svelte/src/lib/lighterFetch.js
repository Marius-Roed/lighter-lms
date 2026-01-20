import apiFetch from "@wordpress/api-fetch";

/**
 * Configure apiFetch with nonce + root url
 * @param {string} restUrl - The base REST namespace
 * @param {string} nonce - The REST API nonce
 */
export function setupApiFetch(restUrl, nonce) {
    apiFetch.use(apiFetch.createRootURLMiddleware(restUrl));
    apiFetch.use(apiFetch.createNonceMiddleware(nonce));
    return apiFetch;
}

/**
 * Preconfigured wordpress apiFetch
 * @template T
 * @type {<T>(options: import('@wordpress/api-fetch').APIFetchOptions) => Promise<T>}
 */
const lighterFetch = /** @type {any} */ (setupApiFetch(window.LighterLMS.restUrl, window.LighterLMS.nonce));

export default lighterFetch;
