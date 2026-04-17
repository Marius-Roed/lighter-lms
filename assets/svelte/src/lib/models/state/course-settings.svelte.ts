import type { Course } from "./course-post.svelte.ts";
import {
  createCourseProduct,
  type CourseProductInstance,
} from "./course-product.svelte.ts";

export class CourseSettings<S extends AvailableStore> {
  readonly store: S;

  displayFooter = $state(true);
  displayHeader = $state(true);
  displaySidebar = $state(false);

  product = $state<CourseProductInstance<S> | null>(null);

  showIcons = $state(false);
  showProgress = $state(true);
  syncProductImg = $state(true);

  thumbnail = $state<{ id: number; alt: string; src: string }>({
    id: 0,
    alt: "Placeholder",
    src: "https://placehold.co/350/D2C8E1/663399?text=%3F",
  });

  tags = $state([]);

  constructor(course: () => Course, initial?: CourseSettingsData<S>) {
    this.store = LighterLMS.globals.store as S;

    this.displayFooter = initial?.displayFooter ?? true;
    this.displayHeader = initial?.displayHeader ?? true;
    this.displaySidebar = initial?.displaySidebar ?? false;

    this.showIcons = initial?.showIcons ?? false;
    this.showProgress = initial?.showProgress ?? true;
    this.syncProductImg = initial?.syncProductImg ?? true;

    this.tags = initial?.tags ?? [];
    this.thumbnail = initial?.thumbnail ?? {
      id: 0,
      alt: "Placeholder",
      src: "https://placehold.co/350/D2C8E1/663399?text=%3F",
    };

    if (this.store)
      this.product = (
        initial?.product
          ? createCourseProduct(this.store, course, initial?.product)
          : null
      ) as CourseProductInstance<S> | null;
  }

  createEmptyProduct(course: () => Course): void {
    this.product = createCourseProduct(this.store, course);
  }

  getHiddenData(): object {
    return {
      display_header: this.displayHeader,
      display_sidebar: this.displaySidebar,
      display_footer: this.displayFooter,
      show_icons: this.showIcons,
      show_progress: this.showProgress,
      sync_prod_img: this.syncProductImg,
      product: this.product?.getHiddenData(),
      tags: this.tags,
    };
  }
}
