                        $group_title);
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('DYNAMIC TAGS DEBUG - Tag already exists: ' . $tag_name);
                    }
                }
            }
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('DYNAMIC TAGS DEBUG - Total tags registered: ' . count($tags));
    }
    
    return $tags;
}

// ============================================================================
// CORRECCIÓN 5: Mejorar render de Dynamic Tags
// ============================================================================
// Reemplazar la función render_dynamic_tags_dynamic en bricks-api-integrator.php (línea ~800)

function render_dynamic_tags_dynamic_FIXED($content, $post, $context) {
    // Buscar cualquier tag con formato {prefijo_slug_campo}
    if (strpos($content, '{') === false) {
        return $content;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('RENDER TAGS DEBUG - Processing content: ' . substr($content, 0, 100));
    }
    
    $content = preg_replace_callback(
        '/\{([a-zA-Z0-9_]+)_([a-zA-Z0-9-]+)_([a-zA-Z0-9_.-]+)\}/',
        function($matches) use ($post, $context) {
            $prefix = $matches[1];
            $identifier = $matches[2];
            $field = $matches[3];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RENDER TAGS DEBUG - Processing tag: ' . $matches[0]);
                error_log('RENDER TAGS DEBUG - Prefix: ' . $prefix . ', Identifier: ' . $identifier . ', Field: ' . $field);
            }
            
            // Primero intentar obtener desde el loop object actual
            $loop_object = \Bricks\Query::get_loop_object();
            if (!empty($loop_object)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('RENDER TAGS DEBUG - Loop object found, type: ' . gettype($loop_object));
                }
                
                // Intentar acceso directo a la propiedad
                if (property_exists($loop_object, $field)) {
                    $value = $loop_object->$field;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('RENDER TAGS DEBUG - Found direct property: ' . $field . ' = ' . $value);
                    }
                    return $this->format_field_output($value, $field);
                }
                
                // Intentar acceso a través de api_data
                if (isset($loop_object->api_data)) {
                    $api_data = $loop_object->api_data;
                    $value = $this->get_value_by_dot_notation($api_data, $field);
                    if ($value !== '' && $value !== null) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('RENDER TAGS DEBUG - Found in api_data: ' . $field . ' = ' . $value);
                        }
                        return $this->format_field_output($value, $field);
                    }
                }
                
                // Intentar con el campo normalizado
                $normalized_field = str_replace(['-', '.'], '_', $field);
                if (property_exists($loop_object, $normalized_field)) {
                    $value = $loop_object->$normalized_field;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('RENDER TAGS DEBUG - Found normalized property: ' . $normalized_field . ' = ' . $value);
                    }
                    return $this->format_field_output($value, $field);
                }
            }
            
            // Si el prefijo es 'snap', usar lógica de source
            if ($prefix === 'snap') {
                $value = $this->get_source_field_value($identifier, $field, $post, $context);
                if ($value !== '') {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('RENDER TAGS DEBUG - Found via source method: ' . $field . ' = ' . $value);
                    }
                    return $value;
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('RENDER TAGS DEBUG - Tag not resolved: ' . $matches[0]);
            }
            
            return $matches[0]; // Devolver el tag original si no se puede resolver
        },
        $content
    );
    
    return $content;
}

// ============================================================================
// PASOS PARA APLICAR LAS CORRECCIONES
// ============================================================================

/*
PASOS PARA IMPLEMENTAR:

1. HAZ BACKUP DEL ARCHIVO ORIGINAL:
   cp bricks-api-integrator.php bricks-api-integrator.php.backup

2. APLICAR LAS CORRECCIONES UNA POR UNA:
   
   a) Encuentra la función extract_nested_items y reemplázala con extract_nested_items_FIXED
   b) Encuentra la función convert_api_data_for_bricks y reemplázala con convert_api_data_for_bricks_FIXED
   c) En run_custom_query_dynamic, encuentra la sección que maneja sources y actualízala
   d) Encuentra add_dynamic_tags_dynamic y reemplázala con add_dynamic_tags_dynamic_FIXED
   e) Encuentra render_dynamic_tags_dynamic y reemplázala con render_dynamic_tags_dynamic_FIXED

3. ACTIVAR DEBUGGING:
   En wp-config.php, añadir:
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);

4. LIMPIAR CACHÉ:
   - Ir a admin → Bricks API Integrator
   - Click "Clear Cache"
   - Limpiar caché de WordPress si usas plugins de caché

5. REGENERAR QUERY TYPES Y TAGS:
   - Ir a Sources → Editar tu source "Vehículos Motor"
   - Click "Crear tags y query types dinámicos"
   - Verificar que aparecen los tags

6. PROBAR EN BRICKS:
   - Crear nuevo Query Loop
   - Seleccionar "Vehículos Motor (Source)" en el dropdown
   - Los dynamic tags deberían aparecer y funcionar

7. VERIFICAR LOGS:
   Revisar /wp-content/debug.log para ver los mensajes de debug

NOTAS IMPORTANTES:
- Las correcciones están diseñadas específicamente para tu caso con arrays anidados
- El items_path "items" debería funcionar correctamente
- Los tags dinámicos deberían aparecer en el selector de Bricks
- Si persisten problemas, revisar los logs de debug para más información
*/
