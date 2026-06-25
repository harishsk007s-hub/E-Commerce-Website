/**
 * eCommerce Frontend API Bridge
 * Connects the static frontend with the PHP backend API
 */

// Detect if we are on localhost or production
const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const API_BASE = isLocal 
    ? 'http://localhost/ecommerce-backend/api/v1/' 
    : '/api/v1/'; 
const API_KEY = 'sk_live_zenco_123456789'; // Default from database.sql

// Get or generate session ID for guest users
let sessionId = localStorage.getItem('ec_session_id');
if (!sessionId) {
    sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
    localStorage.setItem('ec_session_id', sessionId);
}

const ecommerce = {
    // Current user info (if logged in)
    user: JSON.parse(localStorage.getItem('ec_user')) || null,

    async apiFetch(endpoint, options = {}) {
        const url = API_BASE + endpoint;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-API-KEY': API_KEY
            }
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        return await response.json();
    },

    async getCart() {
        const params = new URLSearchParams({
            session_id: sessionId,
            user_id: this.user ? this.user.id : 0
        });
        return await this.apiFetch(`cart/view?${params.toString()}`);
    },

    async addToCart(productId, quantity = 1, variant = '') {
        return await this.apiFetch('cart/add', {
            method: 'POST',
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                variant: variant,
                session_id: sessionId,
                user_id: this.user ? this.user.id : 0
            })
        });
    },

    async checkout(shippingAddress, paymentMethod = 'razorpay') {
        return await this.apiFetch('orders/checkout', {
            method: 'POST',
            body: JSON.stringify({
                session_id: sessionId,
                user_id: this.user ? this.user.id : 0,
                shipping_address: shippingAddress,
                payment_method: paymentMethod
            })
        });
    },

    async getOrders() {
        if (!this.user) return { error: 'Login required' };
        return await this.apiFetch(`orders/list?user_id=${this.user.id}`);
    },

    // Handle login success from frontend
    loginSuccess(userData) {
        this.user = userData;
        localStorage.setItem('ec_user', JSON.stringify(userData));
        
        // Merge guest cart with user account
        this.apiFetch('cart/merge', {
            method: 'POST',
            body: JSON.stringify({
                session_id: sessionId,
                user_id: userData.id
            })
        });
    },

    logout() {
        this.user = null;
        localStorage.removeItem('ec_user');
        window.location.href = 'login.php';
    }
};

// Auto-initialize cart count if element exists
document.addEventListener('DOMContentLoaded', async () => {
    const cartCountEl = document.getElementById('cart-count');
    if (cartCountEl) {
        const cart = await ecommerce.getCart();
        if (cart.items) {
            const totalItems = cart.items.reduce((sum, item) => sum + item.quantity, 0);
            cartCountEl.textContent = totalItems;
        }
    }
});
