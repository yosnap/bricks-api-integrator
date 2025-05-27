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
}
