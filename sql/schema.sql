CREATE DATABASE IF NOT EXISTS farmacia_archimede;
USE farmacia_archimede;

-- ====================
-- TAB. UTENTI
-- Gestisce gli utenti registrati della farmacia.
-- ====================
CREATE TABLE users (
                       user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,            -- Identificatore univoco interno
                       email VARCHAR(255) NOT NULL UNIQUE,                        -- Email usata come login (univoca)
                       password_hash CHAR(64) NOT NULL,                           -- Hash della password (es: SHA-256)
                       first_name VARCHAR(50) NOT NULL,                           -- Nome
                       last_name VARCHAR(50) NOT NULL,                            -- Cognome
                       tax_code CHAR(16) NOT NULL,                                -- Codice fiscale (CF)
                       is_admin BOOLEAN NOT NULL DEFAULT 0                        -- 1 = Admin, 0 = Utente normale
);

-- ====================
-- TAB. TIPI DI PRODOTTO
-- Entità separata per ogni tipologia, con nome e descrizione
-- Utile per filtri, pagine categoria, SEO
-- ====================
CREATE TABLE product_types (
                               product_type_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- ID tipo prodotto
                               name VARCHAR(50) NOT NULL UNIQUE,                            -- Nome tipo (es. Farmaci, Integratori)
                               description TEXT                                             -- Descrizione opzionale
);

-- ====================
-- TAB. PRODOTTI
-- Tutti i dati relativi ai prodotti in vendita
-- ====================
CREATE TABLE products (
                          product_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,     -- ID prodotto
                          product_type_id SMALLINT UNSIGNED NOT NULL,                  -- FK verso tipi prodotto
                          short_name VARCHAR(128) NOT NULL,                            -- Nome breve (per preview/lista)
                          name VARCHAR(128) NOT NULL,                                  -- Nome completo
                          manufacturer VARCHAR(100) NOT NULL,                          -- Produttore
                          aic_code CHAR(10) NOT NULL UNIQUE,                           -- Codice AIC (identificativo farmaco)
                          format ENUM(
        'compresse',   -- Compresse
        'capsule',     -- Capsule
        'sciroppo',    -- Sciroppo
        'gocce',       -- Gocce
        'pomata',      -- Pomata
        'crema',       -- Crema
        'spray',       -- Spray
        'polvere',     -- Polvere
        'soluzione',   -- Soluzione
        'gel',
        'granulato',
        'cerotto',     -- Cerotto
        'altro'        -- Altro (in caso serva)
    ) NOT NULL,                                 -- Formato del prodotto
                          price DECIMAL(10,2) NOT NULL CHECK (price > 0),              -- Prezzo > 0
                          availability INT UNSIGNED NOT NULL DEFAULT 0,                -- Disponibilità in magazzino
                          description TEXT,                                            -- Descrizione dettagliata
                          image_path VARCHAR(255),                                     -- Path immagine
                          FOREIGN KEY (product_type_id) REFERENCES product_types(product_type_id)
);

-- ====================
-- TAB. INDIRIZZI
-- Salva indirizzi multipli (spedizione/fatturazione) per ogni utente
-- ====================
CREATE TABLE addresses (
                           address_id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,    -- ID indirizzo
                           user_id INT UNSIGNED NOT NULL,                             -- FK verso utente
                           city VARCHAR(100) NOT NULL,                                -- Città
                           province VARCHAR(100) NOT NULL,                            -- Provincia
                           street VARCHAR(255) NOT NULL,                              -- Via
                           street_number VARCHAR(10) NOT NULL,                        -- Numero civico
                           postal_code VARCHAR(20) NOT NULL,                          -- CAP
                           address_type ENUM('shipping','billing') NOT NULL,          -- Tipologia indirizzo
                           FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ====================
-- TAB. ORDINI
-- Non vengono salvati dati delle carte: solo riferimento provider esterno
-- ====================
CREATE TABLE orders (
                        order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,            -- ID ordine
                        user_id INT UNSIGNED NOT NULL,                              -- FK utente
                        shipping_address_id SMALLINT UNSIGNED NOT NULL,              -- FK indirizzo di spedizione
                        payment_method VARCHAR(32) NOT NULL,                        -- Metodo pagamento (es: 'stripe', 'paypal')
                        payment_reference VARCHAR(128) NOT NULL,                    -- Token transazione restituito dal provider
                        payment_masked_card VARCHAR(8),                             -- Maschera carta es. '****1234' (opzionale, solo ricevuta)
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,     -- Data e ora ordine
                        FOREIGN KEY (user_id) REFERENCES users(user_id),
                        FOREIGN KEY (shipping_address_id) REFERENCES addresses(address_id)
                        -- sia payment_reference che payment_masked_card veranno generati da una classe
);

-- ====================
-- TAB. ARTICOLI ORDINE (order_items)
-- Rappresenta la quantità di ogni prodotto acquistato in ogni ordine
-- ====================
CREATE TABLE order_items (
                             order_id INT UNSIGNED NOT NULL,                             -- FK ordine
                             product_id SMALLINT UNSIGNED NOT NULL,                      -- FK prodotto
                             quantity INT UNSIGNED NOT NULL CHECK (quantity > 0),        -- Quantità acquistata (>0)
                             PRIMARY KEY (order_id, product_id),
                             FOREIGN KEY (order_id) REFERENCES orders(order_id),
                             FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ====================
-- INDICI MIGLIORATIVI PER LE QUERY DI FILTRO
-- ====================
ALTER TABLE products
    ADD INDEX idx_short_name (short_name(50)),
    ADD INDEX idx_product_type (product_type_id),
    ADD INDEX idx_availability (availability);
