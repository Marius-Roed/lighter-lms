import apiFetch from "@wordpress/api-fetch";

/** @typedef {import('@wordpress/api-fetch').default} APIFetch */

/**
 * Configure apiFetch with nonce + root url
 * @param {string} restUrl - The base REST namespace
 * @param {string} nonce - The REST API nonce
 * @returns {APIFetch}
 */
export function setupApiFetch(restUrl, nonce) {
    apiFetch.use(apiFetch.createRootURLMiddleware(restUrl));
    apiFetch.use(apiFetch.createNonceMiddleware(nonce));
    return apiFetch;
}

/**
 * Preconfigured apiFetch
 * @type {APIFetch}
 */
const lighterFetch = setupApiFetch(window.LighterLMS.restUrl, window.LighterLMS.nonce);

export default lighterFetch;
