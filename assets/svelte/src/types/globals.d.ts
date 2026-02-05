export { };

declare global {
    type CourseStatus = "publish" | "pending" | "draft" | "auto-draft" | "future" | "private";

    interface Window {
        LighterLMS: {
            machineId: number;
            nonce: string;
            restUrl: string;
            course: {
                settings: {
                    description: string;
                    displayFooter: boolean;
                    displayHeader: boolean;
                    displaySidebar: boolean;
                    showIcons: boolean;
                    status: CourseStatus;
                    publishedOn: Date;
                    userLocale: string;
                    store: string;
                    product?: object;
                }
            }
            settings: {
                builders: {
                    plugins: Array<string>;
                    active: string;
                }
                stores: {
                    plugins: Array<string>;
                    active: string;
                }
            }
            user?: {
                courses: Array<Course>
            }
            addAction: Function;
            addFilter: Function;
            doAction: Function;
            applyFilter: Function;
            course?: {
                settings: CourseSettings;
            }
        }
        lighterCourses: lighterCourses;
        lighterCourse: lighterCourse;
    }

    interface Course {
        id: number,
        title: string,
        topics: Array<Topic>
    }

    interface Topic {
        key: string,
        post_id: string,
        sort_order: string,
        title: string,
        lessons: Array<Lesson>
    }

    interface Lesson {
        ID: string,
        comment_count: string,
        comment_status: string,
        guid: string,
        menu_order: string,
        ping_status: string,
        pinged: string,
        post_author: string,
        post_content: string,
        post_content_filtered: string,
        post_date: string,
        post_date_gmt: string,
        post_excerpt: string,
        post_mime_type: string,
        post_modified: string,
        post_modified_gmt: string,
        post_name: string,
        post_parent: string,
        post_password: string,
        post_status: string,
        post_title: string,
        post_type: string,
        to_ping: string,
    }

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

    type CourseAccess = {
        [x: string]: (string | number)[];
    };

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
        id: number;
        name: string;
        slug: string;
        taxonomy: string;
    }

    interface lighterCourse {
        tags: {
            all: Array<tag>;
            selected: Array<tag>;
        }
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
        }
        media: (t: MediaArgs) => Media & JQuery<HTMLElement>;
    }

    const tinymce: {
        $: Function;
        AddOnManager: Function;
        Annotator: Function
        DOM: {
            $: Function;
            $$: Function;
            add: Function;
            addClass: Function;
            addStyle: Function
        }
        Editor: Function;
        Env: Object;
        get: (elementId: string) => any;
    }
}
