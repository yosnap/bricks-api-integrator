<?php
/**
 * Sistema de Proxy Seguro para Imágenes
 *
 * Oculta credenciales de API y genera URLs limpias para imágenes
 * Formato: /wp-json/bricks-api/v1/proxy/{endpoint_slug}/{resource_id}
 *
 * @package Bricks_API_Integrator
 * @version 0.2-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Preparar headers de autenticación para peticiones HTTP
 *
 * @param array $endpoint_config Configuración del endpoint con datos de autenticación
 * @return array Headers preparados con autenticación incluida
 */
function bricks_api_proxy_prepare_auth_headers($endpoint_config = []) {
    $headers = [
        'User-Agent' => 'Bricks API Integrator/0.2-beta',
        'Accept' => '*/*',
        'Connection' => 'keep-alive',
        'Cache-Control' => 'no-cache'
    ];

    // Si no hay configuración o auth_type, retornar headers básicos
    if (empty($endpoint_config) || empty($endpoint_config['auth_type']) || $endpoint_config['auth_type'] === 'none') {
        return $headers;
    }

    $auth_type = $endpoint_config['auth_type'];

    switch ($auth_type) {
        case 'bearer':
        case 'token':
            // Soportar ambos nombres de campo
            $token = $endpoint_config['token'] ?? $endpoint_config['auth_token'] ?? '';
            if (!empty($token)) {
                $headers['Authorization'] = 'Bearer ' . trim($token);
            }
            break;

        case 'api_key':
            // Soportar ambos formatos
            $key_name = $endpoint_config['api_key_header'] ?? $endpoint_config['auth_key'] ?? 'X-API-Key';
            $key_value = $endpoint_config['api_key'] ?? $endpoint_config['auth_value'] ?? '';
            if (!empty($key_value)) {
                $headers[trim($key_name)] = trim($key_value);
            }
            break;

        case 'basic':
            // Soportar múltiples nombres de campos por compatibilidad
            $username = $endpoint_config['basic_user'] ?? $endpoint_config['auth_username'] ?? '';
            $password = $endpoint_config['basic_password'] ?? $endpoint_config['auth_password'] ?? '';
            if (!empty($username) && !empty($password)) {
                $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
            }
            break;
    }

    return $headers;
}

/**
 * Registrar endpoint REST API para el proxy de imágenes
 */
add_action('rest_api_init', function() {
    register_rest_route('bricks-api/v1', '/proxy/(?P<endpoint>[a-zA-Z0-9\-]+)/(?P<resource_id>[a-zA-Z0-9\-\/]+)', [
        'methods' => 'GET',
        'callback' => 'bricks_api_proxy_image',
        'permission_callback' => '__return_true',
        'args' => [
            'endpoint' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'resource_id' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ]
    ]);
});

/**
 * Callback del proxy de imágenes
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function bricks_api_proxy_image($request) {
    $endpoint_slug = $request->get_param('endpoint');
    $resource_id = $request->get_param('resource_id');

    // Buscar el endpoint configurado buscando en los field_transformers
    $endpoints = get_option('bricks_api_endpoints', []);
    $endpoint_config = null;
    $proxy_config = null;

    foreach ($endpoints as $endpoint) {
        // Buscar en field_transformers por proxy_slug
        if (!empty($endpoint['field_transformers']) && is_array($endpoint['field_transformers'])) {
            foreach ($endpoint['field_transformers'] as $transformer) {
                if ($transformer['type'] === 'proxy' &&
                    isset($transformer['proxy_slug']) &&
                    $transformer['proxy_slug'] === $endpoint_slug) {
                    $endpoint_config = $endpoint;
                    $proxy_config = $transformer;
                    break 2; // Salir de ambos foreach
                }
            }
        }
    }

    if (!$endpoint_config || !$proxy_config) {
        return new WP_Error('endpoint_not_found', 'Endpoint o configuración de proxy no encontrados', ['status' => 404]);
    }

    // Verificar si hay configuración de proxy
    if (empty($proxy_config['proxy_url_template'])) {
        return new WP_Error('proxy_not_configured', 'Proxy no configurado para este endpoint', ['status' => 500]);
    }

    // Construir la URL real del recurso
    $resource_url = str_replace('{resource_id}', $resource_id, $proxy_config['proxy_url_template']);

    // Preparar headers de autenticación
    $args = [
        'headers' => [],
        'timeout' => 30,
        'sslverify' => false // Para desarrollo local
    ];

    // Añadir autenticación según configuración del endpoint
    $auth_headers = bricks_api_proxy_prepare_auth_headers($endpoint_config);
    $args['headers'] = array_merge($args['headers'], $auth_headers);

    // Añadir parámetros adicionales del proxy
    if (!empty($proxy_config['proxy_params']) && is_array($proxy_config['proxy_params'])) {
        $resource_url = add_query_arg($proxy_config['proxy_params'], $resource_url);
    }

    // Intentar obtener desde caché transient
    $cache_key = 'bricks_api_proxy_' . md5($resource_url);
    $cached = get_transient($cache_key);

    if ($cached !== false && !empty($cached['body'])) {
        // Servir desde caché
        header('Content-Type: ' . $cached['content_type']);
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        header('X-Proxy-Cache: HIT');

        echo base64_decode($cached['body']);
        exit;
    }

    // Hacer la petición a la API
    $response = wp_remote_get($resource_url, $args);

    if (is_wp_error($response)) {
        return new WP_Error('proxy_error', $response->get_error_message(), ['status' => 500]);
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    $body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        return new WP_Error('resource_error', 'Error al obtener recurso: ' . $status_code, ['status' => $status_code]);
    }

    // Si la respuesta es JSON, puede ser que la API devuelva la URL de la imagen
    if (strpos($content_type, 'application/json') !== false) {
        $json_data = json_decode($body, true);

        // Buscar URL de imagen en la respuesta JSON
        if (isset($json_data['url'])) {
            $image_url = $json_data['url'];

            // Hacer segunda petición para obtener la imagen real
            $image_response = wp_remote_get($image_url, ['timeout' => 30, 'sslverify' => false]);

            if (!is_wp_error($image_response)) {
                $image_status = wp_remote_retrieve_response_code($image_response);
                $image_content_type = wp_remote_retrieve_header($image_response, 'content-type');
                $image_body = wp_remote_retrieve_body($image_response);

                if ($image_status === 200 && strpos($image_content_type, 'image/') === 0) {
                    // Cachear la imagen
                    set_transient($cache_key, [
                        'content_type' => $image_content_type,
                        'body' => base64_encode($image_body)
                    ], HOUR_IN_SECONDS);

                    // Servir imagen
                    header('Content-Type: ' . $image_content_type);
                    header('Cache-Control: public, max-age=3600');
                    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                    header('X-Proxy-Cache: MISS');

                    echo $image_body;
                    exit;
                }
            }
        }

        return new WP_Error('invalid_response', 'La API no devolvió una URL de imagen válida', ['status' => 400]);
    }

    // Verificar que sea una imagen directamente
    if (strpos($content_type, 'image/') !== 0) {
        return new WP_Error('invalid_content', 'El recurso no es una imagen (Content-Type: ' . $content_type . ')', ['status' => 400]);
    }

    // Cachear la respuesta (1 hora)
    set_transient($cache_key, [
        'content_type' => $content_type,
        'body' => base64_encode($body)
    ], HOUR_IN_SECONDS);

    // Devolver la imagen directamente
    header('Content-Type: ' . $content_type);
    header('Cache-Control: public, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    header('X-Proxy-Cache: MISS');

    echo $body;
    exit;
}

/**
 * Generar URL de proxy para un recurso
 *
 * @param string $endpoint_slug Slug del endpoint (ej: 'inventrip-images')
 * @param string $resource_id ID del recurso (ej: '55464')
 * @return string URL del proxy
 */
function bricks_api_generate_proxy_url($endpoint_slug, $resource_id) {
    $site_url = get_site_url();
    return $site_url . '/wp-json/bricks-api/v1/proxy/' . $endpoint_slug . '/' . $resource_id;
}

/**
 * Aplicar transformación de proxy a un valor
 *
 * @param mixed $value Valor original
 * @param array $transformer Configuración del transformador
 * @return mixed Valor transformado
 */
function bricks_api_apply_proxy_transform($value, $transformer) {
    if (empty($transformer['proxy_slug'])) {
        return $value;
    }

    $endpoint_slug = $transformer['proxy_slug'];

    // Si es un array de valores, transformar cada uno
    if (is_array($value)) {
        $transformed = [];
        foreach ($value as $item) {
            $resource_id = bricks_api_extract_id_from_path($item);
            $transformed[] = bricks_api_generate_proxy_url($endpoint_slug, $resource_id);
        }
        return $transformed;
    } else {
        // Valor único
        $resource_id = bricks_api_extract_id_from_path($value);
        return bricks_api_generate_proxy_url($endpoint_slug, $resource_id);
    }
}
