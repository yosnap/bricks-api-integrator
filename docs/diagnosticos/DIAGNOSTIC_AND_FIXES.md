# Diagnóstico y Correcciones - Bricks API Integrator

## Problemas Identificados

### 1. **Query Loop no renderiza datos**
- El array anidado no se está extrayendo correctamente
- La conversión a formato Bricks tiene problemas
- Los pseudo-posts no tienen la estructura correcta

### 2. **Tags dinámicos no aparecen**
- Problema en el registro de dynamic tags
- Inconsistencias en los prefijos
- Falta sincronización entre sources y endpoints

### 3. **Disconnection entre Test y Query Loop**
- El test usa una ruta de código diferente
- El query loop usa otra implementación
- Problemas de caché y sincronización

## Correcciones Requeridas

### A. Corregir extracción de items_path en Query Loop

**Archivo:** `bricks-api-integrator.php`
**Línea:** ~680 (función `run_custom_query_dynamic`)

**Problema:** El `items_path` no se está aplicando correctamente para sources.

**Solución:** Corregir la lógica de extracción:

```php
// ANTES (línea ~680)
if ($source_config && !empty($source_config['items_path'])) {
    $data = $this->extract_nested_items($raw_data, $source_config['items_path']);
}

// DESPUÉS
if ($source_config && !empty($source_config['items_path'])) {
    $data = $this->extract_nested_items($raw_data, $source_config['items_path']);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('QUERY LOOP DEBUG - items_path applied: ' . $source_config['items_path']);
        error_log('QUERY LOOP DEBUG - extracted data: ' . print_r($data, true));
    }
} else {
    $data = $raw_data;
}

// Asegurar que tenemos un array de items
if (!empty($data) && !is_array($data)) {
    $data = [$data];
} elseif (empty($data)) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('QUERY LOOP DEBUG - No data after extraction');
    }
    return $results;
}
```

### B. Mejorar función extract_nested_items

**Archivo:** `bricks-api-integrator.php`
**Línea:** ~715 (función `extract_nested_items`)

**Solución:** Hacer más robusta la extracción:

```php
private function extract_nested_items($data, $items_path) {
    if (empty($items_path) || empty($data)) {
        return $data;
    }
    
    $path_parts = explode('.', $items_path);
    $current_data = $data;
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('EXTRACT DEBUG - Starting with path: ' . $items_path);
        error_log('EXTRACT DEBUG - Path parts: ' . print_r($path_parts, true));
    }
    
    foreach ($path_parts as $part) {
        if (is_array($current_data) && isset($current_data[$part])) {
            $current_data = $current_data[$part];
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EXTRACT DEBUG - Found part: ' . $part);
            }
        } elseif (is_object($current_data) && isset($current_data->$part)) {
            $current_data = $current_data->$part;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EXTRACT DEBUG - Found object part: ' . $part);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EXTRACT DEBUG - Part not found: ' . $part);
                error_log('EXTRACT DEBUG - Available keys: ' . print_r(is_array($current_data) ? array_keys($current_data) : 'not array', true));
            }
            return [];
        }
    }
    
    return is_array($current_data) ? $current_data : [$current_data];
}
```

### C. Corregir conversión de datos para Bricks

**Archivo:** `bricks-api-integrator.php`
**Línea:** ~730 (función `convert_api_data_for_bricks`)

**Solución:** Mejorar la estructura de pseudo-posts:

```php
private function convert_api_data_for_bricks($api_data) {
    if (empty($api_data)) {
        return [];
    }
    
    // Si no es array, convertir a array
    if (!is_array($api_data)) {
        $api_data = [$api_data];
    }
    
    $converted_data = [];
    
    foreach ($api_data as $index => $item) {
        // Crear un objeto pseudo-post para cada item
        $pseudo_post = new stdClass();
        $pseudo_post->ID = is_numeric($index) ? ($index + 1) : 1;
        $pseudo_post->post_title = '';
        $pseudo_post->post_content = '';
        $pseudo_post->post_type = 'api_data';
        $pseudo_post->post_status = 'publish';
        $pseudo_post->post_date = current_time('mysql');
        $pseudo_post->post_author = 1;
        
        // CLAVE: Añadir todos los campos de la API como propiedades directas
        if (is_array($item) || is_object($item)) {
            $item_array = (array) $item;
            foreach ($item_array as $key => $value) {
                // Normalizar el nombre de la propiedad
                $prop_name = sanitize_key($key);
                $pseudo_post->$prop_name = $value;
            }
        }
        
        // También mantener acceso a datos originales
        $pseudo_post->api_data = $item;
        
        // Intentar extraer título si existe
        if (is_array($item) || is_object($item)) {
            $item_array = (array) $item;
            foreach (['title', 'name', 'nombre', 'titulo', 'titol-anunci'] as $title_field) {
                if (isset($item_array[$title_field])) {
                    $pseudo_post->post_title = sanitize_text_field($item_array[$title_field]);
                    break;
                }
            }
        }
        
        $converted_data[] = $pseudo_post;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('CONVERT DEBUG - Converted ' . count($converted_data) . ' items');
        if (!empty($converted_data[0])) {
            error_log('CONVERT DEBUG - First item properties: ' . print_r(get_object_vars($converted_data[0]), true));
        }
    }
    
    return $converted_data;
}
```

### D. Corregir registro de Dynamic Tags

**Archivo:** `bricks-api-integrator.php`
**Línea:** ~780 (función `add_dynamic_tags_dynamic`)

**Solución:** Asegurar registro correcto:

```php
public function add_dynamic_tags_dynamic($tags) {
    // Solo usar los tags generados manualmente
    $tags_data = get_option('bricks_api_generated_tags', []);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('DYNAMIC TAGS DEBUG - Tags data found: ' . count($tags_data));
    }
    
    if (!empty($tags_data)) {
        foreach ($tags_data as $slug => $info) {
            $group_title = $info['group_title'] ?? $slug;
            $endpoint_name = $info['endpoint_name'] ?? $slug;
            $tag_list = $info['tags'] ?? [];
            
            foreach ($tag_list as $tag) {
                // Extraer el nombre del tag sin llaves
                $tag_name = trim($tag, '{}');
                
                $tags[] = [
                    'name' => $tag_name,
                    'label' => $tag_name, 
                    'group' => $group_title
                ];
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('DYNAMIC TAGS DEBUG - Added tag: ' . $tag_name . ' to group: ' . $group_title);
                }
            }
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('DYNAMIC TAGS DEBUG - Total tags registered: ' . count($tags));
    }
    
    return $tags;
}
```

## Pasos para Implementar las Correcciones

### 1. Hacer Backup
```bash
cp bricks-api-integrator.php bricks-api-integrator.php.backup
```

### 2. Aplicar correcciones una por una

### 3. Limpiar caché
- Ir a admin → Bricks API Integrator → Click "Clear Cache"
- También limpiar caché de WordPress si usas algún plugin de caché

### 4. Regenerar tags y query types
- Ir a Sources → Editar tu source
- Click "Crear tags y query types dinámicos"

### 5. Probar en Bricks
- Crear nuevo Query Loop
- Seleccionar tu source en el dropdown
- Los dynamic tags deberían aparecer en el selector

## Debugging Adicional

Si los problemas persisten, activar logging detallado añadiendo a `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Los logs aparecerán en `/wp-content/debug.log`

## Verificaciones

1. **Query Loop**: Los datos deben aparecer en el loop
2. **Dynamic Tags**: Deben aparecer en el selector de Bricks
3. **Console**: No debe haber errores JavaScript
4. **Debug Log**: Debe mostrar los pasos de extracción correctamente
