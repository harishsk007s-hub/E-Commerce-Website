import axios from 'axios';
import { PHP_API_URL, API_KEY } from '../config/api';

const api = axios.create({
  baseURL: PHP_API_URL,
  headers: {
    'X-API-KEY': API_KEY,
    'Content-Type': 'application/json',
  },
});

export interface ContactFormData {
  name: string;
  email: string;
  subject: string;
  comment: string;
}

export const submitContactForm = async (formData: ContactFormData) => {
  try {
    const response = await api.post('contact.php', formData);
    return response.data;
  } catch (error: any) {
    const serverError = error.response?.data?.error || error.message;
    throw new Error(serverError);
  }
};
