# Suppli

**Descripción:**
Suppli es una aplicación web para gestionar el stock de un comercio y cobrar en caja desde el mismo lugar. Los productos se organizan en repositorios y góndolas, cada venta descuenta el stock automáticamente y cada turno de caja se cierra con el efectivo esperado, el contado y la diferencia.

Integrantes:

- Batista, Ignacio | [@ignaciobatista207](https://github.com/ignaciobatista207)


Proyecto institucional **E.E.S.T Nº4 de Berazategui**.

## Elevator's Pitch

- Para pequeños comercios y autoservicios (almacenes, kioscos, minimercados)
- Quienes necesitan llevar el control de su mercadería y de sus ventas sin depender de planillas ni cuadernos
- Suppli es una aplicación web de gestión de stock con caja registradora
- Que descuenta el stock automáticamente en cada venta, avisa cuando un producto está por agotarse y cierra la caja mostrando si sobra o falta efectivo, para saber siempre qué hay a mano y cuánto se vendió
- Diferente a las planillas de cálculo y a los sistemas que tienen el inventario y la caja por separado
- Nuestro proyecto reúne ambas cosas en un solo lugar, ordenado por repositorios y góndolas, simple de usar desde la computadora o el celular y con permisos de administrador y miembro.

## Requisitos

- PHP 8.1 o superior.
- Extensión PDO habilitada.
- MySQL o PostgreSQL según la configuración en el archivo `.env`.


## MVP
- Login de usuarios
- Crear repositorio (el usuario es administrador al crearlo)
- Crear góndolas
- Cargar productos (nombre, precio, fecha de ingreso, cantidad, categoría)
- Sumar/restar cantidades
- Caja registradora con descuento automático de stock
- Subcategorías
- Unirse a un repositorio con contraseña

## Migraciones

Colocá tus archivos `.sql` en `src/database/migrations/` y ejecuta:

```bash
composer migrate
```

## Licencia

Este proyecto está licenciado bajo la [Licencia MIT](./LICENSE).

---

Generado por la [plantilla](https://github.com/PDI-EEST4/slim-template-2026), a discreción de los integrantes del proyecto.