
export interface DeleteModal {
    open: boolean;
    type?: "topic" | "lesson";
    id?: number;
    key?: string;
    title: string;
    lessonCount: number;
}
