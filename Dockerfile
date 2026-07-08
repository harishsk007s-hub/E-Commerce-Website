# ==========================================
# STAGE 1: Build the React frontend
# ==========================================
FROM node:18-alpine AS frontend-builder
WORKDIR /app
COPY package*.json tsconfig*.json vite.config.ts tailwind.config.js postcss.config.js index.html ./
COPY src/ ./src
COPY public/ ./public
RUN npm install
RUN npm run build

# ==========================================
# STAGE 2: Build the Node Express backend
# ==========================================
FROM node:18-alpine AS backend-builder
WORKDIR /app

COPY server/package*.json ./
RUN npm install

COPY server/ ./

RUN npm run build

# ==========================================
# STAGE 3: Final Production Image (PHP + Apache + Node.js)
# ==========================================
FROM php:8.2-apache

# Install Node.js, npm, and system packages
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules required for rewrites and reverse proxies
# Fix Apache MPM conflict and enable required modules
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true
RUN a2enmod mpm_prefork
RUN a2enmod rewrite proxy proxy_http headers

# Copy custom Apache virtual host configuration
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set up working directory in Apache DocumentRoot
WORKDIR /var/www/html

# Copy built React frontend to Apache public directory
COPY --from=frontend-builder /app/dist/ ./

# Copy built Node Express backend
COPY --from=backend-builder /app/ ./server

# Copy PHP backend
COPY backend/ ./backend

# Copy custom .htaccess and package files
COPY .htaccess ./
COPY package*.json ./

# Create uploads directory and set permissions
RUN mkdir -p backend/uploads && chmod -R 777 backend/uploads

# Expose HTTP port (Apache)
EXPOSE 80

# Copy startup script and make it executable
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Run startup script
CMD ["/start.sh"]
