import type { CourseData } from "./course.d.ts";

export { };

declare global {
    type PostStatus = "publish" | "pending" | "draft" | "auto-draft" | "future" | "private";
    type EditStatus = "clean" | "dirty";
    type AvailableStore = "woocommerce" | "fluentcart" | "memberpress";

    interface Window {
        LighterLMS: LighterLMS;
        lighterCourses: lighterCourses;
        lighterCourse: lighterCourse;
    }

    interface LighterLMS<S extends AvailableStore = AvailableStore> {
        machineId: number;
        nonce: string;
        restUrl: string;
        namespace: string;
        course: LighterCourse<S>;
        settings: {
            builders: {
                plugins: string[];
                active: string;
            }
            stores: {
                plugins: string[];
                active: string;
            }
        }
        lesson?: {
            settings: object;
        }
        user?: {
            courses: Course[]
            owns: {
                course_id: number,
                lessons: number[]
            }[]
        }
        addAction: Function;
        addFilter: Function;
        doAction: Function;
        applyFilter: Function;
        course?: {
            settings: CourseSettings;
        }
    }
    const LighterLMS: LighterLMS;

    type CourseAccess = {
        [x: string]: (string | number)[];
    };

    interface LighterCourse<S extends AvailableStore> extends CourseData {
        settings: {
            baseUrl: string;
            currency: string;
            description: string;
            displayFooter: boolean;
            displayHeader: boolean;
            displaySidebar: boolean;
            editor: string;
            product?: Product<S>;
            publishedOn: Date;
            showIcons: boolean;
            showProgress: boolean;
            slug: string;
            status: CourseStatus;
            store: S;
            sync_prod_img: boolean;
            thumbnail: {
                alt: string;
                id: number;
                src: string;
            };
            userLocale: string;
        }
    }

    interface WooProduct {
        access: CourseAccess[];
        auto_comp: boolean;
        boolean: auto_hide;
        description: string;
        downloads: Array;
        id: number | string;
        images: Array;
        name: string;
        regular_price: string;
        sale_price: string;
        short_description: string;
        stock_quantity?: number;
        menu_order: number;
        sku: string;
        catalog_visibility: "visible" | "catalog" | "search" | "hidden";
    }

    interface FluentProduct {
        uuid: string;
        price: number;
    }

    interface MemberProduct {
        membershipId: string;
        expires: Date;
    }

    type ProductMap = {
        woocommerce: WooProduct;
        fluentcart: FluentProduct;
        memberpress: MemberProduct;
    }

    type Product<S extends AvailableStore> = ProductMap[S];

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

    interface WPRestPost {
        id: number;
        slug: string;
        status: PostStatus;
        type: string;
        title: { rendered: string; raw?: string };
        content: { rendered: string; raw?: string };
        excerpt: { rendered: string; raw?: string };
        author: string;
        date: string;
        date_gmt: string;
        modified: string;
        modified_gmt: string;
        menu_order: number;
        parent: number;
        meta: Record<string, unknown>;
        _links?: Record<string, unknown[]>;
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
            all: tag[];
            selected: tag[];
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
