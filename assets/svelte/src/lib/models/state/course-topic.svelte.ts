import { randflake } from "$lib/utils/index.ts";
import type { LessonData, TopicData } from "$types/course.d.ts"
import { Lesson } from "./course-lesson.svelte.ts"

export class Topic {
    readonly key: string;
    readonly courseId: number;

    title = $state("");
    sortOrder = $state(0);
    lessons = $state<Lesson[]>([]);
    isExpanded = $state(false);
    updatedAt = $state("");

    readonly sortedLessons = $derived.by(() => {
        return [...this.lessons ?? []].sort((a, b) => a.sortOrder - b.sortOrder).filter((l) => l.status !== "trash");
    });

    constructor(data: TopicData) {
        this.key = data.key ?? randflake().generate();
        this.courseId = data.courseId;
        this.title = data.title;
        this.sortOrder = data.sortOrder;
        this.lessons = data.lessons?.map((l) => new Lesson(l)) ?? [];
    }

    addLesson(data: LessonData | Lesson): void {
        this.lessons = [...this.lessons, data instanceof Lesson ? data : new Lesson(data)];
    }

    removeLesson(key: string): void {
        this.lessons = this.lessons.filter((l) => l.key !== key);
    }

    removeLessons(): void {
        this.lessons.forEach((l) => {
            this.removeLesson(l.key);
        });
    }

    toggleIsExpanded(force?: boolean): void {
        if (typeof force !== 'undefined') {
            this.isExpanded = force;
        } else {
            this.isExpanded = !this.isExpanded;
        }
    }

    getHiddenData(): object {
        return {
            key: this.key,
            title: this.title,
            sort_order: this.sortOrder,
            course_id: this.courseId,
        };
    }

    toRestData(): TopicData {
        return {
            key: this.key,
            title: this.title,
            sortOrder: this.sortOrder,
            courseId: this.courseId,
            lessons: this.lessons?.map((l) => l.toRestData()) ?? [],
        }
    }

    serialize(): string {
        return JSON.stringify(this.toRestData());
    }

    static deserialize(data: string): Topic {
        return new Topic(JSON.parse(data));
    }
}
