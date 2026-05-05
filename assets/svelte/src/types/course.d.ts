interface CourseData extends WPRestPost {
  type: "course";
  key: string;
  settings: CourseSettingsData;
  topics?: TopicData[];
}

interface TopicData {
  key: string;
  title: string;
  sortOrder: number;
  courseId: number;
  lessons?: LessonData[];
}

export type LessonType = "video" | "text" | "download" | "live";

type LighterMeta = Record<
  number,
  {
    course_id: number;
    title: string;
    topics?: { key: string; sort_order: number; title: string }[];
  }
>;

interface LessonData extends WPRestPost {
  type: "lighter_lessons";
  lighter_lesson_key: string;
  lighter_lesson_type: LessonType;
  parentTopic?: string;
  _lighter_meta?: LighterMeta;
}

interface LessonDataCreate extends WPRestPostCreate {
  type: "lighter_lessons";
  lighter_lesson_key: string;
  lighter_lesson_type: LessonType;
  _lighter_meta?: LighterMeta;
}

interface LessonParentCourse {
  course_id: number;
  course_title: string;
  match_type: "course" | "topic";
  topics: Array<{
    ID: number;
    key: string;
    sort_order: number;
    title: string;
    _idx?: number;
  }>;
}

export type {
  CourseData,
  TopicData,
  LessonData,
  LessonDataCreate,
  LessonParentCourse,
};
