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
    readonly author: number;
    readonly type: string;

    title = $state("");
    sortOrder = $state(0);
    topics = $state<Topic[]>([]);

    readonly sortedTopics = $derived(
        [...this.topics].sort((a, b) => a.sortOrder - b.sortOrder)
    );

    readonly allLessons: Lesson[] = $derived(
        this.sortedTopics.flatMap((t) => t.sortedLessons)
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
        let newOrder = [];
        const reordered = [...this.sortedTopics];
        const [moved] = reordered.splice(fromIndex, 1);
        if (fromIndex > toIndex) {
            toIndex++;
        }
        reordered.splice(toIndex, 0, moved);
        reordered.forEach((t, i) => {
            newOrder[i] = t.key;
            return t.sortOrder = (i + 1) * 10
        });
        this.topics = reordered;
        return newOrder;
    }

    moveLesson(fromTopicKey: string, toTopicKey: string, fromIndex: number, toIndex: number) {
        const fromTopic = this.topics.find((t) => t.key === fromTopicKey);
        const toTopic = this.topics.find((t) => t.key === toTopicKey);
        if (!fromTopic || !toTopic) return;

        const fromLessons: Lesson[] = [...fromTopic.sortedLessons];
        const [moved] = fromLessons.splice(fromIndex, 1);

        if (fromTopicKey === toTopicKey) {
            fromLessons.splice(toIndex, 0, moved);
            fromLessons.forEach((l, i) => l.sortOrder = i);
            fromTopic.lessons = fromLessons;
        } else {
            fromLessons.forEach((l, i) => l.sortOrder = i);
            fromTopic.lessons = fromLessons;

            const toLessons: Lesson[] = [...toTopic.sortedLessons];
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
