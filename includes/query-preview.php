<?php
/**
 * Query Preview Enhancement
 * 
 * Añade funcionalidad de preview para Query Types
 * Archivo: includes/query-preview.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para preview de Query Types
 */
trait QueryPreview {
    
    /**
     * Añadir hook para preview de queries
     */
    public function init_query_preview_hooks() {
        // AJAX para preview de query
        add_action('wp_ajax_preview_query_type', [$this, 'ajax_preview_query_type']);
        add_action('wp_ajax_nopriv_preview_query_type', [$this, 'ajax_preview_query_type']);
        
        // Enqueue scripts en admin y frontend (para Bricks Builder)
        add_action('admin_enqueue_scripts', [$this, 'enqueue_preview_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_preview_scripts']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Query Preview: Hooks inicializados');
        }
    }
    
    /**
     * Añadir opciones de preview a los controles de Query
     */
    public function add_preview_options_to_query_controls($control_options) {
        // Verificar que estamos en Bricks
        if (!function_exists('bricks_is_builder')) {
            return $control_options;
        }
        
        // Añadir información adicional a nuestros query types
        if (isset($control_options['queryTypes'])) {
            $endpoints = get_option('bricks_api_endpoints', []);
            $sources = get_option('bricks_api_sources', []);
            
            foreach ($control_options['queryTypes'] as $key => $name) {
                // Solo para nuestros query types
                if (strpos($key, 'api_') === 0 || strpos($key, 'source_') === 0) {
                    // Añadir metadata para el preview
                    $control_options['queryTypesPreview'][$key] = [
                        'hasPreview' => true,
                        'previewAction' => 'preview_query_type',
                        'nonce' => wp_create_nonce('preview_query_' . $key)
                    ];
                }
            }
        }
        
        return $control_options;
    }
    
    /**
     * AJAX handler para preview de query type
     */
    public function ajax_preview_query_type() {
        $query_type = sanitize_text_field($_POST['query_type'] ?? '');
        $limit = intval($_POST['limit'] ?? 1);
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'preview_query_' . $query_type)) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
        
        try {
            $preview_data = $this->get_query_preview_data($query_type, $limit);
            
            if (empty($preview_data)) {
                wp_send_json_error(['message' => 'No se encontraron datos para este query type']);
            }
            
            wp_send_json_success([
                'query_type' => $query_type,
                'total_found' => count($preview_data['full_data'] ?? []),
                'preview_data' => $preview_data['preview'],
                'fields' => $preview_data['fields'],
                'sample_tags' => $preview_data['sample_tags']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Obtener datos de preview para un query type específico
     */
    public function get_query_preview_data($query_type, $limit = 1) {
        if (strpos($query_type, 'api_') !== 0 && strpos($query_type, 'source_') !== 0) {
            throw new Exception('Query type no válido');
        }
        
        $api_data = [];
        $endpoint_name = '';
        
        // Query Type AUTOMÁTICO desde endpoint
        if (strpos($query_type, 'api_') === 0) {
            $endpoint_key = str_replace('api_', '', $query_type);
            $endpoints = get_option('bricks_api_endpoints', []);
            
            foreach ($endpoints as $endpoint) {
                if (!empty($endpoint['name'])) {
                    $sanitized_name = sanitize_key($endpoint['name']);
                    if ($sanitized_name === $endpoint_key) {
                        $api_data = $this->get_api_data_with_cache($endpoint['url'], $endpoint);
                        $endpoint_name = $endpoint['name'];
                        break;
                    }
                }
            }
        }
        
        // Query Type MANUAL desde source
        if (strpos($query_type, 'source_') === 0) {
            $source_key = str_replace('source_', '', $query_type);
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
                            $endpoint_name = $source['name'];
                        }
                        break;
                    }
                }
            }
        }
        
        if (empty($api_data)) {
            throw new Exception('No se encontraron datos en el endpoint');
        }
        
        // Extraer primer item para preview
        $first_item = is_array($api_data) ? ($api_data[0] ?? $api_data) : $api_data;
        
        // Extraer campos disponibles
        $fields = $this->extract_fields_from_data($first_item);
        
        // Generar sample tags
        $sample_tags = $this->generate_sample_tags_for_preview($query_type, $fields);
        
        return [
            'full_data' => $api_data,
            'preview' => array_slice($api_data, 0, $limit),
            'fields' => $fields,
            'sample_tags' => $sample_tags,
            'endpoint_name' => $endpoint_name
        ];
    }
    
    /**
     * Generar sample tags para preview
     */
    private function generate_sample_tags_for_preview($query_type, $fields) {
        $sample_tags = [];
        $base_prefix = strpos($query_type, 'api_') === 0 ? 'snap_auto_' : 'snap_';
        $identifier = str_replace(['api_', 'source_'], '', $query_type);
        
        foreach ($fields as $field => $label) {
            $sample_tags[] = '{' . $base_prefix . $identifier . '_' . $field . '}';
        }
        
        return $sample_tags;
    }
    
    /**
     * Enqueue scripts para preview
     */
    public function enqueue_preview_scripts($hook = '') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Query Preview: Cargando scripts en: ' . $hook);
        }
        
        // Script para admin (configuración de endpoints)
        if (is_admin()) {
            wp_enqueue_script(
                'bricks-api-query-preview',
                BRICKS_API_INTEGRATOR_URL . 'assets/query-preview.js',
                ['jquery'],
                BRICKS_API_INTEGRATOR_VERSION,
                true
            );
            
            wp_enqueue_style(
                'bricks-api-query-preview',
                BRICKS_API_INTEGRATOR_URL . 'assets/query-preview.css',
                [],
                BRICKS_API_INTEGRATOR_VERSION
            );
        }
        
        // Script específico para Bricks Builder (frontend)
        wp_enqueue_script(
            'bricks-query-type-preview',
            BRICKS_API_INTEGRATOR_URL . 'assets/bricks-query-preview.js',
            ['jquery'],
            BRICKS_API_INTEGRATOR_VERSION,
            true
        );
        
        // Localizar variables para ambos scripts
        wp_localize_script('bricks-api-query-preview', 'bricksApiPreview', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bricks_api_preview')
        ]);
        
        wp_localize_script('bricks-query-type-preview', 'bricksApiPreview', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bricks_api_preview')
        ]);
    }
    
    /**
     * Formatear valor para preview
     */
    private function format_preview_value($value) {
        if (is_array($value)) {
            return '(' . count($value) . ' elementos) ' . implode(', ', array_slice($value, 0, 3)) . (count($value) > 3 ? '...' : '');
        }
        
        if (is_object($value)) {
            return '(Objeto con ' . count((array)$value) . ' propiedades)';
        }
        
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        
        $str_value = (string) $value;
        return strlen($str_value) > 100 ? substr($str_value, 0, 97) . '...' : $str_value;
    }
}
