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

export { WooProduct, FluentProduct, MemberProduct, Product };
