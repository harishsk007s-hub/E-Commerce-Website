import axios from 'axios';

import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

// Interceptor to add Authorization header if token exists in Zustand persist storage
api.interceptors.request.use((config) => {
  const userStorage = localStorage.getItem('user-storage');
  if (userStorage) {
    try {
      const { state } = JSON.parse(userStorage);
      if (state.token) {
        config.headers.Authorization = `Bearer ${state.token}`;
      }
    } catch (e) {
      console.error('Error parsing user-storage from localStorage', e);
    }
  }
  return config;
});

/**
 * Send magic link to user's email for account creation
 */
export const sendMagicLink = async (email: string) => {
  const response = await api.post('auth_send_magic_link.php', { email });
  return response.data;
};

/**
 * Create a new account with token, name, phone and password
 */
export const createAccount = async (payload: { token: string; name: string; phone: string; password: string }) => {
  const response = await api.post('auth_create_account.php', payload);
  return response.data;
};

/**
 * Login with username/email and password
 */
export const login = async (username: string, password: string) => {
  const response = await api.post('auth_login.php', { username, password });
  return response.data;
};

/**
 * Fetch current user profile using the auth token
 */
export const getCurrentUser = async () => {
  const response = await api.get('auth_me.php');
  return response.data;
};

/**
 * Update user profile (name, phone, address, and password)
 */
export const updateProfile = async (payload: { 
  name: string; 
  phone?: string; 
  address_line1?: string;
  address_line2?: string;
  address_line3?: string;
  pincode?: string;
  password?: string;
}) => {
  const response = await api.post('auth_update_profile.php', payload);
  return response.data;
};

// Legacy OTP endpoints (to be removed after full migration)
export const sendOTP = async (email: string) => {
  const response = await api.post('auth.php?action=send_otp', { email });
  return response.data;
};

export const verifyOTP = async (email: string, otp: string) => {
  const response = await api.post('auth.php?action=verify_otp', { email, otp });
  return response.data;
};

/**
 * Forgot password - send reset link to email
 */
export const forgotPassword = async (email: string) => {
  const response = await api.post('auth_forgot_password.php', { email });
  return response.data;
};

/**
 * Reset password with token
 */
export const resetPassword = async (payload: { token: string; new_password: string }) => {
  const response = await api.post('auth_reset_password.php', payload);
  return response.data;
};
