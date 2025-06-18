<?php
/**
 * API Manager - Gestión dinámica de APIs
 * 
 * @package BricksAPIIntegrator
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para gestión de APIs
 */
trait APIManager {
    
    /**
     * Obtener datos de API por nombre de endpoint
     */
    public function get_api_data_by_endpoint_name($endpoint_name) {
        $endpoints = get_option('bricks_api_endpoints', []);
        
        foreach ($endpoints as $endpoint) {
            if ($endpoint['name'] === $endpoint_name) {
                return $this->get_api_data_with_cache($endpoint['url'], $endpoint);
            }
        }
        
        return [];
    }
    
    /**
     * Obtener datos de API por source ID - CORREGIDO FINAL
     */
    public function get_api_data_by_source_id($source_id, $force_refresh = false) {
        $sources = get_option('bricks_api_sources', []);
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($sources[$source_id])) {
            return [];
        }
        
        $source = $sources[$source_id];
        $endpoint_id = $source['endpoint_id'] ?? '';
        
        if (!isset($endpoints[$endpoint_id])) {
            return [];
        }
        
        $endpoint = $endpoints[$endpoint_id];
        
        
        // Añadir parámetros de paginación por defecto si no existen en la URL
        $url = $endpoint['url'];
        $parsed_url = parse_url($url);
        $query_params = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query_params);
        }
        
        // Añadir parámetros necesarios para obtener datos
        if (!isset($query_params['per_page'])) {
            $url = add_query_arg('per_page', 10, $url);
        }
        if (!isset($query_params['page'])) {
            $url = add_query_arg('page', 1, $url);
        }
        
        
        // Usar get_api_data_with_cache que ya maneja autenticación correctamente
        $data = $this->get_api_data_with_cache($url, $endpoint, $force_refresh);
        
        if (empty($data)) {
            return [];
        }
        
        
        // Procesar items_path si está configurado - LÓGICA CORREGIDA
        if (!empty($source['items_path'])) {
            $path_parts = explode('.', $source['items_path']);
            $processed_data = $data;
            
            
            foreach ($path_parts as $part) {
                
                if (is_array($processed_data) && isset($processed_data[$part])) {
                    $processed_data = $processed_data[$part];
                } elseif (is_object($processed_data) && isset($processed_data->$part)) {
                    $processed_data = $processed_data->$part;
                } else {
                    return [];
                }
            }
            
            // Adaptación: distinguir entre array indexado y objeto plano
            if (is_array($processed_data)) {
                // Si es array indexado, devolver tal cual
                if (array_keys($processed_data) === range(0, count($processed_data) - 1)) {
                    return $processed_data;
                } else {
                    // Es un objeto plano (array asociativo), devolver tal cual
                    return $processed_data;
                }
            } elseif (is_object($processed_data)) {
                return (array)$processed_data;
            } else {
                return $processed_data;
            }
        }
        
        return $data;
    }
    
    /**
     * Obtener datos de API con cache
     */
    public function get_api_data_with_cache($url, $endpoint_config = [], $force_refresh = false) {
        $cache_key = 'api_data_' . md5($url . serialize($endpoint_config));
        
        
        // Cache estático - saltamos si se fuerza el refresco
        if (!$force_refresh && isset(self::$api_cache[$cache_key])) {
            return self::$api_cache[$cache_key];
        }
        
        // Cache de WordPress - saltamos si se fuerza el refresco
        $cache_duration = get_option('bricks_api_cache_duration', 300);
        if (!$force_refresh && $cache_duration > 0) {
            $cached_data = get_transient($cache_key);
            if ($cached_data !== false) {
                self::$api_cache[$cache_key] = $cached_data;
                return $cached_data;
            }
        }
        
        // Procesar URL dinámica
        $processed_url = $this->process_dynamic_url($url);
        
        // Si contiene parámetros no resueltos, devolver vacío
        if (strpos($processed_url, '{') !== false) {
            return [];
        }
        
        // Preparar headers y opciones de la petición
        $headers = $this->prepare_request_headers($endpoint_config);
        $request_options = [
            'headers' => $headers,
            'timeout' => 30
        ];
        
        // Configurar autenticación básica si está definida y no está ya configurada
        if (empty($headers['Authorization']) && isset($endpoint_config['auth_type']) && $endpoint_config['auth_type'] === 'basic') {
            $username = $endpoint_config['username'] ?? '';
            $password = $endpoint_config['password'] ?? '';
            
            if (!empty($username) && !empty($password)) {
                $request_options['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
            }
        }
        
        // Determinar el método HTTP a usar (GET por defecto)
        $method = isset($endpoint_config['method']) ? strtoupper($endpoint_config['method']) : 'GET';
        
        // Realizar petición según el método
        if ($method === 'GET') {
            $response = wp_remote_get($processed_url, $request_options);
        } elseif ($method === 'POST') {
            $response = wp_remote_post($processed_url, $request_options);
        } else {
            $request_options['method'] = $method;
            $response = wp_remote_request($processed_url, $request_options);
        }
        
        // Verificar si hay errores en la respuesta
        if (is_wp_error($response)) {
            return [];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return [];
        }
        
        // Obtener y procesar el cuerpo de la respuesta
        $body = wp_remote_retrieve_body($response);
        // Log del body crudo de la API
        error_log('DEBUG API RAW BODY: ' . $body);
        // Intentar decodificar como JSON
        $data = json_decode($body, true);
        // Log del array $data justo después de decodificar el JSON
        error_log('DEBUG API RAW DATA: ' . print_r($data, true));
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        if (empty($data)) {
            return [];
        }
        
        // Detectar formato común de respuesta con wrapper de estado y datos
        // Muchas APIs usan este formato: {status: "success", data: [...]}
        if (isset($data['status']) && ($data['status'] === 'success' || $data['status'] === 'ok') && isset($data['data'])) {
            $data = $data['data'];
        }
        
        // Guardar en cache
        self::$api_cache[$cache_key] = $data;
        
        // Guardar en transient si la caché está habilitada
        if ($cache_duration > 0) {
            set_transient($cache_key, $data, $cache_duration);
        }
        
        return $data;
        
        // Convertir a array si no lo es
        if (!is_array($data)) {
            $data = [$data];
        }
        
        // Guardar en cache (configurable, por defecto 5 minutos)
        $cache_duration = get_option('bricks_api_cache_duration', 300); // 5 minutos por defecto
        
        // Si el caché está deshabilitado (0), no guardar en caché
        if ($cache_duration > 0) {
            set_transient($cache_key, $data, $cache_duration);
        }
        
        // Siempre guardar en caché estático para esta petición
        self::$api_cache[$cache_key] = $data;
        
        return $data;
    }
    
    /**
     * Procesar URLs dinámicas
     */
    private function process_dynamic_url($url) {
        // Parámetros de URL (?id=123 -> {id})
        $url = preg_replace_callback('/\{(\w+)\}/', function($matches) {
            $param = $matches[1];
            if (isset($_GET[$param])) {
                return urlencode(sanitize_text_field($_GET[$param]));
            }
            return $matches[0];
        }, $url);
        
        // Dynamic data tags de Bricks
        if (function_exists('bricks_render_dynamic_data')) {
            $url = preg_replace_callback('/\{([^}]+)\}/', function($matches) {
                $tag = $matches[0];
                $value = bricks_render_dynamic_data($tag);
                return $value ?: $matches[0];
            }, $url);
        }
        
        return $url;
    }
    
    /**
     * Preparar headers de petición
     */
    private function prepare_request_headers($endpoint_config) {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Bricks-API-Integrator/2.0'
        ];
        
        if (empty($endpoint_config)) {
            return $headers;
        }
        
        $auth_type = $endpoint_config['auth_type'] ?? 'none';
        
        switch ($auth_type) {
            case 'token':
                if (!empty($endpoint_config['token'])) {
                    $headers['Authorization'] = 'Bearer ' . $endpoint_config['token'];
                }
                break;
                
            case 'basic':
                if (!empty($endpoint_config['basic_user']) && !empty($endpoint_config['basic_password'])) {
                    $headers['Authorization'] = 'Basic ' . base64_encode($endpoint_config['basic_user'] . ':' . $endpoint_config['basic_password']);
                }
                break;
                
            case 'api_key':
                if (!empty($endpoint_config['api_key'])) {
                    $header_name = $endpoint_config['api_key_header'] ?? 'X-API-Key';
                    $headers[$header_name] = $endpoint_config['api_key'];
                }
                break;
        }
        
        return $headers;
    }
    
    /**
     * Build a dynamic API URL with dynamic parameters and pagination
     *
     * @param string $base_url URL base del endpoint
     * @param array $dynamic_params Lista de parámetros dinámicos (name, source, default)
     * @param array $pagination_config Configuración de paginación (type, param, per_page_param, page, per_page)
     * @param array $overrides Sobrescrituras de parámetros (por ejemplo, desde la UI de Bricks)
     * @param WP_Post|null $post Contexto de post actual
     * @param array $extra_context Contexto adicional (opcional)
     * @return string URL final construida
     */
    public function build_dynamic_api_url($base_url, $dynamic_params = [], $pagination_config = [], $overrides = [], $post = null, $extra_context = []) {
        // Log inicial de la función
        error_log('DEBUG URL BASE: ' . $base_url);
        error_log('DEBUG PARAMS INICIALES: ' . print_r($dynamic_params, true));
        error_log('DEBUG OVERRIDES: ' . print_r($overrides, true));
        $url = $base_url;
        // 1. Procesar parámetros dinámicos y reemplazar en el path si corresponde
        if (!empty($dynamic_params)) {
            foreach ($dynamic_params as $param) {
                $param_name = $param['name'] ?? '';
                $param_source = $param['source'] ?? 'static';
                $param_default = $param['default'] ?? '';
                if (empty($param_name)) continue;
                // Sobrescritura desde UI
                if (isset($overrides[$param_name]) && $overrides[$param_name] !== '') {
                    $param_value = $overrides[$param_name];
                } else {
                    // Obtener valor según el origen
                    switch ($param_source) {
                        case 'url':
                            $param_value = isset($_GET[$param_name]) ? sanitize_text_field($_GET[$param_name]) : $param_default;
                            break;
                        case 'post':
                            global $post;
                            $param_value = isset($post->ID) ? $post->ID : $param_default;
                            break;
                        case 'user':
                            $current_user = wp_get_current_user();
                            $param_value = $current_user->exists() ? $current_user->ID : $param_default;
                            break;
                        default:
                            $param_value = $param_default;
                            break;
                    }
                }
                // Log de cada parámetro procesado
                error_log('DEBUG PARAM: ' . $param_name . ' = ' . var_export($param_value, true));
                // Reemplazar en el path si existe el placeholder
                if (strpos($url, '{'.$param_name.'}') !== false) {
                    $url = str_replace('{'.$param_name.'}', urlencode($param_value), $url);
                } else if ($param_value !== '') {
                    // Si no está en el path, añadir como query string
                    $url = add_query_arg($param_name, $param_value, $url);
                }
            }
        }
        // 2. Procesar paginación
        if (!empty($pagination_config) && isset($pagination_config['type']) && $pagination_config['type'] !== 'none') {
            $type = $pagination_config['type'];
            $pagination_param = $pagination_config['param'] ?? '';
            $per_page_param = $pagination_config['per_page_param'] ?? '';
            $page = isset($pagination_config['page']) ? intval($pagination_config['page']) : 1;
            $per_page = isset($pagination_config['per_page']) ? intval($pagination_config['per_page']) : 10;
            if ($type === 'page_param' && !empty($pagination_param)) {
                $url = add_query_arg($pagination_param, $page, $url);
            } elseif ($type === 'offset_param' && !empty($pagination_param)) {
                $offset = ($page - 1) * $per_page;
                $url = add_query_arg($pagination_param, $offset, $url);
            }
            if (!empty($per_page_param)) {
                $url = add_query_arg($per_page_param, $per_page, $url);
            }
        }
        // Log final de la URL construida
        error_log('DEBUG URL FINAL: ' . $url);
        return $url;
    }
}
