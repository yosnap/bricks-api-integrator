<?php
/**
 * Dynamic Tags - Compatibilidad con versión anterior
 * 
 * @package BricksAPIIntegrator
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mantener compatibilidad con filtros existentes
 */
add_filter('bricks/query/dynamic_tags', 'bricks_api_register_dynamic_tags_for_query_loop');

/**
 * Register dynamic tags for Bricks Query Loop (compatibilidad)
 */
function bricks_api_register_dynamic_tags_for_query_loop($tags) {
    // Get API sources and endpoints
    $api_sources = get_option('bricks_api_sources', []);
    $endpoints = get_option('bricks_api_endpoints', []);
    
    if (empty($api_sources)) {
        // If no sources configured, add default fields
        $tags['api_source'] = [
            'name'  => 'api_source',
            'label' => esc_html__('API Source', 'bricks-api-integrator'),
            'fields' => [
                'id' => esc_html__('ID', 'bricks-api-integrator'),
                'title' => esc_html__('Title', 'bricks-api-integrator'),
                'name' => esc_html__('Name', 'bricks-api-integrator'),
                'description' => esc_html__('Description', 'bricks-api-integrator'),
                'content' => esc_html__('Content', 'bricks-api-integrator'),
                'image' => esc_html__('Image', 'bricks-api-integrator'),
                'url' => esc_html__('URL', 'bricks-api-integrator'),
                'api_url' => esc_html__('API URL', 'bricks-api-integrator'),
                'api_id' => esc_html__('API ID', 'bricks-api-integrator'),
            ],
        ];
        
        return $tags;
    }
    
    // Add common fields that should always be available
    $common_fields = [
        'api_url' => esc_html__('API URL', 'bricks-api-integrator'),
        'api_id' => esc_html__('API ID', 'bricks-api-integrator'),
    ];
    
    // Process each source to get its fields
    foreach ($api_sources as $source_id => $source) {
        // Skip if no endpoint configured
        $endpoint_id = isset($source['endpoint_id']) ? $source['endpoint_id'] : '';
        if (empty($endpoint_id) || !isset($endpoints[$endpoint_id])) {
            continue;
        }
        
        $endpoint = $endpoints[$endpoint_id];
        $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
        
        if (empty($endpoint_url)) {
            continue;
        }
        
        // Get sample data from API
        $data = get_api_data($endpoint_url, $endpoint);
        
        // Skip if no data
        if (empty($data) || !is_array($data)) {
            continue;
        }
        
        // Extract items based on items path
        $items_path = isset($source['items_path']) ? $source['items_path'] : '';
        $items = $data;
        
        if (!empty($items_path)) {
            $path_parts = explode('.', $items_path);
            
            foreach ($path_parts as $part) {
                if (isset($items[$part])) {
                    $items = $items[$part];
                } else {
                    // Path not found
                    $items = [];
                    break;
                }
            }
        }
        
        // Ensure items is an array and get first item for fields
        if (!is_array($items)) {
            $items = [$items];
        }
        
        // Get fields from first item
        $fields = $common_fields;
        
        if (!empty($items) && isset($items[0]) && is_array($items[0])) {
            $sample_item = $items[0];
            
            // Add field prefix if specified
            $field_prefix = isset($source['field_prefix']) ? $source['field_prefix'] : '';
            
            // Extract fields from sample item
            foreach ($sample_item as $key => $value) {
                $display_key = !empty($field_prefix) ? $field_prefix . $key : $key;
                $fields[$display_key] = ucfirst(str_replace('_', ' ', $key));
                
                // If value is an array or object, add nested fields
                if (is_array($value) || is_object($value)) {
                    foreach ($value as $sub_key => $sub_value) {
                        $nested_key = $display_key . '.' . $sub_key;
                        $fields[$nested_key] = ucfirst(str_replace('_', ' ', $key)) . ' > ' . ucfirst(str_replace('_', ' ', $sub_key));
                    }
                }
            }
        }
        
        // Add source to tags
        $tags[$source_id] = [
            'name'  => $source_id,
            'label' => isset($source['name']) ? $source['name'] : esc_html__('API Source', 'bricks-api-integrator'),
            'fields' => $fields,
        ];
    }
    
    // Add generic API source tag if no specific sources were added
    if (!isset($tags['api_source'])) {
        $tags['api_source'] = [
            'name'  => 'api_source',
            'label' => esc_html__('API Source', 'bricks-api-integrator'),
            'fields' => $common_fields,
        ];
    }
    
    return $tags;
}
