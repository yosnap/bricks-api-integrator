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

// --- LÓGICA AJAX PARA TAGS DINÁMICOS DE SOURCES - UNIFICADA CON TEST SOURCE ---
add_action('wp_ajax_generate_source_tags', function() {
    // --- DEBUG LOG ---
    $debug = (isset($_GET['debug']) && $_GET['debug'] == '1') || (isset($_POST['debug']) && $_POST['debug'] == '1');
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    $log_file = '/tmp/bricks_api_debug.log';
    function bricks_api_debug_log($data, $context = '', $log_file = '/tmp/bricks_api_debug.log') {
        $entry = date('Y-m-d H:i:s') . " [$context] " . print_r($data, true) . "\n";
        file_put_contents($log_file, $entry, FILE_APPEND);
    }
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
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
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

    // Usar sistema unificado de autenticación (soporta Bearer, API Key, Basic Auth)
    if (!function_exists('bricks_api_proxy_prepare_auth_headers')) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/image-proxy.php';
    }
    $auth_headers = bricks_api_proxy_prepare_auth_headers($endpoint);
    $args['headers'] = array_merge($args['headers'], $auth_headers);

    $response = wp_remote_get($url, $args);
    // Log de depuración de la respuesta HTTP
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log('DEBUG API HTTP STATUS: ' . $status_code);
        error_log('DEBUG API RAW BODY: ' . $body);
    }
    if (is_wp_error($response)) {
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    $body = wp_remote_retrieve_body($response);
    $items = json_decode($body, true);
    if ($debug && !$is_ajax) {
        echo '<pre style="background:#222;color:#fff;padding:10px;">[DEBUG generate_source_tags] BODY:\n' . htmlspecialchars($body) . '\n\nITEMS:\n' . print_r($items, true) . '</pre>';
    }
    bricks_api_debug_log([
        'source_id' => $source_id,
        'handler' => 'generate_source_tags',
        'body' => $body,
        'items' => $items
    ], 'generate_source_tags', $log_file);
    // --- Aplicar items_path si está configurado ---
    $items_path = $source['items_path'] ?? '';
    $items_data = $items;
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
    // --- Normalizar la respuesta: siempre array indexado para generación de tags ---
    if (is_object($items_data)) {
        $items_data = [ (array)$items_data ];
    } elseif (is_array($items_data) && count($items_data) > 0 && array_keys($items_data) !== range(0, count($items_data) - 1)) {
        // Es un array asociativo (objeto plano)
        $items_data = [ $items_data ];
    } elseif (!is_array($items_data)) {
        $items_data = [ $items_data ];
    }
    // Usar el primer elemento para generar los tags
    $first_item = isset($items_data[0]) ? $items_data[0] : [];
    // Log detallado para depuración
    error_log('DEBUG TAGS $first_item: ' . print_r($first_item, true));
    // Validar que el objeto tenga campos útiles
    if (empty($first_item) || !is_array($first_item) || count(array_filter(array_keys($first_item), 'is_string')) === 0) {
        wp_send_json_error('La respuesta de la API no contiene datos válidos para generar tags.');
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
    $sources[$source_id]['tags'] = $tags_final;
    $sources[$source_id]['example'] = $first_item;
    $sources[$source_id]['tags_generated'] = true;
    $sources[$source_id]['last_tag_generation'] = current_time('mysql');
    update_option('bricks_api_sources', $sources);
    // --- Generar datos para la respuesta AJAX ---
    $tag_example_data = $first_item;
    $tag_objects = function_exists('bricks_api_extract_tags_recursive')
        ? bricks_api_extract_tags_recursive($tag_example_data, 'snap_' . $slug)
        : [];
    if (empty($tag_objects)) {
        wp_send_json_error('No se pudieron generar tags dinámicos para la estructura recibida.');
    }
    $example_json = json_encode($tag_example_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $debug_flag = isset($_GET['debug_tags']) || isset($_POST['debug_tags']);
    if ($debug_flag) {
        echo '<div style="background:#222;color:#fff;padding:10px;margin-bottom:10px;">';
        echo '<b>DEBUG generate_source_tags - BODY:</b><br><pre>' . htmlspecialchars($body) . '</pre>';
        echo '<b>DEBUG generate_source_tags - ITEMS:</b><br><pre>' . print_r($items, true) . '</pre>';
        echo '</div>';
    }
    error_log('DEBUG FINAL $items: ' . print_r($items, true));
    error_log('DEBUG FINAL $first_item: ' . print_r($first_item, true));
    $json_api_response = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $url_real = $url;
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
        'field_prefix' => $field_prefix,
        'api_response_json' => $json_api_response,
        'url_real' => $url_real
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
        // Si es array indexado, tomar siempre el primer objeto válido
        if (is_array($example) && array_keys($example) === range(0, count($example) - 1)) {
            $found = false;
            foreach ($example as $item) {
                if (is_array($item) && count($item)) {
                    $tag_example_data = $item;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
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
    // --- Datos del endpoint asociado ---
    $endpoint_id = $source['endpoint_id'] ?? '';
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint = $endpoints[$endpoint_id] ?? [];
    $endpoint_info = [
        'name' => $endpoint['name'] ?? '-',
        'url' => $endpoint['url'] ?? '-',
        'method' => $endpoint['method'] ?? '-',
        'auth_type' => $endpoint['auth_type'] ?? '-',
    ];
    // --- url_real y total_items ---
    $url_real = $endpoint['url'] ?? '-';
    $total_items = is_array($example) ? count($example) : (is_array($example_json) ? count($example_json) : '-');
    // --- preview_type ---
    $preview_type = 'object';
    if (is_array($example) && array_keys($example) === range(0, count($example) - 1)) {
        $preview_type = 'array';
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {}
    wp_send_json_success([
        'tags' => array_column($tag_objects, 'tag'),
        'disabled_tags' => $disabled,
        'example' => $tag_example_data,
        'example_json' => $example_json,
        'items_path' => $items_path,
        'tag_objects' => $tag_objects,
        'endpoint' => $endpoint_info,
        'url_real' => $url_real,
        'total_items' => $total_items,
        'preview_type' => $preview_type
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
    $preview = $first_item;
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
    if ($endpoint_id === '' || !isset($endpoints[$endpoint_id])) {
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

    // Usar sistema unificado de autenticación (soporta Bearer, API Key, Basic Auth)
    if (!function_exists('bricks_api_proxy_prepare_auth_headers')) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/image-proxy.php';
    }
    $auth_headers = bricks_api_proxy_prepare_auth_headers($endpoint);
    $args['headers'] = array_merge($args['headers'], $auth_headers);

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
    $preview = null;
    if (is_array($items_data)) {
        $is_indexed = array_keys($items_data) === range(0, count($items_data) - 1);
        if ($is_indexed && count($items_data) > 0) {
            $first_item = $items_data[0];
            $preview = $first_item;
        } elseif (count($items_data) > 0) {
            $preview = $items_data;
        } else {
            wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
        }
    } elseif (is_object($items_data)) {
        $preview = (array)$items_data;
    } elseif (!empty($items_data) || $items_data === 0 || $items_data === '0') {
        $preview = $items_data;
    } else {
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    wp_send_json_success(['fields' => array_keys($preview), 'preview' => $preview]);
});

// --- AJAX para probar ruta de items ---
add_action('wp_ajax_test_items_path', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('No autorizado');
    }
    $endpoint_id = sanitize_text_field($_POST['endpoint_id'] ?? '');
    $items_path = sanitize_text_field($_POST['items_path'] ?? '');
    $source_id = sanitize_text_field($_POST['source_id'] ?? '');
    if ($endpoint_id === '' || $endpoint_id === null) {
        wp_send_json_error('ID de endpoint no proporcionado');
        return;
    }
    $endpoints = get_option('bricks_api_endpoints', []);
    if ($endpoint_id === '' || !isset($endpoints[$endpoint_id])) {
        wp_send_json_error('Endpoint no encontrado.');
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
    $preview = null;
    if (is_array($items)) {
        $is_indexed = array_keys($items) === range(0, count($items) - 1);
        if ($is_indexed && count($items) > 0) {
            $first_item = $items[0];
            $preview = $first_item;
        } elseif (count($items) > 0) {
            $preview = $items;
        } else {
            wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
        }
    } elseif (is_object($items)) {
        $preview = (array)$items;
    } elseif (!empty($items) || $items === 0 || $items === '0') {
        $preview = $items;
    } else {
        wp_send_json_error('La API no devolvió datos o el array de items está vacío.');
    }
    wp_send_json_success(['preview' => $preview]);
});

// --- AJAX para preview de Source ---
add_action('wp_ajax_preview_source_api', function() {
    // --- DEBUG LOG ---
    $debug = (isset($_GET['debug']) && $_GET['debug'] == '1') || (isset($_POST['debug']) && $_POST['debug'] == '1');
    $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    $log_file = '/tmp/bricks_api_debug.log';
    function bricks_api_debug_log($data, $context = '', $log_file = '/tmp/bricks_api_debug.log') {
        $entry = date('Y-m-d H:i:s') . " [$context] " . print_r($data, true) . "\n";
        file_put_contents($log_file, $entry, FILE_APPEND);
    }
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
    if ($debug && !$is_ajax) {
        echo '<pre style="background:#222;color:#fff;padding:10px;">[DEBUG preview_source_api] ITEMS:\n' . print_r($items, true) . '</pre>';
    }
    bricks_api_debug_log([
        'source_id' => $source_id,
        'handler' => 'preview_source_api',
        'items' => $items
    ], 'preview_source_api', $log_file);
    $available_keys = [];
    if (is_array($items)) {
        $available_keys = array_keys($items);
    } elseif (is_object($items)) {
        $available_keys = array_keys(get_object_vars($items));
    }
    $items_path = !empty($source['items_path']) ? $source['items_path'] : '';
    $items_data = $items;
    // --- Construir URL con parámetros dinámicos de ejemplo (igual que Test Source) ---
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
    if (!trait_exists('APIManager')) {
        require_once __DIR__ . '/../api-manager.php';
    }
    $api_manager = new class { use APIManager; };
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint = $endpoints[$source['endpoint_id']] ?? [];
    $url = $api_manager->build_dynamic_api_url(
        $endpoint['url'] ?? '',
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

    // Usar sistema unificado de autenticación (soporta Bearer, API Key, Basic Auth)
    if (!function_exists('bricks_api_proxy_prepare_auth_headers')) {
        require_once BRICKS_API_INTEGRATOR_PATH . 'includes/image-proxy.php';
    }
    $auth_headers = bricks_api_proxy_prepare_auth_headers($endpoint);
    $args['headers'] = array_merge($args['headers'], $auth_headers);

    $response = wp_remote_get($url, $args);
    // Log de depuración de la respuesta HTTP
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        error_log('DEBUG API HTTP STATUS: ' . $status_code);
        error_log('DEBUG API RAW BODY: ' . $body);
    }
    if (is_wp_error($response)) {
        wp_send_json_error('Error de API: ' . $response->get_error_message());
    }
    $body = wp_remote_retrieve_body($response);
    $items = json_decode($body, true);
    $items_path = !empty($source['items_path']) ? $source['items_path'] : '';
    $items_data = $items;
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
    // --- Lógica uniforme para preview reforzada y robusta ---
    $preview = [];
    $preview_type = 'object';
    $es_objeto_plano = false;
    // Caso 1: Array de un solo objeto
    if (is_array($items_data) && count($items_data) === 1 && is_array($items_data[0]) && array_keys($items_data[0]) !== range(0, count($items_data[0]) - 1)) {
        $preview = $items_data[0];
        $es_objeto_plano = true;
    // Caso 2: Objeto plano directamente
    } elseif (is_array($items_data) && count($items_data) > 0 && array_keys($items_data) !== range(0, count($items_data) - 1)) {
        $preview = $items_data;
        $es_objeto_plano = true;
    // Caso 3: Array indexado
    } elseif (is_array($items_data) && array_keys($items_data) === range(0, count($items_data) - 1)) {
        $preview = isset($items_data[0]) ? $items_data[0] : [];
        $preview_type = 'array';
    // Caso 4: Objeto (stdClass)
    } elseif (is_object($items_data)) {
        $preview = (array)$items_data;
        $es_objeto_plano = true;
    } else {
        $preview = (object)[];
        $preview_type = 'object';
    }
    // Si es objeto plano (array asociativo u objeto), reemplazar placeholders en la URL con valores reales
    if ($es_objeto_plano && is_array($preview)) {
        foreach ($dynamic_params as $param) {
            $param_name = $param['name'];
            if (isset($preview[$param_name]) && $preview[$param_name] !== '') {
                $overrides[$param_name] = $preview[$param_name];
            }
        }
        $url_real = $api_manager->build_dynamic_api_url(
            $endpoint['url'],
            $dynamic_params,
            $pagination_config,
            $overrides
        );
        $preview_type = 'object';
    }
    if (is_object($preview)) {
        $preview = json_decode(json_encode($preview), true);
    }
    if (!isset($url_real)) {
        $url_real = $url;
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
    // Log de depuración para ver el preview, overrides y url_real
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('DEBUG PREVIEW: ' . print_r($preview, true));
        error_log('DEBUG OVERRIDES: ' . print_r($overrides, true));
        error_log('DEBUG URL_REAL: ' . print_r($url_real, true));
    }
    wp_send_json_success([
        'fields' => $fields,
        'preview' => $preview,
        'total_items' => $total_items,
        'endpoint' => $endpoint_info,
        'items_path' => $items_path_info,
        'raw_data' => $items_data, // respuesta original de la API
        'url_real' => $url_real,
        'preview_type' => $preview_type
    ]);
});

// Función para obtener datos de la API
function get_api_data($url, $endpoint) {
    if (empty($url)) return [];
    
    $method = isset($endpoint['method']) ? strtoupper($endpoint['method']) : 'GET';
    $headers = isset($endpoint['headers']) ? $endpoint['headers'] : [];
    $body = isset($endpoint['body']) ? $endpoint['body'] : '';
    
    // Convertir headers de formato visual a formato para la petición
    $formatted_headers = [];
    foreach ($headers as $header) {
        if (isset($header['key']) && isset($header['value'])) {
            $formatted_headers[$header['key']] = $header['value'];
        }
    }
    
    $args = [
        'method' => $method,
        'headers' => $formatted_headers,
        'timeout' => 30,
    ];
    
    if ($method === 'POST' && !empty($body)) {
        $args['body'] = $body;
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS API DEBUG: Realizando petición a ' . $url);
    }
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS API ERROR: ' . $response->get_error_message());
        }
        return [];
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS API ERROR: Error decodificando JSON - ' . json_last_error_msg());
        }
        return [];
    }
    
    return $data;
}
