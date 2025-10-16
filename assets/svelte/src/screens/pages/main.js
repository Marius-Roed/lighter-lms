import { hydrate } from 'svelte'
import App from './App.svelte'

/**
 * @typedef {Object} Columns
 * @property {string} cb
 * @property {string} title
 * @property {string} date
 * @property {Record<string, string>} [extras]
 */

/**
 * @typedef {Object} Pagination
 * @property {number} page
 * @property {number} totalPages
 * @property {number} totalPosts
 */

/**
 * @typedef {Object} LighterCourses
 * @property {Record<string, string>} actions
 * @property {Columns} columns
 * @property {Pagination} pagination
 * @property {import("$lib/posts.svelte").Post[]} posts
 */

const target = document.querySelector('.wrap');

if (!target) {
    throw new Error('Could not mount lighter-lms');
}

let app;
/** @type {LighterCourses} */
var lighterCourses = window.lighterCourses;

function getPostData() {
    if (lighterCourses) {
        app = hydrate(App, {
            target: target,
        });
    }
}

getPostData();

export default app;

