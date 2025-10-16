export { };

declare global {
    type CourseStatus = "publish" | "pending" | "draft";

    interface Window {
        LighterLMS: {
            machineId: number;
            nonce: string;
            restUrl: string;
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
            addAction: Function;
            addFilter: Function;
            doAction: Function;
            applyFilter: Function;
        }
        lighterCourses: lighterCourses;
        lighterCourse: lighterCourse;
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
    }

    interface MediaArgs {
        title: string;
        button: {
            text: string;
        };
        multiple: boolean;
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
        media: (t: MediaArgs) => {
            open: () => Media;
        };
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
