-- src/database/migrations/015_crear_turnos_de_caja.sql
-- Turnos de caja: como máximo UNO abierto por repositorio (se controla en la
-- transacción de apertura, bloqueando la fila del repositorio).
-- opened_by / closed_by → users.id (INT UNSIGNED); repository_id → repositories.id (BIGINT UNSIGNED).
CREATE TABLE cash_sessions (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    repository_id  BIGINT UNSIGNED NOT NULL,
    opened_by      INT UNSIGNED    NOT NULL,
    opened_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_amount DECIMAL(12,2)   NOT NULL,
    status         ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    closed_by      INT UNSIGNED    NULL,
    closed_at      TIMESTAMP       NULL DEFAULT NULL,
    expected_cash  DECIMAL(12,2)   NULL,
    counted_cash   DECIMAL(12,2)   NULL,
    difference     DECIMAL(12,2)   NULL,
    close_note     VARCHAR(255)    NULL,
    PRIMARY KEY (id),
    KEY idx_cash_sessions_repo_status (repository_id, status),
    KEY idx_cash_sessions_repo_opened (repository_id, opened_at),
    KEY idx_cash_sessions_opened_by (opened_by),
    KEY idx_cash_sessions_closed_by (closed_by),
    CONSTRAINT chk_cash_sessions_opening CHECK (opening_amount >= 0),
    CONSTRAINT fk_cash_sessions_repository
        FOREIGN KEY (repository_id) REFERENCES repositories (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_cash_sessions_opened_by
        FOREIGN KEY (opened_by) REFERENCES users (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_cash_sessions_closed_by
        FOREIGN KEY (closed_by) REFERENCES users (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;