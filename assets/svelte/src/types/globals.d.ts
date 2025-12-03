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
            }
            addAction: Function;
            addFilter: Function;
            doAction: Function;
            applyFilter: Function;
            course?: Course;
        }
        lighterCourses: lighterCourses;
        lighterCourse: lighterCourse;
    }

    interface Course {
        settings: CourseSettings;
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
