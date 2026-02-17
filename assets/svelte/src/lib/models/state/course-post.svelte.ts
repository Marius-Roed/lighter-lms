import type { CourseData, TopicData } from "$/types/course.d.ts";
import { Topic } from "./course-topic.svelte.ts";
import type { Lesson } from "./course-lesson.svelte.ts";

export class Course {
    readonly id: number;
    readonly key: string;
    readonly slug: string;
    readonly publishDate: string;
    readonly publishDateGMT: string;
    readonly modifiedDate: string;
    readonly modifiedDateGMT: string;
    readonly author: string;
    readonly type: string;

    title = $state("");
    sortOrder = $state(0);
    topics = $state<Topic[]>([]);

    readonly sortedTopics = $derived(
        [...this.topics].sort((a, b) => a.sortOrder - b.sortOrder)
    );

    readonly allLessons: Lesson[] = $derived(
        this.sortedTopics.flatMap((t) => t.sortedLesson)
    );

    constructor(data: CourseData) {
        this.id = data.id;
        this.key = data.key;
        this.slug = data.slug;
        this.publishDate = data.date;
        this.publishDateGMT = data.date_gmt;
        this.modifiedDate = data.modified;
        this.modifiedDateGMT = data.modified_gmt;
        this.author = data.author;
        this.type = data.type;
        this.title = data.title.rendered;
        this.sortOrder = data.menu_order;
        this.topics = data.topics.map((t) => new Topic(t));
    }

    addTopic(data: TopicData): Topic {
        const topic = new Topic(data);
        this.topics = [...this.topics, topic];
        return topic;
    }

    removeTopic(key: string) {
        this.topics = this.topics.filter((t) => t.key !== key);
    }

    moveTopic(fromIndex: number, toIndex: number) {
        const reordered = [...this.sortedTopics];
        const [moved] = reordered.splice(fromIndex, 1);
        reordered.splice(toIndex, 0, moved);
        reordered.forEach((t, i) => t.sortOrder = i);
        this.topics = reordered;
    }

    moveLesson(fromTopicKey: string, toTopicKey: string, fromIndex: number, toIndex: number) {
        const fromTopic = this.topics.find((t) => t.key === fromTopicKey);
        const toTopic = this.topics.find((t) => t.key === toTopicKey);
        if (!fromTopic || !toTopic) return;

        const fromLessons: Lesson[] = [...fromTopic.sortedLesson];
        const [moved] = fromLessons.splice(fromIndex, 1);

        if (fromTopicKey === toTopicKey) {
            fromLessons.splice(toIndex, 0, moved);
            fromLessons.forEach((l, i) => l.sortOrder = i);
            fromTopic.lessons = fromLessons;
        } else {
            fromLessons.forEach((l, i) => l.sortOrder = i);
            fromTopic.lessons = fromLessons;

            const toLessons: Lesson[] = [...toTopic.sortedLesson];
            toLessons.splice(toIndex, 0, moved);
            toLessons.forEach((l, i) => l.sortOrder = i);
            toTopic.lessons = toLessons;
        }
    }

    getLessonByKey(key: string): Lesson | undefined {
        return this.allLessons.find(l => l.key === key);
    }

    toRest() {
        return {
            id: this.id,
            course_key: this.key,
            slug: this.slug,
            date: this.publishDate,
            date_gmt: this.publishDateGMT,
            modified: this.modifiedDate,
            modified_gmt: this.modifiedDateGMT,
            menu_order: this.sortOrder,
            author: this.author,
            type: this.type,
            topics: this.sortedTopics.map((t) => t.toRestData())
        };
    }
}
