-- Relación muchos a muchos entre usuarios y repositorios.
-- El rol 'admin' se asigna al usuario que crea el repositorio.
CREATE TABLE repository_members (
    repository_id BIGINT UNSIGNED  NOT NULL,
    user_id       BIGINT UNSIGNED  NOT NULL,
    role          ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    joined_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (repository_id, user_id),
    KEY idx_members_user (user_id),
    CONSTRAINT fk_members_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_members_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
