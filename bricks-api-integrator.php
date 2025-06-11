<?php
/*
    * Plugin Name: Bricks API Integrator
    * Description: Integra el constructor de páginas Bricks con APIs externas de forma dinámica.
    * Version: 0.1-beta
    * Author: sn4p Dev
    * Author URI: https://sn4p.dev
    * License: GPL2
    * License URI: https://www.gnu.org/licenses/gpl-2.0.html
    * Text Domain: bricks-api-integrator
*/

if (!defined('ABSPATH')) {
    exit; // Evita accesos directos
}

// Definir constantes del plugin
define('BRICKS_API_INTEGRATOR_VERSION', '0.1-beta');
define('BRICKS_API_INTEGRATOR_PATH', plugin_dir_path(__FILE__));
define('BRICKS_API_INTEGRATOR_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios ANTES de definir la clase
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/api-manager.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/field-extractor.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/functions.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/query-preview.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/cleaner.php';

// Archivos necesarios para el admin - HABILITAR SOLO LOS NECESARIOS
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/templates.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/templates.php';
}
/*
// TEMPORALMENTE DESHABILITADO - debug-source crea sources automáticamente
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/debug-source.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/debug-source.php';
}
*/
/*
// TEMPORALMENTE DESHABILITADO - Causa duplicados de query types
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/direct-source.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/direct-source.php';
}
*/
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php';
}

// Script de limpieza específica para query types
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'cleanup-query-types.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'cleanup-query-types.php';
}

// Script de limpieza temporal
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'cleanup-duplicates.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'cleanup-duplicates.php';
}

// Script de limpieza agresiva
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'aggressive-cleanup.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'aggressive-cleanup.php';
}

// Inicialización simplificada para evitar errores durante la activación
add_action('plugins_loaded', function() {
    // Solo verificar Bricks en admin cuando sea necesario
    if (is_admin() && !wp_doing_ajax()) {
        $theme = wp_get_theme();
        if ('Bricks' != $theme->name && 'Bricks' != $theme->parent_theme) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>El plugin Bricks API Integrator funciona mejor con Bricks Builder instalado.</p></div>';
            });
        }
    }
    
    // Inicializar el plugin
    if (class_exists('BricksAPIIntegrator')) {
        new BricksAPIIntegrator();
    }
});

class BricksAPIIntegrator {
    
    // Usar traits para funcionalidad modular
    use APIManager, FieldExtractor, QueryPreview;
    
    /**
     * Cache estático para respuestas API
     */
    private static $api_cache = [];
    
    public function __construct() {
        $this->init_hooks();
        $this->init_query_preview_hooks(); // Añadir hooks de preview
        $this->clean_debug_sources(); // Limpiar sources de debug automáticamente
        
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
    }
    
    /**
     * Activación del plugin
     */
    public function activate_plugin() {
        // Crear configuración por defecto si no existe
        if (!get_option('bricks_api_endpoints')) {
            update_option('bricks_api_endpoints', []);
        }
        if (!get_option('bricks_api_sources')) {
            update_option('bricks_api_sources', []);
        }
        if (!get_option('bricks_api_templates')) {
            update_option('bricks_api_templates', []);
        }
        
        // Limpiar configuración obsoleta de launchers si existe
        delete_option('bricks_api_launchers');
    }
    
    /**
     * Limpiar sources de debug automáticamente
     */
    private function clean_debug_sources() {
        $sources = get_option('bricks_api_sources', []);
        $cleaned = false;
        
        // Eliminar sources de debug/prueba automáticamente creados
        $debug_keys = ['debug_api_source', 'debug_source', 'api_source_debug', 'test_source'];
        
        foreach ($debug_keys as $key) {
            if (isset($sources[$key])) {
                unset($sources[$key]);
                $cleaned = true;
            }
        }
        
        if ($cleaned) {
            update_option('bricks_api_sources', $sources);
        }
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Hooks de Bricks Builder - NUEVOS DINÁMICOS
        add_filter('bricks/setup/control_options', [$this, 'add_query_types_dynamic']);
        add_filter('bricks/query/run', [$this, 'run_custom_query_dynamic'], 10, 2);
        add_filter('bricks/dynamic_tags_list', [$this, 'add_dynamic_tags_dynamic']);
        add_filter('bricks/dynamic_data/render_content', [$this, 'render_dynamic_tags_dynamic'], 30, 3);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX hooks
        add_action('wp_ajax_test_api_endpoint', [$this, 'ajax_test_api_endpoint']);
        add_action('wp_ajax_test_advanced_api_endpoint', [$this, 'ajax_test_advanced_api_endpoint']);
        add_action('wp_ajax_get_dynamic_tags_for_endpoint', [$this, 'ajax_get_dynamic_tags_for_endpoint']);
        add_action('wp_ajax_clear_api_cache', [$this, 'ajax_clear_api_cache']);
        add_action('wp_ajax_regenerate_bricks_integration', [$this, 'ajax_regenerate_bricks_integration']);
        add_action('wp_ajax_reset_plugin_data', [$this, 'ajax_reset_plugin_data']);
        add_action('wp_ajax_clean_duplicates', [$this, 'ajax_clean_duplicates']);
        add_action('wp_ajax_clean_query_types', [$this, 'ajax_clean_query_types']);
        add_action('wp_ajax_clean_debug_sources', [$this, 'ajax_clean_debug_sources']);
        add_action('wp_ajax_update_cache_duration', [$this, 'ajax_update_cache_duration']);
        add_action('wp_ajax_get_cache_duration', [$this, 'ajax_get_cache_duration']);
        add_action('wp_ajax_refresh_endpoint_data', [$this, 'ajax_refresh_endpoint_data']);
        add_action('wp_ajax_test_items_path', [$this, 'ajax_test_items_path']);
        add_action('wp_ajax_save_single_api_endpoint', [$this, 'ajax_save_single_api_endpoint']);
        
        // Shortcode para debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_shortcode('debug_api_integrator', [$this, 'debug_shortcode']);
        }
        
        // Hook para limpiar cache cuando se actualicen las opciones
        add_action('update_option_bricks_api_endpoints', [$this, 'clear_all_cache']);
        add_action('update_option_bricks_api_sources', [$this, 'clear_all_cache']);
    }
    
    /**
     * Query Types dinámicos con diferenciación AUTO/MANUAL
     */
    public function add_query_types_dynamic($control_options) {
        // Limpiar query types existentes de nuestro plugin primero para evitar duplicación
        if (!isset($control_options['queryTypes'])) {
            $control_options['queryTypes'] = [];
        }
        foreach ($control_options['queryTypes'] as $key => $name) {
            if (strpos($key, 'snap_ep_') === 0) {
                unset($control_options['queryTypes'][$key]);
            }
        }
        
        // Registrar solo los query types generados manualmente
        $query_types = get_option('bricks_api_generated_query_types', []);
        if (!empty($query_types)) {
            foreach ($query_types as $slug => $info) {
                $key = 'snap_ep_' . $slug;
                $label = $info['endpoint_name'] . ' (Endpoint)';
                $control_options['queryTypes'][$key] = $label;
                }
            }
        return $control_options;
    }
    
    /**
     * Ejecutar Query con diferenciación AUTO/MANUAL
     */
    public function run_custom_query_dynamic($results, $query_object) {
        $object_type = $query_object->object_type ?? '';
        
        // Solo procesar nuestros nuevos query types
        if (strpos($object_type, 'snap_ep_') !== 0) {
            return $results;
        }
        
        $slug = str_replace('snap_ep_', '', $object_type);
        $query_types = get_option('bricks_api_generated_query_types', []);
        if (!isset($query_types[$slug])) {
            return $results;
        }
        $query_type_info = $query_types[$slug];
        $endpoint_url = $query_type_info['url'] ?? '';
        if (empty($endpoint_url)) {
            return $results;
            }
            
        // Obtener datos de la API
        $response = wp_remote_get($endpoint_url);
        if (is_wp_error($response)) {
            return $results;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (empty($data)) {
            return $results;
        }
        // Si es array de arrays, tomar el array principal
        if (isset($data[0]) && is_array($data[0])) {
            $data = $data;
        } else if (!is_array($data) || array_keys($data) !== range(0, count($data) - 1)) {
            // Si es un objeto asociativo, envolverlo en un array
            $data = [$data];
                                }
        // Convertir al formato que espera Bricks
        return $this->convert_api_data_for_bricks($data);
    }
    
    /**
     * Extraer ítems anidados usando items_path
     */
    private function extract_nested_items($data, $items_path) {
        if (empty($items_path) || empty($data)) {
            return $data;
        }
        
        $path_parts = explode('.', $items_path);
        $current_data = $data;
        
        foreach ($path_parts as $part) {
            if (is_array($current_data) && isset($current_data[$part])) {
                $current_data = $current_data[$part];
            } elseif (is_object($current_data) && isset($current_data->$part)) {
                $current_data = $current_data->$part;
            } else {
                return [];
            }
        }
        
        return is_array($current_data) ? $current_data : [$current_data];
    }
    
    /**
     * Convertir datos de API al formato que espera Bricks
     */
    private function convert_api_data_for_bricks($api_data) {
        if (empty($api_data)) {
            return [];
        }
        
        // Si no es array, convertir a array
        if (!is_array($api_data)) {
            $api_data = [$api_data];
        }
        
        $converted_data = [];
        
        foreach ($api_data as $index => $item) {
            // Crear un objeto pseudo-post para cada item
            $pseudo_post = new stdClass();
            $pseudo_post->ID = is_numeric($index) ? ($index + 1) : 1;
            $pseudo_post->post_title = '';
            $pseudo_post->post_content = '';
            $pseudo_post->post_type = 'api_data';
            $pseudo_post->post_status = 'publish';
            
            // Añadir todos los campos de la API como meta data
            $pseudo_post->api_data = $item;
            
            // Intentar extraer título si existe
            if (is_array($item) || is_object($item)) {
                $item_array = (array) $item;
                foreach (['title', 'name', 'nombre', 'titulo', 'id'] as $title_field) {
                    if (isset($item_array[$title_field])) {
                        $pseudo_post->post_title = $item_array[$title_field];
                        break;
                    }
                }
            }
            
            $converted_data[] = $pseudo_post;
        }
        

        
        return $converted_data;
    }
    
    /**
     * Dynamic Tags diferenciados para AUTO/MANUAL
     */
    public function add_dynamic_tags_dynamic($tags) {
        // Solo usar los tags generados manualmente
        $tags_data = get_option('bricks_api_generated_tags', []);
        if (!empty($tags_data)) {
            foreach ($tags_data as $slug => $info) {
                $group_title = $info['group_title'] ?? $slug;
                $endpoint_name = $info['endpoint_name'] ?? $slug;
                $tag_list = $info['tags'] ?? [];
                foreach ($tag_list as $tag) {
                    $tags[] = [
                        'name' => $tag,
                        'label' => $tag, // Puedes mejorar esto si quieres mostrar un label más bonito
                        'group' => $group_title
                    ];
                    }
                }
            }
        return $tags;
    }
    
    /**
     * Obtener datos de API por nombre de endpoint
     */
    public function get_api_data_by_endpoint_name($endpoint_name) {
        $endpoints = get_option('bricks_api_endpoints', []);
        
        foreach ($endpoints as $endpoint) {
            if (isset($endpoint['name']) && $endpoint['name'] === $endpoint_name && !empty($endpoint['url'])) {
                try {
                    return $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
                } catch (Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Error al obtener datos para endpoint ' . $endpoint_name . ': ' . $e->getMessage());
                    }
                    return null;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Obtener datos de API con caché
     */
    public function get_api_data_with_cache($url, $endpoint_config = [], $bypass_cache = false) {
        // Si bypass_cache es true, obtener datos frescos
        if ($bypass_cache) {
            return $this->fetch_api_data_direct($url, $endpoint_config);
        }
        
        // Generar clave de cache
        $cache_key = 'bricks_api_cache_' . md5($url . serialize($endpoint_config));
        
        // Intentar obtener del cache primero
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false && !empty($cached_data)) {
            return $cached_data;
        }
        
        // Si no hay cache, obtener datos frescos
        $fresh_data = $this->fetch_api_data_direct($url, $endpoint_config);
        
        // Guardar en cache por 5 minutos
        if (!empty($fresh_data)) {
            set_transient($cache_key, $fresh_data, 300);
        }
        
        return $fresh_data;
    }
    
    /**
     * Obtener datos directamente de la API
     */
    private function fetch_api_data_direct($url, $endpoint_config = []) {
        // Configuración por defecto
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'Bricks API Integrator/2.1.1'
            )
        );
        
        // Añadir autenticación si está configurada
        if (!empty($endpoint_config['auth_type']) && $endpoint_config['auth_type'] !== 'none') {
            switch ($endpoint_config['auth_type']) {
                case 'bearer':
                    if (!empty($endpoint_config['auth_token'])) {
                        $args['headers']['Authorization'] = 'Bearer ' . $endpoint_config['auth_token'];
                    }
                    break;
                case 'api_key':
                    if (!empty($endpoint_config['auth_key']) && !empty($endpoint_config['auth_value'])) {
                        $args['headers'][$endpoint_config['auth_key']] = $endpoint_config['auth_value'];
                    }
                    break;
                case 'basic':
                    // Compatibilidad con ambos nombres de campo
                    $username = $endpoint_config['basic_user'] ?? $endpoint_config['auth_username'] ?? '';
                    $password = $endpoint_config['basic_password'] ?? $endpoint_config['auth_password'] ?? '';
                    if (!empty($username) && !empty($password)) {
                        $args['headers']['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
                    }
                    break;
            }
        }
        
        // LOG: Registrar URL y headers antes de la petición
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('API DEBUG - URL: ' . $url);
            error_log('API DEBUG - Headers: ' . print_r($args['headers'], true));
        }
        // Realizar petición
        $response = wp_remote_get($url, $args);

        // LOG: Registrar código de estado y cuerpo de la respuesta
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $status_code = wp_remote_retrieve_response_code($response);
            error_log('API DEBUG - Status Code: ' . $status_code);
            $body = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            error_log('API DEBUG - Body: ' . (is_string($body) ? substr($body, 0, 1000) : print_r($body, true)));
        }
        
        // Verificar errores
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error en API request: ' . $response->get_error_message());
            }
            return null;
        }
        
        // Obtener código de estado
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('API returned status code: ' . $status_code);
            }
            return null;
        }
        
        // Decodificar JSON
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('JSON decode error: ' . json_last_error_msg());
            }
            return null;
        }
        
        return $data;
    }
    
    /**
     * Obtener datos de API con parámetros dinámicos para páginas de detalle
     * 
     * @param array $endpoint Configuración del endpoint
     * @param bool $force_refresh Forzar actualización de caché
     * @return mixed Datos de la API o null si no se pudieron obtener
     */
    private function get_api_data_with_dynamic_params($endpoint, $force_refresh = false) {
        // Registrar para depuración

        
        // Verificar si hay parámetros configurados
        $has_params = !empty($endpoint['params']) && is_array($endpoint['params']);
        $has_dynamic_params = !empty($endpoint['dynamic_params']) && is_array($endpoint['dynamic_params']);
        

        
        // Construir URL completa con todos los parámetros (estáticos y dinámicos)
        $test_url = $this->build_complete_test_url($endpoint);
        

        
        if (!empty($test_url)) {
            try {
                // Obtener datos con la URL completa
                $test_data = $this->get_api_data_with_cache($test_url, $endpoint, $force_refresh);
                if (!empty($test_data)) {
                    // NUEVO: Si el endpoint tiene items_path, extraer el array anidado
                    if (!empty($endpoint['items_path'])) {
                        $test_data = $this->extract_nested_items($test_data, $endpoint['items_path']);
                    }
                    return $test_data;
                } else {

                }
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Error al obtener datos con parámetros: ' . $e->getMessage());
                }
            }
        }
        
        // Si todo lo anterior falla, intentar obtener datos básicos del endpoint
        if (!$force_refresh) {
            $basic_data = $this->get_api_data_by_endpoint_name($endpoint['name']);
            
            if (!empty($basic_data)) {

                return $basic_data;
            }
        }
        
        return null;
    }
    
    /**
     * Construir URL de prueba con parámetros de muestra para páginas de detalle
     */
    private function build_test_url_with_params($endpoint) {
        $url = $endpoint['url'];
        $dynamic_params = $endpoint['dynamic_params'] ?? [];
        

        
        if (empty($dynamic_params)) {

            return $url;
        }
        
        // Valores de muestra para diferentes tipos de parámetros
        $sample_values = [
            'id' => '1',
            'ID' => '1', 
            'product_id' => '1',
            'post_id' => '1',
            'user_id' => '1',
            'slug' => 'sample',
            'category' => 'sample',
            'tag' => 'sample',
            'type' => 'sample',
            'status' => 'active',
            'limit' => '10',
            'page' => '1',
            'per_page' => '10'
        ];
        
        $params_added = 0;
        foreach ($dynamic_params as $param) {
            $param_name = $param['name'] ?? '';
            $param_default = $param['default'] ?? '';
            
            if (empty($param_name)) {
                continue;
            }
            
            // Usar valor por defecto si existe
            if (!empty($param_default)) {
                $url = add_query_arg($param_name, $param_default, $url);
                $params_added++;

                continue;
            }
            
            // Buscar valor de muestra basado en el nombre del parámetro
            $sample_value = null;
            foreach ($sample_values as $key => $value) {
                if (stripos($param_name, $key) !== false) {
                    $sample_value = $value;
                    break;
                }
            }
            
            // Si no encontramos valor específico, usar valor genérico
            if ($sample_value === null) {
                if (strpos($param_name, 'id') !== false || strpos($param_name, 'ID') !== false) {
                    $sample_value = '1';
                } else {
                    $sample_value = 'sample';
                }
            }
            
            $url = add_query_arg($param_name, $sample_value, $url);
            $params_added++;
            

        }
        

        
        return $url;
    }
    
    /**
     * Construir URL completa con todos los parámetros configurados (estáticos y dinámicos)
     * 
     * @param array $endpoint Configuración del endpoint
     * @return string URL completa con todos los parámetros
     */
    private function build_complete_test_url($endpoint) {
        $url = $endpoint['url'];
        

        
        // 1. Aplicar parámetros estáticos configurados
        if (isset($endpoint['params']) && is_array($endpoint['params'])) {
            foreach ($endpoint['params'] as $param) {
                if (!empty($param['name']) && isset($param['value'])) {
                    $param_name = $param['name'];
                    $param_value = $param['value'];
                    
                    // Asegurarnos de que los parámetros se apliquen correctamente
                    $url = add_query_arg($param_name, $param_value, $url);
                    

                }
            }
        }
        
        // 2. Aplicar parámetros dinámicos si existen
        $dynamic_params = $endpoint['dynamic_params'] ?? [];
        
        if (!empty($dynamic_params)) {
            foreach ($dynamic_params as $param) {
                $param_name = $param['name'] ?? '';
                $param_default = $param['default'] ?? '';
                
                if (empty($param_name)) {
                    continue;
                }
                
                // Usar valor por defecto si existe
                if (!empty($param_default)) {
                    $url = add_query_arg($param_name, $param_default, $url);
                    

                    continue;
                }
                
                // Usar un valor de muestra para el test
                $sample_value = '1';
                if (strpos($param_name, 'slug') !== false || 
                    strpos($param_name, 'name') !== false || 
                    strpos($param_name, 'category') !== false || 
                    strpos($param_name, 'tag') !== false) {
                    $sample_value = 'sample';
                }
                
                $url = add_query_arg($param_name, $sample_value, $url);
                

            }
        }
        

        
        return $url;
    }
    
    /**
     * Obtener los encabezados de autenticación para una petición a la API
     * 
     * @param array $endpoint Configuración del endpoint
     * @return array Encabezados de autenticación
     */
    private function get_auth_headers($endpoint) {
        $headers = [
            'User-Agent' => 'PostmanRuntime/7.44.0',
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'Cache-Control' => 'no-cache'
        ];
        
        // Añadir autenticación si está configurada
        if (!empty($endpoint['auth_type']) && $endpoint['auth_type'] !== 'none') {
            switch ($endpoint['auth_type']) {
                case 'bearer':
                    if (!empty($endpoint['auth_token'])) {
                        $headers['Authorization'] = 'Bearer ' . $endpoint['auth_token'];
                    }
                    break;
                case 'api_key':
                    if (!empty($endpoint['auth_key']) && !empty($endpoint['auth_value'])) {
                        // Preservar el nombre exacto del encabezado como lo configuró el usuario
                        // Esto es crucial para APIs que esperan un formato específico
                        $key_name = $endpoint['auth_key'];
                        $key_value = $endpoint['auth_value'];
                        
                        // Usar exactamente el nombre del encabezado configurado por el usuario
                        $headers[$key_name] = $key_value;
                        

                    }
                    break;
                case 'basic':
                    // Compatibilidad con ambos nombres de campo
                    $username = $endpoint_config['basic_user'] ?? $endpoint_config['auth_username'] ?? '';
                    $password = $endpoint_config['basic_password'] ?? $endpoint_config['auth_password'] ?? '';
                    if (!empty($username) && !empty($password)) {
                        $headers['Authorization'] = 'Basic ' . base64_encode($username . ':' . $password);
                    }
                    break;
            }
        }
        
        return $headers;
    }
    
    /**
     * Obtener los parámetros aplicados para mostrarlos en la respuesta
     * 
     * @param array $endpoint Configuración del endpoint
     * @return array Parámetros aplicados
     */
    private function get_applied_params($endpoint) {
        $params = [];
        
        // Parámetros estáticos
        if (isset($endpoint['params']) && is_array($endpoint['params'])) {
            foreach ($endpoint['params'] as $param) {
                if (!empty($param['name']) && isset($param['value'])) {
                    $params[$param['name']] = $param['value'];
                }
            }
        }
        
        // Parámetros dinámicos
        $dynamic_params = $endpoint['dynamic_params'] ?? [];
        if (!empty($dynamic_params)) {
            foreach ($dynamic_params as $param) {
                $param_name = $param['name'] ?? '';
                $param_default = $param['default'] ?? '';
                
                if (empty($param_name)) {
                    continue;
                }
                
                if (!empty($param_default)) {
                    $params[$param_name] = $param_default;
                } else {
                    // Valor de muestra para el test
                    $sample_value = '1';
                    if (strpos($param_name, 'slug') !== false || 
                        strpos($param_name, 'name') !== false || 
                        strpos($param_name, 'category') !== false || 
                        strpos($param_name, 'tag') !== false) {
                        $sample_value = 'sample';
                    }
                    $params[$param_name] = $sample_value;
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Renderizar Dynamic Tags diferenciados AUTO/MANUAL
     */
    public function render_dynamic_tags_dynamic($content, $post, $context) {
        if (strpos($content, '{snap_') === false) {
            return $content;
        }
        // Nuevo patrón: soporta notación de punto en el campo
        $content = preg_replace_callback(
            '/\{snap_(auto_)?([a-zA-Z0-9_]+)_([a-zA-Z0-9_.]+)\}/',
            function($matches) use ($post, $context) {
                $is_auto = !empty($matches[1]);
                $identifier = $matches[2];
                $field = $matches[3];
                $loop_object = \Bricks\Query::get_loop_object();
                if (!empty($loop_object) && isset($loop_object->api_data)) {
                    $api_data = $loop_object->api_data;
                    $value = $this->get_value_by_dot_notation($api_data, $field);
                    if ($value !== '' && $value !== null) {
                                return $this->format_field_output($value, $field);
                            }
                        }
                // Si no estamos en loop, obtener datos dinámicamente
                if ($is_auto) {
                    $value = $this->get_dynamic_field_value($identifier, $field, $post, $context);
                } else {
                    $value = $this->get_source_field_value($identifier, $field, $post, $context);
                }
                return $value !== '' ? $value : $matches[0];
            },
            $content
        );
        return $content;
    }
    
    /**
     * Obtener valor de un campo usando notación de punto (dot notation)
     */
    private function get_value_by_dot_notation($data, $path) {
        if (is_object($data)) $data = (array)$data;
        $parts = explode('.', $path);
        foreach ($parts as $part) {
            if (is_array($data) && isset($data[$part])) {
                $data = $data[$part];
            } else {
                return '';
            }
        }
        return $data;
    }
    
    /**
     * Obtener valor de campo desde source (tags manuales)
     */
    private function get_source_field_value($source_identifier, $field, $post = null, $context = null) {
        $sources = get_option('bricks_api_sources', []);
        $endpoints = get_option('bricks_api_endpoints', []);
        
        // Buscar el source por identificador
        $target_source = null;
        foreach ($sources as $source_id => $source) {
            if (sanitize_key($source['name']) === $source_identifier) {
                $target_source = $source;
                break;
            }
        }
        
        if (!$target_source) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Source no encontrado: {$source_identifier}");
            }
            return '';
        }
        
        // Obtener endpoint asociado
        $endpoint_id = $target_source['endpoint_id'] ?? '';
        if (!isset($endpoints[$endpoint_id])) {
            return '';
        }
        
        $endpoint = $endpoints[$endpoint_id];
        
        try {
            // Obtener datos del endpoint
            $raw_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
            
            if (empty($raw_data)) {
                return '';
            }
            
            // Aplicar items_path si está configurado
            if (!empty($target_source['items_path'])) {
                $api_data = $this->extract_nested_items($raw_data, $target_source['items_path']);
            } else {
                $api_data = $raw_data;
            }
            
            // Si es un array, tomar el primer elemento
            if (is_array($api_data) && isset($api_data[0])) {
                $api_data = $api_data[0];
            }
            
            $data_array = (array) $api_data;
            
            // Buscar el campo exacto
            if (isset($data_array[$field])) {
                return $this->format_field_output($data_array[$field], $field);
            }
            
            // Buscar variaciones del campo
            foreach ($data_array as $key => $value) {
                if (sanitize_key($key) === $field) {
                    return $this->format_field_output($value, $field);
                }
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error al obtener datos de source: " . $e->getMessage());
            }
        }
        
        return '';
    }

    /**
     * Manejar campos de array por índice
     */
    private function handle_array_item_field($data_array, $field, $context) {
        // Formato: especialidades_item_nombre
        $parts = explode('_item_', $field);
        if (count($parts) !== 2) {
            return '';
        }
        
        $array_name = $parts[0];
        $item_field = $parts[1];
        
        if (!isset($data_array[$array_name]) || !is_array($data_array[$array_name])) {
            return '';
        }
        
        // Obtener índice del contexto o usar 0 por defecto
        $index = $this->get_array_index_from_context($context) ?: 0;
        $array_data = $data_array[$array_name];
        
        if (isset($array_data[$index]) && is_array($array_data[$index])) {
            $item_data = $array_data[$index];
            if (isset($item_data[$item_field])) {
                return $this->format_field_output($item_data[$item_field], $item_field);
            }
        }
        
        return '';
    }
    
    /**
     * Manejar campos del último elemento del array
     */
    private function handle_array_last_field($data_array, $field) {
        // Formato: especialidades_last_nombre
        $parts = explode('_last_', $field);
        if (count($parts) !== 2) {
            return '';
        }
        
        $array_name = $parts[0];
        $item_field = $parts[1];
        
        if (!isset($data_array[$array_name]) || !is_array($data_array[$array_name])) {
            return '';
        }
        
        $array_data = $data_array[$array_name];
        $last_item = end($array_data);
        
        if (is_array($last_item) && isset($last_item[$item_field])) {
            return $this->format_field_output($last_item[$item_field], $item_field);
        }
        
        return '';
    }
    
    /**
     * Obtener índice de array del contexto (para bucles)
     */
    private function get_array_index_from_context($context) {
        // En contexto de loop, Bricks puede proporcionar índice
        if (isset($context['loop_index'])) {
            return $context['loop_index'];
        }
        
        // Obtener de variable global de Bricks si existe
        global $bricks_loop_index;
        if (isset($bricks_loop_index)) {
            return $bricks_loop_index;
        }
        
        return 0; // Por defecto, primer elemento
    }
    
    /**
     * Obtener valor de campo dinámico con parámetros
     */
    private function get_dynamic_field_value($endpoint_identifier, $field, $post = null, $context = null) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $target_endpoint = null;
        foreach ($endpoints as $endpoint) {
            if (sanitize_key($endpoint['name']) === $endpoint_identifier) {
                $target_endpoint = $endpoint;
                break;
            }
        }
        if (!$target_endpoint) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Endpoint no encontrado: {$endpoint_identifier}");
            }
            return '';
        }
        $dynamic_url = $this->build_dynamic_url($target_endpoint, $post, $context);
        try {
            $api_data = $this->get_api_data_with_cache($dynamic_url, $target_endpoint);
            if (empty($api_data)) {
                return '';
            }
            // Si es un array, tomar el primer elemento
            if (is_array($api_data) && isset($api_data[0])) {
                $api_data = $api_data[0];
            }
            // Usar notación de punto para buscar el campo
            $value = $this->get_value_by_dot_notation($api_data, $field);
            if ($value !== '' && $value !== null) {
                    return $this->format_field_output($value, $field);
                }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Error al obtener datos dinámicos: " . $e->getMessage());
            }
        }
        return '';
    }
    
    /**
     * Construir URL dinámica con parámetros del contexto actual - MEJORADO PARA PLACEHOLDERS
     */
    private function build_dynamic_url($endpoint, $post = null, $context = null) {
        $url = $endpoint['url'];
        $dynamic_params = $endpoint['dynamic_params'] ?? [];
        
        // NUEVO: Manejar placeholders en la URL (como {id})
        if (strpos($url, '{') !== false) {
            $url = $this->replace_url_placeholders($url, $endpoint, $post, $context);
        }
        
        if (empty($dynamic_params)) {
            return $url;
        }
        
        global $wp_query;
        
        foreach ($dynamic_params as $param) {
            $param_name = $param['name'] ?? '';
            $param_source = $param['source'] ?? 'url';
            $param_default = $param['default'] ?? '';
            
            if (empty($param_name)) {
                continue;
            }
            
            $param_value = $param_default; // Valor por defecto
            
            // Obtener valor según el origen
            switch ($param_source) {
                case 'url':
                    // Parámetro de URL
                    if (isset($_GET[$param_name])) {
                        $param_value = sanitize_text_field($_GET[$param_name]);
                    } elseif (get_query_var($param_name)) {
                        $param_value = get_query_var($param_name);
                    }
                    break;
                    
                case 'post':
                    // ID del post actual
                    if ($post && isset($post->ID)) {
                        $param_value = $post->ID;
                    } elseif (is_singular()) {
                        $param_value = get_the_ID();
                    } elseif (get_query_var('p')) {
                        $param_value = get_query_var('p');
                    }
                    break;
                    
                case 'post_slug':
                    // Slug del post actual
                    if ($post && isset($post->post_name)) {
                        $param_value = $post->post_name;
                    } elseif (is_singular()) {
                        $current_post = get_post();
                        $param_value = $current_post ? $current_post->post_name : '';
                    }
                    break;
                    
                case 'user':
                    // ID del usuario actual
                    $current_user = wp_get_current_user();
                    if ($current_user->exists()) {
                        $param_value = $current_user->ID;
                    }
                    break;
                    
                case 'meta':
                    // Meta field del post actual
                    if ($post && isset($post->ID)) {
                        $meta_value = get_post_meta($post->ID, $param_name, true);
                        if (!empty($meta_value)) {
                            $param_value = $meta_value;
                        }
                    }
                    break;
                    
                case 'static':
                    // Valor estático (ya asignado como default)
                    break;
            }
            
            // Agregar parámetro si tiene valor
            if ($param_value !== '') {
                $url = add_query_arg($param_name, $param_value, $url);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("URL dinámica construida: {$url}");
        }
        
        return $url;
    }
    
    /**
     * NUEVO: Reemplazar placeholders en URLs (como {id})
     */
    private function replace_url_placeholders($url, $endpoint, $post = null, $context = null) {
        // Patrón para encontrar placeholders: {variable}
        preg_match_all('/\{([^}]+)\}/', $url, $matches);
        
        if (empty($matches[1])) {
            return $url;
        }
        
        foreach ($matches[1] as $placeholder) {
            $replacement_value = $this->get_placeholder_value($placeholder, $endpoint, $post, $context);
            $url = str_replace('{' . $placeholder . '}', $replacement_value, $url);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Placeholder {$placeholder} reemplazado con: {$replacement_value}");
            }
        }
        
        return $url;
    }
    
    /**
     * NUEVO: Obtener valor para un placeholder
     */
    private function get_placeholder_value($placeholder, $endpoint, $post = null, $context = null) {
        // Buscar en parámetros dinámicos si hay uno que corresponda
        $dynamic_params = $endpoint['dynamic_params'] ?? [];
        
        foreach ($dynamic_params as $param) {
            if ($param['name'] === $placeholder) {
                return $this->get_param_value_by_source($param, $post, $context);
            }
        }
        
        // Valores por defecto según el nombre del placeholder
        switch ($placeholder) {
            case 'id':
                // ID del post actual
                if ($post && isset($post->ID)) {
                    return $post->ID;
                } elseif (is_singular()) {
                    return get_the_ID();
                }
                return '12'; // Valor por defecto para testing
                
            case 'slug':
                // Slug del post actual
                if ($post && isset($post->post_name)) {
                    return $post->post_name;
                } elseif (is_singular()) {
                    $current_post = get_post();
                    return $current_post ? $current_post->post_name : '';
                }
                return 'sample-slug';
                
            case 'user_id':
                // ID del usuario actual
                $current_user = wp_get_current_user();
                return $current_user->exists() ? $current_user->ID : '1';
                
            default:
                return $placeholder; // Si no se puede resolver, devolver el placeholder
        }
    }
    
    /**
     * NUEVO: Obtener valor de parámetro según su fuente
     */
    private function get_param_value_by_source($param, $post = null, $context = null) {
        $param_source = $param['source'] ?? 'url';
        $param_default = $param['default'] ?? '';
        $param_name = $param['name'] ?? '';
        
        switch ($param_source) {
            case 'post':
                if ($post && isset($post->ID)) {
                    return $post->ID;
                } elseif (is_singular()) {
                    return get_the_ID();
                }
                return $param_default;
                
            case 'post_slug':
                if ($post && isset($post->post_name)) {
                    return $post->post_name;
                } elseif (is_singular()) {
                    $current_post = get_post();
                    return $current_post ? $current_post->post_name : $param_default;
                }
                return $param_default;
                
            case 'url':
                if (isset($_GET[$param_name])) {
                    return sanitize_text_field($_GET[$param_name]);
                } elseif (get_query_var($param_name)) {
                    return get_query_var($param_name);
                }
                return $param_default;
                
            case 'meta':
                if ($post && isset($post->ID)) {
                    $meta_value = get_post_meta($post->ID, $param_name, true);
                    return !empty($meta_value) ? $meta_value : $param_default;
                }
                return $param_default;
                
            case 'user':
                $current_user = wp_get_current_user();
                return $current_user->exists() ? $current_user->ID : $param_default;
                
            case 'static':
            default:
                return $param_default;
        }
    }
    
    /**
     * Formatear salida de campo - MEJORADO PARA ARRAYS
     */
    private function format_field_output($value, $field_name = '') {
        if (is_array($value)) {
            // Detectar tipo de array y formatearlo apropiadamente
            $array_type = $this->detect_array_type_for_output($value);
            
            switch ($array_type) {
                case 'simple_list':
                    // Lista simple: ["item1", "item2", "item3"]
                    if (strpos($field_name, '_first') !== false) {
                        return isset($value[0]) ? sanitize_text_field((string) $value[0]) : '';
                    } elseif (strpos($field_name, '_count') !== false) {
                        return count($value);
                    } elseif (strpos($field_name, '_join') !== false) {
                        return implode(', ', array_map('sanitize_text_field', array_slice($value, 0, 5)));
                    } else {
                        // Default: mostrar primeros elementos separados por coma
                        return implode(', ', array_map('sanitize_text_field', array_slice($value, 0, 3)));
                    }
                    
                case 'object_list':
                    // Array de objetos: [{"nombre": "Juan"}, {"nombre": "María"}]
                    if (strpos($field_name, '_count') !== false) {
                        return count($value);
                    } elseif (strpos($field_name, '_first_') !== false) {
                        // Extraer campo específico del primer objeto
                        $field_parts = explode('_first_', $field_name);
                        if (count($field_parts) > 1) {
                            $sub_field = end($field_parts);
                            if (isset($value[0][$sub_field])) {
                                return $this->format_field_output($value[0][$sub_field]);
                            }
                        }
                        return '';
                    } else {
                        // Mostrar cantidad de elementos
                        return count($value) . ' elementos';
                    }
                    
                case 'associative':
                    // Array asociativo - mostrar como JSON limpio
                    if (strpos($field_name, '_json') !== false) {
                        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    } else {
                        // Mostrar primeros pares clave-valor
                        $pairs = [];
                        $count = 0;
                        foreach ($value as $k => $v) {
                            if ($count >= 3) break;
                            $pairs[] = $k . ': ' . (is_scalar($v) ? $v : 'objeto');
                            $count++;
                        }
                        return implode(', ', $pairs);
                    }
                    
                case 'mixed':
                default:
                    // Array mixto o complejo - mostrar como JSON
                    if (strpos($field_name, '_json') !== false) {
                        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    } else {
                        return count($value) . ' elementos (mixtos)';
                    }
            }
            
        } elseif (is_object($value)) {
            // Objeto - convertir a array y procesar
            return $this->format_field_output((array) $value, $field_name);
            
        } elseif (is_bool($value)) {
            return $value ? 'Sí' : 'No';
            
        } elseif (is_null($value)) {
            return '';
            
        } else {
            // Valor escalar (string, number)
            return sanitize_text_field((string) $value);
        }
    }
    
    /**
     * Detectar tipo de array para output (similar al field extractor)
     */
    private function detect_array_type_for_output($array) {
        if (empty($array)) {
            return 'empty';
        }
        
        // Verificar si es array indexado vs asociativo
        $keys = array_keys($array);
        $is_indexed = ($keys === array_keys($keys));
        
        if (!$is_indexed) {
            return 'associative';
        }
        
        // Es array indexado, verificar contenido
        $types = array_map('gettype', array_slice($array, 0, 3));
        $unique_types = array_unique($types);
        
        if (count($unique_types) > 1) {
            return 'mixed';
        }
        
        $dominant_type = $unique_types[0];
        
        switch ($dominant_type) {
            case 'array':
            case 'object':
                return 'object_list';
            case 'string':
            case 'integer':
            case 'double':
            case 'boolean':
                return 'simple_list';
            default:
                return 'mixed';
        }
    }
    
    /**
     * ADMIN PANEL METHODS
     */
    
    public function add_admin_menu() {
        // Usar las funciones existentes del archivo includes
        bricks_api_integrator_menu();
    }
    
    public function register_settings() {
        register_setting('bricks_api_integrator_settings', 'bricks_api_endpoints');
        register_setting('bricks_api_integrator_settings', 'bricks_api_sources');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'bricks-api-integrator') === false) {
            return;
        }
        bricks_api_integrator_assets();
        // Pasar variables globales al JS principal
        wp_localize_script('bricks-api-integrator-js', 'bricksApiIntegrator', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'saveSingleApiEndpointNonce' => wp_create_nonce('save_single_api_endpoint'),
        ]);
    }
    
    /**
     * AJAX HANDLERS
     */
    
    public function ajax_test_api_endpoint() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'test_api_endpoint')) {
            wp_die('Sin permisos');
        }
        
        $index = intval($_POST['index']);
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($endpoints[$index])) {
            wp_send_json_error(['message' => 'Endpoint no encontrado']);
        }
        
        $endpoint = $endpoints[$index];
        
        try {
            // Construir URL con todos los parámetros configurados (estáticos y dinámicos)
            $test_url = $this->build_complete_test_url($endpoint);
            
            // Obtener los valores de autenticación
            $auth_type = isset($endpoint['auth_type']) ? $endpoint['auth_type'] : 'none';
            $auth_key = isset($endpoint['auth_key']) ? trim($endpoint['auth_key']) : '';
            $auth_value = isset($endpoint['auth_value']) ? $endpoint['auth_value'] : '';
            
            // Configurar encabezados básicos de Postman
            $headers = [
                'User-Agent' => 'PostmanRuntime/7.44.0',
                'Accept' => '*/*',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache'
            ];
            
            // Configuración básica de la solicitud
            $args = [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'PostmanRuntime/7.44.0',
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive',
                    'Cache-Control' => 'no-cache'
                ]
            ];
            
            // Añadir la API key directamente a los encabezados
            if ($auth_type === 'api_key' && !empty($auth_key) && !empty($auth_value)) {
                $args['headers'][$auth_key] = $auth_value;
            }
            
            // Añadir API Key directamente a los encabezados de la solicitud
            if ($auth_type === 'api_key' && !empty($auth_key) && !empty($auth_value)) {
                $args['headers'][$auth_key] = $auth_value;
                

            }
            
            // Añadir Bearer Token si está configurado
            if ($auth_type === 'bearer' && !empty($endpoint['auth_token'])) {
                $args['headers']['Authorization'] = 'Bearer ' . $endpoint['auth_token'];
            }
            
            // Añadir Basic Auth si está configurado
            if ($auth_type === 'basic' && !empty($endpoint['auth_username']) && !empty($endpoint['auth_password'])) {
                $args['headers']['Authorization'] = 'Basic ' . base64_encode($endpoint['auth_username'] . ':' . $endpoint['auth_password']);
            }
            
            // Registrar los encabezados finales para depuración

            
            // Registrar los encabezados para depuración

            
            // Añadir la API key directamente al encabezado si no está presente
            if ($auth_type === 'api_key' && !empty($auth_key) && !empty($auth_value)) {
                // Forzar la API key en el encabezado correcto
                $args['headers'][$auth_key] = $auth_value;
                
                // Si la URL contiene api-sports.io o api-football, añadir el encabezado específico
                if (strpos($test_url, 'api-sports.io') !== false || strpos($test_url, 'api-football') !== false) {
                    // Para esta API específica, asegurarnos de que el encabezado tenga el formato correcto
                    $args['headers']['x-apisports-key'] = $auth_value;
                }
                
                // Registrar en el log para depuración

            }
            
            // Mostrar información de depuración

            
            // LOG: Registrar URL y headers antes de la petición
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('API TEST DEBUG - URL: ' . $test_url);
                error_log('API TEST DEBUG - Headers: ' . print_r($args['headers'], true));
            }
            // Realizar la petición a la API
            $response = wp_remote_get($test_url, $args);
            // LOG: Registrar código de estado y cuerpo de la respuesta
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $status_code = wp_remote_retrieve_response_code($response);
                error_log('API TEST DEBUG - Status Code: ' . $status_code);
                $body = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
                error_log('API TEST DEBUG - Body: ' . (is_string($body) ? substr($body, 0, 1000) : print_r($body, true)));
            }
            
            if (is_wp_error($response)) {
                wp_send_json_error([
                    'message' => 'Error de conexión: ' . $response->get_error_message(),
                    'test_url' => $test_url,
                    'base_url' => $endpoint['url']
                ]);
                return;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $response_body = wp_remote_retrieve_body($response);
                $response_headers = wp_remote_retrieve_headers($response);
                
                // Intentar decodificar la respuesta de error para mostrar información más detallada
                $error_details = '';
                $decoded_error = json_decode($response_body, true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded_error)) {
                    $error_details = ' - Detalles: ' . json_encode($decoded_error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
                
                // Registrar información detallada en el log para depuración
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Error en la API: Código ' . $status_code);
                    error_log('URL: ' . $test_url);
                    error_log('Encabezados enviados: ' . print_r($headers, true));
                    error_log('Encabezados recibidos: ' . print_r($response_headers, true));
                    error_log('Cuerpo de la respuesta: ' . $response_body);
                }
                
                // Crear una copia de los encabezados para mostrar en la respuesta
                $headers_for_display = $args['headers'];
                
                // Si es API key, asegurarnos de que se muestre en la respuesta
                if ($auth_type === 'api_key' && !empty($auth_key) && !empty($auth_value)) {
                    $headers_for_display[$auth_key] = $auth_value;
                }
                
                wp_send_json_error([
                    'message' => 'La API devolvió un código de estado no válido: ' . $status_code . $error_details,
                    'test_url' => $test_url,
                    'base_url' => $endpoint['url'],
                    'response_body' => $response_body,
                    'headers_sent' => $headers_for_display,
                    'headers_received' => $response_headers,
                    'auth_config' => [
                        'type' => $auth_type,
                        'key_name' => $auth_key,
                        'has_value' => !empty($auth_value) ? 'yes' : 'no'
                    ]
                ]);
                return;
            }
            
            // Obtener el cuerpo de la respuesta
            $body = wp_remote_retrieve_body($response);
            
            // Intentar decodificar el JSON
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error([
                    'message' => 'Error al decodificar JSON: ' . json_last_error_msg(),
                    'test_url' => $test_url,
                    'base_url' => $endpoint['url'],
                    'response_body' => $body
                ]);
                return;
            }
            
            if (empty($data)) {
                wp_send_json_error([
                    'message' => 'No se obtuvieron datos de la API. Verifica la URL y autenticación.',
                    'test_url' => $test_url,
                    'base_url' => $endpoint['url']
                ]);
                return;
            }
            
            // Analizar estructura de datos
            $sample_fields = [];
            $sample_data = null;
            $count = 0;
            

            
            if (is_array($data)) {
                $count = count($data);
                if (isset($data[0])) {
                    $sample_data = $data[0];
                    $fields = $this->extract_fields_from_data($data[0]);
                    $sample_fields = array_slice(array_keys($fields), 0, 8);
                }
            } else {
                $count = 1;
                $sample_data = $data;
                $fields = $this->extract_fields_from_data($data);
                $sample_fields = array_slice(array_keys($fields), 0, 8);
            }
            
            // Construir URL de test para mostrar (con todos los parámetros)
            $test_url = $this->build_complete_test_url($endpoint);
            
            // Obtener el cuerpo de la respuesta original sin procesar
            $original_body = wp_remote_retrieve_body($response);
            
            // Asegurarnos de que estamos pasando el payload completo sin procesar
            // Esto garantiza que se muestre exactamente lo que devuelve la API
            $full_response_json = $original_body;
            
            // Verificar que el JSON sea válido y esté bien formateado
            $decoded_json = json_decode($original_body);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Si el JSON es válido, lo re-codificamos con formato bonito
                // Esto asegura que se muestre correctamente en el frontend
                $full_response_json = json_encode($decoded_json, 
                    JSON_PRETTY_PRINT | 
                    JSON_UNESCAPED_UNICODE | 
                    JSON_UNESCAPED_SLASHES | 
                    JSON_PRESERVE_ZERO_FRACTION
                );
            }
            
            // Limitar el tamaño del payload solo si es extremadamente grande para evitar problemas de memoria
            if (strlen($full_response_json) > 1000000) {
                $full_response_json = substr($full_response_json, 0, 1000000) . '... (truncado por tamaño excesivo)';
            }
            
            // Analizar estructura de datos
            $sample_fields = [];
            $sample_data = null;
            $count = 0;
            $total_items = 0;
            
            if (is_array($data)) {
                // Manejar estructura de respuesta tipo array
                if (isset($data['items']) && is_array($data['items'])) {
                    // Formato común: { items: [...], total: X }
                    $count = count($data['items']);
                    $total_items = isset($data['total']) ? $data['total'] : $count;
                    
                    if ($count > 0) {
                        $sample_data = $data['items'][0];
                        $fields = $this->extract_fields_from_data($sample_data);
                        $sample_fields = array_slice(array_keys($fields), 0, 12);
                    }
                } else if (isset($data[0])) {
                    // Formato de array simple: [item1, item2, ...]
                    $count = count($data);
                    $total_items = $count;
                    $sample_data = $data[0];
                    $fields = $this->extract_fields_from_data($sample_data);
                    $sample_fields = array_slice(array_keys($fields), 0, 12);
                } else {
                    // Objeto simple
                    $count = 1;
                    $total_items = 1;
                    $sample_data = $data;
                    $fields = $this->extract_fields_from_data($data);
                    $sample_fields = array_slice(array_keys($fields), 0, 12);
                }
            } else {
                $count = 1;
                $total_items = 1;
                $sample_data = $data;
                $fields = $this->extract_fields_from_data($data);
                $sample_fields = array_slice(array_keys($fields), 0, 12);
            }
            
            wp_send_json_success([
                'count' => $count,
                'total_items' => $total_items,
                'sample_fields' => $sample_fields,
                'sample_data' => $sample_data,
                'message' => 'API funcionando correctamente',
                'test_url' => $test_url,
                'base_url' => $endpoint['url'],
                'full_response' => $full_response_json,
                'response_structure' => $this->analyze_data_structure($data),
                'params_applied' => $this->get_applied_params($endpoint)
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_test_advanced_api_endpoint() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'test_advanced_api_endpoint')) {
            wp_die('Sin permisos');
        }
        
        $index = intval($_POST['index']);
        $custom_url = sanitize_url($_POST['url']);
        $custom_headers = $_POST['headers'] ?? [];
        
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($endpoints[$index])) {
            wp_send_json_error(['message' => 'Endpoint no encontrado']);
        }
        
        $endpoint = $endpoints[$index];
        
        try {
            // Preparar headers básicos del endpoint
            $headers = $this->prepare_request_headers($endpoint);
            
            // Añadir headers personalizados
            if (!empty($custom_headers) && is_array($custom_headers)) {
                foreach ($custom_headers as $key => $value) {
                    $headers[sanitize_text_field($key)] = sanitize_text_field($value);
                }
            }
            
            // Hacer petición con URL personalizada
            $response = wp_remote_get($custom_url, [
                'headers' => $headers,
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                wp_send_json_error(['message' => 'Error de conexión: ' . $response->get_error_message()]);
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                wp_send_json_error(['message' => "Error HTTP {$status_code}"]);
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Respuesta no es JSON válido']);
            }
            
            if (empty($data)) {
                wp_send_json_error(['message' => 'Respuesta vacía de la API']);
            }
            
            // Preparar datos de respuesta
            $sample_fields = [];
            $sample_data = null;
            
            if (is_array($data)) {
                $count = count($data);
                if (isset($data[0])) {
                    $fields = $this->extract_fields_from_data($data[0]);
                    $sample_fields = array_slice(array_keys($fields), 0, 8);
                    $sample_data = $data[0];
                }
            } else {
                $count = 1;
                $fields = $this->extract_fields_from_data($data);
                $sample_fields = array_slice(array_keys($fields), 0, 8);
                $sample_data = $data;
            }
            
            wp_send_json_success([
                'count' => $count,
                'sample_fields' => $sample_fields,
                'sample_data' => $sample_data,
                'message' => 'Test avanzado completado exitosamente'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error inesperado: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_get_dynamic_tags_for_endpoint() {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX: ajax_get_dynamic_tags_for_endpoint called');
            error_log('POST data: ' . print_r($_POST, true));
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos de administrador']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'get_dynamic_tags')) {
            wp_send_json_error(['message' => 'Nonce inválido', 'nonce_received' => $_POST['nonce']]);
            return;
        }
        
        $index = intval($_POST['index']);
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($endpoints[$index])) {
            wp_send_json_error(['message' => 'Endpoint no encontrado', 'index' => $index, 'total_endpoints' => count($endpoints)]);
            return;
        }
        
        $endpoint = $endpoints[$index];
        
        // Log para debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX: Obteniendo dynamic tags para endpoint: ' . $endpoint['name']);
        }
        
        try {
            // Primero intentar obtener datos con parámetros dinámicos
            $sample_data = $this->get_api_data_with_dynamic_params($endpoint);
            
            // Si no hay datos, intentar sin parámetros dinámicos
            if (empty($sample_data)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('AJAX: No hay datos con parámetros, intentando sin parámetros');
                }
                $sample_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
            }
            
            if (empty($sample_data)) {
                wp_send_json_error([
                    'message' => 'No se pudieron obtener datos del endpoint. Verifica la configuración.',
                    'debug_info' => [
                        'endpoint_name' => $endpoint['name'],
                        'endpoint_url' => $endpoint['url'],
                        'has_params' => !empty($endpoint['dynamic_params'])
                    ]
                ]);
                return;
            }
            

            
            // Extraer campos dinámicamente
            $sample_item = is_array($sample_data) && isset($sample_data[0]) ? $sample_data[0] : $sample_data;
            $fields = $this->extract_fields_from_data($sample_item);
            
            if (empty($fields)) {
                wp_send_json_error([
                    'message' => 'No se encontraron campos válidos en los datos del endpoint.',
                    'sample_data_preview' => is_array($sample_data) ? array_keys($sample_data) : gettype($sample_data)
                ]);
                return;
            }
            
            // Generar dynamic tags con el prefijo correcto
            $endpoint_slug = sanitize_key($endpoint['name']);
            $dynamic_tags = [];
            
            foreach ($fields as $field => $label) {
                $tag = '{snap_' . $endpoint_slug . '_' . $field . '}';
                $dynamic_tags[] = $tag;
            }
            
            // Ordenar tags para mejor visualización
            sort($dynamic_tags);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX: Generados ' . count($dynamic_tags) . ' dynamic tags para ' . $endpoint['name']);
            }
            
            wp_send_json_success([
                'tags' => $dynamic_tags,
                'count' => count($dynamic_tags),
                'endpoint_name' => $endpoint['name'],
                'fields_info' => $fields,
                'sample_data' => $sample_item, // Añadir datos de ejemplo para mostrar valores
                'has_dynamic_params' => !empty($endpoint['dynamic_params']),
                'data_type' => is_array($sample_data) ? 'array' : 'object',
                'data_count' => is_array($sample_data) ? count($sample_data) : 1
            ]);
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX: Error al generar dynamic tags: ' . $e->getMessage());
            }
            
            wp_send_json_error([
                'message' => 'Error al generar dynamic tags: ' . $e->getMessage(),
                'endpoint_name' => $endpoint['name'] ?? 'Desconocido'
            ]);
        }
    }
    
    public function ajax_clear_api_cache() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'clear_api_cache')) {
            wp_die('Sin permisos');
        }
        
        $this->clear_all_cache();
        wp_send_json_success(['message' => 'Cache limpiado correctamente']);
    }
    
    public function ajax_regenerate_bricks_integration() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'regenerate_bricks_integration')) {
            wp_die('Sin permisos');
        }
        
        try {
            // Limpiar cache primero
            $this->clear_all_cache();
            
            // Forzar regeneración de datos
            $endpoints = get_option('bricks_api_endpoints', []);
            $sources = get_option('bricks_api_sources', []);
            
            $regenerated_count = 0;
            
            // Forzar recarga de datos de endpoints
            foreach ($endpoints as $endpoint) {
                if (!empty($endpoint['url'])) {
                    $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
                    $regenerated_count++;
                }
            }
            
            // Regenerar dynamic tags
            $tags = $this->add_dynamic_tags_dynamic([]);
            $tags_count = count($tags);
            
            wp_send_json_success([
                'message' => "Regeneración completada. {$regenerated_count} endpoints actualizados, {$tags_count} dynamic tags generados.",
                'endpoints' => $regenerated_count,
                'tags' => $tags_count
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante la regeneración: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_clean_duplicates() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'clean_duplicates')) {
            wp_die('Sin permisos');
        }
        
        try {
            if (function_exists('clean_bricks_api_integrator_duplicates')) {
                $result = clean_bricks_api_integrator_duplicates();
                
                wp_send_json_success([
                    'message' => "Duplicados eliminados. Endpoints: {$result['endpoints_before']} → {$result['endpoints_after']}, Sources: {$result['sources_before']} → {$result['sources_after']}"
                ]);
            } else {
                wp_send_json_error(['message' => 'Función de limpieza no disponible']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante la limpieza: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_clean_query_types() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'clean_query_types')) {
            wp_die('Sin permisos');
        }
        
        try {
            if (function_exists('clean_bricks_api_query_types_duplicates')) {
                $result = clean_bricks_api_query_types_duplicates();
                
                wp_send_json_success([
                    'message' => $result['message']
                ]);
            } else {
                wp_send_json_error(['message' => 'Función de limpieza de Query Types no disponible']);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante la limpieza: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_clean_debug_sources() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'clean_debug_sources')) {
            wp_die('Sin permisos');
        }
        
        try {
            $sources = get_option('bricks_api_sources', []);
            $initial_count = count($sources);
            
            // Eliminar sources de debug/prueba
            $debug_keys = ['debug_api_source', 'debug_source', 'api_source_debug', 'test_source'];
            $cleaned_count = 0;
            
            foreach ($debug_keys as $key) {
                if (isset($sources[$key])) {
                    unset($sources[$key]);
                    $cleaned_count++;
                }
            }
            
            update_option('bricks_api_sources', $sources);
            
            wp_send_json_success([
                'message' => "Sources de debug eliminados: {$cleaned_count}. Total sources: {$initial_count} → " . count($sources)
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante la limpieza: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_reset_plugin_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'reset_plugin_data')) {
            wp_die('Sin permisos');
        }
        
        try {
            // Limpiar todas las opciones del plugin
            delete_option('bricks_api_endpoints');
            delete_option('bricks_api_sources');
            delete_option('bricks_api_launchers');
            delete_option('bricks_api_templates');
            
            // Recrear opciones vacías
            update_option('bricks_api_endpoints', []);
            update_option('bricks_api_sources', []);
            update_option('bricks_api_launchers', []);
            update_option('bricks_api_templates', []);
            
            // Limpiar todo el cache
            $this->clear_all_cache();
            
            // Limpiar también opciones de WordPress relacionadas
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%bricks_api%'");
            
            // Recrear opciones básicas después de la limpieza agresiva
            update_option('bricks_api_endpoints', []);
            update_option('bricks_api_sources', []);
            update_option('bricks_api_launchers', []);
            update_option('bricks_api_templates', []);
            
            wp_send_json_success([
                'message' => 'Todos los datos del plugin han sido eliminados y reiniciados correctamente.'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante el reset: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX handler para actualizar duración de caché
     */
    public function ajax_update_cache_duration() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'update_cache_duration')) {
            wp_die('Sin permisos');
        }
        
        $duration = intval($_POST['duration']);
        
        // Validar duración
        $valid_durations = [0, 60, 300, 900, 3600]; // 0 = sin caché, 1min, 5min, 15min, 1hora
        if (!in_array($duration, $valid_durations)) {
            wp_send_json_error(['message' => 'Duración de caché inválida']);
            return;
        }
        
        // Guardar configuración
        update_option('bricks_api_cache_duration', $duration);
        
        // Limpiar caché existente para aplicar la nueva configuración
        $this->clear_all_cache();
        
        $duration_text = '';
        switch ($duration) {
            case 0: $duration_text = 'Sin caché (siempre actualizado)'; break;
            case 60: $duration_text = '1 minuto'; break;
            case 300: $duration_text = '5 minutos'; break;
            case 900: $duration_text = '15 minutos'; break;
            case 3600: $duration_text = '1 hora'; break;
        }
        
        wp_send_json_success([
            'message' => "Duración de caché actualizada a: {$duration_text}",
            'duration' => $duration
        ]);
    }
    
    /**
     * AJAX handler para obtener duración actual de caché
     */
    public function ajax_get_cache_duration() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'get_cache_duration')) {
            wp_die('Sin permisos');
        }
        
        $duration = get_option('bricks_api_cache_duration', 300); // 5 minutos por defecto
        
        wp_send_json_success([
            'duration' => $duration
        ]);
    }
    
    /**
     * AJAX handler para refrescar datos de un endpoint específico
     */
    public function ajax_refresh_endpoint_data() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'refresh_endpoint_data')) {
            wp_die('Sin permisos');
        }
        
        $index = intval($_POST['index']);
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($endpoints[$index])) {
            wp_send_json_error(['message' => 'Endpoint no encontrado']);
        }
        
        $endpoint = $endpoints[$index];
        
        try {
            // Limpiar todas las cachés relacionadas con este endpoint
            $cache_key_base = 'api_data_' . md5($endpoint['url']);
            $cache_key = $cache_key_base . serialize($endpoint);
            delete_transient($cache_key);
            
            // También limpiar otras posibles cachés relacionadas
            global $wpdb;
            $like = '%' . $wpdb->esc_like($cache_key_base) . '%';
            $keys = $wpdb->get_col($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                AND option_name LIKE %s",
                '_transient_%', $like
            ));
            
            foreach ($keys as $key) {
                $key = str_replace('_transient_', '', $key);
                delete_transient($key);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Eliminada caché: ' . $key);
                }
            }
            
            // Verificar si el endpoint tiene parámetros configurados
            $has_static_params = !empty($endpoint['params']) && is_array($endpoint['params']);
            $has_dynamic_params = !empty($endpoint['dynamic_params']) && is_array($endpoint['dynamic_params']);
            
            // Registrar para depuración
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Actualizando endpoint: ' . $endpoint['name']);
                error_log('URL base: ' . $endpoint['url']);
                error_log('Tiene parámetros estáticos: ' . ($has_static_params ? 'Sí' : 'No'));
                error_log('Tiene parámetros dinámicos: ' . ($has_dynamic_params ? 'Sí' : 'No'));
                
                if ($has_static_params) {
                    error_log('Parámetros estáticos: ' . print_r($endpoint['params'], true));
                }
                if ($has_dynamic_params) {
                    error_log('Parámetros dinámicos: ' . print_r($endpoint['dynamic_params'], true));
                }
            }
            
            // Construir la URL completa con todos los parámetros configurados
            $test_url = $this->build_complete_test_url($endpoint);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('URL completa para actualización: ' . $test_url);
            }
            
            // Configurar encabezados
            $headers = [
                'User-Agent' => 'PostmanRuntime/7.44.0',
                'Accept' => '*/*',
                'Connection' => 'keep-alive',
                'Cache-Control' => 'no-cache'
            ];
            
            // Añadir encabezado de API Key si está configurado
            if ($endpoint['auth_type'] === 'api_key' && !empty($endpoint['auth_key']) && !empty($endpoint['auth_value'])) {
                $headers[$endpoint['auth_key']] = $endpoint['auth_value'];
            }
            
            // Añadir encabezado de Bearer Token si está configurado
            if ($endpoint['auth_type'] === 'bearer' && !empty($endpoint['auth_token'])) {
                $headers['Authorization'] = 'Bearer ' . $endpoint['auth_token'];
            }
            
            // Añadir encabezado de Basic Auth si está configurado
            if ($endpoint['auth_type'] === 'basic' && !empty($endpoint['auth_username']) && !empty($endpoint['auth_password'])) {
                $headers['Authorization'] = 'Basic ' . base64_encode($endpoint['auth_username'] . ':' . $endpoint['auth_password']);
            }
            
            // Realizar la petición directamente sin usar la caché
            $response = wp_remote_get($test_url, [
                'timeout' => 30,
                'headers' => $headers
            ]);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                throw new Exception('La API devolvió un código de estado no válido: ' . $status_code);
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
            }
            
            // Registrar la respuesta para depuración
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Respuesta recibida correctamente');
                error_log('Tipo de datos: ' . gettype($data));
                error_log('Estructura: ' . (is_array($data) ? 'Array con ' . count($data) . ' elementos' : 'No es un array'));
            }
            
            if (empty($data)) {
                wp_send_json_error([
                    'message' => 'La API devolvió una respuesta vacía. Verifica la URL y configuración.',
                    'test_url' => $test_url,
                    'has_static_params' => $has_static_params,
                    'has_dynamic_params' => $has_dynamic_params
                ]);
                return;
            }
            
            // Analizar estructura de datos
            $sample_fields = [];
            $sample_data = null;
            $count = 0;
            
            if (is_array($data)) {
                $count = count($data);
                if (isset($data[0])) {
                    $sample_data = $data[0];
                    $fields = $this->extract_fields_from_data($data[0]);
                    $sample_fields = array_slice(array_keys($fields), 0, 8);
                }
            } else {
                $count = 1;
                $sample_data = $data;
                $fields = $this->extract_fields_from_data($data);
                $sample_fields = array_slice(array_keys($fields), 0, 8);
            }
            
            wp_send_json_success([
                'count' => $count,
                'sample_fields' => $sample_fields,
                'sample_data' => $sample_data,
                'message' => 'Datos actualizados correctamente desde la API',
                'cache_cleared' => true,
                'endpoint_name' => $endpoint['name']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error al actualizar datos: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX handler para probar el items_path en un endpoint
     */
    public function ajax_test_items_path() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'test_items_path')) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
        
        $endpoint_id = sanitize_text_field($_POST['endpoint_id']);
        $items_path = sanitize_text_field($_POST['items_path']);
        $filter_field = isset($_POST['filter_field']) ? sanitize_text_field($_POST['filter_field']) : '';
        $filter_value = isset($_POST['filter_value']) ? sanitize_text_field($_POST['filter_value']) : '';
        
        $endpoints = get_option('bricks_api_endpoints', []);
        
        if (!isset($endpoints[$endpoint_id])) {
            wp_send_json_error(['message' => 'Endpoint no encontrado']);
        }
        
        $endpoint = $endpoints[$endpoint_id];
        
        // Obtener la URL del endpoint sin modificaciones automáticas
        $url = $endpoint['url'];
        $params_applied = [];
        
        // Aplicar todos los parámetros configurados en el endpoint
        if (isset($endpoint['params']) && is_array($endpoint['params'])) {
            foreach ($endpoint['params'] as $param) {
                if (!empty($param['name']) && isset($param['value'])) {
                    $param_name = $param['name'];
                    $param_value = $param['value'];
                    
                    // Convertir valores booleanos a su representación correcta
                    if ($param_value === 'true') {
                        $param_value = true;
                    } elseif ($param_value === 'false') {
                        $param_value = false;
                    }
                    
                    // Determinar si el parámetro es para la URL o para el cuerpo
                    if (strpos($url, '{' . $param_name . '}') !== false) {
                        // Reemplazar en la URL
                        $url = str_replace('{' . $param_name . '}', urlencode($param_value), $url);
                    } else {
                        // Añadir como parámetro de consulta
                        $url = add_query_arg($param_name, $param_value, $url);
                    }
                    
                    $params_applied[$param_name] = $param_value;
                }
            }
        }
        
        // Actualizar la URL en la configuración del endpoint para la solicitud
        $endpoint['url'] = $url;
        
        try {
            // Obtener datos de la API (sin caché para pruebas)
            $raw_data = $this->get_api_data_with_cache($url, $endpoint, true);
            
            if (empty($raw_data)) {
                wp_send_json_error(['message' => 'No se pudieron obtener datos de la API']);
            }
            
            // Aplicar items_path si está configurado
            if (!empty($items_path)) {
                $items_data = $this->extract_nested_items($raw_data, $items_path);
                
                // Verificar si se obtuvieron datos
                if (empty($items_data)) {
                    // Analizar la estructura de los datos para ayudar en la depuración
                    $structure_info = $this->analyze_data_structure($raw_data);
                    
                    // Intentar buscar automáticamente la ruta correcta
                    $auto_detected_path = '';
                    $auto_detected_data = null;
                    
                    // Buscar rutas comunes
                    $common_paths = ['items', 'data', 'results', 'content', 'list', 'records', 'vehicles'];
                    foreach ($common_paths as $path) {
                        if (is_array($raw_data) && isset($raw_data[$path]) && !empty($raw_data[$path])) {
                            $auto_detected_path = $path;
                            $auto_detected_data = $raw_data[$path];
                            break;
                        }
                    }
                    
                    // Si no se encontró en el primer nivel, buscar en el segundo nivel
                    if (empty($auto_detected_path)) {
                        foreach ($common_paths as $path1) {
                            if (is_array($raw_data) && isset($raw_data[$path1]) && is_array($raw_data[$path1])) {
                                foreach ($common_paths as $path2) {
                                    if (isset($raw_data[$path1][$path2]) && !empty($raw_data[$path1][$path2])) {
                                        $auto_detected_path = $path1 . '.' . $path2;
                                        $auto_detected_data = $raw_data[$path1][$path2];
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Mostrar la estructura completa de la respuesta para depuración
                    $full_response = json_encode($raw_data, JSON_PRETTY_PRINT);
                    if (strlen($full_response) > 5000) {
                        $full_response = substr($full_response, 0, 5000) . '... (truncado)';
                    }
                    
                    wp_send_json_error([
                        'message' => 'No se encontraron datos en la ruta especificada',
                        'path' => $items_path,
                        'raw_data_preview' => json_encode(array_keys(is_array($raw_data) ? $raw_data : []), JSON_PRETTY_PRINT),
                        'structure_info' => $structure_info,
                        'raw_data_sample' => $this->get_sample_data($raw_data),
                        'auto_detected_path' => $auto_detected_path,
                        'full_response' => $full_response
                    ]);
                }
                
                // Aplicar filtro si se especificó
                if (!empty($filter_field) && $filter_value !== '') {
                    $filtered_data = [];
                    
                    // Convertir valor de filtro a tipo apropiado
                    $typed_filter_value = $filter_value;
                    if ($filter_value === 'true') {
                        $typed_filter_value = true;
                    } elseif ($filter_value === 'false') {
                        $typed_filter_value = false;
                    } elseif (is_numeric($filter_value)) {
                        $typed_filter_value = strpos($filter_value, '.') !== false ? 
                            (float)$filter_value : (int)$filter_value;
                    }
                    
                    foreach ($items_data as $item) {
                        if (is_array($item) && isset($item[$filter_field])) {
                            // Comparación estricta para booleanos, laxa para otros tipos
                            $match = false;
                            if (is_bool($typed_filter_value)) {
                                $match = $item[$filter_field] === $typed_filter_value;
                            } else {
                                $match = $item[$filter_field] == $typed_filter_value;
                            }
                            
                            if ($match) {
                                $filtered_data[] = $item;
                            }
                        }
                    }
                    
                    $items_data = $filtered_data;
                }
                
                // Obtener una muestra de los datos
                $sample_item = is_array($items_data) && !empty($items_data) ? $items_data[0] : $items_data;
                $fields = $this->extract_fields_from_data($sample_item);
                
                wp_send_json_success([
                    'message' => sprintf('Se encontraron %d elementos en la ruta "%s"', count($items_data), $items_path),
                    'count' => count($items_data),
                    'sample_data' => json_encode($sample_item, JSON_PRETTY_PRINT),
                    'fields' => array_keys($fields),
                    'has_filter' => !empty($filter_field) && $filter_value !== '',
                    'filter_count' => !empty($filter_field) ? count($items_data) : 0,
                    'params_applied' => $params_applied,
                    'url_used' => $url
                ]);
            } else {
                // Sin items_path, usar datos directos
                $count = is_array($raw_data) ? count($raw_data) : 1;
                $sample_item = is_array($raw_data) && isset($raw_data[0]) ? $raw_data[0] : $raw_data;
                $fields = $this->extract_fields_from_data($sample_item);
                
                wp_send_json_success([
                    'message' => 'Usando datos directos de la API (sin ruta de elementos)',
                    'count' => $count,
                    'sample_data' => json_encode($sample_item, JSON_PRETTY_PRINT),
                    'fields' => array_keys($fields)
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error al procesar los datos: ' . $e->getMessage()]);
        }
    }
    
    /**
     * UTILITY METHODS
     */
    
    public function count_dynamic_tags() {
        // Contar basándose ÚNICAMENTE en endpoints (sistema único)
        $endpoints = get_option('bricks_api_endpoints', []);
        $total_tags = 0;
        
        // Contar tags SOLO por endpoints
        foreach ($endpoints as $endpoint) {
            if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                $sample_data = $this->get_api_data_by_endpoint_name($endpoint['name']);
                if (!empty($sample_data)) {
                    $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
                    $total_tags += count($fields);
                } else {
                    // Si no hay datos, estimar tags básicos (id, title, name, description, url)
                    $total_tags += 5; // estimación conservadora para tags básicos
                }
            }
        }
        
        return $total_tags;
    }
    
    /**
     * Contar Query Types generados
     */
    public function count_query_types() {
        $endpoints = get_option('bricks_api_endpoints', []);
        $query_types_count = 0;
        
        foreach ($endpoints as $endpoint) {
            if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                $query_types_count++;
            }
        }
        
        return $query_types_count;
    }
    
    public function clear_all_cache() {
        global $wpdb;
        
        // Limpiar transients de API
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_api_data_%' OR option_name LIKE '_transient_timeout_api_data_%'");
        
        // Limpiar cache estático
        self::$api_cache = [];
    }
    
    /**
     * DEBUG SHORTCODE
     */
    public function debug_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p style="color: red;">🚫 Sin permisos de administrador</p>';
        }
        
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        $templates = get_option('bricks_api_templates', []);
        
        // Simular query types para ver qué se está generando
        $mock_control_options = ['queryTypes' => []];
        $control_options_with_query_types = $this->add_query_types_dynamic($mock_control_options);
        $generated_query_types = $control_options_with_query_types['queryTypes'] ?? [];
        
        // Filtrar solo nuestros query types
        $api_query_types = [];
        foreach ($generated_query_types as $key => $name) {
            if (strpos($key, 'api_') === 0) {
                $api_query_types[$key] = $name;
            }
        }
        
        ob_start();
        ?>
        <div style="background: #f0f8ff; padding: 20px; margin: 20px 0; border-left: 4px solid #007cba; font-family: monospace; font-size: 14px;">
            <h3 style="margin-top: 0;">🔍 Debug Bricks API Integrator v2.0 - Sistema Único Optimizado</h3>
            
            <div style="margin-bottom: 20px;">
                <h4>📊 Estadísticas Sistema Único</h4>
                <ul style="margin: 0;">
                    <li><strong>Endpoints configurados:</strong> <?php echo count($endpoints); ?></li>
                    <li><strong>Query Types configurados:</strong> <?php echo count($sources); ?> (para Query Loop)</li>
                    <li><strong>Templates configurados:</strong> <?php echo count($templates); ?></li>
                    <li><strong>Query Types generados:</strong> <?php echo count($api_query_types); ?></li>
                    <li><strong>Dynamic tags generados (único):</strong> <?php echo $this->count_dynamic_tags(); ?></li>
                    <li><strong>Prefijo usado:</strong> <code>snap_</code> (fijo para todos los tags)</li>
                    <li><strong>Fuente de tags:</strong> ✅ Solo Endpoints (no duplicación)</li>
                    <li><strong>Bricks Builder:</strong> <?php echo function_exists('bricks_is_builder') ? '✅ Activo' : '❌ No encontrado'; ?></li>
                </ul>
            </div>
            
            <?php if (!empty($api_query_types)): ?>
            <div style="margin-bottom: 20px;">
                <h4>🔧 Query Types Registrados</h4>
                <ul style="margin: 0;">
                    <?php foreach ($api_query_types as $key => $name): ?>
                        <li><code><?php echo esc_html($key); ?></code> → <?php echo esc_html($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($endpoints)): ?>
            <div style="margin-bottom: 20px;">
                <h4>🌐 Endpoints Configurados (generan dynamic tags automáticamente)</h4>
                <ul style="margin: 0;">
                    <?php foreach ($endpoints as $index => $endpoint): ?>
                        <li><strong><?php echo esc_html($endpoint['name'] ?? 'Sin nombre'); ?></strong> - <?php echo esc_html($endpoint['url'] ?? 'Sin URL'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <h4>✨ Sistema Único Activo</h4>
                <ul style="margin: 0;">
                    <li>✅ Dynamic tags se generan SOLO desde endpoints</li>
                    <li>✅ Prefijo fijo "snap_" aplicado a todos los tags</li>
                    <li>✅ ZERO duplicación de tags</li>
                    <li>✅ Un solo sistema funcionando (endpoints únicamente)</li>
                    <li>❌ Query Types NO generan dynamic tags (evita duplicación)</li>
                    <li>❌ Página "Tags Dinámicos" removida (innecesaria)</li>
                </ul>
            </div>
            
            <p style="margin-bottom: 0;"><small>💡 Los dynamic tags se generan automáticamente SOLO desde endpoints con prefijo "snap_" para evitar duplicación</small></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Analizar la estructura de los datos para ayudar en la depuración
     */
    private function analyze_data_structure($data) {
        $result = [];
        
        if (is_array($data)) {
            $result['type'] = 'array';
            $result['count'] = count($data);
            
            // Determinar si es un array asociativo o indexado
            $keys = array_keys($data);
            $is_associative = false;
            foreach ($keys as $key) {
                if (!is_numeric($key)) {
                    $is_associative = true;
                    break;
                }
            }
            
            $result['is_associative'] = $is_associative;
            
            // Analizar las claves principales
            $result['keys'] = array_slice($keys, 0, 10); // Mostrar hasta 10 claves
            if (count($keys) > 10) {
                $result['keys_truncated'] = true;
                $result['total_keys'] = count($keys);
            }
            
            // Sugerir posibles rutas de elementos
            $suggested_paths = [];
            
            // Verificar si hay una clave 'data', 'items', 'results', etc.
            $common_container_keys = ['data', 'items', 'results', 'content', 'list', 'records', 'vehicles'];
            foreach ($common_container_keys as $container_key) {
                if (isset($data[$container_key]) && is_array($data[$container_key])) {
                    $suggested_paths[] = $container_key;
                }
            }
            
            // Verificar si hay una clave 'status' y 'data'
            if (isset($data['status']) && isset($data['data']) && is_array($data['data'])) {
                $suggested_paths[] = 'data';
            }
            
            $result['suggested_paths'] = $suggested_paths;
            
            // Si es un array indexado, analizar el primer elemento
            if (!$is_associative && !empty($data)) {
                $first_item = reset($data);
                if (is_array($first_item) || is_object($first_item)) {
                    $result['first_item_keys'] = array_keys((array)$first_item);
                }
            }
        } elseif (is_object($data)) {
            $result['type'] = 'object';
            $data_array = (array)$data;
            $result['count'] = count($data_array);
            
            // Analizar las propiedades principales
            $result['properties'] = array_slice(array_keys($data_array), 0, 10);
            if (count($data_array) > 10) {
                $result['properties_truncated'] = true;
                $result['total_properties'] = count($data_array);
            }
            
            // Sugerir posibles rutas de elementos
            $suggested_paths = [];
            
            // Verificar si hay una propiedad 'data', 'items', 'results', etc.
            $common_container_props = ['data', 'items', 'results', 'content', 'list', 'records', 'vehicles'];
            foreach ($common_container_props as $container_prop) {
                if (isset($data->$container_prop) && (is_array($data->$container_prop) || is_object($data->$container_prop))) {
                    $suggested_paths[] = $container_prop;
                }
            }
            
            $result['suggested_paths'] = $suggested_paths;
        } else {
            $result['type'] = gettype($data);
            $result['value'] = (string)$data;
        }
        
        return $result;
    }
    
    /**
     * Obtener una muestra de los datos para ayudar en la depuración
     */
    private function get_sample_data($data) {
        if (is_array($data)) {
            $sample = [];
            
            // Si es un array asociativo, mostrar las claves principales
            $keys = array_keys($data);
            $is_associative = false;
            foreach ($keys as $key) {
                if (!is_numeric($key)) {
                    $is_associative = true;
                    break;
                }
            }
            
            if ($is_associative) {
                // Para arrays asociativos, mostrar las claves principales y sus tipos
                foreach ($keys as $key) {
                    if (count($sample) >= 5) break; // Limitar a 5 elementos
                    
                    $value = $data[$key];
                    if (is_array($value)) {
                        $sample[$key] = '[Array: ' . count($value) . ' elementos]';
                        
                        // Si es un array, mostrar las primeras claves
                        if (!empty($value)) {
                            $first_keys = array_slice(array_keys($value), 0, 3);
                            $sample[$key] .= ' Claves: ' . implode(', ', $first_keys);
                            
                            // Si el primer elemento es un array, mostrar su estructura
                            $first_value = reset($value);
                            if (is_array($first_value)) {
                                $sample[$key] .= ' | Primer elemento: [' . implode(', ', array_slice(array_keys($first_value), 0, 3)) . '...]';
                            }
                        }
                    } elseif (is_object($value)) {
                        $sample[$key] = '[Object: ' . count((array)$value) . ' propiedades]';
                    } else {
                        $sample[$key] = $value;
                    }
                }
            } else {
                // Para arrays indexados, mostrar los primeros elementos
                $sample = array_slice($data, 0, 2);
                if (count($data) > 2) {
                    $sample[] = '... (' . (count($data) - 2) . ' elementos más)';
                }
            }
            
            return $sample;
        } elseif (is_object($data)) {
            $sample = [];
            $data_array = (array)$data;
            
            // Mostrar las propiedades principales
            $properties = array_keys($data_array);
            foreach ($properties as $prop) {
                if (count($sample) >= 5) break; // Limitar a 5 propiedades
                
                $value = $data_array[$prop];
                if (is_array($value)) {
                    $sample[$prop] = '[Array: ' . count($value) . ' elementos]';
                } elseif (is_object($value)) {
                    $sample[$prop] = '[Object: ' . count((array)$value) . ' propiedades]';
                } else {
                    $sample[$prop] = $value;
                }
            }
            
            return $sample;
        }
        
        return $data;
    }

    /**
     * Guardar un solo endpoint vía AJAX
     */
    public function ajax_save_single_api_endpoint() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX SAVE ENDPOINT - POST: ' . print_r($_POST, true));
        }
        if (!current_user_can('manage_options')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX SAVE ENDPOINT - Error: No tienes permisos suficientes.');
            }
            wp_send_json_error(['message' => 'No tienes permisos suficientes.']);
        }
        check_ajax_referer('save_single_api_endpoint', 'nonce');

        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        if ($index < 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX SAVE ENDPOINT - Error: Índice de endpoint inválido.');
            }
            wp_send_json_error(['message' => 'Índice de endpoint inválido.']);
        }

        $endpoints = get_option('bricks_api_endpoints', []);
        if (!isset($endpoints[$index])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AJAX SAVE ENDPOINT - Error: Endpoint no encontrado. Index: ' . $index . ' Endpoints: ' . print_r($endpoints, true));
            }
            wp_send_json_error(['message' => 'Endpoint no encontrado.']);
        }

        // Construir el array del endpoint con los datos recibidos
        $dynamic_params = [];
        $param_names = isset($_POST['param_names']) ? (array) $_POST['param_names'] : [];
        $param_sources = isset($_POST['param_sources']) ? (array) $_POST['param_sources'] : [];
        $param_defaults = isset($_POST['param_defaults']) ? (array) $_POST['param_defaults'] : [];
        foreach ($param_names as $i => $name) {
            if (!empty($name)) {
                $dynamic_params[] = [
                    'name' => sanitize_text_field($name),
                    'source' => sanitize_text_field($param_sources[$i] ?? 'url'),
                    'default' => sanitize_text_field($param_defaults[$i] ?? '')
                ];
            }
        }

        $endpoints[$index] = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'url' => esc_url_raw($_POST['url'] ?? ''),
            'auth_type' => sanitize_text_field($_POST['auth_type'] ?? 'none'),
            'token' => sanitize_text_field($_POST['token'] ?? ''),
            'basic_user' => sanitize_text_field($_POST['basic_user'] ?? ''),
            'basic_password' => trim($_POST['basic_password'] ?? ''),
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'api_key_header' => sanitize_text_field($_POST['api_key_header'] ?? 'X-API-Key'),
            'dynamic_params' => $dynamic_params,
            'items_path' => sanitize_text_field($_POST['items_path'] ?? ''),
        ];

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AJAX SAVE ENDPOINT - Endpoint actualizado: ' . print_r($endpoints[$index], true));
        }

        update_option('bricks_api_endpoints', $endpoints);
        wp_send_json_success(['message' => 'Endpoint guardado correctamente.']);
    }
}

// Compatibilidad con archivos existentes
if (!function_exists('get_api_data')) {
    function get_api_data($endpoint_url, $endpoint_config = []) {
        static $integrator_instance = null;
        if ($integrator_instance === null) {
            $integrator_instance = new BricksAPIIntegrator();
        }
        return $integrator_instance->get_api_data_with_cache($endpoint_url, $endpoint_config);
    }
}