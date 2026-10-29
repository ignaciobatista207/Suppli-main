-- Historial de cambios de stock. quantity lleva signo:
--   entrada  -> positivo
--   salida / venta -> negativo
--   ajuste   -> cualquiera (distinto de 0)
-- sale_id solo se completa cuando el movimiento viene de una venta.
CREATE TABLE stock_movements (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NOT NULL,
    sale_id    BIGINT UNSIGNED NULL,
    type       ENUM('entrada', 'salida', 'venta', 'ajuste') NOT NULL,
    quantity   INT             NOT NULL,
    note       VARCHAR(255)    NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_movements_product_date (product_id, created_at),
    KEY idx_movements_user (user_id),
    KEY idx_movements_sale (sale_id),
    CONSTRAINT chk_movements_quantity CHECK (
        quantity <> 0 AND (
            (type = 'entrada' AND quantity > 0)
            OR (type IN ('salida', 'venta') AND quantity < 0)
            OR type = 'ajuste'
        )
    ),
    CONSTRAINT fk_movements_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_movements_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_movements_sale
        FOREIGN KEY (sale_id) REFERENCES sales (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
