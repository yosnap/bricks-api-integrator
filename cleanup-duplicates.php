<?php
/**
 * Script de limpieza para eliminar duplicados
 * Ejecutar una sola vez para limpiar la configuración
 */

// Solo ejecutar si estamos en WordPress admin
if (!defined('ABSPATH')) {
    exit;
}

// Función para limpiar duplicados
function clean_bricks_api_integrator_duplicates() {
    // Limpiar endpoints duplicados
    $endpoints = get_option('bricks_api_endpoints', []);
    $clean_endpoints = [];
    $seen_names = [];
    
    foreach ($endpoints as $endpoint) {
        if (!empty($endpoint['name']) && !isset($seen_names[$endpoint['name']])) {
            $clean_endpoints[] = $endpoint;
            $seen_names[$endpoint['name']] = true;
        }
    }
    
    update_option('bricks_api_endpoints', $clean_endpoints);
    
    // Limpiar sources duplicados
    $sources = get_option('bricks_api_sources', []);
    $clean_sources = [];
    $seen_source_names = [];
    
    foreach ($sources as $source_id => $source) {
        if (!empty($source['name']) && !isset($seen_source_names[$source['name']])) {
            $clean_sources[$source_id] = $source;
            $seen_source_names[$source['name']] = true;
        }
    }
    
    update_option('bricks_api_sources', $clean_sources);
    
    // Limpiar cache
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_api_data_%'");
    
    return [
        'endpoints_before' => count($endpoints),
        'endpoints_after' => count($clean_endpoints),
        'sources_before' => count($sources), 
        'sources_after' => count($clean_sources)
    ];
}

// Solo ejecutar si es admin y se solicita específicamente
if (is_admin() && isset($_GET['clean_duplicates']) && current_user_can('manage_options')) {
    $result = clean_bricks_api_integrator_duplicates();
    echo '<div class="notice notice-success"><p>';
    echo '✅ Limpieza completada:<br>';
    echo "Endpoints: {$result['endpoints_before']} → {$result['endpoints_after']}<br>";
    echo "Sources: {$result['sources_before']} → {$result['sources_after']}<br>";
    echo 'Cache limpiado correctamente';
    echo '</p></div>';
}
