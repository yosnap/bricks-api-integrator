<?php
/**
 * Campos y configuración específica para Inmovilla
 *
 * Define los campos conocidos de Inmovilla para mejorar
 * la visualización en dynamic tags y query loops
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Campos conocidos de inmuebles Inmovilla
 */
function get_inmovilla_inmueble_fields() {
    return array(
        // Datos básicos
        'codigo'              => array(
            'label'       => 'Código de referencia',
            'description' => 'Código único del inmueble',
            'type'        => 'text',
        ),
        'titulo'              => array(
            'label'       => 'Título',
            'description' => 'Nombre o título del inmueble',
            'type'        => 'text',
        ),
        'descripcion'         => array(
            'label'       => 'Descripción',
            'description' => 'Descripción completa del inmueble',
            'type'        => 'textarea',
        ),

        // Ubicación
        'ciudad'              => array(
            'label'       => 'Ciudad',
            'description' => 'Nombre de la ciudad',
            'type'        => 'text',
        ),
        'zona'                => array(
            'label'       => 'Zona',
            'description' => 'Zona o barrio',
            'type'        => 'text',
        ),
        'direccion'           => array(
            'label'       => 'Dirección',
            'description' => 'Dirección completa',
            'type'        => 'text',
        ),
        'codigo_postal'       => array(
            'label'       => 'Código postal',
            'description' => 'Código postal',
            'type'        => 'text',
        ),

        // Precios
        'precioinmo'          => array(
            'label'       => 'Precio de compra',
            'description' => 'Precio del inmueble',
            'type'        => 'number',
        ),
        'precioalq'           => array(
            'label'       => 'Precio de alquiler',
            'description' => 'Precio mensual de alquiler',
            'type'        => 'number',
        ),

        // Características físicas
        'superficie'          => array(
            'label'       => 'Superficie total',
            'description' => 'Superficie total en m²',
            'type'        => 'number',
        ),
        'superficie_util'     => array(
            'label'       => 'Superficie útil',
            'description' => 'Superficie útil en m²',
            'type'        => 'number',
        ),
        'habitaciones'        => array(
            'label'       => 'Habitaciones',
            'description' => 'Número de habitaciones',
            'type'        => 'number',
        ),
        'baños'               => array(
            'label'       => 'Baños',
            'description' => 'Número de baños',
            'type'        => 'number',
        ),
        'plantas'             => array(
            'label'       => 'Plantas',
            'description' => 'Número de plantas',
            'type'        => 'number',
        ),
        'ano_construccion'    => array(
            'label'       => 'Año de construcción',
            'description' => 'Año de construcción',
            'type'        => 'number',
        ),

        // Características booleanas
        'ascensor'            => array(
            'label'       => 'Ascensor',
            'description' => 'Tiene ascensor',
            'type'        => 'boolean',
        ),
        'terraza'             => array(
            'label'       => 'Terraza',
            'description' => 'Tiene terraza',
            'type'        => 'boolean',
        ),
        'piscina'             => array(
            'label'       => 'Piscina',
            'description' => 'Tiene piscina',
            'type'        => 'boolean',
        ),
        'garaje'              => array(
            'label'       => 'Garaje',
            'description' => 'Tiene garaje/aparcamiento',
            'type'        => 'boolean',
        ),
        'calefaccion'         => array(
            'label'       => 'Calefacción',
            'description' => 'Tiene calefacción',
            'type'        => 'boolean',
        ),
        'aire_acondicionado'  => array(
            'label'       => 'Aire acondicionado',
            'description' => 'Tiene aire acondicionado',
            'type'        => 'boolean',
        ),

        // Medios
        'imagen'              => array(
            'label'       => 'Imagen principal',
            'description' => 'URL de la imagen principal',
            'type'        => 'image',
        ),
        'galeria'             => array(
            'label'       => 'Galería de imágenes',
            'description' => 'Array de URLs de imágenes',
            'type'        => 'gallery',
        ),
    );
}

/**
 * Filtro para mejorar campos de dynamic tags de Inmovilla
 */
add_filter( 'bricks/query/dynamic_tags', function ( $tags ) {
    // Mejorar campos de Inmovilla si existen
    $inmovilla_sources = array( 'inmovilla_inmuebles', 'inmovilla_destacados' );

    foreach ( $inmovilla_sources as $source_id ) {
        if ( isset( $tags[ $source_id ] ) ) {
            $fields = get_inmovilla_inmueble_fields();

            foreach ( $fields as $field_key => $field_config ) {
                $tags[ $source_id ]['fields'][ $field_key ] = $field_config['label'];
            }
        }
    }

    return $tags;
}, 20 );

/**
 * Helper: Formatear precio para visualización
 */
function format_inmovilla_precio( $precio, $tipo = 'compra' ) {
    if ( ! is_numeric( $precio ) ) {
        return '-';
    }

    $precio = floatval( $precio );

    if ( $precio == 0 ) {
        return '-';
    }

    return number_format( $precio, 0, ',', '.' ) . ' €';
}

/**
 * Helper: Formatear superficie
 */
function format_inmovilla_superficie( $superficie ) {
    if ( ! is_numeric( $superficie ) ) {
        return '-';
    }

    return number_format( floatval( $superficie ), 0, ',', '.' ) . ' m²';
}

/**
 * Helper: Convertir booleano de Inmovilla a legible
 */
function format_inmovilla_boolean( $value ) {
    return ( $value == 1 || $value === true ) ? 'Sí' : 'No';
}

/**
 * Filtro para aplicar formateo automático a campos conocidos
 * cuando se usan en dynamic tags
 */
add_filter( 'bricks/query_result', function ( $result, $query_type, $params ) {
    // Solo aplicar a sources de Inmovilla
    if ( ! in_array( $query_type, array( 'inmovilla_inmuebles', 'inmovilla_destacados' ) ) ) {
        return $result;
    }

    if ( ! is_array( $result ) || empty( $result['data'] ) ) {
        return $result;
    }

    // Procesar cada elemento
    foreach ( $result['data'] as &$item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        // Aplicar formateo a campos conocidos
        if ( isset( $item['precioinmo'] ) ) {
            $item['_formatted_precioinmo'] = format_inmovilla_precio( $item['precioinmo'], 'compra' );
        }

        if ( isset( $item['precioalq'] ) ) {
            $item['_formatted_precioalq'] = format_inmovilla_precio( $item['precioalq'], 'alquiler' );
        }

        if ( isset( $item['superficie'] ) ) {
            $item['_formatted_superficie'] = format_inmovilla_superficie( $item['superficie'] );
        }

        // Convertir booleanos
        foreach ( array( 'ascensor', 'terraza', 'piscina', 'garaje', 'calefaccion', 'aire_acondicionado' ) as $bool_field ) {
            if ( isset( $item[ $bool_field ] ) ) {
                $item[ '_formatted_' . $bool_field ] = format_inmovilla_boolean( $item[ $bool_field ] );
            }
        }
    }

    return $result;
}, 10, 3 );
