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

        <h2><?php esc_html_e('Datos del Endpoint en una Tabla', 'bricks-api-integrator'); ?></h2>

        <?php
        // Obtener el valor actualizado de la URL del endpoint de la base de datos
        $endpoint_url = get_option('bricks_api_endpoint');

        if ($endpoint_url) {
            // Realizar la solicitud a la API con la URL actualizada
            $response = wp_remote_get($endpoint_url);

            if (is_wp_error($response)) {
                echo '<div class="notice notice-error"><p>' . __('Error al conectar con la API', 'bricks-api-integrator') . '</p></div>';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (!empty($data)) {
                    // Mostrar los datos en una tabla
                    echo '<table class="widefat fixed" cellspacing="0">';
                    echo '<thead><tr><th>' . esc_html__('Variable PHP', 'bricks-api-integrator') . '</th><th>' . esc_html__('Valor', 'bricks-api-integrator') . '</th></tr></thead>';
                    echo '<tbody>';

                    // Función recursiva para renderizar los datos del array en la tabla
                    render_payload_table($data, '$payload');

                    echo '</tbody>';
                    echo '</table>';
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

// Función recursiva para renderizar los datos del payload en la tabla
function render_payload_table($data, $path = '$payload')
{
    foreach ($data as $key => $value) {
        $new_path = $path . "['" . $key . "']";

        if (is_array($value)) {
            render_payload_table($value, $new_path); // Llamada recursiva para arrays
        } else {
            echo '<tr>';
            echo '<td><code>' . esc_html($new_path) . '</code></td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
    }
}

// Registro de la configuración del endpoint
add_action('admin_init', 'bricks_api_integrator_settings_init');

function bricks_api_integrator_settings_init()
{
    register_setting('bricks_api_integrator_settings', 'bricks_api_endpoint'); // Aquí se registra la URL

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
    $url = get_option('bricks_api_endpoint'); // Obtener el valor guardado de la base de datos
?>
    <input type="url" name="bricks_api_endpoint" value="<?php echo esc_attr($url); ?>" style="width: 100%;" />
<?php
}
