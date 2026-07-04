// Centralized API configuration for the React frontend
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

// Dynamic subfolder detection if running in a subdirectory on local Apache
const pathSegments = window.location.pathname.split('/');
const subfolder = pathSegments[1] && pathSegments[1] !== 'dist' && pathSegments[1] !== 'index.html' && !pathSegments[1].includes(':')
  ? '/' + pathSegments[1]
  : '';

// Base URL for the PHP API (Backend)
export const PHP_API_URL = import.meta.env.VITE_PHP_API_URL || (isLocal 
    ? (window.location.port === '5173' ? '/backend/api/v1/' : `${subfolder}/backend/api/v1/`)
    : '/backend/api/v1/');

// Base URL for the Node API (Server)
export const NODE_API_URL = isLocal 
    ? (window.location.port === '5173' ? 'http://localhost:8000/api/' : `${subfolder}/api/`)
    : '/api/';

// Uploads URL
export const UPLOADS_URL = import.meta.env.VITE_UPLOADS_URL || (isLocal 
    ? (window.location.port === '5173' ? '/uploads/' : `${subfolder}/backend/uploads/`)
    : '/backend/uploads/');

export const API_KEY = 'sk_live_zenco_123456789';
