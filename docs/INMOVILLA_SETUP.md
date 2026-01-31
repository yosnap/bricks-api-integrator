# Integración de Inmovilla - Guía de Configuración

## Descripción

Integración directa de la API de Inmovilla con Bricks API Integrator sin necesidad de plugins adicionales.

## Configuración Inicial

El endpoint de Inmovilla se registra automáticamente al activar el plugin con los siguientes parámetros:

- **Nombre:** Inmovilla - API
- **URL:** https://apiweb.inmovilla.com/apiweb/apiweb.php
- **Método:** POST
- **Parámetros dinámicos:**
  - `agencia` → Número de agencia (ej: 2)
  - `password` → Contraseña de API (ej: 82ku9xz2aw3)
  - `idioma` → Código de idioma (1 = Español)
  - `lostipos` → Parámetro requerido por la API
  - `ip` → IP del servidor (generada automáticamente)

## Crear Query Types

Para crear un Query Type que obtenga inmuebles:

1. Ve a **API Integrator > Sources**
2. Haz clic en **"Crear Source"**
3. Configura:
   - **Nombre:** Inmuebles
   - **Endpoint:** Inmovilla - API
   - **Data Path:** `paginacion` (la sección de la respuesta JSON que contiene los datos)
   - **Parámetros dinámicos adicionales:**
     - `tipo` = `paginacion` (tipo de búsqueda)
     - `pos` = `1` (posición inicial)
     - `num_elementos` = `20` (elementos por página)
     - `where` = `` (filtros SQL vacío)
     - `orden` = `precioinmo` (ordenar por precio)

4. Haz clic en **"Guardar"**

## Dynamic Tags Disponibles

Los dynamic tags se generan automáticamente desde los campos retornados por la API:

```
{inmuebles:codigo}
{inmuebles:titulo}
{inmuebles:descripcion}
{inmuebles:precioinmo}
{inmuebles:precioalq}
{inmuebles:ciudad}
{inmuebles:zona}
{inmuebles:superficie}
{inmuebles:habitaciones}
{inmuebles:baños}
{inmuebles:imagen}
```

(Los nombres exactos dependen de lo que retorne la API)

## Uso en Bricks Builder

1. Abre cualquier página en Bricks Builder
2. Añade un **Query Loop**
3. Selecciona el Source (ej: "Inmuebles")
4. Los dynamic tags se generan automáticamente
5. Usa los tags en tus elementos

## Respuesta esperada de la API

```json
{
  "paginacion": [
    {
      "codigo": 123,
      "titulo": "Apartamento en Madrid",
      "precioinmo": 250000,
      "ciudad": "Madrid",
      ...
    }
  ],
  "destacados": [...],
  "tipos": [...],
  "ciudades": [...],
  "zonas": [...]
}
```

## Troubleshooting

### Error "NECESITAMOS RECIBIR LA IP"

Esto significa que el parámetro `ip` no se está enviando correctamente. Verifica que:
1. El parámetro `ip` está en la lista de parámetros dinámicos del endpoint
2. El endpoint tiene un valor por defecto para la IP

### Los dynamic tags no aparecen

1. Verifica que el **Data Path** es correcto (ej: `paginacion`)
2. Prueba el Source con el botón **"Test Source"**
3. Verifica que la respuesta JSON es válida

### La API retorna un error

Verifica:
1. ¿El número de agencia es correcto?
2. ¿La contraseña es correcta?
3. ¿Los parámetros requeridos están en la solicitud?
