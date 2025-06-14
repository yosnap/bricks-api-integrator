<?php
/**
 * Verificación práctica de Sources - Query Loop
 * 
 * Para ejecutar desde WordPress: 
 * - Activar WP_DEBUG = true en wp-config.php
 * - Acceder a este archivo desde el navegador o incluirlo en functions.php
 */

// Verificar si estamos en WordPress
if (!defined('ABSPATH')) {
    // Si no estamos en WordPress, simular las funciones básicas
    function get_option($option, $default = false) {
        // Simular datos para testing
        switch ($option) {
            case 'bricks_api_sources':
                return [
                    'source_test_1' => [
                        'name' => 'Productos Test',
                        'endpoint_id' => '0',
                        'items_path' => 'data.productos',
                        'field_prefix' => 'snap_'
                    ]
                ];
            case 'bricks_api_endpoints':
                return [
                    [
                        'name' => 'API Test',
                        'url' => 'https://api.ejemplo.com/productos'
                    ]
                ];
            case 'bricks_api_generated_query_types':
                return [
                    'productos-test' => [
                        'query_type' => '{snap_productos-test}',
                        'endpoint_name' => 'Productos Test',
                        'group_title' => 'Productos Test (Source)',
                        'url' => '0', // endpoint_id
                        'fields' => ['id', 'nombre', 'precio'],
                        'example' => ['id' => 1, 'nombre' => 'Test', 'precio' => 100]
                    ]
                ];
        }
        return $default;
    }
}

echo "=== VERIFICACIÓN SOURCES QUERY LOOP ===\n\n";

// 1. Verificar sources configurados
$sources = get_option('bricks_api_sources', []);
echo "1. SOURCES CONFIGURADOS:\n";
if (empty($sources)) {
    echo "❌ No hay sources configurados\n";
    echo "   → Necesitas crear un source primero\n\n";
} else {
    foreach ($sources as $source_id => $source) {
        echo "✅ Source ID: {$source_id}\n";
        echo "   - Nombre: " . ($source['name'] ?? 'Sin nombre') . "\n";
        echo "   - Endpoint ID: " . ($source['endpoint_id'] ?? 'No configurado') . "\n";
        echo "   - Items Path: " . ($source['items_path'] ?? 'Vacío') . "\n";
        echo "   - Field Prefix: " . ($source['field_prefix'] ?? 'No configurado') . "\n\n";
    }
}

// 2. Verificar endpoints disponibles
$endpoints = get_option('bricks_api_endpoints', []);
echo "2. ENDPOINTS DISPONIBLES:\n";
if (empty($endpoints)) {
    echo "❌ No hay endpoints configurados\n\n";
} else {
    foreach ($endpoints as $index => $endpoint) {
        echo "✅ Endpoint #{$index}\n";
        echo "   - Nombre: " . ($endpoint['name'] ?? 'Sin nombre') . "\n";
        echo "   - URL: " . ($endpoint['url'] ?? 'Sin URL') . "\n\n";
    }
}

// 3. Verificar query types generados
$query_types = get_option('bricks_api_generated_query_types', []);
echo "3. QUERY TYPES GENERADOS:\n";
if (empty($query_types)) {
    echo "❌ No hay query types generados\n";
    echo "   → Necesitas generar tags desde un source\n\n";
} else {
    foreach ($query_types as $slug => $info) {
        $is_source = strpos($info['group_title'] ?? '', '(Source)') !== false;
        $is_endpoint = strpos($info['group_title'] ?? '', '(Endpoint)') !== false;
        
        if ($is_source) {
            echo "✅ SOURCE Query Type: {$slug}\n";
            echo "   - Nombre: " . ($info['endpoint_name'] ?? 'Sin nombre') . "\n";
            echo "   - Grupo: " . ($info['group_title'] ?? 'Sin grupo') . "\n";
            echo "   - Key esperado en Bricks: snap_source_{$slug}\n";
            echo "   - Endpoint ID: " . ($info['url'] ?? 'No configurado') . "\n\n";
        }
    }
}

// 4. Simular el process del query loop
echo "4. SIMULACIÓN QUERY LOOP:\n";

// Test con un source específico
if (!empty($query_types)) {
    foreach ($query_types as $slug => $info) {
        if (strpos($info['group_title'] ?? '', '(Source)') !== false) {
            echo "🔄 Simulando query loop para: snap_source_{$slug}\n";
            
            // Simular object_type que enviaría Bricks
            $object_type = "snap_source_{$slug}";
            
            echo "   - object_type recibido: {$object_type}\n";
            
            // Verificar detección
            $is_endpoint = strpos($object_type, 'snap_ep_') === 0;
            $is_source = strpos($object_type, 'snap_source_') === 0;
            
            echo "   - is_endpoint: " . ($is_endpoint ? 'true' : 'false') . "\n";
            echo "   - is_source: " . ($is_source ? 'true' : 'false') . "\n";
            
            if ($is_source) {
                $extracted_slug = str_replace('snap_source_', '', $object_type);
                echo "   - slug extraído: {$extracted_slug}\n";
                
                // Verificar si existe en query_types
                if (isset($query_types[$extracted_slug])) {
                    echo "   ✅ Query type encontrado\n";
                    
                    $query_info = $query_types[$extracted_slug];
                    $endpoint_id = $query_info['url'] ?? '';
                    echo "   - endpoint_id: {$endpoint_id}\n";
                    
                    // Verificar si el endpoint existe
                    if (isset($endpoints[$endpoint_id])) {
                        echo "   ✅ Endpoint encontrado\n";
                        $endpoint = $endpoints[$endpoint_id];
                        echo "   - URL del endpoint: " . ($endpoint['url'] ?? 'Sin URL') . "\n";
                        
                        // Buscar configuración del source
                        $source_config = null;
                        foreach ($sources as $source) {
                            // Simular bricks_api_normalize_slug
                            $source_slug = strtolower(str_replace(' ', '-', $source['name'] ?? ''));
                            if ($source_slug === $extracted_slug) {
                                $source_config = $source;
                                break;
                            }
                        }
                        
                        if ($source_config) {
                            echo "   ✅ Source config encontrado\n";
                            echo "   - items_path: " . ($source_config['items_path'] ?? 'Vacío') . "\n";
                            
                            if (!empty($source_config['items_path'])) {
                                echo "   ✅ Se aplicaría items_path: {$source_config['items_path']}\n";
                                echo "   🎯 RESULTADO: Query loop debería funcionar correctamente\n";
                            } else {
                                echo "   ⚠️  items_path vacío - usaría datos directos\n";
                            }
                        } else {
                            echo "   ❌ Source config NO encontrado\n";
                        }
                    } else {
                        echo "   ❌ Endpoint NO encontrado para ID: {$endpoint_id}\n";
                    }
                } else {
                    echo "   ❌ Query type NO encontrado para slug: {$extracted_slug}\n";
                }
            }
            echo "\n";
            break; // Solo probar el primero
        }
    }
}

echo "5. PASOS PARA CREAR/PROBAR UN SOURCE:\n\n";
echo "1. Ir a admin: API Integrator → Query Types\n";
echo "2. Crear nuevo Query Type:\n";
echo "   - Nombre: 'Productos Test'\n";
echo "   - Seleccionar un endpoint existente\n";
echo "   - Items Path: 'data.productos' (o la ruta de tu API)\n";
echo "3. Probar con botón '🧪 Test Source'\n";
echo "4. Generar tags con botón '⚡ Crear tags y query types dinámicos'\n";
echo "5. En Bricks Builder:\n";
echo "   - Añadir elemento con Query Loop\n";
echo "   - Seleccionar 'Productos Test (Source)'\n";
echo "   - Verificar que funciona el loop\n\n";

echo "=== FIN DE LA VERIFICACIÓN ===\n";
