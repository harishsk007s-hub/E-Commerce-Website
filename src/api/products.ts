import axios from 'axios';
import { Product } from '../types';

import { PHP_API_URL, API_KEY, UPLOADS_URL } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

const formatImagePath = (img: string) => {
  if (!img) return '/assests/uploads/placeholder.jpg';
  if (img.startsWith('http') || img.startsWith('/') || img.startsWith('data:')) return img;
  // If it's a legacy path with 'assests', return it as is but ensure it starts with /
  if (img.includes('assests/')) {
    return img.startsWith('/') ? img : '/' + img;
  }
  return UPLOADS_URL + img;
};

export const getProducts = async (): Promise<Product[]> => {
  try {
    const response = await api.get('products.php');
    if (response.data && response.data.status === 'success' && Array.isArray(response.data.products)) {
      return response.data.products.map((p: any) => {
        const rawImages = Array.isArray(p.images) ? p.images : (typeof p.images === 'string' ? JSON.parse(p.images) : [p.images]);
        const formattedImages = (rawImages || []).map(formatImagePath);
        
        return {
          ...p,
          category: p.category_name || p.category || 'Papadam', // Map database field or use fallback
          image: formattedImages[0] || '/assests/uploads/placeholder.jpg',
          images: formattedImages
        };
      });
    }
    // If it's just an array (old API style)
    if (Array.isArray(response.data)) {
      return response.data.map((p: any) => ({
        ...p,
        category: p.category_name || p.category || 'Papadam',
        image: formatImagePath(p.image),
        images: Array.isArray(p.images) ? p.images.map(formatImagePath) : [formatImagePath(p.image)]
      }));
    }
    return [];
  } catch (error: any) {
    const serverError = error.response?.data?.error || error.message;
    console.error(`Error fetching products from API (${serverError})`);
    return [];
  }
};

export const syncProducts = async (products: Product[]): Promise<void> => {
  try {
    // Note: Assuming there's a products.php handling sync or similar
    await api.post('products.php?action=sync', { products });
  } catch (error) {
    console.error('Error syncing products to API:', error);
    // Silent fail or handle as needed
  }
};

export const getProductBySlug = async (slug: string): Promise<Product> => {
  try {
    const response = await api.get(`products.php?action=view&slug=${slug}`);
    if (response.data && response.data.product) {
      const p = response.data.product;
      const rawImages = Array.isArray(p.images) ? p.images : (typeof p.images === 'string' ? JSON.parse(p.images) : [p.images]);
      const formattedImages = (rawImages || []).map(formatImagePath);

      return {
        ...p,
        category: p.category_name || p.category || 'Papadam',
        image: formattedImages[0] || '/assests/uploads/placeholder.jpg',
        images: formattedImages
      };
    }
    return response.data;
  } catch (error: any) {
    const serverError = error.response?.data?.error || error.message;
    console.error(`Error fetching product ${slug} from API (${serverError})`);
    throw new Error(serverError);
  }
};

export const getProductById = async (id: number): Promise<Product> => {
  try {
    const response = await api.get(`products.php?action=view&id=${id}`);
    if (response.data && response.data.product) {
      const p = response.data.product;
      const rawImages = Array.isArray(p.images) ? p.images : (typeof p.images === 'string' ? JSON.parse(p.images) : [p.images]);
      const formattedImages = (rawImages || []).map(formatImagePath);

      return {
        ...p,
        category: p.category_name || p.category || 'Papadam',
        image: formattedImages[0] || '/assests/uploads/placeholder.jpg',
        images: formattedImages
      };
    }
    return response.data;
  } catch (error: any) {
    const serverError = error.response?.data?.error || error.message;
    console.error(`Error fetching product ${id} from API (${serverError})`);
    throw new Error(serverError);
  }
};
