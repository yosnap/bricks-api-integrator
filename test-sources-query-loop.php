<?php
/**
 * Script de Testing para Query Loop de Sources
 * 
 * Este archivo simula y prueba la funcionalidad de Sources con items_path
 */

// Simular un endpoint con estructura anidada típica
$mock_api_response = [
    'status' => 'success',
    'meta' => [
        'total' => 5,
        'page' => 1
    ],
    'data' => [
        'productos' => [
            [
                'id' => 1,
                'nombre' => 'Producto 1',
                'precio' => 100,
                'categoria' => 'Electrónicos',
                'disponible' => true,
                'detalles' => [
                    'marca' => 'Samsung',
                    'modelo' => 'ABC123'
                ]
            ],
            [
                'id' => 2,
                'nombre' => 'Producto 2', 
                'precio' => 200,
                'categoria' => 'Hogar',
                'disponible' => false,
                'detalles' => [
                    'marca' => 'LG',
                    'modelo' => 'XYZ456'
                ]
            ],
            [
                'id' => 3,
                'nombre' => 'Producto 3',
                'precio' => 150,
                'categoria' => 'Deportes',
                'disponible' => true,
                'detalles' => [
                    'marca' => 'Nike',
                    'modelo' => 'DEF789'
                ]
            ]
        ]
    ]
];

echo "=== TESTING QUERY LOOP SOURCES ===\n\n";

echo "1. ESTRUCTURA DE API SIMULADA:\n";
echo json_encode($mock_api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "2. CASOS DE PRUEBA:\n\n";

// Caso 1: Sin items_path (datos directos)
echo "CASO 1 - Sin items_path:\n";
echo "- Debería devolver: Array completo (con status, meta, data)\n";
echo "- Elementos en loop: 1 (el objeto completo)\n\n";

// Caso 2: Con items_path = "data" 
echo "CASO 2 - items_path = 'data':\n";
echo "- Debería devolver: Solo el contenido de 'data'\n";
echo "- Elementos en loop: 1 (el objeto data)\n\n";

// Caso 3: Con items_path = "data.productos" (lo que queremos)
echo "CASO 3 - items_path = 'data.productos':\n";
echo "- Debería devolver: Array de productos\n";
echo "- Elementos en loop: 3 (cada producto individual)\n";
echo "- Tags disponibles: {snap_productos_id}, {snap_productos_nombre}, etc.\n\n";

// Simulación de función extract_nested_items
function test_extract_nested_items($data, $items_path) {
    if (empty($items_path) || empty($data)) {
        return $data;
    }
    
    $path_parts = explode('.', $items_path);
    $current_data = $data;
    
    echo "Navegando path: " . $items_path . "\n";
    foreach ($path_parts as $part) {
        echo "- Buscando: '{$part}' en nivel actual\n";
        if (is_array($current_data) && isset($current_data[$part])) {
            $current_data = $current_data[$part];
            echo "  ✅ Encontrado: " . (is_array($current_data) ? count($current_data) . " elementos" : "valor escalar") . "\n";
        } elseif (is_object($current_data) && isset($current_data->$part)) {
            $current_data = $current_data->$part;
            echo "  ✅ Encontrado en objeto\n";
        } else {
            echo "  ❌ NO encontrado\n";
            return [];
        }
    }
    
    return is_array($current_data) ? $current_data : [$current_data];
}

echo "3. SIMULACIÓN DE EXTRACT_NESTED_ITEMS:\n\n";

echo "TEST items_path = 'data.productos':\n";
$extracted = test_extract_nested_items($mock_api_response, 'data.productos');
echo "Resultado: " . count($extracted) . " elementos extraídos\n";
echo "Primer elemento: " . json_encode($extracted[0] ?? null, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "4. ESTRUCTURA ESPERADA EN BRICKS:\n\n";
echo "Cada elemento del array se convierte en un pseudo-post:\n";
foreach ($extracted as $index => $item) {
    echo "Elemento " . ($index + 1) . ":\n";
    echo "- ID: " . ($index + 1) . "\n";
    echo "- post_title: " . ($item['nombre'] ?? 'Sin título') . "\n";
    echo "- api_data: " . json_encode($item, JSON_UNESCAPED_UNICODE) . "\n";
    echo "- Tags disponibles:\n";
    foreach ($item as $field => $value) {
        if (is_array($value)) {
            foreach ($value as $subfield => $subvalue) {
                echo "  • {snap_productos_{$field}_{$subfield}}: {$subvalue}\n";
            }
        } else {
            echo "  • {snap_productos_{$field}}: {$value}\n";
        }
    }
    echo "\n";
}

echo "=== FIN DEL TEST ===\n";
