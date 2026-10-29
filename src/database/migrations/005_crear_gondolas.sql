-- Góndolas de cada repositorio. category_id es opcional (tipo de góndola).
CREATE TABLE gondolas (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id BIGINT UNSIGNED NOT NULL,
    category_id   BIGINT UNSIGNED NULL,
    name          VARCHAR(100)    NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_gondolas_repository (repository_id),
    KEY idx_gondolas_category (category_id),
    CONSTRAINT fk_gondolas_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_gondolas_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
