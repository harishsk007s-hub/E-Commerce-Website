import axios from 'axios';

import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

export interface CheckoutData {
  session_id: string;
  customer_id?: number;
  shipping_address: {
    country: string;
    state: string;
    city: string;
    address: string;
  };
  payment_method: string;
  coupon_code?: string;
}

export const processCheckout = async (checkoutData: CheckoutData) => {
  const response = await api.post('orders.php?action=checkout', checkoutData);
  return response.data;
};

export const verifyPayment = async (paymentData: any) => {
  const response = await api.post('payment-verify.php', paymentData);
  return response.data;
};

export const getOrderDetails = async (orderId: number) => {
  const response = await api.get(`orders.php?action=view&id=${orderId}`);
  return response.data;
};

export const getUserOrders = async (userId: number) => {
  const response = await api.get(`orders.php?action=list&user_id=${userId}`);
  return response.data;
};
