import { randflake, type PostStatus } from "$lib/utils/index.ts";
import type { LessonData, LessonDataCreate } from "$types/course.d.ts";

export class Lesson {
  readonly key: string;
  readonly type = "lesson" as const;
  readonly editLink: string;

  slug: string;
  date: string;
  author: number;
  parentKey: string;
  lighterMeta: LessonData["_lighter_meta"];
  id: number;

  title = $state("");
  sortOrder = $state(0);
  status = $state<PostStatus>("auto-draft");
  lessonType = $state<LessonData["lighter_lesson_type"]>("text");
  modified = $state("");

  #original!: LessonData;
  readonly isDirty = $derived(
    this.title !== this.#original.title.rendered ||
      this.status !== this.#original.status ||
      this.lessonType !== this.#original.lighter_lesson_type,
  );

  constructor(data: LessonData) {
    this.#original = data;
    this.id = data.id;
    this.key = data.lighter_lesson_key ?? randflake().generate();
    this.slug = data.slug ?? "";
    this.date = data.date ?? "";
    this.author = data.author ?? 0;
    this.title = data.title.raw ?? data.title.rendered;
    this.status = data.status;
    this.lessonType = "text";
    this.modified = data.modified;
    this.lighterMeta = data._lighter_meta;

    this.editLink = this.getEditLink(data);
    this.parentKey = this.parseParentKey(data);
    this.sortOrder = this.parseSortOrder(data);
  }

  getEditLink(data: LessonData): string {
    const url = new URL(
      data.permalink_template ?? data.link ?? window.location.href,
    );
    url.pathname = "/wp-admin/post.php";
    const action =
      LighterLMS.globals.editor !== "classic-editor"
        ? (LighterLMS.globals.editor ?? "edit")
        : "edit";
    const params = new URLSearchParams({ post: data.id.toString(), action });

    url.search = params.toString();

    return url.toString();
  }

  parseParentKey(data: LessonData): string {
    const meta = data._lighter_meta;
    const courseId = window.LighterLMS?.course?.id;
    if (!meta || !courseId || !meta[courseId]) return "";

    const course = meta[courseId];
    return course.topics?.[0].key ?? "";
  }

  parseSortOrder(data: LessonData): number {
    const meta = data._lighter_meta;
    const courseId = window.LighterLMS?.course?.id;
    if (!meta || !courseId || !meta[courseId]) return 0;

    const course = meta[courseId];
    return course.topics?.[0].sort_order ?? 0;
  }

  setStatus(v: LessonData["status"]): void {
    this.status = v;
  }

  update(data: LessonData | Lesson): void {
    if (data instanceof Lesson) {
      this.id = data.id;
      this.title = data.title;
      this.sortOrder = data.sortOrder;
      this.status = data.status;
      this.lessonType = data.lessonType;
      this.modified = data.modified;
    } else {
      this.id = data.id;
      this.title = data.title?.raw ?? data.title?.rendered ?? this.title;
      this.sortOrder = this.parseSortOrder(data) || this.sortOrder;
      this.status = data.status ?? this.status;
      this.lessonType = data.lighter_lesson_type ?? this.lessonType;
      this.modified = data.modified ?? this.modified;
      this.author = data.author ?? this.author;
      this.slug = data.slug ?? this.slug;
      this.date = data.date ?? this.date;

      this.#original = { ...this.toRestData(), ...data };
    }
  }

  getHiddenData(): object {
    return {
      id: this.id,
      title: this.title,
      author: this.author,
      date: this.date,
      key: this.key,
      slug: this.slug,
      status: this.status,
      lesson_type: this.lessonType,
      lighter_meta: {
        [this.parentKey]: this.sortOrder,
      },
      dirty: this.isDirty,
    };
  }

  toRestData(): LessonData & { dirty: boolean } {
    return {
      id: this.id,
      title: { rendered: this.title },
      status: this.status,
      author: this.author,
      date: this.date,
      modified: this.modified,
      lighter_lesson_key: this.key,
      lighter_lesson_type: this.lessonType,
      type: "lighter_lessons",
      slug: this.slug,
      date_gmt: "",
      modified_gmt: "",
      menu_order: undefined,
      excerpt: { rendered: "" },
      content: { rendered: "" },
      meta: undefined,
      parent: undefined,
      _lighter_meta: this.lighterMeta,
      dirty: this.isDirty,
    };
  }

  serialize(): string {
    return JSON.stringify({
      parentTopic: this.parentKey,
      ...this.toRestData(),
    });
  }

  static deserialize(data: string): Lesson {
    return new Lesson(JSON.parse(data));
  }

  static fromCreate(data: LessonDataCreate): Lesson {
    const toTextField = (field?: WPRawField): WPTextField => ({
      raw: field?.raw ?? "",
      rendered: field?.raw ?? "",
    });

    return new Lesson({
      id: 0,
      date_gmt: "",
      modified: "",
      modified_gmt: "",

      slug: "",
      date: "",
      author: 0,
      menu_order: 0,
      parent: 0,

      ...data,

      title: { rendered: data.title, raw: data.title },
      content: toTextField(data.content),
      excerpt: toTextField(data.excerpt),
    });
  }
}
