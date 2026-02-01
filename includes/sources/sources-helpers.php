<?php
// Helpers generales para Sources

/**
 * Realizar petición HTTP a la API, con soporte especial para Inmovilla
 *
 * @param string $url URL del endpoint
 * @param array $endpoint Configuración del endpoint
 * @param array $source_config Configuración del source (opcional)
 * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'body' => string]
 */
if (!function_exists('bricks_api_source_make_request')) {
    function bricks_api_source_make_request($url, $endpoint = [], $source_config = []) {
        // Asegurar que las funciones de IP estén disponibles
        if (!function_exists('bricks_api_get_client_ip')) {
            require_once BRICKS_API_INTEGRATOR_PATH . 'includes/api-manager.php';
        }

        // Preparar headers base
        $args = [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Bricks API Integrator/2.1.1',
                'Accept' => 'application/json',
            ]
        ];

        // Añadir headers de autenticación si está disponible
        if (function_exists('bricks_api_proxy_prepare_auth_headers')) {
            $auth_headers = bricks_api_proxy_prepare_auth_headers($endpoint);
            $args['headers'] = array_merge($args['headers'], $auth_headers);
        }

        $method = isset($endpoint['method']) ? strtoupper($endpoint['method']) : 'GET';

        // Detectar si es Inmovilla y usar formato POST especial
        if (strpos($url, 'apiweb.inmovilla.com') !== false) {
            $parsed_url = wp_parse_url($url);
            $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];

            // Obtener parámetros del endpoint
            $params = [];
            if (!empty($endpoint['dynamic_params'])) {
                foreach ($endpoint['dynamic_params'] as $param) {
                    if (!empty($param['name']) && isset($param['default'])) {
                        $params[$param['name']] = $param['default'];
                    }
                }
            }

            // También obtener de source_config si existe
            if (!empty($source_config['dynamic_params'])) {
                foreach ($source_config['dynamic_params'] as $param) {
                    if (!empty($param['name']) && isset($param['default'])) {
                        $params[$param['name']] = $param['default'];
                    }
                }
            }

            // Extraer de query string si existe
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
                $params = array_merge($params, $query_params);
            }

            // Usar tipo del source si está definido, o deducirlo del items_path
            $tipo = $source_config['tipo'] ?? $source_config['items_path'] ?? $params['tipo'] ?? 'paginacion';

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('INMOVILLA SOURCE - tipo: ' . $tipo . ' | source_config[tipo]: ' . ($source_config['tipo'] ?? 'N/A') . ' | items_path: ' . ($source_config['items_path'] ?? 'N/A'));
            }

            // Construir string de parámetros en formato Inmovilla
            $agencia = $params['agencia'] ?? '';
            $password = $params['password'] ?? '';
            $idioma = $params['idioma'] ?? '1';
            $lostipos = $params['lostipos'] ?? 'lostipos';
            $pos = $params['pos'] ?? '1';
            $num = $params['num_elementos'] ?? '20';
            $where = $params['where'] ?? '';
            $orden = $params['orden'] ?? '';

            // Para tipo "zonas", se requiere cod_ciu en el where
            if ($tipo === 'zonas') {
                if (!empty($params['cod_ciu'])) {
                    $where = 'cod_ciu=' . $params['cod_ciu'];
                }
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('INMOVILLA ZONAS - cod_ciu: ' . ($params['cod_ciu'] ?? 'NO DEFINIDO') . ' | where: ' . $where);
                    error_log('INMOVILLA ZONAS - params: ' . print_r($params, true));
                }
            }

            // Formato: agencia;password;idioma;lostipos;tipo;pos;num;where;orden
            $texto = $agencia . ';' . $password . ';' . $idioma . ';' . $lostipos . ';' . $tipo . ';' . $pos . ';' . $num . ';' . $where . ';' . $orden;

            $dominio = $_SERVER['SERVER_NAME'] ?? '';
            $ip = !empty($params['ip']) ? $params['ip'] : bricks_api_get_client_ip();

            // Configurar body y headers para Inmovilla
            $args['body'] = 'param=' . rawurlencode($texto) . '&elDominio=' . urlencode($dominio) . '&ia=' . urlencode($ip) . '&json=1';
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['headers']['User-Agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('INMOVILLA SOURCE REQUEST - PARAM: ' . $texto);
                error_log('INMOVILLA SOURCE REQUEST - URL: ' . $base_url);
            }

            $response = wp_remote_post($base_url, $args);
        } elseif ($method === 'POST') {
            // POST genérico
            $response = wp_remote_post($url, $args);
        } else {
            // GET por defecto
            $response = wp_remote_get($url, $args);
        }

        // Verificar errores
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'data' => null,
                'error' => $response->get_error_message(),
                'body' => ''
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SOURCE REQUEST HTTP STATUS: ' . $status_code);
            error_log('SOURCE REQUEST RAW BODY: ' . substr($body, 0, 500));
        }

        if ($status_code !== 200) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'HTTP ' . $status_code,
                'body' => $body
            ];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log detallado para depurar respuestas inválidas
            error_log('SOURCE REQUEST JSON ERROR - Body: ' . substr($body, 0, 1000));
            return [
                'success' => false,
                'data' => null,
                'error' => 'Error de JSON: ' . json_last_error_msg() . ' - Respuesta: ' . substr($body, 0, 200),
                'body' => $body
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'error' => null,
            'body' => $body
        ];
    }
}

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
