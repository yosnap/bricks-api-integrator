<?php
/**
 * Registro automático de Endpoints de Inmovilla
 * Se ejecuta al activar el plugin y registra los endpoints en bricks_api_endpoints
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registrar endpoints de Inmovilla automáticamente
 * Ejecutar al activar el plugin o cuando se detecte que faltan
 */
function register_inmovilla_api_endpoints() {
    // Obtener endpoints existentes
    $endpoints = get_option( 'bricks_api_endpoints', array() );

    // Verificar si ya existen los endpoints de Inmovilla
    $inmovilla_endpoint_names = array(
        'Inmovilla - API',
    );

    $endpoint_names = wp_list_pluck( $endpoints, 'name' );
    $needs_registration = true;

    foreach ( $inmovilla_endpoint_names as $name ) {
        if ( in_array( $name, $endpoint_names ) ) {
            $needs_registration = false;
            break;
        }
    }

    // Si ya existen, no hacer nada
    if ( ! $needs_registration ) {
        return;
    }

    // Registrar el endpoint de Inmovilla
    $new_endpoint = array(
        'name'                => 'Inmovilla - API',
        'url'                 => 'https://apiweb.inmovilla.com/apiweb/apiweb.php',
        'method'              => 'POST',
        'auth_type'           => 'none',
        'token'               => '',
        'basic_user'          => '',
        'basic_password'      => '',
        'api_key'             => '',
        'api_key_header'      => 'X-API-Key',
        'dynamic_params'      => array(
            array(
                'name'     => 'agencia',
                'source'   => 'static',
                'default'  => '2',
                'required' => true,
            ),
            array(
                'name'     => 'password',
                'source'   => 'static',
                'default'  => '82ku9xz2aw3',
                'required' => true,
            ),
            array(
                'name'     => 'idioma',
                'source'   => 'static',
                'default'  => '1',
                'required' => true,
            ),
            array(
                'name'     => 'lostipos',
                'source'   => 'static',
                'default'  => 'lostipos',
                'required' => true,
            ),
            array(
                'name'     => 'tipo',
                'source'   => 'static',
                'default'  => 'paginacion',
                'required' => true,
            ),
            array(
                'name'     => 'pos',
                'source'   => 'static',
                'default'  => '1',
                'required' => true,
            ),
            array(
                'name'     => 'num_elementos',
                'source'   => 'static',
                'default'  => '20',
                'required' => true,
            ),
            array(
                'name'     => 'where',
                'source'   => 'static',
                'default'  => '',
                'required' => false,
            ),
            array(
                'name'     => 'orden',
                'source'   => 'static',
                'default'  => '',
                'required' => false,
            ),
            array(
                'name'     => 'ip',
                'source'   => 'static',
                'default'  => gethostbyname( gethostname() ) !== gethostname() ? gethostbyname( gethostname() ) : '127.0.0.1',
                'required' => true,
            ),
        ),
        'field_transformers'  => [],
    );

    // Añadir el endpoint
    $endpoints[] = $new_endpoint;

    // Guardar
    update_option( 'bricks_api_endpoints', $endpoints );

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[Inmovilla] Endpoint API registrado correctamente' );
    }
}

/**
 * Forzar actualización del endpoint de Inmovilla
 * Útil cuando cambian los parámetros requeridos
 */
function force_update_inmovilla_endpoint() {
    $endpoints = get_option( 'bricks_api_endpoints', array() );

    // Buscar y eliminar el endpoint existente de Inmovilla
    foreach ( $endpoints as $key => $endpoint ) {
        if ( isset( $endpoint['name'] ) && $endpoint['name'] === 'Inmovilla - API' ) {
            unset( $endpoints[ $key ] );
            break;
        }
    }

    // Reindexar el array
    $endpoints = array_values( $endpoints );

    // Guardar sin el endpoint de Inmovilla
    update_option( 'bricks_api_endpoints', $endpoints );

    // Ahora registrar el nuevo endpoint con los parámetros actualizados
    register_inmovilla_api_endpoints();

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '[Inmovilla] Endpoint API actualizado forzadamente' );
    }
}

// Registrar inmediatamente al cargar el plugin
add_action( 'plugins_loaded', 'register_inmovilla_api_endpoints', 5 );

// También en admin_init como respaldo
add_action( 'admin_init', 'register_inmovilla_api_endpoints', 5 );

// Forzar ejecución si se accede a la página de endpoints
add_action( 'load-admin_page_bricks-api-integrator-endpoints', 'register_inmovilla_api_endpoints', 1 );
