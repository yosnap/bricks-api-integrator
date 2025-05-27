<?php
/**
 * Script de limpieza para Query Types duplicados
 * 
 * @package BricksAPIIntegrator
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Limpiar duplicados de Query Types
 */
function clean_bricks_api_query_types_duplicates() {
    global $wpdb;
    
    $result = [
        'cleaned_options' => 0,
        'cleaned_transients' => 0,
        'message' => ''
    ];
    
    // 1. Limpiar opciones relacionadas con direct-source
    $deleted_options = $wpdb->query("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '%bai_api_source%' 
        OR option_name LIKE '%direct_source%'
        OR option_name LIKE '%bricks_api_debug%'
    ");
    
    $result['cleaned_options'] = $deleted_options;
    
    // 2. Limpiar transients de cache
    $deleted_transients = $wpdb->query("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_api_data_%' 
        OR option_name LIKE '_transient_timeout_api_data_%'
        OR option_name LIKE '_transient_bricks_query_%'
    ");
    
    $result['cleaned_transients'] = $deleted_transients;
    
    // 3. Limpiar cache de objeto si está disponible
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    $result['message'] = "Limpieza completada. Opciones: {$deleted_options}, Transients: {$deleted_transients}";
    
    return $result;
}

/**
 * Verificar si hay Query Types duplicados
 */
function check_query_types_duplicates() {
    // Simular el filtro para ver qué se está generando
    $control_options = ['queryTypes' => []];
    
    // Aplicar el filtro de nuestro plugin
    if (class_exists('BricksAPIIntegrator')) {
        $integrator = new BricksAPIIntegrator();
        $control_options = $integrator->add_query_types_dynamic($control_options);
    }
    
    $query_types = $control_options['queryTypes'] ?? [];
    
    // Filtrar solo los nuestros
    $api_query_types = [];
    foreach ($query_types as $key => $name) {
        if (strpos($key, 'api_') === 0 || strpos($key, 'bai_') === 0) {
            $api_query_types[$key] = $name;
        }
    }
    
    return [
        'total_query_types' => count($query_types),
        'api_query_types' => $api_query_types,
        'duplicates_found' => count($api_query_types) > count(get_option('bricks_api_endpoints', []))
    ];
}

// Si se ejecuta desde admin, hacer la limpieza
if (is_admin() && isset($_GET['clean_query_types']) && current_user_can('manage_options')) {
    $cleanup_result = clean_bricks_api_query_types_duplicates();
    
    add_action('admin_notices', function() use ($cleanup_result) {
        echo '<div class="notice notice-success"><p>✅ ' . $cleanup_result['message'] . '</p></div>';
    });
}
