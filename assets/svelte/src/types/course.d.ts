
interface CourseData extends WPRestPost {
    type: "course";
    key: string;
    topics?: Topic[];
}

interface TopicData {
    key: string;
    title: string;
    sortOrder: number;
    courseId: number;
    lessons?: LessonData[];
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
