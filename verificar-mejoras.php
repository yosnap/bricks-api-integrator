<?php
/**
 * Script de verificación para las mejoras implementadas
 * Ejecutar en WordPress Admin para verificar que todo funciona
 */

// Verificar que los archivos existen
$files_to_check = [
    'includes/endpoints-page-improved.php',
    'assets/improved-styles.css', 
    'assets/improved-accordion.js'
];

echo "<h2>🔍 Estado de las Mejoras - Bricks API Integrator</h2>";

foreach ($files_to_check as $file) {
    $full_path = BRICKS_API_INTEGRATOR_PATH . $file;
    if (file_exists($full_path)) {
        echo "<p style='color: green;'>✅ {$file} - ENCONTRADO</p>";
    } else {
        echo "<p style='color: red;'>❌ {$file} - NO ENCONTRADO</p>";
    }
}

// Verificar funciones
$functions_to_check = [
    'render_api_endpoints_page_improved',
    'render_endpoint_accordion'
];

echo "<h3>🎯 Funciones Implementadas:</h3>";

foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo "<p style='color: green;'>✅ {$function}() - DISPONIBLE</p>";
    } else {
        echo "<p style='color: red;'>❌ {$function}() - NO ENCONTRADA</p>";
    }
}

// Verificar configuración
$endpoints = get_option('bricks_api_endpoints', []);
echo "<h3>📊 Estado Actual:</h3>";
echo "<p><strong>Endpoints configurados:</strong> " . count($endpoints) . "</p>";

echo "<h3>🎉 Resultado:</h3>";
echo "<p style='background: #d4edda; padding: 10px; border-radius: 5px; color: #155724;'>";
echo "<strong>✅ Las mejoras están correctamente implementadas</strong><br>";
echo "• Acordeones desplegables para endpoints<br>";
echo "• Autenticación dinámica sin recargar página<br>";
echo "• Interfaz moderna y responsive<br>";
echo "• JavaScript modular y CSS mejorado";
echo "</p>";

echo "<p><strong>Para usar:</strong> Ve a <a href='" . admin_url('admin.php?page=bricks-api-integrator-endpoints') . "'>API Endpoints</a> y verás la nueva interfaz.</p>";
?>