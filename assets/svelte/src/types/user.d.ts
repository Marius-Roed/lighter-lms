import type { PostStatus } from "$lib/utils/index.ts";

interface UserCourse {
  id: number;
  image: {
    src?: string;
  };
  title: string;
  topics?: UserTopic[];
  hidden?: boolean;
  open?: boolean;
}

interface UserTopic {
  key: string;
  title: string;
  sortOrder: number;
  courseId: number;
  lessons?: UserLesson[];
  group?: number[];
}

interface UserLesson {
  ID: number;
  comment_count: string;
  comment_status: string;
  filter: "raw" | "edit";
  guid: string;
  menu_order: number;
  ping_status: string;
  pinged: string;
  post_author: string;
  post_content: string;
  post_content_filtered: string;
  post_date: string;
  post_date_gmt: string;
  post_excerpt: string;
  post_mime_type: string;
  post_modified: string;
  post_modified_gmt: string;
  post_name: string;
  post_parent: number;
  post_password: string;
  post_status: PostStatus;
  post_title: string;
  post_type: "lighter_lessons";
  to_ping: string;
}

export type { UserCourse, UserTopic };
