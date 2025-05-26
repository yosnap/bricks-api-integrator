<?php
/**
 * Debug API Source
 * 
 * Este archivo crea un API Source de prueba para verificar la integración con Bricks
 */

if (!defined('ABSPATH')) {
    exit; // Evitar el acceso directo
}

/**
 * Crear un API Source de prueba
 */
function create_debug_api_source() {
    // Verificar si ya existe el API Source de prueba
    $api_sources = get_option('bricks_api_sources', []);
    
    if (isset($api_sources['debug_api_source'])) {
        error_log('API Source de prueba ya existe');
        return;
    }
    
    // Crear un API Source de prueba
    $debug_source = [
        'name' => 'API Source de Prueba',
        'endpoint_id' => '0', // Usar el primer endpoint configurado
        'items_path' => '',
        'field_prefix' => 'snap_',
        'pagination_type' => 'none',
        'pagination_param' => '',
        'per_page_param' => '',
        'dynamic_params' => [
            [
                'name' => 'id',
                'source' => 'static',
                'default' => '5'
            ]
        ]
    ];
    
    // Guardar el API Source de prueba
    $api_sources['debug_api_source'] = $debug_source;
    update_option('bricks_api_sources', $api_sources);
    
    error_log('API Source de prueba creado: ' . print_r($debug_source, true));
}

/**
 * Registrar el API Source de prueba con Bricks
 */
function register_debug_api_source_with_bricks($sources) {
    // Agregar el API Source de prueba a Bricks
    $sources['debug_api_source'] = [
        'name' => 'API Source de Prueba',
        'class' => 'Bricks_API_Source_Query',
    ];
    
    error_log('API Source de prueba registrado con Bricks');
    
    return $sources;
}

// Crear el API Source de prueba
add_action('init', 'create_debug_api_source');

// Registrar el API Source de prueba con Bricks
add_filter('bricks/query/sources', 'register_debug_api_source_with_bricks', 20);
