<?php
// Hooks y filtros para integración de sources personalizados en Bricks

// Registro seguro de la clase y filtro solo si Bricks está cargado
add_action('init', function() {
    if (defined('BRICKS_VERSION') && class_exists('Bricks_Query_Provider')) {
        add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
        register_api_source_query_class();
    }
}, 20);

// Registro seguro de la clase y filtro SOLO cuando Bricks ha cargado completamente
if (has_action('bricks/loaded')) {
    add_action('bricks/loaded', function() {
        if (class_exists('Bricks_Query_Provider')) {
            add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
            register_api_source_query_class();
        }
    }, 20);
} else {
    add_action('after_setup_theme', function() {
        if (defined('BRICKS_VERSION') && class_exists('Bricks_Query_Provider')) {
            add_filter('bricks/query/sources', 'register_api_sources_with_bricks');
            register_api_source_query_class();
        }
    }, 20);
}

// Registro de Query Types de Sources como tipos estándar (sin clase personalizada)
add_filter('bricks/query/sources', function($sources) {
    $api_sources = get_option('bricks_api_sources', []);
    if (empty($api_sources)) return $sources;
    foreach ($api_sources as $source_id => $source) {
        $display_name = isset($source['query_type_name']) ? $source['query_type_name'] : $source['name'];
        $sources['source_' . $source_id] = [
            'name' => $display_name,
        ];
    }
    return $sources;
}, 20);

// Devolver datos para el loop de Bricks desde Sources
add_filter('bricks/query/run', function($results, $query_obj) {
    $object_type = isset($query_obj->object_type) ? $query_obj->object_type : '';
    if (strpos($object_type, 'source_') !== 0) return $results;
    $source_id = str_replace('source_', '', $object_type);
    $api_sources = get_option('bricks_api_sources', []);
    if (!isset($api_sources[$source_id])) return $results;
    $source = $api_sources[$source_id];
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
    if (!isset($endpoints[$endpoint_id])) return $results;
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
    if (empty($endpoint_url)) return $results;
    if (!function_exists('get_api_data')) require_once __DIR__ . '/../api-manager.php';
    $data = get_api_data($endpoint_url, $endpoint);
    if (empty($data) || !is_array($data)) return $results;
    $items_path = isset($source['items_path']) ? $source['items_path'] : '';
    $items = $data;
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        foreach ($path_parts as $part) {
            if (isset($items[$part])) {
                $items = $items[$part];
            } else {
                return $results;
            }
        }
    }
    if (!is_array($items)) $items = [$items];
    $formatted = [];
    foreach ($items as $item) {
        $formatted[] = is_array($item) ? (object)$item : $item;
    }
    return [
        'items' => $formatted,
        'count' => count($formatted),
        'found_posts' => count($formatted),
        'post_count' => count($formatted),
    ];
}, 20, 2);
