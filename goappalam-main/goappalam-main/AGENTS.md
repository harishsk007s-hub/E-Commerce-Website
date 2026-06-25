---
description: Repository Information Overview
alwaysApply: true
---

# Goappalam Repository Information

## Summary
This repository contains a static mirror of the **Goappalam** website (`goappalam.in`), an online e-commerce platform specializing in appalams (papads) and other food products. The site was originally built using **WordPress**, **WooCommerce**, and **Elementor**, and was mirrored using **HTTrack Website Copier**.

## Structure
- **Root**: Contains numerous static HTML files and directories representing site pages (e.g., `shop/`, `product/`, `cart/`, `about-us/`).
- **`wp-content/`**: Contains the `poco` theme, various plugins (e.g., WooCommerce, Elementor, LiteSpeed), and media uploads.
- **`wp-includes/`**: Core WordPress static assets including JavaScript and CSS files.
- **`wp-json/`**: Static exports of the WordPress REST API endpoints.
- **`.zencoder/` & `.zenflow/`**: Contain workflow configurations for the repository.

## Language & Runtime
**Type**: Static Website (HTML/CSS/JS)  
**Source Platform**: WordPress 6.9  
**E-commerce Framework**: WooCommerce 10.1.3  
**Page Builder**: Elementor 3.33.4  
**Theme**: Poco  

## Key Resources
**Main Files**:
- `index.html`: The main landing page.
- `shop/index.html`: The e-commerce shop page.
- `cart/index.html`: The shopping cart page.
- `wp-content/themes/poco/`: The active website theme.

**Configuration Structure**:
- The project follows a standard WordPress directory layout, but all dynamic PHP functionality has been converted to static HTML/JSON files.
- Site configuration is reflected in the static CSS/JS files and the mirrored HTML structure.

## Usage & Operations
Since this is a static mirror, there are no build or installation steps required for the code itself. The repository serves as a snapshot of the live site.

**Integration Points**:
- **Facebook Pixel**: Integrated for tracking (`fbevents.js` references in `index.html`).
- **Google Fonts**: DNS prefetch for font loading.
- **Knit Pay**: Payment integration markers found in the HTML.

## Validation
**Quality Checks**:
- Links can be verified by checking for broken relative paths within the mirrored directory structure.
- Static assets (images, CSS, JS) are located in `wp-content/` and `wp-includes/`.
