# DIAGNÓSTICO ESPECÍFICO - ENDPOINT VEHÍCULOS MOTOR

## Problema Actual

Basándome en las imágenes proporcionadas, has configurado:

- **Endpoint**: "Motor" con URL de API de vehículos
- **Source**: "Vehículos Motor" que usa el endpoint Motor
- **Items Path**: "items" (para extraer datos del array anidado)
- **Autenticación**: Basic Auth configurada

### Síntomas:
1. ✅ **Test del endpoint funciona** - devuelve datos correctamente
2. ❌ **Query Loop está vacío** - no renderiza ningún elemento
3. ❌ **Dynamic Tags no aparecen** - no están disponibles en Bricks
4. ⚠️ **Mensaje de error**: "La API no devolvió datos o el array de ítems está vacío"

## Análisis del Problema

### 1. Estructura de Datos Esperada

Tu API probablemente devuelve algo como:
```json
{
  "status": "success",
  "total": 150,
  "items": [
    {
      "id": 308484,
      "author_id": "126",
      "data-creacio": "2025-06-13 12:28:20",
      "status": "publish",
      "slug": "toyota-land-cruiser-2-8-d-4d-3",
      "titol-anunci": "Toyota Land Cruiser 2.8 D-4D",
      "descripcio-anunci": "...",
      // ... más campos
    }
    // ... más vehículos
  ]
}
```

### 2. Problemas Identificados

#### A. **Extract Nested Items no funciona correctamente**
- La función `extract_nested_items` no está navegando correctamente por el `items_path`
- No maneja adecuadamente arrays anidados

#### B. **Conversión a formato Bricks deficiente**
- Los pseudo-posts no tienen las propiedades necesarias
- Los campos de la API no se mapean correctamente

#### C. **Dynamic Tags no se registran**
- El sistema de tags no está funcionando para sources
- Falta sincronización entre la generación y registro de tags

#### D. **Autenticación básica inconsistente**
- El query loop puede no estar usando la misma autenticación que el test

## Correcciones Específicas Para Tu Caso

### 1. **Corrección del items_path**

El problema principal está en cómo se extrae el array "items". La función actual no maneja correctamente:

```php
// PROBLEMA: No extrae correctamente "items" del objeto response
$data = $response['items']; // Esto falla si la estructura es compleja

// SOLUCIÓN: Navegación robusta por el path
function extract_nested_items($data, $items_path = 'items') {
    // Manejar específicamente tu caso de "items"
    if ($items_path === 'items' && isset($data['items']) && is_array($data['items'])) {
        return $data['items'];
    }
    // ... resto de lógica robusta
}
```

### 2. **Mapeo de campos para Bricks**

Tus campos específicos necesitan mapearse correctamente:

```php
// Campos específicos de tu API que deben estar disponibles en Bricks:
$specific_fields = [
    'titol-anunci',     // Título del anuncio
    'descripcio-anunci', // Descripción
    'data-creacio',     // Fecha de creación
    'author_id',        // ID del autor
    'slug',             // Slug
    'status',           // Estado
    // ... otros campos de tu API
];
```

### 3. **Dynamic Tags específicos**

Los tags que deberías ver en Bricks:
```
{snap_vehiculos_motor_titol_anunci}
{snap_vehiculos_motor_descripcio_anunci}
{snap_vehiculos_motor_data_creacio}
{snap_vehiculos_motor_author_id}
{snap_vehiculos_motor_slug}
{snap_vehiculos_motor_status}
```

## Plan de Implementación Paso a Paso

### PASO 1: Activar Debug Detallado
```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### PASO 2: Aplicar Correcciones Específicas

1. **Corregir extract_nested_items** para manejar tu estructura exacta
2. **Mejorar convert_api_data_for_bricks** para mapear campos específicos
3. **Corregir autenticación** en query loops
4. **Regenerar dynamic tags** con la estructura correcta

### PASO 3: Verificación

Después de aplicar correcciones, deberías ver en `/wp-content/debug.log`:

```
QUERY LOOP DEBUG - Processing source: vehiculos_motor
QUERY LOOP DEBUG - Source endpoint_id: 0
QUERY LOOP DEBUG - Raw data keys: status, total, items
QUERY LOOP DEBUG - Items path: items
QUERY LOOP DEBUG - Extracted data count: 10
QUERY LOOP DEBUG - Final data count before conversion: 10
CONVERT DEBUG - Successfully converted 10 items
DYNAMIC TAGS DEBUG - Added tag: snap_vehiculos_motor_titol_anunci to group: Vehículos Motor (Source)
```

### PASO 4: Configuración en Bricks

1. **Query Loop:**
   - Type: "Vehículos Motor (Source)"
   - Debería mostrar 10 elementos por defecto

2. **Dynamic Tags:**
   - Grupo: "Vehículos Motor (Source)"
   - Tags disponibles para cada campo de tu API

3. **Verificación:**
   - Los elementos aparecen en el loop
   - Los dynamic tags renderizan contenido real
   - No hay errores en console

## Comandos de Verificación

### Ver logs en tiempo real:
```bash
tail -f /wp-content/debug.log | grep "QUERY LOOP\|DYNAMIC TAGS\|EXTRACT\|CONVERT"
```

### Limpiar caché:
```bash
# Desde WordPress admin
Admin → Bricks API Integrator → Clear Cache

# O desde código
delete_transient('api_data_' . md5($endpoint_url));
wp_cache_flush();
```

### Regenerar tags:
```bash
# Desde Sources page
Sources → Edit "Vehículos Motor" → "Crear tags y query types dinámicos"
```

## Resultado Esperado

Después de las correcciones:

1. **Query Loop muestra vehículos** ✅
2. **Dynamic tags aparecen en selector** ✅  
3. **Tags renderizan datos reales** ✅
4. **No hay errores en logs** ✅

### Estructura final en Bricks:
- **Loop Container**: 10 vehículos de la API
- **Tags disponibles**: Todos los campos de cada vehículo
- **Funcionalidad**: Paginación, filtros, etc.

## Si Algo Falla

1. **Restaurar backup**: `cp bricks-api-integrator.php.backup_TIMESTAMP bricks-api-integrator.php`
2. **Revisar logs**: Buscar errores específicos
3. **Verificar autenticación**: Comprobar que Basic Auth funciona en query loop
4. **Probar items_path**: Verificar que "items" es el path correcto

---

**Nota**: Estas correcciones están diseñadas específicamente para tu caso con la API de vehículos y deberían resolver todos los problemas identificados.
