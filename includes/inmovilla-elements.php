<?php
/**
 * Elementos UI para Inmovilla (Paginación, Filtros, Ordenamiento)
 *
 * Shortcodes para usar en Bricks que funcionan con parámetros URL
 * Incluye 4 templates de estilos: classic, modern, minimal, custom
 *
 * @package Bricks_API_Integrator
 * @version 0.3-beta
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtener opciones de UI con fallback
 */
function inmovilla_get_element_options() {
    if (function_exists('inmovilla_get_ui_options')) {
        return inmovilla_get_ui_options();
    }
    // Fallback si no está cargado el archivo de settings
    return [
        'default_template' => 'modern',
        'pagination_prev_text' => 'Anterior',
        'pagination_next_text' => 'Siguiente',
        'results_format' => 'Mostrando {from}-{to} de {total} inmuebles',
        'results_empty_text' => 'No se encontraron resultados',
        'filter_submit_text' => 'Buscar',
        'filter_clear_text' => 'Limpiar',
    ];
}

/**
 * Shortcode: Paginación de Inmovilla
 * Uso: [inmovilla_pagination template="modern"]
 */
add_shortcode('inmovilla_pagination', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'show_info' => 'true',
        'prev_text' => $ui_options['pagination_prev_text'],
        'next_text' => $ui_options['pagination_next_text'],
        'max_links' => 5,
    ], $atts);

    if (!class_exists('Inmovilla_Query_State')) {
        return '<!-- Inmovilla Query State no disponible -->';
    }

    $total = Inmovilla_Query_State::get_total();
    $max_pages = Inmovilla_Query_State::get_max_pages();
    $current_page = Inmovilla_Query_State::get_current_page();
    $per_page = Inmovilla_Query_State::get_per_page();

    if ($max_pages <= 1) {
        return '';
    }

    $current_url = remove_query_arg('paged');
    $template = esc_attr($atts['template']);

    $output = '<nav class="inmovilla-pagination inmovilla-template-' . $template . '" aria-label="Paginación">';

    // Info de página
    if ($atts['show_info'] === 'true') {
        $from = (($current_page - 1) * $per_page) + 1;
        $to = min($current_page * $per_page, $total);
        $output .= '<div class="inmovilla-pagination-info">';
        $output .= sprintf('Mostrando %d-%d de %d resultados', $from, $to, $total);
        $output .= '</div>';
    }

    $output .= '<ul class="inmovilla-pagination-links">';

    // Botón Anterior
    if ($current_page > 1) {
        $prev_url = add_query_arg('paged', $current_page - 1, $current_url);
        $output .= '<li class="inmovilla-page-item inmovilla-prev">';
        $output .= '<a href="' . esc_url($prev_url) . '" class="inmovilla-page-link">';
        $output .= '<span class="inmovilla-icon">&#8249;</span>';
        $output .= '<span class="inmovilla-text">' . esc_html($atts['prev_text']) . '</span>';
        $output .= '</a></li>';
    }

    // Enlaces de páginas
    $max_links = intval($atts['max_links']);
    $half = floor($max_links / 2);
    $start = max(1, $current_page - $half);
    $end = min($max_pages, $start + $max_links - 1);

    if ($end - $start < $max_links - 1) {
        $start = max(1, $end - $max_links + 1);
    }

    if ($start > 1) {
        $output .= '<li class="inmovilla-page-item">';
        $output .= '<a href="' . esc_url(add_query_arg('paged', 1, $current_url)) . '" class="inmovilla-page-link">1</a>';
        $output .= '</li>';
        if ($start > 2) {
            $output .= '<li class="inmovilla-page-item inmovilla-ellipsis"><span>...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $is_current = ($i === $current_page);
        $output .= '<li class="inmovilla-page-item' . ($is_current ? ' inmovilla-current' : '') . '">';
        if ($is_current) {
            $output .= '<span class="inmovilla-page-link">' . $i . '</span>';
        } else {
            $output .= '<a href="' . esc_url(add_query_arg('paged', $i, $current_url)) . '" class="inmovilla-page-link">' . $i . '</a>';
        }
        $output .= '</li>';
    }

    if ($end < $max_pages) {
        if ($end < $max_pages - 1) {
            $output .= '<li class="inmovilla-page-item inmovilla-ellipsis"><span>...</span></li>';
        }
        $output .= '<li class="inmovilla-page-item">';
        $output .= '<a href="' . esc_url(add_query_arg('paged', $max_pages, $current_url)) . '" class="inmovilla-page-link">' . $max_pages . '</a>';
        $output .= '</li>';
    }

    // Botón Siguiente
    if ($current_page < $max_pages) {
        $next_url = add_query_arg('paged', $current_page + 1, $current_url);
        $output .= '<li class="inmovilla-page-item inmovilla-next">';
        $output .= '<a href="' . esc_url($next_url) . '" class="inmovilla-page-link">';
        $output .= '<span class="inmovilla-text">' . esc_html($atts['next_text']) . '</span>';
        $output .= '<span class="inmovilla-icon">&#8250;</span>';
        $output .= '</a></li>';
    }

    $output .= '</ul></nav>';

    return $output;
});

/**
 * Shortcode: Resumen de resultados
 * Uso: [inmovilla_results_summary template="modern"]
 */
add_shortcode('inmovilla_results_summary', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'format' => $ui_options['results_format'],
        'empty_text' => $ui_options['results_empty_text'],
    ], $atts);

    if (!class_exists('Inmovilla_Query_State')) {
        return '';
    }

    $total = Inmovilla_Query_State::get_total();
    $current_page = Inmovilla_Query_State::get_current_page();
    $per_page = Inmovilla_Query_State::get_per_page();
    $template = esc_attr($atts['template']);

    if ($total === 0) {
        return '<div class="inmovilla-results-summary inmovilla-template-' . $template . ' inmovilla-no-results">' . esc_html($atts['empty_text']) . '</div>';
    }

    $from = (($current_page - 1) * $per_page) + 1;
    $to = min($current_page * $per_page, $total);

    $text = str_replace(
        ['{from}', '{to}', '{total}', '{page}', '{pages}'],
        [$from, $to, number_format($total, 0, ',', '.'), $current_page, Inmovilla_Query_State::get_max_pages()],
        $atts['format']
    );

    return '<div class="inmovilla-results-summary inmovilla-template-' . $template . '">' . esc_html($text) . '</div>';
});

/**
 * Shortcode: Selector de ordenamiento
 * Uso: [inmovilla_order_select template="modern" label="Ordenar por:"]
 */
add_shortcode('inmovilla_order_select', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'label' => 'Ordenar por:',
        'default' => '',
        'show_label' => 'true',
    ], $atts);

    $order_fields = inmovilla_get_order_fields();
    $current_order = $_GET['orderby'] ?? $atts['default'];
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-order-select inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($atts['label'])) {
        $output .= '<label for="inmovilla-orderby">' . esc_html($atts['label']) . '</label>';
    }

    $output .= '<select id="inmovilla-orderby" name="orderby" onchange="inmovilla_apply_filter(this)">';
    $output .= '<option value="">-- Seleccionar --</option>';

    foreach ($order_fields as $key => $config) {
        $selected = ($current_order === $key) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($key) . '"' . $selected . '>' . esc_html($config['label']) . '</option>';
    }

    $output .= '</select></div>';

    return $output;
});

/**
 * Shortcode: Filtro select
 * Uso: [inmovilla_filter_select field="keyacci" template="modern"]
 */
add_shortcode('inmovilla_filter_select', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'field' => '',
        'label' => '',
        'placeholder' => 'Todos',
        'source' => '',
        'show_label' => 'true',
    ], $atts);

    if (empty($atts['field'])) {
        return '<!-- Campo de filtro no especificado -->';
    }

    $filter_fields = inmovilla_get_filter_fields();
    $field_config = $filter_fields[$atts['field']] ?? null;
    $current_value = $_GET[$atts['field']] ?? '';
    $label = !empty($atts['label']) ? $atts['label'] : ($field_config['label'] ?? ucfirst($atts['field']));
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-filter-select inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($label)) {
        $output .= '<label for="inmovilla-filter-' . esc_attr($atts['field']) . '">' . esc_html($label) . '</label>';
    }

    $output .= '<select id="inmovilla-filter-' . esc_attr($atts['field']) . '" name="' . esc_attr($atts['field']) . '" onchange="inmovilla_apply_filter(this)">';

    // Determinar el source_type
    $source_type = '';
    if (!empty($atts['source'])) {
        $source_type = $atts['source'];
    } elseif (!empty($field_config['source_type'])) {
        $source_type = $field_config['source_type'];
    }

    // Para zonas: requiere ciudad seleccionada
    if ($source_type === 'zonas' && empty($_GET['key_loca'])) {
        $output .= '<option value="">-- Selecciona ciudad primero --</option>';
    } else {
        $output .= '<option value="">' . esc_html($atts['placeholder']) . '</option>';

        $options = [];

        if (!empty($field_config['options'])) {
            $options = $field_config['options'];
        } elseif (!empty($source_type)) {
            $filter_params = [];
            if ($source_type === 'zonas' && !empty($_GET['key_loca'])) {
                $filter_params['cod_ciu'] = sanitize_text_field($_GET['key_loca']);
            }
            $options = inmovilla_get_filter_options($source_type, $filter_params);
        }

        if (defined('WP_DEBUG') && WP_DEBUG && !empty($source_type)) {
            error_log('INMOVILLA SELECT: field=' . $atts['field'] . ' | source_type=' . $source_type . ' | opciones=' . count($options));
        }

        foreach ($options as $value => $text) {
            $selected = ((string)$current_value === (string)$value) ? ' selected' : '';
            $output .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($text) . '</option>';
        }
    }

    $output .= '</select></div>';

    return $output;
});

/**
 * Shortcode: Filtro de rango numérico
 * Uso: [inmovilla_filter_range field="precio" template="modern"]
 */
add_shortcode('inmovilla_filter_range', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'field' => '',
        'label' => '',
        'min' => '0',
        'max' => '1000000',
        'step' => '1000',
        'from_placeholder' => 'Desde',
        'to_placeholder' => 'Hasta',
        'show_label' => 'true',
    ], $atts);

    if (empty($atts['field'])) {
        return '<!-- Campo de filtro no especificado -->';
    }

    $field_from = $atts['field'] . '_desde';
    $field_to = $atts['field'] . '_hasta';
    $current_from = $_GET[$field_from] ?? '';
    $current_to = $_GET[$field_to] ?? '';
    $template = esc_attr($atts['template']);

    $output = '<div class="inmovilla-filter-range inmovilla-template-' . $template . '">';

    if ($atts['show_label'] === 'true' && !empty($atts['label'])) {
        $output .= '<label>' . esc_html($atts['label']) . '</label>';
    }

    $output .= '<div class="inmovilla-range-inputs">';
    $output .= '<input type="number" name="' . esc_attr($field_from) . '" value="' . esc_attr($current_from) . '" placeholder="' . esc_attr($atts['from_placeholder']) . '" min="' . esc_attr($atts['min']) . '" max="' . esc_attr($atts['max']) . '" step="' . esc_attr($atts['step']) . '" onchange="inmovilla_apply_filter(this)">';
    $output .= '<span class="inmovilla-range-separator">-</span>';
    $output .= '<input type="number" name="' . esc_attr($field_to) . '" value="' . esc_attr($current_to) . '" placeholder="' . esc_attr($atts['to_placeholder']) . '" min="' . esc_attr($atts['min']) . '" max="' . esc_attr($atts['max']) . '" step="' . esc_attr($atts['step']) . '" onchange="inmovilla_apply_filter(this)">';
    $output .= '</div></div>';

    return $output;
});

/**
 * Shortcode: Contenedor de filtros
 * Uso: [inmovilla_filters_form template="modern"]...[/inmovilla_filters_form]
 */
add_shortcode('inmovilla_filters_form', function($atts, $content = null) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'layout' => 'horizontal', // horizontal, vertical, grid
        'submit_text' => $ui_options['filter_submit_text'],
        'show_submit' => 'true',
        'show_clear' => 'true',
        'clear_text' => $ui_options['filter_clear_text'],
    ], $atts);

    $template = esc_attr($atts['template']);
    $layout = esc_attr($atts['layout']);

    $output = '<form class="inmovilla-filters-form inmovilla-template-' . $template . ' inmovilla-layout-' . $layout . '" method="get">';
    $output .= '<div class="inmovilla-filters-wrapper">';
    $output .= do_shortcode($content);
    $output .= '</div>';

    if ($atts['show_submit'] === 'true' || $atts['show_clear'] === 'true') {
        $output .= '<div class="inmovilla-filters-actions">';
        if ($atts['show_submit'] === 'true') {
            $ui_cfg = function_exists('inmovilla_get_ui_options') ? inmovilla_get_ui_options() : [];
            $btn_bg = !empty($ui_cfg['primary_color']) ? $ui_cfg['primary_color'] : '#2563eb';
            $btn_hover = !empty($ui_cfg['primary_hover_color']) ? $ui_cfg['primary_hover_color'] : '#1d4ed8';
            $btn_radius = (!empty($ui_cfg['border_radius']) ? $ui_cfg['border_radius'] : 8) . 'px';
            $btn_text_color = '#ffffff';

            $output .= '<button type="submit" class="inmovilla-btn inmovilla-btn-primary" style="'
                . 'background-color:' . esc_attr($btn_bg) . ' !important;'
                . 'color:' . esc_attr($btn_text_color) . ' !important;'
                . 'font-size:14px !important;'
                . 'font-weight:600 !important;'
                . 'padding:12px 24px !important;'
                . 'border:none !important;'
                . 'border-radius:' . esc_attr($btn_radius) . ' !important;'
                . 'cursor:pointer !important;'
                . 'line-height:1.4 !important;'
                . 'display:inline-block !important;'
                . 'text-align:center !important;'
                . 'opacity:1 !important;'
                . 'visibility:visible !important;'
                . '">' . esc_html($atts['submit_text']) . '</button>';
        }
        if ($atts['show_clear'] === 'true') {
            $base_url = strtok($_SERVER['REQUEST_URI'], '?');
            $output .= '<a href="' . esc_url($base_url) . '" class="inmovilla-btn inmovilla-btn-secondary">' . esc_html($atts['clear_text']) . '</a>';
        }
        $output .= '</div>';
    }

    $output .= '</form>';

    return $output;
});

/**
 * Shortcode: Botón limpiar filtros
 */
add_shortcode('inmovilla_clear_filters', function($atts) {
    $ui_options = inmovilla_get_element_options();

    $atts = shortcode_atts([
        'template' => $ui_options['default_template'],
        'text' => $ui_options['filter_clear_text'],
    ], $atts);

    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
    $template = esc_attr($atts['template']);

    return '<a href="' . esc_url($base_url) . '" class="inmovilla-clear-filters inmovilla-template-' . $template . '">' . esc_html($atts['text']) . '</a>';
});

/**
 * Obtener opciones de filtro desde la API
 * Para zonas, necesita cod_ciu como parámetro
 */
function inmovilla_get_filter_options($source_type, $filter_params = []) {
    // Crear cache key con parámetros si existen
    $cache_suffix = '';
    if ($source_type === 'zonas') {
        $cod_ciu = $filter_params['cod_ciu'] ?? sanitize_text_field($_GET['key_loca'] ?? '');
        if (!empty($cod_ciu)) {
            $cache_suffix = '_' . $cod_ciu;
        }
    }

    $cache_key = 'inmovilla_filter_options_' . $source_type . $cache_suffix;
    // Solo usar cache si tiene datos (no cachear arrays vacíos)
    $cached = get_transient($cache_key);
    if ($cached !== false && !empty($cached)) {
        return $cached;
    }

    // Borrar transient vacío si existe
    if ($cached !== false && empty($cached)) {
        delete_transient($cache_key);
    }

    $options = [];
    $sources = get_option('bricks_api_sources', []);
    $source_key = 'inmovilla_' . $source_type;

    // Si el source no existe, intentar crear uno automáticamente
    if (!isset($sources[$source_key])) {
        // Buscar el endpoint de Inmovilla
        $endpoints = get_option('bricks_api_endpoints', []);
        $inmovilla_endpoint_id = null;
        foreach ($endpoints as $id => $endpoint) {
            if (!is_array($endpoint)) {
                continue;
            }
            if (strpos($endpoint['url'] ?? '', 'apiweb.inmovilla.com') !== false) {
                $inmovilla_endpoint_id = $id;
                break;
            }
        }

        // Si no hay endpoint, no se puede continuar
        if ($inmovilla_endpoint_id === null) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('INMOVILLA FILTER_OPTIONS: No endpoint encontrado, source ' . $source_key . ' no puede crearse');
            }
            return $options;
        }

        // Crear el source dinámicamente
        $sources[$source_key] = [
            'name' => 'Inmovilla - ' . ucfirst($source_type),
            'query_type_name' => ucfirst($source_type) . ' (Inmovilla)',
            'endpoint_id' => $inmovilla_endpoint_id,
            'items_path' => $source_type,
            'tipo' => $source_type,
            'pagination_type' => 'none',
            'supports_filters' => $source_type === 'zonas',
            'supports_sorting' => false,
            'supports_pagination' => false,
        ];
    }

    // Para zonas con cod_ciu, pasar en el where
    $query_args = ['per_page' => 1000, 'skip_url_filters' => true];
    if ($source_type === 'zonas' && !empty($cod_ciu)) {
        $query_args['where'] = 'cod_ciu=' . $cod_ciu;
    }

    // Asegurar que el source tenga el campo 'tipo' correcto
    if (empty($sources[$source_key]['tipo'])) {
        $sources[$source_key]['tipo'] = $source_type;
    }

    $handler = Inmovilla_Query_Handler::get_instance();
    $result = $handler->execute_query($sources[$source_key], $query_args);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('INMOVILLA FILTER_OPTIONS: source_type=' . $source_type . ' | items=' . count($result['items'] ?? []) . ' | error=' . ($result['error'] ?? 'ninguno'));
        // Log del primer item para diagnóstico de campos
        if (!empty($result['items'][0])) {
            $first = (array) $result['items'][0];
            error_log('INMOVILLA FILTER_OPTIONS: primer_item_campos=' . implode(',', array_keys($first)));
        }
    }

    if (empty($result['error']) && !empty($result['items'])) {
        foreach ($result['items'] as $item) {
            $item = (array) $item;
            switch ($source_type) {
                case 'tipos':
                    if (isset($item['cod_tipo']) && isset($item['tipo'])) {
                        $options[$item['cod_tipo']] = $item['tipo'];
                    }
                    break;
                case 'ciudades':
                    if (isset($item['cod_ciu']) && isset($item['city'])) {
                        $options[$item['cod_ciu']] = $item['city'];
                    }
                    break;
                case 'zonas':
                    if (isset($item['cod_zona']) && isset($item['zona'])) {
                        $options[$item['cod_zona']] = $item['zona'];
                    }
                    break;
            }
        }
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('INMOVILLA FILTER_OPTIONS: resultado_final=' . count($options) . ' opciones | keys=' . implode(',', array_keys($options)));
    }

    // Solo cachear si hay resultados
    if (!empty($options)) {
        set_transient($cache_key, $options, HOUR_IN_SECONDS);
    }
    return $options;
}

/**
 * Obtener estilos CSS de los elementos (sin variables, esas se definen en inmovilla-settings.php)
 */
function inmovilla_get_element_styles() {
    return '
    /* ============================================
       ESTILOS BASE (Compartidos)
       ============================================ */
    .inmovilla-pagination,
    .inmovilla-results-summary,
    .inmovilla-filter-select,
    .inmovilla-order-select,
    .inmovilla-filter-range,
    .inmovilla-filters-form {
        font-family: inherit;
        box-sizing: border-box;
    }

    .inmovilla-pagination-links {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        list-style: none;
        padding: 0;
        margin: 0;
        align-items: center;
    }

    .inmovilla-page-item {
        display: inline-flex;
    }

    /* ============================================
       TEMPLATE: CLASSIC
       ============================================ */
    .inmovilla-template-classic {
        --t-primary: #333333;
        --t-border: #cccccc;
        --t-bg: #f5f5f5;
        --t-radius: 3px;
    }

    .inmovilla-template-classic.inmovilla-pagination {
        margin: 20px 0;
    }

    .inmovilla-template-classic .inmovilla-pagination-info {
        margin-bottom: 10px;
        color: #666;
        font-size: 13px;
    }

    .inmovilla-template-classic .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        padding: 6px 12px;
        border: 1px solid var(--t-border);
        background: var(--t-bg);
        color: var(--t-primary);
        text-decoration: none;
        font-size: 13px;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-classic .inmovilla-page-link:hover {
        background: #e5e5e5;
    }

    .inmovilla-template-classic .inmovilla-current .inmovilla-page-link {
        background: var(--t-primary);
        border-color: var(--t-primary);
        color: #fff;
    }

    .inmovilla-template-classic .inmovilla-ellipsis span {
        padding: 6px 8px;
    }

    .inmovilla-template-classic.inmovilla-results-summary {
        color: #666;
        font-size: 13px;
        padding: 8px 0;
        border-bottom: 1px solid var(--t-border);
        margin-bottom: 15px;
    }

    .inmovilla-template-classic select,
    .inmovilla-template-classic input[type="number"] {
        padding: 8px 10px;
        border: 1px solid var(--t-border);
        border-radius: var(--t-radius);
        font-size: 13px;
        background: #fff;
        color: #333;
    }

    .inmovilla-template-classic select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 30px;
        cursor: pointer;
    }

    .inmovilla-template-classic label {
        display: block;
        margin-bottom: 4px;
        font-size: 13px;
        font-weight: 600;
        color: #333;
    }

    /* ============================================
       TEMPLATE: MODERN (Default)
       ============================================ */
    .inmovilla-template-modern {
        --t-primary: var(--inmovilla-primary);
        --t-radius: 8px;
    }

    .inmovilla-template-modern.inmovilla-pagination {
        margin: 24px 0;
    }

    .inmovilla-template-modern .inmovilla-pagination-info {
        margin-bottom: 12px;
        color: var(--inmovilla-text-light);
        font-size: 14px;
        font-weight: 500;
    }

    .inmovilla-template-modern .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border: 1px solid var(--inmovilla-border);
        border-radius: var(--t-radius);
        background: var(--inmovilla-bg);
        color: var(--inmovilla-text);
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: var(--inmovilla-transition);
        box-shadow: var(--inmovilla-shadow);
    }

    .inmovilla-template-modern .inmovilla-page-link:hover {
        background: var(--inmovilla-bg-hover);
        border-color: var(--t-primary);
        color: var(--t-primary);
    }

    .inmovilla-template-modern .inmovilla-current .inmovilla-page-link {
        background: var(--t-primary);
        border-color: var(--t-primary);
        color: #fff;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
    }

    .inmovilla-template-modern .inmovilla-ellipsis span {
        padding: 10px 8px;
        color: var(--inmovilla-text-light);
    }

    .inmovilla-template-modern .inmovilla-icon {
        font-size: 18px;
        line-height: 1;
    }

    .inmovilla-template-modern.inmovilla-results-summary {
        color: var(--inmovilla-text);
        font-size: 15px;
        font-weight: 500;
        padding: 12px 16px;
        background: var(--inmovilla-bg-hover);
        border-radius: var(--t-radius);
        margin-bottom: 20px;
    }

    .inmovilla-template-modern select,
    .inmovilla-template-modern input[type="number"] {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--inmovilla-border);
        border-radius: var(--t-radius);
        font-size: 14px;
        background: var(--inmovilla-bg);
        color: var(--inmovilla-text);
        transition: var(--inmovilla-transition);
        box-shadow: var(--inmovilla-shadow);
    }

    .inmovilla-template-modern select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 40px;
        cursor: pointer;
    }

    .inmovilla-template-modern select:focus,
    .inmovilla-template-modern input[type="number"]:focus {
        outline: none;
        border-color: var(--t-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .inmovilla-template-modern label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--inmovilla-text);
    }

    .inmovilla-template-modern .inmovilla-filter-select,
    .inmovilla-template-modern .inmovilla-order-select,
    .inmovilla-template-modern .inmovilla-filter-range {
        margin-bottom: 16px;
    }

    .inmovilla-template-modern .inmovilla-range-inputs {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .inmovilla-template-modern .inmovilla-range-inputs input {
        flex: 1;
    }

    .inmovilla-template-modern .inmovilla-range-separator {
        color: var(--inmovilla-text-light);
        font-weight: 500;
    }

    /* ============================================
       TEMPLATE: MINIMAL
       ============================================ */
    .inmovilla-template-minimal {
        --t-primary: #18181b;
    }

    .inmovilla-template-minimal.inmovilla-pagination {
        margin: 20px 0;
    }

    .inmovilla-template-minimal .inmovilla-pagination-info {
        margin-bottom: 8px;
        color: #71717a;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inmovilla-template-minimal .inmovilla-page-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border: none;
        background: transparent;
        color: #71717a;
        text-decoration: none;
        font-size: 14px;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-minimal .inmovilla-page-link:hover {
        color: var(--t-primary);
    }

    .inmovilla-template-minimal .inmovilla-current .inmovilla-page-link {
        color: var(--t-primary);
        font-weight: 700;
        border-bottom: 2px solid var(--t-primary);
        border-radius: 0;
    }

    .inmovilla-template-minimal .inmovilla-ellipsis span {
        padding: 8px 4px;
        color: #a1a1aa;
    }

    .inmovilla-template-minimal.inmovilla-results-summary {
        color: #71717a;
        font-size: 13px;
        margin-bottom: 16px;
    }

    .inmovilla-template-minimal select,
    .inmovilla-template-minimal input[type="number"] {
        padding: 10px 12px;
        border: none;
        border-bottom: 1px solid #e4e4e7;
        border-radius: 0;
        font-size: 14px;
        background: transparent;
        transition: var(--inmovilla-transition);
    }

    .inmovilla-template-minimal select:focus,
    .inmovilla-template-minimal input[type="number"]:focus {
        outline: none;
        border-color: var(--t-primary);
    }

    .inmovilla-template-minimal label {
        display: block;
        margin-bottom: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #71717a;
    }

    .inmovilla-template-minimal .inmovilla-range-inputs {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .inmovilla-template-minimal .inmovilla-range-separator {
        color: #a1a1aa;
    }

    /* ============================================
       TEMPLATE: CUSTOM (Base para personalizar)
       ============================================ */
    .inmovilla-template-custom {
        /* Usa las variables CSS de :root */
    }

    /* ============================================
       FILTROS FORM - Layouts
       ============================================ */
    .inmovilla-filters-form {
        margin-bottom: 24px;
    }

    .inmovilla-layout-horizontal .inmovilla-filters-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .inmovilla-layout-horizontal .inmovilla-filter-select,
    .inmovilla-layout-horizontal .inmovilla-order-select,
    .inmovilla-layout-horizontal .inmovilla-filter-range {
        flex: 1;
        min-width: 180px;
        margin-bottom: 0;
    }

    .inmovilla-layout-vertical .inmovilla-filters-wrapper {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .inmovilla-layout-grid .inmovilla-filters-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .inmovilla-filters-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid var(--inmovilla-border);
    }

    .inmovilla-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        border: none;
        border-radius: var(--inmovilla-radius);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: var(--inmovilla-transition);
        line-height: 1.4;
    }

    .inmovilla-filters-form .inmovilla-btn-primary,
    button.inmovilla-btn-primary,
    .inmovilla-btn-primary {
        background-color: var(--inmovilla-primary, #2563eb) !important;
        color: #fff !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        opacity: 1 !important;
        visibility: visible !important;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }

    .inmovilla-filters-form .inmovilla-btn-primary:hover,
    button.inmovilla-btn-primary:hover,
    .inmovilla-btn-primary:hover {
        background-color: var(--inmovilla-primary-hover, #1d4ed8) !important;
        color: #fff !important;
    }

    .inmovilla-btn-secondary {
        background: var(--inmovilla-bg-hover);
        color: var(--inmovilla-text);
        border: 1px solid var(--inmovilla-border);
    }

    .inmovilla-btn-secondary:hover {
        background: #e2e8f0;
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 640px) {
        .inmovilla-pagination-links {
            justify-content: center;
        }

        .inmovilla-template-modern .inmovilla-page-link,
        .inmovilla-template-classic .inmovilla-page-link {
            padding: 8px 12px;
            font-size: 13px;
        }

        .inmovilla-template-modern .inmovilla-text,
        .inmovilla-template-classic .inmovilla-text {
            display: none;
        }

        .inmovilla-layout-horizontal .inmovilla-filters-wrapper {
            flex-direction: column;
        }

        .inmovilla-layout-horizontal .inmovilla-filter-select,
        .inmovilla-layout-horizontal .inmovilla-order-select {
            min-width: 100%;
        }

        .inmovilla-filters-actions {
            flex-direction: column;
        }

        .inmovilla-btn {
            width: 100%;
        }
    }
    ';
}

/**
 * Endpoint AJAX para cargar zonas dinámicamente cuando cambia la ciudad
 */
add_action('wp_ajax_nopriv_inmovilla_get_zones', function() {
    $cod_ciu = isset($_GET['cod_ciu']) ? sanitize_text_field($_GET['cod_ciu']) : '';

    if (empty($cod_ciu)) {
        wp_send_json_error(['message' => 'Código de ciudad requerido']);
    }

    $options = inmovilla_get_filter_options('zonas', ['cod_ciu' => $cod_ciu]);

    wp_send_json_success(['options' => $options]);
});

add_action('wp_ajax_inmovilla_get_zones', function() {
    $cod_ciu = isset($_GET['cod_ciu']) ? sanitize_text_field($_GET['cod_ciu']) : '';

    if (empty($cod_ciu)) {
        wp_send_json_error(['message' => 'Código de ciudad requerido']);
    }

    $options = inmovilla_get_filter_options('zonas', ['cod_ciu' => $cod_ciu]);

    wp_send_json_success(['options' => $options]);
});

/**
 * Registrar estilos CSS de los templates
 */
add_action('wp_head', function() {
    echo '<style id="inmovilla-elements-styles">' . inmovilla_get_element_styles() . '</style>';
}, 101); // Después de inmovilla-custom-styles (100)

/**
 * Registrar JavaScript
 */
add_action('wp_footer', function() {
    ?>
    <script id="inmovilla-elements-scripts">
    function inmovilla_apply_filter(element) {
        var form = element.closest('.inmovilla-filters-form');

        // Si es la ciudad (key_loca), recargar opciones de zonas siempre
        if (element.name === 'key_loca') {
            inmovilla_reload_zone_options(element.value);
        }

        // Si está dentro de un formulario, solo marcar como changed
        if (form) {
            form.classList.add('inmovilla-filters-changed');
            return false;
        }

        // Si no está en formulario (filtro independiente), aplicar inmediatamente
        var url = new URL(window.location.href);
        var name = element.name;
        var value = element.value;

        // Si es ciudad y hay filtro de zona, no recargar todavía
        // (el usuario primero seleccionará la zona)
        if (element.name === 'key_loca') {
            var zoneSelect = document.querySelector('select[name="key_zona"]');
            if (zoneSelect) {
                // Actualizar la URL sin recargar, para que cuando seleccione zona se incluya la ciudad
                url.searchParams.delete('paged');
                url.searchParams.delete('key_zona');
                if (value) {
                    url.searchParams.set(name, value);
                } else {
                    url.searchParams.delete(name);
                }
                window.history.replaceState({}, '', url.toString());
                return;
            }
        }

        url.searchParams.delete('paged');
        if (value) {
            url.searchParams.set(name, value);
        } else {
            url.searchParams.delete(name);
        }

        window.location.href = url.toString();
    }

    // Recargar opciones de zonas cuando cambia la ciudad
    function inmovilla_reload_zone_options(cod_ciu) {
        var zoneSelect = document.querySelector('select[name="key_zona"]');
        if (!zoneSelect) return;

        if (!cod_ciu) {
            // Si no hay ciudad, limpiar zonas
            zoneSelect.innerHTML = '<option value="">-- Selecciona ciudad primero --</option>';
            return;
        }

        // Mostrar estado de carga
        zoneSelect.innerHTML = '<option value="">Cargando zonas...</option>';

        // Usar endpoint AJAX de WordPress
        var ajaxUrl = '<?php echo admin_url("admin-ajax.php"); ?>';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', ajaxUrl + '?action=inmovilla_get_zones&cod_ciu=' + encodeURIComponent(cod_ciu), true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success && response.data && response.data.options) {
                        var html = '<option value="">-- Selecciona zona --</option>';
                        var options = response.data.options;
                        for (var value in options) {
                            html += '<option value="' + value + '">' + options[value] + '</option>';
                        }
                        zoneSelect.innerHTML = html;
                    } else {
                        zoneSelect.innerHTML = '<option value="">No hay zonas disponibles</option>';
                    }
                } catch(e) {
                    console.error('Error al cargar zonas:', e);
                    zoneSelect.innerHTML = '<option value="">Error al cargar zonas</option>';
                }
            }
        };
        xhr.onerror = function() {
            zoneSelect.innerHTML = '<option value="">Error de conexión</option>';
        };
        xhr.send();
    }

    // Manejar submit del formulario
    document.querySelectorAll('.inmovilla-filters-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var url = new URL(window.location.origin + window.location.pathname);

            form.querySelectorAll('select, input').forEach(function(input) {
                if (input.value && input.name) {
                    url.searchParams.set(input.name, input.value);
                }
            });

            window.location.href = url.toString();
        });
    });
    </script>
    <?php
});
