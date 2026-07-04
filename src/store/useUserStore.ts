import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { getCurrentUser } from '../api/auth';
import { useCartStore } from './useCartStore';
import { mergeCart } from '../api/cart';

interface User {
  id: number;
  name: string;
  email: string;
  username: string;
  phone?: string;
  role?: string;
  addresses?: {
    shipping?: {
      line1: string;
      line2: string;
      line3: string;
      pincode: string;
    }
  };
}

interface UserState {
  user: User | null;
  token: string | null;
  isLoggedIn: boolean;
  isLoginModalOpen: boolean;
  _hasHydrated: boolean;
  setUser: (user: User | null, token: string | null) => void;
  setLoginModalOpen: (open: boolean) => void;
  setHasHydrated: (state: boolean) => void;
  logout: () => void;
  loadUser: () => Promise<void>;
}

export const useUserStore = create<UserState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isLoggedIn: false,
      isLoginModalOpen: false,
      _hasHydrated: false,
      setUser: async (user, token) => {
        set({ 
          user, 
          token: token !== undefined ? token : get().token, 
          isLoggedIn: !!user 
        });
        if (user) {
          const sessionId = useCartStore.getState().sessionId;
          if (sessionId) {
            try {
              await mergeCart(sessionId, user.id);
            } catch (err) {
              console.error('Failed to merge cart', err);
            }
          }
          useCartStore.getState().loadCart();
        }
      },
      setLoginModalOpen: (open) => set({ isLoginModalOpen: open }),
      setHasHydrated: (state) => set({ _hasHydrated: state }),
      logout: () => {
        set({ user: null, token: null, isLoggedIn: false });
        useCartStore.getState().clearCart();
      },
      loadUser: async () => {
        const { token } = get();
        if (!token) return;
        
        try {
          const data = await getCurrentUser();
          if (data.status === 'success') {
            set({ user: data.user, isLoggedIn: true });
            useCartStore.getState().loadCart();
          } else {
            set({ user: null, token: null, isLoggedIn: false });
          }
        } catch (error: any) {
          if (error.response?.status === 401) {
            set({ user: null, token: null, isLoggedIn: false });
          } else {
            console.error('Failed to load user', error);
          }
        }
      },
    }),
    {
      name: 'user-storage',
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true);
      }
    }
  )
);
