<?php
/**
 * Componente para mostrar filtros activos de Inmovilla
 * Shortcode simple: [inmovilla_active_filters]
 *
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Registrar el shortcode
add_shortcode( 'inmovilla_active_filters', 'inmovilla_render_active_filters' );

/**
 * Renderizar los filtros activos
 */
function inmovilla_render_active_filters() {
    // Obtener parámetros de la URL
    $params = wp_parse_args( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( empty( $params ) ) {
        return '';
    }

    // Filtrar solo parámetros que comienzan con key_
    $active_filters = [];
    foreach ( $params as $key => $value ) {
        if ( strpos( $key, 'key_' ) === 0 && ! empty( $value ) ) {
            $active_filters[ $key ] = $value;
        }
    }

    if ( empty( $active_filters ) ) {
        return '';
    }

    $filter_labels = [
        'key_tipo'      => 'Tipo',
        'key_loca'      => 'Ubicación',
        'key_zona'      => 'Zona',
        'key_precio_min' => 'Precio Min',
        'key_precio_max' => 'Precio Max',
    ];

    $html = '<div class="inmovilla-active-filters" style="margin: 20px 0; padding: 15px; background: #f3f4f6; border-radius: 8px;">';
    $html .= '<strong style="display: block; margin-bottom: 10px;">Filtros activos:</strong>';

    foreach ( $active_filters as $key => $value ) {
        $label = isset( $filter_labels[ $key ] ) ? $filter_labels[ $key ] : str_replace( 'key_', '', $key );

        // URL sin este filtro
        $clean_params = array_diff_key( $params, [ $key => true ] );
        $clean_url = add_query_arg( $clean_params );

        $html .= sprintf(
            '<span style="display: inline-block; margin-right: 10px; margin-bottom: 8px; padding: 6px 12px; background: #2563eb; color: white; border-radius: 20px; font-size: 13px;">
                <strong>%s:</strong> %s
                <a href="%s" style="color: white; text-decoration: none; margin-left: 5px; font-weight: bold;">×</a>
            </span>',
            esc_html( $label ),
            esc_html( $value ),
            esc_url( $clean_url )
        );
    }

    // Botón limpiar todos
    $base_url = remove_query_arg( array_keys( $active_filters ) );
    $html .= sprintf(
        '<a href="%s" style="display: inline-block; margin-left: 10px; padding: 6px 12px; background: #ef4444; color: white; border-radius: 4px; text-decoration: none; font-size: 13px;">Limpiar todos</a>',
        esc_url( $base_url )
    );

    $html .= '</div>';
    return $html;
}
