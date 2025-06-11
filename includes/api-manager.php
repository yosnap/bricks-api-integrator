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
     * Obtener datos de API por source ID
     */
    public function get_api_data_by_source_id($source_id) {
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
        $data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
        
        // Procesar items_path si está configurado
        if (!empty($source['items_path']) && !empty($data)) {
            $path_parts = explode('.', $source['items_path']);
            $processed_data = $data;
            
            foreach ($path_parts as $part) {
                if (isset($processed_data[$part])) {
                    $processed_data = $processed_data[$part];
                } else {
                    return [];
                }
            }
            
            return is_array($processed_data) ? $processed_data : [$processed_data];
        }
        
        return $data;
    }
    
    /**
     * Obtener datos de API con cache
     */
    public function get_api_data_with_cache($url, $endpoint_config = [], $force_refresh = false) {
        $cache_key = 'api_data_' . md5($url . serialize($endpoint_config));
        
        // Cache estático
        if (!$force_refresh && isset(self::$api_cache[$cache_key])) {
            return self::$api_cache[$cache_key];
        }
        
        // Cache de WordPress (solo si no está deshabilitado)
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
        
        // Preparar headers
        $headers = $this->prepare_request_headers($endpoint_config);
        
        // Realizar petición
        $response = wp_remote_get($processed_url, [
            'headers' => $headers,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return [];
        }
        
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
        $url = $base_url;
        // 1. Procesar parámetros dinámicos
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
                            if ($post && isset($post->ID)) {
                                $param_value = $post->ID;
                            } elseif (is_singular()) {
                                $param_value = get_the_ID();
                            } else {
                                $param_value = $param_default;
                            }
                            break;
                        case 'post_slug':
                            if ($post && isset($post->post_name)) {
                                $param_value = $post->post_name;
                            } elseif (is_singular()) {
                                $current_post = get_post();
                                $param_value = $current_post ? $current_post->post_name : $param_default;
                            } else {
                                $param_value = $param_default;
                            }
                            break;
                        case 'user':
                            $current_user = wp_get_current_user();
                            $param_value = $current_user->exists() ? $current_user->ID : $param_default;
                            break;
                        case 'meta':
                            if ($post && isset($post->ID)) {
                                $meta_value = get_post_meta($post->ID, $param_name, true);
                                $param_value = !empty($meta_value) ? $meta_value : $param_default;
                            } else {
                                $param_value = $param_default;
                            }
                            break;
                        case 'static':
                        default:
                            $param_value = $param_default;
                            break;
                    }
                }
                // Agregar parámetro si tiene valor
                if ($param_value !== '') {
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
        return $url;
    }
}
