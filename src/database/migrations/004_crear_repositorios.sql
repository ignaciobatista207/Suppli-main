-- src/database/migrations/004_crear_repositorios.sql
CREATE TABLE repositories (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    color         VARCHAR(7)      NOT NULL DEFAULT '#294370',
    created_by    INT UNSIGNED    NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_repositories_name (name),
    CONSTRAINT fk_repositories_created_by
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;