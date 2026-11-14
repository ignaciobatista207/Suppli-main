-- src/database/migrations/013_rellenar_codigos_de_productos.sql
-- Numera los productos existentes por repositorio, en orden de id.
-- Compatible con MySQL 5.7 (sin funciones de ventana): el código es la cantidad
-- de productos del mismo repositorio con id menor o igual. El GROUP BY obliga a
-- materializar la tabla derivada, así se puede actualizar "products" sin error 1093.
UPDATE products p
INNER JOIN (
    SELECT a.id, COUNT(*) AS rn
    FROM products a
    INNER JOIN products b
        ON b.repository_id = a.repository_id AND b.id <= a.id
    GROUP BY a.id
) t ON t.id = p.id
SET p.code = t.rn;

-- next_product_code = max(code) + 1 (o 1 si el repositorio no tiene productos)
UPDATE repositories r
LEFT JOIN (
    SELECT repository_id, MAX(code) AS max_code
    FROM products
    GROUP BY repository_id
) t ON t.repository_id = r.id
SET r.next_product_code = COALESCE(t.max_code, 0) + 1;