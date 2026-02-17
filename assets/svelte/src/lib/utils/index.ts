import { Course } from '$lib/models/state/course-post.svelte.js';
import { CourseAPI } from '$lib/api/course-api.ts';

export { Randflake } from './randflake.ts';

export const initCourse = (): { course: Course, api: CourseAPI } => {
    if (!LighterLMS.course) {
        console.error('LighterLMS: Cannot initialize course. No course object found.');
        return;
    }

    const course = new Course(LighterLMS.course);
    const api = new CourseAPI(course.id);

    return { course, api };
};

export const addQueryArgs = (path: string, args: Record<string, string | number | boolean>): string => {
    const params = new URLSearchParams();

    for (const [key, val] of Object.entries(args)) {
        params.append(key, String(val));
    }

    const separator = path.includes("?") ? "&" : "?";
    return path + separator + params.toString();
}
