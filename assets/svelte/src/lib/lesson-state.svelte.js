
/**
 * @typedef {Object} LessonSettings
 * @property {array} parents
 */

export const lessonSettings = $state(
    /** @type {LessonSettings} */(LighterLMS.lesson?.settings)
);
