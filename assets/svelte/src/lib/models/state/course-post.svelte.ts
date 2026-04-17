import type { CourseData, TopicData } from "$/types/course.d.ts";
import { Topic } from "./course-topic.svelte.ts";
import type { Lesson } from "./course-lesson.svelte.ts";
import type { PostStatus } from "$lib/utils/index.ts";
import { SvelteDate } from "svelte/reactivity";

export class Course {
  readonly id: number;
  readonly key: string;
  readonly dateGMT: string;
  readonly modifiedDate: string;
  readonly modifiedDateGMT: string;
  readonly author: number;
  readonly type: string = "course";

  title = $state("");
  sortOrder = $state(0);
  topics = $state<Topic[]>([]);
  status = $state<PostStatus>("auto-draft");
  date: Date = $state(new SvelteDate());
  slug = $state("");
  excerpt = $state("");
  content = $state("");

  readonly sortedTopics = $derived(
    [...this.topics].sort((a, b) => a.sortOrder - b.sortOrder),
  );

  readonly allLessons: Lesson[] = $derived(
    this.sortedTopics.flatMap((t) => t.sortedLessons),
  );

  constructor(data: CourseData) {
    this.id = data.id;
    this.key = data.key;
    this.slug = data.slug ?? "";
    this.content = data.content?.rendered ?? "";
    this.excerpt = data.excerpt?.rendered ?? "";
    this.date = new SvelteDate(data.date ?? Date.now());
    this.dateGMT = data.date_gmt;
    this.modifiedDate = data.modified;
    this.modifiedDateGMT = data.modified_gmt;
    this.author = data.author ?? 0;
    this.title = data.title.rendered;
    this.sortOrder = data.menu_order ?? 0;
    this.status = data.status;
    this.topics = data.topics?.map((t) => new Topic(t)) ?? [];
  }

  addTopic(data: TopicData): Topic {
    const topic = new Topic(data);
    this.topics = [...this.topics, topic];
    return topic;
  }

  updateTopic(data: Partial<TopicData>): void {
    if (!data.key) return;
    const { key, lessons, ...newData } = data;

    const topic = this.topics.find((t) => t.key === key);
    if (!topic) return;

    Object.assign(topic, newData);

    if (lessons) {
      topic.removeLessons();
      lessons.forEach((l) => {
        topic.addLesson(l);
      });
    }
  }

  removeTopic(key: string) {
    this.topics = this.topics.filter((t) => t.key !== key);
  }

  moveTopic(fromIndex: number, toIndex: number): Record<number, string> {
    let newOrder: string[] = [];
    const reordered = [...this.sortedTopics];
    const [moved] = reordered.splice(fromIndex, 1);
    if (fromIndex > toIndex) {
      toIndex++;
    }
    reordered.splice(toIndex, 0, moved);
    reordered.forEach((t, i) => {
      newOrder[i] = t.key;
      return (t.sortOrder = (i + 1) * 10);
    });
    this.topics = reordered;
    return newOrder;
  }

  moveLesson(
    fromIndex: number,
    toIndex: number,
    fromTopicKey: string,
    toTopicKey?: string,
  ) {
    const fromTopic = this.topics.find((t) => t.key === fromTopicKey);
    const toTopic = this.topics.find((t) => t.key === toTopicKey) ?? fromTopic;
    if (!fromTopic || !toTopic) return;

    const fromLessons = [...fromTopic.sortedLessons];
    const [moved] = fromLessons.splice(fromIndex, 1);

    if (fromTopic.key === toTopic.key) {
      fromLessons.splice(toIndex, 0, moved);
      fromLessons.forEach((l, i) => (l.sortOrder = (i + 1) * 10));
      fromTopic.lessons = fromLessons;
    } else {
      fromLessons.forEach((l, i) => (l.sortOrder = (i + 1) * 10));
      fromTopic.lessons = fromLessons;

      const toLessons: Lesson[] = [...toTopic.sortedLessons];
      toLessons.splice(toIndex, 0, moved);
      toLessons.forEach((l, i) => (l.sortOrder = (i + 1) * 10));
      toTopic.lessons = toLessons;
    }
  }

  getLessonByKey(key: string): Lesson | undefined {
    return this.allLessons.find((l) => l.key === key);
  }

  toRest() {
    return {
      id: this.id,
      key: this.key,
      slug: this.slug,
      content: { rendered: this.content },
      date: this.date.toISOString(),
      date_gmt: this.dateGMT,
      excerpt: { rendered: this.excerpt },
      modified: this.modifiedDate,
      modified_gmt: this.modifiedDateGMT,
      menu_order: this.sortOrder,
      status: this.status,
      author: this.author,
      title: { rendered: this.title },
      type: this.type as CourseData["type"],
      topics: this.sortedTopics.map((t) => t.toRestData()),
    };
  }
}
