<?php
if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Register dynamic tags with Bricks Builder
 */
add_filter('bricks/dynamic_tags_list', 'add_api_tags_to_builder');
function add_api_tags_to_builder($tags)
{
    // Get configured API launchers
    $api_launchers = get_option('bricks_api_launchers', []);
    if (!is_array($api_launchers) || empty($api_launchers)) {
        // Backward compatibility: If no launchers are configured, use the old method with direct endpoints
        return add_legacy_api_tags_to_builder($tags);
    }
    
    // Get endpoints
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }
    
    // Iterate through each launcher and create dynamic tags
    foreach ($api_launchers as $launcher_id => $launcher) {
        $group_name = isset($launcher['dynamic_tag_group']) ? esc_html($launcher['dynamic_tag_group']) : esc_html($launcher['name']);
        $endpoint_id = isset($launcher['endpoint_id']) ? $launcher['endpoint_id'] : '';
        $field_prefix = isset($launcher['field_prefix']) ? $launcher['field_prefix'] : '';
        
        // Skip if no endpoint ID
        if (empty($endpoint_id) || !isset($endpoints[$endpoint_id])) {
            continue;
        }
        
        $endpoint = $endpoints[$endpoint_id];
        $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;
        
        if (!$endpoint_url) {
            continue; // Skip if no URL
        }
        
        // Get API data
        $data = get_api_data($endpoint_url, $endpoint);
        
        // Validate data is an array before processing
        if (is_array($data)) {
            // Generate dynamic tags with field prefix
            $tags = array_merge($tags, generate_dynamic_tags($data, $group_name, '$payload', 0, $field_prefix));
        } else {
            // If not an array, register an error tag
            $tags[] = [
                'name'  => '{' . sanitize_title($group_name) . '_error}',
                'label' => esc_html__('Error retrieving data', 'bricks-api-integrator'),
                'group' => $group_name,
            ];
        }
    }
    
    return $tags;
}

/**
 * Legacy method to add API tags directly from endpoints (for backward compatibility)
 */
function add_legacy_api_tags_to_builder($tags)
{
    // Get endpoints configured from the plugin panel
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }

    // Iterate through each endpoint and create a group of dynamic tags based on the endpoint name
    foreach ($endpoints as $endpoint) {
        $group_name = isset($endpoint['name']) ? esc_html($endpoint['name']) : esc_html__('Unnamed Group', 'bricks-api-integrator');

        // Get API data for this endpoint
        $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;
        if (!$endpoint_url) {
            continue; // Skip if no URL
        }

        $data = get_api_data($endpoint_url, $endpoint);

        // Validate data is an array before processing
        if (is_array($data)) {
            // Get all PHP variables and register dynamic tags
            $tags = array_merge($tags, generate_dynamic_tags($data, $group_name));
        } else {
            // If not an array, register an error tag
            $tags[] = [
                'name'  => '{' . sanitize_title($group_name) . '_error}',
                'label' => esc_html__('Error retrieving data', 'bricks-api-integrator'),
                'group' => $group_name,
            ];
        }
    }

    return $tags;
}

// Función para generar tags dinámicos a partir de las claves de los datos
function generate_dynamic_tags($data, $group_name, $parent_key = '$payload', $depth = 0, $field_prefix = '')
{
    $tags = [];

    foreach ($data as $key => $value) {
        // Apply field prefix if provided
        $display_key = !empty($field_prefix) ? $field_prefix . $key : $key;
        
        // Generar la clave completa en formato PHP
        $variable_key = $parent_key . "['$key']";

        // Si el valor es un array o un objeto, recursivamente obtener los valores internos
        if (is_array($value) || is_object($value)) {
            foreach ($value as $subkey => $subvalue) {
                $sub_variable_key = $variable_key . "['$subkey']";

                // Generar el label basado en la clave
                $label = generate_label($display_key, $subkey);

                // Registrar el tag dinámico
                $tags[] = [
                    'name'  => '{' . sanitize_title($group_name) . '_' . $label . '}',
                    'label' => $label,
                    'group' => $group_name,
                ];

                // Si es un array, continuar la recursividad
                if (is_array($subvalue) || is_object($subvalue)) {
                    $tags = array_merge($tags, generate_dynamic_tags($subvalue, $group_name, $sub_variable_key, $depth + 1, $field_prefix));
                }
            }
        } else {
            // Generar el label y registrar el tag si no es un array
            $label = sanitize_title($display_key);
            $tags[] = [
                'name'  => '{' . sanitize_title($group_name) . '_' . $label . '}',
                'label' => $label,
                'group' => $group_name,
            ];
        }
    }

    return $tags;
}

// Función para generar el label basado en el formato
function generate_label($key, $index)
{
    $label = sanitize_title($key);

    // Si el índice es numérico y mayor o igual a 1, se añade al final del label
    if (is_numeric($index) && intval($index) >= 1) {
        $label .= '_' . intval($index);
    }

    return $label;
}

/**
 * Get API data from endpoint with authentication support
 *
 * @param string $endpoint_url The URL of the API endpoint
 * @param array $endpoint_config Optional endpoint configuration with authentication details
 * @return array|null The API data or null on error
 */
function get_api_data($endpoint_url, $endpoint_config = [])
{
    // Prepare request arguments
    $args = [];
    
    // Add authentication if provided in endpoint config
    if (!empty($endpoint_config)) {
        $auth_type = isset($endpoint_config['auth_type']) ? $endpoint_config['auth_type'] : 'none';
        
        if ($auth_type === 'token') {
            $token = isset($endpoint_config['token']) ? $endpoint_config['token'] : '';
            if (!empty($token)) {
                $args['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            }
        } elseif ($auth_type === 'basic') {
            $user = isset($endpoint_config['basic_user']) ? $endpoint_config['basic_user'] : '';
            $password = isset($endpoint_config['basic_password']) ? $endpoint_config['basic_password'] : '';
            if (!empty($user) && !empty($password)) {
                $args['headers'] = [
                    'Authorization' => 'Basic ' . base64_encode("$user:$password"),
                ];
            }
        }
    }
    
    // Make the API request
    $response = wp_remote_get($endpoint_url, $args);

    if (is_wp_error($response)) {
        return null; // Return null on error
    }
    
    // Check for successful response code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return null; // Return null on non-200 response
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Ensure JSON response is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null; // Return null on JSON decode error
    }

    return $data;
}

/**
 * Render dynamic tag values
 */
add_filter('bricks/dynamic_data/render_tag', 'get_api_tag_value', 10, 3);
function get_api_tag_value($tag, $post, $context = 'text')
{
    // Get configured API launchers
    $api_launchers = get_option('bricks_api_launchers', []);
    
    // If launchers exist, try to get values from them first
    if (is_array($api_launchers) && !empty($api_launchers)) {
        // Get endpoints
        $endpoints = get_option('bricks_api_endpoints', []);
        if (!is_array($endpoints)) {
            $endpoints = [];
        }
        
        // Iterate through each launcher to check if the tag belongs to its group
        foreach ($api_launchers as $launcher_id => $launcher) {
            $group_name = isset($launcher['dynamic_tag_group']) ? sanitize_title($launcher['dynamic_tag_group']) : sanitize_title($launcher['name']);
            $endpoint_id = isset($launcher['endpoint_id']) ? $launcher['endpoint_id'] : '';
            $field_prefix = isset($launcher['field_prefix']) ? $launcher['field_prefix'] : '';
            
            // Skip if no endpoint ID or endpoint doesn't exist
            if (empty($endpoint_id) || !isset($endpoints[$endpoint_id])) {
                continue;
            }
            
            $endpoint = $endpoints[$endpoint_id];
            $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;
            
            if (!$endpoint_url) {
                continue; // Skip if no URL
            }
            
            // Check if tag belongs to this group
            if (strpos($tag, $group_name . '_') === 0) {
                // Get API data
                $data = get_api_data($endpoint_url, $endpoint);
                
                // Validate data is an array before searching for tag value
                if (is_array($data)) {
                    // Extract tag key without group prefix
                    $tag_key = str_replace($group_name . '_', '', $tag);
                    
                    // If field prefix is used, remove it from the tag key for lookup
                    if (!empty($field_prefix) && strpos($tag_key, $field_prefix) === 0) {
                        $tag_key = substr($tag_key, strlen($field_prefix));
                    }
                    
                    // Get the value
                    return get_nested_value($data, explode('_', $tag_key));
                }
            }
        }
    }
    
    // Fallback to legacy method if no launcher matched or no launchers exist
    return get_legacy_api_tag_value($tag, $post, $context);
}

/**
 * Legacy method to get API tag values directly from endpoints (for backward compatibility)
 */
function get_legacy_api_tag_value($tag, $post, $context = 'text')
{
    // Get endpoints configured from the plugin panel
    $endpoints = get_option('bricks_api_endpoints', []);
    if (!is_array($endpoints)) {
        $endpoints = [];
    }

    // Iterate through each endpoint to check if the tag belongs to a group
    foreach ($endpoints as $endpoint) {
        $group_name = isset($endpoint['name']) ? sanitize_title($endpoint['name']) : null;
        $endpoint_url = isset($endpoint['url']) ? esc_url($endpoint['url']) : null;

        if (!$group_name || !$endpoint_url) {
            continue; // Skip if no name or URL
        }

        // Get API data
        $data = get_api_data($endpoint_url, $endpoint);

        // Validate data is an array before searching for tag value
        if (is_array($data)) {
            // Look for and return the corresponding dynamic tag value
            if (strpos($tag, $group_name . '_') === 0) {
                $tag_key = str_replace($group_name . '_', '', $tag);
                return get_nested_value($data, explode('_', $tag_key));
            }
        }
    }

    return $tag; // Return original tag if no match found
}

// Función para obtener el valor anidado basado en la estructura de la clave
function get_nested_value($data, $keys)
{
    foreach ($keys as $key) {
        if (isset($data[$key])) {
            $data = $data[$key];
        } else {
            return 'Dato no disponible';
        }
    }
    return is_array($data) ? json_encode($data) : esc_html($data);
}
