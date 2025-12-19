
import Randflake from "./randflake";
import lighterFetch from "./lighterFetch";
import settings, { initAccess } from "./settings.svelte";
import { untrack } from "svelte";

/** @typedef {"publish"|"pending"|"future"|"private"|"draft"} LessonStatus */

/** @typedef {"clean" | "dirty"} EditStatus */

/**
 * @typedef {Object} Lesson
 * @property {number} [id] - ID of the lesson post
 * @property {string} key - Unique lesson key
 * @property {string} title - Lesson title
 * @property {number} sortOrder - The number it appears when sorted
 * @property {string} parentTopicKey - Key of the topic this lesson belongs to
 * @property {LessonStatus} postStatus="draft" - Status of the lesson
 * @property {EditStatus} editStatus="clean" - Whether the lesson has been edited.
 * @property {string} [permalink] - Url to the lesson
 * @property {string} [icon] - Name of the icon to show
 */

/**
 * @typedef {Object} Topic
 * @property {string} key - Unique Topic key
 * @property {string} title - Topic title
 * @property {number} sortOrder - The number it appears when sorted
 */

/** @typedef {Topic | Lesson} HierarchicalItem
 * @description Union type for flattened output
 */

/** @typedef {Topic & {lessons: Lesson[] }} TopicWithLessons */

/**
 * @typedef {Object} DeleteModal
 * @property {boolean} open
 * @property {"topic"|"lesson"} type
 * @property {number} id
 * @property {string} key
 * @property {string} title
 * @property {number} lessonCount
 */

let isInited = $state(false);

export const course = $state({ id: 0, tags: { all: [], selected: [] } });

export const topics = $state(
    /** @type {Topic[]} */([])
);

export const lessons = $state(
    /** @type {Lesson[]} */([])
);

const defaults = /** @type {Partial<Lesson>[]} */[];

// INFO: Derived hack since it might not be inited if used outside this module.
export var safeLessons = () => {
    const v = $derived(isInited ? lessons : []);
    return v;
};

/**
 * Initialize state from server data
 * @param {{courseNum: number, topicsData: Topic[], lessonsData: Lesson[]}} data
 */
export function initState({ courseNum: initCourse, topicsData: initTopics, lessonsData: initLessons }) {
    course.id = initCourse;
    if (lighterCourse) {
        course.tags.all = lighterCourse.tags.all;
        course.tags.selected = lighterCourse.tags.selected;
    }

    for (const topic of initTopics) {
        if (!topic.key) topic.key = genKey();
    }

    for (const lesson of initLessons) {
        if (!lesson.key) lesson.key = genKey();
        lesson.editStatus = "clean";
    }

    topics.splice(0, topics.length, ...initTopics);
    lessons.splice(0, lessons.length, ...initLessons);
    defaults.splice(0, defaults.length, ...initLessons);

    isInited = true;
}

$effect.root(() => {
    $effect(() => {
        lessons;
        for (let i = 0; i < lessons.length; i++) {
            if (!objectEquals(lessons[i], defaults[i])) {
                untrack(() => lessons[i].editStatus = "dirty");
            } else {
                untrack(() => lessons[i].editStatus = "clean");
            }
        }
    });
});

/**
 * See if two objects are complete equal.
 *
 * @param {object} obj1 
 * @param {object} obj2 
 * @returns {boolean}
 */
function objectEquals(obj1, obj2) {
    if (Object.keys(obj1).length != Object.keys(obj2).length) {
        return false;
    }
    for (const key in obj1) {
        if (key == "editStatus") continue;
        if (obj1[key] !== obj2[key]) {
            return false;
        }
    }
    return true;
}

/**
 * Gets a flattened, ordered array.
 * @returns {HierarchicalItem[]}
 */
function flattenOrder() {
    const sortedTopics = [...topics].sort((a, b) => {
        const orderDiff = (a.sortOrder ?? Infinity) - (b.sortOrder ?? Infinity);
        return orderDiff !== 0 ? orderDiff : a.key.localeCompare(b.key);
    });

    const lessonByTopic = new Map();
    lessons.forEach(lesson => {
        const { parentTopicKey: parent } = lesson;
        if (parent && !lessonByTopic.has(parent)) {
            lessonByTopic.set(parent, []);
        }
        if (parent) {
            lessonByTopic.get(parent).push(lesson);
        }
    });

    lessonByTopic.forEach((lessonList, _) => {
        lessonList.sort(
            ( /** @type {Lesson} */ a,
            /** @type {Lesson} */ b) => {
                const orderDiff = (a.sortOrder ?? Infinity) - (b.sortOrder ?? Infinity);
                return orderDiff !== 0 ? orderDiff : a.key.localeCompare(b.key);
            }
        );
    });

    const flattened = [];
    sortedTopics.forEach(topic => {
        flattened.push(topic);
        const topicLessons = lessonByTopic.get(topic.key) || [];
        flattened.push(...topicLessons);
    });

    return flattened;
}

/**
 * Generates a unique key
 * @returns {string} - The generated key
 */
function genKey() {
    const machine = window.LighterLMS.machineId || 0;
    const gen = new Randflake(machine);

    return gen.generate();
}

/**
 * Gets the amount of lessons from a specified topic
 *
 * @param {string} topicId - Topic to get the lessons from
 * @returns {number} - The amount of lessons 0-base.
 */
function topicLessonLength(topicId) {
    const l = lessons.filter((les) => les.parentTopicKey === topicId);
    return l.length;
}

/**
 * Gets a topic based on an identifier
 *
 * @param {string} key - The key of the topic
 *
 * @returns {object}
function getTopicIdx(key) {
    return topics.findIndex((t) => t.key === key);
};
*/

/**
 * Add a new topic
 * @param {string} [title="New module"]
 */
export function addTopic(title = "New Topic") {
    const key = genKey();
    topics.push({
        key,
        title,
        sortOrder: topics.length + 1,
    });

    if (settings.product.access && !settings.product.access[key]) {
        settings.product.access[key] = [];
    }
}

/**
 * Get a topic by key
 * @param {string} key 
 * @returns {Topic}
 */
export function getTopic(key) {
    return topics.filter((t) => t.key === key)[0];
}


/**
 * Get a hierarchical view of topics with their lessons
 * @param {Topic} topic 
 * @returns {TopicWithLessons}
 */
export function getTopicWithLessons(topic) {
    return {
        ...topic,
        lessons: lessons.filter(l => l.parentTopicKey === topic.key)
    };
}

/**
 * @param {Topic} topic 
 * @returns {Lesson[]}
 */
export function getTopicLessons(topic) {
    return lessons.filter(l => l.parentTopicKey === topic.key);
}

/**
 * Change the topic order
 * @param {number} sourceIndex - Index of the topic to move
 * @param {number} targetIndex - Destination index
 */
export function moveTopic(sourceIndex, targetIndex) {
    if (
        sourceIndex < 0 ||
        targetIndex < 0 ||
        sourceIndex >= topics.length ||
        targetIndex >= topics.length
    ) {
        return;
    }

    const [moved] = topics.splice(sourceIndex, 1);

    topics.splice(targetIndex, 0, moved);

    topics.forEach((t, i) => {
        t.sortOrder = i;
    });
}

/**
 * Updates the title of a topic
 *
 * @param {number} idx - The index of the topic
 * @param {string} v - The new title value
 */
export function updateTopicTitle(idx, v) {
    topics[idx].title = v.trim();
}

/**
 * Deletes a topic from a given key 
 * + recursively deletes it's lessons.
 *
 * @param {string} key 
 */
export async function deleteTopic(key) {
    let topicIndex = topics.findIndex(t => t.key === key)

    if (topicIndex < 0) {
        throw new Error('Could not find topic with ID (' + key + ')');
    }

    try {
        await lighterFetch({
            path: "/topic/" + key,
            method: "DELETE",
        });

        for (let i = 0; i < lessons.length; i++) {
            if (lessons[i].parentTopicKey === key) await deleteLesson(lessons[i].id, lessons[i].key, i);
        }

        topics.splice(topicIndex, 1);
    } catch (e) {
        console.error(e);
    }
}

/**
 * Get a lesson by key
 *
 * @param {string} lessonKey - key of the lesson
 */
export function getLesson(lessonKey) {
    return lessons.findIndex(l => l.key === lessonKey);
}

/**
 * Move a lesson
 *
 * @param {Lesson} lesson - The lesson object to move.
 * @param {number|boolean} moveUp - Whether to move the lesson up or down. 1/true for up, 0/false for down.
 */
export function moveLesson(lesson, moveUp) {
    const isUp = !!moveUp;
    const topicLessons = lessons
        .filter(({ parentTopicKey }) => lesson.parentTopicKey === parentTopicKey)
        .sort((a, b) => a.sortOrder - b.sortOrder);

    const currIdx = topicLessons.findIndex(({ key }) => key === lesson.key);
    if (currIdx === -1) return;

    if (isUp && currIdx > 0) {
        const prevLesson = topicLessons[currIdx - 1];
        const temp = lesson.sortOrder;
        lesson.sortOrder = prevLesson.sortOrder;
        prevLesson.sortOrder = temp;
    } else if (!isUp && currIdx < topicLessons.length - 1) {
        const nextLesson = topicLessons[currIdx + 1];
        const temp = lesson.sortOrder;
        lesson.sortOrder = nextLesson.sortOrder;
        nextLesson.sortOrder = temp;
    }
}

/**
 * Adds a lesson to a topic
 * @param {string} topicKey - Key of the topic the lesson belongs to
 * @param {string} [title="New lesson"]
 */
export function addLesson(topicKey, title = "New lesson") {
    const key = genKey();
    /** @type {LessonStatus} */
    const draft = "draft";
    const newLesson = {
        key,
        title,
        status: draft,
        parentTopicKey: topicKey,
        sortOrder: (topicLessonLength(topicKey) + 1),
        editStatus: "dirty",
    }
    lessons.push(newLesson);
    if (settings.product.access && settings.product.access[topicKey]) {
        settings.product.access[topicKey].push(key);
    } else if (settings.product.access) {
        settings.product.access[topicKey] = [key];
    }
}

/**
 *
 * @param {string} key 
 * @param {Partial<Lesson>} [data={}] - The new data to insert
 */
export function updateLesson(key, data = {}) {
    const idx = getLesson(key);

    if (idx < 0) return;

    const existing = lessons[idx];
    if (!existing) return;

    const updated = {
        ...existing,
        ...data
    };

    lessons[idx] = updated;
}

/**
 * Delete a lesson from the server + cleint
 * 
 * @param {number} id - ID of the lesson post
 * @param {string} key - The lesson key
 * @param {number} [idx=-1] - The index of the leesson
 */
export async function deleteLesson(id, key, idx = -1) {
    if (idx < 0) {
        idx = getLesson(key);
    }

    try {
        await lighterFetch({
            path: '/lesson/' + key,
            method: 'DELETE',
        });
    } catch (e) {
        console.error(e);
    } finally {
        lessons.splice(idx, 1);
        /* NOTE:
         * Maybe change this in the future.
         * Currently it is like this, as a call will fail
         * if the lesson was never saved to the DB, however
         * the ui should still reflect the update.
         */
    }
}

/**
 * @param {string} key 
 */
export async function syncLesson(key) {
    const idx = getLesson(key);
    /** @type Partial<Lesson> */
    const synced = {};

    const resp = await lighterFetch({
        'url': '/wp-json/wp/v2/lighter_lessons/' + lessons[idx].id,
    });

    for (const key of Object.keys(lessons[idx])) {
        if (key in resp) {
            synced[key] = resp[key].rendered ?? resp[key];
        }
    }

    lessons[idx] = { ...lessons[idx], ...Object.fromEntries(Object.entries(synced)) };
}

export const editModal = $state({
    open: false,
    key: null,
    lesson: null,
});

/**
 * Opens edit lesson modal to lesson with key
 *
 * @param {string} key - The key of the lesson to open the modal with
 */
export function editLesson(key) {
    const idx = getLesson(key);
    if (idx < 0) return; // TODO: Show error; Tried to open lesson.

    const lesson = lessons[idx];

    editModal.open = true;
    editModal.key = lesson.key;
    editModal.lesson = lesson;
}

export function editNextLesson() {
    const allLessons = flattenOrder().filter(item => !item.courseId);
    const idx = allLessons.findIndex((l) => l.key === editModal.key);

    if (idx < 0 || idx + 1 > allLessons.length - 1) return;

    editModal.key = allLessons[idx + 1].key;
    editModal.lesson = allLessons[idx + 1];
}

export function editPrevLesson() {
    const allLessons = flattenOrder().filter(item => !item.courseId);
    const idx = allLessons.findIndex((l) => l.key === editModal.key);

    if (idx < 0 || idx - 1 < 0) return;

    editModal.key = allLessons[idx - 1].key;
    editModal.lesson = allLessons[idx - 1];
}




/** @type {DeleteModal} */
export const deleteModal = $state({
    open: false,
    type: null,
    id: null,
    key: null,
    title: "",
    lessonCount: 0
});

/**
 * Shows modal to confirm deletion of topic
 *
 * @param {Topic} topic 
 */
export function confirmDeleteTopic(topic) {
    const count = lessons.filter((l) => (l.parentTopicKey === topic.key)).length;

    deleteModal.open = true;
    deleteModal.type = "topic";
    deleteModal.key = topic.key;
    deleteModal.title = topic.title;
    deleteModal.lessonCount = count;
}

/**
 * Shows modal to confirm deletion of lesson
 *
 * @param {Lesson} lesson 
 */
export function confirmDeleteLesson(lesson) {
    deleteModal.open = true;
    deleteModal.type = "lesson";
    deleteModal.id = lesson.id;
    deleteModal.key = lesson.key;
    deleteModal.title = lesson.title;
    deleteModal.lessonCount = 0;
}

export const postStatus = {
    publish: "Published",
    pending: "Pending review",
    future: "Scheduled",
    private: "Private",
    draft: "Draft",
};
