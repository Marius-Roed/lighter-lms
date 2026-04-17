import { lighterFetch } from "$lib/api/lighter-fetch.ts";
import { addQueryArgs } from "$lib/utils/index.ts";
import type { Lesson } from "$lib/models/state/course-lesson.svelte.ts";
import type { CourseData, LessonData } from "$types/course.js";
import type { Course } from "./course-post.svelte.ts";

export class MoveModal {
  readonly #initial: () => Course;

  rawCourses = $state<CourseData[]>([]);
  isOpen = $state(false);
  page = $state(1);
  chosenCourse = $state(0);
  openedLesson = $state<Lesson | null>(null);
  openedTopic = $derived(this.openedLesson?.parentKey ?? "");

  constructor(initial: () => Course) {
    this.#initial = initial;
    this.chosenCourse = initial().id;
  }

  initialCourse: CourseData = $derived.by(() => {
    return this.#initial().toRest();
  });

  courses = $derived.by(() =>
    [this.initialCourse, ...this.rawCourses].sort(
      (a, b) =>
        new Date(b.date ?? 0).getTime() - new Date(a.date ?? 0).getTime(),
    ),
  );

  topics = $derived.by(() => {
    if (this.chosenCourse === this.#initial().id) {
      return (
        [...this.#initial().topics]?.sort(
          (a, b) => a.sortOrder - b.sortOrder,
        ) ?? []
      );
    }

    return (
      this.rawCourses
        .find((c) => c.id === this.chosenCourse)
        ?.topics?.sort((a, b) => a.sort_order - b.sort_order) ?? []
    );
  });

  open(lesson: Lesson | null = null) {
    this.isOpen = true;
    this.openedLesson = lesson;
  }

  close() {
    this.isOpen = false;
  }

  loadCourses(): void {
    if (this.page === 1 && this.courses.length >= 2) return; // No need to load again on first page
    const offset = 80 * (this.page - 1);
    const url = addQueryArgs("wp/v2/lighter_courses", {
      per_page: 80,
      status: "any",
      full_structure: true,
      offset,
    });

    lighterFetch<CourseData[]>({ url })
      .then((res) => {
        const newCourses = res.filter(
          (n) => !this.courses.some((c) => c.id === n.id),
        );
        this.rawCourses.push(...newCourses);
      })
      .catch((e) => {
        console.error(e);
      });
  }

  filteredTopics = $derived(
    this.topics
      .map((topic) => {
        const isOpenedTopic = topic.key === this.openedTopic;
        const openedIdx = isOpenedTopic
          ? topic.lessons?.findIndex(
              (l: LessonData) => l.id === this.openedLesson?.id,
            )
          : -1;

        const lessons = topic.lessons?.filter((_, idx: number) => {
          if (!isOpenedTopic) return true;
          return idx !== openedIdx && idx !== openedIdx - 1;
        });

        const showAfterTopic = openedIdx !== 0;

        return { ...topic, title: topic.title, lessons, showAfterTopic };
      })
      .filter((topic) => topic.lessons.length > 0 || topic.showAfterTopic),
  );
}
