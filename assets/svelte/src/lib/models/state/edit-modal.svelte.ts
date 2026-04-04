import type { Lesson } from './course-lesson.svelte.ts';

export class EditModal {
    currentLessonId = $state<number | null>(null);

    #allLessons: () => Lesson[];

    constructor(allLessons: () => Lesson[]) {
        this.#allLessons = allLessons;
    }

    currentIndex = $derived.by(() => 
        this.#allLessons().findIndex((l) => l.id === this.currentLessonId)
    );

    currentLesson = $derived.by(() => 
        this.currentIndex >= 0 ? this.#allLessons()[this.currentIndex] : null
    );

    previousLesson = $derived.by(() => 
        this.currentIndex > 0 ? this.#allLessons()[this.currentIndex - 1] : null
    );

    nextLesson = $derived.by(() => {
        const lessons = this.#allLessons();
        const next = this.currentIndex + 1;
        return next < lessons.length ? lessons[next] : null;
    });

    open(lessonId: number) {
        this.currentLessonId = lessonId;
    }

    close() {
        this.currentLessonId = null;
    }

    goNext() {
        if (this.nextLesson) this.currentLessonId = this.nextLesson.id;
    }

    goPrev() {
        if (this.previousLesson) this.currentLessonId = this.previousLesson.id;
    }
}
