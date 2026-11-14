-- src/database/migrations/014_restringir_codigo_de_productos.sql
-- Ya todos los productos tienen código: se vuelve obligatorio y único por repositorio.
ALTER TABLE products
    MODIFY COLUMN code INT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_products_repository_code (repository_id, code);