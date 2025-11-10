/**
 * @typedef {"publish" | "pending" | "draft" | "auto-draft" | "future" | "private"} CourseStatus
 */

/**
 * @typedef {Object} Settings
 * @property {boolean} showIcons - Whether the lesson icons should be shown.
 * @property {boolean} showProgress - Whether to show lesson progress
 * @property {CourseStatus} status - The status of the course.
 * @property {Date} publishedOn - The gmt date which the course was published.
 * @property {string} userLocale - The current users locale.
 * @property {string} description - Course description.
 * @property {boolean} displayHeader - Whether to display the theme header.
 * @property {boolean} displaySidebar - Whether to display the theme sidebar.
 * @property {boolean} displayFooter - Whether to display the theme footer.
 * @property {string} currency - The store currency.
 * @property {string} store - The store course is linked to
 * @property {array} tags - The tags on the course
 * @property {Product} product
 */

/**
 * @typedef {Object} Course
 * @property {Settings} settings
 */

/**
 * @typedef {Object} LighterLMS
 * @property {number} machineId
 * @property {string} nonce
 * @property {string} restUrl
 * @property {Settings} settings
 * @property {Course} [course]
 */

const parseProduct = (/** @type {Product} */ raw) => {
    if (isEmpty(raw)) return {};
    const product = {
        ...raw,
        auto_comp: JSON.parse(raw.auto_comp.toString()),
        auto_hide: JSON.parse(raw.auto_hide.toString()),
    };
    return product;
}

/** @type {Partial<Settings>} */
const raw = window.LighterLMS.course?.settings || {};

/** @type {Settings | {}} */
const normalized = raw ? {
    ...raw,
    publishedOn: raw.publishedOn instanceof Date ? raw.publishedOn : new Date(raw.publishedOn ?? Date.now()),
    product: raw.product ? parseProduct(raw.product) : {},
    tags: window.lighterCourse?.tags.selected ?? []
} : {};

export const settings = $state(
/** @type {Settings} */(normalized)
);

/**
 * Returns a locale formated string
 *
 * @param {Date} date - Date to format
 */
export function displayDate(date) {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }

    return date.toLocaleDateString(settings.userLocale || undefined, { day: "2-digit", month: "long", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

/**
 * @param {string} s
 */
export const capitalize = (s) => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase();


/**
 * @param {object} obj
 */
export function isEmpty(obj) {
    for (const prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            return false;
        }
    }
    return true;
}

/**
 * @param {Product|undefined} args 
 * @returns {string}
 */
export function setProduct(args = undefined) {
    const title = document.getElementById('title').value;
    let product = {
        auto_comp: true,
        auto_hide: true,
        id: args?.id ?? "temp",
        name: args?.name ?? "Course: " + title,
        description: args?.description,
        downloads: args?.downloads,
        images: args?.images ?? [{}],
        regular_price: args?.regular_price,
        sale_price: args?.sale_price,
        short_description: args?.short_description,
        stock_quantity: args?.stock_quantity,
        menu_order: args?.menu_order
    }

    settings.product = { ...product };

    return settings.product.name ?? "";
}

/**
 * @typedef {Object} Product
 * @property {Array} [access]
 * @property {boolean} auto_comp
 * @property {boolean} auto_hide
 * @property {string} description
 * @property {Array} downloads
 * @property {number|string} id
 * @property {Array} images
 * @property {string} name
 * @property {string} regular_price
 * @property {string} sale_price
 * @property {string} short_description
 * @property {number|null} stock_quantity
 * @property {number} menu_order
 */
