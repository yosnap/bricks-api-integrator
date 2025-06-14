<?php
/**
 * Script de verificación para ejecutar en WordPress
 * 
 * INSTRUCCIONES:
 * 1. Copia este código
 * 2. Ve a WordPress Admin → Apariencia → Editor de temas → functions.php
 * 3. Pega el código al final del archivo (antes del ?>)
 * 4. Guarda
 * 5. Ve a cualquier página del admin y verás los resultados
 * 6. IMPORTANTE: Elimina el código después de usarlo
 */

add_action('admin_notices', function() {
    // Solo mostrar en la página principal del admin
    $screen = get_current_screen();
    if ($screen->id !== 'dashboard') return;
    
    echo '<div class="notice notice-info" style="padding: 20px; font-family: monospace; white-space: pre-line; background: #f0f8ff;">';
    echo '<h3>🔍 VERIFICACIÓN SOURCES QUERY LOOP</h3>';
    
    // 1. Verificar sources configurados
    $sources = get_option('bricks_api_sources', []);
    echo "\n1. SOURCES CONFIGURADOS:\n";
    if (empty($sources)) {
        echo "❌ No hay sources configurados";
        echo "\n   → Ve a: API Integrator → Query Types → Crear nuevo\n";
    } else {
        foreach ($sources as $source_id => $source) {
            echo "✅ Source: {$source_id}\n";
            echo "   - Nombre: " . ($source['name'] ?? 'Sin nombre') . "\n";
            echo "   - Endpoint ID: " . ($source['endpoint_id'] ?? 'No configurado') . "\n";
            echo "   - Items Path: " . ($source['items_path'] ?? 'Vacío') . "\n\n";
        }
    }
    
    // 2. Verificar endpoints disponibles
    $endpoints = get_option('bricks_api_endpoints', []);
    echo "\n2. ENDPOINTS DISPONIBLES:\n";
    if (empty($endpoints)) {
        echo "❌ No hay endpoints configurados";
        echo "\n   → Ve a: API Integrator → API Endpoints → Crear nuevo\n";
    } else {
        foreach ($endpoints as $index => $endpoint) {
            echo "✅ Endpoint #{$index}: " . ($endpoint['name'] ?? 'Sin nombre') . "\n";
            echo "   - URL: " . ($endpoint['url'] ?? 'Sin URL') . "\n";
        }
    }
    
    // 3. Verificar query types generados
    $query_types = get_option('bricks_api_generated_query_types', []);
    echo "\n\n3. QUERY TYPES GENERADOS:\n";
    if (empty($query_types)) {
        echo "❌ No hay query types generados";
        echo "\n   → Genera tags desde un source o endpoint\n";
    } else {
        foreach ($query_types as $slug => $info) {
            $is_source = strpos($info['group_title'] ?? '', '(Source)') !== false;
            
            if ($is_source) {
                echo "✅ SOURCE: {$slug}\n";
                echo "   - Nombre: " . ($info['endpoint_name'] ?? 'Sin nombre') . "\n";
                echo "   - Key en Bricks: snap_source_{$slug}\n";
                echo "   - Endpoint ID: " . ($info['url'] ?? 'No configurado') . "\n\n";
            }
        }
    }
    
    // 4. Instrucciones siguientes
    echo "\n\n4. PASOS SIGUIENTES:\n";
    if (!empty($sources)) {
        echo "✅ Tienes sources configurados - ¡Perfecto!\n";
        echo "👉 AHORA PRUEBA EN BRICKS:\n";
        echo "   1. Ve a Bricks Builder\n";
        echo "   2. Añade elemento con Query Loop\n";
        echo "   3. Busca tu Query Type con '(Source)'\n";
        echo "   4. ¡Verifica que funciona el loop!\n\n";
        
        echo "🔍 DEBUG: Activa WP_DEBUG=true para ver logs detallados\n";
    } else {
        echo "👉 PRIMERO CREA UN SOURCE:\n";
        echo "   1. Ve a: API Integrator → Query Types\n";
        echo "   2. Crea nuevo con items_path (ej: data.productos)\n";
        echo "   3. Genera tags con el botón ⚡\n";
        echo "   4. Luego prueba en Bricks\n";
    }
    
    echo '</div>';
});
