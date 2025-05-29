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

// El menú principal ya está registrado en functions.php
// Esta función solo renderiza el dashboard
function bricks_api_integrator_render_dashboard()
{
    // Contenido del dashboard
    include_once(BRICKS_API_INTEGRATOR_PATH . 'templates/dashboard.php');
}
