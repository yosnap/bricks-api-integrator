<?php
// Handlers AJAX para Sources

// --- AJAX para guardar Source ---
add_action('wp_ajax_save_api_source', function() {
    check_ajax_referer('save_api_source', 'nonce');
    require_once __DIR__ . '/../field-extractor.php';
    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = bricks_api_normalize_slug($name);
    $endpoint = sanitize_text_field($_POST['endpoint'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $params = $_POST['dynamic_params'] ?? [];
    $related = $_POST['related_endpoints'] ?? [];
    if (!$name || !$endpoint) {
        wp_send_json_error('Nombre y endpoint principal son obligatorios.');
    }
    $sources = get_option('bricks_api_sources', []);
    $sources[$slug] = [
        'name' => $name,
        'endpoint' => $endpoint,
        'items_path' => $items_path,
        'dynamic_params' => $params,
        'related_endpoints' => $related
    ];
    update_option('bricks_api_sources', $sources);
    wp_send_json_success(['sources' => $sources]);
});

// --- LÓGICA AJAX PARA TAGS DINÁMICOS DE SOURCES - CORREGIDO ---
add_action('wp_ajax_generate_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $endpoint_id = $source['endpoint_id'] ?? '';
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!isset($endpoints[$endpoint_id])) {
        wp_send_json_error('Endpoint no encontrado para este source');
    }
    $endpoint = $endpoints[$endpoint_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $dynamic_params = $source['dynamic_params'] ?? [];
    $pagination_config = [
        'type' => $source['pagination_type'] ?? '',
        'param' => $source['pagination_param'] ?? '',
        'per_page_param' => $source['per_page_param'] ?? '',
        'page' => 1,
        'per_page' => 10
    ];
    $overrides = [];
    foreach ($dynamic_params as $param) {
        $overrides[$param['name']] = $param['default'] ?? '';
    }
    $api_manager = new class { use APIManager; };
    $url = $api_manager->build_dynamic_api_url(
        $endpoint['url'],
        $dynamic_params,
        $pagination_config,
        $overrides
    );
    $args = [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Bricks API Integrator/2.1.1'
        ]
    ];
    if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] === 'basic') {
        $username = $endpoint['basic_user'] ?? $endpoint['auth_username'] ?? '';
        $password = $endpoint['basic_password'] ?? $endpoint['auth_password'] ?? '';
        if (!empty($username) && !empty($password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }
    }
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    $body = wp_remote_retrieve_body($response);
    $items = json_decode($body, true);
    // Lógica robusta para extraer el primer ítem correctamente
    if (is_array($items) && array_keys($items) === range(0, count($items) - 1)) {
        $first_item = isset($items[0]) ? $items[0] : [];
    } elseif (is_array($items) && count($items) > 0) {
        $first_item = $items;
    } elseif (is_object($items)) {
        $first_item = (array)$items;
    } else {
        $first_item = ['value' => $items];
    }
    if (empty($first_item) && !empty($items)) {
        $first_item = (array)$items;
    }
    if (empty($first_item) || !is_array($first_item)) {
        wp_send_json_error('No se pudo extraer ningún campo del primer item de la API.');
    }
    if (!function_exists('extract_tags_with_examples')) {
        require_once __DIR__ . '/sources-helpers.php';
    }
    $tags_with_examples = extract_tags_with_examples($first_item);
    $slug = bricks_api_normalize_slug($source['name']);
    $field_prefix = 'snap_';
    $tags_final = array_map(function($tagObj) use ($field_prefix, $slug) {
        $tag = is_array($tagObj) ? $tagObj['tag'] : $tagObj;
        $normalized = preg_replace('/[.\[\]]+/', '_', $tag);
        $normalized = preg_replace('/_+/', '_', $normalized);
        $normalized = trim($normalized, '_');
        return '{' . $field_prefix . $slug . '_' . $normalized . '}';
    }, $tags_with_examples);
    $query_type = '{' . $field_prefix . $slug . '}';
    $group_title = $source['name'] . ' (Source)';
    $query_types = get_option('bricks_api_generated_query_types', []);
    $tags_data = get_option('bricks_api_generated_tags', []);
    $query_types[$slug] = [
        'query_type' => $query_type,
        'endpoint_name' => $source['name'],
        'group_title' => $group_title,
        'url' => $source['endpoint_id'] ?? '',
        'fields' => array_column($tags_with_examples, 'tag'),
        'example' => $first_item,
        'field_prefix' => $field_prefix
    ];
    $tags_data[$slug] = [
        'tags' => $tags_final,
        'group_title' => $group_title,
        'endpoint_name' => $source['name'],
        'example' => $first_item,
        'field_prefix' => $field_prefix
    ];
    update_option('bricks_api_generated_query_types', $query_types);
    update_option('bricks_api_generated_tags', $tags_data);
    $sources = get_option('bricks_api_sources', []);
    if (isset($sources[$source_id])) {
        if (!trait_exists('APIManager')) {
            require_once __DIR__ . '/../api-manager.php';
        }
        $api_manager = new class { public static $api_cache = []; use APIManager; };
        $items = $api_manager->get_api_data_by_source_id($source_id, true);
        if (is_array($items) && array_keys($items) === range(0, count($items) - 1)) {
            $first_item = isset($items[0]) ? $items[0] : [];
        } elseif (is_array($items) && count($items) > 0) {
            $first_item = $items;
        } elseif (is_object($items)) {
            $first_item = (array)$items;
        } else {
            $first_item = ['value' => $items];
        }
        if (empty($first_item) && !empty($items)) {
            $first_item = (array)$items;
        }
        $sources[$source_id]['tags'] = $tags_final;
        $sources[$source_id]['example'] = $first_item;
        $sources[$source_id]['tags_generated'] = true;
        $sources[$source_id]['last_tag_generation'] = current_time('mysql');
        update_option('bricks_api_sources', $sources);
    }
    // --- Refuerzo para arrays, objetos y ejemplos ---
    // Validar raíz
    if (empty($first_item)) {
        wp_send_json_error('La respuesta de la API está vacía, no se pueden generar tags.');
    }
    $tag_example_data = $first_item;
    // Si la raíz es un array indexado y tiene al menos un objeto, usar el primer objeto
    if (is_array($first_item) && array_keys($first_item) === range(0, count($first_item) - 1)) {
        if (isset($first_item[0]) && is_array($first_item[0]) && count($first_item[0])) {
            $tag_example_data = $first_item[0];
        } else {
            wp_send_json_error('El array de la API está vacío o no contiene objetos válidos.');
        }
    } elseif (is_array($first_item) && !count($first_item)) {
        wp_send_json_error('El objeto de la API está vacío, no se pueden generar tags.');
    }
    require_once __DIR__ . '/../field-extractor.php';
    $slug = bricks_api_normalize_slug($source['name']);
    $tag_objects = function_exists('bricks_api_extract_tags_recursive')
        ? bricks_api_extract_tags_recursive($tag_example_data, 'snap_' . $slug)
        : [];
    // Si no se generaron tags, error claro
    if (empty($tag_objects)) {
        wp_send_json_error('No se pudieron generar tags dinámicos para la estructura recibida.');
    }
    // Para arrays y objetos, el campo example será el JSON completo
    $example_json = json_encode($tag_example_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    wp_send_json_success([
        'tags' => array_column($tag_objects, 'tag'),
        'tag_objects' => $tag_objects,
        'query_type' => $query_type,
        'endpoint_name' => $source['name'],
        'group_title' => $group_title,
        'url' => $source['endpoint_id'] ?? '',
        'fields' => array_column($tag_objects, 'tag'),
        'example' => $tag_example_data,
        'example_json' => $example_json,
        'field_prefix' => $field_prefix
    ]);
});

// --- AJAX para obtener tags y ejemplos de un Source ---
add_action('wp_ajax_get_tags_for_source', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $source_name = sanitize_text_field($_POST['source_name'] ?? '');
    $full_info = isset($_POST['full_info']) && $_POST['full_info'];
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
        return;
    }
    $source = $sources[$source_id];
    $tags = $source['tags'] ?? [];
    $example = $source['example'] ?? [];
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    $disabled = $source['disabled_tags'] ?? [];
    $tag_objects = [];
    if ($full_info && !empty($tags) && !empty($example) && is_array($example)) {
        if (!function_exists('bricks_api_extract_tags_recursive')) {
            require_once __DIR__ . '/sources-helpers.php';
        }
        $slug = bricks_api_normalize_slug($source['name']);
        $tag_example_data = $example;
        if (is_array($example) && array_keys($example) === range(0, count($example) - 1)) {
            if (isset($example[0]) && is_array($example[0]) && count($example[0])) {
                $tag_example_data = $example[0];
            } else {
                wp_send_json_error('El array de la API está vacío o no contiene objetos válidos.');
            }
        } elseif (is_array($example) && !count($example)) {
            wp_send_json_error('El objeto de la API está vacío, no se pueden generar tags.');
        }
        $tag_objects = function_exists('bricks_api_extract_tags_recursive')
            ? bricks_api_extract_tags_recursive($tag_example_data, 'snap_' . $slug)
            : [];
    }
    $items_path = $source['items_path'] ?? '';
    $example_json = !empty($example) ? json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    wp_send_json_success([
        'tags' => array_column($tag_objects, 'tag'),
        'disabled_tags' => $disabled,
        'example' => $tag_example_data,
        'example_json' => $example_json,
        'items_path' => $items_path,
        'tag_objects' => $tag_objects
    ]);
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    if (empty($example)) {
        if (!trait_exists('APIManager')) {
            require_once __DIR__ . '/../api-manager.php';
        }
        $api_manager = new class { public static $api_cache = []; use APIManager; };
        $items = $api_manager->get_api_data_by_source_id($source_id, true);
        $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
        $sources[$source_id]['example'] = $first_item;
        update_option('bricks_api_sources', $sources);
        if (defined('WP_DEBUG') && WP_DEBUG) {}
    }
});

// --- AJAX para obtener solo los tags de un Source ---
add_action('wp_ajax_get_source_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    check_ajax_referer('bricks_api_source_nonce', 'nonce');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $tags = $source['tags'] ?? [];
    $disabled = $source['disabled_tags'] ?? [];
    wp_send_json_success(['tags' => $tags, 'disabled_tags' => $disabled]);
});

// --- AJAX para activar/desactivar un tag de un Source ---
add_action('wp_ajax_toggle_source_tag', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $tag = sanitize_text_field($_POST['tag'] ?? '');
    $enabled = $_POST['enabled'] === 'true';
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $disabled = $source['disabled_tags'] ?? [];
    if ($enabled) {
        $disabled = array_diff($disabled, [$tag]);
    } else {
        if (!in_array($tag, $disabled)) $disabled[] = $tag;
    }
    $source['disabled_tags'] = array_values($disabled);
    $sources[$source_id] = $source;
    update_option('bricks_api_sources', $sources);
    wp_send_json_success(['disabled_tags' => $source['disabled_tags']]);
});

// --- AJAX para eliminar tags y query type de un Source ---
add_action('wp_ajax_delete_source_and_tags', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    $slug = bricks_api_normalize_slug($source['name']);
    $query_types = get_option('bricks_api_generated_query_types', []);
    $tags_data = get_option('bricks_api_generated_tags', []);
    unset($query_types[$slug]);
    unset($tags_data[$slug]);
    update_option('bricks_api_generated_query_types', $query_types);
    update_option('bricks_api_generated_tags', $tags_data);
    wp_send_json_success('Tags y query type eliminados.');
});

// --- AJAX para testear y refrescar un Source ---
add_action('wp_ajax_test_source_api', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $force_refresh = !empty($_POST['force_refresh']);
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    $items = $api_manager->get_api_data_by_source_id($source_id);
    if ($force_refresh) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $endpoint_id = $source['endpoint_id'] ?? '';
        $endpoint = $endpoints[$endpoint_id] ?? [];
        $url = $endpoint['url'] ?? '';
        $api_manager->get_api_data_with_cache($url, $endpoint, true);
        $items = $api_manager->get_api_data_by_source_id($source_id);
    }
    if (empty($items) || !is_array($items)) {
        wp_send_json_error('No se pudo obtener datos de la API o el array de items está vacío.');
    }
    $first_item = is_array($items) && isset($items[0]) && is_array($items[0]) ? $items[0] : (is_array($items) ? $items : []);
    $fields = is_array($first_item) ? array_keys($first_item) : [];
    $preview = esc_html(print_r($first_item, true));
    wp_send_json_success(['fields' => $fields, 'preview' => $preview]);
});

// --- AJAX para testear y refrescar un Source en vivo ---
add_action('wp_ajax_test_source_api_live', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source_nonce')) {
        wp_send_json_error('Nonce inválido.');
    }
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $force_refresh = !empty($_POST['force_refresh']);
    $param_names = isset($_POST['param_names']) ? (array)$_POST['param_names'] : [];
    $param_sources = isset($_POST['param_sources']) ? (array)$_POST['param_sources'] : [];
    $param_defaults = isset($_POST['param_defaults']) ? (array)$_POST['param_defaults'] : [];
    $endpoints = get_option('bricks_api_endpoints', []);
    if(!$endpoint_id || !isset($endpoints[$endpoint_id])){
        wp_send_json_error('Endpoint no encontrado.');
    }
    $endpoint = $endpoints[$endpoint_id];
    $dynamic_params = [];
    foreach ($param_names as $i => $name) {
        if (!empty($name)) {
            $dynamic_params[] = [
                'name' => $name,
                'source' => $param_sources[$i] ?? 'url',
                'default' => $param_defaults[$i] ?? ''
            ];
        }
    }
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $api_manager = new class { use APIManager; };
    $pagination_config = [
        'type' => $_POST['pagination_type'] ?? '',
        'param' => $_POST['pagination_param'] ?? '',
        'per_page_param' => $_POST['per_page_param'] ?? '',
        'page' => 1,
        'per_page' => 10
    ];
    $overrides = [];
    foreach ($dynamic_params as $param) {
        $overrides[$param['name']] = $param['default'];
    }
    $url = $api_manager->build_dynamic_api_url(
        $endpoint['url'],
        $dynamic_params,
        $pagination_config,
        $overrides
    );
    $args = [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Bricks API Integrator/2.1.1'
        ]
    ];
    if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] === 'basic') {
        $username = $endpoint['basic_user'] ?? $endpoint['auth_username'] ?? '';
        $password = $endpoint['basic_password'] ?? $endpoint['auth_password'] ?? '';
        if (!empty($username) && !empty($password)) {
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
        }
    }
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('TEST SOURCE DEBUG - HTTP STATUS: ' . $status_code);
        error_log('TEST SOURCE DEBUG - RAW BODY: ' . $body);
    }
    if ($status_code !== 200) {
        wp_send_json_error('Error HTTP: ' . $status_code);
    }
    $raw_data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error('Error de JSON: ' . json_last_error_msg());
    }
    $items_data = $raw_data;
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        foreach ($path_parts as $part) {
            if (is_array($items_data) && isset($items_data[$part])) {
                $items_data = $items_data[$part];
            } else {
                wp_send_json_error('Items path "' . $items_path . '" no encontrado en la respuesta.');
            }
        }
    }
    $preview = '';
    $fields = [];
    if (is_array($items_data)) {
        $is_indexed = array_keys($items_data) === range(0, count($items_data) - 1);
        if ($is_indexed && count($items_data) > 0) {
            $first_item = $items_data[0];
            $preview = esc_html(print_r($first_item, true));
            $fields = is_array($first_item) ? array_keys($first_item) : [];
        } elseif (count($items_data) > 0) {
            $preview = esc_html(print_r($items_data, true));
            $fields = array_keys($items_data);
        } else {
            wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
        }
    } elseif (is_object($items_data)) {
        $preview = esc_html(print_r($items_data, true));
        $fields = array_keys(get_object_vars($items_data));
    } elseif (!empty($items_data) || $items_data === 0 || $items_data === '0') {
        $preview = esc_html(print_r($items_data, true));
        $fields = [];
    } else {
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    wp_send_json_success(['fields' => $fields, 'preview' => $preview]);
});

// --- AJAX para probar ruta de items ---
add_action('wp_ajax_test_items_path', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    if (empty($endpoint_id)) {
        wp_send_json_error('ID de endpoint no proporcionado');
        return;
    }
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!isset($endpoints[$endpoint_id])) {
        wp_send_json_error('Endpoint no encontrado');
        return;
    }
    $endpoint = $endpoints[$endpoint_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    $data = $api_manager->get_api_data_with_cache($endpoint['url'], $endpoint);
    if (empty($data) || !is_array($data)) {
        wp_send_json_error('No se pudo obtener datos de la API o el array de items está vacío.');
    }
    $items = $data;
    if (!empty($items_path)) {
        $path_parts = explode('.', $items_path);
        foreach ($path_parts as $part) {
            if (is_array($items) && isset($items[$part])) {
                $items = $items[$part];
            } else {
                wp_send_json_error('Items path "' . $items_path . '" no encontrado en la respuesta.');
            }
        }
    }
    $preview = '';
    if (is_array($items)) {
        $is_indexed = array_keys($items) === range(0, count($items) - 1);
        if ($is_indexed && count($items) > 0) {
            $first_item = $items[0];
            $preview = esc_html(print_r($first_item, true));
        } elseif (count($items) > 0) {
            $preview = esc_html(print_r($items, true));
        } else {
            wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
        }
    } elseif (is_object($items)) {
        $preview = esc_html(print_r($items, true));
    } elseif (!empty($items) || $items === 0 || $items === '0') {
        $preview = esc_html(print_r($items, true));
    } else {
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    wp_send_json_success(['preview' => $preview]);
});

// --- AJAX para preview de Source ---
add_action('wp_ajax_preview_source_api', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bricks_api_source_nonce')) {
        wp_send_json_error('Nonce inválido.');
    }
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    $sources = get_option('bricks_api_sources', []);
    if (empty($source_id) || !isset($sources[$source_id])) {
        wp_send_json_error('Source no encontrado');
    }
    $source = $sources[$source_id];
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $api_manager = new class { public static $api_cache = []; use APIManager; };
    $items = $api_manager->get_api_data_by_source_id($source_id, true);
    $available_keys = [];
    if (is_array($items)) {
        $available_keys = array_keys($items);
    } elseif (is_object($items)) {
        $available_keys = array_keys(get_object_vars($items));
    }
    $items_path = !empty($source['items_path']) ? $source['items_path'] : '';
    $items_data = $items;
    // --- Lógica uniforme para preview ---
    if (is_array($items_data) && array_keys($items_data) === range(0, count($items_data) - 1)) {
        // Array indexado: usar primer elemento
        $preview = isset($items_data[0]) ? $items_data[0] : [];
    } elseif (is_array($items_data)) {
        // Array asociativo: usar tal cual
        $preview = $items_data;
    } elseif (is_object($items_data)) {
        $preview = (array)$items_data;
    } else {
        $preview = ['value' => $items_data];
    }
    if (is_object($preview)) {
        $preview = json_decode(json_encode($preview), true);
    }
    $fields = is_array($preview) ? array_keys($preview) : [];
    $total_items = (is_array($items_data) && array_keys($items_data) === range(0, count($items_data) - 1)) ? count($items_data) : (is_array($items_data) ? 1 : 0);
    $endpoint_info = [];
    if (!empty($source['endpoint_id'])) {
        $endpoints = get_option('bricks_api_endpoints', []);
        if (isset($endpoints[$source['endpoint_id']])) {
            $endpoint = $endpoints[$source['endpoint_id']];
            $endpoint_info = [
                'name' => $endpoint['name'] ?? '',
                'url' => $endpoint['url'] ?? '',
                'method' => $endpoint['method'] ?? 'GET',
                'auth_type' => $endpoint['auth_type'] ?? 'none'
            ];
        }
    }
    $items_path_info = !empty($source['items_path']) ? $source['items_path'] : 'Raíz de la respuesta';
    wp_send_json_success([
        'fields' => $fields,
        'preview' => $preview,
        'total_items' => $total_items,
        'endpoint' => $endpoint_info,
        'items_path' => $items_path_info,
        'raw_data' => $items_data // respuesta original de la API
    ]);
});
