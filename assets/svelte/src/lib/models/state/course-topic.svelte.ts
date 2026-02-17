import { Randflake } from "$lib/utils/randflake.ts";
import type { LessonData, TopicData } from "$types/course.d.ts"
import { Lesson } from "./course-lesson.svelte.ts"

export class Topic {
    readonly key: string;
    readonly course: number;

    title = $state("");
    sortOrder = $state(0);
    lessons = $state<Lesson[]>([]);

    readonly sortedLesson = $derived(
        [...this.lessons].sort((a, b) => a.sortOrder - b.sortOrder)
    );

    constructor(data: TopicData) {
        this.key = data.key ?? new Randflake().generate();
        this.course = data.course;
        this.title = data.title;
        this.sortOrder = data.sort_order;
        this.lessons = data.lessons?.map((l) => new Lesson(l));
    }

    addLesson(data: LessonData | Lesson): void {
        this.lessons = [...this.lessons, data instanceof Lesson ? data : new Lesson(data)];
    }

    removeLesson(key: string): void {
        this.lessons = this.lessons.filter((l) => l.key !== key);
    }

    toRestData(): TopicData {
        return {
            key: this.key,
            title: this.title,
            sort_order: this.sortOrder,
            course: this.course,
            lessons: this.lessons?.map((l) => l.toRestData()) ?? [],
        }
    }
}
