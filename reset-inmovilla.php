<?php
/**
 * Script para resetear/actualizar el endpoint de Inmovilla
 * Actualiza los parámetros sin eliminar otros endpoints
 */

define( 'WP_USE_THEMES', false );
$wp_load = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
require( $wp_load );

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'No tienes permisos.' );
}

// Cargar el archivo de endpoints de Inmovilla
require_once dirname( __FILE__ ) . '/includes/inmovilla-auto-endpoints.php';

// Usar la función de actualización forzada
if ( function_exists( 'force_update_inmovilla_endpoint' ) ) {
    force_update_inmovilla_endpoint();
    $mensaje = 'El endpoint de Inmovilla ha sido actualizado con los nuevos parámetros.';
    $tipo = 'success';
} else {
    $mensaje = 'Error: No se encontró la función de actualización.';
    $tipo = 'error';
}

$color_bg = $tipo === 'success' ? '#d4edda' : '#f8d7da';
$color_border = $tipo === 'success' ? '#c3e6cb' : '#f5c6cb';
$color_text = $tipo === 'success' ? '#155724' : '#721c24';
$icono = $tipo === 'success' ? '✅' : '❌';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Inmovilla</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f1f1f1; }
        .box { background: <?php echo $color_bg; ?>; border: 1px solid <?php echo $color_border; ?>; color: <?php echo $color_text; ?>; padding: 20px 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        h2 { margin-top: 0; }
        .button { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; }
        .button:hover { background: #005a87; }
        .params { background: rgba(0,0,0,0.05); padding: 15px; border-radius: 4px; margin-top: 15px; }
        .params code { display: block; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="box">
        <h2><?php echo $icono; ?> Reset Endpoint Inmovilla</h2>
        <p><?php echo esc_html( $mensaje ); ?></p>

        <?php if ( $tipo === 'success' ) : ?>
        <div class="params">
            <strong>Parámetros configurados:</strong>
            <code>agencia, password, idioma, lostipos</code>
            <code>tipo, pos, num_elementos, where, orden, ip</code>
        </div>
        <?php endif; ?>

        <a href="<?php echo admin_url( 'admin.php?page=bricks-api-integrator-endpoints' ); ?>" class="button">Volver a Endpoints</a>
    </div>
</body>
</html>
