-- Catálogo global y fijo de categorías (compartido por todos los repositorios).
-- Jerarquía de dos niveles: parent_id NULL = categoría principal;
-- si apunta a otra fila, es una subcategoría.
-- Los nodos principales sin hijos (ej. Congelados) también se pueden asignar.
CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    parent_id  INT UNSIGNED      NULL,
    name       VARCHAR(100)      NOT NULL,
    slug       VARCHAR(100)      NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_parent_sort (parent_id, sort_order),
    CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;