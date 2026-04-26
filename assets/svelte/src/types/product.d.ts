import type { PostStatus } from "$lib/utils/index.ts";

interface BaseProduct {
  access: CourseAccess;
  autoHide: boolean;
  id: number;
  name: string;
  description: string;
  short_description: string;
  regular_price: string;
  sale_price: string;
  images: {
    alt: string;
    id: number;
    src: string;
  }[];
}

export type WooDownload =
  | [{ id: number; file: string; name: string }]
  | undefined;

interface WooREST {
  attributes: {
    id: number;
    name: string;
    position: number;
    visible: boolean;
    variation: boolean;
    options: string[];
  }[];
  average_rating: string;
  backordered: boolean;
  backorders: "no" | "notify" | "yes";
  backorders_allowed: boolean;
  brands: { id: number; name: string; slug: string }[];
  button_text: string;
  catalog_visibility: "visible" | "catalog" | "search" | "hidden";
  categories: { id: number; name: string; slug: string }[];
  cross_sell_ids: number[];
  date_created: string;
  date_created_gmt: string;
  date_modified: string;
  date_modified_gmt: string;
  date_on_sale_from?: string;
  date_on_sale_from_gmt?: string;
  date_on_sale_to?: string;
  date_on_sale_to_gmt?: string;
  default_attributes: { id: number; name: string; slug: string }[];
  description: string;
  dimensions: { length: string; width: string; height: string };
  download_expiry: number;
  download_limit: number;
  downloadable: boolean;
  downloads: { id: string; name: string; file: string }[];
  external_url: string;
  featured: boolean;
  global_unique_id: string;
  grouped_products: {}[];
  has_options: boolean;
  id: number;
  images: {
    id: number;
    date_created?: string;
    date_created_gmt?: string;
    date_modified?: string;
    date_modified_gmt?: string;
    src: string;
    name: string;
    alt: string;
  }[];
  low_stock_amount?: number;
  manage_stock: boolean;
  menu_order: number;
  meta_data: {}[];
  name: string;
  on_sale: boolean;
  parent_id: number;
  permalink: string;
  post_password: string;
  price: string;
  price_html: string;
  purchasable: true;
  purchase_note: string;
  rating_count: number;
  regular_price: string;
  related_ids: number[];
  reviews_allowed: boolean;
  sale_price: string;
  shipping_class: string;
  shipping_class_id: number;
  shipping_required: boolean;
  shipping_taxable: boolean;
  short_description: string;
  sku: string;
  slug: string;
  sold_individually: boolean;
  status: PostStatus;
  stock_quantity?: number;
  stock_status: "instock";
  tags: {}[];
  tax_class: string;
  tax_status: "taxable";
  total_sales: number;
  type: "simple" | "variation";
  upsell_ids: number[];
  variations: {}[];
  virtual: boolean;
  weight: string;
  _links: { self: string[]; collection: string[] };
}

interface WooProduct extends BaseProduct {
  downloads?: WooDownload;
  stock_quantity?: number;
  menu_order: number;
  sku: string;
  catalog_visibility: "visible" | "catalog" | "search" | "hidden";
}

interface FluentProduct extends BaseProduct {
  uuid: string;
}

interface MemberProduct extends BaseProduct {
  membershipId: string;
  expires: Date;
}

type ProductMap = {
  woocommerce: WooProduct;
  fluentcart: FluentProduct;
  memberpress: MemberProduct;
};

type Product<S extends AvailableStore> = ProductMap[S];

export { WooProduct, WooREST, FluentProduct, MemberProduct, Product };
