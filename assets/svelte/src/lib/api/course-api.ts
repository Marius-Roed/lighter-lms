import { lighterFetch } from "./lighter-fetch.ts";
import type { Lesson } from "$state/course-lesson.svelte.ts";
import type { Topic } from "$state/course-topic.svelte.ts";
import { addQueryArgs } from "$lib/utils/index.ts";
import type { LessonData, TopicData } from "$types/course.d.ts";

export class CourseAPI {
    readonly #courseId: number;

    constructor(courseId: number) {
        this.#courseId = courseId;
    }

    // ─── Lesson ───────────────────────────────────────

    createLesson(data: Lesson) {
        return lighterFetch<Lesson>({
            path: "lesson",
            method: "POST",
            data: data.toRestData(),
        });
    }

    updateLesson(lessonId: number, data: Lesson) {
        return lighterFetch({
            path: `lesson/${lessonId}`,
            method: "PUT",
            data: data.toRestData()
        });
    }

    updateLessonTitle(lessonId: number, title: string) {
        return lighterFetch({
            path: `lesson/${lessonId}`,
            method: "PATCH",
            data: { title },
        });
    }

    updateLessonStatus(lessonId: number, status: string) {
        return lighterFetch<LessonData>({
            path: `lesson/${lessonId}`,
            method: "PUT",
            data: { status },
        });
    }

    updateLessonOrder(fromTopicKey: string, toTopicKey: string, data) {
        throw new Error("Not yet impletmented!");
    }

    deleteLesson(lessonKey: string) {
        return lighterFetch({
            path: `lesson/${lessonKey}`,
            method: "DELETE",
        });
    }

    // ─── Topic ───────────────────────────────────────

    createTopic(data: Topic) {
        return lighterFetch<TopicData>({
            path: `course/${this.#courseId}/topic`,
            method: "POST",
            data: data.toRestData(),
        });
    }

    updateTopic(topicKey: string, data: Topic) {
        return lighterFetch({
            path: `course/${this.#courseId}/topic/${topicKey}`,
            method: "PATCH",
            data: data.toRestData()
        });
    }

    updateTopicTitle(topicKey: string, title: string) {
        return lighterFetch({
            path: `course/${this.#courseId}/topic/${topicKey}`,
            method: "PATCH",
            data: { title }
        });
    }

    updateTopicOrder(topicKeys: string[]) {
        return lighterFetch({
            path: `course/${this.#courseId}/topic-order`,
            method: "PUT",
            data: { order: topicKeys }
        });
    }

    moveTopic(fromIndex: number, toIndex: number) {
        return lighterFetch<TopicData[]>({
            path: `course/${this.#courseId}/topic-move`,
            method: "PUT",
            data: { fromIndex, toIndex }
        });
    }

    deleteTopic(topicKey: string) {
        return lighterFetch({
            path: `course/${this.#courseId}/topic/${topicKey}`,
            method: "DELETE",
        });
    }

    getTopic(topicKey: string, withLessons = false) {
        let url = `course/${this.#courseId}/topic/${topicKey}`;
        return lighterFetch({
            path: addQueryArgs(url, { withLessons }),
            method: "GET",
        });
    }
}

