<?php
/*
    * Plugin Name: Bricks API Integrator
    * Description: Integra el constructor de páginas Bricks con APIs externas de forma dinámica.
    * Version: 2.1.1
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
define('BRICKS_API_INTEGRATOR_VERSION', '2.1.1');
define('BRICKS_API_INTEGRATOR_PATH', plugin_dir_path(__FILE__));
define('BRICKS_API_INTEGRATOR_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios ANTES de definir la clase
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/api-manager.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/field-extractor.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/functions.php';

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
/*
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php';
}
*/

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
    use APIManager, FieldExtractor;
    
    /**
     * Cache estático para respuestas API
     */
    private static $api_cache = [];
    
    public function __construct() {
        $this->init_hooks();
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Bricks API Integrator - Sources de debug eliminados automáticamente');
            }
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
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        
        if (!isset($control_options['queryTypes'])) {
            $control_options['queryTypes'] = [];
        }
        
        // DEBUG: Log del estado inicial
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Bricks API Integrator - Query Types iniciales: ' . count($control_options['queryTypes']));
        }
        
        // Limpiar query types existentes de nuestro plugin primero para evitar duplicación
        $removed_count = 0;
        foreach ($control_options['queryTypes'] as $key => $name) {
            if (strpos($key, 'api_') === 0 || 
                strpos($key, 'source_') === 0 || 
                strpos($key, 'listado_') === 0 || 
                strpos($key, 'launcher_') === 0 ||
                strpos($key, 'bai_api_source') === 0 ||
                strpos($name, 'API:') === 0 ||
                strpos($name, '(Auto)') !== false || 
                strpos($name, '(Manual)') !== false) {
                unset($control_options['queryTypes'][$key]);
                $removed_count++;
            }
        }
        
        // DEBUG: Log de limpieza
        if (defined('WP_DEBUG') && WP_DEBUG && $removed_count > 0) {
            error_log("Bricks API Integrator - Eliminados {$removed_count} query types duplicados");
        }
        
        $registered_keys = [];
        $added_count = 0;
        
        // 1. Query Types AUTOMÁTICOS desde ENDPOINTS
        foreach ($endpoints as $endpoint_id => $endpoint) {
            if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                $query_type_key = 'api_' . sanitize_key($endpoint['name']);
                
                // Evitar duplicación
                if (!isset($registered_keys[$query_type_key])) {
                    $control_options['queryTypes'][$query_type_key] = $endpoint['name'] . ' (Auto)';
                    $registered_keys[$query_type_key] = true;
                    $added_count++;
                }
            }
        }
        
        // 2. Query Types MANUALES desde SOURCES
        foreach ($sources as $source_id => $source) {
            if (!empty($source['name'])) {
                $query_type_key = 'source_' . sanitize_key($source['name']);
                
                // Evitar duplicación
                if (!isset($registered_keys[$query_type_key])) {
                    $control_options['queryTypes'][$query_type_key] = $source['name'] . ' (Manual)';
                    $registered_keys[$query_type_key] = true;
                    $added_count++;
                }
            }
        }
        
        // DEBUG: Log final
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Bricks API Integrator - Agregados {$added_count} query types. Total final: " . count($control_options['queryTypes']));
        }
        
        return $control_options;
    }
    
    /**
     * Ejecutar Query con diferenciación AUTO/MANUAL
     */
    public function run_custom_query_dynamic($results, $query_object) {
        $object_type = $query_object->object_type ?? '';
        
        // DEBUG: Log para debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Bricks API Integrator - Query ejecutado: ' . $object_type);
        }
        
        // Verificar si es uno de nuestros query types
        if (strpos($object_type, 'api_') !== 0 && strpos($object_type, 'source_') !== 0) {
            return $results;
        }
        
        $api_data = [];
        
        try {
            // Query Type AUTOMÁTICO desde endpoint
            if (strpos($object_type, 'api_') === 0) {
                $endpoint_key = str_replace('api_', '', $object_type);
                $endpoints = get_option('bricks_api_endpoints', []);
                
                foreach ($endpoints as $endpoint) {
                    if (!empty($endpoint['name'])) {
                        $sanitized_name = sanitize_key($endpoint['name']);
                        if ($sanitized_name === $endpoint_key) {
                            $api_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('Bricks API Integrator - Datos AUTO encontrados: ' . count($api_data) . ' elementos');
                            }
                            break;
                        }
                    }
                }
            }
            
            // Query Type MANUAL desde source
            if (strpos($object_type, 'source_') === 0) {
                $source_key = str_replace('source_', '', $object_type);
                $sources = get_option('bricks_api_sources', []);
                $endpoints = get_option('bricks_api_endpoints', []);
                
                foreach ($sources as $source_id => $source) {
                    if (!empty($source['name'])) {
                        $sanitized_name = sanitize_key($source['name']);
                        if ($sanitized_name === $source_key) {
                            $endpoint_id = $source['endpoint_id'] ?? '';
                            if (isset($endpoints[$endpoint_id])) {
                                $endpoint = $endpoints[$endpoint_id];
                                $raw_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
                                
                                // Aplicar items_path si está configurado
                                if (!empty($source['items_path']) && !empty($raw_data)) {
                                    $api_data = $this->extract_nested_items($raw_data, $source['items_path']);
                                } else {
                                    $api_data = $raw_data;
                                }
                                
                                if (defined('WP_DEBUG') && WP_DEBUG) {
                                    error_log('Bricks API Integrator - Datos MANUAL encontrados: ' . count($api_data) . ' elementos');
                                }
                            }
                            break;
                        }
                    }
                }
            }
            
            // Si encontramos datos, convertirlos al formato que espera Bricks
            if (!empty($api_data)) {
                return $this->convert_api_data_for_bricks($api_data);
            }
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Bricks API Integrator - Error en query: ' . $e->getMessage());
            }
            return $results;
        }
        
        return $results;
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
            $pseudo_post->ID = $index + 1;
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Bricks API Integrator - Datos convertidos: ' . count($converted_data) . ' elementos');
        }
        
        return $converted_data;
    }
    
    /**
     * Dynamic Tags diferenciados para AUTO/MANUAL
     */
    public function add_dynamic_tags_dynamic($tags) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        
        // DEBUG: Log del proceso
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Generando Dynamic Tags diferenciados AUTO/MANUAL');
        }
        
        // Prefijo fijo para todos los tags
        $fixed_prefix = 'snap_';
        $tags_generated = 0;
        
        // 1. Tags AUTOMÁTICOS desde ENDPOINTS
        foreach ($endpoints as $endpoint_id => $endpoint) {
            if (empty($endpoint['name']) || empty($endpoint['url'])) {
                continue;
            }
            
            // Obtener datos de muestra del endpoint
            $sample_data = $this->get_api_data_with_dynamic_params($endpoint);
            $endpoint_slug = sanitize_key($endpoint['name']);
            
            if (empty($sample_data)) {
                // Si no hay datos, crear tags básicos por defecto
                $basic_fields = ['id', 'title', 'name', 'description', 'url', 'slug', 'content', 'image'];
                
                foreach ($basic_fields as $field) {
                    $tags[] = [
                        'name' => '{' . $fixed_prefix . 'auto_' . $endpoint_slug . '_' . $field . '}',
                        'label' => ucfirst($field) . ' (Auto)',
                        'group' => $endpoint['name'] . ' (Auto)'
                    ];
                    $tags_generated++;
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Tags AUTO básicos generados para endpoint "' . $endpoint['name'] . '": ' . count($basic_fields));
                }
                continue;
            }
            
            // Extraer campos dinámicamente
            $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
            
            if (!empty($fields)) {
                foreach ($fields as $field => $label) {
                    $tags[] = [
                        'name' => '{' . $fixed_prefix . 'auto_' . $endpoint_slug . '_' . $field . '}',
                        'label' => $label,
                        'group' => $endpoint['name'] . ' (Auto)'
                    ];
                    $tags_generated++;
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Tags AUTO generados para endpoint "' . $endpoint['name'] . '": ' . count($fields));
                }
            }
        }
        
        // 2. Tags MANUALES desde SOURCES (para ítems anidados)
        foreach ($sources as $source_id => $source) {
            if (empty($source['name'])) {
                continue;
            }
            
            $source_slug = sanitize_key($source['name']);
            
            // Obtener datos de muestra para el source
            $endpoint_id = $source['endpoint_id'] ?? '';
            if (isset($endpoints[$endpoint_id])) {
                $endpoint = $endpoints[$endpoint_id];
                $raw_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
                
                // Aplicar items_path si está configurado
                if (!empty($source['items_path']) && !empty($raw_data)) {
                    $sample_data = $this->extract_nested_items($raw_data, $source['items_path']);
                } else {
                    $sample_data = $raw_data;
                }
                
                if (!empty($sample_data)) {
                    $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
                    
                    foreach ($fields as $field => $label) {
                        $tags[] = [
                            'name' => '{' . $fixed_prefix . $source_slug . '_' . $field . '}',
                            'label' => $label,
                            'group' => $source['name'] . ' (Manual)'
                        ];
                        $tags_generated++;
                    }
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Tags MANUAL generados para source "' . $source['name'] . '": ' . count($fields));
                    }
                } else {
                    // Tags básicos si no hay datos
                    $basic_fields = ['id', 'title', 'name', 'description'];
                    
                    foreach ($basic_fields as $field) {
                        $tags[] = [
                            'name' => '{' . $fixed_prefix . $source_slug . '_' . $field . '}',
                            'label' => ucfirst($field),
                            'group' => $source['name'] . ' (Manual)'
                        ];
                        $tags_generated++;
                    }
                }
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Dynamic Tags generados: {$tags_generated} (AUTO + MANUAL)");
        }
        
        return $tags;
    }
    
    /**
     * Obtener datos de API con parámetros dinámicos para páginas de detalle
     */
    private function get_api_data_with_dynamic_params($endpoint) {
        // Primero intentar obtener datos básicos del endpoint
        $basic_data = $this->get_api_data_by_endpoint_name($endpoint['name']);
        
        // Si tenemos datos básicos, usarlos
        if (!empty($basic_data)) {
            return $basic_data;
        }
        
        // Si no hay datos básicos, intentar con parámetros de muestra para páginas de detalle
        if (!empty($endpoint['dynamic_params'])) {
            $test_url = $this->build_test_url_with_params($endpoint);
            
            if (!empty($test_url)) {
                try {
                    $test_data = $this->get_api_data_with_cache($test_url, $endpoint, true);
                    if (!empty($test_data)) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Datos obtenidos con parámetros de test para: ' . $endpoint['name']);
                        }
                        return $test_data;
                    }
                } catch (Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Error al obtener datos con parámetros de test: ' . $e->getMessage());
                    }
                }
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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Construyendo URL de test para: ' . $endpoint['name']);
            error_log('URL base: ' . $url);
            error_log('Parámetros dinámicos: ' . json_encode($dynamic_params));
        }
        
        if (empty($dynamic_params)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('No hay parámetros dinámicos, devolviendo URL base');
            }
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
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Agregado parámetro {$param_name} = {$param_default} (por defecto)");
                }
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
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Agregado parámetro {$param_name} = {$sample_value} (valor de muestra)");
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("URL final de test ({$params_added} parámetros agregados): {$url}");
        }
        
        return $url;
    }
    
    /**
     * Renderizar Dynamic Tags diferenciados AUTO/MANUAL
     */
    public function render_dynamic_tags_dynamic($content, $post, $context) {
        if (strpos($content, '{snap_') === false) {
            return $content;
        }
        
        // Procesar tags con diferentes patrones para AUTO/MANUAL
        $content = preg_replace_callback(
            '/\{snap_(auto_)?([a-zA-Z0-9_]+)_([a-zA-Z0-9_]+)\}/',
            function($matches) use ($post, $context) {
                $is_auto = !empty($matches[1]); // Detectar si es tag automático
                $identifier = $matches[2]; // nombre del endpoint/source
                $field = $matches[3]; // campo
                
                // Primero verificar si estamos en un loop de Bricks
                $loop_object = \Bricks\Query::get_loop_object();
                
                if (!empty($loop_object) && isset($loop_object->api_data)) {
                    $api_data = $loop_object->api_data;
                    
                    if (is_array($api_data) || is_object($api_data)) {
                        $data_array = (array) $api_data;
                        
                        // Manejar campos especiales de arrays
                        if (strpos($field, '_item_') !== false) {
                            return $this->handle_array_item_field($data_array, $field, $context);
                        } elseif (strpos($field, '_last_') !== false) {
                            return $this->handle_array_last_field($data_array, $field);
                        }
                        
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
                    }
                }
                
                // Si no estamos en un loop, obtener datos dinámicamente
                if ($is_auto) {
                    // Tag automático: obtener desde endpoint
                    $value = $this->get_dynamic_field_value($identifier, $field, $post, $context);
                } else {
                    // Tag manual: obtener desde source
                    $value = $this->get_source_field_value($identifier, $field, $post, $context);
                }
                
                return $value !== '' ? $value : $matches[0];
            },
            $content
        );
        
        return $content;
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
        
        // Buscar el endpoint por identificador
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
        
        // Construir URL con parámetros dinámicos actuales
        $dynamic_url = $this->build_dynamic_url($target_endpoint, $post, $context);
        
        // Obtener datos del endpoint con los parámetros actuales
        try {
            $api_data = $this->get_api_data_with_cache($dynamic_url, $target_endpoint);
            
            if (empty($api_data)) {
                return '';
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
            // Usar el método mejorado que maneja parámetros dinámicos
            $data = $this->get_api_data_with_dynamic_params($endpoint);
            
            if (empty($data)) {
                // Si no hay datos con parámetros dinámicos, intentar con URL base
                $data = $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
                
                if (empty($data)) {
                    // Construir URL de test para mostrar en el error
                    $test_url = $this->build_test_url_with_params($endpoint);
                    wp_send_json_error([
                        'message' => 'No se obtuvieron datos de la API. Verifica la URL y autenticación.',
                        'test_url' => $test_url,
                        'base_url' => $endpoint['url']
                    ]);
                }
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
            
            // Construir URL de test para mostrar
            $test_url = $this->build_test_url_with_params($endpoint);
            
            wp_send_json_success([
                'count' => $count,
                'sample_fields' => $sample_fields,
                'sample_data' => $sample_data,
                'message' => 'API funcionando correctamente',
                'test_url' => $test_url,
                'base_url' => $endpoint['url']
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
            // Limpiar caché específico de este endpoint primero
            $cache_key = 'api_data_' . md5($endpoint['url'] . serialize($endpoint));
            delete_transient($cache_key);
            
            // Forzar actualización de datos
            $data = $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
            
            if (empty($data)) {
                wp_send_json_error([
                    'message' => 'No se obtuvieron datos de la API. Verifica la URL y configuración.',
                    'endpoint_url' => $endpoint['url']
                ]);
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
