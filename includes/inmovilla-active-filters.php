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

    // Obtener campos de filtro conocidos
    $filter_fields = function_exists( 'inmovilla_get_filter_fields' ) ? inmovilla_get_filter_fields() : [];

    // Filtrar parámetros que son filtros conocidos de Inmovilla
    $active_filters = [];
    foreach ( $params as $key => $value ) {
        if ( ! empty( $value ) && isset( $filter_fields[ $key ] ) ) {
            $active_filters[ $key ] = $value;
        }
    }

    if ( empty( $active_filters ) ) {
        return '';
    }

    $html = '<div class="inmovilla-active-filters" style="margin: 20px 0; padding: 15px; background: #f3f4f6; border-radius: 8px;">';
    $html .= '<strong style="display: block; margin-bottom: 10px;">Filtros activos:</strong>';

    foreach ( $active_filters as $key => $value ) {
        $config = $filter_fields[ $key ];
        $label  = $config['label'] ?? str_replace( 'key_', '', $key );

        // Resolver valor legible: si el campo tiene opciones, buscar el texto
        $display_value = $value;
        if ( ! empty( $config['options'][ $value ] ) ) {
            $display_value = $config['options'][ $value ];
        } elseif ( ! empty( $config['source_type'] ) ) {
            // Obtener opciones desde la API para mostrar nombre legible
            $options = function_exists( 'inmovilla_get_filter_options' )
                ? inmovilla_get_filter_options( $config['source_type'] )
                : [];
            if ( ! empty( $options[ $value ] ) ) {
                $display_value = $options[ $value ];
            }
        }

        // URL sin este filtro
        $clean_params = array_diff_key( $params, [ $key => true ] );
        $clean_url = add_query_arg( $clean_params );

        $html .= sprintf(
            '<span style="display: inline-block; margin-right: 10px; margin-bottom: 8px; padding: 6px 12px; background: #2563eb; color: white; border-radius: 20px; font-size: 13px;">
                <strong>%s:</strong> %s
                <a href="%s" style="color: white; text-decoration: none; margin-left: 5px; font-weight: bold;">×</a>
            </span>',
            esc_html( $label ),
            esc_html( $display_value ),
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
