-- Repositorios (cada negocio/depósito). Para unirse se usa nombre + contraseña.
-- La contraseña se guarda hasheada (password_hash), nunca en texto plano.
-- created_by referencia users.id (INT UNSIGNED): el tipo debe coincidir exactamente.
-- color: uno de la paleta fija de la app (se valida en el servidor).
CREATE TABLE repositories (
<<<<<<< HEAD
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
=======
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)    NOT NULL,
    password_hash VARCHAR(255)    NOT NULL,
    color         VARCHAR(7)      NOT NULL DEFAULT '#294370',
    created_by    INT UNSIGNED    NOT NULL,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
>>>>>>> cb34803 (corregir migraciones)
    PRIMARY KEY (id),
    UNIQUE KEY uq_repositories_name (name),
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