export interface Product {
  id: number;
  slug?: string;
  name: string;
  sku?: string;
  price: string;
  price_1kg?: string | number;
  price_500g?: string | number;
  price_250g?: string | number;
  image: string;
  images?: string[];
  category: string;
  description: string;
  variations?: {
    name: string;
    options: string[];
  }[];
  selectedWeight?: string;
}

export type View = 'home' | 'shop' | 'product' | 'cart';

export interface Branding {
  site_title: string;
  logo: string;
  favicon: string;
  primary_color: string;
  primary_font: string;
}
