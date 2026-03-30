import { CourseAPI } from "$lib/api/course-api.ts";
import { Randflake } from "$lib/utils/randflake.ts";
import type { CourseData, LessonData, TopicData } from "$types/course.js";
import { Lesson } from "../state/course-lesson.svelte.ts";
import { Course } from "../state/course-post.svelte.ts";
import { Topic } from "../state/course-topic.svelte.ts";

export class CourseService {
    readonly #api: CourseAPI;

    course = $state<Course>(null);
    error = $state<string>("");

    constructor(data: CourseData) {
        this.course = new Course(data);
        this.#api = new CourseAPI(data.id);
    }

    renameLesson(lessonKey: string, newTitle: string) {
        const lesson = this.course.getLessonByKey(lessonKey);
        if (!lesson) return;

        const oldTitle = lesson.title;
        lesson.title = newTitle;

        this.#api.updateLessonTitle(lesson.id, newTitle).catch(() => {
            lesson.title = oldTitle;
            // TODO: Toast failure.
        });
    }

    moveLessonDirection(lesson: Lesson | string, direction: "up" | "down"): void {
        if (!this.course) return;

        if (typeof lesson === "string") {
            lesson = this.course.getLessonByKey(lesson);

            if (!lesson) return;
        }

        const move = direction === "up" ? 1 : -1;
        const idx = lesson.sortOrder;
        const newIdx = idx + move;

        this.moveLesson(lesson.parentKey, lesson.parentKey, idx, newIdx);
    }

    moveLesson(fromTopicKey: string, toTopicKey: string, fromIndex: number, toIndex: number) {
        if (!this.course) return;

        const fromTopic = this.course.topics.find(
            (t) => t.key === fromTopicKey
        );
        const toTopic = this.course.topics.find(
            (t) => t.key === toTopicKey
        );
        if (!fromTopic || !toTopic) return;

        const fromSnapshot = fromTopic.lessons.map((l) => ({
            key: l.key,
            sortOrder: l.sortOrder,
        }));
        const toSnapshot = toTopic.lessons.map((l) => ({
            key: l.key,
            sortOrder: l.sortOrder,
        }));
        const fromLessonsBefore = [...fromTopic.lessons];
        const toLessonsBefore = [...toTopic.lessons];

        this.course.moveLesson(fromTopicKey, toTopicKey, fromIndex, toIndex);

        this.#api.updateLessonOrder(fromTopicKey, toTopicKey, {
            from: fromTopic.lessons.map(l => l.key),
            to: toTopic.lessons.map(l => l.key),
        }).catch(() => {
            fromTopic.lessons = fromLessonsBefore;
            toTopic.lessons = toLessonsBefore;
            for (const s of fromSnapshot) {
                fromTopic.lessons.find((l) => l.key === s.key)!.sortOrder = s.sortOrder;
            }
            for (const s of toSnapshot) {
                toTopic.lessons.find((l) => l.key === s.key)!.sortOrder = s.sortOrder;
            }
            // TODO: Toast failure.
        });
    }

    setLessonStatus(lessonKey: string | number, value: LessonData['status']): void {
        const lesson = typeof lessonKey === "string"
            ? this.course.getLessonByKey(lessonKey)
            : this.course.allLessons.find((l) => l.id === lessonKey);
        if (!lesson) return;

        lesson.setStatus(value);
    }

    createLesson(topicKey: string, data: { title: string, lesson_type?: string }): void {
        const topic = this.course.topics.find((t) => t.key === topicKey);
        if (!topic) return;

        const placeholder = {
            id: -Date.now(),
            lesson_key: new Randflake().generate(),
            slug: "",
            date: new Date().toISOString(),
            date_gmt: new Date().toISOString(),
            modified: new Date().toISOString(),
            modified_gmt: new Date().toISOString(),
            author: "",
            parent: 0,
            parent_key: topicKey,
            title: { rendered: data.title },
            content: { rendered: "" },
            excerpt: { rendered: "" },
            menu_order: topic.lessons.length,
            sort_order: topic.lessons.length,
            status: "auto-draft" as const,
            type: "lesson" as "lesson",
            lesson_type: data.lesson_type ?? "text",
            meta: {},
        };

        topic.addLesson(placeholder);

        this.#api.createLesson(new Lesson(placeholder)).then((lesson) => {
            topic.removeLesson(placeholder.lesson_key);
            topic.addLesson(lesson);
        }).catch(() => {
            topic.removeLesson(placeholder.lesson_key);
            // TODO: Toast failure.
        });
    }

    deleteLesson(topicKey: string, lessonId: number) {
        const topic = this.course.topics.find(t => t.key === topicKey);
        if (!topic) return;

        const lesson = topic.lessons.find(l => l.id === lessonId);
        if (!lesson) return;

        const snapshot = [...topic.lessons];
        topic.removeLesson(lesson.key);

        this.#api.deleteLesson(lesson.key).catch(() => {
            topic.lessons = snapshot;
            // TODO: Toast error
        });
    }

    insertTopic(title: string, nodeKey: string, position: "before" | "after" = "after", lessons: LessonData[] = []): void {
        const existingTopic = this.course.topics.find(t => t.key === nodeKey);
        if (!existingTopic) throw new Error(`Cannot find node to insert adjacent topic on`);

        const placeholder: TopicData = {
            key: new Randflake().generate(),
            title,
            course: this.course.id,
            sortOrder: position === "before" ? existingTopic.sortOrder : existingTopic.sortOrder + 1,
            lessons
        }

        this.course.topics.forEach(t => {
            t.sortOrder = t.sortOrder > existingTopic.sortOrder ? t.sortOrder + 1 : t.sortOrder;

            if (position === "before") t.sortOrder++;
        });

        this.course.addTopic(placeholder);
    }

    createTopic(title: string, lessons: LessonData[] = []): void {
        const placeholder: TopicData = {
            key: new Randflake().generate(),
            title,
            course: this.course.id,
            sort_order: this.course.topics.length,
            lessons
        }

        this.course.addTopic(placeholder);

        this.#api.createTopic(new Topic(placeholder)).then((real) => {
            this.course.removeTopic(placeholder.key);
            this.course.addTopic(real);
        }).catch((e) => {
            console.error(e);
            this.course.removeTopic(placeholder.key);
            // TODO: Toast failure.
        });
    }

    renameTopic(topicKey: string, newTitle: string) {
        const topic = this.course.topics.find((t) => t.key === topicKey);
        if (!topic) return;

        const oldTitle = topic.title;
        topic.title = newTitle;

        this.#api.updateTopicTitle(topicKey, newTitle).catch(() => {
            topic.title = oldTitle;
            // TODO: Toast failure.
        })
    }

    serializeTopic(topic: Topic): string {
        return topic.serialize() ?? "";
    }

    moveTopic(fromIndex: number, toIndex: number) {
        const fromTopic = this.course.sortedTopics[fromIndex];
        const toTopic = this.course.sortedTopics[toIndex];

        if (!(fromTopic instanceof Topic) || !(toTopic instanceof Topic)) return;

        const snapshot = Array.from(this.course.topics.map(t => {
            return { key: t.key, sort: t.sortOrder, updatedAt: t.updatedAt };
        }));

        const reordered = this.course.moveTopic(fromIndex, toIndex);

        this.#api.moveTopic(fromTopic.key, reordered)
            .then((res) => {
                res.forEach((n) => {
                    this.course.updateTopic(n);
                });
            })
            .catch((e) => {
                console.error(e);
                snapshot.forEach(({ key, sort, updatedAt }) => {
                    const topic = this.course.topics.find(t => t.key === key)
                    if (topic) {
                        topic.sortOrder = sort;
                        topic.updatedAt = updatedAt;
                    }
                });
                // TODO: Toast failure.
            });
    }

    deleteTopic(topicKey: string) {
        const topic = this.course.topics.find((t) => t.key === topicKey);
        if (!topic) return;

        const snapshot = [...this.course.topics];

        this.course.removeTopic(topicKey);

        this.#api.deleteTopic(topicKey).catch(() => {
            this.course.topics = snapshot;
            // TODO: Toast failure.
        });
    }

    shuffleTopics() {
        this.course.topics?.map(t => {
            t.sortOrder = Math.floor(Math.random() * 9);
            return t;
        });
        console.log(this.course.topics);
    }
}
