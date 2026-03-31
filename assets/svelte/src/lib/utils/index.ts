import { createContext } from 'svelte';
import type { CourseService } from '$lib/models/service/course-service.svelte.ts';
import { Randflake } from './randflake.ts';

export const [getCourseService, setCourseService] = createContext<CourseService>();

export const addQueryArgs = (path: string, args: Record<string, string | number | boolean>): string => {
    const params = new URLSearchParams();

    for (const [key, val] of Object.entries(args)) {
        params.append(key, String(val));
    }

    const separator = path.includes("?") ? "&" : "?";
    return path + separator + params.toString();
}

export function randflake() {
    return new Randflake();
}

export const POSTSTATUS = ["publish", "pending", "draft", "auto-draft", "future", "private", "trash"] as const;
export type PostStatus = (typeof POSTSTATUS)[number];
