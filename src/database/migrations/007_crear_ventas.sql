-- Cabecera de cada venta de la caja registradora.
CREATE TABLE sales (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id BIGINT UNSIGNED NOT NULL,
    user_id       BIGINT UNSIGNED NOT NULL,
    total         DECIMAL(12,2)   NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sales_repository_date (repository_id, created_at),
    KEY idx_sales_user (user_id),
    CONSTRAINT chk_sales_total CHECK (total >= 0),
    CONSTRAINT fk_sales_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_sales_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
