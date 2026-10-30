-- Productos. "stock" es la cantidad actual; el historial vive en stock_movements.
-- No se borran productos: se desactivan con active = 0 para no perder
-- el historial de movimientos y ventas.
CREATE TABLE products (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id BIGINT UNSIGNED NOT NULL,
    gondola_id    BIGINT UNSIGNED NOT NULL,
    category_id   BIGINT UNSIGNED NULL,
    name          VARCHAR(150)    NOT NULL,
    price         DECIMAL(10,2)   NOT NULL,
    stock         INT             NOT NULL DEFAULT 0,
    entry_date    DATE            NOT NULL,
    expiry_date   DATE            NULL,
    active        TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_repository_active (repository_id, active),
    KEY idx_products_gondola (gondola_id),
    KEY idx_products_category (category_id),
    KEY idx_products_expiry (repository_id, expiry_date),
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_stock CHECK (stock >= 0),
    CONSTRAINT fk_products_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_products_gondola
        FOREIGN KEY (gondola_id) REFERENCES gondolas (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
