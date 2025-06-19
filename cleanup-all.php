<?php
/**
 * Script de limpieza completa para Bricks API Integrator
 * Este script elimina todas las opciones relacionadas con el plugin
 */

// Asegurarse de que este script solo se ejecute desde WordPress
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
}
require_once(ABSPATH . 'wp-load.php');

// Verificar que el usuario tiene permisos de administrador
if (!current_user_can('manage_options')) {
    wp_die('No tienes permisos para ejecutar este script.');
}

// Array de todas las opciones a eliminar
$options_to_delete = [
    'bricks_api_endpoints',
    'bricks_api_sources',
    'bricks_api_generated_query_types',
    'bricks_api_generated_tags',
    'bricks_api_settings',
    'bricks_api_cache'
];

// Eliminar cada opción
$deleted_options = [];
$failed_options = [];

foreach ($options_to_delete as $option) {
    if (delete_option($option)) {
        $deleted_options[] = $option;
    } else {
        $failed_options[] = $option;
    }
}

// Mostrar resultados
echo "<h2>Resultados de la limpieza</h2>";

if (!empty($deleted_options)) {
    echo "<h3>✅ Opciones eliminadas correctamente:</h3>";
    echo "<ul>";
    foreach ($deleted_options as $option) {
        echo "<li>{$option}</li>";
    }
    echo "</ul>";
}

if (!empty($failed_options)) {
    echo "<h3>❌ Opciones que no se pudieron eliminar (posiblemente no existían):</h3>";
    echo "<ul>";
    foreach ($failed_options as $option) {
        echo "<li>{$option}</li>";
    }
    echo "</ul>";
}

// Limpiar caché de transients
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%bricks_api_cache%'");
echo "<h3>✅ Cache de transients limpiada</h3>";

echo "<p>Proceso de limpieza completado. Ahora puedes proceder a reconstruir los endpoints y sources.</p>";
echo "<p><a href='" . admin_url('admin.php?page=bricks-api-integrator') . "' class='button button-primary'>Volver al panel de administración</a></p>"; 