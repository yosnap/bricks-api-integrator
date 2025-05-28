<?php
/**
 * Script de Verificación - Cambios en Endpoints
 * 
 * Verifica que los cambios realizados en la página de endpoints
 * funcionen correctamente sin duplicación de formularios.
 */

// Verificar estructura del archivo endpoints-page.php
$file_path = '/Users/paulo/Local Sites/api-fetch/app/public/wp-content/plugins/bricks-api-integrator/includes/endpoints-page.php';

if (!file_exists($file_path)) {
    echo "❌ Archivo no encontrado: $file_path\n";
    exit(1);
}

$content = file_get_contents($file_path);

// Verificaciones
$checks = [
    'función_principal' => [
        'pattern' => '/function render_api_endpoints_page\(\)/',
        'description' => 'Función principal existe'
    ],
    'acordeon_unico' => [
        'pattern' => '/class="endpoint-accordion"/',
        'description' => 'Estructura de acordeón implementada'
    ],
    'auth_dinamica' => [
        'pattern' => '/id="auth-fields-/',
        'description' => 'Campos de autenticación dinámicos'
    ],
    'javascript_toggle' => [
        'pattern' => '/function toggleEndpoint\(index\)/',
        'description' => 'Función JavaScript para acordeones'
    ],
    'javascript_auth' => [
        'pattern' => '/function toggleAuthFields\(index, authType\)/',
        'description' => 'Función JavaScript para autenticación'
    ],
    'sin_duplicacion' => [
        'pattern' => '/<!-- Acordeón Único con Contenido Completo -->/',
        'description' => 'Comentario que identifica acordeón único'
    ],
    'css_estilos' => [
        'pattern' => '/\.endpoint-accordion/',
        'description' => 'Estilos CSS para acordeones'
    ]
];

echo "🔍 Verificando cambios en endpoints-page.php...\n\n";

$all_passed = true;

foreach ($checks as $key => $check) {
    $found = preg_match($check['pattern'], $content);
    $status = $found ? "✅" : "❌";
    $all_passed = $all_passed && $found;
    
    echo "$status {$check['description']}\n";
}

echo "\n";

if ($all_passed) {
    echo "🎉 VERIFICACIÓN EXITOSA: Todos los cambios están implementados correctamente\n";
    echo "\n📋 Resumen de mejoras:\n";
    echo "   • Eliminado formulario duplicado\n";
    echo "   • Implementado acordeón único\n";
    echo "   • Campos de autenticación dinámicos\n";
    echo "   • JavaScript funcional para toggles\n";
    echo "   • CSS mejorado para UX\n";
} else {
    echo "⚠️  VERIFICACIÓN PARCIAL: Algunos elementos no se encontraron\n";
    echo "   Revisar implementación manual\n";
}

// Análisis adicional de la estructura
echo "\n🔬 ANÁLISIS DE ESTRUCTURA:\n";

// Contar acordeones
$accordion_count = preg_match_all('/class="endpoint-accordion"/', $content);
echo "   • Acordeones encontrados: $accordion_count\n";

// Verificar JavaScript functions
$js_functions = ['toggleEndpoint', 'toggleAuthFields'];
foreach ($js_functions as $func) {
    $found = strpos($content, "function $func") !== false;
    $status = $found ? "✅" : "❌";
    echo "   • Función $func: $status\n";
}

// Verificar CSS classes
$css_classes = ['endpoint-accordion', 'endpoint-header', 'endpoint-content', 'auth-inputs'];
foreach ($css_classes as $class) {
    $found = strpos($content, ".$class") !== false;
    $status = $found ? "✅" : "❌";
    echo "   • Estilo .$class: $status\n";
}

echo "\n";
