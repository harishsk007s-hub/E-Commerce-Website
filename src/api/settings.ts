import axios from 'axios';
import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

export interface PaymentMethod {
  code: string;
  name: string;
  config: any;
}

export interface AppSettings {
  payment_methods: PaymentMethod[];
  general: any;
  tax: any;
  features: any;
}

export const getAppSettings = async (): Promise<AppSettings> => {
  const response = await api.get('settings.php');
  return response.data;
};
