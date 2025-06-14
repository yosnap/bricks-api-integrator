<?php
/**
 * Debug específico para el source Posts Test
 * Añadir temporalmente al functions.php
 */

add_action('admin_notices', function() {
    if (get_current_screen()->id !== 'dashboard') return;
    
    $sources = get_option('bricks_api_sources', []);
    $endpoints = get_option('bricks_api_endpoints', []);
    
    echo '<div class="notice notice-info" style="padding: 15px; font-family: monospace;">';
    echo '<h3>🔍 DEBUG POSTS TEST</h3>';
    
    foreach ($sources as $source_id => $source) {
        if (strpos($source['name'], 'Posts') !== false) {
            echo "Source: " . $source['name'] . "\n";
            echo "Source ID: {$source_id}\n";
            echo "Endpoint ID guardado: '" . ($source['endpoint_id'] ?? 'VACIO') . "'\n";
            echo "Tipo: " . gettype($source['endpoint_id'] ?? null) . "\n";
            
            // Verificar si el endpoint existe
            $ep_id = $source['endpoint_id'] ?? '';
            echo "Buscando endpoint con ID: '{$ep_id}'\n";
            
            if (isset($endpoints[$ep_id])) {
                echo "✅ Endpoint encontrado: " . $endpoints[$ep_id]['name'] . "\n";
            } else {
                echo "❌ Endpoint NO encontrado\n";
                echo "Endpoints disponibles:\n";
                foreach ($endpoints as $idx => $ep) {
                    echo "  - ID '{$idx}' (tipo: " . gettype($idx) . "): " . $ep['name'] . "\n";
                }
            }
            break;
        }
    }
    echo '</div>';
});
