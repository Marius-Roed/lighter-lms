
/**
 * @typedef {Object} LessonSettings
 * @property {string} slug
 * @property {array} parents
 */

export const lessonSettings = $state(
    /** @type {LessonSettings} */(LighterLMS.lesson?.settings)
);
