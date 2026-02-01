<?php
// Hooks y filtros para integración de sources personalizados en Bricks

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('BRICKS API DEBUG: sources-hooks.php incluido');
}

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

// Devolver datos para el loop de Bricks desde Sources
add_filter('bricks/query/run', function($results, $query_obj) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS API DEBUG: bricks/query/run ejecutado para ' . $query_obj->object_type);
    }

    $object_type = isset($query_obj->object_type) ? $query_obj->object_type : '';

    // Obtener sources PRIMERO para poder verificar
    $api_sources = get_option('bricks_api_sources', []);

    // Aceptar tanto 'source_' como 'snap_source_' como prefijo
    $source_id = '';
    if (strpos($object_type, 'snap_source_') === 0) {
        $source_id = str_replace('snap_source_', '', $object_type);
    } elseif (strpos($object_type, 'source_') === 0) {
        $source_id = str_replace('source_', '', $object_type);
    } else {
        return $results;
    }

    // Normalizar: convertir guiones a guiones bajos para coincidir con keys de sources
    $source_id_normalized = str_replace('-', '_', $source_id);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS API DEBUG: source_id=' . $source_id . ' | normalized=' . $source_id_normalized);
        error_log('BRICKS API DEBUG: api_sources keys=' . implode(', ', array_keys($api_sources)));
    }

    // Intentar encontrar el source con diferentes variantes del ID
    $source = null;
    $possible_ids = [
        $source_id,
        $source_id_normalized,
        'query_type_' . $source_id,
        'query_type_' . $source_id_normalized,
    ];

    foreach ($possible_ids as $try_id) {
        if (isset($api_sources[$try_id])) {
            $source_id = $try_id;
            $source = $api_sources[$try_id];
            break;
        }
    }

    if (!$source) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS API DEBUG: Source no encontrado para ' . $object_type);
        }
        return $results;
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS API DEBUG: Source encontrado: ' . $source_id);
    }
    
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
    
    // --- Obtener parámetros dinámicos y contexto del post actual ---
    $dynamic_params = isset($source['dynamic_params']) ? $source['dynamic_params'] : [];
    $pagination_config = [
        'type' => $source['pagination_type'] ?? '',
        'param' => $source['pagination_param'] ?? '',
        'per_page_param' => $source['per_page_param'] ?? '',
        'page' => 1,
        'per_page' => 10
    ];
    
    // Obtener query_vars de WordPress
    global $wp_query;
    $query_vars = isset($wp_query->query_vars) ? $wp_query->query_vars : [];
    
    $overrides = [];
    foreach ($dynamic_params as $param) {
        $param_name = $param['name'];
        $param_source = $param['source'] ?? 'url';
        $param_default = $param['default'] ?? '';
        $param_required = $param['required'] ?? false;
        
        // Primero intentar obtener el valor de query_vars de WordPress
        if (isset($query_vars[$param_name])) {
            $overrides[$param_name] = sanitize_text_field($query_vars[$param_name]);
            continue;
        }

        // Si no está en query_vars, obtener valor según el origen
        switch ($param_source) {
            case 'post':
                global $post;
                $value = isset($post->post_name) ? $post->post_name : null;
                if ($value !== null || $param_required) {
                    $overrides[$param_name] = $value !== null ? $value : $param_default;
                }
                break;
            case 'user':
                $current_user = wp_get_current_user();
                $value = $current_user->exists() ? $current_user->ID : null;
                if ($value !== null || $param_required) {
                    $overrides[$param_name] = $value !== null ? $value : $param_default;
                }
                break;
            case 'static':
                $overrides[$param_name] = $param_default;
                break;
            case 'url':
            default:
                $value = isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : null;
                if ($value !== null || $param_required) {
                    $overrides[$param_name] = $value !== null ? $value : $param_default;
                }
                break;
        }
    }
    
    // --- Construir la URL final usando build_dynamic_api_url ---
    if (!trait_exists('APIManager')) require_once __DIR__ . '/../api-manager.php';
    $api_manager = new class { use APIManager; };
    $final_url = $api_manager->build_dynamic_api_url(
        $endpoint_url,
        $dynamic_params,
        $pagination_config,
        $overrides
    );
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('BRICKS API DEBUG: URL final: ' . $final_url);
    }

    // Usar función helper que soporta Inmovilla
    if (!function_exists('bricks_api_source_make_request')) {
        require_once __DIR__ . '/sources-helpers.php';
    }

    $api_result = bricks_api_source_make_request($final_url, $endpoint, $source);

    if (!$api_result['success']) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BRICKS API DEBUG: Error en petición: ' . $api_result['error']);
        }
        return $results;
    }

    $data = $api_result['data'];
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

    // Inmovilla: el primer elemento de 'paginacion' es metadata, saltarlo
    if (is_array($items) && isset($items[0]['total']) && !isset($items[0]['cod_ofer'])) {
        array_shift($items);
    }

    // --- Normalización robusta: siempre array indexado de arrays ---
    if (is_object($items)) {
        $items = [ (array)$items ];
    } elseif (is_array($items) && count($items) > 0 && array_keys($items) !== range(0, count($items) - 1)) {
        // Es un array asociativo (objeto plano)
        $items = [ $items ];
    } elseif (!is_array($items)) {
        $items = [ $items ];
    }
    
    // Normalizar y copiar campos a la raíz de cada item
    $formatted = [];
    foreach ($items as $item) {
        if (is_object($item)) {
            $formatted[] = $item;
        } elseif (is_array($item)) {
            // Si el array es asociativo (claves string), lo convertimos a objeto normal
            if (array_keys($item) !== range(0, count($item) - 1)) {
                $formatted[] = (object)$item;
            } else {
                // Si es un array indexado (lista), lo metemos como propiedad 'value'
                $formatted[] = (object)['value' => $item];
            }
        } else {
            // Si es escalar, lo metemos como propiedad 'value'
            $formatted[] = (object)['value' => $item];
        }
    }

    // --- Aplicar transformaciones de campos ---
    $field_transformers = isset($endpoint['field_transformers']) ? $endpoint['field_transformers'] : [];

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔄 SOURCES - Field transformers del endpoint: ' . print_r($field_transformers, true));
        error_log('🔄 SOURCES - Primer item ANTES de transformar: ' . print_r($formatted[0] ?? 'vacío', true));
    }

    if (!empty($field_transformers)) {
        require_once plugin_dir_path(__FILE__) . '/../field-extractor.php';

        foreach ($formatted as &$item) {
            // Convertir objeto a array para transformación
            $item_array = is_object($item) ? (array)$item : $item;

            foreach ($field_transformers as $transformer) {
                $field_name = $transformer['field'] ?? '';

                if (empty($field_name) || !isset($item_array[$field_name])) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("🔄 SOURCES - Campo '$field_name' no encontrado en item. Campos disponibles: " . implode(', ', array_keys($item_array)));
                    }
                    continue;
                }

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("🔄 SOURCES - Transformando campo '$field_name' con valor: " . print_r($item_array[$field_name], true));
                }

                // Aplicar transformación al campo
                $item_array[$field_name] = bricks_api_apply_field_transform(
                    $field_name,
                    $item_array[$field_name],
                    [$transformer]
                );

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("🔄 SOURCES - Campo '$field_name' transformado a: " . print_r($item_array[$field_name], true));
                }
            }

            // Convertir de vuelta a objeto
            $item = (object)$item_array;
        }
        unset($item);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('🔄 SOURCES - Primer item DESPUÉS de transformar: ' . print_r($formatted[0] ?? 'vacío', true));
        }
    }

    // --- Detectar si es single (detalle) o listado ---
    $is_single = false;
    if (isset($query_obj->settings['is_single']) && $query_obj->settings['is_single']) {
        $is_single = true;
    } elseif (isset($query_obj->settings['posts_per_page']) && $query_obj->settings['posts_per_page'] == 1) {
        $is_single = true;
    } elseif (count($formatted) === 1) {
        $is_single = true;
    }
    
    if ($is_single) {
        $single_item = $formatted[0];
        if (is_array($single_item)) {
            $single_item = (object)$single_item;
        }
        return [$single_item];
    }
    
    return $formatted;
}, 20, 2);
