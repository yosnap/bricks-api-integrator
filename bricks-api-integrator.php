<?php
/*
    * Plugin Name: Bricks API Integrator
    * Description: Integra el constructor de páginas Bricks con APIs externas.
    * Version: 1.0
    * Author: Yosn4p Dev
    * Author URI: https://yosn4p.dev
    * License: GPL2
    * License URI: https://www.gnu.org/licenses/gpl-2.0.html
    * Text Domain: bricks-api-integrator
*/

if (!defined('ABSPATH')) {
    exit; // Evita accesos directos
}

// Incluir el archivo que maneja los dynamic tags
require_once plugin_dir_path(__FILE__) . 'dynamic-tags.php';

// Añadir menú en el Dashboard de WordPress
add_action('admin_menu', 'bricks_api_integrator_menu');


function bricks_api_integrator_menu()
{
    add_menu_page(
        'Bricks API Integrator',
        'API Integrator',
        'manage_options',
        'bricks-api-integrator',
        'bricks_api_integrator_dashboard',
        'dashicons-admin-generic', // Icono del menú
        20
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

        if (is_array($value) || is_object($value)) {
            render_api_item_table((array) $value, $variable_key);
        } else {
            echo '<tr>';
            echo '<td><code>' . esc_html($variable_key) . '</code></td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '<td><code>' . esc_html($php_variable) . '</code></td>';
            echo '</tr>';
        }
    }
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
        <?php foreach ($endpoints as $index => $endpoint): ?>
            <div class="endpoint-group" data-index="<?php echo esc_attr($index); ?>">
                <h4><?php esc_html_e('Endpoint', 'bricks-api-integrator'); ?> <?php echo esc_html($index + 1); ?></h4>

                <label><?php esc_html_e('Nombre del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="text" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($endpoint['name']); ?>" style="width: 100%;" placeholder="Nombre del Endpoint" />

                <label><?php esc_html_e('URL del Endpoint:', 'bricks-api-integrator'); ?></label>
                <input type="url" name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($endpoint['url']); ?>" style="width: 100%;" placeholder="URL del Endpoint" />

                <label><?php esc_html_e('Autenticación:', 'bricks-api-integrator'); ?></label>
                <select name="bricks_api_endpoints[<?php echo esc_attr($index); ?>][auth_type]" class="auth-type-select">
                    <option value="none" <?php selected($endpoint['auth_type'], 'none'); ?>><?php esc_html_e('Sin Autenticación', 'bricks-api-integrator'); ?></option>
                    <option value="basic" <?php selected($endpoint['auth_type'], 'basic'); ?>><?php esc_html_e('Autenticación Básica', 'bricks-api-integrator'); ?></option>
                    <option value="token" <?php selected($endpoint['auth_type'], 'token'); ?>><?php esc_html_e('Token', 'bricks-api-integrator'); ?></option>
                </select>

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
