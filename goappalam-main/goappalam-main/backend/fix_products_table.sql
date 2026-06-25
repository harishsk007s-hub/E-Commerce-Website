-- Fix products table schema for Goappalam
3→-- This script adds the missing weight-based price columns and updates the main price to VARCHAR to allow ranges.
4→
5→SET FOREIGN_KEY_CHECKS = 0;
6→
7→-- 1. Alter products table
8→ALTER TABLE products 
9→MODIFY COLUMN price VARCHAR(255) NOT NULL DEFAULT '0.00',
10→ADD COLUMN IF NOT EXISTS price_1kg VARCHAR(50) DEFAULT '0.00',
11→ADD COLUMN IF NOT EXISTS price_500g VARCHAR(50) DEFAULT '0.00',
12→ADD COLUMN IF NOT EXISTS price_250g VARCHAR(50) DEFAULT '0.00';
13→
14→-- 2. Ensure categories table exists and has correct columns
15→CREATE TABLE IF NOT EXISTS categories (
16→    id INT AUTO_INCREMENT PRIMARY KEY,
17→    name VARCHAR(255) NOT NULL,
18→    parent_id INT DEFAULT 0,
19→    image VARCHAR(255),
20→    description TEXT,
21→    sort_order INT DEFAULT 0,
22→    seo_title VARCHAR(255),
23→    seo_desc TEXT,
24→    slug VARCHAR(255) UNIQUE
25→);
26→
27→SET FOREIGN_KEY_CHECKS = 1;
28→