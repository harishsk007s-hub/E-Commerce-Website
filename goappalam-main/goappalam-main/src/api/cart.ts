import axios from 'axios';

import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

export const getCart = async (sessionId: string, userId?: number, location?: { country?: string; state?: string; city?: string }) => {
  let url = `cart.php?action=view&session_id=${sessionId}${userId ? `&user_id=${userId}` : ''}`;
  if (location) {
    if (location.country) url += `&country=${encodeURIComponent(location.country)}`;
    if (location.state) url += `&state=${encodeURIComponent(location.state)}`;
    if (location.city) url += `&city=${encodeURIComponent(location.city)}`;
  }
  const response = await api.get(url);
  return response.data;
};

export const addToCart = async (sessionId: string, productId: number, quantity: number, variant: string = '', userId?: number) => {
  const response = await api.post('cart.php?action=add', {
    session_id: sessionId,
    user_id: userId,
    product_id: productId,
    quantity,
    variant,
  });
  return response.data;
};

export const updateCartItem = async (sessionId: string, productId: number, quantity: number, userId?: number, variant?: string) => {
  const response = await api.post('cart.php?action=update', {
    session_id: sessionId,
    user_id: userId,
    product_id: productId,
    quantity,
    variant,
  });
  return response.data;
};

export const removeFromCart = async (sessionId: string, productId: number, userId?: number, variant?: string) => {
  const response = await api.post('cart.php?action=remove', {
    session_id: sessionId,
    user_id: userId,
    product_id: productId,
    variant,
  });
  return response.data;
};

export const clearCart = async (sessionId: string, userId?: number) => {
  const response = await api.post('cart.php?action=clear', {
    session_id: sessionId,
    user_id: userId,
  });
  return response.data;
};

export const mergeCart = async (sessionId: string, userId: number) => {
  const response = await api.post('cart.php?action=merge', {
    session_id: sessionId,
    user_id: userId,
  });
  return response.data;
};
