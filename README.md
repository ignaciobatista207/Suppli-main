# Suppli

**Descripción:**
{breve explicación del proyecto}

Integrantes:

- Batista, Ignacio | [@ignaciobatista207](https://github.com/ignaciobatista207)


Proyecto institucional **E.E.S.T Nº4 de Berazategui**.

## Elevator's Pitch

- Para {cliente objetivo}
- Quienes {necesidad y/o oportunidad}
- El {nombre del proyecto} es un {categoría del producto}
- Que {beneficio clave, razón para comprarlo}
- Diferente a {otras soluciones existentes, por ejemplo...}
- Nuestro proyecto {declaración de la diferencia}.

## Requisitos

- PHP 8.1 o superior.
- Extensión PDO habilitada.
- MySQL o PostgreSQL según la configuración en el archivo `.env`.


## MVP
- Login de usuarios
- Crear repositorio (el usuario es administrador al crearlo)
- Crear góndolas
- Cargar productos (nombre, precio, fecha de ingreso, vencimiento, cantidad, categoría)
- Sumar/restar cantidades
- Caja registradora con descuento automático de stock

## Extras (si sobra tiempo)
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
