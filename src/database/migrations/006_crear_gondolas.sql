-- Góndolas de cada repositorio. Toda góndola tiene una categoría (principal o
-- subcategoría) del catálogo global. El nombre es único dentro del repositorio.
CREATE TABLE gondolas (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id BIGINT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED    NOT NULL,
    name          VARCHAR(100)    NOT NULL,
    color         VARCHAR(7)      NOT NULL DEFAULT '#294370',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gondolas_repository_name (repository_id, name),
    KEY idx_gondolas_category (category_id),
    CONSTRAINT fk_gondolas_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gondolas_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;