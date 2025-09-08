CREATE DATABASE IF NOT EXISTS gbarison
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE gbarison;

-- ====================
-- TAB. UTENTI
-- ====================
CREATE TABLE IF NOT EXISTS users (
                                     user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                     email VARCHAR(255) NOT NULL UNIQUE,
                                     password_hash CHAR(64) NOT NULL,
                                     first_name VARCHAR(50) NOT NULL,
                                     last_name  VARCHAR(50) NOT NULL,
                                     tax_code   CHAR(16) NOT NULL,
                                     is_admin   BOOLEAN NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TAB. TIPI DI PRODOTTO
-- ====================
CREATE TABLE IF NOT EXISTS product_types (
                                             product_type_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                             name VARCHAR(50) NOT NULL UNIQUE,
                                             description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TAB. PRODOTTI
-- ====================
CREATE TABLE IF NOT EXISTS products (
                                        product_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                        product_type_id SMALLINT UNSIGNED NOT NULL,
                                        short_name VARCHAR(128) NOT NULL,
                                        name VARCHAR(128) NOT NULL,
                                        manufacturer VARCHAR(100) NOT NULL,
                                        aic_code CHAR(10) NOT NULL UNIQUE,
                                        format ENUM(
                                            'compresse','capsule','sciroppo','gocce','pomata','crema',
                                            'spray','polvere','soluzione','gel','granulato','cerotto','altro'
                                            ) NOT NULL,
                                        price DECIMAL(10,2) NOT NULL CHECK (price > 0),
                                        availability INT UNSIGNED NOT NULL DEFAULT 0,
                                        description TEXT,
                                        image_path VARCHAR(255),
                                        CONSTRAINT fk_products_type FOREIGN KEY (product_type_id)
                                            REFERENCES product_types(product_type_id),

    -- indici senza ALTER
                                        KEY idx_short_name (short_name(50)),
                                        KEY idx_product_type (product_type_id),
                                        KEY idx_availability (availability)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TAB. INDIRIZZI (opzionale)
-- ====================
-- CREATE TABLE IF NOT EXISTS addresses (
--     address_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id INT UNSIGNED NOT NULL,
--     city VARCHAR(100) NOT NULL,
--     province VARCHAR(100) NOT NULL,
--     street VARCHAR(255) NOT NULL,
--     street_number VARCHAR(10) NOT NULL,
--     postal_code VARCHAR(20) NOT NULL,
--     address_type ENUM('shipping','billing') NOT NULL,
--     FOREIGN KEY (user_id) REFERENCES users(user_id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TAB. ORDINI (opzionale)
-- ====================
-- CREATE TABLE IF NOT EXISTS orders (
--     order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--     user_id INT UNSIGNED NOT NULL,
--     shipping_address_id SMALLINT UNSIGNED NOT NULL,
--     payment_method VARCHAR(32) NOT NULL,
--     payment_reference VARCHAR(128) NOT NULL,
--     payment_masked_card VARCHAR(8),
--     created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(user_id),
--     FOREIGN KEY (shipping_address_id) REFERENCES addresses(address_id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================
-- TAB. ARTICOLI ORDINE (opzionale)
-- ====================
-- CREATE TABLE IF NOT EXISTS order_items (
--     order_id INT UNSIGNED NOT NULL,
--     product_id SMALLINT UNSIGNED NOT NULL,
--     quantity INT UNSIGNED NOT NULL CHECK (quantity > 0),
--     PRIMARY KEY (order_id, product_id),
--     FOREIGN KEY (order_id) REFERENCES orders(order_id),
--     FOREIGN KEY (product_id) REFERENCES products(product_id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;