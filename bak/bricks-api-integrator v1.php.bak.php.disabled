<?php
/*
    * Plugin Name: Bricks API Integrator
    * Description: Integra el constructor de páginas Bricks con APIs externas.
    * Version: 1.0
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
define('BRICKS_API_INTEGRATOR_VERSION', '1.0');
define('BRICKS_API_INTEGRATOR_PATH', plugin_dir_path(__FILE__));
define('BRICKS_API_INTEGRATOR_URL', plugin_dir_url(__FILE__));

// Incluir los archivos necesarios
require_once BRICKS_API_INTEGRATOR_PATH . 'dynamic-tags.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/endpoints.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/launcher.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/sources.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/templates.php';
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/debug-source.php'; // Archivo de depuración
require_once BRICKS_API_INTEGRATOR_PATH . 'includes/direct-source.php'; // Integración directa con Bricks

// Asegurarse de que la función get_api_data esté disponible
if (!function_exists('get_api_data')) {
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
}

/**
 * Register dynamic tags for Bricks Query Loop
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
add_filter('bricks/query/dynamic_tags', 'bricks_api_register_dynamic_tags_for_query_loop');

// Añadir menú en el Dashboard de WordPress
add_action('admin_menu', 'bricks_api_integrator_menu');

function bricks_api_integrator_menu() {
    // Menú principal
    add_menu_page(
        'Bricks API Integrator',
        'API Integrator',
        'manage_options',
        'bricks-api-integrator',
        'bricks_api_integrator_dashboard',
        'dashicons-admin-generic', // Icono del menú
        20
    );
    
    // Submenús
    add_submenu_page(
        'bricks-api-integrator',
        __('API Sources', 'bricks-api-integrator'),
        __('API Sources', 'bricks-api-integrator'),
        'manage_options',
        'bricks-api-integrator-sources',
        'render_api_sources_page'
    );
    
    add_submenu_page(
        'bricks-api-integrator',
        __('API Launcher', 'bricks-api-integrator'),
        __('API Launcher', 'bricks-api-integrator'),
        'manage_options',
        'bricks-api-integrator-launcher',
        'render_api_launcher_page'
    );
    
    add_submenu_page(
        'bricks-api-integrator',
        __('API Templates', 'bricks-api-integrator'),
        __('API Templates', 'bricks-api-integrator'),
        'manage_options',
        'bricks-api-templates',
        'render_api_templates_page'
    );
}

// Callback del dashboard
function bricks_api_integrator_dashboard()
{
?>
    <div class="wrap">
        <h1><?php esc_html_e('Bricks API Integrator', 'bricks-api-integrator'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bricks_api_integrator_settings');
            do_settings_sections('bricks-api-integrator');
            submit_button();
            ?>
        </form>

        <h2><?php esc_html_e('Endpoints Configurados', 'bricks-api-integrator'); ?></h2>

        <?php
        $endpoints = get_option('bricks_api_endpoints', []);

        if (!is_array($endpoints)) {
            $endpoints = [];
        }

        if (!empty($endpoints)) {
            foreach ($endpoints as $index => $endpoint) {
                $endpoint_name = isset($endpoint['name']) ? $endpoint['name'] : 'Endpoint sin nombre';
                $endpoint_url = isset($endpoint['url']) ? $endpoint['url'] : '';
                $auth_type = isset($endpoint['auth_type']) ? $endpoint['auth_type'] : 'none';

                echo '<div class="accordion">';
                echo '<button class="accordion-toggle" aria-expanded="false">' . esc_html($endpoint_name) . ' <span class="toggle-icon">+</span></button>';
                echo '<div class="accordion-content">';
                echo '<p><strong>URL:</strong> ' . esc_html($endpoint_url) . '</p>';

                if ($auth_type === 'basic') {
                    echo '<p><strong>Autenticación:</strong> ' . __('Básica', 'bricks-api-integrator') . '</p>';
                } elseif ($auth_type === 'token') {
                    echo '<p><strong>Autenticación:</strong> ' . __('Token', 'bricks-api-integrator') . '</p>';
                } else {
                    echo '<p><strong>Autenticación:</strong> ' . __('Sin autenticación', 'bricks-api-integrator') . '</p>';
                }

                if ($endpoint_url) {
                    $args = [];

                    if ($auth_type === 'token') {
                        $token = isset($endpoint['token']) ? $endpoint['token'] : '';
                        $args['headers'] = [
                            'Authorization' => 'Bearer ' . $token,
                        ];
                    } elseif ($auth_type === 'basic') {
                        $user = isset($endpoint['basic_user']) ? $endpoint['basic_user'] : '';
                        $password = isset($endpoint['basic_password']) ? $endpoint['basic_password'] : '';
                        $args['headers'] = [
                            'Authorization' => 'Basic ' . base64_encode("$user:$password"),
                        ];
                    }

                    $response = wp_remote_get($endpoint_url, $args);

                    if (is_wp_error($response)) {
                        echo '<div class="notice notice-error"><p>' . __('Error al conectar con la API', 'bricks-api-integrator') . '</p></div>';
                    } else {
                        $body = wp_remote_retrieve_body($response);
                        $data = json_decode($body, true);

                        if ($data === null) {
                            echo '<p>' . __('La respuesta de la API es nula o inválida.', 'bricks-api-integrator') . '</p>';
                        } elseif (is_array($data) || is_object($data)) {
                            echo '<p>' . __('Datos devueltos por la API:', 'bricks-api-integrator') . '</p>';
                            echo '<table class="widefat fixed" cellspacing="0">';
                            echo '<thead><tr><th>' . esc_html__('Variable', 'bricks-api-integrator') . '</th><th>' . esc_html__('Valor', 'bricks-api-integrator') . '</th><th>' . esc_html__('Variable PHP', 'bricks-api-integrator') . '</th></tr></thead>';
                            echo '<tbody>';

                            if (is_array($data) && isset($data[0])) {
                                render_api_item_table($data[0], '$payload[' . $index . ']');
                            } else {
                                render_api_item_table((array) $data, '$payload[' . $index . ']');
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p>' . __('No se pudo recuperar ningún dato del endpoint.', 'bricks-api-integrator') . '</p>';
                        }
                    }
                }

                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('No hay endpoints configurados.', 'bricks-api-integrator') . '</p>';
        }
        ?>
        <button id="add-endpoint" class="button"><?php esc_html_e('Añadir otro endpoint', 'bricks-api-integrator'); ?></button>
    </div>
<?php
}

// Función para renderizar los datos de un objeto o array
function render_api_item_table($item, $parent_key = '$payload')
{
    foreach ($item as $key => $value) {
        $variable_key = $parent_key . "['$key']";
        $php_variable = $variable_key;

        if (is_array($value)) {
            // If the array is a simple array of strings or numbers, concatenate the values
            if (array_is_list($value) && (all_items_are_strings_or_numbers($value))) {
                $concatenated_values = implode(', ', $value); // Join the array values with commas
                echo '<tr>';
                echo '<td><code>' . esc_html($variable_key) . '</code></td>';
                echo '<td>' . esc_html($concatenated_values) . '</td>';
                echo '<td><code>' . esc_html($php_variable) . '</code></td>';
                echo '</tr>';
            } else {
                // If it's a nested associative array or more complex structure, recurse through it
                render_api_item_table($value, $variable_key);
            }
        } else {
            // For non-array values, display them normally
            echo '<tr>';
            echo '<td><code>' . esc_html($variable_key) . '</code></td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '<td><code>' . esc_html($php_variable) . '</code></td>';
            echo '</tr>';
        }
    }
}

// Helper function to check if all array items are strings or numbers
function all_items_are_strings_or_numbers($array)
{
    foreach ($array as $item) {
        if (!is_string($item) && !is_numeric($item)) {
            return false;
        }
    }
    return true;
}


// Registro de la configuración del endpoint
add_action('admin_init', 'bricks_api_integrator_settings_init');

function bricks_api_integrator_settings_init()
{
    register_setting('bricks_api_integrator_settings', 'bricks_api_endpoints');

    add_settings_section(
        'bricks_api_integrator_section',
        __('Configuración de Endpoints', 'bricks-api-integrator'),
        null,
        'bricks-api-integrator'
    );

    add_settings_field(
        'bricks_api_endpoints',
        __('Endpoints', 'bricks-api-integrator'),
        'bricks_api_endpoints_render',
        'bricks-api-integrator',
        'bricks_api_integrator_section'
    );
}

// Campo para agregar múltiples endpoints
function bricks_api_endpoints_render()
{
    $endpoints = get_option('bricks_api_endpoints', []);

    if (!is_array($endpoints)) {
        $endpoints = [];
    }
?>
    <div id="endpoints-wrapper">
        <?php 
        $counter = 1;
        foreach ($endpoints as $index => $endpoint): 
            // Asegurarse de que el índice sea numérico
            $display_index = is_numeric($index) ? intval($index) : $counter;
        ?>
            <div class="endpoint-group" data-index="<?php echo esc_attr($index); ?>">
                <h4><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?> <?php echo esc_html($counter); ?></h4>

                <label><?php esc_html_e('Nombre del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($endpoint['name']); ?>" style="width: 100%;" placeholder="Nombre del Endpoint" />

                <label><?php esc_html_e('URL del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="url" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($endpoint['url']); ?>" style="width: 100%;" placeholder="URL del Endpoint" />

                <label><?php esc_html_e('Autenticación:', 'bricks-api-integrator'); ?></label>
                <select name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][auth_type]" class="auth-type-select">
                    <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>><?php esc_html_e('Sin Autenticación', 'bricks-api-integrator'); ?></option>
                    <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>><?php esc_html_e('Autenticación Básica', 'bricks-api-integrator'); ?></option>
                    <option value="api_key" <?php selected($endpoint['auth_type'], 'api_key'); ?>><?php esc_html_e('API Key', 'bricks-api-integrator'); ?></option>
                </select>
                <?php $counter++; ?>

                <div class="auth-fields">
                    <?php if ($endpoint['auth_type'] === 'basic') : ?>
                        <label><?php esc_html_e('Usuario:', 'bricks-api-integrator'); ?></label>
                        <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][basic_user]" value="<?php echo esc_attr($endpoint['basic_user']); ?>" style="width: 100%;" placeholder="Usuario" />

                        <label><?php esc_html_e('Contraseña:', 'bricks-api-integrator'); ?></label>
                        <input type="password" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][basic_password]" value="<?php echo esc_attr($endpoint['basic_password']); ?>" style="width: 100%;" placeholder="Contraseña" />
                    <?php elseif ($endpoint['auth_type'] === 'token') : ?>
                        <label><?php esc_html_e('Token:', 'bricks-api-integrator'); ?></label>
                        <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][token]" value="<?php echo esc_attr($endpoint['token']); ?>" style="width: 100%;" placeholder="Token" />
                    <?php endif; ?>
                </div>

                <button type="button" class="remove-endpoint button"><?php esc_html_e('Eliminar Endpoint', 'bricks-api-integrator'); ?></button>
                <hr>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

// Enqueue styles and scripts
add_action('admin_enqueue_scripts', 'bricks_api_integrator_assets');
function bricks_api_integrator_assets()
{
    // Enqueue CSS
    wp_enqueue_style('bricks-api-integrator-style', plugin_dir_url(__FILE__) . 'assets/bricks-api-integrator.css');

    // Enqueue JavaScript
    wp_enqueue_script('bricks-api-integrator-script', plugin_dir_url(__FILE__) . 'assets/bricks-api-integrator.js', ['jquery'], null, true);
}
