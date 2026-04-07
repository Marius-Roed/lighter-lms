import { CourseAPI } from "$lib/api/course-api.ts";
import { Randflake } from "$lib/utils/randflake.ts";
import type { CourseData, LessonData, LessonDataCreate, LessonType, TopicData } from "$types/course.js";
import { Lesson } from "../state/course-lesson.svelte.ts";
import { Course } from "../state/course-post.svelte.ts";
import { Topic } from "../state/course-topic.svelte.ts";
import { EditModal } from "../state/edit-modal.svelte.ts";
import { MoveModal } from "../state/lesson-move.svelte.ts";

export class CourseService {
    readonly #api: CourseAPI;
    readonly editModal: EditModal;
    readonly moveModal: MoveModal;

    course = $state<Course>() as Course;
    error = $state<string>("");

    constructor(data: CourseData) {
        this.course = new Course(data);
        this.#api = new CourseAPI(data.id);

        this.editModal = new EditModal(() => this.course.allLessons ?? []);
        this.moveModal = new MoveModal();
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

    async moveLesson(fromIndex: number, toIndex: number, fromTopicKey: string, toTopicKey?: string) {
        if (!this.course) return;

        const grabOrder = (lessons: Lesson[]) => lessons.map((l) => ({ key: l.key, sortOrder: l.sortOrder }));

        const fromTopic = this.course.topics.find((t) => t.key === fromTopicKey);
        const toTopic = this.course.topics.find((t) => t.key === toTopicKey) ?? fromTopic;
        if (!fromTopic || !toTopic) return;

        const fromSnapshot = grabOrder(fromTopic.lessons);
        const fromLessonsBefore = [...fromTopic.lessons];

        const isDifferent = fromTopic.key !== toTopic.key;
        const toSnapshot = isDifferent ? grabOrder(toTopic.lessons) : false;
        const toLessonsBefore = isDifferent ? [...toTopic.lessons] : false;

        this.course.moveLesson(fromIndex, toIndex, fromTopic.key, toTopic.key);

        try {
            const fromData = grabOrder(fromTopic.lessons);
            await this.#api.updateLessonOrder(fromData, fromTopic.key);
            if (isDifferent) {
                const toData = grabOrder(toTopic.lessons);
                await this.#api.updateLessonOrder(toData, toTopic.key);
            }
        } catch (e) {
            fromTopic.lessons = fromLessonsBefore;
            for (const s of fromSnapshot) {
                fromTopic.lessons.find((l) => l.key === s.key)!.sortOrder = s.sortOrder;
            }

            if (isDifferent && toSnapshot && toLessonsBefore) {
                toTopic.lessons = toLessonsBefore;
                for (const s of toSnapshot) {
                    toTopic.lessons.find((l) => l.key === s.key)!.sortOrder = s.sortOrder;
                }
            }
            // TODO: Toast failure.
        }
    }

    setLessonStatus(lessonKey: string | number, value: LessonData['status']): void {
        const lesson = typeof lessonKey === "string"
            ? this.course.getLessonByKey(lessonKey)
            : this.course.allLessons.find((l) => l.id === lessonKey);
        if (!lesson) return;

        const oldVal = lesson.status;

        lesson.setStatus(value);

        this.#api.updateLessonStatus(lesson.id, value).catch(() => {
            lesson.setStatus(oldVal);
        });
    }

    createLesson(topicKey: string, data: { title: string, lesson_type?: string }): void {
        const topic = this.course.topics.find((t) => t.key === topicKey);
        if (!topic) return;

        const placeholder = {
            lighter_lesson_key: new Randflake().generate(),
            title: data.title,
            status: "draft" as const,
            type: "lighter_lessons",
            lighter_lesson_type: (data.lesson_type ?? "text") as LessonType,
            _lighter_meta: {
                [this.course.id ?? 0]: {
                    course_id: this.course.id ?? 0,
                    title: this.course.title ?? "",
                    topics: [
                        {
                            key: topic.key,
                            sort_order: (topic.sortedLessons?.length + 1) * 10,
                            title: topic.title,
                        }
                    ]
                }
            },
        } satisfies LessonDataCreate;

        const lesson = Lesson.fromCreate(placeholder);
        topic.addLesson(lesson);

        this.#api.createLesson(placeholder).then((realLesson) => {
            lesson.update(realLesson);
        }).catch(() => {
            topic.removeLesson(placeholder.lighter_lesson_key);
            // TODO: Toast failure.
        });
    }

    deleteLesson(lessonId: number) {
        const lesson = this.course.allLessons.find(l => l.id === lessonId);
        if (!lesson) return;

        const topic = this.course.topics.find((t) => t.key === lesson.parentKey);
        if ( !topic) return;

        const snapshot = [...topic.lessons];
        topic.removeLesson(lesson.key);

        this.#api.deleteLesson(lesson.id).catch(() => {
            topic.lessons = snapshot;
            // TODO: Toast error
        });
    }

    serializeLesson(lesson: Lesson) {
        return lesson.serialize() ?? "";
    }

    insertTopic(title: string, nodeKey: string, position: "before" | "after" = "after", lessons: LessonData[] = []): void {
        const existingTopic = this.course.topics.find(t => t.key === nodeKey);
        if (!existingTopic) throw new Error(`Cannot find node to insert adjacent topic on`);

        const placeholder: TopicData = {
            key: new Randflake().generate(),
            title,
            courseId: this.course.id ?? 0,
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
            courseId: this.course.id ?? 0,
            sortOrder: this.course.topics?.length ?? 0,
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
        }) ?? []);

        const reordered = this.course.moveTopic(fromIndex, toIndex) ?? [];

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

        const snapshot = [...this.course.topics ?? []];

        this.course.removeTopic(topicKey);

        this.#api.deleteTopic(topicKey).catch(() => {
            if (this.course)
                this.course.topics = snapshot;
            // TODO: Toast failure.
        });
    }
}
