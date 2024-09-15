<?php
if (!defined('ABSPATH')) {
    exit; // Evita accesos directos
}

// Renderizar el dashboard del plugin
function bricks_api_integrator_render_dashboard()
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

        <!-- Aquí agregas la lógica de los endpoints -->
    </div>
<?php
}

// Registrar el menú de administración
add_action('admin_menu', 'bricks_api_integrator_menu');
function bricks_api_integrator_menu()
{
    add_menu_page(
        'Bricks API Integrator',
        'API Integrator',
        'manage_options',
        'bricks-api-integrator',
        'bricks_api_integrator_render_dashboard',
        'dashicons-admin-generic',
        20
    );
}
