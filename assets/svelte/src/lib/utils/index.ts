import { CourseService } from "$lib/models/service/course-service.svelte.ts";
import { Randflake } from "./randflake.ts";

let instance: CourseService | null = null;

export function getCourseService(): CourseService {
  if (!instance) {
    if (!LighterLMS.course) {
      console.error("CourseService is not allowed here");
    }
    instance = new CourseService(LighterLMS.course);
  }
  return instance;
}

export const addQueryArgs = (
  path: string,
  args: Record<string, string | number | boolean>,
): string => {
  const params = new URLSearchParams();

  for (const [key, val] of Object.entries(args)) {
    params.append(key, String(val));
  }

  const separator = path.includes("?") ? "&" : "?";
  return path + separator + params.toString();
};

/**
 * Returns a locale formated string
 */
export function displayDate(
  date: Date,
  locale: string = LighterLMS.globals.userLocale,
): string {
  if (!(date instanceof Date)) {
    date = new Date(date);
  }

  return date.toLocaleDateString(locale || undefined, {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/**
 * Checks whether an object or array is empty.
 */
export function isEmpty(obj: Object | Array<unknown>): boolean {
  if (Array.isArray(obj)) return obj.length === 0;
  for (const prop in obj) {
    if (obj.hasOwnProperty(prop)) {
      return false;
    }
  }
  return true;
}

export function randflake() {
  return new Randflake();
}

export const POSTSTATUS = [
  "publish",
  "pending",
  "draft",
  "auto-draft",
  "future",
  "private",
  "trash",
] as const;
export type PostStatus = (typeof POSTSTATUS)[number];

export const debouncePromise = (func: Function, delay: number): Function => {
  let timeoutId: number | null = null;
  let rejectFn = null;

  return (...args: any): Promise<unknown> => {
    if (timeoutId) {
      window.clearTimeout(timeoutId);
    }

    const promise = new Promise((res, outerRej) => {
      rejectFn = outerRej;

      timeoutId = window.setTimeout(async () => {
        try {
          const result = await func(...args);
          res(result);
          rejectFn = null;
        } catch (error) {
          outerRej(error);
          rejectFn = null;
        }
        timeoutId = null;
      }, delay);
    });

    return promise;
  };
};
