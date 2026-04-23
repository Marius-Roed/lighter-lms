import type { Product, WooDownload } from "$types/product.js";
import type { Course } from "./course-post.svelte.ts";

class BaseProduct<S extends AvailableStore> {
  readonly store: S;
  readonly #course: () => Course;
  #cleanup: () => void;

  access = $state<CourseAccess>({});
  autoHide = $state(false);
  name = $state("");
  id = $state(0);
  price = $state("");
  salePrice = $state<string | null>(null);
  description = $state("");
  shortDescription = $state("");
  images = $state([
    {
      id: 0,
      alt: "placeholder",
      src: "https://placehold.co/350/D2C8E1/663399?text=%3F",
    },
  ]);
  #prevTopicKeys = new Set<string>();

  constructor(store: S, course: () => Course, initial?: Product<S>) {
    this.store = store;
    this.#course = course;
    this.access = initial?.access ?? {};
    this.autoHide = initial?.autoHide ?? false;
    this.name = initial?.name ?? "";
    this.id = initial?.id ?? 0;
    this.price = initial?.regular_price ?? "0";
    this.salePrice = initial?.sale_price ?? "";
    this.description = initial?.description ?? "";
    this.shortDescription = initial?.short_description ?? "";
    this.images = initial?.images ?? [
      {
        alt: "placeholder",
        id: 0,
        src: "https://placehold.co/350/D2C8E1/663399?text=%3F",
      },
    ];
    this.#prevTopicKeys = new Set(Object.keys(this.access));

    this.#cleanup = $effect.root(() => {
      $effect(() => {
        const course = this.#course();
        const nextKeys = new Set(course.topics?.map((t) => t.key) ?? []);

        // console.log("effect ran", { nextKeys, prevTopic: this.#prevTopicKeys });

        for (const key of nextKeys) {
          if (!this.#prevTopicKeys.has(key)) {
            this.access[key] = [];
          }
        }

        for (const key of this.#prevTopicKeys) {
          if (!nextKeys.has(key)) {
            delete this.access[key];
          }
        }

        this.#prevTopicKeys = nextKeys;
      });
    });
  }

  getHiddenData(): Object {
    return {
      access: this.access,
      auto_hide: this.autoHide,
      name: this.name,
      id: this.id,
      regular_price: this.price,
      sale_price: this.salePrice,
      description: this.description,
      short_description: this.shortDescription,
      images: this.images,
    };
  }

  destroy() {
    this.#cleanup();
  }
}

class WooCommerceProduct extends BaseProduct<"woocommerce"> {
  downloads = $state<WooDownload>();
  stockQuantity = $state<number | null>(null);
  menuOrder = $state(0);
  sku = $state("");
  catalogVisibility: "visible" | "catalog" | "search" | "hidden";

  constructor(course: () => Course, initial?: Product<"woocommerce">) {
    super("woocommerce", course, initial);

    this.downloads = initial?.downloads;
    this.menuOrder = initial?.menu_order ?? 0;
    this.catalogVisibility = $state(initial?.catalog_visibility ?? "visible");
    this.sku = initial?.sku ?? "";
    this.stockQuantity = initial?.stock_quantity ?? null;
  }

  getHiddenData(): Object {
    const obj = super.getHiddenData();
    return {
      ...obj,
      downloads: this.downloads,
      menu_order: this.menuOrder,
      sku: this.sku,
      catalog_visibility: this.catalogVisibility,
      stock_quantity: this.stockQuantity,
    };
  }
}

class FluentCartProduct extends BaseProduct<"fluentcart"> {
  constructor(course: () => Course, initial?: Product<"fluentcart">) {
    super("fluentcart", course, initial);
  }
}

class MemberPressProduct extends BaseProduct<"memberpress"> {
  constructor(course: () => Course, initial?: Product<"memberpress">) {
    super("memberpress", course, initial);
  }
}

export type CourseProductInstance<S extends AvailableStore> =
  S extends "woocommerce"
    ? WooCommerceProduct
    : S extends "fluentcart"
      ? FluentCartProduct
      : MemberPressProduct;

export function createCourseProduct<S extends AvailableStore>(
  store: S,
  course: () => Course,
  initial?: Product<S>,
): CourseProductInstance<S> {
  const products = {
    woocommerce: () =>
      new WooCommerceProduct(course, initial as Product<"woocommerce">),
    fluentcart: () =>
      new FluentCartProduct(course, initial as Product<"fluentcart">),
    memberpress: () =>
      new MemberPressProduct(course, initial as Product<"memberpress">),
  };

  return products[store]() as CourseProductInstance<S>;
}
