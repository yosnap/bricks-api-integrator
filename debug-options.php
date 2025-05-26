<?php
/**
 * Debug completo de opciones - Ejecutar temporalmente
 */

// Cargar WordPress
require_once('../../../wp-load.php');

// Verificar permisos
if (!current_user_can('manage_options')) {
    die('No tienes permisos para acceder a esta página.');
}

// Verificar todas las opciones relacionadas
echo "<h2>🔍 Debug de Opciones del Plugin</h2>";

$options_to_check = [
    'bricks_api_endpoints',
    'bricks_api_sources', 
    'bricks_api_launchers',
    'bricks_api_templates'
];

foreach ($options_to_check as $option) {
    $value = get_option($option, []);
    echo "<h3>📋 {$option}</h3>";
    echo "<pre>" . print_r($value, true) . "</pre>";
    echo "<p><strong>Cantidad:</strong> " . (is_array($value) ? count($value) : 'No es array') . "</p>";
    echo "<hr>";
}

// Verificar transients
global $wpdb;
$transients = $wpdb->get_results(
    "SELECT option_name, option_value FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_api_data_%' 
     OR option_name LIKE '_transient_timeout_api_data_%'"
);

echo "<h3>🗄️ Transients Activos</h3>";
if (empty($transients)) {
    echo "<p>✅ No hay transients activos</p>";
} else {
    foreach ($transients as $transient) {
        echo "<p><strong>{$transient->option_name}:</strong> " . substr($transient->option_value, 0, 100) . "...</p>";
    }
}

// Verificar si Bricks está cargado y los hooks
echo "<h3>🎯 Estado de Hooks</h3>";
echo "<p><strong>Bricks cargado:</strong> " . (function_exists('bricks_is_builder') ? '✅ Sí' : '❌ No') . "</p>";
echo "<p><strong>Hook bricks/setup/control_options:</strong> " . (has_filter('bricks/setup/control_options') ? '✅ Activo' : '❌ Inactivo') . "</p>";
echo "<p><strong>Hook bricks/dynamic_tags_list:</strong> " . (has_filter('bricks/dynamic_tags_list') ? '✅ Activo' : '❌ Inactivo') . "</p>";

// Limpiar manualmente si es necesario
echo "<h3>🧹 Limpieza Manual</h3>";
if (isset($_GET['clean']) && $_GET['clean'] === 'all') {
    // Eliminar todas las opciones
    delete_option('bricks_api_endpoints');
    delete_option('bricks_api_sources');
    delete_option('bricks_api_launchers');
    delete_option('bricks_api_templates');
    
    // Limpiar transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
    
    // Recrear opciones vacías
    update_option('bricks_api_endpoints', []);
    update_option('bricks_api_sources', []);
    update_option('bricks_api_launchers', []);
    update_option('bricks_api_templates', []);
    
    echo "<p style='color: green;'>✅ Limpieza completa realizada. <a href='?'>Actualizar página</a></p>";
} else {
    echo "<p><a href='?clean=all' onclick='return confirm(\"¿Estás seguro de limpiar todo?\")' style='background: #dc3232; color: white; padding: 10px; text-decoration: none; border-radius: 3px;'>🧹 Limpiar Todo Manualmente</a></p>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
h2, h3 { color: #333; }
hr { margin: 20px 0; }
</style>
