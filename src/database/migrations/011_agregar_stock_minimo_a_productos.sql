-- Stock mínimo sugerido por producto. 0 = sin alerta de stock bajo.
-- Un producto está en "stock bajo" cuando min_stock > 0 y stock <= min_stock.
ALTER TABLE products
    ADD COLUMN min_stock INT NOT NULL DEFAULT 0 AFTER stock,
    ADD CONSTRAINT chk_products_min_stock CHECK (min_stock >= 0);