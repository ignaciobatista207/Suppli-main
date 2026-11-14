-- src/database/migrations/012_agregar_codigo_a_productos.sql
-- Código interno correlativo por repositorio (1, 2, 3...). Se agrega como NULL
-- para poder rellenarlo en la migración siguiente.
ALTER TABLE products
    ADD COLUMN code INT UNSIGNED NULL AFTER repository_id;

-- Próximo código a entregar en cada repositorio (contador atómico).
ALTER TABLE repositories
    ADD COLUMN next_product_code INT UNSIGNED NOT NULL DEFAULT 1;