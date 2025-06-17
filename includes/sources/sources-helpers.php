<?php
// Helpers generales para Sources

// Obtener datos de ejemplo para Sources API en Bricks
function get_api_source_sample_data($results, $query_obj) {
    // Solo procesar fuentes API
    if (!isset($query_obj->settings['source']) || strpos($query_obj->settings['source'], 'api_source') !== 0) {
        return $results;
    }
    $source_id = isset($query_obj->settings['source_id']) ? $query_obj->settings['source_id'] : '';
    if (empty($source_id)) {
        return $results;
    }
    $api_sources = get_option('bricks_api_sources', []);
    if (!isset($api_sources[$source_id])) {
        return $results;
    }
    $source = $api_sources[$source_id];
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
    if (!isset($endpoints[$endpoint_id])) {
        return $results;
    }
    $endpoint = $endpoints[$endpoint_id];
    $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
    if (empty($endpoint_url)) {
        return $results;
    }
    $data = get_api_data($endpoint_url, $endpoint);
    if (empty($data) || !is_array($data)) {
        return $results;
    }
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
    if (!is_array($items)) {
        $items = [$items];
    }
    $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
    if (!empty($field_prefix)) {
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $prefixed_item = [];
                foreach ($item as $item_key => $item_value) {
                    $prefixed_item[$field_prefix . $item_key] = $item_value;
                }
                $items[$key] = $prefixed_item;
            }
        }
    }
    $items = array_slice($items, 0, 10);
    foreach ($items as $key => $item) {
        if (is_array($item)) {
            $items[$key]['_api_url'] = $endpoint_url;
        }
    }
    return [
        'count' => count($items),
        'items' => $items,
    ];
}

// Extraer tags con ejemplos de un ítem (recursivo)
function extract_tags_with_examples($item, $prefix = '') {
    $tags = [];
    foreach ($item as $key => $value) {
        $path = $prefix ? $prefix . '.' . $key : $key;
        if (is_array($value) && !empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
            $tags = array_merge($tags, extract_tags_with_examples($value, $path));
        } else if (is_array($value) && !empty($value)) {
            if (is_array($value[0] ?? null)) {
                $tags = array_merge($tags, extract_tags_with_examples($value[0], $path . '[0]'));
            } else {
                $tags[] = ['tag' => $path, 'example' => json_encode($value)];
            }
        } else {
            $tags[] = ['tag' => $path, 'example' => is_scalar($value) ? $value : json_encode($value)];
        }
    }
    return $tags;
}
