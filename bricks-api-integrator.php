<?php
/*
    * Plugin Name: Bricks API Integrator
    * Description: Integra el constructor de páginas Bricks con APIs externas de forma dinámica.
    * Version: 2.0
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
define('BRICKS_API_INTEGRATOR_VERSION', '2.0');
define('BRICKS_API_INTEGRATOR_PATH', plugin_dir_path(__FILE__));
define('BRICKS_API_INTEGRATOR_URL', plugin_dir_url(__FILE__));

// Incluir archivos necesarios ANTES de definir la clase
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/api-manager.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/field-extractor.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/functions.php';

// Archivos existentes (solo si existen)
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/endpoints.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/endpoints.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/templates.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/templates.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/debug-source.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/debug-source.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'includes/direct-source.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'includes/direct-source.php';
}
if (file_exists(BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php')) {
    require_once BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php';
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
        add_action('wp_ajax_clear_api_cache', [$this, 'ajax_clear_api_cache']);
        add_action('wp_ajax_regenerate_bricks_integration', [$this, 'ajax_regenerate_bricks_integration']);
        add_action('wp_ajax_reset_plugin_data', [$this, 'ajax_reset_plugin_data']);
        
        // Shortcode para debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_shortcode('debug_api_integrator', [$this, 'debug_shortcode']);
        }
        
        // Hook para limpiar cache cuando se actualicen las opciones
        add_action('update_option_bricks_api_endpoints', [$this, 'clear_all_cache']);
        add_action('update_option_bricks_api_sources', [$this, 'clear_all_cache']);
    }
    
    /**
     * NUEVA FUNCIONALIDAD DINÁMICA - Query Types
     */
    public function add_query_types_dynamic($control_options) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        
        if (!isset($control_options['queryTypes'])) {
            $control_options['queryTypes'] = [];
        }
        
        // Limpiar query types existentes de nuestro plugin primero
        foreach ($control_options['queryTypes'] as $key => $name) {
            if (strpos($key, 'api_') === 0 || strpos($key, 'source_') === 0) {
                unset($control_options['queryTypes'][$key]);
            }
        }
        
        // Registrar query types basándose en endpoints configurados
        foreach ($endpoints as $endpoint_id => $endpoint) {
            if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                $query_type_key = 'api_' . sanitize_key($endpoint['name']);
                $control_options['queryTypes'][$query_type_key] = $endpoint['name'];
            }
        }
        
        // También registrar basándose en sources si están configurados
        foreach ($sources as $source_id => $source) {
            if (!empty($source['name'])) {
                $query_type_key = 'source_' . sanitize_key($source_id);
                $control_options['queryTypes'][$query_type_key] = $source['name'];
            }
        }
        
        return $control_options;
    }
    
    /**
     * NUEVA FUNCIONALIDAD DINÁMICA - Ejecutar Query
     */
    public function run_custom_query_dynamic($results, $query_object) {
        $object_type = $query_object->object_type ?? '';
        
        // Verificar si es uno de nuestros query types
        if (strpos($object_type, 'api_') !== 0 && strpos($object_type, 'source_') !== 0) {
            return $results;
        }
        
        try {
            if (strpos($object_type, 'api_') === 0) {
                // Query basado en endpoint
                $endpoint_name = str_replace('api_', '', $object_type);
                $endpoint_name = str_replace('_', ' ', $endpoint_name);
                $api_data = $this->get_api_data_by_endpoint_name($endpoint_name);
            } else {
                // Query basado en source
                $source_id = str_replace('source_', '', $object_type);
                $api_data = $this->get_api_data_by_source_id($source_id);
            }
            
            return !empty($api_data) ? $api_data : $results;
        } catch (Exception $e) {
            return $results;
        }
    }
    
    /**
     * NUEVA FUNCIONALIDAD DINÁMICA - Dynamic Tags
     */
    public function add_dynamic_tags_dynamic($tags) {
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        
        // Si no hay endpoints ni sources, no generar tags
        if (empty($endpoints) && empty($sources)) {
            return $tags;
        }
        
        // Generar tags dinámicamente para cada endpoint
        foreach ($endpoints as $endpoint_id => $endpoint) {
            if (empty($endpoint['name']) || empty($endpoint['url'])) {
                continue;
            }
            
            // Obtener datos de muestra
            $sample_data = $this->get_api_data_by_endpoint_name($endpoint['name']);
            
            if (empty($sample_data)) {
                // NO generar tags básicos si no hay datos - esto evita los tags fantasma
                continue;
            }
            
            // Extraer campos dinámicamente solo si hay datos
            $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
            
            // Crear tags solo si hay campos reales
            if (!empty($fields)) {
                $endpoint_slug = sanitize_key($endpoint['name']);
                foreach ($fields as $field => $label) {
                    $tags[] = [
                        'name' => '{api_' . $endpoint_slug . '_' . $field . '}',
                        'label' => $label,
                        'group' => $endpoint['name']
                    ];
                }
            }
        }
        
        // Generar tags para sources configurados
        foreach ($sources as $source_id => $source) {
            if (empty($source['name'])) {
                continue;
            }
            
            $sample_data = $this->get_api_data_by_source_id($source_id);
            
            if (empty($sample_data)) {
                continue;
            }
            
            $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
            
            if (!empty($fields)) {
                foreach ($fields as $field => $label) {
                    $tags[] = [
                        'name' => '{source_' . $source_id . '_' . $field . '}',
                        'label' => $label,
                        'group' => $source['name']
                    ];
                }
            }
        }
        
        return $tags;
    }
    
    /**
     * NUEVA FUNCIONALIDAD DINÁMICA - Renderizar Tags
     */
    public function render_dynamic_tags_dynamic($content, $post, $context) {
        if (strpos($content, '{api_') === false && strpos($content, '{source_') === false) {
            return $content;
        }
        
        $loop_object = \Bricks\Query::get_loop_object();
        
        if (empty($loop_object)) {
            return $content;
        }
        
        // Procesar tags de API
        $content = preg_replace_callback(
            '/\{(api_|source_)([a-zA-Z0-9_]+)_([a-zA-Z0-9_]+)\}/',
            function($matches) use ($loop_object) {
                $type = $matches[1]; // 'api_' o 'source_'
                $identifier = $matches[2]; // nombre del endpoint o source
                $field = $matches[3]; // campo
                
                $value = $this->get_field_value_from_object($loop_object, $field);
                return $value !== '' ? $value : $matches[0];
            },
            $content
        );
        
        return $content;
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
        $data = $this->get_api_data_with_cache($endpoint['url'], $endpoint, true);
        
        if (empty($data)) {
            wp_send_json_error(['message' => 'No se obtuvieron datos de la API']);
        }
        
        $sample_fields = [];
        if (!empty($data[0])) {
            $fields = $this->extract_fields_from_data($data[0]);
            $sample_fields = array_slice(array_keys($fields), 0, 5);
        }
        
        wp_send_json_success([
            'count' => count($data),
            'sample_fields' => $sample_fields,
            'message' => 'API funcionando correctamente'
        ]);
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
            
            wp_send_json_success([
                'message' => 'Todos los datos del plugin han sido eliminados y reiniciados correctamente.'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error durante el reset: ' . $e->getMessage()]);
        }
    }
    
    /**
     * UTILITY METHODS
     */
    
    public function count_dynamic_tags() {
        // Contar basándose en nuestros datos reales, no en el filtro de Bricks
        $endpoints = get_option('bricks_api_endpoints', []);
        $sources = get_option('bricks_api_sources', []);
        
        $total_tags = 0;
        
        // Contar tags por endpoints
        foreach ($endpoints as $endpoint) {
            if (!empty($endpoint['name']) && !empty($endpoint['url'])) {
                $sample_data = $this->get_api_data_by_endpoint_name($endpoint['name']);
                if (!empty($sample_data)) {
                    $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
                    $total_tags += count($fields);
                } else {
                    // Si no hay datos, contar tags básicos
                    $total_tags += 9; // tags básicos
                }
            }
        }
        
        // Contar tags por sources
        foreach ($sources as $source_id => $source) {
            if (!empty($source['name'])) {
                $sample_data = $this->get_api_data_by_source_id($source_id);
                if (!empty($sample_data)) {
                    $fields = $this->extract_fields_from_data($sample_data[0] ?? $sample_data);
                    $total_tags += count($fields);
                }
            }
        }
        
        return $total_tags;
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
        
        ob_start();
        ?>
        <div style="background: #f0f8ff; padding: 20px; margin: 20px 0; border-left: 4px solid #007cba; font-family: monospace; font-size: 14px;">
            <h3 style="margin-top: 0;">🔍 Debug Bricks API Integrator v2.0</h3>
            
            <div style="margin-bottom: 20px;">
                <h4>📊 Estadísticas</h4>
                <ul style="margin: 0;">
                    <li><strong>Endpoints configurados:</strong> <?php echo count($endpoints); ?></li>
                    <li><strong>Dynamic tags generados:</strong> <?php echo $this->count_dynamic_tags(); ?></li>
                    <li><strong>Bricks Builder:</strong> <?php echo function_exists('bricks_is_builder') ? '✅ Activo' : '❌ No encontrado'; ?></li>
                </ul>
            </div>
            
            <p style="margin-bottom: 0;"><small>💡 Ve al panel de administración para configuración completa</small></p>
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
