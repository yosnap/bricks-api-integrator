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

// Incluir el archivo de autenticación
require_once plugin_dir_path(__FILE__) . 'auth.php';

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

        <h2><?php esc_html_e('Datos del Endpoint', 'bricks-api-integrator'); ?></h2>

        <?php
        // Obtener el valor actualizado de la URL del endpoint y tipo de autenticación de la base de datos
        $endpoint_url = get_option('bricks_api_endpoint');
        $auth_type = get_option('bricks_api_auth_type', 'none');

        if ($endpoint_url) {
            $args = [];

            // Configurar la autenticación según el tipo seleccionado
            if ($auth_type === 'token') {
                $token = get_option('bricks_api_token', '');
                $args['headers'] = [
                    'Authorization' => 'Bearer ' . $token,
                ];
            } elseif ($auth_type === 'basic') {
                $user = get_option('bricks_api_basic_user', '');
                $password = get_option('bricks_api_basic_password', '');
                $args['headers'] = [
                    'Authorization' => 'Basic ' . base64_encode("$user:$password"),
                ];
            }

            // Realizar la solicitud a la API con la URL actualizada
            $response = wp_remote_get($endpoint_url, $args);

            if (is_wp_error($response)) {
                echo '<div class="notice notice-error"><p>' . __('Error al conectar con la API', 'bricks-api-integrator') . '</p></div>';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                // Almacenar todos los ítems (puedes usarlos para otras partes del plugin)
                $all_items = $data; // Aquí tienes todos los ítems para consumirlos más adelante

                if (!empty($all_items)) {
                    // Mostrar solo el primer ítem en la tabla
                    $first_item = is_array($all_items) ? reset($all_items) : null;

                    if ($first_item) {
                        echo '<div class="accordion">';
                        echo '<button class="accordion-toggle">' . esc_html__('Ver datos del Endpoint', 'bricks-api-integrator') . '</button>';
                        echo '<div class="accordion-content">';

                        // Generar la tabla HTML para mostrar las variables y valores del primer ítem
                        echo '<table class="widefat fixed" cellspacing="0">';
                        echo '<thead><tr><th>' . esc_html__('Variable', 'bricks-api-integrator') . '</th><th>' . esc_html__('Valor', 'bricks-api-integrator') . '</th><th>' . esc_html__('Variable PHP', 'bricks-api-integrator') . '</th></tr></thead>';
                        echo '<tbody>';

                        // Función para renderizar los campos del primer ítem
                        render_api_item_table($first_item);

                        echo '</tbody>';
                        echo '</table>';

                        echo '</div>'; // Cierre de .accordion-content
                        echo '</div>'; // Cierre de .accordion
                    } else {
                        echo '<p>' . __('No se encontró el primer ítem en la respuesta de la API.', 'bricks-api-integrator') . '</p>';
                    }
                } else {
                    echo '<p>' . __('No se pudo recuperar ningún dato del endpoint.', 'bricks-api-integrator') . '</p>';
                }
            }
        } else {
            echo '<p>' . __('Por favor, introduce una URL válida para el endpoint.', 'bricks-api-integrator') . '</p>';
        }
        ?>
    </div>
<?php
}

// Función para renderizar los datos del primer ítem en una tabla
function render_api_item_table($item, $parent_key = '')
{
    foreach ($item as $key => $value) {
        // Generar la clave completa si está anidado
        $variable_key = $parent_key ? $parent_key . "['$key']" : "['$key']";

        // Generar la variable PHP
        $php_variable = '$payload' . $variable_key;

        if (is_array($value)) {
            // Si el valor es un array, llamar a la función de nuevo (recursiva)
            render_api_item_table($value, $variable_key);
        } else {
            // Mostrar la variable, el valor y la variable PHP en una fila
            echo '<tr>';
            echo '<td><code>' . esc_html($variable_key) . '</code></td>'; // Mostrar clave como "restaurant['nombre']"
            echo '<td>' . esc_html($value) . '</td>';
            echo '<td><code>' . esc_html($php_variable) . '</code></td>'; // Mostrar variable PHP como "$payload['restaurant']['nombre']"
            echo '</tr>';
        }
    }
}

// Registro de la configuración del endpoint
add_action('admin_init', 'bricks_api_integrator_settings_init');

function bricks_api_integrator_settings_init()
{
    register_setting('bricks_api_integrator_settings', 'bricks_api_endpoint');

    add_settings_section(
        'bricks_api_integrator_section',
        __('Configuración del Endpoint', 'bricks-api-integrator'),
        null,
        'bricks-api-integrator'
    );

    add_settings_field(
        'bricks_api_endpoint',
        __('URL del Endpoint', 'bricks-api-integrator'),
        'bricks_api_endpoint_render',
        'bricks-api-integrator',
        'bricks_api_integrator_section'
    );
}

// Campo de entrada para la URL del endpoint
function bricks_api_endpoint_render()
{
    $url = get_option('bricks_api_endpoint');
?>
    <input type="url" name="bricks_api_endpoint" value="<?php echo esc_attr($url); ?>" style="width: 100%;" />
<?php
}

// Añadir los estilos para el acordeón y el JavaScript
add_action('admin_footer', 'bricks_api_integrator_accordion_script');

function bricks_api_integrator_accordion_script()
{
?>
    <style>
        .accordion {
            margin-bottom: 10px;
        }

        .accordion-toggle {
            background-color: #f1f1f1;
            border: none;
            cursor: pointer;
            padding: 10px 15px;
            text-align: left;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            outline: none;
        }

        .accordion-toggle.active,
        .accordion-toggle:hover {
            background-color: #ddd;
        }

        .accordion-content {
            padding: 15px;
            background-color: white;
            display: none;
            border-top: 1px solid #ccc;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var accordions = document.querySelectorAll('.accordion-toggle');

            accordions.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    var content = this.nextElementSibling;
                    if (content.style.display === 'block') {
                        content.style.display = 'none';
                    } else {
                        content.style.display = 'block';
                    }
                });
            });
        });
    </script>
<?php
}
