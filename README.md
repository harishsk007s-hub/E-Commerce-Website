# Go Appalam - Traditional South Indian Papadum Store

A hybrid web application for the Go Appalam store, combining a React frontend, a Node.js Express server for dynamic APIs, and a PHP-based admin panel and transactional backend.

## 📁 Project Structure

Following the audit and reorganization, the project structure is laid out as follows:

```
├── backend/                  # PHP transactional backend & admin panel
│   ├── admin/                # Admin views (dashboard, orders, products)
│   ├── api/v1/               # PHP REST API endpoints (auth, cart, orders)
│   ├── config/               # PHP settings (database, razorpay)
│   └── includes/             # PHP helper scripts, mail, database connections
├── server/                   # Node.js Express API server (TypeScript)
│   ├── index.ts              # Server entry point & DB migration
│   ├── package.json          # Node server package manager
│   └── tsconfig.json         # TypeScript compiler configurations
├── src/                      # React Frontend Source (Vite + TypeScript)
│   ├── components/           # Reusable UI components
│   ├── config/               # Centralized API URLs
│   ├── store/                # Zustand state stores
│   └── pages/                # Page views (Shop, Profile, Checkout, Home)
├── public/                   # Frontend public static assets
├── dist/                     # React production build output (auto-generated)
├── Dockerfile                # Production Docker configuration for Render
├── apache-vhost.conf         # Apache configuration for routing & API proxying
├── start.sh                  # Bootstrapper entrypoint script for Docker
├── package.json              # Main React project package manager
├── tsconfig.json             # Root TypeScript configuration
└── vite.config.ts            # Vite bundler configurations
```

---

## 🚀 Local Development

To run both the React frontend and the Express backend locally:

### Prerequisites

1. **Node.js** (v18+)
2. **PHP** (8.0+) & **Apache/Nginx** (e.g. XAMPP/WAMP) for running the PHP backend.
3. **MySQL Server** (port 3306)

### Installation & Run

1. Clone or extract the project.
2. In the root directory, install React packages:
   ```bash
   npm install
   ```
3. Install Express server packages:
   ```bash
   cd server && npm install && cd ..
   ```
4. Copy `.env.example` to `.env` in the root:
   ```bash
   cp .env.example .env
   ```
   Fill in your local MySQL details.
5. In your local Apache directory (e.g. `C:\xampp\htdocs\`), place or symlink the `backend` folder so the PHP scripts are served.
6. Start MySQL and Apache via XAMPP.
7. Run the development environment:
   ```bash
   npm run dev:all
   ```
   This will start Vite on port `5173` and Express on port `8000` concurrently.

---

## 🌐 Production Deployment on Render

This project is configured to run as a single **Web Service** on Render using a Docker container.

### Render Setup Steps

1. **Create Web Service**:
   - Go to Render and create a new **Web Service**.
   - Connect your GitHub repository.
2. **Configure Environment**:
   - Set the **Runtime** to `Docker`.
3. **Environment Variables**:
   - Add the following variables under the **Environment** tab:
     - `DB_HOST`: Your hosted MySQL database hostname (e.g. Aiven, Clever Cloud).
     - `DB_PORT`: Database port (usually `3306`).
     - `DB_NAME`: Database name.
     - `DB_USER`: Database username.
     - `DB_PASS`: Database password.
     - `X_API_KEY`: API security key (e.g., `sk_live_zenco_123456789`).
     - `APP_ENV`: `production`.
4. **Deploy**:
   - Click **Deploy Web Service**. Render will build the Docker container which builds the React app, compiles TypeScript, exposes the Apache port, and starts the Node server in the background.

---

## 🛠️ Tech Stack

- **Frontend**: React, TypeScript, Vite, Tailwind CSS, Zustand, Lucide Icons, React Leaflet.
- **Node Backend**: Express, TypeScript, pg/mysql2.
- **PHP Backend**: PHP, Apache `.htaccess`, PDO, PHPMailer.
- **Database**: MySQL.
