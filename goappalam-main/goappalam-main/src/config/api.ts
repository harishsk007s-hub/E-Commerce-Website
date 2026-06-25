// Centralized API configuration for the React frontend
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

// Base URL for the PHP API (Backend)
export const PHP_API_URL = import.meta.env.VITE_PHP_API_URL || (isLocal 
    ? 'http://localhost/goappalam-main/backend/api/v1/' 
    : '/backend/api/v1/');

// Base URL for the Node API (Server)
export const NODE_API_URL = isLocal 
    ? 'http://localhost:8000/api/' 
    : '/api/';

// Uploads URL
export const UPLOADS_URL = import.meta.env.VITE_UPLOADS_URL || (isLocal 
    ? 'http://localhost/goappalam-main/backend/uploads/' 
    : '/backend/uploads/');

export const API_KEY = 'sk_live_zenco_123456789';
