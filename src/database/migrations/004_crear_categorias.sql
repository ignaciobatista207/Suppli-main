-- Categorías por repositorio. parent_id NULL = categoría principal;
-- si apunta a otra categoría, es una subcategoría.
CREATE TABLE categories (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id BIGINT UNSIGNED NOT NULL,
    parent_id     BIGINT UNSIGNED NULL,
    name          VARCHAR(100)    NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_categories_repository (repository_id),
    KEY idx_categories_parent (parent_id),
    CONSTRAINT fk_categories_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
