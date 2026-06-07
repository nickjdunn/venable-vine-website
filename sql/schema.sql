-- Venable & Vine Website Schema
-- Default admin: admin@venableandvine.com / changeme123 (CHANGE IMMEDIATELY)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS page_sections;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menu_categories;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS gallery_images;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS admin_users;

CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner', 'editor') NOT NULL DEFAULT 'editor',
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    layout_desktop JSON NULL,
    layout_mobile JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE page_sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    section_type VARCHAR(50) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    config JSON NULL,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(8,2) NULL,
    price_note VARCHAR(100) NULL,
    photo_path VARCHAR(500) NULL,
    dietary_tags JSON NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NULL,
    details TEXT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    address VARCHAR(500) NOT NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE gallery_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE site_settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Phase 2: Online ordering (stub tables)
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NULL,
    customer_email VARCHAR(255) NULL,
    customer_phone VARCHAR(50) NULL,
    pickup_event_id INT UNSIGNED NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    total DECIMAL(10,2) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pickup_event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(8,2) NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Default admin is created by public/install.php after import (email: admin@venableandvine.com, password: changeme123)

-- Homepage
INSERT INTO pages (slug, title, is_published) VALUES ('home', 'Home', 1);

INSERT INTO page_sections (page_id, section_type, sort_order, is_active, config) VALUES
(1, 'hero', 0, 1, '{"title":"Freshly Squeezed. Family Made.","subtitle":"Handcrafted lemonades, sweet treats, and honey straight from our hives. Made with love, for you.","background_image":"assets/images/BerriesInhand.webp","logo_image":"assets/images/VenableandVineLogo.webp","cta_text":"Find The Truck Today","cta_link":"/find-us.php"}'),
(1, 'menu_preview', 1, 1, '{"title":"Taste the Sunshine","show_coming_soon":true,"coming_soon_title":"Coming Soon!","coming_soon_text":"Get ready for authentic Agua Frescas, classic Fresas con Crema, and our signature Snowflake Refreshers.","link_to_full_menu":true}'),
(1, 'story', 2, 1, '{"title":"From Our Family to Yours","paragraph1":"Venable & Vine started around our kitchen table, with a love for simple, real ingredients. Our kids loved the fresh-squeezed lemonade we''d make on hot summer days, and we loved the honey from the hives buzzing in our backyard. We thought, why not share this?","paragraph2":"Every drink is muddled right in front of you. The ''Vine'' in our name represents the fresh fruit we use, and ''Venable'' is our family name—a promise of quality and care in everything we serve. We''re proud to be family-owned and operated, and we can''t wait to share a little piece of our home with you.","image":"assets/images/FoodTruckPicture.webp"}'),
(1, 'gallery', 3, 1, '{"title":"A Glimpse of Our Goodness"}'),
(1, 'reviews', 4, 1, '{"title":"What Our Customers Say"}'),
(1, 'find_us', 5, 1, '{"title":"Where to Find Us","text":"For all other news, follow us on Facebook!","show_facebook_button":true,"max_events":5}'),
(1, 'contact', 6, 1, '{"title":"Get In Touch","subtitle":"We would love to hear from you!","show_contact":true,"show_review":true}'),
(1, 'newsletter', 7, 1, '{"title":"Stay in the Loop","subtitle":"Sign up for updates on where we''ll be and what''s new on the menu."}'),
(1, 'social', 8, 1, '{"title":"Follow Us"}');

-- Sample menu
INSERT INTO menu_categories (name, sort_order, is_active) VALUES
('Signature Lemonades', 0, 1),
('Frozen Treats', 1, 1),
('Jams & Honey', 2, 1);

INSERT INTO menu_items (category_id, name, description, price, dietary_tags, is_featured, is_active, sort_order) VALUES
(1, 'Strawberry Basil Lemonade', 'Sweet strawberries and fresh basil muddled to order.', 6.00, '["vegetarian"]', 1, 1, 0),
(1, 'Classic Lemonade', 'Fresh-squeezed lemons with a touch of sweetness.', 5.00, '["vegetarian","vegan"]', 0, 1, 1),
(2, 'Frozen Candied Grapes', 'Sweet frozen grapes with a candy crunch.', 4.00, '["vegetarian","vegan","gluten_free"]', 1, 1, 0),
(3, 'Homemade Wildflower Honey', 'From our backyard hives.', 8.00, '["vegetarian","gluten_free"]', 0, 1, 0);

INSERT INTO gallery_images (file_path, caption, sort_order, is_active) VALUES
('assets/images/LemonadeWithHoney.webp', 'Fresh lemonade', 0, 1),
('assets/images/HoneyandJamandBerries.webp', 'Honey and jam', 1, 1),
('assets/images/ImagesOfFoodOffered.webp', 'Our offerings', 2, 1);

-- Site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Venable & Vine'),
('site_tagline', 'Freshly Squeezed. Family Made.'),
('meta_description', 'Venable & Vine is a family-owned food truck serving fresh muddled lemonades, frozen candied fruits, and homemade jams & honey.'),
('logo_path', 'assets/images/VenableandVineLogo.webp'),
('favicon_path', 'assets/images/JamIcon.webp'),
('footer_text', '© 2024 Venable & Vine | Family Owned & Operated'),
('facebook_url', 'https://www.facebook.com/profile.php?id=61578166736407'),
('instagram_url', ''),
('tiktok_url', ''),
('contact_email', ''),
('google_maps_api_key', ''),
('recaptcha_site_key', ''),
('recaptcha_secret_key', ''),
('mailchimp_api_key', ''),
('mailchimp_list_id', '');
