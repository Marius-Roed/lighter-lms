
interface CourseData extends WPRestPost {
    type: "course";
    key: string;
    topics?: Topic[];
}

interface TopicData {
    key: string;
    title: string;
    sort_order: number;
    course: number;
    lessons?: Lesson[];
}

interface LessonData extends WPRestPost {
    type: "lesson";
    lesson_key: string;
    lesson_type: "video" | "text" | "download" | "live";
    parent_key: string;
    sort_order: number;
}

export type {
    CourseData,
    TopicData,
    LessonData
};
