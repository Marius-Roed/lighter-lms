import apiFetch from "@wordpress/api-fetch";

/**
 * @typedef {Object} Post
 * @property {number} id - The ID of the post
 * @property {Date} date - The creation date
 * @property {Date} modified - When the post was last modified
 * @property {string} slug
 * @property {"publish" | "private" | "pending" | "future" | "draft" | "trash"} status
 * @property {string} type - Post type
 * @property {string} link - URL of the post
 * @property {Record<string, string>} columns
 */

export let posts = $state(
    /** @type {Post[]} */([])
);

/**
 * @param {Post[]} newPosts 
 */
export function initPosts(newPosts) {
    posts.splice(0, posts.length, ...newPosts);
}

export let page = $state(1);

let totalPosts = $state(0);

/**
 * @param {number} [n=null] - The number to set the number of posts to.
 * @returns {number} the number of total posts.
 */
export function postsLength(n = null) {
    if (n) totalPosts = n;
    return totalPosts;
}

/**
 * Recursively flattens an object
 *
 * @param {Object} obj
 * @param {string} delimiter
 * @param {string} parentKey
 * @param {Object} result
 *
 * @returns {Object}
 */
function flatten(obj, delimiter = "_", parentKey = "", result = {}) {
    for (const [key, val] of Object.entries(obj)) {
        let newKey = parentKey ? parentKey + delimiter + key : key;

        if (typeof val === "object" && val !== null && !Array.isArray(val)) {
            flatten(val, delimiter, newKey, result);
        } else {
            if (newKey == 'filter_status') newKey = 'status';
            result[newKey] = val;
        }
    }
    return result;
}

function getSearchParams(userArgs) {
    const defaults = {
        page: 1,
        per_page: 20,
        _locale: "user",
    };

    let baseParams = {
        ...defaults,
        ...userArgs
    };

    baseParams = flatten(baseParams);

    return new URLSearchParams([...Object.entries(baseParams)]);
}

async function fetchPosts(page = 1, limit = 20, args = {}) {
    args = {
        ...args,
        page,
        per_page: limit,
    };

    const params = getSearchParams(args);

    const req_url = new URL(`${window.location.origin}/wp-json/wp/v2/lighter_courses?${params.toString()}`);

    const resp = await fetch(req_url.href, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'X-WP-Nonce': window.LighterLMS.nonce,
            'Content-Type': 'application/json',
        },
    });

    const totalPosts = resp.headers.get('X-wp-total');

    const data = await resp.json();

    return {
        posts: data,
        pagination: {
            totalPosts,
            currentPage: page,
            totalPages: Math.ceil(totalPosts / limit)
        }
    };
}

/*
export function createPostsStore() {
    let posts = $state([]);
    let currentPage = $state(1);
    let totalPosts = $state(0);
    let totalPages = $state(0);
    let loading = $state(false);
    let error = $state(null);

    const postsPerPage = 20;

    async function loadPosts(page = 1, args = {}) {
        loading = true;
        error = null;
        try {
            ({ posts, currentPage, totalPosts, totalPages } = await fetchPosts(page, postsPerPage, args));
        } catch (e) {
            error = e.message;
            posts = [];
        } finally {
            loading = false;
        }
    }

    loadPosts(1);

    return {
        get posts() { return posts; },
        get currentPage() { return currentPage; },
        get totalPosts() { return totalPosts; },
        get totalPages() { return totalPages; },
        get loading() { return loading; },
        get error() { return error; },
        loadPosts
    };
}
*/

export class CoursesStore {
    loading = $state(false);
    error = $state(null);
    postsPerPage = $state(20);
    filterTags = $state([]);
    filterDate = $state();

    constructor(courses) {
        this.courses = $state(courses.posts);
        this.pagination = $state(courses.pagination);
        this.columns = $state(courses.columns);
        this.filterStatus = $state(courses.filters.post_stati)
    }

    async loadPosts(page = 1) {
        this.loading = true;
        this.error = null;
        let args = {
            filter: {
                status: this.filterStatus,
                tags: this.filterTags,
                publishDate: this.filterDate
            }
        };
        try {
            const resp = await fetchPosts(page, this.postsPerPage, args);
            this.courses = resp.posts;
            this.pagination = resp.pagination;
        } catch (e) {
            console.error(e);
            this.error = e.message;
            this.courses = [];
        } finally {
            this.loading = false;
        }
    }
}
