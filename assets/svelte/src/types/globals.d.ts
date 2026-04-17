import type { Post } from "$lib/posts.svelte.js";
import type { Product } from "$lib/settings.svelte.js";
import type { PostStatus } from "$lib/utils/index.ts";
import type { CourseData } from "./course.d.ts";

export {};

declare global {
  type EditStatus = "clean" | "dirty";
  type AvailableStore = "woocommerce" | "fluentcart" | "memberpress";
  type AvailableEditors = "classic-editor" | "gutenberg" | "elementor";
  type Merge<A, B> = A & Omit<B, keyof A>;

  interface Window {
    LighterLMS: LighterLMS;
    lighterCourses: lighterCourses;
    lighterCourse: lighterCourse;
  }

  interface LighterLMS {
    machineId: number;
    nonce: string;
    restUrl: string;
    namespace: string;
    course: LighterCourse;
    settings: {
      builders: {
        plugins: string[];
        active: string;
      };
      stores: {
        plugins: string[];
        active: string;
      };
    };
    lesson?: {
      settings: object;
    };
    user?: {
      courses: Course[];
      owns: {
        course_id: number;
        lessons: number[];
      }[];
    };
    globals: LighterGlobalSettings;
    addAction: Function;
    addFilter: Function;
    doAction: Function;
    applyFilter: Function;
  }
  const LighterLMS: LighterLMS;

  type CourseAccess = {
    [x: string]: (string | number)[];
  };

  type LighterCourse<S extends AvailableStore> = CourseData &
    CourseSettingsData<S>;

  interface CourseSettingsData<S extends AvailableStore> {
    displayFooter: boolean;
    displayHeader: boolean;
    displaySidebar: boolean;
    product?: Product<S>;
    showIcons: boolean;
    showProgress: boolean;
    syncProductImg: boolean;
    tags: [];
    thumbnail: {
      alt: string;
      id: number;
      src: string;
    };
  }

  interface WPPost {
    ID: string;
    comment_count: string;
    comment_status: string;
    guid: string;
    menu_order: string;
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
    post_parent: string;
    post_password: string;
    post_status: PostStatus;
    post_title: string;
    post_type: string;
    to_ping: string;
  }

  interface WPRenderedField {
    rendered: string;
  }

  interface WPRawField {
    raw: string;
  }

  interface WPTextField extends WPRenderedField {
    raw?: string;
  }

  interface WPRestPostReadOnly {
    id: number;
    type: string;
    date_gmt: string;
    modified: string;
    modified_gmt: string;
    _links?: Record<string, unknown[]>;
  }

  interface WPRestPostFields {
    slug?: string;
    status?: PostStatus;
    title?: WPRawField;
    content?: WPRawField;
    excerpt?: WPRawField;
    author?: number;
    date?: string;
    menu_order?: number;
    parent?: number;
    meta?: Record<string, unknown>;
  }

  interface WPRestPost extends WPRestPostReadOnly {
    slug?: string;
    status: PostStatus;
    title: WPTextField;
    content: WPTextField;
    excerpt: WPTextField;
    author?: number;
    date?: string;
    menu_order?: number;
    parent?: number;
    link?: string;
    permalink_template?: string;
    meta?: Record<string, unknown>;
  }

  interface WPRestPostCreateRequired {
    title: string;
    status: PostStatus;
  }

  type WPRestPostCreate = Merge<WPRestPostCreateRequired, WPRestPostFields>;

  interface CourseSettings {
    showIcons: boolean;
    showProgress: boolean;
    status: CourseStatus;
    publishedOn: Date;
    userLocale: string;
    description: string;
    displayHeader: boolean;
    displaySidebar: boolean;
    displayFooter: boolean;
    currency: string;
    store: string;
    tags: Array<T>;
    product: Product;
  }

  interface Media {
    $el: Array;
    activeModes: Object;
    cid: string;
    content: Object;
    el: HTMLElement;
    menu: Object;
    modal: Object;
    options: Object;
    regions: Array;
    router: Object;
    states: Object;
    title: Object;
    toolbar: Object;
    uploader: Object;
    views: Object;
    _selection: Object;
    open: () => void;
    on: (event: string, cb: () => unknown) => unknown;
  }

  interface MediaArgs {
    title: string;
    button: {
      text: string;
    };
    multiple: boolean;
    library: Object;
  }

  interface tag {
    count: number;
    description: string;
    id: number;
    name: string;
    slug: string;
    taxonomy: string;
  }

  interface LighterGlobalSettings {
    baseUrl: string;
    courseTags: tag[];
    currency: string;
    editor: AvailableEditors;
    store: AvailableStore;
    userLocale: string;
  }

  const lighterCourse: lighterCourse;

  const wp: {
    editor: {
      autop: Function;
      getContent: Function;
      getDefaultSettings: Function;
      initialize: Function;
      remove: Function;
      removep: Function;
    };
    media: (t: MediaArgs) => Media & JQuery<HTMLElement>;
  };

  const tinymce: {
    $: Function;
    AddOnManager: Function;
    Annotator: Function;
    DOM: {
      $: Function;
      $$: Function;
      add: Function;
      addClass: Function;
      addStyle: Function;
    };
    Editor: Function;
    Env: Object;
    get: (elementId: string) => any;
  };
}
