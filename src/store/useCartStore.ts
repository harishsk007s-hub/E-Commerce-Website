import axios from 'axios';
import { create } from 'zustand';
import { Product } from '../types';
import { addToCart, updateCartItem, removeFromCart, getCart, clearCart as apiClearCart } from '../api/cart';
import { useUserStore } from './useUserStore';
import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

interface CartItem extends Product {
  quantity: number;
}

interface Coupon {
  code: string;
  discount_type: 'percentage' | 'fixed';
  discount_value: number;
}

interface CartState {
  items: CartItem[];
  sessionId: string;
  isCartOpen: boolean;
  highlightedProductId: number | null;
  appliedCoupon: Coupon | null;
  shipping: number;
  tax: number;
  setCartOpen: (open: boolean) => void;
  setHighlightedProduct: (productId: number | null) => void;
  addItem: (product: Product, quantity?: number) => Promise<void>;
  removeItem: (productId: number, selectedWeight?: string) => Promise<void>;
  updateQuantity: (productId: number, quantity: number, selectedWeight?: string) => Promise<void>;
  clearCart: () => Promise<void>;
  totalItems: () => number;
  totalPrice: () => number;
  discountAmount: () => number;
  finalTotal: () => number;
  loadCart: (location?: { country?: string; state?: string; city?: string }) => Promise<void>;
  applyCoupon: (code: string) => Promise<string | null>;
  removeCoupon: () => void;
}

// Simple session ID generator
const getSessionId = () => {
  let sessionId = localStorage.getItem('cart_session_id');
  if (!sessionId) {
    sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('cart_session_id', sessionId);
  }
  return sessionId;
};

// Helper to get userId from useUserStore
const getUserId = () => {
  return useUserStore.getState().user?.id;
};

// CHANGE 1 START
const getInitialItems = (): CartItem[] => {
  try {
    // If it's a first-time visit in this session or overall, ensure it's empty
    const isInitialized = localStorage.getItem('goappalam-cart-initialized');
    if (!isInitialized) {
      localStorage.setItem('goappalam-cart-initialized', 'true');
      localStorage.removeItem('goappalam-cart');
      return [];
    }
    const savedItems = localStorage.getItem('goappalam-cart');
    return savedItems ? JSON.parse(savedItems) : [];
  } catch (e) {
    return [];
  }
};

export const useCartStore = create<CartState>((set, get) => ({
  items: getInitialItems(),
  sessionId: getSessionId(),
  isCartOpen: false,
  highlightedProductId: null,
  appliedCoupon: (() => {
    const saved = localStorage.getItem('goappalam-coupon');
    return saved ? JSON.parse(saved) : null;
  })(),
  shipping: 0,
  tax: 0,
  setCartOpen: (open) => set({ isCartOpen: open }),
  setHighlightedProduct: (productId) => set({ highlightedProductId: productId }),
  addItem: async (product, initialQty = 1) => {
    const sessionId = get().sessionId;
    const userId = getUserId();
    
    // Optimistic Update: Add to state first
    set((state) => {
      const existingItem = state.items.find((item) => 
        item.id === product.id && item.selectedWeight === product.selectedWeight
      );
      let newItems;
      if (existingItem) {
        newItems = state.items.map((item) =>
          (item.id === product.id && item.selectedWeight === product.selectedWeight)
            ? { ...item, quantity: item.quantity + initialQty } : item
        );
      } else {
        newItems = [...state.items, { ...product, quantity: initialQty }];
      }
      localStorage.setItem('goappalam-cart', JSON.stringify(newItems));
      return { items: newItems };
    });

    try {
      await addToCart(sessionId, product.id, initialQty, product.selectedWeight || '', userId);
    } catch (error) {
      console.error('Failed to add to cart on server:', error);
    }
  },
  removeItem: async (productId, selectedWeight) => {
    const sessionId = get().sessionId;
    const userId = getUserId();
    try {
      await removeFromCart(sessionId, productId, userId, selectedWeight);
      set((state) => {
        const newItems = state.items.filter((item) => 
          !(item.id === productId && item.selectedWeight === selectedWeight)
        );
        localStorage.setItem('goappalam-cart', JSON.stringify(newItems));
        return { items: newItems };
      });
    } catch (error) {
      console.error('Failed to remove from cart:', error);
    }
  },
  updateQuantity: async (productId, quantity, selectedWeight) => {
    const sessionId = get().sessionId;
    const userId = getUserId();
    const newQuantity = Math.max(0, quantity);
    
    // Optimistic Update
    set((state) => {
      let newItems;
      if (newQuantity === 0) {
        newItems = state.items.filter((item) => 
          !(item.id === productId && item.selectedWeight === selectedWeight)
        );
      } else {
        newItems = state.items.map((item) =>
          (item.id === productId && item.selectedWeight === selectedWeight)
            ? { ...item, quantity: newQuantity } : item
        );
      }
      localStorage.setItem('goappalam-cart', JSON.stringify(newItems));
      return { items: newItems };
    });

    try {
      if (newQuantity === 0) {
        await removeFromCart(sessionId, productId, userId, selectedWeight);
      } else {
        await updateCartItem(sessionId, productId, newQuantity, userId, selectedWeight);
      }
    } catch (error) {
      console.error('Failed to update cart on server:', error);
    }
  },
  clearCart: async () => {
    const sessionId = get().sessionId;
    const userId = getUserId();
    localStorage.removeItem('goappalam-cart');
    localStorage.removeItem('goappalam-cart-initialized');
    localStorage.removeItem('goappalam-coupon');
    set({ items: [], appliedCoupon: null });
    try {
      await apiClearCart(sessionId, userId);
    } catch (error) {
      console.error('Failed to clear cart on server:', error);
    }
  },
  totalItems: () => get().items.reduce((total, item) => total + item.quantity, 0),
  totalPrice: () => {
    return get().items.reduce((total, item) => {
      // Handle both string and number prices
      const priceStr = String(item.price).replace(/[^\d.-]/g, '').split('-')[0];
      const price = parseFloat(priceStr) || 0;
      return total + price * item.quantity;
    }, 0);
  },
  discountAmount: () => {
    const total = get().totalPrice();
    const coupon = get().appliedCoupon;
    if (!coupon) return 0;

    if (coupon.discount_type === 'percentage') {
      return (total * coupon.discount_value) / 100;
    } else {
      return Math.min(coupon.discount_value, total);
    }
  },
  finalTotal: () => {
    return get().totalPrice() - get().discountAmount() + get().shipping + get().tax;
  },
  loadCart: async (location) => {
    const sessionId = get().sessionId;
    const userId = getUserId();
    try {
      const data = await getCart(sessionId, userId, location);
      if (data && data.items) {
        const mappedItems = data.items.map((item: any) => ({
          ...item,
          id: item.id || item.product_id,
          selectedWeight: item.selectedWeight || item.variant || ''
        }));
        set({ 
          items: mappedItems,
          shipping: data.shipping || 0,
          tax: data.tax || 0
        });
        localStorage.setItem('goappalam-cart', JSON.stringify(mappedItems));
      }
    } catch (error) {
      console.error('Failed to load cart from server:', error);
    }
  },
  applyCoupon: async (code) => {
    try {
      const response = await api.post('coupons.php', { code });
      const data = response.data;
      
      if (data.success) {
        set({ appliedCoupon: data.coupon });
        localStorage.setItem('goappalam-coupon', JSON.stringify(data.coupon));
        return null;
      } else {
        return data.error || 'Failed to apply coupon';
      }
    } catch (error: any) {
      console.error('Coupon error:', error);
      return error.response?.data?.error || 'An error occurred while applying the coupon';
    }
  },
  removeCoupon: () => {
    set({ appliedCoupon: null });
    localStorage.removeItem('goappalam-coupon');
  },
}));
// CHANGE 1 END
