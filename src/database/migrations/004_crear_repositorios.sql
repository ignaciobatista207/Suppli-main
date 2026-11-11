-- Repositorios (cada negocio/depósito). Para unirse se usa nombre + contraseña.
-- La contraseña se guarda hasheada (password_hash), nunca en texto plano.
CREATE TABLE repositories (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
<<<<<<< HEAD
    created_by    BIGINT UNSIGNED NOT NULL,
=======
    users_id   BIGINT UNSIGNED NOT NULL,
>>>>>>> 2cfefa2 (...)
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_repositories_name (name),
    CONSTRAINT fk_repositories_created_by
<<<<<<< HEAD
        FOREIGN KEY (created_by) REFERENCES users (id)
=======
        FOREIGN KEY (users.id) REFERENCES users (id)
>>>>>>> 2cfefa2 (...)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
