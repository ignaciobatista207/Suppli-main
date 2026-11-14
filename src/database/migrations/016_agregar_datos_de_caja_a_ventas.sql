-- src/database/migrations/016_agregar_datos_de_caja_a_ventas.sql
-- total = subtotal - discount_amount (lo calcula el servidor, siempre en centavos).
-- discount_value: el porcentaje (0-100) o el monto fijo que escribió el usuario.
-- payment_method tiene DEFAULT solo para filas previas; la app siempre lo informa.
ALTER TABLE sales
    ADD COLUMN cash_session_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD COLUMN subtotal        DECIMAL(12,2)   NOT NULL DEFAULT 0 AFTER cash_session_id,
    ADD COLUMN discount_type   ENUM('none', 'percent', 'amount') NOT NULL DEFAULT 'none' AFTER subtotal,
    ADD COLUMN discount_value  DECIMAL(10,2)   NOT NULL DEFAULT 0 AFTER discount_type,
    ADD COLUMN discount_amount DECIMAL(12,2)   NOT NULL DEFAULT 0 AFTER discount_value,
    ADD COLUMN payment_method  ENUM('efectivo', 'transferencia', 'tarjeta') NOT NULL DEFAULT 'efectivo' AFTER total,
    ADD COLUMN amount_received DECIMAL(12,2)   NULL AFTER payment_method,
    ADD COLUMN change_given    DECIMAL(12,2)   NULL AFTER amount_received,
    ADD KEY idx_sales_cash_session (cash_session_id),
    ADD CONSTRAINT chk_sales_discount_amount CHECK (discount_amount >= 0),
    ADD CONSTRAINT fk_sales_cash_session
        FOREIGN KEY (cash_session_id) REFERENCES cash_sessions (id)
        ON DELETE RESTRICT;