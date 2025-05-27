<?php
/**
 * Script de limpieza AGRESIVA para eliminar TODO y empezar desde cero
 */

if (!defined('ABSPATH')) {
    exit;
}

function aggressive_cleanup_bricks_api() {
    // Eliminar TODAS las opciones del plugin
    delete_option('bricks_api_endpoints');
    delete_option('bricks_api_sources');
    delete_option('bricks_api_launchers');
    delete_option('bricks_api_templates');
    
    // Limpiar TODO el transient cache relacionado con APIs
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%bricks_api%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%api%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%api%'");
    
    // Recrear configuraciones básicas
    update_option('bricks_api_endpoints', []);
    update_option('bricks_api_sources', []);
    update_option('bricks_api_launchers', []);
    update_option('bricks_api_templates', []);
    
    return 'Limpieza agresiva completada - TODO eliminado y reiniciado';
}

// Hook para ejecutar la limpieza después de que WordPress esté completamente cargado
add_action('admin_init', function() {
    if (is_admin() && isset($_GET['aggressive_cleanup']) && current_user_can('manage_options')) {
        $result = aggressive_cleanup_bricks_api();
        add_action('admin_notices', function() use ($result) {
            echo '<div class="notice notice-success"><p>✅ ' . $result . '</p></div>';
        });
    }
});
