<?php
/**
 * Cleaner - Herramientas para limpiar parámetros persistentes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limpiar completamente todos los parámetros persistentes
 * Esta función realiza una limpieza profunda de la base de datos
 */
function bricks_api_clean_all_parameters() {
    global $wpdb;
    
    // PASO 1: Limpiar completamente todos los Query Types
    $sources = get_option('bricks_api_sources', []);
    $modified = false;
    
    // Recorrer cada source y reiniciar sus parámetros dinámicos
    foreach ($sources as $source_id => $source) {
        // Conservar solo los datos básicos y eliminar cualquier parámetro dinámico
        if (isset($sources[$source_id]['dynamic_params'])) {
            // Guardar los parámetros actuales para verificar si hay cambios
            $old_params = $sources[$source_id]['dynamic_params'];
            
            // Filtrar los parámetros para eliminar cualquier referencia a anunci-actiu
            $filtered_params = array_filter($old_params, function($param) {
                return $param['name'] !== 'anunci-actiu';
            });
            
            // Reindexar el array
            $sources[$source_id]['dynamic_params'] = array_values($filtered_params);
            
            // Verificar si hubo cambios
            if (count($old_params) !== count($filtered_params)) {
                $modified = true;
            }
        }
    }
    
    // Actualizar la opción con los sources limpios si hubo cambios
    if ($modified) {
        update_option('bricks_api_sources', $sources);
    }
    
    // PASO 2: Eliminar todas las transients relacionadas con el plugin
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%bricks_api%'");
    
    // PASO 3: Buscar y limpiar cualquier opción que contenga referencias a anunci-actiu
    $problematic_options = $wpdb->get_results(
        "SELECT option_name FROM {$wpdb->options} 
        WHERE option_value LIKE '%anunci-actiu%'"
    );
    
    foreach ($problematic_options as $option) {
        // Excluir la opción principal de sources que ya limpiamos
        if ($option->option_name !== 'bricks_api_sources') {
            // Si es una transient, eliminarla completamente
            if (strpos($option->option_name, '_transient_') === 0) {
                $transient_name = str_replace(['_transient_', '_transient_timeout_'], '', $option->option_name);
                delete_transient($transient_name);
            } 
            // Si es una opción relacionada con el plugin, intentar limpiarla
            elseif (strpos($option->option_name, 'bricks_api') !== false) {
                $option_value = get_option($option->option_name);
                
                if (is_array($option_value)) {
                    $option_modified = false;
                    
                    // Función recursiva para limpiar arrays
                    $clean_array = function($array) use (&$clean_array, &$option_modified) {
                        foreach ($array as $key => $value) {
                            // Eliminar claves que coincidan con anunci-actiu
                            if ($key === 'anunci-actiu') {
                                unset($array[$key]);
                                $option_modified = true;
                                continue;
                            }
                            
                            // Limpiar valores string que contengan anunci-actiu
                            if (is_string($value) && strpos($value, 'anunci-actiu') !== false) {
                                $array[$key] = str_replace('anunci-actiu', '', $value);
                                $option_modified = true;
                            }
                            // Procesar arrays anidados
                            elseif (is_array($value)) {
                                $array[$key] = $clean_array($value);
                            }
                            // Verificar parámetros dinámicos
                            elseif ($key === 'name' && $value === 'anunci-actiu' && isset($array['parent'])) {
                                $option_modified = true;
                                unset($array['parent'][$key]);
                            }
                        }
                        return $array;
                    };
                    
                    // Limpiar el array
                    $cleaned_value = $clean_array($option_value);
                    
                    // Actualizar la opción si fue modificada
                    if ($option_modified) {
                        update_option($option->option_name, $cleaned_value);
                    }
                }
            }
        }
    }
    
    // PASO 4: Limpiar la caché de WordPress
    wp_cache_flush();
    
    return true;
}

/**
 * Verificar si hay parámetros anunci-actiu en la base de datos
 * @return bool True si se encontraron parámetros, false en caso contrario
 */
function bricks_api_check_problematic_parameters() {
    global $wpdb;
    
    $count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->options} 
        WHERE option_value LIKE '%anunci-actiu%'"
    );
    
    return $count > 0;
}
