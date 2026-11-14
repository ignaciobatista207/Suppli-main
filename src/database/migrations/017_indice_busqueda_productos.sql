-- src/database/migrations/017_indice_busqueda_productos.sql
-- Buscador de la caja: filtra por repositorio y activos, y ordena por nombre.
ALTER TABLE products
    ADD KEY idx_products_repo_active_name (repository_id, active, name);