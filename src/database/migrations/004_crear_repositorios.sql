-- Repositorios (cada negocio o depósito). Para unirse se usa nombre + contraseña.
-- La contraseña se guarda hasheada (password_hash), nunca en texto plano.
CREATE TABLE repositories (
<<<<<<< HEAD
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
<<<<<<< HEAD
    created_by    BIGINT UNSIGNED NOT NULL,
=======
    users_id   BIGINT UNSIGNED NOT NULL,
>>>>>>> 2cfefa2 (...)
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
=======
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_by    INT UNSIGNED NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
>>>>>>> e3aebd6 (cambios en la BD)
    PRIMARY KEY (id),
    UNIQUE KEY uq_repositories_name (name),
    KEY idx_repositories_created_by (created_by),
    CONSTRAINT fk_repositories_created_by
<<<<<<< HEAD
<<<<<<< HEAD
        FOREIGN KEY (created_by) REFERENCES users (id)
=======
        FOREIGN KEY (users.id) REFERENCES users (id)
>>>>>>> 2cfefa2 (...)
=======
        FOREIGN KEY (created_by) REFERENCES users (id)
>>>>>>> e3aebd6 (cambios en la BD)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;