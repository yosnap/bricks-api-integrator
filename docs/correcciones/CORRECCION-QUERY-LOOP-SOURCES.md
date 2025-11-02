# Corrección Query Loop para Sources

## Problema Identificado

Los Query Types creados desde **Sources** no funcionaban en el Query Loop de Bricks porque:

1. **Sources** se registraban como `snap_source_*` 
2. **Endpoints** se registraban como `snap_ep_*`
3. Pero el handler `run_custom_query_dynamic()` solo procesaba `snap_ep_*`

## Cambios Realizados

### 1. Archivo: `bricks-api-integrator.php`

**Función modificada:** `run_custom_query_dynamic()`

**Antes:**
```php
// Solo procesar nuestros nuevos query types
if (strpos($object_type, 'snap_ep_') !== 0) {
    return $results; // ❌ Sources ignorados
}
```

**Después:**
```php
// ✅ CORREGIR: Procesar tanto endpoints como sources
$is_endpoint = strpos($object_type, 'snap_ep_') === 0;
$is_source = strpos($object_type, 'snap_source_') === 0;

if (!$is_endpoint && !$is_source) {
    return $results;
}
```

### 2. Lógica Diferenciada

**Para Endpoints (`snap_ep_`):**
- Usa la URL directamente del query_type
- No aplica items_path

**Para Sources (`snap_source_`):**
- Obtiene el endpoint desde endpoint_id
- Aplica items_path para extraer arrays anidados
- Usa `extract_nested_items()` para navegar la estructura

### 3. Debug Logging Añadido

Se han añadido logs para depuración cuando `WP_DEBUG` está activo:

```php
error_log('QUERY LOOP DEBUG - object_type: ' . $object_type);
error_log('QUERY LOOP DEBUG - is_endpoint: ' . ($is_endpoint ? 'true' : 'false'));
error_log('QUERY LOOP DEBUG - is_source: ' . ($is_source ? 'true' : 'false'));
```

## Testing

### Para verificar que funciona:

1. **Crear un Source** con items_path (ej: `data.productos`)
2. **Generar tags dinámicos** desde el Source
3. **Usar en Bricks Query Loop:**
   - Ir a Bricks Builder
   - Añadir elemento con Query Loop
   - Seleccionar el Query Type del Source (aparece como "Nombre (Source)")
   - Verificar que aparecen los elementos del array anidado

### Logs esperados en wp-content/debug.log:

```
QUERY LOOP DEBUG - object_type: snap_source_productos
QUERY LOOP DEBUG - is_endpoint: false
QUERY LOOP DEBUG - is_source: true
QUERY LOOP DEBUG - source endpoint_id: 0
QUERY LOOP DEBUG - source_config found: yes
QUERY LOOP DEBUG - items_path: data.productos
QUERY LOOP DEBUG - extracted data count: 5
```

## Resultado

- ✅ **Endpoints** siguen funcionando igual
- ✅ **Sources** ahora funcionan en Query Loop
- ✅ **items_path** se aplica correctamente para arrays anidados
- ✅ **Dynamic tags** funcionan para ambos tipos

## Notas Técnicas

- La función `bricks_api_normalize_slug()` se usa para generar slugs consistentes
- El `endpoint_id` en sources se guarda como `url` en bricks_api_generated_query_types
- Los datos se convierten a pseudo-posts para compatibilidad con Bricks
