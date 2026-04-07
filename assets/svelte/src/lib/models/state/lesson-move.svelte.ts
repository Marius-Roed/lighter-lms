import { lighterFetch } from '$lib/api/lighter-fetch.ts';
import { addQueryArgs } from '$lib/utils/index.ts';
import type { CourseData } from '$types/course.js';

export class MoveModal {
    rawCourses = $state<CourseData[]>([]);
    isOpen = $state(false);
    page = $state(1);
    chosenCourse = $state(0);

    courses = $derived.by(() => 
        [...this.rawCourses].sort((a, b) => new Date(b.date ?? 0) - new Date(a.date ?? 0))
    );

    topics = $derived.by(() => 
        this.rawCourses.find((c) => c.id === this.chosenCourse)?.topics ?? []
    );

    open() {
        this.isOpen = true;
    }

    close() {
        this.isOpen = false;
    }

    fetchCourse(id: number): void {
        lighterFetch<CourseData>({ url: 'wp/v2/lighter_courses/' + id + "?full_structure=true" }).then((res) => {
            this.rawCourses.push(res);
            this.chosenCourse = res.id;
        });
    }

    loadCourses(): void {
        if (this.page === 1 && this.courses.length) return; // No need to load again on first page
        const offset = 80*(this.page - 1);
        const url = addQueryArgs('wp/v2/lighter_courses', {
            per_page: 80,
            status: "any",
            full_structure: true,
            offset
        });
        lighterFetch<CourseData[]>({ url }).then((res) => {
            const newCourses = res.filter((n) => !this.courses.some((c) => c.id === n.id));
            this.rawCourses.push(...newCourses);
        });
    }
}
