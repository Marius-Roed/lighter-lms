import type { LessonData } from "$types/course.d.ts";

export class Lesson {
    readonly id: number;
    readonly key: string;
    readonly slug: string;
    readonly date: string;
    readonly author: string;
    readonly type = "lesson" as const;
    readonly parentKey: string;

    title = $state("");
    sortOrder = $state(0);
    status = $state<LessonData["status"]>("auto-draft");
    lessonType = $state<LessonData["lesson_type"]>("text");
    modified = $state("");

    readonly #original: LessonData;
    readonly isDirty = $derived(
        this.title !== this.#original.title.rendered
        || this.sortOrder !== this.#original.sort_order
        || this.status !== this.#original.status
        || this.lessonType !== this.#original.lesson_type
    );

    constructor(data: LessonData) {
        this.#original = data;
        this.id = data.id;
        this.key = data.lesson_key;
        this.slug = data.slug;
        this.date = data.date;
        this.author = data.author;
        this.parentKey = data.parent_key;
        this.title = data.title.rendered;
        this.sortOrder = data.sort_order;
        this.status = data.status;
        this.lessonType = data.lesson_type;
        this.modified = data.modified;
    }

    toRestData(): Partial<LessonData> {
        return {
            id: this.id,
            title: { rendered: this.title },
            status: this.status,
            lesson_type: this.lessonType,
            sort_order: this.sortOrder,
            parent_key: this.parentKey,
        };
    }
}
