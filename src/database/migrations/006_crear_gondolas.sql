-- Góndolas de cada repositorio. category_id es opcional y apunta al
-- catálogo global (puede ser una categoría principal o una subcategoría;
-- esa regla se valida en la aplicación).
CREATE TABLE gondolas (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED NULL,
    name          VARCHAR(100) NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gondolas_repository_name (repository_id, name),
    KEY idx_gondolas_category (category_id),
    CONSTRAINT fk_gondolas_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_gondolas_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;