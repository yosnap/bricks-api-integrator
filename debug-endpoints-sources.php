<?php
/**
 * DEBUG: Verificar asociación endpoints-sources
 * 
 * INSTRUCCIONES:
 * 1. Añade este código al final de functions.php de tu tema
 * 2. Ve al dashboard del admin
 * 3. Verás un recuadro con la información de debug
 * 4. ELIMINA el código después de ver los resultados
 */

add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'dashboard') return;
    
    echo '<div class="notice notice-warning" style="padding: 15px; font-family: monospace; white-space: pre-line; background: #fff3cd;">';
    echo '<h3>🔍 DEBUG: ENDPOINTS vs SOURCES</h3>';
    
    // 1. Endpoints disponibles
    $endpoints = get_option('bricks_api_endpoints', []);
    echo "\n📡 ENDPOINTS DISPONIBLES:\n";
    if (empty($endpoints)) {
        echo "❌ No hay endpoints configurados\n";
    } else {
        foreach ($endpoints as $index => $endpoint) {
            echo "✅ ID: {$index} - Nombre: " . ($endpoint['name'] ?? 'Sin nombre') . "\n";
            echo "   URL: " . ($endpoint['url'] ?? 'Sin URL') . "\n\n";
        }
    }
    
    // 2. Sources y sus endpoint_id
    $sources = get_option('bricks_api_sources', []);
    echo "\n📋 SOURCES Y SUS ENDPOINT_ID:\n";
    if (empty($sources)) {
        echo "❌ No hay sources configurados\n";
    } else {
        foreach ($sources as $source_id => $source) {
            echo "✅ Source: " . ($source['name'] ?? 'Sin nombre') . "\n";
            echo "   Source ID: {$source_id}\n";
            echo "   Endpoint ID asignado: " . ($source['endpoint_id'] ?? 'No asignado') . "\n";
            
            // Verificar si el endpoint existe
            $endpoint_id = $source['endpoint_id'] ?? '';
            if ($endpoint_id !== '' && isset($endpoints[$endpoint_id])) {
                echo "   ✅ Endpoint encontrado: " . ($endpoints[$endpoint_id]['name'] ?? 'Sin nombre') . "\n";
            } else {
                echo "   ❌ PROBLEMA: Endpoint ID '{$endpoint_id}' no existe\n";
                echo "   💡 SOLUCIÓN: Reasignar endpoint en el source\n";
            }
            echo "\n";
        }
    }
    
    // 3. Query types generados
    $query_types = get_option('bricks_api_generated_query_types', []);
    echo "\n🎯 QUERY TYPES GENERADOS:\n";
    if (empty($query_types)) {
        echo "❌ No hay query types generados\n";
        echo "💡 Genera tags después de corregir la asociación\n";
    } else {
        foreach ($query_types as $slug => $info) {
            $is_source = strpos($info['group_title'] ?? '', '(Source)') !== false;
            if ($is_source) {
                echo "✅ Query Type Source: {$slug}\n";
                echo "   Endpoint ID: " . ($info['url'] ?? 'No configurado') . "\n";
            }
        }
    }
    
    echo '</div>';
});
