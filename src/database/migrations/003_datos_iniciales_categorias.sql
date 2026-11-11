<<<<<<< HEAD
-- Datos iniciales del catálogo de categorías (9 principales + 21 subcategorías).
-- Se puede ejecutar más de una vez: ON DUPLICATE KEY UPDATE actúa sobre el slug (UNIQUE).
-- No se usan ids fijos: cada subcategoría busca a su padre por slug.

-- Categorías principales
INSERT INTO categories (parent_id, name, slug, sort_order) VALUES
    (NULL, 'Alimentos', 'alimentos', 1),
    (NULL, 'Bebidas', 'bebidas', 2),
    (NULL, 'Frescos', 'frescos', 3),
    (NULL, 'Congelados', 'congelados', 4),
    (NULL, 'Limpieza', 'limpieza', 5),
    (NULL, 'Perfumería', 'perfumeria', 6),
    (NULL, 'Farmacia y salud', 'farmacia-y-salud', 7),
    (NULL, 'Bebés y maternidad', 'bebes-y-maternidad', 8),
    (NULL, 'Bazar, electro y varios', 'bazar-electro-y-varios', 9)
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Alimentos
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Enlatados', 'enlatados', 1 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Arroz, pastas y harinas', 'arroz-pastas-y-harinas', 2 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Condimentos', 'condimentos', 3 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Desayuno/merienda', 'desayuno-merienda', 4 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Bebidas
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bebidas con alcohol', 'bebidas-con-alcohol', 1 FROM categories AS padre WHERE padre.slug = 'bebidas'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bebidas sin alcohol', 'bebidas-sin-alcohol', 2 FROM categories AS padre WHERE padre.slug = 'bebidas'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Frescos
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Lácteos y derivados', 'lacteos-y-derivados', 1 FROM categories AS padre WHERE padre.slug = 'frescos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Embutidos', 'embutidos', 2 FROM categories AS padre WHERE padre.slug = 'frescos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Limpieza
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cuidado de la ropa', 'cuidado-de-la-ropa', 1 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Limpieza de superficies', 'limpieza-de-superficies', 2 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Utensilios de limpieza', 'utensilios-de-limpieza', 3 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Perfumería
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Higiene personal', 'higiene-personal', 1 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cuidado de la piel', 'cuidado-de-la-piel', 2 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cosmética', 'cosmetica', 3 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Higiene femenina y adultos', 'higiene-femenina-y-adultos', 4 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Farmacia y salud
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Medicamentos OTC', 'medicamentos-otc', 1 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Primeros auxilios y botiquín', 'primeros-auxilios-y-botiquin', 2 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Suplementos y nutrición', 'suplementos-y-nutricion', 3 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Bazar, electro y varios
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bazar y cocina', 'bazar-y-cocina', 1 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Electrodomésticos y belleza', 'electrodomesticos-y-belleza', 2 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Mascotas', 'mascotas', 3 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);
=======
-- Datos iniciales del catálogo de categorías (9 principales + 21 subcategorías).
-- Se puede ejecutar más de una vez: ON DUPLICATE KEY UPDATE actúa sobre el slug (UNIQUE).
-- No se usan ids fijos: cada subcategoría busca a su padre por slug.

-- Categorías principales
INSERT INTO categories (parent_id, name, slug, sort_order) VALUES
    (NULL, 'Alimentos', 'alimentos', 1),
    (NULL, 'Bebidas', 'bebidas', 2),
    (NULL, 'Frescos', 'frescos', 3),
    (NULL, 'Congelados', 'congelados', 4),
    (NULL, 'Limpieza', 'limpieza', 5),
    (NULL, 'Perfumería', 'perfumeria', 6),
    (NULL, 'Farmacia y salud', 'farmacia-y-salud', 7),
    (NULL, 'Bebés y maternidad', 'bebes-y-maternidad', 8),
    (NULL, 'Bazar, electro y varios', 'bazar-electro-y-varios', 9)
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Alimentos
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Enlatados', 'enlatados', 1 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Arroz, pastas y harinas', 'arroz-pastas-y-harinas', 2 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Condimentos', 'condimentos', 3 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Desayuno/merienda', 'desayuno-merienda', 4 FROM categories AS padre WHERE padre.slug = 'alimentos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Bebidas
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bebidas con alcohol', 'bebidas-con-alcohol', 1 FROM categories AS padre WHERE padre.slug = 'bebidas'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bebidas sin alcohol', 'bebidas-sin-alcohol', 2 FROM categories AS padre WHERE padre.slug = 'bebidas'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Frescos
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Lácteos y derivados', 'lacteos-y-derivados', 1 FROM categories AS padre WHERE padre.slug = 'frescos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Embutidos', 'embutidos', 2 FROM categories AS padre WHERE padre.slug = 'frescos'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Limpieza
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cuidado de la ropa', 'cuidado-de-la-ropa', 1 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Limpieza de superficies', 'limpieza-de-superficies', 2 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Utensilios de limpieza', 'utensilios-de-limpieza', 3 FROM categories AS padre WHERE padre.slug = 'limpieza'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Perfumería
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Higiene personal', 'higiene-personal', 1 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cuidado de la piel', 'cuidado-de-la-piel', 2 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Cosmética', 'cosmetica', 3 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Higiene femenina y adultos', 'higiene-femenina-y-adultos', 4 FROM categories AS padre WHERE padre.slug = 'perfumeria'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Farmacia y salud
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Medicamentos OTC', 'medicamentos-otc', 1 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Primeros auxilios y botiquín', 'primeros-auxilios-y-botiquin', 2 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Suplementos y nutrición', 'suplementos-y-nutricion', 3 FROM categories AS padre WHERE padre.slug = 'farmacia-y-salud'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

-- Subcategorías de Bazar, electro y varios
INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Bazar y cocina', 'bazar-y-cocina', 1 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Electrodomésticos y belleza', 'electrodomesticos-y-belleza', 2 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);

INSERT INTO categories (parent_id, name, slug, sort_order)
SELECT padre.id, 'Mascotas', 'mascotas', 3 FROM categories AS padre WHERE padre.slug = 'bazar-electro-y-varios'
ON DUPLICATE KEY UPDATE
    categories.parent_id  = VALUES(parent_id),
    categories.name       = VALUES(name),
    categories.sort_order = VALUES(sort_order);
>>>>>>> 2cfefa2 (...)
