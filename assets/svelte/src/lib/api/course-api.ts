import { lighterFetch } from "./lighter-fetch.ts";
import type { Lesson } from "$state/course-lesson.svelte.ts";
import type { Topic } from "$state/course-topic.svelte.ts";
import { addQueryArgs, type PostStatus } from "$lib/utils/index.ts";
import type {
  LessonData,
  LessonDataCreate,
  TopicData,
} from "$types/course.d.ts";

type Reorder = { id: number; key: string; sort_order: number };
type UpdateData = { topic_key: string; reorder: Reorder[] };

export class CourseAPI {
  readonly #courseId: number;

  constructor(courseId: number) {
    this.#courseId = courseId;
  }

  // ─── Lesson ───────────────────────────────────────

  createLesson(data: LessonDataCreate) {
    return lighterFetch<LessonData>({
      url: "wp/v2/lighter_lessons",
      method: "POST",
      data,
      parse: true,
    });
  }

  updateLesson(lessonId: number, data: Lesson) {
    return lighterFetch({
      path: `lesson/${lessonId}`,
      method: "PUT",
      data: data.toRestData(),
      parse: true,
    });
  }

  updateLessonTitle(lessonId: number, title: string) {
    return lighterFetch({
      url: `wp/v2/lighter_lessons/${lessonId}`,
      method: "PATCH",
      data: { title },
      parse: true,
    });
  }

  updateLessonStatus(lessonId: number, status: PostStatus) {
    return lighterFetch<LessonData>({
      url: `wp/v2/lighter_lessons/${lessonId}`,
      method: "PATCH",
      data: { status },
      parse: true,
    });
  }

  updateLessonOrder(to: UpdateData, from?: UpdateData) {
    return lighterFetch<TopicData[]>({
      path: "lesson/updateOrder",
      method: "PUT",
      data: { to, from },
      parse: true,
    });
  }

  deleteLesson(lessonKey: number) {
    return lighterFetch({
      url: `wp/v2/lighter_lessons/${lessonKey}`,
      method: "DELETE",
      parse: true,
    });
  }

  // ─── Topic ───────────────────────────────────────

  createTopic(data: Topic) {
    return lighterFetch<TopicData>({
      path: `course/${this.#courseId}/topic`,
      method: "POST",
      data: data.toRestData(),
      parse: true,
    });
  }

  updateTopic(topicKey: string, data: Topic) {
    return lighterFetch({
      path: `course/${this.#courseId}/topic/${topicKey}`,
      method: "PATCH",
      data: data.toRestData(),
      parse: true,
    });
  }

  updateTopicTitle(topicKey: string, title: string) {
    return lighterFetch({
      path: `course/${this.#courseId}/topic/${topicKey}`,
      method: "PATCH",
      data: { title },
      parse: true,
    });
  }

  updateTopicOrder(topicKeys: string[]) {
    return lighterFetch({
      path: `course/${this.#courseId}/topic-order`,
      method: "PUT",
      data: { order: topicKeys },
      parse: true,
    });
  }

  moveTopic(topicKey: string, reordered: Record<number, string>) {
    return lighterFetch<TopicData[]>({
      path: `course/${this.#courseId}/topic-move`,
      method: "PUT",
      data: { topic_key: topicKey, reordered },
      parse: true,
    });
  }

  deleteTopic(topicKey: string) {
    return lighterFetch({
      path: `course/${this.#courseId}/topic/${topicKey}`,
      method: "DELETE",
      parse: true,
    });
  }

  getTopic(topicKey: string, withLessons = false) {
    let url = `course/${this.#courseId}/topic/${topicKey}`;
    return lighterFetch({
      path: addQueryArgs(url, { withLessons }),
      method: "GET",
      parse: true,
    });
  }
}
