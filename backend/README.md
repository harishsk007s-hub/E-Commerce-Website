# eCommerce Backend (PHP 8+)

A production-ready PHP eCommerce backend with an Admin Panel, Customer Area (Login/Register/Profile), and REST API.

## 🚀 Quick Deployment

1. **XAMPP Setup**:
   - Copy this folder to `C:\xampp\htdocs\ecommerce-backend`.
   - Start Apache and MySQL in XAMPP.
2. **Database Import**:
   - Open [phpMyAdmin](http://localhost/phpmyadmin).
   - Create a database named `database`.
   - Import `database.sql` located in the root folder.
3. **Configuration**:
   - Update `config/database.php` with your DB credentials.
   - Add your Razorpay test keys to `config/razorpay.php`.

## 🔑 Test Logins

| User Type | Username | Password |
|-----------|----------|----------|
| **Super Admin** | `admin` | `admin@2026` |
| **Developer** | `developer` | `developer@2026` |
| **Customer** | `john@example.com` | `12345678` |

## 🛠️ Key Features

- **User System**: Full registration with email verification and Bootstrap 5 profile area.
- **Admin Panel**: Consistent UI with Breadcrumbs, DataTables, and Dark Mode.
- **Dashboard**: Chart.js revenue trends, recent orders (DESC), and quick refresh.
- **Payments**: Integrated Razorpay tracking (Payment ID/Key) and CSV export.
- **Security**: CSRF protection on all forms and failed login lockout (5 attempts).
- **API**: Headless REST API supporting `X-API-KEY` and guest/user cart merging.

## 📡 Frontend Connection

Include the bridge script in your HTML/React project:
```html
<script src="/ecommerce-backend/public/js/ecommerce-frontend.js"></script>
```

**Generate API Key**:
1. Login to **Developer Panel** (`/developer/index.php`).
2. Go to **Clients** → Create New Client.
3. Use the generated `X-API-KEY` in your frontend headers.

## ✅ Test Checklist

- [ ] User register → verify email → login → profile
- [ ] Admin login → clear dashboard → orders DESC
- [ ] Developer login → create client → generate API key
- [ ] Payments page → manual "Mark Completed" works
- [ ] Frontend API: add to cart → view cart → checkout
- [ ] All tables responsive, searchable, paginated
- [ ] No PHP errors, secure sessions/CSRF
