import { hydrate } from 'svelte'
import Course from './Course.svelte'
import Settings from './Settings.svelte';
import { initState } from '$lib/state.svelte.js';

const origin = window.origin;
const target = document.getElementById('lighter-course-mount');
const settingsTarget = document.getElementById('lighter-course-settings');

if (!target) {
    throw new Error('Could not mount lighter-lms');
}

const courseNum = Number(target.dataset.course) || 0;
const topicsData = JSON.parse(target.dataset.topics || "[]");
const lessonsData = JSON.parse(target.dataset.lessons || "[]");

for (let i = 0, len = lessonsData.length; i < len; i++) {
    /** @type {import('$lib/state.svelte.js').Lesson} */
    const lesson = lessonsData[i];
    lesson.permalink = lesson.id ? origin + "/wp-admin/post.php?post=" + lesson.id + "&action=edit&in_dialog=1" : null;
}

initState({ courseNum, topicsData, lessonsData });

const course = hydrate(Course, {
    target: target,
    props: {
        screen: target.dataset.screen || "dashboard",
        courseNum,
    }
});

const settings = hydrate(Settings, {
    target: settingsTarget,
    props: {
        courseNum,
    }
});

export default { course, settings };
